<?php
namespace App\Libraries;
date_default_timezone_set('America/Mexico_City');// Zona horaria de Mexico
use DateTime;
use stdClass;

class Globals {    
  
    /**
     *  Función de petición de información al api
     * 
     *  @param array:formParam   Función de configuración de petición de información
     *  @return object queryResult
        @array:formParam[
            dataBase    string
            query       string  (opcional)
            tabla       string  (requerido si no existe el arámetro de query)
            select      array (opcional)
            join        array[array] (opcional)
            where       array (opcional)
            whereIn     array (opcional)
            like        array (opcional)
            orlike      array (opcional)
            order       string (opcional)
            groupBy     array  (opcional)
            limit       int (opcional) : array [(int)start,(int)length]
        ]
     */
    public function getTabla($fromParams)
    {
        $session    = \Config\Services::session(); 
        $client     = \Config\Services::curlrequest();
        $config     = config('AuthConfig');

        //Consulta perfiles por usuario
        $request = $client->request('POST', $config->base_url_cima.'con_global/getTabla', [
            'auth' => [$config->auth_user, $config->auth_pass,'basic'],
            'headers' => [$config->auth_token => $config->auth_token_pass],
            'form_params' => $fromParams
        ]);
        $request = json_decode($request->getBody());

        if(isset($request->code) && $request->code != 200)
        {
            $request->error=true;
            $request->respuesta=$request->message;
        }
        
        return $request;
    }
    
    /** 
     *  Función para insertar o editar información de una sola tabla
     *  @param array:dataInsert     información a registrar en la tabla del catálogo (No incluye id)
     *  @param array:dataConfig     información de configuración para la query de edición o inserción
     *  @param array:dataBitacora   información de registro de bitacora
     *  @return object:result       Información resultante de la transacción
        @array:dataInsert() = [
            'nombreDataBase' => valueDataBase
        ]
        @array:dataConfig = [
            dataBase    string
            tabla       string
            editar      bool
            editar_id   array['idNombre'=>id] (opcional si editar==true)
        ]
        @array:dataBitacora = [
            script      string
        ]
    */
    public function saveTabla($dataInsert, $dataConfig, $dataBitacora)
    {
        $session    = \Config\Services::session(); 
        $client     = \Config\Services::curlrequest();
        $config     = config('AuthConfig');
        $response  = new \stdClass();
        $response->error = true;
        $response->respuesta = 'Error|Parametros de configuración';

        if (empty($dataInsert)) 
            return $response;
        if (empty($dataConfig) || !isset($dataConfig['dataBase']) || !isset($dataConfig['tabla']) || !isset($dataConfig['editar'])) 
            return $response;
        if (empty($dataBitacora) || !isset($dataBitacora['script']) ) 
            return $response;
            
        //Consulta perfiles por usuario
        $request = $client->request('POST', $config->base_url_cima . 'con_global/saveTabla', [
            'auth' => [$config->auth_user, $config->auth_pass, 'basic'],
            'headers' => [$config->auth_token => $config->auth_token_pass],
            'form_params' => [
                'dataInsert' => $dataInsert,
                'dataConfig' => $dataConfig,
                'dataBitacora' => ['id_user' => $session->get('id_usuario'), 'script' => $dataBitacora['script']]
            ],
        ]);
        $request = json_decode($request->getBody());

        if(isset($request->code) && $request->code != 200)
        {
            $request->error=true;
            $request->respuesta=$request->message;
        }       
        return $request;
    }
    
    /** 
     *  Función para insertar un arreglo de información en una tabla
     *  @param array:dataInsert     información a registrar en la tabla del catálogo (No incluye id)
     *  @param array:dataConfig     información de configuración para la query de edición o inserción
     *  @return object:result       Información resultante de la transacción
        @array:dataInsert() = [
            'nombreDataBase' => valueDataBase
        ]
        @array:dataConfig = [
            dataBase    string
            tabla       string
        ]
    */
    public function dataInsertBatch($dataInsert, $dataConfig)
    {
        $session    = \Config\Services::session(); 
        $client     = \Config\Services::curlrequest();
        $config     = config('AuthConfig');
        $response  = new \stdClass();
        $response->error = true;
        $response->respuesta = 'Error|Parametros de configuración';

        if (empty($dataInsert)) 
            return $response;
        if (empty($dataConfig) || !isset($dataConfig['dataBase']) || !isset($dataConfig['tabla']) || !isset($dataConfig['editar'])) 
            return $response;
            
        //Consulta perfiles por usuario
        $request = $client->request('POST', $config->base_url_cima . 'con_global/dataInsertBatch', [
            'auth' => [$config->auth_user, $config->auth_pass, 'basic'],
            'headers' => [$config->auth_token => $config->auth_token_pass],
            'form_params' => [
                'dataInsert' => $dataInsert,
                'dataConfig' => $dataConfig,
            ],
        ]);
        $request = json_decode($request->getBody());

        if(isset($request->code) && $request->code != 200)
        {
            $request->error=true;
            $request->respuesta=$request->message;
        }       
        return $request;
    }

    /**
     * Función que manda información mediante POST a cualquier controlador de cima_api
     * 
     * @param string:urlController  Ruta a partir del nombre del controlador
     * @param array:objeto:data     Información a enciar al api
     * @param array:dataBitacora    Información del script
     * @param string:method         (opcional) tipo de petición default="POST"
       $dataBitacora['script'=>"nombreControlador.php"] 
     */
    public function request($urlCotroller,$data, $dataBitacora, $method = "POST")
    {
        $session    = \Config\Services::session(); 
        $client     = \Config\Services::curlrequest();
        $config     = config('AuthConfig');
        $response   = new \stdClass();
        $response->error = true;
        $response->respuesta = 'Error|Globals|Parametros de configuración';

        if (empty($urlCotroller)) {
            $response->respuesta .= '|Url';
            return $response;
        }
        if (empty($data)) {
            $response->respuesta .= '|Data';
            return $response;
        }
        if (empty($dataBitacora) || !isset($dataBitacora['script']) ) {
            $response->respuesta .= '|DataBitacora';
            return $response;
        }
        
        //Consulta perfiles por usuario
        $request = $client->request($method, $config->base_url_cima . $urlCotroller, [
            'auth' => [$config->auth_user, $config->auth_pass, 'basic'],
            'headers' => [$config->auth_token => $config->auth_token_pass],
            'form_params' => [
                'data' => $data,
                'dataBitacora' => ['id_user' => $session->get('id_usuario'), 'script' => $dataBitacora['script']]
            ],
        ]);
        $request = json_decode($request->getBody());

        if(isset($request->code) && $request->code != 200)
        {
            $request->error=true;
            $request->respuesta=$request->message;
        }       
        return $request;
    }
    
    /**
     * Función que manda información mediante POST a cualquier controlador de cima_api
     * 
     * @param string:urlController  Ruta de conexión
     * @param array:data            arreglo asociado de inforación para mandar
     * @param string:method         (opcional) tipo de petición default="POST"
     */
    public function requestExterno($urlCotroller,$data,$method = "POST",$ssl = false)
    {
        $session    = \Config\Services::session(); 
        $client     = \Config\Services::curlrequest();
        $config     = config('AuthConfig');
        $response   = new \stdClass();
        $response->error = true;
        $response->respuesta = 'Error|Globals|Parametros de configuración';

        if (empty($urlCotroller)) {
            $response->respuesta .= '|Url';
            return $response;
        }
        if (empty($data)) {
            $response->respuesta .= '|Data';
            return $response;
        }
        
        //Consulta perfiles por usuario
        $request = $client->request($method, $urlCotroller, [
            'auth' => [$config->auth_user, $config->auth_pass, 'basic'],
            'headers' => [$config->auth_token => $config->auth_token_pass],
            'headers' => [$config->auth_token => $config->auth_token_pass],
            'verify' => $ssl,
            'form_params' => $data,
        ]);
        $request = json_decode($request->getBody());

        if(isset($request->code) && $request->code != 200)
        {
            $request->error=true;
            $request->respuesta=$request->message;
        }       
        return $request;
    }

    /**
     *  Función que guarda la bitacora de las vistas consultadas por un usuario
     */
    public function saveBitacoraVista($url, $view)
    {
        $session = \Config\Services::session();
        $config = config('AuthConfig');
        $client = \Config\Services::curlrequest();
		
        try {
			if (isset($session->id_usuario) && $_SERVER['REQUEST_METHOD'] == "GET" && !strpos(strtolower($_SERVER['REQUEST_URI']),'checar_sesion')){
				$request = $client->request('POST', $config->base_url_cima . 'con_global/saveBitacoraVista', [
					'auth' => [$config->auth_user, $config->auth_pass, 'basic'],
					'headers' => [$config->auth_token => $config->auth_token_pass],
					'form_params' => [
						'dataInsert' => ['id_usuario'=>$session->id_usuario,'url'=>$url, 'vista'=>$view]
					],
				]);
                $request = json_decode($request->getBody());

                if(isset($request->code) && $request->code != 200)
                {
                    $request->error=true;
                    $request->respuesta=$request->message;
                }       
			}
            else{
                $request  = new \stdClass();
                $request->error = false;
                $request->respuesta = 'Sin incersión' ;    
            }
		} catch (\Throwable $th) {
			log_message('critical','Error|Registro Bitacor Vista|'.json_encode(['id_usuario'=>$session->get('id_usuario'),'url'=>$_SERVER['REQUEST_URI']]));
            $request  = new \stdClass();
            $request->error = true;
            $request->respuesta = (string)$th ;
		}
        return $request;
    }

}
