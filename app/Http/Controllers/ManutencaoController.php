<?php

namespace App\Http\Controllers;

use App\Models\Fornecedor;
use App\Models\Funcionario;
use App\Models\Manutencao;
use App\Models\ManutencaoItem;
use App\Models\Veiculo;
use App\Models\Produto;
use App\Models\Estoque;
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
        $query = parent::getTenantRecords()->with(['veiculo', 'responsavel', 'fornecedor']);

        if ($request->filled('veiculo')) {
            $placa = $request->veiculo;
            $query->whereHas('veiculo', function($q) use ($placa) {
                $q->where('placa', 'LIKE', "%{$placa}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $query->orderBy('manutencao_id', 'desc');
        $records = $query->paginate(20);
        $deleteUrl = "{$this->redirectPage}/delete";
        $newItemUrl = "{$this->redirectPage}/new";
        $title = $this->formTitle; // 👈 Adicionado aqui para o layout não estourar Undefined variable

        return view($this->listView, compact('records', 'deleteUrl', 'newItemUrl', 'title'));
    }

    public function register($id = null) {
        $data = null;
        if ($id) {
            $data = Manutencao::with(['itens.produto', 'fornecedor'])->where('manutencao_id', $id)->firstOrFail();
        }
        $title = $id ? "Editar Manutenção: #$id" : "Nova Manutenção";

        return view($this->registerView, [
            'data' => $data,
            'title' => $title,
            'veiculos' => Veiculo::where('empresa_id', $this->empresa_id)->where('ativo', 'Sim')->orderBy('placa')->get(),
            'funcionarios' => Funcionario::where('empresa_id', $this->empresa_id)->orderBy('nome')->get(),
            'fornecedores' => Fornecedor::where('empresa_id', $this->empresa_id)->orderBy('razao_social')->get()
        ]);
    }

    public function save(Request $request) {
        $user_logged = session('user_logged');
        $usuario_id = $user_logged['id'] ?? $user_logged['usuario_id'];
        $empresa_id = $this->empresa_id;
        $filial_id_final = $request->filial_id ?? ($user_logged['filial_id'] ?? 1);

        $manutencaoId = $request->manutencao_id ?: $request->id;

        // Trava: impede duplicidade de OS aberta para o mesmo veículo
        if (!$manutencaoId) {
            $aberta = Manutencao::where('empresa_id', $empresa_id)
                ->where('veiculo_id', $request->veiculo_id)
                ->whereIn('status', ['Aguardando', 'Aguardando Aprovação', 'Em Manutenção', 'Em andamento', 'Iniciado'])
                ->first();

            if ($aberta) {
                return redirect()->back()
                    ->with('mensagem_erro', "O veículo selecionado já possui a manutenção aberta #{$aberta->manutencao_id}!")
                    ->withInput();
            }
        }

        if ($request->filled('checklist') && !is_array($request->checklist)) {
            $linhas = preg_split('/\r\n|\r|\n/', $request->checklist);
            $request->merge(['checklist' => array_values(array_filter(array_map('trim', $linhas)))]);
        }

        try {
            return DB::transaction(function () use ($request, $usuario_id, $empresa_id, $filial_id_final, $manutencaoId) {
                $dados = $request->except(['produtos_ids', 'produtos_nomes', 'tipos_itens', 'quantidades', 'valores', 'subtotais']);
                $dados['empresa_id'] = $empresa_id;

                if ($manutencaoId > 0) {
                    $registro = Manutencao::where('manutencao_id', $manutencaoId)
                        ->where('empresa_id', $empresa_id)
                        ->firstOrFail();
                    $registro->update($dados);
                    $id_manutencao = $manutencaoId;
                } else {
                    $ultimo = DB::table('manutencoes')->where('empresa_id', $empresa_id)->max('manutencao_id') ?? 0;
                    $dados['manutencao_id'] = $ultimo + 1;
                    $registro = Manutencao::create($dados);
                    $id_manutencao = $registro->manutencao_id;
                }

                // Gravação dos Itens (Peças + Serviços)
                DB::table('manutencao_itens')->where('manutencao_id', $id_manutencao)->delete();

                $totalCusto = 0;
                if ($request->has('produtos_nomes') && is_array($request->produtos_nomes)) {
                    foreach ($request->produtos_nomes as $key => $descricao) {
                        $p_id = $request->produtos_ids[$key] ?? null;
                        $tipoItem = $request->tipos_itens[$key] ?? 'peca';
                        $qtd = (float) str_replace(',', '.', $request->quantidades[$key] ?? 1);
                        $vlr = (float) str_replace(',', '.', $request->valores[$key] ?? 0);
                        $sub = $qtd * $vlr;
                        $totalCusto += $sub;

                        DB::table('manutencao_itens')->insert([
                            'empresa_id'     => $empresa_id,
                            'usuario_id'     => $usuario_id,
                            'filial_id'      => $filial_id_final,
                            'manutencao_id'  => $id_manutencao,
                            'produto_id'     => ($tipoItem === 'servico' || empty($p_id)) ? null : $p_id,
                            'descricao'      => $descricao,
                            'quantidade'     => $qtd,
                            'valor_unitario' => $vlr,
                            'subtotal'       => $sub,
                            'created_at'     => now()
                        ]);
                    }
                }

                DB::table('manutencoes')
                    ->where('manutencao_id', $id_manutencao)
                    ->where('empresa_id', $empresa_id)
                    ->update(['custo' => $totalCusto]);

                return redirect($this->redirectPage)->with('mensagem_sucesso', 'Manutenção salva com sucesso!');
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('mensagem_erro', 'Erro ao salvar manutenção: ' . $e->getMessage())->withInput();
        }
    }

    public function buscarProdutos(Request $request) {
        $pesquisa = $request->q ?: $request->term ?: '';

        $produtos = Produto::where('empresa_id', $this->empresa_id)
            ->when(!empty($pesquisa), function($query) use ($pesquisa) {
                return $query->where('nome', 'LIKE', "%{$pesquisa}%");
            })
            ->select(['id', 'nome', 'valor_compra'])
            ->orderBy('nome', 'asc')
            ->limit(30)
            ->get();

        return response()->json([
            'results' => $produtos->map(function($p) {
                return [
                    'id'    => $p->id,
                    'text'  => $p->nome,
                    'valor' => number_format((float)$p->valor_compra, 2, '.', '')
                ];
            })
        ]);
    }

    public function finalizarOS($id) {
        try {
            DB::beginTransaction();

            $manutencao = Manutencao::with('itens')->where('manutencao_id', $id)->firstOrFail();

            if (in_array($manutencao->status, ['Finalizado', 'Concluída'])) {
                return redirect()->back()->with('mensagem_erro', 'Esta manutenção já se encontra finalizada.');
            }

            // Baixa estoque apenas para itens que são produtos físicos (produto_id preenchido)
            foreach ($manutencao->itens as $item) {
                if (!empty($item->produto_id)) {
                    $estoqueRegistro = Estoque::where('produto_id', $item->produto_id)->first();
                    if ($estoqueRegistro) {
                        $estoqueRegistro->decrement('quantidade', (float)$item->quantidade);
                    }
                }
            }

            $manutencao->update([
                'status' => 'Finalizado',
                'data_finalizacao' => now()
            ]);

            DB::commit();
            return redirect($this->redirectPage)->with('mensagem_sucesso', 'Manutenção finalizada e estoque baixado!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect($this->redirectPage)->with('mensagem_erro', 'Erro ao finalizar OS: ' . $e->getMessage());
        }
    }

    public function imprimir($id) {
        $manutencao = Manutencao::with(['veiculo', 'itens.produto', 'responsavel', 'fornecedor'])
            ->where('manutencao_id', $id)
            ->firstOrFail();

        $config = DB::table('config_notas')
            ->where('empresa_id', $this->empresa_id)
            ->select([
                'razao_social', 'nome_fantasia', 'cnpj',
                'logradouro', 'complemento', 'numero',
                'bairro', 'municipio', 'uf', 'logo'
            ])
            ->first();

        return view('manutencoes.imprimir', compact('manutencao', 'config'));
    }

    public function destroy($id) {
        try {
            DB::table('manutencao_itens')->where('manutencao_id', $id)->delete();
            Manutencao::where('manutencao_id', $id)->delete();
            return redirect($this->redirectPage)->with('mensagem_sucesso', 'Excluído com sucesso!');
        } catch (\Exception $e) {
            return redirect($this->redirectPage)->with('mensagem_erro', 'Erro ao excluir.');
        }
    }
}
