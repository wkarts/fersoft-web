<?php

namespace App\Services\Pagamentos;

use App\Models\PagamentoCobranca;
use Argws\ElectronicPayments\{Charge, ChargeStatus};

final class ParcelamentoCartao
{
    /** Confere o grupo inteiro antes de autorizar a venda ou baixar o título. */
    public static function conferir(PagamentoCobranca $p, Charge $primeira, Cobrancas $servico, ?string $evento): ?array
    {
        $grupo = $primeira->installmentId;
        if (!$grupo || $primeira->reference !== $p->referencia || ($p->parcelamento_externo && $grupo !== $p->parcelamento_externo)) {
            return null;
        }
        $sdk = $servico->connection($p->conexao)->sdk();
        $ids = [];
        $estados = [];
        $total = 0;
        $dataPagamento = null;
        $numero = (int) $p->numero_parcelas;
        do {
            $pagina = $sdk->payment->listPayments(query: ['installment' => $grupo, 'offset' => count($ids), 'limit' => 100]);
            if (!is_array($pagina['data'] ?? null) || !is_bool($pagina['hasMore'] ?? null) || !$pagina['data']) {
                return null;
            }
            foreach ($pagina['data'] as $linha) {
                $id = $linha['id'] ?? null;
                if (!is_string($id) || $id === '' || isset($ids[$id]) || count($ids) >= $numero ||
                    ($linha['externalReference'] ?? null) !== $p->referencia || ($linha['installment'] ?? null) !== $grupo ||
                    ($linha['billingType'] ?? null) !== 'CREDIT_CARD' || !is_numeric($linha['value'] ?? null)) {
                    return null;
                }
                $centavos = (int) round($linha['value'] * 100);
                if ($centavos < 1) {
                    return null;
                }
                $total += $centavos;
                $ids[$id] = true;
                $status = $linha['status'] ?? '';
                if (!empty($linha['deleted'])) {
                    $status = 'DELETED';
                } elseif (!empty($linha['refunds'])) {
                    $status = 'REFUND';
                }
                $estados[$status] = true;
                if (in_array($status, ['CONFIRMED', 'RECEIVED'], true)) {
                    $data = $linha['paymentDate'] ?? '';
                    if (!is_string($data) || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $data)) {
                        return null;
                    }
                    $dataPagamento = max($dataPagamento ?? $data, $data);
                }
            }
        } while ($pagina['hasMore']);
        if (count($ids) !== $numero || $total !== (int) $p->centavos || !isset($ids[$primeira->id->id]) || ($evento !== null && !isset($ids[$evento]))) {
            return null;
        }
        $status = match (true) {
            array_keys($estados) === ['RECEIVED'] => ChargeStatus::Settled,
            !array_diff(array_keys($estados), ['RECEIVED', 'CONFIRMED']) => ChargeStatus::Confirmed,
            array_keys($estados) === ['DELETED'] => ChargeStatus::Cancelled,
            array_keys($estados) === ['REFUNDED'] => ChargeStatus::Refunded,
            !array_diff(array_keys($estados), ['PENDING', 'AWAITING_RISK_ANALYSIS']) => ChargeStatus::Pending,
            default => ChargeStatus::Unknown,
        };
        return [new Charge($primeira->id, $p->referencia, $status, $status->value, $total,
            $primeira->checkoutUrl, null, $grupo), $dataPagamento];
    }
}
