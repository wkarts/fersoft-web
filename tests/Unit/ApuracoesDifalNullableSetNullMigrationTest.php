<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ApuracoesDifalNullableSetNullMigrationTest extends TestCase
{
    private function source(): string
    {
        $path = __DIR__ . '/../../database/migrations/2026_06_01_120012_create_apuracoes_difal_table.php';
        $source = file_get_contents($path);

        $this->assertIsString($source);

        return $source;
    }

    public function testUsuarioIdPermiteNullQuandoFkUsaSetNull(): void
    {
        $source = $this->source();

        $this->assertStringContainsString(
            "$table->unsignedInteger('usuario_id')->nullable()",
            $source
        );

        $this->assertStringContainsString(
            "addForeignIfMissing('apuracoes_difal', 'apuracoes_difal_usuario_id_foreign', 'usuario_id', 'usuarios', 'set null')",
            $source
        );
    }

    public function testColunaLegadaNotNullEhNormalizadaParaNullableAntesDaFkSetNull(): void
    {
        $source = $this->source();

        $this->assertStringContainsString(
            "strtolower(trim($onDelete)) === 'set null'",
            $source
        );

        $this->assertStringContainsString(
            "$targetNullable = $mustBeNullable ? true : $currentlyNullable;",
            $source
        );

        // Garante que o helper não volte a sair cedo apenas porque o tipo já
        // corresponde ao tipo referenciado; a nullability também precisa ser validada.
        $this->assertStringNotContainsString(
            "if ($referencedType === '' || $localType === $referencedType) {",
            $source
        );
    }
}
