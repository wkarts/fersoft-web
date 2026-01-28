<?php

namespace App\Http\Controllers\MP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClienteDelivery;
use App\Models\EnderecoDelivery;
use App\Models\CidadeDelivery;
use App\Models\BairroDeliveryLoja;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function login(Request $request){
        try{
            $cliente = ClienteDelivery::
            where('email', $request->email)
            ->where('senha', md5($request->senha))
            ->first();

            if($cliente == null){
                $cliente = ClienteDelivery::
                where('celular', $request->celular)
                ->where('senha', md5($request->senha))
                ->first();
            }

            if($cliente == null){
                return response()->json("Credenciais inválidas", 401);
            }
            
            return response()->json($cliente, 200);
        }catch(\Exception $e){
            return response()->json($e->getMessage(), 401);
        }
    }

    public function cadastrar(Request $request){
        try{
            $celular = preg_replace('/[^0-9]/', '', $request->celular);
            $token = rand(100000, 888888);

            $cliente = ClienteDelivery::
            where('email', $request->email)
            ->first();
            if($cliente != null){
                return response()->json("Já existe um cliente com este email cadastrado!", 401);
            }

            $cliente = ClienteDelivery::
            where('celular', $request->celular)
            ->first();
            if($cliente != null){
                return response()->json("Já existe um cliente com este celular cadastrado!", 401);
            }
            $data = [
                'nome' => $request->nome,
                'sobre_nome' => $request->sobre_nome,
                'celular' => $request->celular,
                'email' => $request->email,
                'token' => $token,
                'ativo' => 1,
                'senha' => md5($request->senha)
            ];

            $cliente = ClienteDelivery::create($data);
            return response()->json($cliente, 200);
        }catch(\Exception $e){
            return response()->json($e->getMessage(), 401);
        }
    }

    public function atualizar(Request $request){
        try{

            $validMail = ClienteDelivery::
            where('email', $request->email)
            ->first();
            if($validMail != null && $validMail->id != $request->id){
                return response()->json("Já existe um cliente com este celular cadastrado!", 401);
            }

            $item = ClienteDelivery::findOrFail($request->id);

            $item->nome = $request->nome;
            $item->sobre_nome = $request->sobre_nome;
            $item->email = $request->email;
            $item->cpf = $request->cpf;

            if($request->senha != ""){
                $item->senha = md5($request->senha);
            }

            $item->save();

            return response()->json($item, 200);

        }catch(\Exception $e){
            return response()->json($e->getMessage(), 401);
        }
    }

    public function salvarEndereco(Request $request){
        try{

            $cidade = $request->cidade;

            $city = CidadeDelivery::where('nome','like',  "%$cidade%")
            ->first();

            if($request->padrao == true){
                $cli = ClienteDelivery::findOrFail($request->cliente_id);
                foreach($cli->enderecos as $e){
                    $e->padrao = false;
                    $e->save();
                }
            }
            $data = [
                'cliente_id' => $request->cliente_id,
                'rua' => $request->rua,
                'numero' => $request->numero,
                'bairro' => '',
                'bairro_id' => $request->bairro_id,
                'referencia' => $request->referencia ?? "",
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'cidade_id' => $city->id,
                'tipo' => $request->tipo,
                'cep' => $request->cep,
                'padrao' => $request->padrao
            ];

            EnderecoDelivery::create($data);
            return response()->json($data, 200);
        }catch(\Exception $e){
            return response()->json($e->getMessage(), 401);
        }
    }

    public function atualizarEndereco(Request $request){
        try{

            $endereco = EnderecoDelivery::find($request->id);

            $endereco->rua = $request->rua;
            $endereco->numero = $request->numero;
            $endereco->bairro = '';
            $endereco->bairro_id = $request->bairro_id;
            $endereco->referencia = $request->referencia ?? "";
            $endereco->latitude = $request->latitude;
            $endereco->longitude = $request->longitude;
            $endereco->tipo = $request->tipo;
            $endereco->cep = $request->cep;
            $endereco->padrao = $request->padra;

            $item = $endereco->save();
            return response()->json($item, 200);
        }catch(\Exception $e){
            return response()->json($e->getMessage(), 401);
        }
    }

    public function find($cliente_id){
        try{
            $item = ClienteDelivery::
            where('id', $cliente_id)
            ->with('enderecos')
            ->with('pedidos')
            ->first();

            foreach($item->enderecos as $e){
                $e->cidade;
                $e->_bairro;
            }

            return response()->json($item, 200);

        }catch(\Exception $e){
            return response()->json($e->getMessage(), 401);
        }
    }

    public function salvarImagem(Request $request){
        try{
            $imagem = $request->file;
            $usuarioId = $request->usuario_id;
            $public = env('SERVIDOR_WEB') ? 'public/' : '';

            $fileName = Str::random(20);

            $cliente = ClienteDelivery::find($usuarioId);

            if(!is_dir(public_path('fotos_cliente_delivery'))){
                mkdir(public_path('fotos_cliente_delivery'), 0777, true);
            }
            if($cliente->img != ''){
                if(file_exists($public.'fotos_cliente_delivery/'.$cliente->img)){
                    unlink($public.'fotos_cliente_delivery/'.$cliente->img);
                }
            }

            $imgData = str_replace('data:image/jpeg;base64,', '', $imagem);
            $imgData = str_replace('data:image/jpg;base64,', '', $imgData);
            $imgData = str_replace(' ', '+', $imgData);
            $imgData = base64_decode($imgData);

            $cliente->img = $fileName.'.jpg';
            $cliente->save();
            file_put_contents($public.'fotos_cliente_delivery/'.$cliente->img, $imgData);

            return response()->json($fileName.'.jpg', 201);
        }catch(\Exception $e){
            return response()->json($e->getMessage(), 401);
        }
    }

}
