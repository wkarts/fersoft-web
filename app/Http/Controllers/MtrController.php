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
                // A view histórica possui o estado visual "recebido", enquanto
                // o schema conciliado preserva o enum anterior. Mantemos o
                // banco em "transmitido" e reconhecemos o recebimento pelo
                // retorno da integração.
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
        }

        $manifestos = $query->orderByDesc('id')
            ->paginate(15)
            ->appends($request->all());

        $manifestos->getCollection()->transform(function (MtrManifesto $manifesto) {
            if (
                $manifesto->status === 'transmitido'
                && stripos((string) $manifesto->mensagem_retorno, 'recebid') !== false
            ) {
                $manifesto->setAttribute('status', 'recebido');
            }

            return $manifesto;
        });

        $vendasImportacao = DB::table('vendas')
            ->leftJoin('clientes', 'clientes.id', '=', 'vendas.cliente_id')
            ->where('vendas.empresa_id', $this->empresa_id)
            ->whereNotIn('vendas.id', function ($q) {
                $q->select('origem_id')
                    ->from('mtr_manifestos')
                    ->where('empresa_id', $this->empresa_id)
                    ->where('tipo_origem', 'nfe')
                    ->whereNotNull('origem_id');
            })
            ->select('vendas.*', 'clientes.razao_social as cliente_nome')
            ->orderByDesc('vendas.id')
            ->limit(50)
            ->get();

        $ticketsImportacao = DB::table('pesagens')
            ->leftJoin('clientes', 'clientes.id', '=', 'pesagens.cliente_id')
            ->where('pesagens.empresa_id', $this->empresa_id)
            ->where(function ($q) {
                $q->whereNotNull('pesagens.peso_final')
                    ->orWhere('pesagens.peso_final', '>', 0)
                    ->orWhereIn('pesagens.status', ['FINALIZADA', 'concluído']);
            })
            ->whereNotIn('pesagens.id', function ($q) {
                $q->select('origem_id')
                    ->from('mtr_manifestos')
                    ->where('empresa_id', $this->empresa_id)
                    ->where('tipo_origem', 'ticket_pesagem')
                    ->whereNotNull('origem_id');
            })
            ->select(
                'pesagens.*',
                'clientes.razao_social as cliente_nome',
                DB::raw('COALESCE(pesagens.peso_final, pesagens.peso_liquido_bruto, pesagens.peso) as peso_calculado')
            )
            ->orderByDesc('pesagens.id')
            ->limit(50)
            ->get();

        return view('mtr.emissao.index', [
            'manifestos' => $manifestos,
            'vendasImportacao' => $vendasImportacao,
            'ticketsImportacao' => $ticketsImportacao,
            'title' => 'Central de Emissão de MTR',
        ]);
    }

    public function createAvulso()
    {
        return view('mtr.emissao.create', [
            'unidadesGeradoras' => MtrConfig::where('empresa_id', $this->empresa_id)
                ->where('perfil', 'Gerador')
                ->where('ativo', true)
                ->get(),
            'clientes' => DB::table('clientes')
                ->where('empresa_id', $this->empresa_id)
                ->select('id', 'razao_social', 'cpf_cnpj')
                ->orderBy('razao_social')
                ->get(),
            'title' => 'Emissão de MTR',
            'origem' => 'avulso',
        ]);
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
        $depara = null;
        $produtoNome = 'Diversos/Sucatas';

        if ($ticket && $ticket->produto_id) {
            $produto = DB::table('produtos')
                ->where('id', $ticket->produto_id)
                ->where('empresa_id', $this->empresa_id)
                ->first();

            if ($produto) {
                $produtoNome = $produto->nome;
                $depara = MtrDeparaResiduo::query()
                    ->where('empresa_id', $this->empresa_id)
                    ->where(function ($q) use ($produto) {
                        $q->where('produto_id', $produto->id)
                            ->orWhere('sub_categoria_id', $produto->sub_categoria_id)
                            ->orWhere('ncm', $produto->NCM);
                    })
                    ->first();
            }
        }

        $residuos = [[
            'codigo_ibama' => $depara->cod_ibama ?? '',
            'quantidade' => $pesoLiquido,
            'unidade' => $depara->unidade_medida ?? 2,
            'estado_fisico' => $depara->estado_fisico ?? 1,
            'classe_residuo' => $depara->classe_residuo ?? 42,
            'acondicionamento' => $depara->acondicionamento_id ?? 8,
            'tratamento' => $depara->tratamento_id ?? 43,
        ]];

        $cliente = $pesagem->cliente_id
            ? DB::table('clientes')
                ->where('id', $pesagem->cliente_id)
                ->where('empresa_id', $this->empresa_id)
                ->first()
            : null;

        $nfeNum = $pesagem->nf_numero
            ?? $pesagem->nfe
            ?? $pesagem->numero_nfe
            ?? '';
        $obs = "Produto: {$produtoNome} | Ticket Pesagem: #{$pesagemId}";
        if ($nfeNum !== '') {
            $obs .= " | NF-e: {$nfeNum}";
        }

        return redirect()->route('mtr.emissao.create.avulso')
            ->with('destinador_cnpj', $cliente->cpf_cnpj ?? null)
            ->with('placa_veiculo', $pesagem->placa_veiculo ?? '')
            ->with('motorista_nome', $pesagem->motorista_nome ?? '')
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

            if (!$produto) {
                continue;
            }

            $nomes[] = $produto->nome;

            $depara = MtrDeparaResiduo::query()
                ->where('empresa_id', $this->empresa_id)
                ->where(function ($q) use ($produto) {
                    $q->where('produto_id', $produto->id)
                        ->orWhere('sub_categoria_id', $produto->sub_categoria_id)
                        ->orWhere('ncm', $produto->NCM);
                })
                ->first();

            $residuos[] = [
                'codigo_ibama' => $depara->cod_ibama ?? '',
                'quantidade' => $item->quantidade,
                'unidade' => $depara->unidade_medida ?? 2,
                'estado_fisico' => $depara->estado_fisico ?? 1,
                'classe_residuo' => $depara->classe_residuo ?? 42,
                'acondicionamento' => $depara->acondicionamento_id ?? 8,
                'tratamento' => $depara->tratamento_id ?? 43,
            ];
        }

        $cliente = DB::table('clientes')
            ->where('id', $venda->cliente_id)
            ->where('empresa_id', $this->empresa_id)
            ->first();

        $nfeNum = $venda->numero_nfe ?? $venda->nfe ?? $venda->id;
        $obs = 'NF-e: ' . $nfeNum . ' | Produtos: ' . implode(', ', array_unique($nomes));

        return redirect()->route('mtr.emissao.create.avulso')
            ->with('destinador_cnpj', $cliente->cpf_cnpj ?? null)
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

        return view('mtr.emissao.edit', [
            'mtr' => $mtr,
            'itens' => $mtr->itens,
            'unidadesGeradoras' => MtrConfig::where('empresa_id', $this->empresa_id)
                ->where('perfil', 'Gerador')->where('ativo', true)->get(),
            'clientes' => DB::table('clientes')
                ->where('empresa_id', $this->empresa_id)
                ->orderBy('razao_social')->get(),
            'title' => 'Editar Rascunho MTR',
        ]);
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

                $residuos[] = [
                    'resCodigoIbama' => preg_replace('/\D+/', '', (string) $item->cod_ibama),
                    'marQuantidade' => (float) $item->quantidade,
                    'uniCodigo' => $unidade,
                    'tiaCodigo' => $acondicionamento,
                    'traCodigo' => $tratamento,
                    'tieCodigo' => $estado,
                    'claCodigo' => $classe,
                ];
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

            $credencial = $this->credencialDoManifesto($mtr);
            $service = new SinirIemaService($credencial->orgao, $credencial->ambiente);
            $dados = $service->requestWithToken(
                $this->token($credencial, $service),
                'GET',
                '/consultarManifesto/' . rawurlencode($mtr->numero_mtr)
            );

            $situacao = (string) ($dados['situacao'] ?? $dados['status'] ?? 'Emitido');

            if (stripos($situacao, 'Cancelado') !== false) {
                $mtr->status = 'cancelado';
            } else {
                // O schema histórico não possui enum "recebido".
                $mtr->status = 'transmitido';
            }

            $mtr->mensagem_retorno = mb_substr($situacao, 0, 2000);
            $mtr->save();

            return response()->json([
                'success' => true,
                'status' => $mtr->status,
                'situacao_descricao' => $situacao,
                'message' => 'Status atualizado no portal MTR.',
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

            $mensagem = "📄 *MANIFESTO DE TRANSPORTE DE RESÍDUOS (MTR)*\n"
                . "----------------------------------------\n"
                . "*Nº MTR Oficial:* " . ($mtr->numero_mtr ?: 'Pendente') . "\n"
                . "*Gerador:* {$mtr->gerador_nome}\n"
                . "*Destinador:* {$mtr->destinador_nome}\n"
                . "*Placa Veículo:* " . ($mtr->veiculo_placa ?: '-') . "\n\n"
                . "🔗 *MTR Oficial:*\n"
                . route('mtr.emissao.pdf', $mtr->numero_mtr ?: $mtr->id);

            $resultado = $this->whatsapputil->sendMessage(
                $numero,
                $mensagem,
                (int) $this->empresa_id
            );

            $providerResponse = json_decode($resultado, true);
            if (!is_array($providerResponse) || !($providerResponse['success'] ?? false)) {
                throw new \RuntimeException(
                    is_array($providerResponse)
                        ? ($providerResponse['message'] ?? 'Falha ao enviar MTR via WhatsApp.')
                        : 'Resposta inválida ao enviar MTR via WhatsApp.'
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'MTR enviado via WhatsApp com sucesso!',
                'provider_response' => $providerResponse,
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

        // Filtros de Data
        $dtInicioTransp = $request->input('dt_inicio_transp', date('Y-m-d', strtotime('-30 days')));
        $dtFimTransp    = $request->input('dt_fim_transp', date('Y-m-d'));

        $dtInicioDest   = $request->input('dt_inicio_dest', date('Y-m-d', strtotime('-30 days')));
        $dtFimDest      = $request->input('dt_fim_dest', date('Y-m-d'));

        // 1. MTRs onde a empresa atuou como TRANSPORTADOR (Consulta no Banco Local)
        $queryTransp = MtrManifesto::where('empresa_id', $this->empresa_id)
            ->where(function ($q) use ($cnpjEmpresa) {
                if ($cnpjEmpresa !== '') {
                    $q->where('transportador_cnpj', 'like', "%{$cnpjEmpresa}%");
                }
                $q->orWhere('tipo_origem', 'transporte');
            })
            ->whereBetween('data_expedicao', [$dtInicioTransp . ' 00:00:00', $dtFimTransp . ' 23:59:59']);

        if ($request->filled('mtr_transp')) {
            $queryTransp->where('numero_mtr', 'like', '%' . trim((string) $request->mtr_transp) . '%');
        }

        $mtrsTransportador = $queryTransp->orderByDesc('id')->get()->map(function ($item) {
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

        // 2. MTRs onde a empresa atuou como DESTINADOR / RECEBEDOR (Consulta no Banco Local)
        $queryDest = MtrManifesto::where('empresa_id', $this->empresa_id)
            ->where(function ($q) use ($cnpjEmpresa) {
                if ($cnpjEmpresa !== '') {
                    $q->where('destinador_cnpj', 'like', "%{$cnpjEmpresa}%");
                }
                $q->orWhere('status', 'recebido')
                    ->orWhere('mensagem_retorno', 'like', '%recebid%');
            })
            ->whereBetween('data_expedicao', [$dtInicioDest . ' 00:00:00', $dtFimDest . ' 23:59:59']);

        if ($request->filled('mtr_dest')) {
            $queryDest->where('numero_mtr', 'like', '%' . trim((string) $request->mtr_dest) . '%');
        }

        $mtrsDestinador = $queryDest->orderByDesc('id')->get()->map(function ($item) {
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

        return view('mtr.recepcao.index', [
            'mtrsTransportador' => $mtrsTransportador,
            'mtrsDestinador'    => $mtrsDestinador,
            'dtInicio'          => $dtInicioDest,
            'dtFim'             => $dtFimDest,
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

            // 1. MONTAGEM DO PAYLOAD NO FORMATO PADRÃO EXIGIDO PELA API DO SINIR
            // Captura os dados informados na tela (placa e motorista)
            $placaVeiculo = (string) $request->input('placa', '');
            $nomeMotorista = (string) $request->input('motorista', '');

            // Monta o payload garantindo a chave exata exigida pelo validador do SINIR
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
                    // Corrige o corte de casas decimais do PHP (troca vírgula por ponto)
                    $qtdRecebida = (float) str_replace(',', '.', (string) ($residuo['quantidadeRecebida'] ?? '0'));
                    $qtdEmitida  = (float) str_replace(',', '.', (string) ($residuo['quantidade'] ?? $residuo['quantidadeRecebida'] ?? '0'));

                    $cod = preg_replace('/\D+/', '', (string) ($residuo['cod_ibama'] ?? $residuo['codigo_ibama'] ?? ''));

                    if ($qtdRecebida > 0 && $cod !== '') {
                        $itemPayload = [
                            'resCodigoIbama' => $cod,
                            'marQuantidade' => $qtdEmitida, // Chave OBRIGATÓRIA para o SINIR encontrar o item original
                            'marQuantidadeRecebida' => $qtdRecebida,
                            'uniCodigo' => (int) ($residuo['unidade_medida'] ?? 3),
                            'traCodigo' => (int) ($residuo['tratamento_id'] ?? 43),
                            'tiaCodigo' => (int) ($residuo['tratamento_id'] ?? 43),
                            'tieCodigo' => (int) ($residuo['estado_fisico'] ?? 1),
                            'claCodigo' => (int) ($residuo['classe_residuo'] ?? 43)
                        ];

                        // Se tivermos o ID interno da base do governo, incluímos também
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
                ? 'https://admin.sinir.gov.br/api'
                : 'https://admin-homologacao.sinir.gov.br/api';

            if ($credencial->orgao === 'IEMA') {
                $baseUrl = ($credencial->ambiente === 'producao')
                    ? 'https://mtr.iema.es.gov.br/api'
                    : 'https://mtr-homologacao.iema.es.gov.br/api';
            }

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
            $jaRecebidoNoGoverno = false;

            // Verifica se houve erro, MAS se o erro for "já recebido", perdoa o erro para salvar localmente
            if ($response->failed() || (is_array($jsonResponse) && (isset($jsonResponse['erro']) && $jsonResponse['erro'] == true || isset($jsonResponse['erros'])))) {
                $detalhesErro = json_encode($jsonResponse);

                // Checa se a mensagem do governo indica que já foi recebido/baixado
                if (stripos($detalhesErro, 'já recebido') !== false || stripos($detalhesErro, 'ja recebido') !== false || stripos($detalhesErro, 'situacao invalida para recebimento') !== false) {
                    $jaRecebidoNoGoverno = true; // Ignora o erro da API e força a gravação local
                } else {
                    throw new \Exception("Recusado pelo SINIR. Detalhes: " . $detalhesErro);
                }
            }

            // 5. GRAVA E CONSOLIDA NO BANCO DE DADOS LOCAL DO FERSOFT ERP
            DB::beginTransaction();

            // Captura os nomes reais enviados pelos campos ocultos da view
            $geradorNomeForm = (string) $request->input('gerador_nome');
            if (empty($geradorNomeForm)) {
                $geradorNomeForm = 'GERADOR EXTERNO (NOME NÃO INFORMADO)';
            }

            $transportadorNomeForm = (string) $request->input('transportador_nome');
            if (empty($transportadorNomeForm)) {
                $transportadorNomeForm = 'TRANSPORTADOR (NOME NÃO INFORMADO)';
            }

            $mtr = \App\Models\MtrManifesto::where('empresa_id', $this->empresa_id)
                ->where('numero_mtr', $numeroMtr)
                ->first();

            if (!$mtr) {
                // Se não existir, cria o novo manifesto de recepção
                $mtr = \App\Models\MtrManifesto::create([
                    'empresa_id' => $this->empresa_id,
                    'usuario_id' => $this->usuario_id,
                    'orgao' => $credencial->orgao ?: 'SINIR',
                    'tipo_origem' => 'recebimento_externo', // Indica que veio da tela de recepção
                    'seu_codigo' => 'REC-' . time(),
                    'numero_mtr' => $numeroMtr,
                    'data_expedicao' => date('Y-m-d H:i:s', $dataRecebimento / 1000), // Converte timestamp para Data
                    'gerador_nome' => strtoupper($geradorNomeForm),
                    'transportador_nome' => strtoupper($transportadorNomeForm),
                    'destinador_cnpj' => preg_replace('/\D+/', '', (string) $credencial->cpf_cnpj),
                    'destinador_nome' => strtoupper($credencial->descricao ?: 'EMPRESA DESTINATÁRIA'),
                    'veiculo_placa' => strtoupper($request->input('placa', 'N/INF')),
                    'motorista_nome' => strtoupper($request->input('motorista', 'N/INF')),
                    'status' => 'recebido', // Define o status oficial de recebimento
                    'mensagem_retorno' => $jaRecebidoNoGoverno ? 'Sincronizado: Já constava como recebido no SINIR.' : 'Manifesto recebido e baixado com sucesso no SINIR.',
                ]);
            } else {
                // Se já existir, atualiza os dados para não duplicar
                $mtr->status = 'recebido'; // Força o status correto
                $mtr->tipo_origem = 'recebimento_externo'; // Garante o tipo da origem
                $mtr->mensagem_retorno = $jaRecebidoNoGoverno ? 'Sincronizado: Já constava como recebido no SINIR.' : 'Manifesto recebido e baixado com sucesso no SINIR.';
                $mtr->gerador_nome = strtoupper($geradorNomeForm);
                $mtr->transportador_nome = strtoupper($transportadorNomeForm);
                $mtr->veiculo_placa = strtoupper($request->input('placa', $mtr->veiculo_placa));
                $mtr->motorista_nome = strtoupper($request->input('motorista', $mtr->motorista_nome));
                $mtr->save();
            }

            if (!empty($residuosInput)) {
                // Remove os itens antigos para gravar a consolidação final
                \App\Models\MtrManifestoItem::where('mtr_manifesto_id', $mtr->id)->delete();

                foreach ($residuosInput as $residuo) {
                    $qtd = (float) str_replace(',', '.', (string) ($residuo['quantidadeRecebida'] ?? $residuo['quantidade'] ?? '0'));
                    $cod = preg_replace('/\D+/', '', (string) ($residuo['cod_ibama'] ?? $residuo['codigo_ibama'] ?? ''));

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

            // URL DA API LEGADA CONFORME O MANUAL DO VÍDEO
            $baseUrlLegada = ($credencial->ambiente === 'producao')
                ? 'https://admin.sinir.gov.br/api'
                : 'https://admin-homologacao.sinir.gov.br/api';

            if ($credencial->orgao === 'IEMA') {
                $baseUrlLegada = ($credencial->ambiente === 'producao')
                    ? 'https://mtr.iema.es.gov.br/api'
                    : 'https://mtr-homologacao.iema.es.gov.br/api';
            }

            // CONSULTA NO GOVERNO USANDO A ROTA CORRETA DO MANUAL
            $response = \Illuminate\Support\Facades\Http::withToken($token)
                ->withHeaders(['Accept' => 'application/json'])
                ->get($baseUrlLegada . '/retornaManifesto/' . $numeroLimpo);

            if ($response->successful()) {
                $dados = $response->json();

                $gerador = $dados['gerador']['nome'] ?? $dados['nomeGerador'] ?? $dados['geradorNome'] ?? '';
                $transportador = $dados['transportador']['nome'] ?? $dados['nomeTransportador'] ?? $dados['transportadorNome'] ?? '';
                $placa = $dados['placaVeiculo'] ?? $dados['veiculoPlaca'] ?? $dados['placa'] ?? '';
                $motorista = $dados['nomeMotorista'] ?? $dados['motoristaNome'] ?? $dados['motorista'] ?? '';

                $residuosApi = $dados['listaManifestoResiduos'] ?? $dados['residuos'] ?? [];
                $residuos = [];

                foreach ($residuosApi as $item) {
                    $residuos[] = [
                        'codigoIbama' => $item['resCodigoIbama'] ?? $item['codigoIbama'] ?? '',
                        'descricaoResiduo' => $item['resDescricao'] ?? $item['descricao'] ?? 'RESÍDUO DECLARADO',
                        'unidadeMedidaNome' => $item['unidade'] ?? 'TON',
                        'quantidade' => $item['marQuantidade'] ?? $item['quantidade'] ?? 0,
                        'codigoGerado' => $item['codigoGerado'] ?? $item['resCodigo'] ?? null, // Captura o ID interno do item
                        'uniCodigo' => $item['uniCodigo'] ?? 3,
                        'traCodigo' => $item['traCodigo'] ?? $item['tiaCodigo'] ?? 43,
                        'tieCodigo' => $item['tieCodigo'] ?? 1,
                        'claCodigo' => $item['claCodigo'] ?? 43,
                    ];
                }

                return response()->json([
                    'success' => true,
                    'data' => [
                        'numeroMtr' => $numeroLimpo,
                        'veiculoPlaca' => $placa,
                        'motoristaNome' => $motorista,
                        'geradorNome' => $gerador,
                        'transportadorNome' => $transportador,
                        'residuos' => $residuos
                    ]
                ]);
            }

            // SE O GOVERNO FALHAR OU ESTIVER FORA DO AR, BUSCA NO BANCO LOCAL DO FERSOFT
            $mtrLocal = \App\Models\MtrManifesto::with('itens')
                ->where('empresa_id', $this->empresa_id)
                ->where('numero_mtr', $numeroLimpo)
                ->first();

            if ($mtrLocal) {
                $residuos = $mtrLocal->itens->map(function ($item) {
                    return [
                        'codigoIbama' => $item->cod_ibama,
                        'descricaoResiduo' => $item->descricao_residuo,
                        'unidadeMedidaNome' => 'TON',
                        'quantidade' => $item->quantidade,
                        'uniCodigo' => 3,
                        'traCodigo' => $item->tratamento_id ?? 43,
                        'tieCodigo' => $item->estado_fisico ?? 1,
                        'claCodigo' => $item->classe_residuo ?? 43,
                        'id' => $item->id
                    ];
                })->toArray();

                return response()->json([
                    'success' => true,
                    'data' => [
                        'numeroMtr' => $mtrLocal->numero_mtr,
                        'veiculoPlaca' => $mtrLocal->veiculo_placa,
                        'geradorNome' => $mtrLocal->gerador_nome,
                        'transportadorNome' => $mtrLocal->transportador_nome,
                        'residuos' => $residuos
                    ]
                ]);
            }

            throw new \Exception("MTR não encontrado na base do SINIR nem no histórico local.");

        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function replaceItens(MtrManifesto $manifesto, array $residuos): void
    {
        MtrManifestoItem::where('mtr_manifesto_id', $manifesto->id)->delete();

        foreach ($residuos as $residuo) {
            $codigo = preg_replace('/\D+/', '', (string) ($residuo['codigo_ibama'] ?? ''));
            $quantidade = (float) ($residuo['quantidade'] ?? 0);

            if ($codigo === '' || $quantidade <= 0) {
                continue;
            }

            MtrManifestoItem::create([
                'mtr_manifesto_id' => $manifesto->id,
                'cod_ibama' => $codigo,
                'descricao_residuo' => $residuo['descricao_residuo'] ?? 'RESÍDUO DECLARADO',
                'quantidade' => $quantidade,
                'unidade_medida' => (string) ($residuo['unidade'] ?? $residuo['unidade_medida'] ?? '2'),
                'estado_fisico' => $residuo['estado_fisico'] ?? 1,
                'classe_residuo' => (string) ($residuo['classe_residuo'] ?? '43'),
                'acondicionamento_id' => $residuo['acondicionamento_id']
                    ?? $residuo['acondicionamento']
                        ?? 8,
                'tratamento_id' => $residuo['tratamento_id']
                    ?? $residuo['tratamento']
                        ?? 43,
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
        return MtrConfig::where('empresa_id', $this->empresa_id)
            ->where('cpf_cnpj', $mtr->gerador_cnpj)
            ->firstOrFail();
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
}
