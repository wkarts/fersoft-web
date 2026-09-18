@extends('default.layout')

@section('content')
    <div class="card card-custom gutter-b">
        <div class="card-header border-0 pt-5">
            <h3 class="card-title font-weight-bolder text-dark">
                <i class="la la-clock text-primary mr-2"></i> {{ $title }}
            </h3>
        </div>

        <div class="card-body">
            <!-- FORMULÁRIO DE FILTROS E EXPORTAÇÃO -->
            <form method="GET" action="{{ url('/ponto/espelho') }}" id="formFiltroPonto" class="mb-5">
                <div class="row align-items-end">
                    <!-- Data Início -->
                    <div class="col-xl-2 col-lg-3 col-md-4 mb-3">
                        <label class="font-weight-bold text-dark-75 mb-1 font-size-sm">Data Início</label>
                        <input type="date" name="data_inicio" id="data_inicio" class="form-control form-control-solid font-weight-bold"
                               value="{{ $dataInicio }}" required>
                    </div>

                    <!-- Data Fim -->
                    <div class="col-xl-2 col-lg-3 col-md-4 mb-3">
                        <label class="font-weight-bold text-dark-75 mb-1 font-size-sm">Data Fim</label>
                        <input type="date" name="data_fim" id="data_fim" class="form-control form-control-solid font-weight-bold"
                               value="{{ $dataFim }}" required>
                    </div>

                    <!-- Colaborador -->
                    <div class="col-xl-3 col-lg-6 col-md-4 mb-3">
                        <label class="font-weight-bold text-dark-75 mb-1 font-size-sm">Colaborador</label>
                        <select name="funcionario_id" id="funcionario_id" class="form-control form-control-solid font-weight-bold">
                            <option value="TODOS">-- Todos os Colaboradores --</option>
                            @foreach($funcionarios as $f)
                                <option value="{{ $f->id }}" {{ ($funcionarioId == $f->id) ? 'selected' : '' }}>
                                    {{ $f->nome }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Botões de Ação Alinhados -->
                    <div class="col-xl-5 col-lg-12 mb-3">
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <!-- Filtrar -->
                            <button type="submit" class="btn btn-primary font-weight-bolder px-4 mr-2" style="height: 38px; display: inline-flex; align-items: center;">
                                <i class="la la-filter mr-1"></i> Filtrar
                            </button>

                            <!-- Ajuste Manual -->
                            <button type="button" class="btn btn-warning font-weight-bolder px-4 mr-2" data-toggle="modal" data-target="#modalAjusteManual" style="height: 38px; display: inline-flex; align-items: center;">
                                <i class="la la-edit mr-1"></i> + Ajuste
                            </button>

                            <!-- Dropdown ou Botões de Exportação Agrupados -->
                            <button type="button" class="btn btn-success font-weight-bolder px-3 mr-2" onclick="exportarAfd()" title="Exportar Arquivo AFD para Folha (Portaria 671)" style="height: 38px; display: inline-flex; align-items: center;">
                                <i class="la la-download mr-1"></i> AFD
                            </button>

                            <button type="button" class="btn btn-info font-weight-bolder px-3" onclick="imprimirPdf()" title="Imprimir Espelho de Ponto em PDF" style="height: 38px; display: inline-flex; align-items: center;">
                                <i class="la la-print mr-1"></i> PDF
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <!-- CARTÕES DE INDICADORES DO PERÍODO -->
            @if(isset($totais))
                <div class="row mb-5">
                    <div class="col-md-3 mb-3 mb-md-0">
                        <div class="bg-light-primary px-6 py-4 rounded-xl border border-primary">
                            <div class="text-primary font-weight-bolder font-size-h3">{{ $totais['trabalhadas'] }}</div>
                            <div class="text-muted font-weight-bold font-size-sm mt-1">Horas Trabalhadas</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3 mb-md-0">
                        <div class="bg-light-success px-6 py-4 rounded-xl border border-success">
                            <div class="text-success font-weight-bolder font-size-h3">+{{ $totais['extras'] }}</div>
                            <div class="text-muted font-weight-bold font-size-sm mt-1">Horas Extras</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3 mb-md-0">
                        <div class="bg-light-danger px-6 py-4 rounded-xl border border-danger">
                            <div class="text-danger font-weight-bolder font-size-h3">-{{ $totais['atrasos'] }}</div>
                            <div class="text-muted font-weight-bold font-size-sm mt-1">Atrasos / Faltas</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="{{ $totais['saldo_positivo'] ? 'bg-light-info border-info text-info' : 'bg-light-warning border-warning text-warning' }} px-6 py-4 rounded-xl border">
                            <div class="font-weight-bolder font-size-h3 {{ $totais['saldo_positivo'] ? 'text-info' : 'text-warning' }}">
                                {{ $totais['saldo'] }}
                            </div>
                            <div class="text-muted font-weight-bold font-size-sm mt-1">Saldo Banco de Horas</div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="separator separator-dashed my-5"></div>

            <!-- TABELA DE MARCAÇÕES -->
            <div class="table-responsive">
                <table class="table table-head-custom table-vertical-center table-hover">
                    <thead>
                    <tr class="text-uppercase text-secondary">
                        <th>Data/Hora</th>
                        <th>Funcionário</th>
                        <th>Tipo da Batida</th>
                        <th>Origem</th>
                        <th>Localização (GPS)</th>
                        <th>Viagem Vinc.</th>
                        <th>Observações</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($marcacoes as $m)
                        <tr>
                            <td class="font-weight-bold text-dark">
                                {{\Carbon\Carbon::parse($m->data_hora_marcacao)->format('d/m/Y H:i:s')}}
                            </td>
                            <td>
                                <span class="font-weight-bolder text-dark">{{ $m->funcionario->nome ?? 'N/I' }}</span>
                                @if(!empty($m->funcionario->funcao->nome))
                                    <br><small class="text-muted">{{ $m->funcionario->funcao->nome }}</small>
                                @endif
                            </td>
                            <td>
                                @if($m->tipo_marcacao == 'entrada')
                                    <span class="badge badge-success font-weight-bold">🟢 Entrada</span>
                                @elseif($m->tipo_marcacao == 'inicio_almoco')
                                    <span class="badge badge-warning font-weight-bold">🟡 Saída Almoço</span>
                                @elseif($m->tipo_marcacao == 'fim_almoco')
                                    <span class="badge badge-info font-weight-bold">🟢 Retorno Almoço</span>
                                @elseif($m->tipo_marcacao == 'saida')
                                    <span class="badge badge-danger font-weight-bold">🔴 Saída</span>
                                @elseif($m->tipo_marcacao == 'inicio_hora_extra')
                                    <span class="badge badge-dark font-weight-bold">⚡ Início Extra</span>
                                @elseif($m->tipo_marcacao == 'fim_hora_extra')
                                    <span class="badge badge-secondary font-weight-bold">🛑 Fim Extra</span>
                                @else
                                    <span class="badge badge-light font-weight-bold">{{ strtoupper($m->tipo_marcacao) }}</span>
                                @endif
                            </td>
                            <td>
                            <span class="label label-inline label-light-primary font-weight-bold">
                                {{ strtoupper($m->origem) }}
                            </span>
                            </td>
                            <td>
                                @if($m->latitude && $m->longitude)
                                    <a href="https://maps.google.com/?q={{$m->latitude}},{{$m->longitude}}" target="_blank" class="btn btn-sm btn-light-success font-weight-bold">
                                        <i class="la la-map-marker"></i> Ver no Mapa
                                    </a>
                                @else
                                    <span class="text-muted"><i class="la la-map-marker-slash"></i> Sem GPS</span>
                                @endif
                            </td>
                            <td>
                                @if($m->movimentacao_veiculo_id)
                                    <span class="badge badge-light-info font-weight-bold">#{{$m->movimentacao_veiculo_id}}</span>
                                @else
                                    <span class="text-muted">---</span>
                                @endif
                            </td>
                            <td>
                                <small class="text-dark-75">{{ $m->observacoes ?? '---' }}</small>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="la la-info-circle text-muted mb-2 font-size-h2 d-block"></i>
                                Nenhuma marcação de ponto encontrada no período selecionado.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL DE AJUSTE MANUAL DE PONTO -->
    <div class="modal fade" id="modalAjusteManual" tabindex="-1" role="dialog" aria-labelledby="modalAjusteLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form method="POST" action="{{ url('/ponto/ajuste-manual') }}">
                    @csrf
                    <div class="modal-header bg-light">
                        <h5 class="modal-title font-weight-bold text-dark" id="modalAjusteLabel">
                            <i class="la la-clock text-warning mr-1"></i> Inclusão / Ajuste Manual de Ponto
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="font-weight-bold">Colaborador <span class="text-danger">*</span></label>
                            <select name="funcionario_id" class="form-control" required>
                                <option value="">-- Selecione o Colaborador --</option>
                                @foreach($funcionarios as $f)
                                    <option value="{{ $f->id }}" {{ (isset($funcionarioId) && $funcionarioId == $f->id) ? 'selected' : '' }}>
                                        {{ $f->nome }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold">Data da Batida <span class="text-danger">*</span></label>
                                <input type="date" name="data" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold">Horário (HH:mm) <span class="text-danger">*</span></label>
                                <input type="time" name="hora" class="form-control" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Tipo da Batida <span class="text-danger">*</span></label>
                            <select name="tipo_marcacao" class="form-control" required>
                                <option value="entrada">🟢 1 - Entrada</option>
                                <option value="inicio_almoco">🟡 2 - Saída Almoço</option>
                                <option value="fim_almoco">🟢 3 - Retorno Almoço</option>
                                <option value="saida">🔴 4 - Saída</option>
                                <option value="inicio_hora_extra">⚡ 5 - Início Hora Extra</option>
                                <option value="fim_hora_extra">🛑 6 - Fim Hora Extra</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Justificativa / Motivo <span class="text-danger">*</span></label>
                            <textarea name="justificativa" class="form-control" rows="3" placeholder="Ex: Esquecimento de registro, atestado médico nº ..., problema de conexão celular." required></textarea>
                            <small class="text-muted">Exigência da Portaria 671/2021 MTE para auditoria fiscal.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning font-weight-bold">
                            <i class="la la-check"></i> Gravar Ajuste
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function exportarAfd() {
            const dataInicio = document.getElementById('data_inicio').value;
            const dataFim = document.getElementById('data_fim').value;
            const funcionarioId = document.getElementById('funcionario_id').value;

            if (!dataInicio || !dataFim) {
                alert('Por favor, informe a Data Início e a Data Fim para exportação.');
                return;
            }

            const urlAfd = "{{ url('/ponto/relatorios/exportar-afd') }}"
                + "?data_inicio=" + encodeURIComponent(dataInicio)
                + "&data_fim=" + encodeURIComponent(dataFim)
                + "&funcionario_id=" + encodeURIComponent(funcionarioId);

            window.location.href = urlAfd;
        }
        function imprimirPdf() {
            const dataInicio = document.getElementById('data_inicio').value;
            const dataFim = document.getElementById('data_fim').value;
            const funcionarioId = document.getElementById('funcionario_id').value;

            if (!funcionarioId || funcionarioId === 'TODOS') {
                alert('Por favor, selecione um colaborador específico para gerar o PDF do Espelho de Ponto.');
                return;
            }

            const urlPdf = "{{ url('/ponto/espelho/pdf') }}/" + funcionarioId
                + "?data_inicio=" + encodeURIComponent(dataInicio)
                + "&data_fim=" + encodeURIComponent(dataFim);

            window.open(urlPdf, '_blank');
        }
    </script>
@endsection
