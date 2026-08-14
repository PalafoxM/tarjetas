<!-- Topbar Start -->
<?php $session = \Config\Services::session(); ?>
<?php $nombreCompleto = trim((string) $session->get('nombre_completo')) ?: 'Usuario'; ?>
<link async href="https://fonts.googleapis.com/css?family=Warnes" data-generated="http://enjoycss.com" rel="stylesheet" type="text/css"/>

<style>
    .neon {
        display: inline-block;
        -webkit-box-sizing: border-box;
        -moz-box-sizing: border-box;
        box-sizing: border-box;
        padding: 10px;
        border: none;
        font: normal 20px/normal "Warnes", Helvetica, sans-serif;
        color: rgba(255,255,255,1);
        text-decoration: normal;
        text-align: center;
        -o-text-overflow: clip;
        text-overflow: clip;
        white-space: pre;
        text-shadow: 0 0 10px rgba(255,255,255,1) , 0 0 20px rgba(255,255,255,1) , 0 0 30px rgba(255,255,255,1) , 0 0 40px #ff00de , 0 0 70px #ff00de , 0 0 80px #ff00de , 0 0 100px #ff00de ;
        -webkit-transition: all 200ms cubic-bezier(0.42, 0, 0.58, 1);
        -moz-transition: all 200ms cubic-bezier(0.42, 0, 0.58, 1);
        -o-transition: all 200ms cubic-bezier(0.42, 0, 0.58, 1);
        transition: all 200ms cubic-bezier(0.42, 0, 0.58, 1);
       
    }

    .neon:hover {
    text-shadow: 0 0 10px rgba(255,255,255,1) , 0 0 20px rgba(255,255,255,1) , 0 0 30px rgba(255,255,255,1) , 0 0 40px #00ffff , 0 0 70px #00ffff , 0 0 80px #00ffff , 0 0 100px #00ffff ;
    }

    .notification-bell-btn {
        position: relative;
        min-width: 42px;
        min-height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
    }

    .notification-bell-badge {
        position: absolute;
        top: 2px;
        right: 2px;
        transform: translate(30%, -30%);
        min-width: 18px;
        height: 18px;
        padding: 0 5px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .68rem;
        font-weight: 700;
        line-height: 1;
    }

    .notification-tray {
        width: min(380px, calc(100vw - 24px));
        max-height: 430px;
        overflow: hidden;
    }

    .notification-tray__filters {
        display: flex;
        flex-wrap: wrap;
        gap: .45rem;
        margin-top: .55rem;
    }

    .notification-tray__filter {
        border: 1px solid rgba(148, 163, 184, .22);
        background: rgba(15, 23, 42, .75);
        color: #cbd5e1;
        border-radius: 999px;
        padding: .33rem .7rem;
        font-size: .72rem;
        line-height: 1;
        transition: background .15s ease, border-color .15s ease, color .15s ease, transform .15s ease;
    }

    .notification-tray__filter:hover {
        color: #fff;
        border-color: rgba(59, 130, 246, .45);
        transform: translateY(-1px);
    }

    .notification-tray__filter.is-active {
        background: linear-gradient(135deg, rgba(59, 130, 246, .95), rgba(14, 165, 233, .9));
        border-color: transparent;
        color: #fff;
        box-shadow: 0 10px 22px rgba(14, 165, 233, .24);
    }

    .notification-tray__list {
        max-height: 320px;
        overflow-y: auto;
    }

    .notification-tray__item {
        display: block;
        padding: .75rem .9rem;
        border-bottom: 1px solid rgba(148, 163, 184, .12);
        color: #e2e8f0;
        text-decoration: none;
        border-left: 3px solid transparent;
    }

    .notification-tray__item:hover {
        background: rgba(59, 130, 246, .12);
        color: #fff;
    }

    .notification-tray__item.is-unread {
        background: rgba(14, 165, 233, .08);
        border-left-color: rgba(56, 189, 248, .9);
    }

    .notification-tray__item.is-rejected {
        border-left-color: rgba(248, 113, 113, .95);
    }

    .notification-tray__pill {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        margin-bottom: .45rem;
        padding: .18rem .5rem;
        border-radius: 999px;
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .02em;
        text-transform: uppercase;
        color: #fff;
        background: rgba(100, 116, 139, .7);
    }

    .notification-tray__pill--rejected {
        background: linear-gradient(135deg, rgba(239, 68, 68, .95), rgba(244, 63, 94, .9));
    }

    .notification-tray__pill--unread {
        background: linear-gradient(135deg, rgba(14, 165, 233, .95), rgba(59, 130, 246, .9));
    }

    .notification-tray__pill--group {
        background: rgba(148, 163, 184, .22);
        color: #e2e8f0;
    }

    .notification-tray__count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 18px;
        height: 18px;
        margin-left: .35rem;
        padding: 0 .35rem;
        border-radius: 999px;
        font-size: .68rem;
        font-weight: 700;
        background: rgba(255, 255, 255, .14);
        color: #fff;
    }

    .notification-tray__title {
        font-size: .9rem;
        font-weight: 700;
        margin-bottom: .15rem;
    }

    .notification-tray__message {
        font-size: .82rem;
        color: #cbd5e1;
        margin-bottom: .15rem;
        line-height: 1.3;
    }

    .notification-tray__meta {
        font-size: .72rem;
        color: #94a3b8;
    }
</style>
<div class="navbar-custom topnav-navbar-dark">
    <ul class="list-unstyled topbar-menu float-end mb-0">      
        
        <li class="dropdown notification-list">
            <a class="nav-link dropdown-toggle text-white notification-bell-btn" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" id="notificationBellDropdown">
                <i class="mdi mdi-bell-outline fs-4"></i>
                <span class="badge bg-danger rounded-pill notification-bell-badge d-none" id="notificationBellBadge">0</span>
            </a>
            <div class="dropdown-menu dropdown-menu-end dropdown-menu-animated topbar-dropdown-menu notification-tray p-0" aria-labelledby="notificationBellDropdown">
                <div class="px-3 py-2 border-bottom border-secondary">
                    <div class="fw-semibold text-white">Notificaciones</div>
                    <div class="small text-muted" id="notificationTraySubtitle">Cargando...</div>
                    <div class="notification-tray__filters" role="tablist" aria-label="Filtrar notificaciones">
                        <button type="button" class="notification-tray__filter is-active" data-filter="all">
                            <span>Todas</span><span class="notification-tray__count" data-count-for="all">0</span>
                        </button>
                        <button type="button" class="notification-tray__filter" data-filter="unread">
                            <span>Sin leer</span><span class="notification-tray__count" data-count-for="unread">0</span>
                        </button>
                        <button type="button" class="notification-tray__filter" data-filter="rejected">
                            <span>Rechazadas</span><span class="notification-tray__count" data-count-for="rejected">0</span>
                        </button>
                    </div>
                </div>
                <div class="notification-tray__list" id="notificationTrayList">
                    <div class="px-3 py-4 text-muted small">Consultando notificaciones...</div>
                </div>
                <div class="px-3 py-2 border-top border-secondary text-end">
                    <a href="#" class="small text-info text-decoration-none" id="notificationTrayRefresh">Actualizar</a>
                </div>
            </div>
        </li>

        <li class="dropdown notification-list d-lg-none">
            <div id="titulo">
                <h3><?= esc($nombreCompleto) ?></h3>
            </div>
        </li>

        <li class="dropdown notification-list">
            <a class="nav-link dropdown-toggle nav-user arrow-none me-0" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false"
                aria-expanded="false">
                <span class="account-user-avatar"> 
                    <img src="<?php echo base_url();?>/assets/images/user.png"  class="rounded-circle">
                </span>
                <span>
                    <span class="account-user-name"><?= esc($nombreCompleto) ?></span>
                    <!-- <span class="account-position"><?php //echo $session->get('dsc_perfil');?></span> -->
                </span>
            </a>
            <div class="dropdown-menu dropdown-menu-end dropdown-menu-animated topbar-dropdown-menu profile-dropdown">
                
                <!-- item-->
                <!-- <a href="#" onClick="saeg.general.cambiar_foto_perfil();" class="dropdown-item notify-item">
                    <i class="mdi mdi-account-circle me-1"></i>
                    <span>Subir foto de perfil</span>
                </a> -->
                <!-- item-->
                <a href="<?php echo base_url()?>index.php/Login/cerrar" class="dropdown-item notify-item">
                    <i class="mdi mdi-logout me-1"></i>
                    <span>Salir</span>
                </a>

            </div>
        </li>

    </ul>
    <div class="app-search dropdown d-none d-lg-block">

        <div id="titulo">
            <div class="d-flex align-items-center gap-2">
                <img src="<?php echo base_url();?>/assets/images/st4.png" alt="SECTUR/FIC" style="height: 28px; width: 28px; object-fit: contain;">
                <div class="d-flex flex-column lh-1">
                    <span class="text-white fw-semibold" style="font-size: 1rem; letter-spacing: .02em;">SECTUR/FIC</span>
                    <small class="text-muted" style="font-size: .72rem;">Comisión de Alimentos y Hospedajes</small>
                </div>
            </div>
        </div>
        
    </div>
</div>
<!-- end Topbar - -->
<!-- <div id="mdl_subir_foto_perfil" class="modal fade"  aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        <form action="javascript:;" id="frmDocumentoSustituir" method="post" enctype="multipart/form-data">
            <div class="modal-header">
                <h4 class="modal-title" id="standard-modalLabel">Subir foto de perfil</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
            <div class="modal-body">
                <div class="container">
                    <div class="row">    
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">                                
                            <strong>!Atención! - </strong> El nombre del documento no debe contener caracteres raros. ej: #$%¡?#"!"*¨[]
                        </div>                                            
                        <div class="col-md-12">
                                <label for="input_doc_foto">Subir foto de perfil</label>      
                                <div class="file-loading">                      
                                    <input id="input_doc_foto" name="input_doc_foto" type="file" class="file"  data-theme="fas"> 
                                </div>                               
                        </div>                        
                                                                       
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </form>
        </div>
    </div>
</div> -->

<script>        
    $("#input_doc_foto").fileinput({
        language: 'es',
        uploadUrl: base_url + '/index.php/Usuario/SubirFotoPerfil', //SubiendoDocumentoSustituir
        enableResumableUpload: true,
        resumableUploadOptions: {
            // uncomment below if you wish to test the file for previous partial uploaded chunks
            // to the server and resume uploads from that point afterwards
            // testUrl: "http://localhost/test-upload.php"
        },                        
        maxFileCount: 1,
        //allowedFileTypes: ['image'],    // allow only images
        maxFileSize: 1000,
        showCancel: true,
        //initialPreviewAsData: true,
        //overwriteInitial: false,
        // initialPreview: [],          // if you have previously uploaded preview files
        // initialPreviewConfig: [],    // if you have previously uploaded preview files
        //theme: 'fa5',
        //deleteUrl: "http://localhost/file-delete.php",
        allowedFileExtensions: ['jpg','jpeg','png','JPG','PNG']
    }).on('fileuploaded', function(event, previewId, index, fileId) {
        console.log('File Uploaded', 'ID: ' + fileId + ', Thumb ID: ' + previewId);            
    }).on('fileuploaderror', function(event, data, msg) {
        console.log('File Upload Error', 'ID: ' + data.fileId + ', Thumb ID: ' + data.previewId);
    }).on('filebatchuploadcomplete', function(event, preview, config, tags, extraData) {
        console.log('File Batch Uploaded', preview, config, tags, extraData);
        Swal.fire("", "Se subió correctamente su foto de perfil", "success");
        $('#mdl_subir_foto_perfil').modal('hide');
        document.location.reload(true);
        //sass.repositorio.carga_docucumentos_repositorio();
    });
</script><!-- /.modal-dialog -->
