-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Дек 23 2025 г., 14:29
-- Версия сервера: 8.0.30
-- Версия PHP: 8.1.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `crm`
--

-- --------------------------------------------------------

--
-- Структура таблицы `city_suppliers`
--

CREATE TABLE `city_suppliers` (
  `id` int NOT NULL,
  `city_id` int NOT NULL,
  `supplier_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_unicode_520_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `clients`
--

CREATE TABLE `clients` (
  `client_id` int NOT NULL,
  `family_id` int DEFAULT NULL,
  `center_id` int NOT NULL,
  `agent_id` int DEFAULT NULL,
  `creator_id` int DEFAULT NULL,
  `client_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `first_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `last_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `middle_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `gender` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `phone_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `phone_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `passport_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `passport_expiry_date` date DEFAULT NULL,
  `nationality` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `monitoring_date_start` date DEFAULT NULL,
  `monitoring_date_end` date DEFAULT NULL,
  `visit_date` date DEFAULT NULL,
  `visit_purpose` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `visit_date_start` date DEFAULT NULL,
  `visit_date_end` date DEFAULT NULL,
  `days_until_visit` int DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `sale_price` decimal(15,2) DEFAULT NULL,
  `appointment_datetime` datetime DEFAULT NULL,
  `paid_from_balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `paid_from_credit` decimal(15,2) NOT NULL DEFAULT '0.00',
  `payment_status` tinyint(1) NOT NULL DEFAULT '0',
  `client_status` int NOT NULL DEFAULT '1',
  `rejection_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `recording_uid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `pdf_file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `client_cities`
--

CREATE TABLE `client_cities` (
  `id` int NOT NULL,
  `client_id` int NOT NULL,
  `city_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `client_input_values`
--

CREATE TABLE `client_input_values` (
  `id` int NOT NULL,
  `client_id` int NOT NULL,
  `relative_id` int DEFAULT NULL,
  `input_id` int NOT NULL,
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `client_relatives`
--

CREATE TABLE `client_relatives` (
  `relative_id` int NOT NULL,
  `family_id` int NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `phone_code` varchar(10) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `passport_number` varchar(50) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `passport_expiry_date` date DEFAULT NULL,
  `nationality` varchar(100) DEFAULT NULL,
  `notes` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Структура таблицы `families`
--

CREATE TABLE `families` (
  `family_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Структура таблицы `fin_cashes`
--

CREATE TABLE `fin_cashes` (
  `id` int NOT NULL,
  `name` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `fin_suppliers`
--

CREATE TABLE `fin_suppliers` (
  `id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `fin_transactions`
--

CREATE TABLE `fin_transactions` (
  `id` int NOT NULL,
  `transaction_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `operation_type` int NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `cash_id` int NOT NULL,
  `agent_id` int DEFAULT NULL,
  `supplier_id` int DEFAULT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `affected_clients_log` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin COMMENT 'Лог анкет, затронутых транзакцией'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int NOT NULL,
  `user_login` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `attempts` int DEFAULT '1',
  `last_attempt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `pdf_parsing_rules`
--

CREATE TABLE `pdf_parsing_rules` (
  `rule_id` int NOT NULL,
  `center_id` int NOT NULL,
  `center_identifier_text` varchar(255) NOT NULL,
  `passport_mask` varchar(255) DEFAULT NULL,
  `rule_status` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Структура таблицы `settings_centers`
--

CREATE TABLE `settings_centers` (
  `center_id` int NOT NULL,
  `center_name` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `country_id` int NOT NULL,
  `center_status` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `settings_center_fields`
--

CREATE TABLE `settings_center_fields` (
  `id` int NOT NULL,
  `center_id` int NOT NULL,
  `field_name` varchar(50) NOT NULL,
  `is_visible` tinyint(1) NOT NULL DEFAULT '1',
  `is_required` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Структура таблицы `settings_cities`
--

CREATE TABLE `settings_cities` (
  `city_id` int NOT NULL,
  `city_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `city_category` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `country_id` int NOT NULL,
  `city_status` tinyint(1) DEFAULT '1',
  `cost_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `min_sale_price` decimal(10,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `settings_city_inputs`
--

CREATE TABLE `settings_city_inputs` (
  `id` int NOT NULL,
  `city_id` int NOT NULL,
  `input_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `settings_countries`
--

CREATE TABLE `settings_countries` (
  `country_id` int NOT NULL,
  `country_name` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `country_status` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `settings_inputs`
--

CREATE TABLE `settings_inputs` (
  `input_id` int NOT NULL,
  `input_name` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `input_type` int NOT NULL,
  `input_status` int NOT NULL DEFAULT '1',
  `input_select_data` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `user_id` int NOT NULL,
  `user_login` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `user_password` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `user_group` int NOT NULL DEFAULT '0',
  `user_status` int NOT NULL DEFAULT '2',
  `user_session_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `user_firstname` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `user_lastname` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `user_tel` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `user_tel_2` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `user_balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `user_credit_limit` decimal(15,2) NOT NULL DEFAULT '0.00',
  `user_supervisor` int NOT NULL DEFAULT '0',
  `can_export` tinyint(1) NOT NULL DEFAULT '0',
  `user_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `user_website` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `user_messengers` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `user_comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `user_countries`
--

CREATE TABLE `user_countries` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `country_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Структура таблицы `user_export_settings`
--

CREATE TABLE `user_export_settings` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `center_id` int NOT NULL,
  `settings` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `city_suppliers`
--
ALTER TABLE `city_suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`client_id`);

--
-- Индексы таблицы `client_cities`
--
ALTER TABLE `client_cities`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `client_input_values`
--
ALTER TABLE `client_input_values`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `client_input_unique` (`client_id`,`input_id`),
  ADD KEY `client_id_val_fk_idx` (`client_id`),
  ADD KEY `input_id_val_fk_idx` (`input_id`);

--
-- Индексы таблицы `client_relatives`
--
ALTER TABLE `client_relatives`
  ADD PRIMARY KEY (`relative_id`),
  ADD KEY `family_id` (`family_id`);

--
-- Индексы таблицы `families`
--
ALTER TABLE `families`
  ADD PRIMARY KEY (`family_id`);

--
-- Индексы таблицы `fin_cashes`
--
ALTER TABLE `fin_cashes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_name` (`name`);

--
-- Индексы таблицы `fin_suppliers`
--
ALTER TABLE `fin_suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `fin_transactions`
--
ALTER TABLE `fin_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cash_id` (`cash_id`),
  ADD KEY `idx_agent_id` (`agent_id`);

--
-- Индексы таблицы `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_login` (`user_login`),
  ADD KEY `ip_address` (`ip_address`);

--
-- Индексы таблицы `pdf_parsing_rules`
--
ALTER TABLE `pdf_parsing_rules`
  ADD PRIMARY KEY (`rule_id`),
  ADD UNIQUE KEY `center_id` (`center_id`);

--
-- Индексы таблицы `settings_centers`
--
ALTER TABLE `settings_centers`
  ADD PRIMARY KEY (`center_id`);

--
-- Индексы таблицы `settings_center_fields`
--
ALTER TABLE `settings_center_fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `center_id` (`center_id`);

--
-- Индексы таблицы `settings_cities`
--
ALTER TABLE `settings_cities`
  ADD PRIMARY KEY (`city_id`),
  ADD KEY `city_status` (`city_status`),
  ADD KEY `country_id` (`country_id`) USING BTREE;

--
-- Индексы таблицы `settings_city_inputs`
--
ALTER TABLE `settings_city_inputs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `city_input_unique` (`city_id`,`input_id`);

--
-- Индексы таблицы `settings_countries`
--
ALTER TABLE `settings_countries`
  ADD PRIMARY KEY (`country_id`);

--
-- Индексы таблицы `settings_inputs`
--
ALTER TABLE `settings_inputs`
  ADD PRIMARY KEY (`input_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `idx_user_firstname` (`user_firstname`),
  ADD KEY `idx_user_lastname` (`user_lastname`);

--
-- Индексы таблицы `user_countries`
--
ALTER TABLE `user_countries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `country_id` (`country_id`);

--
-- Индексы таблицы `user_export_settings`
--
ALTER TABLE `user_export_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_center_unique` (`user_id`,`center_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `city_suppliers`
--
ALTER TABLE `city_suppliers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT для таблицы `clients`
--
ALTER TABLE `clients`
  MODIFY `client_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT для таблицы `client_cities`
--
ALTER TABLE `client_cities`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=447;

--
-- AUTO_INCREMENT для таблицы `client_input_values`
--
ALTER TABLE `client_input_values`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT для таблицы `client_relatives`
--
ALTER TABLE `client_relatives`
  MODIFY `relative_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT для таблицы `families`
--
ALTER TABLE `families`
  MODIFY `family_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT для таблицы `fin_cashes`
--
ALTER TABLE `fin_cashes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT для таблицы `fin_suppliers`
--
ALTER TABLE `fin_suppliers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `fin_transactions`
--
ALTER TABLE `fin_transactions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=127;

--
-- AUTO_INCREMENT для таблицы `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT для таблицы `pdf_parsing_rules`
--
ALTER TABLE `pdf_parsing_rules`
  MODIFY `rule_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT для таблицы `settings_centers`
--
ALTER TABLE `settings_centers`
  MODIFY `center_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT для таблицы `settings_center_fields`
--
ALTER TABLE `settings_center_fields`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=163;

--
-- AUTO_INCREMENT для таблицы `settings_cities`
--
ALTER TABLE `settings_cities`
  MODIFY `city_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `settings_city_inputs`
--
ALTER TABLE `settings_city_inputs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT для таблицы `settings_countries`
--
ALTER TABLE `settings_countries`
  MODIFY `country_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT для таблицы `settings_inputs`
--
ALTER TABLE `settings_inputs`
  MODIFY `input_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT для таблицы `user_countries`
--
ALTER TABLE `user_countries`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `user_export_settings`
--
ALTER TABLE `user_export_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `client_input_values`
--
ALTER TABLE `client_input_values`
  ADD CONSTRAINT `client_id_val_fk` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `input_id_val_fk` FOREIGN KEY (`input_id`) REFERENCES `settings_inputs` (`input_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `fin_transactions`
--
ALTER TABLE `fin_transactions`
  ADD CONSTRAINT `fin_transactions_ibfk_1` FOREIGN KEY (`cash_id`) REFERENCES `fin_cashes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `idx_agent_id` FOREIGN KEY (`agent_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
