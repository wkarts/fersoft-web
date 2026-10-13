<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class ContratoEngMedicaoInheritanceTest extends TestCase
{
    public function test_controller_loads_with_real_parent_and_distinct_whatsapp_actions(): void
    {
        $root = dirname(__DIR__, 2);
        $code = <<<'CODE'
require $argv[1] . '/vendor/autoload.php';

$controller = new ReflectionClass(\App\Http\Controllers\ContratoEngMedicaoController::class);
$parent = \App\Http\Controllers\BaseController::class;
if (!$controller->isSubclassOf($parent)) {
    throw new RuntimeException('A medição deve continuar herdando BaseController.');
}

$action = $controller->getMethod('enviarWhatsAppMedicao');
$parameters = $action->getParameters();
if (!$action->isPublic()
    || $action->getDeclaringClass()->getName() !== $controller->getName()
    || count($parameters) !== 2
    || (string) $parameters[0]->getType() !== \Illuminate\Http\Request::class
    || $parameters[1]->getName() !== 'id') {
    throw new RuntimeException('A ação de medição deve receber Request e id.');
}

$generic = $controller->getMethod('enviarWhatsApp');
$genericParameters = $generic->getParameters();
if ($generic->getDeclaringClass()->getName() !== $parent
    || count($genericParameters) !== 3
    || (string) $genericParameters[1]->getType() !== 'array'
    || (string) $genericParameters[2]->getType() !== 'string'
    || !$genericParameters[2]->isDefaultValueAvailable()
    || $genericParameters[2]->getDefaultValue() !== '') {
    throw new RuntimeException('O contrato genérico de WhatsApp do BaseController foi alterado.');
}

echo "MEDICAO_HERANCA_OK\n";
CODE;

        // Autoload em subprocesso detecta FatalError sem derrubar toda a suíte.
        // Não instancia controller, não consulta banco e não envia mensagens.
        $process = new Process([
            PHP_BINARY, '-d', 'display_errors=stderr', '-r', $code, $root,
        ], $root);
        $process->setTimeout(30);
        $process->run();

        $this->assertSame(
            0,
            $process->getExitCode(),
            $process->getErrorOutput() . $process->getOutput()
        );
        $this->assertSame("MEDICAO_HERANCA_OK\n", $process->getOutput());
    }

    public function test_existing_whatsapp_url_and_route_name_point_to_measurement_action(): void
    {
        $routes = file_get_contents(dirname(__DIR__, 2) . '/routes/web.php');
        $this->assertIsString($routes);
        $this->assertStringContainsString(
            "['prefix' => 'contratos/medicoes', 'middleware' => 'verificaEmpresa']",
            $routes
        );
        $this->assertStringContainsString(
            "Route::get('/enviar-whatsapp/{id}', 'ContratoEngMedicaoController@enviarWhatsAppMedicao')->name('contratos.medicoes.whatsapp');",
            $routes
        );
        $this->assertStringNotContainsString(
            "ContratoEngMedicaoController@enviarWhatsapp'",
            $routes
        );
    }
}
