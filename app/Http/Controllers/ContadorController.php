<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contador;
use App\Models\Cidade;
use App\Models\Usuario;
use App\Models\Representante;
use App\Models\Empresa;
use App\Helpers\Menu;

class ContadorController extends Controller
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

        $data = Contador::orderBy('id', 'desc')->paginate(20);
        $count = Contador::count();
        $representantes = Representante::orderBy('nome', 'asc')->get();

        return view('contadores/list')
        ->with('title', 'Contadores')
        ->with('count', $count)
        ->with('representantes', $representantes)
        ->with('data', $data);
    }

    public function filtro(Request $request){
        $representante_id = $request->representante_id;
        $rep = Representante::findOrFail($representante_id);

        $data = Contador::
        where('razao_social', 'LIKE', "%$request->nome%")
        ->when($representante_id != 'null', function ($q) use ($rep) {
            return $q->where('representante_id', $rep->usuario_id);
        })
        ->get();

        $count = Contador::count();
        $representantes = Representante::orderBy('nome', 'asc')->get();

        return view('contadores/list')
        ->with('title', 'Contadores')
        ->with('count', $count)
        ->with('representantes', $representantes)
        ->with('representante_id', $representante_id)
        ->with('nome', $request->nome)
        ->with('data', $data);
    }

    public function new(){
        $cidades = Cidade::all();   

        $representantes = Representante::orderBy('nome', 'asc')->get();

        return view('contadores/register')
        ->with('title', 'Novo contador')
        ->with('representantes', $representantes)
        ->with('cidades', $cidades);
    }

    public function edit($id){

        $cidades = Cidade::all();

        $representantes = Representante::orderBy('nome', 'asc')->get();
        $escritorio = Contador::findOrFail($id);
        return view('contadores/register')
        ->with('title', 'Editar contador')
        ->with('escritorio', $escritorio)
        ->with('representantes', $representantes)
        ->with('cidades', $cidades);
    }

    public function save(Request $request){
        $this->_validate($request);

        $cidade = Cidade::findOrFail($request->cidade_id);
        $permissoesTodas = json_encode($this->permissoesTodas());
        try{

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
                'menu_representante' => 0,
                'permissao' => $permissoesTodas,
                'img' => '',
                'empresa_id' => $empresa->id
            ];

            $usuario = Usuario::create($data);

            $request->merge([
                'chave_pix' => $request->chave_pix ?? '',
                'conta' => $request->conta ?? '',
                'agencia' => $request->agencia ?? '',
                'banco' => $request->banco ?? '',
                'ie' => $request->ie ?? '',
                'representante_id' => $request->representante_id,
                'dados_bancarios' => $request->dados_bancarios ? 1 : 0,
                'contador_parceiro' => $request->contador_parceiro ? 1 : 0,
                'empresa_id' => $empresa->id
            ]);
            Contador::create($request->all());
            session()->flash('mensagem_sucesso', "Contador cadastrado");

        }catch(\Exception $e){
            session()->flash('mensagem_erro', $e->getMessage());
        }

        return redirect('/contadores');
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

    public function update(Request $request){
        // $this->_validate($request);

        try{
            $rep = null;
            if($request->representante_id){
                $rep = Representante::findOrFail($request->representante_id);
            }
            $request->merge([
                'chave_pix' => $request->chave_pix ?? '',
                'conta' => $request->conta ?? '',
                'agencia' => $request->agencia ?? '',
                'banco' => $request->banco ?? '',
                'ie' => $request->ie ?? '',
                // 'representante_id' => $rep ? $rep->usuario->id : null,
                'representante_id' => $request->representante_id,
                'dados_bancarios' => $request->dados_bancarios ? 1 : 0,
                'contador_parceiro' => $request->contador_parceiro ? 1 : 0,
            ]);
            $item = Contador::find($request->id);

            $item->fill($request->all())->save();

            $empresa = $item->empresa;
            $empresa->representante_legal = $request->representante_legal ?? '';
            $empresa->save();

            session()->flash('mensagem_sucesso', "Contador atualizado");

        }catch(\Exception $e){
            session()->flash('mensagem_erro', $e->getMessage());
        }

        return redirect('/contadores');
    }

    public function delete($id){
        try{
            $contador = Contador::find($id);
            if($contador->delete()){

                session()->flash('mensagem_sucesso', 'Registro removido!');
            }else{

                session()->flash('mensagem_erro', 'Erro!');
            }
            return redirect('/contadores');
        }catch(\Exception $e){
            return view('errors.sql')
            ->with('title', 'Erro ao deletar registro')
            ->with('motivo', $e->getMessage());
        }
    }

    public function deleteEmpresa($id){

        $empresa = Empresa::findOrFail($id);
        $empresa->contador_id = null;
        $empresa->save();
        session()->flash('mensagem_sucesso', "Empresa desatribuída");
        return redirect()->back();
    }

    private function _validate(Request $request){
        $rules = [
            'razao_social' => 'required|max:100',
            'nome_fantasia' => 'required|max:80',
            'cnpj' => 'required',
            // 'ie' => 'required',
            'percentual_comissao' => 'required',
            'logradouro' => 'required|max:80',
            'numero' => 'required|max:10',
            'bairro' => 'required|max:50',
            'fone' => 'required|max:20',
            'cep' => 'required',
            'cidade_id' => 'required',
            'email' => 'required|email|max:80',
            'nome_usuario' => 'required',
            'login' => 'required|unique:usuarios',
            'senha' => 'required',
        ];

        $messages = [
            'razao_social.required' => 'O Razão social nome é obrigatório.',
            'razao_social.max' => '100 caracteres maximos permitidos.',
            'nome_fantasia.required' => 'O campo Nome Fantasia é obrigatório.',
            'nome_fantasia.max' => '80 caracteres maximos permitidos.',
            'cnpj.required' => 'O campo CNPJ é obrigatório.',
            'logradouro.required' => 'O campo Logradouro é obrigatório.',
            // 'ie.required' => 'O campo Inscrição Estadual é obrigatório.',
            'logradouro.max' => '80 caracteres maximos permitidos.',
            'numero.required' => 'O campo Numero é obrigatório.',
            'cep.required' => 'O campo CEP é obrigatório.',
            'municipio.required' => 'O campo Municipio é obrigatório.',
            'numero.max' => '10 caracteres maximos permitidos.',
            'bairro.required' => 'O campo Bairro é obrigatório.',
            'bairro.max' => '50 caracteres maximos permitidos.',
            'fone.required' => 'O campo Telefone é obrigatório.',
            'fone.max' => '20 caracteres maximos permitidos.',
            'email.required' => 'O campo email é obrigatório.',
            'email.email' => 'Informe um email valido.',
            'email.max' => '80 caracteres maximos permitidos.',
            'cidade_id.required' => 'O campo cidade é obrigatório.',
            'percentual_comissao.required' => 'O campo % é obrigatório.',
            'nome_usuario.required' => 'Campo obrigatório.',
            'senha.required' => 'Campo obrigatório.',
            'login.required' => 'Campo obrigatório.',
            'login.unique' => 'Usuário já cadastrado no sistema.',

        ];
        $this->validate($request, $rules, $messages);
    }

    public function empresas($id){
        $contador = Contador::find($id);

        $empresas = Empresa::where('contador_id', $id)->get();

        return view('contadores/empresas')
        ->with('title', 'Empresas')
        ->with('empresas', $empresas)
        ->with('contador', $contador);
    }

    public function filtroEmpresa(Request $request){
        $contador = Contador::find($request->contador_id);
        $empresas = Empresa::
        where('nome', 'LIKE', "%$request->nome%")
        ->where('contador_id', $request->contador_id)->get();

        return view('contadores/empresas')
        ->with('empresas', $empresas)
        ->with('title', 'Empresas')
        ->with('nome', $request->nome)
        ->with('contador', $contador);
    }

    public function quickSave(Request $request){
        try{
            $data = $request->data;

            $data['agencia'] = isset($data['agencia']) ? $data['agencia'] : '';
            $data['conta'] = isset($data['conta']) ? $data['conta'] : '';
            $data['banco'] = isset($data['banco']) ? $data['banco'] : '';
            $data['chave_pix'] = isset($data['chave_pix']) ? $data['chave_pix'] : '';
            $data['ie'] = isset($data['ie']) ? $data['ie'] : '';

            $result = Contador::create($data);
            return response()->json($result, 200);


        }catch(\Exception $e){
            return response()->json($e->getMessage(), 401);
        }
    }

    public function setEmpresa(Request $request){
        $empresa = Empresa::findOrFail($request->empresa);
        $empresa->contador_id = $request->contador_id;
        $empresa->save();
        session()->flash('mensagem_sucesso', "Empresa atribuída");
        return redirect()->back();
    }

}
