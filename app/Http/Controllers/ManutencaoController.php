<?php

namespace App\Http\Controllers;

use App\Models\Fornecedor;
use App\Models\Funcionario;
use App\Models\Manutencao;
use App\Models\Veiculo;
use Illuminate\Http\Request;

class ManutencaoController extends BaseController
{
    protected $model = Manutencao::class;
    protected $resource = 'manutencoes';
    protected $table = 'manutencoes';
    protected $formTitle = 'Manutenções de Veículos';
    protected $listView = 'manutencoes.list';
    protected $registerView = 'manutencoes.register';
    protected $redirectPage = '/manutencoes';

    protected function getTenantRecords()
    {
        $query = parent::getTenantRecords()
            ->with(['veiculo', 'responsavel', 'fornecedor']);

        $query = $this->applyFilialFilter($query);

        return $query->orderByDesc('data_manutencao');
    }

    protected function headers(): array
    {
        return [
            'Veículo',
            'Tipo',
            'Descrição',
            'Data',
            'KM Atual',
            'Custo',
            'Prioridade',
            'Status',
            'Próxima Revisão',
        ];
    }

    protected function fields(): array
    {
        return [
            'veiculo.placa',
            'tipo',
            'descricao',
            'data_manutencao',
            'quilometragem_atual',
            'custo',
            'prioridade',
            'status',
            'proxima_manutencao_data',
        ];
    }

    protected function rules(): array
    {
        return [
            'veiculo_id' => 'required|exists:veiculos,id',
            'descricao' => 'required|string|max:255',
            'tipo' => 'nullable|string|max:60',
            'data_manutencao' => 'required|date',
            'custo' => 'nullable|numeric|min:0',
            'prioridade' => 'required|in:Baixa,Média,Alta',
            'status' => 'required|in:Planejada,Em andamento,Concluída',
            'quilometragem_atual' => 'nullable|numeric|min:0',
            'proxima_manutencao_km' => 'nullable|numeric|min:0',
            'proxima_manutencao_data' => 'nullable|date',
            'responsavel_id' => 'nullable|exists:funcionarios,id',
            'fornecedor_id' => 'nullable|exists:fornecedores,id',
            'observacoes' => 'nullable|string',
        ];
    }

    protected function messages(): array
    {
        return [
            'veiculo_id.required' => 'Selecione um veículo.',
            'veiculo_id.exists' => 'Veículo inválido.',
            'descricao.required' => 'Informe uma descrição para a manutenção.',
            'data_manutencao.required' => 'Informe a data da manutenção.',
            'data_manutencao.date' => 'Informe uma data de manutenção válida.',
            'prioridade.required' => 'Informe a prioridade.',
            'prioridade.in' => 'Prioridade informada é inválida.',
            'status.required' => 'Informe o status da manutenção.',
            'status.in' => 'Status informado é inválido.',
            'quilometragem_atual.numeric' => 'Informe a quilometragem em formato numérico.',
            'quilometragem_atual.min' => 'A quilometragem deve ser maior ou igual a zero.',
            'proxima_manutencao_km.numeric' => 'Informe a próxima quilometragem em formato numérico.',
            'proxima_manutencao_km.min' => 'A próxima quilometragem deve ser maior ou igual a zero.',
            'proxima_manutencao_data.date' => 'Informe uma data válida para a próxima manutenção.',
            'responsavel_id.exists' => 'Responsável selecionado é inválido.',
            'fornecedor_id.exists' => 'Fornecedor selecionado é inválido.',
        ];
    }

    protected function defineFilters(Request $request): array
    {
        return [
            [
                'name' => 'veiculo_id',
                'label' => 'Veículo',
                'type' => 'select',
                'placeholder' => 'Todos os veículos',
                'options' => $this->applyFilialFilter(
                        Veiculo::where('empresa_id', $this->empresa_id)
                    )
                    ->orderBy('placa')
                    ->get()
                    ->map(fn($veiculo) => [
                        'value' => $veiculo->id,
                        'label' => $veiculo->placa . ' - ' . $veiculo->marca . ' ' . $veiculo->modelo,
                    ])->toArray(),
            ],
            [
                'name' => 'status',
                'label' => 'Status',
                'type' => 'select',
                'placeholder' => 'Todos os status',
                'options' => collect(['Planejada', 'Em andamento', 'Concluída'])
                    ->map(fn($status) => ['value' => $status, 'label' => $status])
                    ->toArray(),
            ],
            [
                'name' => 'prioridade',
                'label' => 'Prioridade',
                'type' => 'select',
                'placeholder' => 'Todas as prioridades',
                'options' => collect(['Baixa', 'Média', 'Alta'])
                    ->map(fn($prioridade) => ['value' => $prioridade, 'label' => $prioridade])
                    ->toArray(),
            ],
            [
                'name' => 'data_manutencao',
                'label' => 'Data',
                'type' => 'date',
            ],
        ];
    }

    public function register($id = null)
    {
        $query = $this->applyFilialFilter(
            $this->model::where('empresa_id', $this->empresa_id)
        )->with(['veiculo', 'responsavel', 'fornecedor']);

        $data = $id ? $query->findOrFail($id) : null;

        $title = $this->formatString($this->registerTitle, [
            'form_title' => $this->formTitle,
        ]);

        $veiculos = $this->applyFilialFilter(
            Veiculo::where('empresa_id', $this->empresa_id)
        )
            ->orderBy('placa')
            ->get();

        $funcionarios = Funcionario::where('empresa_id', $this->empresa_id)
            ->orderBy('nome')
            ->get();

        $fornecedores = Fornecedor::where('empresa_id', $this->empresa_id)
            ->orderBy('razao_social')
            ->get();

        return view($this->registerView, [
            'data' => $data,
            'title' => $title,
            'actionSave' => "{$this->redirectPage}/save",
            'actionUpdate' => "{$this->redirectPage}/update",
            'actionCancel' => $this->redirectPage,
            'veiculos' => $veiculos,
            'funcionarios' => $funcionarios,
            'fornecedores' => $fornecedores,
        ]);
    }

    protected function prepararChecklist(Request $request): void
    {
        $checklistItens = $request->input('checklist_items');

        if ($checklistItens === null) {
            return;
        }

        $linhas = preg_split('/\r\n|\r|\n/', $checklistItens);
        $itens = array_values(array_filter(array_map('trim', $linhas)));

        $request->merge([
            'checklist' => $itens ?: null,
        ]);
    }

    public function save(Request $request)
    {
        $this->prepararChecklist($request);

        if (!$request->filled('status')) {
            $request->merge(['status' => 'Planejada']);
        }

        if (!$request->filled('prioridade')) {
            $request->merge(['prioridade' => 'Média']);
        }

        return parent::save($request);
    }

    public function update(Request $request, $id)
    {
        $this->prepararChecklist($request);

        return parent::update($request, $id);
    }
}
