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

        $empresaId = $this->empresa_id ?? session('user_logged')['empresa'] ?? session('user_logged')['empresa_id'] ?? 1;

        // FILTRO DE DATA (Abre sempre no dia atual por padrão)
        $dataInicial = $request->input('data_inicial', date('Y-m-d'));
        $dataFinal = $request->input('data_final', date('Y-m-d'));

        $query = Pesagem::withoutGlobalScopes()
            ->with(['fornecedor', 'tickets', 'veiculo'])
            ->where('empresa_id', $empresaId)
            ->whereIn('tipo', ['compra', 'Compra', 'COMPRA'])
            ->whereIn('status', ['concluído', 'Concluído', 'CONCLUÍDO', 'concluido', 'Concluido'])
            ->whereNotIn('id', function($subQuery) {
                $subQuery->select('pesagem_id')->from('pesagem_pagamentos');
            });

        // Aplica o filtro de data (usando a data de registro ou criação)
        $query->where(function($q) use ($dataInicial, $dataFinal) {
            $q->whereBetween(DB::raw('DATE(dt_registro)'), [$dataInicial, $dataFinal])
                ->orWhereBetween(DB::raw('DATE(created_at)'), [$dataInicial, $dataFinal]);
        });

        if ($this->filial_id !== null) {
            $query->where(function ($q) {
                $q->where('filial_id', $this->filial_id)
                    ->orWhereNull('filial_id');
            });
        }

        $pesagensPendentes = $query->orderBy('id', 'desc')->get();

        // 1. Busca CNPJ da Empresa nas configurações
        $configNota = DB::table('config_notas')->where('empresa_id', $empresaId)->first();
        $cnpjEmpresa = $configNota ? preg_replace('/[^0-9]/', '', $configNota->cnpj) : '';

        $listaParaPagamento = [];

        foreach ($pesagensPendentes as $pesagem) {
            $fornecedor = $pesagem->fornecedor;
            $tabelaPrecoId = $fornecedor ? $fornecedor->tabela_preco_id : null;

            $valorTotalPesagem = 0;
            $valorKgExibicao = 0;
            $precosEncontrados = true;

            // 2. REGRA INTELIGENTE DE FRETE (COLETA VS ENTREGA)
            $tipoFrete = 'ENTREGA';
            if ($pesagem->veiculo && !empty($pesagem->veiculo->proprietario_documento)) {
                $docVeiculo = preg_replace('/[^0-9]/', '', $pesagem->veiculo->proprietario_documento);
                if ($docVeiculo === $cnpjEmpresa && $docVeiculo !== '00000000000000' && $docVeiculo !== '00000000000') {
                    $tipoFrete = 'COLETA';
                }
            }

            // 3. CALCULA O PESO LÍQUIDO FINAL EXATO DA PESAGEM
            $pesoEntrada = $pesagem->tickets->whereIn('tipo', ['entrada', 'avulsa'])->sum('peso');
            $pesoSaida = $pesagem->tickets->where('tipo', 'saida')->sum('peso');
            $pesoBag = $pesagem->tickets->sum('peso_bag');

            $pesoLiqBalanca = abs($pesoEntrada - $pesoSaida);
            if ($pesoLiqBalanca == 0) $pesoLiqBalanca = $pesoEntrada > 0 ? $pesoEntrada : $pesoSaida;
            $pesoLiqBalanca = max(0, $pesoLiqBalanca - $pesoBag);

            $percentualAbatimento = 0;
            if ($pesagem->danificado)   { $percentualAbatimento += (float) $pesagem->danificado_desconto; }
            if ($pesagem->quebrado)     { $percentualAbatimento += (float) $pesagem->quebrado_desconto; }
            if ($pesagem->esverdeado)   { $percentualAbatimento += (float) $pesagem->esverdeado_desconto; }
            if ($pesagem->ardido)       { $percentualAbatimento += (float) $pesagem->ardido_desconto; }
            if ($pesagem->secagem)      { $percentualAbatimento += (float) $pesagem->secagem_desconto; }
            $percentualAbatimento += (float) ($pesagem->umidade_desconto ?? 0);
            $percentualAbatimento += (float) ($pesagem->impureza_desconto ?? 0);

            $pesoFinalAposImpureza = max(0, $pesoLiqBalanca - ($pesoLiqBalanca * ($percentualAbatimento / 100)));

            if ($pesoFinalAposImpureza > 0) {
                $ticketRef = $pesagem->tickets->first();
                if ($ticketRef) {
                    // Busca Preço
                    $precoItem = DB::table('tabela_preco_itens')
                        ->where('tabela_preco_id', $tabelaPrecoId)
                        ->where('produto_id', $ticketRef->produto_id)
                        ->where('tipo_frete', $tipoFrete)
                        ->value('valor_kg');

                    if ($precoItem) {
                        $valorKgExibicao = (float) $precoItem;
                        $valorTotalPesagem = $pesoFinalAposImpureza * $valorKgExibicao;
                    } else {
                        // Tenta buscar no cadastro padrão de produtos caso não tenha na tabela de preços
                        $produtoBase = DB::table('produtos')->where('id', $ticketRef->produto_id)->first();
                        if($produtoBase && $produtoBase->valor_compra > 0){
                            $valorKgExibicao = (float) $produtoBase->valor_compra;
                            $valorTotalPesagem = $pesoFinalAposImpureza * $valorKgExibicao;
                        } else {
                            $precosEncontrados = false;
                        }
                    }
                }
            } else {
                $precosEncontrados = false;
            }

            $listaParaPagamento[] = (object)[
                'id' => $pesagem->id,
                'data' => $pesagem->dt_registro ?: $pesagem->created_at,
                'fornecedor_nome' => $fornecedor->razao_social ?? 'Sem Fornecedor',
                'chave_pix' => $fornecedor->pix ?? '',
                'peso_total' => $pesoFinalAposImpureza,
                'frete_usado' => $tipoFrete,
                'valor_kg' => $valorKgExibicao,
                'valor_calculado' => $valorTotalPesagem,
                'tabela_ok' => (($tabelaPrecoId && $precosEncontrados) || $precosEncontrados) && $pesoFinalAposImpureza > 0
            ];
        }

        return view('pagamento_lote.index', compact('listaParaPagamento', 'title', 'dataInicial', 'dataFinal'));
    }

    public function gerarArquivoPix(Request $request)
    {
        $ids = $request->input('pesagens_ids');

        if (empty($ids)) {
            session()->flash('mensagem_erro', 'Selecione pelo menos uma pesagem para gerar o arquivo.');
            return redirect()->back();
        }

        $contaBancaria = \App\Models\ContaBancaria::where('padrao', 1)->first();
        if (!$contaBancaria) {
            session()->flash('mensagem_erro', 'Erro: Configure uma conta bancária como PADRÃO.');
            return redirect()->back();
        }

        $empresa = \App\Models\Empresa::find($contaBancaria->empresa_id);
        $agencia = $contaBancaria->agencia;
        $conta   = $contaBancaria->conta;

        $cnpjEmpresa = str_pad(preg_replace('/\D/', '', $empresa->cnpj), 14, '0', STR_PAD_LEFT);
        $agenciaLimpa = preg_replace('/\D/', '', $agencia);
        $contaLimpa   = preg_replace('/\D/', '', $conta);

        $contaSoNumero = substr($contaLimpa, 0, 5);
        $dac           = substr($contaLimpa, -1);

        $nomeEmpresa = str_pad(substr($empresa->nome, 0, 30), 30, ' ', STR_PAD_RIGHT);

        $agenciaFormatada = str_pad($agenciaLimpa, 5, '0', STR_PAD_LEFT);
        $contaFormatada   = str_pad($contaSoNumero, 5, '0', STR_PAD_LEFT);
        $dacFormatado     = substr($dac, 0, 1);

        $linhas = [];

        // Header Arquivo
        $header = '041' . '0000' . '0' . str_repeat(' ', 9) . '2' . $cnpjEmpresa . str_repeat(' ', 20) . $agenciaFormatada . ' ' . str_pad($contaFormatada, 7, '0', STR_PAD_LEFT) . ' ' . $dacFormatado . $nomeEmpresa . str_pad('BANCO ITAU SA', 30, ' ', STR_PAD_RIGHT) . str_repeat(' ', 10) . '1' . date('dmY') . date('His') . str_repeat(' ', 9) . '00000' . '081';
        $linhas[] = str_pad($header, 240, ' ', STR_PAD_RIGHT);

        // Header Lote
        $headerLote = str_pad('041', 3, '0', STR_PAD_LEFT);
        $headerLote .= '00011C204501 2';
        $headerLote .= str_pad($cnpjEmpresa, 14, '0', STR_PAD_LEFT);
        $headerLote .= str_repeat(' ', 20);
        $headerLote .= str_pad($agenciaFormatada, 5, '0', STR_PAD_LEFT);
        $headerLote .= ' ';
        $headerLote .= str_pad($contaFormatada, 7, '0', STR_PAD_LEFT);
        $headerLote .= ' ';
        $headerLote .= $dacFormatado;
        $headerLote .= str_pad($nomeEmpresa, 30, ' ', STR_PAD_RIGHT);

        $linhas[] = str_pad($headerLote, 240, ' ', STR_PAD_RIGHT);

        $pesagens = \App\Models\Pesagem::with(['fornecedor'])->whereIn('id', $ids)->get();

        $totalPagarGeral = 0;
        $sequencial = 1;

        foreach ($pesagens as $pesagem) {
            // Garante que o arquivo PIX receba os valores exatos confirmados na tela pelo usuário
            $valorDoRequest = $request->input("valores.{$pesagem->id}");

            if ($valorDoRequest && $valorDoRequest > 0) {
                $valorTotalPesagem = (float) $valorDoRequest;
            } else {
                continue;
            }

            $totalPagarGeral += $valorTotalPesagem;
            $valorFormatado = str_pad(number_format($valorTotalPesagem, 2, '', ''), 15, '0', STR_PAD_LEFT);
            $nomeFavorecido = str_pad(substr($pesagem->fornecedor->razao_social, 0, 30), 30, ' ', STR_PAD_RIGHT);
            $chavePix = str_pad(substr($pesagem->fornecedor->pix, 0, 60), 60, ' ', STR_PAD_RIGHT);

            $seqStr = str_pad($sequencial++, 5, '0', STR_PAD_LEFT);
            $segA = '041' . '0001' . '3' . $seqStr . 'A' . '000' . '000' . '000' . str_repeat(' ', 3) . '0000' . ' ' . '0000000' . ' ' . '0' . $nomeFavorecido . str_repeat(' ', 20) . date('dmY') . 'BRL' . str_repeat('0', 15) . $valorFormatado . str_repeat(' ', 20) . str_repeat('0', 8) . str_repeat(' ', 27) . '0' . str_repeat('0', 10);
            $linhas[] = str_pad($segA, 240, ' ', STR_PAD_RIGHT);

            $seqStrB = str_pad($sequencial++, 5, '0', STR_PAD_LEFT);
            $segB = '041' . '0001' . '3' . $seqStrB . 'B' . str_repeat(' ', 3) . '0' . str_repeat(' ', 113) . $chavePix . str_repeat(' ', 45);
            $linhas[] = str_pad($segB, 240, ' ', STR_PAD_RIGHT);
        }

        $qtdLinhasLote = str_pad(($sequencial + 1), 6, '0', STR_PAD_LEFT);
        $totalPagarStr = str_pad(number_format($totalPagarGeral, 2, '', ''), 18, '0', STR_PAD_LEFT);

        $trailerLote = '041' . '0001' . '5' . str_repeat(' ', 9) . str_pad(($sequencial + 1), 6, '0', STR_PAD_LEFT) . str_pad(number_format($totalPagarGeral, 2, '', ''), 18, '0', STR_PAD_LEFT) . str_repeat(' ', 207);
        $linhas[] = str_pad($trailerLote, 240, ' ', STR_PAD_RIGHT);

        // O trailer do Arquivo (registro 9) precisa ter 240 posições
        $trailerArquivo = '041' . '9999' . '9' . str_repeat(' ', 9) . '000001' . str_pad(($sequencial + 3), 6, '0', STR_PAD_LEFT) . str_repeat(' ', 217);
        $linhas[] = str_pad($trailerArquivo, 240, ' ', STR_PAD_RIGHT);

        // A geração do arquivo unindo com quebra de linha
        $conteudoTxt = implode("\r\n", $linhas) . "\r\n";
        $nomeArquivo = 'REM_PIX_ITAU_' . date('dmY_His') . '.rem';

        return response($conteudoTxt, 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="' . $nomeArquivo . '"',
        ]);
    }
}
