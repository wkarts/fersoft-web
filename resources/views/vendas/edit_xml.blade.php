@extends('default.layout')
@section('css')
<style type="text/css">
	textarea{
		width: 100%;
		height: 300px;
	}
</style>
@endsection
@section('content')

<div class="card card-custom gutter-b">
	<div class="card-body">

		<div class="@if(env('ANIMACAO')) animate__animated @endif animate__bounce" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

			<h4>Cliente: <strong class="text-success">{{ $item->cliente->razao_social }}</strong></h4>
			<h4>Total de produtos: <strong class="text-success">{{ sizeof($item->itens) }}</strong></h4>
			<h4>Valor total: <strong class="text-success">R$ {{ moeda($item->valor_total+$item->acrescimo-$item->desconto) }}</strong></h4>
			@if($item->estado == 'DISPONIVEL')
			<span class="label label-xl label-inline label-light-primary">Disponível</span>

			@elseif($item->estado == 'APROVADO')
			<span class="label label-xl label-inline label-light-success">Aprovado</span>
			@elseif($item->estado == 'CANCELADO')
			<span class="label label-xl label-inline label-light-danger">Cancelado</span>
			@else
			<span class="label label-xl label-inline label-light-warning">Rejeitado</span>
			@endif
			<div class="row mt-4">
				@if($item->estado == 'DISPONIVEL' || $item->estado == 'REJEITADO')
				<div class="col-12">
					<textarea class="form-control" id="textarea-xml" rows="25"></textarea>
				</div>
				<div class="col-12 mt-4">
					<button type="button" class="btn btn-success btn-enviar spinner-white spinner-right" onclick="enviar()">
						<i class="la la-send"></i>
						Enviar
					</button>
				</div>

				@endif

				<input type="hidden" value="{{$xml}}" id="xml">
			</div>
		</div>
	</div>
</div>

@endsection
@section('javascript')
<script type="text/javascript">
	$(function(){
		let xml = formatXml($('#xml').val())
		$('#textarea-xml').val(xml)
	})
	function formatXml(xml) {
		var formatted = '';
		var reg = /(>)(<)(\/*)/g;
		xml = xml.replace(reg, '$1\r\n$2$3');
		var pad = 0;
		jQuery.each(xml.split('\r\n'), function(index, node) {
			var indent = 0;
			if (node.match( /.+<\/\w[^>]*>$/ )) {
				indent = 0;
			} else if (node.match( /^<\/\w/ )) {
				if (pad != 0) {
					pad -= 1;
				}
			} else if (node.match( /^<\w[^>]*[^\/]>.*$/ )) {
				indent = 1;
			} else {
				indent = 0;
			}

			var padding = '';
			for (var i = 0; i < pad; i++) {
				padding += '  ';
			}

			formatted += padding + node + '\r\n';
			pad += indent;
		});

		return formatted;
	}

	var EMITINDO = false;
	function enviar(){
	// $('#preloader1').css('display', 'block');
	if(!EMITINDO){

		EMITINDO = true;
		$('.btn-enviar').addClass('spinner')
		$('.btn-enviar').attr('disabled', 'disabled');

		let xml = $('#textarea-xml').val()
		xml = xml.replaceAll("\n", "")
		// xml = xml.replaceAll(" ", "")
		let js = {
			id: {{$item->id}},
			_token: '{{csrf_token()}}',
			xml: xml,
		}
		console.clear()

		$.ajax
		({
			type: 'POST',
			data: js,
			url: path + 'nf/gerarNfWithXml',
			dataType: 'json',
			success: function(e){
				EMITINDO = false;
				let recibo = e;
				let retorno = recibo.substring(0,4);
				let mensagem = recibo.substring(5,recibo.length);
				if(retorno == 'Erro'){
					try{
						let m = JSON.parse(mensagem);
						try{
							swal("Erro", "[" + m.protNFe.infProt.cStat + "] : " + m.protNFe.infProt.xMotivo, "error")
						}catch{
							swal("Erro", "[" + m.cStat + "] : " + m.xMotivo, "error")
						}
					}catch{
						swal("Erro", mensagem, "error")
					}
				}
				else if(e == 'Apro'){
					swal("Cuidado!", "Esta NFe já esta aprovada, não é possível enviar novamente!", "warning")

				}
				else{
					
					swal({
						title: "Sucesso",
						text: "NFe gerada com sucesso RECIBO: "+recibo+", deseja enviar email para o cliente?",
						icon: "success",
						buttons: ["Não", 'Sim'],
						dangerMode: true,
					}).then((v) => {

						window.open(path+"nf/imprimir/{{$item->id}}", "_blank");
						location.reload();
					});
				}


				$('.btn-enviar').removeClass('spinner')
				$('.btn-enviar').removeAttr('disabled');

			}, error: function(e){
				EMITINDO = false;
				try{
					let js = e.responseJSON;

					if(js.message){

						swal("Erro!", js.message, "warning")

					}else if(e.status == 407){
						swal("Algo deu errrado", js, "error")

					}else{
						let err = "";
						js.map((v) => {
							err += v + "\n";
						});

						swal("Erro!", err, "warning")

					}
				}catch{
					console.log(e)
					swal("", e.responseText, "warning")

				}

				$('.btn-enviar').removeAttr('disabled');
				$('.btn-enviar').removeClass('spinner')

			}
		});
	}
}
</script>
@endsection