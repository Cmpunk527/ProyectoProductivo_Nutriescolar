-- ============================================================
--  Base de datos: PROYECTO   (NutriEscolar)  ·  SCRIPT ÚNICO
--
--  Este es el ÚNICO archivo SQL que necesitas. Sirve para:
--    · Instalar desde cero (crea la base, las tablas y datos de ejemplo).
--    · Actualizar una base que ya tenías (separa Alimento/InformacionNutricional,
--      agrega índices y cursos, SIN perder tus datos).
--    · Volver a ejecutarlo cuando quieras: no duplica ni borra nada.
--
--  Cómo usarlo: phpMyAdmin → Importar → este archivo → Continuar.
--  (No hace falta seleccionar una base antes: el script la crea y la usa.)
--
--  Tablas que usa el sitio: Rol, Usuario, Grado, Curso, Acudiente,
--  Estudiante, Alimento, InformacionNutricional, Menu, MenuAlimento,
--  ConsumoAlimento y RecuperacionContrasena.
--  Consumo y Reporte pertenecen al diagrama original y se conservan
--  para una futura ampliación; el sitio actual no las utiliza.
-- ============================================================

CREATE DATABASE IF NOT EXISTS `PROYECTO`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `PROYECTO`;

-- ============================================================
--  1) ESTRUCTURA (tablas)
-- ============================================================

-- ------------------------------------------------------------
-- Tabla: Rol
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Rol` (
  `RolId`  INT         NOT NULL AUTO_INCREMENT,
  `Nombre` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`RolId`),
  UNIQUE KEY `uq_rol_nombre` (`Nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: Usuario
-- EXTENSIÓN: se agrega la columna `Institucion` para poder guardar
-- el campo "Institución educativa" del formulario de registro.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Usuario` (
  `UsuarioId`  INT          NOT NULL AUTO_INCREMENT,
  `Nombre`     VARCHAR(50)  NOT NULL,
  `Email`      VARCHAR(50)  NOT NULL,
  `Contraseña` VARCHAR(100) NOT NULL,
  `RolId`      INT          NOT NULL,
  `Institucion` VARCHAR(100) NULL,
  PRIMARY KEY (`UsuarioId`),
  UNIQUE KEY `uq_usuario_email` (`Email`),
  KEY `idx_usuario_nombre` (`Nombre`),
  CONSTRAINT `fk_usuario_rol`
    FOREIGN KEY (`RolId`) REFERENCES `Rol` (`RolId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: Grado
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Grado` (
  `GradoId` INT         NOT NULL AUTO_INCREMENT,
  `Nombre`  VARCHAR(30) NOT NULL,
  PRIMARY KEY (`GradoId`),
  UNIQUE KEY `uq_grado_nombre` (`Nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: Curso
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Curso` (
  `CursoId` INT         NOT NULL AUTO_INCREMENT,
  `Nombre`  VARCHAR(20) NOT NULL,
  `GradoId` INT         NOT NULL,
  PRIMARY KEY (`CursoId`),
  CONSTRAINT `fk_curso_grado`
    FOREIGN KEY (`GradoId`) REFERENCES `Grado` (`GradoId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: Acudiente
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Acudiente` (
  `AcudienteId`     INT         NOT NULL AUTO_INCREMENT,
  `Nombre`          VARCHAR(50) NOT NULL,
  `Apellido`        VARCHAR(50) NOT NULL,
  `NumeroDocumento` BIGINT      NOT NULL,
  `Email`           VARCHAR(50) NULL,
  `Celular`         VARCHAR(20) NULL,
  PRIMARY KEY (`AcudienteId`),
  UNIQUE KEY `uq_acudiente_doc` (`NumeroDocumento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: Estudiante
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Estudiante` (
  `EstudianteId`    INT         NOT NULL AUTO_INCREMENT,
  `Nombre`          VARCHAR(50) NOT NULL,
  `Apellido`        VARCHAR(50) NOT NULL,
  `UsuarioId`       INT         NOT NULL,
  `NumeroDocumento` BIGINT      NOT NULL,
  `CursoId`         INT         NOT NULL,
  `AcudienteId`     INT         NOT NULL,
  `Email`           VARCHAR(50) NULL,
  `Celular`         VARCHAR(20) NULL,
  PRIMARY KEY (`EstudianteId`),
  UNIQUE KEY `uq_estudiante_usuario`  (`UsuarioId`),
  UNIQUE KEY `uq_estudiante_doc`      (`NumeroDocumento`),
  CONSTRAINT `fk_estudiante_usuario`
    FOREIGN KEY (`UsuarioId`)   REFERENCES `Usuario`   (`UsuarioId`),
  CONSTRAINT `fk_estudiante_curso`
    FOREIGN KEY (`CursoId`)     REFERENCES `Curso`     (`CursoId`),
  CONSTRAINT `fk_estudiante_acudiente`
    FOREIGN KEY (`AcudienteId`) REFERENCES `Acudiente` (`AcudienteId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: Alimento
-- Solo identifica al alimento (nombre y descripción). Sus valores
-- nutricionales viven en la tabla InformacionNutricional (relación 1:1).
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Alimento` (
  `AlimentoId`    INT           NOT NULL AUTO_INCREMENT,
  `Nombre`        VARCHAR(100)  NOT NULL,
  `Descripcion`   TEXT          NULL,
  PRIMARY KEY (`AlimentoId`),
  UNIQUE KEY `uq_alimento_nombre` (`Nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: InformacionNutricional
-- Características nutricionales de cada alimento (calorías, proteínas,
-- grasas, carbohidratos y sodio). Relación 1:1 con Alimento: cada alimento
-- tiene como máximo una ficha nutricional (AlimentoId es UNIQUE) y, si el
-- alimento se elimina, su ficha se elimina con él (ON DELETE CASCADE).
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `InformacionNutricional` (
  `InformacionNutricionalId` INT           NOT NULL AUTO_INCREMENT,
  `AlimentoId`               INT           NOT NULL,
  `Calorias`                 DECIMAL(10,2) NOT NULL,
  `Proteinas`                DECIMAL(10,2) NOT NULL,
  `Grasas`                   DECIMAL(10,2) NOT NULL,
  `Carbohidratos`            DECIMAL(10,2) NOT NULL,
  `Sodio`                    DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`InformacionNutricionalId`),
  UNIQUE KEY `uq_infonutricional_alimento` (`AlimentoId`),
  CONSTRAINT `fk_infonutricional_alimento`
    FOREIGN KEY (`AlimentoId`) REFERENCES `Alimento` (`AlimentoId`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: Menu
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Menu` (
  `MenuId`      INT  NOT NULL AUTO_INCREMENT,
  `Fecha`       DATE NOT NULL,
  `Descripcion` TEXT NULL,
  PRIMARY KEY (`MenuId`),
  UNIQUE KEY `uq_menu_fecha` (`Fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: MenuAlimento (relación Menu ↔ Alimento)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `MenuAlimento` (
  `MenuAlimentoId` INT NOT NULL AUTO_INCREMENT,
  `MenuId`         INT NOT NULL,
  `AlimentoId`     INT NOT NULL,
  PRIMARY KEY (`MenuAlimentoId`),
  UNIQUE KEY `uq_menualimento` (`MenuId`, `AlimentoId`),
  CONSTRAINT `fk_menualimento_menu`
    FOREIGN KEY (`MenuId`)     REFERENCES `Menu`     (`MenuId`),
  CONSTRAINT `fk_menualimento_alimento`
    FOREIGN KEY (`AlimentoId`) REFERENCES `Alimento` (`AlimentoId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: Consumo  (esquema original, relacionado con Estudiante)
-- Se conserva para uso futuro del módulo completo de estudiantes.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Consumo` (
  `ConsumoId`    INT  NOT NULL AUTO_INCREMENT,
  `EstudianteId` INT  NOT NULL,
  `MenuId`       INT  NOT NULL,
  `Fecha`        DATE NOT NULL,
  PRIMARY KEY (`ConsumoId`),
  CONSTRAINT `fk_consumo_estudiante`
    FOREIGN KEY (`EstudianteId`) REFERENCES `Estudiante` (`EstudianteId`),
  CONSTRAINT `fk_consumo_menu`
    FOREIGN KEY (`MenuId`)       REFERENCES `Menu`       (`MenuId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: Reporte
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Reporte` (
  `ReporteId`   INT         NOT NULL AUTO_INCREMENT,
  `UsuarioId`   INT         NOT NULL,
  `FechaInicio` DATE        NOT NULL,
  `FechaFin`    DATE        NOT NULL,
  `TipoReporte` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`ReporteId`),
  CONSTRAINT `fk_reporte_usuario`
    FOREIGN KEY (`UsuarioId`) REFERENCES `Usuario` (`UsuarioId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- EXTENSIÓN: Tabla RecuperacionContrasena
-- Soporta el flujo real de "olvidé mi contraseña": se genera un
-- token único que se envía por correo y permite definir una
-- contraseña nueva una sola vez, dentro de un tiempo límite.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `RecuperacionContrasena` (
  `RecuperacionId` INT          NOT NULL AUTO_INCREMENT,
  `UsuarioId`      INT          NOT NULL,
  `Token`          VARCHAR(64)  NOT NULL,
  `Expira`         DATETIME     NOT NULL,
  `Usado`          TINYINT(1)   NOT NULL DEFAULT 0,
  PRIMARY KEY (`RecuperacionId`),
  UNIQUE KEY `uq_recuperacion_token` (`Token`),
  CONSTRAINT `fk_recuperacion_usuario`
    FOREIGN KEY (`UsuarioId`) REFERENCES `Usuario` (`UsuarioId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- EXTENSIÓN: Tabla ConsumoAlimento
-- El propio usuario registra EXACTAMENTE qué alimentos comió cada día
-- (no todo el menú obligatoriamente); es la base del reporte nutricional.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ConsumoAlimento` (
  `ConsumoAlimentoId` INT  NOT NULL AUTO_INCREMENT,
  `UsuarioId`          INT  NOT NULL,
  `AlimentoId`         INT  NOT NULL,
  `Fecha`              DATE NOT NULL,
  PRIMARY KEY (`ConsumoAlimentoId`),
  UNIQUE KEY `uq_consumoalimento` (`UsuarioId`, `AlimentoId`, `Fecha`),
  KEY `idx_consumoalimento_usuario_fecha` (`UsuarioId`, `Fecha`),
  CONSTRAINT `fk_consumoalimento_usuario`
    FOREIGN KEY (`UsuarioId`)  REFERENCES `Usuario`  (`UsuarioId`),
  CONSTRAINT `fk_consumoalimento_alimento`
    FOREIGN KEY (`AlimentoId`) REFERENCES `Alimento` (`AlimentoId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  ACTUALIZACIÓN AUTOMÁTICA DE BASES DE DATOS ANTIGUAS
--  (si instalas desde cero, todo este bloque simplemente no hace nada)
--  Cada paso revisa primero si hace falta y solo entonces actúa, por eso
--  el script completo se puede ejecutar varias veces sin dañar tus datos.
-- ============================================================

-- A) Columna Institucion en Usuario (versiones muy antiguas no la tenían)
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE LOWER(TABLE_SCHEMA) = LOWER(DATABASE()) AND LOWER(TABLE_NAME) = 'usuario' AND LOWER(COLUMN_NAME) = 'institucion');
SET @q := IF(@c = 0, 'ALTER TABLE `Usuario` ADD COLUMN `Institucion` VARCHAR(100) NULL', 'DO 0');
PREPARE st FROM @q;
EXECUTE st;
DEALLOCATE PREPARE st;

-- B) Alimento separado: si Alimento todavía tiene las columnas nutricionales,
--    primero se COPIAN a InformacionNutricional y luego se quitan de Alimento.
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE LOWER(TABLE_SCHEMA) = LOWER(DATABASE()) AND LOWER(TABLE_NAME) = 'alimento' AND LOWER(COLUMN_NAME) = 'calorias');
SET @q := IF(@c > 0, 'INSERT INTO `InformacionNutricional` (`AlimentoId`, `Calorias`, `Proteinas`, `Grasas`, `Carbohidratos`, `Sodio`) SELECT a.`AlimentoId`, a.`Calorias`, a.`Proteinas`, a.`Grasas`, a.`Carbohidratos`, a.`Sodio` FROM `Alimento` a WHERE NOT EXISTS (SELECT 1 FROM `InformacionNutricional` i WHERE i.`AlimentoId` = a.`AlimentoId`)', 'DO 0');
PREPARE st FROM @q;
EXECUTE st;
DEALLOCATE PREPARE st;
SET @q := IF(@c > 0, 'ALTER TABLE `Alimento` DROP COLUMN `Calorias`, DROP COLUMN `Proteinas`, DROP COLUMN `Grasas`, DROP COLUMN `Carbohidratos`, DROP COLUMN `Sodio`', 'DO 0');
PREPARE st FROM @q;
EXECUTE st;
DEALLOCATE PREPARE st;

-- C) ConsumoUsuario (tabla antigua, ya sin uso): su contenido pasa a
--    ConsumoAlimento (todos los alimentos de ese menú) y la tabla se elimina.
SET @c := (SELECT COUNT(*) FROM information_schema.TABLES WHERE LOWER(TABLE_SCHEMA) = LOWER(DATABASE()) AND LOWER(TABLE_NAME) = 'consumousuario');
SET @q := IF(@c > 0, 'INSERT IGNORE INTO `ConsumoAlimento` (`UsuarioId`, `AlimentoId`, `Fecha`) SELECT cu.`UsuarioId`, ma.`AlimentoId`, cu.`Fecha` FROM `ConsumoUsuario` cu INNER JOIN `MenuAlimento` ma ON ma.`MenuId` = cu.`MenuId`', 'DO 0');
PREPARE st FROM @q;
EXECUTE st;
DEALLOCATE PREPARE st;
SET @q := IF(@c > 0, 'DROP TABLE `ConsumoUsuario`', 'DO 0');
PREPARE st FROM @q;
EXECUTE st;
DEALLOCATE PREPARE st;

-- D) Índices que aceleran las consultas del sitio y evitan datos repetidos
--    (si ya había filas repetidas, se crea un índice normal en vez de único)
SET @c := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE LOWER(TABLE_SCHEMA) = LOWER(DATABASE()) AND LOWER(TABLE_NAME) = 'alimento' AND LOWER(INDEX_NAME) = 'uq_alimento_nombre');
SET @d := (SELECT COUNT(*) FROM (SELECT 1 FROM `Alimento` GROUP BY `Nombre` HAVING COUNT(*) > 1) x);
SET @q := IF(@c > 0, 'DO 0', IF(@d = 0, 'ALTER TABLE `Alimento` ADD UNIQUE KEY `uq_alimento_nombre` (`Nombre`)', 'ALTER TABLE `Alimento` ADD KEY `uq_alimento_nombre` (`Nombre`)'));
PREPARE st FROM @q;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE LOWER(TABLE_SCHEMA) = LOWER(DATABASE()) AND LOWER(TABLE_NAME) = 'menu' AND LOWER(INDEX_NAME) = 'uq_menu_fecha');
SET @d := (SELECT COUNT(*) FROM (SELECT 1 FROM `Menu` GROUP BY `Fecha` HAVING COUNT(*) > 1) x);
SET @q := IF(@c > 0, 'DO 0', IF(@d = 0, 'ALTER TABLE `Menu` ADD UNIQUE KEY `uq_menu_fecha` (`Fecha`)', 'ALTER TABLE `Menu` ADD KEY `uq_menu_fecha` (`Fecha`)'));
PREPARE st FROM @q;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE LOWER(TABLE_SCHEMA) = LOWER(DATABASE()) AND LOWER(TABLE_NAME) = 'menualimento' AND LOWER(INDEX_NAME) = 'uq_menualimento');
SET @d := (SELECT COUNT(*) FROM (SELECT 1 FROM `MenuAlimento` GROUP BY `MenuId`, `AlimentoId` HAVING COUNT(*) > 1) x);
SET @q := IF(@c > 0, 'DO 0', IF(@d = 0, 'ALTER TABLE `MenuAlimento` ADD UNIQUE KEY `uq_menualimento` (`MenuId`, `AlimentoId`)', 'ALTER TABLE `MenuAlimento` ADD KEY `uq_menualimento` (`MenuId`, `AlimentoId`)'));
PREPARE st FROM @q;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE LOWER(TABLE_SCHEMA) = LOWER(DATABASE()) AND LOWER(TABLE_NAME) = 'usuario' AND LOWER(INDEX_NAME) = 'idx_usuario_nombre');
SET @q := IF(@c > 0, 'DO 0', 'ALTER TABLE `Usuario` ADD KEY `idx_usuario_nombre` (`Nombre`)');
PREPARE st FROM @q;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE LOWER(TABLE_SCHEMA) = LOWER(DATABASE()) AND LOWER(TABLE_NAME) = 'consumoalimento' AND LOWER(INDEX_NAME) = 'idx_consumoalimento_usuario_fecha');
SET @q := IF(@c > 0, 'DO 0', 'ALTER TABLE `ConsumoAlimento` ADD KEY `idx_consumoalimento_usuario_fecha` (`UsuarioId`, `Fecha`)');
PREPARE st FROM @q;
EXECUTE st;
DEALLOCATE PREPARE st;


-- ============================================================
--  3) DATOS INICIALES (roles, administrador, cursos, alimentos PAE, menús)
-- ============================================================

-- Roles usados por registro.php / inicio-sesion.php
INSERT INTO `Rol` (`Nombre`) VALUES
  ('admin'),
  ('estudiante'),
  ('profesor')
ON DUPLICATE KEY UPDATE `Nombre` = VALUES(`Nombre`);

-- Usuario administrador de prueba -> usuario: admin  /  contraseña: admin123
-- (se crea primero, inmediatamente después de los roles, para que quede
--  disponible aunque algún otro INSERT posterior falle en tu servidor)
INSERT INTO `Usuario` (`Nombre`, `Email`, `Contraseña`, `RolId`, `Institucion`)
SELECT 'admin', 'admin@nutriescolar.edu.co',
       '$2y$10$rL.oM5Hvr.rLIePg4oToveqJYiaD3MrBxdL4pjIG18Qs6aEShe.ii',
       (SELECT RolId FROM Rol WHERE Nombre = 'admin'),
       'Colegio Técnico Palermo'
WHERE NOT EXISTS (SELECT 1 FROM `Usuario` WHERE `Email` = 'admin@nutriescolar.edu.co');

-- Grados y cursos de ejemplo (necesarios para el registro de estudiantes)
INSERT INTO `Grado` (`Nombre`)
SELECT * FROM (SELECT 'Quinto' AS Nombre UNION ALL SELECT 'Sexto' UNION ALL
               SELECT 'Séptimo' UNION ALL SELECT 'Octavo' UNION ALL
               SELECT 'Noveno' UNION ALL SELECT 'Décimo' UNION ALL SELECT 'Once') AS datos
WHERE NOT EXISTS (SELECT 1 FROM `Grado` WHERE `Grado`.`Nombre` = datos.Nombre);

-- Cursos 01, 02 y 03 de cada grado (501-503, 601-603, ... 1101-1103)
INSERT INTO `Curso` (`Nombre`, `GradoId`)
SELECT datos.Nombre, g.GradoId
FROM (
  SELECT '501' AS Nombre, 'Quinto' AS Grado UNION ALL
  SELECT '502', 'Quinto' UNION ALL
  SELECT '503', 'Quinto' UNION ALL
  SELECT '601', 'Sexto' UNION ALL
  SELECT '602', 'Sexto' UNION ALL
  SELECT '603', 'Sexto' UNION ALL
  SELECT '701', 'Séptimo' UNION ALL
  SELECT '702', 'Séptimo' UNION ALL
  SELECT '703', 'Séptimo' UNION ALL
  SELECT '801', 'Octavo' UNION ALL
  SELECT '802', 'Octavo' UNION ALL
  SELECT '803', 'Octavo' UNION ALL
  SELECT '901', 'Noveno' UNION ALL
  SELECT '902', 'Noveno' UNION ALL
  SELECT '903', 'Noveno' UNION ALL
  SELECT '1001', 'Décimo' UNION ALL
  SELECT '1002', 'Décimo' UNION ALL
  SELECT '1003', 'Décimo' UNION ALL
  SELECT '1101', 'Once' UNION ALL
  SELECT '1102', 'Once' UNION ALL
  SELECT '1103', 'Once'
) AS datos
INNER JOIN `Grado` g ON g.Nombre = datos.Grado
WHERE NOT EXISTS (SELECT 1 FROM `Curso` c WHERE c.Nombre = datos.Nombre AND c.GradoId = g.GradoId);

-- Usuario estudiante de prueba -> usuario: juan perez / contraseña: estudiante123
INSERT INTO `Usuario` (`Nombre`, `Email`, `Contraseña`, `RolId`, `Institucion`)
SELECT 'juan perez', 'juan.perez@nutriescolar.edu.co',
       '$2y$10$QUDZFhavxZRD9UFhKLg3W.Ntmha2QYzwHYh0Qr0PGwor6YbZnGpuK',
       (SELECT RolId FROM Rol WHERE Nombre = 'estudiante'),
       'Colegio Técnico Palermo'
WHERE NOT EXISTS (SELECT 1 FROM `Usuario` WHERE `Email` = 'juan.perez@nutriescolar.edu.co');

-- Acudiente y Estudiante de ejemplo para "juan perez"
INSERT INTO `Acudiente` (`Nombre`, `Apellido`, `NumeroDocumento`, `Email`, `Celular`)
SELECT 'Carlos', 'Perez', 100200300, 'carlos.perez@example.com', '3001234567'
WHERE NOT EXISTS (SELECT 1 FROM `Acudiente` WHERE `NumeroDocumento` = 100200300);

INSERT INTO `Estudiante` (`Nombre`, `Apellido`, `UsuarioId`, `NumeroDocumento`, `CursoId`, `AcudienteId`, `Email`, `Celular`)
SELECT 'Juan', 'Perez', u.UsuarioId, 900100200, c.CursoId, ac.AcudienteId, u.Email, NULL
FROM `Usuario` u
JOIN `Curso` c ON c.Nombre = '901'
JOIN `Acudiente` ac ON ac.NumeroDocumento = 100200300
WHERE u.Email = 'juan.perez@nutriescolar.edu.co'
  AND NOT EXISTS (SELECT 1 FROM `Estudiante` WHERE `UsuarioId` = u.UsuarioId);

-- ------------------------------------------------------------
-- Alimentos de ejemplo, basados en los grupos de alimentos y
-- preparaciones típicas de las minutas patrón del PAE
-- (Programa de Alimentación Escolar) colombiano: cereal, proteína,
-- leguminosa, tubérculo, verdura/ensalada, fruta/jugo y lácteo.
-- Los valores nutricionales son aproximados (tablas de composición
-- de alimentos de referencia), pensados para fines demostrativos.
-- ------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS `tmp_alimentos_seed`;
CREATE TEMPORARY TABLE `tmp_alimentos_seed` (
  `Nombre`        VARCHAR(100),
  `Descripcion`   TEXT,
  `Calorias`      DECIMAL(10,2),
  `Proteinas`     DECIMAL(10,2),
  `Grasas`        DECIMAL(10,2),
  `Carbohidratos` DECIMAL(10,2),
  `Sodio`         DECIMAL(10,2)
);
INSERT INTO `tmp_alimentos_seed` VALUES
  ('Arroz blanco', 'Cereal — base del almuerzo PAE', 130.00, 2.70, 0.30, 28.00, 1.00),
  ('Pollo guisado', 'Proteína — guiso de pollo con verduras', 165.00, 25.00, 6.00, 2.00, 70.00),
  ('Carne de res en bistec', 'Proteína — bistec de res a la plancha', 217.00, 26.00, 12.00, 0.00, 60.00),
  ('Pescado guisado', 'Proteína — filete de pescado guisado', 206.00, 22.00, 12.00, 0.00, 65.00),
  ('Cerdo guisado', 'Proteína — carne de cerdo guisada', 242.00, 23.00, 16.00, 1.00, 58.00),
  ('Huevo revuelto', 'Proteína — complemento AM/PM', 155.00, 13.00, 11.00, 1.10, 124.00),
  ('Fríjoles guisados', 'Leguminosa — fríjol con guiso, se acompaña con arroz', 127.00, 8.70, 0.50, 22.80, 2.00),
  ('Lentejas guisadas', 'Leguminosa — fuente de hierro y fibra', 116.00, 9.00, 0.40, 20.00, 2.00),
  ('Garbanzos guisados', 'Leguminosa — alternativa proteica vegetal', 164.00, 8.90, 2.60, 27.40, 7.00),
  ('Papa criolla', 'Tubérculo — acompañamiento típico', 87.00, 2.00, 0.10, 20.10, 6.00),
  ('Plátano maduro asado', 'Tubérculo/fruta — acompañamiento', 122.00, 1.30, 0.37, 31.90, 4.00),
  ('Ensalada de tomate y lechuga', 'Verdura — ensalada cruda', 22.00, 1.10, 0.20, 4.20, 8.00),
  ('Ensalada de zanahoria y remolacha', 'Verdura — ensalada rallada', 35.00, 1.20, 0.20, 8.00, 60.00),
  ('Sopa de verduras con pollo', 'Preparación caliente — entrada', 90.00, 7.00, 2.50, 9.00, 350.00),
  ('Jugo de mora', 'Bebida — fruta natural sin azúcar añadida', 45.00, 0.50, 0.10, 11.00, 2.00),
  ('Jugo de guayaba', 'Bebida — fruta natural', 50.00, 0.60, 0.20, 11.50, 2.00),
  ('Jugo de lulo', 'Bebida — fruta natural', 42.00, 0.60, 0.20, 10.00, 2.00),
  ('Bienestarina', 'Complemento nutricional ICBF (avena/soya/leche fortificada)', 380.00, 18.00, 6.00, 62.00, 210.00);

-- 1) El alimento (solo nombre y descripción) va en la tabla Alimento
INSERT INTO `Alimento` (`Nombre`, `Descripcion`)
SELECT t.`Nombre`, t.`Descripcion`
FROM `tmp_alimentos_seed` t
WHERE NOT EXISTS (SELECT 1 FROM `Alimento` a WHERE a.`Nombre` = t.`Nombre` COLLATE utf8mb4_unicode_ci);

-- 2) Sus características nutricionales van en InformacionNutricional
INSERT INTO `InformacionNutricional` (`AlimentoId`, `Calorias`, `Proteinas`, `Grasas`, `Carbohidratos`, `Sodio`)
SELECT a.`AlimentoId`, t.`Calorias`, t.`Proteinas`, t.`Grasas`, t.`Carbohidratos`, t.`Sodio`
FROM `tmp_alimentos_seed` t
INNER JOIN `Alimento` a ON a.`Nombre` = t.`Nombre` COLLATE utf8mb4_unicode_ci
WHERE NOT EXISTS (SELECT 1 FROM `InformacionNutricional` i WHERE i.`AlimentoId` = a.`AlimentoId`);

DROP TEMPORARY TABLE IF EXISTS `tmp_alimentos_seed`;

-- Menús de la semana (lunes a viernes) siguiendo la estructura de un
-- almuerzo PAE: cereal + proteína + leguminosa/tubérculo + verdura + jugo
INSERT INTO `Menu` (`Fecha`, `Descripcion`)
SELECT * FROM (SELECT '2026-05-11' AS Fecha, 'Menú lunes' AS Descripcion UNION ALL
               SELECT '2026-05-12', 'Menú martes' UNION ALL
               SELECT '2026-05-13', 'Menú miércoles' UNION ALL
               SELECT '2026-05-14', 'Menú jueves' UNION ALL
               SELECT '2026-05-15', 'Menú viernes') AS datos
WHERE NOT EXISTS (SELECT 1 FROM `Menu` WHERE `Menu`.`Fecha` = datos.Fecha);

-- Relación Menu <-> Alimento (qué se sirvió cada día, estilo minuta PAE)
INSERT INTO `MenuAlimento` (`MenuId`, `AlimentoId`)
SELECT m.MenuId, a.AlimentoId
FROM (
  -- Lunes: arroz + pollo + fríjoles + ensalada + jugo
  SELECT '2026-05-11' AS Fecha, 'Arroz blanco' AS Alimento UNION ALL
  SELECT '2026-05-11', 'Pollo guisado' UNION ALL
  SELECT '2026-05-11', 'Fríjoles guisados' UNION ALL
  SELECT '2026-05-11', 'Ensalada de tomate y lechuga' UNION ALL
  SELECT '2026-05-11', 'Jugo de mora' UNION ALL
  -- Martes: arroz + carne de res + ensalada de zanahoria + jugo
  SELECT '2026-05-12', 'Arroz blanco' UNION ALL
  SELECT '2026-05-12', 'Carne de res en bistec' UNION ALL
  SELECT '2026-05-12', 'Ensalada de zanahoria y remolacha' UNION ALL
  SELECT '2026-05-12', 'Jugo de guayaba' UNION ALL
  -- Miércoles: arroz + pescado + papa criolla + ensalada + jugo
  SELECT '2026-05-13', 'Arroz blanco' UNION ALL
  SELECT '2026-05-13', 'Pescado guisado' UNION ALL
  SELECT '2026-05-13', 'Papa criolla' UNION ALL
  SELECT '2026-05-13', 'Ensalada de tomate y lechuga' UNION ALL
  -- Jueves: sopa + arroz + lentejas + plátano
  SELECT '2026-05-14', 'Sopa de verduras con pollo' UNION ALL
  SELECT '2026-05-14', 'Arroz blanco' UNION ALL
  SELECT '2026-05-14', 'Lentejas guisadas' UNION ALL
  SELECT '2026-05-14', 'Plátano maduro asado' UNION ALL
  -- Viernes: arroz + cerdo + garbanzos + jugo de lulo
  SELECT '2026-05-15', 'Arroz blanco' UNION ALL
  SELECT '2026-05-15', 'Cerdo guisado' UNION ALL
  SELECT '2026-05-15', 'Garbanzos guisados' UNION ALL
  SELECT '2026-05-15', 'Jugo de lulo'
) AS rel
JOIN `Menu` m ON m.Fecha = rel.Fecha
JOIN `Alimento` a ON a.Nombre = rel.Alimento
WHERE NOT EXISTS (
  SELECT 1 FROM `MenuAlimento` ma WHERE ma.MenuId = m.MenuId AND ma.AlimentoId = a.AlimentoId
);

-- Consumo de ejemplo para el usuario "juan perez" (para probar Consultar Consumo):
-- se registra que comió TODOS los alimentos del menú del lunes (2026-05-11).
INSERT IGNORE INTO `ConsumoAlimento` (`UsuarioId`, `AlimentoId`, `Fecha`)
SELECT u.UsuarioId, ma.AlimentoId, m.Fecha
FROM `Usuario` u
JOIN `Menu` m ON m.Fecha = '2026-05-11'
JOIN `MenuAlimento` ma ON ma.MenuId = m.MenuId
WHERE u.Email = 'juan.perez@nutriescolar.edu.co'
  AND NOT EXISTS (
    SELECT 1 FROM `ConsumoAlimento` ca
    WHERE ca.UsuarioId = u.UsuarioId AND ca.AlimentoId = ma.AlimentoId AND ca.Fecha = m.Fecha
  );

-- ============================================================
--  RESUMEN (si ves esta fila al final, todo quedó bien)
-- ============================================================
SELECT 'Base de datos PROYECTO lista' AS estado,
  (SELECT COUNT(*) FROM `Usuario`)                AS usuarios,
  (SELECT COUNT(*) FROM `Alimento`)               AS alimentos,
  (SELECT COUNT(*) FROM `InformacionNutricional`) AS fichas_nutricionales,
  (SELECT COUNT(*) FROM `Alimento` a LEFT JOIN `InformacionNutricional` i ON i.`AlimentoId` = a.`AlimentoId`
     WHERE i.`AlimentoId` IS NULL)                AS alimentos_sin_ficha,
  (SELECT COUNT(*) FROM `Menu`)                   AS menus,
  (SELECT COUNT(*) FROM `Curso`)                  AS cursos;
