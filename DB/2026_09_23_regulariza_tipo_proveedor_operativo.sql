-- Regularizacion de id_tipo_proveedor para usuarios operativos proveedor.
-- No modifica proveedores principales (id_perfil = 2).
-- Ejecutar manualmente y revisar los SELECT antes y despues.

-- Conteo previo por perfil/tipo proveedor.
SELECT
    u.id_perfil,
    cp.dsc_perfil AS perfil,
    u.id_tipo_proveedor,
    ctp.dsc_perfil AS tipo_proveedor,
    COUNT(*) AS total
FROM usuario u
LEFT JOIN cat_perfil cp ON cp.id_perfil = u.id_perfil
LEFT JOIN cat_tipo_proveedor ctp ON ctp.id_tipo_proveedor = u.id_tipo_proveedor
WHERE u.id_perfil IN (5, 7)
  AND (
        (u.id_perfil = 5 AND COALESCE(u.id_tipo_proveedor, 0) <> 2)
     OR (u.id_perfil = 7 AND COALESCE(u.id_tipo_proveedor, 0) <> 3)
  )
GROUP BY u.id_perfil, cp.dsc_perfil, u.id_tipo_proveedor, ctp.dsc_perfil
ORDER BY u.id_perfil, u.id_tipo_proveedor;

-- Detalle previo de filas candidatas.
SELECT
    u.id_usuario,
    u.usuario,
    u.id_perfil,
    cp.dsc_perfil AS perfil,
    u.id_tipo_proveedor,
    ctp.dsc_perfil AS tipo_proveedor_actual,
    CASE
        WHEN u.id_perfil = 5 THEN 2
        WHEN u.id_perfil = 7 THEN 3
    END AS id_tipo_proveedor_esperado,
    u.id_proveedor,
    u.id_establecimiento,
    u.visible
FROM usuario u
LEFT JOIN cat_perfil cp ON cp.id_perfil = u.id_perfil
LEFT JOIN cat_tipo_proveedor ctp ON ctp.id_tipo_proveedor = u.id_tipo_proveedor
WHERE u.id_perfil IN (5, 7)
  AND (
        (u.id_perfil = 5 AND COALESCE(u.id_tipo_proveedor, 0) <> 2)
     OR (u.id_perfil = 7 AND COALESCE(u.id_tipo_proveedor, 0) <> 3)
  )
ORDER BY u.id_perfil, u.id_usuario;

-- Regularizacion: solo id_tipo_proveedor y fec_act.
UPDATE usuario
SET
    id_tipo_proveedor = CASE
        WHEN id_perfil = 5 THEN 2
        WHEN id_perfil = 7 THEN 3
        ELSE id_tipo_proveedor
    END,
    fec_act = NOW()
WHERE id_perfil IN (5, 7)
  AND (
        (id_perfil = 5 AND COALESCE(id_tipo_proveedor, 0) <> 2)
     OR (id_perfil = 7 AND COALESCE(id_tipo_proveedor, 0) <> 3)
  );

-- Conteo posterior: debe regresar cero filas.
SELECT
    u.id_perfil,
    cp.dsc_perfil AS perfil,
    u.id_tipo_proveedor,
    ctp.dsc_perfil AS tipo_proveedor,
    COUNT(*) AS total
FROM usuario u
LEFT JOIN cat_perfil cp ON cp.id_perfil = u.id_perfil
LEFT JOIN cat_tipo_proveedor ctp ON ctp.id_tipo_proveedor = u.id_tipo_proveedor
WHERE u.id_perfil IN (5, 7)
  AND (
        (u.id_perfil = 5 AND COALESCE(u.id_tipo_proveedor, 0) <> 2)
     OR (u.id_perfil = 7 AND COALESCE(u.id_tipo_proveedor, 0) <> 3)
  )
GROUP BY u.id_perfil, cp.dsc_perfil, u.id_tipo_proveedor, ctp.dsc_perfil
ORDER BY u.id_perfil, u.id_tipo_proveedor;

-- Verificacion de casos conocidos.
SELECT
    u.id_usuario,
    u.usuario,
    u.id_perfil,
    cp.dsc_perfil AS perfil,
    u.id_tipo_proveedor,
    ctp.dsc_perfil AS tipo_proveedor,
    u.id_proveedor,
    u.id_establecimiento,
    u.visible
FROM usuario u
LEFT JOIN cat_perfil cp ON cp.id_perfil = u.id_perfil
LEFT JOIN cat_tipo_proveedor ctp ON ctp.id_tipo_proveedor = u.id_tipo_proveedor
WHERE u.usuario IN ('trattoria', 'brabbit', 'trattocentro')
ORDER BY u.usuario;
