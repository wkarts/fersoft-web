@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
    <div class="card-header">
        <h3 class="card-title">Importação de CT-e (Contas a Receber)</h3>
    </div>
    <div class="card-body">
        <form action="/importarCte/lote" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row">
                <div class="col-md-3">
                    <label class="font-weight-bold">Prazo de Vencimento</label>
                    <select name="prazo" class="form-control" required>
                        <option value="0">A Vista (Mesma data da nota)</option>
                        <option value="10">10 Dias</option>
                        <option value="15">15 Dias</option>
                        <option value="20">20 Dias</option>
                        <option value="30">30 Dias</option>
                    </select>
                    <small class="text-muted">Calculado a partir da emissão do CT-e.</small>
                </div>

                <div class="col-md-4">
                    <label class="font-weight-bold">Categoria da Conta</label>
                    <select name="categoria_id" class="form-control" required>
                        <option value="">Selecione a categoria...</option>
                        @foreach($categorias as $c)
                            <option value="{{ $c->id }}">{{ $c->nome }}</option>
                        @endforeach
                    </select>
                </div>
					
              		@if(empresaComFilial())
                    <div class="form-group col-lg-4 col-md-6 col-sm-12">
                        <label class="col-form-label font-weight-bold">Filial</label>
                        <select class="custom-select form-control" name="filial_id">
                            <option value="-1">Matriz (Sede)</option>
                            @foreach(__locaisAtivos() as $key => $nome)
                                <option value="{{ $key }}">{{ $nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
              	</div>
              
                <div class="col-md-5">
                    <label class="font-weight-bold">Arquivos XML</label>
                    <input type="file" name="xmls[]" multiple class="form-control" accept=".xml" required>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-success px-8">
                    <i class="la la-check"></i> Iniciar Importação
                </button>
            </div>
        </form>
    </div>
</div>
@endsection