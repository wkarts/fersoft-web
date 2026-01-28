<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NuvemShopConfig;

class NuvemShopAuthController extends Controller
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

    private function getConfig(){
        return NuvemShopConfig::where('empresa_id', $this->empresa_id)->first();
    }
    
    public function index(Request $request){
        $config = $this->getConfig();
        if($config != null){

            $auth = new \TiendaNube\Auth($config->client_id, $config->client_secret);
            $url = $auth->login_url_brazil();
            return redirect($url);
        }else{
            session()->flash("mensagem_erro", "Configure as credênciais!");
            return redirect('/nuvemshop/config');
        }
    }

    public function auth(Request $request){
        $config = $this->getConfig();
        if($config != null){
            $code = $request->code;
            $auth = new \TiendaNube\Auth($config->client_id, $config->client_secret);
            $store_info = $auth->request_access_token($code);

            $store_info['email'] = $config->email;

            session(['store_info' => $store_info]);
            session()->flash("mensagem_sucesso", "Autenticação realizada, access_token: " . $store_info['access_token'] . " store id: " . $store_info['store_id']);

            return redirect('/nuvemshop/pedidos');
        }else{
            session()->flash("mensagem_erro", "Configure as credênciais!");
            return redirect('/nuvemshop/config');
        }
    }

    public function app(){
        $store_info = session('store_info');
        $api = new \TiendaNube\API($store_info['store_id'], $store_info['access_token'], 'Awesome App ('.$store_info['email'].')');
        $response = $api->get("categories");
        print_r($response);
    }

    
}
