<?php

namespace Tests\Feature;

use Tests\TestCase;

class MovimentacaoReaberturaOtpRouteTest extends TestCase
{
    public function test_reabertura_de_agendamento_usa_post_com_middleware_e_nome(): void
    {
        $rota = app('router')->getRoutes()->getByName('movimentacaoVeiculo.reabrir');

        $this->assertNotNull($rota);
        $this->assertSame('movimentacaoVeiculo/reabrir/{id}', $rota->uri());
        $this->assertContains('POST', $rota->methods());
        $this->assertNotContains('GET', $rota->methods());
        $this->assertStringEndsWith('MovimentacaoVeiculoController@reabrir', $rota->getActionName());
        $this->assertContains('verificaEmpresa', $rota->gatherMiddleware());
        $this->assertContains('validaAcesso', $rota->gatherMiddleware());
        $this->assertContains('throttle:10,1', $rota->gatherMiddleware());
    }

    public function test_modal_compartilhado_esta_disponivel_nas_duas_telas(): void
    {
        $base = resource_path('views/movimentacoes_veiculos');
        $modal = file_get_contents($base . '/partials/reabrir_modal.blade.php');
        $script = file_get_contents($base . '/partials/reabrir_script.blade.php');

        $this->assertStringContainsString('reabrirMovimentacaoOtpModal', $modal);
        $this->assertStringContainsString('@csrf', $modal);
        $this->assertStringContainsString("code: code", $script);
        $this->assertStringContainsString("/movimentacaoVeiculo/reabrir", $script);
        $this->assertStringContainsString("reabrir_modal", file_get_contents($base . '/list.blade.php'));
        $this->assertStringContainsString("reabrir_modal", file_get_contents($base . '/agenda.blade.php'));
    }
}
