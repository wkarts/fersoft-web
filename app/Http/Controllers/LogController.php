<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Log;
use App\Models\Usuario;
use App\Models\Filial;
use Illuminate\Http\Request;

class LogController extends BaseController
{
    protected $model = Log::class;
    protected $listView = 'logs.list';
    protected $formTitle = 'Logs de Atividades';
    protected $redirectPage = '/logs';
    protected $filial_id = null;

    /**
     * Define as regras de validação para os logs.
     */
    protected function rules(): array
    {
        return [
            'acao' => 'required|string|max:255',
            'modelo' => 'nullable|string|max:255',
            'dados' => 'nullable|json',
            'dados_anteriores' => 'nullable|json',
            'dados_depois' => 'nullable|json',
        ];
    }

    /**
     * Define as mensagens de validação personalizadas.
     */
    protected function messages(): array
    {
        return [
            'acao.required' => 'A ação do log é obrigatória.',
            'acao.string' => 'A ação deve ser um texto válido.',
            'acao.max' => 'A ação não pode ter mais que 255 caracteres.',
            'modelo.string' => 'O modelo deve ser um texto válido.',
            'modelo.max' => 'O modelo não pode ter mais que 255 caracteres.',
            'dados.json' => 'Os dados devem estar em formato JSON válido.',
            'dados_anteriores.json' => 'Os dados anteriores devem estar em formato JSON válido.',
            'dados_depois.json' => 'Os dados depois devem estar em formato JSON válido.',
        ];
    }

    /**
     * Define os filtros disponíveis na listagem.
     */
    protected function defineFilters(Request $request): array
    {
        return [
            [
                'name' => 'usuario_id',
                'label' => 'Usuário',
                'type' => 'select',
                'placeholder' => 'Todos os Usuários',
                'options' => Usuario::where('empresa_id', $this->empresa_id)
                    ->select('id', 'nome')
                    ->get()
                    ->map(fn($usuario) => [
                        'value' => $usuario->id,
                        'label' => $usuario->nome,
                    ])->toArray(),
            ],
            [
                'name' => 'filial_id',
                'label' => 'Filial',
                'type' => 'select',
                'placeholder' => 'Todas as Filiais',
                'options' => Filial::where('empresa_id', $this->empresa_id)
                    ->select('id', 'nome_fantasia')
                    ->get()
                    ->map(fn($filial) => [
                        'value' => $filial->id,
                        'label' => $filial->nome_fantasia,
                    ])->toArray(),
            ],
            [
                'name' => 'acao',
                'label' => 'Ação',
                'type' => 'select',
                'placeholder' => 'Todas as Ações',
                'options' => Log::where('empresa_id', $this->empresa_id)
                    ->select('acao')
                    ->distinct()
                    ->orderBy('acao')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'value' => $item->acao,
                            'label' => ucfirst(str_replace('_', ' ', $item->acao)),
                        ];
                    })->toArray(),
            ],
            [
                'name' => 'modelo',
                'label' => 'Modelo',
                'type' => 'text',
                'placeholder' => 'Digite o nome do modelo',
            ],
        ];
    }

    /**
     * Lista os logs filtrados por empresa e usuário logado.
     */
    public function list(Request $request)
    {
        $query = Log::where('empresa_id', $this->empresa_id);

        // Filtro por filial (sem refatorar)
        if ($request->filled('filial_id')) {
            if ($request->filial_id === 'null') {
                $query->whereNull('filial_id');
            } else {
                $query->where('filial_id', $request->filial_id);
            }
        }

        if ($request->filled('usuario_id')) {
            $query->where('usuario_id', $request->usuario_id);
        }

        // Filtro por datas (se preenchido)
        if ($request->filled('data_inicial')) {
            $query->whereDate('created_at', '>=', $request->data_inicial);
        }
        if ($request->filled('data_final')) {
            $query->whereDate('created_at', '<=', $request->data_final);
        }

        if ($request->filled('modelo')) {
            $modeloBusca = strtolower(trim($request->modelo));
            $query->whereRaw("INSTR(LOWER(modelo), ?) > 0", [$modeloBusca]);
            // Remove o valor de 'modelo' da Request para evitar que applyFilters() adicione um filtro de igualdade
            $request->request->remove('modelo');
        }

        // Filtros pré-existentes (sem refatorar)
        //$query = $this->applyFilters($request, $query);

        if ($request->filled('acao')) {
            $query->where('acao', $request->acao);
        }

        // Estratégia em duas etapas para reduzir uso de memória no ORDER BY
        // 1) pagina apenas IDs (payload pequeno)
        // 2) carrega registros completos apenas da página atual
        $idsPaginator = (clone $query)
            ->select('id')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $ids = $idsPaginator->pluck('id')->all();
        $logsById = empty($ids)
            ? collect()
            : Log::whereIn('id', $ids)->get()->keyBy('id');

        $orderedLogs = collect($ids)
            ->map(function ($id) use ($logsById) {
                return $logsById->get($id);
            })
            ->filter()
            ->values();

        $logs = $idsPaginator->setCollection($orderedLogs);
        $usuarios = Usuario::where('empresa_id', $this->empresa_id)->get();
        $filiais = Filial::where('empresa_id', $this->empresa_id)->get();

        $acoes = Log::where('empresa_id', $this->empresa_id)
            ->select('acao')
            ->distinct()
            ->orderBy('acao')
            ->pluck('acao')
            ->toArray();

        // 🔹 Retorna a view garantindo que todas as variáveis estejam disponíveis
        return view($this->listView, [
            'title' => $this->formTitle,
            'logs' => $logs,
            'usuarios' => $usuarios,
            'filiais' => $filiais,
            'acoes' => $acoes,
        ]);
    }

}
