<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ErroLog;
use App\Models\Empresa;

class ErroLogController extends Controller
{
    public function index(){
        $data = ErroLog::
        orderBy('id', 'desc')
        ->paginate(30);

        $empresas = Empresa::
        orderBy('nome')
        ->get();

        return view('erro_log/index')
        ->with('data', $data)
        ->with('empresas', $empresas)
        ->with('title', 'Erros do sistema');
    }

    public function filtro(Request $request){
        $data = ErroLog::
        orderBy('id', 'desc')

        ->when($request->empresa != 'null', function ($q) use ($request) {
            return $q->where('empresa_id', $request->empresa);
        })
        ->when($request->data_inicial && $request->data_final, function ($q) use ($request) {
            return $q->whereBetween('created_at', [
                $this->parseDate($request->data_inicial),
                $this->parseDate($request->data_final, true)
            ]);
        })
        ->get();

        $empresas = Empresa::
        orderBy('nome')
        ->get();

        return view('erro_log/index')
        ->with('empresa', $request->empresa)
        ->with('data_inicial', $request->data_inicial)
        ->with('data_final', $request->data_final)
        ->with('data', $data)
        ->with('empresas', $empresas)
        ->with('title', 'Erros do sistema');
    }

    private static function parseDate($date, $plusDay = false){
        if($plusDay == false)
            return date('Y-m-d', strtotime(str_replace("/", "-", $date)));
        else
            return date('Y-m-d', strtotime("+1 day",strtotime(str_replace("/", "-", $date))));
    }

    public function destroy($id){
        try{

            $item = ErroLog::findOrFail($id);

            $item->delete();
            session()->flash('mensagem_sucesso', 'Erro removido!');

        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
        }
        return redirect()->back();
    }
}
