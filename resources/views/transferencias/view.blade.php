@extends('default.layout')

@section('css')
<style type="text/css">
	body.loading .modal-loading {
		display: block;
	}

	.modal-loading {
		display: none;
		position: fixed;
		z-index: 10000;
		top: 0;
		left: 0;
		height: 100%;
		width: 100%;
		background: rgba(255, 255, 255, 0.8)
		url("/loading.gif") 50% 50% no-repeat;
	}

</style>
@endsection

@section('content')
<input type="hidden" id="_token" value="{{ csrf_token() }}">
<input type="hidden" id="transferencia_id" value="{{ $item->id }}">
<div class="card card-custom gutter-b">
	<input type="hidden" id="pass" value="{{ $config->senha_remover ?? '' }}">
	<div class="card-body @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
		<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
			<div class="card card-custom gutter-b example example-compact">
				<div class="card-body">

					<div class="row">
						<div class="col-12 col-md-4">
							<h4>Origem:
								<strong>
									{{ $item->filial_saida ? $item->filial_saida->descricao : 'Matriz' }}
								</strong>
							</h4>
						</div>
						<div class="col-12 col-md-4">
							<h4>Destino:
								<strong>
									{{ $item->filial_entrada ? $item->filial_entrada->descricao : 'Matriz' }}
								</strong>
							</h4>
						</div>
						<div class="col-12 col-md-4">
							<h4>Data: <strong>{{ __date($item->created_at) }}</strong></h4>
						</div>

						<div class="col-12 col-md-4">
							<h4>Estado:
								@if($item->estado == 'novo')
								<span class="label label-xl label-inline label-light-primary">Novo</span>
								@elseif($item->estado == 'aprovado')
								<span class="label label-xl label-inline label-light-success">Aprovado</span>
								@elseif($item->estado == 'cancelado')
								<span class="label label-xl label-inline label-light-danger">Cancelado</span>
								@else
								<span class="label label-xl label-inline label-light-warning">Rejeitado</span>
								@endif
							</h4>
						</div>
					</div>

					<div class="table-responsive">
						<h5 class="ml-3 mt-5">Itens</h5>
                        <table class="table">
                            <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Quantidade</th>
                                <th>Valor Unitário</th>
                                <th>Sub Total</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($item->itens as $i)
                                <tr>
                                    <td>{{ $i->produto->nome }}</td>
                                    <td>{{ $i->quantidade }}</td>
                                    <td>R$ {{ number_format($i->valor_unitario, 2, ',', '.') }}</td>
                                    <td>R$ {{ number_format($i->sub_total, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

					<a target="_blank" href="/transferencia/print/{{ $item->id }}" class="btn btn-sm btn-info">
						<i class="la la-print"></i> imprimir
					</a>

                    @if($item->estado == 'novo')
                        <a href="javascript:void(0);" onclick="atualizarValores()" class="btn btn-sm btn-warning">
                            <i class="la la-refresh"></i> Atualizar Valores
                        </a>
                    @endif

                </div>

				<hr>
				@if($item->estado == 'novo' || $item->estado == 'rejeitado')
				<form class="card-body" method="post" action="{{ route('transferencia.update-fiscal', [$item->id]) }}">
					@csrf
					@method('put')
					<h4>Emitir NFe de transferência</h4>

					<div class="row">
						<div class="col-md-4 col-6">
							<label>Natureza de operação</label>
							<select required name="natureza_id" class="form-control custom-select">
								<option value="">Selecione</option>
								@foreach($naturezas as $nat)
								<option @if($item->natureza_id == $nat->id) selected @endif value="{{ $nat->id }}">{{ $nat->natureza }}</option>
								@endforeach
							</select>
						</div>

						<div class="col-md-2 col-6">
							<label>Finalidade</label>
							<select required name="finNFe" class="custom-select">
								<option value="">Selecione</option>
								@foreach(App\Models\Transferencia::finalidades() as $key => $f)
								<option

								@if($item->finNFe == $key)
								selected
								@endif

								value="{{$key}}">{{$f}}</option>
								@endforeach
							</select>
						</div>

                        <!--
						<div class="col-md-2 col-6">
							<label>Tipo</label>
							<select required name="tpNF" class="custom-select">
								<option value="1">Saida</option>
								<option value="0">Entrada</option>

							</select>
						</div>
                        -->

                        <div class="col-md-2 col-6">
                            <label>Tipo</label>
                            <select required name="tpNF" class="custom-select">
                                <option value="1" @if($item->tpNF == 1) selected @endif>Saída</option>
                                <option value="0" @if($item->tpNF == 0) selected @endif>Entrada</option>
                            </select>
                        </div>

						<div class="col-md-4 col-6">
							<label>Transportadora</label>
							<select name="transportadora_id" class="form-control">
								<option value="">Selecione</option>
								@foreach($transportadoras as $t)
								<option @if($item->transportadora_id == $t->id) selected @endif value="{{ $t->id }}">{{ $t->razao_social }}</option>
								@endforeach
							</select>
						</div>

						<div class="col-12 mt-2 text-right">
							<button type="submit" class="btn btn-dark btn-sm">
								<i class="la la-check"></i>
								Salvar
							</button>

							@if($item->natureza_id != null)
							<button type="button" class="btn btn-success btn-sm" id="btn-transmitir">
								<i class="la la-file"></i>
								Emitir NFe
							</button>

							<a href="{{ route('transferencia.xml-temp', [$item->id]) }}" target="_blank" class="btn btn-warning btn-sm">
								<i class="la la-file-code"></i>
								XML temporário
							</a>

							<a href="{{ route('transferencia.danfe-temp', [$item->id]) }}" target="_blank" class="btn btn-primary btn-sm">
								<i class="la la-file-pdf"></i>
								Danfe temporário
							</a>
							@endif
                            <!--
                            <button type="button" class="btn btn-info btn-sm" onclick="consultarSituacaoNFe()">
                                <i class="la la-search"></i> Consultar Situação SEFAZ
                            </button>
                            -->
						</div>

					</div>

				</form>
				@elseif($item->estado == 'aprovado')
				<div class="card-body">
					<div class="row">
						<div class="col-12 mt-2 text-right">
							<a href="{{ route('transferencia.imprimir-nfe', [$item->id]) }}" target="_blank" class="btn btn-primary btn-sm">
								<i class="la la-file-pdf"></i>
								Imprimir DANFE
							</a>

							<button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modal-corrigir">
								<i class="la la-exclamation-circle"></i>
								Corrigir NFe
							</button>

							<button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#modal-cancelar">
								<i class="la la-close"></i>
								Cancelar NFe
							</button>

							@if($item->sequencia_cce > 0)
							<a href="{{ route('transferencia.imprimir-correcao', [$item->id]) }}" target="_blank" class="btn btn-warning btn-sm">
								<i class="la la-file-pdf"></i>
								Imprimir Correção
							</a>
							@endif
                            <!--
                            <button type="button" class="btn btn-info btn-sm" onclick="consultarSituacaoNFe()">
                                <i class="la la-search"></i> Consultar Situação SEFAZ
                            </button>
                            -->
						</div>

					</div>
				</div>
				@elseif($item->estado == 'cancelado')
				<div class="card-body">
					<div class="row">
						<div class="col-12 mt-2 text-right">
							<a href="{{ route('transferencia.imprimir-cancela', [$item->id]) }}" target="_blank" class="btn btn-danger btn-sm">
								<i class="la la-file-pdf"></i>
								Imprimir Cancelamento
							</a>
						</div>

					</div>
				</div>
				@endif
            </div>
		</div>
	</div>
</div>


<div class="modal fade" id="modal-cancelar" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">CANCELAR NFe <strong class="text-danger">{{ $item->numero_nfe }}</strong></h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			<div class="modal-body">
				<div class="row">

					<div class="form-group validated col-sm-12 col-lg-12">
						<label class="col-form-label" id="">Justificativa</label>
						<div class="">
							<input type="text" id="justificativa" placeholder="Justificativa minimo de 15 caracteres" name="justificativa" class="form-control" value="">
						</div>
					</div>
				</div>

			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
				<button type="button" id="btn-cancelar-2" onclick="cancelarNfe()" class="btn btn-light-success font-weight-bold spinner-white spinner-right">Cancelar NFe</button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modal-corrigir" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">CARTA DE CORREÇÃO NFe <strong class="text-danger">{{ $item->numero_nfe }}</strong></h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="form-group validated col-sm-12 col-lg-12">
						<label class="col-form-label" id="">Correção</label>
						<div class="">
							<input type="text" id="correcao" placeholder="Correção minimo de 15 caracteres" name="correcao" class="form-control" value="">
						</div>
					</div>
				</div>

			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
				<button type="button" id="btn-corrigir-2" onclick="corrigirNfe()" class="btn btn-light-success font-weight-bold spinner-white spinner-right">Corrigir NFe</button>
			</div>
		</div>
	</div>
</div>

<div class="modal-loading loading-class"></div>

<!-- Modal Mensagem (Avisos e Erros) -->
<div class="modal fade" id="modalMensagem" tabindex="-1" role="dialog" aria-labelledby="modalMensagemLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalMensagemLabel">Mensagem</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modalMensagemTexto">
                Mensagem exibida aqui.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação -->
<div class="modal fade" id="modalConfirmacao" tabindex="-1" role="dialog" aria-labelledby="modalConfirmacaoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalConfirmacaoLabel">Confirmação</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modalConfirmacaoMensagem">
                Deseja realmente atualizar os valores desta transferência?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Não</button>
                <button type="button" id="btnConfirmarAcao" class="btn btn-primary">Sim</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('javascript')
<script type="text/javascript" src="/js/nfe_transferecia.js"></script>

<script>
    function atualizarValores() {
        $('#modalConfirmacaoMensagem').html(
            `<p style="font-size:14px;">
            Esta operação irá <strong>atualizar automaticamente</strong> os valores dos produtos desta transferência.<br><br>
            ➔ O <strong>Valor Unitário</strong> será atualizado com base no <strong>preço de compra atual</strong> de cada produto.<br>
            ➔ O <strong>Subtotal</strong> será recalculado conforme a nova quantidade × novo valor unitário.<br><br>
            Tem certeza que deseja continuar?
        </p>`
        );

        $('#modalConfirmacao').modal('show');

        $('#btnConfirmarAcao').off('click').on('click', function() {
            $('#modalConfirmacao').modal('hide');
            $('body').addClass('loading');

            $.ajax({
                url: '/transferencia/atualizar-valores/' + $('#transferencia_id').val(),
                method: 'GET',
                success: function(response) {
                    $('body').removeClass('loading');
                    $('#modalMensagemTexto').html(`<p>Valores atualizados com sucesso!</p>`);
                    $('#modalMensagem').modal('show');
                    $('#modalMensagem').on('hidden.bs.modal', function() {
                        location.reload();
                    });
                },
                error: function() {
                    $('body').removeClass('loading');
                    $('#modalMensagemTexto').html(`<p>Ocorreu um erro ao tentar atualizar os valores!</p>`);
                    $('#modalMensagem').modal('show');
                }
            });
        });
    }
</script>

@endsection
