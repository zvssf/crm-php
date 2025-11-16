#!/usr/bin/env python3
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
        self.meta = {
            "provider": None,
            "group_urn": None,
            "appointment_date": None,
            "appointment_time": None,
            "visa_category_global": None,
        }

    # ---------------------- PUBLIC API ---------------------- #

    def parse(self) -> dict:
        self.full_text = self._extract_full_text()
        self._parse_global_metadata()

        applicants = self._parse_applicants_from_pdf()
        applicants = self._filter_invalid_applicants(applicants)
        self._refine_visa_category_with_applicants(applicants)

        result = {
            "source_file": self.pdf_path.name,
            "provider": self.meta["provider"],
            "group_urn": self.meta["group_urn"],
            "appointment_date": self.meta["appointment_date"],
            "appointment_time": self.meta["appointment_time"],
            "visa_category_global": self.meta["visa_category_global"],
            "raw_text_locale": "en",
            "applicants": applicants,
        }
        return result

    # ---------------------- BASIC TEXT ---------------------- #

    def _extract_full_text(self) -> str:
        parts = []
        with pdfplumber.open(self.pdf_path) as pdf:
            for page in pdf.pages:
                text = page.extract_text() or ""
                parts.append(text)
        return "\n".join(parts)

    # ---------------------- GLOBAL META ---------------------- #

    def _parse_global_metadata(self):
        """
        Построчный разбор шапки: provider, group_urn, date/time, visa category.
        """
        text = self.full_text
        lines_raw = text.splitlines()
        lines = [ln.strip() for ln in lines_raw]

        # --- Provider ---
        if "BLS Spain Application Centre" in text:
            self.meta["provider"] = "BLS Spain"
        elif "VFS Global" in text or "VFS G\nLOBAL" in text or "VFS G LOBAL" in text:
            self.meta["provider"] = "VFS Global"
        elif "iDATA" in text:
            self.meta["provider"] = "iDATA"
        else:
            self.meta["provider"] = None

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
        BLS Spain:
        ...
        Group URN:
        No. Of Applicants: 2 Visa Type: Tourist Visa ...
        IST239202474725
        Appointment Details
        Passport Appointment
        Applicant Name Reference Number Value Added Services
        Number Date & Time
        2025-11-12
        OZL***** AYD***** *****303
        11:30-11:45
        IST239202474725/1
        2025-11-12
        MEH***** AYD***** *****910
        11:30-11:45
        IST239202474725/2
        ...
        """
        applicants = []
        with pdfplumber.open(self.pdf_path) as pdf:
            first_page = pdf.pages[0]
            lines = (first_page.extract_text() or "").splitlines()

        # ищем "Appointment Details"
        idx = None
        for i, ln in enumerate(lines):
            if ln.strip().lower() == "appointment details":
                idx = i
                break
        if idx is None:
            return []

        date_re = re.compile(r"\d{4}-\d{2}-\d{2}")
        time_re = re.compile(r"\d{1,2}:\d{2}(?:-\d{1,2}:\d{2})?")
        ref_re = re.compile(r"[A-Z]{3}\d{9,}(?:/\d+)?")

        # пролистываем заголовки до первой строки-даты
        i = idx + 1
        while i < len(lines) and not date_re.fullmatch(lines[i].strip()):
            i += 1

        while i + 3 < len(lines):
            date_line = lines[i].strip()
            if not date_re.fullmatch(date_line):
                break

            name_pass = lines[i + 1].strip()
            time_line = lines[i + 2].strip()
            ref_line = lines[i + 3].strip()

            if not time_re.fullmatch(time_line) or not ref_re.fullmatch(ref_line):
                break

            tokens = name_pass.split()
            if not tokens:
                break
            passport = tokens[-1]
            name = " ".join(tokens[:-1]) or None

            passport_masked = any(ch in passport for ch in "*xX")

            applicant = {
                "name": name,
                "passport": passport,
                "passport_masked": passport_masked,
                "appointment_date": date_line,
                "appointment_time": time_line,
                "datetime_raw": f"{date_line} {time_line}",
                "visa_category": self.meta.get("visa_category_global"),
                "reference_number": ref_line,
                "special_reference_number": None,
            }
            applicants.append(applicant)

            i += 4

        # если глобальная дата/время пустые — возьмём из первого заявителя
        if applicants:
            if not self.meta.get("appointment_date"):
                self.meta["appointment_date"] = applicants[0]["appointment_date"]
            if not self.meta.get("appointment_time"):
                self.meta["appointment_time"] = applicants[0]["appointment_time"]

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
        p = (passport or "").strip()
        if len(p) < 5:
            return False
        return any(ch.isdigit() or ch in "*xX" for ch in p)

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

    @staticmethod
    def _clean_visa_category_text(text: str) -> str | None:
        if not text:
            return None

        t = text.strip()
        low = t.lower()

        # Специальные кейсы, которые нас реально интересуют
        m = re.search(r"\d+\s*-\s*short\s+stay[^\n]*", t, re.I)
        if m:
            return m.group(0).strip()

        if "application with" in low:
            idx = low.index("application with")
            return t[idx:].strip()

        if "seasonal worker" in low:
            return "Seasonal worker"

        if "short term" in low:
            if "standard" in low:
                return "Short Term Standard"
            return "Short Term"

        # дальше — общий “санитайзер”
        tokens = t.split()
        cleaned_tokens = []
        for tok in tokens:
            if re.fullmatch(r"[A-Z0-9/]{8,}", tok):
                continue
            cleaned_tokens.append(tok)
        t = " ".join(cleaned_tokens).strip()
        low = t.lower()

        if low.startswith("please be informed") or "corona virus" in low:
            return None

        if len(t) > 120:
            return None

        return t or None

    def _refine_visa_category_with_applicants(self, applicants):
        if not applicants:
            return

        cleaned_cats = []

        for a in applicants:
            raw_cat = a.get("visa_category")
            cleaned = self._clean_visa_category_text(raw_cat)
            if cleaned is not None:
                a["visa_category"] = cleaned
                cleaned_cats.append(cleaned)
            else:
                a["visa_category"] = None

        # чистим глобальную категорию (если есть)
        if self.meta.get("visa_category_global"):
            current = self._clean_visa_category_text(self.meta["visa_category_global"])
            if current:
                self.meta["visa_category_global"] = current
            else:
                self.meta["visa_category_global"] = None

        # если после чистки глобальная всё ещё есть — дополняем ею апликантов
        if self.meta.get("visa_category_global"):
            for a in applicants:
                if not a.get("visa_category"):
                    a["visa_category"] = self.meta["visa_category_global"]
            return

        # иначе строим глобальную из категорий заявителей
        if cleaned_cats:
            cleaned_cats_sorted = sorted(set(cleaned_cats), key=len)
            best = cleaned_cats_sorted[0]
            self.meta["visa_category_global"] = best
            for a in applicants:
                if not a.get("visa_category"):
                    a["visa_category"] = best

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


def main():
    if len(sys.argv) < 2:
        print("Usage: parse_visa_pdf.py <file.pdf>", file=sys.stderr)
        sys.exit(1)

    pdf_path = sys.argv[1]

    try:
        parser = VisaAppointmentParser(pdf_path)
        result = parser.parse()
        print(json.dumps(result, ensure_ascii=False, indent=2))
    except Exception as e:
        print(f"Error while parsing {pdf_path}: {e}", file=sys.stderr)
        error_result = {
            "source_file": Path(pdf_path).name,
            "error": str(e),
            "applicants": [],
        }
        print(json.dumps(error_result, ensure_ascii=False, indent=2))
        sys.exit(1)


if __name__ == "__main__":
    main()
