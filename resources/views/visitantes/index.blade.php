@extends('default.layout')

@section('content')
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-6">
                <h2 class="page-title">Cadastro de Visitantes</h2>
            </div>
        </div>

        <!-- FILTRO E PESQUISA -->
        <div class="card mb-4">
            <div class="card-body">
                <form action="{{ url('/visitantes') }}" method="GET">
                    <div class="row align-items-end">
                        <div class="col-md-10 form-group mb-0">
                            <label>Pesquisar por Nome ou CPF</label>
                            <input type="text" name="pesquisa" class="form-control" value="{{ $pesquisa }}" placeholder="Digite o nome ou CPF do visitante...">
                        </div>
                        <div class="col-md-2 form-group mb-0">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- TABELA DE VISITANTES -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="bg-dark text-white">
                        <tr>
                            <th width="80">Foto</th>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>WhatsApp</th>
                            <th>Status (Lista Negra)</th>
                            <th width="120">Ações</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($visitantes as $v)
                            <tr>
                                <td>
                                    @if($v->foto)
                                        <img src="{{ asset('storage/' . $v->foto) }}" alt="Foto" style="width: 50px; height: 50px; object-fit: cover; border-radius: 50%;">
                                    @else
                                        <div style="width: 50px; height: 50px; background: #ddd; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-user text-muted"></i>
                                        </div>
                                    @endif
                                </td>
                                <td class="align-middle"><strong>{{ $v->nome }}</strong></td>
                                <td class="align-middle">{{ $v->cpf }}</td>
                                <td class="align-middle">{{ $v->telefone ?? 'Não informado' }}</td>
                                <td class="align-middle">
                                    @if($v->bloqueado == 1)
                                        <span class="badge badge-danger" title="{{ $v->motivo_bloqueio }}">BLOQUEADO</span>
                                    @else
                                        <span class="badge badge-success">LIBERADO</span>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    <button type="button" class="btn btn-sm btn-warning btn-editar"
                                            data-id="{{ $v->id }}"
                                            data-nome="{{ $v->nome }}"
                                            data-telefone="{{ $v->telefone }}"
                                            data-bloqueado="{{ $v->bloqueado }}"
                                            data-motivo="{{ $v->motivo_bloqueio }}">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">Nenhum visitante encontrado.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <!-- Paginação do Laravel -->
                <div class="d-flex justify-content-center mt-3">
                    {{ $visitantes->appends(['pesquisa' => $pesquisa])->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL DE EDIÇÃO -->
    <div class="modal fade" id="modalEditarVisitante" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">Editar Visitante</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ url('/visitantes/atualizar') }}" method="POST">
                    @csrf
                    <input type="hidden" name="visitante_id" id="edit_visitante_id">
                    <div class="modal-body">

                        <div class="form-group">
                            <label>Nome Completo</label>
                            <input type="text" name="nome" id="edit_nome" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>WhatsApp (Telefone)</label>
                            <input type="text" name="telefone" id="edit_telefone" class="form-control" placeholder="(00) 00000-0000">
                        </div>

                        <hr>

                        <div class="form-group">
                            <label class="text-danger font-weight-bold"><i class="fas fa-ban"></i> Lista Negra (Bloqueio)</label>
                            <select name="bloqueado" id="edit_bloqueado" class="form-control">
                                <option value="0">LIBERADO (Pode entrar)</option>
                                <option value="1">BLOQUEADO (Proibida a entrada)</option>
                            </select>
                        </div>

                        <div class="form-group" id="div_motivo_bloqueio" style="display: none;">
                            <label>Motivo do Bloqueio</label>
                            <textarea name="motivo_bloqueio" id="edit_motivo" class="form-control" rows="2" placeholder="Obrigatório caso esteja bloqueado..."></textarea>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    <script>
        $(document).ready(function() {

            // Máscara para o telefone no modal (se você usar jquery.mask)
            if($.fn.mask) {
                $('#edit_telefone').mask('(00) 00000-0000');
            }

            // Exibe ou esconde o campo de motivo dependendo da seleção
            $('#edit_bloqueado').on('change', function() {
                if ($(this).val() == '1') {
                    $('#div_motivo_bloqueio').slideDown();
                    $('#edit_motivo').prop('required', true);
                } else {
                    $('#div_motivo_bloqueio').slideUp();
                    $('#edit_motivo').prop('required', false).val('');
                }
            });

            // Puxa os dados para o Modal
            $('.btn-editar').on('click', function() {
                $('#edit_visitante_id').val($(this).data('id'));
                $('#edit_nome').val($(this).data('nome'));

                let tel = $(this).data('telefone');
                if (tel) { $('#edit_telefone').val(tel).trigger('input'); }
                else { $('#edit_telefone').val(''); }

                let bloqueado = $(this).data('bloqueado');
                $('#edit_bloqueado').val(bloqueado).trigger('change');
                $('#edit_motivo').val($(this).data('motivo'));

                $('#modalEditarVisitante').modal('show');
            });

            // Trava de processamento no submit
            $('form').on('submit', function() {
                let btn = $(this).find('button[type="submit"]');
                btn.html('<i class="fas fa-spinner fa-spin"></i> Salvando...');
                btn.prop('disabled', true);
            });
        });
    </script>
@endsection
