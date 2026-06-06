@extends('default.layout')
@section('content')
    <div class="card card-custom gutter-b">
        <div class="card-header border-0 pt-6">
            <h3 class="card-title align-items-start flex-column">
                <span class="card-label font-weight-bolder font-size-h3 text-dark">{{ $formTitle }}</span>
            </h3>
            <div class="card-toolbar">
                <button type="button" class="btn btn-sm btn-light-warning font-weight-bolder" data-toggle="modal" data-target="#modalGuiaPreenchimento">
                    <i class="fa fa-question-circle"></i> Guia de Preenchimento
                </button>
            </div>
        </div>

        <div class="modal fade" id="modalGuiaPreenchimento" tabindex="-1" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title">Como preencher a OS?</h5>
                    </div>
                    <div class="modal-body">
                        <ul class="list-unstyled">
                            <li class="mb-3"><strong>👓 Longe/Perto:</strong> Preencha os campos de Esférico, Cilíndrico e Eixo conforme a receita.</li>
                            <li class="mb-3"><strong>⚠️ Validação:</strong> Se você informar um valor no campo Cilíndrico (CIL), o campo Eixo torna-se obrigatório.</li>
                            <li class="mb-3"><strong>📸 Receita Digital:</strong> Utilize o campo de anexo no final do formulário para subir a foto da receita original.</li>
                            <li class="mb-3"><strong>💬 WhatsApp:</strong> Verifique se o número do WhatsApp está correto para que o cliente receba o aviso de "Pronto".</li>
                        </ul>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button></div>
                </div>
            </div>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ isset($item) ? route('otica.update', $item->id) : route('otica.store') }}" id="form-otica" enctype="multipart/form-data">
                @csrf

                @if(isset($item))
                    @method('PUT')
                    <input type="hidden" name="id" value="{{ $item->id }}">
                @endif

                <div class="row bg-light-secondary p-5 rounded mb-8 border">
                    <div class="col-md-5">
                        <label class="font-weight-bold">Cliente <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <select name="cliente_id" id="cliente_id" class="form-control select2" style="width: 100% !important;" required>
                                @if(isset($item) && $item->cliente)
                                    <option value="{{ $item->cliente_id }}" selected>{{ $item->cliente->razao_social }}</option>
                                @endif
                            </select>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalClienteRapido"><i class="fa fa-plus"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="font-weight-bold">Status da OS</label>
                        <select name="status" class="form-control">
                            <option value="orcamento" {{ (isset($item) && $item->status == 'orcamento') ? 'selected' : '' }}>Orçamento / Aguardando</option>
                            <option value="pendente" {{ (isset($item) && $item->status == 'pendente') ? 'selected' : '' }}>Aguardando Laboratório</option>
                            <option value="laboratorio" {{ (isset($item) && $item->status == 'laboratorio') ? 'selected' : '' }}>Em Produção no Lab.</option>
                            <option value="conferencia" {{ (isset($item) && $item->status == 'conferencia') ? 'selected' : '' }}>Conferência / Qualidade</option>
                            <option value="pronto" {{ (isset($item) && $item->status == 'pronto') ? 'selected' : '' }}>Pronto para Retirada</option>
                            @if(isset($item) && $item->status == 'entregue')
                                <option value="entregue" selected>Entregue / Faturado</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="font-weight-bold">Médico / CRM</label>
                        <input type="text" name="medico" class="form-control" value="{{ $item->medico ?? '' }}">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card card-custom bg-light-info shadow-sm mb-5 border">
                            <div class="card-header border-0 pt-5 min-h-40px"><h3 class="card-title font-weight-bolder text-info font-size-h6">Receita: Longe</h3></div>
                            <div class="card-body pt-0">
                                <div class="row mb-2 small font-weight-bold text-center">
                                    <div class="col-2">LADO</div><div class="col">ESF</div><div class="col">CIL</div><div class="col">EIXO</div><div class="col">DNP</div><div class="col">DP</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-2 font-weight-bold py-2">OD</div>
                                    <div class="col"><input type="text" name="esf_od_longe" class="form-control form-control-sm" value="{{ $item->esf_od_longe ?? '' }}"></div>
                                    <div class="col"><input type="text" name="cil_od_longe" class="form-control form-control-sm" value="{{ $item->cil_od_longe ?? '' }}"></div>
                                    <div class="col"><input type="text" name="eixo_od_longe" class="form-control form-control-sm" value="{{ $item->eixo_od_longe ?? '' }}"></div>
                                    <div class="col"><input type="text" name="dnp_od_longe" class="form-control form-control-sm" value="{{ $item->dnp_od_longe ?? '' }}"></div>
                                    <div class="col"><input type="text" name="dp_od_longe" class="form-control form-control-sm" value="{{ $item->dp_od_longe ?? '' }}"></div>
                                </div>
                                <div class="row">
                                    <div class="col-2 font-weight-bold py-2">OE</div>
                                    <div class="col"><input type="text" name="esf_oe_longe" class="form-control form-control-sm" value="{{ $item->esf_oe_longe ?? '' }}"></div>
                                    <div class="col"><input type="text" name="cil_oe_longe" class="form-control form-control-sm" value="{{ $item->cil_oe_longe ?? '' }}"></div>
                                    <div class="col"><input type="text" name="eixo_oe_longe" class="form-control form-control-sm" value="{{ $item->eixo_oe_longe ?? '' }}"></div>
                                    <div class="col"><input type="text" name="dnp_oe_longe" class="form-control form-control-sm" value="{{ $item->dnp_oe_longe ?? '' }}"></div>
                                    <div class="col"><input type="text" name="dp_oe_longe" class="form-control form-control-sm" value="{{ $item->dp_oe_longe ?? '' }}"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card card-custom bg-light-success shadow-sm mb-5 border">
                            <div class="card-header border-0 pt-5 min-h-40px"><h3 class="card-title font-weight-bolder text-success font-size-h6">Receita: Perto</h3></div>
                            <div class="card-body pt-0">
                                <div class="row mb-2 small font-weight-bold text-center">
                                    <div class="col-2">LADO</div><div class="col">ESF</div><div class="col">CIL</div><div class="col">EIXO</div><div class="col">ADD</div><div class="col">ALT</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-2 font-weight-bold py-2">OD</div>
                                    <div class="col"><input type="text" name="esf_od_perto" class="form-control form-control-sm" value="{{ $item->esf_od_perto ?? '' }}"></div>
                                    <div class="col"><input type="text" name="cil_od_perto" class="form-control form-control-sm" value="{{ $item->cil_od_perto ?? '' }}"></div>
                                    <div class="col"><input type="text" name="eixo_od_perto" class="form-control form-control-sm" value="{{ $item->eixo_od_perto ?? '' }}"></div>
                                    <div class="col"><input type="text" name="adicao_od_perto" class="form-control form-control-sm" value="{{ $item->adicao_od_perto ?? '' }}"></div>
                                    <div class="col"><input type="text" name="altura_od_perto" class="form-control form-control-sm" value="{{ $item->altura_od_perto ?? '' }}"></div>
                                </div>
                                <div class="row">
                                    <div class="col-2 font-weight-bold py-2">OE</div>
                                    <div class="col"><input type="text" name="esf_oe_perto" class="form-control form-control-sm" value="{{ $item->esf_oe_perto ?? '' }}"></div>
                                    <div class="col"><input type="text" name="cil_oe_perto" class="form-control form-control-sm" value="{{ $item->cil_oe_perto ?? '' }}"></div>
                                    <div class="col"><input type="text" name="eixo_oe_perto" class="form-control form-control-sm" value="{{ $item->eixo_oe_perto ?? '' }}"></div>
                                    <div class="col"><input type="text" name="adicao_oe_perto" class="form-control form-control-sm" value="{{ $item->adicao_oe_perto ?? '' }}"></div>
                                    <div class="col"><input type="text" name="altura_oe_perto" class="form-control form-control-sm" value="{{ $item->altura_oe_perto ?? '' }}"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card card-custom border shadow-none mb-8 bg-light">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 border-right">
                                <label class="font-weight-bolder text-dark">Armação</label>
                                <select name="armacao_id" id="armacao_id" class="form-control select2" style="width: 100% !important;">
                                    @if(isset($item) && $item->armacao) <option value="{{ $item->armacao_id }}" selected>{{ $item->armacao }}</option> @endif
                                </select>
                                <div class="row mt-3">
                                    <div class="col-md-4"><label>Qtd</label><input type="number" id="qtd_armacao" name="qtd_armacao" value="{{ $item->qtd_armacao ?? 1 }}" class="form-control"></div>
                                    <div class="col-md-8"><label>Valor R$</label><input type="text" id="valor_armacao" name="valor_armacao" class="form-control money" value="{{ number_format($item->valor_armacao ?? 0, 2, ',', '.') }}"></div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="font-weight-bolder text-dark">Lente / Tratamento</label>
                                <select name="lente_id" id="lente_id" class="form-control select2" style="width: 100% !important;">
                                    @if(isset($item) && $item->lente) <option value="{{ $item->lente_id }}" selected>{{ $item->lente }}</option> @endif
                                </select>
                                <div class="row mt-3">
                                    <div class="col-md-4"><label>Qtd</label><input type="number" id="qtd_lente" name="qtd_lente" value="{{ $item->qtd_lente ?? 1 }}" class="form-control"></div>
                                    <div class="col-md-8"><label>Valor R$</label><input type="text" id="valor_lente" name="valor_lente" class="form-control money" value="{{ number_format($item->valor_lente ?? 0, 2, ',', '.') }}"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-8 align-items-end">
                    <div class="col-md-3 form-group"><label>Tipo de Lente</label><input type="text" name="tipo_lente" class="form-control" value="{{ $item->tipo_lente ?? '' }}"></div>
                    <div class="col-md-3 form-group"><label>Tratamento</label><input type="text" name="tratamento" class="form-control" value="{{ $item->tratamento ?? '' }}"></div>
                    <div class="col-md-3 form-group"><label>Pagamento</label><input type="text" name="forma_pagamento" class="form-control" value="{{ $item->forma_pagamento ?? '' }}"></div>
                    <div class="col-md-3 form-group"><label>Previsão (Dias)</label><input type="number" name="previsao_retorno_dias" class="form-control" value="{{ $item->previsao_retorno_dias ?? 30 }}"></div>

                    <div class="col-md-5 form-group">
                        <label>Observações Gerais</label>
                        <textarea name="observacao" class="form-control" rows="2">{{ $item->observacao ?? '' }}</textarea>
                    </div>

                    <div class="col-md-3 form-group">
                        <label class="font-weight-bold text-dark">Foto/PDF da Receita</label>
                        <input type="file" name="anexo_receita" class="form-control-file" accept="image/*,application/pdf">
                        @if(isset($item) && $item->anexo_receita)
                            <a href="{{ asset('storage/' . $item->anexo_receita) }}" target="_blank" class="btn btn-xs btn-light-info font-weight-bold mt-2">
                                <i class="fa fa-eye"></i> Ver Receita Anexada
                            </a>
                        @endif
                    </div>

                    <div class="col-md-4 form-group"><label class="font-weight-bolder text-danger font-size-h5">TOTAL (R$)</label><input type="text" id="total_os" name="total_os" class="form-control form-control-solid font-weight-bolder text-danger font-size-h3 bg-light-danger text-right" readonly value="0,00"></div>
                </div>

                <div class="text-right border-top pt-5">
                    <a href="{{ route('otica.index') }}" class="btn btn-secondary btn-lg mr-2">Voltar</a>

                    @if(!isset($item) || (isset($item) && $item->status != 'entregue'))
                        <button type="submit" class="btn btn-lg btn-info px-10 font-weight-bolder"><i class="fa fa-save"></i> Salvar OS</button>

                        @if(isset($item))
                            <a href="{{ route('otica.faturar', ['id' => $item->id, 'tipo' => 'pdv']) }}" class="btn btn-lg btn-success px-10 font-weight-bolder ml-2" onclick="return confirm('Deseja enviar para Frente de Caixa (NFC-e)?')">
                                <i class="fa fa-cash-register"></i> Venda PDV
                            </a>
                            <a href="{{ route('otica.faturar', ['id' => $item->id, 'tipo' => 'nfe']) }}" class="btn btn-lg btn-primary px-10 font-weight-bolder ml-2" onclick="return confirm('Deseja enviar para Vendas (NF-e)?')">
                                <i class="fa fa-file-invoice-dollar"></i> Venda NF-e
                            </a>
                        @endif
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalClienteRapido" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white"><h5 class="modal-title text-white font-weight-bold">Novo Cliente</h5></div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 form-group"><label>Nome *</label><input type="text" id="modal_nome" class="form-control"></div>
                        <div class="col-md-6 form-group"><label>CPF / CNPJ</label><input type="text" id="modal_cpf" class="form-control cpf_cnpj"></div>
                        <div class="col-md-6 form-group"><label>Data Nasc.</label><input type="date" id="modal_nascimento" class="form-control"></div>

                        <div class="col-md-6 form-group"><label>Telefone Fixo/Recado</label><input type="text" id="modal_telefone" class="form-control celular"></div>
                        <div class="col-md-6 form-group"><label>WhatsApp</label><input type="text" id="modal_whatsapp" class="form-control celular" placeholder="(00) 00000-0000"></div>

                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold text-danger">CEP *</label>
                            <div class="input-group">
                                <input type="text" id="modal_cep" class="form-control cep" placeholder="00000-000">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-secondary" onclick="buscarCepRapido()"><i class="fa fa-search"></i></button>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-8 form-group">
                            <label class="font-weight-bold">Cidade / UF *</label>
                            <select id="modal_cidade_id" class="form-control select2-cidade" style="width: 100% !important;"></select>
                        </div>

                        <div class="col-md-8 form-group"><label>Rua</label><input type="text" id="modal_rua" class="form-control"></div>
                        <div class="col-md-4 form-group"><label>Nº</label><input type="text" id="modal_numero" class="form-control" value="S/N"></div>
                        <div class="col-md-12 form-group"><label>Bairro</label><input type="text" id="modal_bairro" class="form-control"></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button><button type="button" class="btn btn-primary" onclick="salvarClienteRapido()">Salvar</button></div>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    <script>
        $(document).ready(function () {
            // VALIDAÇÃO INTELIGENTE DA RECEITA ANTES DE ENVIAR
            $('#form-otica').on('submit', function(e) {
                let valido = true;
                let mensagem = "";

                // 1. Validação para o bloco de LONGE
                let cilODLonge = $('#form-otica input[name="cil_od_longe"]').val().trim();
                let eixoODLonge = $('#form-otica input[name="eixo_od_longe"]').val().trim();
                if (cilODLonge !== "" && eixoODLonge === "") {
                    valido = false;
                    mensagem += "• Receita Longe (OD): Você informou o Cilíndrico (CIL), portanto o Eixo é obrigatório!\n";
                }

                let cilOELonge = $('#form-otica input[name="cil_oe_longe"]').val().trim();
                let eixoOELonge = $('#form-otica input[name="eixo_oe_longe"]').val().trim();
                if (cilOELonge !== "" && eixoOELonge === "") {
                    valido = false;
                    mensagem += "• Receita Longe (OE): Você informou o Cilíndrico (CIL), portanto o Eixo é obrigatório!\n";
                }

                // 2. Validação para o bloco de PERTO
                let cilODPerto = $('#form-otica input[name="cil_od_perto"]').val().trim();
                let eixoODPerto = $('#form-otica input[name="eixo_od_perto"]').val().trim();
                if (cilODPerto !== "" && eixoODPerto === "") {
                    valido = false;
                    mensagem += "• Receita Perto (OD): Você informou o Cilíndrico (CIL), portanto o Eixo é obrigatório!\n";
                }

                let cilOEPerto = $('#form-otica input[name="cil_oe_perto"]').val().trim();
                let eixoOEPerto = $('#form-otica input[name="eixo_oe_perto"]').val().trim();
                if (cilOEPerto !== "" && eixoOEPerto === "") {
                    valido = false;
                    mensagem += "• Receita Perto (OE): Você informou o Cilíndrico (CIL), portanto o Eixo é obrigatório!\n";
                }

                if (!valido) {
                    e.preventDefault();
                    alert("Atenção! Erro técnico na receita médica:\n\n" + mensagem);
                }
            });
            const ptBR = { searching: () => "Pesquisando...", noResults: () => "Nenhum resultado encontrado", inputTooShort: () => "Digite para buscar..." };

            $('#cliente_id').select2({
                language: ptBR, width: '100%',
                ajax: { url: "{{ route('otica.buscarClientes') }}", dataType: 'json', delay: 250, processResults: (data) => ({ results: data }) }
            });

            function initProd(id, valorId, qtdId) {
                $(id).select2({
                    language: ptBR, width: '100%',
                    ajax: { url: "{{ route('otica.buscarProdutos') }}", dataType: 'json', delay: 250, processResults: (data) => ({ results: data }) }
                }).on('select2:select', function (e) {
                    $(this).attr('data-preco', e.params.data.preco || 0);
                    calcularValores();
                });
                $(qtdId).on('change keyup', calcularValores);
                $(valorId).on('change keyup', somarTotalOS);
            }

            initProd('#armacao_id', '#valor_armacao', '#qtd_armacao');
            initProd('#lente_id', '#valor_lente', '#qtd_lente');

            function calcularValores() {
                let pArma = parseFloat($('#armacao_id').attr('data-preco') || 0);
                let qArma = parseInt($('#qtd_armacao').val() || 1);
                if(pArma > 0) $('#valor_armacao').val((pArma * qArma).toFixed(2).replace('.', ',')).trigger('input');

                let pLente = parseFloat($('#lente_id').attr('data-preco') || 0);
                let qLente = parseInt($('#qtd_lente').val() || 1);
                if(pLente > 0) $('#valor_lente').val((pLente * qLente).toFixed(2).replace('.', ',')).trigger('input');

                somarTotalOS();
            }

            function somarTotalOS() {
                let vArma = parseFloat($('#valor_armacao').val().replace(/\./g, '').replace(',', '.') || 0);
                let vLente = parseFloat($('#valor_lente').val().replace(/\./g, '').replace(',', '.') || 0);
                $('#total_os').val((vArma + vLente).toFixed(2).replace('.', ','));
            }
            setTimeout(somarTotalOS, 800);
        });

        function buscarCepRapido() {
            let cep = $('#modal_cep').val().replace(/\D/g, '');
            if (cep.length !== 8) {
                alert('Digite um CEP válido com 8 dígitos.');
                return;
            }

            $('#modal_cep').prop('disabled', true);

            $.getJSON("https://viacep.com.br/ws/" + cep + "/json/", function(dados) {
                if (!("erro" in dados)) {
                    $('#modal_rua').val(dados.logradouro);
                    $('#modal_bairro').val(dados.bairro);

                    let urlBusca = "{{ route('otica.buscarCidades') }}?q=" + encodeURIComponent(dados.localidade) + "&uf=" + dados.uf;

                    $.get(urlBusca, function(res) {
                        if(res && res.length > 0) {
                            $('#modal_cidade_id').empty();
                            let opt = new Option(res[0].text, res[0].id, true, true);
                            $('#modal_cidade_id').append(opt).trigger('change');
                        } else {
                            alert("Cidade " + dados.localidade + " (" + dados.uf + ") não encontrada no banco do ERP.");
                        }
                    });
                } else {
                    alert("CEP não encontrado.");
                }
            }).fail(function() {
                alert("Erro ao buscar o CEP online.");
            }).always(function() {
                $('#modal_cep').prop('disabled', false);
            });
        }

        function salvarClienteRapido() {
            let whatsDigitado = $('#modal_whatsapp').val().trim();
            if (whatsDigitado.replace(/\D/g, '') === "") {
                whatsDigitado = null;
            }

            let dados = {
                _token: "{{ csrf_token() }}",
                razao_social: $('#modal_nome').val(),
                cpf_cnpj: $('#modal_cpf').val(),
                data_nascimento: $('#modal_nascimento').val(),
                telefone: $('#modal_telefone').val(),
                whatsapp: whatsDigitado,
                cep: $('#modal_cep').val(),
                cidade_id: $('#modal_cidade_id').val(),
                rua: $('#modal_rua').val(),
                numero: $('#modal_numero').val(),
                bairro: $('#modal_bairro').val()
            };

            if (!dados.razao_social.trim()) {
                alert("O nome do cliente é obrigatório!");
                return;
            }

            if (!dados.cep || dados.cep.replace(/\D/g, '').length !== 8) {
                alert("O CEP é obrigatório e precisa ter 8 dígitos para evitar rejeições na NF-e.");
                return;
            }

            if (!dados.cidade_id) {
                alert("Por favor, selecione a Cidade do cliente.");
                return;
            }

            let btnSalvar = $('#modalClienteRapido .btn-primary');
            let textoOriginal = btnSalvar.text();
            btnSalvar.prop('disabled', true).text('Salvando...');

            $.post("{{ route('otica.clienteRapido') }}", dados, function (res) {
                if (res.success) {
                    let opt = new Option(res.nome, res.id, true, true);
                    $('#cliente_id').append(opt).trigger('change');

                    $('#modalClienteRapido').modal('hide');
                    $('#modalClienteRapido input').val('');
                    $('#modal_cidade_id').val(null).trigger('change');
                } else {
                    alert("Erro ao salvar no banco de dados: " + res.message);
                }
            }).fail(function(xhr) {
                alert("Erro crítico no servidor. Verifique os logs.");
            }).always(function() {
                btnSalvar.prop('disabled', false).text(textoOriginal);
            });
        }
    </script>
@endsection
