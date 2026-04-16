<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Funcionario;
use App\Models\ContatoFuncionario;
use App\Models\Usuario;
use App\Models\ComissaoVenda;
use App\Models\VendaCaixa;
use App\Services\SaveFilesDB;

class FuncionarioController extends Controller
{
    protected $empresa_id = null;
    protected $saveFilesDB;
    protected $directoryPath;

    public function __construct(SaveFilesDB $saveFilesDB)
    {
        $this->saveFilesDB = $saveFilesDB;
        $this->directoryPath = public_path('imgs_funcionarios/');

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
        $funcionarios = Funcionario::
        where('empresa_id', $this->empresa_id)
        ->get();
        
        $funcoes = \App\Models\Funcao::where('empresa_id', $this->empresa_id)->get();
        $filiais = \App\Models\Filial::where('empresa_id', $this->empresa_id)->get();

        return view('funcionarios/list')
        ->with('funcionarios', $funcionarios)
        ->with('funcoes', $funcoes)
        ->with('filiais', $filiais)
        ->with('title', 'Funcionarios');
    }

    public function new(){
        $usuarios = Usuario::where('empresa_id', $this->empresa_id)->get();
        $funcionarios = Funcionario::where('empresa_id', $this->empresa_id)->get();
        
        $funcoes = \App\Models\Funcao::where('empresa_id', $this->empresa_id)->get();
        $filiais = \App\Models\Filial::where('empresa_id', $this->empresa_id)->get();

        $temp = [];
        foreach($usuarios as $u){
            if(!isset($u->funcionario)){
                array_push($temp, $u);
            }
        }
        return view('funcionarios/register')
        ->with('usuarios', $temp)
        ->with('funcoes', $funcoes)
        ->with('filiais', $filiais)
        ->with('empresa_id', $this->empresa_id)
        ->with('title', 'Cadastrar Funcionario');
    }

    private function parseDate($date){
        return date('Y-m-d', strtotime(str_replace("/", "-", $date)));
    }

    public function save(Request $request){
        $funcionario = new Funcionario();
        $this->_validate($request);

        $dataRegsitro = $this->parseDate($request->input('data_registro'));
        $request->merge([ 'data_registro' => $dataRegsitro]);

        $request->merge([ 'email' => $request->email ?? '']);
        $request->merge([ 'percentual_comissao' => $request->percentual_comissao ? __replace($request->percentual_comissao) : 0]);
        $request->merge([ 'usuario_id' => $request->usuario_id != 'NULL' ? $request->usuario_id : null]);

        $request->merge([ 'salario' => $request->salario ? __replace($request->salario) : 0 ]);

        // Merge dos novos campos
        $request->merge([ 
            'funcao_id' => $request->funcao_id ? $request->funcao_id : null,
            'filial_id' => $request->filial_id != '' ? $request->filial_id : null
        ]);

        $request->merge([
            'cnh' => $request->cnh ?? null,
            'categoria_cnh' => $request->categoria_cnh ?? null,
            'status_motorista' => $request->status_motorista ?? 'Ativo',
            'status_funcionario' => $request->status_funcionario ?? 'Ativo',
            'matricula' => $request->matricula ? trim($request->matricula) : null,
            'pis' => $request->pis ? preg_replace('/\D/', '', $request->pis) : null,
            'codigo_relogio' => $request->codigo_relogio ? trim($request->codigo_relogio) : null,
        ]);

        $request->merge([
            'vencimento_cnh' => $request->vencimento_cnh
                ? \Carbon\Carbon::createFromFormat('d/m/Y', $request->vencimento_cnh)->format('Y-m-d')
                : null
        ]);

        $request->merge([
            'data_nascimento' => $request->data_nascimento
                ? \Carbon\Carbon::createFromFormat('d/m/Y', $request->data_nascimento)->format('Y-m-d')
                : null
        ]);

        $request->merge([
            'data_admissao' => $request->data_admissao
                ? \Carbon\Carbon::createFromFormat('d/m/Y', $request->data_admissao)->format('Y-m-d')
                : null
        ]);

        // Salvar a imagem usando o SaveFilesDB
        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $filename = $this->saveFilesDB->SaveFile($request->file('file'), $this->directoryPath, $funcionario, 'foto_funcionario');
            $request->merge(['foto_funcionario' => $filename]);
        }

        $result = $funcionario->create($request->all());

        if($result){
            session()->flash("mensagem_sucesso", "Funcionario cadastrado com sucesso!");
        }else{
            session()->flash('mensagem_erro', 'Erro ao cadastrar funcionario!');
        }

        return redirect('/funcionarios');
    }

    public function edit($id){
        $funcionario = new Funcionario(); //Model

        $usuarios = Usuario::where('empresa_id', $this->empresa_id)->get();
        $funcionarios = Funcionario::where('empresa_id', $this->empresa_id)->get();
        
        $funcoes = \App\Models\Funcao::where('empresa_id', $this->empresa_id)->get();
        $filiais = \App\Models\Filial::where('empresa_id', $this->empresa_id)->get();

        $resp = $funcionario
        ->where('id', $id)->first();

        $temp = [];
        foreach($usuarios as $u){
            if(!isset($u->funcionario)){
                array_push($temp, $u);
            }
        }

        if(valida_objeto($resp)){
            return view('funcionarios/register')
            ->with('pessoaFisicaOuJuridica', true)
            ->with('funcionario', $resp)
            ->with('usuarios', $usuarios)
            ->with('funcoes', $funcoes)
            ->with('filiais', $filiais)
            ->with('empresa_id', $this->empresa_id)
            ->with('title', 'Editar Funcionario');
        }else{
            return redirect('/403');
        }
    }

    public function update(Request $request){
        $funcionario = new Funcionario();

        $id = $request->input('id');
        $resp = $funcionario->where('id', $id)->first();
        $request->merge([ 'data_registro' => '01/01/2000']);
        $request->merge([ 'salario' => $request->salario ?? 0 ]);

        $this->_validate($request);

        // Verificar se a imagem deve ser removida
        if ($request->input('remove_image') == '1') {
            $imagePath = $this->directoryPath . $resp->foto_funcionario;

            // Exclui o arquivo físico se ele existir
            if ($resp->foto_funcionario && file_exists($imagePath)) {
                unlink($imagePath);
                \Log::info("Imagem {$imagePath} removida com sucesso.");
            } else {
                \Log::warning("Imagem {$imagePath} não encontrada para remoção.");
            }

            // Define o campo como nulo no banco para remoção da referência
            $resp->foto_funcionario = null;
        }

        // Atualizar a imagem usando o SaveFilesDB
        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $filename = $this->saveFilesDB->SaveFile($request->file('file'), $this->directoryPath, $resp, 'foto_funcionario');
            $request->merge(['foto_funcionario' => $filename]);
        }

        $usuario = Usuario::find($request->usuario_id);
        if(isset($usuario->funcionario) && $resp->usuario_id != $request->usuario_id){
            session()->flash('mensagem_erro', 'Usuário ja esta em outro funcionário');
            return redirect()->back();
        }

        $resp->nome = $request->input('nome');
        $resp->cpf = $request->input('cpf');

        $resp->rua = $request->input('rua');
        $resp->numero = $request->input('numero');
        $resp->bairro = $request->input('bairro');

        $resp->telefone = $request->input('telefone');
        $resp->celular = $request->input('celular');
        $resp->email = $request->input('email');
        $resp->salario = __replace($request->salario);

        $resp->percentual_comissao = $request->input('percentual_comissao') ?
        __replace($request->percentual_comissao) : 0;

        $resp->usuario_id = ($request->usuario_id && $request->usuario_id != 'NULL') ? $request->usuario_id : null;

        // Inserção dos novos campos
        $resp->funcao_id = $request->input('funcao_id') ? $request->input('funcao_id') : null;
        $resp->filial_id = $request->input('filial_id') != '' ? $request->input('filial_id') : null;

        $resp->cnh = $request->input('cnh');
        $resp->categoria_cnh = $request->input('categoria_cnh');

        $resp->vencimento_cnh = $request->vencimento_cnh
            ? \Carbon\Carbon::createFromFormat('d/m/Y', $request->vencimento_cnh)->format('Y-m-d')
            : null;

        $resp->data_nascimento = $request->data_nascimento
            ? \Carbon\Carbon::createFromFormat('d/m/Y', $request->data_nascimento)->format('Y-m-d')
            : null;

        $resp->data_admissao = $request->data_admissao
            ? \Carbon\Carbon::createFromFormat('d/m/Y', $request->data_admissao)->format('Y-m-d')
            : null;

        $resp->status_motorista = $request->input('status_motorista');
        $resp->tipo_sanguineo = $request->input('tipo_sanguineo');
        $resp->status_funcionario = $request->input('status_funcionario');
        $resp->numero_registro = $request->input('numero_registro');
        $resp->matricula = $request->input('matricula');
        $resp->pis = $request->pis ? preg_replace('/\D/', '', $request->pis) : null;
        $resp->codigo_relogio = $request->input('codigo_relogio');
        $resp->observacao_ponto = $request->input('observacao_ponto');
        $resp->observacoes = $request->input('observacoes');

        $funcionario->fill($request->all());

        $result = $resp->save();
        if($result){
            session()->flash('mensagem_sucesso', 'Funcionario editado com sucesso!');
        }else{
            session()->flash('mensagem_erro', 'Erro ao editar funcionario!');
        }

        return redirect('/funcionarios');
    }

    public function quickSave(Request $request) {
        try {
            $novaFuncao = \App\Models\Funcao::create([
                'nome' => $request->nome,
                'empresa_id' => $request->empresa_id
            ]);
            return response()->json(['success' => true, 'id' => $novaFuncao->id, 'nome' => $novaFuncao->nome]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao salvar função']);
        }
    }

    public function delete($id){

        $resp = Funcionario::where('id', $id)->first();

        if(valida_objeto($resp)){
            if ($resp->foto_funcionario && file_exists($this->directoryPath . $resp->foto_funcionario)) {
                unlink($this->directoryPath . $resp->foto_funcionario);
            }
            if($resp->delete()){
                session()->flash('mensagem_sucesso', 'Registro removido!');
            }else{
                session()->flash('mensagem_erro', 'Erro!');
            }
            return redirect('/funcionarios');
        }else{
            return redirect('/403');
        }

    }

    private function _validate(Request $request){
        $rules = [
            'nome' => 'required|max:50',
            'cpf' => 'required',
            'rua' => 'required|max:80',
            'numero' => 'required|max:10',
            'bairro' => 'required|max:50',
            'telefone' => 'required|max:20',
            'celular' => 'required|max:20',
            'email' => 'max:40',
            'rg' => 'required',
            'data_registro' => 'required',
            'cnh' => 'nullable|max:255',
            'categoria_cnh' => 'nullable|in:A,B,C,D,E',
            'vencimento_cnh' => 'nullable|date',
            'status_motorista' => 'required|in:Ativo,Inativo',
            'vencimento_cnh' => 'nullable|date_format:d/m/Y',
            'data_nascimento' => 'nullable|date_format:d/m/Y',
            'data_admissao' => 'nullable|date_format:d/m/Y',
            'tipo_sanguineo' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'status_funcionario' => 'required|in:Ativo,Desligado',
            'matricula' => 'nullable|max:60',
            'pis' => 'nullable|max:20',
            'codigo_relogio' => 'nullable|max:30',
            'observacao_ponto' => 'nullable|max:500',
            'foto_funcionario' => 'nullable|image|max:2048',
        ];

        $messages = [
            'nome.required' => 'O campo Nome é obrigatório.',
            'data_registro.required' => 'O campo data de registro é obrigatório.',
            'nome.max' => '50 caracteres maximos permitidos.',
            'cpf.required' => 'O campo CPF é obrigatório.',
            'rua.required' => 'O campo Rua é obrigatório.',
            'rg.required' => 'O campo IE/RG é obrigatório.',
            'rua.max' => '80 caracteres maximos permitidos.',
            'numero.required' => 'O campo Numero é obrigatório.',
            'numero.max' => '10 caracteres maximos permitidos.',
            'bairro.required' => 'O campo Bairro é obrigatório.',
            'bairro.max' => '50 caracteres maximos permitidos.',
            'telefone.required' => 'O campo Celular é obrigatório.',
            'telefone.max' => '20 caracteres maximos permitidos.',
            'celular.required' => 'O campo Celular é obrigatório.',
            'celular.max' => '20 caracteres maximos permitidos.',

            'email.required' => 'O campo Email é obrigatório.',
            'email.max' => '40 caracteres maximos permitidos.',
            'email.email' => 'Email inválido.',

            'cnh.max' => '255 caracteres máximos permitidos para CNH.',
            'categoria_cnh.in' => 'Categoria CNH deve ser A, B, C, D ou E.',
            'vencimento_cnh.date' => 'Vencimento CNH deve ser uma data válida.',
            'status_motorista.in' => 'Status do motorista deve ser Ativo ou Inativo.',
            'vencimento_cnh.date_format' => 'Vencimento CNH deve ser uma data válida no formato DD/MM/AAAA.',

        ];
        $this->validate($request, $rules, $messages);
    }

    private function _validateContato(Request $request){
        $rules = [
            'nome' => 'required|max:40',
            'telefone' => 'required|max:20',
        ];

        $messages = [
            'nome.required' => 'O campo nome é obrigatório.',
            'nome.max' => '40 caracteres maximos permitidos.',

            'telefone.required' => 'O campo Celular é obrigatório.',
            'telefone.max' => '20 caracteres maximos permitidos.',

        ];
        $this->validate($request, $rules, $messages);
    }

    public function contatos($id, $edit = false){
        $funcionario = Funcionario::
        where('id', $id)
        ->first();
        if(valida_objeto($funcionario)){
            return view('funcionarios/contatos')
            ->with('funcionario', $funcionario)
            ->with('edit', $edit)
            ->with('title', 'Contato Funcionario');
        }else{
            return redirect('/403');
        }
    }

    public function editContato($id){
        $contato = ContatoFuncionario::
        where('id', $id)
        ->first();
        if($contato != null && valida_objeto($contato->funcionario)){

            $funcionario = $contato->funcionario;

            return view('funcionarios/contatos')
            ->with('funcionario', $funcionario)
            ->with('contato', $contato)
            ->with('title', 'Contato Funcionario');
        }else{
            return redirect('/403');
        }
    }

    public function deleteContato($id){
        $funcionario = ContatoFuncionario::
        where('id', $id)
        ->first();

        if($funcionario != null && valida_objeto($funcionario->funcionario)){

            $delete = $funcionario->delete();

            if($delete){
                session()->flash('mensagem_sucesso', 'Registro removido!');
            }else{
                session()->flash('mensagem_erro', 'Erro!');
            }

            return redirect("/funcionarios/contatos/$funcionario->id");
        }else{
            return redirect('/403');
        }
    }

    public function saveContato(Request $request){
        $this->_validateContato($request);

        $result = null;
        if($request->id > 0){
            $contato = ContatoFuncionario::
            where('id', $request->id)
            ->first();

            $contato->nome = $request->nome;
            $contato->telefone = $request->telefone;

            $result = $contato->save();
        }else{
            $result = ContatoFuncionario::create($request->all());
        }
        if($result){
            session()->flash("mensagem_sucesso", "Contato cadastrado/editado com sucesso!");
        }else{
            session()->flash('mensagem_erro', 'Erro ao cadastrar contato!');
        }

        return redirect("/funcionarios/contatos/$request->funcionario_id");
    }


    public function comissao(){
        $funcionarios = Funcionario::
        where('empresa_id', $this->empresa_id)
        ->get();
        $comissoes = ComissaoVenda::
        where('empresa_id', $this->empresa_id)
        ->limit(200)
        ->orderBy('id', 'desc')
        ->get();

        return view('funcionarios/comissao')
        ->with('funcionarios', $funcionarios)
        ->with('comissoes', $comissoes)
        ->with('comissaoJs', true)
        ->with('title', 'Lista de comissões');
    }

    public function pagarComissao(Request $request){
        try{
            $vArr = $arr = $request->arr;
            $arr = explode(",", $arr);

            foreach($arr as $a){

                $pedido = ComissaoVenda::find($a);
                $pedido->status = 1;

                $pedido->save();
            }
            session()->flash('mensagem_sucesso', 'Comissão(s) paga(s) com sucesso!');
        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Erro ao pagar comissão(s)!');

        }
        return redirect()->back();
    }

    public function comissaoFiltro(Request $request){
        $funcionarioId = $request->funcionario_id;
        $status = $request->status;
        $dataInicial = $request->data_inicial;
        $dataFinal = $request->data_final;

        $comissoes = ComissaoVenda::
        where('empresa_id', $this->empresa_id)
        ->orderBy('id', 'desc');

        if($status != '--'){
            $comissoes->where('status', $status);
        }

        if($dataFinal && $dataInicial){
            $data_inicial = $this->parseDate($request->data_inicial);
            $data_final = $this->parseDate($request->data_final, true);
            $comissoes->whereBetween('created_at', [$data_inicial,
                $data_final]);
        }

        if($funcionarioId != '--'){
            $comissoes->where('funcionario_id', $funcionarioId);
        }
        $comissoes = $comissoes->get();

        $funcionarios = Funcionario::
        where('empresa_id', $this->empresa_id)
        ->get();

        return view('funcionarios/comissao')
        ->with('funcionarios', $funcionarios)
        ->with('comissoes', $comissoes)
        ->with('comissaoJs', true)
        ->with('title', 'Lista de comissões');
    }

    private function calcularComissaoVenda($venda, $percentual_comissao){
        $valorRetorno = 0;
        foreach($venda->itens as $i){
            if($i->produto->perc_comissao > 0){
                $valorRetorno += (($i->valor*$i->quantidade) * $i->produto->perc_comissao) / 100;
            }

            if($i->produto->valor_comissao > 0){
                $valorRetorno += $i->quantidade*$i->produto->valor_comissao;
            }
        }

        if($valorRetorno == 0){
            $valorRetorno = ($venda->valor_total * $percentual_comissao) / 100;
        }
        return $valorRetorno;
    }

    public function calcComissao(){
        $vendas = VendaCaixa::where('empresa_id', $this->empresa_id)
        ->whereDate('created_at', '>=', '2024-02-01')
        ->whereDate('created_at', '<=', '2024-02-29')
        ->get();

        $cont = 1;
        echo "total de vendas do PDV: " . sizeof($vendas) . "<br>";

        foreach($vendas as $v){
            $c = ComissaoVenda::where('venda_id', $v->id)
            ->where('empresa_id', $this->empresa_id)
            ->where('tabela', 'venda_caixas')
            ->first();
            if($c == null){
                if($v->usuario){
                    if($v->usuario->funcionario){
                        $percentual_comissao = $v->usuario->funcionario->percentual_comissao;
                        $valorComissao = $this->calcularComissaoVenda($v, $percentual_comissao);

                        $dataComissao = [
                            'funcionario_id' => $v->usuario->funcionario->id,
                            'venda_id' => $v->id,
                            'tabela' => 'venda_caixas',
                            'valor' => $valorComissao,
                            'status' => 0,
                            'empresa_id' => $this->empresa_id
                        ];
                        // ComissaoVenda::create($dataComissao);
                        echo __date($v->created_at) . " ID:$v->id - valor comissão R$".moeda($valorComissao)."<br>";
                    }
                }
            }else{
                // $c->created_at = $v->created_at;
                // $c->save();
                // echo $c->created_at . "<br>";

                // echo "ID: #$v->id R$ ". moeda($v->valor_total).", valor da comissão: R$ ".moeda($c->valor).", data: ".\Carbon\Carbon::parse($v->created_at)->format('d/m/Y H:i')." - $cont<br>";
                $cont++;
            }
        }
    }

}