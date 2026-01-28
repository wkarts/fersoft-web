<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ConfigSystem;
class ConfigController extends Controller
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

        $item = ConfigSystem::first();
        return view('config.index', compact('item'))
        ->with('title', 'Configuração');
    }

    public function removeCor(){
        $item = ConfigSystem::first();
        
        $item->cor = '';
        $item->save();
        session()->flash("mensagem_sucesso", "Cor padrão removida!");
        return redirect()->back();

    }

    public function save(Request $request){
        $item = ConfigSystem::first();
        $logo1 = $request->logo1;
        $logo2 = $request->logo2;
        $cor = $request->cor;
        try{
            if($request->hasFile('logo1')){
            //unlink anterior
                $file = $request->file('logo1');
                $fileName = "slym.png";
                $upload = $file->move(public_path('imgs'), $fileName);
            }

            if($request->hasFile('logo2')){
            //unlink anterior
                $file = $request->file('logo2');
                $fileName = "slym2.png";
                $upload = $file->move(public_path('imgs'), $fileName);
            }
            if($item != null){
                $item->update([
                    'cor' => $cor,
                    'mensagem_plano_indeterminado' => $request->mensagem_plano_indeterminado ?? null,
                    'inicio_mensagem_plano' => $request->inicio_mensagem_plano ?? null,
                    'fim_mensagem_plano' => $request->fim_mensagem_plano ?? null,
                    'valor_base_contrato' => $request->valor_base_contrato ?? null,
                    'usuario_correios' => $request->usuario_correios ?? null,
                    'codigo_acesso_correios' => $request->codigo_acesso_correios ?? null,
                    'cartao_postagem_correios' => $request->cartao_postagem_correios ?? null,
                    'token_integra_notas' => $request->token_integra_notas ?? '',
                ]);
            }else{
                ConfigSystem::create([
                    'cor' => $cor,
                    'mensagem_plano_indeterminado' => $request->mensagem_plano_indeterminado ?? null,
                    'inicio_mensagem_plano' => $request->inicio_mensagem_plano ?? null,
                    'fim_mensagem_plano' => $request->fim_mensagem_plano ?? null,
                    'valor_base_contrato' => $request->valor_base_contrato ?? null,
                    'usuario_correios' => $request->usuario_correios ?? null,
                    'codigo_acesso_correios' => $request->codigo_acesso_correios ?? null,
                    'cartao_postagem_correios' => $request->cartao_postagem_correios ?? null,
                    'token_integra_notas' => $request->token_integra_notas ?? '',
                ]);
            }

            session()->flash("mensagem_sucesso", "Configurado com sucesso!");
        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Erro ao configurar: ' . $e->getMessage());
        }
        return redirect()->back();
    }

}
