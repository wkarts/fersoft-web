var codigo = "";
var nome = "";
var ncm = "";
var cfop = "";
var unidade = "";
var valor = "";
var quantidade = "";
var codBarras = "";
var cfopEntrda = "";
var TOTAL = 0;
var fatura = [];
var semRegitro;
var PRODUTO = null;
var SUBCATEGORIAS = [];

$(function () {

	SUBCATEGORIAS = JSON.parse($('#subs').val())

	fatura = JSON.parse($('#fatura').val());
	TOTAL = parseFloat($('#total').val())
	semRegitro = $('#prodSemRegistro').val();
	if(semRegitro == 0){
		$('#salvarNF').removeAttr("disabled");
		$('.sem-registro').css('display', 'none');
	}
	verificaProdutoSemRegistro();

	montaHtmlFatura((html) => {
		$('#fatura-html').html(html)
	})

	setTimeout(() => {
		montaSubs()
	}, 100)
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

function linkProduto(){
	$('#kt_select2_1').val('null').change()
	$('#valor_venda2').val('')
	$('#valor_compra2').val('')
	$('#modal1').modal('hide');
	$('#modal-link').modal('show');
	$('#estoque').val(this.quantidade)
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
	let id = this.codigo;
	let prod = $('#kt_select2_1').val()

	let estoque = $('#estoque').val()
	let valor = $('#valor_venda2').val()
	let valorCompra = $('#valor_compra2').val()
	if(prod != 'null'){
		produto = PRODUTO;
		$('#n_'+id).html(produto.nome)
		$('#n_'+id).removeClass('text-danger')
		$('#th_prod_id_'+id).html(produto.id)
		$('#th_prod_valor_venda_'+id).html(valor)
		$('#th_prod_valor_compra_'+id).html(valorCompra)
		$('#qtd_aux_'+id).html(estoque)

		semRegitro--;
		verificaProdutoSemRegistro();
		$('#modal-link').modal('hide');

	}else{
		swal("Erro", "Selecione o produto", "error");
	}
})

$('#salvarEdit').click(() => {
	let id = $('#idEdit').val();
	$('#n_'+id).html($('#nomeEdit').val());
	$('#th_prod_conv_unit_'+id).html($('#conv_estoqueEdit').val());

	$('#th_prod_valor_venda_'+id).html($('#valorVendaEdit').val());
	$('#th_prod_valor_compra_'+id).html($('#valorCompraEdit').val());

	$('#modal2').modal('hide');
})

function verificaProdutoSemRegistro(){
	if(semRegitro == 0){
		$('#salvarNF').removeAttr("disabled");
		$('.sem-registro').css('display', 'none');
	}else{
		$('.prodSemRegistro').html(semRegitro);
	}
}

function _construct(codigo, nome, codBarras, ncm, cfop, unidade, valor, quantidade, cfop_entrada, cest){

	this.codigo = codigo;
	this.nome = nome;
	this.ncm = ncm;
	this.cest = cest;
	this.cfop = cfop;
	this.unidade = unidade;
	this.valor = valor;
	this.quantidade = quantidade;
	this.codBarras = codBarras;
	this.cfopEntrda = cfop_entrada;
}

function cadProd(codigo, nome, codBarras, ncm, cfop, unidade, valor, quantidade, cfop_entrada, cest){

	_construct(codigo, nome, codBarras, ncm, cfop, unidade, valor, quantidade, cfop_entrada, cest);
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

		$('#cfop').val(cfop);
		$('#CEST').val(cest);

		$('#un_compra').val(unidade);
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
		$('#conv_estoque').val('1');

		$('#cfop_entrada').val(cfop_entrada);
		$('#codBarras').val(codBarras);
		$("#quantidade").trigger("click");
		montaSubs()

		$('#modal1').modal('toggle');

	})

}

function deleteProd(item){
	if (confirm('Deseja excluir este item, se confirmar sua NF ficará informal?')) {
		var tr = $(item).closest('tr');
		tr.fadeOut(500, function() {
			tr.remove();
			verificaTabelaVazia();
			verificaProdutoSemRegistro();
		});

		return false;
	}
}

function editProd(id){

	let produtoId = $('#th_prod_id_'+id).html();
	$('#idEdit').val(id)
	$.ajax
	({
		type: 'GET',
		url: path + 'produtos/getProduto/'+produtoId,
		dataType: 'json',
		success: function(e){
			$("#nomeEdit").val(e.nome)
			$("#conv_estoqueEdit").val(e.conversao_unitaria)
			$("#valorVendaEdit").val(e.valor_venda)
			$("#valorCompraEdit").val(e.valor_compra)
			$('#modal2').modal('show');
		}, error: function(e){
			console.log(e);
		}
	});
}

function verificaTabelaVazia(){
	if($('table tbody tr').length == 0){
		$('#salvarNF').addClass("disabled");
	}
}

function validaItem(){
	let nome = $('#nome').val()
	let ncm = $('#ncm').val()
	let cfop = $('#cfop').val()
	let valor = $('#valor').val()
	let valor_venda = $('#valor_venda').val()
	let un_compra = $('#un_compra').val()
	let unidade_venda = $('#unidade_venda').val()

	if(nome && ncm && cfop && valor && valor_venda && un_compra && unidade_venda){
		return true
	}else{
		return false
	}
}

$('#categoria_id').change(() => {
	montaSubs()
})

function montaSubs(){
	let categoria_id = $('#categoria_id').val()
	let subs = SUBCATEGORIAS.filter((x) => {
		return x.categoria_id == categoria_id
	})

	let options = ''
	subs.map((s) => {
		options += '<option value="'+s.id+'">'
		options += s.nome
		options += '</option>'
	})
	$('#sub_categoria_id').html('<option value="">selecione</option>')
	$('#sub_categoria_id').append(options)
}

var saveProduto = false;
$('#salvar').click(() => {
	if(saveProduto == false){
		saveProduto = true;

		let valid = validaItem()

		if(!valid){
			swal("Alerta", "Todos os campos com * são obrigatórios!", "warning")
			saveProduto = false;
			return
		}
		$('#preloader').css('display', 'block');
		$("#th_"+this.codigo).removeClass("red-text");
		$("#n_"+this.codigo).html($('#nome').val());
		let valorVenda = $('#valor_venda').val();
		let valor_compra = $('#valor').val();
		let unidadeVenda = $('#unidade_venda').val();
		let conversaoEstoque = $('#conv_estoque').val();
		let categoria_id = $('#categoria_id').val();
		// let cor = $('#cor').val();
		let cfop = $('#cfop').val();
		let referencia = $('#referencia').val();
		let percentual_lucro = $('#percentual_lucro').val();

		let CST_CSOSN = $('#CST_CSOSN').val();
		let CST_PIS = $('#CST_PIS').val();
		let CST_COFINS =$('#CST_COFINS').val();
		let CST_IPI = $('#CST_IPI').val();
		let perc_icms = $('#perc_icms').val();
		let perc_pis = $('#perc_pis').val();
		let perc_cofins = $('#perc_cofins').val();
		let perc_ipi = $('#perc_ipi').val();
		let codBarras = $('#codBarras').val();
		let marca_id = $('#marca_id').val();
		let sub_categoria_id = $('#sub_categoria_id').val();

		let prod = {
			valorVenda: valorVenda,
			unidadeVenda: unidadeVenda,
			conversao_unitaria: conversaoEstoque,
			categoria_id: categoria_id,
			sub_categoria_id: sub_categoria_id,
			marca_id: marca_id,
			valorCompra: valor_compra,
			nome: $('#nome').val(),
			ncm: this.ncm,
			cfop: cfop,
			percentual_lucro: percentual_lucro,
			referencia: referencia,
			unidadeCompra: $('#un_compra').val(),
			valor: this.valor,
			quantidade: this.quantidade,
			codBarras: codBarras,
			CST_CSOSN: CST_CSOSN,
			CST_PIS: CST_PIS,
			CST_COFINS: CST_COFINS,
			CST_IPI: CST_IPI,
			valorCompra: valor_compra,
			perc_icms: perc_icms,
			perc_pis: perc_pis,
			perc_cofins: perc_cofins,
			perc_ipi: perc_ipi,

			estoque_minimo: $('#estoque_minimo').val(),
			gerenciar_estoque: $('#gerenciar_estoque').is(':checked') ? 1 : 0,
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
			filial_id: $('#filial_id') ? $('#filial_id').val() : -1,

		}

		semRegitro--;
		verificaProdutoSemRegistro();

		let token = $('#_token').val();

		$.ajax
		({
			type: 'POST',
			data: {
				produto: prod,
				_token: token
			},
			url: path + 'produtos/salvarProdutoDaNota',
			dataType: 'json',
			success: function(e){

				let cfop_entrada = $('#cfop_entrada').val()
				$("#th_prod_id_"+codigo).html(e.id);
				$("#cfop_entrada_"+codigo).html(cfop_entrada);
				$("#th_acao1_"+codigo).css('display', 'none');
				$("#th_acao2_"+codigo).css('display', 'block');
				$("#n_"+codigo).removeClass('text-danger');
				$('#preloader').css('display', 'none');
				$('#modal1').modal('hide');
				// alert(conversaoEstoque)
				$('#th_prod_conv_unit_'+codigo).html(conversaoEstoque);


				swal('Sucesso', 'Item salvo', 'success')

				saveProduto = false;

			}, error: function(e){
				console.log(e)
				$('#preloader').css('display', 'none');
				saveProduto = false;
			}
		});
	}
})

/* USANDO DA VIEW
var salvando = false;
$('#salvarNF').click(() => {
    $('#salvarNF').addClass('spinner').attr('disabled', 'disabled');
    
    if(salvando == false){
        salvando = true;
        $('#preloader2').css('display', 'block');

        salvarNF((data) => {
            if(data.id){
                salvarItens(data.id, (v) => { // data.id = codigo da compra
                    if(v){
                        salvarFatura(data.id, (f) => {
                            $('#modal1').modal('hide');
                            $('#preloader2').css('display', 'none');
                            sucesso();
                        });
                    }
                });
            }
        });
    }
});

*/

	/*USANDO DA VIEW
	function salvarFatura(compra_id, call){
    let token = $('#_token').val();
    // Pegando as faturas diretamente do input hidden (onde o rateio já salva o JSON pronto)
    let faturas = JSON.parse($('#fatura').val() || '[]'); 
    let faturasSalvas = 0;

    if(faturas.length > 0){
        faturas.map((item) => {
            item.compra_id = compra_id;
            
            $.ajax({
                type: 'POST',
                data: { parcela: item, _token: token },
                url: path + 'compraFiscal/salvarParcela',
                dataType: 'json',
                success: function(e){
                    faturasSalvas++;
                    if(faturasSalvas === faturas.length){
                        call(true);
                    }
                }, 
                error: function(e){
                    console.log(e);
                    $('#preloader2').css('display', 'none');
                    salvando = false;
                }
            });
        });
    } else {
        // Se a nota for zerada ou não tiver faturas, passa direto
        call(true);
    }
}
*/

function sucesso(){
	audioSuccess()
	$('#content').css('display', 'none');
	$('#anime').css('display', 'block');
	setTimeout(() => {
		location.href = path+'compras';
	}, 4000)
}

$('#filial_id').change(() => {
	$('#salvarNF').removeAttr("disabled");
})

	/*USANDO DA VIEW

	function salvarNF(call){
    let valor_nf = $('#valorDaNF').html();
    // Limpando formatação de moeda para salvar no BD
    valor_nf = valor_nf.replace('R$','').replace(/\s/g, '').replace('.','').replace(',','.').trim();

    let js = {
        fornecedor_id: $('#idFornecedor').val(),
        nNf: $('#nNf').val(),
        data_emissao: $('#data_emissao').val(),
        valor_nf: valor_nf,
        observacao: '',
        lote: $('#lote').val(),
        desconto: $('#vDesc').val(),
        xml_path: $('#pathXml').val(),
        categoria_conta_id: $('#categoria_conta_id').val(),
        chave: $('#chave').val(),
        filial_id: $('#filial_id').length ? $('#filial_id').val() : -1,
        veiculo_id: $('#veiculo_geral').val(), // Atualizado para pegar do select geral
        conta_empresa_id: $('#conta_empresa_id').val() // Capturando a Conta Bancária
    };

    let token = $('#_token').val();

    $.ajax({
        type: 'POST',
        data: { nf: js, _token: token },
        url: path + 'compraFiscal/salvarNfFiscal',
        dataType: 'json',
        success: function(e){
            call(e);
        }, 
        error: function(e){
            console.log(e);
            $('#preloader2').css('display', 'none');
            salvando = false;
        }
    });
}
*/

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

$('#conv_estoque').blur(() => {
	let v = $('#valor').val()
	let conv_estoque = $('#conv_estoque').val()
	let percentual_lucro = $('#percentual_lucro').val()
	let vUnitCompra = v/conv_estoque

	$('#valor').val(vUnitCompra.toFixed(2).replace(".", ","))

	if(percentual_lucro == '0,00'){
		$('#valor_venda').val(vUnitCompra.toFixed(2).replace(".", ","))
	}else{
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
	}
})

/*USANDO DA VIEW
	function salvarItens(id, call){
    let token = $('#_token').val();
    let totalItens = $('table tbody tr').length;
    let itensSalvos = 0;

    $('table tbody tr').each(function(){
        let tr = $(this);
        let js = {
            compra_id: id,
            produto_id: parseInt(tr.find('.cod').html()),
            codigo: tr.find('.codigo').html(),
            xProd: tr.find('.nome').html(),
            codBarras: tr.find('.codBarras').html(),
            quantidade: tr.find('.quantidade').html() ? tr.find('.quantidade').html().replace(',','.') : tr.find('input[name="quantidade[]"]').val(),
            valor: tr.find('.valor').html().replace('.','').replace(',','.'),
            cfop: tr.find('.cfop').val(),
            cfop_entrada: tr.find('.cfop_entrada_input').val(),
            cst_icms: tr.find('.cst_icms_input').val(),
            cst_pis: tr.find('.cst_pis_input').val(),
            cst_cofins: tr.find('.cst_cofins_input').val(),
            finalidade: tr.find('.finalidade_input').val(),
            
            // Impostos do XML
            vbc_icms: tr.find('.vbc_icms').val(),
            p_icms: tr.find('.p_icms').val(),
            v_icms: tr.find('.v_icms').val(),
            vbc_pis: tr.find('.vbc_pis').val(),
            p_pis: tr.find('.p_pis').val(),
            v_pis: tr.find('.v_pis').val(),
            vbc_cofins: tr.find('.vbc_cofins').val(),
            p_cofins: tr.find('.p_cofins').val(),
            v_cofins: tr.find('.v_cofins').val(),

            // Reforma Tributária
            cst_ibs_cbs: tr.find('.cst_ibs_cbs').val(),
            bc_ibs_cbs: tr.find('.bc_ibs_cbs').val(),
            aliq_ibs: tr.find('.aliq_ibs').val(),
            aliq_cbs: tr.find('.aliq_cbs').val(),
            valor_ibs: tr.find('.valor_ibs').val(),
            valor_cbs: tr.find('.valor_cbs').val(),
            class_trib_ibs_cbs: tr.find('.class_trib_ibs_cbs').val(),
            
            filial_id: $('#filial_id').length ? $('#filial_id').val() : -1,
            unidade: tr.find('.unidade').val() || 'UN'
        };

        $.ajax({
            type: 'POST',
            data: { produto: js, _token: token },
            url: path + 'compraFiscal/salvarItem',
            dataType: 'json',
            success: function(e){
                itensSalvos++;
                // Só chama a próxima etapa (Faturas) quando salvar o último item
                if(itensSalvos === totalItens){
                    call(true);
                }
            }, 
            error: function(e){
                console.log(e);
                $('#preloader2').css('display', 'none');
                salvando = false;
            }
        });
    });
}

*/

$('#add-pag').click(() => {
	let vencimento = $('#kt_datepicker_3').val();
	let valor_parcela = $('#valor_parcela').val();
	if(vencimento.length<10 || valor_parcela < 0){
		swal("Erro", "Informe o valor da parcela e vencimento", "error")
	}else{
		somaFatura((res) => {
			valor_parcela = valor_parcela.replace(",", ".")
			let soma = res + parseFloat(valor_parcela)

			if(soma <= TOTAL){
				let js = {
					numero: fatura.length+1,
					vencimento: vencimento,
					valor_parcela: parseFloat(valor_parcela),
					rand: Math.floor(Math.random() * 10000)
				}

				fatura.push(js)
				montaHtmlFatura((html) => {
					$('#fatura-html').html(html)
				})
			}else{
				swal({
					title: "Alerta",
					text: "Valor total de parcelas ultrapassado, deseja continuar?",
					icon : "warning",
					buttons: [
					'Cancelar',
					'Confirmar'
					],
				})
				.then(
					(Confirmar) => {
						let js = {
							numero: fatura.length+1,
							vencimento: vencimento,
							valor_parcela: parseFloat(valor_parcela),
							rand: Math.floor(Math.random() * 10000)
						}

						fatura.push(js)
						montaHtmlFatura((html) => {
							$('#fatura-html').html(html)
						})
					},
					(Cancelar) => {}
					)
			}
		})
	}
})

function somaFatura(call){
	let soma = 0;
	fatura.map((rs) => {

		let v = 0;
		try{
			v = parseFloat(rs.valor_parcela.replace(",", "."))
		}catch{
			v = parseFloat(rs.valor_parcela)
		}
		soma += v
	})
	call(soma)
}

function montaHtmlFatura(call){
	let html = '';
	fatura.map((f) => {
		html += '<div class="col-sm-12 col-lg-6 col-md-6 col-xl-4">'
		html += '<div class="card card-custom gutter-b example example-compact text-white">'
		html += '<div class="card-header">'
		html += '<div class="card-title">'
		html += '<h3 style="width: 230px; font-size: 20px; height: 10px;" class="card-title"> R$ '
		html += maskMoney(f.valor_parcela)
		html += '</h3> <a class="delete-parcela" onclick="deleteParcela('+f.rand+')"><i class="la la-trash text-danger"></i></a></div>'
		html += '<div class="card-body">'
		html += '<div class="kt-widget__info">'
		html += '<span class="kt-widget__label text-dark">Número:</span>'
		html += '<a target="_blank" class="kt-widget__data text-success"> '
		html += f.numero
		html += '</a></div>'
		html += '<div class="kt-widget__info">'
		html += '<span class="kt-widget__label text-dark">Vencimento:</span>'
		html += '<a target="_blank" class="kt-widget__data text-success"> '
		html += f.vencimento
		html += '</a></div>'
		html += '</div></div></div></div>'
	});
	call(html)
}

function deleteParcela(rand){
	let arr = [];
	fatura.map((rs) => {
		if(rs.rand != rand){
			arr.push(rs)
		}
	})
	fatura = arr;
	montaHtmlFatura((html) => {
		$('#fatura-html').html(html)
	})

}

function maskMoney(v){
	try{
		v = v.replace(",", ".");
		v = parseFloat(v);
	}catch{
	}
	return v.toFixed(2).replace(".", ",");
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

$('#produto-search').keyup(() => {
	console.clear()
	let pesquisa = $('#produto-search').val();

	if(pesquisa.length > 1){
		montaAutocomplete(pesquisa, (res) => {
			if(res){
				if(res.length > 0){
					montaHtmlAutoComplete(res, (html) => {
						$('.search-prod').html(html)
						$('.search-prod').css('display', 'block')
					})

				}else{
					$('.search-prod').css('display', 'none')
				}
			}else{
				$('.search-prod').css('display', 'none')
			}
		})
	}else{
		$('.search-prod').css('display', 'none')
	}
})

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

