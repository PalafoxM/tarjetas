
<script src="<?= base_url('assets/parallax/libraries.min.js') ?>"></script>
<script src="<?= base_url('assets/parallax/jquery.parallax.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.26.25/dist/sweetalert2.min.js"
        integrity="sha384-hW8ZCQHtRH+nVOAkHZ4amZvYsAtKn1ZOvMV6dNag1Rb1thWmLZMBKTRxFV0cOxiK"
        crossorigin="anonymous"></script>

<script src="<?= base_url('/js/global-loading.js') ?>"></script>
<script src="<?= base_url('js/csrf-session.js') ?>?v=<?= time() ?>"></script>
<script src="<?= base_url('js/fic-login-footer.js') ?>?v=<?= time() ?>"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>

<?php if (isset($scripts)) : foreach ($scripts as $js) : ?>
<script src="<?= base_url("js/{$js}.js") ?>?filever=<?= time() ?>"></script>
<?php endforeach; endif; ?>

<script src="<?= base_url('js/fic-login.js') ?>?v=<?= time() ?>"></script>

    </body>

</html>

