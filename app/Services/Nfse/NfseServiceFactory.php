<?php

namespace App\Services\Nfse;

use App\Services\Nfse\Drivers\DiasDavilaTools;
use App\Services\Nfse\Drivers\SalvadorTools;
use App\Services\NfseNacionalTools;

class NfseServiceFactory
{
    public static function criar(string $provedor, array $config, $certificado): NfseEmissorInterface
    {
        return match (strtolower(trim($provedor))) {
            'nacional', 'sefin' => new NfseNacionalTools($config, $certificado),
            'diasdavila', 'saatri' => new DiasDavilaTools($config, $certificado),
            'salvador' => new SalvadorTools($config, $certificado),
            default => throw new \InvalidArgumentException(
                "Provedor NFS-e '{$provedor}' não suportado."
            ),
        };
    }
}
