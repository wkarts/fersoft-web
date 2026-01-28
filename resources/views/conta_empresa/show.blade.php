@extends('default.layout', ['title' => 'Movimentações conta ' . $item->nome])
@section('content')
<div class="card card-custom gutter-b">
	<div class="card-body">
		<div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-12">
				<div class="card-body">
					<h3 class="">Conta: <strong></strong>{{ $item->nome }}</h3>

					<form class="row" method="get" action="{{ route('contas-empresa.show', [$item->id]) }}">
						<div class="col-md-2">
							<label>Data inicial</label>
							<input value="{{ $data_inicio }}" type="date" name="data_inicio" class="form-control">
						</div>

						<div class="col-md-2">
							<label>Data final</label>
							<input value="{{ $data_final }}" type="date" name="data_final" class="form-control">
						</div>

						<div class="col-md-2">
							<label>Tipo</label>
							<select name="tipo" class="form-control custom-select">
								<option value="">Selecione</option>
								<option @if($tipo ==  'entrada') selected @endif value="entrada">Entrada</option>
								<option @if($tipo ==  'saida') selected @endif value="saida">Saída</option>
							</select>
						</div>

						<div class="col-md-4">
							<br>
							<button class="btn btn-light-primary px-6 font-weight-bold mt-1">
								<i class="la la-search"></i>
								Filtrar
							</button>
							<a class="btn btn-warning px-6 font-weight-bold mt-1" href="{{ route('contas-empresa.show', [$item->id]) }}">
								<i class="la la-eraser"></i>
								Limpar
							</a>
						</div>
					</form>
					<br>

					@forelse($data as $m)
					<div class="row">
						<div class="col-md-2">
							{{ __date($m->created_at) }}
						</div>

						<div class="col-md-6 col-12">
							{{ $m->descricao }}
							@if($m->caixa_id)
							<br>
							Fechamento caixa abertura {{ __date($m->caixa->created_at) }}
							@endif
						</div>

						<div class="col-md-2 col-12 @if($m->tipo == 'entrada') text-success @else text-danger @endif">
							<label class="float-right">{{ $m->tipo_pagamento ? App\Models\Venda::getTipo($m->tipo_pagamento) : '' }}</label>
						</div>

						<div class="col-md-2 col-12 @if($m->tipo == 'entrada') text-success @else text-danger @endif">
							<label class="float-right">@if($m->tipo == 'entrada')+@else-@endif R$ {{ moeda($m->valor) }}</label>
						</div>
					</div>
					<div class="row">
						<div class="col-md-10">
						</div>
						<div class="col-md-2">
							<label class="float-right @if($m->saldo_atual <= 0) text-danger @else text-info @endif">
								Saldo: R$ {{ moeda($m->saldo_atual) }}
							</label>
						</div>
					</div>
					<hr>
					@empty
					<h4 class="text-center">Nenhuma movimentação encontrada!</h4>
					@endforelse
				</div>
			</div>
			<div class="col-12 m-5">
				{{$data->links()}}	
			</div>
		</div>
	</div>
</div>
@endsection