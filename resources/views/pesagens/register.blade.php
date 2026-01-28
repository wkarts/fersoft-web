@extends('default.layout')

@section('content')
    <style type="text/css">
        #focus-codigo:hover {
            cursor: pointer;
        }

        .search-prod {
            position: absolute;
            top: 0;
            margin-top: 40px;
            left: 10;
            width: 100%;
            max-height: 200px;
            overflow: auto;
            z-index: 9999;
            border: 1px solid #eeeeee;
            border-radius: 4px;
            background-color: #fff;
            box-shadow: 0px 1px 6px 1px rgba(0, 0, 0, 0.4);
        }

        .search-prod label:hover {
            cursor: pointer;
        }

        .search-prod label {
            margin-left: 10px;
            width: 100%;
            margin-top: 7px;
            font-size: 14px;
            color: #000 !important;
        }
    </style>
    <div class="card card-custom gutter-b">
        <div class="card-body">
            <div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
                <div class="col-sm-12 col-lg-4 col-md-6 col-xl-4">
                    <a href="{{ route('pesagens.register') }}" class="btn btn-lg btn-success">
                        <i class="fa fa-plus"></i> Nova Pesagem
                    </a>
                </div>
            </div>
            <br>

            <div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
                <h4>Filtros</h4>
                <form method="get" action="{{ route('pesagens.list') }}">
                    <div class="row align-items-center">
                        <div class="form-group col-lg-3 col-md-6">
                            <label>Data Inicial</label>
                            <input type="datetime-local" name="data_inicial" class="form-control" value="{{ old('data_inicial', $dataInicial ?? '') }}">
                        </div>
                        <div class="form-group col-lg-3 col-md-6">
                            <label>Data Final</label>
                            <input type="datetime-local" name="data_final" class="form-control" value="{{ old('data_final', $dataFinal ?? '') }}">
                        </div>
                        <div class="form-group col-lg-3 col-md-6">
                            <label>Veículo</label>
                            <select name="veiculo_id" class="form-control select2">
                                <option value="">Selecione</option>
                                @foreach($veiculos as $veiculo)
                                    <option value="{{ $veiculo->id }}" {{ old('veiculo_id', $veiculo_id ?? '') == $veiculo->id ? 'selected' : '' }}>
                                        {{ $veiculo->placa }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-lg-3 col-md-6">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="">Todos</option>
                                <option value="em andamento" {{ old('status', $status ?? '') == 'em andamento' ? 'selected' : '' }}>Em Andamento</option>
                                <option value="concluído" {{ old('status', $status ?? '') == 'concluído' ? 'selected' : '' }}>Concluído</option>
                            </select>
                        </div>
                        <div class="col-lg-3 mt-3">
                            <button class="btn btn-primary w-100" type="submit">Filtrar</button>
                        </div>
                    </div>
                </form>
                <hr>

                <h4>Lista de Pesagens</h4>
                <label>Total de registros: {{ count($pesagens) }}</label>
                <div class="row">
                    <div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
                        <div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">
                            <table class="datatable-table" style="max-width: 100%; overflow: scroll">
                                <thead class="datatable-head">
                                <tr class="datatable-row" style="left: 0px;">
                                    <th>ID</th>
                                    <th>Data</th>
                                    <th>Veículo</th>
                                    <th>Peso</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                                </thead>
                                <tbody id="body" class="datatable-body">
                                @forelse($pesagens as $pesagem)
                                    <tr class="datatable-row">
                                        <td class="datatable-cell">{{ $pesagem->id }}</td>
                                        <td class="datatable-cell">{{ \Carbon\Carbon::parse($pesagem->created_at)->format('d/m/Y H:i') }}</td>
                                        <td class="datatable-cell">{{ $pesagem->veiculo->placa ?? 'N/A' }}</td>
                                        <td class="datatable-cell">{{ number_format($pesagem->peso, 2, ',', '.') }} kg</td>
                                        <td class="datatable-cell">
                                            <span class="badge {{ $pesagem->status == 'concluído' ? 'badge-success' : 'badge-warning' }}">
                                                {{ ucfirst($pesagem->status) }}
                                            </span>
                                        </td>
                                        <td class="datatable-cell">
                                            <a href="{{ route('pesagens.edit', $pesagem->id) }}" class="btn btn-warning btn-sm">
                                                <i class="la la-edit"></i>
                                            </a>
                                            <form action="{{ route('pesagens.delete', $pesagem->id) }}" method="POST" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="la la-trash"></i>
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#modalTickets{{ $pesagem->id }}">
                                                <i class="fa fa-ticket"></i> Tickets
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Modal Tickets -->
                                    <div class="modal fade" id="modalTickets{{ $pesagem->id }}" tabindex="-1" role="dialog" aria-labelledby="modalTicketsLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-lg" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header bg-primary text-white">
                                                    <h5 class="modal-title" id="modalTicketsLabel">Tickets - Pesagem #{{ $pesagem->id }}</h5>
                                                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <ul class="list-group">
                                                        @forelse($pesagem->tickets as $ticket)
                                                            <li class="list-group-item">
                                                                <strong>Tipo:</strong> {{ ucfirst($ticket->tipo) }} -
                                                                <strong>Peso:</strong> {{ number_format($ticket->peso, 2, ',', '.') }} kg
                                                                <span class="float-right">
                                                                    <button class="btn btn-warning btn-sm">Editar</button>
                                                                    <button class="btn btn-danger btn-sm">Excluir</button>
                                                                </span>
                                                            </li>
                                                        @empty
                                                            <li class="list-group-item text-center">Nenhum ticket encontrado</li>
                                                        @endforelse
                                                    </ul>
                                                    <hr>
                                                    <form method="POST" action="{{ route('ticketsPesagem.save') }}">
                                                        @csrf
                                                        <input type="hidden" name="pesagem_id" value="{{ $pesagem->id }}">
                                                        <div class="form-group">
                                                            <label for="peso">Peso (kg):</label>
                                                            <input type="number" name="peso" step="0.01" class="form-control" required>
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="tipo">Tipo:</label>
                                                            <select name="tipo" class="form-control" required>
                                                                <option value="entrada">Entrada</option>
                                                                <option value="saida">Saída</option>
                                                                <option value="avulsa">Avulsa</option>
                                                            </select>
                                                        </div>
                                                        <button type="submit" class="btn btn-primary">Adicionar Ticket</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Fim Modal Tickets -->
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">Nenhuma pesagem encontrada.</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div class="d-flex flex-wrap py-2 mr-3">
                        {{ $pesagens->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
