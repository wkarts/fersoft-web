@extends('default.layout')
@section('content')
<style type="text/css">
	#focus-codigo:hover { cursor: pointer; }

	/* Caixinha de pesquisa original do seu sistema */
	.search-prod {
		position: absolute;
		top: 100%;
		left: 0;
		width: 100%;
		max-height: 200px;
		overflow: auto;
		z-index: 9999;
		border: 1px solid #eeeeee;
		border-radius: 4px;
		background-color: #fff;
		box-shadow: 0px 1px 6px 1px rgba(0, 0, 0, 0.4);
	}

	.search-prod label:hover { cursor: pointer; background-color: #f3f6f9; color: #3699FF !important; }
	.search-prod label {
		padding: 10px;
		width: 100%;
		margin: 0;
		font-size: 14px;
		color: #000 !important;
		border-bottom: 1px solid #f1f1f1;
		display: block;
	}
</style>

<div class="card card-custom gutter-b">
    <div class="card-header">
        <h3 class="card-title">Novo Apontamento / Ajuste Múltiplo</h3>
    </div>
    
    <div class="card-body">
        <form method="post" action="/estoque/ajustes" id="form-ajuste">
            @csrf
            
            <div class="row mb-8">
                <div class="col-md-3">
                    <label class="font-weight-bold">Data da Movimentação</label>
                    <input type="date" class="form-control" name="data_ref" value="{{ date('Y-m-d') }}" required>
                </div>
                
                <div class="col-md-4">
                    {!! __view_locais_select() !!}
                </div>
                
                <div class="col-md-5">
                    <label class="font-weight-bold">Observação / Motivo Geral</label>
                    <input type="text" class="form-control" name="observacao" value="{{ old('observacao') }}" placeholder="Ex: Ajuste de inventário" required>
                </div>
            </div>

            <div class="separator separator-dashed my-8"></div>

            <h5 class="text-dark font-weight-bold mb-5">Adicionar Produtos</h5>
            <div class="row align-items-end bg-light p-5 rounded mb-8">
                
                <div class="form-group mb-0 col-md-5" style="position: relative;">
                    <label class="font-weight-bold">Produto</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="focus-codigo">
                                <i class="la la-barcode"></i>
                            </span>
                        </div>
                        <input type="hidden" id="produto_id_temp">
                        <input placeholder="Digite para buscar o produto..." type="search" id="produto-search" class="form-control" autocomplete="off">
                        
                        <div class="search-prod" style="display: none;"></div>
                    </div>
                </div>

                <div class="form-group mb-0 col-md-3">
                    <label class="font-weight-bold">Tipo</label>
                    <select class="form-control" id="tipo_temp">
                        <option value="saida">Saída (Baixa / Redução)</option>
                        <option value="entrada">Entrada (Sobra / Incremento)</option>
                    </select>
                </div>

                <div class="form-group mb-0 col-md-2">
                    <label class="font-weight-bold">Quantidade</label>
                    <input type="text" id="qtd_temp" class="form-control" placeholder="0">
                </div>

                <div class="form-group mb-0 col-md-2">
                    <button type="button" class="btn btn-primary btn-block" onclick="adicionarNaTabela()">
                        <i class="la la-plus"></i> Incluir
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-vertical-center" id="tabela-itens">
                    <thead class="thead-light">
                        <tr>
                            <th>Produto</th>
                            <th width="150">Tipo</th>
                            <th width="150">Quantidade</th>
                            <th width="80" class="text-center">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        </tbody>
                </table>
            </div>

            <div class="card-footer text-right border-0 pt-5 mt-5 px-0">
                <a href="/estoque/ajustes" class="btn btn-secondary mr-2">Cancelar</a>
                <button type="submit" class="btn btn-success">Salvar Apontamentos</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('javascript')
<script type="text/javascript">

    // Var original do seu sistema (se não existir, usa a raiz)
    let basePath = typeof path !== 'undefined' ? path : '/';

    // 1. Busca Dinâmica de Produtos (Copiado exatamente do seu código original)
    $('#produto-search').keyup((e) => {
        let pesquisa = $('#produto-search').val();
        
        // Verifica o campo de filial gerado pelo seu helper __view_locais_select()
        // O helper costuma criar um select com id="filial_id" ou "local_id"
        let filial_id = $('#filial_id').val() || $('#local_id').val(); 
        
        if(pesquisa.length > 1){
            montaAutocomplete(pesquisa, filial_id, (res) => {
                if(res && res.length > 0){
                    montaHtmlAutoComplete(res, (html) => {
                        $('.search-prod').html(html).show();
                    });
                } else {
                    $('.search-prod').hide();
                }
            });
        } else {
            $('.search-prod').hide();
        }
    });

    function montaAutocomplete(pesquisa, filial_id, call){
        $.get(basePath + 'produtos/autocomplete', {pesquisa: pesquisa, filial_id: filial_id})
        .done((res) => { call(res); })
        .fail((err) => { call([]); });
    }

    function montaHtmlAutoComplete(arr, call){
        let html = '';
        arr.map((rs) => {
            let p = rs.nome;
            if(rs.referencia) p += ' | REF: ' + rs.referencia;
            if(parseFloat(rs.estoqueAtual) > 0) p += ' | Est: ' + rs.estoqueAtual;
            
            // Corrige o onclick para mandar nome com escape para não quebrar
            html += `<label onclick="selecionarProdutoNaBusca(${rs.id}, '${p.replace(/'/g, "\\'")}')">${p}</label>`;
        });
        call(html);
    }

    function selecionarProdutoNaBusca(id, texto){
        $('#produto_id_temp').val(id);
        $('#produto-search').val(texto);
        $('.search-prod').hide();
    }

    // Fecha a busca se clicar fora
    $(document).click(function(event) { 
        if(!$(event.target).closest('.input-group').length) {
            $('.search-prod').hide();
        }        
    });

    // 2. Rotina de Adicionar na Tabela Nova
    let itemIndex = 0;

    function adicionarNaTabela() {
        let idProduto = $('#produto_id_temp').val();
        let nomeProduto = $('#produto-search').val();
        let tipo = $('#tipo_temp').val();
        let tipoNome = tipo === 'entrada' ? 'Entrada (Incremento)' : 'Saída (Redução)';
        let badgeClass = tipo === 'entrada' ? 'success' : 'danger';
        
        // Pega a quantidade, trocando vírgula por ponto para validar corretamente
        let qtdString = $('#qtd_temp').val().replace(',', '.');
        let qtd = parseFloat(qtdString);

        if (!idProduto) {
            swal("Atenção", "Pesquise e clique em um produto da lista.", "warning");
            return;
        }

        if (isNaN(qtd) || qtd <= 0) { 
            swal("Atenção", "Informe uma quantidade válida maior que zero.", "warning");
            return; 
        }

        let tbody = $('#tabela-itens tbody');
        let trId = 'linha_ajuste_' + itemIndex;
        
        let html = `
            <tr id="${trId}">
                <td>
                    <input type="hidden" name="itens[${itemIndex}][produto_id]" value="${idProduto}">
                    <span class="font-weight-bolder">${nomeProduto}</span>
                </td>
                <td>
                    <input type="hidden" name="itens[${itemIndex}][tipo]" value="${tipo}">
                    <span class="badge badge-${badgeClass} p-2">${tipoNome}</span>
                </td>
                <td class="font-weight-bold">
                    <input type="hidden" name="itens[${itemIndex}][quantidade]" value="${qtd}">
                    ${qtd}
                </td>
                <td style="display:none;">
                    <input type="hidden" name="itens[${itemIndex}][custo_unitario]" value="">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-icon btn-light-danger" onclick="$('#${trId}').remove()">
                        <i class="flaticon2-trash"></i>
                    </button>
                </td>
            </tr>
        `;

        tbody.append(html);

        // Limpa para o próximo
        $('#produto_id_temp').val('');
        $('#produto-search').val('');
        $('#qtd_temp').val('');
        $('#produto-search').focus();
        
        itemIndex++;
    }
</script>
@endsection