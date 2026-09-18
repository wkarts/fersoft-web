@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">
    <div class="card-header border-0 pt-6">
        <h3 class="card-title font-weight-bolder text-dark">
            <i class="la la-file-import text-success icon-xl mr-2"></i> Finalizar Importação de NFS-e
        </h3>
        <div class="card-toolbar">
            <a href="/nfse-tomadas" class="btn btn-light-danger font-weight-bold"><i class="la la-arrow-left"></i> Voltar</a>
        </div>
    </div>

    <div class="card-body">
        <form action="/nfse-tomadas/salvar-importacao/{{ $nota->id }}" method="POST" id="form-confirmar-importacao">
            @csrf

            <div class="row mb-8">
                <div class="col-md-8">
                    <div class="card card-custom bg-light-info shadow-none p-5">
                        <h5 class="text-info font-weight-bold mb-3">Dados do Documento</h5>
                        <div class="row">
                            <div class="col-md-3"><span>Nota:</span><br><strong>{{ $nota->numero_nota }}</strong></div>
                            <div class="col-md-4"><span>Emissão:</span><br><strong>{{ date('d/m/Y', strtotime($nota->data_emissao)) }}</strong></div>
                            <div class="col-md-5"><span>Prestador:</span><br><strong>{{ $nota->prestador_nome }}</strong></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-custom bg-light-secondary shadow-none p-5 h-100">
                        <h5 class="text-dark font-weight-bold mb-2">Descrição do Serviço</h5>
                        <div style="max-height: 80px; overflow-y: auto; font-size: 0.9rem;">
                            {!! isset($descricao_servico) ? nl2br(e($descricao_servico)) : 'Prestação de serviços gerais.' !!}
                        </div>
                    </div>
                </div>
            </div>

            @php
                $vServico = $nota->valor_servico;
                $vLiquido = $nota->valor_liquido > 0 ? $nota->valor_liquido : $vServico;
                $vPis = 0; $vCofins = 0; $vCsll = 0; $vIr = 0;
                if (round($vServico - $vLiquido, 2) > 0) {
                    $vPis = round($vServico * 0.0065, 2);
                    $vCofins = round($vServico * 0.03, 2);
                    $vCsll = round($vServico * 0.01, 2);
                    $vIr = round($vServico * 0.015, 2);
                }
            @endphp
            <h5 class="text-dark font-weight-bold mb-4">Retenções e Valor Líquido</h5>
            <div class="table-responsive mb-8">
                <table class="table table-bordered text-center">
                    <thead class="bg-dark text-white">
                        <tr><th>Valor Serviço</th><th>PIS</th><th>COFINS</th><th>CSLL</th><th>IRRF</th><th>VALOR LÍQUIDO</th></tr>
                    </thead>
                    <tbody>
                        <tr class="font-weight-bolder">
                            <td class="text-primary">R$ {{ number_format($vServico, 2, ',', '.') }}</td>
                            <td>R$ {{ number_format($vPis, 2, ',', '.') }}</td>
                            <td>R$ {{ number_format($vCofins, 2, ',', '.') }}</td>
                            <td>R$ {{ number_format($vCsll, 2, ',', '.') }}</td>
                            <td>R$ {{ number_format($vIr, 2, ',', '.') }}</td>
                            <td class="text-success font-size-h4">R$ {{ number_format($vLiquido, 2, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="card card-custom gutter-b shadow-sm border border-light">
                <div class="card-header border-0"><h3 class="card-title font-weight-bold">Configuração de Pagamento e Rateio</h3></div>
                <div class="card-body">
                    <div class="row mb-6 bg-light-secondary p-4 rounded">
                        <div class="form-group col-lg-3">
                            <label>Forma de Faturamento</label>
                            <select id="tipo_condicao" class="custom-select form-control">
                                <option value="vista">À Vista</option>
                                <option value="rateio">Ratear por Veículos</option>
                                <option value="prazo">Parcelamento Manual</option>
                            </select>
                        </div>
                        <div class="form-group col-lg-6 id-div-rateio" style="display:none;">
                            <label>Selecione os Veículos</label>
                            <select id="veiculos_rateio" class="form-control select2-custom" multiple="multiple" style="width: 100%">
                                @foreach($veiculos as $v)
                                    <option value="{{ $v->id }}">{{ $v->placa }} - {{ $v->modelo }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-lg-3 div-gerador" style="display:none;">
                            <label>Qtd Parcelas</label>
                            <div class="input-group">
                                <input type="number" id="qtd_parcelas_manual" name="quantidade_parcelas" class="form-control" value="1" min="1">
                                <div class="input-group-append"><button type="button" id="btn_gerar_parcelas" class="btn btn-primary">Gerar</button></div>
                            </div>
                        </div>
                    </div>
                    <table class="table table-bordered table-striped" id="tabela-fatura">
                        <thead><tr><th>Parcela</th><th>Vencimento</th><th>Valor (R$)</th><th>Veículo</th><th>Ações</th></tr></thead>
                        <tbody></tbody>
                    </table>
                    <input type="hidden" name="fatura_json" id="fatura_json_input">
                </div>
            </div>

            <div class="row mb-8">
                <div class="col-md-4">
                    <label>Categoria da Conta *</label>
                    <select name="categoria_conta_id" class="custom-select form-control" required>
                        <option value="">-- Selecione --</option>
                        @foreach($categoriasDeConta as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label>Prazo de Pagamento (dias) *</label>
                    <input type="number" name="prazo_pagamento" class="form-control" value="30" min="0" required>
                </div>
            </div>
            <div class="text-right">
                <button type="submit" id="btn-salvar-importacao" class="btn btn-success font-weight-bolder px-10 py-4 shadow-lg">
                    <i class="la la-check-circle icon-lg"></i> CONFIRMAR E SALVAR
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('javascript')
<script>
  $(document).ready(function() {
        $('#veiculo_geral').change(function() {
            let idVeiculo = $(this).val();
            $('.select-veiculo-parcela').val(idVeiculo);
            atualizarFaturaJson();
        });

        $('#tipo_condicao').change(function() {
            let tipo = $(this).val();
            if (tipo === 'rateio') {
                $('.id-div-rateio').fadeIn();
                $('.div-gerador').hide();
                executarRateioVeiculos();
            } else if (tipo === 'prazo') {
                $('.div-gerador').fadeIn();
                $('.id-div-rateio').hide();
            } else if (tipo === 'vista') {
                $('.div-gerador').hide();
                $('.id-div-rateio').hide();
                
                let totalNF = "{{ number_format((double)$infos['vNF'], 2, ',', '.') }}";
                let hoje = "{{ date('d/m/Y') }}";
                
                $('#tabela-fatura tbody').html(`
                    <tr>
                        <td><input type="text" name="fat_num[]" class="form-control text-center font-weight-bold" value="001"></td>
                        <td><input type="text" name="fat_venc[]" class="form-control date-input text-center" value="${hoje}"></td>
                        <td><input type="text" name="fat_val[]" class="form-control money text-right font-weight-bold text-success" value="${totalNF}"></td>
                        <td>
                            <select name="fat_veiculo[]" class="custom-select form-control select-veiculo-parcela">
                                <option value="">Usar veículo geral da nota</option>
                                @foreach($veiculos as $v) <option value="{{ $v->id }}">{{ $v->placa }}</option> @endforeach
                            </select>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-icon btn-danger btn-remover-fat"><i class="la la-trash"></i></button>
                        </td>
                    </tr>
                `);
                $('.money').mask('#.##0,00', {reverse: true});
                $('.date-input').mask('00/00/0000');
                $('#tabela-fatura tbody .select-veiculo-parcela').val($('#veiculo_geral').val());
                atualizarFaturaJson();
            } else {
                location.reload(); // Recarrega para voltar o XML ao normal se desistir
            }
        });

        $('#veiculos_rateio').change(function() { executarRateioVeiculos(); });

        function executarRateioVeiculos() {
            let veiculosSelecionados = $('#veiculos_rateio').val();
            if (!veiculosSelecionados || veiculosSelecionados.length === 0) {
                $('#tabela-fatura tbody').html('<tr><td colspan="5" class="text-center text-danger">Selecione pelo menos um veículo no campo acima!</td></tr>');
                return;
            }

            let totalNF = parseFloat("{{ $infos['vNF'] }}");
            let qtdVeiculos = veiculosSelecionados.length;
            let valorFatiado = (totalNF / qtdVeiculos).toFixed(2);
            let valorFormatado = parseFloat(valorFatiado).toLocaleString('pt-br', {minimumFractionDigits: 2});
            let hoje = "{{ date('d/m/Y') }}";
            let html = '';

            veiculosSelecionados.forEach(function(veiculoId, index) {
                let numeroParcela = String(index + 1).padStart(3, '0');
                html += `
                    <tr>
                        <td><input type="text" name="fat_num[]" class="form-control text-center font-weight-bold" value="${numeroParcela}"></td>
                        <td><input type="text" name="fat_venc[]" class="form-control date-input text-center" value="${hoje}"></td>
                        <td><input type="text" name="fat_val[]" class="form-control money text-right font-weight-bold text-success" value="${valorFormatado}"></td>
                        <td>
                            <select name="fat_veiculo[]" class="custom-select form-control select-veiculo-parcela">
                                <option value="">Usar veículo geral da nota</option>
                                @foreach($veiculos as $v)
                                    <option value="{{ $v->id }}" ${veiculoId == "{{ $v->id }}" ? 'selected' : ''}>{{ $v->placa }} - {{ $v->modelo }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-icon btn-danger btn-remover-fat"><i class="la la-trash"></i></button>
                        </td>
                    </tr>
                `;
            });

            $('#tabela-fatura tbody').html(html);
            $('.money').mask('#.##0,00', {reverse: true});
            $('.date-input').mask('00/00/0000');
            atualizarFaturaJson();
        }

        $('#btn_gerar_parcelas').click(function() {
            let qtd = parseInt($('#qtd_parcelas_manual').val()) || 1;
            let totalNF = parseFloat("{{ $infos['vNF'] }}");
            let valorParcela = (totalNF / qtd).toFixed(2);
            let valorFormatado = parseFloat(valorParcela).toLocaleString('pt-br', {minimumFractionDigits: 2});
            let html = '';
            let dataBase = new Date();

            for (let i = 1; i <= qtd; i++) {
                dataBase.setMonth(dataBase.getMonth() + 1);
                let dia = String(dataBase.getDate()).padStart(2, '0');
                let msg = String(dataBase.getMonth() + 1).padStart(2, '0');
                let ano = dataBase.getFullYear();
                let dataStr = `${dia}/${msg}/${ano}`;

                html += `
                    <tr>
                        <td><input type="text" name="fat_num[]" class="form-control text-center font-weight-bold" value="${String(i).padStart(3, '0')}"></td>
                        <td><input type="text" name="fat_venc[]" class="form-control date-input text-center" value="${dataStr}"></td>
                        <td><input type="text" name="fat_val[]" class="form-control money text-right font-weight-bold text-success" value="${valorFormatado}"></td>
                        <td>
                            <select name="fat_veiculo[]" class="custom-select form-control select-veiculo-parcela">
                                <option value="">Usar veículo geral da nota</option>
                                @foreach($veiculos as $v) <option value="{{ $v->id }}">{{ $v->placa }}</option> @endforeach
                            </select>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-icon btn-danger btn-remover-fat"><i class="la la-trash"></i></button>
                        </td>
                    </tr>
                `;
            }
            $('#tabela-fatura tbody').html(html);
            $('.money').mask('#.##0,00', {reverse: true});
            $('.date-input').mask('00/00/0000');
            $('.select-veiculo-parcela').val($('#veiculo_geral').val());
            atualizarFaturaJson();
        });

        $('#btn-adicionar-linha-fatura').click(function() {
            let numLinhas = $('#tabela-fatura tbody tr').length + 1;
            let numFormatado = String(numLinhas).padStart(3, '0');
            let hoje = "{{ date('d/m/Y') }}";
            
            let novaLinha = `
                <tr>
                    <td><input type="text" name="fat_num[]" class="form-control text-center font-weight-bold" value="${numFormatado}"></td>
                    <td><input type="text" name="fat_venc[]" class="form-control date-input text-center" value="${hoje}"></td>
                    <td><input type="text" name="fat_val[]" class="form-control money text-right font-weight-bold text-success" value="0,00"></td>
                    <td>
                        <select name="fat_veiculo[]" class="custom-select form-control select-veiculo-parcela">
                            <option value="">Usar veículo geral da nota</option>
                            @foreach($veiculos as $v) <option value="{{ $v->id }}">{{ $v->placa }}</option> @endforeach
                        </select>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-icon btn-danger btn-remover-fat"><i class="la la-trash"></i></button>
                    </td>
                </tr>
            `;
            $('#tabela-fatura tbody').append(novaLinha);
            $('.money').mask('#.##0,00', {reverse: true});
            $('.date-input').mask('00/00/0000');
            $('#tabela-fatura tbody tr:last .select-veiculo-parcela').val($('#veiculo_geral').val());
            atualizarFaturaJson();
        });

        $(document).on('click', '.btn-remover-fat', function() {
            if(confirm("Deseja remover esta parcela do financeiro?")) {
                $(this).closest('tr').remove();
                atualizarFaturaJson();
            }
        });

        $(document).on('blur', 'input[name="fat_venc[]"], input[name="fat_val[]"], input[name="fat_num[]"]', function() {
            atualizarFaturaJson();
        });
        
        $(document).on('change', 'select[name="fat_veiculo[]"]', function() {
            atualizarFaturaJson();
        });

        function atualizarFaturaJson() {
            let faturas = [];
            $('#tabela-fatura tbody tr').each(function() {
                let num = $(this).find('input[name="fat_num[]"]').val();
                let venc = $(this).find('input[name="fat_venc[]"]').val();
                let val = $(this).find('input[name="fat_val[]"]').val();
                let veiculo = $(this).find('select[name="fat_veiculo[]"]').val();
                
                if(num && venc && val) {
                    faturas.push({
                        numero: num,
                        vencimento: venc,
                        valor_parcela: val,
                        veiculo_id: veiculo
                    });
                }
            });
            $('#fatura_json_input').val(JSON.stringify(faturas));
        }
    });
</script>
@endsection