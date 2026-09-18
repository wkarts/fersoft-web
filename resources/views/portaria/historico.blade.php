@extends('default.layout')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-6">
            <h2 class="page-title">Histórico de Portaria</h2>
        </div>
        <div class="col-6 text-right">
            <!-- Botão para voltar ao painel principal -->
            <a href="{{ url('/portaria') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar para Portaria
            </a>
        </div>
    </div>

    <!-- Filtro de Pesquisa -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ url('/portaria/historico') }}" method="GET">
                <div class="row align-items-end">
                    <div class="col-md-2 form-group mb-3 mb-md-0">
                        <label>Data Inicial</label>
                        <input type="date" name="data_inicial" class="form-control" value="{{ $dataInicial }}">
                    </div>
                    <div class="col-md-2 form-group mb-3 mb-md-0">
                        <label>Data Final</label>
                        <input type="date" name="data_final" class="form-control" value="{{ $dataFinal }}">
                    </div>
                    <div class="col-md-3 form-group mb-3 mb-md-0">
                        <label>Nome do Visitante</label>
                        <input type="text" name="nome_visitante" class="form-control" value="{{ $nomeVisitante }}" placeholder="Parte do nome...">
                    </div>
                    <div class="col-md-3 form-group mb-3 mb-md-0">
                        <label>Placa do Veículo</label>
                        <input type="text" name="placa_veiculo" class="form-control" value="{{ $placaVeiculo }}" placeholder="ABC-1234">
                    </div>
                    <div class="col-md-2 form-group mb-0">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela de Resultados -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="bg-dark text-white">
                        <tr>
                            <th>Data/Hora Entrada</th>
                            <th>Data/Hora Saída</th>
                            <th>Visitante</th>
                            <th>Placa</th>
                            <th>Visitado (Procurou)</th>
                            <th>Liberado por</th>
                            <th>Obs</th>
                            <th>Ações</th> <!-- Coluna de ações adicionada aqui -->
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movimentos as $mov)
                        <tr>
                            <td>{{ $mov->data_hora_entrada->format('d/m/Y H:i') }}</td>
                            <td>
                                @if($mov->data_hora_saida)
                                    {{ $mov->data_hora_saida->format('d/m/Y H:i') }}
                                @else
                                    <span class="badge badge-warning">Ainda na empresa</span>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $mov->visitante->nome }}</strong>
                                <!-- Selo de bloqueio visual -->
                                @if($mov->visitante->bloqueado == 1)
                                    <br><span class="badge badge-danger">BLOQUEADO</span>
                                @endif
                            </td>
                            <td>{{ $mov->placa_veiculo ?? '-' }}</td>
                            <td>{{ $mov->funcionarioVisitado->nome ?? '-' }}</td>
                            <td>{{ $mov->porteiro->nome ?? '-' }}</td>
                            <td>{{ $mov->observacoes ?? '-' }}</td>
                            
                            <!-- Botoes de Bloqueio/Desbloqueio -->
                            <td>
                                @if(session('user_logged')['adm'] == 1)
                                    @if($mov->visitante->bloqueado == 1)
                                        <button type="button" class="btn btn-sm btn-success btn-desbloquear" 
                                                data-id="{{ $mov->visitante->id }}" 
                                                data-nome="{{ $mov->visitante->nome }}"
                                                title="Remover da Lista Negra">
                                            <i class="fas fa-check-circle"></i> Desbloquear
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-sm btn-danger btn-bloquear" 
                                                data-id="{{ $mov->visitante->id }}" 
                                                data-nome="{{ $mov->visitante->nome }}"
                                                title="Adicionar à Lista Negra">
                                            <i class="fas fa-ban"></i> Bloquear
                                        </button>
                                    @endif
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">Nenhum registro encontrado para este período.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DE BLOQUEIO -->
<div class="modal fade" id="modalBloqueio" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Bloquear Visitante</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
          <p>Você está adicionando <strong><span id="nome_visitante_bloqueio"></span></strong> à lista negra.</p>
          <input type="hidden" id="visitante_id_bloqueio">
          <div class="form-group">
              <label>Motivo do Bloqueio:</label>
              <textarea id="motivo_bloqueio" class="form-control" rows="3" placeholder="Ex: Ex-funcionário, causou confusão na recepção, etc." required></textarea>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger" id="btn_confirmar_bloqueio">Confirmar Bloqueio</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('javascript')
<script>
$(document).ready(function() {
    // 1. Abre o modal para bloquear
    $('.btn-bloquear').on('click', function() {
        $('#visitante_id_bloqueio').val($(this).data('id'));
        $('#nome_visitante_bloqueio').text($(this).data('nome'));
        $('#motivo_bloqueio').val('');
        $('#modalBloqueio').modal('show');
    });

    // 2. Confirma o bloqueio via AJAX
    $('#btn_confirmar_bloqueio').on('click', function() {
        let visitanteId = $('#visitante_id_bloqueio').val();
        let motivo = $('#motivo_bloqueio').val();

        if (!motivo) {
            swal("Atenção", "É obrigatório informar o motivo do bloqueio.", "warning");
            return;
        }

        let btn = $(this);
        btn.prop('disabled', true).text('Bloqueando...');

        $.ajax({
            url: "{{ url('/portaria/bloquear-visitante') }}",
            type: 'POST',
            data: { 
                _token: '{{ csrf_token() }}', 
                visitante_id: visitanteId, 
                motivo: motivo, 
                acao_bloquear: 1 
            },
            success: function() {
                swal("Sucesso", "Visitante bloqueado com sucesso!", "success").then(() => {
                    location.reload(); // Recarrega a página para atualizar o botão
                });
            },
            error: function() {
                swal("Erro", "Falha ao bloquear visitante.", "error");
                btn.prop('disabled', false).text('Confirmar Bloqueio');
            }
        });
    });

    // 3. Desbloqueia direto via AJAX
    $('.btn-desbloquear').on('click', function() {
        let visitanteId = $(this).data('id');
        
        swal({
            title: "Desbloquear?",
            text: "Este visitante terá o acesso liberado novamente.",
            icon: "warning",
            buttons: ["Cancelar", "Sim, Desbloquear"],
            dangerMode: true,
        }).then((willDelete) => {
            if (willDelete) {
                $.ajax({
                    url: "{{ url('/portaria/bloquear-visitante') }}",
                    type: 'POST',
                    data: { 
                        _token: '{{ csrf_token() }}', 
                        visitante_id: visitanteId, 
                        acao_bloquear: 0 // 0 para remover bloqueio
                    },
                    success: function() {
                        swal("Liberado", "Visitante desbloqueado!", "success").then(() => {
                            location.reload();
                        });
                    }
                });
            }
        });
    });
});
</script>
@endsection