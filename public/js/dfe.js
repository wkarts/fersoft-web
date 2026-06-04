var array = [];
var codigo = "";
var nome = "";
var ncm = "";
var cfop = "";
var unidade = "";
var valor = "";
var valorCompra = "";
var quantidade = "";
var codBarras = "";
var cest = "";
var nNf = 0;
var semRegitro;
var PRODUTO = null;
var linha_global = "";

$(function () {
	let uri = window.location.pathname;
	if(uri.split('/')[2] == 'novaConsulta'){
		if(!$('#empresa_filial').val()){
			filtrar();
		}
	}else {
		try{
			array = JSON.parse($('#docs').val());
		}catch{
			array = [];
		}
	}
});

function gerarCode(){
	$.get(path+'produtos/gerarCodigoEan')
	.done((res) => {
		$('#codBarras').val(res)
	})
	.fail((err) => {
		swal("Erro", "Erro ao buscar código", "error")
	})
}

$('#tipo_evento').change(() => {
	let tipo = $('#tipo_evento').val();
	if(tipo == 3 || tipo == 4){
		$('#div-just').css('display', 'block')
	}else{
		$('#div-just').css('display', 'none')
	}
})

function linkProduto(){
	let percentualLucro = $('#percentual_lucro').val()
	percentualLucro = percentualLucro.replace(",", ".");


	let valorVenda = parseFloat(this.valorCompra) + (parseFloat(this.valorCompra) * (percentualLucro/100));
	valorVenda = formatReal(valorVenda);
	valorVenda = valorVenda.replace('.', '')
	valorVenda = valorVenda.substring(3, valorVenda.length)
	$('#kt_select2_1').val('null').change()
	$('#valor_venda2').val(valorVenda)
	$('#valor_compra2').val(parseFloat(this.valorCompra).toFixed(2).replace('.', ','))
	$('#modal1').modal('hide');
	$('#modal-link').modal('show');
	$('#estoque').val(this.quantidade)
}

$('#btn-buscar-documentos').click(() => {
	let local = $('#locais').val()
	if(local){
		filtrar(local)
	}else{
		swal("Alerta", "Selecione o local", "warning")
	}
})

function filtrar(local = -1){
	$('#aguarde').removeClass('d-none')

	$.get(path + 'dfe/getDocumentosNovos', {local: local})
	.done((value) => {
		$('#preloader1').css('display', 'none')
		$('#aguarde').css('display', 'none')

		if(value.length > 0){
			montaTabela(value, (html) => {
				$('table tbody').html(html)
				$('#table').css('display', 'block')
			})
			swal("Sucesso", "Foram encontrados " + value.length + " novos registros!", "success")
		}else{
			swal("Sucesso", "A requisição obteve sucesso, porém sem novos registros!!", "success")
			$('#sem-resultado').css('display', 'block')

		}

	})
	.fail(err => {
		console.log(err)
		$('#preloader1').css('display', 'none')
		$('#aguarde').css('display', 'none')
		try{
			swal("Erro", err.responseJSON.message, "warning")
		}catch{
			swal("Erro", "Erro inesperado!!", "warning")
		}
	})
}

function montaTabela(array, call){
	let html = '';
	array.map((v) => {

		html += '<tr class="datatable-row">';
		html += '<td class="datatable-cell"><span class="codigo" style="width: 300px;" id="id">'
		+ v.nome[0] + '</span></td>'
		html += '<td class="datatable-cell"><span class="codigo" style="width: 100px;" id="id">'
		+ v.documento[0] + '</span></td>'
		html += '<td class="datatable-cell"><span class="codigo" style="width: 100px;" id="id">'
		+ v.valor[0] + '</span></td>'
		html += '<td class="datatable-cell"><span class="codigo" style="width: 200px;" id="id">'
		+ v.chave[0] + '</span></td>'
		html += '</tr>';
	})

	call(html)
}

function setarEvento(chave){

	array.map((element) => {
		if(element.chave == chave){

			$('#nome').val(element.nome)
			$('#cnpj').val(element.documento)
			$('#valor').val(element.valor)
			$('#data_emissao').val(element.data_emissao)
			$('#num_prot').val(element.num_prot)
			$('#chave').val(element.chave)
		}

	})

}

function _construct(codigo_, nome_, codBarras_, ncm_, cfop_, unidade_, valor_, quantidade_, valorCompra_, nNf_, cest_, linha_){
	codigo = codigo_;
	nome = nome_;
	ncm = ncm_;
	cfop = cfop_;
	unidade = unidade_;
	valor = valor_;
	valorCompra = valorCompra_;
	quantidade = quantidade_;
	nNf = nNf_;
	cest = cest_;
	codBarras = codBarras_.substring(0, 13);
  	linha_global = linha_ || codigo_; // Trava de segurança ativada
}

// AQUI ESTAVA FALTANDO A PALAVRA "linha" NO FINAL:
function cadProd(codigo, nome, codBarras, ncm, cfop, unidade, valor, quantidade, valorCompra, nNf, cest, linha){
	_construct(codigo, nome, codBarras, ncm, cfop, unidade, valor, quantidade, valorCompra, nNf, cest, linha);

	$('#nome').val(nome);
	$("#nome").focus();
	getUnidadeMedida((data) => {

		let achouUnidade = false;
		data.map((v) => {
			if(v == unidade){
				achouUnidade = true;
			}
		})

		// if(!achouUnidade){
		// 	swal('', "Unidade de compra deste produto não corresponde a nenhuma pré-determinada\n"+
		// 		"Unidade: " + unidade, 'warning')
		// 	.then(s => {


		// 		if(unidade == 'M3C'){
		// 			unidade = 'M3';
		// 			swal('', 'M3C alterado para ' + unidade, 'warning')

		// 		}
		// 		else if(unidade == 'M2C'){
		// 			unidade = 'M2';
		// 			swal('', 'M2C alterado para ' + unidade, 'warning')

		// 		}
		// 		else if(unidade == 'MC'){
		// 			unidade = 'M';
		// 			swal('', 'MC alterado para ' + unidade, 'warning')
		// 		}
		// 		else if(unidade == 'UN'){
		// 			unidade = 'UNID';
		// 			swal('', 'UN alterado para ' + unidade, 'warning')

		// 		}else{
		// 			unidade = 'UNID';
		// 			swal('', 'UN alterado para ' + unidade, 'warning')

		// 		}
		// 	})
		// }

		$('#ncm').val(ncm);
		$("#ncm").trigger("click");
		let dig2Cfop = cfop.substring(1,2);

		if(dig2Cfop == 4){
			cfop = '5405';
		}

		if(cfop == 5405){
			$('#CST_CSOSN').val(500).change()
		}

		// CST_CSOSN

		$('#cfop').val(cfop);
		$('#CEST').val(cest);

		$('#unidade_compra').val(unidade).change();
		$('#referencia').val(codigo);
		$('#unidade_venda option[value="'+unidade+'"]').prop("selected", true);

		$('#valor').val(valor);
		let percentualLucro = $('#percentual_lucro').val()
		percentualLucro = percentualLucro.replace(",", ".");
		// percentualLucro = parseFloat(percentualLucro)

		let valorVenda = parseFloat(valor) + (parseFloat(valor) * (percentualLucro/100));
		valorVenda = formatReal(valorVenda);
		valorVenda = valorVenda.replace('.', '')
		valorVenda = valorVenda.substring(3, valorVenda.length)

		$('#valor_venda').val(valorVenda)
		
		$('#quantidade').val(quantidade);
		$('#codBarras').val(codBarras);
		$('#conv_estoque').val('1');
		// $('#valor_venda').val('0');
		$("#quantidade").trigger("click");

		$('#modal1').modal('show');
	})

}

function getUnidadeMedida(call){

	$.ajax
	({
		type: 'GET',
		url: path + 'produtos/getUnidadesMedida',
		dataType: 'json',
		success: function(e){
			call(e)

		}, error: function(e){
			console.log(e)
		}

	});
}

$('#kt_select2_1').change(() => {
	let produto = $('#kt_select2_1').val()
	if(produto != 'null'){

		produto = JSON.parse(produto);
		$('#valor_venda2').val(parseFloat(produto.valor_venda).toFixed(casas_decimais))
		$('#valor_compra2').val(parseFloat(produto.valor_compra).toFixed(casas_decimais))
	}else{
		$('#valor_venda2').val('')
	}
})

$('#salvarLink').click(() => {
	let estoque = $('#estoque').val();
	let valor = $('#valor_venda2').val();
	let valorCompra = $('#valor_compra2').val();

	if(PRODUTO != null){
        let produto = PRODUTO;
        let fornecedor_id = $('#fornecedor_id').val() || 0;

        let js = {
            estoque: estoque,
            valor_venda: valor,
            referencia: produto.referencia, // Mantém a ref original
            valor_compra: valorCompra,
            produto_id: produto.id,
            numero_nfe: nNf, // Usando a variável global corrigida
            fornecedor_id: fornecedor_id,
            codigo_fornecedor: codigo, 
            descricao_fornecedor: nome, 
            codigo_barras_fornecedor: codBarras 
        };

        let token = $('#_token').val();

        $.ajax({
            type: 'POST',
            data: { produto: js, _token: token },
            url: path + 'produtos/updateProdutoDaNotaComEstoque',
            dataType: 'json',
            success: (e) => {
                $("#th_prod_id_" + linha_global).html(e.id);
                $("#th_" + linha_global).removeClass("text-danger red-text");
                $("#th_acao1_" + linha_global).css('display', 'none');
                $("#th_estoque_" + linha_global).addClass('disabled');

                $('#preloader').css('display', 'none');
                $('#modal1').modal('hide');
                $('#modal-link').modal('hide'); // Fecha o modal de vínculo também

                swal("Sucesso", "Produto Vinculado, e estoque inserido: " + js.estoque, "success")
                .then(sim => {
                    location.reload();
                });
            }, 
            error: function(e){
                console.log(e);
                $('#preloader').css('display', 'none');
                document.write(e.responseText);
            }
        }); // <-- AQUI ERA ONDE FALTAVA FECHAR ANTES
    }else{
        swal("Erro", "Selecione o produto", "error");
    }
});

$('#salvar').click(() => {
	$('#preloader').css('display', 'block');
	$("#th_" + linha_global).removeClass("red-text text-danger");
	$("#th_" + linha_global).html($('#nome').val());
	let valorVenda = $('#valor_venda').val();

	if(valorVenda <= 0){
		swal("Erro", "Informe um valor de venda", "warning");
		$('#preloader').css('display', 'none');
	}else{
		let valorCompra = $('#valor').val();
		let unidadeVenda = $('#unidade_venda').val();
		let conversaoEstoque = $('#conv_estoque').val();
		let categoria_id = $('#categoria_id').val();
		let cor = $('#cor').val();
		let CST_CSOSN = $('#CST_CSOSN').val();
		let CST_PIS = $('#CST_PIS').val();
		let CST_COFINS = $('#CST_COFINS').val();
		let CST_IPI = $('#CST_IPI').val();
		let cfop = $('#cfop').val();
		let percentual_lucro = $('#percentual_lucro').val();
		let codBarrasT = $('#codBarras').val();
		let perc_icms = $('#perc_icms').val();
		let perc_pis = $('#perc_pis').val();
		let perc_cofins = $('#perc_cofins').val();
		let perc_ipi = $('#perc_ipi').val();

		let prod = {
			valorVenda: valorVenda,
			valorCompra: valorCompra,
			percentual_lucro: percentual_lucro,
			unidadeVenda: unidadeVenda,
			conversao_unitaria: conversaoEstoque,
			categoria_id: categoria_id,
			cor: cor,
			nome: $('#nome').val(),
			ncm: ncm, // Variável global corrigida
			cfop: cfop,
			unidadeCompra: unidade, // Variável global corrigida
			valor: valor, // Variável global corrigida
			quantidade: quantidade, // Variável global corrigida
			codBarras: codBarrasT,
			numero_nfe: nNf, // Variável global corrigida
			CST_CSOSN: CST_CSOSN,
			CST_PIS: CST_PIS,
			CST_COFINS: CST_COFINS,
			CST_IPI: CST_IPI,
			referencia: codigo, // Variável global corrigida
			gerenciar_estoque: $('#gerenciar_estoque').is(':checked') ? 1 : 0,
			perc_icms: perc_icms,
			perc_pis: perc_pis,
			perc_cofins: perc_cofins,
			perc_ipi: perc_ipi,
			estoque_minimo: $('#estoque_minimo').val(),
			inativo: $('#inativo').is(':checked'),
			CEST: $('#CEST').val(),
			anp: $('#anp').val(),
			perc_glp: $('#perc_glp').val(),
			perc_gnn: $('#perc_gnn').val(),
			perc_gni: $('#perc_gni').val(),
			valor_partida: $('#valor_partida').val(),
			unidade_tributavel: $('#unidade_tributavel').val(),
			quantidade_tributavel: $('#quantidade_tributavel').val(),
			largura: $('#largura').val(),
			altura: $('#altura').val(),
			comprimento: $('#comprimento').val(),
			peso_liquido: $('#peso_liquido').val(),
			peso_bruto: $('#peso_bruto').val(),
			cenq_ipi: $('#cenq_ipi').val(),
			perc_iss: $('#perc_iss').val(),
			pRedBC: $('#pRedBC').val(),
			pICMSST: $('#pICMSST').val(),
			cBenef: $('#cBenef').val(),
			origem: $('#origem').val(),
			perc_icms_interestadual: $('#perc_icms_interestadual').val(),
			perc_icms_interno: $('#perc_icms_interno').val(),
			perc_fcp_interestadual: $('#perc_fcp_interestadual').val(),
			CFOP_entrada_estadual: $('#CFOP_entrada_estadual').val(),
			CFOP_entrada_inter_estadual: $('#CFOP_entrada_inter_estadual').val(),
			modBC: $('#modBC').val(),
			modBCST: $('#modBCST').val(),
			CST_CSOSN_entrada: $('#CST_CSOSN_entrada').val(),
			CST_PIS_entrada: $('#CST_PIS_entrada').val(),
			CST_COFINS_entrada: $('#CST_COFINS_entrada').val(),
			CST_IPI_entrada: $('#CST_IPI_entrada').val(),
			perc_frete: $('#perc_frete').val(),
			perc_outros: $('#perc_outros').val(),
			perc_mlv: $('#perc_mlv').val(),
			filial_id: $('#filial_id').length > 0 ? $('#filial_id').val() : -1,
			fornecedor_id: $('#fornecedor_id').val() || 0,
			codigo_fornecedor: codigo, 
			descricao_fornecedor: nome, 
			codigo_barras_fornecedor: codBarras 
		};

		let token = $('#_token').val();

		$.ajax({
			type: 'POST',
			data: { produto: prod, _token: token },
			url: path + 'produtos/salvarProdutoDaNotaComEstoque',
			dataType: 'json',
			success: function(e) { 
				$("#th_prod_id_" + linha_global).html(e.id);
				$("#th_" + linha_global).removeClass("text-danger red-text"); 
				$("#th_acao1_" + linha_global).css('display', 'none');
				$("#th_estoque_" + linha_global).addClass('disabled');

				$('#preloader').css('display', 'none');
				$('#modal1').modal('hide');

				swal("Sucesso", "Produto Salvo, e inserido o estoque quantidade: " + prod.quantidade, "success")
				.then(sim => {
					location.reload();
				});
			}, 
			error: function(e){
				console.log(e);
				$('#preloader').css('display', 'none');
				document.write(e.responseText);
			}
		});
	}
});

function salvarEstoque(id, valor, quantidade, numero_nfe){
	swal("Alerta", "Deseja atribuir estoque a este produto?", "warning")
	.then(sim => {
		if(sim){
			let token = $('#_token').val();
			$.ajax
			({
				type: 'POST',
				data: {
					produto: id,
					quantidade: quantidade,
					valor: valor,
					numero_nfe: numero_nfe,
					_token: token
				},
				url: path + 'produtos/setEstoque',
				dataType: 'json',
				success: function(e){
					$("#th_estoque_"+id).addClass('disabled');

					swal("Sucesso", "Inserido o estoque quantidade: " + quantidade, "success")
					.then(() => {
						location.reload()
					})


				}, error: function(e){
					console.log(e)
					$('#preloader').css('display', 'none');
				}
			});
		}
	})
}

function maskMoney(v){
	try{
		v = v.replace(",", ".");
		v = parseFloat(v);
	}catch{

	}
	return v.toFixed(2);
}

$('#percentual_lucro').keyup(() => {

	let valorCompra = parseFloat($('#valor').val().replace(',', '.'));
	let percentualLucro = parseFloat($('#percentual_lucro').val().replace(',', '.'));

	if(valorCompra > 0 && percentualLucro > 0){
		let valorVenda = valorCompra + (valorCompra * (percentualLucro/100));
		valorVenda = formatReal(valorVenda);
		valorVenda = valorVenda.replace('.', '')
		valorVenda = valorVenda.substring(3, valorVenda.length)

		$('#valor_venda').val(valorVenda)
	}else{
		$('#valor_venda').val('0')
	}
})

$('#valor_venda').keyup(() => {
	let valorCompra = parseFloat($('#valor').val().replace(',', '.'));
	let valorVenda = parseFloat($('#valor_venda').val().replace(',', '.'));

	if(valorCompra > 0 && valorVenda > 0){
		let dif = (valorVenda - valorCompra)/valorCompra*100;

		$('#percentual_lucro').val(dif)
	}else{
		$('#percentual_lucro').val('0')
	}
})

function formatReal(v){
	return v.toLocaleString('pt-br', {style: 'currency', currency: 'BRL', minimumFractionDigits: casas_decimais});
}

var searchTimeout = null; // Variável para controlar o tempo de digitação

$('#produto-search').keyup(() => {
	let pesquisa = $('#produto-search').val();
	
	if(pesquisa.length > 1){
		montaAutocomplete(pesquisa, (res) => {
			if(res && res.length > 0){
				montaHtmlAutoComplete(res, (html) => {
					$('.search-prod').html(html);
					$('.search-prod').css('display', 'block');
				});
			}else{
				$('.search-prod').css('display', 'none');
			}
		});
	}else{
		$('.search-prod').css('display', 'none');
	}
});


function montaAutocomplete(pesquisa, call){
	$.get(path + 'produtos/autocomplete', {pesquisa: pesquisa})
	.done((res) => {
		call(res)
	})
	.fail((err) => {
		console.log(err)
		call([])
	})
}

function montaHtmlAutoComplete(arr, call){
	let html = ''
	arr.map((rs) => {
		let p = rs.nome
		if(rs.grade){
			p += ' ' + rs.str_grade
		}
		if(rs.referencia != ""){
			p += ' | REF: ' + rs.referencia
		}
		if(parseFloat(rs.estoqueAtual) > 0){
			p += ' | Estoque: ' + rs.estoqueAtual
		}
		html += '<label onclick="selectProd('+rs.id+')">'+p+'</label>'
	})
	call(html)
}

function selectProd(id){

	let lista_id = $('#lista_id').val();
	$.get(path + 'produtos/autocompleteProduto', {id: id, lista_id: lista_id})
	.done((res) => {
		PRODUTO = res

		let p = PRODUTO.nome
		if(PRODUTO.referencia != ""){
			p += ' | REF: ' + PRODUTO.referencia
		}

		$('#valor_venda2').val(parseFloat(PRODUTO.valor_venda).toFixed(casas_decimais))
		$('#valor_compra2').val(parseFloat(PRODUTO.valor_compra).toFixed(casas_decimais))
		$('#produto-search').val(p)
	})
	.fail((err) => {
		console.log(err)
		swal("Erro", "Erro ao encontrar produto", "error")
	})
	$('.search-prod').css('display', 'none')
}

