<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Fornecedor;
use App\Models\Pesagem;
use App\Models\Produto;
use App\Models\Veiculo;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EstoqueFisicoPesagemService
{
    /**
     * Registra os movimentos físicos gerados por uma pesagem concluída.
     * A operação é idempotente por empresa, pesagem e produto.
     */
    public function registrar(Pesagem $pesagem, int $empresaId, ?int $usuarioId = null): int
    {
        if (!Schema::hasTable('estoque_fisico_movimentos')) {
            return 0;
        }

        if ((int) $pesagem->empresa_id !== $empresaId) {
            throw new \RuntimeException('A pesagem não pertence à empresa autenticada.');
        }

        $pesagem->loadMissing('tickets');

        $configNota = DB::table('config_notas')
            ->where('empresa_id', $empresaId)
            ->first();

        $cnpjEmpresa = preg_replace('/\D/', '', (string) ($configNota->cnpj ?? ''));
        $filialId = ((int) ($pesagem->filial_id ?? 0)) > 0 ? (int) $pesagem->filial_id : null;
        $dataMovimento = $this->resolverDataMovimento($pesagem);
        $inseridos = 0;

        /** @var Collection<int, Collection> $ticketsAgrupados */
        $ticketsAgrupados = $pesagem->tickets
            ->filter(fn ($ticket) => (int) ($ticket->produto_id ?? 0) > 0)
            ->groupBy('produto_id');

        foreach ($ticketsAgrupados as $produtoId => $ticketsProduto) {
            $produtoId = (int) $produtoId;
            $movimento = $this->montarMovimento(
                $pesagem,
                $ticketsProduto,
                $produtoId,
                $empresaId,
                $filialId,
                $usuarioId,
                $cnpjEmpresa,
                $dataMovimento
            );

            if ($movimento === null) {
                continue;
            }

            $existia = DB::table('estoque_fisico_movimentos')
                ->where('empresa_id', $empresaId)
                ->where('pesagem_id', $pesagem->id)
                ->where('produto_id', $produtoId)
                ->exists();

            if ($existia) {
                unset($movimento['created_at']);
            }

            DB::table('estoque_fisico_movimentos')->updateOrInsert(
                [
                    'empresa_id' => $empresaId,
                    'pesagem_id' => $pesagem->id,
                    'produto_id' => $produtoId,
                ],
                $movimento
            );

            if (!$existia) {
                $inseridos++;
            }
        }

        return $inseridos;
    }

    public function sincronizarEmpresa(int $empresaId, ?int $usuarioId = null): array
    {
        if (!Schema::hasTable('estoque_fisico_movimentos')) {
            throw new \RuntimeException('A migration de estoque físico ainda não foi aplicada.');
        }

        $processadas = 0;
        $inseridos = 0;
        $erros = [];

        Pesagem::query()
            ->where('empresa_id', $empresaId)
            ->whereIn('status', ['concluído', 'concluido', 'CONCLUÍDO', 'CONCLUIDO'])
            ->with('tickets')
            ->orderBy('id')
            ->chunkById(100, function ($pesagens) use ($empresaId, $usuarioId, &$processadas, &$inseridos, &$erros): void {
                foreach ($pesagens as $pesagem) {
                    try {
                        $inseridos += $this->registrar($pesagem, $empresaId, $usuarioId);
                        $processadas++;
                    } catch (\Throwable $e) {
                        $erros[] = "Pesagem #{$pesagem->id}: {$e->getMessage()}";
                    }
                }
            });

        return compact('processadas', 'inseridos', 'erros');
    }

    private function montarMovimento(
        Pesagem $pesagem,
        Collection $tickets,
        int $produtoId,
        int $empresaId,
        ?int $filialId,
        ?int $usuarioId,
        string $cnpjEmpresa,
        string $dataMovimento
    ): ?array {
        $tipoMovimento = strtolower((string) $pesagem->tipo) === 'venda' ? 'saida' : 'entrada';

        $pesoEntrada = (float) $tickets->where('tipo', 'entrada')->sum('peso');
        $pesoSaida = (float) $tickets->where('tipo', 'saida')->sum('peso');
        $pesoAvulso = (float) $tickets->where('tipo', 'avulsa')->sum('peso');

        $pesoLiquidoBalanca = abs(($pesoEntrada + $pesoAvulso) - $pesoSaida);
        if ($pesoLiquidoBalanca <= 0) {
            $pesoLiquidoBalanca = max($pesoEntrada + $pesoAvulso, $pesoSaida);
        }

        $pesoBag = (float) $tickets->sum('peso_bag');
        $pesoLiquidoBalanca = max(0, $pesoLiquidoBalanca - $pesoBag);
        if ($pesoLiquidoBalanca <= 0) {
            return null;
        }

        $percentualAbatimento = $this->percentualAbatimento($pesagem);
        $pesoImpureza = $pesoLiquidoBalanca * ($percentualAbatimento / 100);
        $quantidade = max(0, $pesoLiquidoBalanca - $pesoImpureza);
        if ($quantidade <= 0) {
            return null;
        }

        $valorUnitario = $this->resolverValorUnitario(
            $pesagem,
            $tickets,
            $produtoId,
            $tipoMovimento,
            $empresaId,
            $cnpjEmpresa
        );

        return [
            'filial_id' => $filialId,
            'usuario_id' => $usuarioId ?: ($pesagem->usuario_id ?: null),
            'tipo' => $tipoMovimento,
            'peso_bruto' => round($pesoLiquidoBalanca, 2),
            'peso_impureza' => round($pesoImpureza, 2),
            'quantidade' => round($quantidade, 2),
            'valor_unitario' => round($valorUnitario, 4),
            'valor_total' => round($quantidade * $valorUnitario, 2),
            'data_movimento' => $dataMovimento,
            'updated_at' => now(),
            'created_at' => now(),
        ];
    }

    private function percentualAbatimento(Pesagem $pesagem): float
    {
        $percentual = 0.0;

        foreach (['danificado', 'quebrado', 'esverdeado', 'ardido', 'secagem'] as $campo) {
            if ((bool) $pesagem->{$campo}) {
                $percentual += (float) ($pesagem->{$campo . '_desconto'} ?? 0);
            }
        }

        $percentual += (float) ($pesagem->umidade_desconto ?? 0);
        $percentual += (float) ($pesagem->impureza_desconto ?? 0);

        return max(0, min(100, $percentual));
    }

    private function resolverValorUnitario(
        Pesagem $pesagem,
        Collection $tickets,
        int $produtoId,
        string $tipoMovimento,
        int $empresaId,
        string $cnpjEmpresa
    ): float {
        $ticketComValor = $tickets->first(fn ($ticket) => (float) ($ticket->valor_unitario ?? 0) > 0);
        $valor = (float) ($ticketComValor->valor_unitario ?? 0);
        if ($valor > 0) {
            return $valor;
        }

        $tabelaPrecoId = null;
        if ($tipoMovimento === 'entrada' && $pesagem->fornecedor_id) {
            $parceiro = Fornecedor::where('empresa_id', $empresaId)->find($pesagem->fornecedor_id);
            $tabelaPrecoId = $parceiro?->tabela_preco_id;
        } elseif ($tipoMovimento === 'saida' && $pesagem->cliente_id) {
            $parceiro = Cliente::where('empresa_id', $empresaId)->find($pesagem->cliente_id);
            $tabelaPrecoId = $parceiro?->tabela_preco_id;
        }

        if ($tabelaPrecoId) {
            $tipoFrete = 'ENTREGA';
            if ($pesagem->veiculo_id) {
                $veiculo = Veiculo::where('empresa_id', $empresaId)->find($pesagem->veiculo_id);
                $docVeiculo = preg_replace('/\D/', '', (string) ($veiculo->proprietario_documento ?? ''));
                if ($cnpjEmpresa !== '' && $docVeiculo === $cnpjEmpresa) {
                    $tipoFrete = 'COLETA';
                }
            }

            $valor = (float) DB::table('tabela_preco_itens')
                ->where('tabela_preco_id', $tabelaPrecoId)
                ->where('produto_id', $produtoId)
                ->where('tipo_frete', $tipoFrete)
                ->value('valor_kg');
        }

        if ($valor > 0) {
            return $valor;
        }

        $produto = Produto::where('empresa_id', $empresaId)->find($produtoId);

        return $tipoMovimento === 'entrada'
            ? (float) ($produto->valor_compra ?? 0)
            : (float) ($produto->valor_venda ?? 0);
    }

    private function resolverDataMovimento(Pesagem $pesagem): string
    {
        foreach ([$pesagem->dt_registro ?? null, $pesagem->created_at ?? null] as $data) {
            if ($data) {
                return Carbon::parse($data)->toDateString();
            }
        }

        return now()->toDateString();
    }
}
