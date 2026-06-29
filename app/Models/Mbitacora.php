<?php namespace App\Models;

use CodeIgniter\Model;
class Mbitacora extends Model {
    protected $DBGroup = 'bitacora';
    
    function __construct() {
        parent::__construct();
    }  
    public function insertTabla($datos = array(), $tabla="bitacora_turnos"){
        $this->db->transStart(); 
        $builder = $this->db->table($tabla);                  
        $builder->insert($datos);
        $id = $this->db->insertID();
        $this->db->transComplete();
        
        if ($this->db->transStatus() === FALSE)
        {
                $last_query = $this->db->getLastQuery();
                $sql = $last_query->getQuery();
                $this->db->transRollback();
                return  $sql;
        }
        else
        {            
            $this->db->transCommit();
            return $id; 
        }
    } 
    
    

}