<div class="row">
	<div class="col-lg-6 col-12">
		<input type="hidden" id="venda_id" value="{{ $item->id }}">
		<input type="hidden" id="total_venda" value="{{ $item->valor_total }}">
		<input type="hidden" id="_token" value="{{ csrf_token() }}">
		<p>Venda <strong class="text-info">#{{ $item->codigo_venda }}</strong></p>
		<p>Cliente: 
			<strong class="text-info">
				@if($item->cliente)
				{{ $item->cliente->razao_social }}
				@elseif($item->cliente_nome != null)
				{{ $item->cliente_nome }}
				@else
				NAO IDENTIFCADO
				@endif
			</strong>
		</p>
		<p>Número: <strong class="text-info">{{ $item->numero_sequencial }}</strong></p>

		<p>Total: <strong class="text-success">R$ {{ moeda($item->valor_total) }}</strong></p>
	</div>

	<div class="col-lg-6 col-12">
		<p>Data: <strong class="text-info">{{ __date($item->created_at) }}</strong></p>
		<p>Desconto: <strong class="text-danger">R$ {{ moeda($item->desconto) }}</strong></p>
		<p>Acréscimo: <strong class="text-info">R$ {{ moeda($item->acrescimo) }}</strong></p>
	</div>

	<div class="col-12" style="border-top: 1px solid #999">
		<h5 class="mt-2">Itens da venda</h5>
		<div class="table-responsive">
			<table class="table">
				<thead>
					<tr>
						<th>Produto</th>
						<th>Quantidade</th>
						<th>Valor</th>
						<th>Subtotal</th>
					</tr>
				</thead>
				<tbody>
					@foreach($item->itens as $i)
					<tr>
						<td>{{ $i->produto->nome }}</td>
						<td>{{ number_format($i->quantidade,2) }}</td>
						<td>{{ moeda($i->valor) }}</td>
						<td>{{ moeda($i->sub_total) }}</td>
					</tr>
					@endforeach
				</tbody>
			</table>
		</div>

	</div>

	<div class="col-12" style="border-top: 1px solid #999">
		<h5 class="mt-2">Fatura</h5>
		<div class="table-responsive">
			<table class="table table-dynamic">
				<thead>
					<tr>
						<th></th>
						<th>Valor</th>
						<th>Vencimento</th>
						<th>Forma de pagamento</th>
					</tr>
				</thead>
				<tbody>
					@if(isset($item) && sizeof($item->fatura) > 0)
					@foreach($item->fatura as $f)
					<tr class="dynamic-form">
						<td>
							<button type="button" class="btn btn-sm btn-danger btn-line-delete">
								<i class="la la-trash"></i>
							</button>
						</td>
						<td>
							<input name="valor_parcela[]" placeholder="Valor" type="text" value="{{moeda($f->valor)}}" class="form-control money valor_parcela">
						</td>
						<td>
							<input name="vencimento_parcela[]" placeholder="Vencimento da parcela" value="{{($f->data_vencimento)}}" type="date" class="form-control">
						</td>
						<td>
							{{$f->tipo_pagamento}}
							<select class="custom-select" name="forma_pagamento_parcela[]">
								@foreach(App\Models\VendaBalcao::tiposPagamento() as $key => $tp)
								<option @if($f->forma_pagamento == $tp) selected @endif value="{{$key}}">{{$tp}}</option>
								@endforeach
							</select>
						</td>
					</tr>
					@endforeach
					@else
					<tr class="dynamic-form">
						<td>
							<button type="button" class="btn btn-sm btn-danger btn-line-delete">
								<i class="la la-trash"></i>
							</button>
						</td>
						<td>
							<input name="valor_parcela[]" placeholder="Valor" type="text" class="form-control money valor_parcela" value="{{ moeda($item->valor_total) }}">
						</td>
						<td>
							<input name="vencimento_parcela[]" placeholder="Vencimento da parcela" type="date" class="form-control vencimento" value="{{ date('Y-m-d') }}">
						</td>
						<td>
							<select class="custom-select forma_pagamento" name="forma_pagamento_parcela[]">
								@foreach(App\Models\VendaBalcao::tiposPagamento() as $key => $tp)
								<option value="{{$key}}">{{$tp}}</option>
								@endforeach
							</select>
						</td>
					</tr>
					@endif
				</tbody>
			</table>
		</div>
		<div class="row col-12">
			<button type="button" class="btn btn-info btn-sm btn-clone-tbl">
				<i class="la la-plus"></i> Adicionar parcela
			</button>
		</div>
	</div>

	<div class="form-group col-lg-4 col-md-4 col-sm-6">
		<label class="col-form-label">Natureza de Operação</label>
		<div class="">
			<div class="input-group date">
				<select class="custom-select form-control" id="natureza_id">
					<option value="">selecione</option>
					@foreach($naturezas as $n)
					<option value="{{$n->id}}">{{$n->natureza}}</option>
					@endforeach
				</select>
			</div>
		</div>
	</div>
	@if($item->cliente_id == null)
	<p class="col-12 text-danger">
		<i class="la la-warning text-danger"></i> não é possível emitir NFe de uma venda sem cliente
	</p>
	<p class="col-12 text-danger">
		<i class="la la-warning text-danger"></i> não é possível gerar um pedido de uma venda sem cliente
	</p>
	@endif
	<div class="col-12"></div>
	
	@if($item->cliente_id == null)
	<div class="col-lg-4 col-12">
		<button class="btn btn-dark w-100 btn-lg" disabled>
			Finalizar e Gerar Pedido
		</button>
	</div>
	<div class="col-lg-4 col-12">
		<button class="btn btn-success w-100 btn-lg" disabled>
			Finalizar e Gerar NFe
		</button>
	</div>
	@else
	<div class="col-lg-4 col-12">
		<button class="btn btn-dark w-100 btn-finish btn-lg" type="button" id="btn-finalizar">
			Finalizar e Gerar Pedido
		</button>
	</div>
	<div class="col-lg-4 col-12">
		<button class="btn btn-success w-100 btn-finish btn-lg" type="button" id="btn-finalizar-nfe">
			Finalizar e Gerar NFe
		</button>
	</div>
	@endif

	<div class="col-lg-4 col-12">
		<button class="btn btn-info w-100 btn-finish btn-lg" type="button" id="btn-finalizar-nfce">
			Finalizar e Gerar NFCe
		</button>
	</div>
</div>