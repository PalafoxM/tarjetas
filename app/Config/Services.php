<?php

namespace Config;

use CodeIgniter\HTTP\CURLRequest;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\URI;
use CodeIgniter\Config\BaseService;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    /*
     * public static function example($getShared = true)
     * {
     *     if ($getShared) {
     *         return static::getSharedInstance('example');
     *     }
     *
     *     return new \CodeIgniter\Example();
     * }
     */
    public static function curlrequest(array $options = [], ?ResponseInterface $response = null, ?App $config = null, bool $getShared = true)
    {
        if ($getShared === true) {
            return static::getSharedInstance('curlrequest', $options, $response, $config);
        }

        $sslVerify = env('BACK_STI_SSL_VERIFY');
        if ($sslVerify !== null && ! array_key_exists('verify', $options)) {
            $options['verify'] = ENVIRONMENT === 'production'
                ? true
                : filter_var($sslVerify, FILTER_VALIDATE_BOOLEAN);
        }

        $config ??= config(App::class);
        $response ??= new Response($config);

        return new CURLRequest(
            $config,
            new URI($options['base_uri'] ?? null),
            $response,
            $options
        );
    }
}
