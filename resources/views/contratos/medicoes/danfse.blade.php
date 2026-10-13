@extends('default.layout')
@section('content')
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="text-primary font-weight-bold">
                    <i class="fa fa-file-invoice"></i> NFS-e Autorizada - Medição #{{ $fatura->id }}
                </h4>
                <div>
                    <button onclick="window.print()" class="btn btn-primary btn-sm px-4">
                        <i class="fa fa-print"></i> Imprimir / Salvar PDF
                    </button>
                    <a href="{{ route('contratos.medicoes.index') }}" class="btn btn-secondary btn-sm px-4">
                        <i class="fa fa-arrow-left"></i> Voltar
                    </a>
                </div>
            </div>

            <!-- CABEÇALHO / DOCUMENTO AUXILIAR -->
            <div class="border p-4 bg-white" style="max-width: 850px; margin: 0 auto; font-family: Arial, sans-serif;">

                <!-- TOPO E QR CODE -->
                <table class="table table-bordered mb-2 text-center" style="font-size: 11px;">
                    <tr>
                        <td style="width: 20%; vertical-align: middle;">
                            <!-- Geração de QR Code -->
                            @php
                                $qrCodeData = "https://nfse.diasdavila.ba.gov.br/consultar?chave=" . ($fatura->chave_nfse ?? '');
                            @endphp
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=90x90&data={{ urlencode($qrCodeData) }}" alt="QR Code NFS-e" style="max-width: 90px;">
                        </td>
                        <td style="width: 60%; vertical-align: middle;">
                            <h5 class="font-weight-bold mb-0">NOTA FISCAL DE SERVIÇOS ELETRÔNICA</h5>
                            <small>Documento Auxiliar de Prestação de Serviços</small>
                        </td>
                        <td style="width: 20%; vertical-align: middle; background-color: #e2f0d9;">
                            <span class="badge badge-success p-2" style="font-size: 11px;">AUTORIZADA</span>
                        </td>
                    </tr>
                </table>

                <!-- IDENTIFICAÇÃO DO DOCUMENTO -->
                <table class="table table-bordered mb-2" style="font-size: 10px;">
                    <tr>
                        <td><strong>NÚMERO DA NFS-E:</strong><br>{{ $fatura->numero_nfse }}</td>
                        <td><strong>SÉRIE:</strong><br>1</td>
                        <td><strong>DATA DE EMISSÃO:</strong><br>{{ date('d/m/Y H:i', strtotime($fatura->updated_at)) }}</td>
                        <td><strong>COMPETÊNCIA:</strong><br>{{ date('m/Y', strtotime($fatura->data_faturamento)) }}</td>
                    </tr>
                    <tr>
                        <td colspan="2"><strong>CHAVE DE ACESSO NFS-E:</strong><br><span class="font-weight-bold">{{ $fatura->chave_nfse }}</span></td>
                        <td colspan="2"><strong>PROTOCOLO DE AUTORIZAÇÃO:</strong><br><span class="font-weight-bold">{{ $fatura->protocolo_nfse }}</span></td>
                    </tr>
                </table>

                <!-- PRESTADOR DE SERVIÇOS (EMITENTE) -->
                <table class="table table-bordered mb-2" style="font-size: 10px;">
                    <tr class="bg-secondary text-white">
                        <th colspan="2">PRESTADOR DE SERVIÇOS (EMITENTE)</th>
                    </tr>
                    <tr>
                        <td><strong>RAZÃO SOCIAL / NOME:</strong><br>{{ $emitente->razao_social }}</td>
                        <td><strong>CNPJ / CPF:</strong><br>{{ $emitente->cnpj }}</td>
                    </tr>
                    <tr>
                        <td><strong>INSCRIÇÃO MUNICIPAL:</strong><br>{{ $emitente->inscricao_municipal }}</td>
                        <td><strong>OPTANTE PELO SIMPLES NACIONAL:</strong><br>{{ ($emitente->optante_simples == '1' || strtolower($emitente->optante_simples ?? '') == 'sim') ? 'Sim' : 'Não' }}</td>
                    </tr>
                    <tr>
                        <td colspan="2"><strong>ENDEREÇO:</strong><br>{{ $emitente->endereco }}, {{ $emitente->numero }} - {{ $emitente->bairro }} | {{ $emitente->cidade }} / {{ $emitente->uf }}</td>
                    </tr>
                </table>

                <!-- TOMADOR DE SERVIÇOS (CLIENTE) -->
                <table class="table table-bordered mb-2" style="font-size: 10px;">
                    <tr class="bg-secondary text-white">
                        <th colspan="2">TOMADOR DE SERVIÇOS (CLIENTE)</th>
                    </tr>
                    <tr>
                        <td><strong>NOME / RAZÃO SOCIAL:</strong><br>{{ $fatura->cliente->razao_social ?? $fatura->cliente->nome }}</td>
                        <td><strong>CNPJ / CPF:</strong><br>{{ $fatura->cliente->cpf_cnpj }}</td>
                    </tr>
                    <tr>
                        <td colspan="2"><strong>ENDEREÇO:</strong><br>{{ $fatura->cliente->rua ?? '' }}, {{ $fatura->cliente->numero ?? '' }} - {{ $fatura->cliente->bairro ?? '' }} | {{ $fatura->cliente->cidade->nome ?? '' }} / {{ $fatura->cliente->cidade->uf ?? '' }}</td>
                    </tr>
                </table>

                <!-- DESTINATÁRIO / INTERMEDIÁRIO -->
                <table class="table table-bordered mb-0" style="font-size: 9px; border-bottom: none;">
                    <tr class="text-center bg-light">
                        <td>DESTINATÁRIO DA OPERAÇÃO NÃO IDENTIFICADO NA NFS-e</td>
                    </tr>
                    <tr class="text-center bg-light border-top-0">
                        <td>INTERMEDIÁRIO DA OPERAÇÃO NÃO IDENTIFICADO NA NFS-e</td>
                    </tr>
                </table>

                <!-- SERVIÇO PRESTADO -->
                <table class="table table-bordered mb-2" style="font-size: 9px;">
                    <tr class="bg-light font-weight-bold">
                        <td colspan="4" style="font-size: 10px;">SERVIÇO PRESTADO</td>
                    </tr>
                    <tr>
                        <td style="width: 30%;"><strong>Código de Tributação Nacional/Municipal</strong><br>14.05.01 / -</td>
                        <td style="width: 25%;"><strong>Código da NBS</strong><br>1.2002.90.00</td>
                        <td style="width: 45%;" colspan="2"><strong>Local da Prestação / Sigla UF / País</strong><br>DIAS D'ÁVILA / BA / -</td>
                    </tr>
                    <tr>
                        <td colspan="4" style="border-bottom: none; padding-bottom: 0;">
                            Restauração, recondicionamento, acondicionamento, pintura, beneficiamento, lavagem, secagem, tingimento, galvanoplastia, anodização, corte, recorte, plastificação, cos...
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" style="border-top: none; height: 90px; vertical-align: top;">
                            <strong style="font-size: 10px;">Descrição do Serviço</strong><br>
                            MEDIÇÃO DE CONTRATO #{{ $fatura->id }}<br>
                            @foreach($fatura->itens as $item)
                                - {{ mb_strtoupper($item->servico->nome ?? $item->produto->nome ?? $item->descricao ?? 'Serviço') }} | Qtd: {{ number_format($item->quantidade, 2, ',', '.') }} | Vl. Unit: R$ {{ number_format($item->valor_unitario, 2, ',', '.') }} | Subtotal: R$ {{ number_format($item->sub_total, 2, ',', '.') }}<br>
                            @endforeach
                            @if(!empty($fatura->observacao))
                                <br><em>Obs: {{ mb_strtoupper($fatura->observacao) }}</em>
                            @endif
                        </td>
                    </tr>
                </table>

                <!-- TRIBUTAÇÃO MUNICIPAL (ISSQN) E FEDERAL -->
                <table class="table table-bordered mb-2" style="font-size: 9px;">
                    <tr class="bg-light">
                        <td colspan="4" style="font-size: 10px;"><strong>TRIBUTAÇÃO MUNICIPAL (ISSQN)</strong></td>
                    </tr>
                    <tr>
                        <td style="width: 25%;"><strong>Tipo de Tributação do ISSQN</strong><br>Operação Tributável</td>
                        <td style="width: 45%;" colspan="2"><strong>Município / Sigla UF / País de Incidência do ISSQN</strong><br>Nenhum</td>
                        <td style="width: 30%;"></td>
                    </tr>
                    <tr>
                        <td><strong>BC ISSQN</strong><br>R$ {{ number_format($fatura->valor_total, 2, ',', '.') }}</td>
                        <td><strong>Alíquota Aplicada</strong><br>3,00 %</td>
                        <td><strong>Retenção do ISSQN</strong><br>Não Retido</td>
                        <td><strong>ISSQN Apurado</strong><br>R$ {{ number_format($fatura->valor_total * 0.03, 2, ',', '.') }}</td>
                    </tr>

                    <tr class="bg-light border-top">
                        <td colspan="4" style="font-size: 10px;"><strong>TRIBUTAÇÃO FEDERAL (EXCETO CBS)</strong></td>
                    </tr>
                    <tr>
                        <td><strong>IRRF</strong><br>-</td>
                        <td><strong>Contribuição Previdenciária - Retida</strong><br>-</td>
                        <td colspan="2"><strong>Contribuições Sociais - Retidas</strong><br>-</td>
                    </tr>
                    <tr>
                        <td><strong>PIS - Débito Apuração Própria</strong><br>-</td>
                        <td><strong>COFINS - Débito Apuração Própria</strong><br>-</td>
                        <td colspan="2"><strong>Descrição Contrib. Sociais - Retidas</strong><br>0 - PIS/COFINS/CSLL Não Retidos</td>
                    </tr>
                </table>

                <!-- TRIBUTAÇÃO IBS/CBS -->
                <table class="table table-bordered mb-2" style="font-size: 9px;">
                    <tr class="bg-light">
                        <td colspan="4" style="font-size: 10px;"><strong>TRIBUTAÇÃO IBS/CBS</strong></td>
                    </tr>
                    <tr>
                        <td style="width: 30%;"><strong>CST / cClassTrib</strong><br>000 / 000001</td>
                        <td style="width: 70%;" colspan="3"><strong>Indicador de Operação / Código IBGE Incidência / Município Incidência / Sigla UF</strong><br>050101 / 2910057 / DIAS D'ÁVILA / BA</td>
                    </tr>
                    <tr>
                        <td><strong>Exclusões e Reduções da Base de Cálculo</strong><br>R$ 0,60</td>
                        <td><strong>Base de Cálculo Após Exclusões e Reduções</strong><br>R$ 19,40</td>
                        <td><strong>Red. Alíquota IBS / Red. Alíquota CBS</strong><br>- / - / -</td>
                        <td><strong>Alíquota - IBS UF / IBS Mun</strong><br>0,10 % / 0,00 %</td>
                    </tr>
                    <tr>
                        <td><strong>Aliq. Efetiva Municipal - IBS</strong><br>0,00 %</td>
                        <td><strong>Valor Apurado Municipal - IBS</strong><br>R$ 0,00</td>
                        <td><strong>Aliq. Efetiva Estadual - IBS</strong><br>0,10 %</td>
                        <td><strong>Valor Apurado Estadual - IBS</strong><br>R$ 0,02</td>
                    </tr>
                    <tr>
                        <td><strong>Valor Total Apurado - IBS</strong><br>R$ 0,02</td>
                        <td><strong>Alíquota - CBS</strong><br>0,90 %</td>
                        <td><strong>Alíquota Efetiva - CBS</strong><br>0,90 %</td>
                        <td><strong>Valor Total Apurado - CBS</strong><br>R$ 0,17</td>
                    </tr>

                    <tr class="bg-light border-top">
                        <td colspan="4" style="font-size: 10px;"><strong>VALOR TOTAL DA NFS-e</strong></td>
                    </tr>
                    <tr>
                        <td><strong>VALOR DA OPERAÇÃO / SERVIÇO</strong><br>R$ {{ number_format($fatura->valor_total, 2, ',', '.') }}</td>
                        <td><strong>Desconto Incondicionado</strong><br>-</td>
                        <td colspan="2"><strong>Desconto Condicionado</strong><br>-</td>
                    </tr>
                    <tr>
                        <td><strong>Total das Retenções (ISSQN / Federais)</strong><br>-</td>
                        <td><strong>VALOR LÍQUIDO DA NFS-e</strong><br>R$ {{ number_format($fatura->valor_total, 2, ',', '.') }}</td>
                        <td><strong>Total do IBS/CBS</strong><br>R$ 0,19</td>
                        <td class="bg-light"><strong>VALOR LÍQUIDO DA NFS-e + IBS/CBS</strong><br><strong style="font-size: 12px;">R$ {{ number_format($fatura->valor_total, 2, ',', '.') }}</strong></td>
                    </tr>
                </table>

                <!-- INFORMAÇÕES COMPLEMENTARES -->
                <table class="table table-bordered mb-0" style="font-size: 9px;">
                    <tr class="bg-light">
                        <td style="font-size: 10px;"><strong>INFORMAÇÕES COMPLEMENTARES</strong></td>
                    </tr>
                    <tr>
                        <td style="height: 40px; vertical-align: top;">
                            Totais aproximados dos Tributos cfe. Lei nº 12.741/2012: Federais: R$ 0,00; Estaduais: R$ 0,00; Municipais: R$ {{ number_format($fatura->valor_total * 0.03, 2, ',', '.') }};
                        </td>
                    </tr>
                </table>

            </div>
        </div>
    </div>

    <style>
        @media print {
            body * {
                visibility: visible;
            }
            .card, .card-body {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
            }
            .btn, a, hr, header, footer, .sidebar, nav {
                display: none !important;
            }
            @page {
                margin: 0.5cm;
            }
        }
    </style>
@endsection
