<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Login::index');
$routes->get('Login', 'Login::index');
$routes->get('Login/cerrar', 'Login::cerrar');
$routes->post('Login/validar_usuario', 'Login::validar_usuario');
$routes->get('Inicio', 'Inicio::index');
// $routes->get('pdfTurno/(:num)', 'Inicio::pdfTurno/$1');


