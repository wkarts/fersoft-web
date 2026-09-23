<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ isset($config) && isset($config->nome) ? $config->nome : 'Nosso Delivery' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 h-screen flex flex-col items-center justify-center p-4">

    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-sm text-center border border-gray-100">
        
        <div class="mx-auto mb-4 flex items-center justify-center h-28">
            @if(isset($config) && isset($config->logo) && $config->logo != '')
                <img src="/delivery/logos/{{ $config->logo }}" alt="Logo" class="max-h-full max-w-full object-contain drop-shadow-sm">
            @else
                <div class="w-24 h-24 bg-red-100 rounded-full flex items-center justify-center">
                    <svg class="w-12 h-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                </div>
            @endif
        </div>

        @if(isset($config) && isset($config->nome))
            <h2 class="text-2xl font-bold text-gray-800 mb-1" style="text-transform: capitalize;">{{ $config->nome }}</h2>
            <h3 class="text-lg font-semibold text-red-600 mb-2">Bem-vindo(a)!</h3>
        @else
            <h2 class="text-2xl font-bold text-gray-800 mb-1">Bem-vindo(a)!</h2>
        @endif

        <p class="text-gray-500 mb-8 text-sm">Para ver o cardápio e fazer seu pedido, informe seu WhatsApp.</p>

        @if(session()->has('message_erro'))
        <div class="mb-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-xl shadow-sm text-sm text-left">
            <p class="font-bold">Aviso:</p>
            <p>{{ session()->get('message_erro') }}</p>
        </div>
        @endif
      
        <form action="/pedir/{{ $nome_empresa }}/entrar" method="POST">
            @csrf
            
            <div class="mb-6 text-left">
                <input type="tel" name="telefone" id="telefone" placeholder="(00) 00000-0000" required
                       class="w-full py-4 px-4 text-gray-700 bg-gray-50 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent text-lg text-center font-medium tracking-wider transition-all"
                       oninput="mascaraTelefone(this)">
            </div>

            <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-4 px-4 rounded-xl shadow-lg shadow-red-500/30 transform transition hover:-translate-y-0.5 focus:outline-none">
                Entrar no Cardápio
            </button>
        </form>
    </div>

    <script>
        // Script para formatar o telefone
        function mascaraTelefone(input) {
            let valor = input.value.replace(/\D/g, ''); 
            valor = valor.replace(/^(\d{2})(\d)/g, '($1) $2'); 
            valor = valor.replace(/(\d)(\d{4})$/, '$1-$2'); 
            input.value = valor;
        }
    </script>

</body>
</html>