<?php

namespace App\Http\Controllers;

use App\Models\Fornecedor;
use App\Models\Funcionario;
use App\Models\Manutencao;
use App\Models\ManutencaoItem;
use App\Models\Veiculo;
use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManutencaoController extends BaseController
{
    protected $model = Manutencao::class;
    protected $resource = 'manutencoes';
    protected $table = 'manutencoes';
    protected $formTitle = 'Manutenções de Veículos';
    protected $listView = 'manutencoes.list';
    protected $registerView = 'manutencoes.register';
    protected $redirectPage = '/manutencoes';

    public function rules(): array {
        return ['veiculo_id' => 'required', 'data_manutencao' => 'required'];
    }

    public function messages(): array {
        return ['veiculo_id.required' => 'O campo veículo é obrigatório.', 'data_manutencao.required' => 'A data é obrigatória.'];
    }

    public function index(Request $request) {
        $query = parent::getTenantRecords()->with(['veiculo', 'responsavel']);
        $query->orderBy('manutencao_id', 'desc'); 
        $records = $query->get();
        $deleteUrl = "{$this->redirectPage}/delete";
        $newItemUrl = "{$this->redirectPage}/new";
        return view($this->listView, compact('records', 'deleteUrl', 'newItemUrl'));
    }

    public function register($id = null) {
        $data = null;
        if ($id) { $data = Manutencao::with('itens')->findOrFail($id); }
        $title = $id ? "Editar Manutenção: $id" : "Nova Manutenção";

        return view($this->registerView, [
            'data' => $data,
            'title' => $title,
            'veiculos' => Veiculo::where('empresa_id', $this->empresa_id)->where('ativo', 'Sim')->orderBy('placa')->get(),
            'funcionarios' => Funcionario::where('empresa_id', $this->empresa_id)->orderBy('nome')->get(),
            'fornecedores' => Fornecedor::where('empresa_id', $this->empresa_id)->orderBy('razao_social')->get()
        ]);
    }

public function save(Request $request) {
    // 1. Definição de contexto
    $user_logged = session('user_logged');
    $usuario_id = $user_logged['id'] ?? $user_logged['usuario_id'];
    $empresa_id = $this->empresa_id;
    $filial_id_final = $request->filial_id ?? ($user_logged['filial_id'] ?? 1);

    // 2. BLOQUEIO: Não permitir 2 manutenções "Pendente" ou "Em Andamento" para o mesmo veículo
    // Só fazemos essa checagem se for um NOVO registro (id == 0 ou null)
    if (!$request->id || $request->id == 0) {
        $aberta = Manutencao::where('empresa_id', $empresa_id)
            ->where('veiculo_id', $request->veiculo_id)
            ->whereIn('status', ['Pendente', 'Em Andamento', 'Iniciado']) // Ajuste conforme seus status
            ->first();

        if ($aberta) {
            return redirect()->back()
                ->with('mensagem_erro', "O veículo {$aberta->veiculo->placa} já possui uma manutenção aberta (ID: {$aberta->manutencao_id})!")
                ->withInput();
        }
    }

    // 3. Tratamento de dados
    if ($request->filled('checklist')) {
        $linhas = preg_split('/\r\n|\r|\n/', $request->checklist);
        $request->merge(['checklist' => array_values(array_filter(array_map('trim', $linhas)))]);
    }
    if ($request->prioridade == 'Média') $request->merge(['prioridade' => 'Media']);

    try {
        return DB::transaction(function () use ($request, $usuario_id, $empresa_id, $filial_id_final) {
            
            // 4. Lógica de Salvar/Editar (Substituindo o parent::save para evitar duplicidade)
            if ($request->id > 0) {
                $registro = Manutencao::where('manutencao_id', $request->id)
                    ->where('empresa_id', $empresa_id)
                    ->firstOrFail();
                $registro->update($request->all());
                $id_manutencao = $request->id;
            } else {
                $dados = $request->all();
                $dados['empresa_id'] = $empresa_id;
                // Busca o próximo ID manual se o seu sistema não for Auto Increment
                $ultimo = DB::table('manutencoes')->where('empresa_id', $empresa_id)->max('manutencao_id');
                $dados['manutencao_id'] = $ultimo + 1;
                
                $registro = Manutencao::create($dados);
                $id_manutencao = $registro->manutencao_id;
            }

            // 5. Gravação dos Itens
            if ($id_manutencao && $request->filled('produtos_ids')) {
                DB::table('manutencao_itens')->where('manutencao_id', $id_manutencao)->delete();
                
                foreach ($request->produtos_ids as $key => $p_id) {
                    DB::table('manutencao_itens')->insert([
                        'empresa_id'     => $empresa_id,
                        'usuario_id'     => $usuario_id,
                        'filial_id'      => $filial_id_final,
                        'manutencao_id'  => $id_manutencao,
                        'produto_id'     => $p_id,
                        'descricao'      => $request->produtos_nomes[$key] ?? '',
                        'quantidade'     => (float)str_replace(',', '.', $request->quantidades[$key]),
                        'valor_unitario' => (float)str_replace(',', '.', $request->valores[$key]),
                        'subtotal'       => (float)($request->quantidades[$key] * $request->valores[$key]),
                        'created_at'     => now()
                    ]);
                }
                
                // Atualiza custo total na tabela pai
                $total = DB::table('manutencao_itens')->where('manutencao_id', $id_manutencao)->sum('subtotal');
                DB::table('manutencoes')->where('manutencao_id', $id_manutencao)->update(['custo' => $total]);
            }

            return redirect($this->redirectPage)->with('mensagem_sucesso', 'Manutenção salva com sucesso!');
        });
    } catch (\Exception $e) {
        return redirect()->back()->with('mensagem_erro', 'Erro ao salvar: ' . $e->getMessage())->withInput();
    }
}

    public function buscarProdutos(Request $request) {
        $pesquisa = $request->term;
        $produtos = Produto::where('empresa_id', $this->empresa_id)
            ->where('nome', 'LIKE', "%$pesquisa%")
            ->select(['id', 'nome', 'valor_compra'])
            ->limit(20)->get();

        return response()->json(['results' => $produtos->map(function($p) {
            return ['id' => $p->id, 'text' => $p->nome, 'valor' => number_format($p->valor_compra, 2, '.', '')];
        })]);
    }

  public function finalizarOS($id)
{
    try {
        DB::beginTransaction();

        // 1. Busca a manutenção e seus itens
        $manutencao = Manutencao::with('itens')->findOrFail($id);

        // Evita finalizar algo que já está finalizado
        if ($manutencao->status == 'Finalizado') {
            return redirect()->back()->with('error', 'Esta manutenção já foi finalizada!');
        }

        // 2. Baixa o estoque de cada produto utilizado
        foreach ($manutencao->itens as $item) {
    // Busca o registro na tabela de estoque vinculado ao produto
    $estoqueRegistro = \App\Models\Estoque::where('produto_id', $item->produto_id)->first();

    if ($estoqueRegistro) {
        // Supondo que a coluna com o número seja 'quantidade' ou 'atual'
        // Verifique o nome real da coluna de saldo na sua tabela de estoques
        $estoqueRegistro->decrement('quantidade', (float)$item->quantidade);
    }
}

        // 3. Atualiza o status da manutenção
        $manutencao->update([
            'status' => 'Finalizado',
            'data_finalizacao' => now() // Caso tenha esse campo
        ]);

        DB::commit();
        return redirect($this->redirectPage)->with('success', 'Manutenção finalizada e estoque atualizado!');

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect($this->redirectPage)->with('error', 'Erro ao finalizar: ' . $e->getMessage());
    }
}
  
  
  
    public function imprimir($id)
{
    $manutencao = Manutencao::with(['veiculo', 'itens.produto', 'responsavel', 'fornecedor'])
        ->findOrFail($id);

    // Busca os dados da empresa com os nomes de colunas corretos
    $config = DB::table('config_notas')
        ->where('empresa_id', $this->empresa_id)
        ->select([
            'razao_social', 'nome_fantasia', 'cnpj', 
            'logradouro', 'complemento', 'numero', 
            'bairro', 'municipio', 'uf','logo'
        ])
        ->first();

    return view('manutencoes.imprimir', compact('manutencao', 'config'));
}

    public function destroy($id) {
        try {
            DB::table('manutencao_itens')->where('manutencao_id', $id)->delete();
            Manutencao::where('manutencao_id', $id)->delete();
            return redirect($this->redirectPage)->with('success', 'Excluído com sucesso!');
        } catch (\Exception $e) {
            return redirect($this->redirectPage)->with('error', 'Erro ao excluir.');
        }
    }
}