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
    protected $usage = 'depositos:programar [--date YYYY-MM-DD] [--time HH:MM]';
    protected $help = 'Ejecuta el consumo diario de hospedaje y el retorno de alimentos vencidos; si corre en domingo, tambien procesa la liberacion semanal. Con --time puedes fijar una hora de referencia puntual.';

    public function run(array $params)
    {
        $dateOption = CLI::getOption('date');
        $timeOption = CLI::getOption('time');
        $timezone = new DateTimeZone('America/Mexico_City');
        $today = new DateTimeImmutable('now', $timezone);
        $todayDate = $today->format('Y-m-d');

        if (trim((string) $dateOption) !== '') {
            $dateValue = trim((string) $dateOption);
            $timeValue = trim((string) $timeOption);
            if ($timeValue === '' && $dateValue === $todayDate) {
                $timeValue = '11:50:00';
            }
            if ($timeValue === '') {
                $timeValue = '23:59:59';
            } elseif (preg_match('/^\d{2}:\d{2}$/', $timeValue) === 1) {
                $timeValue .= ':00';
            }

            try {
                $reference = new DateTimeImmutable($dateValue . ' ' . $timeValue, $timezone);
            } catch (\Throwable $e) {
                CLI::error('La fecha u hora indicada no es valida. Usa --date YYYY-MM-DD y --time HH:MM.');
                return EXIT_ERROR;
            }
        } else {
            $reference = $today;
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
