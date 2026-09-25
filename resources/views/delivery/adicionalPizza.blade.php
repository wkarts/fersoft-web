@extends('delivery.default')
@section('content')

<!-- Tela de Sucesso (Animação Lottie) -->
<div class="row" id="anime" style="display: none;">
	<div class="col-12 col-md-4 offset-md-4 text-center py-5">
		<lottie-player src="/anime/success.json" background="transparent" speed="0.8" style="width: 100%; height: auto;" autoplay></lottie-player>
	</div>
</div>

<div id="content" style="display: block; padding-bottom: 140px;">
	<section class="py-4">
		<div class="container">
			
            <!-- Variáveis Ocultas (Motor do Carrinho) -->
            <input type="hidden" value="{{json_encode($sabores)}}" id="sabores">
			<input type="hidden" value="{{$tamanho}}" id="tamanho">
            <input type="hidden" id="maximo_adicionais_pizza" value="{{$config->maximo_adicionais_pizza}}">
            <input type="hidden" id="_token" value="{{ csrf_token() }}">

			<!-- RESUMO DA PIZZA (Visual Limpo em formato de Card) -->
			<div class="card-moderno mb-4">
				<h2 style="font-size: 20px; font-weight: 800; margin-bottom: 12px; color: var(--cor-texto-escuro);">
					Personalize sua Pizza
				</h2>
                
                <!-- Sabores escolhidos (Exibidos como Etiquetas/Tags) -->
                <div style="margin-bottom: 15px;">
                    @foreach($saboresIncluidos as $key => $s)
                        <span style="display: inline-block; background-color: #f1f5f9; color: #475569; padding: 5px 12px; border-radius: 6px; font-size: 14px; font-weight: bold; margin-right: 5px; margin-bottom: 5px; border: 1px solid #e2e8f0;">
                            {{$s['produto']['nome']}}
                        </span>
                    @endforeach
                </div>

				<span style="color: #10b981; font-weight: 800; font-size: 22px; display: block;">
					R$ <span id="valor_produto">{{number_format($maiorValor, 2, '.', '')}}</span>
				</span>

                @if(env("DIVISAO_VALOR_PIZZA") == 0)
                    <p style="font-size: 13px; color: #94a3b8; background-color: #f8fafc; padding: 10px; border-radius: 8px; margin-top: 10px; margin-bottom: 0;">
                        <i class="fa fa-info-circle mr-1 text-primary"></i> <strong>Atenção:</strong> Permanece o preço do sabor com o maior valor.
                    </p>
                @endif
			</div>

			<!-- TÍTULO DA SEÇÃO DE ADICIONAIS E BOTÃO LIMPAR -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 style="font-size: 15px; font-weight: 700; color: var(--cor-texto-claro); margin: 0; text-transform: uppercase; letter-spacing: 0.5px;">
                    Adicionais Extras
                </h3>
                
                <!-- Botão de Remover (Agora limpo e discreto) -->
                <button onclick="location.reload()" class="btn btn-sm" style="background: transparent; color: #ef4444; font-size: 13px; font-weight: bold; padding: 0; box-shadow: none;">
                    <i class="fa fa-trash mr-1"></i> Limpar Seleção
                </button>
            </div>

			@if(count($adicionais) > 0)
				<div class="row">
					@foreach($adicionais as $a)
                        <div class="col-12 col-md-6 mb-3" onclick="selet_add({{$a->complemento}})">
                            <div class="card-moderno" id="adicional_{{$a->complemento->id}}" style="padding: 15px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; transition: all 0.2s ease;">
                                <span style="font-size: 16px; font-weight: bold; color: var(--cor-texto-escuro);">
                                    {{$a->complemento->nome()}}
                                </span>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span style="font-size: 15px; font-weight: 700; color: var(--cor-texto-claro);">
                                        + R$ {{number_format($a->complemento->valor, 2, ',', '.')}}
                                    </span>
                                    <i class="fa fa-check-circle check-icon"></i>
                                </div>
                            </div>
                        </div>
                    @endforeach
				</div>
			@else
				<div class="card-moderno text-center py-4">
					<p style="margin: 0; color: var(--cor-texto-claro); font-weight: 500;">Nenhum adicional disponível para este tamanho.</p>
				</div>
			@endif

			<!-- CAMPO DE OBSERVAÇÃO -->
			<div class="card-moderno mt-4" style="padding: 15px;">
				<h4 style="font-size: 15px; font-weight: 700; margin-bottom: 10px;">Alguma observação para a pizza?</h4>
				<input type="text" class="form-control" id="observacao" placeholder="Ex: Metade sem cebola, borda bem assada...">
			</div>

		</div>
	</section>
</div>

<!-- BARRA FIXA DO RODAPÉ (Padrão iFood para Finalizar) -->
<div style="position: fixed; bottom: 0; left: 0; width: 100%; background: white; padding: 15px; box-shadow: 0 -5px 20px rgba(0,0,0,0.08); z-index: 1000; border-top: 1px solid #f1f5f9;">
	<div class="container">
		<div class="row align-items-center">
			
			<!-- Controle de Quantidade Elegante -->
			<div class="col-4 col-md-3">
				<div class="d-flex align-items-center justify-content-center" style="background: #f1f5f9; border-radius: 12px; height: 50px; padding: 5px;">
					<input type="number" class="text-center" value="1" id="quantidade" style="border: none !important; background: transparent !important; font-weight: 800; font-size: 18px; width: 100%; padding: 0 !important; margin: 0 !important;">
				</div>
			</div>

			<!-- Botão Adicionar Principal -->
			<div class="col-8 col-md-9">
				<button onclick="adicionar()" type="button" class="btn-principal" 
                        style="width: 100%; height: 65px; font-size: 20px; font-weight: 900; background: #ea1d2c; border-radius: 12px; color: white;">
                    <span><i class="fa fa-cart-plus mr-2"></i> ADICIONAR</span>
                    <span style="margin-left: 10px;">R$ <strong id="valor_total">{{number_format($maiorValor, 2, '.', '')}}</strong></span>
                </button>
			</div>

		</div>
	</div>
</div>
<script type="text/javascript">
    let valorBase = parseFloat("{{$maiorValor}}");
    let valorTotal = valorBase;

    function selet_add(adicional) {
        const card = document.getElementById('adicional_' + adicional.id);
        const valorAdicional = parseFloat(adicional.valor);
        
        // Pega o texto (nome do adicional) dentro do card para mudar a cor dele também
        const textoCard = card.querySelector('span');

        // Alterna a classe de destaque
        if (card.classList.contains('adicional-selecionado')) {
            card.classList.remove('adicional-selecionado');
            valorTotal -= valorAdicional;
            // Volta a cor do texto para o original (escuro)
            if(textoCard) textoCard.style.color = 'var(--cor-texto-escuro)';
        } else {
            card.classList.add('adicional-selecionado');
            valorTotal += valorAdicional;
            // Muda a cor do texto para vermelho quando selecionado
            if(textoCard) textoCard.style.color = '#ea1d2c';
        }

        // Atualiza o valor no botão
        document.getElementById('valor_total').innerText = valorTotal.toFixed(2);
    }

    function adicionar() {
        // Lógica de envio para o seu carrinho (via AJAX ou Form)
        // Certifique-se de que a função 'adicionar()' envia o valorTotal atualizado
        console.log("Adicionando ao carrinho com o valor: " + valorTotal);
    }
</script>
<style>
    /* 1. Card padrão (neutro) */
    .card-moderno {
        border: 2px solid #e2e8f0 !important;
        background-color: #ffffff !important;
        cursor: pointer;
        transition: all 0.25s ease-in-out !important;
    }

    /* 2. O ESTADO "SELECIONADO" (Fundo marcante e borda viva) */
    .adicional-selecionado {
        border-color: #ea1d2c !important; /* Vermelho vibrante */
        background-color: #fff1f2 !important; /* Fundo rosa bem clarinho */
        box-shadow: 0 4px 10px rgba(234, 29, 44, 0.15) !important;
    }
    
    /* 3. Ícone de check (Sempre visível no selecionado) */
    .check-icon {
        display: none;
        color: #ea1d2c;
        font-size: 22px;
        font-weight: 900;
    }
    .adicional-selecionado .check-icon {
        display: block;
    }
</style>
@endsection