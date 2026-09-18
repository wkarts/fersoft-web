@extends('default.layout')
@section('content')
    <style>
        /* Estilização moderna e limpa para tabelas corporativas */
        .table-hover tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.04);
        }
        .badge-status {
            padding: 6px 12px;
            font-weight: 600;
            border-radius: 4px;
            font-size: 11px;
            text-transform: uppercase;
        }
    </style>

    <div class="card card-custom gutter-b border-0 shadow-sm">
        <div class="card-body">
            <div class="mb-5" id="kt_user_profile_aside">
                <input type="hidden" id="_token" value="{{ csrf_token() }}">

                <!-- Cabeçalho e Botão de Novo Registro -->
                <div class="d-flex justify-content-between align-items-center flex-wrap mb-5">
                    <div>
                        <h3 class="font-weight-bolder text-dark mb-1">{{ $title }}</h3>
                        <span class="text-muted font-weight-bold font-size-sm">Total de Registros Encontrados: <strong class="text-success">{{ count($records) }}</strong></span>                </div>
                    <div>
                        <a href="{{ $newItemUrl }}" class="btn btn-primary font-weight-bold px-4 py-2">
                            <i class="la la-plus"></i> {{ $newItemText }}
                        </a>
                    </div>
                </div>


                <!-- Filtros de Pesquisa Fixos e Dinâmicos -->
                @php
                    // Captura a empresa_id de forma segura direto da sessão/requisição do ERP
                    $empresaIdSegura = request()->empresa_id ?? session('user_logged')['empresa_id'] ?? 1;

                    // Busca os dados diretamente para não depender do array do Controller
                    $listaVeiculos = \App\Models\Veiculo::where('empresa_id', $empresaIdSegura)->get();
                    $listaMotoristas = \App\Models\Funcionario::where('empresa_id', $empresaIdSegura)->get();
                @endphp

                <div class="card bg-light border-0 p-4 mb-5">
                    <form class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft" method="get" action="{{ $filterUrl }}">
                        <div class="row align-items-end">

                            <!-- Filtro por Veículo -->
                            <div class="form-group col-lg-3 col-md-4 col-sm-6 mb-3">
                                <label class="font-weight-bold text-dark font-size-sm">Veículo</label>
                                <select name="veiculo_id" class="form-control form-control-solid">
                                    <option value="">Todos os Veículos</option>
                                    @foreach($listaVeiculos as $v)
                                        <option value="{{ $v->id }}" {{ request()->get('veiculo_id') == $v->id ? 'selected' : '' }}>
                                            {{ $v->placa }} - {{ $v->modelo }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Filtro por Motorista -->
                            <div class="form-group col-lg-3 col-md-4 col-sm-6 mb-3">
                                <label class="font-weight-bold text-dark font-size-sm">Motorista</label>
                                <select name="motorista_id" class="form-control form-control-solid">
                                    <option value="">Todos os Motoristas</option>
                                    @foreach($listaMotoristas as $f)
                                        <option value="{{ $f->id }}" {{ request()->get('motorista_id') == $f->id ? 'selected' : '' }}>
                                            {{ $f->nome }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Filtro por Status -->
                            <div class="form-group col-lg-2 col-md-4 col-sm-6 mb-3">
                                <label class="font-weight-bold text-dark font-size-sm">Status</label>
                                <select name="status" class="form-control form-control-solid">
                                    <option value="">Todos</option>
                                    <option value="agendado" {{ request()->get('status') == 'agendado' ? 'selected' : '' }}>Agendado</option>
                                    <option value="iniciado" {{ request()->get('status') == 'iniciado' ? 'selected' : '' }}>Iniciado</option>
                                    <option value="finalizado" {{ request()->get('status') == 'finalizado' ? 'selected' : '' }}>Finalizado</option>
                                    <option value="cancelado" {{ request()->get('status') == 'cancelado' ? 'selected' : '' }}>Cancelado</option>
                                </select>
                            </div>

                            <!-- Filtro Data Início -->
                            <div class="form-group col-lg-2 col-md-4 col-sm-6 mb-3">
                                <label class="font-weight-bold text-dark font-size-sm">Data Início</label>
                                <input type="date" name="data_inicio" class="form-control form-control-solid" value="{{ request()->get('data_inicio', '') }}">
                            </div>

                            <!-- Botões de Ação -->
                            <div class="col-lg-2 col-md-4 col-sm-12 mb-3">
                                <button type="submit" class="btn btn-primary font-weight-bold px-3 mr-1">
                                    <i class="la la-search"></i> Filtrar
                                </button>
                                <a href="{{ $filterUrl }}" class="btn btn-secondary font-weight-bold px-2">
                                    <i class="la la-redo"></i> Limpar
                                </a>
                            </div>

                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabela de Registros Profissional -->
            <div class="row @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">
                <div class="col-12">
                    <div class="table-responsive">
                        <table class="table table-hover table-vertical-center border-0">
                            <thead class="bg-gray-100 text-uppercase font-size-sm font-weight-bolder text-muted">
                            <tr>
                                @foreach($headers as $header)
                                    <th class="pl-4">{{ $header }}</th>
                                @endforeach
                                <th class="text-center" style="min-width: 160px;">Ações</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($records as $record)
                                <tr>
                                    @foreach($fields as $field)
                                        <td class="pl-4 font-size-sm">
                                            @if($field == 'status_formatado')
                                                @php
                                                    $statusVal = strtolower($record->status ?? '');
                                                    $badgeClass = match($statusVal) {
                                                        'agendado' => 'badge-warning text-dark',
                                                        'iniciado' => 'badge-primary',
                                                        'finalizado', 'concluida' => 'badge-success',
                                                        'cancelado' => 'badge-danger',
                                                        default => 'badge-secondary'
                                                    };
                                                @endphp
                                                <span class="badge badge-status {{ $badgeClass }}">
                                                    {{ ucfirst($record->status ?? 'N/A') }}
                                                </span>
                                            @else
                                                <span class="text-dark-75 font-weight-bold">{{ data_get($record, $field) ?: 'N/A' }}</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center">
                                            <!-- Botão de Enviar Link do Checklist Manualmente -->
                                            <a href="/movimentacaoVeiculo/enviar-checklist/{{ $record->id }}" class="btn btn-icon btn-light-success btn-sm mr-1" title="Enviar Link do Checklist via WhatsApp">
                                                <i class="la la-whatsapp"></i>
                                            </a>

                                            <!-- Botão de Ver Respostas do Checklist -->
                                            <a href="/movimentacaoVeiculo/ver-checklist/{{ $record->id }}" class="btn btn-icon btn-light-primary btn-sm mr-1" title="Ver Respostas e Fotos do Checklist">
                                                <i class="la la-clipboard-check"></i>
                                            </a>

                                            <a href="/movimentacaoVeiculo/imprimir/{{ $record->id }}" target="_blank" class="btn btn-icon btn-light-info btn-sm mr-1" title="Imprimir">
                                                <i class="la la-print"></i>
                                            </a>
                                            <a href="{{ $editUrl }}/{{ $record->id }}" class="btn btn-icon btn-light-warning btn-sm mr-1" title="Editar">
                                                <i class="la la-edit"></i>
                                            </a>
                                            <a onclick="if(confirm('Deseja realmente excluir esta movimentação?')) { window.location.href = '{{ $deleteUrl }}/{{ $record->id }}' }" class="btn btn-icon btn-light-danger btn-sm" title="Excluir" style="cursor: pointer;">
                                                <i class="la la-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($headers) + 1 }}" class="text-center py-5 text-muted font-weight-bold">
                                        Nenhuma movimentação encontrada para o período ou filtros selecionados.
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Paginação -->
            @if(isset($links) && method_exists($records, 'links'))
                <div class="d-flex justify-content-between align-items-center flex-wrap mt-4">
                    <div class="d-flex flex-wrap py-2 mr-3">
                        {{ $links }}
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
