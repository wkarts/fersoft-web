<?php

namespace Tests\Unit;

use App\Services\Nfse\NfseTaxCalculator;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class NfseTaxCalculatorSyntaxTest extends TestCase
{
    public function test_calculator_source_is_valid_php_without_pasted_annotations(): void
    {
        $path = dirname(__DIR__, 2) . '/app/Services/Nfse/NfseTaxCalculator.php';
        $this->assertFileExists($path);
        $source = file_get_contents($path);
        $this->assertNotFalse($source);

        // TOKEN_PARSE verifica o PHP real sem executar consultas nem emitir notas.
        $this->assertNotEmpty(token_get_all($source, TOKEN_PARSE));
        $this->assertDoesNotMatchRegularExpression('/\[cite:\s*[0-9,\s]+\]/', $source);
        $this->assertDoesNotMatchRegularExpression('/::create\(\s*\[\s*\.\.\.\s*\]/', $source);
    }

    public function test_existing_calculation_contract_can_be_autoloaded(): void
    {
        $this->assertTrue(class_exists(NfseTaxCalculator::class));
        $method = new ReflectionMethod(NfseTaxCalculator::class, 'calcular');

        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
        $this->assertSame('array', (string) $method->getReturnType());
        $this->assertSame(4, $method->getNumberOfRequiredParameters());
        $this->assertSame(5, $method->getNumberOfParameters());
        $this->assertSame([], $method->getParameters()[4]->getDefaultValue());
    }
}
