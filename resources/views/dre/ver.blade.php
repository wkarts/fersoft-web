{{-- resources/views/dre/ver.blade.php --}}
@extends('default.layout')

@section('content')
    <div class="container-fluid py-4">
        {{-- cabeçalho das datas e imposto --}}
        <div class="row mb-4">
            <div class="col-12 col-md-4 mb-2">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Início</h5>
                        <p class="text-success mb-0">{{ $dre->inicio->format('d/m/Y') }}</p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4 mb-2">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Fim</h5>
                        <p class="text-danger mb-0">{{ $dre->fim->format('d/m/Y') }}</p>
                    </div>
                </div>
            </div>
            @if($tributacao->regime != 1)
                <div class="col-12 col-md-4 mb-2">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">% Imposto</h5>
                            <p class="text-primary mb-0">{{ number_format($dre->percentual_imposto,2,',','.') }} %</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- observação --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-1">Observação</h5>
                        <p class="text-info mb-0">{{ $dre->observacao ?: '—' }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- categorias e lançamentos --}}
        @foreach($dre->categorias as $idx => $cat)
            @php $sign = in_array($idx, [0,2]) ? '+' : '-'; @endphp
            <div class="card mb-4">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <span>{{ $cat->nome }} ({{ $sign }})</span>
                    <button class="btn btn-sm btn-light" onclick="addLancamento({{ $cat }})">
                        <i class="la la-plus text-success"></i>
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="thead-light">
                            <tr>
                                <th>Descrição</th>
                                <th class="text-right">Valor (R$)</th>
                                <th class="text-right">%</th>
                                <th class="text-center">Ações</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($cat->lancamentos as $l)
                                <tr>
                                    <td>{{ $l->nome }}</td>
                                    <td class="text-right">R$ {{ number_format($l->valor,2,',','.') }}</td>
                                    <td class="text-right">
                                        @if($idx > 0)&minus; @endif
                                        {{ number_format($l->percentual,2,',','.') }}%
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-info" onclick="editLacamento({{ $l }})">
                                            <i class="la la-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger"
                                                onclick="swal('Atenção!','Deseja remover este registro?','warning')
                                                     .then(ok => ok && location.href='/dre/deleteLancamento/{{ $l->id }}')">
                                            <i class="la la-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach

                            @if($idx > 2)
                                <tr class="font-weight-bold text-info">
                                    <td>Total {{ mb_strtolower($cat->nome) }}</td>
                                    <td class="text-right">R$ {{ number_format($cat->soma(),2,',','.') }}</td>
                                    <td class="text-right">− {{ number_format($cat->percentual(),2,',','.') }}%</td>
                                    <td></td>
                                </tr>
                            @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach

        {{-- resumo final --}}
        <div class="card mb-4">
            @php $lucro = $dre->lucro_prejuizo; @endphp
            <div class="card-body d-flex justify-content-between align-items-center {{ $lucro>=0?'bg-success text-white':'bg-danger text-white' }}">
                <span>Lucro (Prejuízo) no Período</span>
                <strong>R$ {{ number_format($lucro,2,',','.') }}</strong>
            </div>
        </div>

        {{-- botão cancelar / imprimir --}}
        <div class="text-right mb-4">
            <a class="btn btn-info btn-lg" href="/dre/list">
                <i class="la la-close"></i> Cancelar
            </a>
            <a class="btn btn-info btn-lg" href="/dre/imprimir/{{ $dre->id }}">
                <i class="la la-print"></i> Imprimir
            </a>
        </div>
    </div>

    {{-- Modal de edição --}}
    <div class="modal fade" id="modal-edit" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm" role="document">
            <form method="post" action="/dre/updatelancamento">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 id="titulo" class="modal-title"></h6>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nome</label>
                            <input required type="text" id="nome-edit" name="nome" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Valor</label>
                            <input required type="text" id="valor" name="valor" class="form-control">
                        </div>
                        <input type="hidden" id="lancamento_id" name="lancamento_id">
                    </div>
                    <div class="modal-footer p-2">
                        <button type="submit" class="btn btn-success btn-block">
                            <i class="la la-edit"></i> Alterar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal de novo lançamento --}}
    <div class="modal fade" id="modal-new" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm" role="document">
            <form method="post" action="/dre/novolancamento">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 id="titulo-new" class="modal-title"></h6>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nome</label>
                            <input required type="text" id="nome" name="nome" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Valor</label>
                            <input required type="text" id="valor" name="valor" class="form-control money">
                        </div>
                        <input type="hidden" id="categoria_id" name="categoria_id">
                    </div>
                    <div class="modal-footer p-2">
                        <button type="submit" class="btn btn-success btn-block">
                            <i class="la la-check"></i> Salvar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- carrega o script que exibe os modais e preenche os campos --}}
    @push('scripts')
        <script src="{{ asset('js/dre.js') }}"></script>
    @endpush
@endsection
