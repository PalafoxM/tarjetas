<?php $session     = \Config\Services::session();?>
<!DOCTYPE html>
<html lang="es">

<head>
        <meta charset="utf-8" />
        <title>SECTUR/FIC</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta content="Sistema de Junta de Gobierno" name="description" />
        <meta content="ISAPEG" name="author" />
        <!-- App favicon -->
        <link rel="shortcut icon" href="<?php echo base_url();?>/assets/images/proyecto/favicon.png">
        
        <link href="<?php echo base_url(); ?>/assets/css/main.css" rel="stylesheet" type="text/css" />
        <!-- third party css -->
        <link href="<?php echo base_url(); ?>/assets/css/vendor/jquery-jvectormap-1.2.2.css" rel="stylesheet" type="text/css" />
        <!-- third party css end -->

        <!-- App css -->
        <link href="<?php echo base_url(); ?>/assets/css/icons.min.css" rel="stylesheet" type="text/css" />
        <link href="<?php echo base_url(); ?>/assets/css/app-creative.min.css" rel="stylesheet" type="text/css" id="light-style" />
        <link href="<?php echo base_url(); ?>/assets/css/app-creative-dark.min.css" rel="stylesheet" type="text/css" id="dark-style" />
        <link rel="stylesheet" href="<?= base_url("/assets/fontawesome_6/css/all.css")?>">
        <link rel="stylesheet" type="text/css" href="<?php echo base_url();?>/assets/fileinput5/css/fileinput.css">
        <link href="<?php echo base_url(); ?>/assets/css/bootstrap-icons.css" rel="stylesheet" type="text/css" />
        <link href="<?php echo base_url();?>/assets/css/custom.css" rel="stylesheet" type="text/css" id="custom-style" />

        <script src="<?php echo base_url("/assets/js/vendor.min.js"); ?>"></script>        

        <script type="text/javascript" src="<?php echo base_url();?>/assets/fileinput5/js/fileinput.js"></script>
        <script type="text/javascript" src="<?php echo base_url();?>/assets/fileinput5/js/locales/es.js"></script>
        <script type="text/javascript" src="<?php echo base_url();?>/assets/fileinput5/themes/fas/theme.js"></script>
        <script type="text/javascript" src="<?php echo base_url();?>/assets/sweetAlert2/sweetalert2.all.min.js"></script>
        
        <!--Bootstrap table-->
        <link href="<?php echo (base_url('/assets/bootstrap-table-master/dist_/bootstrap-table.min.css'));?>" rel="stylesheet">
        <script src="<?php echo base_url('/assets/bootstrap-table-master/dist_/bootstrap-table.min.js');?>"></script>
        <script src="<?php echo base_url('/assets/bootstrap-table-master/dist_/tableExport.min.js');?>"></script>
        <script src="<?php echo base_url('/assets/bootstrap-table-master/dist_/bootstrap-table-locale-all.min.js');?>"></script>
        <script src="<?php echo base_url('/assets/bootstrap-table-master/dist_/extensions/export/bootstrap-table-export.min.js');?>"></script>
        <script src="<?php echo base_url('/assets/js/vendor/drag_select.min.js');?>"></script>
        <script src='<?php echo base_url();?>/assets/js/dragula.min.js'></script>
        <!-- datepicker -->
        <script src="<?php echo base_url('/assets/bootstrap-datepicker-master/js/bootstrap-datepicker.js');?>"></script>
        <script src="<?php echo base_url('/assets/bootstrap-datepicker-master/dist/locales/bootstrap-datepicker.es.min.js');?>"></script>
        <!-- Parsley -->
        <script src="<?= base_url("/assets/parsley_2_9/dist/parsley.js")?>"></script>
        <script src="<?= base_url("/assets/parsley_2_9/dist/i18n/es.js")?>"></script>
        <script src="<?= base_url("/js/general.js")?>"></script>

        <?php if (isset($scripts)): foreach ($scripts as $js): ?>
            <script src="<?php echo base_url() . "/js/{$js}.js" ?>?filever=<?php echo time() ?>" type="text/javascript"></script>
                <?php endforeach;
            endif;
        ?>          

    </head>
<body class="loading" data-layout-color="dark" data-leftbar-theme="dark" data-leftbar-compact-mode="condensed" data-layout-config='{"leftSideBarTheme":"dark","layoutBoxed":false,"leftSidebarCondensed":true,"leftSidebarScrollable":false,"darkMode":true,"showRightSidebarOnStart":false}'>
        <!-- Begin page -->
        <script>            
            var base_url = "<?php echo base_url();?>";          
        </script>          
        <style>
            .parsley-error
            {
            color: #B94A48 !important;
            background-color: #F2DEDE !important;
            border: 1px solid #EED3D7 !important;
            }
            .parsley-errors-list
            {
            color: red
            }
        </style>
        
        <div class="wrapper">
        <?php echo view('secciones/vNavBar'); ?>           
        <?php echo view('secciones/vSideBar'); ?>
        
        <div class="content-page"> 
