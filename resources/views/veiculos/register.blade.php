@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<!-- <form method="post" action="/veiculos/{{{ isset($veiculo) ? 'update' : 'save' }}}"> -->
                <form method="post" action="/veiculos/{{{ isset($veiculo) ? 'update' : 'save' }}}" enctype="multipart/form-data">

                    <input type="hidden" name="id" value="{{{ isset($veiculo) ? $veiculo->id : 0 }}}">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<h3 class="card-title">{{isset($veiculo) ? 'Editar' : 'Novo'}} Veículo</h3>
						</div>
					</div>
					@csrf

					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">
										<div class="form-group validated col-sm-10 col-lg-2">
											<label class="col-form-label">Placa</label>
											<div class="">
												<input id="placa" type="text" class="form-control @if($errors->has('placa')) is-invalid @endif" name="placa" value="{{{ isset($veiculo) ? $veiculo->placa : old('placa') }}}">
												@if($errors->has('placa'))
												<div class="invalid-feedback">
													{{ $errors->first('placa') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-lg-2 col-md-2 col-sm-6">
											<label class="col-form-label">UF</label>

											<select class="custom-select form-control" id="sigla_uf" name="uf">
												@foreach($ufs as $u)
												<option @if(isset($veiculo)) @if($u==$veiculo->uf)
													selected
													@endif
													@endisset
													value="{{$u}}">{{$u}}</option>
												@endforeach
											</select>
										</div>

										<div class="form-group validated col-sm-10 col-lg-2">
											<label class="col-form-label">Cor</label>
											<div class="">
												<input id="cor" type="text" class="form-control @if($errors->has('cor')) is-invalid @endif" name="cor" value="{{{ isset($veiculo) ? $veiculo->cor : old('cor') }}}">
												@if($errors->has('cor'))
												<div class="invalid-feedback">
													{{ $errors->first('cor') }}
												</div>
												@endif
											</div>
										</div>

                                        <div class="form-group validated col-sm-10 col-lg-2">
                                            <label class="col-form-label">Marca</label>
                                            <input type="text" class="form-control" name="marca" value="{{ $veiculo->marca ?? old('marca') }}">
                                        </div>

                                        <div class="form-group validated col-sm-10 col-lg-2">
                                            <label class="col-form-label">Modelo</label>
                                            <input type="text" class="form-control" name="modelo" value="{{ $veiculo->modelo ?? old('modelo') }}">
                                        </div>

                                        <div class="form-group validated col-sm-10 col-lg-2">
                                            <label class="col-form-label">Combustível</label>
                                            <div class="">
                                                <input id="cor" type="text" class="form-control @if($errors->has('combustivel')) is-invalid @endif" name="combustivel" value="{{{ isset($veiculo) ? $veiculo->combustivel : old('combustivel') }}}">
                                                @if($errors->has('combustivel'))
                                                    <div class="invalid-feedback">
                                                        {{ $errors->first('combustivel') }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="form-group validated col-sm-10 col-lg-4">
                                            <label class="col-form-label">Chassi</label>
                                            <input type="text" class="form-control" name="chassi" value="{{ $veiculo->chassi ?? old('chassi') }}">
                                        </div>

                                        <div class="form-group validated col-sm-10 col-lg-2">
                                            <label class="col-form-label">Quilometragem Atual</label>
                                            <input type="number" step="0.01" class="form-control @if($errors->has('quilometragem')) is-invalid @endif" name="quilometragem" value="{{ old('quilometragem', $veiculo->quilometragem ?? '') }}">
                                            @if($errors->has('quilometragem'))
                                                <div class="invalid-feedback">{{ $errors->first('quilometragem') }}</div>
                                            @endif
                                        </div>

                                        <div class="form-group validated col-sm-10 col-lg-2">
                                            <label class="col-form-label">KM Últ. Manutenção</label>
                                            <input type="number" step="0.01" class="form-control @if($errors->has('quilometragem_ultima_manutencao')) is-invalid @endif" name="quilometragem_ultima_manutencao" value="{{ old('quilometragem_ultima_manutencao', $veiculo->quilometragem_ultima_manutencao ?? '') }}">
                                            @if($errors->has('quilometragem_ultima_manutencao'))
                                                <div class="invalid-feedback">{{ $errors->first('quilometragem_ultima_manutencao') }}</div>
                                            @endif
                                        </div>

                                        <div class="form-group validated col-sm-10 col-lg-2">
                                            <label class="col-form-label">KM Próx. Manutenção</label>
                                            <input type="number" step="0.01" class="form-control @if($errors->has('proxima_manutencao_km')) is-invalid @endif" name="proxima_manutencao_km" value="{{ old('proxima_manutencao_km', $veiculo->proxima_manutencao_km ?? '') }}">
                                            @if($errors->has('proxima_manutencao_km'))
                                                <div class="invalid-feedback">{{ $errors->first('proxima_manutencao_km') }}</div>
                                            @endif
                                        </div>

                                        <div class="form-group validated col-sm-10 col-lg-2">
                                            <label class="col-form-label">Status da Manutenção</label>
                                            <select class="custom-select form-control @if($errors->has('status_manutencao')) is-invalid @endif" name="status_manutencao">
                                                @foreach(($statusManutencao ?? ['Em dia', 'Atenção', 'Em manutenção']) as $status)
                                                    <option value="{{ $status }}" @if(old('status_manutencao', $veiculo->status_manutencao ?? 'Em dia') == $status) selected @endif>{{ $status }}</option>
                                                @endforeach
                                            </select>
                                            @if($errors->has('status_manutencao'))
                                                <div class="invalid-feedback">{{ $errors->first('status_manutencao') }}</div>
                                            @endif
                                        </div>

                                        <div class="form-group validated col-sm-10 col-lg-2">
                                            <label class="col-form-label">Ano Fabricação</label>
                                            <input type="text" class="form-control" name="ano_fabricacao" value="{{ $veiculo->ano_fabricacao ?? old('ano_fabricacao') }}">
                                        </div>

                                        <div class="form-group validated col-sm-10 col-lg-2">
                                            <label class="col-form-label">Ano Modelo</label>
                                            <input type="text" class="form-control" name="ano_modelo" value="{{ $veiculo->ano_modelo ?? old('ano_modelo') }}">
                                        </div>

										<div class="form-group validated col-sm-10 col-lg-2">
											<label class="col-form-label">RNTRC</label>
											<div class="">
												<input id="cor" type="text" class="form-control @if($errors->has('rntrc')) is-invalid @endif" name="rntrc" value="{{{ isset($veiculo) ? $veiculo->rntrc : old('rntrc') }}}">
												@if($errors->has('rntrc'))
												<div class="invalid-feedback">
													{{ $errors->first('rntrc') }}
												</div>
												@endif
											</div>
										</div>

                                        <div class="form-group validated col-sm-10 col-lg-2">
                                            <label class="col-form-label">Traccar ID</label>
                                            <div class="">
                                                <input id="cor" type="text" class="form-control @if($errors->has('traccar_id')) is-invalid @endif" name="traccar_id" value="{{{ isset($veiculo) ? $veiculo->traccar_id : old('traccar_id') }}}">
                                                @if($errors->has('traccar_id'))
                                                    <div class="invalid-feedback">
                                                        {{ $errors->first('traccar_id') }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="form-group validated col-sm-10 col-lg-2">
                                            <label class="col-form-label">Data Últ. Manutenção</label>
                                            <input type="date" class="form-control @if($errors->has('data_ultima_manutencao')) is-invalid @endif" name="data_ultima_manutencao" value="{{ old('data_ultima_manutencao', isset($veiculo) ? optional($veiculo->data_ultima_manutencao)->format('Y-m-d') : '') }}">
                                            @if($errors->has('data_ultima_manutencao'))
                                                <div class="invalid-feedback">{{ $errors->first('data_ultima_manutencao') }}</div>
                                            @endif
                                        </div>

                                        <div class="form-group validated col-sm-10 col-lg-2">
                                            <label class="col-form-label">Data Próx. Revisão</label>
                                            <input type="date" class="form-control @if($errors->has('data_proxima_revisao')) is-invalid @endif" name="data_proxima_revisao" value="{{ old('data_proxima_revisao', isset($veiculo) ? optional($veiculo->data_proxima_revisao)->format('Y-m-d') : '') }}">
                                            @if($errors->has('data_proxima_revisao'))
                                                <div class="invalid-feedback">{{ $errors->first('data_proxima_revisao') }}</div>
                                            @endif
                                        </div>

										<div class="form-group validated col-sm-10 col-lg-3">
											<label class="col-form-label">Renavam</label>
											<div class="">
												<input id="cor" type="text" class="form-control @if($errors->has('renavam')) is-invalid @endif" name="renavam" value="{{{ isset($veiculo) ? $veiculo->renavam : old('renavam') }}}">
												@if($errors->has('renavam'))
												<div class="invalid-feedback">
													{{ $errors->first('renavam') }}
												</div>
												@endif
											</div>
										</div>
										<div class="form-group validated col-sm-10 col-lg-3">
											<label class="col-form-label">TAF</label>
											<div class="">
												<input id="cor" type="text" class="form-control @if($errors->has('taf')) is-invalid @endif" name="taf" value="{{{ isset($veiculo) ? $veiculo->taf : old('taf') }}}">
												@if($errors->has('taf'))
												<div class="invalid-feedback">
													{{ $errors->first('taf') }}
												</div>
												@endif
											</div>
										</div>

                                                                                <div class="form-group validated col-sm-10 col-lg-3">
                                                                                        <label class="col-form-label">Nº registro estadual</label>
                                                                                        <div class="">
                                                                                                <input id="cor" type="text" class="form-control @if($errors->has('numero_registro_estadual')) is-invalid @endif" name="numero_registro_estadual" value="{{{ isset($veiculo) ? $veiculo->numero_registro_estadual : old('numero_registro_estadual') }}}">
                                                                                                @if($errors->has('numero_registro_estadual'))
                                                                                                <div class="invalid-feedback">
                                                                                                        {{ $errors->first('numero_registro_estadual') }}
                                                                                                </div>
                                                                                                @endif
                                                                                        </div>
                                                                                </div>

                                        <div class="form-group validated col-sm-12 col-lg-6">
                                            <label class="col-form-label">Observações de Manutenção</label>
                                            <textarea class="form-control @if($errors->has('observacoes_manutencao')) is-invalid @endif" name="observacoes_manutencao" rows="3">{{ old('observacoes_manutencao', $veiculo->observacoes_manutencao ?? '') }}</textarea>
                                            @if($errors->has('observacoes_manutencao'))
                                                <div class="invalid-feedback">{{ $errors->first('observacoes_manutencao') }}</div>
                                            @endif
                                        </div>

                                                                                <div class="form-group validated col-lg-3 col-md-6 col-sm-6">
                                                                                        <label class="col-form-label">Tipo do Veículo</label>

                                                                                        <select class="custom-select form-control" id="tipo" name="tipo">
												@foreach($tipos as $key => $t)
												<option @isset($veiculo) @if($key==$veiculo->tipo)
													selected
													@endif
													@endisset
													value="{{$key}}">{{$key}} - {{$t}}</option>
												@endforeach
											</select>
										</div>

										<div class="form-group validated col-lg-3 col-md-6 col-sm-6">
											<label class="col-form-label">Tipo de Carroceria</label>

											<select class="custom-select form-control" id="tipo_carroceira" name="tipo_carroceira">
												@foreach($tiposCarroceria as $key => $t)
												<option @isset($veiculo) @if($key==$veiculo->tipo_carroceira)
													selected
													@endif
													@endisset
													value="{{$key}}">{{$key}} - {{$t}}</option>
												@endforeach
											</select>
										</div>

										<div class="form-group validated col-lg-3 col-md-6 col-sm-6">
											<label class="col-form-label">Tipo de Rodado</label>

											<select class="custom-select form-control" id="tipo_rodado" name="tipo_rodado">
												@foreach($tiposRodado as $key => $t)
												<option @isset($veiculo) @if($key==$veiculo->tipo_rodado)
													selected
													@endif
													@endisset
													value="{{$key}}">{{$key}} - {{$t}}</option>
												@endforeach
											</select>
										</div>

										<div class="form-group validated col-sm-10 col-lg-2">
											<label class="col-form-label">Tara</label>
											<div class="">
												<input id="tara" type="text" class="form-control @if($errors->has('tara')) is-invalid @endif" name="tara" value="{{{ isset($veiculo) ? $veiculo->tara : old('tara') }}}">
												@if($errors->has('tara'))
												<div class="invalid-feedback">
													{{ $errors->first('tara') }}
												</div>
												@endif
											</div>
										</div>
										<div class="form-group validated col-sm-10 col-lg-2">
											<label class="col-form-label">Capacidade</label>
											<div class="">
												<input id="capacidade" type="text" class="form-control @if($errors->has('capacidade')) is-invalid @endif" name="capacidade" value="{{{ isset($veiculo) ? $veiculo->capacidade : old('capacidade') }}}">
												@if($errors->has('capacidade'))
												<div class="invalid-feedback">
													{{ $errors->first('capacidade') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-10 col-lg-3">
											<label class="col-form-label">Nome Proprietário</label>
											<div class="">
												<input id="proprietario_nome" type="text" class="form-control @if($errors->has('proprietario_nome')) is-invalid @endif" name="proprietario_nome" value="{{{ isset($veiculo) ? $veiculo->proprietario_nome : old('proprietario_nome') }}}">
												@if($errors->has('proprietario_nome'))
												<div class="invalid-feedback">
													{{ $errors->first('proprietario_nome') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-lg-2 col-md-2 col-sm-2">
											<label class="col-form-label">Pessoa</label>

											<select class="custom-select form-control" id="tipo-prop" name="prop">
												<option value="j">Juridica</option>
												<option value="f">Fisica</option>
											</select>
										</div>
										<div class="form-group validated col-sm-10 col-lg-2">
											<label class="col-form-label tipo-doc">CNPJ Proprietário</label>
											<div class="">
												<input id="proprietario_documento" type="text" class="form-control @if($errors->has('proprietario_documento')) is-invalid @endif" name="proprietario_documento" value="{{{ isset($veiculo) ? $veiculo->proprietario_documento : old('proprietario_documento') }}}">
												@if($errors->has('proprietario_documento'))
												<div class="invalid-feedback">
													{{ $errors->first('proprietario_documento') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-10 col-lg-2">
											<label class="col-form-label tipo-ie">IE Proprietário</label>
											<div class="">
												<input id="proprietario_ie" type="text" class="form-control @if($errors->has('proprietario_ie')) is-invalid @endif" name="proprietario_ie" value="{{{ isset($veiculo) ? $veiculo->proprietario_ie : old('proprietario_ie') }}}">
												@if($errors->has('proprietario_ie'))
												<div class="invalid-feedback">
													{{ $errors->first('proprietario_ie') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-lg-2 col-md-6 col-sm-6">
											<label class="col-form-label">UF Proprietário</label>

											<select class="custom-select form-control" id="proprietario_uf" name="proprietario_uf">
												@foreach($ufs as $key => $u)
												<option @isset($veiculo) @if($key==$veiculo->proprietario_uf)
													selected
													@endif
													@endisset
													value="{{$key}}">{{$u}}</option>
												@endforeach
											</select>
										</div>

										<div class="form-group validated col-lg-3 col-md-6 col-sm-6">
											<label class="col-form-label">Tipo do Proprietário</label>

											<select class="custom-select form-control" id="proprietario_tp" name="proprietario_tp">
												@foreach($tiposProprietario as $key => $t)
												<option @isset($veiculo) @if($key==$veiculo->proprietario_tp)
													selected
													@endif
													@endisset
													value="{{$key}}">{{$key}} - {{$t}}</option>
												@endforeach
											</select>
										</div>

                                        <div class="form-group validated col-lg-4 col-md-6 col-sm-6">
                                            <label class="col-form-label">Motorista</label>
                                            <select class="custom-select form-control @if($errors->has('motorista_id')) is-invalid @endif" name="motorista_id">
                                                <option value="">-- Selecione o Motorista --</option>
                                                @foreach($motoristas as $motorista)
                                                    <option value="{{ $motorista->id }}" @if(isset($veiculo) && $veiculo->motorista_id == $motorista->id) selected @endif>
                                                        {{ $motorista->nome }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @if($errors->has('motorista_id'))
                                                <div class="invalid-feedback">
                                                    {{ $errors->first('motorista_id') }}
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Usando o componente x-image-upload para o upload de imagem -->
                                        <div class="col-12"> </div>
                                        <x-image-upload
                                            :image-url="isset($veiculo->foto_veiculo) ? '/imgs_veiculos/'.$veiculo->foto_veiculo : '/imgs/no_image.png'"
                                            title="Foto Veículo"
                                            input-name="file"
                                        />

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
						<a style="width: 100%" class="btn btn-danger" href="/veiculos">
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

<!-- Inclua o modal fora do form para evitar problemas com iframes -->
<x-image-modal
    :image-url="isset($veiculo->foto_veiculo) ? '/imgs_veiculos/'.$veiculo->foto_veiculo : '/imgs/no_image.png'"
    title="Foto Veículo"
/>

@endsection
