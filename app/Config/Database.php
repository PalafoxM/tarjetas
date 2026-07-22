<?php

namespace Config;

use CodeIgniter\Database\Config;

/**
 * Database Configuration
 */
class Database extends Config
{
    /**
     * The directory that holds the Migrations
     * and Seeds directories.
     */
    public string $filesPath = APPPATH . 'Database' . DIRECTORY_SEPARATOR;

    /**
     * Lets you choose which connection group to
     * use if no other is specified.
     */
    public string $defaultGroup = 'default';

    /**
     * The default database connection.
     */
     public array $default = [
      'DSN'          => '',
        //'hostname'     => '127.0.0.1',
        'hostname'     => '18.222.102.91',
        'username'     => 'adminSecturi',
        //'username'     => 'root',
        //'password'     => 'yDEa&3FeCT1v@z',
        'password'     => 'admin%53cturi.2025',
        'database'     => 'tarjetas',
        'DBDriver'     => 'MySQLi',
        'DBPrefix'     => '',
        'pConnect'     => false,
        'DBDebug'      => true,
        'charset'      => 'utf8',
        'DBCollat'     => 'utf8_general_ci',
        'swapPre'      => '',
        'encrypt'      => false,
        'compress'     => false,
        'strictOn'     => false,
        'failover'     => [],
        'port'         => 3306,
        'numberNative' => false,
    ];

    /**
     * This database connection is used when
     * running PHPUnit database tests.
     */
    public array $bitacora = [
        'DSN'          => '',
        //'hostname'     => '127.0.0.1',
        'hostname'     => '18.222.102.91',
        'username'     => 'adminSecturi',
        //'username'     => 'root',
        //'password'     => 'yDEa&3FeCT1v@z',
        'password'     => 'admin%53cturi.2025',
        'database'     => 'tarjetas',
        'DBDriver'     => 'MySQLi',
        'DBPrefix'     => '',
        'pConnect'     => false,
        'DBDebug'      => true,
        'charset'      => 'utf8',
        'DBCollat'     => 'utf8_general_ci',
        'swapPre'      => '',
        'encrypt'      => false,
        'compress'     => false,
        'strictOn'     => false,
        'failover'     => [],
        'port'         => 3306,
        'numberNative' => false,
    ];

     public array $tests = [
        'DSN'         => '',
        'hostname'    => '127.0.0.1',
        'username'    => '',
        'password'    => '',
        'database'    => ':memory:',
        'DBDriver'    => 'SQLite3',
        'DBPrefix'    => 'db_',  // Needed to ensure we're working correctly with prefixes live. DO NOT REMOVE FOR CI DEVS
        'pConnect'    => false,
        'DBDebug'     => true,
        'charset'     => 'utf8',
        'DBCollat'    => 'utf8_general_ci',
        'swapPre'     => '',
        'encrypt'     => false,
        'compress'    => false,
        'strictOn'    => false,
        'failover'    => [],
        'port'        => 3306,
        'foreignKeys' => true,
        'busyTimeout' => 1000,
    ];

    public function __construct()
    {
        parent::__construct();

        $this->applyEnvironmentOverrides();

        // Ensure that we always set the database group to 'tests' if
        // we are currently running an automated test suite, so that
        // we don't overwrite live data on accident.
        if (ENVIRONMENT === 'testing') {
            $this->defaultGroup = 'tests';
        }
    }

    private function applyEnvironmentOverrides(): void
    {
        $defaultOverrides = [
            'hostname' => env('DB_HOST'),
            'username' => env('DB_USERNAME'),
            'password' => env('DB_PASSWORD'),
            'database' => env('DB_DATABASE'),
            'port' => env('DB_PORT'),
        ];

        foreach ($defaultOverrides as $key => $value) {
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }

            $this->default[$key] = $key === 'port' ? (int) $value : $value;
        }

        $bitacoraOverrides = [
            'hostname' => env('BITACORA_DB_HOST'),
            'username' => env('BITACORA_DB_USERNAME'),
            'password' => env('BITACORA_DB_PASSWORD'),
            'database' => env('BITACORA_DB_DATABASE'),
            'port' => env('BITACORA_DB_PORT'),
        ];

        foreach ($bitacoraOverrides as $key => $value) {
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }

            $this->bitacora[$key] = $key === 'port' ? (int) $value : $value;
        }

        if (trim((string) ($this->bitacora['hostname'] ?? '')) === '') {
            $this->bitacora['hostname'] = $this->default['hostname'];
        }
        if (trim((string) ($this->bitacora['username'] ?? '')) === '') {
            $this->bitacora['username'] = $this->default['username'];
        }
        if (trim((string) ($this->bitacora['password'] ?? '')) === '') {
            $this->bitacora['password'] = $this->default['password'];
        }
        if ((int) ($this->bitacora['port'] ?? 0) <= 0) {
            $this->bitacora['port'] = $this->default['port'];
        }
    }
}
