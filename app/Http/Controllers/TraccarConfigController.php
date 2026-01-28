<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TraccarConfig;

class TraccarConfigController extends BaseController
{
    protected $model = TraccarConfig::class;
    protected $redirectPage = '/traccarConfigs';
    protected $formTitle = 'Configurações Traccar';
    protected $registerView = 'traccar.config';
    protected $listView = 'traccar.config';

    protected function rules(): array
    {
        return [
            'empresa_id' => ['required', 'exists:empresas,id'],
            'usuario_id' => ['nullable', 'exists:usuarios,id'],
            'base_url' => ['required', 'url'],
            'socket_url' => ['nullable', 'url'],
            'mail_user_name' => ['required', 'string'],
            'password' => ['nullable', 'string', 'min:6'],
            'token_traccar' => ['nullable', 'string'],
            'default_map' => ['nullable', 'string'],
            'token_google_maps' => ['nullable', 'string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'empresa_id.required' => 'A empresa é obrigatória.',
            'base_url.required' => 'A URL base do Traccar é obrigatória.',
            'socket_url.required' => 'A URL Socket do Traccar é obrigatória.',
            'mail_user_name.required' => 'O nome de usuário é obrigatório.',
            'password.min' => 'A senha deve ter no mínimo 6 caracteres.',
        ];
    }

    public function register($id = null)
    {
        $data = $this->model::firstOrCreate(
            ['empresa_id' => $this->empresa_id],
            [
                'usuario_id' => $this->usuario_id,
                'base_url' => 'https://default-url.com',
                'socket_url' => 'wss://default-socket.com/api/socket',
                'mail_user_name' => 'default@example.com',
            ]
        );

        $title = $this->formTitle;

        return view($this->registerView, [
            'data' => $data,
            'title' => $title,
            'actionSave' => route('traccarConfigs.save'),
            'actionCancel' => $this->redirectPage,
        ]);
    }

    public function save(Request $request)
    {
        $validatedData = $request->validate($this->rules(), $this->messages());
        $validatedData['usuario_id'] = $this->usuario_id;

        if (empty($validatedData['password'])) {
            unset($validatedData['password']);
        }

        try {
            $this->model::updateOrCreate(
                ['empresa_id' => $this->empresa_id],
                $validatedData
            );

            session()->flash('mensagem_sucesso', 'Configurações salvas com sucesso!');
            return redirect()->route('traccarConfigs.register');
        } catch (\Exception $e) {
            session()->flash('mensagem_erro', 'Erro ao salvar configurações: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }
}
