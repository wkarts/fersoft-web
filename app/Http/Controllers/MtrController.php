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
            'status' => 'status',
        ] as $column => $input) {
            if ($request->filled($input)) {
                $column === 'status'
                    ? $query->where($column, $request->{$input})
                    : $query->where($column, 'like', '%' . $request->{$input} . '%');
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
                    'Cancelamento solicitado pelo gerador no FERSOFT WEB.'
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

        $mtrsTransportador = [];
        $mtrsDestinador = [];

        $dtInicio = $request->input('dt_inicio_dest', date('Y-m-d', strtotime('-5 days')));
        $dtFim = $request->input('dt_fim_dest', date('Y-m-d'));

        if (Carbon::parse($dtInicio)->diffInDays(Carbon::parse($dtFim)) > 5) {
            return redirect()->back()
                ->with('erro', 'O período máximo permitido para consulta no SINIR é de 5 dias.');
        }

        try {
            if ($credencial) {
                $service = new SinirIemaService($credencial->orgao, $credencial->ambiente);
                $token = $this->token($credencial, $service);

                $mtrsTransportador = $service->requestWithToken(
                    $token,
                    'GET',
                    '/manifestosTransportador',
                    [
                        'dataInicio' => $request->input('dt_inicio_transp', $dtInicio),
                        'dataFim' => $request->input('dt_fim_transp', $dtFim),
                        'mtr' => $request->input('mtr_transp'),
                    ]
                );

                $mtrsDestinador = $service->requestWithToken(
                    $token,
                    'GET',
                    '/manifestosDestinador',
                    [
                        'dataInicio' => $dtInicio,
                        'dataFim' => $dtFim,
                        'mtr' => $request->input('mtr_dest'),
                    ]
                );
            }
        } catch (\Throwable $e) {
            Log::warning('Aviso ao buscar MTRs externos.', [
                'empresa_id' => $this->empresa_id,
                'error' => $e->getMessage(),
            ]);
        }

        return view('mtr.recepcao.index', [
            'mtrsTransportador' => $mtrsTransportador,
            'mtrsDestinador' => $mtrsDestinador,
            'dtInicio' => $dtInicio,
            'dtFim' => $dtFim,
            'title' => 'Recepção e Transporte de MTR (SINIR)',
        ]);
    }

    public function salvarRecebimentoMtr(Request $request)
    {
        try {
            $credencial = $this->credencialPadrao();
            $service = new SinirIemaService($credencial->orgao, $credencial->ambiente);
            $token = $this->token($credencial, $service);

            $service->requestWithToken($token, 'POST', '/receberManifesto', [
                'manNumero' => (string) $request->numero_mtr,
                'responsavelRecebimento' => (string) $request->responsavel,
                'dataRecebimento' => (int) (
                    Carbon::parse($request->input('data_recebimento') ?: now())->timestamp * 1000
                ),
                'residuos' => $request->input('residuos', []),
            ]);

            DB::beginTransaction();

            $mtr = MtrManifesto::where('empresa_id', $this->empresa_id)
                ->where('numero_mtr', $request->numero_mtr)
                ->first();

            if (!$mtr) {
                // O schema histórico não possui tipo_origem/status exclusivos de recebimento.
                $mtr = MtrManifesto::create([
                    'orgao' => $credencial->orgao ?: 'SINIR',
                    'tipo_origem' => 'avulso',
                    'seu_codigo' => 'REC-' . time(),
                    'numero_mtr' => $request->numero_mtr,
                    'data_expedicao' => now(),
                    'gerador_cnpj' => preg_replace('/\D+/', '', (string) $request->input('gerador_cnpj', $credencial->cpf_cnpj)),
                    'gerador_nome' => (string) $request->input('gerador_nome', 'GERADOR EXTERNO'),
                    'transportador_cnpj' => preg_replace('/\D+/', '', (string) $request->input('transportador_cnpj', $credencial->cpf_cnpj)),
                    'transportador_nome' => (string) $request->input('transportador_nome', 'TRANSPORTADOR'),
                    'destinador_cnpj' => preg_replace('/\D+/', '', (string) $credencial->cpf_cnpj),
                    'destinador_nome' => $credencial->descricao ?: 'EMPRESA DESTINATÁRIA',
                    'veiculo_placa' => $request->input('placa'),
                    'motorista_nome' => $request->input('motorista'),
                    'status' => 'transmitido',
                    'mensagem_retorno' => 'Manifesto recebido pelo destinador.',
                ]);
            } else {
                $mtr->status = 'transmitido';
                $mtr->mensagem_retorno = 'Manifesto recebido pelo destinador.';
                $mtr->save();
            }

            if ($request->has('residuos')) {
                MtrManifestoItem::where('mtr_manifesto_id', $mtr->id)->delete();

                foreach ((array) $request->input('residuos', []) as $residuo) {
                    $quantidade = (float) ($residuo['quantidadeRecebida'] ?? $residuo['quantidade'] ?? 0);
                    $codigo = preg_replace('/\D+/', '', (string) ($residuo['cod_ibama'] ?? $residuo['codigo_ibama'] ?? ''));

                    if ($quantidade <= 0 || $codigo === '') {
                        continue;
                    }

                    MtrManifestoItem::create([
                        'mtr_manifesto_id' => $mtr->id,
                        'cod_ibama' => $codigo,
                        'descricao_residuo' => $residuo['descricao'] ?? 'RESÍDUO RECEBIDO',
                        'quantidade' => $quantidade,
                        'unidade_medida' => (string) ($residuo['unidade_medida'] ?? 2),
                        'estado_fisico' => $residuo['estado_fisico'] ?? 1,
                        'classe_residuo' => (string) ($residuo['classe_residuo'] ?? 43),
                        'acondicionamento_id' => $residuo['acondicionamento_id'] ?? 8,
                        'tratamento_id' => $residuo['tratamento_id'] ?? 43,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'MTR recebido e registrado localmente.',
            ]);
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar recebimento: ' . $e->getMessage(),
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
            $credencial = $this->credencialPadrao();
            $service = new SinirIemaService($credencial->orgao, $credencial->ambiente);

            $dados = $service->requestWithToken(
                $this->token($credencial, $service),
                'GET',
                '/consultarManifesto/' . rawurlencode((string) $numeroMtr)
            );

            return response()->json(['success' => true, 'data' => $dados]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
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
