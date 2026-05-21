@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
    <div class="card-header border-0 pt-6">
        <h3 class="card-title align-items-start flex-column">
            <span class="card-label font-weight-bolder font-size-h3 text-dark">Lista de Ordens de Serviço (Ótica)</span>
        </h3>
        <div class="card-toolbar">
            <a href="{{ route('otica.create') }}" class="btn btn-success font-weight-bolder">
                <i class="fa fa-plus"></i> Nova OS / Receita
            </a>
        </div>
    </div>
    
    <div class="card-body">
        <form method="POST" action="{{ route('otica.list') }}" class="mb-7">
            @csrf
            <div class="row align-items-center bg-light p-4 rounded">
                <div class="col-md-4 form-group mb-0">
                    <label class="font-weight-bold">Pesquisar Cliente</label>
                    <input type="text" name="cliente" class="form-control" placeholder="Nome ou CPF..." value="{{ $cliente ?? '' }}">
                </div>
                <div class="col-md-4 form-group mb-0">
                    <label class="font-weight-bold">Filtrar por Status</label>
                    <select name="status" class="form-control">
                        <option value="">Todos</option>
                        <option value="orcamento">Orçamento / Aguardando</option>
                        <option value="pendente">Aguardando Laboratório</option>
                        <option value="laboratorio">Em Produção no Lab.</option>
                        <option value="conferencia">Conferência / Qualidade</option>
                        <option value="pronto">Pronto para Retirada</option>
                        <option value="entregue">Entregue / Faturado</option>
                    </select>
                </div>
                <div class="col-md-4 form-group mb-0 text-right">
                    <button type="submit" class="btn btn-primary font-weight-bold mt-7 px-8">
                        <i class="fa fa-search"></i> Filtrar
                    </button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-head-custom table-vertical-center table-bordered table-hover">
                <thead class="thead-light">
                    <tr>
                        <th width="80" class="text-center">ID</th>
                        <th>Cliente</th>
                        <th class="text-center">Data</th>
                        <th class="text-center">Status da OS</th>
                        <th class="text-right">Total (R$)</th>
                        <th width="280" class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data as $item)
                    <tr>
                        <td class="text-center font-weight-bold text-muted">#{{ $item->id }}</td>
                        <td>
                            <span class="text-dark-75 font-weight-bolder d-block font-size-lg">{{ $item->cliente->razao_social ?? 'Não Identificado' }}</span>
                        </td>
                        <td class="text-center font-weight-bold">{{ \Carbon\Carbon::parse($item->data)->format('d/m/Y') }}</td>
                        <td class="text-center">
                            <select class="form-control form-control-sm status-rapido font-weight-bold" data-id="{{ $item->id }}" 
                                    style="{{ $item->status == 'entregue' ? 'background-color: #c9f7f5; color: #1bc5bd; border-color: #1bc5bd;' : 'background-color: #f3f6f9; color: #3f4254;' }}">
                                <option value="orcamento" {{ $item->status == 'orcamento' ? 'selected' : '' }}>Orçamento</option>
                                <option value="pendente" {{ $item->status == 'pendente' ? 'selected' : '' }}>Aguardando Lab.</option>
                                <option value="laboratorio" {{ $item->status == 'laboratorio' ? 'selected' : '' }}>Em Produção</option>
                                <option value="conferencia" {{ $item->status == 'conferencia' ? 'selected' : '' }}>Conferência</option>
                                <option value="pronto" {{ $item->status == 'pronto' ? 'selected' : '' }}>Pronto</option>
                                <option value="entregue" disabled {{ $item->status == 'entregue' ? 'selected' : '' }}>Entregue / Faturado</option>
                            </select>
                        </td>
                        <td class="text-right font-weight-bolder text-success font-size-lg">
                            R$ {{ number_format(($item->valor_lente ?? 0) + ($item->valor_armacao ?? 0), 2, ',', '.') }}
                        </td>
                        <td class="text-center">
                            <a href="{{ route('otica.edit', $item->id) }}" class="btn btn-icon btn-light-primary btn-sm mr-1" title="Editar OS">
                                <i class="fa fa-edit"></i>
                            </a>

                            <a href="{{ route('otica.imprimirOS', $item->id) }}" target="_blank" class="btn btn-icon btn-light-dark btn-sm mr-1" title="Imprimir OS Técnica (Laboratório)">
                                <i class="fa fa-print"></i>
                            </a>

                            <a href="{{ route('otica.imprimirRecibo', $item->id) }}" target="_blank" class="btn btn-icon btn-light-info btn-sm mr-1" title="Imprimir Recibo do Cliente">
                                <i class="fa fa-file-text-o"></i>
                            </a>

                            @if($item->status != 'entregue')
                                <a href="{{ route('otica.faturar', ['id' => $item->id, 'tipo' => 'pdv']) }}" class="btn btn-icon btn-light-success btn-sm mr-1" title="Venda Rápida PDV (NFC-e)" onclick="return confirm('Deseja enviar para a Frente de Caixa (PDV)?')">
                                    <i class="fa fa-shopping-cart"></i>
                                </a>

                                <a href="{{ route('otica.faturar', ['id' => $item->id, 'tipo' => 'nfe']) }}" class="btn btn-icon btn-light-warning btn-sm mr-1" title="Venda Completa (NF-e)" onclick="return confirm('Deseja enviar para as Vendas (NF-e)?')">
                                    <i class="fa fa-file-text"></i>
                                </a>
                            @else
                                <span class="label label-light-success label-inline font-weight-bold ml-1 mr-1 px-3">Faturado</span>
                            @endif

                            <a href="/otica/delete/{{ $item->id }}" class="btn btn-icon btn-light-danger btn-sm" onclick="return confirm('Excluir esta OS definitivamente?')" title="Excluir OS">
                                <i class="fa fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between align-items-center flex-wrap mt-5">{{ $data->links() }}</div>
    </div>
</div>
@endsection

@section('javascript')
<script>
$('.status-rapido').change(function() {
    let select = $(this);
    $.post("{{ route('otica.alterarStatus') }}", {
        _token: "{{ csrf_token() }}", id: select.data('id'), status: select.val()
    }, function(res) {
        if(res.success) {
            // Pequeno feedback visual ao alterar status
            select.css('background-color', '#e1f0ff').animate({ backgroundColor: "#f3f6f9" }, 1000);
        } else {
            alert('Erro ao atualizar status.');
        }
    });
});
</script>
@endsection