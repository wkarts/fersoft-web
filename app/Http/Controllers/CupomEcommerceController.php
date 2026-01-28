<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CupomDescontoEcommerce;

class CupomEcommerceController extends Controller
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
        $data = CupomDescontoEcommerce::
        where('empresa_id', $this->empresa_id)
        ->paginate(30);

        return view('cupom_ecommerce/index')
        ->with('title', 'Cupons de desconto')
        ->with('data', $data);
    }

    public function create(){
        return view('cupom_ecommerce/register')
        ->with('title', 'Novo cupom');
    }

    public function edit($id){
        $codigo = CupomDescontoEcommerce::findOrFail($id);
        return view('cupom_ecommerce/register', compact('codigo'))
        ->with('title', 'Editar cupom');
    }

    public function store(Request $request){
        $this->_validate($request);
        try{
            $request->merge([
                'valor' => __replace($request->valor),
                'valor_minimo_pedido' => __replace($request->valor_minimo_pedido),
                'status' => $request->status ? true : false
            ]);
            CupomDescontoEcommerce::create($request->all());
            session()->flash('mensagem_sucesso', 'Cupom adicionado!');
        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado!');
        }
        return redirect('/cuponsEcommerce');

    }

    public function update(Request $request, $id){

        $this->_validate($request);
        $item = CupomDescontoEcommerce::findOrFail($id);

        try{
            $request->merge([
                'valor' => __replace($request->valor),
                'valor_minimo_pedido' => __replace($request->valor_minimo_pedido),
                'status' => $request->status ? true : false
            ]);
            $item->fill($request->all())->save();
            session()->flash('mensagem_sucesso', 'Cupom atualizado!');
        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado!');
        }
        return redirect('/cuponsEcommerce');

    }

    public function delete($id){
        $item = CupomDescontoEcommerce::findOrFail($id);
        try{
            $item->delete();
            session()->flash('mensagem_sucesso', 'Cupom removido!');
        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado!');
        }
        return redirect('/cuponsEcommerce');
    }

    private function _validate(Request $request){
        // print_r($request->todos);
        // die;
        $rules = [
            'valor' => 'required',
            'codigo' => 'required',
            'descricao' => 'required|max:100',
            'valor' => 'required',
            'valor_minimo_pedido' => 'required',
        ];

        $messages = [
            'descricao.required' => 'O campo descrição é obrigatório.',
            'descricao.max' => 'máximo de 100 caracteres.',
            'valor.required' => 'O campo valor é obrigatório.',
            'valor_minimo_pedido.required' => 'O campo valor mínimo é obrigatório.',
            'cliente_id.required' => 'O campo cliente é obrigatório.',
            'codigo.required' => 'O campo código é obrigatório.',
        ];
        $this->validate($request, $rules, $messages);
    }
}
