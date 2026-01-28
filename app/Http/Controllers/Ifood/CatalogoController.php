<?php

namespace App\Http\Controllers\Ifood;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\IfoodConfig;
use App\Restaurant\IfoodService;

class CatalogoController extends Controller
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

        $item = IfoodConfig::
        where('empresa_id', $this->empresa_id)
        ->first();

        if($item == null){
            session()->flash("mensagem_erro", "Configure o App");
            return redirect('/ifood/config');
        }

        $iFoodService = new IfoodService($item);
        $data = $iFoodService->getCatalogs();

        if(isset($data->message)){
            session()->flash("mensagem_erro", $data->message);
            return redirect('/ifood/config');
        }
        return view('catalogo_ifood/index', compact('data'))
        ->with('config', $item)
        ->with('title', 'Catálogos iFood');

    }

    public function setCatalogo($id){
        $item = IfoodConfig::
        where('empresa_id', $this->empresa_id)
        ->first();

        $item->catalogId = $id;
        $item->save();
        session()->flash("mensagem_sucesso", "Catálogo definido!");
        return redirect()->back();
    }
}
