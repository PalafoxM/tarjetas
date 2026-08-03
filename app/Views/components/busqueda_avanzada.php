<style>
    .busqueda-avanzada-wrapper .card {
        border: 1px solid rgba(148, 163, 184, .15);
    }

    .busqueda-avanzada-wrapper .input-group-text {
        border-right: 0;
    }

    .busqueda-avanzada-wrapper .input-group-text i {
        font-size: 1.1rem;
    }

    .busqueda-avanzada-wrapper .input-group .form-control {
        border-left: 0;
    }

    .busqueda-avanzada-wrapper .input-group .form-control:focus {
        border-color: var(--bs-info);
        box-shadow: none;
    }

    .busqueda-avanzada-wrapper .input-group .form-control:focus + .btn {
        border-color: var(--bs-info);
    }

    #btn_cerrar_busqueda_avanzada {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }
    
    .busqueda-avanzada-wrapper .d-flex.justify-content-end {
        justify-content: flex-end !important;
    }
    
    .busqueda-avanzada-wrapper .collapse .card-body .row {
        justify-content: flex-end !important;
    }
    
    .busqueda-avanzada-wrapper .collapse .card-body .col-12 {
        max-width: 500px;
        margin-left: auto;
    }
    
    .busqueda-avanzada-wrapper .collapse .card-body {
        padding-right: 0;
    }
</style>

<div class="busqueda-avanzada-wrapper">
    <div class="d-flex justify-content-end mb-3">
        <button class="btn btn-outline-info" type="button" data-bs-toggle="collapse" data-bs-target="#busquedaAvanzadaCollapse" aria-expanded="false" aria-controls="busquedaAvanzadaCollapse">
            <i class="mdi mdi-magnify-plus me-1"></i> Búsqueda avanzada
        </button>
    </div>
    
    <div class="collapse mb-3" id="busquedaAvanzadaCollapse">
        <div class="card card-body bg-secondary bg-opacity-10">
            <div class="row">
                <div class="col-12 col-md-8 col-lg-6">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-secondary">
                            <i class="mdi mdi-magnify text-muted"></i>
                        </span>
                        <input type="text" class="form-control" id="busqueda_avanzada_input" placeholder="Buscar por usuario o nombre completo...">
                        <button type="button" class="btn btn-outline-secondary" id="btn_cerrar_busqueda_avanzada" style="display:none;">
                            <i class="mdi mdi-close"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>