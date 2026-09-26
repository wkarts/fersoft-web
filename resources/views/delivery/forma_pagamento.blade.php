@extends('delivery.default')
@section('content')

<meta name="csrf-token" content="{{ csrf_token() }}">

<style type="text/css">
    #map {
        width: 100%;
        height: 350px;
        background: #e2e8f0;
        border-radius: 12px;
    }

    /* Efeito de Seleção nos Cards de Endereço e Pagamento */
    .card-selecionavel {
        cursor: pointer;
        transition: all 0.2s ease;
        border: 2px solid #e2e8f0 !important;
        border-radius: 12px !important;
        background: #ffffff !important;
        margin-bottom: 15px;
    }
    
    .card-selecionavel:active {
        transform: scale(0.98);
    }

    /* Classe dinâmica de ativo injetada pelo sistema */
    .card-ativo {
        border-color: var(--cor-primaria) !important;
        background-color: #fff5f5 !important;
        box-shadow: 0 4px 12px rgba(234, 29, 44, 0.08) !important;
    }

    /* Customização dos botões de opção de pagamento */
    .opcao-pagamento {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 15px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        margin-bottom: 12px;
        cursor: pointer;
        background: #fff;
        transition: all 0.2s ease;
    }

    .opcao-pagamento input[type="radio"] {
        width: 20px;
        height: 20px;
        accent-color: var(--cor-primaria);
        cursor: pointer;
    }
  
  /* Card padrão */
    .card-selecionavel {
        border: 2px solid #e2e8f0 !important;
        transition: all 0.2s ease;
    }
    /* Destaque visual forte quando selecionado */
    .card-selecionavel.ativo {
        border-color: #ea1d2c !important;
        background-color: #fff1f2 !important;
    }
  
</style>

<!-- Tela de Sucesso (Animação Lottie) -->
<div class="row" id="anime" style="display: none;">
	<div class="col-12 col-md-4 offset-md-4 text-center py-5">
		<lottie-player src="/anime/finish{{rand(1,4)}}.json" background="transparent" speed="0.8" style="width: 100%; height: auto;" autoplay></lottie-player>
	</div>
</div>

<div id="content" style="display: block; padding-top: 20px; padding-bottom: 140px;">
	
    @csrf
    <input type="hidden" id="_token" value="{{ csrf_token() }}">
  
  	<!-- Inputs do Motor Técnico original -->
    <input type="hidden" id="email-cliente" value="{{$cliente->email}}">
    <input type="hidden" id="lat_padrao" value="{{env('LATITUDE_PADRAO')}}">
    <input type="hidden" id="lng_padrao" value="{{env('LONGITUDE_PADRAO')}}">
    <input type="hidden" id="usar_bairros" value="{{$usar_bairros}}">
    <input type="hidden" id="pedido_id" value="{{$pedido->id}}">
	<input type="hidden" id="total-init" value="{{$total}}">
    <input type="hidden" id="endereco_selecionado" value="">

    <div class="container">
        
        <!-- SEÇÃO 1: FORMA DE ENTREGA -->
        <div class="card-moderno">
            <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 15px;"><i class="fa fa-map-marker text-danger"></i> Forma de Entrega</h3>
            
            <!-- Alerta de Bairros Atendidos Computado -->
            <div class="alert alert-info" style="border-radius: 8px; font-size: 13px; background-color: #f0f9ff; border: 1px solid #bae6fd; color: #0369a1;">
                <strong style="display: block; margin-bottom: 4px;"><i class="fa fa-motorcycle"></i> Bairros Atendidos:</strong>
                @if(isset($bairros) && count($bairros) > 0)
                    {{ implode(', ', $bairros->pluck('nome')->toArray()) }}.
                @else
                    Todos os bairros da região (consulte a taxa digitando seu endereço).
                @endif
                <br>Se o seu bairro não estiver na cobertura, a taxa será R$ 0,00 e o pedido poderá ser cancelado pela loja.
            </div>

            @if(count($enderecos) > 0)
                <p style="font-size: 13px; font-weight: bold; color: var(--cor-texto-claro); text-transform: uppercase; margin-bottom: 10px;">Seus Endereços Cadastrados</p>
            @else
                <p style="font-size: 14px;" class="text-secondary"><i class="fa fa-exclamation-triangle text-warning"></i> Você ainda não possui endereços cadastrados.</p>
            @endif

            <!-- Grid de Endereços Inteligente -->
            <div class="row">
                @foreach($enderecos as $e)
                    <div class="col-12 col-md-6" onclick="set_endereco({{$e->id}})">
                        <div id="endereco_select_{{$e->id}}" class="card-selecionavel p-3">
                            <h5 style="font-size: 16px; font-weight: 800; margin-bottom: 2px;">{{$e->rua}}, {{$e->numero}}</h5>
                            <p style="font-size: 14px; margin: 0; color: var(--cor-texto-claro);">{{$e->bairro}}</p>
                            <small style="color: #94a3b8; display: block; margin-top: 4px;">Ref: {{$e->referencia}}</small>
                        </div>
                    </div>
                @endforeach

                <!-- Opção Retirar no Balcão -->
                <div class="col-12 col-md-6" onclick="set_endereco('balcao')">
                    <div id="endereco_select_balcao" class="card-selecionavel p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <h5 style="font-size: 16px; font-weight: 800; margin: 0;">Retirar no Balcão</h5>
                            <p style="font-size: 13px; margin: 0; color: var(--cor-texto-claro);">Você busca diretamente na nossa loja</p>
                        </div>
                        <i class="fa fa-shopping-basket text-secondary" style="font-size: 20px;"></i>
                    </div>
                </div>
            </div>

            <a href="#gal2" id="novo-endereco" class="btn btn-outline-danger btn-block mt-2" style="border: 2px solid var(--cor-primaria) !important; background: transparent !important; color: var(--cor-primaria) !important;">
                <i class="fa fa-plus"></i> Adicionar Novo Endereço
            </a>
        </div>

        <!-- Avisos de Distância ou Acréscimo Fretado -->
        <div id="acrescimo-entrega" style="display: none" class="card-moderno bg-light border-success p-3">
            <h4 style="font-size: 16px; margin: 0;">Acréscimo de entrega: <strong id="valor-entrega" style="color: var(--cor-primaria);">R$ 0,00</strong></h4>
            <h5 id="frete-gratuito" style="margin: 5px 0 0 0; display: none; color: #10b981; font-size: 14px; font-weight: bold;"><i class="fa fa-gift"></i> Seu frete é gratuito para este endereço!</h5>
        </div>
        <h5 id="entrega-distante" style="display: none; color: #ef4444; font-size: 14px; font-weight: bold;" class="alert alert-danger text-center"><i class="fa fa-ban"></i> Este endereço excede o limite de nossas entregas: máximo {{$maximo_km_entrega}} KM</h5>

        <!-- SEÇÃO 2: DADOS DE IDENTIFICAÇÃO (BLINDADO E BONITO) -->
        <div class="card-moderno" style="border-left: 5px solid var(--cor-primaria) !important;">
            <h4 style="font-size: 16px; font-weight: 800; margin-bottom: 12px; color: var(--cor-primaria);"><i class="fa fa-user-circle"></i> Dados de Identificação</h4>
            
            <div class="form-group mb-3">
                <label style="font-weight: bold; font-size: 14px; color: var(--cor-texto-escuro);">Nome de quem vai receber *</label>
                <input id="nome-cliente" type="text" value="{{ (isset($cliente) && $cliente->nome != 'Cliente') ? $cliente->nome . ' ' . $cliente->sobre_nome : '' }}" placeholder="Digite seu nome completo...">
            </div>

            <div class="form-group mb-3">
                <label style="font-weight: bold; font-size: 14px; color: var(--cor-texto-escuro);">Celular para contato *</label>
                <input type="text" value="{{$ultimoPedido != null ? $ultimoPedido->telefone : $cliente->celular}}" id="telefone" placeholder="(71) 99999-9999">
            </div>

            <div class="form-group mb-0">
                <label style="font-weight: bold; font-size: 14px; color: var(--cor-texto-escuro);">Observação do Pedido (opcional)</label>
                <textarea class="form-control" id="observacao" rows="2" placeholder="Ex: Tocar a campainha, deixar na portaria..."></textarea>
            </div>
        </div>

        <!-- SEÇÃO 3: CUPOM DE DESCONTO -->
        <div class="card-moderno">
            <h4 style="font-size: 15px; font-weight: 800; margin-bottom: 10px;"><i class="fa fa-ticket text-warning"></i> Possui Cupom de Desconto?</h4>
            <div class="d-flex gap-2">
                <input type="text" id="cupom" value="{{$cupom > 0 ? $cupom : ''}}" placeholder="Digite o código do cupom..." style="flex-grow: 1;">
            </div>
            <div id="desconto" style="display: none" class="mt-2 text-success font-weight-bold">
                <i class="fa fa-check"></i> Valor do cupom: <strong id="valor-cupom"></strong>
            </div>
            <div id="cupom-invalido" style="display: none" class="mt-2 text-danger font-weight-bold">
                <i class="fa fa-times"></i> Cupom inválido ou expirado.
            </div>
        </div>

        <!-- SEÇÃO 4: FORMA DE PAGAMENTO (CARDS EXCLUSIVOS) -->
        <div class="card-moderno">
            <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 15px;"><i class="fa fa-credit-card text-success"></i> Forma de Pagamento</h3>
            
            <fieldset class="form-group mb-0">
                <!-- Maquineta -->
                <label class="opcao-pagamento" for="maquineta">
                    <div class="d-flex align-items-center gap-3">
                        <input type="radio" name="gridRadios" id="maquineta" value="maquineta">
                        <span style="font-weight: bold; font-size: 15px;">Máquina de Cartão (Crédito/Débito)</span>
                    </div>
                    <img width="30" src="/imgs/credit-card.png">
                </label>
              
              <label class="opcao-pagamento" for="pix">
                      <div class="d-flex align-items-center gap-3">
                          <input type="radio" name="gridRadios" id="pix" value="pix">
                          <span style="font-weight: bold; font-size: 15px;">Pix</span>
                      </div>
                      <img width="30" src="/imgs/pix-logo.png"> </label>

                            
                <!-- Dinheiro -->
                <label class="opcao-pagamento" for="dinheiro" style="flex-direction: column; align-items: stretch; gap: 0;">
                    <div class="d-flex align-items-center justify-content-between" style="width: 100%;">
                        <div class="d-flex align-items-center gap-3">
                            <input type="radio" name="gridRadios" id="dinheiro" value="dinheiro">
                            <span style="font-weight: bold; font-size: 15px;">Dinheiro / Cédula</span>
                        </div>
                        <img width="35" src="/imgs/100-real.png">
                    </div>
                    
                    <!-- Campo de Troco Oculto que abre com elegância -->
                    <div id="div_do_troco" style="display: none; margin-top: 15px; border-top: 1px dashed #cbd5e1; padding-top: 15px;">
                        <label style="font-size: 13px; font-weight: bold; color: var(--cor-texto-claro);">Vai precisar de troco? Para quanto?</label>
                        <input type="text" placeholder="Ex: {{ $total%10 == 0 ? $total : ((int)($total/10) +1)*10}},00" id="troco_para" style="max-width: 150px;">
                    </div>
                </label>

                <!-- PagSeguro Online integrado se ativo -->
                @if($pagseguroAtivado == true)
                    <label class="opcao-pagamento" for="pagseguro">
                        <div class="d-flex align-items-center gap-3">
                            <input type="radio" name="gridRadios" id="pagseguro" value="pagseguro">
                            <span style="font-weight: bold; font-size: 15px;">Pagar Online com Cartão</span>
                        </div>
                        <img width="30" src="/imgs/debit-card.png">
                    </label>
                @endif
            </fieldset>
        </div>

    </div>
</div>

<!-- BARRA FIXA DE FECHAMENTO (IGUAL IFOOD/RAPPI) -->
<div style="position: fixed; bottom: 0; left: 0; width: 100%; background: white; padding: 15px; box-shadow: 0 -5px 20px rgba(0,0,0,0.08); z-index: 1000; border-top: 1px solid #f1f5f9;">
    <div class="container">
        <div class="row mt-2 mb-3" id="acrescimo-entrega-resumo" style="display: none; font-size: 14px; color: var(--cor-texto-claro);">
            <div class="col-12 d-flex justify-content-between">
                <span>Taxa de Entrega:</span>
                <span class="text-danger font-weight-bold">R$ <span id="valor-entrega-resumo">0,00</span></span>
            </div>
        </div>
        <div class="row align-items-center">
    <div class="col-12">
        <a href="javascript:void(0)" id="finalizar-venda" 
           style="display: flex; justify-content: space-between; align-items: center; background: #10b981; color: white; padding: 18px 25px; border-radius: 12px; font-weight: 900; font-size: 19px; text-decoration: none; width: 100%; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);">
            
            <button class="btn btn-success btn-lg btn-block" style="padding: 20px; font-size: 20px;">
                <i class="fa fa-check-circle"></i> CONFIRMAR E FINALIZAR R$ {{ number_format($total, 2, ',', '.') }}
            </button>

            <strong id="total-resumo" style="color: white; font-size: 20px;">
                R$ {{ number_format($total, 2, ',', '.') }}
            </strong>
        </a>
    </div>
</div>
    </div>
</div>

<!-- MODAL MAPA / ENDEREÇO (BLINDADO) -->
<div id="gal2" class="pop-overlay">
	<div id="endereco-modal" class="popup" style="border-radius: 16px; padding: 20px;">
		<div id="info-mapa">
			<p style="font-weight: bold; margin-bottom: 10px; text-center"><i class="fa fa-map"></i> Arraste o marcador até a sua casa:</p>
			<div id="map" class="mb-3"></div>
			<a style="color: #fff" id="btn-end-map" class="btn btn-success btn-block">Confirmar Localização</a>
		</div>

		<div id="form-endereco" style="display: none">
			<a style="color: #fff" id="abrir-mapa" class="btn btn-primary btn-block mb-3"><i class="fa fa-map-marker"></i> Corrigir Pelo Mapa</a>
			
            <div class="form-group mb-2">
				<label>CEP</label>
				<input type="text" id="cep" placeholder="00000-000">
			</div>
            <div class="form-group mb-2">
				<label>Rua</label>
				<input type="text" id="rua" placeholder="Nome da rua...">
			</div>
			<div class="form-group mb-2">
				<label>Número</label>
				<input type="text" id="numero" placeholder="Nº">
			</div>

			@if($usar_bairros == 1)
                <div class="form-group mb-2">
                    <label>Bairro</label>
                    <select id="bairro" class="form-control">
                        <option value="" disabled selected hidden>Selecione o seu bairro...</option>
                        @foreach($bairros as $b)
                            <option value="id:{{$b->id}}">{{$b->nome}} (+ R$ {{number_format($b->valor_entrega, 2, ',', '.')}})</option>
                        @endforeach
                    </select>
                </div>
			@else
                <div class="form-group mb-2">
                    <label>Bairro</label>
                    <input type="text" id="bairro" placeholder="Digite seu bairro...">
                </div>
			@endif

			<div class="form-group mb-3">
				<label>Referência ou Apartamento</label>
				<input type="text" id="referencia" placeholder="Ex: Apt 302, bloco B, próximo ao mercado...">
			</div>
			<a href="#!" id="salvar_endereco" class="btn btn-danger btn-block disabled">Salvar Endereço</a>
		</div>
		<a class="close" href="#!">×</a>
	</div>
</div>

<!-- MODAL PAGSEGUURO (MODERNIZADO) -->
<div id="modal-pagseguro" class="pop-overlay">
	<div style="overflow-y: auto; border-radius: 16px; padding: 20px;" class="popup">
		<div id="div-cartao-antigo" @if(sizeof($cartoes) > 0) style="display: block" @else style="display: none" @endif>
			<h6 class="font-weight-bold mb-3">Cartões salvos anteriormente:</h6>
			@foreach($cartoes as $c)
                <div class="card p-2 mb-2 bg-white border" style="border-radius: 8px;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <input class="form-check-input" type="radio" name="escolha-cartao" value="{{$c}}">
                            <img width="50" src="https://stc.pagseguro.uol.com.br/public/img/payment-methods-flags/68x30/{{$c['bandeira']}}.png">
                        </div>
                        <div class="text-right" style="font-size: 13px;">
                            <strong>{{$c['numero_cartao']}}</strong><br>
                            <span class="text-muted">CPF: {{$c['cpf']}}</span>
                        </div>
                    </div>
                </div>
			@endforeach
			<hr>
			<label class="d-flex align-items-center gap-3 p-2 border rounded bg-light" style="cursor: pointer;">
				<input class="form-check-input" type="radio" name="escolha-cartao" value="null">
				<span class="font-weight-bold text-dark">Utilizar um Novo Cartão</span>
			</label>
		</div><br>

		<div class="modal-body p-0" id="div-pagar" @if(sizeof($cartoes) > 0) style="display: none" @else style="display: block" @endif style="width: 100%;">
			<div class="card-wrapper mb-3" style="margin: 0 auto;"></div>
			
			<form>
				<div class="form-group mb-2"><label>Número do Cartão</label><input type="tel" id="number" name="number"></div>
				<div class="form-group mb-2"><label>Nome Impresso no Cartão</label><input type="text" value="{{$pedido->cliente->nome}} {{$pedido->cliente->sobre_nome}}" id="nome" name="name"></div>
				<div class="form-group mb-2"><label>CPF do Titular</label><input type="tel" id="cpf" name="cpf"></div>
				<div class="row">
					<div class="col-6 form-group mb-2"><label>Validade</label><input type="tel" placeholder="MM/AAAA" id="validade" name="validade"></div>
					<div class="col-6 form-group mb-2"><label>CVC</label><input type="tel" id="cvc" name="cvc"></div>
				</div>
				<div class="form-group mb-3"><label>Parcelamento</label><select id="fator" class="form-control"></select></div>

				<a style="color: #fff" type="button" id="finalizar-venda-cartao" class="btn btn-success btn-lg btn-block">
					<i class="fa fa-check"></i> Pagar Agora <i id="icon-spin" style="display: none;" class="fa fa-spinner fa-spin"></i>
				</a>
			</form>
		</div>
		<a class="close" href="#!">×</a>
		<a style="margin-right: 30px;" class="close" id="voltar"><i class="fa fa-arrow-left" style="font-size: 16px; margin-top: 5px;"></i></a>
	</div>
</div>
<script type="text/javascript">
    // 1. Configurações de Token
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
    if (typeof window.axios !== 'undefined') {
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = '{{ csrf_token() }}';
    }

    // Função global que estava a ser bloqueada
    function set_endereco(id) {
        document.querySelectorAll('.card-selecionavel').forEach(el => el.classList.remove('ativo'));
        document.getElementById('endereco_select_' + id).classList.add('ativo');
        // Adicione aqui a sua lógica original de salvar o endereço selecionado do FerSoft
    }

    document.addEventListener("DOMContentLoaded", function() {
        
        // --- LÓGICA DO BOTÃO SALVAR ENDEREÇO (VALIDAÇÃO EM TEMPO REAL) ---
        const btnSalvar = document.getElementById('salvar_endereco');
        const inputsEnd = ['rua', 'numero', 'bairro'];
        
        inputsEnd.forEach(id => {
            const el = document.getElementById(id);
            if(el) {
                el.addEventListener('input', function() {
                    const rua = document.getElementById('rua').value;
                    const numero = document.getElementById('numero').value;
                    // Se houver select de bairro, verifica ele também
                    const bairroSelect = document.getElementById('bairro');
                    const bairroVal = bairroSelect ? bairroSelect.value : document.getElementById('bairro').value;
                    
                    if(rua.length > 3 && numero.length > 0 && bairroVal.length > 0) {
                        btnSalvar.classList.remove('disabled');
                    } else {
                        btnSalvar.classList.add('disabled');
                    }
                });
            }
        });

        // --- LÓGICA VISUAL DE PAGAMENTO ---
        document.querySelectorAll('.opcao-pagamento').forEach(function(el) {
            el.addEventListener('click', function() {
                const radio = this.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                    radio.dispatchEvent(new Event('change'));
                    const divTroco = document.getElementById('div_do_troco');
                    if (divTroco) divTroco.style.display = (radio.value === 'dinheiro') ? 'block' : 'none';
                }
            });
        });
    });
</script>
@endsection