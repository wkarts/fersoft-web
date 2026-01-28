<?php

namespace App\Http\Controllers;

use App\Models\Pesagem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AcompanhamentoClientesExport;
use Barryvdh\DomPDF\Facade\Pdf;

class AcompanhamentoClientesController extends BaseController
{
    /**
     * Propriedades exigidas pelo BaseController
     * (mesmo sendo um controller só de relatório).
     */
    protected $model        = Pesagem::class;
    protected $resource     = 'relatorios.acompanhamento-clientes';
    protected $formTitle    = 'Acompanhamento de Clientes';
    protected $listView     = 'relatorios.acompanhamento_clientes.pdf';      // se quiser, depois pode trocar para uma view "index"
    protected $registerView = 'relatorios.acompanhamento_clientes.pdf';

    /**
     * Usada pelo BaseController para redirecionamentos padrão.
     * Aqui apontamos para a rota principal do relatório.
     *
     * Se no seu sistema você preferir usar nome de rota,
     * pode trocar para algo como:
     *   protected $redirectPage = 'relatorios.acompanhamento-clientes';
     *
     * Mas, como a exception fala de "propriedade não definida",
     * o importante é ela existir e ser uma string válida.
     */
    protected $redirectPage = '/relatorios/acompanhamento-clientes';

    public function __construct()
    {
        parent::__construct();
        // Se o BaseController já controla empresa_id/usuario_id, não
        // precisamos fazer nada adicional aqui para este relatório.
    }

    /**
     * Implementações obrigatórias do BaseController.
     * Como esse controller é apenas de relatório, não usamos regras aqui.
     */
    public function rules($id = null): array
    {
        return [];
    }

    public function messages(): array
    {
        return [];
    }

    /**
     * Tela de filtros + preview (se quiser usar DataTables depois).
     */
    public function index(Request $request)
    {
        // filtros básicos
        $filtros = [
            'data_inicio' => $request->input('data_inicio'),
            'data_fim'    => $request->input('data_fim'),
            'cliente_id'  => $request->input('cliente_id'),
            'filial_id'   => $request->input('filial_id'), // se usar filial
        ];

        // Preview na tela, usando a mesma query do export
        $pesagens = collect();
        $periodoTitulo = null;

        if ($filtros['data_inicio'] && $filtros['data_fim']) {
            $pesagens = $this->buildQuery($filtros)->get();
            // evita o erro de variável indefinida na view
            $periodoTitulo = $this->montarTituloPeriodo(
                $filtros['data_inicio'],
                $filtros['data_fim']
            );
        }

        // agora a view (inclusive pdf.blade.php) sempre recebe $periodoTitulo
        return view($this->listView, compact('filtros', 'pesagens', 'periodoTitulo'));
    }

    /**
     * Exportação para Excel com layout melhorado.
     */
    public function exportExcel(Request $request)
    {
        $filtros = [
            'data_inicio' => $request->input('data_inicio'),
            'data_fim'    => $request->input('data_fim'),
            'cliente_id'  => $request->input('cliente_id'),
            'filial_id'   => $request->input('filial_id'),
        ];

        if (!$filtros['data_inicio'] || !$filtros['data_fim']) {
            return $this->errorResponse('Informe o período para exportar o relatório.');
        }

        $fileName = 'acompanhamento_clientes_' .
            Carbon::parse($filtros['data_inicio'])->format('Ymd') . '_' .
            Carbon::parse($filtros['data_fim'])->format('Ymd') . '.xlsx';

        return Excel::download(
            new AcompanhamentoClientesExport($filtros, $this->buildQuery($filtros)),
            $fileName
        );
    }

    /**
     * Exportação para PDF.
     */
    public function exportPdf(Request $request)
    {
        $filtros = [
            'data_inicio' => $request->input('data_inicio'),
            'data_fim'    => $request->input('data_fim'),
            'cliente_id'  => $request->input('cliente_id'),
            'filial_id'   => $request->input('filial_id'),
        ];

        if (!$filtros['data_inicio'] || !$filtros['data_fim']) {
            return $this->errorResponse('Informe o período para exportar o relatório.');
        }

        $pesagens = $this->buildQuery($filtros)->get();

        $periodoTitulo = $this->montarTituloPeriodo($filtros['data_inicio'], $filtros['data_fim']);

        $pdf = Pdf::loadView('relatorios.acompanhamento_clientes.pdf', [
            'pesagens'      => $pesagens,
            'filtros'       => $filtros,
            'periodoTitulo' => $periodoTitulo,
        ])->setPaper('a4', 'landscape'); // paisagem para ficar parecido com a planilha

        $fileName = 'acompanhamento_clientes_' .
            Carbon::parse($filtros['data_inicio'])->format('Ymd') . '_' .
            Carbon::parse($filtros['data_fim'])->format('Ymd') . '.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Query centralizada usada por index, Excel e PDF.
     */
    protected function buildQuery(array $filtros)
    {
        $query = Pesagem::query()
            ->with(['cliente', 'material', 'veiculo']) // ajuste conforme seus relacionamentos
            ->whereBetween('data', [
                Carbon::parse($filtros['data_inicio'])->startOfDay(),
                Carbon::parse($filtros['data_fim'])->endOfDay(),
            ]);

        if (!empty($filtros['cliente_id'])) {
            $query->where('cliente_id', $filtros['cliente_id']);
        }

        // se usar multi-empresa/filial
        if (!empty($filtros['filial_id'])) {
            $query->where('filial_id', $filtros['filial_id']);
        }

        // Se você usar empresa_id acoplado ao usuário/logado:
        if (auth()->check() && isset(auth()->user()->empresa_id)) {
            $query->where('empresa_id', auth()->user()->empresa_id);
        }

        // Ordenação padrão por data
        $query->orderBy('data')->orderBy('id');

        return $query;
    }

    /**
     * Monta o título tipo: "outubro 2025".
     */
    protected function montarTituloPeriodo(string $dataInicio, string $dataFim): string
    {
        $inicio = Carbon::parse($dataInicio);
        $fim    = Carbon::parse($dataFim);

        if ($inicio->isSameMonth($fim)) {
            // Ex: "outubro 2025"
            return mb_strtolower($inicio->translatedFormat('F Y'));
        }

        // Período cruzando meses/anos
        return $inicio->format('d/m/Y') . ' a ' . $fim->format('d/m/Y');
    }

    /**
     * Atalho simples para respostas de erro padronizadas.
     * Mantida genérica, sem depender do BaseController.
     */
    public function errorResponse(string $message)
    {
        if (request()->wantsJson()) {
            return response()->json(['message' => $message], 422);
        }

        return redirect()->back()->withErrors($message);
    }
}
