@extends('default.layout')
@section('content')
    <div class="d-flex flex-column flex-column-fluid" id="kt_content">
        <div class="card card-custom gutter-b shadow-sm border-0">
            <div class="card-header border-0 py-5">
                <h3 class="card-title align-items-start flex-column">
                <span class="card-label font-weight-bolder text-dark">
                    <i class="la la-file-invoice-dollar icon-xl text-primary mr-2"></i> {{ $title }}
                </span>
                    <span class="text-muted mt-2 font-weight-bold font-size-sm">Importe o TXT da contabilidade e gerencie os contracheques</span>
                </h3>
                <div class="card-toolbar">
                    <button type="button" class="btn btn-primary font-weight-bolder" data-toggle="modal" data-target="#modalImportar">
                        <i class="la la-upload"></i> Importar TXT da Folha
                    </button>
                </div>
            </div>

            <div class="card-body pt-0 pb-10">
                @if(session('mensagem_sucesso'))
                    <div class="alert alert-success">{!! session('mensagem_sucesso') !!}</div>
                @endif
                @if(session('mensagem_erro'))
                    <div class="alert alert-danger">{!! session('mensagem_erro') !!}</div>
                @endif

                <!-- Filtro Unificado com Botão de Exclusão de Lote -->
                <form method="GET" action="{{ route('folha.index') }}" class="mb-7">
                    <div class="row align-items-center">
                        <div class="col-md-3 mb-2">
                            <select name="competencia" id="selectCompetencia" class="form-control" onchange="this.form.submit()">
                                <option value="">Todas as Competências</option>
                                @foreach($competencias as $comp)
                                    <option value="{{ $comp }}" {{ request('competencia') == $comp ? 'selected' : '' }}>{{ $comp }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-2">
                            <input type="text" name="busca" class="form-control" placeholder="Buscar por Nome, CPF ou Matrícula..." value="{{ request('busca') }}">
                        </div>
                        <div class="col-md-5 mb-2 d-flex flex-wrap align-items-center">
                            <button type="submit" class="btn btn-light-primary font-weight-bold mr-2 mb-1"><i class="la la-search"></i> Filtrar</button>
                            <a href="{{ route('folha.index') }}" class="btn btn-light font-weight-bold mr-2 mb-1">Limpar</a>

                            @if(request()->filled('competencia'))
                                <button type="button" class="btn btn-danger font-weight-bold mb-1" onclick="confirmarExclusaoLote('{{ request('competencia') }}')">
                                    <i class="la la-trash"></i> Excluir Mês {{ request('competencia') }}
                                </button>
                            @endif
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="thead-light">
                        <tr>
                            <th>Matrícula</th>
                            <th>Funcionário</th>
                            <th>CPF</th>
                            <th>Competência</th>
                            <th>Salário Base</th>
                            <th>Vencimentos</th>
                            <th>Descontos</th>
                            <th>Líquido</th>
                            <th class="text-center">Ações</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($holerites as $h)
                            <tr>
                                <td>{{ $h->matricula }}</td>
                                <td><strong>{{ $h->funcionario_nome ?? '-' }}</strong></td>
                                <td>{{ $h->cpf }}</td>
                                <td><span class="badge badge-info">{{ $h->competencia }}</span></td>
                                <td>R$ {{ number_format($h->salario_base, 2, ',', '.') }}</td>
                                <td class="text-success">R$ {{ number_format($h->total_vencimentos, 2, ',', '.') }}</td>
                                <td class="text-danger">R$ {{ number_format($h->total_descontos, 2, ',', '.') }}</td>
                                <td><strong>R$ {{ number_format($h->valor_liquido, 2, ',', '.') }}</strong></td>
                                <td class="text-center">
                                    <a href="{{ route('folha.pdf', $h->id) }}" target="_blank" class="btn btn-sm btn-light-danger" title="Visualizar PDF">
                                        <i class="la la-file-pdf"></i> PDF
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-5">Nenhum contracheque localizado para esta consulta.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $holerites->appends(request()->all())->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Importação do TXT -->
    <div class="modal fade" id="modalImportar" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('folha.importar') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title font-weight-bold">Importar Arquivo TXT da Folha</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <i aria-hidden="true" class="ki ki-close"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Selecione o arquivo TXT emitido pela contabilidade: <span class="text-danger">*</span></label>
                            <input type="file" name="arquivo_txt" class="form-control" accept=".txt" required>
                            <small class="form-text text-muted">Exemplo: <code>FOLHA072026.txt</code></small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary font-weight-bold"><i class="la la-check"></i> Processar Importação</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Formulário oculto para envio do DELETE via AJAX/SweetAlert -->
    <form id="formExcluirLote" method="POST" action="" style="display: none;">
        @csrf
        @method('DELETE')
    </form>

    <script>
        function confirmarExclusaoLote(competencia) {
            Swal.fire({
                title: 'Excluir lote de ' + competencia + '?',
                text: 'Todos os contracheques e eventos importados deste mês serão removidos permanentemente desta empresa!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f64e60',
                cancelButtonColor: '#7E8299',
                confirmButtonText: 'Sim, excluir lote!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    let form = document.getElementById('formExcluirLote');
                    form.action = "{{ url('/folhaPagamento/excluir') }}/" + encodeURIComponent(competencia);
                    form.submit();
                }
            });
        }
    </script>
@endsection
