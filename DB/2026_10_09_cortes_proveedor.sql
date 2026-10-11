-- Cortes de proveedores con ventana global y cierre individual por establecimiento.
-- NO ejecutar sin respaldo previo. Preparado para revision.

CREATE TABLE IF NOT EXISTS `cortes_proveedor` (
    `id_corte` INT(11) NOT NULL AUTO_INCREMENT,
    `numero_corte` INT(11) NOT NULL,
    `nombre` VARCHAR(150) NOT NULL,
    `fecha_inicio_recepcion` DATETIME NOT NULL,
    `fecha_fin_recepcion` DATETIME NOT NULL,
    `fecha_inicio_periodo_global` DATETIME DEFAULT NULL,
    `visible` TINYINT(1) NOT NULL DEFAULT 1,
    `fec_reg` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fec_act` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `usu_reg` INT(11) DEFAULT NULL,
    `usu_act` INT(11) DEFAULT NULL,
    PRIMARY KEY (`id_corte`),
    UNIQUE KEY `uq_cortes_proveedor_numero` (`numero_corte`),
    KEY `idx_cortes_proveedor_ventana` (`fecha_inicio_recepcion`, `fecha_fin_recepcion`),
    KEY `idx_cortes_proveedor_visible` (`visible`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `proveedor_corte_periodo` (
    `id_periodo` INT(11) NOT NULL AUTO_INCREMENT,
    `id_corte` INT(11) NOT NULL,
    `id_proveedor` INT(11) NOT NULL,
    `id_establecimiento` INT(11) NOT NULL,
    `numero_periodo` TINYINT(1) NOT NULL,
    `fecha_inicio_periodo` DATETIME NOT NULL,
    `fecha_fin_periodo` DATETIME DEFAULT NULL,
    `fecha_envio` DATETIME DEFAULT NULL,
    `estado` ENUM('pendiente','documentos','cerrado') NOT NULL DEFAULT 'pendiente',
    `reporte_url` TEXT DEFAULT NULL,
    `id_factura` INT(11) DEFAULT NULL,
    `factura_pdf` TEXT DEFAULT NULL,
    `factura_xml` TEXT DEFAULT NULL,
    `fec_reg` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fec_act` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `usu_reg` INT(11) DEFAULT NULL,
    `usu_act` INT(11) DEFAULT NULL,
    `visible` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id_periodo`),
    UNIQUE KEY `uq_periodo_establecimiento_corte_numero` (`id_corte`, `id_establecimiento`, `numero_periodo`),
    KEY `idx_periodo_proveedor` (`id_proveedor`),
    KEY `idx_periodo_establecimiento` (`id_establecimiento`),
    KEY `idx_periodo_corte_establecimiento_estado` (`id_corte`, `id_establecimiento`, `estado`),
    KEY `idx_periodo_estado` (`estado`),
    CONSTRAINT `chk_periodo_numero`
        CHECK (`numero_periodo` IN (1, 2)),
    CONSTRAINT `fk_periodo_corte`
        FOREIGN KEY (`id_corte`) REFERENCES `cortes_proveedor` (`id_corte`)
        ON UPDATE CASCADE,
    CONSTRAINT `fk_periodo_proveedor`
        FOREIGN KEY (`id_proveedor`) REFERENCES `proveedor` (`id_proveedor`)
        ON UPDATE CASCADE,
    CONSTRAINT `fk_periodo_establecimiento`
        FOREIGN KEY (`id_establecimiento`) REFERENCES `establecimiento` (`id_establecimiento`)
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cortes_proveedor` (
    `numero_corte`,
    `nombre`,
    `fecha_inicio_recepcion`,
    `fecha_fin_recepcion`,
    `fecha_inicio_periodo_global`,
    `visible`,
    `fec_reg`
)
SELECT
    1,
    'Primer corte proveedores 2026',
    '2026-10-09 00:00:00',
    '2026-10-19 23:59:59',
    '2026-09-13 00:00:00',
    1,
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `cortes_proveedor` WHERE `numero_corte` = 1
);

-- Rollback sugerido, previa validacion:
-- DROP TABLE IF EXISTS `proveedor_corte_periodo`;
-- DROP TABLE IF EXISTS `cortes_proveedor`;
