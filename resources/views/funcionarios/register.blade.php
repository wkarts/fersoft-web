@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="card card-custom gutter-b example example-compact">
        <div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
            <div class="col-lg-12">
                <br>
                <form method="post" action="/funcionarios/{{{ isset($funcionario) ? 'update' : 'save' }}}" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="{{{ isset($funcionario) ? $funcionario->id : 0 }}}">
                    <div class="row align-items-center">
                        <div class="col-lg-2 col-md-7 col-sm-12">
                            <div class="card card-custom gutter-b example example-compact">
                                <div class="card-header text-center">
                                    <h3 class="card-title">{{ isset($funcionario) ? 'Editar' : 'Novo' }} Funcionário</h3>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-10 col-md-5 col-sm-12">
                            <x-image-upload :image-url="isset($funcionario->foto_funcionario) ? '/imgs_funcionarios/'.$funcionario->foto_funcionario : '/imgs/no_image.png'" title="Foto Funcionário" input-name="file" />
                        </div>

                    </div>
                    @csrf
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="kt-section kt-section--first">
                                <div class="kt-section__body">

                                    <div class="row">
                                        <div class="form-group validated col-sm-10 col-lg-6">
                                            <label class="col-form-label">Nome</label>
                                            <input id="nome" type="text" class="form-control @if($errors->has('nome')) is-invalid @endif" name="nome" value="{{{ isset($funcionario) ? $funcionario->nome : old('nome') }}}">
                                            @if($errors->has('nome'))
                                            <div class="invalid-feedback">{{ $errors->first('nome') }}</div>
                                            @endif
                                        </div>
                                        <div class="form-group validated col-sm-3 col-lg-3">
                                            <label class="col-form-label">CPF</label>
                                            <input type="text" id="cpf" class="form-control @if($errors->has('cpf')) is-invalid @endif" name="cpf" value="{{{ isset($funcionario) ? $funcionario->cpf : old('cpf') }}}">
                                            @if($errors->has('cpf'))
                                            <div class="invalid-feedback">{{ $errors->first('cpf') }}</div>
                                            @endif
                                        </div>

                                        <div class="form-group validated col-sm-3 col-lg-3">
                                            <label class="col-form-label">RG</label>
                                            <input type="text" id="rg" class="form-control @if($errors->has('rg')) is-invalid @endif" name="rg" value="{{{ isset($funcionario) ? $funcionario->rg : old('rg') }}}">
                                            @if($errors->has('rg'))
                                            <div class="invalid-feedback">{{ $errors->first('rg') }}</div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group col-lg-2 col-md-6 col-sm-12">
                                            <label class="col-form-label">Data de Nascimento</label>
                                            <div class="input-group date">
                                                <input type="text" name="data_nascimento" class="form-control" readonly value="{{ isset($funcionario->data_nascimento) ? \Carbon\Carbon::parse($funcionario->data_nascimento)->format('d/m/Y') : old('data_nascimento') }}" id="kt_datepicker_3" />
                                                <div class="input-group-append"><span class="input-group-text"><i class="la la-calendar"></i></span></div>
                                            </div>
                                        </div>

                                        <div class="form-group col-lg-2 col-md-6 col-sm-12">
                                            <label class="col-form-label">Data de Admissão</label>
                                            <div class="input-group date">
                                                <input type="text" name="data_admissao" class="form-control" readonly value="{{ isset($funcionario->data_admissao) ? \Carbon\Carbon::parse($funcionario->data_admissao)->format('d/m/Y') : old('data_admissao') }}" id="kt_datepicker_3" />
                                                <div class="input-group-append"><span class="input-group-text"><i class="la la-calendar"></i></span></div>
                                            </div>
                                        </div>

                                        <div class="form-group col-lg-2 col-md-6 col-sm-12">
                                            <label class="col-form-label">Tipo Sanguíneo</label>
                                            <select class="custom-select" name="tipo_sanguineo">
                                                <option value="">Selecione</option>
                                                @foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $tipo)
                                                <option value="{{ $tipo }}" {{ isset($funcionario) && $funcionario->tipo_sanguineo == $tipo ? 'selected' : '' }}>{{ $tipo }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="form-group col-lg-3 col-md-6 col-sm-12">
                                            <label class="col-form-label">Status do Funcionário</label>
                                            <select class="custom-select" name="status_funcionario">
                                                <option value="Ativo" {{ isset($funcionario) && $funcionario->status_funcionario == 'Ativo' ? 'selected' : '' }}>Ativo</option>
                                                <option value="Desligado" {{ isset($funcionario) && $funcionario->status_funcionario == 'Desligado' ? 'selected' : '' }}>Desligado</option>
                                            </select>
                                        </div>

                                        <div class="form-group col-lg-3 col-md-12 col-sm-12">
                                            <label class="col-form-label">Número de Registro</label>
                                            <input type="text" name="numero_registro" class="form-control" value="{{ isset($funcionario->numero_registro) ? $funcionario->numero_registro : old('numero_registro') }}">
                                        </div>
                                    </div>

                                    <hr>

                                    <div class="row">
                                        <div class="form-group validated col-sm-8 col-lg-4">
                                            <label class="col-form-label">Rua</label>
                                            <input id="rua" type="text" class="form-control @if($errors->has('rua')) is-invalid @endif" name="rua" value="{{{ isset($funcionario) ? $funcionario->rua : old('rua') }}}">
                                        </div>
                                        <div class="form-group validated col-sm-2 col-lg-2">
                                            <label class="col-form-label">Número</label>
                                            <input id="numero" type="text" class="form-control @if($errors->has('numero')) is-invalid @endif" name="numero" value="{{{ isset($funcionario) ? $funcionario->numero : old('numero') }}}">
                                        </div>
                                        <div class="form-group validated col-sm-8 col-lg-3">
                                            <label class="col-form-label">Bairro</label>
                                            <input id="bairro" type="text" class="form-control @if($errors->has('bairro')) is-invalid @endif" name="bairro" value="{{{ isset($funcionario) ? $funcionario->bairro : old('bairro') }}}">
                                        </div>
                                        <div class="form-group validated col-sm-8 col-lg-3">
                                            <label class="col-form-label">Email</label>
                                            <input id="email" type="text" class="form-control @if($errors->has('email')) is-invalid @endif" name="email" value="{{{ isset($funcionario) ? $funcionario->email : old('email') }}}">
                                        </div>
                                    </div>

                                   <div class="row">
										<div class="form-group validated col-sm-8 col-lg-2">
											<label class="col-form-label">Telefone</label>
											<input id="telefone" type="text" class="form-control @if($errors->has('telefone')) is-invalid @endif" name="telefone" value="{{{ isset($funcionario) ? $funcionario->telefone : old('telefone') }}}">
										</div>
										<div class="form-group validated col-sm-8 col-lg-2">
											<label class="col-form-label">Celular</label>
											<input id="celular" type="text" class="form-control @if($errors->has('celular')) is-invalid @endif" name="celular" value="{{{ isset($funcionario) ? $funcionario->celular : old('celular') }}}">
										</div>

                                        <div class="form-group col-lg-2 col-md-9 col-sm-12">
											<label class="col-form-label">Data de Registro</label>
											<div class="">
												<div class="input-group date">
													<input type="text" name="data_registro" class="form-control @if($errors->has('data_registro')) is-invalid @endif" readonly value="{{{ isset($funcionario->data_registro) ? \Carbon\Carbon::parse($funcionario->data_registro)->format('d/m/Y') : old('data_registro') }}}" id="kt_datepicker_3" />
													<div class="input-group-append">
														<span class="input-group-text">
															<i class="la la-calendar"></i>
														</span>
													</div>
												</div>
												@if($errors->has('data_registro'))
												<div class="invalid-feedback">
													{{ $errors->first('data_registro') }}
												</div>
												@endif

											</div>
										</div>
										<div class="form-group validated col-sm-8 col-lg-2">
											<label class="col-form-label">Salário</label>
											<input id="salario" type="text" class="form-control money" name="salario" value="{{{ isset($funcionario) ? $funcionario->salario : old('salario') }}}">
										</div>

										<div class="form-group validated col-sm-8 col-lg-2">
											<label class="col-form-label">Comissão (%)</label>
											<input id="percentual_comissao" type="text" class="form-control money" name="percentual_comissao" value="{{{ isset($funcionario) ? $funcionario->percentual_comissao : old('percentual_comissao') }}}">
										</div>

                                        <div class="form-group validated col-sm-8 col-lg-2">
                                            <label class="col-form-label">Função</label>
                                            <div class="input-group">
                                                <select class="form-control custom-select select2" name="funcao_id" id="funcao_id">
                                                    <option value="">Selecione</option>
                                                    @foreach($funcoes as $f)
                                                        <option value="{{$f->id}}" @if(isset($funcionario) && $funcionario->funcao_id == $f->id) selected @endif>{{$f->nome}}</option>
                                                    @endforeach
                                                </select>
                                                <div class="input-group-append">
                                                    <button class="btn btn-primary" type="button" data-toggle="modal" data-target="#modal_funcao">
                                                        <i class="la la-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group validated col-sm-8 col-lg-2">
                                            <label class="col-form-label">Unidade/Filial</label>
                                            <select class="form-control custom-select" name="filial_id">
                                                <option value="">MATRIZ</option>
                                                @foreach($filiais as $f)
                                                    <option value="{{$f->id}}" @if(isset($funcionario) && $funcionario->filial_id == $f->id) selected @endif>
                                                        {{ $f->razao_social ?? $f->nome_fantasia ?? $f->descricao ?? $f->nome ?? 'Filial ' . $f->id }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
									</div> <div class="row">
										<div class="form-group validated col-sm-8 col-lg-4">
											<label class="col-form-label">Usuário (opcional)</label>
											<select class="form-control custom-select" name="usuario_id">
												<option value="NULL">--</option>
												@foreach($usuarios as $u)
												<option value="{{$u->id}}" @if(isset($funcionario) && $funcionario->usuario_id == $u->id) selected @endif>{{$u->nome}}</option>
												@endforeach
											</select>
										</div>
                                        <div class="form-group col-lg-3 col-md-6 col-sm-12">
                                            <label class="col-form-label">Matrícula (Ponto/AFD)</label>
                                            <input type="text" name="matricula" class="form-control" value="{{ isset($funcionario->matricula) ? $funcionario->matricula : old('matricula') }}">
                                        </div>
                                        <div class="form-group col-lg-3 col-md-6 col-sm-12">
                                            <label class="col-form-label">PIS</label>
                                            <input type="text" name="pis" class="form-control" value="{{ isset($funcionario->pis) ? $funcionario->pis : old('pis') }}">
                                        </div>
									</div>

                                    <div id="secao_motorista" style="display: none;">
                                                <div class="card card-custom gutter-b example example-compact" style="max-height: 30px; margin: 20px auto; display: flex; justify-content: center;">
                                                    <div class="card-header"><h3 class="card-title">Dados de Motorista</h3></div>
                                                </div>

                                                <div class="row">
                                                    <div class="form-group validated col-sm-9 col-lg-3">
                                                        <label class="col-form-label">CNH</label>
                                                        <input id="cnh" type="text" class="form-control" name="cnh" value="{{{ isset($funcionario) ? $funcionario->cnh : old('cnh') }}}">
                                                    </div>

                                                    <div class="form-group col-lg-3 col-md-9 col-sm-12">
                                                        <label class="col-form-label">Categoria</label>
                                                        <select class="custom-select form-control" name="categoria_cnh">
                                                            <option value="">--</option>
                                                            @foreach(['A','B','C','D','E', 'AB', 'AC', 'AD', 'AE'] as $cat)
                                                            <option value="{{$cat}}" @if(isset($funcionario) && $funcionario->categoria_cnh == $cat) selected @endif>{{$cat}}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    <div class="form-group col-lg-3 col-md-9 col-sm-12">
                                                        <label class="col-form-label">Vencimento CNH</label>
                                                        <div class="input-group date">
                                                            <input type="text" name="vencimento_cnh" class="form-control" readonly value="{{ isset($funcionario->vencimento_cnh) && $funcionario->vencimento_cnh ? \Carbon\Carbon::parse($funcionario->vencimento_cnh)->format('d/m/Y') : old('vencimento_cnh') }}" id="kt_datepicker_3" />
                                                            <div class="input-group-append"><span class="input-group-text"><i class="la la-calendar"></i></span></div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group col-lg-3 col-md-9 col-sm-12">
                                                        <label class="col-form-label">Status do Motorista</label>
                                                        <select class="custom-select form-control" name="status_motorista">
                                                            <option value="Ativo" @if(isset($funcionario) && $funcionario->status_motorista == 'Ativo') selected @endif>Ativo</option>
                                                            <option value="Inativo" @if(isset($funcionario) && $funcionario->status_motorista == 'Inativo') selected @endif>Inativo</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group col-lg-3 col-md-9 col-sm-12">
                                                <label class="col-form-label">Vencimento CNH</label>
                                                <div class="input-group date">
                                                    <input type="text" name="vencimento_cnh" class="form-control" readonly value="{{ isset($funcionario->vencimento_cnh) ? \Carbon\Carbon::parse($funcionario->vencimento_cnh)->format('d/m/Y') : old('vencimento_cnh') }}" id="kt_datepicker_3" />
                                                    <div class="input-group-append"><span class="input-group-text"><i class="la la-calendar"></i></span></div>
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
							<div class="col-lg-3">
								<a style="width: 100%" class="btn btn-danger" href="/funcionarios"><i class="la la-close"></i> Cancelar</a>
							</div>
							<div class="col-lg-3">
								<button style="width: 100%" type="submit" class="btn btn-success"><i class="la la-check"></i> Salvar</button>
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<x-image-modal :image-url="isset($funcionario->foto_funcionario) ? '/imgs_funcionarios/'.$funcionario->foto_funcionario : '/imgs/no_image.png'" title="Foto Funcionário" />
<div class="modal fade" id="modal_funcao" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" data-backdrop="false">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Nova Função</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<i aria-hidden="true" class="ki ki-close"></i>
				</button>
			</div>
			<div class="modal-body">
				<div class="form-group">
					<label>Nome da Função</label>
					<input type="text" id="nome_funcao_quick" class="form-control" placeholder="Ex: Gerente">
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-light-primary font-weight-bold" data-dismiss="modal">Fechar</button>
				<button type="button" id="btn_save_funcao_quick" class="btn btn-primary font-weight-bold">Salvar</button>
			</div>
		</div>
	</div>
</div>

@endsection
@section('javascript')
<script>
$(document).ready(function() {
    // 1. Garante que o Select2 seja inicializado
    if($('.select2').length) {
        $('.select2').select2();
    }

    // --- NOVA LÓGICA DO MOTORISTA AQUI ---
    function verificaMotorista() {
        // Pega o nome da função que está selecionada
        let nomeFuncao = $('#funcao_id option:selected').text().toLowerCase();
        
        // Se o nome tiver a palavra "motorista", mostra os campos com uma animação
        if (nomeFuncao.includes('motorista')) {
            $('#secao_motorista').slideDown();
        } else {
            $('#secao_motorista').slideUp();
        }
    }

    // Roda a verificação assim que a tela abre (ótimo para quando for Editar)
    verificaMotorista();

    // Roda a verificação toda vez que o usuário trocar a função no Select
    $('#funcao_id').change(function() {
        verificaMotorista();
    });
    // -------------------------------------

    // 2. Evento de clique para salvar a função nova (Já estava no seu código)
    $('#btn_save_funcao_quick').click(function() {
        let nome = $('#nome_funcao_quick').val();
        let empresa_id = "{{ $empresa_id }}"; 

        if (nome == "") {
            alert("Por favor, informe o nome da função."); 
            return;
        }

        let btn = $(this);
        btn.prop('disabled', true).text('Salvando...');

        $.post('/funcoes/quickSave', {
            _token: '{{ csrf_token() }}',
            nome: nome,
            empresa_id: empresa_id
        })
        .done(function(data) {
            if (data.success) {
                let newOption = new Option(data.nome, data.id, true, true);
                $('#funcao_id').append(newOption).trigger('change');
                
                $('#nome_funcao_quick').val('');
                $('#modal_funcao').modal('hide');
                
                alert("Função cadastrada com sucesso!");
            } else {
                alert("Erro: " + data.message);
            }
        })
        .fail(function(xhr) {
            alert("Erro de comunicação. Aperte F12 e veja o Console.");
            console.log(xhr.responseText);
        })
        .always(function() {
            btn.prop('disabled', false).text('Salvar');
        });
    });
});
</script>
@endsection