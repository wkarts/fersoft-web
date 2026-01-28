@extends('ecommerce.default')
@section('content')

<style type="text/css">
    textarea{
        width: 100% !important;
        border-radius: 10px;
    }
    .body-form{
        margin: 5px;
    }
</style>
<section class="featured">
    <div class="row">
        <div class="container">

            <div class="row featured__filter">
                <div class="col-12 mix cartao @if($forma_inicial != 'cartao') d-none @endif">
                    <div class="row">
                        <div class="col-lg-12">
                            <center><h3>Finalizando orçamento</h3></center>
                        </div>
                    </div>
                    <br>
                    <form action="/ecommercePay/finalizaOrcamento" method="post">
                        @csrf

                        <input style="visibility: hidden" type="" name="transactionAmount" id="transactionAmount" value="{{$total}}" />
                        <input type="hidden" name="total_pag" value="{{$totais->total_pix}}">

                        <input style="visibility: hidden" value="{{$descricao}}" name="description">
                        <input style="visibility: hidden" name="paymentMethodId" id="paymentMethodId" />
                        <input type="hidden" value="{{$carrinho->id}}" name="carrinho_id">
                        
                        <div class="row body-form">
                            <div class="col-lg-12">
                                <div class="checkout__input">
                                    <p>Observação</p>
                                    <textarea name="observacao" rows="4"></textarea>
                                </div>
                            </div>

                            <div class="col-lg-12">
                                <button type="submit" class="btn primary-btn float-right cart_button_checkout">
                                    <span class="spinner-border d-none" role="status" aria-hidden="true"></span>
                                    FINALIZAR
                                </button>
                            </div>
                        </div> 
                    </form>

                </div>
            </div>
        </div>
    </div>
</section>
@endsection 



