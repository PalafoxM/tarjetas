-- TICKET 04D-B - Folios oficiales SECTURI sin tabla nueva
-- Configura exclusivamente cat_claves_secturi como catalogo y puntero.

-- Auditoria previa opcional de usuarios SECTURI existentes.
SELECT
    u.id_usuario,
    u.usuario,
    u.nombre,
    u.primer_apellido,
    u.id_clave,
    c.clave,
    c.dsc_clave,
    u.folio,
    u.sub_folio
FROM usuario u
LEFT JOIN cat_claves_secturi c ON c.id_clave = u.id_clave
WHERE u.visible = 1
  AND u.id_secturi_perfil IS NOT NULL
  AND u.id_clave IS NOT NULL
ORDER BY u.id_clave, u.folio, u.sub_folio;

-- SD = SIN DISCIPLINA. Verificar en el catalogo correcto; no guardar en cat_claves_secturi.
SELECT id_diciplina, des_diciplina, visible
FROM cat_diciplina
WHERE UPPER(TRIM(des_diciplina)) IN ('SIN DISCIPLINA', 'SD');

START TRANSACTION;

DELETE FROM cat_claves_secturi
WHERE id_cat = 4;

INSERT INTO cat_claves_secturi
    (clave, id_cat, dsc_clave, direccion, folio, folio_hasta, sub_folio, apoyo, visible)
VALUES
    ('TA', 4, 'TURISMO ALIMENTACION', NULL, '1020', '1020', 'A', NULL, 1),
    ('TH', 4, 'TURISMO HOSPEDAJE', NULL, '1111', '1111', 'A', NULL, 1);

COMMIT;

-- Resultado esperado: solo las claves oficiales TA y TH.
SELECT
    id_clave,
    clave,
    id_cat,
    dsc_clave,
    folio,
    folio_hasta,
    sub_folio,
    visible
FROM cat_claves_secturi
ORDER BY id_clave;
