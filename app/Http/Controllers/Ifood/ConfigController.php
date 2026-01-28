<?php

namespace App\Http\Controllers\Ifood;

use Illuminate\Http\Request;
use App\Models\IfoodConfig;
use App\Restaurant\IfoodService;
use App\Http\Controllers\Controller;

class ConfigController extends Controller
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
        $item = IfoodConfig::
        where('empresa_id', $this->empresa_id)
        ->first();

        return view('ifood.config')
        ->with('title', 'Configuração iFood')
        ->with('item', $item);
    }

    public function configSave(Request $request){
        $this->_validateConfig($request);

        $item = IfoodConfig::
        where('empresa_id', $this->empresa_id)
        ->first();

        try{
            if($item == null){
                $data = [
                    'empresa_id' => $this->empresa_id,
                    'clientId' => $request->clientId,
                    'clientSecret' => $request->clientSecret,
                    'merchantId' => $request->merchantId,
                    'grantType' => 'authorization_code',
                    'authorizationCode' => '',
                    'userCode' => '',
                    'authorizationCodeVerifier' => '',
                ];
                $item = IfoodConfig::create($data);
            }else{
                $item->clientId = $request->clientId;
                $item->clientSecret = $request->clientSecret;
                $item->merchantId = $request->merchantId;
                $item->authorizationCode = $request->authorizationCode;
                $item->save();
            }

            session()->flash("mensagem_sucesso", "Configurado com sucesso!");
        }catch(\Exception $e){
            session()->flash("mensagem_erro", "Algo deu errado: " . $e->getMessage());
        }
        return redirect()->back();
    }

    public function userCode(){
        $item = IfoodConfig::
        where('empresa_id', $this->empresa_id)
        ->first();
        $iFoodService = new IfoodService($item);
        $userCode = $iFoodService->getUserCode();

        session()->flash("mensagem_sucesso", "Novo código gerado!");
        return redirect()->back();
    }

    private function _validateConfig(Request $request){
        $rules = [
            'clientId' => 'required',
            'clientSecret' => 'required',
            'merchantId' => 'required',
        ];

        $messages = [
            'clientId.required' => 'Campo obrigatório.',
            'clientSecret.required' => 'Campo obrigatório.',
            'merchantId.required' => 'Campo obrigatório.',
        ];
        $this->validate($request, $rules, $messages);
    }

    public function getToken(){

        $item = IfoodConfig::
        where('empresa_id', $this->empresa_id)
        ->first();

        $iFoodService = new IfoodService($item);
        $result = $iFoodService->oAuthToken();

        if($result['success'] == 0){
            $result = $iFoodService->newToken();
            // session()->flash("mensagem_erro", "Algo deu errado ao gerar token: " . $result['message']);
        }else{
            session()->flash("mensagem_sucesso", "Token gerado: " . $result['token']);
        }

        return redirect()->back();

    }
    
}

