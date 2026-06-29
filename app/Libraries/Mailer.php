<?php
namespace App\Libraries;

use App\Models\Mglobal;

setlocale(LC_TIME, 'es_ES.utf8', 'es_MX.UTF-8', 'es_MX', 'esp_esp', 'Spanish'); // usar solo LC_TIME para evitar que los decimales los separe con coma en lugar de punto y fallen los inserts de peso y talla
date_default_timezone_set('America/Mexico_City');

class Mailer {

    /**
     * Envia correo, requiere por default body, usuario y destinatarios
     * @param  string   $body         [mensaje]
     * @param  int      $user         [id_usuario]
     * @param  boolean  $recipients   [uno solo o array de destinatarios ['a@a.com','b@b.com',...,N]]
     * @param  int      $type         [tipo (1 - template default, 2 - custom, 3 - solo imagen)]
     * @param  boolean  $view         [view ya renderizado]
     * @param  boolean  $attachments  [idem, array de ruta real ie ['C:/path/to/file.foo','/path/to/file.bar',...,N]
     * @param  boolean  $title        [titulo del correo]
     * @param  boolean  $header       [idem]
     * @param  boolean  $footer       [idem]
     * @param  boolean  $name         [del correo]
     * @return [boolean trace]        [true, err stack]
     */
    function send(
        $body, 
        $user, 
        $recipients  = false, 
        $type        = 1,
        $view        = false, 
        $attachments = false, 
        $title       = false, 
        $header      = false, 
        $footer      = false, 
        $name        = false)
    {
        if (!is_array($recipients) && $recipients) {
            return "Sin destinatarios";
        }

        // default
        if ($type == 1 ) 
            $return = self::default_template(
                $title,
                $name,
                $body,
                $recipients,
                $attachments,
                $header
            );

        //custom
        if ($type == 2 ) 
            $return = self::custom_template(
                $title,
                $name,
                $body,
                $recipients,
                $attachments,
                $header
            );

        //boletin
        if ($type == 3 ) 
            $return = self::boletin(
                $title,
                $name,
                $recipients,
                $attachments
            );

        $query = new Mglobal();

        if ($return === true){
            // on success
            $dataInsert = [
                // 'enviado'            => time(),
                'fecha_envio'        => date('Y-m-d H:mm:ss', time()),
                'id_usuario_envio'   => $user,
                'remitente'          => 'soporteti_isapeg@guanajuato.gob.mx',
                'destinatario'       => json_encode($recipients),
                'asunto'             => $title,
                'mensaje'            => json_encode($body),
                'adjunto'            => json_encode($attachments),
                'formato'            => 'html'
            ];
            $dataConfig = [
                'tabla'     => 'correos_enviados',
                'editar'    => false
            ];
            $status = $query->saveTabla($dataInsert,$dataConfig,['script'=>'Mailer.envio']);

            return true;
        }
        else {
            // on fail
            
            // $dataInsert = [
            //     'id_estatus_trabajo' => -2,
            //     'trace'              => json_encode($email->printDebugger(['headers']))
            // ];
            // $dataConfig = [
            //     'dataBase'  => 'saeg',
            //     'tabla'     => 'trabajo_zip',
            //     'editar'    => true,
            //     'idEditar'  => ['id_trabajo' => $trabajo_pendiente->id_trabajo]
            // ];
            // $status = $query->saveTabla($dataInsert,$dataConfig,['script'=>'Mailer.envio_err'] );

            return $return;
        }
    }

    private function default_template($title, $name, $body, $recipients, $attachments, $header)
    {
        $title  = ($title) ? $title : "Notificaciones DTIC";
        $name   = ($name) ? $name : "Notificaciones DTIC";
        $body   = self::template($title, $body);
        $email  = \Config\Services::email();
        $email->clear();
        $email->setSubject($title);
        $email->setFrom('soporteti_isapeg@guanajuato.gob.mx', $name);

        foreach ($recipients as $recipient) {
            $email->setTo($recipients);
        }

        if ($attachments) {
            foreach ($attachments as $attachment) {
                $email->attach($attachment);
            }
        }

        if ($header)
            $email->setHeader($header);

        $email->setMessage($body);

        if ($email->send()){
            return true;
        }
        else{
            return $email->printDebugger(['headers']);
        }       
    }

    private function custom_template($title, $name, $body, $recipients, $attachments, $header)
    {
        $title = ($title) ? $title : "Notificaciones DTIC";
        $name  = ($name) ? $name : "Notificaciones DTIC";
        $body  = ($footer) ? $body.$footer : $body;

        $email = \Config\Services::email();
        $email->clear();
        $email->setSubject($title);
        $email->setFrom('soporteti_isapeg@guanajuato.gob.mx', $name);

        foreach ($recipients as $recipient) {
            $email->setTo($recipients);
        }
        
        if ($attachments) {
            foreach ($attachments as $attachment) {
                $email->attach($attachment);
            }
        }

        if ($header)
            $email->setHeader($header);

        // if ($view)
        //     $email->setMessage($view);
        // else
            $email->setMessage($body);

        if ($email->send()){
            return true;
        }
        else{
            return $email->printDebugger(['headers']);
        }
    }

    private function boletin($title, $name, $recipients, $attachments)
    {
        $title = ($title) ? $title : "Notificaciones DTIC";
        $name  = ($name) ? $name : "Notificaciones DTIC";

        $email = \Config\Services::email();
        $email->clear();
        $email->setSubject($title);
        $email->setFrom('soporteti_isapeg@guanajuato.gob.mx', $name);

        foreach ($recipients as $recipient) {
            $email->setTo($recipients);
        }        
        
        $message ='';

        foreach ($attachments as $attachment) {
            $email->attach($attachment);
            $cid   = $email->setAttachmentCID($attachment);
            $message .= "<img style=\"max-width: 900px;\" src=\"cid:".$cid."\" alt=\"image\"/>";
        }

        $email->setMessage($message);

        if ($email->send()){
            return true;
        }
        else{
            return $email->printDebugger(['headers']);
        }
    }

    public function template($title,$body)
    {
        
        $template = "
        <html>
            <head>
                <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
            </head>
            <body>
                <table border='0' width='702'>
                    <tr>
                        <td height='25'>&nbsp;</td><td height='652'>
                            <table border='0' width='652'>
                                <tr>
                                    <td height='35' width='652'>
                                        <h1>{$title}</h1>
                                    </td>
                                </tr>
                                <tr>
                                    <td height='35' width='652'>
                                        &nbsp;
                                    </td>
                                </tr>
                                <tr>
                                    <td height='25'>&nbsp;</td>
                                </tr>
                                <tr>
                                    <td style='font-family:Helvetica,Arial,sans-serif; padding: 4px; font-size: 14px; color: #333' height='5' width='652'>
                                        <b>{$body}</b>
                                    </td>
                                </tr>
                                <tr>
                                    <td width='652' align='middle'>
                                        <table cellspacing='0' cellpadding='0' width='652' border='0'>
                                            <tr>
                                                <td height='10'></td>
                                            </tr>
                                            <tr>
                                                <td colspan='2' align='left' style='font-family: Helvetica,Arial,sans-serif; padding: 8px; font-size: 11px; color: #004785;padding-top: 20px;'>
                                                    <p>
                                                        Para consultas y reporte de problemas puede dirigirse al correo <a href='mailto:soporteti_isapeg@guanajuato.gob.mx'>soporteti_isapeg@guanajuato.gob.mx</a><br>
                                                        Realice su consulta proporcionando los siguientes datos:<br>
                                                        <ul>
                                                            <li><b>Correo:</b><br></li>
                                                            <li><b>Nombre Completo:</b><br></li>
                                                            <li><b>Problematica:</b><br></li>
                                                        </ul>
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td height='10'></td>
                                            </tr>
                                            <tr>
                                                <td colspan='2' align='left' style='font-family: Helvetica,Arial,sans-serif; padding: 8px; font-size: 10px; color: #004785;'>
                                                    <b>Este es un correo generado automaticamente por el sistema SAEG - Sistema de Auditorias del Estado de Guanajuato.<br>
                                                    <b>Número de contacto: (473) 735-2700 ext.: 140<br>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td height='10'></td>
                                </tr>
                                <tr>
                                    <td height='25' style='font-family: Helvetica,Arial,sans-serif; padding: 8px; font-size: 10px; color: #004785;'>
                                        Dirección de Tecnologías de la Información y Comunicaciones DTIC<br>
                                        Dirección General de Planeación y Desarrollo DGPyD<br>
                                        Instituto de Salud Pública del Estado de Guanajuato ISAPEG
                                    </td>
                                </tr>
                            </table>
                        </td><td height='25'>&nbsp;</td>
                    </tr>
                </table>
            </body>
        </html>
       ";
       return $template;
    }

    /**
     * Funcion que manda correos default del sistema
     * @param array:destinatario    ["correo","correo"] (opcional Se mandará al usuario logueado)
     * @param int:string:mensaje    int: Mensaje definido en el sistema, string: Mensaje customizado
     * @param string:asunto         "Asunto" (opcional)
     */
    public function mailSAEG($mensaje = false, $destinatario = false, $asunto = false)
    {
        $Mglobal = new Mglobal();
        $session = \Config\Services::session();
        $destinatario = ($destinatario)? $destinatario: [$Mglobal->getTabla(['select' => ["correo"],'tabla'=>'usuario', 'where'=>['id_usuario'=>$session->id_usuario] ])->data[0]->correo];
        $asunto = ($asunto)? $asunto:"[SAEG] Notificación del sistema";

        switch ($mensaje) {
            case false: 
                $mensaje = "Correo de prueba"; 
                break;
            case 1:
                $mensaje = "Asignación de Auditoría. “Se le ha asignado una Auditoría para su análisis y atención, mediante la
                Plataforma de Auditorías SAEG, puede ingresar en la siguiente liga: <br> http://salud1.guanajuato.gob.mx:8080/sass";
                break;
            case 2:
                $mensaje = "Requerimiento. “Tiene una solicitud de información pendiente, mediante la Plataforma de Auditorías SAEG, para atender el requerimiento ingrese a la siguiente liga con su usuario y contraseña <br> http://salud1.guanajuato.gob.mx:8080/sass";
                break;
            case 3:
                $mensaje = "Mensaje Equipo DSA. “Le han enviado un mensaje mediante la Plataforma de Auditorías SAEG,
                para consultarlo ingresa a la Plataforma de Auditorías SAEG, en la siguiente liga:” (Si es posible
                mencionar el usuario que envía el mensaje, o alguna otra referencia, puede ser el número de la
                Auditoría) <br> http://salud1.guanajuato.gob.mx:8080/sass";
                break;
            case 4:
                $mensaje = "Mensaje al equipo DSA a la UR. “Le han enviado un mensaje mediante la Plataforma de Auditorías
                SAEG, para consultarlo ingrese a la Plataforma de Auditorías SAEG, en la siguiente liga:” (Si es
                posible mencionar el usuario que envía el mensaje) <br> http://salud1.guanajuato.gob.mx:8080/sass";
                break;
            case 5:
                $mensaje = "Mensaje al Equipo DSA cuando la UR responda solicitud. La Unidad responsable ha respondido su
                solicitud de información, mediante la Plataforma de Auditorías, para consultar la información
                ingrese a la Plataforma de Auditorías en la siguiente liga: (Si es posible mencionar el usuario que
                envía el mensaje) <br> http://salud1.guanajuato.gob.mx:8080/sass";
                break;
            
            default:
                $mensaje = $mensaje;
                break;
        }

        $mail = self::send($mensaje,$session->id_usuario,$destinatario,1,false,false,$asunto);
        return $mail;
    }
}