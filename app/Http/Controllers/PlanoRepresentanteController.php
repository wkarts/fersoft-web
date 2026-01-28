<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PlanoEmpresaRepresentante;
use App\Models\PlanoEmpresa;
use App\Models\Empresa;

class PlanoRepresentanteController extends Controller
{
    public function __construct(){
        $this->middleware(function ($request, $next) {
            $value = session('user_logged');
            if(!$value){
                return redirect("/login");
            }

            if(!$value['super']){
                return redirect('/graficos');
            }
            return $next($request);
        });
    }

    public function index(){
        $planos = PlanoEmpresaRepresentante::
        orderBy('id', 'desc')
        ->get();

        return view('empresas/planos_pendentes')
        ->with('planos', $planos)
        ->with('title', 'Planos pendentes');
    }

    public function ativar($id){
        $p = PlanoEmpresaRepresentante::find($id);
        try{
            $data = [
                'empresa_id' => $p->empresa_id,
                'plano_id' => $p->plano_id,
                'expiracao' => $p->expiracao,
                'mensagem_alerta' => ''
            ];

            $empresa = Empresa::find($p->empresa_id);
            $plano = $empresa->planoEmpresa;
            if($plano != null){
                $plano->delete();
            }
            PlanoEmpresa::create($data);
            $p->delete();

            session()->flash("mensagem_sucesso", "Plano ativado!");
        }catch(\Exception $e){
            session()->flash("mensagem_erro", "Algo deu errado, " . $e->getMessage());
        }
        return redirect()->back();
    }

    public function delete($id){
        $p = PlanoEmpresaRepresentante::find($id);
        $p->delete();
        session()->flash("mensagem_sucesso", "Plano removido!");
        return redirect()->back();

    }

}
