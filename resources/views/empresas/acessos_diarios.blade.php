@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<div class="card">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">

							<h3 class="card-title">Acessos de hoje</h3>
						</div>
					</div>

					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">
									<canvas id="grafico-acessos"></canvas>
								</div>
							</div>
						</div>

					</div>
				</div>
				<hr>
				<div class="card mt-2 mb-2">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">

							<h3 class="card-title">Acessos da última semana</h3>
						</div>
					</div>
					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">
									<canvas id="grafico-semana"></canvas>
								</div>
							</div>
						</div>

					</div>
				</div>
				<hr>
				<div class="card mt-2 mb-2">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">

							<h3 class="card-title">Acessos do último mês</h3>
						</div>
					</div>
					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">
									<canvas id="grafico-mes"></canvas>
								</div>
							</div>
						</div>

					</div>
				</div>
				<input type="hidden" id="semana" value="{{json_encode($ultimaSemana)}}">
				<input type="hidden" id="mes" value="{{json_encode($ultimoMes)}}">
			</div>
		</div>
	</div>
</div>
@endsection

@section('javascript')
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.bundle.js"></script>
<script type="text/javascript">
	const colors = ['#ff8a65', '#b9f6ca', '#26c6da', '#03a9f4', '#40c4ff', '#64ffda', '#84ffff', '#4caf50', 
	'#69f0ae', '#76ff03', '#cddc39', '#c6ff00', '#ffb74d', '#e65100', '#ff8a65', '#b9f6ca', '#26c6da', '#03a9f4', 
	'#40c4ff', '#64ffda', '#84ffff', '#4caf50', '#69f0ae', '#76ff03', '#cddc39', '#c6ff00', '#ffb74d', '#e65100',
	'#ff8a65', '#b9f6ca'];

	$(function(){
		montaAcessos()
		montaSemana()
		montaMes()
	})
	function montaAcessos(){
		let dados = JSON.parse('{{json_encode($data)}}')

		var ctx = $('#grafico-acessos');
		var myChart = new Chart(ctx, {
			type: "bar",
			data: {

				labels: constroiLabel(dados),
				datasets: [{
					label: 'Total',
					backgroundColor: constroiColor(dados),
					borderColor: '#565',
					data: constroiData(dados),
				}]
			},
			options: {

				legend: {
					display: false
				},

			}
		});
	}

	function montaSemana(){
		let dados = JSON.parse($('#semana').val())
		console.log("dados", dados)
		var ctx = $('#grafico-semana');
		var myChart = new Chart(ctx, {
			type: "bar",
			data: {

				labels: constroiLabel2(dados),
				datasets: [{
					label: 'Total',
					backgroundColor: constroiColor(dados),
					borderColor: '#565',
					data: constroiData2(dados),
				}]
			},
			options: {

				legend: {
					display: false
				},

			}
		});
	}

	function montaMes(){
		let dados = JSON.parse($('#mes').val())
		console.log("dados", dados)
		var ctx = $('#grafico-mes');
		var myChart = new Chart(ctx, {
			type: "bar",
			data: {

				labels: constroiLabel2(dados),
				datasets: [{
					label: 'Total',
					backgroundColor: constroiColor(dados),
					borderColor: '#565',
					data: constroiData2(dados),
				}]
			},
			options: {

				legend: {
					display: false
				},

			}
		});
	}

	function constroiLabel(dados){
		let temp = [];
		dados.map((i,v) => {
			temp.push(v);
		})
		return temp;
	}

	function constroiData(dados){
		let temp = [];
		dados.map((v) => {
			temp.push(v);
		})
		return temp;
	}

	function constroiLabel2(dados){
		let temp = [];
		dados.map((i,v) => {
			console.log(i.dia)
			temp.push(i.dia);
		})
		return temp;
	}

	function constroiData2(dados){
		let temp = [];
		dados.map((i,v) => {
			console.log(i.dia)

			temp.push(i.valor);
		})
		return temp;
	}

	function constroiColor(dados){
		let temp = [];
		let cont = 0;
		dados.map((v) => {
			temp.push(colors[cont]);
			cont++;
		})
		return temp;
	}
</script>
@endsection
