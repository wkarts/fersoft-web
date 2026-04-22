@extends('default.layout')
@section('content')

{{-- Inicialização de somas --}}
<?php 
    $somaValor = 0; $somaPago = 0; $somaPendente = 0; 
?>

<div class="card card-custom gutter-b">
    <div class="card-body">
        
        {{-- (GRUPO) TOPO: BOTÕES DE CADASTRO E RETENÇÕES --}}
        <div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft mb-5">
            <div class="col-12 d-flex justify-content-between align-items-center p-0">
                <a href="/contasPagar/new" class="btn btn-lg btn-success font-weight-bold">
                    <i class="fa fa-plus"></i> Nova Conta a Pagar
                </a>
                @if($comRetencoes)
                <a href="{{ route('retencoes.index') }}" class="btn btn-sm btn-dark font-weight-bold">
                    <i class="fa fa-list"></i> Lista de retenções
                </a>
                @endif
            </div>
        </div>

        {{-- (GRUPO) FILTROS DE BUSCA: Onde o usuário define o que quer ver na tela --}}
        <div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight" id="kt_user_profile_aside">
            <form method="get" action="/contasPagar/filtro">
                <div class="row align-items-center">
                    <div class="form-group col-lg-4">
                        <label class="col-form-label font-weight-bold">Fornecedor</label>
                        <select class="form-control select2" id="kt_select2_3" name="fornecedorId">
                            <option value="null">Selecione o fornecedor</option>
                            @foreach($fornecedores as $forn)
                                <option @isset($fornecedorId) @if($fornecedorId == $forn->id) selected @endif @endif value="{{$forn->id}}">
                                    {{$forn->id}} - {{$forn->razao_social}} ({{$forn->cpf_cnpj}})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-lg-2">
                        <label class="col-form-label font-weight-bold">Filtro de data</label>
                        <select class="custom-select form-control" name="tipo_filtro_data">
                            <option @if(isset($tipo_filtro_data) && $tipo_filtro_data == 1) selected @endif value="1">Vencimento</option>
                            <option @if(isset($tipo_filtro_data) && $tipo_filtro_data == 2) selected @endif value="2">Data de registro</option>
                            <option @if(isset($tipo_filtro_data) && $tipo_filtro_data == 3) selected @endif value="3">Data de pagamento</option>
                            <option @if(isset($tipo_filtro_data) && $tipo_filtro_data == 4) selected @endif value="4">Data de Emissão</option>
                        </select>
                    </div>

                    <div class="form-group col-lg-2">
                        <label class="col-form-label font-weight-bold">Data Inicial</label>
                        <input type="text" name="data_inicial" class="form-control date-input" value="{{ $dataInicial ?? '' }}" id="kt_datepicker_3" />
                    </div>

                    <div class="form-group col-lg-2">
                        <label class="col-form-label font-weight-bold">Data Final</label>
                        <input type="text" name="data_final" class="form-control date-input" value="{{ $dataFinal ?? '' }}" id="kt_datepicker_3" />
                    </div>

                    <div class="form-group col-lg-2">
                        <label class="col-form-label font-weight-bold">Estado</label>
                        <select class="custom-select form-control" name="status">
                            <option @if(isset($status) && $status == 'todos') selected @endif value="todos">TODOS</option>
                            <option @if(isset($status) && $status == 'pago') selected @endif value="pago">PAGO</option>
                            <option @if(isset($status) && $status == 'pendente') selected @endif value="pendente">PENDENTE</option>
                            <option @if(isset($status) && $status == 'vencido') selected @endif value="vencido">VENCIDO</option>
                        </select>
                    </div>

                    <div class="form-group col-lg-2">
                        <label class="col-form-label font-weight-bold">Categoria</label>
                        <select class="custom-select form-control" name="categoria">
                            <option value="todos">TODOS</option>
                            @foreach($categorias as $cat)
                                <option @if(isset($categoria) && $categoria == $cat->id) selected @endif value="{{$cat->id}}">{{$cat->nome}}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-lg-2">
                        <label class="col-form-label font-weight-bold">Tipo de Pagamento</label>
                        <select class="custom-select form-control" name="tipo_pagamento">
                            <option value="">Todos</option>
                            @foreach(App\Models\ContaPagar::tiposPagamento() as $tp)
                                <option @isset($tipo_pagamento) @if($tipo_pagamento == $tp) selected @endif @endif value="{{$tp}}">{{$tp}}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-lg-2">
                        <label class="col-form-label font-weight-bold">Nº nota fiscal</label>
                        <input type="text" name="numero_nota_fiscal" value="{{ $numero_nota_fiscal ?? '' }}" class="form-control" placeholder="Nº nota">
                    </div>

                    @if(empresaComFilial())
                        {!! __view_locais_select_filtro("Local", isset($filial_id) ? $filial_id : '') !!}
                    @endif

                    <div class="form-group col-lg-2">
                        <label class="col-form-label font-weight-bold">Veículo</label>
                        <select class="custom-select form-control" name="veiculo_id_filtro">
                            <option value="todos">TODOS</option>
                            @foreach($veiculos as $v)
                                <option @if(isset($veiculo_id_filtro) && $veiculo_id_filtro == $v->id) selected @endif value="{{$v->id}}">{{$v->placa}}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2">
                        <button type="submit" class="btn btn-light-primary btn-block font-weight-bold mt-4">Filtrar</button>
                    </div>
                </div>
            </form>

            {{-- (GRUPO) BARRA DE FERRAMENTAS: Funções extras como Seleção múltipla e Exportação --}}
            <div class="row mt-8 mb-4">
                <div class="col-12 d-flex align-items-center flex-wrap">
                    <button id="btn_seleciona_varios" class="btn btn-light-info font-weight-bold mr-2">
                        <i class="la la-list"></i> Selecionar Vários
                    </button>

                    <button style="display: none" id="btn_pagar" class="btn btn-success font-weight-bold mr-2">
                        <i class="la la-check"></i> Pagar Selecionados
                    </button>

                    <a href="{{ url('/contasPagar/syncNotaFiscal') }}" class="btn btn-light font-weight-bold mr-2">
                        <i class="fa fa-sync text-info"></i> Sincronizar NF das Compras
                    </a>

                    <a href="/contasPagar/export?{{ http_build_query(request()->all()) }}" class="btn btn-light font-weight-bold">
                        <i class="fa fa-file-excel text-success"></i> Exportar para Excel
                    </a>
                </div>
            </div>

            <h6 class="text-danger font-weight-bold">*{{ $infoDados }}</h6>
            <label>Total de registros: {{ sizeof($contas) }}</label>

            {{-- (GRUPO) TABELA PRINCIPAL: Exibição dos dados das contas --}}
            <div class="datatable datatable-bordered datatable-default datatable-primary datatable-loaded mt-4">
                <table class="datatable-table" style="max-width: 100%; overflow: scroll">
                    <thead class="datatable-head">
                        <tr class="datatable-row">
                            <th class="datatable-cell" style="width: 50px; display: none" id="th_check">
                                <span>
                                    <label class="checkbox checkbox-single checkbox-all">
                                        <input type="checkbox" id="check-todos">
                                        <span></span>
                                    </label>
                                </span>
                            </th>
                            <th class="datatable-cell"><span style="width: 160px;">AÇÃO</span></th>
                            <th class="datatable-cell"><span style="width: 180px;">FORNECEDOR</span></th>
                            <th class="datatable-cell"><span style="width: 200px;">CATEGORIA / NOTA / REF</span></th>
                            <th class="datatable-cell"><span style="width: 100px;">VALOR INT.</span></th>
                            <th class="datatable-cell"><span style="width: 100px;">VALOR PAGO</span></th>
                            <th class="datatable-cell"><span style="width: 100px;">VENCIMENTO</span></th>
                            <th class="datatable-cell"><span style="width: 80px;">ESTADO</span></th>
                            <th class="datatable-cell"><span style="width: 130px;">AUDITORIA DATAS</span></th>
                            <th class="datatable-cell"><span style="width: 170px;">USUÁRIOS (C / E / B)</span></th>
                        </tr>
                    </thead>
                    <tbody class="datatable-body" id="body">
                        @foreach($contas as $c)
                        <tr class="datatable-row">
                            {{-- Checkbox de seleção múltipla --}}
                            <td class="datatable-cell td_check" style="width: 50px; display: none">
                                <span>
                                    <label class="checkbox checkbox-single">
                                        <input type="checkbox" class="select-check" value="{{$c->id}}" data-valor="{{$c->valor_integral}}">
                                        <span></span>
                                    </label>
                                </span>
                            </td>

                            {{-- (GRUPO) BOTÕES DE AÇÃO NA LINHA: Imprimir, Editar, Excluir, Estornar --}}
<td class="datatable-cell">
    <span style="width: 160px;">
        @if(!$c->status)
            {{-- BOTÃO DE BAIXAR (PAGAR) QUE TINHA SUMIDO --}}
            <a href="/contasPagar/pagar/{{$c->id}}" class="btn btn-success btn-sm btn-icon" title="Baixar Pagamento">
                <i class="la la-check"></i>
            </a>

            <a href="/contasPagar/edit/{{$c->id}}" class="btn btn-warning btn-sm btn-icon" title="Editar"><i class="la la-edit"></i></a>
            <a onclick='swal("Remover?", "warning").then((s)=>{if(s)location.href="/contasPagar/delete/{{$c->id}}"})' 
               class="btn btn-danger btn-sm btn-icon" title="Remover">
                <i class="la la-trash"></i>
            </a>
        @else
            {{-- Botão de Estorno (Aparece se pago) --}}
            <a class="btn btn-sm btn-dark btn-icon" href="javascript:void(0)" onclick="estornarConta({{ $c->id }})" title="Estornar Pagamento">
                <i class="la la-rotate-left"></i>
            </a>
            {{-- Botão de Imprimir Recibo --}}
            <a href="/contasPagar/imprimirRecibo/{{$c->id}}" class="btn btn-sm btn-info btn-icon" target="_blank" title="Imprimir Recibo">
                <i class="la la-print"></i>
            </a>
        @endif
        
        {{-- Botão para vincular veículo --}}
        <button type="button" class="btn btn-secondary btn-sm btn-icon" data-toggle="modal" data-target="#modal_veiculo_{{$c->id}}" title="Vincular Veículo">
            <i class="la la-truck"></i>
        </button>
    </span>
</td>

                            <td class="datatable-cell"><span style="width: 180px;">{{ $c->fornecedor->razao_social ?? '--' }}</span></td>

<td class="datatable-cell">
    <span style="width: 200px; line-height: 1.4;">
        <strong class="text-dark d-block" style="text-transform: uppercase;">{{ $c->categoria->nome }}</strong>
        <small class="text-primary d-block font-weight-bold">Ref: {{ $c->referencia }}</small>
        
        {{-- ========================================== --}}
        {{-- ADICIONADO: AUDITORIA DE FILIAL / MATRIZ COM NOME --}}
        @php
            $nomeLocal = 'Matriz'; // Padrão
            if ($c->filial_id && $c->filial_id > 0) {
                $listaLocais = __locaisAtivos(); // Pega a lista do seu próprio sistema
                // Se achar o ID na lista, pega o nome. Se não achar, mostra "Filial X"
                $nomeLocal = isset($listaLocais[$c->filial_id]) ? $listaLocais[$c->filial_id] : 'Filial ' . $c->filial_id;
            }
        @endphp
        <span class="label label-inline label-light-danger font-weight-bold mt-1 mb-1" style="font-size: 11px;">
            <i class="la la-building mr-1"></i> 
            Local: {{ $nomeLocal }}
        </span>
        {{-- ========================================== --}}

        {{-- ADICIONADO: Exibição do Tipo de Pagamento --}}
        @if($c->tipo_pagamento)
            <small class="text-muted d-block mt-1"><b>Pgto:</b> {{ $c->tipo_pagamento }}</small>
        @endif
        
        {{-- BUSCA A CONTA EMPRESA LÁ NA TABELA DE ITENS (MOVIMENTAÇÃO FINANCEIRA) --}}
        @php
            // Vai na tabela de itens e procura se tem algum pagamento para esta conta
            $transacao = \App\Models\ItemContaEmpresa::where('conta_pagar_id', $c->id)->first();
            $nomeConta = null;
            
            if($transacao) {
                // Se achou a transação, busca o nome da conta
                $contaVinculada = \App\Models\ContaEmpresa::find($transacao->conta_id);
                if($contaVinculada) {
                    $nomeConta = $contaVinculada->nome;
                }
            }
        @endphp

        @if($nomeConta)
            <small class="text-info d-block"><b>Conta:</b> {{ $nomeConta }}</small>
        @endif
        {{-- FIM DA BUSCA DA CONTA --}}

        @if($c->numero_nota_fiscal)
	            @if($c->compra_id)
	                {{-- Se a conta veio de uma compra, transforma a etiqueta em um botão de impressão --}}
	                <a href="/compras/imprimir/{{ $c->compra_id }}" target="_blank" class="label label-inline label-light-primary font-weight-bold" style="font-size: 11px; margin-top: 4px; cursor: pointer; text-decoration: none;" title="Imprimir DANFE">
	                    <i class="la la-print text-primary mr-1" style="font-size: 14px;"></i> NF: {{ $c->numero_nota_fiscal }}
	                </a>
	            @else
	                {{-- Se for uma conta manual (sem compra vinculada), mantém apenas o texto --}}
	                <span class="label label-inline border border-primary text-primary font-weight-bold" style="font-size: 10px; background: none; margin-top: 2px;">NF: {{ $c->numero_nota_fiscal }}</span>
	            @endif
        @endif
        @if($c->veiculo)
            <span class="label label-inline label-light-primary font-weight-bold mt-1" style="font-size: 11px;"><i class="la la-truck"></i> {{ $c->veiculo->placa }}</span>
        @endif
    </span>
</td>

                            <td class="datatable-cell" style="text-align: right;"><span style="width: 100px;">{{ number_format($c->valor_integral, 2, ',', '.') }}</span></td>
                            <td class="datatable-cell" style="text-align: right;"><span style="width: 100px;">{{ number_format($c->valor_pago, 2, ',', '.') }}</span></td>
                            <td class="datatable-cell">
                                <span style="width: 100px;">
                                    {{ \Carbon\Carbon::parse($c->data_vencimento)->format('d/m/Y') }}
                                    @if(!$c->status) <br><small class="text-danger font-weight-bold">{{ $c->diasAtraso() }}</small> @endif
                                </span>
                            </td>
                            <td class="datatable-cell">
                                <span style="width: 80px;">
                                    {!! $c->status ? '<span class="label label-inline label-light-success font-weight-bold">Pago</span>' : '<span class="label label-inline label-light-danger font-weight-bold">Pendente</span>' !!}
                                </span>
                            </td>

                            <td class="datatable-cell">
                                <span style="width: 130px;">
                                    <div class="d-flex flex-column text-left">
                                        <small><i class="la la-file-invoice text-primary"></i> <b>E:</b> {{ $c->data_emissao ? \Carbon\Carbon::parse($c->data_emissao)->format('d/m/Y') : '--' }}</small>
                                        <small><i class="la la-clock text-muted"></i> <b>R:</b> {{ \Carbon\Carbon::parse($c->created_at)->format('d/m/Y') }}</small>
                                        @if($c->status) <small class="text-success font-weight-bold"><i class="la la-check-circle"></i> <b>P:</b> {{ \Carbon\Carbon::parse($c->data_pagamento)->format('d/m/Y') }}</small> @endif
                                    </div>
                                </span>
                            </td>

                            <td class="datatable-cell">
                                <span style="width: 170px;">
                                    <div class="d-flex flex-column text-left" style="font-size: 13px; gap: 3px;">
                                        <span class="text-primary font-weight-bolder">C: {{ $c->usuario->nome ?? 'Sist.' }}</span>
                                        <span class="text-warning font-weight-bolder">E: {{ $c->usuarioEdicao->nome ?? '--' }}</span>
                                        @if($c->status) <span class="text-success font-weight-bolder">B: {{ $c->usuarioBaixa->nome ?? '--' }}</span> @endif
                                    </div>
                                </span>
                            </td>
                        </tr>
                        <?php $somaValor += $c->valor_integral; $somaPago += $c->valor_pago; if(!$c->status) $somaPendente += $c->valor_integral; ?>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- (GRUPO) TOTAIS FINANCEIROS: Soma geral e soma do que foi selecionado --}}
            <div class="card card-custom gutter-b mt-5">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <h5 class="text-danger">A Pagar: <strong>R$ {{ number_format($somaPendente, 2, ',', '.') }}</strong></h5>
                        </div>
                        <div class="col-md-3">
                            <h5 class="text-success">Pago: <strong>R$ {{ number_format($somaPago, 2, ',', '.') }}</strong></h5>
                        </div>
                        {{-- Div que mostra o valor total das contas que o usuário marcou no checkbox --}}
                        <div class="col-md-3" id="div-valor-selecionado" style="display: none">
                            <h5 class="text-primary">Selecionado: <strong id="valor-selecionado">R$ 0,00</strong></h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- (GRUPO) MODAIS: Janelas que abrem por cima da tela (Ex: Vincular Veículo) --}}
@foreach($contas as $c)
<div class="modal fade" id="modal_veiculo_{{$c->id}}" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="{{ route('contasPagar.setVeiculo', [$c->id]) }}">
                @csrf
                @method('put')
                <div class="modal-header"><h5 class="modal-title font-weight-bold">Vincular Veículo - {{ $c->referencia }}</h5></div>
                <div class="modal-body">
                    <select name="veiculo_id" class="form-control">
                        <option value="">Nenhum</option>
                        @foreach($veiculos as $v)
                            <option @if($c->veiculo_id == $v->id) selected @endif value="{{$v->id}}">{{$v->placa}} - {{$v->modelo}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-success font-weight-bold">Salvar</button></div>
            </form>
        </div>
    </div>
</div>
@endforeach

<input type="hidden" id="casas_decimais" value="{{ $casasDecimais ?? 2 }}">

@endsection

@section('javascript')
<script type="text/javascript">
    var BTNSELECIONA = false;
    var SOMA = 0;

    // Função que ativa o modo de seleção múltipla
    $('#btn_seleciona_varios').click(function(){
        BTNSELECIONA = !BTNSELECIONA;
        if(BTNSELECIONA){
            $(this).removeClass('btn-light-info').addClass('btn-info');
            $('.td_check, #th_check, #div-valor-selecionado').show();
        }else{
            $(this).removeClass('btn-info').addClass('btn-light-info');
            $('.td_check, #th_check, #div-valor-selecionado, #btn_pagar').hide();
            $('.select-check, #check-todos').prop('checked', false);
            SOMA = 0;
            atualizaValorTexto();
        }
    });

    // Função para Marcar/Desmarcar todos os itens da tabela de uma vez
    $('#check-todos').click(function(){
        var status = $(this).prop('checked');
        $('.select-check').prop('checked', status);
        calcularSoma();
    });

    // Quando o usuário clica em um checkbox individual
    $('.select-check').change(function(){
        calcularSoma();
    });

    // Calcula o valor somado das contas marcadas
    function calcularSoma(){
        SOMA = 0;
        var selecionados = 0;
        $('.select-check:checked').each(function(){
            SOMA += parseFloat($(this).data('valor'));
            selecionados++;
        });
        
        atualizaValorTexto();
        
        // Se houver ao menos 1 selecionado, o botão de Pagar aparece
        if(selecionados >= 1) $('#btn_pagar').show();
        else $('#btn_pagar').hide();
    }

    // Formata o valor para Real (R$) e exibe no campo de Selecionado
    function atualizaValorTexto(){
        $('#valor-selecionado').html(SOMA.toLocaleString('pt-br',{style: 'currency', currency: 'BRL'}));
    }

    // Ao clicar em Pagar Selecionados, envia os IDs para a rota de pagar múltiplos
    $('#btn_pagar').click(function(){
        swal("Atenção!", "Deseja pagar as contas selecionadas?", "warning").then((sim) => {
            if(sim){
                var ids = [];
                $('.select-check:checked').each(function(){ ids.push($(this).val()); });
                location.href = '/contasPagar/pagarMultiplos/' + ids.join(',');
            }
        });
    });
  
   // Função que abre o alerta de estorno pedindo a senha
    function estornarConta(id) {
        Swal.fire({
            title: 'Estornar Pagamento?',
            text: "Digite a senha de autorização:",
            // TRUQUE ANTI-CHROME: Html customizado com readonly temporário e autocomplete desligado
            html: '<input type="password" id="senha_estorno" class="swal2-input" autocomplete="new-password" readonly onfocus="this.removeAttribute(\'readonly\');" placeholder="Senha">',
            showCancelButton: true,
            confirmButtonText: 'Confirmar Estorno',
            cancelButtonText: 'Cancelar',
            preConfirm: () => {
                const senha = document.getElementById('senha_estorno').value;
                if (!senha) { 
                    Swal.showValidationMessage('A senha é obrigatória'); 
                }
                return senha;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Formulário invisível via POST
                let form = document.createElement('form');
                form.method = 'POST';
                form.action = '/contasPagar/estorno';

                let csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';
                form.appendChild(csrfToken);

                let idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = id;
                form.appendChild(idInput);

                let senhaInput = document.createElement('input');
                senhaInput.type = 'hidden';
                senhaInput.name = 'senha';
                senhaInput.value = result.value;
                form.appendChild(senhaInput);

                document.body.appendChild(form);
                form.submit();
            }
        });
    }
</script>
@endsection