@extends('default.layout', ['title' => 'Gestão de Contas e Bancos'])
@section('content')

    <style>
        .card-kpi { border-left: 4px solid #3699FF; transition: transform 0.2s; }
        .card-kpi:hover { transform: translateY(-3px); }
        .card-kpi.success { border-left-color: #1BC5BD; }
        .card-kpi.danger { border-left-color: #F64E60; }
    </style>

    {{-- CARDS DE RESUMO FINANCEIRO (VISUAL MEHORADO) --}}
    @php
        $totalSaldo = $data->sum('saldo');
        $contasAtivas = $data->where('status', 1)->count();
    @endphp

    <div class="row mb-5">
        <div class="col-md-4">
            <div class="card card-custom card-kpi success bg-white shadow-sm padding-5">
                <div class="card-body p-4">
                    <span class="text-muted font-weight-bold d-block">Saldo Geral em Contas</span>
                    <span class="font-weight-bolder font-size-h2 {{ $totalSaldo < 0 ? 'text-danger' : 'text-success' }}">
                    R$ {{ moeda($totalSaldo) }}
                </span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-custom card-kpi bg-white shadow-sm padding-5">
                <div class="card-body p-4">
                    <span class="text-muted font-weight-bold d-block">Contas Ativas</span>
                    <span class="font-weight-bolder font-size-h2 text-dark">{{ $contasAtivas }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-4 text-right my-auto">
            <a href="{{ route('contas-empresa.create') }}" class="btn btn-success btn-lg font-weight-bold">
                <i class="fa fa-plus-circle"></i> Nova Conta
            </a>
            <button type="button" class="btn btn-info btn-lg ml-2 font-weight-bold" data-toggle="modal" data-target="#modalManual">
                <i class="fa fa-question-circle"></i> Ajuda
            </button>
        </div>
    </div>

    <div class="card card-custom shadow-sm">
        <div class="card-header border-0 pt-5">
            <h3 class="card-title font-weight-bolder text-dark">Contas Cadastradas</h3>
        </div>
        <div class="card-body pt-2">
            <div class="table-responsive">
                <table class="table table-head-custom table-vertical-center id="kt_advance_table_widget_1">
                <thead>
                <tr class="text-left text-uppercase">
                    <th>Conta / Banco</th>
                    <th>Agência / Conta</th>
                    <th>Plano de Contas</th>
                    <th>Local</th>
                    <th>Status</th>
                    <th class="text-right">Saldo Inicial</th>
                    <th class="text-right">Saldo Atual</th>
                    <th class="text-center">Ações</th>
                </tr>
                </thead>
                <tbody>
                @foreach($data as $item)
                    <tr>
                        <td>
                            <span class="text-dark-75 font-weight-bolder d-block font-size-lg">{{ $item->nome }}</span>
                            <span class="text-muted font-weight-bold">{{ $item->banco ?? 'Caixa Interno' }}</span>
                        </td>
                        <td>
                            <span class="text-dark-75 font-weight-bold d-block">Ag: {{ $item->agencia ?? '-' }}</span>
                            <span class="text-muted">Cc: {{ $item->conta ?? '-' }}</span>
                        </td>
                        <td>
                            <span class="label label-inline label-light-primary font-weight-bold">
                                {{ $item->plano->descricao ?? 'Não vinculado' }}
                            </span>
                        </td>
                        <td>
                            @php
                                $nomeLocal = 'Matriz';
                                if ($item->filial_id && $item->filial_id > 0) {
                                    $listaLocais = __locaisAtivos();
                                    $nomeLocal = isset($listaLocais[$item->filial_id]) ? $listaLocais[$item->filial_id] : 'Filial ' . $item->filial_id;
                                }
                            @endphp
                            <span class="label label-inline label-light-danger font-weight-bold">
                                {{ $nomeLocal }}
                            </span>
                        </td>
                        <td>
                            @if($item->status)
                                <span class="label label-dot label-success mr-2"></span><span class="font-weight-bold text-success">Ativa</span>
                            @else
                                <span class="label label-dot label-danger mr-2"></span><span class="font-weight-bold text-danger">Inativa</span>
                            @endif
                        </td>
                        <td class="text-right font-weight-bold text-muted">
                            R$ {{ moeda($item->saldo_inicial) }}
                        </td>
                        <td class="text-right">
                            <span class="font-weight-bolder font-size-lg {{ $item->saldo < 0 ? 'text-danger' : 'text-success' }}">
                                R$ {{ moeda($item->saldo) }}
                            </span>
                        </td>
                        <td class="text-center">
                            <a title="Ver Extrato / Movimentações" href="{{ route('contas-empresa.show', $item->id) }}" class="btn btn-icon btn-light-primary btn-sm mr-1">
                                <i class="la la-list"></i>
                            </a>
                            <a title="Editar Conta" href="{{ route('contas-empresa.edit', $item->id) }}" class="btn btn-icon btn-light-warning btn-sm mr-1">
                                <i class="la la-edit"></i>
                            </a>
                            <button title="Excluir Conta" type="button" class="btn btn-icon btn-light-danger btn-sm" onclick="excluirConta({{$item->id}})">
                                <i class="la la-trash"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
