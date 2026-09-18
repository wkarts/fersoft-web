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
                return $q->where('conta_pagars.filial_id', $filialId);
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
                return $q->where('conta_recebers.filial_id', $filialId);
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
            if ($request->status_filtro !== 'all') {
                $query->where('status', $request->status_filtro);
            }
        } else {
            // Se a requisição veio sem o parâmetro status_filtro, define como pending padrão
            $query->where('status', 'pending');
        }

        $records = $query->orderBy('data_transacao', 'desc')->get();

        // Lógica original de marcação de status
        foreach ($records as $extrato) {
            $extrato->achou_pendente = false;
            $extrato->achou_pago = false;
            $listaBusca = ($extrato->tipo === 'debit') ? $contasPagarSugestao : $contasReceberSugestao;
            $descBanco = strtoupper($extrato->descricao);
            foreach ($listaBusca as $titulo) {
                if (abs((float)$titulo->valor_integral - (float)$extrato->valor) < 0.01) {
                    $nomeFornecedor = trim(strtoupper($titulo->nome_parceiro ?? ''));
                    if (!empty($nomeFornecedor)) {
                        $primeiraPalavra = explode(' ', $nomeFornecedor)[0];
                        if (strlen($primeiraPalavra) > 2 && strpos($descBanco, $primeiraPalavra) !== false) {
                            if ($titulo->status == 1) { $extrato->achou_pago = true; } else { $extrato->achou_pendente = true; }
                            break;
                        }
                    }
                }
            }
        }

        // BLOCO DE CÁLCULO DO CONFRONTO ERP x BANCO
        $resumo = null;
        if ($request->filled('conta_filtro') && $request->filled('data_inicio') && $request->filled('data_fim')) {

            // 1. Somar apenas CRÉDITO do Banco (Entradas)
            $extratoIn = BankStatementTransaction::where('empresa_id', $empresaId)
                ->where('conta_bancaria_id', $request->conta_filtro)
                ->whereBetween('data_transacao', [$request->data_inicio, $request->data_fim])
                ->where('tipo', 'credit')
                ->sum('valor');

            // 2. Somar apenas DÉBITO do Banco (Saídas)
            $extratoOut = BankStatementTransaction::where('empresa_id', $empresaId)
                ->where('conta_bancaria_id', $request->conta_filtro)
                ->whereBetween('data_transacao', [$request->data_inicio, $request->data_fim])
                ->where('tipo', 'debit')
                ->sum('valor');

            // 3. Somar ENTRADAS do ERP (Removido o filtro de empresa_id)
            $erpIn = ItemContaEmpresa::where('conta_id', $request->conta_filtro)
                ->whereRaw("DATE(data_pagamento) = ?", [$request->data_inicio])
                // Usamos whereIn para aceitar variações caso o seu sistema grave de formas diferentes
                ->whereIn('tipo', ['entrada', 'ENTRADA', '1', 'c'])
                ->sum('valor');

            // 4. Somar SAÍDAS do ERP (Removido o filtro de empresa_id)
            $erpOut = ItemContaEmpresa::where('conta_id', $request->conta_filtro)
                ->whereRaw("DATE(data_pagamento) = ?", [$request->data_inicio])
                ->where('tipo', 'saida')
                ->sum('valor');

            $resumo = [
                'banco_in'   => $extratoIn,
                'banco_out'  => abs($extratoOut),
                'erp_in'     => $erpIn,
                'erp_out'    => abs($erpOut),
                'dif_in'     => $extratoIn - $erpIn,
                'dif_out'    => abs($extratoOut) - abs($erpOut)
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

            $conteudo = file_get_contents($request->file('arquivo')->getPathname());
            $conteudo = str_replace('&', '&amp;', $conteudo);
            $conteudo = str_replace('&amp;amp;', '&amp;', $conteudo);

            $posicaoOfx = strpos($conteudo, '<OFX>');
            if ($posicaoOfx !== false) {
                $conteudo = substr($conteudo, $posicaoOfx);
            }

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

            // Executa automação respeitando a trava de duplicidade
            $this->aplicarRegrasDePara($request->conta_bancaria_id);

            return redirect($this->redirectPage)->with('mensagem_sucesso', "Arquivo importado! {$inseridos} lançamentos do extrato processados.");

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

            foreach ($request->conta_ids as $index => $idTitulo) {
                $titulo = $model::where('empresa_id', $empresaId)->findOrFail($idTitulo);

                if ($titulo->status == 0) {
                    // CORREÇÃO: Inicializa com o valor integral do próprio título
                    $valorPago = $titulo->valor_integral;
                    $tituloJuros = 0;
                    $tituloDesconto = 0;

                    // Se for o primeiro título, ele recebe os ajustes de Juros/Desconto do extrato
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
                    $tipoDocumento = $request->tipo_documento ?? 'Conciliação';

                    if (is_numeric($tipoDocumento)) {
                        $tipoDocumento = \App\Models\Venda::getTipoPagamentoNFe($tipoDocumento);
                    }
                    $titulo->update([
                        'status' => 1,
                        $campoValor => $valorPago, // Agora grava o valor correto
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

            // 1. Busca as regras de automação
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

            // 2. Busca os extratos pendentes
            $pendentes = BankStatementTransaction::withoutGlobalScopes()
                ->where('empresa_id', $empresaId)
                ->when($filialId, function($q) use ($filialId) {
                    return $q->where('filial_id', $filialId);
                })
                ->where('status', 'pending')
                ->get();

            $processados = 0;
            $duplicadosIgnorados = 0;

            foreach ($pendentes as $extrato) {
                foreach ($regras as $regra) {
                    if (stripos($extrato->descricao, $regra->palavra_chave) !== false) {

                        $tipoMovimentoCheck = $regra->tipo_conta === 'pagar' ? 'saida' : 'entrada';

                        // --- VALIDAÇÃO DE DUPLICIDADE ---
                        // Verifica se já existe movimento no ERP para esta conta bancária na mesma data e com o mesmo valor
                        $jaExiste = ItemContaEmpresa::where('empresa_id', $empresaId)
                            ->where('conta_id', $extrato->conta_bancaria_id)
                            ->whereRaw("DATE(data_pagamento) = ?", [$extrato->data_transacao])
                            ->where('valor', $extrato->valor)
                            ->where('tipo', $tipoMovimentoCheck)
                            ->exists();

                        if ($jaExiste) {
                            // Marca o extrato como concilado/processado para não ficar na fila pendente e não duplicar lançamento no ERP
                            $extrato->update(['status' => 'reconciled']);
                            $duplicadosIgnorados++;
                            break;
                        }
                        // ----------------------------------

                        $model = $regra->tipo_conta === 'pagar' ? ContaPagar::class : ContaReceber::class;
                        $campoValor = $regra->tipo_conta === 'pagar' ? 'valor_pago' : 'valor_recebido';
                        $campoDataBaixa = $regra->tipo_conta === 'pagar' ? 'data_pagamento' : 'data_recebimento';

                        $dadosNovo = [
                            'empresa_id' => $empresaId,
                            'filial_id' => $this->getFilialId(),
                            'usuario_id' => $this->getUsuarioId(),
                            'categoria_id' => $regra->categoria_id,
                            'referencia' => 'Robô Conciliador: ' . $extrato->descricao,
                            'forma_pagamento' => 'pix',
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

            $msg = "Mágica feita! {$processados} lançamentos baixados automaticamente.";
            if ($duplicadosIgnorados > 0) {
                $msg .= " ({$duplicadosIgnorados} lançamentos já existiam na mesma data/valor e foram marcados como concilidados para evitar duplicidade).";
            }

            return redirect()->back()->with('mensagem_sucesso', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('mensagem_erro', 'Erro: ' . $e->getMessage());
        }
    }

    protected function aplicarRegrasDePara($contaId)
    {
        $empresaId = $this->getEmpresaId();
        $regras = ConciliacaoRegra::where('empresa_id', $empresaId)->get();
        $pendentes = BankStatementTransaction::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->where('conta_bancaria_id', $contaId)
            ->where('status', 'pending')
            ->get();

        foreach ($pendentes as $extrato) {
            foreach ($regras as $regra) {
                if (stripos($extrato->descricao, $regra->palavra_chave) !== false) {

                    $tipoMovimentoCheck = $regra->tipo_conta === 'pagar' ? 'saida' : 'entrada';

                    // VALIDAÇÃO ANTI-DUPLICIDADE:
                    // Verifica se já existe caixa/movimentação no ERP com a mesma data, conta e valor
                    $jaExisteNoCaixa = ItemContaEmpresa::where('empresa_id', $empresaId)
                        ->where('conta_id', $extrato->conta_bancaria_id)
                        ->whereRaw("DATE(data_pagamento) = ?", [$extrato->data_transacao])
                        ->where('valor', $extrato->valor)
                        ->where('tipo', $tipoMovimentoCheck)
                        ->exists();

                    if ($jaExisteNoCaixa) {
                        // Apenas marca o extrato importado como concilado sem gerar duplicidade no financeiro
                        $extrato->update(['status' => 'reconciled']);
                        break;
                    }

                    $req = new Request([
                        'extrato_id' => $extrato->id,
                        'categoria_id' => $regra->categoria_id,
                        'fornecedor_id' => $regra->fornecedor_id,
                        'cliente_id' => $regra->cliente_id
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
        $extrato = \App\Models\BankStatementTransaction::withoutGlobalScopes()->findOrFail($request->extrato_id);
        $empresaId = $this->getEmpresaId();
        $filialId = $this->getFilialId();
        $dataExtrato = \Carbon\Carbon::parse($extrato->data_transacao);

        $valorBusca = abs($extrato->valor);
        $model = ($extrato->tipo === 'debit') ? \App\Models\ContaPagar::class : \App\Models\ContaReceber::class;
        $tipoConta = ($extrato->tipo === 'debit') ? 'pagar' : 'receber';
        $relacao = ($extrato->tipo === 'debit') ? 'fornecedor' : 'cliente';

        // 1. Busca Títulos ABERTOS (Exatos)
        $sugestoesAbertas = $model::with([$relacao])
            ->where('empresa_id', $empresaId)->where('status', 0)
            ->when($filialId, fn($q) => $q->where('filial_id', $filialId))
            ->whereBetween('valor_integral', [$valorBusca - 5, $valorBusca + 5])
            ->orderByRaw("ABS(valor_integral - {$valorBusca}) ASC")
            ->limit(10)->get();

        // 2. Busca Títulos JÁ PAGOS (Resolve a Elizabete)
        $sugestoesPagas = $model::with([$relacao])
            ->where('empresa_id', $empresaId)->where('status', 1)
            ->when($filialId, fn($q) => $q->where('filial_id', $filialId))
            ->whereBetween('valor_integral', [$valorBusca - 5, $valorBusca + 5])
            ->whereBetween($tipoConta === 'pagar' ? 'data_pagamento' : 'data_recebimento', [
                $dataExtrato->copy()->subDays(10)->format('Y-m-d'),
                $dataExtrato->copy()->addDays(10)->format('Y-m-d')
            ])->limit(5)->get();

        $combosEncontrados = [];
        $outrasOpcoes = [];

        // 3. Se não achou NENHUM exato, procura combos ou títulos com o MESMO NOME
        if ($sugestoesAbertas->isEmpty() && $sugestoesPagas->isEmpty()) {
            $titulosNoPeriodo = clone $model::with([$relacao])
                ->where('empresa_id', $empresaId)->where('status', 0)
                ->when($filialId, fn($q) => $q->where('filial_id', $filialId))
                ->whereBetween('data_vencimento', [
                    $dataExtrato->copy()->subDays(15)->format('Y-m-d'),
                    $dataExtrato->copy()->addDays(15)->format('Y-m-d')
                ])->get();

            $achouCombo = false;
            foreach ($titulosNoPeriodo as $i => $t1) {
                foreach ($titulosNoPeriodo as $j => $t2) {
                    if ($i >= $j) continue;
                    $parceiro1 = $t1->fornecedor_id ?? $t1->cliente_id;
                    $parceiro2 = $t2->fornecedor_id ?? $t2->cliente_id;

                    if ($parceiro1 == $parceiro2 && abs(($t1->valor_integral + $t2->valor_integral) - $valorBusca) < 0.01) {
                        $combosEncontrados[] = [$t1, $t2];
                        $achouCombo = true;
                    }
                }
            }

            // CORREÇÃO: Traz apenas opções que tenham o mesmo nome (Resolve Supergasbras)
            if (!$achouCombo) {
                $descBanco = strtoupper($extrato->descricao);
                $outrasOpcoes = $titulosNoPeriodo->filter(function($t) use ($descBanco) {
                    $nome = strtoupper($t->fornecedor->razao_social ?? ($t->cliente->razao_social ?? ''));
                    if (!$nome) return false;

                    // Pega a primeira palavra do fornecedor (ex: SUPERGASBRAS) e vê se tem no extrato do banco
                    $primeiraPalavra = explode(' ', $nome)[0];
                    return (strlen($primeiraPalavra) > 2 && strpos($descBanco, $primeiraPalavra) !== false);
                })->take(10)->values();
            }
        }

        return response()->json([
            'valor_banco'    => $valorBusca,
            'tipo_conta'     => $tipoConta,
            'sugestoes'      => $sugestoesAbertas,
            'sugestoes_pagas'=> $sugestoesPagas,
            'combos'         => $combosEncontrados,
            'outras_opcoes'  => $outrasOpcoes
        ]);
    }

}
