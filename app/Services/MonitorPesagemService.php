<?php

namespace App\Services;

use App\Models\Pesagem;
use App\Models\Usuario;
use Carbon\Carbon;

class MonitorPesagemService
{
    private static array $usuarioCache = [];

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

        if (!array_key_exists($pesagem->usuario_id, self::$usuarioCache)) {
            self::$usuarioCache[$pesagem->usuario_id] = Usuario::find($pesagem->usuario_id);
        }

        $usuario = self::$usuarioCache[$pesagem->usuario_id];
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

        $itensProduto = $pesagem->tickets
            ->filter(fn ($ticket) => !empty($ticket->produto_id))
            ->groupBy('produto_id')
            ->map(function ($tickets) {
                $produto = $tickets->first()->produto ?? null;
                $pesoLiquido = $tickets->sum(function ($ticket) {
                    $peso = (float) ($ticket->peso ?? 0);
                    $pesoBag = (float) ($ticket->peso_bag ?? 0);
                    return max(0, $peso - $pesoBag);
                });

                return [
                    'produto_id' => (int) $tickets->first()->produto_id,
                    'produto_nome' => $produto->nome ?? '—',
                    'peso_liquido' => (float) $pesoLiquido,
                ];
            })
            ->values()
            ->all();

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

        $direcao = $pesagem->tipo === 'compra' ? 'entrada' : ($pesagem->tipo === 'venda' ? 'saida' : null);

        $parceiroId = null;
        $parceiroNome = '—';
        $parceiroTipo = null;

        if ($pesagem->tipo === 'compra') {
            $parceiroId = $pesagem->fornecedor_id;
            $parceiroNome = $pesagem->fornecedor->razao_social
                ?? $pesagem->fornecedor->nome_fantasia
                ?? '—';
            $parceiroTipo = 'Fornecedor';
        } elseif ($pesagem->tipo === 'venda') {
            $parceiroId = $pesagem->cliente_id;
            $parceiroNome = $pesagem->cliente->razao_social
                ?? $pesagem->cliente->nome_fantasia
                ?? '—';
            $parceiroTipo = 'Cliente';
        }

        return [
            'id' => $pesagem->id,
            'empresa_id' => $pesagem->empresa_id,
            'filial_id' => $pesagem->filial_id,
            'filial' => $pesagem->filial->descricao ?? 'Matriz',
            'tipo' => $pesagem->tipo,
            'acao' => $tipoEvento,
            'status' => $pesagem->status,
            'direcao' => $direcao,
            'timestamp' => $timestamp->format('H:i:s'),
            'timestamp_iso' => $timestamp->toIso8601String(),
            'motorista' => $motorista,
            'usuario' => $usuarioNome,
            'fornecedor' => $fornecedorNome,
            'parceiro_id' => $parceiroId,
            'parceiro_nome' => $parceiroNome,
            'parceiro_tipo' => $parceiroTipo,
            'produtos' => $produtos->values()->all(),
            'produtos_resumo' => $produtosResumo !== '' ? $produtosResumo : '—',
            'produtos_count' => $produtos->count(),
            'produtos_itens' => $itensProduto,
            'pesos' => [
                'bruto' => $pesoBruto,
                'tara' => $tara,
                'bag' => $pesoBag,
                'liquido' => $pesoLiquido,
                'final' => $pesoFinal,
            ],
            'peso_total' => $pesoFinal > 0 ? $pesoFinal : $pesoLiquido,
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
