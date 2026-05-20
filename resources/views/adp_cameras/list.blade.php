@extends('default.layout')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="card card-custom gutter-b">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-0">Câmeras ADP</h4>
                <small class="text-muted">Cadastro de câmeras por empresa, com stream, snapshot e permissões por usuário.</small>
            </div>
            <div>
                <form action="{{ route('adp.cameras.importFromDevices') }}" method="POST" style="display:inline-block">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary btn-sm">Importar dispositivos ADP</button>
                </form>
                <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#modalAdpCameraNew">
                    <i class="fa fa-plus"></i> Nova câmera
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover table-sm">
                <thead class="thead-light">
                <tr>
                    <th>ID</th>
                    <th>Descrição</th>
                    <th>Configuração ADP</th>
                    <th>UUID</th>
                    <th>Stream/Snapshot</th>
                    <th>Status</th>
                    <th>Preview</th>
                    <th>Ações</th>
                </tr>
                </thead>
                <tbody>
                @forelse($cameras as $camera)
                    <tr data-camera-row="{{ $camera->id }}"
                        data-integrador-config-id="{{ $camera->integrador_config_id }}"
                        data-camera-uuid="{{ $camera->camera_uuid }}">
                        <td>{{ $camera->id }}</td>
                        <td>
                            <strong>{{ $camera->descricao }}</strong><br>
                            <small class="text-muted">{{ $camera->model ?: 'Modelo não informado' }} / {{ $camera->driver ?: 'driver não informado' }}</small>
                        </td>
                        <td>#{{ $camera->integrador_config_id }}</td>
                        <td><small>{{ $camera->camera_uuid }}</small></td>
                        <td>
                            <span class="badge {{ $camera->supports_stream ? 'badge-success' : 'badge-secondary' }}">Stream</span>
                            <span class="badge {{ $camera->supports_snapshot ? 'badge-success' : 'badge-secondary' }}">Snapshot</span>
                        </td>
                        <td>
                            <span class="badge badge-secondary" data-camera-status="{{ $camera->id }}">{{ $camera->status ?: 'pendente' }}</span>
                        </td>
                        <td style="width:180px">
                            <div class="border rounded bg-light d-flex align-items-center justify-content-center" style="width:160px;height:90px;overflow:hidden;">
                                <img data-camera-preview="{{ $camera->id }}" style="max-width:100%;max-height:100%;display:none;" alt="Preview">
                                <small data-camera-preview-empty="{{ $camera->id }}" class="text-muted">Sem imagem</small>
                            </div>
                            <button type="button" class="btn btn-outline-dark btn-xs mt-1" data-camera-test="{{ $camera->id }}">Testar</button>
                            <button type="button" class="btn btn-outline-primary btn-xs mt-1" data-camera-snapshot="{{ $camera->id }}">Snapshot</button>
                        </td>
                        <td style="white-space:nowrap">
                            <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modalAdpCameraEdit{{ $camera->id }}">Editar</button>
                            <form action="{{ route('adp.cameras.delete', $camera->id) }}" method="POST" style="display:inline-block">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Deseja excluir esta câmera?')">Excluir</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted">Nenhuma câmera ADP cadastrada.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{ $cameras->links() }}
    </div>
</div>

@include('adp_cameras.partials.form', ['camera' => null, 'modalId' => 'modalAdpCameraNew'])
@foreach($cameras as $camera)
    @include('adp_cameras.partials.form', ['camera' => $camera, 'modalId' => 'modalAdpCameraEdit' . $camera->id])
@endforeach

<script src="{{ asset('js/adp-runtime-client.js') }}"></script>
<script src="{{ asset('js/adp-camera-manager.js') }}"></script>
@endsection
