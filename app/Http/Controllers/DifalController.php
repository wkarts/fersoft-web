<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Difal;

class DifalController extends Controller
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

        $data = Difal::where('empresa_id', $this->empresa_id)->get();
        return view('difal.index', compact('data'))->with('title', 'Difal');
    }

    public function create(){
        return view('difal.register')->with('title', 'Cadastrar Difal');
    }

    public function edit($id){
        $item = Difal::findOrFail($id);
        return view('difal.register', compact('item'))->with('title', 'Cadastrar Difal');
    }

    public function store(Request $request){
        try{
            Difal::create(
                [
                    'empresa_id' => $this->empresa_id,
                    'uf' => $request->uf,
                    'cfop' => $request->cfop,
                    'pICMSUFDest' => str_replace(",", ".", $request->pICMSUFDest),
                    'pICMSInter' => str_replace(",", ".", $request->pICMSInter),
                    'pICMSInterPart' => str_replace(",", ".", $request->pICMSInterPart),
                    'pFCPUFDest' => str_replace(",", ".", $request->pFCPUFDest),
                ]
            );
            session()->flash("mensagem_sucesso", "Configurado com sucesso!");

        }catch(\Exception $e){
            session()->flash("mensagem_erro", "Algo deu errado: " . $e->getMessage());
        }

        return redirect()->route('difal.index');
    }

    public function update(Request $request, $id){
        try{
            $item = Difal::findOrFail($id);
            $item->cfop = $request->cfop;
            $item->uf = $request->uf;
            $item->pICMSUFDest = str_replace(",", ".", $request->pICMSUFDest);
            $item->pICMSInter = str_replace(",", ".", $request->pICMSInter);
            $item->pICMSInterPart = str_replace(",", ".", $request->pICMSInterPart);
            $item->pFCPUFDest = str_replace(",", ".", $request->pFCPUFDest);
            $item->save();
            session()->flash("mensagem_sucesso", "Editado com sucesso!");

        }catch(\Exception $e){
            session()->flash("mensagem_erro", "Algo deu errado: " . $e->getMessage());
        }

        return redirect()->route('difal.index');
    }

    public function destroy($id){
        try{

            $item = Difal::findOrFail($id);
            $item->delete();
            session()->flash('mensagem_sucesso', 'Registro removido!');

        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
        }
        return redirect()->back();
    }
}
