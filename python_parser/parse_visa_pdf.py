#!/usr/bin/env python3
import os
import sys
import json
import re
from pathlib import Path

import pdfplumber


class VisaAppointmentParser:
    """
    Универсальный парсер подтверждений записи в визовые центры.

    Поддерживает:
    - VFS Global (France, Poland, Bulgaria, UK/Seasonal worker и т.п.)
    - BLS Spain (отдельная ветка разбора таблицы)
    """

    def __init__(self, pdf_path: str):
        self.pdf_path = Path(pdf_path)
        if not self.pdf_path.exists():
            raise FileNotFoundError(f"PDF not found: {self.pdf_path}")

        self.full_text = ""
        self.provider = None

        self.meta = {
            "provider": None,
            "group_urn": None,
            "appointment_date": None,
            "appointment_time": None,
            "visa_category_global": None,
            "raw_text_locale": None,
        }

    # ---------------------- PUBLIC API ---------------------- #

    def parse(self) -> dict:
        # 1) сначала тянем полный текст (для шапки, провайдера и BLS)
        self._extract_text_with_layout()

        # 2) определяем провайдера по полному тексту
        self._detect_provider()

        # 3) парсим глобальные метаданные (дата/время/категория/группа)
        self._parse_global_metadata()

        # 4) парсим заявителей (VFS или BLS)
        applicants = self._parse_applicants_from_pdf()
        applicants = self._filter_invalid_applicants(applicants)
        self._refine_visa_category_with_applicants(applicants)

        # Фоллбек: дата/время из первого аппликанта, если в шапке пусто
        if applicants:
            first = applicants[0]
            if not self.meta.get("appointment_date") and first.get("appointment_date"):
                self.meta["appointment_date"] = first["appointment_date"]
            if not self.meta.get("appointment_time") and first.get("appointment_time"):
                self.meta["appointment_time"] = first["appointment_time"]

        # Фоллбек: group_urn из reference_number, если в шапке он не найден
        if not self.meta.get("group_urn") and applicants:
            base_candidates = set()

            for a in applicants:
                ref = (a.get("reference_number") or "").strip()
                # Ищем паттерн вроде BUL67427091816/1, PLTR79622146950/3 и т.п.
                m = re.match(r"^([A-Z0-9]+)/\d+$", ref, flags=re.IGNORECASE)
                if m:
                    base_candidates.add(m.group(1))

            # Если нашли ровно один "базовый" номер — считаем его group_urn
            if len(base_candidates) == 1:
                base = base_candidates.pop()
                self.meta["group_urn"] = base

                # И одновременно чистим reference_number у совпавших заявителей:
                # "BUL67427091816/1" -> "BUL67427091816"
                for a in applicants:
                    ref = (a.get("reference_number") or "").strip()
                    if re.match(rf"^{re.escape(base)}/\d+$", ref, flags=re.IGNORECASE):
                        a["reference_number"] = base

        result = {
            "source_file": os.path.basename(self.pdf_path),
            "provider": self.provider,
            "group_urn": self.meta.get("group_urn"),
            "appointment_date": self.meta.get("appointment_date"),
            "appointment_time": self.meta.get("appointment_time"),
            "visa_category_global": self.meta.get("visa_category_global"),
            "raw_text_locale": self.meta.get("raw_text_locale"),
            "applicants": applicants,
        }
        return result

    # ---------------------- BASIC TEXT ---------------------- #

    def _extract_text_with_layout(self):
        """
        Заполняем self.full_text и raw_text_locale.
        Этого достаточно для наших задач (табличный layout берём через pdfplumber в других местах).
        """
        self.full_text = self._extract_full_text() or ""

        txt = self.full_text
        # Простая эвристика: если есть кириллица — ru, иначе en
        if re.search(r"[А-Яа-яЁё]", txt):
            locale = "ru"
        else:
            locale = "en"
        self.meta["raw_text_locale"] = locale

    def _detect_provider(self):
        """
        Определяем провайдера по полному тексту.
        """
        text = self.full_text or self._extract_full_text()

        if "BLS Spain Application Centre" in text:
            provider = "BLS Spain"
        elif "VFS Global" in text or "VFS G\nLOBAL" in text or "VFS G LOBAL" in text:
            provider = "VFS Global"
        elif "iDATA" in text:
            provider = "iDATA"
        else:
            provider = None

        self.meta["provider"] = provider
        self.provider = provider

    def _extract_full_text(self) -> str:
        parts = []
        with pdfplumber.open(self.pdf_path) as pdf:
            for page in pdf.pages:
                text = page.extract_text() or ""
                parts.append(text)
        return "\n".join(parts)

    # ---------------------- GLOBAL META ---------------------- #

    def _normalize_time(self, text: str | None) -> str | None:
        """
        Нормализация времени:
        - если формат интервала, напр. '09:30-10:00' → берём только '09:30'
        - остальное возвращаем как есть (включая '08:00 am', '8:45' и т.п.)
        """
        if not text:
            return None

        t = text.strip()

        # Временной интервал с возможным am/pm слева/справа
        interval = re.match(
            r"^(\d{1,2}:\d{2}(?:\s*(?:am|pm|AM|PM))?)\s*[-–]\s*\d{1,2}:\d{2}(?:\s*(?:am|pm|AM|PM))?$",
            t,
        )
        if interval:
            return interval.group(1).strip()

        return t

    def _parse_global_metadata(self):
        """
        Построчный разбор шапки: provider, group_urn, date/time, visa category.
        """
        text = self.full_text
        lines_raw = text.splitlines()
        lines = [ln.strip() for ln in lines_raw]

        # --- Provider --- (если ещё не определён)
        if not self.meta.get("provider"):
            if "BLS Spain Application Centre" in text:
                self.meta["provider"] = "BLS Spain"
            elif "VFS Global" in text or "VFS G\nLOBAL" in text or "VFS G LOBAL" in text:
                self.meta["provider"] = "VFS Global"
            elif "iDATA" in text:
                self.meta["provider"] = "iDATA"
            else:
                self.meta["provider"] = None

        # синхронизируем с self.provider
        self.provider = self.meta["provider"]

        # --- Visa Type (BLS Spain, но может встретиться ещё где-то) ---
        m = re.search(r"Visa\s+Type[ \t]*:[ \t]*([^\n\r]+)", text)
        if m:
            self.meta["visa_category_global"] = m.group(1).strip()

        date_pattern = re.compile(
            r"(\d{2}[./-]\d{2}[./-]\d{4}|\d{4}[./-]\d{2}[./-]\d{2})"
        )
        time_pattern = re.compile(
            r"(\d{1,2}:\d{2}"
            r"(?:\s*(?:am|pm|AM|PM))?"
            r"(?:\s*-\s*\d{1,2}:\d{2}(?:\s*(?:am|pm|AM|PM))?)?)"
        )

        def next_non_empty_value(start_idx, max_steps=5, regex=None):
            for j in range(start_idx + 1, min(len(lines), start_idx + 1 + max_steps)):
                candidate = lines[j].strip()
                if not candidate:
                    continue
                if regex is None:
                    return candidate
                m_ = regex.search(candidate)
                if m_:
                    return m_.group(1)
            return None

        # --- Group URN (может быть на следующей строке, особенно у BLS) ---
        for i, ln in enumerate(lines):
            low = ln.lower()
            if "group urn" in low and self.meta["group_urn"] is None:
                m = re.search(r"Group\s+URN\s*[-:]\s*([A-Z0-9]+)", ln)
                if m:
                    self.meta["group_urn"] = m.group(1)
                else:
                    # берем первую непустую строку ниже
                    val = next_non_empty_value(i, max_steps=3)
                    if val:
                        token = val.split()[0]
                        token = re.sub(r"[^A-Z0-9]", "", token)
                        if token:
                            self.meta["group_urn"] = token
                break

        # fallback: ищем "похожую" строку, если group_urn всё ещё странный
        if not self.meta["group_urn"] or len(self.meta["group_urn"]) < 5:
            for ln in lines:
                cand = ln.strip().replace(" ", "")
                if re.fullmatch(r"[A-Z]{2,4}\d{6,}", cand):
                    self.meta["group_urn"] = cand
                    break

        # --- Appointment Date / Time / Category (построчно) ---
        for i, ln in enumerate(lines):
            low = ln.lower()

            # Appointment Date
            if "appointment date" in low and self.meta["appointment_date"] is None:
                m = date_pattern.search(ln)
                if m:
                    self.meta["appointment_date"] = m.group(1)
                else:
                    v = next_non_empty_value(i, regex=date_pattern)
                    if v:
                        self.meta["appointment_date"] = v

            # Appointment Time
            if "appointment time" in low and self.meta["appointment_time"] is None:
                m = time_pattern.search(ln)
                if m:
                    self.meta["appointment_time"] = m.group(1)
                else:
                    v = next_non_empty_value(i, regex=time_pattern)
                    if v:
                        self.meta["appointment_time"] = v

            # Visa Category (VFS)
            if "visa category" in low and self.meta["visa_category_global"] is None:
                collected = []
                for j in range(i + 1, min(len(lines), i + 1 + 5)):
                    v = lines[j].strip()
                    if not v:
                        continue
                    vlow = v.lower()
                    if (
                        vlow.startswith("address")
                        or vlow.startswith("visa application center")
                        or vlow.startswith("visa application centre")
                        or vlow.startswith("number of")
                        or vlow.startswith("email id")
                    ):
                        break
                    collected.append(v)
                if collected:
                    self.meta["visa_category_global"] = " ".join(collected)

            # Category (без слова Visa)
            if (
                self.meta["visa_category_global"] is None
                and low.startswith("category")
            ):
                after = ln.split("Category", 1)[1].strip(" :\t-")
                parts = []
                if after:
                    parts.append(after)
                for j in range(i + 1, min(len(lines), i + 1 + 3)):
                    v = lines[j].strip()
                    if not v:
                        continue
                    vlow = v.lower()
                    if (
                        vlow.startswith("address")
                        or vlow.startswith("visa application center")
                        or vlow.startswith("visa application centre")
                        or vlow.startswith("number of")
                        or vlow.startswith("email id")
                    ):
                        break
                    parts.append(v)
                if parts:
                    self.meta["visa_category_global"] = " ".join(parts)

        # 🔹 Нормализуем время в шапке (срежем интервалы типа 09:30-10:00)
        if self.meta.get("appointment_time"):
            self.meta["appointment_time"] = self._normalize_time(
                self.meta["appointment_time"]
            )

    # ---------------------- APPLICANT PARSING ---------------------- #

    def _parse_applicants_from_pdf(self):
        # BLS Spain — свой отдельный парсер, без таблиц с координатами
        if self.meta["provider"] == "BLS Spain":
            return self._parse_bls_spain()

        applicants = []
        with pdfplumber.open(self.pdf_path) as pdf:
            for page_index, page in enumerate(pdf.pages):
                page_applicants = self._parse_applicants_from_page(page)
                if page_applicants:
                    applicants.extend(page_applicants)
        return applicants

    # ---------- BLS Spain специальный разбор ---------- #

    def _parse_bls_spain(self):
        """
        Специальный парсер BLS Spain.

        Структура (пример с 1 заявителем):

            Appointment Details
            Passport Appointment
            Applicant Name Reference Number Value Added Services
            Number Date & Time
            2025-10-01
            GUR***** AKG***** *****051 Premium
            13:30-13:45
            IST486202125425

        Или с несколькими заявителями – такие же блоки повторяются.
        """
        lines = self.full_text.splitlines()

        # Ищем блок "Appointment Details"
        start_idx = None
        for i, line in enumerate(lines):
            if line.strip().lower() == "appointment details":
                start_idx = i
                break

        if start_idx is None:
            return []

        # Регэкспы для даты/времени/референса
        date_re = re.compile(r"\d{4}-\d{2}-\d{2}")
        time_re = re.compile(r"\d{1,2}:\d{2}(?:-\d{1,2}:\d{2})?")
        ref_re = re.compile(r"[A-Z]{3}\d{9,}(?:/\d+)?")

        applicants = []

        i = start_idx + 1
        # Пролистываем шапку таблицы до первой строки с датой
        while i < len(lines) and not date_re.fullmatch(lines[i].strip()):
            i += 1

        # Дальше ожидаем блоки по 4 строки:
        #   дата
        #   строка "ФИО + паспорт (+ VAS)"
        #   время
        #   reference number
        while i + 3 < len(lines):
            date_line = lines[i].strip()
            if not date_re.fullmatch(date_line):
                break

            name_pass = lines[i + 1].strip()
            time_line = lines[i + 2].strip()
            ref_line = lines[i + 3].strip()

            # Проверяем время и reference
            if not time_re.fullmatch(time_line) or not ref_re.fullmatch(ref_line):
                break

            tokens = name_pass.split()
            if not tokens:
                break

            # --- ищем паспорт с конца, чтобы не поймать VAS (Premium/Normal) ---
            passport = None
            name_tokens = tokens
            for idx_tok in range(len(tokens) - 1, -1, -1):
                tok = tokens[idx_tok]
                if self._passport_looks_valid(tok):
                    passport = tok
                    name_tokens = tokens[:idx_tok]
                    break

            # fallback: последний токен считаем паспортом
            if passport is None:
                passport = tokens[-1]
                name_tokens = tokens[:-1]

            name = " ".join(name_tokens) or None
            passport_masked = any(ch in passport for ch in "*xX")

            appointment_date = date_line

            # нормализуем время: '13:30-13:45' → '13:30'
            time_norm = self._normalize_time(time_line)
            appointment_time = time_norm
            datetime_raw = (
                f"{appointment_date} {time_norm}".strip() if time_norm else appointment_date
            )

            # глобальная категория уже должна быть распарсена в _parse_global_metadata
            global_cat = self.meta.get("visa_category_global")
            visa_category = (
                self._clean_visa_category_text(global_cat) if global_cat else None
            )

            applicants.append(
                {
                    "name": name,
                    "passport": passport,
                    "passport_masked": passport_masked,
                    "appointment_date": appointment_date,
                    "appointment_time": appointment_time,
                    "datetime_raw": datetime_raw,
                    "visa_category": visa_category,
                    "reference_number": ref_line,
                    "special_reference_number": None,
                }
            )

            i += 4

        return applicants

    # ---------- VFS / Generic таблица ---------- #

    def _parse_applicants_from_page(self, page):
        words = page.extract_words(
            keep_blank_chars=False,
            x_tolerance=2,
            y_tolerance=3,
        )
        if not words:
            return []

        lines = self._group_words_into_lines(words)
        if not lines:
            return []

        # Находим строку шапки: там должны быть и "Applicant", и "Passport"
        header_index = None
        for idx, line in enumerate(lines):
            text = " ".join(w["text"] for w in line).lower()
            if "applicant" in text and "passport" in text:
                header_index = idx
                break

        # fallback через "Appointment Details"
        if header_index is None:
            for idx, line in enumerate(lines):
                text = " ".join(w["text"] for w in line).lower()
                if "appointment" in text and "details" in text:
                    if idx + 1 < len(lines):
                        header_index = idx + 1
                        break

        if header_index is None or header_index >= len(lines):
            return []

        header_line = lines[header_index]
        header_cells = self._build_header_cells(header_line)
        if not header_cells:
            return []

        column_map = self._map_header_cells_to_logical_columns(header_cells)
        if not column_map:
            return []

        # --- АГРЕГАТОР СТРОК В ОДНУ ЗАПИСЬ ЗАЯВИТЕЛЯ ---
        data_start = header_index + 1
        applicants_raw_rows = []
        current_row = None
        empty_rows_in_a_row = 0

        for line in lines[data_start:]:
            line_text = " ".join(w["text"] for w in line).strip()
            low = line_text.lower()

            if any(
                stop in low
                for stop in [
                    "payment invoice",
                    "declaration",
                    "please note",
                    "© vfs global",
                    "thank you",
                    "for more visa information",
                ]
            ):
                break

            row = self._assign_line_to_columns(line, column_map)

            has_any_value = any(
                (row.get(k) or "").strip()
                for k in (
                    "name",
                    "passport",
                    "time",
                    "category",
                    "reference",
                    "special_reference",
                )
            )

            if not has_any_value:
                empty_rows_in_a_row += 1
                if empty_rows_in_a_row >= 2:
                    break
                continue
            else:
                empty_rows_in_a_row = 0

            new_has_name = bool((row.get("name") or "").strip())
            new_has_passport = bool((row.get("passport") or "").strip())

            starts_new_applicant = False
            if current_row is not None and (current_row.get("name") or current_row.get("passport")):
                if new_has_name or new_has_passport:
                    starts_new_applicant = True

            if starts_new_applicant:
                applicants_raw_rows.append(current_row)
                current_row = row
            else:
                if current_row is None:
                    current_row = row
                else:
                    for key in column_map.keys():
                        old_val = (current_row.get(key) or "").strip()
                        new_val = (row.get(key) or "").strip()
                        if new_val:
                            if old_val:
                                current_row[key] = f"{old_val} {new_val}"
                            else:
                                current_row[key] = new_val

        if current_row is not None:
            applicants_raw_rows.append(current_row)

        applicants = []
        for raw_row in applicants_raw_rows:
            applicant = self._build_applicant_object(raw_row)
            applicants.append(applicant)

        return applicants

    # ---------------------- APPLICANT POST-PROCESS ---------------------- #

    @staticmethod
    def _passport_looks_valid(passport: str) -> bool:
        """
        Паспорт:
        - 5–15 символов
        - только буквы/цифры/*/x/X
        - есть хотя бы одна цифра или маскирующий символ (*, x, X)
        """
        if not passport:
            return False

        p_raw = passport.strip()
        # убираем пробелы вообще
        p = re.sub(r"\s+", "", p_raw)

        if len(p) < 5 or len(p) > 15:
            return False

        # только допустимые символы
        if not re.fullmatch(r"[A-Za-z0-9*Xx]+", p):
            return False

        # должна быть цифра или маска
        if not any(ch.isdigit() or ch in "*xX" for ch in p):
            return False

        return True

    @staticmethod
    def _is_valid_applicant(applicant: dict) -> bool:
        """
        Фильтруем шум:
        - строки типа "Your appointment has / Turkey / Bulgaria Visa Application ..."
        - оставляем:
          - либо строки с нормальным паспортом
          - либо строки, где имя похоже на ФИО (2+ слова, без цифр)
        """
        name = (applicant.get("name") or "").strip()
        passport = (applicant.get("passport") or "").strip()

        # Если паспорт выглядит правдоподобным — уже ок.
        if VisaAppointmentParser._passport_looks_valid(passport):
            return True

        if not name or len(name) < 3:
            return False
        if any(ch.isdigit() for ch in name):
            return False
        if " " not in name:
            return False

        first_word = name.split()[0].lower()
        stop = {
            "your",
            "arrive",
            "bulgaria",
            "istanbul",
            "turkey",
            "customer/s",
            "please",
        }
        if first_word in stop:
            return False

        return True

    def _filter_invalid_applicants(self, applicants):
        return [a for a in applicants if self._is_valid_applicant(a)]

    def _cut_service_type_suffix(self, text: str) -> str:
        """
        Удаляет хвосты вида 'Service Type: Premium', 'Service Type - Normal' и т.п.
        Оставляет только основную категорию.
        """
        if not text:
            return text

        # Ищем "Service Type" и обрезаем всё после него
        cleaned = re.split(r"Service\s+Type[:\- ]", text, flags=re.IGNORECASE)[0]

        # Удаляем хвостовые пробелы
        return cleaned.strip()

    def _clean_visa_category_text(self, text: str) -> str | None:
        """
        Универсальная чистка текста категории:
        - нормализуем пробелы
        - убираем служебные префиксы (Customer/s, Reference Number...)
        - отрезаем хвост 'Service Type: ...'
        - сохраняем важные паттерны (Short stay, Application with, Seasonal worker, Short Term)
        - вырезаем reference-коды
        - убираем оторванную конечную цифру (Driver Standard 5 -> Driver Standard)
        """
        if not text:
            return None

        # нормализуем пробелы
        t = " ".join(str(text).split())
        if len(t) < 3:
            return None

        # сначала убираем явные дисклеймеры
        low = t.lower()
        if low.startswith("please be informed") or "corona virus" in low:
            return None

        # служебные префиксы, которые НИКОГДА не являются названием визы
        t = re.sub(r"^(Customer/?s|Customer)\s+", "", t, flags=re.IGNORECASE)
        t = re.sub(
            r"^(Reference Number Number|Reference Number)\s+",
            "",
            t,
            flags=re.IGNORECASE,
        )
        t = re.sub(r"^Number\s+", "", t, flags=re.IGNORECASE)

        t = t.strip()

        # 🔹 ВАЖНО: обрезаем хвост 'Service Type: ...'
        t = self._cut_service_type_suffix(t)
        if not t:
            return None

        low = t.lower()

        if len(t) < 3:
            return None

        # ===== важные встречающиеся паттерны, которые хотим сохранить как есть =====

        # "3 - Short stay others", "2 - Short stay tourism" и т.п.
        m = re.search(r"\d+\s*-\s*short\s+stay[^\n]*", t, re.IGNORECASE)
        if m:
            return m.group(0).strip()

        # "Application with Biometric ( Individual - Bireysel)" и подобные
        if "application with" in low:
            idx = low.index("application with")
            return t[idx:].strip()

        # "Seasonal worker"
        if "seasonal worker" in low:
            return "Seasonal worker"

        # "Short Term Standard", "Short Term"
        if "short term" in low:
            if "standard" in low:
                return "Short Term Standard"
            return "Short Term"

        # ===== общий sanitizing =====

        # убираем длинные reference-подобные токены
        tokens = []
        for tok in t.split():
            if re.fullmatch(r"[A-Z0-9/]{8,}", tok):
                continue
            tokens.append(tok)
        t = " ".join(tokens).strip()

        parts = t.split()
        if (
            len(parts) >= 2
            and re.fullmatch(r"\d+", parts[-1])
            and any(ch.isalpha() for ch in t)
        ):
            parts = parts[:-1]
            t = " ".join(parts).strip()

        if len(t) > 120:
            return None
        if len(t) < 3:
            return None

        return t or None

    def _refine_visa_category_with_applicants(self, applicants):
        if not applicants:
            return

        cleaned_cats = []

        # 1. Чистим категории у каждого заявителя
        for a in applicants:
            raw_cat = a.get("visa_category")
            cleaned = self._clean_visa_category_text(raw_cat)
            if cleaned is not None:
                a["visa_category"] = cleaned
                cleaned_cats.append(cleaned)
            else:
                a["visa_category"] = None

        # 2. Чистим глобальную, если она есть — но не даём ей приоритет
        if self.meta.get("visa_category_global"):
            current = self._clean_visa_category_text(self.meta["visa_category_global"])
            self.meta["visa_category_global"] = current

        # 3. Если есть хоть одна вменяемая категория у заявителей —
        #    берём самую короткую как "каноническую" глобальную
        if cleaned_cats:
            best = sorted(set(cleaned_cats), key=len)[0]
            self.meta["visa_category_global"] = best

        # 4. Подставляем глобальную туда, где у заявителя категория пустая
        if self.meta.get("visa_category_global"):
            for a in applicants:
                if not a.get("visa_category"):
                    a["visa_category"] = self.meta["visa_category_global"]

    # ---------------------- LINE / COLUMN UTILITIES ---------------------- #

    @staticmethod
    def _group_words_into_lines(words, line_tol=3.0):
        words_sorted = sorted(words, key=lambda w: (w["top"], w["x0"]))
        lines = []
        current_line = []
        current_top = None

        for w in words_sorted:
            top = w["top"]
            if not current_line:
                current_line = [w]
                current_top = top
                continue

            if abs(top - current_top) <= line_tol:
                current_line.append(w)
            else:
                lines.append(current_line)
                current_line = [w]
                current_top = top

        if current_line:
            lines.append(current_line)

        return lines

    @staticmethod
    def _build_header_cells(header_line, gap_threshold=20.0):
        header_line_sorted = sorted(header_line, key=lambda w: w["x0"])

        cells = []
        current_cell_words = []
        current_x0 = None
        current_x1 = None

        for w in header_line_sorted:
            if not current_cell_words:
                current_cell_words = [w]
                current_x0 = w["x0"]
                current_x1 = w["x1"]
                continue

            gap = w["x0"] - current_x1
            if gap <= gap_threshold:
                current_cell_words.append(w)
                current_x1 = w["x1"]
            else:
                text = " ".join(ww["text"] for ww in current_cell_words)
                center = (current_x0 + current_x1) / 2.0
                cells.append(
                    {
                        "text": text,
                        "x0": current_x0,
                        "x1": current_x1,
                        "x_center": center,
                    }
                )
                current_cell_words = [w]
                current_x0 = w["x0"]
                current_x1 = w["x1"]

        if current_cell_words:
            text = " ".join(ww["text"] for ww in current_cell_words)
            center = (current_x0 + current_x1) / 2.0
            cells.append(
                {
                    "text": text,
                    "x0": current_x0,
                    "x1": current_x1,
                    "x_center": center,
                }
            )

        return cells

    @staticmethod
    def _map_header_cells_to_logical_columns(header_cells):
        col_map = {}

        for cell in header_cells:
            text = cell["text"].strip()
            low = text.lower()
            x_center = cell["x_center"]

            if "applicant" in low:
                col_map["name"] = x_center
            if "passport" in low:
                col_map["passport"] = x_center
            if "special" in low and "reference" in low:
                col_map["special_reference"] = x_center
            elif "reference" in low:
                col_map["reference"] = x_center
            if "appointment" in low and ("time" in low or "date" in low):
                col_map["time"] = x_center
            if "category" in low:
                col_map["category"] = x_center

        return col_map

    @staticmethod
    def _assign_line_to_columns(line_words, column_map):
        if not column_map:
            return {}

        col_words = {name: [] for name in column_map.keys()}

        for w in sorted(line_words, key=lambda w: w["x0"]):
            word_center = (w["x0"] + w["x1"]) / 2.0

            best_col = None
            best_dist = None
            for col_name, col_center in column_map.items():
                dist = abs(word_center - col_center)
                if best_dist is None or dist < best_dist:
                    best_dist = dist
                    best_col = col_name

            if best_col is not None:
                col_words[best_col].append(w["text"])

        result = {}
        for col_name, words in col_words.items():
            result[col_name] = " ".join(words) if words else ""
        return result

    # ---------------------- BUILD APPLICANT OBJECT ---------------------- #

    def _build_applicant_object(self, row):
        name = (row.get("name") or "").strip() or None
        passport = (row.get("passport") or "").strip() or None
        time_raw = (row.get("time") or "").strip()
        category_row = (row.get("category") or "").strip() or None
        reference = (row.get("reference") or "").strip() or None
        special_ref = (row.get("special_reference") or "").strip() or None

        passport_masked = bool(
            passport and any(ch in passport for ch in ["*", "x", "X"])
        )

        appointment_date = self.meta.get("appointment_date")
        appointment_time = self.meta.get("appointment_time")
        datetime_raw = time_raw if time_raw else None

        if time_raw:
            # Вытаскиваем дату/время из грязной строки
            m_date = re.search(
                r"(\d{4}-\d{2}-\d{2}|\d{2}-\d{2}-\d{4}|\d{2}[./-]\d{2}[./-]\d{4})",
                time_raw,
            )
            if m_date:
                appointment_date = m_date.group(1)

            m_time = re.search(
                r"(\d{1,2}:\d{2}"
                r"(?:\s*(?:am|pm|AM|PM))?"
                r"(?:\s*-\s*\d{1,2}:\d{2}(?:\s*(?:am|pm|AM|PM))?)?)",
                time_raw,
            )
            if m_time:
                appointment_time = m_time.group(1)

        # нормализуем время (обрежем возможный интервал)
        if appointment_time:
            appointment_time = self._normalize_time(appointment_time)

        # Если и дата, и время известны – делаем красивый datetime_raw
        if appointment_date and appointment_time:
            datetime_raw = f"{appointment_date} {appointment_time}"

        visa_category = category_row or self.meta.get("visa_category_global")

        applicant = {
            "name": name,
            "passport": passport,
            "passport_masked": passport_masked,
            "appointment_date": appointment_date,
            "appointment_time": appointment_time,
            "datetime_raw": datetime_raw,
            "visa_category": visa_category,
            "reference_number": reference,
            "special_reference_number": special_ref,
        }
        return applicant


if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Использование: python parse_visa_pdf.py <path_to_pdf>")
        sys.exit(1)

    pdf_path = Path(sys.argv[1])

    if not pdf_path.exists() or pdf_path.suffix.lower() != ".pdf":
        print(f"[ОШИБКА] PDF-файл не найден или указан неверно: {pdf_path}")
        sys.exit(1)

    parser = VisaAppointmentParser(pdf_path)
    result = parser.parse()

    print(json.dumps(result, ensure_ascii=False, indent=2))