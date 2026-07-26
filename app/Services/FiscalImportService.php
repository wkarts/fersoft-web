<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class FiscalImportService
{
    public function formatarCst($icms): string
    {
        $origem = (string) ($icms->orig ?? '0');
        $cst = (string) ($icms->CST ?? $icms->CSOSN ?? '90');

        return $origem . str_pad($cst, 2, '0', STR_PAD_LEFT);
    }

    public function determinarRegraFiscal(string $cfopSaida, string $finalidade): array
    {
        $interestadual = str_starts_with($cfopSaida, '6');
        $regra = [
            'cfop' => ($interestadual ? '2' : '1') . substr($cfopSaida, 1),
            'cst_icms' => '090',
            'cst_pis' => '70',
            'cst_cofins' => '70',
        ];

        return match ($finalidade) {
            'uso_consumo_sem_credito' => array_replace($regra, [
                'cfop' => $interestadual ? '2556' : '1556',
            ]),
            'uso_consumo_com_credito' => array_replace($regra, [
                'cfop' => $interestadual ? '2556' : '1556',
                'cst_pis' => '50',
                'cst_cofins' => '50',
            ]),
            'imobilizado' => array_replace($regra, [
                'cfop' => $interestadual ? '2551' : '1551',
            ]),
            'revenda' => array_replace($regra, [
                'cfop' => $interestadual ? '2102' : '1102',
                'cst_pis' => '01',
                'cst_cofins' => '01',
            ]),
            default => $regra,
        };
    }

    public function salvarReferenciaProduto(
        int $produtoId,
        int $fornecedorId,
        ?string $codigoXml,
        ?string $descricao,
        ?string $codigoBarras,
        int $empresaId,
        ?int $usuarioId
    ): void {
        if ($produtoId <= 0 || $fornecedorId <= 0) {
            return;
        }

        DB::table('produto_fornecedors')->updateOrInsert(
            [
                'empresa_id' => $empresaId,
                'produto_id' => $produtoId,
                'fornecedor_id' => $fornecedorId,
                'codigo_fornecedor' => preg_replace('/[^a-zA-Z0-9]/', '', (string) $codigoXml),
            ],
            [
                'descricao_fornecedor' => $descricao ?: '',
                'codigo_barras_fornecedor' => $codigoBarras ?: '',
                'usuario_id' => $usuarioId,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function parseMoeda($valor): float
    {
        if ($valor === null || $valor === '') {
            return 0.0;
        }

        $texto = preg_replace('/[^0-9,.-]/', '', (string) $valor);
        if (str_contains($texto, ',') && str_contains($texto, '.')) {
            $texto = str_replace('.', '', $texto);
            $texto = str_replace(',', '.', $texto);
        } elseif (str_contains($texto, ',')) {
            $texto = str_replace(',', '.', $texto);
        }

        return (float) $texto;
    }
}
