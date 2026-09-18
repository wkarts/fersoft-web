@extends('default.layout')
@section('content')

    <div class="card card-custom gutter-b shadow-sm border-0 rounded-lg">
        <div class="card-body p-6">
            <!-- CABEÇALHO -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-6 gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="/agendamentos" class="btn btn-sm btn-light-secondary font-weight-bold me-3">
                            <i class="la la-arrow-left"></i> Voltar
                        </a>
                        <h3 class="font-weight-bold m-0">Agendamento <span class="text-primary">#{{$agendamento->id}}</span></h3>
                    </div>
                    <div class="text-muted mt-1">
                        <i class="la la-user me-1"></i> Cliente: <strong>{{$agendamento->cliente->razao_social}}</strong> ({{$agendamento->cliente->telefone}})
                        @if(isset($agendamento->veiculo))
                            <span class="ms-3"><i class="la la-car me-1"></i> Veículo: <strong>{{$agendamento->veiculo->placa}} - {{$agendamento->veiculo->marca}} {{$agendamento->veiculo->modelo}}</strong></span>
                        @endif
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    @if($agendamento->status != 2)
                        <a class="btn btn-primary font-weight-bold shadow-sm" onclick='swal("Confirmar", "Deseja gerar uma Ordem de Serviço a partir deste agendamento?", "info").then((sim) => {if(sim){ location.href="/agendamentos/gerarOs/{{ $agendamento->id }}" }})' href="#!">
                            <i class="la la-tools me-1"></i> Gerar Ordem de Serviço (OS)
                        </a>

                        @if($agendamento->status == 0)
                            <a class="btn btn-success font-weight-bold shadow-sm" onclick='swal("Atenção!", "Deseja ir para a frente de caixa?", "warning").then((sim) => {if(sim){ location.href="/agendamentos/irParaFrenteCaixa/{{ $agendamento->id }}" }})' href="#!">
                                <i class="la la-cash-register me-1"></i> Frente de Caixa
                            </a>
                        @endif
                    @endif
                </div>
            </div>

            <!-- MOTIVO DO CANCELAMENTO (SE APLICÁVEL) -->
            @if($agendamento->status == 2)
                <div class="alert alert-custom alert-danger p-4 rounded-lg mb-6" role="alert">
                    <div class="alert-icon"><i class="la la-times-circle fs-2"></i></div>
                    <div class="alert-text">
                        <strong>AGENDAMENTO CANCELADO</strong><br>
                        <span><strong>Motivo / Justificativa:</strong> {{ $agendamento->motivo_cancelamento ?? 'Não especificado' }}</span>
                    </div>
                </div>
            @endif

            <div class="row g-4">
                <div class="col-md-6 col-12">
                    <div class="card bg-light p-5 border-0 rounded-lg">
                        <h5 class="text-dark font-weight-bold mb-3"><i class="la la-clock me-1 text-primary"></i> Horário Agendado</h5>
                        <p class="m-0 fs-6">Data: <strong>{{ \Carbon\Carbon::parse($agendamento->data)->format('d/m/Y')}}</strong></p>
                        <p class="m-0 fs-6">Horário: <strong class="text-success">{{ $agendamento->inicio}}</strong> às <strong class="text-danger">{{ $agendamento->termino}}</strong></p>
                        <p class="m-0 fs-6">Atendente: <strong>{{$agendamento->funcionario->nome ?? 'Não informado'}}</strong></p>
                    </div>
                </div>

                <div class="col-md-6 col-12">
                    <div class="card bg-light p-5 border-0 rounded-lg">
                        <h5 class="text-dark font-weight-bold mb-3"><i class="la la-list-alt me-1 text-primary"></i> Serviços Previstos</h5>
                        @foreach($agendamento->itens as $s)
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <span>{{$s->servico->nome}}</span>
                                <strong>R$ {{ moeda($s->servico->valor) }}</strong>
                            </div>
                        @endforeach
                        <div class="d-flex justify-content-between pt-3">
                            <span class="font-weight-bold fs-5">Total:</span>
                            <strong class="text-success fs-4">R$ {{moeda($agendamento->total)}}</strong>
                        </div>
                    </div>
                </div>
            </div>

            @if($agendamento->observacao != '')
                <div class="alert alert-custom alert-outline-warning mt-5 p-4 rounded-lg" role="alert">
                    <div class="alert-icon"><i class="la la-exclamation-triangle fs-2"></i></div>
                    <div class="alert-text"><strong>Queixa do Cliente / Observação:</strong> {{$agendamento->observacao}}</div>
                </div>
            @endif

            <div class="d-flex justify-content-between mt-6 pt-4 border-top">
                @if($agendamento->status != 2)
                    <button type="button" class="btn btn-light-danger font-weight-bold" data-toggle="modal" data-target="#modal-cancelar">
                        <i class="la la-times-circle me-1"></i> Cancelar Agendamento
                    </button>

                    @if($agendamento->status == 0)
                        <a class="btn btn-warning font-weight-bold shadow-sm" onclick='swal("Atenção!", "Deseja alterar para finalizado?", "warning").then((sim) => {if(sim){ location.href="/agendamentos/alterarStatus/{{ $agendamento->id }}" }})' href="#!">
                            <i class="la la-check-circle me-1"></i> Marcar como Finalizado
                        </a>
                    @elseif($agendamento->status == 3)
                        <span class="badge bg-light-primary text-primary font-weight-bold p-3 fs-6">EM EXECUÇÃO NA OFICINA</span>
                        <a class="btn btn-success font-weight-bold shadow-sm" onclick='swal("Atenção!", "Deseja alterar para finalizado?", "warning").then((sim) => {if(sim){ location.href="/agendamentos/alterarStatus/{{ $agendamento->id }}" }})' href="#!">
                            <i class="la la-check-circle me-1"></i> Marcar como Concluído
                        </a>
                    @else
                        <span class="badge bg-light-success text-success font-weight-bold p-3 fs-6">CONCLUÍDO</span>
                    @endif
                @else
                    <span class="badge bg-light-danger text-danger font-weight-bold p-3 fs-6">CANCELADO</span>
                @endif
            </div>
        </div>
    </div>

    <!-- MODAL DE JUSTIFICATIVA DO CANCELAMENTO -->
    <div class="modal fade" id="modal-cancelar" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="post" action="/agendamentos/cancelar/{{ $agendamento->id }}">
                    @csrf
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title text-white"><i class="la la-times-circle text-white me-1"></i> Cancelar Agendamento #{{ $agendamento->id }}</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                    </div>

                    <div class="modal-body">
                        <div class="form-group mb-0">
                            <label class="form-label font-weight-bold">Informe o Motivo / Justificativa *</label>
                            <textarea class="form-control" name="motivo_cancelamento" rows="3" required placeholder="Ex: Cliente solicitou o cancelamento por motivo de viagem, reagendado para próxima semana..."></textarea>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Voltar</button>
                        <button type="submit" class="btn btn-danger font-weight-bold">
                            <i class="la la-check"></i> Confirmar Cancelamento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
