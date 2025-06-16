<?php
$meses = [
    1 => 'ENERO',
    2 => 'FEBRERO',
    3 => 'MARZO',
    4 => 'ABRIL',
    5 => 'MAYO',
    6 => 'JUNIO',
    7 => 'JULIO',
    8 => 'AGOSTO',
    9 => 'SEPTIEMBRE',
    10 => 'OCTUBRE',
    11 => 'NOVIEMBRE',
    12 => 'DICIEMBRE',
];
?>


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
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Pages</a></li>
                                <li class="breadcrumb-item active">Profile</li>
                            </ol>
                        </div>
                        <h4 class="page-title">Crear Cursos</h4>
                    </div>
                    <!--end page-title-box-->
                </div>
                <!--end col-->
            </div>
            <!-- end page title end breadcrumb -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <!--end card-body-->
                        <div class="card-body">
                            <ul class="nav nav-pills mb-0" id="pills-tab" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="general_detail_tab" data-toggle="pill"
                                        href="#general_detail">Categorias</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="activity_detail_tab" data-toggle="pill"
                                        href="#activity_detail">Periodo</a>
                                </li>
                                <li class="nav-item">
                                    <a onclick="ini.inicio.getSelectPeriodos()" class="nav-link" id="portfolio_detail_tab" data-toggle="pill"
                                        href="#portfolio_detail">Cursos</a>
                                </li>
                                <!--  <li class="nav-item">
                                    <a class="nav-link" id="settings_detail_tab" data-toggle="pill"
                                        href="#settings_detail">Settings</a>
                                </li> -->
                            </ul>
                        </div>
                        <!--end card-body-->
                    </div>
                    <!--end card-->
                </div>
                <!--end col-->
            </div>
            <!--end row-->
            <div class="row">
                <div class="col-12">
                    <div class="tab-content detail-list" id="pills-tabContent">
                        <div class="tab-pane fade show active" id="general_detail">
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">

                                        <div class="card-body">
                                            <span>CATEGORIAS</span>
                                            <button onclick="ini.inicio.agregar()"
                                                class="btn btn-gradient-primary px-4 float-right mt-0 mb-3"><i
                                                    class="mdi mdi-plus-circle-outline mr-2"></i>Agregar</button>
                                            <table id="datatableCategorias" class="table" data-toggle="table">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th class="text-center">NOMBRE</th>
                                                        <th class="text-center">ID MOODLE</th>
                                                        <th class="text-center">ESTATUS</th>
                                                        <th class="text-center">ACCIONES</th>
                                                    </tr>
                                                    <!--end tr-->
                                                </thead>

                                                <tbody>
                                                    <?php foreach($categoria_sac as $e): ?>
                                                    <tr>
                                                        <td class="text-center"><?= $e->dsc_categoria_sac?></td>
                                                        <td class="text-center"><?= $e->id_moodle?></td>
                                                        <td class="text-center">
                                                            <?php if($e->activo == 1):?>
                                                            <i class="mdi mdi-eye text-success font-18"></i>
                                                            <?php endif; ?>
                                                            <?php if($e->activo == 0):?>
                                                            <i class="mdi mdi-eye-off text-danger font-18"></i>
                                                            <?php endif; ?>
                                                        </td>

                                                        <td class="text-center">

                                                            <button title="editar"
                                                                onclick="ini.inicio.editarCat(<?= $e->id_categoria_sac?>)"
                                                                class="btn btn-gradient-warning px-4"><i
                                                                    class="dripicons-pencil font-21"></i>
                                                            </button>
                                                            <?php if($e->activo == 1):?>
                                                            <button title="Desactivar"
                                                                onclick="ini.inicio.activarCat(<?= $e->id_categoria_sac?>, 3)"
                                                                class="btn btn-gradient-success px-4 "><i
                                                                    class="mdi mdi-eye font-21"></i>
                                                            </button>
                                                            <?php endif; ?>
                                                            <?php if($e->activo == 0):?>
                                                            <button title="Activar"
                                                                onclick="ini.inicio.activarCat(<?= $e->id_categoria_sac?>, 4)"
                                                                class="btn btn-gradient-danger px-4 "><i
                                                                    class="mdi mdi-eye-off font-21"></i>
                                                            </button>
                                                            <?php endif; ?>
                                                            <button title="eliminar"
                                                                onclick="ini.inicio.eliminarCat(<?= $e->id_categoria_sac?>)"
                                                                class="btn btn-gradient-danger px-4 "><i
                                                                    class="dripicons-trash font-21"></i>
                                                            </button>



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
                        <!--end general detail-->

                        <div class="tab-pane fade" id="activity_detail">
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <span>PERIODO</span>
                                            <button onclick="ini.inicio.agregarPeriodo()"
                                                class="btn btn-gradient-primary px-4 float-right mt-0 mb-3"><i
                                                    class="mdi mdi-plus-circle-outline mr-2"></i>Agregar</button>
                                            <table id="datatablePeriodos" class="table" data-toggle="table">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th class="text-center">PERIODO</th>
                                                        <th class="text-center">MES</th>
                                                        <th class="text-center">DIA INICIO</th>
                                                        <th class="text-center">DIA FIN</th>
                                                        <th class="text-center">ESTATUS</th>
                                                        <th class="text-center">ACCIONES</th>
                                                    </tr>
                                                    <!--end tr-->
                                                </thead>

                                                <tbody>
                                                    <?php foreach($periodo_sac as $e): ?>
                                                    <tr>
                                                        <td class="text-center"><?= 'P'.$e->id_periodo_sac?></td>
                                                        <td class="text-center"><?= $meses[$e->mes] ?? 'MES INVÁLIDO' ?>
                                                        </td>
                                                        <td class="text-center"><?= $e->dia_inicio?></td>
                                                        <td class="text-center"><?= $e->dia_fin?></td>
                                                        <td class="text-center">
                                                            <?php if($e->activo == 1):?>
                                                            <i class="mdi mdi-eye text-success font-18"></i>
                                                            <?php endif; ?>
                                                            <?php if($e->activo == 0):?>
                                                            <i class="mdi mdi-eye-off text-danger font-18"></i>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-center">
                                                            <button title="editar"
                                                                onclick="ini.inicio.editarPeriodo(<?= $e->id_periodo_sac?>)"
                                                                class="btn btn-gradient-warning px-4"><i
                                                                    class="dripicons-pencil font-21"></i>
                                                            </button>
                                                            <?php if($e->activo == 1):?>
                                                            <button title="Desactivar"
                                                                onclick="ini.inicio.activarPeriodo(<?= $e->id_periodo_sac?>, 1)"
                                                                class="btn btn-gradient-success px-4 "><i
                                                                    class="mdi mdi-eye-off font-21"></i>
                                                            </button>
                                                            <?php endif; ?>
                                                            <?php if($e->activo == 0):?>
                                                            <button title="Activar"
                                                                onclick="ini.inicio.activarPeriodo(<?= $e->id_periodo_sac?>, 2)"
                                                                class="btn btn-gradient-danger px-4 "><i
                                                                    class="mdi mdi-eye font-21"></i>
                                                            </button>
                                                            <?php endif; ?>
                                                            <button title="eliminar"
                                                                onclick="ini.inicio.eliminarPeriodo(<?= $e->id_periodo_sac?>)"
                                                                class="btn btn-gradient-danger px-4 "><i
                                                                    class="dripicons-trash font-21"></i>
                                                            </button>

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
                        <!--end education detail-->

                        <div class="tab-pane fade" id="portfolio_detail">
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <span>CURSO</span>
                                            <button data-toggle="modal" data-animation="bounce"
                                                data-target=".bs-example-modal-lg2" onclick="ini.inicio.borrarDatos()"
                                                class="btn btn-gradient-primary px-4 float-right mt-0 mb-3">
                                                <i class="mdi mdi-plus-circle-outline mr-2"></i>Agregar Curso
                                            </button>
                                            <table id="datatableCursos" class="table" data-toggle="table">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th class="text-center">ID CURSO</th>
                                                        <th class="text-center">MOODLE</th>
                                                        <th class="text-center">CURSO</th>
                                                        <th class="text-center">ESTATUS</th>
                                                        <th class="text-center">ACCIONES</th>
                                                    </tr>
                                                    <!--end tr-->
                                                </thead>

                                                <tbody>
                                                    <?php foreach($cursos_sac as $e): ?>
                                                    <tr>
                                                        <td class="text-center"><?= $e->id_cursos_sac?></td>
                                                        <td class="text-center"><?= $e->id_moodle?></td>
                                                        <td class="text-center"><?= $e->dsc_curso?></td>
                                                        <td class="text-center">
                                                            <?php if($e->activo == 1):?>
                                                            <i class="mdi mdi-eye text-success font-18"></i>
                                                            <?php endif; ?>
                                                            <?php if($e->activo == 0):?>
                                                            <i class="mdi mdi-eye-off text-danger font-18"></i>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-center">
                                                            <button title="Ver detalle"
                                                                onclick="ini.inicio.verDetalle(<?= $e->id_cursos_sac?>)"
                                                                class="btn btn-gradient-info px-4"><i
                                                                    class="mdi mdi-file-document-box font-21"></i>
                                                            </button>
                                                            <button title="editar"
                                                                onclick="ini.inicio.editarCursoSac(<?= $e->id_cursos_sac?>)"
                                                                class="btn btn-gradient-warning px-4"><i
                                                                    class="dripicons-pencil font-21"></i>
                                                            </button>

                                                            <?php if($e->activo == 1):?>
                                                            <button title="Desactivar"
                                                                onclick="ini.inicio.activarCursoSac(<?= $e->id_cursos_sac?>,3)"
                                                                class="btn btn-gradient-success px-4 "><i
                                                                    class="mdi mdi-eye font-21"></i>
                                                            </button>
                                                            <?php endif; ?>
                                                            <?php if($e->activo == 0):?>
                                                            <button title="Activar"
                                                                onclick="ini.inicio.activarCursoSac(<?= $e->id_cursos_sac?>,4)"
                                                                class="btn btn-gradient-danger px-4 "><i
                                                                    class="mdi mdi-eye-off font-21"></i>
                                                            </button>
                                                            <?php endif; ?>
                                                            <button title="eliminar"
                                                                onclick="ini.inicio.eliminarCursoSac(<?= $e->id_cursos_sac?>)"
                                                                class="btn btn-gradient-danger px-4 "><i
                                                                    class="dripicons-trash font-21"></i>
                                                            </button>

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
                        <!--end portfolio detail-->

                        <div class="tab-pane fade" id="settings_detail">
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">

                                        <div class="card-body">



                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <!--end settings detail-->
                    </div>
                    <!--end tab-content-->

                </div>
                <!--end col-->
            </div>
            <!--end row-->

        </div><!-- container -->



    </div>
    <!-- end page content -->
</div>
<div id="verDetalleCurso" class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog"
    aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body"  style="max-height: 70vh; overflow-y: auto;">
                <!-- end page title end breadcrumb -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="mt-0 header-title">Detalles del curso</h4>
                                <div class="accordion" id="accordionExample-faq">
                                    <div class="card shadow-none border mb-1">
                                        <div class="card-header" id="headingOne">
                                            <h5 class="my-0">
                                                <button class="btn btn-link ml-4 shadow-none" type="button"
                                                    data-toggle="collapse" data-target="#collapseOne"
                                                    aria-expanded="true" aria-controls="collapseOne">
                                                    Categorias del curso
                                                </button>
                                            </h5>
                                        </div>

                                        <div id="collapseOne" class="collapse show" aria-labelledby="headingOne"
                                            data-parent="#accordionExample-faq">
                                            <div class="card-body" id="detalleCurso">
                                               
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card shadow-none border mb-1">
                                        <div class="card-header" id="headingTwo">
                                            <h5 class="my-0">
                                                <button
                                                    class="btn btn-link collapsed ml-4 align-self-center shadow-none"
                                                    type="button" data-toggle="collapse" data-target="#collapseTwo"
                                                    aria-expanded="false" aria-controls="collapseTwo">
                                                    Periodos del Curso
                                                </button>
                                            </h5>
                                        </div>
                                        <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo"
                                            data-parent="#accordionExample-faq">
                                            <div class="card-body" id="detallePeriodo">
                                 
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card shadow-none border mb-1">
                                        <div class="card-header" id="headingThree">
                                            <h5 class="my-0">
                                                <button class="btn btn-link collapsed ml-4 shadow-none" type="button"
                                                    data-toggle="collapse" data-target="#collapseThree"
                                                    aria-expanded="false" aria-controls="collapseThree">
                                                    Detalles
                                                </button>
                                            </h5>
                                        </div>
                                        <div id="collapseThree" class="collapse" aria-labelledby="headingThree"
                                            data-parent="#accordionExample-faq">
                                            <div class="card-body" id="detalles">
                                                
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!--end accordion-->
                            </div>
                            <!--end card-body-->
                        </div>
                        <!--end card-->
                    </div>
                </div>
                <!--end row-->

            </div>

            <div class="modal-footer" id="guardarPeriodo">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div id="modalAgregarPeriodo" class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog"
    aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">CREAR PERIODO</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <input type="hidden" value="0" id="editar_curso">
            <input type="hidden" value="0" id="editar">
            <input type="hidden" value="0" id="editar_periodo">
            <input type="hidden" value="0" id="id_periodo">
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="mes">MES</label>
                            <select class="form-control" id="mes">
                                <option value="0">Selecciona una opcion</option>
                                <?php foreach($cat_mes as $m): ?>
                                <option value="<?= $m->id_mes ?>"><?= $m->dsc_mes ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="dia_inicio">DIA INICIO</label>
                            <?php $diaActual = date('j');?>

                            <select class="form-control" id="dia_inicio">
                                <option value="0">Selecciona una opción</option>
                                <?php for ($i = 1; $i <= 31; $i++): ?>
                                <option value="<?= $i ?>" <?= ($i == $diaActual) ? 'selected' : '' ?>>
                                    <?= $i ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <!--     <div class="col-md-6">
                        <div class="form-group">
                            <label for="fec_inicio">Fecha Inicio</label>
                            <input type="date" class="form-control" id="fec_inicio" autocomplete="off"
                                placeholder="Nombre del Curso">
                        </div>
                    </div> -->
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="dia_fin">DIA FIN</label>
                            <select class="form-control" id="dia_fin">
                                <option value="0">Selecciona una opción</option>
                                <?php for ($i = 1; $i <= 31; $i++): ?>
                                <option value="<?= $i ?>" <?= ($i == $diaActual) ? 'selected' : '' ?>>
                                    <?= $i ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="periodo">PERIODO</label>
                            <select class="form-control" id="periodo">
                                <option value="0">Selecciona una opción</option>
                                <?php for ($i = 1; $i <= 9; $i++): ?>
                                <option value="<?= $i ?>">
                                    <?='PERIODO '. $i ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <!-- <div class="col-md-6">
                        <label for="descripcion">Descripción</label>
                        <textarea type="text" class="form-control" id="descripcion" autocomplete="off"
                            placeholder="Descripción"></textarea>
                    </div> -->

                </div>
            </div>

            <div class="modal-footer" id="guardar_periodo">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" onclick="ini.inicio.guardarPeriodo()" class="btn btn-primary">Guardar</button>
            </div>
            <div class="modal-footer" id="load_periodo" style="display:none;">
                <button class="btn btn-gradient-primary" type="button" disabled>
                    <span class="spinner-grow spinner-grow-sm" role="status" aria-hidden="true"></span>
                    Loading...
                </button>
            </div>>
        </div>
    </div>
</div>

<div id="modalAgregarCategoria" class="modal fade bs-example-modal-lg2" tabindex="-1" role="dialog"
    aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form id="formCurso">
                <input type="hidden" id="des_larga" name="des_larga">
                <input type="hidden" id="dirigido" name="dirigido">
                <input type="hidden" id="duracion" name="duracion">
                <input type="hidden" id="autogestivo" name="autogestivo">
                <input type="hidden" id="horas" name="horas">
                <input type="hidden" id="curso_linea" name="curso_linea">
                <input type="hidden" id="informacion" name="informacion">
                <div class="modal-header">
                    <h5 class="modal-title">Crear Curso</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                        onclick="ini.inicio.borrarDatos()">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <input type="hidden" value="0" id="editar_curso">
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nombre_curso">Nombre del Curso</label>
                                <input type="text" class="form-control" id="nombre_curso" name="nombre_curso"
                                    autocomplete="off" placeholder="Nombre del Curso">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="id_moodle">ID Moodle</label>
                                <input type="text" class="form-control" id="id_moodle" name="id_moodle"
                                    autocomplete="off" placeholder="ID moodle">
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <label for="id_moodle">Nuevo Curso</label>
                            <div class="checkbox checkbox-primary text-center">
                                <input id="new_curso" type="checkbox" name="new_curso">
                                <label for="new_curso">
                                    Nuevo
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="categoria">Categoria</label>
                                <select class="form-control select2" id="categoria" name="categoria[]" multiple
                                    data-placeholder="Selecciona una opción">
                                    <?php foreach ($categoria_sac as $c): ?>
                                    <option value="<?= $c->id_categoria_sac ?>"><?= $c->dsc_categoria_sac ?></option>
                                    <?php endforeach; ?>
                                </select>

                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="periodo">Periodo</label>
                            <select class="form-control select2" id="periodos" name="periodo[]" multiple
                                data-placeholder="Selecciona una opción">
                                <?php foreach($periodo_sac as $p): ?>
                                <option value="<?= $p->id_periodo_sac ?>">
                                    <?= $p->dia_inicio.' AL '.$p->dia_fin.' DE '.$meses[$p->mes].' P'.$p->periodo.'-'.$p->id_periodo_sac ?>
                                </option>
                                <?php endforeach; ?>
                            </select>

                        </div>

                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="descripcion">Descripción Corta</label>
                                <textarea class="form-control" id="descripcion" name="descripcion"
                                    placeholder="Descriocion Corta"></textarea>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Descripción Larga</label><br>
                                <a onclick="descripcion()" class="btn btn-gradient-info"
                                    placeholder="Descriocion Larga">Agregar Descripción Larga</a>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Agregar Detalles</label><br>
                                <a onclick="detallerCurso()" class="btn btn-gradient-info"
                                    placeholder="Descriocion Larga">Detalles del Curso</a>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="col-lg-4 align-self-center mb-3 mb-lg-0">
                                <div class="met-profile-main">
                                    <div class="met-profile-main-pic" id="vista_img_ruta">

                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="img_ruta">Imagen</label>
                                <input type="file" class="form-control" id="img_ruta" name="img_ruta"
                                    accept="image/png, image/jpeg">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="col-lg-4 align-self-center mb-3 mb-lg-0">
                                <div class="met-profile-main">
                                    <div class="met-profile-main-pic" id="vista_img_deta_ruta">

                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="img_deta_ruta">Imagen Detalle</label>
                                <input type="file" class="form-control" id="img_deta_ruta" name="img_deta_ruta">

                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" id="guardarCursos">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"
                        onclick="ini.inicio.borrarDatos()">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
                <div class="modal-footer" id="load_curso" style="display:none;">
                    <button class="btn btn-gradient-primary" type="button" disabled>
                        <span class="spinner-grow spinner-grow-sm" role="status" aria-hidden="true"></span>
                        Loading...
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<div id="modalTextEditor" class="modal fade bs-example-modal-lg2" tabindex="-1" role="dialog"
    aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">

                <!-- Editor Summernote -->
                <div id="summernote">
                    <p>Escribe aqui el contenido de la descripción larga</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="guardarContenido">Guardar</button>
            </div>

        </div>
    </div>
</div>
<div id="modalAgregarDetalles" class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog"
    aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Agregar Detalles</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="detalleCurso" style="max-height: 70vh; overflow-y: auto;">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="detalle_dirigido">Dirigido a <span class="text-danger">*<span></label>
                            <input type="text" class="form-control" id="detalle_dirigido" name="detalle_dirigido">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="detalle_duracion">Duración <span class="text-danger">*<span></label>
                            <input type="text" class="form-control" id="detalle_duracion" name="detalle_duracion">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="detalle_autogestivo">Autogestivo <span class="text-danger">*<span></label>
                            <input type="text" class="form-control" id="detalle_autogestivo" name="detalle_autogestivo">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="detalle_horas">Horas <span class="text-danger">*<span></label>
                            <input type="text" class="form-control" id="detalle_horas" name="detalle_horas">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="detalle_curso_linea">Curso en linea <span class="text-danger">*<span></label>
                            <input type="text" class="form-control" id="detalle_curso_linea" name="detalle_curso_linea">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="detalle_informacion">Información <span class="text-danger">*<span></label>
                            <input type="text" class="form-control" id="detalle_informacion" name="detalle_informacion">
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer" id="guardarPeriodo">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="guardarDetalle">Guardar</button>
            </div>
        </div>
    </div>
</div>


<link href="<?php echo base_url(); ?>plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet"
    type="text/css" />
<!-- App css -->
<link href="<?php echo base_url(); ?>assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
<link href="<?php echo base_url(); ?>assets/css/jquery-ui.min.css" rel="stylesheet">
<link href="<?php echo base_url(); ?>assets/css/metisMenu.min.css" rel="stylesheet" type="text/css" />
<link href="<?php echo base_url(); ?>assets/css/app.min.css" rel="stylesheet" type="text/css" />
<link href="<?= base_url()?>assets/css/icons.min.css" rel="stylesheet" type="text/css" />
<!-- jQuery  -->
<script src="<?php echo base_url(); ?>assets/js/jquery.min.js"></script>
<script src="<?php echo base_url(); ?>assets/js/jquery-ui.min.js"></script>
<script src="<?php echo base_url(); ?>assets/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo base_url(); ?>assets/js/jquery.slimscroll.min.js"></script>
<!-- Required datatable js -->
<script src="<?php echo base_url(); ?>plugins/datatables/jquery.dataTables.min.js"></script>
<script src="<?php echo base_url(); ?>plugins/datatables/dataTables.bootstrap4.min.js"></script>
<script src="<?php echo base_url(); ?>assets/pages/jquery.analytics_customers.init.js"></script>

<script src="<?= base_url()?>plugins/apexcharts/apexcharts.min.js"></script>

<!-- App js -->
<script src="<?= base_url()?>assets/js/app.js"></script>


<script src="<?= base_url()?>assets/js/metismenu.min.js"></script>
<script src="<?= base_url()?>assets/js/waves.js"></script>
<script src="<?= base_url()?>assets/js/feather.min.js"></script>



<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />


<!-- include summernote css/js -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote.min.js"></script>
<script>
ini.inicio.guardarCursos();
$(document).ready(function() {
    $('#summernote').summernote({
        height: 500, // Altura del editor
        lang: 'es-ES',
        toolbar: [
            ['style', ['bold', 'italic', 'underline', 'clear']],
            ['font', ['strikethrough', 'superscript', 'subscript']],
            ['fontsize', ['fontsize']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['height', ['height']],
            ['insert', ['link', 'picture', 'video']],
            ['view', ['fullscreen', 'codeview', 'help']],
        ],
    });
    $('#datatableCategorias,#datatablePeriodos,#datatableCursos').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json' // Ruta al archivo de localización
        },
        destroy: true,
        searching: true,
    });
    // Inicializar Select2 en todos los select con la clase .select2
    $('.select2').select2({
        placeholder: "Selecciona una opción", // Texto del placeholder
        allowClear: true, // Permite borrar todas las selecciones
        width: '100%', // Ajusta el ancho al 100% del contenedor
        color: 'black'
    });
    $("#img_ruta").on('change', function() {
        console.log('entro al change');
        const file = this.files[0]; // Obtener el archivo seleccionado

        if (file) {
            const reader = new FileReader();

            // Leer el archivo como una URL de datos
            reader.onload = function(e) {
                // Mostrar la nueva imagen en la vista previa
                $('#vista_img_ruta').html(
                    `<img src="${e.target.result}" class="img-uniforme" alt="Nueva imagen" class="rounded-circle">`
                );
            };

            reader.readAsDataURL(file); // Convertir el archivo a una URL de datos
        } else {
            // Limpiar la vista previa si no se selecciona ningún archivo
            $('#vista_img_deta_ruta').html('<p>No hay imagen seleccionada.</p>');
        }
    });
    $("#img_deta_ruta").on('change', function() {
        const file = this.files[0]; // Obtener el archivo seleccionado

        if (file) {
            const reader = new FileReader();

            // Leer el archivo como una URL de datos
            reader.onload = function(e) {
                // Mostrar la nueva imagen en la vista previa
                $('#vista_img_deta_ruta').html(
                    `<img src="${e.target.result}" class="img-uniforme" alt="Nueva imagen" class="rounded-circle">`
                );
            };

            reader.readAsDataURL(file); // Convertir el archivo a una URL de datos
        } else {
            // Limpiar la vista previa si no se selecciona ningún archivo
            $('#vista_img_deta_ruta').html('<p>No hay imagen seleccionada.</p>');
        }
    });
    $('#guardarContenido').on('click', function() {
        // Obtener el contenido del editor
        var contenido = $('#summernote').summernote('code');
        // Asignar el contenido al campo oculto
        $('#des_larga').val(contenido);
        // Cerrar el modal
        $('#modalTextEditor').modal('hide');
        $('#modalAgregarCategoria').modal('show');
    });
    $('#guardarDetalle').on('click', function() {
        let dirigido = $('#detalle_dirigido').val();
        let duracion = $('#detalle_duracion').val();
        let autogestivo = $('#detalle_autogestivo').val();
        let horas = $('#detalle_horas').val();
        let curso_linea = $('#detalle_curso_linea').val();
        let informacion = $('#detalle_informacion').val();
        $('#dirigido').val(dirigido);
        $('#duracion').val(duracion);
        $('#autogestivo').val(autogestivo);
        $('#horas').val(horas);
        $('#curso_linea').val(curso_linea);
        $('#informacion').val(informacion);
        $('#modalAgregarDetalles').modal('hide');
        $('#modalAgregarCategoria').modal('show');

    });

});

function descripcion() {
    $('#modalAgregarCategoria').modal('hide');
    $('#modalTextEditor').modal('show');
}

function detallerCurso() {
    $('#modalAgregarCategoria').modal('hide');
    $('#modalAgregarDetalles').modal('show');
}
</script>