<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Empresa;
use App\Models\Produto;
use App\Models\Cliente;
use App\Models\Usuario;
use App\Models\ConfigNota;
use App\Models\Tributacao;
use App\Models\NaturezaOperacao;
use App\Models\Funcionario;
use App\Models\VendaCaixa;
use App\Models\ItemVendaCaixa;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SyncApiController extends Controller
{
    /**
     * Valida credenciais do PDV local e status da empresa na Nuvem.
     */
    private function autenticarPdvLocal(Request $request)
    {
        // Limpa o CNPJ vindo da requisição (deixa apenas números)
        $cnpj = preg_replace('/[^0-9]/', '', (string)$request->get('cnpj'));
        $tokenSync = trim((string)$request->get('token_sync'));

        if (empty($cnpj) || empty($tokenSync)) {
            return ['autenticado' => false, 'mensagem' => 'CNPJ e Token de Sincronização são obrigatórios.', 'code' => 400];
        }

        // Busca o registro na config_notas removendo qualquer pontuação, barra, hífen ou espaço
        $configNota = ConfigNota::whereRaw("REGEXP_REPLACE(cnpj, '[^0-9]', '') = ?", [$cnpj])
            ->where('token_sync', $tokenSync)
            ->first();

        // Fallback para MySQL mais antigo sem REGEXP_REPLACE
        if (!$configNota) {
            $configNota = ConfigNota::whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(cnpj, '.', ''), '/', ''), '-', ''), ' ', '') = ?", [$cnpj])
                ->where('token_sync', $tokenSync)
                ->first();
        }

        if (!$configNota) {
            return ['autenticado' => false, 'mensagem' => 'Credenciais de sincronização inválidas (CNPJ ou Token incorreto).', 'code' => 401];
        }

        $empresa = Empresa::find($configNota->empresa_id);
        if (!$empresa || $empresa->status != 1) {
            return [
                'autenticado' => false,
                'mensagem' => $empresa ? ($empresa->mensagem_bloqueio ?: 'Empresa bloqueada/desativada no servidor.') : 'Empresa não encontrada.',
                'code' => 403
            ];
        }

        $planoEmpresa = $empresa->planoEmpresa;
        if ($planoEmpresa && strtotime(date('Y-m-d')) > strtotime($planoEmpresa->expiracao) && $planoEmpresa->expiracao != '0000-00-00') {
            return ['autenticado' => false, 'mensagem' => 'Plano do sistema expirado na nuvem.', 'code' => 403];
        }

        return ['autenticado' => true, 'empresa' => $empresa, 'configNota' => $configNota];
    }

    /**
     * POST /api/v1/sync/validar-empresa
     */
    public function validarEmpresa(Request $request)
    {
        $auth = $this->autenticarPdvLocal($request);
        if (!$auth['autenticado']) {
            return response()->json(['ativa' => false, 'mensagem' => $auth['mensagem']], $auth['code']);
        }

        return response()->json([
            'ativa' => true,
            'empresa_id' => $auth['empresa']->id,
            'razao_social' => $auth['empresa']->nome,
            'mensagem' => 'Empresa ativa e autorizada para o PDV Local.'
        ], 200);
    }

    /**
     * GET /api/v1/sync/carga-inicial
     */
    public function cargaInicial(Request $request)
    {
        $auth = $this->autenticarPdvLocal($request);
        if (!$auth['autenticado']) {
            return response()->json(['erro' => $auth['mensagem']], $auth['code']);
        }

        try {
            $empresaId = $auth['empresa']->id;
            $configNota = $auth['configNota'];

            $produtos = Produto::where('empresa_id', $empresaId)->where('inativo', false)->get();
            $clientes = Cliente::where('empresa_id', $empresaId)->where('inativo', false)->get();
            $usuarios = Usuario::where('empresa_id', $empresaId)->where('ativo', 1)->get()->makeVisible(['senha']);
            $funcionarios = Funcionario::where('empresa_id', $empresaId)->where('status_funcionario', 'Ativo')->get();
            $tributacao = Tributacao::where('empresa_id', $empresaId)->first();
            $naturezas = NaturezaOperacao::where('empresa_id', $empresaId)->get();

            $embalagens = [];
            if (\Schema::hasTable('produto_embalagens')) {
                $embalagens = DB::table('produto_embalagens')->get();
            }

            return response()->json([
                'empresa' => [
                    'id' => $auth['empresa']->id,
                    // Procura razao_social primeiro. Se não achar, procura nome.
                    'nome' => $auth['empresa']->razao_social ?? $auth['empresa']->nome ?? 'Empresa Não Configurada',
                    'nome_fantasia' => $auth['empresa']->nome_fantasia ?? '',

                    // Trata CNPJ e IE
                    'cpf_cnpj' => $auth['empresa']->cnpj ?? $auth['empresa']->cpf_cnpj ?? $configNota->cnpj ?? '',
                    'ie' => $auth['empresa']->ie ?? $auth['empresa']->inscricao_estadual ?? $configNota->ie ?? 'ISENTO',

                    // Procura por rua, logradouro ou endereco (em empresa ou config_nota)
                    'rua' => $auth['empresa']->logradouro ?? $auth['empresa']->endereco ?? $auth['empresa']->rua ?? $configNota->rua ?? $configNota->logradouro ?? '',
                    'numero' => $auth['empresa']->numero ?? $configNota->numero ?? 'S/N',
                    'bairro' => $auth['empresa']->bairro ?? $configNota->bairro ?? '',

                    // Procura por cidade ou municipio
                    'cidade' => $auth['empresa']->cidade ?? $configNota->municipio ?? $configNota->cidade ?? '',
                    'uf' => $auth['empresa']->uf ?? $auth['empresa']->estado ?? $configNota->uf ?? '',

                    // Procura telefone ou fone
                    'fone' => $auth['empresa']->telefone ?? $auth['empresa']->fone ?? $configNota->fone ?? ''
                ],

                'config_nota' => [
                    'csc' => $configNota->csc,
                    'csc_id' => $configNota->csc_id,
                    'certificado_a3' => $configNota->certificado_a3,
                    'numero_serie_nfce' => $configNota->numero_serie_nfce,
                    'ultimo_numero_nfce' => $configNota->ultimo_numero_nfce,
                ],
                'produtos' => $produtos,
                'produto_embalagens' => $embalagens,
                'clientes' => $clientes,
                'usuarios' => $usuarios,
                'funcionarios' => $funcionarios,
                'tributacao' => $tributacao,
                'naturezas' => $naturezas,
                'timestamp' => Carbon::now()->toDateTimeString()
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['erro' => 'Erro interno na carga inicial: ' . $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/v1/sync/atualizacoes
     */
    public function buscarAtualizacoes(Request $request)
    {
        $auth = $this->autenticarPdvLocal($request);
        if (!$auth['autenticado']) {
            return response()->json(['erro' => $auth['mensagem']], $auth['code']);
        }

        $empresaId = $auth['empresa']->id;
        $dataRef = $request->get('ultima_sincronizacao');

        if (!$dataRef) {
            return response()->json(['erro' => 'O parâmetro ultima_sincronizacao é obrigatório.'], 400);
        }

        $produtos = Produto::where('empresa_id', $empresaId)->where('updated_at', '>', $dataRef)->get();
        $clientes = Cliente::where('empresa_id', $empresaId)->where('updated_at', '>', $dataRef)->get();

        return response()->json([
            'produtos' => $produtos,
            'clientes' => $clientes,
            'timestamp' => Carbon::now()->toDateTimeString()
        ], 200);
    }

    /**
     * POST /api/v1/sync/enviar-vendas
     */
    public function receberVendasOffline(Request $request)
    {
        $vendas = $request->input('vendas', []);
        $empresaId = $request->input('empresa_id');
        $vendasProcessadas = [];

        DB::beginTransaction();
        try {
            foreach ($vendas as $vendaLocal) {
                $primeiroPagamento = '01';
                if (!empty($vendaLocal['pagamentos']) && isset($vendaLocal['pagamentos'][0]['tipo_pagamento'])) {
                    $primeiroPagamento = $vendaLocal['pagamentos'][0]['tipo_pagamento'];
                } elseif (!empty($vendaLocal['pagamentos']) && isset($vendaLocal['pagamentos'][0]['tipo'])) {
                    $primeiroPagamento = $vendaLocal['pagamentos'][0]['tipo'];
                }

                $formasMap = [
                    '01' => 'DINHEIRO', '03' => 'CARTAO DE CREDITO', '04' => 'CARTAO DE DEBITO',
                    '17' => 'PIX', '10' => 'VALE ALIMENTACAO', '11' => 'VALE REFEICAO'
                ];
                $textoFormaPagamento = $formasMap[$primeiroPagamento] ?? 'DINHEIRO';

                $chave = trim($vendaLocal['chave'] ?? '');
                $xmlAssinado = $vendaLocal['xml_assinado'] ?? '';
                $pathXmlFinal = '';

                // 1. SALVA O FICHEIRO XML FISICAMENTE NA PASTA
                if (!empty($chave) && !empty($xmlAssinado)) {
                    $pathXmlFinal = $chave . '.xml';
                    $pastaDestino = public_path('xml_nfce/');
                    if (!file_exists($pastaDestino)) {
                        mkdir($pastaDestino, 0755, true);
                    }
                    file_put_contents($pastaDestino . $pathXmlFinal, $xmlAssinado);
                }

                // 2. CORRIGE O FUSO HORÁRIO
                // Pega a hora exata do Desktop ou do momento atual
                $dataVendaFormatada = isset($vendaLocal['data_venda']) ? Carbon::parse($vendaLocal['data_venda'])->format('Y-m-d H:i:s') : date('Y-m-d H:i:s');

                // 3. GRAVA A VENDA FORÇANDO AS COLUNAS (Ignora a restrição do Laravel)
                $vendaWeb = new VendaCaixa();
                $vendaWeb->empresa_id         = $empresaId;
                $vendaWeb->usuario_id         = $vendaLocal['user_id'] ?? null;
                $vendaWeb->vendedor_id        = $vendaLocal['vendedor_id'] ?? null;
                $vendaWeb->cliente_id         = $vendaLocal['cliente_id'] ?? null;
                $vendaWeb->valor_total        = $vendaLocal['valor_total'];
                $vendaWeb->dinheiro_recebido  = $vendaLocal['dinheiro_recebido'] ?? $vendaLocal['valor_total'];
                $vendaWeb->valor_pagamento_1  = $vendaLocal['valor_total'];
                $vendaWeb->troco              = $vendaLocal['troco'] ?? 0;
                $vendaWeb->desconto           = $vendaLocal['desconto'] ?? 0;
                $vendaWeb->acrescimo          = $vendaLocal['acrescimo'] ?? 0;
                $vendaWeb->tipo_pagamento     = $primeiroPagamento;
                $vendaWeb->forma_pagamento    = $textoFormaPagamento;
                $vendaWeb->estado             = 'APROVADO';
                $vendaWeb->natureza_id        = 1;
                $vendaWeb->numero_sequencial  = $vendaLocal['numero_nota'] ?? 1;
                $vendaWeb->NFcNumero          = $vendaLocal['numero_nota'] ?? 0;
                $vendaWeb->chave              = $chave;
                $vendaWeb->path_xml           = $pathXmlFinal;
                $vendaWeb->signed_xml         = $xmlAssinado;
                $vendaWeb->nome               = $vendaLocal['nome'] ?? '';
                $vendaWeb->cpf                = $vendaLocal['cpf'] ?? $vendaLocal['cpf_cnpj'] ?? '';
                $vendaWeb->observacao         = $vendaLocal['observacao'] ?? '';
                $vendaWeb->pedido_delivery_id = 0;
                $vendaWeb->plataforma_origem  = 'DESKTOP';
                $vendaWeb->contigencia        = 1; // <-- GARANTIDO: Contigência ativa!
                $vendaWeb->data_emissao       = $dataVendaFormatada;
                $vendaWeb->data_registro      = $dataVendaFormatada;

                // <-- GARANTIDO: Desliga o sistema automático do Laravel para não acrescentar 3h!
                $vendaWeb->timestamps         = false;
                $vendaWeb->created_at         = $dataVendaFormatada;
                $vendaWeb->updated_at         = $dataVendaFormatada;

                $vendaWeb->save();

                // Salva os itens da venda
                if (!empty($vendaLocal['itens'])) {
                    foreach ($vendaLocal['itens'] as $item) {
                        $qtd = $item['quantidade'] ?? $item['qtd'] ?? 1;
                        $valUnit = $item['valor_unitario'] ?? $item['valor_venda'] ?? $item['valor'] ?? 0;
                        $prodId = $item['produto_id'] ?? $item['id'] ?? null;

                        ItemVendaCaixa::create([
                            'venda_caixa_id' => $vendaWeb->id,
                            'produto_id'     => $prodId,
                            'quantidade'     => $qtd,
                            'valor'          => $valUnit,
                            'observacao'     => '',
                        ]);
                    }
                }

                // 4. TRANSMISSÃO AUTOMÁTICA (Com o Array Exato que o NFePHP exige)
                if (!empty($xmlAssinado) && !empty($chave)) {
                    try {
                        $configNota = ConfigNota::where('empresa_id', $empresaId)->first();
                        if ($configNota) {
                            // Constrói o array estruturado para não gerar erro no construtor
                            $nfeConfig = [
                                "atualizacao" => date('Y-m-d H:i:s'),
                                "tpAmb"       => (int) $configNota->ambiente,
                                "razaosocial" => $configNota->razao_social,
                                "siglaUF"     => $configNota->uf ?? $configNota->UF,
                                "cnpj"        => preg_replace('/[^0-9]/', '', $configNota->cnpj),
                                "schemes"     => "PL_009_V4",
                                "versao"      => "4.00",
                                "CSC"         => $configNota->csc,
                                "CSCid"       => str_pad($configNota->csc_id, 6, "0", STR_PAD_LEFT)
                            ];

                            $nfeService = new \App\Services\NFCeService($nfeConfig, $empresaId);

                            $retornoSefaz = $nfeService->transmitirNfce($xmlAssinado, $chave, $vendaWeb->id);

                            if (!str_contains($retornoSefaz, 'Erro')) {
                                // Se precisar reafirmar que aprovou (embora já nasça como APROVADO acima)
                                DB::table('venda_caixas')->where('id', $vendaWeb->id)->update(['estado' => 'APROVADO']);
                            } else {
                                \Log::error("Erro na transmissão SEFAZ (Venda ID {$vendaWeb->id}): " . $retornoSefaz);
                            }
                        }
                    } catch (\Exception $ex) {
                        \Log::warning("Erro ao instanciar NFePHP ou transmitir ID {$vendaWeb->id}: " . $ex->getMessage());
                    }
                }

                $vendasProcessadas[] = $vendaLocal['id_local'];
            }

            DB::commit();

            return response()->json([
                'sucesso'              => true,
                'mensagem'             => count($vendasProcessadas) . ' venda(s) sincronizada(s) e processadas com sucesso!',
                'vendas_sincronizadas' => $vendasProcessadas
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'sucesso'  => false,
                'mensagem' => 'Erro ao processar venda no Web: ' . $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $login = strtolower(trim($request->login));
        $senha = trim($request->senha);

        $usuario = Usuario::where('empresa_id', $request->empresa_id)
            ->whereRaw('LOWER(login) = ?', [$login])
            ->where('ativo', 1)
            ->first();

        if ($usuario && Hash::check($senha, $usuario->senha)) {
            return response()->json(['sucesso' => true, 'usuario' => $usuario]);
        }

        return response()->json(['sucesso' => false, 'mensagem' => 'Usuário ou senha inválidos.'], 401);
    }
}
