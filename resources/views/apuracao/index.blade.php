@extends('default.layout')
@section('content')
    <style>
        /* Fixa a largura das colunas para garantir alinhamento vertical perfeito */
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

        /* Garante que o texto pequeno da categoria não quebre o alinhamento */
        .text-detail { padding-left: 3.5rem !important; }

        @media print {
            @page { margin: 8mm; }

            body.printing-apuracao {
                background: #fff !important;
            }

            body.printing-apuracao > *:not(#apuracao-relatorio) {
                display: none !important;
            }

            body.printing-apuracao #apuracao-relatorio {
                display: block !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
                box-shadow: none !important;
                border: 0 !important;
            }

            body.printing-apuracao .no-print {
                display: none !important;
            }
        }
    </style>

    <div class="container mt-5">
        <div class="card card-custom gutter-b" id="apuracao-relatorio">
            <div class="card-header py-3">
                <div class="header-custom">
                    <div style="flex: 1;" class="no-print"><button class="btn btn-sm btn-light-primary font-weight-bold" onclick="imprimirApuracao()"><i class="la la-print"></i> Imprimir</button></div>
                    <div style="flex: 2; text-align: center;"><h3 class="card-title font-weight-bolder text-dark mb-0">Apuração de Resultado - {{ str_pad($mes, 2, '0', STR_PAD_LEFT) }}/{{ $ano }}</h3></div>
                    <div style="flex: 1; text-align: right;" class="no-print">
                        <form action="/apuracao/finalizar" method="POST" id="form-finalizar" style="display: inline-block;">
                            @csrf
                            <input type="hidden" name="mes" value="{{ $mes }}"><input type="hidden" name="ano" value="{{ $ano }}">
                            <input type="hidden" name="regime" value="{{ $regime }}"><input type="hidden" name="lucro_prejuizo_liquido" id="val_final">
                            <button type="button" class="btn btn-sm btn-success font-weight-bold" onclick="confirmar()">Finalizar Período</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <form method="get" action="/apuracao" id="form-filtro" class="mb-8 no-print">
                    <div class="row align-items-end">
                        <div class="col-lg-2">
                            <label class="font-weight-bold">Período</label>
                            <div class="d-flex">
                                <select name="mes" class="form-control mr-1" onchange="$('#form-filtro').submit()">@for($m=1;$m<=12;$m++)<option value="{{$m}}" {{$mes==$m?'selected':''}}>{{str_pad($m,2,'0',STR_PAD_LEFT)}}</option>@endfor</select>
                                <select name="ano" class="form-control" onchange="$('#form-filtro').submit()">@for($a=date('Y')-2;$a<=date('Y')+2;$a++)<option value="{{$a}}" {{$ano==$a?'selected':''}}>{{$a}}</option>@endfor</select>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <label class="font-weight-bold">Unidade / Filial</label>
                            <select class="form-control select2" name="filial_id" onchange="$('#form-filtro').submit()">
                                <option value="todos" {{$filial_id=='todos'?'selected':''}}>-- TODAS AS UNIDADES --</option>
                                <option value="matriz" {{$filial_id=='matriz'?'selected':''}}>{{ $nomeMatriz }} (Matriz)</option>
                                @foreach($filiais as $f)<option value="{{$f->id}}" {{$filial_id==$f->id?'selected':''}}>FILIAL - {{ $f->razao_social ?? $f->nome }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-lg-3">
                            <label class="font-weight-bold">Regime</label>
                            <div class="radio-inline mt-2">
                                <label class="radio radio-success"><input type="radio" name="regime" value="competencia" {{$regime=='competencia'?'checked':''}} onchange="$('#form-filtro').submit()"><span></span> Comp.</label>
                                <label class="radio radio-success"><input type="radio" name="regime" value="caixa" {{$regime=='caixa'?'checked':''}} onchange="$('#form-filtro').submit()"><span></span> Caixa</label>
                            </div>
                        </div>
                        
                        <div class="col-lg-4 text-right mt-4 mt-lg-0">
                            <button type="button" class="btn btn-sm btn-outline-info font-weight-bold" onclick="$('#bloco-simulacao').slideToggle()">
                                <i class="la la-calculator"></i> Simular Estoque
                            </button>
                            <button type="submit" class="btn btn-sm btn-primary font-weight-bold">Aplicar</button>
                        </div>
                    </div>

                    <!-- BLOCO DE SIMULAÇÃO (Inicia oculto a menos que esteja em uso) -->
                    <div class="row mt-4 p-4 rounded" id="bloco-simulacao" style="background-color: #f3f6f9; border: 1px dashed #b5b5c3; display: {{ request()->hasAny(['simular_est_inicial', 'simular_compras', 'simular_est_final']) ? 'flex' : 'none' }};">
                        <div class="col-12 mb-3">
                            <span class="text-info font-weight-bolder text-uppercase small">
                                <i class="la la-info-circle text-info"></i> Simulação de CMV (Sem necessidade de finalizar o período)
                            </span>
                        </div>
                        <div class="col-lg-4">
                            <label class="small font-weight-bold">Estoque Inicial (R$)</label>
                            <input type="number" step="0.01" name="simular_est_inicial" class="form-control form-control-sm" value="{{ request('simular_est_inicial') }}" placeholder="Ex: 15000.00">
                        </div>
                        <div class="col-lg-4">
                            <label class="small font-weight-bold">Compras no Mês (R$)</label>
                            <input type="number" step="0.01" name="simular_compras" class="form-control form-control-sm" value="{{ request('simular_compras') }}" placeholder="Ex: 5000.00">
                        </div>
                        <div class="col-lg-4">
                            <label class="small font-weight-bold">Estoque Final (R$)</label>
                            <input type="number" step="0.01" name="simular_est_final" class="form-control form-control-sm" value="{{ request('simular_est_final') }}" placeholder="Ex: 12000.00">
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
                    @php
                        $recBruta   = $dados['receita_bruta'] ?? 0;
                        $deducoes   = abs($dados['deducao_venda'] ?? 0);
                        $devolucaos = abs($dados['devolucao'] ?? 0);

                        // Calcula a Receita Líquida intermediária
                        $recLiquida = $recBruta - $deducoes - $devolucaos;

                        // Lucro bruto agora parte do faturamento líquido real
                        $lucBruto   = $recLiquida - $cmvReal;

                        $despesas   = abs($dados['operacional'] ?? 0) + 
                                      abs($dados['administrativa'] ?? 0) + 
                                      abs($dados['tributaria'] ?? 0) + 
                                      abs($dados['pessoal'] ?? 0); // 👈 Incluído a Folha nas despesas

                        $outros     = ($dados['financeira'] ?? 0) + ($dados['nao_operacional'] ?? 0);
                        $resFinal   = $lucBruto - $despesas + $outros + $saldoAnterior;
                    @endphp

                    <!-- 1. RECEITA BRUTA -->
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

                    <!-- 2. DEDUÇÕES DA RECEITA (ICMS, PIS, COFINS, DEVOLUÇÕES) -->
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

                    <!-- DEVOLUÇÃO DE VENDA -->
                    @if($devolucaos > 0)
                    <tr class="row-group" style="background-color: #fff5f5">
                        <td class="pl-7 font-weight-bold text-danger col-nome"><i class="la la-minus mr-2"></i> (-) DEVOLUÇÕES DE VENDAS</td>
                        <td class="col-valor font-weight-bold text-danger"><span class="valor-apuracao">R$ {{number_format($devolucaos,2,',','.')}}</span></td>
                    </tr>
                    @endif

                    <!-- (=) RECEITA LÍQUIDA -->
                    <tr class="bg-secondary text-dark font-weight-boldest">
                        <td class="pl-7 col-nome">(=) RECEITA LÍQUIDA</td>
                        <td class="col-valor"><span class="valor-apuracao">R$ {{number_format($recLiquida,2,',','.')}}</span></td>
                    </tr>

                    <!-- 3. CUSTO DE MERCADORIA (CMV) -->
                    <tr class="row-group" onclick="toggleDet('cmv_det')">
                        <td class="pl-7 text-danger font-weight-bold col-nome"><i class="la la-minus-circle mr-2"></i> (-) CUSTO DE MERCADORIA (CMV)</td>
                        <td class="col-valor text-danger font-weight-bold"><span class="valor-apuracao">R$ {{number_format($cmvReal,2,',','.')}}</span></td>
                    </tr>
                    <tr class="row-detail cmv_det"><td class="text-detail text-muted small col-nome">(+) Estoque Inicial</td><td class="col-valor text-muted small"><span class="valor-apuracao">R$ {{number_format($estoqueInicial,2,',','.')}}</span></td></tr>
                    <tr class="row-detail cmv_det"><td class="text-detail text-muted small col-nome">(+) Compras Líquidas</td><td class="col-valor text-muted small"><span class="valor-apuracao">R$ {{number_format($comprasDoMes,2,',','.')}}</span></td></tr>
                    <tr class="row-detail cmv_det"><td class="text-detail text-muted small col-nome">(-) Estoque Final</td><td class="col-valor text-muted small"><span class="valor-apuracao">R$ {{number_format($estoqueFinal,2,',','.')}}</span></td></tr>

                    <!-- (=) LUCRO BRUTO -->
                    <tr class="bg-primary text-white font-weight-boldest"><td class="pl-7 col-nome">(=) LUCRO BRUTO</td><td class="col-valor"><span class="valor-apuracao">R$ {{number_format($lucBruto,2,',','.')}}</span></td></tr>

                    <!-- 4. GRUPOS DE DESPESAS (OPERACIONAL, ADMINISTRATIVA, TRIBUTÁRIA, PESSOAL) -->
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

                    <!-- 5. FLUXO FINANCEIRO E NÃO OPERACIONAL -->
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

                    <!-- RESULTADO LÍQUIDO FINAL -->
                    <tr class="{{ $resFinal >= 0 ? 'bg-success' : 'bg-danger' }} text-white font-weight-boldest">
                        <td class="pl-7 h4 col-nome">RESULTADO LÍQUIDO FINAL</td>
                        <td class="col-valor h4 text-white"><span class="valor-apuracao">R$ {{number_format($resFinal,2,',','.')}}</span></td>
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

            const isVisible = Array.from(rows).some(row => row.style.display === 'table-row');
            rows.forEach((row) => {
                row.style.display = isVisible ? 'none' : 'table-row';
            });
        }
        document.getElementById('val_final').value = "{{ $resFinal }}";

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
            swal({title: "Finalizar?", text: "Isso travará o estoque do mês!", icon: "warning", buttons: ["Não", "Sim"]})
                .then((v) => { if(v) $('#form-finalizar').submit(); });
        }
    </script>
@endsection
