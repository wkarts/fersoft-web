@extends('default.layout')
@section('content')

<style>
    /* Simulação de folha A4 na tela */
    .folha-a4 {
        background: white;
        width: 210mm;
        min-height: 297mm;
        padding: 20mm;
        margin: 10px auto;
        border: 1px solid #d3d3d3;
        box-shadow: 0 0 5px rgba(0,0,0,0.1);
        position: relative;
    }

    /* Ajustes para Impressão Real */
    @media print {
        body * { visibility: hidden; }
        .folha-a4, .folha-a4 * { visibility: visible; }
        .folha-a4 {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            border: none;
            box-shadow: none;
            padding: 0;
            margin: 0;
        }
        .btn-print-page { display: none; }
    }

    .header-relatorio { border-bottom: 2px solid #28a745; margin-bottom: 20px; padding-bottom: 10px; }
    .table-relatorio { width: 100%; border-collapse: collapse; font-size: 12px; }
    .table-relatorio th { background: #343a40; color: white; padding: 8px; }
    .table-relatorio td { border: 1px solid #dee2e6; padding: 8px; }
    .info-box { background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
    .btn-dae { background: #28a745; color: white !important; padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: bold; display: inline-block; }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12 text-right mb-2">
            <button onclick="window.print()" class="btn btn-primary btn-print-page">
                <i class="fa fa-print"></i> Imprimir Relatório
            </button>
        </div>
    </div>

    <div class="folha-a4">
    <div class="header-relatorio">
        <h2>Fer<span style="color: #28a745;">Soft</span> - Guia de Auxílio para Emissão de DAE</h2>
        <p>Use os dados abaixo para preencher o portal da SEFAZ/BA (Código 2175)</p>
    </div>

    <div class="info-box" style="border: 2px solid #343a40;">
        <h5 style="background: #343a40; color: white; padding: 5px; margin: -15px -15px 15px -15px;">DADOS PARA O PORTAL SEFAZ</h5>
        
        <div class="row">
            <div class="col-6">
                <p><strong>Inscrição Estadual:</strong> Suas IE cadastrada</p>
                <p><strong>Data de Vencimento:</strong> 15/{{ date('m/Y', strtotime($apuracao->data_final . ' +1 month')) }}</p>
                <p><strong>Data de Pagamento:</strong> (Data que você pretende pagar)</p>
                <p><strong>Valor Principal:</strong> <span class="text-danger">R$ {{ number_format($apuracao->valor_total_difal, 2, ',', '.') }}</span></p>
            </div>
            <div class="col-6">
                <p><strong>Referência:</strong> {{ $apuracao->referencia }}</p>
                <p><strong>Quantidade Total de Notas:</strong> {{ count($items) }}</p>
            </div>
        </div>

        <div style="margin-top: 10px; padding: 10px; border: 1px dashed #ccc;">
            <strong>Números das Notas Fiscais (Preencha nos campos do portal):</strong><br>
            <p style="font-size: 14px; letter-spacing: 1px;">
                @foreach($items as $i)
                    [{{ $i->numero_nota }}] 
                @endforeach
            </p>
        </div>
    </div>

    <div class="text-center mt-4">
        <a href="https://servicos.sefaz.ba.gov.br/sistemas/arasp/pagamento/modulos/dae/pagamento/formulario_dae_pagamento.aspx" target="_blank" class="btn-dae">
            ABRIR PORTAL SEFAZ/BA AGORA
        </a>
    </div>

            </div>
        </div>
    


@endsection