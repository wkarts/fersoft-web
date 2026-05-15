<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Demonstrativo de Faturamento</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 13px; /* Tamanho base da fonte */
            color: #000;
        }
        
        /* Layout do Cabeçalho usando tabela para o DOMPDF entender perfeitamente */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .accountant-box {
            border: 1px solid #000;
            border-radius: 12px; /* Borda arredondada igual à foto */
            padding: 15px;
            text-align: center;
            line-height: 1.6;
        }

        /* Caixa de Dados da Empresa */
        .company-box {
            border: 1px solid #000;
            padding: 15px;
            line-height: 1.8;
        }

        /* NOME DA EMPRESA MAIOR (Sua solicitação) */
        .company-name {
            font-size: 16px; 
            font-weight: bold;
            text-transform: uppercase;
        }

        /* Título Central */
        .title {
            text-align: center;
            text-decoration: underline;
            font-size: 16px;
            font-weight: bold;
            margin: 35px 0 25px 0;
        }

        .statement-text {
            text-align: center;
            margin-bottom: 25px;
        }

        /* Tabela Centralizada (Sua solicitação) */
        .data-table-container {
            width: 100%;
            text-align: center;
        }
        
        .data-table {
            width: 55%; /* Largura da tabela, ajustada para ficar mais central */
            margin: 0 auto; /* Isso centraliza a tabela na página */
            border-collapse: collapse;
        }

        .data-table th, .data-table td {
            border: 1px solid #000;
            padding: 6px 10px;
        }

        .data-table td:nth-child(2) {
            text-align: right; /* Alinha os valores em R$ para a direita */
        }

        /* Rodapé */
        .footer {
            text-align: center;
            margin-top: 60px;
            line-height: 2;
        }
    </style>
</head>
<body>

    <div class="accountant-box">
        @if($empresa->contabilidade)
            <strong>{{ strtoupper($empresa->contabilidade->razao_social) }}</strong><br><br>
            CRC: {{ $empresa->contabilidade->crc }} - CNPJ: {{ $empresa->contabilidade->cnpj }}<br><br>
            {{ $empresa->contabilidade->logradouro }}, nº {{ $empresa->contabilidade->numero }} - {{ $empresa->contabilidade->bairro }}<br>
            CEP: {{ $empresa->contabilidade->cep }} 
            {{-- Removi temporariamente a cidade/uf para não dar erro com o cidade_id --}}
        @else
            <strong style="color: red;">CONTABILIDADE NÃO VINCULADA</strong><br>
            <small>Vincule a contabilidade no cadastro desta unidade.</small>
        @endif
    </div>

    <div class="company-box">
        <strong>Empresa: </strong> <span class="company-name">{{ $empresa->razao_social ?? 'Razão Social da Empresa' }}</span><br>
        Endereço: {{ $empresa->endereco ?? '' }} {{ $empresa->numero ?? '' }} {{ $empresa->bairro ?? '' }} - {{ $empresa->complemento ?? '' }}<br>
        Cidade / UF: {{ $empresa->cidade ?? 'SALVADOR' }} / {{ $empresa->uf ?? 'BA' }}<br>
        CNPJ / CPF: {{ $empresa->cnpj ?? '' }}<br>
        Inscrição Estadual: {{ $empresa->inscricao_estadual ?? 'ISENTO' }}
    </div>

    <div class="title">DEMONSTRATIVO DE FATURAMENTO</div>

    <div class="statement-text">
        Declaro para fins de direito que a empresa qualificada acima, obteve os seguintes faturamentos demonstrados abaixo.
    </div>

    <div class="data-table-container">
        <table class="data-table">
            <tbody>
                @foreach($faturamentos as $fat)
                <tr>
                    <td style="text-align: left; text-transform: uppercase;">{{ $fat->mes_nome }} / {{ $fat->ano }}</td>
                    <td>{{ number_format($fat->valor, 2, ',', '.') }}</td>
                </tr>
                @endforeach
                
                <tr>
                    <td style="text-align: center; font-weight: bold;">Total</td>
                    <td style="font-weight: bold;">{{ number_format($total_faturamento, 2, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="footer">
        Por ser verdade, passo e firmo a presente declaração.<br><br>
              @php
          $meses_pt = [1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril', 5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto', 9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'];
          $mes_atual = $meses_pt[(int)date('m')];
      @endphp
      SALVADOR / BA, {{ date('d') }} de {{ $mes_atual }} de {{ date('Y') }}..
    </div>

</body>
</html>