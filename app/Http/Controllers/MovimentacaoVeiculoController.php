<?php

namespace App\Http\Controllers;

use App\Models\MovimentacaoVeiculo;
use App\Models\Veiculo;
use App\Models\Funcionario;
use App\Models\TipoMovimentacao;
use Illuminate\Http\Request;

class MovimentacaoVeiculoController extends BaseController
{
    protected $model = MovimentacaoVeiculo::class;
    protected $resource = 'movimentacoes_veiculos';
    protected $table = 'movimentacoes_veiculos';
    protected $formTitle = 'Movimentações de Veículos';
    protected $listView = 'movimentacoes_veiculos.list';
    protected $registerView = 'movimentacoes_veiculos.register';
    protected $redirectPage = '/movimentacaoVeiculo';

    protected function getTenantRecords()
    {
        $query = $this->applyFilialFilter(parent::getTenantRecords());

        return $query->with(['veiculo', 'motorista', 'tipoMovimentacao'])
            ->orderByDesc('data_movimentacao');
    }

    protected function defineFilters(Request $request): array
    {
        $veiculos = $this->applyFilialFilter(
            Veiculo::where('empresa_id', $this->empresa_id)
        )
            ->orderBy('placa')
            ->get()
            ->map(fn($veiculo) => [
                'value' => $veiculo->id,
                'label' => $veiculo->placa . ' - ' . $veiculo->marca . ' ' . $veiculo->modelo,
            ])->toArray();

        $tipos = $this->applyFilialFilter(
            TipoMovimentacao::where('empresa_id', $this->empresa_id)
        )
            ->orderBy('nome')
            ->get()
            ->map(fn($tipo) => [
                'value' => $tipo->id,
                'label' => $tipo->nome,
            ])->toArray();

        return [
            [
                'name' => 'veiculo_id',
                'label' => 'Veículo',
                'type' => 'select',
                'placeholder' => 'Todos os Veículos',
                'options' => $veiculos,
            ],
            [
                'name' => 'tipo_movimentacao_id',
                'label' => 'Tipo de Movimentação',
                'type' => 'select',
                'placeholder' => 'Todos os Tipos',
                'options' => $tipos,
            ],
            [
                'name' => 'data_inicial',
                'label' => 'Data Inicial',
                'type' => 'date',
            ],
            [
                'name' => 'data_final',
                'label' => 'Data Final',
                'type' => 'date',
            ],
        ];
    }

    protected function headers(): array
    {
        return ['Veículo', 'Motorista', 'Tipo de Movimentação', 'Data', 'KM Saída', 'KM Chegada', 'Custo', 'Status'];
    }

    protected function fields(): array
    {
        return ['veiculo.nome', 'motorista.nome', 'tipoMovimentacao.nome', 'data_movimentacao', 'km_saida', 'km_chegada', 'custo', 'status'];
    }

    protected function rules(): array
    {
        return [
            'veiculo_id' => 'required|exists:veiculos,id',
            'motorista_id' => 'nullable|exists:funcionarios,id',
            'tipo_movimentacao_id' => 'required|exists:tipos_movimentacoes,id',
            'data_movimentacao' => 'required|date',
            'km_saida' => 'nullable|integer|min:0',
            'km_chegada' => 'nullable|integer|min:0|gte:km_saida',
            'custo' => 'nullable|numeric|min:0',
            'observacoes' => 'nullable|string',
            //'status' => 'required|in:em andamento,concluída',
        ];
    }

    protected function messages(): array
    {
        return [
            'veiculo_id.required' => 'O campo Veículo é obrigatório.',
            'veiculo_id.exists' => 'O Veículo selecionado não existe.',
            'tipo_movimentacao_id.required' => 'O campo Tipo de Movimentação é obrigatório.',
            'tipo_movimentacao_id.exists' => 'O Tipo de Movimentação selecionado não existe.',
            'data_movimentacao.required' => 'O campo Data de Movimentação é obrigatório.',
            'data_movimentacao.date' => 'O campo Data de Movimentação deve ser uma data válida.',
            'km_saida.integer' => 'O campo KM de Saída deve ser um número inteiro.',
            'km_chegada.gte' => 'O campo KM de Chegada deve ser maior ou igual ao KM de Saída.',
        ];
    }

    public function register($id = null)
    {
        $query = $this->applyFilialFilter(
            $this->model::where('empresa_id', $this->empresa_id)
        );

        $data = $id ? $query->findOrFail($id) : null;

        $title = $this->formatString($this->registerTitle, [
            'form_title' => $this->formTitle,
        ]);

        return view($this->registerView, [
            'data' => $data,
            'title' => $title,
            'actionSave' => "{$this->redirectPage}/save",
            'actionUpdate' => "{$this->redirectPage}/update",
            'actionCancel' => $this->redirectPage,
            'veiculos' => $this->applyFilialFilter(
                Veiculo::where('empresa_id', $this->empresa_id)
            )
                ->orderBy('placa')
                ->get(),
            'motoristas' => Funcionario::where('empresa_id', $this->empresa_id)
                ->orderBy('nome')
                ->get(),
            'tiposMovimentacao' => $this->applyFilialFilter(
                TipoMovimentacao::where('empresa_id', $this->empresa_id)
            )
                ->orderBy('nome')
                ->get(),
        ]);
    }

    protected function validateHeadersAndFields()
    {
        if (count($this->headers()) !== count($this->fields())) {
            throw new \Exception('Os headers e fields não estão alinhados no controlador: ' . static::class);
        }
    }

    public function list(Request $request)
    {
        $this->validateHeadersAndFields();

        $query = $this->getTenantRecords();

        if (method_exists($this, 'defineFilters')) {
            $query = $this->applyFilters($request, $query);
        }

        $records = $query->get();

        return view($this->listView, [
            'records' => $records,
            'title' => $this->formatString($this->listTitle, ['form_title' => $this->formTitle]),
            'headers' => $this->headers(),
            'fields' => $this->fields(),
            'newItemUrl' => "{$this->redirectPage}/new",
            'newItemText' => 'Novo Registro',
            'editUrl' => "{$this->redirectPage}/edit",
            'deleteUrl' => "{$this->redirectPage}/delete",
            'filterUrl' => "{$this->redirectPage}/list",
            'filters' => $this->getFilters($request),
        ]);
    }

}





/*
namespace App\Http\Controllers;

use App\Models\MovimentacaoVeiculo;
use App\Models\TipoMovimentacao;
use App\Models\Veiculo;
use App\Models\Funcionario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MovimentacaoVeiculoController extends BaseController
{
    protected $model = MovimentacaoVeiculo::class;
    protected $resource = 'movimentacoes_veiculos';
    protected $table = 'movimentacoes_veiculos';
    protected $formTitle = 'Movimentações de Veículos';
    protected $listView = 'movimentacoes_veiculos.list';
    protected $registerView = 'movimentacoes_veiculos.register';
    protected $redirectPage = '/movimentacaoVeiculo';
    protected $redirectDeny = '/403';
    protected $listTitle = 'Lista de {form_title}';
    protected $registerTitle = 'Cadastro de {form_title}';
    protected $editTitle = 'Editando registro #{registro_id} - {form_title}';

    protected $successCreate = 'Novo registro inserido com sucesso em: {form_title}';
    protected $errorCreate = 'Erro ao inserir um novo registro em: {form_title}';
    protected $successUpdateMessage = 'O registro #{registro_id} foi atualizado com sucesso em: {form_title}';
    protected $errorUpdateMessage = 'Erro ao atualizar o registro #{registro_id} em: {form_title}';
    protected $successDeleteMessage = 'O registro #{registro_id} foi removido com sucesso em: {form_title}';
    protected $errorDeleteMessage = 'Erro ao remover o registro #{registro_id} em: {form_title}';

    protected function getTenantRecords()
    {
        return $this->model::where('empresa_id', $this->empresa_id);
    }

    protected function formatTitle(string $template, array $placeholders): string
    {
        foreach ($placeholders as $key => $value) {
            $template = str_replace("{{$key}}", $value, $template);
        }
        return $template;
    }

    protected function rules(): array
    {
        return [
            'veiculo_id' => 'required|exists:veiculos,id',
            'motorista_id' => 'nullable|exists:funcionarios,id',
            'tipo_movimentacao_id' => 'required|exists:tipos_movimentacoes,id',
            'data_movimentacao' => 'required|date',
            'km_saida' => 'nullable|integer|min:0',
            'km_chegada' => 'nullable|integer|min:0|gte:km_saida',
            'custo' => 'nullable|numeric|min:0',
            'observacoes' => 'nullable|string',
            'status' => 'required|in:em andamento,concluída',
        ];
    }


    protected function messages(): array
    {
        return [
            'veiculo_id.required' => 'O campo Veículo é obrigatório.',
            'veiculo_id.exists' => 'O Veículo selecionado não existe.',
            'motorista_id.exists' => 'O Motorista selecionado não existe.',
            'tipo_movimentacao_id.required' => 'O campo Tipo de Movimentação é obrigatório.',
            'tipo_movimentacao_id.exists' => 'O Tipo de Movimentação selecionado não existe.',
            'data_movimentacao.required' => 'O campo Data de Movimentação é obrigatório.',
            'data_movimentacao.date' => 'O campo Data de Movimentação deve conter uma data válida.',
            'km_saida.integer' => 'O campo KM de Saída deve ser um número inteiro.',
            'km_saida.min' => 'O campo KM de Saída deve ser maior ou igual a 0.',
            'km_chegada.integer' => 'O campo KM de Chegada deve ser um número inteiro.',
            'km_chegada.min' => 'O campo KM de Chegada deve ser maior ou igual a 0.',
            'km_chegada.gte' => 'O campo KM de Chegada deve ser maior ou igual ao KM de Saída.',
            'custo.numeric' => 'O campo Custo deve ser um número.',
            'custo.min' => 'O campo Custo deve ser maior ou igual a 0.',
            'observacoes.string' => 'O campo Observações deve ser um texto.',
            'status.required' => 'O campo Status é obrigatório.',
            'status.in' => 'O campo Status deve ser "em andamento" ou "concluída".',
        ];
    }

    protected function headers(): array
    {
        return ['Veículo', 'Motorista', 'Tipo de Movimentação', 'Data', 'Status'];
    }

    protected function fields(): array
    {
        return ['veiculo.nome', 'motorista.nome', 'tipoMovimentacao.nome', 'data_movimentacao', 'status'];
    }

    public function list()
    {
        $request = request(); // Obter a instância do Request manualmente

        $query = $this->getTenantRecords();

        if ($request->filled('veiculo_id')) {
            $query->where('veiculo_id', $request->veiculo_id);
        }

        if ($request->filled('tipo_movimentacao_id')) {
            $query->where('tipo_movimentacao_id', $request->tipo_movimentacao_id);
        }

        if ($request->filled('data_inicial') && $request->filled('data_final')) {
            $query->whereBetween('data_movimentacao', [$request->data_inicial, $request->data_final]);
        }

        $records = $query->get();

        $title = $this->formatString($this->listTitle, [
            'form_title' => $this->formTitle,
        ]);

        $filterUrl = "{$this->redirectPage}/filtro";

        $filters = [
            [
                'name' => 'veiculo_id',
                'label' => 'Veículo',
                'type' => 'select',
                'placeholder' => 'Todos os Veículos',
                'options' => Veiculo::select('id', \DB::raw("CONCAT(marca, ' ', modelo) AS nome"))->get()->map(function ($veiculo) {
                    return [
                        'value' => $veiculo->id,
                        'label' => $veiculo->nome,
                    ];
                })->toArray(),
                'value' => $request->veiculo_id ?? '',
            ],
            [
                'name' => 'tipo_movimentacao_id',
                'label' => 'Tipo de Movimentação',
                'type' => 'select',
                'placeholder' => 'Todos os Tipos',
                'options' => TipoMovimentacao::select('id', 'nome')->get()->map(function ($tipo) {
                    return [
                        'value' => $tipo->id,
                        'label' => $tipo->nome,
                    ];
                })->toArray(),
                'value' => $request->tipo_movimentacao_id ?? '',
            ],
            [
                'name' => 'data_inicial',
                'label' => 'Data Inicial',
                'type' => 'date',
                'value' => $request->data_inicial ?? '',
            ],
            [
                'name' => 'data_final',
                'label' => 'Data Final',
                'type' => 'date',
                'value' => $request->data_final ?? '',
            ],
        ];

        return view($this->listView, [
            'records' => $records,
            'title' => $title,
            'headers' => $this->headers(),
            'fields' => $this->fields(),
            'newItemUrl' => "{$this->redirectPage}/new",
            'newItemText' => 'Nova Movimentação',
            'editUrl' => "{$this->redirectPage}/edit",
            'deleteUrl' => "{$this->redirectPage}/delete",
            'filterUrl' => $filterUrl,
            'filters' => $filters, // Passa os filtros para a view
        ]);
    }

    public function filtro(Request $request)
    {
        $query = $this->getTenantRecords();

        if ($request->filled('veiculo_id')) {
            $query->where('veiculo_id', $request->veiculo_id);
        }

        if ($request->filled('tipo_movimentacao_id')) {
            $query->where('tipo_movimentacao_id', $request->tipo_movimentacao_id);
        }

        if ($request->filled('data_inicial') && $request->filled('data_final')) {
            $query->whereBetween('data_movimentacao', [$request->data_inicial, $request->data_final]);
        }

        $records = $query->get();

        $title = $this->formatTitle($this->listTitle, [
            'form_title' => $this->formTitle,
        ]);

        $filterUrl = "{$this->redirectPage}/filtro";

        $filters = [
            [
                'name' => 'veiculo_id',
                'label' => 'Veículo',
                'type' => 'select',
                'placeholder' => 'Todos os Veículos',
                'options' => Veiculo::select('id', \DB::raw("CONCAT(marca, ' ', modelo) AS nome"))->get()->map(function ($veiculo) {
                    return [
                        'value' => $veiculo->id,
                        'label' => $veiculo->nome,
                    ];
                })->toArray(),
                'value' => $request->veiculo_id ?? '',
            ],
            [
                'name' => 'tipo_movimentacao_id',
                'label' => 'Tipo de Movimentação',
                'type' => 'select',
                'placeholder' => 'Todos os Tipos',
                'options' => TipoMovimentacao::select('id', 'nome')->get()->map(function ($tipo) {
                    return [
                        'value' => $tipo->id,
                        'label' => $tipo->nome,
                    ];
                })->toArray(),
                'value' => $request->tipo_movimentacao_id ?? '',
            ],
            [
                'name' => 'data_inicial',
                'label' => 'Data Inicial',
                'type' => 'date',
                'value' => $request->data_inicial ?? '',
            ],
            [
                'name' => 'data_final',
                'label' => 'Data Final',
                'type' => 'date',
                'value' => $request->data_final ?? '',
            ],
        ];

        return view($this->listView, [
            'records' => $records,
            'title' => $title,
            'headers' => $this->headers(),
            'fields' => $this->fields(),
            'newItemUrl' => "{$this->redirectPage}/new",
            'newItemText' => 'Nova Movimentação',
            'editUrl' => "{$this->redirectPage}/edit",
            'deleteUrl' => "{$this->redirectPage}/delete",
            'filterUrl' => $filterUrl,
            'filters' => $filters, // Garante que $filters seja enviado à view
        ]);
    }


    public function register()
    {
        $title = $this->formatTitle($this->registerTitle, [
            'form_title' => $this->formTitle,
        ]);

        return view($this->registerView, [
            'data' => null,
            'title' => $title,
            'actionSave' => "{$this->redirectPage}/save",
            'actionCancel' => $this->redirectPage,
        ]);
    }

    public function edit($id)
    {
        $data = $this->getTenantRecords()->findOrFail($id);

        $title = $this->formatTitle($this->editTitle, [
            'registro_id' => $data->id,
            'form_title' => $this->formTitle,
        ]);

        return view($this->registerView, [
            'data' => $data,
            'title' => $title,
            'actionUpdate' => "{$this->redirectPage}/update",
            'actionCancel' => $this->redirectPage,
        ]);
    }
}
*/
