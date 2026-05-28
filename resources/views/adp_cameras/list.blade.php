@extends('default.layout')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
    .adp-camera-page .camera-card { border:1px solid #e9eef7; border-radius:14px; background:#fff; box-shadow:0 6px 18px rgba(15,23,42,.04); overflow:hidden; height:100%; }
    .adp-camera-page .camera-preview { height:150px; background:#0b1220; display:flex; align-items:center; justify-content:center; color:#cbd5e1; overflow:hidden; }
    .adp-camera-page .camera-preview { position:relative; }
    .adp-camera-page .camera-preview img { width:100%; height:100%; object-fit:cover; display:none; }
    .adp-camera-page .camera-preview img.adp-camera-preview-loaded { display:block !important; }
    .adp-camera-page .live-mode-badge { position:absolute; top:8px; left:8px; background:rgba(15,23,42,.82); color:#fff; font-size:10px; border-radius:999px; padding:4px 8px; display:none; }
    .adp-camera-page .camera-body { padding:14px; }
    .adp-camera-page .camera-uuid { font-family:monospace; font-size:11px; color:#6b7280; word-break:break-all; }
    .adp-camera-page .camera-actions .btn { margin-right:4px; margin-bottom:4px; }
    .adp-camera-page .status-badge { border-radius:999px; padding:5px 10px; font-size:11px; font-weight:700; }
    .adp-camera-page .status-online { background:#e8fff3; color:#0b8f55; }
    .adp-camera-page .status-offline { background:#ffe8e8; color:#cc1f1a; }
    .adp-camera-page .status-pendente { background:#f3f4f6; color:#4b5563; }
    .adp-camera-page .camera-log { min-height:34px; max-height:70px; overflow:auto; font-size:11px; background:#f8fafc; border-radius:8px; padding:8px; color:#4b5563; }
</style>

<div class="container-fluid adp-camera-page">
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
        <div>
            <h4 class="mb-0">Câmeras ADP</h4>
            <small class="text-muted">Teste câmera, stream, snapshot, preview e permissões por usuário.</small>
        </div>
        <div class="mt-3 mt-md-0">
            <a href="{{ url('/adp') }}" class="btn btn-outline-primary btn-sm mr-1">Configurações ADP</a>
            <a href="{{ url('/adp/cameras_parametros') }}" class="btn btn-outline-primary btn-sm mr-1">Parâmetros</a>
            <form action="{{ route('adp.cameras.importFromDevices') }}" method="POST" style="display:inline-block">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-sm">Importar dispositivos ADP</button>
            </form>
            <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#modalAdpCameraNew">
                <i class="fa fa-plus"></i> Nova câmera
            </button>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4 mb-3">
            <div class="card card-body border-0 shadow-sm">
                <strong>{{ $cameras->total() ?? $cameras->count() }}</strong>
                <small class="text-muted">Câmeras cadastradas</small>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card card-body border-0 shadow-sm">
                <strong>{{ ($configs ?? collect())->count() }}</strong>
                <small class="text-muted">Configurações ADP disponíveis</small>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card card-body border-0 shadow-sm">
                <strong>Stream + Snapshot</strong>
                <small class="text-muted">Teste operacional direto contra a API ADP</small>
            </div>
        </div>
    </div>

    <div class="row">
        @forelse($cameras as $camera)
            @php($cameraConfig = ($configMap ?? collect())->get($camera->integrador_config_id))
            <div class="col-xl-4 col-lg-6 mb-4">
                <div class="camera-card" data-camera-row="{{ $camera->id }}"
                     data-integrador-config-id="{{ $camera->integrador_config_id }}"
                     data-base-url="{{ $cameraConfig->base_url ?? '' }}"
                     data-camera-uuid="{{ $camera->camera_uuid }}"
                     data-stream-url="{{ $camera->stream_url }}"
                     data-snapshot-url="{{ $camera->snapshot_url }}">
                    <div class="camera-preview">
                        <img data-camera-preview="{{ $camera->id }}" alt="Preview da câmera">
                        <span class="live-mode-badge" data-camera-live-mode="{{ $camera->id }}"></span>
                        <span data-camera-preview-empty="{{ $camera->id }}">Sem imagem capturada</span>
                    </div>
                    <div class="camera-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h5 class="mb-0">{{ $camera->descricao }}</h5>
                                <small class="text-muted">{{ $camera->model ?: 'Modelo não informado' }} / {{ $camera->driver ?: 'driver não informado' }}</small>
                            </div>
                            <span class="status-badge status-pendente" data-camera-status="{{ $camera->id }}">{{ $camera->status ?: 'pendente' }}</span>
                        </div>

                        <div class="camera-uuid mb-2">{{ $camera->camera_uuid }}</div>

                        <div class="mb-2">
                            <span class="badge {{ $camera->supports_stream ? 'badge-success' : 'badge-secondary' }}">Stream</span>
                            <span class="badge {{ $camera->supports_snapshot ? 'badge-success' : 'badge-secondary' }}">Snapshot</span>
                            <span class="badge badge-light">Config #{{ $camera->integrador_config_id }}</span>
                        </div>

                        <div class="camera-actions mb-2">
                            <button type="button" class="btn btn-outline-dark btn-sm" data-camera-test="{{ $camera->id }}">Testar</button>
                            <button type="button" class="btn btn-outline-info btn-sm" data-camera-stream="{{ $camera->id }}">Ao vivo</button>
                            <button type="button" class="btn btn-primary btn-sm" data-camera-snapshot="{{ $camera->id }}">Snapshot</button>
                            <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modalAdpCameraEdit{{ $camera->id }}">Editar</button>
                            <form action="{{ route('adp.cameras.delete', $camera->id) }}" method="POST" style="display:inline-block">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Deseja excluir esta câmera ADP?')">Excluir</button>
                            </form>
                        </div>

                        <div class="camera-log" data-camera-log="{{ $camera->id }}">Aguardando teste...</div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-light border text-center">Nenhuma câmera ADP cadastrada.</div>
            </div>
        @endforelse
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
