@extends('default.layout')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/fer-modules.css') }}?v={{ filemtime(public_path('css/fer-modules.css')) }}">
@endpush

@section('content')
    <div class="d-flex flex-column flex-column-fluid" id="kt_content">
        <div class="fer-page fer-operational fer-form card card-custom gutter-b">
            <div class="card-header">
                <div class="card-title">
                    <h3 class="card-label">
                        <i class="la la-sliders-h text-primary mr-2"></i>
                        {{ $title }}
                    </h3>
                </div>
            </div>

            <div class="card-body">
                @php
                    $diasAtuais = explode(',', $parametros->dias_atendimento ?? '1,2,3,4,5');
                @endphp

                <form method="POST" action="{{ url('/logistica/parametros-agendamento/salvar') }}">
                    @csrf

                    <h5 class="font-weight-bold text-dark mb-4 border-bottom pb-2">
                        1. Disponibilidade de Veículos por Turno (Capacidade Diária)
                    </h5>
                    <div class="row">
                        <!-- TURNO MANHÃ -->
                        <div class="col-md-6 mb-4">
                            <div class="p-4 rounded" style="background: #f0fdf4; border: 1px solid #bbf7d0;">
                                <h6 class="font-weight-bold text-success mb-3">☀️ Turno da Manhã</h6>
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label class="font-weight-bold">Veículos Disponíveis</label>
                                        <input type="number" name="vagas_manha" class="form-control" min="0" value="{{ $parametros->vagas_manha ?? 3 }}" required>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label class="font-weight-bold">Início Manhã</label>
                                        <input type="time" name="horario_manha_inicio" class="form-control" value="{{ substr($parametros->horario_manha_inicio ?? '08:00', 0, 5) }}" required>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label class="font-weight-bold">Fim Manhã</label>
                                        <input type="time" name="horario_manha_fim" class="form-control" value="{{ substr($parametros->horario_manha_fim ?? '12:00', 0, 5) }}" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TURNO TARDE -->
                        <div class="col-md-6 mb-4">
                            <div class="p-4 rounded" style="background: #eff6ff; border: 1px solid #bfdbfe;">
                                <h6 class="font-weight-bold text-primary mb-3">🌤️ Turno da Tarde</h6>
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label class="font-weight-bold">Veículos Disponíveis</label>
                                        <input type="number" name="vagas_tarde" class="form-control" min="0" value="{{ $parametros->vagas_tarde ?? 3 }}" required>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label class="font-weight-bold">Início Tarde</label>
                                        <input type="time" name="horario_tarde_inicio" class="form-control" value="{{ substr($parametros->horario_tarde_inicio ?? '13:30', 0, 5) }}" required>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label class="font-weight-bold">Fim Tarde</label>
                                        <input type="time" name="horario_tarde_fim" class="form-control" value="{{ substr($parametros->horario_tarde_fim ?? '17:30', 0, 5) }}" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h5 class="font-weight-bold text-dark mt-4 mb-4 border-bottom pb-2">
                        2. Tempo Limite no Cliente (Franquia) e Taxa de Hora Extra
                    </h5>
                    <div class="row">
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold">Tempo Limite no Cliente (Horas)</label>
                            <input type="number" step="0.5" name="tempo_franquia_horas" class="form-control" value="{{ $parametros->tempo_franquia_horas ?? 4 }}" required>
                            <small class="form-text text-muted">Ex: 4 horas (das 08h às 12h).</small>
                        </div>

                        <div class="form-group col-md-4">
                            <label class="font-weight-bold">Valor da Taxa Extra (R$ por Hora Excedente)</label>
                            <input type="text" name="valor_taxa_hora_extra" class="form-control" value="{{ number_format($parametros->valor_taxa_hora_extra ?? 150, 2, ',', '') }}" required>
                            <small class="form-text text-muted">Cobrado na medição caso ultrapasse a franquia.</small>
                        </div>

                        <div class="form-group col-md-4">
                            <label class="font-weight-bold">Dias da Semana Permitidos</label>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                @php
                                    $mapaDias = ['1' => 'Seg', '2' => 'Ter', '3' => 'Qua', '4' => 'Qui', '5' => 'Sex', '6' => 'Sáb', '0' => 'Dom'];
                                @endphp
                                @php foreach ($mapaDias as $num => $label): @endphp
                                <label class="checkbox checkbox-primary mr-3">
                                    <input type="checkbox" name="dias_atendimento[]" value="{{ $num }}" {{ in_array((string)$num, $diasAtuais) ? 'checked' : '' }}>
                                    <span></span> {{ $label }}
                                </label>
                                @php endforeach; @endphp
                            </div>
                        </div>
                    </div>

                    <h5 class="font-weight-bold text-dark mt-4 mb-4 border-bottom pb-2">
                        3. Tipos de Veículos Exibidos para o Parceiro
                    </h5>
                    <div class="row">
                        <div class="form-group col-12">
                            <label class="font-weight-bold">Lista de Tipos de Veículos (separados por vírgula)</label>
                            <input type="text" name="tipos_veiculos_disponiveis" class="form-control" value="{{ $parametros->tipos_veiculos_disponiveis }}">
                            <small class="form-text text-muted">Ex: Caminhão Roll-on/Roll-off, Caminhão Poliguindaste (Brooks), Caminhão Munck, Caminhão Baú</small>
                        </div>
                    </div>

                    <h5 class="font-weight-bold text-dark mt-4 mb-4 border-bottom pb-2">
                        4. Categorias de Produtos Exibidas no Portal do Parceiro
                    </h5>
                    <div class="row">
                        @php
                            $user_logged_view = session('user_logged');
                            $empresaIdView = $user_logged_view['empresa_id'] ?? ($user_logged_view['empresa'] ?? 1);
                            $categorias = \Illuminate\Support\Facades\DB::table('categorias')
                                ->where('empresa_id', $empresaIdView)
                                ->orderBy('nome')
                                ->get();
                        @endphp

                        <div class="form-group col-md-6">
                            <label class="font-weight-bold text-dark">Categoria de Resíduos (Materiais para Coleta)</label>
                            <!-- Removida a classe select2 e colocado form-control normal para garantir visibilidade total -->
                            <select name="categoria_residuos_id" class="form-control" style="height: 42px; border-radius: 6px;">
                                <option value="">-- Selecione a Categoria de Resíduos --</option>
                                @foreach ($categorias as $cat)
                                    <option value="{{ $cat->id }}" {{ isset($parametros) && $parametros->categoria_residuos_id == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->nome }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Apenas produtos desta categoria aparecerão para o cliente escolher o resíduo.</small>
                        </div>

                        <div class="form-group col-md-6">
                            <label class="font-weight-bold text-dark">Categoria de Caçambas / Equipamentos (Locação)</label>
                            <select name="categoria_cacambas_id" class="form-control" style="height: 42px; border-radius: 6px;">
                                <option value="">-- Selecione a Categoria de Caçambas --</option>
                                @foreach ($categorias as $cat)
                                    <option value="{{ $cat->id }}" {{ isset($parametros) && $parametros->categoria_cacambas_id == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->nome }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Apenas produtos desta categoria aparecerão para o cliente selecionar caçambas/equipamentos.</small>
                        </div>

                    </div>
                    <h5 class="font-weight-bold text-dark mt-4 mb-4 border-bottom pb-2">
                        5. Alertas de Atraso de Saída da Frota (WhatsApp)
                    </h5>
                    <div class="row">
                        <div class="form-group col-md-3">
                            <label class="font-weight-bold text-dark">Disparar Alerta de Atraso?</label>
                            <select name="notificar_atraso_whatsapp" class="form-control" style="height: 42px; border-radius: 6px;">
                                <option value="1" {{ ($parametros->notificar_atraso_whatsapp ?? 1) == 1 ? 'selected' : '' }}>🟢 SIM (Ativo)</option>
                                <option value="0" {{ ($parametros->notificar_atraso_whatsapp ?? 1) == 0 ? 'selected' : '' }}>🔴 NÃO (Desativado)</option>
                            </select>
                            <small class="form-text text-muted">Avisa motorista e gestor se o veículo não iniciar a rota.</small>
                        </div>

                        <div class="form-group col-md-3">
                            <label class="font-weight-bold text-dark">Tolerância para Atraso (Minutos)</label>
                            <input type="number" name="minutos_tolerancia_atraso" class="form-control" style="height: 42px; border-radius: 6px;"
                                   value="{{ $parametros->minutos_tolerancia_atraso ?? 20 }}" min="5" max="180">
                            <small class="form-text text-muted">Tempo após a hora prevista para considerar atraso.</small>
                        </div>

                        <div class="form-group col-md-6">
                            <label class="font-weight-bold text-dark">Gestor / Responsável pela Frota</label>
                            <select name="funcionario_gestor_frota_id" class="form-control" style="height: 42px; border-radius: 6px;">
                                <option value="">-- Selecione o Gestor da Frota --</option>
                                @php
                                    $funcionariosLista = \App\Models\Funcionario::where('empresa_id', $empresaIdView)->orderBy('nome')->get();
                                @endphp
                                @foreach($funcionariosLista as $f)
                                    <option value="{{ $f->id }}" {{ ($parametros->funcionario_gestor_frota_id ?? '') == $f->id ? 'selected' : '' }}>
                                        {{ $f->nome }} ({{ $f->telefone ?: ($f->celular ?: 'Sem telefone') }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Este colaborador receberá a notificação crítica de saída atrasada.</small>
                        </div>
                    </div>
                    <div class="card-footer text-right mt-4">
                        <button type="submit" class="btn btn-success font-weight-bold">
                            <i class="la la-check"></i> Salvar Parâmetros da Logística
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
