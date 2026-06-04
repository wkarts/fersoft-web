<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\NFSeNacionalService;
use App\Models\ManifestaNfseTomada;
use App\Models\Fornecedor;
use App\Models\Compra;

class NFSeTomadaController extends BaseController 
{
    protected $model;
    protected $formTitle;
    protected $redirectPage = '/nfse-tomadas';

    public function __construct() {
        $this->model = Compra::class;
        $this->formTitle = 'NFS-e Tomada Nacional';
        parent::__construct();
    }

    protected function rules(): array { return []; }
    protected function messages(): array { return []; }

    public function index(Request $request)
    {
        $emp_id = session('user_logged')['empresa'];

        $query = \App\Models\ManifestaNfseTomada::query()
            ->where('manifesta_nfse_tomadas.empresa_id', $emp_id);

        $numero_nota = $request->numero_nota;
        $fornecedor = $request->fornecedor;
        $data_inicial = $request->data_inicial ?: date('01/m/Y'); 
        $data_final = $request->data_final ?: date('t/m/Y');      

        if ($numero_nota) {
            $query->where('manifesta_nfse_tomadas.numero_nota', $numero_nota);
        }

        if ($fornecedor) {
            $cnpjLimpo = preg_replace('/[^0-9]/', '', $fornecedor);
            
            $query->where(function($q) use ($fornecedor, $cnpjLimpo) {
                $q->where('manifesta_nfse_tomadas.prestador_nome', 'LIKE', "%{$fornecedor}%");
                if (!empty($cnpjLimpo)) {
                    $q->orWhere('manifesta_nfse_tomadas.chave', 'LIKE', "%{$cnpjLimpo}%");
                }
            });
        }

        if ($data_inicial && $data_final) {
            $dIni = \Carbon\Carbon::createFromFormat('d/m/Y', $data_inicial)->format('Y-m-d 00:00:00');
            $dFim = \Carbon\Carbon::createFromFormat('d/m/Y', $data_final)->format('Y-m-d 23:59:59');
            $query->whereBetween('manifesta_nfse_tomadas.data_emissao', [$dIni, $dFim]);
        }

        $query->leftJoin('filials', 'filials.id', '=', 'manifesta_nfse_tomadas.filial_id');

        // 🔥 CORREÇÃO DOS ÍCONES 'C' e 'F' 🔥
        // Agora verifica diretamente se o ID da compra está vinculado, forçando a luz acender
        $docs = $query->select([
                'manifesta_nfse_tomadas.*', 
                'filials.descricao as nome_filial', 
                
                DB::raw("IF(manifesta_nfse_tomadas.fatura_salva = 1, 1, EXISTS(
                    SELECT 1 FROM conta_pagars 
                    INNER JOIN fornecedors ON fornecedors.id = conta_pagars.fornecedor_id
                    WHERE conta_pagars.empresa_id = $emp_id 
                    AND DATE(conta_pagars.data_emissao) = DATE(manifesta_nfse_tomadas.data_emissao)
                    AND CAST(conta_pagars.numero_nota_fiscal AS UNSIGNED) = CAST(manifesta_nfse_tomadas.numero_nota AS UNSIGNED)
                    AND REPLACE(REPLACE(REPLACE(fornecedors.cpf_cnpj, '.', ''), '/', ''), '-', '') COLLATE utf8mb4_unicode_ci = 
                    (CASE 
                        WHEN CHAR_LENGTH(manifesta_nfse_tomadas.chave) = 50 THEN SUBSTRING(manifesta_nfse_tomadas.chave, 10, 14)
                        ELSE '' 
                    END) COLLATE utf8mb4_unicode_ci
                )) as ja_no_pagar"),
                
                DB::raw("IF(manifesta_nfse_tomadas.compra_servico_id > 0, 1, EXISTS(
                    SELECT 1 FROM compras 
                    INNER JOIN fornecedors ON fornecedors.id = compras.fornecedor_id
                    WHERE compras.empresa_id = $emp_id 
                    AND DATE(compras.data_emissao) = DATE(manifesta_nfse_tomadas.data_emissao)
                    AND CAST(compras.nf AS UNSIGNED) = CAST(manifesta_nfse_tomadas.numero_nota AS UNSIGNED)
                    AND REPLACE(REPLACE(REPLACE(fornecedors.cpf_cnpj, '.', ''), '/', ''), '-', '') COLLATE utf8mb4_unicode_ci = 
                    (CASE 
                        WHEN CHAR_LENGTH(manifesta_nfse_tomadas.chave) = 50 THEN SUBSTRING(manifesta_nfse_tomadas.chave, 10, 14)
                        ELSE '' 
                    END) COLLATE utf8mb4_unicode_ci
                )) as ja_comprado")
            ])
            ->orderBy('manifesta_nfse_tomadas.data_emissao', 'desc')
            ->paginate(20);

        $filiais = \App\Models\Filial::where('empresa_id', $emp_id)->get();

        return view('nfse_tomadas.index', compact('docs', 'filiais', 'data_inicial', 'data_final', 'numero_nota', 'fornecedor'))
            ->with(['title' => 'NFS-e Tomadas']);
    }

    public function sincronizarManual(Request $request) 
    {
        try {
            $emp_id = session('user_logged')['empresa'];
            $local = $request->local ?? 'matriz'; 
            $filial_id = ($local == 'matriz') ? null : $local;

            if ($local == 'matriz') {
                $cert = DB::table('certificados')->where('empresa_id', $emp_id)->first();
                $conf = DB::table('config_notas')->where('empresa_id', $emp_id)->first();
                
                if (!$cert || !$conf) {
                    throw new \Exception("Configurações de notas ou certificado da Matriz não encontrados para a empresa ID: " . $emp_id);
                }
                
                $servico = new NFSeNacionalService($conf->cnpj, $cert->arquivo, $cert->senha);
            } else {
                $f = DB::table('filials')->where('id', $local)->first();
                
                if (!$f) {
                    throw new \Exception("Filial não encontrada no sistema.");
                }
                
                $servico = new NFSeNacionalService($f->cnpj, $f->arquivo_certificado, $f->senha_certificado);
            }
          
            $nsuAtual = (int)(ManifestaNfseTomada::where('empresa_id', $emp_id)
                ->where('filial_id', $filial_id)
                ->max('nsu') ?? 0);

            $totalNotasProcessadas = 0;
            $limiteDeConsultasPorClique = 30;
            $consultasRealizadas = 0;

            while ($consultasRealizadas < $limiteDeConsultasPorClique) {
                $consultasRealizadas++;

                $resultado = $servico->consultar($nsuAtual);

                if (!$resultado) {
                    break;
                }

                $notasNesseLote = 0;
                $maiorNsuEncontradoNoLote = $nsuAtual;

                if (isset($resultado['LoteDFe']) && is_array($resultado['LoteDFe']) && count($resultado['LoteDFe']) > 0) {
                    foreach ($resultado['LoteDFe'] as $doc) {
                        $chave = trim($doc['ChaveAcesso']);
                        $nsuReal = $doc['NSU'] ?? $doc['nsu'] ?? $doc['numNSU'] ?? null;

                        if (!$nsuReal) {
                            continue;
                        }

                        $nsuReal = (int)$nsuReal;
                        if ($nsuReal > $maiorNsuEncontradoNoLote) {
                            $maiorNsuEncontradoNoLote = $nsuReal;
                        }

                        $xmlGzip = base64_decode($doc['ArquivoXml']);
                        $xmlString = gzdecode($xmlGzip);
                        
                        // 🔥 SALVA O XML FISICAMENTE NO SERVIDOR PARA O FINANCEIRO PODER IMPRIMIR
                        try {
                            $pastaServico = public_path('xml_servico');

                            // Cria a pasta se ela ainda não existir
                            if (!file_exists($pastaServico)) { 
                                @mkdir($pastaServico, 0777, true); 
                            }

                            // Salva o XML exatamente onde o sistema já costuma ler
                            file_put_contents($pastaServico . '/' . $chave . '.xml', $xmlString);

                        } catch (\Exception $e) {
                            \Log::error("Erro ao salvar XML físico da NFS-e: " . $e->getMessage());
                        }
                        
                        $xmlStringLimpo = preg_replace('/xmlns="[^"]+"/', '', $xmlString);
                        $xmlStringLimpo = preg_replace('/xmlns:[^=]+="[^"]+"/', '', $xmlStringLimpo);
                        $xml = simplexml_load_string($xmlStringLimpo);
                        
                        if (!$xml) {
                            continue;
                        }

                        $inf = null;
                        if (isset($xml->infNFSe)) {
                            $inf = $xml->infNFSe;
                        } elseif (isset($xml->NFSe->infNFSe)) {
                            $inf = $xml->NFSe->infNFSe;
                        } elseif (isset($xml->compNFSe->NFSe->infNFSe)) {
                            $inf = $xml->compNFSe->NFSe->infNFSe;
                        } else {
                            $inf = $xml;
                        }

                        $numeroNota = '0';
                        if (isset($inf->nNFSe)) {
                            $numeroNota = (string)$inf->nNFSe;
                        } elseif (isset($inf->identificacaoNFSe->numero)) {
                            $numeroNota = (string)$inf->identificacaoNFSe->numero;
                        } else {
                            $numeroNota = substr($chave, 25, 9);
                        }

                        $prestadorNome = 'Fornecedor sem Nome';
                        $prestadorCnpj = '';

                        if (isset($inf->emit->xNome)) {
                            $prestadorNome = (string)$inf->emit->xNome;
                            $prestadorCnpj = (string)$inf->emit->CNPJ;
                        } elseif (isset($inf->prestador->razSocial)) {
                            $prestadorNome = (string)$inf->prestador->razSocial;
                            $prestadorCnpj = (string)$inf->prestador->CNPJ;
                        } elseif (isset($inf->prestador->identificacaoPrestador->cnpj)) {
                            $prestadorCnpj = (string)$inf->prestador->identificacaoPrestador->cnpj;
                            $prestadorNome = (string)($inf->prestador->xNome ?? 'Fornecedor - ' . $prestadorCnpj);
                        } else {
                            $prestadorCnpj = substr($chave, 6, 14);
                        }

                        $vServicoBruto = 0;
                        $vLiquido = 0;

                        if (isset($inf->DPS->infDPS->valores->vServPrest->vServ)) {
                            $vServicoBruto = (float)$inf->DPS->infDPS->valores->vServPrest->vServ;
                        } elseif (isset($inf->valores->vServPrest->vServ)) {
                            $vServicoBruto = (float)$inf->valores->vServPrest->vServ;
                        } elseif (isset($inf->valores->vServ)) {
                            $vServicoBruto = (float)$inf->valores->vServ;
                        }

                        if (isset($inf->valores->vLiq)) {
                            $vLiquido = (float)$inf->valores->vLiq;
                        } else {
                            $vLiquido = $vServicoBruto; 
                        }

                        if ($vServicoBruto == 0 && $vLiquido > 0) {
                            $vServicoBruto = $vLiquido;
                        }

                        $dataEmissao = null;
                        if (isset($inf->dhEmi)) {
                            $dataEmissao = date('Y-m-d', strtotime((string)$inf->dhEmi));
                        } elseif (isset($inf->dCompet)) {
                            $dataEmissao = date('Y-m-d', strtotime((string)$inf->dCompet));
                        } elseif (isset($inf->dtEmit)) {
                            $dataEmissao = date('Y-m-d', strtotime((string)$inf->dtEmit));
                        } elseif (isset($doc['DataHoraGeracao'])) {
                            $dataEmissao = date('Y-m-d', strtotime((string)$doc['DataHoraGeracao']));
                        } else {
                            $dataEmissao = date('Y-m-d'); 
                        }

                        if (!empty($prestadorCnpj) && $prestadorNome != 'Fornecedor sem Nome') {
    
                            // 1. Formata o CNPJ no padrão do ERP (com pontos e traço)
                            $cnpjNumeros = preg_replace('/[^0-9]/', '', $prestadorCnpj);
                            $cnpjFormatado = strlen($cnpjNumeros) == 14 
                                ? substr($cnpjNumeros,0,2).'.'.substr($cnpjNumeros,2,3).'.'.substr($cnpjNumeros,5,3).'/'.substr($cnpjNumeros,8,4).'-'.substr($cnpjNumeros,12,2) 
                                : $prestadorCnpj;

                            // 2. Busca o fornecedor tentando com e sem pontuação
                            $fornecedor = Fornecedor::where('empresa_id', $emp_id)
                                ->where(function($q) use ($cnpjFormatado, $cnpjNumeros) {
                                    $q->where('cpf_cnpj', $cnpjFormatado)
                                      ->orWhere('cpf_cnpj', $cnpjNumeros);
                                })->first();

                            if (!$fornecedor) {
                                // Limpa lixos numéricos que algumas prefeituras colocam no nome
                                $nomeLimpo = preg_replace('/^[0-9\.\-\/]+\s+/', '', $prestadorNome);

                                Fornecedor::create([
                                    'empresa_id'   => $emp_id,
                                    'razao_social' => $nomeLimpo,
                                    'nome_fantasia'=> $nomeLimpo,
                                    'cpf_cnpj'     => $cnpjFormatado, // Agora salva formatado!
                                    'ie_rg'        => 'ISENTO',
                                    'rua'          => isset($inf->emit->enderNac->xLgr) ? (string)$inf->emit->enderNac->xLgr : 'Não Informada',
                                    'numero'       => isset($inf->emit->enderNac->nro) ? (string)$inf->emit->enderNac->nro : 'S/N',
                                    'bairro'       => isset($inf->emit->enderNac->xBairro) ? (string)$inf->emit->enderNac->xBairro : 'Não Informado',
                                    'cidade_id'    => 1, 
                                    'cep'          => isset($inf->emit->enderNac->CEP) ? (string)$inf->emit->enderNac->CEP : '00000000',
                                    'contribuinte' => 1,
                                    'cod_pais'     => '1058'
                                ]);
                            }
                        }

                        $this->disableNextModelAudit();

                        ManifestaNfseTomada::updateOrCreate(
                            [
                                'chave' => $chave, 
                                'empresa_id' => $emp_id
                            ],
                            [
                                'filial_id'          => $filial_id,
                                'nsu'                => $nsuReal,
                                'numero_nota'        => $numeroNota,
                                'prestador_nome'     => $prestadorNome,
                                'prestador_cnpj_cpf' => $prestadorCnpj,
                                'valor_servico'      => $vServicoBruto,
                                'valor_liquido'      => $vLiquido,
                                'data_emissao'       => $dataEmissao,
                            ]
                        );
                        
                        $notasNesseLote++;
                        $totalNotasProcessadas++;
                    }
                }

                if ($notasNesseLote > 0) {
                    $nsuAtual = $maiorNsuEncontradoNoLote;
                } else {
                    $nsuAtual++;
                    
                    $this->disableNextModelAudit();
                    
                    ManifestaNfseTomada::updateOrCreate(
                        [
                            'chave' => 'AVANCO_COMPAT_FILA_' . $nsuAtual, 
                            'empresa_id' => $emp_id
                        ],
                        [
                            'filial_id'          => $filial_id,
                            'nsu'                => $nsuAtual,
                            'numero_nota'        => '0',
                            'prestador_nome'     => 'SINC_AVANCO_MECANICO',
                            'prestador_cnpj_cpf' => '00000000000000',
                            'valor_servico'      => 0,
                            'valor_liquido'      => 0,
                            'data_emissao'       => date('Y-m-d'),
                            'fatura_salva'       => 0
                        ]
                    );
                }

                if ($notasNesseLote > 0 && $notasNesseLote < 50) {
                    break;
                }
            }

            $this->markManualAuditExecuted();

            if ($totalNotasProcessadas > 0) {
                session()->flash('mensagem_sucesso', "Sucesso! Foram sincronizadas $totalNotasProcessadas notas fiscais com dados reais.");
            } else {
                session()->flash('mensagem_sucesso', "Sincronização realizada. Procurando novos registros na fila. Se necessário clique novamente.");
            }

        } catch (\Exception $e) {
            session()->flash('mensagem_erro', 'Erro na sincronização: ' . $e->getMessage());
        }
        
        return redirect($this->redirectPage);
    }
  
    public function detalhesLançamento($id)
    {
        $nota = ManifestaNfseTomada::findOrFail($id);
        $emp_id = session('user_logged')['empresa'];

        $veiculos = \App\Models\Veiculo::where('empresa_id', $emp_id)->get();
        $categoriasDeConta = \App\Models\CategoriaConta::where('empresa_id', $emp_id)
            ->where('tipo', 'pagar')
            ->orderBy('nome', 'asc')
            ->get();

        // 🔥 BUSCA A DESCRIÇÃO DIRETAMENTE NO ARQUIVO FÍSICO 🔥
        $descricaoServico = 'Prestação de serviços gerais discriminada no corpo do documento nacional.';
        $caminhoXml = public_path('xml_servico/' . $nota->chave . '.xml');
        
        if (file_exists($caminhoXml)) {
            $xmlString = file_get_contents($caminhoXml);
            $xmlClean = preg_replace('/ xmlns[^=]*="[^"]*"/i', '', $xmlString);
            $xml = simplexml_load_string($xmlClean);

            // Rastreio da tag <cServ>
            if (isset($xml->infNFSe->DPS->infDPS->serv->cServ->xDescServ)) {
                $descricaoServico = (string)$xml->infNFSe->DPS->infDPS->serv->cServ->xDescServ;
            } elseif (isset($xml->DPS->infDPS->serv->cServ->xDescServ)) {
                $descricaoServico = (string)$xml->DPS->infDPS->serv->cServ->xDescServ;
            } elseif (isset($xml->infNFSe->serv->cServ->xDescServ)) {
                $descricaoServico = (string)$xml->infNFSe->serv->cServ->xDescServ;
            } elseif (isset($xml->infNFSe->DPS->infDPS->serv->xDescServ)) {
                $descricaoServico = (string)$xml->infNFSe->DPS->infDPS->serv->xDescServ;
            } elseif (isset($xml->infNFSe->servico->Discriminacao)) {
                $descricaoServico = (string)$xml->infNFSe->servico->Discriminacao;
            }
        }

        // 🔥 CORREÇÃO DA VARIÁVEL: Enviando explicitamente como 'descricao_servico'
        return view('nfse_tomadas.importar_painel', [
            'nota' => $nota,
            'veiculos' => $veiculos,
            'categoriasDeConta' => $categoriasDeConta,
            'descricao_servico' => $descricaoServico
        ])->with(['title' => $this->formTitle]);
    }
  
    public function salvarImportacaoPainel(Request $request, $id)
    {
        $request->validate([
            'categoria_conta_id' => 'required',
            'prazo_pagamento' => 'required',
            'quantidade_parcelas' => 'required|integer|min:1'
        ]);

        try {
            DB::beginTransaction();

            $nota = \App\Models\ManifestaNfseTomada::findOrFail($id);
            $emp_id = session('user_logged')['empresa'];
            $usuario_id = session('user_logged')['id'];

            $cnpjNumeros = preg_replace('/[^0-9]/', '', $nota->prestador_cnpj_cpf);
            $cnpjFormatado = strlen($cnpjNumeros) == 14 
                ? substr($cnpjNumeros,0,2).'.'.substr($cnpjNumeros,2,3).'.'.substr($cnpjNumeros,5,3).'/'.substr($cnpjNumeros,8,4).'-'.substr($cnpjNumeros,12,2) 
                : $nota->prestador_cnpj_cpf;

            $fornecedor = \App\Models\Fornecedor::where('empresa_id', $emp_id)->where('cpf_cnpj', $cnpjFormatado)->first();
            if (!$fornecedor) {
                $fornecedor = \App\Models\Fornecedor::create([
                    'empresa_id'   => $emp_id,
                    'razao_social' => $nota->prestador_nome,
                    'nome_fantasia'=> $nota->prestador_nome,
                    'cpf_cnpj'     => $cnpjFormatado,
                    'cidade_id'    => 1,
                    'ie_rg'        => 'ISENTO'
                ]);
            }

            $this->disableNextModelAudit();
            $compra = \App\Models\Compra::create([
                'fornecedor_id' => $fornecedor->id,
                'usuario_id' => $usuario_id,
                'nf' => ltrim($nota->numero_nota, '0'),
                'data_emissao' => $nota->data_emissao,
                'valor' => $nota->valor_servico,
                'veiculo_id' => $request->veiculo_id,
                'estado' => 'IMPORTADO',
                'xml_importado' => 1,
                'xml_path' => $nota->chave . '.xml',
                'categoria_conta_id' => $request->categoria_conta_id,
                'chave' => $nota->chave,
                'empresa_id' => $emp_id,
                'filial_id' => $nota->filial_id,
                'observacao' => 'NFS-e Nacional Tomada Nº ' . $nota->numero_nota,
                'numero_emissao' => 0,
            ]);

            $servicoProduto = \App\Models\Produto::where('empresa_id', $emp_id)->where('referencia', '140101')->first();
            if (!$servicoProduto) {
                $servicoProduto = \App\Models\Produto::create([
                    'nome' => 'SERVICO TOMADO - NFSE NACIONAL', 'referencia' => '140101',
                    'valor_compra' => $nota->valor_servico, 'valor_venda' => $nota->valor_servico, 'gerenciar_estoque' => 0,
                    'empresa_id' => $emp_id, 'locais' => '["-1"]', 'tipo_item' => '09', 'unidade_compra' => 'UN', 'unidade_venda' => 'UN', 'conversao_unitaria' => 1
                ]);
            }

            \App\Models\ItemCompra::create([
                'compra_id' => $compra->id,
                'produto_id' => $servicoProduto->id,
                'quantidade' => 1,
                'valor_unitario' => $nota->valor_servico,
                'unidade_compra' => 'UN',
                'cfop_entrada' => '1933'
            ]);

            $vServico = $nota->valor_servico;
            $vLiquido = $nota->valor_liquido > 0 ? $nota->valor_liquido : $vServico;

            // ========================================================
            // 1. LEITURA DO XML PARA DESCOBRIR SE TEVE ISS RETIDO
            // ========================================================
            $valorIssRetido = 0;
            $caminhoXml = public_path('xml_servico/' . $nota->chave . '.xml');

            if (file_exists($caminhoXml)) {
                $xmlString = file_get_contents($caminhoXml);
                $xmlClean = preg_replace('/ xmlns[^=]*="[^"]*"/i', '', $xmlString);
                $xmlObj = simplexml_load_string($xmlClean);

                // A. Tenta Padrão Nacional (NFS-e Nacional / DPS)
                // tpRetISSQN: 1 = Não retido, 2 = Retido pelo Tomador
                $tpRetISSQN = null;
                if (isset($xmlObj->infNFSe->DPS->infDPS->valores->trib->tribMun->tpRetISSQN)) {
                    $tpRetISSQN = (string)$xmlObj->infNFSe->DPS->infDPS->valores->trib->tribMun->tpRetISSQN;
                } elseif (isset($xmlObj->DPS->infDPS->valores->trib->tribMun->tpRetISSQN)) {
                    $tpRetISSQN = (string)$xmlObj->DPS->infDPS->valores->trib->tribMun->tpRetISSQN;
                }

                if ($tpRetISSQN == '2') {
                    if (isset($xmlObj->infNFSe->valores->vISSQN)) {
                        $valorIssRetido = (float)$xmlObj->infNFSe->valores->vISSQN;
                    } elseif (isset($xmlObj->valores->vISSQN)) {
                        $valorIssRetido = (float)$xmlObj->valores->vISSQN;
                    }
                }

                // B. Tenta Padrão ABRASF (Prefeituras antigas locais)
                // IssRetido: 1 = Sim, 2 = Não
                if (isset($xmlObj->Nfse->InfNfse->Servico->Valores->IssRetido)) {
                    if ((string)$xmlObj->Nfse->InfNfse->Servico->Valores->IssRetido == '1') {
                        if (isset($xmlObj->Nfse->InfNfse->Servico->Valores->ValorIssRetido)) {
                            $valorIssRetido = (float)$xmlObj->Nfse->InfNfse->Servico->Valores->ValorIssRetido;
                        } elseif (isset($xmlObj->Nfse->InfNfse->Servico->Valores->ValorIss)) {
                            $valorIssRetido = (float)$xmlObj->Nfse->InfNfse->Servico->Valores->ValorIss;
                        }
                    }
                } elseif (isset($xmlObj->infNFSe->servico->valores->issRetido)) {
                    if ((string)$xmlObj->infNFSe->servico->valores->issRetido == '1') {
                        $valorIssRetido = (float)$xmlObj->infNFSe->servico->valores->valorIssRetido;
                    }
                }
            }

            // ========================================================
            // 2. CÁLCULO DAS RETENÇÕES
            // ========================================================
            // Subtrai o ISS Retido da diferença bruta antes de calcular os federais
            $diffFederal = round($vServico - $vLiquido - $valorIssRetido, 2);
            $vPis = 0; $vCofins = 0; $vCsll = 0; $vIr = 0;

            if ($diffFederal > 0) {
                $vPis = round($vServico * 0.0065, 2);
                $vCofins = round($vServico * 0.03, 2);
                $vCsll = round($vServico * 0.01, 2);
                
                $somaImpostos = $vPis + $vCofins + $vCsll;
                $vIr = round($diffFederal - $somaImpostos, 2);
                if ($vIr < 0) $vIr = 0;
            }

            $retencoes = [
                'valor_iss' => $valorIssRetido, // <- AGORA RECEBE O VALOR REAL
                'valor_pis' => $vPis, 
                'valor_cofins' => $vCofins,
                'valor_ir' => $vIr, 
                'valor_csll' => $vCsll, 
                'valor_inss' => 0
            ];

            $qtdParcelas = (int)$request->quantidade_parcelas > 0 ? (int)$request->quantidade_parcelas : 1;
            $valorParcela = round($vLiquido / $qtdParcelas, 2);
            $somaAcumulada = 0;

            $descricaoServico = !empty($nota->prestador_nome) ? "Prestação de Serviço - " . $nota->prestador_nome : "Serviço Tomado";

            for ($i = 1; $i <= $qtdParcelas; $i++) {
                $vencimento = date('Y-m-d', strtotime($nota->data_emissao . " + " . ($request->prazo_pagamento * $i) . " days"));
                $valorFinal = ($i == $qtdParcelas) ? round($vLiquido - $somaAcumulada, 2) : $valorParcela;
                $somaAcumulada += $valorFinal;

                \App\Models\ContaPagar::create([
                    'compra_id' => $compra->id,
                    'fornecedor_id' => $fornecedor->id,
                    'data_vencimento' => $vencimento,
                    'data_emissao' => $nota->data_emissao,
                    'valor_integral' => $valorFinal,
                    'valor_original' => $valorFinal,
                    'status' => false,
                    'referencia' => "NFS-e " . $nota->numero_nota . " - " . substr($descricaoServico, 0, 50) . " ($i/$qtdParcelas)",
                    'categoria_id' => $request->categoria_conta_id,
                    'empresa_id' => $emp_id,
                    'filial_id' => $nota->filial_id,
                    'veiculo_id' => $request->veiculo_id,
                    'numero_nota_fiscal' => ltrim($nota->numero_nota, '0'),
                    'usuario_id' => $usuario_id,
                    ...($i == 1 ? $retencoes : []) 
                ]);
            }

            // 🔥 GARANTIA DE VÍNCULO BLINDADA (Força a gravação ignorando o bloqueio do Model) 🔥
            $nota->compra_servico_id = $compra->id;
            $nota->fatura_salva = 1;
            $nota->save();

            DB::commit();
            session()->flash('mensagem_sucesso', "Sucesso! NFS-e Nº {$nota->numero_nota} foi importada e provisionada com as retenções no Contas a Pagar.");
            return redirect('/nfse-tomadas');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('mensagem_erro', 'Erro ao processar importação: ' . $e->getMessage());
        }
    }  	

    public function imprimirEspelho($id)
    {
        try {
            $nota = \App\Models\ManifestaNfseTomada::findOrFail($id);
            $empresa_id = session('user_logged')['empresa'];

            // 🔥 AGORA LÊ DA PASTA FÍSICA ONDE SALVAMOS O ARQUIVO 🔥
            $caminhoXml = public_path('xml_servico/' . $nota->chave . '.xml');
            $xmlString = null;
            
            if (file_exists($caminhoXml)) {
                $xmlString = file_get_contents($caminhoXml);
            } elseif (!empty($nota->xml_base64)) {
                $xmlString = base64_decode($nota->xml_base64); // Mantido por segurança de notas velhas
            } elseif (!empty($nota->xml_puro)) {
                $xmlString = $nota->xml_puro;
            }

            if ($xmlString) {
                $xmlClean = preg_replace('/ xmlns[^=]*="[^"]*"/i', '', $xmlString);
                $xml = simplexml_load_string($xmlClean);

                $descricaoServico = 'Descrição não informada no XML.';
                
                // 🔥 NOVO MAPEAMENTO COM A TAG <cServ> DA IMAGEM 🔥
                if (isset($xml->infNFSe->DPS->infDPS->serv->cServ->xDescServ)) {
                    $descricaoServico = (string)$xml->infNFSe->DPS->infDPS->serv->cServ->xDescServ;
                } elseif (isset($xml->DPS->infDPS->serv->cServ->xDescServ)) {
                    $descricaoServico = (string)$xml->DPS->infDPS->serv->cServ->xDescServ;
                } elseif (isset($xml->infNFSe->serv->cServ->xDescServ)) {
                    $descricaoServico = (string)$xml->infNFSe->serv->cServ->xDescServ;
                } elseif (isset($xml->infNFSe->DPS->infDPS->serv->xDescServ)) {
                    $descricaoServico = (string)$xml->infNFSe->DPS->infDPS->serv->xDescServ;
                } elseif (isset($xml->infNFSe->servico->Discriminacao)) {
                    $descricaoServico = (string)$xml->infNFSe->servico->Discriminacao;
                }

                return view('nfse.visualizar', [
                    'xml' => $xml, 
                    'descricao_servico' => $descricaoServico,
                    'title' => 'DANFSE - Nota ' . $nota->numero_nota
                ]);
            }

            return view('nfse_tomadas.espelho_layout', [
                'nota' => $nota,
                'descricao_servico' => 'Serviço Tomado - XML não encontrado na pasta',
                'title' => 'DANFSE - Nota ' . $nota->numero_nota
            ]);

        } catch (\Exception $e) {
            return "Erro ao renderizar a impressão da NFS-e: " . $e->getMessage();
        }
    }
}