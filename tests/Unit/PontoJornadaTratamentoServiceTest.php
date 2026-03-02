<?php

namespace Tests\Unit;

use App\Services\Ponto\PontoJornadaTratamentoService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class PontoJornadaTratamentoServiceTest extends TestCase
{
    public function test_avaliar_dia_identifica_falta_sem_marcacoes(): void
    {
        $service = new PontoJornadaTratamentoService();

        $out = $service->avaliarDia(['entrada' => '08:00', 'saida' => '17:00'], [], 5, 10);

        $this->assertSame('falta', $out[0]['tipo']);
    }

    public function test_avaliar_dia_identifica_atraso_e_debito(): void
    {
        $service = new PontoJornadaTratamentoService();

        $out = $service->avaliarDia(
            ['entrada' => '08:00', 'saida' => '17:00'],
            [Carbon::parse('2026-03-01 08:30:00'), Carbon::parse('2026-03-01 16:00:00')],
            5,
            10
        );

        $tipos = array_column($out, 'tipo');
        $this->assertContains('atraso', $tipos);
        $this->assertContains('debito_banco_horas', $tipos);
    }
}
