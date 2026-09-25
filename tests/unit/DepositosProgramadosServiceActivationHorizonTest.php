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

    public function testWeeklyFoodEndStillUsesReferenceDate(): void
    {
        $foodEnd = $this->invokeServiceMethod(
            'resolveFoodEnd',
            $this->date('2026-09-24'),
            $this->date('2026-10-25'),
            'semanal'
        );

        $this->assertSame('2026-09-24', $foodEnd->format('Y-m-d'));
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
