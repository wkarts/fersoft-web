<?php

namespace Tests\Unit;

use Com\Tecnick\Barcode\Barcode;
use ReflectionClass;
use Tests\TestCase;

class FiscalBarcodeCompatibilityTest extends TestCase
{
    public function test_tc_lib_barcode_deprecation_is_silenced_for_sped_da(): void
    {
        $this->assertTrue(
            defined('TCLIB_BARCODE_SILENCE_DEPRECATION'),
            'O bootstrap deve definir TCLIB_BARCODE_SILENCE_DEPRECATION antes do uso do SPED-DA.'
        );

        $this->assertTrue(
            TCLIB_BARCODE_SILENCE_DEPRECATION,
            'O sinalizador oficial do tc-lib-barcode precisa permanecer habilitado enquanto o SPED-DA exigir a série 1.x.'
        );

        $reflection = new ReflectionClass(Barcode::class);
        $source = file_get_contents($reflection->getFileName());

        $this->assertStringContainsString(
            'TCLIB_BARCODE_SILENCE_DEPRECATION',
            $source,
            'A versão instalada do tc-lib-barcode deve reconhecer o sinalizador oficial de compatibilidade.'
        );
    }

    public function test_instantiating_barcode_does_not_emit_the_sped_blocking_deprecation(): void
    {
        $deprecations = [];

        set_error_handler(
            function (int $severity, string $message) use (&$deprecations): bool {
                if (
                    $severity === E_USER_DEPRECATED
                    && str_contains($message, 'tc-lib-barcode 1.x is deprecated')
                ) {
                    $deprecations[] = $message;
                    return true;
                }

                return false;
            }
        );

        try {
            new Barcode();
        } finally {
            restore_error_handler();
        }

        $this->assertSame(
            [],
            $deprecations,
            'O aviso de depreciação do tc-lib-barcode não pode interromper DANFE/DACTE/DAMDFE.'
        );
    }

    public function test_sped_da_dependency_contract_still_requires_barcode_v1(): void
    {
        $lock = json_decode(
            file_get_contents(base_path('composer.lock')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $packages = array_merge(
            $lock['packages'] ?? [],
            $lock['packages-dev'] ?? []
        );

        $spedDa = null;
        foreach ($packages as $package) {
            if (($package['name'] ?? null) === 'nfephp-org/sped-da') {
                $spedDa = $package;
                break;
            }
        }

        $this->assertNotNull($spedDa);
        $this->assertSame(
            '^1',
            $spedDa['require']['tecnickcom/tc-lib-barcode'] ?? null,
            'Quando o SPED-DA suportar tc-lib-barcode ^2, esta compatibilidade deve ser revisada/removida.'
        );
    }
}
