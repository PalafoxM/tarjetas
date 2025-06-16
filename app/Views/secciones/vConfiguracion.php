<!-- Page Content-->
<div class="page-content-tab">

    <div class="container-fluid">
        <!-- Page-Title -->
        <div class="row">
            <div class="col-sm-12">
                <div class="page-title-box">
                    <div class="float-right">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Metrica</a></li>
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Analytics</a></li>
                            <li class="breadcrumb-item active">Configuración</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Configuración del Curso</h4>

                </div>
                <!--end page-title-box-->
            </div>
            <!--end col-->
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <input type="hidden" id="id_curso" checked data-switch="bool" name="id_curso"
                            value="<?php echo (isset($id_curso))?$id_curso:''?>" />
                        <h4 class="header-title mt-0">Configuración</h4>
                        <?php $fec_ini = (isset($fec_inicio))?date("Y-m-d", strtotime($fec_inicio)):''; ?>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="fec_inicio">Fecha Inicio</label>
                                    <input type="date" class="form-control" id="fec_inicio" name="fec_inicio"
                                        value="<?php echo $fec_ini ?>">
                                </div>
                            </div>
                            <?php $fec_f = (isset($fec_fin))?date("Y-m-d", strtotime($fec_fin)):''; ?>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="fec_fin">Fecha Fin</label>
                                    <input type="date" class="form-control" id="fec_fin" name="fec_fin"
                                        value="<?php echo $fec_f; ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="numero_id_curso">ID Curso</label>
                                    <input type="text" class="form-control" id="numero_id_curso" name="numero_id_curso"
                                        value="<?php echo (isset($eventos->numero_id_curso))?$eventos->numero_id_curso:''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <button type="button" onclick="fechas()" class="btn btn-primary">Copiar
                                        fechas</button>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive dash-social">
                            <?php if(isset($quizz) && !empty($quizz)): ?>
                            <table class="table table-centered mb-0">
                                <thead>
                                    <tr>
                                        <th>NOMBRE</th>
                                        <th>TIEMPO INICIO</th>
                                        <th>TIEMPO FIN</th>
                                        <th>TIEMPO LIMITE</th>
                                        <th>EDITAR</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i = 0; ?>
                                    <?php foreach ($quizz as $q): ?>

                                    <tr>
                                        <td><?= htmlspecialchars($q->name) ?></td>
                                        <td>
                                            <input type="date" autocomplete="off" class="form-control"
                                                id="timeopen<?= $i ?>" name="timeopen<?= $i ?>"
                                                value="<?= date("Y-m-d", $q->timeopen) ?>" readonly>
                                            <input type="hidden" name="id_curso<?= $i ?>" id="id_curso<?= $i ?>"
                                                value="<?= $q->id ?>">
                                        </td>
                                        <td>
                                            <input type="date" autocomplete="off" class="form-control"
                                                id="timeclose<?= $i ?>" name="timeclose<?= $i ?>"
                                                value="<?= date("Y-m-d", $q->timeclose) ?>" readonly>
                                            <!-- Solo `YYYY-MM-DD` -->
                                        </td>
                                        <td><?= gmdate("H:i:s", $q->timelimit) ?></td>
                                        <!-- Convierte `timelimit` en horas:minutos:segundos -->
                                        <td>
                                            <!-- Switch -->
                                            <div>
                                                <div class="custom-control custom-switch">
                                                    <input onclick="activar_fecha(<?=$i?>)" type="checkbox"
                                                        class="custom-control-input" id="switch_<?= $i ?>">
                                                    <label class="custom-control-label" for="switch_<?= $i ?>"></label>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php $i++; ?>
                                    <?php endforeach; ?>
                                </tbody>


                            </table>
                            <?php endif ?>
                        </div>
                        <div class="row">
                            <div class="d-flex justify-content-end">
                                <button type="submit" id="btn_guardar_conf" class="btn btn-primary">Guardar
                                    Configuración</button>

                                <button id="btn_guardar_load" style="display:none" class="btn btn-gradient-primary"
                                    type="button" disabled>
                                    <span class="spinner-border spinner-border-sm" role="status"
                                        aria-hidden="true"></span>
                                    Guardando...
                                </button>
                            </div>
                        </div>
                    </div>
                    <!--end card-body-->
                </div>
                <!--end card-->
            </div>
            <!--end col-->
        </div>
        <!--end row-->

    </div><!-- container -->


</div>
<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Título del modal</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="fecha_inicio">Fecha Inicio</label>
                            <input type="date" class="form-control" id="fecha_inicio" required="">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="fecha_fin">Fecha Fin</label>
                            <input type="date" class="form-control" id="fecha_fin" required="">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="CompanyName">ID Curso</label>
                            <input type="text" class="form-control" id="ContactNo" required="">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <button type="button" onclick="fechas()" class="btn btn-primary">Copiar fechas</button>
                        </div>
                    </div>
                </div>

                <table class="table table-centered mb-0" id="quizzTable">
                    <thead>
                        <tr>
                            <th>NOMBRE</th>
                            <th>TIEMPO INICIO</th>
                            <th>TIEMPO FIN</th>
                            <th>TIEMPO LIMITE</th>
                            <th>EDITAR</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Las filas se llenarán dinámicamente aquí -->
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary">Guardar cambios</button>
            </div>
        </div>
    </div>
</div>
<!-- end page-wrapper -->




<link href="<?php echo base_url(); ?>plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet"
    type="text/css" />

<!-- App css -->
<link href="<?php echo base_url(); ?>assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
<link href="<?php echo base_url(); ?>assets/css/jquery-ui.min.css" rel="stylesheet">
<link href="<?php echo base_url(); ?>assets/css/metisMenu.min.css" rel="stylesheet" type="text/css" />
<link href="<?php echo base_url(); ?>assets/css/app.min.css" rel="stylesheet" type="text/css" />



<!-- jQuery  -->
<script src="<?php echo base_url(); ?>assets/js/jquery.min.js"></script>
<script src="<?php echo base_url(); ?>assets/js/jquery-ui.min.js"></script>
<script src="<?php echo base_url(); ?>assets/js/bootstrap.bundle.min.js"></script>

<script src="<?php echo base_url(); ?>assets/js/jquery.slimscroll.min.js"></script>
<script src="<?php echo base_url(); ?>plugins/apexcharts/apexcharts.min.js"></script>

<!-- Required datatable js -->
<script src="<?php echo base_url(); ?>plugins/datatables/jquery.dataTables.min.js"></script>
<script src="<?php echo base_url(); ?>plugins/datatables/dataTables.bootstrap4.min.js"></script>

<script src="<?php echo base_url(); ?>assets/pages/jquery.analytics_customers.init.js"></script>
<script>
$(document).ready(function() {

    st.agregar.formConfigurarCurso();
});

function fechas() {
    let fec_inicio = $('#fec_inicio').val();
    let fec_fin = $('#fec_fin').val();

    console.log("Fecha inicio:", fec_inicio, "Fecha fin:", fec_fin);

    // Itera sobre cada fila en el cuerpo de la tabla y asigna los valores
    $('tbody tr').each(function(index) {
        // Encuentra los inputs que comienzan con "timeopen" y "timeclose" según el índice
        $(this).find(`input[name="timeopen${index}"]`).val(fec_inicio);
        $(this).find(`input[name="timeclose${index}"]`).val(fec_fin);

    });

    const Toast = Swal.mixin({
        toast: true,
        position: 'top-right',
        showConfirmButton: false,
        timer: 2500
    })

    Toast.fire({
        type: 'success',
        title: 'Copia de fechas exitosa',
        icon: 'success'
    });

}

function activar_fecha(i) {
    document.getElementById(`timeopen${i}`).readOnly = false;
    document.getElementById(`timeclose${i}`).readOnly = false;
}
</script>