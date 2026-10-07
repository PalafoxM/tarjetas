<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class CsrfTokenResponse implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // La validación la realiza el filtro csrf de CI4.
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ) {
        $method = strtoupper($request->getMethod());

        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        $response->setHeader(csrf_header(), csrf_hash());
        $response->setHeader('Cache-Control', 'no-store');

        return $response;
    }
}
