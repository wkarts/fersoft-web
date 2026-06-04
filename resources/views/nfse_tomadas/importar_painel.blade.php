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

{{-- Interceptor JS para evitar duplo clique no salvamento --}}
<script>
    document.getElementById('form-confirmar-importacao').addEventListener('submit', function() {
        var btn = document.getElementById('btn-salvar-importacao');
        btn.disabled = true;
        btn.innerHTML = '<i class="la la-spinner la-spin"></i> Processando Lançamento...';
    });
</script>

@endsection