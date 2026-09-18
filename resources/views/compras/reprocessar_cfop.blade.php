@extends('default.layout')
@section('content')

    <style>
        /* CONFIGURAÇÕES GERAIS DE IMPRESSÃO */
        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            .no-print, form, button, .navbar, .aside, .footer, header, .card-header {
                display: none !important;
            }

            @page {
                size: landscape;
                margin: 0.5cm;
            }

            body { background-color: #fff !important; }
            .card { border: none !important; }
            .container-fluid { padding: 0 !important; }

            table { width: 100% !important; border-collapse: collapse; }
            th, td {
                font-size: 8pt !important;
                padding: 5px !important;
                border: 1px solid #eee !important;
            }

            .text-right { text-align: right !important; }

            .badge-danger { background-color: #F64E60 !important; color: #fff !important; }
            .badge-warning { background-color: #FFA800 !important; color: #fff !important; }
            .badge-success { background-color: #1BC5BD !important; color: #fff !important; }
            .badge-primary { background-color: #3699FF !important; color: #fff !important; }
            .label-light-success { background-color: #C9F7F5 !important; color: #1BC5BD !important; }
            .label-light-info { background-color: #EEE5FF !important; color: #8950FC !important; }
        }
    </style>

    <div class="card card-custom">
        <div class="card-header no-print">
            <h3 class="card-title">{{ $title }}</h3>
        </div>

        <div class="card-body">
            <!-- MENSAGENS DE ALERTA DO REPROCESSAMENTO -->
            @if(session('mensagem_sucesso'))
                <div class="alert alert-custom alert-success p-4 mb-4 rounded shadow-sm no-print" role="alert">
                    <div class="alert-icon"><i class="la la-check-circle fs-2"></i></div>
                    <div class="alert-text font-weight-bold">{{ session('mensagem_sucesso') }}</div>
                </div>
            @endif

            @if(session('mensagem_erro'))
                <div class="alert alert-custom alert-danger p-4 mb-4 rounded shadow-sm no-print" role="alert">
                    <div class="alert-icon"><i class="la la-times-circle fs-2"></i></div>
                    <div class="alert-text font-weight-bold">{{ session('mensagem_erro') }}</div>
                </div>
            @endif

            @if(session('erros_xml') && is_array(session('erros_xml')))
                <div class="alert alert-custom alert-warning p-4 mb-4 rounded shadow-sm no-print" role="alert">
                    <div class="alert-icon"><i class="la la-exclamation-triangle fs-2"></i></div>
                    <div class="alert-text">
                        <strong>Atenção: alguns arquivos XML não foram localizados:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach(session('erros_xml') as $erro)
                                <li>{{ $erro }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <form method="GET" action="/compraconferencia" id="form-filtro-conferencia" class="no-print">
                <div class="row">
                    <div class="col-md-2">
                        <label class="font-weight-bold">Data Inicial</label>
                        <input type="date" name="data_inicial" id="filtro_data_inicial" class="form-control" value="{{ $data_inicial }}">
                    </div>
                    <div class="col-md-2">
                        <label class="font-weight-bold">Data Final</label>
                        <input type="date" name="data_final" id="filtro_data_final" class="form-control" value="{{ $data_final }}">
                    </div>
                    <div class="col-md-2">
                        <label class="font-weight-bold">Nº Nota</label>
                        <input type="text" name="numero_nota" class="form-control" value="{{ request('numero_nota') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="font-weight-bold">Fornecedor</label>
                        <select name="fornecedor_id" class="form-control select2">
                            <option value="todos">Todos</option>
                            @foreach($fornecedores as $f)
                                <option value="{{ $f->id }}" {{ request('fornecedor_id') == $f->id ? 'selected' : '' }}>
                                    {{ $f->razao_social }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="font-weight-bold">Categoria</label>
                        <select name="categoria_id" class="form-control select2">
                            <option value="todos">Todas</option>
                            @foreach($categorias as $cat)
                                <option value="{{ $cat->id }}" {{ request('categoria_id') == $cat->id ? 'selected' : '' }}>{{ $cat->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-2">
                        <label class="font-weight-bold">Estado</label>
                        <select name="estado" class="form-control select2">
                            <option value="todos">Todos</option>
                            <option value="novo" {{ request('estado') == 'novo' ? 'selected' : '' }}>NOVO</option>
                            <option value="importado" {{ request('estado') == 'importado' ? 'selected' : '' }}>IMPORTADO</option>
                            <option value="emitida" {{ request('estado') == 'emitida' ? 'selected' : '' }}>EMITIDA</option>
                            <option value="rejeitado" {{ request('estado') == 'rejeitado' ? 'selected' : '' }}>REJEITADO</option>
                        </select>
                    </div>
                    <div class="col-md-10 text-right">
                        <br>
                        <button type="submit" class="btn btn-primary font-weight-bold">
                            <i class="la la-filter"></i> Filtrar
                        </button>

                        <button type="button" onclick="window.print()" class="btn btn-secondary font-weight-bold ms-1">
                            <i class="la la-print"></i> Imprimir
                        </button>

                        <!-- BOTÃO PARA EXECUTAR O REPROCESSAMENTO NO PERÍODO -->
                        <button type="button" onclick="dispararReprocessamento()" class="btn btn-warning font-weight-bold shadow-sm ms-1">
                            <i class="la la-sync"></i> Reprocessar CFOP / Tributos
                        </button>
                    </div>
                </div>
            </form>

            <!-- FORMULÁRIO OCULTO PARA ENVIO POST SEGURO -->
            <form id="form-executar-reprocessamento" method="POST" action="/compras/reprocessar-cfop/executar" style="display: none;">
                @csrf
                <input type="hidden" name="data_inicial" id="post_data_inicial">
                <input type="hidden" name="data_final" id="post_data_final">
            </form>

            <div class="d-none d-print-block text-center mb-5">
                <h2 class="font-weight-bold">CONFERÊNCIA DE COMPRAS</h2>
                <p>Período: {{ date('d/m/Y', strtotime($data_inicial)) }} a {{ date('d/m/Y', strtotime($data_final)) }}</p>
            </div>

            <div class="table-responsive mt-4">
                <table class="table table-vertical-center">
                    <thead>
                    <tr>
                        <th style="width: 8%">Nº Nota</th>
                        <th style="width: 10%">Estado</th>
                        <th style="width: 10%">Tipo</th>
                        <th>Fornecedor/Terceiro</th>
                        <th class="text-right">Qtd. Itens</th>
                        <th class="text-right">Valor Nota</th>
                        <th class="text-right">V. Pago</th>
                        <th class="text-right">Saldo</th>
                        <th style="width: 15%">Financeiro</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($compras as $c)
                        @php
                            $valorNota = (float)$c->valor;
                            $valorPago = $c->contasPagar->sum('valor_pago');
                            $falta = $valorNota - $valorPago;
                            $estado = strtoupper($c->estado);
                        @endphp
                        <tr>
                            <td><strong>{{ $c->numero_exibicao ?? '---' }}</strong></td>
                            <td>
                                @if($estado == 'REJEITADO')
                                    <span class="badge badge-danger">REJEITADO ⚠️</span>
                                @elseif($estado == 'NOVO')
                                    <span class="badge badge-warning">NOVO ⏳</span>
                                @elseif($estado == 'IMPORTADO')
                                    <span class="badge badge-primary">IMPORTADO</span>
                                @else
                                    <span class="badge badge-success">{{ $estado }}</span>
                                @endif
                            </td>
                            <td>
                            <span class="label label-inline {{ $c->tipo_relatorio == 'PRÓPRIA' ? 'label-light-success' : 'label-light-info' }}">
                                {{ $c->tipo_relatorio }}
                            </span>
                            </td>
                            <td>{{ $c->fornecedor->razao_social ?? 'N/A' }}</td>
                            <td class="text-right">{{ number_format($c->itens->sum('quantidade'), 2, ',', '.') }}</td>
                            <td class="text-right">R$ {{ number_format($valorNota, 2, ',', '.') }}</td>
                            <td class="text-right" style="color: #1BC5BD !important;">R$ {{ number_format($valorPago, 2, ',', '.') }}</td>
                            <td class="text-right" style="color: #F64E60 !important;">R$ {{ number_format($falta, 2, ',', '.') }}</td>
                            <td>
                                @forelse($c->contasPagar as $cp)
                                    <small class="d-block font-weight-bold">
                                        [{{ $cp->categoria->nome ?? 'S/C' }}] {{ $cp->status ? 'Pago' : 'Aberto' }}
                                    </small>
                                @empty
                                    <small class="text-muted">Sem Lançamento</small>
                                @endforelse
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer bg-light">
            <div class="row align-items-center">
                <div class="col-3 text-center border-right">
                    <small class="text-muted d-block">Qtd. Total de Itens</small>
                    <h4 class="font-weight-bolder">{{ number_format($total_produtos, 2, ',', '.') }}</h4>
                </div>
                <div class="col-2 text-center border-right">
                    <small class="text-muted d-block">Notas Próprias</small>
                    <h4 class="font-weight-bolder text-primary">{{ $total_propria }}</h4>
                </div>
                <div class="col-2 text-center border-right">
                    <small class="text-muted d-block">Notas Terceiros</small>
                    <h4 class="font-weight-bolder text-warning">{{ $total_terceiro }}</h4>
                </div>
                <div class="col-5 text-right">
                    <small class="text-muted d-block">VALOR TOTAL DO PERÍODO</small>
                    <h2 class="font-weight-boldest text-success">R$ {{ number_format($soma_valores, 2, ',', '.') }}</h2>
                </div>
            </div>
        </div>
    </div>

    @section('javascript')
        <script type="text/javascript">
            $(function () {
                $('.select2').select2();
            });

            function dispararReprocessamento() {
                let dtIni = $('#filtro_data_inicial').val();
                let dtFim = $('#filtro_data_final').val();

                if (!dtIni || !dtFim) {
                    swal("Atenção!", "Informe a Data Inicial e a Data Final para executar o reprocessamento.", "warning");
                    return;
                }

                // Formata as datas para exibição na mensagem
                let partesIni = dtIni.split('-');
                let dataIniFormatada = partesIni.length === 3 ? partesIni[2] + '/' + partesIni[1] + '/' + partesIni[0] : dtIni;

                let partesFim = dtFim.split('-');
                let dataFimFormatada = partesFim.length === 3 ? partesFim[2] + '/' + partesFim[1] + '/' + partesFim[0] : dtFim;

                swal({
                    title: "Reprocessar CFOP e Tributos?",
                    text: "Deseja atualizar os CFOPs, CSTs e vincular as categorias de todas as compras emitidas entre " + dataIniFormatada + " e " + dataFimFormatada + "?",
                    icon: "warning",
                    buttons: ["Cancelar", "Sim, Executar"],
                    dangerMode: true,
                }).then((confirmado) => {
                    if (confirmado) {
                        $('#post_data_inicial').val(dtIni);
                        $('#post_data_final').val(dtFim);
                        $('#form-executar-reprocessamento').submit();
                    }
                });
            }
        </script>
    @endsection

@endsection
