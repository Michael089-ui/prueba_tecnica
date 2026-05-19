-- Crear la base de datos si no existe
CREATE DATABASE IF NOT EXISTS `prueba_tecnica` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `prueba_tecnica`;

-- Tabla de guías
CREATE TABLE IF NOT EXISTS `guias` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `numero_guia` VARCHAR(50) UNIQUE NOT NULL,
  `cliente` VARCHAR(100) NOT NULL,
  `ciudad_destino` VARCHAR(100) NOT NULL,
  `direccion` VARCHAR(255) NOT NULL,
  `estado` ENUM('PENDIENTE', 'ENTREGADO', 'DEVUELTO') DEFAULT 'PENDIENTE',
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de registros (entregas y devoluciones)
CREATE TABLE IF NOT EXISTS `registros_guia` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `guia_id` INT NOT NULL,
  `tipo_registro` ENUM('ENTREGA', 'DEVOLUCION') NOT NULL,
  `nombre_recibe` VARCHAR(100) DEFAULT NULL,
  `motivo_devolucion` VARCHAR(255) DEFAULT NULL,
  `observacion` TEXT DEFAULT NULL,
  `foto_path` VARCHAR(255) NOT NULL,
  `firma_path` VARCHAR(255) NOT NULL,
  `latitud` DECIMAL(10, 8) DEFAULT NULL,
  `longitud` DECIMAL(11, 8) DEFAULT NULL,
  `fecha_registro` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`guia_id`) REFERENCES `guias`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Insertar datos semilla para pruebas
INSERT INTO `guias` (`numero_guia`, `cliente`, `ciudad_destino`, `direccion`, `estado`) VALUES
('GUI-982341', 'Almacenes Éxito', 'Medellín', 'Calle 10 # 43E-125', 'PENDIENTE'),
('GUI-982342', 'Nutresa S.A.', 'Bogotá', 'Av. El Dorado # 68C-80', 'PENDIENTE'),
('GUI-982343', 'Tecnoquímicas', 'Cali', 'Carrera 5 # 15-45', 'PENDIENTE'),
('GUI-982344', 'Homecenter', 'Barranquilla', 'Calle 98 # 52-115', 'PENDIENTE'),
('GUI-982345', 'Bancolombia', 'Medellín', 'Carrera 48 # 26-85', 'PENDIENTE'),
('GUI-982346', 'Postobón', 'Bucaramanga', 'Diagonal 15 # 56-20', 'PENDIENTE'),
('GUI-982347', 'Cervecería Bavaria', 'Bogotá', 'Calle 94 # 11A-32', 'PENDIENTE');
