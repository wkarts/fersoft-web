<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BairroDeliveryLoja;
use App\Models\BairroDelivery;
use App\Models\DeliveryConfig;
class BairroDeliveryLojaController extends Controller
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
        $config = DeliveryConfig::
        where('empresa_id', $this->empresa_id)
        ->first();

        if($config == null){
            session()->flash('mensagem_erro', 'Configure o delivery primeiro!');
            return redirect('/configDelivery');
        }

        $bairros = BairroDeliveryLoja::
        where('empresa_id', $this->empresa_id)
        ->orderBy('nome', 'asc')
        ->paginate(20);

        $bairrosDoSuper = BairroDelivery::
        where('cidade_id', $config->cidade_id)
        ->get();

        return view('bairros_loja/list')
        ->with('bairros', $bairros)
        ->with('bairrosDoSuper', $bairrosDoSuper)
        ->with('title', 'Bairros');
    }

    public function herdar(){
        $config = DeliveryConfig::
        where('empresa_id', $this->empresa_id)
        ->first();

        $bairrosDoSuper = BairroDelivery::
        where('cidade_id', $config->cidade_id)
        ->get();

        foreach($bairrosDoSuper as $b){
            $item = [
                'empresa_id' => $this->empresa_id,
                'nome' => $b->nome,
                'valor_entrega' => $b->valor_entrega
            ];

            BairroDeliveryLoja::create($item);
        }

        session()->flash('mensagem_sucesso', 'Bairros cadastrados para sua configuração de delivery!');
        return redirect('/bairrosDeliveryLoja');
    }

    public function new(){

        return view('bairros_loja/register')
        ->with('title', 'Cadastrar Bairro');
    }

    public function save(Request $request){
        $bairro = new BairroDeliveryLoja();
        $this->_validate($request);

        $request->merge(['valor_entrega' => str_replace(",", ".", $request->valor_entrega)]);

        $result = $bairro->create($request->all());

        if($result){
            session()->flash("mensagem_sucesso", "Bairro cadastrado com sucesso.");
        }else{
            session()->flash('mensagem_erro', 'Erro ao cadastrar bairro.');

        }

        return redirect('/bairrosDeliveryLoja');
    }

    public function edit($id){
        $bairro = new BairroDeliveryLoja(); 

        $resp = $bairro
        ->where('id', $id)->first();

        return view('bairros_loja/register')
        ->with('bairro', $resp)
        ->with('title', 'Editar Bairro');

    }

    public function update(Request $request){
        $bairro = new BairroDeliveryLoja();
        $request->merge(['valor_entrega' => str_replace(",", ".", $request->valor_entrega)]);
        
        $id = $request->input('id');
        $resp = $bairro
        ->where('id', $id)->first(); 

        $this->_validate($request);

        $resp->nome = $request->input('nome');
        $resp->valor_entrega = $request->input('valor_entrega');

        $result = $resp->save();
        if($result){
            session()->flash('mensagem_sucesso', 'Bairro editado com sucesso!');
        }else{
            session()->flash('mensagem_erro', 'Erro ao editar bairro!');
        }

        return redirect('/bairrosDeliveryLoja'); 
    }

    public function delete($id){

        $delete = BairroDeliveryLoja
        ::where('id', $id)
        ->delete();
        if($delete){
            session()->flash('mensagem_sucesso', 'Registro removido!');
        }else{
            session()->flash('mensagem_erro', 'Erro!');
        }
        return redirect('/bairrosDeliveryLoja');

    }


    private function _validate(Request $request){
        $rules = [
            'nome' => 'required|max:50',
            'valor_entrega' => 'required',
        ];

        $messages = [
            'nome.required' => 'O campo nome é obrigatório.',
            'nome.max' => '50 caracteres maximos permitidos.',
            'valor_entrega.required' => 'O campo valor de entrega é obrigatório.',
            'cidade_id.required' => 'O campo cidade é obrigatório.',

        ];
        $this->validate($request, $rules, $messages);
    }
}
