@extends('default.layout')
@section('content')

<style>
    /* CONFIGURAÇÕES GERAIS DE IMPRESSÃO */
    @media print {
        /* 1. Esconde agressivamente menus, topos escuros e rodapés do template base */
        .no-print, form, button, .navbar, .aside, #kt_aside, .header, #kt_header, .header-mobile, .footer, .card-header, .subheader {
            display: none !important;
        }

        /* 2. Reseta o fundo e remove preenchimentos inúteis para usar a folha toda */
        body, .content, .d-flex, .wrapper {
            background-color: #fff !important;
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
        }

        .card { border: none !important; box-shadow: none !important; }
        .card-body { padding: 0 !important; }

        /* 3. Ajustes cruciais para a Tabela não quebrar errado */
        table { width: 100% !important; border-collapse: collapse !important; }
        thead { display: table-header-group !important; } /* Repete o cabeçalho no topo de cada página nova */
        tr { page-break-inside: avoid !important; } /* Proíbe o navegador de cortar uma linha pela metade */
        
        th, td { 
            font-size: 10px !important; /* Fonte um pouco menor para caber todas as colunas lado a lado */
            padding: 4px 6px !important;
            border: 1px solid #ddd !important; /* Borda cinza clara para guiar a leitura */
        }

        /* 4. Força a impressão exata das cores de fundo (badges) */
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        /* 5. Mantém as cores dos botõezinhos e textos alinhados */
        .text-right { text-align: right !important; }
        .badge { padding: 4px 8px !important; border-radius: 4px !important; display: inline-block; font-size: 9px !important; font-weight: bold; }
        .badge-danger { background-color: #F64E60 !important; color: #fff !important; border: none !important; }
        .badge-warning { background-color: #FFA800 !important; color: #fff !important; border: none !important; }
        .badge-success { background-color: #1BC5BD !important; color: #fff !important; border: none !important; }
        .badge-primary { background-color: #3699FF !important; color: #fff !important; border: none !important; }
        .label-light-success { background-color: #C9F7F5 !important; color: #1BC5BD !important; padding: 2px 6px; border-radius: 4px; }
        .label-light-info { background-color: #EEE5FF !important; color: #8950FC !important; padding: 2px 6px; border-radius: 4px; }
    }
</style>

<div class="card card-custom">
    <div class="card-header no-print">
        <h3 class="card-title">{{ $title }}</h3>
    </div>
    
    <div class="card-body">
        <form method="GET" action="/compraconferencia" class="no-print">
            <div class="row">
                <div class="col-md-2">
                    <label>Data Inicial</label>
                    <input type="date" name="data_inicial" class="form-control" value="{{ $data_inicial }}">
                </div>
                <div class="col-md-2">
                    <label>Data Final</label>
                    <input type="date" name="data_final" class="form-control" value="{{ $data_final }}">
                </div>

                <div class="col-md-2">
                    <label>Filial</label>
                    <select name="filial_id" class="form-control select2">
                        <option value="todos">Todas</option>
                        <option value="matriz" {{ request('filial_id') == 'matriz' ? 'selected' : '' }}>Matriz</option>
                        @foreach($filiais as $f)
                            <option value="{{ $f->id }}" {{ request('filial_id') == $f->id ? 'selected' : '' }}>
                                {{ $f->descricao ?? $f->nome ?? 'Filial '.$f->id }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label>Fornecedor</label>
                    <select name="fornecedor_id" class="form-control select2">
                        <option value="todos">Todos</option>
                        @foreach($fornecedores as $f)
                            <option value="{{ $f->id }}" {{ request('fornecedor_id') == $f->id ? 'selected' : '' }}>
                                {{ $f->razao_social }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label>Categoria (Múltipla)</label>
                    @php $cats = request('categoria_id', []); @endphp
                    <select name="categoria_id[]" class="form-control select2" multiple="multiple" data-placeholder="Todas as categorias">
                        @foreach($categorias as $cat)
                            <option value="{{ $cat->id }}" {{ in_array($cat->id, $cats) ? 'selected' : '' }}>{{ $cat->nome }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-2">
                    <label>Nº Nota</label>
                    <input type="text" name="numero_nota" class="form-control" value="{{ request('numero_nota') }}">
                </div>

                <div class="col-md-2">
                    <label>Estado</label>
                    <select name="estado" class="form-control select2">
                        <option value="todos">Todos</option>
                        <option value="novo" {{ request('estado') == 'novo' ? 'selected' : '' }}>NOVO</option>
                        <option value="importado" {{ request('estado') == 'importado' ? 'selected' : '' }}>IMPORTADO</option>
                        <option value="emitida" {{ request('estado') == 'emitida' ? 'selected' : '' }}>EMITIDA</option>
                        <option value="aprovado" {{ request('estado') == 'aprovado' ? 'selected' : '' }}>APROVADO</option>
                        <option value="rejeitado" {{ request('estado') == 'rejeitado' ? 'selected' : '' }}>REJEITADO</option>
                    </select>
                </div>

                <div class="col-md-8 text-right align-self-end">
                    <button class="btn btn-primary">Filtrar</button>
                    <button type="button" onclick="window.print()" class="btn btn-secondary">
                        <i class="fa fa-print"></i> Imprimir
                    </button>
                </div>
            </div>
        </form>

        <div class="d-none d-print-block text-center mb-5">
            <h2 class="font-weight-bold">CONFERÊNCIA DE COMPRAS</h2>
            <p>Período: {{ date('d/m/Y', strtotime($data_inicial)) }} a {{ date('d/m/Y', strtotime($data_final)) }}</p>
        </div>

        <div class="table-responsive">
            <table class="table table-vertical-center">
                <thead>
                    <tr>
                        <th style="width: 8%">Nº Nota</th>
                        <th style="width: 10%">Estado</th>
                        <th style="width: 10%">Tipo</th>
                        <th>Fornecedor/Terceiro</th>
                        <th class="text-right">Qtd. Itens</th>
                        <th class="text-right">Valor Nota</th>
                        <th class="text-right">V. Pago</th>
                        <th class="text-right">Saldo</th>
                        <th style="width: 15%">Financeiro</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($compras as $c)
                    @php
                        $valorNota = (float)$c->valor;
                        $valorPago = $c->contasPagar->sum('valor_pago');
                        $falta = $valorNota - $valorPago;
                        $estado = strtoupper($c->estado);
                    @endphp
                    <tr>
                        <td><strong>{{ $c->numero_exibicao ?? '---' }}</strong></td>
                        <td>
                            @if($estado == 'REJEITADO')
                                <span class="badge badge-danger">REJEITADO ⚠️</span>
                            @elseif($estado == 'NOVO')
                                <span class="badge badge-warning">NOVO ⏳</span>
                            @elseif($estado == 'IMPORTADO')
                                <span class="badge badge-primary">IMPORTADO</span>
                            @elseif($estado == 'APROVADO')
                                <span class="badge badge-success" style="background-color: #28a745 !important;">APROVADO ✔️</span>
                            @else
                                <span class="badge badge-success">{{ $estado }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="label label-inline {{ $c->tipo_relatorio == 'PRÓPRIA' ? 'label-light-success' : 'label-light-info' }}">
                                {{ $c->tipo_relatorio }}
                            </span>
                        </td>
                        <td>{{ $c->fornecedor->razao_social ?? 'N/A' }}</td>
                        <td class="text-right">{{ number_format($c->itens->sum('quantidade'), 2, ',', '.') }}</td>
                        <td class="text-right">R$ {{ number_format($valorNota, 2, ',', '.') }}</td>
                        <td class="text-right" style="color: #1BC5BD !important;">R$ {{ number_format($valorPago, 2, ',', '.') }}</td>
                        <td class="text-right" style="color: #F64E60 !important;">R$ {{ number_format($falta, 2, ',', '.') }}</td>
                        <td>
                            @forelse($c->contasPagar as $cp)
                                <small class="d-block font-weight-bold">
                                    [{{ $cp->categoria->nome ?? 'S/C' }}] {{ $cp->status ? 'Pago' : 'Aberto' }}
                                </small>
                            @empty
                                <small class="text-muted">Sem Lançamento</small>
                            @endforelse
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-footer bg-light">
        <div class="row align-items-center">
            <div class="col-3 text-center border-right">
                <small class="text-muted d-block">Qtd. Total de Itens</small>
                <h4 class="font-weight-bolder">{{ number_format($total_produtos, 2, ',', '.') }}</h4>
            </div>
            <div class="col-2 text-center border-right">
                <small class="text-muted d-block">Notas Próprias</small>
                <h4 class="font-weight-bolder text-primary">{{ $total_propria }}</h4>
            </div>
            <div class="col-2 text-center border-right">
                <small class="text-muted d-block">Notas Terceiros</small>
                <h4 class="font-weight-bolder text-warning">{{ $total_terceiro }}</h4>
            </div>
            <div class="col-5 text-right">
                <small class="text-muted d-block">VALOR TOTAL DO PERÍODO</small>
                <h2 class="font-weight-boldest text-success">R$ {{ number_format($soma_valores, 2, ',', '.') }}</h2>
            </div>
        </div>
    </div>
</div>

@section('javascript')
<script type="text/javascript">
    $(function () {
        $('.select2').select2();
    });
</script>
@endsection

@endsection
