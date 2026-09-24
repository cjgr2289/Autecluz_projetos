-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 14-09-2026 a las 22:46:05
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
-- Base de datos: `sistema_proyectos`
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
(5, 'Tintas', 'Pinturas, brochas, rodillos', 1, '2026-09-10 11:23:17'),
(6, 'Segurança', 'Equipos de protección personal', 1, '2026-09-10 11:23:17'),
(7, 'Escritorio', 'Materiais de escritorio', 1, '2026-09-10 11:23:17'),
(8, 'Informática', 'Equipos y accesorios de computación', 1, '2026-09-10 11:23:17'),
(9, 'Otros', 'Productos no clasificados', 1, '2026-09-10 11:23:17');

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
  `costo_anterior` decimal(15,2) DEFAULT NULL,
  `costo_nuevo` decimal(15,2) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `fecha_cambio` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `historial_items`
--

INSERT INTO `historial_items` (`id`, `item_id`, `estado_anterior`, `estado_nuevo`, `fecha_anterior`, `fecha_nueva`, `cantidad_anterior`, `cantidad_nueva`, `costo_anterior`, `costo_nuevo`, `usuario_id`, `comentario`, `fecha_cambio`) VALUES
(1, 1, NULL, 'pendiente', NULL, '2026-09-14', NULL, 2, NULL, NULL, 1, 'Item creado', '2026-09-11 11:29:26'),
(2, 2, NULL, 'pendiente', NULL, '2026-09-14', NULL, 1, NULL, NULL, 1, 'Item creado', '2026-09-11 11:34:13'),
(3, 3, NULL, 'pendiente', NULL, '2026-09-14', NULL, 1, NULL, NULL, 1, 'Item creado', '2026-09-14 10:56:14'),
(4, 4, NULL, 'pendiente', NULL, '2026-09-14', NULL, 1, NULL, NULL, 1, 'Item creado', '2026-09-14 10:56:14'),
(5, 5, NULL, 'pendiente', NULL, '2026-09-14', NULL, 1, NULL, NULL, 1, 'Item creado', '2026-09-14 10:56:15'),
(6, 6, NULL, 'pendiente', NULL, '2026-09-14', NULL, 1, NULL, NULL, 1, 'Item creado', '2026-09-14 10:56:15'),
(7, 7, NULL, 'pendiente', NULL, '2026-09-14', NULL, 1, NULL, NULL, 1, 'Item creado', '2026-09-14 13:01:08'),
(8, 8, NULL, 'pendiente', NULL, '2026-09-14', NULL, 2, NULL, NULL, 1, 'Item creado', '2026-09-14 13:01:09'),
(9, 9, NULL, 'pendiente', NULL, '2026-09-14', NULL, 1, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 16:08:20'),
(10, 10, NULL, 'pendiente', NULL, '2026-09-14', NULL, 3, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 16:09:14'),
(29, 29, NULL, 'pendiente', NULL, '2026-09-21', NULL, 50, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 17:08:33'),
(30, 30, NULL, 'pendiente', NULL, '2026-09-14', NULL, 1, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 17:09:49'),
(31, 31, NULL, 'pendiente', NULL, '2026-09-21', NULL, 1, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 17:09:49'),
(32, 32, NULL, 'pendiente', NULL, '2026-09-21', NULL, 1, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 17:09:49'),
(33, 33, NULL, 'pendiente', NULL, '2026-09-21', NULL, 1, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 17:09:49'),
(34, 34, NULL, 'pendiente', NULL, '2026-09-21', NULL, 1, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 17:09:49'),
(35, 35, NULL, 'pendiente', NULL, '2026-09-21', NULL, 1, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 17:09:49'),
(36, 36, NULL, 'pendiente', NULL, '2026-09-21', NULL, 1, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 17:09:49'),
(37, 37, NULL, 'pendiente', NULL, '2026-09-21', NULL, 1, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 17:09:49'),
(38, 38, NULL, 'pendiente', NULL, '2026-09-21', NULL, 1, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 17:09:49'),
(39, 39, NULL, 'pendiente', NULL, '2026-09-21', NULL, 1, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 17:09:49'),
(40, 40, NULL, 'pendiente', NULL, '2026-09-21', NULL, 8, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 17:09:49'),
(41, 41, NULL, 'pendiente', NULL, '2026-09-21', NULL, 2, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 17:09:49'),
(42, 42, NULL, 'pendiente', NULL, '2026-09-21', NULL, 6, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 17:09:49'),
(43, 43, NULL, 'pendiente', NULL, '2026-09-21', NULL, 10, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 17:09:49'),
(44, 44, NULL, 'pendiente', NULL, '2026-09-21', NULL, 10, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 17:09:49'),
(45, 45, NULL, 'pendiente', NULL, '2026-09-21', NULL, 10, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 17:09:49'),
(46, 46, NULL, 'pendiente', NULL, '2026-09-21', NULL, 10, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 17:09:49'),
(47, 47, NULL, 'pendiente', NULL, '2026-09-21', NULL, 5, NULL, NULL, 1, 'Item creado (lote)', '2026-09-14 17:09:49');

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
  `costo_unitario` decimal(15,2) DEFAULT NULL,
  `moneda` varchar(3) DEFAULT 'USD',
  `proveedor` varchar(200) DEFAULT NULL,
  `numero_factura` varchar(100) DEFAULT NULL,
  `fecha_compra` date DEFAULT NULL,
  `unidad_medida` varchar(50) DEFAULT NULL,
  `especificaciones` text DEFAULT NULL,
  `estado` enum('solicitado','pendiente','stock','cotacion','orçado','pendiente_pago','comprado_llegar','llego') DEFAULT 'solicitado',
  `fecha_requerida` date NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `items_proyecto`
--

INSERT INTO `items_proyecto` (`id`, `proyecto_id`, `producto_id`, `nombre_item`, `descripcion`, `cantidad`, `costo_unitario`, `moneda`, `proveedor`, `numero_factura`, `fecha_compra`, `unidad_medida`, `especificaciones`, `estado`, `fecha_requerida`, `fecha_creacion`) VALUES
(1, 1, 1, 'Tomada Steck de 32 A 3P+T Macho', NULL, 2, NULL, 'USD', NULL, NULL, NULL, 'UNIDAD', '', 'pendiente', '2026-09-14', '2026-09-11 11:29:26'),
(2, 1, 2, 'Tomada Steck de 32 A 3P+T FÊMEA', NULL, 1, NULL, 'USD', NULL, NULL, NULL, 'UNIDAD', '', 'pendiente', '2026-09-14', '2026-09-11 11:34:13'),
(3, 1, 4, 'Conector M16 IP65 12 Pinos Fêmea', NULL, 1, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'pendiente', '2026-09-14', '2026-09-14 10:56:14'),
(4, 1, 5, 'Conector IFM M12 montável Macho', NULL, 2, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'pendiente', '2026-09-14', '2026-09-14 10:56:14'),
(5, 1, 6, 'Conector IFM M12 montável Fêmea', NULL, 2, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'pendiente', '2026-09-14', '2026-09-14 10:56:15'),
(6, 1, 3, 'Conector M16 IP65 12 Pinos Macho 5A', NULL, 1, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'pendiente', '2026-09-14', '2026-09-14 10:56:15'),
(7, 1, 8, 'Conexão Festo 10MM / SMC FÊMEA', NULL, 1, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'pendiente', '2026-09-14', '2026-09-14 13:01:08'),
(8, 1, 7, 'Conexão Festo 10 mm / SMC MACHO', NULL, 2, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'pendiente', '2026-09-14', '2026-09-14 13:01:09'),
(9, 1, 9, 'Painel 350x280x175mm', NULL, 1, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'pendiente', '2026-09-14', '2026-09-14 16:08:20'),
(10, 1, 10, 'Cabo PP 4 X 2,5 mm²', NULL, 3, NULL, 'USD', NULL, NULL, NULL, 'm', '', 'pendiente', '2026-09-14', '2026-09-14 16:09:14'),
(29, 2, NULL, 'Luvas / Marcadores', NULL, 50, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'pendiente', '2026-09-21', '2026-09-14 17:08:33'),
(30, 2, 11, 'Caixa termoplástica com tampa (250 x 200 x 130 mm)', NULL, 1, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'pendiente', '2026-09-14', '2026-09-14 17:09:49'),
(31, 2, 12, 'Trecho de trilho DIN 35mm perfurado', NULL, 1, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'pendiente', '2026-09-21', '2026-09-14 17:09:49'),
(32, 2, NULL, 'Disjuntor DIN Unipolar/Bipolar (C6A)', NULL, 1, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'pendiente', '2026-09-21', '2026-09-14 17:09:49'),
(33, 2, NULL, 'Fonte Chaveada DIN Entrada: 100-240VAC / Saída: 24VDC - 2,5A', NULL, 1, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'pendiente', '2026-09-21', '2026-09-14 17:09:49'),
(34, 2, NULL, 'Controlador de Temperatura Spirax Sarco SX-UNI', NULL, 1, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'pendiente', '2026-09-21', '2026-09-14 17:09:49'),
(35, 2, NULL, 'Relé de Interface DIN com base (Contato SPDT / Reversor)', NULL, 1, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'pendiente', '2026-09-21', '2026-09-14 17:09:49'),
(36, 2, NULL, 'Sensor de temperatura ambiente (ex: PT100 haste/cabeçote)', NULL, 1, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'pendiente', '2026-09-21', '2026-09-14 17:09:49'),
(37, 2, 5, 'Conector IFM M12 montável Macho', NULL, 1, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'pendiente', '2026-09-21', '2026-09-14 17:09:49'),
(38, 2, 6, 'Conector IFM M12 montável Fêmea', NULL, 1, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'pendiente', '2026-09-21', '2026-09-14 17:09:49'),
(39, 2, NULL, 'Prensa-cabos termoplástico PG9 / PG11 com contra-porca', NULL, 1, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'pendiente', '2026-09-21', '2026-09-14 17:09:49'),
(40, 2, NULL, 'Bornes de passagem tipo KRG / SAK 1,5 mm', NULL, 8, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'pendiente', '2026-09-21', '2026-09-14 17:09:49'),
(41, 2, NULL, 'Poste final / Trava para trilho DIN', NULL, 2, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'pendiente', '2026-09-21', '2026-09-14 17:09:49'),
(42, 2, NULL, 'parafuso rosca soberba', NULL, 6, NULL, 'USD', NULL, NULL, NULL, 'unidad', '', 'pendiente', '2026-09-21', '2026-09-14 17:09:49'),
(43, 2, NULL, 'Fio flexível 1,5 mm² Preto', NULL, 10, NULL, 'USD', NULL, NULL, NULL, 'm', '', 'pendiente', '2026-09-21', '2026-09-14 17:09:49'),
(44, 2, NULL, 'Fio flexível 1,5 mm² Azul Claro', NULL, 10, NULL, 'USD', NULL, NULL, NULL, 'm', '', 'pendiente', '2026-09-21', '2026-09-14 17:09:49'),
(45, 2, NULL, 'Fio flexível 1 mm² azul Oscuro', NULL, 10, NULL, 'USD', NULL, NULL, NULL, 'm', '', 'pendiente', '2026-09-21', '2026-09-14 17:09:49'),
(46, 2, NULL, 'Fio flexível 1 mm² CINZA', NULL, 10, NULL, 'USD', NULL, NULL, NULL, 'm', '', 'pendiente', '2026-09-21', '2026-09-14 17:09:49'),
(47, 2, NULL, 'Cabo PP de comando blindado/manga (ex: 3x0,75mm²)', NULL, 5, NULL, 'USD', NULL, NULL, NULL, 'm', '', 'pendiente', '2026-09-21', '2026-09-14 17:09:49');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones_leidas`
--

CREATE TABLE `notificaciones_leidas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo` enum('item_modificado','item_agregado','item_proximo','item_vencido','estado_cambiado') NOT NULL,
  `referencia_id` int(11) NOT NULL,
  `fecha_lectura` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `activo` tinyint(1) DEFAULT 1,
  `usuario_creacion` int(11) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `nombre`, `descripcion`, `categoria_id`, `unidad_medida`, `codigo`, `activo`, `usuario_creacion`, `fecha_creacion`) VALUES
(1, 'Tomada Steck de 32 A 3P+T Macho', '', 3, 'UNIDAD', '23672', 1, 1, '2026-09-11 11:27:12'),
(2, 'Tomada Steck de 32 A 3P+T FÊMEA', '', 3, 'UNIDAD', '25034', 1, 1, '2026-09-11 11:29:42'),
(3, 'Conector M16 IP65 12 Pinos Macho 5A', '', 3, 'unidad', '25003', 1, 1, '2026-09-14 10:46:08'),
(4, 'Conector M16 IP65 12 Pinos Fêmea', '', 3, 'unidad', '25010', 1, 1, '2026-09-14 10:47:25'),
(5, 'Conector IFM M12 montável Macho', '', 3, 'unidad', '8204', 1, 1, '2026-09-14 10:54:55'),
(6, 'Conector IFM M12 montável Fêmea', '', 3, 'unidad', '8211', 1, 1, '2026-09-14 10:55:56'),
(7, 'Conexão Festo 10 mm / SMC MACHO', '', 4, 'unidad', '', 1, 1, '2026-09-14 12:59:51'),
(8, 'Conexão Festo 10MM / SMC FÊMEA', '', 4, 'unidad', '', 1, 1, '2026-09-14 13:00:54'),
(9, 'Painel 350x280x175mm', '', 9, 'unidad', '', 1, 1, '2026-09-14 16:07:25'),
(10, 'Cabo PP 4 X 2,5 mm²', '', 3, 'm', '', 1, 1, '2026-09-14 16:08:08'),
(11, 'Caixa termoplástica com tampa (250 x 200 x 130 mm)', '', 9, 'unidad', '', 1, 1, '2026-09-14 16:54:34'),
(12, 'Trecho de trilho DIN 35mm perfurado', '', 3, 'unidad', '', 1, 1, '2026-09-14 16:57:27');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proyectos`
--

CREATE TABLE `proyectos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `fecha_aprobacion` date DEFAULT NULL,
  `orden_compra` varchar(50) DEFAULT NULL,
  `estado` enum('solicitado','orçado','pendente_aprovacion_cliente','aprovado_cliente','espera_orden_compra','comprando_materiales','elaboracion','terminado','pendiente_cobro_cliente','finalizado') DEFAULT 'solicitado',
  `usuario_creacion` int(11) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `proyectos`
--

INSERT INTO `proyectos` (`id`, `nombre`, `descripcion`, `fecha_inicio`, `fecha_fin`, `fecha_aprobacion`, `orden_compra`, `estado`, `usuario_creacion`, `fecha_creacion`) VALUES
(1, 'ADEQUAÇÃO DE ESTEIRA DE CONTINGÊNCIA EM CASO DE AVARIAS NO RAIO-X.', 'Padronizar as conexões elétricas, de controle e de segurança das duas esteiras (Esteira Principal com Raio-X e Esteira Reserva de Contingência) utilizando conectores industriais de engate rápido. O objetivo é garantir que, em caso de falha do Raio-X ou da mecânica principal, a substituição física e o religamento elétrico da esteira reserva sejam feitos de forma rápida, sem a necessidade de ferramentas especiais ou abertura de painéis elétricos por um eletricista dedicado. Dessa forma pode-se garantir que o operador/Técnico apenas desencaixe uma esteira e encaixe a outra (Plug and Play), facilitando o setup.', '2026-09-14', '2026-09-30', '2026-09-14', 'orçamento 194', 'comprando_materiales', 1, '2026-09-10 12:36:08'),
(2, 'ADEQUAÇÃO DE CLIMATIZAÇÃO SALA DAS BOMBAS DE VÁCUO', 'Automatizar o controle de temperatura da sala de climatização através da integração do controlador( Spirax Sarco SX-UNI), realizando o acionamento da válvula esfera motorizada existente (Atuador SIMOKIT CR02 / 24VDC) instalada na tubulação de retorno de água gelada, a partir da leitura de um novo sensor de temperatura ambiente, onde o intuito é deixa o controle entre 17ºC à 22ºC, a situação atual deixa a sala muito gelada quando as bombas não estão em operação condensando e minando na sala da coordenação que fica logo abaixo desta.', '2026-09-07', '2026-10-23', '2026-09-07', 'orçamento 194', 'comprando_materiales', 1, '2026-09-14 16:51:50');

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
  `tipo_usuario` enum('directivo','gerenciador','supervisor','compras','proyectista') NOT NULL,
  `idioma_preferido` enum('es','pt') DEFAULT 'es',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `username`, `password`, `nombre_completo`, `email`, `tipo_usuario`, `idioma_preferido`, `fecha_creacion`, `activo`) VALUES
(1, 'Master', '$2y$10$YhzYTI9x7M/YNiiNmwdbOen9qMIBfzY7EcpScQ7UqKDFbzC61Itm6', 'Administrador Master', 'carlosgomez@autecluz.com', 'directivo', 'pt', '2026-09-10 11:23:17', 1),
(2, 'Heloisa.luz', '$2y$10$qfdhj98AuIzEhiTUDcdtwOUJwbzmeiE019zivHNlGnvK4gYUmtjiq', 'Heloisa Luz', 'carlosgomez@autecluz.com', 'compras', 'pt', '2026-09-10 19:53:43', 1);

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
  ADD KEY `idx_items_proyecto_estado` (`proyecto_id`,`estado`);

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
  ADD KEY `idx_codigo` (`codigo`);

--
-- Indices de la tabla `proyectos`
--
ALTER TABLE `proyectos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_creacion` (`usuario_creacion`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `historial_items`
--
ALTER TABLE `historial_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT de la tabla `historial_proyectos`
--
ALTER TABLE `historial_proyectos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `items_proyecto`
--
ALTER TABLE `items_proyecto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT de la tabla `notificaciones_leidas`
--
ALTER TABLE `notificaciones_leidas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  ADD CONSTRAINT `items_proyecto_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE SET NULL;

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
  ADD CONSTRAINT `proyectos_ibfk_1` FOREIGN KEY (`usuario_creacion`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
