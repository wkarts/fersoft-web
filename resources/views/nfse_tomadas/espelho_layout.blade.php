<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; margin: 30px; font-size: 13px; }
        .wrapper { border: 1px solid #000; padding: 15px; width: 100%; max-width: 800px; margin: 0 auto; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
        .row { display: flex; justify-content: space-between; margin-bottom: 10px; gap: 10px; }
        .box { border: 1px solid #ccc; padding: 10px; flex: 1; background: #f9f9f9; }
        .title { font-weight: bold; font-size: 11px; color: #666; text-uppercase; margin-bottom: 3px; }
        .value { font-size: 14px; font-weight: bold; color: #000; }
        .footer { margin-top: 30px; text-align: center; font-size: 11px; color: #999; border-top: 1px dashed #ccc; padding-top: 10px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>

    @php
        // 🧠 MOTOR DE CÁLCULO DE RETENÇÕES (Engenharia Reversa)
        $vServico = $nota->valor_servico;
        $vLiquido = $nota->valor_liquido > 0 ? $nota->valor_liquido : $vServico;
        
        $diffFederal = round($vServico - $vLiquido, 2);
        $vPis = 0; $vCofins = 0; $vCsll = 0; $vIr = 0;

        if ($diffFederal > 0) {
            $vPis = round($vServico * 0.0065, 2);
            $vCofins = round($vServico * 0.03, 2);
            $vCsll = round($vServico * 0.01, 2);
            
            // O IR absorve a diferença para fechar a matemática exata do líquido
            $somaImpostos = $vPis + $vCofins + $vCsll;
            $vIr = round($diffFederal - $somaImpostos, 2);
            if ($vIr < 0) $vIr = 0; 
        }
    @endphp

    <div class="no-print" style="max-width: 800px; margin: 0 auto 15px auto; text-align: right;">
        <button onclick="window.print()" style="padding: 8px 20px; background: #1bc5bd; color: #fff; border: none; font-weight: bold; border-radius: 4px; cursor: pointer;">🖨️ IMPRIMIR DOCUMENTO</button>
    </div>

    <div class="wrapper">
        <div class="header">
            <h2>RECEBIMENTO DE PRESTAÇÃO DE SERVIÇOS</h2>
            <h3>DOCUMENTO AUXILIAR DA NFS-e ELETRÔNICA (DANFSE)</h3>
            <p>Ambiente de Dados Nacional (ADN)</p>
        </div>

        <div class="row">
            <div class="box">
                <div class="title">Número da Nota</div>
                <div class="value">{{ $nota->numero_nota }}</div>
            </div>
            <div class="box">
                <div class="title">Código de Controle (NSU)</div>
                <div class="value">{{ $nota->nsu }}</div>
            </div>
            <div class="box">
                <div class="title">Data de Emissão</div>
                <div class="value">{{ date('d/m/Y', strtotime($nota->data_emissao)) }}</div>
            </div>
        </div>

        <div class="row">
            <div class="box">
                <div class="title">Dados do Prestador (Fornecedor)</div>
                <div class="value" style="font-size: 15px;">{{ $nota->prestador_nome }}</div>
                <div style="margin-top: 5px;"><strong>CNPJ/CPF:</strong> {{ $nota->prestador_cnpj }}</div>
            </div>
        </div>

        <div class="row">
          <div class="box" style="min-height: 100px;">
              <div class="title">Descrição dos Serviços Prestados</div>
              <div style="font-size: 13px; line-height: 1.6; margin-top: 5px;">
                  @if(isset($descricao_servico) && !empty($descricao_servico))
                      {!! nl2br(e($descricao_servico)) !!}
                  @else
                      {{ $nota->observacao ?? 'Prestação de serviços executada conforme especificações registradas na receita federal.' }}
                  @endif
              </div>
          </div>
      </div>

        {{-- BLOCO DINÂMICO DE RETENÇÕES FEDERAIS --}}
        @if($diffFederal > 0)
        <div class="row">
            <div class="box" style="background: #fff8f8; border-color: #ffcccc;">
                <div class="title" style="color: #d9534f;">RETENÇÕES FEDERAIS ESPECIFICADAS (PIS / COFINS / CSLL / IRRF)</div>
                <div style="font-size: 13px; margin-top: 8px; display: flex; justify-content: space-between; border-top: 1px dashed #ffcccc; padding-top: 8px;">
                    <span><strong>PIS:</strong> R$ {{ number_format($vPis, 2, ',', '.') }}</span>
                    <span><strong>COFINS:</strong> R$ {{ number_format($vCofins, 2, ',', '.') }}</span>
                    <span><strong>CSLL:</strong> R$ {{ number_format($vCsll, 2, ',', '.') }}</span>
                    <span><strong>IRRF/Outros:</strong> R$ {{ number_format($vIr, 2, ',', '.') }}</span>
                </div>
            </div>
        </div>
        @endif

        {{-- RESUMO FINANCEIRO COMPLETO --}}
        <div class="row">
            <div class="box" style="text-align: right;">
                <div class="title">VALOR DO SERVIÇO</div>
                <div class="value" style="font-size: 18px;">R$ {{ number_format($vServico, 2, ',', '.') }}</div>
            </div>
            
            <div class="box" style="text-align: right; background: #fff8f8; border-color: #ffcccc; {{ $diffFederal == 0 ? 'opacity: 0.5;' : '' }}">
                <div class="title" style="color: #d9534f;">TOTAL DE RETENÇÕES</div>
                <div class="value" style="font-size: 18px; color: #d9534f;">- R$ {{ number_format($diffFederal, 2, ',', '.') }}</div>
            </div>
            
            <div class="box" style="text-align: right; background: #eef9f2; border-color: #1bc5bd;">
                <div class="title" style="color: #1bc5bd;">VALOR LÍQUIDO DA NFS-E</div>
                <div class="value" style="font-size: 24px; color: #0b6644;">R$ {{ number_format($vLiquido, 2, ',', '.') }}</div>
            </div>
        </div>

        <div class="footer">
            FerSoft ERP - Documento impresso via módulo de NFS-e Nacional em {{ date('d/m/Y H:i:s') }}
        </div>
    </div>

</body>
</html>