@extends('default.layout')

@section('content')
<style>
    .form-control, .form-select, .select2-container--default .select2-selection--single {
        border: 1px solid #a1a8c3 !important;
        border-radius: 4px !important;
        min-height: 38px !important;
        background-color: #ffffff !important;
    }
    .border-box {
        border: 1px solid #cbd5e1 !important;
        background-color: #f8fafc;
        border-radius: 6px;
        padding: 18px;
        margin-bottom: 20px;
    }
</style>

<div class="card card-custom gutter-b">
    <div class="card-header bg-primary py-3">
        <div class="card-title">
            <h3 class="card-label text-white font-weight-bolder">
                <i class="fa fa-edit text-white mr-2"></i> Editar Unidade / Credencial MTR (#{{ $unidade->id }})
            </h3>
        </div>
    </div>

    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-custom alert-light-danger fade show mb-4" role="alert">
                <div class="alert-icon"><i class="flaticon-warning"></i></div>
                <div class="alert-text">
                    <strong>Atenção! Por favor verifique os erros abaixo:</strong>
                    <ul class="mb-0 mt-1 pl-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @if (session('erro'))
            <div class="alert alert-danger mb-4">{{ session('erro') }}</div>
        @endif

        <form action="{{ route('mtr.unidades.update', $unidade->id) }}" method="POST">
            @csrf

            <!-- BLOCO 1: DADOS DA UNIDADE -->
            <div class="border-box">
                <h5 class="text-primary font-weight-bold mb-3">
                    <span class="badge badge-primary mr-2">1</span> Identificação do Empreendimento (Fersoft)
                </h5>
                <div class="row">
                    <div class="col-md-3 form-group">
                        <label class="font-weight-bold">Órgão Ambientador: <span class="text-danger">*</span></label>
                        <select name="orgao" id="orgao" class="form-control" required>
                            <option value="SINIR" {{ old('orgao', $unidade->orgao) == 'SINIR' ? 'selected' : '' }}>SINIR (Nacional)</option>
                            <option value="IEMA" {{ old('orgao', $unidade->orgao) == 'IEMA' ? 'selected' : '' }}>IEMA (Espírito Santo)</option>
                            <option value="SIGOR" {{ old('orgao', $unidade->orgao) == 'SIGOR' ? 'selected' : '' }}>SIGOR (São Paulo)</option>
                            <option value="FEAM" {{ old('orgao', $unidade->orgao) == 'FEAM' ? 'selected' : '' }}>FEAM (Minas Gerais)</option>
                        </select>
                    </div>

                    <div class="col-md-3 form-group">
                        <label class="font-weight-bold">Perfil no MTR: <span class="text-danger">*</span></label>
                        <select name="perfil" id="perfil_mtr" class="form-control" required>
                            <option value="Gerador" {{ old('perfil', $unidade->perfil) == 'Gerador' ? 'selected' : '' }}>Gerador (Sua Empresa)</option>
                            <option value="Transportador" {{ old('perfil', $unidade->perfil) == 'Transportador' ? 'selected' : '' }}>Transportador</option>
                            <option value="Destinador" {{ old('perfil', $unidade->perfil) == 'Destinador' ? 'selected' : '' }}>Destinador / Recebedor</option>
                            <option value="Armazenador" {{ old('perfil', $unidade->perfil) == 'Armazenador' ? 'selected' : '' }}>Armazenador Temporário</option>
                        </select>
                    </div>

                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold">Puxar do Cadastro de Clientes / Parceiros:</label>
                        <select name="cliente_id" id="cliente_id" class="form-control select2">
                            <option value="">-- Selecione para atualizar dados --</option>
                            @foreach($clientes as $c)
                                <option value="{{ $c->id }}" data-cnpj="{{ $c->cpf_cnpj }}" {{ old('cpf_cnpj', $unidade->cpf_cnpj) == $c->cpf_cnpj ? 'selected' : '' }}>
                                    {{ $c->razao_social }} ({{ $c->cpf_cnpj }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3 form-group">
                        <label class="font-weight-bold">CNPJ / CPF Empreendimento: <span class="text-danger">*</span></label>
                        <input type="text" name="cpf_cnpj" id="cpf_cnpj" class="form-control" value="{{ old('cpf_cnpj', $unidade->cpf_cnpj) }}" placeholder="Somente números" required>
                    </div>

                    <div class="col-md-3 form-group">
                        <label class="font-weight-bold">Cód. Unidade no Portal MTR: <span class="text-danger">*</span></label>
                        <input type="text" name="unidade_id" id="unidade_id" class="form-control" value="{{ old('unidade_id', $unidade->unidade_id) }}" placeholder="Ex: 569194" required>
                    </div>

                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold">Descrição / Nome Fantasia:</label>
                        <input type="text" name="descricao" id="descricao" class="form-control" value="{{ old('descricao', $unidade->descricao) }}" placeholder="Ex: Unidade Dias D'Ávila">
                    </div>
                </div>
            </div>

            <!-- BLOCO 2: LOGIN DO GERADOR -->
            <div class="border-box" id="box_login_gerador">
                <h5 class="text-primary font-weight-bold mb-3">
                    <span class="badge badge-primary mr-2">2</span> Credenciais de Login no Portal (Apenas para GERADOR)
                </h5>
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label class="font-weight-bold">CPF do Usuário Gerador:</label>
                        <input type="text" name="cpf_usuario" id="cpf_usuario" class="form-control" value="{{ old('cpf_usuario', $unidade->cpf_usuario) }}" placeholder="CPF cadastrado no SINIR">
                    </div>

                    <div class="col-md-4 form-group">
                        <label class="font-weight-bold">Senha do Portal MTR:</label>
                        <input type="password" name="senha" id="senha" class="form-control" value="" placeholder="Apenas preencha se desejar alterar a senha">
                    </div>

                    <div class="col-md-4 form-group">
                        <label class="font-weight-bold">Ambiente de Emissão: <span class="text-danger">*</span></label>
                        <select name="ambiente" id="ambiente" class="form-control" required>
                            <option value="homologacao" {{ old('ambiente', $unidade->ambiente) == 'homologacao' ? 'selected' : '' }}>Homologação (Testes)</option>
                            <option value="producao" {{ old('ambiente', $unidade->ambiente) == 'producao' ? 'selected' : '' }}>Produção (Oficial)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group mb-0">
                    <label class="checkbox">
                        <input type="checkbox" name="ativo" value="1" {{ old('ativo', $unidade->ativo) ? 'checked' : '' }}>
                        <span></span> Credencial Ativa para Emissão
                    </label>
                </div>
            </div>

            <!-- BOTÕES -->
            <div class="d-flex justify-content-between mt-4">
                <button type="button" class="btn btn-info font-weight-bold" id="btnTestarConexao">
                    <i class="fa fa-plug mr-1"></i> Testar Login no Órgão
                </button>

                <div>
                    <a href="{{ route('mtr.unidades.index') }}" class="btn btn-secondary mr-2">
                        <i class="fa fa-arrow-left"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-success font-weight-bold px-5">
                        <i class="fa fa-save"></i> Atualizar Unidade
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- SCRIPT INJETADO DIRETAMENTE COM PROTEÇÃO DE CARREGAMENTO DO JQUERY        -->
<!-- ========================================================================= -->
<script>
    // Função recursiva para esperar o jQuery existir na tela
    function iniciarScriptMTR() {
        if (typeof window.jQuery === 'undefined') {
            setTimeout(iniciarScriptMTR, 100);
            return;
        }

        var $ = window.jQuery;
        
        $(document).ready(function() {
            console.log("✅ Script do MTR carregado com sucesso!");

            // 1. Inicia o componente de busca de clientes
            if ($.fn.select2) {
                $('.select2').select2({ width: '100%', placeholder: "-- Selecione --", allowClear: true });
            }

            // 2. Ao selecionar cliente, preenche CNPJ e Nome
            $('#cliente_id').on('change', function() {
                var cnpj = $(this).find(':selected').data('cnpj');
                var nome = $(this).find(':selected').text().split('(')[0].trim();
                if (cnpj) {
                    $('#cpf_cnpj').val(cnpj);
                    $('#descricao').val(nome);
                }
            });

            // 3. Testar Login na API via AJAX
            $('#btnTestarConexao').off('click').on('click', function(e) {
                e.preventDefault();
                console.log("⏳ Iniciando teste de conexão...");

                var cpfUsuario = $('#cpf_usuario').val() || $('#cpf_cnpj').val();
                var senha = $('#senha').val();
                var unidadeId = $('#unidade_id').val();
                var orgao = $('#orgao').val();
                var ambiente = $('#ambiente').val();
                var unidadeDbId = "{{ $unidade->id }}";

                if (!cpfUsuario) {
                    alert('⚠️ Preencha o CPF do Usuário Gerador.');
                    $('#cpf_usuario').focus();
                    return;
                }

                var btn = $(this);
                btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Conectando ao SINIR...');

                $.ajax({
                    url: "{{ route('mtr.unidades.testarConexao') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        orgao: orgao,
                        ambiente: ambiente,
                        cpf_usuario: cpfUsuario,
                        senha: senha,
                        unidade_id: unidadeId,
                        unidade_db_id: unidadeDbId 
                    },
                    success: function(res) {
                        console.log("✅ Sucesso na resposta:", res);
                        alert("✅ " + (res.mensagem || "Autenticação realizada com sucesso!"));
                    },
                    error: function(err) {
                        console.log("❌ Erro na resposta:", err);
                        var msg = (err.responseJSON && err.responseJSON.mensagem) ? err.responseJSON.mensagem : "Falha ao conectar com o portal do órgão.";
                        alert("❌ Erro: " + msg);
                    },
                    complete: function() {
                        btn.prop('disabled', false).html('<i class="fa fa-plug mr-1"></i> Testar Login no Órgão');
                    }
                });
            });
        });
    }

    // Dispara a função assim que o HTML chega no navegador
    iniciarScriptMTR();
</script>
@endsection