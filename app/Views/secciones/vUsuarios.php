<!-- Top Bar End -->
<?php  $session = \Config\Services::session();    ?>
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
                                <li class="breadcrumb-item active">Usuarios</li>
                            </ol>
                        </div>
                        <h4 class="page-title">Usuario</h4>

                    </div>
                    <!--end page-title-box-->
                </div>
                <!--end col-->
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <a href="<?php echo base_url(); ?>index.php/Inicio/altaUsuario"
                                class="btn btn-gradient-danger px-4 float-right mt-0 mb-3"><i
                                    class="mdi mdi-account-plus-outline mr-2"></i>Agregar</a>
                                    <?php if($session->id_perfil == 6):?>
                            <button onclick="ini.inicio.cargaCsv()"
                                class="btn btn-gradient-primary px-4 float-right mt-0 mb-3"><i
                                    class="mdi mdi-plus-circle-outline mr-2"></i>Subir y Procesar</button>
                                    <?php endif; ?>
                            <h4 class="header-title mt-0">Lista de Usuarios</h4>
                            <div class="table-responsive dash-social">
                                <table id="usuariosTable" class="table">
                                    <thead class="thead-light">
                                        <tr>
                                            <th class="text-center">NOMBRE</th>
                                            <th class="text-center">CURP</th>
                                            <th class="text-center">PERFIL</th>
                                            <th class="text-center">SEXO</th>
                                            <th class="text-center">ACCIONES</th>
                                        </tr>
                                        <!--end tr-->
                                    </thead>

                                    <tbody>
                                        <?php foreach($usuario as $u): ?>
                                        <tr>
                                            <td class="text-center"><?= $u->nombre_completo?></td>
                                            <td class="text-center"><?= $u->curp?></td>
                                            <td class="text-center"><?= $u->dsc_perfil?></td>
                                            <td class="text-center"><?= $u->dsc_sexo?></td>
                                            <td class="text-center">
                                                <a href="javascript:void(0);" data-toggle="modal"
                                                    data-animation="bounce" data-target=".bs-example"
                                                    onclick="ini.inicio.getUsuario(<?=$u->id_usuario?>)"><i
                                                        class="mdi mdi-pencil text-success font-18"></i></a>
                                                <?php if($session->id_usuario != $u->id_usuario):?>
                                                <a href="javascript:void(0);"
                                                    onclick="ini.inicio.deleteUsuario(<?=$u->id_usuario?>)"><i
                                                        class="mdi mdi-trash-can text-danger font-18"></i></a>
                                                <?php endif; ?>

                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->

    <div id="agregarUsuario" class="modal fade bs-example" tabindex="-1" role="dialog"
        aria-labelledby="myLargeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Usuario</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="detalleCurso" style="max-height: 70vh; overflow-y: auto;">
                    <form id="formAgregarUsuarioTsi" name="formAgregarUsuarioTsi">
                        <div class="row">
                            <input type="hidden" value="0" name="id_usuario" id="id_usuario">
                            <input type="hidden" value="0" name="editar" id="editar">
                            <!-- seccion izquierdo incio -->
                            <div class="col-md-12 ">
                                <div class="card">
                                    <!--init card -->
                                    <div class="card-body">

                                        <div class="row">
                                            <div class="col-md-3">
                                                <label for="curp" class="form-label">CURP</label>
                                                <div class="input-group flex-nowrap">
                                                    <span class="input-group-text" id="basic-addon1"><i id="icono"
                                                            class="dripicons-search"></i>
                                                        <div style="display:none;" id="spinner" class="spinner-border"
                                                            role="status">
                                                            <span class="visually-hidden">Loading...</span>
                                                        </div>
                                                    </span>
                                                    <input type="text" class="form-control" placeholder="CURP"
                                                        aria-label="Username" id="curp" name="curp"
                                                        aria-describedby="basic-addon1" autocomplete="off">
                                                </div>

                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3 position-relative" id="">
                                                    <label for="nombre"
                                                        class="form-label campoObligatorio">NOMBRE</label>
                                                    <input type="text" autocomplete="off" class="form-control"
                                                        id="nombre" name="nombre" placeholder="NOMBRE">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3 position-relative" id="">
                                                    <label for="primer_apellido"
                                                        class="form-label campoObligatorio">PRIMER
                                                        APELLIDO</label>
                                                    <input type="text" autocomplete="off" class="form-control"
                                                        id="primer_apellido" name="primer_apellido"
                                                        placeholder="PRIMER APELLIDO">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3 position-relative" id="">
                                                    <label for="segundo_apellido"
                                                        class="form-label campoObligatorio">SEGUNDO
                                                        APELLIDO</label>
                                                    <input type="text" autocomplete="off" class="form-control"
                                                        id="segundo_apellido" name="segundo_apellido"
                                                        placeholder="SEGUNDO APELLIDO">
                                                </div>
                                            </div>


                                        </div>
                                        <div class="row">

                                            <div class="col-md-3">
                                                <div class="mb-3 position-relative" id="">
                                                    <label for="fec_nac" class="form-label campoObligatorio">FECHA
                                                        NACIMIENTO</label>
                                                    <input type="date" autocomplete="off" class="form-control"
                                                        id="fec_nac" name="fec_nac" placeholder="FEC. NACIMIENTO">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3 position-relative" id="">
                                                    <label for="rfc" class="form-label campoObligatorio">RFC</label>
                                                    <input type="text" autocomplete="off" class="form-control" id="rfc"
                                                        name="rfc" placeholder="NOMBRE COMPLETO">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3 position-relative" id="">
                                                    <label for="correo"
                                                        class="form-label campoObligatorio">CORREO</label>
                                                    <input type="text" autocomplete="off" class="form-control"
                                                        id="correo" name="correo" placeholder="CORREO ELECTRONICO">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3 position-relative" id="">
                                                    <label for="id_sexo" class="form-label">SEXO</label>
                                                    <select class="form-control" id="id_sexo" name="id_sexo"
                                                        data-placeholder="seleccione" style="z-index:100;">
                                                        <option value="0">seleccione</option>
                                                        <option value="1">HOMBRE</option>
                                                        <option value="2">MUJER</option>
                                                    </select>
                                                </div>
                                            </div>

                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-6 position-relative" id="">
                                                    <label for="id_nivel" class="form-label">NIVEL TABULAR</label>
                                                    <select class="form-control select2" data-toggle="select2"
                                                        id="id_nivel" name="id_nivel" data-placeholder="Seleccione"
                                                        style="z-index:100;">
                                                        <option value="0">Seleccione</option>
                                                        <?php foreach ($cat_nivel as $g): ?>
                                                        <option value="<?php echo $g->id_nivel; ?>">
                                                            <?php echo $g->dsc_nivel.' '.$g->denominacion_tabular; ?>
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="mb-6 position-relative" id="">
                                                    <label for="c" class="form-label">DEPENDENCIA</label>
                                                    <select class="form-control select2" data-toggle="select2"
                                                        id="id_dependencia" name="id_dependencia"
                                                        data-placeholder="Seleccione" style="z-index:100;" readonly>
                                                        <option value="0">Seleccione</option>
                                                        <?php foreach ($cat_dependencia as $dep): ?>
                                                        <option value="<?php echo $dep->id_dependencia; ?>"
                                                            <?php if ($session->id_dependencia == $dep->id_dependencia) echo 'selected'; ?>>
                                                            <?php echo $dep->dsc_dependencia ?>
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="mb-3 position-relative" id="">
                                                    <label for="denominacion_funcional"
                                                        class="form-label campoObligatorio">FUNCION</label>
                                                    <input type="text" autocomplete="off" class="form-control"
                                                        id="denominacion_funcional" name="denominacion_funcional"
                                                        placeholder="DENOMINACION FUNCIONAL"
                                                        oninput="this.value = this.value.toUpperCase();">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3 position-relative" id="">
                                                    <label for="area" class="form-label campoObligatorio">AREA
                                                        PERSONAL</label>
                                                    <input type="text" autocomplete="off" class="form-control" id="area"
                                                        name="area" placeholder="GRUPO"
                                                        oninput="this.value = this.value.toUpperCase();">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-6 position-relative" id="">
                                                    <label for="jefe_inmediato"
                                                        class="form-label campoObligatorio">FEJE/A
                                                        INMEDIATO</label>
                                                    <input type="text" autocomplete="off" class="form-control"
                                                        id="jefe_inmediato" name="jefe_inmediato"
                                                        placeholder="SUPERVISOR"
                                                        oninput="this.value = this.value.toUpperCase();">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="mb-3 position-relative" id="">
                                                    <label for="id_perfil" class="form-label">PERFIL</label>
                                                    <select class="form-control select2" data-toggle="select2"
                                                        id="id_perfil" name="id_perfil" data-placeholder="Seleccione"
                                                        style="z-index:100;">
                                                        <option value="0">Seleccione</option>
                                                        <?php foreach ($cat_perfil as $p): ?>
                                                        <option value="<?php echo htmlspecialchars($p->id_perfil); ?>">
                                                            <?php echo htmlspecialchars($p->dsc_perfil); ?>
                                                        </option>
                                                        <?php endforeach; ?>



                                                    </select>

                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3 position-relative" id="">
                                                    <label for="usuario"
                                                        class="form-label campoObligatorio">USUARIO</label>
                                                    <input type="text" autocomplete="off" class="form-control"
                                                        id="usuario" name="usuario" placeholder="USUARIO"
                                                        oninput="this.value = this.value.toUpperCase();">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3 position-relative" id="">
                                                    <label for="contrasenia"
                                                        class="form-label campoObligatorio">CONTRASEÑA</label>
                                                    <input type="password" autocomplete="off" class="form-control"
                                                        id="contrasenia" name="contrasenia" placeholder="CONTRASEÑA"
                                                        oninput="this.value = this.value.toUpperCase();">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3 position-relative" id="">
                                                    <label for="confirmar_contrasenia"
                                                        class="form-label campoObligatorio">CONFIRMAR
                                                        CONTRASEÑA</label>
                                                    <input type="password" autocomplete="off" class="form-control"
                                                        id="confirmar_contrasenia" name="confirmar_contrasenia"
                                                        placeholder="CONFIRMAR"
                                                        oninput="this.value = this.value.toUpperCase();">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!--end card -->
                            </div>
                            <!-- seccion izquierdo fin-->
                            <!-- seccion derecha incio -->
                        </div>


                        <div class="row mb-5" id="btn_save">
                            <div class="col-md-12 text-center ">
                                <button class="btn btn-info" type="submit"><i class="mdi mdi-content-save"></i> Guardar
                                </button>
                                <button class="btn btn-warning" type="button" data-dismiss="modal"><i
                                        class="mdi mdi-content-save-off-outline" id="cancelarTurno"></i> Cancelar
                                </button>
                            </div>
                        </div>
                        <div class="row mb-5" id="btn_load" style="display:none;">
                            <div class="col-md-12 text-center ">
                                <button class="btn btn-info" type="button" disabled>
                                    <span class="spinner-grow spinner-grow-sm me-1" role="status"
                                        aria-hidden="true"></span>
                                    Guardando...
                                </button>
                            </div>
                        </div>

                    </form>



                </div>
            </div>
        </div>
    </div>

    <!-- modal -->
    <div id="standard-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="standard-modalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="standard-modalLabel">Subir Archivo</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                </div>
                <div class="modal-body">
                    <form id="uploadCSVParticipantes" name="uploadCSVParticipantes" enctype="multipart/form-data">

                        <div class="form-group">
                            <label for="fileParticipantes">Seleccionar Archivo CSV:</label>
                            <input type="file" name="fileParticipantes" id="fileParticipantes" accept=".csv" required
                                class="form-control">
                        </div>


                    </form>
                </div>
                <div class="modal-footer" id="btn_csv">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" onclick="ini.inicio.subirCsv()">Procesar
                        csv</button>
                </div>
                <div class="modal-footer" id="load_csv" style="display:none">
                    <button class="btn btn-primary mt-3" >
                        <div class="spinner-grow" role="status">
                            <span class="visually-hidden">.</span>
                        </div>
                    </button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
    <!-- modal -->

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
    ini.inicio.agregarUsuario();
    $('#usuariosTable').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json' // Ruta al archivo de localización
        },
        destroy: true,
        searching: true,
    });

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