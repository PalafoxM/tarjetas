<?php
$schemaReady = !empty($schemaReady);
$cortes = array_values(array_map(static function ($item) {
    return is_object($item) ? get_object_vars($item) : (array) $item;
}, is_array($cortesProveedor ?? null) ? $cortesProveedor : []));
$seguimiento = array_values(array_map(static function ($item) {
    return is_object($item) ? get_object_vars($item) : (array) $item;
}, is_array($seguimientoCortesProveedor ?? null) ? $seguimientoCortesProveedor : []));

$formatDate = static function ($value): string {
    $value = trim((string) $value);
    return $value !== '' ? $value : 'Sin fecha';
};
?>

<style>
    .provider-cuts-page {
        min-height: calc(100vh - 70px);
        padding: 30px 28px 42px;
        background: linear-gradient(180deg, #0f172a, #111827);
        color: #f8fafc;
    }

    .provider-cuts-card {
        background: rgba(17, 24, 39, .96);
        border: 1px solid rgba(148, 163, 184, .16);
        border-radius: 12px;
        box-shadow: 0 18px 42px rgba(2, 6, 23, .22);
    }

    .provider-cuts-card .form-control,
    .provider-cuts-card .form-select {
        background: #111827;
        border-color: rgba(148, 163, 184, .28);
        color: #f8fafc;
    }

    .provider-cuts-table {
        min-width: 1080px;
    }

    .provider-cuts-wrap {
        overflow-x: auto;
    }
</style>

<div class="provider-cuts-page">
    <header class="mb-4">
        <h3 class="mb-1 text-white">Cortes de proveedores</h3>
        <p class="text-muted mb-0">Configura ventanas globales y consulta cierres individuales por establecimiento.</p>
    </header>

    <?php if (!$schemaReady): ?>
        <div class="alert alert-warning">
            La estructura de cortes de proveedores no esta instalada. Revisa y ejecuta manualmente
            <code>DB/2026_10_09_cortes_proveedor.sql</code> antes de operar esta pantalla.
        </div>
    <?php else: ?>
        <section class="card provider-cuts-card mb-4">
            <div class="card-body">
                <h5 class="text-white mb-3">Crear corte</h5>
                <form id="form-corte-proveedor" class="row g-3">
                    <input type="hidden" name="id_corte" id="id_corte" value="">
                    <div class="col-12 col-md-2">
                        <label class="form-label">Numero</label>
                        <input class="form-control" type="number" min="1" name="numero_corte" id="numero_corte" required>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Nombre</label>
                        <input class="form-control" type="text" name="nombre" id="nombre" required>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Inicio recepcion</label>
                        <input class="form-control" type="datetime-local" name="fecha_inicio_recepcion" id="fecha_inicio_recepcion" required>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Fin recepcion</label>
                        <input class="form-control" type="datetime-local" name="fecha_fin_recepcion" id="fecha_fin_recepcion" required>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Inicio global del periodo</label>
                        <input class="form-control" type="datetime-local" name="fecha_inicio_periodo_global" id="fecha_inicio_periodo_global">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Visible</label>
                        <select class="form-select" name="visible" id="visible">
                            <option value="1">Visible</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6 d-flex align-items-end gap-2">
                        <button class="btn btn-primary" type="submit">Guardar corte</button>
                        <button class="btn btn-outline-light" type="button" id="limpiar-corte-form">Limpiar</button>
                    </div>
                </form>
            </div>
        </section>

        <section class="card provider-cuts-card mb-4">
            <div class="card-body">
                <h5 class="text-white mb-3">Cortes configurados</h5>
                <div class="provider-cuts-wrap">
                    <table class="table table-dark table-striped provider-cuts-table align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nombre</th>
                                <th>Recepcion</th>
                                <th>Inicio global</th>
                                <th>Estado</th>
                                <th>Cierres</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cortes as $corte): ?>
                                <tr>
                                    <td><?= (int) ($corte['numero_corte'] ?? 0) ?></td>
                                    <td><?= esc((string) ($corte['nombre'] ?? '')) ?></td>
                                    <td><?= esc($formatDate($corte['fecha_inicio_recepcion'] ?? '')) ?> - <?= esc($formatDate($corte['fecha_fin_recepcion'] ?? '')) ?></td>
                                    <td><?= esc($formatDate($corte['fecha_inicio_periodo_global'] ?? '')) ?></td>
                                    <td><span class="badge bg-info"><?= esc((string) ($corte['estado_calculado'] ?? '')) ?></span></td>
                                    <td><?= (int) ($corte['cierres_enviados'] ?? 0) ?></td>
                                    <td>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-light js-editar-corte"
                                            data-corte='<?= esc(json_encode($corte), 'attr') ?>'
                                            <?= ((int) ($corte['cierres_enviados'] ?? 0) > 0) ? 'disabled' : '' ?>>
                                            Editar
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-warning js-toggle-corte"
                                            data-id-corte="<?= (int) ($corte['id_corte'] ?? 0) ?>"
                                            data-visible="<?= ((int) ($corte['visible'] ?? 0) === 1) ? '0' : '1' ?>"
                                            <?= ((int) ($corte['cierres_enviados'] ?? 0) > 0) ? 'disabled' : '' ?>>
                                            <?= ((int) ($corte['visible'] ?? 0) === 1) ? 'Desactivar' : 'Activar' ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="card provider-cuts-card">
            <div class="card-body">
                <h5 class="text-white mb-3">Seguimiento por establecimiento</h5>
                <div class="provider-cuts-wrap">
                    <table class="table table-dark table-striped provider-cuts-table align-middle">
                        <thead>
                            <tr>
                                <th>Corte</th>
                                <th>Proveedor</th>
                                <th>Establecimiento</th>
                                <th>Usados</th>
                                <th>Periodo actual</th>
                                <th>Estado</th>
                                <th>Inicio periodo</th>
                                <th>Cierre</th>
                                <th>Reporte</th>
                                <th>PDF</th>
                                <th>XML</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($seguimiento as $row): ?>
                                <tr>
                                    <td><?= (int) ($row['numero_corte'] ?? 0) ?></td>
                                    <td><?= esc((string) ($row['proveedor'] ?? '')) ?></td>
                                    <td><?= esc((string) ($row['establecimiento'] ?? '')) ?></td>
                                    <td><?= (int) ($row['facturas_emitidas'] ?? 0) ?>/2</td>
                                    <td><?= (int) ($row['numero_periodo'] ?? 1) ?></td>
                                    <td>
                                        <?php if (!empty($row['ciclo_finalizado'])): ?>
                                            <span class="badge bg-success">Ciclo finalizado</span>
                                        <?php elseif (($row['estado'] ?? '') === 'cerrado'): ?>
                                            <span class="badge bg-success">Enviado/Cerrado</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Pendiente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= esc($formatDate($row['fecha_inicio_periodo'] ?? '')) ?></td>
                                    <td><?= esc($formatDate($row['fecha_fin_periodo'] ?? '')) ?></td>
                                    <td><?= !empty($row['tiene_reporte']) ? 'Si' : 'No' ?></td>
                                    <td><?= !empty($row['tiene_pdf']) ? 'Si' : 'No' ?></td>
                                    <td><?= !empty($row['tiene_xml']) ? 'Si' : 'No' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    <?php endif; ?>
</div>

<script>
(function () {
    var form = document.getElementById('form-corte-proveedor');
    if (!form) return;

    function toLocalInput(value) {
        value = String(value || '').trim();
        return value ? value.replace(' ', 'T').slice(0, 16) : '';
    }

    document.querySelectorAll('.js-editar-corte').forEach(function (button) {
        button.addEventListener('click', function () {
            var corte = JSON.parse(this.getAttribute('data-corte') || '{}');
            form.id_corte.value = corte.id_corte || '';
            form.numero_corte.value = corte.numero_corte || '';
            form.nombre.value = corte.nombre || '';
            form.fecha_inicio_recepcion.value = toLocalInput(corte.fecha_inicio_recepcion);
            form.fecha_fin_recepcion.value = toLocalInput(corte.fecha_fin_recepcion);
            form.fecha_inicio_periodo_global.value = toLocalInput(corte.fecha_inicio_periodo_global);
            form.visible.value = String(corte.visible || '0');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });

    document.getElementById('limpiar-corte-form').addEventListener('click', function () {
        form.reset();
        form.id_corte.value = '';
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        $.ajax({
            url: '<?= esc(base_url('index.php/Inicio/guardarCorteProveedor'), 'js') ?>',
            type: 'POST',
            dataType: 'json',
            data: $(form).serialize()
        }).done(function (response) {
            Swal.fire(response && response.error ? 'Atencion' : 'Correcto', response && response.respuesta ? response.respuesta : 'Operacion finalizada.', response && response.error ? 'warning' : 'success')
                .then(function () { if (!response || !response.error) window.location.reload(); });
        }).fail(function (request) {
            var response = request.responseJSON || {};
            Swal.fire('Error', response.respuesta || 'No fue posible guardar el corte.', 'error');
        });
    });

    document.querySelectorAll('.js-toggle-corte').forEach(function (button) {
        button.addEventListener('click', function () {
            $.ajax({
                url: '<?= esc(base_url('index.php/Inicio/cambiarVisibilidadCorteProveedor'), 'js') ?>',
                type: 'POST',
                dataType: 'json',
                data: {
                    id_corte: this.getAttribute('data-id-corte'),
                    visible: this.getAttribute('data-visible')
                }
            }).done(function (response) {
                Swal.fire(response && response.error ? 'Atencion' : 'Correcto', response && response.respuesta ? response.respuesta : 'Operacion finalizada.', response && response.error ? 'warning' : 'success')
                    .then(function () { if (!response || !response.error) window.location.reload(); });
            }).fail(function (request) {
                var response = request.responseJSON || {};
                Swal.fire('Error', response.respuesta || 'No fue posible actualizar el corte.', 'error');
            });
        });
    });
})();
</script>
