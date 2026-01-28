<?php

namespace App\Http\Controllers;

use App\Services\LogService;
use Illuminate\Http\Request;
use App\Models\ConfigNota;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SyncDataController extends Controller
{
    protected $logService;

    public function __construct()
    {
        //$this->middleware('throttle:10,1');
        // Inicializa apenas sem passar empresa_id e usuario_id pois serão setados dinamicamente na função sync()
        $this->logService = null;
    }

    public function sync(Request $request)
    {
        $rules = [
            'cnpj'       => 'required',
            'token_sync' => 'required',
            'tenant_id'  => 'required|integer',
            'model'      => 'required|string'
        ];

        if (!$request->isMethod('get')) {
            $rules['data'] = 'required|array';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => implode(' ', $validator->errors()->all())
            ], 400);
        }

        // Normaliza o CNPJ removendo quaisquer caracteres que não sejam dígitos
        $cnpj = preg_replace('/\D/', '', $request->input('cnpj'));
        $tokenSync  = $request->input('token_sync');
        $tenantId   = $request->input('tenant_id');
        $modelName  = $request->input('model');
        $data       = $request->input('data');

        // Inicializa o LogService com a empresa_id recebida da API e usuario_id, filial_id como null
        $this->logService = new LogService($tenantId, null, null);
        // Lista de modelos permitidos para sincronização
        /*
        $allowedModels = [
            'Cliente'     => \App\Models\Cliente::class,
            'Veiculo'     => \App\Models\Veiculo::class,
            'SubCategoria'=> \App\Models\SubCategoria::class,
            'Produto'     => \App\Models\Produto::class,
            // Adicione outros modelos conforme necessário.
        ];
        */

        $allowedModels = config('allowed_models');

        if (!isset($allowedModels[$modelName])) {
            return response()->json([
                'success' => false,
                'message' => 'Modelo não permitido para sincronização.'
            ], 400);
        }
        $modelClass = $allowedModels[$modelName];

        // Registra log do request usando o model passado via API (convertido em objeto)
        $this->logService->registrar('sync_request', get_class(new $modelClass), [
            'tenant_id'  => $tenantId,
            'cnpj'       => $cnpj,
            'token_sync' => $tokenSync
        ]);

        $config = ConfigNota::where('empresa_id', $tenantId)
            ->whereRaw("REPLACE(cnpj, ' ', '') = ?", [$cnpj])
            ->where('token_sync', $tokenSync)
            ->first();

        if (!$config) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciais de tenant inválidas.'
            ], 403);
        }

        try {
            $method = $request->method();
            $data['empresa_id'] = $tenantId;

            switch ($method) {
                case 'POST':
                    $record = $modelClass::create($data);
                    $action = 'create';
                    break;

                case 'PUT':
                case 'PATCH':
                    if (!isset($data['id'])) {
                        return response()->json([
                            'success' => false,
                            'message' => 'ID necessário para atualização.'
                        ], 400);
                    }

                    $record = $modelClass::where('empresa_id', $tenantId)->find($data['id']);
                    if ($record) {
                        $dadosAntes = json_encode($record->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        $record->update($data);
                        $action = 'update';
                        $this->logService->registrar('sync_update', get_class(new $modelClass), [
                            'tenant_id'    => $tenantId,
                            'record_id'    => $record->id,
                            'dados_antes'  => $dadosAntes,
                            'dados_depois' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        ]);
                    } else {
                        $record = $modelClass::create($data);
                        $action = 'create';
                        $this->logService->registrar('sync_create_fallback', get_class(new $modelClass), [
                            'tenant_id'    => $tenantId,
                            'record_id'    => $record->id ?? null,
                            'dados_antes'  => null,
                            'dados_depois' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        ]);
                    }
                    break;

                case 'DELETE':
                    if (!isset($data['id'])) {
                        return response()->json([
                            'success' => false,
                            'message' => 'ID necessário para exclusão.'
                        ], 400);
                    }

                    $record = $modelClass::where('empresa_id', $tenantId)->find($data['id']);
                    if ($record) {
                        $dadosAntes = json_encode($record->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        $record->delete();
                        $this->logService->registrar('delete', get_class(new $modelClass), [
                            'tenant_id'    => $tenantId,
                            'record_id'    => $data['id'],
                            'dados_antes'  => $dadosAntes,
                            'dados_depois' => null,
                        ]);
                        return response()->json([
                            'success' => true,
                            'message' => 'Registro excluído com sucesso.'
                        ], 200);
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'Registro não encontrado para exclusão.'
                        ], 404);
                    }

                // Dentro do switch principal do método `sync`, substitua o trecho do `case 'GET'` por:

                case 'GET':
                    // Autenticação do tenant continua obrigatória
                    $config = ConfigNota::where('empresa_id', $tenantId)
                        ->whereRaw("REPLACE(cnpj, ' ', '') = ?", [$cnpj])
                        ->where('token_sync', $tokenSync)
                        ->first();

                    if (!$config) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Credenciais de tenant inválidas.'
                        ], 403);
                    }

                    $query = $modelClass::query();

                    // Detecta se a tabela possui a coluna empresa_id
                    $hasEmpresaId = \Schema::hasColumn((new $modelClass)->getTable(), 'empresa_id');
                    if ($hasEmpresaId) {
                        $query->where('empresa_id', $tenantId);
                    }

                    // Aplica os demais filtros da query string, exceto campos de controle
                    foreach ($request->query() as $key => $value) {
                        if (in_array($key, ['cnpj', 'token_sync', 'model', 'empresa_id', 'tenant_id'])) continue;
                        $query->where($key, $value);
                    }

                    $records = $query->get();

                    // Log da operação GET
                    $this->logService->registrar('sync_get', get_class(new $modelClass), [
                        'tenant_id' => $tenantId,
                        'filtros'   => json_encode($request->query(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Dados obtidos com sucesso.',
                        'data'    => $records
                    ], 200);

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Método não suportado: ' . $method
                    ], 405);
            }

            // Obtém o token de sessão (mesmo sem usuário logado, o Laravel gera um token para a sessão)
            $sessionToken = session()->token();
            // Registra log da sincronização realizada com sucesso, incluindo o id da empresa e os dados da API
            if (!isset($action)) $action = 'undefined';

            $this->logService->registrar('sync_' . $action, get_class(new $modelClass), [
                'tenant_id'    => $tenantId,
                'record_id'    => $record->id ?? null,
                'dados_antes'  => null,
                'dados_depois' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ]);

            return response()->json([
                'success'       => true,
                'message'       => 'Operação ' . strtoupper($method) . ' realizada com sucesso.',
                'data'          => $record,
                'session_token' => $sessionToken
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erro na sincronização: ' . $e->getMessage());
            $this->logService->registrar('sync_error', get_class(new $modelClass), [
                'tenant_id'    => $tenantId,
                'error'        => $e->getMessage(),
                'dados_depois' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao sincronizar: ' . $e->getMessage()
            ], 500);
        }

    }

    public function docsync(Request $request)

    {
        $validator = Validator::make($request->all(), [
            'cnpj'       => 'required',
            'token_sync' => 'required',
            'tenant_id'  => 'required|integer',
            'model'      => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => implode(' ', $validator->errors()->all())
            ], 400);
        }

        $cnpj = preg_replace('/\D/', '', $request->input('cnpj'));
        $tokenSync = $request->input('token_sync');
        $tenantId = $request->input('tenant_id');
        $modelName = $request->input('model');

        $allowedModels = config('allowed_models');
        if (!isset($allowedModels[$modelName])) {
            return response()->json([
                'success' => false,
                'message' => 'Modelo não permitido.'
            ], 400);
        }

        $config = ConfigNota::where('empresa_id', $tenantId)
            ->whereRaw("REPLACE(cnpj, ' ', '') = ?", [$cnpj])
            ->where('token_sync', $tokenSync)
            ->first();

        if (!$config) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciais de tenant inválidas.'
            ], 403);
        }

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="pt-br">
        <head>
            <meta charset="UTF-8">
            <title>Documentação da API de Sincronização</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; background: #f8f9fa; }
                h1, h2, h3 { color: #2c3e50; }
                code, pre { background: #eef; padding: 5px; display: block; margin-bottom: 10px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
                .ok { color: green; }
                .fail { color: red; }
                .info { background: #e3f2fd; padding: 10px; border-left: 4px solid #2196f3; margin: 10px 0; }
            </style>
        </head>
        <body>
            <h1>Documentação da API de Sincronização</h1>
            <p><strong>Controller:</strong> <code>SyncDataController</code></p>
            <p><strong>Rota principal:</strong> <code>/sync/data</code></p>
            <p><strong>Suporta:</strong> <code>POST, GET, PUT, PATCH, DELETE</code></p>

            <div class="info">
                <strong>Importante:</strong> O campo <code>tenant_id</code> é obrigatório em todas as chamadas e será forçado como <code>empresa_id</code> automaticamente, se a tabela da model tiver essa coluna.
            </div>

            <h2>Parâmetros obrigatórios</h2>
            <table>
                <thead>
                    <tr><th>Campo</th><th>Tipo</th><th>Descrição</th></tr>
                </thead>
                <tbody>
                    <tr><td><code>cnpj</code></td><td>string</td><td>CNPJ da empresa</td></tr>
                    <tr><td><code>token_sync</code></td><td>string</td><td>Token de autenticação</td></tr>
                    <tr><td><code>tenant_id</code></td><td>int</td><td>ID da empresa (equivale a empresa_id)</td></tr>
                    <tr><td><code>model</code></td><td>string</td><td>Nome da model (ex: Produto)</td></tr>
                    <tr><td><code>data</code></td><td>array</td><td>Obrigatório para POST, PUT, PATCH, DELETE</td></tr>
                </tbody>
            </table>

            <h2>Exemplos de uso</h2>

            <h3>GET (consulta)</h3>
            <pre>GET /sync/data?cnpj=123...&token_sync=abc...&tenant_id=1&model=Produto&id=5</pre>
            <p>Consulta registros usando filtros flexíveis na query.</p>

            <h3>POST (criação)</h3>
            <pre>{
          "cnpj": "...",
          "token_sync": "...",
          "tenant_id": 1,
          "model": "Produto",
          "data": {
            "nome": "Produto A",
            "categoria_id": 2
          }
        }</pre>

            <h3>PUT / PATCH (atualização)</h3>
            <pre>{
          "cnpj": "...",
          "token_sync": "...",
          "tenant_id": 1,
          "model": "Produto",
          "data": {
            "id": 5,
            "nome": "Produto Atualizado"
          }
        }</pre>

            <h3>DELETE</h3>
            <pre>{
          "cnpj": "...",
          "token_sync": "...",
          "tenant_id": 1,
          "model": "Produto",
          "data": {
            "id": 5
          }
        }</pre>

            <h2>Validações e Segurança</h2>
            <ul>
                <li>Validação de CNPJ + token_sync + tenant_id em <code>config_notas</code></li>
                <li>Força <code>empresa_id = tenant_id</code> quando o campo existir na tabela</li>
                <li>Validação dinâmica de campos obrigatórios por método</li>
                <li>Requisições são logadas com ação, modelo, dados antes/depois</li>
            </ul>

            <h2>Autorização</h2>
            <p>Valida se o <code>tenant_id</code> e <code>cnpj</code> pertencem a uma configuração ativa na tabela <code>config_notas</code>.</p>

            <h2>Log</h2>
            <p>Todas as ações são registradas pelo <code>LogService</code> com dados antes e depois (quando aplicável).</p>

            <h2>Lista de models permitidas</h2>
            <pre>// config/allowed_models.php
        return [
            'Produto' => \\App\\Models\\Produto::class,
            'Cliente' => \\App\\Models\\Cliente::class,
            'SubCategoria' => \\App\\Models\\SubCategoria::class,
        ];</pre>

        </body>
        </html>
        HTML;

        return response($html, 200)->header('Content-Type', 'text/html');
    }

}
