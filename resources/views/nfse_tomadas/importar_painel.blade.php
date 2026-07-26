@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <h3 class="card-label font-weight-bolder text-dark">
                <i class="la la-file-import text-success icon-xl"></i> Finalizar Importação de NFS-e Tomada
                <span class="text-muted pt-2 font-size-sm d-block">Confirme as regras de retenção, prazos e parametrização financeira</span>
            </h3>
        </div>
        <div class="card-toolbar">
            <a href="/nfse-tomadas" class="btn btn-light-danger font-weight-bold">
                <i class="la la-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <div class="card-body">
        
        {{-- Formulário de Disparo do Lançamento --}}
        <form action="/nfse-tomadas/salvar-importacao/{{ $nota->id }}" method="POST" id="form-confirmar-importacao">
            @csrf

            {{-- 1. Resumo dos Dados Gerados pela Receita --}}
            <div class="row mb-8">
                <div class="col-12">
                    <h5 class="text-dark font-weight-bold mb-4">1. Dados do Documento Fiscal</h5>
                    <div class="bg-light p-5 rounded row">
                        
                        <div class="col-md-2 mb-3">
                            <span class="text-muted d-block font-size-sm">Número da Nota</span>
                            <strong class="text-dark font-size-lg">{{ $nota->numero_nota }}</strong>
                        </div>
                        
                        <div class="col-md-2 mb-3">
                            <span class="text-muted d-block font-size-sm">Data de Emissão</span>
                            <strong class="text-dark font-size-lg">{{ date('d/m/Y', strtotime($nota->data_emissao)) }}</strong>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <span class="text-muted d-block font-size-sm">Prestador / Fornecedor</span>
                            <strong class="text-dark font-size-lg">{{ $nota->prestador_nome }}</strong>
                            <small class="text-muted d-block">{{ $nota->prestador_cnpj_cpf ?? $nota->prestador_cnpj }}</small>
                        </div>
                        
                        {{-- CAIXA DA DESCRIÇÃO (Agora separada na sua própria coluna) --}}
                        <div class="col-md-4 mb-3">
                            <span class="text-muted d-block font-size-sm">Descrição do Serviço Reconhecido</span>
                            <span class="text-dark font-weight-bold font-size-sm d-block bg-white p-2 rounded border" style="max-height: 75px; overflow-y: auto;">
                                @if(isset($descricao_servico) && !empty($descricao_servico))
                                    {!! nl2br(e($descricao_servico)) !!}
                                @else
                                    Prestação de serviços gerais discriminada no corpo do documento nacional.
                                @endif
                            </span>
                        </div>
                        
                    </div>
                </div>
            </div>

            {{-- 2. Bloco de Retenções Fiscais Identificadas --}}
            <div class="row mb-8">
                <div class="col-12">
                    <h5 class="text-dark font-weight-bold mb-4">2. Valores e Retenções Provisionadas</h5>
                    <div class="table-responsive bg-light-secondary p-4 rounded">
                        <table class="table table-borderless table-vertical-center mb-0">
                            <thead>
                                <tr class="text-muted font-size-xs text-uppercase">
                                    <th>Valor do Serviço</th>
                                    <th>PIS Retido (Est.)</th>
                                    <th>COFINS Retido (Est.)</th>
                                    <th>CSLL Retida (Est.)</th>
                                    <th>IRRF Retido (Est.)</th>
                                    <th class="text-right text-success font-weight-bold">Valor Líquido a Pagar</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $vServico = $nota->valor_servico;
                                    $vLiquido = $nota->valor_liquido > 0 ? $nota->valor_liquido : $vServico;
                                    
                                    // Provisionamento de simulação gráfica baseado nas regras do seu Controller
                                    $vPis = round($vServico - $vLiquido, 2) > 0 ? round($vServico * 0.0065, 2) : 0;
                                    $vCofins = round($vServico - $vLiquido, 2) > 0 ? round($vServico * 0.03, 2) : 0;
                                    $vCsll = round($vServico - $vLiquido, 2) > 0 ? round($vServico * 0.01, 2) : 0;
                                    $vIr = round($vServico - $vLiquido, 2) > 0 ? round($vServico * 0.015, 2) : 0;
                                @endphp
                                <tr class="font-size-lg font-weight-bolder text-dark">
                                    <td>R$ {{ number_format($vServico, 2, ',', '.') }}</td>
                                    <td class="text-danger font-weight-normal">R$ {{ number_format($vPis, 2, ',', '.') }}</td>
                                    <td class="text-danger font-weight-normal">R$ {{ number_format($vCofins, 2, ',', '.') }}</td>
                                    <td class="text-danger font-weight-normal">R$ {{ number_format($vCsll, 2, ',', '.') }}</td>
                                    <td class="text-danger font-weight-normal">R$ {{ number_format($vIr, 2, ',', '.') }}</td>
                                    <td class="text-right text-success font-size-h4">R$ {{ number_format($vLiquido, 2, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- 3. Parâmetros de Lançamento e Condições de Pagamento --}}
            <div class="row mb-8">
                <div class="col-12">
                    <h5 class="text-dark font-weight-bold mb-4">3. Classificação e Condição de Pagamento</h5>
                    <div class="row">
                        
                        {{-- Categoria de Conta (Plano de Contas) --}}
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold text-dark">Categoria de Conta Finan. <span class="text-danger">*</span></label>
                            <select name="categoria_conta_id" class="form-control custom-select" required>
                                <option value="">Selecione uma categoria...</option>
                                @foreach($categoriasDeConta as $c)
                                    <option value="{{ $c->id }}" {{ old('categoria_conta_id') == $c->id ? 'selected' : '' }}>
                                        {{ $c->nome }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Prazo entre parcelas --}}
                        <div class="col-md-3 form-group">
                            <label class="font-weight-bold text-dark">Intervalo de Dias (Prazo) <span class="text-danger">*</span></label>
                            <select name="prazo_pagamento" class="form-control custom-select" required>
                                <option value="30" {{ old('prazo_pagamento') == '30' ? 'selected' : '' }}>A cada 30 dias</option>
                                <option value="15" {{ old('prazo_pagamento') == '15' ? 'selected' : '' }}>A cada 15 dias</option>
                                <option value="0" {{ old('prazo_pagamento') == '0' ? 'selected' : '' }}>À Vista / Mesma Data</option>
                            </select>
                        </div>

                        {{-- Quantidade de Parcelas --}}
                        <div class="col-md-2 form-group">
                            <label class="font-weight-bold text-dark">Qtd. Parcelas <span class="text-danger">*</span></label>
                            <input type="number" name="quantidade_parcelas" class="form-control" value="{{ old('quantidade_parcelas', 1) }}" min="1" max="72" required>
                        </div>

                        {{-- Vínculo de Frota / Veículo (Opcional) --}}
                        <div class="col-md-3 form-group">
                            <label class="font-weight-bold text-dark">Vincular a Veículo / Frota</label>
                            <select name="veiculo_id" class="form-control custom-select">
                                <option value="">Nenhum (Geral)</option>
                                @foreach($veiculos as $v)
                                    <option value="{{ $v->id }}" {{ old('veiculo_id') == $v->id ? 'selected' : '' }}>
                                        {{ $v->placa }} - {{ $v->marca }}/{{ $v->modelo }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                    </div>
                </div>
            </div>

            <div class="card card-custom gutter-b border shadow-none">
                <div class="card-header border-0">
                    <div class="card-title">
                        <h3 class="card-label font-weight-bold">4. Parcelamento e Rateio Avançado</h3>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="alert alert-light-info mb-5">
                        Mantenha <strong>Automático</strong> para usar quantidade, prazo e veículo geral informados acima.
                        Use as opções avançadas somente quando cada parcela precisar de vencimento, valor ou veículo próprio.
                    </div>

                    <div class="row align-items-end">
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold">Forma de distribuição</label>
                            <select id="tipo_condicao_nfse" class="custom-select form-control">
                                <option value="automatico">Automático pelos parâmetros acima</option>
                                <option value="manual">Parcelamento manual</option>
                                <option value="rateio">Ratear igualmente por veículos</option>
                            </select>
                        </div>

                        <div class="col-md-4 form-group" id="grupo_quantidade_manual" style="display: none;">
                            <label class="font-weight-bold">Quantidade de parcelas manuais</label>
                            <div class="input-group">
                                <input type="number" id="qtd_parcelas_manual_nfse" class="form-control" value="1" min="1" max="120">
                                <div class="input-group-append">
                                    <button type="button" id="btn_gerar_parcelas_nfse" class="btn btn-primary">Gerar</button>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-8 form-group" id="grupo_rateio_veiculos" style="display: none;">
                            <label class="font-weight-bold">Veículos participantes do rateio</label>
                            <select id="veiculos_rateio_nfse" class="form-control select2-custom" multiple="multiple" style="width: 100%;">
                                @foreach($veiculos as $v)
                                    <option value="{{ $v->id }}">{{ $v->placa }} - {{ $v->marca }}/{{ $v->modelo }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div id="painel_parcelas_nfse" style="display: none;">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="tabela_parcelas_nfse">
                                <thead>
                                    <tr>
                                        <th style="width: 100px;">Parcela</th>
                                        <th style="width: 160px;">Vencimento</th>
                                        <th style="width: 170px;">Valor (R$)</th>
                                        <th>Veículo</th>
                                        <th style="width: 70px;" class="text-center">Ação</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <button type="button" id="btn_adicionar_parcela_nfse" class="btn btn-sm btn-light-primary">
                            <i class="la la-plus"></i> Adicionar parcela
                        </button>
                        <div class="text-right font-weight-bold mt-3">
                            Total distribuído: <span id="total_parcelas_nfse" class="text-success">R$ 0,00</span>
                        </div>
                    </div>

                    <input type="hidden" name="fatura_json" id="fatura_json_input">
                </div>
            </div>

            <hr class="my-8">

            {{-- Botões de Disparo --}}
            <div class="row">
                <div class="col-12 text-right">
                    <a href="/nfse-tomadas" class="btn btn-light-dark font-weight-bold mr-2">Cancelar</a>
                    <button type="submit" id="btn-salvar-importacao" class="btn btn-success font-weight-bolder px-8 shadow-sm">
                        <i class="la la-check-circle"></i> CONFIRMAR E GERAR FINANCEIRO
                    </button>
                </div>
            </div>

        </form>

    </div>
</div>

{{-- Parcelamento avançado e proteção contra duplo envio --}}
<script>
    (function () {
        const valorLiquido = Number(@json(round((float) $vLiquido, 2)));
        const vencimentoBase = @json(\Carbon\Carbon::parse($nota->data_emissao)->addDays(30)->format('d/m/Y'));
        const veiculos = @json($veiculos->map(function ($v) {
            return ['id' => $v->id, 'descricao' => trim($v->placa . ' - ' . $v->marca . '/' . $v->modelo)];
        })->values());

        const moeda = function (valor) {
            return Number(valor || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        };

        const parseMoeda = function (valor) {
            if (typeof valor === 'number') return valor;
            let texto = String(valor || '').replace(/[^0-9,.-]/g, '');
            if (texto.indexOf(',') >= 0) texto = texto.replace(/\./g, '').replace(',', '.');
            return Number(texto) || 0;
        };

        const opcoesVeiculo = function (selecionado) {
            let html = '<option value="">Usar veículo geral da nota</option>';
            veiculos.forEach(function (v) {
                html += '<option value="' + v.id + '" ' + (String(selecionado || '') === String(v.id) ? 'selected' : '') + '>' + v.descricao + '</option>';
            });
            return html;
        };

        const adicionarLinha = function (numero, vencimento, valor, veiculoId) {
            const linha = `
                <tr>
                    <td><input type="text" class="form-control text-center parcela-numero" value="${numero}"></td>
                    <td><input type="text" class="form-control date-input parcela-vencimento" value="${vencimento}"></td>
                    <td><input type="text" class="form-control money parcela-valor text-right" value="${moeda(valor)}"></td>
                    <td><select class="custom-select form-control parcela-veiculo">${opcoesVeiculo(veiculoId)}</select></td>
                    <td class="text-center"><button type="button" class="btn btn-sm btn-icon btn-danger remover-parcela-nfse"><i class="la la-trash"></i></button></td>
                </tr>`;
            $('#tabela_parcelas_nfse tbody').append(linha);
            $('.date-input').mask('00/00/0000');
            $('.money').mask('#.##0,00', {reverse: true});
        };

        const atualizarJson = function () {
            const parcelas = [];
            let total = 0;
            $('#tabela_parcelas_nfse tbody tr').each(function () {
                const valor = parseMoeda($(this).find('.parcela-valor').val());
                total += valor;
                parcelas.push({
                    numero: $(this).find('.parcela-numero').val(),
                    vencimento: $(this).find('.parcela-vencimento').val(),
                    valor_parcela: valor.toFixed(2),
                    veiculo_id: $(this).find('.parcela-veiculo').val() || null
                });
            });
            $('#total_parcelas_nfse').text('R$ ' + moeda(total));
            $('#fatura_json_input').val(parcelas.length ? JSON.stringify(parcelas) : '');
            return total;
        };

        const gerarParcelas = function (quantidade, veiculosSelecionados) {
            quantidade = Math.max(1, Number(quantidade || 1));
            $('#tabela_parcelas_nfse tbody').empty();
            const base = Math.floor((valorLiquido / quantidade) * 100) / 100;
            let acumulado = 0;
            for (let i = 1; i <= quantidade; i++) {
                const valor = i === quantidade ? Number((valorLiquido - acumulado).toFixed(2)) : base;
                acumulado += valor;
                adicionarLinha(String(i).padStart(3, '0'), vencimentoBase, valor, veiculosSelecionados ? veiculosSelecionados[i - 1] : null);
            }
            atualizarJson();
        };

        $('#tipo_condicao_nfse').on('change', function () {
            const tipo = $(this).val();
            $('#grupo_quantidade_manual').toggle(tipo === 'manual');
            $('#grupo_rateio_veiculos').toggle(tipo === 'rateio');
            $('#painel_parcelas_nfse').toggle(tipo !== 'automatico');
            if (tipo === 'automatico') {
                $('#tabela_parcelas_nfse tbody').empty();
                $('#fatura_json_input').val('');
                $('#total_parcelas_nfse').text('R$ 0,00');
            } else if (tipo === 'manual') {
                gerarParcelas($('#qtd_parcelas_manual_nfse').val());
            }
        });

        $('#btn_gerar_parcelas_nfse').on('click', function () {
            gerarParcelas($('#qtd_parcelas_manual_nfse').val());
        });

        $('#veiculos_rateio_nfse').on('change', function () {
            const ids = $(this).val() || [];
            if (ids.length) gerarParcelas(ids.length, ids);
            else {
                $('#tabela_parcelas_nfse tbody').empty();
                atualizarJson();
            }
        });

        $('#btn_adicionar_parcela_nfse').on('click', function () {
            const proximo = $('#tabela_parcelas_nfse tbody tr').length + 1;
            adicionarLinha(String(proximo).padStart(3, '0'), vencimentoBase, 0, null);
            atualizarJson();
        });

        $(document).on('click', '.remover-parcela-nfse', function () {
            $(this).closest('tr').remove();
            atualizarJson();
        });
        $(document).on('keyup change blur', '.parcela-numero, .parcela-vencimento, .parcela-valor, .parcela-veiculo', atualizarJson);

        document.getElementById('form-confirmar-importacao').addEventListener('submit', function (event) {
            const tipo = $('#tipo_condicao_nfse').val();
            if (tipo !== 'automatico') {
                const total = atualizarJson();
                if (!$('#tabela_parcelas_nfse tbody tr').length) {
                    event.preventDefault();
                    alert('Gere ao menos uma parcela para concluir a importação.');
                    return;
                }
                if (Math.abs(total - valorLiquido) > 0.02) {
                    event.preventDefault();
                    alert('A soma das parcelas deve ser igual ao valor líquido de R$ ' + moeda(valorLiquido) + '.');
                    return;
                }
            }

            const btn = document.getElementById('btn-salvar-importacao');
            btn.disabled = true;
            btn.innerHTML = '<i class="la la-spinner la-spin"></i> Processando Lançamento...';
        });
    })();
</script>

@endsection