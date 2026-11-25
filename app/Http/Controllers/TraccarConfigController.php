<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TraccarConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use Exception;

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
            'password' => ['nullable', 'string', 'min:4'],
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
            'password.min' => 'A senha deve ter no mínimo 4 caracteres.',
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

        $baseUrl = rtrim($validatedData['base_url'], '/');
        $username = trim($validatedData['mail_user_name']);
        $rawPassword = $validatedData['password'] ?? null;

        // Recupera o registro existente da empresa para reutilizar senha se o campo vier vazio
        $configExistente = $this->model::where('empresa_id', $this->empresa_id)->first();

        if (empty($rawPassword) && !empty($configExistente?->password)) {
            try {
                $rawPassword = Crypt::decryptString($configExistente->password);
            } catch (Exception $e) {
                $rawPassword = $configExistente->password;
            }
        }

        if (!empty($rawPassword)) {
            try {
                // 1. Gera ou obtém o token diretamente via Basic Auth no endpoint de token do Traccar
                // Sem necessidade de manipular CookieJar manualmente
                $tokenResponse = Http::withBasicAuth($username, $rawPassword)
                    ->connectTimeout(15)
                    ->timeout(20)
                    ->post("{$baseUrl}/api/session/token");

                if ($tokenResponse->successful()) {
                    $tokenGerado = trim($tokenResponse->body(), '"');
                    $validatedData['token_traccar'] = $tokenGerado;
                } else {
                    // Fallback: se o endpoint de criação de token falhar, consulta a sessão para pegar o token de usuário
                    $sessionResponse = Http::withBasicAuth($username, $rawPassword)
                        ->connectTimeout(15)
                        ->timeout(20)
                        ->get("{$baseUrl}/api/session");

                    if ($sessionResponse->successful()) {
                        $tokenExistente = $sessionResponse->json('token');
                        if (!empty($tokenExistente)) {
                            $validatedData['token_traccar'] = $tokenExistente;
                        }
                    } else {
                        session()->flash('mensagem_erro', 'Falha na autenticação: usuário ou senha incorretos no Traccar.');
                        return redirect()->back()->withInput();
                    }
                }
            } catch (Exception $e) {
                session()->flash('mensagem_erro', 'Erro ao contactar o servidor Traccar: ' . $e->getMessage());
                return redirect()->back()->withInput();
            }

            // Encripta de forma reversível para uso nos comandos e robôs
            $validatedData['password'] = $rawPassword;
        } else {
            unset($validatedData['password']);
        }

        try {
            $this->model::updateOrCreate(
                ['empresa_id' => $this->empresa_id],
                $validatedData
            );

            session()->flash('mensagem_sucesso', 'Configurações e Token do Traccar atualizados com sucesso!');
            return redirect()->route('traccarConfigs.register');
        } catch (Exception $e) {
            session()->flash('mensagem_erro', 'Erro ao gravar as configurações: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }
}
