<?php

namespace App\Services\Pagamentos;

use App\Models\{Cliente, ContaReceber, PagamentoCobranca, PagamentoConexao};
use Argws\ElectronicPayments\{Charge, ChargeId, ChargeRequest, Environment, PaymentEvent, PaymentException, PaymentMethod, Reconciliation};
use Argws\ElectronicPayments\Asaas\{AsaasProvider, Connection};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Cobrancas
{
    public const FORMAS = ['pix' => '17', 'boleto' => '15', 'credit_card' => '03'];

    public function connection(PagamentoConexao $c): Connection
    {
        abort_unless($c->provedor === 'asaas', 409, 'Esta conexão pertence a outro provedor.');
        return new Connection($c->account(), fn () => Estabelecimento::fiscal($c->empresa_id, $c->local),
            fn () => $c->credencial);
    }

    public function provider(PagamentoConexao $c): AsaasProvider
    {
        return new AsaasProvider($this->connection($c));
    }

    public function titulo(PagamentoConexao $c, int $id)
    {
        return ContaReceber::withoutGlobalScopes()->withoutDeleted()->where('empresa_id', $c->empresa_id)
            ->where('filial_id', $c->local ?: null)->whereKey($id);
    }

    private function conferirParcela(array $parcelas, ?int $indice, string $forma, int $cobrado, int $total): void
    {
        if (!$parcelas) {
            abort_unless($indice === null && $cobrado === $total, 409, 'O valor da cobrança diverge do total da venda.');
            return;
        }
        $soma = 0;
        $selecionada = null;
        foreach ($parcelas as $posicao => $parcela) {
            $valor = str_replace(',', '.', (string) ($parcela['valor'] ?? ''));
            abort_unless(is_numeric($valor) && (float) $valor > 0, 422, 'Valor inválido no pagamento múltiplo.');
            $centavos = (int) round((float) $valor * 100);
            $soma += $centavos;
            if ($posicao === $indice && ($parcela['tipo'] ?? null) === $forma) {
                $selecionada = $centavos;
            }
        }
        abort_unless($soma === $total && $selecionada === $cobrado, 409,
            'Confira a parcela eletrônica e a soma das formas de pagamento.');
    }

    public function assinaturaCarrinho(array $itens): string
    {
        abort_if(!$itens, 422, 'O carrinho está vazio.');
        $linhas = [];
        foreach ($itens as $item) {
            $id = (int) ($item['id'] ?? 0);
            $quantidade = str_replace(',', '.', (string) ($item['quantidade'] ?? ''));
            $valor = str_replace(',', '.', (string) ($item['valor'] ?? ''));
            abort_unless($id > 0 && is_numeric($quantidade) && is_numeric($valor), 422, 'Item inválido no carrinho.');
            $linhas[] = [$id, number_format((float) $quantidade, 8, '.', ''), number_format((float) $valor, 8, '.', '')];
        }
        return hash('sha256', json_encode($linhas, JSON_THROW_ON_ERROR));
    }

    public function emitir(Estabelecimento $site, PaymentMethod $metodo, ?int $tituloId, string $conexaoEsperada, array $pdv = [], int $numeroParcelas = 1): PagamentoCobranca
    {
        $c = $site->ativa();
        [$cobranca, $cliente, $vencimento, $nova] = DB::transaction(function () use ($site, $c, $metodo, $tituloId, $conexaoEsperada, $pdv, $numeroParcelas) {
            // Serializa a reserva antes da primeira chamada remota.
            $atual = PagamentoConexao::lockForUpdate()->findOrFail($c->id);
            abort_unless($atual->selecionada && $atual->webhook_id === $conexaoEsperada, 409, 'O ambiente mudou. Reabra a tela.');
            abort_unless(isset($atual->metodosAtivos()[$metodo->value]), 403, 'Forma de pagamento desativada neste estabelecimento.');
            abort_unless($numeroParcelas >= 1 && $numeroParcelas <= ($metodo === PaymentMethod::CreditCard ? $atual->max_parcelas_cartao : 1),
                422, 'Quantidade de parcelas incompatível com o estabelecimento ou a forma de pagamento.');
            if ($tituloId) {
                $titulo = $this->titulo($c, $tituloId)->lockForUpdate()->firstOrFail();
                abort_if($titulo->status || $titulo->valor_recebido > 0 || $titulo->boleto()->exists(), 409,
                    'O título está baixado, tem recebimento parcial ou já possui boleto.');
                $clienteId = $titulo->cliente_id ?: ($titulo->venda->cliente_id ?? $titulo->vendaCaixa->cliente_id ?? null);
                $centavos = (int) round($titulo->valor_integral * 100);
                $data = max(today()->toDateString(), substr($titulo->data_vencimento, 0, 10));
                $origem = 'titulo-'.$tituloId;
            } else {
                $clienteId = $pdv['cliente'];
                $centavos = (int) $pdv['centavos'];
                $this->conferirParcela($pdv['parcelas'] ?? [], isset($pdv['parcela']) ? (int) $pdv['parcela'] : null,
                    self::FORMAS[$metodo->value], $centavos, (int) $pdv['total']);
                $data = today()->toDateString();
                $origem = 'pdv-'.$site->usuario->id.'-'.$pdv['operacao'];
            }
            $cliente = Cliente::withoutDeleted()->where('empresa_id', $site->empresa)->findOrFail($clienteId);
            abort_unless($centavos >= $numeroParcelas && trim($cliente->razao_social) !== '' &&
                in_array(strlen(preg_replace('/\D/', '', $cliente->cpf_cnpj ?? '')), [11, 14], true),
                422, 'Informe valor positivo e cliente com nome e CPF/CNPJ.');
            $assinatura = $tituloId ? null : $this->assinaturaCarrinho($pdv['itens']);
            $cobranca = PagamentoCobranca::firstOrCreate(['conexao_id' => $c->id, 'origem' => $origem, 'vigente' => true], [
                'referencia' => (string) Str::uuid(), 'usuario_id' => $site->usuario->id, 'cliente_id' => $cliente->id,
                'numero_parcelas' => $numeroParcelas, 'parcela_pdv' => $pdv['parcela'] ?? null, 'carrinho_hash' => $assinatura, 'centavos' => $centavos, 'metodo' => $metodo->value, 'conta_receber_id' => $tituloId,
            ]);
            abort_unless($cobranca->numero_parcelas == $numeroParcelas && $cobranca->centavos == $centavos && $cobranca->cliente_id == $cliente->id &&
                ($cobranca->parcela_pdv === null ? null : (int) $cobranca->parcela_pdv) === (isset($pdv['parcela']) ? (int) $pdv['parcela'] : null) &&
                $cobranca->metodo === $metodo->value && $cobranca->carrinho_hash === $assinatura, 409, 'A operação já possui uma cobrança com outros dados.');
            return [$cobranca, $cliente, $data, $cobranca->wasRecentlyCreated];
        });
        if ($nova) {
            $api = $this->provider($c);
            $ref = $c->webhook_id.'-'.$cliente->id;
            $enviada = false;
            try {
                $clientes = $api->findCustomers($ref);
                abort_if(count($clientes) > 1, 409, 'Mais de um pagador corresponde ao cliente no Asaas.');
                $pagador = $clientes[0] ?? $api->createCustomer($cliente->razao_social,
                    preg_replace('/\D/', '', $cliente->cpf_cnpj), $ref, $cliente->email ?: null);
                $enviada = true;
                $remote = $api->create(new ChargeRequest($pagador, $cobranca->referencia, $cobranca->centavos,
                    $metodo, new \DateTimeImmutable($vencimento), $numeroParcelas));
                // Um webhook pode ter conciliado a cobrança enquanto o POST estava em trânsito.
                PagamentoCobranca::whereKey($cobranca->id)->whereNull('externo')
                    ->update(['externo' => $remote->id->id, 'parcelamento_externo' => $remote->installmentId, 'situacao' => $remote->status->value]);
                $cobranca->refresh();
            } catch (\Throwable $e) {
                if (!$enviada || ($e instanceof PaymentException && !$e->outcomeUnknown && $e->getCode() >= 400 && $e->getCode() < 500)) {
                    $cobranca->update(['situacao' => 'recusada', 'vigente' => null]);
                }
                throw $e;
            }
        }
        return $cobranca;
    }

    public function sincronizar(PagamentoCobranca $cobranca, ?string $eventoExterno = null): ?Charge
    {
        return DB::transaction(function () use ($cobranca, $eventoExterno) {
            $p = PagamentoCobranca::lockForUpdate()->findOrFail($cobranca->id);
            $c = $p->conexao;
            $api = $this->provider($c);
            $parcelada = $p->numero_parcelas > 1;
            $dataGrupo = null;
            if ($p->externo) {
                abort_if(!$parcelada && $eventoExterno !== null && $eventoExterno !== $p->externo, 409);
                $remote = $api->get(new ChargeId($c->account(), 'asaas', $p->externo));
            } else {
                $matches = $api->findByReference($p->referencia);
                if (!$matches || (!$parcelada && count($matches) !== 1)) {
                    $p->update(['conciliacao' => 'review']);
                    return null;
                }
                $remote = $matches[0];
                abort_if(!$parcelada && $eventoExterno !== null && $eventoExterno !== $remote->id->id, 409);
            }
            if ($parcelada) {
                $grupo = ParcelamentoCartao::conferir($p, $remote, $this, $eventoExterno);
                if (!$grupo) {
                    $p->update(['conciliacao' => 'review']);
                    return null;
                }
                [$remote, $dataGrupo] = $grupo;
                $p->parcelamento_externo = $remote->installmentId;
            }
            $parecer = Reconciliation::compare($remote, new ChargeId($c->account(), 'asaas', $p->externo ?: $remote->id->id),
                $p->referencia, $p->centavos);
            $p->externo = $remote->id->id;
            $p->situacao = $remote->status->value;
            $p->conciliacao = $parecer->value;
            if ($parecer !== Reconciliation::Review && in_array($remote->status->value, ['cancelled', 'refunded'], true)) {
                $p->vigente = null;
            }
            if ($p->conta_receber_id && !$p->baixada_em && in_array($parecer, [Reconciliation::PaymentConfirmed, Reconciliation::CreditAvailable], true)) {
                $titulo = $this->titulo($c, $p->conta_receber_id)->lockForUpdate()->first();
                if (!$titulo || $titulo->status || $titulo->valor_recebido > 0 || (int) round($titulo->valor_integral * 100) !== (int) $p->centavos
                    || ($titulo->cliente_id && $titulo->cliente_id != $p->cliente_id) || $titulo->boleto()->exists()) {
                    $p->conciliacao = Reconciliation::Review->value;
                } else {
                    $data = $dataGrupo;
                    if (!$parcelada) {
                        $recebimentos = $api->reports()->page('payments', ['externalReference' => $p->referencia])['report'];
                        $linha = $recebimentos['rows'][0] ?? [];
                        $data = $linha['paymentDate'] ?? null;
                        if (count($recebimentos['rows']) !== 1 || $recebimentos['has_more'] || ($linha['id'] ?? null) !== $p->externo
                            || !$data || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $data)) {
                            $p->update(['conciliacao' => Reconciliation::Review->value]);
                            return $remote;
                        }
                    }
                    $titulo->status = true;
                    $titulo->valor_recebido = $titulo->valor_integral;
                    $titulo->data_recebimento = $data;
                    $titulo->tipo_pagamento = ['pix' => 'Pagamento Instantâneo (PIX)', 'boleto' => 'Boleto Bancário', 'credit_card' => 'Cartão de Crédito'][$p->metodo];
                    $titulo->save();
                    $p->baixada_em = now();
                }
            }
            $p->save();
            return $remote;
        });
    }

    public function evento(PagamentoCobranca $cobranca, PaymentEvent $event): bool
    {
        return DB::transaction(function () use ($cobranca, $event) {
            $p = PagamentoCobranca::lockForUpdate()->findOrFail($cobranca->id);
            $key = ['cobranca_id' => $p->id, 'evento_id' => $event->eventId];
            if (DB::table('pagamento_eventos')->where($key)->exists()) {
                return true;
            }
            if (!$this->sincronizar($p, $event->charge->id)) {
                return false;
            }
            DB::table('pagamento_eventos')->insert($key + ['tipo' => $event->eventType, 'processado_em' => now()]);
            return true;
        });
    }

    public function protegerVenda(int $empresa, int $id, bool $pdv = false): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('pagamento_cobrancas')) {
            return;
        }
        if ($pdv) {
            \App\Models\VendaCaixa::where('empresa_id', $empresa)->whereKey($id)->lockForUpdate()->first(['id']);
        }
        $titulos = ContaReceber::withoutGlobalScopes()->where('empresa_id', $empresa)
            ->where($pdv ? 'venda_caixa_id' : 'venda_id', $id)->lockForUpdate()->pluck('id');
        $cobradas = PagamentoCobranca::whereHas('conexao', fn ($q) => $q->where('empresa_id', $empresa)->where('ambiente', 'production'))
            ->where('vigente', true)->where(function ($q) use ($pdv, $id, $titulos) {
                $q->whereIn('conta_receber_id', $titulos);
                if ($pdv) {
                    $q->orWhere('venda_caixa_id', $id);
                }
            });
        abort_if($cobradas->exists(), 409, 'Esta venda possui cobrança Asaas. Confira o recebimento antes de substituir os títulos ou alterar a venda.');
    }

    public function reservarPdv(Estabelecimento $site, array $venda, float $total): PagamentoCobranca
    {
        $p = PagamentoCobranca::whereIn('conexao_id', $site->conexoes()->select('id'))
            ->where('referencia', $venda['pagamento_eletronico'])->whereNull('conta_receber_id')
            ->where('usuario_id', $site->usuario->id)->lockForUpdate()->firstOrFail();
        $p->conexao->account()->authorize(Estabelecimento::fiscal($site->empresa, $site->local));
        abort_unless($p->conexao->ambiente === Environment::Production->value && !$p->venda_caixa_id && !$p->acao_pendente, 409,
            'A venda exige uma cobrança de produção disponível.');
        $this->conferirParcela($venda['pag_multi'] ?? [], $p->parcela_pdv !== null ? (int) $p->parcela_pdv : null,
            self::FORMAS[$p->metodo], (int) $p->centavos, (int) round($total * 100));
        $this->sincronizar($p);
        $p->refresh();
        abort_unless(
            in_array($p->conciliacao, ['payment_confirmed', 'credit_available'], true) &&
            $p->carrinho_hash === $this->assinaturaCarrinho($venda['itens'] ?? []) &&
            $p->cliente_id == $venda['cliente'] &&
            ($p->parcela_pdv !== null || self::FORMAS[$p->metodo] === $venda['tipo_pagamento']) &&
            empty($venda['rascunho']) && empty($venda['consignado']) && max(0, (int) ($venda['filial_id'] ?? 0)) === $site->local,
            409, 'A cobrança deve estar confirmada em produção e corresponder a esta venda.');
        return $p;
    }
}
