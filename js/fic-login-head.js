(function () {
    'use strict';

    function readMeta(name) {
        var element = document.querySelector('meta[name="' + name + '"]');
        return element ? element.content : '';
    }

    window.base_url = readMeta('fic-base-url').replace(/\/?$/, '/');

    var backgrounds = [];

    try {
        backgrounds = JSON.parse(
            readMeta('fic-login-backgrounds') || '[]'
        );
    } catch (error) {
        backgrounds = [];
    }

    var currentBackground = readMeta('fic-login-background');
    var loadingBackground = readMeta('fic-login-loading-background');

    if (Array.isArray(backgrounds) && backgrounds.length > 0) {
        currentBackground = backgrounds[
            Math.floor(Math.random() * backgrounds.length)
        ];
    }

    function cssUrl(value) {
        return 'url(' + JSON.stringify(value) + ')';
    }

    document.documentElement.style.setProperty(
        '--fic-login-main-bg',
        cssUrl(currentBackground)
    );

    document.documentElement.style.setProperty(
        '--fic-login-loading-bg',
        cssUrl(loadingBackground)
    );
})();