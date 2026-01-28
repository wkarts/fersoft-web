<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VideoAjuda;
class VideoController extends Controller
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
        $data = VideoAjuda::all();
        return view('videos.index', compact('data'))
        ->with('title', 'Vídeos');
    }

    public function store(Request $request){
        try{
            if($request->id > 0){
                $item = VideoAjuda::findOrFail($request->id);
                $item->fill($request->all())->save();
                session()->flash("mensagem_sucesso", "Vídeo atualizado!");
            }else{
                VideoAjuda::create($request->all());
                session()->flash("mensagem_sucesso", "Vídeo cadastrado!");
            }
        }catch(\Exception $e){
            session()->flash("mensagem_erro", "Erro ao cadastrar vídeo!");
        }
        return redirect()->back();

    }

    public function delete($id){
        VideoAjuda::findOrFail($id)->delete();
        session()->flash("mensagem_sucesso", "Vídeo removido!");
        return redirect()->back();
    }
}
