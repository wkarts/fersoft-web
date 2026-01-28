<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContaPagar;
use App\Models\ConfigNota;
use Dompdf\Dompdf;

class RetencaoController extends Controller
{

    protected $empresa_id = null;

    public function __construct(){

        $this->middleware(function ($request, $next) {
            $this->empresa_id = $request->empresa_id;
            $value = session('user_logged');
            if(!$value){
                return redirect("/login");
            }
            return $next($request);
        });
    }

    public function index(Request $request){

        $fornecedor = $request->fornecedor;
        $data_inicio = $request->data_inicio;
        $data_final = $request->data_final;
        $data = ContaPagar::where('conta_pagars.empresa_id', $this->empresa_id)
        ->select('conta_pagars.*')
        ->where(function($query) {
            return $query->orWhere('conta_pagars.valor_inss', '>', 0)
            ->orWhere('conta_pagars.valor_iss', '>', 0)
            ->orWhere('conta_pagars.valor_pis', '>', 0)
            ->orWhere('conta_pagars.valor_cofins', '>', 0)
            ->orWhere('conta_pagars.valor_ir', '>', 0)
            ->orWhere('conta_pagars.outras_retencoes', '>', 0);
        })
        ->when(!empty($data_inicio), function ($query) use ($data_inicio) {
            return $query->whereDate('conta_pagars.created_at', '>=', $data_inicio);
        })
        ->when(!empty($data_final), function ($query) use ($data_final) {
            return $query->whereDate('conta_pagars.created_at', '<=', $data_final);
        })
        ->join('fornecedors', 'fornecedors.id' , '=', 'conta_pagars.fornecedor_id')
        ->when($fornecedor, function ($query) use ($fornecedor) {
            return $query->where('fornecedors.razao_social', 'LIKE', "%$fornecedor%");
        })
        ->paginate(30);

        return view('retencoes.index', compact('data'));
    }

    public function print(Request $request){
        $fornecedor = $request->fornecedor;
        $data_inicio = $request->data_inicio;
        $data_final = $request->data_final;
        $data = ContaPagar::where('conta_pagars.empresa_id', $this->empresa_id)
        ->select('conta_pagars.*')
        ->where(function($query) {
            $query->where('conta_pagars.valor_inss', '>', 0)
            ->orWhere('conta_pagars.valor_iss', '>', 0)
            ->orWhere('conta_pagars.valor_pis', '>', 0)
            ->orWhere('conta_pagars.valor_cofins', '>', 0)
            ->orWhere('conta_pagars.valor_ir', '>', 0)
            ->orWhere('conta_pagars.outras_retencoes', '>', 0);
        })
        ->when(!empty($data_inicio), function ($query) use ($data_inicio) {
            return $query->whereDate('conta_pagars.created_at', '>=', $data_inicio);
        })
        ->when(!empty($data_final), function ($query) use ($data_final) {
            return $query->whereDate('conta_pagars.created_at', '<=', $data_final);
        })
        ->join('fornecedors', 'fornecedors.id' , '=', 'conta_pagars.fornecedor_id')
        ->when($fornecedor, function ($query) use ($fornecedor) {
            return $query->where('fornecedors.razao_social', 'LIKE', "%$fornecedor%");
        })
        ->get();

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $p = view('retencoes.print', compact('data', 'config'));
        // return $p;

        $domPdf = new Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);

        $pdf = ob_get_clean();

        $domPdf->setPaper("A4", "landscape");
        $domPdf->render();
        $domPdf->stream("Retenções.pdf", array("Attachment" => false));
    }
}
