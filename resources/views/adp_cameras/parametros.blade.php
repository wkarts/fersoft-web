@extends('default.layout')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
    .adp-camera-params-page { --adp-blue:#3699ff; --adp-dark:#0f172a; --adp-muted:#7e8299; }
    .adp-camera-params-page .page-head { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:16px; }
    .adp-camera-params-page .page-head h4 { margin:0; font-size:20px; font-weight:700; color:#1f2937; }
    .adp-camera-params-page .page-head small { color:#8a94a6; }
    .adp-camera-params-page .toolbar { display:flex; flex-wrap:wrap; gap:6px; justify-content:flex-end; }
    .adp-camera-params-page .camera-grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap:14px; }
    .adp-camera-params-page .camera-card { background:#fff; border:1px solid #e8edf6; border-radius:14px; box-shadow:0 6px 18px rgba(15,23,42,.04); overflow:hidden; }
    .adp-camera-params-page .camera-card-head { display:flex; gap:12px; padding:14px; border-bottom:1px solid #eef2f7; }
    .adp-camera-params-page .preview-box { width:112px; min-width:112px; height:76px; background:#0b1220; color:#cbd5e1; border-radius:10px; display:flex; align-items:center; justify-content:center; overflow:hidden; font-size:11px; text-align:center; }
    .adp-camera-params-page .preview-box { position:relative; }
    .adp-camera-params-page .preview-box img { width:100%; height:100%; object-fit:cover; display:none; }
    .adp-camera-params-page .preview-box img.adp-camera-preview-loaded { display:block!important; }
    .adp-camera-params-page .live-mode-badge { position:absolute; top:6px; left:6px; background:rgba(15,23,42,.82); color:#fff; font-size:9.5px; border-radius:999px; padding:3px 7px; display:none; }
    .adp-camera-params-page .camera-title { min-width:0; flex:1; }
    .adp-camera-params-page .camera-title strong { display:block; font-size:15px; color:#111827; line-height:1.25; }
    .adp-camera-params-page .camera-title .meta { color:#7e8299; font-size:11px; line-height:1.35; margin-top:3px; }
    .adp-camera-params-page .camera-uuid { font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; color:#4b5563; font-size:10.5px; word-break:break-all; margin-top:6px; }
    .adp-camera-params-page .camera-card-body { padding:12px 14px; }
    .adp-camera-params-page .chip-row { display:flex; flex-wrap:wrap; gap:5px; margin-bottom:8px; }
    .adp-camera-params-page .chip { display:inline-flex; align-items:center; gap:4px; border-radius:999px; padding:4px 8px; font-size:10.5px; font-weight:700; background:#f3f6fb; color:#536179; }
    .adp-camera-params-page .chip-ok { background:#e8fff3; color:#0b8f55; }
    .adp-camera-params-page .chip-warn { background:#fff6e5; color:#9a6500; }
    .adp-camera-params-page .chip-info { background:#eaf4ff; color:#1d5fa8; }
    .adp-camera-params-page .camera-log { max-height:62px; overflow:auto; font-size:11px; background:#f8fafc; border-radius:8px; padding:8px; color:#4b5563; white-space:pre-line; }
    .adp-camera-params-page .camera-actions { display:flex; flex-wrap:wrap; gap:5px; justify-content:flex-end; margin-top:10px; }
    .adp-camera-params-page .camera-actions .btn { width:32px; height:30px; padding:0; display:inline-flex; align-items:center; justify-content:center; }
    .adp-camera-params-page .status-badge { border-radius:999px; padding:4px 8px; font-size:10.5px; font-weight:700; }
    .adp-camera-params-page .status-online { background:#e8fff3; color:#0b8f55; }
    .adp-camera-params-page .status-offline { background:#ffe8e8; color:#cc1f1a; }
    .adp-camera-params-page .status-pendente { background:#f3f4f6; color:#4b5563; }
    @media (max-width: 576px) {
        .adp-camera-params-page .page-head { display:block; }
        .adp-camera-params-page .toolbar { justify-content:flex-start; margin-top:10px; }
        .adp-camera-params-page .camera-grid { grid-template-columns:1fr; }
        .adp-camera-params-page .camera-card-head { flex-direction:column; }
        .adp-camera-params-page .preview-box { width:100%; height:150px; }
    }
</style>

<div class="container-fluid adp-camera-params-page">
    <div class="page-head">
        <div>
            <h4>Parâmetros de Câmeras ADP</h4>
            <small>Cadastro técnico, permissões e testes das câmeras vinculadas ao A.D.P.</small>
        </div>
        <div class="toolbar">
            <a href="{{ url('/adp/cameras') }}" class="btn btn-outline-primary btn-sm" title="Teste de câmeras"><i class="fa fa-video-camera"></i> Teste</a>
            <a href="{{ url('/adp') }}" class="btn btn-outline-primary btn-sm" title="Configurações ADP"><i class="fa fa-cogs"></i> ADP</a>
            <form action="{{ route('adp.cameras.importFromDevices') }}" method="POST" style="display:inline-block">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-sm" title="Importar dispositivos ADP"><i class="fa fa-download"></i> Importar</button>
            </form>
            <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#modalAdpCameraNew" title="Nova câmera">
                <i class="fa fa-plus"></i> Nova
            </button>
        </div>
    </div>

    <div class="camera-grid">
        @forelse($cameras as $camera)
            @php($cameraConfig = ($configMap ?? collect())->get($camera->integrador_config_id))
            <div class="camera-card" data-camera-row="{{ $camera->id }}"
                 data-integrador-config-id="{{ $camera->integrador_config_id }}"
                 data-base-url="{{ $cameraConfig->base_url ?? '' }}"
                 data-camera-uuid="{{ $camera->camera_uuid }}"
                 data-stream-url="{{ $camera->stream_url }}"
                 data-snapshot-url="{{ $camera->snapshot_url }}">
                <div class="camera-card-head">
                    <div class="preview-box">
                        <img data-camera-preview="{{ $camera->id }}" alt="Preview da câmera">
                        <span class="live-mode-badge" data-camera-live-mode="{{ $camera->id }}"></span>
                        <span data-camera-preview-empty="{{ $camera->id }}">Sem imagem</span>
                    </div>
                    <div class="camera-title">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="mr-2">
                                <strong>{{ $camera->descricao }}</strong>
                                <div class="meta">{{ $camera->model ?: 'Modelo não informado' }} / {{ $camera->driver ?: 'driver não informado' }}</div>
                            </div>
                            <span data-camera-status="{{ $camera->id }}" class="status-badge status-{{ ($camera->status === 'online' || $camera->status === 'ativa') ? 'online' : 'pendente' }}">{{ $camera->status ?: 'pendente' }}</span>
                        </div>
                        <div class="camera-uuid">{{ $camera->camera_uuid }}</div>
                    </div>
                </div>
                <div class="camera-card-body">
                    <div class="chip-row">
                        <span class="chip chip-info" title="Configuração ADP"><i class="fa fa-plug"></i> Config #{{ $camera->integrador_config_id ?: '-' }}</span>
                        <span class="chip {{ $camera->supports_stream ? 'chip-ok' : 'chip-warn' }}" title="Suporte a stream"><i class="fa fa-rss"></i> Stream</span>
                        <span class="chip {{ $camera->supports_snapshot ? 'chip-ok' : 'chip-warn' }}" title="Suporte a snapshot"><i class="fa fa-camera"></i> Snapshot</span>
                        @if(($camera->camera_access_mode ?? 'all') === 'selective')
                            <span class="chip chip-warn" title="Permissão seletiva"><i class="fa fa-lock"></i> Seletivo</span>
                        @else
                            <span class="chip chip-ok" title="Liberada para todos os usuários"><i class="fa fa-users"></i> Todos</span>
                        @endif
                    </div>

                    <div class="camera-log" data-camera-log="{{ $camera->id }}">Aguardando teste...</div>

                    <div class="camera-actions">
                        <button type="button" class="btn btn-outline-dark btn-sm" data-camera-test="{{ $camera->id }}" title="Testar câmera"><i class="fa fa-check-circle"></i></button>
                        <button type="button" class="btn btn-outline-info btn-sm" data-camera-stream="{{ $camera->id }}" title="Preview ao vivo via proxy/MJPEG"><i class="fa fa-play"></i></button>
                        <button type="button" class="btn btn-primary btn-sm" data-camera-snapshot="{{ $camera->id }}" title="Capturar snapshot"><i class="fa fa-camera"></i></button>
                        <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modalAdpCameraEdit{{ $camera->id }}" title="Editar"><i class="fa fa-pencil"></i></button>
                        <form action="{{ route('adp.cameras.delete', $camera->id) }}" method="POST" style="display:inline-block">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" title="Excluir" onclick="return confirm('Deseja excluir esta câmera ADP?')"><i class="fa fa-trash"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="alert alert-light border text-center">Nenhuma câmera ADP cadastrada.</div>
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
