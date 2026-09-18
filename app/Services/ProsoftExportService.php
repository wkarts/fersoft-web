<?php

namespace App\Services;

use App\Models\Compra;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class ProsoftExportService
{
    private function formatField($value, $length, $type = 'string', $precision = 2)
    {
        if ($type === 'decimal') {
            $floatVal = (float) str_replace(',', '.', $value);
            $formatted = number_format($floatVal, $precision, '.', '');
            return str_pad($formatted, $length, ' ', STR_PAD_LEFT);
        }

        if ($type === 'integer') {
            return str_pad(preg_replace('/[^0-9]/', '', (string)$value), $length, '0', STR_PAD_LEFT);
        }

        if ($type === 'date_dmy') {
            if (empty($value)) return str_pad('', $length, ' ', STR_PAD_RIGHT);
            return Carbon::parse($value)->format('dmy');
        }

        if ($type === 'date_dmyyyy') {
            if (empty($value)) return str_pad('', $length, ' ', STR_PAD_RIGHT);
            return Carbon::parse($value)->format('dmY');
        }

        // Limpa acentos para garantir o tamanho exato de bytes na geração ASCII
        $cleaned = preg_replace(
            array('/[áàâãä]/u', '/[ÁÀÂÃÄ]/u', '/[éèêë]/u', '/[ÉÈÊË]/u', '/[íìîï]/u', '/[ÍÌÎÏ]/u', '/[óòôõö]/u', '/[ÓÒÔÕÖ]/u', '/[úùûü]/u', '/[ÚÙÛÜ]/u', '/ç/u', '/Ç/u', '/ñ/u', '/Ñ/u'),
            array('a', 'A', 'e', 'E', 'i', 'I', 'o', 'O', 'u', 'U', 'c', 'C', 'n', 'N'),
            (string)$value
        );
        $cleaned = preg_replace('/[^a-zA-Z0-9\s\.,-]/', '', $cleaned);
        $cleaned = substr(trim($cleaned), 0, $length);

        return str_pad($cleaned, $length, ' ', STR_PAD_RIGHT);
    }

    /**
     * 1. Gera o arquivo de Notas de Entrada (Compras)
     */
    public function gerarArquivoNotas(array $compraIds, string $filePath)
    {
        $compras = Compra::with(['fornecedor', 'itens'])->whereIn('id', $compraIds)->get();
        $linhas = [];

        foreach ($compras as $compra) {
            $fornecedor = $compra->fornecedor;
            $cnpjCpf = preg_replace('/[^0-9]/', '', $fornecedor->cpf_cnpj ?? '');

            $numNotaBruto = (!empty($compra->nf) && $compra->nf != '0') ? $compra->nf : $compra->numero_emissao;
            $numNotaLimpo = preg_replace('/[^0-9]/', '', (string)$numNotaBruto);

            if (strlen($numNotaLimpo) <= 6) {
                $numNotaPos31 = $numNotaLimpo;
                $numNotaPos869 = '';
            } else {
                $numNotaPos31 = '000000';
                $numNotaPos869 = $numNotaLimpo;
            }

            $codCtbNota = '';
            if (!empty($compra->categoria_conta_id)) {
                $cat = \DB::table('categoria_contas')->where('id', $compra->categoria_conta_id)->first();
                if ($cat && !empty($cat->int_ctb)) {
                    $codCtbNota = $cat->int_ctb;
                }
            }

            $itensPorCfop = [];
            foreach ($compra->itens as $item) {
                $cfop4 = $item->cfop_entrada ?? null;
                if (empty($cfop4) || $cfop4 == '0') {
                    $cfop4 = '1102';
                }

                if ($cfop4 === '1405') {
                    $cfop4 = '1407';
                }

                if (str_starts_with($cfop4, '5')) {
                    $cfop4 = '1' . substr($cfop4, 1);
                } elseif (str_starts_with($cfop4, '6')) {
                    $cfop4 = '2' . substr($cfop4, 1);
                }
                $itensPorCfop[$cfop4][] = $item;
            }

            if (empty($itensPorCfop)) {
                $itensPorCfop['1102'] = [];
            }

            $indiceDesdobramento = 0;
            foreach ($itensPorCfop as $cfop4 => $itensCfop) {
                $valorMercadoriasCfop = 0;
                foreach ($itensCfop as $item) {
                    $valorMercadoriasCfop += ($item->quantidade * $item->valor_unitario);
                }
                if ($valorMercadoriasCfop <= 0) {
                    $valorMercadoriasCfop = $compra->valor / count($itensPorCfop);
                }

                $vbcIcms = 0;
                $vIcms = 0;
                $valorOutras = $valorMercadoriasCfop;

                $linha = '';
                $linha .= $this->formatField('', 4); // 1 a 4
                $linha .= $this->formatField($cnpjCpf, 14, 'integer'); // 5 a 18
                $linha .= $this->formatField($compra->data_saida ?? $compra->data_emissao, 6, 'date_dmy'); // 19 a 24
                $linha .= $this->formatField($compra->data_emissao, 6, 'date_dmy'); // 25 a 30
                $linha .= $this->formatField($numNotaPos31, 6, 'integer'); // 31 a 36
                $linha .= $this->formatField('', 3); // 37 a 39
                $linha .= $this->formatField('1', 3); // 40 a 42
                $linha .= $this->formatField($indiceDesdobramento, 1, 'integer'); // 43
                $linha .= $this->formatField('1', 5, 'integer'); // 44 a 48
                $linha .= $this->formatField('', 3); // 49 a 51
                $linha .= $this->formatField($valorMercadoriasCfop, 14, 'decimal'); // 52 a 65
                $linha .= $this->formatField($vbcIcms, 14, 'decimal'); // 66 a 79
                $linha .= $this->formatField($vIcms, 14, 'decimal'); // 80 a 93
                $linha .= $this->formatField(0, 14, 'decimal'); // 94 a 107
                $linha .= $this->formatField($valorOutras, 14, 'decimal'); // 108 a 121
                $linha .= $this->formatField(0, 14, 'decimal'); // 122 a 135
                $linha .= $this->formatField(0, 5, 'decimal', 2); // 136 a 140
                $linha .= $this->formatField(0, 14, 'decimal'); // 141 a 154
                $linha .= $this->formatField(0, 14, 'decimal'); // 155 a 168
                $linha .= $this->formatField(0, 14, 'decimal'); // 169 a 182
                $linha .= $this->formatField(0, 14, 'decimal'); // 183 a 196
                $linha .= $this->formatField(0, 14, 'decimal'); // 197 a 210
                $linha .= $this->formatField($valorMercadoriasCfop, 14, 'decimal'); // 211 a 224
                $linha .= $this->formatField($compra->tipo_pagamento == '01' ? '0' : '1', 1); // 225

                $currentLen = strlen($linha);
                if ($currentLen < 623) {
                    $linha .= str_repeat(' ', 623 - $currentLen);
                }
                $linha .= $this->formatField($cfop4, 4, 'integer');

                $currentLen = strlen($linha);
                if ($currentLen < 768) {
                    $linha .= str_repeat(' ', 768 - $currentLen);
                }
                $codCtbFormatado = !empty($codCtbNota) ? str_pad(trim($codCtbNota), 4, '0', STR_PAD_LEFT) : '0000';
                $linha .= $this->formatField($codCtbFormatado, 4);

                $currentLen = strlen($linha);
                if ($currentLen < 857) {
                    $linha .= str_repeat(' ', 857 - $currentLen);
                }
                $linha .= $this->formatField('NF-E', 5);

                $currentLen = strlen($linha);
                if ($currentLen < 868) {
                    $linha .= str_repeat(' ', 868 - $currentLen);
                }
                if (!empty($numNotaPos869)) {
                    $linha .= $this->formatField($numNotaPos869, 10, 'integer');
                } else {
                    $linha .= str_repeat(' ', 10);
                }

                $currentLen = strlen($linha);
                if ($currentLen < 972) {
                    $linha .= str_repeat(' ', 972 - $currentLen);
                }
                $linha .= $this->formatField($compra->chave ?? '', 44);

                // AJUSTE: Situação Especial (Código 0004 exclusivo para CFOP 2556)
                $currentLen = strlen($linha);
                if ($currentLen < 1100) {
                    $linha .= str_repeat(' ', 1100 - $currentLen);
                }
                $sitEspecial = (trim((string)$cfop4) === '2556') ? '0004' : '    ';
                $linha .= $this->formatField($sitEspecial, 4); // 1101 a 1104

                $currentLen = strlen($linha);
                if ($currentLen < 1139) {
                    $linha .= str_repeat(' ', 1139 - $currentLen);
                }
                $linha .= $this->formatField('00', 10);

                $currentLen = strlen($linha);
                if ($currentLen < 1917) {
                    $linha .= str_repeat(' ', 1917 - $currentLen);
                }
                $linha .= $this->formatField('1', 1, 'integer');

                $linhas[] = $linha;
                $indiceDesdobramento++;
            }
        }

        File::put($filePath, implode("\r\n", $linhas));
        return $filePath;
    }

    /**
     * 2. Gera o arquivo de Itens das Notas de Entrada
     */
    public function gerarArquivoItens(array $compraIds, string $filePath)
    {
        $compras = Compra::with([
            'fornecedor',
            'itens.produto'
        ])->whereIn('id', $compraIds)->get();

        $linhas = [];

        foreach ($compras as $compra) {
            $fornecedor = $compra->fornecedor;
            $cnpjCpf = preg_replace('/[^0-9]/', '', $fornecedor->cpf_cnpj ?? '');
            $dataEntrada = Carbon::parse($compra->data_saida ?? $compra->data_emissao)->format('Ymd');
            $numeroNota = (!empty($compra->nf) && $compra->nf != '0') ? $compra->nf : $compra->numero_emissao;
            $numDoc = str_pad(preg_replace('/[^0-9]/', '', $numeroNota), 6, '0', STR_PAD_LEFT);

            $codCtb = '';
            if (!empty($compra->categoria_conta_id)) {
                $categoria = \DB::table('categoria_contas')->where('id', $compra->categoria_conta_id)->first();
                if ($categoria && !empty($categoria->int_ctb)) {
                    $codCtb = $categoria->int_ctb;
                }
            }

            // AJUSTE: Agrupar itens por CFOP para manter o mesmo índice de desdobramento da nota
            $itensPorCfop = [];
            foreach ($compra->itens as $item) {
                $cfop4 = $item->cfop_entrada ?? null;
                if (empty($cfop4) || $cfop4 == '0') {
                    $cfop4 = '1102';
                }
                if ($cfop4 === '1405') {
                    $cfop4 = '1407';
                }
                if (str_starts_with($cfop4, '5')) {
                    $cfop4 = '1' . substr($cfop4, 1);
                } elseif (str_starts_with($cfop4, '6')) {
                    $cfop4 = '2' . substr($cfop4, 1);
                }
                $itensPorCfop[$cfop4][] = $item;
            }

            if (empty($itensPorCfop)) {
                $itensPorCfop['1102'] = [];
            }

            $indiceDesdobramento = 0;

            foreach ($itensPorCfop as $cfop4 => $itensCfop) {
                $itemIndex = 1;

                foreach ($itensCfop as $item) {
                    // Busca código do produto
                    $codigoProduto = '';
                    if ($item->produto) {
                        if (!empty($item->produto->referencia)) {
                            $codigoProduto = trim($item->produto->referencia);
                        } else {
                            $codigoProduto = trim((string)$item->produto->id);
                        }
                    }
                    if (empty($codigoProduto)) {
                        $codigoProduto = 'PROD' . $item->id;
                    }

                    $valorItem = $item->quantidade * $item->valor_unitario;

                    // CST ICMS
                    $cstIcmsOriginal = trim($item->cst_icms ?? '00');
                    if ($cstIcmsOriginal === '0102') {
                        $cstIcms = '090';
                    } elseif ($cstIcmsOriginal === '0500') {
                        $cstIcms = '060';
                    } else {
                        $cstIcms = str_pad(preg_replace('/[^0-9]/', '', $cstIcmsOriginal), 3, '0', STR_PAD_LEFT);
                        if (strlen($cstIcms) < 3) {
                            $cstIcms = '0' . $cstIcms;
                        }
                    }

                    $vbcIcms = $item->vbc_icms ?? 0;
                    $vIcms = $item->v_icms ?? 0;
                    $pIcms = $item->p_icms ?? 0;

                    $valorOutrasIcms = $item->v_outros_icms ?? 0;
                    $valorIsentasIcms = 0;

                    if ($valorOutrasIcms <= 0 && $vbcIcms <= 0 && $vIcms <= 0) {
                        if (in_array($cstIcms, ['040', '090', '060'])) {
                            $valorOutrasIcms = $valorItem;
                        } elseif ($cstIcms === '041') {
                            $valorIsentasIcms = $valorItem;
                        } else {
                            $valorOutrasIcms = $valorItem;
                        }
                    } elseif ($cstIcms === '020' && $valorOutrasIcms <= 0) {
                        $vbcIcms = $item->vbc_icms ?? $valorItem;
                        $valorOutrasIcms = max(0, $valorItem - $vbcIcms);
                    }

                    $cstPis = trim($item->cst_pis ?? '50');
                    $cstCofins = trim($item->cst_cofins ?? '50');
                    $alqPis = $item->p_pis ?? 0;
                    $alqCofins = $item->p_cofins ?? 0;
                    $bcPisCofins = $item->vbc_pis ?? ($vbcIcms > 0 ? $vbcIcms : $valorItem);
                    $vlrPis = $item->v_pis ?? ($bcPisCofins * ($alqPis / 100));
                    $vlrCofins = $item->v_cofins ?? ($bcPisCofins * ($alqCofins / 100));
                    $vlrReceita = $item->v_receita ?? $valorItem;

                    $bcIbsCbs = $item->bc_ibs_cbs ?? 0;
                    $aliqIbsUf = $item->aliq_ibs_uf ?? 0;
                    $aliqIbsMun = $item->aliq_ibs_mun ?? 0;
                    $aliqCbs = $item->aliq_cbs ?? 0;
                    $valorIbs = $item->valor_ibs ?? 0;
                    $valorCbs = $item->valor_cbs ?? 0;

                    $linha = '';
                    $linha .= $this->formatField($cnpjCpf, 14, 'integer');
                    $linha .= $this->formatField($dataEntrada, 8);
                    $linha .= $this->formatField($numDoc, 6);
                    $linha .= $this->formatField('1', 3);

                    // AJUSTE CRUCIAL: Agora a posição 32 recebe o índice do desdobramento do CFOP
                    $linha .= $this->formatField($indiceDesdobramento, 1, 'integer');

                    $linha .= $this->formatField($itemIndex, 2, 'integer');
                    $linha .= $this->formatField($codigoProduto, 20);
                    $linha .= $this->formatField('', 3);
                    $linha .= $this->formatField('', 1);
                    $linha .= $this->formatField($item->unidade_compra ?? 'UN', 3);
                    $linha .= $this->formatField($cstIcms, 3);
                    $linha .= $this->formatField($item->quantidade, 14, 'decimal', 3);
                    $linha .= $this->formatField($item->valor_unitario, 14, 'decimal', 6);
                    $linha .= $this->formatField(0, 14, 'decimal');
                    $linha .= $this->formatField($valorItem, 14, 'decimal');
                    $linha .= $this->formatField($item->p_ipi ?? 0, 5, 'decimal', 2);
                    $linha .= $this->formatField($item->v_ipi ?? 0, 14, 'decimal');
                    $linha .= $this->formatField($vbcIcms, 14, 'decimal');

                    $currentLen = strlen($linha);
                    if ($currentLen < 167) {
                        $linha .= str_repeat(' ', 167 - $currentLen);
                    }
                    $nomeProduto = $item->produto->nome ?? 'Produto ' . $codigoProduto;
                    $linha .= $this->formatField($nomeProduto, 80);

                    $currentLen = strlen($linha);
                    if ($currentLen < 344) {
                        $linha .= str_repeat(' ', 344 - $currentLen);
                    }
                    $linha .= $this->formatField($pIcms, 5, 'decimal', 2);
                    $linha .= $this->formatField($vIcms, 14, 'decimal');

                    $currentLen = strlen($linha);
                    if ($currentLen < 587) {
                        $linha .= str_repeat(' ', 587 - $currentLen);
                    }
                    $linha .= $this->formatField($valorIsentasIcms, 14, 'decimal');

                    $currentLen = strlen($linha);
                    if ($currentLen < 601) {
                        $linha .= str_repeat(' ', 601 - $currentLen);
                    }
                    $linha .= $this->formatField($valorOutrasIcms, 14, 'decimal');

                    $currentLen = strlen($linha);
                    if ($currentLen < 698) {
                        $linha .= str_repeat(' ', 698 - $currentLen);
                    }
                    $linha .= $this->formatField($cstPis, 2, 'integer');
                    $linha .= $this->formatField($cstCofins, 2, 'integer');

                    $currentLen = strlen($linha);
                    if ($currentLen < 1263) {
                        $linha .= str_repeat(' ', 1263 - $currentLen);
                    }
                    $linha .= $this->formatField($vlrReceita, 14, 'decimal');
                    $linha .= $this->formatField($bcPisCofins, 14, 'decimal');
                    $linha .= $this->formatField($alqPis, 8, 'decimal', 4);
                    $linha .= $this->formatField($alqCofins, 8, 'decimal', 4);
                    $linha .= $this->formatField(0, 14, 'decimal');
                    $linha .= $this->formatField(0, 8, 'decimal', 4);
                    $linha .= $this->formatField(0, 8, 'decimal', 4);
                    $linha .= $this->formatField($vlrPis, 14, 'decimal');
                    $linha .= $this->formatField($vlrCofins, 14, 'decimal');

                    $currentLen = strlen($linha);
                    if ($currentLen < 1549) {
                        $linha .= str_repeat(' ', 1549 - $currentLen);
                    }
                    $linha .= $this->formatField($bcIbsCbs, 14, 'decimal');
                    $linha .= $this->formatField($aliqIbsUf, 8, 'decimal', 4);
                    $linha .= $this->formatField($aliqIbsMun, 8, 'decimal', 4);
                    $linha .= $this->formatField($aliqCbs, 8, 'decimal', 4);
                    $linha .= $this->formatField($valorIbs, 14, 'decimal');
                    $linha .= $this->formatField($valorCbs, 14, 'decimal');

                    $linhas[] = $linha;
                    $itemIndex++;
                }

                $indiceDesdobramento++;
            }
        }

        File::put($filePath, implode("\r\n", $linhas));
        return $filePath;
    }

    /**
     * 3. Gera o arquivo de Serviços Tomados (CFOP 1933/2933)
     */
    public function gerarArquivoServicos(array $compraIds, string $filePath)
    {
        $compras = Compra::with(['fornecedor', 'itens'])
            ->whereIn('id', $compraIds)
            ->whereHas('itens', function($q) {
                $q->whereIn('cfop_entrada', ['1933', '2933']);
            })
            ->get();

        $linhas = [];

        foreach ($compras as $compra) {
            $fornecedor = $compra->fornecedor;
            $cnpjCpf = preg_replace('/[^0-9]/', '', $fornecedor->cpf_cnpj ?? '');
            $numeroNota = (!empty($compra->nf) && $compra->nf != '0') ? $compra->nf : $compra->numero_emissao;

            $codCtbNota = '';
            if (!empty($compra->categoria_conta_id)) {
                $cat = \DB::table('categoria_contas')->where('id', $compra->categoria_conta_id)->first();
                if ($cat) {
                    if (!empty($cat->int_ctb_servico)) {
                        $codCtbNota = $cat->int_ctb_servico;
                    } elseif (!empty($cat->int_ctb)) {
                        $codCtbNota = $cat->int_ctb;
                    }
                }
            }

            foreach ($compra->itens as $item) {
                if (!in_array($item->cfop_entrada, ['1933', '2933'])) continue;

                $valorServico = $item->quantidade * $item->valor_unitario;

                $cstPis = trim($item->cst_pis ?? '50');
                $cstCofins = trim($item->cst_cofins ?? '50');
                $alqPis = $item->p_pis ?? 0;
                $alqCofins = $item->p_cofins ?? 0;
                $vlrPis = $item->v_pis ?? 0;
                $vlrCofins = $item->v_cofins ?? 0;
                $bcPisCofins = $item->vbc_pis ?? $valorServico;

                $temPisCofins = ($cstPis === '50' || $cstCofins === '50') && ($vlrPis > 0 || $vlrCofins > 0);
                $origemCredito = $temPisCofins ? '0' : ' ';
                $bcCredito = $temPisCofins ? '13' : '  ';

                $linha = '';
                $linha .= $this->formatField('56', 2, 'integer');
                $linha .= $this->formatField('NFS-E', 5);
                $linha .= $this->formatField($cnpjCpf, 14, 'integer');
                $linha .= $this->formatField($compra->data_emissao, 8, 'date_dmyyyy');
                $linha .= $this->formatField('1', 3);
                $linha .= $this->formatField('', 2);
                $linha .= $this->formatField('', 8);
                $linha .= $this->formatField('0', 1);
                $linha .= $this->formatField('', 1);
                $linha .= $this->formatField('0101', 4, 'integer');
                $linha .= $this->formatField($valorServico, 14, 'decimal');
                $linha .= $this->formatField($valorServico, 14, 'decimal');
                $linha .= $this->formatField(5.00, 5, 'decimal', 2);
                $linha .= $this->formatField(($valorServico * 0.05), 14, 'decimal');
                $linha .= $this->formatField('', 15);
                $linha .= $this->formatField($compra->observacao ?? '', 100);

                $currentLen = strlen($linha);
                if ($currentLen < 294) {
                    $linha .= str_repeat(' ', 294 - $currentLen);
                }

                $linha .= $this->formatField('000000', 6);
                $linha .= $this->formatField('', 1);
                $codCtbFormatado = !empty($codCtbNota) ? str_pad(trim($codCtbNota), 4, '0', STR_PAD_LEFT) : '0000';
                $linha .= $this->formatField($codCtbFormatado, 4);

                $currentLen = strlen($linha);
                if ($currentLen < 396) {
                    $linha .= str_repeat(' ', 396 - $currentLen);
                }
                $linha .= $this->formatField('OU000', 5);

                $currentLen = strlen($linha);
                if ($currentLen < 418) {
                    $linha .= str_repeat(' ', 418 - $currentLen);
                }
                $linha .= $this->formatField('00', 10);

                $currentLen = strlen($linha);
                if ($currentLen < 438) {
                    $linha .= str_repeat(' ', 438 - $currentLen);
                }
                $linha .= $this->formatField($cstPis, 2, 'integer');
                $linha .= $this->formatField($cstCofins, 2, 'integer');
                $linha .= $this->formatField($bcPisCofins, 14, 'decimal');
                $linha .= $this->formatField($alqPis, 8, 'decimal', 4);
                $linha .= $this->formatField($vlrPis, 14, 'decimal');
                $linha .= $this->formatField($alqCofins, 8, 'decimal', 4);
                $linha .= $this->formatField($vlrCofins, 14, 'decimal');

                $currentLen = strlen($linha);
                if ($currentLen < 572) {
                    $linha .= str_repeat(' ', 572 - $currentLen);
                }
                $linha .= $this->formatField($origemCredito, 1);
                $linha .= $this->formatField($bcCredito, 2);

                $currentLen = strlen($linha);
                if ($currentLen < 731) {
                    $linha .= str_repeat(' ', 731 - $currentLen);
                }
                $numNotaServico = str_pad(preg_replace('/[^0-9]/', '', (string)$numeroNota), 10, '0', STR_PAD_LEFT);
                $linha .= $this->formatField($numNotaServico, 10);

                $linhas[] = $linha;
            }
        }

        File::put($filePath, implode("\r\n", $linhas));
        return $filePath;
    }

    /**
     * 4. Gera o arquivo de Faturas (Duplicatas)
     */
    public function gerarArquivoFaturas(array $compraIds, string $filePath)
    {
        $compras = Compra::with(['fornecedor'])->whereIn('id', $compraIds)->get();
        $linhas = [];

        foreach ($compras as $compra) {
            $fornecedor = $compra->fornecedor;
            $cnpjCpf = preg_replace('/[^0-9]/', '', $fornecedor->cpf_cnpj ?? '');
            $uf = $fornecedor->uf ?? 'SP';
            $ie = preg_replace('/[^0-9]/', '', $fornecedor->ie_rg ?? '');
            $numeroNota = (!empty($compra->nf) && $compra->nf != '0') ? $compra->nf : $compra->numero_emissao;

            $faturas = \App\Models\ContaPagar::where('compra_id', $compra->id)->get();

            if ($faturas->isEmpty()) {
                $faturas = collect([
                    (object)[
                        'referencia' => 'DUP ' . $numeroNota,
                        'data_vencimento' => $compra->data_emissao,
                        'valor_integral' => $compra->valor,
                        'valor_pago' => 0,
                        'data_pagamento' => null,
                        'juros' => 0,
                        'multa' => 0,
                        'desconto' => 0,
                    ]
                ]);
            }

            foreach ($faturas as $fatura) {
                $linha = '';
                $linha .= $this->formatField('0', 1);
                $linha .= $this->formatField('1', 1);
                $linha .= $this->formatField($cnpjCpf, 14, 'integer');
                $linha .= $this->formatField($uf, 2);
                $linha .= $this->formatField($ie, 20);
                $linha .= $this->formatField('1', 6);
                $linha .= $this->formatField('', 6);
                $linha .= $this->formatField($numeroNota, 10, 'integer');
                $linha .= $this->formatField('000', 3, 'integer');
                $linha .= $this->formatField($compra->data_emissao, 8, 'date_dmyyyy');
                $numFatura = $fatura->referencia ?? ('DUP' . $numeroNota);
                $linha .= $this->formatField($numFatura, 20);
                $linha .= $this->formatField($fatura->data_vencimento ?? $compra->data_emissao, 8, 'date_dmyyyy');
                $valorBruto = $fatura->valor_integral ?? $compra->valor;
                $linha .= $this->formatField($valorBruto, 14, 'decimal');
                $linha .= $this->formatField($fatura->valor_ir ?? 0, 14, 'decimal');
                $linha .= $this->formatField($fatura->valor_pis ?? 0, 14, 'decimal');
                $linha .= $this->formatField($fatura->valor_cofins ?? 0, 14, 'decimal');
                $linha .= $this->formatField($fatura->valor_csll ?? 0, 14, 'decimal');
                $valorLiq = $valorBruto - ($fatura->valor_ir ?? 0) - ($fatura->valor_pis ?? 0) - ($fatura->valor_cofins ?? 0);
                $linha .= $this->formatField($valorLiq, 14, 'decimal');
                $linha .= $this->formatField('', 8);
                $linha .= $this->formatField('', 11);
                $linha .= $this->formatField($fatura->data_pagamento ?? '', 8, 'date_dmyyyy');
                $linha .= $this->formatField('', 8);
                $linha .= $this->formatField($fatura->valor_pago ?? 0, 14, 'decimal');
                $linha .= $this->formatField('', 11);
                $valJurosMulta = ($fatura->juros ?? 0) + ($fatura->multa ?? 0);
                $linha .= $this->formatField($valJurosMulta, 14, 'decimal');
                $linha .= $this->formatField('', 11);
                $linha .= $this->formatField($fatura->desconto ?? 0, 14, 'decimal');
                $linha .= $this->formatField('', 11);
                $linha .= $this->formatField(0, 6, 'decimal', 2);
                $linha .= $this->formatField('S', 1);

                $linhas[] = $linha;
            }
        }

        File::put($filePath, implode("\r\n", $linhas));
        return $filePath;
    }

    /**
     * 5. Gera o arquivo de Itens de Faturas
     */
    public function gerarArquivoItensFaturas(array $compraIds, string $filePath)
    {
        $compras = Compra::with(['fornecedor'])->whereIn('id', $compraIds)->get();
        $linhas = [];

        foreach ($compras as $compra) {
            $fornecedor = $compra->fornecedor;
            $cnpjCpf = preg_replace('/[^0-9]/', '', $fornecedor->cpf_cnpj ?? '');
            $numeroNota = (!empty($compra->nf) && $compra->nf != '0') ? $compra->nf : $compra->numero_emissao;

            $primeiroItem = $compra->itens->first();
            $cfop4 = $primeiroItem->cfop_entrada ?? '1102';
            if (str_starts_with($cfop4, '5')) {
                $cfop4 = '1' . substr($cfop4, 1);
            } elseif (str_starts_with($cfop4, '6')) {
                $cfop4 = '2' . substr($cfop4, 1);
            }

            $faturas = \App\Models\ContaPagar::where('compra_id', $compra->id)->get();
            if ($faturas->isEmpty()) {
                $faturas = collect([
                    (object)[
                        'referencia' => 'DUP ' . $numeroNota,
                        'data_vencimento' => $compra->data_emissao,
                        'valor_integral' => $compra->valor,
                    ]
                ]);
            }

            foreach ($faturas as $fatura) {
                $linha = '';
                $numFatura = $fatura->referencia ?? ('DUP' . $numeroNota);
                $linha .= $this->formatField($numFatura, 20);
                $linha .= $this->formatField($fatura->data_vencimento ?? $compra->data_emissao, 8, 'date_dmyyyy');
                $linha .= $this->formatField($cnpjCpf, 14, 'integer');
                $linha .= $this->formatField($cfop4, 4, 'integer');
                $linha .= $this->formatField('50', 2, 'integer');
                $linha .= $this->formatField('50', 2, 'integer');

                $currentLen = strlen($linha);
                if ($currentLen < 96) {
                    $linha .= str_repeat(' ', 96 - $currentLen);
                }
                $linha .= $this->formatField($fatura->valor_integral ?? $compra->valor, 14, 'decimal');

                $linhas[] = $linha;
            }
        }

        File::put($filePath, implode("\r\n", $linhas));
        return $filePath;
    }

    /**
     * 6. Gera o arquivo de Cadastro de Terceiros
     */
    public function gerarArquivoTerceiros(array $compraIds, string $filePath)
    {
        $compras = Compra::with(['fornecedor'])->whereIn('id', $compraIds)->get();
        $linhas = [];
        $fornecedoresProcessados = [];

        foreach ($compras as $compra) {
            $fornecedor = $compra->fornecedor;
            if (!$fornecedor || isset($fornecedoresProcessados[$fornecedor->id])) {
                continue;
            }
            $fornecedoresProcessados[$fornecedor->id] = true;

            $cnpjCpf = preg_replace('/[^0-9]/', '', $fornecedor->cpf_cnpj ?? '');
            if (empty($cnpjCpf)) continue;

            $personalidade = (strlen($cnpjCpf) > 11) ? '0' : '1';
            $cep = preg_replace('/[^0-9]/', '', $fornecedor->cep ?? '');
            if (strlen($cep) == 8) {
                $cepFormatado = substr($cep, 0, 5) . '-' . substr($cep, 5, 3);
            } else {
                $cepFormatado = '';
            }

            $linha = '';
            $linha .= $this->formatField('TRC', 3); // 1 a 3 (Fixo)[cite: 8]
            $linha .= $this->formatField('', 5); // 4 a 8[cite: 8]
            $linha .= $this->formatField('', 2); // 9 a 10[cite: 8]
            $linha .= $this->formatField($personalidade, 1); // 11[cite: 8]
            $linha .= $this->formatField($cnpjCpf, 14, 'integer'); // 12 a 25[cite: 8]
            $linha .= $this->formatField($fornecedor->razao_social ?? '', 60); // 26 a 85[cite: 8]
            $linha .= $this->formatField($fornecedor->nome_fantasia ?? '', 20); // 86 a 105[cite: 8]
            $linha .= $this->formatField('', 10); // 106 a 115 (Tipo Logr)[cite: 8]
            $linha .= $this->formatField($fornecedor->rua ?? '', 60); // 116 a 175[cite: 8]
            $linha .= $this->formatField($fornecedor->numero ?? '', 10); // 176 a 185[cite: 8]
            $linha .= $this->formatField($fornecedor->complemento ?? '', 20); // 186 a 205[cite: 8]
            $linha .= $this->formatField($cepFormatado, 9); // 206 a 214[cite: 8]
            $linha .= $this->formatField($fornecedor->bairro ?? '', 30); // 215 a 244[cite: 8]
            $linha .= $this->formatField($fornecedor->cidade->nome ?? '', 30); // 245 a 274[cite: 8]
            $linha .= $this->formatField($fornecedor->cidade->uf ?? '', 2); // 275 a 276[cite: 8]
            $linha .= $this->formatField(Carbon::parse($fornecedor->created_at)->format('Ymd'), 8); // 277 a 284[cite: 8]

            $currentLen = strlen($linha);
            if ($currentLen < 424) {
                $linha .= str_repeat(' ', 424 - $currentLen); // Avança ignorando contatos
            }

            $ie = preg_replace('/[^0-9]/', '', $fornecedor->ie_rg ?? '');
            $linha .= $this->formatField($ie, 20); // 425 a 444 (IE)[cite: 8]
            $linha .= $this->formatField('', 20); // 445 a 464 (IM)[cite: 8]

            $currentLen = strlen($linha);
            if ($currentLen < 623) {
                $linha .= str_repeat(' ', 623 - $currentLen); // Avança até Endereço Principal[cite: 8]
            }

            $linha .= $this->formatField('S', 1); // 624 (Principal)[cite: 8]
            $contribuinte = (!empty($ie) && $ie !== 'ISENTO') ? 'S' : 'N';
            $linha .= $this->formatField($contribuinte, 1); // 625 (Contribuinte ICMS)[cite: 8]
            $linha .= $this->formatField(' ', 1); // 626 Filler[cite: 8]

            $linhas[] = $linha;
        }

        File::put($filePath, implode("\r\n", $linhas));
        return $filePath;
    }

    /**
     * 7. Gera o arquivo de Cadastro de Produtos
     */
    public function gerarArquivoProdutos(array $compraIds, string $filePath)
    {
        $compras = Compra::with(['itens.produto'])->whereIn('id', $compraIds)->get();
        $linhas = [];
        $produtosProcessados = [];

        foreach ($compras as $compra) {
            foreach ($compra->itens as $item) {
                $produto = $item->produto;

                if (!$produto || isset($produtosProcessados[$produto->id])) {
                    continue;
                }
                $produtosProcessados[$produto->id] = true;

                // AJUSTE: Prioriza 'referencia', se vazio usa o 'id'
                $codigoProduto = '';
                if (!empty($produto->referencia)) {
                    $codigoProduto = trim($produto->referencia);
                } else {
                    $codigoProduto = trim((string)$produto->id);
                }

                $linha = '';
                $linha .= $this->formatField($codigoProduto, 20);
                $linha .= $this->formatField($produto->nome ?? 'PRODUTO', 80);
                $linha .= $this->formatField('00', 2);
                $linha .= $this->formatField($item->unidade_compra ?? 'UN', 3);

                $ncm = preg_replace('/[^0-9]/', '', $produto->ncm ?? '');
                $linha .= $this->formatField($ncm, 10);

                $currentLen = strlen($linha);
                if ($currentLen < 167) {
                    $linha .= str_repeat(' ', 167 - $currentLen);
                }

                $codigoBarras = preg_replace('/[^0-9]/', '', $produto->codigo_barras ?? '');
                $linha .= $this->formatField($codigoBarras, 14);

                $currentLen = strlen($linha);
                if ($currentLen < 328) {
                    $linha .= str_repeat(' ', 328 - $currentLen);
                }

                $linha .= $this->formatField('00', 2);

                $linhas[] = $linha;
            }
        }

        File::put($filePath, implode("\r\n", $linhas));
        return $filePath;
    }

    public function aplicarAjusteEmLote(array $filtros, array $alteracoes, array $compraIds = [])
    {
        $query = \DB::table('item_compras');

        if (!empty($compraIds)) {
            $query->whereIn('compra_id', $compraIds);
        }

        if (!empty($filtros['cfop'])) {
            $query->where('cfop_entrada', $filtros['cfop']);
        }
        if (!empty($filtros['cst_icms'])) {
            $query->where('cst_icms', $filtros['cst_icms']);
        }
        if (!empty($filtros['cst_pis'])) {
            $query->where('cst_pis', $filtros['cst_pis']);
        }

        $itens = $query->get();

        foreach ($itens as $item) {
            $updateData = [];

            if (!empty($alteracoes['novo_cfop'])) {
                $updateData['cfop_entrada'] = $alteracoes['novo_cfop'];
            }

            if (!empty($alteracoes['novo_cst_icms'])) {
                $updateData['cst_icms'] = $alteracoes['novo_cst_icms'];
            }

            if (!empty($alteracoes['novo_cst_pis'])) {
                $updateData['cst_pis'] = $alteracoes['novo_cst_pis'];
            }

            if (!empty($alteracoes['novo_cst_cofins'])) {
                $updateData['cst_cofins'] = $alteracoes['novo_cst_cofins'];
            }

            if (isset($alteracoes['calcular_pis_cofins']) && $alteracoes['calcular_pis_cofins'] == true) {
                $alqPis = $alteracoes['aliq_pis'] ?? 1.65;
                $alqCofins = $alteracoes['aliq_cofins'] ?? 7.60;

                $valorItem = $item->quantidade * $item->valor_unitario;
                $bcPisCofins = $item->vbc_pis > 0 ? $item->vbc_pis : $valorItem;

                $updateData['p_pis'] = $alqPis;
                $updateData['p_cofins'] = $alqCofins;
                $updateData['vbc_pis'] = $bcPisCofins;
                $updateData['vbc_cofins'] = $bcPisCofins;
                $updateData['v_pis'] = round($bcPisCofins * ($alqPis / 100), 2);
                $updateData['v_cofins'] = round($bcPisCofins * ($alqCofins / 100), 2);
            }

            if (!empty($updateData)) {
                \DB::table('item_compras')->where('id', $item->id)->update($updateData);
            }
        }

        return true;
    }
}
