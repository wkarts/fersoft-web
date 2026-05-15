@extends('default.layout')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1>Geração de Compra em Lote</h1>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-primary card-outline">
                <form action="{{ route('compras.lote.importar') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">
                        
                        {{-- LINHA 1 --}}
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label>Arquivo Excel</label>
                                <input type="file" name="file" class="form-control" required>
                            </div>
                            <div class="col-md-5 form-group">
                                <label class="font-weight-bold">Produto Padrão <span class="text-danger">*</span></label>
                                <select name="produto_id" class="form-control select2" id="kt_select2_1" style="width: 100%" required>
                                    <option value="">Selecione...</option>
                                    @foreach($produtos as $p)
                                        <option value="{{ $p->id }}">
                                            [{{ $p->id }}] {{ $p->referencia != '' && $p->referencia != null ? '[' . $p->referencia . '] ' : '' }} - {{ $p->nome }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            {{-- Ajustado para col-md-3 para fechar a grade de 12 certinho --}}
                            <div class="col-md-3 form-group">
                                <label>Preço Unitário (Custo)</label>
                                <input type="text" name="preco_unitario" class="form-control money" placeholder="0,00" required>
                            </div>
                        </div>

                        {{-- LINHA 2 --}}
                        <div class="row mt-3">
                            <div class="col-md-2 form-group">
                                <label>Data Retroativa</label>
                                <input type="date" name="data_retroativa" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-2 form-group">
                                <label>Data de Saída</label>
                                <input type="date" name="data_saida" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            {{-- Ajustado para col-md-4 para espalhar igual --}}
                            <div class="col-md-4 form-group">
                                <label>Conta de Saída</label>
                                <select name="conta_id" class="form-control select2" id="kt_select2_2" style="width: 100%" required>
                                    <option value="">Selecione...</option>
                                    @foreach($contas as $c)
                                        <option value="{{ $c->id }}">{{ $c->nome }}</option>
                                    @endforeach
                                </select>
                            </div>
                            {{-- Ajustado para col-md-4 para espalhar igual --}}
                            <div class="col-md-4 form-group">
                                <label>Categoria Financeira</label>
                                <select name="categoria_id" class="form-control select2" id="kt_select2_3" style="width: 100%" required>
                                    <option value="">Selecione...</option>
                                    @foreach($categorias as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->nome }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div> {{-- AQUI ESTAVA FALTANDO FECHAR A DIV! ISSO QUEBRAVA TUDO --}}

                        {{-- LINHA 3 --}}
                        <div class="row mt-3">
                            {{-- Ajustados todos para col-md-4 (4 + 4 + 4 = 12) com seus IDs únicos --}}
                            <div class="col-md-4 form-group">
                                <label>Natureza de Operação</label>
                                <select name="natureza_id" class="form-control select2" id="kt_select2_4" style="width: 100%" required>
                                    <option value="">Selecione...</option>
                                    @foreach($naturezas as $n)
                                        <option value="{{ $n->id }}">{{ $n->natureza }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label class="font-weight-bold">Destino da Importação (Local) <span class="text-danger">*</span></label>
                                <select name="filial_id" class="form-control select2" id="kt_select2_5" style="width: 100%" required>
                                    <option value="matriz">Matriz</option>
                                    @foreach($filiais as $f)
                                        <option value="{{ $f->id }}">{{ $f->descricao ?? $f->nome }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Tipo de Pagamento</label>
                                {{-- Trocamos o ID de kt_select2_6 para select_tipo_pagamento --}}
                                <select name="tipo_pagamento_nfe" class="form-control select2" id="select_tipo_pagamento" style="width: 100%" required>
                                    <option value="">Selecione...</option>
                                    <option value="01">01 - Dinheiro</option>
                                    <option value="17">17 - PIX</option>
                                    <option value="15">15 - Boleto Bancário</option>
                                    <option value="03">03 - Cartão de Crédito</option>
                                    <option value="99">99 - Outros</option>
                                </select>
                            </div>
                        </div>

                        {{-- LINHA 4 --}}
                        <div class="row mt-3">
                            <div class="col-md-12 form-group">
                                <label>Observação (NF-e)</label>
                                <textarea name="observacao" class="form-control" rows="2"></textarea>
                            </div>
                        </div>

                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-success float-right px-5">Processar e Enviar Notas</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>
@endsection

@section('javascript')
<script>
    $(document).ready(function() {
        // Inicializa o Select2 para todos os selects que tiverem a classe
        $('.select2').select2({
            theme: 'bootstrap4'
        });

        // Máscara de preço
        if($.fn.mask) {
            $('.money').mask('#.##0,00', {reverse: true});
        }
    });
</script>
@endsection