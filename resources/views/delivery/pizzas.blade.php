@extends('delivery.default')
@section('content')

<div class="container" style="padding-top: 20px; padding-bottom: 120px;">
    
    <!-- CABEÇALHO COM RESUMO DA PIZZA -->
    <div class="card-moderno mb-4">
        <h2 style="font-size: 18px; font-weight: 800; margin-bottom: 5px;">
            Montando sua Pizza
        </h2>
        <p style="font-size: 14px; color: var(--cor-texto-claro); margin: 0;">
            {{ session('tamanho_pizza')['tamanho'] }} | {{ session('tamanho_pizza')['sabores'] }} sabor(es)
        </p>
    </div>

    <!-- BUSCA MODERNA -->
    <form action="/pizza/pesquisa" method="get" class="mb-4">
        <div class="input-group">
            <input type="text" name="pesquisa" class="form-control" placeholder="Pesquisar sabor..." required>
            <div class="input-group-append">
                <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i></button>
            </div>
        </div>
        <input type="hidden" name="link" value="{{$_SERVER['REQUEST_URI']}}">
    </form>

    <!-- LISTA DE SABORES DISPONÍVEIS -->
    <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 15px; color: var(--cor-texto-escuro);">
        Escolha os Sabores ({{count($saboresIncluidos)}} / {{session('tamanho_pizza')['sabores']}})
    </h3>

    @if(env("DIVISAO_VALOR_PIZZA") == 0)
    <p style="font-size: 12px; color: var(--cor-primaria); margin-bottom: 15px;">*Prevalecerá o preço do sabor mais caro.</p>
    @endif

    <div class="row">
        @foreach($pizzas as $p)
            @if(isset($pesquisa) || $p->produto->categoria->id == $categoria)
                @if($p->produto->status && $p->valor > 0)
                    <div class="col-6 col-md-4 mb-3" onclick="select_pizza({{$p->produto}}, {{$p->produto->galeria}}, {{$p->produto->produto}})">
                        <a href="{{ session('tamanho_pizza')['sabores'] > count($saboresIncluidos) ? '#add-pizza' : '#!' }}" style="text-decoration: none;">
                            <div class="card-moderno p-0" style="overflow: hidden; {{ session('tamanho_pizza')['sabores'] == count($saboresIncluidos) ? 'opacity: 0.5;' : '' }}">
                                @if(count($p->produto->galeria) > 0)
                                    <img src="/imagens_produtos/{{$p->produto->galeria[0]->path}}" style="width: 100%; height: 120px; object-fit: cover;">
                                @else
                                    <img src="/imagens/sem-imagem.png" style="width: 100%; height: 120px; object-fit: cover;">
                                @endif
                                <div class="p-2">
                                    <h4 style="font-size: 14px; font-weight: bold; margin: 0;">{{$p->produto->produto->nome}}</h4>
                                    <span style="font-size: 13px; color: #10b981; font-weight: 700;">R${{$p->valor}}</span>
                                </div>
                            </div>
                        </a>
                    </div>
                @endif
            @endif
        @endforeach
    </div>

    <!-- SABORES ADICIONADOS (LISTA ELEGANTE) -->
    @if(count($saboresIncluidos) > 0)
        <h3 style="font-size: 16px; font-weight: 700; margin: 20px 0 15px 0;">Sabores Selecionados</h3>
        @foreach($saboresIncluidos as $s)
            <div class="card-moderno d-flex justify-content-between align-items-center p-3 mb-2">
                <span style="font-weight: bold;">{{$s['produto']['nome']}}</span>
                <a href="/pizza/removeSabor/{{$s['id']}}" style="color: #ef4444;"><i class="fa fa-trash"></i></a>
            </div>
        @endforeach
    @endif

</div>

<!-- BOTÃO FLUTUANTE DE FINALIZAR -->
<div style="position: fixed; bottom: 0; left: 0; width: 100%; padding: 15px 10px; background: white; border-top: 2px solid #f1f5f9; z-index: 1000; box-shadow: 0 -5px 15px rgba(0,0,0,0.08);">
    <div class="container">
        @if(count($saboresIncluidos) < session('tamanho_pizza')['sabores'])
            <button class="btn btn-secondary btn-block" style="padding: 16px; font-weight: bold; border-radius: 12px; cursor: not-allowed; opacity: 0.6;">
                Selecione {{ session('tamanho_pizza')['sabores'] }} sabores
            </button>
        @else
            <a href="/pizza/adicionais" class="btn btn-success btn-block" style="padding: 16px; font-weight: 800; border-radius: 12px; font-size: 18px; display: flex; justify-content: space-between; align-items: center; text-decoration: none; background-color: #10b981 !important;">
                <span>Continuar (Adicionais)</span>
                <strong>R$ {{number_format($valorPizza, 2, ',', '.')}}</strong>
            </a>
        @endif
    </div>
</div>

<!-- MODAL DE ADIÇÃO DE SABOR (ESTILO MODERNO) -->
<div id="add-pizza" class="pop-overlay">
    <div class="popup" style="border-radius: 16px;">
        <form method="post" action="/pizza/adicionarSabor">
            @csrf
            <div class="text-center p-3">
                <img src="" id="img" style="width: 100%; height: 180px; object-fit: cover; border-radius: 12px;">
                <h4 class="mt-3" id="sabor" style="font-weight: 800;"></h4>
                <p id="descricao" style="font-size: 13px; color: #666;"></p>
                <input type="hidden" id="pizza_id" name="pizza_id">
                <input type="hidden" name="link" value="{{$_SERVER['REQUEST_URI']}}">
                <button type="submit" class="btn-principal mt-3">Confirmar Sabor</button>
            </div>
        </form>
        <a class="close" href="#!">×</a>
    </div>
</div>
<style>
    /* 1. Estilização dos Cards de Sabor */
    .card-opcao {
        padding: 20px !important;
        margin-bottom: 15px !important;
        border: 2px solid #e2e8f0 !important;
        border-radius: 12px !important;
        cursor: pointer;
    }
    
    .card-opcao:has(input:checked) {
        border-color: #ea1d2c !important;
        background-color: #fff5f5 !important;
    }

    /* 2. Esconde o radio button padrão */
    .sr-only { 
        position: absolute; width: 1px; height: 1px; overflow: hidden; 
    }

    /* 3. Bolinha de seleção */
    .radio-indicator {
        width: 24px; height: 24px; border-radius: 50%; border: 2px solid #cbd5e1;
        transition: 0.2s;
    }
    .card-opcao:has(input:checked) .radio-indicator {
        border: 7px solid #ea1d2c;
    }

    /* 4. Estilização do Botão Fixo no Rodapé */
    .btn-continuar {
        width: 100%;
        padding: 18px;
        font-size: 18px;
        font-weight: 800;
        border-radius: 12px;
        text-transform: uppercase;
        border: none;
        display: flex;
        justify-content: space-between;
        align-items: center;
        text-decoration: none;
        transition: 0.3s;
    }
    .btn-ativo { background-color: #10b981 !important; color: white !important; }
    .btn-inativo { background-color: #94a3b8 !important; color: white !important; cursor: not-allowed; }
</style>

<div style="position: fixed; bottom: 0; left: 0; width: 100%; padding: 15px; background: white; border-top: 2px solid #f1f5f9; z-index: 1000; box-shadow: 0 -5px 15px rgba(0,0,0,0.08);">
    <div class="container">
        @if(count($saboresIncluidos) < session('tamanho_pizza')['sabores'])
            <div class="btn-continuar btn-inativo">
                <span>Selecione {{ session('tamanho_pizza')['sabores'] }} sabor(es)</span>
            </div>
        @else
            <a href="/pizza/adicionais" class="btn-continuar btn-ativo">
                <span>Continuar (Adicionais)</span>
                <strong>R$ {{number_format($valorPizza, 2, ',', '.')}}</strong>
            </a>
        @endif
    </div>
</div>
@endsection