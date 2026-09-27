<?php

namespace Tests\Unit;

use Tests\TestCase;

class FuncionamentoDeliveryRegressionTest extends TestCase
{
    public function test_new_schedule_requires_a_valid_day(): void
    {
        $controller = file_get_contents(
            app_path('Http/Controllers/FuncionamentoDeliveryController.php')
        );

        $this->assertStringContainsString(
            "if((int) \$request->id === 0)",
            $controller
        );
        $this->assertStringContainsString(
            "Rule::in(FuncionamentoDelivery::dias())",
            $controller
        );
        $this->assertStringContainsString(
            "'dia.required' => 'Selecione o dia da semana.'",
            $controller
        );
    }

    public function test_same_company_cannot_create_the_same_day_twice(): void
    {
        $controller = file_get_contents(
            app_path('Http/Controllers/FuncionamentoDeliveryController.php')
        );

        $this->assertStringContainsString(
            "->where('empresa_id', \$this->empresa_id)",
            $controller
        );
        $this->assertStringContainsString(
            "->where('dia', \$request->dia)",
            $controller
        );
        $this->assertStringContainsString(
            "Este dia da semana já está configurado para o Delivery.",
            $controller
        );
    }

    public function test_creation_form_is_hidden_when_no_day_is_available(): void
    {
        $view = file_get_contents(
            resource_path('views/funcionamentoDelivery/index.blade.php')
        );

        $this->assertStringContainsString(
            '@if(isset($funcionamento) || count($dias) > 0)',
            $view
        );
        $this->assertStringContainsString(
            'name="dia" required',
            $view
        );
        $this->assertStringContainsString(
            'Todos os dias da semana já estão configurados.',
            $view
        );
    }
}
