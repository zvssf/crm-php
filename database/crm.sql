-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Ноя 03 2025 г., 02:14
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
  `visit_purpose` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `visit_date_start` date DEFAULT NULL,
  `visit_date_end` date DEFAULT NULL,
  `days_until_visit` int DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `sale_price` decimal(15,2) DEFAULT NULL,
  `paid_from_balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `paid_from_credit` decimal(15,2) NOT NULL DEFAULT '0.00',
  `payment_status` tinyint(1) NOT NULL DEFAULT '0',
  `client_status` int NOT NULL DEFAULT '1',
  `rejection_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `recording_uid` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Дамп данных таблицы `clients`
--

INSERT INTO `clients` (`client_id`, `center_id`, `agent_id`, `creator_id`, `client_name`, `first_name`, `last_name`, `middle_name`, `gender`, `phone_code`, `phone_number`, `email`, `passport_number`, `birth_date`, `passport_expiry_date`, `nationality`, `visit_purpose`, `visit_date_start`, `visit_date_end`, `days_until_visit`, `notes`, `sale_price`, `paid_from_balance`, `paid_from_credit`, `payment_status`, `client_status`, `rejection_reason`, `recording_uid`) VALUES
(17, 1, 19, 1, 'создал менеджер', 'менеджер', 'создал', NULL, 'male', '4', '352345', 'aa@a.r', '123', '4538-06-03', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '345.00', '0.00', '0.00', 0, 1, NULL, NULL),
(18, 1, 33, 1, 'Функциональный за Агента Тест', 'Тест', 'Функциональный за Агента', NULL, 'male', '1', '23312', 'a@a.a', '123', NULL, NULL, NULL, NULL, NULL, NULL, 100, NULL, '4324.00', '0.00', '0.00', 0, 1, NULL, NULL),
(19, 1, 33, 1, 'анкетовна анкета', 'анкета', 'анкетовна', NULL, 'male', '756', '756354', 'a@adsds.d', '657', '2008-03-04', '2025-10-16', 'AMERICAN', NULL, '2025-11-20', '2025-11-30', 38, NULL, '2000.00', '0.00', '0.00', 1, 4, NULL, NULL),
(20, 1, 33, 1, 'тест тест', 'тест', 'тест', NULL, 'male', '4', '53654456', 'a@a.a', '12657897800', '2025-10-22', '2025-10-31', 'ALGERIAN', NULL, '2025-10-09', '2025-11-21', 0, NULL, '1000.00', '0.00', '0.00', 1, 4, NULL, NULL),
(21, 1, 33, 1, '1 1', '1', '1', NULL, 'male', '6', '54243234', 'a@a.a', '234564243132', '2025-10-24', '2025-10-25', 'ALBANIAN', NULL, '2025-10-08', '2025-11-20', 2, NULL, '1000.00', '450.00', '550.00', 2, 2, NULL, '6907b2e2d3c89'),
(22, 1, 33, 1, '2 2', '2', '2', NULL, 'male', '3', '45745867', 'a@a.a', '5687945326', '2025-10-08', '2025-10-25', 'ALBANIAN', NULL, '2025-10-15', '2025-11-21', 100, 'авпвапвапвапвапп', '50.00', '50.00', '0.00', 1, 2, NULL, '6907b2e4bec30'),
(23, 1, 33, 1, '3 3', '3', '3', NULL, 'male', '7', '864558769', 'agA@a.a', '678955243', '2025-10-08', '2025-10-16', 'ALGERIAN', NULL, '2025-10-22', '2025-11-28', 8, NULL, '400.00', '1000.00', '0.00', 1, 1, NULL, NULL),
(24, 1, 19, 1, '5 5', '5', '5', NULL, 'male', '4', '7564576', 'agA@a.a', '679802346523145', '2025-10-16', '2025-10-19', 'AMERICAN', NULL, '2025-10-17', '2025-11-28', 3, NULL, '1.00', '0.00', '0.00', 1, 1, NULL, NULL),
(25, 7, 33, 1, 'Тест Тест', 'Тест', 'Тест', NULL, '', '1', '2314532435453', NULL, '1231233245543', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2000.00', '0.00', '0.00', 0, 3, NULL, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `client_cities`
--

CREATE TABLE `client_cities` (
  `id` int NOT NULL,
  `client_id` int NOT NULL,
  `city_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Дамп данных таблицы `client_cities`
--

INSERT INTO `client_cities` (`id`, `client_id`, `city_id`) VALUES
(7, 2, 1),
(11, 6, 1),
(17, 4, 1),
(26, 8, 2),
(27, 7, 1),
(28, 9, 1),
(29, 10, 1),
(34, 1, 1),
(35, 1, 3),
(36, 1, 2),
(37, 5, 1),
(68, 11, 2),
(69, 11, 1),
(70, 12, 1),
(71, 13, 1),
(72, 3, 1),
(73, 14, 1),
(74, 15, 1),
(75, 16, 1),
(76, 16, 3),
(87, 18, 1),
(88, 17, 1),
(96, 20, 1),
(131, 24, 2),
(158, 25, 1),
(202, 19, 1),
(241, 23, 1),
(260, 21, 1),
(262, 22, 1);

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

--
-- Дамп данных таблицы `client_input_values`
--

INSERT INTO `client_input_values` (`id`, `client_id`, `input_id`, `value`) VALUES
(60, 25, 11, '123'),
(61, 25, 12, '2'),
(62, 25, 13, 'Нет'),
(74, 23, 13, 'Да'),
(75, 22, 13, 'Да');

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
(1, 'Касса 1', '2574870.70', 1),
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
(2, 'Даб', '-39109.90', 1),
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
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `affected_clients_log` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin COMMENT 'Лог анкет, затронутых транзакцией'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `fin_transactions`
--

INSERT INTO `fin_transactions` (`id`, `transaction_date`, `operation_type`, `amount`, `cash_id`, `agent_id`, `supplier_id`, `comment`, `affected_clients_log`) VALUES
(1, '2025-08-22 13:01:40', 1, '5450.00', 3, 19, NULL, 'папапирипирурап', NULL),
(2, '2025-08-22 13:01:40', 1, '4044.00', 2, 19, NULL, NULL, NULL),
(3, '2025-08-22 13:01:50', 2, '-5450.00', 5, NULL, NULL, NULL, NULL),
(4, '2025-08-22 13:01:50', 1, '444.00', 2, 21, NULL, NULL, NULL),
(7, '2025-09-08 22:46:26', 1, '5000.00', 1, 19, NULL, NULL, NULL),
(8, '2025-09-09 02:38:52', 1, '1000.00', 1, 19, NULL, NULL, NULL),
(9, '2025-09-09 09:13:16', 1, '2000.10', 1, 19, NULL, NULL, NULL),
(10, '2025-09-09 09:16:16', 1, '1000.00', 1, 21, NULL, NULL, NULL),
(11, '2025-09-09 12:37:13', 2, '-1000.00', 1, NULL, 1, NULL, NULL),
(12, '2025-09-09 18:30:29', 2, '-5000.00', 3, NULL, 1, NULL, NULL),
(13, '2025-09-09 19:29:23', 1, '1000.00', 1, 19, NULL, NULL, NULL),
(14, '2025-09-09 19:29:34', 2, '-2000.00', 1, NULL, 1, NULL, NULL),
(15, '2025-09-09 19:59:32', 0, '-1000.00', 1, NULL, NULL, NULL, NULL),
(16, '2025-09-09 20:00:01', 1, '2000.00', 4, 19, NULL, NULL, NULL),
(17, '2025-09-09 20:00:15', 1, '2000.00', 4, 19, NULL, NULL, NULL),
(18, '2025-09-09 20:36:25', 0, '-1000.00', 1, NULL, NULL, NULL, NULL),
(19, '2025-09-09 20:36:47', 2, '-1000.00', 1, NULL, NULL, NULL, NULL),
(20, '2025-09-09 20:37:00', 2, '-1000.00', 1, NULL, 2, NULL, NULL),
(21, '2025-09-09 20:37:52', 0, '-1000.00', 1, NULL, 2, NULL, NULL),
(22, '2025-09-09 20:55:51', 2, '-1000.00', 1, NULL, 1, NULL, NULL),
(23, '2025-09-09 20:56:22', 2, '-1000.00', 1, NULL, 1, NULL, NULL),
(24, '2025-09-09 20:56:43', 2, '-1000.00', 3, NULL, 1, 'папапирипирурап', NULL),
(25, '2025-09-10 01:47:24', 1, '1.00', 1, 19, NULL, 'test', NULL),
(26, '2025-09-10 01:48:33', 1, '1.00', 1, 19, NULL, NULL, NULL),
(27, '2025-09-10 01:52:34', 1, '1.00', 1, 19, NULL, '', NULL),
(28, '2025-09-10 01:54:54', 1, '1000.00', 1, 19, NULL, '123к', NULL),
(29, '2025-09-10 13:26:52', 2, '-1000.00', 1, NULL, NULL, '', NULL),
(30, '2025-09-10 13:27:33', 2, '-1000.00', 1, NULL, 1, '', NULL),
(31, '2025-09-10 13:48:49', 1, '1.00', 1, 19, NULL, '', NULL),
(32, '2025-09-10 13:49:24', 2, '-2.00', 1, NULL, NULL, '', NULL),
(33, '2025-09-10 13:49:41', 1, '2.00', 1, 19, NULL, '', NULL),
(34, '2025-09-10 20:43:02', 2, '-3000.00', 1, NULL, 2, '123\r\n\r\n876', NULL),
(35, '2025-09-10 20:56:25', 2, '0.00', 3, NULL, 2, 'sadasdasdsadasdasdasdasdasdasddddddaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa\r\naaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaasdasfdsfgfgsdfgsdfgsdfgdghsadasdasdsadasdasdasdasdasdasddddddaaaaaaaaaaa', NULL),
(36, '2025-09-10 21:29:33', 1, '2.00', 4, 19, NULL, '123321', NULL),
(37, '2025-09-10 21:32:29', 2, '0.00', 4, NULL, NULL, '12', NULL),
(38, '2025-09-10 21:34:36', 2, '0.00', 4, NULL, 2, 'пваы', NULL),
(39, '2025-09-10 21:35:08', 2, '0.00', 4, NULL, 2, '456\r\n\r\n798', NULL),
(40, '2025-09-11 16:32:50', 1, '1000.00', 1, 19, NULL, '890', NULL),
(41, '2025-09-11 16:42:25', 1, '1000.00', 1, 21, NULL, '432', NULL),
(42, '2025-09-11 16:50:05', 2, '-890.10', 1, NULL, 2, '09', NULL),
(43, '2025-09-11 16:51:04', 1, '1000.00', 1, 19, NULL, '', NULL),
(44, '2025-09-11 16:51:26', 2, '-2000.20', 1, NULL, NULL, '', NULL),
(45, '2025-09-11 16:51:40', 1, '10000.00', 1, 19, NULL, '', NULL),
(46, '2025-10-04 19:47:02', 1, '100000.00', 1, 33, NULL, '', NULL),
(47, '2025-10-04 19:47:32', 1, '100000.00', 1, 33, NULL, '', NULL),
(48, '2025-10-04 19:49:42', 2, '-1000000.00', 1, NULL, 1, '', NULL),
(49, '2025-10-04 19:50:16', 1, '1000000.00', 1, 19, NULL, '', NULL),
(50, '2025-10-04 19:52:25', 1, '1000000.00', 1, 19, NULL, '', NULL),
(51, '2025-10-04 19:52:56', 1, '1000000.00', 1, 19, NULL, '', NULL),
(52, '2025-10-04 19:54:09', 1, '255.00', 3, 33, NULL, '', NULL),
(53, '2025-10-04 19:54:40', 1, '2.00', 4, 33, NULL, '', NULL),
(54, '2025-10-04 19:55:35', 2, '-33.00', 1, NULL, 1, '', NULL),
(55, '2025-10-04 19:56:17', 1, '300000.00', 1, 33, NULL, '', NULL),
(56, '2025-10-04 20:37:32', 1, '15000.00', 1, 33, NULL, '', NULL),
(57, '2025-10-14 20:46:41', 1, '1000.00', 1, 33, NULL, '', NULL),
(58, '2025-10-14 20:49:05', 1, '3000.00', 1, 33, NULL, '', NULL),
(59, '2025-10-14 20:50:36', 1, '1000.00', 1, 33, NULL, '', NULL),
(60, '2025-10-14 20:53:02', 1, '1000.00', 1, 33, NULL, '', NULL),
(61, '2025-10-14 21:16:12', 1, '1100.00', 1, 33, NULL, '', NULL),
(62, '2025-10-14 21:31:52', 1, '1100.00', 1, 33, NULL, '', NULL),
(63, '2025-10-14 21:32:23', 1, '1100.00', 1, 33, NULL, '', NULL),
(80, '2025-10-14 21:36:27', 1, '1100.00', 1, 33, NULL, '', NULL),
(81, '2025-10-14 21:42:47', 1, '1100.00', 1, 33, NULL, '', NULL),
(82, '2025-10-14 21:51:33', 1, '50.00', 1, 33, NULL, '', NULL),
(83, '2025-10-14 22:09:21', 1, '1100.00', 1, 33, NULL, '', NULL),
(84, '2025-10-14 22:13:20', 1, '1100.00', 1, 33, NULL, '', NULL),
(85, '2025-10-14 22:17:25', 1, '1100.00', 1, 33, NULL, '', NULL),
(86, '2025-10-14 22:28:25', 1, '1100.00', 1, 33, NULL, '', NULL),
(87, '2025-10-14 22:34:44', 1, '1100.00', 1, 33, NULL, '', NULL),
(88, '2025-10-14 22:48:57', 1, '1100.00', 1, 33, NULL, '', NULL),
(89, '2025-10-16 21:43:48', 1, '1100.00', 1, 33, NULL, '', NULL),
(90, '2025-10-16 21:47:43', 1, '1100.00', 1, 33, NULL, '', NULL),
(91, '2025-10-16 21:52:23', 1, '1100.00', 1, 33, NULL, '', NULL),
(92, '2025-10-16 22:06:53', 1, '1100.00', 1, 33, NULL, '', NULL),
(93, '2025-10-16 22:12:03', 1, '1100.00', 1, 33, NULL, '', NULL),
(94, '2025-10-16 22:15:12', 1, '1100.00', 1, 33, NULL, '', NULL),
(95, '2025-10-22 21:06:09', 1, '1100.00', 1, 33, NULL, '', NULL),
(96, '2025-10-22 22:07:45', 1, '1100.00', 1, 33, NULL, '', NULL),
(97, '2025-10-22 23:38:25', 2, '-1100.00', 1, NULL, NULL, '', NULL),
(98, '2025-10-22 23:38:46', 1, '0.00', 1, 33, NULL, '', NULL),
(99, '2025-10-22 23:39:07', 2, '0.00', 1, NULL, 1, '', NULL),
(100, '2025-10-23 16:59:22', 1, '1100.00', 1, 33, NULL, '', NULL),
(101, '2025-10-23 17:00:15', 1, '1100.00', 1, 33, NULL, '', NULL),
(102, '2025-10-23 17:00:23', 1, '1100.00', 1, 33, NULL, '', NULL),
(103, '2025-10-23 17:01:01', 1, '1100.00', 1, 33, NULL, '', NULL),
(104, '2025-10-23 17:23:52', 1, '1100.00', 1, 33, NULL, '', NULL),
(105, '2025-10-23 17:30:00', 1, '1100.00', 1, 33, NULL, '', NULL),
(106, '2025-10-23 17:35:16', 1, '1100.00', 1, 33, NULL, '', NULL),
(107, '2025-10-23 17:46:10', 1, '1100.00', 1, 33, NULL, '', NULL),
(108, '2025-10-23 17:53:16', 1, '1100.00', 1, 33, NULL, '', NULL),
(109, '2025-10-23 21:52:38', 1, '1100.00', 1, 33, NULL, '', NULL),
(110, '2025-10-23 21:54:46', 1, '1100.00', 1, 33, NULL, '', NULL),
(111, '2025-10-23 21:57:29', 1, '1090.00', 1, 33, NULL, '', NULL),
(112, '2025-10-24 10:29:20', 1, '1200.00', 1, 33, NULL, '', NULL),
(113, '2025-10-24 10:33:23', 1, '1100.00', 1, 33, NULL, '', NULL),
(114, '2025-10-26 22:40:37', 1, '1100.00', 1, 33, NULL, '', '[{\"type\": \"credit_repayment\", \"amount\": 1000, \"client_id\": 21}, {\"type\": \"full_payment\", \"amount\": 100, \"client_id\": 22}]'),
(115, '2025-10-27 11:39:13', 1, '1100.00', 1, 33, NULL, '', '[{\"client_id\":21,\"type\":\"credit_repayment\",\"amount\":1000},{\"client_id\":22,\"type\":\"full_payment\",\"amount\":100}]'),
(116, '2025-10-27 11:45:03', 1, '1100.00', 1, 33, NULL, '', '[{\"client_id\":21,\"type\":\"credit_repayment\",\"amount\":1000},{\"client_id\":22,\"type\":\"full_payment\",\"amount\":100}]'),
(117, '2025-10-27 11:48:08', 1, '1100.00', 1, 33, NULL, '', '[{\"client_id\":21,\"type\":\"credit_repayment\",\"amount\":1000},{\"client_id\":22,\"type\":\"full_payment\",\"amount\":100}]'),
(118, '2025-10-27 11:55:09', 1, '1100.00', 1, 33, NULL, '', '[{\"client_id\":21,\"type\":\"credit_repayment\",\"amount\":1000},{\"client_id\":22,\"type\":\"full_payment\",\"amount\":100}]'),
(119, '2025-10-27 11:57:38', 1, '1100.00', 1, 33, NULL, '', '[{\"client_id\":21,\"type\":\"credit_repayment\",\"amount\":1000},{\"client_id\":22,\"type\":\"full_payment\",\"amount\":100}]'),
(120, '2025-10-27 12:01:59', 1, '1100.00', 1, 33, NULL, '', '[{\"client_id\":21,\"type\":\"credit_repayment\",\"amount\":1000},{\"client_id\":22,\"type\":\"full_payment\",\"amount\":100}]'),
(121, '2025-10-27 12:06:15', 1, '1100.00', 1, 33, NULL, '', '[{\"client_id\":21,\"type\":\"credit_repayment\",\"amount\":1000},{\"client_id\":22,\"type\":\"full_payment\",\"amount\":100}]'),
(122, '2025-10-27 12:12:11', 1, '1100.00', 1, 33, NULL, '', '[{\"client_id\":21,\"type\":\"credit_repayment\",\"amount\":1000},{\"client_id\":22,\"type\":\"full_payment\",\"amount\":100}]'),
(123, '2025-10-27 12:19:14', 1, '1100.00', 1, 33, NULL, '', '[{\"client_id\":21,\"type\":\"full_payment\",\"amount\":1000},{\"client_id\":22,\"type\":\"full_payment\",\"amount\":100}]'),
(124, '2025-10-27 12:24:44', 0, '1100.00', 1, 33, NULL, '', '[{\"client_id\":21,\"type\":\"full_payment\",\"amount\":1000},{\"client_id\":22,\"type\":\"full_payment\",\"amount\":100}]'),
(125, '2025-10-27 12:38:52', 0, '1000.00', 1, 33, NULL, '', '[{\"client_id\":21,\"type\":\"full_payment\",\"amount\":1000}]');

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
(6, 'ВЦ России', 17, 1),
(7, 'тест', 22, 1);

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

--
-- Дамп данных таблицы `settings_center_fields`
--

INSERT INTO `settings_center_fields` (`id`, `center_id`, `field_name`, `is_visible`, `is_required`) VALUES
(61, 7, 'middle_name', 1, 0),
(62, 7, 'phone', 1, 0),
(63, 7, 'gender', 1, 0),
(64, 7, 'email', 1, 0),
(65, 7, 'birth_date', 1, 0),
(66, 7, 'passport_expiry_date', 1, 0),
(67, 7, 'nationality', 1, 0),
(68, 7, 'visit_dates', 0, 0),
(69, 7, 'days_until_visit', 0, 0),
(70, 7, 'notes', 0, 0),
(91, 2, 'middle_name', 1, 0),
(92, 2, 'phone', 0, 0),
(93, 2, 'gender', 0, 0),
(94, 2, 'email', 0, 0),
(95, 2, 'birth_date', 0, 0),
(96, 2, 'passport_expiry_date', 0, 0),
(97, 2, 'nationality', 0, 0),
(98, 2, 'visit_dates', 0, 0),
(99, 2, 'days_until_visit', 0, 0),
(100, 2, 'notes', 0, 0),
(131, 1, 'middle_name', 1, 0),
(132, 1, 'phone', 1, 0),
(133, 1, 'gender', 1, 0),
(134, 1, 'email', 1, 0),
(135, 1, 'birth_date', 1, 0),
(136, 1, 'passport_expiry_date', 1, 0),
(137, 1, 'nationality', 1, 0),
(138, 1, 'visit_dates', 1, 0),
(139, 1, 'days_until_visit', 1, 0),
(140, 1, 'notes', 1, 0);

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
(17, 'Египет', 1),
(18, 'тест', 1),
(19, 'тест 2', 1),
(20, 'тест 3', 1),
(21, 'тест 4', 1),
(22, 'тест 5', 1);

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
  `user_tel_2` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `user_balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `user_credit_limit` decimal(15,2) NOT NULL DEFAULT '0.00',
  `user_supervisor` int NOT NULL DEFAULT '0',
  `can_export` tinyint(1) NOT NULL DEFAULT '0',
  `user_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `user_website` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `user_messengers` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `user_comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`user_id`, `user_login`, `user_password`, `user_group`, `user_status`, `user_session_key`, `user_firstname`, `user_lastname`, `user_tel`, `user_tel_2`, `user_balance`, `user_credit_limit`, `user_supervisor`, `can_export`, `user_address`, `user_website`, `user_messengers`, `user_comment`) VALUES
(1, 'a@a.a', 'e10adc3949ba59abbe56e057f20f883e', 1, 1, 'KezBYaaiWksJjB7DVHgqe3Ntde48tWC1hvmNWidnjLyCov9g5D+nEsYRLIWJ9wwFdhWFm0kAyAVwEZLM7ocdCQLexZIevo2pBZujrlIIRvmNHUaXRVRbD8mkte/vQn44', 'Сергей', 'Фамилия', '+79009009090', NULL, '0.00', '0.00', 0, 0, NULL, NULL, NULL, NULL),
(2, 'a@a.aa', '74b87337454200d4d33f80c4663dc5e5', 1, 1, '', 'Сергей', 'Фамилия', '+79009009091', NULL, '0.00', '0.00', 0, 0, NULL, NULL, NULL, NULL),
(3, 'd@d.d', '74b87337454200d4d33f80c4663dc5e5', 2, 1, '', 'Имя', 'Фамилия', '+79009009092', NULL, '0.00', '0.00', 0, 0, NULL, NULL, NULL, NULL),
(12, 's@s.ss', '74b87337454200d4d33f80c4663dc5e5', 3, 1, '', 'Олег', 'Фамилия', '+79009009093', NULL, '0.00', '0.00', 3, 0, NULL, NULL, NULL, NULL),
(19, 'asd@asd.sadwwww', '74b87337454200d4d33f80c4663dc5e5', 4, 1, '', 'Ирина', 'Фамилия', '+79009009094', NULL, '1017252.50', '0.00', 32, 0, NULL, NULL, NULL, NULL),
(21, 's@s.ssw', '74b87337454200d4d33f80c4663dc5e5', 4, 1, '', 'Роман', 'Фамилия', '+79009009098', NULL, '-11111.48', '0.00', 12, 0, '', '', '', ''),
(22, 's@s.sss', '74b87337454200d4d33f80c4663dc5e5', 3, 1, '', 'Вадим', 'Фамилия', '+79009009097', NULL, '0.00', '0.00', 3, 0, NULL, NULL, NULL, NULL),
(27, 'a@a.aasdd', '74b87337454200d4d33f80c4663dc5e5', 4, 0, '', 'Сергей', 'Фамилия', '+234324234234234', NULL, '0.00', '0.00', 12, 0, NULL, NULL, NULL, NULL),
(28, 'a@a.aeee', '74b87337454200d4d33f80c4663dc5e5', 1, 0, '', 'Анатолий', 'Фамилия', '+79009009096', NULL, '0.00', '0.00', 0, 0, NULL, NULL, NULL, NULL),
(29, 'asd@asd.sf', '202cb962ac59075b964b07152d234b70', 2, 0, '', 'пвпа', 'вапва', '+899444444444', NULL, '0.00', '0.00', 0, 0, NULL, NULL, NULL, NULL),
(30, 'rukA@a.a', '202cb962ac59075b964b07152d234b70', 2, 1, '', 'Руководитель', 'типА', '+213123123', NULL, '0.00', '0.00', 0, 0, '123', '', '', ''),
(31, 'rukB@a.a', '202cb962ac59075b964b07152d234b70', 2, 1, '', 'Руководитель', 'тип-Б', '+12394352', NULL, '0.00', '0.00', 0, 0, NULL, NULL, NULL, NULL),
(32, 'menA@a.a', '202cb962ac59075b964b07152d234b70', 3, 1, '', 'Менежер', 'тип-А-1', '+123123453', NULL, '0.00', '0.00', 30, 0, NULL, NULL, NULL, NULL),
(33, 'agA@a.a', '202cb962ac59075b964b07152d234b70', 4, 1, '', 'Агент', 'тип-А-1', '+3234546657', NULL, '-550.00', '1500.00', 32, 1, '', '', '', ''),
(34, 'agA@aasdasd.a', '202cb962ac59075b964b07152d234b70', 1, 0, '', 'test', 'test', '+98543739845', NULL, '0.00', '0.00', 0, 0, 'Тихонравова, 6, 38 Владимир 600037', 'site.ru', 'telegram:test_user|viber:89959606801', 'dsfsdfsdfsdfsdf');

-- --------------------------------------------------------

--
-- Структура таблицы `user_countries`
--

CREATE TABLE `user_countries` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `country_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Дамп данных таблицы `user_countries`
--

INSERT INTO `user_countries` (`id`, `user_id`, `country_id`) VALUES
(5, 33, 1);

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
-- Дамп данных таблицы `user_export_settings`
--

INSERT INTO `user_export_settings` (`id`, `user_id`, `center_id`, `settings`) VALUES
(1, 1, 1, '{\"fields\":[\"c.middle_name\",\"phone_combined\",\"c.gender\",\"c.email\",\"c.birth_date\",\"c.passport_expiry_date\",\"c.nationality\",\"manager_name\",\"agent_name\",\"client_cities_list\",\"client_categories_list\",\"c.sale_price\",\"c.visit_date_start\",\"c.visit_date_end\",\"c.days_until_visit\",\"c.notes\",\"input_11\",\"input_12\",\"input_13\"],\"field_order\":{\"c.client_id\":\"\",\"c.last_name\":\"\",\"c.first_name\":\"\",\"c.middle_name\":\"\",\"phone_combined\":\"\",\"c.gender\":\"\",\"c.email\":\"\",\"c.passport_number\":\"\",\"c.birth_date\":\"\",\"c.passport_expiry_date\":\"\",\"c.nationality\":\"\",\"manager_name\":\"\",\"agent_name\":\"\",\"client_cities_list\":\"\",\"client_categories_list\":\"\",\"c.sale_price\":\"\",\"c.visit_date_start\":\"\",\"c.visit_date_end\":\"\",\"c.days_until_visit\":\"\",\"c.notes\":\"\",\"input_11\":\"\",\"input_12\":\"\",\"input_13\":\"\"}}'),
(2, 33, 1, '{\"fields\":[\"c.middle_name\",\"phone_combined\",\"c.gender\",\"c.email\",\"manager_name\",\"agent_name\",\"client_cities_list\",\"client_categories_list\",\"c.sale_price\",\"c.visit_date_start\",\"c.visit_date_end\",\"c.days_until_visit\",\"c.notes\",\"input_11\",\"input_12\",\"input_13\"],\"field_order\":{\"c.client_id\":\"\",\"c.last_name\":\"\",\"c.first_name\":\"\",\"c.middle_name\":\"\",\"phone_combined\":\"\",\"c.gender\":\"\",\"c.email\":\"\",\"c.passport_number\":\"\",\"manager_name\":\"\",\"agent_name\":\"\",\"client_cities_list\":\"\",\"client_categories_list\":\"\",\"c.sale_price\":\"\",\"c.visit_date_start\":\"\",\"c.visit_date_end\":\"\",\"c.days_until_visit\":\"\",\"c.notes\":\"\",\"input_11\":\"\",\"input_12\":\"\",\"input_13\":\"\"}}');

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT для таблицы `clients`
--
ALTER TABLE `clients`
  MODIFY `client_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT для таблицы `client_cities`
--
ALTER TABLE `client_cities`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=263;

--
-- AUTO_INCREMENT для таблицы `client_input_values`
--
ALTER TABLE `client_input_values`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- AUTO_INCREMENT для таблицы `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT для таблицы `settings_centers`
--
ALTER TABLE `settings_centers`
  MODIFY `center_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT для таблицы `settings_center_fields`
--
ALTER TABLE `settings_center_fields`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;

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
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT для таблицы `user_countries`
--
ALTER TABLE `user_countries`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `user_export_settings`
--
ALTER TABLE `user_export_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
