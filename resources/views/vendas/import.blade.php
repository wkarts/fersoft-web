@extends('default.layout')
@section('content')

    <style>
        /* Resumo superior */
        .card-custom .card-body h4 strong {
            color: #3699FF;
        }
        .summary-badge {
            display: inline-block;
            background: #E3F2FD;
            color: #3699FF;
            padding: .5rem 1rem;
            border-radius: .375rem;
            font-weight: 600;
        }

        /* Formulário */
        .form-row .custom-select {
            min-width: 200px;
        }

        /* Accordion */
        .accordion .card {
            border: 1px solid #e2e5ec;
            border-radius: .375rem;
            margin-bottom: .75rem;
        }
        .accordion .card-header {
            background: #f7f9fc;
            padding: .75rem 1.25rem;
            cursor: pointer;
            position: relative;
        }
        .accordion .card-header:hover {
            background: #eef2f6;
        }
        .accordion .card-header .toggle-icon {
            position: absolute;
            right: 1.25rem;
            top: 50%;
            transform: translateY(-50%) rotate(0deg);
            transition: transform .3s ease;
        }
        /* Aproveita a classe .collapsed do seu toggle */
        .accordion .card-header.collapsed .toggle-icon {
            transform: translateY(-50%) rotate(-90deg);
        }
        .accordion .card-body {
            background: #ffffff;
            padding: 1rem 1.25rem;
        }

        /* Títulos das seções */
        .card-body h3.card-title {
            color: #1F2937;
            border-left: 3px solid #3699FF;
            padding-left: .75rem;
            margin-bottom: 1rem;
        }

        /* Itens de detalhe */
        .card-body h5 {
            display: flex;
            justify-content: space-between;
            margin-bottom: .5rem;
        }
        .card-body h5 strong {
            color: #212529;
        }

        /* Botão de salvar */
        #salvar-venda {
            padding: .75rem 1.5rem;
            font-size: 1rem;
        }
    </style>

    <div class="card card-custom gutter-b">
        <div class="card-body">

            {{-- Resumo de total importado --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>Total de Arquivos importados: <strong>{{ sizeof($data) }}</strong></h4>
                <span class="summary-badge">{{ sizeof($data) }}</span>
            </div>

            <form method="post" action="/vendas/importStore">
                @csrf

                {{-- Seletor de Local --}}
                <div class="form-row mb-4">
                    <div class="form-group col-lg-3 col-md-4 col-sm-6">
                        <label class="col-form-label">Local</label>
                        <div class="input-group">
                            <select class="custom-select form-control" name="tabela">
                                <option value="vendas">VENDAS</option>
                                <option @if(!$data[0]['cliente']) selected @endif value="venda_caixas">PDV</option>
                            </select>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="data" value="{{ json_encode($data) }}">

                {{-- Accordion de cada XML --}}
                <div id="accordionExample1" class="accordion">
                    @foreach($data as $key => $d)
                        <div class="card">
                            <div class="card-header {{ $key>0?'collapsed':'' }}"
                                 data-toggle="collapse"
                                 data-target="#collapseOne{{ $key }}">
                                <label class="checkbox checkbox-info check-sub mb-0 mr-2">
                                    <input checked type="checkbox" name="ch_{{ $d['chave'] }}">
                                    <span></span>
                                </label>
                                <strong class="text-info mr-3">{{ $d['chave'] }}</strong>
                                <small class="text-muted">{{ \Carbon\Carbon::parse($d['data'])->format('d/m/Y H:i:s') }}</small>
                                <i class="la la-angle-down toggle-icon"></i>
                            </div>
                            <div id="collapseOne{{ $key }}"
                                 class="collapse {{ $key==0?'show':'' }}"
                                 data-parent="#accordionExample1">
                                <div class="card-body">

                                    {{-- Cliente --}}
                                    @if($d['cliente'])
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <h3 class="card-title">Cliente</h3>
                                                <h5>Razão social: <strong>{{ $d['cliente']['razao_social'] }}</strong></h5>
                                                <h5>Nome fantasia: <strong>{{ $d['cliente']['nome_fantasia'] }}</strong></h5>
                                                <h5>CNPJ/CPF: <strong>{{ $d['cliente']['cpf_cnpj'] }}</strong></h5>
                                                <h5>IE/RG: <strong>{{ $d['cliente']['ie_rg'] }}</strong></h5>
                                                <h5>Endereço: <strong>{{ $d['cliente']['rua'] }}, {{ $d['cliente']['numero'] }} – {{ $d['cliente']['bairro'] }}</strong></h5>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Produtos --}}
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <h3 class="card-title">Produtos</h3>
                                            @foreach($d['produtos'] as $p)
                                                <h5>Código: <strong>{{ $p['codigo'] }}</strong></h5>
                                                <h5>Nome: <strong>{{ $p['xProd'] }}</strong></h5>
                                                <h5>CFOP: <strong>{{ $p['CFOP'] }}</strong></h5>
                                                <h5>Unidade: <strong>{{ $p['uCom'] }}</strong></h5>
                                                <h5>Valor unitário: <strong>{{ number_format((float)$p['vUnCom'],2,',','.') }}</strong></h5>
                                                <h5>Quantidade: <strong>{{ $p['qCom'] }}</strong></h5>
                                                <h5>NCM: <strong>{{ $p['NCM'] }}</strong></h5>
                                                <h5>Código de barras: <strong>{{ $p['codBarras'] }}</strong></h5>
                                                @if(! $loop->last)<hr>@endif
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- Fatura --}}
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <h3 class="card-title">Fatura</h3>
                                            @foreach($d['fatura'] as $f)
                                                <h5>Vencimento: <strong>{{ \Carbon\Carbon::parse($f['vencimento'])->format('d/m/Y') }}</strong></h5>
                                                <h5>Valor: <strong>{{ number_format((float)$f['valor_parcela'],2,',','.') }}</strong></h5>
                                                @if(! $loop->last)<hr>@endif
                                            @endforeach
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Botão de confirmação --}}
                <div class="text-right mt-4">
                    <button id="salvar-venda" type="submit" class="btn btn-lg btn-success">
                        <i class="la la-check"></i> Salvar Importação
                    </button>
                </div>

            </form>
        </div>
    </div>

@endsection
