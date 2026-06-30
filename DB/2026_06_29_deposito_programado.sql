-- Esquema para depósito programado por reserva y liberación semanal.
-- Aplicar sobre la base de datos `tarjetas`.

USE `tarjetas`;

ALTER TABLE `usuario`
    ADD COLUMN `monto_deposito_reservado` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `monto_deposito_hotel`,
    ADD COLUMN `monto_deposito_operativo` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `monto_deposito_reservado`,
    ADD COLUMN `deposito_programado_estatus` VARCHAR(20) NOT NULL DEFAULT 'sin_programa' AFTER `monto_deposito_operativo`;

CREATE TABLE IF NOT EXISTS `usuario_deposito_programado` (
    `id_usuario_deposito_programado` INT(11) NOT NULL AUTO_INCREMENT,
    `id_usuario` INT(11) NOT NULL,
    `id_qr_cliente` INT(11) DEFAULT NULL,
    `tipo_evento` VARCHAR(20) NOT NULL COMMENT 'alta, activacion, semanal',
    `periodo_inicio` DATE NOT NULL,
    `periodo_fin` DATE NOT NULL,
    `fecha_ejecucion_programada` DATETIME NOT NULL,
    `monto_diario` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `dias_programados` INT(11) NOT NULL DEFAULT 0,
    `monto_total_reservado` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `monto_total_aplicado` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `estatus` VARCHAR(20) NOT NULL DEFAULT 'reservado' COMMENT 'reservado, pendiente, aplicado, parcial, error, cancelado',
    `observaciones` TEXT DEFAULT NULL,
    `fec_reg` DATETIME DEFAULT NULL,
    `usu_reg` INT(11) DEFAULT NULL,
    `fec_act` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(),
    `usu_act` INT(11) DEFAULT NULL,
    `visible` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id_usuario_deposito_programado`),
    UNIQUE KEY `uq_udp_usuario_periodo_tipo` (`id_usuario`, `periodo_inicio`, `periodo_fin`, `tipo_evento`),
    KEY `idx_udp_usuario` (`id_usuario`),
    KEY `idx_udp_estatus` (`estatus`),
    KEY `idx_udp_fecha` (`fecha_ejecucion_programada`),
    CONSTRAINT `fk_udp_usuario`
        FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `detalle_movimiento` (
    `id_detalle_movimiento` INT(11) NOT NULL AUTO_INCREMENT,
    `id_usuario` INT(11) NOT NULL,
    `id_pago` INT(11) DEFAULT NULL,
    `tipo_movimiento` VARCHAR(30) NOT NULL COMMENT 'abono, ajuste, reversa',
    `tipo_origen` VARCHAR(40) NOT NULL COMMENT 'deposito_programado, activacion_qr, corte_semanal',
    `creditos` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `saldo_anterior` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `saldo_nuevo` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `descripcion` TEXT DEFAULT NULL,
    `fec_reg` DATETIME DEFAULT NULL,
    `usu_reg` INT(11) DEFAULT NULL,
    `visible` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id_detalle_movimiento`),
    KEY `idx_dm_usuario` (`id_usuario`),
    KEY `idx_dm_pago` (`id_pago`),
    KEY `idx_dm_origen` (`tipo_origen`),
    CONSTRAINT `fk_dm_usuario`
        FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_dm_pago`
        FOREIGN KEY (`id_pago`) REFERENCES `pagos` (`id_pago`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `usuario_deposito_programado_aplicacion` (
    `id_usuario_deposito_programado_aplicacion` INT(11) NOT NULL AUTO_INCREMENT,
    `id_usuario_deposito_programado` INT(11) NOT NULL,
    `tipo_evento` VARCHAR(20) NOT NULL COMMENT 'activacion, semanal',
    `periodo_inicio` DATE NOT NULL,
    `periodo_fin` DATE NOT NULL,
    `fecha_aplicacion` DATETIME NOT NULL,
    `monto_aplicado` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `id_pago` INT(11) DEFAULT NULL,
    `id_movimiento` INT(11) DEFAULT NULL,
    `estatus_aplicacion` VARCHAR(20) NOT NULL DEFAULT 'aplicado' COMMENT 'pendiente, aplicado, parcial, error',
    `detalle_error` TEXT DEFAULT NULL,
    `intento` INT(11) NOT NULL DEFAULT 1,
    `fec_reg` DATETIME DEFAULT NULL,
    `usu_reg` INT(11) DEFAULT NULL,
    `fec_act` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(),
    `usu_act` INT(11) DEFAULT NULL,
    `visible` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id_usuario_deposito_programado_aplicacion`),
    KEY `idx_udpa_programado` (`id_usuario_deposito_programado`),
    KEY `idx_udpa_estatus` (`estatus_aplicacion`),
    KEY `idx_udpa_fecha` (`fecha_aplicacion`),
    CONSTRAINT `fk_udpa_programado`
        FOREIGN KEY (`id_usuario_deposito_programado`) REFERENCES `usuario_deposito_programado` (`id_usuario_deposito_programado`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_udpa_pago`
        FOREIGN KEY (`id_pago`) REFERENCES `pagos` (`id_pago`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_udpa_movimiento`
        FOREIGN KEY (`id_movimiento`) REFERENCES `detalle_movimiento` (`id_detalle_movimiento`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
