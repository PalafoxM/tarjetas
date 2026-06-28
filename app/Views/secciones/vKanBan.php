<style>
    .gu-mirror{
    position: fixed !important;
    margin: 0 !important;
    z-index: 9999 !important;
    opacity: 0.8;
    -ms-filter: "progid:DXImageTransform.Microsoft.Alpha(Opacity=80)";
    filter: alpha(opacity=80);
  }
  .gu-hide{
    display: none !important;
  }
  .gu-unselectable{
    -webkit-user-select: none !important;
    -moz-user-select: none !important;
    -ms-user-select: none !important;
    user-select: none !important;
  }
  .gu-transit{
    opacity: 0.2;
    -ms-filter: "progid:DXImageTransform.Microsoft.Alpha(Opacity=20)";
    filter: alpha(opacity=20);
  }
  .task-list-items{
    min-height: 1000px!important;
  }
  
</style>
<?php 
use CodeIgniter\I18n\Time;
?>                
<div class="content">
    <!-- Start Content-->
    <div class="container-fluid">
        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="<?=base_url("/index.php/Auditoria")?>">Listado de auditorias</a></li>
                            <li class="breadcrumb-item"><a href="<?php echo base_url() ?>/index.php/Auditoria/requerimiento/<?php echo $tokenA;?>">Requerimientos</a></li>
                            <li class="breadcrumb-item active">Seguimiento de auditoria</li>
                        </ol>
                    </div>
                    <h4 class="page-title" id="encabezado" data-vigente="<?=$datos_requerimiento->data[0]->vigente?>" >Seguimiento de auditoria <!--onclick="saeg.auditoria.modal_auditoria()"-->
                        <?php if ($datos_requerimiento->data[0]->vigente == 1):?>
                        <a href="<?php echo base_url();?>/index.php/kanban/solicitud_informacion/<?php echo $token;?>/<?php echo $tokenA;?>"  class="btn btn-success btn-sm ms-3"><i class="fa-solid fa-magnifying-glass-plus"></i> Agregar Solicitud de Información</a></h4>
                        <?php endif?>
                </div>
            </div>
        </div>     
        <!-- end page title --> 

        <div class="row">
            <div class="col-12">
                <div class="card border-primary border">
                    <div class="card-body">
                        <div class="row">                                                                                     
                            <div class="col-6">                                                
                                <span><b>Folio auditoria: </b><?= (!empty($datos_auditoria->data))?$datos_auditoria->data[0]->numero_auditoria:""; ?></span><br>
                                <span><b>Nombre de la auditoría: </b><?= (!empty($datos_auditoria->data))?$datos_auditoria->data[0]->dsc_auditoria:""; ?></span><br>
                                <span><b>Ente Auditor: </b><?= (!empty($datos_auditoria->data))?$datos_auditoria->data[0]->auditor:""; ?></span><br>
                                <span><b>Periodo de revisión: </b><?= (!empty($datos_auditoria->data))?$datos_auditoria->data[0]->periodo_revision_euro:""; ?></span><br>
                                <span><b>Año(s) fiscal(es) revisado(s): </b><?= (!empty($datos_auditoria->data))?$datos_auditoria->data[0]->auditoria_anio_fiscal:""; ?></span><br>
                            </div>
                            <div class="col-6">
                                <span><b>Requerimiento:</b><?= (!empty($datos_requerimiento->data))?$datos_requerimiento->data[0]->dsc_requerimiento:""; ?></span><br>
                                <span><b>Fecha registro requerimiento: </b><?= (!empty($datos_requerimiento->data))?$datos_requerimiento->data[0]->fecha_registro:""; ?></span><br>
                                <span><b>Fecha límite requerimiento: </b></span><?= (!empty($datos_requerimiento->data))?$datos_requerimiento->data[0]->fecha_limite:""; ?><br>                                                
                                <br>
                                <?php if ($datos_requerimiento->data[0]->vigente == 1):?>
                                <button class ="btn btn-primary" id="btn_cerrar_requerimiento" style="display:none" onclick="saeg.auditoria.cerrarRequerimiento(this)">Cerrar requerimiento</button>
                                <?php endif?>
                            </div>   
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <input type="hidden" id="id_requerimiento" class="id_requerimiento" value="<?php echo $token;?>">
        <div class="row">
            <div class="col-12">
                <div class="board">
                    <div class="tasks" data-plugin="dragula" data-containers='["contain_pendientes", "contain_analisis", "contain_entregable"]'>
                        <h5 class="mt-0 task-header text-uppercase">Pendiente</h5>
                        
                        <div id="contain_pendientes" class="task-list-items">
                            <?php  foreach ($solicitudes_pendiente->data as $pend) {?>
                                <!-- Task Item -->                                               
                                <?php //Calcular la diferencia entre fecha límite de entrega y fecha actual para mostrar prioridad de solicitud
                                    $prioridad = "";
                                    $current = Time::now('America/Mexico_City', 'en_US');
                                    $test    = Time::parse($pend->fecha_limite_entrega_informacion, 'America/Mexico_City');
                                    $diff = $current->difference($test);
                                    $cantidad_restantes = $diff->getDays();
                                    //Calcular dias entre fecha de la solicitud y fecha limite de entrega
                                    $registro = Time::parse($pend->fecha_registro, 'America/Mexico_City');
                                    $limite    = Time::parse($pend->fecha_limite_entrega_informacion, 'America/Mexico_City');
                                    $diff2 = $registro->difference($limite);
                                    $dias_para_responder = $diff2->getDays();
                                    //Obtener el tercio de días para responder para poner la eitqueta de prioridad
                                    $modulo = $dias_para_responder/3;
                                    $advertencia = $dias_para_responder - $modulo;
                                    $urgencia = $dias_para_responder - 2*$modulo;
                                    if($advertencia >= $cantidad_restantes){ $prioridad ="bg-warning"; $prioridad_texto = "Próximo a vencer"; }
                                    if($urgencia >= $cantidad_restantes){ $prioridad ="bg-danger"; $prioridad_texto = "Urgente";}
                                ?>
                                <div class="card mb-0 manita" id="solicitud_<?php echo $pend->id_solicitud_informacion;?>" >                                                                                                    
                                    <div class="card-body p-3">
                                        <small class="float-end text-muted"><?php echo date('d/m/Y', strtotime($pend->fecha_limite_entrega_informacion));?>&nbsp; &nbsp;<span title="Eliminar solicitud" class="text-dark" onClick="saeg.auditoria.eliminar_solicitud_req(<?php echo $pend->id_solicitud_informacion;?>)"><i class="fas fa-trash"></i></span></small>
                                        <?php if($prioridad){?><span class="badge <?php echo $prioridad;?>"><?php echo $prioridad_texto;?></span><?php } ?>
                                        <div onClick="saeg.auditoria.detalle_solicitud_informacion(<?php echo $pend->id_solicitud_informacion;?>)">
                                            <h5 class="mt-2 mb-2">
                                                <a href="#"  class="text-body">
                                                <?php
                                                if(isset($unidades_responsables_pendientes) && !empty($unidades_responsables_pendientes)):
                                                    $k=$y=0;
                                                    foreach ($unidades_responsables_pendientes->data as $r) {
                                                        if($pend->id_solicitud_informacion == $r->id_solicitud_informacion && $k<4){
                                                            if($y==0){
                                                                $color="";$y=1;                                                                            
                                                            }else{
                                                                $color="color_gris";$y=0;
                                                            }
                                                            echo "<span class='".$color."'>".$r->dsc_unidad_responsable."</span><br>";
                                                            $k++;
                                                        }else{
                                                            if($k==4){
                                                                echo "&nbsp;&nbsp;&nbsp;&nbsp;...";
                                                                $k++;
                                                            }
                                                        }                                                                   
                                                    }
                                                endif;
                                                    ?>
                                                </a>
                                            </h5>

                                            <p class="mb-0">
                                                <!--<span class="pe-2 text-nowrap mb-2 d-inline-block">
                                                    <i class="mdi mdi-briefcase-outline text-muted"></i>
                                                    iOS
                                                </span>-->
                                                <span class="text-nowrap mb-2 d-inline-block">
                                                    <i class="mdi mdi-comment-multiple-outline text-muted"></i>
                                                    Folio <b><?php echo $pend->folio_oficio_solicitud;?></b> 
                                                </span>
                                            </p>

                                        <!--<div class="dropdown float-end">
                                            <a href="#" class="dropdown-toggle text-muted arrow-none" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="mdi mdi-dots-vertical font-18"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                
                                                <a href="javascript:void(0);" class="dropdown-item"><i class="mdi mdi-pencil me-1"></i>Edit</a>
                                                
                                                <a href="javascript:void(0);" class="dropdown-item"><i class="mdi mdi-delete me-1"></i>Delete</a>
                                                
                                                <a href="javascript:void(0);" class="dropdown-item"><i class="mdi mdi-plus-circle-outline me-1"></i>Add People</a>
                                                
                                                <a href="javascript:void(0);" class="dropdown-item"><i class="mdi mdi-exit-to-app me-1"></i>Leave</a>
                                            </div>
                                        </div>-->
                                            <?php 
                                                if(isset($unidades_responsables_pendientes) && !empty($unidades_responsables_pendientes)):
                                                    $j=0;
                                                    foreach ($unidades_responsables_pendientes->data as $r) {
                                                        if($pend->id_solicitud_informacion == $r->id_solicitud_informacion && $j < 4){?>
                                                            <p class="mb-0">
                                                                <img src="<?php if($r->ruta_foto!=''){echo base_url()."/".$r->ruta_foto;}else{$random= rand(1,8); echo base_url()."/fotos/Usuario".$random."-8.png";}?>" alt="user-img" class="avatar-xs rounded-circle me-1" />
                                                                <span class="align-middle texto_chiquito"><?php echo strtoupper($r->nombre_completo); ?></span>
                                                            </p>
                                                        <?php $j++; }else{
                                                            if($j == 4){
                                                                echo "&nbsp;&nbsp;&nbsp;&nbsp;...";
                                                                $j++;
                                                            }
                                                        }
                                                    }
                                                endif;
                                            ?>   
                                            </div>                                                     
                                    </div> <!-- end card-body -->
                                </div>
                                <!-- Task Item End -->
                            <?php }?>
                        </div> <!-- end company-list-1-->
                    </div>

                    <div class="tasks">
                        <h5 class="mt-0 task-header text-uppercase">Análisis</h5>
                        
                        <div id="contain_analisis" class="task-list-items">
                            
                        <?php foreach ($solicitudes_analisis->data as $anal) {?>
                                <!-- Task Item -->                                               
                                <?php //Calcular la diferencia entre fecha límite de entrega y fecha actual para mostrar prioridad de solicitud
                                    $prioridad = "";
                                    $current = Time::now('America/Mexico_City', 'en_US');
                                    $test    = Time::parse($anal->fecha_limite_entrega_informacion, 'America/Mexico_City');
                                    $diff = $current->difference($test);
                                    $cantidad_restantes = $diff->getDays();
                                    //Calcular dias entre fecha de la solicitud y fecha limite de entrega
                                    $registro = Time::parse($anal->fecha_registro, 'America/Mexico_City');
                                    $limite    = Time::parse($anal->fecha_limite_entrega_informacion, 'America/Mexico_City');
                                    $diff2 = $registro->difference($limite);
                                    $dias_para_responder = $diff2->getDays();
                                    //Obtener el tercio de días para responder para poner la eitqueta de prioridad
                                    $modulo = $dias_para_responder/3;
                                    $advertencia = $dias_para_responder - $modulo;
                                    $urgencia = $dias_para_responder - 2*$modulo;
                                    if($advertencia >= $cantidad_restantes){ $prioridad ="bg-warning"; $prioridad_texto = "Próximo a vencer"; }
                                    if($urgencia >= $cantidad_restantes){ $prioridad ="bg-danger"; $prioridad_texto = "Urgente";}
                                ?>
                                <div class="card mb-0 manita" id="solicitud_<?php echo $anal->id_solicitud_informacion;?>" onClick="saeg.auditoria.detalle_solicitud_informacion(<?php echo $anal->id_solicitud_informacion;?>)">
                                    <div class="card-body p-3">
                                        <small class="float-end text-muted"><?php echo date('d/m/Y', strtotime($anal->fecha_limite_entrega_informacion));?></small>
                                        <?php if($prioridad){?><span class="badge <?php echo $prioridad;?>"><?php echo $prioridad_texto;?></span><?php } ?>

                                        <h5 class="mt-2 mb-2">
                                            <a href="#" onClick="saeg.auditoria.detalle_solicitud_informacion(<?php echo $anal->id_solicitud_informacion;?>)" class="text-body">
                                            <?php
                                            //print_r($anal->id_solicitud_informacion);
                                            if(isset($unidades_responsables_analisis) && !empty($unidades_responsables_analisis)):
                                                $k=$y=0;
                                                foreach ($unidades_responsables_analisis->data as $r) {                                                                    
                                                    if($anal->id_solicitud_informacion == $r->id_solicitud_informacion && $k<4){
                                                        if($y==0){
                                                            $color="";$y=1;                                                                            
                                                        }else{
                                                            $color="color_gris";$y=0;
                                                        }
                                                        echo "<span class='".$color."'>".$r->dsc_unidad_responsable."</span><br>";
                                                        $k++;
                                                    }else{
                                                        if($k==4){
                                                            echo "&nbsp;&nbsp;&nbsp;&nbsp;...";
                                                            $k++;
                                                        }
                                                    }   
                                                }
                                            endif;
                                                ?>
                                            </a>
                                        </h5>

                                        <p class="mb-0">
                                            <!--<span class="pe-2 text-nowrap mb-2 d-inline-block">
                                                <i class="mdi mdi-briefcase-outline text-muted"></i>
                                                iOS
                                            </span>-->
                                            <span class="text-nowrap mb-2 d-inline-block">
                                                <i class="mdi mdi-comment-multiple-outline text-muted"></i>
                                                Folio <b><?php echo $anal->folio_oficio_solicitud;?></b> 
                                            </span>
                                        </p>

                                        <!-- <div class="dropdown float-end">
                                            <a href="#" class="dropdown-toggle text-muted arrow-none" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="mdi mdi-dots-vertical font-18"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                
                                                <a href="javascript:void(0);" class="dropdown-item"><i class="mdi mdi-pencil me-1"></i>Edit</a>
                                                
                                                <a href="javascript:void(0);" class="dropdown-item"><i class="mdi mdi-delete me-1"></i>Delete</a>
                                                
                                                <a href="javascript:void(0);" class="dropdown-item"><i class="mdi mdi-plus-circle-outline me-1"></i>Add People</a>
                                                
                                                <a href="javascript:void(0);" class="dropdown-item"><i class="mdi mdi-exit-to-app me-1"></i>Leave</a>
                                            </div>
                                        </div> -->
                                        <?php 
                                            if(isset($unidades_responsables_analisis) && !empty($unidades_responsables_analisis)):
                                                $j=0;                                                                
                                                foreach ($unidades_responsables_analisis->data as $r) {
                                                    if($anal->id_solicitud_informacion == $r->id_solicitud_informacion&& $j < 4){?>
                                                        <p class="mb-0">
                                                            <img src="<?php if($r->ruta_foto!=''){echo base_url()."/".$r->ruta_foto;}else{$random= rand(1,8); echo base_url()."/fotos/Usuario".$random."-8.png";}?>" alt="user-img" class="avatar-xs rounded-circle me-1" />
                                                            <span class="align-middle texto_chiquito"><?php echo strtoupper($r->nombre_completo); ?></span>
                                                        </p>
                                                    <?php $j++; }else{
                                                        if($j == 4){
                                                            echo "&nbsp;&nbsp;&nbsp;&nbsp;...";
                                                            $j++;
                                                        }
                                                    }
                                                }
                                            endif;
                                        ?>                                                        
                                    </div> <!-- end card-body -->
                                </div>
                                <!-- Task Item End -->
                            <?php }?>


                        </div> <!-- end company-list-2-->
                    </div>


                    <div class="tasks">
                        <h5 class="mt-0 task-header text-uppercase">Entregable</h5>
                        <div id="contain_entregable" class="task-list-items">
                        <?php foreach ($solicitudes_entregable->data as $entre) {?>
                                <!-- Task Item -->                                               
                                <?php //Calcular la diferencia entre fecha límite de entrega y fecha actual para mostrar prioridad de solicitud
                                    $prioridad = "";
                                    $current = Time::now('America/Mexico_City', 'en_US');
                                    $test    = Time::parse($entre->fecha_limite_entrega_informacion, 'America/Mexico_City');
                                    $diff = $current->difference($test);
                                    $cantidad_restantes = $diff->getDays();
                                    //Calcular dias entre fecha de la solicitud y fecha limite de entrega
                                    $registro = Time::parse($entre->fecha_registro, 'America/Mexico_City');
                                    $limite    = Time::parse($entre->fecha_limite_entrega_informacion, 'America/Mexico_City');
                                    $diff2 = $registro->difference($limite);
                                    $dias_para_responder = $diff2->getDays();
                                    //Obtener el tercio de días para responder para poner la eitqueta de prioridad
                                    $modulo = $dias_para_responder/3;
                                    $advertencia = $dias_para_responder - $modulo;
                                    $urgencia = $dias_para_responder - 2*$modulo;
                                    if($advertencia >= $cantidad_restantes){ $prioridad ="bg-warning"; $prioridad_texto = "Próximo a vencer"; }
                                    if($urgencia >= $cantidad_restantes){ $prioridad ="bg-danger"; $prioridad_texto = "Urgente";}
                                ?>
                                <div class="card mb-0 manita" id="solicitud_<?php echo $entre->id_solicitud_informacion;?>" onClick="saeg.auditoria.detalle_solicitud_informacion(<?php echo $entre->id_solicitud_informacion;?>)">
                                    <div class="card-body p-3">
                                        <small class="float-end text-muted"><?php echo date('d/m/Y', strtotime($entre->fecha_limite_entrega_informacion));?></small>                                                        

                                        <h5 class="mt-2 mb-2">
                                            <a href="#" onClick="saeg.auditoria.detalle_solicitud_informacion(<?php echo $entre->id_solicitud_informacion;?>)" class="text-body">
                                            <?php
                                            //print_r($anal->id_solicitud_informacion);
                                            if(isset($unidades_responsables_entregable) && !empty($unidades_responsables_entregable)):
                                                $k=$y=0;                                                                
                                                foreach ($unidades_responsables_entregable->data as $r) {
                                                    if($entre->id_solicitud_informacion == $r->id_solicitud_informacion && $k < 4){
                                                        if($y==0){
                                                            $color="";$y=1;                                                                            
                                                        }else{
                                                            $color="color_gris";$y=0;
                                                        }
                                                        echo "<span class='".$color."'>".$r->dsc_unidad_responsable."</span><br>";
                                                        $k++;
                                                    }else{
                                                        if($k==4){
                                                            echo "&nbsp;&nbsp;&nbsp;&nbsp;...";
                                                            $k++;
                                                        }
                                                    }   
                                                }                                                                
                                            endif;
                                                ?>
                                            </a>
                                        </h5>

                                        <p class="mb-0">
                                            <!--<span class="pe-2 text-nowrap mb-2 d-inline-block">
                                                <i class="mdi mdi-briefcase-outline text-muted"></i>
                                                iOS
                                            </span>-->
                                            <span class="text-nowrap mb-2 d-inline-block">
                                                <i class="mdi mdi-comment-multiple-outline text-muted"></i>
                                                Folio <b><?php echo $entre->folio_oficio_solicitud;?></b> 
                                            </span>
                                        </p>

                                        <!-- <div class="dropdown float-end">
                                            <a href="#" class="dropdown-toggle text-muted arrow-none" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="mdi mdi-dots-vertical font-18"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                
                                                <a href="javascript:void(0);" class="dropdown-item"><i class="mdi mdi-pencil me-1"></i>Edit</a>
                                                
                                                <a href="javascript:void(0);" class="dropdown-item"><i class="mdi mdi-delete me-1"></i>Delete</a>
                                                
                                                <a href="javascript:void(0);" class="dropdown-item"><i class="mdi mdi-plus-circle-outline me-1"></i>Add People</a>
                                                
                                                <a href="javascript:void(0);" class="dropdown-item"><i class="mdi mdi-exit-to-app me-1"></i>Leave</a>
                                            </div>
                                        </div> -->
                                        <?php 
                                            if(isset($unidades_responsables_entregable) && !empty($unidades_responsables_entregable)):
                                                $j=0;   
                                                foreach ($unidades_responsables_entregable->data as $r) {
                                                    if($entre->id_solicitud_informacion == $r->id_solicitud_informacion && $j<4){?>
                                                        <p class="mb-0">
                                                            <img src="<?php if($r->ruta_foto!=''){echo base_url()."/".$r->ruta_foto;}else{$random= rand(1,8); echo base_url()."/fotos/Usuario".$random."-8.png";}?>" alt="user-img" class="avatar-xs rounded-circle me-1" />
                                                            <span class="align-middle texto_chiquito"><?php echo strtoupper($r->nombre_completo); ?></span>
                                                        </p>
                                                    <?php $j++; }else{
                                                        if($j == 4){
                                                            echo "&nbsp;&nbsp;&nbsp;&nbsp;...";
                                                            $j++;
                                                        }
                                                    }
                                                }
                                            endif;
                                        ?>                                                        
                                    </div> <!-- end card-body -->
                                </div>
                                <!-- Task Item End -->
                            <?php }?>

                        </div> <!-- end company-list-3-->
                    </div>

                </div> <!-- end .board-->
            </div> <!-- end col -->
        </div>
        <!-- end row-->

    </div>
    <!-- container -->

</div>
<!-- content -->         
        
<!--  Task details modal -->
<div class="modal fade task-modal-content" id="task-detail-modal" tabindex="-1" role="dialog" aria-labelledby="TaskDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div id="info_solicitud_detalle"></div>                    
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>
<!-- Modal -->        
<div  id="mdl_respuesta_texto" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="fill-info-modalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content modal-filled bg-info">
            <div class="modal-header">
                <h4 class="modal-title" id="fill-info-modalLabel">Respuesta de la tarea</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
            <div class="modal-body">
                <div id="div_respuesta_texto"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>
<!-- Modal -->        
<div  id="mdl_respuesta_documento" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="fill-info-modalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content modal-filled bg-success">
            <div class="modal-header">
                <h4 class="modal-title" id="fill-info-modalLabel">Documentos agregados a la respuesta</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
            <div class="modal-body">
                <div id="div_respuesta_documentos"></div>
            </div>
            <div class="modal-footer">
                <button type="button" id="descarga_zip" class="btn btn-light">Descargar Zip <i class="fa-solid fa-download"></i></button>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>


<!-- Modal de requerimiento -->
<div id="mdl_requerimiento" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="info-header-modalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header modal-colored-header bg-info">
                <h4 class="modal-title" id="info-header-modalLabel">Finalizar requerimiento</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
            <div class="modal-body">
                <form id="frm_requerimiento">
                    <div class="row">
                        <div class="col-xl-3 col-lg-3 col-md-3 col-sm-12 mb-3">
                            <label class="form-label">Folio de oficio </label>
                            <input type="text" class="form-control" name="folio" id="folio" autocomplete="off" value="">
                        </div>
                        <div class="col-xl-3 col-lg-3 col-md-3 col-sm-12 mb-3 position-relative" id="fecha_limite_div">
                            <label class="form-label">Fecha de término <font color="red">*</font></label>
                            <input type="text" class="form-control" name="fecha_limite" id="fecha_limite" data-provide="datepicker" data-date-autoclose="true" data-date-container="#fecha_limite_div" data-date-format="dd/mm/yyyy" data-date-language="es" data-date-start-date="<?=date("d/m/Y")?>" value="" autocomplete="off" required>
                        </div>
                    </div>
                    <div class="row" id="input_doc_div">
                        <label for="input_doc" class="form-label">Oficio de finalización</label>
                        <input id="input_doc" name="input_doc" type="file" class="file" data-preview-file-type="text" data-theme="fas" >
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Cerrar</button>
                <button type="button" class="btn btn-info" id="btn_requerimiento" data-token="<?=$token?>" onclick="saeg.auditoria.terminarRequerimiento(this)" ><i class="fa-regular fa-floppy-disk"></i> Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
    saeg.auditoria.inicializa_kanban();
    saeg.auditoria.revisa_estatus_solicitudes();
    let config = {
        language: 'es',
        showUploadStats: true,
        showUpload: false,
        allowedFileExtensions: ['pdf']
    };
    $("#input_doc").fileinput(config);
</script>
        

        