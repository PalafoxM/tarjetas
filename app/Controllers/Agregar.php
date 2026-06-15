<?php namespace App\Controllers;
use CodeIgniter\Controller;
use App\Libraries\Curps;
use App\Libraries\Fechas;
use App\Libraries\Funciones;
use App\Models\Mglobal;
use App\Models\Magregarturno;


use stdClass;
use Exception;
use CodeIgniter\API\ResponseTrait;

class Agregar extends BaseController {

    use ResponseTrait;
    private $defaultData = array(
        'title' => 'Turnos 2.0',
        'layout' => 'plantilla/lytDefault',
        'contentView' => 'vUndefined',
        'stylecss' => '',
    );
    public function __construct()
    {
        setlocale(LC_TIME, 'es_ES.utf8', 'es_MX.UTF-8', 'es_MX', 'esp_esp', 'Spanish'); // usar solo LC_TIME para evitar que los decimales los separe con coma en lugar de punto y fallen los inserts de peso y talla
        date_default_timezone_set('America/Mexico_City');  
        $session = \Config\Services::session();
        if($session->get('logueado')!= 1){
            header('Location:'.base_url().'index.php/Login/cerrar?inactividad=1');            
            die();
        }
    }

    private function _renderView($data = array()) {
        $session = \Config\Services::session();
        $Mglobal = new Mglobal;   
        $misCursos = $Mglobal->getTabla(['tabla' => 'vw_estudiante_curso', 'where' => ['visible' => 1, 'id_usuario' => $session->id_usuario ]]);
        $data["dscCursos"] = []; // Inicializamos como un arreglo vacío

        if (isset($misCursos->data) && !empty($misCursos->data)) {
            foreach ($misCursos->data as $c) {
                // Obtener la información del curso
                $miCurso = $Mglobal->getTabla([
                    'tabla' => 'cursos_sac', 
                    'where' => [
                        'visible' => 1, 
                        'id_cursos_sac' => $c->id_curso 
                    ]
                ]);
                if (isset($miCurso->data) && !empty($miCurso->data)) {
                    // Agregar los datos del curso al arreglo
                    $data["dscCursos"][] = [
                        'dsc_curso' => $miCurso->data[0]->dsc_curso,
                        'img'       => $miCurso->data[0]->img_ruta,
                        'id'        => $miCurso->data[0]->id_cursos_sac,
                        'periodo'   => $c->id_periodo
                    ];
                }
            }
        }   
        $data = array_merge($this->defaultData, $data);
        echo view($data['layout'], $data);               
    }

  
    public function index()
    {
        $session = \Config\Services::session();
        $data = array();
        $catalogos = new Mglobal;

        $tables = array(
            'cat_asuntos' => 'id_asunto, dsc_asunto',
            'cat_destinatario' => 'id_destinatario, nombre_destinatario, cargo, id_tipo_cargo',
            'cat_indicaciones' => 'id_indicacion, dsc_indicacion',
            'cat_estatus' => 'id_estatus, dsc_status',
            'cat_resultado_turno' => 'id_resultado_turno, descripcion',
        );

        foreach ($tables as $table => $select ) {
            try {
                if ($table == 'cat_destinatario'){
                    $dataDB = array('select' => $select, 'tabla' => $table, 'where' => 'visible = 1 ORDER BY id_tipo_cargo ASC');
                    $response = $catalogos->getTabla($dataDB); 
                }
                $dataDB = array('select' => $select, 'tabla' => $table, 'where' => 'visible = 1');
                $response = $catalogos->getTabla($dataDB);

                if (isset($response) && isset($response->data)) {
                    $data[$table] = $response->data;

                    // Filtrar datos según criterios
                    switch ($table) {
                        case 'cat_destinatario':
                            $data['turnado'] = $response->data; //tambien se ocupa esta variable para llenar el select con copia
                            // $data['cppNombre']
                            // $data['cppNombre'] = array_filter($response->data, function($elemento) {
                            //     return $elemento->id_tipo_cargo == '1';
                            // });
                            $data['firmaTurno'] = array_filter($response->data, function($elemento) {
                                return $elemento->id_tipo_cargo == '9';
                            });
                            break;
                    }
                } else {
                    $data[$table] = array();
                }
            } catch (\Exception $e) {
                $this->handleException($e);
            }
        }
        // var_dump($data['cat_destinatario']);
        // die();

            $data['scripts'] = array('principal','agregar');
            $data['edita'] = 0;
            $data['nombre_completo'] = $session->nombre_completo; 
            $data['contentView'] = 'formularios/vFormAgregar';                
            $this->_renderView($data);
    }

    public function vistaCajero()
    {
        $session = \Config\Services::session();
        $idPerfil = $session->id_perfil;
        if(!in_array($idPerfil, [1, 6])){ // Solo permitir acceso a perfiles admin y cajero
            return $this->failUnauthorized('Acceso denegado');
        }

        $data['scripts'] = array('principal','agregar');
        $data['edita'] = 0;
        $data['nombre_completo'] = $session->nombre_completo; 
        $data['contentView'] = 'secciones/vCajero';                
        $this->_renderView($data);


    }
  
}