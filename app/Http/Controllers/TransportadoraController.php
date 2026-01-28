<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transportadora;
use App\Models\Cliente;
use App\Models\Cidade;

class TransportadoraController extends Controller
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
    $transportadoras = Transportadora::
    where('empresa_id', $this->empresa_id)
    ->get();
    return view('transportadora/list')
    ->with('transportadoras', $transportadoras)
    ->with('title', 'Transportadoras');
  }

  public function new(){

    $estados = Cliente::estados();
    $cidades = Cidade::all();
    return view('transportadora/register')
    ->with('pessoaFisicaOuJuridica', true)
    ->with('cidadeJs', true)
    ->with('estados', $estados)
    ->with('cidades', $cidades)
    ->with('title', 'Cadastrar Transportadora');
  }

  public function save(Request $request){
    $transp = new Transportadora();
    $this->_validate($request);
    try{
      $cidade = $request->input('cidade');
      $cidade = explode("-", $cidade);
      $cidade = $cidade[0];
      $request->merge([ 'cidade_id' => $cidade]);
      $request->merge([ 'email' => $request->email ?? '']);
      $request->merge([ 'telefone' => $request->telefone ?? '']);

      $transp->create($request->all());
      session()->flash("mensagem_sucesso", "Transportadora cadastrada com sucesso!");
    }catch(\Exception $e){
      __saveError($e, $this->empresa_id);
      session()->flash("mensagem_erro", "Algo deu errado: " . $e->getMessage());
    }

    return redirect('/transportadoras');
  }

  public function edit($id){
    $transp = new Transportadora(); 

    $resp = $transp
    ->where('id', $id)->first();  
    if(valida_objeto($resp)){
      $estados = Cliente::estados();
      $cidades = Cidade::all();
      return view('transportadora/register')
      ->with('pessoaFisicaOuJuridica', true)
      ->with('cidadeJs', true)
      ->with('transp', $resp)
      ->with('estados', $estados)
      ->with('cidades', $cidades)
      ->with('title', 'Editar Transportadora');
    }else{
      return redirect('/403');
    }

  }

  public function update(Request $request){
    $this->_validate($request);
    $transp = new Transportadora();

    try{
      $resp = $transp->findOrFail($request->id); 

      $cidade = $request->input('cidade');
      $cidade = explode("-", $cidade);
      $cidade = $cidade[0];

      $resp->razao_social = $request->input('razao_social');

      $resp->cnpj_cpf = $request->input('cnpj_cpf');
      $resp->cidade_id = $cidade;

      $resp->logradouro = $request->input('logradouro');
      $resp->email = $request->input('email');
      $resp->telefone = $request->input('telefone');

      $resp->save();
      session()->flash('mensagem_sucesso', 'Transportadora editada com sucesso!');
    }catch(\Exception $e){
      __saveError($e, $this->empresa_id);
      session()->flash("mensagem_erro", "Algo deu errado: " . $e->getMessage());
    }

    return redirect('/transportadoras'); 
  }

  public function delete($id){
    $resp = Transportadora
    ::where('id', $id)
    ->first();
    if(valida_objeto($resp)){

      if($resp->delete()){
        session()->flash('mensagem_sucesso', 'Registro removido!');
      }else{
        session()->flash('mensagem_erro', 'Erro!');
      }
      return redirect('/transportadoras');
    }else{
      return redirect('/403');
    }
  }


  private function _validate(Request $request){
    $rules = [
      'razao_social' => 'required|max:50',
      'cnpj_cpf' => 'required',
      'logradouro' => 'required|max:80',
      'cidade' => 'required',
    ];

    $messages = [
      'razao_social.required' => 'O Razão social nome é obrigatório.',
      'razao_social.max' => '50 caracteres maximos permitidos.',

      'cnpj_cpf.required' => 'O campo CPF/CNPJ é obrigatório.',
      'logradouro.required' => 'O campo Rua é obrigatório.',
      'logradouro.max' => '80 caracteres maximos permitidos.',

      'cidade.required' => 'O campo Cidade é obrigatório.',

    ];
    $this->validate($request, $rules, $messages);
  }

  public function all(){
    $clientes = Transportadora::all();
    $arr = array();
    foreach($clientes as $c){
      $arr[$c->id. ' - ' .$c->razao_social] = null;
                //array_push($arr, $temp);
    }
    echo json_encode($arr);
  }

  public function find($id){
    $cliente = Transportadora::
    where('id', $id)
    ->first();

    echo json_encode($this->getCidade($cliente));
  }

  private function getCidade($transp){
    $temp = $transp;
    $transp['cidade'] = $transp->cidade;
    return $temp;
  }

  public function quickSave(Request $request){
    try{
      $data = $request->data;

      $temp = Transportadora::
      where('cnpj_cpf', $data['cpf_cnpj'])
      ->where('empresa_id', $this->empresa_id)
      ->first();
      if($temp != null){
        return response()->json("Transportadora já cadastrada", 401);
      }
      $transp = [
        'razao_social' => $data['razao_social'],
        'logradouro' => $data['logradouro'] ?? '',
        'numero' => $data['numero'] ?? '',
        'cnpj_cpf' => $data['cpf_cnpj'] ?? '',
        'telefone' => $data['telefone'] ?? '',
        'email' => $data['email'] ?? '',
        'cidade_id' => $data['cidade_id'] ?? 1, 
        'empresa_id' => $this->empresa_id, 
      ];

      $res = Transportadora::create($transp);
      return response()->json($res, 200);
    }catch(\Exception $e){
      return response()->json($e->getMessage(), 401);
    }
  }

}
