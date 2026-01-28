<?php

namespace App\Prints;

use NFePHP\DA\NFe\ComprovanteFechamentoCaixa as BaseComprovante;

class ComprovanteFechamentoCaixa extends BaseComprovante
{
    /**
     * Cria uma caixa de texto com ou sem bordas.
     * Este método foi extraído de CommonNFePHP para suprir a chamada ausente
     */
    protected function pTextBox(
        $x,
        $y,
        $w,
        $h,
        $text = '',
        $aFont = ['font'=>'Times','size'=>8,'style'=>''],
        $vAlign = 'T',
        $hAlign = 'L',
        $border = 1,
        $link = '',
        $force = true,
        $hmax = 0,
        $vOffSet = 0
    ) {
        // Garante que exista índice 'style'
        $aFont = array_merge(['font'=>'Times','size'=>8,'style'=>''],$aFont);

        $oldY    = $y;
        $resetou = false;

        if ($w < 0) {
            return $y;
        }
        if (is_object($text)) {
            $text = '';
        }
        if (is_string($text)) {
            $text = utf8_decode(trim($text));
        } else {
            $text = (string)$text;
        }

        // Borda opcional
        if ($border) {
            $this->pdf->RoundedRect($x, $y, $w, $h, 0.8, '1234', 'D');
        }

        // Configura fonte e calcula altura de linha
        $this->pdf->SetFont($aFont['font'], $aFont['style'], $aFont['size']);
        $incY = method_exists($this->pdf, 'getFontSize')
            ? $this->pdf->getFontSize()
            : $aFont['size'];

        // Quantidade de linhas
        $n = !$force
            ? $this->pdf->WordWrap($text, $w)
            : 1;
        $altText = $incY * $n;

        // Determina y inicial conforme alinhamento vertical
        $lines = explode("\n", $text);
        if ($vAlign === 'C') {
            $y1 = $y + $incY + (($h - $altText) / 2);
        } elseif ($vAlign === 'B') {
            $y1 = ($y + $h) - 0.5;
        } else { // Top
            $y1 = $y + $incY;
        }

        // Desenha cada linha
        foreach ($lines as $line) {
            $texto = trim($line);
            $comp  = $this->pdf->GetStringWidth($texto);

            // Se força ajuste de tamanho
            if ($force) {
                $newSize = $aFont['size'];
                while ($comp > $w && $newSize > 1) {
                    $this->pdf->SetFont($aFont['font'], $aFont['style'], --$newSize);
                    $comp = $this->pdf->GetStringWidth($texto);
                }
            }

            // Alinhamento horizontal
            if ($hAlign === 'C') {
                $x1 = $x + (($w - $comp) / 2);
            } elseif ($hAlign === 'R') {
                $x1 = $x + $w - ($comp + 0.5);
            } else {
                $x1 = $x + 0.5;
            }

            // Desenha o texto (respeita offset vertical, se houver)
            if ($vOffSet > 0 && $y1 > ($oldY + $vOffSet)) {
                if (!$resetou) {
                    $y1 = $oldY;
                    $resetou = true;
                }
                $this->pdf->Text($x1, $y1, $texto);
            } else {
                $this->pdf->Text($x1, $y1, $texto);
            }

            $y1 += $incY;
            if ($hmax > 0 && $y1 > ($y + ($hmax - 1))) {
                break;
            }
        }

        return ($y1 - $y) - $incY;
    }

    /**
     * Layout personalizado para cupom 80 mm, com colunas alinhadas e cabeçalho de empresa.
     * Assinatura compatível com o parent.
     */
    public function monta(
        $orientacao  = 'P',
        $papel       = '',
        $logoAlign   = 'C',
        $classPdf    = false,
        $depecNumReg = ''
    ) {
        // 1) Inicializa o PDF (mantém $this->pdf válido)
        parent::monta($orientacao, $papel, $logoAlign, false, $depecNumReg);

        // 2) Cabeçalho da empresa
        $this->pdf->SetFont('Courier','B',10);
        $y = 5;
        $razao = $this->config->razao_social ?? 'EMPRESA';
        $this->pTextBox(0, $y, 80, 5, $razao, ['font'=>'Courier','size'=>10,'style'=>'B'], 'T','C', 0);
        $y += 5;
        $this->pdf->SetFont('Courier','',8);
        $this->pTextBox(0, $y, 80, 4, 'CNPJ: '.$this->config->cnpj, [], 'T','C', 0);
        $y += 4;
        $this->pTextBox(0, $y, 80, 4, 'IE: '.$this->config->ie, [], 'T','C', 0);
        $y += 4;
        $end = trim("{$this->config->logradouro}, {$this->config->numero} - {$this->config->bairro}");
        $this->pTextBox(0, $y, 80, 4, $end, [], 'T','C', 0);
        $y += 4;
        $city = trim("{$this->config->cidade}-{$this->config->uf} CEP: {$this->config->cep}");
        $this->pTextBox(0, $y, 80, 4, $city, [], 'T','C', 0);
        $y += 6;

        // 3) Título do cupom
        $this->pdf->SetFont('Courier','B',9);
        $this->pTextBox(0, $y, 80, 5, 'FECHAMENTO DE CAIXA', ['font'=>'Courier','size'=>9,'style'=>'B'], 'T','C',0);
        $y += 6;

        // 4) Cabeçalho da tabela
        $this->pdf->SetFont('Courier','B',8);
        $x = 2;
        $cols = [
            ['w'=>30,'title'=>'CLIENTE'],
            ['w'=>6,'title'=>'ID'],
            ['w'=>12,'title'=>'DATA'],
            ['w'=>8,'title'=>'HORA'],
            ['w'=>12,'title'=>'VALOR'],
        ];
        foreach ($cols as $c) {
            $this->pTextBox($x, $y, $c['w'], 5, $c['title'], ['font'=>'Courier','size'=>8,'style'=>'B'], 'T','C', 0);
            $x += $c['w'];
        }
        $y += 6;
        $this->pdf->SetDrawColor(0,0,0);
        $this->pdf->Line(2, $y-1, 78, $y-1);

        // 5) Linhas de dados
        $this->pdf->SetFont('Courier','',8);
        foreach ($this->vendas as $v) {
            $x = 2;
            // Cliente
            $nome = mb_strimwidth($v->cliente, 0, 15, '…');
            $this->pTextBox($x, $y, 30, 5, $nome);
            $x += 30;
            // ID
            $this->pTextBox($x, $y, 6, 5, (string)$v->id, [], 'T','C');
            $x += 6;
            // Data
            $dt = \Carbon\Carbon::parse($v->created_at);
            $this->pTextBox($x, $y, 12, 5, $dt->format('d/m/y'), [], 'T','C');
            $x += 12;
            // Hora
            $this->pTextBox($x, $y, 8, 5, $dt->format('H:i'), [], 'T','C');
            $x += 8;
            // Valor
            $val = number_format($v->valor, 2, ',', '.');
            $this->pTextBox($x, $y, 12, 5, $val, [], 'T','R');
            $y += 5;
            if ($y > 260) {
                $this->pdf->AddPage();
                $y = 10;
            }
        }

        // 6) Totais e resumen
        $y += 4;
        $this->pdf->SetFont('Courier','B',8);
        $this->pTextBox(2, $y, 78, 5, 'TOTAL VENDAS: '.number_format($this->somaVendas,2,',','.'));
        $y += 5;
        foreach ($this->somaTiposPagamento as $label => $valor) {
            $txt = "{$label}: ".number_format($valor,2,',','.');
            $this->pTextBox(2, $y, 78, 5, $txt);
            $y += 5;
        }

        // 7) Assinatura
        $y += 8;
        $this->pTextBox(0, $y, 80, 5, '___________________________________________', [], 'T','C',0);
        $y += 5;
        $this->pTextBox(0, $y, 80, 5, 'Assinatura', [], 'T','C',0);

        // 8) Renderiza e retorna
        return $this->render();
    }
}
