@extends('default.layout', ['title' => 'SPED'])
@section('content')
<div class="card card-custom gutter-b">
    <div class="card-body">
        <br>
        <div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

            <h6 class="mb-0">Arquivo SPED</h6>
            <form method="post" action="{{ route('sped.store') }}" class="mt-3">
                @csrf
                <div class="row">
                    <div class="col-lg-2 col-6">
                        <label>Data inicial</label>
                        <input type="date" value="{{ $firstDate }}" name="data_inicial" class="form-control" required>
                    </div>
                    
                    <div class="col-lg-2 col-6">
                        <label>Data final</label>
                        <input type="date" value="{{ $lastDate }}" name="data_final" class="form-control" required>
                    </div>

                    <!-- NOVO CAMPO: SELEÇÃO DE MATRIZ / FILIAL -->
                    <div class="col-lg-3 col-12">
                        <label>Estabelecimento</label>
                        <select name="filial_id" class="form-control">
                            <option value="">Matriz</option>
                            @foreach($filiais as $f)
                                <!-- Ajuste $f->descricao para a coluna que tem o nome da sua filial (ex: razao_social, nome, etc) -->
                                <option value="{{ $f->id }}">Filial - {{ $f->descricao ?? $f->razao_social ?? $f->id }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 col-6 mt-3 mt-lg-0">
                        <label>Inventário</label>
                        <select id="inventario" name="inventario" class="form-control">
                            <option value="0">Não</option>
                            <option value="1">Sim</option>
                        </select>
                    </div>

                    <div class="col-lg-3 col-6 mt-3 mt-lg-0">
                        <label>Data de inventário</label>
                        <input type="date" name="data_inventario" class="form-control">
                    </div>

                    <div class="col-lg-4 col-12 mt-3">
                        <label>Motivo de inventário</label>
                        <select id="motivo_inventario" name="motivo_inventario" class="form-control">
                            @foreach(App\Models\Sped::motivosInventario() as $key => $m)
                                <option value="{{ $key }}">{{ $m }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 mt-3">
                        <label class="d-none d-md-block">&nbsp;</label>
                        <button class="btn btn-success w-100" type="submit"> 
                            Gerar arquivo
                        </button>
                    </div>
                </div>
            </form>
            <hr />
        </div>
    </div>
</div>
@endsection