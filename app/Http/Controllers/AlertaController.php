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

            // Complemento: comunicados consultáveis por usuários autenticados.
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


    /** Busca o comunicado ativo mais recente ainda não confirmado pelo usuário. */
    public function pendente(Request $request)
    {
        $userLogged = session('user_logged');
        $usuarioId = $userLogged['id'] ?? $userLogged['usuario_id'] ?? null;

        if (!$usuarioId) {
            return response()->json(['pendente' => false]);
        }

        $lidosIds = \Illuminate\Support\Facades\DB::table('aviso_acessos')
            ->where('usuario_id', $usuarioId)
            ->pluck('aviso_id')
            ->toArray();

        $aviso = Aviso::where('status', 1)
            ->whereNotIn('id', $lidosIds)
            ->orderBy('id', 'desc')
            ->first();

        if (!$aviso) {
            return response()->json(['pendente' => false]);
        }

        return response()->json([
            'pendente' => true,
            'id' => $aviso->id,
            'titulo' => $aviso->titulo,
            'prioridade' => strtoupper((string) $aviso->prioridade),
            'texto' => $aviso->texto,
            'data' => \Carbon\Carbon::parse($aviso->created_at)->format('d/m/Y H:i'),
        ]);
    }

    /** Confirma leitura somente para a empresa do usuário autenticado. */
    public function marcarComoLido(Request $request, $id)
    {
        $userLogged = session('user_logged');
        $usuarioId = $userLogged['id'] ?? $userLogged['usuario_id'] ?? null;

        if (!$usuarioId) {
            return response()->json(['success' => false, 'message' => 'Usuário não autenticado'], 401);
        }

        $usuario = \Illuminate\Support\Facades\DB::table('usuarios')
            ->where('id', $usuarioId)
            ->first(['id', 'empresa_id']);

        if (!$usuario) {
            return response()->json(['success' => false, 'message' => 'Usuário não encontrado'], 404);
        }

        $empresaId = $usuario->empresa_id ?: ($userLogged['empresa_id'] ?? session('empresa_id'));
        if (!$empresaId) {
            return response()->json(['success' => false, 'message' => 'Empresa não identificada'], 422);
        }

        if (!Aviso::where('id', $id)->where('status', 1)->exists()) {
            return response()->json(['success' => false, 'message' => 'Comunicado não encontrado'], 404);
        }

        $jaExiste = \Illuminate\Support\Facades\DB::table('aviso_acessos')
            ->where('aviso_id', $id)
            ->where('usuario_id', $usuarioId)
            ->exists();

        if (!$jaExiste) {
            \Illuminate\Support\Facades\DB::table('aviso_acessos')->insert([
                'aviso_id' => $id,
                'empresa_id' => $empresaId,
                'usuario_id' => $usuarioId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json(['success' => true]);
    }
}
