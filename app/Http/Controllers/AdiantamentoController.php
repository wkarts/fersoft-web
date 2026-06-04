<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Adiantamento;
use App\Models\ItemContaEmpresa;
use App\Models\ContaEmpresa;
use App\Models\Cliente;
use App\Models\Fornecedor;
use App\Models\CategoriaConta;
use Illuminate\Support\Facades\DB;

class AdiantamentoController extends BaseController
{
    protected $model = Adiantamento::class;

    protected $resource = 'adiantamentos';
    protected $table = 'adiantamentos';
    protected $formTitle = 'Controle de Adiantamentos';
    protected $redirectPage = '/adiantamentos';

    public function rules(): array {
        return [
            'tipo_pessoa' => 'required',
            'pessoa_id' => 'required',
            'valor' => 'required',
            'data' => 'required',
            'conta_id' => 'required',
            'categoria_id' => 'required'
        ];
    }

    public function messages(): array {
        return [
            'tipo_pessoa.required' => 'Selecione se é Cliente ou Fornecedor.',
            'pessoa_id.required' => 'Busque e selecione a Razão Social.',
            'valor.required' => 'O valor do adiantamento é obrigatório.',
            'data.required' => 'A data é obrigatória.',
            'conta_id.required' => 'Selecione a conta bancária.',
            'categoria_id.required' => 'Selecione a categoria financeira.'
        ];
    }

    public function index(Request $request)
    {
        $tipo = $request->get('tipo');
        $pessoa_id = $request->get('pessoa_id');
        $title = $this->formTitle;

        $query = Adiantamento::where('empresa_id', $this->empresa_id)
            ->where('status', '!=', 'cancelado');

        // Filtros de busca
        if ($tipo) {
            if ($tipo == 'cliente') {
                $query->whereNotNull('cliente_id');
                if ($pessoa_id) $query->where('cliente_id', $pessoa_id);
            } else {
                $query->whereNotNull('fornecedor_id');
                if ($pessoa_id) $query->where('fornecedor_id', $pessoa_id);
            }
        }

        $saldos = $query->select(
            'cliente_id',
            'fornecedor_id',
            DB::raw('SUM(valor_total) as total_gerado'),
            DB::raw('SUM(valor_utilizado) as total_usado'),
            DB::raw('SUM(valor_total - valor_utilizado) as saldo_disponivel')
        )
            ->groupBy('cliente_id', 'fornecedor_id')
            ->with(['cliente', 'fornecedor']) // Importante: puxa os dados das relações
            ->orderByDesc('saldo_disponivel')
            ->get();

        $contas = ContaEmpresa::where('empresa_id', $this->empresa_id)->get();
        $categorias = CategoriaConta::where('empresa_id', $this->empresa_id)->get();

        return view('adiantamentos.index', compact('saldos', 'contas', 'categorias', 'tipo', 'pessoa_id', 'title'));
    }

    public function buscarPessoas(Request $request)
    {
        $pesquisa = $request->term;
        $tipo = $request->tipo;
        $query = ($tipo == 'cliente') ? Cliente::query() : Fornecedor::query();

        $data = $query->where('empresa_id', $this->empresa_id)
            ->where(function($q) use ($pesquisa) {
                $q->where('razao_social', 'LIKE', "%$pesquisa%")
                    ->orWhere('nome_fantasia', 'LIKE', "%$pesquisa%")
                    ->orWhere('cpf_cnpj', 'LIKE', "%$pesquisa%");
            })
            ->limit(20)->get();

        return response()->json([
            'results' => $data->map(function($item) {
                return [
                    'id' => $item->id,
                    'text' => ($item->cpf_cnpj ? $item->cpf_cnpj . " - " : "") . ($item->nome_fantasia ?: $item->razao_social)
                ];
            })
        ]);
    }

    public function imprimir(Request $request)
    {
        $tipo = $request->get('tipo');
        $pessoa_id = $request->get('pessoa_id');

        $query = Adiantamento::where('empresa_id', $this->empresa_id)
            ->where('status', '!=', 'cancelado');

        if ($tipo == 'cliente') {
            $query->whereNotNull('cliente_id');
            if ($pessoa_id) $query->where('cliente_id', $pessoa_id);
        } elseif ($tipo == 'fornecedor') {
            $query->whereNotNull('fornecedor_id');
            if ($pessoa_id) $query->where('fornecedor_id', $pessoa_id);
        }

        $dados = $query->with(['cliente', 'fornecedor', 'conta', 'categoria'])->get();
        $config = \App\Models\ConfigNota::where('empresa_id', $this->empresa_id)->first();

        return view('adiantamentos.relatorio', compact('dados', 'config', 'tipo'));
    }

    public function store(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $valor = str_replace(['.', ','], ['', '.'], $request->valor);
                $filial_id = __get_local_padrao() == -1 ? null : __get_local_padrao();

                $conta = ContaEmpresa::findOrFail($request->conta_id);
                if ($request->tipo_pessoa == 'cliente') {
                    $conta->saldo += $valor;
                    $descricao = "Adiant. Cliente: " . $request->nome_pessoa;
                    $tipo_mov = 'entrada';
                } else {
                    $conta->saldo -= $valor;
                    $descricao = "Adiant. Fornecedor: " . $request->nome_pessoa;
                    $tipo_mov = 'saida';
                }
                $conta->save();

                $item = ItemContaEmpresa::create([
                    'conta_id' => $conta->id,
                    'descricao' => $descricao,
                    'data_pagamento' => $request->data,
                    'valor' => $valor,
                    'tipo' => $tipo_mov,
                    'categoria_id' => $request->categoria_id,
                    'saldo_atual' => $conta->saldo,
                    'empresa_id' => $this->empresa_id,
                    'user_id' => $this->usuario_id,
                  	'origem' => 'Adiantamento'
                ]);

                Adiantamento::create([
                    'empresa_id' => $this->empresa_id,
                    'user_id' => $this->usuario_id,
                    'filial_id' => $filial_id,
                    'cliente_id' => ($request->tipo_pessoa == 'cliente') ? $request->pessoa_id : null,
                    'fornecedor_id' => ($request->tipo_pessoa == 'fornecedor') ? $request->pessoa_id : null,
                    'valor_total' => $valor,
                    'valor_utilizado' => 0,
                    'data' => $request->data,
                    'descricao' => $descricao,
                    'item_conta_empresa_id' => $item->id,
                    'status' => 'aberto'
                ]);

                return redirect()->back()->with('success', 'Adiantamento registrado com sucesso!');
            });
        } catch (\Exception $e) { return redirect()->back()->with('error', 'Erro ao salvar: ' . $e->getMessage()); }
    }

    public function cancelar($id)
    {
        try {
            return DB::transaction(function () use ($id) {
                $adv = Adiantamento::findOrFail($id);
                if ($adv->valor_utilizado > 0) {
                    return redirect()->back()->with('error', 'Este adiantamento já possui utilizações.');
                }
                $itemOrig = ItemContaEmpresa::find($adv->item_conta_empresa_id);
                $conta = ContaEmpresa::find($itemOrig->conta_id);
                if ($adv->cliente_id) { $conta->saldo -= $adv->valor_total; $tipo_estorno = 'saida'; }
                else { $conta->saldo += $adv->valor_total; $tipo_estorno = 'entrada'; }
                $conta->save();

                ItemContaEmpresa::create([
                    'conta_id' => $conta->id,
                    'descricao' => "ESTORNO: " . $adv->descricao,
                    'data_pagamento' => date('Y-m-d'),
                    'valor' => $adv->valor_total,
                    'tipo' => $tipo_estorno,
                    'categoria_id' => $itemOrig->categoria_id,
                    'saldo_atual' => $conta->saldo,
                    'empresa_id' => $this->empresa_id,
                    'user_id' => $this->usuario_id,
                  	'origem' => 'Adiantamento'
                ]);
                $adv->update(['status' => 'cancelado']);
                return redirect()->back()->with('success', 'Adiantamento estornado!');
            });
        } catch (\Exception $e) { return redirect()->back()->with('error', 'Erro ao estornar: ' . $e->getMessage()); }
    }

    public function extrato($tipo, $id)
    {
        $pessoa = ($tipo == 'cliente') ? \App\Models\Cliente::findOrFail($id) : \App\Models\Fornecedor::findOrFail($id);
        $lancamentos = Adiantamento::with('movimentacoes')->where('empresa_id', $this->empresa_id)->where($tipo . '_id', $id)->orderBy('data', 'desc')->get();
        return view('adiantamentos.extrato', compact('pessoa', 'lancamentos', 'tipo'));
    }

    public function getSaldoPessoa($tipo, $id)
    {
        try {
            $adiantamentos = Adiantamento::where('empresa_id', $this->empresa_id)->where($tipo . '_id', $id)->where('status', '!=', 'cancelado');
            $totalCredito = $adiantamentos->sum('valor_total');
            $idsAdiantamentos = $adiantamentos->pluck('id');
            $totalUsado = 0;
            if (count($idsAdiantamentos) > 0) {
                $totalUsado = \App\Models\AdiantamentoMovimentacao::whereIn('adiantamento_id', $idsAdiantamentos)->sum('valor');
            }
            $saldoFinal = $totalCredito - $totalUsado;
            return response()->json(['saldo' => $saldoFinal > 0 ? $saldoFinal : 0]);
        } catch (\Exception $e) { return response()->json(['saldo' => 0, 'erro' => $e->getMessage()]); }
    }

    /**
     * VERSÃO DINÂMICA: Aceita 7 parâmetros para gravar Usuário e Filial corretamente.
     */
    public static function baixarAdiantamento($pessoa_id, $tipo, $valor_nota, $empresa_id, $id_documento, $usuario_id, $filial_id)
    {
        $adiantamentos = Adiantamento::where('empresa_id', $empresa_id)
            ->where($tipo . '_id', $pessoa_id)
            ->where('status', 'aberto')
            ->orderBy('data', 'asc')
            ->get();

        $saldo_abater = $valor_nota;

        foreach ($adiantamentos as $adv) {
            if ($saldo_abater <= 0) break;
            $disponivel = $adv->valor_total - $adv->valor_utilizado;
            $usar = ($disponivel > $saldo_abater) ? $saldo_abater : $disponivel;
            $adv->valor_utilizado += $usar;
            if ($adv->valor_utilizado >= $adv->valor_total) { $adv->status = 'finalizado'; }
            $adv->save();

            DB::table('adiantamento_movimentacoes')->insert([
                'adiantamento_id' => $adv->id,
                'empresa_id' => $empresa_id, // Fix SQL Error 1452
                'usuario_id' => $usuario_id, // Fix SQL Error 1452
                'filial_id' => $filial_id,   // Fix Undefined variable
                'conta_receber_id' => ($tipo == 'cliente') ? $id_documento : null,
                'conta_pagar_id' => ($tipo == 'fornecedor') ? $id_documento : null,
                'valor' => $usar,
                'data' => date('Y-m-d'),
                'created_at' => now(), 'updated_at' => now()
            ]);
            $saldo_abater -= $usar;
        }
    }

    public function sincronizar()
    {
        return redirect()->back()->with('success', 'Sincronização iniciada! O sistema está a procurar notas autorizadas.');
    }

    public function index_agnaldo_desativou_26042026(Request $request)
    {
        __saveRedirect($this->empresa_id, '', 'adiantamentos');

        $saldos = Adiantamento::where('empresa_id', $this->empresa_id)
            ->where('status', '!=', 'cancelado')
            ->select(
                'cliente_id',
                'fornecedor_id',
                DB::raw('SUM(valor_total) as total_gerado'),
                DB::raw('SUM(valor_utilizado) as total_usado'),
                DB::raw('SUM(valor_total - valor_utilizado) as saldo_disponivel')
            )
            ->groupBy('cliente_id', 'fornecedor_id')
            ->with(['cliente', 'fornecedor'])
            ->orderByDesc('saldo_disponivel')
            ->get();

        $contas = \App\Models\ContaEmpresa::where('empresa_id', $this->empresa_id)->get();
        $categorias = \App\Models\CategoriaConta::where('empresa_id', $this->empresa_id)->get();

        return view('adiantamentos.index', compact('saldos', 'contas', 'categorias'));
    }

    public function buscarPessoas_agnaldo_desativou_26042026(Request $request)
    {
        $pesquisa = $request->term;
        $tipo = $request->tipo;

        $query = ($tipo == 'cliente') ? Cliente::query() : Fornecedor::query();

        $data = $query->where('empresa_id', $this->empresa_id)
            ->where(function($q) use ($pesquisa) {
                $q->where('razao_social', 'LIKE', "%$pesquisa%")
                  ->orWhere('nome_fantasia', 'LIKE', "%$pesquisa%")
                  ->orWhere('cpf_cnpj', 'LIKE', "%$pesquisa%");
            })
            ->limit(20)
            ->get();

        return response()->json([
            'results' => $data->map(function($item) {
                $identificador = $item->cpf_cnpj ? $item->cpf_cnpj . " - " : "";
                $nomeExibicao = $item->nome_fantasia ?: $item->razao_social;

                return [
                    'id' => $item->id,
                    'text' => $identificador . $nomeExibicao
                ];
            })
        ]);
    }

    public function store_agnaldo_desativou_26042026(Request $request)
    {

      //dd($request->all());

        try {
            return DB::transaction(function () use ($request) {
                $valor = str_replace(['.', ','], ['', '.'], $request->valor);
                $filial_id = __get_local_padrao();

                // SE a empresa de teste não tem filiais cadastradas, ou se a função retornar 1 (Matriz padrão), forçamos NULL
                if ($filial_id <= 1) {
                    $filial_id = null;
                }

                $categoria = \App\Models\CategoriaConta::find($request->categoria_id);
                    $nomeCategoria = $categoria ? $categoria->nome : 'Adiantamento';

                    $nomePessoa = $request->nome_pessoa; // Vem formatado: "CNPJ - Nome"

                    $conta = ContaEmpresa::findOrFail($request->conta_id);

                    if ($request->tipo_pessoa == 'cliente') {
                        $conta->saldo += $valor;
                        // Monta a string profissional para Entrada
                        $descricao = "Rec. Adiantamento - {$nomePessoa} (Ref: {$nomeCategoria})";
                        $tipo_mov = 'entrada';
                    } else {
                        $conta->saldo -= $valor;
                        // Monta a string profissional para Saída
                        $descricao = "Pag. Adiantamento - {$nomePessoa} (Ref: {$nomeCategoria})";
                        $tipo_mov = 'saida';
                    }
                    $conta->save();

                $item = ItemContaEmpresa::create([
                    'conta_id' => $conta->id,
                    'descricao' => $descricao,
                    'data_pagamento' => $request->data,
                    'valor' => $valor,
                    'tipo' => $tipo_mov,
                    'categoria_id' => $request->categoria_id,
                    'saldo_atual' => $conta->saldo,
                    'empresa_id' => $this->empresa_id,
                    'user_id' => auth()->user()->id ?? 1
                ]);

                Adiantamento::create([
                    'empresa_id' => $this->empresa_id,
                    'filial_id' => $filial_id,
                    'usuario_id' => auth()->user()->id ?? 1,
                    'cliente_id' => ($request->tipo_pessoa == 'cliente') ? $request->pessoa_id : null,
                    'fornecedor_id' => ($request->tipo_pessoa == 'fornecedor') ? $request->pessoa_id : null,
                    'valor_total' => $valor,
                    'valor_utilizado' => 0,
                    'data' => $request->data,
                    'descricao' => $descricao,
                    'item_conta_empresa_id' => $item->id,
                    'status' => 'aberto'
                ]);

                return redirect()->back()->with('success', 'Adiantamento registrado com sucesso!');
            });
        } catch (\Exception $e) {
            // O CÓDIGO DA VERDADE:
            // Isso vai fazer o erro do banco explodir na tela preta igualzinho ao print que você mandou!
            dd('ERRO AO SALVAR:', $e->getMessage(), 'LINHA:', $e->getLine());

            // return redirect()->back()->with('error', 'Erro ao salvar: ' . $e->getMessage());
        }
    }

    public function cancelar_agnaldo_desativou_26042026($id)
    {
        try {
            return DB::transaction(function () use ($id) {
                $adv = Adiantamento::findOrFail($id);

                if ($adv->valor_utilizado > 0) {
                    return redirect()->back()->with('error', 'Este adiantamento já possui utilizações e não pode ser estornado.');
                }

                $itemOrig = ItemContaEmpresa::find($adv->item_conta_empresa_id);
                $conta = ContaEmpresa::find($itemOrig->conta_id);

                if ($adv->cliente_id) {
                    $conta->saldo -= $adv->valor_total;
                    $tipo_estorno = 'saida';
                } else {
                    $conta->saldo += $adv->valor_total;
                    $tipo_estorno = 'entrada';
                }
                $conta->save();

                ItemContaEmpresa::create([
                    'conta_id' => $conta->id,
                    'descricao' => "ESTORNO: " . $adv->descricao,
                    'data_pagamento' => date('Y-m-d'),
                    'valor' => $adv->valor_total,
                    'tipo' => $tipo_estorno,
                    'categoria_id' => $itemOrig->categoria_id,
                    'saldo_atual' => $conta->saldo,
                    'empresa_id' => $this->empresa_id,
                    'user_id' => auth()->user()->id ?? 1
                ]);

                $adv->update(['status' => 'cancelado']);

                return redirect()->back()->with('success', 'Adiantamento estornado com sucesso!');
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao estornar: ' . $e->getMessage());
        }
    }

    public function extrato_agnaldo_desativou_26042026($tipo, $id)
    {
        $pessoa = ($tipo == 'cliente')
            ? \App\Models\Cliente::findOrFail($id)
            : \App\Models\Fornecedor::findOrFail($id);

        $lancamentos = Adiantamento::with('movimentacoes')
            ->where('empresa_id', $this->empresa_id)
            ->where($tipo . '_id', $id)
            ->orderBy('data', 'desc')
            ->get();

        return view('adiantamentos.extrato', compact('pessoa', 'lancamentos', 'tipo'));
    }

    public function getSaldoPessoa_agnaldo_desativou_26042026($tipo, $id)
    {
        try {
            $adiantamentos = \App\Models\Adiantamento::where('empresa_id', $this->empresa_id)
                ->where($tipo . '_id', $id)
                ->where('status', '!=', 'cancelado');

            $totalCredito = $adiantamentos->sum('valor_total');
            $idsAdiantamentos = $adiantamentos->pluck('id');

            $totalUsado = 0;
            if (count($idsAdiantamentos) > 0) {
                $totalUsado = \App\Models\AdiantamentoMovimentacao::whereIn('adiantamento_id', $idsAdiantamentos)
                    ->sum('valor');
            }

            $saldoFinal = $totalCredito - $totalUsado;

            return response()->json(['saldo' => $saldoFinal > 0 ? $saldoFinal : 0]);

        } catch (\Exception $e) {
            return response()->json(['saldo' => 0, 'erro' => $e->getMessage()]);
        }
    }

    /**
     * Método Estático ÚNICO para uso em outros módulos (Vendas/Compras)
     * Adicionamos "= null" no id_documento para que ele funcione na sua Compra Manual sem causar erro!
     */
    public static function baixarAdiantamento_agnaldo_desativou_26042026($pessoa_id, $tipo, $valor_nota, $empresa_id, $id_documento = null)
    {
        $adiantamentos = Adiantamento::where('empresa_id', $empresa_id)
            ->where($tipo . '_id', $pessoa_id)
            ->where('status', 'aberto')
            ->orderBy('data', 'asc')
            ->get();

        $saldo_abater = $valor_nota;

        foreach ($adiantamentos as $adv) {
            if ($saldo_abater <= 0) break;

            $disponivel = $adv->valor_total - $adv->valor_utilizado;
            $usar = ($disponivel > $saldo_abater) ? $saldo_abater : $disponivel;

            $adv->valor_utilizado += $usar;
            if ($adv->valor_utilizado >= $adv->valor_total) {
                $adv->status = 'finalizado';
            }
            $adv->save();

            DB::table('adiantamento_movimentacoes')->insert([
                'adiantamento_id' => $adv->id,
                'conta_receber_id' => ($tipo == 'cliente') ? $id_documento : null,
                'conta_pagar_id' => ($tipo == 'fornecedor') ? $id_documento : null,
                'valor' => $usar,
                'data' => date('Y-m-d'),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $saldo_abater -= $usar;
        }
    }
  
       public function devolver(Request $request)
      {
          try {
              return DB::transaction(function () use ($request) {
                  $valorDevolucao = str_replace(['.', ','], ['', '.'], $request->valor_devolucao);
                  $valorRestante = $valorDevolucao;
                  $tipoPessoa = $request->tipo_pessoa; 
                  $pessoaId = $request->pessoa_id;

                  // --- NOVO: Busca o nome da pessoa para a descrição ---
                  if ($tipoPessoa == 'cliente') {
                      $pessoa = \App\Models\Cliente::find($pessoaId);
                      $nomePessoa = $pessoa ? ($pessoa->nome_fantasia ?: $pessoa->razao_social) : 'Cliente não identificado';
                      $tipoMovFinanceiro = 'saida'; // Devolução para cliente sai dinheiro
                  } else {
                      $pessoa = \App\Models\Fornecedor::find($pessoaId);
                      $nomePessoa = $pessoa ? ($pessoa->nome_fantasia ?: $pessoa->razao_social) : 'Fornecedor não identificado';
                      $tipoMovFinanceiro = 'entrada'; // Fornecedor devolvendo pra você entra dinheiro
                  }
                  // -----------------------------------------------------

                  $adiantamentos = Adiantamento::where('empresa_id', $this->empresa_id)
                      ->where($tipoPessoa . '_id', $pessoaId)
                      ->where('status', 'aberto')
                      ->orderBy('data', 'asc')
                      ->get();

                  if ($adiantamentos->isEmpty()) {
                      return redirect()->back()->with('error', 'Não há adiantamentos abertos para esta pessoa.');
                  }

                  foreach ($adiantamentos as $adv) {
                      if ($valorRestante <= 0) break;

                      $disponivel = $adv->valor_total - $adv->valor_utilizado;
                      $usar = ($disponivel > $valorRestante) ? $valorRestante : $disponivel;

                      DB::table('adiantamento_movimentacoes')->insert([
                          'adiantamento_id' => $adv->id,
                          'empresa_id' => $this->empresa_id,
                          'usuario_id' => $this->usuario_id,
                          'valor' => $usar,
                          'data' => $request->data_devolucao,
                          'created_at' => now(), 'updated_at' => now()
                      ]);

                      $adv->valor_utilizado += $usar;
                      if ($adv->valor_utilizado >= $adv->valor_total) { $adv->status = 'finalizado'; }
                      $adv->save();
                      $valorRestante -= $usar;
                  }

                  $conta = ContaEmpresa::findOrFail($request->conta_id);
                  if ($tipoMovFinanceiro == 'saida') { $conta->saldo -= $valorDevolucao; } 
                  else { $conta->saldo += $valorDevolucao; }
                  $conta->save();

                  // Descrição Profissional com o nome da pessoa
                  $descricaoFinanceira = "DEVOLUÇÃO Adiantamento - " . strtoupper($tipoPessoa) . ": " . $nomePessoa;

                  ItemContaEmpresa::create([
                      'conta_id' => $conta->id,
                      'descricao' => $descricaoFinanceira,
                      'data_pagamento' => $request->data_devolucao,
                      'valor' => $valorDevolucao,
                      'tipo' => $tipoMovFinanceiro,
                      'categoria_id' => $request->categoria_id ?? null,
                      'saldo_atual' => $conta->saldo,
                      'empresa_id' => $this->empresa_id,
                      'user_id' => $this->usuario_id,
                      'origem' => 'Adiantamento'
                  ]);

                  return redirect()->back()->with('success', 'Devolução de R$ ' . number_format($valorDevolucao, 2, ',', '.') . ' realizada!');
              });
          } catch (\Exception $e) { dd($e->getMessage()); }
      }
		
  		public function update(Request $request, $id)
      {
          try {
              return DB::transaction(function () use ($request, $id) {
                  $adv = Adiantamento::findOrFail($id);
                  $valorNovo = str_replace(['.', ','], ['', '.'], $request->valor);
                  $valorAntigo = $adv->valor_total;

                  if ($adv->valor_utilizado > 0) {
                      return redirect()->back()->with('error', 'Não é possível editar um adiantamento já utilizado.');
                  }

                  // Atualiza o saldo na Conta Empresa
                  $itemOrig = ItemContaEmpresa::find($adv->item_conta_empresa_id);
                  $conta = ContaEmpresa::find($itemOrig->conta_id);

                  if ($adv->cliente_id) {
                      // Se cliente: Devolve o antigo (subtrai) e soma o novo
                      $conta->saldo = ($conta->saldo - $valorAntigo) + $valorNovo;
                  } else {
                      // Se fornecedor: Devolve o antigo (soma) e subtrai o novo
                      $conta->saldo = ($conta->saldo + $valorAntigo) - $valorNovo;
                  }
                  $conta->save();

                  // Atualiza o registro financeiro
                  $itemOrig->update([
                      'valor' => $valorNovo,
                      'descricao' => $request->descricao,
                      'data_pagamento' => $request->data,
                      'saldo_atual' => $conta->saldo
                  ]);

                  // Atualiza o adiantamento
                  $adv->update([
                      'valor_total' => $valorNovo,
                      'data' => $request->data,
                      'descricao' => $request->descricao
                  ]);

                  return redirect()->back()->with('success', 'Adiantamento atualizado!');
              });
          } catch (\Exception $e) {
              return redirect()->back()->with('error', 'Erro ao editar: ' . $e->getMessage());
          }
      }
}
