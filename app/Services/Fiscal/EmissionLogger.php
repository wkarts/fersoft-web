<?php

namespace App\Services\Fiscal;

use App\Models\FiscalEmissionLog;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmissionLogger
{
    public function __construct(private int $empresaId)
    {
    }

    public function start(array $context, string $xmlEnvio, ?string $chave): FiscalEmissionLog
    {
        $data = [
            'empresa_id'          => $this->empresaId,
            'usuario_id'          => Arr::get($context, 'usuario_id'),
            'filial_id'           => Arr::get($context, 'filial_id'),
            'document_type'       => Arr::get($context, 'document_type', 'NFe'),
            'document_id'         => Arr::get($context, 'document_id'),
            'document_reference'  => Arr::get($context, 'document_reference'),
            'numero'              => Arr::get($context, 'numero'),
            'serie'               => Arr::get($context, 'serie'),
            'chave'               => $chave,
            'ambiente'            => Arr::get($context, 'ambiente'),
            'status'              => 'enviado',
            'xml_envio_path'      => $this->storePayload($xmlEnvio, $chave, 'envio', 'xml'),
        ];

        return FiscalEmissionLog::create($data);
    }

    public function finish(
        FiscalEmissionLog $log,
        array $attributes,
        ?string $rawResponse = null,
        ?array $payload = null
    ): void {
        if ($rawResponse) {
            $log->xml_retorno_path = $this->storePayload($rawResponse, $log->chave, 'retorno', 'xml');
        }

        if ($payload !== null) {
            $log->retorno_payload = json_encode($payload, JSON_UNESCAPED_UNICODE);
        }

        foreach ($attributes as $key => $value) {
            $log->{$key} = $value;
        }

        $log->save();
    }

    private function storePayload(?string $content, ?string $chave, string $suffix, string $extension): ?string
    {
        if ($content === null || $content === '') {
            return null;
        }

        $normalizedChave = $chave ? preg_replace('/[^0-9A-Za-z]/', '', $chave) : 'sem-chave';
        $directory = 'fiscal_emissions/' . date('Y/m/d');
        $filename = $normalizedChave . '_' . $suffix . '_' . Str::random(8) . '.' . $extension;
        $path = $directory . '/' . $filename;

        Storage::disk('local')->put($path, $content);

        return $path;
    }
}
