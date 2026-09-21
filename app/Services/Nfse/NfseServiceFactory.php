<?php

namespace App\Services\Nfse;

class NfseServiceFactory
{
    public static function criar(string $provedor, array $config, $certificado)
    {
        switch (strtolower($provedor)) {
            case 'nacional':
            case 'sefin':
                return new \App\Services\NfseNacionalTools($config, $certificado);

            case 'diasdavila':
            case 'saatri':
                return new \App\Services\Nfse\Drivers\DiasDavilaTools($config, $certificado);

            case 'salvador':
                return new \App\Services\Nfse\Drivers\SalvadorTools($config, $certificado);

            default:
                throw new \InvalidArgumentException("Provedor NFS-e '{$provedor}' não suportado.");
        }
    }
}