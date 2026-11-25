<?php

namespace App\Http\Controllers;

use App\Models\MtrConfig;
use App\Models\MtrDeparaResiduo;
use App\Models\MtrManifesto;
use App\Models\MtrManifestoItem;
use App\Services\SinirIemaService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MtrController extends BaseController
{
    protected $model = MtrManifesto::class;
    protected $redirectPage = '/mtr/emissao';
    protected $formTitle = 'Manifesto de Transporte de Resíduos';
    protected ?string $ultimoErroMtr = null;

    protected function rules(): array
    {
        return [];
    }

    protected function messages(): array
    {
        return [];
    }

    private function getDadosSessao(): array
    {
        return [
            'empresa_id' => $this->empresa_id,
            'usuario_id' => $this->usuario_id,
            'filial_id' => $this->filial_id,
        ];
    }

    /**
     * Função auxiliar para carregar as listas de Motoristas e Veículos
     */
    private function getListasAutocompletar(): array
    {
        try {
            $motoristas = DB::table('motoristas')
                ->where('empresa_id', $this->empresa_id)
                ->select('nome')
                ->orderBy('nome')
                ->get();
            if ($motoristas->isEmpty()) {
                throw new \Exception('Vazio, buscar nos funcionarios');
            }
        } catch (\Exception $e) {
            try {
                $motoristas = DB::table('funcionarios')
                    ->where('empresa_id', $this->empresa_id)
                    ->where(function ($q) {
                        $q->where('funcao', 'like', '%motorista%')
                            ->orWhere('cargo', 'like', '%motorista%');
                    })
                    ->select('nome')
                    ->orderBy('nome')
                    ->get();

                if ($motoristas->isEmpty()) {
                    $motoristas = DB::table('funcionarios')
                        ->where('empresa_id', $this->empresa_id)
                        ->select('nome')
                        ->orderBy('nome')
                        ->get();
                }
            } catch (\Exception $ex) {
                $motoristas = collect();
            }
        }

        try {
            $veiculos = DB::table('veiculos')
                ->where('empresa_id', $this->empresa_id)
                ->select('placa')
                ->orderBy('placa')
                ->get();
        } catch (\Exception $e) {
            $veiculos = collect();
        }

        return [
            'motoristasLista' => $motoristas,
            'veiculosLista' => $veiculos,
        ];
    }

    /**
     * Carrega apenas os produtos que possuem relação com o De-Para
     * (por produto/item, subcategoria/subgrupo, categoria ou NCM),
     * evitando exibir imobilizado, EPI, mantimentos e itens sem vínculo.
     */
    private function getProdutosComDepara(): array
    {
        $produtos = DB::table('produtos')
            ->where('empresa_id', $this->empresa_id)
            ->select('id', 'nome', 'sub_categoria_id', 'categoria_id', 'NCM')
            ->orderBy('nome')
            ->get();

        $deparas = MtrDeparaResiduo::where('empresa_id', $this->empresa_id)->get();

        $porProduto = $deparas->whereNotNull('produto_id')->keyBy('produto_id');
        $porSubcat  = $deparas->whereNotNull('sub_categoria_id')->keyBy('sub_categoria_id');
        $porCat     = $deparas->whereNotNull('categoria_id')->keyBy('categoria_id');
        $porNcm     = $deparas->whereNotNull('ncm')->keyBy('ncm');

        $resultado = [];
        foreach ($produtos as $p) {
            $regra = $porProduto->get($p->id)
                ?? (!empty($p->sub_categoria_id) ? $porSubcat->get($p->sub_categoria_id) : null)
                ?? (!empty($p->categoria_id) ? $porCat->get($p->categoria_id) : null)
                ?? (!empty($p->NCM) ? $porNcm->get($p->NCM) : null);

            if (!$regra) {
                continue;
            }

            $resultado[] = [
                'id'                  => $p->id,
                'nome'                => $p->nome,
                'tem_depara'          => true,
                'cod_ibama'           => $regra->cod_ibama ?? '',
                'descricao_residuo'   => $regra->descricao_residuo ?? $p->nome,
                'unidade_medida'      => $regra->unidade_medida ?? 2,
                'estado_fisico'       => $regra->estado_fisico ?? 1,
                'classe_residuo'      => $regra->classe_residuo ?? 43,
                'acondicionamento_id' => $regra->acondicionamento_id ?? 8,
                'tratamento_id'       => $regra->tratamento_id ?? 43,
                'numero_onu'          => $regra->numero_onu ?? '',
                'classe_risco'        => $regra->classe_risco ?? '',
                'nome_embarque'       => $regra->nome_embarque ?? '',
                'grupo_embalagem'     => $regra->grupo_embalagem ?? '',
            ];
        }

        return $resultado;
    }

    /**
     * Converte valores numéricos com vírgula, ponto decimal ou separador de milhar
     * (ex.: "1,5", "1.5", "1.234,56") para float de forma segura.
     */
    private function parseQuantidadeMtr($valor): float
    {
        $texto = trim((string) $valor);
        if ($texto === '') {
            return 0.0;
        }

        $texto = preg_replace('/[^\d,.-]/', '', $texto);

        if (str_contains($texto, ',') && str_contains($texto, '.')) {
            $texto = str_replace('.', '', $texto);
            $texto = str_replace(',', '.', $texto);
        } elseif (str_contains($texto, ',')) {
            $texto = str_replace(',', '.', $texto);
        }

        return (float) $texto;
    }

    public function index(Request $request)
    {
        $query = MtrManifesto::query()
            ->where('empresa_id', $this->empresa_id);

        if ($request->filled('numero_mtr')) {
            $query->where(function ($q) use ($request) {
                $q->where('numero_mtr', 'like', '%' . $request->numero_mtr . '%')
                    ->orWhere('seu_codigo', 'like', '%' . $request->numero_mtr . '%');
            });
        }

        foreach ([
                     'transportador_nome' => 'transportadora',
                     'motorista_nome' => 'motorista',
                 ] as $column => $input) {
            if ($request->filled($input)) {
                $query->where($column, 'like', '%' . $request->{$input} . '%');
            }
        }

        if ($request->filled('status')) {
            if ($request->status === 'recebido') {
                $query->where('status', 'transmitido')
                    ->where('mensagem_retorno', 'like', '%recebid%');
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('envolvido')) {
            $query->where(function ($q) use ($request) {
                $q->where('gerador_nome', 'like', '%' . $request->envolvido . '%')
                    ->orWhere('destinador_nome', 'like', '%' . $request->envolvido . '%');
            });
        }

        if ($request->filled('placa')) {
            $placa = preg_replace('/[^a-zA-Z0-9]/', '', (string) $request->placa);
            $query->where('veiculo_placa', 'like', '%' . $placa . '%');
        }

        if ($request->filled('data_inicio') && $request->filled('data_fim')) {
            $query->whereBetween('data_expedicao', [
                $request->data_inicio . ' 00:00:00',
                $request->data_fim . ' 23:59:59',
            ]);
        } elseif ($request->filled('data_inicio')) {
            $query->where('data_expedicao', '>=', $request->data_inicio . ' 00:00:00');
        } elseif ($request->filled('data_fim')) {
            $query->where('data_expedicao', '<=', $request->data_fim . ' 23:59:59');
        }

        $manifestos = $query->orderByDesc('data_expedicao')
            ->orderByDesc('id')
            ->paginate(15)
            ->appends($request->all());

        $manifestos->getCollection()->transform(function (MtrManifesto $manifesto) {
            if (
                $manifesto->status === 'transmitido'
                && stripos((string) $manifesto->mensagem_retorno, 'recebid') !== false
            ) {
                $manifesto->setAttribute('status', 'recebido');
            }

            if ($manifesto->tipo_origem === 'nfe' && !empty($manifesto->venda_id)) {
                $numeroNfe = DB::table('vendas')->where('id', $manifesto->venda_id)->value('NfNumero');
                $manifesto->numero_nfe_exibicao = $numeroNfe ?: $manifesto->venda_id;
            } elseif ($manifesto->tipo_origem === 'ticket_pesagem' && !empty($manifesto->origem_id)) {
                $pesagemDoc = DB::table('pesagens')->where('id', $manifesto->origem_id)->first();
                if ($pesagemDoc) {
                    $manifesto->numero_nfe_exibicao = $pesagemDoc->nf_numero ?? $pesagemDoc->nfe ?? $pesagemDoc->numero_nfe ?? null;
                }
            }

            return $manifesto;
        });

        // 1. IMPORTAÇÃO DE NF-E
        $vendasQuery = DB::table('vendas')
            ->leftJoin('clientes', 'clientes.id', '=', 'vendas.cliente_id')
            ->where('vendas.empresa_id', $this->empresa_id)
            ->select('vendas.*', 'clientes.razao_social as cliente_nome', 'vendas.NfNumero')
            ->orderByDesc('vendas.id')
            ->limit(20)
            ->get();

        $vendasIds = $vendasQuery->pluck('id')->toArray();
        $mtrsVinculadosVenda = [];
        if (!empty($vendasIds)) {
            $mtrsVinculadosVenda = DB::table('mtr_manifestos')
                ->where('empresa_id', $this->empresa_id)
                ->where('tipo_origem', 'nfe')
                ->whereIn('origem_id', $vendasIds)
                ->pluck('numero_mtr', 'origem_id')
                ->toArray();
        }

        $vendasImportacao = $vendasQuery->map(function ($v) use ($mtrsVinculadosVenda) {
            $v->tem_mtr = isset($mtrsVinculadosVenda[$v->id]);
            $v->numero_mtr = $v->tem_mtr ? $mtrsVinculadosVenda[$v->id] : null;
            return $v;
        });

        // 2. IMPORTAÇÃO DE PESAGEM
        $ticketsQuery = DB::table('pesagens')
            ->leftJoin('clientes', 'clientes.id', '=', 'pesagens.cliente_id')
            ->leftJoin('fornecedors', 'fornecedors.id', '=', 'pesagens.fornecedor_id')
            ->where('pesagens.empresa_id', $this->empresa_id)
            ->where(function ($q) {
                $q->whereNotNull('pesagens.peso_final')
                    ->orWhere('pesagens.peso_final', '>', 0)
                    ->orWhereIn('pesagens.status', ['FINALIZADA', 'concluído']);
            })
            ->select(
                'pesagens.*',
                DB::raw('COALESCE(clientes.razao_social, fornecedors.razao_social, "NÃO INFORMADO") as cliente_nome'),
                DB::raw('COALESCE(pesagens.peso_final, pesagens.peso_liquido_bruto, pesagens.peso) as peso_calculado')
            )
            ->orderByDesc('pesagens.id')
            ->limit(20)
            ->get();

        $pesagensIds = $ticketsQuery->pluck('id')->toArray();
        $mtrsVinculadosPesagem = [];
        if (!empty($pesagensIds)) {
            $mtrsVinculadosPesagem = DB::table('mtr_manifestos')
                ->where('empresa_id', $this->empresa_id)
                ->where('tipo_origem', 'ticket_pesagem')
                ->whereIn('origem_id', $pesagensIds)
                ->pluck('numero_mtr', 'origem_id')
                ->toArray();
        }

        $ticketsImportacao = $ticketsQuery->map(function ($t) use ($mtrsVinculadosPesagem) {
            $t->tem_mtr = isset($mtrsVinculadosPesagem[$t->id]);
            $t->numero_mtr = $t->tem_mtr ? $mtrsVinculadosPesagem[$t->id] : null;
            $t->nf_vinculada = $t->nf_numero ?? $t->nfe ?? $t->numero_nfe ?? null;
            return $t;
        });

        return view('mtr.emissao.index', [
            'manifestos' => $manifestos,
            'vendasImportacao' => $vendasImportacao,
            'ticketsImportacao' => $ticketsImportacao,
            'title' => 'Central de Emissão de MTR',
        ]);
    }

    public function createAvulso()
    {
        $listas = $this->getListasAutocompletar();

        return view('mtr.emissao.create', array_merge([
            'unidadesGeradoras' => MtrConfig::where('empresa_id', $this->empresa_id)
                ->where('perfil', 'Gerador')
                ->where('ativo', true)
                ->get(),
            'clientes' => DB::table('clientes')
                ->where('empresa_id', $this->empresa_id)
                ->select('id', 'razao_social', 'cpf_cnpj')
                ->orderBy('razao_social')
                ->get(),
            'produtosComDepara' => $this->getProdutosComDepara(),
            'title' => 'Emissão de MTR',
            'origem' => 'avulso',
        ], $listas));
    }

    public function createPesagem($pesagemId)
    {
        $pesagem = DB::table('pesagens')
            ->where('id', $pesagemId)
            ->where('empresa_id', $this->empresa_id)
            ->first();

        if (!$pesagem) {
            return redirect()->route('mtr.emissao.index')->with('erro', 'Pesagem não encontrada.');
        }

        $tickets = DB::table('tickets_pesagem')
            ->where('empresa_id', $this->empresa_id)
            ->where('pesagem_id', $pesagemId)
            ->get();

        $pesoBruto = (float) $tickets->where('tipo', 'entrada')->sum('peso');
        $pesoTara = (float) $tickets->where('tipo', 'saida')->sum('peso');
        $pesoBag = (float) $tickets->sum('peso_bag');
        $pesoLiquido = max(0, $pesoBruto - $pesoTara - $pesoBag);

        if ((float) ($pesagem->impureza_desconto ?? 0) > 0) {
            $pesoLiquido -= $pesoLiquido * ((float) $pesagem->impureza_desconto / 100);
        }

        $ticket = $tickets->first();
        $produto = null;
        $produtoNome = 'Diversos/Sucatas';

        if ($ticket && $ticket->produto_id) {
            $produto = DB::table('produtos')
                ->where('id', $ticket->produto_id)
                ->where('empresa_id', $this->empresa_id)
                ->first();
        }

        $depara = null;
        if ($produto) {
            $produtoNome = $produto->nome;
            $depara = MtrDeparaResiduo::where('empresa_id', $this->empresa_id)->where('produto_id', $produto->id)->first()
                ?? MtrDeparaResiduo::where('empresa_id', $this->empresa_id)->where('sub_categoria_id', $produto->sub_categoria_id)->whereNotNull('sub_categoria_id')->first()
                ?? MtrDeparaResiduo::where('empresa_id', $this->empresa_id)->where('categoria_id', $produto->categoria_id)->whereNotNull('categoria_id')->first()
                ?? MtrDeparaResiduo::where('empresa_id', $this->empresa_id)->where('ncm', $produto->NCM)->whereNotNull('ncm')->first();
        }

        $residuos = [[
            'codigo_ibama' => $depara->cod_ibama ?? '',
            'descricao_residuo' => $depara->descricao_residuo ?? $produtoNome,
            'quantidade' => (float) $pesoLiquido,
            'unidade' => $depara->unidade_medida ?? 2,
            'estado_fisico' => $depara->estado_fisico ?? 1,
            'classe_residuo' => $depara->classe_residuo ?? 43,
            'acondicionamento_id' => $depara->acondicionamento_id ?? 8,
            'tratamento_id' => $depara->tratamento_id ?? 43,
            'numero_onu' => $depara->numero_onu ?? null,
            'classe_risco' => $depara->classe_risco ?? null,
            'nome_embarque' => $depara->nome_embarque ?? null,
            'grupo_embalagem' => $depara->grupo_embalagem ?? null,
        ]];

        $cliente = null;
        if (!empty($pesagem->cliente_id)) {
            $cliente = DB::table('clientes')
                ->where('id', $pesagem->cliente_id)
                ->where('empresa_id', $this->empresa_id)
                ->first();
        } elseif (!empty($pesagem->fornecedor_id)) {
            $cliente = DB::table('fornecedors')
                ->where('id', $pesagem->fornecedor_id)
                ->where('empresa_id', $this->empresa_id)
                ->first();
        }

        $nfeNum = $pesagem->nf_numero ?? $pesagem->nfe ?? $pesagem->numero_nfe ?? '';
        $obs = "Produto: {$produtoNome} | Ticket Pesagem: #{$pesagemId}";
        if ($nfeNum !== '') {
            $obs .= " | NF-e: {$nfeNum}";
        }

        $placaTratada = preg_replace('/[^a-zA-Z0-9]/', '', (string) ($pesagem->placa_veiculo ?? $pesagem->placa ?? ''));

        $geradorDefault = MtrConfig::where('empresa_id', $this->empresa_id)
            ->where('perfil', 'Gerador')
            ->where('ativo', true)
            ->first();

        return redirect()->route('mtr.emissao.create.avulso')
            ->with('gerador_id', $geradorDefault->id ?? null)
            ->with('destinador_cnpj', $cliente->cpf_cnpj ?? null)
            ->with('placa_veiculo', strtoupper($placaTratada))
            ->with('motorista_nome', mb_strtoupper($pesagem->motorista ?? $pesagem->motorista_nome ?? ''))
            ->with('origem_tipo', 'ticket_pesagem')
            ->with('origem_id', $pesagemId)
            ->with('ticket_pesagem_id', $ticket->id ?? null)
            ->with('residuos_importados', $residuos)
            ->with('observacao_importada', $obs)
            ->with('sucesso', 'Ticket de Pesagem importado com sucesso!');
    }

    public function createNfe($vendaId)
    {
        $venda = DB::table('vendas')
            ->where('id', $vendaId)
            ->where('empresa_id', $this->empresa_id)
            ->first();

        if (!$venda) {
            return redirect()->route('mtr.emissao.index')->with('erro', 'Venda não encontrada.');
        }

        $itensVenda = DB::table('item_vendas')->where('venda_id', $vendaId)->get();
        $residuos = [];
        $nomes = [];

        foreach ($itensVenda as $item) {
            $produto = DB::table('produtos')
                ->where('id', $item->produto_id)
                ->where('empresa_id', $this->empresa_id)
                ->first();

            if (!$produto) continue;

            $nomes[] = $produto->nome;

            $depara = MtrDeparaResiduo::where('empresa_id', $this->empresa_id)->where('produto_id', $produto->id)->first()
                ?? MtrDeparaResiduo::where('empresa_id', $this->empresa_id)->where('sub_categoria_id', $produto->sub_categoria_id)->whereNotNull('sub_categoria_id')->first()
                ?? MtrDeparaResiduo::where('empresa_id', $this->empresa_id)->where('categoria_id', $produto->categoria_id)->whereNotNull('categoria_id')->first()
                ?? MtrDeparaResiduo::where('empresa_id', $this->empresa_id)->where('ncm', $produto->NCM)->whereNotNull('ncm')->first();

            $residuos[] = [
                'codigo_ibama' => $depara->cod_ibama ?? '',
                'descricao_residuo' => $depara->descricao_residuo ?? $produto->nome,
                'quantidade' => (float) $item->quantidade,
                'unidade' => $depara->unidade_medida ?? 2,
                'estado_fisico' => $depara->estado_fisico ?? 1,
                'classe_residuo' => $depara->classe_residuo ?? 43,
                'acondicionamento_id' => $depara->acondicionamento_id ?? 8,
                'tratamento_id' => $depara->tratamento_id ?? 43,
                'numero_onu' => $depara->numero_onu ?? null,
                'classe_risco' => $depara->classe_risco ?? null,
                'nome_embarque' => $depara->nome_embarque ?? null,
                'grupo_embalagem' => $depara->grupo_embalagem ?? null,
            ];
        }

        $cliente = DB::table('clientes')
            ->where('id', $venda->cliente_id)
            ->where('empresa_id', $this->empresa_id)
            ->first();

        $cnpjDestinador = '';
        if ($cliente) {
            $cnpjDestinador = preg_replace('/\D/', '', (string) $cliente->cpf_cnpj);
        }

        $placa = '';
        $motoristaNome = '';
        if (!empty($venda->frete_id)) {
            $frete = DB::table('fretes')->where('id', $venda->frete_id)->first();
            if ($frete) {
                $placa = preg_replace('/[^a-zA-Z0-9]/', '', (string) $frete->placa);
                $motoristaNome = $frete->motorista ?? '';
            }
        }

        $nfeNum = $venda->numero_nfe ?? $venda->nfe ?? $venda->id;
        $obs = 'NF-e: ' . $nfeNum . ' | Produtos: ' . implode(', ', array_unique($nomes));

        $geradorDefault = MtrConfig::where('empresa_id', $this->empresa_id)
            ->where('perfil', 'Gerador')
            ->where('ativo', true)
            ->first();

        return redirect()->route('mtr.emissao.create.avulso')
            ->with('gerador_id', $geradorDefault->id ?? null)
            ->with('destinador_cnpj', $cnpjDestinador)
            ->with('placa_veiculo', strtoupper($placa))
            ->with('motorista_nome', mb_strtoupper($motoristaNome))
            ->with('origem_tipo', 'nfe')
            ->with('origem_id', $vendaId)
            ->with('venda_id', $vendaId)
            ->with('residuos_importados', $residuos)
            ->with('observacao_importada', $obs)
            ->with('sucesso', 'Itens da Nota Fiscal importados com sucesso!');
    }

    public function salvarRascunho(Request $request)
    {
        DB::beginTransaction();

        try {
            $gerador = MtrConfig::where('empresa_id', $this->empresa_id)
                ->findOrFail($request->gerador_id);

            $transportador = $this->clientePorDocumento($request->transportador_cnpj);
            $destinador = $this->clientePorDocumento($request->destinador_cnpj);

            $tipoOrigem = $this->normalizarTipoOrigem(
                (string) $request->input('origem_tipo', 'avulso')
            );

            $manifesto = MtrManifesto::create([
                'empresa_id' => $this->empresa_id,
                'usuario_id' => $this->usuario_id,
                'orgao' => $gerador->orgao ?: 'SINIR',
                'tipo_origem' => $tipoOrigem,
                'origem_id' => $request->input('origem_id') ?: null,
                'venda_id' => $tipoOrigem === 'nfe'
                    ? ($request->input('venda_id') ?: $request->input('origem_id'))
                    : null,
                'ticket_pesagem_id' => $tipoOrigem === 'ticket_pesagem'
                    ? ($request->input('ticket_pesagem_id') ?: null)
                    : null,
                'seu_codigo' => 'MTR-' . time(),
                'data_expedicao' => Carbon::parse($request->input('data_expedicao') ?: now()),
                'gerador_cnpj' => preg_replace('/\D+/', '', (string) $gerador->cpf_cnpj),
                'gerador_nome' => $gerador->descricao ?: 'GERADOR',
                'transportador_cnpj' => preg_replace('/\D+/', '', (string) $request->transportador_cnpj),
                'transportador_nome' => $transportador->razao_social ?? 'TRANSPORTADOR',
                'destinador_cnpj' => preg_replace('/\D+/', '', (string) $request->destinador_cnpj),
                'destinador_nome' => $destinador->razao_social ?? 'DESTINADOR',
                'armazenador_cnpj' => $request->has('possui_armazenamento')
                    ? preg_replace('/\D+/', '', (string) $request->armazenador_cnpj)
                    : null,
                'veiculo_placa' => strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', (string) $request->placa_veiculo)),
                'motorista_nome' => $request->nome_motorista,
                'observacao' => $request->observacao,
                'status' => 'rascunho',
            ]);

            $this->replaceItens($manifesto, (array) $request->input('residuos', []));

            DB::commit();

            if ($request->acao === 'transmitir') {
                return $this->transmitir($request, $manifesto->id);
            }

            return redirect()->route('mtr.emissao.index')
                ->with('sucesso', 'MTR salvo como rascunho com sucesso!');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Erro ao salvar rascunho MTR.', [
                'empresa_id' => $this->empresa_id,
                'usuario_id' => $this->usuario_id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->withInput()->with('erro', 'Erro ao salvar: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $mtr = MtrManifesto::where('empresa_id', $this->empresa_id)
            ->with('itens')
            ->findOrFail($id);

        if ($mtr->status === 'transmitido') {
            return redirect()->route('mtr.emissao.index')
                ->with('erro', 'MTRs transmitidos não podem ser editados.');
        }

        $listas = $this->getListasAutocompletar();

        return view('mtr.emissao.edit', array_merge([
            'mtr' => $mtr,
            'itens' => $mtr->itens,
            'unidadesGeradoras' => MtrConfig::where('empresa_id', $this->empresa_id)
                ->where('perfil', 'Gerador')->where('ativo', true)->get(),
            'clientes' => DB::table('clientes')
                ->where('empresa_id', $this->empresa_id)
                ->orderBy('razao_social')->get(),
            'produtosComDepara' => $this->getProdutosComDepara(),
            'title' => 'Editar Rascunho MTR',
        ], $listas));
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $mtr = MtrManifesto::where('empresa_id', $this->empresa_id)->findOrFail($id);

            if ($mtr->status === 'transmitido') {
                throw new Exception('Edição não permitida para MTR transmitido.');
            }

            $gerador = MtrConfig::where('empresa_id', $this->empresa_id)
                ->findOrFail($request->gerador_id);

            $transportador = $this->clientePorDocumento($request->transportador_cnpj);
            $destinador = $this->clientePorDocumento($request->destinador_cnpj);

            $mtr->fill([
                'orgao' => $gerador->orgao ?: $mtr->orgao,
                'gerador_cnpj' => preg_replace('/\D+/', '', (string) $gerador->cpf_cnpj),
                'gerador_nome' => $gerador->descricao ?: 'GERADOR',
                'transportador_cnpj' => preg_replace('/\D+/', '', (string) $request->transportador_cnpj),
                'transportador_nome' => $transportador->razao_social ?? 'TRANSPORTADOR',
                'destinador_cnpj' => preg_replace('/\D+/', '', (string) $request->destinador_cnpj),
                'destinador_nome' => $destinador->razao_social ?? 'DESTINADOR',
                'armazenador_cnpj' => $request->has('possui_armazenamento')
                    ? preg_replace('/\D+/', '', (string) $request->armazenador_cnpj)
                    : null,
                'veiculo_placa' => strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', (string) $request->placa_veiculo)),
                'motorista_nome' => $request->nome_motorista,
                'data_expedicao' => Carbon::parse($request->input('data_expedicao') ?: now()),
                'observacao' => $request->observacao,
            ]);
            $mtr->save();

            $this->replaceItens($mtr, (array) $request->input('residuos', []));

            DB::commit();

            if ($request->acao === 'transmitir') {
                return $this->transmitir($request, $mtr->id);
            }

            return redirect()->route('mtr.emissao.index')
                ->with('sucesso', 'Rascunho atualizado com sucesso!');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('erro', 'Erro ao atualizar: ' . $e->getMessage());
        }
    }

    public function transmitir(Request $request, $id)
    {
        try {
            $mtr = MtrManifesto::where('empresa_id', $this->empresa_id)
                ->with('itens')
                ->findOrFail($id);

            $credencial = MtrConfig::where('empresa_id', $this->empresa_id)
                ->where('cpf_cnpj', $mtr->gerador_cnpj)
                ->first();

            if (!$credencial) {
                throw new Exception('Credenciais não configuradas para o Gerador.');
            }

            $service = new SinirIemaService($credencial->orgao, $credencial->ambiente);
            $token = $service->getToken(
                $credencial->cpf_cnpj,
                (string) $credencial->senha,
                (string) $credencial->unidade_id,
                $credencial->cpf_usuario
            );

            $residuos = [];
            foreach ($mtr->itens as $item) {
                $unidade = $this->codigoInteiro($item->unidade_medida);
                $acondicionamento = (int) $item->acondicionamento_id;
                $tratamento = (int) $item->tratamento_id;
                $estado = (int) ($item->estado_fisico ?: 1);
                $classe = $this->codigoInteiro($item->classe_residuo);

                if ($unidade <= 0 || $acondicionamento <= 0 || $tratamento <= 0 || $classe <= 0) {
                    throw new Exception('Há resíduo sem unidade, classe, acondicionamento ou tratamento válido.');
                }

                $itemPayload = [
                    'resCodigoIbama' => trim((string) $item->cod_ibama),
                    'marQuantidade' => (float) $item->quantidade,
                    'uniCodigo' => $unidade,
                    'tiaCodigo' => $acondicionamento,
                    'traCodigo' => $tratamento,
                    'tieCodigo' => $estado,
                    'claCodigo' => $classe,
                ];

                if (!empty($item->numero_onu)) {
                    $itemPayload['marNumeroONU'] = (string) $item->numero_onu;
                    $itemPayload['marClasseRisco'] = (string) $item->classe_risco;
                    $itemPayload['marNomeEmbarque'] = (string) $item->nome_embarque;

                    if (!empty($item->grupo_embalagem) && $item->grupo_embalagem !== 'NA') {
                        $mapaGrupos = [
                            'I'   => 1,
                            'II'  => 2,
                            'III' => 3
                        ];

                        if (isset($mapaGrupos[$item->grupo_embalagem])) {
                            $itemPayload['greCodigo'] = $mapaGrupos[$item->grupo_embalagem];
                        }
                    }
                }

                if (!empty($item->codigo_interno)) {
                    $itemPayload['marCodigoInterno'] = (string) $item->codigo_interno;
                }
                if (!empty($item->descricao_interna)) {
                    $itemPayload['marDescricaoInterna'] = (string) $item->descricao_interna;
                }

                $residuos[] = $itemPayload;
            }

            $transportadorUnidade = $this->unidadePorDocumento($mtr->transportador_cnpj);
            $destinadorUnidade = $this->unidadePorDocumento($mtr->destinador_cnpj);

            if (!$transportadorUnidade || !$destinadorUnidade) {
                throw new Exception('Cadastre a unidade MTR do transportador e do destinador.');
            }

            $manifestoData = [
                'gerador' => [
                    'cpfCnpj' => preg_replace('/\D+/', '', (string) $mtr->gerador_cnpj),
                    'unidade' => (int) $credencial->unidade_id,
                ],
                'transportador' => [
                    'cpfCnpj' => preg_replace('/\D+/', '', (string) $mtr->transportador_cnpj),
                    'unidade' => (int) $transportadorUnidade,
                ],
                'destinador' => [
                    'cpfCnpj' => preg_replace('/\D+/', '', (string) $mtr->destinador_cnpj),
                    'unidade' => (int) $destinadorUnidade,
                ],
                'listaManifestoResiduos' => $residuos,
                'nomeMotorista' => mb_strtoupper((string) ($mtr->motorista_nome ?: 'NAO INFORMADO')),
                'placaVeiculo' => strtoupper((string) ($mtr->veiculo_placa ?: 'AAA0000')),
                'dataExpedicao' => (int) (Carbon::parse($mtr->data_expedicao)->timestamp * 1000),
                'possuiArmazenamentoTemporario' => !empty($mtr->armazenador_cnpj),
                'nomeResponsavel' => 'Responsável Técnico',
                'tipoManifesto' => 0,
                'seuCodigo' => (string) $mtr->seu_codigo,
                'observacoes' => (string) ($mtr->observacao ?? ''),
            ];

            if ($mtr->armazenador_cnpj) {
                $manifestoData['armazenadorTemporario'] = [
                    'cpfCnpj' => preg_replace('/\D+/', '', (string) $mtr->armazenador_cnpj),
                    'unidade' => (int) ($this->unidadePorDocumento($mtr->armazenador_cnpj) ?: 1),
                ];
            }

            $resposta = $service->transmitirManifesto(
                $token,
                $credencial->cpf_cnpj,
                (string) $credencial->senha,
                (string) $credencial->unidade_id,
                [$manifestoData]
            );

            $numero = data_get($resposta, 'respostaApiwsManifestoDTO.0.manifestoNumeroNacional')
                ?: data_get($resposta, 'respostaApiwsManifestoDTO.0.manNumero')
                    ?: data_get($resposta, '0.manifestoNumeroNacional')
                        ?: data_get($resposta, 'manNumero');

            if (!$numero) {
                $mensagem = data_get($resposta, 'respostaApiwsManifestoDTO.0.resposta.mensagem')
                    ?: data_get($resposta, 'respostaApiwsManifestoDTO.0.restResponseMensagem')
                        ?: 'Resposta sem número oficial de MTR.';

                $mtr->status = 'erro';
                $mtr->mensagem_retorno = mb_substr((string) $mensagem, 0, 2000);
                $mtr->save();

                throw new Exception('Rejeição SINIR/IEMA: ' . $mensagem);
            }

            $mtr->status = 'transmitido';
            $mtr->numero_mtr = (string) $numero;
            $mtr->mensagem_retorno = 'Transmitido com sucesso.';
            $mtr->save();

            return redirect()->route('mtr.emissao.index')
                ->with('sucesso', 'MTR Oficial gerado com sucesso: ' . $numero);
        } catch (\Throwable $e) {
            Log::error('Erro na transmissão MTR.', [
                'empresa_id' => $this->empresa_id,
                'mtr_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('mtr.emissao.index')
                ->with('erro', 'Erro na transmissão: ' . $e->getMessage());
        }
    }

    public function downloadPdf($numeroMtr)
    {
        return $this->downloadDocumento($numeroMtr, false);
    }

    public function downloadCdf($numeroMtr)
    {
        return $this->downloadDocumento($numeroMtr, true);
    }

    public function cancelar(Request $request, $id)
    {
        try {
            $mtr = MtrManifesto::where('empresa_id', $this->empresa_id)->findOrFail($id);
            $credencial = $this->credencialDoManifesto($mtr);
            $service = new SinirIemaService($credencial->orgao, $credencial->ambiente);
            $token = $this->token($credencial, $service);

            $service->requestWithToken($token, 'POST', '/cancelarManifesto', [
                'manNumero' => (string) $mtr->numero_mtr,
                'justificativa' => (string) $request->input(
                    'justificativa',
                    'Cancelamento solicitado pelo gerador em ' . config('app.name') . '.'
                ),
            ]);

            $mtr->status = 'cancelado';
            $mtr->save();

            return redirect()->route('mtr.emissao.index')
                ->with('sucesso', 'Manifesto cancelado com sucesso!');
        } catch (\Throwable $e) {
            return redirect()->route('mtr.emissao.index')
                ->with('erro', 'Erro ao cancelar MTR: ' . $e->getMessage());
        }
    }

    public function consultarStatus($id)
    {
        try {
            $mtr = MtrManifesto::where('empresa_id', $this->empresa_id)->findOrFail($id);

            if (!$mtr->numero_mtr) {
                return response()->json([
                    'success' => false,
                    'message' => 'MTR ainda não possui número oficial.',
                ], 422);
            }

            $credencial = MtrConfig::where('empresa_id', $this->empresa_id)
                ->where('cpf_cnpj', $mtr->gerador_cnpj)
                ->first() ?: $this->credencialPadrao();

            $service = new SinirIemaService($credencial->orgao, $credencial->ambiente);
            $token = $this->token($credencial, $service);

            $dadosBrutos = null;
            try {
                $dadosBrutos = $service->requestWithToken(
                    $token,
                    'GET',
                    '/retornaManifesto/' . rawurlencode($mtr->numero_mtr)
                );
            } catch (\Throwable $e) {
                $dadosBrutos = $service->requestWithToken(
                    $token,
                    'GET',
                    '/consultarManifesto/' . rawurlencode($mtr->numero_mtr)
                );
            }

            $dados = data_get($dadosBrutos, 'objetoResposta.0')
                ?? data_get($dadosBrutos, 'objetoResposta')
                ?? data_get($dadosBrutos, '0')
                ?? $dadosBrutos;

            $situacao = (string) (
                data_get($dados, 'situacaoManifesto.simDescricao')
                ?? data_get($dados, 'situacao')
                ?? data_get($dados, 'status')
                ?? 'Emitido'
            );

            if (stripos($situacao, 'Cancelado') !== false) {
                $mtr->status = 'cancelado';
            } else {
                $mtr->status = 'transmitido';
            }

            $mtr->mensagem_retorno = mb_substr($situacao, 0, 2000);
            $mtr->save();

            return response()->json([
                'success' => true,
                'status' => $mtr->status,
                'situacao_descricao' => $situacao,
                'message' => 'Status atualizado no SINIR: ' . $situacao,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function enviarWhatsAppMtr(Request $request)
    {
        try {
            $numero = preg_replace('/\D+/', '', (string) $request->whatsapp);
            if (strlen($numero) < 10) {
                return response()->json(['success' => false, 'message' => 'Número de WhatsApp inválido.'], 422);
            }

            if (!str_starts_with($numero, '55')) {
                $numero = '55' . $numero;
            }

            $mtr = MtrManifesto::where('empresa_id', $this->empresa_id)
                ->findOrFail($request->mtr_id);

            if (empty($mtr->numero_mtr)) {
                return response()->json(['success' => false, 'message' => 'Este MTR ainda não possui número oficial para gerar PDF.'], 422);
            }

            $credencial = MtrConfig::where('empresa_id', $this->empresa_id)
                ->where('cpf_cnpj', $mtr->gerador_cnpj)
                ->first() ?: $this->credencialPadrao();

            $service = new SinirIemaService($credencial->orgao, $credencial->ambiente);
            $pdfContent = $service->downloadWithToken(
                $this->token($credencial, $service),
                '/downloadManifesto/' . rawurlencode($mtr->numero_mtr)
            );

            if (empty($pdfContent)) {
                throw new \RuntimeException('O SINIR não retornou o arquivo PDF deste MTR.');
            }

            $nomePdfLocal = 'MTR_' . $mtr->numero_mtr . '.pdf';
            $caminhoTemp = storage_path('app/public/' . $nomePdfLocal);
            file_put_contents($caminhoTemp, $pdfContent);

            $legenda = "📄 *MANIFESTO DE TRANSPORTE DE RESÍDUOS (MTR)*\n"
                . "----------------------------------------\n"
                . "*Nº MTR Oficial:* {$mtr->numero_mtr}\n"
                . "*Gerador:* {$mtr->gerador_nome}\n"
                . "*Destinador:* {$mtr->destinador_nome}\n"
                . "*Placa Veículo:* " . ($mtr->veiculo_placa ?: '-');

            $retorno = $this->whatsapputil->sendMedia(
                (int) $this->empresa_id,
                $numero,
                $caminhoTemp,
                $legenda
            );

            if (!is_array($retorno) || !($retorno['success'] ?? false)) {
                throw new \RuntimeException(
                    $retorno['error'] ?? $retorno['message'] ?? 'Falha ao enviar o PDF do MTR via WhatsApp.'
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'PDF do MTR enviado via WhatsApp com sucesso!',
                'provider_response' => $retorno,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao enviar WhatsApp: ' . $e->getMessage(),
            ], 400);
        }
    }

    public function destroy($id)
    {
        $mtr = MtrManifesto::where('empresa_id', $this->empresa_id)->findOrFail($id);

        if ($mtr->status === 'transmitido') {
            return redirect()->back()
                ->with('erro', 'MTRs transmitidos não podem ser excluídos, apenas cancelados.');
        }

        MtrManifestoItem::where('mtr_manifesto_id', $mtr->id)->delete();
        $mtr->delete();

        return redirect()->route('mtr.emissao.index')
            ->with('sucesso', 'Rascunho excluído com sucesso.');
    }

    public function recepcaoIndex(Request $request)
    {
        $credencial = MtrConfig::where('empresa_id', $this->empresa_id)
            ->where('ativo', true)
            ->first();

        $cnpjEmpresa = preg_replace('/\D+/', '', (string) ($credencial?->cpf_cnpj ?? ''));

        $filtrouTransp = $request->filled('dt_inicio_transp') || $request->filled('dt_fim_transp') || $request->filled('mtr_transp');
        $filtrouDest   = $request->filled('dt_inicio_dest') || $request->filled('dt_fim_dest') || $request->filled('mtr_dest');

        // 1. MTRs como DESTINADOR / RECEBIDOS
        $queryDest = MtrManifesto::where('empresa_id', $this->empresa_id)
            ->where(function ($q) use ($cnpjEmpresa) {
                if ($cnpjEmpresa !== '') {
                    $q->where('destinador_cnpj', 'like', "%{$cnpjEmpresa}%");
                }
                $q->orWhere(function ($received) {
                    $received->where('status', 'transmitido')
                        ->where('mensagem_retorno', 'like', '%recebido%');
                })
                    ->orWhere('mensagem_retorno', 'like', '%recebid%');
            });

        if ($request->filled('dt_inicio_dest') && $request->filled('dt_fim_dest')) {
            $queryDest->whereBetween('data_expedicao', [
                $request->dt_inicio_dest . ' 00:00:00',
                $request->dt_fim_dest . ' 23:59:59',
            ]);
        } elseif ($request->filled('dt_inicio_dest')) {
            $queryDest->where('data_expedicao', '>=', $request->dt_inicio_dest . ' 00:00:00');
        } elseif ($request->filled('dt_fim_dest')) {
            $queryDest->where('data_expedicao', '<=', $request->dt_fim_dest . ' 23:59:59');
        }

        if ($request->filled('mtr_dest')) {
            $queryDest->where('numero_mtr', 'like', '%' . trim((string) $request->mtr_dest) . '%');
        }

        $queryDest->orderByDesc('data_expedicao')->orderByDesc('id');
        if (!$filtrouDest) {
            $queryDest->limit(10);
        }

        $mtrsDestinador = $queryDest->get()->map(function ($item) {
            return [
                'id'                => $item->id,
                'numeroMtr'         => $item->numero_mtr,
                'dataEmissao'       => strtotime((string) $item->data_expedicao) * 1000,
                'geradorNome'       => $item->gerador_nome,
                'destinadorNome'    => $item->destinador_nome,
                'transportadorNome' => $item->transportador_nome,
                'situacao'          => 'Recebido',
            ];
        });

        // 2. MTRs como TRANSPORTADOR
        $queryTransp = MtrManifesto::where('empresa_id', $this->empresa_id)
            ->where(function ($q) use ($cnpjEmpresa) {
                if ($cnpjEmpresa !== '') {
                    $q->where('transportador_cnpj', 'like', "%{$cnpjEmpresa}%");
                }
                $q->orWhere('tipo_origem', 'avulso');
            });

        if ($request->filled('dt_inicio_transp') && $request->filled('dt_fim_transp')) {
            $queryTransp->whereBetween('data_expedicao', [
                $request->dt_inicio_transp . ' 00:00:00',
                $request->dt_fim_transp . ' 23:59:59',
            ]);
        } elseif ($request->filled('dt_inicio_transp')) {
            $queryTransp->where('data_expedicao', '>=', $request->dt_inicio_transp . ' 00:00:00');
        } elseif ($request->filled('dt_fim_transp')) {
            $queryTransp->where('data_expedicao', '<=', $request->dt_fim_transp . ' 23:59:59');
        }

        if ($request->filled('mtr_transp')) {
            $queryTransp->where('numero_mtr', 'like', '%' . trim((string) $request->mtr_transp) . '%');
        }

        $queryTransp->orderByDesc('data_expedicao')->orderByDesc('id');
        if (!$filtrouTransp) {
            $queryTransp->limit(10);
        }

        $mtrsTransportador = $queryTransp->get()->map(function ($item) {
            return [
                'id'                => $item->id,
                'numeroMtr'         => $item->numero_mtr,
                'dataEmissao'       => strtotime((string) $item->data_expedicao) * 1000,
                'geradorNome'       => $item->gerador_nome,
                'destinadorNome'    => $item->destinador_nome,
                'transportadorNome' => $item->transportador_nome,
                'situacao'          => ucfirst($item->status),
            ];
        });

        return view('mtr.recepcao.index', [
            'mtrsTransportador' => $mtrsTransportador,
            'mtrsDestinador'    => $mtrsDestinador,
            'dtInicio'          => $request->input('dt_inicio_dest', date('Y-m-d')),
            'dtFim'             => $request->input('dt_fim_dest', date('Y-m-d')),
            'title'             => 'Recepção e Transporte de MTR (SINIR)',
        ]);
    }

    public function salvarRecebimentoMtr(Request $request)
    {
        try {
            $credencial = $this->credencialPadrao();
            $service = new SinirIemaService($credencial->orgao, $credencial->ambiente);
            $token = $this->token($credencial, $service);

            $numeroMtr = (string) $request->numero_mtr;
            $responsavel = (string) $request->responsavel;
            $dataRecebimento = (int) (\Carbon\Carbon::parse($request->input('data_recebimento') ?: now())->timestamp * 1000);
            $residuosInput = $request->input('residuos', []);

            $placaVeiculo = (string) $request->input('placa', '');
            $nomeMotorista = (string) $request->input('motorista', '');

            $payload = [
                'manNumero' => $numeroMtr,
                'nomeResponsavelRecebimento' => $responsavel,
                'nomeMotorista' => $nomeMotorista,
                'placaVeiculo' => $placaVeiculo,
                'dataRecebimento' => $dataRecebimento,
                'observacoes' => (string) ($request->input('observacao') ?: 'Recebimento efetuado via Fersoft ERP')
            ];

            $listaManifestoResiduos = [];
            if (!empty($residuosInput)) {
                foreach ($residuosInput as $residuo) {
                    $qtdRecebida = $this->parseQuantidadeMtr($residuo['quantidadeRecebida'] ?? '0');
                    $qtdEmitida  = $this->parseQuantidadeMtr($residuo['quantidade'] ?? ($residuo['quantidadeRecebida'] ?? '0'));

                    $cod = trim((string) ($residuo['cod_ibama'] ?? $residuo['codigo_ibama'] ?? ''));

                    if ($qtdRecebida > 0 && $cod !== '') {
                        $justificativaItem = trim((string) ($residuo['justificativa'] ?? ''));

                        if (abs($qtdRecebida - $qtdEmitida) > 0.0001 && $justificativaItem === '') {
                            $justificativaItem = 'Divergência de pesagem na recepção (emitido: ' . $qtdEmitida . ' / recebido: ' . $qtdRecebida . ')';
                        }

                        $itemPayload = [
                            'resCodigoIbama' => $cod,
                            'marQuantidade' => $qtdEmitida,
                            'marQuantidadeRecebida' => $qtdRecebida,
                            'uniCodigo' => (int) ($residuo['unidade_medida'] ?? 3),
                            'traCodigo' => (int) ($residuo['tratamento_id'] ?? 43),
                            'tiaCodigo' => (int) ($residuo['acondicionamento_id'] ?? 8),
                            'tieCodigo' => (int) ($residuo['estado_fisico'] ?? 1),
                            'claCodigo' => (int) ($residuo['classe_residuo'] ?? 43),
                        ];

                        if ($justificativaItem !== '') {
                            $itemPayload['marJustificativa'] = $justificativaItem;
                        }

                        if (!empty($residuo['codigo_gerado'])) {
                            $itemPayload['codigoGerado'] = (int) $residuo['codigo_gerado'];
                            $itemPayload['resCodigo'] = (int) $residuo['codigo_gerado'];
                        }

                        $listaManifestoResiduos[] = $itemPayload;
                    }
                }
            }

            if (!empty($listaManifestoResiduos)) {
                $payload['listaManifestoResiduos'] = $listaManifestoResiduos;
            }

            $baseUrl = ($credencial->ambiente === 'producao')
                ? 'https://admin.sinir.gov.br/apiws/rest'
                : 'https://admin-homologacao.sinir.gov.br/apiws/rest';

            if ($credencial->orgao === 'IEMA') {
                $baseUrl = ($credencial->ambiente === 'producao')
                    ? 'https://mtr.iema.es.gov.br/apiws/rest'
                    : 'https://mtr-homologacao.iema.es.gov.br/apiws/rest';
            }

            $jaRecebidoNoGoverno = false;
            $jsonResponse = null;

            try {
                $response = \Illuminate\Support\Facades\Http::withToken($token)
                    ->withHeaders([
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json'
                    ])
                    ->post($baseUrl . '/receberManifesto', $payload);

                if ($response->failed() && $response->status() === 404) {
                    $response = \Illuminate\Support\Facades\Http::withToken($token)
                        ->withHeaders([
                            'Accept' => 'application/json',
                            'Content-Type' => 'application/json'
                        ])
                        ->post($baseUrl . '/receberManifestoLote', [$payload]);
                }

                $jsonResponse = $response->json();

                if ($response->failed() || (is_array($jsonResponse) && (isset($jsonResponse['erro']) && $jsonResponse['erro'] == true || isset($jsonResponse['erros'])))) {
                    $detalhesErro = json_encode($jsonResponse);

                    if (
                        stripos($detalhesErro, 'já recebido') !== false ||
                        stripos($detalhesErro, 'ja recebido') !== false ||
                        stripos($detalhesErro, 'situacao invalida') !== false ||
                        stripos($detalhesErro, 'situação inválida') !== false ||
                        stripos($detalhesErro, 'cdf') !== false ||
                        stripos($detalhesErro, 'certificado') !== false ||
                        stripos($detalhesErro, 'diferente de ativo') !== false
                    ) {
                        $jaRecebidoNoGoverno = true;
                    } else {
                        throw new \Exception("Recusado pelo SINIR. Detalhes: " . $detalhesErro);
                    }
                }
            } catch (\Throwable $e) {
                $erroMsg = $e->getMessage();

                if (
                    stripos($erroMsg, 'já recebido') !== false ||
                    stripos($erroMsg, 'ja recebido') !== false ||
                    stripos($erroMsg, 'invalida') !== false ||
                    stripos($erroMsg, 'inválida') !== false ||
                    stripos($erroMsg, 'cdf') !== false ||
                    stripos($erroMsg, 'certificado') !== false ||
                    stripos($erroMsg, 'diferente de ativo') !== false
                ) {
                    $jaRecebidoNoGoverno = true;
                } elseif (!$jaRecebidoNoGoverno) {
                    throw new \Exception("Erro ao comunicar recebimento ao SINIR: " . $erroMsg);
                }
            }

            $dadosGoverno = $this->detalhesMtrExterno($numeroMtr)->getData(true);

            $geradorNomeForm = (string) $request->input('gerador_nome');
            $transportadorNomeForm = (string) $request->input('transportador_nome');
            $destinadorCnpjForm = preg_replace('/\D+/', '', (string) $credencial->cpf_cnpj);
            $destinadorNomeForm = strtoupper($credencial->descricao ?: 'EMPRESA DESTINATÁRIA');
            $dataExpedicaoForm = date('Y-m-d H:i:s');

            if (isset($dadosGoverno['success']) && $dadosGoverno['success'] && !empty($dadosGoverno['data']['geradorNome'])) {
                $geradorNomeForm = $dadosGoverno['data']['geradorNome'];
                $transportadorNomeForm = $dadosGoverno['data']['transportadorNome'] ?: $transportadorNomeForm;

                if (!empty($dadosGoverno['data']['destinadorNome'])) {
                    $destinadorNomeForm = $dadosGoverno['data']['destinadorNome'];
                }
                if (!empty($dadosGoverno['data']['destinadorCnpj'])) {
                    $destinadorCnpjForm = preg_replace('/\D+/', '', $dadosGoverno['data']['destinadorCnpj']);
                }

                if (!empty($dadosGoverno['data']['dataExpedicao'])) {
                    $ts = (int) $dadosGoverno['data']['dataExpedicao'];
                    $dataExpedicaoForm = date('Y-m-d H:i:s', ($ts > 9999999999 ? $ts / 1000 : $ts));
                }
            }

            if (empty($geradorNomeForm)) {
                $geradorNomeForm = 'GERADOR EXTERNO (NOME NÃO INFORMADO)';
            }
            if (empty($transportadorNomeForm)) {
                $transportadorNomeForm = 'TRANSPORTADOR (NOME NÃO INFORMADO)';
            }

            DB::beginTransaction();

            $mtr = \App\Models\MtrManifesto::where('empresa_id', $this->empresa_id)
                ->where('numero_mtr', $numeroMtr)
                ->first();

            if (!$mtr) {
                $mtr = \App\Models\MtrManifesto::create([
                    'empresa_id' => $this->empresa_id,
                    'usuario_id' => $this->usuario_id,
                    'orgao' => $credencial->orgao ?: 'SINIR',
                    'tipo_origem' => 'avulso',
                    'seu_codigo' => 'REC-' . time(),
                    'numero_mtr' => $numeroMtr,
                    'data_expedicao' => $dataExpedicaoForm,
                    'gerador_nome' => strtoupper($geradorNomeForm),
                    'transportador_nome' => strtoupper($transportadorNomeForm),
                    'destinador_cnpj' => $destinadorCnpjForm,
                    'destinador_nome' => strtoupper($destinadorNomeForm),
                    'veiculo_placa' => strtoupper($request->input('placa', 'N/INF')),
                    'motorista_nome' => strtoupper($request->input('motorista', 'N/INF')),
                    'status' => 'transmitido',
                    'mensagem_retorno' => $jaRecebidoNoGoverno ? 'Sincronizado: Já constava como recebido no SINIR.' : 'Manifesto recebido e baixado com sucesso no SINIR.',
                ]);
            } else {
                $mtr->status = 'transmitido';
                $mtr->mensagem_retorno = $jaRecebidoNoGoverno ? 'Sincronizado: Já constava como recebido no SINIR.' : 'Manifesto recebido e baixado com sucesso no SINIR.';
                $mtr->gerador_nome = strtoupper($geradorNomeForm);
                $mtr->transportador_nome = strtoupper($transportadorNomeForm);
                $mtr->destinador_nome = strtoupper($destinadorNomeForm);
                $mtr->destinador_cnpj = $destinadorCnpjForm;
                if ($mtr->data_expedicao == date('Y-m-d H:i:s') && $dataExpedicaoForm != date('Y-m-d H:i:s')) {
                    $mtr->data_expedicao = $dataExpedicaoForm;
                }
                $mtr->veiculo_placa = strtoupper($request->input('placa', $mtr->veiculo_placa));
                $mtr->motorista_nome = strtoupper($request->input('motorista', $mtr->motorista_nome));
                $mtr->save();
            }

            if (!empty($residuosInput)) {
                \App\Models\MtrManifestoItem::where('mtr_manifesto_id', $mtr->id)->delete();

                foreach ($residuosInput as $residuo) {
                    $qtd = $this->parseQuantidadeMtr($residuo['quantidadeRecebida'] ?? ($residuo['quantidade'] ?? '0'));
                    $cod = trim((string) ($residuo['cod_ibama'] ?? $residuo['codigo_ibama'] ?? ''));

                    if ($qtd > 0 && $cod !== '') {
                        \App\Models\MtrManifestoItem::create([
                            'mtr_manifesto_id' => $mtr->id,
                            'cod_ibama' => $cod,
                            'descricao_residuo' => $residuo['descricao'] ?? 'RESÍDUO DECLARADO',
                            'quantidade' => $qtd,
                            'unidade_medida' => (string) ($residuo['unidade_medida'] ?? 3),
                            'estado_fisico' => (int) ($residuo['estado_fisico'] ?? 1),
                            'classe_residuo' => (string) ($residuo['classe_residuo'] ?? 43),
                            'acondicionamento_id' => (int) ($residuo['acondicionamento_id'] ?? 8),
                            'tratamento_id' => (int) ($residuo['tratamento_id'] ?? 43),
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $jaRecebidoNoGoverno
                    ? 'MTR sincronizado! Ele já havia sido baixado no portal e agora está gravado no sistema.'
                    : 'MTR recebido e baixado com sucesso no portal do SINIR e salvo localmente!',
            ]);
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function receberPorCodigoBarras(Request $request)
    {
        try {
            parse_str($request->getContent(), $dados);
            $numero = $request->input('codigo_barras') ?? ($dados['codigo_barras'] ?? null);

            if (!$numero) {
                throw new Exception('Informe o número do MTR para recebimento.');
            }

            $credencial = $this->credencialPadrao();
            $service = new SinirIemaService($credencial->orgao, $credencial->ambiente);

            $service->requestWithToken(
                $this->token($credencial, $service),
                'POST',
                '/receberManifesto',
                ['manNumero' => trim((string) $numero)]
            );

            return redirect()->route('mtr.recepcao.index')
                ->with('sucesso', 'MTR recebido e confirmado com sucesso!');
        } catch (\Throwable $e) {
            return redirect()->route('mtr.recepcao.index')
                ->with('erro', 'Erro ao receber MTR: ' . $e->getMessage());
        }
    }

    public function receberPorProvisorio(Request $request)
    {
        try {
            $credencial = $this->credencialPadrao();
            $service = new SinirIemaService($credencial->orgao, $credencial->ambiente);

            $service->requestWithToken(
                $this->token($credencial, $service),
                'POST',
                '/receberManifestoProvisorio',
                [
                    'numeroMtrProvisorio' => $request->numero_provisorio,
                    'cnpjGerador' => preg_replace('/\D+/', '', (string) $request->cnpj_gerador),
                ]
            );

            return redirect()->route('mtr.recepcao.index')
                ->with('sucesso', 'MTR Provisório recebido com sucesso!');
        } catch (\Throwable $e) {
            return redirect()->route('mtr.recepcao.index')
                ->with('erro', 'Erro ao receber MTR Provisório: ' . $e->getMessage());
        }
    }

    public function detalhesMtrExterno($numeroMtr)
    {
        try {
            $numeroLimpo = preg_replace('/[^0-9a-zA-Z]/', '', (string) $numeroMtr);

            $credencial = $this->credencialPadrao();
            $service = new SinirIemaService($credencial->orgao, $credencial->ambiente);
            $token = $this->token($credencial, $service);

            $baseUrlLegada = ($credencial->ambiente === 'producao')
                ? 'https://admin.sinir.gov.br/apiws/rest'
                : 'https://admin-homologacao.sinir.gov.br/apiws/rest';

            if ($credencial->orgao === 'IEMA') {
                $baseUrlLegada = ($credencial->ambiente === 'producao')
                    ? 'https://mtr.iema.es.gov.br/apiws/rest'
                    : 'https://mtr-homologacao.iema.es.gov.br/apiws/rest';
            }

            $response = \Illuminate\Support\Facades\Http::withToken($token)
                ->withHeaders(['Accept' => 'application/json'])
                ->get($baseUrlLegada . '/retornaManifesto/' . $numeroLimpo);

            $dadosBrutos = $response->successful() ? $response->json() : null;

            \Illuminate\Support\Facades\Log::info("SINIR RETORNO MTR #{$numeroLimpo}:", [
                'resposta_bruta' => $dadosBrutos
            ]);

            $dados = data_get($dadosBrutos, 'objetoResposta.0')
                ?? data_get($dadosBrutos, 'objetoResposta')
                ?? data_get($dadosBrutos, '0')
                ?? $dadosBrutos;

            if (!is_array($dados) || (empty($dados['manNumero']) && empty($dados['listaManifestoResiduos']) && empty($dados['gerador']) && empty($dados['parceiroGerador']))) {
                try {
                    $responseAlt = \Illuminate\Support\Facades\Http::withToken($token)
                        ->withHeaders(['Accept' => 'application/json'])
                        ->get($baseUrlLegada . '/consultarManifesto/' . $numeroLimpo);
                    $dadosAlt = $responseAlt->successful() ? $responseAlt->json() : null;

                    if (is_array($dadosAlt)) {
                        $dados = data_get($dadosAlt, 'objetoResposta.0')
                            ?? data_get($dadosAlt, 'objetoResposta')
                            ?? data_get($dadosAlt, '0')
                            ?? $dadosAlt;
                    }
                } catch (\Throwable $e) {
                    // Continua para o fallback
                }
            }

            if (is_array($dados) && !empty($dados)) {
                $geradorCnpj = data_get($dados, 'gerador.cpfCnpj')
                    ?? data_get($dados, 'parceiroGerador.parCnpj')
                    ?? data_get($dados, 'parceiroGerador.cpfCnpj')
                    ?? data_get($dados, 'cnpjGerador')
                    ?? '';

                $gerador = data_get($dados, 'gerador.nome')
                    ?? data_get($dados, 'gerador.razaoSocial')
                    ?? data_get($dados, 'gerador.parDescricao')
                    ?? data_get($dados, 'parceiroGerador.parDescricao')
                    ?? data_get($dados, 'parceiroGerador.nome')
                    ?? data_get($dados, 'nomeGerador')
                    ?? data_get($dados, 'geradorNome')
                    ?? '';

                if (empty($gerador) && !empty($geradorCnpj)) {
                    $gerador = $this->nomeParceiroPorDocumento($geradorCnpj) ?? '';
                }

                $transportadorCnpj = data_get($dados, 'transportador.cpfCnpj')
                    ?? data_get($dados, 'parceiroTransportador.parCnpj')
                    ?? data_get($dados, 'parceiroTransportador.cpfCnpj')
                    ?? data_get($dados, 'cnpjTransportador')
                    ?? '';

                $transportador = data_get($dados, 'transportador.nome')
                    ?? data_get($dados, 'transportador.razaoSocial')
                    ?? data_get($dados, 'transportador.parDescricao')
                    ?? data_get($dados, 'parceiroTransportador.parDescricao')
                    ?? data_get($dados, 'parceiroTransportador.nome')
                    ?? data_get($dados, 'nomeTransportador')
                    ?? data_get($dados, 'transportadorNome')
                    ?? '';

                if (empty($transportador) && !empty($transportadorCnpj)) {
                    $transportador = $this->nomeParceiroPorDocumento($transportadorCnpj) ?? '';
                }

                $destinadorCnpj = data_get($dados, 'destinador.cpfCnpj')
                    ?? data_get($dados, 'parceiroDestinador.parCnpj')
                    ?? data_get($dados, 'parceiroDestinador.cpfCnpj')
                    ?? data_get($dados, 'cnpjDestinador')
                    ?? '';

                $destinador = data_get($dados, 'destinador.nome')
                    ?? data_get($dados, 'destinador.razaoSocial')
                    ?? data_get($dados, 'destinador.parDescricao')
                    ?? data_get($dados, 'parceiroDestinador.parDescricao')
                    ?? data_get($dados, 'parceiroDestinador.nome')
                    ?? data_get($dados, 'nomeDestinador')
                    ?? data_get($dados, 'destinadorNome')
                    ?? '';

                $placa = data_get($dados, 'manPlacaVeiculo')
                    ?? data_get($dados, 'placaVeiculo')
                    ?? data_get($dados, 'veiculoPlaca')
                    ?? '';

                $motorista = data_get($dados, 'manNomeMotorista')
                    ?? data_get($dados, 'nomeMotorista')
                    ?? data_get($dados, 'motoristaNome')
                    ?? '';

                $dataExpedicao = data_get($dados, 'manDataExpedicao') ?? data_get($dados, 'dataExpedicao') ?? null;

                $residuosApi = data_get($dados, 'listaManifestoResiduos')
                    ?? data_get($dados, 'listaManifestoResiduo')
                    ?? data_get($dados, 'residuos')
                    ?? [];

                $residuos = [];
                if (is_array($residuosApi)) {
                    foreach ($residuosApi as $item) {
                        $codIbama = data_get($item, 'resCodigoIbama')
                            ?? data_get($item, 'residuo.resCodigoIbama')
                            ?? data_get($item, 'codigoIbama')
                            ?? '';

                        if (empty($codIbama)) {
                            continue;
                        }

                        $residuos[] = [
                            'codigoIbama'       => $codIbama,
                            'descricaoResiduo'  => data_get($item, 'resDescricao')
                                ?? data_get($item, 'residuo.resDescricao')
                                    ?? data_get($item, 'descricao')
                                    ?? 'RESÍDUO DECLARADO',
                            'unidadeMedidaNome' => data_get($item, 'uniDescricao')
                                ?? data_get($item, 'unidade.uniDescricao')
                                    ?? data_get($item, 'unidade')
                                    ?? 'TON',
                            'tratamentoNome'    => data_get($item, 'traDescricao')
                                ?? data_get($item, 'tratamento.traDescricao')
                                    ?? 'Conforme MTR',
                            'quantidade'        => data_get($item, 'marQuantidade')
                                ?? data_get($item, 'quantidade')
                                    ?? 0,
                            'codigoGerado'      => data_get($item, 'codigoGerado')
                                ?? data_get($item, 'resCodigo')
                                    ?? null,
                            'uniCodigo'         => data_get($item, 'uniCodigo')
                                ?? data_get($item, 'unidade.uniCodigo')
                                    ?? 3,
                            'traCodigo'         => data_get($item, 'traCodigo')
                                ?? data_get($item, 'tratamento.traCodigo')
                                    ?? 43,
                            'tiaCodigo'         => data_get($item, 'tiaCodigo')
                                ?? data_get($item, 'acondicionamento.tiaCodigo')
                                    ?? 8,
                            'tieCodigo'         => data_get($item, 'tieCodigo')
                                ?? data_get($item, 'estadoFisico.tieCodigo')
                                    ?? 1,
                            'claCodigo'         => data_get($item, 'claCodigo')
                                ?? data_get($item, 'classe.claCodigo')
                                    ?? 43,
                        ];
                    }
                }

                return response()->json([
                    'success' => true,
                    'data' => [
                        'numeroMtr'         => $numeroLimpo,
                        'veiculoPlaca'      => $placa,
                        'motoristaNome'     => $motorista,
                        'geradorCnpj'       => $geradorCnpj,
                        'geradorNome'       => $gerador,
                        'transportadorCnpj' => $transportadorCnpj,
                        'transportadorNome' => $transportador,
                        'destinadorCnpj'    => $destinadorCnpj,
                        'destinadorNome'    => $destinador,
                        'dataExpedicao'     => $dataExpedicao,
                        'residuos'          => $residuos,
                    ]
                ]);
            }

            $mtrLocal = \App\Models\MtrManifesto::with('itens')
                ->where('empresa_id', $this->empresa_id)
                ->where('numero_mtr', $numeroLimpo)
                ->first();

            if ($mtrLocal) {
                $residuos = $mtrLocal->itens->map(function ($item) {
                    return [
                        'codigoIbama'       => $item->cod_ibama,
                        'descricaoResiduo'  => $item->descricao_residuo,
                        'unidadeMedidaNome' => 'TON',
                        'tratamentoNome'    => 'Conforme MTR',
                        'quantidade'        => $item->quantidade,
                        'uniCodigo'         => (int) ($item->unidade_medida ?: 3),
                        'traCodigo'         => (int) ($item->tratamento_id ?: 43),
                        'tiaCodigo'         => (int) ($item->acondicionamento_id ?: 8),
                        'tieCodigo'         => (int) ($item->estado_fisico ?: 1),
                        'claCodigo'         => (int) ($item->classe_residuo ?: 43),
                        'id'                => $item->id
                    ];
                })->toArray();

                return response()->json([
                    'success' => true,
                    'data' => [
                        'numeroMtr'         => $mtrLocal->numero_mtr,
                        'veiculoPlaca'      => $mtrLocal->veiculo_placa,
                        'geradorNome'       => $mtrLocal->gerador_nome,
                        'transportadorNome' => $mtrLocal->transportador_nome,
                        'destinadorNome'    => $mtrLocal->destinador_nome,
                        'residuos'          => $residuos
                    ]
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'numeroMtr'         => $numeroLimpo,
                    'veiculoPlaca'      => '',
                    'motoristaNome'     => '',
                    'geradorNome'       => '',
                    'transportadorNome' => '',
                    'destinadorNome'    => '',
                    'residuos'          => []
                ]
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function replaceItens(MtrManifesto $manifesto, array $residuos): void
    {
        MtrManifestoItem::where('mtr_manifesto_id', $manifesto->id)->delete();

        foreach ($residuos as $residuo) {
            $codigo = trim((string) ($residuo['codigo_ibama'] ?? ''));
            $quantidade = $this->parseQuantidadeMtr($residuo['quantidade'] ?? 0);

            if ($codigo === '' || $quantidade <= 0) {
                continue;
            }

            MtrManifestoItem::create([
                'mtr_manifesto_id' => $manifesto->id,
                'cod_ibama' => $codigo,
                'descricao_residuo' => $residuo['descricao_residuo'] ?? $residuo['descricao'] ?? 'RESÍDUO DECLARADO',
                'quantidade' => $quantidade,
                'unidade_medida' => (string) ($residuo['unidade'] ?? $residuo['unidade_medida'] ?? '2'),
                'estado_fisico' => (int) ($residuo['estado_fisico'] ?? 1),
                'classe_residuo' => (string) ($residuo['classe_residuo'] ?? '43'),
                'acondicionamento_id' => (int) ($residuo['acondicionamento_id'] ?? $residuo['acondicionamento'] ?? 8),
                'tratamento_id' => (int) ($residuo['tratamento_id'] ?? $residuo['tratamento'] ?? 43),
                'numero_onu' => $residuo['numero_onu'] ?? null,
                'classe_risco' => $residuo['classe_risco'] ?? null,
                'nome_embarque' => $residuo['nome_embarque'] ?? null,
                'grupo_embalagem' => $residuo['grupo_embalagem'] ?? null,
                'codigo_interno' => $residuo['codigo_interno'] ?? null,
                'descricao_interna' => $residuo['descricao_interna'] ?? null,
            ]);
        }
    }

    private function clientePorDocumento($documento): ?object
    {
        $doc = preg_replace('/\D+/', '', (string) $documento);
        if ($doc === '') {
            return null;
        }

        return DB::table('clientes')
            ->where('empresa_id', $this->empresa_id)
            ->whereRaw("REPLACE(REPLACE(REPLACE(cpf_cnpj,'.',''),'/',''),'-','') = ?", [$doc])
            ->first();
    }

    private function credencialPadrao(): MtrConfig
    {
        return MtrConfig::where('empresa_id', $this->empresa_id)
            ->where('ativo', true)
            ->orderByDesc('id')
            ->firstOrFail();
    }

    private function credencialDoManifesto(MtrManifesto $mtr): MtrConfig
    {
        $cnpjGeradorLimpo = preg_replace('/\D+/', '', (string) $mtr->gerador_cnpj);

        if ($cnpjGeradorLimpo !== '') {
            $credencial = MtrConfig::where('empresa_id', $this->empresa_id)
                ->where('ativo', true)
                ->whereRaw("REPLACE(REPLACE(REPLACE(cpf_cnpj,'.',''),'/',''),'-','') = ?", [$cnpjGeradorLimpo])
                ->orderByDesc('id')
                ->first();

            if ($credencial) {
                return $credencial;
            }
        }

        return $this->credencialPadrao();
    }

    private function token(MtrConfig $credencial, SinirIemaService $service): string
    {
        return $service->getToken(
            (string) $credencial->cpf_cnpj,
            (string) $credencial->senha,
            (string) $credencial->unidade_id,
            $credencial->cpf_usuario
        );
    }

    private function unidadePorDocumento($documento): ?string
    {
        $doc = preg_replace('/\D+/', '', (string) $documento);

        $config = MtrConfig::where('empresa_id', $this->empresa_id)
            ->where('cpf_cnpj', $doc)
            ->where('ativo', true)
            ->first();

        return $config?->unidade_id;
    }

    private function normalizarTipoOrigem(string $tipo): string
    {
        return match ($tipo) {
            'nfe' => 'nfe',
            'pesagem', 'ticket_pesagem' => 'ticket_pesagem',
            default => 'avulso',
        };
    }

    private function codigoInteiro($value): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        return (int) preg_replace('/\D+/', '', (string) $value);
    }

    private function downloadDocumento($numeroMtr, bool $cdf)
    {
        try {
            $mtr = MtrManifesto::where('empresa_id', $this->empresa_id)
                ->where(function ($q) use ($numeroMtr) {
                    $q->where('id', $numeroMtr)->orWhere('numero_mtr', $numeroMtr);
                })
                ->firstOrFail();

            if (!$mtr->numero_mtr) {
                throw new Exception('MTR ainda não possui número oficial.');
            }

            $credencial = $this->credencialDoManifesto($mtr);
            $service = new SinirIemaService($credencial->orgao, $credencial->ambiente);

            $body = $service->downloadWithToken(
                $this->token($credencial, $service),
                ($cdf ? '/downloadCertificado/' : '/downloadManifesto/') . rawurlencode($mtr->numero_mtr)
            );

            return response($body, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . ($cdf ? 'CDF_' : 'MTR_') . $mtr->numero_mtr . '.pdf"',
            ]);
        } catch (\Throwable $e) {
            return redirect()->back()
                ->with('erro', ($cdf ? 'Erro ao baixar CDF: ' : 'Erro ao baixar PDF: ') . $e->getMessage());
        }
    }

    private function enviarMensagemZapMtr(int $empresaId, string $telefone, string $mensagem): void
    {
        try {
            $numero = explode('@', $telefone)[0];
            $resultado = $this->whatsapputil->sendMessage($numero, $mensagem, $empresaId);
            $this->assertWhatsAppSendSucceeded($resultado);
            $retorno = json_decode($resultado, true)['text_response'];

            Log::info('MTR WhatsApp: tentativa de envio Connect|API', [
                'empresa_id' => $empresaId,
                'numero' => $numero,
                'success' => $retorno['success'] ?? false,
                'status' => $retorno['status'] ?? null,
                'erro' => $retorno['error'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('MTR WhatsApp: erro ao enviar mensagem: ' . $e->getMessage());
        }
    }

    public function gerarMtrDeMovimentacao(int $movimentacaoId, ?int $destinoId = null)
    {
        $this->ultimoErroMtr = null;
        DB::beginTransaction();

        try {
            $movimentacao = \App\Models\MovimentacaoVeiculo::with(['veiculo', 'motorista'])->find($movimentacaoId);

            if (!$movimentacao) {
                $this->ultimoErroMtr = "Movimentação #{$movimentacaoId} não encontrada.";
                DB::rollBack();
                return null;
            }

            $empresaId = (int) $movimentacao->empresa_id;
            $destinos = $movimentacao->destinos ?? collect();

            $destino = null;
            if ($destinos->count() > 0) {
                $destino = $destinoId
                    ? $destinos->firstWhere('id', $destinoId)
                    : ($destinos->where('is_aterro', 0)->whereNotNull('produto_residuo_id')->first()
                        ?: $destinos->whereNotNull('produto_residuo_id')->first()
                            ?: $destinos->where('is_aterro', 0)->first()
                                ?: $destinos->first());
            }

            $destinoIdRef = $destino ? $destino->id : 0;
            $codigoBusca = 'MTR-MOV-' . $movimentacaoId . '-' . $destinoIdRef;

            $manifestoExistente = \App\Models\MtrManifesto::where('empresa_id', $empresaId)
                ->where(function ($q) use ($codigoBusca, $movimentacaoId) {
                    $q->where('seu_codigo', $codigoBusca)
                        ->orWhere('seu_codigo', 'like', "MTR-MOV-{$movimentacaoId}-%");
                })
                ->orderByDesc('id')
                ->first();

            if ($manifestoExistente) {
                DB::commit();
                return $manifestoExistente;
            }

            $gerador = \App\Models\MtrConfig::where('empresa_id', $empresaId)
                ->where('perfil', 'Gerador')
                ->where('ativo', true)
                ->orderByDesc('id')
                ->first();

            if (!$gerador) {
                $gerador = \App\Models\MtrConfig::where('empresa_id', $empresaId)
                    ->where('ativo', true)
                    ->orderByDesc('id')
                    ->first();
            }

            if (!$gerador) {
                $this->ultimoErroMtr = "Nenhuma configuração de credencial MTR ativa para a empresa ID {$empresaId}.";
                DB::rollBack();
                return null;
            }

            $paradaAterro = $destinos->firstWhere('is_aterro', 1);
            $clienteDest = null;

            if ($paradaAterro && !empty($paradaAterro->cliente_id)) {
                $clienteDest = DB::table('clientes')->where('id', $paradaAterro->cliente_id)->first();
            } elseif ($paradaAterro && !empty($paradaAterro->fornecedor_id)) {
                $clienteDest = DB::table('fornecedores')->where('id', $paradaAterro->fornecedor_id)->first();
            } elseif ($destino && !empty($destino->cliente_id)) {
                $clienteDest = DB::table('clientes')->where('id', $destino->cliente_id)->first();
            } elseif ($destino && !empty($destino->fornecedor_id)) {
                $clienteDest = DB::table('fornecedores')->where('id', $destino->fornecedor_id)->first();
            } elseif (!empty($movimentacao->cliente_id)) {
                $clienteDest = DB::table('clientes')->where('id', $movimentacao->cliente_id)->first();
            }

            $docDestinador = $clienteDest->cpf_cnpj ?? $clienteDest->cnpj ?? $gerador->cpf_cnpj ?? '';
            $destinadorCnpj = preg_replace('/\D+/', '', (string) $docDestinador) ?: preg_replace('/\D+/', '', (string) $gerador->cpf_cnpj);
            $destinadorNome = $clienteDest->razao_social ?? $clienteDest->nome ?? ($gerador->descricao ?: 'DESTINADOR');

            $produtoId = $destino->produto_residuo_id ?? null;
            $produtoResiduo = $produtoId
                ? DB::table('produtos')->where('id', $produtoId)->first()
                : null;

            $depara = null;
            if ($produtoId) {
                try {
                    $depara = \App\Models\MtrDeparaResiduo::where('empresa_id', $empresaId)->where('produto_id', $produtoId)->first()
                        ?? \App\Models\MtrDeparaResiduo::where('empresa_id', $empresaId)->where('sub_categoria_id', $produtoResiduo->sub_categoria_id)->whereNotNull('sub_categoria_id')->first()
                        ?? \App\Models\MtrDeparaResiduo::where('empresa_id', $empresaId)->where('categoria_id', $produtoResiduo->categoria_id)->whereNotNull('categoria_id')->first()
                        ?? \App\Models\MtrDeparaResiduo::where('empresa_id', $empresaId)->where('ncm', $produtoResiduo->NCM)->whereNotNull('ncm')->first();
                } catch (\Throwable $e) {
                    $depara = null;
                }
            }

            $usuarioValidoId = $this->usuario_id
                ?: DB::table('usuarios')->where('empresa_id', $empresaId)->value('id')
                    ?: DB::table('usuarios')->value('id');

            $placaFormatada = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', (string) ($movimentacao->veiculo->placa ?? '')));

            $manifesto = \App\Models\MtrManifesto::create([
                'empresa_id' => $empresaId,
                'usuario_id' => $usuarioValidoId,
                'orgao' => $gerador->orgao ?: 'SINIR',
                'tipo_origem' => 'avulso',
                'origem_id' => $destino ? $destino->id : null,
                'seu_codigo' => $codigoBusca,
                'data_expedicao' => \Carbon\Carbon::now(),
                'gerador_cnpj' => preg_replace('/\D+/', '', (string) $gerador->cpf_cnpj),
                'gerador_nome' => $gerador->descricao ?: 'GERADOR',
                'transportador_cnpj' => preg_replace('/\D+/', '', (string) $gerador->cpf_cnpj),
                'transportador_nome' => $gerador->descricao ?: 'TRANSPORTADOR',
                'destinador_cnpj' => $destinadorCnpj,
                'destinador_nome' => $destinadorNome,
                'veiculo_placa' => $placaFormatada ?: 'AAA0000',
                'motorista_nome' => $movimentacao->motorista->nome ?? 'NÃO INFORMADO',
                'observacao' => "MTR gerado automaticamente pela Agenda #{$movimentacaoId}",
                'status' => 'rascunho',
            ]);

            $itensColetados = $destino
                ? \App\Models\MovimentacaoDestinoItem::where('movimentacao_destino_id', $destino->id)->get()
                : collect();

            if ($itensColetados->count() > 0) {
                foreach ($itensColetados as $it) {
                    $prodItem = DB::table('produtos')->where('id', $it->produto_id)->first();
                    $deparaItem = null;

                    if ($it->produto_id) {
                        try {
                            $deparaItem = \App\Models\MtrDeparaResiduo::where('empresa_id', $empresaId)->where('produto_id', $it->produto_id)->first()
                                ?? \App\Models\MtrDeparaResiduo::where('empresa_id', $empresaId)->where('sub_categoria_id', $prodItem->sub_categoria_id)->whereNotNull('sub_categoria_id')->first()
                                ?? \App\Models\MtrDeparaResiduo::where('empresa_id', $empresaId)->where('categoria_id', $prodItem->categoria_id)->whereNotNull('categoria_id')->first()
                                ?? \App\Models\MtrDeparaResiduo::where('empresa_id', $empresaId)->where('ncm', $prodItem->NCM)->whereNotNull('ncm')->first();
                        } catch (\Throwable $e) {
                            $deparaItem = null;
                        }
                    }

                    $qtdItem = (float) (($it->quantidade ?? 0) > 0 ? $it->quantidade : 1);

                    \App\Models\MtrManifestoItem::create([
                        'empresa_id'          => $empresaId,
                        'mtr_manifesto_id'    => $manifesto->id,
                        'usuario_id'          => $usuarioValidoId,
                        'cod_ibama'           => ($deparaItem && !empty($deparaItem->cod_ibama)) ? trim($deparaItem->cod_ibama) : '200301',
                        'descricao_residuo'   => $deparaItem->descricao_residuo ?? ($prodItem->nome ?? 'RESÍDUO DA COLETA'),
                        'quantidade'          => $qtdItem,
                        'unidade_medida'      => (string) (($deparaItem && !empty($deparaItem->unidade_medida)) ? $deparaItem->unidade_medida : '3'),
                        'estado_fisico'       => (int) (($deparaItem && !empty($deparaItem->estado_fisico)) ? $deparaItem->estado_fisico : 1),
                        'classe_residuo'      => (string) (($deparaItem && !empty($deparaItem->classe_residuo)) ? $deparaItem->classe_residuo : '43'),
                        'acondicionamento_id' => (int) (($deparaItem && !empty($deparaItem->acondicionamento_id)) ? $deparaItem->acondicionamento_id : 8),
                        'tratamento_id'       => (int) (($deparaItem && !empty($deparaItem->tratamento_id)) ? $deparaItem->tratamento_id : 43),
                        'numero_onu'          => $deparaItem->numero_onu ?? null,
                        'classe_risco'        => $deparaItem->classe_risco ?? null,
                        'nome_embarque'       => $deparaItem->nome_embarque ?? null,
                        'grupo_embalagem'     => $deparaItem->grupo_embalagem ?? null,
                    ]);
                }
            } else {
                $qtdResiduo = (float) (($destino->quantidade_residuo ?? 0) > 0 ? $destino->quantidade_residuo : 1);

                \App\Models\MtrManifestoItem::create([
                    'empresa_id'          => $empresaId,
                    'mtr_manifesto_id'    => $manifesto->id,
                    'usuario_id'          => $usuarioValidoId,
                    'cod_ibama'           => ($depara && !empty($depara->cod_ibama)) ? trim($depara->cod_ibama) : '200301',
                    'descricao_residuo'   => $depara->descricao_residuo ?? ($produtoResiduo->nome ?? 'RESÍDUO DA COLETA'),
                    'quantidade'          => $qtdResiduo,
                    'unidade_medida'      => (string) (($depara && !empty($depara->unidade_medida)) ? $depara->unidade_medida : '3'),
                    'estado_fisico'       => (int) (($depara && !empty($depara->estado_fisico)) ? $depara->estado_fisico : 1),
                    'classe_residuo'      => (string) (($depara && !empty($depara->classe_residuo)) ? $depara->classe_residuo : '43'),
                    'acondicionamento_id' => (int) (($depara && !empty($depara->acondicionamento_id)) ? $depara->acondicionamento_id : 8),
                    'tratamento_id'       => (int) (($depara && !empty($depara->tratamento_id)) ? $depara->tratamento_id : 43),
                    'numero_onu'          => $depara->numero_onu ?? null,
                    'classe_risco'        => $depara->classe_risco ?? null,
                    'nome_embarque'       => $depara->nome_embarque ?? null,
                    'grupo_embalagem'     => $depara->grupo_embalagem ?? null,
                ]);
            }

            DB::commit();
            return $manifesto;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->ultimoErroMtr = $e->getMessage();
            Log::error("Erro em gerarMtrDeMovimentacao: " . $e->getMessage());
            return null;
        }
    }

    public function notificarResponsavelEmissaoMtr($movimentacaoId, $destinoId)
    {
        $movimentacao = \App\Models\MovimentacaoVeiculo::with(['veiculo', 'motorista'])->find($movimentacaoId);
        $destino = $movimentacao?->destinos()->find($destinoId);

        if (!$movimentacao || !$destino) {
            return;
        }

        $empresaId = (int) $movimentacao->empresa_id;
        $this->empresa_id = $empresaId;

        $manifesto = $this->gerarMtrDeMovimentacao((int) $movimentacaoId, (int) $destinoId);
        if (!$manifesto) return;

        $responsavel = \App\Models\Funcionario::where('empresa_id', $empresaId)
            ->where('permissao_mtr_whatsapp', 1)
            ->whereNotNull('celular')
            ->first();

        if (!$responsavel) {
            $responsavel = \App\Models\Funcionario::where('empresa_id', $empresaId)
                ->whereNotNull('celular')
                ->first();
        }

        if (!$responsavel) return;

        $produtoResiduo = $destino->produto_residuo_id
            ? DB::table('produtos')->where('id', $destino->produto_residuo_id)->first()
            : null;

        $telefoneResp = preg_replace('/\D+/', '', $responsavel->celular ?: $responsavel->telefone);
        if (!str_starts_with($telefoneResp, '55')) $telefoneResp = '55' . $telefoneResp;

        $msg = "♻️ *COLETA REGISTRADA PELO MOTORISTA*\n\n";
        $msg .= "🚛 *Veículo:* " . ($movimentacao->veiculo->placa ?? 'N/A') . "\n";
        $msg .= "👤 *Motorista:* " . ($movimentacao->motorista->nome ?? 'N/A') . "\n";
        $msg .= "📦 *Resíduo:* " . ($produtoResiduo->nome ?? 'Resíduo') . " (" . $destino->quantidade_residuo . " " . $destino->unidade_residuo . ")\n\n";
        $msg .= "O rascunho do MTR foi preparado.\n";
        $msg .= "Para conferir os dados e transmitir ao SINIR, envie:\n";
        $msg .= "👉 *EMITIR MTR AGENDA -{$movimentacao->id}*";

        $this->enviarMensagemZapMtr($empresaId, $telefoneResp, $msg);
    }

    private function localizarOuCriarManifestoPorAgenda(int $idInformado, int $empresaFallback = 1): ?MtrManifesto
    {
        $manifesto = MtrManifesto::with(['itens'])
            ->where('seu_codigo', 'like', "MTR-MOV-{$idInformado}-%")
            ->orWhere('id', $idInformado)
            ->orderByDesc('id')
            ->first();

        if ($manifesto) {
            $this->empresa_id = $manifesto->empresa_id;
            return $manifesto;
        }

        $movimentacao = \App\Models\MovimentacaoVeiculo::find($idInformado);

        if ($movimentacao) {
            $this->empresa_id = $movimentacao->empresa_id;
            $novo = $this->gerarMtrDeMovimentacao($movimentacao->id, null);
            if ($novo) {
                return MtrManifesto::with(['itens'])->find($novo->id);
            }
        }

        $this->empresa_id = $empresaFallback;
        return null;
    }

    public function enviarResumoParaConfirmacao($funcionario, $telefone, $agendaId)
    {
        try {
            $movimentacao = \App\Models\MovimentacaoVeiculo::find($agendaId);
            $manifestoBusca = MtrManifesto::find($agendaId);
            $empresaId = (int) ($movimentacao->empresa_id ?? $manifestoBusca->empresa_id ?? $funcionario->empresa_id ?? 1);

            $ultimos8 = substr(preg_replace('/\D/', '', $telefone), -8);
            $temPermissao = !empty($funcionario->permissao_mtr_whatsapp)
                || \App\Models\Funcionario::where('permissao_mtr_whatsapp', 1)
                    ->where(function ($q) use ($ultimos8) {
                        $q->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(celular, '(', ''), ')', ''), '-', ''), ' ', '') LIKE ?", ["%{$ultimos8}%"])
                            ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(telefone, '(', ''), ')', ''), '-', ''), ' ', '') LIKE ?", ["%{$ultimos8}%"]);
                    })->exists();

            if (!$temPermissao) {
                $this->enviarMensagemZapMtr(
                    $empresaId,
                    $telefone,
                    "⛔ *Acesso Negado*\nVocê não possui permissão ativa para emitir MTR via WhatsApp."
                );
                return response()->json(['status' => 'sem_permissao_mtr']);
            }

            $manifesto = $this->localizarOuCriarManifestoPorAgenda((int) $agendaId, $empresaId);

            if (!$manifesto) {
                $detalheErro = $this->ultimoErroMtr ?: "Verifique os dados da Agenda #{$agendaId}.";
                $this->enviarMensagemZapMtr(
                    $empresaId,
                    $telefone,
                    "❌ *Não foi possível gerar o rascunho de MTR*\nMotivo: {$detalheErro}"
                );
                return response()->json(['status' => 'manifesto_nao_encontrado', 'erro' => $detalheErro]);
            }

            $empresaId = (int) $manifesto->empresa_id;

            $resumo = "📋 *RESUMO DO MTR PARA CONFERÊNCIA*\n";
            $resumo .= "----------------------------------------\n";
            $resumo .= "📄 *Rascunho MTR:* #{$manifesto->id} ({$manifesto->seu_codigo})\n";
            $resumo .= "🏢 *Gerador:* {$manifesto->gerador_nome}\n";
            $resumo .= "🏭 *Destinador:* {$manifesto->destinador_nome}\n";
            $resumo .= "🚛 *Placa do Veículo:* " . ($manifesto->veiculo_placa ?: 'N/INF') . "\n";
            $resumo .= "👤 *Motorista:* " . ($manifesto->motorista_nome ?: 'N/INF') . "\n";
            $resumo .= "----------------------------------------\n";
            $resumo .= "📦 *Resíduos e Quantidades:*\n";

            foreach ($manifesto->itens as $item) {
                $resumo .= "• {$item->descricao_residuo} — *{$item->quantidade}* (IBAMA: {$item->cod_ibama})\n";
            }

            $resumo .= "----------------------------------------\n";
            $resumo .= "✅ *Os dados estão corretos?*\n";
            $resumo .= "Para transmitir oficialmente ao SINIR/IEMA, responda:\n";
            $resumo .= "👉 *CONFIRMAR MTR -{$manifesto->id}*";

            $this->enviarMensagemZapMtr($empresaId, $telefone, $resumo);

            return response()->json(['status' => 'resumo_enviado', 'manifesto_id' => $manifesto->id]);
        } catch (\Throwable $e) {
            Log::error("Erro ao enviar resumo MTR WhatsApp: " . $e->getMessage());
            return response()->json(['status' => 'erro_resumo', 'message' => $e->getMessage()], 500);
        }
    }

    public function processarEmissaoPorWhatsappAgenda($funcionario, $telefone, $agendaId)
    {
        try {
            $manifesto = $this->localizarOuCriarManifestoPorAgenda((int) $agendaId, (int) ($funcionario->empresa_id ?? 1));

            if (!$manifesto) {
                $this->enviarMensagemZapMtr(
                    (int) ($funcionario->empresa_id ?? 1),
                    $telefone,
                    "❌ Manifesto MTR ID -{$agendaId} não foi encontrado."
                );
                return response()->json(['status' => 'manifesto_nao_encontrado']);
            }

            $empresaId = (int) $manifesto->empresa_id;

            $this->enviarMensagemZapMtr(
                $empresaId,
                $telefone,
                "🔄 *Transmitindo MTR #{$manifesto->id}* para o SINIR/IEMA, aguarde..."
            );

            $requestFalso = new Request();
            $this->empresa_id = $empresaId;
            $this->usuario_id = $manifesto->usuario_id ?: 1;

            $this->transmitir($requestFalso, $manifesto->id);

            $manifesto->refresh();

            if ($manifesto->status === 'transmitido' && !empty($manifesto->numero_mtr)) {
                $msgSucesso = "✅ *MTR TRANSMITIDO COM SUCESSO!*\n\n";
                $msgSucesso .= "📄 *Nº MTR Oficial:* {$manifesto->numero_mtr}\n";
                $msgSucesso .= "🏢 *Gerador:* {$manifesto->gerador_nome}\n";
                $msgSucesso .= "🏭 *Destinador:* {$manifesto->destinador_nome}";

                $this->enviarMensagemZapMtr($empresaId, $telefone, $msgSucesso);

                try {
                    $credencial = $this->credencialDoManifesto($manifesto);
                    $service = new \App\Services\SinirIemaService($credencial->orgao, $credencial->ambiente);
                    $pdfContent = $service->downloadWithToken(
                        $this->token($credencial, $service),
                        '/downloadManifesto/' . rawurlencode($manifesto->numero_mtr)
                    );

                    if (!empty($pdfContent)) {
                        $nomePdfLocal = 'MTR_' . $manifesto->numero_mtr . '.pdf';
                        $caminhoTemp = storage_path('app/public/' . $nomePdfLocal);
                        file_put_contents($caminhoTemp, $pdfContent);

                        $this->whatsapputil->sendMedia(
                            $empresaId,
                            $telefone,
                            $caminhoTemp,
                            "📄 Documento oficial do MTR nº {$manifesto->numero_mtr}"
                        );
                    }
                } catch (\Throwable $pdfErr) {
                    Log::warning("MTR WhatsApp: Não foi possível anexar o PDF automaticamente: " . $pdfErr->getMessage());
                    $linkPdf = route('mtr.emissao.pdf', $manifesto->numero_mtr);
                    $this->enviarMensagemZapMtr($empresaId, $telefone, "🔗 *Link do PDF:* {$linkPdf}");
                }
            } else {
                $this->enviarMensagemZapMtr(
                    $empresaId,
                    $telefone,
                    "❌ *Falha na transmissão do MTR*\nMotivo: " . ($manifesto->mensagem_retorno ?? 'Verifique as credenciais e unidades MTR.')
                );
            }

            return response()->json(['status' => 'mtr_processado_whatsapp']);
        } catch (\Throwable $e) {
            Log::error("Erro no processamento MTR WhatsApp: " . $e->getMessage());

            $this->enviarMensagemZapMtr(
                (int) ($this->empresa_id ?: ($funcionario->empresa_id ?? 1)),
                $telefone,
                "❌ Erro interno ao processar MTR: " . $e->getMessage()
            );

            return response()->json(['status' => 'erro_interno', 'message' => $e->getMessage()], 500);
        }
    }
}
