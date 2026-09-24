-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 22-09-2026 a las 11:47:07
-- Versión del servidor: 10.6.28-MariaDB-cll-lve
-- Versión de PHP: 8.4.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `autecluz_Projetos`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`, `descripcion`, `activo`, `fecha_creacion`) VALUES
(1, 'Ferramentas', 'Ferramentas manuaies y eletricas', 1, '2026-09-10 11:23:17'),
(2, 'Materiais de Construçao', 'Cemento, areia, ferro, etc.', 1, '2026-09-10 11:23:17'),
(3, 'Eletricos', 'Cabos, tomadas, interruptor, fios, conectores,', 1, '2026-09-10 11:23:17'),
(4, 'Encanamento', 'eletrodutos, conexoes, chuveros, torneira, canos', 1, '2026-09-10 11:23:17'),
(5, 'Tintas', 'tintas en materiais para pintar', 1, '2026-09-10 11:23:17'),
(6, 'Segurança', 'EPI', 1, '2026-09-10 11:23:17'),
(7, 'Escritorio', 'Materiais de escritorio', 1, '2026-09-10 11:23:17'),
(8, 'Informática', 'Equipos y accesorios de computación', 1, '2026-09-10 11:23:17'),
(9, 'Outros', 'Produtos sem clasificåo', 1, '2026-09-10 11:23:17'),
(10, 'Herramientas', 'Herramientas manuales y eléctricas', 1, '2026-09-19 00:36:30'),
(11, 'Materiales de Construcción', 'Cemento, arena, hierro, etc.', 1, '2026-09-19 00:36:30'),
(12, 'Eléctricos', 'Cables, tomacorrientes, interruptores', 1, '2026-09-19 00:36:30'),
(13, 'Plomería', 'Tuberías, conexiones, grifos', 1, '2026-09-19 00:36:30'),
(14, 'Pinturas', 'Pinturas, brochas, rodillos', 1, '2026-09-19 00:36:30'),
(15, 'Seguridad', 'Equipos de protección personal', 1, '2026-09-19 00:36:30'),
(16, 'Oficina', 'Materiales de oficina', 1, '2026-09-19 00:36:30'),
(17, 'Otros', 'Productos no clasificados', 1, '2026-09-19 00:36:30');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_items`
--

CREATE TABLE `historial_items` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `estado_anterior` varchar(50) DEFAULT NULL,
  `estado_nuevo` varchar(50) DEFAULT NULL,
  `fecha_anterior` date DEFAULT NULL,
  `fecha_nueva` date DEFAULT NULL,
  `cantidad_anterior` int(11) DEFAULT NULL,
  `cantidad_nueva` int(11) DEFAULT NULL,
  `cantidad_stock_anterior` int(11) DEFAULT NULL,
  `cantidad_stock_nueva` int(11) DEFAULT NULL,
  `cantidad_entregada_anterior` int(11) DEFAULT NULL,
  `cantidad_entregada_nueva` int(11) DEFAULT NULL,
  `costo_anterior` decimal(15,2) DEFAULT NULL,
  `costo_nuevo` decimal(15,2) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `fecha_cambio` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `historial_items`
--

INSERT INTO `historial_items` (`id`, `item_id`, `estado_anterior`, `estado_nuevo`, `fecha_anterior`, `fecha_nueva`, `cantidad_anterior`, `cantidad_nueva`, `cantidad_stock_anterior`, `cantidad_stock_nueva`, `cantidad_entregada_anterior`, `cantidad_entregada_nueva`, `costo_anterior`, `costo_nuevo`, `usuario_id`, `comentario`, `fecha_cambio`) VALUES
(1, 1, NULL, 'pendiente', NULL, '2026-09-14', NULL, 2, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'Item creado', '2026-09-11 11:29:26'),
(2, 2, NULL, 'pendiente', NULL, '2026-09-14', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'Item creado', '2026-09-11 11:34:13'),
(3, 3, NULL, 'pendiente', NULL, '2026-09-14', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'Item creado', '2026-09-14 10:56:14'),
(4, 4, NULL, 'pendiente', NULL, '2026-09-14', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'Item creado', '2026-09-14 10:56:14'),
(5, 5, NULL, 'pendiente', NULL, '2026-09-14', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'Item creado', '2026-09-14 10:56:15'),
(6, 6, NULL, 'pendiente', NULL, '2026-09-14', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'Item creado', '2026-09-14 10:56:15'),
(7, 7, NULL, 'pendiente', NULL, '2026-09-14', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'Item creado', '2026-09-14 13:01:08'),
(8, 8, NULL, 'pendiente', NULL, '2026-09-14', NULL, 2, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'Item creado', '2026-09-14 13:01:09'),
(9, 9, NULL, 'pendiente', NULL, '2026-09-14', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 16:08:20'),
(10, 10, NULL, 'pendiente', NULL, '2026-09-14', NULL, 3, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 16:09:14'),
(29, 29, NULL, 'pendiente', NULL, '2026-09-21', NULL, 50, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 17:08:33'),
(30, 30, NULL, 'pendiente', NULL, '2026-09-14', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 17:09:49'),
(31, 31, NULL, 'pendiente', NULL, '2026-09-21', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 17:09:49'),
(32, 32, NULL, 'pendiente', NULL, '2026-09-21', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 17:09:49'),
(33, 33, NULL, 'pendiente', NULL, '2026-09-21', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 17:09:49'),
(34, 34, NULL, 'pendiente', NULL, '2026-09-21', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 17:09:49'),
(35, 35, NULL, 'pendiente', NULL, '2026-09-21', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 17:09:49'),
(36, 36, NULL, 'pendiente', NULL, '2026-09-21', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 17:09:49'),
(37, 37, NULL, 'pendiente', NULL, '2026-09-21', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 17:09:49'),
(38, 38, NULL, 'pendiente', NULL, '2026-09-21', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 17:09:49'),
(39, 39, NULL, 'pendiente', NULL, '2026-09-21', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 17:09:49'),
(40, 40, NULL, 'pendiente', NULL, '2026-09-21', NULL, 8, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 17:09:49'),
(41, 41, NULL, 'pendiente', NULL, '2026-09-21', NULL, 2, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 17:09:49'),
(42, 42, NULL, 'pendiente', NULL, '2026-09-21', NULL, 6, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 17:09:49'),
(43, 43, NULL, 'pendiente', NULL, '2026-09-21', NULL, 10, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 17:09:49'),
(44, 44, NULL, 'pendiente', NULL, '2026-09-21', NULL, 10, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 17:09:49'),
(45, 45, NULL, 'pendiente', NULL, '2026-09-21', NULL, 10, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 17:09:49'),
(46, 46, NULL, 'pendiente', NULL, '2026-09-21', NULL, 10, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 17:09:49'),
(47, 47, NULL, 'pendiente', NULL, '2026-09-21', NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 17:09:49'),
(49, 10, 'pendiente', 'stock', '2026-09-14', '2026-09-14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, '', '2026-09-16 20:10:05'),
(50, 5, 'pendiente', 'stock', '2026-09-14', '2026-09-14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, '', '2026-09-16 20:46:26'),
(51, 4, 'pendiente', 'stock', '2026-09-14', '2026-09-14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, '', '2026-09-16 20:46:40'),
(52, 2, 'pendiente', 'stock', '2026-09-14', '2026-09-14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, '', '2026-09-16 20:46:47'),
(53, 1, 'pendiente', 'stock', '2026-09-14', '2026-09-14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, '', '2026-09-16 20:46:54'),
(54, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, 'Item editado: nombre_item: \'Conector IFM M12 montável Fêmea\' -> \'Conector Omron M12 montável Fêmea XS2C-D4S2\'', '2026-09-17 11:42:32'),
(55, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, 'Item editado: nombre_item: \'Conector IFM M12 montável Macho\' -> \'Conector Omron M12 montável Macho XS2G-D4S1\'', '2026-09-17 11:43:41'),
(56, 9, 'pendiente', 'comprado_llegar', '2026-09-14', '2026-09-14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, '[TRANSICIÓN FORZADA] ', '2026-09-17 11:47:31'),
(57, 8, 'pendiente', 'comprado_llegar', '2026-09-14', '2026-09-14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, '[TRANSICIÓN FORZADA] ir buscar', '2026-09-17 12:02:10'),
(58, 7, 'pendiente', 'comprado_llegar', '2026-09-14', '2026-09-14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, '[TRANSICIÓN FORZADA] ir buscar', '2026-09-17 12:02:25'),
(59, 6, 'pendiente', 'orçado', '2026-09-14', '2026-09-14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, '[TRANSICIÓN FORZADA] ', '2026-09-17 12:55:02'),
(60, 3, 'pendiente', 'orçado', '2026-09-14', '2026-09-14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, '[TRANSICIÓN FORZADA] ', '2026-09-17 12:55:11'),
(61, 37, 'pendiente', 'stock', '2026-09-21', '2026-09-21', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, '', '2026-09-17 12:55:48'),
(62, 38, 'pendiente', 'stock', '2026-09-21', '2026-09-21', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, '', '2026-09-17 12:55:56'),
(63, 6, 'orçado', 'comprado_llegar', '2026-09-14', '2026-09-14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, '', '2026-09-17 17:18:13'),
(64, 3, 'orçado', 'comprado_llegar', '2026-09-14', '2026-09-14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, '', '2026-09-17 17:18:23'),
(65, 9, 'comprado_llegar', 'comprado_llegar', '2026-09-14', '2026-09-14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 'até dia 25/09/2026', '2026-09-17 19:10:27'),
(66, 8, 'comprado_llegar', 'llego', '2026-09-14', '2026-09-14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, '', '2026-09-17 19:14:42'),
(67, 7, 'comprado_llegar', 'llego', '2026-09-14', '2026-09-14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, '', '2026-09-17 19:14:52'),
(68, 9, 'comprado_llegar', 'comprado_llegar', '2026-09-14', '2026-09-14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 'chega em 15 a 20 dias uteis', '2026-09-17 19:21:02'),
(69, 6, 'comprado_llegar', 'comprado_llegar', '2026-09-14', '2026-09-14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 'chega ate dia 25/09/2026', '2026-09-17 19:21:48'),
(70, 3, 'comprado_llegar', 'comprado_llegar', '2026-09-14', '2026-09-14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 'chega ate dia 25/09/2026', '2026-09-17 19:22:13'),
(71, 29, 'pendiente', 'stock', '2026-09-21', '2026-09-21', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, '', '2026-09-17 19:50:55'),
(72, 46, 'pendiente', 'stock', '2026-09-21', '2026-09-21', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, '', '2026-09-17 19:51:08'),
(73, 45, 'pendiente', 'stock', '2026-09-21', '2026-09-21', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, '', '2026-09-17 19:51:18'),
(74, 44, 'pendiente', 'stock', '2026-09-21', '2026-09-21', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, '', '2026-09-17 19:51:27'),
(75, 42, 'pendiente', 'stock', '2026-09-21', '2026-09-21', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, '', '2026-09-17 19:51:51'),
(76, 40, 'pendiente', 'stock', '2026-09-21', '2026-09-21', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, '', '2026-09-17 19:52:04'),
(77, 39, 'pendiente', 'stock', '2026-09-21', '2026-09-21', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, '', '2026-09-17 19:55:22'),
(78, 41, 'pendiente', 'stock', '2026-09-21', '2026-09-21', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, '', '2026-09-17 19:57:41'),
(79, 32, 'pendiente', 'stock', '2026-09-21', '2026-09-21', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, '', '2026-09-17 20:18:09'),
(80, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, 'Item editado: nombre_item: \'Tomada Steck de 32 A 3P+T FÊMEA\' -> \'Tomada Scame de 32 A 3P+T FÊMEA (318.3246)\' | unidad_medida: \'UNIDAD\' -> \'\'', '2026-09-17 20:40:38'),
(81, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, 'Item editado: nombre_item: \'Tomada Steck de 32 A 3P+T Macho\' -> \'Tomada Scame de 32 A 3P+T Macho (218.3236)\' | unidad_medida: \'UNIDAD\' -> \'\'', '2026-09-17 20:41:00'),
(82, 30, 'pendiente', 'cotacion', '2026-09-14', '2026-09-14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 'esperando a compra da fonte, para verificar tamanho da mesma, e ver se a que tem em estoque atende. ', '2026-09-18 14:06:17'),
(83, 31, 'pendiente', 'stock', '2026-09-21', '2026-09-21', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, '', '2026-09-18 16:38:42'),
(84, 43, 'pendiente', 'stock', '2026-09-21', '2026-09-21', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, '', '2026-09-18 16:39:03'),
(85, 47, 'pendiente', 'stock', '2026-09-21', '2026-09-21', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, '', '2026-09-18 16:39:15'),
(86, 10, 'stock', 'separado', '2026-09-14', '2026-09-14', 3, 3, 0, 3, 0, 3, NULL, NULL, 4, '', '2026-09-18 19:37:03'),
(87, 8, 'llego', 'separado', '2026-09-14', '2026-09-14', 2, 2, 0, 2, 0, 2, NULL, NULL, 4, '', '2026-09-18 19:37:14'),
(88, 7, 'llego', 'separado', '2026-09-14', '2026-09-14', 1, 1, 0, 1, 0, 1, NULL, NULL, 4, '', '2026-09-18 19:37:24'),
(89, 5, 'stock', 'separado', '2026-09-14', '2026-09-14', 2, 2, 0, 2, 0, 2, NULL, NULL, 4, '', '2026-09-18 19:37:36'),
(90, 4, 'stock', 'separado', '2026-09-14', '2026-09-14', 2, 2, 0, 2, 0, 2, NULL, NULL, 4, '', '2026-09-18 19:37:46'),
(91, 2, 'stock', 'separado', '2026-09-14', '2026-09-14', 1, 1, 0, 1, 0, 1, NULL, NULL, 4, '', '2026-09-18 19:37:58'),
(92, 1, 'stock', 'separado', '2026-09-14', '2026-09-14', 2, 2, 0, 2, 0, 2, NULL, NULL, 4, '', '2026-09-18 19:38:04'),
(93, 31, 'stock', 'separado', '2026-09-21', '2026-09-21', 1, 1, 0, 1, 0, 1, NULL, NULL, 4, '', '2026-09-18 20:41:21'),
(95, 32, 'stock', 'separado', '2026-09-21', '2026-09-21', 1, 1, 0, 1, 0, 1, NULL, NULL, 4, '', '2026-09-21 13:23:18'),
(96, 37, 'stock', 'separado', '2026-09-21', '2026-09-21', 1, 1, 0, 1, 0, 1, NULL, NULL, 4, '', '2026-09-21 13:23:27'),
(97, 38, 'stock', 'separado', '2026-09-21', '2026-09-21', 1, 1, 0, 1, 0, 1, NULL, NULL, 4, '', '2026-09-21 13:23:35'),
(98, 39, 'stock', 'separado', '2026-09-21', '2026-09-21', 1, 1, 0, 1, 0, 1, NULL, NULL, 4, '', '2026-09-21 13:23:42'),
(99, 40, 'stock', 'separado', '2026-09-21', '2026-09-21', 8, 8, 0, 8, 0, 8, NULL, NULL, 4, '', '2026-09-21 13:23:51'),
(100, 41, 'stock', 'separado', '2026-09-21', '2026-09-21', 2, 2, 0, 2, 0, 2, NULL, NULL, 4, '', '2026-09-21 13:23:58'),
(101, 42, 'stock', 'separado', '2026-09-21', '2026-09-21', 6, 6, 0, 6, 0, 6, NULL, NULL, 4, '', '2026-09-21 13:24:06'),
(102, 43, 'stock', 'separado', '2026-09-21', '2026-09-21', 10, 10, 0, 10, 0, 10, NULL, NULL, 4, '', '2026-09-21 13:24:47'),
(103, 44, 'stock', 'separado', '2026-09-21', '2026-09-21', 10, 10, 0, 10, 0, 10, NULL, NULL, 4, '', '2026-09-21 13:24:55'),
(104, 45, 'stock', 'separado', '2026-09-21', '2026-09-21', 10, 10, 0, 10, 0, 10, NULL, NULL, 4, '', '2026-09-21 13:25:01'),
(105, 46, 'stock', 'separado', '2026-09-21', '2026-09-21', 10, 10, 0, 10, 0, 10, NULL, NULL, 4, '', '2026-09-21 13:25:09'),
(106, 47, 'stock', 'separado', '2026-09-21', '2026-09-21', 5, 5, 0, 5, 0, 5, NULL, NULL, 4, '', '2026-09-21 13:25:16'),
(107, 29, 'stock', 'separado', '2026-09-21', '2026-09-21', 50, 50, 0, 50, 0, 50, NULL, NULL, 4, '', '2026-09-21 13:25:22'),
(108, 34, 'pendiente', 'recibido', '2026-09-21', '2026-09-21', 1, 1, 0, 1, 0, 1, NULL, NULL, 1, '[TRANSICIÓN FORZADA] tem em estoque na Frisia Informo Ademar dia 21/sep/2026', '2026-09-21 17:56:46'),
(109, 35, 'pendiente', 'stock', '2026-09-21', '2026-09-21', 1, 1, 0, 1, 0, 1, NULL, NULL, 1, '', '2026-09-22 10:25:24'),
(110, 35, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'Item editado: nombre_item: \'Relé de Interface DIN com base (Contato SPDT / Reversor)\' -> \'Relé de Interface DIN com base 24V (Contato SPDT / Reversor)\'', '2026-09-22 10:27:05'),
(111, 33, 'pendiente', 'pendiente_pago', '2026-09-21', '2026-09-21', 1, 1, 0, 1, 0, 1, NULL, NULL, 3, 'Esperando Priscila pedir', '2026-09-22 10:40:31'),
(112, 33, 'pendiente_pago', 'pendiente_pago', '2026-09-21', '2026-09-21', 1, 1, 1, 1, 1, 1, NULL, NULL, 3, 'Esperando Priscila comprar', '2026-09-22 10:42:18'),
(113, 36, 'pendiente', 'pendiente_pago', '2026-09-21', '2026-09-21', 1, 1, 0, 1, 0, 1, NULL, NULL, 3, 'Esperando Priscila comprar', '2026-09-22 11:05:49');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_proyectos`
--

CREATE TABLE `historial_proyectos` (
  `id` int(11) NOT NULL,
  `proyecto_id` int(11) NOT NULL,
  `estado_anterior` varchar(50) DEFAULT NULL,
  `estado_nuevo` varchar(50) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `fecha_cambio` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `historial_proyectos`
--

INSERT INTO `historial_proyectos` (`id`, `proyecto_id`, `estado_anterior`, `estado_nuevo`, `usuario_id`, `comentario`, `fecha_cambio`) VALUES
(1, 2, NULL, 'solicitado', 1, 'Proyecto creado', '2026-09-14 16:51:50'),
(2, 2, 'solicitado', 'comprando_materiales', 1, 'Cambio de estado en edición', '2026-09-14 16:53:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `items_proyecto`
--

CREATE TABLE `items_proyecto` (
  `id` int(11) NOT NULL,
  `proyecto_id` int(11) NOT NULL,
  `producto_id` int(11) DEFAULT NULL,
  `nombre_item` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `cantidad` int(11) NOT NULL,
  `cantidad_stock` int(11) DEFAULT 0,
  `cantidad_entregada` int(11) DEFAULT 0,
  `cantidad_recibida` int(11) DEFAULT 0,
  `costo_unitario` decimal(15,2) DEFAULT NULL,
  `moneda` varchar(3) DEFAULT 'USD',
  `proveedor` varchar(200) DEFAULT NULL,
  `numero_factura` varchar(100) DEFAULT NULL,
  `fecha_compra` date DEFAULT NULL,
  `unidad_medida` varchar(50) DEFAULT NULL,
  `especificaciones` text DEFAULT NULL,
  `estado` enum('solicitado','pendiente','stock','separado','cotacion','orçado','pendiente_pago','comprado_llegar','llego','entregado','recibido') DEFAULT 'solicitado',
  `entregado_por` int(11) DEFAULT NULL,
  `fecha_entrega` datetime DEFAULT NULL,
  `recibido_por` int(11) DEFAULT NULL,
  `fecha_recepcion` datetime DEFAULT NULL,
  `fecha_requerida` date NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `items_proyecto`
--

INSERT INTO `items_proyecto` (`id`, `proyecto_id`, `producto_id`, `nombre_item`, `descripcion`, `cantidad`, `cantidad_stock`, `cantidad_entregada`, `cantidad_recibida`, `costo_unitario`, `moneda`, `proveedor`, `numero_factura`, `fecha_compra`, `unidad_medida`, `especificaciones`, `estado`, `entregado_por`, `fecha_entrega`, `recibido_por`, `fecha_recepcion`, `fecha_requerida`, `fecha_creacion`) VALUES
(1, 1, 1, 'Tomada Scame de 32 A 3P+T Macho (218.3236)', NULL, 2, 2, 2, 0, NULL, 'USD', NULL, NULL, NULL, '', '', 'separado', NULL, NULL, NULL, NULL, '2026-09-14', '2026-09-11 11:29:26'),
(2, 1, 2, 'Tomada Scame de 32 A 3P+T FÊMEA (318.3246)', NULL, 1, 1, 1, 0, NULL, 'USD', NULL, NULL, NULL, '', '', 'separado', NULL, NULL, NULL, NULL, '2026-09-14', '2026-09-11 11:34:13'),
(3, 1, 4, 'Conector M16 IP65 12 Pinos Fêmea', NULL, 1, 0, 0, 0, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'comprado_llegar', NULL, NULL, NULL, NULL, '2026-09-14', '2026-09-14 10:56:14'),
(4, 1, 5, 'Conector Omron M12 montável Macho XS2G-D4S1', NULL, 2, 2, 2, 0, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'separado', NULL, NULL, NULL, NULL, '2026-09-14', '2026-09-14 10:56:14'),
(5, 1, 6, 'Conector Omron M12 montável Fêmea XS2C-D4S2', NULL, 2, 2, 2, 0, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'separado', NULL, NULL, NULL, NULL, '2026-09-14', '2026-09-14 10:56:15'),
(6, 1, 3, 'Conector M16 IP65 12 Pinos Macho 5A', NULL, 1, 0, 0, 0, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'comprado_llegar', NULL, NULL, NULL, NULL, '2026-09-14', '2026-09-14 10:56:15'),
(7, 1, 8, 'Conexão Festo 10MM / SMC FÊMEA', NULL, 1, 1, 1, 0, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'separado', NULL, NULL, NULL, NULL, '2026-09-14', '2026-09-14 13:01:08'),
(8, 1, 7, 'Conexão Festo 10 mm / SMC MACHO', NULL, 2, 2, 2, 0, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'separado', NULL, NULL, NULL, NULL, '2026-09-14', '2026-09-14 13:01:09'),
(9, 1, 9, 'Painel 350x280x175mm', NULL, 1, 0, 0, 0, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'comprado_llegar', NULL, NULL, NULL, NULL, '2026-09-14', '2026-09-14 16:08:20'),
(10, 1, 10, 'Cabo PP 4 X 2,5 mm²', NULL, 3, 3, 3, 0, NULL, 'USD', NULL, NULL, NULL, 'm', '', 'separado', NULL, NULL, NULL, NULL, '2026-09-14', '2026-09-14 16:09:14'),
(29, 2, NULL, 'Luvas / Marcadores', NULL, 50, 50, 50, 0, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'separado', NULL, NULL, NULL, NULL, '2026-09-21', '2026-09-14 17:08:33'),
(30, 2, 11, 'Caixa termoplástica com tampa (250 x 200 x 130 mm)', NULL, 1, 0, 0, 0, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'cotacion', NULL, NULL, NULL, NULL, '2026-09-14', '2026-09-14 17:09:49'),
(31, 2, 12, 'Trecho de trilho DIN 35mm perfurado', NULL, 1, 1, 1, 0, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'separado', NULL, NULL, NULL, NULL, '2026-09-21', '2026-09-14 17:09:49'),
(32, 2, NULL, 'Disjuntor DIN Unipolar/Bipolar (C6A)', NULL, 1, 1, 1, 0, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'separado', NULL, NULL, NULL, NULL, '2026-09-21', '2026-09-14 17:09:49'),
(33, 2, NULL, 'Fonte Chaveada DIN Entrada: 100-240VAC / Saída: 24VDC - 2,5A', NULL, 1, 1, 1, 0, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'pendiente_pago', NULL, NULL, NULL, NULL, '2026-09-21', '2026-09-14 17:09:49'),
(34, 2, NULL, 'Controlador de Temperatura Spirax Sarco SX-UNI', NULL, 1, 1, 1, 0, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'recibido', NULL, NULL, NULL, NULL, '2026-09-21', '2026-09-14 17:09:49'),
(35, 2, NULL, 'Relé de Interface DIN com base 24V (Contato SPDT / Reversor)', NULL, 1, 1, 1, 0, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'stock', NULL, NULL, NULL, NULL, '2026-09-21', '2026-09-14 17:09:49'),
(36, 2, NULL, 'Sensor de temperatura ambiente (ex: PT100 haste/cabeçote)', NULL, 1, 1, 1, 0, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'pendiente_pago', NULL, NULL, NULL, NULL, '2026-09-21', '2026-09-14 17:09:49'),
(37, 2, 5, 'Conector IFM M12 montável Macho', NULL, 1, 1, 1, 0, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'separado', NULL, NULL, NULL, NULL, '2026-09-21', '2026-09-14 17:09:49'),
(38, 2, 6, 'Conector IFM M12 montável Fêmea', NULL, 1, 1, 1, 0, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'separado', NULL, NULL, NULL, NULL, '2026-09-21', '2026-09-14 17:09:49'),
(39, 2, NULL, 'Prensa-cabos termoplástico PG9 / PG11 com contra-porca', NULL, 1, 1, 1, 0, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'separado', NULL, NULL, NULL, NULL, '2026-09-21', '2026-09-14 17:09:49'),
(40, 2, NULL, 'Bornes de passagem tipo KRG / SAK 1,5 mm', NULL, 8, 8, 8, 0, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'separado', NULL, NULL, NULL, NULL, '2026-09-21', '2026-09-14 17:09:49'),
(41, 2, NULL, 'Poste final / Trava para trilho DIN', NULL, 2, 2, 2, 0, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'separado', NULL, NULL, NULL, NULL, '2026-09-21', '2026-09-14 17:09:49'),
(42, 2, NULL, 'parafuso rosca soberba', NULL, 6, 6, 6, 0, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'separado', NULL, NULL, NULL, NULL, '2026-09-21', '2026-09-14 17:09:49'),
(43, 2, NULL, 'Fio flexível 1,5 mm² Preto', NULL, 10, 10, 10, 0, NULL, 'USD', NULL, NULL, NULL, 'm', '', 'separado', NULL, NULL, NULL, NULL, '2026-09-21', '2026-09-14 17:09:49'),
(44, 2, NULL, 'Fio flexível 1,5 mm² Azul Claro', NULL, 10, 10, 10, 0, NULL, 'USD', NULL, NULL, NULL, 'm', '', 'separado', NULL, NULL, NULL, NULL, '2026-09-21', '2026-09-14 17:09:49'),
(45, 2, NULL, 'Fio flexível 1 mm² azul Oscuro', NULL, 10, 10, 10, 0, NULL, 'USD', NULL, NULL, NULL, 'm', '', 'separado', NULL, NULL, NULL, NULL, '2026-09-21', '2026-09-14 17:09:49'),
(46, 2, NULL, 'Fio flexível 1 mm² CINZA', NULL, 10, 10, 10, 0, NULL, 'USD', NULL, NULL, NULL, 'm', '', 'separado', NULL, NULL, NULL, NULL, '2026-09-21', '2026-09-14 17:09:49'),
(47, 2, NULL, 'Cabo PP de comando blindado/manga (ex: 3x0,75mm²)', NULL, 5, 5, 5, 0, NULL, 'USD', NULL, NULL, NULL, 'm', '', 'separado', NULL, NULL, NULL, NULL, '2026-09-21', '2026-09-14 17:09:49');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones_leidas`
--

CREATE TABLE `notificaciones_leidas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo` enum('item_modificado','item_agregado','item_proximo','item_vencido','estado_cambiado','item_separado') NOT NULL,
  `referencia_id` int(11) NOT NULL,
  `fecha_lectura` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `notificaciones_leidas`
--

INSERT INTO `notificaciones_leidas` (`id`, `usuario_id`, `tipo`, `referencia_id`, `fecha_lectura`) VALUES
(1, 1, 'item_vencido', 6, '2026-09-16 12:51:38'),
(2, 1, 'item_vencido', 1, '2026-09-16 12:51:39'),
(3, 1, 'item_vencido', 9, '2026-09-16 12:51:40'),
(4, 1, 'item_vencido', 4, '2026-09-16 12:51:41'),
(5, 1, 'item_vencido', 7, '2026-09-16 12:51:41'),
(6, 1, 'item_vencido', 2, '2026-09-16 12:51:41'),
(7, 1, 'item_vencido', 10, '2026-09-16 12:51:42'),
(8, 1, 'item_vencido', 5, '2026-09-16 12:51:42'),
(9, 1, 'item_vencido', 8, '2026-09-16 12:51:42'),
(10, 1, 'item_vencido', 3, '2026-09-16 12:51:43'),
(11, 1, 'item_vencido', 30, '2026-09-16 12:51:43'),
(12, 1, 'item_proximo', 35, '2026-09-16 12:51:43'),
(13, 1, 'item_proximo', 43, '2026-09-16 12:51:45'),
(14, 1, 'item_proximo', 46, '2026-09-16 12:51:45'),
(15, 1, 'item_proximo', 29, '2026-09-16 12:51:45'),
(16, 1, 'item_proximo', 38, '2026-09-16 12:51:46'),
(17, 1, 'item_proximo', 33, '2026-09-16 12:51:46'),
(18, 1, 'item_proximo', 41, '2026-09-16 12:51:46'),
(19, 1, 'item_proximo', 44, '2026-09-16 12:51:46'),
(20, 1, 'item_proximo', 36, '2026-09-16 12:51:47'),
(21, 1, 'item_proximo', 47, '2026-09-16 12:51:47'),
(22, 1, 'item_proximo', 31, '2026-09-16 12:51:48'),
(23, 1, 'item_proximo', 39, '2026-09-16 12:51:48'),
(24, 1, 'item_proximo', 34, '2026-09-16 12:51:49'),
(25, 1, 'item_proximo', 42, '2026-09-16 12:51:49'),
(26, 1, 'item_proximo', 45, '2026-09-16 12:51:50'),
(27, 1, 'item_proximo', 37, '2026-09-16 12:51:50'),
(28, 1, 'item_proximo', 32, '2026-09-16 12:51:50'),
(29, 1, 'item_proximo', 40, '2026-09-16 12:51:50'),
(30, 1, 'item_agregado', 32, '2026-09-16 12:51:50'),
(31, 1, 'item_agregado', 31, '2026-09-16 12:51:52'),
(32, 1, 'item_agregado', 47, '2026-09-16 12:51:53'),
(33, 1, 'item_agregado', 46, '2026-09-16 12:51:53'),
(34, 1, 'item_agregado', 30, '2026-09-16 12:51:53'),
(35, 1, 'item_agregado', 45, '2026-09-16 12:51:53'),
(36, 1, 'item_agregado', 44, '2026-09-16 12:51:54'),
(37, 1, 'item_agregado', 43, '2026-09-16 12:51:54'),
(38, 1, 'item_agregado', 42, '2026-09-16 12:51:54'),
(39, 1, 'item_agregado', 41, '2026-09-16 12:51:54'),
(40, 1, 'item_agregado', 40, '2026-09-16 12:51:54'),
(41, 1, 'item_agregado', 39, '2026-09-16 12:51:54'),
(42, 1, 'item_agregado', 38, '2026-09-16 12:51:55'),
(43, 1, 'item_agregado', 37, '2026-09-16 12:51:55'),
(44, 1, 'item_agregado', 36, '2026-09-16 12:51:55'),
(45, 1, 'item_agregado', 35, '2026-09-16 12:51:55'),
(46, 1, 'item_agregado', 34, '2026-09-16 12:51:55'),
(47, 1, 'item_agregado', 33, '2026-09-16 12:51:56'),
(48, 1, 'item_agregado', 29, '2026-09-16 12:51:59'),
(49, 1, 'item_agregado', 10, '2026-09-16 12:52:00'),
(50, 8, 'item_vencido', 6, '2026-09-17 12:22:46'),
(51, 8, 'item_vencido', 9, '2026-09-17 12:22:46'),
(52, 8, 'item_vencido', 7, '2026-09-17 12:22:46'),
(53, 8, 'item_vencido', 30, '2026-09-17 12:22:46'),
(54, 8, 'item_vencido', 3, '2026-09-17 12:22:46'),
(55, 8, 'item_vencido', 8, '2026-09-17 12:22:46'),
(56, 8, 'item_proximo', 31, '2026-09-17 12:22:46'),
(57, 8, 'item_proximo', 39, '2026-09-17 12:22:46'),
(58, 8, 'item_proximo', 47, '2026-09-17 12:22:46'),
(59, 8, 'item_proximo', 34, '2026-09-17 12:22:46'),
(60, 8, 'item_proximo', 42, '2026-09-17 12:22:46'),
(61, 8, 'item_proximo', 37, '2026-09-17 12:22:46'),
(62, 8, 'item_proximo', 45, '2026-09-17 12:22:46'),
(63, 8, 'item_proximo', 32, '2026-09-17 12:22:46'),
(64, 8, 'item_proximo', 40, '2026-09-17 12:22:46'),
(65, 8, 'item_proximo', 35, '2026-09-17 12:22:46'),
(66, 8, 'item_proximo', 43, '2026-09-17 12:22:46'),
(67, 8, 'item_proximo', 29, '2026-09-17 12:22:46'),
(68, 8, 'item_proximo', 38, '2026-09-17 12:22:46'),
(69, 8, 'item_proximo', 46, '2026-09-17 12:22:46'),
(70, 8, 'item_proximo', 33, '2026-09-17 12:22:46'),
(71, 8, 'item_proximo', 41, '2026-09-17 12:22:46'),
(72, 8, 'item_proximo', 36, '2026-09-17 12:22:46'),
(73, 8, 'item_proximo', 44, '2026-09-17 12:22:46'),
(74, 8, 'item_modificado', 58, '2026-09-17 12:22:46'),
(75, 8, 'item_modificado', 57, '2026-09-17 12:22:46'),
(76, 8, 'item_modificado', 56, '2026-09-17 12:22:46'),
(77, 8, 'item_modificado', 55, '2026-09-17 12:22:46'),
(78, 8, 'item_modificado', 54, '2026-09-17 12:22:46'),
(79, 8, 'item_modificado', 53, '2026-09-17 12:22:46'),
(80, 8, 'item_modificado', 52, '2026-09-17 12:22:46'),
(81, 8, 'item_modificado', 51, '2026-09-17 12:22:46'),
(82, 8, 'item_modificado', 50, '2026-09-17 12:22:46'),
(83, 8, 'item_modificado', 49, '2026-09-17 12:22:46'),
(84, 8, 'item_modificado', 30, '2026-09-17 12:22:46'),
(85, 8, 'item_modificado', 31, '2026-09-17 12:22:46'),
(86, 8, 'item_modificado', 32, '2026-09-17 12:22:46'),
(87, 8, 'item_modificado', 33, '2026-09-17 12:22:46'),
(88, 8, 'item_modificado', 34, '2026-09-17 12:22:46'),
(89, 8, 'item_modificado', 35, '2026-09-17 12:22:46'),
(90, 8, 'item_modificado', 36, '2026-09-17 12:22:46'),
(91, 8, 'item_modificado', 37, '2026-09-17 12:22:46'),
(92, 8, 'item_modificado', 38, '2026-09-17 12:22:46'),
(93, 8, 'item_modificado', 39, '2026-09-17 12:22:46'),
(94, 8, 'item_modificado', 40, '2026-09-17 12:22:46'),
(95, 8, 'item_modificado', 41, '2026-09-17 12:22:46'),
(96, 8, 'item_modificado', 42, '2026-09-17 12:22:46'),
(97, 8, 'item_modificado', 43, '2026-09-17 12:22:46'),
(98, 8, 'item_modificado', 44, '2026-09-17 12:22:46'),
(99, 8, 'item_modificado', 45, '2026-09-17 12:22:46'),
(100, 8, 'item_modificado', 46, '2026-09-17 12:22:46'),
(101, 8, 'item_modificado', 47, '2026-09-17 12:22:46'),
(102, 8, 'item_modificado', 29, '2026-09-17 12:22:46'),
(103, 8, 'item_modificado', 10, '2026-09-17 12:22:46'),
(104, 8, 'item_agregado', 42, '2026-09-17 12:22:46'),
(105, 8, 'item_agregado', 41, '2026-09-17 12:22:46'),
(106, 8, 'item_agregado', 40, '2026-09-17 12:22:47'),
(107, 8, 'item_agregado', 39, '2026-09-17 12:22:47'),
(108, 8, 'item_agregado', 38, '2026-09-17 12:22:47'),
(109, 8, 'item_agregado', 37, '2026-09-17 12:22:47'),
(110, 8, 'item_agregado', 36, '2026-09-17 12:22:47'),
(111, 8, 'item_agregado', 35, '2026-09-17 12:22:47'),
(112, 8, 'item_agregado', 34, '2026-09-17 12:22:47'),
(113, 8, 'item_agregado', 33, '2026-09-17 12:22:47'),
(114, 8, 'item_agregado', 32, '2026-09-17 12:22:47'),
(115, 8, 'item_agregado', 31, '2026-09-17 12:22:47'),
(116, 8, 'item_agregado', 30, '2026-09-17 12:22:47'),
(117, 8, 'item_agregado', 47, '2026-09-17 12:22:47'),
(118, 8, 'item_agregado', 46, '2026-09-17 12:22:47'),
(119, 8, 'item_agregado', 45, '2026-09-17 12:22:47'),
(120, 8, 'item_agregado', 44, '2026-09-17 12:22:47'),
(121, 8, 'item_agregado', 43, '2026-09-17 12:22:47'),
(122, 8, 'item_agregado', 29, '2026-09-17 12:22:47'),
(123, 8, 'item_agregado', 10, '2026-09-17 12:22:47'),
(124, 3, 'item_vencido', 8, '2026-09-17 14:20:52'),
(125, 3, 'item_vencido', 6, '2026-09-17 14:20:52'),
(126, 3, 'item_vencido', 9, '2026-09-17 14:20:52'),
(127, 3, 'item_vencido', 7, '2026-09-17 14:20:52'),
(128, 3, 'item_vencido', 30, '2026-09-17 14:20:52'),
(129, 3, 'item_vencido', 3, '2026-09-17 14:20:52'),
(130, 3, 'item_proximo', 33, '2026-09-17 14:20:52'),
(131, 3, 'item_proximo', 43, '2026-09-17 14:20:52'),
(132, 3, 'item_proximo', 36, '2026-09-17 14:20:52'),
(133, 3, 'item_proximo', 46, '2026-09-17 14:20:52'),
(134, 3, 'item_proximo', 31, '2026-09-17 14:20:52'),
(135, 3, 'item_proximo', 41, '2026-09-17 14:20:52'),
(136, 3, 'item_proximo', 34, '2026-09-17 14:20:52'),
(137, 3, 'item_proximo', 44, '2026-09-17 14:20:52'),
(138, 3, 'item_proximo', 39, '2026-09-17 14:20:52'),
(139, 3, 'item_proximo', 47, '2026-09-17 14:20:52'),
(140, 3, 'item_proximo', 32, '2026-09-17 14:20:52'),
(141, 3, 'item_proximo', 42, '2026-09-17 14:20:52'),
(142, 3, 'item_proximo', 35, '2026-09-17 14:20:52'),
(143, 3, 'item_proximo', 45, '2026-09-17 14:20:52'),
(144, 3, 'item_proximo', 29, '2026-09-17 14:20:52'),
(145, 3, 'item_proximo', 40, '2026-09-17 14:20:52'),
(146, 3, 'item_modificado', 55, '2026-09-17 14:20:52'),
(147, 3, 'item_modificado', 54, '2026-09-17 14:20:52'),
(148, 3, 'item_modificado', 53, '2026-09-17 14:20:52'),
(149, 3, 'item_modificado', 52, '2026-09-17 14:20:52'),
(150, 3, 'item_modificado', 51, '2026-09-17 14:20:52'),
(151, 3, 'item_modificado', 50, '2026-09-17 14:20:52'),
(152, 3, 'item_modificado', 49, '2026-09-17 14:20:52'),
(153, 3, 'item_modificado', 30, '2026-09-17 14:20:52'),
(154, 3, 'item_modificado', 31, '2026-09-17 14:20:52'),
(155, 3, 'item_modificado', 32, '2026-09-17 14:20:52'),
(156, 3, 'item_modificado', 33, '2026-09-17 14:20:52'),
(157, 3, 'item_modificado', 34, '2026-09-17 14:20:53'),
(158, 3, 'item_modificado', 35, '2026-09-17 14:20:53'),
(159, 3, 'item_modificado', 36, '2026-09-17 14:20:53'),
(160, 3, 'item_modificado', 37, '2026-09-17 14:20:53'),
(161, 3, 'item_modificado', 38, '2026-09-17 14:20:53'),
(162, 3, 'item_modificado', 39, '2026-09-17 14:20:53'),
(163, 3, 'item_modificado', 40, '2026-09-17 14:20:53'),
(164, 3, 'item_modificado', 41, '2026-09-17 14:20:53'),
(165, 3, 'item_modificado', 42, '2026-09-17 14:20:53'),
(166, 3, 'item_modificado', 43, '2026-09-17 14:20:53'),
(167, 3, 'item_modificado', 44, '2026-09-17 14:20:53'),
(168, 3, 'item_modificado', 45, '2026-09-17 14:20:53'),
(169, 3, 'item_modificado', 46, '2026-09-17 14:20:53'),
(170, 3, 'item_modificado', 47, '2026-09-17 14:20:53'),
(171, 3, 'item_modificado', 29, '2026-09-17 14:20:53'),
(172, 3, 'item_modificado', 10, '2026-09-17 14:20:53'),
(173, 3, 'item_modificado', 9, '2026-09-17 14:20:53'),
(174, 3, 'item_modificado', 8, '2026-09-17 14:20:53'),
(175, 3, 'item_modificado', 7, '2026-09-17 14:20:53'),
(176, 3, 'item_agregado', 47, '2026-09-17 14:20:53'),
(177, 3, 'item_agregado', 46, '2026-09-17 14:20:53'),
(178, 3, 'item_agregado', 45, '2026-09-17 14:20:53'),
(179, 3, 'item_agregado', 44, '2026-09-17 14:20:53'),
(180, 3, 'item_agregado', 43, '2026-09-17 14:20:53'),
(181, 3, 'item_agregado', 42, '2026-09-17 14:20:53'),
(182, 3, 'item_agregado', 41, '2026-09-17 14:20:53'),
(183, 3, 'item_agregado', 40, '2026-09-17 14:20:53'),
(184, 3, 'item_agregado', 39, '2026-09-17 14:20:53'),
(185, 3, 'item_agregado', 38, '2026-09-17 14:20:53'),
(186, 3, 'item_agregado', 37, '2026-09-17 14:20:53'),
(187, 3, 'item_agregado', 36, '2026-09-17 14:20:53'),
(188, 3, 'item_agregado', 35, '2026-09-17 14:20:53'),
(189, 3, 'item_agregado', 34, '2026-09-17 14:20:53'),
(190, 3, 'item_agregado', 33, '2026-09-17 14:20:53'),
(191, 3, 'item_agregado', 32, '2026-09-17 14:20:53'),
(192, 3, 'item_agregado', 31, '2026-09-17 14:20:53'),
(193, 3, 'item_agregado', 30, '2026-09-17 14:20:53'),
(194, 3, 'item_agregado', 29, '2026-09-17 14:20:53'),
(195, 3, 'item_agregado', 10, '2026-09-17 14:20:53'),
(196, 1, 'item_modificado', 62, '2026-09-17 15:29:06'),
(197, 1, 'item_modificado', 61, '2026-09-17 15:29:06'),
(198, 1, 'item_modificado', 60, '2026-09-17 15:29:07'),
(199, 1, 'item_modificado', 59, '2026-09-17 15:29:07'),
(200, 1, 'item_modificado', 58, '2026-09-17 15:29:08'),
(201, 1, 'item_modificado', 57, '2026-09-17 15:29:08'),
(202, 1, 'item_modificado', 56, '2026-09-17 15:29:08'),
(203, 1, 'item_modificado', 55, '2026-09-17 15:29:08'),
(204, 1, 'item_modificado', 54, '2026-09-17 15:29:09'),
(205, 1, 'item_modificado', 53, '2026-09-17 15:29:09'),
(206, 1, 'item_modificado', 52, '2026-09-17 15:29:09'),
(207, 1, 'item_modificado', 51, '2026-09-17 15:29:15'),
(208, 1, 'item_modificado', 50, '2026-09-17 15:29:15'),
(209, 1, 'item_modificado', 49, '2026-09-17 15:29:16'),
(210, 1, 'item_modificado', 70, '2026-09-17 19:47:24'),
(211, 1, 'item_modificado', 69, '2026-09-17 19:47:24'),
(212, 1, 'item_modificado', 68, '2026-09-17 19:47:25'),
(213, 1, 'item_modificado', 67, '2026-09-17 19:47:25'),
(214, 1, 'item_modificado', 66, '2026-09-17 19:47:26'),
(215, 1, 'item_modificado', 65, '2026-09-17 19:47:26'),
(216, 1, 'item_modificado', 64, '2026-09-17 19:47:27'),
(217, 1, 'item_modificado', 63, '2026-09-17 19:47:29'),
(218, 1, 'item_modificado', 79, '2026-09-17 20:21:10'),
(219, 1, 'item_modificado', 78, '2026-09-17 20:21:11'),
(220, 1, 'item_modificado', 77, '2026-09-17 20:21:11'),
(221, 1, 'item_modificado', 76, '2026-09-17 20:21:11'),
(222, 1, 'item_modificado', 75, '2026-09-17 20:21:11'),
(223, 1, 'item_modificado', 74, '2026-09-17 20:21:12'),
(226, 3, 'item_modificado', 81, '2026-09-18 12:25:38'),
(227, 3, 'item_modificado', 80, '2026-09-18 12:25:38'),
(228, 3, 'item_modificado', 79, '2026-09-18 12:25:38'),
(229, 3, 'item_modificado', 78, '2026-09-18 12:25:38'),
(230, 3, 'item_modificado', 77, '2026-09-18 12:25:38'),
(231, 3, 'item_modificado', 76, '2026-09-18 12:25:38'),
(232, 3, 'item_modificado', 75, '2026-09-18 12:25:38'),
(233, 3, 'item_modificado', 74, '2026-09-18 12:25:38'),
(234, 3, 'item_modificado', 73, '2026-09-18 12:25:38'),
(235, 3, 'item_modificado', 72, '2026-09-18 12:25:38'),
(236, 3, 'item_modificado', 71, '2026-09-18 12:25:38'),
(237, 3, 'item_modificado', 67, '2026-09-18 12:25:38'),
(238, 3, 'item_modificado', 66, '2026-09-18 12:25:38'),
(239, 3, 'item_modificado', 85, '2026-09-18 18:13:15'),
(240, 3, 'item_modificado', 84, '2026-09-18 18:13:15'),
(241, 3, 'item_modificado', 83, '2026-09-18 18:13:15'),
(242, 8, 'item_modificado', 92, '2026-09-18 19:42:53'),
(243, 8, 'item_modificado', 91, '2026-09-18 19:42:53'),
(244, 8, 'item_modificado', 90, '2026-09-18 19:42:53'),
(245, 8, 'item_modificado', 89, '2026-09-18 19:42:53'),
(246, 8, 'item_modificado', 88, '2026-09-18 19:42:53'),
(247, 8, 'item_modificado', 87, '2026-09-18 19:42:53'),
(248, 8, 'item_modificado', 86, '2026-09-18 19:42:53'),
(249, 8, 'item_modificado', 85, '2026-09-18 19:42:53'),
(250, 8, 'item_modificado', 84, '2026-09-18 19:42:53'),
(251, 8, 'item_modificado', 83, '2026-09-18 19:42:53'),
(252, 8, 'item_modificado', 82, '2026-09-18 19:42:53'),
(253, 8, 'item_modificado', 81, '2026-09-18 19:42:53'),
(254, 8, 'item_modificado', 80, '2026-09-18 19:42:53'),
(255, 8, 'item_modificado', 79, '2026-09-18 19:42:53'),
(256, 8, 'item_modificado', 78, '2026-09-18 19:42:53'),
(257, 8, 'item_modificado', 77, '2026-09-18 19:42:53'),
(258, 8, 'item_modificado', 76, '2026-09-18 19:42:53'),
(259, 8, 'item_modificado', 75, '2026-09-18 19:42:53'),
(260, 8, 'item_modificado', 74, '2026-09-18 19:42:53'),
(261, 8, 'item_modificado', 73, '2026-09-18 19:42:53'),
(262, 8, 'item_modificado', 72, '2026-09-18 19:42:53'),
(263, 8, 'item_modificado', 71, '2026-09-18 19:42:53'),
(264, 8, 'item_modificado', 70, '2026-09-18 19:42:53'),
(265, 8, 'item_modificado', 69, '2026-09-18 19:42:53'),
(266, 8, 'item_modificado', 68, '2026-09-18 19:42:53'),
(267, 8, 'item_modificado', 67, '2026-09-18 19:42:53'),
(268, 8, 'item_modificado', 66, '2026-09-18 19:42:53'),
(269, 8, 'item_modificado', 65, '2026-09-18 19:42:53'),
(270, 8, 'item_modificado', 64, '2026-09-18 19:42:53'),
(271, 8, 'item_modificado', 63, '2026-09-18 19:42:53'),
(272, 3, 'item_modificado', 92, '2026-09-18 19:44:06'),
(273, 3, 'item_modificado', 91, '2026-09-18 19:44:06'),
(274, 3, 'item_modificado', 90, '2026-09-18 19:44:06'),
(275, 3, 'item_modificado', 89, '2026-09-18 19:44:06'),
(276, 3, 'item_modificado', 88, '2026-09-18 19:44:06'),
(277, 3, 'item_modificado', 87, '2026-09-18 19:44:06'),
(278, 3, 'item_modificado', 86, '2026-09-18 19:44:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `unidad_medida` varchar(50) DEFAULT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `costo_actual` decimal(15,2) DEFAULT NULL,
  `moneda` varchar(3) DEFAULT 'USD',
  `fecha_ultimo_costo` date DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `usuario_creacion` int(11) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `nombre`, `descripcion`, `categoria_id`, `unidad_medida`, `codigo`, `costo_actual`, `moneda`, `fecha_ultimo_costo`, `activo`, `usuario_creacion`, `fecha_creacion`) VALUES
(1, 'Tomada Steck de 32 A 3P+T Macho', '', 3, 'UNIDAD', '23672', NULL, 'USD', NULL, 1, 1, '2026-09-11 11:27:12'),
(2, 'Tomada Steck de 32 A 3P+T FÊMEA', '', 3, 'UNIDAD', '25034', NULL, 'USD', NULL, 1, 1, '2026-09-11 11:29:42'),
(3, 'Conector M16 IP65 12 Pinos Macho 5A', '', 3, 'unidad', '25003', NULL, 'USD', NULL, 1, 1, '2026-09-14 10:46:08'),
(4, 'Conector M16 IP65 12 Pinos Fêmea', '', 3, 'unidad', '25010', NULL, 'USD', NULL, 1, 1, '2026-09-14 10:47:25'),
(5, 'Conector IFM M12 montável Macho', '', 3, 'unidad', '8204', NULL, 'USD', NULL, 1, 1, '2026-09-14 10:54:55'),
(6, 'Conector IFM M12 montável Fêmea', '', 3, 'unidad', '8211', NULL, 'USD', NULL, 1, 1, '2026-09-14 10:55:56'),
(7, 'Conexão Festo 10 mm / SMC MACHO', '', 4, 'unidad', '', NULL, 'USD', NULL, 1, 1, '2026-09-14 12:59:51'),
(8, 'Conexão Festo 10MM / SMC FÊMEA', '', 4, 'unidad', '', NULL, 'USD', NULL, 1, 1, '2026-09-14 13:00:54'),
(9, 'Painel 350x280x175mm', '', 9, 'unidad', '', NULL, 'USD', NULL, 1, 1, '2026-09-14 16:07:25'),
(10, 'Cabo PP 4 X 2,5 mm²', '', 3, 'm', '', NULL, 'USD', NULL, 1, 1, '2026-09-14 16:08:08'),
(11, 'Caixa termoplástica com tampa (250 x 200 x 130 mm)', '', 9, 'unidad', '', NULL, 'USD', NULL, 1, 1, '2026-09-14 16:54:34'),
(12, 'Trecho de trilho DIN 35mm perfurado', '', 3, 'unidad', '', NULL, 'USD', NULL, 1, 1, '2026-09-14 16:57:27');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proyectos`
--

CREATE TABLE `proyectos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `propuesta_tecnica` varchar(255) DEFAULT NULL,
  `propuesta_nombre_original` varchar(255) DEFAULT NULL,
  `propuesta_fecha_subida` datetime DEFAULT NULL,
  `propuesta_subida_por` int(11) DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `fecha_aprobacion` date DEFAULT NULL,
  `orden_compra` varchar(50) DEFAULT NULL,
  `estado` enum('solicitado','orçado','pendente_aprovacion_cliente','aprovado_cliente','espera_orden_compra','comprando_materiales','elaboracion','terminado','pendiente_cobro_cliente','finalizado') DEFAULT 'solicitado',
  `usuario_creacion` int(11) DEFAULT NULL,
  `encargado_id` int(11) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `proyectos`
--

INSERT INTO `proyectos` (`id`, `nombre`, `descripcion`, `propuesta_tecnica`, `propuesta_nombre_original`, `propuesta_fecha_subida`, `propuesta_subida_por`, `fecha_inicio`, `fecha_fin`, `fecha_aprobacion`, `orden_compra`, `estado`, `usuario_creacion`, `encargado_id`, `fecha_creacion`) VALUES
(1, 'ADEQUAÇÃO DE ESTEIRA DE CONTINGÊNCIA EM CASO DE AVARIAS NO RAIO-X.', 'Padronizar as conexões elétricas, de controle e de segurança das duas esteiras (Esteira Principal com Raio-X e Esteira Reserva de Contingência) utilizando conectores industriais de engate rápido. O objetivo é garantir que, em caso de falha do Raio-X ou da mecânica principal, a substituição física e o religamento elétrico da esteira reserva sejam feitos de forma rápida, sem a necessidade de ferramentas especiais ou abertura de painéis elétricos por um eletricista dedicado. Dessa forma pode-se garantir que o operador/Técnico apenas desencaixe uma esteira e encaixe a outra (Plug and Play), facilitando o setup.', 'propuestas/propuesta_proyecto_1_20260921142247_137e8261.pdf', 'Proposta técnica Adequação esteira de contigência Raio X.pdf', '2026-09-21 14:22:47', 1, '2026-09-14', '2026-09-30', '2026-09-14', 'orçamento 194', 'comprando_materiales', 1, NULL, '2026-09-10 12:36:08'),
(2, 'ADEQUAÇÃO DE CLIMATIZAÇÃO SALA DAS BOMBAS DE VÁCUO', 'Automatizar o controle de temperatura da sala de climatização através da integração do controlador( Spirax Sarco SX-UNI), realizando o acionamento da válvula esfera motorizada existente (Atuador SIMOKIT CR02 / 24VDC) instalada na tubulação de retorno de água gelada, a partir da leitura de um novo sensor de temperatura ambiente, onde o intuito é deixa o controle entre 17ºC à 22ºC, a situação atual deixa a sala muito gelada quando as bombas não estão em operação condensando e minando na sala da coordenação que fica logo abaixo desta.', 'propuestas/propuesta_proyecto_2_20260921142324_aa90ae4d.pdf', 'Proposta técnica Adequação da climatização da sala das bombas de vácuo.pdf', '2026-09-21 14:23:24', 1, '2026-09-07', '2026-10-23', '2026-09-07', 'orçamento 194', 'comprando_materiales', 1, NULL, '2026-09-14 16:51:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nombre_completo` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `tipo_usuario` enum('directivo','gerenciador','supervisor','compras','proyectista','almacen') NOT NULL,
  `idioma_preferido` enum('es','pt') DEFAULT 'es',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `activo` tinyint(1) DEFAULT 1,
  `debe_cambiar_password` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `username`, `password`, `nombre_completo`, `email`, `tipo_usuario`, `idioma_preferido`, `fecha_creacion`, `activo`, `debe_cambiar_password`) VALUES
(1, 'Master', '$2y$10$YhzYTI9x7M/YNiiNmwdbOen9qMIBfzY7EcpScQ7UqKDFbzC61Itm6', 'Administrador Master', 'carlosgomez@autecluz.com', 'directivo', 'pt', '2026-09-10 11:23:17', 1, 0),
(3, 'CARLA.SOUZA', '$2y$10$YRQx7GZqS481su8WtrDuhOfDxSmQ48DCOZplzNOQS0OZAZBTjapmm', 'CARLA SOUZA', 'Compras@autecluz.com', 'compras', 'pt', '2026-09-16 10:42:29', 1, 0),
(4, 'DANIEL.PAICO', '$2y$10$1284lFTsimaUU.8ViYKEtOPo4kUJlqQMEudN7l5acq2DO.y.Z9rSu', 'ERIK DANIEL PAICO', 'daniel@autecluz.com', 'almacen', 'es', '2026-09-16 13:16:50', 1, 0),
(6, 'Carlos.Gomez', '$2y$10$9OA/p48eOMtL.K9utOzc5.NqLjW0uFbCc80ivtWsUN8rbYe2V2R7G', 'Carlos Gomez', 'carlosgomez@autecluz.com', 'gerenciador', 'es', '2026-09-16 18:27:39', 1, 0),
(7, 'Ademar.Souza', '$2y$10$4ylWUhNh9WDUJ2gqymz7ruekO.7xJbJNR4j4XBbyfmIxldKiiIJYa', 'Ademar Junior Souza', 'ademar@autecluz.com', 'supervisor', 'pt', '2026-09-17 11:49:50', 1, 0),
(8, 'Heloisa.luz', '$2y$10$yg9TrU7LQqpH1UcggHubAePtrpi.n0OvLvFy4SVo0fX7oMP2vHZHG', 'Heloisa Luz', 'heloisa@autecluz.com', 'compras', 'pt', '2026-09-17 11:50:39', 1, 0),
(9, 'Priscila.Jorge', '$2y$10$Ydyeg0u5.aqogLT9S1ibLuZ25fxvFBnErralXYdxPF8vXfY4lhqj.', 'Priscila Biral Jorge', 'priscila@autecluz.com', 'directivo', 'pt', '2026-09-17 19:16:30', 1, 0),
(10, 'Americo.luz', '$2y$10$6H6acPE5OHlALWN4ZeYiAuLnE/nwwriA/uakLg5osAXHiFb6sbLha', 'Americo Hilario da Luz', 'americo@autecluz.com', 'directivo', 'pt', '2026-09-17 19:17:07', 1, 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `historial_items`
--
ALTER TABLE `historial_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `historial_proyectos`
--
ALTER TABLE `historial_proyectos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proyecto_id` (`proyecto_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `items_proyecto`
--
ALTER TABLE `items_proyecto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_proyecto` (`proyecto_id`),
  ADD KEY `idx_producto` (`producto_id`),
  ADD KEY `idx_items_estado` (`estado`),
  ADD KEY `idx_items_proyecto_estado` (`proyecto_id`,`estado`),
  ADD KEY `entregado_por` (`entregado_por`),
  ADD KEY `recibido_por` (`recibido_por`);

--
-- Indices de la tabla `notificaciones_leidas`
--
ALTER TABLE `notificaciones_leidas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_usuario_notif` (`usuario_id`,`tipo`,`referencia_id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_referencia` (`referencia_id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_creacion` (`usuario_creacion`),
  ADD KEY `idx_nombre` (`nombre`),
  ADD KEY `idx_categoria` (`categoria_id`),
  ADD KEY `idx_codigo` (`codigo`),
  ADD KEY `idx_productos_costo` (`costo_actual`);

--
-- Indices de la tabla `proyectos`
--
ALTER TABLE `proyectos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_creacion` (`usuario_creacion`),
  ADD KEY `idx_proyectos_encargado` (`encargado_id`),
  ADD KEY `propuesta_subida_por` (`propuesta_subida_por`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `historial_items`
--
ALTER TABLE `historial_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- AUTO_INCREMENT de la tabla `historial_proyectos`
--
ALTER TABLE `historial_proyectos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `items_proyecto`
--
ALTER TABLE `items_proyecto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT de la tabla `notificaciones_leidas`
--
ALTER TABLE `notificaciones_leidas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=279;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `proyectos`
--
ALTER TABLE `proyectos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `historial_items`
--
ALTER TABLE `historial_items`
  ADD CONSTRAINT `historial_items_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items_proyecto` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `historial_items_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `historial_proyectos`
--
ALTER TABLE `historial_proyectos`
  ADD CONSTRAINT `historial_proyectos_ibfk_1` FOREIGN KEY (`proyecto_id`) REFERENCES `proyectos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `historial_proyectos_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `items_proyecto`
--
ALTER TABLE `items_proyecto`
  ADD CONSTRAINT `items_proyecto_ibfk_1` FOREIGN KEY (`proyecto_id`) REFERENCES `proyectos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `items_proyecto_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `items_proyecto_ibfk_3` FOREIGN KEY (`entregado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `items_proyecto_ibfk_4` FOREIGN KEY (`recibido_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `notificaciones_leidas`
--
ALTER TABLE `notificaciones_leidas`
  ADD CONSTRAINT `notificaciones_leidas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `productos_ibfk_2` FOREIGN KEY (`usuario_creacion`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `proyectos`
--
ALTER TABLE `proyectos`
  ADD CONSTRAINT `proyectos_ibfk_1` FOREIGN KEY (`usuario_creacion`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `proyectos_ibfk_2` FOREIGN KEY (`encargado_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `proyectos_ibfk_3` FOREIGN KEY (`propuesta_subida_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `proyectos_ibfk_encargado` FOREIGN KEY (`encargado_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
