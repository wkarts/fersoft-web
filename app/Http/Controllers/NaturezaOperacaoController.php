<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NaturezaOperacao;
use App\Models\CategoriaConta;

class NaturezaOperacaoController extends Controller
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

  function sanitizeString($str){
    return preg_replace('{\W}', ' ', preg_replace('{ +}', ' ', strtr(
      utf8_decode(html_entity_decode($str)),
      utf8_decode('ÀÁÃÂÉÊÍÓÕÔÚÜÇÑàáãâéêíóõôúüçñ'),
      'AAAAEEIOOOUUCNaaaaeeiooouucn')));
  }

  public function index(Request $request){
    $naturezas = NaturezaOperacao::
    where('empresa_id', $request->empresa_id)
    ->get();

    return view('naturezaOperacao/list')
    ->with('naturezas', $naturezas)
    ->with('title', 'Naturezas de Operação');
  }

  public function new(){
    $categoriasDeConta = CategoriaConta::where('empresa_id', $this->empresa_id)
    ->where('tipo', 'receber')
    ->orderBy('nome', 'asc')->get();

    return view('naturezaOperacao/register', compact('categoriasDeConta'))
    ->with('title', 'Cadastrar Natureza de Operação');
  }

  public function save(Request $request){
    $natureza = new NaturezaOperacao();
    $this->_validate($request);
    $request->merge([ 'natureza' => strtoupper($this->sanitizeString($request->input('natureza')))]);

    $request->merge([ 'sobrescreve_cfop' => $request->input('sobrescreve_cfop') ? true : false ]);
    $request->merge([ 'nao_movimenta_estoque' => $request->input('nao_movimenta_estoque') ? true : false ]);

    $result = $natureza->create($request->all());

    if($result){
      session()->flash("mensagem_sucesso", "Natureza de Operação cadastrada com sucesso.");
    }else{
      session()->flash('mensagem_erro', 'Erro ao cadastrar natureza de operação.');
    }

    return redirect('/naturezaOperacao');
  }

  public function edit($id){

    $resp = NaturezaOperacao::findOrFail($id);

    $categoriasDeConta = CategoriaConta::where('empresa_id', $this->empresa_id)
    ->where('tipo', 'receber')
    ->orderBy('nome', 'asc')->get();

    if(valida_objeto($resp)){
      return view('naturezaOperacao/register')
      ->with('natureza', $resp)
      ->with('categoriasDeConta', $categoriasDeConta)
      ->with('title', 'Editar natureza de operação');
    }else{
      return redirect('/403');
    }

  }

  public function update(Request $request){
    $natureza = new NaturezaOperacao();

    $id = $request->input('id');
    $resp = $natureza
    ->where('id', $id)->first(); 

    $this->_validate($request);

    $resp->natureza = $this->sanitizeString(strtoupper($request->input('natureza')));
    $resp->CFOP_entrada_estadual = $request->input('CFOP_entrada_estadual');
    $resp->CFOP_entrada_inter_estadual = $request->input('CFOP_entrada_inter_estadual');
    $resp->CFOP_saida_estadual = $request->input('CFOP_saida_estadual');
    $resp->CFOP_saida_inter_estadual = $request->input('CFOP_saida_inter_estadual');
    $resp->sobrescreve_cfop = $request->input('sobrescreve_cfop') ? true : false;
    $resp->nao_movimenta_estoque = $request->input('nao_movimenta_estoque') ? true : false;
    $resp->finNFe = $request->input('finNFe');
    $resp->CST_CSOSN = $request->input('CST_CSOSN');

    $result = $resp->save();
    if($result){

      session()->flash('mensagem_sucesso', 'Natureza de operação editada com sucesso!');
    }else{
      session()->flash('mensagem_ero', 'Erro ao editar categoria!');
    }

    return redirect('/naturezaOperacao'); 
  }

  public function delete($id){
    $natureza = NaturezaOperacao
    ::where('id', $id)
    ->first();
    if(valida_objeto($natureza)){

      try{
        $natureza->delete();
        session()->flash('mensagem_sucesso', 'Registro removido!');
      }catch(\Exception $e){
        session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
      }
      return redirect('/naturezaOperacao');
    }else{
      return redirect('403');
    }
  }

  private function _validate(Request $request){
    $rules = [
      'natureza' => 'required|max:80',
      'CFOP_entrada_estadual' => 'required|min:4',
      'CFOP_entrada_inter_estadual' => 'required|min:4',
      'CFOP_saida_estadual' => 'required|min:4',
      'CFOP_saida_inter_estadual' => 'required|min:4',
    ];

    $messages = [
      'natureza.required' => 'O campo nome é obrigatório.',
      'natureza.max' => '80 caracteres maximos permitidos.',
      'CFOP_entrada_estadual.required' => 'Campo obritatório.',
      'CFOP_entrada_estadual.min' => 'Minimo de 4 digitos.',
      'CFOP_entrada_inter_estadual.required' => 'Campo obritatório.',
      'CFOP_entrada_inter_estadual.min' => 'Minimo de 4 digitos.',
      'CFOP_saida_estadual.required' => 'Campo obritatório.',
      'CFOP_saida_estadual.min' => 'Minimo de 4 digitos.',
      'CFOP_saida_inter_estadual.required' => 'Campo obritatório.',
      'CFOP_saida_inter_estadual.min' => 'Minimo de 4 digitos.',
    ];
    $this->validate($request, $rules, $messages);
  }

  public function find($id){
    $item = NaturezaOperacao::findOrFail($id);
    return response()->json($item, 200);
  }
}
