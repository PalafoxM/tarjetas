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

    .busqueda-avanzada-wrapper .input-group .form-control:focus {
        border-color: var(--bs-info);
    }
    
    .busqueda-avanzada-wrapper .d-flex.justify-content-end {
        justify-content: flex-end !important;
    }
    
    .busqueda-avanzada-wrapper .collapse .card-body .row {
        justify-content: flex-end !important;
    }
    
    .busqueda-avanzada-wrapper .collapse .card-body .col-12 {
        max-width: 400px;
        margin-left: auto;
        margin-right: 0;
        padding: 0;
    }
    
    .busqueda-avanzada-wrapper .collapse .card-body {
        padding: 1rem 1.5rem;
    }

    .busqueda-avanzada-wrapper .input-group {
        position: relative;
    }

    .busqueda-avanzada-wrapper .input-clear-btn {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #6c757d;
        cursor: pointer;
        padding: 0 5px;
        font-size: 1.1rem;
        z-index: 10;
        display: none;
        transition: color 0.2s ease;
        line-height: 1;
    }

    .busqueda-avanzada-wrapper .input-clear-btn:hover {
        color: #e9ecef;
    }

    .busqueda-avanzada-wrapper .input-clear-btn.visible {
        display: block;
    }

    .busqueda-avanzada-wrapper .input-clear-btn i {
        font-size: 1.1rem;
        pointer-events: none;
    }

    .busqueda-avanzada-wrapper .form-control.has-clear-btn {
        padding-right: 35px;
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
            <div class="row justify-content-end">
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="input-group position-relative">
                        <input type="text" class="form-control" id="busqueda_avanzada_input" placeholder="Buscar por usuario o nombre completo...">
                        <button type="button" class="input-clear-btn" id="busqueda_clear_btn" aria-label="Limpiar búsqueda">
                            <i class="mdi mdi-close"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function($) {
    'use strict';
    
    window.BusquedaAvanzada = {
        datosOriginales: [],
        busquedaActiva: false,
        timeoutId: null,
        terminoActual: '',
        tabla: null,
        currentXhr: null,
        
        init: function(tablaSelector = '#cajerosTable') {
            this.tabla = $(tablaSelector);
        },
        
        realizarBusqueda: function(termino) {
            const table = this.tabla || $('#cajerosTable');
            const texto = termino.trim();
            
            if (this.currentXhr) {
                this.currentXhr.abort();
                this.currentXhr = null;
            }

            if (!texto) {
                this.restaurarTablaOriginal();
                return;
            }
            
            if (texto.length < 2) return;
            
            this.busquedaActiva = true;
            this.terminoActual = texto;
            
            this.currentXhr = $.ajax({
                url: base_url + 'index.php/Usuario/buscarUsuariosAvanzado',
                type: 'GET',
                dataType: 'json',
                data: { termino: texto },
                timeout: 10000,
                global: false,
                success: (response) => {
                    let rows = [];
                    if (Array.isArray(response)) rows = response;
                    else if (response && Array.isArray(response.data)) rows = response.data;
                    else if (response && Array.isArray(response.rows)) rows = response.rows;

                    if (window.cajeros && typeof window.cajeros.establecerRegistrosBaseDiaLlegada === 'function') {
                        window.cajeros.establecerRegistrosBaseDiaLlegada(rows);
                        rows = window.cajeros.aplicarFiltroDiaLlegada(rows);
                        window.cajeros.actualizarEstadoFiltroDiaLlegada();
                    }
                    
                    table.bootstrapTable('load', rows);
                },
                error: (xhr, status, error) => {
                    if (status !== 'abort') {
                        console.error('Error en búsqueda avanzada:', status, error);
                        this.restaurarTablaOriginal();
                    }
                }
            });
        },
        
        restaurarTablaOriginal: function() {
            const table = this.tabla || $('#cajerosTable');
            
            this.busquedaActiva = false;
            this.terminoActual = '';
            
            if (this.datosOriginales && Array.isArray(this.datosOriginales) && this.datosOriginales.length > 0) {
                let rows = this.datosOriginales;
                if (window.cajeros && typeof window.cajeros.establecerRegistrosBaseDiaLlegada === 'function') {
                    window.cajeros.establecerRegistrosBaseDiaLlegada(rows);
                    rows = window.cajeros.aplicarFiltroDiaLlegada(rows);
                    window.cajeros.actualizarEstadoFiltroDiaLlegada();
                }
                table.bootstrapTable('load', rows);
            } else {
                table.bootstrapTable('refresh', { silent: true });
            }
        },
        
        limpiarBusqueda: function() {
            if (this.currentXhr) {
                this.currentXhr.abort();
                this.currentXhr = null;
            }

            $('#busqueda_avanzada_input').val('').removeClass('has-clear-btn');
            $('#busqueda_clear_btn').removeClass('visible');
            
            if (this.timeoutId) {
                clearTimeout(this.timeoutId);
                this.timeoutId = null;
            }
            
            this.busquedaActiva = false;
            this.terminoActual = '';
            
            this.restaurarTablaOriginal();
            
            const collapseEl = document.getElementById('busquedaAvanzadaCollapse');
            if (collapseEl && collapseEl.classList.contains('show')) {
                const bsCollapse = bootstrap.Collapse.getInstance(collapseEl);
                if (bsCollapse) bsCollapse.hide();
            }
        }
    };

    $(function () {
        window.BusquedaAvanzada.init();
        
        const $inputBusqueda = $('#busqueda_avanzada_input');
        const $clearBtn = $('#busqueda_clear_btn');
        
        function toggleClearButton() {
            const hasText = $inputBusqueda.val().trim().length > 0;
            $clearBtn.toggleClass('visible', hasText);
            $inputBusqueda.toggleClass('has-clear-btn', hasText);
        }
        
        $inputBusqueda.on('input', function() {
            const texto = $(this).val();
            
            toggleClearButton();
            clearTimeout(window.BusquedaAvanzada.timeoutId);
            
            if (texto.trim().length === 0) {
                if (window.BusquedaAvanzada.currentXhr) window.BusquedaAvanzada.currentXhr.abort();
                window.BusquedaAvanzada.restaurarTablaOriginal();
                return;
            }
            
            if (texto.trim().length < 2) return;
            
            window.BusquedaAvanzada.timeoutId = setTimeout(function() {
                window.BusquedaAvanzada.realizarBusqueda(texto);
            }, 300);
        });
        
        $clearBtn.on('click', function() {
            $inputBusqueda.val('');
            toggleClearButton();
            
            clearTimeout(window.BusquedaAvanzada.timeoutId);
            if (window.BusquedaAvanzada.currentXhr) {
                window.BusquedaAvanzada.currentXhr.abort();
                window.BusquedaAvanzada.currentXhr = null;
            }
            
            window.BusquedaAvanzada.restaurarTablaOriginal();
            $inputBusqueda.focus();
        });

        $('[data-bs-toggle="collapse"][data-bs-target="#busquedaAvanzadaCollapse"]').on('click', function() {
            setTimeout(function() {
                $inputBusqueda.focus();
                toggleClearButton();
            }, 400);
        });
        
        $('#busquedaAvanzadaCollapse').on('hidden.bs.collapse', function() {
            window.BusquedaAvanzada.limpiarBusqueda();
            $clearBtn.removeClass('visible');
            $inputBusqueda.removeClass('has-clear-btn');
        });
        
        toggleClearButton();
    });
})(jQuery);
</script>