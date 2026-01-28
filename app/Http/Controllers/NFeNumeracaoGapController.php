<?php

namespace App\Http\Controllers;

use App\Models\Filial;
use App\Models\NFeNumeracaoGap;
use App\Models\NFeInutilizacao;
use App\Models\ConfigNota;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\NFeInutilizacaoService;
use App\Services\LogService;
use Illuminate\Support\Facades\DB;

class NFeNumeracaoGapController extends BaseController
{
    /**
     * Model base do relatório (view SQL).
     */
    protected $model = NFeNumeracaoGap::class;

    /**
     * Página padrão para redirecionamento.
     */
    protected $redirectPage = '/relatorios/nfe-numeracao-gaps';

    /**
     * View padrão de listagem.
     */
    protected $listView = 'relatorios.nfe_numeracao_gaps.index';

    /**
     * Título padrão do relatório.
     */
    protected $formTitle = 'Relatório de Saltos de Numeração de NF-e';

    /**
     * @var NFeInutilizacaoService
     */
    protected $nfeInutilizacaoService;

    /**
     * @var LogService
     */
    protected $logService;

    public function __construct(NFeInutilizacaoService $nfeInutilizacaoService)
    {
        parent::__construct(); // BaseController cuida de empresa_id, usuario_id, filial_id

        $this->nfeInutilizacaoService = $nfeInutilizacaoService;

        // Inicializa LogService com os mesmos dados do BaseController
        $this->middleware(function ($request, $next) {
            $this->logService = new LogService(
                $this->empresa_id,
                $this->usuario_id,
                $this->filial_id
            );
            return $next($request);
        });
    }

    /**
     * Implementação mínima exigida pelo BaseController.
     * Como este controller é apenas de relatório, não há regras de CRUD aqui.
     */
    protected function rules(): array
    {
        return [];
    }

    /**
     * Implementação mínima exigida pelo BaseController.
     */
    protected function messages(): array
    {
        return [];
    }

    /**
     * Resolve texto de ambiente ('producao'/'homologacao') com base em ConfigNota/Filial.
     */
    protected function resolveAmbienteTexto(?int $filialId = null): string
    {
        $emitente = null;

        if (!is_null($filialId)) {
            $emitente = Filial::where('empresa_id', $this->empresa_id)
                ->where('id', $filialId)
                ->first();
        }

        if (!$emitente) {
            $emitente = ConfigNota::where('empresa_id', $this->empresa_id)->first();
        }

        $tpAmb = (int) ($emitente->ambiente ?? 1);
        if (!in_array($tpAmb, [1, 2], true)) {
            $tpAmb = 1;
        }

        return $tpAmb === 1 ? 'producao' : 'homologacao';
    }

    /**
     * Relatório de saltos de numeração (tela paginada).
     * AGORA: ignora gaps já totalmente cobertos por inutilizações autorizadas OU marcadas como ignoradas.
     */
    public function index(Request $request)
    {
        $tableGaps = (new NFeNumeracaoGap)->getTable(); // vw_nfe_numeracao_gaps

        $query = NFeNumeracaoGap::query()
            ->where($tableGaps . '.empresa_id', $this->empresa_id);

        /**
         * 🔹 Excluir gaps já inutilizados OU ignorados
         * (faixa inteira coberta por inutilização AUTORIZADA ou com ignorar_gaps = 1)
         *
         * Obs.: usamos o operador <=> do MySQL para comparar NULL = NULL (Matriz).
         */
        $query->whereRaw("
            NOT EXISTS (
                SELECT 1
                  FROM nfe_inutilizacoes ni
                 WHERE ni.empresa_id = {$this->empresa_id}
                   AND ni.modelo = '55'
                   AND (ni.status = 'autorizada' OR ni.ignorar_gaps = 1)
                   AND ni.serie = {$tableGaps}.serie
                   AND ni.numero_inicial <= {$tableGaps}.numero_inicial_pulado
                   AND ni.numero_final   >= {$tableGaps}.numero_final_pulado
                   AND (ni.filial_id <=> {$tableGaps}.filial_id)
            )
        ");

        // ========= FILTROS =========

        $filialId = $request->get('filial_id');
        if (
            $filialId === 'null' ||
            $filialId === '' ||
            (isset($filialId) && (int) $filialId <= 0) // trata -1, 0, etc. como null (Matriz)
        ) {
            $filialId = null;
        }

        if ($filialId !== null) {
            $query->where('filial_id', $filialId);
        } elseif ($request->has('filial_id')) {
            $query->whereNull('filial_id');
        }

        if ($request->filled('serie')) {
            $query->where('serie', $request->get('serie'));
        }

        $dataInicial = $request->get('data_inicial');
        $dataFinal   = $request->get('data_final');

        if ($dataInicial) {
            $dataInicialCarbon = Carbon::parse($dataInicial);

            $query->where(function ($q) use ($dataInicialCarbon) {
                $q->whereDate('data_doc_anterior', '>=', $dataInicialCarbon)
                    ->orWhereDate('data_doc_atual', '>=', $dataInicialCarbon);
            });
        }

        if ($dataFinal) {
            $dataFinalCarbon = Carbon::parse($dataFinal);

            $query->where(function ($q) use ($dataFinalCarbon) {
                $q->whereDate('data_doc_anterior', '<=', $dataFinalCarbon)
                    ->orWhereDate('data_doc_atual', '<=', $dataFinalCarbon);
            });
        }

        if ($request->boolean('somente_sem_chave')) {
            $query->where(function ($q) {
                $q->whereNull('chave_nfe_anterior')
                    ->orWhere('chave_nfe_anterior', '')
                    ->orWhereNull('chave_nfe_atual')
                    ->orWhere('chave_nfe_atual', '');
            });
        }

        $registros = $query
            ->orderBy('filial_label')
            ->orderBy('serie')
            ->orderBy('numero_inicial_pulado')
            ->paginate(50)
            ->appends($request->query());

        $filiais = Filial::where('empresa_id', $this->empresa_id)
            ->select('id', 'nome_fantasia')
            ->orderBy('nome_fantasia')
            ->get();

        $filters = [
            'filial_id'         => $request->get('filial_id'),
            'serie'             => $request->get('serie'),
            'data_inicial'      => $dataInicial,
            'data_final'        => $dataFinal,
            'somente_sem_chave' => $request->boolean('somente_sem_chave'),
        ];

        // Log simples da visualização do relatório
        if ($this->logService) {
            $this->logService->registrar('view', NFeNumeracaoGap::class, [
                'acao'    => 'listar_gaps',
                'filtros' => $filters,
            ]);
        }

        return view($this->listView, [
            'title'     => $this->formTitle,
            'registros' => $registros,
            'filiais'   => $filiais,
            'filters'   => $filters,
        ]);
    }

    /**
     * Regras de validação para inutilização de faixa.
     */
    protected function rulesInutilizar(): array
    {
        return [
            'filial_id'       => ['nullable', 'integer'],
            'serie'           => ['required', 'integer', 'min:1'],
            'numero_inicial'  => ['required', 'integer', 'min:1'],
            'numero_final'    => ['required', 'integer', 'gte:numero_inicial'],
            'justificativa'   => ['required', 'string', 'min:15', 'max:255'],
            'modelo'          => ['nullable', 'string', 'max:2'],  // 55 / 65
            //'ano'             => ['nullable', 'string', 'max:2'],  // AA
        ];
    }

    /**
     * Mensagens de erro para inutilização.
     */
    protected function messagesInutilizar(): array
    {
        return [
            'serie.required'          => 'A série é obrigatória.',
            'serie.integer'           => 'A série deve ser um número inteiro.',
            'numero_inicial.required' => 'O número inicial é obrigatório.',
            'numero_inicial.integer'  => 'O número inicial deve ser um número inteiro.',
            'numero_final.required'   => 'O número final é obrigatório.',
            'numero_final.integer'    => 'O número final deve ser um número inteiro.',
            'numero_final.gte'        => 'O número final deve ser maior ou igual ao número inicial.',
            'justificativa.required'  => 'A justificativa é obrigatória.',
            'justificativa.min'       => 'A justificativa deve ter pelo menos :min caracteres.',
        ];
    }

    /**
     * Diagnostica uma faixa de numeração para saber quais números
     * já foram utilizados (vendas/compras) e quais ainda estão livres.
     *
     * Usado principalmente quando a SEFAZ retorna [241] "Um numero da faixa ja foi utilizado".
     */
    public function diagnosticarFaixa(Request $request)
    {
        $dados = $request->validate([
            'filial_id'       => ['nullable'],
            'serie'           => ['required', 'integer', 'min:1'],
            'numero_inicial'  => ['required', 'integer', 'min:1'],
            'numero_final'    => ['required', 'integer', 'gte:numero_inicial'],
        ]);

        // Normaliza filial (Matriz = null)
        $filialId = $dados['filial_id'] ?? null;
        if (
            $filialId === 'null' ||
            $filialId === '' ||
            (isset($filialId) && (int) $filialId <= 0)
        ) {
            $filialId = null;
        }

        $empresaId = $this->empresa_id;
        $serie     = (int) $dados['serie'];
        $ini       = (int) $dados['numero_inicial'];
        $fim       = (int) $dados['numero_final'];

        // Busca em vendas
        $vendas = DB::table('vendas')
            ->where('empresa_id', $empresaId)
            ->when(
                is_null($filialId),
                function ($q) {
                    $q->whereNull('filial_id');
                },
                function ($q) use ($filialId) {
                    $q->where('filial_id', $filialId);
                }
            )
            ->where('nSerie', $serie)
            ->whereBetween('NfNumero', [$ini, $fim])
            ->select(
                DB::raw("'V' as origem"),
                'id as origem_id',
                'NfNumero as numero',
                'data_emissao as data_doc',
                'chave',
                'estado as status_nfe'
            )
            ->get();

        // Busca em compras (usa a mesma lógica da view)
        $compras = DB::table('compras as c')
            ->leftJoin('config_notas as cn', function ($join) use ($serie) {
                $join->on('cn.empresa_id', '=', 'c.empresa_id')
                    ->where('cn.numero_serie_nfe', '=', $serie);
            })
            ->where('c.empresa_id', $empresaId)
            ->when(
                is_null($filialId),
                function ($q) {
                    $q->whereNull('c.filial_id');
                },
                function ($q) use ($filialId) {
                    $q->where('c.filial_id', $filialId);
                }
            )
            ->whereBetween('c.numero_emissao', [$ini, $fim])
            ->select(
                DB::raw("'C' as origem"),
                'c.id as origem_id',
                'c.numero_emissao as numero',
                'c.data_emissao as data_doc',
                'c.chave',
                'c.estado as status_nfe'
            )
            ->get();

        $usadosCollection = $vendas->concat($compras)->sortBy('numero')->values();

        $todos      = range($ini, $fim);
        $numerosUsados = $usadosCollection->pluck('numero')->map(function ($n) {
            return (int) $n;
        })->unique()->values()->all();

        $livres = array_values(array_diff($todos, $numerosUsados));

        return response()->json([
            'ok'        => true,
            'empresa_id'=> $empresaId,
            'filial_id' => $filialId,
            'serie'     => $serie,
            'faixa'     => ['inicio' => $ini, 'fim' => $fim],
            'utilizados'=> $usadosCollection,
            'livres'    => $livres,
        ]);
    }

    /**
     * Inutiliza uma faixa de numeração a partir de um salto identificado.
     * AGORA: registra logs usando LogService e resolve ambiente corretamente.
     */
    public function inutilizarFaixa(Request $request)
    {
        $dados = $request->validate(
            $this->rulesInutilizar(),
            $this->messagesInutilizar()
        );

        if (
            isset($dados['filial_id']) &&
            (
                $dados['filial_id'] === 'null' ||
                $dados['filial_id'] === '' ||
                (int) $dados['filial_id'] <= 0 // trata -1 ou 0 como null (Matriz)
            )
        ) {
            $dados['filial_id'] = null;
        }

        $modelo = $dados['modelo'] ?? '55';
        $ano    = Carbon::now()->format('y');

        // Resolve ambiente (producao/homologacao) de acordo com ConfigNota/Filial
        $ambienteTexto = $this->resolveAmbienteTexto($dados['filial_id'] ?? null);

        $payloadService = [
            'empresa_id'      => $this->empresa_id,
            'filial_id'       => $dados['filial_id'] ?? null,
            'usuario_id'      => $this->usuario_id,
            'modelo'          => $modelo,
            'serie'           => (int) $dados['serie'],
            'numero_inicial'  => (int) $dados['numero_inicial'],
            'numero_final'    => (int) $dados['numero_final'],
            'ano'             => $ano,
            'justificativa'   => $dados['justificativa'],
            'ambiente'        => $ambienteTexto,
            'origem'          => 'salto_numeracao',
        ];

        try {
            // Log de requisição de inutilização
            if ($this->logService) {
                $this->logService->registrar('nfe_inutilizacao_gap_request', NFeInutilizacao::class, [
                    'registro_id' => null,
                    'dados_antes' => null,
                    'dados_depois' => $payloadService,
                ]);
            }

            // Chama NFePHP via service e grava inutilização localmente
            $this->nfeInutilizacaoService->inutilizarFaixa($payloadService);

            // Tenta localizar o registro gravado para log completo
            $registro = NFeInutilizacao::where('empresa_id', $this->empresa_id)
                ->where(function ($q) use ($payloadService) {
                    if (is_null($payloadService['filial_id'])) {
                        $q->whereNull('filial_id');
                    } else {
                        $q->where('filial_id', $payloadService['filial_id']);
                    }
                })
                ->where('modelo', $modelo)
                ->where('serie', $payloadService['serie'])
                ->where('numero_inicial', $payloadService['numero_inicial'])
                ->where('numero_final', $payloadService['numero_final'])
                ->orderByDesc('id')
                ->first();

            if ($this->logService) {
                $this->logService->registrar('nfe_inutilizacao_gap_success', NFeInutilizacao::class, [
                    'registro_id' => $registro->id ?? null,
                    'dados_antes' => null,
                    'dados_depois' => $registro ? $registro->toArray() : $payloadService,
                ]);
            }

            session()->flash('mensagem_sucesso', 'Faixa enviada para inutilização com sucesso.');
        } catch (\Throwable $e) {
            Log::error('Erro ao inutilizar faixa de NF-e a partir de salto de numeração', [
                'empresa_id' => $this->empresa_id,
                'dados'      => $payloadService,
                'exception'  => $e,
            ]);

            if ($this->logService) {
                $this->logService->registrar('nfe_inutilizacao_gap_error', NFeInutilizacao::class, [
                    'registro_id' => null,
                    'dados_antes' => null,
                    'dados_depois' => [
                        'payload' => $payloadService,
                        'erro'    => $e->getMessage(),
                    ],
                ]);
            }

            session()->flash(
                'mensagem_erro',
                'Erro ao inutilizar faixa: ' . $e->getMessage()
            );
        }

        return redirect($this->redirectPage);
    }

    /**
     * Inutilização manual feita a partir da tela de "Inutilizações de NF-e".
     * Reaproveita NFeInutilizacaoService e LogService.
     * Ambiente agora também é resolvido pelo ConfigNota/Filial.
     */
    public function inutilizarFaixaManual(Request $request)
    {
        $dados = $request->validate(
            $this->rulesInutilizar(),
            $this->messagesInutilizar()
        );

        if (
            isset($dados['filial_id']) &&
            (
                $dados['filial_id'] === 'null' ||
                $dados['filial_id'] === '' ||
                (int) $dados['filial_id'] <= 0
            )
        ) {
            $dados['filial_id'] = null;
        }

        $modelo = $dados['modelo'] ?? '55';
        $ano    = $dados['ano']    ?? Carbon::now()->format('y');

        $ambienteTexto = $this->resolveAmbienteTexto($dados['filial_id'] ?? null);

        $payloadService = [
            'empresa_id'      => $this->empresa_id,
            'filial_id'       => $dados['filial_id'] ?? null,
            'usuario_id'      => $this->usuario_id,
            'modelo'          => $modelo,
            'serie'           => (int) $dados['serie'],
            'numero_inicial'  => (int) $dados['numero_inicial'],
            'numero_final'    => (int) $dados['numero_final'],
            'ano'             => $ano,
            'justificativa'   => $dados['justificativa'],
            'ambiente'        => $ambienteTexto,
            'origem'          => 'manual_view_inutilizacoes',
        ];

        try {
            if ($this->logService) {
                $this->logService->registrar('nfe_inutilizacao_manual_request', NFeInutilizacao::class, [
                    'registro_id'  => null,
                    'dados_antes'  => null,
                    'dados_depois' => $payloadService,
                ]);
            }

            $registro = $this->nfeInutilizacaoService->inutilizarFaixa($payloadService);

            if ($this->logService) {
                $this->logService->registrar('nfe_inutilizacao_manual_success', NFeInutilizacao::class, [
                    'registro_id'  => $registro->id ?? null,
                    'dados_antes'  => null,
                    'dados_depois' => $registro ? $registro->toArray() : $payloadService,
                ]);
            }

            session()->flash('mensagem_sucesso', 'Faixa inutilizada com sucesso.');
        } catch (\Throwable $e) {
            Log::error('Erro ao inutilizar faixa de NF-e manualmente (tela de inutilizações)', [
                'empresa_id' => $this->empresa_id,
                'dados'      => $payloadService,
                'exception'  => $e,
            ]);

            if ($this->logService) {
                $this->logService->registrar('nfe_inutilizacao_manual_error', NFeInutilizacao::class, [
                    'registro_id'  => null,
                    'dados_antes'  => null,
                    'dados_depois' => [
                        'payload' => $payloadService,
                        'erro'    => $e->getMessage(),
                    ],
                ]);
            }

            session()->flash(
                'mensagem_erro',
                'Erro ao inutilizar faixa: ' . $e->getMessage()
            );
        }

        return redirect()->route('relatorios.nfe_inutilizacoes.index');
    }

    /**
     * Relatório individual de uma inutilização específica.
     * Mostra dados do registro + resumo do XML de retorno.
     */
    public function relatorioInutilizacaoIndividual($id)
    {
        $registro = NFeInutilizacao::where('empresa_id', $this->empresa_id)
            ->where('id', $id)
            ->firstOrFail();

        $xmlResumo = null;

        if (! empty($registro->xml_retorno)) {
            try {
                $xml = simplexml_load_string($registro->xml_retorno);

                $retInut = $xml->retInutNFe ?? $xml;
                $infInut = $retInut->infInut ?? ($xml->infInut ?? null);

                if ($infInut) {
                    $xmlResumo = [
                        'tpAmb'    => (string) ($infInut->tpAmb ?? ''),
                        'verAplic' => (string) ($infInut->verAplic ?? ''),
                        'cStat'    => (string) ($infInut->cStat ?? ''),
                        'xMotivo'  => (string) ($infInut->xMotivo ?? ''),
                        'cUF'      => (string) ($infInut->cUF ?? ''),
                        'ano'      => (string) ($infInut->ano ?? ''),
                        'CNPJ'     => (string) ($infInut->CNPJ ?? ''),
                        'mod'      => (string) ($infInut->mod ?? ''),
                        'serie'    => (string) ($infInut->serie ?? ''),
                        'nNFIni'   => (string) ($infInut->nNFIni ?? ''),
                        'nNFFin'   => (string) ($infInut->nNFFin ?? ''),
                        'dhRecbto' => (string) ($infInut->dhRecbto ?? ''),
                        'nProt'    => (string) ($infInut->nProt ?? ''),
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('Erro ao interpretar XML na visualização individual da inutilização', [
                    'empresa_id' => $this->empresa_id,
                    'registro_id'=> $registro->id,
                    'exception'  => $e,
                ]);
            }
        }

        if ($this->logService) {
            $this->logService->registrar('view', NFeInutilizacao::class, [
                'acao'        => 'relatorio_individual_inutilizacao',
                'registro_id' => $registro->id,
            ]);
        }

        return view('relatorios.nfe_inutilizacoes.show', [
            'registro'  => $registro,
            'xmlResumo' => $xmlResumo,
        ]);
    }

    /**
     * Executa ações sobre um documento específico (anterior ou atual).
     */
    public function acaoDocumento(Request $request)
    {
        $validated = $request->validate([
            'tipo_doc'      => ['required', 'in:anterior,atual'], // qual lado do salto
            'origem'        => ['required', 'in:V,C'],            // V = venda, C = compra
            'origem_id'     => ['required', 'integer'],
            'acao'          => ['required', 'in:reenviar,cancelar'],
            'justificativa' => ['nullable', 'string', 'max:255'],
        ], [
            'tipo_doc.required'  => 'O tipo de documento é obrigatório.',
            'tipo_doc.in'        => 'Tipo de documento inválido.',
            'origem.required'    => 'A origem é obrigatória.',
            'origem.in'          => 'Origem inválida.',
            'origem_id.required' => 'O ID de origem é obrigatório.',
            'origem_id.integer'  => 'O ID de origem deve ser um número inteiro.',
            'acao.required'      => 'A ação é obrigatória.',
            'acao.in'            => 'Ação inválida.',
        ]);

        try {
            // (Aqui você ainda vai plugar o service fiscal real)
            if ($this->logService) {
                $this->logService->registrar('acao_documento_gap', NFeNumeracaoGap::class, [
                    'registro_id' => $validated['origem_id'],
                    'dados_antes' => null,
                    'dados_depois' => $validated,
                ]);
            }

            session()->flash(
                'mensagem_sucesso',
                'Ação sobre o documento registrada. Ajuste a implementação para chamar o service fiscal real.'
            );
        } catch (\Throwable $e) {
            Log::error('Erro na ação sobre documento de NF-e a partir de salto de numeração', [
                'empresa_id' => $this->empresa_id,
                'dados'      => $validated,
                'exception'  => $e,
            ]);

            if ($this->logService) {
                $this->logService->registrar('acao_documento_gap_error', NFeNumeracaoGap::class, [
                    'registro_id' => $validated['origem_id'],
                    'dados_antes' => null,
                    'dados_depois' => [
                        'dados' => $validated,
                        'erro'  => $e->getMessage(),
                    ],
                ]);
            }

            session()->flash(
                'mensagem_erro',
                'Erro ao executar ação sobre documento: ' . $e->getMessage()
            );
        }

        return redirect($this->redirectPage);
    }

    /**
     * Versão "PDF" do relatório de gaps:
     * - reusa os mesmos filtros do index()
     * - aplica também o filtro de NOT EXISTS (não mostrar já inutilizadas OU ignoradas)
     */
    public function pdf(Request $request)
    {
        $tableGaps = (new NFeNumeracaoGap)->getTable();

        $query = NFeNumeracaoGap::query()
            ->where($tableGaps . '.empresa_id', $this->empresa_id);

        // Mesmo filtro para remover gaps já inutilizados OU ignorados
        $query->whereRaw("
            NOT EXISTS (
                SELECT 1
                  FROM nfe_inutilizacoes ni
                 WHERE ni.empresa_id = {$this->empresa_id}
                   AND ni.modelo = '55'
                   AND (ni.status = 'autorizada' OR ni.ignorar_gaps = 1)
                   AND ni.serie = {$tableGaps}.serie
                   AND ni.numero_inicial <= {$tableGaps}.numero_inicial_pulado
                   AND ni.numero_final   >= {$tableGaps}.numero_final_pulado
                   AND (ni.filial_id <=> {$tableGaps}.filial_id)
            )
        ");

        $filialId = $request->get('filial_id');
        if (
            $filialId === 'null' ||
            $filialId === '' ||
            (isset($filialId) && (int) $filialId <= 0) // trata -1, 0, etc. como null (Matriz)
        ) {
            $filialId = null;
        }

        if ($filialId !== null) {
            $query->where('filial_id', $filialId);
        } elseif ($request->has('filial_id')) {
            $query->whereNull('filial_id');
        }

        if ($request->filled('serie')) {
            $query->where('serie', $request->get('serie'));
        }

        $dataInicial = $request->get('data_inicial');
        $dataFinal   = $request->get('data_final');

        if ($dataInicial) {
            $dataInicialCarbon = Carbon::parse($dataInicial);

            $query->where(function ($q) use ($dataInicialCarbon) {
                $q->whereDate('data_doc_anterior', '>=', $dataInicialCarbon)
                    ->orWhereDate('data_doc_atual', '>=', $dataInicialCarbon);
            });
        }

        if ($dataFinal) {
            $dataFinalCarbon = Carbon::parse($dataFinal);

            $query->where(function ($q) use ($dataFinalCarbon) {
                $q->whereDate('data_doc_anterior', '<=', $dataFinalCarbon)
                    ->orWhereDate('data_doc_atual', '<=', $dataFinalCarbon);
            });
        }

        if ($request->boolean('somente_sem_chave')) {
            $query->where(function ($q) {
                $q->whereNull('chave_nfe_anterior')
                    ->orWhere('chave_nfe_anterior', '')
                    ->orWhereNull('chave_nfe_atual')
                    ->orWhere('chave_nfe_atual', '');
            });
        }

        // SEM paginação: traz todos os saltos que batem no filtro
        $registros = $query
            ->orderBy('filial_label')
            ->orderBy('serie')
            ->orderBy('numero_inicial_pulado')
            ->get();

        $filialNome = null;
        if ($filialId !== null) {
            $filial = Filial::where('empresa_id', $this->empresa_id)
                ->select('id', 'nome_fantasia')
                ->find($filialId);

            if ($filial) {
                $filialNome = $filial->nome_fantasia;
            }
        }

        $filters = [
            'filial_id'         => $request->get('filial_id'),
            'filial_nome'       => $filialNome,
            'serie'             => $request->get('serie'),
            'data_inicial'      => $dataInicial,
            'data_final'        => $dataFinal,
            'somente_sem_chave' => $request->boolean('somente_sem_chave'),
        ];

        $totalGaps           = $registros->count();
        $totalNumerosPulados = $registros->sum('quantidade_pulada');

        $porFilial = $registros
            ->groupBy('filial_label')
            ->map(function ($grupo) {
                return [
                    'gaps'            => $grupo->count(),
                    'numeros_pulados' => $grupo->sum('quantidade_pulada'),
                ];
            })
            ->sortKeys()
            ->toArray();

        $porSerie = $registros
            ->groupBy('serie')
            ->map(function ($grupo) {
                return [
                    'gaps'            => $grupo->count(),
                    'numeros_pulados' => $grupo->sum('quantidade_pulada'),
                ];
            })
            ->sortKeys()
            ->toArray();

        $summary = [
            'total_gaps'    => $totalGaps,
            'total_numeros' => $totalNumerosPulados,
            'por_filial'    => $porFilial,
            'por_serie'     => $porSerie,
        ];

        if ($this->logService) {
            $this->logService->registrar('view', NFeNumeracaoGap::class, [
                'acao'    => 'relatorio_pdf_gaps',
                'filtros' => $filters,
                'resumo'  => $summary,
            ]);
        }

        return view(
            'relatorios.nfe_numeracao_gaps.pdf',
            compact('registros', 'filters', 'summary')
        );
    }

    /**
     * LISTAGEM DE INUTILIZAÇÕES (view de inutilizadas).
     */
    public function inutilizacoes(Request $request)
    {
        $query = NFeInutilizacao::query()
            ->where('empresa_id', $this->empresa_id);

        $filialId = $request->get('filial_id');
        if (
            $filialId === 'null' ||
            $filialId === '' ||
            (isset($filialId) && (int) $filialId <= 0)
        ) {
            $filialId = null;
        }

        if ($filialId !== null) {
            $query->where('filial_id', $filialId);
        } elseif ($request->has('filial_id')) {
            $query->whereNull('filial_id');
        }

        if ($request->filled('modelo')) {
            $query->where('modelo', $request->get('modelo'));
        }

        if ($request->filled('serie')) {
            $query->where('serie', $request->get('serie'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('ambiente')) {
            $query->where('ambiente', $request->get('ambiente'));
        }

        if ($request->filled('numero_inicial')) {
            $query->where('numero_inicial', '>=', $request->get('numero_inicial'));
        }

        if ($request->filled('numero_final')) {
            $query->where('numero_final', '<=', $request->get('numero_final'));
        }

        $dataInicial = $request->get('data_inicial');
        $dataFinal   = $request->get('data_final');

        if ($dataInicial) {
            $query->whereDate('created_at', '>=', Carbon::parse($dataInicial));
        }

        if ($dataFinal) {
            $query->whereDate('created_at', '<=', Carbon::parse($dataFinal));
        }

        $registros = $query
            ->orderByDesc('created_at')
            ->paginate(50)
            ->appends($request->query());

        $filiais = Filial::where('empresa_id', $this->empresa_id)
            ->select('id', 'nome_fantasia')
            ->orderBy('nome_fantasia')
            ->get();

        $filters = [
            'filial_id'      => $request->get('filial_id'),
            'modelo'         => $request->get('modelo'),
            'serie'          => $request->get('serie'),
            'status'         => $request->get('status'),
            'ambiente'       => $request->get('ambiente'),
            'numero_inicial' => $request->get('numero_inicial'),
            'numero_final'   => $request->get('numero_final'),
            'data_inicial'   => $dataInicial,
            'data_final'     => $dataFinal,
        ];

        if ($this->logService) {
            $this->logService->registrar('view', NFeInutilizacao::class, [
                'acao'    => 'listar_inutilizacoes',
                'filtros' => $filters,
            ]);
        }

        return view('relatorios.nfe_inutilizacoes.index', [
            'title'     => 'Inutilizações de NF-e',
            'registros' => $registros,
            'filiais'   => $filiais,
            'filters'   => $filters,
        ]);
    }

    /**
     * RELATÓRIO PDF das inutilizações.
     */
    public function inutilizacoesPdf(Request $request)
    {
        $query = NFeInutilizacao::query()
            ->where('empresa_id', $this->empresa_id);

        $filialId = $request->get('filial_id');
        if (
            $filialId === 'null' ||
            $filialId === '' ||
            (isset($filialId) && (int) $filialId <= 0)
        ) {
            $filialId = null;
        }

        if ($filialId !== null) {
            $query->where('filial_id', $filialId);
        } elseif ($request->has('filial_id')) {
            $query->whereNull('filial_id');
        }

        if ($request->filled('modelo')) {
            $query->where('modelo', $request->get('modelo'));
        }

        if ($request->filled('serie')) {
            $query->where('serie', $request->get('serie'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('ambiente')) {
            $query->where('ambiente', $request->get('ambiente'));
        }

        if ($request->filled('numero_inicial')) {
            $query->where('numero_inicial', '>=', $request->get('numero_inicial'));
        }

        if ($request->filled('numero_final')) {
            $query->where('numero_final', '<=', $request->get('numero_final'));
        }

        $dataInicial = $request->get('data_inicial');
        $dataFinal   = $request->get('data_final');

        if ($dataInicial) {
            $query->whereDate('created_at', '>=', Carbon::parse($dataInicial));
        }

        if ($dataFinal) {
            $query->whereDate('created_at', '<=', Carbon::parse($dataFinal));
        }

        $registros = $query
            ->orderByDesc('created_at')
            ->get();

        $filialNome = null;
        if ($filialId !== null) {
            $filial = Filial::where('empresa_id', $this->empresa_id)
                ->select('id', 'nome_fantasia')
                ->find($filialId);

            if ($filial) {
                $filialNome = $filial->nome_fantasia;
            }
        }

        $filters = [
            'filial_id'      => $request->get('filial_id'),
            'filial_nome'    => $filialNome,
            'modelo'         => $request->get('modelo'),
            'serie'          => $request->get('serie'),
            'status'         => $request->get('status'),
            'ambiente'       => $request->get('ambiente'),
            'numero_inicial' => $request->get('numero_inicial'),
            'numero_final'   => $request->get('numero_final'),
            'data_inicial'   => $dataInicial,
            'data_final'     => $dataFinal,
        ];

        $summary = [
            'total'        => $registros->count(),
            'por_status'   => $registros->groupBy('status')->map->count()->toArray(),
            'por_ambiente' => $registros->groupBy('ambiente')->map->count()->toArray(),
        ];

        if ($this->logService) {
            $this->logService->registrar('view', NFeInutilizacao::class, [
                'acao'    => 'relatorio_pdf_inutilizacoes',
                'filtros' => $filters,
                'resumo'  => $summary,
            ]);
        }

        return view('relatorios.nfe_inutilizacoes.pdf', [
            'registros' => $registros,
            'filters'   => $filters,
            'summary'   => $summary,
        ]);
    }

    public function marcarInutilizacaoComoIgnorada(Request $request, $id)
    {
        $registro = NFeInutilizacao::where('empresa_id', $this->empresa_id)
            ->where('id', $id)
            ->firstOrFail();

        $dados = $request->validate([
            'ignorar' => ['nullable', 'boolean'],
            'motivo'  => ['nullable', 'string', 'max:255'],
        ]);

        $ignorar = $dados['ignorar'] ?? true;

        $registro->ignorar_gaps = (bool) $ignorar;

        if ($ignorar) {
            $registro->motivo_ignoracao = $dados['motivo']
                ?: 'Ignorado manualmente no relatório de gaps.';
        } else {
            // se quiser "desmarcar" no futuro
            $registro->motivo_ignoracao = null;
        }

        $registro->save();

        if ($this->logService) {
            $this->logService->registrar(
                'nfe_inutilizacao_toggle_ignorar_gaps',
                NFeInutilizacao::class,
                [
                    'registro_id'  => $registro->id,
                    'dados_antes'  => null,
                    'dados_depois' => [
                        'ignorar_gaps'     => $registro->ignorar_gaps,
                        'motivo_ignoracao' => $registro->motivo_ignoracao,
                    ],
                ]
            );
        }

        session()->flash(
            'mensagem_sucesso',
            $ignorar
                ? 'Inutilização marcada para não aparecer mais no relatório de gaps.'
                : 'Inutilização reativada para aparecer novamente no relatório de gaps.'
        );

        return redirect()->back();
    }

    public function ignorarGapManual(Request $request)
    {
        $dados = $request->validate([
            'filial_id'      => ['nullable'],
            'serie'          => ['required', 'integer', 'min:1'],
            'numero_inicial' => ['required', 'integer', 'min:1'],
            'numero_final'   => ['required', 'integer', 'gte:numero_inicial'],
            'motivo'         => ['nullable', 'string', 'max:255'],
        ]);

        // Normaliza filial: matriz => null
        $filialId = $dados['filial_id'] ?? null;
        if (
            $filialId === 'null' ||
            $filialId === '' ||
            (is_numeric($filialId) && (int)$filialId <= 0)
        ) {
            $filialId = null;
        }

        $motivo = trim($dados['motivo'] ?? '');
        if ($motivo === '') {
            $motivo = 'Gap ignorado manualmente no relatório de saltos de numeração.';
        }

        $ambienteTexto = $this->resolveAmbienteTexto($filialId);

        // Modelo fixo 55 aqui; se você tiver 65 também nos gaps, pode adaptar depois
        $registro = NFeInutilizacao::create([
            'empresa_id'       => $this->empresa_id,
            'filial_id'        => $filialId,
            'usuario_id'       => $this->usuario_id ?? null,
            'modelo'           => '55',
            'serie'            => (int)$dados['serie'],
            'numero_inicial'   => (int)$dados['numero_inicial'],
            'numero_final'     => (int)$dados['numero_final'],
            'ano'              => date('y'),
            'justificativa'    => $motivo,
            'ambiente'         => $ambienteTexto,
            'origem'           => 'gap_ignorado_manual',
            'status'           => 'rejeitada', // não importa para o filtro, ele olha ignorar_gaps
            'protocolo'        => null,
            'mensagem'         => 'Gap ignorado manualmente (sem envio à SEFAZ).',
            'nfserver_id'      => null,
            'xml_solicitacao'  => null,
            'xml_retorno'      => null,
            'ignorar_gaps'     => true,
            'motivo_ignoracao' => $motivo,
        ]);

        if (property_exists($this, 'logService') && $this->logService) {
            $this->logService->registrar(
                'nfe_gap_ignorado_manual',
                NFeInutilizacao::class,
                [
                    'registro_id'  => $registro->id ?? null,
                    'dados_antes'  => null,
                    'dados_depois' => [
                        'nfe_inutilizacao_id' => $registro->id ?? null,
                        'payload'             => $dados,
                    ],
                ]
            );
        }

        session()->flash(
            'mensagem_sucesso',
            'Gap marcado para ser ignorado no relatório de saltos de numeração.'
        );

        return redirect()->back();
    }

}
