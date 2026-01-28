<?php

namespace App\Services\Fiscal;

class TransmissaoResult implements \JsonSerializable
{
    private bool $success;
    private string $status;
    private ?int $cStat;
    private ?string $xMotivo;
    private ?string $protocolo;
    private ?string $recibo;
    private ?string $mensagem;
    private ?string $xmlAutorizadoPath;
    private array $payload;
    private array $context;

    public function __construct(array $data)
    {
        $this->success           = (bool)($data['success'] ?? false);
        $this->status            = (string)($data['status'] ?? 'erro_tecnico');
        $this->cStat             = isset($data['cStat']) ? (int)$data['cStat'] : null;
        $this->xMotivo           = $data['xMotivo'] ?? null;
        $this->protocolo         = $data['protocolo'] ?? null;
        $this->recibo            = $data['recibo'] ?? null;
        $this->mensagem          = $data['mensagem'] ?? null;
        $this->xmlAutorizadoPath = $data['xmlAutorizadoPath'] ?? null;
        $this->payload           = $data['payload'] ?? [];
        $this->context           = $data['context'] ?? [];
    }

    public function __toString(): string
    {
        $prefix = $this->success ? 'OK' : 'Erro';
        $codigo = $this->cStat !== null ? '[' . $this->cStat . ']' : '';
        $motivo = $this->mensagem ?? $this->xMotivo ?? 'Retorno indefinido';

        return sprintf('%s: %s %s', $prefix, $codigo, trim($motivo));
    }

    public function jsonSerialize(): array
    {
        return [
            'success'            => $this->success,
            'status'             => $this->status,
            'cStat'              => $this->cStat,
            'xMotivo'            => $this->xMotivo,
            'mensagem'           => $this->mensagem ?? $this->formatMensagem(),
            'protocolo'          => $this->protocolo,
            'recibo'             => $this->recibo,
            'xmlAutorizadoPath'  => $this->xmlAutorizadoPath,
            'payload'            => $this->payload,
            'context'            => $this->context,
        ];
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function isDenegado(): bool
    {
        return $this->status === 'denegado';
    }

    public function isErroTecnico(): bool
    {
        return $this->status === 'erro_tecnico';
    }

    public function isRejeicao(): bool
    {
        return $this->status === 'rejeitado';
    }

    public function getCStat(): ?int
    {
        return $this->cStat;
    }

    public function getMensagem(): string
    {
        return $this->mensagem ?? $this->formatMensagem();
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getRecibo(): ?string
    {
        return $this->recibo;
    }

    public function getProtocolo(): ?string
    {
        return $this->protocolo;
    }

    public function toArray(): array
    {
        return $this->jsonSerialize();
    }

    public function httpStatus(): int
    {
        if ($this->isSuccess() || $this->isDenegado()) {
            return 200;
        }

        if ($this->isRejeicao()) {
            return 422;
        }

        return 500;
    }

    private function formatMensagem(): string
    {
        $codigo = $this->cStat !== null ? '[' . $this->cStat . '] ' : '';
        $motivo = $this->xMotivo ?? 'Retorno indefinido';

        return $codigo . $motivo;
    }
}
