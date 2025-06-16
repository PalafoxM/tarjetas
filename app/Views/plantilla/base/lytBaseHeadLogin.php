<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>SAC</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Sistema de Administración de Capacitación" name="description" />
    <meta content="SAC" name="author" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="<?php echo base_url(); ?>assets/images/favicon.ico">


   
    <script src="<?= base_url("/js/general.js") ?>"></script>

    <?php if (isset($scripts)) : foreach ($scripts as $js) : ?>
    <script src="<?php echo base_url() . "/js/{$js}.js" ?>?filever=<?php echo time() ?>" type="text/javascript">
    </script>
    <?php endforeach;
    endif; ?>

</head>


<style>
* {
  box-sizing: border-box;
}

html, body {
  background-color: #FEDCC8;
  margin: 0;
  height: 100%;
  overflow: hidden;
}

.parallax {
  perspective: 100px;
  height: 100vh;
  overflow-x: hidden;
  overflow-y: auto;
  position: absolute;
  top: 0;
  left: 50%;
  right: 0;
  bottom: 0;
  margin-left: -1500px;
}

.parallax__layer {
  position: absolute;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
}

.parallax__layer img {
  display: block;
  position: absolute;
  bottom: 0;
  width: 100%;
  left: 0;
}

.parallax__cover {
  background: #2D112B;
  display: block;
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  height: 2000px;
  z-index: 2;
}

.parallax__layer__0 {
  transform: translateZ(-300px) scale(4);
}
.parallax__layer__1 {
  transform: translateZ(-250px) scale(3.5);
}
.parallax__layer__2 {
  transform: translateZ(-200px) scale(3);
}
.parallax__layer__3 {
  transform: translateZ(-150px) scale(2.5);
}
.parallax__layer__4 {
  transform: translateZ(-100px) scale(2);
}
.parallax__layer__5 {
  transform: translateZ(-50px) scale(1.5);
}
.parallax__layer__6 {
  transform: translateZ(0px) scale(1);
}
</style>

<body class="account-body accountbg">
    <script>
    var base_url = "<?php echo base_url(); ?>";

    // Lista de imágenes de fondo
    const backgrounds = [
        "<?php echo base_url(); ?>assets/images/backgrounds/bg1.jpg",
        "<?php echo base_url(); ?>assets/images/backgrounds/bg2.jpg",
        "<?php echo base_url(); ?>assets/images/backgrounds/bg3.jpg",
        "<?php echo base_url(); ?>assets/images/backgrounds/bg4.jpg",
        "<?php echo base_url(); ?>assets/images/backgrounds/bg5.jpg"
    ];

    // Función para seleccionar un fondo aleatorio
    function setRandomBackground() {
        const randomIndex = Math.floor(Math.random() * backgrounds.length);
        const randomBackground = backgrounds[randomIndex];
        document.body.style.backgroundImage = `url('${randomBackground}')`;
    }

    // Cambiar el fondo al cargar la página
    window.onload = setRandomBackground;
    </script>