<?php
$facturasListadoUrl = (string) ($facturasListadoUrl ?? base_url('index.php/Inicio/getFacturasFic'));
$facturasArchivoUrl = (string) ($facturasArchivoUrl ?? base_url('index.php/Inicio/verFacturaProveedorArchivo'));
?>
<style>
    .facturas-fic-page {
        min-height: calc(100vh - 70px);
        padding: 30px 28px 42px;
        background:
            radial-gradient(circle at 82% 8%, rgba(251, 191, 36, .1), transparent 24%),
            linear-gradient(180deg, #0f172a, #111827);
        color: #f8fafc;
    }

    .facturas-fic-card {
        background: linear-gradient(180deg, rgba(24, 31, 48, .97), rgba(17, 24, 39, .98));
        border: 1px solid rgba(148, 163, 184, .16);
        border-radius: 16px;
        box-shadow: 0 18px 48px rgba(2, 6, 23, .22);
    }

    .facturas-fic-table {
        min-width: 1120px;
    }

    .facturas-fic-table-wrap {
        overflow-x: auto;
    }

    .facturas-fic-pill {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .4rem .75rem;
        border-radius: 999px;
        background: rgba(251, 191, 36, .14);
        color: #fde68a;
        font-size: .8rem;
        font-weight: 700;
    }

    .facturas-fic-card .bootstrap-table .fixed-table-toolbar {
        margin-bottom: 1rem;
    }

    .facturas-fic-card .bootstrap-table .fixed-table-toolbar .search input {
        min-height: 42px;
        border-radius: 10px;
        border: 1px solid rgba(148, 163, 184, .34);
        background: rgba(15, 23, 42, .92);
        color: #f8fafc;
    }

    .facturas-fic-card .fixed-table-pagination {
        color: #cbd5e1;
        padding-top: 1rem;
    }

    .facturas-fic-card .fixed-table-pagination .btn,
    .facturas-fic-card .fixed-table-pagination .dropdown-menu,
    .facturas-fic-card .fixed-table-pagination .page-link {
        background: #111827;
        border-color: rgba(148, 163, 184, .28);
        color: #e2e8f0;
    }

    .facturas-fic-card .fixed-table-pagination .page-item.active .page-link {
        background: #f59e0b;
        border-color: #f59e0b;
        color: #111827;
    }
</style>

<div class="container-fluid facturas-fic-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h3 class="mb-1 text-white">Facturas</h3>
            <p class="text-muted mb-0">Consulta los XML y PDF enviados por proveedores desde Formatos.</p>
        </div>
        <a class="btn btn-outline-light" href="<?= base_url('index.php/Inicio') ?>">
            <i class="mdi mdi-arrow-left me-1"></i> Volver a inicio
        </a>
    </div>

    <div class="card facturas-fic-card">
        <div class="card-header border-0 bg-transparent pt-3 pb-0">
            <span class="facturas-fic-pill">Bandeja administrativa</span>
            <h5 class="text-white mt-2 mb-1">Facturas registradas</h5>
            <p class="text-muted mb-0">Listado de facturas guardadas en la tabla facturas.</p>
        </div>
        <div class="card-body facturas-fic-table-wrap">
            <table
                id="tablaFacturasFic"
                class="table table-dark table-hover align-middle facturas-fic-table mb-0"
                data-toggle="table"
                data-url="<?= esc($facturasListadoUrl, 'attr') ?>"
                data-side-pagination="client"
                data-response-handler="facturasFic.responseHandler"
                data-search="true"
                data-search-highlight="true"
                data-pagination="true"
                data-page-size="10"
                data-page-list="[10, 25, 50, 100, All]"
                data-locale="es-MX"
                data-pagination-pre-text="Anterior"
                data-pagination-next-text="Siguiente"
                data-search-align="left">
                <thead>
                    <tr>
                        <th data-field="id_factura" data-sortable="true">ID</th>
                        <th data-field="proveedor" data-sortable="true">Proveedor</th>
                        <th data-field="establecimiento" data-sortable="true">Establecimiento</th>
                        <th data-field="no_proveedor" data-sortable="true">No. proveedor</th>
                        <th data-field="rfc" data-sortable="true">RFC</th>
                        <th data-field="estatus" data-formatter="facturasFic.estatus" data-sortable="true">Estatus</th>
                        <th data-field="fec_reg" data-formatter="facturasFic.fecha" data-sortable="true">Fecha registro</th>
                        <th data-field="acciones" data-formatter="facturasFic.acciones" data-align="center">Archivos</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<script>
window.facturasFic = {
    archivoUrl: '<?= esc($facturasArchivoUrl, 'js') ?>',

    responseHandler: function (response) {
        if (Array.isArray(response)) return response;
        if (response && Array.isArray(response.rows)) return response.rows;
        return [];
    },

    estatus: function (value, row) {
        var idEstatus = Number(row && row.id_estatus ? row.id_estatus : 0);
        if (idEstatus === 1) return '<span class="badge bg-success">Registrada</span>';
        return '<span class="badge bg-secondary">' + facturasFic.escapeHtml(value || 'Sin estatus') + '</span>';
    },

    fecha: function (value) {
        if (!value) return '';
        var texto = String(value);
        var match = texto.match(/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2}):(\d{2})/);
        if (match) return match[3] + '/' + match[2] + '/' + match[1] + ' ' + match[4] + ':' + match[5] + ':' + match[6];
        return facturasFic.escapeHtml(texto);
    },

    acciones: function (value, row) {
        row = row || {};
        var id = Number(row.id_factura || 0);
        if (!id) return '<span class="text-muted">Sin archivos</span>';

        var xmlDisabled = Number(row.tiene_xml || 0) === 1 ? '' : ' disabled';
        var pdfDisabled = Number(row.tiene_pdf || 0) === 1 ? '' : ' disabled';
        return '<div class="btn-group btn-group-sm" role="group">' +
            '<button type="button" class="btn btn-outline-info" title="Ver XML"' + xmlDisabled + ' onclick="facturasFic.abrirArchivo(' + id + ', \'xml\')"><i class="mdi mdi-file-xml-box"></i></button>' +
            '<button type="button" class="btn btn-outline-danger" title="Ver PDF"' + pdfDisabled + ' onclick="facturasFic.abrirArchivo(' + id + ', \'pdf\')"><i class="mdi mdi-file-pdf-box"></i></button>' +
            '<a href="' + base_url('inicio/pdfPagoTerceros(' + id + ')') + '" class="btn btn-outline-info" title="Hoja Azul"' + pdfDisabled + '><i class="mdi mdi-file-pdf-box"></i></a>' +
            '<a href="' + base_url('inicio/liberacionPago(' + id + ')') + '" class="btn btn-outline-primary" title="Liberación de Pago"' + pdfDisabled + '><i class="mdi mdi-file-pdf-box"></i></a>' +
            '</div>';
    },

    abrirArchivo: function (idFactura, tipo) {
        if (!idFactura || ['xml', 'pdf'].indexOf(tipo) === -1) return;
        window.open(this.archivoUrl + '?id_factura=' + encodeURIComponent(idFactura) + '&tipo=' + encodeURIComponent(tipo), '_blank', 'noopener');
    },

    escapeHtml: function (value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
};
</script>
