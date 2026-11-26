@php
    $sitePagamento = \Illuminate\Support\Facades\Schema::hasColumn('pagamento_conexoes', 'provedor')
        ? \App\Services\Pagamentos\Estabelecimento::paraParametros($localPagamento) : null;
@endphp
@if($sitePagamento)
    @php
        $catalogoPagamento = config('pagamentos_eletronicos.provedores');
        $provedores = $sitePagamento->provedores()->orderBy('provedor')->get();
        $disponiveis = array_diff_key($catalogoPagamento, $provedores->keyBy('provedor')->all());
        $rotaParametros = 'parametros-pagamentos.'.($localPagamento ? 'filial' : 'emitente').'.';
        $destino = $localPagamento ? ['filial' => $localPagamento] : [];
        $ambienteFiscal = $sitePagamento::fiscal($sitePagamento->empresa, $localPagamento)->value;
        $conexoesPagamento = \App\Models\PagamentoConexao::where('empresa_id', $sitePagamento->empresa)
            ->where('local', $localPagamento)->get()->groupBy('provedor');
    @endphp
    <details id="pagamentos-eletronicos" class="card card-body mb-3" @if(session('abrir_parametros_pagamento')) open @endif>
        <summary class="font-weight-bold">Pagamentos eletrônicos · {{ $localPagamento ? 'Filial' : 'Matriz' }}</summary>
        <div class="table-responsive mt-3"><table class="table">
            <thead><tr><th>Provedor</th><th>Ambiente em uso</th><th>Ações</th></tr></thead>
            <tbody>
            @forelse($provedores as $provedor)
                @php($ativaPagamento = $conexoesPagamento->get($provedor->provedor, collect())->firstWhere('selecionada', true))
                <tr><td>{{ $catalogoPagamento[$provedor->provedor]['nome'] ?? $provedor->provedor }}</td>
                    <td>{{ !$ativaPagamento ? 'Inativo' : ($ativaPagamento->ambiente === 'sandbox' ? 'Homologação' : ($ambienteFiscal === 'production' ? 'Produção' : 'Bloqueado pelo ambiente fiscal')) }}</td>
                    <td>@isset($catalogoPagamento[$provedor->provedor])
                        <button class="btn btn-sm btn-light-primary" type="button" data-toggle="modal" data-target="#parametros-provedor-{{ $provedor->id }}">Configurar</button>
                    @endisset</td></tr>
            @empty
                <tr><td colspan="3">Nenhum provedor adicionado neste estabelecimento.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        @if($disponiveis)
            <form method="post" action="{{ route($rotaParametros.'adicionar', $destino) }}">
                @csrf
                <input type="hidden" name="estabelecimento" value="{{ $sitePagamento->empresa }}-{{ $localPagamento }}">
                <label>Provedor <select required class="form-control" name="provedor">
                    @foreach($disponiveis as $codigo => $definicao)<option value="{{ $codigo }}">{{ $definicao['nome'] }}</option>@endforeach
                </select></label>
                <button class="btn btn-primary" type="submit">Adicionar provedor</button>
            </form>
        @endif
    </details>
    @foreach($provedores as $provedor)
        @isset($catalogoPagamento[$provedor->provedor])
            <div class="modal fade" id="parametros-provedor-{{ $provedor->id }}" tabindex="-1" role="dialog" aria-labelledby="nome-provedor-{{ $provedor->id }}">
                <div class="modal-dialog modal-lg" role="document"><div class="modal-content">
                    <div class="modal-header"><h5 id="nome-provedor-{{ $provedor->id }}">{{ $catalogoPagamento[$provedor->provedor]['nome'] }} · {{ $localPagamento ? 'Filial' : 'Matriz' }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">&times;</button></div>
                    <div class="modal-body">
                        @include($catalogoPagamento[$provedor->provedor]['formulario'], ['conexoes' => $conexoesPagamento->get($provedor->provedor, collect())])
                    </div>
                </div></div>
            </div>
        @endisset
    @endforeach
@endif
