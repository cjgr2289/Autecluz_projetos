-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 24-09-2026 a las 08:16:09
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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `razon_social` varchar(250) DEFAULT NULL,
  `ruc` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `pais` varchar(100) DEFAULT 'Brasil',
  `sitio_web` varchar(200) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `usuario_creacion` int(11) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes_responsables`
--

CREATE TABLE `clientes_responsables` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `cargo` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `celular` varchar(50) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `correlativos`
--

CREATE TABLE `correlativos` (
  `id` int(11) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `prefijo` varchar(20) NOT NULL,
  `ultimo_numero` int(11) DEFAULT 0,
  `año` int(11) DEFAULT 0,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `porcentaje_lucro` decimal(5,2) DEFAULT 30.00,
  `moneda` varchar(3) DEFAULT 'USD',
  `fecha_ultimo_costo` date DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `usuario_creacion` int(11) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `propuestas_economicas`
--

CREATE TABLE `propuestas_economicas` (
  `id` int(11) NOT NULL,
  `proyecto_id` int(11) NOT NULL,
  `numero` varchar(50) DEFAULT NULL,
  `titulo` varchar(200) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `modo_mano_obra` enum('horas','dias') DEFAULT 'dias',
  `cantidad_tiempo` decimal(10,2) DEFAULT 0.00,
  `personas` int(11) DEFAULT 1,
  `valor_unitario` decimal(10,2) DEFAULT 93.00,
  `horas_por_dia` int(11) DEFAULT 8,
  `subtotal_materiales` decimal(15,2) DEFAULT 0.00,
  `subtotal_mano_obra` decimal(15,2) DEFAULT 0.00,
  `subtotal_general` decimal(15,2) DEFAULT 0.00,
  `descuento_porcentaje` decimal(5,2) DEFAULT 0.00,
  `descuento_valor` decimal(15,2) DEFAULT 0.00,
  `impuestos_porcentaje` decimal(5,2) DEFAULT 0.00,
  `impuestos_valor` decimal(15,2) DEFAULT 0.00,
  `total_final` decimal(15,2) DEFAULT 0.00,
  `moneda` varchar(3) DEFAULT 'BRL',
  `validez_dias` int(11) DEFAULT 30,
  `condiciones_pago` varchar(200) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `estado` enum('borrador','enviada','aprobada','rechazada') DEFAULT 'borrador',
  `usuario_creacion` int(11) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `propuestas_items`
--

CREATE TABLE `propuestas_items` (
  `id` int(11) NOT NULL,
  `propuesta_id` int(11) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `producto_id` int(11) DEFAULT NULL,
  `nombre_item` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `unidad_medida` varchar(50) DEFAULT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `costo_unitario` decimal(15,2) DEFAULT 0.00,
  `porcentaje_lucro` decimal(5,2) DEFAULT 30.00,
  `precio_venta_unitario` decimal(15,2) DEFAULT 0.00,
  `subtotal` decimal(15,2) DEFAULT 0.00,
  `orden` int(11) DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proyectos`
--

CREATE TABLE `proyectos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_solicitud` date DEFAULT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `cliente_responsable_id` int(11) DEFAULT NULL,
  `propuesta_tecnica` varchar(255) DEFAULT NULL,
  `propuesta_nombre_original` varchar(255) DEFAULT NULL,
  `propuesta_fecha_subida` datetime DEFAULT NULL,
  `propuesta_subida_por` int(11) DEFAULT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `fecha_aprobacion` date DEFAULT NULL,
  `orden_compra` varchar(50) DEFAULT NULL,
  `estado` enum('solicitado','orçado','pendente_aprovacion_cliente','aprovado_cliente','espera_orden_compra','comprando_materiales','elaboracion','terminado','pendiente_cobro_cliente','finalizado','rechazado') DEFAULT 'solicitado',
  `motivo_rechazo` text DEFAULT NULL,
  `usuario_creacion` int(11) DEFAULT NULL,
  `encargado_id` int(11) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_creacion` (`usuario_creacion`),
  ADD KEY `idx_nombre` (`nombre`),
  ADD KEY `idx_activo` (`activo`);

--
-- Indices de la tabla `clientes_responsables`
--
ALTER TABLE `clientes_responsables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cliente` (`cliente_id`),
  ADD KEY `idx_activo` (`activo`);

--
-- Indices de la tabla `correlativos`
--
ALTER TABLE `correlativos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tipo` (`tipo`);

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
-- Indices de la tabla `propuestas_economicas`
--
ALTER TABLE `propuestas_economicas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero` (`numero`),
  ADD KEY `usuario_creacion` (`usuario_creacion`),
  ADD KEY `idx_proyecto` (`proyecto_id`),
  ADD KEY `idx_numero` (`numero`),
  ADD KEY `idx_estado` (`estado`);

--
-- Indices de la tabla `propuestas_items`
--
ALTER TABLE `propuestas_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `producto_id` (`producto_id`),
  ADD KEY `idx_propuesta` (`propuesta_id`);

--
-- Indices de la tabla `proyectos`
--
ALTER TABLE `proyectos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_creacion` (`usuario_creacion`),
  ADD KEY `idx_proyectos_encargado` (`encargado_id`),
  ADD KEY `propuesta_subida_por` (`propuesta_subida_por`),
  ADD KEY `cliente_responsable_id` (`cliente_responsable_id`),
  ADD KEY `idx_proyectos_cliente` (`cliente_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `clientes_responsables`
--
ALTER TABLE `clientes_responsables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `correlativos`
--
ALTER TABLE `correlativos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `historial_items`
--
ALTER TABLE `historial_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `historial_proyectos`
--
ALTER TABLE `historial_proyectos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `items_proyecto`
--
ALTER TABLE `items_proyecto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notificaciones_leidas`
--
ALTER TABLE `notificaciones_leidas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `propuestas_economicas`
--
ALTER TABLE `propuestas_economicas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `propuestas_items`
--
ALTER TABLE `propuestas_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `proyectos`
--
ALTER TABLE `proyectos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD CONSTRAINT `clientes_ibfk_1` FOREIGN KEY (`usuario_creacion`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `clientes_responsables`
--
ALTER TABLE `clientes_responsables`
  ADD CONSTRAINT `clientes_responsables_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE;

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
-- Filtros para la tabla `propuestas_economicas`
--
ALTER TABLE `propuestas_economicas`
  ADD CONSTRAINT `propuestas_economicas_ibfk_1` FOREIGN KEY (`proyecto_id`) REFERENCES `proyectos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `propuestas_economicas_ibfk_2` FOREIGN KEY (`usuario_creacion`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `propuestas_items`
--
ALTER TABLE `propuestas_items`
  ADD CONSTRAINT `propuestas_items_ibfk_1` FOREIGN KEY (`propuesta_id`) REFERENCES `propuestas_economicas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `propuestas_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items_proyecto` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `propuestas_items_ibfk_3` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `proyectos`
--
ALTER TABLE `proyectos`
  ADD CONSTRAINT `proyectos_ibfk_1` FOREIGN KEY (`usuario_creacion`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `proyectos_ibfk_2` FOREIGN KEY (`encargado_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `proyectos_ibfk_3` FOREIGN KEY (`propuesta_subida_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `proyectos_ibfk_4` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `proyectos_ibfk_5` FOREIGN KEY (`cliente_responsable_id`) REFERENCES `clientes_responsables` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `proyectos_ibfk_encargado` FOREIGN KEY (`encargado_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
