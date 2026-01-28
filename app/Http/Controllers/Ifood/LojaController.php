<?php

namespace App\Http\Controllers\Ifood;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\IfoodConfig;
use App\Restaurant\IfoodService;

class LojaController extends Controller
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

        $config = IfoodConfig::
        where('empresa_id', $this->empresa_id)
        ->first();

        $iFoodService = new IfoodService($config);
        $dataStatus = $iFoodService->statusMerchant();
        $dataInterruptions = $iFoodService->getInterruptions();

        $dataMerchant = null;

        if(isset($dataStatus->message)){
            if($dataStatus->message == 'token expired'){
                return redirect('/ifood/getToken');
            }

            session()->flash("mensagem_erro", $dataStatus->message);
            return redirect('/ifood/config');
        }

        if(is_array($dataStatus)){
            $dataMerchant = $dataStatus[0];
        }

        return view('config_ifood/index', compact('dataMerchant', 'dataInterruptions'))
        ->with('links', true)
        ->with('title', 'Config Loja');
    }

    public function interrupcao(Request $request){
        $config = IfoodConfig::
        where('empresa_id', $this->empresa_id)
        ->first();

        $iFoodService = new IfoodService($config);
        $data = [
            'description' => $request->descricao,
            'start' => $request->inicio.":14.508Z",
            'end' => $request->fim.":14.508Z",
        ];

        $dataStatus = $iFoodService->setInterruption($data);

        if(isset($dataStatus->error)){
            session()->flash("mensagem_erro", $dataStatus->error->message);
        }else{
            session()->flash("mensagem_sucesso", "Registro adicionado!");
        }

        return redirect()->back();

    }

    public function deleteInterruption($id){
        $config = IfoodConfig::
        where('empresa_id', $this->empresa_id)
        ->first();

        $iFoodService = new IfoodService($config);

        $dataStatus = $iFoodService->deleteInterruption($id);

        if(isset($dataStatus->error)){
            session()->flash("mensagem_erro", $dataStatus->error->message);
        }else{
            session()->flash("mensagem_sucesso", "Registro removido!");
        }

        return redirect()->back();

    }

}
