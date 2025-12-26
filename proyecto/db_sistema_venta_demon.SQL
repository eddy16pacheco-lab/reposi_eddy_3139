-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Versión del servidor:         12.1.2-MariaDB - MariaDB Server
-- SO del servidor:              Win64
-- HeidiSQL Versión:             12.11.0.7065
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Volcando estructura de base de datos para db_sistema_ventas_demon
CREATE DATABASE IF NOT EXISTS `db_sistema_ventas_demon` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci */;
USE `db_sistema_ventas_demon`;

-- Volcando estructura para tabla db_sistema_ventas_demon.transaccion
CREATE TABLE IF NOT EXISTS `transaccion` (
  `id_transaccion` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `tipo_transaccion` enum('Venta','Gasto') NOT NULL DEFAULT 'Venta',
  `monto_transacion` double NOT NULL,
  `descripcion` varchar(100) NOT NULL DEFAULT '',
  `forma_pago` enum('Efectivo','Transferencia','Tarjeta') NOT NULL DEFAULT 'Efectivo',
  PRIMARY KEY (`id_transaccion`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `puede_realiazar` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- Volcando datos para la tabla db_sistema_ventas_demon.transaccion: ~0 rows (aproximadamente)

-- Volcando estructura para tabla db_sistema_ventas_demon.usuario
CREATE TABLE IF NOT EXISTS `usuario` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_usuario` varchar(50) NOT NULL,
  `contraseña_usuario` varchar(50) NOT NULL,
  `correo` varchar(100) DEFAULT 'Administrador',
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `rol` enum('Empleado','Administrador') NOT NULL DEFAULT 'Administrador',
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `nombre_usuario` (`nombre_usuario`),
  UNIQUE KEY `correo` (`correo`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- Volcando datos para la tabla db_sistema_ventas_demon.usuario: ~1 rows (aproximadamente)
INSERT INTO `usuario` (`id_usuario`, `nombre_usuario`, `contraseña_usuario`, `correo`, `nombre`, `apellido`, `fecha_nacimiento`, `rol`) VALUES
	(1, 'Eddy_20', 'Eddy_20', 'eddy_20@gmail.com', 'Eddy', 'Pacheco', '2025-12-23', 'Administrador');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
