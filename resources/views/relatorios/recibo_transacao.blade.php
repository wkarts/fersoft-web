<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Comprovante de Transação #{{ $transacao->id }}</title>
    <style>
        @page { margin: 1.5cm; }
        * { box-sizing: border-box; }
        body { font-family: sans-serif; font-size: 13px; color: #333; margin: 0; padding: 0; }
        .recibo-box { width: 100%; border: 1px solid #000; padding: 25px; position: relative; min-height: 400px; }
        .valor-box { position: absolute; top: 25px; right: 25px; font-size: 18px; border: 2px solid #000; padding: 6px 18px; font-weight: bold; background-color: #fff; }
        .header-container { width: 100%; border-bottom: 1px solid #000; margin-bottom: 25px; padding-bottom: 15px; min-height: 105px; }
        .logo-area { float: left; width: 25%; }
        .info-area { float: left; width: 50%; text-align: center; padding-top: 5px; }
        .logo { max-width: 150px; max-height: 85px; }
        .title { font-size: 18px; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
        .empresa-nome { font-size: 14px; font-weight: bold; text-transform: uppercase; }
        .dados-empresa { font-size: 10px; color: #555; }
        .clear { clear: both; }
        .content { margin: 35px 0; line-height: 1.8; }
        .footer { margin-top: 60px; text-align: center; }
        .linha-assinatura { border-top: 1px solid #000; display: inline-block; width: 380px; margin-bottom: 5px; }
        .assinatura-texto { font-size: 11px; color: #555; }
        .info-usuario { margin-top: 30px; font-size: 10px; color: #777; text-align: left; border-top: 1px dashed #ccc; padding-top: 5px;}
    </style>
</head>
<body>

<div class="recibo-box">

    <div class="valor-box">
        R$ {{ number_format($transacao->valor, 2, ',', '.') }}
    </div>

    <div class="header-container">
        <div class="logo-area">
            @php
                // Prevenindo erro caso $config seja nulo
                $logo = $config->logo ?? null;
                $pathLogo = $logo ? public_path('logos/' . $logo) : null;
            @endphp
            @if($pathLogo && file_exists($pathLogo))
                <img src="{{ $pathLogo }}" class="logo">
            @endif
        </div>

        <div class="info-area">
            <div class="title">Comprovante de Lançamento</div>
            <div class="empresa-nome">{{ $config->razao_social ?? $empresa->nome ?? 'Empresa Não Identificada' }}</div>
            <div class="dados-empresa">
                CNPJ: {{ $config->cnpj ?? 'Não informado' }} <br>
                {{ $config->cidade ?? 'Cidade' }} - {{ $config->uf ?? 'UF' }}
            </div>
        </div>
        <div class="clear"></div>
    </div>

    <div class="content">
        <p>Certificamos a realização do seguinte lançamento financeiro:</p>
        
        <table style="width: 100%; border: 1px solid #eee; padding: 10px; margin-bottom: 20px; background-color: #f9f9f9;">
            <tr>
                <td><strong>Conta Bancária:</strong> {{ $item->nome ?? 'N/A' }}</td>
                <td><strong>Agência/Conta:</strong> {{ $item->agencia ?? '-' }} / {{ $item->conta ?? '-' }}</td>
            </tr>
        </table>

        <p>
            <strong>Descrição:</strong> {{ $transacao->descricao }}<br>
            <strong>Tipo de Operação:</strong> {{ $transacao->tipo == 'entrada' ? 'CRÉDITO (+)' : 'DÉBITO (-)' }}<br>
            <strong>Data do Lançamento:</strong> {{ date('d/m/Y', strtotime($transacao->data_pagamento ?? $transacao->created_at)) }}<br>
            <strong>Categoria:</strong> {{ $transacao->categoria->nome ?? 'N/A' }}<br>
            
            @if(isset($transacao->juros) && $transacao->juros > 0)
                <strong>Juros:</strong> R$ {{ number_format($transacao->juros, 2, ',', '.') }}<br>
            @endif
            @if(isset($transacao->multa) && $transacao->multa > 0)
                <strong>Multa:</strong> R$ {{ number_format($transacao->multa, 2, ',', '.') }}<br>
            @endif
        </p>
    </div>

    <div class="footer">
        <div class="linha-assinatura"></div>
        <div class="assinatura-texto">
            Assinatura do Responsável<br>
            {{ $config->cidade ?? 'Local' }}, {{ date('d/m/Y') }}
        </div>
    </div>

    <div class="info-usuario">
        Documento gerado por {{ auth()->user()->name ?? 'Sistema' }} em {{ date('d/m/Y H:i:s') }}
    </div>

</div>

</body>
</html>