<?php 
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Libraries\Curps;
use App\Libraries\Fechas;
use App\Libraries\Funciones;
use App\Models\Mglobal;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

use stdClass;
use CodeIgniter\API\ResponseTrait;

require_once FCPATH . 'app/Libraries/PHPMailer/Exception.php';
require_once FCPATH . 'app/Libraries/PHPMailer/PHPMailer.php';
require_once FCPATH . 'app/Libraries/PHPMailer/SMTP.php';

require_once FCPATH . '/mpdf/autoload.php';

class Usuario extends BaseController 
{

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
        $this->globals = new Mglobal();
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
        $data['unidad'] = $this->globals->getTabla(["tabla"=>"cat_clues","select"=>"id_clues, NOMBRE_UNIDAD", "where"=>["visible"=>1],'limit' => 10]); 
        $data['perfiles'] = $this->globals->getTabla(["tabla"=>"seg_perfiles", "where"=>["visible"=>1]]); 
        $data['cat_sexo'] = $this->globals->getTabla(["tabla"=>"cat_sexo", "where"=>["visible"=>1]]); 
        $data['scripts'] = array('principal','inicio');
        $data['edita'] = 0;
        $data['nombre_completo'] = $session->nombre_completo; 
        $data['contentView'] = 'secciones/vUsuarios';                
        $this->_renderView($data);
        
    }

    public function enviarCorreo()
    {
        $session = \Config\Services::session();
        $response = new \stdClass();
        $response->error = true;
        $principal = new Mglobal;
        $mail = new PHPMailer(true);
        $data = array();
        $id_participante = $this->request->getPost('id_participante');
        $participante = $principal->getTabla(['tabla' => 'participantes', 'where' => ['visible' => 1, 'id_participante'=>$id_participante]]);
        if(isset($participante->data) && empty($participante->data)){
            $response->respuesta = 'Id de usuario no encontrador favor de contactar al Administrador';
            return $this->respond($response);
        }
        $usuario = $participante->data[0];
        $hoy = date("Y-m-d H:i:s"); 
        $dataInsert = [
            'id_sexo'               => (int)$usuario->id_sexo,           
            'id_nivel'              => (int)$usuario->id_nivel,           
            'id_dependencia'        => (int)$usuario->id_dependencia,  
            'id_perfil'             => 8,           
            'id_padre'              => (int)$session->id_perfil,           
            'usuario'               => $usuario->curp,           
            'nombre'                => $usuario->nombre,           
            'primer_apellido'       => $usuario->primer_apellido,           
            'segundo_apellido'      => $usuario->segundo_apellido,             
            'correo'                => $usuario->correo,           
            'curp'                  => $usuario->curp, 
            'contrasenia'           => md5($usuario->curp),
            'rfc'                   => $usuario->rfc,             
            'denominacion_funcional'=> $usuario->denominacion_funcional,             
            'area'                  => $usuario->area,             
            'jefe_inmediato'        => $usuario->jefe_inmediato,                      
            'fec_nac'               => date("Y-m-d", strtotime($usuario->fec_nac)),             
            'fec_registro'          => $hoy  
        ];   
        $dataBitacora = ['id_user' => $session->id_usuario, 'script' => 'Agregar.php/guardaUsuario'];
        $dataConfig = [
            "tabla"=>"usuario",
            "editar"=>false,
            //"idEditar"=>['id_usuario'=>$data['id_usuario']]
        ];
        $response = $this->globals->saveTabla($dataInsert,$dataConfig,$dataBitacora);  
        $dataConfig = [
            "tabla"=>"participantes",
            "editar"=>true,
            "idEditar"=>['id_participante'=>$id_participante]
        ];
        $response = $this->globals->saveTabla(['visible'=>0],$dataConfig,$dataBitacora);
        $contrasenia = md5($usuario->curp);
        $response->error = false;
        $response->respuesta = "Correo enviado correctamente.";
        return $this->respond($response);
/*         try {
            $mail->isSMTP(); // Usar SMTP para el envío
            $mail->SMTPDebug = 2; // Habilitar depuración (2 para mensajes de cliente y servidor)
            $mail->Host = 'smtp.gmail.com'; // Servidor SMTP de Gmail
            $mail->SMTPAuth = true; // Habilitar autenticación SMTP
            $mail->Username = 'palafox.marin31@gmail.com'; // Correo electrónico del remitente
            $mail->Password = 'vxqh wycc fsgg tzvk'; // Contraseña de aplicación o contraseña de Gmail
            $mail->SMTPSecure = 'tls'; // Usar cifrado TLS
            $mail->Port = 587; // Puerto SMTP para TLS
        
            // Configurar el correo electrónico
            $mail->setFrom($usuario->correo, 'Sistema de Administración de Capacitación (SAC)');
            $mail->addAddress('palafox.marin@hotmail.com'); // Correo del destinatario
            $mail->Subject = 'Credenciales de Acceso al Sistema SAC'; // Asunto del correo
            $mail->isHTML(true); // Habilitar contenido HTML en el cuerpo del correo
        
            // Cuerpo del correo
            $mail->Body = "
                <p>Te damos la bienvenida al <strong>Sistema de Administración de Capacitación (SAC)</strong>.</p>
                <p>A continuación, te proporcionamos tus credenciales de acceso:</p>
                <ul>
                    <li><strong>Usuario:</strong> $usuario->curp</li>
                    <li><strong>Contraseña:</strong> $contrasenia</li>
                </ul>
                <p>Puedes acceder al sistema a través del siguiente enlace: <a href='http://172.31.187.142/sac2/'>http://172.31.187.142/sac2/</a></p>
                <p>Si tienes alguna duda o necesitas asistencia, no dudes en contactarnos.</p>
                <p>¡Gracias por ser parte de SAC!</p>
            ";
        
            // Enviar el correo
            if ($mail->send()) {
                $response->error = false;
                $response->respuesta = "Correo enviado correctamente.";
            } else {
                $response->error = true;
                $response->respuesta = "Error al enviar el correo: " . $mail->ErrorInfo;
            }
            return $this->respond($response); // Devolver la respuesta
        } catch (Exception $e) {
            // Manejar excepciones
            $response->error = true;
            $response->respuesta = "Error inesperado al enviar el correo: " . $e->getMessage();
            return $this->respond($response);
        } */

    }
 
    
    public function getParticipante()
    {
        $session = \Config\Services::session();
        $principal = new Mglobal;
        $dataDB = array();
        $id_participante = $this->request->getPost('id_usuario');
        if ($session->id_perfil == 1 ) {
            $dataDB = array('tabla' => 'participantes', 'where' => ['visible' => 1, 'id_participante'=>$id_participante]);
        }
        if ($session->id_perfil == 4) {
            $dataDB = array('tabla' => 'participantes', 'where' => ['visible' => 1, 'id_dep_padre' => 4, 'id_participante'=>$id_participante]);
        } 
        if($session->id_perfil == 6  ){
            $dataDB = array('tabla' => 'participantes', 'where' => ['visible' => 1, 'id_dependencia' => $session->id_dependencia, 'id_participante'=>$id_participante]);
        }
        $response = $principal->getTabla($dataDB);
        // var_dump($response);
        // die();
        return $this->respond($response->data[0]);
    }
    public function getDetenido()
    {
        $session = \Config\Services::session();
        $principal = new Mglobal;
        $dataDB = array();
        $id_detenido = $this->request->getPost('id_usuario');
        if ($session->id_perfil == 1 ) {
            $dataDB = array('tabla' => 'detenidos', 'where' => ['visible' => 1, 'id_detenido'=>$id_detenido]);
        }
        if ($session->id_perfil == 4) {
            $dataDB = array('tabla' => 'detenidos', 'where' => ['visible' => 1, 'id_dep_padre' => 4, 'id_detenido'=>$id_detenido]);
        } 
        if($session->id_perfil != 4 || $session->id_perfil != 1 ){
            $dataDB = array('tabla' => 'detenidos', 'where' => ['visible' => 1, 'id_dependencia' => $session->id_dependencia, 'id_detenido'=>$id_detenido]);
        }
        $response = $principal->getTabla($dataDB);
        // var_dump($response);
        // die();
        return $this->respond($response->data[0]);
    }
    public function getUsuarios()
    {
        $session = \Config\Services::session();
        $principal = new Mglobal;
        $dataDB = array();
        if ($session->id_perfil == -1) {
            $dataDB = array('tabla' => 'vw_usuario', 'where' => ['visible' => 1]);
        } elseif ($session->id_perfil == 1) {
            $dataDB = array('tabla' => 'vw_usuario', 'where' => ['visible' => 1]);
        } 
        $response = $principal->getTabla($dataDB);
        // var_dump($response);
        // die();
        return $this->respond($response->data);
    }
    public function getUsuario()
    {
        $session = \Config\Services::session();
        $id_usuario = $this->request->getPost('id_usuario');
        
        // Validar que el ID de usuario esté presente y sea válido
        if (!$id_usuario) {
            return $this->fail('ID de usuario no proporcionado', 400);
        }

        // var_dump($id_usuario);
        // die();
        $response = $this->globals->getTabla(["tabla"=>"vw_usuario","where"=>["id_usuario" => $id_usuario, "visible" => 1]])->data;
        // var_dump($response[0]);
        // die();
        return $this->respond($response[0]);
    }
    public function deleteParticipante()
    {
        $response = new \stdClass();
        $response->error = true;
        $data = $this->request->getPost();

        if (!isset($data['id_participante']) || empty($data['id_participante'])){
            $response->respuesta = "No se ha proporcionado un identificador válido";
            return $this->respond($response);
        }

        $dataConfig = [
            "tabla"=>"participantes",
            "editar"=>true,
            "idEditar"=>['id_participante'=>$data['id_participante']]
        ];
        $response = $this->globals->saveTabla(["visible"=>0],$dataConfig,["script"=>"Usuario.deleteUsuario"]);
        return $this->respond($response);
    }
    public function deleteUsuario()
    {
        $response = new \stdClass();
        $response->error = true;
        $data = $this->request->getPost();

        if (!isset($data['id_usuario']) || empty($data['id_usuario'])){
            $response->respuesta = "No se ha proporcionado un identificador válido";
            return $this->respond($response);
        }

        $dataConfig = [
            "tabla"=>"usuario",
            "editar"=>true,
            "idEditar"=>['id_usuario'=>$data['id_usuario']]
        ];
        $response = $this->globals->saveTabla(["visible"=>0],$dataConfig,["script"=>"Usuario.deleteUsuario"]);
        return $this->respond($response);
    }
    public function estudianteCurso()
    {
        $response = new \stdClass();
        $response->error = true;
        $data = $this->request->getPost();

        if (!isset($data['id_estudiante_curso']) || empty($data['id_estudiante_curso'])){
            $response->respuesta = "No se ha proporcionado un identificador válido";
            return $this->respond($response);
        }

        $dataConfig = [
            "tabla"=>"estudiante_curso",
            "editar"=>true,
            "idEditar"=>['id_estudiante_curso'=>$data['id_estudiante_curso']]
        ];
        $response = $this->globals->saveTabla(["visible"=>0],$dataConfig,["script"=>"Usuario.deleteUsuario"]);
        return $this->respond($response);

    }
    public function deleteDetenido()
    {
        $response = new \stdClass();
        $response->error = true;
        $response->repuesta = "Error|Error al guardar en la base de datos";
        $data = $this->request->getPost();

        if (!isset($data['id_detenido']) || empty($data['id_detenido'])){
            $response->respuesta = "No se ha proporcionado un identificador válido";
            return $this->respond($response);
        }

        $dataConfig = [
            "tabla"=>"detenidos",
            "editar"=>true,
            "idEditar"=>['id_detenido'=>$data['id_detenido']]
        ];
        $result = $this->globals->saveTabla(["visible"=>0],$dataConfig,["script"=>"Usuario.deleteDetenido"]);
        if(!$result->error){
            $response->error     = $result->error;
            $response->respuesta = $result->respuesta;

        }
        return $this->respond($response);
    }
    public function editarCurso()
    {
        $session = \Config\Services::session();
        $response = new \stdClass();
        $response->error = true;
        $data = $this->request->getPost();
        $file = $this->request->getFile('img_ruta');
        $file2 = $this->request->getFile('img_deta_ruta');
    
        // Validar campos obligatorios
        if (!isset($data['categoria']) || empty($data['categoria'])) {
            $response->respuesta = "Es requerido categoría.";
            return $this->respond($response);
        }
        if (!isset($data['periodo']) || empty($data['periodo'])) {
            $response->respuesta = "Es requerido periodo.";
            return $this->respond($response);
        }
        if (!isset($data['nombre_curso']) || empty($data['nombre_curso'])) {
            $response->respuesta = "Es requerido nombre curso.";
            return $this->respond($response);
        }
    
        // Validar archivos solo si se han subido nuevos
        $allowedMimeTypes = ['image/png', 'image/jpeg'];
        $maxSize = 1 * 1024 * 1024; // 1 MB
    
        if ($file && $file->isValid()) {
            if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
                $response->respuesta = "El archivo img_ruta debe ser una imagen PNG o JPG.";
                return $this->respond($response);
            }
            if ($file->getSize() > $maxSize) {
                $response->respuesta = "El archivo img_ruta no debe exceder 1 MB.";
                return $this->respond($response);
            }
        }
    
        if ($file2 && $file2->isValid()) {
            if (!in_array($file2->getMimeType(), $allowedMimeTypes)) {
                $response->respuesta = "El archivo img_deta_ruta debe ser una imagen PNG o JPG.";
                return $this->respond($response);
            }
            if ($file2->getSize() > $maxSize) {
                $response->respuesta = "El archivo img_deta_ruta no debe exceder 1 MB.";
                return $this->respond($response);
            }
        }
    
        // Mover los archivos a la carpeta de destino (solo si se han subido nuevos)
        $uploadPath = FCPATH . 'assets/images/cartas/';
    
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true); // Crear la carpeta si no existe
        }
    
        $newName = null;
        $newName2 = null;
    
        if ($file && $file->isValid()) {
            $newName = $file->getRandomName(); // Generar un nombre único
            $file->move($uploadPath, 'imagen_' . $newName);
        }
    
        if ($file2 && $file2->isValid()) {
            $newName2 = $file2->getRandomName(); // Generar un nombre único
            $file2->move($uploadPath, 'imagen_detalle' . $newName2);
        }
    
        // Preparar datos para actualizar el curso
        $dataUpdate = [
            'dsc_curso'    => $data['nombre_curso'],
            'descripcion'  => $data['descripcion'],
            'des_larga'    => $data['des_larga'],
            'usu_act'      => (int)$session->id_usuario,
        ];
        if(isset($data['new_curso']) && !empty($data['new_curso'])){
            $dataUpdate['nuevo'] = 1;
        }else{
            $dataUpdate['nuevo'] = 0;
        }
        if(isset($data['dirigido']) && !empty($data['dirigido'])){
            $dataUpdate['dirigido'] = $data['dirigido'];
        }
        if(isset($data['duracion']) && !empty($data['duracion'])){
            $dataUpdate['duracion'] = $data['duracion'];
        }
        if(isset($data['autogestivo']) && !empty($data['autogestivo'])){
            $dataUpdate['autogestivo'] = $data['autogestivo'];
        }
        if(isset($data['horas']) && !empty($data['horas'])){
            $dataUpdate['horas'] = $data['horas'];
        }
        if(isset($data['curso_linea']) && !empty($data['curso_linea'])){
            $dataUpdate['curso_linea'] = $data['curso_linea'];
        }
        if(isset($data['informacion']) && !empty($data['informacion'])){
            $dataUpdate['informacion']  = $data['informacion'];
        }
        // Actualizar las rutas de las imágenes solo si se han subido nuevos archivos
        if ($newName) {
            $dataUpdate['img_ruta'] = 'assets/images/cartas/imagen_' . $newName;
        }
        if ($newName2) {
            $dataUpdate['img_deta_ruta'] = 'assets/images/cartas/imagen_detalle' . $newName2;
        }
    
        if (isset($data['id_moodle']) && !empty($data['id_moodle'])) {
            $dataUpdate['id_moodle'] = $data['id_moodle'];
        }
    
        // Configuración para la actualización
        $dataConfig = [
            "tabla" => "cursos_sac",
            "editar" => true,
            "idEditar" => ['id_cursos_sac' => (int)$data['editar_curso']]
        ];
    
        // Guardar en la base de datos
        $result = $this->globals->saveTabla($dataUpdate, $dataConfig, ["script" => "categoriaCurso.editarCurso"]);
    
        if (!$result->error) {
            // Desactivar categorías y periodos anteriores
            $categoria_curso = $this->globals->getTabla([
                "tabla" => "categoria_curso",
                "where" => ["id_curso" => $data['editar_curso']]
            ]);
            $periodo_curso = $this->globals->getTabla([
                "tabla" => "periodo_curso",
                "where" => ["id_curso" => $data['editar_curso']]
            ]);
    
            foreach ($categoria_curso->data as $j) {
                $dataInsert = [
                    'visible' => 0, // Desactivar la categoría
                    'usu_act' => $session->id_usuario,
                ];
                $dataConfig = [
                    "tabla" => "categoria_curso",
                    "editar" => true,
                    "idEditar" => ['id_curso' => $data['editar_curso']]
                ];
                $this->globals->saveTabla($dataInsert, $dataConfig, ["script" => "categoriaCurso.editarCurso"]);
            }
    
            foreach ($data['categoria'] as $key) {
                $dataInsert = [
                    'id_curso'     => $data['editar_curso'],
                    'id_categoria' => $key,
                    'fec_reg'      => date('Y-m-d H:i:s'),
                    'usu_reg'      => $session->id_usuario,
                ];
                $dataConfig = [
                    "tabla" => "categoria_curso",
                    "editar" => false
                ];
                $this->globals->saveTabla($dataInsert, $dataConfig, ["script" => "categoriaCurso.guardarCurso"]);
            }
    
            foreach ($periodo_curso->data as $k) {
                $dataInsert = [
                    'visible' => 0, // Desactivar el periodo
                    'usu_act' => $session->id_usuario,
                ];
                $dataConfig = [
                    "tabla" => "periodo_curso",
                    "editar" => true,
                    "idEditar" => ['id_curso' => $data['editar_curso']]
                ];
                $this->globals->saveTabla($dataInsert, $dataConfig, ["script" => "categoriaCurso.editarCurso"]);
            }
    
            foreach ($data['periodo'] as $key) {
                $dataInsert = [
                    'id_curso'     => $data['editar_curso'],
                    'id_periodo'   => $key,
                    'fec_reg'      => date('Y-m-d H:i:s'),
                    'usu_reg'      => $session->id_usuario,
                ];
                $dataConfig = [
                    "tabla" => "periodo_curso",
                    "editar" => false
                ];
                $this->globals->saveTabla($dataInsert, $dataConfig, ["script" => "categoriaCurso.guardarCurso"]);
            }
    
            $response->error = false;
            $response->respuesta = "Curso actualizado correctamente.";
        } else {
            $response->respuesta = $result->respuesta;
        }
    
        return $this->respond($response);
    }
    public function guardarCursos()
    {
        $session = \Config\Services::session();
        $response = new \stdClass();
        $response->error = true;
        $data = $this->request->getPost();
        $file = $this->request->getFile('img_ruta');
        $file2 = $this->request->getFile('img_deta_ruta');
    
        // Validar archivo
        if ($file === null || !$file->isValid() || $file2 === null || !$file2->isValid() ) {
            $response->respuesta = "El archivo de imagen no es válido.";
            return $this->respond($response);
        }
    
        // Validar tipo MIME y tamaño del archivo
        $allowedMimeTypes = ['image/png', 'image/jpeg'];
        $maxSize = 1 * 1024 * 1024; // 1 MB
    
        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            $response->respuesta = "El archivo debe ser una imagen PNG o JPG.";
            return $this->respond($response);
        }
        if (!in_array($file2->getMimeType(), $allowedMimeTypes)) {
            $response->respuesta = "El archivo debe ser una imagen PNG o JPG.";
            return $this->respond($response);
        }
    
        if ($file->getSize() > $maxSize || $file2->getSize() > $maxSize ) {
            $response->respuesta = "El archivo no debe exceder 1 MB.";
            return $this->respond($response);
        }
    
        // Mover el archivo a la carpeta de destino
        $uploadPath = FCPATH . 'assets/images/cartas/';
    
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true); // Crear la carpeta si no existe
        }
    
        $newName = $file->getRandomName(); // Generar un nombre único para el archivo
        $newName2 = $file2->getRandomName(); // Generar un nombre único para el archivo
        $file->move($uploadPath, 'imagen_'.$newName); // Mover el archivo
        $file2->move($uploadPath, 'imagen_detalle'.$newName2); // Mover el archivo

    
    
        // Validar campos obligatorios
        if (!isset($data['categoria']) || empty($data['categoria'])) {
            $response->respuesta = "Es requerido categoría.";
            return $this->respond($response);
        }
        if (!isset($data['periodo']) || empty($data['periodo'])) {
            $response->respuesta = "Es requerido periodo.";
            return $this->respond($response);
        }
        if (!isset($data['nombre_curso']) || empty($data['nombre_curso'])) {
            $response->respuesta = "Es requerido nombre curso.";
            return $this->respond($response);
        }
    
        // Preparar datos para insertar en la tabla de cursos
        $dataInsert = [
            'dsc_curso'    => $data['nombre_curso'],
            'descripcion'  => $data['descripcion'],
            'des_larga'    => $data['des_larga'],
            'dirigido'     => $data['dirigido'],
            'duracion'     => $data['duracion'],
            'autogestivo'  => $data['autogestivo'],
            'horas'        => $data['horas'],
            'curso_linea'  => $data['curso_linea'],
            'informacion'  => $data['informacion'],
            'img_ruta'     => 'assets/images/cartas/imagen_' . $newName, // Guardar la ruta relativa
            'img_deta_ruta'=> 'assets/images/cartas/imagen_detalle' . $newName2, // Guardar la ruta relativa
            'usu_reg'      => $session->id_usuario,
        ];
    
        if (isset($data['id_moodle']) && !empty($data['id_moodle'])) {
            $dataInsert['id_moodle'] = $data['id_moodle'];
        }
        if (isset($data['new_curso']) && !empty($data['new_curso'])) {
            $dataInsert['nuevo'] = 1;
        }
    
        // Guardar en la tabla de cursos
        $dataConfig = [
            "tabla" => "cursos_sac",
            "editar" => false,
        ];
    
        $result = $this->globals->saveTabla($dataInsert, $dataConfig, ["script" => "Usuario.guardarCurso"]);
    
        if (!$result->error) {
            $idRegistro = $result->idRegistro;
    
            // Guardar periodos asociados al curso
            foreach ($data['periodo'] as $p) {
                $dataInsert = [
                    'id_curso'   => $idRegistro,
                    'id_periodo' => $p,
                    'fec_reg'    => date('Y-m-d H:i:s'),
                    'usu_reg'    => $session->id_usuario,
                ];
                $dataConfig = [
                    "tabla" => "periodo_curso",
                    "editar" => false,
                ];
                $result = $this->globals->saveTabla($dataInsert, $dataConfig, ["script" => "Usuario.guardarCurso"]);
                if ($result->error) {
                    $response->error = $result->error;
                    $response->respuesta = $result->respuesta;
                    return $this->respond($response);
                }
            }
    
            // Guardar categorías asociadas al curso
            foreach ($data['categoria'] as $c) {
                $dataInsert = [
                    'id_curso'    => $idRegistro,
                    'id_categoria' => $c,
                    'fec_reg'     => date('Y-m-d H:i:s'),
                    'usu_reg'     => $session->id_usuario,
                ];
                $dataConfig = [
                    "tabla" => "categoria_curso",
                    "editar" => false,
                ];
                $result = $this->globals->saveTabla($dataInsert, $dataConfig, ["script" => "Usuario.guardarCurso"]);
                if ($result->error) {
                    $response->error = $result->error;
                    $response->respuesta = $result->respuesta;
                    return $this->respond($response);
                }
            }
    
            $response->error = false;
            $response->respuesta = "Curso guardado correctamente.";
        } else {
            $response->respuesta = $result->respuesta;
        }
    
        return $this->respond($response);
    }
    public function getCursos()
    {
        $session = \Config\Services::session();
        $response = new \stdClass();
        $response->error = true;
        $id_cat = $this->request->getPost('id_cat');
        $result = $this->globals->getTabla(["tabla"=>"cursos_sac", "where"=>["visible"=>1 ]]);
        if(!$result->error){
            $response->error      =  false;
            $response->respuesta  =  $result->respuesta;
            $response->data       =  $result->data;
        }

        return $this->respond($response->data);
    }
    public function getCategoria()
    {
        $session = \Config\Services::session();
        $response = new \stdClass();
        $response->error = true;
        $id_cat = $this->request->getPost('id_cat');
        $result = $this->globals->getTabla(["tabla"=>"categoria_sac", "where"=>["id_categoria_sac"=>$id_cat ]]);
        if(!$result->error){
            $response->error      =  false;
            $response->respuesta  =  $result->respuesta;
            $response->dsc_categoria_sac       =  $result->data[0]->dsc_categoria_sac;
            $response->id_moodle  =  $result->data[0]->id_moodle;

        }

        return $this->respond($response);
    }
    public function verDetalle()
    {
        $session = \Config\Services::session();
        $response = new \stdClass();
        $id_curso = $this->request->getPost('id_curso');
        $response->error = true;
        $response->data = []; // Inicializa un array en la propiedad 'data'

        $categoria = $this->globals->getTabla([
            "tabla" => "vw_categoria",
            "where" => ["id_curso" => $id_curso, 'visible' => 1]
        ]);

        $periodo = $this->globals->getTabla([
            "tabla" => "vw_periodo",
            "where" => ["id_curso" => $id_curso, 'visible' => 1]
        ]);
        $curso = $this->globals->getTabla([
            "tabla" => "cursos_sac",
            "where" => ["id_cursos_sac" => $id_curso, 'visible' => 1]
        ]);

        if (!$categoria->error) {
            $response->error = false;
            $response->respuesta = $categoria->respuesta;
            $response->data['categoria'] = $categoria->data; // Corrige la asignación del array
        }

        if (!$periodo->error) {
            $response->error = false;
            $response->respuesta = $periodo->respuesta;
            $response->data['periodo'] = $periodo->data; // Corrige la asignación del array
        }
        if (!$curso->error) {
            $response->error = false;
            $response->respuesta = $curso->respuesta;
            $response->data['curso'] = $curso->data; // Corrige la asignación del array
        }

        return $this->respond($response);
    }

    public function obtenerCategorias()
    {
        $session = \Config\Services::session();
        $response = new \stdClass();
        $response->error = true;
        $result = $this->globals->getTabla(["tabla"=>"categoria_sac", 'where' =>['visible' =>1]]);
        if(!$result->error){
            $response->error      =  false;
            $response->respuesta  =  $result->respuesta;
            $response->data       =  $result->data;

        }
        return $this->respond($response->data);
    }
    public function obtenerCursosSac()
    {
        $session = \Config\Services::session();
        $response = new \stdClass();
        $response->error = true;
        $result = $this->globals->getTabla(["tabla"=>"cursos_sac", 'where' => ['visible' => 1]]);
        if(!$result->error){
            $response->error      =  false;
            $response->respuesta  =  $result->respuesta;
            $response->data       =  $result->data;

        }
        return $this->respond($response->data);
    }
    public function getCursoSac()
    {
        $session = \Config\Services::session();
        $response = new \stdClass();
        $response->error = true;
        $id_curso = $this->request->getPost('id_curso');
        $cursos    = $this->globals->getTabla(["tabla"=>"cursos_sac", 'where' => ['id_cursos_sac' => $id_curso, 'visible' => 1]]);
        $categoria = $this->globals->getTabla(["tabla"=>"vw_categoria", 'where' => ['id_curso' => $id_curso, 'visible' => 1]]);
        $periodo   = $this->globals->getTabla(["tabla"=>"vw_periodo", 'where' => ['id_curso' => $id_curso, 'visible' => 1]]);
        if(!$cursos->error){
            $response->error        =  false;
            $response->respuesta    =  $cursos->respuesta;
            $response->data['curso']=  $cursos->data;
        }
        if(!$categoria->error){
            $response->error        =  false;
            $response->respuesta    =  $categoria->respuesta;
            $response->data['categoria']=  $categoria->data;
        }
        if(!$periodo->error){
            $response->error        =  false;
            $response->respuesta    =  $periodo->respuesta;
            $response->data['periodo']=  $periodo->data;
        }
        return $this->respond($response);
    }
    public function guardarCategoria()
    {
        $session = \Config\Services::session();
        $response = new \stdClass();
        $response->error = true;
        $data = $this->request->getPost();
        if(isset($data['editar']) && !empty($data['editar'])){
            if($data['editar'] == 2){
                $dataInsert=[       
                    'visible' => 0,
                ];
                $dataConfig = [
                        "tabla"=>"categoria_sac",
                        "editar"=>true,
                        "idEditar" =>["id_categoria_sac" => $data['id_cat']]
                ];
                   
            }
            if ($data['editar'] == 1) {
                // Construir el array $dataInsert de manera dinámica
                $dataInsert = [
                    'dsc_categoria_sac' => $data['comentario'],
                    'usu_act' => $session->id_usuario,
                ];
            
                // Agregar 'id_moodle' solo si está presente y no está vacío
                if (isset($data['idMoodle']) && !empty($data['idMoodle'])) {
                    $dataInsert['id_moodle'] = $data['idMoodle'];
                }
            
                // Configuración para la actualización
                $dataConfig = [
                    "tabla" => "categoria_sac",
                    "editar" => true,
                    "idEditar" => ["id_categoria_sac" => $data['id_cat']]
                ];
            }
            if($data['editar'] == 3){
                $dataInsert=[       
                    'activo' => 0,
                    'usu_act'    => $session->id_usuario,
                ];
                $dataConfig = [
                        "tabla"=>"categoria_sac",
                        "editar"=>true,
                        "idEditar" =>["id_categoria_sac" => $data['id_cat']]
                ];
                   
            }
            if($data['editar'] == 4){
                $dataInsert=[       
                    'activo' => 1,
                    'usu_act'    => $session->id_usuario,
                ];
                $dataConfig = [
                        "tabla"=>"categoria_sac",
                        "editar"=>true,
                        "idEditar" =>["id_categoria_sac" => $data['id_cat']]
                ];
                   
            }

        }else{
            $dataInsert=[       
                'dsc_categoria_sac' => $data['comentario'],
                'fec_reg'    => date('Y-m-d H:i:s'),
                'usu_reg'    => $session->id_usuario,
    
            ];
            $dataConfig = [
                    "tabla"=>"categoria_sac",
                    "editar"=>false,
                    //  "idEditar"=>['id_usuario'=>$data['id_usuario']]
            ];  
        }
        $response = $this->globals->saveTabla($dataInsert,$dataConfig,["script"=>"categoria_sac.saveCategoria"]);
        return $this->respond($response);
    }
    public function activarPeriodo()
    {
        $session = \Config\Services::session();
        $response = new \stdClass();
        $response->error = true;
        $data = $this->request->getPost();  
        if($data['id']==1){
            $dataInsert=[       
                'activo' => 0,
            ];
            $dataConfig = [
                    "tabla"=>"periodo_sac",
                    "editar"=>true,
                    "idEditar"=>['id_periodo_sac'=>$data['id_periodo']]
            ]; 
        }
        if($data['id']==2){
            $dataInsert=[       
                'activo' => 1,
            ];
            $dataConfig = [
                    "tabla"=>"periodo_sac",
                    "editar"=>true,
                    "idEditar"=>['id_periodo_sac'=>$data['id_periodo']]
            ]; 
        }
           
        
  
    $response = $this->globals->saveTabla($dataInsert,$dataConfig,["script"=>"periodo_sac.eliminarPeriodo"]);
    return $this->respond($response);
    }
    public function eliminarPeriodo()
    {
        $session = \Config\Services::session();
        $response = new \stdClass();
        $response->error = true;
        $data = $this->request->getPost();  
       
            $dataInsert=[       
                'visible' => 0,
            ];
            $dataConfig = [
                    "tabla"=>"periodo_sac",
                    "editar"=>true,
                    "idEditar"=>['id_periodo_sac'=>$data['id_periodo']]
            ]; 
        
  
    $response = $this->globals->saveTabla($dataInsert,$dataConfig,["script"=>"periodo_sac.eliminarPeriodo"]);
    return $this->respond($response);
    }
    public function guardarPeriodo()
    {
        $session = \Config\Services::session();
        $response = new \stdClass();
        $response->error = true;
        $data = $this->request->getPost();  
   
        if($data['editar_periodo'] ==1){
            $periodo = $this->globals->getTabla(["tabla"=>"periodo_sac", 'where' => ['visible' => 1, 'periodo' => $data['periodo'] ]]);
            if(isset($periodo->data) && !empty($periodo->data)){
                $response->respuesta  =  'El periodo ya existe en la base de datos';
                return $this->respond($response);
            }
      
            $dataInsert=[       
                'dia_inicio'      => $data['diaInicio'],
                'dia_fin'         => $data['diaFin'],
                'periodo'         => (int)$data['periodo'],
                'mes'             => (int)$data['mes'],
                'usu_act'         => $session->id_usuario,
    
            ];
            $dataConfig = [
                    "tabla"=>"periodo_sac",
                    "editar"=>true,
                    "idEditar"=>['id_periodo_sac'=>$data['id_periodo']]
            ]; 
        }else{
            $periodo = $this->globals->getTabla(["tabla"=>"periodo_sac", 'where' => ['visible' => 1, 'periodo' => $data['periodo'] ]]);
            $mes = $this->globals->getTabla(["tabla"=>"periodo_sac", 'where' => ['visible' => 1, 'mes' => $data['mes'] ]]);
            if(isset($periodo->data) && !empty($periodo->data)){
                $response->respuesta  =  'El periodo ya existe en la base de datos';
                return $this->respond($response);
            }
            if(isset($mes->data) && !empty($mes->data)){
                $response->respuesta  =  'El mes ya existe en la base de datos';
                return $this->respond($response);
            }
            $dataInsert=[       
                'dia_inicio'      => $data['diaInicio'],
                'dia_fin'         => $data['diaFin'],
                'periodo'         => (int)$data['periodo'],
                'mes'             => (int)$data['mes'],
                'fec_reg'         => date('Y-m-d H:i:s'),
                'usu_reg'         => $session->id_usuario,
    
            ];
            $dataConfig = [
                    "tabla"=>"periodo_sac",
                    "editar"=>false,
                    //  "idEditar"=>['id_usuario'=>$data['id_usuario']]
            ]; 
        }
      
    
  
    $response = $this->globals->saveTabla($dataInsert,$dataConfig,["script"=>"periodo_sac.savePeriodo"]);
    return $this->respond($response);
    }
    public function optenerPeriodo()
    {
        $session = \Config\Services::session();
        $response = new \stdClass();
        $response->error = true;
        $id_periodo = $this->request->getPost('id_periodo');  
        if(isset($id_periodo) && !empty($id_periodo)){
            $result = $this->globals->getTabla(["tabla"=>"vw_periodo", 'where' => ['visible' => 1, 'id_periodo_sac' => $id_periodo ]]);
            if(!$result->error){
                $response->error      =  false;
                $response->respuesta  =  $result->respuesta;
                $response->data       =  $result->data;
    
            }
            return $this->respond($response->data[0]);
        }
 
    }
    public function getPeriodos()
    {
        $session = \Config\Services::session();
        $response = new \stdClass();
        $response->error = true;
        $data = $this->request->getPost();  
        $result = $this->globals->getTabla(["tabla"=>"periodo_sac", 'where' => ['visible' => 1]]);
        if(!$result->error){
            $response->error      =  false;
            $response->respuesta  =  $result->respuesta;
            $response->data       =  $result->data;

        }
        return $this->respond($response->data);
    }
    public function getSelectPeriodos()
    {
        $session = \Config\Services::session();
        $response = new \stdClass();
        $response->error = true;
        $data = $this->request->getPost();  
        $result = $this->globals->getTabla(["tabla"=>"periodo_sac", 'where' => ['visible' => 1]]);
        if(!$result->error){
            $response->error           =  false;
            $response->respuesta       =  $result->respuesta;
            $response->data['periodo'] =  $result->data;

        }
        $result = $this->globals->getTabla(["tabla"=>"categoria_sac", 'where' => ['visible' => 1]]);
        if(!$result->error){
            $response->error             =  false;
            $response->respuesta         =  $result->respuesta;
            $response->data['categoria'] =  $result->data;

        }
        return $this->respond($response->data);
    }
    public function activarCursoSac()
    {
        $session = \Config\Services::session();
        $response = new \stdClass();
        $response->error = true;
        $id_curso_sac = $this->request->getPost('id_curso_sac');
        $editar = $this->request->getPost('editar');
        if($editar == 3){
            $dataInsert=[       
                'activo' => 0,
                'usu_act'=>$session->id_usuario
            ];
            $dataConfig = [
                    "tabla"=>"cursos_sac",
                    "editar"=>true,
                    "idEditar" =>["id_cursos_sac" => $id_curso_sac]
            ];
        }else{
            $dataInsert=[       
                'activo' => 1,
                'usu_act'=>$session->id_usuario
            ];
            $dataConfig = [
                    "tabla"=>"cursos_sac",
                    "editar"=>true,
                    "idEditar" =>["id_cursos_sac" => $id_curso_sac ]
            ];
        }
        $result = $this->globals->saveTabla($dataInsert,$dataConfig,["script"=>"categoria_sac.saveCategoria"]);
        if(!$result->error){
            $response->error      =  false;
            $response->respuesta  =  $result->respuesta;

        }
        return $this->respond($response);
    }
    public function guardarCursoSac()
    {
        $session = \Config\Services::session();
        $response = new \stdClass();
        $response->error = true;
        $data = $this->request->getPost();
        if(isset($data['editar']) && !empty($data['editar'])){
            if($data['editar'] == 2){
                $dataInsert=[       
                    'visible' => 0,
                    'usu_act' => $session->id_usuario
                ];
                $dataConfig = [
                        "tabla"=>"cursos_sac",
                        "editar"=>true,
                        "idEditar" =>["id_cursos_sac" => $data['id_cat']]
                ];
                   
            }
            if($data['editar'] == 1){
                $dataInsert=[       
                    'id_curso_sac'    => (int)$data['categoria'],
                    'dsc_curso_sac'   => $data['nombre_curso'],
                    'id_moodle'         => $data['id_moodle'],
                    'usu_act'           => $session->id_usuario,
                  
        
                ];
                $dataConfig = [
                        "tabla"=>"cat_curso_sac",
                        "editar"=>true,
                        "idEditar" =>["id_curso_sac" => $data['editar_curso']]
                ];
                   
            }
            if($data['editar'] == 3){
                $dataInsert=[       
                    'visible' => 1,
                    'usu_act'    => $session->id_usuario,
                ];
                $dataConfig = [
                        "tabla"=>"cat_curso_sac",
                        "editar"=>true,
                        "idEditar" =>["id_curso_sac" => $data['id_cat']]
                ];
                   
            }

        }else{
            $dataInsert=[       
                'dsc_categoria_sac' => $data['comentario'],
                'fec_reg'    => date('Y-m-d H:i:s'),
                'usu_reg'    => $session->id_usuario,
    
            ];
            $dataConfig = [
                    "tabla"=>"cat_curso_sac",
                    "editar"=>false,
                    //  "idEditar"=>['id_usuario'=>$data['id_usuario']]
            ];  
        }
      
        $response = $this->globals->saveTabla($dataInsert,$dataConfig,["script"=>"categoria_sac.saveCategoria"]);
        return $this->respond($response);
    }
    public function UpdateUsuario()
    {
        $response = new \stdClass();
        $response->error = true;
        $data = $this->request->getPost();
        // var_dump(isset($data['editar']));
        // die();
        
        $dataInsert=[       
            'usuario' => $data['usuario'],
            'contrasenia' => md5($data['contrasenia']),
            'correo' => $data['correo'],
            'id_perfil' => $data['perfil'],
            'id_sexo' => $data['sexo'],
            'nombre' =>$data['nombre'],
            'primer_apellido' => $data['primer_apellido'],
            'segundo_apellido' => $data['segundo_apellido'],
            'id_clues' => $data['id_clues'],
        ];
        // var_dump($dataInsert);
        // die();
        if (isset($data['editar'])){
            $dataConfig = [
                "tabla"=>"seg_usuarios",
                "editar"=>false,
                //  "idEditar"=>['id_usuario'=>$data['id_usuario']]
            ];  
        }else{
            $dataConfig = [
                "tabla"=>"seg_usuarios",
                "editar"=>true,
                 "idEditar"=>['id_usuario'=>$data['id_usuario']]
            ];
        }
        

        $response = $this->globals->saveTabla($dataInsert,$dataConfig,["script"=>"Usuario.saveUsuario"]);
        return $this->respond($response);
    }
    public function saveUsuario()
    {
        $response = new \stdClass();
        $response->error = true;
        $data = $this->request->getPost();
        var_dump($data['id_usuario']);
        die();
        // if (!isset($data['id_usuario']) || empty($data['id_usuario'])){
        //     $response->respuesta = "No se ha proporcionado un identificador válido";
        //     return $this->respond($response);
        // }
        // $dataInsert=[
        //     'dsc_carpeta'          => $dsc_carpeta,
        //     'id_carpeta_padre'  => $id_carpeta_raiz,
        //     'id_unidad'           => $id_unidad,
        //     'ruta'           => $ruta_raiz.'/'.$nombre_unix,
        //     'ruta_real'       => $ruta_carpeta_fisica,
        //     'fecha_registro'       => date('Y-m-d H:i:s'),
        //     'usuario_registro' => $session->id_usuario,
        //     'visible'     => 1,
        //     'nombre_carpeta'     => $nombre_unix
        // ];

        $dataConfig = [
            "tabla"=>"seg_usuarios",
            "editar"=>false,
            // "idEditar"=>['id_usuario'=>$data['id_usuario']]
        ];
        $response = $this->globals->saveTabla($dataInsert,$dataConfig,["script"=>"Usuario.saveUsuario"]);
        return $this->respond($response);
    }
    
}