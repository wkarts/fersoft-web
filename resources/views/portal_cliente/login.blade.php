<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal do Cliente - Acesso</title>
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome para o ícone de reciclagem aparecer -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body { background-color: #f3f4f6; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .login-box { background: #fff; padding: 40px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        .btn-acessar { background-color: #0f766e; color: white; width: 100%; padding: 10px; font-weight: bold; }
        .btn-acessar:hover { background-color: #115e59; color: white; }
    </style>
</head>
<body>

<div class="login-box">

    <div class="text-center mb-4">
        <!-- 1. CORREÇÃO DA LOGO: Buscando da pasta /logos/ -->
        @if(isset($config) && $config->logo)
            <img src="/logos/{{ $config->logo }}" alt="Logo" style="max-height: 80px; margin-bottom: 15px;">
        @else
            <i class="fas fa-recycle fa-3x" style="color: #0f766e; margin-bottom: 15px;"></i>
        @endif
        
        <h5 style="color: #333; font-weight: bold;">{{ $config->nome_fantasia ?? $config->razao_social }}</h5>
        
        <h4 style="color: #0f766e; font-weight: bold; margin-top: 10px;">Portal de Coletas</h4>
    </div>

    @if(session('erro'))
        <div class="alert alert-danger text-center">{{ session('erro') }}</div>
    @endif
    @if(session('sucesso'))
        <div class="alert alert-success text-center">{{ session('sucesso') }}</div>
    @endif

    <!-- 2. CORREÇÃO DA AÇÃO: Usando a variável $slug -->
    <form action="/portal-cliente/{{ $slug }}/autenticar" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label font-weight-bold">CPF ou CNPJ</label>
            <input type="text" name="cpf_cnpj" id="cpf_cnpj" class="form-control" required placeholder="Digite seu documento">
        </div>
        <div class="mb-4">
            <label class="form-label font-weight-bold">Senha de Acesso</label>
            <input type="password" name="senha" class="form-control" required placeholder="Senha (6 dígitos)">
        </div>
        <div class="mb-4 text-end">
            <a href="/portal-cliente/{{ $slug }}/esqueci-senha" style="color: #0f766e; text-decoration: none; font-size: 0.9em;">Esqueceu sua senha?</a>
        </div>
        <button type="submit" class="btn btn-acessar">Entrar no Portal</button>
    </form>

</div> <!-- FIM DA LOGIN-BOX -->

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script>
    // Máscara inteligente: aceita CPF ou CNPJ no mesmo campo
    var options = {
        onKeyPress: function (cpf, ev, el, op) {
            var masks = ['000.000.000-009', '00.000.000/0000-00'];
            $('#cpf_cnpj').mask((cpf.length > 14) ? masks[1] : masks[0], op);
        }
    }
    $('#cpf_cnpj').length > 11 ? $('#cpf_cnpj').mask('00.000.000/0000-00', options) : $('#cpf_cnpj').mask('000.000.000-00#', options);
</script>
</body>
</html>