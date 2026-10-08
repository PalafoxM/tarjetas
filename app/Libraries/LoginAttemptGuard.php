<?php

namespace App\Libraries;

final class LoginAttemptGuard
{
    private $handle;
    private array $state;
    private bool $closed = false;

    public function __construct(string $usuario)
    {
        $directory = WRITEPATH . 'login_attempts';

        if (!is_dir($directory)
            && !mkdir($directory, 0700, true)
            && !is_dir($directory)) {
            throw new \RuntimeException('No se pudo crear el almacenamiento de intentos.');
        }

        $key = hash('sha256', mb_strtolower(trim($usuario), 'UTF-8'));
        $this->handle = fopen($directory . '/' . $key . '.json', 'c+');

        if ($this->handle === false) {
            throw new \RuntimeException('No se pudo abrir el control de intentos.');
        }

        // Evita que peticiones simultáneas pierdan incrementos.
        if (!flock($this->handle, LOCK_EX | LOCK_NB)) {
            fclose($this->handle);
            $this->handle = null;
            throw new \RuntimeException('Ya existe una validación para esta cuenta.');
        }

        $raw = stream_get_contents($this->handle);
        $decoded = $raw === '' ? [] : json_decode($raw, true);

        if (!is_array($decoded)) {
            $this->close();
            throw new \RuntimeException('El control de intentos no es válido.');
        }

        $this->state = array_merge([
            'failures' => 0,
            'blocked_until' => 0,
        ], $decoded);

        if ($this->state['blocked_until'] > 0
            && $this->state['blocked_until'] <= time()) {
            $this->reset();
        }
    }

    public function retryAfter(): int
    {
        return max(0, (int) $this->state['blocked_until'] - time());
    }

    public function failures(): int
    {
        return (int) $this->state['failures'];
    }

    public function recordFailure(): void
    {
        $this->state['failures']++;

        if ($this->state['failures'] >= 10) {
            $this->state['blocked_until'] = time() + 900;
        }

        $this->persist();
    }

    public function reset(): void
    {
        $this->state = [
            'failures' => 0,
            'blocked_until' => 0,
        ];

        $this->persist();
    }

    private function persist(): void
    {
        $json = json_encode($this->state, JSON_THROW_ON_ERROR);

        rewind($this->handle);

        if (!ftruncate($this->handle, 0)
            || fwrite($this->handle, $json) !== strlen($json)
            || !fflush($this->handle)) {
            throw new \RuntimeException('No se pudo guardar el control de intentos.');
        }
    }

    public function close(): void
    {
        if (!$this->closed && is_resource($this->handle)) {
            flock($this->handle, LOCK_UN);
            fclose($this->handle);
        }

        $this->closed = true;
    }

    public function __destruct()
    {
        $this->close();
    }
}