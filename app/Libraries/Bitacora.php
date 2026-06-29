<?php
namespace App\Libraries;
date_default_timezone_set('America/Mexico_City');// Zona horaria de Mexico
use DateTime;
//ssa_auditoria
use App\Models\Mbitacora;

class Bitacora {

    function GuardaBitacora($action, $table, $field, $keyvalue, $oldvalue, $newvalue) {
        $ci = & get_instance();
        $bitacora = array(
            'datetime' => date('Y-m-d H:i:s'),
            'script' => "app/index.php/ValidacionConsultas",
            'user' => "SISTEMA",
            'action' => $action,
            'table' => $table,
            'field' => $field,
            'keyvalue' => $keyvalue,
            'oldvalue' => $oldvalue,
            'newvalue' => $newvalue
        );
        //$where_talla = "id_consulta = ".$consulta->id_consulta;
        $inserta_bitacora = $ci->Mconexion->InsertaBitacora($bitacora);
        return $inserta_bitacora;
    }

    function GuardaBitacoraValidacion($action, $table, $field, $keyvalue, $oldvalue, $newvalue) {
        $ci = & get_instance();
        $bitacora = array(
            'id_consulta' => $keyvalue,
            'fechahora' => date('Y-m-d H:i:s'),
            'campo' => $field,
            'decia' => $oldvalue,
            'dice' => $newvalue,
            'motivo_cambio' => $action
        );
        //$where_talla = "id_consulta = ".$consulta->id_consulta;
        $inserta_bitacora = $ci->Mconexion->InsertaBitacoraValidacion($bitacora);
        return $inserta_bitacora;
    }

    function RegistraMovimiento($action, $script, $user, $table, $field, $keyvalue, $oldvalue, $newvalue) {
        //$ci = & get_instance();
        $bita = new Mbitacora;
        //$inserted = $users->insertBitacora($data);
        $bitacora = array(
            'datetime' => date('Y-m-d H:i:s'),
            'script' => $script,
            'user' => $user,
            'action' => $action,
            'table' => $table,
            'field' => $field,
            'keyvalue' => $keyvalue,
            'oldvalue' => $oldvalue,
            'newvalue' => $newvalue
        );
        
        $inserta_bitacora = $bita->insertBitacoraSismeg($bitacora);
        return $inserta_bitacora;
    }

}