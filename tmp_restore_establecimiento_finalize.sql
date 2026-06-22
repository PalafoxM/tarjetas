INSERT INTO `establecimiento` (
  `id_establecimiento`,`id_tipo`,`no_proveedor`,`dsc_establecimiento`,`direccion`,`telefono`,`ubicacion`,`fec_reg`,`usu_reg`,`fec_act`,`usu_act`,`visible`
)
SELECT
  s.`id_establecimiento`,s.`id_tipo`,s.`no_proveedor`,s.`dsc_establecimiento`,s.`direccion`,s.`telefono`,s.`ubicacion`,s.`fec_reg`,s.`usu_reg`,s.`fec_act`,s.`usu_act`,s.`visible`
FROM `establecimiento_restore_stage` s
LEFT JOIN `establecimiento` e
  ON e.id_establecimiento = s.id_establecimiento
WHERE e.id_establecimiento IS NULL;

DROP TABLE `establecimiento_restore_stage`;

SELECT COUNT(*) AS total_final, MIN(id_establecimiento) AS min_id, MAX(id_establecimiento) AS max_id FROM `establecimiento`;
SELECT id_establecimiento, no_proveedor, dsc_establecimiento FROM `establecimiento` WHERE id_establecimiento IN (1,2,3,4,62,63,64,79,80,89,90,91) ORDER BY id_establecimiento;