@extends('default.layout')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $formTitle }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('compraFiscal.difal.create') }}" class="btn btn-success btn-sm">
                            <i class="fa fa-plus"></i> Nova Apuração
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Competência</th>
                                    <th>Período</th>
                                    <th>Valor Total</th>
                                    <th>Status</th>
                                    <th>Financeiro</th>
                                    <th width="150">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data as $item)
                                <tr>
                                    <td>{{ $item->id }}</td>
                                    <td><strong>{{ $item->referencia }}</strong></td>
                                    <td>{{ date('d/m/Y', strtotime($item->data_inicial)) }} até {{ date('d/m/Y', strtotime($item->data_final)) }}</td>
                                    <td>R$ {{ number_format($item->valor_total_difal, 2, ',', '.') }}</td>
                                    <td>
                                        <span class="badge {{ $item->status == 'aberta' ? 'badge-warning' : 'badge-success' }}">
                                            {{ strtoupper($item->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($item->contas_a_pagar_id)
                                            <span class="text-success"><i class="fa fa-check"></i> Gerado (#{{ $item->contas_a_pagar_id }})</span>
                                        @else
                                            <span class="text-muted">Pendente</span>
                                        @endif
                                    </td>
                                    <td>
                                      <div class="btn-group">
                                          <button title="Ver Memória de Cálculo" class="btn btn-info btn-sm" onclick="verDetalhes({{ $item->id }})">
                                              <i class="fa fa-list"></i>
                                          </button>

                                          <a href="{{ url('compras/apuracao-difal/imprimir') }}/{{ $item->id }}" target="_blank" class="btn btn-default btn-sm" title="Imprimir Relatório">
                                              <i class="fa fa-print"></i>
                                          </a>

                                          <button title="Cancelar" class="btn btn-danger btn-sm" onclick="cancelarApuracao({{ $item->id }})">
                                              <i class="fa fa-trash"></i>
                                          </button>
                                      </div>
                                  </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDetalhes" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Memória de Cálculo - Detalhes da Apuração</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="conteudoDetalhes">
                </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
    function cancelarApuracao(id) {
        if(confirm('Deseja realmente cancelar esta apuração? O lançamento financeiro também será removido.')) {
            $.ajax({
                url: "{{ url('compras/apuracao-difal/cancelar') }}/" + id,
                type: 'GET', // No seu roteiro colocamos GET para facilitar, mas o ideal seria DELETE
                success: function(res) {
                    location.reload();
                },
                error: function(xhr) {
                    alert(xhr.responseJSON.message);
                }
            });
        }
    }

    function verDetalhes(id) {
    $('#modalDetalhes').modal('show');
    $('#conteudoDetalhes').html('<p class="text-center"><i class="fa fa-spinner fa-spin"></i> Carregando dados...</p>');
    
    // Faz a busca do conteúdo formatado
    $.get("{{ url('compras/apuracao-difal/detalhes') }}/" + id, function(data) {
        $('#conteudoDetalhes').html(data);
    });
}
</script>
@endsection