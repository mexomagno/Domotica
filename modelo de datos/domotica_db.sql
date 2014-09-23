-- phpMyAdmin SQL Dump
-- version 4.1.12
-- http://www.phpmyadmin.net
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 15-08-2014 a las 01:31:33
-- Versión del servidor: 5.6.16
-- Versión de PHP: 5.5.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de datos: `domotica`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servidores_domotica`
--

CREATE TABLE IF NOT EXISTS `servidores_domotica` (
  `id_servidor_domotica` int(11) NOT NULL AUTO_INCREMENT,
  `direccion` varchar(255) COLLATE utf8_spanish_ci NOT NULL COMMENT 'Dirección donde aloja el servidor de domótica',
  `nombre_familia` varchar(255) COLLATE utf8_spanish_ci NOT NULL DEFAULT 'Familia',
  `hostname` varchar(255) COLLATE utf8_spanish_ci NOT NULL COMMENT 'ip o dns acceso servidor domótica',
  `puerto` int(11) NOT NULL DEFAULT '22' COMMENT 'Puerto conexión ssh',
  `id_user` int(11) NOT NULL COMMENT 'id usuario normal servidor domotica',
  `id_superuser` int(11) NOT NULL COMMENT 'id super usuario servidor domotica',
  PRIMARY KEY (`id_servidor_domotica`),
  KEY `id_user` (`id_user`,`id_superuser`),
  KEY `id_superuser` (`id_superuser`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci COMMENT='Servidores válidos accesibles, compatibles con el sistema' AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios_domotica`
--

CREATE TABLE IF NOT EXISTS `usuarios_domotica` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id unico del usuario',
  `usuario` varchar(30) COLLATE utf32_spanish_ci NOT NULL COMMENT 'nombre usuario acceso al servidor',
  `password` varchar(255) COLLATE utf32_spanish_ci NOT NULL COMMENT 'password usuario acceso al servidor',
  `privilegios` varchar(1) COLLATE utf32_spanish_ci NOT NULL DEFAULT 'U' COMMENT 'privilegios del usuario',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf32 COLLATE=utf32_spanish_ci AUTO_INCREMENT=3 ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios_web`
--

CREATE TABLE IF NOT EXISTS `usuarios_web` (
  `nombre_real` varchar(255) COLLATE utf8_spanish_ci NOT NULL COMMENT 'nombre real del usuario',
  `nombre_usuario` varchar(255) COLLATE utf8_spanish_ci NOT NULL COMMENT 'nick del usuario',
  `password` varchar(40) COLLATE utf8_spanish_ci NOT NULL DEFAULT 'pass',
  `id_servidor_domotica` int(11) NOT NULL COMMENT 'id del servidor de domotica asociado al usuario',
  PRIMARY KEY (`nombre_usuario`),
  UNIQUE KEY `nombre_usuario` (`nombre_usuario`),
  KEY `id_servidor_domotica` (`id_servidor_domotica`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci COMMENT='Tabla de usuarios y sus atributos';

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `servidores_domotica`
--
ALTER TABLE `servidores_domotica`
  ADD CONSTRAINT `servidores_domotica_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `usuarios_domotica` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `servidores_domotica_ibfk_2` FOREIGN KEY (`id_superuser`) REFERENCES `usuarios_domotica` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuarios_web`
--
ALTER TABLE `usuarios_web`
  ADD CONSTRAINT `usuario-casa` FOREIGN KEY (`id_servidor_domotica`) REFERENCES `servidores_domotica` (`id_servidor_domotica`) ON DELETE CASCADE ON UPDATE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
