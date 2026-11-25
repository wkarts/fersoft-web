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
use App\Helpers\StockMove;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SyncApiController extends Controller
{
    /**
     * Valida credenciais do PDV local e status da empresa na Nuvem.
     */
    private function autenticarPdvLocal(Request $request)
    {
        $cnpj = preg_replace('/[^0-9]/', '', (string) $request->input('cnpj', $request->get('cnpj')));
        $tokenSync = trim((string) $request->input('token_sync', $request->get('token_sync')));

        if (empty($cnpj) || empty($tokenSync)) {
            return [
                'autenticado' => false,
                'mensagem' => 'CNPJ e Token de Sincronização são obrigatórios.',
                'code' => 400
            ];
        }

        $configNota = ConfigNota::whereRaw("REGEXP_REPLACE(cnpj, '[^0-9]', '') = ?", [$cnpj])
            ->where('token_sync', $tokenSync)
            ->first();

        if (!$configNota) {
            $configNota = ConfigNota::whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(cnpj, '.', ''), '/', ''), '-', ''), ' ', '') = ?", [$cnpj])
                ->where('token_sync', $tokenSync)
                ->first();
        }

        if (!$configNota) {
            return [
                'autenticado' => false,
                'mensagem' => 'Credenciais de sincronização inválidas (CNPJ ou Token incorreto).',
                'code' => 401
            ];
        }

        $empresa = Empresa::find($configNota->empresa_id);
        if (!$empresa || (int) $empresa->status !== 1) {
            return [
                'autenticado' => false,
                'mensagem' => $empresa
                    ? ($empresa->mensagem_bloqueio ?: 'Empresa bloqueada/desativada no servidor.')
                    : 'Empresa não encontrada.',
                'code' => 403
            ];
        }

        $planoEmpresa = $empresa->planoEmpresa;
        if (
            $planoEmpresa &&
            !empty($planoEmpresa->expiracao) &&
            $planoEmpresa->expiracao !== '0000-00-00' &&
            strtotime(date('Y-m-d')) > strtotime($planoEmpresa->expiracao)
        ) {
            return [
                'autenticado' => false,
                'mensagem' => 'Plano do sistema expirado na nuvem.',
                'code' => 403
            ];
        }

        return [
            'autenticado' => true,
            'empresa' => $empresa,
            'configNota' => $configNota
        ];
    }

    /**
     * POST /api/v1/sync/validar-empresa
     */
    public function validarEmpresa(Request $request)
    {
        $auth = $this->autenticarPdvLocal($request);
        if (!$auth['autenticado']) {
            return response()->json([
                'ativa' => false,
                'mensagem' => $auth['mensagem']
            ], $auth['code']);
        }

        return response()->json([
            'ativa' => true,
            'empresa_id' => $auth['empresa']->id,
            'razao_social' => $auth['empresa']->razao_social ?? $auth['empresa']->nome,
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
            if (Schema::hasTable('produto_embalagens')) {
                $embalagens = DB::table('produto_embalagens')->get();
            }

            return response()->json([
                'empresa' => [
                    'id' => $auth['empresa']->id,
                    'nome' => $auth['empresa']->razao_social ?? $auth['empresa']->nome ?? 'Empresa Não Configurada',
                    'razao_social' => $auth['empresa']->razao_social ?? $auth['empresa']->nome ?? 'Empresa Não Configurada',
                    'nome_fantasia' => $auth['empresa']->nome_fantasia ?? '',
                    'cpf_cnpj' => $auth['empresa']->cnpj ?? $auth['empresa']->cpf_cnpj ?? $configNota->cnpj ?? '',
                    'ie' => $auth['empresa']->ie ?? $auth['empresa']->inscricao_estadual ?? $configNota->ie ?? 'ISENTO',
                    'rua' => $auth['empresa']->logradouro ?? $auth['empresa']->endereco ?? $auth['empresa']->rua ?? $configNota->rua ?? $configNota->logradouro ?? '',
                    'numero' => $auth['empresa']->numero ?? $configNota->numero ?? 'S/N',
                    'bairro' => $auth['empresa']->bairro ?? $configNota->bairro ?? '',
                    'cidade' => $auth['empresa']->cidade ?? $configNota->municipio ?? $configNota->cidade ?? '',
                    'uf' => $auth['empresa']->uf ?? $auth['empresa']->estado ?? $configNota->uf ?? '',
                    'fone' => $auth['empresa']->telefone ?? $auth['empresa']->fone ?? $configNota->fone ?? ''
                ],
                'config_nota' => [
                    'csc' => $configNota->csc,
                    'csc_id' => $configNota->csc_id,
                    'certificado_a3' => $configNota->certificado_a3,
                    'numero_serie_nfce' => $configNota->numero_serie_nfce,
                    'ultimo_numero_nfce' => $configNota->ultimo_numero_nfce,
                    'ambiente' => $configNota->ambiente ?? 2,
                    'nat_op_padrao' => $configNota->nat_op_padrao,
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

    private function resolverNaturezaOperacaoId(int $empresaId, ?ConfigNota $configNota, ?VendaCaixa $vendaExistente = null): int
    {
        $natId = (int) ($configNota->nat_op_padrao ?? 0);
        if ($natId > 0 && NaturezaOperacao::where('id', $natId)->exists()) {
            return $natId;
        }

        if ($vendaExistente && (int) $vendaExistente->natureza_id > 0 && NaturezaOperacao::where('id', $vendaExistente->natureza_id)->exists()) {
            return (int) $vendaExistente->natureza_id;
        }

        $fallback = NaturezaOperacao::where('empresa_id', $empresaId)->orderBy('id')->value('id')
            ?? NaturezaOperacao::orderBy('id')->value('id');

        if (!$fallback) {
            throw new \RuntimeException('Nenhuma Natureza de Operação configurada em config_notas ou natureza_operacaos.');
        }

        return (int) $fallback;
    }

    /**
     * POST /api/v1/sync/enviar-vendas
     * Recebe vendas/NFC-e do PDV Offline com idempotência, movimentação de estoque e confirmação de autorização.
     */
    public function receberVendasOffline(Request $request)
    {
        $auth = $this->autenticarPdvLocal($request);
        if (!$auth['autenticado']) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => $auth['mensagem']
            ], $auth['code']);
        }

        $empresaId = $auth['empresa']->id;
        $configNota = $auth['configNota'];
        $vendas = $request->input('vendas', []);

        if (!is_array($vendas) || empty($vendas)) {
            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Nenhuma venda enviada no lote.',
                'vendas_sincronizadas' => []
            ], 200);
        }

        $vendasProcessadas = [];
        $errosPorVenda = [];

        foreach ($vendas as $vendaLocal) {
            $idLocal = $vendaLocal['id_local'] ?? null;

            DB::beginTransaction();
            try {
                $chave = preg_replace('/\D/', '', (string) ($vendaLocal['chave'] ?? ''));
                $numeroNota = (int) ($vendaLocal['numero_nota'] ?? 0);
                $tipoEmissao = strtoupper((string) ($vendaLocal['tipo_emissao'] ?? 'NFCE'));
                $xmlAssinado = (string) ($vendaLocal['xml_assinado'] ?? '');
                $statusFiscalLocal = strtoupper((string) ($vendaLocal['status_fiscal'] ?? ''));

                $vendaExistente = null;
                if (!empty($chave)) {
                    $vendaExistente = VendaCaixa::where('empresa_id', $empresaId)
                        ->where('chave', $chave)
                        ->first();
                }

                if (!$vendaExistente && $numeroNota > 0 && $tipoEmissao !== 'NAO_FISCAL') {
                    $vendaExistente = VendaCaixa::where('empresa_id', $empresaId)
                        ->where('NFcNumero', $numeroNota)
                        ->where('plataforma_origem', 'DESKTOP')
                        ->first();
                }

                if ($vendaExistente) {
                    if ($vendaExistente->estado === 'APROVADO') {
                        DB::commit();
                        if ($idLocal !== null) {
                            $vendasProcessadas[] = $idLocal;
                        }
                        continue;
                    }
                    $vendaWeb = $vendaExistente;
                } else {
                    $vendaWeb = new VendaCaixa();
                }

                $naturezaId = $this->resolverNaturezaOperacaoId($empresaId, $configNota, $vendaExistente);
                $naturezaObj = NaturezaOperacao::find($naturezaId);
                $naoMovimentaEstoque = $naturezaObj ? (bool) $naturezaObj->nao_movimenta_estoque : false;

                $primeiroPagamento = '01';
                if (!empty($vendaLocal['pagamentos']) && isset($vendaLocal['pagamentos'][0]['tipo_pagamento'])) {
                    $primeiroPagamento = (string) $vendaLocal['pagamentos'][0]['tipo_pagamento'];
                } elseif (!empty($vendaLocal['pagamentos']) && isset($vendaLocal['pagamentos'][0]['tipo'])) {
                    $primeiroPagamento = (string) $vendaLocal['pagamentos'][0]['tipo'];
                }

                $formasMap = [
                    '01' => 'DINHEIRO',
                    '03' => 'CARTAO DE CREDITO',
                    '04' => 'CARTAO DE DEBITO',
                    '17' => 'PIX',
                    '10' => 'VALE ALIMENTACAO',
                    '11' => 'VALE REFEICAO',
                ];
                $textoFormaPagamento = $formasMap[$primeiroPagamento] ?? 'DINHEIRO';

                $pathXmlFinal = $vendaWeb->path_xml ?? '';
                if (!empty($chave)) {
                    $arquivoXmlSalvo = public_path('xml_nfce/' . $chave . '.xml');
                    if (!empty($xmlAssinado)) {
                        $pathXmlFinal = $chave . '.xml';
                        $pastaDestino = public_path('xml_nfce/');
                        if (!file_exists($pastaDestino)) {
                            mkdir($pastaDestino, 0755, true);
                        }
                        file_put_contents($arquivoXmlSalvo, $xmlAssinado);
                    } elseif (file_exists($arquivoXmlSalvo)) {
                        $pathXmlFinal = $chave . '.xml';
                        $xmlAssinado = (string) file_get_contents($arquivoXmlSalvo);
                    }
                }

                $tzLocal = config('app.timezone') ?: 'America/Bahia';
                $dataRef = $vendaLocal['data_emissao'] ?? $vendaLocal['data_venda'] ?? null;
                $dataVendaFormatada = $dataRef
                    ? Carbon::parse($dataRef)->setTimezone($tzLocal)->format('Y-m-d H:i:s')
                    : Carbon::now($tzLocal)->format('Y-m-d H:i:s');

                $filialId = isset($vendaLocal['filial_id']) && $vendaLocal['filial_id'] !== '' ? $vendaLocal['filial_id'] : null;

                $vendaWeb->empresa_id         = $empresaId;
                $vendaWeb->filial_id          = $filialId;
                $vendaWeb->usuario_id         = $vendaLocal['user_id'] ?? null;
                $vendaWeb->vendedor_id        = $vendaLocal['vendedor_id'] ?? null;
                $vendaWeb->cliente_id         = $vendaLocal['cliente_id'] ?? null;
                $vendaWeb->valor_total        = (float) ($vendaLocal['valor_total'] ?? 0);
                $vendaWeb->dinheiro_recebido  = (float) ($vendaLocal['dinheiro_recebido'] ?? ($vendaLocal['valor_total'] ?? 0));
                $vendaWeb->valor_pagamento_1  = (float) ($vendaLocal['valor_total'] ?? 0);
                $vendaWeb->troco              = (float) ($vendaLocal['troco'] ?? 0);
                $vendaWeb->desconto           = (float) ($vendaLocal['desconto'] ?? 0);
                $vendaWeb->acrescimo          = (float) ($vendaLocal['acrescimo'] ?? 0);
                $vendaWeb->tipo_pagamento     = $primeiroPagamento;
                $vendaWeb->forma_pagamento    = $textoFormaPagamento;
                $vendaWeb->natureza_id        = $naturezaId;
                $vendaWeb->numero_sequencial  = $numeroNota ?: 1;
                $vendaWeb->NFcNumero          = $numeroNota;
                $vendaWeb->chave              = $chave ?: null;
                $vendaWeb->path_xml           = $pathXmlFinal;
                $vendaWeb->signed_xml         = $xmlAssinado;
                $vendaWeb->nome               = $vendaLocal['nome'] ?? '';
                $vendaWeb->cpf                = $vendaLocal['cpf'] ?? $vendaLocal['cpf_cnpj'] ?? '';
                $vendaWeb->observacao         = $vendaLocal['observacao'] ?? '';
                $vendaWeb->pedido_delivery_id = 0;
                $vendaWeb->plataforma_origem  = 'DESKTOP';
                $vendaWeb->contigencia        = !empty($vendaLocal['contigencia']) ? 1 : 0;
                $vendaWeb->data_emissao       = $dataVendaFormatada;
                $vendaWeb->data_registro      = $dataVendaFormatada;

                $jaAprovadaNoSefaz = ($statusFiscalLocal === 'AUTORIZADA')
                    || (strpos($xmlAssinado, '<cStat>100</cStat>') !== false);

                $vendaWeb->estado = $jaAprovadaNoSefaz ? 'APROVADO' : 'CONTINGENCIA';
                $vendaWeb->timestamps = false;
                $vendaWeb->created_at = $dataVendaFormatada;
                $vendaWeb->updated_at = $dataVendaFormatada;
                $vendaWeb->save();

                if (ItemVendaCaixa::where('venda_caixa_id', $vendaWeb->id)->count() === 0 && !empty($vendaLocal['itens'])) {
                    $stockMove = new StockMove();
                    foreach ($vendaLocal['itens'] as $item) {
                        $qtd = (float) ($item['quantidade'] ?? $item['qtd'] ?? 1);
                        $valUnit = (float) ($item['valor_unitario'] ?? $item['valor_venda'] ?? $item['valor'] ?? 0);
                        $prodId = $item['produto_id'] ?? $item['id'] ?? null;
                        $produtoModel = $prodId ? Produto::where('empresa_id', $empresaId)->find($prodId) : null;

                        $cfop = $produtoModel ? (int) ($produtoModel->CFOP_saida_estadual ?? 5102) : 5102;
                        if ($naturezaObj && (int) $naturezaObj->sobrescreve_cfop === 1 && !empty($naturezaObj->CFOP_saida_estadual)) {
                            $cfop = (int) $naturezaObj->CFOP_saida_estadual;
                        }

                        ItemVendaCaixa::create([
                            'venda_caixa_id' => $vendaWeb->id,
                            'produto_id'     => $prodId,
                            'quantidade'     => $qtd,
                            'valor'          => $valUnit,
                            'observacao'     => $item['observacao'] ?? '',
                            'cfop'           => $cfop,
                            'valor_custo'    => $produtoModel ? (float) ($produtoModel->valor_compra ?? 0) : 0,
                        ]);

                        if (!$naoMovimentaEstoque && $produtoModel && (int) ($produtoModel->gerenciar_estoque ?? 0) === 1) {
                            $fatorConversao = isset($item['fator_conversao']) && (float) $item['fator_conversao'] > 0
                                ? (float) $item['fator_conversao']
                                : 1;
                            $stockMove->downStock((int) $produtoModel->id, $qtd * $fatorConversao, $filialId);
                        }
                    }
                }

                if (!$jaAprovadaNoSefaz && $tipoEmissao !== 'NAO_FISCAL' && !empty($xmlAssinado) && !empty($chave) && $configNota) {
                    $nfeConfig = [
                        'atualizacao' => date('Y-m-d H:i:s'),
                        'tpAmb'       => (int) ($configNota->ambiente ?? 2),
                        'razaosocial' => $configNota->razao_social ?? ($auth['empresa']->razao_social ?? $auth['empresa']->nome),
                        'siglaUF'     => $configNota->uf ?? $configNota->UF ?? 'BA',
                        'cnpj'        => preg_replace('/[^0-9]/', '', (string) $configNota->cnpj),
                        'schemes'     => 'PL_009_V4',
                        'versao'      => '4.00',
                        'CSC'         => $configNota->csc,
                        'CSCid'       => str_pad((string) $configNota->csc_id, 6, '0', STR_PAD_LEFT),
                    ];

                    $nfeService = new \App\Services\NFCeService($nfeConfig, $empresaId);
                    $retornoSefaz = $nfeService->transmitirNfce($xmlAssinado, $chave, $vendaWeb->id);

                    $retornoStr = is_array($retornoSefaz) ? json_encode($retornoSefaz) : (string) $retornoSefaz;
                    if (stripos($retornoStr, 'Erro') === false && stripos($retornoStr, 'Rejei') === false) {
                        $arquivoXmlAut = public_path('xml_nfce/' . $chave . '.xml');
                        $dadosUpdate = ['estado' => 'APROVADO'];
                        if (file_exists($arquivoXmlAut)) {
                            $dadosUpdate['signed_xml'] = (string) file_get_contents($arquivoXmlAut);
                            $dadosUpdate['path_xml'] = $chave . '.xml';
                        }
                        DB::table('venda_caixas')->where('id', $vendaWeb->id)->update($dadosUpdate);
                        $jaAprovadaNoSefaz = true;
                    } else {
                        Log::error("Falha na transmissão SEFAZ (Venda Web ID {$vendaWeb->id}): " . $retornoStr);
                        $errosPorVenda[] = [
                            'id_local' => $idLocal,
                            'mensagem' => 'SEFAZ não autorizou: ' . $retornoStr,
                        ];
                    }
                }

                DB::commit();

                if ($jaAprovadaNoSefaz || $tipoEmissao === 'NAO_FISCAL') {
                    if ($idLocal !== null) {
                        $vendasProcessadas[] = $idLocal;
                    }
                }
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Erro ao processar venda offline id_local={$idLocal}: " . $e->getMessage());
                $errosPorVenda[] = [
                    'id_local' => $idLocal,
                    'mensagem' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'sucesso'              => count($vendasProcessadas) > 0 || count($errosPorVenda) === 0,
            'mensagem'             => count($vendasProcessadas) . ' venda(s) sincronizada(s) com sucesso!'
                . (count($errosPorVenda) > 0 ? ' (' . count($errosPorVenda) . ' pendente(s)/com pendência SEFAZ)' : ''),
            'vendas_sincronizadas' => $vendasProcessadas,
            'erros'                => $errosPorVenda,
        ], 200);
    }

    /**
     * POST /api/v1/sync/login
     */
    public function login(Request $request)
    {
        $login = strtolower(trim((string) $request->login));
        $senha = trim((string) $request->senha);

        $usuario = Usuario::where('empresa_id', $request->empresa_id)
            ->whereRaw('LOWER(login) = ?', [$login])
            ->where('ativo', 1)
            ->first();

        if ($usuario && Hash::check($senha, $usuario->senha)) {
            return response()->json(['sucesso' => true, 'usuario' => $usuario]);
        }

        return response()->json(['sucesso' => false, 'mensagem' => 'Usuário ou senha inválidos.'], 401);
    }

    /**
     * POST /api/v1/sync/transmitir-sefaz
     * Recebe XML do PDV Desktop, transmite/autoriza na SEFAZ via NFCeService
     * e devolve protocolo + XML autorizado para o PDV sair de CONTINGENCIA.
     */
    public function transmitirSefazOffline(Request $request)
    {
        $auth = $this->autenticarPdvLocal($request);
        if (!$auth['autenticado']) {
            return response()->json([
                'sucesso' => false,
                'autorizada' => false,
                'mensagem' => $auth['mensagem']
            ], $auth['code']);
        }

        $empresa = $auth['empresa'];
        $configNota = $auth['configNota'];
        $empresaId = $empresa->id;

        $chave = preg_replace('/\D/', '', (string) $request->input('chave', ''));
        $xml = (string) $request->input('xml', '');

        $xmlLimpo = preg_replace('/^<\?xml[^>]*\?>\s*/i', '', $xml);
        $xmlLimpo = preg_replace('/^<NFeProcContingencia[^>]*>|<\/NFeProcContingencia>\s*$/i', '', $xmlLimpo);
        if (preg_match('/<NFe[\s\S]*<\/NFe>/i', $xmlLimpo, $matchNFe)) {
            $xmlLimpo = $matchNFe[0];
        } else {
            $xmlLimpo = trim($xmlLimpo);
        }

        if (empty($chave) || empty($xmlLimpo)) {
            return response()->json([
                'sucesso' => false,
                'autorizada' => false,
                'mensagem' => 'Chave de acesso e XML são obrigatórios para transmissão SEFAZ.'
            ], 400);
        }

        try {
            $nfeConfig = [
                'atualizacao' => date('Y-m-d H:i:s'),
                'tpAmb'       => (int) ($configNota->ambiente ?? 2),
                'razaosocial' => $configNota->razao_social ?? ($empresa->razao_social ?? $empresa->nome),
                'siglaUF'     => $configNota->uf ?? $configNota->UF ?? 'BA',
                'cnpj'        => preg_replace('/[^0-9]/', '', (string) ($configNota->cnpj ?? $empresa->cnpj)),
                'schemes'     => 'PL_009_V4',
                'versao'      => '4.00',
                'CSC'         => $configNota->csc,
                'CSCid'       => str_pad((string) $configNota->csc_id, 6, '0', STR_PAD_LEFT),
            ];

            $nfeService = new \App\Services\NFCeService($nfeConfig, $empresaId);

            $xmlAssinado = (stripos($xmlLimpo, '<Signature') === false)
                ? $nfeService->sign($xmlLimpo)
                : $xmlLimpo;

            $retorno = $nfeService->transmitirNfce($xmlAssinado, $chave, 0);
            $retornoStr = is_array($retorno) ? json_encode($retorno) : trim((string) $retorno);

            $autorizada = $nfeService->tipoUltimoResultadoTransmissao() === 'autorizada'
                || (preg_match('/^\d{10,20}$/', $retornoStr) === 1);

            if ($autorizada) {
                $caminhoXmlAutorizado = public_path('xml_nfce/' . $chave . '.xml');
                $xmlAutorizado = file_exists($caminhoXmlAutorizado)
                    ? file_get_contents($caminhoXmlAutorizado)
                    : null;

                return response()->json([
                    'sucesso'        => true,
                    'autorizada'     => true,
                    'cStat'          => '100',
                    'nProt'          => $retornoStr,
                    'dhRecbto'       => date('c'),
                    'xMotivo'        => 'Autorizado o uso da NFC-e',
                    'xml_autorizado' => $xmlAutorizado,
                ], 200);
            }

            Log::warning("SEFAZ não autorizou chave {$chave} (empresa {$empresaId}): {$retornoStr}");
            return response()->json([
                'sucesso'    => false,
                'autorizada' => false,
                'mensagem'   => $retornoStr ?: 'SEFAZ não autorizou o XML.',
            ], 200);
        } catch (\Throwable $e) {
            Log::error("Erro em transmitirSefazOffline chave={$chave}: " . $e->getMessage());
            return response()->json([
                'sucesso'    => false,
                'autorizada' => false,
                'mensagem'   => 'Erro ao transmitir XML na SEFAZ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/sync/fechar-caixa
     * Registra/sincroniza o fechamento de turno do PDV Offline na retaguarda web.
     */
    public function fecharCaixaOffline(Request $request)
    {
        $auth = $this->autenticarPdvLocal($request);
        if (!$auth['autenticado']) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => $auth['mensagem']
            ], $auth['code']);
        }

        $empresa = $auth['empresa'];
        $empresaId = (int) $empresa->id;

        $turnoIdLocal = (int) $request->input('turno_id_local', 0);
        $numeroCaixa = (int) $request->input('numero_caixa', 1);
        $usuarioId = $request->input('usuario_id') ? (int) $request->input('usuario_id') : null;
        $saldoInicial = (float) $request->input('saldo_inicial', 0);
        $valorDinheiroCaixa = (float) $request->input('valor_dinheiro_caixa', 0);
        $totalVendas = (float) $request->input('total_vendas', 0);
        $totalSangrias = (float) $request->input('total_sangrias', 0);
        $totalSuprimentos = (float) $request->input('total_suprimentos', 0);
        $dataAbertura = $request->input('data_abertura');
        $dataFechamento = $request->input('data_fechamento') ?: date('Y-m-d H:i:s');
        $movimentacoes = $request->input('movimentacoes', []);

        DB::beginTransaction();
        try {
            $dtAb = $dataAbertura ? Carbon::parse($dataAbertura)->format('Y-m-d H:i:s') : date('Y-m-d H:i:s');
            $dtFech = Carbon::parse($dataFechamento)->format('Y-m-d H:i:s');
            $marcadorTurno = "PDV_OFF_TURNO_{$turnoIdLocal}_CAIXA_{$numeroCaixa}";

            // Verifica se já existe registro de fechamento desse turno PDV para evitar duplicidade
            $aberturaExistente = null;
            if ($turnoIdLocal > 0 && Schema::hasTable('abertura_caixas')) {
                if (Schema::hasColumn('abertura_caixas', 'observacao')) {
                    $aberturaExistente = DB::table('abertura_caixas')
                        ->where('empresa_id', $empresaId)
                        ->where('observacao', 'LIKE', "%{$marcadorTurno}%")
                        ->first();
                }
            }

            $ultimaVendaNfceId = (int) (VendaCaixa::where('empresa_id', $empresaId)->orderBy('id', 'desc')->value('id') ?? 0);
            $ultimaVendaNfeId = 0;
            if (Schema::hasTable('vendas')) {
                $ultimaVendaNfeId = (int) (DB::table('vendas')->where('empresa_id', $empresaId)->orderBy('id', 'desc')->value('id') ?? 0);
            }

            if (Schema::hasTable('abertura_caixas')) {
                $payloadAbertura = [
                    'empresa_id' => $empresaId,
                    'usuario_id' => $usuarioId ?: (Usuario::where('empresa_id', $empresaId)->value('id') ?? 1),
                    'valor' => $saldoInicial,
                    'primeira_venda_nfce' => $ultimaVendaNfceId,
                    'primeira_venda_nfe' => $ultimaVendaNfeId,
                    'ultima_venda_nfce' => $ultimaVendaNfceId,
                    'ultima_venda_nfe' => $ultimaVendaNfeId,
                    'status' => 1, // fechado
                    'updated_at' => $dtFech,
                ];

                if (Schema::hasColumn('abertura_caixas', 'valor_dinheiro_caixa')) {
                    $payloadAbertura['valor_dinheiro_caixa'] = $valorDinheiroCaixa;
                }
                if (Schema::hasColumn('abertura_caixas', 'data_registro')) {
                    $payloadAbertura['data_registro'] = $dtAb;
                }
                if (Schema::hasColumn('abertura_caixas', 'observacao')) {
                    $payloadAbertura['observacao'] = "{$marcadorTurno} | Vendas: {$totalVendas} | Sangrias: {$totalSangrias} | Suprimentos: {$totalSuprimentos}";
                }

                if ($aberturaExistente) {
                    DB::table('abertura_caixas')
                        ->where('id', $aberturaExistente->id)
                        ->update($payloadAbertura);
                    $aberturaWebId = $aberturaExistente->id;
                } else {
                    $payloadAbertura['created_at'] = $dtAb;
                    $aberturaWebId = DB::table('abertura_caixas')->insertGetId($payloadAbertura);
                }

                // Lança movimentações do turno se ainda não lançadas para este turno PDV
                if (is_array($movimentacoes) && !empty($movimentacoes)) {
                    foreach ($movimentacoes as $mov) {
                        $tipoMov = strtoupper((string) ($mov['tipo'] ?? ''));
                        $valorMov = (float) ($mov['valor'] ?? 0);
                        $obsMov = trim((string) ($mov['observacao'] ?? ''));
                        $obsMarcada = trim("{$obsMov} [{$marcadorTurno}]");
                        $dtMov = !empty($mov['data_movimento'])
                            ? Carbon::parse($mov['data_movimento'])->format('Y-m-d H:i:s')
                            : $dtFech;

                        if ($valorMov <= 0) continue;

                        if ($tipoMov === 'SANGRIA' && Schema::hasTable('sangria_caixas')) {
                            $jaExiste = DB::table('sangria_caixas')
                                ->where('empresa_id', $empresaId)
                                ->where('observacao', $obsMarcada)
                                ->exists();
                            if (!$jaExiste) {
                                DB::table('sangria_caixas')->insert([
                                    'empresa_id' => $empresaId,
                                    'usuario_id' => $usuarioId ?: 1,
                                    'valor' => $valorMov,
                                    'observacao' => $obsMarcada,
                                    'created_at' => $dtMov,
                                    'updated_at' => $dtMov,
                                ]);
                            }
                        } elseif ($tipoMov === 'SUPRIMENTO' && Schema::hasTable('suprimento_caixas')) {
                            $jaExiste = DB::table('suprimento_caixas')
                                ->where('empresa_id', $empresaId)
                                ->where('observacao', $obsMarcada)
                                ->exists();
                            if (!$jaExiste) {
                                DB::table('suprimento_caixas')->insert([
                                    'empresa_id' => $empresaId,
                                    'usuario_id' => $usuarioId ?: 1,
                                    'valor' => $valorMov,
                                    'observacao' => $obsMarcada,
                                    'created_at' => $dtMov,
                                    'updated_at' => $dtMov,
                                ]);
                            }
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Fechamento de caixa sincronizado com o sistema web!',
                'abertura_id' => $aberturaWebId ?? null,
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Erro em fecharCaixaOffline turno={$turnoIdLocal} empresa={$empresaId}: " . $e->getMessage());
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Erro ao registrar fechamento de caixa na nuvem: ' . $e->getMessage(),
            ], 500);
        }
    }
}
