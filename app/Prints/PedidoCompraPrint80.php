<?php

namespace App\Prints;

use App\Models\ConfigNota;
use App\Models\Compra;
use NFePHP\DA\Legacy\Pdf;
use NFePHP\DA\Legacy\Common;
use Com\Tecnick\Barcode\Barcode;

class PedidoCompraPrint80 extends Common
{
    protected $compra;
    protected $pdf;
    protected $config;
    protected $larg = 80;
    protected $fontePadrao = 'Arial';
    protected $alturaBase = 100;

    public function __construct(Compra $compra)
    {
        $this->compra = $compra;
        $this->config = ConfigNota::configStatic();

        $alturaDinamica = $this->calculaAltura();
        $this->pdf = new Pdf('P', 'mm', [$this->larg, $alturaDinamica]);
        $this->pdf->SetMargins(2, 2);
        $this->pdf->SetAutoPageBreak(false, 0);

        $this->pdf->SetFont($this->fontePadrao, '', 9);
    }

    protected function calculaAltura()
    {
        $altura = $this->alturaBase;
        $altura += 2; // Cabeçalho
        $altura += 2; // Rodapé
        $altura += count($this->compra->itens) * 8; // Cada item ocupa 8mm
        $altura += 2; // Totais e informações adicionais
        return max($altura, $this->alturaBase);
    }

    public function monta()
    {
        $this->pdf->AddPage();
        $this->montaCabecalho();
        $this->montaConteudo();
        $this->montaRodape();
    }

    protected function montaCabecalho()
    {
        if (!empty($this->config->logo) && file_exists(public_path('logos/' . $this->config->logo))) {
            $this->pdf->Image(public_path('logos/' . $this->config->logo), 10, 2, 60);
            $this->pdf->Ln(20);
        } else {
            $this->pdf->SetFont('Arial', 'B', 10);
            $this->pdf->Cell(0, 10, mb_convert_encoding($this->config->razao_social ?? 'Empresa Não Configurada', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        }

        $this->pdf->Ln(2);
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(0, 5, mb_convert_encoding('PEDIDO DE COMPRA', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->pdf->Ln(2);
        $this->pdf->Cell(0, 5, mb_convert_encoding('Número do Pedido: ' . $this->compra->id, 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->pdf->Ln(2);
    }

    protected function montaConteudo()
    {
        $this->pdf->SetFont('Courier', 'B', 7);
        $this->pdf->Cell(0, 5, mb_convert_encoding('Fornecedor: ' . ($this->compra->fornecedor->razao_social ?? 'Não informado'), 'ISO-8859-1', 'UTF-8'), 0, 1);
        $this->pdf->Cell(0, 5, mb_convert_encoding('CNPJ: ' . ($this->compra->fornecedor->cpf_cnpj ?? 'Não informado'), 'ISO-8859-1', 'UTF-8'), 0, 1);
        $this->pdf->Cell(0, 5, mb_convert_encoding('Endereço: ' . ($this->compra->fornecedor->rua ?? 'Não informado') . ', ' . ($this->compra->fornecedor->numero ?? ''), 'ISO-8859-1', 'UTF-8'), 0, 1);
        $this->pdf->Ln(2);

        $this->pdf->SetFont('Arial', 'B', 7);
        $this->pdf->Cell(0, 5, mb_convert_encoding('Itens do Pedido:', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->pdf->Ln(2);

        foreach ($this->compra->itens as $item) {
            $this->pdf->SetFont('Arial', '', 5);
            $this->pdf->Cell(5, 5, $item->produto->NCMreferencia, 0, 0);
            $this->pdf->Cell(30, 5, mb_convert_encoding($item->produto->nome, 'ISO-8859-1', 'UTF-8'), 0, 0);
            $this->pdf->Cell(15, 5, number_format($item->quantidade, 2, ',', '.'), 0, 0, 'R');
            $this->pdf->Cell(15, 5, number_format($item->valor_unitario, 2, ',', '.'), 0, 1, 'R');
        }

        $this->pdf->Ln(2);
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(0, 5, mb_convert_encoding('Total: R$ ' . number_format($this->compra->valor, 2, ',', '.'), 'ISO-8859-1', 'UTF-8'), 0, 1, 'R');
        $this->pdf->Ln(2);
    }

    protected function montaRodape()
    {
        $this->pdf->SetFont('Arial', 'i', 8);
        $this->pdf->Ln(5);
        $this->pdf->Cell(0, 4, mb_convert_encoding(env('SITE_SUPORTE', 'Suporte Técnico: contato@empresa.com') . ' | ' . env('EMAIL_SUPORTE', 'Suporte Técnico'), 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->pdf->Ln(3);
        $this->pdf->SetFont('Arial', 'I', 7);
        $this->pdf->Cell(0, 5, utf8_decode('Emitido em: ') . utf8_decode(now()->format('d/m/Y H:i')), 0, 1, 'R');
        $this->pdf->Ln(4);
        //$this->pdf->Cell(0, 4, env('EMAIL_SUPORTE', 'Suporte Técnico'), 0, 1, 'C');
        if (!empty($this->config->logo) && file_exists(public_path('logos/' . $this->config->logo))) {
            $this->pdf->Image(public_path('logos/' . $this->config->logo), 25, $this->pdf->GetY(), 30);
        }
    }

    public function render()
    {
        return $this->pdf->getPdf();
    }
}
