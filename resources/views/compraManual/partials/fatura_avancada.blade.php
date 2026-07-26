@php
    $mostrarCategoriaConta = $mostrarCategoriaConta ?? false;
    $mostrarAdiantamento = $mostrarAdiantamento ?? false;
    $faturasIniciais = $faturasIniciais ?? [];
@endphp

<div class="card card-custom border shadow-none mt-6" id="card-fatura-avancada-compra">
    <div class="card-header border-0">
        <div class="card-title">
            <h3 class="card-label font-weight-bold">Configuração financeira avançada</h3>
        </div>
    </div>
    <div class="card-body pt-0">
        <div class="row">
            @if($mostrarCategoriaConta)
                <div class="form-group col-lg-4">
                    <label class="font-weight-bold">Categoria da conta <span class="text-danger">*</span></label>
                    <select class="custom-select form-control" id="categoria_conta_id" name="categoria_conta_id">
                        <option value="">Selecione...</option>
                        @foreach(($categoriasDeConta ?? []) as $categoriaConta)
                            <option value="{{ $categoriaConta->id }}">{{ $categoriaConta->nome }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="form-group col-lg-4">
                <label class="checkbox checkbox-lg mt-8">
                    <input type="checkbox" id="usar_fatura_avancada_compra" value="1">
                    <span></span>&nbsp;&nbsp;Definir pagamento, conta e veículo por parcela
                </label>
            </div>

            @if($mostrarAdiantamento)
                <div class="form-group col-lg-4" id="bloco-adiantamento-compra" style="display:none;">
                    <label class="checkbox checkbox-lg mt-8">
                        <input type="checkbox" name="usar_adiantamento" id="usar_adiantamento" value="1">
                        <span></span>&nbsp;&nbsp;<strong id="saldo-adiantamento-compra" class="text-info">Usar adiantamento do fornecedor</strong>
                    </label>
                </div>
            @endif
        </div>

        <div id="painel-fatura-avancada-compra" style="display:none;">
            <div class="row align-items-end mb-4">
                <div class="form-group col-lg-6">
                    <label class="font-weight-bold">Ratear igualmente entre veículos</label>
                    <select id="rateio_veiculos_compra" class="form-control select2-custom" multiple="multiple" style="width:100%;">
                        @foreach(($veiculos ?? []) as $veiculoRateio)
                            <option value="{{ $veiculoRateio->id }}">{{ $veiculoRateio->placa }} - {{ $veiculoRateio->marca }}/{{ $veiculoRateio->modelo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-lg-3">
                    <button type="button" class="btn btn-light-primary" id="btn-ratear-fatura-compra">
                        <i class="la la-random"></i> Gerar rateio
                    </button>
                </div>
                <div class="form-group col-lg-3 text-right">
                    <button type="button" class="btn btn-light-success" id="btn-adicionar-fatura-compra">
                        <i class="la la-plus"></i> Adicionar parcela
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="tabela-fatura-avancada-compra">
                    <thead>
                        <tr>
                            <th style="width:90px;">Parcela</th>
                            <th style="width:145px;">Vencimento</th>
                            <th style="width:145px;">Valor</th>
                            <th style="width:180px;">Pagamento</th>
                            <th>Conta bancária</th>
                            <th>Veículo</th>
                            <th style="width:65px;">Ação</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="text-right font-weight-bold">Total das parcelas: <span id="total-fatura-avancada-compra" class="text-success">R$ 0,00</span></div>
        </div>
    </div>
</div>

<script>
(function () {
    const contasCompra = @json(($contasEmpresa ?? collect())->map(function ($conta) {
        return ['id' => $conta->id, 'nome' => $conta->nome];
    })->values());
    const veiculosCompra = @json(($veiculos ?? collect())->map(function ($veiculo) {
        return ['id' => $veiculo->id, 'nome' => trim($veiculo->placa . ' - ' . $veiculo->marca . '/' . $veiculo->modelo)];
    })->values());
    const faturasIniciaisCompra = @json(collect($faturasIniciais)->map(function ($fatura, $indice) {
        return [
            'db_id' => data_get($fatura, 'id'),
            'numero' => str_pad((string) ($indice + 1), 3, '0', STR_PAD_LEFT),
            'data' => data_get($fatura, 'data_vencimento') ? \Carbon\Carbon::parse(data_get($fatura, 'data_vencimento'))->format('d/m/Y') : date('d/m/Y'),
            'valor' => (float) data_get($fatura, 'valor_integral', 0),
            'forma_pagamento' => data_get($fatura, 'tipo_pagamento', 'boleto'),
            'conta_empresa_id' => data_get($fatura, 'conta_empresa_id'),
            'veiculo_id' => data_get($fatura, 'veiculo_id'),
            'paga' => (bool) data_get($fatura, 'status', false),
        ];
    })->values());

    function moedaCompra(valor) {
        return Number(valor || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
    function parseMoedaCompra(valor) {
        let texto = String(valor || '').replace(/[^0-9,.-]/g, '');
        if (texto.indexOf(',') >= 0) texto = texto.replace(/\./g, '').replace(',', '.');
        return Number(texto) || 0;
    }
    function optionsCompra(lista, selecionado, vazio) {
        let html = '<option value="">' + vazio + '</option>';
        lista.forEach(function (item) {
            html += '<option value="' + item.id + '" ' + (String(item.id) === String(selecionado || '') ? 'selected' : '') + '>' + item.nome + '</option>';
        });
        return html;
    }
    function adicionarParcelaCompra(dados) {
        dados = dados || {};
        const numero = dados.numero || String($('#tabela-fatura-avancada-compra tbody tr').length + 1).padStart(3, '0');
        const data = dados.data || $('#kt_datepicker_3').val() || new Date().toLocaleDateString('pt-BR');
        const valor = dados.valor || 0;
        const paga = Boolean(dados.paga);
        const bloqueio = paga ? 'disabled' : '';
        const acao = paga
            ? '<span class="label label-light-success label-inline" title="Parcela paga e protegida contra edição">Paga</span>'
            : '<button type="button" class="btn btn-sm btn-icon btn-danger remover-fatura-compra"><i class="la la-trash"></i></button>';
        const row = `
            <tr data-db-id="${dados.db_id || ''}" data-paga="${paga ? 1 : 0}" class="${paga ? 'bg-light-success' : ''}">
                <td><input type="text" class="form-control text-center fat-num-compra" value="${numero}" ${bloqueio}></td>
                <td><input type="text" class="form-control date-input fat-data-compra" value="${data}" ${bloqueio}></td>
                <td><input type="text" class="form-control money text-right fat-valor-compra" value="${moedaCompra(valor)}" ${bloqueio}></td>
                <td><select class="custom-select form-control fat-forma-compra" ${bloqueio}>
                    <option value="boleto" ${dados.forma_pagamento === 'boleto' ? 'selected' : ''}>Boleto</option>
                    <option value="pix" ${dados.forma_pagamento === 'pix' ? 'selected' : ''}>PIX</option>
                    <option value="dinheiro" ${dados.forma_pagamento === 'dinheiro' ? 'selected' : ''}>Dinheiro</option>
                    <option value="transferencia" ${dados.forma_pagamento === 'transferencia' ? 'selected' : ''}>Transferência</option>
                    <option value="cartao_credito" ${dados.forma_pagamento === 'cartao_credito' ? 'selected' : ''}>Cartão de crédito</option>
                    <option value="cartao_debito" ${dados.forma_pagamento === 'cartao_debito' ? 'selected' : ''}>Cartão de débito</option>
                    <option value="cheque" ${dados.forma_pagamento === 'cheque' ? 'selected' : ''}>Cheque</option>
                    <option value="outro" ${dados.forma_pagamento === 'outro' ? 'selected' : ''}>Outro</option>
                </select></td>
                <td><select class="custom-select form-control fat-conta-compra" ${bloqueio}>${optionsCompra(contasCompra, dados.conta_empresa_id, 'Sem baixa bancária imediata')}</select></td>
                <td><select class="custom-select form-control fat-veiculo-compra" ${bloqueio}>${optionsCompra(veiculosCompra, dados.veiculo_id, 'Usar veículo geral')}</select></td>
                <td class="text-center">${acao}</td>
            </tr>`;
        $('#tabela-fatura-avancada-compra tbody').append(row);
        $('.date-input').mask('00/00/0000');
        $('.money').mask('#.##0,00', {reverse: true});
        atualizarTotalFaturaCompra();
    }
    function atualizarTotalFaturaCompra() {
        let total = 0;
        $('#tabela-fatura-avancada-compra .fat-valor-compra').each(function () { total += parseMoedaCompra($(this).val()); });
        $('#total-fatura-avancada-compra').text('R$ ' + moedaCompra(total));
        return total;
    }

    window.coletarFaturaManualCompra = function () {
        if (!$('#usar_fatura_avancada_compra').is(':checked')) return [];
        const parcelas = [];
        $('#tabela-fatura-avancada-compra tbody tr').each(function () {
            parcelas.push({
                db_id: $(this).data('db-id') || null,
                numero: $(this).find('.fat-num-compra').val(),
                data: $(this).find('.fat-data-compra').val(),
                valor: $(this).find('.fat-valor-compra').val(),
                forma_pagamento: $(this).find('.fat-forma-compra').val(),
                conta_empresa_id: $(this).find('.fat-conta-compra').val() || null,
                veiculo_id: $(this).find('.fat-veiculo-compra').val() || null,
                paga: Number($(this).data('paga') || 0) === 1
            });
        });
        return parcelas;
    };

    $('#usar_fatura_avancada_compra').on('change', function () {
        const ativo = $(this).is(':checked');
        $('#painel-fatura-avancada-compra').toggle(ativo);
        if (ativo && !$('#tabela-fatura-avancada-compra tbody tr').length) {
            adicionarParcelaCompra({valor: Number(window.TOTAL || 0)});
        }
    });
    $('#btn-adicionar-fatura-compra').on('click', function () { adicionarParcelaCompra(); });
    $(document).on('click', '.remover-fatura-compra', function () { $(this).closest('tr').remove(); atualizarTotalFaturaCompra(); });
    $(document).on('keyup change blur', '.fat-valor-compra', atualizarTotalFaturaCompra);
    $('#btn-ratear-fatura-compra').on('click', function () {
        const ids = $('#rateio_veiculos_compra').val() || [];
        if (!ids.length) { alert('Selecione ao menos um veículo para o rateio.'); return; }
        const total = Number(window.TOTAL || 0);
        const base = Math.floor((total / ids.length) * 100) / 100;
        let acumulado = 0;
        if ($('#tabela-fatura-avancada-compra tbody tr[data-paga="1"]').length) {
            alert('O rateio não pode substituir parcelas que já foram pagas.');
            return;
        }
        $('#tabela-fatura-avancada-compra tbody').empty();
        ids.forEach(function (id, index) {
            const valor = index === ids.length - 1 ? Number((total - acumulado).toFixed(2)) : base;
            acumulado += valor;
            adicionarParcelaCompra({numero: String(index + 1).padStart(3, '0'), valor: valor, veiculo_id: id});
        });
    });

    if (faturasIniciaisCompra.length) {
        $('#usar_fatura_avancada_compra').prop('checked', true);
        $('#painel-fatura-avancada-compra').show();
        faturasIniciaisCompra.forEach(adicionarParcelaCompra);
    }

    @if($mostrarAdiantamento)
    function consultarAdiantamentoCompra() {
        const fornecedorId = $('#kt_select2_1').val() || $('.fornecedor').val();
        if (!fornecedorId || fornecedorId === '--') {
            $('#bloco-adiantamento-compra').hide();
            $('#usar_adiantamento').prop('checked', false);
            return;
        }
        $.get('/adiantamentos/consulta-saldo/fornecedor/' + fornecedorId).done(function (res) {
            const saldo = Number(res.saldo || 0);
            $('#bloco-adiantamento-compra').toggle(saldo > 0);
            $('#saldo-adiantamento-compra').text('Usar adiantamento do fornecedor (R$ ' + moedaCompra(saldo) + ')');
            if (saldo <= 0) $('#usar_adiantamento').prop('checked', false);
        });
    }
    $('#kt_select2_1, .fornecedor').on('change', consultarAdiantamentoCompra);
    setTimeout(consultarAdiantamentoCompra, 300);
    @endif
})();
</script>
