<?php

namespace App\Commands;

use App\Libraries\PagoPartidaConciliacionService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

class ConciliarPagosPartida extends BaseCommand
{
    protected $group = 'Pagos';
    protected $name = 'pagos:conciliar-partidas';
    protected $description = 'Concilia pagos autorizados pendientes contra partidas presupuestales.';
    protected $usage = 'pagos:conciliar-partidas';
    protected $help = 'Ejecuta la conciliacion idempotente de pagos autorizados que aun no tienen movimiento de partida.';

    public function run(array $params)
    {
        $actorUserId = 0;
        $limit = 100;

        try {
            $result = (new PagoPartidaConciliacionService())->conciliarPendientes($actorUserId, $limit);
        } catch (Throwable $e) {
            log_message('error', 'ConciliarPagosPartida.run: ' . $e->getMessage());
            CLI::error('No fue posible ejecutar la conciliacion de pagos por partida.');
            CLI::error($e->getMessage());

            return EXIT_ERROR;
        }

        CLI::write('Conciliacion de pagos por partida');
        CLI::write('Detectados: ' . (int) ($result['detectados'] ?? 0));
        CLI::write('Aplicados: ' . (int) ($result['aplicados'] ?? 0));
        CLI::write('Omitidos: ' . (int) ($result['omitidos'] ?? 0));
        CLI::write('Errores: ' . (int) ($result['errores'] ?? 0));

        $errores = array_values(array_filter(
            is_array($result['detalle'] ?? null) ? $result['detalle'] : [],
            static function ($item): bool {
                return is_array($item) && !empty($item['error']);
            }
        ));

        if (!empty($errores)) {
            CLI::newLine();
            CLI::write('Pagos con error:');
            foreach (array_slice($errores, 0, 10) as $error) {
                CLI::write('- Pago ' . (int) ($error['id_pago'] ?? 0) . ': ' . (string) ($error['message'] ?? 'Error no especificado.'));
            }

            if (count($errores) > 10) {
                CLI::write('- Se omitieron ' . (count($errores) - 10) . ' errores adicionales. Revisa los logs para el detalle completo.');
            }
        }

        return EXIT_SUCCESS;
    }
}
