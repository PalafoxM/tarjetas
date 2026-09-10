<?php

namespace App\Controllers;

use App\Libraries\Funciones;
use CodeIgniter\API\ResponseTrait;

class ConsultaSaldo extends BaseController
{
    use ResponseTrait;

    private array $defaultData = [
        'title' => 'Consulta de saldo',
        'layout' => 'plantilla/lytLogin',
        'contentView' => 'vUndefined',
        'stylecss' => '',
    ];

    private function renderView(array $data = [])
    {
        $data = array_merge($this->defaultData, $data);
        return view($data['layout'], $data);
    }

    public function index()
    {
        return $this->renderView([
            'contentView' => 'secciones/vConsultaSaldo',
        ]);
    }

    public function consultar()
    {
        $folio = trim((string) $this->request->getPost('folio'));
        $invalidFolioMessage = 'Ingresa un folio válido de 3 dígitos, por ejemplo: 016.';

        if (!preg_match('/^\d{3}$/', $folio)) {
            return $this->respond([
                'error' => true,
                'message' => $invalidFolioMessage,
            ], 400);
        }

        $baseUrl = env('BACK_STI_API_BASE_URL') ?: env('NODE_API_BASE_URL');
        $baseUrl = rtrim((string) $baseUrl, '/') . '/';

        if ($baseUrl === '/') {
            log_message('error', 'ConsultaSaldo.consultar: BACK_STI_API_BASE_URL/NODE_API_BASE_URL no configurado.');
            return $this->respond([
                'error' => true,
                'message' => 'No fue posible consultar el saldo en este momento. Inténtalo nuevamente más tarde.',
            ], 503);
        }

        try {
            $token = $this->buildBackStiToken();
            if ($token === '') {
                log_message('error', 'ConsultaSaldo.consultar: TOKEN_API no configurado.');
                return $this->respond([
                    'error' => true,
                    'message' => 'No fue posible consultar el saldo en este momento. Inténtalo nuevamente más tarde.',
                ], 503);
            }

            $client = \Config\Services::curlrequest([
                'timeout' => 12,
                'http_errors' => false,
            ]);

            $apiResponse = $client->post($baseUrl . 'api/consulta-saldo', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ],
                'json' => ['folio' => $folio],
            ]);

            $statusCode = $apiResponse->getStatusCode();
            $result = json_decode((string) $apiResponse->getBody(), true);

            if (!is_array($result)) {
                throw new \RuntimeException('Respuesta inválida de backSti.');
            }

            if ($statusCode >= 400 || !empty($result['error']) || (isset($result['ok']) && $result['ok'] === false)) {
                return $this->respondBackStiError($statusCode, $result, $folio);
            }

            $saldo = $this->resolveSaldo($result);
            if ($saldo === null) {
                log_message('warning', 'ConsultaSaldo.consultar: backSti no devolvió saldo usable para folio ' . $folio);
                return $this->respond([
                    'error' => true,
                    'message' => 'No encontramos información para ese folio. Verifica el dato e inténtalo nuevamente.',
                ], 404);
            }

            return $this->respond([
                'error' => false,
                'saldo' => round($saldo, 2),
                'saldo_formateado' => '$' . number_format($saldo, 2, '.', ','),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'ConsultaSaldo.consultar: ' . $e->getMessage());
            return $this->respond([
                'error' => true,
                'message' => 'No fue posible consultar el saldo en este momento. Inténtalo nuevamente más tarde.',
            ], 502);
        }
    }

    private function buildBackStiToken(): string
    {
        if (trim((string) env('TOKEN_API')) === '') {
            return '';
        }

        $jwt = new Funciones();
        return $jwt->generateToken([
            'id' => 0,
            'nombre' => 'Consulta publica de saldo',
            'scope' => 'consulta_saldo',
        ]);
    }

    private function respondBackStiError(int $statusCode, array $result, string $folio)
    {
        $message = (string) ($result['message'] ?? $result['mensaje'] ?? $result['respuesta'] ?? '');

        if ($statusCode === 400) {
            return $this->respond([
                'error' => true,
                'message' => $message !== '' ? $message : 'Ingresa un folio válido de 3 dígitos, por ejemplo: 016.',
            ], 400);
        }

        if ($statusCode === 401 || $statusCode === 403) {
            log_message('error', 'ConsultaSaldo.consultar: backSti rechazó autenticación del puente interno.');
            return $this->respond([
                'error' => true,
                'message' => 'No fue posible consultar el saldo en este momento. Inténtalo nuevamente más tarde.',
            ], 503);
        }

        if ($statusCode === 404 || $this->isNotFoundResponse($result)) {
            return $this->respond([
                'error' => true,
                'message' => 'No encontramos información para ese folio. Verifica el dato e inténtalo nuevamente.',
            ], 404);
        }

        if ($statusCode === 409) {
            log_message('warning', 'ConsultaSaldo.consultar: folio duplicado en backSti para folio ' . $folio);
            return $this->respond([
                'error' => true,
                'message' => 'No fue posible consultar ese folio. Solicita ayuda para revisar tu información.',
            ], 409);
        }

        if ($statusCode === 429) {
            return $this->respond([
                'error' => true,
                'message' => 'Se alcanzó el límite de consultas. Inténtalo nuevamente en unos minutos.',
            ], 429);
        }

        if ($statusCode >= 500) {
            log_message('warning', 'ConsultaSaldo.consultar: backSti no disponible para folio ' . $folio);
            return $this->respond([
                'error' => true,
                'message' => 'No fue posible consultar el saldo en este momento. Inténtalo nuevamente más tarde.',
            ], 502);
        }

        log_message('warning', 'ConsultaSaldo.consultar: respuesta no exitosa de backSti para folio ' . $folio);
        return $this->respond([
            'error' => true,
            'message' => 'No fue posible consultar el saldo en este momento. Inténtalo nuevamente más tarde.',
        ], 502);
    }

    private function resolveSaldo(array $result): ?float
    {
        $sources = [$result];

        foreach (['data', 'respuesta', 'result'] as $key) {
            if (isset($result[$key])) {
                $sources[] = $result[$key];
            }
        }

        foreach ($sources as $source) {
            if (is_array($source) && $this->isList($source) && isset($source[0]) && is_array($source[0])) {
                $sources[] = $source[0];
                continue;
            }

            if (!is_array($source)) {
                continue;
            }

            foreach (['saldo', 'saldo_disponible', 'monto_deposito_operativo', 'monto_deposito', 'balance', 'available_balance'] as $key) {
                if (array_key_exists($key, $source) && is_numeric($source[$key])) {
                    return (float) $source[$key];
                }
            }
        }

        return null;
    }

    private function isNotFoundResponse(array $result): bool
    {
        $message = strtolower((string) ($result['message'] ?? $result['mensaje'] ?? $result['respuesta'] ?? ''));

        return strpos($message, 'no encontrado') !== false
            || strpos($message, 'no existe') !== false
            || strpos($message, 'not found') !== false;
    }

    private function isList(array $value): bool
    {
        if ($value === []) {
            return true;
        }

        return array_keys($value) === range(0, count($value) - 1);
    }
}
