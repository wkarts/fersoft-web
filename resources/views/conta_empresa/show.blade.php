@extends('default.layout', ['title' => 'Movimentações conta ' . $item->nome])

@section('content')
    <div class="card card-custom gutter-b">
        <div class="card-body">
            <div class="table-responsive">
                <form class="row mb-5" method="get" action="{{ route('contas-empresa.show', [$item->id]) }}">
                    <div class="col-md-2">
                        <label>Data inicial</label>
                        <input value="{{ $data_inicio ?? '' }}" type="date" name="data_inicio" class="form-control">
                    </div>

                    <div class="col-md-2">
                        <label>Data final</label>
                        <input value="{{ $data_final ?? '' }}" type="date" name="data_final" class="form-control">
                    </div>

                    <div class="col-md-2">
                        <label>Tipo</label>
                        <select name="tipo" class="form-control custom-select">
                            <option value="">Selecione</option>
                            <option @if(isset($tipo) && $tipo == 'entrada') selected @endif value="entrada">Entrada</option>
                            <option @if(isset($tipo) && $tipo == 'saida') selected @endif value="saida">Saída</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <br>
                        <button class="btn btn-light-primary px-6 font-weight-bold mt-1">
                            <i class="la la-search"></i> Filtrar
                        </button>
                        <a class="btn btn-warning px-6 font-weight-bold mt-1" href="{{ route('contas-empresa.show', [$item->id]) }}">
                            <i class="la la-eraser"></i> Limpar
                        </a>
                    </div>
                </form>

                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>Data</th>
                        <th>Descrição</th>
                        <th class="text-right">Entrada (+)</th>
                        <th class="text-right">Saída (-)</th>
                        <th class="text-right">Saldo</th>
                    </tr>
                    </thead>
                    <tbody>
                    {{-- Linha de Saldo Inicial --}}
                    <tr class="bg-light">
                        <td colspan="4"><strong>SALDO ANTERIOR AO PERÍODO</strong></td>
                        <td class="text-right"><strong>R$ {{ moeda($saldo_anterior) }}</strong></td>
                    </tr>

                    {{-- 1. Defina a variável antes do loop --}}
                    @php
                        $saldo_acumulado = $saldo_anterior;
                    @endphp

                    @forelse($data as $m)
                        @php
                            // 2. Calcula o saldo atual dinamicamente conforme o tipo
                            if($m->tipo == 'entrada') {
                                $saldo_acumulado += $m->valor;
                            } else {
                                $saldo_acumulado -= $m->valor;
                            }
                        @endphp

                        <tr>
                            {{-- 3. Data de pagamento ou criação --}}
                            <td>{{ __date($m->data_pagamento ?? $m->created_at) }}</td>

                            <td>{{ $m->descricao }}</td>

                            <td class="text-right text-success">
                                {{ $m->tipo == 'entrada' ? '+ R$ ' . moeda($m->valor) : '-' }}
                            </td>

                            <td class="text-right text-danger">
                                {{ $m->tipo == 'saida' ? '- R$ ' . moeda($m->valor) : '-' }}
                            </td>

                            <td class="text-right">
                                <span class="{{ $saldo_acumulado < 0 ? 'text-danger' : 'text-info' }}">
                                    R$ {{ moeda($saldo_acumulado) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">Nenhuma movimentação!</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="col-12 mt-5">
                {{ $data->appends(request()->all())->links() }}
            </div>
        </div>
    </div>
@endsection
