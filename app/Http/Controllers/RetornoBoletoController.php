<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RetornoBoletoController extends Controller
{
    public function index(){
        $file = file_get_contents('retorno.ret');
        $return = new \Eduardokum\LaravelBoleto\Cnab\Retorno\Cnab400\Banco\Bancoob($file);
        dd($return);


        foreach($return->getDetalhes() as $object) {
            echo $object;
        }
    }
}
