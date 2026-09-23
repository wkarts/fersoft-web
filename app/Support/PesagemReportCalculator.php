<?php

namespace App\Support;

use App\Models\Pesagem;
use Illuminate\Support\Collection;

/**
 * Consolidação exclusivamente de APRESENTAÇÃO para pesagens.
 *
 * Não altera models, banco, conciliação persistida, estoque ou financeiro.
 * A função desta classe é impedir que estados sucessivos do mesmo veículo
 * sejam somados como se fossem pesos físicos independentes no front/relatórios.
 */
class PesagemReportCalculator
{
    public static function summarize(Pesagem $pesagem): array
    {
        $tickets = $pesagem->relationLoaded('tickets')
            ? $pesagem->tickets
            : $pesagem->tickets()->with('produto')->get();

        $tickets = collect($tickets)->filter(function ($ticket) {
            return $ticket
                && is_numeric($ticket->peso)
                && (float) $ticket->peso >= 0
                && in_array(strtolower((string) $ticket->tipo), ['entrada', 'saida', 'avulsa'], true);
        })->values();

        $ordenados = $tickets->sortBy(function ($ticket) {
            $data = $ticket->inicio ?? $ticket->created_at ?? $ticket->fim ?? null;
            $timestamp = $data ? strtotime((string) $data) : 0;
            return sprintf('%020d-%020d', $timestamp ?: 0, (int) ($ticket->id ?? 0));
        })->values();

        $primeiro = $ordenados->first();
        $ultimo = $ordenados->last();

        $pesoInicial = $primeiro ? self::f($primeiro->peso) : 0.0;
        $pesoFinal = $ultimo ? self::f($ultimo->peso) : 0.0;
        $variacaoFisica = ($primeiro && $ultimo)
            ? abs($pesoInicial - $pesoFinal)
            : 0.0;

        $produtos = $tickets
            ->groupBy(function ($ticket) {
                return $ticket->produto_id ?: ('sem_produto_' . (int) $ticket->id);
            })
            ->map(function (Collection $grupo) {
                return self::summarizeProduct($grupo);
            });

        // Fonte de verdade de APRESENTAÇÃO do líquido consolidado:
        // soma dos líquidos conciliados por produto, nunca soma de pesos físicos intermediários.
        $pesoLiquidoTotal = (float) $produtos->sum('peso_liquido');
        $valorTotalOperacao = (float) $produtos->sum('valor_total');
        $pesoBagTotal = (float) $produtos->sum('peso_bag');

        // Preserva o resultado final já conciliado/persistido sempre que existir.
        // Isso evita alterar conformidade financeira/estoque apenas por mudança visual.
        $atributos = $pesagem->getAttributes();
        $temPesoFinalPersistido = array_key_exists('peso_final', $atributos)
            && $atributos['peso_final'] !== null
            && is_numeric($atributos['peso_final']);

        $pesoFinalLiquido = $temPesoFinalPersistido
            ? max(0.0, (float) $atributos['peso_final'])
            : $pesoLiquidoTotal;

        // Se o peso final persistido exceder o líquido consolidado (caso legado/atípico),
        // não inventa desconto negativo: apenas preserva o final e mostra desconto zero.
        $descontos = $pesoFinalLiquido <= $pesoLiquidoTotal
            ? max(0.0, $pesoLiquidoTotal - $pesoFinalLiquido)
            : 0.0;

        $entradasLegado = (float) $tickets->where('tipo', 'entrada')->sum(fn ($t) => self::f($t->peso));
        $saidasLegado = (float) $tickets->where('tipo', 'saida')->sum(fn ($t) => self::f($t->peso));
        $avulsasLegado = (float) $tickets->where('tipo', 'avulsa')->sum(fn ($t) => self::f($t->peso));

        // Apenas diagnóstico; não substitui o líquido por produto nem altera persistência.
        $divergenciaFisica = abs($variacaoFisica - $pesoLiquidoTotal);

        return [
            'tickets' => $ordenados,
            'produtos' => $produtos,

            'peso_inicial' => $pesoInicial,
            'peso_final' => $pesoFinal,
            // Alias mantido para compatibilidade com consumidores da revisão intermediária.
            'peso_final_veiculo' => $pesoFinal,
            'variacao_fisica' => $variacaoFisica,
            'peso_liquido_total' => $pesoLiquidoTotal,
            'peso_bag_total' => $pesoBagTotal,
            'descontos' => $descontos,
            'peso_final_liquido' => $pesoFinalLiquido,
            'valor_total_operacao' => $valorTotalOperacao,

            'tem_sequencia' => $ordenados->count() >= 2,
            'divergencia_fisica' => $divergenciaFisica,
            'conciliacao_fisica_compativel' => $ordenados->count() >= 2 && $divergenciaFisica <= 0.01,

            // Mantidos somente para conferência/fallback técnico. Não devem ser usados
            // como peso físico do veículo no resumo visual.
            'legacy_total_entrada' => $entradasLegado,
            'legacy_total_saida' => $saidasLegado,
            'legacy_total_avulsa' => $avulsasLegado,
        ];
    }

    private static function summarizeProduct(Collection $tickets): array
    {
        $ordenados = $tickets->sortBy(function ($ticket) {
            $data = $ticket->inicio ?? $ticket->created_at ?? $ticket->fim ?? null;
            $timestamp = $data ? strtotime((string) $data) : 0;
            return sprintf('%020d-%020d', $timestamp ?: 0, (int) ($ticket->id ?? 0));
        })->values();

        $produto = optional($ordenados->first())->produto;

        $entradaBruta = (float) $ordenados->where('tipo', 'entrada')->sum(fn ($t) => self::f($t->peso));
        $saidaBruta = (float) $ordenados->where('tipo', 'saida')->sum(fn ($t) => self::f($t->peso));
        $avulsaBruta = (float) $ordenados->where('tipo', 'avulsa')->sum(fn ($t) => self::f($t->peso));

        // Mantém a mesma base líquida praticada pela aplicação: peso - recipiente por leitura.
        $entradaAjustada = (float) $ordenados->where('tipo', 'entrada')->sum(fn ($t) => self::pesoAjustado($t));
        $saidaAjustada = (float) $ordenados->where('tipo', 'saida')->sum(fn ($t) => self::pesoAjustado($t));
        $avulsaAjustada = (float) $ordenados->where('tipo', 'avulsa')->sum(fn ($t) => self::pesoAjustado($t));

        $pesoLiquido = abs(($entradaAjustada + $avulsaAjustada) - $saidaAjustada);
        $pesoBag = (float) $ordenados->sum(fn ($t) => self::f($t->peso_bag ?? 0));

        $valorUnitario = 0.0;
        foreach ($ordenados as $ticket) {
            $candidato = (float) ($ticket->valor_unitario ?? 0);
            if ($candidato > 0) {
                $valorUnitario = $candidato;
                break;
            }
        }

        if ($valorUnitario <= 0 && $produto) {
            $valorUnitario = max(0.0, (float) (
                $produto->valor_venda
                ?? $produto->valor_compra
                ?? 0
            ));
        }

        return [
            'produto' => $produto,
            'tickets' => $ordenados,
            'entrada' => $entradaBruta,
            'saida' => $saidaBruta,
            'avulsa' => $avulsaBruta,
            'peso_bruto' => max($entradaBruta + $avulsaBruta, $saidaBruta),
            'peso_bag' => $pesoBag,
            'peso_liquido' => $pesoLiquido,
            'valor_unitario' => $valorUnitario,
            // O valor financeiro do produto é sempre líquido x unitário.
            'valor_total' => $pesoLiquido * $valorUnitario,
        ];
    }

    private static function pesoAjustado($ticket): float
    {
        return max(0.0, self::f($ticket->peso) - self::f($ticket->peso_bag ?? 0));
    }

    private static function f($value): float
    {
        return max(0.0, (float) ($value ?? 0));
    }
}
