<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class HistoricoStatusOticaNullableMigrationTest extends TestCase
{
    private function source(): string
    {
        $path = __DIR__ . '/../../database/migrations/2026_09_18_120011_create_historico_status_otica_table.php';
        $source = file_get_contents($path);

        $this->assertIsString($source);

        return $source;
    }

    public function testColunaObrigatoriaAusenteEmTabelaPopuladaEhRelaxadaParaNullable(): void
    {
        $source = $this->source();

        $this->assertStringContainsString(
            'relaxRequiredColumnForExistingRows',
            $source
        );

        $this->assertStringContainsString(
            '$expected[\'nullable\'] = true;',
            $source
        );

        $this->assertStringContainsString(
            '$column->nullable((bool) $expected[\'nullable\']);',
            $source
        );
    }

    public function testMigrationNaoMantemAbortPorCampoObrigatorioAusente(): void
    {
        $source = $this->source();

        $this->assertStringNotContainsString(
            'Campo obrigatório ausente em tabela com dados:',
            $source
        );
    }
}
