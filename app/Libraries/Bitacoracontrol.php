<?php
namespace App\Libraries;
use App\Models\Mbitacora;
date_default_timezone_set('America/Mexico_City');// Zona horaria de Mexico
use DateTime;

class Bitacoracontrol {
    function RegistraMovimiento($action, $script, $user, $table, $field, $keyvalue, $oldvalue, $newvalue) {        
        $session = \Config\Services::session();      
        $config = config('AuthConfig');
        $client = \Config\Services::curlrequest(); 
        $Mbitacora = new Mbitacora;

        $bitacora = array(
            'datetime' => date('Y-m-d H:i:s'),
            'script' => $script,
            'user' => $user,
            'action' => $action,
            'table' => $table,
            'field' => $field,
            'keyvalue' => $keyvalue,
            'oldvalue' => $oldvalue,
            'newvalue' => $newvalue,
            // 'tipo' => 'inserta_tabla',
            // 'tabla' => 'bitacora_control'
        );
        
        $inserted = $Mbitacora->insertTabla($bitacora,'bitacora_turnos');      
        if ($inserted == -1)
            return $this->respond(false);      
        return $inserted;
    }
    
    /**
     * Funcion que guarda en bitacora el desglose de un insert que ya se ejecuto y resulto exitoso.
     * Desglosa cada campo en una fila de bitacora para tener todo el respaldo de lo que se guardo
     * @param array $arreglo El arreglo que contiene los datos que se insertaron
     * @param string $controladorCI El nombre del controlador de CodeIgniter que ejecuto la insercion
     * @param int $usuario El ID de usuario que esta ejecutando la insercion
     * @param string $tabla El nombre de la tabla sobre la cual se hizo el insert
     * @param int $llavePrimaria El ID numerico del registro recien insertado sobre la tabla
     */
    function RegistraInsert($arreglo, $controladorCI, $usuario, $tabla, $llavePrimaria) {
        $estatus = true;
        foreach (array_keys($arreglo) as $nombreCampo) {
            $newvalue = $arreglo[$nombreCampo];
            if (!$this->RegistraMovimiento("A", $controladorCI, $usuario, $tabla, $nombreCampo, $llavePrimaria, "", $newvalue))
            $estatus = false;
        }
        return $estatus;
    }
    
    /**
     * Funcion que guarda en bitacora el desglose de un update que ya se ejecuto y resulto exitoso.
     * Desglosa cada campo en una fila de bitacora para tener todo el respaldo de lo que se guardo
     * @param type $arregloNuevo El arreglo que contiene los datos que se actualizaron
     * @param type $arregloViejo El arreglo que contiene los datos antes de que se actualizaran
     * @param type $controladorCI El nombre del controlador de CodeIgniter que ejecuto la insercion
     * @param type $usuario El ID de usuario que esta ejecutando la insercion
     * @param type $tabla El nombre de la tabla sobre la cual se hizo el insert
     * @param type $llavePrimaria El ID numerico del registro actualizado sobre la tabla
     */
    function RegistraUpdate($arregloNuevo, $controladorCI, $usuario, $tabla, $llavePrimaria) {
        $estatus = true;
        foreach (array_keys($arregloNuevo) as $nombreCampo) {
            $newvalue = $arregloNuevo[$nombreCampo];
            //$oldvalue = $arregloViejo[$nombreCampo];
            if (!$sql = $this->RegistraMovimiento("U", $controladorCI, $usuario, $tabla, $nombreCampo, $llavePrimaria, "", $newvalue))
            $estatus = false;
        }
        return $sql;
    }
    
}