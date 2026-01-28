@extends('ecommerce.default')
@section('content')

<section class="featured">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="section-title">
                    <h2>Formas de Pagamento</h2>
                </div>
                <input type="hidden" id="totais" value="{{json_encode($totais)}}" name="">
                <div class="section-title">
                    <h2 class="total-h2">R$ {{number_format($totais->total_cartao, 2, ',', '.')}}</h2>
                </div>
                <div class="featured__controls">
                    <ul>
                        @if(in_array('cartao', $formas_pagamento))
                        <li id="click-cartao" class="@if($forma_inicial == 'cartao') active @endif div-cartao">Cartão de Crédito</li>
                        @endif

                        @if(in_array('pix', $formas_pagamento))
                        <li id="click-pix" class="@if($forma_inicial == 'pix') active @endif div-pix">PIX</li>
                        @endif

                        @if(in_array('boleto', $formas_pagamento))
                        <li id="click-boleto" class="@if($forma_inicial == 'boleto') active @endif div-boleto">Boleto</li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
        <div class="row featured__filter">

            @if(in_array('cartao', $formas_pagamento))
            <div class="col-12 mix cartao @if($forma_inicial != 'cartao') d-none @endif">
                <div class="row">
                    <div class="col-lg-12">
                        <h3>Pagamento com Cartão</h3>
                    </div>
                </div>
                <br>
                <form action="/ecommercePay/cartao" method="post" id="paymentForm">
                    @csrf
                    <div class="row">
                        <div class="col-lg-4 col-12">

                            <div class="checkout__input">
                                <p>Titular do cartão<span>*</span></p>
                                <input value="{{ old('cardholderName') }}" id="cardholderName" data-checkout="cardholderName" type="text">
                                @if($errors->has('cardholderName'))
                                <label class="text-danger">
                                    {{ $errors->first('cardholderName') }}
                                </label>
                                @endif
                            </div>
                        </div>

                        <div class="col-lg-2 col-12">
                            <div class="checkout__input">
                                <p>Tipo de documento<span></span></p>
                                <select class="form-control" style="width: 100%; height: 45px;" id="docType" name="docType" data-checkout="docType">
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-4 col-12">

                            <div class="checkout__input">
                                <p>Número do documento<span>*</span></p>
                                <input value="{{ old('docNumber') }}" id="docNumber" data-checkout="docNumber" name="docNumber" type="tel" class="cpf_cnpj">
                                @if($errors->has('docNumber'))
                                <label class="text-danger">{{ $errors->first('docNumber') }}</label>
                                @endif
                            </div>
                        </div>

                        <div class="col-lg-4 col-12">

                            <div class="checkout__input">
                                <p>Email<span>*</span></p>
                                <input value="{{ $cliente->email }}" value="{{ old('email') }}" id="email" name="email" type="text">
                                @if($errors->has('email'))
                                <label class="text-danger">{{ $errors->first('email') }}</label>
                                @endif
                            </div>
                        </div>

                        <div class="col-lg-6 col-12">

                            <div class="checkout__input">
                                <p>Número do cartão<span>*</span></p>
                                <input style="width: 90%;" data-checkout="cardNumber" value="{{ old('cardNumber') }}" id="cardNumber" type="text" > 
                                <img id="band-img" style="width: 20px;" src="">

                                @if($errors->has('cardNumber'))
                                <label class="text-danger">{{ $errors->first('cardNumber') }}</label>
                                @endif



                            </div>
                        </div>

                        <div class="col-lg-3 col-6">
                            <div class="checkout__input">
                                <p>Parcelas<span></span></p>
                                <select style="height: 45px;" class="form-control" id="installments" name="installments">
                                </select>
                            </div>
                        </div>


                        <div class="col-lg-4 col-12">

                            <div class="checkout__input">
                                <p>Data de Vencimento<span>*</span></p>

                                <div class="row">
                                    <div class="col-6">
                                        <input placeholder="MM" data-checkout="cardExpirationMonth" value="{{ old('cardExpirationMonth') }}" id="cardExpirationMonth" type="text">
                                        @if($errors->has('cardExpirationMonth'))
                                        <label class="text-danger">{{ $errors->first('cardExpirationMonth') }}</label>
                                        @endif
                                    </div>
                                    <div class="col-6">

                                        <input placeholder="AA" data-checkout="cardExpirationYear" value="{{ old('cardExpirationYear') }}" id="cardExpirationYear" type="text">
                                        @if($errors->has('cardExpirationYear'))
                                        <label class="text-danger">{{ $errors->first('cardExpirationYear') }}</label>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-2 col-12">

                            <div class="checkout__input">
                                <p>Código de segurança<span>*</span></p>
                                <input data-checkout="securityCode" value="{{ old('securityCode') }}" id="securityCode" type="text">
                                @if($errors->has('securityCode'))
                                <label class="text-danger">{{ $errors->first('securityCode') }}</label>
                                @endif
                            </div>
                        </div>


                        <div style="visibility: hidden" class="form-group col-lg-2 col-md-8 col-12">
                            <label class="col-form-label">Banco emissor</label>
                            <div class="">
                                <div class="input-group">
                                    <select class="custom-select" id="issuer" name="issuer" data-checkout="issuer">
                                    </select>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="total_pag" value="{{$totais->total_cartao}}">
                        <input style="visibility: hidden;" type="" name="transactionAmount" id="transactionAmount" value="{{$totais->total_cartao}}" />
                        <input style="visibility: hidden" value="{{$descricao}}" name="description">
                        <input style="visibility: hidden" name="paymentMethodId" id="paymentMethodId" />
                        <input type="hidden" value="{{$carrinho->id}}" name="carrinho_id">


                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <button type="submit" class="btn primary-btn cart_button_checkout">
                                <span class="spinner-border d-none" role="status" aria-hidden="true"></span>
                                PAGAR COM CARTÃO
                            </button>
                        </div>
                    </div>  
                </form>
            </div>
            @endif
            <!-- fim cartao -->
            @if(in_array('pix', $formas_pagamento))
            <div class="col-12 mix pix @if($forma_inicial != 'pix') d-none @endif">
                <div class="row">
                    <div class="col-lg-12">
                        <h3>Pagamento com PIX</h3>
                    </div>
                </div>
                <br>

                <form action="/ecommercePay/pix" method="post" id="paymentFormPix">
                    @csrf
                    <div class="row">
                        <div class="col-lg-4 col-12">

                            <div class="checkout__input">
                                <p>Nome<span>*</span></p>
                                <input value="{{$cliente->nome}}" name="payerFirstName" id="payerFirstName" type="text">
                                @if($errors->has('payerFirstName'))
                                <label class="text-danger">
                                    {{ $errors->first('payerFirstName') }}
                                </label>
                                @endif
                            </div>
                        </div>

                        <div class="col-lg-4 col-12">

                            <div class="checkout__input">
                                <p>Sobrenome<span>*</span></p>
                                <input value="{{$cliente->sobre_nome}}" name="payerLastName" id="payerLastName" type="text">
                                @if($errors->has('payerLastName'))
                                <label class="text-danger">
                                    {{ $errors->first('payerLastName') }}
                                </label>
                                @endif
                            </div>
                        </div>
                        <div class="col-lg-4 col-6"></div>

                        <div class="col-lg-2 col-12">
                            <div class="checkout__input">
                                <p>Tipo de documento<span></span></p>
                                <select name="docType" class="form-control" style="width: 100%; height: 45px;" id="docType2" data-checkout="docType">

                                </select>
                            </div>
                        </div>

                        <div class="col-lg-3 col-12">

                            <div class="checkout__input">
                                <p>Número do documento<span>*</span></p>
                                <input value="{{ old('docNumber') ? old('docNumber') : $cliente->cpf  }}" id="docNumber" data-checkout="docNumber" name="docNumber" type="tel" class="cpf_cnpj">
                                @if($errors->has('docNumber'))
                                <label class="text-danger">{{ $errors->first('docNumber') }}</label>
                                @endif
                            </div>
                        </div>

                        <div class="col-lg-3 col-12">

                            <div class="checkout__input">
                                <p>Email<span>*</span></p>
                                <input value="{{ $cliente->email }}" id="payerEmail" name="payerEmail" type="email">
                                @if($errors->has('payerEmail'))
                                <label class="text-danger">{{ $errors->first('payerEmail') }}</label>
                                @endif
                            </div>
                        </div>

                        <input type="hidden" name="total_pag" value="{{$totais->total_pix}}">
                        <input style="visibility: hidden" type="" name="transactionAmount" id="transactionAmount" value="{{$totais->total_pix}}" />
                        <input style="visibility: hidden" value="{{$descricao}}" name="description">
                        <input style="visibility: hidden" name="paymentMethodId" id="paymentMethodId" />
                        <input type="hidden" value="{{$carrinho->id}}" name="carrinho_id">

                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <button type="submit" class="btn primary-btn cart_button_checkout">
                                <span class="spinner-border d-none" role="status" aria-hidden="true"></span>
                                PAGAR COM PIX
                            </button>
                        </div>
                    </div> 
                </form>
            </div>
            <!-- fim pix -->
            @endif

            @if(in_array('boleto', $formas_pagamento))
            <div class="col-12 mix boleto @if($forma_inicial != 'boleto') d-none @endif">
                <div class="row">
                    <div class="col-lg-12">
                        <h3>Pagamento com Boleto</h3>
                    </div>
                </div>
                <br>
                <form action="/ecommercePay/boleto" method="post" id="paymentFormBoleto">
                    @csrf
                    <div class="row">
                        <div class="col-lg-4 col-12">

                            <div class="checkout__input">
                                <p>Nome<span>*</span></p>
                                <input value="{{ $cliente->nome }}" name="payerFirstName" id="payerFirstName" type="text">
                                @if($errors->has('payerFirstName'))
                                <label class="text-danger">
                                    {{ $errors->first('payerFirstName') }}
                                </label>
                                @endif
                            </div>
                        </div>

                        <div class="col-lg-4 col-12">

                            <div class="checkout__input">
                                <p>Sobrenome<span>*</span></p>
                                <input value="{{ $cliente->sobre_nome }}" name="payerLastName" id="payerLastName" type="text">
                                @if($errors->has('payerLastName'))
                                <label class="text-danger">
                                    {{ $errors->first('payerLastName') }}
                                </label>
                                @endif
                            </div>
                        </div>
                        <div class="col-lg-4 col-6"></div>

                        <div class="col-lg-2 col-12">
                            <div class="checkout__input">
                                <p>Tipo de documento<span></span></p>
                                <select name="docType" class="form-control" style="width: 100%; height: 45px;" id="docType3" data-checkout="docType">

                                </select>
                            </div>
                        </div>

                        <div class="col-lg-3 col-12">

                            <div class="checkout__input">
                                <p>Número do documento<span>*</span></p>
                                <input value="{{ old('docNumber') ? old('docNumber') : $cliente->cpf  }}" id="docNumber" data-checkout="docNumber" name="docNumber" type="tel" class="cpf_cnpj">
                                @if($errors->has('docNumber'))
                                <label class="text-danger">{{ $errors->first('docNumber') }}</label>
                                @endif
                            </div>
                        </div>

                        <div class="col-lg-3 col-12">

                            <div class="checkout__input">
                                <p>Email<span>*</span></p>
                                <input value="{{ $cliente->email }}" id="payerEmail" name="payerEmail" type="email">
                                @if($errors->has('payerEmail'))
                                <label class="text-danger">{{ $errors->first('payerEmail') }}</label>
                                @endif
                            </div>
                        </div>

                        <input type="hidden" name="total_pag" value="{{$totais->total_boleto}}">
                        <input style="visibility: hidden;" type="" name="transactionAmount" id="transactionAmount" value="{{$totais->total_boleto}}" />
                        <input style="visibility: hidden" value="{{$descricao}}" name="description">
                        <input style="visibility: hidden" name="paymentMethodId" id="paymentMethodId" />
                        <input type="hidden" value="{{$carrinho->id}}" name="carrinho_id">

                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <button type="submit" class="btn primary-btn cart_button_checkout">
                                <span class="spinner-border d-none" role="status" aria-hidden="true"></span>
                                PAGAR COM BOLETO
                            </button>
                        </div>
                    </div> 
                </form>
            </div>
            @endif
            <!-- fim boleto -->
        </div>
    </div>
</div>
</section>
@endsection 

@section('javascript')
<script type="text/javascript">

    $('.cart_button_checkout').click(() => {
        setTimeout(() => {
            $('.cart_button_checkout').prop("disabled", true);
            $('.spinner-border').removeClass('d-none')
        }, 100)
    })

    $('.div-cartao').click(() => {
        removeClass()
        activeClass('cartao')
    })

    $('.div-pix').click(() => {
        removeClass()
        activeClass('pix')
    })

    $('.div-boleto').click(() => {
        removeClass()
        activeClass('boleto')
    })

    function removeClass(){
        $('.div-cartao').removeClass('active')
        $('.div-pix').removeClass('active')
        $('.div-boleto').removeClass('active')
    }

    function activeClass(classe){

        $('.'+classe).removeClass('d-none')

        if(classe == 'cartao'){
            $('.div-cartao').addClass('active')
            $('.pix').addClass('d-none')
            $('.boleto').addClass('d-none')
        }else if(classe == 'boleto'){
            $('.div-boleto').addClass('active')

            $('.cartao').addClass('d-none')
            $('.pix').addClass('d-none')
        }else{
            $('.div-pix').addClass('active')

            $('.cartao').addClass('d-none')
            $('.boleto').addClass('d-none')
        }
        
    }
</script>
@endsection 

