@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 text-dark font-weight-bold">
            <i class="fas fa-list text-primary mr-2"></i> {{ $title }}
        </h5>
    </div>
    
    <div class="card-body">
        <form action="/tabelas-precos/save" method="POST">
            @csrf
            <input type="hidden" name="id" value="{{ $data->id ?? '' }}">
            
            <div class="form-group row">
                <div class="col-lg-6">
                    <label>Nome da Tabela <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="descricao" value="{{ old('descricao', $data->descricao ?? '') }}" placeholder="Ex: Tabela Capital, Tabela Interior..." required>
                </div>
            </div>

            <hr>
            
            <div class="d-flex justify-content-between mb-3">
                <h6 class="font-weight-bolder">Itens (Preços por Produto)</h6>
                <button type="button" class="btn btn-sm btn-primary" onclick="adicionarLinha()">
                    <i class="fas fa-plus"></i> Adicionar Produto
                </button>
            </div>

            <table class="table table-bordered table-hover" id="tabela-itens">
                <thead class="thead-light">
                    <tr>
                        <th>Produto / Sucata (Busca por Nome, Cód ou Ref)</th>
                        <th width="200">Tipo de Frete</th>
                        <th width="200">Valor do Kg (R$)</th>
                        <th width="80" class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody id="corpo-tabela">
                    @if(isset($data) && $data->itens->count() > 0)
                        @foreach($data->itens as $item)
                            <tr>
                                <td>
                                    <select class="form-control select2-ajax" name="produto_id[]" required style="width: 100%;">
                                        @if($item->produto)
                                            <option value="{{ $item->produto_id }}" selected>
                                                {{ $item->produto->referencia ? 'Ref: '.$item->produto->referencia.' | ' : '' }}{{ $item->produto->nome }}
                                            </option>
                                        @endif
                                    </select>
                                </td>
                                <td>
                                    <select class="form-control" name="tipo_frete[]">
                                        <option value="ENTREGA" {{ $item->tipo_frete == 'ENTREGA' ? 'selected' : '' }}>Fornecedor Entrega</option>
                                        <option value="COLETA" {{ $item->tipo_frete == 'COLETA' ? 'selected' : '' }}>Nós Coletamos</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" class="form-control money" name="valor_kg[]" value="{{ number_format($item->valor_kg, 2, ',', '') }}" required>
                                </td>
                                <td class="text-center align-middle">
                                    <button type="button" class="btn btn-sm btn-danger" onclick="removerLinha(this)"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td>
                                <select class="form-control select2-ajax" name="produto_id[]" required style="width: 100%;">
                                    </select>
                            </td>
                            <td>
                                <select class="form-control" name="tipo_frete[]">
                                    <option value="ENTREGA">Fornecedor Entrega</option>
                                    <option value="COLETA">Nós Coletamos</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" class="form-control money" name="valor_kg[]" placeholder="0,00" required>
                            </td>
                            <td class="text-center align-middle">
                                <button type="button" class="btn btn-sm btn-danger" onclick="removerLinha(this)"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>

            <div class="card-footer bg-white border-0 text-right mt-5">
                <a href="/tabelas-precos" class="btn btn-light-danger font-weight-bold mr-2"><i class="fas fa-times"></i> Cancelar</a>
                <button type="submit" class="btn btn-success font-weight-bold"><i class="fas fa-save"></i> Salvar Tabela</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('javascript')
<script>
    // A mágica acontece aqui: O Laravel injeta a URL exata antes do JS rodar
    var searchUrl = "{{ route('tabelas-precos.search-produto') }}";

    // Inicializa a pesquisa Ajax do Select2
    function initSelect2Ajax() {
        $('.select2-ajax').select2({
            placeholder: "Digite o nome, ref ou código...",
            allowClear: true,
            ajax: {
                url: searchUrl, // <-- Usando a URL injetada pelo Blade
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { term: params.term };
                },
                processResults: function (data) {
                    return { results: data.results };
                },
                cache: true
            },
            minimumInputLength: 1, // Só pesquisa depois de digitar 1 letra
        });
    }

    $(document).ready(function() {
        initSelect2Ajax();
    });

    // Função para clonar campos Select2 com Ajax
    function adicionarLinha() {
        let corpoTabela = document.getElementById('corpo-tabela');
        let primeiraLinha = corpoTabela.querySelector('tr');
        
        if(primeiraLinha) {
            // Destrói temporariamente o plugin para copiar o HTML limpo
            $(primeiraLinha).find('.select2-ajax').select2('destroy');
            
            let novaLinha = primeiraLinha.cloneNode(true);
            
            // Limpa os campos da nova linha
            novaLinha.querySelector('input[name="valor_kg[]"]').value = '';
            let selectProd = novaLinha.querySelector('select[name="produto_id[]"]');
            selectProd.innerHTML = ''; // Limpa as opções da linha clonada
            
            corpoTabela.appendChild(novaLinha);
            
            // Reativa o plugin em todas as linhas (a velha e a nova)
            initSelect2Ajax();
        }
    }

    function removerLinha(btn) {
        let totalLinhas = document.querySelectorAll('#corpo-tabela tr').length;
        if(totalLinhas > 1) {
            let tr = $(btn).closest('tr');
            tr.find('.select2-ajax').select2('destroy');
            tr.remove();
        } else {
            alert('A tabela precisa de pelo menos 1 item!');
        }
    }
</script>
@endsection