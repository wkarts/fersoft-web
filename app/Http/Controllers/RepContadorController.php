<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contador;
use App\Models\Cidade;
use App\Models\Empresa;
use App\Models\Usuario;
use App\Models\Representante;
use App\Helpers\Menu;

class RepContadorController extends Controller
{
    protected $usuario_id = null;

    public function __construct(){
        $this->middleware(function ($request, $next) {

            $value = session('user_logged');
            if(!$value){
                return redirect("/login");
            }

            if(!$value['super'] && !$value['tipo_representante']){
                return redirect('/graficos');
            }

            $this->usuario_id = $value['id'];
            return $next($request);
        });
    }

    public function index(Request $request){

        $data = Contador::where('representante_id', $this->usuario_id)
        ->orderBy('id', 'desc')
        ->get();

        return view('rep_contador.index', compact('data'))
        ->with('title', 'Contadores/Parceiros');
    }

    public function create(){
        $cidades = Cidade::all();

        return view('rep_contador.register', compact('cidades'))
        ->with('title', 'Cadastrar Contador');
    }

    public function edit($id){
        $item = Contador::findOrFail($id);
        $cidades = Cidade::all();

        return view('rep_contador.register', compact('item', 'cidades'))
        ->with('title', 'Editar Contadores');
    }

    public function update(Request $request, $id){
        $item = Contador::findOrFail($id);
        try{
            $request->merge([
                'chave_pix' => $request->chave_pix ?? ''
            ]);
            $item->fill($request->all())->save();

            $empresa = $item->empresa;
            $empresa->representante_legal = $request->representante_legal ?? '';
            $empresa->save();
            session()->flash("mensagem_sucesso", 'Contador atualizado!');
            return redirect()->route('rep-parceiro.index');
        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function store(Request $request){
        $this->_validate($request);

        try{
            $cidade = Cidade::findOrFail($request->cidade_id);
            $permissoesTodas = json_encode($this->permissoesTodas());

            $rep = Representante::where('usuario_id', $this->usuario_id)->first();

            $arr = [
                'nome' => $request->razao_social,
                'rua' => '',
                'numero' => '',
                'bairro' => '',
                'cidade' => $cidade->nome,
                'telefone' => $request->fone,
                'email' => $request->email,
                'cnpj' => $request->cnpj,
                'status' => 1,
                'permissao' => $permissoesTodas,
                'tipo_contador' => 1,
                'pix' => $request->chave_pix ?? '',
                'representante_legal' => $request->representante_legal ?? '',
            ];

            $empresa = Empresa::create($arr);

            $data = [
                'nome' => $request->nome_usuario, 
                'senha' => md5($request->senha),
                'login' => $request->login,
                'adm' => 1,
                'ativo' => 1,
                'permissao' => $permissoesTodas,
                'menu_representante' => 0,
                'img' => '',
                'empresa_id' => $empresa->id
            ];

            $usuario = Usuario::create($data);

            $request->merge([
                'representante_id' => $rep->usuario_id,
                'chave_pix' => $request->chave_pix ?? '',
                'empresa_id' => $empresa->id,
                'ie' => $request->ie ?? ''
            ]);

            $contador = Contador::create($request->all());
            $msg = "CNPJ: $contador->cnpj,";
            $msg .= " Nome: $request->razao_social,";
            $msg .= " Telefone: $request->fone";
            __saveAlertSuper('Novo contador parceiro', $msg, $request->empresa_id);

            session()->flash("mensagem_sucesso", 'Contador cadastrado!');
            return redirect()->route('rep-parceiro.index');
        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    private function permissoesTodas(){
        $menu = new Menu();
        $menu = $menu->getMenu();
        $temp = [];
        foreach($menu as $m){
            foreach($m['subs'] as $s){
                array_push($temp, $s['rota']);
            }
        }

        return $temp;
    }

    private function _validate(Request $request){
        $rules = [
            'nome_usuario' => 'required',
            'login' => 'required|unique:usuarios',
            'senha' => 'required',
        ];

        $messages = [
            'nome_usuario.required' => 'Campo obrigatório.',
            'senha.required' => 'Campo obrigatório.',
            'login.required' => 'Campo obrigatório.',
            'login.unique' => 'Usuário já cadastrado no sistema.',
        ];

        $this->validate($request, $rules, $messages);
    }
}
