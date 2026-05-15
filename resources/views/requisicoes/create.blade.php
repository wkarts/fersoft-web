@extends('default.layout')

@section('content')
<div class="card card-custom">
    <div class="card-header bg-light">
        <h3 class="card-title text-dark font-weight-bolder">Nova Requisição de Material / EPI</h3>
    </div>
    
    <form action="{{ route('requisicoes.store') }}" method="POST" id="form-requisicao">
        @csrf
        <div class="card-body">
            
            <div class="row mb-8">
                <div class="col-md-4">
                    <label class="font-weight-bold">Funcionário Beneficiário <span class="text-danger">*</span></label>
                    <select name="funcionario_id" class="form-control" required>
                        <option value="">Selecione o funcionário...</option>
                        @foreach($funcionarios as $f)
                            <option value="{{ $f->id }}">{{ $f->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="font-weight-bold">Técnico Responsável <span class="text-danger">*</span></label>
                    <select name="tecnico_id" class="form-control" required>
                        <option value="">Selecione o Técnico de Segurança...</option>
                        @foreach($funcionarios as $t)
                            <option value="{{ $t->id }}">{{ $t->nome }}</option>
                        @endforeach
                    </select>
                </div>
                  <div class="col-md-3">
                      <label>Data da Entrega (Opcional)</label>
                      <input type="date" name="data_requisicao" class="form-control" value="{{ date('Y-m-d') }}">
                  </div>
              
              
                <div class="col-md-4">
                    <label class="font-weight-bold">Observação</label>
                    <input type="text" name="observacao" class="form-control">
                </div>
            </div>

            <div class="separator separator-dashed my-8"></div>

            <h5 class="text-dark font-weight-bold mb-5">Adicionar Equipamentos</h5>
            <div class="row align-items-end bg-light-primary p-5 rounded mb-8">
                <div class="col-md-3">
                    <label>Produto / EPI</label>
                    <select id="prod_temp" class="form-control custom-select">
                        <option value="">Selecione o produto...</option>
                        @foreach($produtos as $p)
                            @php 
                                // Verifica se o produto possui registro na tabela estoques, se não, saldo é 0
                                $saldo = $p->estoque ? $p->estoque->quantidade : 0; 
                            @endphp
                            
                            <option value="{{ $p->id }}" data-nome="{{ $p->nome }}" data-estoque="{{ $saldo }}">
                                {{ $p->nome }} (Estoque: {{ number_format($saldo, 2, ',', '.') }})
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label>Nº C.A.</label>
                    <input type="text" id="ca_temp" class="form-control" placeholder="Ex: 13281">
                </div>

                <div class="col-md-2">
                    <label>Uso</label>
                    <select id="uso_temp" class="form-control custom-select">
                        <option value="1">1 - Eventual</option>
                        <option value="2" selected>2 - Permanente</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label>Motivo</label>
                    <select id="motivo_temp" class="form-control custom-select">
                        <option value="A">A - Admissão</option>
                        <option value="S" selected>S - Substituição</option>
                        <option value="D">D - Dolo</option>
                        <option value="E">E - Eventual</option>
                        <option value="P">P - Permanente</option>
                    </select>
                </div>

                <div class="col-md-1">
                    <label>Qtd</label>
                    <input type="number" id="qtd_temp" class="form-control" step="0.01" min="0.01" placeholder="0">
                </div>

                <div class="col-md-2">
                    <button type="button" class="btn btn-primary btn-block font-weight-bolder" onclick="adicionarItem()">
                        Incluir
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-vertical-center" id="tabela-itens">
                    <thead class="thead-light">
                        <tr>
                            <th>Produto</th>
                            <th width="120">Nº C.A.</th>
                            <th width="100">Uso</th>
                            <th width="120">Motivo</th>
                            <th width="80">Qtde</th>
                            <th width="80" class="text-center">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer text-right">
            <a href="{{ route('requisicoes.index') }}" class="btn btn-secondary font-weight-bold mr-2">Cancelar</a>
            <button type="submit" class="btn btn-success font-weight-bold">Finalizar Requisição</button>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    function adicionarItem() {
        let selectProd = $('#prod_temp');
        let id = selectProd.val();

        if (!id || id === "") {
            alert("Por favor, selecione um produto.");
            return;
        }

        let option = selectProd.find('option:selected');
        let nome = option.data('nome');
        let estoque = parseFloat(option.data('estoque'));
        
        let ca = $('#ca_temp').val();
        let uso = $('#uso_temp').val();
        let motivo = $('#motivo_temp').val();
        let qtd = parseFloat($('#qtd_temp').val());

        if (isNaN(qtd) || qtd <= 0) { 
            alert("Informe uma quantidade válida."); 
            return; 
        }
        if (qtd > estoque) { 
            alert("Estoque insuficiente! Saldo atual: " + estoque); 
            return; 
        }
        if ($('#linha_' + id).length > 0) { 
            alert("Este produto já está na lista."); 
            return; 
        }

        let tr = `
            <tr id="linha_${id}">
                <td>
                    <input type="hidden" name="produtos[${id}][id]" value="${id}">
                    <input type="hidden" name="produtos[${id}][ca]" value="${ca}">
                    <input type="hidden" name="produtos[${id}][uso]" value="${uso}">
                    <input type="hidden" name="produtos[${id}][motivo]" value="${motivo}">
                    <input type="hidden" name="produtos[${id}][qtd]" value="${qtd}">
                    ${nome}
                </td>
                <td>${ca || '-'}</td>
                <td>${uso}</td>
                <td>${motivo}</td>
                <td>${qtd}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger" onclick="removerItem(${id})">X</button>
                </td>
            </tr>
        `;

        $('#tabela-itens tbody').append(tr);

        selectProd.val('').trigger('change');
        $('#ca_temp').val('');
        $('#qtd_temp').val('');
    }

    
</script>
@endsection