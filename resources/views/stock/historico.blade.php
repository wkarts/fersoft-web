@extends('default.layout')
@section('content')
<div class="card card-custom">
    <div class="card-header">
        <h3 class="card-title">Histórico de Movimentação: <strong>{{ $item->produto->nome }}</strong></h3>
        <div class="card-toolbar">
            <a href="/estoque" class="btn btn-secondary btn-sm"><i class="la la-arrow-left"></i> Voltar para o Estoque</a>
        </div>
    </div>
    <div class="card-body">
        
        <form method="get" action="/estoque/historico/{{ $item->id }}" class="mb-6">
            <div class="row align-items-end">
                <div class="col-lg-3">
                    <label>Data Inicial</label>
                    <input type="date" name="data_inicial" class="form-control" value="{{ $data_inicial }}">
                </div>
                <div class="col-lg-3">
                    <label>Data Final</label>
                    <input type="date" name="data_final" class="form-control" value="{{ $data_final }}">
                </div>
                <div class="col-lg-2">
                    <button class="btn btn-primary btn-block" type="submit"><i class="la la-search"></i> Filtrar</button>
                </div>
                <div class="col-lg-2">
                    <a href="/estoque/historico/{{ $item->id }}" class="btn btn-light-danger btn-block"><i class="la la-eraser"></i> Limpar</a>
                </div>
            </div>
        </form>

        <div class="table-responsive">
           <table class="table table-hover table-bordered">
                <thead>
                    <tr class="bg-light">
                        <th>Data/Hora</th>
                        <th>Utilizador</th>
                        <th>Tipo</th>
                        <th>Origem / Documento</th>
                        <th>Fornecedor / Cliente</th>
                        <th>Qtd Movimentada</th>
                        <th class="text-primary">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                      @forelse($movimentacoes as $m)
                      <tr>
                          <!-- ALTERADO: Exibindo a data real da movimentação (retroativa) em vez da data de criação do registro -->
                          <td>{{ date('d/m/Y H:i', strtotime($m->movimentado_em)) }}</td>

                          <td>
                              <span class="text-dark font-weight-bold">{{ $m->usuario_nome ?? 'Sistema' }}</span>
                          </td>

                          <td>
                              <span class="label label-inline font-weight-bold {{ $m->tipo == 'entrada' ? 'label-light-success' : 'label-light-danger' }}">
                                  {{ strtoupper($m->tipo) }}
                              </span>
                          </td>

                          <td>
                              <!-- ALTERADO: Exibindo a Origem vinda do banco e tratando o Ajuste Manual -->
                              <span class="text-dark-75 font-weight-bolder">
                                  {{ strtoupper($m->origem_tipo ?? 'AJUSTE MANUAL') }}
                              </span>

                              @if($m->origem_tipo == 'compra')
                                  <span class="text-muted ml-1">
                                      #{{ $m->compra_nf ?: ($m->compra_emissao ?: $m->origem_id) }}
                                  </span>
                              @elseif($m->origem_id) 
                                  <span class="text-muted ml-1">#{{ $m->origem_id }}</span> 
                              @endif
                              <br>
                              <span class="label label-inline label-light-info font-weight-bold mt-1" style="font-size: 0.75rem;">
                                  {{ $m->filial_nome ?? 'MATRIZ' }}
                              </span>

                              {{-- NOVO: Exibindo a observação/contexto se for um ajuste --}}
                              @if($m->contexto)
                                  <br><small class="text-muted">{{ $m->contexto }}</small>
                              @endif
                          </td>

                          <td>
                              @if($m->origem_tipo == 'compra')
                                  <span class="text-info font-weight-bold"><i class="la la-truck"></i> {{ $m->fornecedor_nome ?? 'Não identificado' }}</span>
                              @else
                                  <span class="text-muted">-</span>
                              @endif
                          </td>

                          <td class="font-weight-bold {{ $m->tipo == 'entrada' ? 'text-success' : 'text-danger' }}">
                              {{ $m->tipo == 'entrada' ? '+' : '-' }} {{ number_format($m->quantidade, 2, ',', '.') }}
                          </td>

                          <td class="font-weight-boldest text-primary h6">
                              {{ number_format($m->saldo_momento, 2, ',', '.') }}
                          </td>
                      </tr>
                      @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            Nenhuma movimentação registada para este período.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
    </div>
</div>
@endsection