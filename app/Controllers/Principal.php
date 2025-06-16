<?php namespace App\Controllers;
use CodeIgniter\Controller;
use App\Libraries\Curps;
use App\Libraries\Fechas;
use App\Libraries\Funciones;
use App\Models\Mglobal;

use stdClass;
use CodeIgniter\API\ResponseTrait;

class Principal extends BaseController {

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
        $data = array_merge($this->defaultData, $data);
        echo view($data['layout'], $data);               
    }
   
    public function index()
    {  
       
        $session = \Config\Services::session();
        $data = array();
        $data['scripts'] = array('principal');
        $data['edita'] = 0;
        $data['contentView'] = 'secciones/vVacio';                
        $this->_renderView($data);
        
    }
    public function uploadCSV()
    {
        $response = new \stdClass();
        $session = \Config\Services::session();
    
        if (isset($_FILES['fileParticipantes']) && $_FILES['fileParticipantes']['error'] == 0) {
            $filePath = $_FILES['fileParticipantes']['tmp_name'];
            
            // Lee el archivo CSV y convierte sus datos en un array
            $data = [];
        
            if (($handle = fopen($filePath, "r")) !== false) {
                $header = fgetcsv($handle, 1000, ","); // Lee la primera fila como encabezado
        
                while (($row = fgetcsv($handle, 1000, ",")) !== false) {
                    $encodedRow = array_map('utf8_encode', $row); // Codifica los valores a UTF-8
                    $courseData = array_combine($header, $encodedRow); // Combina encabezado y valores

                    $data[] = $courseData;
                }
                fclose($handle);
            }

            $columnasRequeridas = [
                'nombre', 'primer_apellido', 'segundo_apellido', 'curp', 'correo',
                'denominacion_funcional', 'nivel', 'municipio',
                 'area', 'jefe_inmediato', 'centro_gestor'
            ];
        
            // Compara las columnas requeridas con el encabezado del archivo CSV
            $columnasFaltantes = array_diff($columnasRequeridas, $header);
        
            if (!empty($columnasFaltantes)) {
                // Si faltan columnas, devolver error con los nombres de las columnas faltantes
                $response->error = true; 
                $response->respuesta = 'faltan columnas'; 
                return $this->respond($response);
            }
        
      

            $processResponse = $this->procesarDatos($data);
            if($processResponse->error){
                $response->error = true;
                $response->respuesta = $processResponse->respuesta;
                return $this->respond($response);
            }
         
            // Convertir el array a JSON (opcional)
          
           // $json_data = json_encode($dataArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            //echo $json_data; // Para ver el JSON resultante
            //var_dump($json_data);
            
        }

     
        $response->error = false; 
        return $this->respond($response);
        //return $this->response->setJSON($response);
    }
    public function procesarDatos($data)
    {
        $response = new \stdClass();
        $session = \Config\Services::session();
        $this->globals = new Mglobal();
        $dataClean = [];
        $dataTrash = [];
        $emailsSeen = []; // Lista para verificar correos duplicados en el CSV
        $curpSeen = []; 
    
        foreach ($data as $d) {
            if (isset($d['curp']) && !empty($d['curp'])) {
    
                // Validación de duplicados de correo en el archivo CSV
                if (in_array($d['correo'], $emailsSeen)) {
                    $response->respuesta = "Existen correos duplicados en el CSV";
                    $response->error = true;
                    return $response;
                } else {
                    $emailsSeen[] = $d['correo']; // Guardar correo para evitar duplicados en el CSV
                }
                if (in_array($d['curp'], $curpSeen)) {
                    $response->respuesta = "Existen CURP duplicados en el CSV";
                    $response->error = true;
                    return $response;
                } else {
                    $curpSeen[] = $d['curp']; // Guardar correo para evitar duplicados en el CSV
                }
               
                $curpDB = $this->globals->getTabla(['tabla' => 'participantes', 'where' => [
                    'visible' => 1,
                    'id_dependencia' => $session->get('id_dependencia'),
                    'curp' => $d['curp']
                ]]);
                $correoDB = $this->globals->getTabla(['tabla' => 'participantes', 'where' => [
                    'visible' => 1,
                    'id_dependencia' => $session->get('id_dependencia'),
                    'correo' => $d['correo']
                ]]);
    
                if (!empty($curpDB->data)) {
                    $d['observaciones'] = 'Curp ya existe en la base de datos';
                    $dataTrash[] = $d;
                    continue;
                }
                if (!empty($correoDB->data)) {
                    $d['observaciones'] = 'Correo ya existe en la base de datos';
                    $dataTrash[] = $d;
                    continue;
                }
                if (!preg_match('/^[^@]+@[^@]+$/', $d['correo'])) {
                    $d['observaciones'] = 'Correo debe contener exactamente un "@" y tener estructura válida';
                    $dataTrash[] = $d;
                    continue;
                }
                
    
                // Validar la CURP en formato y datos
                $result = $this->validarCURP($d['curp']);
                if (is_object($result) && !$result->error) {
                    // Si es válido, añadir la fecha de nacimiento, edad y sexo al registro
                    $d['fecha_nacimiento'] = $result->fecha_nacimiento;
                    $d['edad'] = $result->edad;
                    $d['sexo'] = $result->sexo;
                    $dataClean[] = $d;
                } else {
                    $d['observaciones'] = is_object($result) ? $result->respuesta : 'Error al procesar la CURP';
                    $dataTrash[] = $d;
                }
            } else {
                // CURP vacía
                $d['observaciones'] = 'CURP vacía';
                $dataTrash[] = $d;
            }
        }
    
        // Procesar y guardar los datos limpios y descartados en la base de datos
        $this->guardarEnBaseDeDatos($dataClean, $dataTrash);
    
        // Respuesta final
        $response->error = false;
        return $response;
    }
    private function guardarEnBaseDeDatos($dataClean, $dataTrash)
    {
        $session = \Config\Services::session();
       
        if (!empty($dataTrash)) {
            foreach ($dataTrash as $c) {
                $dataInsert = [
                    'nombre'             => $c['nombre'],
                    'primer_apellido'    => $c['primer_apellido'],
                    'segundo_apellido'   => $c['segundo_apellido'],
                    'curp'               => $c['curp'],
                    'correo'             => $c['correo'],
                   // 'fec_nac'            => date("Y-m-d H:i:s", strtotime($c['fec_nac'])),
                    'centro_gestor'      => $c['centro_gestor'],
                    'jefe_inmediato'     => $c['jefe_inmediato'],
                    'area'               => $c['area'],
                    'rfc'                => substr($c['curp'], 0, 10), 
                    'observaciones'      => $c['observaciones'],
                    //'id_sexo'            => ($c['sexo'] == 'HOMBRE') ? 1 : 2,
                    'id_municipio'       => 15,
                    'id_dependencia'     => (int)$session->get('id_dependencia'),
                    'id_dep_padre'       => (int)$session->get('id_padre'),
                    'id_nivel'           => (int)$c['nivel'],
                    'fec_reg'            => date("Y-m-d H:i:s"),
                    'usu_reg'            => $session->get('id_usuario')
                ];
                $dataBitacora = ['id_user' => $session->get('id_usuario'), 'script' => 'Agregar.php/guardarDetenido'];
                $dataConfig = ["tabla" => "detenidos", "editar" => false];
                $this->globals->saveTabla($dataInsert, $dataConfig, $dataBitacora);
            }
        }
    
        if (!empty($dataClean)) {
            foreach ($dataClean as $c) {
                $dataInsert = [
                    'nombre'             => $c['nombre'],
                    'primer_apellido'    => $c['primer_apellido'],
                    'segundo_apellido'   => $c['segundo_apellido'],
                    'curp'               => $c['curp'],
                    'correo'             => $c['correo'],
                    'fec_nac'            => $c['fecha_nacimiento'],
                    'centro_gestor'      => $c['centro_gestor'],
                    'jefe_inmediato'     => $c['jefe_inmediato'],
                    'area'               => $c['area'],
                    'rfc'                => substr($c['curp'], 0, 10),
                    'edad'               => $c['edad'],
                    'id_sexo'            => ($c['sexo'] == 'H') ? 1 : 2,
                    'id_municipio'       => 15,
                    'id_dependencia'     => (int)$session->get('id_dependencia'),
                    'id_dep_padre'       => (int)$session->get('id_padre'),
                    'id_nivel'           => (int)$c['nivel'],
                    'fec_reg'            => date("Y-m-d H:i:s"),
                    'usu_reg'            => $session->get('id_usuario')
                ];
                $dataBitacora = ['id_user' => $session->get('id_usuario'), 'script' => 'Agregar.php/guardarParticipantes'];
                $dataConfig = ["tabla" => "participantes", "editar" => false];
                $this->globals->saveTabla($dataInsert, $dataConfig, $dataBitacora);
            }
        }
    }
    function validarCURP($curp) {
        // Lista de códigos de entidades válidos en México
        $response = new \stdClass();
        $response->error = true;
        $entidadesValidas = [
            'AS', 'BC', 'BS', 'CC', 'CL', 'CM', 'CS', 'CH', 'DF', 'DG', 'GT', 
            'GR', 'HG', 'JC', 'MC', 'MN', 'MS', 'NT', 'NL', 'OC', 'PL', 'QT', 
            'QR', 'SP', 'SL', 'SR', 'TC', 'TL', 'TS', 'VZ', 'YN', 'ZS'
        ];
        
        // Validación de longitud de 18 caracteres y el formato general
        if (strlen($curp) !== 18 ) {
            $response->respuesta = "CURP no válida por formato general";
            return false; // CURP no válida por formato general
        }
       
        // Validación de fecha de nacimiento en CURP
        $anio = intval(substr($curp, 4, 2));
        $mes = intval(substr($curp, 6, 2));
        $dia = intval(substr($curp, 8, 2));
        
        // Ajustar año para fechas de 1900 a 2099
        $anioCompleto = ($anio < 50) ? 2000 + $anio : 1900 + $anio;
    
        // Verificar si el año de nacimiento es en el futuro
        $anioActual = intval(date('Y'));
        if ($anioCompleto > $anioActual) {
            $anioCompleto -= 100; // Ajustar el año si es en el futuro
        }
    
        if (!checkdate($mes, $dia, $anioCompleto)) {
            $response->respuesta = "CURP no válida por fecha de nacimiento incorrecta";
            return $response; // CURP no válida por fecha de nacimiento incorrecta
        }
    
        // Validación de sexo (posición 11)
        $sexo = $curp[10];
        if ($sexo !== 'H' && $sexo !== 'M') {
            $response->respuesta = "Validación de sexo solo es valido H o M";
            return $response; // CURP no válida por sexo incorrecto
        }
    
        // Validación de entidad de nacimiento (posiciones 12 y 13)
        $entidad = substr($curp, 11, 2);
        if (!in_array($entidad, $entidadesValidas)) {
            $response->respuesta = "CURP no válida por entidad de nacimiento ejemplo GT";
            return $response;// CURP no válida por entidad incorrecta
        }
    
        // Validación de primeras consonantes internas en apellidos y nombre (posiciones 14, 15 y 16)
        $consonantesInternas = substr($curp, 13, 3);
        if (!preg_match("/^[B-DF-HJ-NP-TV-Z]{3}$/", $consonantesInternas)) {
            $response->respuesta = "CURP no válida por consonantes internas incorrectas del apellidos y nombre";
            return $response; // CURP no válida por consonantes internas incorrectas
        }
    
        $ultimosDos = substr($curp, -1);
        if (!ctype_digit($ultimosDos)) {
            $response->respuesta = "los ultimos 1 digitos tiene que ser números entero";
            return $response;; // CURP no válida por consonantes internas incorrectas
        }
    
        // CURP válida - calcular fecha de nacimiento y edad
        $fechaNacimiento = "$anioCompleto-$mes-$dia";
        $timestampNacimiento = strtotime($fechaNacimiento);
        $timestampHoy = time();
        $edad = (int) date('Y', $timestampHoy) - (int) date('Y', $timestampNacimiento);
    
        // Ajuste en caso de que el cumpleaños aún no haya ocurrido en el año actual
        if (date('md', $timestampHoy) < date('md', $timestampNacimiento)) {
            $edad--;
        }
    
        $response->error = false;
        $response->respuesta = "CURP válida";
        $response->fecha_nacimiento = $fechaNacimiento;
        $response->edad = $edad;
        $response->sexo = $sexo;
        return $response;
    }
    public function guardarParticipantes()
    {  
        $session = \Config\Services::session();
        $response = new \stdClass();
        $this->globals = new Mglobal();
        $data = $this->request->getPost();
        
    
        // Validación de campos requeridos
        $result = $this->validarCamposRequeridos($data);
         if($result->error){
             $response->error = true;
             $response->respuesta =  $result->respuesta;
             return $this->respond($response);
         }  
        
     

        // Configuración de bitácora
        $dataBitacora = ['id_user' => $session->id_usuario, 'script' => 'Agregar.php/guardaParticipante'];

        // Verificación de unicidad para CURP y correo
  
        if($data['editar'] == 1 && $data['id_detenido'] != 0 || $data['id_participante'] ==0){
            if (!$this->verificarUnicidad('curp', $data['curp']) || !$this->verificarUnicidad('correo', $data['correo'])) {
                $response->error = true;
                $response->respuesta = !$this->verificarUnicidad('curp', $data['curp']) ? 'La CURP ya existe en la base de datos' : 'El correo ya existe en la base de datos';
                return $this->respond($response);
            }
        }
        
        $hoy = date("Y-m-d H:i:s"); 
        $dataInsert = [
            'curp'                  => $data['curp'],           
            'curp_viejo'            => $data['curp_viejo'],           
            'nombre'                => $data['nombre'],           
            'primer_apellido'       => $data['primer_apellido'],           
            'segundo_apellido'      => $data['segundo_apellido'],           
            'fec_nac'               => date("Y-m-d", strtotime($data['fec_nac'])),   
            'rfc'                   => $data['rfc'],   
            'correo'                => $data['correo'],   
            'id_sexo'               => (int)$data['id_sexo'],   
            'id_nivel'              => (int)$data['id_nivel'],   
            'id_dependencia'        => (int)$session->get('id_dependencia'),   
            'funcion'               => $data['funcion'],   
            'denominacion_funcional'=> $data['denominacion_funcional'],   
            'area'                  => $data['area'],   
            'jefe_inmediato'        => $data['jefe_inmediato'],   
            'id_municipio'          => (int)$data['id_municipio'],   
            'centro_gestor'         => $data['centro_gestor'],   
            'correo_enlace'        => $data['correo_enlace'],   
            'id_dep_padre'          => $session->get('id_dependencia'),
            'usu_reg'               => $session->get('id_usuario'),
            'fec_reg'               => $hoy   
        ];
     

     
       //agregar nuevo
        if($data['editar'] == 0 && $data['id_participante'] == 0){
            $dataConfig = [
                "tabla" => "participantes",
                "editar" => false,
               // "idEditar" => ['id_participante' => $data['id_participante']]
            ];
        }
        //editar participante
        if($data['editar'] == 1 && $data['id_participante'] != 0){
            $dataConfig = [
                "tabla" => "participantes",
                "editar" => true,
                "idEditar" => ['id_participante' => $data['id_participante']]
            ];
        }
        //editar detenido
        if($data['editar'] == 1 && $data['id_detenido'] != 0){
            $dataConfig = [
                "tabla" => "participantes",
                "editar" => false,
               // "idEditar" => ['id_participante' => $data['id_participante']]
            ];
        }

       
        $response = $this->globals->saveTabla($dataInsert, $dataConfig, $dataBitacora);
      
        // Si es una edición, marcar al participante en detenidos como inactivo
        if ($data['editar'] == 1 && $data['id_detenido'] != 0) {
            $dataConfigDetenidos = ["tabla" => "detenidos", "editar" => true, "idEditar" => ['id_detenido' => $data['id_detenido']]];
            $dataDetenidos = ['visible' => 0];
            $result = $this->globals->saveTabla($dataDetenidos, $dataConfigDetenidos, $dataBitacora);
            $response->error = $result->error;
            $response->respuesta = $result->respuesta;
        }
        // ahora insertamos en la de participante 
      

        return $this->respond($response);
    }
    private function verificarUnicidad($campo, $valor)
    {
        $session = \Config\Services::session();
        $registro = $this->globals->getTabla(['tabla' => 'participantes', 'where' => ['visible' => 1, $campo => $valor, 'id_dependencia' => $session->get('id_dependencia'), ]]);
        return empty($registro->data);
    }
    private function validarCamposRequeridos($data)
    {
       
        $response = new \stdClass();
        $response->error = false;
        $response->respuesta = 'campos requeridos';
        if(empty($data['curp'])){
            $response->error = true;
            $response->respuesta = 'El campo curp es requerido';
        }
        if(empty($data['correo'])){
            $response->error = true;
            $response->respuesta = 'El campo correo es requerido';
        }
        if(empty($data['fec_nac'])){
            $response->error = true;
            $response->respuesta = 'El campo fecha de nacimiento es requerido';
        }
        if(empty($data['primer_apellido'])){
            $response->error = true;
            $response->respuesta = 'El campo primer apellido es requerido';
        }
        if(empty($data['id_municipio'])){
            $response->error = true;
            $response->respuesta = 'El campo municipio es requerido';
        }
        if(empty($data['correo_enlace'])){
            $response->error = true;
            $response->respuesta = 'El campo correo del enlace es requerido';
        }
        if($data['id_nivel'] == 'Seleccione'){
            $response->error = true;
            $response->respuesta = 'El campo nivel es requerido';
        }
        if(empty($data['id_sexo'])){
            $response->error = true;
            $response->respuesta = 'El campo sexo es requerido';
        }
        return $response;
    }
    public function crearEvento()
    {
        $session = \Config\Services::session();
        $globals = new Mglobal;
        $response = new \stdClass();
        $vw = $Mglobal->getTabla(['tabla' => 'vw_estudiante_curso','where' => ['visible' => 1, 'id_dependencia' => $session->id_dependencia]
        ])->data;
        if($session->id_perfil === 1){
            $dataConfig = ["tabla"=>"estudiante_curso", "editar"=>true,"idEditar"=>['id_dependencia'=>$session->id_dependencia]
            ];
        }
        if($session->get('id_perfil') === 4){
            $cursoPadre  = $globals->getTabla(['tabla' => 'cursos_perfil', 'where' => ['visible' => 1, 'id_padre' => 4]]);
        }
        $dataBitacora = ['id_user' =>  $session->id_usuario, 'script' => 'Agregar.php/guardaCurso'];
        $dataConfig = [
            "tabla"=>"categoria",
            "editar"=>false,
            // "idEditar"=>['id_usuario'=>$data['id_usuario']]
        ];
        $Insert = [
            'dsc_categoria'  => $response->data[0]->name,                      
            'id_moodle_categoria'      => $response->data[0]->id,                      
            'fec_reg'        => $hoy   
        ];
       $response = $this->globals->saveTabla($Insert,$dataConfig,$dataBitacora);
        die();

    }
    public function Matricular()
    {  
       
        $session = \Config\Services::session();
        $globals = new Mglobal;
        $cursos = [];
        if($session->get('id_perfil') === 1){
            $cursoPadre  = $globals->getTabla(['tabla' => 'cursos_perfil', 'where' => ['visible' => 1]]);
        }
        if($session->get('id_perfil') === 4){
            $cursoPadre  = $globals->getTabla(['tabla' => 'cursos_perfil', 'where' => ['visible' => 1, 'id_padre' => 4]]);
        }
        if($session->get('id_perfil') >= 5){
            $cursoPadre  = $globals->getTabla(['tabla' => 'cursos_perfil', 'where' => ['visible' => 1, 'id_padre' => $session->get('id_padre')]]);
        }


        if(isset($cursoPadre->data) && !empty($cursoPadre->data)){
            $id_cursos= $cursoPadre->data;
            foreach ($id_cursos as $key) {
                $data = ['courseId' => $key->id_curso];
                $details = $globals->createCurso($data, 'getCourseDetailsById');
            
                if (isset($details->data) && !empty($details->data)) {
                    $cursos[] = [
                        'id' => $details->data[0]->id,
                        'shortname' => $details->data[0]->shortname,
                        'fullname' => $details->data[0]->fullname,
                        'startdate' => date('d-m-Y', $details->data[0]->startdate),
                        'enddate' => date('d-m-Y', $details->data[0]->enddate),
                    ];
                }
            }
        }

        if ($session->get('id_perfil') >= 5) {
            $participantes = $globals->getTabla(['tabla' => 'participantes', 'where' => ['visible' => 1, 'id_dependencia' => $session->get('id_dependencia')]]);
        } 
        if ($session->get('id_perfil') == 4 || $session->get('id_perfil') == 4){
            $participantes = $globals->getTabla(['tabla' => 'participantes', 'where' => ['visible' => 1, 'id_dep_padre'  => $session->get('id_perfil')]]);
        }
        if ($session->get('id_perfil') == 1){
            $participantes = $globals->getTabla(['tabla' => 'participantes', 'where' => ['visible' => 1]]);
        }

      
    
            // Add full name to each filtered $detenidos record
            foreach ($participantes->data as $d) {
                $d->nombre_completo = $d->nombre . ' ' . $d->primer_apellido . ' ' . $d->segundo_apellido;
            }
        
        //die( var_dump( $participantes ) );
     
        $data['cursos'] = $cursos;
        $data['participantes'] = (!empty($participantes->data))?$participantes->data:'';
        $data['scripts'] = array('principal');
        $data['edita'] = 0;
        $data['contentView'] = 'secciones/vMatricular';                
        $this->_renderView($data);
        
    }
  
}