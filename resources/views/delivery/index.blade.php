@extends('delivery.default')
@section('content')

<style>
    /* Esconde a barra de rolagem mas mantém a funcionalidade (Mobile First) */
    .hide-scroll-bar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
    .hide-scroll-bar::-webkit-scrollbar {
        display: none;
    }
</style>

<script src="https://cdn.tailwindcss.com"></script>

<div class="bg-gray-50 min-h-screen pb-24 font-sans">

    @if(session()->has('message_sucesso'))
    <div class="m-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm" role="alert">
        <p class="font-bold">Sucesso!</p>
        <p>{{ session()->get('message_sucesso') }}</p>
    </div>
    @endif

    @if(session()->has('message_erro'))
    <div class="m-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm" role="alert">
        <p class="font-bold">Ops!</p>
        <p>{{ session()->get('message_erro') }}</p>
    </div>
    @endif


    @if(count($destaques) > 0)
    <div class="pt-6 pb-2">
        <h2 class="text-xl font-bold text-gray-800 px-4 mb-4">Destaques para você</h2>
        
        <div class="flex overflow-x-auto snap-x snap-mandatory hide-scroll-bar px-4 pb-4 space-x-4">
            @foreach($destaques as $d)
                @if(!isset($d->block))
                <div class="snap-start shrink-0 w-72 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col justify-between">
                    
                    <div class="h-40 w-full bg-gray-200 relative">
                        @if(count($d->galeria) > 0)
                            <img src="/imagens_produtos/{{$d->galeria[0]->path}}" class="w-full h-full object-cover" alt="{{$d->produto->nome}}">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-gray-400">Sem imagem</div>
                        @endif
                        
                        @if($d->valor_anterior > 0 && count($d->pizza) == 0)
                            <span class="absolute top-2 right-2 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded-lg">PROMOÇÃO</span>
                        @endif
                    </div>

                    <div class="p-4 flex-1 flex flex-col justify-between">
                        <div>
                            <h3 class="font-bold text-lg text-gray-800 leading-tight">{{$d->produto->nome}}</h3>
                            <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{$d->descricao}}</p>
                        </div>

                        <div class="mt-4 flex items-center justify-between">
                            <div class="flex flex-col">
                                @if(count($d->pizza) == 0)
                                    @if($d->valor_anterior > 0)
                                        <span class="text-xs text-gray-400 line-through">R$ {{number_format($d->valor_anterior, 2, ',', '.')}}</span>
                                        <span class="text-lg font-bold text-green-600">R$ {{number_format($d->valor, 2, ',', '.')}}</span>
                                    @else
                                        <span class="text-lg font-bold text-gray-900">R$ {{number_format($d->valor, 2, ',', '.')}}</span>
                                    @endif
                                @else
                                    <div class="text-sm font-semibold text-gray-700">
                                    @foreach($d->pizza as $tp)
                                        @if($tp->valor > 0)
                                            <p>{{$tp->tamanho->nome()}}: <span class="text-green-600">R$ {{number_format($tp->valor, 2, ',', '.')}}</span></p>
                                        @endif
                                    @endforeach
                                    </div>
                                @endif
                            </div>

                            <a href="/cardapio/acompanhamento/{{$d->id}}" class="bg-red-600 hover:bg-red-700 text-white w-10 h-10 rounded-full flex items-center justify-center shadow-md transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            </a>
                        </div>
                    </div>
                </div>
                @endif
            @endforeach
        </div>
    </div>
    @endif


    <div class="pt-4 px-4">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Cardápio</h2>
        
        <div class="grid grid-cols-2 gap-4">
            @foreach($categorias as $c)
            <a href="/cardapio/{{$c->id}}" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden transform transition hover:scale-105 active:scale-95 flex flex-col">
                <div class="h-28 w-full bg-gray-100">
                    <img loading="lazy" src="imagens_categorias/{{$c->path}}" class="w-full h-full object-cover" alt="{{$c->nome}}">
                </div>
                <div class="p-3 text-center flex-1 flex items-center justify-center">
                    <h3 class="font-semibold text-gray-800 text-sm">{{$c->nome}}</h3>
                </div>
            </a>
            @endforeach
        </div>
    </div>

    <div class="fixed bottom-0 left-0 w-full bg-white border-t border-gray-200 p-4 shadow-[0_-10px_15px_-3px_rgba(0,0,0,0.05)] z-50">
        <a href="/carrinho" class="w-full bg-red-600 flex items-center justify-between text-white font-bold py-3 px-6 rounded-xl shadow-lg hover:bg-red-700 transition-colors">
            <span class="flex items-center gap-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                Ver meu carrinho
            </span>
            <span>➔</span>
        </a>
    </div>

</div>

@endsection