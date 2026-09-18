<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class FiscalImportService
{
    /**
     * Ajusta o CST/CSOSN para 3 dígitos (Origem + CST)
     */
    public function formatarCst($icms)
    {
        $origem = (string)($icms->orig ?? '0');
        $cst = (string)($icms->CST ?? $icms->CSOSN ?? '90');
        
        // Garante que o CST tenha 2 dígitos (ex: 00 vira 00, 90 vira 90)
        $cst = str_pad($cst, 2, '0', STR_PAD_LEFT);
        
        return $origem . $cst;
    }

  
    public function determinarRegraFiscal($cfopSaida, $finalidade)
    {
        // Regras padrão baseadas na UF (Entrada)
        $isInterestadual = (substr($cfopSaida, 0, 1) == '6');
        $cfopEntrada = $isInterestadual ? '2' . substr($cfopSaida, 1) : '1' . substr($cfopSaida, 1);

        $regra = [
            'cfop' => $cfopEntrada,
            'cst_icms' => '090', // Padrão
            'cst_pis' => '70',
            'cst_cofins' => '70'
        ];

        switch ($finalidade) {
            case 'uso_consumo_sem_credito':
                $regra['cfop'] = $isInterestadual ? '2556' : '1556';
                $regra['cst_icms'] = '090'; // Ou 060 dependendo do estado
                $regra['cst_pis'] = '70';
                $regra['cst_cofins'] = '70';
                break;

            case 'uso_consumo_com_credito': // Ex: Manutenção de frota
                $regra['cfop'] = $isInterestadual ? '2556' : '1556';
                $regra['cst_pis'] = '50';
                $regra['cst_cofins'] = '50';
                break;

            case 'imobilizado':
                $regra['cfop'] = $isInterestadual ? '2551' : '1551';
                break;

            case 'revenda':
                // Mantém a lógica padrão 5102 -> 1102 / 6102 -> 2102
                $regra['cfop'] = $isInterestadual ? '2102' : '1102';
                $regra['cst_pis'] = '01'; // Crédito normal para revenda
                $regra['cst_cofins'] = '01';
                break;
        }

        return $regra;
    }
  
    /**
     * Registra o vínculo Produto x Fornecedor (Referência)
     */
    public function salvarReferenciaProduto($produtoId, $fornecedorId, $codigoXml, $descricao, $codBarras, $empresaId, $usuarioId)
    {
        if ((int)$produtoId > 0 && (int)$fornecedorId > 0) {
            DB::table('produto_fornecedors')->updateOrInsert(
                [
                    'produto_id' => $produtoId,
                    'fornecedor_id' => $fornecedorId,
                    'codigo_fornecedor' => preg_replace('/[^a-zA-Z0-9]/', '', $codigoXml)
                ],
                [
                    'empresa_id' => $empresaId,
                    'descricao_fornecedor' => $descricao ?? '',
                    'codigo_barras_fornecedor' => $codBarras ?? '',
                    'usuario_id' => $usuarioId,
                    'updated_at' => now()
                ]
            );
        }
    }

    /**
     * Padroniza a conversão de valores para float
     */
    public function parseMoeda($valor)
    {
        if (empty($valor)) return 0;
        $valorStr = (string)$valor;
        $valorStr = preg_replace('/[^0-9.,]/', '', $valorStr);

        if (strpos($valorStr, ',') !== false && strpos($valorStr, '.') !== false) {
            $valorStr = str_replace('.', '', $valorStr);
            $valorStr = str_replace(',', '.', $valorStr);
        } elseif (strpos($valorStr, ',') !== false) {
            $valorStr = str_replace(',', '.', $valorStr);
        }
        return (float)$valorStr;
    }
}
