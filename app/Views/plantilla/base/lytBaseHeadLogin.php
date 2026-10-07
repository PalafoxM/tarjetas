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

    <meta name="csrf-token" content="<?= esc(csrf_hash(), 'attr') ?>">
    <meta name="csrf-header" content="<?= esc(csrf_header(), 'attr') ?>">
    <meta name="csrf-token-name" content="<?= esc(csrf_token(), 'attr') ?>">

    <meta name="fic-login-backgrounds"
          content="<?= esc(json_encode($loginBackgroundUrls), 'attr') ?>">
    <meta name="fic-login-background"
          content="<?= esc($loginDefaultBackgroundUrl, 'attr') ?>">
    <meta name="fic-login-loading-background"
          content="<?= esc($loginLoadingBackgroundUrl, 'attr') ?>">
    <meta name="fic-base-url"
          content="<?= esc(base_url(), 'attr') ?>">

    <meta name="viewport"
          content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">

    <link rel="shortcut icon"
          href="<?= base_url('assets/images/proyecto/favicon.png') ?>"
          type="image/x-icon">
     <link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/sweetalert2@11.26.25/dist/sweetalert2.min.css"
      integrity="sha384-dCW5imOdApH6OwpFau8cZNKjqVbJYnCA5q+8YsMYP3XwXKsV6Jfz1u6MZLnXaBsS"
      crossorigin="anonymous">

    <link rel="stylesheet" href="<?= base_url('css/fic-common.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/parallax/styles.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/fic-login.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/global-loading.css') ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=MedievalSharp&display=swap"
          rel="stylesheet">

    <script src="<?= base_url('js/fic-login-head.js') ?>?v=<?= time() ?>"></script>

    <?= view('plantilla/base/lytGoogleAnalytics') ?>
</head>
<body class="fic-login-body" data-base-url="<?= esc(base_url(), 'attr') ?>">

<div id="globalLoadingOverlay" class="global-loading-overlay" aria-hidden="true">
    <div class="global-loading-overlay__card" role="status" aria-live="polite" aria-busy="true">
        <span class="global-loading-overlay__spinner"></span>
        <strong>Cargando...</strong>
        <span class="global-loading-overlay__text">Preparando la interfaz</span>
    </div>
</div>