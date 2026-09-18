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
            valor_compra: valorCompra,
            produto_id: produto.id,
            numero_nfe: nNf, 
            fornecedor_id: fornecedor_id,
            
            // 🔥 O SEGREDO: Enviamos o código que veio da nota aqui
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
                $('#modal-link').modal('hide'); 

                swal("Sucesso", "Produto Vinculado com sucesso!", "success")
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
    }else{
        swal("Erro", "Selecione o produto", "error");
    }
});

var searchTimeout = null;

// =================================================================
// 🟢 SALVAR PRODUTO COMPLETO VIA MODAL (FINAL E PERFEITO)
// =================================================================
$('#salvar').click(function(e) {
    e.preventDefault();
    let btn = $(this);
    
    let valorVenda = $('#valor_venda').val();
    if(valorVenda <= 0 || valorVenda === ''){
        swal("Erro", "Informe um valor de venda válido", "warning");
        return;
    }

    $('#preloader').css('display', 'block');
    btn.addClass('spinner spinner-white spinner-right').attr('disabled', true);

    // Como corrigimos os NAMEs no HTML, o jQuery varre tudo sozinho!
    let formArray = $('#kt_form').serializeArray();
    let prod = {};
    
    // Transforma os campos em um objeto, e caso algo venha vazio ele joga '0' pra não dar erro
    $.each(formArray, function() {
        prod[this.name] = this.value || '0'; 
    });

    // Como o Checkbox não é capturado quando está desmarcado, forçamos aqui
    prod.gerenciar_estoque = $('#gerenciar_estoque').is(':checked') ? 1 : 0;
    prod.inativo = $('#inativo').is(':checked') ? 1 : 0;

    // Colocamos os dados que são gerados pelo sistema, que não ficam dentro do form do modal
    prod.ncm = ncm; 
    prod.id_empresa = $('#empresa_id').val() || 1;
    prod.fornecedor_id = $('#fornecedor_id').val() || 0;
    prod.numero_nfe = nNf;
    prod.codigo_fornecedor = codigo;
    prod.descricao_fornecedor = nome;
    prod.codigo_barras_fornecedor = codBarras;
    prod.filial_id = $('#filial_id').length > 0 ? $('#filial_id').val() : -1;

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

            swal("Sucesso", "Produto Salvo e vinculado ao Estoque com Sucesso!", "success")
            .then(sim => {
                location.reload(); 
            });
        }, 
        error: function(e){
            console.error(e);
            $('#preloader').css('display', 'none');
            btn.removeClass('spinner spinner-white spinner-right').attr('disabled', false);
            
            try {
                swal("Erro de Banco de Dados", e.responseJSON.message || e.responseText, "error");
            } catch (err) {
                swal("Erro Inesperado", "Ocorreu uma falha grave na comunicação com o servidor.", "error");
            }
        }
    });
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

// Garante o tempo de digitação correto sem quebrar a busca
var searchTimeout = null; 

// =================================================================
// 🟢 AUTOCOMPLETE CORRIGIDO: INTEGRAÇÃO PERFEITA COM AS DUAS ROTAS
// =================================================================
var searchTimeout = null; 

$('#produto-search').keyup(function() {
	let pesquisa = $(this).val();
	
	if (searchTimeout) {
		clearTimeout(searchTimeout);
	}

	if(pesquisa.length > 1){
		searchTimeout = setTimeout(() => {
			let filial_id = $('#filial_id').length > 0 ? $('#filial_id').val() : -1;

			// 1. Chamamos a rota de pesquisa por TEXTO do seu sistema
			$.get(path + 'produtos/autocomplete', { 
				pesquisa: pesquisa,
				filial_id: filial_id 
			})
			.done((res) => {
				let dados = Array.isArray(res) ? res : (res.produtos || []);

				if(dados.length > 0){
					let html = '';
					dados.map((rs) => {
						let p = rs.nome;
						if(rs.grade){
							p += ' ' + (rs.str_grade || '');
						}
						
						// Força a prioridade visual (z-index) para aparecer na frente do modal branco
						html += '<label onclick="selectProd('+rs.id+')" style="display: block; width: 100%; padding: 8px; background: #fff; cursor: pointer; border-bottom: 1px solid #eee; position: relative; z-index: 99999 !important;">'+p+'</label>';
					});
					
					$('.search-prod').html(html);
					$('.search-prod').css({
						'display': 'block',
						'position': 'absolute',
						'z-index': '99999',
						'background': '#fff',
						'width': '93%',
						'border': '1px solid #ccc',
						'max-height': '200px',
						'overflow-y': 'auto'
					});
				} else {
					$('.search-prod').css('display', 'none');
				}
			})
			.fail((err) => {
				console.log("Erro na busca por texto:", err);
			});
		}, 400); 
	} else {
		$('.search-prod').css('display', 'none');
	}
});

// 2. Esta função roda APENAS quando você clica em um item da lista
function selectProd(id){
	let lista_id = $('#lista_id').val() || 0;
	let filial_id = $('#filial_id').length > 0 ? $('#filial_id').val() : -1;

	// Chamamos a rota de busca por ID para preencher os valores na tela
	$.get(path + 'produtos/autocompleteProduto', { id: id, lista_id: lista_id, filial_id: filial_id })
	.done((res) => {
		PRODUTO = Array.isArray(res) ? res[0] : res;

		if(PRODUTO) {
			let p = PRODUTO.nome; 
			if(PRODUTO.grade && PRODUTO.str_grade){
				p += ' ' + PRODUTO.str_grade;
			}

			// Alimenta os inputs de preço do modal de vínculo
			$('#valor_venda2').val(parseFloat(PRODUTO.valor_venda || 0).toFixed(casas_decimais));
			$('#valor_compra2').val(parseFloat(PRODUTO.valor_compra || 0).toFixed(casas_decimais));
			$('#produto-search').val(p);
		}
	})
	.fail((err) => {
		console.log(err);
		swal("Erro", "Erro ao carregar detalhes do produto", "error");
	});
	$('.search-prod').css('display', 'none');
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

