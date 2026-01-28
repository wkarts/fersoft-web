<?php

namespace App\Http\Controllers\Ifood;

use Illuminate\Http\Request;
use App\Models\IfoodConfig;
use App\Models\CategoriaIfood;
use App\Restaurant\IfoodService;
use App\Http\Controllers\Controller;

class CategoryController extends Controller
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
        $result = $iFoodService->getCategories();

        if(isset($result->message) && $result->message == 'token expired'){
            session()->flash("mensagem_erro", "Token Expirado!");
            return redirect('/ifood/config');
        }else{
            //buscou as categorias

            // echo "<pre>";
            // print_r($result);
            // echo "</pre>";
            // die;
            foreach($result->elements as $item){


                $dataCategoria = [
                    'empresa_id' => $this->empresa_id,
                    'nome' => $item->name,
                    'imagem' => $item->image,
                    'id_ifood' => $item->id
                ];

                CategoriaIfood::updateOrCreate($dataCategoria);
            }

        }

    }
}
