-- TICKET 04I-M
-- Log idempotente para conciliar pagos autorizados contra cat_partida.
-- Ejecutar antes de desplegar el codigo conciliador.

CREATE TABLE IF NOT EXISTS `pago_partida_movimiento` (
    `id_pago_partida_movimiento` INT(11) NOT NULL AUTO_INCREMENT,
    `id_pago` INT(11) NOT NULL,
    `id_solicitud_pago` INT(11) DEFAULT NULL,
    `id_partida` INT(11) NOT NULL,
    `tipo_movimiento` VARCHAR(20) NOT NULL COMMENT 'aplicacion, reversion',
    `monto` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `saldo_anterior` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `saldo_nuevo` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `descripcion` VARCHAR(255) DEFAULT NULL,
    `fec_reg` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `usu_reg` INT(11) DEFAULT NULL,
    `visible` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id_pago_partida_movimiento`),
    UNIQUE KEY `uk_ppm_pago_tipo` (`id_pago`, `tipo_movimiento`),
    KEY `idx_ppm_solicitud_pago` (`id_solicitud_pago`),
    KEY `idx_ppm_partida` (`id_partida`),
    CONSTRAINT `fk_ppm_pago`
        FOREIGN KEY (`id_pago`) REFERENCES `pagos` (`id_pago`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_ppm_solicitud_pago`
        FOREIGN KEY (`id_solicitud_pago`) REFERENCES `solicitud_pago` (`id_solicitud_pago`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_ppm_partida`
        FOREIGN KEY (`id_partida`) REFERENCES `cat_partida` (`id_partida`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
