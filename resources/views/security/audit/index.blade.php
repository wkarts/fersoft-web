@extends('default.layout')

@section('content')
<link rel="stylesheet" href="/css/security-admin.css">

@php
    $exportParams = array_filter([
        'empresa_id' => request('empresa_id'),
        'filial_id' => request('filial_id'),
        'usuario_id' => request('usuario_id'),
        'acao' => request('acao'),
        'modelo' => request('modelo'),
        'data_inicial' => request('data_inicial'),
        'data_final' => request('data_final'),
    ], fn($v) => $v !== null && $v !== '');
@endphp

<div class="security-admin-page">
    @if(session('mensagem_sucesso'))<div class="alert alert-success">{{ session('mensagem_sucesso') }}</div>@endif
    @if(session('mensagem_erro'))<div class="alert alert-danger">{{ session('mensagem_erro') }}</div>@endif

    <div class="sa-hero mb-4">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="sa-chip"><i class="la la-history"></i> Auditoria</span>
                <span class="sa-chip"><i class="la la-filter"></i> {{ $totalFiltrado ?? $logs->total() }} registro(s) filtrado(s)</span>
                <h2 class="sa-title mt-2">Logs e rastreabilidade</h2>
                <p class="sa-subtitle">Consulte alterações, veja detalhes, filtre por período, usuário, ação e recurso. A exportação JSON fica aqui dentro da auditoria e respeita os filtros aplicados.</p>
            </div>
            <div class="col-lg-4 mt-3 mt-lg-0">
                <div class="sa-actions justify-content-lg-end">
                    <a href="/seguranca/administracao" class="btn btn-light-primary"><i class="la la-shield"></i> Administração</a>
                    <a href="/seguranca/auditoria/politicas" class="btn btn-light"><i class="la la-sliders-h"></i> Políticas</a>
                    <a href="/seguranca/auditoria/restaurar" class="btn btn-warning"><i class="la la-undo"></i> Restauração</a>
                </div>
            </div>
        </div>
    </div>

    <div class="sa-card mb-4"><div class="sa-card-body">
        <h4 class="sa-section-title">Filtros da auditoria</h4>
        <p class="sa-section-help">Use filtros objetivos para localizar eventos. O botão de exportação usa os mesmos filtros da consulta atual.</p>
        <form method="get" action="/seguranca/auditoria">
            <div class="row">
                @if($isSuper)
                    <div class="form-group col-xl-3 col-lg-4 col-md-6">
                        <label>Empresa</label>
                        <select name="empresa_id" class="form-control">
                            <option value="">Todas</option>
                            @foreach($empresas as $empresa)
                                <option value="{{ $empresa->id }}" {{ request('empresa_id') == $empresa->id ? 'selected' : '' }}>{{ $empresa->nome }} - {{ $empresa->cnpj }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="form-group col-xl-2 col-lg-4 col-md-6"><label>Data inicial</label><input type="date" name="data_inicial" class="form-control" value="{{ request('data_inicial') }}"></div>
                <div class="form-group col-xl-2 col-lg-4 col-md-6"><label>Data final</label><input type="date" name="data_final" class="form-control" value="{{ request('data_final') }}"></div>
                <div class="form-group col-xl-2 col-lg-4 col-md-6">
                    <label>Filial</label>
                    <select name="filial_id" class="form-control">
                        <option value="">Todas</option>
                        <option value="null" {{ request('filial_id') === 'null' ? 'selected' : '' }}>Matriz</option>
                        @foreach($filiais as $filial)
                            <option value="{{ $filial->id }}" {{ request('filial_id') == $filial->id ? 'selected' : '' }}>{{ $filial->nome_fantasia }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-xl-3 col-lg-4 col-md-6">
                    <label>Usuário</label>
                    <select name="usuario_id" class="form-control">
                        <option value="">Todos</option>
                        @foreach($usuarios as $usuario)
                            <option value="{{ $usuario->id }}" {{ request('usuario_id') == $usuario->id ? 'selected' : '' }}>{{ $usuario->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-xl-2 col-lg-4 col-md-6">
                    <label>Ação</label>
                    <select name="acao" class="form-control">
                        <option value="">Todas</option>
                        @foreach($acoes as $acao)
                            <option value="{{ $acao }}" {{ request('acao') == $acao ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $acao)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-xl-3 col-lg-4 col-md-6"><label>Recurso/Modelo</label><input type="text" name="modelo" class="form-control" value="{{ request('modelo') }}" placeholder="Cliente, Produto, Conta..."></div>
            </div>
            <div class="sa-actions">
                <button class="btn btn-primary"><i class="la la-search"></i> Filtrar</button>
                <a href="/seguranca/auditoria" class="btn btn-light">Limpar</a>
                <a href="/seguranca/auditoria/exportar-json?{{ http_build_query($exportParams) }}" class="btn btn-outline-primary"><i class="la la-download"></i> Exportar JSON filtrado</a>
            </div>
            <small class="text-muted d-block mt-2">A exportação respeita as permissões da auditoria, políticas de visualização e o limite protegido do backend.</small>
        </form>
    </div></div>

    <div class="sa-card"><div class="sa-card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
            <div><h4 class="sa-section-title">Registros encontrados</h4><p class="sa-section-help">Clique em Detalhes para visualizar o evento e, quando permitido, o JSON ou opções de restauração.</p></div>
            <span class="sa-badge sa-badge-info">Página {{ $logs->currentPage() }} de {{ $logs->lastPage() }}</span>
        </div>
        <div class="table-responsive sa-table-wrap">
            <table class="table table-bordered table-hover sa-table">
                <thead>
                    <tr><th>Data</th><th>Ação</th><th>Recurso</th><th>Registro</th><th>Usuário</th><th>Filial</th><th>Origem</th><th width="180">Ações</th></tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ optional($log->created_at)->format('d/m/Y H:i:s') }}</td>
                            <td><span class="sa-badge sa-badge-info">{{ $log->acao }}</span></td>
                            <td><strong>{{ class_basename($log->modelo) }}</strong><br><small class="text-muted">{{ $log->modelo }}</small></td>
                            <td>{{ $log->registro_id ?? '-' }}</td>
                            <td>{{ optional($log->usuario)->nome ?? $log->usuario_id ?? '-' }}</td>
                            <td>{{ optional($log->filial)->nome_fantasia ?? 'Matriz' }}</td>
                            <td>{{ $log->ip_address }}</td>
                            <td>
                                <div class="sa-actions">
                                    <a class="btn btn-sm btn-primary" href="/seguranca/auditoria/{{ $log->id }}">Detalhes</a>
                                    @if(in_array($log->acao, ['update', 'delete', 'create']))
                                        <a class="btn btn-sm btn-warning" href="/seguranca/auditoria/{{ $log->id }}/restaurar/preview">Restaurar</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><div class="sa-empty">Nenhum log encontrado para os filtros informados.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @foreach($logs as $log)
            <div class="sa-mobile-card">
                <strong>{{ $log->acao }}</strong> · {{ optional($log->created_at)->format('d/m/Y H:i') }}<br>
                {{ class_basename($log->modelo) }} #{{ $log->registro_id ?? '-' }}<br>
                Usuário: {{ optional($log->usuario)->nome ?? $log->usuario_id ?? '-' }}<br>
                <div class="sa-actions mt-2"><a class="btn btn-sm btn-primary" href="/seguranca/auditoria/{{ $log->id }}">Detalhes</a></div>
            </div>
        @endforeach
        {{ $logs->links() }}
    </div></div>
</div>
@endsection
