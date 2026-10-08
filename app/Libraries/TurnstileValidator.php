<?php

namespace App\Libraries;

use Config\Services;
use Throwable;

final class TurnstileValidator
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    private const MAX_TOKEN_LENGTH = 2048;

    private string $secretKey;
    private $client;

    public function __construct(?string $secretKey = null, $client = null)
    {
        $this->secretKey = trim($secretKey ?? (string) env('TURNSTILE_SECRET_KEY'));
        $caBundle = trim((string) env('TURNSTILE_CA_BUNDLE'));
        $clientOptions = ['verify' => true];
        if ($caBundle !== '' && is_file($caBundle) && is_readable($caBundle)) {
            // CI 4.4.3 consulta ssl_key al recibir una ruta en verify.
            $clientOptions['verify'] = $caBundle;
            $clientOptions['ssl_key'] = $caBundle;
        }
        $this->client = $client ?? Services::curlrequest(
            $clientOptions,
            null,
            null,
            false
        );
    }

    /**
     * @return array{success: bool, unavailable: bool, errorCodes: array<int, string>}
     */
    public function validate(string $token, ?string $remoteIp = null): array
    {
        $token = trim($token);

        if ($this->secretKey === '') {
            return $this->failure(['missing-secret-key'], true);
        }

        if ($token === '') {
            return $this->failure(['missing-input-response']);
        }

        if (strlen($token) > self::MAX_TOKEN_LENGTH) {
            return $this->failure(['invalid-input-response']);
        }

        $payload = [
            'secret' => $this->secretKey,
            'response' => $token,
        ];

        $remoteIp = trim((string) $remoteIp);
        if ($remoteIp !== '' && filter_var($remoteIp, FILTER_VALIDATE_IP)) {
            $payload['remoteip'] = $remoteIp;
        }

        try {
            $response = $this->client->post(self::VERIFY_URL, [
                'form_params' => $payload,
                'headers' => ['Accept' => 'application/json'],
                'connect_timeout' => 3,
                'timeout' => 8,
                'http_errors' => false,
            ]);

            if ($response->getStatusCode() !== 200) {
                return $this->failure(['siteverify-http-error'], true);
            }

            $result = json_decode((string) $response->getBody(), true);
            if (!is_array($result) || !array_key_exists('success', $result)) {
                return $this->failure(['siteverify-invalid-response'], true);
            }

            $errorCodes = array_values(array_filter(array_map(
                'strval',
                is_array($result['error-codes'] ?? null) ? $result['error-codes'] : []
            )));

            return [
                'success' => $result['success'] === true,
                'unavailable' => false,
                'errorCodes' => $errorCodes,
            ];
        } catch (Throwable $error) {
            log_message('error', 'Turnstile Siteverify no disponible: ' . $error->getMessage());

            return $this->failure(['siteverify-unavailable'], true);
        }
    }

    /**
     * @param array<int, string> $errorCodes
     * @return array{success: bool, unavailable: bool, errorCodes: array<int, string>}
     */
    private function failure(array $errorCodes, bool $unavailable = false): array
    {
        return [
            'success' => false,
            'unavailable' => $unavailable,
            'errorCodes' => $errorCodes,
        ];
    }
}
