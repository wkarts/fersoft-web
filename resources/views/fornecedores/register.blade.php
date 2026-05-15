@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<!--begin::Portlet-->
				<form method="post" action="/fornecedores/{{{ isset($forn) ? 'update' : 'save' }}}">

					<input type="hidden" name="id" value="{{{ isset($forn) ? $forn->id : 0 }}}">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">

							<h3 class="card-title">{{isset($forn) ? 'Editar' : 'Novo'}} Fornecedor</h3>
						</div>

					</div>
					@csrf

					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">
										<div class="form-group col-sm-12 col-lg-12">
											<label>Pessoa:</label>
											<div class="radio-inline">
												<label>Pessoa:</label>
                                                <div class="radio-inline">
                                                    <label class="radio radio-success">
                                                        <input name="group1" type="radio" id="pessoaFisica" 
                                                            @if(isset($forn) && strlen(preg_replace('/\D/', '', $forn->cpf_cnpj)) <= 11) checked @endif />
                                                        <span></span>
                                                        FISICA
                                                    </label>
                                                    <label class="radio radio-success">
                                                        <input name="group1" type="radio" id="pessoaJuridica" 
                                                            @if(isset($forn) && strlen(preg_replace('/\D/', '', $forn->cpf_cnpj)) > 11 && $forn->cpf_cnpj != '00.000.000/0000-00') checked @endif/>
                                                        <span></span>
                                                        JURIDICA
                                                    </label>
                                                    <label class="radio radio-success">
                                                        <input value="p_ext" name="group1" type="radio" id="pessoaExt" 
                                                            @if(isset($forn) && $forn->cpf_cnpj == '00.000.000/0000-00') checked @endif 
                                                            @if(old('group1') == 'p_ext') checked @endif/>
                                                        <span></span>
                                                        EXTERIOR
                                                    </label>
                                                </div>
											</div>

										</div>

										<div class="form-group validated col-sm-3 col-lg-4">
											<label class="col-form-label" id="lbl_cpf_cnpj">CPF</label>
											<div class="">
												<input type="text" id="cpf_cnpj" class="form-control @if($errors->has('cpf_cnpj')) is-invalid @endif" name="cpf_cnpj" value="{{{ isset($forn) ? $forn->cpf_cnpj : old('cpf_cnpj') }}}">
												@if($errors->has('cpf_cnpj'))
												<div class="invalid-feedback">
													{{ $errors->first('cpf_cnpj') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-lg-1 col-md-2 col-sm-6">
											<br><br>
											<a type="button" id="btn-consulta-cadastro" onclick="consultaCadastro()" class="btn btn-success spinner-white spinner-right">
												<span>
													<i class="fa fa-search"></i>
												</span>
											</a>
										</div>


										<div class="form-group validated col-sm-10 col-lg-6">
											<label class="col-form-label">Razao Social/Nome</label>
											<div class="">
												<input id="razao_social" type="text" class="form-control @if($errors->has('razao_social')) is-invalid @endif" name="razao_social" value="{{{ isset($forn) ? $forn->razao_social : old('razao_social') }}}">
												@if($errors->has('razao_social'))
												<div class="invalid-feedback">
													{{ $errors->first('razao_social') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-10 col-lg-6">
											<label class="col-form-label">Nome Fantasia</label>
											<div class="">
												<input id="nome_fantasia" type="text" class="form-control @if($errors->has('nome_fantasia')) is-invalid @endif" name="nome_fantasia" value="{{{ isset($forn) ? $forn->nome_fantasia : old('nome_fantasia') }}}">
												@if($errors->has('nome_fantasia'))
												<div class="invalid-feedback">
													{{ $errors->first('nome_fantasia') }}
												</div>
												@endif
											</div>
										</div>


										<div class="form-group validated col-sm-3 col-lg-3">
											<label class="col-form-label" id="lbl_ie_rg">IE/RG</label>
											<div class="">
												<input type="text" id="ie_rg" class="form-control @if($errors->has('ie_rg')) is-invalid @endif" name="ie_rg" value="{{{ isset($forn) ? $forn->ie_rg : old('ie_rg') }}}">
												@if($errors->has('ie_rg'))
												<div class="invalid-feedback">
													{{ $errors->first('ie_rg') }}
												</div>
												@endif
											</div>
										</div>
										<div class="form-group validated col-lg-3 col-md-3 col-sm-10">
											<label class="col-form-label">Contribuinte</label>

											<select class="custom-select form-control" id="contribuinte" name="contribuinte">
												<option value=""></option>
												<option @if(isset($forn) && $forn->contribuinte == 1) selected @endif value="1" @if(old('contribuinte') == 1) selected @endif selected>SIM</option>
												<option @if(isset($forn) && $forn->contribuinte == 0) selected @endif value="0" @if(old('contribuinte') == 0) @endif>NAO</option>
											</select>
											@if($errors->has('contribuinte'))
											<div class="invalid-feedback">
												{{ $errors->first('contribuinte') }}
											</div>
											@endif

										</div>

										<hr>


										<div class="form-group validated col-sm-8 col-lg-5">
											<label class="col-form-label">Rua</label>
											<div class="">
												<input id="rua" type="text" class="form-control @if($errors->has('rua')) is-invalid @endif" name="rua" value="{{{ isset($forn) ? $forn->rua : old('rua') }}}">
												@if($errors->has('rua'))
												<div class="invalid-feedback">
													{{ $errors->first('rua') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-2 col-lg-2">
											<label class="col-form-label">Número</label>
											<div class="">
												<input id="numero" type="text" class="form-control @if($errors->has('numero')) is-invalid @endif" name="numero" value="{{{ isset($forn) ? $forn->numero : old('numero') }}}">
												@if($errors->has('numero'))
												<div class="invalid-feedback">
													{{ $errors->first('numero') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-8 col-lg-3">
											<label class="col-form-label">Bairro</label>
											<div class="">
												<input id="bairro" type="text" class="form-control @if($errors->has('bairro')) is-invalid @endif" name="bairro" value="{{{ isset($forn) ? $forn->bairro : old('bairro') }}}">
												@if($errors->has('bairro'))
												<div class="invalid-feedback">
													{{ $errors->first('bairro') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-8 col-lg-4">
											<label class="col-form-label">Complemento</label>
											<div class="">
												<input id="complemento" type="text" class="form-control @if($errors->has('complemento')) is-invalid @endif" name="complemento" value="{{{ isset($forn) ? $forn->complemento : old('complemento') }}}">
												@if($errors->has('complemento'))
												<div class="invalid-feedback">
													{{ $errors->first('complemento') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-8 col-lg-2">
											<label class="col-form-label">CEP</label>
											<div class="">
												<input id="cep" type="text" class="form-control @if($errors->has('cep')) is-invalid @endif" name="cep" value="{{{ isset($forn) ? $forn->cep : old('cep') }}}">
												@if($errors->has('cep'))
												<div class="invalid-feedback">
													{{ $errors->first('cep') }}
												</div>
												@endif
											</div>
										</div>


										<div class="form-group validated col-sm-8 col-lg-4">
											<label class="col-form-label">Email</label>
											<div class="">
												<input id="email" type="text" class="form-control @if($errors->has('email')) is-invalid @endif" name="email" value="{{{ isset($forn) ? $forn->email : old('email') }}}">
												@if($errors->has('email'))
												<div class="invalid-feedback">
													{{ $errors->first('email') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-lg-5 col-md-6 col-sm-10">
											<label class="col-form-label">Cidade</label>

											<select class="form-control select2" id="kt_select2_1" name="cidade">
												<option value="">Selecione a cidade</option>
												@foreach($cidades as $c)
												<option value="{{$c->id}}" @isset($forn) @if($c->id == $forn->cidade_id) selected @endif @endisset @if(old('cidade') == $c->id)
													selected
													@endif >{{$c->nome}} ({{$c->uf}})
												</option>
												@endforeach
											</select>
											@if($errors->has('cidade'))
											<div class="invalid-feedback">
												{{ $errors->first('cidade') }}
											</div>
											@endif
										</div>

										<div class="form-group validated col-lg-3 col-md-3 col-sm-6">
											<label class="col-form-label text-left">Pais</label>
											<select class="form-control select2" id="kt_select2_3" name="cod_pais">
												@foreach($pais as $p)
												<option value="{{$p->codigo}}" @if(isset($forn)) @if($p->codigo == $forn->cod_pais) selected @endif @else @if($p->codigo == 1058) selected @endif @endif >{{$p->codigo}} -  ({{$p->nome}})</option>
												@endforeach
											</select>
											@if($errors->has('cod_pais'))
											<div class="invalid-feedback">
												{{ $errors->first('cod_pais') }}
											</div>
											@endif
										</div>

										<div class="form-group validated col-sm-8 col-lg-4">
											<label class="col-form-label">ID estrangeiro (Opcional)</label>
											<div class="">
												<input id="id_estrangeiro" type="text" class="form-control @if($errors->has('id_estrangeiro')) is-invalid @endif" name="id_estrangeiro" value="{{{ isset($forn) ? $forn->id_estrangeiro : old('id_estrangeiro') }}}">
												@if($errors->has('id_estrangeiro'))
												<div class="invalid-feedback">
													{{ $errors->first('id_estrangeiro') }}
												</div>
												@endif
											</div>
										</div>


										<div class="row col-12">
                                          <div class="form-group validated col-sm-8 col-lg-4">
                                              <label class="col-form-label">Banco</label>
                                              <input type="text" class="form-control" name="banco" value="{{{ isset($forn) ? $forn->banco : old('banco') }}}">
                                          </div>
                                          <div class="form-group validated col-sm-4 col-lg-2">
                                              <label class="col-form-label">Agência</label>
                                              <input type="text" class="form-control" name="agencia" value="{{{ isset($forn) ? $forn->agencia : old('agencia') }}}">
                                          </div>
                                          <div class="form-group validated col-sm-8 col-lg-3">
                                              <label class="col-form-label">Conta</label>
                                              <input type="text" class="form-control" name="conta" value="{{{ isset($forn) ? $forn->conta : old('conta') }}}">
                                          </div>
                                      </div>

                                      <div class="row col-12">
                                          <div class="form-group validated col-sm-8 col-lg-3">
                                              <label class="col-form-label">Telefone (Opcional)</label>
                                              <input id="telefone" type="text" class="form-control" name="telefone" value="{{{ isset($forn) ? $forn->telefone : old('telefone') }}}">
                                          </div>

                                          <div class="form-group validated col-sm-8 col-lg-3">
                                              <label class="col-form-label">Celular (Opcional)</label>
                                              <input id="celular" type="text" class="form-control" name="celular" value="{{{ isset($forn) ? $forn->celular : old('celular') }}}">
                                          </div>

                                          <div class="form-group validated col-sm-8 col-lg-4">
                                              <label class="col-form-label">Chave PIX (Opcional)</label>
                                              <input id="pix" type="text" class="form-control" name="pix" value="{{{ isset($forn) ? $forn->pix : old('pix') }}}">
                                          </div>

                                          {{-- Removi a classe d-none para garantir visibilidade ao carregar --}}
                                          <div class="form-group validated col-sm-8 t-pix col-lg-2">
                                              <label class="col-form-label">Tipo PIX</label>
                                              <select class="form-control" name="tipo_pix">
                                                  <option value="">--</option>
                                                  @foreach(App\Models\Fornecedor::tiposDePix() as $tp)
                                                  <option @isset($forn) @if($forn->tipo_pix == $tp) selected @endif @endif value="{{$tp}}">{{ strtoupper($tp) }}</option>
                                                  @endforeach
                                              </select>
                                          </div>
                                        
                                        <div class="row">
                                              <div class="form-group col-lg-6">
                                                  <label>Tabela de Preços (Para Pesagem)</label>
                                                  <select name="tabela_preco_id" class="form-control custom-select">
                                                      <option value="">Nenhuma (Preço Manual)</option>
                                                      @foreach(\App\Models\TabelaPreco::where('empresa_id', session('user_logged')['empresa'])->get() as $tabela)
                                                          <option value="{{ $tabela->id }}" 
                                                              {{ (isset($forn) && $forn->tabela_preco_id == $tabela->id) ? 'selected' : '' }}>
                                                              {{ $tabela->descricao }}
                                                          </option>
                                                      @endforeach
                                                  </select>
                                                  <span class="form-text text-muted">Selecione a tabela que define os preços de compra para este fornecedor.</span>
                                              </div>
                                          </div>
                                      </div>
									</div>

								</div>

							</div>
						</div>
					</div>
				</div>
				<div class="card-footer">

					<div class="row">
						<div class="col-xl-2">

						</div>
						<div class="col-lg-3 col-sm-6 col-md-4">
							<a style="width: 100%" class="btn btn-danger" href="/fornecedores">
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

@section('javascript')
    {{-- IMask para CPF/CNPJ (caret estável) --}}
    <script src="https://unpkg.com/imask"></script>
    {{-- (Opcional) Inputmask só para CEP; não encostamos no CPF/CNPJ --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.9/jquery.inputmask.min.js"></script>

    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function(){
            const docEl   = document.getElementById('cpf_cnpj');
            const $cep    = $('#cep');
            const $lblDoc = $('#lbl_cpf_cnpj');

            const $fis = $('#pessoaFisica');   // CPF
            const $jur = $('#pessoaJuridica'); // CNPJ
            const $ext = $('#pessoaExt');      // CNPJ (Exterior)

            // --- GARANTIR que nenhuma outra máscara esteja no campo ---
            try { $('#cpf_cnpj').inputmask('remove'); } catch(e){}
            // remove listeners legados que possam ter sido anexados
            $('#cpf_cnpj').off();

            // --- IMask no CPF/CNPJ ---
            let docMask = null;
            function setDocMask(tipo){
                // destrói qualquer instância anterior
                if (docMask) { docMask.destroy(); docMask = null; }

                // sempre liberamos edição; só no Exterior decidimos depois
                docEl.removeAttribute('readonly');

                if (tipo === 'cpf') {
                    $lblDoc.text('CPF');
                    docMask = IMask(docEl, {
                        mask: '000.000.000-00',
                        lazy: true,           // mostra só conforme digita (mais leve)
                        overwrite: 'shift',   // não come o próximo dígito; caret estável
                        // evita travas estranhas com mobile
                        blocks: { 0: { placeholderChar: '_' } }
                    });
                } else if (tipo === 'cnpj') {
                    $lblDoc.text('CNPJ');
                    docMask = IMask(docEl, {
                        mask: '00.000.000/0000-00',
                        lazy: true,
                        overwrite: 'shift',
                        blocks: { 0: { placeholderChar: '_' } }
                    });
                } else { // cnpj_ext
                    $lblDoc.text('CNPJ (Exterior)');
                    docMask = IMask(docEl, {
                        mask: '00.000.000/0000-00',
                        lazy: true,
                        overwrite: 'shift',
                        blocks: { 0: { placeholderChar: '_' } }
                    });
                    // valor fixo conforme seu requisito
                    docMask.value = '00.000.000/0000-00';
                    docEl.setAttribute('readonly', 'readonly'); // só Exterior fica readonly
                }
            }

            function currentTipo(){
                if ($ext.prop('checked')) return 'cnpj_ext';
                if ($jur.prop('checked')) return 'cnpj';
                return 'cpf';
            }

            // --- CEP com Inputmask (independente do CPF/CNPJ) ---
            try {
                $cep.inputmask('99999-999', {
                    removeMaskOnSubmit: true,
                    showMaskOnFocus: true,
                    showMaskOnHover: false,
                    jitMasking: true,
                    placeholder: '_'
                });
            } catch(e){}

            // --- Inicialização coerente (edição/old) ---
            (function init(){
                const raw = (docEl.value || '').replace(/\D/g,'');
                const isZeros14 = raw.length === 14 && /^0{14}$/.test(raw);

                if (!$fis.is(':checked') && !$jur.is(':checked') && !$ext.is(':checked')) {
                    if (isZeros14 || docEl.value === '00.000.000/0000-00') {
                        $ext.prop('checked', true);
                    } else if (raw.length === 14) {
                        $jur.prop('checked', true);
                    } else {
                        $fis.prop('checked', true);
                    }
                }
                setDocMask(currentTipo());

                if (currentTipo() === 'cnpj_ext') {
                    const d = (docEl.value || '').replace(/\D/g,'');
                    if (!d.length || /^0{14}$/.test(d)) {
                        docMask.value = '00.000.000/0000-00';
                    }
                }
            })();

            // --- Troca de rádios (apenas aqui trocamos a máscara) ---
            $fis.on('change', function(){ if (this.checked){ setDocMask('cpf'); } });
            $jur.on('change', function(){ if (this.checked){ setDocMask('cnpj'); } });
            $ext.on('change', function(){ if (this.checked){ setDocMask('cnpj_ext'); } });

            // --- Limpar com Ctrl+A + Delete/Backspace sem travar caret ---
            docEl.addEventListener('keydown', function(e){
                const key = e.key || e.keyCode;
                const del = (key === 'Delete' || key === 'Backspace' || key === 46 || key === 8);
                if (!del) return;

                const start = docEl.selectionStart, end = docEl.selectionEnd;
                if (typeof start === 'number' && typeof end === 'number' && end > start) {
                    if (start === 0 && end === docEl.value.length) {
                        // reset amigável: recria a máscara "limpa" do tipo atual
                        e.preventDefault();
                        const tipo = currentTipo();
                        setDocMask(tipo);
                        if (tipo === 'cnpj_ext') docMask.value = '00.000.000/0000-00';
                    }
                }
            });

            // (Opcional) Dica leve – não interfere na digitação
            function showDocHint(){
                $('#doc-feedback-inline').remove();
                const tipo = currentTipo();
                const dig = (docEl.value || '').replace(/\D/g,'');
                if (!dig.length) return;
                let ok = false, msg = '';
                if (tipo === 'cpf') {
                    ok = dig.length === 11;
                    msg = ok ? 'Campo no formato de CPF (11 dígitos).' : 'CPF inválido (tamanho).';
                } else { // cnpj/cnpj_ext
                    ok = dig.length === 14 || (tipo === 'cnpj_ext' && (dig === '' || /^0{14}$/.test(dig)));
                    msg = ok ? 'Campo no formato de CNPJ (14 dígitos).' : 'CNPJ inválido (tamanho).';
                }
                $('#cpf_cnpj').after(`<small id="doc-feedback-inline" class="${ok?'text-success':'text-danger'}">${msg}</small>`);
            }
            $('#cpf_cnpj').on('blur input', showDocHint);

            // PIX (sua lógica)
            function pixDigita(){
                if($('#pix').val().trim().length > 0){
                    $('.t-pix').removeClass('d-none')
                }else{
                    $('.t-pix').addClass('d-none')
                }
            }
            $('#pix').on('keyup', pixDigita);
            pixDigita();
        });
    </script>
@endsection
