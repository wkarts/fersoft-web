@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="card card-custom gutter-b example example-compact">
        <div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
            <div class="col-lg-12">
                <br>

                <form method="post" action="{{{ isset($categoria) ? '/categoriasConta/update': '/categoriasConta/save' }}}" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="{{{ isset($categoria) ? $categoria->id : 0 }}}">
                    
                    <div class="card card-custom gutter-b example example-compact">
                        <div class="card-header">
                            <h3 class="card-title">{{isset($categoria) ? 'Editar' : 'Nova'}} Categoria de Conta</h3>
                        </div>
                    </div>
                    @csrf

                    <div class="row">
                        <div class="col-xl-1"></div>
                        <div class="col-xl-10">
                            <div class="kt-section kt-section--first">
                                <div class="kt-section__body">

                                    <div class="row">
                                        <div class="form-group validated col-sm-6 col-lg-5">
                                            <label class="col-form-label">Nome</label>
                                            <input type="text" class="form-control @if($errors->has('nome')) is-invalid @endif" name="nome" value="{{{ isset($categoria) ? $categoria->nome : old('nome') }}}">
                                            @if($errors->has('nome'))
                                            <div class="invalid-feedback">
                                                {{ $errors->first('nome') }}
                                            </div>
                                            @endif
                                        </div>
                                        
                                        <div class="form-group validated col-sm-6 col-lg-3">
                                            <label class="col-form-label">Tipo</label>
                                            <select class="custom-select" name="tipo">
                                                <option @if(isset($categoria) && $categoria->tipo == 'receber') selected @elseif(old('tipo') == 'receber') selected @endif value="receber">Receber</option>
                                                <option @if(isset($categoria) && $categoria->tipo == 'pagar') selected @elseif(old('tipo') == 'pagar') selected @endif value="pagar">A pagar</option>
                                            </select>
                                        </div>

                                        <div class="form-group validated col-sm-6 col-lg-4">
                                            <label class="col-form-label">Grupo na DRE (Resultado)</label>
                                            <select class="custom-select" name="dre_grupo">
                                                <option value="">Nenhum (Não classificado)</option>
                                                @foreach(App\Models\CategoriaConta::gruposDRE() as $key => $label)
                                                    <option value="{{ $key }}" 
                                                        @isset($categoria) @if($categoria->dre_grupo == $key) selected @endif @endisset>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <span class="form-text text-muted">Defina onde este valor aparece no relatório.</span>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group col-sm-12 col-lg-12">
                                            <label class="col-form-label">Configuração de Fechamento</label>
                                            <div class="checkbox-inline">
                                                <label class="checkbox checkbox-success">
                                                    <input type="checkbox" name="incluir_resultado" value="1" 
                                                        {{{ (isset($categoria) && $categoria->incluir_resultado) || !isset($categoria) ? 'checked' : '' }}}>
                                                    <span></span>
                                                    Contabilizar no Lucro/Prejuízo Mensal
                                                </label>
                                            </div>
                                            <p class="form-text text-muted">Se marcado, os lançamentos desta categoria serão somados na apuração mensal.</p>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-xl-2"></div>
                            <div class="col-lg-3 col-sm-6 col-md-4">
                                <a style="width: 100%" class="btn btn-danger" href="/categoriasConta">
                                    <i class="la la-close"></i>
                                    <span class="">Cancelar</span>
                                </a>
                            </div>
                            <div class="col-lg-3 col-sm-6 col-md-4">
                                <button style="width: 100%" type="submit" class="btn btn-success">
                                    <i class="la la-check"></i>
                                    <span class="">Salvar</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection