@extends('default.layout')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-document-fiscal.css') }}?v={{ filemtime(public_path('css/ui-document-fiscal.css')) }}">
@endpush
@section('content')
    <style>
        /* Tabela Analítica */
        .table-apuracao-analitica { table-layout: fixed; width: 100%; border-collapse: collapse; background: #fff; }
        .table-apuracao-analitica .col-nome { width: 70%; }
        .table-apuracao-analitica .col-valor { width: 30%; text-align: right !important; padding-right: 2.5rem !important; }
        .table-apuracao-analitica td.col-valor .valor-apuracao { display: block; width: 100%; text-align: right !important; }
        .row-group { cursor: pointer; border-bottom: 1px solid #ebedf3; }
        .row-group:hover { background-color: #f3f6f9 !important; }
        .row-detail { display: none; background-color: #fbfbfb; }
        .font-weight-boldest { font-weight: 900 !important; }
        .text-detail { padding-left: 3.5rem !important; }

        /* Tabela Sintética Profissional */
        .table-apuracao-sintetica { width: 100%; border-collapse: collapse; text-align: right; background: #fff; border: 1px solid #ebedf3; }
        .table-apuracao-sintetica th { text-align: center; font-weight: bold; background-color: #1e1e2d; color: #fff; padding: 1rem 0.75rem; font-size: 0.95em; text-transform: uppercase; border: 1px solid #ebedf3; }
        .table-apuracao-sintetica td { border: 1px solid #ebedf3; padding: 0.85rem 0.75rem; vertical-align: middle; }
        .table-apuracao-sintetica .td-destaque { background-color: #f8f9fa; font-weight: 800; font-size: 1.15em; }

        .header-custom { display: flex; align-items: center; justify-content: space-between; width: 100%; }

        /* IMPRESSÃO EM PAISAGEM */
        @media print {
            @page { size: landscape; margin: 10mm; } /* Força o formato Paisagem */
            body.printing-apuracao { background: #fff !important; }
            body.printing-apuracao > *:not(#apuracao-relatorio) { display: none !important; }
            body.printing-apuracao #apuracao-relatorio { display: block !important; width: 100% !important; margin: 0 !important; padding: 0 !important; background: #fff !important; box-shadow: none !important; border: 0 !important; }
            body.printing-apuracao .no-print { display: none !important; }
        }
    </style>

    <div class="document-fiscal-page document-fiscal-page--apuracao container mt-5">
        <div class="card card-custom gutter-b" id="apuracao-relatorio">
            <div class="card-header py-3">
                <div class="header-custom d-flex align-items-center justify-content-between w-100">
                    <div style="flex: 1;" class="no-print">
                        <button class="btn btn-sm btn-light-primary font-weight-bold" onclick="imprimirApuracao()">
                            <i class="la la-print"></i> Imprimir
                        </button>
                    </div>

                    <div style="flex: 2; text-align: center;">
                        <h3 class="card-title font-weight-bolder text-dark mb-0">
                            Apuração de Resultado - {{ str_pad($mes, 2, '0', STR_PAD_LEFT) }}/{{ $ano }}
                        </h3>
                        <!-- AVISO DE PERÍODO ENCERRADO -->
                        @if(isset($apuracao) && $apuracao != null)
                            <span class="label label-inline label-danger font-weight-bold mt-2">PERÍODO ENCERRADO</span>
                        @endif
                    </div>

                    <div style="flex: 1; text-align: right;" class="no-print">
                        @if(isset($apuracao) && $apuracao != null)
                            <!-- BOTÃO DE REABRIR (SE JÁ ESTIVER FINALIZADO) -->
                            <form action="/apuracao/reabrir" method="POST" id="form-reabrir" style="display: inline-block;">
                                @csrf
                                <input type="hidden" name="apuracao_id" value="{{ $apuracao->id }}">
                                <button type="button" class="btn btn-sm btn-warning font-weight-bold" onclick="confirmarReabertura()">
                                    <i class="la la-unlock"></i> Reabrir Período
                                </button>
                            </form>
                        @else
                            <!-- BOTÃO DE FINALIZAR (SE AINDA ESTIVER ABERTO) -->
                            <form action="/apuracao/finalizar" method="POST" id="form-finalizar" style="display: inline-block;">
                                @csrf
                                <input type="hidden" name="mes" value="{{ $mes }}">
                                <input type="hidden" name="ano" value="{{ $ano }}">
                                <input type="hidden" name="regime" value="{{ $regime }}">
                                <input type="hidden" name="filial_id" value="{{ $filial_id }}"> <!-- ADICIONE ESTA LINHA -->
                                <input type="hidden" name="lucro_prejuizo_liquido" id="val_final">
                                <button type="button" class="btn btn-sm btn-success font-weight-bold" onclick="confirmar()">
                                    <i class="la la-lock"></i> Finalizar Período
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card-body">
                <!-- FORMULÁRIO DE FILTROS -->
                <form method="get" action="/apuracao" id="form-filtro" class="mb-8 no-print">
                    <div class="row align-items-center">
                        <div class="col-lg-2">
                            <div class="form-group mb-0">
                                <label class="font-weight-bold">Período</label>
                                <div class="d-flex">
                                    <select name="mes" class="form-control mr-1 custom-select" onchange="$('#form-filtro').submit()">
                                        @for($m=1; $m<=12; $m++) <option value="{{$m}}" {{$mes==$m?'selected':''}}>{{str_pad($m,2,'0',STR_PAD_LEFT)}}</option> @endfor
                                    </select>
                                    <select name="ano" class="form-control custom-select" onchange="$('#form-filtro').submit()">
                                        @for($a=date('Y')-2; $a<=date('Y')+2; $a++) <option value="{{$a}}" {{$ano==$a?'selected':''}}>{{$a}}</option> @endfor
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- CORREÇÃO DO CAMPO DE UNIDADE/FILIAL (ESTAVA SEM BORDA) -->
                        <div class="col-lg-4">
                            <div class="form-group mb-0">
                                <label class="font-weight-bold">Unidade / Filial</label>
                                <select class="form-control custom-select" name="filial_id" id="filial_id" onchange="$('#form-filtro').submit()">
                                    <option value="todos" {{$filial_id=='todos'?'selected':''}}>-- TODAS AS UNIDADES --</option>
                                    <option value="matriz" {{$filial_id=='matriz'?'selected':''}}>{{ $nomeMatriz ?? 'Matriz' }} (Matriz)</option>
                                    @foreach($filiais as $f)
                                        <option value="{{$f->id}}" {{$filial_id==$f->id?'selected':''}}>FILIAL - {{ $f->razao_social ?? $f->nome }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <div class="form-group mb-0">
                                <label class="font-weight-bold">Regime</label>
                                <div class="radio-inline mt-1">
                                    <label class="radio radio-success"><input type="radio" name="regime" value="competencia" {{$regime=='competencia'?'checked':''}} onchange="$('#form-filtro').submit()"><span></span> Comp.</label>
                                    <label class="radio radio-success"><input type="radio" name="regime" value="caixa" {{$regime=='caixa'?'checked':''}} onchange="$('#form-filtro').submit()"><span></span> Caixa</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-3 text-right">
                            <button type="button" class="btn btn-sm btn-outline-info font-weight-bold mr-2" onclick="$('#bloco-simulacao').slideToggle()">
                                <i class="la la-calculator"></i> Simular Estoque
                            </button>
                            <button type="submit" class="btn btn-sm btn-primary font-weight-bold">
                                <i class="la la-filter"></i> Aplicar
                            </button>
                        </div>
                    </div>

                    <!-- BLOCO DE SIMULAÇÃO -->
                    <div class="row mt-4 p-4 rounded" id="bloco-simulacao" style="background-color: #f3f6f9; border: 1px dashed #b5b5c3; display: {{ request()->hasAny(['simular_est_inicial', 'simular_compras', 'simular_est_final']) ? 'flex' : 'none' }};">
                        <div class="col-12 mb-3">
                            <span class="text-info font-weight-bolder text-uppercase small"><i class="la la-info-circle text-info"></i> Simulação de CMV</span>
                        </div>
                        <div class="col-lg-4">
                            <label class="small font-weight-bold">Estoque Inicial (R$)</label>
                            <input type="number" step="0.01" name="simular_est_inicial" class="form-control form-control-sm" value="{{ request('simular_est_inicial') }}">
                        </div>
                        <div class="col-lg-4">
                            <label class="small font-weight-bold">Compras no Mês (R$)</label>
                            <input type="number" step="0.01" name="simular_compras" class="form-control form-control-sm" value="{{ request('simular_compras') }}">
                        </div>
                        <div class="col-lg-4">
                            <label class="small font-weight-bold">Estoque Final (R$)</label>
                            <input type="number" step="0.01" name="simular_est_final" class="form-control form-control-sm" value="{{ request('simular_est_final') }}">
                        </div>
                    </div>
                </form>

                @php
                    $recBruta = $dados['receita_bruta'] ?? 0;

                    // 1. Separa Impostos de Devoluções/Cancelamentos
                    $valIcms             = abs($dados['deducao_venda'] ?? 0);
                    $valVendasCanceladas = abs($dados['venda_cancelada'] ?? 0) + abs($dados['devolucao'] ?? 0);

                    // 2. Receita Líquida Contábil (Faturamento - Impostos - Cancelamentos)
                    $receitaLiquidaSintetica = $recBruta - $valIcms - $valVendasCanceladas;

                    // 3. Tratando despesas operacionais
                    $valPessoal        = abs($dados['pessoal'] ?? 0);
                    $valAdministrativa = abs($dados['administrativa'] ?? 0) + $valPessoal;
                    $valOperacional    = abs($dados['operacional'] ?? 0);
                    $valTributaria     = abs($dados['tributaria'] ?? 0);
                    $valFinanceira     = abs($dados['financeira'] ?? 0);

                    // 4. Total de Saídas (CMV + Todas as Despesas Operacionais)
                    $totalDespesaSintetica = $cmvReal + $valAdministrativa + $valOperacional + $valTributaria + $valFinanceira;

                    // 5. Resultado Final
                    $resultadoMesSintetico = $receitaLiquidaSintetica - $totalDespesaSintetica;
                    $resultadoAcumulado = $resultadoMesSintetico + $saldoAnterior;

                    // ==========================================
                    // Variáveis Tabela Analítica (Restauradas para não quebrar a view)
                    $deducoes            = $valIcms;
                    $devolucaos          = $valVendasCanceladas;

                    $recLiquidaAnalitica = $receitaLiquidaSintetica;
                    $lucBrutoAnalitico   = $recLiquidaAnalitica - $cmvReal;
                    $despesasAnaliticas  = $valAdministrativa + $valOperacional + $valTributaria;
                    $outrosAnaliticos    = ($dados['financeira'] ?? 0) + ($dados['nao_operacional'] ?? 0);
                    $resFinalAnalitico   = $lucBrutoAnalitico - $despesasAnaliticas + $outrosAnaliticos + $saldoAnterior;
                @endphp

                    <!-- BOTÕES DE ALTERNÂNCIA DE VISUALIZAÇÃO -->
                <div class="d-flex justify-content-end mb-4 no-print">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary font-weight-bold active" id="btn-sintetico" onclick="switchView('sintetico')">
                            <i class="la la-table"></i> Grade Sintética
                        </button>
                        <button type="button" class="btn btn-outline-primary font-weight-bold" id="btn-analitico" onclick="switchView('analitico')">
                            <i class="la la-list"></i> Relatório Analítico
                        </button>
                    </div>
                </div>

                <!-- ============================================== -->
                <!-- 1. TABELA SINTÉTICA (VISUAL PROFISSIONAL) -->
                <!-- ============================================== -->
                <div id="view-sintetica" class="table-responsive">
                    <table class="table-apuracao-sintetica table-hover">
                        <thead>
                        <tr>
                            <th style="text-align: left; width: 25%;">DESCRIÇÃO DO ITEM</th>
                            <th style="width: 15%;">VALORES (R$)</th>
                            <th style="width: 15%;">RECEITA LÍQUIDA</th>
                            <th style="width: 15%;">TOTAL DESPESAS</th>
                            <th style="width: 15%;">RESULTADO DO MÊS</th>
                            <th style="width: 15%;">RESULTADO ACUMULADO</th>
                        </tr>
                        </thead>
                        <tbody>
                        <!-- BLOCO DE RECEITAS -->
                        <tr>
                            <td style="text-align: left; font-weight: 800; color: #1bc5bd; font-size: 1.1em;">
                                <i class="la la-chart-bar text-success mr-2"></i> FATURAMENTO BRUTO
                            </td>
                            <td style="font-weight: 800; color: #1bc5bd; font-size: 1.1em;">R$ {{ number_format($recBruta, 2, ',', '.') }}</td>

                            <!-- GRANDES TOTAIS (MANTIDOS FIXOS NA DIREITA) -->
                            <td rowspan="12" class="td-destaque" style="color: #3F4254;">
                                R$ {{ number_format($receitaLiquidaSintetica, 2, ',', '.') }}
                            </td>
                            <td rowspan="12" class="td-destaque" style="color: #f64e60;">
                                R$ {{ number_format($totalDespesaSintetica, 2, ',', '.') }}
                            </td>
                            <td rowspan="12" class="td-destaque" style="color: {{ $resultadoMesSintetico < 0 ? '#f64e60' : '#1bc5bd' }};">
                                R$ {{ number_format($resultadoMesSintetico, 2, ',', '.') }}
                            </td>
                            <td rowspan="12" class="td-destaque" style="color: {{ $resultadoAcumulado < 0 ? '#f64e60' : '#1bc5bd' }};">
                                R$ {{ number_format($resultadoAcumulado, 2, ',', '.') }}
                                <div style="font-size: 0.65em; color: #a1a5b7; font-weight: 500; margin-top: 5px;">
                                    (Inclui Ant: R$ {{ number_format($saldoAnterior, 2, ',', '.') }})
                                </div>
                            </td>
                        </tr>

                        <!-- DEDUÇÕES DA RECEITA -->
                        <tr>
                            <td style="text-align: left; padding-left: 2rem; color: #f64e60;">(-) ICMS / Impostos s. Venda</td>
                            <td style="color: #f64e60;">R$ {{ number_format($valIcms, 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td style="text-align: left; padding-left: 2rem; color: #f64e60;">(-) Vendas Canceladas / Devoluções</td>
                            <td style="color: #f64e60;">R$ {{ number_format($valVendasCanceladas, 2, ',', '.') }}</td>
                        </tr>

                        <!-- BLOCO DE CMV -->
                        <tr>
                            <td style="text-align: left; padding-left: 2rem; color: #7e8299;">(+) Estoque Inicial</td>
                            <td style="color: #7e8299;">R$ {{ number_format($estoqueInicial, 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td style="text-align: left; padding-left: 2rem; color: #7e8299;">(+) Compras no Mês</td>
                            <td style="color: #7e8299;">R$ {{ number_format($comprasDoMes, 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td style="text-align: left; padding-left: 2rem; color: #7e8299;">(-) Estoque Final</td>
                            <td style="color: #7e8299;">R$ {{ number_format($estoqueFinal, 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td style="text-align: left; font-weight: bold; color: #ffa800;">
                                <i class="la la-box text-warning mr-2"></i> (=) CUSTO DA MERC. VENDIDA
                            </td>
                            <td style="font-weight: bold; color: #ffa800;">R$ {{ number_format($cmvReal, 2, ',', '.') }}</td>
                        </tr>

                        <!-- BLOCO DE DESPESAS OPERACIONAIS -->
                        <tr>
                            <td style="text-align: left; font-weight: bold; color: #f64e60; padding-top: 1rem;">
                                <i class="la la-minus-circle text-danger mr-2"></i> DESPESAS OPERACIONAIS
                            </td>
                            <td style="font-weight: bold; color: #f64e60; padding-top: 1rem;"></td>
                        </tr>
                        <tr>
                            <td style="text-align: left; padding-left: 2rem; color: #7e8299;">Administrativas / Pessoal</td>
                            <td style="color: #7e8299;">R$ {{ number_format($valAdministrativa, 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td style="text-align: left; padding-left: 2rem; color: #7e8299;">Operacionais</td>
                            <td style="color: #7e8299;">R$ {{ number_format($valOperacional, 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td style="text-align: left; padding-left: 2rem; color: #7e8299;">Tributárias</td>
                            <td style="color: #7e8299;">R$ {{ number_format($valTributaria, 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td style="text-align: left; padding-left: 2rem; border-bottom: 2px solid #ebedf3; color: #7e8299;">Financeiras</td>
                            <td style="border-bottom: 2px solid #ebedf3; color: #7e8299;">R$ {{ number_format($valFinanceira, 2, ',', '.') }}</td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ============================================== -->
                <!-- 2. TABELA ANALÍTICA (ESTRUTURA DRE ORIGINAL) -->
                <!-- ============================================== -->
                <div id="view-analitica" style="display: none;">
                    <table class="table table-apuracao-analitica">
                        <colgroup><col class="col-nome"><col class="col-valor"></colgroup>
                        <thead>
                        <tr class="bg-light">
                            <th class="pl-7 col-nome">DESCRIÇÃO (ANALÍTICO)</th>
                            <th class="col-valor">VALOR (R$)</th>
                        </tr>
                        </thead>
                        <tbody>
                        <!-- RECEITA BRUTA -->
                        <tr class="row-group bg-light-success" onclick="toggleDet('rec')">
                            <td class="pl-7 font-weight-bold col-nome"><i class="la la-plus text-success mr-2"></i> (+) RECEITA BRUTA</td>
                            <td class="col-valor font-weight-bold text-success"><span class="valor-apuracao">R$ {{number_format($recBruta,2,',','.')}}</span></td>
                        </tr>
                        @foreach($detalhes['receita_bruta'] as $det)
                            <tr class="row-detail rec">
                                <td class="text-detail text-muted small col-nome">{{$det->categoria_nome}}</td>
                                <td class="col-valor text-muted small"><span class="valor-apuracao">R$ {{number_format($det->total,2,',','.')}}</span></td>
                            </tr>
                        @endforeach

                        <!-- DEDUÇÕES DA RECEITA -->
                        <tr class="row-group" onclick="toggleDet('ded_venda')" style="background-color: #fff5f5">
                            <td class="pl-7 font-weight-bold text-danger col-nome"><i class="la la-minus mr-2"></i> (-) DEDUÇÕES DA RECEITA (IMPOSTOS S/ FATURAMENTO)</td>
                            <td class="col-valor font-weight-bold text-danger"><span class="valor-apuracao">R$ {{number_format($deducoes,2,',','.')}}</span></td>
                        </tr>
                        @foreach($detalhes['deducao_venda'] as $det)
                            <tr class="row-detail ded_venda">
                                <td class="text-detail text-muted small col-nome">{{$det->categoria_nome}}</td>
                                <td class="col-valor text-danger small"><span class="valor-apuracao">R$ {{number_format(abs($det->total),2,',','.')}}</span></td>
                            </tr>
                        @endforeach

                        @if($devolucaos > 0)
                            <tr class="row-group" onclick="toggleDet('venda_cancelada')" style="background-color: #fff5f5">
                                <td class="pl-7 font-weight-bold text-danger col-nome"><i class="la la-minus mr-2"></i> (-) DEVOLUÇÕES / VENDAS CANCELADAS</td>
                                <td class="col-valor font-weight-bold text-danger"><span class="valor-apuracao">R$ {{number_format($devolucaos,2,',','.')}}</span></td>
                            </tr>
                            @if(isset($detalhes['venda_cancelada']))
                                @foreach($detalhes['venda_cancelada'] as $det)
                                    <tr class="row-detail venda_cancelada">
                                        <td class="text-detail text-muted small col-nome">{{$det->categoria_nome}}</td>
                                        <td class="col-valor text-danger small"><span class="valor-apuracao">R$ {{number_format(abs($det->total),2,',','.')}}</span></td>
                                    </tr>
                                @endforeach
                            @endif
                        @endif

                        <!-- RECEITA LÍQUIDA -->
                        <tr class="bg-secondary text-dark font-weight-boldest">
                            <td class="pl-7 col-nome">(=) RECEITA LÍQUIDA</td>
                            <td class="col-valor"><span class="valor-apuracao">R$ {{number_format($recLiquidaAnalitica,2,',','.')}}</span></td>
                        </tr>

                        <!-- CMV -->
                        <tr class="row-group" onclick="toggleDet('cmv_det')">
                            <td class="pl-7 text-danger font-weight-bold col-nome"><i class="la la-minus-circle mr-2"></i> (-) CUSTO DE MERCADORIA (CMV)</td>
                            <td class="col-valor text-danger font-weight-bold"><span class="valor-apuracao">R$ {{number_format($cmvReal,2,',','.')}}</span></td>
                        </tr>
                        <tr class="row-detail cmv_det"><td class="text-detail text-muted small col-nome">(+) Estoque Inicial</td><td class="col-valor text-muted small"><span class="valor-apuracao">R$ {{number_format($estoqueInicial,2,',','.')}}</span></td></tr>
                        <tr class="row-detail cmv_det"><td class="text-detail text-muted small col-nome">(+) Compras Líquidas</td><td class="col-valor text-muted small"><span class="valor-apuracao">R$ {{number_format($comprasDoMes,2,',','.')}}</span></td></tr>
                        <tr class="row-detail cmv_det"><td class="text-detail text-muted small col-nome">(-) Estoque Final</td><td class="col-valor text-muted small"><span class="valor-apuracao">R$ {{number_format($estoqueFinal,2,',','.')}}</span></td></tr>

                        <!-- LUCRO BRUTO -->
                        <tr class="bg-primary text-white font-weight-boldest">
                            <td class="pl-7 col-nome">(=) LUCRO BRUTO</td>
                            <td class="col-valor"><span class="valor-apuracao">R$ {{number_format($lucBrutoAnalitico,2,',','.')}}</span></td>
                        </tr>

                        <!-- DESPESAS DETALHADAS -->
                        @foreach([
                            'pessoal' => 'COM PESSOAL / FOLHA',
                            'operacional' => 'OPERACIONAIS',
                            'administrativa' => 'ADMINISTRATIVAS',
                            'tributaria' => 'TRIBUTÁRIAS (OUTROS IMPOSTOS)'
                        ] as $key => $label)
                            <tr class="row-group" onclick="toggleDet('{{$key}}')" style="background-color: #fff5f5">
                                <td class="pl-7 font-weight-bold text-danger col-nome"><i class="la la-minus mr-2"></i> (-) DESPESAS {{$label}}</td>
                                <td class="col-valor font-weight-bold text-danger"><span class="valor-apuracao">R$ {{number_format(abs($dados[$key] ?? 0),2,',','.')}}</span></td>
                            </tr>
                            @foreach($detalhes[$key] as $det)
                                <tr class="row-detail {{$key}}">
                                    <td class="text-detail text-muted small col-nome">{{$det->categoria_nome}}</td>
                                    <td class="col-valor text-danger small"><span class="valor-apuracao">R$ {{number_format(abs($det->total),2,',','.')}}</span></td>
                                </tr>
                            @endforeach
                        @endforeach

                        <!-- FINANCEIRO E NÃO OPERACIONAL -->
                        @foreach(['financeira' => 'FINANCEIRO', 'nao_operacional' => 'NÃO OPERACIONAL'] as $key => $label)
                            <tr class="row-group bg-light" onclick="toggleDet('{{$key}}')">
                                <td class="pl-7 font-weight-bold col-nome"><i class="la la-plus-circle mr-2"></i> (+/-) {{$label}}</td>
                                <td class="col-valor font-weight-bold {{ ($dados[$key] ?? 0) < 0 ? 'text-danger' : 'text-success' }}"><span class="valor-apuracao">R$ {{number_format($dados[$key] ?? 0,2,',','.')}}</span></td>
                            </tr>
                            @foreach($detalhes[$key] as $det)
                                <tr class="row-detail {{$key}}">
                                    <td class="text-detail text-muted small col-nome">{{$det->categoria_nome}}</td>
                                    <td class="col-valor small {{ $det->total < 0 ? 'text-danger' : 'text-success' }}"><span class="valor-apuracao">R$ {{number_format($det->total,2,',','.')}}</span></td>
                                </tr>
                            @endforeach
                        @endforeach

                        <tr class="row-top"><td class="pl-7 text-muted font-italic col-nome">Saldo Mês Anterior</td><td class="col-valor text-muted"><span class="valor-apuracao">R$ {{number_format($saldoAnterior,2,',','.')}}</span></td></tr>

                        <!-- RESULTADO LÍQUIDO FINAL (ANALÍTICO) -->
                        <tr class="{{ $resFinalAnalitico >= 0 ? 'bg-success' : 'bg-danger' }} text-white font-weight-boldest">
                            <td class="pl-7 h4 col-nome">RESULTADO LÍQUIDO FINAL</td>
                            <td class="col-valor h4 text-white"><span class="valor-apuracao">R$ {{number_format($resFinalAnalitico,2,',','.')}}</span></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Sempre mandando o acumulado ao salvar no Banco de dados
        document.getElementById('val_final').value = "{{ $resultadoAcumulado }}";

        // Função de alternância de visualização entre Sintético e Analítico
        function switchView(view) {
            if(view === 'sintetico') {
                document.getElementById('view-sintetica').style.display = 'block';
                document.getElementById('view-analitica').style.display = 'none';
                document.getElementById('btn-sintetico').classList.add('active');
                document.getElementById('btn-analitico').classList.remove('active');
            } else {
                document.getElementById('view-sintetica').style.display = 'none';
                document.getElementById('view-analitica').style.display = 'block';
                document.getElementById('btn-sintetico').classList.remove('active');
                document.getElementById('btn-analitico').classList.add('active');
            }
        }

        // Função do ToggleDet da tabela Analítica (Expandir Categorias)
        function toggleDet(cls) {
            const rows = document.querySelectorAll('tr.' + cls);
            if (!rows.length) return;
            const isVisible = Array.from(rows).some(row => row.style.display === 'table-row');
            rows.forEach((row) => {
                row.style.display = isVisible ? 'none' : 'table-row';
            });
        }

        function imprimirApuracao() {
            const relatorio = document.getElementById('apuracao-relatorio');
            if (!relatorio || !relatorio.parentNode) { window.print(); return; }

            const origem = relatorio.parentNode;
            const placeholder = document.createElement('div');
            placeholder.id = 'apuracao-print-placeholder';
            origem.insertBefore(placeholder, relatorio);
            document.body.appendChild(relatorio);
            document.body.classList.add('printing-apuracao');

            const restaurarLayout = function() {
                if (placeholder.parentNode) {
                    placeholder.parentNode.insertBefore(relatorio, placeholder);
                    placeholder.parentNode.removeChild(placeholder);
                }
                document.body.classList.remove('printing-apuracao');
            };

            window.addEventListener('afterprint', restaurarLayout, { once: true });
            window.print();
            setTimeout(restaurarLayout, 1200);
        }

        function confirmar() {
            swal({
                title: "Finalizar?",
                text: "Isso travará o estoque do mês!",
                icon: "warning",
                buttons: ["Não", "Sim"]
            }).then((v) => {
                if(v) $('#form-finalizar').submit();
            });
        }

        function confirmarReabertura() {
            swal({
                title: "Reabrir Apuração?",
                text: "Isso irá destravar o período e o estoque. Você poderá recalcular o mês.",
                icon: "warning",
                buttons: ["Cancelar", "Sim, Reabrir"]
            }).then((v) => {
                if(v) $('#form-reabrir').submit();
            });
        }
    </script>
@endsection
