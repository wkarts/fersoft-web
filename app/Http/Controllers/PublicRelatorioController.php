<?php

namespace App\Http\Controllers;

use App\Models\Pesagem;
use App\Models\ConfigNota;
use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use NFePHP\DA\Legacy\Pdf;
use Com\Tecnick\Barcode\Barcode;

class PublicRelatorioController extends Controller
{
    protected $pesagem;
    protected $pdf;
    protected $config;
    protected $logService;
    protected $larg = 80;
    protected $dpi = 203; // DPI padrão para impressoras térmicas
    protected $fontePadrao = 'Arial';
    protected $logomarca = '';
    protected $alturaBase = 80; // Altura mínima do PDF

    public function __construct()
    {
        $this->middleware('throttle:10,1');
    }

    /**
     * Helpers de cálculo (mantendo estrutura do controller)
     */
    protected function f($v): float
    {
        // força float não-negativo
        return max(0.0, (float)$v);
    }

    protected function absDiff(float $a, float $b): float
    {
        // sempre maior - menor (diferença absoluta)
        return ($a >= $b) ? ($a - $b) : ($b - $a);
    }

    protected function clamp(float $value, float $min, float $max): float
    {
        if ($value < $min) return $min;
        if ($value > $max) return $max;
        return $value;
    }

    /**
     * Gera o relatório completo de 80mm em PDF baseado no token da pesagem.
     */
    public function gerarRelatorio80mm(Request $request, $token)
    {
        try {
            // 🔹 Busca a pesagem pelo token, garantindo que esteja visível publicamente
            $pesagem = Pesagem::with(['tickets.produto', 'veiculo', 'motorista', 'fornecedor', 'cliente'])
                ->where('token', $token)
                ->where('view_public', 1)
                ->first();

            if (!$pesagem) {
                throw new \Exception("Pesagem com o token {$token} não encontrada ou não disponível publicamente.");
            }

            // 🔹 Carrega os dados da empresa associada à pesagem
            $dadosEmpresa = $this->carregarDadosEmpresa($pesagem->empresa_id);

            // 🔹 Calcula a altura dinâmica do relatório
            $alturaDinamica = $this->calculaAltura($pesagem);

            // 🔹 Configuração do PDF
            $this->pdf = new Pdf('P', 'mm', [$this->larg, $alturaDinamica]);
            $this->pdf->SetMargins(2, 2);
            $this->pdf->SetAutoPageBreak(false, 0);
            $this->pdf->SetFont('Arial', '', 9);
            $this->pdf->AddPage();

            // 🔹 Monta o relatório
            $this->montaCabecalho($pesagem, $dadosEmpresa);
            $this->montaConteudo($pesagem);
            $this->montaCarimbo($dadosEmpresa);
            $this->montaRodape($dadosEmpresa);

            // 🔹 Captura os dados antes da alteração para log
            $dadosAntes = $pesagem->toArray();

            // **Altera a visibilidade após a impressão para "não visível" (view_public = 0)**
            $pesagem->view_public = 0; // Altera para não visível
            $pesagem->save(); // Salva a alteração no banco de dados

            // 🔹 Captura os dados após a alteração para log
            $dadosDepois = $pesagem->toArray();

            // 🔹 Instancia manual do LogService com os IDs corretos
            $empresaId = $pesagem->empresa_id;
            $usuarioId = auth()->check() ? auth()->id() : null;
            $filialId = $pesagem->filial_id ?? null;

            $logService = new \App\Services\LogService($empresaId, $usuarioId, $filialId);

            // 🔹 Registra log da alteração da visibilidade
            $logService->registrar('get/update', PublicRelatorioController::class, [
                'registro_id' => $pesagem->id,
                'dados_antes' => $dadosAntes,
                'dados_depois' => $dadosDepois,
            ]);

            // 🔹 Retorna o PDF como resposta
            $output = $this->pdf->Output('', 'S'); // Gera o PDF como string
            return response($output)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="relatorio_80mm.pdf"');

        } catch (\Exception $e) {
            Log::error('Erro ao gerar o relatório.', [
                'token' => $token,
                'exception' => $e->getMessage()
            ]);

            session()->flash('mensagem_erro', "Erro ao gerar relatório: " . $e->getMessage());

            return redirect(env('PORTAL_URL'));
        }
    }

    /**
     * Carrega os dados da empresa a partir da tabela ConfigNota.
     */
    protected function carregarDadosEmpresa($empresaId)
    {
        try {
            $config = ConfigNota::where('empresa_id', $empresaId)->first();

            if (!$config) {
                throw new \Exception("Configuração da empresa não encontrada para o ID: {$empresaId}");
            }

            return $config->toArray();
        } catch (\Exception $e) {
            Log::error('Erro ao carregar dados da empresa.', [
                'empresa_id' => $empresaId,
                'exception' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Calcula a altura necessária para o PDF com base no conteúdo.
     *
     * @return float
     */
    protected function calculaAltura(Pesagem $pesagem)
    {
        // Cria um objeto temporário do PDF para cálculos
        $tempPdf = new Pdf('P', 'mm', [$this->larg, 1000]); // Altura inicial grande para cálculo
        $tempPdf->SetFont('Arial', '', 9); // Define a fonte padrão usada no PDF

        // Altura base mínima
        $altura = $this->alturaBase;

        // Incremento fixo para cabeçalho e rodapé
        $altura += 30; // Espaço reservado para o cabeçalho
        $altura += 20; // Espaço reservado para o rodapé

        // Informações principais da pesagem
        $informacoes = [
            'Pesagem ID: ' . $pesagem->id,
            'Veículo: ' . ($pesagem->veiculo->modelo ?? 'N/A'),
            'Placa Veículo: ' . ($pesagem->placa_veiculo ?? 'N/A'),
            'Placa Carreta: ' . ($pesagem->placa_carreta ?? 'N/A'),
            'Motorista: ' . ($pesagem->motorista_nome ?? 'N/A'),
            'Tipo: ' . ucfirst($pesagem->tipo ?? 'N/A'),
            'Status: ' . ucfirst($pesagem->status),
        ];

        // Calcula a altura das informações principais
        foreach ($informacoes as $info) {
            $larguraTexto = $tempPdf->GetStringWidth($info);
            $linhas = ceil($larguraTexto / ($this->larg - 4)); // Subtraindo margens de 2mm de cada lado
            $altura += $linhas * 5; // Cada linha ocupa 5mm
        }

        // Altura para os tickets agrupados
        $ticketsAgrupados = $pesagem->tickets->groupBy('produto_id');
        foreach ($ticketsAgrupados as $produtoId => $tickets) {
            // Adiciona altura para cabeçalho de cada produto
            $altura += 10;

            foreach ($tickets as $ticket) {
                $nomeProduto = $ticket->produto->nome ?? 'N/A';
                $larguraProduto = $tempPdf->GetStringWidth($nomeProduto);
                $linhasProduto = ceil($larguraProduto / ($this->larg - 4));
                $altura += $linhasProduto * 5; // Cada linha adicional ocupa 5mm
            }

            // Cada linha de ticket ocupa 5mm
            $altura += count($tickets) * 5;
        }

        // Incremento para cálculos gerais e campos adicionais
        $altura += 40; // Para peso bruto, líquido, descontos, e total final
        $altura += 50; // Espaço para carimbo e assinatura

        // Ajusta a altura mínima para evitar problemas de conteúdo pequeno
        return max($altura, $this->alturaBase);
    }

    /**
     * Adiciona o QR Code ao PDF.
     *
     * @param string $conteudo O conteúdo a ser codificado no QR Code
     * @param int $x Posição horizontal do QR Code no PDF
     * @param int|null $y Posição vertical do QR Code no PDF (caso não seja fornecido, usa o valor atual do Y)
     * @param int $largura Largura do QR Code
     * @param int $altura Altura do QR Code
     */
    protected function adicionaQRCode($conteudo, $x = 25, $y = null, $largura = 30, $altura = 30)
    {
        $barcode = new Barcode();

        // Gera o QR Code
        $qrCodeObj = $barcode->getBarcodeObj(
            'QRCODE,H',  // Tipo de código (QR Code, alta correção de erro)
            $conteudo,   // Conteúdo do QR Code
            -4,          // Largura
            -4,          // Altura
            'black',     // Cor do código
            [0, 0, 0, 0] // Margens
        )->setBackgroundColor('white'); // Cor de fundo

        // Obtém os dados da imagem PNG do QR Code
        $qrCodeImage = $qrCodeObj->getPngData();

        // Cria um arquivo temporário para o QR Code
        $tempPath = sys_get_temp_dir() . '/qrcode_' . uniqid() . '.png';
        safe_file_put_contents($tempPath, $qrCodeImage);

        // Adiciona a imagem do QR Code no PDF
        $this->pdf->Image($tempPath, $x, $y ?? $this->pdf->GetY(), $largura, $altura);

        // Remove o arquivo temporário
        @unlink($tempPath);
    }

    /**
     * Monta o cabeçalho do relatório.
     *
     * @param Pesagem $pesagem
     * @param array $dadosEmpresa
     */
    protected function montaCabecalho(Pesagem $pesagem, $dadosEmpresa)
    {
        $espacoEntreLogoEQRCode = 2; // Espaço fixo entre logo e QR Code
        $yQRCode = 2; // Posição inicial do QR Code

        // Verifica se há logo e a adiciona ao PDF
        if (!empty($dadosEmpresa['logo']) && file_exists(public_path('logos/' . $dadosEmpresa['logo']))) {
            // Obtém o tamanho da logo
            list($larguraLogo, $alturaLogo) = getimagesize(public_path('logos/' . $dadosEmpresa['logo']));

            // Define a largura desejada para a logo no PDF (60mm)
            $larguraLogoNoPDF = 60;
            $escalaAltura = $alturaLogo / $larguraLogo;

            // Calcula a altura proporcional no PDF
            $alturaLogoNoPDF = $larguraLogoNoPDF * $escalaAltura;

            // Adiciona a imagem da logo ao PDF
            $this->pdf->Image(public_path('logos/' . $dadosEmpresa['logo']), 10, 2, $larguraLogoNoPDF);

            // Ajusta a posição do QR Code com base na altura da logo
            $yQRCode += $alturaLogoNoPDF + $espacoEntreLogoEQRCode;

            // Adiciona espaço após a logo
            $this->pdf->Ln($alturaLogoNoPDF);
        } else {
            $this->pdf->SetFont('Arial', 'B', 10);
            // Se não houver logo, exibe a razão social
            $this->pdf->Cell(0, 10, mb_convert_encoding($dadosEmpresa['razao_social'] ?? 'Empresa Não Configurada', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

            // Ajusta o espaço superior quando não há logo
            $yQRCode += 20;
        }

        // Adiciona o QR Code com o token, ajustando a posição dinâmica
        $this->adicionaQRCode(env('URL_PESAGEM_TOKEN') . '/getTicket/withToken/relPrn80mm/' . $pesagem->token, 25, $yQRCode);

        $this->pdf->Ln(30); // Espaço adicional após o QR Code

        // Adiciona o ticket no cabeçalho
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Ln(2);
        $this->pdf->Cell(0, 5, utf8_decode('Ticket: ' . $pesagem->token), 0, 1, 'C');
        $this->pdf->Ln(2);
    }

    protected function montaConteudo(Pesagem $pesagem)
    {
        // Fonte e tamanho para o conteúdo principal
        $this->pdf->SetFont('Courier', 'B', 10);

        // Exibe o status e o ID da pesagem
        $this->pdf->Cell(0, 5, mb_convert_encoding('Status: ' . ucfirst($pesagem->status), 'ISO-8859-1', 'UTF-8'), 0, 1);
        $this->pdf->Cell(0, 5, mb_convert_encoding('Pesagem ID: ' . $pesagem->id, 'ISO-8859-1', 'UTF-8'), 0, 1);

        // Fontes para detalhes da pesagem
        $this->pdf->SetFont('Courier', 'BI', 8);

        // Detalhes do veículo e motorista
        $this->pdf->Cell(0, 5, mb_convert_encoding('Veículo: ' . ($pesagem->veiculo->modelo ?? 'N/A'), 'ISO-8859-1', 'UTF-8'), 0, 1);
        $this->pdf->Cell(0, 5, mb_convert_encoding('Placa Veículo: ' . ($pesagem->placa_veiculo ?? 'N/A'), 'ISO-8859-1', 'UTF-8'), 0, 1);
        $this->pdf->Cell(0, 5, mb_convert_encoding('Placa Carreta: ' . ($pesagem->placa_carreta ?? 'N/A'), 'ISO-8859-1', 'UTF-8'), 0, 1);
        $this->pdf->Cell(0, 5, mb_convert_encoding('Motorista: ' . ($pesagem->motorista_nome ?? 'N/A'), 'ISO-8859-1', 'UTF-8'), 0, 1);

        // Tipo da pesagem
        $tipo = $pesagem->tipo ?? 'N/A';
        $this->pdf->Cell(0, 5, mb_convert_encoding('Tipo: ' . ucfirst($tipo), 'ISO-8859-1', 'UTF-8'), 0, 1);

        // Informações adicionais para compra ou venda
        $this->pdf->SetFont('Arial', 'I', 7);
        $this->pdf->Ln(2);
        if ($tipo === 'compra') {
            $fornecedor = $pesagem->fornecedor->razao_social ?? 'Fornecedor não informado';
            $this->pdf->Cell(0, 4, mb_convert_encoding('Fornecedor: ', 'ISO-8859-1', 'UTF-8'), 0, 1);
            $this->pdf->Ln(0);
            $this->pdf->Cell(0, 4, mb_convert_encoding($fornecedor, 'ISO-8859-1', 'UTF-8'), 0, 1);
        } elseif ($tipo === 'venda') {
            $cliente = $pesagem->cliente->razao_social ?? 'Cliente não informado';
            $this->pdf->Cell(0, 4, mb_convert_encoding('Cliente: ', 'ISO-8859-1', 'UTF-8'), 0, 1);
            $this->pdf->Ln(0);
            $this->pdf->Cell(0, 4, mb_convert_encoding($cliente, 'ISO-8859-1', 'UTF-8'), 0, 1);
        }

        /**
         * ======================
         * CÁLCULO ROBUSTO (SEM NEGATIVOS / SEM ESTOURO)
         * ======================
         */
        $entradas = $pesagem->tickets->where('tipo', 'entrada')->sum(function ($t) {
            return $this->f($t->peso);
        });
        $saidas   = $pesagem->tickets->where('tipo', 'saida')->sum(function ($t) {
            return $this->f($t->peso);
        });
        $avulsas  = $pesagem->tickets->where('tipo', 'avulsa')->sum(function ($t) {
            return $this->f($t->peso);
        });
        $bags     = $pesagem->tickets->sum(function ($t) {
            return $this->f($t->peso_bag);
        });

        $baseEntradas = $entradas + $avulsas;

        // diferença absoluta entre (entradas + avulsas) e saídas
        $pesoLiquido = $this->absDiff($baseEntradas, $saidas);

        // somatório de percentuais (sempre sobre base não-negativa)
        $pct = 0.0;
        if (!empty($pesagem->danificado)) $pct += $this->f($pesagem->danificado_desconto);
        if (!empty($pesagem->quebrado))   $pct += $this->f($pesagem->quebrado_desconto);
        if (!empty($pesagem->esverdeado)) $pct += $this->f($pesagem->esverdeado_desconto);
        if (!empty($pesagem->ardido))     $pct += $this->f($pesagem->ardido_desconto);
        if (!empty($pesagem->secagem))    $pct += $this->f($pesagem->secagem_desconto);
        $pct += $this->f($pesagem->umidade_desconto);
        $pct += $this->f($pesagem->impureza_desconto);

        // descontos percentuais calculados sobre o líquido não-negativo
        $descontosPercentuais = ($pesoLiquido > 0) ? ($pesoLiquido * ($pct / 100.0)) : 0.0;

        // soma de bags + percentuais, porém NUNCA acima do pesoLiquido
        $descontos = $this->clamp($bags + $descontosPercentuais, 0.0, $pesoLiquido);

        // Peso final nunca negativo
        $pesoFinal = $this->clamp($pesoLiquido - $descontos, 0.0, $pesoLiquido);

        // Para exibição adicional (mantendo seu rótulo original)
        $pesoBrutoPorPesagem = $this->f($baseEntradas);

        // Caixa Resumo
        $this->pdf->Ln(2); // Espaçamento antes da caixa
        $this->pdf->SetFont('Arial', 'I', 7);
        $this->pdf->Cell(0, 7, '---------------------------------------Resumo------------------------------------------', 0, 1, 'C');

        // Desenha a borda da caixa
        $this->pdf->Cell(0, 30, '', 1, 1); // Cria uma célula de altura 30mm com borda

        // Configura a posição inicial dentro da caixa
        $startX = $this->pdf->GetX() + 0;
        $startY = $this->pdf->GetY() - 30;

        $this->pdf->SetXY($startX, $startY + 0);

        // Exibição dos cálculos gerais
        $this->pdf->Ln(0);
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell(0, 5, mb_convert_encoding('Peso Bruto Total (Pesagem): ' . number_format($pesoBrutoPorPesagem, 2, ',', '.') . ' kg', 'ISO-8859-1', 'UTF-8'), 0, 1);
        $this->pdf->Cell(0, 5, mb_convert_encoding('Total Entrada: ' . number_format($entradas, 2, ',', '.') . ' kg', 'ISO-8859-1', 'UTF-8'), 0, 1);
        $this->pdf->Cell(0, 5, mb_convert_encoding('Total Saída: ' . number_format($saidas, 2, ',', '.') . ' kg', 'ISO-8859-1', 'UTF-8'), 0, 1);
        $this->pdf->Cell(0, 5, mb_convert_encoding('Peso Líquido: ' . number_format($pesoLiquido, 2, ',', '.') . ' kg', 'ISO-8859-1', 'UTF-8'), 0, 1);
        $this->pdf->Cell(0, 5, mb_convert_encoding('Descontos: ' . number_format($descontos, 2, ',', '.') . ' kg', 'ISO-8859-1', 'UTF-8'), 0, 1);
        $this->pdf->Cell(0, 5, mb_convert_encoding('Peso Final: ' . number_format($pesoFinal, 2, ',', '.') . ' kg', 'ISO-8859-1', 'UTF-8'), 0, 1);

        // Lista de Tickets
        $this->pdf->Ln(2);
        $this->pdf->SetFont('Arial', 'I', 7);
        $this->pdf->Cell(0, 5, '--------------------------------Tickets / Pesagens-----------------------------------', 0, 1, 'C');

        // Detalhes dos produtos
        $ticketsAgrupados = $pesagem->tickets->groupBy('produto_id');
        if ($ticketsAgrupados->isEmpty()) {
            $this->pdf->Cell(0, 5, mb_convert_encoding('Nenhum ticket encontrado!', 'ISO-8859-1', 'UTF-8'), 0, 1);
            return;
        }

        foreach ($ticketsAgrupados as $produtoId => $tickets) {
            $produto = $tickets->first()->produto ?? null;

            // Pesos por produto (com avulsas e diferença absoluta)
            $entradaProduto = (float) $tickets->where('tipo', 'entrada')->sum('peso');
            $saidaProduto   = (float) $tickets->where('tipo', 'saida')->sum('peso');
            $avulsaProduto  = (float) $tickets->where('tipo', 'avulsa')->sum('peso');

            $entradaProduto = $this->f($entradaProduto);
            $saidaProduto   = $this->f($saidaProduto);
            $avulsaProduto  = $this->f($avulsaProduto);

            $brutoProduto   = $entradaProduto + $avulsaProduto;
            $liqProduto     = $this->absDiff($brutoProduto, $saidaProduto); // diferença absoluta

            // Caixa do produto
            $this->pdf->Ln(0);
            $this->pdf->SetFont('Arial', 'I', 7);
            $this->pdf->Cell(0, 2, '', 0, 1, 'C');

            $this->pdf->Cell(0, 20, '', 1, 1);

            $startX = $this->pdf->GetX() + 0;
            $startY = $this->pdf->GetY() - 20;
            $this->pdf->SetXY($startX, $startY + 0);

            $this->pdf->Ln(0);
            $this->pdf->SetFont('Arial', '', 7);
            $this->pdf->Cell(0, 4, mb_convert_encoding('Produto: ' . ($produto->nome ?? 'Não informado'), 'ISO-8859-1', 'UTF-8'), 0, 1);
            $this->pdf->Cell(0, 4, mb_convert_encoding('Peso Bruto: ' . number_format($brutoProduto, 2, ',', '.') . ' kg', 'ISO-8859-1', 'UTF-8'), 0, 1);
            $this->pdf->Cell(0, 4, mb_convert_encoding('Entrada: ' . number_format($entradaProduto, 2, ',', '.') . ' kg', 'ISO-8859-1', 'UTF-8'), 0, 1);
            $this->pdf->Cell(0, 4, mb_convert_encoding('Saída: ' . number_format($saidaProduto, 2, ',', '.') . ' kg', 'ISO-8859-1', 'UTF-8'), 0, 1);
            // mantém a métrica "recipiente" como no relatório impresso (total de descontos aplicados)
            $this->pdf->Cell(0, 4, mb_convert_encoding('Recipiente de Pesagem: ' . number_format($descontos, 2, ',', '.') . ' kg', 'ISO-8859-1', 'UTF-8'), 0, 1);
            $this->pdf->Cell(0, 4, mb_convert_encoding('Peso Líquido: ' . number_format($liqProduto, 2, ',', '.') . ' kg', 'ISO-8859-1', 'UTF-8'), 0, 1);

            // Tabela de tickets por produto
            $this->pdf->Cell(25, 4, mb_convert_encoding('ID', 'ISO-8859-1', 'UTF-8'), 1, 0);
            $this->pdf->Cell(10, 4, mb_convert_encoding('Tipo', 'ISO-8859-1', 'UTF-8'), 1, 0);
            $this->pdf->Cell(25, 4, mb_convert_encoding('Peso (kg)', 'ISO-8859-1', 'UTF-8'), 1, 0);
            $this->pdf->Cell(16, 4, mb_convert_encoding('Data', 'ISO-8859-1', 'UTF-8'), 1, 1);

            foreach ($tickets as $ticket) {
                $this->pdf->Cell(25, 5, $ticket->id, 1, 0);
                $this->pdf->Cell(10, 5, ucfirst($ticket->tipo), 1, 0);
                $this->pdf->Cell(25, 5, number_format($this->f($ticket->peso), 2, ',', '.'), 1, 0);
                $this->pdf->Cell(16, 5, $ticket->created_at->format('d/m/Y'), 1, 1);
            }
        }

        $this->pdf->Ln(5);
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(0, 5, mb_convert_encoding('Peso Total Geral: ' . number_format($pesoFinal, 2, ',', '.') . ' kg', 'ISO-8859-1', 'UTF-8'), 0, 1);
    }

    /**
     * Monta a caixa para carimbo ou assinatura no rodapé.
     *
     * @param array $dadosEmpresa Dados da empresa para exibição
     */
    protected function montaCarimbo($dadosEmpresa)
    {
        // Fonte e tamanho para o rodapé
        $this->pdf->SetFont('Arial', 'I', 7);

        // Espaçamento antes da caixa de carimbo ou assinatura
        $this->pdf->Ln(2);

        // Exibe a linha com o título "CARIMBO"
        $this->pdf->Cell(0, 6, '---------------------------------------CARIMBO------------------------------------------', 0, 1, 'C');

        // Desenha a borda da caixa (uma célula de 20mm de altura)
        $this->pdf->Cell(0, 20, '', 1, 1); // Borda

        // Configura a posição inicial dentro da caixa para a razão social
        $startX = $this->pdf->GetX() + 0; // Margem interna de 2mm
        $startY = $this->pdf->GetY() - 20; // Altura da caixa (considerando a altura da borda)

        $this->pdf->SetXY($startX, $startY + 0);

        // Exibe as informações da empresa
        $this->pdf->Cell(0, 5, mb_convert_encoding('Razão Social: ' . ($dadosEmpresa['razao_social'] ?? 'N/A'), 'ISO-8859-1', 'UTF-8'), 0, 1);

        // Exibe o CNPJ e IE em uma linha
        $this->pdf->Cell(50, 5, mb_convert_encoding('CNPJ: ' . ($dadosEmpresa['cnpj'] ?? 'N/A'), 'ISO-8859-1', 'UTF-8'), 0, 0);
        $this->pdf->Cell(50, 5, mb_convert_encoding('IE: ' . ($dadosEmpresa['ie'] ?? 'N/A'), 'ISO-8859-1', 'UTF-8'), 0, 1);

        // Exibe o município e UF em uma linha
        $this->pdf->Cell(50, 5, mb_convert_encoding('Município: ' . ($dadosEmpresa['municipio'] ?? 'N/A'), 'ISO-8859-1', 'UTF-8'), 0, 0);
        $this->pdf->Cell(50, 5, mb_convert_encoding('UF: ' . ($dadosEmpresa['uf'] ?? 'N/A'), 'ISO-8859-1', 'UTF-8'), 0, 1);

        // Exibe o e-mail e telefone em uma linha
        $this->pdf->Cell(50, 5, mb_convert_encoding('E-mail: ' . ($dadosEmpresa['email'] ?? 'N/A'), 'ISO-8859-1', 'UTF-8'), 0, 0);
        $this->pdf->Cell(50, 5, mb_convert_encoding('Fone: ' . ($dadosEmpresa['fone'] ?? 'N/A'), 'ISO-8859-1', 'UTF-8'), 0, 1);

        // Campo para a assinatura (linha para assinatura)
        $this->pdf->SetFont('Arial', '', 9);
        $this->pdf->Ln(5); // Espaçamento antes do campo de assinatura
        $this->pdf->Cell(0, 5, '___________________________________________', 0, 1, 'C'); // Linha de assinatura
        $this->pdf->Cell(0, 5, 'Assinatura', 0, 1, 'C'); // Texto "Assinatura"
    }

    /**
     * Monta o rodapé do relatório.
     *
     * @param array $dadosEmpresa
     */
    protected function montaRodape($dadosEmpresa)
    {
        // Define a fonte para o rodapé
        $this->pdf->SetFont('Arial', 'i', 8);

        // Adiciona uma linha em branco
        $this->pdf->Ln(5);

        // Exibe informações de contato de suporte
        $this->pdf->Cell(0, 4, mb_convert_encoding(
            env('SITE_SUPORTE', 'Suporte Técnico: contato@empresa.com') . ' | ' .
            env('EMAIL_SUPORTE', 'Suporte Técnico'),
            'ISO-8859-1', 'UTF-8'
        ), 0, 1, 'C');

        // Adiciona um pequeno espaço após as informações de contato
        $this->pdf->Ln(3);

        // Exibe a data de emissão no canto direito
        $this->pdf->SetFont('Arial', 'I', 7);
        $this->pdf->Cell(0, 5, utf8_decode('Emitido em: ') . utf8_decode(now()->format('d/m/Y H:i')), 0, 1, 'R');

        // Adiciona um pequeno espaço após a data
        $this->pdf->Ln(4);

        // Verifica se há logo configurada e a adiciona ao rodapé
        if (!empty($dadosEmpresa['logo']) && file_exists(public_path('logos/' . $dadosEmpresa['logo']))) {
            $this->pdf->Image(public_path('logos/' . $dadosEmpresa['logo']), 25, $this->pdf->GetY(), 30);
        }
    }

    protected function getStringWidth($texto)
    {
        $tempPdf = new Pdf('P', 'mm', [$this->larg, 1000]);
        $tempPdf->SetFont('Arial', '', 9);
        return $tempPdf->GetStringWidth($texto);
    }

    protected function adicionaLogo($logo)
    {
        $caminhoLogo = public_path('logos/' . $logo);
        list($larguraLogo, $alturaLogo) = getimagesize($caminhoLogo);
        $alturaLogoNoPDF = 60 * ($alturaLogo / $larguraLogo);
        $this->pdf->Image($caminhoLogo, 10, 2, 60);
        $this->pdf->Ln($alturaLogoNoPDF);
    }

}
