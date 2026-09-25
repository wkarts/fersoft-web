<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cardápio Digital</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        html { scroll-behavior: smooth; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 antialiased pb-24">

    <header class="bg-red-600 text-white px-4 pt-6 pb-6 rounded-b-3xl shadow-md sticky top-0 z-50">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-xl font-bold tracking-wide">Cardápio <span class="text-yellow-300 text-xs">V.8</span></h1>
                <p class="text-sm text-red-100 mt-1 flex items-center gap-2">
                    <i class="fa-solid fa-utensils"></i>
                    <span class="font-bold bg-white text-red-600 px-2 py-0.5 rounded-full text-[11px] uppercase tracking-wider">
                        {{ $pedido->mesa->nome ?? 'Mesa ' . session('mesa_open')['mesa_id'] }}
                    </span>
                </p>
            </div>
            <a href="{{ url('/cardapio-web/ver') }}" class="relative bg-white text-red-600 p-3 rounded-full shadow-lg hover:scale-105 transition cursor-pointer">
                <i class="fa-solid fa-basket-shopping text-lg"></i>
                <span class="absolute -top-1 -right-1 bg-yellow-400 text-slate-900 font-bold text-xs w-5 h-5 rounded-full flex items-center justify-center border-2 border-white">
                    {{ $pedido->itens->count() ?? 0 }}
                </span>
            </a>
        </div>
        
        <div class="flex gap-3 overflow-x-auto mt-5 no-scrollbar pb-1">
            <a href="#destaques" class="bg-white/20 text-white text-xs font-semibold px-4 py-2 rounded-full whitespace-nowrap">⭐ Destaques</a>
            @foreach($categorias as $cat)
                <a href="#categoria-{{ $cat->id }}" class="bg-white/20 text-white text-xs font-semibold px-4 py-2 rounded-full whitespace-nowrap">{{ $cat->nome }}</a>
            @endforeach
        </div>
    </header>

    <main class="max-w-md mx-auto mt-6 space-y-8 relative z-0">
        @if(count($destaques) > 0)
        <section id="destaques" class="px-4 scroll-mt-40">
            <h2 class="text-lg font-bold text-gray-900 mb-3">⭐ Mais Pedidos da Casa</h2>
            <div class="flex gap-4 overflow-x-auto no-scrollbar pb-4">
                @foreach($destaques as $destaque)
                    <div class="bg-white min-w-[160px] w-40 rounded-2xl shadow-sm border border-gray-100 p-3 flex flex-col justify-between">
                        <div>
                            <div class="w-full h-24 bg-gray-100 rounded-xl overflow-hidden mb-2">
                                @php
                                    $imgD = isset($destaque->galeria) && count($destaque->galeria) > 0 ? $destaque->galeria[0]->path : $destaque->imagem;
                                @endphp
                                @if($imgD)
                                    <img src="{{ asset('imagens_produtos/' . rawurlencode($imgD)) }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-300"><i class="fa-solid fa-camera"></i></div>
                                @endif
                            </div>
                            <h3 class="font-semibold text-sm text-gray-800 line-clamp-2 leading-tight">{{ $destaque->nome }}</h3>
                        </div>
                        <div class="mt-3 flex items-center justify-between">
                            @php
                                $precoDestaque = $destaque->valor;
                                $prefixoDestaque = "";
                                if($precoDestaque <= 0 && isset($destaque->pizza) && count($destaque->pizza) > 0) {
                                    $precoDestaque = $destaque->pizza[0]->valor; 
                                    $prefixoDestaque = "A partir de";
                                }
                            @endphp
                            <span class="text-red-600 font-bold text-[11px] leading-tight">{{ $prefixoDestaque }} <br> R$ {{ number_format($precoDestaque, 2, ',', '.') }}</span>
                            
                            <a href="{{ url('/cardapio-web/adicionais/' . $destaque->id) }}" class="bg-red-50 text-red-600 w-8 h-8 rounded-full flex items-center justify-center cursor-pointer hover:bg-red-600 hover:text-white transition-colors border border-red-100 shrink-0">
                                <i class="fa-solid fa-plus text-sm"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
        @endif

        <div class="px-4 space-y-8">
            @foreach($categorias as $cat)
                <section id="categoria-{{ $cat->id }}" class="scroll-mt-40">
                    <h2 class="text-sm font-bold text-gray-900 border-b border-gray-200 pb-2 mb-3 uppercase">{{ $cat->nome }}</h2>
                    <div class="space-y-3">
                        @forelse($cat->produtos as $produto)
                            @if($produto->status)
                                <div class="bg-white rounded-2xl p-3 shadow-sm border border-gray-100 flex gap-3 items-center">
                                    <div class="w-20 h-20 bg-gray-50 rounded-xl overflow-hidden flex-shrink-0">
                                        @php
                                            $imgP = isset($produto->galeria) && count($produto->galeria) > 0 ? $produto->galeria[0]->path : $produto->imagem;
                                        @endphp
                                        @if($imgP)
                                            <img src="{{ asset('imagens_produtos/' . rawurlencode($imgP)) }}" class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center bg-gray-100 text-gray-300"><i class="fa-solid fa-camera"></i></div>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-semibold text-sm text-gray-900 truncate">{{ $produto->nome }}</h3>
                                        <p class="text-[11px] text-gray-400 line-clamp-2 mt-0.5 leading-tight">{{ $produto->descricao ?? 'Sem descrição' }}</p>
                                        <div class="flex items-center justify-between mt-2">
                                            @php
                                                $precoFinal = $produto->valor;
                                                $prefixo = "";
                                                if($precoFinal <= 0 && isset($produto->pizza) && count($produto->pizza) > 0) {
                                                    $precoFinal = $produto->pizza[0]->valor; 
                                                    $prefixo = "A partir de ";
                                                }
                                            @endphp
                                            <span class="text-xs font-bold text-gray-900">{{ $prefixo }} R$ {{ number_format($precoFinal, 2, ',', '.') }}</span>
                                            
                                            <a href="{{ url('/cardapio-web/adicionais/' . $produto->id) }}" class="bg-red-600 text-white px-3 py-1 rounded-full font-medium text-xs flex items-center gap-1 shadow-md cursor-pointer hover:bg-red-700">
                                                Adicionar <i class="fa-solid fa-plus text-[10px]"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @empty
                            <p class="text-xs text-gray-400 italic px-2">Nenhum item.</p>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    </main>

    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 px-6 py-2 flex justify-around items-center z-50 max-w-md mx-auto shadow-xl rounded-t-2xl">
        <a href="#" class="flex flex-col items-center gap-0.5 text-red-600 cursor-pointer">
            <i class="fa-solid fa-house text-lg"></i><span class="text-[10px] font-medium">Início</span>
        </a>
        <a href="{{ url('/cardapio-web/ver') }}" class="flex flex-col items-center gap-0.5 text-gray-400 cursor-pointer">
            <i class="fa-solid fa-receipt text-lg"></i><span class="text-[10px] font-medium">Meu Pedido</span>
        </a>
        <a href="{{ url('/pedido/chamar_garcom') }}" class="flex flex-col items-center gap-0.5 text-gray-400 cursor-pointer">
            <i class="fa-solid fa-bell text-lg"></i><span class="text-[10px] font-medium">Chamar Garçom</span>
        </a>
    </nav>
</body>
</html>