<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="utf-8" />
        <title>Sistema de Administracion de Capacitación</title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta content="Sistema de Administracion de Capacitación" name="description" />
        <meta content="" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />

        <!-- App favicon -->
        <link rel="shortcut icon" href="<?php echo base_url();?>assets/images/favicon.ico">

        <!-- App css -->
        <link href="<?php echo base_url();?>assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="<?php echo base_url();?>assets/css/jquery-ui.min.css" rel="stylesheet">
        <link href="<?php echo base_url();?>assets/css/icons.min.css" rel="stylesheet" type="text/css" />
        <link href="<?php echo base_url();?>assets/css/metisMenu.min.css" rel="stylesheet" type="text/css" />
        <link href="<?php echo base_url();?>assets/css/app.min.css" rel="stylesheet" type="text/css" />

        <?php if (isset($scripts)): foreach ($scripts as $js): ?>
            <script src="<?php echo base_url() . "/js/{$js}.js" ?>?filever=<?php echo time() ?>" type="text/javascript"></script>
                <?php endforeach;
            endif;
        ?> 

    </head>
    <script>            
            var base_url = "<?php echo base_url();?>";        
    </script> 

    <body>