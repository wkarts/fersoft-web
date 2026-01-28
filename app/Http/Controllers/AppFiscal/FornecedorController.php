<?php

namespace App\Http\Controllers\AppFiscal;

use Illuminate\Http\Request;
use App\Models\Fornecedor;
use App\Models\Cidade;
use App\Rules\ValidaCep;
use App\Rules\ValidaDocumento;
use Illuminate\Support\Facades\Validator;

class FornecedorController extends Controller
{
	public function fornecedores(Request $request){
		$fornecedores = Fornecedor::
		where('empresa_id', $request->empresa_id)
		->get();
		foreach($fornecedores as $c){
			$c->cidade;
		}
		return response()->json($fornecedores, 200);
	}

        public function salvar(Request $request){

                $this->validateFornecedor($request);

                if($request->id > 0){
                        $fornecedor = Fornecedor::find($request->id);
			$fornecedor->razao_social = $request->razao_social;
			$fornecedor->nome_fantasia = $request->nome_fantasia;
			$fornecedor->bairro = $request->bairro;
			$fornecedor->numero = $request->numero;
			$fornecedor->rua = $request->logradouro;
                        $fornecedor->cpf_cnpj = $request->cpf_cnpj;
                        $fornecedor->telefone = $request->telefone;
                        $fornecedor->celular = $request->celular;
			$fornecedor->email = $request->email;
			$fornecedor->cep = $request->cep;
			$fornecedor->ie_rg = $request->ie_rg;
			$fornecedor->cidade_id = $request->cidade;
			$res = $fornecedor->save();
		}else{
			$data = [
				'razao_social' => $request->razao_social,
				'nome_fantasia' => $request->nome_fantasia,
				'bairro' => $request->bairro,
				'numero' => $request->numero,
				'rua' => $request->logradouro,
				'cpf_cnpj' => $request->cpf_cnpj,
				'telefone' => $request->telefone ?? '',
				'celular' => $request->celular ?? '',
				'email' => $request->email,
				'cep' => $request->cep,
				'ie_rg' => $request->ie_rg,
				'cidade_id' => $request->cidade,
				'empresa_id' => $request->empresa_id
			];
                        $res = Fornecedor::create($data);
                }


                return response()->json($res, 200);
        }

        private function validateFornecedor(Request $request): void
        {
                $rules = [
                        'razao_social'  => 'required|max:80',
                        'nome_fantasia' => 'required|max:80',
                        'bairro'        => 'required|max:80',
                        'numero'        => 'required|max:10',
                        'logradouro'    => 'required|max:80',
                        'cep'           => ['required', new ValidaCep],
                        'cpf_cnpj'      => ['required', new ValidaDocumento],
                        'cidade'        => 'required|exists:cidades,id',
                ];

                $messages = [
                        'razao_social.required'  => 'Razão social é obrigatória.',
                        'razao_social.max'       => 'Razão social deve ter no máximo 80 caracteres.',
                        'nome_fantasia.required' => 'Nome fantasia é obrigatório.',
                        'nome_fantasia.max'      => 'Nome fantasia deve ter no máximo 80 caracteres.',
                        'bairro.required'        => 'Bairro é obrigatório.',
                        'bairro.max'             => 'Bairro deve ter no máximo 80 caracteres.',
                        'numero.required'        => 'Número é obrigatório.',
                        'numero.max'             => 'Número deve ter no máximo 10 caracteres.',
                        'logradouro.required'    => 'Logradouro é obrigatório.',
                        'logradouro.max'         => 'Logradouro deve ter no máximo 80 caracteres.',
                        'cep.required'           => 'CEP é obrigatório.',
                        'cpf_cnpj.required'      => 'CPF/CNPJ é obrigatório.',
                        'cidade.required'        => 'Cidade é obrigatória.',
                        'cidade.exists'          => 'Cidade não encontrada.',
                ];

                Validator::make($request->all(), $rules, $messages)->validate();
        }

	public function cidades(){
		$cidades = Cidade::all();
		return response()->json($cidades, 200);
	}

	public function ufs(){
		$ufs = Cidade::
		selectRaw('distinct(uf) as uf')
		->get();
		$arrTemp = [];
		foreach($ufs as $u){
			array_push($arrTemp, $u->uf);
		}
		return response()->json($arrTemp, 200);
	}

	public function delete(Request $request){
		$fornecedor = Fornecedor::find($request->id);
		$delete = $fornecedor->delete();
		return response()->json($delete, 200);
	}
}