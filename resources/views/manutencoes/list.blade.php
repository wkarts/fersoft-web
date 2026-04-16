@extends('default.layout')
@section('content')
    <div class="card card-custom gutter-b">
        <div class="card-body">
            <div class="container-fluid">
                
                {{-- CABEÇALHO --}}
                <div class="row align-items-center pb-5">
                    <div class="col-lg-6">
                        <h3 class="card-title">Gestão de Manutenções</h3>
                    </div>
                    <div class="col-lg-6 text-right">
                        <div class="btn-group mr-4" role="group">
                            <button type="button" onclick="changeView('table')" id="btn-view-table" class="btn btn-light-primary btn-sm active">
                                <i class="la la-list"></i> Tabela
                            </button>
                            <button type="button" onclick="changeView('grid')" id="btn-view-grid" class="btn btn-light-primary btn-sm">
                                <i class="la la-th-large"></i> Grade
                            </button>
                        </div>

                        <a href="{{ $newItemUrl }}" class="btn btn-primary font-weight-bolder">
                            <i class="la la-plus"></i> Nova Manutenção
                        </a>
                    </div>
                </div>

                {{-- FORMULÁRIO DE FILTROS --}}
                <form method="get" action="/manutencoes" class="mb-7 bg-light-secondary p-5 rounded">
                    <div class="row align-items-end">
                        <div class="form-group col-lg-3 col-md-4">
                            <label class="font-weight-bold">Veículo (Placa)</label>
                            <input type="text" name="veiculo" class="form-control" value="{{ request('veiculo') }}" placeholder="Ex: ABC-1234">
                        </div>

                        <div class="form-group col-lg-2 col-md-4">
                            <label class="font-weight-bold">Status</label>
                            <select name="status" class="form-control">
                                <option value="">Todos</option>
                                <option value="Aguardando Aprovação" @if(request('status') == 'Aguardando Aprovação') selected @endif>Aguardando</option>
                                <option value="Em Manutenção" @if(request('status') == 'Em Manutenção') selected @endif>Em Manutenção</option>
                                <option value="Finalizado" @if(request('status') == 'Finalizado') selected @endif>Finalizado</option>
                            </select>
                        </div>

                        <div class="form-group col-lg-3 col-md-4">
                            <button type="submit" class="btn btn-primary font-weight-bold">
                                <i class="la la-search"></i> Filtrar
                            </button>
                            <a href="/manutencoes" class="btn btn-light-danger font-weight-bold ml-2">Limpar</a>
                        </div>
                    </div>
                </form>

                {{-- 1. VISUALIZAÇÃO EM TABELA --}}
                <div id="view-table" class="row">
                    <div class="col-xl-12">
                        <div class="table-responsive">
                            <table class="table table-head-custom table-vertical-center">
                                <thead>
                                    <tr class="text-left">
                                        <th>ID</th>
                                        <th>Veículo</th>
                                        <th>Mecânico</th>
                                        <th>Data</th>
                                        <th>Status</th>
                                        <th>Total</th>
                                        <th class="text-right">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($records as $record)
                                        @php 
                                            $corS = 'warning';
                                            if($record->status == 'Em Manutenção') $corS = 'info';
                                            if($record->status == 'Finalizado' || $record->status == 'Concluída') $corS = 'success';
                                        @endphp
                                        <tr>
                                            <td><span class="text-dark-75 font-weight-bolder">#{{ $record->manutencao_id }}</span></td>
                                            <td>
                                                <div class="font-weight-bolder">{{ $record->veiculo->placa ?? 'N/A' }}</div>
                                                <div class="text-muted small">{{ $record->veiculo->marca ?? '' }}</div>
                                            </td>
                                            {{-- MOSTRA O NOME DO MECÂNICO --}}
                                            <td>{{ $record->responsavel->nome ?? 'Não informado' }}</td>
                                            
                                            <td>{{ $record->data_manutencao ? \Carbon\Carbon::parse($record->data_manutencao)->format('d/m/Y') : '--' }}</td>
                                            
                                            <td><span class="label label-light-{{ $corS }} label-inline font-weight-bold">{{ $record->status }}</span></td>
                                            
                                            <td class="font-weight-bold">R$ {{ number_format($record->custo, 2, ',', '.') }}</td>
                                            
                                            <td class="text-right text-nowrap">
                                                {{-- BOTÃO FINALIZAR --}}
                                                @if($record->status != 'Finalizado' && $record->status != 'Concluída')
                                                    <a href="/manutencoes/finalizar/{{ $record->manutencao_id }}" class="btn btn-icon btn-light-info btn-sm mr-2" title="Finalizar Manutenção" onclick="return confirm('Confirmar conclusão e saída do veículo?')">
                                                        <i class="la la-check-double"></i>
                                                    </a>
                                                @endif
                                                
                                                {{-- BOTÃO IMPRIMIR --}}
                                                <a href="/manutencoes/imprimir/{{ $record->manutencao_id }}" target="_blank" class="btn btn-icon btn-light-dark btn-sm mr-2" title="Imprimir OS">
                                                    <i class="la la-print"></i>
                                                </a>

                                                {{-- BOTÃO EDITAR --}}
                                                <a href="/manutencoes/edit/{{ $record->manutencao_id }}" class="btn btn-icon btn-light-warning btn-sm mr-2" title="Editar">
                                                    <i class="la la-edit"></i>
                                                </a>

                                                {{-- BOTÃO EXCLUIR --}}
                                                <a href="javascript:;" onclick="if(confirm('Deseja realmente excluir este registro?')) { window.location.href = '/manutencoes/delete/{{ $record->manutencao_id }}' }" class="btn btn-icon btn-light-danger btn-sm" title="Excluir">
                                                    <i class="la la-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="text-center text-muted py-5">Nenhum lançamento encontrado.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- 2. VISUALIZAÇÃO EM GRADE --}}
                <div id="view-grid" class="row d-none">
                    @forelse($records as $record)
                        @php 
                            $corSG = 'warning';
                            if($record->status == 'Em Manutenção') $corSG = 'info';
                            if($record->status == 'Finalizado' || $record->status == 'Concluída') $corSG = 'success';
                        @endphp
                        <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12">
                            <div class="card card-custom gutter-b shadow-sm border">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-3">
                                        <span class="label label-light-{{ $corSG }} label-inline font-weight-bold">{{ $record->status }}</span>
                                        <span class="text-muted">#{{ $record->manutencao_id }}</span>
                                    </div>
                                    <div class="d-flex align-items-center mb-4">
                                        <div class="symbol symbol-40 symbol-light-info mr-3">
                                            <span class="symbol-label"><i class="la la-car-side text-info icon-lg"></i></span>
                                        </div>
                                        <div class="d-flex flex-column">
                                            <span class="text-dark font-weight-boldest font-size-h5">{{ $record->veiculo->placa ?? 'N/A' }}</span>
                                            <span class="text-muted">{{ $record->veiculo->marca ?? '' }}</span>
                                        </div>
                                    </div>
                                    <div class="separator separator-dashed mb-4"></div>
                                    <div class="mt-4 text-right">
                                        @if($record->status != 'Finalizado')
                                            <a href="/manutencoes/finalizar/{{ $record->manutencao_id }}" class="btn btn-info btn-sm mr-2" onclick="return confirm('Finalizar?')">Finalizar</a>
                                        @endif
                                        <a href="/manutencoes/imprimir/{{ $record->manutencao_id }}" target="_blank" class="btn btn-light-dark btn-sm mr-2"><i class="la la-print"></i></a>
                                        <a href="/manutencoes/edit/{{ $record->manutencao_id }}" class="btn btn-light-warning btn-sm">Editar</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center text-muted py-10">Nenhum lançamento encontrado.</div>
                    @endforelse
                </div>

                {{-- PAGINAÇÃO --}}
                <div class="d-flex justify-content-center py-5">
                    @if(isset($links)) {!! $links !!} @endif
                </div>

            </div>
        </div>
    </div>

    <script>
        function changeView(type) {
            if (type === 'grid') {
                document.getElementById('view-table').classList.add('d-none');
                document.getElementById('view-grid').classList.remove('d-none');
                document.getElementById('btn-view-grid').classList.add('active');
                document.getElementById('btn-view-table').classList.remove('active');
            } else {
                document.getElementById('view-grid').classList.add('d-none');
                document.getElementById('view-table').classList.remove('d-none');
                document.getElementById('btn-view-table').classList.add('active');
                document.getElementById('btn-view-grid').classList.remove('active');
            }
        }
    </script>
@endsection