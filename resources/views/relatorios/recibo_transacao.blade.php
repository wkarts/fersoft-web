<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <title>Comprovante de Lançamento</title>

    <style>

        @page {

            margin: 1.5cm;

        }



        body {

            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;

            font-size: 11px;

            color: #2b2b2b;

            margin: 0;

            padding: 0;

            background-color: #ffffff;

        }



        .container {

            width: 100%;

            border: 1px solid #dcdcdc;

            border-radius: 6px;

            padding: 20px;

            box-sizing: border-box;

        }



        /* Cabeçalho */

        .header-table {

            width: 100%;

            border-collapse: collapse;

        }



        .logo-col {

            width: 25%;

            vertical-align: middle;

        }



        .logo-img {

            max-width: 130px;

            max-height: 65px;

            object-fit: contain;

        }



        .info-col {

            width: 50%;

            text-align: center;

            vertical-align: middle;

        }



        .title {

            font-size: 13px;

            font-weight: 800;

            letter-spacing: 0.5px;

            text-transform: uppercase;

            color: #1a1a1a;

            margin-bottom: 3px;

        }



        .empresa-nome {

            font-size: 12px;

            font-weight: 700;

            color: #333333;

        }



        .empresa-detalhes {

            font-size: 10px;

            color: #666666;

            margin-top: 2px;

        }



        .valor-col {

            width: 25%;

            text-align: right;

            vertical-align: middle;

        }



        .valor-box {

            background-color: #f8f9fa;

            border: 1px solid #222222;

            border-radius: 4px;

            padding: 6px 10px;

            display: inline-block;

            text-align: right;

        }



        .valor-label {

            font-size: 8px;

            text-transform: uppercase;

            color: #555555;

            font-weight: bold;

        }



        .valor-quantia {

            font-size: 15px;

            font-weight: 800;

            color: #1a1a1a;

        }



        .divider {

            border-top: 2px solid #2c3e50;

            margin: 15px 0;

        }



        /* Card de Informações Bancárias */

        .bank-card {

            background-color: #f8f9fa;

            border-left: 4px solid #0056b3;

            border-radius: 3px;

            padding: 10px 14px;

            margin-bottom: 15px;

        }



        .bank-table {

            width: 100%;

            border-collapse: collapse;

        }



        /* Tabela de Detalhes da Transação */

        .details-table {

            width: 100%;

            border-collapse: collapse;

            margin-top: 5px;

        }



        .details-table td {

            padding: 8px 4px;

            border-bottom: 1px dashed #e9ecef;

            font-size: 11px;

        }



        .label {

            font-weight: 700;

            color: #495057;

            width: 30%;

        }



        .value {

            color: #212529;

            width: 70%;

        }



        .badge-credito {

            color: #198754;

            font-weight: bold;

        }



        .badge-debito {

            color: #dc3545;

            font-weight: bold;

        }



        /* Assinatura */

        .footer-signature {

            margin-top: 50px;

            text-align: center;

        }



        .signature-line {

            width: 240px;

            margin: 0 auto;

            border-top: 1px solid #333;

            padding-top: 5px;

            font-size: 11px;

            font-weight: 600;

        }



        .signature-sub {

            font-size: 10px;

            color: #6c757d;

            margin-top: 2px;

        }



        /* Rodapé de Auditoria */

        .audit-footer {

            margin-top: 35px;

            border-top: 1px dashed #cccccc;

            padding-top: 6px;

            font-size: 9px;

            color: #888888;

            text-align: center;

        }

    </style>

</head>

<body>



<div class="container">

    {{-- Cabeçalho --}}

    <table class="header-table">

        <tr>

            <td class="logo-col">

                @if(!empty($logoBase64))

                    <img class="logo-img" src="{{ $logoBase64 }}">

                @else

                    <span style="font-weight: bold; color: #aaa;">[ LOGO ]</span>

                @endif

            </td>

            <td class="info-col">

                <div class="title">Comprovante de Lançamento</div>

                <div class="empresa-nome">{{ $empresa->nome ?? ($config->nome ?? 'Empresa') }}</div>

                <div class="empresa-detalhes">

                    CNPJ: {{ $config->cnpj ?? $empresa->cnpj ?? 'Não informado' }}<br>

                    {{ $empresa->cidade ?? 'Cidade' }} - {{ $empresa->uf ?? 'UF' }}

                </div>

            </td>

            <td class="valor-col">

                <div class="valor-box">

                    <div class="valor-label">VALOR TOTAL</div>

                    <div class="valor-quantia">R$ {{ number_format($transacao->valor, 2, ',', '.') }}</div>

                </div>

            </td>

        </tr>

    </table>



    <div class="divider"></div>



    <p style="font-size: 11px; color: #555; margin-bottom: 12px;">

        Certificamos a realização do seguinte lançamento financeiro:

    </p>



    {{-- Bloco Conta Bancária --}}

    <div class="bank-card">

        <table class="bank-table">

            <tr>

                <td width="55%">

                    <strong>Conta Bancária:</strong> {{ $item->nome }}

                </td>

                <td width="45%" style="text-align: right;">

                    <strong>Agência/Conta:</strong> {{ $item->agencia ?? '-' }} / {{ $item->conta ?? '-' }}

                </td>

            </tr>

        </table>

    </div>



    {{-- Detalhes da Operação --}}

    <table class="details-table">

        <tr>

            <td class="label">Descrição:</td>

            <td class="value"><strong>{{ $transacao->descricao }}</strong></td>

        </tr>

        <tr>

            <td class="label">Tipo de Operação:</td>

            <td class="value">

                @if($transacao->tipo == 'entrada')

                    <span class="badge-credito">CRÉDITO (+)</span>

                @else

                    <span class="badge-debito">DÉBITO (-)</span>

                @endif

            </td>

        </tr>

        <tr>

            <td class="label">Data do Lançamento:</td>

            <td class="value">{{ date('d/m/Y', strtotime($transacao->data_pagamento ?? $transacao->created_at)) }}</td>

        </tr>

        <tr>

            <td class="label">Categoria:</td>

            <td class="value">{{ $transacao->categoria->nome ?? 'Geral' }}</td>

        </tr>

        @if(isset($transacao->usuario))

            <tr>

                <td class="label">Registrado por:</td>

                <td class="value">{{ $transacao->usuario->nome }}</td>

            </tr>

        @endif

    </table>



    {{-- Área de Assinatura --}}

    <div class="footer-signature">

        <div class="signature-line">

            Assinatura do Responsável

        </div>

        <div class="signature-sub">

            {{ $empresa->cidade ?? 'Local' }}, {{ date('d/m/Y') }}

        </div>

    </div>



    {{-- Rodapé Auditoria --}}

    <div class="audit-footer">

        Documento gerado por Sistema em {{ date('d/m/Y H:i:s') }}

    </div>

</div>



</body>

</html>
