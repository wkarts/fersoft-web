<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContaFinanceira;
use App\Models\CategoriaContaFinanceira;

class ContaFinanceiraController extends Controller
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

    public function index(){
        $contas = ContaFinanceira::
        where('empresa_id', $this->empresa_id)
        ->get();

        return view('conta_financeira/index')
        ->with('contas', $contas)
        ->with('title', 'Contas Financeira');
    }

    public function new(){
        $categorias = CategoriaContaFinanceira::
        where('empresa_id', $this->empresa_id)
        ->get();

        return view('conta_financeira/register')
        ->with('categorias', $categorias)
        ->with('title', 'Cadastrar Conta Financeira');
    }
}
