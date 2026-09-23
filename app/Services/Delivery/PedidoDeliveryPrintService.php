<?php

namespace App\Services\Delivery;

use App\Models\PedidoDelivery;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;

class PedidoDeliveryPrintService
{
    private const PAPER_WIDTH_MM = 80.0;
    private const MIN_HEIGHT_MM = 160.0;
    private const MAX_HEIGHT_MM = 1000.0;

    /**
     * Renderiza o cupom operacional do Delivery.
     *
     * Este relatório pertence ao domínio da aplicação e não ao SPED-DA.
     * Nenhuma classe customizada é adicionada ao vendor.
     */
    public function render(PedidoDelivery $pedido): string
    {
        $pedido->loadMissing([
            'empresa.deliveryConfig.cidade',
            'cliente',
            'endereco._bairro',
            'endereco.cidade',
            'itens.produto.produto',
            'itens.produto.categoria',
            'itens.itensAdicionais.adicional',
            'itens.tamanho',
            'itens.sabores.produto.produto',
        ]);

        $dados = $this->buildViewData($pedido);

        $paper = [
            0,
            0,
            $this->mmToPoints(self::PAPER_WIDTH_MM),
            $this->mmToPoints($this->estimateHeightMm($dados)),
        ];

        return Pdf::loadView('pedidosDelivery.impressao_pedido', $dados)
            ->setPaper($paper, 'portrait')
            ->output();
    }

    protected function buildViewData(PedidoDelivery $pedido): array
    {
        $empresa = $pedido->empresa;
        $config = $empresa?->deliveryConfig;

        $itens = collect($pedido->itensOrdenadosPorPizza())
            ->map(function ($item) {
                $quantidade = max(0.0, (float) $item->quantidade);

                $sabores = $item->sabores
                    ->map(fn ($sabor) => $sabor->produto?->produto?->nome)
                    ->filter()
                    ->values();

                $adicionais = $item->itensAdicionais
                    ->map(function ($adicional) {
                        $model = $adicional->adicional;

                        return [
                            'nome' => $model ? $model->nome() : 'Adicional',
                            'quantidade' => (float) $adicional->quantidade,
                            'valor' => (float) ($model?->valor ?? 0),
                        ];
                    })
                    ->values();

                $produtoNome = $item->produto?->produto?->nome
                    ?? $item->produto?->descricao
                    ?? 'Produto';

                $tamanho = $item->tamanho ? $item->tamanho->nome() : null;

                // O valor gravado no item é a melhor referência histórica.
                // Em registros antigos sem snapshot, preserva-se o cálculo do Model.
                $valorLinha = (float) ($item->valor ?? 0);
                if ($valorLinha <= 0) {
                    $valorLinha = (float) $item->valorProduto() * $quantidade;
                }

                $valorUnitario = $quantidade > 0
                    ? $valorLinha / $quantidade
                    : $valorLinha;

                return [
                    'nome' => $produtoNome,
                    'quantidade' => $quantidade,
                    'valor_unitario' => $valorUnitario,
                    'valor_total' => $valorLinha,
                    'tamanho' => $tamanho,
                    'sabores' => $sabores,
                    'adicionais' => $adicionais,
                    'observacao' => trim((string) ($item->observacao ?? '')),
                ];
            })
            ->values();

        $subtotalItens = (float) $itens->sum('valor_total');
        $desconto = max(0.0, (float) ($pedido->desconto ?? 0));
        $frete = max(0.0, (float) ($pedido->valor_entrega ?? 0));

        $total = (float) ($pedido->valor_total ?? 0);
        if ($total <= 0) {
            $total = max(0.0, $subtotalItens - $desconto + $frete);
        }

        // Pedidos antigos nem sempre gravavam valor_entrega separadamente.
        if ($frete <= 0 && $pedido->endereco_id && $total > 0) {
            $freteCalculado = $total - $subtotalItens + $desconto;
            if ($freteCalculado > 0) {
                $frete = $freteCalculado;
            }
        }

        $subtotal = max(0.0, $total - $frete + $desconto);
        if ($subtotal <= 0 && $subtotalItens > 0) {
            $subtotal = $subtotalItens;
        }

        $dataRegistro = $pedido->data_registro ?: $pedido->created_at;
        $dataPedido = $dataRegistro ? Carbon::parse($dataRegistro) : null;

        $previsaoEntrega = null;
        if ($dataPedido && !empty($config?->tempo_medio_entrega)) {
            $previsaoEntrega = $dataPedido->copy()
                ->addMinutes((int) $config->tempo_medio_entrega);
        }

        $clienteNome = trim(
            (string) ($pedido->cliente?->nome ?? '') . ' ' .
            (string) ($pedido->cliente?->sobre_nome ?? '')
        );

        if ($clienteNome === '') {
            $clienteNome = 'Cliente Delivery';
        }

        return [
            'pedido' => $pedido,
            'empresaNome' => $config?->nome
                ?: $empresa?->nome_fantasia
                ?: $empresa?->nome
                ?: 'Delivery',
            'empresaDocumento' => $empresa?->cnpj,
            'empresaTelefone' => $config?->telefone ?: $empresa?->telefone,
            'empresaEndereco' => $this->formatStoreAddress($pedido),
            'logoDataUri' => $this->loadLogoDataUri($config?->logo),
            'qrCodeDataUri' => $this->loadRouteQrCodeDataUri($pedido),
            'clienteNome' => $clienteNome,
            'telefone' => $pedido->telefone ?: $pedido->cliente?->celular,
            'enderecoEntrega' => $this->formatDeliveryAddress($pedido),
            'itens' => $itens,
            'subtotal' => $subtotal,
            'desconto' => $desconto,
            'frete' => $frete,
            'total' => $total,
            'formaPagamento' => trim((string) ($pedido->forma_pagamento ?? '')),
            'trocoPara' => max(0.0, (float) ($pedido->troco_para ?? 0)),
            'observacao' => trim((string) ($pedido->observacao ?? '')),
            'dataPedido' => $dataPedido,
            'previsaoEntrega' => $previsaoEntrega,
        ];
    }

    protected function formatStoreAddress(PedidoDelivery $pedido): string
    {
        $empresa = $pedido->empresa;
        $config = $empresa?->deliveryConfig;

        $logradouro = trim(implode(', ', array_filter([
            trim((string) ($config?->rua ?? $empresa?->rua ?? '')),
            trim((string) ($config?->numero ?? $empresa?->numero ?? '')),
        ])));

        $complemento = implode(' - ', array_filter([
            trim((string) ($config?->bairro ?? $empresa?->bairro ?? '')),
            trim((string) ($config?->cidade?->nome ?? '')),
        ]));

        $cep = trim((string) ($config?->cep ?? $empresa?->cep ?? ''));

        return implode(' | ', array_filter([
            $logradouro,
            $complemento,
            $cep !== '' ? 'CEP ' . $cep : null,
        ]));
    }

    protected function formatDeliveryAddress(PedidoDelivery $pedido): ?string
    {
        $endereco = $pedido->endereco;

        if (!$endereco) {
            return null;
        }

        $logradouro = trim(implode(', ', array_filter([
            trim((string) ($endereco->rua ?? '')),
            trim((string) ($endereco->numero ?? '')),
        ])));

        $localidade = implode(' - ', array_filter([
            trim((string) ($endereco->_bairro?->nome ?? $endereco->bairro ?? '')),
            trim((string) ($endereco->cidade?->nome ?? '')),
        ]));

        return implode(' | ', array_filter([
            $logradouro,
            $localidade,
            !empty($endereco->cep) ? 'CEP ' . $endereco->cep : null,
            !empty($endereco->referencia) ? 'Ref.: ' . $endereco->referencia : null,
        ]));
    }

    protected function loadLogoDataUri(?string $logo): ?string
    {
        if (empty($logo)) {
            return null;
        }

        $path = public_path('delivery/logos/' . basename($logo));

        return $this->fileToDataUri($path);
    }

    protected function loadRouteQrCodeDataUri(PedidoDelivery $pedido): ?string
    {
        foreach ([$pedido->qr_code_base64, $pedido->qr_code] as $storedQrCode) {
            if (is_string($storedQrCode) && str_starts_with($storedQrCode, 'data:image/')) {
                return $storedQrCode;
            }
        }

        if ((int) env('QRCODE_MAPS', 0) !== 1) {
            return null;
        }

        return $this->fileToDataUri(public_path('rotas/' . $pedido->id . '.png'));
    }

    protected function fileToDataUri(string $path): ?string
    {
        if (!is_file($path) || !is_readable($path)) {
            return null;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            default => 'image/png',
        };

        return 'data:' . $mime . ';base64,' . base64_encode($content);
    }

    protected function estimateHeightMm(array $dados): float
    {
        $height = 105.0;

        foreach ($dados['itens'] as $item) {
            $height += 10.0;
            $height += count($item['sabores']) * 4.0;
            $height += count($item['adicionais']) * 4.0;

            if (!empty($item['tamanho'])) {
                $height += 4.0;
            }

            if (!empty($item['observacao'])) {
                $height += 5.0;
            }
        }

        if (!empty($dados['enderecoEntrega'])) {
            $height += 12.0;
        }

        if (!empty($dados['observacao'])) {
            $height += 8.0;
        }

        if (!empty($dados['qrCodeDataUri'])) {
            $height += 48.0;
        }

        return min(self::MAX_HEIGHT_MM, max(self::MIN_HEIGHT_MM, $height));
    }

    protected function mmToPoints(float $millimeters): float
    {
        return $millimeters * 72 / 25.4;
    }
}
