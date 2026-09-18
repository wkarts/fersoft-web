<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Parceiro - {{ $config->nome_fantasia ?? 'Sistema' }}</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f3f4f6; /* Fundo cinza claro */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .header-topo {
            background-color: #0f766e; /* Verde escuro profissional (parecido com o do vídeo) */
            color: white;
            padding: 40px 20px;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }
        .form-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            margin-top: -20px;
            margin-bottom: 40px;
            padding: 30px;
        }
        .form-label {
            font-weight: 600;
            color: #374151;
            margin-top: 15px;
        }
        .btn-enviar {
            background-color: #0f766e;
            color: white;
            padding: 10px 30px;
            font-weight: bold;
            border-radius: 6px;
            border: none;
        }
        .btn-enviar:hover {
            background-color: #115e59;
            color: white;
        }
    </style>
</head>
<body>

<div class="container" style="max-width: 800px; margin-top: 50px;">
    
    <!-- Cabeçalho Dinâmico -->
    <div class="text-center mb-4">
        <!-- Tentativa de exibir a logo do Fersoft ERP -->
        @if(isset($config) && $config->logo)
            <!-- A maioria dos ERPs baseados no Fersoft salvam a logo na pasta public/logos -->
            <img src="/logos/{{ $config->logo }}" alt="Logo" style="max-height: 80px; margin-bottom: 15px;">
        @else
            <i class="fas fa-recycle fa-3x" style="color: #0f766e; margin-bottom: 15px;"></i>
        @endif

        <!-- Exibe a Razão Social ou Nome Fantasia da Empresa -->
        <h5 style="color: #333; font-weight: bold;">{{ $config->nome_fantasia ?? $config->razao_social }}</h5>

        <h4 style="color: #0f766e; font-weight: bold; margin-top: 10px;">Portal de Coletas</h4>
    </div>
	
    <!-- Formulário -->
    <div class="form-container">
        
        <div id="mensagem-sucesso" class="alert alert-success d-none text-center">
            <h4><i class="fas fa-check-circle"></i> Sucesso!</h4>
            <p>Seus dados foram enviados para análise. Em breve, nossa equipe entrará em contato via WhatsApp liberando seu acesso.</p>
        </div>

        <form id="form-cadastro" action="/cadastro-parceiro/salvar" method="POST">
            @csrf
            <!-- ID Oculto da Empresa para salvar no banco correto -->
            <input type="hidden" name="empresa_id" value="{{ $empresa_id }}">

            <div class="row">
                <div class="col-md-12">
                    <label class="form-label">1. Nome completo / Razão Social *</label>
                    <input type="text" name="nome_completo" class="form-control" placeholder="Insira sua resposta" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">2. Tipo de Pessoa *</label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="tipo_pessoa" id="pf" value="Fisica" required>
                            <label class="form-check-label" for="pf">Física</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="tipo_pessoa" id="pj" value="Juridica">
                            <label class="form-check-label" for="pj">Jurídica</label>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">3. CPF ou CNPJ *</label>
                    <input type="text" name="cpf_cnpj" id="cpf_cnpj" class="form-control" placeholder="Insira sua resposta" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">4. Regime Tributário (Caso PJ)</label>
                    <input type="text" name="regime_tributario" class="form-control" placeholder="Ex: Lucro Real, Presumido...">
                </div>

                <div class="col-md-6">
                    <label class="form-label">5. Optante pelo Simples Nacional? *</label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="simples_nacional" value="1" required>
                            <label class="form-check-label">Sim</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="simples_nacional" value="0">
                            <label class="form-check-label">Não</label>
                        </div>
                    </div>
                </div>

                <div class="col-md-12">
                    <label class="form-label">6. RG ou Inscrição Estadual (Obrigatório para PJ)</label>
                    <input type="text" name="rg_ie" class="form-control" placeholder="Insira sua resposta">
                </div>

                <div class="col-md-6">
                    <label class="form-label">7. Telefone / WhatsApp *</label>
                    <input type="text" name="telefone" id="telefone" class="form-control" placeholder="(00) 00000-0000" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">8. E-mail *</label>
                    <input type="email" name="email" class="form-control" placeholder="Insira sua resposta" required>
                </div>

                <hr class="mt-4 mb-2">
                <h5 class="mt-3 text-secondary">Endereço de Coleta</h5>

                <div class="col-md-4">
                    <label class="form-label">9. CEP *</label>
                    <input type="text" name="cep_coleta" id="cep" class="form-control" placeholder="00000-000" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label">10. Rua (Coletor) *</label>
                    <input type="text" name="rua_coleta" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">11. Número *</label>
                    <input type="text" name="numero_coleta" class="form-control" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label">12. Bairro *</label>
                    <input type="text" name="bairro_coleta" class="form-control" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label">13. Cidade *</label>
                    <input type="text" name="cidade_coleta" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">14. Estado (UF) *</label>
                    <input type="text" name="estado_coleta" class="form-control" maxlength="2" required>
                </div>

                <div class="col-md-12">
                    <label class="form-label">15. Endereço de Cobrança (Se diferente)</label>
                    <input type="text" name="endereco_cobranca" class="form-control" placeholder="Insira sua resposta">
                </div>

                <hr class="mt-4 mb-2">
                <h5 class="mt-3 text-secondary">Dados Bancários e Contato</h5>

                <div class="col-md-12">
                    <label class="form-label">16. Pessoa de Contato (Nome Completo) *</label>
                    <input type="text" name="pessoa_contato" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">17. Banco</label>
                    <input type="text" name="banco" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">18. Agência</label>
                    <input type="text" name="agencia" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">19. Conta (Sem dígito)</label>
                    <input type="text" name="conta" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">20. Dígito</label>
                    <input type="text" name="digito" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">21. Tipo de Conta</label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="tipo_conta" value="Corrente">
                            <label class="form-check-label">Corrente</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="tipo_conta" value="Poupanca">
                            <label class="form-check-label">Poupança</label>
                        </div>
                    </div>
                </div>

                <div class="col-md-12">
                    <label class="form-label">22. Chave Pix</label>
                    <input type="text" name="chave_pix" class="form-control">
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">23. Responsável Comercial</label>
                    <input type="text" name="responsavel_comercial" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">24. Origem (Como nos conheceu?)</label>
                    <input type="text" name="origem_cadastro" class="form-control">
                </div>
            </div>

            <div class="mt-5 text-center">
                <button type="submit" class="btn btn-enviar btn-lg" id="btn-submit">Enviar Cadastro</button>
            </div>
        </form>
    </div>
</div>

<!-- Scripts Essenciais (jQuery + Mask + SweetAlert) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function(){
    // Aplica Máscaras
    $('#telefone').mask('(00) 00000-0000');
    $('#cep').mask('00000-000');

    // Troca a máscara de CPF/CNPJ e define se IE/RG é obrigatório
    $('input[name="tipo_pessoa"]').change(function() {
        if($(this).val() == 'Fisica') {
            $('#cpf_cnpj').mask('000.000.000-00', {reverse: true});
            // Tira a obrigatoriedade do RG para Pessoa Física
            $('input[name="rg_ie"]').prop('required', false);
        } else {
            $('#cpf_cnpj').mask('00.000.000/0000-00', {reverse: true});
            // Torna a IE obrigatória para Pessoa Jurídica
            $('input[name="rg_ie"]').prop('required', true);
        }
    });

    // ==========================================
    // BUSCA AUTOMÁTICA DE CNPJ (BRASIL API)
    // ==========================================
    $('#cpf_cnpj').on('blur', function() {
        let documento = $(this).val().replace(/\D/g, '');
        
        // Verifica se tem 14 dígitos (CNPJ)
        if (documento.length === 14) {
            console.log("Consultando CNPJ...");

            $.getJSON(`https://brasilapi.com.br/api/cnpj/v1/${documento}`, function(dados) {
                if (dados && dados.cnpj) {
                    // Preenche o Nome/Razão Social
                    $('input[name="nome_completo"]').val(dados.razao_social || dados.nome_fantasia || '');
                    
                    // Preenche E-mail e Telefone caso a API traga
                    if (dados.email) {
                        $('input[name="email"]').val(dados.email);
                    }
                    if (dados.ddd_telefone_1) {
                        $('input[name="telefone"]').val(`(${dados.ddd_telefone_1}) ${dados.telefone_1}`);
                    }

                    // Tenta preencher a Inscrição Estadual (RG/IE) se a API retornar listagem de inscrições estaduais ativas
                    if (dados.inscricoes_estaduais && dados.inscricoes_estaduais.length > 0) {
                        // Pega a primeira inscrição estadual ativa da lista
                        let ieAtiva = dados.inscricoes_estaduais.find(i => i.ativo) || dados.inscricoes_estaduais[0];
                        if (ieAtiva && ieAtiva.inscricao_estadual) {
                            $('input[name="rg_ie"]').val(ieAtiva.inscricao_estadual);
                        }
                    }

                    // Se houver CEP retornado, dispara a busca do ViaCEP automaticamente
                    if (dados.cep) {
                        let cepEmpresa = dados.cep.replace(/\D/g, '');
                        $('#cep').val(cepEmpresa).trigger('blur');
                    }
                }
            }).fail(function() {
                Swal.fire('Atenção', 'CNPJ não encontrado na base da Receita Federal.', 'warning');
            });
        }
    });

    // Busca automática de endereço pelo CEP
    $('#cep').on('blur', function() {
        // Tira o traço do CEP para a busca
        let cepBuscar = $(this).val().replace(/\D/g, '');
        
        if (cepBuscar.length === 8) {
            // Coloca um texto de carregando
            $('input[name="rua_coleta"]').val('Buscando...');
            
            // Consome a API do ViaCEP
            $.getJSON(`https://viacep.com.br/ws/${cepBuscar}/json/`, function(dados) {
                if (!("erro" in dados)) {
                    $('input[name="rua_coleta"]').val(dados.logradouro);
                    $('input[name="bairro_coleta"]').val(dados.bairro);
                    $('input[name="cidade_coleta"]').val(dados.localidade);
                    $('input[name="estado_coleta"]').val(dados.uf);
                    
                    // Joga o cursor para o cliente digitar o número
                    $('input[name="numero_coleta"]').focus();
                } else {
                    Swal.fire('Atenção', 'CEP não encontrado.', 'warning');
                    $('input[name="rua_coleta"]').val('');
                }
            });
        }
    });

    // Envio do formulário via AJAX
    $('#form-cadastro').on('submit', function(e){
        e.preventDefault();
        
        $('#btn-submit').prop('disabled', true).text('Enviando aguarde...');

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            success: function(response){
                if(response.sucesso){
                    $('#form-cadastro').slideUp();
                    $('#mensagem-sucesso').removeClass('d-none').hide().fadeIn();
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Tudo certo!',
                        text: response.mensagem,
                        confirmButtonColor: '#0f766e'
                    });
                }
            },
            error: function(){
                Swal.fire('Erro!', 'Ocorreu um erro ao enviar. Tente novamente.', 'error');
                $('#btn-submit').prop('disabled', false).text('Enviar Cadastro');
            }
        });
    });
});
</script>

</body>
</html>