<?php

namespace App\Http\Controllers;

use App\Models\TipoMovimentacao;
use Illuminate\Http\Request;

class TipoMovimentacaoController extends BaseController
{
    protected $model = TipoMovimentacao::class;
    protected $resource = 'tipos_movimentacoes';
    protected $table = 'tipos_movimentacoes';
    protected $formTitle = 'Tipos de Movimentações';
    protected $listView = 'tipos_movimentacoes.list';
    protected $registerView = 'tipos_movimentacoes.register';
    protected $redirectPage = '/tiposMovimentacao';

    protected function defineFilters(Request $request): array
    {
        return [
            [
                'name' => 'nome',
                'label' => 'Nome',
                'type' => 'text',
                'placeholder' => 'Digite o nome',
            ],
            [
                'name' => 'ativo',
                'label' => 'Status',
                'type' => 'select',
                'placeholder' => 'Todos os Status',
                'options' => [
                    ['value' => '1', 'label' => 'Ativo'],
                    ['value' => '0', 'label' => 'Inativo'],
                ],
            ]
        ];
    }



    protected function headers(): array
    {
        return ['Nome', 'Descrição', 'Ativo'];
    }

    protected function fields(): array
    {
        return ['nome', 'descricao', 'ativo'];
    }

    protected function rules(): array
    {
        return [
            'nome' => 'required|string|max:100',
            'descricao' => 'nullable|string|max:255',
            'ativo' => 'required|boolean',
        ];
    }

    protected function messages(): array
    {
        return [
            'nome.required' => 'O campo Nome é obrigatório.',
            'ativo.required' => 'O campo Ativo é obrigatório.',
            'ativo.boolean' => 'O campo Ativo deve ser verdadeiro ou falso.',
        ];
    }
}


/*
    protected $redirectDeny         = '/403';
    protected $listTitle            = 'Lista de {form_title}';
    protected $registerTitle        = 'Cadastro de {form_title}';
    protected $editTitle            = 'Editando registro #{registro_id} - {form_title}';
    protected $successCreate        = 'Novo registro inserido com sucesso em: {form_title}';
    protected $errorCreate          = 'Erro ao inserir um novo registro em: {form_title}';
    protected $successUpdateMessage = 'O registro #{registro_id} foi atualizado com sucesso em: {form_title}';
    protected $errorUpdateMessage   = 'Erro ao atualizar o registro #{registro_id} em: {form_title}';
    protected $successDeleteMessage = 'O registro #{registro_id} foi removido com sucesso em: {form_title}';
    protected $errorDeleteMessage   = 'Erro ao remover o registro #{registro_id} em: {form_title}';

    protected function getTenantRecords()
    {
        return $this->model::where('empresa_id', $this->empresa_id);
    }

    protected function rules(): array
    {
        return [
            'nome' => 'required|string|max:100',
            'descricao' => 'nullable|string|max:255',
            'ativo' => 'required|boolean',
        ];
    }

    protected function messages(): array
    {
        return [
            'nome.required' => 'O campo Nome é obrigatório.',
            'ativo.required' => 'O campo Ativo é obrigatório.',
            'ativo.boolean' => 'O campo Ativo deve ser verdadeiro ou falso.',
        ];
    }

    protected function headers(): array
    {
        return ['Nome', 'Descrição', 'Ativo'];
    }

    protected function fields(): array
    {
        return ['nome', 'descricao', 'ativo'];
    }
*/
 /*
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
            'nome' => 'required|string|max:100',
            'descricao' => 'nullable|string|max:255',
            'ativo' => 'required|boolean',
        ];
    }

    protected function messages(): array
    {
        return [
            'nome.required' => 'O campo Nome é obrigatório.',
            'ativo.required' => 'O campo Ativo é obrigatório.',
            'ativo.boolean' => 'O campo Ativo deve ser verdadeiro ou falso.',
        ];
    }

    public function list(Request $request)
    {
        $query = $this->getTenantRecords();

        $records = $query->get();

        $title = $this->formatTitle($this->listTitle, [
            'form_title' => $this->formTitle,
        ]);

        return view($this->listView, [
            'records' => $records,
            'title' => $title,
            'headers' => ['Nome', 'Descrição', 'Ativo'],
            'fields' => ['nome', 'descricao', 'ativo'],
            'newItemUrl' => "{$this->redirectPage}/new",
            'newItemText' => 'Novo Registro',
            'editUrl' => "{$this->redirectPage}/edit",
            'deleteUrl' => "{$this->redirectPage}/delete",
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



/*
namespace App\Http\Controllers;

use App\Models\TipoMovimentacao;

class TipoMovimentacaoController extends BaseController
{
    protected $model = TipoMovimentacao::class;
    protected $resource = 'tipos_movimentacoes';
    protected $table = 'tipos_movimentacoes';
    protected $formTitle = 'Tipos de Movimentações';
    protected $listView = 'tipos_movimentacoes.list';
    protected $registerView = 'tipos_movimentacoes.register';
    protected $redirectPage = '/tiposMovimentacao';
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
            'nome' => 'required|string|max:100',
            'descricao' => 'nullable|string|max:255',
            'ativo' => 'required|boolean',
        ];
    }

    protected function messages(): array
    {
        return [
            'nome.required' => 'O campo Nome é obrigatório.',
            'ativo.required' => 'O campo Ativo é obrigatório.',
            'ativo.boolean' => 'O campo Ativo deve ser verdadeiro ou falso.',
        ];
    }

    public function list()
    {
        $records = $this->getTenantRecords()->get();

        $title = $this->formatTitle($this->listTitle, [
            'form_title' => $this->formTitle,
        ]);

        return view($this->listView, [
            'records' => $records,
            'title' => $title,
            'headers' => ['Nome', 'Descrição', 'Ativo'],
            'fields' => ['nome', 'descricao', 'ativo'],
            'newItemUrl' => "{$this->redirectPage}/new",
            'newItemText' => 'Novo Registro',
            'editUrl' => "{$this->redirectPage}/edit",
            'deleteUrl' => "{$this->redirectPage}/delete",
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
