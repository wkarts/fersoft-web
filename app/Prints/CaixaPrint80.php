<?php
// app/Prints/CaixaPrint80.php

namespace App\Prints;

use App\Models\ConfigNota;
use App\Models\VendaCaixa;
use NFePHP\DA\Legacy\Pdf;
use NFePHP\DA\Legacy\Common;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CaixaPrint80 extends Common
{
    protected Pdf   $pdf;
    protected object $config;
    protected array  $vendas;
    protected float  $somaVendas;
    protected array  $somaTiposPagamento;
    protected int    $larg       = 80;
    // removido o base fixo; agora é puramente calculado em calculaAltura()
    protected int    $alturaBase = 0;

    public function __construct(array $vendas, float $somaVendas, array $somaTiposPagamento)
    {
        $this->vendas             = $vendas;
        $this->somaVendas         = $somaVendas;
        $this->somaTiposPagamento = $somaTiposPagamento;
        $this->config             = ConfigNota::configStatic();

        // altura sempre exata, sem mínimo artificial
        $altura = $this->calculaAltura();
        $this->pdf = new Pdf('P', 'mm', [$this->larg, $altura]);
        // afasta 1mm das bordas para evitar corte
        $this->pdf->SetMargins(3, 3);
        $this->pdf->SetAutoPageBreak(false, 0);
        $this->pdf->SetFont('Courier', '', 9);

        if (!property_exists($this->pdf, 'InHeader')) {
            $this->pdf->InHeader = false;
            $this->pdf->InFooter = false;
        }
    }

    protected function calculaAltura(): float
    {
        $altura = 0;
        // cabeçalho (logo + empresa + CNPJ/IE + endereço + CEP)
        $altura += 5   // logo linha
            + 5   // razão social
            + 4   // CNPJ|IE
            + 4   // endereço
            + 4   // CEP
            + 2;  // espaçamento extra

        // título + linha
        $altura += 6   // FECHAMENTO DE CAIXA
            + 1   // espaçamento
            + 1;  // linha T

        // gap antes das colunas
        $altura += 2;

        // cabeçalho de colunas
        $altura += 5   // ID/Data/Hora/Valor
            + 1;  // espaçamento

        // corpo: para cada grupo de cliente
        $grupos = collect($this->vendas)
            ->groupBy(fn($v) => $v->cliente->id ?? 0);
        foreach ($grupos as $itens) {
            // CPF/CNPJ + razão social (2 linhas)
            $altura += 5 + 5;
            // cada venda
            $altura += count($itens) * 4;
            // total por cliente
            $altura += 4;
            // pequeno espaçamento
            $altura += 1;
        }

        // totais finais: cada label+valor ocupa até 5mm (quebrando linhas se necessário)
        $nTot = collect($this->somaTiposPagamento)
                ->filter(fn($v) => $v > 0)
                ->count() + 1; // +1 para Total Geral
        $altura += $nTot * 5;

        // rodapé: separador + assinatura + data
        $altura += 1   // separador T
            + 6   // espaço antes da assinatura
            + 45  // linha de assinatura
            + 4   // "Assinatura"
            + 4   // espaço
            + 4;  // data

        return $altura;
    }

    public function monta(): void
    {
        $this->pdf->AddPage();
        $this->montaCabecalho();
        $this->montaConteudo();
        $this->montaRodape();
    }

    protected function montaCabecalho(): void
    {
        // logo centralizado
        if (!empty($this->config->logo)
            && file_exists($path = public_path("logos/{$this->config->logo}"))
        ) {
            $imgW = 50;
            $x    = ($this->larg - $imgW) / 2;
            $this->pdf->Image($path, $x, 3, $imgW);
        }
        $this->pdf->Ln(25);

        // razão social da empresa
        $this->pdf->SetFont('Courier','B',10);
        $this->pdf->Cell(0,5,
            mb_convert_encoding($this->config->razao_social,'ISO-8859-1','UTF-8'),
            0,1,'C'
        );

        // formata CNPJ
        $rawCnpj = preg_replace('/\D/', '', $this->config->cnpj ?? '');
        $fmtCnpj = strlen($rawCnpj)===14
            ? preg_replace(
                '/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/',
                '$1.$2.$3/$4-$5',
                $rawCnpj
            )
            : $this->config->cnpj;

        // CNPJ | IE
        $this->pdf->SetFont('Courier','B',8);
        $this->pdf->Cell(0,4,
            'CNPJ: '.$fmtCnpj.' | IE: '.$this->config->ie,
            0,1,'C'
        );

        // endereço
        $this->pdf->Cell(0,4,
            mb_convert_encoding(
                ($this->config->logradouro ?? '').', '.
                ($this->config->numero   ?? '').' – '.
                ($this->config->bairro   ?? ''),
                'ISO-8859-1','UTF-8'
            ),
            0,1,'C'
        );
        // CEP
        $this->pdf->Cell(0,4,
            '- CEP '.($this->config->cep ?? ''),
            0,1,'C'
        );

        $this->pdf->Ln(2);

        $this->pdf->SetFont('Courier','B',11);
        $this->pdf->Cell(0,4,'___________________________________________',0,1,'C');
        $this->pdf->Ln(3);
        // título
        $this->pdf->SetFont('Courier','BI',13);
        $this->pdf->Cell(0,6,'FECHAMENTO DE CAIXA',0,1,'C');
        //$this->pdf->Ln(1);

        // **linha idêntica à de assinatura**
        $this->pdf->Cell(0,4,'___________________________________________',0,1,'C');
        $this->pdf->Ln(1);

        // cabeçalho de colunas
        $this->pdf->SetFont('Courier','B',9);
        $this->pdf->Cell(8,5,'ID','',0,'L');
        $this->pdf->Cell(22,5,'Data','',0,'C');
        $this->pdf->Cell(14,5,'Hora','',0,'C');
        $this->pdf->Cell(32,5,'Valor','', 0,'R');
        $this->pdf->Ln(2);

        // **outra linha idêntica à de assinatura**
        $this->pdf->Cell(0,4,'___________________________________________',0,1,'C');
        $this->pdf->Ln(1);
    }

    protected function montaConteudo(): void
    {
        $this->pdf->SetFont('Courier','',9);

        $grupos = collect($this->vendas)
            ->groupBy(fn($v) => $v->cliente->id ?? 0);

        foreach ($grupos as $itens) {
            // cliente
            $cliente = $itens->first()->cliente ?? (object)[
                'cpf_cnpj'     => '',
                'razao_social' => '-'
            ];

            // formata CPF/CNPJ
            $raw = preg_replace('/\D/','',$cliente->cpf_cnpj ?? '');
            if (strlen($raw) === 11) {
                $doc = 'CPF: '.preg_replace(
                        '/(\d{3})(\d{3})(\d{3})(\d{2})/',
                        '$1.$2.$3-$4',
                        $raw
                    );
            } elseif (strlen($raw) === 14) {
                $doc = 'CNPJ: '.preg_replace(
                        '/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/',
                        '$1.$2.$3/$4-$5',
                        $raw
                    );
            } else {
                $doc = '';
            }

            // imprime CPF/CNPJ
            $this->pdf->SetFont('Courier','B',9);
            $this->pdf->Cell(0,5,
                mb_convert_encoding($doc,'ISO-8859-1','UTF-8'),
                0,1,'L'
            );

            // auto-dimensiona razão social
            $nome = $cliente->razao_social;
            $maxW  = 74 * (72/25.4);
            $font  = 9;
            $this->pdf->SetFont('Courier','B',$font);
            while ($this->pdf->GetStringWidth($nome) > $maxW && $font > 6) {
                $font--;
                $this->pdf->SetFont('Courier','B',$font);
            }
            $this->pdf->Cell(0,5,
                mb_convert_encoding($nome,'ISO-8859-1','UTF-8'),
                0,1,'L'
            );

            // vendas
            $this->pdf->SetFont('Courier','B',7);
            foreach ($itens as $v) {
                $dt     = Carbon::parse($v->created_at);
                $rawVal = (!$v->consignado && !$v->rascunho && $v->estado!='CANCELADO')
                    ? (isset($v->cpf)
                        ? $v->valor_total
                        : ($v->valor_total - $v->desconto + $v->acrescimo)
                    )
                    : 0;
                $valor  = number_format($rawVal,2,',','.');

                $this->pdf->Cell(8,4,$v->id,'',0,'L');
                $this->pdf->Cell(22,4,$dt->format('d/m/Y'),'',0,'C');
                $this->pdf->Cell(14,4,$dt->format('H:i:s'),'',0,'C');
                $this->pdf->Cell(32,4,$valor,'',1,'R');
            }

            // total por cliente
            $totalCliente = collect($itens)->sum(fn($v)=>
            (!$v->consignado && !$v->rascunho && $v->estado!='CANCELADO')
                ? (isset($v->cpf)
                ? $v->valor_total
                : ($v->valor_total - $v->desconto + $v->acrescimo)
            )
                : 0
            );
            $this->pdf->SetFont('Courier','B',9);
            $this->pdf->Cell(8,4,'','',0,'L');
            $this->pdf->Cell(22,4,'','',0,'C');
            $this->pdf->Cell(14,4,'Total:','',0,'L');
            $this->pdf->Cell(32,4,number_format($totalCliente,2,',','.'),'',1,'R');
            $this->pdf->Ln(1);
        }
    }

    protected function montaRodape(): void
    {
        // separador
        $this->pdf->SetFont('Courier','B',9);
        //$this->pdf->Cell(0,0,'','T',1,'C');
        $this->pdf->Cell(0,4,'___________________________________________',0,1,'C');
        $this->pdf->Ln(2);
        // totais finais
        foreach (['Total Geral' => $this->somaVendas] + $this->somaTiposPagamento as $key => $val) {
            if ($val <= 0 && $key !== 'Total Geral') continue;
            $label = $key === 'Total Geral'
                ? 'Total Geral'
                : VendaCaixa::getTipoPagamento($key);

            // quebra se necessário
            $this->pdf->SetFont('Courier','B',9);
            if ($this->pdf->GetStringWidth($label) > 60) {
                $this->pdf->Cell(0,4,
                    mb_convert_encoding($label.':','ISO-8859-1','UTF-8'),
                    0,1,'L'
                );
                $this->pdf->Cell(0,4,number_format($val,2,',','.'),
                    0,1,'R'
                );
            } else {
                $this->pdf->Cell(0,4,
                    mb_convert_encoding($label.':','ISO-8859-1','UTF-8'),
                    0,0,'L'
                );
                $this->pdf->Cell(0,4,number_format($val,2,',','.'),0,1,'R');
            }
            $this->pdf->Ln(0);
        }

        // assinatura e data
        $this->pdf->Ln(6);
        $this->pdf->Cell(0,4,'___________________________________________',0,1,'C');
        $this->pdf->Cell(0,4,'Assinatura',0,1,'C');
        $this->pdf->Ln(4);
        $this->pdf->SetFont('Courier','BI',7);
        $this->pdf->Cell(0,4,
            'Impresso em '.now()->format('d/m/Y H:i'),
            0,1,'C'
        );
    }

    public function render(): string
    {
        $this->monta();
        return $this->pdf->getPdf();
    }
}
