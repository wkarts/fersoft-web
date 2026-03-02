<?php

namespace Tests\Unit;

use App\Services\Ponto\PontoAfdParserService;
use PHPUnit\Framework\TestCase;

class PontoAfdParserServiceTest extends TestCase
{
    public function test_parse_line_with_compact_datetime(): void
    {
        $service = new PontoAfdParserService();
        $line = '3 01032024083000 12345678901 MAT 0001';

        $parsed = $service->parseLine($line);

        $this->assertSame('3', $parsed['tipo_registro']);
        $this->assertSame('12345678901', $parsed['pis']);
        $this->assertSame('2024-03-01 08:30:00', $parsed['data_hora_marcacao']);
        $this->assertFalse($parsed['inconsistente']);
    }

    public function test_parse_line_marks_inconsistency_when_datetime_not_found(): void
    {
        $service = new PontoAfdParserService();
        $parsed = $service->parseLine('LINHA SEM DATA');

        $this->assertTrue($parsed['inconsistente']);
        $this->assertNotNull($parsed['motivo_inconsistencia']);
    }
}
