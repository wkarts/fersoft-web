<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\ProdutoPrateleira;

class ProdutoPrateleiraController extends BaseController
{
    protected $model;
    protected $formTitle     = 'Prateleiras';
    protected $listTitle     = 'Lista de {form_title}';
    protected $registerTitle = 'Cadastro de {form_title}';
    protected $editTitle     = 'Editando prateleira #{registro_id}';
    protected $redirectPage  = '/produto_prateleiras/list';
    protected $listView      = 'produto_prateleiras.list';

    public function __construct()
    {
        parent::__construct();
        $this->model = ProdutoPrateleira::class;
    }

    /**
     * Lista as prateleiras (com filtro por identificacao, descricao, filial_id e mostrar_excluidas).
     */
    public function list(Request $request)
    {
        // 1) Query base: sempre filtrar pela empresa
        if ($request->get('mostrar_excluidas')) {
            $query = $this->model::where('empresa_id', $this->empresa_id)->withTrashed();
        } else {
            $query = $this->model::where('empresa_id', $this->empresa_id);
        }

        // 2) Filtro por nome (identificação)
        if ($request->filled('identificacao')) {
            $valorId = trim($request->get('identificacao'));
            $query->where('identificacao', 'like', '%' . $valorId . '%');
        }

        // 3) Filtro por descrição
        if ($request->filled('descricao')) {
            $valorDesc = trim($request->get('descricao'));
            $query->where('descricao', 'like', '%' . $valorDesc . '%');
        }

        // 4) Filtro por filial:
        // Se a empresa NÃO trabalha com filial, $this->filial_id será null,
        // então não entra aqui. Se a empresa trabalhá com filiais,
        // primeiro verifico o filtro enviado no GET; senão houver, uso a filial do usuário.
        if (empresaComFilial()) {
            // tenta ler do request; se não vier, usa a filial do usuário
            $filialSelecionada = $request->filled('filial_id')
                ? intval($request->get('filial_id'))
                : $this->filial_id;

            // Se vier um número válido (>0), filtra por ele
            if (!is_null($filialSelecionada) && $filialSelecionada > 0) {
                $query->where('filial_id', $filialSelecionada);
            } else {
                // se o usuário está “na Matriz” (filial padrão null ou -1),
                // exibir apenas registros de Matriz
                $query->whereNull('filial_id');
            }
        }

        // 5) Ordenar
        $query = $query->orderBy('identificacao');

        // 6) Paginar
        $porPagina = intval($request->get('por_pagina', 10));
        if ($porPagina <= 0) {
            $porPagina = 10;
        }
        $records = $query->paginate($porPagina);

        // 7) Fazer com que os filtros apareçam nos links de página
        $records->appends($request->only([
            'identificacao',
            'descricao',
            'filial_id',
            'mostrar_excluidas',
            'por_pagina'
        ]));

        // 8) Título
        $title = $this->formatString($this->listTitle, [
            'form_title' => $this->formTitle,
        ]);

        return view($this->listView, [
            'records' => $records,
            'title'   => $title,
        ]);
    }

    public function list_(Request $request)
    {
        // 1) Monta a query base (filtrando sempre por empresa_id).
        if ($request->get('mostrar_excluidas')) {
            // Se checkbox “mostrar_excluidas” estiver marcado, traz com trashed()
            $query = $this->model::where('empresa_id', $this->empresa_id)
                ->withTrashed();
        } else {
            // Senão, traz somente os registros não deletados
            $query = $this->model::where('empresa_id', $this->empresa_id);
        }

        // 2) Aplicar filtro por 'identificacao' (LIKE)
        if ($request->filled('identificacao')) {
            $valorId = trim($request->get('identificacao'));
            $query->where('identificacao', 'like', '%' . $valorId . '%');
        }

        // 3) Aplicar filtro por 'descricao' (LIKE)
        if ($request->filled('descricao')) {
            $valorDesc = trim($request->get('descricao'));
            $query->where('descricao', 'like', '%' . $valorDesc . '%');
        }

        // 4) Aplicar filtro por 'filial_id' (se for um valor válido)
        if ($request->filled('filial_id')) {
            $filial = intval($request->get('filial_id'));
            if ($filial > 0) {
                $query->where('filial_id', $filial);
            }
        }

        // 5) Ordenar por 'identificacao' e paginar
        $query = $query->orderBy('identificacao');

        $porPagina = intval($request->get('por_pagina', 10));
        if ($porPagina <= 0) {
            $porPagina = 10;
        }

        // 6) Executar paginação
        $records = $query->paginate($porPagina);

        // 7) Garantir que os parâmetros de filtro sejam adicionados aos links de página
        $records->appends($request->only([
            'identificacao',
            'descricao',
            'filial_id',
            'mostrar_excluidas',
            'por_pagina'
        ]));

        // 8) Montar o título dinamicamente
        $title = $this->formatString($this->listTitle, [
            'form_title' => $this->formTitle,
        ]);

        // 9) Retornar a view, passando os registros e o título
        return view($this->listView, [
            'records' => $records,
            'title'   => $title,
        ]);
    }

    /**
     * Define quais filtros estarão disponíveis no formulário.
     * (Não estritamente necessário para esta lista, mas deixado caso o BaseController os utilize.)
     */
    protected function defineFilters(Request $request): array
    {
        return [
            [
                'name'    => 'identificacao',
                'type'    => 'text',
                'default' => '',
            ],
            [
                'name'    => 'descricao',
                'type'    => 'text',
                'default' => '',
            ],
            [
                'name'    => 'filial_id',
                'type'    => 'select',
                'default' => '',
            ],
            [
                'name'    => 'mostrar_excluidas',
                'type'    => 'checkbox',
                'default' => 0,
            ],
            [
                'name'    => 'por_pagina',
                'type'    => 'select',
                'default' => 10,
            ],
        ];
    }

    /**
     * Regras de validação para create / update
     */
    protected function rules(): array
    {
        // Se for update, pega o id atual; senão, fica null
        $idAtual = request()->get('id') ?? null;

        // Descobre qual filial está sendo usada neste cadastro/edição.
        // Pode ser nulo (ou -1) para a matriz, ou um integer para filial específica.
        // Supondo que $this->filial_id já venha do seu BaseController
        // (ouvidos cada usuário tem um filial padrão).
        $filialId = $this->filial_id; // se for “matriz”, deve ser null (ou -1).
        if ($filialId === '-1') {
            // normalize "-1" para null
            $filialId = null;
        }

        return [
            'identificacao' => [
                'required',
                'string',
                'max:50',
                // Regra de unicidade ESCOPADA:
                //  – dentro da mesma empresa_id
                //  – e no mesmo filial_id (NULL se for matriz)
                Rule::unique('produto_prateleiras', 'identificacao')
                    ->ignore($idAtual)
                    ->where(function($query) use ($filialId) {
                        // Sempre filtra pela própria empresa
                        $query->where('empresa_id', $this->empresa_id);

                        // Se for “Matriz” (filialId null), exige WHERE filial_id IS NULL.
                        if (is_null($filialId)) {
                            $query->whereNull('filial_id');
                        } else {
                            // Se for uma filial concreta, exige WHERE filial_id = aquele id
                            $query->where('filial_id', $filialId);
                        }
                    }),
            ],
            'descricao'   => 'nullable|string|max:100',
            'posicao'     => 'nullable|string|max:10',
            'localizacao' => 'nullable|string|max:100',
            'observacao'  => 'nullable|string',
        ];
    }

    protected function rules____(): array
    {
        // Se for edição, $idAtual receberá o id do registro que está sendo atualizado;
        // no create, $idAtual será nulo.
        $idAtual = request()->get('id') ?? null;

        return [
            'identificacao' => [
                'required',
                'string',
                'max:50',
                Rule::unique('produto_prateleiras', 'identificacao')
                    ->ignore($idAtual)
                    ->where(function ($query) {
                        // Somente invalida se existir a mesma identificacao na mesma empresa e filial
                        $query->where('empresa_id', $this->empresa_id)
                            ->where('filial_id', $this->filial_id);
                    }),
            ],
            'descricao'   => 'nullable|string|max:100',
            'posicao'     => 'nullable|string|max:10',
            'localizacao' => 'nullable|string|max:100',
            'observacao'  => 'nullable|string',
        ];
    }

    protected function rules__(): array
    {
        $idAtual = request()->get('id') ?? null;

        return [
            'identificacao' => [
                'required',
                'string',
                'max:50',
                // “ignore” faz com que, no update, não considere o próprio registro
                Rule::unique('produto_prateleiras', 'identificacao')
                    ->ignore($idAtual)
                    ->where(function($query) {
                        // garante que só seja único dentro de (empresa_id, filial_id)
                        $query->where('empresa_id', $this->empresa_id)
                            ->where('filial_id', $this->filial_id);
                    }),
            ],
            'descricao'     => 'nullable|string|max:100',
            'posicao'       => 'nullable|string|max:10',
            'localizacao'   => 'nullable|string|max:100',
            'observacao'    => 'nullable|string',
        ];
    }
    protected function rules_(): array
    {
        $idExcluir = request()->id ?? null;

        return [
            'identificacao' => 'required|string|max:50|unique:produto_prateleiras,identificacao,' . $idExcluir,
            'descricao'     => 'nullable|string|max:100',
            'posicao'       => 'nullable|string|max:10',
            'localizacao'   => 'nullable|string|max:100',
            'observacao'    => 'nullable|string',
        ];
    }

    protected function messages(): array
    {
        return [
            'identificacao.required' => 'A identificação é obrigatória.',
            'identificacao.unique'   => 'Já existe uma prateleira com esta identificação.',
            'identificacao.max'      => 'A identificação não pode ter mais de 50 caracteres.',
            'descricao.max'          => 'A descrição não pode ter mais de 100 caracteres.',
            'posicao.max'            => 'A posição não pode ter mais de 10 caracteres.',
            'localizacao.max'        => 'A localização não pode ter mais de 100 caracteres.',
        ];
    }

    /**
     * Retorna JSON de uma prateleira para ser usado na edição via AJAX.
     */
    public function getPrateleiraJson($id)
    {
        $registro = $this->model::withTrashed()
            ->where('empresa_id', $this->empresa_id)
            ->findOrFail($id);

        return response()->json($registro);
    }

    /**
     * Gera a view de etiquetas para os IDs passados como query string: ?ids[]=x&ids[]=y
     */
    public function etiquetas(Request $request)
    {
        $ids = $request->query('ids', []);
        $prateleiras = $this->model::where('empresa_id', $this->empresa_id)
            ->whereIn('id', $ids)
            ->get();

        return view('produto_prateleiras.etiquetas', compact('prateleiras'));
    }

    public function searchPrateleiras(Request $request)
    {
        $term = $request->get('term', '');

        $query = ProdutoPrateleira::query()
            ->where('empresa_id', $this->empresa_id)
            // só filtra por filial se $this->filial_id não for nulo (ou zero, ou “-1” conforme seu caso)
            ->when($this->filial_id, function($q) {
                $q->where('filial_id', $this->filial_id);
            })
            ->where('identificacao', 'LIKE', "%{$term}%")
            ->orderBy('identificacao')
            ->take(20)
            ->get(['id', 'identificacao']);

        $results = $query->map(function($item) {
            return [
                'id'   => $item->id,
                'text' => $item->identificacao,
            ];
        });

        return response()->json(['results' => $results]);
    }

    public function searchPrateleiras_(Request $request)
    {
        $term = $request->get('term', '');
        // Ajuste “ProdutoPrateleira::” para o nome real do seu Model
        $query = ProdutoPrateleira::query()
            ->where('empresa_id', $this->empresa_id)
            ->where('filial_id', $this->filial_id)
            ->where('identificacao', 'LIKE', "%{$term}%")
            ->orderBy('identificacao')
            ->take(20)
            ->get(['id', 'identificacao']);

        // Converte para o formato que o Select2 espera: id/text
        $results = $query->map(function($item) {
            return [
                'id'   => $item->id,
                'text' => $item->identificacao,
            ];
        });

        return response()->json(['results' => $results]);
    }

    // Métodos save(), update(), delete(), restore() permanecem inalterados…
}
