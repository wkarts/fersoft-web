@extends('default.layout')
@section('content')
    <div class="d-flex flex-column flex-column-fluid" id="kt_content">
        <div class="card card-custom gutter-b example example-compact">
            <div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
                <div class="col-lg-12">
                    <br>

                    <!-- NAVEGAÇÃO SUPERIOR -->
                    <div class="row mb-3">
                        <div class="col-12 d-flex justify-content-between align-items-center">
                            <h3 class="font-weight-bold text-dark">
                                Locação #{{$locacao->id}}
                                <small class="text-muted">({{$locacao->tipo == 'cacamba' ? 'Caçamba / Container' : 'Equipamento / Máquina'}})</small>
                            </h3>
                            <a href="/locacao" class="btn btn-secondary font-weight-bold">
                                <i class="la la-arrow-left"></i> Voltar para Lista
                            </a>
                        </div>
                    </div>

                    <!-- FORMULÁRIO DE ADIÇÃO DE ITEM / PATRIMÔNIO -->
                    <form method="post" action="/locacao/salvarItem">
                        @csrf
                        <input type="hidden" id="idLocacao" name="locacao_id" value="{{$locacao->id}}">

                        <div class="card card-custom gutter-b example example-compact">
                            <div class="card-header">
                                <h3 class="card-title">Adicionar Item / Patrimônio</h3>
                            </div>

                            <div class="card-body">
                                <div class="row">
                                    <div class="form-group validated col-lg-4 col-md-6 col-sm-12">
                                        <label class="col-form-label">Produto / Equipamento</label>
                                        <div class="input-group">
                                            <select class="form-control select2 @if($errors->has('produto_id')) is-invalid @endif" id="kt_select2_1" name="produto_id">
                                                <option value="">Selecione o produto</option>
                                                @foreach($produtos as $p)
                                                    <option value="{{$p->id}}">{{$p->nome}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @if($errors->has('produto_id'))
                                            <div class="invalid-feedback">{{ $errors->first('produto_id') }}</div>
                                        @endif
                                    </div>

                                    <div class="form-group validated col-lg-3 col-md-6 col-sm-12">
                                        <label class="col-form-label">Cód. Patrimônio / Identificador Físico</label>
                                        <input type="text" class="form-control" name="codigo_patrimonio" placeholder="Ex: CAÇAMBA #04, Série ABC-123">
                                    </div>

                                    @if($locacao->tipo == 'equipamento')
                                        <div class="form-group validated col-lg-2 col-md-4 col-sm-6">
                                            <label class="col-form-label">Horímetro Inicial</label>
                                            <input type="number" class="form-control" name="horimetro_inicial" placeholder="Ex: 1250">
                                        </div>
                                    @endif

                                    <div class="form-group validated col-sm-6 col-lg-2">
                                        <label class="col-form-label">Valor (R$)</label>
                                        <input type="text" class="form-control @if($errors->has('valor')) is-invalid @endif money" id="valor" name="valor" value="{{{ old('valor') }}}">
                                        @if($errors->has('valor'))
                                            <div class="invalid-feedback">{{ $errors->first('valor') }}</div>
                                        @endif
                                    </div>

                                    <div class="form-group validated col-lg-12 col-md-12 col-sm-12">
                                        <label class="col-form-label">Observação do Item</label>
                                        <input type="text" id="observacao" class="form-control @if($errors->has('observacao')) is-invalid @endif" name="observacao" value="{{{ old('observacao') }}}">
                                    </div>
                                </div>

                                <div class="row mt-2">
                                    <div class="col-lg-3 col-sm-6 col-md-4">
                                        <button style="width: 100%" type="submit" class="btn btn-success">
                                            <i class="la la-plus"></i> Adicionar Item
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- LISTA DE ITENS VINCULADOS -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card card-custom gutter-b example example-compact">
                            <div class="card-header">
                                <h3 class="card-title">ITENS E PATRIMÔNOS ALOCADOS</h3>
                            </div>

                            <div class="card-body">
                                <div class="col-xl-12">
                                    <div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">
                                        <table class="datatable-table" style="max-width: 100%; overflow-x: auto">
                                            <thead class="datatable-head">
                                            <tr class="datatable-row">
                                                <th class="datatable-cell"><span style="width: 250px;">PRODUTO</span></th>
                                                <th class="datatable-cell"><span style="width: 150px;">CÓD. PATRIMÔNIO</span></th>
                                                @if($locacao->tipo == 'equipamento')
                                                    <th class="datatable-cell"><span style="width: 120px;">HORÍMETRO</span></th>
                                                @endif
                                                <th class="datatable-cell"><span style="width: 120px;">VALOR</span></th>
                                                <th class="datatable-cell"><span style="width: 200px;">OBSERVAÇÃO</span></th>
                                                <th class="datatable-cell"><span style="width: 80px;">AÇÕES</span></th>
                                            </tr>
                                            </thead>

                                            <tbody id="body" class="datatable-body">
                                            @foreach($locacao->itens as $i)
                                                <tr class="datatable-row">
                                                    <td class="datatable-cell"><span style="width: 250px;">{{$i->produto->nome}}</span></td>
                                                    <td class="datatable-cell"><span style="width: 150px;" class="font-weight-bold text-primary">{{$i->codigo_patrimonio ?? '--'}}</span></td>
                                                    @if($locacao->tipo == 'equipamento')
                                                        <td class="datatable-cell"><span style="width: 120px;">{{$i->horimetro_inicial ?? '--'}} h</span></td>
                                                    @endif
                                                    <td class="datatable-cell"><span style="width: 120px;">R$ {{ number_format($i->valor, 2, ',', '.')}}</span></td>
                                                    <td class="datatable-cell"><span style="width: 200px;">{{$i->observacao}}</span></td>
                                                    <td class="datatable-cell">
													<span style="width: 80px;">
														<a class="btn btn-danger btn-sm" onclick='swal("Atenção!", "Deseja remover este item?", "warning").then((sim) => {if(sim){ location.href="/locacao/deleteItem/{{ $i->id }}" } })' href="#!">
															<i class="la la-trash"></i>
														</a>
													</span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    <hr class="my-6">

                                    <!-- INFORMAÇÕES LOGÍSTICAS E RESUMO -->
                                    <div class="row bg-light p-4 rounded">
                                        <div class="col-lg-6 col-md-12">
                                            <h4>
                                                {{ $locacao->finalidade == 'coleta_fornecedor' ? 'Fornecedor:' : 'Cliente:' }}
                                                <strong class="text-info">
                                                    {{ $locacao->cliente->razao_social ?? $locacao->fornecedor->razao_social ?? '---' }}
                                                </strong>
                                            </h4>

                                            @if($locacao->material_previsto)
                                                <h5>Material Previsto: <strong class="text-warning">{{ $locacao->material_previsto }}</strong></h5>
                                            @endif

                                            <h4>Data de Início/Entrega: <strong class="text-info">{{\Carbon\Carbon::parse($locacao->inicio)->format('d/m/Y')}}</strong></h4>

                                            <h4>Data de Término/Retirada: <strong class="text-info">
                                                    @if($locacao->fim != '1969-12-31' && $locacao->fim != null)
                                                        {{ \Carbon\Carbon::parse($locacao->fim)->format('d/m/Y')}}
                                                    @else
                                                        <span class="text-muted">A definir (Sob demanda)</span>
                                                    @endif
                                                </strong></h4>

                                            @if($locacao->rua_entrega)
                                                <h5>Local da Obra: <strong class="text-dark">{{$locacao->rua_entrega}}, {{$locacao->numero_entrega}} - {{$locacao->bairro_entrega}}</strong></h5>
                                            @endif
                                        </div>

                                        <div class="col-lg-6 col-md-12 text-lg-right">
                                            <h2 class="mb-4">Total: R$ <strong class="text-success">{{number_format($locacao->total, 2, ',', '.')}}</strong></h2>

                                            <h5>Status Atual:
                                                @if($locacao->status == 0)
                                                    <span class="badge badge-primary">AGENDADO / NOVO</span>
                                                @elseif($locacao->status == 1)
                                                    <span class="badge badge-info">EM USO / NO CLIENTE</span>
                                                @elseif($locacao->status == 2)
                                                    <span class="badge badge-warning">AGUARDANDO RETIRADA</span>
                                                @elseif($locacao->status == 3)
                                                    <span class="badge badge-success">FINALIZADO / RETIRADO</span>
                                                @endif
                                            </h5>

                                            <div class="mt-4">
                                                <!-- AÇÕES LOGÍSTICAS COM MODAL DE TRANSPORTE -->
                                                @if($locacao->status == 0)
                                                    <button type="button" onclick="abrirModalAgendamento('/locacao/marcarComoEntregue/{{$locacao->id}}', 'Agendar Entrega no Cliente')" class="btn btn-primary font-weight-bold">
                                                        <i class="la la-truck"></i> Confirmar Entrega no Cliente
                                                    </button>
                                                @elseif($locacao->status == 1)
                                                    <button type="button" onclick="abrirModalAgendamento('/locacao/solicitarRetirada/{{$locacao->id}}', 'Agendar Retirada de Caçamba/Equipamento')" class="btn btn-warning font-weight-bold">
                                                        <i class="la la-exclamation-circle"></i> Cliente Pediu Retirada
                                                    </button>
                                                @elseif($locacao->status == 2)
                                                    <button type="button" onclick="confirmarFinalizacao('/locacao/finalizarLocacao/{{$locacao->id}}')" class="btn btn-success font-weight-bold">
                                                        <i class="la la-check-circle"></i> Confirmar Retirada e Finalizar
                                                    </button>
                                                @endif

                                                <a target="_blank" href="/locacao/comprovante/{{$locacao->id}}" class="btn btn-secondary">
                                                    <i class="la la-print"></i> Comprovante / Minuta
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-4 d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5>Observações Gerais:</h5>
                                            <p class="text-dark">{{$locacao->observacao ?? 'Nenhuma observação informada.'}}
                                                <a data-toggle="modal" data-target="#modal1"><i class="la la-edit text-warning"></i></a>
                                            </p>
                                        </div>

                                        <a href="/locacao" class="btn btn-lg btn-success font-weight-bold px-8">
                                            <i class="la la-check"></i> Concluir / Sair
                                        </a>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL EDICAO OBSERVAÇÃO -->
    <div class="modal fade" id="modal1" data-backdrop="static" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Observação da Locação</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">x</button>
                </div>
                <form method="post" action="/locacao/saveObs">
                    @csrf
                    <input type="hidden" value="{{$locacao->id}}" name="id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="form-group validated col-sm-12 col-lg-12">
                                <label class="col-form-label">Observação</label>
                                <textarea name="observacao" class="form-control" rows="4">{{$locacao->observacao}}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
                        <button type="submit" class="btn btn-light-success font-weight-bold">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL DE AGENDAMENTO DE TRANSPORTE (VEÍCULO + MOTORISTA) -->
    <div class="modal fade" id="modalAgendamentoTransporte" data-backdrop="static" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white" id="modalAgendamentoTitle">Agendar Transporte</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">x</button>
                </div>
                <form id="formAgendamentoTransporte" method="post" action="">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="form-group col-12">
                                <label class="font-weight-bold">Data e Hora Prevista de Saída</label>
                                <input type="datetime-local" class="form-control" name="data_hora_saida" id="data_hora_saida" required value="{{ date('Y-m-d\TH:i') }}">
                            </div>

                            <div class="form-group col-12">
                                <label class="font-weight-bold">Selecione o Veículo / Caminhão</label>
                                <select class="form-control select2-modal" style="width: 100%" id="select_veiculo_modal" name="veiculo_id" required>
                                    <option value="">Selecione o Veículo</option>
                                    @foreach(App\Models\Veiculo::where('empresa_id', $locacao->empresa_id)->get() as $v)
                                        <option value="{{$v->id}}">{{$v->placa}} - {{$v->modelo}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group col-12">
                                <label class="font-weight-bold">Selecione o Motorista</label>
                                <select class="form-control select2-modal" style="width: 100%" id="select_motorista_modal" name="motorista_id" required>
                                    <option value="">Selecione o Motorista</option>
                                    @foreach(App\Models\Funcionario::where('empresa_id', $locacao->empresa_id)->get() as $m)
                                        <option value="{{$m->id}}">{{$m->nome}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success font-weight-bold">Confirmar Agendamento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @section('javascript')
        <script type="text/javascript">
            // ABRE O MODAL PARA DEFINIR VEÍCULO, MOTORISTA E DATA/HORA
            function abrirModalAgendamento(urlAcao, titulo) {
                $('#formAgendamentoTransporte').attr('action', urlAcao);
                $('#modalAgendamentoTitle').text(titulo);

                // Exibe o modal
                $('#modalAgendamentoTransporte').modal('show');
            }

            // Quando o modal for totalmente aberto, reinicializa o Select2 com z-index correto
            $('#modalAgendamentoTransporte').on('shown.bs.modal', function () {
                $('.select2-modal').select2({
                    dropdownParent: $('#modalAgendamentoTransporte')
                });
            });

            function confirmarFinalizacao(urlAcao) {
                swal({
                    title: "Confirmação",
                    text: "Confirmar a retirada e finalizar a locação?",
                    icon: "warning",
                    buttons: ["Cancelar", "Confirmar"],
                }).then((confirmado) => {
                    if (confirmado) {
                        window.location.href = urlAcao;
                    }
                });
            }

            $('#kt_select2_1').change(() => {
                let p = $('#kt_select2_1').val()

                if(p > 0){
                    let idLocacao = $('#idLocacao').val()

                    $.get(path + 'locacao/validaEstoque/'+p+'/'+idLocacao)
                        .done((res) => {
                            if(res.semEstoqueData != ""){
                                swal("Atenção", "Produto sem estoque na data " + res.semEstoqueData, "warning")
                                $('#kt_select2_1').val('').change()
                                $('#valor').val('')
                            } else {
                                $('#valor').val(parseFloat(res.valor_locacao).toFixed(casas_decimais).replace(".", ","))
                            }
                        })
                        .fail((err) => {
                            console.log(err)
                            swal('Erro', 'Algo deu errado ao validar estoque', 'error')
                        })
                }else{
                    $('#valor').val('')
                }
            })
        </script>
    @endsection
@endsection
