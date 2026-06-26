<?php
$solicitudAlta = is_array($solicitudAlta ?? null) ? $solicitudAlta : [];
$grupo = strtolower((string) ($solicitudAlta['grupo'] ?? 'fic'));
$esFic = $grupo === 'fic';
$perfilOptions = is_array($solicitudAlta['perfil_options'] ?? null) ? $solicitudAlta['perfil_options'] : [];
$establecimientoId = (int) ($solicitudAlta['establecimiento_id'] ?? 0);
$pageTitle = (string) ($solicitudAlta['title'] ?? 'Solicitud de folio de usuario');
$pageSubtitle = (string) ($solicitudAlta['subtitle'] ?? 'Captura los datos del usuario y el perfil solicitado.');
$backUrl = (string) ($solicitudAlta['back_url'] ?? base_url('index.php/Inicio'));
$saveUrl = (string) ($solicitudAlta['save_url'] ?? base_url('index.php/Inicio'));
$catalogosUrl = (string) ($solicitudAlta['catalogos_url'] ?? base_url('index.php/Usuario/getCatalogosCrud'));
?>
<style>
    .solicitud-alta-page {
        min-height: calc(100vh - 140px);
    }

    .solicitud-alta-shell {
        background: linear-gradient(180deg, rgba(17, 24, 39, .96), rgba(15, 23, 42, .98));
        border: 1px solid rgba(148, 163, 184, .16);
        border-radius: 18px;
        box-shadow: 0 18px 44px rgba(0, 0, 0, .28);
    }

    .solicitud-alta-note {
        background: rgba(224, 242, 254, .12);
        border: 1px solid rgba(96, 165, 250, .22);
        color: #dbeafe;
        border-radius: 14px;
    }

    .solicitud-alta-page .form-label {
        color: #cbd5e1;
        font-weight: 600;
    }

    .solicitud-alta-page .form-control,
    .solicitud-alta-page .form-select,
    .solicitud-alta-page textarea {
        background-color: #111827;
        border-color: rgba(148, 163, 184, .28);
        color: #f8fafc;
    }

    .solicitud-alta-page .form-control:focus,
    .solicitud-alta-page .form-select:focus,
    .solicitud-alta-page textarea:focus {
        border-color: #60a5fa;
        box-shadow: 0 0 0 .2rem rgba(59, 130, 246, .16);
    }

    .solicitud-alta-page .crud-ui-upper { text-transform: uppercase; }
    .solicitud-alta-page .crud-ui-lower { text-transform: lowercase; }
    .solicitud-alta-page .select2-container { width: 100% !important; }
</style>

<div class="container-fluid py-4 solicitud-alta-page" id="solicitudAltaPage"
     data-grupo="<?= esc($grupo, 'attr') ?>"
     data-save-url="<?= esc($saveUrl, 'attr') ?>"
     data-back-url="<?= esc($backUrl, 'attr') ?>"
     data-catalogos-url="<?= esc($catalogosUrl, 'attr') ?>">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <div>
            <div class="badge bg-info mb-2"><?= esc(strtoupper($grupo)) ?></div>
            <h3 class="mb-1 text-white"><?= esc($pageTitle) ?></h3>
            <p class="text-muted mb-0"><?= esc($pageSubtitle) ?></p>
        </div>
        <a href="<?= esc($backUrl, 'attr') ?>" class="btn btn-outline-secondary">
            <i class="mdi mdi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="card solicitud-alta-shell">
        <div class="card-body p-4">
            <div class="alert alert-info solicitud-alta-note mb-4" role="alert">
                Completa la solicitud y envíala al catálogo correspondiente. Los campos de partida y montos no se muestran en pantalla.
            </div>

            <form id="<?= esc($esFic ? 'formSolicitudUsuarioFic' : 'formSolicitudUsuarioCatalogo', 'attr') ?>" autocomplete="off">
                <?= csrf_field() ?>
                <div class="alert alert-info d-none mb-3" id="<?= esc($esFic ? 'solicitudUsuarioFicAlert' : 'solicitudUsuarioCatalogoAlert', 'attr') ?>" role="alert"></div>
                <input type="hidden" id="<?= esc($esFic ? 'solicitud_usuario_id_fic' : 'solicitud_usuario_catalogo_id', 'attr') ?>" name="id_solicitud_usuario" value="0">
                <input type="hidden" id="<?= esc($esFic ? 'solicitud_usuario_establecimiento_fic' : 'solicitud_usuario_catalogo_establecimiento', 'attr') ?>" name="id_establecimiento" value="<?= esc((string) $establecimientoId, 'attr') ?>">

                <div class="row g-3">
                    <?php if ($esFic): ?>
                        <div class="col-12 col-md-3">
                            <label class="form-label" for="solicitud_fic_categoria">Categoría</label>
                            <select class="form-select js-select2-catalog" id="solicitud_fic_categoria" required>
                                <option value="">Seleccione</option>
                            </select>
                            <input type="hidden" name="id_clave" id="solicitud_fic_id_clave">
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label" for="solicitud_fic_pais">País o región</label>
                            <select class="form-select js-select2-catalog" name="id_pais" id="solicitud_fic_pais" required>
                                <option value="">Seleccione</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label" for="solicitud_fic_disciplina">Disciplina</label>
                            <select class="form-select js-select2-catalog" name="id_diciplina" id="solicitud_fic_disciplina" required>
                                <option value="">Seleccione</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label" for="solicitud_fic_clave">Clave</label>
                            <input type="text" class="form-control crud-ui-lower" id="solicitud_fic_clave" readonly>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label" for="solicitud_fic_folio">Folio</label>
                            <input type="text" class="form-control" id="solicitud_fic_folio" inputmode="numeric" pattern="[0-9]*" maxlength="20" required placeholder="folio">
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label" for="solicitud_fic_subfolio">Subfolio</label>
                            <input type="text" class="form-control crud-ui-upper" id="solicitud_fic_subfolio" maxlength="20" required placeholder="subfolio">
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label" for="solicitud_fic_pax">Pax</label>
                            <input type="number" class="form-control" id="solicitud_fic_pax" min="1" required placeholder="pax">
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label" for="solicitud_fic_anfitrion">Anfitrión Gto</label>
                            <input type="text" class="form-control crud-ui-upper" id="solicitud_fic_anfitrion" maxlength="80" required placeholder="ANFITRIÓN GTO">
                        </div>
                    <?php endif; ?>

                    <div class="col-12 col-md-4">
                        <label class="form-label" for="<?= esc($esFic ? 'solicitud_usuario_fic_perfil' : 'solicitud_usuario_catalogo_perfil', 'attr') ?>">Perfil solicitado</label>
                        <select class="form-select" id="<?= esc($esFic ? 'solicitud_usuario_fic_perfil' : 'solicitud_usuario_catalogo_perfil', 'attr') ?>" name="id_perfil_solicitado" required>
                            <option value="">Selecciona una opción</option>
                            <?php foreach ($perfilOptions as $perfilOption): ?>
                                <?php if ($esFic): ?>
                                    <option value="<?= esc((string) ($perfilOption['id_perfil_fic'] ?? 0), 'attr') ?>"><?= esc((string) ($perfilOption['dsc_perfil'] ?? '')) ?></option>
                                <?php else: ?>
                                    <option value="<?= esc((string) ($perfilOption['id_perfil'] ?? 0), 'attr') ?>"><?= esc((string) ($perfilOption['dsc_perfil'] ?? '')) ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label" for="<?= esc($esFic ? 'solicitud_fic_beneficios' : 'solicitud_usuario_catalogo_beneficios', 'attr') ?>">Beneficios</label>
                        <select class="form-select" id="<?= esc($esFic ? 'solicitud_fic_beneficios' : 'solicitud_usuario_catalogo_beneficios', 'attr') ?>" name="beneficios" required>
                            <option value="ninguno">Ninguno</option>
                            <option value="hospedaje">Hospedaje</option>
                            <option value="alimentos">Alimentos</option>
                            <option value="ambos">Ambos</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label" for="<?= esc($esFic ? 'solicitud_usuario_fic_nombre' : 'solicitud_usuario_catalogo_nombre', 'attr') ?>">Nombre</label>
                        <input type="text" class="form-control crud-ui-upper" id="<?= esc($esFic ? 'solicitud_usuario_fic_nombre' : 'solicitud_usuario_catalogo_nombre', 'attr') ?>" name="nombre" required autocomplete="off" placeholder="NOMBRE">
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label" for="<?= esc($esFic ? 'solicitud_usuario_fic_primer_apellido' : 'solicitud_usuario_catalogo_primer_apellido', 'attr') ?>">Primer apellido</label>
                        <input type="text" class="form-control crud-ui-upper" id="<?= esc($esFic ? 'solicitud_usuario_fic_primer_apellido' : 'solicitud_usuario_catalogo_primer_apellido', 'attr') ?>" name="primer_apellido" required autocomplete="off" placeholder="PRIMER APELLIDO">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label" for="<?= esc($esFic ? 'solicitud_usuario_fic_segundo_apellido' : 'solicitud_usuario_catalogo_segundo_apellido', 'attr') ?>">Segundo apellido</label>
                        <input type="text" class="form-control crud-ui-upper" id="<?= esc($esFic ? 'solicitud_usuario_fic_segundo_apellido' : 'solicitud_usuario_catalogo_segundo_apellido', 'attr') ?>" name="segundo_apellido" autocomplete="off" placeholder="SEGUNDO APELLIDO">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label" for="<?= esc($esFic ? 'solicitud_usuario_fic_correo' : 'solicitud_usuario_catalogo_correo', 'attr') ?>">Correo (opcional)</label>
                        <input type="email" class="form-control crud-ui-lower" id="<?= esc($esFic ? 'solicitud_usuario_fic_correo' : 'solicitud_usuario_catalogo_correo', 'attr') ?>" name="correo" autocomplete="off" placeholder="correo@dominio.com">
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="<?= esc($esFic ? 'solicitud_usuario_fic_observaciones' : 'solicitud_usuario_catalogo_observaciones', 'attr') ?>">Observaciones</label>
                        <textarea class="form-control" id="<?= esc($esFic ? 'solicitud_usuario_fic_observaciones' : 'solicitud_usuario_catalogo_observaciones', 'attr') ?>" name="observaciones" rows="4" placeholder="Opcional"></textarea>
                    </div>
                </div>

                <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">
                    <a href="<?= esc($backUrl, 'attr') ?>" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Enviar solicitud</button>
                </div>
            </form>
        </div>
    </div>
</div>