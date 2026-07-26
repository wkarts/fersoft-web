<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Requisicao;
use App\Models\Produto;
use App\Models\Funcionario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PDF;

class RequisicaoController extends BaseController
{
    protected $model = Requisicao::class;
    protected $redirectPage = '/requisicoes';
    protected $formTitle = 'Requisição de Material/EPI';

    public function __construct()
    {
        parent::__construct();
    }

    protected function rules(): array { return []; }
    protected function messages(): array { return []; }

    public function index(Request $request)
    {
        $title = "Histórico de Requisições";
        $query = Requisicao::with(['funcionario', 'responsavel'])
            ->where('empresa_id', $this->empresa_id);

        if ($request->filled('funcionario_id')) {
            $query->where('funcionario_id', $request->funcionario_id);
        }

        $requisicoes = $query->orderBy('data_requisicao', 'desc')->paginate(15);
        $funcionarios = Funcionario::where('empresa_id', $this->empresa_id)->orderBy('nome')->get();
     	$filiais = \App\Models\Filial::where('empresa_id', $this->empresa_id)->get();

        return view('requisicoes.index', compact('requisicoes', 'funcionarios', 'title'));
    }

    public function create()
    {
        $title = "Nova Requisição de Material/EPI";
        $empresa_id = $this->empresa_id;

        $funcionarios = Funcionario::where('empresa_id', $empresa_id)->orderBy('nome')->get();

        $tecnicos = Funcionario::where('empresa_id', $empresa_id)
            ->whereHas('funcao', function($q) {
                $q->where('nome', 'LIKE', '%Tecnico%')
                    ->orWhere('nome', 'LIKE', '%Seguranca%');
            })->orderBy('nome')->get();
      
        $filial_id = $this->filial_id;

        $produtos = Produto::with(['estoque' => function ($query) use ($empresa_id, $filial_id) {
                $query->where('empresa_id', $empresa_id);
                
                // Garante que pega o estoque do local correto (Matriz ou Filial)
                if ($filial_id) {
                    $query->where('filial_id', $filial_id);
                } else {
                    $query->whereNull('filial_id');
                }
            }])
            ->where('empresa_id', $empresa_id)
            ->where('inativo', 0) // <-- CORRIGIDO AQUI PARA 0 (Produtos Ativos)
            ->whereHas('categoria', function ($query) {
                $query->where('nome', 'LIKE', '%EPI%');
            })
            ->orderBy('nome')
            ->get();

        return view('requisicoes.create', compact('funcionarios', 'tecnicos', 'produtos', 'title'));
    }

    public function store(Request $request)
    {
        if (!$request->has('produtos') || empty($request->produtos)) {
            return redirect()->back()->with('error', 'Adicione pelo menos um produto.');
        }

        return DB::transaction(function () use ($request) {
            $usuarioDigitador = $this->usuario_id ?? 1;

            // Permite usar a data informada ou a data atual (Retroativo)
            $dataRequisicao = $request->data_requisicao ? $request->data_requisicao . ' ' . date('H:i:s') : now();

            $requisicao = Requisicao::create([
                'empresa_id'     => $this->empresa_id,
                'filial_id'      => $this->filial_id,
                'funcionario_id' => $request->funcionario_id,
                'responsavel_id' => $request->tecnico_id,
                'usuario_id'     => $usuarioDigitador,
                'data_requisicao'=> $dataRequisicao,
                'status'         => 'Finalizado',
                'observacao'     => $request->observacao
            ]);

            foreach ($request->produtos as $item) {
                $produto = Produto::findOrFail($item['id']);

                // Baixa de estoque
                $queryEstoque = DB::table('estoques')
                    ->where('empresa_id', $this->empresa_id)
                    ->where('produto_id', $produto->id);

                if ($this->filial_id) {
                    $queryEstoque->where('filial_id', $this->filial_id);
                } else {
                    $queryEstoque->whereNull('filial_id');
                }

                $queryEstoque->decrement('quantidade', $item['qtd']);
                $saldoAtualizado = $queryEstoque->value('quantidade') ?? 0;

                DB::table('requisicao_itens')->insert([
                    'empresa_id'    => $this->empresa_id,
                    'filial_id'     => $this->filial_id,
                    'usuario_id'    => $usuarioDigitador,
                    'requisicao_id' => $requisicao->id,
                    'produto_id'    => $produto->id,
                    'quantidade'    => $item['qtd'],
                    'ca_snapshot'   => $item['ca'] ?? $item['ca_numero'] ?? $produto->ca_numero ?? $produto->ca,
                    'fabricante'    => $item['fabricante'] ?? $produto->fabricante ?? null,
                    'uso'           => $item['uso'] ?? null,
                    'motivo'        => $item['motivo'] ?? null,
                    'created_at'    => $dataRequisicao,
                    'updated_at'    => $dataRequisicao
                ]);

                // Registro no Kardex
                DB::table('stock_movements')->insert([
                    'empresa_id'      => $this->empresa_id,
                    'filial_id'       => $this->filial_id,
                    'usuario_id'      => $usuarioDigitador,
                    'produto_id'      => $produto->id,
                    'tipo'            => 'saida',
                    'quantidade'      => $item['qtd'],
                    'origem_tipo'     => 'Requisição EPI',
                    'origem_id'       => $requisicao->id,
                    'idempotency_key' => uniqid('req_'),
                    'movimentado_em'  => $dataRequisicao,
                    'saldo_momento'   => $saldoAtualizado,
                    'created_at'      => $dataRequisicao,
                    'contexto'        => ''
                ]);
            }

            return redirect()->route('requisicoes.index')->with('success', 'Requisição concluída!');
        });
    }

    /**
     * Método para Deletar com Verificação de Senha
     */
    public function destroy(Request $request, $id)
    {
        // 1. Busca a configuração da empresa
        $config = DB::table('config_notas')
            ->where('empresa_id', $this->empresa_id)
            ->first();

        // 2. Transforma a senha digitada em MD5 para comparar com o banco
        $senhaDigitadaCripto = md5(trim($request->senha_exclusao));

        // 3. Compara as duas versões criptografadas
        if (!$config || $senhaDigitadaCripto !== $config->senha_remover) {
            return redirect()->back()->with('error', 'Senha de exclusão incorreta!');
        }

        return DB::transaction(function () use ($id) {
            // ... restante do código de exclusão que você já tem ...
            $requisicao = Requisicao::where('empresa_id', $this->empresa_id)->findOrFail($id);

            // 2. Devolve os produtos ao estoque antes de excluir
            $itens = DB::table('requisicao_itens')->where('requisicao_id', $id)->get();
            foreach ($itens as $item) {
                DB::table('estoques')
                    ->where('empresa_id', $this->empresa_id)
                    ->where('produto_id', $item->produto_id)
                    ->increment('quantidade', $item->quantidade);

                // Remove registros do Kardex vinculados
                DB::table('stock_movements')
                    ->where('origem_tipo', 'Requisição EPI')
                    ->where('origem_id', $id)
                    ->delete();
            }

            // 3. Remove itens e a requisição
            DB::table('requisicao_itens')->where('requisicao_id', $id)->delete();
            $requisicao->delete();

            return redirect()->route('requisicoes.index')->with('success', 'Requisição excluída e estoque estornado!');
        });
    }

    public function show($id)
    {
        $title = "Detalhes da Requisição";
        $requisicao = Requisicao::with(['funcionario', 'responsavel', 'itens.produto'])
            ->where('empresa_id', $this->empresa_id)
            ->findOrFail($id);

        return view('requisicoes.show', compact('requisicao', 'title'));
    }

    public function imprimir($id)
    {
        // Busca a requisição com os itens, produtos e funcionário
        $item = \App\Models\Requisicao::with(['itens.produto', 'funcionario'])->findOrFail($id);

        // Busca os dados da empresa para o cabeçalho
        $config = \App\Models\ConfigNota::where('empresa_id', $this->empresa_id)->first();

        $p = [
            'item' => $item,
            'config' => $config
        ];

        // Renderiza a sua nova view 'relatorio.blade.php'
        $html = view('requisicoes.relatorio', $p)->render();

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="requisicao_'.$id.'.pdf"');
    }

    public function imprimirFichaFiltro(Request $request)
    {
        $data_inicial = $request->data_inicial;
        $data_final = $request->data_final;
        $funcionario_id = $request->funcionario_id;

        // CORREÇÃO AQUI: Adicionado 'funcionario.funcao' e 'responsavel'
        $requisicoes = \App\Models\Requisicao::with(['itens.produto', 'funcionario.funcao', 'responsavel'])
            ->where('empresa_id', $this->empresa_id)
            ->when($data_inicial, function ($q) use ($data_inicial) {
                return $q->whereDate('data_requisicao', '>=', $data_inicial);
            })
            ->when($data_final, function ($q) use ($data_final) {
                return $q->whereDate('data_requisicao', '<=', $data_final);
            })
            ->when($funcionario_id, function ($q) use ($funcionario_id) {
                return $q->where('funcionario_id', $funcionario_id);
            })
            ->orderBy('data_requisicao', 'asc')
            ->get();

        $config = \App\Models\ConfigNota::where('empresa_id', $this->empresa_id)->first();

        // Impede o erro se não selecionar funcionário
        if($requisicoes->isEmpty()){
            return redirect()->back()->with('error', 'Nenhuma requisição encontrada.');
        }
        $requisicoesAgrupadas = $requisicoes->groupBy('funcionario_id');

        $p = [
            'requisicoesAgrupadas' => $requisicoesAgrupadas,
            'config' => $config,
            'data_inicial' => $data_inicial,
            'data_final' => $data_final
        ];

        $html = view('requisicoes.pdf_ficha_consolidada', $p)->render();

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape'); // Garante que a folha fique deitada
        $dompdf->render();

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf');
    }

    // Método para o botão "Finalizar" que trava a edição
    public function finalizar($id)
    {
        $item = \App\Models\Requisicao::findOrFail($id);
        $item->status = 'Finalizado';
        $item->save();

        return redirect()->back()->with('success', 'Requisição finalizada com sucesso! A edição agora está bloqueada.');
    }

  
 	public function migrarHistorico(Request $request)
{
    // Verifique se a sua sessão realmente tem o 'empresa_id'
    // Para teste rápido, você pode forçar o número 2:
    $empresa_id = session('user_logged')['empresa_id'] ?? 2; 

    if (!$empresa_id || $empresa_id == 0) {
        return redirect()->back()->with('mensagem_erro', 'Erro: Empresa não identificada na sessão.');
    }

    try {
        \Maatwebsite\Excel\Facades\Excel::import(
            new \App\Imports\HistoricoEpiImport($empresa_id), 
            $request->file('file')
        );
        return redirect()->back()->with('mensagem_sucesso', 'Migração executada!');
    } catch (\Exception $e) {
        return redirect()->back()->with('mensagem_erro', 'Erro: ' . $e->getMessage());
    }
}
}
