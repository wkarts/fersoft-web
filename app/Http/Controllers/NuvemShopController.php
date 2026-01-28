<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NuvemShopConfig;
use App\Models\NaturezaOperacao;

class NuvemShopController extends Controller
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

    public function config(){
        $config = NuvemShopConfig::
        where('empresa_id', $this->empresa_id)
        ->first();

        $naturezas = NaturezaOperacao::
        where('empresa_id', $this->empresa_id)
        ->get();

        return view('nuvemshop/config')
        ->with('config', $config)
        ->with('naturezas', $naturezas)
        ->with('title', 'Configurar Parametros Nuvem Shop');
    }

    public function save(Request $request){
        $this->_validateConfig($request);
        $result = false;
        if($request->id == 0){

            $result = NuvemShopConfig::create([
                'client_id' => $request->client_id,
                'client_secret' => $request->client_secret,
                'natureza_padrao' => $request->natureza_padrao ?? null,
                'forma_pagamento_padrao' => $request->forma_pagamento_padrao ?? null,
                'email' => $request->email,
                'empresa_id' => $this->empresa_id
            ]);
        }else{

            $config = NuvemShopConfig::
            where('empresa_id', $this->empresa_id)
            ->first();

            $config->client_id = $request->client_id;
            $config->client_secret = $request->client_secret;
            $config->natureza_padrao = $request->natureza_padrao ?? null;
            $config->forma_pagamento_padrao = $request->forma_pagamento_padrao ?? null;
            $config->email = $request->email;
            $result = $config->save();
        }

        if($result){
            session()->flash("mensagem_sucesso", "Configurado com sucesso!");
        }else{
            session()->flash('mensagem_erro', 'Erro ao configurar!');
        }

        return redirect('/nuvemshop/config');
    }

    private function _validateConfig(Request $request){
        $rules = [
            'client_id' => 'required',
            'client_secret' => 'required',
            'email' => 'required',
        ];

        $messages = [
            'client_id.required' => 'Campo obrigatório.',
            'client_secret.required' => 'Campo obrigatório.',
            'email.required' => 'Campo obrigatório.'
        ];
        $this->validate($request, $rules, $messages);
    }

    public function categorias(){
        $store_info = session('store_info');

        if(!$store_info){
            return redirect('/nuvemshop');
        }
        $api = new \TiendaNube\API($store_info['store_id'], $store_info['access_token'], 'Awesome App ('.$store_info['email'].')');
        try{
            $categorias = (array)$api->get("categories");
            $categorias = $categorias['body'];
        }catch(\Exception $e){
            echo $e->getMessage();
            die;
        }
        // echo "<pre>";
        // print_r($categorias);
        // echo "</pre>";

        // die;
        return view('nuvemshop/categorias')
        ->with('categorias', $categorias)
        ->with('title', 'Categorias');
    }

    public function categoria_new(){
        $store_info = session('store_info');
        $api = new \TiendaNube\API($store_info['store_id'], $store_info['access_token'], 'Awesome App ('.$store_info['email'].')');
        $categorias = (array)$api->get("categories");
        $categorias = $categorias['body'];

        return view('nuvemshop/categorias_form')
        ->with('categorias', $categorias)
        ->with('title', 'Nova Categoria');

    }

    public function categoria_edit($id){
        $store_info = session('store_info');
        $api = new \TiendaNube\API($store_info['store_id'], $store_info['access_token'], 'Awesome App ('.$store_info['email'].')');
        $categoria = (array)$api->get("categories/".$id);
        $categoria = $categoria['body'];

        $categorias = (array)$api->get("categories");
        $categorias = $categorias['body'];

        return view('nuvemshop/categorias_form')
        ->with('categoria', $categoria)
        ->with('categorias', $categorias)
        ->with('title', 'Editar Categoria');

    }

    public function categoria_delete($id){
        $store_info = session('store_info');
        $api = new \TiendaNube\API($store_info['store_id'], $store_info['access_token'], 'Awesome App ('.$store_info['email'].')');
        try{
            $response = $api->delete("categories/$id");
            session()->flash("mensagem_sucesso", "Categoria removida!");

        }catch(\Exception $e){
            session()->flash("mensagem_erro", "Erro inesperado: " . $e->getMessage());

        }
        return redirect('/nuvemshop/categorias');
    }

    public function saveCategoria(Request $request){
        $nome = $request->nome;
        $descricao = $request->descricao;
        $id = $request->id;
        $categoria_id = $request->categoria_id;

        try{
            $store_info = session('store_info');
            $api = new \TiendaNube\API($store_info['store_id'], $store_info['access_token'], 'Awesome App ('.$store_info['email'].')');
            if($id > 0){

                if($categoria_id == 0){
                    $response = $api->put("categories/$id", [
                        'name' => $nome,
                        'description' => $descricao
                    ]);
                }else{
                    $response = $api->put("categories/$id", [
                        'name' => $nome,
                        'parent' => $categoria_id,
                        'description' => $descricao
                    ]);
                }
                if($response){
                    session()->flash("mensagem_sucesso", "Categoria atualizada!");
                }else{
                    session()->flash("mensagem_erro", "Erro inesperado: " . $e->getMessage());
                }

            }else{
                $response = $api->post("categories", [
                    'name' => $nome,
                    'parent' => $categoria_id,
                    'description' => $descricao
                ]);
                if($response){
                    session()->flash("mensagem_sucesso", "Categoria criada!");
                }else{
                    session()->flash("mensagem_erro", "Erro inesperado: " . $e->getMessage());
                }
            }
        }catch(\Exception $e){
            session()->flash("mensagem_erro", "Erro inesperado: " . $e->getMessage());

        }

        return redirect('/nuvemshop/categorias');
    }
}
