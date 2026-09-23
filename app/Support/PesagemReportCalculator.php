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
        // soma das diferenças físicas por produto, antes de descontos de recipiente/qualidade.
        $pesoLiquidoTotal = (float) $produtos->sum('peso_liquido');
        $valorTotalOperacao = (float) $produtos->sum('valor_total');
        $pesoBagTotal = (float) $produtos->sum('peso_bag');

        // Regra histórica do ticket simples:
        // impurezas/descontos são compostos somente por recipiente (peso_bag)
        // + percentuais efetivamente configurados na pesagem.
        //
        // IMPORTANTE: pesagens.peso_final NÃO é usado para inferir desconto visual.
        // Esse campo é operacional e, em pesagens ainda em andamento ou legadas,
        // pode estar zerado sem significar perda de 100% do peso.
        $percentualDesconto = 0.0;

        if (!empty($pesagem->danificado)) {
            $percentualDesconto += self::f($pesagem->danificado_desconto ?? 0);
        }
        if (!empty($pesagem->quebrado)) {
            $percentualDesconto += self::f($pesagem->quebrado_desconto ?? 0);
        }
        if (!empty($pesagem->esverdeado)) {
            $percentualDesconto += self::f($pesagem->esverdeado_desconto ?? 0);
        }
        if (!empty($pesagem->ardido)) {
            $percentualDesconto += self::f($pesagem->ardido_desconto ?? 0);
        }
        if (!empty($pesagem->secagem)) {
            $percentualDesconto += self::f($pesagem->secagem_desconto ?? 0);
        }

        // Umidade e impureza sempre participam quando houver percentual informado,
        // mantendo o comportamento histórico do relatório.
        $percentualDesconto += self::f($pesagem->umidade_desconto ?? 0);
        $percentualDesconto += self::f($pesagem->impureza_desconto ?? 0);

        $descontoPercentual = $pesoLiquidoTotal > 0
            ? ($pesoLiquidoTotal * ($percentualDesconto / 100.0))
            : 0.0;

        $descontos = min(
            $pesoLiquidoTotal,
            max(0.0, $pesoBagTotal + $descontoPercentual)
        );

        $pesoFinalLiquido = max(0.0, $pesoLiquidoTotal - $descontos);

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

        // O peso líquido físico do produto é a diferença entre leituras brutas.
        // O recipiente (peso_bag) é desconto/impureza e será aplicado uma única vez
        // no consolidado da pesagem. Isso preserva o comportamento histórico do
        // comprovante simples e evita dupla subtração.
        $pesoLiquido = abs(($entradaBruta + $avulsaBruta) - $saidaBruta);
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

    private static function f($value): float
    {
        return max(0.0, (float) ($value ?? 0));
    }
}
