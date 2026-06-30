-- Compatibilidad BD para contrato de la app.
-- Fuente funcional: C:\wamp64\www\pagosqr\CONTRATO_API_PHP.md
-- Este script no crea endpoints PHP. Mantiene la comunicacion indirecta por BD.

USE `tarjetas`;

-- Corrige el problema de DEFINER heredado y expone saldos reales desde usuario.
-- Importante: la vista anterior tomaba monto_deposito desde cat_nivel_cliente;
-- la app requiere el saldo operativo guardado en usuario.
DROP VIEW IF EXISTS `vw_usuario`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `vw_usuario` AS
SELECT
    `u`.`id_usuario` AS `id_usuario`,
    `u`.`usuario` AS `usuario`,
    `u`.`contrasenia` AS `contrasenia`,
    `u`.`id_establecimiento` AS `id_establecimiento`,
    `u`.`id_perfil` AS `id_perfil`,
    `u`.`id_nivel_cliente` AS `id_nivel_cliente`,
    `u`.`id_partida` AS `id_partida`,
    `u`.`id_fic_perfil` AS `id_fic_perfil`,
    `u`.`id_ug_perfil` AS `id_ug_perfil`,
    `u`.`id_estatus_hotel` AS `id_estatus_hotel`,
    `u`.`id_establecimiento_hotel` AS `id_establecimiento_hotel`,
    CONCAT_WS(' ', `u`.`nombre`, `u`.`primer_apellido`, `u`.`segundo_apellido`) AS `nombre_completo`,
    `u`.`id_tipo_habitacion` AS `id_tipo_habitacion`,
    `u`.`id_secul_perfil` AS `id_secul_perfil`,
    `u`.`id_secturi_perfil` AS `id_secturi_perfil`,
    `u`.`id_tipo_proveedor` AS `id_tipo_proveedor`,
    `u`.`id_proveedor` AS `id_proveedor`,
    `u`.`id_pais` AS `id_pais`,
    `u`.`id_diciplina` AS `id_diciplina`,
    `u`.`id_clave` AS `id_clave`,
    `u`.`id_estado` AS `id_estado`,
    `u`.`nombre` AS `nombre`,
    `u`.`primer_apellido` AS `primer_apellido`,
    `u`.`segundo_apellido` AS `segundo_apellido`,
    `u`.`correo` AS `correo`,
    `u`.`monto_deposito` AS `monto_deposito`,
    `u`.`monto_deposito` AS `monto`,
    `u`.`monto_deposito_hotel` AS `monto_deposito_hotel`,
    `u`.`monto_deposito_hotel` AS `monto_hotel`,
    `u`.`monto_deposito_reservado` AS `monto_deposito_reservado`,
    `u`.`monto_deposito_operativo` AS `monto_deposito_operativo`,
    `u`.`deposito_programado_estatus` AS `deposito_programado_estatus`,
    `u`.`tiene_alimentos` AS `tiene_alimentos`,
    `u`.`tiene_hospedaje` AS `tiene_hospedaje`,
    `u`.`nip` AS `nip`,
    `u`.`folio` AS `folio`,
    `u`.`ruta_foto_relativa` AS `ruta_foto_relativa`,
    `u`.`fecha_check_in` AS `fecha_check_in`,
    `u`.`fecha_check_out` AS `fecha_check_out`,
    `u`.`fec_vigencia_desde` AS `fec_vigencia_desde`,
    `u`.`fec_vigencia_hasta` AS `fec_vigencia_hasta`,
    `u`.`noche` AS `noche`,
    `u`.`tarifa_noche` AS `tarifa_noche`,
    `u`.`tarifa_noche` AS `tarifa_hotel`,
    `u`.`tarifa_total` AS `tarifa_total`,
    `u`.`activo_qr` AS `activo_qr`,
    `u`.`qr` AS `qr`,
    `u`.`qr` AS `codigo_qr`,
    `u`.`api_token` AS `api_token`,
    `u`.`api_token_expira` AS `api_token_expira`,
    `cc`.`clave` AS `clave`,
    `cc`.`dsc_clave` AS `dsc_clave`,
    `cd`.`des_diciplina` AS `dsc_diciplina`,
    `cpa`.`dsc_pais` AS `dsc_pais`,
    `cnc`.`monto_deposito` AS `monto_deposito_catalogo`,
    `a`.`partida` AS `partida`,
    `a`.`des_partida` AS `des_partida`,
    `u`.`visible` AS `visible`
FROM `usuario` `u`
LEFT JOIN `cat_claves` `cc` ON `u`.`id_clave` = `cc`.`id_clave`
LEFT JOIN `cat_perfil` `cp` ON `u`.`id_perfil` = `cp`.`id_perfil`
LEFT JOIN `cat_partida` `a` ON `u`.`id_partida` = `a`.`id_partida`
LEFT JOIN `cat_pais` `cpa` ON `cpa`.`id_pais` = `u`.`id_pais`
LEFT JOIN `cat_nivel_cliente` `cnc` ON `cnc`.`id_nivel_cliente` = `u`.`id_nivel_cliente`
LEFT JOIN `cat_diciplina` `cd` ON `u`.`id_diciplina` = `cd`.`id_diciplina`;

-- Vista de compatibilidad para fallback legado POST /getTabla tabla=transactions.
-- La API de la app debe preferir endpoints semanticos, pero esta vista mantiene aliases
-- esperados por la app para estatus y consumos del proveedor del dia.
DROP VIEW IF EXISTS `transactions`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `transactions` AS
SELECT
    `sp`.`id_solicitud_pago` AS `id`,
    `sp`.`folio_solicitud` AS `transaction_id`,
    `p`.`id_pago` AS `payment_id`,
    `sp`.`id_solicitud_pago` AS `solicitud_pago_id`,
    `sp`.`id_usuario` AS `client_user_id`,
    `sp`.`id_usuario` AS `id_usuario`,
    CONCAT_WS(' ', `u`.`nombre`, `u`.`primer_apellido`, `u`.`segundo_apellido`) AS `cliente`,
    CONCAT_WS(' ', `u`.`nombre`, `u`.`primer_apellido`, `u`.`segundo_apellido`) AS `nombre_cliente`,
    CONCAT_WS(' ', `u`.`nombre`, `u`.`primer_apellido`, `u`.`segundo_apellido`) AS `nombre_completo`,
    `sp`.`id_establecimiento` AS `id_establecimiento`,
    `e`.`dsc_establecimiento` AS `dsc_establecimiento`,
    `e`.`no_proveedor` AS `providerRef`,
    `e`.`no_proveedor` AS `provider_ref`,
    `e`.`no_proveedor` AS `no_proveedor`,
    CAST(`sp`.`monto_solicitado` AS DECIMAL(10,2)) AS `amount`,
    CAST(`sp`.`monto_solicitado` AS DECIMAL(10,2)) AS `monto`,
    CAST(`sp`.`monto_solicitado` AS DECIMAL(10,2)) AS `subtotal`,
    COALESCE(CAST(NULLIF(`p`.`propina`, '') AS DECIMAL(10,2)), 0.00) AS `tip`,
    COALESCE(CAST(NULLIF(`p`.`propina`, '') AS DECIMAL(10,2)), 0.00) AS `propina`,
    CAST(`sp`.`monto_solicitado` AS DECIMAL(10,2)) + COALESCE(CAST(NULLIF(`p`.`propina`, '') AS DECIMAL(10,2)), 0.00) AS `total`,
    CASE `sp`.`estatus`
        WHEN 'autorizado' THEN 'approved'
        WHEN 'rechazado' THEN 'rejected'
        WHEN 'cancelado' THEN 'rejected'
        ELSE 'pending'
    END AS `status`,
    CASE `sp`.`estatus`
        WHEN 'autorizado' THEN 'approved'
        WHEN 'rechazado' THEN 'rejected'
        WHEN 'cancelado' THEN 'rejected'
        ELSE 'pending'
    END AS `estado`,
    `sp`.`estatus` AS `estatus`,
    `sp`.`metodo_autorizacion` AS `paymentMethod`,
    `sp`.`metodo_autorizacion` AS `payment_method`,
    `sp`.`motivo_rechazo` AS `motivo_rechazo`,
    `sp`.`observaciones` AS `observaciones`,
    `u`.`monto_deposito` AS `current_balance`,
    `sp`.`fec_reg` AS `created_at`,
    `sp`.`fec_reg` AS `fecha`,
    `sp`.`fec_reg` AS `fecha_registro`,
    COALESCE(`sp`.`fec_act`, `sp`.`fecha_respuesta`, `p`.`fec_reg`, `sp`.`fec_reg`) AS `updated_at`,
    `sp`.`visible` AS `visible`
FROM `solicitud_pago` `sp`
LEFT JOIN `pagos` `p` ON `p`.`id_solicitud_pago` = `sp`.`id_solicitud_pago` AND `p`.`visible` = 1
LEFT JOIN `usuario` `u` ON `u`.`id_usuario` = `sp`.`id_usuario`
LEFT JOIN `establecimiento` `e` ON `e`.`id_establecimiento` = `sp`.`id_establecimiento`
WHERE `sp`.`visible` = 1;
