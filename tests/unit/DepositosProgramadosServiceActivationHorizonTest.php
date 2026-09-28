<?php

use App\Libraries\DepositosProgramadosService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class DepositosProgramadosServiceActivationHorizonTest extends CIUnitTestCase
{
    private object $service;
    private ReflectionClass $serviceReflection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serviceReflection = new ReflectionClass(DepositosProgramadosService::class);
        $this->service = $this->serviceReflection->newInstanceWithoutConstructor();
    }

    public function testActivationWithoutFoodMarkerCoversUntilReferenceWeekSunday(): void
    {
        $window = $this->activationFoodWindow([
            'fec_vigencia_desde' => '2026-09-17',
            'fec_vigencia_hasta' => '2026-10-25',
            'fecha_ultimo_deposito_alimentos' => null,
            'tiene_alimentos' => 1,
            'monto_deposito' => 220,
        ], '2026-09-24');

        $this->assertSame('2026-09-17', $window['start']);
        $this->assertSame('2026-09-27', $window['end']);
        $this->assertSame(11, $window['days']);
        $this->assertSame(2420.0, $window['amount']);
        $this->assertSame('2026-09-27', $window['marker']);
    }

    public function testActivationWithPreviousFoodMarkerStartsNextDayAndCoversUntilSunday(): void
    {
        $window = $this->activationFoodWindow([
            'fec_vigencia_desde' => '2026-09-17',
            'fec_vigencia_hasta' => '2026-10-25',
            'fecha_ultimo_deposito_alimentos' => '2026-09-20',
            'tiene_alimentos' => 1,
            'monto_deposito' => 220,
        ], '2026-09-24');

        $this->assertSame('2026-09-21', $window['start']);
        $this->assertSame('2026-09-27', $window['end']);
        $this->assertSame(7, $window['days']);
        $this->assertSame(1540.0, $window['amount']);
        $this->assertSame('2026-09-27', $window['marker']);
    }

    public function testActivationFoodEndIsCappedByValidityEnd(): void
    {
        $window = $this->activationFoodWindow([
            'fec_vigencia_desde' => '2026-09-17',
            'fec_vigencia_hasta' => '2026-09-25',
            'fecha_ultimo_deposito_alimentos' => null,
            'tiene_alimentos' => 1,
            'monto_deposito' => 220,
        ], '2026-09-24');

        $this->assertSame('2026-09-17', $window['start']);
        $this->assertSame('2026-09-25', $window['end']);
        $this->assertSame(9, $window['days']);
        $this->assertSame(1980.0, $window['amount']);
        $this->assertSame('2026-09-25', $window['marker']);
    }

    public function testSundayActivationEndsOnSameSunday(): void
    {
        $window = $this->activationFoodWindow([
            'fec_vigencia_desde' => '2026-09-17',
            'fec_vigencia_hasta' => '2026-10-25',
            'fecha_ultimo_deposito_alimentos' => '2026-09-20',
            'tiene_alimentos' => 1,
            'monto_deposito' => 220,
        ], '2026-09-27');

        $this->assertSame('2026-09-21', $window['start']);
        $this->assertSame('2026-09-27', $window['end']);
        $this->assertSame(7, $window['days']);
        $this->assertSame('2026-09-27', $window['marker']);
    }

    public function testActivationWithoutFoodDoesNotCalculateFoodDepositOrMarker(): void
    {
        $window = $this->activationFoodWindow([
            'fec_vigencia_desde' => '2026-09-17',
            'fec_vigencia_hasta' => '2026-10-25',
            'fecha_ultimo_deposito_alimentos' => null,
            'tiene_alimentos' => 0,
            'monto_deposito' => 220,
        ], '2026-09-24');

        $this->assertSame('2026-09-17', $window['start']);
        $this->assertSame('2026-09-27', $window['end']);
        $this->assertSame(0, $window['days']);
        $this->assertSame(0.0, $window['amount']);
        $this->assertNull($window['marker']);
    }

    public function testWeeklyFoodEndCoversNextWeekSunday(): void
    {
        $foodEnd = $this->invokeServiceMethod(
            'resolveFoodEnd',
            $this->date('2026-09-27'),
            $this->date('2026-10-25'),
            'semanal'
        );

        $this->assertSame('2026-10-04', $foodEnd->format('Y-m-d'));
    }

    public function testWeeklyFoodBalanceAccumulatesOverConsumedBalance(): void
    {
        $balance = $this->invokeServiceMethod(
            'calculateFoodBalanceAfterApplication',
            'semanal',
            183.0,
            660.0
        );

        $this->assertSame(843.0, $balance);
    }

    public function testWeeklyFoodBalanceAccumulatesWhenThereWereNoConsumptions(): void
    {
        $balance = $this->invokeServiceMethod(
            'calculateFoodBalanceAfterApplication',
            'semanal',
            1760.0,
            660.0
        );

        $this->assertSame(2420.0, $balance);
    }

    public function testActivationFoodBalanceKeepsReplacementSemantics(): void
    {
        $balance = $this->invokeServiceMethod(
            'calculateFoodBalanceAfterApplication',
            'activacion',
            220.0,
            2420.0
        );

        $this->assertSame(2420.0, $balance);
    }

    public function testWeeklyWindowWithSundayMarkerCoversNextFullWeek(): void
    {
        $window = $this->weeklyFoodWindow([
            'fec_vigencia_desde' => '2026-09-17',
            'fec_vigencia_hasta' => '2026-10-25',
            'fecha_ultimo_deposito_alimentos' => '2026-09-27',
            'tiene_alimentos' => 1,
            'monto_deposito' => 220,
        ], '2026-09-27');

        $this->assertSame('2026-09-28', $window['start']);
        $this->assertSame('2026-10-04', $window['end']);
        $this->assertSame(7, $window['days']);
        $this->assertSame(1540.0, $window['amount']);
        $this->assertSame('2026-10-04', $window['marker']);
    }

    public function testWeeklyWindowWithDelayedMarkerCatchesUpThroughNextSunday(): void
    {
        $window = $this->weeklyFoodWindow([
            'fec_vigencia_desde' => '2026-09-17',
            'fec_vigencia_hasta' => '2026-10-25',
            'fecha_ultimo_deposito_alimentos' => '2026-09-24',
            'tiene_alimentos' => 1,
            'monto_deposito' => 220,
        ], '2026-09-27');

        $this->assertSame('2026-09-25', $window['start']);
        $this->assertSame('2026-10-04', $window['end']);
        $this->assertSame(10, $window['days']);
        $this->assertSame(2200.0, $window['amount']);
        $this->assertSame('2026-10-04', $window['marker']);
    }

    public function testWeeklyWindowAlreadyCoveredThroughNextSundayDoesNotApplyAgain(): void
    {
        $window = $this->weeklyFoodWindow([
            'fec_vigencia_desde' => '2026-09-17',
            'fec_vigencia_hasta' => '2026-10-25',
            'fecha_ultimo_deposito_alimentos' => '2026-10-04',
            'tiene_alimentos' => 1,
            'monto_deposito' => 220,
        ], '2026-09-27');

        $this->assertSame('2026-10-05', $window['start']);
        $this->assertSame('2026-10-04', $window['end']);
        $this->assertSame(0, $window['days']);
        $this->assertSame(0.0, $window['amount']);
        $this->assertNull($window['marker']);
    }

    public function testWeeklyWindowIsCappedByValidityEnd(): void
    {
        $window = $this->weeklyFoodWindow([
            'fec_vigencia_desde' => '2026-09-17',
            'fec_vigencia_hasta' => '2026-09-26',
            'fecha_ultimo_deposito_alimentos' => '2026-09-24',
            'tiene_alimentos' => 1,
            'monto_deposito' => 220,
        ], '2026-09-27');

        $this->assertSame('2026-09-25', $window['start']);
        $this->assertSame('2026-09-26', $window['end']);
        $this->assertSame(2, $window['days']);
        $this->assertSame(440.0, $window['amount']);
        $this->assertSame('2026-09-26', $window['marker']);
    }

    private function activationFoodWindow(array $user, string $referenceDate): array
    {
        $vigenciaInicio = $this->invokeServiceMethod('resolveUserDate', $user, ['fec_vigencia_desde']);
        $vigenciaFin = $this->invokeServiceMethod('resolveUserDate', $user, ['fec_vigencia_hasta']);
        $foodStart = $this->invokeServiceMethod('normalizeDateToStart', $vigenciaInicio);
        $ultimoDepositoAlimentos = $this->invokeServiceMethod('resolveUserDate', $user, ['fecha_ultimo_deposito_alimentos']);

        if ($ultimoDepositoAlimentos !== null) {
            $siguienteDiaAlimentos = $ultimoDepositoAlimentos->modify('+1 day')->setTime(0, 0, 0);
            if ($siguienteDiaAlimentos > $foodStart) {
                $foodStart = $siguienteDiaAlimentos;
            }
        }

        $foodEnd = $this->invokeServiceMethod('resolveFoodEnd', $this->date($referenceDate), $vigenciaFin, 'activacion');
        $foodDays = (int) ($user['tiene_alimentos'] ?? 0) === 1
            ? $this->invokeServiceMethod('countInclusiveDays', $foodStart, $foodEnd)
            : 0;
        $foodAmount = $foodDays > 0 ? round($foodDays * (float) ($user['monto_deposito'] ?? 0), 2) : 0.0;
        $marker = $foodDays > 0
            ? $foodStart->modify('+' . ($foodDays - 1) . ' days')->format('Y-m-d')
            : null;

        return [
            'start' => $foodStart->format('Y-m-d'),
            'end' => $foodEnd->format('Y-m-d'),
            'days' => $foodDays,
            'amount' => $foodAmount,
            'marker' => $marker,
        ];
    }

    private function weeklyFoodWindow(array $user, string $referenceDate): array
    {
        $vigenciaInicio = $this->invokeServiceMethod('resolveUserDate', $user, ['fec_vigencia_desde']);
        $vigenciaFin = $this->invokeServiceMethod('resolveUserDate', $user, ['fec_vigencia_hasta']);
        $foodStart = $this->invokeServiceMethod('normalizeDateToStart', $vigenciaInicio);
        $ultimoDepositoAlimentos = $this->invokeServiceMethod('resolveUserDate', $user, ['fecha_ultimo_deposito_alimentos']);

        if ($ultimoDepositoAlimentos !== null) {
            $siguienteDiaAlimentos = $ultimoDepositoAlimentos->modify('+1 day')->setTime(0, 0, 0);
            if ($siguienteDiaAlimentos > $foodStart) {
                $foodStart = $siguienteDiaAlimentos;
            }
        }

        $foodEnd = $this->invokeServiceMethod('resolveFoodEnd', $this->date($referenceDate), $vigenciaFin, 'semanal');
        $foodDays = (int) ($user['tiene_alimentos'] ?? 0) === 1
            ? $this->invokeServiceMethod('countInclusiveDays', $foodStart, $foodEnd)
            : 0;
        $foodAmount = $foodDays > 0 ? round($foodDays * (float) ($user['monto_deposito'] ?? 0), 2) : 0.0;
        $marker = $foodDays > 0
            ? $foodStart->modify('+' . ($foodDays - 1) . ' days')->format('Y-m-d')
            : null;

        return [
            'start' => $foodStart->format('Y-m-d'),
            'end' => $foodEnd->format('Y-m-d'),
            'days' => $foodDays,
            'amount' => $foodAmount,
            'marker' => $marker,
        ];
    }

    private function date(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value, new DateTimeZone('America/Mexico_City'));
    }

    private function invokeServiceMethod(string $methodName, ...$arguments)
    {
        $method = $this->serviceReflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invoke($this->service, ...$arguments);
    }
}
