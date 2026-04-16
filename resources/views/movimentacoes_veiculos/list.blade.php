@extends('default.layout')
@section('content')
<style>
    /* Remove a quebra de linha e permite scroll se a tabela for grande */
    .datatable-table {
        display: block !important;
        width: 100% !important;
        overflow-x: auto !important;
    }

    /* Garante que as células fiquem lado a lado, não empilhadas */
    .datatable-row {
        display: flex !important;
        flex-direction: row !important;
        width: 100% !important;
        min-width: 1200px; /* Força uma largura mínima para as colunas não esmagarem */
    }

    .datatable-cell {
        flex: 1 !important; /* Faz todas as colunas terem tamanhos parecidos */
        display: flex !important;
        align-items: center !important;
        padding: 10px 5px !important;
    }

    /* Coluna de Ações: um pouco maior para os botões caberem lado a lado */
    .datatable-cell:last-child {
        flex: 0 0 180px !important; 
        justify-content: center;
    }
</style>

    <div class="card card-custom gutter-b">
        <div class="card-body">
            <div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
                <input type="hidden" id="_token" value="{{ csrf_token() }}">

                <!-- Filtros de Pesquisa -->
                <form class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft" method="get" action="{{ $filterUrl }}">
                    <div class="row align-items-center">
                        @foreach($filters as $filter)
                            <div class="form-group col-lg-3 col-sm-6">
                                <label class="col-form-label">{{ $filter['label'] }}</label>
                                <div>
                                    @if($filter['type'] === 'select')
                                        <select name="{{ $filter['name'] }}" class="custom-select">
                                            <option value="">{{ $filter['placeholder'] }}</option>
                                            @foreach($filter['options'] as $option)
                                                <option value="{{ $option['value'] }}" @if(request()->get($filter['name']) == $option['value']) selected @endif>
                                                    {{ $option['label'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @elseif($filter['type'] === 'date')
                                        <input type="date" name="{{ $filter['name'] }}" class="form-control" value="{{ request()->get($filter['name'], '') }}">
                                    @elseif($filter['type'] === 'text')
                                        <input type="text" name="{{ $filter['name'] }}" class="form-control" placeholder="{{ $filter['placeholder'] }}" value="{{ request()->get($filter['name'], '') }}">
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        <div class="col-lg-3 col-xl-2 mt-2 mt-lg-0">
                            <button style="margin-top: 15px;" class="btn btn-light-primary px-6 font-weight-bold">Filtrar</button>
                        </div>
                    </div>
                </form>

                <br>
                <h4 class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight">{{ $title }}</h4>
                <label class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight">Registros: <strong class="text-success">{{ count($records) }}</strong></label>
                <div class="row @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">
                    <div class="form-group col-lg-3 col-md-4 col-sm-6">
                        <a href="{{ $newItemUrl }}" class="btn btn-success">
                            <i class="la la-plus"></i>
                            {{ $newItemText }}
                        </a>
                    </div>
                </div>
            </div>

            <!-- Tabela de Registros -->
            <div class="row @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">
                <div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
                    <div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">
                        <table class="datatable-table" style="max-width: 100%; overflow: scroll">
                            <thead class="datatable-head">
                            <tr class="datatable-row" style="left: 0px;">
                                @foreach($headers as $header)
                                    <th class="datatable-cell datatable-cell-sort">
                                        <span>{{ $header }}</span>
                                    </th>
                                @endforeach
                                <th class="datatable-cell datatable-cell-sort">
                                    <span>Ações</span>
                                </th>
                            </tr>
                            </thead>
                            <tbody class="datatable-body">
                            @foreach($records as $record)
                                <tr class="datatable-row">
                                    @foreach($fields as $field)
    <td class="datatable-cell">
        <span>
            @if($field == 'status_formatado')
                {!! data_get($record, $field) !!}
            @else
                {{ data_get($record, $field) ?: 'N/A' }}
            @endif
        </span>
                           </td>
                             @endforeach
                                   <td class="datatable-cell">
    <a href="/movimentacaoVeiculo/imprimir/{{ $record->id }}" target="_blank" class="btn btn-info btn-sm mr-1">
        <i class="la la-print"></i> Imprimir
    </a>

    <a href="{{ $editUrl }}/{{ $record->id }}" class="btn btn-warning btn-sm mr-1">
        <i class="la la-edit"></i> Editar
    </a>
    
    <a onclick="if(confirm('Deseja realmente excluir?')) { window.location.href = '{{ $deleteUrl }}/{{ $record->id }}' }" class="btn btn-danger btn-sm">
        <i class="la la-trash"></i> Excluir
    </a>
</td>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Paginação -->
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div class="d-flex flex-wrap py-2 mr-3">
                    @if(isset($links))
                        {{ $links }}
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
