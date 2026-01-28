const initModeloGrafico = 'line';
const initModeloGrafico2 = 'bar';
const colors = ['#ff8a65', '#b9f6ca', '#26c6da', '#03a9f4', '#40c4ff', '#64ffda', '#84ffff', '#4caf50',
'#69f0ae', '#76ff03', '#cddc39', '#c6ff00', '#ffb74d', '#e65100', '#ff8a65', '#b9f6ca', '#26c6da', '#03a9f4',
'#40c4ff', '#64ffda', '#84ffff', '#4caf50', '#69f0ae', '#76ff03', '#cddc39', '#c6ff00', '#ffb74d', '#e65100',
'#ff8a65', '#b9f6ca'];

var DADOSFATURAMENTO = [];
var DADOSPRODUTOS = [];
var MODELO = initModeloGrafico;
var MODELOPROD = initModeloGrafico2;
var DIAS = 0;
$(function () {

	faturamentoDosUltimosSeteDias();
	filtrarProdutos();

	setTimeout(() => {
		$('#seteDias').trigger('click')
		buscaProdutos()

		// montaContaPagar([])
		getContasPagar()
		getContasReceber()
		getVendasPDV()
		getVendasPedido()
		getOrcamentos()
		getEmissaoNfe()
		getEmissaoNfce()
		getProdutos()
	}, 10)


});

$('#set-location').click(() => {
	let filial_id = $('#filial_id').val()
	$.get(path + 'usuarios/set-location', {filial_id: filial_id})
	.done((success) => {
		swal("Sucesso", "Local definido como padrão!", "success")

	})
	.fail((err) => {
		console.log(err)
		swal("Opss", "Algo deu errado!", "error")
	})
})

$('#filial_id').change(() => {
	if($('#filial_id')){
		setTimeout(() => {
			$('#seteDias').trigger('click')
			filtrar()
			buscaProdutos()
			filtrarProdutos();

			getContasPagar()
			getContasReceber()
			getVendasPDV()
			getVendasPedido()
			getOrcamentos()
			getEmissaoNfe()
			getEmissaoNfce()
			getProdutos()

		}, 10)
	}
})

function buscaProdutos(){
	let filial_id = $('#filial_id').val()
	$.get(path + 'graficos/countProdutos', {filial_id: filial_id})
	.done((success) => {
		$('#tot-produtos').html(success)
	})
	.fail((err) => {
		console.log(err)
		// alert('Erro ao buscar dados de faturamento')
	})
}

function contasPagar(){
	let dInit = getDateInit()
	let dFin = getDateNow()
	let q = "/contasPagar/filtro?fornecedorId=null&tipo_filtro_data=1&data_inicial="+dInit+"&data_final"
	+"="+dFin+"&status=todos&categoria=todos&tipo_pagamento=&numero_nota_fiscal="

	location.href = q
}

function contasReceber(){
	let dInit = getDateInit()
	let dFin = getDateNow()
	let q = "/contasReceber/filtro?clienteId=null&tipo_filtro_data=1&data_inicial="+dInit+"&data_final="+dFin+"&status=todos&"
	+"categoria=todos&tipo_pagamento=&numero_pedido="

	location.href = q
}

function getDateNow(){
	var data = new Date();
	var dia = String(data.getDate()).padStart(2, '0');
	var mes = String(data.getMonth() + 1).padStart(2, '0');
	var ano = data.getFullYear();
	return dia + '/' + mes + '/' + ano;
}

function getDateInit(){
	var data = new Date();
	data.setDate(data.getDate() - DIAS);

	var dia = String(data.getDate()).padStart(2, '0');
	var mes = String(data.getMonth() + 1).padStart(2, '0');
	var ano = data.getFullYear();
	return dia + '/' + mes + '/' + ano;
}

function getContasPagar(){
	let filial_id = $('#filial_id').val()

	$.get(path + 'graficos/contasPagar', {filial_id: filial_id})
	.done((data) => {
		montaContaPagar(data)
	})
	.fail((err) => {
		console.log(err)
	})

}

function getContasReceber(){
	let filial_id = $('#filial_id').val()

	$.get(path + 'graficos/contasReceber', {filial_id: filial_id})
	.done((data) => {
		montaContaReceber(data)
	})
	.fail((err) => {
		console.log(err)
	})

}

function getVendasPDV(){
	let filial_id = $('#filial_id').val()
	$.get(path + 'graficos/vendasPdv', {filial_id: filial_id})
	.done((data) => {
		montaVendasPdv(data)
	})
	.fail((err) => {
		console.log(err)
	})

}

function getVendasPedido(){
	let filial_id = $('#filial_id').val()
	$.get(path + 'graficos/vendasPedido', {filial_id: filial_id})
	.done((data) => {
		montaVendasPedido(data)
	})
	.fail((err) => {
		console.log(err)
	})

}

function getOrcamentos(){
	let filial_id = $('#filial_id').val()
	$.get(path + 'graficos/orcamentos', {filial_id: filial_id})
	.done((data) => {
		montaOrcamentos(data)
	})
	.fail((err) => {
		console.log(err)
	})

}

function getEmissaoNfe(){
	let filial_id = $('#filial_id').val()
	$.get(path + 'graficos/emissaoNfe', {filial_id: filial_id})
	.done((data) => {
		montaNfe(data)
	})
	.fail((err) => {
		console.log(err)
	})
}

function getEmissaoNfce(){
	let filial_id = $('#filial_id').val()
	$.get(path + 'graficos/emissaoNfce', {filial_id: filial_id})
	.done((data) => {
		montaNfce(data)
	})
	.fail((err) => {
		console.log(err)
	})

}

function getProdutos(){
	let filial_id = $('#filial_id').val()
	$.get(path + 'graficos/produtos', {filial_id: filial_id})
	.done((data) => {
		console.log("prod", data)
		// aert('oi')
		montaProdutos(data)
	})
	.fail((err) => {
		console.log(err)
	})

}

function montaContaPagar(dados){
	var ctx = $('#grafico-contas-pagar');
	var myChart = new Chart(ctx, {
		type: "bar",
		data: {

			labels: constroiLabel(dados),
			datasets: [{
				label: 'Valor',
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


function montaContaReceber(dados){
	var ctx = $('#grafico-contas-receber');
	var myChart = new Chart(ctx, {
		type: "bar",
		data: {

			labels: constroiLabel(dados),
			datasets: [{
				label: 'Valor',
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

function montaVendasPdv(dados){
	var ctx = $('#grafico-vendas-pdv');
	var myChart = new Chart(ctx, {
		type: "bar",
		data: {

			labels: constroiLabel(dados),
			datasets: [{
				label: 'Valor',
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

function montaVendasPedido(dados){
	var ctx = $('#grafico-vendas-pedido');
	var myChart = new Chart(ctx, {
		type: "bar",
		data: {

			labels: constroiLabel(dados),
			datasets: [{
				label: 'Valor',
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

function montaOrcamentos(dados){
	var ctx = $('#grafico-orcamentos');
	var myChart = new Chart(ctx, {
		type: "bar",
		data: {

			labels: constroiLabel(dados),
			datasets: [{
				label: 'Valor',
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

function montaProdutos(dados){
	var ctx = $('#grafico-produtos');
	var myChart = new Chart(ctx, {
		type: "bar",
		data: {

			labels: constroiLabel(dados),
			datasets: [{
				label: 'Valor',
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


function montaNfe(dados){
	var ctx = $('#grafico-nfe');
	var myChart = new Chart(ctx, {
		type: "bar",
		data: {

			labels: constroiLabel(dados),
			datasets: [{
				label: 'Valor',
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

function montaNfce(dados){
	var ctx = $('#grafico-nfce');
	var myChart = new Chart(ctx, {
		type: "bar",
		data: {

			labels: constroiLabel(dados),
			datasets: [{
				label: 'Valor',
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



function montaGraficoFaturamento(modelo, dados){
	$('#novo-faturamento').html('<canvas id="grafico-faturamento" style="width: 100%; margin-left: 10px; margin-top: 20px;"></canvas>')
	var ctx = $('#grafico-faturamento');

	var myChart = new Chart(ctx, {
		type: modelo,
		data: {

			labels: constroiLabel(dados),
			datasets: [{
				label: 'Valor',
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

function montaGraficoProdutos(modelo, dados){
	$('#novo-produtos').html('<canvas id="grafico-produtos-vendidos" style="width: 100%; margin-left: 10px; margin-top: 20px;"></canvas>')
	var ctx = $('#grafico-produtos-vendidos');
	var myChart = new Chart(ctx, {
		type: modelo,
		data: {

			labels: constroiLabel(dados),
			datasets: [{
				label: 'Produto',
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

function constroiLabel(dados){
	let temp = [];
	dados.map((v) => {
		temp.push(v.data);
	})
	return temp;
}

function constroiData(dados){
	let temp = [];
	dados.map((v) => {
		temp.push(v.total);
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

function alteraModeloGrafico(modelo){

	montaGraficoFaturamento(modelo, DADOSFATURAMENTO);
	MODELO = modelo;
}
function alteraModeloGrafico2(modelo){

	montaGraficoProdutos(modelo, DADOSPRODUTOS);
	MODELOPROD = modelo;
}

function faturamentoDosUltimosSeteDias(){
	let filial_id = $('#filial_id').val()

	$.get(path + 'graficos/faturamentoDosUltimosSeteDias', {filial_id: filial_id})
	.done((success) => {
		DADOSFATURAMENTO = success;
		montaGraficoFaturamento(initModeloGrafico, success);
	})
	.fail((err) => {
		console.log(err)
		// alert('Erro ao buscar dados de faturamento')
	})
}

function filtrar(){
	let filial_id = $('#filial_id').val()
	let data_inicial = $('.data_inicial').val();
	let data_final = $('.data_final').val();
	let js = {
		data_inicial: data_inicial,
		data_final: data_final,
		filial_id: filial_id,
	}
	$.get(path + 'graficos/faturamentoFiltrado', js)
	.done((success) => {
		console.log(success)
		DADOSFATURAMENTO = success;
		montaGraficoFaturamento(MODELO, success);
	})
	.fail((err) => {
		alert('Erro ao buscar dados de faturamento')
	})
}

function filtrarProdutos(){

	let filial_id = $('#filial_id').val()
	let data_inicial = $('.data_inicial2').val();
	let data_final = $('.data_final2').val();
	let js = {
		data_inicial: data_inicial,
		data_final: data_final,
		filial_id: filial_id,
	}

	$.get(path + 'graficos/produtosFiltrado', js)
	.done((success) => {
		console.clear()
		console.log("ssss", success)
		DADOSPRODUTOS = success;
		montaGraficoProdutos(MODELOPROD, success);
	})
	.fail((err) => {
		// alert('Erro ao buscar dados de faturamento')
	})
}

//fitro box

$('#hoje').click(() => {
	filtroBox(1)
	DIAS = 0
	activeButton('#hoje')

})

$('#seteDias').click(() => {
	filtroBox(7)
	DIAS = 7
	activeButton('#seteDias')
})

$('#trintaDias').click(() => {
	filtroBox(30)
	DIAS = 30
	activeButton('#trintaDias')
})

$('#sessentaDias').click(() => {
	filtroBox(60)
	DIAS = 60
	activeButton('#sessentaDias')
})

function activeButton(b){
	$('#hoje').removeClass('active')
	$('#seteDias').removeClass('active')
	$('#trintaDias').removeClass('active')
	$('#sessentaDias').removeClass('active')
	$(b).addClass('active')
}

function filtroBox(dias){
	let filial_id = $('#filial_id').val()

	$.get(path + 'graficos/boxConsulta', {dias: dias, filial_id: filial_id})
	.done((success) => {
		// console.log(success)
		$('#tot-vendas').html(success.totalDeVendas)
		$('#tot-pedidos').html(success.totalDePedidos)
		$('#tot-contas-receber').html(success.totalDeContaReceber)
		$('#tot-contas-pagar').html(success.totalDeContaPagar)
	})
	.fail((err) => {
	})
}

function convertMoney(v){
	return v.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}



