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
    public function cleanDetenidos()
    {
        $session = \Config\Services::session();
        $response = new \stdClass();
        $response->error = true;
        $response->respuesta = "Error|Error al guardar en la base de datos";
        $this->globals = new Mglobal();
        $dataInsert = [
            'visible' => 0,           
        ];
        $dataBitacora = ['id_user' => $session->id_usuario, 'script' => 'Agregar.php/guardaTurno'];
        $dataConfig = [
            "tabla"=>"detenidos",
            "editar"=>true,
            "idEditar"=>['id_dependencia'=>$session->id_dependencia]
        ];

        $result = $this->globals->saveTabla($dataInsert,$dataConfig,$dataBitacora);
        if(!$result->error){
            $response->error     = $result->error;
            $response->respuesta = $result->respuesta;
           
        }
        return $this->respond($response);
    }
    public function guardaUsuarioSti(){
        $session = \Config\Services::session();
        $response = new \stdClass();
        // $response->error = true;
        $this->globals = new Mglobal();
        $data = $this->request->getPost();
        
        $hoy = date("Y-m-d H:i:s"); 
    
        if( $data['editar'] !=1){
            if(empty($data['contrasenia']) || empty($data['confirmar_contrasenia'])){
                throw new Exception("Los campos de contraseña son obligatorios");
            }
              
            if($data['contrasenia'] != $data['confirmar_contrasenia'] ){
                throw new Exception("Las contraseñas no son identicas");
            }
        }
      
        if(empty($data['usuario']) ){
            throw new Exception("El campo de <strong>usuario</strong> es requerido");
        }
        if($data['id_sexo'] == 0 ){
            throw new Exception("El campo sexo es requerido");
        }
        if($data['id_nivel'] == 0 ){
            throw new Exception("El campo Nivel es requerido");
        }
        if($data['id_dependencia'] == 0){
            throw new Exception("El campo Dependencia es requerido");
        }
        if($data['id_perfil'] == 0 ){
            throw new Exception("El campo perfil es requerido");
        }
        if(empty($data['correo']) ){
            throw new Exception("El campo correo es requerido");
        }
        if(empty($data['nombre']) || 
           empty($data['primer_apellido']) || 
           empty($data['rfc']) ){
            throw new Exception("Algunos campos son requeridos");
        }
        if( $data['editar'] !=1){
            $curp  = $this->globals->getTabla(['tabla' => 'usuario', 'where' => ['curp' => $data['curp'], 'visible' =>1]]); 
            if( !empty($curp->data) ){
                throw new Exception("El campo de <strong>CURP</strong> ya existe en la base de datos");
            }
            $existente  = $this->globals->getTabla(['tabla' => 'usuario', 'where' => ['usuario' => $data['usuario'], 'contrasenia' => md5($data['contrasenia']),  'visible' =>1]]); 
            if( !empty($existente->data) ){
                throw new Exception("El <strong> usuario y/o contraseña</strong> ya existe en la base de datos, favor de cambiar los datos");
            }
        }
     
        
        $dataInsert = [
            'id_sexo'               => (int)$data['id_sexo'],           
            'id_nivel'              => (int)$data['id_nivel'],           
            'id_dependencia'        => (int)$data['id_dependencia'],             
            'id_perfil'             => (int)$data['id_perfil'],           
            'id_padre'              => (int)$session->get('id_perfil'),           
            'usuario'               => $data['usuario'],                
            'nombre'                => $data['nombre'],           
            'primer_apellido'       => $data['primer_apellido'],           
            'segundo_apellido'      => $data['segundo_apellido'],             
            'correo'                => $data['correo'],           
            'curp'                  => $data['curp'],           
            'rfc'                   => $data['rfc'],             
            'denominacion_funcional'=> $data['denominacion_funcional'],             
            'area'                  => $data['area'],             
            'jefe_inmediato'        => $data['jefe_inmediato'],             
            'fec_nac'               => $data['fec_nac'],            
            'fec_registro'          => $hoy   
        ];
        if(isset($data['contrasenia']) && !empty($data['contrasenia'])){
          $dataInsert['contrasenia'] = md5($data['contrasenia']); 
        }     
        $dataBitacora = ['id_user' => $session->get('id_usuario'), 'script' => 'Agregar.php/guardaTurno'];
        
       
        $dataConfig = [
            "tabla"=>"usuario",
            "editar"=>($data['editar']==1)?true:false,
            "idEditar"=>['id_usuario'=>$data['id_usuario']]
        ];

        $response = $this->globals->saveTabla($dataInsert,$dataConfig,$dataBitacora);
        
        return $this->respond($response);
    }

    private function handleException($e)
    {
        log_message('error', "Se produjo una excepción: " . $e->getMessage());
    }
    function validarCampo($valor, $nombreCampo) {
        // $pattern = "/^([a-zA-Z 0-9]+)$/";
        $pattern = "/^([a-zA-ZáéíóúüñÁÉÍÓÚÜÑ 0-9]+)$/";
        
        if (!preg_match($pattern, $valor)) {
            throw new Exception("Error en el campo '$nombreCampo': Por favor, utilice únicamente caracteres alfanuméricos (letras y números). Gracias.");
        }
    
        return $valor;
    }
    public function cambioPassword()
    {
        $session = \Config\Services::session();
        $response = new \stdClass();
        $Mglobal    = new Mglobal;
        $response->error = true;
        $response->respuesta = 'Error| Error al Generar la consulta';
        $data= $this->request->getPost();
        if (!isset($data['id_usuario']) || empty($data['id_usuario'])){
            $response->respuesta = "No se ha proporcionado un identificador válido";
            return $this->respond($response);
        }
        $usuario = $Mglobal->getTabla(["tabla"=>"usuario","where"=>["id_usuario" => $data['id_usuario'], "visible" => 1]])->data[0];
        if($usuario->contrasenia == md5($data['contrasenia'])){
            $response->error     = true;
            $response->respuesta = 'La contraseña no puede ser la misma que ya esta registrada';
            return $this->respond($response);

        }
        $dataInsert = [
            'cambio_pass' =>1,
            'contrasenia' =>md5($data['contrasenia'])
        ];
        $dataConfig = [
            "tabla"=>"usuario",
            "editar"=>true,
            "idEditar"=>['id_usuario'=>$data['id_usuario']]
        ];
        $result = $Mglobal->saveTabla($dataInsert,$dataConfig,["script"=>"Usuario.deleteUsuario"]);
        if(!$result->error){
            $response->error     = $result->error;
            $response->respuesta = $result->respuesta;

        }
        return $this->respond($response);
    

    }
    public function guardaTurno(){
        $session = \Config\Services::session();
        $response = new \stdClass();
        // $response->error = true;
        $agregar = new Magregarturno();
        $data = $this->request->getPost();
        
        $currentDateTime = new \DateTime();
        $formattedDate = $currentDateTime->format('Y-m-d H:i:s');
        $fecha_peticion = $currentDateTime::createFromFormat('d/m/Y', $data['fecha_peticion'])->format('Y-m-d');
        $fecha_recepcion = $currentDateTime::createFromFormat('d/m/Y', $data['fecha_recepcion'])->format('Y-m-d');

        $anioActual = date("Y"); 
        $dataInsert = [
            'anio'                         => $anioActual,
            'id_asunto'                    => $data['asunto'],           
            'fecha_peticion'               => $fecha_peticion,             
            'fecha_recepcion'              => $fecha_recepcion,                           
            'solicitante_titulo'           => $data['titulo_inv'],                 
            'solicitante_nombre'           => $data['nombre_t'],                 
            'solicitante_primer_apellido'  => $data['primer_apellido'],                         
            'solicitante_segundo_apellido' => $data['segundo_apellido'],                         
            'solicitante_cargo'            => $data['cargo_inv'],             
            'solicitante_razon_social'     => $data['razon_social_inv'],                     
            'resumen'                      => $this->validarCampo($data['resumen'],"resumen"),     
            'id_estatus'                   => $data['status'],         
            'observaciones'                => $data['observaciones'],
            'id_resultado_turno'              => $data['id_resultado_turno'],   
            'resultado_turno'              => $data['resultado_turno'],             
            'firma_turno'                  => $data['firma_turno'],         
            'usuario_registro'             => $session->id_usuario,             
            'fecha_registro'               => $formattedDate, 
            // arrays
            'id_destinatario'              => isset($data['nombre_turno']) ? $data['nombre_turno'] : array(), 
            'id_destinatario_copia'        => isset($data['cpp']) ? $data['cpp'] : array(),
            'id_indicacion'                => isset($data['indicacion']) ? $data['indicacion'] : array(),
        ];
       /*  var_dump($dataInsert);
        die(); */
        $dataBitacora = ['id_user' =>  $session->id_usuario, 'script' => 'Agregar.php/guardaTurno'];
        
       
        try {
            $respuesta = $agregar->guardarTurnoNuevo($dataInsert, $dataBitacora);
            $response->respuesta = $respuesta;
            return $this->respond($response);
        } catch (\Exception $e) {
            $this->handleException($e);
            
            $response->error = $e->getMessage();
            return $this->respond($response);
        }
    }
    public function uploadCSV()
    {
        $response = new \stdClass();
    
        // Verificar si el archivo se recibió correctamente
        if ($file = $this->request->getFile('fileinput')) {
            if ($file->getClientMimeType() !== 'text/csv' && strtolower($file->getExtension()) !== 'csv') {
                $response->error = true;
                $response->respuesta = 'El archivo debe ser de formato CSV.';
                return $this->respond($response);
            }
            $id_categoria = $this->request->getPost('id_categoria');
         
            if ($file->isValid() && !$file->hasMoved()) {
                // Asignar un nombre aleatorio y mover el archivo a la carpeta de uploads
              
                $newName = $file->getRandomName();
                $file->move(WRITEPATH . 'uploads', $newName);
                $filePath = WRITEPATH . 'uploads/' . $newName;
            
                // Procesar el archivo CSV y enviar los datos a Node.js
                $processResponse = $this->processCSVAndSend($filePath, $id_categoria);
                // Eliminar el archivo CSV después de procesarlo
                // Configurar la respuesta en función del resultado de `processCSVAndSend`
                if ($processResponse->error) {
                    $response->error = true;
                    $response->respuesta = 'Error al procesar el CSV';
                    
                } else {
                    $response->error = false;
                    $response->respuesta = 'Archivo procesado correctamente';
                    //$response->data = $processResponse->data;
                }
            } else {
                $response->error = true;
                $response->respuesta = 'Error en la subida del archivo.';
            }
        } else {
            $response->error = true;
            $response->message = 'Archivo no recibido.';
        }
        return $this->respond($response);
        //return $this->response->setJSON($response);
    }
    public function processCSVAndSend($filePath, $id_categoria)
    {
        $response = new \stdClass();
        $data = [];
        
        if (($handle = fopen($filePath, "r")) !== false) {
            $header = fgetcsv($handle, 1000, ","); // Lee la primera fila como encabezado
    
            while (($row = fgetcsv($handle, 1000, ",")) !== false) {
                $encodedRow = array_map('utf8_encode', $row); // Codifica los valores a UTF-8
                $courseData = array_combine($header, $encodedRow); // Combina encabezado y valores
    
               // Convertir las fechas al formato `yyyy-mm-dd`
               // $courseData['startdate'] = date('Y-m-d', strtotime(str_replace('/', '-', $courseData['startdate'])));
               // $courseData['enddate'] = date('Y-m-d', strtotime(str_replace('/', '-', $courseData['enddate'])));
    
                $data[] = $courseData;
            }
            fclose($handle);
        }
        // Enviar los datos a Node.js
        return $this->sendDataToNode($data, $id_categoria);
    }
    public function sendDataToNode($data, $id_categoria)
    {
        $client = \Config\Services::curlrequest();
        $session = \Config\Services::session();
        $response = new \stdClass();
     
        $catalogos      = new Mglobal;
      
        foreach($data as $key){
             $insert = [
                'fullname'   => $key['fullname'],
                'categoryid' => $id_categoria,
                'startdate'  => $key['startdate'],
                'enddate'    => $key['enddate'],
                'idnumber'   => $key['idnumber']
             ];
             $result = $catalogos->createCurso($insert, 'crearCursosDesdeCSV');
        
             if(!$result->error){
                $response->error     = false;
                $response->respuesta = 'creacion de cursos exitoso';
             }else{
                $response->error     = true;
                $response->respuesta = 'Inconsistencia en el archivo, verificar ID moodle';
             }
             
        }
    return $response;
        
    }
    public function getAllCursos() {
        $session     = \Config\Services::session();
        $response    = new stdClass();
        $response->error = true ;
        $response->respuesta = 'Error| Error al generar la consulta' ;
        $Mglobal   = new Mglobal;
        $id_cursos_sac = $this->request->getPost('id_cursos_sac');
        $data = [];
        $result = $Mglobal->getTabla(['tabla' => 'cursos_sac', 'where' => ['visible' => 1, 'activo' => 1, 'id_cursos_sac' => $id_cursos_sac]]);
     
        if(!$result->error){
           $response->error     = $result->error;
           $response->respuesta = $result->respuesta;
           $response->data      = $result->data;
        }
              
        return $this->respond($response);
    }
    public function guardarCursoPrograma(){
        $session     = \Config\Services::session();
        $response    = new stdClass();
        $response->error = true ;
        $response->respuesta = 'Error| Error al generar la consulta' ;
        $Mglobal   = new Mglobal;
        $data =  $this->request->getPost();
        $hoy = date("Y-m-d H:i:s"); 
        if($data['editar'] == 0 ){
            $where =['visible' => 1, 'id_curso' => $data['id_curso_sac'], 'id_periodo' =>$data['periodo'], 'id_usuario'=>$session->id_usuario ];
            $registro    = $Mglobal->getTabla(['tabla' => 'estudiante_curso', 'where' => $where]);
        
            if(isset($registro->data) && !empty($registro->data)){
                $response->error = true;
                $response->respuesta = 'El Usuario ya tiene registrado este curso y periodo';
                return $this->respond($response);
            }
        }
     
        $dataConfig = [
            "tabla"=>"estudiante_curso",
            "editar"=>($data['editar']==1)?true:false,
             "idEditar"=>['id_estudiante_curso'=>$data['id_periodo_editar']]
        ];
        if($data['editar']==0){
            $Insert = [
                'id_curso'      => (int)$data['id_curso_sac'],                      
                'id_periodo'    => (int)$data['periodo'],                      
                'id_usuario'    => (int)$session->id_usuario,                                         
                'usu_reg'       => (int)$session->id_usuario,                      
                'fec_reg'       => $hoy   
            ];
        }else{
            $Insert = [     
                'id_periodo'    => (int)$data['periodo'],                                                              
                'usu_act'       => (int)$session->id_usuario,                       
            ];
        }
       
       $dataBitacora = ['id_user' =>  $session->id_usuario, 'script' => 'Agregar.php/guardaCurso'];
       $result = $Mglobal->saveTabla($Insert,$dataConfig,$dataBitacora);
       if(!$result->error){
        $response->error     = false;
        $response->respuesta = $result->respuesta;
       }
        
        return $this->respond($response);
    }
    public function detalleCurso($id_cursos_sac = null) {
        $session     = \Config\Services::session();
   
        $response    = new stdClass();
        $response->error = true ;
        $response->respuesta = 'Error| Error al generar la consulta' ;
        $Mglobal   = new Mglobal;
        $data = [];
        $result    = $Mglobal->getTabla(['tabla' => 'cursos_sac', 'where' => ['visible' => 1, 'activo' => 1, 'id_cursos_sac' => $id_cursos_sac]]);
        $periodo   = $Mglobal->getTabla(['tabla' => 'vw_periodo', 'where' => ['visible' => 1, 'id_curso' => $id_cursos_sac]]);
        $categoria = $Mglobal->getTabla(['tabla' => 'vw_categoria', 'where' => ['visible' => 1, 'id_curso' => $id_cursos_sac]]);
        if(isset($result->data) && empty($result->data)){
            $data['contentView'] = 'secciones/vError500';
            $data['layout'] = 'plantilla/lytLogin';
            $this->_renderView($data);
            die();
          
        }
        $data['curso']= $result->data[0];
        if(!$periodo->error){
           $data['periodo']= (isset($periodo->data) && !empty($periodo->data))?$periodo->data:[];
        }
        if(!$categoria->error){
           $data['categoria']= (isset($categoria->data) && !empty($categoria->data))?$categoria->data:[];
        }
        $data['registro'] = false;
        if($id_cursos_sac){
            $result = $Mglobal->getTabla(['tabla'=>'estudiante_curso', 'where' =>['id_curso' => $id_cursos_sac, 'id_usuario' => $session->id_usuario, 'visible' => 1,]]);
            if(isset($result->data) && !empty($result->data)){
               $data['registro'] = true;
            }
        }
        $usuRegCurso   = $Mglobal->getTabla(['tabla' => 'estudiante_curso', 'where' => ['visible' => 1, 'id_curso' => $id_cursos_sac, 'id_usuario' => $session->id_usuario]]);
        if(isset( $usuRegCurso->data) && !empty( $usuRegCurso->data)){
           $data['id_periodo_editar'] = $usuRegCurso->data[0]->id_estudiante_curso;
        }

        $data['scripts'] = array('agregar');
        $data['contentView'] = 'secciones/vDetalleProgramar';                
        $this->_renderView($data);
    }
    public function TablaPrograma() {
        $session     = \Config\Services::session();
        $response    = new stdClass();
        $Mglobal   = new Mglobal;
        $data = [];
        $data['usuario'] = [];
        $data['cursos_sac'] = []; // Inicializa el array

        $cursoSac = $Mglobal->getTabla(['tabla' => 'cursos_sac', 'where' => ['visible' => 1, 'activo' => 1]]);
        if (isset($cursoSac->data) && !empty($cursoSac->data)) {
            $sac = $cursoSac->data;
            foreach ($sac as $s) {
                $periodos = [];
                $counts = [];
                
                for ($i = 1; $i <= 9; $i++) {
                    $periodos[$i] = $Mglobal->getTabla([
                        'tabla' => 'vw_estudiante_curso',
                        'where' => ['visible' => 1, 'id_curso' => $s->id_cursos_sac, 'id_periodo' => $i]
                    ])->data;
                    
                    $counts[$i] = count($periodos[$i]);
                }
          
                $data['cursos_sac'][] = [
                    'id_cursos_sac'   => $s->id_cursos_sac,
                    'dsc_curso'       => $s->dsc_curso,
                    'periodos'        => $periodos, // Array de nombres de estudiantes
                    'contador'        => $counts
                ];
            }
        }
/*         if (isset($cursoSac->data) && !empty($cursoSac->data)) {
            $sac = $cursoSac->data;
            $contador = count($sac);
            foreach ($sac as $s) {
                $vwEstudianteCurso = $Mglobal->getTabla([
                    'tabla' => 'vw_estudiante_curso',
                    'where' => ['visible' => 1, 'id_curso' => $s->id_cursos_sac]
                ])->data;
                $periodos = [];
                die( var_dump($vwEstudianteCurso) );
                foreach ($vwEstudianteCurso as $v) {
                    $periodos[] = $v->id_periodo; // Agregar cada estudiante al array
                }
                $data['cursos_sac'][] = [
                    'id_cursos_sac'   => $s->id_cursos_sac,
                    'dsc_curso'       => $s->dsc_curso,
                    'periodos'        => $periodos, // Array de nombres de estudiantes
                    'contador'        => $contador
                ];
            }
        } */
        
       // die(var_dump($data['cursos_sac']));

        if($session->id_perfil == 1):
        $cursoDB = $Mglobal->getTabla([
            'tabla' => 'vw_estudiante_curso', 
            'where' => [
                'visible' => 1, 
            ]
        ]);
        endif;
        if($session->id_perfil != 1):
        $cursoDB = $Mglobal->getTabla([
            'tabla' => 'vw_estudiante_curso', 
            'where' => [
                'visible' => 1, 
                'id_dependencia' => $session->id_dependencia
            ]
        ]);
        endif;
        if (isset($cursoDB->data) && !empty($cursoDB->data)) {
            // Arreglo temporal para agrupar los datos por usuario
            $usuarios = [];
        
            foreach ($cursoDB->data as $c) {
                $nombreUsuario = $c->nombre_completo;
        
                // Si el usuario no existe en el arreglo, lo inicializamos
                if (!isset($usuarios[$nombreUsuario])) {
                    $usuarios[$nombreUsuario] = [
                        'nombre' => '<h6>'.$nombreUsuario.'</h6>',
                        'P1' => '',
                        'P2' => '',
                        'P3' => '',
                        'P4' => '',
                        'P5' => '',
                        'P6' => '',
                        'P7' => '',
                        'P8' => '',
                        'P9' => ''
                    ];
                }
        
                $key = 'P' . $c->id_periodo;
                if (isset($usuarios[$nombreUsuario][$key])) {
                    // Si ya hay un curso en este periodo, agregamos el nuevo curso separado por una coma
                    if (!empty($usuarios[$nombreUsuario][$key])) {
                        $usuarios[$nombreUsuario][$key] .= '<br> ';
                    }
                    $usuarios[$nombreUsuario][$key] .= '<span class="badge badge-md badge-soft-purple">'.$c->dsc_curso.'</span>';
                }
            }

            $data['usuario'] = array_values($usuarios);
        }
       
        $data['scripts'] = array('agregar');
        $data['contentView'] = 'secciones/vTablaPrograma';                
        $this->_renderView($data);
    }
    public function TablaProgramaCurso() {
        $session     = \Config\Services::session();
        $response    = new stdClass();
        $Mglobal   = new Mglobal;
        $data = [];
        $data['usuario'] = [];
        $data['cursos_sac'] = []; // Inicializa el array

        $cursoSac = $Mglobal->getTabla(['tabla' => 'cursos_sac', 'where' => ['visible' => 1, 'activo' => 1]]);
        if (isset($cursoSac->data) && !empty($cursoSac->data)) {
            $sac = $cursoSac->data;
            $contador = count($sac);
            foreach ($sac as $s) {
                $vwEstudianteCurso = $Mglobal->getTabla([
                    'tabla' => 'vw_estudiante_curso',
                    'where' => ['visible' => 1, 'id_curso' => $s->id_cursos_sac]
                ])->data;
                $periodos = [];
                //die( var_dump($vwEstudianteCurso) );
                foreach ($vwEstudianteCurso as $v) {
                    $periodos[] = $v->id_periodo; // Agregar cada estudiante al array
                }
                $data['cursos_sac'][] = [
                    'id_cursos_sac'   => $s->id_cursos_sac,
                    'dsc_curso'       => $s->dsc_curso,
                    'periodos'        => $periodos, // Array de nombres de estudiantes
                    'contador'        => $contador
                ];
            }
        }
        
       // die(var_dump($data['cursos_sac']));

        if($session->id_perfil == 1):
        $cursoDB = $Mglobal->getTabla([
            'tabla' => 'vw_estudiante_curso', 
            'where' => [
                'visible' => 1, 
            ]
        ]);
        endif;
        if($session->id_perfil != 1):
        $cursoDB = $Mglobal->getTabla([
            'tabla' => 'vw_estudiante_curso', 
            'where' => [
                'visible' => 1, 
                'id_dependencia' => $session->id_dependencia
            ]
        ]);
        endif;
        if (isset($cursoDB->data) && !empty($cursoDB->data)) {
            // Arreglo temporal para agrupar los datos por usuario
            $usuarios = [];
        
            foreach ($cursoDB->data as $c) {
                $nombreUsuario = $c->nombre_completo;
        
                // Si el usuario no existe en el arreglo, lo inicializamos
                if (!isset($usuarios[$nombreUsuario])) {
                    $usuarios[$nombreUsuario] = [
                        'nombre' => '<h6>'.$nombreUsuario.'</h6>',
                        'P1' => '',
                        'P2' => '',
                        'P3' => '',
                        'P4' => '',
                        'P5' => '',
                        'P6' => '',
                        'P7' => '',
                        'P8' => '',
                        'P9' => ''
                    ];
                }
        
                $key = 'P' . $c->id_periodo;
                if (isset($usuarios[$nombreUsuario][$key])) {
                    // Si ya hay un curso en este periodo, agregamos el nuevo curso separado por una coma
                    if (!empty($usuarios[$nombreUsuario][$key])) {
                        $usuarios[$nombreUsuario][$key] .= '<br> ';
                    }
                    $usuarios[$nombreUsuario][$key] .= '<span class="badge badge-md badge-soft-purple">'.$c->dsc_curso.'</span>';
                }
            }

            $data['usuario'] = array_values($usuarios);
        }
       
        $data['scripts'] = array('agregar');
        $data['contentView'] = 'secciones/vTablaPrograma';                
        $this->_renderView($data);
    }
    public function ProgramarCurso() {
        $session     = \Config\Services::session();
        $response    = new stdClass();
        $Mglobal   = new Mglobal;
        $data = [];
        
        $cursoDB = $Mglobal->getTabla(['tabla' => 'cursos_sac', 'where' => ['visible' => 1, 'activo' => 1]]);
        $data['cursos'] = (isset($cursoDB->data) && !empty($cursoDB->data))?$cursoDB->data:[];
        //die(var_dump($data['cursos']));
        $data['scripts'] = array('agregar');
        $data['contentView'] = 'secciones/vProgramar';                
        $this->_renderView($data);
    }
    public function Configuracion() {
        $session     = \Config\Services::session();
        $response    = new stdClass();
        $catalogos   = new Mglobal;
        // Obtener el evento_id encriptado desde GET y desencriptarlo
        $id_curso = $this->request->getGet('id_curso');
    
        if (!$id_curso) {
            // Manejar error de desencriptación
            echo "ID no válido o error de desencriptación.";
            return;
        }
        $datos = ['courseId' => $id_curso ];
        $categoria = "";
        $quizz = $catalogos->createCurso($datos, 'traerQuiz');
        $details = $catalogos->createCurso($datos, 'getCourseDetailsById');
        //die( var_dump( $details ) );
        if(!empty($quizz->data)){
            $data['quizz'] = $quizz->data;
        }
        if(!empty($details->data)){
            $data['details'] = $details->data;
            $insert = [
                 'categoryId' => $details->data[0]->categoryid
            ];
            $categoria = $catalogos->createCurso($insert, 'getCoursesByCategoryId');
            $data['categoria'] = $categoria->data;
            $data['fec_inicio'] = date('d-m-Y', $categoria->data[0]->startdate); 
            $data['fec_fin'] = date('d-m-Y', $categoria->data[0]->enddate); 
        }
        //var_dump( $categoria->data[0]->modules );
        $data['id_curso'] = $id_curso;

        $data['scripts'] = array('agregar');
        $data['contentView'] = 'secciones/vConfiguracion';                
        $this->_renderView($data);
    }
    public function SolicitarCurso($id_categoria=NULL)
    {
        $session = \Config\Services::session();
        if($session->get('id_perfil') >= 5){
            header('Location:'.base_url().'index.php/Inicio');            
            die();
        }
        $response = new \stdClass();
        $catalogos = new Mglobal;
        $eventos = [];
        $data = [
            'categoryId' => $id_categoria
        ];
    
        $categoria = $catalogos->createCurso($data, 'getCoursesByCategoryId');
       
        if (!empty($categoria->data)) {
            // Recorre los cursos y convierte las fechas a un formato legible
            foreach ($categoria->data as &$curso) {
                if (isset($curso->startdate)) {
                    $curso->startdate_legible = date('d-m-Y', $curso->startdate);
                }
                if (isset($curso->enddate)) {
                    $curso->enddate_legible = date('d-m-Y', $curso->enddate);
                }
            }
            $eventos = $categoria->data;
        }
        $data['eventos']      = $eventos;
        $data['id_categoria'] = $id_categoria;
        $data['scripts']      = array('inicio');
        $data['edita']        = 0;
        $data['contentView'] = 'secciones/vSolicitud';                
        $this->_renderView($data); 
    }
    public function getCoursesByCategoryId($id_categoria)
    {
        $session = \Config\Services::session();
        $response = new \stdClass();
        $catalogos = new Mglobal;
    
        $eventos = '';
        $data = [
            'categoryId' => $id_categoria
        ];
    
        $categoria = $catalogos->createCurso($data, 'getCoursesByCategoryId');
    
        if (!empty($categoria->data)) {
            // Recorre los cursos y convierte las fechas a un formato legible
            foreach ($categoria->data as &$curso) {
                if (isset($curso->startdate)) {
                    $curso->startdate_legible = date('d-m-Y', $curso->startdate);
                }
                if (isset($curso->enddate)) {
                    $curso->enddate_legible = date('d-m-Y', $curso->enddate);
                }
            }
            $eventos = $categoria->data;
        }
      
        return $this->respond($eventos);
    }
    public function guardaCategoria(){
        $session = \Config\Services::session();
        $response = new \stdClass();
        // $response->error = true;
        $this->globals = new Mglobal();
        $data = $this->request->getPost();
        
        $hoy = date("Y-m-d H:i:s"); 
      
        if(empty($data['nombre_curso']) ){
            throw new Exception("Es requerido el Nombre del curso");
        }
        //valida que el nombre del curso y nombre corto del curso no se repitan
        if(!empty($data['nombre_curso']) ){
            $cursoDB = $this->globals->getTabla(['tabla' => 'categoria', 'where' => ['dsc_categoria'=> $data['nombre_curso'] ,'visible' => 1]]);
            if(!empty($cursoDB->data) && isset($cursoDB->data[0]->dsc_categoria) ){
                throw new Exception("Es Nombre del curso ya existe");
            }

        }
          
        $dataBitacora = ['id_user' =>  $session->id_usuario, 'script' => 'Agregar.php/guardaCurso'];
        $dataInsert = [
            'categoryName' => $data['nombre_curso'],                      
            'courseName' => 'Curso de Prueba',
            'startDate' => '2023-01-01',
            'endDate' => '2023-12-31' 
        ];
   
        $response = $this->globals->createCurso($dataInsert, 'crearCategoria');
      
        if($response->error){
            throw new Exception("No se puedo crear la Categoria");
        }else{
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
        }
      
        return $this->respond($response);
    }
    public function formConfigurarCurso() {
        $session     = \Config\Services::session();
        $response    = new stdClass();
        $catalogos   = new Mglobal;

        // Obtener el evento_id encriptado desde GET y desencriptarlo
        $formData = $this->request->getPost();

        //validar que ya exista el curso 
        $cursoExiste        = $catalogos->getTabla(['tabla' => 'cursos_perfil', 'where' => ['id_curso'=> $formData['id_curso'] ,'visible' => 1, 'id_padre'   => $session->get('id_perfil') ]]);
        if(empty($cursoExiste->data) ){
           
            $insert = [
                'id_curso'   => (int)$formData['id_curso'],
                'id_padre'   => $session->get('id_perfil'),
                'fec_reg'    => date("Y-m-d H:i:s"),
                'usu_reg'    => $session->get('id_usuario')
            ]; $dataBitacora = ['id_user' =>  $session->id_usuario, 'script' => 'Agregar.php/updateEventos'];
   
            $dataConfig = [
                "tabla"=>"cursos_perfil",
                "editar"=>false,
               // "idEditar"=>['id_curso_moodle'=>$formData['id_curso']]
            ];
           $result = $catalogos->saveTabla($insert,$dataConfig,$dataBitacora);
            if(!$result->error){
                $response->error = $result->error;
                $response->respuesta = $result->respuesta;
            }else{
                $response->error = true;
                $response->respuesta = 'Error al actualizar las fechas';
            }

        }
       
       
        foreach ($formData['tableData'] as $key) {
            // Accede a los valores directamente sin `$i` en el índice
            if(isset($key["id_curso"]) && $key["id_curso"] > 0 ){
                $data = [
                    'id_curso'  => $key["id_curso"],
                    'timeopen'  => strtotime($key["timeopen"]),  // Convierte a Unix timestamp
                    'timeclose' => strtotime($key["timeclose"])  // Convierte a Unix timestamp
                ];
            $result       = $catalogos->createCurso($data, 'updateQuiz'); 
                if(!$result->error){
                    $response->error = $result->error;
                    $response->respuesta = $result->respuesta;
                }else{
                    $response->error = true;
                    $response->respuesta = 'Error al actualizar las fechas';
                }
               
            }
        }

        return $this->respond($response);
    }

  
}