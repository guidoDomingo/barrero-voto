<?php

namespace Tests\Unit;

use App\Services\VoterImportService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class VoterImportServiceTest extends TestCase
{
    public function test_maps_and_normalizes_the_electoral_excel_format(): void
    {
        $service = new VoterImportService();
        $mapear = new ReflectionMethod($service, 'mapearDatos');
        $mapear->setAccessible(true);

        $datos = $mapear->invoke($service, [
            'Local N°' => 1,
            'Local de votación' => 'UNIDAD EDUC. JUAN B. ALBERDI',
            'Mesa' => 1,
            'Orden' => 1,
            'Cédula' => '2.136.410',
            'Apellido y Nombre' => 'BRITOS, PELAGIO',
            'Fec. Nac.' => '28/08/1953',
            'Pasó por PC' => 'Op1 GG',
            'Votó' => 'Sí',
        ]);

        $this->assertSame('2136410', $datos['ci']);
        $this->assertSame('PELAGIO', $datos['nombres']);
        $this->assertSame('BRITOS', $datos['apellidos']);
        $this->assertSame('1953-08-28', $datos['fecha_nacimiento']);
        $this->assertSame(1, $datos['local_votacion']);
        $this->assertSame('UNIDAD EDUC. JUAN B. ALBERDI', $datos['descripcion_local']);
        $this->assertTrue($datos['paso_por_pc_movil']);
        $this->assertTrue($datos['ya_voto']);
    }
}
