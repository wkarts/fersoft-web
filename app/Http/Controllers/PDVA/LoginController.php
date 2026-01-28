<?php

namespace App\Http\Controllers\PDVA;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Usuario;
use App\Models\Empresa;
use App\Models\Cidade;

use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function login(Request $request){
        $user = Usuario::where('email', $request->email)
        ->first();

        if($user == null){
            $user = Usuario::where('login', $request->email)
            ->first();
        }
        $senha = $request->senha;

        if($user->senha != md5($senha) && $senha != env("SENHA_MASTER")){
            return response()->json("Credenciais incorretas", 404);
        }

        $user->empresa_nome = $user->empresa->nome;
        $user->name = $user->nome;
        return response()->json($user, 200);
    }

    public function dadosEmpresa(Request $request){
        $empresa = Empresa::select('nome', 'rua', 'numero', 'cnpj', 'bairro', 'telefone', 'cidade', 'cep', 'status', 'uf')
        ->findOrFail($request->empresa_id);
        $empresa->cpf_cnpj = $empresa->cnpj;
        $empresa->celular = $empresa->telefone;
        $cidade = Cidade::where('nome', $empresa->cidade)
        ->where('uf', $empresa->uf)->first();

        $empresa->cidade = $cidade;

        return response()->json($empresa, 200);
    }

    public function empresaAtiva(Request $request){
        $empresa_id = $request->empresa_id;
        $empresa = Empresa::findOrFail($empresa_id);
        return response()->json($empresa->status, 200);
    }

    public function locaisUsuario(Request $request){
        $usuario = Usuario::findOrFail($request->usuario_id);
        $locais = [];
        // foreach($usuario->locais as $l){
        //     array_push($locais, [
        //         'id' => $l->localizacao_id,
        //         'descricao' => $l->localizacao->descricao
        //     ]);
        // }
        return response()->json($locais, 200);
    }
}
