@extends('default.layout')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-document-fiscal.css') }}?v={{ filemtime(public_path('css/ui-document-fiscal.css')) }}">
@endpush
@section('content')

    <div class="document-fiscal-page document-fiscal-page--tabela-preco-nfe container-fluid">
        <h2 class="mb-4">Tabela de Preços para NF-e (Pauta Fiscal)</h2>

        @if(session('sucesso'))
            <div class="alert alert-success">
                {{ session('sucesso') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                @foreach($errors->all() as $erro)
                    <div>{{ $erro }}</div>
                @endforeach
            </div>
        @endif

        <div class="card mb-4">
            <div class="card-header" id="card-titulo">Cadastrar / Atualizar Preço</div>
            <div class="card-body">
                <form action="{{ url('/tabelaPrecoNfe/save') }}" method="POST" class="row">
                    @csrf
                    <div class="col-md-5 form-group">
                        <label class="font-weight-bold">Produto Padrão <span class="text-danger">*</span></label>
                        <select name="produto_id" class="form-control select2" id="kt_select2_1" style="width: 100%" required>
                            <option value="">Selecione o produto de sucata...</option>
                            @foreach($produtos as $p)
                                <option value="{{ $p->id }}" {{ old('produto_id') == $p->id ? 'selected' : '' }}>
                                    {{ !empty($p->referencia) ? '[' . $p->referencia . '] ' : '' }}{{ $p->nome }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Preço Base NFe (R$)</label>
                        <input type="text" name="preco_nfe" id="preco_nfe" class="form-control" value="{{ old('preco_nfe') }}" placeholder="Ex: 12,90" inputmode="decimal" required>
                    </div>
                    <div class="col-md-3">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">Salvar Preço</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Preços Configurados</div>
            <div class="card-body p-0">
                <table class="table table-striped table-hover m-0">
                    <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Preço Base NF-e (R$)</th>
                        <th width="150">Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    @if(isset($precos_nfe) && count($precos_nfe) > 0)
                        @foreach($precos_nfe as $preco)
                            <tr>
                                <td>{{ $preco->produto_nome }}</td>
                                <td>R$ {{ number_format($preco->preco_nfe, 2, ',', '.') }}</td>
                                <td>
                                    <!-- Botão de Editar -->
                                    <button type="button" class="btn btn-warning btn-sm btn-editar"
                                            data-id="{{ $preco->produto_id }}"
                                            data-preco="{{ number_format($preco->preco_nfe, 2, ',', '.') }}">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <form action="{{ route('tabela-preco-nfe.delete', $preco->id) }}" method="POST"
                                          class="d-inline" onsubmit="return confirm('Excluir este preço?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" title="Excluir preço">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="3" class="text-center py-3">Nenhum preço configurado ainda.</td>
                        </tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    <script>
        $(document).on('click', '.btn-editar', function() {
            let produtoId = $(this).attr('data-id');
            let preco = $(this).attr('data-preco');

            // Reutiliza a seleção existente, inclusive com Select2.
            $('#kt_select2_1').val(produtoId).trigger('change');
            $('#preco_nfe').val(preco);
            $('#card-titulo').html('<strong>Editando Preço do Produto</strong>');
            window.scrollTo({top: 0, behavior: 'smooth'});
        });
    </script>
@endsection
