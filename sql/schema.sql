-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 11-10-2025 a las 13:36:53
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
(37, '2025-10-03 08:00:00', '2025-10-04 08:00:00', 'ST ROJAS', 'CB MARTINEZ');

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
(17, '', 1, 'SA', 'Prada Walter', 'Prada Walter', 'COM', 'Retirado', NULL, '2025-09-30', NULL, NULL, 'Falleció en – Rolando Av Jorge Newbery - EL CALAFATE, SANTA CRUZ 2519 el 30Sep25', '2025-10-03 11:24:21');

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
(163, 'OFICIALES', 1, 'CR', 'LANDA DIEGO HORACIO', 'LANDA DIEGO HORACIO', 'Com', NULL, NULL, '2025-09-22', '433', NULL, NULL, '2025-10-03 11:24:21'),
(164, 'SUBOFICIALES', 2, 'SP', 'Lance Gustavo Daniel', 'Lance Gustavo Daniel', 'Com', 'Ca', NULL, '2025-07-22', '6', NULL, NULL, '2025-10-03 11:24:21'),
(165, 'SUBOFICIALES', 3, 'SP', 'Peña German David', 'Peña German David', 'Com', 'RG', NULL, '2025-07-24', '618', NULL, NULL, '2025-10-03 11:24:21'),
(166, 'SUBOFICIALES', 4, 'SA', 'GARINO RAUL', 'GARINO RAUL', 'ABEL Com', 'RG', NULL, '2025-05-12', '332', NULL, NULL, '2025-10-03 11:24:21');

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
(3, 2, 'INTRANET', 'SIN SERVICIO', '', '', '2025-10-02 10:30:54'),
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
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categoria`
--
ALTER TABLE `categoria`
  MODIFY `id` tinyint(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT de la tabla `personal_alta`
--
ALTER TABLE `personal_alta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `personal_fallecido`
--
ALTER TABLE `personal_fallecido`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `personal_internado`
--
ALTER TABLE `personal_internado`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=167;

--
-- AUTO_INCREMENT de la tabla `redise_snapshot`
--
ALTER TABLE `redise_snapshot`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
-- Filtros para la tabla `sistema_estado`
--
ALTER TABLE `sistema_estado`
  ADD CONSTRAINT `fk_sist_cat` FOREIGN KEY (`categoria_id`) REFERENCES `categoria` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
