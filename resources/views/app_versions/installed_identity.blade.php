@php
    $installedBaseTag = data_get(optional($appVersionCurrent)->metadata, 'installed_release.base_stable_tag');
    $installedIdentityStatus = data_get(optional($appVersionCurrent)->metadata, 'identity_status');
@endphp
@if($installedBaseTag)
    <small class="d-block" title="Base informada pelo build; a versão instalada está identificada separadamente.">Base estável: {{ $installedBaseTag }}</small>
@elseif($installedIdentityStatus === 'unverified')
    <small class="d-block">Versão não identificada para este commit</small>
@endif
