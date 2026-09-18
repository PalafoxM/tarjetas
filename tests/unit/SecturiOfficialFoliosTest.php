<?php

use App\Libraries\SecturiFoliosOficiales;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class SecturiOfficialFoliosTest extends CIUnitTestCase
{
    public function testTaOfficialTransitions(): void
    {
        $this->assertSame('B', SecturiFoliosOficiales::siguienteSubFolio('TA', 'A'));
        $this->assertSame('AA', SecturiFoliosOficiales::siguienteSubFolio('TA', 'Z'));
        $this->assertSame('AB', SecturiFoliosOficiales::siguienteSubFolio('TA', 'AA'));
        $this->assertSame('AF', SecturiFoliosOficiales::siguienteSubFolio('TA', 'AD'));
        $this->assertSame('AH', SecturiFoliosOficiales::siguienteSubFolio('TA', 'AG'));
        $this->assertNull(SecturiFoliosOficiales::siguienteSubFolio('TA', 'AH'));
    }

    public function testTaNeverContainsAeAndKeepsAaComplete(): void
    {
        $subFolios = SecturiFoliosOficiales::subFolios('TA');

        $this->assertContains('AA', $subFolios);
        $this->assertNotContains('AE', $subFolios);
        $this->assertTrue(SecturiFoliosOficiales::contiene('TA', '1020', 'AA'));
        $this->assertFalse(SecturiFoliosOficiales::contiene('TA', '1020', 'AE'));
    }

    public function testThOfficialTransitions(): void
    {
        $this->assertSame('B', SecturiFoliosOficiales::siguienteSubFolio('TH', 'A'));
        $this->assertSame('G', SecturiFoliosOficiales::siguienteSubFolio('TH', 'B'));
        $this->assertNull(SecturiFoliosOficiales::siguienteSubFolio('TH', 'G'));
    }

    public function testThNeverContainsC(): void
    {
        $subFolios = SecturiFoliosOficiales::subFolios('TH');

        $this->assertSame(['A', 'B', 'G'], $subFolios);
        $this->assertNotContains('C', $subFolios);
        $this->assertFalse(SecturiFoliosOficiales::contiene('TH', '1111', 'C'));
    }
}
