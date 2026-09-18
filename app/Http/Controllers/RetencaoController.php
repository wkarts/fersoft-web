<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContaPagar;
use App\Models\ConfigNota;
use App\Models\Fornecedor;
use App\Models\CategoriaConta;
use Dompdf\Dompdf;

class RetencaoController extends Controller
{
    protected $empresa_id = null;

    public function __construct(){
        $this->middleware(function ($request, $next) {
            $value = session('user_logged');
            if(!$value){
                return redirect("/login");
            }
            $this->empresa_id = $value['empresa'];
            return $next($request);
        });
    }

    public function index(Request $request){
    $emp_id = session('user_logged')['empresa'];
    $filial_id = $request->input('filial_id'); // Pega o id do select
    $fornecedor = $request->fornecedor;
    
    // 🌟 NOVO: Data Padrão (Mês Anterior) caso o usuário não tenha feito uma pesquisa
    $data_inicio = $request->input('data_inicio', now()->subMonth()->startOfMonth()->format('Y-m-d'));
    $data_final  = $request->input('data_final', now()->subMonth()->endOfMonth()->format('Y-m-d'));

    // Busca as filiais cadastradas
    $empresas = \App\Models\Filial::where('empresa_id', $emp_id)->get();

    $query = ContaPagar::where('conta_pagars.empresa_id', $emp_id)
        ->select('conta_pagars.*')
        ->where(function($q) {
            return $q->where('conta_pagars.valor_inss', '>', 0)
                ->orWhere('conta_pagars.valor_iss', '>', 0)
                ->orWhere('conta_pagars.valor_pis', '>', 0)
                ->orWhere('conta_pagars.valor_cofins', '>', 0)
                ->orWhere('conta_pagars.valor_ir', '>', 0)
                ->orWhere('conta_pagars.valor_csll', '>', 0)
                ->orWhere('conta_pagars.outras_retencoes', '>', 0);
        });

    // 🌟 NOVO: Filtro da Matriz (Idêntico ao seu outro controller)
    if (!empty($filial_id)) {
        if ($filial_id == 'matriz') {
            $query->whereNull('conta_pagars.filial_id');
        } else {
            $query->where('conta_pagars.filial_id', $filial_id);
        }
    }

    // Filtro por Data (Sempre vai ter dados, pois setamos o mês anterior)
    if($data_inicio && $data_final){
        $query->whereBetween('conta_pagars.data_emissao', [$data_inicio, $data_final]);
    }

    $query->join('fornecedors', 'fornecedors.id' , '=', 'conta_pagars.fornecedor_id')
        ->when($fornecedor, function ($q) use ($fornecedor) {
            return $q->where('fornecedors.razao_social', 'LIKE', "%$fornecedor%");
        });

    $data = $query->orderBy('conta_pagars.data_emissao', 'desc')->paginate(30);
    $data->appends($request->all()); // Mantém os filtros ao mudar de página!

    $fornecedores = Fornecedor::where('empresa_id', $emp_id)->orderBy('razao_social', 'asc')->get();
    $categorias = CategoriaConta::where('empresa_id', $emp_id)->where('tipo', 'pagar')->orderBy('nome', 'asc')->get();
    
    // Enviando as datas padrões para preencher a tela
    return view('retencoes.index', compact('data', 'fornecedores', 'categorias', 'empresas', 'data_inicio', 'data_final', 'filial_id'));
}
  
    public function print(Request $request){
        $fornecedor = $request->fornecedor;
        $data_inicio = $request->data_inicio;
        $data_final = $request->data_final;

        $query = ContaPagar::where('conta_pagars.empresa_id', $this->empresa_id)
            ->select('conta_pagars.*')
            ->where(function($q) {
                $q->where('conta_pagars.valor_inss', '>', 0)
                    ->orWhere('conta_pagars.valor_iss', '>', 0)
                    ->orWhere('conta_pagars.valor_pis', '>', 0)
                    ->orWhere('conta_pagars.valor_cofins', '>', 0)
                    ->orWhere('conta_pagars.valor_ir', '>', 0)
                    ->orWhere('conta_pagars.valor_csll', '>', 0) // Adicionado CSLL[cite: 4]
                    ->orWhere('conta_pagars.outras_retencoes', '>', 0);
            });

        // ALTERADO: Relatório também por DATA DE EMISSÃO[cite: 4]
        if($data_inicio && $data_final){
            $query->whereBetween('conta_pagars.data_emissao', [$data_inicio, $data_final]);
        }

        $query->join('fornecedors', 'fornecedors.id' , '=', 'conta_pagars.fornecedor_id')
            ->when($fornecedor, function ($q) use ($fornecedor) {
                return $q->where('fornecedors.razao_social', 'LIKE', "%$fornecedor%");
            });

        $data = $query->get();

        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();

        $p = view('retencoes.print', compact('data', 'config'));

        $domPdf = new Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);
        $domPdf->setPaper("A4", "landscape");
        $domPdf->render();
        $domPdf->stream("Retenções.pdf", array("Attachment" => false));
    }
}