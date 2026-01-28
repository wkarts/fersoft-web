<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CashBackCliente;
use App\Models\Empresa;
use App\Models\ConfigNota;
use App\Models\CashBackConfig;
use App\Utils\WhatsAppUtil;

class TesteController extends Controller
{
    protected $util;

    public function __construct(WhatsAppUtil $util){
        $this->util = $util;
    }

    public function index(Request $request){
        $amanha = date('Y-m-d', strtotime('+1 days'));
        $empresas = Empresa::all();

        foreach($empresas as $e){
            $configNota = ConfigNota::where('empresa_id', $e->id)->first();

            $data = CashBackCliente::whereDate('data_expiracao', $amanha)
            ->where('empresa_id', $e->id)
            ->get();
            $config = CashBackConfig::where('empresa_id', $e->id)->first();

            if($config){
                foreach($data as $cashback){
                    if($cashback->status_mensagem_1_dia == 0){
                        $numero = preg_replace('/[^0-9]/', '', $cashback->cliente->celular);
                        $texto = $config->mensagem_automatica_1_dia;

                        $nomeCliente = $cashback->cliente->razao_social;
                        if($cashback->cliente->nome_fantasia != ''){
                            $nomeCliente = $cashback->cliente->nome_fantasia;
                        }
                        // echo $cashback->cliente;

                        $texto = str_replace("{credito}", moeda($cashback->valor_credito), $texto);
                        $texto = str_replace("{expiracao}", __date($cashback->data_expiracao, 0), $texto);
                        $texto = str_replace("{nome}", $nomeCliente, $texto);
                        if($numero != ''){
                            $retorno = $this->util->sendMessage('55'.$numero, $texto, $e->id);
                            $retorno = json_decode($retorno);
                            if($retorno->success == true){
                                if($retorno){
                                    $cashback->status_mensagem_1_dia = 1;
                                    $cashback->save();
                                }
                            }
                        }
                    }
                }
            }

        }
    }
}
