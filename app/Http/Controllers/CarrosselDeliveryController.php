<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CarrosselDelivery;
use Illuminate\Support\Str;

class CarrosselDeliveryController extends Controller
{
    protected $empresa_id = null;
    public function __construct(){
        if(!is_dir(public_path('carrossel_delivery'))){
            mkdir(public_path('carrossel_delivery'), 0777, true);
        }
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
        $data = CarrosselDelivery::
        where('empresa_id', $this->empresa_id)
        ->orderBy('status', 'desc')
        ->orderBy('valor_ordem', 'desc')
        ->orderBy('created_at', 'desc')
        ->get();

        return view('carroselDelivery/index')
        ->with('data', $data)
        ->with('title', 'Carrossel de Delivery');
    }

    public function save(Request $request){
        try{
            if($request->hasFile('file')){
                $file = $request->file('file');
                $extensao = $file->getClientOriginalExtension();

                $fileName = Str::random(20) . ".$extensao";

                $file->move(public_path('carrossel_delivery'), $fileName);

                $last = CarrosselDelivery::where('empresa_id', $this->empresa_id)
                ->where('status', 1)
                ->orderBy('valor_ordem', 'desc')
                ->first();
                CarrosselDelivery::create([
                    'empresa_id' => $this->empresa_id,
                    'path' => $fileName,
                    'status' => 1,
                    'valor_ordem' => $last != null ? $last->valor_ordem : -10
                ]);
                session()->flash('mensagem_sucesso', 'Imagem cadastrada!');
            }
        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
        }
        return redirect()->back();
    }

    public function delete($id){
        $item = CarrosselDelivery::findOrFail($id);
        if(valida_objeto($item)){
            try{
                if(file_exists(public_path('carrossel_delivery/').$item->path)){
                    unlink(public_path('carrossel_delivery/').$item->path);
                }

                $item->delete();
                session()->flash('mensagem_sucesso', 'imagem removida!');
            }catch(\Exception $e){
                session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
            }
            return redirect()->back();

        }else{
            return redirect('/403');
        }
    }

    public function up($id){
        $item = CarrosselDelivery::findOrFail($id);

        $prox = CarrosselDelivery::where('empresa_id', $this->empresa_id)
        ->where('valor_ordem', '>', $item->valor_ordem)
        ->where('status', $item->status)
        ->orderBy('valor_ordem', 'desc')
        ->first();

        $item->valor_ordem = $prox != null ? $prox->valor_ordem+1 : $item->valor_ordem+1;
        
        $item->save();

        return redirect()->back();
    }

    public function down($id){
        $item = CarrosselDelivery::findOrFail($id);

        $prox = CarrosselDelivery::where('empresa_id', $this->empresa_id)
        ->where('valor_ordem', '<', $item->valor_ordem)
        ->where('status', $item->status)
        ->orderBy('valor_ordem', 'desc')
        ->first();

        $item->valor_ordem = $prox != null ? $prox->valor_ordem-1 : $item->valor_ordem-1;
        $item->save();

        return redirect()->back();
    }

    //ajax
    public function alteraStatus($id){
        $item = CarrosselDelivery::findOrFail($id);

        $item->status = !$item->status;
        $item->save();
    }
}
