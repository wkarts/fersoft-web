@extends('default.layout', ['title' => 'Retenções'])
@section('content')

<style>
    .text-right { text-align: right !important; }
    .select2-container { width: 100% !important; }
</style>

<div class="card card-custom gutter-b">
    <div class="card-body">
        <div class="" style="margin-left: 10px; margin-right: 10px;">

            {{-- Filtro de Pesquisa --}}
            <form method="get" action="">
                <div class="row">
                    <div class="form-group col-lg-2">
                        <label>Matriz/Filial</label>
                        {{-- O name "filial_id" deve bater com o Controller --}}
                        <select name="filial_id" class="form-control">
                            <option value="todos" {{ ($filial_id ?? request('filial_id')) === 'todos' ? 'selected' : '' }}>Todas</option>
                            <option value="matriz" {{ ($filial_id ?? request('filial_id')) === 'matriz' ? 'selected' : '' }}>Matriz</option>
                            @foreach($empresas as $emp)
                                {{-- AQUI FOI CORRIGIDO: Usando 'descricao' ou 'razao_social' em vez de 'nome' --}}
                                <option value="{{ $emp->id }}" {{ request()->filial_id == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->descricao ?? $emp->razao_social ?? 'Filial '.$emp->id }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-lg-3">
                        <label>Fornecedor</label>
                        <input type="text" name="fornecedor" class="form-control" value="{{ request()->fornecedor }}" />
                    </div>
                    <div class="form-group col-lg-2">
                        <label>Data início (Emissão)</label>
                        <input type="date" name="data_inicio" class="form-control" value="{{ request()->data_inicio }}" />
                    </div>
                    <div class="form-group col-lg-2">
                        <label>Data final (Emissão)</label>
                        <input type="date" name="data_final" class="form-control" value="{{ request()->data_final }}" />
                    </div>
                    <div class="col-lg-2">
                        <br><button style="margin-top: 17px;" class="btn btn-light-primary px-6 font-weight-bold">Filtrar</button>
                        <a href="{{ route('retencoes.index') }}" style="margin-top: 17px;" class="btn btn-light-danger px-6 font-weight-bold">Limpar</a>
                    </div>
                </div>
            </form>

            {{-- Painel de Fechamento Consolidado --}}
            <div class="card card-custom bg-light-secondary mb-5 border">
                <div class="card-body py-4">
                    <h6 class="font-weight-bold mb-4"><i class="la la-compress-arrows-alt"></i> Gerar Guias Consolidadas (Agrupar por Emissão)</h6>
                    <div class="row">
                        <div class="form-group col-md-2">
                            <label>Mês/Ano Emissão</label>
                            <div class="input-group">
                                <select id="mes_fechamento" class="form-control select2">
                                    @foreach(['01'=>'Jan','02'=>'Fev','03'=>'Mar','04'=>'Abr','05'=>'Mai','06'=>'Jun','07'=>'Jul','08'=>'Ago','09'=>'Set','10'=>'Out','11'=>'Nov','12'=>'Dez'] as $m => $n)
                                        <option value="{{ $m }}" {{ date('m') == $m ? 'selected' : '' }}>{{ $n }}</option>
                                    @endforeach
                                </select>
                                <input type="number" id="ano_fechamento" class="form-control" value="{{ date('Y') }}">
                            </div>
                        </div>

                        <div class="form-group col-md-3">
                            <label>PCC (PIS/COF/CSLL)</label>
                            <select id="fornecedor_pcc_id" class="form-control select2">
                                <option value="">Selecione...</option>
                                @foreach($fornecedores as $f) <option value="{{ $f->id }}">{{ $f->razao_social }}</option> @endforeach
                            </select>
                            <select id="categoria_pcc_id" class="form-control mt-1">
                                <option value="">Categoria PCC...</option>
                                @foreach($categorias as $c) <option value="{{ $c->id }}">{{ $c->nome }}</option> @endforeach
                            </select>
                        </div>

                        <div class="form-group col-md-3">
                            <label>IRRF (Imposto de Renda)</label>
                            <select id="fornecedor_ir_id" class="form-control select2">
                                <option value="">Selecione...</option>
                                @foreach($fornecedores as $f) <option value="{{ $f->id }}">{{ $f->razao_social }}</option> @endforeach
                            </select>
                            <select id="categoria_ir_id" class="form-control mt-1">
                                <option value="">Categoria IRRF...</option>
                                @foreach($categorias as $c) <option value="{{ $c->id }}">{{ $c->nome }}</option> @endforeach
                            </select>
                        </div>

                        <div class="form-group col-md-2">
                            <label>ISS (Prefeitura)</label>
                            <select id="fornecedor_iss_id" class="form-control select2">
                                <option value="">Selecione...</option>
                                @foreach($fornecedores as $f) <option value="{{ $f->id }}">{{ $f->razao_social }}</option> @endforeach
                            </select>
                            <select id="categoria_iss_id" class="form-control mt-1">
                                <option value="">Categoria ISS...</option>
                                @foreach($categorias as $c) <option value="{{ $c->id }}">{{ $c->nome }}</option> @endforeach
                            </select>
                        </div>

                        <div class="col-md-2"><br>
                            <button onclick="processarFechamentoMensal()" class="btn btn-success btn-block font-weight-bold mt-2">Gerar Guias</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th width="30"><input type="checkbox" id="select-all"></th>
                            <th>Fornecedor</th>
                            <th>Data Emissão</th>
                            <th class="text-right">Vl Bruto</th>
                            <th class="text-right">INSS</th>
                            <th class="text-right">ISS</th>
                            <th class="text-right">PIS</th>
                            <th class="text-right">COFINS</th>
                            <th class="text-right">IR</th>
                            <th class="text-right text-primary">CSLL</th>
                            <th class="text-right">Outras</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data as $item)
                        <tr>
                            <td><input type="checkbox" class="check-item" value="{{ $item->id }}"></td>
                            <td>{{ $item->fornecedor->razao_social }}</td>
                            <td>{{ __date($item->data_emissao) }}</td>
                            <td class="text-right font-weight-bold">{{ moeda($item->valor_integral) }}</td>
                            <td class="text-right">{{ moeda($item->valor_inss) }}</td>
                            <td class="text-right">{{ moeda($item->valor_iss) }}</td>
                            <td class="text-right">{{ moeda($item->valor_pis) }}</td>
                            <td class="text-right">{{ moeda($item->valor_cofins) }}</td>
                            <td class="text-right">{{ moeda($item->valor_ir) }}</td>
                            <td class="text-right">{{ moeda($item->valor_csll) }}</td>
                            <td class="text-right">{{ moeda($item->outras_retencoes) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    @php
                        $t_bruto = $data->sum('valor_integral');
                        $t_ret = $data->sum('valor_inss') + $data->sum('valor_iss') + $data->sum('valor_pis') + $data->sum('valor_cofins') + $data->sum('valor_ir') + $data->sum('valor_csll') + $data->sum('outras_retencoes');
                    @endphp
                    <tfoot>
                        <tr class="bg-light">
                            <td colspan="2"><strong>TOTAIS</strong></td>
                            <td class="text-right"><strong>{{ moeda($t_bruto) }}</strong></td>
                            <td class="text-right">{{ moeda($data->sum('valor_inss')) }}</td>
                            <td class="text-right">{{ moeda($data->sum('valor_iss')) }}</td>
                            <td class="text-right">{{ moeda($data->sum('valor_pis')) }}</td>
                            <td class="text-right">{{ moeda($data->sum('valor_cofins')) }}</td>
                            <td class="text-right">{{ moeda($data->sum('valor_ir')) }}</td>
                            <td class="text-right">{{ moeda($data->sum('valor_csll')) }}</td>
                            <td class="text-right">{{ moeda($data->sum('outras_retencoes')) }}</td>
                        </tr>
                        <tr>
                            <td colspan="11" class="text-right py-4">
                                <strong>Retenções Abatidas: <span class="text-danger">{{ moeda($t_ret) }}</span> | Valor Líquido Total Pago: <span class="text-success">{{ moeda($t_bruto - $t_ret) }}</span></strong>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Botão Imprimir PDF --}}
            <div class="mt-4">
                <form method="get" action="{{ route('retencoes.print') }}" target="_blank">
                    <input type="hidden" name="data_inicio" value="{{ request()->data_inicio }}">
                    <input type="hidden" name="data_final" value="{{ request()->data_final }}">
                    <input type="hidden" name="fornecedor" value="{{ request()->fornecedor }}">
                    <button class="btn btn-dark"><i class="la la-print"></i> Imprimir Relatório PDF</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
    $(document).ready(function() {
        // Inicializa o Select2 para os campos de formulário (opcional)
        $('.select2').select2({ width: '100%' });

        // 1. Funcionalidade Marcar Todos (Corrigido dentro do document.ready)
        $('#select-all').click(function() {
            $('.check-item').prop('checked', this.checked);
        });
    }); // Faltava fechar as chaves do document.ready aqui!

    // 2. Processamento dos IDs selecionados
    function processarFechamentoMensal() {
        let idsSelecionados = [];
        $('.check-item:checked').each(function() {
            idsSelecionados.push($(this).val());
        });

        if(idsSelecionados.length === 0) {
            swal("Atenção", "Selecione ao menos um lançamento na tabela.", "warning");
            return;
        }

        const dados = {
            _token: '{{ csrf_token() }}',
            ids: idsSelecionados, // Array de IDs
            mes: $('#mes_fechamento').val(),
            ano: $('#ano_fechamento').val(),
            fornecedor_pcc_id: $('#fornecedor_pcc_id').val(),
            categoria_pcc_id: $('#categoria_pcc_id').val(),
            fornecedor_ir_id: $('#fornecedor_ir_id').val(),
            categoria_ir_id: $('#categoria_ir_id').val(),
            fornecedor_iss_id: $('#fornecedor_iss_id').val(),
            categoria_iss_id: $('#categoria_iss_id').val(),
        };

        swal({ 
            title: "Gerar Guias Consolidadas?",
            text: "Serão geradas guias para os " + idsSelecionados.length + " itens selecionados.",
            icon: "info", 
            buttons: ["Cancelar", "Gerar Agora"]
        }).then(confirm => {
            if(confirm) {
                $.post('{{ route("compras.fecharMesRetencoes") }}', dados)
                .done(res => { swal("Sucesso", res, "success").then(() => location.reload()); })
                .fail(err => { swal("Erro", err.responseText, "error"); });
            }
        });
    }
</script>
@endsection
