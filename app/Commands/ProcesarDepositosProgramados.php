<?php

namespace App\Commands;

use App\Libraries\DepositosProgramadosService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use DateTimeImmutable;
use DateTimeZone;

class ProcesarDepositosProgramados extends BaseCommand
{
    protected $group = 'Depositos';
    protected $name = 'depositos:programar';
    protected $description = 'Procesa liberacion diaria y semanal de depositos programados.';
    protected $usage = 'depositos:programar [--date YYYY-MM-DD]';
    protected $help = 'Ejecuta el consumo diario de hospedaje y el retorno de alimentos vencidos; si corre en domingo, tambien procesa la liberacion semanal.';

    public function run(array $params)
    {
        $dateOption = CLI::getOption('date');
        $timezone = new DateTimeZone('America/Mexico_City');

        if (trim((string) $dateOption) !== '') {
            try {
                $reference = new DateTimeImmutable(trim((string) $dateOption) . ' 23:59:59', $timezone);
            } catch (\Throwable $e) {
                CLI::error('La fecha indicada en --date no es valida. Usa YYYY-MM-DD.');
                return EXIT_ERROR;
            }
        } else {
            $reference = new DateTimeImmutable('now', $timezone);
        }

        $service = new DepositosProgramadosService();
        $result = [
            'ok' => true,
            'weekly' => null,
            'daily' => $service->processDailyProgrammedMaintenance($reference->format('Y-m-d H:i:s'), 0),
        ];

        if ((int) $reference->format('N') === 7) {
            $result['weekly'] = $service->processWeeklyDeposits($reference->format('Y-m-d H:i:s'), 0);
        }

        CLI::write(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return EXIT_SUCCESS;
    }
}
