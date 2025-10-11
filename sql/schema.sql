-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 11-10-2025 a las 22:32:25
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `parteoficialturno`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categoria`
--

CREATE TABLE `categoria` (
  `id` tinyint(4) NOT NULL,
  `nombre` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categoria`
--

INSERT INTO `categoria` (`id`, `nombre`) VALUES
(5, 'Sistemas – Data Center'),
(3, 'Sistemas – ISP Edificio Libertador'),
(1, 'Sistemas – Radioeléctricos – Nodos REDISE'),
(2, 'Sistemas – Servicios'),
(6, 'Sistemas – SITM2'),
(4, 'SITELPAR');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `login_audit`
--

CREATE TABLE `login_audit` (
  `id` bigint(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `success` tinyint(1) NOT NULL,
  `reason` varchar(180) DEFAULT NULL,
  `ip` varchar(64) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `login_audit`
--

INSERT INTO `login_audit` (`id`, `user_id`, `email`, `success`, `reason`, `ip`, `user_agent`, `created_at`) VALUES
(1, 1, 'nestor.g.rojas99@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 08:55:08'),
(2, 1, 'nestor.g.rojas99@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 08:55:15'),
(3, 1, 'nestor.g.rojas99@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 09:12:50'),
(4, 1, 'nestor.g.rojas99@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 09:16:13'),
(5, 1, 'nestor.g.rojas99@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 09:22:44'),
(6, 1, 'nestor.g.rojas99@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 09:23:07'),
(7, 1, 'nestor.g.rojas99@gmail.com', 0, 'password_invalido', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 09:23:42'),
(8, 1, 'nestor.g.rojas99@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 09:23:54'),
(9, 1, 'nestor.g.rojas99@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 09:29:52'),
(10, 1, 'nestor.g.rojas99@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 09:44:56'),
(11, 1, 'nestor.g.rojas99@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 09:45:03'),
(12, 1, 'nestor.g.rojas99@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 09:45:43'),
(13, 1, 'nestor.g.rojas99@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 09:45:53'),
(14, 1, 'nestor.g.rojas99@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 09:55:53'),
(15, 1, 'nestor.g.rojas99@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 09:56:19'),
(16, 1, 'nestor.g.rojas99@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 10:01:33'),
(17, 2, 'gabi.rojas.3399@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 10:03:47'),
(18, 2, 'gabi.rojas.3399@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 10:22:49'),
(19, 1, 'nestor.g.rojas99@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 10:22:57'),
(20, 1, 'nestor.g.rojas99@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 10:26:02'),
(21, 1, 'nestor.g.rojas99@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 10:26:24'),
(22, 1, 'nestor.g.rojas99@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 10:26:37'),
(23, 1, 'nestor.g.rojas99@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 10:38:45'),
(24, 2, 'gabi.rojas.3399@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 10:39:02'),
(25, 1, 'nestor.g.rojas99@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 10:39:56'),
(26, 1, 'nestor.g.rojas99@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 10:40:35'),
(27, 2, 'gabi.rojas.3399@gmail.com', 0, 'usuario_inactivo', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 10:41:14'),
(28, 2, 'gabi.rojas.3399@gmail.com', 0, 'usuario_inactivo', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 10:41:19'),
(29, 1, 'nestor.g.rojas99@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 10:41:23'),
(30, 2, 'gabi.rojas.3399@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 10:41:33'),
(31, 2, 'gabi.rojas.3399@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 10:45:17'),
(32, 1, 'nestor.g.rojas99@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 10:45:35'),
(33, 1, 'nestor.g.rojas99@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 10:45:44'),
(34, 2, 'gabi.rojas.3399@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 10:50:58'),
(35, 1, 'nestor.g.rojas99@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 10:51:08'),
(36, 2, 'gabi.rojas.3399@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 10:56:50'),
(37, 1, 'nestor.g.rojas99@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 11:46:13'),
(38, 1, 'nestor.g.rojas99@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 11:46:31'),
(39, 1, 'nestor.g.rojas99@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 11:46:42'),
(40, 1, 'nestor.g.rojas99@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 11:52:53'),
(41, 1, 'nestor.g.rojas99@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 17:23:40'),
(42, 1, 'nestor.g.rojas99@gmail.com', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 17:27:05');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `novedad`
--

CREATE TABLE `novedad` (
  `id` int(11) NOT NULL,
  `titulo` varchar(140) NOT NULL,
  `descripcion` text NOT NULL,
  `categoria_id` tinyint(4) NOT NULL,
  `unidad_id` int(11) DEFAULT NULL,
  `servicio` varchar(40) DEFAULT NULL,
  `ticket` varchar(60) DEFAULT NULL,
  `prioridad` enum('BAJA','MEDIA','ALTA') DEFAULT 'MEDIA',
  `estado` enum('ABIERTO','EN_PROCESO','RESUELTO') NOT NULL DEFAULT 'ABIERTO',
  `fecha_inicio` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_resolucion` datetime DEFAULT NULL,
  `creado_por` varchar(80) DEFAULT NULL,
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `novedad_evento`
--

CREATE TABLE `novedad_evento` (
  `id` bigint(20) NOT NULL,
  `novedad_id` int(11) NOT NULL,
  `tipo` enum('CREADA','ACTUALIZADA','RESUELTA','REABIERTA') NOT NULL,
  `detalle` text DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `usuario` varchar(80) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `partes`
--

CREATE TABLE `partes` (
  `id` int(11) NOT NULL,
  `fecha_desde` datetime NOT NULL,
  `fecha_hasta` datetime NOT NULL,
  `oficial_turno` varchar(120) DEFAULT NULL,
  `suboficial_turno` varchar(120) DEFAULT NULL,
  `titulo` varchar(255) NOT NULL,
  `file_rel_path` varchar(255) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `parte_arma`
--

CREATE TABLE `parte_arma` (
  `id` int(11) NOT NULL,
  `desde` datetime NOT NULL,
  `hasta` datetime NOT NULL,
  `oficial` varchar(120) NOT NULL,
  `suboficial` varchar(120) NOT NULL,
  `turno` char(7) NOT NULL,
  `html_path` varchar(255) NOT NULL,
  `pdf_path` varchar(255) NOT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `parte_arma_data`
--

CREATE TABLE `parte_arma_data` (
  `id` int(11) NOT NULL,
  `parte_id` int(11) NOT NULL,
  `cenope_json` mediumtext NOT NULL,
  `redise_json` mediumtext NOT NULL,
  `texto_ccc` mediumtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `parte_encabezado`
--

CREATE TABLE `parte_encabezado` (
  `id` int(11) NOT NULL,
  `fecha_desde` datetime NOT NULL,
  `fecha_hasta` datetime NOT NULL,
  `oficial_turno` varchar(120) NOT NULL,
  `suboficial_turno` varchar(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `parte_encabezado`
--

INSERT INTO `parte_encabezado` (`id`, `fecha_desde`, `fecha_hasta`, `oficial_turno`, `suboficial_turno`) VALUES
(1, '2025-09-27 08:00:00', '2025-09-28 08:00:00', 'st rojas', 'cb perez'),
(13, '2025-09-28 08:00:00', '2025-09-29 08:00:00', 'ST MAIDANA', 'SI E'),
(15, '2025-10-01 08:00:00', '2025-10-02 08:00:00', 'ST ROJAS', 'CB MARTINEZ'),
(31, '2025-10-02 08:00:00', '2025-10-03 08:00:00', 'ST SCD NESTOR ROJAS', 'CB INF JOSE LUIS PEDROZO'),
(37, '2025-10-03 08:00:00', '2025-10-04 08:00:00', 'ST ROJAS', 'CB MARTINEZ'),
(41, '2025-10-11 08:00:00', '2025-10-12 08:00:00', 'ST SCD NESTOR GABRIEL ROJAS', 'CB MEC EQ FIJ GONZALEZ');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_resets`
--

CREATE TABLE `password_resets` (
  `id` bigint(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `ip` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `token`, `expires_at`, `used_at`, `created_at`, `ip`) VALUES
(1, 1, 'e680676f253fe2693873b30f42b5da6b920c5a9defb73bd1d442af02c00e810f', '2025-10-11 10:24:32', NULL, '2025-10-11 09:24:32', '::1'),
(2, 1, '89cfab68023c81409142d20c8006aa4b8ecab91ff4b03b343a4ad9f802e3119f', '2025-10-11 10:25:19', NULL, '2025-10-11 09:25:19', '::1'),
(3, 1, 'a10ac106846271e769930c3c948b7f374b4c07bae4952c46d49f349878589135', '2025-10-11 10:25:23', NULL, '2025-10-11 09:25:23', '::1');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personal_alta`
--

CREATE TABLE `personal_alta` (
  `id` int(11) NOT NULL,
  `categoria` enum('OFICIALES','SUBOFICIALES','SOLDADOS VOLUNTARIOS') DEFAULT NULL,
  `nro` int(11) DEFAULT NULL,
  `grado` varchar(5) NOT NULL,
  `apellido_nombre` varchar(120) DEFAULT NULL,
  `apellidoNombre` varchar(120) DEFAULT '',
  `arma` varchar(40) DEFAULT NULL,
  `unidad` varchar(80) DEFAULT NULL,
  `prom` varchar(20) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `habitacion` varchar(40) DEFAULT NULL,
  `hospital` varchar(80) DEFAULT NULL,
  `detalle` text DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `personal_alta`
--
DELIMITER $$
CREATE TRIGGER `bi_personal_alta_cat` BEFORE INSERT ON `personal_alta` FOR EACH ROW BEGIN
  IF NEW.categoria IS NULL OR NEW.categoria = '' THEN
    SET NEW.categoria =
      CASE
        WHEN NEW.grado IN ('TG','GD','GB','CY','CR','TC','MY','CT','TP','TT','ST') THEN 'OFICIALES'
        WHEN NEW.grado IN ('SM','SP','SA','SI','SG','CI','CB')                       THEN 'SUBOFICIALES'
        ELSE 'SOLDADOS VOLUNTARIOS'
      END;
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `bu_personal_alta_cat` BEFORE UPDATE ON `personal_alta` FOR EACH ROW BEGIN
  IF NEW.categoria IS NULL OR NEW.categoria = '' THEN
    SET NEW.categoria =
      CASE
        WHEN NEW.grado IN ('TG','GD','GB','CY','CR','TC','MY','CT','TP','TT','ST') THEN 'OFICIALES'
        WHEN NEW.grado IN ('SM','SP','SA','SI','SG','CI','CB')                       THEN 'SUBOFICIALES'
        ELSE 'SOLDADOS VOLUNTARIOS'
      END;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personal_fallecido`
--

CREATE TABLE `personal_fallecido` (
  `id` int(11) NOT NULL,
  `categoria` enum('OFICIALES','SUBOFICIALES','SOLDADOS VOLUNTARIOS') DEFAULT NULL,
  `nro` int(11) DEFAULT NULL,
  `grado` varchar(5) NOT NULL,
  `apellido_nombre` varchar(120) DEFAULT NULL,
  `apellidoNombre` varchar(120) DEFAULT '',
  `arma` varchar(40) DEFAULT NULL,
  `unidad` varchar(80) DEFAULT NULL,
  `prom` varchar(20) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `habitacion` varchar(40) DEFAULT NULL,
  `hospital` varchar(80) DEFAULT NULL,
  `detalle` text DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `personal_fallecido`
--

INSERT INTO `personal_fallecido` (`id`, `categoria`, `nro`, `grado`, `apellido_nombre`, `apellidoNombre`, `arma`, `unidad`, `prom`, `fecha`, `habitacion`, `hospital`, `detalle`, `creado_en`) VALUES
(22, 'SUBOFICIALES', 1, 'SA', 'Prada Walter Rolando Av Jorge Newbery', 'Prada Walter Rolando Av Jorge Newbery', 'COM', 'Retirado', NULL, '2025-09-30', NULL, NULL, 'Falleció en – - EL CALAFATE, SANTA CRUZ 2519  A determinar A determinar el 30Sep25', '2025-10-11 17:27:52');

--
-- Disparadores `personal_fallecido`
--
DELIMITER $$
CREATE TRIGGER `bi_personal_fallecido_cat` BEFORE INSERT ON `personal_fallecido` FOR EACH ROW BEGIN
  IF NEW.categoria IS NULL OR NEW.categoria = '' THEN
    SET NEW.categoria =
      CASE
        WHEN NEW.grado IN ('TG','GD','GB','CY','CR','TC','MY','CT','TP','TT','ST') THEN 'OFICIALES'
        WHEN NEW.grado IN ('SM','SP','SA','SI','SG','CI','CB')                       THEN 'SUBOFICIALES'
        ELSE 'SOLDADOS VOLUNTARIOS'
      END;
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `bu_personal_fallecido_cat` BEFORE UPDATE ON `personal_fallecido` FOR EACH ROW BEGIN
  IF NEW.categoria IS NULL OR NEW.categoria = '' THEN
    SET NEW.categoria =
      CASE
        WHEN NEW.grado IN ('TG','GD','GB','CY','CR','TC','MY','CT','TP','TT','ST') THEN 'OFICIALES'
        WHEN NEW.grado IN ('SM','SP','SA','SI','SG','CI','CB')                       THEN 'SUBOFICIALES'
        ELSE 'SOLDADOS VOLUNTARIOS'
      END;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personal_internado`
--

CREATE TABLE `personal_internado` (
  `id` int(11) NOT NULL,
  `categoria` enum('OFICIALES','SUBOFICIALES','SOLDADOS VOLUNTARIOS') DEFAULT NULL,
  `nro` int(11) DEFAULT NULL,
  `grado` varchar(5) NOT NULL,
  `apellido_nombre` varchar(120) DEFAULT NULL,
  `apellidoNombre` varchar(120) DEFAULT '',
  `arma` varchar(40) DEFAULT NULL,
  `unidad` varchar(80) DEFAULT NULL,
  `prom` varchar(20) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `habitacion` varchar(40) DEFAULT NULL,
  `hospital` varchar(80) DEFAULT NULL,
  `detalle` text DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `personal_internado`
--

INSERT INTO `personal_internado` (`id`, `categoria`, `nro`, `grado`, `apellido_nombre`, `apellidoNombre`, `arma`, `unidad`, `prom`, `fecha`, `habitacion`, `hospital`, `detalle`, `creado_en`) VALUES
(183, 'OFICIALES', 1, 'CR', 'LANDA DIEGO HORACIO', 'LANDA DIEGO HORACIO', 'Com', 'Retirado', NULL, '2025-09-22', '433', NULL, NULL, '2025-10-11 17:27:52'),
(184, 'SUBOFICIALES', 2, 'SP', 'Lance Gustavo Daniel', 'Lance Gustavo Daniel', 'Com', 'En Actividad - Ca Com M', NULL, '2025-07-22', '6', 'Clinica Pasteur', NULL, '2025-10-11 17:27:52'),
(185, 'SUBOFICIALES', 3, 'SP', 'Peña German David', 'Peña German David', 'Com', 'Retirado UCO SP Espina Héctor Alberto C	Retirado	23Sep25', NULL, '2025-07-24', '618', NULL, NULL, '2025-10-11 17:27:52'),
(186, 'SUBOFICIALES', 4, 'SA', 'GARINO RAUL', 'GARINO RAUL', 'ABEL Com', 'Retirado', NULL, '2025-05-12', '332', NULL, NULL, '2025-10-11 17:27:52');

--
-- Disparadores `personal_internado`
--
DELIMITER $$
CREATE TRIGGER `bi_personal_internado_cat` BEFORE INSERT ON `personal_internado` FOR EACH ROW BEGIN
  IF NEW.categoria IS NULL OR NEW.categoria = '' THEN
    SET NEW.categoria =
      CASE
        WHEN NEW.grado IN ('TG','GD','GB','CY','CR','TC','MY','CT','TP','TT','ST') THEN 'OFICIALES'
        WHEN NEW.grado IN ('SM','SP','SA','SI','SG','CI','CB')                       THEN 'SUBOFICIALES'
        ELSE 'SOLDADOS VOLUNTARIOS'
      END;
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `bu_personal_internado_cat` BEFORE UPDATE ON `personal_internado` FOR EACH ROW BEGIN
  IF NEW.categoria IS NULL OR NEW.categoria = '' THEN
    SET NEW.categoria =
      CASE
        WHEN NEW.grado IN ('TG','GD','GB','CY','CR','TC','MY','CT','TP','TT','ST') THEN 'OFICIALES'
        WHEN NEW.grado IN ('SM','SP','SA','SI','SG','CI','CB')                       THEN 'SUBOFICIALES'
        ELSE 'SOLDADOS VOLUNTARIOS'
      END;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `redise_snapshot`
--

CREATE TABLE `redise_snapshot` (
  `id` int(11) NOT NULL,
  `turno` char(7) NOT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `texto_ccc` mediumtext NOT NULL,
  `data_json` mediumtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `slug` varchar(40) NOT NULL,
  `nombre` varchar(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `slug`, `nombre`) VALUES
(1, 'admin', 'Administrador'),
(2, 'editor', 'Editor'),
(3, 'viewer', 'Solo lectura');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sistema_estado`
--

CREATE TABLE `sistema_estado` (
  `id` int(11) NOT NULL,
  `categoria_id` tinyint(4) NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `estado` enum('EN LINEA','SIN SERVICIO','NOVEDAD') NOT NULL DEFAULT 'EN LINEA',
  `novedad` text DEFAULT NULL,
  `ticket` varchar(60) DEFAULT NULL,
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sistema_estado`
--

INSERT INTO `sistema_estado` (`id`, `categoria_id`, `nombre`, `estado`, `novedad`, `ticket`, `actualizado_en`) VALUES
(1, 2, 'WEB OFICIAL DEL EJERCITO', 'EN LINEA', NULL, NULL, '2025-09-27 18:40:17'),
(2, 2, 'PORTAL EJERCITO', 'EN LINEA', '', '', '2025-09-28 08:24:29'),
(3, 2, 'INTRANET', 'EN LINEA', '', '', '2025-10-11 10:05:08'),
(4, 2, 'WEBMAIL', 'EN LINEA', NULL, NULL, '2025-09-27 18:40:17'),
(5, 3, 'MODERNIZACION', 'EN LINEA', '', '', '2025-10-01 21:49:28'),
(6, 3, 'GCBA', 'EN LINEA', NULL, NULL, '2025-09-27 18:40:17'),
(7, 3, 'TELECOM (BGP)', 'EN LINEA', NULL, NULL, '2025-09-27 18:40:17'),
(8, 4, 'INTERNO', 'EN LINEA', NULL, NULL, '2025-09-27 18:40:17'),
(9, 4, 'SALIDA TEL FIJA', 'EN LINEA', NULL, NULL, '2025-09-27 18:40:17'),
(10, 4, 'SALIDA TEL MOVIL', 'EN LINEA', NULL, NULL, '2025-09-27 18:40:17'),
(11, 6, 'SITM2', 'EN LINEA', NULL, NULL, '2025-09-27 18:40:17');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `unidad`
--

CREATE TABLE `unidad` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(190) NOT NULL,
  `username` varchar(60) DEFAULT NULL,
  `nombre` varchar(120) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `password_hash` varchar(255) NOT NULL,
  `force_change` tinyint(1) NOT NULL DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `email`, `username`, `nombre`, `activo`, `password_hash`, `force_change`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'nestor.g.rojas99@gmail.com', NULL, 'ST Nestor Rojas', 1, '$2y$10$fXPkRn5DWLrXurG1jdh5V.KZmwrxbV/vTh/bs7zHa2PltCYJ.nU3a', 0, '2025-10-11 17:27:05', '2025-10-11 08:54:47', '2025-10-11 17:27:05'),
(2, 'gabi.rojas.3399@gmail.com', NULL, 'ST SCD GABRIEL ROJAS', 1, '$2y$10$GhgUDX3mjbtXZ6i1MACke.XTO75E97AC.lWvpwImjCXq7tBeGoHY.', 0, '2025-10-11 10:56:50', '2025-10-11 10:03:24', '2025-10-11 10:56:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_role`
--

CREATE TABLE `user_role` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `user_role`
--

INSERT INTO `user_role` (`user_id`, `role_id`) VALUES
(1, 1),
(2, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` bigint(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_id` varchar(128) NOT NULL,
  `ip` varchar(64) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_seen_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `user_sessions`
--

INSERT INTO `user_sessions` (`id`, `user_id`, `session_id`, `ip`, `user_agent`, `created_at`, `last_seen_at`) VALUES
(1, 1, 'i2puq0eqfblhmhgpksu6368uj8', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 08:55:08', '2025-10-11 08:55:08'),
(3, 1, 'oimkbq5vdffvfi7e1rmk0fcp7k', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 09:12:50', '2025-10-11 09:12:50'),
(13, 1, '0vjk9rq3qkupsifahfg8l25v93', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 09:55:53', '2025-10-11 09:55:53'),
(39, 1, '98l5q5pplv43t1hvn9h8vd0aqk', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 17:27:05', '2025-10-11 17:27:05');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_novedad_front`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_novedad_front` (
`id` int(11)
,`titulo` varchar(140)
,`descripcion` text
,`categoria_id` tinyint(4)
,`unidad_id` int(11)
,`servicio` varchar(40)
,`ticket` varchar(60)
,`prioridad` enum('BAJA','MEDIA','ALTA')
,`estado_front` varchar(11)
,`fecha_inicio` datetime
,`fecha_resolucion` datetime
,`creado_por` varchar(80)
,`actualizado_en` datetime
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_personal_alta_orden`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_personal_alta_orden` (
`Nro` bigint(21)
,`Grado` varchar(5)
,`Apellido y Nombre` varchar(120)
,`Arma` varchar(40)
,`Unidad` varchar(80)
,`Prom` varchar(20)
,`Fecha` date
,`Hospital` varchar(80)
,`Categoria` enum('OFICIALES','SUBOFICIALES','SOLDADOS VOLUNTARIOS')
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_personal_fallecido_orden`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_personal_fallecido_orden` (
`Nro` bigint(21)
,`Grado` varchar(5)
,`Apellido y Nombre` varchar(120)
,`Arma` varchar(40)
,`Unidad` varchar(80)
,`Prom` varchar(20)
,`Fecha` date
,`Habitación` varchar(40)
,`Hospital` varchar(80)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_personal_internado_orden`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_personal_internado_orden` (
`Nro` bigint(21)
,`Grado` varchar(5)
,`Apellido y Nombre` varchar(120)
,`Arma` varchar(40)
,`Unidad` varchar(80)
,`Prom` varchar(20)
,`Fecha` date
,`Habitación` varchar(40)
,`Hospital` varchar(80)
,`Categoria` enum('OFICIALES','SUBOFICIALES','SOLDADOS VOLUNTARIOS')
);

-- --------------------------------------------------------

--
-- Estructura para la vista `v_novedad_front`
--
DROP TABLE IF EXISTS `v_novedad_front`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_novedad_front`  AS SELECT `n`.`id` AS `id`, `n`.`titulo` AS `titulo`, `n`.`descripcion` AS `descripcion`, `n`.`categoria_id` AS `categoria_id`, `n`.`unidad_id` AS `unidad_id`, `n`.`servicio` AS `servicio`, `n`.`ticket` AS `ticket`, `n`.`prioridad` AS `prioridad`, CASE `n`.`estado` WHEN 'ABIERTO' THEN 'NUEVA' WHEN 'EN_PROCESO' THEN 'ACTUALIZADA' WHEN 'RESUELTO' THEN 'RESUELTA' ELSE 'ACTUALIZADA' END AS `estado_front`, `n`.`fecha_inicio` AS `fecha_inicio`, `n`.`fecha_resolucion` AS `fecha_resolucion`, `n`.`creado_por` AS `creado_por`, `n`.`actualizado_en` AS `actualizado_en` FROM `novedad` AS `n` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_personal_alta_orden`
--
DROP TABLE IF EXISTS `v_personal_alta_orden`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_personal_alta_orden`  AS SELECT row_number() over ( order by field(`personal_alta`.`grado`,'TG','GD','GB','CY','CR','TC','MY','CT','TP','TT','ST','SM','SP','SA','SI','SG','CI','CB','VP','VS','SV','SOLD'),coalesce(`personal_alta`.`apellido_nombre`,`personal_alta`.`apellidoNombre`)) AS `Nro`, `personal_alta`.`grado` AS `Grado`, coalesce(`personal_alta`.`apellido_nombre`,`personal_alta`.`apellidoNombre`) AS `Apellido y Nombre`, `personal_alta`.`arma` AS `Arma`, `personal_alta`.`unidad` AS `Unidad`, `personal_alta`.`prom` AS `Prom`, `personal_alta`.`fecha` AS `Fecha`, `personal_alta`.`hospital` AS `Hospital`, `personal_alta`.`categoria` AS `Categoria` FROM `personal_alta` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_personal_fallecido_orden`
--
DROP TABLE IF EXISTS `v_personal_fallecido_orden`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_personal_fallecido_orden`  AS SELECT row_number() over ( order by field(`personal_fallecido`.`grado`,'TG','GD','GB','CY','CR','TC','MY','CT','TP','TT','ST','SM','SP','SA','SI','SG','CI','CB','VP','VS','SV','SOLD'),coalesce(`personal_fallecido`.`apellido_nombre`,`personal_fallecido`.`apellidoNombre`)) AS `Nro`, `personal_fallecido`.`grado` AS `Grado`, coalesce(`personal_fallecido`.`apellido_nombre`,`personal_fallecido`.`apellidoNombre`) AS `Apellido y Nombre`, `personal_fallecido`.`arma` AS `Arma`, `personal_fallecido`.`unidad` AS `Unidad`, `personal_fallecido`.`prom` AS `Prom`, `personal_fallecido`.`fecha` AS `Fecha`, `personal_fallecido`.`habitacion` AS `Habitación`, `personal_fallecido`.`hospital` AS `Hospital` FROM `personal_fallecido` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_personal_internado_orden`
--
DROP TABLE IF EXISTS `v_personal_internado_orden`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_personal_internado_orden`  AS SELECT row_number() over ( order by field(`personal_internado`.`grado`,'TG','GD','GB','CY','CR','TC','MY','CT','TP','TT','ST','SM','SP','SA','SI','SG','CI','CB','VP','VS','SV','SOLD'),coalesce(`personal_internado`.`apellido_nombre`,`personal_internado`.`apellidoNombre`)) AS `Nro`, `personal_internado`.`grado` AS `Grado`, coalesce(`personal_internado`.`apellido_nombre`,`personal_internado`.`apellidoNombre`) AS `Apellido y Nombre`, `personal_internado`.`arma` AS `Arma`, `personal_internado`.`unidad` AS `Unidad`, `personal_internado`.`prom` AS `Prom`, `personal_internado`.`fecha` AS `Fecha`, `personal_internado`.`habitacion` AS `Habitación`, `personal_internado`.`hospital` AS `Hospital`, `personal_internado`.`categoria` AS `Categoria` FROM `personal_internado` ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categoria`
--
ALTER TABLE `categoria`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_nombre` (`nombre`);

--
-- Indices de la tabla `login_audit`
--
ALTER TABLE `login_audit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indices de la tabla `novedad`
--
ALTER TABLE `novedad`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_nov_cat` (`categoria_id`),
  ADD KEY `fk_nov_uni` (`unidad_id`);

--
-- Indices de la tabla `novedad_evento`
--
ALTER TABLE `novedad_evento`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_evt_nov` (`novedad_id`),
  ADD KEY `idx_evt_fecha` (`creado_en`),
  ADD KEY `idx_evt_tipo` (`tipo`);

--
-- Indices de la tabla `partes`
--
ALTER TABLE `partes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_file` (`file_rel_path`),
  ADD KEY `idx_fecha` (`fecha_desde`,`fecha_hasta`),
  ADD KEY `idx_ofi` (`oficial_turno`),
  ADD KEY `idx_sub` (`suboficial_turno`);

--
-- Indices de la tabla `parte_arma`
--
ALTER TABLE `parte_arma`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_desde_hasta` (`desde`,`hasta`),
  ADD KEY `idx_turno` (`turno`),
  ADD KEY `idx_creado_en` (`creado_en`);

--
-- Indices de la tabla `parte_arma_data`
--
ALTER TABLE `parte_arma_data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pad_parte` (`parte_id`);

--
-- Indices de la tabla `parte_encabezado`
--
ALTER TABLE `parte_encabezado`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_fecha_desde_hasta` (`fecha_desde`,`fecha_hasta`);

--
-- Indices de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indices de la tabla `personal_alta`
--
ALTER TABLE `personal_alta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pi_grado` (`grado`),
  ADD KEY `idx_pi_ape` (`apellidoNombre`),
  ADD KEY `idx_pi_cat` (`categoria`),
  ADD KEY `idx_pi_apellonombre` (`apellido_nombre`);

--
-- Indices de la tabla `personal_fallecido`
--
ALTER TABLE `personal_fallecido`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pi_grado` (`grado`),
  ADD KEY `idx_pi_ape` (`apellidoNombre`),
  ADD KEY `idx_pi_cat` (`categoria`),
  ADD KEY `idx_pi_apellonombre` (`apellido_nombre`);

--
-- Indices de la tabla `personal_internado`
--
ALTER TABLE `personal_internado`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pi_grado` (`grado`),
  ADD KEY `idx_pi_ape` (`apellidoNombre`),
  ADD KEY `idx_pi_cat` (`categoria`),
  ADD KEY `idx_pi_apellonombre` (`apellido_nombre`);

--
-- Indices de la tabla `redise_snapshot`
--
ALTER TABLE `redise_snapshot`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_turno` (`turno`),
  ADD KEY `idx_creado_en` (`creado_en`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indices de la tabla `sistema_estado`
--
ALTER TABLE `sistema_estado`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_cat_nombre` (`categoria_id`,`nombre`);

--
-- Indices de la tabla `unidad`
--
ALTER TABLE `unidad`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indices de la tabla `user_role`
--
ALTER TABLE `user_role`
  ADD PRIMARY KEY (`user_id`,`role_id`),
  ADD KEY `role_id` (`role_id`);

--
-- Indices de la tabla `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_session` (`session_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categoria`
--
ALTER TABLE `categoria`
  MODIFY `id` tinyint(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `login_audit`
--
ALTER TABLE `login_audit`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT de la tabla `novedad`
--
ALTER TABLE `novedad`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `novedad_evento`
--
ALTER TABLE `novedad_evento`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `partes`
--
ALTER TABLE `partes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `parte_arma`
--
ALTER TABLE `parte_arma`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `parte_arma_data`
--
ALTER TABLE `parte_arma_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `parte_encabezado`
--
ALTER TABLE `parte_encabezado`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `personal_alta`
--
ALTER TABLE `personal_alta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `personal_fallecido`
--
ALTER TABLE `personal_fallecido`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `personal_internado`
--
ALTER TABLE `personal_internado`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=187;

--
-- AUTO_INCREMENT de la tabla `redise_snapshot`
--
ALTER TABLE `redise_snapshot`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `sistema_estado`
--
ALTER TABLE `sistema_estado`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `unidad`
--
ALTER TABLE `unidad`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `novedad`
--
ALTER TABLE `novedad`
  ADD CONSTRAINT `fk_nov_cat` FOREIGN KEY (`categoria_id`) REFERENCES `categoria` (`id`),
  ADD CONSTRAINT `fk_nov_uni` FOREIGN KEY (`unidad_id`) REFERENCES `unidad` (`id`);

--
-- Filtros para la tabla `novedad_evento`
--
ALTER TABLE `novedad_evento`
  ADD CONSTRAINT `fk_evt_nov` FOREIGN KEY (`novedad_id`) REFERENCES `novedad` (`id`);

--
-- Filtros para la tabla `parte_arma_data`
--
ALTER TABLE `parte_arma_data`
  ADD CONSTRAINT `fk_pad_parte` FOREIGN KEY (`parte_id`) REFERENCES `parte_arma` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `sistema_estado`
--
ALTER TABLE `sistema_estado`
  ADD CONSTRAINT `fk_sist_cat` FOREIGN KEY (`categoria_id`) REFERENCES `categoria` (`id`);

--
-- Filtros para la tabla `user_role`
--
ALTER TABLE `user_role`
  ADD CONSTRAINT `user_role_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_role_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
