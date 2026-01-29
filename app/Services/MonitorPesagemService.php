<?php

namespace App\Services;

use App\Models\Pesagem;
use App\Models\Usuario;
use Carbon\Carbon;

class MonitorPesagemService
{
    public function buildPayload(Pesagem $pesagem, string $tipoEvento, ?Carbon $timestamp = null): array
    {
        $timestamp = $timestamp ?? now();

        $pesagem->loadMissing([
            'tickets.produto',
            'filial',
            'cliente',
            'fornecedor',
            'motorista',
            'venda.itens',
            'compra.itens',
        ]);

        $motorista = $pesagem->motorista_nome
            ?? $pesagem->motorista->nome
            ?? '—';

        $usuario = Usuario::find($pesagem->usuario_id);
        $usuarioNome = $usuario->login
            ?? $usuario->nome
            ?? '—';

        $fornecedorNome = '—';
        if ($pesagem->tipo === 'compra') {
            $fornecedorNome = $pesagem->fornecedor->razao_social
                ?? $pesagem->fornecedor->nome_fantasia
                ?? '—';
        } elseif ($pesagem->tipo === 'venda') {
            $fornecedorNome = $pesagem->cliente->razao_social
                ?? $pesagem->cliente->nome_fantasia
                ?? '—';
        }

        $produtos = $pesagem->tickets
            ->map(fn ($ticket) => $ticket->produto->nome ?? null)
            ->filter()
            ->unique()
            ->values();

        $produtosResumo = $produtos->take(2)->implode(', ');
        if ($produtos->count() > 2) {
            $produtosResumo .= ' +' . ($produtos->count() - 2);
        }

        $pesoBruto = (float) ($pesagem->peso_bruto ?? $pesagem->peso_liquido_bruto ?? 0);
        $pesoLiquido = (float) ($pesagem->peso_liquido_real ?? $pesagem->peso ?? 0);
        $pesoFinal = (float) ($pesagem->peso_final ?? $pesagem->peso ?? 0);
        $tara = (float) ($pesagem->tara ?? max(0, $pesoBruto - $pesoLiquido));
        $pesoBag = (float) $pesagem->tickets->sum('peso_bag');

        $valorTotal = null;
        if ($pesagem->venda) {
            $valorTotal = (float) $pesagem->venda->valor_total;
        } elseif ($pesagem->compra) {
            $valorTotal = (float) $pesagem->compra->valor;
        }

        $precoKg = null;
        if ($valorTotal && $pesoFinal > 0) {
            $precoKg = $valorTotal / $pesoFinal;
        }

        return [
            'id' => $pesagem->id,
            'empresa_id' => $pesagem->empresa_id,
            'filial_id' => $pesagem->filial_id,
            'filial' => $pesagem->filial->descricao ?? 'Matriz',
            'tipo' => $pesagem->tipo,
            'acao' => $tipoEvento,
            'status' => $pesagem->status,
            'timestamp' => $timestamp->format('H:i:s'),
            'timestamp_iso' => $timestamp->toIso8601String(),
            'motorista' => $motorista,
            'usuario' => $usuarioNome,
            'fornecedor' => $fornecedorNome,
            'produtos' => $produtos->values()->all(),
            'produtos_resumo' => $produtosResumo !== '' ? $produtosResumo : '—',
            'produtos_count' => $produtos->count(),
            'pesos' => [
                'bruto' => $pesoBruto,
                'tara' => $tara,
                'bag' => $pesoBag,
                'liquido' => $pesoLiquido,
                'final' => $pesoFinal,
            ],
            'preco_kg' => $precoKg,
            'valor_total' => $valorTotal,
            'venda_id' => $pesagem->venda_id,
            'compra_id' => $pesagem->compra_id,
        ];
    }

    public function inferTipoEvento(Pesagem $pesagem): string
    {
        if ($pesagem->status === 'concluído') {
            return 'pesagem.finished';
        }

        if ($pesagem->created_at && $pesagem->updated_at && $pesagem->created_at->equalTo($pesagem->updated_at)) {
            return 'pesagem.created';
        }

        return 'pesagem.updated';
    }
}
