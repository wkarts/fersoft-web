@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">
    <div class="card-body">

        {{-- BLOCO DE ALERTAS --}}
        @if(session('mensagem_sucesso'))
            <div class="alert alert-custom alert-light-success fade show mb-10" role="alert">
                <div class="alert-icon"><i class="flaticon2-check-mark"></i></div>
                <div class="alert-text font-weight-bold">{{ session('mensagem_sucesso') }}</div>
                <div class="alert-close">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true"><i class="ki ki-close"></i></span>
                    </button>
                </div>
            </div>
        @endif

        @if(session('mensagem_erro'))
            <div class="alert alert-custom alert-light-danger fade show mb-10" role="alert">
                <div class="alert-icon"><i class="flaticon-warning"></i></div>
                <div class="alert-text font-weight-bold">{{ session('mensagem_erro') }}</div>
                <div class="alert-close">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true"><i class="ki ki-close"></i></span>
                    </button>
                </div>
            </div>
        @endif

        {{-- CABEÇALHO INTELIGENTE: TÍTULO NA ESQUERDA E BOTÕES NA DIREITA --}}
        <div class="row mb-6 align-items-center">
            <div class="col-lg-6 col-md-12 mb-4 mb-lg-0">
                <h3 class="card-label font-weight-bolder text-dark mb-1">NFS-e Tomadas (Recebidas)</h3>
                <span class="text-muted">Consulta de documentos fiscais eletrônicos no Ambiente Nacional (ADN)</span>
            </div>

            <div class="col-lg-6 col-md-12 text-lg-right">
                <a href="#" class="btn btn-info font-weight-bold mr-2" data-toggle="modal" data-target="#modalComoFuncionaTomadas">
                    <i class="la la-info-circle"></i> Como funciona?
                </a>

                <form action="{{ route('nfse.sincronizar-manuais') }}" method="POST" class="d-inline-block mr-2" onsubmit="return confirm('Deseja vincular pagamentos manuais compatíveis às NFS-e tomadas?');">
                    @csrf
                    <button type="submit" class="btn btn-light-primary font-weight-bold">
                        <i class="la la-link"></i> VINCULAR MANUAIS
                    </button>
                </form>

                <form action="/nfse-tomadas/sincronizar" method="GET" id="form-sincronizar" class="d-inline-flex align-items-center justify-content-end flex-wrap" style="gap: 10px;">
                    <select name="local" class="form-control custom-select bg-white border-success w-auto">
                        <option value="matriz">MATRIZ (Sede)</option>
                        @foreach($filiais as $f)
                            <option value="{{ $f->id }}">{{ $f->descricao }}</option>
                        @endforeach
                    </select>
                    <button type="submit" id="btn-buscar-receita" class="btn btn-success font-weight-bolder shadow-sm">
                        <i class="la la-cloud-download"></i> BUSCAR NA RECEITA
                    </button>
                </form>
            </div>
        </div>

        {{-- FORMULÁRIO DE FILTROS ESPAÇADO E ALINHADO --}}
        <div class="bg-light p-6 rounded mb-10">
            <form action="/nfse-tomadas" method="GET">
                <div class="row align-items-end">
                    
                    {{-- DATA INICIAL --}}
                    <div class="col-lg-2 col-md-4 form-group mb-3 mb-lg-0">
                        <label class="font-weight-bold">Data Inicial</label>
                        <div class="input-group date">
                            {{-- 🔥 ID kt_datepicker_3 DEVOLVIDO AQUI 🔥 --}}
                            <input type="text" name="data_inicial" class="form-control datepicker" readonly value="{{ $data_inicial ?? request('data_inicial') }}" id="kt_datepicker_3" />
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="la la-calendar"></i></span>
                            </div>
                        </div>
                    </div>

                    {{-- DATA FINAL --}}
                    <div class="col-lg-2 col-md-4 form-group mb-3 mb-lg-0">
                        <label class="font-weight-bold">Data Final</label>
                        <div class="input-group date">
                            {{-- 🔥 ID kt_datepicker_3 DEVOLVIDO AQUI 🔥 --}}
                            <input type="text" name="data_final" class="form-control datepicker" readonly value="{{ $data_final ?? request('data_final') }}" id="kt_datepicker_3" />
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="la la-calendar"></i></span>
                            </div>
                        </div>
                    </div>
                  
                    {{-- FORNECEDOR --}}
                    <div class="col-lg-4 col-md-4 form-group mb-3 mb-lg-0">
                        <label class="font-weight-bold">Fornecedor (Prestador)</label>
                        <input type="text" name="fornecedor" class="form-control" value="{{ request('fornecedor') }}" placeholder="Nome ou CNPJ...">
                    </div>

                    {{-- N° NOTA --}}
                    <div class="col-lg-2 col-md-6 form-group mb-3 mb-lg-0">
                        <label class="font-weight-bold">Nº Nota</label>
                        <input type="text" name="numero_nota" class="form-control" value="{{ request('numero_nota') }}" placeholder="Número...">
                    </div>

                    {{-- UNIDADE --}}
                    <div class="col-lg-2 col-md-6 form-group mb-3 mb-lg-0">
                        <label class="font-weight-bold">Unidade</label>
                        <select name="filial_id" class="form-control custom-select">
                            <option value="">Todas</option>
                            <option value="matriz" {{ request('filial_id') == 'matriz' ? 'selected' : '' }}>Matriz (Sede)</option>
                            @foreach($filiais as $f)
                                <option value="{{ $f->id }}" {{ request('filial_id') == $f->id ? 'selected' : '' }}>
                                    {{ $f->descricao }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- LINHA DE BOTÕES E CONTADOR --}}
                <div class="row mt-4">
                    <div class="col-12 d-flex justify-content-between align-items-center border-top pt-4">
                        <span class="text-muted font-size-xs">Total nesta listagem: <strong class="text-dark">{{ sizeof($docs) }}</strong> registros obtidos.</span>
                        <div>
                            <a href="/nfse-tomadas" class="btn btn-light-danger font-weight-bold mr-2">
                                <i class="la la-trash"></i> Limpar Filtros
                            </a>
                            <button type="submit" class="btn btn-primary font-weight-bold">
                                <i class="la la-search"></i> Filtrar
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        {{-- TABELA DE DOCUMENTOS --}}
        <div class="table-responsive">
            <table class="table table-head-custom table-vertical-center table-hover">
                <thead>
                    <tr class="text-left">
                        <th style="width: 110px;">Status ERP</th>
                        <th style="width: 110px;">Situação</th>
                        <th style="width: 130px;">Unidade</th>
                        <th style="width: 130px;">Nº Nota / NSU</th>
                        <th style="width: 110px;">Emissão</th>
                        <th>Prestador / Fornecedor</th>
                        <th style="width: 140px;">Valor do Serviço</th>
                        <th class="text-right" style="width: 180px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($docs as $d)
                        {{-- 👀 TRAVA DE CONTROLE: Oculta as linhas de avanço de transmissão do cliente final --}}
                        @if($d->prestador_nome == 'SINC_AVANCO_MECANICO' || $d->nome_filial == 'SINC_AVANCO_MECANICO')
                            @continue
                        @endif

                        @php
                            // 🔥 ULTRA RÁPIDO: O banco no Controller calcula tudo e manda direto para a memória
                            $jaNoPagar = (bool) ($d->ja_no_pagar ?? false);
                            $jaComprado = (bool) ($d->ja_comprado ?? false);

                            // Formatação do CNPJ para exibição visual limpa
                            $cnpjNumeros = preg_replace('/[^0-9]/', '', $d->prestador_cnpj_cpf);
                            if (strlen($cnpjNumeros) == 14) {
                                $cnpjFormatadoDoERP = substr($cnpjNumeros,0,2).'.'.substr($cnpjNumeros,2,3).'.'.substr($cnpjNumeros,5,3).'/'.substr($cnpjNumeros,8,4).'-'.substr($cnpjNumeros,12,2);
                            } else {
                                $cnpjFormatadoDoERP = $d->prestador_cnpj_cpf;
                            }
                        @endphp

                        <tr>
                            {{-- COLUNA STATUS ERP DUPLO DINÂMICO PRECISÃO MILIMÉTRICA --}}
                            <td>
                                <div class="d-flex align-items-center" style="gap: 5px;">
                                    {{-- Badge de Estoque / Compra --}}
                                    @if($jaComprado)
                                        <span class="label label-inline label-light-success font-weight-bold" title="Compra Importada">C ✔</span>
                                    @else
                                        <span class="label label-inline label-light-dark text-muted font-weight-bold" title="Compra Pendente">C ⏳</span>
                                    @endif

                                    {{-- Badge de Financeiro / Contas a Pagar --}}
                                    @if($jaNoPagar)
                                        <span class="label label-inline label-light-info font-weight-bold" title="Lançado no Contas a Pagar">F ✔</span>
                                    @else
                                        <span class="label label-inline label-light-warning font-weight-bold text-dark" title="Financeiro Pendente">F ⏳</span>
                                    @endif
                                </div>
                            </td>

                            {{-- COLUNA SITUAÇÃO FISCAL / MANIFESTO --}}
                            <td>
                                @if(($d->tipo ?? 0) == 4 || ($d->estado ?? '') == 'REJEITADA')
                                    <span class="label label-inline label-light-danger font-weight-bold">REJEITADA ❌</span>
                                @elseif(($d->tipo ?? 0) == 2 || $jaNoPagar)
                                    <span class="label label-inline label-light-success font-weight-bold">CONFIRMADA ✔</span>
                                @else
                                    <span class="label label-inline label-light-primary font-weight-bold">AUTORIZADA</span>
                                @endif
                            </td>
                            
                            {{-- UNIDADE REAL FILTRADA PELO LEFT JOIN --}}
                            <td>
                                <span class="text-dark-75 font-weight-bold font-size-sm d-block">
                                    {{ $d->nome_filial ?? 'MATRIZ' }}
                                </span>
                            </td>
                            
                            <td>
                                <span class="text-dark-75 font-weight-bolder d-block font-size-base">{{ $d->numero_nota }}</span>
                                <span class="text-muted font-size-xs">NSU: {{ $d->nsu }}</span>
                            </td>
                            
                            <td class="text-dark-75 font-weight-bold">
                                {{ date('d/m/Y', strtotime($d->data_emissao)) }}
                            </td>
                            
                            <td>
                                <span class="text-dark-75 font-weight-bolder d-block font-size-base">{{ $d->prestador_nome }}</span>
                                <span class="text-muted font-size-xs d-block">
                                    {{ $cnpjFormatadoDoERP }}
                                </span>
                            </td>
                            
                            <td>
                                <span class="text-dark-75 font-weight-bolder font-size-lg text-primary">
                                    R$ {{ number_format($d->valor_servico, 2, ',', '.') }}
                                </span>
                            </td>
                            
                            {{-- COLUNA AÇÕES REESTRUTURADA --}}
                            <td class="text-right">
                                <div class="d-flex justify-content-end" style="gap: 4px;">
                                    {{-- Bloqueio Inteligente de Duplicidade no Lançamento --}}
                                    @if(!$jaNoPagar && ($d->tipo ?? 0) != 4)
                                        <a href="/nfse-tomadas/detalhes/{{$d->id}}" class="btn btn-xs btn-info font-weight-bold px-3" title="Lançar nota no módulo financeiro">
                                            <i class="la la-file-import"></i> Lançar
                                        </a>
                                    @else
                                        <button class="btn btn-xs btn-light-success font-weight-bold px-3" disabled style="cursor: not-allowed;">
                                            <i class="la la-check"></i> Processada
                                        </button>
                                    @endif

                                    {{-- Botão Imprimir Espelho da Nota --}}
                                    <a href="/nfse-tomadas/espelho/{{$d->id}}" target="_blank" class="btn btn-icon btn-xs btn-light-primary" title="Imprimir Espelho da NFS-e">
                                        <i class="la la-print"></i>
                                    </a>

                                    @if(!empty($d->chave))
                                        <a href="{{ route('nfse-tomadas.xml', $d->id) }}" class="btn btn-icon btn-xs btn-light-success" title="Baixar XML Original"><i class="la la-download"></i></a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center p-10 text-muted font-weight-bold font-size-lg">
                                Nenhuma NFS-e recebida localizada para os filtros informados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- LINKS DE PAGINAÇÃO: Essencial para navegar entre as páginas de forma leve --}}
        <div class="d-flex justify-content-center mt-5">
            {!! $docs->appends(request()->all())->links() !!}
        </div>
        
    </div>
</div>

{{-- 📜 INTERCEPTOR JAVASCRIPT: Evita múltiplos cliques simultâneos e travamentos no cURL --}}
<script>
    document.getElementById('form-sincronizar').addEventListener('submit', function() {
        var btn = document.getElementById('btn-buscar-receita');
        btn.disabled = true;
        btn.innerHTML = '<i class="la la-spinner la-spin"></i> Buscando...';
    });
</script>
<div class="modal fade" id="modalComoFuncionaTomadas" tabindex="-1" role="dialog" aria-labelledby="modalLabelTomadas" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title font-weight-bold" id="modalLabelTomadas">
                    <i class="la la-file-invoice text-white icon-lg"></i> Rotina de NFS-e Tomada
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">x</button>
            </div>
            <div class="modal-body font-size-lg">
                <p>Esta rotina permite a gestão automática de notas fiscais de serviço emitidas contra o seu CNPJ:</p>
                
                <div class="mb-5">
                    <h6 class="text-info font-weight-bold">1. Sincronização</h6>
                    <p>Ao clicar em <b>Buscar na Receita</b>, o sistema consulta o Ambiente Dados Nacional (ADN) para buscar novas notas e importa o arquivo XML para a pasta segura do sistema.</p>
                </div>

                <div class="mb-5">
                    <h6 class="text-info font-weight-bold">2. Detalhamento e Descrição</h6>
                    <p>As notas baixadas são exibidas na listagem. O sistema extrai automaticamente a <b>discriminação do serviço</b> diretamente do conteúdo do XML, evitando digitação manual.</p>
                </div>

                <div class="mb-5">
                    <h6 class="text-info font-weight-bold">3. Integração Financeira</h6>
                    <p>Ao clicar em <b>Lançar</b>, o sistema automatiza:</p>
                    <ul class="list-unstyled">
                        <li><i class="la la-check text-success"></i> Autocadastro de fornecedores;</li>
                        <li><i class="la la-check text-success"></i> Cálculo automático de retenções (PIS, COFINS, IR, CSLL);</li>
                        <li><i class="la la-check text-success"></i> Provisionamento direto no seu Contas a Pagar.</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light font-weight-bold" data-dismiss="modal">Entendido</button>
            </div>
        </div>
    </div>
</div>
@endsection