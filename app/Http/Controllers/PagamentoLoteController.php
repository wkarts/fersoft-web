<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pesagem;
use App\Models\Fornecedor;
use App\Models\PesagemPagamento;
use Illuminate\Support\Facades\DB;

class PagamentoLoteController extends BaseController
{
    public function __construct()
    {
        // Variáveis obrigatórias do BaseController
        $this->redirectPage = '/pagamento-lote';
        $this->formTitle    = 'Lote de Pagamentos';
        $this->listTitle    = 'Lote de {form_title}'; 
        
        parent::__construct();
    }

    protected function rules(): array { return []; }
    protected function messages(): array { return []; }

    public function index(Request $request)
    {
        $title = $this->formatString($this->listTitle, [
            'form_title' => $this->formTitle,
        ]);

        // 1. Blindagem do ID da Empresa
        $empresaId = $this->empresa_id ?? session('user_logged')['empresa'] ?? session('user_logged')['empresa_id'] ?? 1;

        // 2. Inicia a busca blindada (Ignora letras maiúsculas/minúsculas)
        $query = Pesagem::withoutGlobalScopes()
            ->with(['fornecedor', 'tickets', 'veiculo'])
            ->where('empresa_id', $empresaId)
            ->whereIn('tipo', ['compra', 'Compra', 'COMPRA'])
            ->whereIn('status', ['concluído', 'Concluído', 'CONCLUÍDO', 'concluido', 'Concluido'])
            ->whereNotIn('id', function($subQuery) {
                $subQuery->select('pesagem_id')->from('pesagem_pagamentos');
            });

        // 3. Aplica o filtro de Filial (Usando a inteligência do seu BaseController)
        if ($this->filial_id !== null) {
            $query->where(function ($q) {
                $q->where('filial_id', $this->filial_id)
                  ->orWhereNull('filial_id'); // Pega as da filial e as globais da empresa
            });
        }

        // Executa a busca no banco
        $pesagensPendentes = $query->orderBy('id', 'desc')->get();

        $listaParaPagamento = [];

        foreach ($pesagensPendentes as $pesagem) {
            $fornecedor = $pesagem->fornecedor;
            $tabelaPrecoId = $fornecedor ? $fornecedor->tabela_preco_id : null;
            
            $valorTotalPesagem = 0;
            $precosEncontrados = true;

            // Descobre se foi Coleta ou Entrega
            $tipoFrete = 'ENTREGA'; 
            if ($pesagem->veiculo && $pesagem->veiculo->empresa_id == $empresaId) {
                $tipoFrete = 'COLETA';
            }

            // Calcula os R$ baseados na Tabela de Preço
            foreach ($pesagem->tickets as $ticket) {
                if ($ticket->tipo == 'entrada' || $ticket->tipo == 'avulsa') {
                    $pesoLiq = max(0, $ticket->peso - $ticket->peso_bag);

                    $precoItem = DB::table('tabela_preco_itens')
                        ->where('tabela_preco_id', $tabelaPrecoId)
                        ->where('produto_id', $ticket->produto_id)
                        ->where('tipo_frete', $tipoFrete)
                        ->first();

                    if ($precoItem) {
                        $valorTotalPesagem += ($pesoLiq * $precoItem->valor_kg);
                    } else {
                        $precosEncontrados = false; 
                    }
                }
            }

            $listaParaPagamento[] = (object)[
                'id' => $pesagem->id,
                'data' => $pesagem->dt_registro ?: $pesagem->created_at,
                'fornecedor_nome' => $fornecedor->razao_social ?? 'Sem Fornecedor',
                'chave_pix' => $fornecedor->pix ?? '',
                'peso_total' => $pesagem->peso_liquido,
                'frete_usado' => $tipoFrete,
                'valor_calculado' => $valorTotalPesagem,
                'tabela_ok' => ($tabelaPrecoId && $precosEncontrados)
            ];
        }

        return view('pagamento_lote.index', [
            'listaParaPagamento' => $listaParaPagamento,
            'title' => $title
        ]);
    }
  
 public function gerarArquivoPix(Request $request)
{
    $ids = $request->input('pesagens_ids');

    if (empty($ids)) {
        session()->flash('mensagem_erro', 'Selecione pelo menos uma pesagem para gerar o arquivo.');
        return redirect()->back();
    }

    // 1. BUSCA DADOS BANCÁRIOS E DA EMPRESA
    $contaBancaria = \App\Models\ContaBancaria::where('padrao', 1)->first();
    if (!$contaBancaria) {
        session()->flash('mensagem_erro', 'Erro: Configure uma conta bancária como PADRÃO.');
        return redirect()->back();
    }

    $empresa = \App\Models\Empresa::find($contaBancaria->empresa_id);
    $agencia = $contaBancaria->agencia;
    $conta   = $contaBancaria->conta;

    // 2. LIMPEZA E FORMATAÇÃO (O que você estava na dúvida)
    $cnpjEmpresa = str_pad(preg_replace('/\D/', '', $empresa->cnpj), 14, '0', STR_PAD_LEFT);
    $agenciaLimpa = preg_replace('/\D/', '', $agencia); 
    $contaLimpa   = preg_replace('/\D/', '', $conta);   

    // Lógica Itaú: Conta (5 dígitos) + DAC (último dígito)
    $contaSoNumero = substr($contaLimpa, 0, 5); 
    $dac           = substr($contaLimpa, -1);   

    $nomeEmpresa = str_pad(substr($empresa->nome, 0, 30), 30, ' ', STR_PAD_RIGHT);

    // FORMATAÇÃO RÍGIDA PARA O PADRÃO ITAÚ SISPAG
    $agenciaFormatada = str_pad($agenciaLimpa, 5, '0', STR_PAD_LEFT); // vira 03214
    $contaFormatada   = str_pad($contaSoNumero, 5, '0', STR_PAD_LEFT); // vira 81886
    $dacFormatado     = substr($dac, 0, 1); // garante apenas 1 dígito: 0

    $linhas = [];
    
    // --- HEADER DO ARQUIVO (Ajustado com str_pad para garantir 240) ---
    $header = '041' . '0000' . '0' . str_repeat(' ', 9) . '2' . $cnpjEmpresa . str_repeat(' ', 20) . $agenciaFormatada . ' ' . str_pad($contaFormatada, 7, '0', STR_PAD_LEFT) . ' ' . $dacFormatado . $nomeEmpresa . str_pad('BANCO ITAU SA', 30, ' ', STR_PAD_RIGHT) . str_repeat(' ', 10) . '1' . date('dmY') . date('His') . str_repeat(' ', 9) . '00000' . '081';
    // Esta linha abaixo garante que o Header tenha exatamente 240 caracteres
    $linhas[] = str_pad($header, 240, ' ', STR_PAD_RIGHT);

    // --- HEADER DO LOTE (Montagem por Posição Fixa - Padrão Itaú) ---
    $headerLote = str_pad('041', 3, '0', STR_PAD_LEFT);    // 001-003: Banco
    $headerLote .= '0001';                                 // 004-007: Lote
    $headerLote .= '1';                                    // 008-008: Registro (Header Lote)
    $headerLote .= 'C';                                    // 009-009: Operação
    $headerLote .= '20';                                   // 010-011: Serviço
    $headerLote .= '45';                                   // 012-013: Forma Lançamento (PIX)
    $headerLote .= '01';                                   // 014-015: Layout Lote
    $headerLote .= ' ';                                    // 016-016: Reservado
    $headerLote .= '2';                                    // 017-017: Tipo Inscrição (2=CNPJ)
    $headerLote .= str_pad($cnpjEmpresa, 14, '0', STR_PAD_LEFT); // 018-031: CNPJ
    $headerLote .= str_repeat(' ', 20);                    // 032-051: Convênio (Vazio)
    $headerLote .= str_pad($agenciaFormatada, 5, '0', STR_PAD_LEFT); // 052-056: Agência
    $headerLote .= ' ';                                    // 057-057: Branco (O erro estava aqui!)
    $headerLote .= str_pad($contaFormatada, 7, '0', STR_PAD_LEFT);   // 058-064: Conta
    $headerLote .= ' ';                                    // 065-065: Branco
    $headerLote .= $dacFormatado;                          // 066-066: DAC
    $headerLote .= str_pad($nomeEmpresa, 30, ' ', STR_PAD_RIGHT);  // 067-096: Nome Empresa

    $linhas[] = str_pad($headerLote, 240, ' ', STR_PAD_RIGHT);


    // 3. BUSCA AS PESAGENS E GERA OS DETALHES (SEGMENTO A e B)
    $pesagens = \App\Models\Pesagem::with(['fornecedor.tabelaPreco', 'tickets'])
        ->whereIn('id', $ids)
        ->get();

    $totalPagarGeral = 0;
    $sequencial = 1;

    foreach ($pesagens as $pesagem) {
        $valorTotalPesagem = 0;
        $tabela = $pesagem->fornecedor->tabelaPreco;

        if($tabela) {
            foreach ($pesagem->tickets as $ticket) {
                $itemTabela = \App\Models\TabelaPrecoItem::where('tabela_preco_id', $tabela->id)
                    ->where('produto_id', $ticket->produto_id)
                    ->where('tipo_frete', 'COLETA')
                    ->first();

                if ($itemTabela) {
                    $valorTotalPesagem += ($ticket->peso * $itemTabela->valor_kg);
                }
            }
        }

        if ($valorTotalPesagem <= 0) continue;

        $totalPagarGeral += $valorTotalPesagem;
        $valorFormatado = str_pad(number_format($valorTotalPesagem, 2, '', ''), 15, '0', STR_PAD_LEFT);
        $nomeFavorecido = str_pad(substr($pesagem->fornecedor->razao_social, 0, 30), 30, ' ', STR_PAD_RIGHT);
        $chavePix = str_pad(substr($pesagem->fornecedor->pix, 0, 60), 60, ' ', STR_PAD_RIGHT);

        // SEGMENTO A
        $seqStr = str_pad($sequencial++, 5, '0', STR_PAD_LEFT);
        $segA = '041' . '0001' . '3' . $seqStr . 'A' . '000' . '000' . '000' . str_repeat(' ', 3) . '0000' . ' ' . '0000000' . ' ' . '0' . $nomeFavorecido . str_repeat(' ', 20) . date('dmY') . 'BRL' . str_repeat('0', 15) . $valorFormatado . str_repeat(' ', 20) . str_repeat('0', 8) . str_repeat(' ', 27) . '0' . str_repeat('0', 10);
        $linhas[] = str_pad($segA, 240, ' ', STR_PAD_RIGHT);

        // SEGMENTO B
        $seqStrB = str_pad($sequencial++, 5, '0', STR_PAD_LEFT);
        $segB = '041' . '0001' . '3' . $seqStrB . 'B' . str_repeat(' ', 3) . '0' . str_repeat(' ', 113) . $chavePix . str_repeat(' ', 45);
        $linhas[] = str_pad($segB, 240, ' ', STR_PAD_RIGHT);
    }

   // 4. FECHAMENTO (TRAILERS)
    $qtdLinhasLote = str_pad(($sequencial + 1), 6, '0', STR_PAD_LEFT);
    $totalPagarStr = str_pad(number_format($totalPagarGeral, 2, '', ''), 18, '0', STR_PAD_LEFT);

    // Trailer do Lote (Registro tipo 5)
    $trailerLote = '041' . '0001' . '5' . str_repeat(' ', 9) . $qtdLinhasLote . $totalPagarStr . str_repeat('0', 18);
    $linhas[] = str_pad($trailerLote, 240, ' ', STR_PAD_RIGHT);

    // Trailer do Arquivo (Registro tipo 9)
    $qtdLinhasArquivo = str_pad(($sequencial + 3), 6, '0', STR_PAD_LEFT);
    $trailerArquivo = '041' . '9999' . '9' . str_repeat(' ', 9) . '000001' . $qtdLinhasArquivo;
    $linhas[] = str_pad($trailerArquivo, 240, ' ', STR_PAD_RIGHT);
   
    // 5. DOWNLOAD
    $conteudoTxt = implode("\r\n", $linhas) . "\r\n";
    $nomeArquivo = 'REM_PIX_ITAU_' . date('dmY_His') . '.rem';

    return response((string) $conteudoTxt, 200, [
        'Content-Type' => 'text/plain',
        'Content-Disposition' => 'attachment; filename="' . $nomeArquivo . '"',
    ]);
}
  
}