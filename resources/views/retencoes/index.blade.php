@extends('default.layout', ['title' => 'Retenções'])
@section('content')
<div class="card card-custom gutter-b">
	<div class="card-body">

		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

			<form class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft" method="get" action="">
				<div class="row">
					<div class="form-group col-lg-3 col-12">
						<label class="col-form-label">Fornecedor</label>
						<input type="text" name="fornecedor" class="form-control" value="{{ request()->fornecedor }}" />
					</div>

					<div class="form-group col-lg-2 col-12">
						<label class="col-form-label">Data início</label>
						<input type="date" name="data_inicio" class="form-control" value="{{ request()->data_inicio }}" />
					</div>

					<div class="form-group col-lg-2 col-12">
						<label class="col-form-label">Data final</label>
						<input type="date" name="data_final" class="form-control" value="{{ request()->data_final }}" />
					</div>

					<div class="col-lg-2 col-xl-2 mt-2 mt-lg-0">
						<br>
						<button style="margin-top: 17px;" class="btn btn-light-primary px-6 font-weight-bold">Filtrar</button>
						<a href="{{ route('retencoes.index') }}" style="margin-top: 17px;" class="btn btn-light-danger px-6 font-weight-bold">Limpar</a>
					</div>
				</div>
			</form>

			<div class="table-responsive">
				<table class="table">
					<thead>
						<tr>
							<th>Fornecedor</th>
							<th>Data de cadastro</th>
							<th>Valor à pagar</th>
							<th>INSS</th>
							<th>ISS</th>
							<th>PIS</th>
							<th>COFINS</th>
							<th>IR</th>
							<th>Outras retenções</th>
						</tr>
					</thead>
					<tbody>
						@foreach($data as $item)
						<tr>
							<td>{{ $item->fornecedor->razao_social }}</td>
							<td>{{ __date($item->created_at) }}</td>
							<td>{{ moeda($item->valor_integral) }}</td>
							<td>{{ moeda($item->valor_inss) }}</td>
							<td>{{ moeda($item->valor_iss) }}</td>
							<td>{{ moeda($item->valor_pis) }}</td>
							<td>{{ moeda($item->valor_cofins) }}</td>
							<td>{{ moeda($item->valor_ir) }}</td>
							<td>{{ moeda($item->outras_retencoes) }}</td>
						</tr>
						@endforeach
                        @php
                            $total_integral = $data->sum('valor_integral');
                            $total_retencoes = $data->sum('valor_inss') + $data->sum('valor_iss') + $data->sum('valor_pis') + $data->sum('valor_cofins') + $data->sum('valor_ir') + $data->sum('outras_retencoes');
                            $total_liquido = $total_integral - $total_retencoes;
                            if ($total_liquido < 0) $total_liquido = 0;
                        @endphp
                        <tfoot>
                        <tr>
                            <td class="b-top" colspan="2"><strong>Total</strong></td>
                            <td class="b-top">{{ moeda($data->sum('valor_integral')) }}</td>
                            <td class="b-top">{{ moeda($data->sum('valor_inss')) }}</td>
                            <td class="b-top">{{ moeda($data->sum('valor_iss')) }}</td>
                            <td class="b-top">{{ moeda($data->sum('valor_pis')) }}</td>
                            <td class="b-top">{{ moeda($data->sum('valor_cofins')) }}</td>
                            <td class="b-top">{{ moeda($data->sum('valor_ir')) }}</td>
                            <td class="b-top">{{ moeda($data->sum('outras_retencoes')) }}</td>
                        </tr>
                        <tr>
                            <td colspan="9" class="b-top">
                                <strong>Retenções Abatidas: {{ moeda($total_retencoes) }} &nbsp; | &nbsp; Valor Líquido Total Pago: {{ moeda($total_liquido) }}</strong>
                            </td>
                        </tr>
                        </tfoot>

                    </tbody>
				</table>
			</div>

			<div class="d-flex justify-content-between align-items-center flex-wrap">
				<div class="d-flex flex-wrap py-2 mr-3">
					{!! $data->appends(request()->all())->links() !!}
				</div>
			</div>

			<form method="get" action="{{ route('retencoes.print') }}">
				<input type="hidden" name="data_inicio" value="{{ request()->data_inicio }}">
				<input type="hidden" name="data_final" value="{{ request()->data_final }}">
				<input type="hidden" name="fornecedor" value="{{ request()->fornecedor }}">
				<button class="btn btn-dark">
					<i class="la la-print"></i> Imprimir
				</button>
			</form>
		</div>
	</div>
</div>
@endsection
