<?php
namespace App\Libraries;
date_default_timezone_set('America/Mexico_City');// Zona horaria de Mexico
use DateTime;

class Permisos {    
    /**
     * checkModuloPermiso()
     * Función que verifica los permisos de usuario
     * 
     * @param   int     idModulo
     * @param   string  permiso [ 'add','view','update','delete' ]   (solo una opcion)
     * @param   int     idUsuario (opcional, se toma de la ssesión activa)
     * @param   int     idPerfil (opcional)
     * @return  bool    Access
     */
    public function checkModuloPermiso($idPerfil = false, $idModulo,$permiso,$idUsuario = false )
    {
        $session    = \Config\Services::session(); 
        $client     = \Config\Services::curlrequest();
        $config     = config('AuthConfig');

        if(!$idUsuario) $idUsuario = $session->get('id_usuario');
        if (!$idPerfil) $usuarioData['id_perfil'] = $idPerfil;
        $usuarioData = [
            'id_usuario'   => $idUsuario,
            'permiso'       => [$permiso => 1],
            'id_modulo'    => $idModulo,
        ];
        
        //Consulta Permisos
        $response = $client->request('POST', $config->base_url_cima.'con_usuarios/getPermisoUsuario', [
            'auth' => [$config->auth_user, $config->auth_pass,'basic'],
            'headers' => [$config->auth_token => $config->auth_token_pass],
            'form_params' => ['usuarioData'=>$usuarioData]
        ]);
        $permisoData = json_decode($response->getBody());

        if (isset($response->code) && $response->code != 200)
        return false;
        
        if ($permisoData->error)
        return false;

        if (empty($permisoData->data) || count($permisoData->data)==0)
        return false;
    
        return true;
    }
    
    /**
     * getUsuarioPermiso()
     * Funcion de obtención de todos los permisos asignados al usuario con posibilidad de filtros
     * Nota: Uso recomendado en controladores para evitar llamarlo en cada apartado de la vista
     *
     * @param   int     idUsuario   (opcional, toma el id de la session) 
     * @param   int     idSistema   (opcional)
     * @param   int     idPerfil    (opcional)
     * @param   int     idMdosulo   (opcional)
     * @param   array   permiso     (opcional)
     * permiso array (opcional)
     * [   
     *     'view'    => int (0:inactivo, 1:activo)
     *     'add'     => int (0:inactivo, 1:activo)
     *     'update'  => int (0:inactivo, 1:activo)
     *     'delete'  => int (0:inactivo, 1:activo)
     *     'list'    => int (0:inactivo, 1:activo)
     * ]
     * 
     * @return  bool    
     */
    public function getUsuarioPermiso($idModulo = false ,$permiso = false, $idSistema = false, $idUsuario = false, $idPerfil = false)
    {
        $session    = \Config\Services::session(); 
        $client     = \Config\Services::curlrequest();
        $config     = config('AuthConfig');

        if (!$idUsuario) $idUsuario = $session->get('id_usuario');
        if (!$idSistema) $idSistema = ID_SISTEMA;
        if (!$idPerfil && !is_null($idPerfil)) $idPerfil = is_null($session->get('id_perfil_sistema')) ? $session->get('id_perfiles') : [$session->get('id_perfil_sistema')]  ;
    
        $usuarioData = ['id_usuario'   => $idUsuario ];
        
        if ($idModulo)  $usuarioData['id_modulo']   = $idModulo;
        if ($idSistema) $usuarioData['id_sistema']  = $idSistema;
        if ($idPerfil)  $usuarioData['id_perfil']   = $idPerfil;
        if ($permiso)   $usuarioData['permiso']     = $permiso;

        //Consulta Permisos
        $response = $client->request('POST', $config->base_url_cima.'con_usuarios/getPermisoUsuario', [
            'auth' => [$config->auth_user, $config->auth_pass,'basic'],
            'headers' => [$config->auth_token => $config->auth_token_pass],
            'form_params' => ['usuarioData'=>$usuarioData]
        ]);
        $response = json_decode($response->getBody());

        if(isset($response->code) && $response->code != 200)
        {
            $response->error=true;
            $response->respuesta=$response->message;
            return $response;
        }
    
        return $response;
    }



}
