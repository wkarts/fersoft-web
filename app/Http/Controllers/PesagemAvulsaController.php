<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PesagemAvulsa;
use App\Models\PesagemAvulsaItem;
use App\Models\Cliente;
use App\Models\Produto;
use App\Models\ContaReceber;
use App\Models\BalancaConfig;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PesagemAvulsaController extends BaseController
{
    protected $model = PesagemAvulsa::class;
    protected $resource = 'pesagens_avulsas';
    protected $formTitle = 'Serviço de Pesagem';
    protected $redirectPage = '/pesagens_avulsas';

    protected function rules(): array
    {
        return [
            'cliente_id'     => 'required',
            'motorista_nome' => 'required|string',
            'placa_veiculo'  => 'required|string|max:8',
        ];
    }

    protected function messages(): array
    {
        return [
            'cliente_id.required'     => 'O cliente é obrigatório.',
            'motorista_nome.required' => 'O motorista é obrigatório.',
            'placa_veiculo.required'  => 'A placa do veículo é obrigatória.',
        ];
    }

    public function index()
    {
        $balancas = BalancaConfig::where('empresa_id', $this->empresa_id)
            ->where('ativo', true)
            ->where('integrador', 'adp')
            ->get();

        // Filtra apenas produtos ativos que possuem controla_pesagem = 1
        $produtos = Produto::where('empresa_id', $this->empresa_id)
            ->where('inativo', 0)
            ->where('controla_pesagem', 1)
            ->orderBy('nome', 'asc')
            ->get(['id', 'nome', 'referencia']);

        $configNota = \App\Models\ConfigNota::where('empresa_id', $this->empresa_id)->first();
        $usuarioLogado = \App\Models\Usuario::find(session('user_logged')['id'] ?? $this->usuario_id);
        $balancaPadraoUsuarioId = $usuarioLogado->balanca_padrao_id ?? null;

        $pesagensRecentes = PesagemAvulsa::with(['cliente', 'itens'])
            ->where('empresa_id', $this->empresa_id)
            ->orderBy('id', 'desc')
            ->limit(50)
            ->get();

        return view('pesagens_avulsas.index', compact(
            'balancas',
            'produtos',
            'configNota',
            'pesagensRecentes',
            'balancaPadraoUsuarioId'
        ));
    }

    private function consultarDadosCnpjReceita(string $cnpj): ?array
    {
        if (strlen($cnpj) !== 14) {
            return null;
        }

        $urls = [
            "https://brasilapi.com.br/api/cnpj/v1/{$cnpj}",
            "https://publica.cnpj.ws/cnpj/{$cnpj}",
        ];

        foreach ($urls as $url) {
            try {
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_TIMEOUT        => 5,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_HTTPHEADER     => [
                        'Accept: application/json',
                        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) FerSoftERP/1.0',
                    ],
                ]);
                $res  = curl_exec($ch);
                $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($code === 200 && $res) {
                    $json = json_decode($res, true);
                    if (!empty($json['razao_social'])) {
                        $est = $json['estabelecimento'] ?? $json;
                        return [
                            'razao_social'  => mb_strtoupper(substr($json['razao_social'], 0, 100)),
                            'nome_fantasia' => mb_strtoupper(substr($est['nome_fantasia'] ?? $json['razao_social'], 0, 80)),
                            'rua'           => mb_strtoupper(substr($est['logradouro'] ?? 'SEM RUA', 0, 80)),
                            'numero'        => mb_strtoupper(substr($est['numero'] ?? 'SN', 0, 10)),
                            'bairro'        => mb_strtoupper(substr($est['bairro'] ?? 'CENTRO', 0, 50)),
                            'cep'           => preg_replace('/\D/', '', (string) ($est['cep'] ?? '00000000')),
                            'telefone'      => preg_replace('/\D/', '', (string) (($est['ddd1'] ?? '') . ($est['telefone1'] ?? ($json['ddd_telefone_1'] ?? '0000000000')))),
                        ];
                    }
                }
            } catch (\Throwable $e) {
            }
        }

        return null;
    }

    private function obterOuCriarClienteAutomatico(?string $cnpjInput, ?string $nomeInput = null): ?Cliente
    {
        $doc = preg_replace('/\D/', '', (string) $cnpjInput);
        if (strlen($doc) < 11) {
            return null;
        }

        $nomeDigitado = trim((string) $nomeInput);

        $cliente = Cliente::where('empresa_id', $this->empresa_id)
            ->whereRaw("REPLACE(REPLACE(REPLACE(cpf_cnpj, '.', ''), '-', ''), '/', '') = ?", [$doc])
            ->first();

        if ($cliente) {
            $ehNomeGenerico = stripos($cliente->razao_social, 'CLIENTE AVULSO') !== false
                || stripos($cliente->razao_social, 'CLIENTE PESAGEM') !== false;

            if ($ehNomeGenerico) {
                if ($nomeDigitado !== '' && stripos($nomeDigitado, 'CLIENTE AVULSO') === false) {
                    $cliente->update([
                        'razao_social'  => mb_strtoupper($nomeDigitado),
                        'nome_fantasia' => mb_strtoupper($nomeDigitado),
                    ]);
                } elseif (strlen($doc) === 14) {
                    $dadosReceita = $this->consultarDadosCnpjReceita($doc);
                    if ($dadosReceita) {
                        $cliente->update($dadosReceita);
                    }
                }
            }
            return $cliente->fresh();
        }

        $dadosApi = (strlen($doc) === 14) ? $this->consultarDadosCnpjReceita($doc) : null;

        $razaoSocial  = $dadosApi['razao_social']  ?? ($nomeDigitado !== '' ? mb_strtoupper($nomeDigitado) : 'CLIENTE PESAGEM (' . $doc . ')');
        $nomeFantasia = $dadosApi['nome_fantasia'] ?? $razaoSocial;
        $rua          = $dadosApi['rua']           ?? 'SEM RUA';
        $numero       = $dadosApi['numero']        ?? 'SN';
        $bairro       = $dadosApi['bairro']        ?? 'CENTRO';
        $cep          = $dadosApi['cep']           ?? '00000000';
        $telefone     = $dadosApi['telefone']      ?? '0000000000';

        $cidadeId = null;
        $config = \App\Models\ConfigNota::where('empresa_id', $this->empresa_id)->first();
        if ($config && $config->codMun) {
            $cidade = \App\Models\Cidade::where('codigo', $config->codMun)->first();
            $cidadeId = $cidade ? $cidade->id : null;
        }
        if (!$cidadeId) {
            $empresa = \App\Models\Empresa::find($this->empresa_id);
            $cidadeId = $empresa->cidade_id ?? \App\Models\Cidade::value('id');
        }

        $novoId = DB::table('clientes')->insertGetId([
            'empresa_id'        => $this->empresa_id,
            'razao_social'      => $razaoSocial,
            'nome_fantasia'     => $nomeFantasia ?: $razaoSocial,
            'cpf_cnpj'          => $doc,
            'rua'               => $rua,
            'ie_rg'             => 'ISENTO',
            'numero'            => $numero,
            'bairro'            => $bairro,
            'telefone'          => $telefone,
            'complemento'       => '',
            'celular'           => $telefone,
            'email'             => '',
            'cep'               => $cep,
            'consumidor_final'  => 1,
            'contribuinte'      => 1,
            'inativo'           => 0,
            'cidade_id'         => $cidadeId,
            'limite_venda'      => 0,
            'valor_cashback'    => 0,
            'rua_cobranca'      => '',
            'numero_cobranca'   => '',
            'bairro_cobranca'   => '',
            'cep_cobranca'      => '',
            'cod_pais'          => 1058,
            'id_estrangeiro'    => '',
            'grupo_id'          => 0,
            'acessor_id'        => 0,
            'contador_nome'     => '',
            'contador_telefone' => '',
            'contador_email'    => '',
            'funcionario_id'    => 0,
            'observacao'        => 'Cadastrado automaticamente na Pesagem Avulsa',
            'data_aniversario'  => '',
            'data_nascimento'   => '',
            'nuvemshop_id'      => '',
            'imagem'            => '',
            'instagram'         => '',
            'facebook'          => '',
            'linkedin'          => '',
            'tiktok'            => '',
            'whatsapp'          => '',
            'rua_entrega'       => '',
            'nome_entrega'      => '',
            'cpf_cnpj_entrega'  => '',
            'numero_entrega'    => '',
            'bairro_entrega'    => '',
            'cep_entrega'       => '',
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        return Cliente::find($novoId);
    }

    public function buscarOuCriarCliente(Request $request)
    {
        try {
            $cliente = $this->obterOuCriarClienteAutomatico($request->cnpj, $request->cliente_nome);

            if (!$cliente) {
                return response()->json(['error' => 'Informe um CPF (11 dígitos) ou CNPJ (14 dígitos) válido.'], 422);
            }

            return response()->json(['success' => true, 'cliente' => $cliente]);
        } catch (\Throwable $e) {
            \Log::error('Erro ao buscar/criar cliente na pesagem avulsa: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao cadastrar cliente: ' . $e->getMessage()], 500);
        }
    }

    private function recalcularTotaisPai(PesagemAvulsa $pesagem): void
    {
        $itens = PesagemAvulsaItem::where('pesagem_avulsa_id', $pesagem->id)->orderBy('id', 'asc')->get();
        if ($itens->isEmpty()) {
            return;
        }

        $primeiro = $itens->first();
        $ultimoComSaida = $itens->where('peso_saida', '>', 0)->last();

        $pesagem->update([
            'peso_bruto'   => (float) $primeiro->peso_entrada,
            'tara'         => $ultimoComSaida ? (float) $ultimoComSaida->peso_saida : 0,
            'peso_liquido' => (float) $itens->sum('peso_liquido'),
        ]);
    }

    public function save(Request $request)
    {
        try {
            DB::beginTransaction();

            $limparNumero = function ($val) {
                if ($val === null || $val === '') return 0.0;
                $val = str_replace(['R$', ' '], '', (string) $val);
                if (strpos($val, ',') !== false && strpos($val, '.') !== false) {
                    $val = str_replace('.', '', $val);
                    $val = str_replace(',', '.', $val);
                } else {
                    $val = str_replace(',', '.', $val);
                }
                return (float) $val;
            };

            $comItens     = $request->boolean('com_itens');
            $acao         = $request->input('acao', 'salvar_entrada');
            $pesoEntrada  = $limparNumero($request->input('peso_entrada', 0));
            $pesoSaida    = $limparNumero($request->input('peso_saida', 0));
            $valorServico = $limparNumero($request->input('valor_servico', 0));
            $observacao   = trim((string) $request->input('observacao', ''));
            $status       = $request->input('status', 'em andamento');

            $clienteId = (int) $request->input('cliente_id', 0);
            if ($request->filled('cnpj_busca')) {
                $clienteAuto = $this->obterOuCriarClienteAutomatico($request->input('cnpj_busca'), $request->input('cliente_nome'));
                if ($clienteAuto) {
                    $clienteId = $clienteAuto->id;
                }
            }

            $filialIdSalvar = ($this->filial_id && session('filial_nome') !== 'Matriz') ? $this->filial_id : null;

            // =========================================================================
            // FLUXO A: PESAGEM COM ITENS
            // =========================================================================
            if ($comItens) {
                if ($request->filled('id')) {
                    $pesagem = PesagemAvulsa::where('empresa_id', $this->empresa_id)->findOrFail($request->id);
                    $clienteId = $clienteId ?: (int) $pesagem->cliente_id;
                    if ($valorServico <= 0 && (float) $pesagem->valor_servico > 0) {
                        $valorServico = (float) $pesagem->valor_servico;
                    }
                    $pesagem->update([
                        'com_itens'     => 1,
                        'valor_servico' => $valorServico,
                        'observacao'    => $observacao !== '' ? $observacao : $pesagem->observacao,
                    ]);
                } else {
                    if ($clienteId <= 0) {
                        return response()->json(['success' => false, 'message' => 'Informe um CNPJ ou CPF válido do cliente.'], 422);
                    }
                    $pesagem = PesagemAvulsa::create([
                        'empresa_id'            => $this->empresa_id,
                        'filial_id'             => $filialIdSalvar,
                        'usuario_id'            => $this->usuario_id,
                        'cliente_id'            => $clienteId,
                        'com_itens'             => 1,
                        'motorista_nome'        => mb_strtoupper((string) $request->motorista_nome),
                        'placa_veiculo'         => mb_strtoupper((string) $request->placa_veiculo),
                        'placa_carreta'         => mb_strtoupper((string) $request->placa_carreta),
                        'peso_bruto'            => 0,
                        'tara'                  => 0,
                        'peso_liquido'          => 0,
                        'valor_servico'         => $valorServico,
                        'observacao'            => $observacao,
                        'status'                => 'em andamento',
                        'token'                 => Str::random(40),
                        'balanca_evidence_json' => $request->balanca_evidence_json,
                        'camera_snapshots_json' => $request->camera_snapshots_json,
                    ]);
                }

                $temDadosDeItemNaTela = $request->filled('item_id') || $request->filled('produto_id') || $request->filled('produto_nome_livre');

                if ($acao === 'salvar_item' || ($acao === 'finalizar_pesagem' && $temDadosDeItemNaTela && $pesoEntrada > 0)) {
                    if ($request->filled('item_id')) {
                        $item = PesagemAvulsaItem::where('pesagem_avulsa_id', $pesagem->id)->findOrFail($request->item_id);
                        if ($pesoSaida <= 0) {
                            return response()->json(['success' => false, 'message' => 'Informe o Peso de Saída do item "' . $item->produto_nome . '".'], 422);
                        }
                        $liquidoItem = abs((float) $item->peso_entrada - $pesoSaida);
                        $item->update([
                            'peso_saida'   => $pesoSaida,
                            'peso_liquido' => $liquidoItem,
                            'status'       => 'concluído',
                        ]);
                    } else {
                        $produtoId   = $request->input('produto_id') ?: null;
                        $produtoNome = trim((string) $request->input('produto_nome_livre', ''));
                        if ($produtoId) {
                            $prodObj = Produto::where('empresa_id', $this->empresa_id)
                                ->where('controla_pesagem', 1)
                                ->find($produtoId);
                            if ($prodObj) {
                                $produtoNome = $prodObj->nome;
                            }
                        }

                        if (empty($produtoNome)) {
                            return response()->json(['success' => false, 'message' => 'Selecione um Produto/Item habilitado para pesagem antes de registrar o peso.'], 422);
                        }
                        if ($pesoEntrada <= 0) {
                            return response()->json(['success' => false, 'message' => 'Informe o Peso de Entrada do item.'], 422);
                        }

                        $itemConcluido = $pesoSaida > 0;
                        $liquidoItem   = $itemConcluido ? abs($pesoEntrada - $pesoSaida) : $pesoEntrada;

                        PesagemAvulsaItem::create([
                            'pesagem_avulsa_id' => $pesagem->id,
                            'produto_id'        => $produtoId,
                            'produto_nome'      => mb_strtoupper($produtoNome),
                            'peso_entrada'      => $pesoEntrada,
                            'peso_saida'        => $pesoSaida,
                            'peso_liquido'      => $liquidoItem,
                            'status'            => $itemConcluido ? 'concluído' : 'em andamento',
                        ]);
                    }
                }

                $this->recalcularTotaisPai($pesagem);
                $pesagem->refresh();

                if ($acao === 'finalizar_pesagem') {
                    $qtdItens = PesagemAvulsaItem::where('pesagem_avulsa_id', $pesagem->id)->count();
                    if ($qtdItens === 0) {
                        return response()->json(['success' => false, 'message' => 'Adicione pelo menos 1 item antes de finalizar a pesagem.'], 422);
                    }

                    $itensPendentes = PesagemAvulsaItem::where('pesagem_avulsa_id', $pesagem->id)
                        ->where('status', 'em andamento')
                        ->count();

                    if ($itensPendentes > 0) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Ainda existe item aguardando o Peso de Saída. Clique em Pesar Saída do item abaixo antes de finalizar.'
                        ], 422);
                    }

                    $status = 'concluído';
                    $pesagem->update(['status' => 'concluído']);
                } else {
                    $status = 'em andamento';
                }
            }
            // =========================================================================
            // FLUXO B: PESAGEM SEM ITEM
            // =========================================================================
            else {
                if ($pesoEntrada <= 0) {
                    return response()->json(['success' => false, 'message' => 'Informe um Peso de Entrada válido maior que zero.'], 422);
                }

                if ($request->filled('id')) {
                    $pesagem = PesagemAvulsa::where('empresa_id', $this->empresa_id)->findOrFail($request->id);
                    $pesoEntrada = (float) $pesagem->peso_bruto;
                    $clienteId   = $clienteId ?: (int) $pesagem->cliente_id;

                    if ($status === 'concluído' && $pesoSaida <= 0) {
                        return response()->json(['success' => false, 'message' => 'Informe o Peso de Saída para finalizar a pesagem.'], 422);
                    }

                    $pesoLiquido = $pesoSaida > 0 ? abs($pesoEntrada - $pesoSaida) : $pesoEntrada;

                    if ($valorServico <= 0 && (float) $pesagem->valor_servico > 0) {
                        $valorServico = (float) $pesagem->valor_servico;
                    }

                    $pesagem->update([
                        'cliente_id'            => $clienteId,
                        'com_itens'             => 0,
                        'tara'                  => $pesoSaida,
                        'peso_liquido'          => $pesoLiquido,
                        'valor_servico'         => $valorServico,
                        'observacao'            => $observacao !== '' ? $observacao : $pesagem->observacao,
                        'status'                => $status,
                        'balanca_evidence_json' => $request->balanca_evidence_json ?: $pesagem->balanca_evidence_json,
                        'camera_snapshots_json' => $request->camera_snapshots_json ?: $pesagem->camera_snapshots_json,
                    ]);
                } else {
                    if ($clienteId <= 0) {
                        return response()->json(['success' => false, 'message' => 'Informe um CNPJ ou CPF válido do cliente.'], 422);
                    }

                    $pesoLiquido = ($status === 'concluído' && $pesoSaida > 0)
                        ? abs($pesoEntrada - $pesoSaida)
                        : $pesoEntrada;

                    $pesagem = PesagemAvulsa::create([
                        'empresa_id'            => $this->empresa_id,
                        'filial_id'             => $filialIdSalvar,
                        'usuario_id'            => $this->usuario_id,
                        'cliente_id'            => $clienteId,
                        'com_itens'             => 0,
                        'motorista_nome'        => mb_strtoupper((string) $request->motorista_nome),
                        'placa_veiculo'         => mb_strtoupper((string) $request->placa_veiculo),
                        'placa_carreta'         => mb_strtoupper((string) $request->placa_carreta),
                        'peso_bruto'            => $pesoEntrada,
                        'tara'                  => $pesoSaida,
                        'peso_liquido'          => $pesoLiquido,
                        'valor_servico'         => $valorServico,
                        'observacao'            => $observacao,
                        'status'                => $status,
                        'token'                 => Str::random(40),
                        'balanca_evidence_json' => $request->balanca_evidence_json,
                        'camera_snapshots_json' => $request->camera_snapshots_json,
                    ]);
                }
            }

            // =========================================================================
            // INTEGRAÇÃO COM CONTAS A RECEBER (QUANDO CONCLUÍDO E VALOR > 0)
            // =========================================================================
            $contaGeradaId = $pesagem->conta_receber_id;

            if ($status === 'concluído' && $valorServico > 0 && empty($contaGeradaId)) {
                $categoria = DB::table('categoria_contas')
                    ->where('empresa_id', $this->empresa_id)
                    ->where('tipo', 'receber')
                    ->where('nome', 'like', '%servi%')
                    ->first();

                if (!$categoria) {
                    $categoria = DB::table('categoria_contas')
                        ->where('empresa_id', $this->empresa_id)
                        ->where('tipo', 'receber')
                        ->first();
                }

                if (!$categoria) {
                    $catId = DB::table('categoria_contas')->insertGetId([
                        'empresa_id' => $this->empresa_id,
                        'nome'       => 'Serviço de Pesagem',
                        'tipo'       => 'receber',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $catId = $categoria->id;
                }

                $obsFinanceiro = 'Motorista: ' . $pesagem->motorista_nome . ' | Peso Líquido: ' . number_format($pesagem->peso_liquido, 2, ',', '.') . ' kg';
                if (!empty($pesagem->observacao)) {
                    $obsFinanceiro .= ' | Obs: ' . $pesagem->observacao;
                }

                $contaGeradaId = DB::table('conta_recebers')->insertGetId([
                    'empresa_id'         => $this->empresa_id,
                    'venda_id'           => null,
                    'venda_caixa_id'     => null,
                    'cte_id'             => null,
                    'cliente_id'         => $pesagem->cliente_id,
                    'categoria_id'       => $catId,
                    'referencia'         => 'Serviço de Pesagem #' . $pesagem->id . ' - Placa: ' . $pesagem->placa_veiculo,
                    'valor_integral'     => $valorServico,
                    'valor_recebido'     => 0,
                    'date_register'      => now(),
                    'data_vencimento'    => now()->format('Y-m-d'),
                    'data_recebimento'   => null,
                    'status'             => 0,
                    'juros'              => 0,
                    'multa'              => 0,
                    'desconto'           => 0,
                    'observacao'         => $obsFinanceiro,
                    'tipo_pagamento'     => '01',
                    'filial_id'          => $filialIdSalvar,
                    'entrada'            => 0,
                    'estorno'            => 0,
                    'numero_nota_fiscal' => 0,
                    'usuario_id'         => $this->usuario_id,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);

                DB::table('pesagens_avulsas')
                    ->where('id', $pesagem->id)
                    ->update(['conta_receber_id' => $contaGeradaId]);
            }

            DB::commit();

            $pesagemCompleta = PesagemAvulsa::with(['cliente', 'itens'])->find($pesagem->id);

            $msgRetorno = $status === 'concluído'
                ? ($contaGeradaId
                    ? "Pesagem finalizada! Conta a Receber #{$contaGeradaId} gerada com sucesso!"
                    : "Pesagem finalizada com sucesso!")
                : ($comItens ? 'Peso do item registrado!' : 'Entrada registrada! Veículo aguardando saída.');

            return response()->json([
                'success'          => true,
                'message'          => $msgRetorno,
                'pesagem_id'       => $pesagem->id,
                'status'           => $status,
                'pesagem'          => $pesagemCompleta,
                'conta_receber_id' => $contaGeradaId,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erro ao salvar PesagemAvulsa: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro: ' . $e->getMessage()], 500);
        }
    }

    public function deleteItem($id)
    {
        try {
            $item = PesagemAvulsaItem::findOrFail($id);
            $pesagem = PesagemAvulsa::where('empresa_id', $this->empresa_id)->findOrFail($item->pesagem_avulsa_id);

            if ($pesagem->status === 'concluído') {
                return response()->json(['success' => false, 'message' => 'Não é possível excluir itens de uma pesagem já finalizada.'], 422);
            }

            $item->delete();
            $this->recalcularTotaisPai($pesagem);

            return response()->json([
                'success' => true,
                'message' => 'Item removido!',
                'pesagem' => PesagemAvulsa::with(['cliente', 'itens'])->find($pesagem->id),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function cancelar($id)
    {
        try {
            $pesagem = PesagemAvulsa::where('empresa_id', $this->empresa_id)->findOrFail($id);
            $pesagem->update(['status' => 'cancelado']);

            if ($pesagem->conta_receber_id) {
                ContaReceber::where('id', $pesagem->conta_receber_id)->update(['estorno' => 1, 'status' => 2]);
            }

            return response()->json(['success' => true, 'message' => 'Pesagem cancelada com sucesso!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao cancelar: ' . $e->getMessage()], 500);
        }
    }

    public function historico(Request $request)
    {
        $query = PesagemAvulsa::with(['cliente', 'itens'])
            ->where('empresa_id', $this->empresa_id);

        if ($request->filled('data_inicio')) {
            $query->whereDate('created_at', '>=', $request->data_inicio);
        }
        if ($request->filled('data_fim')) {
            $query->whereDate('created_at', '<=', $request->data_fim);
        }
        if ($request->filled('placa')) {
            $query->where('placa_veiculo', 'like', "%{$request->placa}%");
        }

        $pesagens = $query->orderBy('id', 'desc')->limit(100)->get();

        return response()->json($pesagens);
    }

    public function imprimir80mm(Request $request, $id)
    {
        try {
            $pesagem = PesagemAvulsa::with(['cliente', 'itens'])
                ->where('empresa_id', $this->empresa_id)
                ->findOrFail($id);

            $ocultarItens = $request->boolean('ocultar_itens');

            $emitente = \App\Models\ConfigNota::where('empresa_id', $this->empresa_id)->first();
            if (!$emitente) {
                $emitente = \App\Models\Empresa::find($this->empresa_id);
            }

            // 1. Logo em Base64
            $logoHtml = '<h2 style="margin:0; font-size:15px; font-weight:bold;">' . htmlspecialchars($emitente->razao_social ?? 'SERVIÇO DE PESAGEM') . '</h2>';
            if ($emitente && !empty($emitente->logo)) {
                $caminhosLogo = [
                    public_path('logos/' . $emitente->logo),
                    public_path('imgs/' . $emitente->logo),
                ];
                foreach ($caminhosLogo as $caminho) {
                    if (file_exists($caminho) && is_file($caminho)) {
                        $ext = strtolower(pathinfo($caminho, PATHINFO_EXTENSION)) ?: 'png';
                        $base64Logo = base64_encode(file_get_contents($caminho));
                        $logoHtml = '<img src="data:image/' . $ext . ';base64,' . $base64Logo . '" class="logo">';
                        break;
                    }
                }
            }

            // 2. QR Code
            $qrcodeHtml = '';
            try {
                $urlQrCode = url('/pesagens_avulsas/imprimir-80mm/' . $pesagem->id);
                $renderer = new \BaconQrCode\Renderer\ImageRenderer(
                    new \BaconQrCode\Renderer\RendererStyle\RendererStyle(120),
                    new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
                );
                $writer = new \BaconQrCode\Writer($renderer);
                $qrcodeSvg = 'data:image/svg+xml;base64,' . base64_encode($writer->writeString($urlQrCode));
                $qrcodeHtml = '<div class="text-center"><img src="' . $qrcodeSvg . '" class="qrcode"></div>';
            } catch (\Throwable $e) {
                $qrcodeHtml = '';
            }

            $dataFormatada = \Carbon\Carbon::parse($pesagem->created_at)->format('d/m/Y H:i');
            $placa1        = htmlspecialchars($pesagem->placa_veiculo);
            $placa2        = htmlspecialchars($pesagem->placa_carreta ?: 'N/A');
            $motorista     = htmlspecialchars($pesagem->motorista_nome);
            $clienteRazao  = htmlspecialchars($pesagem->cliente->razao_social ?? 'N/A');
            $clienteDoc    = htmlspecialchars($pesagem->cliente->cpf_cnpj ?? 'N/A');

            $pesoEntrada   = number_format((float) $pesagem->peso_bruto, 2, ',', '.');
            $pesoSaida     = number_format((float) $pesagem->tara, 2, ',', '.');
            $pesoLiquido   = number_format((float) $pesagem->peso_liquido, 2, ',', '.');

            // 3. Bloco de Itens em formato de TABELA (Item | Produto | Peso)
            $blocoItensHtml = '';
            if (!$ocultarItens && $pesagem->com_itens && $pesagem->itens && $pesagem->itens->count() > 0) {
                $linhasItens = '';
                foreach ($pesagem->itens->values() as $idx => $item) {
                    $numItem  = $idx + 1;
                    $nomeProd = htmlspecialchars($item->produto_nome);
                    $pLiq     = number_format((float) $item->peso_liquido, 2, ',', '.');
                    $linhasItens .= '
                    <tr>
                        <td style="width:14%; padding:3px 0;">' . $numItem . '</td>
                        <td style="width:54%; padding:3px 2px;">' . $nomeProd . '</td>
                        <td class="text-right" style="width:32%; padding:3px 0;">' . $pLiq . '</td>
                    </tr>';
                }

                $blocoItensHtml = '
                <div class="divider"></div>
                <table class="tabela-itens">
                    <thead>
                        <tr>
                            <th style="width:14%; text-align:left;">Item</th>
                            <th style="width:54%; text-align:left;">Produto</th>
                            <th style="width:32%; text-align:right;">Peso</th>
                        </tr>
                    </thead>
                    <tbody>
                        ' . $linhasItens . '
                    </tbody>
                </table>';
            }

            $linhaSaida = ((float) $pesagem->tara > 0)
                ? '<tr><td>Peso Saída Final:</td><td class="text-right">' . $pesoSaida . ' kg</td></tr>'
                : '';

            $exibirValoresConfig = (bool) ($emitente->pesagem_exibir_valores_relatorio ?? true);
            $linhaValor = ($exibirValoresConfig && (float) $pesagem->valor_servico > 0)
                ? '<tr><td style="padding-top:5px;">Valor do Serviço:</td><td class="text-right" style="padding-top:5px;">R$ ' . number_format((float) $pesagem->valor_servico, 2, ',', '.') . '</td></tr>'
                : '';

            // 4. Bloco de Observação (se preenchido)
            $blocoObservacaoHtml = '';
            if (!empty(trim((string) $pesagem->observacao))) {
                $obsFormatada = nl2br(htmlspecialchars(trim((string) $pesagem->observacao)));
                $blocoObservacaoHtml = '
                <div class="divider"></div>
                <div style="font-size:11.5px;">
                    Observação:<br>
                    ' . $obsFormatada . '
                </div>';
            }

            $emRazao = htmlspecialchars($emitente->razao_social ?? 'N/A');
            $emCnpj  = htmlspecialchars($emitente->cnpj ?? 'N/A');
            $emIe    = htmlspecialchars($emitente->ie ?? 'N/A');
            $emMun   = htmlspecialchars($emitente->municipio ?? 'N/A');
            $emUf    = htmlspecialchars($emitente->UF ?? 'N/A');
            $emFone  = htmlspecialchars($emitente->fone ?? 'N/A');
            $emEmail = htmlspecialchars($emitente->email ?? 'N/A');

            $alturaPapel = 710 + ($pesagem->itens ? ($pesagem->itens->count() * 28) : 0) + (!empty($pesagem->observacao) ? 55 : 0);

            $html = '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Ticket #' . $pesagem->id . '</title>
    <style>
        @page { margin: 3mm; }
        body, table, tr, td, th, div, span, p, b, strong {
            font-family: Helvetica, Arial, sans-serif !important;
            font-weight: bold !important;
            color: #000 !important;
        }
        body { font-size: 11.5px; margin: 0; padding: 0; width: 100%; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .divider { border-top: 1.5px dashed #000; margin: 8px 0; }
        .logo { max-width: 170px; max-height: 65px; margin-bottom: 5px; }
        .qrcode { width: 115px; height: 115px; margin: 4px auto; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; padding: 2.5px 0; }
        .info-principal td { font-size: 12.5px; line-height: 1.35; }
        .info-destaque { font-size: 13.5px !important; }
        .tabela-itens th { border-bottom: 1.5px solid #000; padding-bottom: 3px; font-size: 11.5px; }
        .tabela-itens td { font-size: 11.5px; }
        .box-emitente { border: 1.5px solid #000; padding: 5px; font-size: 9.5px; margin-top: 12px; }
        .assinatura { border-top: 1.5px solid #000; width: 90%; margin: 28px auto 5px auto; text-align: center; padding-top: 3px; font-size: 11.5px; }
    </style>
</head>
<body>
    <div class="text-center">' . $logoHtml . '</div>
    ' . $qrcodeHtml . '
    <div class="text-center" style="font-size:10px;">Ticket: ' . $pesagem->token . '</div>

    <div class="divider"></div>

    <table class="info-principal">
        <tr><td class="info-destaque">Status: Concluído</td></tr>
        <tr><td class="info-destaque">Pesagem ID: ' . $pesagem->id . '</td></tr>
        <tr><td>Data/Hora: ' . $dataFormatada . '</td></tr>
        <tr><td>Placa ¹: ' . $placa1 . '</td></tr>
        <tr><td>Placa ²: ' . $placa2 . '</td></tr>
        <tr><td>Motorista: ' . $motorista . '</td></tr>
        <tr><td>Tipo: Serviço de Pesagem</td></tr>
    </table>

    <div style="margin-top:8px; font-size:12px;">
        <span>Cliente:</span><br>
        <span>' . $clienteRazao . '</span><br>
        <span>CPF/CNPJ: ' . $clienteDoc . '</span>
    </div>

    ' . $blocoItensHtml . '

    <div class="divider"></div>

    <table style="font-size:12px;">
        <tr><td>Peso Entrada Inicial:</td><td class="text-right">' . $pesoEntrada . ' kg</td></tr>
        ' . $linhaSaida . '
        <tr><td style="font-size:12.5px;">Peso Líquido Total:</td><td class="text-right" style="font-size:12.5px;">' . $pesoLiquido . ' kg</td></tr>
        ' . $linhaValor . '
    </table>

    ' . $blocoObservacaoHtml . '

    <div class="box-emitente">
        <table>
            <tr><td colspan="2">Razão Social: ' . $emRazao . '</td></tr>
            <tr><td>CNPJ: ' . $emCnpj . '</td><td>IE: ' . $emIe . '</td></tr>
            <tr><td>Município: ' . $emMun . '</td><td>UF: ' . $emUf . '</td></tr>
            <tr><td>E-mail: ' . $emEmail . '</td><td>Fone: ' . $emFone . '</td></tr>
        </table>
    </div>

    <div class="assinatura">Assinatura</div>
</body>
</html>';

            $dompdf = new \Dompdf\Dompdf(['enable_remote' => true]);
            $dompdf->loadHtml($html);
            $dompdf->setPaper([0, 0, 226.77, $alturaPapel]);
            $dompdf->render();

            return response($dompdf->output())
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="ticket-avulso-' . $pesagem->id . '.pdf"')
                ->header('Cache-Control', 'no-cache, must-revalidate');

        } catch (\Exception $e) {
            \Log::error('Erro ao gerar ticket 80mm Avulso: ' . $e->getMessage());
            return response('Erro ao gerar ticket 80mm: ' . $e->getMessage(), 500);
        }
    }
}
