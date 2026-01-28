@extends('default.layout')
@section('content')
    <div class="card card-custom gutter-b">

        <div class="card-body">

            <div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">

                <div class="col-12">
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
                    <h4>{{ $title }}</h4>
                    <label>Total de registros: {{ isset($records) ? count($records) : 0 }}</label>
                    <div class="row">

                    <a href="{{ $newItemUrl }}" class="btn btn-lg btn-success">
                        <i class="fa fa-plus"></i> {{ $newItemText }}
                    </a>

                </div>
            </div>
            <br>

            <div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">


                    <div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
                        <div class="wizard wizard-3" id="kt_wizard_v3" data-wizard-state="between" data-wizard-clickable="true">
                            <div class="wizard-nav">
                                <div class="wizard-steps px-8 py-8 px-lg-15 py-lg-3">
                                    <div class="wizard-step" data-wizard-type="step" data-wizard-state="done">
                                        <div class="wizard-label">
                                            <h3 class="wizard-title">
                                            <span>
                                                <i style="font-size: 40px" class="la la-table"></i>
                                                Tabela
                                            </span>
                                            </h3>
                                            <div class="wizard-bar"></div>
                                        </div>
                                    </div>
                                    <div class="wizard-step" data-wizard-type="step" data-wizard-state="current">
                                        <div class="wizard-label" id="grade">
                                            <h3 class="wizard-title">
                                            <span>
                                                <i style="font-size: 40px" class="la la-tablet"></i>
                                                Grade
                                            </span>
                                            </h3>
                                            <div class="wizard-bar"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="pb-5" data-wizard-type="step-content">
                                <div class="row">
                                    <div class="col-xl-12">
                                        <div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">
                                            <table class="datatable-table" style="max-width: 100%; overflow: scroll">
                                                <thead class="datatable-head">
                                                <tr class="datatable-row" style="left: 0px;">
                                                    @foreach($headers as $header)
                                                        <th class="datatable-cell datatable-cell-sort">
                                                            <span style="width: 250px;">{{ $header }}</span>
                                                        </th>
                                                    @endforeach
                                                    <th class="datatable-cell datatable-cell-sort">
                                                        <span style="width: 200px;">AÇÕES</span>
                                                    </th>
                                                </tr>
                                                </thead>
                                                <tbody id="body" class="datatable-body">
                                                @foreach($records as $record)
                                                    <tr class="datatable-row">
                                                        @foreach($fields as $field)
                                                            <td class="datatable-cell">
                                                                <span class="codigo" style="width: 250px;" id="{{ $field }}">
                                                                    @if($field === 'ativo')
                                                                        {{ $record->$field ? 'Sim' : 'Não' }}
                                                                    @else
                                                                        {{ $record->$field ?? 'N/A' }}
                                                                    @endif
                                                                </span>
                                                            </td>
                                                        @endforeach
                                                        <td class="datatable-cell">
                                                        <span class="codigo" style="width: 200px;" id="acoes">
                                                            <a class="btn btn-warning" onclick='swal("Atenção!", "Deseja editar este registro?", "warning").then((sim) => {if(sim){ location.href="{{ $editUrl }}/{{ $record->id }}" }else{return false} })' href="#!">
                                                                <i class="la la-edit"></i>
                                                            </a>
                                                            <a class="btn btn-danger" onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="{{ $deleteUrl }}/{{ $record->id }}" }else{return false} })' href="#!">
                                                                <i class="la la-trash"></i>
                                                            </a>
                                                        </span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="pb-5" data-wizard-type="step-content">
                                <div class="row">
                                    @foreach($records as $record)
                                        <div class="col-sm-12 col-lg-6 col-md-6 col-xl-4">
                                            <div class="card card-custom gutter-b example example-compact">
                                                <div class="card-header">
                                                    <div class="card-title">
                                                        <h3 style="width: 230px; font-size: 12px; height: 10px;" class="card-title">{{ substr($record->{$fields[0]}, 0, 30) }}</h3>
                                                    </div>
                                                    <div class="card-toolbar">
                                                        <div class="dropdown dropdown-inline" data-toggle="tooltip" title="" data-placement="left" data-original-title="Ações">
                                                            <a href="#" class="btn btn-hover-light-primary btn-sm btn-icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                <i class="fa fa-ellipsis-h"></i>
                                                            </a>
                                                            <div class="dropdown-menu p-0 m-0 dropdown-menu-md dropdown-menu-right">
                                                                <ul class="navi navi-hover">
                                                                    <li class="navi-header font-weight-bold py-4">
                                                                        <span class="font-size-lg">Ações:</span>
                                                                    </li>
                                                                    <li class="navi-separator mb-3 opacity-70"></li>
                                                                    <li class="navi-item">
                                                                        <a href="{{ $editUrl }}/{{ $record->id }}" class="navi-link">
                                                                        <span class="navi-text">
                                                                            <span class="label label-xl label-inline label-light-primary">Editar</span>
                                                                        </span>
                                                                        </a>
                                                                    </li>
                                                                    <li class="navi-item">
                                                                        <a onclick="if (! confirm('Deseja excluir este registro?')) { return false; }" href="{{ $deleteUrl }}/{{ $record->id }}" class="navi-link">
                                                                        <span class="navi-text">
                                                                            <span class="label label-xl label-inline label-light-danger">Excluir</span>
                                                                        </span>
                                                                        </a>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    @foreach($fields as $field)
                                                        <div class="kt-widget__info">
                                                            <span class="kt-widget__label">{{ ucfirst($field) }}:</span>
                                                            <a class="kt-widget__data text-success">
                                                                @if($field === 'ativo')
                                                                    {{ $record->$field ? 'Sim' : 'Não' }}
                                                                @else
                                                                    {{ $record->$field ?? 'N/A' }}
                                                                @endif
                                                            </a>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div class="d-flex flex-wrap py-2 mr-3">
                        @if(isset($links))
                            {{ $links }}
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
