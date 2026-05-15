<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SpedRegra1400;

class SpedRegra1400Controller extends Controller
{
    protected $empresa_id = null;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->empresa_id = $request->empresa_id;
            $value = session('user_logged');
            if(!$value){
                return redirect("/login");
            }
            return $next($request);
        });
    }

    public function store(Request $request)
    {
        try {
            SpedRegra1400::create([
                'empresa_id' => $this->empresa_id,
                'cfop'       => preg_replace('/[^0-9]/', '', $request->cfop),
                'codigo_ipm' => strtoupper(trim($request->codigo_ipm)),
                'descricao'  => $request->descricao
            ]);

            session()->flash("mensagem_sucesso", "Regra 1400 cadastrada com sucesso!");
        } catch (\Exception $e) {
            session()->flash("mensagem_erro", "Erro ao cadastrar regra: " . $e->getMessage());
        }

        return redirect()->back();
    }

    public function destroy($id)
    {
        try {
            $regra = SpedRegra1400::where('empresa_id', $this->empresa_id)->findOrFail($id);
            $regra->delete();
            session()->flash("mensagem_sucesso", "Regra 1400 removida com sucesso!");
        } catch (\Exception $e) {
            session()->flash("mensagem_erro", "Erro ao remover regra: " . $e->getMessage());
        }

        return redirect()->back();
    }
}