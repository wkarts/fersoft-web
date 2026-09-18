<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Funcionario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class FolhaPagamentoController extends Controller
{
    protected $empresa_id;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user_logged = session('user_logged');

            if (!$user_logged) {
                return redirect('/login');
            }

            // Garante compatibilidade caso a sessão seja array ou objeto StdClass/Model
            if (is_array($user_logged)) {
                $this->empresa_id = $user_logged['empresa_id']
                    ?? $user_logged['empresa']
                    ?? session('empresa_id')
                    ?? null;
            } elseif (is_object($user_logged)) {
                $this->empresa_id = $user_logged->empresa_id
                    ?? $user_logged->empresa
                    ?? session('empresa_id')
                    ?? null;
            }

            // Fallback para usuário autenticado via Auth nativo, se existir
            if (!$this->empresa_id && auth()->check()) {
                $this->empresa_id = auth()->user()->empresa_id ?? null;
            }

            if (!$this->empresa_id) {
                return redirect('/login');
            }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        // 🔒 Filtra somente os registros da empresa em uso
        $query = DB::table('folha_holerites')
            ->join('funcionarios', 'folha_holerites.funcionario_id', '=', 'funcionarios.id')
            ->where('folha_holerites.empresa_id', $this->empresa_id)
            ->where('funcionarios.empresa_id', $this->empresa_id)
            ->select(
                'folha_holerites.*',
                'funcionarios.nome as funcionario_nome'
            );

        if ($request->filled('competencia')) {
            $query->where('folha_holerites.competencia', $request->competencia);
        }

        if ($request->filled('busca')) {
            $busca = $request->busca;
            $query->where(function($q) use ($busca) {
                $q->where('folha_holerites.cpf', 'LIKE', "%{$busca}%")
                    ->orWhere('folha_holerites.matricula', 'LIKE', "%{$busca}%")
                    ->orWhere('funcionarios.nome', 'LIKE', "%{$busca}%");
            });
        }

        $holerites = $query->orderBy('folha_holerites.competencia', 'desc')
            ->orderBy('funcionarios.nome', 'asc')
            ->paginate(25);

        // Competências disponíveis apenas para a empresa logada
        $competencias = DB::table('folha_holerites')
            ->where('empresa_id', $this->empresa_id)
            ->select('competencia')
            ->distinct()
            ->orderBy('competencia', 'desc')
            ->pluck('competencia');

        $title = 'Folha de Pagamento - Contracheques';

        return view('folha_pagamento.index', compact('holerites', 'competencias', 'title'));
    }

    public function importarTxt(Request $request)
    {
        $request->validate([
            'arquivo_txt' => 'required|file|mimes:txt'
        ], [
            'arquivo_txt.required' => 'Selecione um arquivo TXT para importar.',
            'arquivo_txt.mimes' => 'O arquivo deve ser exclusivamente do tipo TXT.'
        ]);

        try {
            $caminhoArquivo = $request->file('arquivo_txt')->getRealPath();
            $conteudo = file_get_contents($caminhoArquivo);

            preg_match('/Competencia:\s*([A-Za-z0-9\/]+)/i', $conteudo, $compMatch);
            $competencia = trim($compMatch[1] ?? Carbon::now()->format('m/Y'));

            $linhas = preg_split("/\r\n|\n|\r/", $conteudo);
            $blocosEmpregados = [];
            $blocoAtual = [];
            $capturando = false;

            foreach ($linhas as $linha) {
                if (str_contains($linha, 'RESUMO GERAL')) {
                    if (!empty($blocoAtual)) {
                        $blocosEmpregados[] = $blocoAtual;
                        $blocoAtual = [];
                    }
                    break;
                }

                if (preg_match('/^\d{3}\.\d{3}\.\d{3}\s+(\d{5})\s+(\d{3}\.\d{3}\.\d{3}-\d{2})/', trim($linha))) {
                    if (!empty($blocoAtual)) {
                        $blocosEmpregados[] = $blocoAtual;
                        $blocoAtual = [];
                    }
                    $capturando = true;
                }

                if ($capturando) {
                    $blocoAtual[] = $linha;
                }
            }

            if (!empty($blocoAtual)) {
                $blocosEmpregados[] = $blocoAtual;
            }

            if (empty($blocosEmpregados)) {
                return redirect()->back()->with('mensagem_erro', 'Nenhum funcionário identificado no layout do arquivo TXT.');
            }

            $processados = 0;
            $ignorados = [];

            DB::transaction(function () use ($blocosEmpregados, $competencia, &$processados, &$ignorados) {
                foreach ($blocosEmpregados as $linhasDoBloco) {
                    $importou = $this->processarBloco($linhasDoBloco, $competencia, $ignorados);
                    if ($importou) {
                        $processados++;
                    }
                }
            });

            $msgSucesso = "Importação da competência {$competencia} concluída! Foram processados {$processados} contracheques para a sua empresa.";

            if (!empty($ignorados)) {
                $totalIgnorados = count($ignorados);
                $amostra = implode(', ', array_slice($ignorados, 0, 4));
                $msgSucesso .= "<br>⚠️ <strong>{$totalIgnorados} registro(s) ignorados</strong> por não pertencerem ao quadro desta empresa: {$amostra}";
                if ($totalIgnorados > 4) {
                    $msgSucesso .= "... e outros.";
                }
            }

            return redirect()->back()->with('mensagem_sucesso', $msgSucesso);

        } catch (\Exception $e) {
            return redirect()->back()->with('mensagem_erro', 'Erro ao processar o arquivo: ' . $e->getMessage());
        }
    }

    private function processarBloco(array $linhas, $competencia, array &$ignorados)
    {
        $primeiraLinha = $linhas[0] ?? '';

        // 1. Identificação do Funcionário
        if (!preg_match('/^([\d\.]+)\s+(\d{4,6})\s+([\d\.\-]+)\s+(.+?)(?:\s{2,}|\s+(\d{6}))/u', trim($primeiraLinha), $dadosIniciais)) {
            return false;
        }

        $divisaoRh = trim($dadosIniciais[1]);
        $matricula = trim($dadosIniciais[2]);
        $cpf       = trim($dadosIniciais[3]);
        $nomeTxt   = trim($dadosIniciais[4]);
        $cpfLimpo  = preg_replace('/\D/', '', $cpf);

        $cbo = null;
        $funcao = null;
        if (preg_match('/(\d{6})\s*-\s*([A-Za-zÀ-ÿ\s\.\-_]+?)(?:\s{2,}|Mensal|$)/u', $primeiraLinha, $mFunc)) {
            $cbo = trim($mFunc[1]);
            $funcao = trim($mFunc[2]);
        }

        // 🔒 Trava Multiempresa
        $funcionario = Funcionario::where('empresa_id', $this->empresa_id)
            ->where(function ($q) use ($cpf, $cpfLimpo) {
                $q->where('cpf', $cpf)
                    ->orWhere('cpf', $cpfLimpo)
                    ->orWhereRaw("REPLACE(REPLACE(cpf, '.', ''), '-', '') = ?", [$cpfLimpo]);
            })->first();

        if (!$funcionario) {
            $ignorados[] = "{$nomeTxt} ({$cpf})";
            return false;
        }

        $textoCompleto = implode("\n", $linhas);

        // 2. Extração das Bases e Totais do Cabeçalho/Rodapé
        preg_match('/Salario Base:\s*([\d\.,]+)/', $textoCompleto, $mSal);
        preg_match('/Base INSS:\s*([\d\.,]+)/', $textoCompleto, $mInss);
        preg_match('/Base IRRF:\s*([\d\.,]+)/', $textoCompleto, $mIrrf);
        preg_match('/Base FGTS:\s*([\d\.,]+)/', $textoCompleto, $mFgts);
        preg_match('/Tot\.Venc\.:\s*([\d\.,]+)/', $textoCompleto, $mVenc);
        preg_match('/Tot\.Desc\.:\s*([\d\.,]+)/', $textoCompleto, $mDesc);
        preg_match('/\*Liquido\*:\s*([\d\.,]+)/', $textoCompleto, $mLiq);
        preg_match('/Vlr\.\s*FGTS:\s*([\d\.,]+)/', $textoCompleto, $mVlrFgts);

        $parseVal = fn($arr) => isset($arr[1]) ? (float)str_replace(['.', ','], ['', '.'], $arr[1]) : 0.00;

        $salarioBase = $parseVal($mSal);
        $baseInss    = $parseVal($mInss);
        $baseIrrf    = $parseVal($mIrrf);
        $baseFgts    = $parseVal($mFgts);
        $totVenc     = $parseVal($mVenc);
        $totDesc     = $parseVal($mDesc);
        $vlrLiq      = $parseVal($mLiq);
        $vlrFgts     = $parseVal($mVlrFgts);

        $holeriteExistente = DB::table('folha_holerites')
            ->where('empresa_id', $this->empresa_id)
            ->where('funcionario_id', $funcionario->id)
            ->where('competencia', $competencia)
            ->first();

        $payload = [
            'empresa_id'        => $this->empresa_id,
            'funcionario_id'    => $funcionario->id,
            'cpf'               => $funcionario->cpf ?: $cpf,
            'matricula'         => $matricula,
            'divisao_rh'        => $divisaoRh,
            'cbo'               => $cbo,
            'funcao'            => $funcao ?: $funcionario->cargo,
            'competencia'       => $competencia,
            'salario_base'      => $salarioBase,
            'base_inss'         => $baseInss,
            'base_irrf'         => $baseIrrf,
            'base_fgts'         => $baseFgts,
            'valor_fgts'        => $vlrFgts,
            'total_vencimentos' => $totVenc,
            'total_descontos'   => $totDesc,
            'valor_liquido'     => $vlrLiq,
            'dados_json'        => json_encode(['nome_txt' => $funcionario->nome]),
            'updated_at'        => now()
        ];

        if ($holeriteExistente) {
            DB::table('folha_holerites')->where('id', $holeriteExistente->id)->update($payload);
            $holeriteId = $holeriteExistente->id;
            DB::table('folha_holerite_itens')->where('folha_holerite_id', $holeriteId)->delete();
        } else {
            $payload['created_at'] = now();
            $holeriteId = DB::table('folha_holerites')->insertGetId($payload);
        }

        // 3. Processamento dos Itens (Proventos e Descontos)
        foreach ($linhas as $idx => $linha) {
            if ($idx < 2) continue;

            if (str_contains($linha, '**Eventos Informativos:**') || str_contains($linha, 'Quitacao em:') || str_contains($linha, '---------------------------------')) {
                break;
            }

            // Remove a coluna lateral esquerda das bases (ex: "Base INSS: 3.055,51", "Tot.Venc.: 3.055,51")
            $restoLinha = preg_replace('/^(Base\s+[A-Z]+:\s*[\d\.,]+|Tot\.[A-Z]+:\s*[\d\.,]+|\*Liquido\*:\s*[\d\.,]+|Vlr\.\s*FGTS:\s*[\d\.,]+|Qtd\.Dp\.[A-Z]+:\s*\d+)/i', '', trim($linha));
            $restoLinha = trim($restoLinha);

            if (empty($restoLinha)) continue;

            // 🔴 3.1 Extrai DESCONTO no final da linha: Cód + Descrição + (Ref opcional) + (Valor entre parênteses)
            if (preg_match('/(\d{3})\s+([A-Za-zÀ-ÿ0-9\.\/\s\-_%]+?)(?:\s+([\d\.,]+))?\s+\(([\d\.,]+)\)$/u', $restoLinha, $mDesc)) {
                $codDesc  = $mDesc[1];
                $descDesc = trim($mDesc[2]);
                $refDesc  = !empty($mDesc[3]) ? trim($mDesc[3]) : null;
                $vlrDesc  = (float)str_replace(['.', ','], ['', '.'], $mDesc[4]);

                if ($vlrDesc > 0) {
                    DB::table('folha_holerite_itens')->insert([
                        'folha_holerite_id' => $holeriteId,
                        'codigo_evento'     => $codDesc,
                        'descricao'         => $descDesc,
                        'referencia'        => $refDesc,
                        'tipo'              => 'desconto',
                        'valor'             => $vlrDesc
                    ]);
                }

                // Remove o desconto processado da linha para analisar o provento restante
                $restoLinha = trim(substr($restoLinha, 0, -strlen($mDesc[0])));
            }

            // 🟢 3.2 Extrai PROVENTO na parte que sobrou
            if (!empty($restoLinha)) {
                // Formato A: Com Referência e Valor (Ex: "001 SALARIO NORMAL 30,00 2.700,00" ou "033 HORA EXTRA 70% 14,12 296,26")
                if (preg_match('/(\d{3})\s+([A-Za-zÀ-ÿ0-9\.\/\s\-_%]+?)\s+([\d\.,]+)\s+([\d\.,]+)$/u', $restoLinha, $mProv)) {
                    $vlrProv = (float)str_replace(['.', ','], ['', '.'], $mProv[4]);
                    if ($vlrProv > 0) {
                        DB::table('folha_holerite_itens')->insert([
                            'folha_holerite_id' => $holeriteId,
                            'codigo_evento'     => $mProv[1],
                            'descricao'         => trim($mProv[2]),
                            'referencia'        => trim($mProv[3]),
                            'tipo'              => 'provento',
                            'valor'             => $vlrProv
                        ]);
                    }
                }
                // Formato B: Sem Referência (Ex: "004 DSR S/REMUNERACAO 59,25" ou "077 AJUDA DE CUSTO. 1.000,00")
                elseif (preg_match('/(\d{3})\s+([A-Za-zÀ-ÿ0-9\.\/\s\-_%]+?)\s+([\d\.,]+)$/u', $restoLinha, $mProv)) {
                    $vlrProv = (float)str_replace(['.', ','], ['', '.'], $mProv[3]);
                    if ($vlrProv > 0) {
                        DB::table('folha_holerite_itens')->insert([
                            'folha_holerite_id' => $holeriteId,
                            'codigo_evento'     => $mProv[1],
                            'descricao'         => trim($mProv[2]),
                            'referencia'        => null,
                            'tipo'              => 'provento',
                            'valor'             => $vlrProv
                        ]);
                    }
                }
            }
        }

        return true;
    }

    public function gerarPdf($id)
    {
        $holerite = DB::table('folha_holerites')
            ->where('empresa_id', $this->empresa_id)
            ->where('id', $id)
            ->first();

        if (!$holerite) {
            abort(403, 'Acesso não autorizado.');
        }

        $funcionario = Funcionario::where('empresa_id', $this->empresa_id)->find($holerite->funcionario_id);

        $itens = DB::table('folha_holerite_itens')
            ->where('folha_holerite_id', $holerite->id)
            ->orderBy('tipo', 'desc')
            ->orderBy('codigo_evento', 'asc')
            ->get();

        $dadosEmpresa = $this->getEmpresaConfig($this->empresa_id);

        $pdf = Pdf::loadView('folha_pagamento.holerite_pdf', compact('holerite', 'itens', 'funcionario', 'dadosEmpresa'))
            ->setPaper('a4', 'portrait');

        $compNome = str_replace('/', '-', $holerite->competencia);
        return $pdf->stream("Recibo_{$compNome}_{$holerite->matricula}.pdf");
    }

    public function excluirCompetencia(Request $request, $competencia)
    {
        $compLimpa = urldecode($competencia);

        // 🔒 Localiza apenas os IDs pertencentes à empresa em sessão
        $holeritesIds = DB::table('folha_holerites')
            ->where('empresa_id', $this->empresa_id)
            ->where('competencia', $compLimpa)
            ->pluck('id');

        if ($holeritesIds->isEmpty()) {
            return redirect()->route('folha.index')->with('mensagem_erro', "Nenhum registro localizado para a competência {$compLimpa}.");
        }

        DB::transaction(function () use ($holeritesIds, $compLimpa) {
            // 1. Remove os itens dos contracheques em lote
            DB::table('folha_holerite_itens')->whereIn('folha_holerite_id', $holeritesIds)->delete();

            // 2. Remove os cabeçalhos dos contracheques em lote
            DB::table('folha_holerites')
                ->where('empresa_id', $this->empresa_id)
                ->whereIn('id', $holeritesIds)
                ->delete();
        });

        return redirect()->route('folha.index')->with('mensagem_sucesso', "Todos os contracheques da competência {$compLimpa} foram excluídos com sucesso!");
    }

    private function getEmpresaConfig($empresaId)
    {
        $configNotas = DB::table('config_notas')->where('empresa_id', $empresaId)->first();
        if ($configNotas) {
            return [
                'razao_social' => $configNotas->razao_social,
                'cnpj'          => $configNotas->cnpj,
                'endereco'      => "{$configNotas->logradouro}, {$configNotas->numero} - {$configNotas->bairro}, {$configNotas->municipio} - {$configNotas->UF}"
            ];
        }

        return [
            'razao_social' => 'FERSOFT ERP',
            'cnpj'          => '00.000.000/0001-00',
            'endereco'      => 'Endereço não cadastrado'
        ];
    }
}
