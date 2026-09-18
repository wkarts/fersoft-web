@extends('default.layout')
@section('content')
    @section('css')
        <style type="text/css">
            .card-modern {
                border: none;
                border-radius: 12px;
                box-shadow: 0 4px 18px rgba(0,0,0,0.05);
                background: #ffffff;
                margin-bottom: 1.5rem;
            }
            .card-header-custom {
                background-color: #f8f9fa;
                border-bottom: 1px solid #ebedf2;
                padding: 1.25rem 1.5rem;
                border-top-left-radius: 12px !important;
                border-top-right-radius: 12px !important;
            }
            .section-title {
                font-size: 1.1rem;
                font-weight: 700;
                color: #2b2b36;
                margin-bottom: 0;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .sub-section-title {
                font-size: 0.95rem;
                font-weight: 700;
                color: #3699ff;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                margin-top: 1rem;
                margin-bottom: 1rem;
                border-bottom: 1px dashed #ebedf2;
                padding-bottom: 5px;
            }
            .form-label {
                font-weight: 600;
                color: #3f4254;
                font-size: 0.88rem;
                margin-bottom: 0.4rem;
            }
        </style>
    @endsection

    <div class="content d-flex flex-column flex-column-fluid" id="kt_content">

        <!-- Header com Infos da OS -->
        <div class="card card-modern mb-5">
            <div class="card-body d-flex align-items-center justify-content-between flex-wrap py-4">
                <div class="d-flex align-items-center me-3">
                    <div class="symbol symbol-45px me-4">
                    <span class="symbol-label bg-light-success">
                        <i class="flaticon2-file text-success fs-2x"></i>
                    </span>
                    </div>
                    <div class="d-flex flex-column">
                        <h3 class="text-dark fw-bold my-0 fs-3">
                            Gerar NFS-e da Ordem de Serviço #{{ $ordem->numero_sequencial > 0 ? $ordem->numero_sequencial : $ordem->id }}
                        </h3>
                        <span class="text-muted fs-7 fw-bold">Cliente: <strong>{{ $ordem->cliente->razao_social }}</strong> | Valor Total dos Serviços: <strong class="text-success">R$ {{ moeda($total) }}</strong></span>
                    </div>
                </div>

                <a href="/ordemServico/servicosordem/{{ $ordem->id }}" class="btn btn-secondary font-weight-bold">
                    <i class="fa fa-arrow-left me-1"></i> Voltar para OS
                </a>
            </div>
        </div>

        <!-- Formulário de Emissão de NFS-e -->
        <form class="form" id="form-servico" method="post" action="/nfse/store">
            @csrf
            <input type="hidden" id="_token" value="{{ csrf_token() }}">
            <input type="hidden" name="os_id" value="{{ $ordem->id }}">

            <!-- 1. DADOS DO TOMADOR -->
            <div class="card card-modern">
                <div class="card-header-custom">
                    <h4 class="section-title"><i class="fa fa-user text-primary"></i> 1. Dados do Tomador (Cliente da OS)</h4>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-lg-8 col-md-10 col-12">
                            <label class="form-label">Cliente selecionado <span class="text-danger">*</span></label>
                            <select required class="form-control select2-custom" id="cliente" name="cliente">
                                @foreach($clientes as $c)
                                    <option value="{{$c->id}}" @if($ordem->cliente_id == $c->id) selected @endif>
                                        {{$c->id}} - {{$c->razao_social}} ({{$c->cpf_cnpj}})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="form-group col-lg-3 col-md-4 col-sm-6 mb-3">
                            <label class="form-label">CPF / CNPJ <span class="text-danger">*</span></label>
                            <input type="tel" value="{{ $ordem->cliente->cpf_cnpj }}" name="documento" class="form-control cpf_cnpj" id="documento" required/>
                        </div>

                        <div class="form-group col-lg-5 col-md-8 col-sm-6 mb-3">
                            <label class="form-label">Razão Social / Nome <span class="text-danger">*</span></label>
                            <input value="{{ $ordem->cliente->razao_social }}" required type="text" name="razao_social" class="form-control" id="razao_social"/>
                        </div>

                        <div class="form-group col-lg-2 col-md-6 col-sm-6 mb-3">
                            <label class="form-label">Inscrição Municipal (IM)</label>
                            <input value="{{ $ordem->cliente->im }}" type="tel" name="im" class="form-control" id="im"/>
                        </div>

                        <div class="form-group col-lg-2 col-md-6 col-sm-6 mb-3">
                            <label class="form-label">Inscrição Estadual (IE)</label>
                            <input value="{{ $ordem->cliente->ie_rg }}" type="tel" name="ie" class="form-control" id="ie"/>
                        </div>

                        <div class="form-group col-lg-2 col-md-4 col-sm-6 mb-3">
                            <label class="form-label">CEP <span class="text-danger">*</span></label>
                            <input required type="tel" name="cep" class="form-control cep" id="cep" value="{{ $ordem->cliente->cep }}"/>
                        </div>

                        <div class="form-group col-lg-5 col-md-8 col-sm-12 mb-3">
                            <label class="form-label">Rua <span class="text-danger">*</span></label>
                            <input required type="text" name="rua" class="form-control" id="rua" value="{{ $ordem->cliente->rua }}"/>
                        </div>

                        <div class="form-group col-lg-2 col-md-4 col-sm-6 mb-3">
                            <label class="form-label">Número <span class="text-danger">*</span></label>
                            <input required type="text" name="numero" class="form-control" id="numero" value="{{ $ordem->cliente->numero }}"/>
                        </div>

                        <div class="form-group col-lg-3 col-md-4 col-sm-6 mb-3">
                            <label class="form-label">Bairro <span class="text-danger">*</span></label>
                            <input required type="text" name="bairro" class="form-control" id="bairro" value="{{ $ordem->cliente->bairro }}"/>
                        </div>

                        <div class="form-group col-lg-4 col-md-6 col-sm-12 mb-3">
                            <label class="form-label">Cidade <span class="text-danger">*</span></label>
                            <select name="cidade_id" required style="width: 100%" class="form-control select2" id="cidade_id">
                                @foreach(App\Models\Cidade::all() as $c)
                                    <option value="{{$c->id}}" @if($ordem->cliente->cidade_id == $c->id) selected @endif>
                                        {{$c->nome}} ({{$c->uf}})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-lg-4 col-md-6 col-sm-6 mb-3">
                            <label class="form-label">E-mail</label>
                            <input type="email" name="email" class="form-control" id="email" value="{{ $ordem->cliente->email }}"/>
                        </div>

                        <div class="form-group col-lg-4 col-md-6 col-sm-6 mb-3">
                            <label class="form-label">Telefone / Celular</label>
                            <input type="tel" name="telefone" class="form-control" id="telefone" value="{{ $ordem->cliente->celular ?? $ordem->cliente->telefone }}"/>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. SERVIÇOS DA OS -->
            <div class="card card-modern">
                <div class="card-header-custom">
                    <h4 class="section-title"><i class="fa fa-concierge-bell text-primary"></i> 2. Serviço e Tributação Imposta</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="form-group col-lg-6 col-12 mb-3">
                            <label class="form-label">Serviço Principal da OS <span class="text-danger">*</span></label>
                            <select required class="form-control custom-select border" id="servico_id" name="servico_id" style="width: 100% !important; display: block !important;">
                                <option value="">Selecione o serviço...</option>
                                @foreach($servicos as $s)
                                    <option value="{{ $s->id }}"
                                            @if(isset($servico) && ($servico->servico_id == $s->id || $servico->id == $s->id)) selected @endif>
                                        {{ $s->nome }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-lg-3 col-md-6 col-12 mb-3">
                            <label class="form-label">Natureza de Operação <span class="text-danger">*</span></label>
                            <select required class="form-control custom-select" name="natureza_operacao" id="natureza_operacao">
                                <option value="1">1 - Tributação no município</option>
                                <option value="2">2 - Tributação fora do município</option>
                                <option value="3">3 - Isenção</option>
                                <option value="4">4 - Imunidade</option>
                            </select>
                        </div>

                        <div class="form-group col-lg-3 col-md-6 col-12 mb-3">
                            <label class="form-label">Valor Total do Serviço (R$) <span class="text-danger">*</span></label>
                            <input required type="text" name="valor_servico" class="form-control money" id="valor_servico" value="{{ moeda($total) }}"/>
                        </div>

                        <div class="form-group col-12 mb-3">
                            <label class="form-label">Discriminação dos Serviços na Nota <span class="text-danger">*</span></label>
                            <textarea required rows="4" name="discriminacao" class="form-control" id="discriminacao">{{ $discriminacao }}</textarea>
                        </div>

                        <div class="form-group col-lg-3 col-md-4 col-sm-6 mb-3">
                            <label class="form-label">Cód. Serviço (LC 116) <span class="text-danger">*</span></label>
                            <input required type="tel" name="codigo_servico" class="form-control" id="codigo_servico"
                                   value="{{ optional(optional($servico)->servico)->codigo_servico ?? '1405' }}"/>
                        </div>

                        <div class="form-group col-lg-3 col-md-4 col-sm-6 mb-3">
                            <label class="form-label">Cód. Tributação do Município</label>
                            <input type="tel" name="codigo_tributacao_municipio" class="form-control" id="codigo_tributacao_municipio"
                                   value="{{ optional(optional($servico)->servico)->codigo_tributacao_municipio ?? optional($config)->codigo_tributacao_municipio ?? '' }}"/>
                        </div>

                        <div class="form-group col-lg-3 col-md-4 col-sm-6 mb-3">
                            <label class="form-label">Exigibilidade ISS <span class="text-danger">*</span></label>
                            <select class="form-control custom-select" name="exigibilidade_iss" id="exigibilidade_iss">
                                @foreach(\App\Models\Nfse::exigibilidades() as $key => $e)
                                    <option value="{{$key}}">{{$e}}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-lg-3 col-md-4 col-sm-6 mb-3">
                            <label class="form-label">ISS Retido <span class="text-danger">*</span></label>
                            <select class="form-control custom-select" name="iss_retido" id="iss_retido">
                                <option value="2">Não</option>
                                <option value="1">Sim</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. FINANCEIRO / PARCELAS -->
            <div class="card card-modern">
                <div class="card-header-custom">
                    <h4 class="section-title"><i class="fa fa-dollar-sign text-success"></i> 3. Condições de Pagamento e Contas a Receber</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="form-group col-lg-3 col-md-4 col-12 mb-3">
                            <label class="form-label">Gerar Títulos Financeiros?</label>
                            <select class="form-control custom-select border" name="gerar_contas_receber" id="gerar_contas_receber">
                                <option value="1">Sim - Gerar no Contas a Receber</option>
                                <option value="0">Não - Apenas Emitir Nota Fiscal</option>
                            </select>
                        </div>

                        <div class="form-group col-lg-3 col-md-4 col-12 mb-3 area-financeiro">
                            <label class="form-label">Categoria Financeira <span class="text-danger">*</span></label>
                            <select class="form-control custom-select border" name="categoria_conta_id" id="categoria_conta_id">
                                <option value="">Selecione a categoria...</option>
                                @foreach($categorias as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->nome }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-lg-2 col-md-4 col-12 mb-3 area-financeiro">
                            <label class="form-label">Forma de Pagamento</label>
                            <select class="form-control custom-select border" name="tipo_pagamento" id="tipo_pagamento">
                                @foreach($tiposPagamento as $tp)
                                    <option value="{{$tp}}">{{$tp}}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-lg-2 col-md-4 col-12 mb-3 area-financeiro">
                            <label class="form-label">Qtd. de Parcelas</label>
                            <select class="form-control custom-select border" id="qtd_parcelas" onchange="gerarParcelas()">
                                <option value="1">1x (À vista / Parcela Única)</option>
                                <option value="2">2x</option>
                                <option value="3">3x</option>
                                <option value="4">4x</option>
                                <option value="5">5x</option>
                                <option value="6">6x</option>
                            </select>
                        </div>

                        <div class="form-group col-lg-2 col-md-4 col-12 mb-3 area-financeiro">
                            <label class="form-label">Primeiro Vencimento</label>
                            <input type="date" id="primeira_data" class="form-control border" value="{{ date('Y-m-d') }}" onchange="gerarParcelas()"/>
                        </div>
                    </div>

                    <div class="area-financeiro">
                        <div class="sub-section-title"><i class="fa fa-calendar-alt me-1"></i> Parcelas Geradas</div>
                        <table class="table table-bordered table-striped">
                            <thead class="bg-light">
                            <tr>
                                <th>Parcela</th>
                                <th>Vencimento</th>
                                <th>Valor (R$)</th>
                            </tr>
                            </thead>
                            <tbody id="container_parcelas"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Botão Emitir -->
            <div class="card card-modern">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <a href="/ordemServico/servicosordem/{{ $ordem->id }}" class="btn btn-secondary font-weight-bold">
                        <i class="fa fa-times me-1"></i> Cancelar
                    </a>
                    <button type="submit" id="salvar" class="btn btn-success font-weight-bold px-8">
                        <i class="fa fa-check me-1"></i> Gerar e Emitir NFS-e da OS
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('javascript')
    <script type="text/javascript">
        $('#gerar_contas_receber').change(function() {
            if ($(this).val() == '1') {
                $('.area-financeiro').show();
            } else {
                $('.area-financeiro').hide();
            }
        });

        function gerarParcelas() {
            let qtd = parseInt($('#qtd_parcelas').val()) || 1;
            let valorTotalStr = $('#valor_servico').val() || '0';
            let valorTotal = parseFloat(valorTotalStr.replace(/\./g, '').replace(',', '.')) || 0;
            let dataInicial = $('#primeira_data').val();

            let container = $('#container_parcelas');
            container.empty();

            if (valorTotal <= 0 || !dataInicial) return;

            let valorParcela = (valorTotal / qtd).toFixed(2);
            let data = new Date(dataInicial + 'T00:00:00');

            for (let i = 0; i < qtd; i++) {
                let dataFormatada = data.toISOString().split('T')[0];
                let row = `<tr>
                <td><strong>Parcela ${i + 1}/${qtd}</strong></td>
                <td>
                    <input type="date" name="parcelas[${i}][vencimento]" class="form-control" value="${dataFormatada}" required />
                </td>
                <td>
                    <input type="text" name="parcelas[${i}][valor]" class="form-control money" value="${valorParcela.replace('.', ',')}" required />
                </td>
            </tr>`;
                container.append(row);
                data.setMonth(data.getMonth() + 1);
            }
        }

        $(document).ready(function() {
            gerarParcelas();
            $('#valor_servico').on('change blur keyup', function() {
                gerarParcelas();
            });

            $('#form-servico').on('submit', function(e) {
                let btnSalvar = $('#salvar');
                if (btnSalvar.data('enviando')) {
                    e.preventDefault();
                    return false;
                }
                btnSalvar.data('enviando', true);
                btnSalvar.prop('disabled', true);
                btnSalvar.addClass('spinner spinner-white spinner-right');
            });
        });
    </script>
@endsection
