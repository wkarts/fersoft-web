<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Requisicao;
use App\Models\Produto;
use App\Models\Funcionario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class RequisicaoController extends Controller
{
    protected $empresa_id = null;

    public function __construct()
    {
        // Trava de segurança compatível com o seu ERP
        $this->middleware(function ($request, $next) {
            $sessionData = session('user_logged');
            
            if (!$sessionData) {
                return redirect("/login");
            }
            
            // Define o ID da empresa vindo da sessão personalizada
            $this->empresa_id = $sessionData['empresa_id'] ?? $request->empresa_id ?? 2;
            
            return $next($request);
        });
    }

    public function index()
{
    $requisicoes = Requisicao::with(['funcionario', 'responsavel'])
        ->where('empresa_id', $this->empresa_id)
        ->orderBy('data_requisicao', 'desc')
        ->paginate(15);

    // Adicionamos o 'title' no compact
    $title = "Histórico de Requisições";
    return view('requisicoes.index', compact('requisicoes', 'title'));
}

public function create()
{
    $sessionData = session('user_logged');
    $empresa_id = $this->empresa_id;

    $funcionarios = Funcionario::where('empresa_id', $empresa_id)->orderBy('nome')->get();
    
    // Filtra produtos apenas onde o nome da categoria contenha "EPI"
    $produtos = Produto::where('empresa_id', $this->empresa_id)
    ->whereHas('categoria', function ($query) {
        $query->where('nome', 'LIKE', '%EPI%');
    })
    ->orderBy('nome')
    ->get();

    return view('requisicoes.create', [
        'funcionarios' => $funcionarios,
        'produtos'     => $produtos,
        'title'        => 'Nova Requisição de Material/EPI',
        'usuario'      => $sessionData,
        'config'       => session('config')
    ]);
}
  
 
    public function store(Request $request)
{
    // Validação básica
    if (!$request->has('produtos') || empty($request->produtos)) {
        return redirect()->back()->with('error', 'Adicione pelo menos um produto.');
    }

    return DB::transaction(function () use ($request) {
        
        $funcionario = Funcionario::findOrFail($request->funcionario_id);
        $user_logado = session('user_logged');

        // 1. Cria a Requisição (Cabeçalho)
        $requisicao = Requisicao::create([
            'empresa_id'     => $this->empresa_id,
            'funcionario_id' => $funcionario->id,
            'responsavel_id' => $user_logado['id'] ?? 1, // Pega o ID do usuário da sessão
            'unidade'        => $funcionario->unidade ?? 'Matriz',
            'data_requisicao'=> now(),
            'observacao'     => $request->observacao,
            'status'         => 'Finalizado'
        ]);

        // 2. Salva os Itens e Baixa o Estoque
        foreach ($request->produtos as $item) {
            $produto = Produto::findOrFail($item['id']);

            // Grava na tabela correta: requisicao_itens
            DB::table('requisicao_itens')->insert([
                'requisicao_id' => $requisicao->id,
                'produto_id'    => $produto->id,
                'quantidade'    => $item['qtd'],
                'uso'           => $item['uso'] ?? '2',     // Vem do formulário
                'motivo'        => $item['motivo'] ?? 'S',  // Vem do formulário
                'created_at'    => now(),
                'updated_at'    => now()
            ]);

            // Baixa o estoque central na tabela de produtos
            $produto->decrement('estoque_atual', $item['qtd']);
            
            // Obs: Se você tiver uma tabela separada só para extrato/kardex (ex: stock_movimento),
            // você pode adicionar um insert nela aqui depois, se achar necessário.
        }

        return redirect()->route('requisicoes.index')->with('success', 'Requisição finalizada e estoque atualizado!');
    });
}
  
  public function imprimir($id)
{
    $requisicao = Requisicao::with(['funcionario', 'responsavel', 'itens.produto'])->findOrFail($id);
    
    // Assegure-se de que a empresa esteja correta. Puxando dados fictícios baseados no PDF.
    $empresa = "METAL SUCA LTDA"; // Você pode puxar do DB
    
    $pdf = \PDF::loadView('requisicoes.pdf', compact('requisicao', 'empresa'));
    
    return $pdf->stream("Ficha_EPI_{$requisicao->id}.pdf");
}
}