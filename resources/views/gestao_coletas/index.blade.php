@extends('default.layout')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-extras.css') }}?v={{ filemtime(public_path('css/ui-extras.css')) }}">
@endpush

@section('content')

    <div class="fer-extra fer-extra--support fer-extra--gestao_coletas card card-custom gutter-b">
        <div class="card-header">
            <div class="card-title">
                <h3 class="card-label">
                    <i class="la la-truck-loading text-success fs-2 me-2"></i>
                    Gestão de Solicitações de Coleta
                </h3>
            </div>
        </div>

        <div class="card-body">
            @if(session('sucesso'))
                <div class="alert alert-success">{{ session('sucesso') }}</div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead class="thead-light">
                    <tr>
                        <th>Cliente</th>
                        <th>Material</th>
                        <th>Disponibilidade</th>
                        <th>Observação</th>
                        <th>Fotos</th>
                        <th>Ações (Agendamento)</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($coletas as $c)
                        <tr>
                            <td class="align-middle fw-bold">{{ $c->pessoa->nome_completo }}</td>
                            <td class="align-middle">{{ $c->material }}</td>
                            <td class="align-middle text-primary">{{ $c->dias_disponiveis }}</td>
                            <td class="align-middle">{{ $c->observacao ?? '-' }}</td>
                            <td class="align-middle">
                                @if($c->fotos)
                                    @php $fotosArr = json_decode($c->fotos, true); @endphp
                                    @if(is_array($fotosArr) && count($fotosArr) > 0)
                                        <button class="btn btn-sm btn-info" onclick="verFotos({{ $c->id }})">
                                            <i class="la la-camera"></i> Ver ({{ count($fotosArr) }})
                                        </button>

                                        <!-- Galeria Oculta para este ID -->
                                        <div id="galeria-{{ $c->id }}" class="d-none">
                                            @foreach($fotosArr as $foto)
                                                <img src="{{ asset('storage/' . $foto) }}" class="img-fluid mb-2 border rounded" style="max-height: 250px;">
                                            @endforeach
                                        </div>
                                    @endif
                                @else
                                    <span class="text-muted">Sem fotos</span>
                                @endif
                            </td>
                            <td class="align-middle">
                                <!-- BOTÃO ANTIGO (Mantido para segurança) -->
                                <button type="button" class="btn btn-sm btn-success fw-bold mb-1" onclick="abrirModalAgendamento({{ $c->id }}, '{{ $c->pessoa->nome_completo }}')">
                                    <i class="la la-bolt"></i> Rápido
                                </button>

                                <!-- NOVO BOTÃO (Vai para a tela completa) -->
                                <a href="/movimentacaoVeiculo/new?coleta_id={{ $c->id }}" class="btn btn-sm btn-primary fw-bold mb-1">
                                    <i class="la la-calendar-check"></i> Completo
                                </a>

                                <button type="button" class="btn btn-sm btn-danger fw-bold mb-1" onclick="abrirModalRecusa({{ $c->id }}, '{{ $c->pessoa->nome_completo }}')">
                                    <i class="la la-times"></i> Recusar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Nenhuma solicitação pendente.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL DE AGENDAMENTO INTELIGENTE -->
    <div class="modal fer-extra-modal fade" id="modal-agendar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="form-agendar" method="POST">
                    @csrf
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title text-white">Agendar Coleta - <span id="nome_cliente_modal"></span></h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">

                        <div class="form-group mb-4">
                            <label class="font-weight-bold">Quem fará o transporte do material? *</label>
                            <select name="tipo_transporte" id="tipo_transporte" class="form-control" required>
                                <option value="frota">Nossa Frota fará a retirada</option>
                                <option value="cliente_traz">O Cliente trará até a empresa</option>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label class="font-weight-bold">Data/Hora Agendada *</label>
                            <input type="datetime-local" name="data_agendada" class="form-control" required>
                        </div>

                        <!-- Div do Veículo (Só aparece se for a Frota) -->
                        <div class="form-group mb-3" id="div_veiculo">
                            <label class="font-weight-bold">Selecione o Veículo (Frota) *</label>
                            <select name="veiculo_id" id="veiculo_id" class="form-control" required>
                                <option value="">Selecione...</option>
                                @foreach($veiculos as $v)
                                    <option value="{{ $v->id }}">{{ $v->placa }} - {{ $v->modelo }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Seleção do Motorista ao Agendar -->
                        <div class="form-group mb-3">
                            <label class="font-weight-bold">Selecione o Motorista *</label>
                            <select name="motorista_id" id="motorista_id" class="form-control" required>
                                <option value="">Selecione o motorista...</option>
                                @foreach($funcionarios as $f)
                                    <option value="{{ $f->id }}">{{ $f->nome }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Div do Funcionário da Portaria (Só aparece se o cliente trouxer) -->
                        <div class="form-group mb-3" id="div_funcionario" style="display: none;">
                            <label class="font-weight-bold">Qual funcionário vai recebê-lo na Portaria? *</label>
                            <select name="funcionario_id" id="funcionario_id" class="form-control">
                                <option value="">Selecione o funcionário responsável...</option>
                                @if(isset($funcionarios))
                                    @foreach($funcionarios as $func)
                                        <option value="{{ $func->id }}">{{ $func->nome }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success fw-bold"><i class="la la-check"></i> Confirmar Agendamento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL DE RECUSA COM MOTIVO -->
    <div class="modal fade" id="modal-recusar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="form-recusar" method="POST">
                    @csrf
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title text-white">Recusar Solicitação - <span id="nome_cliente_recusa"></span></h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label class="font-weight-bold">Informe o motivo da recusa para o parceiro *</label>
                            <textarea name="motivo" class="form-control" rows="3" placeholder="Ex: Categoria de resíduo divergente, horário indisponível, etc." required></textarea>
                            <small class="form-text text-muted">Esta justificativa será enviada automaticamente para o WhatsApp do parceiro e gravada no histórico.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger fw-bold"><i class="la la-ban"></i> Confirmar Recusa</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL PARA VER FOTOS -->
    <div class="modal fer-extra-modal fade" id="modal-fotos" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title text-white">Fotos do Material</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body text-center" id="corpo-fotos">
                    <!-- Imagens injetadas via JS -->
                </div>
            </div>
        </div>
    </div>

@endsection

@section('javascript')
    <script>
        function abrirModalAgendamento(id, nomeCliente) {
            $('#nome_cliente_modal').text(nomeCliente);
            $('#form-agendar').attr('action', '/gestao-coletas/agendar/' + id);

            // Reseta o form toda vez que abrir
            $('#tipo_transporte').val('frota').trigger('change');

            $('#modal-agendar').modal('show');
        }

        function abrirModalRecusa(id, nomeCliente) {
            $('#nome_cliente_recusa').text(nomeCliente);
            $('#form-recusar').attr('action', '/gestao-coletas/recusar/' + id);
            $('#form-recusar').find('textarea[name="motivo"]').val('');
            $('#modal-recusar').modal('show');
        }

        function verFotos(id) {
            let fotosHtml = $('#galeria-' + id).html();
            $('#corpo-fotos').html(fotosHtml);
            $('#modal-fotos').modal('show');
        }

        // Alterna inteligentemente os campos entre Veículo e Funcionário da Portaria
        $('#tipo_transporte').change(function() {
            if ($(this).val() === 'cliente_traz') {
                $('#div_veiculo').slideUp();
                $('#veiculo_id').prop('required', false);
                $('#veiculo_id').val('');

                $('#div_funcionario').slideDown();
                $('#funcionario_id').prop('required', true);
            } else {
                $('#div_funcionario').slideUp();
                $('#funcionario_id').prop('required', false);
                $('#funcionario_id').val('');

                $('#div_veiculo').slideDown();
                $('#veiculo_id').prop('required', true);
            }
        });
    </script>
@endsection
