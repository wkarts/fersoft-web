<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Servico;
use App\Models\CategoriaServico;
use App\Models\TypeService;
use App\Models\Nfse;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
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
        $servicos = Servico::
        where('empresa_id', $this->empresa_id)
            ->get();
        return view('servicos/list')
            ->with('servicos', $servicos)
            ->with('title', 'Serviços');
    }

    public function pesquisa(Request $request){
        $pesquisa = $request->input('pesquisa');
        $servicos = Servico::where('nome', 'LIKE', "%$pesquisa%")
            ->where('empresa_id', $this->empresa_id)
            ->get();
        return view('servicos/list')
            ->with('servicos', $servicos)
            ->with('title', 'Serviços');
    }

    public function new(){
        $categorias = CategoriaServico::where('empresa_id', $this->empresa_id)->get();
        if(sizeof($categorias) == 0){
            session()->flash('mensagem_erro', 'Cadastre uma categoria primeiramente!');
            return redirect('/categoriasServico');
        }

        // Busca filtrando registros ativos e ordenados pelo código
        $cstIbsCbs = DB::table('cst_ibs_cbs')
            ->whereNull('deleted_at')
            ->orderBy('cst_ibs_cbs')
            ->get();

        $classTribIbsCbs = DB::table('class_trib_ibs_cbs')
            ->whereNull('deleted_at')
            ->orderBy('cclasstrib')
            ->get();

        return view('servicos/register')
            ->with('categorias', $categorias)
            ->with('cstIbsCbs', $cstIbsCbs)
            ->with('classTribIbsCbs', $classTribIbsCbs)
            ->with('title', 'Cadastrar Serviço');
    }

    public function edit($id){
        $servico = new Servico();
        $categorias = CategoriaServico::where('empresa_id', $this->empresa_id)->get();
        $resp = $servico->where('id', $id)->first();

        if(valida_objeto($resp)){
            $cstIbsCbs = DB::table('cst_ibs_cbs')
                ->whereNull('deleted_at')
                ->orderBy('cst_ibs_cbs')
                ->get();

            $classTribIbsCbs = DB::table('class_trib_ibs_cbs')
                ->whereNull('deleted_at')
                ->orderBy('cclasstrib')
                ->get();

            return view('servicos/register')
                ->with('servico', $resp)
                ->with('categorias', $categorias)
                ->with('cstIbsCbs', $cstIbsCbs)
                ->with('classTribIbsCbs', $classTribIbsCbs)
                ->with('title', 'Editar Serviço');
        } else {
            return redirect('/403');
        }
    }

    public function save(Request $request){
        $servico = new Servico();
        $this->_validate($request);

        $request->merge([ 'valor' => __replace($request->valor)]);
        $request->merge([ 'comissao' => $request->comissao ? __replace($request->comissao) : 0]);

        $request->merge([ 'tempo_adicional' => $request->tempo_adicional ?? 0]);
        $request->merge([ 'tempo_tolerancia' => $request->tempo_tolerancia ?? 0]);
        $request->merge([ 'valor_adicional' => $request->valor_adicional ? __replace($request->valor_adicional) : 0 ]);

        // Alíquotas e Impostos
        $request->merge([ 'aliquota_iss' => $request->aliquota_iss ? __replace($request->aliquota_iss) : 0 ]);
        $request->merge([ 'aliquota_pis' => $request->aliquota_pis ? __replace($request->aliquota_pis) : 0 ]);
        $request->merge([ 'aliquota_cofins' => $request->aliquota_cofins ? __replace($request->aliquota_cofins) : 0 ]);
        $request->merge([ 'aliquota_inss' => $request->aliquota_inss ? __replace($request->aliquota_inss) : 0 ]);
        $request->merge([ 'aliquota_ibs' => $request->aliquota_ibs ? __replace($request->aliquota_ibs) : 0 ]);
        $request->merge([ 'aliquota_cbs' => $request->aliquota_cbs ? __replace($request->aliquota_cbs) : 0 ]);
        $request->merge([ 'iss_retido'   => $request->input('iss_retido', 2) ]); // <--- ADICIONAR ESTA LINHA

        // Códigos Fiscais NFS-e e Reforma Tributária
        $request->merge([ 'codigo_servico' => $request->codigo_servico ?? ""]);
        $request->merge([ 'codigo_tributacao_nacional' => $request->codigo_tributacao_nacional ?? $request->codigo_servico ?? ""]);
        $request->merge([ 'codigo_nbs' => $request->codigo_nbs ?? ""]);
        $request->merge([ 'cst_ibscbs' => $request->cst_ibscbs ?? ""]);
        $request->merge([ 'codigo_class_trib' => $request->codigo_class_trib ?? ""]);

        $request->merge(['exige_obra' => $request->input('exige_obra', 0)]);

        $result = $servico->create($request->all());

        if($result){
            session()->flash("mensagem_sucesso", "Serviço cadastrado com sucesso!");
        }else{
            session()->flash('mensagem_erro', 'Erro ao cadastrar serviço!');
        }

        return redirect('/servicos');
    }



    public function update(Request $request){
        $servico = new Servico();

        $id = $request->input('id');
        $resp = $servico
            ->where('id', $id)->first();

        $this->_validate($request);
        $valor = __replace($request->input('valor'));
        $comissao = __replace($request->input('comissao'));

        $resp->nome = $request->input('nome');
        $resp->unidade_cobranca = $request->input('unidade_cobranca');
        $resp->categoria_id = $request->input('categoria_id');
        $resp->valor = __replace($valor);
        $resp->tempo_servico = $request->tempo_servico;
        $resp->tempo_adicional = $request->tempo_adicional ?? 0;
        $resp->tempo_tolerancia = $request->tempo_tolerancia ?? 0;
        $resp->valor_adicional = $request->valor_adicional ? __replace($request->valor_adicional) : 0;
        $resp->comissao = $comissao;

        // Alíquotas e Impostos
        $resp->aliquota_iss = $request->aliquota_iss ? __replace($request->aliquota_iss) : 0;
        $resp->aliquota_pis = $request->aliquota_pis ? __replace($request->aliquota_pis) : 0;
        $resp->aliquota_cofins = $request->aliquota_cofins ? __replace($request->aliquota_cofins) : 0;
        $resp->aliquota_inss = $request->aliquota_inss ? __replace($request->aliquota_inss) : 0;
        $resp->aliquota_ibs = $request->aliquota_ibs ? __replace($request->aliquota_ibs) : 0;
        $resp->aliquota_cbs = $request->aliquota_cbs ? __replace($request->aliquota_cbs) : 0;
        $resp->iss_retido   = $request->input('iss_retido', 2); // <--- ADICIONAR ESTA LINHA

        // Códigos Fiscais NFS-e e Reforma Tributária
        $resp->codigo_servico = $request->codigo_servico ?? "";
        $resp->codigo_tributacao_nacional = $request->codigo_tributacao_nacional ?? $request->codigo_servico ?? "";
        $resp->codigo_nbs = $request->codigo_nbs ?? "";
        $resp->cst_ibscbs = $request->cst_ibscbs ?? "";
        $resp->codigo_class_trib = $request->codigo_class_trib ?? "";

        $resp->exige_obra = $request->input('exige_obra', 0);

        $result = $resp->save();
        if($result){
            session()->flash('mensagem_sucesso', 'Serviço editado com sucesso!');
        }else{
            session()->flash('mensagem_erro', 'Erro ao editar serviço!');
        }

        return redirect('/servicos');
    }

    public function delete($id){
        try{
            $resp = Servico
                ::where('id', $id)
                ->first();

            if(valida_objeto($resp)){
                if($resp->delete()){
                    session()->flash('mensagem_sucesso', 'Serviço removido!');
                }else{
                    session()->flash('mensagem_erro', 'Erro!');
                }
                return redirect('/servicos');
            }else{
                return redirect('/403');
            }
        }catch(\Exception $e){
            return view('errors.sql')
                ->with('title', 'Erro ao deletar serviços')
                ->with('motivo', 'Não é possivel remover serviços, incluidos em OS!');
        }
    }

    private function _validate(Request $request){
        $rules = [
            'valor' => 'required',
            'nome' => 'required|max:60',
            'tempo_servico' => 'required'
        ];

        $messages = [
            'valor.required' => 'O campo valor é obrigatório.',
            'nome.required' => 'O campo nome é obrigatório.',
            'tempo_servico.required' => 'O campo tempo de serviço é obrigatório.',
            'nome.max' => '60 caracteres maximos permitidos.'
        ];
        $this->validate($request, $rules, $messages);
    }

    public function all(){
        $services = Servico::
        where('empresa_id', $this->empresa_id)
            ->get();
        $arr = array();
        foreach($services as $s){
            $arr[$s->id. ' - ' .$s->nome] = null;
        }
        echo json_encode($arr);
    }

    public function getValue(Request $request){
        $id = $request->input('id');
        $service = Servico::
        where('id', $id)
            ->first();
        echo json_encode($service->value);
    }

    public function find($id){
        $item = Servico::findOrFail($id);
        $nfse = Nfse::
        where('empresa_id', $this->empresa_id)
            ->orderBy('id', 'desc')
            ->first();

        $item->natureza = "";
        if($nfse != null){
            $item->natureza = $nfse->natureza_operacao;
        }
        return response()->json($item, 200);
    }

    public function autocomplete(Request $request){
        try{
            $data = Servico::
            where('empresa_id', $this->empresa_id)
                ->where('nome', 'LIKE', "%$request->pesquisa%")
                ->orderBy('nome')
                ->get();
            return response()->json($data, 200);

        }catch(\Exception $e){
            return response($e->getMessage(), 401);
        }
    }
}
