<?php

namespace App\Services;

use App\Events\MovimentoRealtime;
use App\Models\StockDailyAggregate;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function mover(array $payload): StockMovement
    {
        return DB::transaction(function () use ($payload) {
            $idempotencyKey = (string) ($payload['idempotency_key'] ?? '');
            if ($idempotencyKey === '') {
                throw new \InvalidArgumentException('idempotency_key é obrigatório.');
            }

            $existente = StockMovement::where('idempotency_key', $idempotencyKey)->first();
            if ($existente) {
                return $existente;
            }

            $quantidade = (float) ($payload['quantidade'] ?? 0);
            if ($quantidade <= 0) {
                throw new \InvalidArgumentException('quantidade deve ser maior que zero.');
            }

            $tipo = (string) ($payload['tipo'] ?? '');
            if (!in_array($tipo, ['entrada', 'saida'], true)) {
                throw new \InvalidArgumentException('tipo deve ser entrada ou saida.');
            }

            $movimentadoEm = isset($payload['movimentado_em'])
                ? Carbon::parse($payload['movimentado_em'])
                : now();

            $custoUnitario = isset($payload['custo_unitario']) ? (float) $payload['custo_unitario'] : null;
            $valorTotal = isset($payload['valor_total'])
                ? (float) $payload['valor_total']
                : ($custoUnitario !== null ? ($quantidade * $custoUnitario) : null);

            $mov = StockMovement::create([
                'empresa_id' => (int) $payload['empresa_id'],
                'filial_id' => $payload['filial_id'] ?? null,
                'produto_id' => (int) $payload['produto_id'],
                'contexto' => (string) $payload['contexto'],
                'tipo' => $tipo,
                'quantidade' => $quantidade,
                'custo_unitario' => $custoUnitario,
                'valor_total' => $valorTotal,
                'origem_tipo' => $payload['origem_tipo'] ?? null,
                'origem_id' => $payload['origem_id'] ?? null,
                'idempotency_key' => $idempotencyKey,
                'movimentado_em' => $movimentadoEm,
                'metadata' => $payload['metadata'] ?? null,
            ]);

            $this->atualizarAgregado($mov);
            $this->emitirDelta($mov);

            return $mov;
        });
    }

    public function transferirEntreContextos(array $payload): array
    {
        $baseKey = (string) ($payload['idempotency_key'] ?? '');
        if ($baseKey === '') {
            throw new \InvalidArgumentException('idempotency_key é obrigatório para transferência.');
        }

        $saida = $this->mover(array_merge($payload, [
            'contexto' => $payload['contexto_origem'],
            'tipo' => 'saida',
            'idempotency_key' => $baseKey . ':saida',
        ]));

        $entrada = $this->mover(array_merge($payload, [
            'contexto' => $payload['contexto_destino'],
            'tipo' => 'entrada',
            'idempotency_key' => $baseKey . ':entrada',
        ]));

        return [$saida, $entrada];
    }

    private function atualizarAgregado(StockMovement $mov): void
    {
        $dataRef = $mov->movimentado_em->toDateString();

        $agg = StockDailyAggregate::firstOrCreate([
            'data_ref' => $dataRef,
            'empresa_id' => $mov->empresa_id,
            'filial_id' => $mov->filial_id,
            'produto_id' => $mov->produto_id,
            'contexto' => $mov->contexto,
        ], [
            'entrada' => 0,
            'saida' => 0,
            'saldo' => 0,
            'valor_entrada' => 0,
            'valor_saida' => 0,
            'custo_medio' => null,
        ]);

        $qtd = (float) $mov->quantidade;
        $valor = (float) ($mov->valor_total ?? 0);

        if ($mov->tipo === 'entrada') {
            $agg->entrada = (float) $agg->entrada + $qtd;
            $agg->saldo = (float) $agg->saldo + $qtd;
            $agg->valor_entrada = (float) $agg->valor_entrada + $valor;
        } else {
            $agg->saida = (float) $agg->saida + $qtd;
            $agg->saldo = (float) $agg->saldo - $qtd;
            $agg->valor_saida = (float) $agg->valor_saida + $valor;
        }

        if ((float) $agg->entrada > 0) {
            $agg->custo_medio = (float) $agg->valor_entrada / (float) $agg->entrada;
        }

        $agg->save();
    }

    private function emitirDelta(StockMovement $mov): void
    {
        DB::afterCommit(function () use ($mov) {
            event(new MovimentoRealtime('stock.delta', [
                'delta' => [
                    'produto_id' => $mov->produto_id,
                    'contexto' => $mov->contexto,
                    'tipo' => $mov->tipo,
                    'quantidade' => (float) $mov->quantidade,
                    'data' => $mov->movimentado_em->toDateString(),
                ],
                'movement_id' => $mov->id,
            ], $mov->empresa_id, $mov->filial_id));
        });
    }
}
