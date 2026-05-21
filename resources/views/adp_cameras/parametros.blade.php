@extends('default.layout')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
    .adp-camera-param-preview{width:120px;height:72px;background:#0b1220;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#cbd5e1;font-size:11px;overflow:hidden}.adp-camera-param-preview img{width:100%;height:100%;object-fit:cover;display:none}.adp-camera-param-preview img.adp-camera-preview-loaded{display:block!important}.adp-camera-param-log{max-height:54px;overflow:auto;font-size:11px;color:#4b5563;background:#f8fafc;border-radius:6px;padding:6px;min-width:180px}.adp-camera-param-actions .btn{margin:1px}
</style>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
        <div>
            <h4 class="mb-0">Parâmetros de Câmeras ADP</h4>
            <small class="text-muted">Cadastro técnico, endpoints, permissões e vínculo com a configuração ADP.</small>
        </div>
        <div class="mt-3 mt-md-0">
            <a href="{{ url('/adp/cameras') }}" class="btn btn-outline-primary btn-sm mr-1">Teste de Câmeras</a>
            <a href="{{ url('/adp') }}" class="btn btn-outline-primary btn-sm mr-1">Configurações ADP</a>
            <form action="{{ route('adp.cameras.importFromDevices') }}" method="POST" style="display:inline-block">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-sm">Importar dispositivos ADP</button>
            </form>
            <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#modalAdpCameraNew">
                <i class="fa fa-plus"></i> Nova câmera
            </button>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="thead-light">
                <tr>
                    <th>ID</th>
                    <th>Descrição</th>
                    <th>Configuração ADP</th>
                    <th>UUID</th>
                    <th>Stream / Snapshot</th>
                    <th>Status</th>
                    <th>Preview/Teste</th>
                    <th>Permissão</th>
                    <th class="text-right">Ações</th>
                </tr>
                </thead>
                <tbody>
                @forelse($cameras as $camera)
                    @php($cameraConfig = ($configMap ?? collect())->get($camera->integrador_config_id))
                    <tr data-camera-row="{{ $camera->id }}"
                        data-integrador-config-id="{{ $camera->integrador_config_id }}"
                        data-base-url="{{ $cameraConfig->base_url ?? '' }}"
                        data-camera-uuid="{{ $camera->camera_uuid }}"
                        data-stream-url="{{ $camera->stream_url }}"
                        data-snapshot-url="{{ $camera->snapshot_url }}">
                        <td>{{ $camera->id }}</td>
                        <td>
                            <strong>{{ $camera->descricao }}</strong><br>
                            <small class="text-muted">{{ $camera->model ?: 'Modelo não informado' }} / {{ $camera->driver ?: 'driver não informado' }}</small>
                        </td>
                        <td>
                            #{{ $camera->integrador_config_id ?: '-' }}<br>
                            <small class="text-muted">{{ $cameraConfig->base_url ?? '-' }}</small>
                        </td>
                        <td><small style="word-break:break-all">{{ $camera->camera_uuid }}</small></td>
                        <td>
                            <span class="badge {{ $camera->supports_stream ? 'badge-success' : 'badge-secondary' }}">Stream</span>
                            <span class="badge {{ $camera->supports_snapshot ? 'badge-success' : 'badge-secondary' }}">Snapshot</span><br>
                            <small class="text-muted">{{ $camera->stream_url ?: 'Stream URL automática' }}</small><br>
                            <small class="text-muted">{{ $camera->snapshot_url ?: 'Snapshot URL automática' }}</small>
                        </td>
                        <td><span data-camera-status="{{ $camera->id }}" class="badge badge-{{ ($camera->status === 'online' || $camera->status === 'ativa') ? 'success' : 'light' }}">{{ $camera->status ?: 'pendente' }}</span></td>
                        <td>
                            <div class="adp-camera-param-preview mb-1">
                                <img data-camera-preview="{{ $camera->id }}" alt="Preview da câmera">
                                <span data-camera-preview-empty="{{ $camera->id }}">Sem imagem</span>
                            </div>
                            <div class="adp-camera-param-log" data-camera-log="{{ $camera->id }}">Aguardando teste...</div>
                        </td>
                        <td>
                            @if(($camera->camera_access_mode ?? 'all') === 'selective')
                                <span class="badge badge-warning">Usuários selecionados</span>
                            @else
                                <span class="badge badge-success">Todos os usuários</span>
                            @endif
                        </td>
                        <td class="text-right adp-camera-param-actions" style="white-space:nowrap">
                            <button type="button" class="btn btn-outline-dark btn-xs" data-camera-test="{{ $camera->id }}">Testar</button>
                            <button type="button" class="btn btn-outline-info btn-xs" data-camera-stream="{{ $camera->id }}">Stream</button>
                            <button type="button" class="btn btn-primary btn-xs" data-camera-snapshot="{{ $camera->id }}">Snapshot</button>
                            <button type="button" class="btn btn-warning btn-xs" data-toggle="modal" data-target="#modalAdpCameraEdit{{ $camera->id }}">Editar</button>
                            <form action="{{ route('adp.cameras.delete', $camera->id) }}" method="POST" style="display:inline-block">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-xs" onclick="return confirm('Deseja excluir esta câmera ADP?')">Excluir</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">Nenhuma câmera ADP cadastrada.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $cameras->links() }}
    </div>
</div>

@include('adp_cameras.partials.form', ['modalId' => 'modalAdpCameraNew', 'camera' => null])
@foreach($cameras as $camera)
    @include('adp_cameras.partials.form', ['modalId' => 'modalAdpCameraEdit' . $camera->id, 'camera' => $camera])
@endforeach

<script src="{{ asset('js/adp-runtime-client.js') }}"></script>
<script src="{{ asset('js/adp-camera-manager.js') }}"></script>
@endsection
