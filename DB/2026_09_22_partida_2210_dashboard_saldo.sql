-- Ajuste temporal de saldo visible para dashboard Partidas FIC.
-- No modifica visibilidad ni partidas 3390A/3390B.

USE `tarjetas`;

UPDATE `cat_partida`
SET
    `monto_disponible` = 216920.00,
    `fec_act` = NOW()
WHERE `id_partida` = 1
  AND `partida` = '2210';
