<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Recibo de Pagamento #{{ $conta->id }}</title>
    <style>
        /* Define margens de segurança na folha para a impressora não cortar */
        @page { 
            margin: 1.5cm; 
        }

        /* Faz com que bordas e padding fiquem dentro da largura total */
        * { 
            box-sizing: border-box; 
        }

        body { 
            font-family: sans-serif; 
            font-size: 13px; 
            color: #333; 
            margin: 0; 
            padding: 0; 
        }
        
        /* Container principal com largura total segura */
        .recibo-box { 
            width: 100%; 
            border: 1px solid #000; 
            padding: 25px; 
            position: relative; 
            min-height: 480px;
        }

        /* Valor R$ posicionado de forma absoluta dentro do padding */
        .valor-box { 
            position: absolute; 
            top: 25px; 
            right: 25px; 
            font-size: 18px; 
            border: 2px solid #000; 
            padding: 6px 18px; 
            font-weight: bold;
            background-color: #fff;
        }

        .header-container {
            width: 100%;
            border-bottom: 1px solid #000;
            margin-bottom: 25px;
            padding-bottom: 15px;
            min-height: 105px;
        }

        .logo-area {
            float: left;
            width: 25%;
        }

        /* Área do meio centralizada */
        .info-area {
            float: left;
            width: 50%;
            text-align: center;
            padding-top: 5px;
        }

        .logo { 
            max-width: 150px; 
            max-height: 85px; 
        }

        .title { 
            font-size: 18px; 
            font-weight: bold; 
            text-transform: uppercase; 
            margin-bottom: 5px;
        }

        .empresa-nome { 
            font-size: 14px; 
            font-weight: bold; 
            text-transform: uppercase; 
        }

        .dados-empresa { 
            font-size: 10px; 
            color: #555; 
            margin-top: 3px;
        }

        .clear { clear: both; }

        .content { margin: 35px 0; line-height: 1.8; text-align: justify; }
        
        .footer { margin-top: 50px; text-align: center; }
        .data-extenso { margin-bottom: 50px; font-size: 14px; }
        
        .linha-assinatura { border-top: 1px solid #000; display: inline-block; width: 400px; margin-bottom: 5px; }
        .nome-assinatura { font-weight: bold; text-transform: uppercase; font-size: 12px; }
    </style>
</head>
<body>

<div class="recibo-box">
    <div class="valor-box">
        R$ {{ number_format($conta->valor_pago ?? $conta->valor_integral, 2, ',', '.') }}
    </div>

    <div class="header-container">
        <div class="logo-area">
            @php
                $pathLogo = null;
                if($conta->filial && $conta->filial->logo) {
                    $pathLogo = public_path('logos/' . $conta->filial->logo);
                } elseif($config->logo) {
                    $pathLogo = public_path('logos/' . $config->logo);
                }
            @endphp

            @if($pathLogo && file_exists($pathLogo))
                <img src="{{ $pathLogo }}" class="logo">
            @endif
        </div>

        <div class="info-area">
            <div class="title">Recibo de Pagamento</div>
            <div class="empresa-nome">
                @if($conta->filial)
                    {{ $conta->filial->razao_social ?? $conta->filial->descricao }}
                @else
                    {{ $config->razao_social }}
                @endif
            </div>
            <div class="dados-empresa">
                CNPJ: {{ $config->cnpj }} | {{ $config->logradouro }}, {{ $config->numero }}
                <br>{{ $config->cidade }} - {{ $config->uf }}
            </div>
        </div>
        
        <div class="clear"></div>
    </div>

    <div class="content">
        <p>Recebemos de <strong>
            @if($conta->filial)
                {{ $conta->filial->razao_social ?? $conta->filial->descricao }}
            @else
                {{ $config->razao_social }}
            @endif
        </strong>, a importância supra de <strong>R$ {{ number_format($conta->valor_pago ?? $conta->valor_integral, 2, ',', '.') }}</strong> 
        referente ao pagamento de: <strong>{{ $conta->referencia }}</strong>.</p>
        
        <p>
            <strong>Fornecedor:</strong> {{ $conta->fornecedor->razao_social ?? 'N/A' }}<br>
            <strong>CNPJ/CPF:</strong> {{ $conta->fornecedor->cpf_cnpj ?? 'N/A' }}
        </p>
        
        <p>
            <strong>Data de Pagamento:</strong> {{ date('d/m/Y', strtotime($conta->data_pagamento)) }}<br>
            <strong>Forma de Pagamento:</strong> {{ $conta->tipo_pagamento }}
        </p>
    </div>

    <div class="footer">
        <div class="data-extenso">
            {{ $config->cidade ?? 'Salvador' }} - {{ $config->uf ?? 'BA' }}, 
            {{ date('d') }} de 
            @php
                $meses = ['01'=>'janeiro','02'=>'fevereiro','03'=>'março','04'=>'abril','05'=>'maio','06'=>'junho','07'=>'julho','08'=>'agosto','09'=>'setembro','10'=>'outubro','11'=>'novembro','12'=>'dezembro'];
                echo $meses[date('m')];
            @endphp 
            de {{ date('Y') }}
        </div>

        <div class="assinatura-container">
            <div class="linha-assinatura"></div>
            <br>
            <div class="nome-assinatura">
                @if($conta->filial)
                    {{ $conta->filial->razao_social ?? $conta->filial->descricao }}
                @else
                    {{ $config->razao_social }}
                @endif
            </div>
            <small>Emitente / Responsável</small>
        </div>
    </div>
</div>

</body>
</html>