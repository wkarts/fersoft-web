<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class BankStatementStatusMigrationSafetyTest extends TestCase
{
    private function migration(): object
    {
        return require __DIR__ . '/../../database/migrations/2026_07_26_120023_update_bank_statement_transactions_table.php';
    }

    public function testDefaultPendenteNaoEhCompativelComEnumLegadoEmIngles(): void
    {
        $migration = $this->migration();
        $method = new ReflectionMethod($migration, 'isDefaultCompatibleWithType');
        $method->setAccessible(true);

        $compatible = $method->invoke(
            $migration,
            "enum('pending','reconciled','ignored')",
            "'pendente'",
            true
        );

        $this->assertFalse($compatible);
    }

    public function testDefaultPendingContinuaCompativelComEnumLegado(): void
    {
        $migration = $this->migration();
        $method = new ReflectionMethod($migration, 'isDefaultCompatibleWithType');
        $method->setAccessible(true);

        $compatible = $method->invoke(
            $migration,
            "enum('pending','reconciled','ignored')",
            "'pending'",
            true
        );

        $this->assertTrue($compatible);
    }

    public function testDefaultPendenteEhCompativelQuandoTipoEfetivoEhVarchar(): void
    {
        $migration = $this->migration();
        $method = new ReflectionMethod($migration, 'isDefaultCompatibleWithType');
        $method->setAccessible(true);

        $compatible = $method->invoke(
            $migration,
            'VARCHAR(20)',
            "'pendente'",
            true
        );

        $this->assertTrue($compatible);
    }
}
