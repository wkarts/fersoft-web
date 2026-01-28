<?php

namespace App\Prints;

/**
 * Classe para a impressão em PDF do Documento Auxiliar de NFe Consumidor
 * NOTA: Esta classe não é a indicada para quem faz uso de impressoras térmicas ESCPOS
 *
 * @category  Library
 * @package   nfephp-org/sped-da
 * @copyright 2009-2016 NFePHP
 * @license   http://www.gnu.org/licenses/lesser.html LGPL v3
 * @link      http://github.com/nfephp-org/sped-da for the canonical source repository
 * @author    Roberto Spadim <roberto at spadim dot com dot br>
 */
use Exception;
use InvalidArgumentException;
use NFePHP\DA\Legacy\Dom;
use NFePHP\DA\Legacy\Pdf;
use NFePHP\DA\Legacy\Common;
use Com\Tecnick\Barcode\Barcode;
use DateTime;
use \Carbon\Carbon;

class CompraPrint80 extends Common
{
    protected $papel;
    protected $venda;
    protected $logomarca=''; // path para logomarca em jpg
    protected $formatoChave="#### #### #### #### #### #### #### #### #### #### ####";
    protected $debugMode=0; //ativa ou desativa o modo de debug
    protected $tpImp; //ambiente
    protected $fontePadrao='Times';
    protected $nfeProc;
    protected $nfe;
    protected $infNFe;
    protected $ide;
    protected $enderDest;
    protected $ICMSTot;
    protected $imposto;
    protected $emit;
    protected $enderEmit;
    protected $qrCode;
    protected $det;
    protected $infAdic;
    protected $textoAdic;
    protected $pag;
    protected $vTroco;
    protected $dest;
    protected $imgQRCode;
    protected $urlQR = '';
    protected $pdf;
    protected $margemInterna = 2;
    protected $hMaxLinha = 9;
    protected $hBoxLinha = 6;
    protected $hLinha = 3;
    protected $totalItens = 0;
    protected $config = null;
    protected $larg = 80;
    protected $suprimentos = [];
    protected $sangrias = [];
    protected $somaTiposPagamento = [];
    protected $abertura = null;
    protected $usuario = null;
    protected $somaVendas = 0;

    public function __construct(
        $venda = ''
    ) {

        $this->venda = $venda;
        if (empty($fonteDANFE)) {
            $this->fontePadrao = 'Times';
        } else {
            $this->fontePadrao = $fonteDANFE;
        }

    }

    public function getPapel()
    {
        return $this->papel;
    }

    public function setPapel($aPap)
    {
        $this->papel = $aPap;
    }

    public function monta(
        $orientacao = 'P',
        $papel = '',
        $logoAlign = 'C',
        $classPdf = false,
        $depecNumReg = ''
    ) {
        $this->montaDANFE($orientacao, $papel, $logoAlign, $classPdf, $depecNumReg);
    }

    public function montaDANFE(
        $orientacao = 'P',
        $papel = '',
        $logoAlign = 'C',
        $classPdf = false,
        $depecNumReg = '',
        $margSup = 5,   // Margem superior (padrão 2mm)
        $margEsq = 2,   // Margem esquerda (padrão 2mm)
        $margInf = 2,   // Margem inferior (padrão 2mm)
        $espacoExtra = 2 // Espaço extra ajustável (padrão 0)
    ) {
        // Obtendo automaticamente a quantidade de itens na venda
        $qtdItens = count($this->venda->itens);

        // Obtendo automaticamente a quantidade de formas de pagamento
        $qtdPgto = isset($this->venda->pagamentos) ? count($this->venda->pagamentos) : 1;

        // Iniciando parâmetros básicos do DANFE
        $hMaxLinha = $this->hMaxLinha;
        $hBoxLinha = $this->hBoxLinha;
        $hLinha = $this->hLinha;

        // verifica se existe informações adicionais
        $this->textoAdic = '';

        if ($orientacao == '') {
            $orientacao = 'P';
        }
        $this->orientacao = $orientacao;

        // Margens e posição inicial
        $xInic = $margEsq;
        $yInic = $margSup;
        $maxW = $this->larg;

        // Definição das alturas de cada seção do relatório
        $hcabecalho = 40; // Cabeçalho (dados emitente + logomarca)  (FIXO)
        $hcabecalhoSecundario =20; // Cabeçalho secundário (cabeçalho sefaz) (FIXO)
        $hCabecItens = 4; // Cabeçalho dos itens (pode ser ajustado dinamicamente)
        $hprodutos = $hLinha + ($qtdItens * $hMaxLinha); // Box produtos
        $hTotal = 12; // Box total (FIXO)
        $hpagamentos = $hLinha + ($qtdPgto * $hLinha); // Box pagamentos

        if (!empty($this->vTroco)) {
            $hpagamentos += $hLinha;
        }

        $hmsgfiscal = 21; // Box imposto (FIXO)

        if (!isset($this->dest)) {
            $hcliente = 6; // Box cliente (FIXO)
        } else {
            $hcliente = 3;
        } // Box cliente (FIXO)

        // Cálculo automático da altura total necessária
        $tamPapelVert = $espacoExtra +
            $hcabecalho +
            $hcabecalhoSecundario +
            $hCabecItens +
            $hprodutos +
            $hTotal +
            $hpagamentos +
            $hmsgfiscal +
            $hcliente +
            $espacoExtra;

        // Definição do papel com altura dinâmica
        $this->papel = array($this->larg, $tamPapelVert);
        $this->logoAlign = $logoAlign;
        $this->numero_registro_dpec = $depecNumReg;

        // Instancia a classe PDF
        if ($classPdf) {
            $this->pdf = $classPdf;
        } else {
            $this->pdf = new Pdf($this->orientacao, 'mm', $this->papel);
        }

        // Definição de margens e área imprimível
        $maxH = $tamPapelVert;
        $this->wPrint = $maxW - ($margEsq * 2);
        $this->hPrint = $maxH - $margSup - $margInf;

        // Estabelece contagem de páginas
        $this->pdf->aliasNbPages();

        // Define as margens do PDF
        $this->pdf->setMargins($margEsq, $margSup, $margInf);
        $this->pdf->setDrawColor(0, 0, 0);
        $this->pdf->setFillColor(255, 255, 255);
        $this->pdf->open(); // Inicia o documento
        $this->pdf->addPage($this->orientacao, $this->papel); // Adiciona a primeira página
        $this->pdf->setLineWidth(0.1); // Define a largura da linha
        $this->pdf->setTextColor(0, 0, 0);
        $this->pdf->textBox(0, 0, $maxW, $maxH); // POR QUE PRECISO DESA LINHA?

        $hUsado = $hCabecItens;
        $w2 = round($this->wPrint * 0.31, 0);
        $totPag = 1;
        $pag = 1;
        $x = $xInic;

        // COLOCA CABEÇALHO
        $y = $yInic;
        $y = $this->pCabecalhoDANFE($x, $y, $hcabecalho, $pag, $totPag);

        // COLOCA CABEÇALHO SECUNDÁRIO
        $y = $hcabecalho;
        $y = $this->pCabecalhoSecundarioDANFE($x, $y, $hcabecalhoSecundario);

        // COLOCA PRODUTOS
        $y = $xInic + $hcabecalho + $hcabecalhoSecundario;
        $y = $this->pProdutosDANFE($x, $y, $hprodutos);

        // COLOCA TOTAL
        $y = $xInic + $hcabecalho + $hcabecalhoSecundario + $hprodutos;
        $y = $this->pTotalDANFE($x, $y + 8, $hTotal);

        // ADICIONA RODAPÉ DINÂMICO NO FINAL DO RELATÓRIO
        $yRodape = $tamPapelVert - 15; // Posição final do rodapé

        $rodapeEsquerda = env('DANFE_RODAPE_ESQUERDA');
        $rodapeDireita = env('DANFE_RODAPE_DIREITA');
        $rodapeSite = env('DANFE_RODAPE_SITE');
        $rodapeEsquerda = str_replace('{DATA_HORA}', now()->format('d/m/Y H:i'), $rodapeEsquerda);

        $yRodape = $tamPapelVert - 15; // Garante que fique sempre no final

        // Define as fontes do rodapé
        $aFontRodape = array('font' => $this->fontePadrao, 'size' => 7, 'style' => '');

        // Renderiza o rodapé
        $this->pdf->textBox($xInic, $yRodape, $this->wPrint / 2, 5, $rodapeEsquerda, $aFontRodape, 'C', 'L', 0, '', false);
        $this->pdf->textBox($xInic + ($this->wPrint / 2), $yRodape, $this->wPrint / 2, 5, $rodapeDireita, $aFontRodape, 'C', 'R', 0, '', false);
        $yRodape += 4;
        $this->pdf->textBox($xInic, $yRodape, $this->wPrint, 5, $rodapeSite, $aFontRodape, 'C', 'C', 0, '', false);

    }

    protected function pCabecalhoDANFE($x = 0, $y = 0, $h = 0, $pag = '1', $totPag = '1')
    {
        // Obtém a pilha de chamadas (backtrace)
        $backtrace = debug_backtrace();

        // Inicializa variável para armazenar o Controller
        $controllerOrigem = 'Desconhecido';

        // Percorre a pilha de chamadas para encontrar o Controller
        foreach ($backtrace as $trace) {
            if (isset($trace['class']) && strpos($trace['class'], 'Controller') !== false) {
                $controllerOrigem = $trace['class'];
                break; // Para no primeiro Controller encontrado
            }
        }

        // Obtendo as configurações da empresa
        $this->config = \App\Models\ConfigNota::configStatic();
        $emitRazao  = $this->config->razao_social;
        $nomeFantasia  = $this->config->nome_fantasia;
        $emitCnpj   = str_replace(" ", "", $this->config->cnpj);
        $emitIE     = $this->config->ie;
        $emitFone   = !empty($this->config->fone) ? 'Fone: ' . $this->config->fone : '';

        // Endereço
        $emitEndereco = trim("{$this->config->logradouro}, {$this->config->numero} {$this->config->bairro}");
        $emitCidade = trim("{$this->config->municipio}-{$this->config->UF} - CEP: {$this->config->cep}");

        // Define que o documento é sempre uma COMPRA
        $tipoDocumento = "CÓDIGO DA COMPRA";
        $codigo = isset($this->compra) ? $this->compra->id : $this->venda->id;
        $dataDocumento = isset($this->compra) ? $this->compra->created_at : $this->venda->created_at;

        // Configuração de posição e margens
        $margemInterna = $this->margemInterna;
        $maxW = $this->wPrint;
        $h -= $margemInterna; // Ajuste de altura

        // Define variáveis padrão da logomarca
        $larguraLogoNoPDF = 50;
        $yLogo = $y; // Define a posição da logomarca no topo

        // Verifica se a logomarca existe
        $logoPath = public_path('logos/' . $this->config->logo);
        $alturaLogoNoPDF = 0; // Altura inicial da logo

        if (!empty($this->config->logo) && file_exists($logoPath)) {
            list($larguraLogo, $alturaLogo) = getimagesize($logoPath);

            // Mantém a proporção original da logo ao redimensionar
            $larguraMax = 50; // Largura máxima para a logo
            $escala = $larguraMax / $larguraLogo;
            $alturaLogoNoPDF = $alturaLogo * $escala;

            // Centraliza e adiciona ao PDF
            $xLogo = ($maxW - $larguraMax) / 2;
            $this->pdf->Image($logoPath, $xLogo, $y, $larguraMax, $alturaLogoNoPDF);

            // Ajusta o cabeçalho para ser impresso abaixo da logo
            $y += $alturaLogoNoPDF + 2;
        }

        $texto = "{$emitRazao}\n";
        if (!empty($nomeFantasia)) {
            $texto .= "{$nomeFantasia}\n";
        }
        $texto .= "CNPJ: {$emitCnpj}  IE: {$emitIE}\n";
        $texto .= "{$emitEndereco}\n{$emitCidade}\n";

        if (!empty($emitFone)) {
            $texto .= "Fone: {$emitFone}\n";
        }

        // Espaço antes do código da compra
        $texto .= "\n\n\n\n\n\n\n ";

        $codigoTexto = "CÓDIGO DA COMPRA: {$codigo} - DATA: " . __date($dataDocumento, 1);



        // Adiciona informação sobre o Controller que chamou
        //$texto .= "\n\nRelatório gerado via: {$controllerOrigem}";


        // Configuração da fonte do cabeçalho
        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');

        // Renderiza o texto do cabeçalho
        $this->pdf->textBox($x, $y, $maxW, $h, utf8_decode($texto), $aFont, 'C', 'L', 0, '', false);

        // Renderiza o código da compra centralizado
        $this->pdf->textBox($x, $y + $h - 6, $maxW, 5, utf8_decode($codigoTexto), $aFont, 'C', 'C', 0, '', false);
    }

    protected function pCabecalhoSecundarioDANFE($x = 0, $y = 0, $h = 0)
    {
        $margemInterna = $this->margemInterna;
        $maxW = $this->wPrint;

        // Definição da largura máxima para garantir que o texto fique dentro da área imprimível
        $w = $maxW - ($margemInterna * 2);

        // Definição dinâmica da altura dos blocos de cabeçalho
        $hBox1 = 8; // Altura do título principal
        $hBox2 = 5; // Espaço abaixo do título

        $texto = "COMPROVANTE DE COMPRA";
        $aFont = array('font' => $this->fontePadrao, 'size' => 9, 'style' => 'B');

        // Renderiza o título
        $this->pdf->textBox($x, $y, $w, $hBox1, $texto, $aFont, 'C', 'C', 0, '', false);

        // Pequeno espaço após o título
        $yBox2 = $y + $hBox1;
        $this->pdf->textBox($x, $yBox2, $w, $hBox2, "\n", $aFont, 'C', 'C', 0, '', false);


        // Define a nova posição Y para o espaço abaixo do título
        $yBox2 = $y + $hBox1;

        // Espaço adicional para evitar sobreposição do conteúdo abaixo
        $texto = "\n";

        // Configuração de fonte para o espaço em branco
        $aFont = array('font' => $this->fontePadrao, 'size' => 7, 'style' => '');

        // Renderiza a linha em branco abaixo do título para separação visual
        $this->pdf->textBox($x, $yBox2, $w, $hBox2, $texto, $aFont, 'C', 'C', 0, '', false);
    }

    protected function pCabecalhoSecundarioDANFE_($x = 0, $y = 0, $h = 0)
    {
        $margemInterna = $this->margemInterna;
        $maxW = $this->wPrint;
        $w = ($maxW*1);
        $hBox1 = 7;
        $texto = "COMPROVANTE DE COMPRA";
        $aFont = array('font'=>$this->fontePadrao, 'size'=>8, 'style'=>'B');
        $this->pdf->textBox($x, $y, $w, $hBox1, $texto, $aFont, 'C', 'C', 0, '', false);
        $hBox2 = 4;
        $yBox2 = $y + $hBox1;
        $texto = "\n";
        $aFont = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'');
        $this->pdf->textBox($x, $yBox2, $w, $hBox2, $texto, $aFont, 'C', 'C', 0, '', false);
    }

    protected function pProdutosDANFE($x = 0, $y = 0, $h = 0)
    {
        $margemInterna = $this->margemInterna;
        $maxW = $this->wPrint;
        $qtdItens = count($this->venda->itens);
        $w = ($maxW * 1);
        $hLinha = $this->hLinha + 2;

        // Definição das larguras das colunas para melhor organização
        $wBoxDescricao = $w * 0.50; // 50% da largura para a descrição
        $wBoxQt = $w * 0.12; // 12% para quantidade
        $wBoxVl = $w * 0.18; // 18% para valor unitário
        $wBoxTotal = $w * 0.20; // 20% para subtotal

        // Cabeçalho da tabela de produtos
        $aFontCabProdutos = array('font' => $this->fontePadrao, 'size' => 6, 'style' => 'B');

        $this->pdf->textBox($x, $y, $wBoxDescricao, $hLinha, "DESCRIÇÃO", $aFontCabProdutos, 'C', 'L', 0, '', false);
        $this->pdf->textBox($x + $wBoxDescricao, $y, $wBoxQt, $hLinha, "QTD", $aFontCabProdutos, 'C', 'C', 0, '', false);
        $this->pdf->textBox($x + $wBoxDescricao + $wBoxQt, $y, $wBoxVl, $hLinha, "VALOR", $aFontCabProdutos, 'C', 'R', 0, '', false);
        $this->pdf->textBox($x + $wBoxDescricao + $wBoxQt + $wBoxVl, $y, $wBoxTotal, $hLinha, "SUBTOTAL", $aFontCabProdutos, 'C', 'R', 0, '', false);

        // Corpo da tabela de produtos
        $aFontProdutos = array('font' => $this->fontePadrao, 'size' => 7, 'style' => '');
        $y += $hLinha; // Move a posição para a próxima linha

        if ($qtdItens > 0) {
            foreach ($this->venda->itens as $key => $p) {
                $this->totalItens += $p->quantidade;

                // Obter nome do produto com limitação de tamanho
                $xProd = trim($p->produto->nome);
                if (strlen($xProd) > 30) {
                    $xProd = substr($xProd, 0, 27) . "..."; // Truncar para evitar quebra de linha
                }

                // Formatar números para garantir alinhamento correto
                $qCom = number_format($p->quantidade, 2, ',', '.');
                $vUnCom = 'R$ ' . number_format($p->valor_unitario, 2, ',', '.');
                $vProd = 'R$ ' . number_format($p->valor_unitario * $p->quantidade, 2, ',', '.');

                // Posicionamento da linha do produto
                $this->pdf->textBox($x, $y, $wBoxDescricao, $hLinha, utf8_decode($xProd), $aFontProdutos, 'C', 'L', 0, '', false);
                $this->pdf->textBox($x + $wBoxDescricao, $y, $wBoxQt, $hLinha, $qCom, $aFontProdutos, 'C', 'C', 0, '', false);
                $this->pdf->textBox($x + $wBoxDescricao + $wBoxQt, $y, $wBoxVl, $hLinha, $vUnCom, $aFontProdutos, 'C', 'R', 0, '', false);
                $this->pdf->textBox($x + $wBoxDescricao + $wBoxQt + $wBoxVl, $y, $wBoxTotal, $hLinha, $vProd, $aFontProdutos, 'C', 'R', 0, '', false);

                $y += $hLinha; // Move para a próxima linha do produto
            }
        }
    }



    protected function pProdutosDANFE2($x = 0, $y = 0, $h = 0)
    {
        $margemInterna = $this->margemInterna;
        $maxW = $this->wPrint;
        $qtdItens = count($this->venda->itens);
        $w = ($maxW * 1);
        $hLinha = $this->hLinha + 2;
        $hMaxLinha = $this->hMaxLinha;
        $hBoxLinha = $this->hBoxLinha;
        $cont = 0;

        // Fontes
        $aFontCabProdutos = array('font' => $this->fontePadrao, 'size' => 6, 'style' => 'B');
        $aFontProdutos = array('font' => $this->fontePadrao, 'size' => 7, 'style' => '');
        $aFontDesc = array('font' => $this->fontePadrao, 'size' => 6.5, 'style' => '');

        // Definição de larguras das colunas ajustáveis
        $wBoxReferencia = $w * 0.12;
        $wBoxDescricao = $w * 0.48;
        $wBoxQt = $w * 0.08;
        $wBoxVl = $w * 0.15;
        $wBoxTotal = $w * 0.17;

        // Cabeçalho dos produtos
        $this->pdf->textBox($x, $y, $wBoxReferencia, $hLinha, "CÓDIGO REF.", $aFontCabProdutos, 'T', 'C', 0, '', false);
        $this->pdf->textBox($x + $wBoxReferencia, $y, $wBoxDescricao, $hLinha, "DESCRIÇÃO", $aFontCabProdutos, 'T', 'L', 0, '', false);
        $this->pdf->textBox($x + $wBoxReferencia + $wBoxDescricao, $y, $wBoxQt, $hLinha, "QTD", $aFontCabProdutos, 'T', 'C', 0, '', false);
        $this->pdf->textBox($x + $wBoxReferencia + $wBoxDescricao + $wBoxQt, $y, $wBoxVl, $hLinha, "VALOR", $aFontCabProdutos, 'T', 'R', 0, '', false);
        $this->pdf->textBox($x + $wBoxReferencia + $wBoxDescricao + $wBoxQt + $wBoxVl, $y, $wBoxTotal, $hLinha, "SUBTOTAL", $aFontCabProdutos, 'T', 'R', 0, '', false);

        if ($qtdItens > 0) {
            foreach ($this->venda->itens as $key => $p) {
                $this->totalItens += $p->quantidade;

                // Obtém a referência do produto
                $cProd = $p->produto->referencia ?: "-";

                // Definição do nome do produto
                if (isset($p->itemPedido) && $p->itemPedido != null) {
                    $xProd = $p->itemPedido->nomeDoProduto();
                } elseif ($this->venda->pedido_delivery_id > 0) {
                    $xProd = $p->nomeDoProdutoDelivery($this->venda->pedido_delivery_id, $key);
                } else {
                    $xProd = $p->produto->nome;
                    if ($p->produto->grade) {
                        $xProd .= " " . $p->produto->str_grade;
                    }
                }

                // Truncar nome do produto para evitar quebra de layout
                $xProd = (strlen($xProd) > 40) ? substr($xProd, 0, 37) . '...' : $xProd;

                // Formatação de valores
                $qCom = number_format($p->quantidade, $this->config->casas_decimais_qtd);
                $vUnCom = number_format($p->valor_unitario, $this->config->casas_decimais, ",", ".");
                $vProd = number_format($p->valor_unitario * $p->quantidade, $this->config->casas_decimais, ",", ".");

                // Posição do produto na tabela
                $yBoxProd = $y + $hLinha + ($cont * $hMaxLinha);

                // Insere os produtos na tabela
                $this->pdf->textBox($x, $yBoxProd, $wBoxReferencia, $hMaxLinha, $cProd, $aFontProdutos, 'C', 'C', 0, '', false);
                $this->pdf->textBox($x + $wBoxReferencia, $yBoxProd, $wBoxDescricao, $hMaxLinha, $xProd, $aFontDesc, 'C', 'L', 0, '', false);
                $this->pdf->textBox($x + $wBoxReferencia + $wBoxDescricao, $yBoxProd, $wBoxQt, $hMaxLinha, $qCom, $aFontProdutos, 'C', 'C', 0, '', false);
                $this->pdf->textBox($x + $wBoxReferencia + $wBoxDescricao + $wBoxQt, $yBoxProd, $wBoxVl, $hMaxLinha, $vUnCom, $aFontProdutos, 'C', 'R', 0, '', false);
                $this->pdf->textBox($x + $wBoxReferencia + $wBoxDescricao + $wBoxQt + $wBoxVl, $yBoxProd, $wBoxTotal, $hMaxLinha, $vProd, $aFontProdutos, 'C', 'R', 0, '', false);

                $cont++;
            }
        }
    }

    protected function pProdutosDANFE_($x = 0, $y = 0, $h = 0)
    {
        $margemInterna = $this->margemInterna;
        $maxW = $this->wPrint;
        $qtdItens = count($this->venda->itens);
        $w = ($maxW*1);
        $hLinha = $this->hLinha+2;
        $aFontCabProdutos = array('font'=>$this->fontePadrao, 'size'=>6, 'style'=>'B');
        $wBoxCod = $w*0;
        $texto = "";
        $this->pdf->textBox($x, $y, $wBoxCod, $hLinha+1, $texto, $aFontCabProdutos, 'T', 'L', 0, '', false);
        $wBoxDescricao = $w*0.60;
        $xBoxDescricao = $wBoxCod + $x;
        $texto = "DESCRICÃO";
        $this->pdf->textBox(
            $xBoxDescricao,
            $y,
            $wBoxDescricao,
            $hLinha,
            $texto,
            $aFontCabProdutos,
            'T',
            'L',
            0,
            '',
            false
        );
        $wBoxQt = $w*0.08;
        $xBoxQt = $wBoxDescricao + $xBoxDescricao;
        $texto = "QTD";
        $this->pdf->textBox($xBoxQt, $y, $wBoxQt, $hLinha, $texto, $aFontCabProdutos, 'T', 'L', 0, '', false);
        $wBoxUn = $w*0;
        $xBoxUn = $wBoxQt + $xBoxQt;
        $texto = "";
        $this->pdf->textBox($xBoxUn, $y, $wBoxUn, $hLinha, $texto, $aFontCabProdutos, 'T', 'L', 0, '', false);
        $wBoxVl = $w*0.15;
        $xBoxVl = $wBoxUn + $xBoxUn;
        $texto = "VALOR";
        $this->pdf->textBox($xBoxVl, $y, $wBoxVl, $hLinha, $texto, $aFontCabProdutos, 'T', 'L', 0, '', false);
        $wBoxTotal = $w*0.17;
        $xBoxTotal = $wBoxVl + $xBoxVl;
        $texto = "SUBTOTAL";
        $this->pdf->textBox($xBoxTotal, $y, $wBoxTotal, $hLinha, $texto, $aFontCabProdutos, 'T', 'L', 0, '', false);
        $hBoxLinha = $this->hBoxLinha;
        $hMaxLinha = $this->hMaxLinha;
        $cont = 0;
        $aFontProdutos = array('font'=>$this->fontePadrao, 'size'=>7.5, 'style'=>'');
        if ($qtdItens > 0) {

            foreach ($this->venda->itens as $key => $p) {

                $this->totalItens += $p->quantidade;
                $thisItem   = '1';
                $prod       = '@';
                $nitem      = 1;
                $cProd      = $p->produto->referencia;

                if(isset($p->itemPedido) && $p->itemPedido != null){
                    $xProd = $p->itemPedido->nomeDoProduto();
                } else if($this->venda->pedido_delivery_id > 0){
                    $xProd = $p->nomeDoProdutoDelivery($this->venda->pedido_delivery_id, $key);
                }else{
                    $xProd = ($cProd != "" ? "$cProd - " : "").$p->produto->nome;
                    if($p->produto->grade){
                        $xProd .= " " . $p->produto->str_grade;
                    }
                }

                $qCom       = number_format($p->quantidade, $this->config->casas_decimais_qtd);
                $uCom       = $p->produto->unidade_venda == 'UNID' ? 'UN' :
                    $p->produto->unidade_venda;

                $vUnCom     = number_format($p->valor_unitario, $this->config->casas_decimais, ",", ".");
                $vProd      = number_format($p->valor_unitario * $p->quantidade, $this->config->casas_decimais, ",", ".");

                $comp = 0;
                if(strlen($xProd) > 30 && strlen($xProd) < 40){
                    $comp = 1.2;
                }else if(strlen($xProd) > 40 && strlen($xProd) < 50){
                    $comp = 2.2;
                }else if(strlen($xProd) > 50){
                    $comp = 3.2;
                }
                //COLOCA PRODUTO
                $yBoxProd = $y + $hLinha + ($cont*$hMaxLinha);
                //COLOCA PRODUTO CÓDIGO
                $wBoxCod = $w*0;
                $texto = '';
                $this->pdf->textBox($x, $yBoxProd, $wBoxCod, $hMaxLinha, $texto, $aFontProdutos, 'C', 'C', 0, '', false);
                //COLOCA PRODUTO DESCRIÇÃO
                $wBoxDescricao = $w*0.60;
                $xBoxDescricao = $wBoxCod + $x;
                $texto = $xProd;
                $this->pdf->textBox(
                    $xBoxDescricao,
                    $yBoxProd,
                    $wBoxDescricao,
                    $hMaxLinha,
                    $texto,
                    $aFontProdutos,
                    'C',
                    'L',
                    0,
                    '',
                    false
                );
                //COLOCA PRODUTO QUANTIDADE
                $wBoxQt = $w*0.08;
                $xBoxQt = $wBoxDescricao + $xBoxDescricao;
                $texto = $qCom;
                $this->pdf->textBox(
                    $xBoxQt,
                    $yBoxProd,
                    $wBoxQt,
                    $hMaxLinha,
                    $texto,
                    $aFontProdutos,
                    'C',
                    'C',
                    0,
                    '',
                    false
                );
                //COLOCA PRODUTO UNIDADE
                $wBoxUn = $w*0;
                $xBoxUn = $wBoxQt + $xBoxQt;
                $texto = '';
                $this->pdf->textBox(
                    $xBoxUn,
                    $yBoxProd,
                    $wBoxUn,
                    $hMaxLinha,
                    $texto,
                    $aFontProdutos,
                    'C',
                    'C',
                    0,
                    '',
                    false
                );
                //COLOCA PRODUTO VL UNITÁRIO
                $wBoxVl = $w*0.15;
                $xBoxVl = $wBoxUn + $xBoxUn;
                $texto = $vUnCom;
                $this->pdf->textBox(
                    $xBoxVl,
                    $yBoxProd,
                    $wBoxVl,
                    $hMaxLinha,
                    $texto,
                    $aFontProdutos,
                    'C',
                    'R',
                    0,
                    '',
                    false
                );
                //COLOCA PRODUTO VL TOTAL
                $wBoxTotal = $w*0.15;
                $xBoxTotal = $wBoxVl + $xBoxVl;
                $texto = $vProd;
                $this->pdf->textBox(
                    $xBoxTotal,
                    $yBoxProd,
                    $wBoxTotal,
                    $hMaxLinha,
                    $texto,
                    $aFontProdutos,
                    'C',
                    'R',
                    0,
                    '',
                    false
                );

                $cont++;
            }
        }
    }

    protected function pTotalDANFE($x = 0, $y = 0, $h = 0)
    {
        $margemInterna = $this->margemInterna;
        $maxW = $this->wPrint;
        $hLinha = 3;
        $wColEsq = ($maxW*0.7);
        $wColDir = ($maxW*0.3);
        $xValor = $x + $wColEsq;
        $qtdItens = count($this->venda->itens);
        $vProd = $this->getTagValue($this->ICMSTot, "vProd");
        $vNF = $this->getTagValue($this->ICMSTot, "vNF");
        $vDesc  = $this->getTagValue($this->ICMSTot, "vDesc");
        $vFrete = $this->getTagValue($this->ICMSTot, "vFrete");
        $vTotTrib = $this->getTagValue($this->ICMSTot, "vTotTrib");
        $texto = "Qtd. Total de Itens";
        $aFont = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B');
        $this->pdf->textBox($x, $y, $wColEsq, $hLinha, $texto, $aFont, 'T', 'L', 0, '', false);
        $texto = $qtdItens;
        $aFont = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B');
        $this->pdf->textBox($xValor, $y, $wColDir, $hLinha, $texto, $aFont, 'T', 'R', 0, '', false);
        $yTotal = $y + ($hLinha);
        $texto = "Total de Produtos";
        $aFont = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B');
        $this->pdf->textBox($x, $yTotal, $wColEsq, $hLinha, $texto, $aFont, 'T', 'L', 0, '', false);
        $texto = "" . $this->totalItens;
        $aFont = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B');
        $this->pdf->textBox($xValor, $yTotal, $wColDir, $hLinha, $texto, $aFont, 'T', 'R', 0, '', false);

        $yDesconto = $y + ($hLinha*2);
        $texto = "Total";
        $aFont = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B');
        $this->pdf->textBox($x, $yDesconto, $wColEsq, $hLinha, $texto, $aFont, 'T', 'L', 0, '', false);
        $texto = "R$ " . number_format($this->venda->valor, $this->config->casas_decimais, ',', '.');
        $aFont = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B');
        $this->pdf->textBox($xValor, $yDesconto, $wColDir, $hLinha, $texto, $aFont, 'T', 'R', 0, '', false);
        $contLinha = 2;

        if($this->venda->desconto > 0){
            $contLinha++;
            $yTotalFinal = $y + ($hLinha*$contLinha);
            $texto = "Desconto";
            $aFont = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B');
            $this->pdf->textBox($x, $yTotalFinal, $wColEsq, $hLinha, $texto, $aFont, 'T', 'L', 0, '', false);
            $texto = "R$ " . number_format($this->venda->desconto, 2, ',', '.');
            $aFont = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B');
            $this->pdf->textBox($xValor, $yTotalFinal, $wColDir, $hLinha, $texto, $aFont, 'T', 'R', 0, '', false);
        }

        if($this->venda->acrescimo > 0){
            $contLinha++;

            $yTotalFinal = $y + ($hLinha*$contLinha);
            $texto = "Acrescimo";
            $aFont = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B');
            $this->pdf->textBox($x, $yTotalFinal, $wColEsq, $hLinha, $texto, $aFont, 'T', 'L', 0, '', false);
            $texto = "R$ " . number_format($this->venda->acrescimo, 2, ',', '.');
            $aFont = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B');
            $this->pdf->textBox($xValor, $yTotalFinal, $wColDir, $hLinha, $texto, $aFont, 'T', 'R', 0, '', false);
        }

        if($this->venda->observacao != ''){
            $contLinha++;
            $yTotalFinal = $y + ($hLinha*$contLinha);
            $texto = "Observação";
            $aFont = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B');
            $this->pdf->textBox($x, $yTotalFinal, $wColEsq, $hLinha, $texto, $aFont, 'T', 'L', 0, '', false);
            $texto = $this->venda->observacao;
            $aFont = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B');
            $this->pdf->textBox($xValor, $yTotalFinal, $wColDir, $hLinha, $texto, $aFont, 'T', 'R', 0, '', false);
        }

        $fornecedor = $this->venda->fornecedor;

        if($fornecedor){
            $contLinha++;
            $contLinha++;
            $yTotalFinal = $y + ($hLinha*$contLinha);
            $texto = $fornecedor->razao_social;
            $aFont = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B');
            $this->pdf->textBox($x, $yTotalFinal, $wColEsq+20, $hLinha, $texto, $aFont, 'T', 'L', 0, '', false);

            $contLinha++;
            $yTotalFinal = $y + ($hLinha*$contLinha);
            $texto = $fornecedor->cpf_cnpj;
            $aFont = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B');
            $this->pdf->textBox($x, $yTotalFinal, $wColEsq+20, $hLinha, $texto, $aFont, 'T', 'L', 0, '', false);
            $contLinha++;

            $yTotalFinal = $y + ($hLinha*$contLinha);
            $texto = "$fornecedor->rua, $fornecedor->numero - $fornecedor->bairro - " . $fornecedor->cidade->nome . "(" . $fornecedor->cidade->uf . ")";
            $aFont = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B');
            $this->pdf->textBox($x, $yTotalFinal, $wColEsq+20, $hLinha, $texto, $aFont, 'T', 'L', 0, '', false);
        }

        // if(isset($this->venda->fatura)){
        //     if(sizeof($this->venda->fatura) > 0){
        //         foreach($this->venda->fatura as $f){

        //             $contLinha++;
        //             $yTotalFinal = $y + ($hLinha*$contLinha);
        //             $texto = $f->tipo_pagamento;
        //             $aFont = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B');
        //             $this->pdf->textBox($x, $yTotalFinal, $wColEsq, $hLinha, $texto, $aFont, 'T', 'L', 0, '', false);
        //             $texto = "R$ " . number_format($f->valor_integral, 2, ',', '.');
        //             $aFont = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B');
        //             $this->pdf->textBox($xValor, $yTotalFinal, $wColDir, $hLinha, $texto, $aFont, 'T', 'R', 0, '', false);
        //         }
        //     }
        // }
    }

    protected function pPagamentosDANFE($x = 0, $y = 0, $h = 0)
    {
        $y += 6;
        $margemInterna = $this->margemInterna;
        $maxW = $this->wPrint;
        $qtdPgto = $this->pag->length;
        $w = ($maxW*1);
        $hLinha = $this->hLinha;
        $wColEsq = ($maxW*0.7);
        $wColDir = ($maxW*0.3);
        $xValor = $x + $wColEsq;
        $aFontPgto = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B');
        $wBoxEsq = $w*0.7;
        $texto = "FORMA DE PAGAMENTO";
        $this->pdf->textBox($x, $y, $wBoxEsq, $hLinha, $texto, $aFontPgto, 'T', 'L', 0, '', false);
        $wBoxDir = $w*0.3;
        $xBoxDescricao = $x + $wBoxEsq;
        $texto = "VALOR PAGO";
        $this->pdf->textBox($xBoxDescricao, $y, $wBoxDir, $hLinha, $texto, $aFontPgto, 'T', 'R', 0, '', false);
        $cont = 0;
        if ($qtdPgto > 0) {
            foreach ($this->pag as $pagI) {
                $tPag = $this->getTagValue($pagI, "tPag");
                $tPagNome = $this->tipoPag($tPag);
                $tPnome = $tPagNome;
                $vPag = number_format($this->getTagValue($pagI, "vPag"), 2, ",", ".");
                $card = $pagI->getElementsByTagName("card")->item(0);
                $cardCNPJ = '';
                $tBand = '';
                $tBandNome = '';
                if (isset($card)) {
                    $cardCNPJ = $this->getTagValue($card, "CNPJ");
                    $tBand    = $this->getTagValue($card, "tBand");
                    $cAut = $this->getTagValue($card, "cAut");
                    $tBandNome = self::getCardName($tBand);
                }
                //COLOCA PRODUTO
                $yBoxProd = $y + $hLinha + ($cont*$hLinha);
                //COLOCA PRODUTO CÓDIGO
                $texto = $tPagNome;
                $this->pdf->textBox($x, $yBoxProd, $wBoxEsq, $hLinha, $texto, $aFontPgto, 'T', 'L', 0, '', false);
                //COLOCA PRODUTO DESCRIÇÃO
                $xBoxDescricao = $wBoxEsq + $x;
                $texto = "R$ " . $vPag;
                $this->pdf->textBox(
                    $xBoxDescricao,
                    $yBoxProd,
                    $wBoxDir,
                    $hLinha,
                    $texto,
                    $aFontPgto,
                    'C',
                    'R',
                    0,
                    '',
                    false
                );
                $cont++;
            }

            if (!empty($this->vTroco)) {
                $yBoxProd = $y + $hLinha + ($cont*$hLinha);
                //COLOCA PRODUTO CÓDIGO
                $texto = 'Troco';
                $this->pdf->textBox($x, $yBoxProd, $wBoxEsq, $hLinha, $texto, $aFontPgto, 'T', 'L', 0, '', false);
                //COLOCA PRODUTO DESCRIÇÃO
                $xBoxDescricao = $wBoxEsq + $x;
                $texto = "R$ " . number_format($this->vTroco, 2, ",", ".");
                $this->pdf->textBox(
                    $xBoxDescricao,
                    $yBoxProd,
                    $wBoxDir,
                    $hLinha,
                    $texto,
                    $aFontPgto,
                    'C',
                    'R',
                    0,
                    '',
                    false
                );
            }
        }
    }

    protected function pFiscalDANFE($x = 0, $y = 0, $h = 0)
    {
        $y += 6;
        $margemInterna = $this->margemInterna;
        $maxW = $this->wPrint;
        $w = ($maxW*1);
        $hLinha = $this->hLinha;
        $aFontTit = array('font'=>$this->fontePadrao, 'size'=>8, 'style'=>'B');
        $aFontTex = array('font'=>$this->fontePadrao, 'size'=>8, 'style'=>'');
        $digVal = $this->getTagValue($this->nfe, "DigestValue");
        $chNFe = str_replace('NFe', '', $this->infNFe->getAttribute("Id"));
        $tpAmb = $this->getTagValue($this->ide, 'tpAmb');

        if ($this->pNotaCancelada()) {
            //101 Cancelamento
            $this->pdf->SetTextColor(255, 0, 0);
            $texto = "NFCe CANCELADA";
            $this->pdf->textBox($x, $y - 25, $w, $h, $texto, $aFontTit, 'C', 'C', 0, '');
            $this->pdf->SetTextColor(0, 0, 0);
        }

        if ($this->pNotaDenegada()) {
            //uso denegado
            $this->pdf->SetTextColor(255, 0, 0);
            $texto = "NFCe CANCELADA";
            $this->pdf->textBox($x, $y - 25, $w, $h, $texto, $aFontTit, 'C', 'C', 0, '');
            $this->pdf->SetTextColor(0, 0, 0);
        }

        $cUF = $this->getTagValue($this->ide, 'cUF');
        $nNF = $this->getTagValue($this->ide, 'nNF');
        $serieNF = str_pad($this->getTagValue($this->ide, "serie"), 3, "0", STR_PAD_LEFT);
        $dhEmi = $this->getTagValue($this->ide, "dhEmi");
        $dhEmilocal = new \DateTime($dhEmi);
        $dhEmiLocalFormat = $dhEmilocal->format('d/m/Y H:i:s');
        $urlChave = $this->urlConsulta[$tpAmb][$this->UFSigla[$cUF]];
        $texto = "ÁREA DE MENSAGEM FISCAL";
        $this->pdf->textBox($x, $y, $w, $hLinha, $texto, $aFontTit, 'C', 'C', 0, '', false);
        $yTex1 = $y + ($hLinha*1);
        $hTex1 = $hLinha*2;
        $texto = "Número " . $nNF . " Série " . $serieNF . " " .$dhEmiLocalFormat . " - Via Consumidor";
        $this->pdf->textBox($x, $yTex1, $w, $hTex1, $texto, $aFontTex, 'C', 'C', 0, '', false);
        $yTex2 = $y + ($hLinha*3);
        $hTex2 = $hLinha*2;
        $texto = "Consulte pela Chave de Acesso em " . $urlChave;
        $this->pdf->textBox($x, $yTex2, $w, $hTex2, $texto, $aFontTex, 'C', 'C', 0, '', false);
        $texto = "CHAVE DE ACESSO";
        $yTit2 = $y + ($hLinha*5);
        $this->pdf->textBox($x, $yTit2, $w, $hLinha, $texto, $aFontTit, 'C', 'C', 0, '', false);
        $yTex3 = $y + ($hLinha*6);
        $texto = $chNFe;
        $this->pdf->textBox($x, $yTex3, $w, $hLinha, $texto, $aFontTex, 'C', 'C', 0, '', false);
    }

    protected function pConsumidorDANFE($x = 0, $y = 0, $h = 0)
    {
        $y += 6;
        $margemInterna = $this->margemInterna;
        $maxW = $this->wPrint;
        $w = ($maxW*1);
        $hLinha = $this->hLinha;
        $aFontTit = array('font'=>$this->fontePadrao, 'size'=>8, 'style'=>'B');
        $aFontTex = array('font'=>$this->fontePadrao, 'size'=>8, 'style'=>'');
        $texto = "CONSUMIDOR";
        $this->pdf->textBox($x, $y, $w, $hLinha, $texto, $aFontTit, 'C', 'C', 0, '', false);
        if (isset($this->dest)) {
            $considEstrangeiro = !empty($this->dest->getElementsByTagName("idEstrangeiro")->item(0)->nodeValue)
                ? $this->dest->getElementsByTagName("idEstrangeiro")->item(0)->nodeValue
                : '';
            $consCPF = !empty($this->dest->getElementsByTagName("CPF")->item(0)->nodeValue)
                ? $this->dest->getElementsByTagName("CPF")->item(0)->nodeValue
                : '';
            $consCNPJ = !empty($this->dest->getElementsByTagName("CNPJ")->item(0)->nodeValue)
                ? $this->dest->getElementsByTagName("CNPJ")->item(0)->nodeValue
                : '';
            $cDest = $consCPF.$consCNPJ.$considEstrangeiro; //documentos do consumidor
            $enderDest = $this->dest->getElementsByTagName("enderDest")->item(0);
            $consNome = $this->getTagValue($this->dest, "xNome");
            $consLgr = $this->getTagValue($enderDest, "xLgr");
            $consNro = $this->getTagValue($enderDest, "nro");
            $consCpl = $this->getTagValue($enderDest, "xCpl", " - ");
            $consBairro = $this->getTagValue($enderDest, "xBairro");
            $consCEP = $this->pFormat($this->getTagValue($enderDest, "CEP"));
            $consMun = $this->getTagValue($enderDest, "xMun");
            $consUF = $this->getTagValue($enderDest, "UF");
            $considEstrangeiro = $this->getTagValue($this->dest, "idEstrangeiro");
            $consCPF = $this->getTagValue($this->dest, "CPF");
            $consCNPJ = $this->getTagValue($this->dest, "CNPJ");
            $consDoc = "";
            if (!empty($consCNPJ)) {
                $consDoc = "CNPJ: $consCNPJ";
            } elseif (!empty($consCPF)) {
                $consDoc = "CPF: $consCPF";
            } elseif (!empty($considEstrangeiro)) {
                $consDoc = "id: $considEstrangeiro";
            }
            $consEnd = "";
            if (!empty($consLgr)) {
                $consEnd = $consLgr
                    . ","
                    . $consNro
                    . " "
                    . $consCpl
                    . ","
                    . $consBairro
                    . ". CEP:"
                    . $consCEP
                    . ". "
                    . $consMun
                    . "-"
                    . $consUF;
            }
            $yTex1 = $y + $hLinha;
            $texto = $consNome;
            if (!empty($consDoc)) {
                $texto .= " - ". $consDoc . "\n" . $consEnd;
                $this->pdf->textBox($x, $yTex1, $w, $hLinha*3, $texto, $aFontTex, 'C', 'C', 0, '', false);
            }
        } else {
            $yTex1 = $y + $hLinha;
            $texto = "Consumidor não identificado";
            $this->pdf->textBox($x, $yTex1, $w, $hLinha, $texto, $aFontTex, 'C', 'C', 0, '', false);
        }
    }

    protected function pQRDANFE($x = 0, $y = 0, $h = 0)
    {
        $y += 6;
        $margemInterna = $this->margemInterna;
        $maxW = $this->wPrint;
        $w = ($maxW*1)+4;
        $hLinha = $this->hLinha;
        $hBoxLinha = $this->hBoxLinha;
        $aFontTit = array('font'=>$this->fontePadrao, 'size'=>8, 'style'=>'B');
        $aFontTex = array('font'=>$this->fontePadrao, 'size'=>8, 'style'=>'');
        $dhRecbto = '';
        $nProt = '';
        if (isset($this->nfeProc)) {
            $nProt = $this->getTagValue($this->nfeProc, "nProt");
            $dhRecbto  = $this->getTagValue($this->nfeProc, "dhRecbto");
        }
        $barcode = new Barcode();
        $bobj = $barcode->getBarcodeObj(
            'QRCODE,M',
            $this->qrCode,
            -4,
            -4,
            'black',
            array(-2, -2, -2, -2)
        )->setBackgroundColor('white');
        $qrcode = $bobj->getPngData();
        $wQr = 50;
        $hQr = 50;
        $yQr = ($y+$margemInterna);
        $xQr = ($w/2) - ($wQr/2);
        // prepare a base64 encoded "data url"
        $pic = 'data://text/plain;base64,' . base64_encode($qrcode);
        $info = getimagesize($pic);
        $this->pdf->image($pic, $xQr, $yQr, $wQr, $hQr, 'PNG');
        $dt = new DateTime($dhRecbto);
        $yQr = ($yQr+$hQr+$margemInterna);
        $this->pdf->textBox($x, $yQr, $w-4, $hBoxLinha, "Protocolo de Autorização: " . $nProt . "\n"
            . $dt->format('d/m/Y H:i:s'), $aFontTex, 'C', 'C', 0, '', false);
    }

    protected function pInfAdic($x = 0, $y = 0, $h = 0)
    {
        $y += 17;
        $margemInterna = $this->margemInterna;
        $maxW = $this->wPrint;
        $w = ($maxW * 1);
        $hLinha = $this->hLinha;
        $aFontTit = array('font' => $this->fontePadrao, 'size' => 8, 'style' => 'B');
        $aFontTex = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
        // seta o textbox do titulo
        $texto = "INFORMAÇÃO ADICIONAL";
        $heigthText = $this->pdf->textBox($x, $y, $w, $hLinha, $texto, $aFontTit, 'C', 'C', 0, '', false);

        // seta o textbox do texto adicional
        $this->pdf->textBox($x, $y+3, $w-2, $hLinha-3, $this->textoAdic, $aFontTex, 'T', 'L', 0, '', false);
    }

    /**
     * printDANFE
     * Esta função envia a DANFE em PDF criada para o dispositivo informado.
     * O destino da impressão pode ser :
     * I-browser
     * D-browser com download
     * F-salva em um arquivo local com o nome informado
     * S-retorna o documento como uma string e o nome é ignorado.
     * Para enviar o pdf diretamente para uma impressora indique o
     * nome da impressora e o destino deve ser 'S'.
     *
     * @param  string $nome    Path completo com o nome do arquivo pdf
     * @param  string $destino Direção do envio do PDF
     * @param  string $printer Identificação da impressora no sistema
     * @return string Caso o destino seja S o pdf é retornado como uma string
     * @todo   Rotina de impressão direta do arquivo pdf criado
     */
    public function printDANFE($nome = '', $destino = 'I', $printer = '')
    {
        $arq = $this->pdf->Output($nome, $destino);
        if ($destino == 'S') {
            //aqui pode entrar a rotina de impressão direta
        }
        return $arq;
    }
    /**
     * Dados brutos do PDF
     * @return string
     */
    public function render()
    {
        return $this->pdf->getPdf();
    }

    /**
     * anfavea
     * Função para transformar o campo cdata do padrão ANFAVEA para
     * texto imprimível
     *
     * @param  string $cdata campo CDATA
     * @return string conteúdo do campo CDATA como string
     */
    protected function pAnfavea($cdata = '')
    {
        if ($cdata == '') {
            return '';
        }
        //remove qualquer texto antes ou depois da tag CDATA
        $cdata = str_replace('<![CDATA[', '<CDATA>', $cdata);
        $cdata = str_replace(']]>', '</CDATA>', $cdata);
        $cdata = preg_replace('/\s\s+/', ' ', $cdata);
        $cdata = str_replace("> <", "><", $cdata);
        $len = strlen($cdata);
        $startPos = strpos($cdata, '<');
        if ($startPos === false) {
            return $cdata;
        }
        for ($x=$len; $x>0; $x--) {
            if (substr($cdata, $x, 1) == '>') {
                $endPos = $x;
                break;
            }
        }
        if ($startPos > 0) {
            $parte1 = substr($cdata, 0, $startPos);
        } else {
            $parte1 = '';
        }
        $parte2 = substr($cdata, $startPos, $endPos-$startPos+1);
        if ($endPos < $len) {
            $parte3 = substr($cdata, $endPos + 1, $len - $endPos - 1);
        } else {
            $parte3 = '';
        }
        $texto = trim($parte1).' '.trim($parte3);
        if (strpos($parte2, '<CDATA>') === false) {
            $cdata = '<CDATA>'.$parte2.'</CDATA>';
        } else {
            $cdata = $parte2;
        }
        //carrega o xml CDATA em um objeto DOM
        $dom = new Dom();
        $dom->loadXML($cdata, LIBXML_NOBLANKS | LIBXML_NOEMPTYTAG);
        //$xml = $dom->saveXML();
        //grupo CDATA infADprod
        $id = $dom->getElementsByTagName('id')->item(0);
        $div = $dom->getElementsByTagName('div')->item(0);
        $entg = $dom->getElementsByTagName('entg')->item(0);
        $dest = $dom->getElementsByTagName('dest')->item(0);
        $ctl = $dom->getElementsByTagName('ctl')->item(0);
        $ref = $dom->getElementsByTagName('ref')->item(0);
        if (isset($id)) {
            if ($id->hasAttributes()) {
                foreach ($id->attributes as $attr) {
                    $name = $attr->nodeName;
                    $value = $attr->nodeValue;
                    $texto .= " $name : $value";
                }
            }
        }
        if (isset($div)) {
            if ($div->hasAttributes()) {
                foreach ($div->attributes as $attr) {
                    $name = $attr->nodeName;
                    $value = $attr->nodeValue;
                    $texto .= " $name : $value";
                }
            }
        }
        if (isset($entg)) {
            if ($entg->hasAttributes()) {
                foreach ($entg->attributes as $attr) {
                    $name = $attr->nodeName;
                    $value = $attr->nodeValue;
                    $texto .= " $name : $value";
                }
            }
        }
        if (isset($dest)) {
            if ($dest->hasAttributes()) {
                foreach ($dest->attributes as $attr) {
                    $name = $attr->nodeName;
                    $value = $attr->nodeValue;
                    $texto .= " $name : $value";
                }
            }
        }
        if (isset($ctl)) {
            if ($ctl->hasAttributes()) {
                foreach ($ctl->attributes as $attr) {
                    $name = $attr->nodeName;
                    $value = $attr->nodeValue;
                    $texto .= " $name : $value";
                }
            }
        }
        if (isset($ref)) {
            if ($ref->hasAttributes()) {
                foreach ($ref->attributes as $attr) {
                    $name = $attr->nodeName;
                    $value = $attr->nodeValue;
                    $texto .= " $name : $value";
                }
            }
        }
        //grupo CADATA infCpl
        $t = $dom->getElementsByTagName('transmissor')->item(0);
        $r = $dom->getElementsByTagName('receptor')->item(0);
        $versao = ! empty($dom->getElementsByTagName('versao')->item(0)->nodeValue) ?
            'Versao:'.$dom->getElementsByTagName('versao')->item(0)->nodeValue.' ' : '';
        $especieNF = ! empty($dom->getElementsByTagName('especieNF')->item(0)->nodeValue) ?
            'Especie:'.$dom->getElementsByTagName('especieNF')->item(0)->nodeValue.' ' : '';
        $fabEntrega = ! empty($dom->getElementsByTagName('fabEntrega')->item(0)->nodeValue) ?
            'Entrega:'.$dom->getElementsByTagName('fabEntrega')->item(0)->nodeValue.' ' : '';
        $dca = ! empty($dom->getElementsByTagName('dca')->item(0)->nodeValue) ?
            'dca:'.$dom->getElementsByTagName('dca')->item(0)->nodeValue.' ' : '';
        $texto .= "".$versao.$especieNF.$fabEntrega.$dca;
        if (isset($t)) {
            if ($t->hasAttributes()) {
                $texto .= " Transmissor ";
                foreach ($t->attributes as $attr) {
                    $name = $attr->nodeName;
                    $value = $attr->nodeValue;
                    $texto .= " $name : $value";
                }
            }
        }
        if (isset($r)) {
            if ($r->hasAttributes()) {
                $texto .= " Receptor ";
                foreach ($r->attributes as $attr) {
                    $name = $attr->nodeName;
                    $value = $attr->nodeValue;
                    $texto .= " $name : $value";
                }
            }
        }
        return $texto;
    }

    /**
     * str2Hex
     * Converte string para haxadecimal ASCII
     *
     * @param  string $str
     * @return string
     */
    protected static function str2Hex($str)
    {
        if ($str == '') {
            return '';
        }
        $hex = "";
        $iCount = 0;
        do {
            // $hex .= sprintf("%02x", ord($str{$iCount}));

            $iCount++;
        } while ($iCount < strlen($str));
        return $hex;
    }//fim str2Hex

    protected static function getCardName($tBand)
    {
        switch ($tBand) {
            case '01':
                $tBandNome = 'VISA';
                break;
            case '02':
                $tBandNome = 'MASTERCARD';
                break;
            case '03':
                $tBandNome = 'AMERICAM EXPRESS';
                break;
            case '04':
                $tBandNome = 'SOROCRED';
                break;
            case '99':
                $tBandNome = 'OUTROS';
                break;
            default:
                $tBandNome = '';
        }
        return $tBandNome;
    }

    /**
     * hex2Str
     * Converte hexadecimal ASCII para string
     *
     * @param  string $str
     * @return string
     */
    protected static function hex2Str($str)
    {
        if ($str == '') {
            return '';
        }
        $bin = "";
        $iCount = 0;
        do {
            // if(phpversion() < 8){
            // $bin .= chr(hexdec($str{$iCount}.$str{($iCount + 1)}));
            // }
            $iCount += 2;
        } while ($iCount < strlen($str));
        return $bin;
    }

    protected function makeQRCode(
        $chNFe,
        $url,
        $tpAmb,
        $cDest = '',
        $dhEmi = '',
        $vNF = '',
        $vICMS = '',
        $digVal = '',
        $idToken = '000001',
        $token = ''
    ) {
        $nVersao = '100';
        $dhHex = self::str2Hex($dhEmi);
        $digHex = self::str2Hex($digVal);
        $seq = '';
        $seq .= 'chNFe=' . $chNFe;
        $seq .= '&nVersao=' . $nVersao;
        $seq .= '&tpAmb=' . $tpAmb;
        if ($cDest != '') {
            $seq .= '&cDest=' . $cDest;
        }
        $seq .= '&dhEmi=' . strtolower($dhHex);
        $seq .= '&vNF=' . $vNF;
        $seq .= '&vICMS=' . $vICMS;
        $seq .= '&digVal=' . strtolower($digHex);
        $seq .= '&cIdToken=' . $idToken;
        //o hash code é calculado com o Token incluso
        $hash = sha1($seq.$token);
        $seq .= '&cHashQRCode='. strtoupper($hash);
        if (strpos($url, '?') === false) {
            $seq = $url.'?'.$seq;
        } else {
            $seq = $url.''.$seq;
        }
        return $seq;
    }

    protected function pNotaCancelada()
    {
        if (!isset($this->nfeProc)) {
            return false;
        }
        $cStat = $this->getTagValue($this->nfeProc, "cStat");
        return $cStat == '101' ||
            $cStat == '151' ||
            $cStat == '135' ||
            $cStat == '155';
    }

    protected function pNotaDenegada()
    {
        if (!isset($this->nfeProc)) {
            return false;
        }
        //NÃO ERA NECESSÁRIO ESSA FUNÇÃO POIS SÓ SE USA
        //1 VEZ NO ARQUIVO INTEIRO
        $cStat = $this->getTagValue($this->nfeProc, "cStat");
        return $cStat == '110' ||
            $cStat == '301' ||
            $cStat == '302';
    }
}
