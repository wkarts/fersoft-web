<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProducaoLote;

class ControleLoteController extends Controller
{
    protected $empresa_id = null;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->empresa_id = session('user_logged')['empresa'] ?? null;
            if (!$this->empresa_id) {
                return redirect("/login");
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $query = ProducaoLote::where('empresa_id', $this->empresa_id)
            ->with([
                'fornecedor',
                'produtoOrigem',
                'cortes.produto',
                'ticketsSaida.produto',
                'ticketsSaida.pesagem.cliente',
                'ticketsSaida.pesagem.venda'
            ]);

        if ($request->filled('pesquisa')) {
            $pesquisa = $request->pesquisa;
            $query->where(function($q) use ($pesquisa) {
                $q->where('codigo_lote', 'like', "%{$pesquisa}%")
                    ->orWhere('nfe_entrada', 'like', "%{$pesquisa}%")
                    ->orWhere('mtr_entrada', 'like', "%{$pesquisa}%")
                    ->orWhereHas('fornecedor', function($qf) use ($pesquisa) {
                        $qf->where('razao_social', 'like', "%{$pesquisa}%");
                    });
            });
        }

        $lotes = $query->orderBy('id', 'desc')->paginate(20);

        // 🚀 O SEGREDO ESTÁ AQUI: Agrupar as movimentações por Produto
        $lotes->getCollection()->transform(function ($lote) {
            $resumo = [];

            // Soma todas as Entradas e Saídas do PEPS agrupando pelo Produto
            foreach ($lote->cortes as $corte) {
                $pId = $corte->produto_gerado_id;
                if (!isset($resumo[$pId])) {
                    $resumo[$pId] = [
                        'nome'    => optional($corte->produto)->nome ?? "Produto #{$pId}",
                        'entrada' => 0,
                        'saida'   => 0,
                        'saldo'   => 0,
                    ];
                }
                $resumo[$pId]['entrada'] += (float) $corte->peso_liquido;
                $resumo[$pId]['saida']   += (float) $corte->quantidade_vendida; // 👈 A saída automática do PEPS está aqui!
                $resumo[$pId]['saldo']   += (float) $corte->saldo_disponivel;
            }

            $lote->resumo_produtos = collect($resumo)->values();
            return $lote;
        });

        return view('producao.controle_lotes', compact('lotes'));
    }
}
