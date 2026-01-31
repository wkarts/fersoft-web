<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.Usuario.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('empresa.{empresaId}.monitor', function ($user, $empresaId) {
    $sessao = session('user_logged');
    if (!$sessao) {
        return false;
    }

    return (int) ($sessao['empresa'] ?? 0) === (int) $empresaId;
});

Broadcast::channel('empresa.{empresaId}.filial.{filialId}.monitor', function ($user, $empresaId, $filialId) {
    $sessao = session('user_logged');
    if (!$sessao) {
        return false;
    }

    if ((int) ($sessao['empresa'] ?? 0) !== (int) $empresaId) {
        return false;
    }

    $locais = $sessao['locais'] ?? [];
    if (is_array($locais)) {
        if (array_key_exists((string) $filialId, $locais) || array_key_exists((int) $filialId, $locais)) {
            return true;
        }
    }

    $localPadrao = $sessao['local_padrao'] ?? null;
    return $localPadrao !== null && (int) $localPadrao === (int) $filialId;
});
