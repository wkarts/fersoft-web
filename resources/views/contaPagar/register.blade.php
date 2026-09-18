@extends('default.layout')
@section('content')
    <div class=" d-flex flex-column flex-column-fluid" id="kt_content">
        <div class="card card-custom gutter-b example example-compact">
            <div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
                <div class="col-lg-12">
                    <br>
                    <form method="post" action="{{{ isset($conta) ? '/contasPagar/update': '/contasPagar/save' }}}" enctype="multipart/form-data" id="form-register">
                        <input type="hidden" name="id" value="{{{ isset($conta) ? $conta->id : 0 }}}">
                        <div class="card card-custom gutter-b example example-compact">
                            <div class="card-header">

                                <h3 class="card-title">{{{ isset($conta) ? "Editar": "Cadastrar" }}} Conta a Pagar</h3>
                            </div>

                        </div>
                        @csrf

                        <div class="row">
                            <div class="col-xl-12">
                                <div class="kt-section kt-section--first">
                                    <div class="kt-section__body">

                                        <button type="button" data-toggle="modal" data-target="#modal_retencoes" class="btn btn-sm btn-dark">
                                            <i class="la la-money"></i>Outras retençoes
                                        </button>

                                        <div class="row">
                                            <div class="form-group validated col-sm-6 col-lg-3">
                                                <label class="col-form-label">Referência</label>
                                                <div class="">
                                                    <input type="text" class="form-control @if($errors->has('referencia')) is-invalid @endif" name="referencia" value="{{{ isset($conta) ? $conta->referencia : old('referencia') }}}">
                                                    @if($errors->has('referencia'))
                                                        <div class="invalid-feedback">
                                                            {{ $errors->first('referencia') }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
											
                                            <div class="form-group col-lg-2 col-md-9 col-sm-12">
                                                <label class="col-form-label">Data de Emissão</label>
                                                <div class="">
                                                    <div class="input-group date">
                                                        <input type="text" name="data_emissao" class="form-control @if($errors->has('data_emissao')) is-invalid @endif date-input" value="{{{ isset($conta) ? \Carbon\Carbon::parse($conta->data_emissao)->format('d/m/Y') : old('emissao') }}}" id="kt_datepicker_3" />
                                                        <div class="input-group-append">
														<span class="input-group-text">
															<i class="la la-calendar"></i>
														</span>
                                                        </div>
                                                        @if($errors->has('data_emissao'))
                                                            <div class="invalid-feedback">
                                                                {{ $errors->first('data_emissao') }}
                                                            </div>
                                                        @endif
                                                    </div>


                                                </div>
                                            </div>
                                            @if(!isset($conta) || $conta->compra_id == null)
                                                <div class="form-group validated col-sm-9 col-lg-4 col-12">
                                                  <label class="col-form-label" id="">Fornecedor</label>
                                                  <div class="input-group">
                                                      <!-- O select fica disabled (bloqueado) se a conta vier de uma compra -->
                                                      <select class="form-control select2 @if($errors->has('fornecedor_id')) is-invalid @endif" id="kt_select2_3" name="fornecedor_id" @if(isset($conta) && $conta->compra_id != null) disabled @endif>
                                                          <option value="">Selecione o fornecedor</option>
                                                          @foreach($fornecedores as $c)
                                                              <option
                                                                  @if(isset($conta) && $conta->fornecedor_id == $c->id)
                                                                      selected
                                                                  @elseif(old('fornecedor_id') == $c->id)
                                                                      selected
                                                                  @endif
                                                                  value="{{$c->id}}">
                                                                  {{$c->id}} - {{$c->razao_social}} @if($c->nome_fantasia) | {{$c->nome_fantasia}} @endif ({{$c->cpf_cnpj}})
                                                              </option>
                                                          @endforeach
                                                      </select>

                                                      <!-- Só mostra o botão de "+" se for possível editar o fornecedor -->
                                                      @if(!isset($conta) || $conta->compra_id == null)
                                                          <button type="button" onclick="novoFornecedor()" class="btn btn-warning btn-sm">
                                                              <i class="la la-plus-circle icon-add"></i>
                                                          </button>
                                                      @endif

                                                      @if($errors->has('fornecedor_id'))
                                                          <div class="invalid-feedback">
                                                              {{ $errors->first('fornecedor_id') }}
                                                          </div>
                                                      @endif
                                                  </div>
                                              </div>

                                            @endif

                                            <div class="form-group validated col-lg-3 col-md-4 col-sm-6">
                                                <label class="col-form-label">Categoria</label>                                                                                    
                                                <select class="custom-select form-control" id="categoria_id" name="categoria_id">
                                                    @foreach($categorias as $cat)
                                                        <option value="{{$cat->id}}" @isset($conta)
                                                            @if($cat->id == $conta->categoria_id)
                                                                selected
                                                            @endif
                                                            @endisset >{{$cat->nome}}
                                                        </option>
                                                    @endforeach
                                                </select>

                                            </div>
                                          
                                         <div class="form-group col-lg-3 col-md-4 col-sm-6">
                                               <label class="col-form-label">Veículo (Opcional)</label>
                                                <select class="form-control custom-select" name="veiculo_id" id="veiculo_id">
                                                 <option value="">Selecione um veículo</option>
                                                 @foreach($veiculos as $v)
                                                <option value="{{$v->id}}">{{$v->placa}} - {{$v->modelo}}</option>
                                                 @endforeach
                                              </select>
                                          </div>
                                          
                                            <div class="form-group col-lg-2 col-md-9 col-sm-12">
                                                <label class="col-form-label">Data de vencimento</label>
                                                <div class="">
                                                    <div class="input-group date">
                                                        <input type="text" name="vencimento" class="form-control @if($errors->has('vencimento')) is-invalid @endif date-input" value="{{{ isset($conta) ? \Carbon\Carbon::parse($conta->data_vencimento)->format('d/m/Y') : old('vencimento') }}}" id="kt_datepicker_3" />
                                                        <div class="input-group-append">
														<span class="input-group-text">
															<i class="la la-calendar"></i>
														</span>
                                                        </div>
                                                        @if($errors->has('vencimento'))
                                                            <div class="invalid-feedback">
                                                                {{ $errors->first('vencimento') }}
                                                            </div>
                                                        @endif
                                                    </div>


                                                </div>
                                            </div>

                                            <div class="form-group validated col-lg-2 col-md-4 col-sm-6">
                                                <label class="col-form-label">Valor</label>

                                                <input id="valor" type="tel" class="form-control @if($errors->has('valor')) is-invalid @endif money" name="valor" value="{{{ isset($conta) ? number_format($conta->valor_integral, $casasDecimais, ',', '.') : old('valor') }}}">
                                                @if($errors->has('valor'))
                                                    <div class="invalid-feedback">
                                                        {{ $errors->first('valor') }}
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="form-group validated col-lg-2 col-md-4 col-sm-6 d-valor_final d-none">
                                                <label class="col-form-label text-danger">Valor final</label>

                                                <input id="valor_final" type="tel" class="form-control money" name="valor_final" value="{{{ isset($conta) ? number_format($conta->valor_integral, $casasDecimais, ',', '.') : old('valor') }}}">
                                            </div>

                                            <!-- Campo Número da Nota Fiscal -->
                                            <div class="form-group validated col-lg-2 col-md-4 col-sm-6">
                                                <label class="col-form-label">Nº nota fiscal</label>
                                                <input id="numero_nota_fiscal" type="text" class="form-control @if($errors->has('numero_nota_fiscal')) is-invalid @endif" name="numero_nota_fiscal" value="{{ isset($conta) ? $conta->numero_nota_fiscal : old('numero_nota_fiscal') }}">
                                                @if($errors->has('numero_nota_fiscal'))
                                                    <div class="invalid-feedback">
                                                        {{ $errors->first('numero_nota_fiscal') }}
                                                    </div>
                                                @endif
                                                <small class="form-text text-muted">
                                                    Se a conta for gerada a partir de uma compra sem número de nota, este campo poderá ser corrigido.
                                                </small>
                                            </div>

                                            <!--
										<div class="form-group validated col-lg-2 col-md-4 col-sm-6">
											<label class="col-form-label">Nº nota fiscal</label>

											<input id="numero_nota_fiscal" type="text" class="form-control @if($errors->has('numero_nota_fiscal')) is-invalid @endif" name="numero_nota_fiscal" value="{{{ isset($conta) ? $conta->numero_nota_fiscal : old('numero_nota_fiscal') }}}">
											@if($errors->has('numero_nota_fiscal'))
                                                <div class="invalid-feedback">
{{ $errors->first('numero_nota_fiscal') }}
                                                </div>
@endif
                                            </div>
-->

                                            <div class="form-group validated col-sm-12 col-lg-3">
                                                <label class="col-form-label" id="">Tipo de Pagamento</label>
                                                <select class="custom-select form-control" id="forma" name="tipo_pagamento">
                                                    <option value="">Selecione o tipo de pagamento</option>
                                                    @foreach(App\Models\ContaPagar::tiposPagamento() as $c)
                                                        <option @isset($conta) @if($conta->tipo_pagamento == $c) selected @endif @else @if(old('tipo_pagamento') == $c) selected @endif @endif value="{{$c}}">{{$c}}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            @if(!isset($conta))
 <div class="form-group col-lg-2 col-md-9 col-sm-12">
    <label class="col-form-label">Conta Paga</label>
    <div class="col-lg-12 col-xl-12">
        <span class="switch switch-outline switch-success">
            <label>
                <input @if(isset($conta) && $conta->status) checked @endif type="checkbox" id="pago" name="status">
                <span></span>
            </label>
        </span>
    </div>
</div>

<div class="form-group validated col-lg-4 col-md-6 col-sm-12 div-conta-empresa" style="display: none">
    <label class="col-form-label">Conta Bancária/Caixa</label>
    <select class="form-control custom-select" name="conta_id" id="conta_id">
        <option value="">Selecione Banco/Caixa</option>
        @foreach($contasEmpresa as $c)
            <option value="{{ $c->id }}" @if(isset($conta) && $conta->conta_id == $c->id) selected @endif>
                {{ $c->nome }} | Saldo: R$ {{ number_format($c->saldo, 2, ',', '.') }}
            </option>
        @endforeach
    </select>
</div>
                                          
<div class="form-group validated col-lg-3 col-md-6 col-sm-12 div-data-pagamento" style="display: none">
    <label class="col-form-label">Data de Pagamento</label>
    <div class="input-group date">
        <input type="text" name="data_pagamento" class="form-control date-input" 
            value="{{ date('d/m/Y') }}" id="kt_datepicker_payment" />
        <div class="input-group-append">
            <span class="input-group-text"><i class="la la-calendar"></i></span>
        </div>
    </div>
</div>

                                                <div class="form-group validated col-lg-2 col-md-4 col-sm-6 div-pago" style="display: none">
                                                    <label class="col-form-label">Valor pago</label>

                                                    <input id="valor_pago" type="text" class="form-control @if($errors->has('valor_pago')) is-invalid @endif money" name="valor_pago" value="{{{ isset($conta) ? number_format($conta->valor_integral, $casasDecimais, ',', '.') : old('valor_pago') }}}">
                                                    @if($errors->has('valor_pago'))
                                                        <div class="invalid-feedback">
                                                            {{ $errors->first('valor_pago') }}
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif

                                            @isset($conta)
                                                {!! __view_locais_select_edit("Local", $conta->filial_id) !!}
                                            @else
                                                {!! __view_locais_select() !!}
                                            @endif

                                            <div class="form-group validated col-12">
                                                <label class="col-form-label">Observação</label>
                                                <div class="">
                                                    <input type="text" class="form-control @if($errors->has('observacao')) is-invalid @endif" name="observacao" value="{{{ isset($conta) ? $conta->observacao : old('observacao') }}}">
                                                    @if($errors->has('observacao'))
                                                        <div class="invalid-feedback">
                                                            {{ $errors->first('observacao') }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        @if(!isset($conta))


                                            <div class="row">
                                                <div class="form-group validated col-lg-4 col-md-4 col-sm-6">
                                                    <label class="col-form-label">Salvar até este mês (opcional)</label>

                                                    <input placeholder="mm/aa" type="text" class="form-control @if($errors->has('recorrencia')) is-invalid @endif" id="recorrencia" name="recorrencia" >
                                                    @if($errors->has('recorrencia'))
                                                        <div class="invalid-feedback">
                                                            {{ $errors->first('recorrencia') }}
                                                        </div>
                                                    @endif
                                                    <p style="color: red; margin-top: 5px;">*Este campo deve ser preenchido se ouver recorrência para este registro
                                                    </p>
                                                </div>
                                            </div>

                                    </div>

                                    @endif

                                    <div class="row tbl" style="display: none">
                                        <div class="col-12 col-sm-6">
                                            <table class="table">
                                                <thead>
                                                <tr>
                                                    <td>Data</td>
                                                    <td>Valor</td>
                                                </tr>
                                                </thead>
                                                <tbody>

                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                </div>
                <input type="hidden" name="parcelas" id="parcelas">
            </div>
            <div class="card-footer">

                <div class="row">
                    <div class="col-xl-2">

                    </div>
                    <div class="col-lg-3 col-sm-6 col-md-4">
                        <a style="width: 100%" class="btn btn-danger" href="/contasPagar">
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

            @include('contaPagar.partials._modal_retencoes')
            </form>
        </div>
    </div>
    </div>
    </div>

    @if(!isset($conta))
        <input type="hidden" id="_token" value="{{ csrf_token() }}">

        <div class="modal fade" id="modal-fornecedor" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Novo Fornecedor</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            x
                        </button>
                    </div>
                    <div class="modal-body">

                        <div class="row">
                            <div class="col-xl-12">

                                <div class="row">
                                    <div class="form-group col-sm-12 col-lg-12">
                                        <label>Pessoa:</label>
                                        <div class="radio-inline">
                                            <label class="radio radio-success">
                                                <input name="group1" type="radio" id="pessoaFisica"/>
                                                <span></span>
                                                FISICA
                                            </label>
                                            <label class="radio radio-success">
                                                <input name="group1" type="radio" id="pessoaJuridica"/>
                                                <span></span>
                                                JURIDICA
                                            </label>

                                        </div>

                                    </div>
                                </div>
                                <div class="row">

                                    <div class="form-group validated col-sm-3 col-lg-4">
                                        <label class="col-form-label" id="lbl_cpf_cnpj">CPF</label>
                                        <input type="text" id="cpf_cnpj" class="form-control @if($errors->has('cpf_cnpj')) is-invalid @endif" name="cpf_cnpj">
                                    </div>

                                    <div class="form-group validated col-lg-2 col-md-2 col-sm-6">
                                        <label class="col-form-label">UF</label>
                                        <select class="custom-select form-control" id="sigla_uf" name="sigla_uf">
                                            @foreach(App\Models\Cidade::estados() as $c)
                                                <option value="{{$c}}">{{$c}}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group validated col-lg-2 col-md-2 col-sm-6">
                                        <br><br>
                                        <a type="button" id="btn-consulta-cadastro" onclick="consultaCadastro()" class="btn btn-success spinner-white spinner-right">
									<span>
										<i class="fa fa-search"></i>
									</span>
                                        </a>
                                    </div>

                                </div>

                                <div class="row">
                                    <div class="form-group validated col-sm-6">
                                        <label class="col-form-label">Razao Social/Nome</label>
                                        <div class="">
                                            <input id="razao_social2" type="text" class="form-control @if($errors->has('razao_social')) is-invalid @endif">

                                        </div>
                                    </div>

                                    <div class="form-group validated col-sm-6">
                                        <label class="col-form-label">Nome Fantasia</label>
                                        <div class="">
                                            <input id="nome_fantasia2" type="text" class="form-control @if($errors->has('nome_fantasia')) is-invalid @endif">
                                        </div>
                                    </div>

                                    <div class="form-group validated col-sm-3 col-lg-4">
                                        <label class="col-form-label" id="lbl_ie_rg">RG</label>
                                        <input type="text" id="ie_rg" class="form-control @if($errors->has('ie_rg')) is-invalid @endif">
                                    </div>

                                    <div class="form-group validated col-lg-3 col-md-3 col-sm-10">
                                        <label class="col-form-label">Contribuinte</label>

                                        <select class="custom-select form-control" id="contribuinte">

                                            <option value="1">SIM</option>
                                            <option value="0">NAO</option>
                                        </select>
                                    </div>

                                </div>
                                <hr>
                                <h5>Endereço</h5>
                                <div class="row">
                                    <div class="form-group validated col-sm-8 col-lg-6">
                                        <label class="col-form-label">Rua</label>
                                        <input id="rua" type="text" class="form-control @if($errors->has('rua')) is-invalid @endif">
                                    </div>

                                    <div class="form-group validated col-sm-2 col-lg-2">
                                        <label class="col-form-label">Número</label>
                                        <input id="numero2" type="text" class="form-control @if($errors->has('numero')) is-invalid @endif">
                                    </div>

                                    <div class="form-group validated col-sm-8 col-lg-3">
                                        <label class="col-form-label">Bairro</label>
                                        <input id="bairro" type="text" class="form-control @if($errors->has('bairro')) is-invalid @endif">
                                    </div>

                                    <div class="form-group validated col-sm-8 col-lg-2">
                                        <label class="col-form-label">CEP</label>
                                        <input id="cep" type="text" class="form-control @if($errors->has('cep')) is-invalid @endif">
                                    </div>

                                    <div class="form-group validated col-sm-8 col-lg-5">
                                        <label class="col-form-label">Complemento</label>
                                        <input id="complemento" type="text" class="form-control">
                                    </div>

                                    <div class="form-group validated col-sm-8 col-lg-3">
                                        <label class="col-form-label">Email</label>
                                        <input id="email" type="text" class="form-control @if($errors->has('email')) is-invalid @endif">
                                    </div>

                                    @php
                                        $cidade = App\Models\Cidade::getCidadeCod($config->codMun);
                                    @endphp
                                    <div class="form-group validated col-lg-4 col-md-6 col-sm-10">
                                        <label class="col-form-label">Cidade</label><br>
                                        <select style="width: 100%" class="form-control select2" id="kt_select2_4">
                                            @foreach(App\Models\Cidade::all() as $c)
                                                <option @if($cidade->id == $c->id) selected @endif value="{{$c->id}}">
                                                    {{$c->nome}} ({{$c->uf}})
                                                </option>
                                            @endforeach
                                        </select>

                                    </div>

                                    <div class="form-group validated col-sm-8 col-lg-3">
                                        <label class="col-form-label">Telefone (Opcional)</label>
                                        <div class="">
                                            <input id="telefone" type="text" class="form-control @if($errors->has('telefone')) is-invalid @endif">
                                        </div>
                                    </div>

                                    <div class="form-group validated col-sm-8 col-lg-3">
                                        <label class="col-form-label">Celular (Opcional)</label>
                                        <div class="">
                                            <input id="celular" type="text" class="form-control @if($errors->has('celular')) is-invalid @endif">
                                        </div>
                                    </div>

                                    <div class="form-group validated col-sm-8 col-lg-4">
                                        <label class="col-form-label">Chave PIX (Opcional)</label>
                                        <div class="">
                                            <input id="pix" type="text" class="form-control @if($errors->has('pix')) is-invalid @endif" name="pix">

                                        </div>
                                    </div>

                                    <div class="form-group validated col-sm-8 t-pix col-lg-2 d-none">
                                        <label class="col-form-label">Tipo PIX</label>
                                        <select class="form-control @if($errors->has('tipo_pix')) is-invalid @endif" name="tipo_pix">
                                            <option value="">--</option>
                                            @foreach(App\Models\Fornecedor::tiposDePix() as $tp)
                                                <option @isset($forn) @if($forn->tipo_pix == $tp) selected @endif @endif value="{{$tp}}">{{ strtoupper($tp) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" id="btn-frete" class="btn btn-danger font-weight-bold spinner-white spinner-right" data-dismiss="modal" aria-label="Close">Fechar</button>
                        <button type="button" onclick="salvarFornecedor()" class="btn btn-success font-weight-bold spinner-white spinner-right">Salvar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

@endsection
@section('javascript')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.3/moment.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script type="text/javascript">
        var PARCELAS = []
        $(function () {
            changePago()
            pixDigita()
            $('#valor_final').val('')

            calculaRetencoes()
        })

        function pixDigita(){
            if($('#pix').val().trim().length > 0){
                $('.t-pix').removeClass('d-none')
            }else{
                $('.t-pix').addClass('d-none')
            }
        }

        $('#pix').keyup(() => {
            pixDigita()
        })

        @if(!isset($conta))
        $('.salvar-retencoes').click(() => {
            calculaRetencoes()
        })

        $('#valor').blur(() => {
            calculaRetencoes()
        })
        @endif

        function calculaRetencoes(){
            let valor_inss = convertMoedaToFloat($('#valor_inss').val())
            let valor_iss = convertMoedaToFloat($('#valor_iss').val())
            let valor_pis = convertMoedaToFloat($('#valor_pis').val())
            let valor_cofins = convertMoedaToFloat($('#valor_cofins').val())
            let valor_ir = convertMoedaToFloat($('#valor_ir').val())
            let outras_retencoes = convertMoedaToFloat($('#outras_retencoes').val())

            let soma = valor_inss + valor_iss + valor_pis + valor_cofins + valor_ir + outras_retencoes

            if(soma > 0){
                let valor = convertMoedaToFloat($('#valor').val())
                $('#valor_final').val(convertFloatToMoeda(valor-soma))
                $('.d-valor_final').removeClass('d-none')
            }else{
                $('.d-valor_final').addClass('d-none')
            }
        }

        $('#recorrencia').blur(() => {

            let recorrencia = $('#recorrencia').val()
            let valor = $('#valor').val()
            if(!valor){
                swal("Alerta", "Informe o valor", "warning")
            }else{
                if(recorrencia.length == 5){
                    $('.tbl').css('display', 'block')
                    let vencimento = $('#kt_datepicker_3').val()
                    let dia = vencimento.split('/')[0]

                    let mes = recorrencia.split('/')[0]
                    let ano = "20"+recorrencia.split('/')[1]

                    // vencimento = converteData(vencimento)
                    if(dia == 31){
                        dia = 30
                    }
                    if(mes == "02"){
                        dia = 28
                    }

                    let d1 = moment(converteData(vencimento))
                    let d2 = moment(ano + "-" + mes + "-" + dia)
                    let duration = moment.duration(d2.diff(d1));
                    let meses = parseInt(duration.asMonths())
                    if(duration.asDays() <= 30){
                        meses++
                    }

                    if(!d1.isAfter(moment(new Date()))){
                        meses++
                    }

                    montaHtml(meses, vencimento, dia+"/"+recorrencia, dia)

                }else{
                    $('.tbl').css('display', 'none')
                }
            }
        })

        function montaHtml(meses, vencimento, ultimoDia, dia) {
    $('table tbody').html('');
    let valor = $('#valor').val();
    if ($('#valor_final').val()) {
        valor = $('#valor_final').val();
    }

    // Pegamos a data limite digitada no campo "Salvar até este mês"
    let recorrencia = $('#recorrencia').val(); // formato "mm/aa"
    let mesLimite = parseInt(recorrencia.split('/')[0]);
    let anoLimite = parseInt("20" + recorrencia.split('/')[1]);

    let vencOriginal = converteData(vencimento);
    let venc = new Date(vencOriginal + "T00:00:00"); // Força fuso horário local

    // O loop começa em 1 porque a Parcela 0 é a que você está salvando agora (Principal)
    // Usamos um limite de segurança de 100 para evitar loops infinitos
    for (let i = 1; i <= 100; i++) {
        // Adiciona 1 mês à data
        venc.setMonth(venc.getMonth() + 1);

        let mesAtual = venc.getMonth() + 1;
        let anoAtual = venc.getFullYear();

        // TRAVA: Se o mês e ano ultrapassarem o limite digitado, para o loop
        if (anoAtual > anoLimite || (anoAtual == anoLimite && mesAtual > mesLimite)) {
            break; 
        }

        let dataFormatada = (venc.getDate() < 10 ? ("0" + venc.getDate()) : venc.getDate()) +
            "/" + (mesAtual < 10 ? "0" + mesAtual : mesAtual) +
            "/" + anoAtual;

        let dataSQL = converteData(dataFormatada);

        let html = '<tr>';
        html += '<td>';
        html += '<input value="' + dataSQL + '" type="date" class="form-control dt">';
        html += '</td>';
        html += '<td>';
        html += '<input value="' + valor + '" type="text" class="form-control valor">';
        html += '</td>';
        html += '</tr>';
        
        $('table tbody').append(html);
    }
}
        function converteData(data){
            let temp = data.split('/')
            return temp[2] + '-' + temp[1] + '-' + temp[0]
        }

        $('#form-register').submit(() => {
            PARCELAS = []
            $('table tbody tr').each(function(){
                let js = {
                    vencimento : $(this).find('.dt').val(),
                    valor : $(this).find('.valor').val(),
                }
                PARCELAS.push(js)
            })
            console.log(JSON.stringify(PARCELAS))

            $('#parcelas').val(JSON.stringify(PARCELAS))
        })

        $('#pago').change(() => {
            changePago()
        })

        function changePago(){
            let valor = $('#valor').val()
            $('#valor_pago').val(valor)
            let pago = $('#pago').is(':checked')
            if(pago){
                $('.div-pago').css('display', 'block')
                $('.div-conta-empresa').css('display', 'block')
                $('.div-data-pagamento').css('display', 'block')
            }else{
                $('.div-pago').css('display', 'none')
                $('.div-conta-empresa').css('display', 'none')
                $('.div-data-pagamento').css('display', 'none')
                $('#conta_id').val('').change()
                $('#kt_datepicker_payment').val("{{ date('d/m/Y') }}")
            }
        }
$('#kt_datepicker_payment').datepicker({
    format: "dd/mm/yyyy",
    todayHighlight: true,
    autoclose: true,
    orientation: "bottom left"
});
        function novoFornecedor(){
            $('#modal-fornecedor').modal('show')
        }

        function salvarFornecedor(){
            let js = {
                razao_social: $('#razao_social2').val(),
                nome_fantasia: $('#nome_fantasia2').val() ? $('#nome_fantasia2').val() : '',
                rua: $('#rua').val() ? $('#rua').val() : '',
                numero: $('#numero2').val() ? $('#numero2').val() : '',
                cpf_cnpj: $('#cpf_cnpj').val() ? $('#cpf_cnpj').val() : '',
                ie_rg: $('#ie_rg').val() ? $('#ie_rg').val() : '',
                bairro: $('#bairro').val() ? $('#bairro').val() : '',
                cep: $('#cep').val() ? $('#cep').val() : '',
                contribuinte: $('#contribuinte').val() ? $('#contribuinte').val() : '',
                cidade_id: $('#kt_select2_4').val() ? $('#kt_select2_4').val() : NULL,
                telefone: $('#telefone').val() ? $('#telefone').val() : '',
                celular: $('#celular').val() ? $('#celular').val() : '',
                pix: $('#pix').val() ? $('#pix').val() : '',
                complemento: $('#complemento').val() ? $('#complemento').val() : '',
                tipo_pix: $('#tipo_pix').val() ? $('#tipo_pix').val() : '',
            }

            if(js.razao_social == ''){
                swal("Erro", "Informe a razão social", "warning")
            }else if(js.rua == ''){
                swal("Erro", "Informe a rua", "warning")
            }
            else if(js.cpf_cnpj == ''){
                swal("Erro", "Informe o CPF/CNPJ", "warning")
            }else if(js.bairro == ''){
                swal("Erro", "Informe o bairro", "warning")
            }else if(js.cep == ''){
                swal("Erro", "Informe o CEP", "warning")
            }else if(js.cep == ''){
                swal("Erro", "Informe o CEP", "warning")
            }
            else{

                let token = $('#_token').val();
                $.post(path + 'fornecedores/quickSave',
                    {
                        _token: token,
                        data: js
                    })
                    .done((res) =>{

                        limparCampos()

                        $('#kt_select2_3').append('<option value="'+res.id+'">'+
                            res.razao_social+'</option>').change();
                        $('#kt_select2_3').val(res.id).change();
                        swal("Sucesso", "Fornecedor adicionado!!", 'success')
                            .then(() => {
                                $('#modal-fornecedor').modal('hide')
                            })
                    })
                    .fail((err) => {
                        console.log(err)
                    })
            }


        }
        $('#pessoaFisica').click(function () {
            $('#lbl_cpf_cnpj').html('CPF');
            $('#lbl_ie_rg').html('RG');
            $('#cpf_cnpj').mask('000.000.000-00', { reverse: true });
            $('#btn-consulta-cadastro').css('display', 'none')

        })

        $('#pessoaJuridica').click(function () {
            $('#lbl_cpf_cnpj').html('CNPJ');
            $('#lbl_ie_rg').html('IE');
            $('#cpf_cnpj').mask('00.000.000/0000-00', { reverse: true });
            $('#btn-consulta-cadastro').css('display', 'block');
        });

        function consultaCadastro() {
            let cnpj = $('#cpf_cnpj').val();
            let uf = $('#sigla_uf').val();
            cnpj = cnpj.replace('.', '');
            cnpj = cnpj.replace('.', '');
            cnpj = cnpj.replace('-', '');
            cnpj = cnpj.replace('/', '');

            if (cnpj.length == 14 && uf.length != '--') {
                $('#btn-consulta-cadastro').addClass('spinner')

                $.ajax
                ({
                    type: 'GET',
                    data: {
                        cnpj: cnpj,
                        uf: uf
                    },
                    url: path + 'nf/consultaCadastro',

                    dataType: 'json',

                    success: function (e) {
                        $('#btn-consulta-cadastro').removeClass('spinner')

                        if (e.infCons.infCad) {
                            let info = e.infCons.infCad;

                            $('#ie_rg').val(info.IE)
                            $('#razao_social2').val(info.xNome)
                            $('#nome_fantasia2').val(info.xFant ? info.xFant : info.xNome)

                            $('#rua').val(info.ender.xLgr)
                            $('#numero2').val(info.ender.nro)
                            $('#bairro').val(info.ender.xBairro)
                            let cep = info.ender.CEP;
                            $('#cep').val(cep.substring(0, 5) + '-' + cep.substring(5, 9))

                            findNomeCidade(info.ender.xMun, (res) => {

                                let jsCidade = JSON.parse(res);

                                if (jsCidade) {

                                    $('#kt_select2_4').val(jsCidade.id).change();
                                }
                            })

                        } else {
                            swal("Erro", e.infCons.xMotivo, "error")

                        }
                    }, error: function (e) {
                        consultaAlternativa(cnpj, (data) => {

                            if(data == false){
                                swal("Alerta", "Nenhum retorno encontrado para este CNPJ, informe manualmente por gentileza", "warning")
                            }else{
                                $('#razao_social2').val(data.nome)
                                $('#nome_fantasia2').val(data.nome)

                                $('#rua').val(data.logradouro)
                                $('#numero2').val(data.numero)
                                $('#bairro').val(data.bairro)
                                let cep = data.cep;
                                $('#cep').val(cep.replace(".", ""))

                                findNomeCidade(data.municipio, (res) => {
                                    let jsCidade = JSON.parse(res);

                                    if (jsCidade) {

                                        $('#kt_select2_4').val(jsCidade.id).change();
                                    }
                                })
                            }
                        })
                        $('#btn-consulta-cadastro').removeClass('spinner')
                    }
                });
            }else{
                swal("Alerta", "Informe corretamente o CNPJ e UF", "warning")
            }
        }

        function consultaAlternativa(cnpj, call){
            cnpj = cnpj.replace('.', '');
            cnpj = cnpj.replace('.', '');
            cnpj = cnpj.replace('-', '');
            cnpj = cnpj.replace('/', '');
            let res = null;
            $.ajax({

                url: 'https://www.receitaws.com.br/v1/cnpj/'+cnpj,
                type: 'GET',
                crossDomain: true,
                dataType: 'jsonp',
                success: function(data)
                {
                    $('#consulta').removeClass('spinner');

                    if(data.status == "ERROR"){
                        swal(data.message, "", "error")
                        call(false)
                    }else{
                        call(data)
                    }

                },
                error: function(e) {
                    $('#consulta').removeClass('spinner');
                    console.log(e)

                    call(false)

                },
            });
        }

        function limparCampos(){
            $('#razao_social2').val('')
            $('#nome_fantasia2').val('')

            $('#rua').val('')
            $('#numero2').val('')
            $('#bairro').val('')
            $('#cep').val('')
            $('#kt_select2_4').val('1').change();
        }

        function findNomeCidade(nomeCidade, call) {

            $.get(path + 'cidades/findNome/' + nomeCidade)
                .done((success) => {
                    call(success)
                })
                .fail((err) => {
                    call(err)
                })
        }
      // Função para verificar saldo de adiantamento
function verificarAdiantamento(pessoa_id, tipo) {
    if(!pessoa_id) return;

    $.get('/adiantamentos/verificar-saldo/' + pessoa_id + '/' + tipo, function(data) {
        if (data.saldo > 0) {
            let valorFormatado = data.saldo.toLocaleString('pt-br',{style: 'currency', currency: 'BRL'});
            
            Swal.fire({
                title: 'Crédito Disponível!',
                text: `Este ${tipo} possui ${valorFormatado} de adiantamento. Deseja utilizar este saldo para abater nesta operação?`,
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sim, usar crédito',
                cancelButtonText: 'Não, manter saldo'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Cria um campo escondido no formulário para avisar o Controller
                    $('#form-venda, #form-compra').append(`<input type="hidden" name="usar_adiantamento" value="1">`);
                    Swal.fire('Confirmado!', 'O saldo será abatido ao salvar.', 'success');
                }
            });
        }
    });
}

// Gatilho quando mudar o Cliente/Fornecedor
$(document).ready(function() {
    // Para Clientes (Venda)
    $('#kt_select2_3').on('change', function() { // Ajuste o ID conforme seu select de cliente
        verificarAdiantamento($(this).val(), 'cliente');
    });

    // Para Fornecedores (Compra)
    $('#kt_select2_1').on('change', function() { // Ajuste o ID conforme seu select de fornecedor
        verificarAdiantamento($(this).val(), 'fornecedor');
    });
});

    </script>
@endsection
