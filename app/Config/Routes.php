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
$routes->get('Inicio/ProveedorEstablecimiento/(:num)', 'Inicio::ProveedorEstablecimiento/$1');
$routes->get('Inicio/Establecimiento/(:num)', 'Inicio::Establecimiento/$1');
$routes->get('Inicio/PerfilSecturi', 'Inicio::PerfilSecturi');
$routes->get('Inicio/PerfilSecturiConsulta', 'Inicio::PerfilSecturiConsulta');
$routes->get('Inicio/Cajero', 'Inicio::Cajero');
$routes->get('Inicio/ProveedorFormatos', 'Inicio::ProveedorFormatos');
$routes->get('Inicio/FacturasFic', 'Inicio::FacturasFic');
$routes->get('Inicio/getFacturasFic', 'Inicio::getFacturasFic');
$routes->get('Inicio/verFacturaProveedorArchivo', 'Inicio::verFacturaProveedorArchivo');
$routes->get('Inicio/pdfPagoTerceros', 'Inicio::pdfPagoTerceros');
$routes->get('Inicio/pdfLiberacionPago', 'Inicio::pdfLiberacionPago');
$routes->get('Inicio/exportarReporteVentasProveedorXlsx', 'Inicio::exportarReporteVentasProveedorXlsx');
$routes->get('Inicio/getSugerenciasFolioInstitucional', 'Inicio::getSugerenciasFolioInstitucional');
$routes->post('Inicio/actualizarSolicitudNuevoFolioTi', 'Inicio::actualizarSolicitudNuevoFolioTi');
$routes->post('Inicio/guardarPagoSinQrProveedor', 'Inicio::guardarPagoSinQrProveedor');
$routes->post('Inicio/enviarFacturaProveedor', 'Inicio::enviarFacturaProveedor');
$routes->get('Inicio/pdfProveedorEncabezadoFactura/(:num)', 'Inicio::pdfProveedorEncabezadoFactura/$1');
$routes->get('Inicio/pdfProveedorFormatoPT/(:num)', 'Inicio::pdfProveedorFormatoPT/$1');
$routes->get('Inicio/pdfProveedorLiberacionPago/(:num)', 'Inicio::pdfProveedorLiberacionPago/$1');
$routes->post('Inicio/getSolicitudesActivacionQrFic', 'Inicio::getSolicitudesActivacionQrFic');
$routes->post('Inicio/activarQrUsuarioFic', 'Inicio::activarQrUsuarioFic');
$routes->post('Inicio/rechazarActivacionQrUsuarioFic', 'Inicio::rechazarActivacionQrUsuarioFic');
$routes->post('Inicio/activarQrDepositosProgramados', 'Inicio::activarQrDepositosProgramados');
$routes->get('Usuario/exportarCajerosXlsx', 'Usuario::exportarCajerosXlsx');
$routes->get('Usuario/exportarCajerosOrdenDiaXlsx', 'Usuario::exportarCajerosOrdenDiaXlsx');
$routes->post('Usuario/subirIneFirmaCajero', 'Usuario::subirIneFirmaCajero');
// $routes->get('pdfTurno/(:num)', 'Inicio::pdfTurno/$1');


