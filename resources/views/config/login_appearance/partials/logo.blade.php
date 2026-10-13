@php
    $logoProfiles = \App\Services\LoginLogoSettings::normalize($editorSettings['logo'] ?? []);
@endphp
<div data-logo-controls>
    <h3>Logo do acesso</h3>
    <p class="appearance-admin__quiet">
        Ajuste somente a logo desta versão. Desktop e mobile são independentes.
        Medidas vazias e posição padrão mantêm o comportamento do modelo, inclusive em telas baixas.
        O arquivo da imagem continua sendo publicado em Logos e ícones.
    </p>
    @foreach(['desktop' => 'Computador', 'mobile' => 'Celular e tablet'] as $scope => $label)
        <fieldset class="appearance-options">
            <legend>{{ $label }}</legend>
            <div class="appearance-form-grid">
                @foreach(['width' => 'Largura', 'height' => 'Altura'] as $key => $fieldLabel)
                    @php([$minimum, $maximum] = \App\Services\LoginLogoSettings::LIMITS[$scope][$key])
                    <div class="appearance-admin__field">
                        <label for="logo-{{ $scope }}-{{ $key }}">{{ $fieldLabel }} (px)</label>
                        <input id="logo-{{ $scope }}-{{ $key }}" type="number" step="1"
                               name="logo[{{ $scope }}][{{ $key }}]" min="{{ $minimum }}" max="{{ $maximum }}"
                               data-logo-scope="{{ $scope }}" data-logo-field="{{ $key }}"
                               placeholder="Automático / padrão do modelo"
                               value="{{ old('logo.' . $scope . '.' . $key, $logoProfiles[$scope][$key]) }}">
                    </div>
                @endforeach
                <div class="appearance-admin__field">
                    <label for="logo-{{ $scope }}-align">Alinhamento horizontal</label>
                    <select id="logo-{{ $scope }}-align" name="logo[{{ $scope }}][align]"
                            data-logo-scope="{{ $scope }}" data-logo-field="align">
                        @foreach(['default' => 'Padrão do modelo', 'left' => 'Esquerda', 'center' => 'Centro', 'right' => 'Direita'] as $value => $option)
                            <option value="{{ $value }}" @selected(old('logo.' . $scope . '.align', $logoProfiles[$scope]['align']) === $value)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="appearance-admin__field">
                    <label for="logo-{{ $scope }}-position">Posição no bloco</label>
                    <select id="logo-{{ $scope }}-position" name="logo[{{ $scope }}][position]"
                            data-logo-scope="{{ $scope }}" data-logo-field="position">
                        @foreach(['default' => 'Padrão do modelo', 'before' => 'Antes do conteúdo', 'after' => 'Depois do conteúdo'] as $value => $option)
                            <option value="{{ $value }}" @selected(old('logo.' . $scope . '.position', $logoProfiles[$scope]['position']) === $value)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                @foreach(['inset' => 'Recuo lateral', 'space_before' => 'Espaço acima', 'space_after' => 'Espaço abaixo'] as $key => $fieldLabel)
                    <div class="appearance-admin__field">
                        <label for="logo-{{ $scope }}-{{ $key }}">{{ $fieldLabel }} (px)</label>
                        <input id="logo-{{ $scope }}-{{ $key }}" type="number" min="0" max="{{ $key === 'inset' ? 64 : 96 }}" step="1"
                               name="logo[{{ $scope }}][{{ $key }}]" data-logo-scope="{{ $scope }}" data-logo-field="{{ $key }}"
                               placeholder="Padrão do modelo"
                               value="{{ old('logo.' . $scope . '.' . $key, $logoProfiles[$scope][$key]) }}">
                    </div>
                @endforeach
            </div>
            <button type="button" class="appearance-btn appearance-btn--subtle" data-logo-reset="{{ $scope }}">Restaurar logo — {{ $label }}</button>
        </fieldset>
    @endforeach
    <p class="appearance-admin__quiet">
        A proporção da imagem é preservada. A posição é relativa ao bloco de apresentação ou de acesso, sem sobrepor campos.
        Use Computador / Celular na prévia. Restaurar afeta apenas os ajustes da logo; a gravação usa os botões já existentes abaixo.
    </p>
</div>
<script src="{{ asset('js/login-logo-editor.js') }}?v={{ filemtime(public_path('js/login-logo-editor.js')) }}" defer></script>
