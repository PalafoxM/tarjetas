<!-- <!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>SUSI</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Sistema de Administración de Capacitación" name="description" />
    <meta content="Agustin Palafox Marin" name="author" />
    <meta name="developer" content="palafox.marin@hotmail.com">
  
    <link rel="shortcut icon" href="<?php echo base_url(); ?>assets/huella.png">

    <link href="<?php echo base_url(); ?>plugins/sweet-alert2/sweetalert2.min.css" rel="stylesheet" type="text/css">
    <link href="<?php echo base_url(); ?>plugins/animate/animate.css" rel="stylesheet" type="text/css">


    <link href="<?php echo base_url(); ?>assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="<?php echo base_url(); ?>assets/css/jquery-ui.min.css" rel="stylesheet">
    <link href="<?php echo base_url(); ?>assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="<?php echo base_url(); ?>assets/css/metisMenu.min.css" rel="stylesheet" type="text/css" />
    <link href="<?php echo base_url(); ?>assets/css/app.min.css" rel="stylesheet" type="text/css" />

   
    <script src="<?= base_url("js/general.js") ?>"></script>

    <?php if (isset($scripts)) : foreach ($scripts as $js) : ?>
    <script src="<?php echo base_url() . "js/{$js}.js" ?>?filever=<?php echo time() ?>" type="text/javascript">
    </script>
    <?php endforeach;
    endif; ?>

    <style>
    /* Asegúrate de que el cuerpo de la página cubra toda la pantalla */
    body {
        margin: 0;
        padding: 0;
        height: 100vh;
        background-size: cover;
        background-position: center;
        transition: background-image 1s ease-in-out;
       /* cursor: url('<?= base_url() ?>assets/puntero2.png') 0 0, auto;*/
    }

    #particles-js {
        position: absolute;
        width: 100%;
        height: 100%;
    }
    /* ===== Fireflies (compiled to plain CSS) ===== */
body{background:grey;overflow:hidden;}







/* (Opcional) si quieres ocultar partículas cuando sea bosque */
body.forest-bg #particles-js { display: none; }
    </style>
</head>

<body class="account-body accountbg">
 <div id="particles-js"></div> 

<script>
  var base_url = "<?php echo base_url(); ?>";

  const FOREST_URL = "https://i.pinimg.com/originals/44/6e/3b/446e3b79395a287ca32f7977dd83b290.jpg";

  const backgrounds = [
    base_url + "assets/images/backgrounds/img1.JPG",
    base_url + "assets/images/backgrounds/img2.JPG",
    //base_url + "assets/images/backgrounds/uni_ia.jpg",
    base_url + "assets/images/backgrounds/img3.JPG",
    base_url + "assets/images/backgrounds/img_9.JPG",
    base_url + "assets/images/backgrounds/img_5.JPG",
    base_url + "assets/images/backgrounds/img_6.JPG",
    base_url + "assets/images/backgrounds/img_7.JPG",
    base_url + "assets/images/backgrounds/img_8.JPG",
    base_url + "assets/images/backgrounds/IMG_45.JPG",
    base_url + "assets/images/backgrounds/img_16.jpg",
    //FOREST_URL,
  ];

  function setRandomBackground() {
    const chosen = backgrounds[Math.floor(Math.random() * backgrounds.length)];

    // pinta fondo
    document.body.style.backgroundImage = `url('${chosen}')`;
    document.body.style.backgroundSize = (window.innerWidth < 768) ? "auto 100%" : "cover";
    document.body.style.backgroundPosition = "center";
    document.body.style.backgroundRepeat = "no-repeat";

    // marca si es bosque (esto activa las luciérnagas vía CSS)
    const isForest = (chosen === FOREST_URL);
    document.body.classList.toggle('forest-bg', isForest);
  }

  window.addEventListener('load', setRandomBackground);
</script>-->
<!DOCTYPE html>
<?php
$loginDefaultBackgroundUrl = base_url('images/fondo_susi.png');
$loginLoadingBackgroundUrl = base_url('images/fondo_susi.png');
$loginBackgroundUrls = [$loginDefaultBackgroundUrl];
?>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>SECTURI/FIC</title>
    <meta name="csrf-token" content="<?= esc(csrf_hash(), 'attr'); ?>">
    <meta name="csrf-header" content="<?= esc(csrf_header(), 'attr'); ?>">
    <meta name="csrf-token-name" content="<?= esc(csrf_token(), 'attr'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <link rel="shortcut icon" href="<?= base_url(); ?>assets/images/favicon.ico" type="image/x-icon">

    <link rel="stylesheet" type="text/css" href="<?= base_url('css/fic-common.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= base_url('parallax.js_files/styles.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= base_url('css/fic-login.css') ?>">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=MedievalSharp&display=swap" rel="stylesheet">
    <script>
        (function () {
            var mainBackgrounds = <?= json_encode($loginBackgroundUrls) ?>;
            var currentBackground = <?= json_encode($loginDefaultBackgroundUrl) ?>;
            var loadingBackground = <?= json_encode($loginLoadingBackgroundUrl) ?>;

            if (Array.isArray(mainBackgrounds) && mainBackgrounds.length > 0) {
                currentBackground = mainBackgrounds[Math.floor(Math.random() * mainBackgrounds.length)];
            }

            document.documentElement.style.setProperty('--fic-login-loading-bg', 'url("' + loadingBackground.replace(/"/g, '\\"') + '")');
            document.documentElement.style.setProperty('--fic-login-main-bg', 'url("' + currentBackground.replace(/"/g, '\\"') + '")');
        })();
    </script>
    <?php if (isset($scripts)) : foreach ($scripts as $js) : ?>
    <script src="<?= base_url("js/{$js}.js") ?>?filever=<?= time() ?>" type="text/javascript"></script>
    <?php endforeach; endif; ?>
</head>
<body class="fic-login-body" data-base-url="<?= esc(base_url(), 'attr') ?>">
<script>
    var base_url = "<?= base_url(); ?>";
</script>
