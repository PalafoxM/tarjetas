-- Agrega control idempotente de alimentos liberados por dia completo.
-- Aplicar sobre la base de datos `tarjetas`.

USE `tarjetas`;

ALTER TABLE `usuario`
    ADD COLUMN `fecha_ultimo_deposito_alimentos` DATE NULL
    AFTER `deposito_programado_estatus`;
