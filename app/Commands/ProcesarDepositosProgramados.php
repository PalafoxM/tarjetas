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
    protected $description = 'Procesa los depositos programados para usuarios con QR activo.';
    protected $usage = 'depositos:programar [--date YYYY-MM-DD]';
    protected $help = 'Ejecuta la liberacion semanal de depositos. Solo corre en domingo a las 23:59:59, salvo que se pase --date para simulacion.';

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

        if ((int) $reference->format('N') !== 7 || $reference->format('H:i') !== '23:59') {
            CLI::error('El proceso de depositos programados solo corre en domingo a las 23:59.');
            return EXIT_ERROR;
        }

        $service = new DepositosProgramadosService();
        $result = $service->processWeeklyDeposits($reference->format('Y-m-d H:i:s'), 0);

        CLI::write(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return EXIT_SUCCESS;
    }
}
