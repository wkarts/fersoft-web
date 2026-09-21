<?php

namespace App\Services\Nfse;

interface NfseEmissorInterface
{
    public function emitir(array $dpsData);

    public function cancelar(string $chave, string $motivo);

    public function getChave();

    public function getProtocolo();
}
