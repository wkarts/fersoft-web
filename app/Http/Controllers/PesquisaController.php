<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pesquisa;
use App\Models\PesquisaResposta;
use App\Models\ConfigNota;
use Dompdf\Dompdf;

class PesquisaController extends Controller
{

    public function __construct(){
        $this->middleware(function ($request, $next) {
            $value = session('user_logged');
            if(!$value){
                return redirect("/login");
            }

            if($request->ajax()){
                return $next($request);
            }
            if(!$value['super']){
                return redirect('/graficos');
            }
            return $next($request);
        });
    }

    public function index(){
        $data = Pesquisa::orderBy('id', 'desc')
        ->get();

        return view('pesquisas/index')
        ->with('data', $data)
        ->with('title', 'Pesquisas de satisfação');
    }

    public function create(){
        return view('pesquisas/form')
        ->with('title', 'Cadastrar pesquisa');
    }

    public function edit($id){
        $item = Pesquisa::findOrFail($id);
        return view('pesquisas/form')
        ->with('item', $item)
        ->with('title', 'Editar pesquisa');
    }

    public function store(Request $request){
        $this->_validate($request);

        try{
            $request->merge(['status' => $request->status ? 1 : 0]);
            $request->merge(['maximo_acessos' => $request->maximo_acessos ?? 0]);
            Pesquisa::create($request->all());
            session()->flash('mensagem_sucesso', 'Pesquisa adicionada!');

        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
        }
        return redirect('/pesquisa');
    }

    public function update(Request $request, $id){
        $this->_validate($request);

        try{
            $item = Pesquisa::findOrFail($id);
            $request->merge(['status' => $request->status ? 1 : 0]);
            $request->merge(['maximo_acessos' => $request->maximo_acessos ?? 0]);
            
            $item->fill($request->all())->save();
            session()->flash('mensagem_sucesso', 'Pesquisa atualizada!');

        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
        }
        return redirect('/pesquisa');
    }

    public function destroy($id){
        try{

            $item = Pesquisa::findOrFail($id);

            $item->respostas()->delete();
            $item->delete();
            session()->flash('mensagem_sucesso', 'Pesquisa removida!');

        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
        }
        return redirect()->back();
    }

    private function _validate(Request $request){

        $rules = [
            'titulo' => 'required|max:50',
            'texto' => 'required',
        ];

        $messages = [
            'titulo.required' => 'Campo obrigatório.',
            'titulo.max' => '50 caracteres maximos permitidos.',
            'texto.required' => 'Campo obrigatório.'
        ];
        $this->validate($request, $rules, $messages);
    }

    public function find($id){
        try{
            $pesquisa = Pesquisa::findOrFail($id);
            return response()->json($pesquisa, 200);
        }catch(\Exception $e){
            return response()->json($e->getMessage(), 404);
        }

    }

    public function salvarNota(Request $request){
        try{
            $pesquisa = Pesquisa::findOrFail($request->id);

            PesquisaResposta::create([
                'empresa_id' => $request->empresa_id,
                'nota' => $request->nota,
                'pesquisa_id' => $pesquisa->id,
                'resposta' => $request->resposta ?? ''
            ]);
            return response()->json($pesquisa, 200);
        }catch(\Exception $e){
            return response()->json($e->getMessage(), 404);
        }

    }

    public function list($id){
        $data = Pesquisa::findOrFail($id);
        return view('pesquisas/respostas')
        ->with('data', $data)
        ->with('title', 'Resposta de pesquisa');
    }

    public function imprimir($id){
        $item = Pesquisa::findOrFail($id);
        $config = ConfigNota::
        where('empresa_id', request()->empresa_id)
        ->first();

        $p = view('pesquisas/print', compact('item', 'config'));

        $domPdf = new Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);

        $pdf = ob_get_clean();

        $domPdf->setPaper("A4");
        $domPdf->render();
        $domPdf->stream("Pesquisa $id.pdf", array("Attachment" => false));
    }
}
