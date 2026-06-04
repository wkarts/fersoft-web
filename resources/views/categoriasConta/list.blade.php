@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
    <div class="card-body">
        <div class="animate__animated animate__backInLeft">
            <div class="col-sm-12 col-lg-4 col-md-6 col-xl-4">
                <a href="/categoriasConta/new" class="btn btn-lg btn-success">
                    <i class="fa fa-plus"></i> Nova Categoria
                </a>
            </div>
        </div>
        <br>
        
        <div class="animate__animated animate__backInRight" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
            <div class="row">
                @foreach($categorias as $c)
                <div class="col-sm-12 col-lg-6 col-md-6 col-xl-4">
                    <div class="card card-custom gutter-b example example-compact shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <h3 class="font-size-h4 text-dark-75 font-weight-bolder mb-0">{{$c->nome}}</h3>
                                <div class="d-flex">
                                    <a href="/categoriasConta/edit/{{$c->id}}" class="btn btn-icon btn-circle btn-sm btn-warning mr-1" title="Editar"><i class="la la-pencil"></i></a>
                                    <a onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/categoriasConta/delete/{{$c->id}}" }})' class="btn btn-icon btn-circle btn-sm btn-danger mr-1" title="Excluir"><i class="la la-trash"></i></a>
                                </div>
                            </div>
                            <hr>
                            
                            <div class="d-flex flex-column">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted font-weight-bold">Tipo:</span>
                                    <span class="label label-inline {{ $c->tipo == 'receber' ? 'label-light-success' : 'label-light-danger' }} font-weight-bold">
                                        {{ strtoupper($c->tipo) }}
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted font-weight-bold">Grupo DRE:</span>
                                    <span class="label label-inline label-light-primary font-weight-bold">
                                        {{ $c->dre_grupo ? (App\Models\CategoriaConta::gruposDRE()[$c->dre_grupo] ?? $c->dre_grupo) : 'Não Definido' }}
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted font-weight-bold">Apuração:</span>
                                    <span class="{{ $c->incluir_resultado ? 'text-success' : 'text-muted' }} font-weight-bold">
                                        <i class="la {{ $c->incluir_resultado ? 'la-check' : 'la-close' }}"></i> {{ $c->incluir_resultado ? 'No Resultado' : 'Ignorar' }}
                                    </span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted font-weight-bold">Conta Despesa:</span>
                                    <span class="text-dark font-weight-bold">{{ $c->contaDespesa ? $c->contaDespesa->classificador : '---' }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted font-weight-bold">Provisão:</span>
                                    <span class="font-weight-bold {{ $c->gera_provisao ? 'text-primary' : 'text-muted' }}">{{ $c->gera_provisao ? 'Ativa' : 'Desativada' }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted font-weight-bold">Terceiros:</span>
                                    <span class="font-weight-bold {{ $c->ignora_terceiro ? 'text-warning' : 'text-info' }}">{{ $c->ignora_terceiro ? 'Ignorar' : 'Obrigatório' }}</span>
                                </div>
                            </div>
                            </div>
                    </div>
                </div>
                @endforeach </div>
        </div>
    </div>
</div>
@endsection