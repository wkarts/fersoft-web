<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Aviso;

class AlertaController extends Controller
{
    public function __construct(){
        $this->middleware(function ($request, $next) {
            $value = session('user_logged');
            if(!$value){
                return redirect("/login");
            }

            // Libera checagem e confirmação de avisos para qualquer usuário logado
            if ($request->is('alertas/pendente') || $request->is('alertas/marcar-lido/*')) {
                return $next($request);
            }

            if($request->ajax()){
                return $next($request);
            }

            if(!$value['super']){
                return redirect('/graficos');
            }
            return $next($request);
        });
    }

    public function index(){
        $data = Aviso::orderBy('id', 'desc')
            ->get();

        return view('alertas/index')
            ->with('data', $data)
            ->with('title', 'Alertas');
    }

    public function create(){
        return view('alertas/form')
            ->with('title', 'Cadastrar alerta');
    }

    public function edit($id){
        $item = Aviso::findOrFail($id);
        return view('alertas/form')
            ->with('item', $item)
            ->with('title', 'Editar alerta');
    }

    public function store(Request $request){
        $this->_validate($request);

        try{
            $request->merge(['status' => $request->status ? 1 : 0]);
            Aviso::create($request->all());
            session()->flash('mensagem_sucesso', 'Alerta adicionado!');

        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
        }
        return redirect('/alertas');
    }

    public function update(Request $request, $id){
        $this->_validate($request);

        try{
            $item = Aviso::findOrFail($id);
            $request->merge(['status' => $request->status ? 1 : 0]);

            $item->fill($request->all())->save();
            session()->flash('mensagem_sucesso', 'Alerta atualizada!');

        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
        }
        return redirect('/alertas');
    }

    public function destroy($id){
        try{

            $item = Aviso::findOrFail($id);
            // $item->respostas()->delete();
            $item->delete();
            session()->flash('mensagem_sucesso', 'Aviso removido!');

        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
        }
        return redirect()->back();
    }

    private function _validate(Request $request){

        $rules = [
            'titulo' => 'required|max:50',
            'texto' => 'required',
        ];

        $messages = [
            'titulo.required' => 'Campo obrigatório.',
            'titulo.max' => '50 caracteres maximos permitidos.',
            'texto.required' => 'Campo obrigatório.'
        ];
        $this->validate($request, $rules, $messages);
    }

    public function list($id){
        $data = Aviso::findOrFail($id);
        return view('alertas/visualizacoes')
            ->with('data', $data)
            ->with('title', 'Visualizaçoes de alerta');
    }

    public function pendente(Request $request)
    {
        $user_logged = session('user_logged');
        if (!$user_logged) {
            return response()->json(['pendente' => false]);
        }

        $usuario_id = $user_logged['id'] ?? $user_logged['usuario_id'] ?? null;
        if (!$usuario_id) {
            return response()->json(['pendente' => false]);
        }

        // Pega os IDs dos avisos que este usuário já confirmou
        $lidosIds = \Illuminate\Support\Facades\DB::table('aviso_acessos')
            ->where('usuario_id', $usuario_id)
            ->pluck('aviso_id')
            ->toArray();

        // Busca o primeiro aviso ativo não lido por ele
        $aviso = Aviso::where('status', 1)
            ->whereNotIn('id', $lidosIds)
            ->orderBy('id', 'desc')
            ->first();

        if ($aviso) {
            return response()->json([
                'pendente'   => true,
                'id'         => $aviso->id,
                'titulo'     => $aviso->titulo,
                'prioridade' => strtoupper($aviso->prioridade),
                'texto'      => $aviso->texto,
                'data'       => \Carbon\Carbon::parse($aviso->created_at)->format('d/m/Y H:i')
            ]);
        }

        return response()->json(['pendente' => false]);
    }

    public function marcarComoLido(Request $request, $id)
    {
        $user_logged = session('user_logged');
        if (!$user_logged) {
            return response()->json(['success' => false, 'message' => 'Usuário não autenticado'], 401);
        }

        $usuario_id = $user_logged['id'] ?? $user_logged['usuario_id'] ?? null;

        // Busca empresa_id na sessão com fallbacks comuns do ERP
        $empresa_id = $user_logged['empresa_id']
            ?? session('empresa_id')
            ?? session('empresa_selecionada')['empresa_id']
            ?? null;

        // Se ainda for nulo, busca direto na tabela de usuários
        if (!$empresa_id && $usuario_id) {
            $empresa_id = \Illuminate\Support\Facades\DB::table('usuarios')
                ->where('id', $usuario_id)
                ->value('empresa_id');
        }

        // Se mesmo assim não achar (ex: super admin geral sem empresa fixa), usa a primeira empresa ativa cadastrada
        if (!$empresa_id) {
            $empresa_id = \Illuminate\Support\Facades\DB::table('empresas')->value('id') ?? 1;
        }

        if ($usuario_id && $id) {
            $jaExiste = \Illuminate\Support\Facades\DB::table('aviso_acessos')
                ->where('aviso_id', $id)
                ->where('usuario_id', $usuario_id)
                ->exists();

            if (!$jaExiste) {
                \Illuminate\Support\Facades\DB::table('aviso_acessos')->insert([
                    'aviso_id'   => $id,
                    'empresa_id' => $empresa_id,
                    'usuario_id' => $usuario_id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        return response()->json(['success' => true]);
    }

}
