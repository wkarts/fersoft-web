<?php

namespace App\Services;

use App\Models\ContaPagar;
use App\Models\ContaReceber;
use App\Models\ItemContaEmpresa;
use App\Models\ConfiguracaoContabil;
use App\Models\Cliente;
use App\Models\Fornecedor;
use App\Models\AdiantamentoMovimentacao;
use App\Models\PlanoContasContabil;

class ContabilidadeService
{
    public function processarAuditoria($dataInicio, $dataFim, $filialId)
    {
        $userLogged = session('user_logged');
        $empresaId = null;

        if (is_array($userLogged)) {
            $empresaId = $userLogged['empresa_id'] ?? null;
            if (!$empresaId && isset($userLogged['empresa'])) {
                $empresa = $userLogged['empresa'];
                $empresaId = is_numeric($empresa) ? $empresa : (is_array($empresa) ? ($empresa['id'] ?? null) : ($empresa->id ?? null));
            }
        } elseif (is_object($userLogged)) {
            $empresaId = $userLogged->empresa_id ?? null;
        }

        if (!$empresaId) {
            $empresaId = auth()->check() ? auth()->user()->empresa_id : session('empresa_id');
        }

        $filialId = is_object($filialId) ? ($filialId->id ?? null) : (string)$filialId;

        $dtInicio = date('Y-m-d 00:00:00', strtotime($dataInicio));
        $dtFim = date('Y-m-d 23:59:59', strtotime($dataFim));

        $montarConta = function($planoObj, $codigoAlternativo) {
            if (!$planoObj) return $codigoAlternativo;
            if (isset($planoObj->codigo_acesso) && trim((string)$planoObj->codigo_acesso) !== '') {
                return trim((string)$planoObj->codigo_acesso);
            }
            if (isset($planoObj->classificador) && trim((string)$planoObj->classificador) !== '') {
                return trim((string)$planoObj->classificador);
            }
            return $codigoAlternativo;
        };

        // Função "Tradutora" para buscar a conta real através do ID salvo nas configurações
        $obterContaConfig = function($idDaConta, $padrao) use ($montarConta) {
            if (empty($idDaConta)) return $padrao;
            $contaPlano = PlanoContasContabil::find($idDaConta);
            return $montarConta($contaPlano, $padrao);
        };

        $config = ConfiguracaoContabil::where('empresa_id', $empresaId)->first();

        // Mapeamento corrigido com os nomes exatos do seu banco de dados
        $contaFornPadrao    = $obterContaConfig(optional($config)->conta_fornecedor_padrao, '21004');
        $contaClientePadrao = $obterContaConfig(optional($config)->conta_cliente_padrao, '11203');
        $contaJurosDespesa  = $obterContaConfig(optional($config)->conta_juros_pagos, '57101');
        $contaMultaDespesa  = $obterContaConfig(optional($config)->conta_multas_pagas, '57119');
        $contaJurosReceita  = $obterContaConfig(optional($config)->conta_juros_recebidos, '41103');
        $contaMultaReceita  = $obterContaConfig(optional($config)->conta_multas_recebidas, '41104');
        $contaIrPassivo     = $obterContaConfig(optional($config)->conta_irrf_recolher, '21010');
        $contaInssPassivo   = $obterContaConfig(optional($config)->conta_inss_recolher, '21011');
        $contaIssPassivo    = $obterContaConfig(optional($config)->conta_iss_recolher, '21012');
        $contaPccPassivo    = $obterContaConfig(optional($config)->conta_pis_recolher, '21013');

        $auditoria = [
            'pagar_prov'    => [],
            'pagar_baixa'   => [],
            'receber_prov'  => [],
            'receber_baixa' => [],
            'manual'        => [],
            'adiantamentos' => []
        ];

        $limparTexto = function($texto) { return trim((string)$texto); };

        $filtroFilial = function($q) use ($filialId) {
            if ($filialId === 'matriz' || empty($filialId) || $filialId === '-1') {
                $q->where(function($sub) {
                    $sub->whereNull('filial_id')
                        ->orWhere('filial_id', 0)
                        ->orWhere('filial_id', -1);
                });
            } else {
                $q->where('filial_id', $filialId);
            }
        };

        $extrairDocumento = function($obj) {
            if (!$obj) return '';
            $doc = $obj->cpf_cnpj ?? $obj->cnpj_cpf ?? $obj->cnpj ?? $obj->cpf ?? $obj->documento ?? '';
            if (empty($doc)) {
                foreach ($obj->getAttributes() as $key => $value) {
                    if (strpos(strtolower($key), 'cnpj') !== false || strpos(strtolower($key), 'cpf') !== false) {
                        $doc = $value; break;
                    }
                }
            }
            return substr(preg_replace('/[^0-9]/', '', (string)$doc), 0, 14);
        };

        $lerConfigCategoria = function($cat) {
            $geraProvisao = true; $ignorarTerceiro = false;
            if ($cat) {
                if (isset($cat->gerar_provisao) && (int)$cat->gerar_provisao === 0) $geraProvisao = false;
                elseif (isset($cat->gera_provisao) && (int)$cat->gera_provisao === 0) $geraProvisao = false;
                if (isset($cat->ignorar_terceiro) && (int)$cat->ignorar_terceiro === 1) $ignorarTerceiro = true;
                elseif (isset($cat->ignora_terceiro) && (int)$cat->ignora_terceiro === 1) $ignorarTerceiro = true;
            }
            return (object)['geraProvisao' => $geraProvisao, 'ignorarTerceiro' => $ignorarTerceiro];
        };

        // ==========================================
        // 1. PAGAR (PROVISÃO)
        // ==========================================
        $pagarProv = ContaPagar::with(['categoria.contaDespesa', 'categoria.contaProvisao', 'fornecedor'])
            ->where('empresa_id', $empresaId)
            ->where(function($q) use ($dtInicio, $dtFim) {
                $q->whereBetween('data_emissao', [$dtInicio, $dtFim])
                    ->orWhereBetween('data_vencimento', [$dtInicio, $dtFim]);
            })
            ->where($filtroFilial)->get();

        foreach ($pagarProv as $item) {
            $dataBase = (!empty($item->data_emissao) && $item->data_emissao !== '0000-00-00' && $item->data_emissao !== '0000-00-00 00:00:00') ? $item->data_emissao : $item->data_vencimento;

            if (strtotime($dataBase) < strtotime($dtInicio) || strtotime($dataBase) > strtotime($dtFim)) {
                continue;
            }

            $cat = $item->categoria;
            $cfg = $lerConfigCategoria($cat);
            if (!$cfg->geraProvisao) continue;

            $entidade = $limparTexto($item->fornecedor->razao_social ?? 'Não informado');
            $nomeCategoria = !empty($cat->nome) ? trim($cat->nome) : 'Sem Categoria';
            $cnpjFornecedor = $cfg->ignorarTerceiro ? '' : $extrairDocumento($item->fornecedor);
            $debitoDespesa = $montarConta(optional($cat)->contaDespesa, '---');
            $creditoForn = $montarConta(optional($cat)->contaProvisao, '---');
            $numNota = $item->numero_nota_fiscal ?? $item->nf ?? '';

            $valorLiquido = (float)($item->valor_original > 0 ? $item->valor_original : $item->valor_integral);
            $valorRetencoes = (float)$item->valor_ir + (float)$item->valor_inss + (float)$item->valor_iss + (float)$item->valor_pis + (float)$item->valor_cofins + (float)$item->valor_csll;
            $valorBruto = $valorLiquido + $valorRetencoes;

            $auditoria['pagar_prov'][] = [
                'id' => $item->id, 'data' => date('d/m/Y', strtotime($dataBase)),
                'entidade' => $entidade, 'historico' => mb_substr("Pagar (Prov) - " . ($numNota ? "NF: $numNota - " : "") . $entidade, 0, 60, 'UTF-8'),
                'categoria' => $nomeCategoria, 'debito' => $debitoDespesa, 'credito' => $creditoForn,
                'terceiro_debito' => '', 'terceiro_credito' => $cnpjFornecedor,
                'documento' => $numNota, 'valor' => number_format($valorBruto, 2, ',', '.'),
                'status' => ($debitoDespesa === '---') ? 'ERRO' : (($creditoForn === '---') ? 'SEM PROVISÃO' : 'OK'),
                'categoria_id' => $item->categoria_id, 'conta_banco_id' => null,
            ];

            $gerarRetencao = function($valorImposto, $nomeImposto, $contaImpostoCredito) use (&$auditoria, $item, $entidade, $creditoForn, $nomeCategoria, $numNota, $cnpjFornecedor, $dataBase) {
                if ((float)$valorImposto > 0) {
                    $auditoria['pagar_prov'][] = [
                        'id' => $item->id, 'data' => date('d/m/Y', strtotime($dataBase)),
                        'entidade' => $entidade, 'historico' => mb_substr("Retenção $nomeImposto - " . ($numNota ? "NF: $numNota - " : "") . $entidade, 0, 60, 'UTF-8'),
                        'categoria' => "Retenção ($nomeCategoria)", 'debito' => $creditoForn, 'credito' => $contaImpostoCredito,
                        'terceiro_debito' => $cnpjFornecedor, 'terceiro_credito' => '', 'documento' => $numNota,
                        'valor' => number_format((float)$valorImposto, 2, ',', '.'), 'status' => ($creditoForn === '---' || $contaImpostoCredito === '---') ? 'ERRO' : 'OK',
                        'categoria_id' => $item->categoria_id, 'conta_banco_id' => null,
                    ];
                }
            };
            $gerarRetencao($item->valor_ir, 'IRRF', $contaIrPassivo);
            $gerarRetencao($item->valor_inss, 'INSS', $contaInssPassivo);
            $gerarRetencao($item->valor_iss, 'ISS', $contaIssPassivo);
            $gerarRetencao((float)$item->valor_pis + (float)$item->valor_cofins + (float)$item->valor_csll, 'PCC', $contaPccPassivo);
        }

        // ==========================================
        // 2. PAGAR (BAIXA)
        // ==========================================
        $pagarBaixa = ContaPagar::with(['categoria.contaProvisao', 'categoria.contaDespesa', 'fornecedor'])
            ->where('empresa_id', $empresaId)
            ->whereBetween('data_pagamento', [$dtInicio, $dtFim])
            ->where($filtroFilial)
            ->get();

        foreach ($pagarBaixa as $cp) {
            $cat = $cp->categoria;
            $cfg = $lerConfigCategoria($cat);
            $nomeCategoria = !empty($cat->nome) ? trim($cat->nome) : 'Sem Categoria';
            $cnpjFornecedor = $cfg->ignorarTerceiro ? '' : $extrairDocumento($cp->fornecedor ?? null);

            $itemBank = ItemContaEmpresa::with('conta.contaContabil')->where('conta_pagar_id', $cp->id)->first();
            $movAdiantamento = AdiantamentoMovimentacao::where('conta_pagar_id', $cp->id)->first();

            if (!$itemBank && !$movAdiantamento) {
                continue;
            }

            $dataTransacao = $itemBank ? $itemBank->data_pagamento : ($movAdiantamento ? $movAdiantamento->data : $cp->data_pagamento);
            $dataFormatada = date('d/m/Y', strtotime($dataTransacao));
            $idBanco = $itemBank ? $itemBank->conta_id : null;

            $codC_Banco = '---';
            if ($itemBank && $itemBank->conta) {
                $codC_Banco = $montarConta(optional($itemBank->conta)->contaContabil, '---');
            }

            if ($codC_Banco === '---' && $movAdiantamento) {
                $codC_Banco = '11501';
            }

            $lancamentoDireto = !$cfg->geraProvisao;
            $codD_Forn = $lancamentoDireto ? $montarConta(optional($cat)->contaDespesa, '---') : $montarConta(optional($cat)->contaProvisao, $contaFornPadrao);

            $numNota = $cp->numero_nota_fiscal ?? $cp->nf ?? '';
            $entidade = $limparTexto($cp->fornecedor->razao_social ?? 'Baixa');
            $textoHistorico = $lancamentoDireto ? "Pgto Direto" : "Pagar (Baixa)";

            $valorPago = (float)(isset($cp->valor_pago) && $cp->valor_pago > 0 ? $cp->valor_pago : ($cp->valor_integral ?? 0));
            $juros = (float)($cp->juros ?? 0);
            $multa = (float)($cp->multa ?? 0);
            $valorPrincipal = $valorPago - $juros - $multa;

            if ($valorPrincipal > 0) {
                $auditoria['pagar_baixa'][] = [
                    'id' => $cp->id, 'data' => $dataFormatada,
                    'entidade' => $entidade, 'historico' => mb_substr("$textoHistorico - " . ($numNota ? "NF: $numNota - " : "") . $entidade, 0, 60, 'UTF-8'),
                    'categoria' => $nomeCategoria, 'debito' => $codD_Forn, 'credito' => $codC_Banco,
                    'terceiro_debito' => $cnpjFornecedor, 'terceiro_credito' => '', 'documento' => $numNota,
                    'valor' => number_format($valorPrincipal, 2, ',', '.'),
                    'status' => ($codC_Banco === '---') ? 'ERRO' : (($codD_Forn === '---') ? 'SEM PROVISÃO' : 'OK'),
                    'categoria_id' => $cp->categoria_id, 'conta_banco_id' => $idBanco,
                ];
            }

            if ($juros > 0) {
                $auditoria['pagar_baixa'][] = [
                    'id' => $cp->id, 'data' => $dataFormatada,
                    'entidade' => $entidade, 'historico' => mb_substr("Juros Pagos - " . ($numNota ? "NF: $numNota - " : "") . $entidade, 0, 60, 'UTF-8'),
                    'categoria' => 'Despesa Financeira (Juros)', 'debito' => $contaJurosDespesa, 'credito' => $codC_Banco,
                    'terceiro_debito' => '', 'terceiro_credito' => '', 'documento' => $numNota,
                    'valor' => number_format($juros, 2, ',', '.'), 'status' => ($codC_Banco === '---') ? 'ERRO' : 'OK',
                    'categoria_id' => $cp->categoria_id, 'conta_banco_id' => $idBanco,
                ];
            }

            if ($multa > 0) {
                $auditoria['pagar_baixa'][] = [
                    'id' => $cp->id, 'data' => $dataFormatada,
                    'entidade' => $entidade, 'historico' => mb_substr("Multa Paga - " . ($numNota ? "NF: $numNota - " : "") . $entidade, 0, 60, 'UTF-8'),
                    'categoria' => 'Despesa Financeira (Multa)', 'debito' => $contaMultaDespesa, 'credito' => $codC_Banco,
                    'terceiro_debito' => '', 'terceiro_credito' => '', 'documento' => $numNota,
                    'valor' => number_format($multa, 2, ',', '.'), 'status' => ($codC_Banco === '---') ? 'ERRO' : 'OK',
                    'categoria_id' => $cp->categoria_id, 'conta_banco_id' => $idBanco,
                ];
            }
        }

        // ==========================================
        // 3. RECEBER (PROVISÃO)
        // ==========================================
        $receberProv = ContaReceber::with(['cliente', 'categoria.contaProvisao', 'categoria.contaDespesa'])
            ->where('empresa_id', $empresaId)
            ->where(function($q) use ($dtInicio, $dtFim) {
                $q->whereBetween('nf_data_emissao', [$dtInicio, $dtFim])
                    ->orWhereBetween('data_vencimento', [$dtInicio, $dtFim]);
            })
            ->where($filtroFilial)->get();

        foreach ($receberProv as $item) {
            $dataBase = (!empty($item->nf_data_emissao) && $item->nf_data_emissao !== '0000-00-00' && $item->nf_data_emissao !== '0000-00-00 00:00:00') ? $item->nf_data_emissao : $item->data_vencimento;

            if (strtotime($dataBase) < strtotime($dtInicio) || strtotime($dataBase) > strtotime($dtFim)) {
                continue;
            }

            $cat = $item->categoria;
            $cfg = $lerConfigCategoria($cat);
            if (!$cfg->geraProvisao) continue;

            $entidade = $limparTexto($item->cliente->razao_social ?? 'Não informado');
            $cnpjCliente = $cfg->ignorarTerceiro ? '' : $extrairDocumento($item->cliente);
            $debito = $montarConta(optional($cat)->contaProvisao, $contaClientePadrao);
            $credito = $montarConta(optional($cat)->contaDespesa, '---');
            $numNota = $item->numero_nota_fiscal ?? $item->nf_numero ?? '';

            $auditoria['receber_prov'][] = [
                'id' => $item->id, 'data' => date('d/m/Y', strtotime($dataBase)),
                'entidade' => $entidade, 'historico' => mb_substr("Receber (Prov) - " . ($numNota ? "NF: $numNota - " : "") . $entidade, 0, 60, 'UTF-8'),
                'categoria' => !empty($cat->nome) ? trim($cat->nome) : 'Sem Categoria', 'debito' => $debito, 'credito' => $credito,
                'terceiro_debito' => $cnpjCliente, 'terceiro_credito' => '', 'documento' => $numNota,
                'valor' => number_format($item->valor_integral, 2, ',', '.'),
                'status' => ($credito === '---') ? 'ERRO' : (($debito === '---') ? 'SEM PROVISÃO' : 'OK'),
                'categoria_id' => $item->categoria_id, 'conta_banco_id' => null,
            ];
        }

        // ==========================================
        // 4. RECEBER (BAIXA)
        // ==========================================
        $receberBaixa = ContaReceber::with(['cliente', 'categoria.contaProvisao', 'categoria.contaDespesa'])
            ->where('empresa_id', $empresaId)
            ->whereBetween('data_recebimento', [$dtInicio, $dtFim])
            ->where($filtroFilial)
            ->get();

        foreach ($receberBaixa as $cr) {
            $cat = $cr->categoria;
            $cfg = $lerConfigCategoria($cat);
            $nomeCategoria = !empty($cat->nome) ? trim($cat->nome) : 'Sem Categoria';
            $cnpjCliente = $cfg->ignorarTerceiro ? '' : $extrairDocumento($cr->cliente ?? null);

            $itemBank = ItemContaEmpresa::with('conta.contaContabil')->where('conta_receber_id', $cr->id)->first();
            $movAdiantamento = AdiantamentoMovimentacao::where('conta_receber_id', $cr->id)->first();

            if (!$itemBank && !$movAdiantamento) {
                continue;
            }

            $dataTransacao = $itemBank ? $itemBank->data_pagamento : ($movAdiantamento ? $movAdiantamento->data : $cr->data_recebimento);
            $dataFormatada = date('d/m/Y', strtotime($dataTransacao));
            $idBanco = $itemBank ? $itemBank->conta_id : null;

            $codD_Banco = '---';
            if ($itemBank && $itemBank->conta) {
                $codD_Banco = $montarConta(optional($itemBank->conta)->contaContabil, '---');
            }

            if ($codD_Banco === '---' && $movAdiantamento) {
                $codD_Banco = '21005';
            }

            $lancamentoDireto = !$cfg->geraProvisao;
            $codC_Cliente = $lancamentoDireto ? $montarConta(optional($cat)->contaDespesa, '---') : $montarConta(optional($cat)->contaProvisao, $contaClientePadrao);

            $numNota = $cr->numero_nota_fiscal ?? $cr->nf_numero ?? '';
            $entidade = $limparTexto($cr->cliente->razao_social ?? 'Baixa');
            $textoHistorico = $lancamentoDireto ? "Rec. Direto" : "Receber (Baixa)";

            $valorPago = (float)(isset($cr->valor_recebido) && $cr->valor_recebido > 0 ? $cr->valor_recebido : (isset($cr->valor_pago) && $cr->valor_pago > 0 ? $cr->valor_pago : ($cr->valor_integral ?? 0)));
            $juros = (float)($cr->juros ?? 0);
            $multa = (float)($cr->multa ?? 0);
            $desconto = (float)($cr->desconto ?? 0);
            $valorPrincipal = $valorPago - $juros - $multa + $desconto;

            if ($valorPrincipal > 0) {
                $auditoria['receber_baixa'][] = [
                    'id' => $cr->id, 'data' => $dataFormatada,
                    'entidade' => $entidade, 'historico' => mb_substr("$textoHistorico - " . ($numNota ? "NF: $numNota - " : "") . $entidade, 0, 60, 'UTF-8'),
                    'categoria' => $nomeCategoria, 'debito' => $codD_Banco, 'credito' => $codC_Cliente,
                    'terceiro_debito' => '', 'terceiro_credito' => $cnpjCliente, 'documento' => $numNota,
                    'valor' => number_format($valorPrincipal, 2, ',', '.'),
                    'status' => ($codD_Banco === '---') ? 'ERRO' : (($codC_Cliente === '---') ? 'SEM PROVISÃO' : 'OK'),
                    'categoria_id' => $cr->categoria_id, 'conta_banco_id' => $idBanco,
                ];
            }

            if ($juros > 0) {
                $auditoria['receber_baixa'][] = [
                    'id' => $cr->id, 'data' => $dataFormatada,
                    'entidade' => $entidade, 'historico' => mb_substr("Juros Recebidos - " . ($numNota ? "NF: $numNota - " : "") . $entidade, 0, 60, 'UTF-8'),
                    'categoria' => 'Receita Financeira (Juros)', 'debito' => $codD_Banco, 'credito' => $contaJurosReceita,
                    'terceiro_debito' => '', 'terceiro_credito' => '', 'documento' => $numNota,
                    'valor' => number_format($juros, 2, ',', '.'), 'status' => ($codD_Banco === '---') ? 'ERRO' : 'OK',
                    'categoria_id' => $cr->categoria_id, 'conta_banco_id' => $idBanco,
                ];
            }

            if ($multa > 0) {
                $auditoria['receber_baixa'][] = [
                    'id' => $cr->id, 'data' => $dataFormatada,
                    'entidade' => $entidade, 'historico' => mb_substr("Multa Recebida - " . ($numNota ? "NF: $numNota - " : "") . $entidade, 0, 60, 'UTF-8'),
                    'categoria' => 'Receita Financeira (Multa)', 'debito' => $codD_Banco, 'credito' => $contaMultaReceita,
                    'terceiro_debito' => '', 'terceiro_credito' => '', 'documento' => $numNota,
                    'valor' => number_format($multa, 2, ',', '.'), 'status' => ($codD_Banco === '---') ? 'ERRO' : 'OK',
                    'categoria_id' => $cr->categoria_id, 'conta_banco_id' => $idBanco,
                ];
            }
        }

        // =========================================================================
        // 5. LANÇAMENTOS MANUAIS (BANCO / CAIXA) - FILTRO VIA CONTA EMPRESA
        // =========================================================================
        $manual = ItemContaEmpresa::with(['categoria.contaDespesa', 'conta.contaContabil'])
            ->where('empresa_id', $empresaId)
            ->whereBetween('data_pagamento', [$dtInicio, $dtFim])
            ->where(function($q) { $q->whereNull('conta_pagar_id')->orWhere('conta_pagar_id', 0); })
            ->where(function($q) { $q->whereNull('conta_receber_id')->orWhere('conta_receber_id', 0); })
            ->whereHas('categoria', function($q) {
                $q->where('nome', 'NOT LIKE', '%Adiantamento%');
            })
            ->whereHas('conta', function($q) use ($filtroFilial) {
                $filtroFilial($q);
            })->get();

        foreach ($manual as $item) {
            $cat = $item->categoria;
            $debito = $montarConta(optional($cat)->contaDespesa, '---');
            $credito = $montarConta(optional($item->conta)->contaContabil, '---');

            if ($debito === $credito && $debito !== '---') {
                continue;
            }

            if (isset($item->tipo) && strtolower($item->tipo) === 'receita') {
                $temp = $debito; $debito = $credito; $credito = $temp;
            }

            $auditoria['manual'][] = [
                'id' => $item->id, 'data' => date('d/m/Y', strtotime($item->data_pagamento)),
                'entidade' => $limparTexto($item->descricao ?? 'Não informada'),
                'historico' => mb_substr($limparTexto($item->origem ?? 'Manual') . " - " . $limparTexto($item->descricao ?? 'Não informada'), 0, 60, 'UTF-8'),
                'categoria' => !empty($cat->nome) ? trim($cat->nome) : 'Sem Categoria',
                'debito' => $debito, 'credito' => $credito,
                'terceiro_debito' => '', 'terceiro_credito' => '', 'documento' => '',
                'valor' => number_format($item->valor, 2, ',', '.'),
                'status' => ($debito === '---' || $credito === '---') ? 'ERRO' : 'OK',
                'categoria_id' => $item->categoria_id, 'conta_banco_id' => $item->conta_id,
            ];
        }

        // =========================================================================
        // 6. ADIANTAMENTOS - SEPARANDO POR MATRIZ E FILIAL
        // =========================================================================
        $adiantamentos = AdiantamentoMovimentacao::where('empresa_id', $empresaId)
            ->whereBetween('data', [$dtInicio, $dtFim])
            ->where(function($q) use ($filtroFilial) {
                $q->where(function($sub) use ($filtroFilial) {
                    $sub->whereNotNull('conta_pagar_id')
                        ->whereIn('conta_pagar_id', function($queryCp) use ($filtroFilial) {
                            $queryCp->select('id')->from('conta_pagars');
                            $filtroFilial($queryCp);
                        });
                })
                    ->orWhere(function($sub) use ($filtroFilial) {
                        $sub->whereNotNull('conta_receber_id')
                            ->whereIn('conta_receber_id', function($queryCr) use ($filtroFilial) {
                                $queryCr->select('id')->from('conta_recebers');
                                $filtroFilial($queryCr);
                            });
                    })
                    ->orWhere(function($sub) use ($filtroFilial) {
                        $sub->whereNotNull('adiantamento_id')
                            ->whereIn('adiantamento_id', function($queryAd) use ($filtroFilial) {
                                $queryAd->select('id')->from('adiantamentos');
                                $filtroFilial($queryAd);
                            });
                    });
            })
            ->get();

        foreach ($adiantamentos as $mov) {
            $isCliente = !empty($mov->conta_receber_id);

            $auditoria['adiantamentos'][] = [
                'id' => $mov->id,
                'data' => date('d/m/Y', strtotime($mov->data)),
                'entidade' => 'Mov. Adiantamento #' . $mov->adiantamento_id,
                'historico' => "Movimentação Adiantamento ID: " . $mov->adiantamento_id,
                'categoria' => 'Adiantamento',
                'debito' => $isCliente ? '11101' : '11501',
                'credito' => $isCliente ? '21005' : '11101',
                'terceiro_debito' => '',
                'terceiro_credito' => '',
                'documento' => '',
                'valor' => number_format((float)$mov->valor, 2, ',', '.'),
                'status' => 'OK',
                'categoria_id' => null,
                'conta_banco_id' => null
            ];
        }

        foreach ($auditoria as $aba => $linhas) {
            usort($auditoria[$aba], function($a, $b) {
                if ($a['status'] === $b['status']) {
                    return strtotime(str_replace('/', '-', $b['data'])) <=> strtotime(str_replace('/', '-', $a['data']));
                }
                if ($a['status'] === 'ERRO') return -1;
                if ($b['status'] === 'ERRO') return 1;
                if ($a['status'] === 'SEM PROVISÃO') return -1;
                if ($b['status'] === 'SEM PROVISÃO') return 1;
                return 0;
            });
        }

        return $auditoria;
    }

    public function gerarArquivoTerceiros($empresaId)
    {
        $linhas = [];

        $limparDoc = function($doc) {
            return substr(preg_replace('/[^0-9]/', '', (string)$doc), 0, 14);
        };

        $extrairTelefone = function($tel) {
            $telLpo = preg_replace('/[^0-9]/', '', $tel);
            if (strlen($telLpo) >= 10) {
                return ['ddd' => substr($telLpo, 0, 2), 'num' => substr($telLpo, 2, 10)];
            }
            return ['ddd' => '', 'num' => ''];
        };

        $formatarLinha = function($terceiro) use ($limparDoc, $extrairTelefone) {
            $doc = $limparDoc($terceiro->cpf_cnpj);
            $tipoPes = strlen($doc) > 11 ? '0' : '1';

            $telefone = $extrairTelefone($terceiro->telefone ?? $terceiro->celular);
            $cidade = \App\Models\Cidade::find($terceiro->cidade_id);
            $codIbge = $cidade ? substr($cidade->codigo, 0, 5) : '';

            $linha = "";
            $linha .= "TRC";
            $linha .= str_pad("", 5, " ");
            $linha .= str_pad("", 2, " ");
            $linha .= $tipoPes;

            $linha .= str_pad($doc, 14, " ", STR_PAD_LEFT);

            $linha .= str_pad(substr($terceiro->razao_social, 0, 60), 60, " ");
            $linha .= str_pad(substr($terceiro->nome_fantasia, 0, 20), 20, " ");
            $linha .= str_pad("RUA", 10, " ");
            $linha .= str_pad(substr($terceiro->rua, 0, 60), 60, " ");
            $linha .= str_pad(substr($terceiro->numero, 0, 10), 10, " ");
            $linha .= str_pad(substr($terceiro->complemento, 0, 20), 20, " ");

            $cep = preg_replace('/[^0-9]/', '', $terceiro->cep);
            $cepFormatado = strlen($cep) == 8 ? substr($cep,0,5)."-".substr($cep,5,3) : "";
            $linha .= str_pad($cepFormatado, 9, " ");

            $linha .= str_pad(substr($terceiro->bairro, 0, 30), 30, " ");
            $linha .= str_pad(substr($cidade->nome ?? '', 0, 30), 30, " ");
            $linha .= str_pad(substr($cidade->uf ?? '', 0, 2), 2, " ");
            $linha .= str_pad("", 8, " ");
            $linha .= str_pad($telefone['ddd'], 5, " ");
            $linha .= str_pad($telefone['num'], 10, " ");
            $linha .= str_pad("", 5, " ");
            $linha .= str_pad("", 10, " ");
            $linha .= str_pad(substr($terceiro->email ?? '', 0, 50), 50, " ");
            $linha .= str_pad("", 60, " ");
            $linha .= str_pad(substr($terceiro->ie_rg ?? '', 0, 20), 20, " ");
            $linha .= str_pad("", 20, " ");
            $linha .= str_pad("", 10, " ");
            $linha .= str_pad("", 18, " ");
            $linha .= str_pad("", 5, " ");
            $linha .= str_pad("", 2, " ");
            $linha .= str_pad("", 8, " ");
            $linha .= " ";
            $linha .= "1058";
            $linha .= str_pad($codIbge, 5, " ");
            $linha .= str_pad("", 86, " ");
            $linha .= str_pad("", 10, " ");
            $linha .= str_pad("", 10, " ");
            $linha .= "S";
            $linha .= ($terceiro->contribuinte == 1 ? "S" : "N");
            $linha .= str_pad("", 14, " ");
            $linha .= str_pad("", 3, " ");
            $linha .= "N";
            $linha .= "N";
            $linha .= "N";
            $linha .= "N";

            return $linha;
        };

        $clientes = Cliente::where('empresa_id', $empresaId)->get();
        foreach ($clientes as $cli) {
            $linhas[] = $formatarLinha($cli);
        }

        $fornecedores = Fornecedor::where('empresa_id', $empresaId)->get();
        foreach ($fornecedores as $forn) {
            $linhas[] = $formatarLinha($forn);
        }

        return implode("\r\n", $linhas);
    }
}
