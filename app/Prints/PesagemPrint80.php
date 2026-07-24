<?php

namespace App\Prints;

use App\Models\ConfigNota;
use App\Models\Pesagem;
use App\Models\PesagemTicketImagem;
use App\Services\Pesagem\PesagemTicketImagemService;
use NFePHP\DA\Legacy\Pdf;
use NFePHP\DA\Legacy\Common;
use Com\Tecnick\Barcode\Barcode;

class PesagemPrint80 extends Common
{
    protected $pesagem;
    protected $pdf;
    protected $config;
    protected $larg = 80;
    protected $dpi = 203; // DPI padrão para impressoras térmicas
    protected $fontePadrao = 'Arial';
    protected $logomarca = '';
    protected $alturaBase = 80; // Altura mínima do PDF
    protected array $tempImageFiles = [];

    public function __construct(Pesagem $pesagem)
    {
        $this->pesagem = $pesagem;
        $this->config = ConfigNota::configStatic();

        $alturaDinamica = $this->calculaAltura(); // Calcular altura com base no conteúdo
        $this->pdf = new Pdf('P', 'mm', [$this->larg, $alturaDinamica]);
        $this->pdf->SetMargins(2, 2);
        $this->pdf->SetAutoPageBreak(false, 0);

        // Define fontes padrão
        $this->fontePadrao = 'Arial';
        $this->pdf->SetFont($this->fontePadrao, '', 9);

        // Solução temporária para o erro
        if (!property_exists($this->pdf, 'InHeader')) {
            $this->pdf->InHeader = false; // Defina o valor padrão
            $this->pdf->InFooter = false; // Defina o valor padrão
        }
    }

    /**
     * Helpers de cálculo (sem alterar a estrutura externa)
     */
    private function f($v): float
    {
        // força float não-negativo
        return max(0.0, (float)$v);
    }

    private function absDiff(float $a, float $b): float
    {
        // sempre maior - menor (diferença absoluta)
        return ($a >= $b) ? ($a - $b) : ($b - $a);
    }

    private function clamp(float $value, float $min, float $max): float
    {
        if ($value < $min) return $min;
        if ($value > $max) return $max;
        return $value;
    }

    private function valorTicket($ticket): float
    {
        $pesoLiquido = max(0, $this->f($ticket->peso) - $this->f($ticket->peso_bag));
        $valorUnitario = (float) ($ticket->valor_unitario ?? 0) > 0
            ? (float) $ticket->valor_unitario
            : (float) ($ticket->produto->valor_venda ?? $ticket->produto->valor_compra ?? 0);

        return (float) ($ticket->valor_total ?? 0) > 0
            ? (float) $ticket->valor_total
            : ($pesoLiquido * max(0, $valorUnitario));
    }

    private function valorTotalLiquidoTickets($tickets): float
    {
        $entradas = 0.0;
        $saidas = 0.0;
        $avulsas = 0.0;

        foreach ($tickets as $ticket) {
            $valor = $this->valorTicket($ticket);
            if ($ticket->tipo === 'saida') {
                $saidas += $valor;
            } elseif ($ticket->tipo === 'avulsa') {
                $avulsas += $valor;
            } else {
                $entradas += $valor;
            }
        }

        return $this->absDiff($entradas + $avulsas, $saidas);
    }



    /**
     * Indica se as imagens das câmeras devem sair nos tickets 80mm.
     */
    protected function deveImprimirImagens80mm(): bool
    {
        return (bool) ($this->config->pesagem_imprimir_imagens_80mm ?? true);
    }

    /**
     * Carrega as imagens persistidas do ticket, sem usar relacionamento para evitar SELECT *.
     */
    protected function imagensTicket80mm($ticket, int $limite = 2)
    {
        if (!$this->deveImprimirImagens80mm() || empty($ticket->id)) {
            return collect();
        }

        try {
            return PesagemTicketImagem::query()
                ->where('ticket_pesagem_id', $ticket->id)
                ->where('ativo', true)
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->limit($limite)
                ->get([
                    'id', 'empresa_id', 'pesagem_id', 'ticket_pesagem_id', 'camera_uuid',
                    'camera_descricao', 'arquivo_path', 'arquivo_url', 'mime_type',
                    'metadata_json', 'storage_disk', 'storage_base_path', 'ativo', 'deleted_at'
                ]);
        } catch (\Throwable $e) {
            \Log::warning('Falha ao carregar imagens do ticket para impressão 80mm.', [
                'ticket_id' => $ticket->id ?? null,
                'message' => $e->getMessage(),
            ]);
            return collect();
        }
    }

    protected function contarImagens80mm(int $limitePorTicket = 2): int
    {
        if (!$this->deveImprimirImagens80mm()) {
            return 0;
        }

        $total = 0;
        foreach ($this->pesagem->tickets ?? [] as $ticket) {
            $total += $this->imagensTicket80mm($ticket, $limitePorTicket)->count();
        }
        return $total;
    }

    protected function imagemTicketParaArquivoTemporario(PesagemTicketImagem $imagem): ?string
    {
        try {
            $src = app(PesagemTicketImagemService::class)->imagemSrcParaRelatorio($imagem, true);
            if (!$src) {
                return null;
            }

            if (preg_match('~^data:image/[^;]+;base64,(.+)$~', $src, $match)) {
                $binario = base64_decode($match[1], true);
                if ($binario === false || $binario === '') {
                    return null;
                }

                $ext = str_contains((string) $imagem->mime_type, 'png') ? 'png' : 'jpg';
                $tmp = sys_get_temp_dir() . '/pesagem_ticket_img_' . uniqid('', true) . '.' . $ext;
                file_put_contents($tmp, $binario);
                $this->tempImageFiles[] = $tmp;
                return $tmp;
            }

            if (is_file($src)) {
                return $src;
            }

            if (($imagem->storage_disk ?: 'public_path') === 'public_path' && $imagem->arquivo_path) {
                $local = public_path(ltrim($imagem->arquivo_path, '/'));
                return is_file($local) ? $local : null;
            }
        } catch (\Throwable $e) {
            \Log::warning('Falha ao preparar imagem do ticket para impressão 80mm.', [
                'imagem_id' => $imagem->id ?? null,
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }

    protected function imprimirImagensTicket80mm($ticket, int $limite = 2): void
    {
        $imagens = $this->imagensTicket80mm($ticket, $limite);
        if ($imagens->isEmpty()) {
            return;
        }

        $this->pdf->Ln(2);
        $this->pdf->SetFont('Arial', 'B', 7);
        $this->pdf->Cell(0, 4, mb_convert_encoding('Imagem(ns) da pesagem - Ticket #' . $ticket->id, 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');

        foreach ($imagens as $imagem) {
            $path = $this->imagemTicketParaArquivoTemporario($imagem);
            if (!$path) {
                continue;
            }

            $x = 6;
            $w = 68;
            $h = 38;
            $y = $this->pdf->GetY();
            $this->pdf->Image($path, $x, $y, $w, $h);
            $this->pdf->Ln($h + 1);

            $caption = $imagem->camera_descricao ?: $imagem->camera_uuid ?: 'Câmera';
            $this->pdf->SetFont('Arial', '', 6);
            $this->pdf->Cell(0, 3, mb_convert_encoding((string) $caption, 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
            $this->pdf->Ln(1);
        }
    }

    protected function limparImagensTemporarias(): void
    {
        foreach ($this->tempImageFiles as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        $this->tempImageFiles = [];
    }

    private function moedaBr(float $valor): string
    {
        return 'R$ ' . number_format(max(0, $valor), 2, ',', '.');
    }

    private function chavePixContraparte(): string
    {
        if (($this->pesagem->tipo ?? '') === 'compra') {
            return (string) ($this->pesagem->fornecedor->pix ?? '');
        }

        if (($this->pesagem->tipo ?? '') === 'venda') {
            return (string) ($this->pesagem->cliente->pix ?? '');
        }

        return '';
    }

    /**
     * Calcula a altura necessária para o PDF com base no conteúdo.
     *
     * @return float
     */
    protected function calculaAltura()
    {
        $tempPdf = new Pdf('P', 'mm', [$this->larg, 1000]);
        $tempPdf->SetFont($this->fontePadrao, '', 9);

        $altura = $this->alturaBase;

        $altura += 30; // Cabeçalho
        $altura += 20; // Rodapé

        $informacoes = [
            'Pesagem ID: ' . $this->pesagem->id,
            'Veículo: ' . ($this->pesagem->veiculo->modelo ?? 'N/A'),
            'Veículo: ' . ($this->pesagem->placa_veiculo ?? 'N/A'),
            'Placa Carreta: ' . ($this->pesagem->placa_carreta ?? 'N/A'),
            'Motorista: ' . ($this->pesagem->motorista_nome ?? 'N/A'),
            'Tipo: ' . ucfirst($this->pesagem->tipo ?? 'N/A'),
            'Status: ' . ucfirst($this->pesagem->status),
        ];
        if ($this->chavePixContraparte() !== '') {
            $informacoes[] = 'Chave PIX: ' . $this->chavePixContraparte();
        }

        foreach ($informacoes as $info) {
            $larguraTexto = $tempPdf->GetStringWidth($info);
            $linhas = ceil($larguraTexto / ($this->larg - 4));
            $altura += $linhas * 5;
        }

        if (!empty(trim($this->pesagem->observacoes))) {
            $textoObs = 'Observações: ' . ucfirst($this->pesagem->observacoes);
            $larguraObs = $tempPdf->GetStringWidth($textoObs);
            $linhasObs = ceil($larguraObs / ($this->larg - 4));
            $altura += ($linhasObs + 1) * 5;
        }

        $exibirValoresTicket = (bool) ($this->config->usar_valores_ticket_pesagem ?? false);

        $ticketsAgrupados = $this->pesagem->tickets->groupBy('produto_id');
        foreach ($ticketsAgrupados as $produtoId => $tickets) {
            $altura += 10;
            foreach ($tickets as $ticket) {
                $nomeProduto = $ticket->produto->nome ?? 'N/A';
                $larguraProduto = $tempPdf->GetStringWidth($nomeProduto);
                $linhasProduto = ceil($larguraProduto / ($this->larg - 4));
                $altura += $linhasProduto * 5;
            }
            $altura += count($tickets) * ($exibirValoresTicket ? 10 : 5);
            if ($exibirValoresTicket) {
                $altura += 8;
            }
        }

        $altura += $exibirValoresTicket ? 56 : 40; // cálculos gerais
        $altura += ($this->contarImagens80mm(2) * 45); // imagens das câmeras no 80mm completo
        $altura += 50; // carimbo e assinatura

        return max($altura, $this->alturaBase);
    }

    protected function quebraTexto($texto, $largura, $altura, $alinhamento)
    {
        $palavras = explode(' ', $texto);
        $linha = '';

        foreach ($palavras as $palavra) {
            if ($this->pdf->GetStringWidth($linha . ' ' . $palavra) <= $largura) {
                $linha .= ($linha === '' ? '' : ' ') . $palavra;
            } else {
                $this->pdf->Cell($largura, $altura, $linha, 0, 1, $alinhamento);
                $linha = $palavra;
            }
        }

        if ($linha !== '') {
            $this->pdf->Cell($largura, $altura, $linha, 0, 1, $alinhamento);
        }
    }

    protected function adicionaQRCode($conteudo, $x = 25, $y = null, $largura = 30, $altura = 30)
    {
        $barcode = new Barcode();
        $qrCodeObj = $barcode->getBarcodeObj(
            'QRCODE,H', $conteudo, -4, -4, 'black', [0,0,0,0]
        )->setBackgroundColor('white');
        $qrCodeImage = $qrCodeObj->getPngData();

        $tempPath = sys_get_temp_dir() . '/qrcode_' . uniqid() . '.png';
        file_put_contents($tempPath, $qrCodeImage);
        $this->pdf->Image($tempPath, $x, $y ?? $this->pdf->GetY(), $largura, $altura);
        @unlink($tempPath);
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
        $espacoEntreLogoEQRCode = 2;
        $yQRCode = 2;

        if (!empty($this->config->logo) && file_exists(public_path('logos/' . $this->config->logo))) {
            list($larguraLogo, $alturaLogo) = getimagesize(public_path('logos/' . $this->config->logo));
            $larguraLogoNoPDF = 60;
            $escalaAltura = $alturaLogo / $larguraLogo;
            $alturaLogoNoPDF = $larguraLogoNoPDF * $escalaAltura;

            $this->pdf->Image(public_path('logos/' . $this->config->logo), 10, 2, $larguraLogoNoPDF);
            $yQRCode += $alturaLogoNoPDF + $espacoEntreLogoEQRCode;
            $this->pdf->Ln($alturaLogoNoPDF);
        } else {
            $this->pdf->SetFont('Arial', 'B', 10);
            $this->pdf->Cell(0, 10, mb_convert_encoding($this->config->razao_social ?? 'Empresa Não Configurada', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
            $yQRCode += 20;
        }

        $this->adicionaQRCode(env('URL_PESAGEM_TOKEN') . '/getTicket/withToken/relPrn80mm/' . $this->pesagem->token, 25, $yQRCode);

        $this->pdf->Ln(30);
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Ln(2);
        $this->pdf->Cell(0, 5, utf8_decode('Ticket: ' . $this->pesagem->token), 0, 1, 'C');
        $this->pdf->Ln(2);
    }

    protected function montaCabecalho_()
    {
        if (!empty($this->config->logo) && file_exists(public_path('logos/' . $this->config->logo))) {
            $this->pdf->Image(public_path('logos/' . $this->config->logo), 10, 2, 60);
            $this->pdf->Ln(20);
        } else {
            $this->pdf->SetFont('Arial', 'B', 10);
            $this->pdf->Cell(0, 10, mb_convert_encoding($this->config->razao_social ?? 'Empresa Não Configurada', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        }
        $this->adicionaQRCode(env('URL_PESAGEM_TOKEN').'/getTicket/withToken/relPrn/'.$this->pesagem->token, 25, $this->pdf->GetY());
        $this->pdf->Ln(30);

        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Ln(2);
        $this->pdf->Cell(0, 5, utf8_decode('Ticket: ' .  $this->pesagem->token ), 0, 1, 'C');
        $this->pdf->Ln(2);
    }

    protected function montaConteudo()
    {
        $this->pdf->SetFont('Courier', 'B', 10);
        $this->pdf->Cell(0, 5, mb_convert_encoding('Status: ' . ucfirst($this->pesagem->status), 'ISO-8859-1', 'UTF-8'), 0, 1);
        $this->pdf->Cell(0, 5, mb_convert_encoding('Pesagem ID: ' . $this->pesagem->id, 'ISO-8859-1', 'UTF-8'), 0, 1);

        $this->pdf->SetFont('Courier', 'BI', 8);
        $this->pdf->Cell(0, 5, mb_convert_encoding('Veículo: ' . ($this->pesagem->veiculo->modelo ?? 'N/A'), 'ISO-8859-1', 'UTF-8'), 0, 1);
        $this->pdf->Cell(0, 5, mb_convert_encoding('Placa Veículo: ' . ($this->pesagem->placa_veiculo ?? 'N/A'), 'ISO-8859-1', 'UTF-8'), 0, 1);
        $this->pdf->Cell(0, 5, mb_convert_encoding('Placa Carreta: ' . ($this->pesagem->placa_carreta ?? 'N/A'), 'ISO-8859-1', 'UTF-8'), 0, 1);
        $this->pdf->Cell(0, 5, mb_convert_encoding('Motorista: ' . ($this->pesagem->motorista_nome ?? 'N/A'), 'ISO-8859-1', 'UTF-8'), 0, 1);

        $tipo = $this->pesagem->tipo ?? 'N/A';
        $this->pdf->Cell(0, 5, mb_convert_encoding('Tipo: ' . ucfirst($tipo), 'ISO-8859-1', 'UTF-8'), 0, 1);

        $this->pdf->SetFont('Arial', 'I', 7);
        $this->pdf->Ln(2);
        if ($tipo === 'compra') {
            $fornecedor = $this->pesagem->fornecedor->razao_social ?? 'Fornecedor não informado';
            $this->pdf->Cell(0, 4, mb_convert_encoding('Fornecedor: ' , 'ISO-8859-1', 'UTF-8'), 0, 1);
            $this->pdf->Ln(0);
            $this->pdf->Cell(0, 4, mb_convert_encoding($fornecedor, 'ISO-8859-1', 'UTF-8'), 0, 1);
        } elseif ($tipo === 'venda') {
            $cliente = $this->pesagem->cliente->razao_social ?? 'Cliente não informado';
            $this->pdf->Cell(0, 4, mb_convert_encoding('Cliente: ', 'ISO-8859-1', 'UTF-8'), 0, 1);
            $this->pdf->Ln(0);
            $this->pdf->Cell(0, 4, mb_convert_encoding( $cliente, 'ISO-8859-1', 'UTF-8'), 0, 1);
        }

        $chavePix = $this->chavePixContraparte();
        if ($chavePix !== '') {
            $this->pdf->Cell(0, 4, mb_convert_encoding('Chave PIX: ' . $chavePix, 'ISO-8859-1', 'UTF-8'), 0, 1);
        }

        if (!empty(trim($this->pesagem->observacoes))) {
            $this->pdf->Ln(2);
            $this->pdf->SetFont('Arial', 'B', 8);
            $this->pdf->Cell(0, 5, mb_convert_encoding('Observações:', 'ISO-8859-1', 'UTF-8'), 0, 1);
            $this->pdf->SetFont('Arial', '', 7);
            $obs = mb_convert_encoding(ucfirst($this->pesagem->observacoes), 'ISO-8859-1', 'UTF-8');
            $this->quebraTexto($obs, $this->larg - 4, 5, 'L');
        }

        // ======================
        // CÁLCULO ROBUSTO (SEM NEGATIVOS / SEM ESTOURO)
        // ======================
        $entradas = $this->pesagem->tickets->where('tipo', 'entrada')->sum(fn($t) => $this->f($t->peso));
        $saidas   = $this->pesagem->tickets->where('tipo', 'saida')  ->sum(fn($t) => $this->f($t->peso));
        $avulsas  = $this->pesagem->tickets->where('tipo', 'avulsa') ->sum(fn($t) => $this->f($t->peso));
        $bags     = $this->pesagem->tickets->sum(fn($t) => $this->f($t->peso_bag));

        $baseEntradas = $entradas + $avulsas;

        // diferença absoluta entre (entradas + avulsas) e saídas
        $pesoLiquido = $this->absDiff($baseEntradas, $saidas);

        // percentuais sempre sobre base não negativa
        $pct = 0.0;
        if (!empty($this->pesagem->danificado)) $pct += $this->f($this->pesagem->danificado_desconto);
        if (!empty($this->pesagem->quebrado))   $pct += $this->f($this->pesagem->quebrado_desconto);
        if (!empty($this->pesagem->esverdeado)) $pct += $this->f($this->pesagem->esverdeado_desconto);
        if (!empty($this->pesagem->ardido))     $pct += $this->f($this->pesagem->ardido_desconto);
        if (!empty($this->pesagem->secagem))    $pct += $this->f($this->pesagem->secagem_desconto);

        $pct += $this->f($this->pesagem->umidade_desconto);
        $pct += $this->f($this->pesagem->impureza_desconto);

        // descontos percentuais sobre pesoLiquido (não negativo)
        $descontosPercentuais = ($pesoLiquido > 0) ? ($pesoLiquido * ($pct / 100.0)) : 0.0;

        // soma de bags + percentuais, porém NUNCA acima do pesoLiquido
        $descontos = $this->clamp($bags + $descontosPercentuais, 0.0, $pesoLiquido);

        $pesoFinal = $this->clamp($pesoLiquido - $descontos, 0.0, $pesoLiquido);

        // Para exibição adicional
        $pesoBrutoPorPesagem = $this->f($baseEntradas); // mantém sua métrica original (sem saídas)

        $exibirValoresTicket = (bool) ($this->config->usar_valores_ticket_pesagem ?? false);
        $valorUnitarioResumo = 0.0;
        $valorTotalResumo = 0.0;

        if ($exibirValoresTicket) {
            $ticketValorUnitario = $this->pesagem->tickets->first(function ($ticket) {
                return (float) ($ticket->valor_unitario ?? 0) > 0;
            });

            $valorUnitarioResumo = $this->f($ticketValorUnitario->valor_unitario ?? 0);
            $valorTotalResumo = $this->valorTotalLiquidoTickets($this->pesagem->tickets);
        }

        $this->pdf->Ln(2);
        $this->pdf->SetFont('Arial', 'I', 7);
        $this->pdf->Cell(0, 7, '---------------------------------------Resumo------------------------------------------', 0, 1, 'C');

        // Box resumo
        $resumoBoxAltura = $exibirValoresTicket ? 40 : 30;
        $this->pdf->Cell(0, $resumoBoxAltura, '', 1, 1);
        $startX = $this->pdf->GetX() + 0;
        $startY = $this->pdf->GetY() - $resumoBoxAltura;
        $this->pdf->SetXY($startX, $startY + 0);

        $this->pdf->Ln(0);
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell(0, 5, mb_convert_encoding('Peso Bruto Total (Pesagem): ' . number_format($pesoBrutoPorPesagem, 2, ',', '.') . ' kg', 'ISO-8859-1', 'UTF-8'), 0, 1);
        $this->pdf->Cell(0, 5, mb_convert_encoding('Total Entrada: ' . number_format($entradas, 2, ',', '.') . ' kg', 'ISO-8859-1', 'UTF-8'), 0, 1);
        $this->pdf->Cell(0, 5, mb_convert_encoding('Total Saída: '   . number_format($saidas,   2, ',', '.') . ' kg', 'ISO-8859-1', 'UTF-8'), 0, 1);
        $this->pdf->Cell(0, 5, mb_convert_encoding('Peso Líquido: '  . number_format($pesoLiquido, 2, ',', '.') . ' kg', 'ISO-8859-1', 'UTF-8'), 0, 1);
        $this->pdf->Cell(0, 5, mb_convert_encoding('Descontos: '     . number_format($descontos,   2, ',', '.') . ' kg', 'ISO-8859-1', 'UTF-8'), 0, 1);
        $this->pdf->Cell(0, 5, mb_convert_encoding('Peso Final: '    . number_format($pesoFinal,   2, ',', '.') . ' kg', 'ISO-8859-1', 'UTF-8'), 0, 1);
        if ($exibirValoresTicket) {
            $this->pdf->Cell(0, 5, mb_convert_encoding('Valor Unitário: ' . $this->moedaBr($valorUnitarioResumo), 'ISO-8859-1', 'UTF-8'), 0, 1);
            $this->pdf->Cell(0, 5, mb_convert_encoding('Valor Total: '    . $this->moedaBr($valorTotalResumo), 'ISO-8859-1', 'UTF-8'), 0, 1);
        }

        $this->pdf->Ln(2);
        $this->pdf->SetFont('Arial', 'I', 7);
        $this->pdf->Cell(0, 5, '--------------------------------Tickets / Pesagens-----------------------------------', 0, 1, 'C');

        // Detalhes por produto (ajuste de líquido por produto também com diferença absoluta)
        $ticketsAgrupados = $this->pesagem->tickets->groupBy('produto_id');
        if ($ticketsAgrupados->isEmpty()) {
            $this->pdf->Cell(0, 5, mb_convert_encoding('Nenhum ticket encontrado!', 'ISO-8859-1', 'UTF-8'), 0, 1);
            return;
        }

        foreach ($ticketsAgrupados as $produtoId => $tickets) {
            $produto = $tickets->first()->produto ?? null;

            $entradaProduto = (float)$tickets->where('tipo', 'entrada')->sum('peso');
            $saidaProduto   = (float)$tickets->where('tipo', 'saida')->sum('peso');
            $avulsaProduto  = (float)$tickets->where('tipo', 'avulsa')->sum('peso');

            $entradaProduto = $this->f($entradaProduto);
            $saidaProduto   = $this->f($saidaProduto);
            $avulsaProduto  = $this->f($avulsaProduto);

            $brutoProduto   = $entradaProduto + $avulsaProduto;
            $liqProduto     = $this->absDiff($brutoProduto, $saidaProduto); // <- diferença absoluta por produto

            $this->pdf->Ln(0);
            $this->pdf->SetFont('Arial', 'I', 7);
            $this->pdf->Cell(0, 2, '', 0, 1, 'C');

            $produtoBoxAltura = $exibirValoresTicket ? 33 : 25;
            $this->pdf->Cell(0, $produtoBoxAltura, '', 1, 1);
            $startX = $this->pdf->GetX() + 0;
            $startY = $this->pdf->GetY() - ($produtoBoxAltura - 1);
            $this->pdf->SetXY($startX, $startY + 0);

            $this->pdf->Ln(0);
            $this->pdf->SetFont('Arial', '', 7);
            $this->pdf->Cell(0, 4, mb_convert_encoding('Produto: ' . ($produto->nome ?? 'Não informado'), 'ISO-8859-1', 'UTF-8'), 0, 1);
            $this->pdf->Cell(0, 4, mb_convert_encoding('Peso Bruto: ' . number_format($brutoProduto, 2, ',', '.') . ' kg', 'ISO-8859-1', 'UTF-8'), 0, 1);
            $this->pdf->Cell(0, 4, mb_convert_encoding('Entrada: '    . number_format($entradaProduto, 2, ',', '.') . ' kg', 'ISO-8859-1', 'UTF-8'), 0, 1);
            $this->pdf->Cell(0, 4, mb_convert_encoding('Saída: '      . number_format($saidaProduto,   2, ',', '.') . ' kg', 'ISO-8859-1', 'UTF-8'), 0, 1);
            // “Recipiente” segue o total (não por produto), preservando seu layout
            $this->pdf->Cell(0, 4, mb_convert_encoding('Recipiente de Pesagem: ' . number_format($descontos, 2, ',', '.') . ' kg', 'ISO-8859-1', 'UTF-8'), 0, 1);
            $this->pdf->Cell(0, 4, mb_convert_encoding('Peso Líquido: ' . number_format($liqProduto, 2, ',', '.') . ' kg', 'ISO-8859-1', 'UTF-8'), 0, 1);

            if ($exibirValoresTicket) {
                $valorUnitarioProduto = 0.0;
                $valorTotalProduto = $this->valorTotalLiquidoTickets($tickets);

                foreach ($tickets as $ticketProduto) {
                    $valorUnitarioResolvido = (float) ($ticketProduto->valor_unitario ?? 0) > 0
                        ? (float) $ticketProduto->valor_unitario
                        : (float) ($ticketProduto->produto->valor_venda ?? $ticketProduto->produto->valor_compra ?? 0);

                    if ($valorUnitarioProduto <= 0 && $valorUnitarioResolvido > 0) {
                        $valorUnitarioProduto = $valorUnitarioResolvido;
                    }

                }

                $this->pdf->Cell(0, 4, mb_convert_encoding('Valor Unitário: ' . $this->moedaBr($valorUnitarioProduto), 'ISO-8859-1', 'UTF-8'), 0, 1);
                $this->pdf->Cell(0, 4, mb_convert_encoding('Valor Total: '    . $this->moedaBr($valorTotalProduto), 'ISO-8859-1', 'UTF-8'), 0, 1);
            }

            // Tabela de tickets por produto
            $this->pdf->Cell(16, 4, mb_convert_encoding('ID', 'ISO-8859-1', 'UTF-8'), 1, 0);
            $this->pdf->Cell(10, 4, mb_convert_encoding('Tipo', 'ISO-8859-1', 'UTF-8'), 1, 0);
            $this->pdf->Cell(17, 4, mb_convert_encoding('Peso (kg)', 'ISO-8859-1', 'UTF-8'), 1, 0);
            $this->pdf->Cell(17, 4, mb_convert_encoding('Recip (kg)', 'ISO-8859-1', 'UTF-8'), 1, 0);
            $this->pdf->Cell(16, 4, mb_convert_encoding('Data', 'ISO-8859-1', 'UTF-8'), 1, 1);

            foreach ($tickets as $ticket) {
                $this->pdf->Cell(16, 5, $ticket->id, 1, 0);
                $this->pdf->Cell(10, 5, ucfirst($ticket->tipo), 1, 0);
                $this->pdf->Cell(17, 5, number_format($this->f($ticket->peso), 2, ',', '.'), 1, 0);
                $this->pdf->Cell(17, 5, number_format($this->f($ticket->peso_bag), 2, ',', '.'), 1, 0);
                $this->pdf->Cell(16, 5, $ticket->created_at->format('d/m/Y'), 1, 1);

                if ($exibirValoresTicket) {
                    $valorUnitarioTicket = (float) ($ticket->valor_unitario ?? 0) > 0
                        ? (float) $ticket->valor_unitario
                        : (float) ($ticket->produto->valor_venda ?? $ticket->produto->valor_compra ?? 0);
                    $valorTotalTicket = $this->valorTicket($ticket);

                    $this->pdf->Cell(76, 4, mb_convert_encoding('  Valor Unitário: ' . $this->moedaBr($valorUnitarioTicket) . '  |  Valor Total: ' . $this->moedaBr($valorTotalTicket), 'ISO-8859-1', 'UTF-8'), 1, 1);
                }

                $this->imprimirImagensTicket80mm($ticket, 2);
            }
        }

        $this->pdf->Ln(5);
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(0, 5, mb_convert_encoding('Peso Total Geral: ' . number_format($pesoFinal, 2, ',', '.') . ' kg', 'ISO-8859-1', 'UTF-8'), 0, 1);

        // Carimbo
        $this->pdf->Ln(2);
        $this->pdf->SetFont('Arial', 'I', 7);
        $this->pdf->Cell(0, 6, '---------------------------------------CARIMBO------------------------------------------', 0, 1, 'C');

        $this->pdf->Cell(0, 20, '', 1, 1);
        $startX = $this->pdf->GetX() + 0;
        $startY = $this->pdf->GetY() - 20;
        $this->pdf->SetXY($startX, $startY + 0);

        $this->pdf->Cell(0, 5, mb_convert_encoding('Razão Social: ' . ($this->config->razao_social ?? 'N/A'), 'ISO-8859-1', 'UTF-8'), 0, 1);
        $this->pdf->Cell(50, 5, mb_convert_encoding('CNPJ: ' . ($this->config->cnpj ?? 'N/A'), 'ISO-8859-1', 'UTF-8'), 0, 0);
        $this->pdf->Cell(50, 5, mb_convert_encoding('IE: '   . ($this->config->ie ?? 'N/A'), 'ISO-8859-1', 'UTF-8'), 0, 1);
        $this->pdf->Cell(50, 5, mb_convert_encoding('Município: ' . ($this->config->municipio ?? 'N/A'), 'ISO-8859-1', 'UTF-8'), 0, 0);
        $this->pdf->Cell(50, 5, mb_convert_encoding('UF: ' . ($this->config->uf ?? 'N/A'), 'ISO-8859-1', 'UTF-8'), 0, 1);
        $this->pdf->Cell(50, 5, mb_convert_encoding('E-mail: ' . ($this->config->email ?? 'N/A'), 'ISO-8859-1', 'UTF-8'), 0, 0);
        $this->pdf->Cell(50, 5, mb_convert_encoding('Fone: '   . ($this->config->fone ?? 'N/A'), 'ISO-8859-1', 'UTF-8'), 0, 1);

        $this->pdf->SetFont('Arial', '', 9);
        $this->pdf->Ln(5);
        $this->pdf->Cell(0, 5, '___________________________________________', 0, 1, 'C');
        $this->pdf->Cell(0, 5, 'Assinatura', 0, 1, 'C');
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
        if (!empty($this->config->logo) && file_exists(public_path('logos/' . $this->config->logo))) {
            $this->pdf->Image(public_path('logos/' . $this->config->logo), 25, $this->pdf->GetY(), 30);
        }
    }

    public function render_()
    {
        $this->pdf->Output('I', 'I'); // Exibe no navegador
    }

    /**
     * Dados brutos do PDF
     * @return string
     */
    public function render()
    {
        $pdf = $this->pdf->getPdf();
        $this->limparImagensTemporarias();
        return $pdf;
    }
}
