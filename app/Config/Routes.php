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
$routes->get('Inicio/EstablecimientosFic', 'Inicio::EstablecimientosFic');
$routes->get('Inicio/Establecimiento/(:num)', 'Inicio::Establecimiento/$1');
$routes->get('Inicio/PerfilSecturi', 'Inicio::PerfilSecturi');
$routes->get('Inicio/PerfilSecturiConsulta', 'Inicio::PerfilSecturiConsulta');
$routes->get('Inicio/PerfilFic', 'Inicio::PerfilFic');
$routes->get('Inicio/PerfilFicConsulta', 'Inicio::PerfilFicConsulta');
$routes->get('Inicio/PerfilSecul', 'Inicio::PerfilSecul');
$routes->get('Inicio/PerfilSeculConsulta', 'Inicio::PerfilSeculConsulta');
$routes->get('Inicio/PerfilUg', 'Inicio::PerfilUg');
$routes->get('Inicio/PerfilUgConsulta', 'Inicio::PerfilUgConsulta');
$routes->get('Inicio/Cajero', 'Inicio::Cajero');
$routes->get('Inicio/SolicitudAlta', 'Inicio::SolicitudAlta');
$routes->get('Inicio/SolicitudAlta/(:segment)', 'Inicio::SolicitudAlta/$1');
$routes->get('Inicio/getDashboardPartidasFic', 'Inicio::getDashboardPartidasFic');
$routes->get('Inicio/ProveedorFormatos', 'Inicio::ProveedorFormatos');
$routes->get('Inicio/getEstablecimientosProveedor', 'Inicio::getEstablecimientosProveedor');
$routes->post('Inicio/guardarSolicitudUsuarioProveedor', 'Inicio::guardarSolicitudUsuarioProveedor');
$routes->get('Inicio/PartidasFic', 'Inicio::PartidasFic');
$routes->get('Inicio/PagosFic', 'Inicio::PagosFic');
$routes->get('Inicio/FacturasFic', 'Inicio::FacturasFic');
$routes->get('Inicio/SolicitudesUsuarioFic', 'Inicio::SolicitudesUsuarioFic');
$routes->get('Inicio/ReportesInstitucionales', 'Inicio::ReportesInstitucionales');
$routes->get('Inicio/exportarReporteInstitucionalSaldosPdf/(:segment)', 'Inicio::exportarReporteInstitucionalSaldosPdf/$1');
$routes->get('Inicio/getFacturasFic', 'Inicio::getFacturasFic');
$routes->get('Inicio/verFacturaProveedorArchivo', 'Inicio::verFacturaProveedorArchivo');
$routes->get('Inicio/pdfPagoTerceros', 'Inicio::pdfPagoTerceros');
$routes->get('Inicio/pdfLiberacionPago', 'Inicio::pdfLiberacionPago');
$routes->get('Inicio/exportarReporteVentasProveedorXlsx', 'Inicio::exportarReporteVentasProveedorXlsx');
$routes->get('Inicio/exportarReporteVentasProveedorPdf', 'Inicio::exportarReporteVentasProveedorPdf');
$routes->get('Inicio/exportarReporteVentasProveedorPdfFormato', 'Inicio::exportarReporteVentasProveedorPdfFormato');
$routes->get('Usuario/getCatalogosCrud', 'Usuario::getCatalogosCrud');
$routes->get('Inicio/getSugerenciasFolioInstitucional', 'Inicio::getSugerenciasFolioInstitucional');
$routes->get('Inicio/getSolicitudFolioEditable', 'Inicio::getSolicitudFolioEditable');
$routes->get('Inicio/getSolicitudesUsuarioFicPerfil', 'Inicio::getSolicitudesUsuarioFicPerfil');
$routes->get('Inicio/getSolicitudUsuarioFicPerfil', 'Inicio::getSolicitudUsuarioFicPerfil');
$routes->post('Inicio/guardarSolicitudUsuarioFicPerfil', 'Inicio::guardarSolicitudUsuarioFicPerfil');
$routes->post('Inicio/cancelarSolicitudUsuarioFicPerfil', 'Inicio::cancelarSolicitudUsuarioFicPerfil');
$routes->get('Inicio/getSolicitudesUsuarioSeculPerfil', 'Inicio::getSolicitudesUsuarioSeculPerfil');
$routes->get('Inicio/getSolicitudUsuarioSeculPerfil', 'Inicio::getSolicitudUsuarioSeculPerfil');
$routes->post('Inicio/guardarSolicitudUsuarioSeculPerfil', 'Inicio::guardarSolicitudUsuarioSeculPerfil');
$routes->post('Inicio/cancelarSolicitudUsuarioSeculPerfil', 'Inicio::cancelarSolicitudUsuarioSeculPerfil');
$routes->get('Inicio/getSolicitudesUsuarioUgPerfil', 'Inicio::getSolicitudesUsuarioUgPerfil');
$routes->get('Inicio/getSolicitudUsuarioUgPerfil', 'Inicio::getSolicitudUsuarioUgPerfil');
$routes->post('Inicio/guardarSolicitudUsuarioUgPerfil', 'Inicio::guardarSolicitudUsuarioUgPerfil');
$routes->post('Inicio/cancelarSolicitudUsuarioUgPerfil', 'Inicio::cancelarSolicitudUsuarioUgPerfil');
$routes->get('Inicio/getNotificacionesUsuario', 'Inicio::getNotificacionesUsuario');
$routes->get('Inicio/getNotificacionesNoLeidas', 'Inicio::getNotificacionesNoLeidas');
$routes->get('Inicio/marcarNotificacionLeida', 'Inicio::marcarNotificacionLeida');
$routes->post('Inicio/marcarNotificacionLeida', 'Inicio::marcarNotificacionLeida');
$routes->get('Inicio/resolverUrlEdicionSolicitud', 'Inicio::resolverUrlEdicionSolicitud');
$routes->post('Inicio/resolverUrlEdicionSolicitud', 'Inicio::resolverUrlEdicionSolicitud');
$routes->post('Inicio/aprobarSolicitudNuevoFolioTi', 'Inicio::aprobarSolicitudNuevoFolioTi');
$routes->post('Inicio/actualizarSolicitudNuevoFolioTi', 'Inicio::actualizarSolicitudNuevoFolioTi');
$routes->post('Inicio/rechazarSolicitudNuevoFolioTi', 'Inicio::rechazarSolicitudNuevoFolioTi');
$routes->post('Inicio/guardarPagoSinQrProveedor', 'Inicio::guardarPagoSinQrProveedor');
$routes->post('Inicio/enviarFacturaProveedor', 'Inicio::enviarFacturaProveedor');
$routes->post('Inicio/subirReporteProveedor', 'Inicio::subirReporteProveedor');
$routes->get('Inicio/pdfProveedorEncabezadoFactura/(:num)', 'Inicio::pdfProveedorEncabezadoFactura/$1');
$routes->get('Inicio/pdfProveedorFormatoPT/(:num)', 'Inicio::pdfProveedorFormatoPT/$1');
$routes->get('Inicio/pdfProveedorLiberacionPago/(:num)', 'Inicio::pdfProveedorLiberacionPago/$1');
$routes->post('Inicio/getSolicitudesActivacionQrFic', 'Inicio::getSolicitudesActivacionQrFic');
$routes->post('Inicio/activarQrUsuarioFic', 'Inicio::activarQrUsuarioFic');
$routes->post('Inicio/rechazarActivacionQrUsuarioFic', 'Inicio::rechazarActivacionQrUsuarioFic');
$routes->get('Inicio/verArchivoSolicitudQrFic', 'Inicio::verArchivoSolicitudQrFic');
$routes->get('Inicio/getSolicitudesUsuarioOperativo', 'Inicio::getSolicitudesUsuarioOperativo');
$routes->get('Inicio/getSolicitudUsuarioOperativo', 'Inicio::getSolicitudUsuarioOperativo');
$routes->get('Inicio/getSolicitudUsuarioOperativo/(:num)', 'Inicio::getSolicitudUsuarioOperativo/$1');
$routes->post('Inicio/aprobarSolicitudUsuarioOperativo', 'Inicio::aprobarSolicitudUsuarioOperativo');
$routes->post('Inicio/rechazarSolicitudUsuarioOperativo', 'Inicio::rechazarSolicitudUsuarioOperativo');
$routes->post('Inicio/activarQrDepositosProgramados', 'Inicio::activarQrDepositosProgramados');
$routes->get('Usuario/exportarCajerosXlsx', 'Usuario::exportarCajerosXlsx');
$routes->get('Usuario/exportarCajerosOrdenDiaXlsx', 'Usuario::exportarCajerosOrdenDiaXlsx');
$routes->post('Usuario/subirIneFirmaCajero', 'Usuario::subirIneFirmaCajero');
// $routes->get('pdfTurno/(:num)', 'Inicio::pdfTurno/$1');


