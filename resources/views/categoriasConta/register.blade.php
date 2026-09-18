@extends('default.layout')
@section('content')
    <div class="d-flex flex-column flex-column-fluid" id="kt_content">
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
                                            </div>
                                            <div class="form-group validated col-sm-6 col-lg-3">
                                                <label class="col-form-label">Tipo</label>
                                                <select class="custom-select" name="tipo">
                                                    <option @if(isset($categoria) && $categoria->tipo == 'receber') selected @endif value="receber">Receber</option>
                                                    <option @if(isset($categoria) && $categoria->tipo == 'pagar') selected @endif value="pagar">A pagar</option>
                                                </select>
                                            </div>
                                            <div class="form-group validated col-sm-6 col-lg-4">
                                                <label class="col-form-label">Grupo na DRE</label>
                                                <select class="custom-select" name="dre_grupo">
                                                    <option value="">Nenhum</option>
                                                    @foreach(App\Models\CategoriaConta::gruposDRE() as $key => $label)
                                                        <option value="{{ $key }}" @isset($categoria) @if($categoria->dre_grupo == $key) selected @endif @endisset>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <h5 class="mt-4 mb-3 text-primary">Parametrização Contábil</h5>
                                        <div class="row bg-light p-3 rounded">

                                            <!-- CTB PRODUTOS -->
                                            <div class="col-md-3 form-group">
                                                <label>Int.Ctb (Produtos)</label>
                                                <input type="text" class="form-control @if($errors->has('int_ctb')) is-invalid @endif" name="int_ctb" value="{{{ isset($categoria) ? $categoria->int_ctb : old('int_ctb') }}}" maxlength="10">
                                                <small class="text-muted">Integração Padrão / Mercadorias.</small>
                                            </div>

                                            <!-- CTB SERVIÇOS (NOVO) -->
                                            <div class="col-md-3 form-group">
                                                <label>Int.Ctb (Serviços)</label>
                                                <input type="text" class="form-control @if($errors->has('int_ctb_servico')) is-invalid @endif" name="int_ctb_servico" value="{{{ isset($categoria) ? $categoria->int_ctb_servico : old('int_ctb_servico') }}}" maxlength="10">
                                                <small class="text-muted">Integração exclusiva para Serviços.</small>
                                            </div>

                                            <div class="col-md-3 form-group">
                                                <label>Conta de Resultado (DRE)</label>
                                                <select class="form-control select2" name="conta_contabil_despesa_id">
                                                    <option value="">Selecione...</option>
                                                    @foreach(App\Models\PlanoContasContabil::all() as $c)
                                                        <option value="{{$c->id}}" @if(isset($categoria) && $categoria->conta_contabil_despesa_id == $c->id) selected @endif>
                                                            {{$c->classificador}} - {{$c->nome}}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <small class="text-muted">Conta onde o valor será alocado.</small>
                                            </div>

                                            <div class="col-md-3 form-group">
                                                <label>Conta de Provisão (Pass/Atv)</label>
                                                <select class="form-control select2" name="conta_contabil_provisao_id">
                                                    <option value="">Selecione...</option>
                                                    @foreach(App\Models\PlanoContasContabil::all() as $c)
                                                        <option value="{{$c->id}}" @if(isset($categoria) && $categoria->conta_contabil_provisao_id == $c->id) selected @endif>
                                                            {{$c->classificador}} - {{$c->nome}}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <small class="text-muted">Conta de controle do passivo/ativo.</small>
                                            </div>

                                            <div class="col-md-12 mt-2">
                                                <!-- Restante dos checkboxes mantidos intactos -->
                                                <div class="checkbox-inline">
                                                    <label class="checkbox checkbox-primary mr-5" title="Se marcado, o sistema criará o lançamento de provisão no vencimento e a baixa no pagamento.">
                                                        <input type="checkbox" name="gera_provisao" value="1" @if(isset($categoria) && $categoria->gera_provisao) checked @endif>
                                                        <span></span> Gerar Provisão (Competência)
                                                    </label>
                                                    <label class="checkbox checkbox-primary mr-5" title="Se marcado, ignora o nome do fornecedor/cliente, útil para impostos e salários.">
                                                        <input type="checkbox" name="ignora_terceiro" value="1" @if(isset($categoria) && $categoria->ignora_terceiro) checked @endif>
                                                        <span></span> Ignorar Terceiro
                                                    </label>
                                                    <label class="checkbox checkbox-success" title="Se marcado, o valor entra no cálculo do seu resultado mensal.">
                                                        <input type="checkbox" name="incluir_resultado" value="1" {{{ (isset($categoria) && $categoria->incluir_resultado) || !isset($categoria) ? 'checked' : '' }}}>
                                                        <span></span> Contabilizar no Resultado
                                                    </label>
                                                </div>

                                                <div class="alert alert-custom alert-light-info mt-3 p-3">
                                                    <ul class="mb-0 small">
                                                        <li><strong>Gerar Provisão:</strong> Separa o registro contábil em duas etapas (lançamento do débito/crédito no vencimento + baixa bancária no pagamento).</li>
                                                        <li><strong>Ignorar Terceiro:</strong> Ideal para despesas fixas (Impostos, Salários). O sistema não associará o lançamento a um fornecedor específico, apenas à conta contábil fixa.</li>
                                                        <li><strong>Contabilizar no Resultado:</strong> Indica que o valor deve compor a apuração mensal de Lucro ou Prejuízo do seu DRE.</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <div class="row">
                                <div class="col-xl-2"></div>
                                <div class="col-lg-3"><a class="btn btn-danger" style="width:100%" href="/categoriasConta"><i class="la la-close"></i> Cancelar</a></div>
                                <div class="col-lg-3"><button style="width:100%" type="submit" class="btn btn-success"><i class="la la-check"></i> Salvar</button></div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                placeholder: "Pesquise pelo código ou nome da conta...",
                allowClear: true,
                width: '100%'
            });
        });
    </script>
@endsection
