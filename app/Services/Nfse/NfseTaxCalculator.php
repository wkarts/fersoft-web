<?php

namespace App\Services\Nfse;

use App\Models\ConfigNota;
use App\Models\Tributacao;
use App\Models\Servico;
use App\Models\Cliente;

class NfseTaxCalculator
{
    /**
     * Calcula todos os tributos, retenções e reforma tributária (IBS/CBS)
     */
    public static function calcular(int $empresaId, int $clienteId, int $servicoId, float $valorServico, array $opcoes = []): array
    {
        $config = ConfigNota::where('empresa_id', $empresaId)->first();
        $tributacao = Tributacao::where('empresa_id', $empresaId)->first();
        $servico = Servico::where('empresa_id', $empresaId)->findOrFail($servicoId);
        $cliente = Cliente::with('cidade')->where('empresa_id', $empresaId)->findOrFail($clienteId);

        // 1. Regime da Empresa: 0 = Simples, 1 = Normal, 2 = MEI
        $regime = (int) ($tributacao->regime ?? 0);
        $isSimples = ($regime === 0 || $regime === 2);
        $isMei = ($regime === 2);

        // Descontos e Deduções
        $descontoIncondicional = (float) ($opcoes['desconto_incondicional'] ?? 0);
        $valorDeducoes = (float) ($opcoes['valor_deducoes'] ?? 0);

        // Base de Cálculo do ISS
        $baseIss = max(0, $valorServico - $descontoIncondicional - $valorDeducoes);

        // 2. ISSQN Municipal
        $aliquotaIss = (float) ($opcoes['aliquota_iss'] ?? $servico->aliquota_iss ?? 3.00);
        $issRetido = !empty($opcoes['iss_retido']) ? 1 : 0; // 1 = Retido, 0 = Não retido
        $valorIss = round(($baseIss * ($aliquotaIss / 100)), 2);
        $valorIssRetido = $issRetido ? $valorIss : 0.00;

        // 3. Retenções Federais (PIS, COFINS, CSLL, IRRF, INSS)
        // Empresas do Simples Nacional em regra NÃO sofrem retenção na fonte de PIS/COFINS/CSLL/IRRF
        $reterFederais = !$isSimples && (strlen(preg_replace('/\D/', '', $cliente->cpf_cnpj)) > 11); // Apenas PJ tomando de PJ Normal

        $aliqPis = $reterFederais ? (float) ($servico->aliquota_pis ?? $tributacao->pis ?? 0.65) : 0.00;
        $aliqCofins = $reterFederais ? (float) ($servico->aliquota_cofins ?? $tributacao->cofins ?? 3.00) : 0.00;
        $aliqCsll = $reterFederais ? (float) ($servico->aliquota_csll ?? 1.00) : 0.00;
        $aliqIr = $reterFederais ? (float) ($servico->aliquota_ir ?? 1.50) : 0.00;
        $aliqInss = !empty($opcoes['reter_inss']) ? (float) ($servico->aliquota_inss ?? 11.00) : 0.00;

        $valorPis = round(($valorServico * ($aliqPis / 100)), 2);
        $valorCofins = round(($valorServico * ($aliqCofins / 100)), 2);
        $valorCsll = round(($valorServico * ($aliqCsll / 100)), 2);
        $valorIr = round(($valorServico * ($aliqIr / 100)), 2);
        $valorInss = round(($valorServico * ($aliqInss / 100)), 2);

        $totalRetencoesFederais = $valorPis + $valorCofins + $valorCsll + $valorIr + $valorInss;

        // 4. Reforma Tributária 2026 (IBS / CBS Informativo no XML)
        $cstIbsCbs = str_pad($servico->cst_ibscbs ?? $tributacao->cst_ibs_cbs ?? '000', 3, '0', STR_PAD_LEFT);
        $classTrib = str_pad($servico->codigo_class_trib ?? $tributacao->class_trib_ibs_cbs ?? '000001', 6, '0', STR_PAD_LEFT);
        $aliqCbs = (float) ($tributacao->aliq_cbs ?? 0.90); // 0,90% federal
        $aliqIbsUf = (float) ($tributacao->aliq_ibs_uf ?? 0.10); // 0,10% estadual
        $aliqIbsMun = (float) ($tributacao->aliq_ibs_mun ?? 0.00);

        $valorCbs = round(($valorServico * ($aliqCbs / 100)), 2);
        $valorIbsUf = round(($valorServico * ($aliqIbsUf / 100)), 2);
        $valorIbsMun = round(($valorServico * ($aliqIbsMun / 100)), 2);

        // 5. Total Líquido a Receber
        $valorLiquido = $valorServico - $descontoIncondicional - $valorIssRetido - $totalRetencoesFederais;

        return [
            'regime' => [
                'codigo'      => $regime, // 0, 1 ou 2
                'opSimpNac'   => $isMei ? 3 : ($isSimples ? 2 : 1), // 1=Normal, 2=Simples, 3=MEI
                'regApTribSN' => $isSimples ? 1 : null,
                'regEspTrib'  => (int) ($config->regime_especial_tributacao ?? 0),
            ],
            'valores' => [
                'valor_servico'          => $valorServico,
                'valor_deducoes'         => $valorDeducoes,
                'desconto_incondicional' => $descontoIncondicional,
                'base_calculo_iss'       => $baseIss,
                'valor_liquido'          => $valorLiquido,
            ],
            'iss' => [
                'exigibilidade' => (int) ($opcoes['exigibilidade_iss'] ?? 1), // 1=Exigível, 2=Não incidência, 3=Isenção
                'iss_retido'    => $issRetido,
                'tpRetISSQN'    => $issRetido ? 2 : 1, // 1=Não Retido, 2=Retido
                'aliquota'      => $aliquotaIss,
                'valor'         => $valorIss,
                'valor_retido'  => $valorIssRetido,
            ],
            'retencoes_federais' => [
                'pis'         => ['aliquota' => $aliqPis, 'valor' => $valorPis],
                'cofins'      => ['aliquota' => $aliqCofins, 'valor' => $valorCofins],
                'csll'        => ['aliquota' => $aliqCsll, 'valor' => $valorCsll],
                'irrf'        => ['aliquota' => $aliqIr, 'valor' => $valorIr],
                'inss'        => ['aliquota' => $aliqInss, 'valor' => $valorInss],
                'total_retido'=> $totalRetencoesFederais,
            ],
            'reforma_tributaria' => [
                'cst'          => $cstIbsCbs,
                'cClassTrib'   => $classTrib,
                'cbs'          => ['aliquota' => $aliqCbs, 'valor' => $valorCbs],
                'ibs_uf'       => ['aliquota' => $aliqIbsUf, 'valor' => $valorIbsUf],
                'ibs_mun'      => ['aliquota' => $aliqIbsMun, 'valor' => $valorIbsMun],
            ]
        ];
    }

    // A calculadora não cria nem transmite documentos. Emissão e envio por
    // WhatsApp permanecem nos fluxos existentes dos controllers de NFS-e.
}
