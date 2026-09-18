@extends('default.layout')
@section('content')
    <style type="text/css">
        .card-modern {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.05);
            background: #ffffff;
            margin-bottom: 1.5rem;
        }
        .area-oficina {
            display: none; /* Inicia oculto até selecionar Oficina */
        }
    </style>

    <div class="d-flex flex-column flex-column-fluid" id="kt_content">
        <div class="card card-custom gutter-b example example-compact">
            <div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
                <div class="col-lg-12">
                    <br>
                    <input type="hidden" id="_token" value="{{csrf_token()}}">
                    <form method="post" action="{{{ isset($ordem) ? '/ordemServico/update': '/ordemServico/save' }}}">
                        <input type="hidden" name="id" value="{{{ isset($ordem->id) ? $ordem->id : 0 }}}">
                        @csrf

                        <div class="card card-custom gutter-b example example-compact">
                            <div class="card-header">
                                <h3 class="card-title">{{{ isset($ordem) ? "Editar": "Adicionar" }}} Ordem de Serviço</h3>
                            </div>

                            <div class="card-body">
                                <!-- CHAVE SELETORA: OS SIMPLES VS OS OFICINA -->
                                <div class="row mb-5 bg-light p-4 rounded border">
                                    <div class="col-12">
                                        <label class="form-label font-weight-bold text-dark fs-6 mb-2">Selecione o Tipo de Atendimento:</label>
                                        <div class="radio-inline">
                                            <label class="radio radio-outline radio-success font-weight-bold me-4">
                                                <input type="radio" name="tipo_os_toggle" value="simples" checked id="tipo_simples" onchange="alternarTipoOS()" />
                                                <span></span> <i class="la la-file-text me-1 fs-5"></i> OS Simples (Serviços Gerais)
                                            </label>
                                            <label class="radio radio-outline radio-primary font-weight-bold">
                                                <input type="radio" name="tipo_os_toggle" value="oficina" id="tipo_oficina" @if(isset($ordem) && $ordem->cliente_veiculo_id) checked @endif onchange="alternarTipoOS()" />
                                                <span></span> <i class="la la-car me-1 fs-5"></i> OS Oficina / Veículos
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- DADOS BÁSICOS (SIMPLES E OFICINA) -->
                                <div class="row">
                                    <div class="form-group validated col-md-6 col-12">
                                        <label class="col-form-label" id="lbl_cpf_cnpj">Cliente <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <select class="form-control select2 cliente @if($errors->has('cliente')) is-invalid @endif" id="kt_select2_1" name="cliente" required>
                                                <option value="">Selecione o cliente...</option>
                                                @foreach($clientes as $c)
                                                    <option @if(old('cliente') == $c->id || (isset($ordem) && $ordem->cliente_id == $c->id)) selected @endif value="{{$c->id}}">{{$c->razao_social}} ({{$c->cpf_cnpj}})</option>
                                                @endforeach
                                            </select>

                                            <button type="button" onclick="novoCliente()" class="btn btn-info btn-sm" title="Cadastrar Novo Cliente">
                                                <i class="la la-plus-circle icon-add"></i> Novo
                                            </button>
                                        </div>
                                        @if($errors->has('cliente'))
                                            <div class="invalid-feedback d-block">{{ $errors->first('cliente') }}</div>
                                        @endif
                                    </div>

                                    <!-- SELEÇÃO DE VEÍCULO (MOSTRADO APENAS EM MODO OFICINA) -->
                                    <div class="form-group validated col-md-6 col-12 area-oficina">
                                        <label class="col-form-label">Veículo / Placa</label>
                                        <div class="input-group">
                                            <select class="form-control select2" id="cliente_veiculo_id" name="cliente_veiculo_id">
                                                <option value="">Selecione o veículo...</option>
                                            </select>

                                            <button type="button" onclick="novoVeiculo()" class="btn btn-primary btn-sm" title="Cadastrar Veículo para este cliente">
                                                <i class="la la-car"></i> + Veículo
                                            </button>
                                        </div>
                                    </div>

                                    {!! __view_locais_select() !!}
                                </div>

                                <!-- CAMPOS EXCLUSIVOS DA OFICINA -->
                                <div class="area-oficina">
                                    <div class="sub-section-title text-primary font-weight-bold border-bottom pb-2 mb-3 mt-2">
                                        <i class="la la-wrench"></i> Informações do Veículo e Diagnóstico
                                    </div>

                                    <div class="row">
                                        <div class="form-group col-lg-3 col-md-4 col-sm-6">
                                            <label class="col-form-label">KM Atual</label>
                                            <input type="number" name="km_veiculo" id="km_veiculo" class="form-control" value="{{ isset($ordem) ? $ordem->km_veiculo : old('km_veiculo') }}" placeholder="Ex: 85000">
                                        </div>

                                        <div class="form-group col-lg-3 col-md-4 col-sm-6">
                                            <label class="col-form-label">Tipo de Manutenção</label>
                                            <select class="custom-select form-control" name="tipo_manutencao" id="tipo_manutencao">
                                                <option value="corretiva" @if(isset($ordem) && $ordem->tipo_manutencao == 'corretiva') selected @endif>Corretiva</option>
                                                <option value="preventiva" @if(isset($ordem) && $ordem->tipo_manutencao == 'preventiva') selected @endif>Preventiva (Troca de óleo/revisão)</option>
                                                <option value="revisao" @if(isset($ordem) && $ordem->tipo_manutencao == 'revisao') selected @endif>Revisão de Rotina</option>
                                                <option value="garantia" @if(isset($ordem) && $ordem->tipo_manutencao == 'garantia') selected @endif>Garantia</option>
                                            </select>
                                        </div>

                                        <div class="form-group col-lg-3 col-md-4 col-sm-6">
                                            <label class="col-form-label">Status do Orçamento / Aceite</label>
                                            <select class="custom-select form-control" name="status_aprovacao" id="status_aprovacao">
                                                <option value="orcamento" @if(isset($ordem) && $ordem->status_aprovacao == 'orcamento') selected @endif>1 - Em Orçamento (Aguardando)</option>
                                                <option value="aprovado" @if(isset($ordem) && $ordem->status_aprovacao == 'aprovado') selected @endif>2 - Aprovado pelo Cliente</option>
                                                <option value="em_andamento" @if(isset($ordem) && $ordem->status_aprovacao == 'em_andamento') selected @endif>3 - Em Execução</option>
                                                <option value="concluido" @if(isset($ordem) && $ordem->status_aprovacao == 'concluido') selected @endif>4 - Concluído</option>
                                                <option value="reprovado" @if(isset($ordem) && $ordem->status_aprovacao == 'reprovado') selected @endif>5 - Recusado pelo Cliente</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group col-md-6 col-12">
                                            <label class="col-form-label">Defeito Relatado pelo Cliente (Queixa)</label>
                                            <textarea class="form-control" name="defeito_relatado" rows="3" placeholder="Ex: Barulho na suspensão dianteira ao frear...">{{{ isset($ordem->defeito_relatado) ? $ordem->defeito_relatado : old('defeito_relatado') }}}</textarea>
                                        </div>

                                        <div class="form-group col-md-6 col-12">
                                            <label class="col-form-label">Diagnóstico Técnico (Mecânico)</label>
                                            <textarea class="form-control" name="diagnostico_tecnico" rows="3" placeholder="Ex: Pastilhas de freio gastas e disco empenado...">{{{ isset($ordem->diagnostico_tecnico) ? $ordem->diagnostico_tecnico : old('diagnostico_tecnico') }}}</textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- CAMPO PADRÃO DE DESCRIÇÃO -->
                                <div class="row">
                                    <div class="form-group col-12">
                                        <label class="col-form-label">Descrição / Observações Gerais da OS</label>
                                        <textarea class="form-control" name="descricao" rows="2" placeholder="Observações gerais da prestação de serviço...">{{{ isset($ordem->descricao) ? $ordem->descricao : old('descricao') }}}</textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer">
                                <div class="row">
                                    <div class="col-lg-3 col-sm-6 col-md-4">
                                        <a style="width: 100%" class="btn btn-danger" href="/ordemServico">
                                            <i class="la la-close"></i> Cancelar
                                        </a>
                                    </div>
                                    <div class="col-lg-3 col-sm-6 col-md-4">
                                        <button style="width: 100%" type="submit" class="btn btn-success">
                                            <i class="la la-check"></i> Salvar e Continuar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL CADASTRAR NOVO CLIENTE -->
    <div class="modal fade" id="modal-cliente" data-backdrop="static" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title text-white"><i class="la la-user-plus"></i> Novo Cliente</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="row">
                                <div class="form-group col-sm-12 col-lg-12">
                                    <label>Pessoa:</label>
                                    <div class="radio-inline">
                                        <label class="radio radio-success">
                                            <input name="group1" type="radio" id="pessoaFisica" checked/>
                                            <span></span> FÍSICA
                                        </label>
                                        <label class="radio radio-success">
                                            <input name="group1" type="radio" id="pessoaJuridica"/>
                                            <span></span> JURÍDICA
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="row align-items-end">
                                <div class="form-group validated col-sm-8 col-lg-6 mb-3">
                                    <label class="col-form-label" id="lbl_cpf_cnpj_modal">CPF</label>
                                    <div class="input-group">
                                        <input type="text" id="cpf_cnpj" class="form-control" name="cpf_cnpj" placeholder="000.000.000-00">
                                        <button type="button" id="btn-consulta-cadastro" onclick="consultaCadastro()" class="btn btn-success" style="display: none;">
                                            <i class="fa fa-search"></i> Consultar
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="form-group validated col-sm-10 col-lg-6 mb-3">
                                    <label class="col-form-label">Razão Social / Nome <span class="text-danger">*</span></label>
                                    <input id="razao_social2" type="text" class="form-control">
                                </div>

                                <div class="form-group validated col-sm-10 col-lg-6 mb-3">
                                    <label class="col-form-label">Nome Fantasia</label>
                                    <input id="nome_fantasia2" type="text" class="form-control">
                                </div>

                                <div class="form-group validated col-sm-3 col-lg-3 mb-3">
                                    <label class="col-form-label" id="lbl_ie_rg_modal">RG</label>
                                    <input type="text" id="ie_rg" class="form-control">
                                </div>

                                <div class="form-group validated col-lg-3 col-md-3 col-sm-6 mb-3">
                                    <label class="col-form-label">Consumidor Final</label>
                                    <select class="custom-select form-control" id="consumidor_final">
                                        <option value="1">SIM</option>
                                        <option value="0">NÃO</option>
                                    </select>
                                </div>

                                <div class="form-group validated col-lg-3 col-md-3 col-sm-6 mb-3">
                                    <label class="col-form-label">Contribuinte</label>
                                    <select class="custom-select form-control" id="contribuinte">
                                        <option value="1">SIM</option>
                                        <option value="0">NÃO</option>
                                    </select>
                                </div>

                                <div class="form-group validated col-sm-3 col-lg-3 mb-3">
                                    <label class="col-form-label">Limite de Venda</label>
                                    <input type="text" id="limite_venda" class="form-control money" value="0,00">
                                </div>
                            </div>

                            <hr>
                            <h5 class="text-primary"><i class="la la-map-marker"></i> Endereço de Faturamento</h5>

                            <div class="row">
                                <div class="form-group validated col-sm-8 col-lg-2 mb-3">
                                    <label class="col-form-label">CEP</label>
                                    <input id="cep" type="text" class="form-control cep">
                                </div>

                                <div class="form-group validated col-sm-8 col-lg-4 mb-3">
                                    <label class="col-form-label">Rua</label>
                                    <input id="rua" type="text" class="form-control">
                                </div>

                                <div class="form-group validated col-sm-2 col-lg-2 mb-3">
                                    <label class="col-form-label">Número</label>
                                    <input id="numero2" type="text" class="form-control">
                                </div>

                                <div class="form-group validated col-sm-8 col-lg-4 mb-3">
                                    <label class="col-form-label">Bairro</label>
                                    <input id="bairro" type="text" class="form-control">
                                </div>

                                @php
                                    $cidadeConfig = App\Models\Cidade::getCidadeCod($config->codMun);
                                @endphp
                                <div class="form-group validated col-lg-6 col-md-6 col-sm-12 mb-3">
                                    <label class="col-form-label">Cidade</label>
                                    <select style="width: 100%" class="form-control select2" id="kt_select2_4">
                                        @foreach(App\Models\Cidade::all() as $c)
                                            <option @if($cidadeConfig && $cidadeConfig->id == $c->id) selected @endif value="{{$c->id}}">
                                                {{$c->nome}} ({{$c->uf}})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group validated col-sm-8 col-lg-6 mb-3">
                                    <label class="col-form-label">E-mail</label>
                                    <input id="email" type="email" class="form-control">
                                </div>

                                <div class="form-group validated col-sm-8 col-lg-3 mb-3">
                                    <label class="col-form-label">Telefone</label>
                                    <input id="telefone" type="text" class="form-control">
                                </div>

                                <div class="form-group validated col-sm-8 col-lg-3 mb-3">
                                    <label class="col-form-label">Celular</label>
                                    <input id="celular" type="text" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">Fechar</button>
                    <button type="button" onclick="salvarCliente()" class="btn btn-success font-weight-bold px-6">
                        <i class="fa fa-save me-1"></i> Salvar Cliente
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL CADASTRAR NOVO VEÍCULO -->
    <div class="modal fade" id="modal-veiculo" data-backdrop="static" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white"><i class="fa fa-car me-2"></i> Cadastrar Novo Veículo</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="form-group col-md-4 col-sm-6 mb-3">
                            <label class="form-label">Placa <span class="text-danger">*</span></label>
                            <input type="text" id="v_placa" class="form-control text-uppercase" placeholder="ABC1D23" required />
                        </div>
                        <div class="form-group col-md-4 col-sm-6 mb-3">
                            <label class="form-label">Marca</label>
                            <input type="text" id="v_marca" class="form-control" placeholder="Ex: Chevrolet" />
                        </div>
                        <div class="form-group col-md-4 col-sm-6 mb-3">
                            <label class="form-label">Modelo</label>
                            <input type="text" id="v_modelo" class="form-control" placeholder="Ex: Onix 1.0" />
                        </div>
                        <div class="form-group col-md-3 col-sm-6 mb-3">
                            <label class="form-label">Ano</label>
                            <input type="text" id="v_ano" class="form-control" placeholder="2022" />
                        </div>
                        <div class="form-group col-md-3 col-sm-6 mb-3">
                            <label class="form-label">Cor</label>
                            <input type="text" id="v_cor" class="form-control" placeholder="Prata" />
                        </div>
                        <div class="form-group col-md-3 col-sm-6 mb-3">
                            <label class="form-label">KM Atual</label>
                            <input type="number" id="v_km" class="form-control" placeholder="0" />
                        </div>
                        <div class="form-group col-md-3 col-sm-6 mb-3">
                            <label class="form-label">Combustível</label>
                            <select id="v_combustivel" class="form-control custom-select">
                                <option value="Flex">Flex</option>
                                <option value="Gasolina">Gasolina</option>
                                <option value="Etanol">Etanol</option>
                                <option value="Diesel">Diesel</option>
                                <option value="GNV">GNV</option>
                                <option value="Elétrico/Híbrido">Elétrico/Híbrido</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">Fechar</button>
                    <button type="button" onclick="salvarVeiculo()" class="btn btn-success font-weight-bold px-6">
                        <i class="fa fa-save me-1"></i> Salvar Veículo
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    <script type="text/javascript">
        // --- CONTROLE DE EXIBIÇÃO SIMPLES X OFICINA ---
        function alternarTipoOS() {
            if ($('#tipo_oficina').is(':checked')) {
                $('.area-oficina').fadeIn();
            } else {
                $('.area-oficina').fadeOut();
                $('#cliente_veiculo_id').val('').change();
                $('#km_veiculo').val('');
            }
        }

        // --- FUNÇÕES DE CLIENTE ---
        function novoCliente(){
            $('#modal-cliente').modal('show');
        }

        $('#pessoaFisica').click(function () {
            $('#lbl_cpf_cnpj_modal').html('CPF');
            $('#lbl_ie_rg_modal').html('RG');
            $('#cpf_cnpj').mask('000.000.000-00', { reverse: true });
            $('#btn-consulta-cadastro').hide();
        });

        $('#pessoaJuridica').click(function () {
            $('#lbl_cpf_cnpj_modal').html('CNPJ');
            $('#lbl_ie_rg_modal').html('IE');
            $('#cpf_cnpj').mask('00.000.000/0000-00', { reverse: true });
            $('#btn-consulta-cadastro').show();
        });

        function consultaCadastro() {
            let cnpj = $('#cpf_cnpj').val().replace(/[^0-9]/g,'');

            if (cnpj.length == 14){
                $('#btn-consulta-cadastro').addClass('spinner spinner-white spinner-right');
                $.get('https://publica.cnpj.ws/cnpj/' + cnpj)
                    .done((data) => {
                        $('#btn-consulta-cadastro').removeClass('spinner spinner-white spinner-right');
                        if (data != null) {
                            let ie = '';
                            if (data.estabelecimento.inscricoes_estaduais.length > 0) {
                                ie = data.estabelecimento.inscricoes_estaduais[0].inscricao_estadual;
                            }
                            $('#ie_rg').val(ie);
                            $('#razao_social2').val(data.razao_social);
                            $('#nome_fantasia2').val(data.estabelecimento.nome_fantasia);
                            $("#rua").val(data.estabelecimento.tipo_logradouro + " " + data.estabelecimento.logradouro);
                            $('#numero2').val(data.estabelecimento.numero);
                            $("#bairro").val(data.estabelecimento.bairro);
                            let cep = data.estabelecimento.cep.replace(/[^\d]+/g, '');
                            $('#cep').val(cep.substring(0, 5) + '-' + cep.substring(5, 9));
                            $('#email').val(data.estabelecimento.email);
                            $('#telefone').val(data.estabelecimento.telefone1);

                            findCidadeCodigo(data.estabelecimento.cidade.ibge_id);
                        }
                    })
                    .fail((err) => {
                        $('#btn-consulta-cadastro').removeClass('spinner spinner-white spinner-right');
                        swal("Erro", err.responseJSON ? err.responseJSON.titulo : "Erro ao consultar CNPJ", "error");
                    });
            } else {
                swal("Alerta", "Informe corretamente o CNPJ (14 dígitos)", "warning");
            }
        }

        function findCidadeCodigo(codigo_ibge){
            $.get(path + "cidades/cidadePorCodigoIbge/" + codigo_ibge)
                .done((res) => {
                    $('#kt_select2_4').val(res.id).change();
                });
        }

        function getDataFromCep(cep) {
            if (cep.length >= 8) {
                cep = cep.replace("-", "");
                $.get('https://ws.apicep.com/cep.json', { code: cep })
                    .done((response) => {
                        $('#bairro').val(response.district);
                        $('#rua').val(response.address);
                        findNomeCidade(response.city, (res) => {
                            let jsCidade = JSON.parse(res);
                            if (jsCidade) {
                                $('#kt_select2_4').val(jsCidade.id).change();
                            }
                        });
                    });
            }
        }

        $('#cep').blur((event) => {
            getDataFromCep(event.target.value);
        });

        function limparCamposCliente(){
            $('#razao_social2').val('');
            $('#nome_fantasia2').val('');
            $('#rua').val('');
            $('#numero2').val('');
            $('#bairro').val('');
            $('#cep').val('');
            $('#cpf_cnpj').val('');
            $('#ie_rg').val('');
            $('#email').val('');
            $('#telefone').val('');
            $('#celular').val('');
        }

        function salvarCliente(){
            let js = {
                razao_social: $('#razao_social2').val(),
                nome_fantasia: $('#nome_fantasia2').val() || '',
                rua: $('#rua').val() || '',
                numero: $('#numero2').val() || '',
                cpf_cnpj: $('#cpf_cnpj').val() || '',
                ie_rg: $('#ie_rg').val() || '',
                bairro: $('#bairro').val() || '',
                cep: $('#cep').val() || '',
                consumidor_final: $('#consumidor_final').val() || '1',
                contribuinte: $('#contribuinte').val() || '1',
                limite_venda: $('#limite_venda').val() || '0',
                cidade_id: $('#kt_select2_4').val() || '1',
                telefone: $('#telefone').val() || '',
                celular: $('#celular').val() || '',
                email: $('#email').val() || '',
            };

            if(js.razao_social == ''){
                swal("Erro", "Informe a razão social", "warning");
            } else {
                let token = $('#_token').val();
                $.post(path + 'clientes/quickSave', {
                    _token: token,
                    data: js
                })
                    .done((res) => {
                        limparCamposCliente();
                        $('#kt_select2_1').append('<option value="'+res.id+'">'+ res.razao_social +' ('+ res.cpf_cnpj +')</option>').change();
                        $('#kt_select2_1').val(res.id).change();
                        swal("Sucesso", "Cliente adicionado com sucesso!", 'success')
                            .then(() => {
                                $('#modal-cliente').modal('hide');
                            });
                    })
                    .fail((err) => {
                        swal("Alerta", err.responseJSON || "Erro ao salvar cliente", "warning");
                    });
            }
        }

        // --- FUNÇÕES DE VEÍCULO ---
        $('#kt_select2_1').change(function() {
            let clienteId = $(this).val();
            if (clienteId) {
                carregarVeiculosCliente(clienteId);
            } else {
                $('#cliente_veiculo_id').empty().append('<option value="">Selecione o veículo...</option>');
            }
        });

        function carregarVeiculosCliente(clienteId, veiculoSelecionadoId = null) {
            $.get(path + 'ordemServico/getVeiculosCliente/' + clienteId)
                .done(function(res) {
                    let select = $('#cliente_veiculo_id');
                    select.empty().append('<option value="">Selecione o veículo...</option>');

                    if (res.length > 0) {
                        res.forEach(function(v) {
                            let selected = (veiculoSelecionadoId && veiculoSelecionadoId == v.id) ? 'selected' : '';
                            select.append(`<option value="${v.id}" ${selected}>${v.placa} - ${v.marca || ''} ${v.modelo || ''} (${v.cor || ''}) | KM: ${v.km_atual}</option>`);
                        });
                    }
                });
        }

        function novoVeiculo() {
            let clienteId = $('#kt_select2_1').val();
            if (!clienteId) {
                swal("Alerta", "Selecione primeiro o cliente antes de cadastrar o veículo!", "warning");
                return;
            }
            $('#modal-veiculo').modal('show');
        }

        function salvarVeiculo() {
            let clienteId = $('#kt_select2_1').val();
            let data = {
                _token: $('#_token').val(),
                cliente_id: clienteId,
                placa: $('#v_placa').val(),
                marca: $('#v_marca').val(),
                modelo: $('#v_modelo').val(),
                ano: $('#v_ano').val(),
                cor: $('#v_cor').val(),
                km_atual: $('#v_km').val(),
                combustivel: $('#v_combustivel').val()
            };

            if (!data.placa) {
                swal("Alerta", "Informe a placa do veículo", "warning");
                return;
            }

            $.post(path + 'ordemServico/storeVeiculoCliente', data)
                .done(function(res) {
                    swal("Sucesso", "Veículo cadastrado com sucesso!", "success");
                    $('#modal-veiculo').modal('hide');
                    carregarVeiculosCliente(clienteId, res.veiculo.id);
                    $('#km_veiculo').val(res.veiculo.km_atual);
                })
                .fail(function(err) {
                    swal("Erro", "Erro ao cadastrar veículo", "error");
                });
        }

        $(document).ready(function() {
            alternarTipoOS();
            let clienteIdInicial = $('#kt_select2_1').val();
            if (clienteIdInicial) {
                carregarVeiculosCliente(clienteIdInicial);
            }
        });
    </script>
@endsection
