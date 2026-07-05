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
$routes->get('Inicio/ProveedorFormatos', 'Inicio::ProveedorFormatos');
$routes->post('Inicio/guardarPagoSinQrProveedor', 'Inicio::guardarPagoSinQrProveedor');
$routes->get('Inicio/pdfProveedorEncabezadoFactura/(:num)', 'Inicio::pdfProveedorEncabezadoFactura/$1');
$routes->get('Inicio/pdfProveedorFormatoPT/(:num)', 'Inicio::pdfProveedorFormatoPT/$1');
$routes->get('Inicio/pdfProveedorLiberacionPago/(:num)', 'Inicio::pdfProveedorLiberacionPago/$1');
$routes->post('Inicio/getSolicitudesActivacionQrFic', 'Inicio::getSolicitudesActivacionQrFic');
$routes->post('Inicio/activarQrUsuarioFic', 'Inicio::activarQrUsuarioFic');
$routes->post('Inicio/rechazarActivacionQrUsuarioFic', 'Inicio::rechazarActivacionQrUsuarioFic');
$routes->post('Inicio/activarQrDepositosProgramados', 'Inicio::activarQrDepositosProgramados');
$routes->post('Usuario/subirIneFirmaCajero', 'Usuario::subirIneFirmaCajero');
// $routes->get('pdfTurno/(:num)', 'Inicio::pdfTurno/$1');


