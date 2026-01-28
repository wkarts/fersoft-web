<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DestaqueDelivery;
use App\Models\DeliveryConfig;
use Illuminate\Support\Str;

class DestaqueDeliveryController extends Controller
{
    protected $empresa_id = null;
    public function __construct(){
        $this->middleware(function ($request, $next) {
            $this->empresa_id = $request->empresa_id;
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
        $data = DestaqueDelivery::
        orderBy('status', 'desc')
        ->orderBy('ordem')
        ->paginate(10);

        $lojas = DeliveryConfig::orderBy('nome')->get();

        return view('destaqueDelivery/list')
        ->with('data', $data)
        ->with('lojas', $lojas)
        ->with('links', true)
        ->with('title', 'Destaques Delivery');
    }

    public function pesquisa(Request $request){
        $data = DestaqueDelivery::
        orderBy('status', 'desc')
        ->orderBy('ordem');

        if($request->loja_id){
            $loja = DeliveryConfig::find($request->loja_id);
            $data->where('empresa_id', $loja->empresa_id);
        }

        if($request->status){
            if($request->status == 1){
                $data->where('status', 1);
            }else{
                $data->where('status', 0);
            }
        }

        $data = $data->get();

        $lojas = DeliveryConfig::orderBy('nome')->get();

        return view('destaqueDelivery/list')
        ->with('data', $data)
        ->with('lojas', $lojas)
        ->with('status', $request->status)
        ->with('loja_id', $request->loja_id)
        ->with('title', 'Destaques Delivery');
    }

    public function new(){

        $lojas = DeliveryConfig::orderBy('nome')->get();

        return view('destaqueDelivery/register')
        ->with('lojas', $lojas)
        ->with('title', 'Cadastrar Destaque');
    }

    public function save(Request $request){

        if(!is_dir(public_path('destaques_delivery'))){
            mkdir(public_path('destaques_delivery'), 0777, true);
        }

        $loja = null;
        if($request->loja_id != "null"){
            $loja = DeliveryConfig::find($request->loja_id);
        }
        $request->merge([
            'loja_id' => $loja != null ? $loja->empresa_id : null,
            'produto_id' => $request->produto_id ? $request->produto_id : null,
            'status' => $request->input('status') ? true : false,
            'ordem' => $request->ordem ?? 0
        ]);

        if(!$request->hasFile('file')){
            session()->flash('mensagem_erro', 'Obrigatório informar a imagem.');
            return redirect()->back();
        }else{
            $file = $request->file('file');

            $extensao = $file->getClientOriginalExtension();
            $fileName = Str::random(20) . "." . $extensao;
            $upload = $file->move(public_path('destaques_delivery'), $fileName);
            $request->merge(['img' => $fileName]);

        }

        $result = DestaqueDelivery::create($request->all());

        if($result){
            session()->flash("mensagem_sucesso", "Destaque cadastrado com sucesso.");
        }else{
            session()->flash('mensagem_erro', 'Erro ao cadastrar destaque.');
        }

        return redirect('/destaquesDelivery');
    }

    public function edit($id){

        $item = DestaqueDelivery::find($id); 

        $lojas = DeliveryConfig::orderBy('nome')->get();

        return view('destaqueDelivery/register')
        ->with('item', $item)
        ->with('lojas', $lojas)
        ->with('title', 'Editar Destaque');

    }

    public function update(Request $request){

        $item = DestaqueDelivery::findOrFail($request->id);
        try{
            $loja = null;
            if($request->loja_id != "null"){
                $loja = DeliveryConfig::find($request->loja_id);
            }
            $request->merge([
                'loja_id' => $loja != null ? $loja->empresa_id : null,
                'produto_id' => $request->produto_id ? $request->produto_id : null,
                'status' => $request->input('status') ? true : false,
                'ordem' => $request->ordem ?? 0
            ]);

            if($request->hasFile('file')){

                if(file_exists(public_path('destaques_delivery/').$item->img)){
                    unlink(public_path('destaques_delivery/').$item->img);
                }

                $file = $request->file('file');

                $extensao = $file->getClientOriginalExtension();
                $fileName = Str::random(20) . "." . $extensao;
                $upload = $file->move(public_path('destaques_delivery'), $fileName);

                $request->merge(['img' => $fileName]);

            }

            $item->fill($request->all())->save();

            session()->flash('mensagem_sucesso', 'Destaque editado com sucesso!');
        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Erro ao editar destaque!');
        }

        return redirect('/destaquesDelivery'); 
    }

    public function alterarStatus($id){
        try{
            $item = DestaqueDelivery::find($id);
            $item->status = !$item->status;
            $item->save();
            return response()->json($item, 200);

        }catch(\Exception $e){
            return response($e->getMessage(), 401);
        }
    }

    public function delete($id){
        try{
            $item = DestaqueDelivery::find($id);
            if(file_exists(public_path('destaques_delivery/').$item->img)){
                unlink(public_path('destaques_delivery/').$item->img);
            }

            $item->delete();
            session()->flash('mensagem_sucesso', 'Registro removido!');

        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());

        }
        return redirect('/destaquesDelivery');

    }
}
