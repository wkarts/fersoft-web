<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Cidade;
use App\Models\GrupoCliente;
use App\Models\Pais;
use App\Models\CashBackConfig;
use App\Imports\ProdutoImport;
use Maatwebsite\Excel\Facades\Excel;
use DB;
use App\Rules\ValidaDocumento;
use App\Models\CreditoVenda;
use App\Models\Acessor;
use App\Models\Funcionario;
use App\Models\ClienteOtica;
use App\Models\ClienteUpload;
use Dompdf\Dompdf;
use Illuminate\Support\Str;

class ClienteController extends Controller
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

    public function buscar(Request $request){
        $pesquisa = $request->input('pesquisa');
        $data = Cliente::where('empresa_id', $this->empresa_id)
        ->when(is_numeric($pesquisa), function ($query) use ($pesquisa) {
            $query->where('cpf_cnpj', 'like', "%$pesquisa%");
        })
        ->when(!is_numeric($pesquisa), function ($query) use ($pesquisa) {
            $query->where('razao_social', 'like', "%$pesquisa%");
        })
        ->get();

        return response()->json($data, 200);
    }

    public function pesquisa(Request $request){
        $pesquisa = $request->input('pesquisa');
        $cpf_cnpj = $request->input('cpf_cnpj');
        $ordem = $request->input('ordem');
        $aniversariante = $request->input('aniversariante') ? true : false;

        if($pesquisa == "" && !$aniversariante && !$cpf_cnpj){
            // return redirect('/clientes');
        }

        $clientes = Cliente::
        where('empresa_id', $this->empresa_id)
        ->where($request->tipo_pesquisa, 'LIKE', "%$pesquisa%")
        ->when($ordem, function ($query) use ($ordem) {
            $query->orderBy('created_at', $ordem);
        });

        if($cpf_cnpj){
            $clientes->where('cpf_cnpj', 'LIKE', "%$cpf_cnpj%");
        }
        $clientes = $clientes->get();

        if($aniversariante){
            $mes = date('m');
            $temp = [];
            foreach($clientes as $c){
                if($c->data_nascimento != ""){
                    if($mes == substr($c->data_nascimento, 3, 2)){
                        array_push($temp, $c);
                    }
                }
            }

            $clientes = $temp;
        }

        return view('clientes/list')
        ->with('clientes', $clientes)
        ->with('tipoPesquisa', $request->tipo_pesquisa)
        ->with('cpf_cnpj', preg_replace('/[^0-9]/', '', $request->cpf_cnpj))
        ->with('pesquisa', $pesquisa)
        ->with('ordem', $ordem)
        ->with('paraImprimir', true)
        ->with('aniversariante', $aniversariante)
        ->with('title', 'Filtro Clientes');
    }

    public function relatorio(Request $request){
        $pesquisa = $request->input('pesquisa');
        $aniversariante = $request->input('aniversariante') ? true : false;

        $clientes = Cliente::
        where('empresa_id', $this->empresa_id)
        ->where($request->tipo_pesquisa, 'LIKE', "%$pesquisa%")
        ->get();

        if($aniversariante){
            $mes = date('m');
            $temp = [];
            foreach($clientes as $c){
                if($c->data_nascimento != ""){
                    if($mes == substr($c->data_nascimento, 3, 2)){
                        array_push($temp, $c);
                    }
                }
            }

            $clientes = $temp;
        }

        $p = view('clientes/relatorio_clientes')
        ->with('clientes', $clientes);

        // return $p;

        $domPdf = new Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);

        $pdf = ob_get_clean();

        $domPdf->setPaper("A4", "landscape");
        $domPdf->render();
        $domPdf->stream("relatorio clientes.pdf", array("Attachment" => false));

    }

    public function index(){

        $clientes = Cliente::
        where('empresa_id', $this->empresa_id)
        ->paginate(20);

        $totalGeralClientes = sizeof(Cliente::
            where('empresa_id', $this->empresa_id)
            ->get());
        return view('clientes/list')
        ->with('clientes', $clientes)
        ->with('totalGeralClientes', $totalGeralClientes)
        ->with('links', true)
        ->with('title', 'Clientes');
    }

    public function new(){
        $estados = Cliente::estados();
        $cidades = Cidade::all();
        $pais = Pais::all();
        $grupos = GrupoCliente::
        where('empresa_id', $this->empresa_id)
        ->get();

        $acessores = Acessor::
        where('empresa_id', $this->empresa_id)
        ->get();

        $funcionarios = Funcionario::
        where('empresa_id', $this->empresa_id)
        ->get();

        return view('clientes/register')
        ->with('pessoaFisicaOuJuridica', true)
        ->with('cidadeJs', true)
        ->with('cidades', $cidades)
        ->with('estados', $estados)
        ->with('acessores', $acessores)
        ->with('funcionarios', $funcionarios)
        ->with('grupos', $grupos)
        ->with('pais', $pais)
        ->with('title', 'Cadastrar Cliente');
    }

    public function save(Request $request){
        $this->_validate($request);

        try{
            $result = DB::transaction(function () use ($request) {
                $cidade = $request->input('cidade');
                $cidade = explode("-", $cidade);
                $cidade = $cidade[0];

                $cidadeTemp = Cidade::find($cidade);
                if($cidadeTemp == null){
                    $request->merge([ 'cidade' => 'a']);
                }

                $cliente = new Cliente();

                $request->merge([ 'limite_venda' => $request->limite_venda ? __replace($request->limite_venda) : 0]);
                $request->merge([ 'valor_cashback' => $request->valor_cashback ? __replace($request->valor_cashback) : 0]);
                $request->merge([ 'celular' => $request->celular ?? '']);
                $request->merge([ 'telefone' => $request->telefone ?? '']);
                $request->merge([ 'ie_rg' => $request->ie_rg ? strtoupper($request->ie_rg) :
                    'ISENTO']);
                $request->merge([ 'razao_social' => strtoupper($request->razao_social)]);
                $request->merge([ 'nome_fantasia' => strtoupper($request->nome_fantasia)]);
                $request->merge([ 'rua' => strtoupper($request->rua)]);
                $request->merge([ 'numero' => strtoupper($request->numero)]);
                $request->merge([ 'bairro' => strtoupper($request->bairro)]);
                $request->merge([ 'email' => $request->email ?? '']);
                $request->merge([ 'observacao' => $request->observacao ?? '']);
                $request->merge([ 'complemento' => $request->complemento ?? '']);
                $request->merge([ 'inativo' => $request->input('inativo') ? true : false ]);

                $request->merge([ 'rua_cobranca' => $request->rua_cobranca ?? '']);
                $request->merge([ 'numero_cobranca' => $request->numero_cobranca ?? '']);
                $request->merge([ 'bairro_cobranca' => $request->bairro_cobranca ?? '']);
                $request->merge([ 'cep_cobranca' => $request->cep_cobranca ?? '']);
                $request->merge([ 'cidade_cobranca_id' => NULL]);

                $request->merge([ 'nome_entrega' => $request->nome_entrega ?? '']);
                $request->merge([ 'cpf_cnpj_entrega' => $request->cpf_cnpj_entrega ?? '']);
                $request->merge([ 'rua_entrega' => $request->rua_entrega ?? '']);
                $request->merge([ 'numero_entrega' => $request->numero_entrega ?? '']);
                $request->merge([ 'bairro_entrega' => $request->bairro_entrega ?? '']);
                $request->merge([ 'cep_entrega' => $request->cep_entrega ?? '']);
                $request->merge([ 'cidade_entrega_id' => NULL]);

                $request->merge([ 'id_estrangeiro' => $request->id_estrangeiro ?? '']);
                $request->merge([ 'contador_nome' => $request->contador_nome ?? '']);
                $request->merge([ 'contador_telefone' => $request->contador_telefone ?? '']);
                $request->merge([ 'contador_email' => $request->contador_email ?? '']);
                $request->merge([ 'data_aniversario' => $request->data_aniversario ?? '']);
                $request->merge([ 'data_nascimento' => $request->data_nascimento ?? '']);

                $request->merge([ 'instagram' => strtoupper($request->instagram ?? '')]);
                $request->merge([ 'facebook' => strtoupper($request->facebook ?? '')]);
                $request->merge([ 'linkedin' => strtoupper($request->linkedin ?? '')]);
                $request->merge([ 'tiktok' => strtoupper($request->tiktok ?? '')]);
                $request->merge([ 'whatsapp' => strtoupper($request->whatsapp ?? '')]);


                if($request->input('cidade_cobranca_id') != ""){
                    $cidade = $request->input('cidade_cobranca_id');
                    $request->merge([ 'cidade_cobranca_id' => $cidade]);
                }

                if($request->input('cidade_entrega_id') != "-"){
                    $cidade = $request->input('cidade_entrega_id');
                    $request->merge([ 'cidade_entrega_id' => $cidade]);
                }

                if(!is_dir(public_path('imgs_clientes'))){
                    mkdir(public_path('imgs_clientes'), 0777, true);
                }

                $fileName = "";
                if($request->hasFile('file')){

                    $file = $request->file('file');

                    $extensao = $file->getClientOriginalExtension();
                    $fileName = Str::random(25) . ".".$extensao;

                    $file->move(public_path('imgs_clientes'), $fileName);
                }

                $blob = $request->blob;

                if($blob && $fileName == ""){

                    $fileName = Str::random(25) . ".png";

                    $img = str_replace('data:image/png;base64,', '', $blob);
                    $img = str_replace(' ', '+', $img);
                    $data = base64_decode($img);
                    file_put_contents(public_path('imgs_clientes/'). $fileName, $data);

                }

                $request->merge([ 'imagem' => $fileName]);

                $result = $cliente->create($request->all());

                $this->criarReceitaOtica($request, $result);

                $this->criarLog($result);

                if($result){
                    session()->flash("mensagem_sucesso", "Cliente cadastrado com sucesso!");
                }else{
                    session()->flash('mensagem_erro', 'Erro ao cadastrar cliente!');
                }

                return redirect('/clientes');
            });
return $result;
}catch(\Exception $e){
    __saveError($e, $this->empresa_id);
    session()->flash("mensagem_erro", "Algo deu errado: " . $e->getMessage());
    return redirect('/clientes');
}
}

private function criarReceitaOtica($request, $result){

    if($request->receita != '[]'){
        $cli = Cliente::find($result->id);
        if($cli->receitaOtica){
           $cli->receitaOtica->delete();
       }
       $receita = (array)json_decode($request->receita);

       $receita['cliente_id'] = $result->id;
       ClienteOtica::create($receita);
   }
}

private function criarLog($objeto, $tipo = 'criar'){
    if(isset(session('user_logged')['log_id'])){
        $record = [
            'tipo' => $tipo,
            'usuario_log_id' => session('user_logged')['log_id'],
            'tabela' => 'clientes',
            'registro_id' => $objeto->id,
            'empresa_id' => $this->empresa_id
        ];
        __saveLog($record);
    }
}

public function edit($id){
        $cliente = new Cliente(); //Model
        $estados = Cliente::estados();
        $resp = $cliente
        ->where('id', $id)->first();

        $cidades = Cidade::all();
        $pais = Pais::all();

        $grupos = GrupoCliente::
        where('empresa_id', $this->empresa_id)
        ->get();

        $acessores = Acessor::
        where('empresa_id', $this->empresa_id)
        ->get();

        $funcionarios = Funcionario::
        where('empresa_id', $this->empresa_id)
        ->get();


        if(valida_objeto($resp)){
            return view('clientes/register')
            ->with('pessoaFisicaOuJuridica', true)
            ->with('cidadeJs', true)
            ->with('cliente', $resp)
            ->with('pais', $pais)
            ->with('funcionarios', $funcionarios)
            ->with('estados', $estados)
            ->with('grupos', $grupos)
            ->with('acessores', $acessores)
            ->with('cidades', $cidades)
            ->with('title', 'Editar Cliente');
        }else{
            return redirect('/403');
        }

    }

    public function update(Request $request){
        $cliente = new Cliente();
        $resp = $cliente->findOrFail($request->id);

        $request->merge([
            'ie_rg' => $request->ie_rg ? strtoupper($request->ie_rg) :'ISENTO',
            'celular' => $request->celular ?? ''
        ]);

        $this->_validate($request);

        try{

            $resp->razao_social = strtoupper($request->input('razao_social'));
            $resp->nome_fantasia = strtoupper($request->input('nome_fantasia'));
            $resp->cpf_cnpj = $request->input('cpf_cnpj');
            $resp->ie_rg = $request->input('ie_rg');
            $resp->limite_venda = $request->limite_venda ? __replace($request->limite_venda) : 0;
            $resp->valor_cashback = $request->valor_cashback ? __replace($request->valor_cashback) : 0;
            $resp->cidade_id = $request->cidade_id;

            $resp->rua = strtoupper($request->input('rua'));
            $resp->numero = strtoupper($request->input('numero'));
            $resp->bairro = strtoupper($request->input('bairro'));

            $resp->telefone = $request->input('telefone') ?? '';
            $resp->celular = $request->input('celular') ?? '';
            $resp->email = $request->input('email');
            $resp->cep = $request->input('cep');
            $resp->consumidor_final = $request->input('consumidor_final');
            $resp->contribuinte = $request->input('contribuinte');
            $resp->cod_pais = $request->input('cod_pais');
            $resp->id_estrangeiro = $request->input('id_estrangeiro');
            $resp->grupo_id = $request->input('grupo_id');
            $resp->inativo = $request->inativo ? true : false;

            $resp->instagram = $request->input('instagram') ?? '';
            $resp->facebook = $request->input('facebook') ?? '';
            $resp->linkedin = $request->input('linkedin') ?? '';
            $resp->tiktok = $request->input('tiktok') ?? '';
            $resp->whatsapp = $request->input('whatsapp') ?? '';

            $resp->rua_cobranca = $request->input('rua_cobranca') ?? '';
            $resp->bairro_cobranca = $request->input('bairro_cobranca') ?? '';
            $resp->numero_cobranca = $request->input('numero_cobranca') ?? '';
            $resp->cep_cobranca = $request->input('cep_cobranca') ?? '';
            if($request->input('cidade_cobranca_id') != ''){
                $cidade = $request->input('cidade_cobranca');
                $resp->cidade_cobranca_id = $cidade;
            }

            $resp->nome_entrega = $request->input('nome_entrega') ?? '';
            $resp->cpf_cnpj_entrega = $request->input('cpf_cnpj_entrega') ?? '';
            $resp->rua_entrega = $request->input('rua_entrega') ?? '';
            $resp->bairro_entrega = $request->input('bairro_entrega') ?? '';
            $resp->numero_entrega = $request->input('numero_entrega') ?? '';
            $resp->cep_entrega = $request->input('cep_entrega') ?? '';
            if($request->input('cidade_entrega_id') != '-'){
                $cidade = $request->input('cidade_entrega_id');
                $resp->cidade_entrega_id = $cidade;
            }

            $resp->complemento = $request->input('complemento') ?? '';
            $resp->acessor_id = $request->input('acessor_id');
            $resp->contador_nome = $request->input('contador_nome');
            $resp->contador_telefone = $request->input('contador_telefone');
            $resp->contador_email = $request->input('contador_email');
            $resp->data_aniversario = $request->input('data_aniversario') ?? '';
            $resp->data_nascimento = $request->input('data_nascimento');
            $resp->funcionario_id = $request->input('funcionario_id');
            $resp->observacao = $request->input('observacao') ?? '';

            $fileName = "";
            if($request->hasFile('file')){

                $file = $request->file('file');

                $extensao = $file->getClientOriginalExtension();
                $fileName = Str::random(25) . ".".$extensao;

                $file->move(public_path('imgs_clientes'), $fileName);
            }

            $blob = $request->blob;

            if($blob && $fileName == ""){

                $fileName = Str::random(25) . ".png";
                $img = str_replace('data:image/png;base64,', '', $blob);
                $img = str_replace(' ', '+', $img);
                $data = base64_decode($img);
                file_put_contents(public_path('imgs_clientes/'). $fileName, $data);

            }

            if($fileName != ""){
                $path = public_path('imgs_clientes/').$resp->imagem;
                if(file_exists($path) && $resp->imagem != ""){
                    unlink($path);
                }
                $resp->imagem = $fileName;
            }

            $result = $resp->save();

            $this->criarReceitaOtica($request, $resp);
            $this->criarLog($resp, 'atualizar');

            session()->flash('mensagem_sucesso', 'Cliente editado com sucesso!');
        }catch(\Exception $e){
            __saveError($e, $this->empresa_id);
            session()->flash("mensagem_erro", "Algo deu errado: " . $e->getMessage());
        }

        return redirect('/clientes');
    }

    public function delete($id){
        try{
            $cliente = Cliente::findOrFail($id);
            if(valida_objeto($cliente)){
                $this->criarLog($cliente, 'deletar');

                if($cliente->imagem != ""){
                    $path = public_path('imgs_clientes/').$cliente->imagem;
                    if(file_exists($path)){
                        unlink($path);
                    }
                }
                if($cliente->delete()){

                    session()->flash('mensagem_sucesso', 'Registro removido!');
                }else{

                    session()->flash('mensagem_erro', 'Erro!');
                }
                return redirect('/clientes');
            }
        }catch(\Exception $e){
            return view('errors.sql')
            ->with('title', 'Erro ao deletar cliente')
            ->with('motivo', 'Não é possivel remover clientes, presentes vendas ou pedidos!');
        }
    }

    private function _validate(Request $request){
        $doc = $request->cpf_cnpj;

        $rules = [
            'razao_social' => 'required|max:80',
            'nome_fantasia' => $doc == '00.000.000/0000-00' ? 'max:80' :(strlen($doc) > 14 ? 'required|max:80' : 'max:80'),
            'cpf_cnpj' => [ 'required', new ValidaDocumento ],
            'rua' => 'required|max:80',
            'numero' => 'required|max:10',
            'bairro' => 'required|max:50',
            'telefone' => 'max:20',
            'celular' => 'max:20',
            'email' => 'max:60',
            'cep' => 'required|min:9',
            'cidade_id' => 'required',
            'consumidor_final' => 'required',
            'contribuinte' => 'required',
            'rua_cobranca' => 'max:80',
            'numero_cobranca' => 'max:10',
            'bairro_cobranca' => 'max:50',
            'cep_cobranca' => 'max:9'
        ];

        $messages = [
            'cidade_id.required' => 'O campo Cidade é obrigatório.',
            'razao_social.required' => 'O campo Razão social/Nome é obrigatório.',
            'razao_social.max' => '50 caracteres maximos permitidos.',
            'nome_fantasia.required' => 'O campo Nome Fantasia é obrigatório.',
            'nome_fantasia.max' => '80 caracteres maximos permitidos.',
            'cpf_cnpj.required' => 'O campo CPF/CNPJ é obrigatório.',
            'cpf_cnpj.min' => strlen($doc) > 14 ? 'Informe 14 números para CNPJ.' : 'Informe 14 números para CPF.',
            'rua.required' => 'O campo Rua é obrigatório.',
            'rua.max' => '80 caracteres maximos permitidos.',
            'numero.required' => 'O campo Numero é obrigatório.',
            'cep.required' => 'O campo CEP é obrigatório.',
            'cep.min' => 'CEP inválido.',
            'cidade.required' => 'O campo Cidade é obrigatório.',
            'numero.max' => '10 caracteres maximos permitidos.',
            'bairro.required' => 'O campo Bairro é obrigatório.',
            'bairro.max' => '50 caracteres maximos permitidos.',
            'telefone.required' => 'O campo Telefone é obrigatório.',
            'telefone.max' => '20 caracteres maximos permitidos.',
            'consumidor_final.required' => 'O campo Consumidor final é obrigatório.',
            'contribuinte.required' => 'O campo Contribuinte é obrigatório.',
            'celular.max' => '20 caracteres maximos permitidos.',

            'email.required' => 'O campo Email é obrigatório.',
            'email.max' => '60 caracteres maximos permitidos.',
            'email.email' => 'Email inválido.',

            'rua_cobranca.max' => '80 caracteres maximos permitidos.',
            'numero_cobranca.max' => '10 caracteres maximos permitidos.',
            'bairro_cobranca.max' => '30 caracteres maximos permitidos.',
            'cep_cobranca.max' => '9 caracteres maximos permitidos.',

        ];
        $this->validate($request, $rules, $messages);
    }

    public function all(){
        $clientes = Cliente::all();
        $arr = array();
        foreach($clientes as $c){
            $arr[$c->id. ' - ' .$c->razao_social] = null;
                //array_push($arr, $temp);
        }
        echo json_encode($arr);
    }

    public function findOne($id){
        $item = Cliente::with('cidade')->findOrFail($id);

        $item->valor_cash_back = $item->valor_cashback;
        $config = CashBackConfig::where('empresa_id', $item->empresa_id)
        ->first();
        $item->config = $config;
        return response()->json($item, 200);
    }

    public function find($id){
        $cliente = Cliente::
        where('id', $id)
        ->first();

        echo json_encode($this->getCidade($cliente));
    }

    public function findCliente($id)
    {
        $cliente = Cliente::where('empresa_id', $this->empresa_id)
            ->with('cidade')
            ->findOrFail($id);

        return response()->json($cliente, 200);
    }

    public function findCliente_($id){
        $cliente = Cliente::
        where('id', $id)
        ->with('cidade')
        ->first();

        return response()->json($cliente, 200);
    }

    public function verificaLimite(Request $request){
        $cliente = Cliente::
        where('id', $request->id)
        ->first();

        $somaVendas = $this->somaVendasCredito($cliente);
        if($somaVendas != null){
            $cliente->soma = $somaVendas->total;
        }else{
            $cliente->soma = 0;
        }
        echo json_encode($cliente);
    }

    private function somaVendasCredito($cliente){
        return CreditoVenda::
        selectRaw('sum(vendas.valor_total) as total')
        ->join('vendas', 'vendas.id', '=', 'credito_vendas.venda_id')
        ->where('credito_vendas.cliente_id', $cliente->id)
        ->where('status', 0)
        ->first();
    }

    private function getCidade($transp){
        $temp = $transp;
        $transp['cidade'] = $transp->cidade;
        return $temp;
    }

    public function cpfCnpjDuplicado(Request $request){
        $cliente = Cliente::
        where('empresa_id', $request->empresa_id)
        ->where('cpf_cnpj', $request->cpf_cnpj)
        ->first();

        echo json_encode($cliente);
    }

    public function importacao(){
        $zip_loaded = extension_loaded('zip') ? true : false;
        if ($zip_loaded === false) {
            session()->flash('mensagem_erro', "Por favor instale/habilite o PHP zip para importar");
            return redirect()->back();
        }
        return view('clientes/importacao')
        ->with('title', 'Importação de clientes');
    }

    public function downloadModelo(){
        try{
            $public = env('SERVIDOR_WEB') ? 'public/' : '';
            return response()->download(public_path('files/') . 'import_clients_csv_template.xlsx');
        }catch(\Exception $e){
            echo $e->getMessage();
        }
    }

    public function importacaoStore(Request $request){

        if ($request->hasFile('file')) {

            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);

            $rows = Excel::toArray(new ProdutoImport, $request->file);
            $retornoErro = $this->validaArquivo($rows);

            if($retornoErro == ""){

                //armazenar no bd
                $teste = [];
                $cont = 0;

                foreach($rows as $row){
                    foreach($row as $key => $r){
                        if($r[0] != 'RAZÃO SOCIAL*'){
                            try{
                                $objeto = $this->preparaObjeto($r);

                                // print_r($objeto);
                                // die;
                                Cliente::create($objeto);
                                $cont++;
                            }catch(\Exception $e){
                                echo $cont;
                                echo $e->getMessage();
                                die;
                                session()->flash('mensagem_erro', $e->getMessage());
                                return redirect()->back();
                            }
                        }
                    }
                }

                session()->flash('mensagem_sucesso', "Clientes inseridos: $cont!!");
                return redirect('/clientes');

            }else{

                session()->flash('mensagem_erro', $retornoErro);
                return redirect()->back();
            }

        }else{
            session()->flash('mensagem_erro', 'Nenhum Arquivo!!');
            return redirect()->back();
        }

    }

    private function preparaObjeto($row){

        $cid = $row[7];
        $cidade = null;
        if(is_numeric($cid)){
            $cidade = Cidade::find($cid);
        }else{
            $uf = "";
            $temp = explode("-", $cid);
            if(isset($temp[1])){
                $uf = $temp[1];
                $cid = $temp[0];
            }
            if($uf != ""){

                $cidade = DB::select("select * from cidades where nome = '$cid' and uf = '$uf'");

                if($cidade == null){
                    $cidade = DB::select("select * from cidades where nome like '%$cid%' and uf = '$uf'");
                }

            }else{
                $cidade = DB::select("select * from cidades where nome = '$cid'");
                if($cidade == null){
                    $cidade = DB::select("select * from cidades where nome like '%$cid%'");
                }
            }
            if($cidade != null){
                $cidade = $cidade[0]->id;
            }else{
                $cidade = NULL;
            }
        }

        $doc = $this->adicionaMascara($row[2]);

        $ie = $row[3] ?? '';
        $arr = [
            'razao_social' => $row[0],
            'nome_fantasia' => $row[1] ?? $row[0],
            'bairro' => $row[6],
            'numero' => $row[5],
            'rua' => $row[4],
            'cpf_cnpj' => $doc,
            'telefone' => $row[8] ?? '',
            'celular' => $row[9] ?? '',
            'email' => $row[10] ?? '',
            'cep' => $row[11],
            'ie_rg' => $ie,
            'consumidor_final' => 1,
            'limite_venda' => $row[12] != "" ? __replace($row[12]) : 0,
            'cidade_id' => $cidade != null ? $cidade : 1,
            'contribuinte' => ($ie == '' || strtoupper($ie) == 'ISENTO') ? false : true,
            'rua_cobranca' => '',
            'numero_cobranca' => '',
            'bairro_cobranca' => '',
            'cep_cobranca' => '',
            'cidade_cobranca_id' => NULL,
            'empresa_id' => $this->empresa_id,
            'contador_nome' => $row[13] ?? '',
            'contador_telefone' => $row[14] ?? '',
            'contador_email' => $row[15] ?? '',

        ];
        return $arr;

    }

    private function adicionaMascara($doc){
        if(strlen($doc) == 14){

            $cnpj = substr($doc, 0, 2);
            $cnpj .= ".".substr($doc, 2, 3);
            $cnpj .= ".".substr($doc, 5, 3);
            $cnpj .= "/".substr($doc, 8, 4);
            $cnpj .= "-".substr($doc, 12, 2);
            return $cnpj;
        }else{
            $cpf = substr($doc, 0, 3);
            $cpf .= ".".substr($doc, 3, 3);
            $cpf .= ".".substr($doc, 6, 3);
            $cpf .= "-".substr($doc, 9, 2);

            return $cpf;
        }
    }

    private function validaArquivo($rows){
        $cont = 0;
        $msgErro = "";
        foreach($rows as $row){
            foreach($row as $key => $r){

                $razaoSocial = $r[0];
                $cnpj = $r[2];
                $ie = $r[3];
                $rua = $r[4];
                $numero = $r[5];
                $bairro = $r[6];
                $cidade = $r[7];
                $cep = $r[11];

                if(strlen($razaoSocial) == 0){
                    $msgErro .= "Coluna razão social em branco na linha: $cont | ";
                }

                if(strlen($cnpj) == 0){
                    $msgErro .= "Coluna cnpj/cpf em branco na linha: $cont | ";
                }

                if(strlen($ie) == 0){
                    $msgErro .= "Coluna ie/rg em branco na linha: $cont";
                }

                if(strlen($rua) == 0){
                    $msgErro .= "Coluna rua em branco na linha: $cont";
                }

                if(strlen($numero) == 0){
                    $msgErro .= "Coluna numero em branco na linha: $cont";
                }

                if(strlen($bairro) == 0){
                    $msgErro .= "Coluna bairro em branco na linha: $cont";
                }

                if(strlen($cidade) == 0){
                    $msgErro .= "Coluna cidade em branco na linha: $cont";
                }

                if(strlen($cep) == 0){
                    $msgErro .= "Coluna cep em branco na linha: $cont";
                }

                if($msgErro != ""){
                    return $msgErro;
                }

                $cont++;
            }

        }

        return $msgErro;
    }

    public function consultaCadastrado($doc){
        $doc = str_replace("_", "/", $doc);
        $cliente = Cliente::
        where('cpf_cnpj', $doc)
        ->where('empresa_id', $this->empresa_id)
        ->first();

        return response()->json($cliente, 200);
    }

    public function quickSave(Request $request){
        try{
            $data = $request->data;

            $temp = Cliente::
            where('empresa_id', $this->empresa_id)
            ->where('cpf_cnpj', $data['cpf_cnpj'])
            ->first();
            if($temp != null) {
                return response()->json("Cliente já cadastrado!", 401);
            }
            $cli = [
                'razao_social' => $data['razao_social'],
                'nome_fantasia' => $data['razao_social'],
                'bairro' => $data['bairro'] ?? '',
                'numero' => $data['numero'] ?? '',
                'rua' => $data['rua'] ?? '',
                'cpf_cnpj' => $data['cpf_cnpj'] ?? '',
                'telefone' => $data['telefone'] ?? '',
                'celular' => $data['celular'] ?? '',
                'email' => $data['email'] ?? '',
                'cep' => $data['cep'] ?? '',
                'ie_rg' => $data['ie_rg'] ?? '',
                'consumidor_final' => $data['consumidor_final'] ?? 1,
                'limite_venda' => $data['limite_venda'] ? __replace($data['limite_venda']) : 0,
                'cidade_id' => $data['cidade_id'] ?? 1,
                'contribuinte' => $data['contribuinte'] ?? 1,
                'rua_cobranca' => '',
                'numero_cobranca' => '',
                'bairro_cobranca' => '',
                'cep_cobranca' => '',
                'empresa_id' => $this->empresa_id,
                'cidade_cobranca_id' => NULL
            ];

            $res = Cliente::create($cli);
            $cli = Cliente::with('cidade')->where('id', $res->id)->first();
            return response()->json($cli, 200);
        }catch(\Exception $e){
            __saveError($e, $this->empresa_id);
            return response()->json($e->getMessage(), 401);
        }
    }

    public function cashBacks($id){
        $item = Cliente::findOrFail($id);
        if(valida_objeto($item)){

            return view('clientes.cash_back', compact('item'))
            ->with('title', 'Lista de CashBack');
        }else{
            return redirect('/403');
        }
    }

    public function upload($id){
        $item = Cliente::findOrFail($id);
        if(valida_objeto($item)){

            return view('clientes.upload', compact('item'))
            ->with('title', 'Upload de documentos');
        }else{
            return redirect('/403');
        }
    }

    public function uploadStore(Request $request, $id){
        $item = Cliente::findOrFail($id);

        if(!is_dir(public_path('documentos_clientes'))){
            mkdir(public_path('documentos_clientes'), 0777, true);
        }
        if(!$request->hasFile('file')){
            session()->flash("mensagem_erro", "Envie um arquivo para upload");
            return redirect()->back();
        }
        $file = $request->file('file');

        $extensao = $file->getClientOriginalExtension();
        $fName = Str::of($file->getClientOriginalName())->basename();
        $fName = explode('.',$fName)[0];

        $fileName = substr($fName, 0, 50) . "_" . Str::random(25) . ".".$extensao;

        $file->move(public_path('documentos_clientes'), $fileName);

        ClienteUpload::create([
            'cliente_id' => $item->id,
            'extensao' => $extensao,
            'file_name' => $fileName,
            'descricao' => $request->descricao
        ]);
        session()->flash("mensagem_sucesso", "Documento carregado!");
        return redirect()->back();
    }

    public function downloadDocumento($id){
        $item = ClienteUpload::findOrFail($id);
        $path = public_path('documentos_clientes/').$item->file_name;
        if(file_exists($path)){
            return response()->download($path);
        }
    }

    public function destroyUpload($id){
        $item = ClienteUpload::findOrFail($id);
        try{
            $path = public_path('documentos_clientes/').$item->file_name;
            if(file_exists($path)){
                unlink($path);
            }
            $item->delete();
            session()->flash("mensagem_sucesso", "Documento removido!");
        }catch(\Exception $e){
            session()->flash("mensagem_erro", "Algo deu errado: " . $e->getMessage());
        }
        return redirect()->back();
    }

    public function searchCliente(Request $request)
    {
        $term = $request->get('term', '');

        $clientes = Cliente::where('empresa_id', $this->empresa_id)
            ->where(function ($query) use ($term) {
                $query->where('razao_social', 'like', "%{$term}%")
                    ->orWhere('cpf_cnpj', 'like', "%{$term}%")
                    ->orWhere('telefone', 'like', "%{$term}%")
                    ->orWhere('cidade_id', 'like', "%{$term}%")
                    ->orWhere('nome_fantasia', 'like', "%{$term}%");
            })
            ->limit(10)
            ->get([
                'id',
                'razao_social',
                'nome_fantasia',
                'cpf_cnpj',
                'telefone',
                'cidade_id',
                'imagem',
            ]);

        $results = $clientes->map(function ($cliente) {
            return [
                'id' => $cliente->id,
                'text' => "{$cliente->razao_social} | {$cliente->cpf_cnpj}",
                'razao_social' => $cliente->razao_social ?? 'N/A',
                'nome_fantasia' => $cliente->nome_fantasia ?? 'N/A',
                'cpf_cnpj' => $cliente->cpf_cnpj ?? 'N/A',
                'telefone' => $cliente->telefone ?? 'N/A',
                'cidade' => $cliente->cidade->nome ?? 'N/A',
                'imgApp' => $cliente->imagem
                    ? env("PATH_URL") . "/imgs_clientes/{$cliente->imagem}"
                    : env("PATH_URL") . "/imgs/no_clientes.png",
            ];
        });

        return response()->json(['results' => $results]);
    }

}
