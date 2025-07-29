-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 28-07-2025 a las 01:50:37
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
-- Base de datos: `inventory_management_system2`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bobinas`
--

CREATE TABLE `bobinas` (
  `bobina_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `metros_actuales` decimal(10,2) DEFAULT NULL,
  `identificador` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `bobinas`
--

INSERT INTO `bobinas` (`bobina_id`, `product_id`, `metros_actuales`, `identificador`, `is_active`, `created_at`) VALUES
(2, 72, 305.00, 'Bobina #1', 1, '2025-07-14 19:57:57'),
(6, 72, 305.00, 'Bobina #2', 1, '2025-07-16 20:13:48'),
(7, 72, 700.00, 'Bobina #3', 1, '2025-07-21 15:03:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `parent_category_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categories`
--

INSERT INTO `categories` (`category_id`, `name`, `parent_category_id`) VALUES
(1, 'DVR', NULL),
(2, 'Cámaras IP', NULL),
(3, 'DVR 8CH', NULL),
(4, 'DVR 4CH', NULL),
(5, 'Accesorios', NULL),
(6, 'Cámaras PTZ', NULL),
(7, 'Cámaras', NULL),
(8, 'Control de Asistencias', NULL),
(9, 'Switch Poe', NULL),
(10, 'Cámaras Domo', NULL),
(11, 'Cámaras Full color', NULL),
(12, 'Switch', NULL),
(13, 'Router', NULL),
(14, 'Dash Cam', NULL),
(15, 'Alarma', NULL),
(17, 'Sensores de Alarmas', NULL),
(18, 'Paquetes de alarmas', NULL),
(19, 'Monitores', NULL),
(27, 'Facial', NULL),
(28, 'Cables', NULL),
(29, 'Cámara Wifi', NULL),
(30, 'Terminal Acceso', NULL),
(31, 'Cámara Bullet', NULL),
(32, 'Soporte y Montaje', NULL),
(33, 'Energía', NULL),
(34, 'Modulos de Expansión', NULL),
(35, 'Videoportero', NULL),
(36, 'Mesh', NULL),
(37, 'Gabinete', NULL),
(38, 'DISCOS', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `cliente_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `ubicacion` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`cliente_id`, `nombre`, `telefono`, `ubicacion`, `email`) VALUES
(1, 'josue', '9996366230', 'Mérida/Yucatán', 'josuechucmedina980@gmail.com');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cotizaciones`
--

CREATE TABLE `cotizaciones` (
  `cotizacion_id` int(11) NOT NULL,
  `numero_cotizacion` varchar(20) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `fecha_cotizacion` date DEFAULT NULL,
  `validez_dias` int(11) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL,
  `descuento_porcentaje` decimal(5,2) DEFAULT NULL,
  `descuento_monto` decimal(10,2) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `condiciones_pago` text DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `estado_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cotizaciones`
--

INSERT INTO `cotizaciones` (`cotizacion_id`, `numero_cotizacion`, `cliente_id`, `fecha_cotizacion`, `validez_dias`, `subtotal`, `descuento_porcentaje`, `descuento_monto`, `total`, `condiciones_pago`, `observaciones`, `estado_id`, `user_id`, `created_at`, `updated_at`) VALUES
(1, 'COT-2025-0001', 1, '2025-07-17', 30, 2177.12, 0.00, 0.00, 2177.12, '', '0', 2, 1, '2025-07-17 04:58:50', '2025-07-17 04:58:50'),
(2, 'COT-2025-0002', 1, '2025-07-17', 30, 544.28, 0.00, 0.00, 544.28, '', '0', 2, 1, '2025-07-17 05:01:04', '2025-07-17 05:01:04'),
(3, 'COT-2025-0003', 1, '2025-07-17', 30, 477.28, 0.00, 0.00, 477.28, '', '0', 2, 1, '2025-07-17 05:02:28', '2025-07-17 05:02:28'),
(4, 'COT-2025-0004', 1, '2025-07-18', 30, 2307.35, 0.00, 0.00, 2307.35, '', '0', 5, 1, '2025-07-18 07:26:15', '2025-07-22 19:54:44'),
(5, 'COT-2025-0005', 1, '2025-07-22', 30, 2307.35, 0.00, 0.00, 2307.35, '', '0', 5, 1, '2025-07-22 17:08:23', '2025-07-22 19:29:29'),
(6, 'COT-2025-0006', 1, '2025-07-22', 30, 1845.88, 0.00, 0.00, 1845.88, '', '0', 5, 1, '2025-07-22 18:03:02', '2025-07-22 19:30:10'),
(7, 'COT-2025-0007', 1, '2025-07-22', 30, 611.47, 0.00, 0.00, 611.47, '', '0', 5, 1, '2025-07-22 19:32:56', '2025-07-22 19:33:17'),
(8, 'COT-2025-0008', 1, '2025-07-22', 30, 611.47, 0.00, 0.00, 611.47, '', '0', 5, 1, '2025-07-22 19:55:54', '2025-07-22 19:56:08'),
(9, 'COT-2025-0009', 1, '2025-07-22', 30, 611.47, 0.00, 0.00, 611.47, '', '0', 5, 1, '2025-07-22 20:04:55', '2025-07-22 20:05:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cotizaciones_acciones`
--

CREATE TABLE `cotizaciones_acciones` (
  `accion_id` int(11) NOT NULL,
  `nombre_accion` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cotizaciones_acciones`
--

INSERT INTO `cotizaciones_acciones` (`accion_id`, `nombre_accion`) VALUES
(3, 'Aprobada'),
(5, 'Convertida'),
(1, 'Creada'),
(7, 'Devolución'),
(2, 'Enviada'),
(6, 'Modificada'),
(4, 'Rechazada');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cotizaciones_historial`
--

CREATE TABLE `cotizaciones_historial` (
  `historial_id` int(11) NOT NULL,
  `cotizacion_id` int(11) NOT NULL,
  `accion_id` int(11) NOT NULL,
  `comentario` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `fecha_accion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cotizaciones_historial`
--

INSERT INTO `cotizaciones_historial` (`historial_id`, `cotizacion_id`, `accion_id`, `comentario`, `user_id`, `fecha_accion`) VALUES
(1, 1, 1, 'Cotización creada con 1 productos por un total de $2,177.12', 1, '2025-07-17 04:58:50'),
(2, 2, 1, 'Cotización creada con 1 productos por un total de $544.28', 1, '2025-07-17 05:01:04'),
(3, 3, 1, 'Cotización creada con 1 productos por un total de $477.28', 1, '2025-07-17 05:02:28'),
(4, 4, 1, 'Cotización creada con 1 productos por un total de $3,057.35', 1, '2025-07-18 07:26:15'),
(5, 5, 1, 'Cotización creada con 1 productos por un total de $2,445.88', 1, '2025-07-22 17:08:23'),
(6, 5, 3, 'Estado cambiado de \'Enviada\' a \'Aprobada\'', 1, '2025-07-22 17:10:22'),
(7, 5, 4, 'Estado cambiado de \'Aprobada\' a \'Rechazada\'', 1, '2025-07-22 17:11:38'),
(8, 5, 3, 'Estado cambiado de \'Rechazada\' a \'Aprobada\'', 1, '2025-07-22 17:11:43'),
(9, 5, 6, 'Cotización modificada con 1 productos por un total de $2,307.35', 1, '2025-07-22 17:42:26'),
(10, 5, 6, 'Cotización modificada con 1 productos por un total de $2,307.35', 1, '2025-07-22 17:53:54'),
(11, 5, 6, 'Cotización modificada con 1 productos por un total de $2,307.35', 1, '2025-07-22 17:54:24'),
(12, 5, 6, 'Cotización modificada con 1 productos por un total de $2,307.35', 1, '2025-07-22 17:57:42'),
(13, 5, 6, 'Cotización modificada con 1 productos por un total de $2,307.35', 1, '2025-07-22 17:58:00'),
(14, 6, 1, 'Cotización creada con 1 productos por un total de $2,145.88', 1, '2025-07-22 18:03:02'),
(15, 6, 3, 'Estado cambiado de \'Enviada\' a \'Aprobada\'', 1, '2025-07-22 18:03:09'),
(16, 6, 4, 'Estado cambiado de \'Aprobada\' a \'Rechazada\'', 1, '2025-07-22 18:25:48'),
(17, 6, 3, 'Estado cambiado de \'Rechazada\' a \'Aprobada\'', 1, '2025-07-22 18:25:51'),
(18, 6, 5, 'Cotización convertida a venta y stock descontado', 1, '2025-07-22 18:37:45'),
(19, 5, 5, 'Cotización convertida a venta y stock descontado', 1, '2025-07-22 19:29:29'),
(20, 6, 6, 'Cotización modificada con 1 productos por un total de $1,845.88', 1, '2025-07-22 19:30:10'),
(21, 7, 1, 'Cotización creada con 1 productos por un total de $611.47', 1, '2025-07-22 19:32:56'),
(22, 7, 3, 'Estado cambiado de \'Enviada\' a \'Aprobada\'', 1, '2025-07-22 19:33:03'),
(23, 7, 5, 'Cotización convertida a venta y stock descontado', 1, '2025-07-22 19:33:17'),
(24, 4, 6, 'Cotización modificada con 1 productos por un total de $2,307.35', 1, '2025-07-22 19:54:30'),
(25, 4, 3, 'Estado cambiado de \'Enviada\' a \'Aprobada\'', 1, '2025-07-22 19:54:36'),
(26, 4, 5, 'Cotización convertida a venta y stock descontado', 1, '2025-07-22 19:54:44'),
(27, 8, 1, 'Cotización creada con 1 productos por un total de $611.47', 1, '2025-07-22 19:55:54'),
(28, 8, 3, 'Estado cambiado de \'Enviada\' a \'Aprobada\'', 1, '2025-07-22 19:55:58'),
(29, 8, 5, 'Cotización convertida a venta y stock descontado', 1, '2025-07-22 19:56:08'),
(30, 9, 1, 'Cotización creada con 1 productos por un total de $611.47', 1, '2025-07-22 20:04:55'),
(31, 9, 3, 'Estado cambiado de \'Enviada\' a \'Aprobada\'', 1, '2025-07-22 20:05:01'),
(32, 9, 5, 'Cotización convertida a venta y stock descontado', 1, '2025-07-22 20:05:15'),
(33, 9, 7, 'Se devolvieron 1 unidades del producto ID 53', 1, '2025-07-22 20:55:13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cotizaciones_insumos`
--

CREATE TABLE `cotizaciones_insumos` (
  `cotizacion_insumo_id` int(11) NOT NULL,
  `cotizacion_id` int(11) NOT NULL,
  `insumo_id` int(11) NOT NULL,
  `nombre_insumo` varchar(255) NOT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `proveedor` varchar(100) DEFAULT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `precio_total` decimal(10,2) NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `stock_disponible` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cotizaciones_insumos`
--

INSERT INTO `cotizaciones_insumos` (`cotizacion_insumo_id`, `cotizacion_id`, `insumo_id`, `nombre_insumo`, `categoria`, `proveedor`, `cantidad`, `precio_unitario`, `precio_total`, `imagen`, `stock_disponible`) VALUES
(2, 5, 4, 'pijas', 'Accesorios', '0', 5.00, 0.00, 0.00, NULL, 0.93),
(4, 6, 3, 'Conectores', 'Accesorios', '0', 5.00, 0.00, 0.00, NULL, 1.98),
(5, 7, 4, 'pijas', 'Accesorios', 'Syscom', 50.00, 0.00, 0.00, NULL, 5.93),
(6, 4, 4, 'pijas', 'Accesorios', '0', 10.00, 2.00, 20.00, NULL, 0.93),
(7, 8, 3, 'Conectores', 'Accesorios', 'Amazon', 1.00, 0.00, 0.00, NULL, 0.00),
(8, 9, 3, 'Conectores', 'Accesorios', 'Amazon', 1.00, 0.00, 0.00, NULL, 1.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cotizaciones_productos`
--

CREATE TABLE `cotizaciones_productos` (
  `cotizacion_producto_id` int(11) NOT NULL,
  `cotizacion_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `precio_unitario` decimal(10,2) DEFAULT NULL,
  `precio_total` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cotizaciones_productos`
--

INSERT INTO `cotizaciones_productos` (`cotizacion_producto_id`, `cotizacion_id`, `product_id`, `cantidad`, `precio_unitario`, `precio_total`) VALUES
(1, 1, 30, 4, 444.28, 1777.12),
(2, 2, 30, 1, 444.28, 444.28),
(3, 3, 30, 1, 444.28, 444.28),
(10, 5, 53, 5, 461.47, 2307.35),
(12, 6, 53, 4, 461.47, 1845.88),
(13, 7, 53, 1, 461.47, 461.47),
(14, 4, 53, 5, 461.47, 2307.35),
(15, 8, 53, 1, 461.47, 461.47),
(16, 9, 53, 1, 461.47, 461.47);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cotizaciones_servicios`
--

CREATE TABLE `cotizaciones_servicios` (
  `cotizacion_servicio_id` int(11) NOT NULL,
  `cotizacion_id` int(11) NOT NULL,
  `servicio_id` int(11) DEFAULT NULL,
  `nombre_servicio` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `precio_total` decimal(10,2) NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cotizaciones_servicios`
--

INSERT INTO `cotizaciones_servicios` (`cotizacion_servicio_id`, `cotizacion_id`, `servicio_id`, `nombre_servicio`, `descripcion`, `cantidad`, `precio_unitario`, `precio_total`, `imagen`, `created_at`) VALUES
(1, 1, 6, 'Consultoría técnica', 'Asesoramiento técnico para sistemas de seguridad.', 4.00, 100.00, 400.00, NULL, '2025-07-17 04:58:51'),
(2, 2, 6, 'Consultoría técnica', 'Asesoramiento técnico para sistemas de seguridad.', 1.00, 100.00, 100.00, '', '2025-07-17 05:01:04'),
(3, 3, 10, 'gsd', 'fdsfg', 1.00, 33.00, 33.00, 'servicio_1752722174_68786afe81abb.png', '2025-07-17 05:02:28'),
(10, 5, NULL, 'Instalación de cámara', 'Instalación completa de cámara de seguridad incluyendo cableado y configuración.', 5.00, 150.00, 750.00, '', '2025-07-22 17:58:00'),
(12, 6, NULL, 'Instalación de cámara', 'Instalación completa de cámara de seguridad incluyendo cableado y configuración.', 2.00, 150.00, 300.00, '', '2025-07-22 19:30:10'),
(13, 7, 1, 'Instalación de cámara', 'Instalación completa de cámara de seguridad incluyendo cableado y configuración.', 1.00, 150.00, 150.00, '', '2025-07-22 19:32:56'),
(14, 4, NULL, 'Instalación de cámara', 'Instalación completa de cámara de seguridad incluyendo cableado y configuración.', 5.00, 150.00, 750.00, '', '2025-07-22 19:54:30'),
(15, 8, 1, 'Instalación de cámara', 'Instalación completa de cámara de seguridad incluyendo cableado y configuración.', 1.00, 150.00, 150.00, '', '2025-07-22 19:55:54'),
(16, 9, 1, 'Instalación de cámara', 'Instalación completa de cámara de seguridad incluyendo cableado y configuración.', 1.00, 150.00, 150.00, '', '2025-07-22 20:04:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipos`
--

CREATE TABLE `equipos` (
  `equipo_id` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `estado` enum('activo','inactivo','en_reparacion') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipos_asignaciones`
--

CREATE TABLE `equipos_asignaciones` (
  `asignacion_id` int(11) NOT NULL,
  `equipo_id` int(11) DEFAULT NULL,
  `tecnico_id` int(11) DEFAULT NULL,
  `fecha_asignacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_devolucion` timestamp NULL DEFAULT NULL,
  `estado` enum('asignado','devuelto','perdido') DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estado_tecnico`
--

CREATE TABLE `estado_tecnico` (
  `estado_id` int(11) NOT NULL,
  `nombre_estado` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `est_cotizacion`
--

CREATE TABLE `est_cotizacion` (
  `est_cot_id` int(11) NOT NULL,
  `nombre_estado` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `est_cotizacion`
--

INSERT INTO `est_cotizacion` (`est_cot_id`, `nombre_estado`) VALUES
(1, 'Borrador'),
(2, 'Enviada'),
(3, 'Aprobada'),
(4, 'Rechazada'),
(5, 'Convertida');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `insumos`
--

CREATE TABLE `insumos` (
  `insumo_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `unidad` varchar(20) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `cantidad` decimal(12,4) DEFAULT NULL,
  `minimo` decimal(10,2) DEFAULT NULL,
  `precio_unitario` decimal(10,2) DEFAULT NULL,
  `ubicacion` varchar(100) DEFAULT NULL,
  `estado` enum('disponible','bajo_stock','agotado') DEFAULT NULL,
  `consumo_semanal` decimal(10,2) DEFAULT NULL,
  `ultima_actualizacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `insumos`
--

INSERT INTO `insumos` (`insumo_id`, `product_id`, `category_id`, `supplier_id`, `nombre`, `categoria`, `unidad`, `imagen`, `cantidad`, `minimo`, `precio_unitario`, `ubicacion`, `estado`, `consumo_semanal`, `ultima_actualizacion`, `is_active`, `created_at`, `updated_at`) VALUES
(1, NULL, 5, 6, 'Conectores', 'Accesorios', 'bolsa', 'uploads/insumos/insumo_1753065188_2581.png', 0.0000, 0.00, 0.00, '', 'agotado', 0.00, '2025-07-21 14:16:17', 0, '2025-07-21 02:33:08', '2025-07-21 14:20:08'),
(2, NULL, 5, 6, 'Conectores', 'Accesorios', 'bolsa (1000 piezas)', 'uploads/insumos/insumo_1753066903_6104.png', 0.9800, 0.00, 0.00, '', 'disponible', 0.00, '2025-07-21 03:26:58', 0, '2025-07-21 03:01:43', '2025-07-21 14:20:28'),
(3, NULL, 5, 6, 'Conectores', 'Accesorios', 'bolsa (1000 piezas)', NULL, 0.9990, 0.00, 0.00, '', 'disponible', 0.00, '2025-07-22 20:04:16', 1, '2025-07-21 03:17:22', '2025-07-22 20:05:15'),
(4, NULL, 5, 4, 'pijas', 'Accesorios', 'bolsa (500 piezas)', NULL, 0.9300, 0.00, 0.00, '', 'disponible', 0.00, '2025-07-22 20:04:33', 1, '2025-07-21 17:12:16', '2025-07-22 20:04:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `insumos_movements`
--

CREATE TABLE `insumos_movements` (
  `insumo_movement_id` int(11) NOT NULL,
  `insumo_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `tipo_movimiento` enum('entrada','salida') DEFAULT NULL,
  `cantidad` decimal(12,4) DEFAULT NULL,
  `piezas_movidas` decimal(10,2) DEFAULT NULL COMMENT 'Cantidad exacta de piezas movidas, si aplica',
  `motivo` text DEFAULT NULL,
  `fecha_movimiento` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `insumos_movements`
--

INSERT INTO `insumos_movements` (`insumo_movement_id`, `insumo_id`, `user_id`, `tipo_movimiento`, `cantidad`, `piezas_movidas`, `motivo`, `fecha_movimiento`) VALUES
(1, 2, 1, 'salida', 0.0300, NULL, 'Trabajo', '2025-07-21 03:06:47'),
(2, 3, 1, 'salida', 0.0300, NULL, 'sf', '2025-07-21 03:17:48'),
(3, 1, 1, 'salida', 1.0000, NULL, 'si', '2025-07-21 14:16:17'),
(4, 3, 1, 'entrada', 0.0300, NULL, 'Sobra', '2025-07-21 14:40:29'),
(5, 3, 1, 'salida', 0.0100, NULL, 'si', '2025-07-21 14:41:05'),
(6, 3, 1, 'salida', 0.0300, NULL, 'wo', '2025-07-21 14:41:25'),
(7, 3, 1, 'entrada', 0.0300, NULL, 'zo', '2025-07-21 14:41:44'),
(8, 3, 1, 'salida', 0.0300, NULL, 'as', '2025-07-21 14:42:01'),
(9, 3, 1, 'entrada', 0.0200, NULL, 'ask', '2025-07-21 14:42:28'),
(10, 3, 1, 'salida', 0.0300, NULL, 'ma', '2025-07-21 14:42:50'),
(11, 3, 1, 'entrada', 0.0250, 25.00, 'sk', '2025-07-21 15:14:58'),
(12, 3, 1, 'salida', 0.0250, 25.00, 'des', '2025-07-21 15:15:10'),
(13, 3, 1, 'entrada', 0.0200, 20.00, 'dd', '2025-07-21 15:15:24'),
(14, 3, 1, 'salida', 0.0250, 25.00, 'fs', '2025-07-21 15:15:38'),
(15, 4, 1, 'entrada', 1.0000, 500.00, 'Ajuste', '2025-07-21 17:13:09'),
(16, 4, 1, 'salida', 0.0700, 35.00, 'Chamba', '2025-07-21 18:37:10'),
(17, 3, 1, 'entrada', 3.0000, 3000.00, 'Ajuste', '2025-07-22 19:30:42'),
(18, 3, 1, 'entrada', 0.0250, 25.00, 'ajuste', '2025-07-22 19:31:05'),
(19, 4, 1, 'entrada', 10.0000, 5000.00, 'ajuste', '2025-07-22 19:31:51'),
(20, 4, 1, 'entrada', 2.0000, 1000.00, 'ds', '2025-07-22 19:34:07'),
(21, 4, 1, 'entrada', 42.0000, NULL, 'ajuste', '2025-07-22 19:34:22'),
(22, 4, 1, 'entrada', 1.0000, NULL, 'ajuste', '2025-07-22 19:34:42'),
(23, 3, 1, 'entrada', 2.0000, NULL, 'ajuste', '2025-07-22 20:04:16'),
(24, 4, 1, 'entrada', 10.0000, NULL, 'si', '2025-07-22 20:04:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `logs`
--

CREATE TABLE `logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `logs`
--

INSERT INTO `logs` (`log_id`, `user_id`, `action`, `description`, `created_at`) VALUES
(1, 1, 'login', 'Usuario Admin inició sesión', '2025-07-12 05:23:35'),
(2, 1, 'login', 'Usuario Admin inició sesión', '2025-07-13 03:01:23'),
(3, 1, 'login', 'Usuario Admin inició sesión', '2025-07-14 04:40:50'),
(4, 1, 'login', 'Usuario admin inició sesión', '2025-07-14 04:57:22'),
(5, 1, 'login', 'Usuario admin inició sesión', '2025-07-14 05:10:42'),
(6, 1, 'login', 'Usuario admin inició sesión', '2025-07-14 14:13:34'),
(7, 1, 'login', 'Usuario Admin inició sesión', '2025-07-14 14:59:44'),
(8, 1, 'login', 'Usuario admin inició sesión', '2025-07-14 15:53:22'),
(9, 1, 'login', 'Usuario admin inició sesión', '2025-07-14 19:02:57'),
(10, 1, 'login', 'Usuario admin inició sesión', '2025-07-14 19:04:17'),
(11, 1, 'login', 'Usuario admin inició sesión', '2025-07-14 20:38:58'),
(12, 1, 'login', 'Usuario admin inició sesión', '2025-07-14 22:32:23'),
(13, 1, 'login', 'Usuario admin inició sesión', '2025-07-14 22:45:22'),
(14, 1, 'login', 'Usuario admin inició sesión', '2025-07-15 14:04:18'),
(15, 1, 'login', 'Usuario admin inició sesión', '2025-07-15 15:15:26'),
(16, 1, 'login', 'Usuario admin inició sesión', '2025-07-15 18:51:08'),
(17, 1, 'login', 'Usuario admin inició sesión', '2025-07-15 21:48:32'),
(18, 1, 'login', 'Usuario admin inició sesión', '2025-07-18 07:23:47'),
(19, 1, 'login', 'Usuario admin inició sesión', '2025-07-19 20:25:32'),
(20, 1, 'login', 'Usuario admin inició sesión', '2025-07-20 21:12:13'),
(21, 1, 'login', 'Usuario admin inició sesión', '2025-07-21 14:09:12'),
(22, 1, 'login', 'Usuario admin inició sesión', '2025-07-27 23:49:18');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movements`
--

CREATE TABLE `movements` (
  `movement_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `bobina_id` int(11) DEFAULT NULL,
  `movement_type_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `movement_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `tecnico_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `movements`
--

INSERT INTO `movements` (`movement_id`, `product_id`, `bobina_id`, `movement_type_id`, `quantity`, `unit_price`, `total_amount`, `reference`, `notes`, `user_id`, `movement_date`, `created_at`, `updated_at`, `tecnico_id`) VALUES
(1, 1, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-11 20:13:41', '2025-07-11 20:13:41', '2025-07-11 20:13:41', NULL),
(2, 2, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-11 20:41:25', '2025-07-11 20:41:25', '2025-07-11 20:41:25', NULL),
(3, 3, NULL, 1, 5, NULL, NULL, NULL, NULL, NULL, '2025-07-11 20:44:13', '2025-07-11 20:44:13', '2025-07-11 20:44:13', NULL),
(4, 4, NULL, 1, 4, NULL, NULL, NULL, NULL, NULL, '2025-07-11 20:47:38', '2025-07-11 20:47:38', '2025-07-11 20:47:38', NULL),
(5, 5, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-11 20:53:33', '2025-07-11 20:53:33', '2025-07-11 20:53:33', NULL),
(6, 6, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-11 20:55:08', '2025-07-11 20:55:08', '2025-07-11 20:55:08', NULL),
(7, 7, NULL, 1, 2, NULL, NULL, NULL, NULL, NULL, '2025-07-11 21:04:49', '2025-07-11 21:04:49', '2025-07-11 21:04:49', NULL),
(8, 8, NULL, 1, 2, NULL, NULL, NULL, NULL, NULL, '2025-07-11 21:06:48', '2025-07-11 21:06:48', '2025-07-11 21:06:48', NULL),
(9, 9, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-11 21:09:04', '2025-07-11 21:09:04', '2025-07-11 21:09:04', NULL),
(10, 10, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-11 21:12:48', '2025-07-11 21:12:48', '2025-07-11 21:12:48', NULL),
(11, 11, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-11 21:19:43', '2025-07-11 21:19:43', '2025-07-11 21:19:43', NULL),
(12, 12, NULL, 1, 13, NULL, NULL, NULL, NULL, NULL, '2025-07-11 21:23:50', '2025-07-11 21:23:50', '2025-07-11 21:23:50', NULL),
(13, 13, NULL, 1, 3, NULL, NULL, NULL, NULL, NULL, '2025-07-11 21:28:28', '2025-07-11 21:28:28', '2025-07-11 21:28:28', NULL),
(14, 14, NULL, 1, 5, NULL, NULL, NULL, NULL, NULL, '2025-07-11 22:06:30', '2025-07-11 22:06:30', '2025-07-11 22:06:30', NULL),
(15, 15, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-11 22:11:20', '2025-07-11 22:11:20', '2025-07-11 22:11:20', NULL),
(16, 16, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-11 22:14:10', '2025-07-11 22:14:10', '2025-07-11 22:14:10', NULL),
(17, 17, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-11 22:18:39', '2025-07-11 22:18:39', '2025-07-11 22:18:39', NULL),
(18, 18, NULL, 1, 2, NULL, NULL, NULL, NULL, NULL, '2025-07-11 22:21:54', '2025-07-11 22:21:54', '2025-07-11 22:21:54', NULL),
(19, 19, NULL, 1, 2, NULL, NULL, NULL, NULL, NULL, '2025-07-11 22:24:18', '2025-07-11 22:24:18', '2025-07-11 22:24:18', NULL),
(20, 20, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-11 22:25:36', '2025-07-11 22:25:36', '2025-07-11 22:25:36', NULL),
(21, 21, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-11 22:29:08', '2025-07-11 22:29:08', '2025-07-11 22:29:08', NULL),
(22, 22, NULL, 1, 19, NULL, NULL, NULL, NULL, NULL, '2025-07-11 22:31:15', '2025-07-11 22:31:15', '2025-07-11 22:31:15', NULL),
(23, 23, NULL, 1, 6, NULL, NULL, NULL, NULL, NULL, '2025-07-11 22:35:43', '2025-07-11 22:35:43', '2025-07-11 22:35:43', NULL),
(24, 24, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-11 22:37:57', '2025-07-11 22:37:57', '2025-07-11 22:37:57', NULL),
(25, 25, NULL, 1, 7, NULL, NULL, NULL, NULL, NULL, '2025-07-11 22:46:06', '2025-07-11 22:46:06', '2025-07-11 22:46:06', NULL),
(26, 26, NULL, 1, 26, NULL, NULL, NULL, NULL, NULL, '2025-07-11 22:49:55', '2025-07-11 22:49:55', '2025-07-11 22:49:55', NULL),
(27, 27, NULL, 1, 2, NULL, NULL, NULL, NULL, NULL, '2025-07-11 22:57:16', '2025-07-11 22:57:16', '2025-07-11 22:57:16', NULL),
(28, 28, NULL, 1, 20, NULL, NULL, NULL, NULL, NULL, '2025-07-11 23:00:44', '2025-07-11 23:00:44', '2025-07-11 23:00:44', NULL),
(29, 29, NULL, 1, 2, NULL, NULL, NULL, NULL, NULL, '2025-07-11 23:03:42', '2025-07-11 23:03:42', '2025-07-11 23:03:42', NULL),
(30, 30, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-11 23:10:33', '2025-07-11 23:10:33', '2025-07-11 23:10:33', NULL),
(31, 31, NULL, 1, 2, NULL, NULL, NULL, NULL, NULL, '2025-07-11 23:12:13', '2025-07-11 23:12:13', '2025-07-11 23:12:13', NULL),
(32, 32, NULL, 1, 5, NULL, NULL, NULL, NULL, NULL, '2025-07-14 14:21:26', '2025-07-14 14:21:26', '2025-07-14 14:21:26', NULL),
(33, 33, NULL, 1, 2, NULL, NULL, NULL, NULL, NULL, '2025-07-14 14:28:52', '2025-07-14 14:28:52', '2025-07-14 14:28:52', NULL),
(34, 34, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 14:32:38', '2025-07-14 14:32:38', '2025-07-14 14:32:38', NULL),
(35, 35, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 15:14:29', '2025-07-14 15:14:29', '2025-07-14 15:14:29', NULL),
(36, 36, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 15:23:27', '2025-07-14 15:23:27', '2025-07-14 15:23:27', NULL),
(37, 37, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, '2025-07-14 15:27:58', '2025-07-14 15:27:58', '2025-07-14 15:27:58', NULL),
(38, 38, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 15:33:34', '2025-07-14 15:33:34', '2025-07-14 15:33:34', NULL),
(39, 39, NULL, 1, 5, NULL, NULL, NULL, NULL, NULL, '2025-07-14 15:37:16', '2025-07-14 15:37:16', '2025-07-14 15:37:16', NULL),
(40, 40, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 15:41:48', '2025-07-14 15:41:48', '2025-07-14 15:41:48', NULL),
(41, 41, NULL, 1, 3, NULL, NULL, NULL, NULL, NULL, '2025-07-14 15:45:34', '2025-07-14 15:45:34', '2025-07-14 15:45:34', NULL),
(42, 42, NULL, 1, 3, NULL, NULL, NULL, NULL, NULL, '2025-07-14 15:48:20', '2025-07-14 15:48:20', '2025-07-14 15:48:20', NULL),
(43, 43, NULL, 1, 3, NULL, NULL, NULL, NULL, NULL, '2025-07-14 15:51:18', '2025-07-14 15:51:18', '2025-07-14 15:51:18', NULL),
(44, 44, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 15:57:53', '2025-07-14 15:57:53', '2025-07-14 15:57:53', NULL),
(45, 45, NULL, 1, 10, NULL, NULL, NULL, NULL, NULL, '2025-07-14 16:01:44', '2025-07-14 16:01:44', '2025-07-14 16:01:44', NULL),
(46, 46, NULL, 1, 4, NULL, NULL, NULL, NULL, NULL, '2025-07-14 16:04:29', '2025-07-14 16:04:29', '2025-07-14 16:04:29', NULL),
(47, 47, NULL, 1, 15, NULL, NULL, NULL, NULL, NULL, '2025-07-14 16:08:38', '2025-07-14 16:08:38', '2025-07-14 16:08:38', NULL),
(48, 48, NULL, 1, 6, NULL, NULL, NULL, NULL, NULL, '2025-07-14 16:25:29', '2025-07-14 16:25:29', '2025-07-14 16:25:29', NULL),
(49, 49, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 16:29:28', '2025-07-14 16:29:28', '2025-07-14 16:29:28', NULL),
(50, 50, NULL, 1, 182, NULL, NULL, NULL, NULL, NULL, '2025-07-14 16:35:23', '2025-07-14 16:35:23', '2025-07-14 16:35:23', NULL),
(51, 51, NULL, 1, 8, NULL, NULL, NULL, NULL, NULL, '2025-07-14 16:42:36', '2025-07-14 16:42:36', '2025-07-14 16:42:36', NULL),
(52, 52, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 16:46:48', '2025-07-14 16:46:48', '2025-07-14 16:46:48', NULL),
(53, 53, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 16:55:40', '2025-07-14 16:55:40', '2025-07-14 16:55:40', NULL),
(54, 54, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 16:58:24', '2025-07-14 16:58:24', '2025-07-14 16:58:24', NULL),
(55, 55, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 17:02:05', '2025-07-14 17:02:05', '2025-07-14 17:02:05', NULL),
(56, 56, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 17:07:19', '2025-07-14 17:07:19', '2025-07-14 17:07:19', NULL),
(57, 57, NULL, 1, 3, NULL, NULL, NULL, NULL, NULL, '2025-07-14 17:19:11', '2025-07-14 17:19:11', '2025-07-14 17:19:11', NULL),
(58, 58, NULL, 1, 5, NULL, NULL, NULL, NULL, NULL, '2025-07-14 17:26:43', '2025-07-14 17:26:43', '2025-07-14 17:26:43', NULL),
(59, 59, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 17:34:44', '2025-07-14 17:34:44', '2025-07-14 17:34:44', NULL),
(60, 60, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 17:37:45', '2025-07-14 17:37:45', '2025-07-14 17:37:45', NULL),
(61, 61, NULL, 1, 4, NULL, NULL, NULL, NULL, NULL, '2025-07-14 17:44:37', '2025-07-14 17:44:37', '2025-07-14 17:44:37', NULL),
(62, 50, NULL, 2, -1, NULL, NULL, NULL, NULL, 1, '2025-07-14 17:47:09', '2025-07-14 17:47:09', '2025-07-14 17:47:09', NULL),
(63, 23, NULL, 2, -4, NULL, NULL, NULL, NULL, 1, '2025-07-14 17:47:53', '2025-07-14 17:47:53', '2025-07-14 17:47:53', NULL),
(64, 3, NULL, 2, -1, NULL, NULL, NULL, NULL, 1, '2025-07-14 17:48:05', '2025-07-14 17:48:05', '2025-07-14 17:48:05', NULL),
(65, 62, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 17:50:59', '2025-07-14 17:50:59', '2025-07-14 17:50:59', NULL),
(66, 63, NULL, 1, 3, NULL, NULL, NULL, NULL, NULL, '2025-07-14 17:52:00', '2025-07-14 17:52:00', '2025-07-14 17:52:00', NULL),
(67, 64, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 18:03:24', '2025-07-14 18:03:24', '2025-07-14 18:03:24', NULL),
(68, 65, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 18:03:32', '2025-07-14 18:03:32', '2025-07-14 18:03:32', NULL),
(69, 66, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 18:07:17', '2025-07-14 18:07:17', '2025-07-14 18:07:17', NULL),
(70, 67, NULL, 1, 2, NULL, NULL, NULL, NULL, NULL, '2025-07-14 18:09:55', '2025-07-14 18:09:55', '2025-07-14 18:09:55', NULL),
(71, 68, NULL, 1, 3, NULL, NULL, NULL, NULL, NULL, '2025-07-14 18:15:07', '2025-07-14 18:15:07', '2025-07-14 18:15:07', NULL),
(72, 69, NULL, 1, 4, NULL, NULL, NULL, NULL, NULL, '2025-07-14 18:26:42', '2025-07-14 18:26:42', '2025-07-14 18:26:42', NULL),
(73, 70, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 19:07:56', '2025-07-14 19:07:56', '2025-07-14 19:07:56', NULL),
(74, 23, NULL, 2, -2, NULL, NULL, NULL, NULL, 1, '2025-07-14 19:11:18', '2025-07-14 19:11:18', '2025-07-14 19:11:18', NULL),
(75, 71, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 19:47:15', '2025-07-14 19:47:15', '2025-07-14 19:47:15', NULL),
(76, 72, NULL, 1, 305, NULL, NULL, NULL, NULL, NULL, '2025-07-14 19:57:57', '2025-07-14 19:57:57', '2025-07-14 21:34:38', NULL),
(85, 12, NULL, 1, 56, NULL, NULL, NULL, NULL, 1, '2025-07-14 21:28:18', '2025-07-14 21:28:18', '2025-07-14 21:28:18', NULL),
(87, 23, NULL, 1, 30, NULL, NULL, NULL, NULL, 1, '2025-07-14 21:30:09', '2025-07-14 21:30:09', '2025-07-14 21:30:09', NULL),
(89, 4, NULL, 1, 10, NULL, NULL, NULL, NULL, 1, '2025-07-14 21:31:44', '2025-07-14 21:31:44', '2025-07-14 21:31:44', NULL),
(91, 3, NULL, 1, 10, NULL, NULL, NULL, NULL, 1, '2025-07-14 21:32:16', '2025-07-14 21:32:16', '2025-07-14 21:32:16', NULL),
(92, 73, NULL, 1, 2, NULL, NULL, NULL, NULL, NULL, '2025-07-14 22:51:26', '2025-07-14 22:51:26', '2025-07-14 22:51:26', NULL),
(93, 3, NULL, 2, -2, NULL, NULL, NULL, NULL, 1, '2025-07-14 23:00:52', '2025-07-14 23:00:52', '2025-07-14 23:00:52', NULL),
(94, 12, NULL, 2, -5, NULL, NULL, NULL, NULL, 1, '2025-07-14 23:01:59', '2025-07-14 23:01:59', '2025-07-14 23:01:59', NULL),
(95, 59, NULL, 2, -1, NULL, NULL, NULL, NULL, 1, '2025-07-14 23:03:30', '2025-07-14 23:03:30', '2025-07-14 23:03:30', NULL),
(96, 23, NULL, 2, -1, NULL, NULL, NULL, NULL, 1, '2025-07-14 23:04:57', '2025-07-14 23:04:57', '2025-07-14 23:04:57', NULL),
(97, 51, NULL, 2, -1, NULL, NULL, NULL, NULL, 1, '2025-07-14 23:11:51', '2025-07-14 23:11:51', '2025-07-14 23:11:51', NULL),
(98, 73, NULL, 2, -2, NULL, NULL, NULL, NULL, 1, '2025-07-14 23:13:22', '2025-07-14 23:13:22', '2025-07-14 23:13:22', NULL),
(99, 57, NULL, 2, -2, NULL, NULL, NULL, NULL, 1, '2025-07-14 23:24:02', '2025-07-14 23:24:02', '2025-07-14 23:24:02', NULL),
(100, 65, NULL, 1, 2, NULL, NULL, NULL, NULL, 1, '2025-07-15 15:34:34', '2025-07-15 15:34:34', '2025-07-15 15:34:34', NULL),
(101, 61, NULL, 1, 2, NULL, NULL, NULL, NULL, NULL, '2025-07-15 18:04:45', '2025-07-15 18:04:45', '2025-07-15 18:04:45', NULL),
(102, 60, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-15 18:05:08', '2025-07-15 18:05:08', '2025-07-15 18:05:08', NULL),
(103, 62, NULL, 1, 1, NULL, NULL, NULL, NULL, 1, '2025-07-15 18:09:55', '2025-07-15 18:09:55', '2025-07-15 18:09:55', NULL),
(104, 62, NULL, 1, 1, NULL, NULL, NULL, NULL, 1, '2025-07-15 18:10:07', '2025-07-15 18:10:07', '2025-07-15 18:10:07', NULL),
(105, 62, NULL, 1, 1, NULL, NULL, NULL, NULL, 1, '2025-07-15 18:10:17', '2025-07-15 18:10:17', '2025-07-15 18:10:17', NULL),
(106, 62, NULL, 1, 1, NULL, NULL, NULL, NULL, 1, '2025-07-15 18:11:29', '2025-07-15 18:11:29', '2025-07-15 18:11:29', NULL),
(107, 62, NULL, 1, 1, NULL, NULL, NULL, NULL, 1, '2025-07-15 18:11:30', '2025-07-15 18:11:30', '2025-07-15 18:11:30', NULL),
(108, 62, NULL, 1, 1, NULL, NULL, NULL, NULL, 1, '2025-07-15 18:19:00', '2025-07-15 18:19:00', '2025-07-15 18:19:00', NULL),
(109, 62, NULL, 1, 1, NULL, NULL, NULL, NULL, 1, '2025-07-15 18:19:13', '2025-07-15 18:19:13', '2025-07-15 18:19:13', NULL),
(110, 62, NULL, 1, 1, NULL, NULL, NULL, NULL, 1, '2025-07-15 18:22:42', '2025-07-15 18:22:42', '2025-07-15 18:22:42', NULL),
(111, 62, NULL, 1, 1, NULL, NULL, NULL, NULL, 1, '2025-07-15 18:22:58', '2025-07-15 18:22:58', '2025-07-15 18:22:58', NULL),
(112, 62, NULL, 1, 1, NULL, NULL, NULL, NULL, 1, '2025-07-15 18:26:24', '2025-07-15 18:26:24', '2025-07-15 18:26:24', NULL),
(113, 60, NULL, 1, 1, NULL, NULL, NULL, NULL, 1, '2025-07-15 18:33:12', '2025-07-15 18:33:12', '2025-07-15 18:33:12', NULL),
(114, 60, NULL, 1, 1, NULL, NULL, NULL, NULL, 1, '2025-07-15 18:33:15', '2025-07-15 18:33:15', '2025-07-15 18:33:15', NULL),
(115, 60, NULL, 1, 1, NULL, NULL, NULL, NULL, 1, '2025-07-15 18:33:15', '2025-07-15 18:33:15', '2025-07-15 18:33:15', NULL),
(116, 60, NULL, 1, 1, NULL, NULL, NULL, NULL, 1, '2025-07-15 18:33:15', '2025-07-15 18:33:15', '2025-07-15 18:33:15', NULL),
(117, 60, NULL, 1, 1, NULL, NULL, NULL, NULL, 1, '2025-07-15 18:33:15', '2025-07-15 18:33:15', '2025-07-15 18:33:15', NULL),
(118, 60, NULL, 1, 1, NULL, NULL, NULL, NULL, 1, '2025-07-15 18:33:16', '2025-07-15 18:33:16', '2025-07-15 18:33:16', NULL),
(119, 60, NULL, 3, 1, NULL, NULL, NULL, NULL, 1, '2025-07-15 18:33:56', '2025-07-15 18:33:56', '2025-07-15 18:33:56', NULL),
(120, 62, NULL, 1, 1, NULL, NULL, NULL, NULL, 1, '2025-07-15 18:34:12', '2025-07-15 18:34:12', '2025-07-15 18:34:12', NULL),
(121, 72, 2, 1, 305, NULL, NULL, NULL, NULL, 1, '2025-07-15 18:34:34', '2025-07-15 18:34:34', '2025-07-15 18:34:34', NULL),
(122, 62, NULL, 1, 1, NULL, NULL, NULL, NULL, 1, '2025-07-15 18:36:19', '2025-07-15 18:36:19', '2025-07-15 18:36:19', NULL),
(123, 62, NULL, 1, 1, NULL, NULL, NULL, NULL, 1, '2025-07-15 18:36:26', '2025-07-15 18:36:26', '2025-07-15 18:36:26', NULL),
(124, 62, NULL, 1, 1, NULL, NULL, NULL, NULL, 1, '2025-07-15 18:38:06', '2025-07-15 18:38:06', '2025-07-15 18:38:06', NULL),
(125, 62, NULL, 1, 1, NULL, NULL, NULL, NULL, 1, '2025-07-15 18:38:26', '2025-07-15 18:38:26', '2025-07-15 18:38:26', NULL),
(126, 60, NULL, 1, 1, NULL, NULL, NULL, NULL, 1, '2025-07-15 18:43:06', '2025-07-15 18:43:06', '2025-07-15 18:43:06', NULL),
(127, 60, NULL, 1, 1, NULL, NULL, NULL, NULL, 1, '2025-07-15 18:43:11', '2025-07-15 18:43:11', '2025-07-15 18:43:11', NULL),
(128, 60, NULL, 1, 1, NULL, NULL, NULL, NULL, 1, '2025-07-15 18:47:19', '2025-07-15 18:47:19', '2025-07-15 18:47:19', NULL),
(129, 60, NULL, 1, 1, NULL, NULL, NULL, NULL, 1, '2025-07-15 18:47:56', '2025-07-15 18:47:56', '2025-07-15 18:47:56', NULL),
(130, 62, NULL, 1, 1, NULL, NULL, NULL, NULL, 1, '2025-07-15 18:49:10', '2025-07-15 18:49:10', '2025-07-15 18:49:10', NULL),
(131, 62, NULL, 1, 1, NULL, NULL, NULL, NULL, 1, '2025-07-15 18:51:24', '2025-07-15 18:51:24', '2025-07-15 18:51:24', NULL),
(132, 62, NULL, 1, 1, NULL, NULL, NULL, NULL, 1, '2025-07-15 18:52:42', '2025-07-15 18:52:42', '2025-07-15 18:52:42', NULL),
(133, 60, NULL, 2, -1, NULL, NULL, NULL, NULL, 1, '2025-07-16 17:10:59', '2025-07-16 17:10:59', '2025-07-16 17:10:59', NULL),
(134, 72, 2, 1, 305, NULL, NULL, NULL, NULL, 1, '2025-07-16 19:36:51', '2025-07-16 19:36:51', '2025-07-16 19:36:51', NULL),
(135, 62, NULL, 1, 1, NULL, NULL, NULL, NULL, 1, '2025-07-16 19:37:02', '2025-07-16 19:37:02', '2025-07-16 19:37:02', NULL),
(136, 72, 3, 1, 305, NULL, NULL, NULL, NULL, 1, '2025-07-16 19:41:39', '2025-07-16 19:41:39', '2025-07-16 19:41:39', NULL),
(137, 72, 2, 2, -305, NULL, NULL, NULL, NULL, 1, '2025-07-16 19:42:56', '2025-07-16 19:42:56', '2025-07-16 19:42:56', NULL),
(138, 72, 2, 2, -610, NULL, NULL, NULL, NULL, 1, '2025-07-16 19:43:34', '2025-07-16 19:43:34', '2025-07-16 19:43:34', NULL),
(139, 72, 3, 2, -305, NULL, NULL, NULL, NULL, 1, '2025-07-16 19:44:27', '2025-07-16 19:44:27', '2025-07-16 19:44:27', NULL),
(140, 72, 2, 1, 305, NULL, NULL, NULL, NULL, 1, '2025-07-16 19:52:04', '2025-07-16 19:52:04', '2025-07-16 19:52:04', NULL),
(141, 72, 3, 7, 305, NULL, NULL, NULL, NULL, 1, '2025-07-16 20:00:09', '2025-07-16 20:00:09', '2025-07-16 20:00:09', NULL),
(142, 72, 4, 1, 305, NULL, NULL, NULL, NULL, 1, '2025-07-16 20:00:27', '2025-07-16 20:00:27', '2025-07-16 20:00:27', NULL),
(143, 72, 5, 1, 305, NULL, NULL, NULL, NULL, 1, '2025-07-16 20:12:55', '2025-07-16 20:12:55', '2025-07-16 20:12:55', NULL),
(144, 72, 6, 1, 305, NULL, NULL, NULL, NULL, 1, '2025-07-16 20:13:48', '2025-07-16 20:13:48', '2025-07-16 20:13:48', NULL),
(145, 72, 2, 7, 305, NULL, NULL, NULL, NULL, 1, '2025-07-21 14:55:34', '2025-07-21 14:55:34', '2025-07-21 14:55:34', NULL),
(146, 72, 2, 2, -30, NULL, NULL, NULL, NULL, 1, '2025-07-21 14:56:00', '2025-07-21 14:56:00', '2025-07-21 14:56:00', NULL),
(147, 72, 2, 2, -305, NULL, NULL, NULL, NULL, 1, '2025-07-21 14:57:58', '2025-07-21 14:57:58', '2025-07-21 14:57:58', NULL),
(148, 72, 2, 2, -20, NULL, NULL, NULL, NULL, 1, '2025-07-21 14:57:58', '2025-07-21 14:57:58', '2025-07-21 14:57:58', NULL),
(149, 72, 7, 1, 305, NULL, NULL, NULL, NULL, 1, '2025-07-21 15:03:00', '2025-07-21 15:03:00', '2025-07-21 15:03:00', NULL),
(150, 72, 6, 2, -305, NULL, NULL, NULL, NULL, 1, '2025-07-21 15:03:15', '2025-07-21 15:03:15', '2025-07-21 15:03:15', NULL),
(151, 72, 6, 7, 305, NULL, NULL, NULL, NULL, 1, '2025-07-21 15:03:33', '2025-07-21 15:03:33', '2025-07-21 15:03:33', NULL),
(152, 72, 2, 2, -255, NULL, NULL, NULL, NULL, 1, '2025-07-21 15:03:57', '2025-07-21 15:03:57', '2025-07-21 15:03:57', NULL),
(153, 72, 7, 3, 305, NULL, NULL, NULL, NULL, 1, '2025-07-21 15:04:18', '2025-07-21 15:04:18', '2025-07-21 15:04:18', NULL),
(154, 72, 2, 1, 305, NULL, NULL, NULL, NULL, 1, '2025-07-21 15:04:35', '2025-07-21 15:04:35', '2025-07-21 15:04:35', NULL),
(155, 72, 7, 3, 140, NULL, NULL, NULL, NULL, 1, '2025-07-21 15:29:31', '2025-07-21 15:29:31', '2025-07-21 15:29:31', NULL),
(156, 72, 7, 3, -50, NULL, NULL, NULL, NULL, 1, '2025-07-21 15:31:42', '2025-07-21 15:31:42', '2025-07-21 15:31:42', NULL),
(157, 53, NULL, 4, -4, NULL, NULL, 'Venta por cotización: COT-2025-0006', 'Conversión de cotización a venta', 1, '2025-07-22 18:37:45', '2025-07-22 18:37:45', '2025-07-22 18:37:45', NULL),
(158, 53, NULL, 4, -5, NULL, NULL, 'Venta por cotización: COT-2025-0005', 'Conversión de cotización a venta', 1, '2025-07-22 19:29:29', '2025-07-22 19:29:29', '2025-07-22 19:29:29', NULL),
(159, 53, NULL, 4, -1, NULL, NULL, 'Venta por cotización: COT-2025-0007', 'Conversión de cotización a venta', 1, '2025-07-22 19:33:17', '2025-07-22 19:33:17', '2025-07-22 19:33:17', NULL),
(160, 53, NULL, 4, -5, NULL, NULL, 'Venta por cotización: COT-2025-0004', 'Conversión de cotización a venta', 1, '2025-07-22 19:54:44', '2025-07-22 19:54:44', '2025-07-22 19:54:44', NULL),
(161, 53, NULL, 4, -1, NULL, NULL, 'Venta por cotización: COT-2025-0008', 'Conversión de cotización a venta', 1, '2025-07-22 19:56:08', '2025-07-22 19:56:08', '2025-07-22 19:56:08', NULL),
(162, 53, NULL, 4, -1, NULL, NULL, 'Venta por cotización: COT-2025-0009', 'Conversión de cotización a venta', 1, '2025-07-22 20:05:15', '2025-07-22 20:05:15', '2025-07-22 20:05:15', NULL),
(163, 53, NULL, 7, 1, NULL, NULL, 'Devolución por cotización #9', 'Devolución de producto tras venta', 1, '2025-07-22 20:55:13', '2025-07-22 20:55:13', '2025-07-22 20:55:13', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movement_types`
--

CREATE TABLE `movement_types` (
  `movement_type_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `is_entry` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `movement_types`
--

INSERT INTO `movement_types` (`movement_type_id`, `name`, `is_entry`) VALUES
(1, 'Entrada', 1),
(2, 'Salida', 0),
(3, 'Ajuste', 1),
(4, 'Venta', 1),
(5, 'Compra', 1),
(6, 'Transferencia', 1),
(7, 'Devolucion', 1),
(8, 'Perdida', 1),
(9, 'Merma', 1),
(10, 'Merma', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `precios_proveedores`
--

CREATE TABLE `precios_proveedores` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(300) DEFAULT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `barcode` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `min_stock` int(11) DEFAULT NULL,
  `max_stock` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `tipo_gestion` enum('normal','bobina','bolsa','par','kit') DEFAULT NULL,
  `unit_measure` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `products`
--

INSERT INTO `products` (`product_id`, `product_name`, `sku`, `barcode`, `price`, `cost_price`, `quantity`, `min_stock`, `max_stock`, `supplier_id`, `category_id`, `description`, `image`, `tipo_gestion`, `unit_measure`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'DAHUA IPC-HDBW1235E-W-S2', 'DHT0040056', '6939554996726', 1165.42, 896.48, 1, NULL, NULL, 1, 2, 'DAHUA IPC-HDBW1235E-W-S2 - Camara IP Domo Wifi de 2 Megapixeles/ Lente de 2.8 mm/ 113 Grados de Apertura/ IR de 30 Metros/ H.265/ Ranura para MicroSD/ IP67/ Antivandalica IK10/ DWDR/', 'uploads/products/prod_687170751cb4f.png', 'normal', '0', NULL, '2025-07-11 20:13:41', '2025-07-11 20:13:41'),
(2, 'DAHUA XVR1B08', 'DHT0360018', '6923172547330', 1661.48, 1278.06, 1, NULL, NULL, 1, 3, 'DAHUA XVR1B08-I-SSD - DVR de 8 canales 1080p Lite/ Con disco SSD de 512GB especial para Videovigilancia/ S-XVR Series/ WizSense/ H.265+/ 4 canales con SMD Plus/ Búsqueda inteligente (Humanos y vehículos) / #S-XVR #DVM', 'uploads/products/prod_687176f55b254.png', 'normal', '0', NULL, '2025-07-11 20:41:25', '2025-07-11 20:41:25'),
(3, 'DAHUA XVR1B08-I -DVR de 8 Canales', 'DHT0360008', '6923172504043', 664.33, 511.02, 12, NULL, NULL, 1, 3, 'DAHUA XVR1B08-I -DVR de 8 Canales 1080p Lite WizSense y Cooper-I. Soporta H.265+, hasta 10 canales IP, y 4 canales con SMD Plus para detección avanzada. Incluye búsqueda inteligente de personas y vehículos y codificación inteligente #DAHQ1M #LF#VolDH', 'uploads/products/prod_6871779dcdf71.png', 'normal', '0', NULL, '2025-07-11 20:44:13', '2025-07-14 23:00:52'),
(4, 'DAHUA XVR1B04-I -DVR de 4 Canales', 'DHT0350010', '6923172503992', 530.17, 407.82, 14, NULL, NULL, 1, 4, 'DAHUA XVR1B04-I -DVR de 4 Canales 1080p LiteWizSense y Cooper-I. Compatible con H.265+, admite hasta 5 canales IP y 4 canales con SMD Plus para detección avanzada. Búsqueda inteligente de personas y vehículos, codificación eficiente #DAHQ1M #LF#VolDH', 'uploads/products/prod_687178eec45a9.png', 'normal', '0', NULL, '2025-07-11 20:47:38', '2025-07-14 21:31:44'),
(5, 'DAHUA PFB203W', 'DAH124015', '6939554903632', 228.98, 176.14, 1, NULL, NULL, 1, 5, 'DAHUA PFB203W - Brazo de pared para camaras domo DAHUA / HDW2120 / 2220 / 2221RZ / HDW1000 / 1100R / HDW1120 / 1220 / 1320S / HDW2120', 'uploads/products/prod_687179cd3f5df.png', 'normal', '0', NULL, '2025-07-11 20:53:33', '2025-07-11 20:53:33'),
(6, 'Camara IP PTZ de 4 MP TiOC de 5x de Zoom Optico', 'DHT0060100', '6923172548184', 3303.44, 2541.11, 1, NULL, NULL, 1, 6, 'DAHUA SD3E405DB-GNY-A-PV1 - Camara IP PTZ de 4 MP TiOC de 5x de Zoom Optico/ Iluminación Dual Inteligente/ Disuasión Activa con Luz Roja y Azul/ IR de 50 Metros/ Micrófono y Altavoz Integrado/ Audio 2 Vías/ Ranura para MicroSD/ IP66/ PoE', 'uploads/products/prod_68717ac1057bb.png', 'normal', 'piezas', NULL, '2025-07-11 20:55:08', '2025-07-11 20:57:57'),
(7, 'Cámara Oculta en Sensor de Movimiento/ 2 Megapixeles', 'DHT0310017', '6923172544629', 678.08, 521.60, 2, NULL, NULL, 1, 7, 'DAHUA HAC-HUM3200A - Cámara Oculta en Sensor de Movimiento/ 2 Megapixeles/ Super Adapt/ Lente de 2.8mm/ 105 Grados de Apertura/ IR de 10 Metros/ DWDR/ Soporta: CVI/CVBS/AHD/TVI/', 'uploads/products/prod_68717c7143317.png', 'normal', '0', NULL, '2025-07-11 21:04:49', '2025-07-11 21:04:49'),
(8, 'Cámara PTZ Mini Domo Antivandálica de 2 Megapíxeles', 'DHT0330021', '6923172591425', 2556.53, 1966.56, 2, NULL, NULL, 1, 6, 'DAHUA SD22204DB-GC - Cámara PTZ Mini Domo Antivandálica de 2 Megapíxeles/ analógica / 4X de Zoom Óptico/ IP66/ IK10/ Starlight/ HLC/ 30FPS/ 3DNR/ #MCI2 #MCC', 'uploads/products/prod_68717ce855daf.png', 'normal', '0', NULL, '2025-07-11 21:06:48', '2025-07-11 21:06:48'),
(9, 'Control de Asistencia Stand Alone con Batería Incluida', 'DHT0800005', '6923172582454', 1193.70, 918.23, 1, NULL, NULL, 1, 8, 'DAHUA ASA1222GL-D - Control de Asistencia Stand Alone con Batería Incluida/ 1000 Usuarios, Passwords y Tarjetas ID/ 2000 Huellas/ 100,000 Registros de Asistencias/ Protocolos TCP/IP/UDP/IPv4/ USB p/Exportar Registros/ Horarios', 'uploads/products/prod_68717d7003e68.png', 'normal', '0', NULL, '2025-07-11 21:09:04', '2025-07-11 21:09:04'),
(10, 'Switch Poe de 10 Puertos/ 8 Puertos PoE 10/100/ 2 Puertos Uplink 10/100/1000', 'DH-CS4010-8ET-110', 'AK05447PAJ00002', 1506.52, 1165.91, 1, NULL, NULL, 1, 9, 'DAHUA CS4010-8ET-110 - Switch Poe de 10 Puertos/ 8 Puertos PoE 10/100/ 2 Puertos Uplink 10/100/1000/ 110 Watts Totales/ Administrable en la Nube por DoLynk Care/ PoE 250 Metros/ Carcasa Metalica/ Switching 5.6 Gbps/', 'uploads/products/prod_68717e50c2b50.png', 'normal', 'Pieza', NULL, '2025-07-11 21:12:48', '2025-07-14 22:36:56'),
(11, 'Cámara Domo de 2 Megapíxeles Antivandálica/ 1080p', 'DHT0300065', '6923172594860', 593.94, 456.88, 1, NULL, NULL, 1, 10, 'DAHUA HAC-HDBW1200EA - Cámara Domo de 2 Megapíxeles Antivandálica/ 1080p/ Lente 2.8 mm/ 115 Grados de Apertura/ IR de 40 Metros/ Super Adapt/ Protección IK10/ Uso Exterior IP67/ Soporta: HDCVI/TVI/AHD y CVBS/', 'uploads/products/prod_68717fef2d852.png', 'normal', '0', NULL, '2025-07-11 21:19:43', '2025-07-11 21:19:43'),
(12, 'DAHUA HAC-HFW1209CN-A-LED - Cámara Bullet Full Color 1080p/ Lente de 2.8 mm/ 106 Grados de Apertura/', 'DH-HAC-HFW1209CN-A-LED', '6939554916724', 443.90, 343.54, 64, NULL, NULL, 1, 11, 'DAHUA HAC-HFW1209CN-A-LED - Cámara Bullet Full Color 1080p/ Lente de 2.8 mm/ 106 Grados de Apertura/ Micrófono Integrado/ Luz Blanca de 20 Mts/ DWDR/ Starlight/ IP67/ Soporta CVI/AHD y CVBS #VolDH #IngDahua', 'uploads/products/prod_687180e662438.png', 'normal', '0', NULL, '2025-07-11 21:23:50', '2025-07-14 23:01:59'),
(13, 'Switch para Escritorio 5 Puertos Fast Ethernet con velocidad de transmisión de 10/100 Mbps en un dis', 'DHT3700020', '6923172574725', 117.34, 90.26, 3, NULL, NULL, 1, 12, 'DAHUA DH-SF1005L - Switch para Escritorio 5 Puertos Fast Ethernet con velocidad de transmisión de 10/100 Mbps en un diseño compacto. Su capa 2 soporta un switching de hasta 1 Gbps y una velocidad de reenvío de 0.744 Mbps. #SwitchDpxv #VolDH', 'uploads/products/prod_687181fc6e1cc.png', 'normal', '0', NULL, '2025-07-11 21:28:28', '2025-07-11 21:28:28'),
(14, 'Router Dahua Ethernet DH-N3', 'ROUDAH010', '6923172560100', 195.10, 150.08, 5, NULL, NULL, 2, 13, 'Router Dahua Ethernet DH-N3, Inalámbrico, 300Mbit/s, 4x RJ-45, 2.4GHz, 2 Antenas Externas NB', 'uploads/products/prod_68718ae67fe19.jpg', 'normal', '0', NULL, '2025-07-11 22:06:30', '2025-07-11 22:06:30'),
(15, 'DashCam equipada con Wi-Fi y G-Sensor, con capacidad de MicroSD de hasta 128 Gb y cuenta con Micrófo', 'DHT0390029', '6923172553324', 762.50, 586.54, 1, NULL, NULL, 1, 14, 'DAHUA M1pro - DashCam equipada con Wi-Fi y G-Sensor, con capacidad de MicroSD de hasta 128 Gb y cuenta con Micrófono y altavoz integrados. #LoNuevo #DAMOV #MDAB', 'uploads/products/prod_68718c08250bb.png', 'normal', '0', NULL, '2025-07-11 22:11:20', '2025-07-11 22:11:20'),
(16, 'Cámara de Tablero (DashCam)/ Soporta ADAS (Sistemas Avanzados de Asistencia al Conductor)', 'DHT0390030', '6939554913303', 1675.10, 1288.54, 1, NULL, NULL, 1, 14, 'DAHUA H10 - Cámara de Tablero (DashCam)/ Soporta ADAS (Sistemas Avanzados de Asistencia al Conductor)/2160P Ultra Alta Resolución/Conectividad Wi-Fi/ Sensor de Fuerza G/ Ranura MicroSD p/hasta 256 GB/ Micrófono y Altavoz integrados/ #LoNuevo #DAMOV #MDAB', 'uploads/products/prod_68718cb22dcf2.png', 'normal', '0', NULL, '2025-07-11 22:14:10', '2025-07-11 22:14:33'),
(17, 'Cámara HDCVI Pinhole 1080p/2 Megapíxeles', 'DHT0310001', '6939554977329', 932.91, 717.62, 1, NULL, NULL, 1, 7, 'DAHUA HAC-HUM3201B-P - Cámara HDCVI Pinhole 1080p/2 Megapíxeles/ Lente de 2.8 MM/ 103 Grados de Apertura/ 1 Entrada de Audio/ WDR/ BLC/ HLC/ Starlight', 'uploads/products/prod_68718dbf40a6f.jpg', 'normal', '0', NULL, '2025-07-11 22:18:39', '2025-07-11 22:18:39'),
(18, 'Interruptor Inalámbrico/ 1 Salida de Relevador', 'DHT1200001', '6923172558527', 447.21, 344.01, 2, NULL, NULL, 1, 5, 'DAHUA DHI-ARM7012-W2 - Interruptor Inalámbrico/ 1 Salida de Relevador NO/NC de 100–240 VAC Max 13 A/ Entrada de 100-240 Vca 50/60 Hz/ Comunicación Estable/ Detector de Interferencias/ Indicador de Estatus/ #alarmasdahua #MAYAL', 'uploads/products/prod_68718e82e288e.png', 'normal', '0', NULL, '2025-07-11 22:21:54', '2025-07-11 22:21:54'),
(19, 'Control Remoto Tipo Llavero de 4 Botones / Armado', 'DHT2480009', '6923172504586', 308.13, 237.02, 2, NULL, NULL, 1, 5, 'DAHUA DHI-ARA24-W2 - Control Remoto Tipo Llavero de 4 Botones / Armado - Desarmado - En Casa - Emergencia / Función de Salto de Frecuencia / Led Indicador de Estado Color Rojo o Verde / #AlarmasDahua #VM', 'uploads/products/prod_68718f1269aee.png', 'normal', '0', NULL, '2025-07-11 22:24:18', '2025-07-11 22:24:18'),
(20, 'Teclado Inalámbrico Interior Touch para Armado y Desarmado', 'DHI-ARK30T-W2', '6923172538680', 1055.56, 811.97, 1, NULL, NULL, 1, 5, 'DAHUA DHI-ARK30T-W2 - Teclado Inalámbrico Interior Touch para Armado y Desarmado / Soporta hasta 32 usuarios con Pin o Tarjetas Mifare / Indicadores Led de Status del Panel / Alarma de Batería Baja / #AlarmasDahua', 'uploads/products/prod_68718f60edfa4.png', 'normal', '0', NULL, '2025-07-11 22:25:36', '2025-07-14 22:34:23'),
(21, 'Sirena Inalámbrica para Exterior con Estrobo Rojo', 'DHT2480007', '6923172535627', 1567.06, 1205.43, 1, NULL, NULL, 1, 15, 'DAHUA DHI-ARA13-W2 - Sirena Inalámbrica para Exterior con Estrobo Rojo/ 110dB / Múltiples sonidos de Alarma/ IP65/ Alarma de Batería Baja/ #AlarmasDahua #VM #AMYV', 'uploads/products/prod_687190347d911.png', 'normal', '0', NULL, '2025-07-11 22:29:08', '2025-07-11 22:29:08'),
(22, 'DAHUA HAC-HFW1509C-LED-28 - Cámara Bullet Full Color de 5 Megapixeles/ Lente de 2.8 mm/ 112 Grados d', 'DH-HAC-HFW1509CN-LED-0280B', '6923172522788', 682.40, 524.92, 19, NULL, NULL, 1, 11, 'DAHUA HAC-HFW1509C-LED-28 - Cámara Bullet Full Color de 5 Megapixeles/ Lente de 2.8 mm/ 112 Grados de Apertura/ Leds para 20 Mts/ WDR de 120 dB/ Starlight/ IP67/ #ProHDCVI #FULLC #1CM #MCI2 #MCC', 'uploads/products/prod_687190b36d03f.png', 'normal', '0', NULL, '2025-07-11 22:31:15', '2025-07-14 23:00:04'),
(23, 'Cámara domo Dahua de 2 MP con lente de 2.8 mm, ángulo de 102 grados, IR de 40 m, micrófono integrado', 'DHT0300064', '6923172593672', 462.90, 356.08, 29, NULL, NULL, 1, 10, 'DAHUA HAC-HDW1200TQ-A-Cámara domo Dahua de 2 MP con lente de 2.8 mm, ángulo de 102 grados, IR de 40 m, micrófono integrado, instalación rápida, DWDR, IP67, y diseño en metal y policarbonato.', 'uploads/products/prod_687191bfe0834.png', 'normal', '0', NULL, '2025-07-11 22:35:43', '2025-07-14 23:04:57'),
(24, 'Cámara Domo FullColor de 2 MP con resolución 1080p, lente de 2.8 mm y 106° de apertura. Ofrece visió', 'DHT0300073', '6939554921483', 430.11, 330.85, 1, NULL, NULL, 1, 10, 'DAHUA DH-HAC-HDW1209TLQN-LED-0280B-S3 - Cámara Domo FullColor de 2 MP con resolución 1080p, lente de 2.8 mm y 106° de apertura. Ofrece visión nocturna de 20 metros, instalación rápida, protección IP67, tecnología Starlight y DWDR.', 'uploads/products/prod_6871924592d74.png', 'normal', '0', NULL, '2025-07-11 22:37:57', '2025-07-11 22:37:57'),
(25, 'Cámara Domo con resolución 1080p, lente de 2.8 mm y ángulo de visión de 103°, Smart IR de 20 m para ', 'SCA397013', '6939554970535', 252.46, 194.20, 7, NULL, NULL, 1, 7, 'DAHUA HAC-T1A21-28 - Cámara Domo con resolución 1080p, lente de 2.8 mm y ángulo de visión de 103°, Smart IR de 20 m para mejor visión nocturna, ideal para interiores. Compatible con los formatos CVI, TVI, AHD y CVBS. #DAHQ1M', 'uploads/products/prod_6871942eb6f3a.png', 'normal', '0', NULL, '2025-07-11 22:46:06', '2025-07-11 22:46:06'),
(26, 'Cámara Domo de 2 Megapixeles/ Lente 2.8 mm', 'DHT0300069', '6923172582003', 254.50, 195.77, 26, NULL, NULL, 1, 10, 'DAHUA HAC-T1A21N-U-28 - Cámara Domo de 2 Megapixeles/ Lente 2.8 mm / 100 Grados de Apertura/ Smart ir 25 Mts/ Uso Interior/ CVI/TVI/AHD/CBVS/ #DAHQ1M #VFL #1CM#VolDH', 'uploads/products/prod_687195135af8a.png', 'normal', '0', NULL, '2025-07-11 22:49:55', '2025-07-11 22:49:55'),
(27, 'Cámara IP PT de 2 Megapíxeles Full Color/ Disuasión Activa', 'DHT0060020', '6923172526588', 2288.20, 1760.15, 2, NULL, NULL, 1, 2, 'DAHUA SD3A200-GN-A-PV - Cámara IP PT de 2 Megapíxeles Full Color/ Disuasión Activa/ Lente Fijo/ Luz Blanca de 30 Metros/ IR de 30 Metros/ H.265+/ Ranura MicroSD/ Audio Bidireccional con Altavoz Integrado/ IP66/ PoE/', 'uploads/products/prod_687196ccc7e5b.png', 'normal', '0', NULL, '2025-07-11 22:57:16', '2025-07-11 22:57:16'),
(28, 'Camara IP Bullet de 2 MP, lente 2.8 mm, 99° de visión, IR 30 m, IP67 y PoE.', 'DHT0030158', '6939554913990', 639.60, 492.00, 20, NULL, NULL, 1, 2, 'DAHUA IPC-B1E20 - Camara IP Bullet de 2 MP, lente 2.8 mm, 99° de visión, IR 30 m, IP67 y PoE. Incluye DWDR, 3D NR, HLC, BLC y compresión H.265+ para videovigilancia eficiente #SwitchD1 #MDIP #D50#VolDH', 'uploads/products/prod_6871979c5421e.png', 'normal', '0', NULL, '2025-07-11 23:00:44', '2025-07-11 23:00:44'),
(29, 'DAHUA SD2A200HB-GN-A-PV-S2 - Camara IP PT de 2 Megapixeles/ Full Color+Disuasion Activa/ Iluminador', 'DHT0060054', '6923172581488', 1328.29, 1021.76, 2, NULL, NULL, 1, 6, 'DAHUA SD2A200HB-GN-A-PV-S2 - Camara IP PT de 2 Megapixeles/ Full Color+Disuasion Activa/ Iluminador Dual Inteligente/ Lente fijo/ 30 Metros de Iluminación IR y Visible/ Audio 2 Vias/ IP66/ PoE/ Detección de Humanos/ Ranura MicroSD/', 'uploads/products/prod_687198871606a.png', 'normal', '0', NULL, '2025-07-11 23:03:42', '2025-07-11 23:04:39'),
(30, 'Camara Bullet de 2 Megapixeles/ Lente Fijo de 3.6mm', 'DH-HAC-HFW1200TN-0360B-S4', '6939554990274', 444.28, 341.75, 1, NULL, NULL, 1, 31, 'DAHUA HAC-HFW1200T-36- Camara Bullet de 2 Megapixeles/ Lente Fijo de 3.6mm/ 83 Grados de Apertura/ Smart IR 30 Metros/ IP67/ Metalica/ BLC/ HLC/ DWDR/ TVI AHD y CBVS/', 'uploads/products/prod_687199e92e522.png', 'normal', '0', NULL, '2025-07-11 23:10:33', '2025-07-14 19:11:13'),
(31, 'Camara Bullet HDCVI 1080p/ Lente 3.6 mm', 'DAH395016', '6923172592569', 638.04, 490.80, 2, NULL, NULL, 1, 31, 'DAHUA HAC-HFW1200D-036- Camara Bullet HDCVI 1080p/ Lente 3.6 mm/ 87.5 Grados de Apertura/ Smart IR 80 Mts/ IP67/ Metálica/ DWDR/ BLC/ HLC/ TVI AHD y CVBS #IngDahua\r\nTipo: Más Vendidos    Etapa: De Línea', 'uploads/products/prod_68719a4d94a15.png', 'normal', '0', NULL, '2025-07-11 23:12:13', '2025-07-14 17:00:11'),
(32, 'DAHUA DHI-ARD323-W2(S) - Contacto Magnético Inalámbrico Interior/ Diseño Compacto/ 1 Entrada de Cont', 'DHI- ARD323- W2(S)', '6923172531483', 399.09, 306.99, 5, NULL, NULL, 1, 17, 'DAHUA DHI-ARD323-W2(S) - Contacto Magnético Inalámbrico Interior/ Diseño Compacto/ 1 Entrada de Contacto Seco / Led Indicador / Alarma de Batería Baja / Detección de Intensidad de Señal / Función de Salto de Frecuencia/ #Alarmasdahua #MAYAL #AMYV', 'uploads/products/prod_68751266507d5.png', 'normal', '0', NULL, '2025-07-14 14:21:26', '2025-07-14 14:21:26'),
(33, 'DAHUA DHI-ARD1233-W2 - Detector PIR Inalámbrico Interior/ Inmunidad de Mascotas/ Led Indicador/ 3 Ni', 'DHI-ARD1233 - W2', '6923172504715', 672.84, 517.57, 2, NULL, NULL, 1, 17, 'DAHUA DHI-ARD1233-W2 - Detector PIR Inalámbrico Interior/ Inmunidad de Mascotas/ Led Indicador/ 3 Niveles de Sensibilidad/ Compensación Automática de Temperatura/ Alarma de Batería Baja/ #AlarmasDahua #AMYV', 'uploads/products/prod_687514243694a.png', 'normal', '0', NULL, '2025-07-14 14:28:52', '2025-07-14 14:28:52'),
(34, 'DAHUA DHI-ART-ARC3000H-03-W2 - Kit de Alarma Inalámbrico con Conexión Wifi y Ethernet / Monitoreo po', 'DHI-ART-ARC3000H-03-W2', '6923172522870', 2777.05, 2136.19, 1, NULL, NULL, 1, 18, 'DAHUA DHI-ART-ARC3000H-03-W2 - Kit de Alarma Inalámbrico con Conexión Wifi y Ethernet / Monitoreo por APP / Incluye Panel WiFi Ethernet; Un Sensor de Movimiento; Un Contacto Magnético; Un Control Remoto/ #AlarmasDahua #DICDAL #Anivdahua4', 'uploads/products/prod_68751506b02ac.png', 'normal', '0', NULL, '2025-07-14 14:32:38', '2025-07-14 14:32:38'),
(35, 'ZKTECO ZD192KSB - Monitor LED HD de 19 pulgadas / Operación 24/7 Ideal para Seguridad/ Resolución 14', 'ZD19-2K', 'WCH202404280541', 1273.73, 979.79, 1, NULL, NULL, 1, 19, 'ZKTECO ZD192KSB - Monitor LED HD de 19 pulgadas / Operación 24/7 Ideal para Seguridad/ Resolución 1440 x 900 / 1 Entrada de video HDMI y 1 VGA / Ángulo de Visión Horizontal 170° / Soporte VESA / Incluye Cable HDMI / Sin Altavoces #HD1 #MCI2', 'uploads/products/prod_68751ed5b780e.png', 'normal', '0', NULL, '2025-07-14 15:14:29', '2025-07-14 15:14:29'),
(36, 'ZKTECO MB10VL - Control de Asistencia y Acceso Básico Visible Light con Autenticación Facial (100 ro', 'MB10VL', 'CMYD232460060', 1868.30, 1437.15, 1, NULL, NULL, 1, 27, 'ZKTECO MB10VL - Control de Asistencia y Acceso Básico Visible Light con Autenticación Facial (100 rostros), Huella Digital BioID (500), Registro de 50,000 Eventos, Conexión TCP/IP y SSR (Reporte en Hoja de Cálculo Mediante USB) #ZKL #CM1', 'uploads/products/prod_687520ef4aebe.webp', 'normal', '0', NULL, '2025-07-14 15:23:27', '2025-07-14 15:23:27'),
(37, 'SAXXON OUTPCAT5ECOPEXT - Bobina de Cable UTP Cat5e 100% Cobre/ 305 Metros/ Exterior con Doble Forro/', 'AUTO-0001', 'TVD119047', 7.00, 2123.41, 305, NULL, NULL, 1, 28, 'SAXXON OUTPCAT5ECOPEXT - Bobina de Cable UTP Cat5e 100% Cobre/ 305 Metros/ Exterior con Doble Forro/ Color Negro/ Ideal para Cableado de Redes de Datos y Video/', 'uploads/products/prod_687521fe8539c.png', 'bobina', '0', NULL, '2025-07-14 15:27:58', '2025-07-14 15:27:58'),
(38, 'IMOU RANGER IQ (IPC-A26HIN-imou) - Cámara IP PT de 2 Megapíxeles/ WiFi/ Con Gateway de Alarma/Detecc', 'IPC-A26HIN-imou', '6939554968617', 2166.62, 1666.63, 1, NULL, NULL, 1, 29, 'IMOU RANGER IQ (IPC-A26HIN-imou) - Cámara IP PT de 2 Megapíxeles/ WiFi/ Con Gateway de Alarma/Detección de Humanos con IA/Lente de 3.6 mm/ AutoTracking/ Sirena Incorporada Personalizable/ Audio 2 Vias/ Modo de Privacidad/ Alarma de Sonido Anormal/', 'uploads/products/prod_6875234ee6520.png', 'normal', '0', NULL, '2025-07-14 15:33:34', '2025-07-14 15:33:34'),
(39, 'IMOU RANGER PRO (IPC-A26HN) - Cámara IP Domo Motorizado 2 Megapíxeles/ Audio Bidireccional/ Auto Tra', 'IPC-A26HN (Ranger Pro)', '6939554961076', 1420.09, 1092.38, 5, NULL, NULL, 1, 29, 'IMOU RANGER PRO (IPC-A26HN) - Cámara IP Domo Motorizado 2 Megapíxeles/ Audio Bidireccional/ Auto Tracking/ Modo de Privacidad/ Lente de 3.6mm/ Ir 10 Mts/ WiFi/ Compatible con Alexa y Asistente de Google/', 'uploads/products/prod_6875242c9d789.png', 'normal', '0', NULL, '2025-07-14 15:37:16', '2025-07-14 15:37:16'),
(40, 'DAHUA ASI1201E-D - Control de Acceso Independiente con Teclado Touch y Tarjetas ID/ 30,000 Usuarios,', 'DHI-ASI1201E-D', '6923172514646', 872.63, 671.25, 1, NULL, NULL, 1, 30, 'DAHUA ASI1201E-D - Control de Acceso Independiente con Teclado Touch y Tarjetas ID/ 30,000 Usuarios, 60,000 Registros/ TCP/IP/ Soporta Lectora Esclavo por Wiegand y RS-485/ Uso Exterior IP66/ Desbloqueo con Tarjeta, Pasword o Combinación/ #BuenFinDahua20', 'uploads/products/prod_6875253cdab1c.png', 'normal', '0', NULL, '2025-07-14 15:41:48', '2025-07-14 15:41:48'),
(41, 'DAHUA DH-SF1006LP - Switch PoE de 6 Puertos Fast Ethernet/ 4 Puertos PoE 10/100/ 36 Watts Totales/ 2', 'DH-SF1006LP', '6923172571403', 487.83, 375.25, 3, NULL, NULL, 1, 9, 'DAHUA DH-SF1006LP - Switch PoE de 6 Puertos Fast Ethernet/ 4 Puertos PoE 10/100/ 36 Watts Totales/ 2 Puertos Uplink RJ45 10/100/ PoE Watchdog/ Soporta hasta 250mts sobre UTP CAT 6/ Protección Contra Descargas/', 'uploads/products/prod_6875261ed4540.png', 'normal', '0', NULL, '2025-07-14 15:45:34', '2025-07-14 15:45:34'),
(42, 'DAHUA SF1010LP - Switch PoE de 10 Puertos Fast Ethernet/ 8 Puertos PoE 10/100 / 65 Watts Totales / 2', 'DH-SF1010LP', '6923172571410', 852.88, 656.06, 3, NULL, NULL, 1, 9, 'DAHUA SF1010LP - Switch PoE de 10 Puertos Fast Ethernet/ 8 Puertos PoE 10/100 / 65 Watts Totales / 2 Puertos Uplink RJ-45/ PoE watchdog/ Hasta 250 metros/ Switching 12 Gbps/ Protección Contra Descargas', 'uploads/products/prod_687526c4247a1.png', 'normal', '0', NULL, '2025-07-14 15:48:20', '2025-07-14 15:48:20'),
(43, 'DAHUA HAC-PT1200B-IL-A-E2Z - Cámara PT Dual de 2 Megapíxeles/ 6x Zoom Hibrido 2 Lentes/ Lentes de 2.', 'DH-HAC-PT1200BN-IL-A-E2Z', '6939554941788', 1214.49, 934.22, 3, NULL, NULL, 1, 6, 'DAHUA HAC-PT1200B-IL-A-E2Z - Cámara PT Dual de 2 Megapíxeles/ 6x Zoom Hibrido 2 Lentes/ Lentes de 2.8mm y 6mm/ Angulo de 109 y 47.2 mm/ Iluminador Dual Inteligente 50 Mts/ Micrófono Integrado/ Super Adapt/ IP66/ #LoNuevo #MCI1 #DP', 'uploads/products/prod_68752776bea16.png', 'normal', '0', NULL, '2025-07-14 15:51:18', '2025-07-14 15:51:18'),
(44, 'DAHUA ASI1201A-D- Teclado Touch para Control de Acceso con Paantalla LCD/ Lectora de Tarjetas ID/ Fu', 'DHI-ASI1201A-D', '6939554945380', 1565.03, 1203.87, 1, NULL, NULL, 1, 8, 'DAHUA ASI1201A-D- Teclado Touch para Control de Acceso con Paantalla LCD/ Lectora de Tarjetas ID/ Funcion Independiente/ 30,000 Usuarios/ 150,000 Registros/ Desbloqueo con Password y/o Tarjetas/ TCP/IP/ RS-485 y Wiegand/ Anti-passback/', 'uploads/products/prod_68752901ee378.png', 'normal', '0', NULL, '2025-07-14 15:57:53', '2025-07-14 15:57:53'),
(45, 'DAHUA HAC-HFW1200T-A - Cámara Bullet HDCVI 1080p micrófono integrado, lente 2.8 mm, ángulo de visión', 'DH-HAC-HFW1200TN-A-0280B-S4', '6923172593115', 457.13, 351.64, 10, NULL, NULL, 1, 31, 'DAHUA HAC-HFW1200T-A - Cámara Bullet HDCVI 1080p micrófono integrado, lente 2.8 mm, ángulo de visión de 103°, IR 30 m, IP67, carcasa metálica, DWDR, BLC, HLC. Ideal para vigilancia con alta definición y resistencia en exteriores. #MCI2', 'uploads/products/prod_687529e82512a.png', 'normal', '0', NULL, '2025-07-14 16:01:44', '2025-07-14 16:01:44'),
(46, 'DAHUA HAC-PT1239A-A-LED - Camara PT de 2 Megapixeles HDCVI/ Full Color/ Lente de 2.8 mm/ 106 Grados ', 'DH-HAC-PT1239AN-A-LED', '6923172599452', 880.80, 677.54, 4, NULL, NULL, 1, 6, 'DAHUA HAC-PT1239A-A-LED - Camara PT de 2 Megapixeles HDCVI/ Full Color/ Lente de 2.8 mm/ 106 Grados de Apertura/ Microfono Integrado/ 40 Metros de Iluminación LED/ Super Adapt/ WDR Real de 130 dB/ IP66/', 'uploads/products/prod_68752a8d240fd.png', 'normal', '0', NULL, '2025-07-14 16:04:29', '2025-07-14 16:04:29'),
(47, 'TRANSCEPTORES HD 2MP ENSON ENS-VT100 AHD/TVI/CVI PUSH-IN CON CONECTOR, AISLADOR DE RUIDO Y PROTECTOR', 'ENS-VT100', NULL, 27.17, 20.90, 15, NULL, NULL, 3, 5, 'TRANSCEPTORES HD 2MP ENSON ENS-VT100 AHD/TVI/CVI PUSH-IN CON CONECTOR, AISLADOR DE RUIDO Y PROTECTOR DE VOLTAJE. CONECTOR 100% COBRE', 'uploads/products/prod_68752b86c202e.jpg', 'normal', '0', NULL, '2025-07-14 16:08:38', '2025-07-14 16:08:38'),
(48, 'IMOU Cruiser SC 3MP (IPC-K7FN-3H0WE) - Cámara IP PT de 3 Megapíxeles/ Wifi/ Full Color/Disuasión act', 'IPC-K7FN-3H0WE', '6976391034020', 1192.44, 917.26, 6, NULL, NULL, 1, 29, 'IMOU Cruiser SC 3MP (IPC-K7FN-3H0WE) - Cámara IP PT de 3 Megapíxeles/ Wifi/ Full Color/Disuasión activa luces Rojo-Azul/ Audio 2 Vías/ 30 Metros Visión Nocturna/ Sirena de 110 dB/ Smart tracking/ Ranura para MicroSD/ IP66/ #TopIMOU #CONGIMOU1', 'uploads/products/prod_68752f7970be1.png', 'normal', '0', NULL, '2025-07-14 16:25:29', '2025-07-14 16:25:29'),
(49, 'IMOU Cruiser SE+ 3MP (IPC-K7CN-3H1WE) - Cámara IP PT de 3MP con WiFi ofrece Full Color, audio bidire', 'IPC-K7CN-3H1WE', '6976391038943', 998.57, 768.13, 1, NULL, NULL, 1, 29, 'IMOU Cruiser SE+ 3MP (IPC-K7CN-3H1WE) - Cámara IP PT de 3MP con WiFi ofrece Full Color, audio bidireccional, 30m, micrófono y altavoz, disuasión activa sirena 110dB, autotracking, ranura MicroSD e IP66. #TopIMOU #INGJUL', 'uploads/products/prod_687530684564d.png', 'normal', '0', NULL, '2025-07-14 16:29:28', '2025-07-14 16:29:28'),
(50, 'Camara Bullet de 2 Megapixeles', 'DH-HAC-B1A21N-U-0280B', '6923172577917', 257.27, 197.90, 181, NULL, NULL, 1, 31, 'DAHUA HAC-B1A21N-U-28 - Camara Bullet de 2 Megapixeles/ Lente de 2.8 mm/ 30 Metros de IR/ 100 Grados de Apertura/ IP67/ Soporta: CVI/TVI/AHD y CVBS/ #LoNuevo #VolDH #M1', 'uploads/products/prod_687531cbac53b.png', 'normal', '0', NULL, '2025-07-14 16:35:23', '2025-07-14 17:47:09'),
(51, 'DAHUA IPC-WPT1339DD-SW-3E2-PV -Cámara IP PT Wifi Dual de 6 MP/ Dos lentes de 3 MP cada uno (fijo y P', 'DH-IPC-WPT1339DD-SW-3E2-PV', '6939554932311', 1249.33, 961.02, 7, NULL, NULL, 1, 29, 'DAHUA IPC-WPT1339DD-SW-3E2-PV -Cámara IP PT Wifi Dual de 6 MP/ Dos lentes de 3 MP cada uno (fijo y PT), Iluminador Dual Inteligente, IR 50m, Microfono y Altavoz Integrados, IA, Autotracking, Disuasión activa, Ranura MicroSD, IP66#LoNuevo #DDPT #DHWifi #MC', 'uploads/products/prod_6875337cf0e5c.png', 'normal', '0', NULL, '2025-07-14 16:42:36', '2025-07-14 23:11:51'),
(52, 'Dahua IPC-WPT1539DD-SW-5E2-PV - Cámara IP PT Wifi Dual de 10 MP, 2 Lentes de 5 MP cada uno (Fijo y P', 'DH-IPC-WPT1539DD-SW-5E2-PV', '6939554932489', 1385.11, 1065.47, 1, NULL, NULL, 1, 29, 'Dahua IPC-WPT1539DD-SW-5E2-PV - Cámara IP PT Wifi Dual de 10 MP, 2 Lentes de 5 MP cada uno (Fijo y PT), Iluminador Dual Inteligente/ IR de 50m, Microfono y Altavoz Integrados, IA, Autotracking, Disuación activa, Ranura MicroSD, IP66 #LoNuevo #DDPT #DHWifi', 'uploads/products/prod_68753478a3c10.png', 'normal', '0', NULL, '2025-07-14 16:46:48', '2025-07-14 16:46:48'),
(53, 'DAHUA HAC-B1A51N-0280B - Cámara Bullet 5 Megapixeles con lente de 2.8 mm y ángulo de 106°. Visión nocturna IR de hasta 20 m, certificación IP67 para exteriores, compatible con CVI, CVBS, AHD y TVI. #H', 'DH-HAC-B1A51N-0280B', 'PRODCM12098159', 461.47, 354.98, -15, NULL, NULL, 1, 31, 'DAHUA HAC-B1A51N-0280B - Cámara Bullet 5 Megapixeles con lente de 2.8 mm y ángulo de 106°. Visión nocturna IR de hasta 20 m, certificación IP67 para exteriores, compatible con CVI, CVBS, AHD y TVI. #HDCVI9.0 #5MP #VIVA #TECNOWEEN #TW1.', 'uploads/products/prod_6879f6cff195c.png', 'normal', '0', NULL, '2025-07-14 16:55:40', '2025-07-22 20:55:13'),
(54, 'DAHUA PFA150 - Montaje para poste compatible con camaras PTZ series SD65XX / SD69 / SD63 / SD64 / SD', 'DH-PFA150-V2', '6939554903717', 474.25, 364.81, 1, NULL, NULL, 1, 32, 'DAHUA PFA150 - Montaje para poste compatible con camaras PTZ series SD65XX / SD69 / SD63 / SD64 / SD6A / SD6C', 'uploads/products/prod_6875373006060.png', 'normal', '0', NULL, '2025-07-14 16:58:24', '2025-07-14 16:58:24'),
(55, 'Camara bullet HDCVI 4 MP Metalica', 'DH-HAC-B2A41N-0280B', 'PRODCAM12328029', 541.29, 416.38, 1, NULL, NULL, 1, 31, 'DAHUA COOPER B2A41 - Camara bullet HDCVI 4 MP / TVI / A HD / CVBS / Lente 2.8 mm / Smart ir 20 Mts / IP67 / Apertura lente 97 grados / Metalica/', 'uploads/products/prod_6875380db06b3.png', 'normal', '0', NULL, '2025-07-14 17:02:05', '2025-07-14 17:02:05'),
(56, 'Cámara IP Domo Antivandalica 4k/ 8 Megapixeles', 'DH-IPC-HDBW2831EN-S-0280B-S2', 'PRODCM12724262', 2449.99, 1884.61, 1, NULL, NULL, 1, 10, 'DAHUA IPC-HDBW2831E-S-S2 -Cámara IP Domo Antivandalica 4k/ 8 Megapixeles/ Lente de 2.8mm/ 105 Grados de Apertura/ H.265+/ WDR Real de 120 dB/ IR de 30 Mts/ Videoanaliticos con IVS/ IP67/ IK10/ PoE/ Ranura para MicroSD/', 'uploads/products/prod_68753947d7aba.png', 'normal', '0', NULL, '2025-07-14 17:07:19', '2025-07-14 17:07:19'),
(57, 'Fuente de Alimentación de 4 Salidas de 11 - 15 Vcc / 5 Amper / Voltaje de Entrada 110- 240 Vac / Fus', 'PS-12-DC-4C', '697477291003', 254.88, 196.06, 1, NULL, NULL, 4, 33, 'Fuente de Alimentación de 4 Salidas de 11 - 15 Vcc / 5 Amper / Voltaje de Entrada 110- 240 Vac / Fusible Termico PTC Integrado para Protección / Salida de Voltaje Inteligente hasta 3 Amper por Salida', 'uploads/products/prod_68753c0fad4ae.png', 'normal', '0', NULL, '2025-07-14 17:19:11', '2025-07-14 23:24:02'),
(58, 'Cámara IP PT WiFi de 5 Megapíxeles con audio bidireccional', 'DH-P5B-PV', '6923172563477', 1047.15, 805.50, 5, NULL, NULL, 1, 2, 'DAHUA DH-P5B-PV- Cámara IP PT WiFi de 5 Megapíxeles con audio bidireccional (micrófono y altavoz), sirena de 110dB, WiFi 6, detección de personas y vehículos, IP65 y ranura MicroSD. #WiFiDahua#DHWifi', 'uploads/products/prod_68753dd30a2fe.png', 'normal', '0', NULL, '2025-07-14 17:26:43', '2025-07-14 17:27:07'),
(59, 'Cámara IP PT WiFi de 3 Megapíxeles con audio bidireccional', 'DH-P3B-PV', '6923172563460', 984.07, 756.98, 0, NULL, NULL, 1, 2, 'DAHUA DH-P3B-PV- Cámara IP PT WiFi de 3 Megapíxeles con audio bidireccional (micrófono y altavoz), sirena de 110 dB, WiFi 6, detección de personas y vehículos, IP65 y ranura MicroSD. #WiFiDahua#DHWifi', 'uploads/products/prod_68753fb492471.png', 'normal', '0', NULL, '2025-07-14 17:34:44', '2025-07-14 23:03:30'),
(60, '(AX PRO) Repetidor de Señal Hikvision / LED Indicador / Batería de Respaldo/ No compatible con el pa', 'DS-PR1-WB', '6941264077992', 1845.80, 1419.85, 12, NULL, NULL, 4, 34, '(AX PRO) Repetidor de Señal Hikvision / LED Indicador / Batería de Respaldo/ No compatible con el panel DS-PHA64-LP', 'uploads/products/prod_6875406929bc2.png', 'normal', '0', NULL, '2025-07-14 17:37:45', '2025-07-16 17:10:59'),
(61, 'Bala TURBOHD 2 Megapíxeles (1080p)', 'B8-TURBO-G2W', '300512030', 337.65, 259.73, 6, NULL, NULL, 4, 31, 'Bala TURBOHD 2 Megapíxeles (1080p) / METALICA / Gran Angular 103° / Lente 2.8 mm / IR EXIR Inteligente 20 mts / Exterior IP66 / TVI-AHD-CVI-CVBS', 'uploads/products/prod_687542053584b.png', 'normal', '0', NULL, '2025-07-14 17:44:37', '2025-07-15 18:04:45'),
(62, '(AX HYBRID PRO) Teclado Compatible con el Panel Hybrid Pro Hikvision DS-PHA64-LP y DS-PHA64-LP(B) / ', 'DS-PK1-LRT-HWB', '6931847164393', 1424.31, 1095.62, 20, NULL, NULL, 4, 5, '(AX HYBRID PRO) Teclado Compatible con el Panel Hybrid Pro Hikvision DS-PHA64-LP y DS-PHA64-LP(B) / Pantalla LCD / 2 zonas cableadas / 1 salida de alarma / 64 Llaveros', 'uploads/products/prod_68754383a52f6.png', 'normal', '0', NULL, '2025-07-14 17:50:59', '2025-07-16 19:37:03'),
(63, 'Kit de Videopotero Hibrido', 'DHI-KTH01', '6923172566768', 1996.03, 1535.41, 3, NULL, NULL, 1, 35, 'DAHUA DHI-KTH01 - Kit de Videopotero Hibrido/ Frente de Calle Analogico con Camara de 1.3 MP/ Monitor Touch de 7 Pulgadas WiFi/ 4 Hilos al Frente de Calle/ 6&1 E&S de Alarma/ Soporta Camaras IP/ Compattible App DMSS/ Apertura remota puerta / #VDP #MCI2', 'uploads/products/prod_687543c031ca2.png', 'normal', '0', NULL, '2025-07-14 17:52:00', '2025-07-14 17:52:00'),
(64, 'HiLook Series / Turret IP 4 Megapixel / 30 mts IR / Exterior IP67 / PoE / Lente 2.8 mm / WDR 120 dB ', 'IPC-T240H(C)', '6941264092483', 1307.48, 1005.75, 1, NULL, NULL, 4, 7, 'HiLook Series / Turret IP 4 Megapixel / 30 mts IR / Exterior IP67 / PoE / Lente 2.8 mm / WDR 120 dB / ONVIF', 'uploads/products/prod_6875466cb712c.png', 'normal', '0', NULL, '2025-07-14 18:03:24', '2025-07-14 18:03:24'),
(65, 'CABLE VORAGO VGA MACHO-MACHO 10 METROS', 'CAB-205', '', 374.15, 287.81, 3, NULL, NULL, 5, 5, 'CABLE VORAGO VGA MACHO-MACHO 10 METROS NEGRO CAB-205', 'uploads/products/prod_6879f6f160802.png', 'normal', '0', NULL, '2025-07-14 18:03:32', '2025-07-18 07:25:37'),
(66, 'Panel de Alarma AX HYBRID PRO', 'DS-PHA64-LP(B)', NULL, 2819.66, 2168.97, 1, NULL, NULL, 4, 15, 'Panel de Alarma AX HYBRID PRO / Wi-Fi / 8 Zonas Cableadas Directas al Panel / 56 Zonas Expandibles: Inalámbricas o Cableadas por Medio de Módulos / Soporta Integración de Batería de Respaldo / 32 Particiones (Áreas)', 'uploads/products/prod_6875475556234.png', 'normal', '0', NULL, '2025-07-14 18:07:17', '2025-07-14 18:07:17'),
(67, 'Router Inalámbrico WISP en Banda 2.4 GHz / Hasta 300 Mbps / 4 Puertos 10/100 Mbps / 2 Antenas Omnidi', 'DS-3WR3N', '6931847137236', 272.51, 209.62, 2, NULL, NULL, 4, 13, 'Router Inalámbrico WISP en Banda 2.4 GHz / Hasta 300 Mbps / 4 Puertos 10/100 Mbps / 2 Antenas Omnidireccional de 5 dBi / Interior', 'uploads/products/prod_687547f314c3d.png', 'normal', '0', NULL, '2025-07-14 18:09:55', '2025-07-14 18:09:55'),
(68, 'TP-Link MERCUSYS Halo Mesh', 'Halo H30(2-Pack)', '849439000669', 1285.57, 988.90, 3, NULL, NULL, 6, 36, 'TP-Link MERCUSYS Halo Mesh, Sistema WiFi Mesh para tu hogar, Doble Banda AC1200, 10/100Mbps Puertos, Cobertura de hasta 2,800 Pies Cuadrados y Más de 100 Dispositivos. Halo H30(2-Pack)', 'uploads/products/prod_6875492b48ccd.jpg', 'normal', '0', NULL, '2025-07-14 18:15:07', '2025-07-14 18:15:07'),
(69, 'DAHUA HAC-HFW1801CN-A-0280B-S2 - Cámara Bullet 4k con Micrófono Integrado/ 8 Megapixeles/ Lente de 2', 'DH-HAC-HFW1801CN-A', NULL, 900.45, 692.65, 4, NULL, NULL, 1, 31, 'DAHUA HAC-HFW1801CN-A-0280B-S2 - Cámara Bullet 4k con Micrófono Integrado/ 8 Megapixeles/ Lente de 2.8 mm/ 106 Grados de Apertura/ 30 Metros de IR/ WDR Real de 120 dB/ Soporta: CVI/TVI/AHD/CVBS/ #ProHDCVI', 'uploads/products/prod_68754be297a18.png', 'normal', '0', NULL, '2025-07-14 18:26:42', '2025-07-14 18:26:42'),
(70, 'Gabinete de Acero IP66 Uso en Intemperie (250 x 300 x 150 mm) con Placa Trasera Interior Metálica y ', 'PST-2530-15A', 'PRODGAB20063699', 768.16, 590.89, 1, NULL, NULL, 4, 37, 'Gabinete de Acero IP66 Uso en Intemperie (250 x 300 x 150 mm) con Placa Trasera Interior Metálica y Compuerta Inferior Atornillable (Incluye Chapa y Llave T).', 'uploads/products/prod_6875558ca5ad9.png', 'normal', '0', NULL, '2025-07-14 19:07:56', '2025-07-14 19:07:56'),
(71, 'GABINETE ENSON LINCE7', 'lince7', 'LINCE07ENE10', 256.10, 197.00, 1, 1, 5, 4, 37, 'GABINETE ENSON LINCE7 PARA SIRENA DE 15W COMPATIBLE CON MODELO PM-SRE108 Y COMPARTIMIENTO PARA TAMPER', 'uploads/products/prod_68755ec387ea7.png', 'normal', '0', NULL, '2025-07-14 19:47:15', '2025-07-14 19:47:15'),
(72, 'Cable UTP CCA, categoría 5E, color negro', 'SAXXON OUTP5ECCAEXT', 'TVD119038', 7.00, 867.88, 1310, 305, NULL, 1, 28, 'SAXXON OUTP5ECCAEXT - Cable UTP CCA, categoría 5E, color negro, 305 metros para exterior, con 4 pares y doble forro', 'uploads/products/prod_6875614596ed8.png', 'bobina', '0', NULL, '2025-07-14 19:57:57', '2025-07-21 15:31:42'),
(73, 'DISCO DE 1 TB NEW PULL 3.5', 'AUTO-0002', 'PRODDIS33296609', 680.11, 523.16, 0, 2, 5, 2, 38, 'Disco Duro GENERICO New Pull, 1 TB, SATA, 3.5 pulgadas, PC', 'uploads/products/prod_687589ee85059.png', 'normal', '0', NULL, '2025-07-14 22:51:26', '2025-07-14 23:13:22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`) VALUES
(1, 'Administrador');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicios`
--

CREATE TABLE `servicios` (
  `servicio_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `servicios`
--

INSERT INTO `servicios` (`servicio_id`, `nombre`, `descripcion`, `categoria`, `precio`, `imagen`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Instalación de cámara', 'Instalación completa de cámara de seguridad incluyendo cableado y configuración.', 'Instalación', 150.00, NULL, 1, '2025-07-17 01:03:53', '2025-07-17 01:03:53'),
(2, 'Configuración de DVR', 'Configuración y programación de grabador digital de video.', 'Configuración', 80.00, '0', 1, '2025-07-17 01:03:53', '2025-07-17 02:09:57'),
(3, 'Mantenimiento preventivo', 'Revisión y verificación de funcionamiento de equipos.', 'Mantenimiento', 120.00, NULL, 1, '2025-07-17 01:03:53', '2025-07-17 01:03:53'),
(4, 'Reparación de cableado', 'Reparación o reemplazo de cables de video y alimentación.', 'Reparación', 200.00, '0', 1, '2025-07-17 01:03:53', '2025-07-17 02:18:44'),
(5, 'Actualización de firmware', 'Actualización de software de equipos de seguridad.', 'Software', 60.00, NULL, 1, '2025-07-17 01:03:53', '2025-07-17 01:03:53'),
(6, 'Consultoría técnica', 'Asesoramiento técnico para sistemas de seguridad.', 'Consultoría', 100.00, NULL, 1, '2025-07-17 01:03:53', '2025-07-17 01:03:53'),
(7, 'Configuración remota', 'Configuración de equipos vía remota.', 'Configuración', 50.00, NULL, 1, '2025-07-17 01:03:53', '2025-07-17 01:03:53'),
(8, 'Capacitación de usuarios', 'Entrenamiento en el uso de sistemas de seguridad.', 'Capacitación', 150.00, 'servicio_1752722168_68786af858230.png', 1, '2025-07-17 01:03:53', '2025-07-17 03:16:08'),
(9, 'Capacitación de usuarios', 'DSSFAGF', 'Capacitación', 123.00, 'servicio_1752722101_68786ab5ee2f5.png', 0, '2025-07-17 01:50:08', '2025-07-17 03:16:00'),
(10, 'gsd', 'fdsfg', 'fdshgdf', 33.00, 'servicio_1752722174_68786afe81abb.png', 1, '2025-07-17 02:35:28', '2025-07-17 03:16:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `suppliers`
--

CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `suppliers`
--

INSERT INTO `suppliers` (`supplier_id`, `name`, `contact_name`, `phone`, `email`, `address`) VALUES
(1, 'TVC', '', '', '', ''),
(2, 'CT', '', '', '', ''),
(3, 'Tecnosinergia', '', '', '', ''),
(4, 'Syscom', '', '', '', ''),
(5, 'PCH', '', '', '', ''),
(6, 'Amazon', '', '', '', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `system_config`
--

CREATE TABLE `system_config` (
  `config_id` int(11) NOT NULL,
  `config_key` varchar(100) NOT NULL,
  `config_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tecnicos`
--

CREATE TABLE `tecnicos` (
  `tecnico_id` int(11) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `fecha_ingreso` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tecnicos`
--

INSERT INTO `tecnicos` (`tecnico_id`, `codigo`, `nombre`, `fecha_ingreso`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Si', 'Josue Chuc', '2005-02-17', 1, '2025-07-15 16:18:28', '2025-07-15 16:18:28');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role_id` int(11) NOT NULL,
  `custom_permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_permissions`)),
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `first_name`, `last_name`, `phone`, `role_id`, `custom_permissions`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@example.com', '$2y$10$hbvAV3/j8TccjhI8q8l9a.SmTbg8Y1uHf5LNEGKV3VrRIoGCLWyO6', 'Administrador', 'Sistema', NULL, 1, NULL, 1, NULL, '2025-07-08 18:13:35', '2025-07-08 18:13:35'),
(2, 'Raul', 'Trabajador@gmail.com', '$2y$10$Q00.4fa799x9fyQIDeVosO4/N6jUys6wcAAitcfL8suv.d44KOUfm', NULL, NULL, NULL, 1, NULL, 1, NULL, '2025-07-11 23:12:58', '2025-07-11 23:12:58');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_cotizaciones_complete`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_cotizaciones_complete` (
`cotizacion_id` int(11)
,`numero_cotizacion` varchar(20)
,`fecha_cotizacion` date
,`validez_dias` int(11)
,`subtotal` decimal(10,2)
,`descuento_porcentaje` decimal(5,2)
,`descuento_monto` decimal(10,2)
,`total` decimal(10,2)
,`condiciones_pago` text
,`observaciones` text
,`estado` varchar(50)
,`cliente_nombre` varchar(100)
,`usuario_creador` varchar(50)
,`productos_total` bigint(21)
,`subtotal_calculado` decimal(42,2)
);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `v_cotizaciones_historial`
--

CREATE TABLE `v_cotizaciones_historial` (
  `historial_id` int(11) DEFAULT NULL,
  `numero_cotizacion` varchar(20) DEFAULT NULL,
  `nombre_accion` varchar(50) DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `realizado_por` varchar(50) DEFAULT NULL,
  `fecha_accion` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_cotizaciones_complete`
--
DROP TABLE IF EXISTS `v_cotizaciones_complete`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u393681165_camarasyalarma`@`127.0.0.1` SQL SECURITY DEFINER VIEW `v_cotizaciones_complete`  AS SELECT `c`.`cotizacion_id` AS `cotizacion_id`, `c`.`numero_cotizacion` AS `numero_cotizacion`, `c`.`fecha_cotizacion` AS `fecha_cotizacion`, `c`.`validez_dias` AS `validez_dias`, `c`.`subtotal` AS `subtotal`, `c`.`descuento_porcentaje` AS `descuento_porcentaje`, `c`.`descuento_monto` AS `descuento_monto`, `c`.`total` AS `total`, `c`.`condiciones_pago` AS `condiciones_pago`, `c`.`observaciones` AS `observaciones`, `est`.`nombre_estado` AS `estado`, `cl`.`nombre` AS `cliente_nombre`, `u`.`username` AS `usuario_creador`, count(`cp`.`cotizacion_producto_id`) AS `productos_total`, sum(`cp`.`cantidad` * `cp`.`precio_unitario`) AS `subtotal_calculado` FROM ((((`cotizaciones` `c` left join `est_cotizacion` `est` on(`c`.`estado_id` = `est`.`est_cot_id`)) left join `clientes` `cl` on(`c`.`cliente_id` = `cl`.`cliente_id`)) left join `users` `u` on(`c`.`user_id` = `u`.`user_id`)) left join `cotizaciones_productos` `cp` on(`c`.`cotizacion_id` = `cp`.`cotizacion_id`)) GROUP BY `c`.`cotizacion_id` ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `bobinas`
--
ALTER TABLE `bobinas`
  ADD PRIMARY KEY (`bobina_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indices de la tabla `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `unique_category_name` (`name`),
  ADD KEY `parent_category_id` (`parent_category_id`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`cliente_id`);

--
-- Indices de la tabla `cotizaciones`
--
ALTER TABLE `cotizaciones`
  ADD PRIMARY KEY (`cotizacion_id`),
  ADD UNIQUE KEY `numero_cotizacion` (`numero_cotizacion`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `estado_id` (`estado_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `cotizaciones_acciones`
--
ALTER TABLE `cotizaciones_acciones`
  ADD PRIMARY KEY (`accion_id`),
  ADD UNIQUE KEY `nombre_accion` (`nombre_accion`);

--
-- Indices de la tabla `cotizaciones_historial`
--
ALTER TABLE `cotizaciones_historial`
  ADD PRIMARY KEY (`historial_id`),
  ADD KEY `cotizacion_id` (`cotizacion_id`),
  ADD KEY `accion_id` (`accion_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `cotizaciones_insumos`
--
ALTER TABLE `cotizaciones_insumos`
  ADD PRIMARY KEY (`cotizacion_insumo_id`),
  ADD KEY `cotizacion_id` (`cotizacion_id`),
  ADD KEY `insumo_id` (`insumo_id`);

--
-- Indices de la tabla `cotizaciones_productos`
--
ALTER TABLE `cotizaciones_productos`
  ADD PRIMARY KEY (`cotizacion_producto_id`),
  ADD KEY `cotizacion_id` (`cotizacion_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indices de la tabla `cotizaciones_servicios`
--
ALTER TABLE `cotizaciones_servicios`
  ADD PRIMARY KEY (`cotizacion_servicio_id`),
  ADD KEY `cotizacion_id` (`cotizacion_id`),
  ADD KEY `servicio_id` (`servicio_id`);

--
-- Indices de la tabla `equipos`
--
ALTER TABLE `equipos`
  ADD PRIMARY KEY (`equipo_id`);

--
-- Indices de la tabla `equipos_asignaciones`
--
ALTER TABLE `equipos_asignaciones`
  ADD PRIMARY KEY (`asignacion_id`),
  ADD KEY `equipo_id` (`equipo_id`),
  ADD KEY `tecnico_id` (`tecnico_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `estado_tecnico`
--
ALTER TABLE `estado_tecnico`
  ADD PRIMARY KEY (`estado_id`);

--
-- Indices de la tabla `est_cotizacion`
--
ALTER TABLE `est_cotizacion`
  ADD PRIMARY KEY (`est_cot_id`);

--
-- Indices de la tabla `insumos`
--
ALTER TABLE `insumos`
  ADD PRIMARY KEY (`insumo_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_supplier_id` (`supplier_id`);

--
-- Indices de la tabla `insumos_movements`
--
ALTER TABLE `insumos_movements`
  ADD PRIMARY KEY (`insumo_movement_id`),
  ADD KEY `insumo_id` (`insumo_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `movements`
--
ALTER TABLE `movements`
  ADD PRIMARY KEY (`movement_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `bobina_id` (`bobina_id`),
  ADD KEY `movement_type_id` (`movement_type_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_movements_tecnico` (`tecnico_id`);

--
-- Indices de la tabla `movement_types`
--
ALTER TABLE `movement_types`
  ADD PRIMARY KEY (`movement_type_id`);

--
-- Indices de la tabla `precios_proveedores`
--
ALTER TABLE `precios_proveedores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indices de la tabla `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indices de la tabla `servicios`
--
ALTER TABLE `servicios`
  ADD PRIMARY KEY (`servicio_id`);

--
-- Indices de la tabla `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplier_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indices de la tabla `system_config`
--
ALTER TABLE `system_config`
  ADD PRIMARY KEY (`config_id`),
  ADD UNIQUE KEY `config_key` (`config_key`);

--
-- Indices de la tabla `tecnicos`
--
ALTER TABLE `tecnicos`
  ADD PRIMARY KEY (`tecnico_id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `bobinas`
--
ALTER TABLE `bobinas`
  MODIFY `bobina_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `cliente_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `cotizaciones`
--
ALTER TABLE `cotizaciones`
  MODIFY `cotizacion_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `cotizaciones_acciones`
--
ALTER TABLE `cotizaciones_acciones`
  MODIFY `accion_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `cotizaciones_historial`
--
ALTER TABLE `cotizaciones_historial`
  MODIFY `historial_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT de la tabla `cotizaciones_insumos`
--
ALTER TABLE `cotizaciones_insumos`
  MODIFY `cotizacion_insumo_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `cotizaciones_productos`
--
ALTER TABLE `cotizaciones_productos`
  MODIFY `cotizacion_producto_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `cotizaciones_servicios`
--
ALTER TABLE `cotizaciones_servicios`
  MODIFY `cotizacion_servicio_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `equipos`
--
ALTER TABLE `equipos`
  MODIFY `equipo_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `equipos_asignaciones`
--
ALTER TABLE `equipos_asignaciones`
  MODIFY `asignacion_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estado_tecnico`
--
ALTER TABLE `estado_tecnico`
  MODIFY `estado_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `est_cotizacion`
--
ALTER TABLE `est_cotizacion`
  MODIFY `est_cot_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `insumos`
--
ALTER TABLE `insumos`
  MODIFY `insumo_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `insumos_movements`
--
ALTER TABLE `insumos_movements`
  MODIFY `insumo_movement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de la tabla `logs`
--
ALTER TABLE `logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `movements`
--
ALTER TABLE `movements`
  MODIFY `movement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=164;

--
-- AUTO_INCREMENT de la tabla `movement_types`
--
ALTER TABLE `movement_types`
  MODIFY `movement_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `precios_proveedores`
--
ALTER TABLE `precios_proveedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `servicios`
--
ALTER TABLE `servicios`
  MODIFY `servicio_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `system_config`
--
ALTER TABLE `system_config`
  MODIFY `config_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tecnicos`
--
ALTER TABLE `tecnicos`
  MODIFY `tecnico_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `cotizaciones_insumos`
--
ALTER TABLE `cotizaciones_insumos`
  ADD CONSTRAINT `cotizaciones_insumos_ibfk_1` FOREIGN KEY (`cotizacion_id`) REFERENCES `cotizaciones` (`cotizacion_id`),
  ADD CONSTRAINT `cotizaciones_insumos_ibfk_2` FOREIGN KEY (`insumo_id`) REFERENCES `insumos` (`insumo_id`);

--
-- Filtros para la tabla `cotizaciones_servicios`
--
ALTER TABLE `cotizaciones_servicios`
  ADD CONSTRAINT `cotizaciones_servicios_ibfk_1` FOREIGN KEY (`cotizacion_id`) REFERENCES `cotizaciones` (`cotizacion_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cotizaciones_servicios_ibfk_2` FOREIGN KEY (`servicio_id`) REFERENCES `servicios` (`servicio_id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `insumos`
--
ALTER TABLE `insumos`
  ADD CONSTRAINT `insumos_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `insumos_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `insumos_ibfk_3` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `movements`
--
ALTER TABLE `movements`
  ADD CONSTRAINT `fk_movements_tecnico` FOREIGN KEY (`tecnico_id`) REFERENCES `tecnicos` (`tecnico_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
