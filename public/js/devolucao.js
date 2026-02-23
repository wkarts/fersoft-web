var ITENS = [];
var SOMAITENS = 0;


function rtParseNumber(v){
	if(v === null || v === undefined) return 0;
	if(typeof v === 'number') return isFinite(v) ? v : 0;
	v = String(v).trim();
	if(!v) return 0;
	v = v.replace(/\s+/g,'').replace(/[R$ ]/g,'');
	v = v.replace(/[^0-9\-,.]/g,'');
	if(v.indexOf(',') !== -1 && v.indexOf('.') !== -1){
		v = v.replace(/\./g,'').replace(',', '.');
	}else if(v.indexOf(',') !== -1){
		v = v.replace(',', '.');
	}
	var n = parseFloat(v);
	return isFinite(n) ? n : 0;
}

function aplicarReformaDevolucaoItem(item, done){
	if(!item || !item.codigo){ if(typeof done === 'function') done(); return; }
	$.ajax({
		type: 'POST',
		url: path + 'vendas/reforma/preview-item',
		dataType: 'json',
		data: {
			produto_id: item.codigo,
			quantidade: item.qCom,
			valor: item.vUnCom,
			valor_total: rtParseNumber(item.vUnCom) * rtParseNumber(item.qCom),
			_token: $('#_token').val()
		}
	}).done((res) => {
		const data = res && res.data ? res.data : null;
		if(data){
			item.bc_ibs_cbs = data.bc_ibs_cbs || 0;
			item.valor_ibs = data.valor_ibs || 0;
			item.valor_cbs = data.valor_cbs || 0;
			item.is_valor = data.is_valor || 0;
			item.cst_ibs_cbs = data.cst_ibs_cbs || item.cst_ibs_cbs || '';
			item.class_trib_ibs_cbs = data.class_trib_ibs_cbs || item.class_trib_ibs_cbs || '';
		}
	}).fail((err) => {
		console.error('[RT][devolucao][preview-item] erro', err && err.status, err && err.responseJSON);
	}).always(() => {
		if(typeof done === 'function') done();
	});
}

$(function () {

	ITENS = JSON.parse($('#itens_nf').val());
	prepara((res) => {
		let t = montaTabela();
		$('#tbl tbody').html(t)
	});
	
});

function novaTransportadora(){
	$('#modal-transportadora').modal('show')
}

function prepara(call){
	let temp = [];
	ITENS.map((v) => {
		let js = {
			CFOP: v.CFOP[0],
			NCM: v.NCM[0],
			cBenef: v.cBenef,
			codBarras: v.codBarras[0],
			codigo: v.codigo[0],
			qCom: v.qCom[0],
			uCom: v.uCom[0],
			vUnCom: v.vUnCom[0],
			vFrete: v.vFrete[0],
			xProd: v.xProd[0],
			parcial: 0,
			cst_csosn: v.cst_csosn,
			cst_pis: v.cst_pis,
			cst_cofins: v.cst_cofins,
			cst_ipi: v.cst_ipi,
			perc_icms: v.perc_icms,
			perc_pis: v.perc_pis,
			perc_cofins: v.perc_cofins,
			perc_ipi: v.perc_ipi,
			modBCST: v.modBCST,
			vBCST: v.vBCST,
			pICMSST: v.pICMSST,
			vICMSST: v.vICMSST,
			pMVAST: v.pMVAST,
			pRedBC: v.pRedBC,
			randDelete: v.randDelete,
			pST: v.pST,
			vICMSSubstituto: v.vICMSSubstituto,
			vICMSSTRet: v.vICMSSTRet,
			orig: v.orig,
			vBCSTRet: v.vBCSTRet,

			codigo_anp: v.codigo_anp,
			descricao_anp: v.descricao_anp,
			uf_cons: v.uf_cons,
			valor_partida: v.valor_partida,
			perc_glp: v.perc_glp,
			perc_gnn: v.perc_gnn,
			perc_gni: v.perc_gni,
			unidade_tributavel: v.unidade_tributavel,
			quantidade_tributavel: v.quantidade_tributavel,
			cest: v.cest,

			qBCMonoRet: v.qBCMonoRet,
			adRemICMSRet: v.adRemICMSRet,
			vICMSMonoRet: v.vICMSMonoRet,
			
		}
		aplicarReformaDevolucaoItem(js);
		temp.push(js)
	})
	ITENS = temp;
	call(true)
}

function montaTabela(){
	SOMAITENS = 0;
	let t = ""; 

	ITENS.map((v) => {

		t += '<tr class="datatable-row" style="left: 0px;">'
		t += '<td class="datatable-cell">'
		t += '<span style="width: 80px;">'
		t += v.codigo + '</span>'
		t += '</td>'

		t += '<td class="datatable-cell">'
		t += '<span style="width: 200px;" class="cod">'
		t += v.xProd + '</span>'
		t += '</td>'

		t += '<td class="datatable-cell">'
		t += '<span style="width: 80px;">'
		t += v.NCM + '</span>'
		t += '</td>'

		t += '<td class="datatable-cell">'
		t += '<span style="width: 80px;" class="cBenef_'+v.randDelete+'">'
		t += v.cBenef + '</span>'
		t += '</td>'

		t += '<td class="datatable-cell">'
		t += '<span style="width: 80px;">'
		t += v.CFOP + '</span>'
		t += '</td>'

		t += '<td class="datatable-cell">'
		t += '<span style="width: 80px;">'
		t += v.cest + '</span>'
		t += '</td>'

		t += '<td class="datatable-cell">'
		t += '<span style="width: 80px;">'
		t += v.codBarras + '</span>'
		t += '</td>'

		t += '<td class="datatable-cell">'
		t += '<span style="width: 80px;">'
		t += v.uCom + '</span>'
		t += '</td>'

		t += '<td class="datatable-cell">'
		t += '<span style="width: 80px;">'
		t += formatReal(v.vUnCom) + '</span>'
		t += '</td>'

		t += '<td class="datatable-cell">'
		t += '<span style="width: 80px;">'
		t += v.qCom + '</span>'
		t += '</td>'

		t += '<td class="datatable-cell">'
		t += '<span style="width: 80px;">'
		t += formatReal(v.vUnCom*v.qCom) + '</span>'
		t += '</td>'

		t += '<td class="datatable-cell">'
		t += '<span style="width: 120px;">'
		t += "<a href='#tbl tbody' class='btn btn-sm btn-danger' onclick='deleteItem(\""+v.randDelete+"\")'>"
		t += '<i class="la la-trash"></i></a>'

		t += "<a href='#tbl tbody' class='btn btn-sm btn-warning ml-1' onclick='editItem(\""+v.randDelete+"\")'>"
		t += '<i class="la la-edit"></i></a>'

		t += '</span>'
		t += '</td>'


		// t += "<td><a href='#tbl tbody' onclick='deleteItem("+v.codigo+")'>"
		// t += "<i class=' material-icons red-text'>delete</i></a></td>";
		// t += "<td><a href='#tbl tbody' onclick='editItem("+v.codigo+")'>"
		// t += "<i class=' material-icons blue-text'>edit</i></a></td>";

		t+= "</tr>";

		SOMAITENS += v.vUnCom*v.qCom;
	});
	$('#soma-itens').html(formatReal(SOMAITENS))
	return t;
}

function formatReal(v)
{	
	return v.toLocaleString('pt-br',{style: 'currency', currency: 'BRL'});
}

function deleteItem(item){
	swal("Atenção", "Deseja excluir este item, se confirmar sua NFe ficará incompleta?", "warning")
	.then(() => {
		percorreDelete(item, (res) => {

			setTimeout(() => {
				let freteV = 0;
				if(res.vFrete){
					let vf = parseFloat(res.vFrete)
					let vfTotal = parseFloat($('#vFrete').val().replace(',', '.'))

					let freteV = vfTotal - vf;
				}

				$('#vFrete').val(freteV.toFixed(2))
				$('#valor_frete').val(freteV.toFixed(2))
				let t = montaTabela();
				$('#tbl tbody').html(t);
			}, 300)
		})
		return false;
	})
}

function percorreDelete(id, call){
	let temp = [];
	let itemR = null;
	ITENS.map((v) => {
		if(v.randDelete != id){
			temp.push(v);
		}else{
			itemR = v
		}
	});
	ITENS = temp;
	call(itemR);
}

function editItem(item){

	getItem(item, (res) => {

		$("#nomeEdit").val(res.xProd)
		$("#quantidadeEdit").val(res.qCom)
		$("#valorEdit").val(res.vUnCom)
		$("#valorFreteEdit").val(res.vFrete)
		$("#pRedBC").val(res.pRedBC)
		$("#idEdit").val(res.codigo)
		$("#cBenef").val(res.cBenef)
		$("#randDelete").val(res.randDelete)

		$('#CST_CSOSN').val(res.cst_csosn).change();
		$('#CST_PIS').val(res.cst_pis).change();
		$('#CST_COFINS').val(res.cst_cofins).change();
		$('#CST_IPI').val(res.cst_ipi).change();

		$('#icms').val(res.perc_icms);
		$('#pis').val(res.perc_pis);
		$('#cofins').val(res.perc_cofins);
		$('#ipi').val(res.perc_ipi);

		$('#modal2').modal('show');
	})	
}

$('#salvarEdit').click(() => {
	let id = $('#idEdit').val();
	let randDelete = $('#randDelete').val();
	let nome = $('#nomeEdit').val();
	let quantidade = $('#quantidadeEdit').val();
	let cBenef = $('#cBenef').val();
	let valorFreteEdit = $('#valorFreteEdit').val();
	let pRedBC = $('#pRedBC').val();
	let valor = $('#valorEdit').val();
	percorreEdit(id, randDelete, nome, quantidade, valor, valorFreteEdit, pRedBC, cBenef, (res) => {

		let t = montaTabela();
		$('#tbl tbody').html(t)
		$('#modal2').modal('hide');
		swal("Sucesso", "Item editado!!", "success")
	})

})

function percorreEdit(id, randDelete, nome, quantidade, valor, valorFreteEdit, pRedBC, cBenef, call){

	let cst_csosn = $('#CST_CSOSN').val();
	let cst_pis = $('#CST_PIS').val();
	let cst_cofins = $('#CST_COFINS').val();
	let cst_ipi = $('#CST_IPI').val();

	let icms = $('#icms').val();
	let pis = $('#pis').val();
	let cofins = $('#cofins').val();
	let ipi = $('#ipi').val();

	valor = valor.replace(",", ".")
	quantidade = quantidade.replace(",", ".")
	valorFreteEdit = valorFreteEdit.replace(",", ".")
	pRedBC = pRedBC.replace(",", ".")

	let temp = [];

	ITENS.map((v) => {
		if(v.randDelete == randDelete){

			v.xProd = nome;
			v.parcial = quantidade != v.qCom ? 1 : 0;
			v.qCom = quantidade;
			v.vUnCom = valor;
			v.vFrete = valorFreteEdit;
			v.pRedBC = pRedBC;

			v.cst_csosn = cst_csosn;
			v.cst_pis = cst_pis;
			v.cst_cofins = cst_cofins;
			v.cst_ipi = cst_ipi;
			v.cBenef = cBenef;

			v.perc_icms = icms;
			v.perc_pis = pis;
			v.perc_cofins = cofins;
			v.perc_ipi = ipi;

		}
		temp.push(v);
	});
	ITENS = temp;
	const itemAlterado = ITENS.find((x) => String(x.randDelete) === String(randDelete));
	aplicarReformaDevolucaoItem(itemAlterado, () => call(true));
}

function getItem(id, call){
	let obj = null;
	ITENS.map((v) => {
		if(v.randDelete == id){
			obj = v;
		}
	})
	call(obj)
}

var SALVANDO = 0;
$('#salvar-devolucao').click(() => {
	$('.modal-loading').css('display', 'block')
	if(SALVANDO == 0){
		SALVANDO = 1;
		$('#salvar-devolucao').attr('disabled', 1)
		$('#preloader2').css('display', 'block');
		let natureza = $('#natureza').val();
		let xmlEntrada = $('#xmlEntrada').val();
		let fornecedorId = $('#idFornecedor').val();
		let nNf = $('#nNf').val();
		let vDesc = $('#vDesc').val();
		let vFrete = $('#valor_frete').val();
		let totalNF = $('#totalNF').val();
		let obs = $('#obs').val();
		let motivo = $('#motivo').val();
		let tipo = $('#tipo').val();
		let transportadora_id = $('#transportadora_id').val();
		let transportadora = JSON.parse($('#transportadora').val());

		let tipoFrete = $('#tipo_frete').val();
		let ufPlaca = $('#uf_placa').val();
		let placa = $('#placa').val();
		let qtd = $('#qtd').val();
		let especie = $('#especie').val();
		let pBruto = $('#peso_bruto').val();
		let pLiquido = $('#peso_liquido').val();
		let vOutros = $('#valor_outros').val();

		let data = {
			natureza: natureza,
			xmlEntrada: xmlEntrada.substring(0, 44),
			fornecedorId: fornecedorId,
			nNf: nNf,
			vDesc: vDesc,
			vFrete: vFrete,
			itens: ITENS,
			devolucao_parcial: SOMAITENS != totalNF,
			valor_integral: totalNF,
			valor_devolvido: SOMAITENS,
			motivo: motivo,
			obs: obs,
			transportadora_id: transportadora_id,
			transportadora: transportadora,
			tipo: tipo,
			tipoFrete: tipoFrete,
			ufPlaca: ufPlaca,
			placa: placa,
			qtd : qtd,
			especie : especie,
			pBruto : pBruto,
			pLiquido : pLiquido,
			vOutros: vOutros
		};
		let token = $('#_token').val();

		$.post(path+'devolucao/salvar', {_token: token, data: data})
		.done((success) => {

			$('#preloader2').css('display', 'none');
			sucesso();
		})
		.fail((err) => {
			console.log(err)
			$('#preloader2').css('display', 'none');
			swal("Ops!!", "Erro ao salvar devolução!", "error")
			$('.modal-loading').css('display', 'none')

		})

		$('#salvar-devolucao').removeAttr('disabled')
	}

})

function sucesso(){
	audioSuccess()
	$('#content').css('display', 'none');
	$('#anime').css('display', 'block');
	setTimeout(() => {
		location.href = path+'devolucao';
	}, 4000)
}

function salvarTransportadora(){
	let js = {
		razao_social: $('#razao_social3').val(),
		logradouro: $('#logradouro3').val(),
		cpf_cnpj: $('#cpf_cnpj3').val(),
		numero: $('#numero3').val() ? $('#numero3').val() : '',
		cidade_id: $('#kt_select2_10').val(),
		telefone: $('#telefone3').val() ? $('#telefone3').val() : '',
		email: $('#email3').val() ? $('#email3').val() : '',
	}

	if(js.razao_social == ''){
		swal("Erro", "Informe a razão social", "warning")
	}else if(js.logradouro == ''){
		swal("Erro", "Informe o logradouro", "warning")
	}else if(js.cpf_cnpj == ''){
		swal("Erro", "Informe o CPF/CNPJ", "warning")
	}

	else{
		let token = $('#_token').val();
		$.post(path + 'transportadoras/quickSave',
		{
			_token: token,
			data: js
		})
		.done((res) =>{

			$('#transportadora_id').append('<option value="'+res.id+'">'+ 
				res.razao_social+'</option>').change();
			$('#transportadora_id').val(res.id).change();
			swal("Sucesso", "Transportadora adicionada!!", 'success')
			.then(() => {
				$('#modal-transportadora').modal('hide')
			})
		})
		.fail((err) => {
			console.log(err)
			swal("Alerta", err.responseJSON, "warning")
		})

	}

	console.log(js)
}

function consultaCadastro3() {
	let cnpj = $('#cpf_cnpj3').val();
	cnpj = cnpj.replace(/[^0-9]/g,'')

	if (cnpj.length == 14) {
		$('#btn-consulta-cadastro3').addClass('spinner')

		$.get('https://publica.cnpj.ws/cnpj/' + cnpj)
		.done((data) => {
			$('#btn-consulta-cadastro3').removeClass('spinner')
			console.log(data)
			if (data!= null) {
				let ie = ''
				if (data.estabelecimento.inscricoes_estaduais.length > 0) {
					ie = data.estabelecimento.inscricoes_estaduais[0].inscricao_estadual
				}

				$('#razao_social3').val(data.razao_social)
				$("#logradouro3").val(data.estabelecimento.tipo_logradouro + " " + data.estabelecimento.logradouro)
				$('#numero3').val(data.estabelecimento.numero)

				findCidadeCodigo3(data.estabelecimento.cidade.ibge_id)

			}
		})
		.fail((err) => {
			$('#btn-consulta-cadastro3').removeClass('spinner')
			console.log(err)
		})

	}else{
		swal("Alerta", "Informe corretamente o CNPJ", "warning")
	}
}

function findCidadeCodigo3(codigo_ibge){

	$.get(path + "cidades/cidadePorCodigoIbge/" + codigo_ibge)
	.done((res) => {
		console.log(res)
		$('#kt_select2_10').val(res.id).change();
	})
	.fail((err) => {
		console.log(err)
	})

}




