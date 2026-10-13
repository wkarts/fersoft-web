<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>DANFSe v2.0 - NFS-e Nº {{ $dados['numero_nfse'] }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 5mm;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 7.5px;
            color: #000;
            background-color: #e5e7eb;
            margin: 0;
            padding: 20px 0;
        }

        /* Simulação visual de folha A4 no ecrã */
        .folha-a4 {
            position: relative;
            overflow: hidden;
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: #ffffff;
            padding: 6mm 8mm;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        /* FAIXA DIAGONAL DE NFS-e CANCELADA */
        .tarja-cancelada {
            position: absolute;
            top: 42%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-32deg);
            font-size: 54px;
            font-weight: 900;
            color: rgba(220, 38, 38, 0.32);
            border: 7px dashed rgba(220, 38, 38, 0.50);
            padding: 12px 48px;
            text-transform: uppercase;
            letter-spacing: 6px;
            pointer-events: none;
            z-index: 999;
            white-space: nowrap;
        }

        /* Barra de Ações Superior */
        .barra-acoes {
            text-align: center;
            margin-bottom: 15px;
        }
        .btn-acao {
            background-color: #1e40af;
            color: #ffffff;
            border: none;
            padding: 9px 22px;
            font-size: 13px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        .btn-acao:hover {
            background-color: #1d4ed8;
        }

        /* Estrutura das Tabelas do DANFSe */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3px;
        }

        td, th {
            border: 1px solid #111;
            padding: 2px 3.5px;
            vertical-align: top;
        }

        .cabecalho-secao {
            background-color: #1e3a8a !important;
            color: #ffffff !important;
            font-weight: bold;
            font-size: 7.5px;
            padding: 2.5px 4px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .rotulo {
            font-size: 6.2px;
            color: #4b5563;
            text-transform: uppercase;
            display: block;
            margin-bottom: 1px;
            font-weight: normal;
        }

        .valor {
            font-size: 7.8px;
            font-weight: bold;
            color: #000;
            display: block;
            line-height: 1.15;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }

        /* Ajustes de Impressão */
        @media print {
            body {
                background: transparent;
                padding: 0;
            }
            .barra-acoes {
                display: none !important;
            }
            .folha-a4 {
                width: 100%;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>

<!-- BOTÃO DE IMPRESSÃO -->
<div class="barra-acoes">
    <button class="btn-acao" onclick="window.print()">
        🖨️ IMPRIMIR / GUARDAR EM PDF
    </button>
</div>

<div class="folha-a4">

    @if(($item->estado ?? '') === 'cancelado')
        <div class="tarja-cancelada">
            NFS-e CANCELADA
        </div>
    @endif

    <!-- CABEÇALHO COM LOGÓTIPO, METADADOS E QR CODE -->
    <table>
        <tr>
            <!-- Coluna 1: Identificação NFS-e -->
            <td width="20%" class="text-center" style="vertical-align: middle; padding: 4px;">
                <strong style="font-size: 17px; color: #1e3a8a; display: block; line-height: 1;">NFS-e</strong>
                <span style="font-size: 7.5px; font-weight: bold;">Nota Fiscal de Serviço Eletrônica</span><br><br>
                <strong style="font-size: 8.5px; display: block;">DANFSe v2.0</strong>
                <span style="font-size: 6.8px;">Documento Auxiliar da NFS-e</span><br><br>
                <span style="font-size: 6.5px; color: #444;">
                        Município: {{ $dados['prestador_cidade'] }}<br>
                        Ambiente: Produção
                    </span>
            </td>

            <!-- Coluna 2: Dados da DPS e Chave -->
            <td width="63%" style="padding: 0;">
                <table style="margin: 0; border: none;">
                    <tr>
                        <td colspan="3" style="background: #f3f4f6;">
                            <span class="rotulo">CHAVE DE ACESSO DA NFS-E</span>
                            <span class="valor" style="font-size: 9.8px; letter-spacing: 0.8px; color: #111;">
                                    {{ $dados['chave'] }}
                                </span>
                        </td>
                    </tr>
                    <tr>
                        <td width="33%"><span class="rotulo">NÚMERO DA NFS-E</span><span class="valor" style="font-size: 10px; color: #1e3a8a;">{{ $dados['numero_nfse'] }}</span></td>
                        <td width="33%"><span class="rotulo">NÚMERO DA DPS</span><span class="valor">{{ $dados['numero_dps'] }}</span></td>
                        <td width="34%"><span class="rotulo">SÉRIE DA DPS</span><span class="valor">{{ $dados['serie_dps'] }}</span></td>
                    </tr>
                    <tr>
                        <td><span class="rotulo">COMPETÊNCIA DA NFS-E</span><span class="valor">{{ $dados['competencia'] }}</span></td>
                        <td><span class="rotulo">EMISSÃO DA NFS-E</span><span class="valor">{{ $dados['data_emissao'] }}</span></td>
                        <td><span class="rotulo">EMITENTE DA NFS-E</span><span class="valor">Prestador</span></td>
                    </tr>
                    <tr>
                        <td><span class="rotulo">SITUAÇÃO DA NFS-E</span>
                            @if(($item->estado ?? '') === 'cancelado')
                                <span class="valor" style="color: #dc2626;">NFS-e Cancelada</span>
                            @else
                                <span class="valor" style="color: #047857;">NFS-e Regular</span>
                            @endif
                        </td>
                        <td><span class="rotulo">EMISSÃO DA DPS</span><span class="valor">{{ $dados['data_emissao_dps'] ?? $dados['data_emissao'] }}</span></td>
                        <td><span class="rotulo">FINALIDADE</span><span class="valor">Padrão</span></td>
                    </tr>
                </table>
            </td>

            <!-- Coluna 3: Código QR Oficial -->
            <td width="17%" class="text-center" style="vertical-align: middle; padding: 4px;">
                @php
                    $urlConsulta = 'https://www.nfse.gov.br/ConsultaPublica?tpc=1&chave=' . $dados['chave'];
                    $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=115x115&margin=0&data=' . urlencode($urlConsulta);
                @endphp
                <img src="{{ $qrCodeUrl }}" alt="QR Code" style="width: 78px; height: 78px; display: block; margin: 0 auto 3px auto;">
                <span style="font-size: 5.6px; line-height: 1.1; color: #374151; display: block;">
                        Consulte a autenticidade apontando a câmara do telemóvel para o código QR
                    </span>
            </td>
        </tr>
    </table>

    <!-- PRESTADOR / FORNECEDOR -->
    <table>
        <tr><th colspan="4" class="cabecalho-secao">PRESTADOR / FORNECEDOR</th></tr>
        <tr>
            <td width="28%"><span class="rotulo">CNPJ/CPF/NIF</span><span class="valor">{{ $dados['prestador_cnpj'] }}</span></td>
            <td width="24%"><span class="rotulo">INSCRIÇÃO MUNICIPAL</span><span class="valor">{{ $dados['prestador_im'] ?: '-' }}</span></td>
            <td width="28%"><span class="rotulo">MUNICÍPIO / UF</span><span class="valor">{{ $dados['prestador_cidade'] }}</span></td>
            <td width="20%"><span class="rotulo">TELEFONE</span><span class="valor">{{ $dados['prestador_fone'] ?: '-' }}</span></td>
        </tr>
        <tr>
            <td colspan="2"><span class="rotulo">NOME / RAZÃO SOCIAL</span><span class="valor">{{ $dados['prestador_nome'] }}</span></td>
            <td colspan="2"><span class="rotulo">ENDEREÇO</span><span class="valor">{{ $dados['prestador_endereco'] }} - CEP: {{ $dados['prestador_cep'] }}</span></td>
        </tr>
        <tr>
            <td colspan="2"><span class="rotulo">SIMPLES NACIONAL NA DATA DE COMPETÊNCIA</span><span class="valor">Não optante</span></td>
            <td colspan="2"><span class="rotulo">REGIME DE APURAÇÃO TRIBUTÁRIA</span><span class="valor">Regime Normal</span></td>
        </tr>
    </table>

    <!-- TOMADOR / ADQUIRENTE -->
    <table>
        <tr><th colspan="4" class="cabecalho-secao">TOMADOR / ADQUIRENTE</th></tr>
        <tr>
            <td width="28%"><span class="rotulo">CNPJ/CPF/NIF</span><span class="valor">{{ $dados['tomador_cnpj'] }}</span></td>
            <td width="24%"><span class="rotulo">INSCRIÇÃO MUNICIPAL</span><span class="valor">-</span></td>
            <td width="28%"><span class="rotulo">MUNICÍPIO / UF</span><span class="valor">{{ $dados['tomador_cidade'] }}</span></td>
            <td width="20%"><span class="rotulo">CEP</span><span class="valor">{{ $dados['tomador_cep'] ?: '-' }}</span></td>
        </tr>
        <tr>
            <td colspan="2"><span class="rotulo">NOME / RAZÃO SOCIAL</span><span class="valor">{{ $dados['tomador_nome'] }}</span></td>
            <td colspan="2"><span class="rotulo">ENDEREÇO</span><span class="valor">{{ $dados['tomador_endereco'] }}</span></td>
        </tr>
    </table>

    <!-- INTERMEDIÁRIO / DESTINATÁRIO -->
    <table>
        <tr>
            <td width="50%"><span class="rotulo">DESTINATÁRIO DA OPERAÇÃO</span><span class="valor" style="font-weight: normal; color: #555;">DESTINATÁRIO DA OPERAÇÃO NÃO IDENTIFICADO NA NFS-e</span></td>
            <td width="50%"><span class="rotulo">INTERMEDIÁRIO DA OPERAÇÃO</span><span class="valor" style="font-weight: normal; color: #555;">INTERMEDIÁRIO DA OPERAÇÃO NÃO IDENTIFICADO NA NFS-e</span></td>
        </tr>
    </table>

    <!-- SERVIÇO PRESTADO -->
    <table>
        <tr><th colspan="3" class="cabecalho-secao">SERVIÇO PRESTADO</th></tr>
        <tr>
            <td width="35%">
                <span class="rotulo">CÓD. TRIBUTAÇÃO NACIONAL / MUNICIPAL</span>
                <span class="valor">{{ $dados['codigo_servico'] }} / -</span>
            </td>
            <td width="30%">
                <span class="rotulo">CÓDIGO DA NBS</span>
                <span class="valor">{{ $dados['codigo_nbs'] }}</span>
            </td>
            <td width="35%">
                <span class="rotulo">LOCAL DA PRESTAÇÃO / SIGLA UF / PAÍS</span>
                <span class="valor">{{ $dados['local_prestacao'] }} / -</span>
            </td>
        </tr>

        @if(!empty($dados['descricao_trib_nac']) || !empty($dados['descricao_nbs']))
            <tr>
                <td colspan="3" style="background-color: #fafafa; padding: 3px 4px;">
                    @if(!empty($dados['descricao_trib_nac']))
                        <div style="font-size: 7.5px; font-weight: bold; color: #111; line-height: 1.2;">
                            {{ $dados['descricao_trib_nac'] }}
                        </div>
                    @endif
                    @if(!empty($dados['descricao_nbs']))
                        <div style="font-size: 7px; color: #4b5563; margin-top: 2px;">
                            <strong style="text-transform: uppercase;">NBS:</strong> {{ $dados['descricao_nbs'] }}
                        </div>
                    @endif
                </td>
            </tr>
        @endif

        <tr>
            <td colspan="3" style="min-height: 48px;">
                <span class="rotulo">DESCRIÇÃO DO SERVIÇO</span>
                <span class="valor" style="font-weight: normal; font-size: 8.5px; line-height: 1.35;">
                        {!! nl2br(e($dados['discriminacao'])) !!}
                    </span>
            </td>
        </tr>
    </table>

    <!-- TRIBUTAÇÃO MUNICIPAL (ISSQN) -->
    <table>
        <tr><th colspan="5" class="cabecalho-secao">TRIBUTAÇÃO MUNICIPAL (ISSQN)</th></tr>
        <tr>
            <td width="25%"><span class="rotulo">TIPO DE TRIBUTAÇÃO DO ISSQN</span><span class="valor">Operação Tributável</span></td>
            <td width="20%"><span class="rotulo">BASE DE CÁLCULO</span><span class="valor">R$ {{ number_format($dados['valor_total'], 2, ',', '.') }}</span></td>
            <td width="15%"><span class="rotulo">ALÍQUOTA</span><span class="valor">{{ $dados['aliquota_iss'] }}%</span></td>
            <td width="20%"><span class="rotulo">RETENÇÃO DO ISSQN</span><span class="valor">{{ $dados['iss_retido'] ? 'Retido' : 'Não Retido' }}</span></td>
            <td width="20%"><span class="rotulo">ISSQN APURADO</span><span class="valor">R$ {{ number_format($dados['valor_iss'], 2, ',', '.') }}</span></td>
        </tr>
    </table>

    <!-- TRIBUTAÇÃO FEDERAL -->
    <table>
        <tr><th colspan="6" class="cabecalho-secao">TRIBUTAÇÃO FEDERAL (RETENÇÕES)</th></tr>
        <tr>
            <td width="16%"><span class="rotulo">PIS</span><span class="valor">R$ 0,00</span></td>
            <td width="16%"><span class="rotulo">COFINS</span><span class="valor">R$ 0,00</span></td>
            <td width="17%"><span class="rotulo">INSS / CP</span><span class="valor">R$ 0,00</span></td>
            <td width="17%"><span class="rotulo">IRRF</span><span class="valor">R$ 0,00</span></td>
            <td width="17%"><span class="rotulo">CSLL</span><span class="valor">R$ 0,00</span></td>
            <td width="17%"><span class="rotulo">TOTAL RETENÇÕES</span><span class="valor">R$ 0,00</span></td>
        </tr>
    </table>

    <!-- VALORES TOTAIS DA NFS-E -->
    <table>
        <tr><th colspan="4" class="cabecalho-secao">VALOR TOTAL DA NFS-E</th></tr>
        <tr>
            <td width="25%"><span class="rotulo">VALOR DA OPERAÇÃO / SERVIÇO</span><span class="valor">R$ {{ number_format($dados['valor_total'], 2, ',', '.') }}</span></td>
            <td width="25%"><span class="rotulo">DESCONTO INCONDICIONADO</span><span class="valor">R$ 0,00</span></td>
            <td width="25%"><span class="rotulo">DESCONTO CONDICIONADO</span><span class="valor">R$ 0,00</span></td>
            <td width="25%" style="background: #eff6ff;">
                <span class="rotulo">VALOR LÍQUIDO DA NFS-E</span>
                <span class="valor" style="font-size: 11px; color: #1e3a8a;">R$ {{ number_format($dados['valor_total'], 2, ',', '.') }}</span>
            </td>
        </tr>
    </table>

    <!-- INFORMAÇÕES COMPLEMENTARES -->
    <table>
        <tr><th class="cabecalho-secao">INFORMAÇÕES COMPLEMENTARES</th></tr>
        <tr>
            <td style="font-size: 7.2px; line-height: 1.35; color: #374151;">
                A autenticidade desta NFS-e pode ser verificada pela leitura do código QR acima ou pela consulta da chave de acesso no portal nacional da NFS-e (www.nfse.gov.br).<br>
                Totais aproximados dos Tributos cfe. Lei nº 12.741/2012: Federais: R$ 0,00; Estaduais: R$ 0,00; Municipais: R$ {{ number_format($dados['valor_iss'], 2, ',', '.') }}.
            </td>
        </tr>
    </table>

    <!-- CANHOTO INFERIOR / ASSINATURA -->
    <table style="margin-top: 10px;">
        <tr>
            <td width="30%"><span class="rotulo">DATA DE CIENTIFICAÇÃO</span><span class="valor" style="margin-top: 14px;">____ / ____ / ________</span></td>
            <td width="40%"><span class="rotulo">IDENTIFICAÇÃO E ASSINATURA DO RECEBEDOR</span><span class="valor" style="margin-top: 14px;">_________________________________________________</span></td>
            <td width="30%">
                <span class="rotulo">Nº NFS-E / CHAVE NFS-E</span>
                <span class="valor" style="font-size: 7px; margin-top: 2px;">
                        Nº {{ $dados['numero_nfse'] }}<br>
                        {{ $dados['chave'] }}
                    </span>
            </td>
        </tr>
    </table>

</div>

</body>
</html>
