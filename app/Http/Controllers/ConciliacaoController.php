<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BankStatementTransaction;
use App\Models\ContaEmpresa;
use App\Models\ContaPagar;
use App\Models\ContaReceber;
use App\Models\ItemContaEmpresa;
use App\Models\ConciliacaoRegra;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ConciliacaoController extends BaseController
{
    public function __construct()
    {
        $this->model        = BankStatementTransaction::class;
        $this->formTitle    = 'Conciliação Bancária';
        $this->redirectPage = '/financeiro/conciliacao/list';
        $this->listView     = 'financeiro.conciliacao.list';

        parent::__construct();
    }

    public function rules(): array { return []; }
    public function messages(): array { return []; }

    private function getEmpresaId()
    {
        $sessao = session('user_logged', []);
        return $sessao['empresa_id'] ?? ($sessao['empresa'] ?? 1);
    }

    private function getUsuarioId()
    {
        return session('user_logged')['id'] ?? 1;
    }

    private function getFilialId()
    {
        $filialId = $this->filial_id ?? (session('user_logged')['local_padrao'] ?? null);
        if (in_array($filialId, [0, '0', -1, '-1', 'null', ''], true)) {
            return null;
        }
        return $filialId;
    }

    public function list(Request $request)
    {
        $empresaId = $this->getEmpresaId();

        $contasBancarias = ContaEmpresa::where('empresa_id', $empresaId)->where('status', 1)->get();
        $categorias      = DB::table('categoria_contas')->where('empresa_id', $empresaId)->get();
        $fornecedores    = DB::table('fornecedors')->where('empresa_id', $empresaId)->get();
        $clientes        = DB::table('clientes')->where('empresa_id', $empresaId)->get();

        $dataLimite = Carbon::now()->subDays(60)->format('Y-m-d');
        $filialId = $this->getFilialId();

        $contasPagarSugestao = DB::table('conta_pagars')
            ->leftJoin('fornecedors', 'conta_pagars.fornecedor_id', '=', 'fornecedors.id')
            ->select('conta_pagars.*', 'fornecedors.razao_social as nome_parceiro')
            ->where('conta_pagars.empresa_id', $empresaId)
            ->when($filialId, function($q) use ($filialId) {
                return $q->where('conta_pagars.filial_id', $filialId); // TRAVA DE FILIAL
            })
            ->where(function($q) use ($dataLimite) {
                $q->where('conta_pagars.status', 0)
                    ->orWhere('conta_pagars.data_vencimento', '>=', $dataLimite);
            })->get();

        $contasReceberSugestao = DB::table('conta_recebers')
            ->leftJoin('clientes', 'conta_recebers.cliente_id', '=', 'clientes.id')
            ->select('conta_recebers.*', 'clientes.razao_social as nome_parceiro')
            ->where('conta_recebers.empresa_id', $empresaId)
            ->when($filialId, function($q) use ($filialId) {
                return $q->where('conta_recebers.filial_id', $filialId); // TRAVA DE FILIAL
            })
            ->where(function($q) use ($dataLimite) {
                $q->where('conta_recebers.status', 0)
                    ->orWhere('conta_recebers.data_vencimento', '>=', $dataLimite);
            })->get();

        $query = BankStatementTransaction::withoutGlobalScopes()->where('empresa_id', $empresaId);

        if ($request->filled('conta_filtro')) {
            $query->where('conta_bancaria_id', $request->conta_filtro);
        }
        if ($request->filled('data_inicio')) {
            $query->where('data_transacao', '>=', $request->data_inicio);
        }
        if ($request->filled('data_fim')) {
            $query->where('data_transacao', '<=', $request->data_fim);
        }
        if ($request->filled('status_filtro')) {
            $query->where('status', $request->status_filtro);
        } else {
            $query->where('status', 'pending');
        }

        $records = $query->orderBy('data_transacao', 'desc')->get();

        foreach ($records as $extrato) {
            $extrato->achou_pendente = false;
            $extrato->achou_pago = false;

            $listaBusca = ($extrato->tipo === 'debit') ? $contasPagarSugestao : $contasReceberSugestao;
            $descBanco = strtoupper($extrato->descricao);

            foreach ($listaBusca as $titulo) {
                if (abs((float)$titulo->valor_integral - (float)$extrato->valor) < 0.01) {
                    $nomeFornecedor = trim(strtoupper($titulo->nome_parceiro ?? ''));
                    $nomeValido = false;

                    if (!empty($nomeFornecedor)) {
                        $primeiraPalavra = explode(' ', $nomeFornecedor)[0];
                        if (strlen($primeiraPalavra) > 2 && strpos($descBanco, $primeiraPalavra) !== false) {
                            $nomeValido = true;
                        }
                    }

                    if ($nomeValido) {
                        if ($titulo->status == 1) {
                            $extrato->achou_pago = true;
                        } else {
                            $extrato->achou_pendente = true;
                        }
                        break;
                    }
                }
            }
        }


        $resumo = null;
        if ($request->filled('conta_filtro') && $request->filled('data_inicio') && $request->filled('data_fim')) {
            $contaResumo = ContaEmpresa::query()
                ->where('empresa_id', $empresaId)
                ->where('id', (int) $request->conta_filtro)
                ->firstOrFail();

            $extratoBase = BankStatementTransaction::withoutGlobalScopes()
                ->where('empresa_id', $empresaId)
                ->where('conta_bancaria_id', $contaResumo->id)
                ->whereBetween('data_transacao', [$request->data_inicio, $request->data_fim]);

            $erpBase = ItemContaEmpresa::query()
                ->where('empresa_id', $empresaId)
                ->where('conta_id', $contaResumo->id)
                ->whereBetween(DB::raw('DATE(data_pagamento)'), [$request->data_inicio, $request->data_fim]);

            $extratoIn = (float) (clone $extratoBase)->where('tipo', 'credit')->sum('valor');
            $extratoOut = abs((float) (clone $extratoBase)->where('tipo', 'debit')->sum('valor'));
            $erpIn = (float) (clone $erpBase)->whereIn('tipo', ['entrada', 'ENTRADA', '1', 'c'])->sum('valor');
            $erpOut = abs((float) (clone $erpBase)->whereIn('tipo', ['saida', 'SAIDA', '0', 'd'])->sum('valor'));

            $resumo = [
                'banco_in' => $extratoIn,
                'banco_out' => $extratoOut,
                'erp_in' => $erpIn,
                'erp_out' => $erpOut,
                'dif_in' => $extratoIn - $erpIn,
                'dif_out' => $extratoOut - $erpOut,
            ];
        }

        return view($this->listView, [
            'records'               => $records,
            'contasBancarias'       => $contasBancarias,
            'categorias'            => $categorias,
            'fornecedores'          => $fornecedores,
            'clientes'              => $clientes,
            'contasPagarSugestao'   => $contasPagarSugestao,
            'contasReceberSugestao' => $contasReceberSugestao,
            'resumo'                => $resumo,
            'title'                 => $this->formTitle,
        ]);
    }

    public function importar(Request $request)
    {
        $request->validate([
            'conta_bancaria_id' => 'required',
            'arquivo' => 'required|file'
        ]);

        try {
            $arquivo = $request->file('arquivo');
            $empresaId = $this->getEmpresaId();

            // 1. Pega o conteúdo do ficheiro
            $conteudo = file_get_contents($request->file('arquivo')->getPathname());

            // 2. Limpa o "&" do ficheiro para não dar o erro da linha 517
            $conteudo = str_replace('&', '&amp;', $conteudo);
            $conteudo = str_replace('&amp;amp;', '&amp;', $conteudo);

            // 3. Pula os cabeçalhos e pega só a parte que é XML
            $posicaoOfx = strpos($conteudo, '<OFX>');
            if ($posicaoOfx !== false) {
                $conteudo = substr($conteudo, $posicaoOfx);
            }

            // 4. A MÁGICA NOVA: Fecha as tags que o banco mandou abertas!
            // Transforma <TAG>valor em <TAG>valor</TAG>
            $conteudo = preg_replace('/<([a-zA-Z0-9_]+)>([^<\r\n]+)/', '<$1>$2</$1>', $conteudo);

            if ($posicaoOfx === false) {
                throw new \Exception("Tag <OFX> não localizada.");
            }

            $ofxData = substr($conteudo, $posicaoOfx);
            $ofxData = preg_replace('/>\s+</', '><', $ofxData);
            $ofxData = preg_replace('/<([A-Z0-9_]+)>([^<]+)/', '<$1>$2</$1>', $ofxData);

            $xml = simplexml_load_string($conteudo);

            if (!$xml || !isset($xml->BANKMSGSRSV1->STMTTRNRS->STMTRS->BANKTRANLIST->STMTTRN)) {
                throw new \Exception("Nenhuma transação encontrada.");
            }

            $transactions = $xml->BANKMSGSRSV1->STMTTRNRS->STMTRS->BANKTRANLIST->STMTTRN;
            $inseridos = 0;

            foreach ($transactions as $transaction) {
                $dataStr = substr((string) $transaction->DTPOSTED, 0, 8);
                $dataTransacao = Carbon::createFromFormat('Ymd', $dataStr)->format('Y-m-d');
                $valorBruto = (float) $transaction->TRNAMT;
                $descricao = preg_replace('/\s+/', ' ', trim((string)$transaction->MEMO ?: (string)$transaction->NAME));
                $doc = (string) $transaction->FITID;

                $hash = md5($empresaId . '_' . $request->conta_bancaria_id . '_' . $dataTransacao . '_' . $valorBruto . '_' . $doc);

                $reg = BankStatementTransaction::firstOrCreate(
                    [
                        'empresa_id' => $empresaId,
                        'import_hash' => $hash
                    ],
                    [
                        'filial_id' => $this->getFilialId(),
                        'usuario_id' => $this->getUsuarioId(),
                        'conta_bancaria_id' => $request->conta_bancaria_id,
                        'data_transacao' => $dataTransacao,
                        'valor' => abs($valorBruto),
                        'tipo' => $valorBruto >= 0 ? 'credit' : 'debit',
                        'descricao' => $descricao,
                        'numero_documento' => $doc,
                        'status' => 'pending'
                    ]
                );

                if ($reg->wasRecentlyCreated) {
                    $inseridos++;
                }
            }

            $this->aplicarRegrasDePara($request->conta_bancaria_id);

            return redirect($this->redirectPage)->with('mensagem_sucesso', "Ficheiro importado! {$inseridos} lançamentos novos.");

        } catch (\Exception $e) {
            return redirect()->back()->with('mensagem_erro', 'Erro: ' . $e->getMessage());
        }
    }

    public function conciliar(Request $request)
    {
        $request->validate([
            'extrato_id' => 'required',
            'tipo_conta' => 'required',
            'conta_ids'  => 'required|array'
        ]);

        DB::beginTransaction();
        try {
            $empresaId = $this->getEmpresaId();
            $extrato = BankStatementTransaction::withoutGlobalScopes()->where('empresa_id', $empresaId)->findOrFail($request->extrato_id);
            $contaBancaria = ContaEmpresa::where('empresa_id', $empresaId)->lockForUpdate()->findOrFail($extrato->conta_bancaria_id);
            $model = $request->tipo_conta === 'pagar' ? ContaPagar::class : ContaReceber::class;

            $jurosValor = $request->acrescimo ? (float) str_replace(',', '.', $request->acrescimo) : 0;
            $descontoValor = $request->desconto ? (float) str_replace(',', '.', $request->desconto) : 0;

            $categoriaId = null;
            $contaPagarId = null;
            $contaReceberId = null;
            $origem = $request->tipo_conta === 'pagar' ? 'ContaPagar' : 'ContaReceber';
            $tipoDocumento = $request->input('tipo_documento', 'Conciliação');
            if (is_numeric($tipoDocumento)) {
                $tipoDocumento = \App\Models\Venda::getTipoPagamentoNFe($tipoDocumento);
            }

            foreach ($request->conta_ids as $index => $idTitulo) {
                $titulo = $model::where('empresa_id', $empresaId)->findOrFail($idTitulo);

                if ($titulo->status == 0) {
                    $valorPago = $titulo->valor_integral;
                    $tituloJuros = 0;
                    $tituloDesconto = 0;

                    if ($index === 0) {
                        $valorPago = $titulo->valor_integral + $jurosValor - $descontoValor;
                        $tituloJuros = $jurosValor;
                        $tituloDesconto = $descontoValor;
                        $categoriaId = $titulo->categoria_id;

                        if ($request->tipo_conta === 'pagar') {
                            $contaPagarId = $titulo->id;
                        } else {
                            $contaReceberId = $titulo->id;
                        }
                    }

                    $campoValor = $request->tipo_conta === 'pagar' ? 'valor_pago' : 'valor_recebido';
                    $campoDataBaixa = $request->tipo_conta === 'pagar' ? 'data_pagamento' : 'data_recebimento';

                    $titulo->update([
                        'status' => 1,
                        $campoValor => $valorPago,
                        'juros' => $tituloJuros,
                        'desconto' => $tituloDesconto,
                        'multa' => 0,
                        $campoDataBaixa => $extrato->data_transacao,
                        'usuario_baixa_id' => $this->getUsuarioId(),
                        'tipo_pagamento' => $tipoDocumento
                    ]);
                }
            }

            if ($request->tipo_conta === 'pagar') {
                $contaBancaria->saldo -= $extrato->valor;
                $tipoMovimento = 'saida';
            } else {
                $contaBancaria->saldo += $extrato->valor;
                $tipoMovimento = 'entrada';
            }
            $contaBancaria->save();

            ItemContaEmpresa::create([
                'empresa_id' => $empresaId,
                'conta_id' => $contaBancaria->id,
                'categoria_id' => $categoriaId,
                'conta_pagar_id' => $contaPagarId,
                'conta_receber_id' => $contaReceberId,
                'origem' => $origem,
                'descricao' => 'Conciliação: ' . $extrato->descricao,
                'tipo_pagamento' => $tipoDocumento,
                'tipo' => $tipoMovimento,
                'valor' => $extrato->valor,
                'data_pagamento' => $extrato->data_transacao,
                'saldo_atual' => $contaBancaria->saldo,
                'user_id' => $this->getUsuarioId()
            ]);

            $extrato->update(['status' => 'reconciled']);

            DB::commit();
            return redirect()->back()->with('mensagem_sucesso', 'Conciliação processada e valores gravados corretamente!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('mensagem_erro', 'Erro ao conciliar: ' . $e->getMessage());
        }
    }

    public function criarEConciliar(Request $request)
    {
        $request->validate([
            'extrato_id' => 'required',
            'categoria_id' => 'required',
        ]);

        DB::beginTransaction();
        try {
            $extrato = BankStatementTransaction::withoutGlobalScopes()->findOrFail($request->extrato_id);
            $tipoClass = $extrato->tipo === 'debit' ? ContaPagar::class : ContaReceber::class;

            $dadosNovo = [
                'empresa_id' => $this->getEmpresaId(),
                'filial_id' => $this->getFilialId(),
                'usuario_id' => $this->getUsuarioId(),
                'categoria_id' => $request->categoria_id,
                'referencia' => 'Gerado no Extrato: ' . $extrato->descricao,
                'forma_pagamento' => $request->forma_pagamento ?? 'pix',
                // Usamos abs() para garantir que o valor seja positivo no financeiro
                'valor_integral' => abs($extrato->valor),
                'data_vencimento' => $extrato->data_transacao,
                'status' => 0
            ];

            // Define se salva como fornecedor ou cliente no novo título
            if ($extrato->tipo === 'debit') {
                $dadosNovo['fornecedor_id'] = $request->fornecedor_id;
            } else {
                $dadosNovo['cliente_id'] = $request->cliente_id;
            }

            $novo = $tipoClass::create($dadosNovo);

            // SALVAMENTO DA REGRA CORRIGIDO
            if ($request->has('salvar_regra') && $request->salvar_regra == 1) {

                // Preparamos os dados da regra dinamicamente
                $dadosRegra = [
                    'categoria_id' => $request->categoria_id,
                    'tipo_conta'   => $extrato->tipo === 'debit' ? 'pagar' : 'receber'
                ];

                // Aqui está o segredo: se for débito, preenche fornecedor. Se não, preenche cliente.
                if ($extrato->tipo === 'debit') {
                    $dadosRegra['fornecedor_id'] = $request->fornecedor_id;
                    $dadosRegra['cliente_id'] = null;
                } else {
                    $dadosRegra['cliente_id'] = $request->cliente_id;
                    $dadosRegra['fornecedor_id'] = null;
                }

                ConciliacaoRegra::updateOrCreate(
                    [
                        'empresa_id' => $this->getEmpresaId(),
                        'filial_id'  => $this->getFilialId(),
                        'palavra_chave' => $extrato->descricao
                    ],
                    $dadosRegra
                );
            }

            DB::commit();

            $request->merge([
                'tipo_conta' => $extrato->tipo === 'debit' ? 'pagar' : 'receber',
                'conta_ids' => [$novo->id]
            ]);

            return $this->conciliar($request);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('mensagem_erro', 'Erro: ' . $e->getMessage());
        }
    }

    public function transferir(Request $request)
    {
        // 1. Validando a categoria obrigatória
        $request->validate([
            'extrato_id' => 'required',
            'conta_destino_id' => 'required',
            'categoria_id' => 'required'
        ]);

        DB::beginTransaction();
        try {
            $empresaId = $this->getEmpresaId();
            $extrato = BankStatementTransaction::withoutGlobalScopes()->findOrFail($request->extrato_id);

            $cOrigem = ContaEmpresa::lockForUpdate()->findOrFail($extrato->conta_bancaria_id);
            $cDestino = ContaEmpresa::lockForUpdate()->findOrFail($request->conta_destino_id);

            // Tira da Origem
            $cOrigem->saldo -= $extrato->valor;
            $cOrigem->save();

            ItemContaEmpresa::create([
                'empresa_id' => $empresaId,
                'conta_id' => $cOrigem->id,
                'categoria_id' => $request->categoria_id, // Inserindo a Categoria
                'tipo' => 'saida',
                'descricao' => 'Transf. Saída (Conciliação): ' . $extrato->descricao, // Melhor descritivo
                'valor' => $extrato->valor,
                'data_pagamento' => $extrato->data_transacao, // CORREÇÃO: Usar a data do extrato e não a de hoje
                'saldo_atual' => $cOrigem->saldo,
                'user_id' => $this->getUsuarioId() // Gravando o usuário da ação
            ]);

            // Coloca no Destino
            $cDestino->saldo += $extrato->valor;
            $cDestino->save();

            ItemContaEmpresa::create([
                'empresa_id' => $empresaId,
                'conta_id' => $cDestino->id,
                'categoria_id' => $request->categoria_id, // Inserindo a Categoria
                'tipo' => 'entrada',
                'descricao' => 'Transf. Entrada (Conciliação): ' . $extrato->descricao, // Melhor descritivo
                'valor' => $extrato->valor,
                'data_pagamento' => $extrato->data_transacao, // CORREÇÃO: Usar a data do extrato
                'saldo_atual' => $cDestino->saldo,
                'user_id' => $this->getUsuarioId() // Gravando o usuário da ação
            ]);

            $extrato->update(['status' => 'reconciled']);

            DB::commit();
            return redirect()->back()->with('mensagem_sucesso', 'Transferência efetivada com categoria e datas corretas!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('mensagem_erro', 'Erro: ' . $e->getMessage());
        }
    }

    public function arquivar(Request $request)
    {
        $request->validate(['extrato_id' => 'required']);
        try {
            BankStatementTransaction::withoutGlobalScopes()
                ->where('empresa_id', $this->getEmpresaId())
                ->findOrFail($request->extrato_id)
                ->update(['status' => 'reconciled']);

            return redirect()->back()->with('mensagem_sucesso', 'Lançamento arquivado da tela.');
        } catch (\Exception $e) {
            return redirect()->back()->with('mensagem_erro', 'Erro: ' . $e->getMessage());
        }
    }

    public function limparPendentes()
    {
        BankStatementTransaction::withoutGlobalScopes()
            ->where('empresa_id', $this->getEmpresaId())
            ->where('status', 'pending')
            ->delete();

        return redirect($this->redirectPage)->with('mensagem_sucesso', 'Limpo.');
    }

    public function processarAutomaticos()
    {
        DB::beginTransaction();
        try {
            $empresaId = $this->getEmpresaId();
            $filialId = $this->getFilialId();

            // 1. Busca as regras corretas (Filial logada + Globais) - FICOU PERFEITO!
            $regras = ConciliacaoRegra::where('empresa_id', $empresaId)
                ->when($filialId, function($q) use ($filialId) {
                    return $q->where(function($sub) use ($filialId) {
                        $sub->where('filial_id', $filialId)
                            ->orWhereNull('filial_id');
                    });
                })->get();

            if ($regras->isEmpty()) {
                return redirect()->back()->with('mensagem_erro', 'Nenhuma regra de automação salva.');
            }

            // 2. Busca os extratos pendentes (AGORA COM A TRAVA DA FILIAL)
            $pendentes = BankStatementTransaction::withoutGlobalScopes()
                ->where('empresa_id', $empresaId)
                ->when($filialId, function($q) use ($filialId) {
                    return $q->where('filial_id', $filialId); // <- Ajuste adicionado aqui!
                })
                ->where('status', 'pending')
                ->get();

            $processados = 0;

            foreach ($pendentes as $extrato) {
                foreach ($regras as $regra) {
                    if (stripos($extrato->descricao, $regra->palavra_chave) !== false) {

                        $model = $regra->tipo_conta === 'pagar' ? ContaPagar::class : ContaReceber::class;
                        $campoValor = $regra->tipo_conta === 'pagar' ? 'valor_pago' : 'valor_recebido';

                        $campoDataBaixa = $regra->tipo_conta === 'pagar' ? 'data_pagamento' : 'data_recebimento';

                        $dadosNovo = [
                            'empresa_id' => $empresaId,
                            'filial_id' => $this->getFilialId(),
                            'usuario_id' => $this->getUsuarioId(),
                            'categoria_id' => $regra->categoria_id,
                            'referencia' => 'Robô Conciliador: ' . $extrato->descricao,
                            'forma_pagamento' => 'pix', // Padrão para automações
                            'valor_integral' => $extrato->valor,
                            $campoValor => $extrato->valor,
                            'data_vencimento' => $extrato->data_transacao,
                            $campoDataBaixa => $extrato->data_transacao,
                            'usuario_baixa_id' => $this->getUsuarioId(),
                            'status' => 1
                        ];

                        if ($regra->tipo_conta === 'pagar') {
                            $dadosNovo['fornecedor_id'] = $regra->fornecedor_id;
                        } else {
                            $dadosNovo['cliente_id'] = $regra->cliente_id;
                        }

                        $novoTitulo = $model::create($dadosNovo);

                        $contaPagarId = $regra->tipo_conta === 'pagar' ? $novoTitulo->id : null;
                        $contaReceberId = $regra->tipo_conta === 'receber' ? $novoTitulo->id : null;
                        $origem = $regra->tipo_conta === 'pagar' ? 'ContaPagar' : 'ContaReceber';

                        $contaBancaria = ContaEmpresa::lockForUpdate()->find($extrato->conta_bancaria_id);
                        if ($contaBancaria) {
                            if ($regra->tipo_conta === 'pagar') {
                                $contaBancaria->saldo -= $extrato->valor;
                                $tipoMovimento = 'saida';
                            } else {
                                $contaBancaria->saldo += $extrato->valor;
                                $tipoMovimento = 'entrada';
                            }
                            $contaBancaria->save();

                            ItemContaEmpresa::create([
                                'empresa_id' => $empresaId,
                                'conta_id' => $contaBancaria->id,
                                'categoria_id' => $regra->categoria_id,
                                'conta_pagar_id' => $contaPagarId,
                                'conta_receber_id' => $contaReceberId,
                                'origem' => $origem,
                                'descricao' => 'Automação: ' . $extrato->descricao,
                                'tipo' => $tipoMovimento,
                                'valor' => $extrato->valor,
                                'data_pagamento' => $extrato->data_transacao,
                                'saldo_atual' => $contaBancaria->saldo,
                                'user_id' => $this->getUsuarioId()
                            ]);
                        }

                        $extrato->update(['status' => 'reconciled']);
                        $processados++;
                        break;
                    }
                }
            }

            DB::commit();
            return redirect()->back()->with('mensagem_sucesso', "Mágica feita! {$processados} lançamentos baixados automaticamente.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('mensagem_erro', 'Erro: ' . $e->getMessage());
        }
    }

    protected function aplicarRegrasDePara($contaId)
    {
        $regras = ConciliacaoRegra::where('empresa_id', $this->getEmpresaId())->get();
        $pendentes = BankStatementTransaction::withoutGlobalScopes()
            ->where('empresa_id', $this->getEmpresaId())
            ->where('conta_bancaria_id', $contaId)
            ->where('status', 'pending')
            ->get();

        foreach ($pendentes as $extrato) {
            foreach ($regras as $regra) {
                if (stripos($extrato->descricao, $regra->palavra_chave) !== false) {
                    $req = new Request([
                        'extrato_id' => $extrato->id,
                        'categoria_id' => $regra->categoria_id,
                        'fornecedor_id' => $regra->fornecedor_id
                    ]);
                    $this->criarEConciliar($req);
                    break;
                }
            }
        }
    }
    public function processarLote(Request $request)
    {
        // Verifica se veio alguma caixinha marcada da tela
        if (!$request->has('extrato_ids') || empty($request->extrato_ids)) {
            return redirect()->back()->with('mensagem_erro', 'Nenhum lançamento foi selecionado.');
        }

        try {
            // Pega todos os IDs que você marcou
            $ids = $request->extrato_ids;

            // Vai no banco de dados e muda o status de todos de uma vez só
            \App\Models\BankStatementTransaction::whereIn('id', $ids)->update([
                'status' => 'reconciled'
            ]);

            // Retorna para a tela mantendo os filtros e avisando quantos foram processados
            return redirect()->back()->with('mensagem_sucesso', count($ids) . ' lançamentos confirmados com sucesso!');

        } catch (\Exception $e) {
            return redirect()->back()->with('mensagem_erro', 'Erro ao processar lote: ' . $e->getMessage());
        }
    }

    public function getSugestoes(Request $request)
    {
        $request->validate(['extrato_id' => ['required', 'integer']]);
        $empresaId = $this->getEmpresaId();
        $filialId = $this->getFilialId();
        $extrato = BankStatementTransaction::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->when($filialId, fn ($q) => $q->where('filial_id', $filialId))
            ->findOrFail((int) $request->extrato_id);

        $dataExtrato = Carbon::parse($extrato->data_transacao);
        $valorBusca = abs((float) $extrato->valor);
        $isDebito = $extrato->tipo === 'debit';
        $model = $isDebito ? ContaPagar::class : ContaReceber::class;
        $relacao = $isDebito ? 'fornecedor' : 'cliente';
        $dataBaixa = $isDebito ? 'data_pagamento' : 'data_recebimento';

        $base = $model::query()->with($relacao)
            ->where('empresa_id', $empresaId)
            ->when($filialId, fn ($q) => $q->where('filial_id', $filialId));

        $abertas = (clone $base)->where('status', 0)
            ->whereBetween('valor_integral', [max(0, $valorBusca - 5), $valorBusca + 5])
            ->orderByRaw('ABS(valor_integral - ?) ASC', [$valorBusca])
            ->limit(10)->get();

        $pagas = (clone $base)->where('status', 1)
            ->whereBetween('valor_integral', [max(0, $valorBusca - 5), $valorBusca + 5])
            ->whereBetween($dataBaixa, [$dataExtrato->copy()->subDays(10)->toDateString(), $dataExtrato->copy()->addDays(10)->toDateString()])
            ->limit(5)->get();

        $combos = [];
        $outras = collect();
        if ($abertas->isEmpty() && $pagas->isEmpty()) {
            $titulos = (clone $base)->where('status', 0)
                ->whereBetween('data_vencimento', [$dataExtrato->copy()->subDays(15)->toDateString(), $dataExtrato->copy()->addDays(15)->toDateString()])
                ->limit(100)->get();

            foreach ($titulos as $i => $primeiro) {
                foreach ($titulos->slice($i + 1) as $segundo) {
                    $parceiro1 = $primeiro->fornecedor_id ?? $primeiro->cliente_id;
                    $parceiro2 = $segundo->fornecedor_id ?? $segundo->cliente_id;
                    if ($parceiro1 && $parceiro1 == $parceiro2 && abs(((float)$primeiro->valor_integral + (float)$segundo->valor_integral) - $valorBusca) < 0.01) {
                        $combos[] = [$primeiro, $segundo];
                        if (count($combos) >= 10) break 2;
                    }
                }
            }

            if (!$combos) {
                $descricaoBanco = mb_strtoupper((string) $extrato->descricao);
                $outras = $titulos->filter(function ($titulo) use ($descricaoBanco, $relacao) {
                    $parceiro = $titulo->{$relacao};
                    $nome = mb_strtoupper((string) ($parceiro->razao_social ?? $parceiro->nome_fantasia ?? ''));
                    $primeira = preg_split('/\s+/', trim($nome))[0] ?? '';
                    return mb_strlen($primeira) > 2 && str_contains($descricaoBanco, $primeira);
                })->take(10)->values();
            }
        }

        return response()->json([
            'valor_banco' => $valorBusca,
            'tipo_conta' => $isDebito ? 'pagar' : 'receber',
            'sugestoes' => $abertas,
            'sugestoes_pagas' => $pagas,
            'combos' => $combos,
            'outras_opcoes' => $outras,
        ]);
    }
}
