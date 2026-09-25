@extends('delivery.default')
@section('content')

<div class="container" style="padding-top: 30px; padding-bottom: 50px;">
  <div class="text-center mb-4">
    <span class="badge badge-primary" style="background-color: #ea1d2c; padding: 10px 20px; border-radius: 20px;">
        PASSO 1 DE 3: ESCOLHA O TAMANHO
    </span>
</div>
    
    <div class="d-flex align-items-center mb-4">
        <a href="javascript:history.back()" style="color: var(--cor-primaria); font-size: 24px; margin-right: 15px; text-decoration: none;">
            <i class="fa fa-arrow-left"></i>
        </a>
        <h2 style="margin: 0; font-size: 22px; font-weight: 800; text-transform: uppercase;">Tipo da Pizza</h2>
    </div>

    <form action="/pizza/escolherSabores" method="get">
        @if(session()->has('message_erro'))
            <div class="alert alert-danger">{{ session()->get('message_erro') }}</div>
        @endif

        <input type="hidden" name="categoria" value="{{$categoria->id}}">
        <input type="hidden" name="produto" value="{{{isset($produto) ? $produto->id : 0 }}}">

        <h4 style="font-size: 16px; margin-bottom: 20px; color: var(--cor-texto-claro);">
            Selecione o tamanho e quantos sabores deseja:
        </h4>

        <div class="row">
            @foreach($tamanhos as $t)
                @for($aux = 1; $aux <= $t->maximo_sabores; $aux++)
                    <div class="col-12 col-md-6 mb-3">
                        <label class="card-opcao card-moderno d-flex align-items-center" style="margin: 0; padding: 20px; cursor: pointer; border: 2px solid #e2e8f0; transition: 0.2s;">
                            
                            <input class="sr-only" type="radio" name="tipo" value="{{$t->nome}}-{{$aux}}" required>
                            
                            <div style="flex-grow: 1;">
                                <div class="titulo-opcao" style="font-weight: 800; font-size: 17px; color: var(--cor-texto-escuro);">{{$t->nome()}}</div>
                                <div style="font-size: 14px; color: var(--cor-texto-claro);">
                                    <i class="fa fa-cutlery mr-1"></i> {{$t->pedacos}} pedaços 
                                    <span class="mx-1">•</span> 
                                    <i class="fa fa-pie-chart mr-1"></i> {{$aux}} {{ $aux == 1 ? 'sabor' : 'sabores' }}
                                </div>
                            </div>
                            
                            <div class="radio-indicator"></div>
                        </label>
                    </div>
                @endfor
            @endforeach
        </div>

        <div style="position: fixed; bottom: 0; left: 0; width: 100%; padding: 15px; background: white; border-top: 1px solid #e2e8f0; z-index: 9999; box-shadow: 0 -4px 10px rgba(0,0,0,0.05);">
            <div class="container">
                <button type="submit" style="width: 100%; background-color: #ea1d2c; color: white; border: none; padding: 18px; font-size: 18px; font-weight: 800; border-radius: 12px; text-transform: uppercase;">
                    <i class="fa fa-arrow-right"></i> Escolher Sabores
                </button>
            </div>
        </div>
    </form>

</div>

<style>
    /* Aumenta o tamanho do card e das fontes para facilitar a leitura */
    .card-opcao {
        padding: 30px !important; /* Muito mais espaço para clicar */
        margin-bottom: 20px !important;
    }
    .titulo-opcao {
        font-size: 20px !important; /* Título bem grande */
        margin-bottom: 5px;
    }
    .radio-indicator {
        width: 30px !important; height: 30px !important; /* Bolinha de seleção maior */
    }
    /* Estilo de "clicado" mais agressivo */
    .card-opcao:has(input:checked) {
        background-color: #fef2f2 !important;
        border-width: 3px !important;
    }
</style>

@endsection