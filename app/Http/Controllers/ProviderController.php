<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Fornecedor;
use App\Models\Cidade;
use App\Models\Cliente;
use App\Models\Pais;
use App\Rules\ValidaDocumento;
use App\Rules\ValidaCep;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ProviderController extends BaseController
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
  
  /**
     * Regras de validação obrigatórias do BaseController
     */
    protected function rules(): array
    {
        return [
            // Aqui você pode colocar as regras do fornecedor no futuro, se quiser.
            // Exemplo: 'razao_social' => 'required|string|max:255',
        ];
    }

    /**
     * Mensagens de erro obrigatórias do BaseController
     */
    protected function messages(): array
    {
        return [
            // Aqui entram as mensagens traduzidas.
            // Exemplo: 'razao_social.required' => 'O campo Razão Social é obrigatório.',
        ];
    }

    public function index(){
    	
        $fornecedores = Fornecedor::with('cidade')
            ->where('empresa_id', $this->empresa_id)
            ->paginate(30); 
            
        return view('fornecedores/list')
            ->with('fornecedores', $fornecedores)
            ->with('title', 'Fornecedores');
    }

    public function pesquisa(Request $request){
        $pesquisa = $request->input('pesquisa');

        $fornecedores = Fornecedor::with('cidade') // Eager Loading
            ->where('empresa_id', $this->empresa_id)
            ->where($request->tipo_pesquisa, 'LIKE', "%$pesquisa%")
            ->paginate(30)
            ->appends($request->except('page'));

        return view('fornecedores/list')
            ->with('fornecedores', $fornecedores)
            ->with('tipoPesquisa', $request->tipo_pesquisa)
            ->with('pesquisa', $pesquisa)
            ->with('title', 'Filtro Fornecedor');
    }

    public function new(){
        $cidades = Cidade::all();
        $estados = Cliente::estados();
        $pais = Pais::all();

        session(['fornecedor_save_token' => Str::uuid()->toString()]);

        return view('fornecedores/register')
            ->with('pessoaFisicaOuJuridica', true)
            ->with('cidadeJs', true)
            ->with('cidades', $cidades)
            ->with('pais', $pais)
            ->with('estados', $estados)
            ->with('title', 'Cadastrar Fornecedor');
    }

    public function save(Request $request){
        $this->normalizeDocumentoECep($request);
        $this->_validate($request);

        if(!$this->registrarTokenCadastro($request)){
            session()->flash("mensagem_erro", "Cadastro de fornecedor já está em processamento. Aguarde a conclusão antes de tentar novamente.");
            return redirect('/fornecedores');
        }

        try {
            $cidade = $request->input('cidade');
            $request->merge([
                'cidade_id'      => $cidade,
                'telefone'       => $request->input('telefone') ?? '',
                'celular'        => $request->input('celular') ?? '',
                'ie_rg'          => $request->input('ie_rg') ?? '',
                'pix'            => $request->input('pix') ?? '',
                'complemento'    => $request->input('complemento') ?? '',
                'tipo_pix'       => $request->input('tipo_pix') ?? 'cpf',
                'email'          => $request->email ?? '',
                'id_estrangeiro' => $request->id_estrangeiro ?? '',
                // Novos campos adicionados corretamente no array
                'banco'          => $request->input('banco') ?? '',
                'agencia'        => $request->input('agencia') ?? '',
                'conta'          => $request->input('conta') ?? '',
                'tabela_preco_id'=> $request->input('tabela_preco_id') ?: null,
            ]);

            $result = Fornecedor::create($request->all());

            $this->criarLog($result);
            session()->flash("mensagem_sucesso", "Fornecedor cadastrado com sucesso!");
        } catch(\Exception $e) {
            __saveError($e, $this->empresa_id);
            session()->flash("mensagem_erro", "Algo deu errado: " . $e->getMessage());
        }
        return redirect('/fornecedores');
    }

    private function registrarTokenCadastro(Request $request): bool
    {
        $token = $request->input('_save_token');

        if(!$token){
            return true;
        }

        return Cache::add('fornecedor_save_token_' . $token, true, now()->addMinutes(5));
    }

    public function edit($id){
        $resp = Fornecedor::where('id', $id)->first();
        $cidades = Cidade::all();
        $estados = Cliente::estados();

        if(valida_objeto($resp)){
            $pais = Pais::all();

            return view('fornecedores/register')
                ->with('cidadeJs', true)
                ->with('pessoaFisicaOuJuridica', true)
                ->with('forn', $resp)
                ->with('pais', $pais)
                ->with('cidades', $cidades)
                ->with('estados', $estados)
                ->with('title', 'Editar Fornecedor');
        }else{
            return redirect('403');
        }
    }

    public function update(Request $request, $id = null)
	{
        $resp = Fornecedor::findOrFail($request->id);

        $this->normalizeDocumentoECep($request);
        $this->_validate($request);

        try{
            $cidade = $request->input('cidade');

            $resp->razao_social   = $request->input('razao_social');
            $resp->nome_fantasia  = $request->input('nome_fantasia');
            $resp->cpf_cnpj       = $request->input('cpf_cnpj');
            $resp->ie_rg          = $request->input('ie_rg') ?? '';
            $resp->rua            = $request->input('rua');
            $resp->numero         = $request->input('numero');
            $resp->bairro         = $request->input('bairro');
            $resp->telefone       = $request->input('telefone') ?? '';
            $resp->celular        = $request->input('celular') ?? '';
            $resp->pix            = $request->input('pix') ?? '';
            $resp->complemento    = $request->input('complemento') ?? '';
            $resp->tipo_pix       = $request->input('tipo_pix') ?? 'cpf';
            $resp->email          = $request->input('email');
            $resp->cep            = $request->input('cep');
            $resp->contribuinte   = $request->input('contribuinte');
            $resp->cidade_id      = $cidade;
            $resp->cod_pais       = $request->input('cod_pais');
            $resp->id_estrangeiro = $request->input('id_estrangeiro');
            
            // Novos campos para atualização
            $resp->banco          = $request->input('banco') ?? '';
            $resp->agencia        = $request->input('agencia') ?? '';
            $resp->conta          = $request->input('conta') ?? '';
            $resp->tabela_preco_id = $request->input('tabela_preco_id') ?: null;

            $resp->save();

            $this->criarLog($resp, 'atualizar');
            session()->flash('mensagem_sucesso', 'Fornecedor editado com sucesso!');
        }catch(\Exception $e){
            __saveError($e, $this->empresa_id);
            session()->flash("mensagem_erro", "Algo deu errado: " . $e->getMessage());
        }
        return redirect('/fornecedores');
    }

    public function find($id){
        $fornecedor = Fornecedor::where('id', $id)->first();
        echo json_encode($this->insertCidade($fornecedor));
    }

    private function insertCidade($fornecedor){
        $cidade = Cidade::getId($fornecedor->cidade_id);
        $fornecedor['nome_cidade'] = $cidade->nome;
        return $fornecedor;
    }

    public function delete($id){
        try{
            $resp = Fornecedor::where('id', $id)->first();
            if(valida_objeto($resp)){
                $this->criarLog($resp, 'deletar');

                if($resp->delete()){
                    session()->flash('mensagem_sucesso', 'Registro removido!');
                }else{
                    session()->flash('mensagem_erro', 'Erro!');
                }
                return redirect('/fornecedores');
            }else{
                return redirect('403');
            }
        }catch(\Exception $e){
            return view('errors.sql')
                ->with('title', 'Erro ao deletar fornecedor')
                ->with('motivo', 'Não é possivel remover fornecedor, presentes em compras!');
        }
    }

    private function _validate(Request $request){
        $isExterior = $this->isExterior($request);

        $rules = [
            'razao_social'  => 'required|max:80',
            'nome_fantasia' => 'required|max:80',
            'rua'           => 'required|max:80',
            'numero'        => 'required|max:10',
            'bairro'        => 'required|max:50',
            'telefone'      => 'max:20',
            'celular'       => 'max:20',
            'email'         => 'max:40',
            'cidade'        => 'required',
            'ie_rg'         => 'max:20',
            'tipo_pix'      => strlen($request->pix) > 0 ? 'required' : '',
        ];

        $docDigits = preg_replace('/\D/', '', (string)$request->input('cpf_cnpj'));

        if ($isExterior) {
            // Documento opcional. Se vier algo diferente de "14 zeros", validamos.
            if ($request->filled('cpf_cnpj') && $docDigits !== '00000000000000') {
                $rules['cpf_cnpj'] = [new ValidaDocumento];
            } else {
                $rules['cpf_cnpj'] = 'nullable';
            }
            // CEP opcional no exterior
            $rules['cep'] = 'nullable';
        } else {
            // Brasil
            $rules['cpf_cnpj'] = ['required', new ValidaDocumento];
            $rules['cep']      = ['required', new ValidaCep];
        }

        $messages = [
            'razao_social.required'  => 'O campo Razão social é obrigatório.',
            'razao_social.max'       => '100 caracteres máximos permitidos.',
            'nome_fantasia.required' => 'O campo Nome Fantasia é obrigatório.',
            'nome_fantasia.max'      => '80 caracteres máximos permitidos.',
            'rua.required'           => 'O campo Rua é obrigatório.',
            'rua.max'                => '80 caracteres máximos permitidos.',
            'numero.required'        => 'O campo Número é obrigatório.',
            'numero.max'             => '10 caracteres máximos permitidos.',
            'bairro.required'        => 'O campo Bairro é obrigatório.',
            'bairro.max'             => '50 caracteres máximos permitidos.',
            'telefone.max'           => '20 caracteres máximos permitidos.',
            'celular.max'            => '20 caracteres máximos permitidos.',
            'email.max'              => '40 caracteres máximos permitidos.',
            'email.email'            => 'Email inválido.',
            'cidade.required'        => 'O campo Cidade é obrigatório.',
            'ie_rg.max'              => '20 caracteres máximos permitidos.',
            'tipo_pix.required'      => 'Campo obrigatório.',
            'cep.required'           => 'O campo CEP é obrigatório.',
            'cpf_cnpj.required'      => 'O campo CPF/CNPJ é obrigatório.',
        ];

        $this->validate($request, $rules, $messages);
    }

    /** Define "Exterior" por: radio p_ext, país != 1058 (Brasil) ou CNPJ 14 zeros */
    private function isExterior(Request $request): bool
    {
        if ($request->input('group1') === 'p_ext') return true;
        if (!empty($request->cod_pais) && (string)$request->cod_pais !== '1058') return true;

        $doc = preg_replace('/\D/', '', (string)$request->input('cpf_cnpj'));
        if ($doc === '00000000000000') return true;

        return false;
    }

    /** Normaliza documento/cep antes de validar/salvar */
    private function normalizeDocumentoECep(Request $request): void
    {
        $isExterior = $this->isExterior($request);

        if ($isExterior) {
            // Exterior: força o campo para "00.000.000/0000-00" (pedidos por você)
            $doc = (string)$request->input('cpf_cnpj');
            $docDigits = preg_replace('/\D/', '', $doc);
            if (!$doc || $docDigits === '' || $docDigits === '00000000000000') {
                $request->merge(['cpf_cnpj' => '00.000.000/0000-00']);
            } else {
                // Se o usuário informou algo, deixamos como digitou (pode ser outro formato), validação cuida
                $request->merge(['cpf_cnpj' => $doc]);
            }
            // CEP exterior não sofre higienização
        } else {
            // Brasil: higieniza para dígitos
            $request->merge([
                'cpf_cnpj' => preg_replace('/\D/', '', (string)$request->input('cpf_cnpj')),
                'cep'      => preg_replace('/\D/', '', (string)$request->input('cep')),
            ]);
        }
    }

    private function criarLog($objeto, $tipo = 'criar'){
        if(isset(session('user_logged')['log_id'])){
            $record = [
                'tipo' => $tipo,
                'usuario_log_id' => session('user_logged')['log_id'],
                'tabela' => 'fornecedores',
                'registro_id' => $objeto->id,
                'empresa_id' => $this->empresa_id
            ];
            __saveLog($record);
        }
    }

    public function all(){
        $providers = Fornecedor::where('empresa_id', $this->empresa_id)->get();
        $arr = [];
        foreach($providers as $c){
            $arr[$c->id. ' - ' .$c->razao_social] = null;
        }
        echo json_encode($arr);
    }

    public function consultaCadastrado($doc){
        $doc = str_replace("_", "/", $doc);
        $cliente = Fornecedor::where('cpf_cnpj', $doc)
            ->where('empresa_id', $this->empresa_id)
            ->first();

        return response()->json($cliente, 200);
    }

    public function quickSave(Request $request){
        try{
            $data = $request->data ?? [];

            $isExterior = false;
            $rawDoc = (string)($data['cpf_cnpj'] ?? '');
            $rawDocDigits = preg_replace('/\D/', '', $rawDoc);

            if (($data['group1'] ?? '') === 'p_ext'
                || (!empty($data['cod_pais']) && (string)$data['cod_pais'] !== '1058')
                || $rawDocDigits === '00000000000000') {
                $isExterior = true;
            }

            if ($isExterior) {
                if (!$rawDoc || $rawDocDigits === '' || $rawDocDigits === '00000000000000') {
                    $data['cpf_cnpj'] = '00.000.000/0000-00';
                }
                // CEP opcional no quick exterior
            } else {
                $data['cpf_cnpj'] = preg_replace('/\D/', '', $rawDoc);
                if (isset($data['cep'])) $data['cep'] = preg_replace('/\D/','', $data['cep']);
            }

            $cli = [
                'razao_social'  => $data['razao_social'] ?? '',
                'nome_fantasia' => $data['razao_social'] ?? '',
                'bairro'        => $data['bairro'] ?? '',
                'numero'        => $data['numero'] ?? '',
                'rua'           => $data['rua'] ?? '',
                'cpf_cnpj'      => $data['cpf_cnpj'] ?? '',
                'telefone'      => $data['telefone'] ?? '',
                'celular'       => $data['celular'] ?? '',
                'email'         => $data['email'] ?? '',
                'cep'           => $data['cep'] ?? '',
                'ie_rg'         => $data['ie_rg'] ?? '',
                'pix'           => $data['pix'] ?? '',
                'complemento'   => $data['complemento'] ?? '',
                'tipo_pix'      => $data['tipo_pix'] ?? 'cpf',
                'cidade_id'     => $data['cidade_id'] ?? 1,
                'contribuinte'  => $data['contribuinte'] ?? 1,
                'empresa_id'    => $this->empresa_id,
            ];

            // Validação quick
            $rulesQuick = [
                'razao_social'  => 'required|max:80',
                'nome_fantasia' => 'required|max:80',
                'cidade_id'     => 'required',
            ];
            if ($isExterior) {
                if (!empty($cli['cpf_cnpj']) && preg_replace('/\D/','',$cli['cpf_cnpj']) !== '00000000000000') {
                    $rulesQuick['cpf_cnpj'] = [new ValidaDocumento];
                } // else opcional
                // cep opcional
            } else {
                $rulesQuick['cpf_cnpj'] = ['required', new ValidaDocumento];
                if (!empty($cli['cep'])) $rulesQuick['cep'] = [new ValidaCep]; // no quick deixei opcional
            }

            $validator = Validator::make($cli, $rulesQuick, [
                'razao_social.required' => 'Razão social é obrigatória.',
                'cpf_cnpj.required'     => 'CPF/CNPJ é obrigatório.',
                'cidade_id.required'    => 'Cidade é obrigatória.',
            ]);

            if ($validator->fails()) {
                return response()->json(['ok' => false, 'errors' => $validator->errors()], 422);
            }

            $res = Fornecedor::create($cli);
            return response()->json($res, 200);
        }catch(\Exception $e){
            return response()->json($e->getMessage(), 401);
        }
    }

    public function historico($id){
        $fornecedor = Fornecedor::findOrFail($id);

        // Busca os lançamentos financeiros vinculados ao fornecedor
        // O 'with' tenta carregar os dados da compra se existirem
        $contas = \App\Models\ContaPagar::with('compra')
            ->where('fornecedor_id', $id)
            ->where('empresa_id', $this->empresa_id)
            ->orderBy('data_vencimento', 'desc')
            ->get();

        $historico = [];
        foreach($contas as $c) {
            // Se houver uma compra vinculada, pega o número da NF dela
            $nf = $c->compra ? $c->compra->numero_emissao : ($c->numero_nota_fiscal ?? '--');

            $valorTotal = (float)$c->valor_integral;
            $valorPago = (float)$c->valor_pago;
            $falta = $valorTotal - $valorPago;

            $historico[] = [
                'emissao' => $c->data_emissao ? \Carbon\Carbon::parse($c->data_emissao)->format('d/m/Y') : '--',
                'nf' => $nf,
                'vencimento' => $c->data_vencimento ? \Carbon\Carbon::parse($c->data_vencimento)->format('d/m/Y') : '--',
                'pagamento' => $c->data_pagamento ? \Carbon\Carbon::parse($c->data_pagamento)->format('d/m/Y') : '--',
                'valor_total' => number_format($valorTotal, 2, ',', '.'),
                'valor_pago' => number_format($valorPago, 2, ',', '.'),
                'falta' => number_format($falta, 2, ',', '.'),
                'status' => $c->status ? 'Pago' : 'Pendente'
            ];
        }

        return response()->json([
            'fornecedor' => $fornecedor->razao_social,
            'historico' => $historico
        ]);
    }
}
