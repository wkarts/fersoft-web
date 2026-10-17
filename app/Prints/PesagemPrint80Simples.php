<?php

namespace App\Prints;

use App\Models\ConfigNota;
use App\Models\Pesagem;
use App\Models\PesagemTicketImagem;
use App\Services\Pesagem\PesagemTicketImagemService;
use NFePHP\DA\Legacy\Pdf;
use NFePHP\DA\Legacy\Common;
use Com\Tecnick\Barcode\Barcode;
use Carbon\Carbon;

class PesagemPrint80Simples extends Common
{
    protected $pesagem;
    protected $pdf;
    protected $config;
    protected $larg = 80;
    protected $dpi = 203;
    protected $fontePadrao = 'Arial';
    protected $logomarca = '';
    protected $alturaBase = 50;
    protected array $tempImageFiles = [];

    public function __construct(Pesagem $pesagem)
    {
        $this->pesagem = $pesagem;
        $this->config = ConfigNota::configStatic();

        $alturaDinamica = $this->calculaAltura();
        $this->pdf = new Pdf('P', 'mm', [$this->larg, $alturaDinamica]);
        $this->pdf->SetMargins(2, 2);
        $this->pdf->SetAutoPageBreak(false, 0);

        $this->fontePadrao = 'Arial';
        $this->pdf->SetFont($this->fontePadrao, '', 9);

        if (!property_exists($this->pdf, 'InHeader')) {
            $this->pdf->InHeader = false;
            $this->pdf->InFooter = false;
        }
    }

    /**
     * Helpers de cálculo
     */
    private function f($v): float
    {
        return max(0.0, (float)$v);
    }

    private function absDiff(float $a, float $b): float
    {
        return ($a >= $b) ? ($a - $b) : ($b - $a);
    }

    private function clamp(float $value, float $min, float $max): float
    {
        if ($value < $min) return $min;
        if ($value > $max) return $max;
        return $value;
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

    private function deveExibirChavePix(): bool
    {
        return (bool) ($this->config->pesagem_exibir_chave_pix_relatorio ?? false);
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

    protected function calculaAltura()
    {
        $tempPdf = new Pdf('P', 'mm', [$this->larg, 1000]);
        $tempPdf->SetFont($this->fontePadrao, '', 9);

        $altura = $this->alturaBase;
        $altura += 30; // cabeçalho
        $altura += 20; // rodapé

        $informacoes = [
            'Pesagem ID: ' . $this->pesagem->id,
            'Veículo: ' . ($this->pesagem->veiculo->modelo ?? 'N/A'),
            'Veículo: ' . ($this->pesagem->placa_veiculo ?? 'N/A'),
            'Placa Carreta: ' . ($this->pesagem->placa_carreta ?? 'N/A'),
            'Motorista: ' . ($this->pesagem->motorista_nome ?? 'N/A'),
            'Tipo: ' . ucfirst($this->pesagem->tipo ?? 'N/A'),
            'Status: ' . ucfirst($this->pesagem->status),
        ];
        if ($this->deveExibirChavePix()) {
            $informacoes[] = 'Chave PIX: ' . ($this->chavePixContraparte() ?: 'Não informada');
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

        $ticketsAgrupados = $this->pesagem->tickets->groupBy('produto_id');
        foreach ($ticketsAgrupados as $produtoId => $tickets) {
            $altura += 10;
            foreach ($tickets as $ticket) {
                $nomeProduto = $ticket->produto->nome ?? 'N/A';
                $larguraProduto = $tempPdf->GetStringWidth($nomeProduto);
                $linhasProduto = ceil($larguraProduto / ($this->larg - 4));
                $altura += $linhasProduto * 5;
            }
            $altura += count($tickets) * 5;
        }

        $altura += 40; // cálculos gerais
        $altura += ($this->contarImagens80mm(1) * 45); // imagens das câmeras no 80mm simples
        $altura += 50; // carimbo/assinatura

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
            'QRCODE,H', $conteudo, -4, -4, 'black', [0, 0, 0, 0]
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
        // $this->montaRodape();
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
        // Cabeçalho principal
        $this->pdf->SetFont('Courier','B',10);
        $this->pdf->Cell(0, 5, mb_convert_encoding('Status: ' . ucfirst($this->pesagem->status), 'ISO-8859-1', 'UTF-8'), 0, 1);
        $this->pdf->Cell(0, 5, mb_convert_encoding('Pesagem ID: ' . $this->pesagem->id, 'ISO-8859-1', 'UTF-8'), 0, 1);

        $this->pdf->SetFont('Courier','BI',8);
        $this->pdf->Cell(0, 5, mb_convert_encoding('Veículo: ' . ($this->pesagem->veiculo->modelo ?? 'N/A'), 'ISO-8859-1', 'UTF-8'), 0, 1);
        $this->pdf->Cell(0, 5, mb_convert_encoding('Placa ¹: ' . ($this->pesagem->placa_veiculo ?? 'N/A'), 'ISO-8859-1', 'UTF-8'), 0, 1);
        $this->pdf->Cell(0, 5, mb_convert_encoding('Placa ²: ' . ($this->pesagem->placa_carreta ?? 'N/A'), 'ISO-8859-1', 'UTF-8'), 0, 1);
        $this->pdf->Cell(0, 5, mb_convert_encoding('Motorista: ' . ($this->pesagem->motorista_nome ?? 'N/A'), 'ISO-8859-1', 'UTF-8'), 0, 1);

        $tipo = $this->pesagem->tipo ?? 'N/A';
        $this->pdf->Cell(0, 5, mb_convert_encoding('Tipo: ' . ucfirst($tipo), 'ISO-8859-1', 'UTF-8'), 0, 1);

        // Fornecedor / Cliente
        $this->pdf->SetFont('Arial','I',7);
        $this->pdf->Ln(2);
        if ($tipo === 'compra') {
            $forn = $this->pesagem->fornecedor->razao_social ?? 'Fornecedor não informado';
            $this->pdf->SetFont('Arial','',7);
            $this->pdf->Cell(0,4, mb_convert_encoding('Fornecedor:', 'ISO-8859-1','UTF-8'), 0,1,'L');
            $this->pdf->SetFont('Arial','B',7);
            $this->pdf->Cell(0,4, mb_convert_encoding($forn, 'ISO-8859-1','UTF-8'), 0,1,'L');
        } elseif ($tipo === 'venda') {
            $cli = $this->pesagem->cliente->razao_social ?? 'Cliente não informado';
            $this->pdf->SetFont('Arial','',7);
            $this->pdf->Cell(0,4, mb_convert_encoding('Cliente:', 'ISO-8859-1','UTF-8'), 0,1,'L');
            $this->pdf->SetFont('Arial','B',7);
            $this->pdf->Cell(0,4, mb_convert_encoding($cli, 'ISO-8859-1','UTF-8'), 0,1,'L');
        }
        if ($this->deveExibirChavePix()) {
            $chavePix = $this->chavePixContraparte();
            $this->pdf->Cell(0,4, mb_convert_encoding('Chave PIX: ' . ($chavePix ?: 'Não informada'), 'ISO-8859-1','UTF-8'), 0,1,'L');
        }
        $this->pdf->SetFont('Arial','I',7);

        // Observações
        if (!empty(trim($this->pesagem->observacoes))) {
            $this->pdf->Ln(2);
            $this->pdf->SetFont('Arial','B',8);
            $this->pdf->Cell(0,5, mb_convert_encoding('Observações:', 'ISO-8859-1','UTF-8'), 0,1,'L');
            $this->pdf->SetFont('Arial','',7);
            $obs = mb_convert_encoding(ucfirst($this->pesagem->observacoes),'ISO-8859-1','UTF-8');
            $this->quebraTexto($obs, $this->larg - 4, 5, 'L');
        }

        // Consolidado (caso queira exibir datas globais, já está calculado)
        $all = $this->pesagem->tickets;
        $rawIni = $all->min('inicio');
        $rawFim = $all->max('fim');
        $iniGlob = $rawIni ? Carbon::parse($rawIni) : null;
        $fimGlob = $rawFim ? Carbon::parse($rawFim) : null;

        // ======================
        // CÁLCULO ROBUSTO (SEM NEGATIVOS / SEM ESTOURO)
        // ======================
        $entradas = $all->where('tipo','entrada')->sum(fn($t)=>$this->f($t->peso));
        $saidas   = $all->where('tipo','saida')  ->sum(fn($t)=>$this->f($t->peso));
        $avulsas  = $all->where('tipo','avulsa') ->sum(fn($t)=>$this->f($t->peso));
        $bags     = $all->sum(fn($t)=>$this->f($t->peso_bag));

        $baseEntradas = $entradas + $avulsas;

        // diferença absoluta entre (entradas + avulsas) e saídas
        $pesoLiquido = $this->absDiff($baseEntradas, $saidas);

        // somatório de percentuais (sempre base não negativa)
        $pct = 0.0;
        if (!empty($this->pesagem->danificado)) $pct += $this->f($this->pesagem->danificado_desconto);
        if (!empty($this->pesagem->quebrado))   $pct += $this->f($this->pesagem->quebrado_desconto);
        if (!empty($this->pesagem->esverdeado)) $pct += $this->f($this->pesagem->esverdeado_desconto);
        if (!empty($this->pesagem->ardido))     $pct += $this->f($this->pesagem->ardido_desconto);
        if (!empty($this->pesagem->secagem))    $pct += $this->f($this->pesagem->secagem_desconto);

        $pct += $this->f($this->pesagem->umidade_desconto);
        $pct += $this->f($this->pesagem->impureza_desconto);

        $descontosPercentuais = ($pesoLiquido > 0) ? ($pesoLiquido * ($pct / 100.0)) : 0.0;

        // soma de bags + percentuais, com clamp para não exceder o líquido
        $descontos = $this->clamp($bags + $descontosPercentuais, 0.0, $pesoLiquido);

        $pesoFinal = $this->clamp($pesoLiquido - $descontos, 0.0, $pesoLiquido);

        // 6) Detalhes por produto com datas por tipo
        $this->pdf->Ln(2);
        $ticketsAgrupados = $all->groupBy('produto_id');
        if ($ticketsAgrupados->isEmpty()) {
            $this->pdf->Cell(0,5, mb_convert_encoding('Nenhum ticket encontrado!','ISO-8859-1','UTF-8'), 0,1,'L');
        } else {
            foreach ($ticketsAgrupados as $prodId => $tks) {
                $nomeProd = optional($tks->first()->produto)->nome ?? 'Não informado';
                $this->pdf->Ln(2);
                $this->pdf->SetFont('Arial','B',7);
                $this->pdf->Cell(0,5, mb_convert_encoding("Produto: {$nomeProd}",'ISO-8859-1','UTF-8'), 0,1,'L');

                foreach (['entrada','saida','avulsa'] as $tipoT) {
                    $peso = (float)$tks->where('tipo',$tipoT)->sum('peso');
                    $peso = $this->f($peso);
                    if ($peso > 0) {
                        $rIni = $tks->where('tipo',$tipoT)->min('inicio');
                        $rFim = $tks->where('tipo',$tipoT)->max('fim');
                        $dIni = $rIni ? Carbon::parse($rIni) : null;
                        $dFim = $rFim ? Carbon::parse($rFim) : null;

                        $label = ucfirst($tipoT).': '.number_format($peso,2,',','.').' kg';
                        $dates = $dIni ? $dIni->format('d/m/Y H:i:s') : '';
                        $dates.= $dFim ? ' / '.$dFim->format('d/m/Y H:i:s') : '';

                        $this->pdf->SetFont('Arial','',7);
                        $this->pdf->Cell(50,4, mb_convert_encoding($label,'ISO-8859-1','UTF-8'), 0,0,'L');
                        $this->pdf->SetFont('Arial','I',6);
                        $this->pdf->Cell(0,4, mb_convert_encoding($dates,'ISO-8859-1','UTF-8'), 0,1,'R');
                    }
                }

                foreach ($tks as $ticketImagem) {
                    $this->imprimirImagensTicket80mm($ticketImagem, 1);
                }
            }
        }

        // 7) Resumo final
        $this->pdf->Ln(2);
        $this->pdf->SetFont('Arial','B',8);
        $this->pdf->Cell(0,5, mb_convert_encoding('Peso Líquido: '.number_format($pesoLiquido,2,',','.').' kg','ISO-8859-1','UTF-8'), 0,1,'L');
        $this->pdf->Cell(0,5, mb_convert_encoding('Impurezas:   '.number_format($descontos,   2,',','.').' kg','ISO-8859-1','UTF-8'), 0,1,'L');
        $this->pdf->Cell(0,5, mb_convert_encoding('Peso Final:  '.number_format($pesoFinal,    2,',','.').' kg','ISO-8859-1','UTF-8'), 0,1,'L');

        // 8) Carimbo / Assinatura
        $this->pdf->Ln(5);
        $this->pdf->Cell(0,20,'',1,1);
        $startX = $this->pdf->GetX();
        $startY = $this->pdf->GetY() - 20;
        $this->pdf->SetXY($startX, $startY);

        $this->pdf->SetFont('Arial','',7);
        $this->pdf->Cell(0,5, mb_convert_encoding('Razão Social: '.($this->config->razao_social??'N/A'),'ISO-8859-1','UTF-8'),0,1,'L');
        $this->pdf->Cell(50,5,mb_convert_encoding('CNPJ: '.($this->config->cnpj     ??'N/A'),'ISO-8859-1','UTF-8'),0,0,'L');
        $this->pdf->Cell(50,5,mb_convert_encoding('IE: '.  ($this->config->ie       ??'N/A'),'ISO-8859-1','UTF-8'),0,1,'L');
        $this->pdf->Cell(50,5,mb_convert_encoding('Município: '.($this->config->municipio??'N/A'),'ISO-8859-1','UTF-8'),0,0,'L');
        $this->pdf->Cell(50,5,mb_convert_encoding('UF: '.       ($this->config->uf       ??'N/A'),'ISO-8859-1','UTF-8'),0,1,'L');
        $this->pdf->Cell(50,5,mb_convert_encoding('E-mail: '.   ($this->config->email    ??'N/A'),'ISO-8859-1','UTF-8'),0,0,'L');
        $this->pdf->Cell(50,5,mb_convert_encoding('Fone: '.     ($this->config->fone     ??'N/A'),'ISO-8859-1','UTF-8'),0,1,'L');

        $this->pdf->Ln(5);
        $this->pdf->SetFont('Arial','',9);
        $this->pdf->Cell(0,5,'___________________________________________',0,1,'C');
        $this->pdf->Cell(0,5,'Assinatura',0,1,'C');
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
        $this->pdf->Output('I', 'I');
    }

    public function render()
    {
        $pdf = $this->pdf->getPdf();
        $this->limparImagensTemporarias();
        return $pdf;
    }
}
