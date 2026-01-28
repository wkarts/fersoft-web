@extends('default.layout')
@section('content')

    <div class="row">
        <div class="col s12">
            <div class="container">
                <h3 class="center-align">Bem-vindo ao teste do {{ env('APP_NAME', '') }}</h3>
                <p>Banco de dados com algumas categorias e produtos cadastrados, fique à vontade para realizar testes.</p>
                <p>Para emissão de NFe, acesse **Saídas e Vendas**, preencha os campos e envie para SEFAZ/PR, ambiente de homologação.</p>
                <p>Para emissão de NFCe, acesse **Saídas e Frente de Caixa**, preencha os campos e envie para SEFAZ/PR, ambiente de homologação.</p>

                <p>Download do app Android de pedidos <a href="{{ env('APP_TEST_URL', '') }}">{{ env('APP_TEST_URL', '') }}</a>, habilite fontes desconhecidas, e defina o path das configurações como **{{ env('APP_TEST_PATH', '') }}**</p>
            </div>
        </div>
    </div>

@endsection
