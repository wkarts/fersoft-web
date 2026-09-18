<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - Portal do Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f3f4f6; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .login-box { background: #fff; padding: 40px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
    </style>
</head>
<body>

<div class="login-box">
    <div class="text-center mb-4">
        <h4 style="color: #0f766e; font-weight: bold;">Recuperar Senha</h4>
        <p class="text-muted">Informe seu documento para receber uma nova senha no WhatsApp.</p>
    </div>

    @if(session('erro'))
        <div class="alert alert-danger text-center">{{ session('erro') }}</div>
    @endif

    <!-- Ação do form agora usa o slug -->
    <form action="/portal-cliente/{{ $slug }}/recuperar-senha" method="POST">
        @csrf
        <div class="mb-4">
            <label class="form-label font-weight-bold">CPF ou CNPJ</label>
            <input type="text" name="cpf_cnpj" id="cpf_cnpj" class="form-control" required placeholder="Digite seu documento">
        </div>
        <button type="submit" class="btn w-100 mb-3" style="background-color: #0f766e; color: white; font-weight: bold;">Enviar Nova Senha</button>
        <div class="text-center">
            <!-- Botão voltar agora usa o slug -->
            <a href="/portal-cliente/{{ $slug }}/login" class="text-secondary" style="text-decoration: none;">Voltar para o Login</a>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script>
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