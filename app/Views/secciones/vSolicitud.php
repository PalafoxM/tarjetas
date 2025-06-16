<!-- Top Bar End -->

<div class="page-wrapper">

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
                                <li class="breadcrumb-item active">Solicitar Curso</li>
                            </ol>
                        </div>
                        <h4 class="page-title">Solicitar Curso</h4>
                        <input type="hidden" autocomplete="off" id="id_categoria" name="id_categoria"
                            value="<?= $id_categoria ?>">
                    </div>
                    <!--end page-title-box-->
                </div>
                <!--end col-->
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <button onclick="cargaCsv()" class="btn btn-gradient-primary px-4 float-right mt-0 mb-3"><i
                                    class="mdi mdi-plus-circle-outline mr-2"></i>Subir y Procesar</button>
                            <h4 class="header-title mt-0">Solicitud</h4>
                            <div class="table-responsive dash-social">
                                <table id="datatableUsuario" class="table">
                                    <thead class="thead-light">
                                        <tr>
                                            <th class="text-center">ID</th>
                                            <th class="text-center">NOMBRE</th>
                                            <th class="text-center">NOMBRE CORTO</th>
                                            <th class="text-center">CATEGORIA</th>
                                            <th class="text-center">ACCIONES</th>
                                        </tr>
                                        <!--end tr-->
                                    </thead>

                                    <tbody>
                                        <?php foreach($eventos as $e): ?>
                                        <tr>
                                            <td><?= $e->id?></td>
                                            <td><?= $e->fullname?></td>
                                            <td><?= $e->shortname?></td>
                                            <td><?= $e->categoryname?></td>
                                            <td class="text-center">
                                                <?php if($e->summary != ''):?>
                                                <a href="javascript:void(0);" onclick="configuracion(<?=$e->id?>)"><i
                                                        class="fas fa-lock-open text-success font-18"></i></a>
                                                <?php endif;?>
                                                <?php if($e->summary == ''):?>
                                                <a ><i
                                                        class="fas fa-lock text-warning font-18"></i></a>
                                                <?php endif;?>
                                              
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
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

    <script>
    function fechas() {
        let fec_inicio = $('#fecha_inicio').val();
        let fec_fin = $('#fecha_fin').val();

        console.log("Fecha inicio:", fec_inicio, "Fecha fin:", fec_fin);

        $('tbody tr').each(function(index) {
            $(this).find(`input[name="timeopen${index}"]`).val(fec_inicio);
            $(this).find(`input[name="timeclose${index}"]`).val(fec_fin);
        });

        const Toast = Swal.mixin({
            toast: true,
            position: 'top-right',
            showConfirmButton: false,
            timer: 2500
        });

        Toast.fire({
            type: 'success',
            title: 'Copia de fechas exitosa',
            icon: 'success'
        });
    }

    function pintarTabla(data) {
        var tbody = $('#quizzTable tbody');
        tbody.empty();

        data.forEach(function(q, i) {
            var row = `
            <tr>
                <td>${q.name}</td>
                <td>
                    <input type="date" autocomplete="off" class="form-control" id="timeopen${i}"
                        name="timeopen${i}" value="${formatDate(q.timeopen)}">
                    <input type="hidden" name="id_curso${i}" id="id_curso${i}" value="${q.id}">
                </td>
                <td>
                    <input type="date" autocomplete="off" class="form-control" id="timeclose${i}"
                        name="timeclose${i}" value="${formatDate(q.timeclose)}">
                </td>
                <td>${formatTime(q.timelimit)}</td>
                <td>
                    <div>
                        <input type="checkbox" id="switch_${i}" data-switch="success"
                            onclick="activar_fecha(${i})" />
                        <label for="switch_${i}" data-on-label="Sí" data-off-label="No"
                            class="mb-0 d-block"></label>
                    </div>
                </td>
            </tr>
        `;
            tbody.append(row);
        });
    }

    function cargaCsv() {
        Swal.fire({
            title: "<strong>Subir CSV</strong>",
            icon: "info",
            html: `<input type='file' id="fileinput" class="form-control" accept=".csv" >`,
            showCloseButton: true,
            showCancelButton: true,
            focusConfirm: false,
            confirmButtonText: "Guardar",
            cancelButtonText: "Cancelar"
        }).then((result) => {
            if (result.isConfirmed) {
                let formData = new FormData();
                let fileinput = $('#fileinput')[0].files[0];
                formData.append('fileinput', $('#fileinput')[0].files[0]);
                formData.append('id_categoria', $('#id_categoria').val());
                if (!fileinput) {
                    Swal.fire("Error", "Es requerido el archivo CSV", "error");
                    return;
                }

                Swal.fire({
                    title: "Atención",
                    text: "Se realizar la carga masiva",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Proceder"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $("#btn_csv").hide();
                        $("#load_csv").show();
                        $.ajax({
                            url: '<?= base_url('/index.php/Agregar/uploadCSV') ?>',
                            type: 'POST',
                            data: formData,
                            contentType: false,
                            processData: false,
                            success: function(response) {
                                if (!response.error) {

                                    Swal.fire("Éxito",
                                        "Los datos se guardaron correctamente.",
                                        "success");
                                    $('#table').bootstrapTable('refresh');
                                    //window.location.reload();
                                } else {
                                    Swal.fire("Error",
                                        "Inconsistencia en el archivo, favor de verificar el ID moodle",
                                        "error");
                                    console.log("Error: " +
                                        response); // Error en el procesamiento
                                }
                                $("#btn_csv").show();
                                $("#load_csv").hide();
                            },
                            error: function(xhr, status, error) {
                                console.log(error);
                                Swal.fire("Error", "Favor de llamar al Administrador",
                                    "error")
                                $("#btn_csv").show();
                                $("#load_csv").hide();
                                //alert("Error en la solicitud: " + error);
                            }
                        });
                    }
                });
            }
        });
    }

    function configuracion(categoryId) {
        var baseUrl = "<?= base_url('index.php/Agregar/Configuracion?id_curso=') ?>";
        window.location.href = baseUrl + categoryId;

    }



    // Función para formatear la fecha en formato YYYY-MM-DD
    function formatDate(timestamp) {
        var date = new Date(timestamp * 1000); // Convertir a milisegundos
        return date.toISOString().split('T')[0];
    }

    // Función para formatear el tiempo en formato HH:MM:SS
    function formatTime(seconds) {
        var hours = Math.floor(seconds / 3600);
        var minutes = Math.floor((seconds % 3600) / 60);
        var secs = seconds % 60;
        return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }
    </script>


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