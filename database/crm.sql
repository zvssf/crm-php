-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Окт 08 2025 г., 19:04
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

--
-- Дамп данных таблицы `city_suppliers`
--

INSERT INTO `city_suppliers` (`id`, `city_id`, `supplier_id`) VALUES
(8, 1, 2);

-- --------------------------------------------------------

--
-- Структура таблицы `clients`
--

CREATE TABLE `clients` (
  `client_id` int NOT NULL,
  `center_id` int NOT NULL,
  `agent_id` int DEFAULT NULL,
  `client_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `first_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `last_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `middle_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `gender` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `phone` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `passport_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `passport_expiry_date` date DEFAULT NULL,
  `nationality` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `visit_purpose` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `visit_date_start` date DEFAULT NULL,
  `visit_date_end` date DEFAULT NULL,
  `days_until_visit` int DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `sale_price` decimal(15,2) DEFAULT NULL,
  `client_status` int NOT NULL DEFAULT '1',
  `rejection_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci
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
  `input_id` int NOT NULL,
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

--
-- Дамп данных таблицы `fin_cashes`
--

INSERT INTO `fin_cashes` (`id`, `name`, `balance`, `status`) VALUES
(1, 'Касса 1', '2522530.70', 1),
(2, 'Касса 2', '21836.00', 2),
(3, 'Касса 3', '14605.00', 1),
(4, 'Касса 4', '5004.00', 1),
(5, 'Касса 5', '10.00', 0);

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

--
-- Дамп данных таблицы `fin_suppliers`
--

INSERT INTO `fin_suppliers` (`id`, `name`, `balance`, `status`) VALUES
(1, 'Занебесное', '1002033.00', 1),
(2, 'Даб', '-2109.90', 1),
(3, 'Название 3', '0.00', 2),
(4, 'Название 4', '0.00', 1);

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
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Дамп данных таблицы `fin_transactions`
--

INSERT INTO `fin_transactions` (`id`, `transaction_date`, `operation_type`, `amount`, `cash_id`, `agent_id`, `supplier_id`, `comment`) VALUES
(1, '2025-08-22 13:01:40', 1, '5450.00', 3, 19, NULL, 'папапирипирурап'),
(2, '2025-08-22 13:01:40', 1, '4044.00', 2, 19, NULL, NULL),
(3, '2025-08-22 13:01:50', 2, '-5450.00', 5, NULL, NULL, NULL),
(4, '2025-08-22 13:01:50', 1, '444.00', 2, 21, NULL, NULL),
(7, '2025-09-08 22:46:26', 1, '5000.00', 1, 19, NULL, NULL),
(8, '2025-09-09 02:38:52', 1, '1000.00', 1, 19, NULL, NULL),
(9, '2025-09-09 09:13:16', 1, '2000.10', 1, 19, NULL, NULL),
(10, '2025-09-09 09:16:16', 1, '1000.00', 1, 21, NULL, NULL),
(11, '2025-09-09 12:37:13', 2, '-1000.00', 1, NULL, 1, NULL),
(12, '2025-09-09 18:30:29', 2, '-5000.00', 3, NULL, 1, NULL),
(13, '2025-09-09 19:29:23', 1, '1000.00', 1, 19, NULL, NULL),
(14, '2025-09-09 19:29:34', 2, '-2000.00', 1, NULL, 1, NULL),
(15, '2025-09-09 19:59:32', 0, '-1000.00', 1, NULL, NULL, NULL),
(16, '2025-09-09 20:00:01', 1, '2000.00', 4, 19, NULL, NULL),
(17, '2025-09-09 20:00:15', 1, '2000.00', 4, 19, NULL, NULL),
(18, '2025-09-09 20:36:25', 0, '-1000.00', 1, NULL, NULL, NULL),
(19, '2025-09-09 20:36:47', 2, '-1000.00', 1, NULL, NULL, NULL),
(20, '2025-09-09 20:37:00', 2, '-1000.00', 1, NULL, 2, NULL),
(21, '2025-09-09 20:37:52', 0, '-1000.00', 1, NULL, 2, NULL),
(22, '2025-09-09 20:55:51', 2, '-1000.00', 1, NULL, 1, NULL),
(23, '2025-09-09 20:56:22', 2, '-1000.00', 1, NULL, 1, NULL),
(24, '2025-09-09 20:56:43', 2, '-1000.00', 3, NULL, 1, 'папапирипирурап'),
(25, '2025-09-10 01:47:24', 1, '1.00', 1, 19, NULL, 'test'),
(26, '2025-09-10 01:48:33', 1, '1.00', 1, 19, NULL, NULL),
(27, '2025-09-10 01:52:34', 1, '1.00', 1, 19, NULL, ''),
(28, '2025-09-10 01:54:54', 1, '1000.00', 1, 19, NULL, '123к'),
(29, '2025-09-10 13:26:52', 2, '-1000.00', 1, NULL, NULL, ''),
(30, '2025-09-10 13:27:33', 2, '-1000.00', 1, NULL, 1, ''),
(31, '2025-09-10 13:48:49', 1, '1.00', 1, 19, NULL, ''),
(32, '2025-09-10 13:49:24', 2, '-2.00', 1, NULL, NULL, ''),
(33, '2025-09-10 13:49:41', 1, '2.00', 1, 19, NULL, ''),
(34, '2025-09-10 20:43:02', 2, '-3000.00', 1, NULL, 2, '123\r\n\r\n876'),
(35, '2025-09-10 20:56:25', 2, '0.00', 3, NULL, 2, 'sadasdasdsadasdasdasdasdasdasddddddaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa\r\naaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaasdasfdsfgfgsdfgsdfgsdfgdghsadasdasdsadasdasdasdasdasdasddddddaaaaaaaaaaa'),
(36, '2025-09-10 21:29:33', 1, '2.00', 4, 19, NULL, '123321'),
(37, '2025-09-10 21:32:29', 2, '0.00', 4, NULL, NULL, '12'),
(38, '2025-09-10 21:34:36', 2, '0.00', 4, NULL, 2, 'пваы'),
(39, '2025-09-10 21:35:08', 2, '0.00', 4, NULL, 2, '456\r\n\r\n798'),
(40, '2025-09-11 16:32:50', 1, '1000.00', 1, 19, NULL, '890'),
(41, '2025-09-11 16:42:25', 1, '1000.00', 1, 21, NULL, '432'),
(42, '2025-09-11 16:50:05', 2, '-890.10', 1, NULL, 2, '09'),
(43, '2025-09-11 16:51:04', 1, '1000.00', 1, 19, NULL, ''),
(44, '2025-09-11 16:51:26', 2, '-2000.20', 1, NULL, NULL, ''),
(45, '2025-09-11 16:51:40', 1, '10000.00', 1, 19, NULL, ''),
(46, '2025-10-04 19:47:02', 1, '100000.00', 1, 33, NULL, ''),
(47, '2025-10-04 19:47:32', 1, '100000.00', 1, 33, NULL, ''),
(48, '2025-10-04 19:49:42', 2, '-1000000.00', 1, NULL, 1, ''),
(49, '2025-10-04 19:50:16', 1, '1000000.00', 1, 19, NULL, ''),
(50, '2025-10-04 19:52:25', 1, '1000000.00', 1, 19, NULL, ''),
(51, '2025-10-04 19:52:56', 1, '1000000.00', 1, 19, NULL, ''),
(52, '2025-10-04 19:54:09', 1, '255.00', 3, 33, NULL, ''),
(53, '2025-10-04 19:54:40', 1, '2.00', 4, 33, NULL, ''),
(54, '2025-10-04 19:55:35', 2, '-33.00', 1, NULL, 1, ''),
(55, '2025-10-04 19:56:17', 1, '300000.00', 1, 33, NULL, ''),
(56, '2025-10-04 20:37:32', 1, '15000.00', 1, 33, NULL, '');

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
-- Структура таблицы `settings_centers`
--

CREATE TABLE `settings_centers` (
  `center_id` int NOT NULL,
  `center_name` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `country_id` int NOT NULL,
  `center_status` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Дамп данных таблицы `settings_centers`
--

INSERT INTO `settings_centers` (`center_id`, `center_name`, `country_id`, `center_status`) VALUES
(1, 'ВЦ Франции', 1, 1),
(2, 'ВЦ Польши', 1, 1),
(3, 'ВЦ Германии', 2, 1),
(4, 'ВЦ Италии', 2, 1),
(5, 'ВЦ Франции', 2, 1),
(6, 'ВЦ России', 17, 1);

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

--
-- Дамп данных таблицы `settings_cities`
--

INSERT INTO `settings_cities` (`city_id`, `city_name`, `city_category`, `country_id`, `city_status`, `cost_price`, `min_sale_price`) VALUES
(1, 'Москва', 'Туристическая', 1, 1, '1000.00', '1500.00'),
(2, 'Стамбул', 'Бизнес', 2, 1, '1200.00', '1800.00'),
(3, 'Москва', 'Бизнес', 1, 1, '1000.00', '200.00');

-- --------------------------------------------------------

--
-- Структура таблицы `settings_city_inputs`
--

CREATE TABLE `settings_city_inputs` (
  `id` int NOT NULL,
  `city_id` int NOT NULL,
  `input_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Дамп данных таблицы `settings_city_inputs`
--

INSERT INTO `settings_city_inputs` (`id`, `city_id`, `input_id`) VALUES
(46, 1, 11),
(47, 1, 12),
(48, 1, 13),
(2, 11, 2),
(6, 12, 1),
(7, 13, 1),
(8, 13, 2),
(9, 13, 4),
(11, 14, 4);

-- --------------------------------------------------------

--
-- Структура таблицы `settings_countries`
--

CREATE TABLE `settings_countries` (
  `country_id` int NOT NULL,
  `country_name` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `country_status` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Дамп данных таблицы `settings_countries`
--

INSERT INTO `settings_countries` (`country_id`, `country_name`, `country_status`) VALUES
(1, 'Россия', 1),
(2, 'Турция', 1),
(17, 'Египет', 1);

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

--
-- Дамп данных таблицы `settings_inputs`
--

INSERT INTO `settings_inputs` (`input_id`, `input_name`, `input_type`, `input_status`, `input_select_data`) VALUES
(1, 'Название поля 1', 3, 1, ''),
(2, 'Название поля 2', 2, 1, 'Пункт 1|Пункт 2|Пункт 3'),
(4, 'Название поля 3', 1, 1, ''),
(11, 'Тектовое проверочное', 1, 1, ''),
(12, 'Список проверочный', 2, 1, '1|2|3'),
(13, 'Выбор проверочный', 3, 1, 'Да|Нет');

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
  `user_tel` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `user_balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `user_supervisor` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`user_id`, `user_login`, `user_password`, `user_group`, `user_status`, `user_session_key`, `user_firstname`, `user_lastname`, `user_tel`, `user_balance`, `user_supervisor`) VALUES
(1, 'a@a.a', 'e10adc3949ba59abbe56e057f20f883e', 1, 1, '/4ibMidSzQ6tX4IkJnQXU2mj+SVWE8FgnhgaP2H8t8cTLhI47gqdBv/9Q2luyAPLx/K8KvlsDk6r8dfe2geLAu1cpcd+LS9jT3IjLoJRPmoFetQueTGFJPBpP+UpmMv7', 'Сергей', 'Фамилия', '+79009009090', '0.00', 0),
(2, 'a@a.aa', '74b87337454200d4d33f80c4663dc5e5', 1, 2, '', 'Сергей', 'Фамилия', '+79009009091', '0.00', 0),
(3, 'd@d.d', '74b87337454200d4d33f80c4663dc5e5', 2, 2, '', 'Имя', 'Фамилия', '+79009009092', '0.00', 0),
(12, 's@s.ss', '74b87337454200d4d33f80c4663dc5e5', 3, 2, '', 'Олег', 'Фамилия', '+79009009093', '0.00', 3),
(19, 'asd@asd.sadwwww', '74b87337454200d4d33f80c4663dc5e5', 4, 1, '', 'Ирина', 'Фамилия', '+79009009094', '1017253.50', 32),
(21, 's@s.ssw', '74b87337454200d4d33f80c4663dc5e5', 4, 2, '', 'Роман', 'Фамилия', '+79009009098', '-11111.48', 12),
(22, 's@s.sss', '74b87337454200d4d33f80c4663dc5e5', 3, 0, '', 'Вадим', 'Фамилия', '+79009009097', '0.00', 3),
(27, 'a@a.aasdd', '74b87337454200d4d33f80c4663dc5e5', 4, 2, '', 'Сергей', 'Фамилия', '+234324234234234', '0.00', 12),
(28, 'a@a.aeee', '74b87337454200d4d33f80c4663dc5e5', 1, 2, '', 'Анатолий', 'Фамилия', '+79009009096', '0.00', 0),
(29, 'asd@asd.sf', '202cb962ac59075b964b07152d234b70', 2, 2, '', 'пвпа', 'вапва', '+899444444444', '0.00', 0),
(30, 'rukA@a.a', '202cb962ac59075b964b07152d234b70', 2, 1, '', 'Руководитель', 'тип-А', '+213123123', '0.00', 0),
(31, 'rukB@a.a', '202cb962ac59075b964b07152d234b70', 2, 1, '', 'Руководитель', 'тип-Б', '+12394352', '0.00', 0),
(32, 'menA@a.a', '202cb962ac59075b964b07152d234b70', 3, 1, 'ERToUtCWfSxIyU7dmHnUjStqfFNzQFm8JdlMfuS4G50SczVYLBL4plkX/2DKQ9zjU4sUZWyk1VHq1kNrMfK+Wkm3CKsOng6yMhRIQmnZFRwH46o/2ZOyHWQGOoS2E4Cv', 'Менежер', 'тип-А-1', '+123123453', '0.00', 30),
(33, 'agA@a.a', '202cb962ac59075b964b07152d234b70', 4, 1, 'RBenJQHDMtoDQ5EakyzAqjrZkRkZPpYQtBd3b2JAKNv5px8U4vIeXwQHZAymrspCfQD19bBnEhkeHiCzFyol0Y8qRi347DQYmDRqPFdbV0SlN9EsoY6vbAk2b+oH5Ndw', 'Агент', 'тип-А-1', '+3234546657', '109757.00', 32);

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
-- Индексы таблицы `settings_centers`
--
ALTER TABLE `settings_centers`
  ADD PRIMARY KEY (`center_id`);

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
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `city_suppliers`
--
ALTER TABLE `city_suppliers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT для таблицы `clients`
--
ALTER TABLE `clients`
  MODIFY `client_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `client_cities`
--
ALTER TABLE `client_cities`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `client_input_values`
--
ALTER TABLE `client_input_values`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT для таблицы `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT для таблицы `settings_centers`
--
ALTER TABLE `settings_centers`
  MODIFY `center_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `settings_cities`
--
ALTER TABLE `settings_cities`
  MODIFY `city_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `settings_city_inputs`
--
ALTER TABLE `settings_city_inputs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT для таблицы `settings_countries`
--
ALTER TABLE `settings_countries`
  MODIFY `country_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT для таблицы `settings_inputs`
--
ALTER TABLE `settings_inputs`
  MODIFY `input_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

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
