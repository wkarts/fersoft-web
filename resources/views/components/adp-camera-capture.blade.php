@props([
    'name' => 'foto',
    'targetInput' => null,
    'label' => 'Capturar foto via câmera ADP',
    'cameras' => collect(),
])

<div class="adp-camera-capture border rounded p-2" data-target-input="{{ $targetInput }}">
    <label class="mb-1">{{ $label }}</label>
    <div class="form-row">
        <div class="col-md-8">
            <select class="form-control form-control-sm" data-adp-camera-capture="camera">
                <option value="">Selecione uma câmera...</option>
                @foreach($cameras as $camera)
                    <option value="{{ $camera->id }}"
                            data-integrador-config-id="{{ $camera->integrador_config_id }}"
                            data-camera-uuid="{{ $camera->camera_uuid }}">
                        {{ $camera->descricao }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <button type="button" class="btn btn-primary btn-sm btn-block" data-adp-camera-capture="btn">Capturar</button>
        </div>
    </div>
    <input type="hidden" name="{{ $name }}" data-adp-camera-capture="value">
    <div class="mt-2 border rounded bg-light d-flex align-items-center justify-content-center" style="height:130px;overflow:hidden;">
        <img data-adp-camera-capture="preview" style="max-width:100%;max-height:100%;display:none;" alt="Foto capturada">
        <small class="text-muted" data-adp-camera-capture="empty">Nenhuma imagem capturada</small>
    </div>
</div>
