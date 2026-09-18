@extends('default.layout')
@section('content')

    <div class="d-flex flex-column flex-column-fluid" id="kt_content">
        <div class="card card-custom gutter-b shadow-sm border-0">
            <div class="card-header border-0 pt-6 pb-2">
                <h3 class="card-title font-weight-bolder text-dark">
                    <i class="la la-user-tie text-primary mr-2 font-size-h3"></i> {{ isset($funcionario) ? 'Editar' : 'Novo' }} Colaborador
                </h3>
                <div class="card-toolbar">
                    <a href="/funcionarios" class="btn btn-light-dark font-weight-bold btn-sm">
                        <i class="la la-arrow-left"></i> Voltar à Listagem
                    </a>
                </div>
            </div>

            <div class="card-body pt-2">
                <form method="post" action="/funcionarios/{{{ isset($funcionario) ? 'update' : 'save' }}}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="id" value="{{{ isset($funcionario) ? $funcionario->id : 0 }}}">

                    <!-- 1. DADOS PESSOAIS & FOTO -->
                    <div class="card card-custom bg-light-secondary card-stretch mb-5 border-0">
                        <div class="card-body p-5">
                            <h6 class="font-weight-bolder text-dark mb-4">
                                <i class="la la-id-card text-primary mr-1"></i> Dados Cadastrais Principais
                            </h6>
                            <div class="row align-items-center">
                                <!-- Coluna da Foto -->
                                <div class="col-lg-2 col-md-3 text-center mb-4 mb-md-0">
                                    <label class="font-weight-bold d-block text-dark font-size-sm mb-2">Foto de Perfil</label>
                                    <div class="d-inline-block p-2 bg-white rounded shadow-sm foto-wrapper" style="width: 140px; height: 160px; overflow: hidden; position: relative;">
                                        <x-image-upload :image-url="isset($funcionario->foto_funcionario) ? '/imgs_funcionarios/'.$funcionario->foto_funcionario : '/imgs/no_image.png'" title="" input-name="file" />
                                    </div>
                                </div>

                                <style>
                                    /* Oculta o texto de extensão que quebra verticalmente */
                                    .foto-wrapper span.form-text,
                                    .foto-wrapper .text-muted,
                                    .foto-wrapper small {
                                        display: none !important;
                                    }
                                </style>

                                <!-- Dados Pessoais Direita -->
                                <div class="col-lg-10 col-md-9">
                                    <div class="row">
                                        <div class="form-group validated col-lg-6 col-md-12 mb-3">
                                            <label class="font-weight-bold">Nome Completo <span class="text-danger">*</span></label>
                                            <input id="nome" type="text" class="form-control @if($errors->has('nome')) is-invalid @endif" name="nome" value="{{{ isset($funcionario) ? $funcionario->nome : old('nome') }}}" required>
                                            @if($errors->has('nome'))
                                                <div class="invalid-feedback">{{ $errors->first('nome') }}</div>
                                            @endif
                                        </div>

                                        <div class="form-group validated col-lg-3 col-md-6 mb-3">
                                            <label class="font-weight-bold">CPF <span class="text-danger">*</span></label>
                                            <input type="text" id="cpf" class="form-control cpf @if($errors->has('cpf')) is-invalid @endif" name="cpf" value="{{{ isset($funcionario) ? $funcionario->cpf : old('cpf') }}}" required>
                                            @if($errors->has('cpf'))
                                                <div class="invalid-feedback">{{ $errors->first('cpf') }}</div>
                                            @endif
                                        </div>

                                        <div class="form-group validated col-lg-3 col-md-6 mb-3">
                                            <label class="font-weight-bold">RG</label>
                                            <input type="text" id="rg" class="form-control @if($errors->has('rg')) is-invalid @endif" name="rg" value="{{{ isset($funcionario) ? $funcionario->rg : old('rg') }}}">
                                        </div>

                                        <div class="form-group col-lg-3 col-md-6 mb-2">
                                            <label class="font-weight-bold">Data de Nascimento</label>
                                            <div class="input-group date">
                                                <input type="text" name="data_nascimento" class="form-control" readonly value="{{ isset($funcionario->data_nascimento) ? \Carbon\Carbon::parse($funcionario->data_nascimento)->format('d/m/Y') : old('data_nascimento') }}" id="kt_datepicker_3" />
                                                <div class="input-group-append"><span class="input-group-text"><i class="la la-calendar"></i></span></div>
                                            </div>
                                        </div>

                                        <div class="form-group col-lg-3 col-md-6 mb-2">
                                            <label class="font-weight-bold">Data de Admissão</label>
                                            <div class="input-group date">
                                                <input type="text" name="data_admissao" class="form-control" readonly value="{{ isset($funcionario->data_admissao) ? \Carbon\Carbon::parse($funcionario->data_admissao)->format('d/m/Y') : old('data_admissao') }}" id="kt_datepicker_3" />
                                                <div class="input-group-append"><span class="input-group-text"><i class="la la-calendar"></i></span></div>
                                            </div>
                                        </div>

                                        <div class="form-group col-lg-3 col-md-6 mb-2">
                                            <label class="font-weight-bold">Tipo Sanguíneo</label>
                                            <select class="custom-select form-control" name="tipo_sanguineo">
                                                <option value="">Selecione</option>
                                                @foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $tipo)
                                                    <option value="{{ $tipo }}" {{ isset($funcionario) && $funcionario->tipo_sanguineo == $tipo ? 'selected' : '' }}>{{ $tipo }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="form-group col-lg-3 col-md-6 mb-2">
                                            <label class="font-weight-bold">Status do Funcionário</label>
                                            <select class="custom-select form-control" name="status_funcionario">
                                                <option value="Ativo" {{ isset($funcionario) && $funcionario->status_funcionario == 'Ativo' ? 'selected' : '' }}>Ativo</option>
                                                <option value="Desligado" {{ isset($funcionario) && $funcionario->status_funcionario == 'Desligado' ? 'selected' : '' }}>Desligado</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. ENDEREÇO E CONTATO -->
                    <div class="card card-custom border mb-5">
                        <div class="card-header border-0 py-3 min-h-40px bg-light-light">
                            <h6 class="card-title font-weight-bolder text-dark mb-0">
                                <i class="la la-home text-primary mr-2 font-size-lg"></i> Localização & Comunicação
                            </h6>
                        </div>
                        <div class="card-body py-4">
                            <div class="row">
                                <div class="form-group validated col-lg-2 col-md-4">
                                    <label class="font-weight-bold">CEP</label>
                                    <input id="cep" type="text" class="form-control cep" name="cep" value="{{{ isset($funcionario) ? $funcionario->cep : old('cep') }}}" placeholder="00000-000">
                                </div>
                                <div class="form-group validated col-lg-6 col-md-8">
                                    <label class="font-weight-bold">Rua / Logradouro</label>
                                    <input id="rua" type="text" class="form-control" name="rua" value="{{{ isset($funcionario) ? $funcionario->rua : old('rua') }}}">
                                </div>
                                <div class="form-group validated col-lg-2 col-md-6">
                                    <label class="font-weight-bold">Número</label>
                                    <input id="numero" type="text" class="form-control" name="numero" value="{{{ isset($funcionario) ? $funcionario->numero : old('numero') }}}">
                                </div>
                                <div class="form-group validated col-lg-2 col-md-6">
                                    <label class="font-weight-bold">Bairro</label>
                                    <input id="bairro" type="text" class="form-control" name="bairro" value="{{{ isset($funcionario) ? $funcionario->bairro : old('bairro') }}}">
                                </div>
                            </div>

                            <div class="row">
                                <div class="form-group validated col-lg-4 col-md-6">
                                    <label class="font-weight-bold">E-mail Profissional</label>
                                    <input id="email" type="email" class="form-control" name="email" value="{{{ isset($funcionario) ? $funcionario->email : old('email') }}}" placeholder="nome@empresa.com.br">
                                </div>
                                <div class="form-group validated col-lg-4 col-md-6">
                                    <label class="font-weight-bold">Telefone Fixo</label>
                                    <input id="telefone" type="text" class="form-control" name="telefone" value="{{{ isset($funcionario) ? $funcionario->telefone : old('telefone') }}}">
                                </div>
                                <div class="form-group validated col-lg-4 col-md-6">
                                    <label class="font-weight-bold text-success"><i class="la la-whatsapp text-success mr-1"></i> Celular (WhatsApp)</label>
                                    <input id="celular" type="text" class="form-control" name="celular" value="{{{ isset($funcionario) ? $funcionario->celular : old('celular') }}}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. FUNÇÃO, ESCALA E REMUNERAÇÃO -->
                    <div class="card card-custom border mb-5">
                        <div class="card-header border-0 py-3 min-h-40px bg-light-light">
                            <h6 class="card-title font-weight-bolder text-dark mb-0">
                                <i class="la la-briefcase text-primary mr-2 font-size-lg"></i> Cargo, Jornada e Remuneração
                            </h6>
                        </div>
                        <div class="card-body py-4">
                            <div class="row">
                                <!-- Função com Botão + Integrado -->
                                <div class="form-group validated col-lg-4 col-md-6">
                                    <label class="font-weight-bold">Função / Cargo</label>
                                    <div class="d-flex">
                                        <div class="flex-grow-1 mr-2">
                                            <select class="form-control custom-select select2" name="funcao_id" id="funcao_id" style="width: 100%;">
                                                <option value="">Selecione a Função...</option>
                                                @foreach($funcoes as $f)
                                                    <option value="{{$f->id}}" @if(isset($funcionario) && $funcionario->funcao_id == $f->id) selected @endif>{{$f->nome}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <button class="btn btn-primary font-weight-bolder" type="button" data-toggle="modal" data-target="#modal_funcao" title="Nova Função" style="height: calc(1.5em + 1.3rem + 2px);">
                                            <i class="la la-plus p-0"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Escala de Trabalho -->
                                <div class="form-group validated col-lg-4 col-md-6">
                                    <label class="font-weight-bold text-primary">Escala de Trabalho / Ponto</label>
                                    <select class="form-control custom-select" name="ponto_escala_id">
                                        <option value="">Padrão CLT 44h (Sem escala vinculada)</option>
                                        @if(isset($escalas))
                                            @foreach($escalas as $e)
                                                <option value="{{ $e->id }}" {{ (isset($funcionario) && $funcionario->ponto_escala_id == $e->id) ? 'selected' : '' }}>
                                                    {{ $e->nome }} ({{ strtoupper($e->tipo) }})
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                    <small class="text-muted">Calcula banco de horas, faltas e tolerância diária.</small>
                                </div>

                                <!-- Unidade / Filial -->
                                <div class="form-group validated col-lg-4 col-md-6">
                                    <label class="font-weight-bold">Unidade / Filial</label>
                                    <select class="form-control custom-select" name="filial_id">
                                        <option value="">MATRIZ</option>
                                        @foreach($filiais as $f)
                                            <option value="{{$f->id}}" @if(isset($funcionario) && $funcionario->filial_id == $f->id) selected @endif>
                                                {{ $f->razao_social ?? $f->nome_fantasia ?? 'Filial ' . $f->id }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Usuário ERP -->
                                <div class="form-group validated col-lg-3 col-md-6">
                                    <label class="font-weight-bold">Usuário Vinculado ao ERP</label>
                                    <select class="form-control custom-select" name="usuario_id">
                                        <option value="NULL">-- Nenhum --</option>
                                        @foreach($usuarios as $u)
                                            <option value="{{$u->id}}" @if(isset($funcionario) && $funcionario->usuario_id == $u->id) selected @endif>{{$u->nome}}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Tipo de Batida -->
                                <div class="form-group col-lg-3 col-md-6">
                                    <label class="font-weight-bold">Batida de Ponto Permitida</label>
                                    <select class="custom-select form-control" name="tipo_ponto">
                                        <option value="ambos" {{ isset($funcionario) && $funcionario->tipo_ponto == 'ambos' ? 'selected' : '' }}>Ambos (WhatsApp e Local)</option>
                                        <option value="whatsapp" {{ isset($funcionario) && $funcionario->tipo_ponto == 'whatsapp' ? 'selected' : '' }}>Somente WhatsApp</option>
                                        <option value="local" {{ isset($funcionario) && $funcionario->tipo_ponto == 'local' ? 'selected' : '' }}>Somente Presencial</option>
                                    </select>
                                </div>

                                <!-- PIN Ponto -->
                                <div class="form-group col-lg-2 col-md-4">
                                    <label class="font-weight-bold">PIN Ponto (4 dígitos)</label>
                                    <input id="pin_ponto" type="text" class="form-control text-center font-weight-bold letter-spacing-2" name="pin_ponto" value="{{{ isset($funcionario) ? $funcionario->pin_ponto : old('pin_ponto') }}}" maxlength="4" placeholder="Ex: 1234">
                                </div>

                                <!-- Salário Base -->
                                <div class="form-group validated col-lg-2 col-md-4">
                                    <label class="font-weight-bold">Salário Base (R$)</label>
                                    <input id="salario" type="text" class="form-control money" name="salario" value="{{{ isset($funcionario) ? $funcionario->salario : old('salario') }}}">
                                </div>

                                <!-- Comissão -->
                                <div class="form-group validated col-lg-2 col-md-4">
                                    <label class="font-weight-bold">Comissão (%)</label>
                                    <input id="percentual_comissao" type="text" class="form-control money" name="percentual_comissao" value="{{{ isset($funcionario) ? $funcionario->percentual_comissao : old('percentual_comissao') }}}">
                                </div>
                            </div>

                            <div class="row pt-2 align-items-center">
                                <div class="form-group col-lg-3 col-md-6 mb-2">
                                    <label class="font-weight-bold">Matrícula (Ponto/AFD)</label>
                                    <input type="text" name="matricula" class="form-control" value="{{ isset($funcionario->matricula) ? $funcionario->matricula : old('matricula') }}">
                                </div>
                                <div class="form-group col-lg-3 col-md-6 mb-2">
                                    <label class="font-weight-bold">PIS / PASEP</label>
                                    <input type="text" name="pis" class="form-control" value="{{ isset($funcionario->pis) ? $funcionario->pis : old('pis') }}">
                                </div>
                                <div class="form-group col-lg-3 col-md-6 mb-2">
                                    <label class="font-weight-bold">Data de Registro</label>
                                    <div class="input-group date">
                                        <input type="text" name="data_registro" class="form-control @if($errors->has('data_registro')) is-invalid @endif" readonly value="{{{ isset($funcionario->data_registro) ? \Carbon\Carbon::parse($funcionario->data_registro)->format('d/m/Y') : old('data_registro') }}}" id="kt_datepicker_3" />
                                        <div class="input-group-append"><span class="input-group-text"><i class="la la-calendar"></i></span></div>
                                    </div>
                                </div>
                                <div class="form-group col-lg-3 col-md-6 mb-2">
                                    <label class="font-weight-bold d-block">Alertas de Logística</label>
                                    <div class="d-flex align-items-center mt-2">
        <span class="switch switch-outline switch-icon switch-success mr-3">
            <label class="mb-0">
                <input type="checkbox" name="recebe_alerta_coleta" value="1" {{ isset($funcionario) && $funcionario->recebe_alerta_coleta ? 'checked' : '' }} />
                <span></span>
            </label>
        </span>
                                        <span class="font-weight-bold text-dark">Alertas Coleta (WhatsApp)</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 4. SEÇÃO DINÂMICA DE MOTORISTA (EXIBIDA SE A FUNÇÃO FOR MOTORISTA) -->
                        <div id="secao_motorista" style="display: none;">
                            <div class="card card-custom border border-primary mb-5">
                                <div class="card-header border-0 py-3 min-h-40px bg-light-primary">
                                    <h6 class="card-title font-weight-bolder text-primary mb-0">
                                        <i class="la la-truck text-primary mr-2 font-size-lg"></i> Dados do Motorista & Geolocalização
                                    </h6>
                                </div>
                                <div class="card-body py-4">
                                    <div class="row">
                                        <div class="form-group col-lg-3 col-md-6">
                                            <label class="font-weight-bold">Número da CNH</label>
                                            <input id="cnh" type="text" class="form-control" name="cnh" value="{{{ isset($funcionario) ? $funcionario->cnh : old('cnh') }}}">
                                        </div>

                                        <div class="form-group col-lg-2 col-md-6">
                                            <label class="font-weight-bold">Categoria CNH</label>
                                            <select class="custom-select form-control" name="categoria_cnh">
                                                <option value="">--</option>
                                                @foreach(['A','B','C','D','E', 'AB', 'AC', 'AD', 'AE'] as $cat)
                                                    <option value="{{$cat}}" @if(isset($funcionario) && $funcionario->categoria_cnh == $cat) selected @endif>{{$cat}}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="form-group col-lg-3 col-md-6">
                                            <label class="font-weight-bold">Vencimento da CNH</label>
                                            <div class="input-group date">
                                                <input type="text" name="vencimento_cnh" class="form-control" readonly value="{{ isset($funcionario->vencimento_cnh) && $funcionario->vencimento_cnh ? \Carbon\Carbon::parse($funcionario->vencimento_cnh)->format('d/m/Y') : old('vencimento_cnh') }}" id="kt_datepicker_3" />
                                                <div class="input-group-append"><span class="input-group-text"><i class="la la-calendar"></i></span></div>
                                            </div>
                                        </div>

                                        <div class="form-group col-lg-2 col-md-6">
                                            <label class="font-weight-bold">Status do Motorista</label>
                                            <select class="custom-select form-control" name="status_motorista">
                                                <option value="Ativo" @if(isset($funcionario) && $funcionario->status_motorista == 'Ativo') selected @endif>Ativo</option>
                                                <option value="Inativo" @if(isset($funcionario) && $funcionario->status_motorista == 'Inativo') selected @endif>Inativo</option>
                                            </select>
                                        </div>

                                        <div class="form-group col-lg-2 col-md-6">
                                            <label class="font-weight-bold">ID Traccar (IMEI)</label>
                                            <input type="text" class="form-control" name="traccar_id" value="{{{ isset($funcionario) ? $funcionario->traccar_id : old('traccar_id') }}}" placeholder="Ex: 8645000...">
                                        </div>
                                    </div>

                                    <!-- Coordenadas -->
                                    <div class="row p-3 rounded mt-2 bg-light">
                                        <div class="col-12 mb-2">
                                    <span class="font-weight-bold text-dark font-size-sm">
                                        <i class="la la-map-marker text-danger mr-1"></i> Coordenadas da Residência (Usado quando a partida da rota for de casa)
                                    </span>
                                        </div>
                                        <div class="form-group col-lg-6 col-md-6 mb-0">
                                            <label class="font-size-xs text-muted">Latitude</label>
                                            <input type="text" class="form-control" name="latitude_residencia" placeholder="Ex: -12.97140000" value="{{{ isset($funcionario) ? $funcionario->latitude_residencia : old('latitude_residencia') }}}">
                                        </div>
                                        <div class="form-group col-lg-6 col-md-6 mb-0">
                                            <label class="font-size-xs text-muted">Longitude</label>
                                            <input type="text" class="form-control" name="longitude_residencia" placeholder="Ex: -38.50140000" value="{{{ isset($funcionario) ? $funcionario->longitude_residencia : old('longitude_residencia') }}}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- BOTÕES DE AÇÃO FIXOS/CENTRALIZADOS -->
                        <div class="d-flex justify-content-between align-items-center pt-4 border-top">
                            <a class="btn btn-outline-danger font-weight-bold px-5" href="/funcionarios">
                                <i class="la la-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-success font-weight-bold px-8 shadow-sm">
                                <i class="la la-check"></i> Salvar Colaborador
                            </button>
                        </div>
                </form>
            </div>
        </div>
    </div>

    <x-image-modal :image-url="isset($funcionario->foto_funcionario) ? '/imgs_funcionarios/'.$funcionario->foto_funcionario : '/imgs/no_image.png'" title="Foto Funcionário" />

    <!-- MODAL NOVA FUNÇÃO -->
    <div class="modal fade" id="modal_funcao" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="false">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold">Cadastrar Nova Função</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <i aria-hidden="true" class="ki ki-close"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-0">
                        <label class="font-weight-bold">Nome do Cargo / Função</label>
                        <input type="text" id="nome_funcao_quick" class="form-control" placeholder="Ex: Motorista Carreteiro / Operador de Produção">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-dark font-weight-bold" data-dismiss="modal">Fechar</button>
                    <button type="button" id="btn_save_funcao_quick" class="btn btn-primary font-weight-bold">Salvar Função</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('javascript')
    <script>
        $(document).ready(function() {
            if($('.select2').length) {
                $('.select2').select2({ width: '100%' });
            }

            function verificaMotorista() {
                let nomeFuncao = $('#funcao_id option:selected').text().toLowerCase();
                if (nomeFuncao.includes('motorista')) {
                    $('#secao_motorista').slideDown();
                } else {
                    $('#secao_motorista').slideUp();
                }
            }

            verificaMotorista();

            $('#funcao_id').change(function() {
                verificaMotorista();
            });

            $('#cep').blur(function() {
                let cep = $(this).val().replace(/\D/g, '');
                if (cep !== "") {
                    let validacep = /^[0-9]{8}$/;
                    if(validacep.test(cep)) {
                        $('#rua').val("Carregando...");
                        $('#bairro').val("Carregando...");

                        $.getJSON("https://viacep.com.br/ws/"+ cep + "/json/?callback=?", function(dados) {
                            if (!("erro" in dados)) {
                                $('#rua').val(dados.logradouro);
                                $('#bairro').val(dados.bairro);
                                $('#numero').focus();
                            } else {
                                $('#rua').val("");
                                $('#bairro').val("");
                                alert("CEP não encontrado.");
                            }
                        });
                    } else {
                        alert("Formato de CEP inválido.");
                    }
                }
            });

            $('#btn_save_funcao_quick').click(function() {
                let nome = $('#nome_funcao_quick').val();
                let empresa_id = "{{ $empresa_id }}";

                if (nome == "") {
                    alert("Por favor, informe o nome da função.");
                    return;
                }

                let btn = $(this);
                btn.prop('disabled', true).text('Salvando...');

                $.post('/funcoes/quickSave', {
                    _token: '{{ csrf_token() }}',
                    nome: nome,
                    empresa_id: empresa_id
                })
                    .done(function(data) {
                        if (data.success) {
                            let newOption = new Option(data.nome, data.id, true, true);
                            $('#funcao_id').append(newOption).trigger('change');

                            $('#nome_funcao_quick').val('');
                            $('#modal_funcao').modal('hide');

                            alert("Função cadastrada com sucesso!");
                        } else {
                            alert("Erro: " + data.message);
                        }
                    })
                    .fail(function() {
                        alert("Erro de comunicação com o servidor.");
                    })
                    .always(function() {
                        btn.prop('disabled', false).text('Salvar Função');
                    });
            });
        });
    </script>
@endsection
