@extends('default.layout', ['title' => isset($item) ? 'Editar' : 'Nova' . ' Conta'])
@section('content')
<div class="card card-custom gutter-b">
    <div class="container">
        <form method="post" action="{{ isset($item) ? route('contas-empresa.update', [$item->id]) : route('contas-empresa.store') }}">
            @csrf
            @isset($item) @method('put') @endif
            
            <div class="card-header"><h3 class="card-title">{{ isset($item) ? 'Editar' : 'Nova' }} Conta</h3></div>
            
            <div class="card-body">
                <div class="row">
                    <div class="form-group col-md-4"><label>Nome da Conta</label><input required name="nome" class="form-control" value="{{ $item->nome ?? old('nome') }}"></div>
                    <div class="form-group col-md-4"><label>Banco</label><input name="banco" class="form-control" value="{{ $item->banco ?? old('banco') }}"></div>
                    <div class="form-group col-md-2"><label>Agência</label><input name="agencia" class="form-control" value="{{ $item->agencia ?? old('agencia') }}"></div>
                    <div class="form-group col-md-2"><label>Conta</label><input name="conta" class="form-control" value="{{ $item->conta ?? old('conta') }}"></div>
                </div>

                <div class="row">
    <div class="form-group col-md-6">
        <label>Plano de Contas (Sistema)</label>
        <select name="plano_conta_id" class="form-control custom-select" required>
            <option value="">Selecione</option>
            @foreach($planos as $p)
                <option value="{{ $p->id }}" {{ (isset($item) && $item->plano_conta_id == $p->id) ? 'selected' : '' }}>{{ $p->descricao }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group col-md-6">
        <label>Conta Contábil (Exportação Prosoft)</label>
        <select name="conta_contabil_id" class="form-control custom-select">
            <option value="">Selecione a conta...</option>
            @isset($planoContasContabeis)
                @foreach($planoContasContabeis as $c)
                    <option value="{{ $c->id }}" {{ (isset($item) && $item->conta_contabil_id == $c->id) ? 'selected' : '' }}>
                        {{ $c->classificador }} - {{ $c->nome }}
                    </option>
                @endforeach
            @endisset
        </select>
    </div>

					<div class="form-group col-md-4">
						<label>Filial / Matriz</label>
							@php
							// Transforma o NULL do banco de dados no -1 (Matriz) para o Select entender
							$filialAtual = isset($item) ? ($item->filial_id ?? -1) : -1;
							@endphp
							<select name="local" class="form-control custom-select" required>
								@foreach(__locaisAtivos() as $key => $local)
									<option value="{{ $key }}" {{ $filialAtual == $key ? 'selected' : '' }}>
										{{ $local }}
									</option>
								@endforeach
							</select>
					</div>
                  					
                  
										{{-- CAMPO DE FILIAL/MATRIZ --}}
										@if(isset($item))
											{{-- Na edição, passamos o filial_id atual. Se for NULL, o helper entende como -1 (Matriz) --}}
											{!! __view_locais_edit($item->filial_id ?? -1) !!}
										@else
											{{-- No novo cadastro, mostra as opções disponíveis para o usuário --}}
											{!! __view_locais_select() !!}
										@endif

										@if(!isset($item))
											<div class="form-group col-md-3">
												<label>Saldo Inicial</label>
												<input required name="saldo_inicial" class="form-control money" value="{{ old('saldo_inicial') }}">
											</div>
										@endif
										
	                                    <div class="form-group validated col-md-2 col-12">
											<label class="col-form-label">Status da conta</label>
											<div class="">
												<select name="status" class="custom-select">
													<option value="1" {{ (isset($item) && (int)$item->status === 1) ? 'selected' : '' }}>Ativa</option>
													<option value="0" {{ (isset($item) && (int)$item->status === 0) ? 'selected' : '' }}>Desativada</option>
													
												</select>
											</div>
										</div>

										<div class="form-group validated col-md-4 col-12">
											<label class="col-form-label d-block">Exibir no Dashboard Analítico</label>
											<div class="d-flex align-items-center">
												<span class="switch switch-outline switch-icon switch-primary mr-3">
													<label>
														<input type="checkbox" name="exibir_dashboard_analitico" value="1"
															{{ old('exibir_dashboard_analitico', isset($item) ? (int)$item->exibir_dashboard_analitico : 0) ? 'checked' : '' }}>
														<span></span>
													</label>
												</span>
												<span class="text-muted">Mostrar em Saldos Bancários</span>
											</div>
										</div>
</div>

    <div class="card-footer text-right">
            <a href="{{ route('contas-empresa.index') }}" class="btn btn-danger">Cancelar</a>
            <button type="submit" class="btn btn-success">Salvar Conta</button>
    </div>
        </form>
    </div>
</div>
@endsection
