<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class GeneratedMigrationsNullableFallbackTest extends TestCase
{
    public function testMigrationsPosterioresNaoAbortamPorColunaObrigatoriaAusenteEmTabelaPopulada(): void
    {
        $files = glob(__DIR__ . '/../../database/migrations/2026_09_18_*.php') ?: [];
        sort($files);

        $checked = 0;

        foreach ($files as $file) {
            $name = basename($file);

            if (preg_match('/^2026_09_18_(\d+)_/', $name, $match) !== 1) {
                continue;
            }

            $sequence = (int) $match[1];
            if ($sequence < 120016 || $sequence > 120130) {
                continue;
            }

            $source = file_get_contents($file);
            $this->assertIsString($source, $name);

            $this->assertStringNotContainsString(
                'Campo obrigatório ausente em tabela com dados:',
                $source,
                $name . ' ainda contém o abort legado para coluna NOT NULL em tabela populada.'
            );

            $this->assertStringContainsString(
                'private function relaxRequiredColumnForExistingRows(',
                $source,
                $name . ' não possui o fallback nullable.'
            );

            $this->assertGreaterThanOrEqual(
                2,
                substr_count($source, '$expected = $this->relaxRequiredColumnForExistingRows('),
                $name . ' precisa aplicar o plano relaxado tanto no preflight quanto no ensureColumn.'
            );

            $this->assertStringContainsString(
                '$column->nullable((bool) $expected[\'nullable\']);',
                $source,
                $name . ' não aplica a nullability efetiva ao Blueprint.'
            );

            $checked++;
        }

        $this->assertSame(75, $checked, 'Quantidade inesperada de migrations do lote protegido.');
    }
}
