<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Empresa;
use App\Models\CancelamentoLicenca;
use Mail;

class CancelamentoController extends Controller
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
        $empresa = Empresa::findOrFail($this->empresa_id);
        $contrato = 0;
        if($empresa->contrato){
            $contrato = 1;
        }
        $item = CancelamentoLicenca::where('empresa_id', $this->empresa_id)->first();
        return view('cancelamento.index', compact('contrato', 'empresa', 'item'))->with('title', 'Cancelamento de contrato');
    }

    public function store(Request $request){
        try{
            $item = CancelamentoLicenca::create([
                'justificativa' => $request->justificativa,
                'empresa_id' => $this->empresa_id
            ]);

            if(env("AVISO_EMAIL_NOVO_CADASTRO") != ""){
                Mail::send('mail.cancelamento', ['item' => $item], function($m){
                    $nomeEmail = env('MAIL_NAME');
                    $nomeEmail = str_replace("_", " ", $nomeEmail);
                    $m->from(env('MAIL_USERNAME'), $nomeEmail);
                    $m->subject('cancelamento');
                    $m->to(env("AVISO_EMAIL_NOVO_CADASTRO"));
                });

            }

            __saveAlertSuper('Cancelamento sistema', $request->justificativa, $this->empresa_id);

            session()->flash("mensagem_sucesso", "Cancelamento registrado!");
        }catch(\Exception $e){
            session()->flash("mensagem_erro", "Algo deu errado: " . $e->getMessage());

        }
        return redirect()->back();

    }

    public function downloadContrato(){
        $empresa = Empresa::findOrFail($this->empresa_id);
        $cnpj = preg_replace('/[^0-9]/', '', $empresa->cnpj);

        return response()->download(public_path('contratos/').$cnpj.'.pdf');

    }
}
