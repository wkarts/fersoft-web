@extends('default.layout')
@section('content')
    <style>
        .table-apuracao { table-layout: fixed; width: 100%; border-collapse: collapse; }
        .col-nome { width: 70%; }
        .col-valor { width: 30%; text-align: right !important; padding-right: 2.5rem !important; }
        .table-apuracao thead .col-valor,
        .table-apuracao tbody .col-valor,
        .table-apuracao tbody td:nth-child(2) {
            text-align: right !important;
            white-space: nowrap;
        }
        .table-apuracao td.col-valor .valor-apuracao {
            display: block;
            width: 100%;
            text-align: right !important;
        }
        .row-group { cursor: pointer; border-bottom: 1px solid #ebedf3; }
        .row-group:hover { background-color: #f3f6f9 !important; }
        .row-detail { display: none; background-color: #fbfbfb; }
        .font-weight-boldest { font-weight: 900 !important; }
        .header-custom { display: flex; align-items: center; justify-content: space-between; width: 100%; }
        .text-detail { padding-left: 3.5rem !important; }

        @media print {
            @page { margin: 8mm; }
            body.printing-apuracao { background: #fff !important; }
            body.printing-apuracao > *:not(#apuracao-relatorio) { display: none !important; }
            body.printing-apuracao #apuracao-relatorio {
                display: block !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
                box-shadow: none !important;
                border: 0 !important;
            }
            body.printing-apuracao .no-print { display: none !important; }
        }
    </style>

    <div class="container mt-5">
        <div class="card card-custom gutter-b" id="apuracao-relatorio">
            <div class="card-header py-3">
                <div class="header-custom">
                    <div style="flex: 1;" class="no-print">
                        <button class="btn btn-sm btn-light-primary font-weight-bold" onclick="imprimirApuracao()">
                            <i class="la la-print"></i> Imprimir
                        </button>
                    </div>

                    <div style="flex: 2; text-align: center;">
                        <h3 class="card-title font-weight-bolder text-dark mb-0">
                            Apuração de Resultado - {{ str_pad($mes, 2, '0', STR_PAD_LEFT) }}/{{ $ano }}
                        </h3>

                        @if($periodoEncerrado)
                            <span class="label label-light-success label-inline mt-2">Período encerrado</span>
                        @elseif($simulacaoAtiva)
                            <span class="label label-light-info label-inline mt-2">Simulação ativa</span>
                        @endif
                    </div>

                    <div style="flex: 1; text-align: right;" class="no-print">
                        <form action="/apuracao/finalizar" method="POST" id="form-finalizar" style="display: inline-block;">
                            @csrf
                            <input type="hidden" name="mes" value="{{ $mes }}">
                            <input type="hidden" name="ano" value="{{ $ano }}">
                            <input type="hidden" name="regime" value="{{ $regime }}">
                            <input type="hidden" name="filial_id" value="{{ $filial_id }}">

                            @php
                                $podeFinalizar = $filial_id === 'todos' && !$periodoEncerrado && !$simulacaoAtiva;
                            @endphp

                            <button
                                type="button"
                                class="btn btn-sm btn-success font-weight-bold"
                                onclick="confirmar()"
                                {{ $podeFinalizar ? '' : 'disabled' }}
                                title="{{ $podeFinalizar ? 'Finalizar período' : 'Finalize somente em Todas as unidades, sem simulação e quando o período estiver aberto.' }}"
                            >
                                Finalizar Período
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="card-body">
                @if($avisoEstoque)
                    <div class="alert alert-custom alert-light-warning fade show mb-6" role="alert">
                        <div class="alert-icon"><i class="flaticon-warning"></i></div>
                        <div class="alert-text">{{ $avisoEstoque }}</div>
                    </div>
                @endif

                @if($filial_id !== 'todos')
                    <div class="alert alert-custom alert-light-info fade show mb-6 no-print" role="alert">
                        <div class="alert-icon"><i class="flaticon-information"></i></div>
                        <div class="alert-text">
                            A visualização por matriz ou filial é analítica. O saldo anterior e o fechamento mensal permanecem consolidados para toda a empresa.
                        </div>
                    </div>
                @endif

                <form method="get" action="/apuracao" id="form-filtro" class="mb-8 no-print">
                    <div class="row align-items-end">
                        <div class="col-lg-2">
                            <label class="font-weight-bold">Período</label>
                            <div class="d-flex">
                                <select name="mes" class="form-control mr-1" onchange="$('#form-filtro').submit()">
                                    @for($m = 1; $m <= 12; $m++)
                                        <option value="{{ $m }}" {{ $mes == $m ? 'selected' : '' }}>
                                            {{ str_pad($m, 2, '0', STR_PAD_LEFT) }}
                                        </option>
                                    @endfor
                                </select>

                                <select name="ano" class="form-control" onchange="$('#form-filtro').submit()">
                                    @for($a = date('Y') - 2; $a <= date('Y') + 2; $a++)
                                        <option value="{{ $a }}" {{ $ano == $a ? 'selected' : '' }}>{{ $a }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <label class="font-weight-bold">Unidade / Filial</label>

                            @if($filialBloqueada)
                                <input type="hidden" name="filial_id" value="{{ $filial_id }}">
                            @endif

                            <select
                                class="form-control select2"
                                name="filial_id"
                                onchange="$('#form-filtro').submit()"
                                {{ $filialBloqueada ? 'disabled' : '' }}
                            >
                                <option value="todos" {{ $filial_id === 'todos' ? 'selected' : '' }}>-- TODAS AS UNIDADES --</option>
                                <option value="matriz" {{ $filial_id === 'matriz' ? 'selected' : '' }}>{{ $nomeMatriz }} (Matriz)</option>
                                @foreach($filiais as $filial)
                                    <option value="{{ $filial->id }}" {{ (string) $filial_id === (string) $filial->id ? 'selected' : '' }}>
                                        FILIAL - {{ $filial->razao_social ?? $filial->nome }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-3">
                            <label class="font-weight-bold">Regime</label>
                            <div class="radio-inline mt-2">
                                <label class="radio radio-success">
                                    <input type="radio" name="regime" value="competencia" {{ $regime === 'competencia' ? 'checked' : '' }} onchange="$('#form-filtro').submit()">
                                    <span></span> Competência
                                </label>
                                <label class="radio radio-success">
                                    <input type="radio" name="regime" value="caixa" {{ $regime === 'caixa' ? 'checked' : '' }} onchange="$('#form-filtro').submit()">
                                    <span></span> Caixa
                                </label>
                            </div>
                        </div>

                        <div class="col-lg-4 text-right mt-4 mt-lg-0">
                            <button type="button" class="btn btn-sm btn-outline-info font-weight-bold" onclick="$('#bloco-simulacao').slideToggle()">
                                <i class="la la-calculator"></i> Simular Estoque
                            </button>
                            <button type="submit" class="btn btn-sm btn-primary font-weight-bold">Aplicar</button>
                        </div>
                    </div>

                    <div
                        class="row mt-4 p-4 rounded"
                        id="bloco-simulacao"
                        style="background-color: #f3f6f9; border: 1px dashed #b5b5c3; display: {{ $simulacaoAtiva ? 'flex' : 'none' }};"
                    >
                        <div class="col-12 mb-3">
                            <span class="text-info font-weight-bolder text-uppercase small">
                                <i class="la la-info-circle text-info"></i>
                                Simulação de CMV — os valores não são gravados no fechamento
                            </span>
                        </div>

                        <div class="col-lg-4">
                            <label class="small font-weight-bold">Estoque Inicial (R$)</label>
                            <input type="number" step="0.01" name="simular_est_inicial" class="form-control form-control-sm" value="{{ request('simular_est_inicial') }}" placeholder="Ex.: 15000.00">
                        </div>

                        <div class="col-lg-4">
                            <label class="small font-weight-bold">Compras Líquidas (R$)</label>
                            <input type="number" step="0.01" name="simular_compras" class="form-control form-control-sm" value="{{ request('simular_compras') }}" placeholder="Ex.: 5000.00">
                        </div>

                        <div class="col-lg-4">
                            <label class="small font-weight-bold">Estoque Final (R$)</label>
                            <input type="number" step="0.01" name="simular_est_final" class="form-control form-control-sm" value="{{ request('simular_est_final') }}" placeholder="Ex.: 12000.00">
                        </div>
                    </div>
                </form>

                <table class="table table-apuracao">
                    <colgroup>
                        <col class="col-nome">
                        <col class="col-valor">
                    </colgroup>
                    <thead>
                    <tr class="bg-light">
                        <th class="pl-7 col-nome">DESCRIÇÃO</th>
                        <th class="col-valor">VALOR (R$)</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr class="row-group bg-light-success" onclick="toggleDet('rec')">
                        <td class="pl-7 font-weight-bold col-nome"><i class="la la-plus text-success mr-2"></i> (+) RECEITA BRUTA</td>
                        <td class="col-valor font-weight-bold text-success"><span class="valor-apuracao">R$ {{ number_format($receitaBruta, 2, ',', '.') }}</span></td>
                    </tr>
                    @foreach(($detalhes['receita_bruta'] ?? []) as $detalhe)
                        <tr class="row-detail rec">
                            <td class="text-detail text-muted small col-nome">{{ $detalhe->categoria_nome }}</td>
                            <td class="col-valor text-muted small"><span class="valor-apuracao">R$ {{ number_format($detalhe->total, 2, ',', '.') }}</span></td>
                        </tr>
                    @endforeach

                    <tr class="row-group" onclick="toggleDet('deducao_venda')" style="background-color: #fff5f5">
                        <td class="pl-7 font-weight-bold text-danger col-nome"><i class="la la-minus mr-2"></i> (-) DEDUÇÕES DA RECEITA</td>
                        <td class="col-valor font-weight-bold text-danger"><span class="valor-apuracao">R$ {{ number_format($deducoesVenda, 2, ',', '.') }}</span></td>
                    </tr>
                    @foreach(($detalhes['deducao_venda'] ?? []) as $detalhe)
                        <tr class="row-detail deducao_venda">
                            <td class="text-detail text-muted small col-nome">{{ $detalhe->categoria_nome }}</td>
                            <td class="col-valor text-danger small"><span class="valor-apuracao">R$ {{ number_format(abs($detalhe->total), 2, ',', '.') }}</span></td>
                        </tr>
                    @endforeach

                    @if($devolucoesVenda > 0)
                        <tr class="row-group" onclick="toggleDet('devolucao')" style="background-color: #fff5f5">
                            <td class="pl-7 font-weight-bold text-danger col-nome"><i class="la la-minus mr-2"></i> (-) DEVOLUÇÕES DE VENDAS</td>
                            <td class="col-valor font-weight-bold text-danger"><span class="valor-apuracao">R$ {{ number_format($devolucoesVenda, 2, ',', '.') }}</span></td>
                        </tr>
                        @foreach(($detalhes['devolucao'] ?? []) as $detalhe)
                            <tr class="row-detail devolucao">
                                <td class="text-detail text-muted small col-nome">{{ $detalhe->categoria_nome }}</td>
                                <td class="col-valor text-danger small"><span class="valor-apuracao">R$ {{ number_format(abs($detalhe->total), 2, ',', '.') }}</span></td>
                            </tr>
                        @endforeach
                    @endif

                    <tr class="bg-secondary text-dark font-weight-boldest">
                        <td class="pl-7 col-nome">(=) RECEITA LÍQUIDA</td>
                        <td class="col-valor"><span class="valor-apuracao">R$ {{ number_format($receitaLiquida, 2, ',', '.') }}</span></td>
                    </tr>

                    <tr class="row-group" onclick="toggleDet('cmv_det')">
                        <td class="pl-7 text-danger font-weight-bold col-nome"><i class="la la-minus-circle mr-2"></i> (-) CUSTO DE MERCADORIA / PRODUÇÃO (CMV/CPV)</td>
                        <td class="col-valor text-danger font-weight-bold"><span class="valor-apuracao">R$ {{ number_format($cmvReal, 2, ',', '.') }}</span></td>
                    </tr>
                    <tr class="row-detail cmv_det">
                        <td class="text-detail text-muted small col-nome">(+) Estoque Inicial</td>
                        <td class="col-valor text-muted small"><span class="valor-apuracao">R$ {{ number_format($estoqueInicial, 2, ',', '.') }}</span></td>
                    </tr>
                    <tr class="row-detail cmv_det">
                        <td class="text-detail text-muted small col-nome">(+) Compras Brutas</td>
                        <td class="col-valor text-muted small"><span class="valor-apuracao">R$ {{ number_format($comprasBrutas, 2, ',', '.') }}</span></td>
                    </tr>
                    @if($devolucoesCompra > 0)
                        <tr class="row-detail cmv_det">
                            <td class="text-detail text-muted small col-nome">(-) Devoluções de Compras</td>
                            <td class="col-valor text-muted small"><span class="valor-apuracao">R$ {{ number_format($devolucoesCompra, 2, ',', '.') }}</span></td>
                        </tr>
                    @endif
                    <tr class="row-detail cmv_det">
                        <td class="text-detail text-muted small col-nome">(=) Compras Líquidas</td>
                        <td class="col-valor text-muted small"><span class="valor-apuracao">R$ {{ number_format($comprasDoMes, 2, ',', '.') }}</span></td>
                    </tr>
                    <tr class="row-detail cmv_det">
                        <td class="text-detail text-muted small col-nome">(-) Estoque Final</td>
                        <td class="col-valor text-muted small"><span class="valor-apuracao">R$ {{ number_format($estoqueFinal, 2, ',', '.') }}</span></td>
                    </tr>

                    <tr class="bg-primary text-white font-weight-boldest">
                        <td class="pl-7 col-nome">(=) LUCRO BRUTO</td>
                        <td class="col-valor"><span class="valor-apuracao">R$ {{ number_format($lucroBruto, 2, ',', '.') }}</span></td>
                    </tr>

                    @foreach([
                        'pessoal' => 'COM PESSOAL / FOLHA',
                        'operacional' => 'OPERACIONAIS',
                        'administrativa' => 'ADMINISTRATIVAS',
                        'tributaria' => 'TRIBUTÁRIAS (OUTROS IMPOSTOS E TAXAS)'
                    ] as $grupo => $descricao)
                        <tr class="row-group" onclick="toggleDet('{{ $grupo }}')" style="background-color: #fff5f5">
                            <td class="pl-7 font-weight-bold text-danger col-nome"><i class="la la-minus mr-2"></i> (-) DESPESAS {{ $descricao }}</td>
                            <td class="col-valor font-weight-bold text-danger"><span class="valor-apuracao">R$ {{ number_format(abs($dados[$grupo] ?? 0), 2, ',', '.') }}</span></td>
                        </tr>
                        @foreach(($detalhes[$grupo] ?? []) as $detalhe)
                            <tr class="row-detail {{ $grupo }}">
                                <td class="text-detail text-muted small col-nome">{{ $detalhe->categoria_nome }}</td>
                                <td class="col-valor text-danger small"><span class="valor-apuracao">R$ {{ number_format(abs($detalhe->total), 2, ',', '.') }}</span></td>
                            </tr>
                        @endforeach
                    @endforeach

                    @foreach(['financeira' => 'FINANCEIRO', 'nao_operacional' => 'NÃO OPERACIONAL'] as $grupo => $descricao)
                        <tr class="row-group bg-light" onclick="toggleDet('{{ $grupo }}')">
                            <td class="pl-7 font-weight-bold col-nome"><i class="la la-plus-circle mr-2"></i> (+/-) {{ $descricao }}</td>
                            <td class="col-valor font-weight-bold {{ ($dados[$grupo] ?? 0) < 0 ? 'text-danger' : 'text-success' }}">
                                <span class="valor-apuracao">R$ {{ number_format($dados[$grupo] ?? 0, 2, ',', '.') }}</span>
                            </td>
                        </tr>
                        @foreach(($detalhes[$grupo] ?? []) as $detalhe)
                            <tr class="row-detail {{ $grupo }}">
                                <td class="text-detail text-muted small col-nome">{{ $detalhe->categoria_nome }}</td>
                                <td class="col-valor small {{ $detalhe->total < 0 ? 'text-danger' : 'text-success' }}">
                                    <span class="valor-apuracao">R$ {{ number_format($detalhe->total, 2, ',', '.') }}</span>
                                </td>
                            </tr>
                        @endforeach
                    @endforeach

                    <tr class="border-top font-weight-bold">
                        <td class="pl-7 col-nome">(=) RESULTADO DO PERÍODO</td>
                        <td class="col-valor {{ $resultadoPeriodo < 0 ? 'text-danger' : 'text-success' }}"><span class="valor-apuracao">R$ {{ number_format($resultadoPeriodo, 2, ',', '.') }}</span></td>
                    </tr>

                    @if($filial_id === 'todos')
                        <tr>
                            <td class="pl-7 text-muted font-italic col-nome">Saldo encerrado do mês anterior</td>
                            <td class="col-valor text-muted"><span class="valor-apuracao">R$ {{ number_format($saldoAnterior, 2, ',', '.') }}</span></td>
                        </tr>
                    @endif

                    <tr class="{{ $resultadoAcumulado >= 0 ? 'bg-success' : 'bg-danger' }} text-white font-weight-boldest">
                        <td class="pl-7 h4 col-nome">RESULTADO LÍQUIDO FINAL</td>
                        <td class="col-valor h4 text-white"><span class="valor-apuracao">R$ {{ number_format($resultadoAcumulado, 2, ',', '.') }}</span></td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function toggleDet(cls) {
            const rows = document.querySelectorAll('tr.' + cls);
            if (!rows.length) return;

            const isVisible = Array.from(rows).some((row) => row.style.display === 'table-row');
            rows.forEach((row) => {
                row.style.display = isVisible ? 'none' : 'table-row';
            });
        }

        function imprimirApuracao() {
            const relatorio = document.getElementById('apuracao-relatorio');
            if (!relatorio || !relatorio.parentNode) {
                window.print();
                return;
            }

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
                title: 'Finalizar período?',
                text: 'O resultado será recalculado no servidor e o estoque atual será fotografado para o mês selecionado.',
                icon: 'warning',
                buttons: ['Não', 'Sim']
            }).then((confirmado) => {
                if (confirmado) {
                    $('#form-finalizar').submit();
                }
            });
        }
    </script>
@endsection
