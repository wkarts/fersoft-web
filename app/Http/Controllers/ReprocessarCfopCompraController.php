<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Compra;
use App\Models\Produto;
use App\Models\Cliente;
use App\Models\Fornecedor;
use App\Models\Categoria;

class ReprocessarCfopCompraController extends BaseController
{
    protected $redirectPage = '/compras/reprocessar-cfop';

    public function __construct()
    {
        $this->redirectPage = '/compras/reprocessar-cfop';
        parent::__construct();
    }

    /* --- Métodos Obrigatórios do BaseController --- */
    public function rules(): array { return []; }
    public function messages(): array { return []; }

    public function index()
    {
        $empresa_id = $this->empresa_id ?? (session('user_logged')['empresa_id'] ?? 2);

        $title        = 'Reprocessar CFOP / Tributos';
        $data_inicial = date('Y-m-01');
        $data_final   = date('Y-m-t');
        $dataInicial  = $data_inicial;
        $dataFinal    = $data_final;

        $total_produtos     = Produto::where('empresa_id', $empresa_id)->count();
        $total_clientes     = Cliente::where('empresa_id', $empresa_id)->count();
        $total_fornecedores = Fornecedor::where('empresa_id', $empresa_id)->count();

        // Contagens e valores exigidos pelo layout de compras
        $total_propria   = Compra::where('empresa_id', $empresa_id)->where('nf', 0)->count();
        $total_terceiro  = Compra::where('empresa_id', $empresa_id)->where('nf', '>', 0)->count();
        $total_terceiros = $total_terceiro;
        $total_geral     = $total_propria + $total_terceiro;
        $soma_valores    = Compra::where('empresa_id', $empresa_id)->sum('valor') ?? 0;

        $fornecedores = Fornecedor::where('empresa_id', $empresa_id)
            ->orderBy('razao_social', 'asc')
            ->get();

        $categorias = class_exists(Categoria::class)
            ? Categoria::where('empresa_id', $empresa_id)->get()
            : collect([]);

        $compras = Compra::where('empresa_id', $empresa_id)
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        return view('compras.reprocessar_cfop', compact(
            'title',
            'data_inicial',
            'data_final',
            'dataInicial',
            'dataFinal',
            'total_produtos',
            'total_clientes',
            'total_fornecedores',
            'total_propria',
            'total_terceiro',
            'total_terceiros',
            'total_geral',
            'soma_valores',
            'fornecedores',
            'categorias',
            'compras'
        ));
    }

    public function executar(Request $request)
    {
        $request->validate([
            'data_inicial' => 'required|date',
            'data_final'   => 'required|date|after_or_equal:data_inicial',
        ], [
            'data_inicial.required' => 'Informe a data inicial.',
            'data_final.required'   => 'Informe a data final.',
            'data_final.after_or_equal' => 'A data final não pode ser menor que a data inicial.'
        ]);

        $empresa_id = $this->empresa_id ?? (session('user_logged')['empresa_id'] ?? 2);

        $dataIni = Carbon::parse($request->data_inicial)->startOfDay();
        $dataFim = Carbon::parse($request->data_final)->endOfDay();

        $compras = DB::table('compras')
            ->where('empresa_id', $empresa_id)
            ->whereBetween('data_emissao', [$dataIni, $dataFim])
            ->get();

        if ($compras->isEmpty()) {
            session()->flash('mensagem_erro', "Nenhuma compra localizada com data de emissão entre {$dataIni->format('d/m/Y')} e {$dataFim->format('d/m/Y')}.");
            return redirect()->back()->withInput();
        }

        $itensAtualizados        = 0;
        $comprasProcessadas      = 0;
        $categoriasSincronizadas = 0;
        $erros = [];

        foreach ($compras as $compra) {
            $chave         = trim($compra->chave ?? '');
            $nf            = (int)($compra->nf ?? 0);
            $numeroEmissao = (int)($compra->numero_emissao ?? 0);

            // Sincronização de categoria entre compras e conta_pagars
            $contaPagar = DB::table('conta_pagars')
                ->where('compra_id', $compra->id)
                ->where('empresa_id', $empresa_id)
                ->first();

            $categoriaIdFinal = null;

            if ($contaPagar && !empty($contaPagar->categoria_id)) {
                $categoriaIdFinal = $contaPagar->categoria_id;
            } elseif (!empty($compra->categoria_conta_id)) {
                $categoriaIdFinal = $compra->categoria_conta_id;
            } else {
                if (strlen($chave) > 44) {
                    $categoriaIdFinal = 98; // SERVIÇOS TOMADOS
                } elseif ($nf === 0 && $numeroEmissao > 0) {
                    $categoriaIdFinal = 7;  // Compra Sucata (PF)
                }
            }

            if ($categoriaIdFinal) {
                DB::table('compras')
                    ->where('id', $compra->id)
                    ->update(['categoria_conta_id' => $categoriaIdFinal]);

                if ($contaPagar) {
                    DB::table('conta_pagars')
                        ->where('id', $contaPagar->id)
                        ->update(['categoria_id' => $categoriaIdFinal]);
                }

                $categoriasSincronizadas++;
            }

            $itens = DB::table('item_compras')
                ->where('compra_id', $compra->id)
                ->orderBy('id', 'asc')
                ->get();

            if ($itens->isEmpty()) {
                continue;
            }

            // Regra 1: Serviço Tomado (chave > 44 dígitos)
            if (strlen($chave) > 44) {
                foreach ($itens as $item) {
                    DB::table('item_compras')
                        ->where('id', $item->id)
                        ->update([
                            'cfop_entrada' => '1933',
                            'updated_at'   => now()
                        ]);
                    $itensAtualizados++;
                }
                $comprasProcessadas++;
                continue;
            }

            // Regra 2: Emissão Própria (nf = 0 e numero_emissao > 0)
            if ($nf === 0 && $numeroEmissao > 0) {
                foreach ($itens as $item) {
                    DB::table('item_compras')
                        ->where('id', $item->id)
                        ->update([
                            'cfop_entrada' => '1102',
                            'cst_icms'     => '051',
                            'cst_pis'      => '74',
                            'cst_cofins'   => '74',
                            'vbc_icms'     => 0.00,
                            'p_icms'       => 0.00,
                            'v_icms'       => 0.00,
                            'updated_at'   => now()
                        ]);
                    $itensAtualizados++;
                }
                $comprasProcessadas++;
                continue;
            }

            // Regra 3: Compra de Terceiro (nf > 0)
            if ($nf > 0) {
                $caminhos = [
                    public_path("xml_entrada/{$chave}.xml"),
                    public_path("xml_entrada/{$compra->xml_path}"),
                    public_path("xml_entrada/{$nf}.xml"),
                    public_path("xml_entrada_emitida/{$chave}.xml")
                ];

                $arquivoXml = null;
                foreach ($caminhos as $c) {
                    if (!empty($c) && file_exists($c)) {
                        $arquivoXml = $c;
                        break;
                    }
                }

                if (!$arquivoXml) {
                    $erros[] = "XML da NF {$nf} (Chave: {$chave}) não localizado em public/xml_entrada.";
                    continue;
                }

                try {
                    $xml = simplexml_load_file($arquivoXml);
                    $infNFe = $xml->NFe->infNFe ?? $xml->infNFe ?? null;

                    if (!$infNFe || !isset($infNFe->det)) {
                        continue;
                    }

                    $index = 0;
                    foreach ($infNFe->det as $det) {
                        if (!isset($itens[$index])) {
                            break;
                        }

                        $itemBanco = $itens[$index];

                        $cfopXml = (string)($det->prod->CFOP ?? '');
                        $cfopFinal = $cfopXml;
                        if (str_starts_with($cfopXml, '5')) {
                            $cfopFinal = '1' . substr($cfopXml, 1);
                        } elseif (str_starts_with($cfopXml, '6')) {
                            $cfopFinal = '2' . substr($cfopXml, 1);
                        }

                        $cstIcms = '00';
                        $imposto = $det->imposto;
                        if (isset($imposto->ICMS)) {
                            foreach ($imposto->ICMS->children() as $tagIcms) {
                                $cstIcms = (string)($tagIcms->CST ?? $tagIcms->CSOSN ?? '00');
                                break;
                            }
                        }

                        $cstPis = '01';
                        if (isset($imposto->PIS)) {
                            foreach ($imposto->PIS->children() as $tagPis) {
                                $cstPis = (string)($tagPis->CST ?? '01');
                                break;
                            }
                        }

                        $cstCofins = '01';
                        if (isset($imposto->COFINS)) {
                            foreach ($imposto->COFINS->children() as $tagCofins) {
                                $cstCofins = (string)($tagCofins->CST ?? '01');
                                break;
                            }
                        }

                        DB::table('item_compras')
                            ->where('id', $itemBanco->id)
                            ->update([
                                'cfop_entrada' => $cfopFinal,
                                'cst_icms'     => $cstIcms,
                                'cst_pis'      => $cstPis,
                                'cst_cofins'   => $cstCofins,
                                'vbc_icms'     => 0.00,
                                'p_icms'       => 0.00,
                                'v_icms'       => 0.00,
                                'updated_at'   => now()
                            ]);

                        $itensAtualizados++;
                        $index++;
                    }

                    $comprasProcessadas++;
                } catch (\Exception $e) {
                    $erros[] = "Erro ao processar XML da NF {$nf}: " . $e->getMessage();
                }
            }
        }

        $msgSucesso = "Processo finalizado! Foram atualizados {$itensAtualizados} item(ns) e {$categoriasSincronizadas} categoria(s) em {$comprasProcessadas} compra(s) entre {$dataIni->format('d/m/Y')} e {$dataFim->format('d/m/Y')}.";

        if (!empty($erros)) {
            session()->flash('mensagem_sucesso', $msgSucesso);
            return redirect()->back()->with('erros_xml', $erros);
        }

        session()->flash('mensagem_sucesso', $msgSucesso);
        return redirect()->back();
    }
}
