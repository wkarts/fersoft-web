@php
    $editing = !empty($camera);
    $selectedUsers = $editing ? ($permissoes[$camera->id] ?? []) : [];
@endphp
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <form method="POST" action="{{ $editing ? route('adp.cameras.save', $camera->id) : route('adp.cameras.save') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ $editing ? 'Editar câmera ADP' : 'Nova câmera ADP' }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="col-md-4">
                        <label>Configuração ADP</label>
                        <select name="integrador_config_id" class="form-control adp-camera-config-select" required>
                            <option value="">Selecione...</option>
                            @foreach($configs as $config)
                                <option value="{{ $config->id }}"
                                        data-base-url="{{ rtrim($config->base_url, '/') }}"
                                        {{ (int) old('integrador_config_id', $camera->integrador_config_id ?? 0) === (int) $config->id ? 'selected' : '' }}>
                                    #{{ $config->id }} - {{ $config->descricao }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label>Descrição</label>
                        <input name="descricao" class="form-control" value="{{ old('descricao', $camera->descricao ?? '') }}" required>
                    </div>
                </div>

                <div class="form-row mt-2">
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="mb-0">UUID da câmera ADP</label>
                            <button type="button" class="btn btn-outline-primary btn-xs adp-camera-load-from-api">Listar câmeras do ADP</button>
                        </div>
                        <select class="form-control adp-camera-discovery-select mb-1" style="display:none;">
                            <option value="">Selecione uma câmera encontrada...</option>
                        </select>
                        <input name="camera_uuid" class="form-control" value="{{ old('camera_uuid', $camera->camera_uuid ?? '') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label>Modelo</label>
                        <input name="model" class="form-control" value="{{ old('model', $camera->model ?? '') }}">
                    </div>
                    <div class="col-md-3">
                        <label>Driver</label>
                        <input name="driver" class="form-control" value="{{ old('driver', $camera->driver ?? '') }}">
                    </div>
                </div>

                <div class="form-row mt-2">
                    <div class="col-md-3">
                        <label>Protocolo</label>
                        <input name="protocol" class="form-control" value="{{ old('protocol', $camera->protocol ?? '') }}">
                    </div>
                    <div class="col-md-5">
                        <label>Host</label>
                        <input name="host" class="form-control" value="{{ old('host', $camera->host ?? '') }}">
                    </div>
                    <div class="col-md-2">
                        <label>Porta</label>
                        <input name="port" class="form-control" value="{{ old('port', $camera->port ?? '') }}">
                    </div>
                    <div class="col-md-2">
                        <label>Status</label>
                        <select name="ativo" class="form-control">
                            <option value="1" {{ old('ativo', $camera->ativo ?? true) ? 'selected' : '' }}>Ativa</option>
                            <option value="0" {{ !old('ativo', $camera->ativo ?? true) ? 'selected' : '' }}>Inativa</option>
                        </select>
                    </div>
                </div>

                <div class="form-row mt-2">
                    <div class="col-md-6">
                        <label>Stream URL</label>
                        <input name="stream_url" class="form-control" value="{{ old('stream_url', $camera->stream_url ?? '') }}">
                        <small class="text-muted">Preenchido automaticamente como /api/cameras/{uuid}/stream quando vazio.</small>
                    </div>
                    <div class="col-md-6">
                        <label>Snapshot URL</label>
                        <input name="snapshot_url" class="form-control" value="{{ old('snapshot_url', $camera->snapshot_url ?? '') }}">
                        <small class="text-muted">Preenchido automaticamente como /api/cameras/{uuid}/snapshot quando vazio.</small>
                    </div>
                </div>

                <div class="form-row mt-3">
                    <div class="col-md-3">
                        <label class="d-block">Recursos</label>
                        <label class="mr-3"><input type="checkbox" name="supports_stream" value="1" {{ old('supports_stream', $camera->supports_stream ?? true) ? 'checked' : '' }}> Stream</label>
                        <label><input type="checkbox" name="supports_snapshot" value="1" {{ old('supports_snapshot', $camera->supports_snapshot ?? true) ? 'checked' : '' }}> Snapshot</label>
                    </div>
                    <div class="col-md-9">
                        <label>Permissão de acesso à câmera</label>
                        <select name="camera_access_mode" class="form-control mb-2 adp-camera-access-mode">
                            <option value="all" {{ old('camera_access_mode', $camera->camera_access_mode ?? 'all') === 'all' ? 'selected' : '' }}>Liberar para todos os usuários</option>
                            <option value="selective" {{ old('camera_access_mode', $camera->camera_access_mode ?? 'all') === 'selective' ? 'selected' : '' }}>Selecionar usuários permitidos</option>
                        </select>

                        <div class="adp-camera-users-box" style="{{ old('camera_access_mode', $camera->camera_access_mode ?? 'all') === 'selective' ? '' : 'display:none;' }}">
                            <label>Usuários com permissão de acesso</label>
                            <select name="usuarios_permitidos[]" class="form-control" multiple size="4">
                                @foreach($usuarios as $usuario)
                                    <option value="{{ $usuario->id }}" {{ in_array((int) $usuario->id, $selectedUsers, true) ? 'selected' : '' }}>
                                        {{ $usuario->nome }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <small class="text-muted">Por padrão, todos os usuários da empresa podem usar a câmera. Use modo seletivo apenas quando quiser restringir acesso.</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success">Salvar câmera</button>
            </div>
        </form>
    </div>
</div>
