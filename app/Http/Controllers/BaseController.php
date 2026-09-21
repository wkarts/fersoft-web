<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use App\Services\LogService;
use App\Models\ConnectApiInstance;
use App\Models\BaseModel;
use App\Utils\WhatsAppUtil;
use Illuminate\Support\Facades\DB;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\Auth;
use App\Models\Usuario;
use App\Services\OtpService;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Support\AuditContext;

abstract class BaseController extends Controller
{
    protected $model;
    protected $logService;
    protected $resource;
    protected $table;
    protected $formTitle;
    protected $listTitle        = 'Lista de {form_title}';
    protected $registerTitle    = 'Cadastro de {form_title}';
    protected $editTitle        = 'Editando registro #{registro_id} - {form_title}';
    protected $listView;
    protected $registerView;
    protected $Prefix_Route;
    protected $redirectPage;
    protected $redirectDeny         = '/403';
    protected $successCreate        = 'Novo registro inserido com sucesso em: {form_title}';
    protected $errorCreate          = 'Erro ao inserir um novo registro em: {form_title}';
    protected $successUpdateMessage = 'O registro #{registro_id} foi atualizado com sucesso em: {form_title}';
    protected $errorUpdateMessage   = 'Erro ao atualizar o registro #{registro_id} em: {form_title}';
    protected $successDeleteMessage = 'O registro #{registro_id} foi removido com sucesso em: {form_title}';
    protected $errorDeleteMessage   = 'Erro ao remover o registro #{registro_id} em: {form_title}';
    protected $empresa_id;

    protected $usuario_id;
    protected $filial_id;
    protected $successWhatsAppMessage = 'Mensagem e arquivos enviados com sucesso para {numero}.';
    protected $errorWhatsAppMessage = 'Erro ao enviar mensagem ou arquivos para {numero}. Verifique os logs.';
    protected $whatsapputil;
    protected ?int $ambienteNFe = null;
    protected ?string $ambienteNFeLabel = null;

    public function __construct()
    {
        //$this->logService = app(LogService::class);
        $this->whatsapputil = app(WhatsAppUtil::class);

        $this->middleware(function ($request, $next) {
            $this->empresa_id = $request->empresa_id;

            $this->usuario_id = session('user_logged')['id'] ?? null;
            if (!$this->usuario_id) {
                return redirect('/login');
            }

            // ─── NORMALIZAÇÃO: tudo <= 0 vira null ───
            $rawFilial = $request->get('filial_id');

            if (isset($rawFilial)) {
                $rawFilial = (int) $rawFilial;
                if ($rawFilial <= 0) {
                    $rawFilial = null;
                }
            } else {
                $rawFilial = null;
            }

            // Lê o local padrão da sessão de forma segura
            $localPadrao = null;
            if (session()->has('user_logged')) {
                $userLogged   = session('user_logged');
                $localPadrao  = $userLogged['local_padrao'] ?? null;

                if (isset($localPadrao)) {
                    $localPadrao = (int) $localPadrao;
                    if ($localPadrao <= 0) {
                        $localPadrao = null;
                    }
                } else {
                    $localPadrao = null;
                }
            }

            // 🔹 Define a filial padrão com prioridade:
            // 1. Requisição (request param já normalizado)
            // 2. Sessão do usuário (local_padrao normalizado)
            // 3. Fallback: null
            $this->filial_id = $rawFilial ?? $localPadrao ?? null;
            // ───────────────────────────────────────────

            if (!property_exists($this, 'redirectPage') || empty($this->redirectPage)) {
                throw new \Exception('A propriedade $redirectPage não está corretamente definida no controlador: ' . static::class);
            }

            // 🔹 Inicializa o LogService com empresa e usuário automaticamente
            $this->logService = new LogService($this->empresa_id, $this->usuario_id, $this->filial_id);

            // Compartilha apenas o estado operacional da Connect|API com as views.
            // O token legado de ConfigNota não participa mais da disponibilidade do WhatsApp.
            $connectApiWhatsApp = $this->getConnectApiWhatsAppState();
            view()->share('connectApiWhatsAppInstance', $connectApiWhatsApp['instance']);
            view()->share('connectApiWhatsAppReady', $connectApiWhatsApp['ready']);
            view()->share('connectApiWhatsAppStatus', $connectApiWhatsApp['status']);

            return $next($request);
        });
    }

    /**
     * Detecta suporte a soft delete tanto no trait nativo quanto no BaseModel custom.
     */
    protected function modelSupportsSoftDelete(): bool
    {
        try {
            if (!$this->model) {
                return false;
            }

            $modelClass = is_string($this->model) ? $this->model : get_class($this->model);

            if (!class_exists($modelClass)) {
                return false;
            }

            if (method_exists($modelClass, 'supportsSoftDelete')) {
                return (bool) $modelClass::supportsSoftDelete();
            }

            if (method_exists($modelClass, 'bootSoftDeletes')) {
                return true;
            }

            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function getTenantRecords()
    {
        $query = $this->model::where('empresa_id', $this->empresa_id);

        if ($this->modelSupportsSoftDelete()) {
            $query = $query->withTrashed();
        }
        return $query;
    }

    protected function formatString(string $template, array $placeholders = []): string
    {
        foreach ($placeholders as $key => $value) {
            $template = str_replace("{{$key}}", $value, $template);
        }
        return $template;
    }

    /**
     * Formata um array ou string JSON para um JSON válido.
     *
     * @param mixed $data
     * @return string|null
     */
    protected function formatarJson($data): ?string
    {
        if (is_string($data)) {
            // 🔹 Tenta decodificar e depois recodificar para evitar JSON com escapes desnecessários
            $decoded = json_decode($data, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            return $data; // Se não puder decodificar, mantém como string original
        }
        if (is_array($data)) {
            return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return null; // Retorna null se os dados não forem manipuláveis
    }

    protected function applyFilters(Request $request, $query)
    {
        if (method_exists($this, 'defineFilters')) {
            $filters = $this->defineFilters($request);

            foreach ($filters as $filter) {
                $value = $request->get($filter['name']);
                if (!empty($value)) {
                    switch ($filter['type']) {
                        case 'select':
                            $query->where($filter['name'], $value);
                            break;

                        case 'text':
                            // Aplica busca parcial usando LIKE para todos os campos de texto
                            $query->where($filter['name'], 'like', '%' . $value . '%');
                            break;

                        case 'date_range':
                            if (isset($value['start'], $value['end'])) {
                                $query->whereBetween($filter['name'], [$value['start'], $value['end']]);
                            }
                            break;

                        case 'date':
                            $query->whereDate($filter['name'], $value);
                            break;
                    }
                }
            }
        }

        return $query;
    }

    protected function applyFilters_nolike(Request $request, $query)
    {
        if (method_exists($this, 'defineFilters')) {
            $filters = $this->defineFilters($request);

            foreach ($filters as $filter) {
                $value = $request->get($filter['name']);
                if (!empty($value)) {
                    switch ($filter['type']) {
                        case 'select':
                        case 'text':
                            $query->where($filter['name'], $value);
                            break;

                        case 'date_range':
                            if (isset($value['start'], $value['end'])) {
                                $query->whereBetween($filter['name'], [$value['start'], $value['end']]);
                            }
                            break;

                        case 'date':
                            $query->whereDate($filter['name'], $value);
                            break;
                    }
                }
            }
        }

        return $query;
    }

    protected function defineFilters(Request $request): array
    {
        return [];
    }

    protected function getFilters(Request $request): array
    {
        $filters = method_exists($this, 'defineFilters') ? $this->defineFilters($request) : [];

        foreach ($filters as &$filter) {
            $filter['value'] = $request->get($filter['name'], $filter['default'] ?? '');
        }

        return $filters;
    }

    public function list(Request $request)
    {
        $query = $this->getTenantRecords();

        if (method_exists($this, 'defineFilters')) {
            $query = $this->applyFilters($request, $query);
        }

        $records = $query->get();

        $title = $this->formatString($this->listTitle, [
            'form_title' => $this->formTitle,
        ]);

        $connectApiWhatsApp = $this->getConnectApiWhatsAppState();

        $filiais = $this->filial_id;

        return view($this->listView, [
            'records' => $records,
            'title' => $title,
            'headers' => $this->headers(),
            'fields' => $this->fields(),
            'newItemUrl' => "{$this->redirectPage}/new",
            'newItemText' => 'Novo Registro',
            'editUrl' => "{$this->redirectPage}/edit",
            'deleteUrl' => "{$this->redirectPage}/delete",
            'filterUrl' => "{$this->redirectPage}/list",
            'filters' => $this->getFilters($request),
            'connectApiWhatsAppInstance' => $connectApiWhatsApp['instance'],
            'connectApiWhatsAppReady' => $connectApiWhatsApp['ready'],
            'connectApiWhatsAppStatus' => $connectApiWhatsApp['status'],
            'filiais' => $filiais,
        ]);
    }

    public function filtro(Request $request)
    {
        return $this->list($request);
    }

    protected function headers(): array
    {
        return [];
    }

    protected function fields(): array
    {
        return [];
    }

    public function register($id = null)
    {
        $data = $id ? $this->model::findOrFail($id) : null;

        $title = $this->formatString($this->registerTitle, [
            'form_title' => $this->formTitle,
        ]);

        $filiais = $this->filial_id;

        return view($this->registerView, [
            'data' => $data,
            'title' => $title,
            'actionSave' => "{$this->redirectPage}/save",
            'actionUpdate' => "{$this->redirectPage}/update",
            'actionCancel' => $this->redirectPage,
            'filiais' => $filiais,
        ]);
    }

    /**
     * Garante que sempre haja um filial_id válido
     */
    protected function resolveFilialId(Request $request): ?int
    {
        $raw  = $request->input('filial_id', null);
        $int  = intval($raw);
        if ($int > 0) {
            return $int;
        }
        $padrao = session('user_logged.local_padrao');
        return (is_numeric($padrao) && $padrao > 0) ? intval($padrao) : null;
    }

    /**
     * Aplica filtro por filial considerando a matriz (filial_id nulo).
     */
    protected function applyFilialFilter($query, string $column = 'filial_id')
    {
        if ($this->filial_id === null) {
            return $query;
        }

        return $query->where(function ($builder) use ($column) {
            $builder->where($column, $this->filial_id)
                ->orWhereNull($column);
        });
    }

    public function save(Request $request)
    {
        if (!$this->validateRequest($request)) {
            return redirect()->back()->withInput();
        }

        try {
            $data = $request->all();
            $acao = 'create';
            $registroId = null;
            $dadosAnteriores = [];

            if ($request->filled('id')) {
                $registroId = $request->id;
                $record = $this->model::where('empresa_id', $this->empresa_id)
                    ->findOrFail($request->id);

                if (!valida_objeto($record)) {
                    throw new \Exception('Você não tem permissão para atualizar este registro.');
                }

                // 🔹 Captura os dados antes da alteração para o log garantindo JSON correto
                $dadosAnteriores = $record->getAttributes();

                $record->fill($data);
                $record->save();
                $acao = 'update';

                session()->flash('mensagem_sucesso', $this->formatString(
                    $this->successUpdateMessage,
                    ['registro_id' => $record->id, 'form_title' => $this->formTitle]
                ));
            } else {
                $data['empresa_id'] = $this->empresa_id;
                $data['usuario_id'] = $this->usuario_id;
                $data['filial_id'] = $this->filial_id;
                //$data['filial_id']  = $this->resolveFilialId($request);

                $record = $this->model::create($data);
                $registroId = $record->id;

                session()->flash('mensagem_sucesso', $this->formatString(
                    $this->successCreate,
                    ['registro_id' => $record->id, 'form_title' => $this->formTitle]
                ));
            }

            // Models que herdam BaseModel já foram auditados automaticamente durante
            // create/save. Não registra uma segunda cópia manual da mesma operação.
            // Models Eloquent legados que não usam BaseModel continuam cobertos aqui.
            if (!($record instanceof BaseModel)) {
                $modelInstance = is_string($this->model) ? app($this->model) : $this->model;

                $this->logService->registrar($acao, get_class($modelInstance), [
                    'registro_id' => $registroId,
                    'dados_antes' => $dadosAnteriores,
                    'dados_depois' => $record->getAttributes(),
                ]);
            }

        } catch (\Exception $e) {
            session()->flash('mensagem_erro', $e->getMessage());
            return redirect()->back()->withInput();
        }

        return redirect($this->redirectPage);
    }

    public function update(Request $request, $id)
    {
        try {
            $request->merge(['id' => $id]);

            // Corrigido para não duplicar log de update:
            // o save() já atualiza e já registra o log manual do controller.
            return $this->save($request);
        } catch (\Exception $e) {
            \Log::error('Erro ao atualizar registro', [
                'id' => $id,
                'empresa_id' => $this->empresa_id,
                'exception' => $e->getMessage(),
            ]);

            session()->flash('mensagem_erro', 'Erro ao atualizar o registro. Tente novamente mais tarde.');
            return redirect()->back()->withInput();
        }
    }

    public function edit($id)
    {
        try {
            // 🔹 Busca o registro pertencente ao tenant
            $record = $this->getTenantRecords()->findOrFail($id);
            // Abrir formulário não altera estado e não pertence à auditoria de negócio.

            return $this->register($id); // Comportamento padrão para exibição do formulário
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('Registro não encontrado', [
                'id' => $id,
                'empresa_id' => $this->empresa_id,
                'exception' => $e->getMessage(),
            ]);

            $errorMessage = "O registro com o ID #{$id} não foi encontrado ou não pertence à sua empresa.";

            session()->flash('mensagem_erro', $errorMessage);
            return redirect($this->redirectPage); // Redireciona para a página padrão
        } catch (\Exception $e) {
            \Log::error('Erro ao carregar o registro', [
                'id' => $id,
                'empresa_id' => $this->empresa_id,
                'exception' => $e->getMessage(),
            ]);

            session()->flash('mensagem_erro', 'Erro ao carregar o registro. Tente novamente mais tarde.');
            return redirect($this->redirectPage); // Redireciona para a página padrão
        }
    }

    public function delete($id)
    {
        try {
            $record = $this->model::where('empresa_id', $this->empresa_id)
                ->findOrFail($id);

            if (!valida_objeto($record)) {
                throw new \Exception('Você não tem permissão para excluir este registro.');
            }

            // 🔹 Captura os dados antes da exclusão para o log
            $dadosAnteriores = $record->getAttributes();
            $acao = 'delete';

            // Evita duplicidade somente quando o model realmente usa a auditoria automática.
            if ($record instanceof BaseModel) {
                $this->disableNextModelAudit();
            }

            if ($this->modelSupportsSoftDelete()) {
                $record->delete();
            } else {
                if (method_exists($record, 'forceDelete')) {
                    $record->forceDelete();
                } else {
                    $record->delete();
                }
            }

            // 🔹 Certifica-se de que $this->model é uma instância válida antes de chamar get_class()
            $modelInstance = is_string($this->model) ? app($this->model) : $this->model;

            // 🔹 Registra o log da exclusão
            $this->logService->registrar($acao, get_class($modelInstance), [
                'registro_id' => $id,
                'dados_antes' => $dadosAnteriores,
                'dados_depois' => null, // Após exclusão, não há mais dados
            ]);

            session()->flash('mensagem_sucesso', $this->formatString(
                $this->successDeleteMessage,
                ['registro_id' => $id, 'form_title' => $this->formTitle]
            ));
        } catch (\Exception $e) {
            session()->flash('mensagem_erro', $e->getMessage());
        }

        return redirect($this->redirectPage);
    }

    protected function validateRequest(Request $request): bool
    {
        try {
            $rules = $this->rules();
            $messages = $this->messages();

            $validator = \Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                session()->flash('mensagem_erro', implode(' ', $validator->errors()->all()));
                return false;
            }

            return true;
        } catch (\Exception $e) {
            session()->flash('mensagem_erro', 'Erro durante a validação: ' . $e->getMessage());
            return false;
        }
    }

    public function restore($id)
    {
        try {
            if (!$this->modelSupportsSoftDelete()) {
                throw new \Exception('Esta tabela não suporta recuperação de registros.');
            }

            $record = $this->model::withTrashed()
                ->where('empresa_id', $this->empresa_id)
                ->findOrFail($id);

            if (!valida_objeto($record)) {
                throw new \Exception('Você não tem permissão para restaurar este registro.');
            }

            // 🔹 Captura os dados antes da restauração para o log
            $dadosAnteriores = $record->getAttributes();

            // Evita duplicidade somente quando o model realmente usa a auditoria automática.
            if ($record instanceof BaseModel) {
                $this->disableNextModelAudit();
            }

            $record->restore();

            // 🔹 Certifica-se de que $this->model é uma instância válida antes de chamar get_class()
            $modelInstance = is_string($this->model) ? app($this->model) : $this->model;

            // 🔹 Registra o log da restauração
            $this->logService->registrar('restore', get_class($modelInstance), [
                'registro_id' => $id,
                'dados_antes' => $dadosAnteriores,
                'dados_depois' => $record->fresh()?->getAttributes() ?? $record->getAttributes(),
            ]);

            session()->flash('mensagem_sucesso', "O registro #{$id} foi restaurado com sucesso em: {$this->formTitle}");
        } catch (\Exception $e) {
            session()->flash('mensagem_erro', $e->getMessage());
        }

        return redirect($this->redirectPage);
    }

    public function search(Request $request)
    {
        $term = $request->get('term', '');
        $records = $this->getTenantRecords()
            ->when($term, function ($query, $term) {
                $query->where('name', 'like', "%{$term}%");
            })
            ->limit(10) // Limita a quantidade de resultados
            ->get(['id', 'name as text']); // Retorna no formato Select2
        return response()->json($records);
    }

    abstract protected function rules(): array;

    abstract protected function messages(): array;

    /**
     * Envia mensagens e arquivos via WhatsApp de forma genérica.
     *
     * @param Request $request Dados da requisição contendo o número de telefone e outros detalhes.
     * @param array $arquivos Lista de caminhos absolutos dos arquivos a serem enviados.
     * @param string $mensagem Mensagem a ser enviada junto com os arquivos (opcional).
     * @return array Retorna um array contendo o status da operação ('success' e 'message').
     */
    public function enviarWhatsApp(Request $request, array $arquivos, string $mensagem = '')
    {
        try {
            $numero = preg_replace('/[^0-9]/', '', $request->celular);
            // Define uma mensagem padrão caso nenhuma mensagem seja fornecida
            $mensagemPadrao = "Olá, segue os arquivos solicitados. Caso desconheça a origem, favor desconsiderar esta mensagem.";

            // Usa mensagem padrão se nenhuma mensagem foi fornecida e há arquivos
            if (empty($mensagem) && !empty($arquivos)) {
                $mensagem = $mensagemPadrao;
            }

            // Verifica se há pelo menos um arquivo ou uma mensagem
            if (empty($arquivos) && empty($mensagem)) {
                throw new \Exception('Nenhum arquivo ou mensagem foi fornecido para envio.');
            }

            // Converte os caminhos dos arquivos em URLs públicas
            $arquivosAsUrls = [];
            foreach ($arquivos as $file) {
                if (!file_exists($file)) {
                    throw new \Exception("Arquivo não encontrado: {$file}");
                }
                $arquivosAsUrls[] = asset(str_replace(public_path(), '', $file));
            }

            // Envia mensagem e arquivos
            $retorno = null;
            foreach ($arquivosAsUrls as $key => $fileUrl) {
                $texto = $key === 0 ? $mensagem : ''; // Envia mensagem apenas no primeiro arquivo
                $retorno = $this->whatsapputil->sendMessage($numero, $texto, $this->empresa_id, $fileUrl);
                $this->assertWhatsAppSendSucceeded($retorno);
            }

            // Caso não haja arquivos, envia apenas a mensagem
            if (empty($arquivos) && !empty($mensagem)) {
                $retorno = $this->whatsapputil->sendMessage($numero, $mensagem, $this->empresa_id);
                $this->assertWhatsAppSendSucceeded($retorno);
            }

            // 🔹 Registra o log do envio de WhatsApp
            $this->logService->registrar('whatsapp_send', 'WhatsApp', [
                'registro_id' => null,
                'dados_antes' => null,
                'dados_depois' => json_encode([
                    'empresa_id' => $this->empresa_id,
                    'usuario_id' => $this->usuario_id,
                    'numero' => $numero,
                    'mensagem' => $mensagem,
                    'arquivos' => $arquivosAsUrls,
                    'status' => 'sucesso'
                ], JSON_UNESCAPED_UNICODE),
            ]);

            // Remove arquivos temporários após o envio bem-sucedido
            $this->removerArquivosTemporarios($arquivos);

            session()->flash('mensagem_sucesso', 'Mensagem enviada com sucesso para o número ' . $numero);

            return [
                'success' => true,
                'message' => 'Mensagem enviada com sucesso para o número ' . $numero,
            ];
        } catch (\Exception $e) {
            \Log::error('Erro ao enviar WhatsApp', [
                'numero' => $numero ?? 'Indefinido',
                'arquivos' => $arquivos ?? [],
                'mensagem' => $mensagem,
                'erro' => $e->getMessage(),
            ]);

            // 🔹 Registra log da falha no envio
            $this->logService->registrar('whatsapp_error', 'WhatsApp', [
                'registro_id' => null,
                'dados_antes' => null,
                'dados_depois' => json_encode([
                    'empresa_id' => $this->empresa_id,
                    'usuario_id' => $this->usuario_id,
                    'numero' => $numero,
                    'mensagem' => $mensagem,
                    'arquivos' => $arquivosAsUrls ?? [],
                    'status' => 'erro',
                    'erro' => $e->getMessage()
                ], JSON_UNESCAPED_UNICODE),
            ]);

            // Tenta remover os arquivos temporários mesmo em caso de erro
            $this->removerArquivosTemporarios($arquivos);

            session()->flash('mensagem_erro', 'Erro ao enviar mensagem: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erro ao enviar mensagem: ' . $e->getMessage(),
            ];
        }
    }

    public function enviarWhatsApp_ok(Request $request, array $arquivos, string $mensagem = '')
    {
        try {
            $numero = preg_replace('/[^0-9]/', '', $request->celular);
            // Define uma mensagem padrão caso nenhuma mensagem seja fornecida
            $mensagemPadrao = "Olá, segue os arquivos solicitados. Caso desconheça a origem, favor desconsiderar esta mensagem.";

            // Usa mensagem padrão se nenhuma mensagem foi fornecida e há arquivos
            if (empty($mensagem) && !empty($arquivos)) {
                $mensagem = $mensagemPadrao;
            }

            // Verifica se há pelo menos um arquivo ou uma mensagem
            if (empty($arquivos) && empty($mensagem)) {
                throw new \Exception('Nenhum arquivo ou mensagem foi fornecido para envio.');
            }

            // Converte os caminhos dos arquivos em URLs públicas
            $arquivosAsUrls = array_map(function ($file) {
                if (!file_exists($file)) {
                    throw new \Exception("Arquivo não encontrado: {$file}");
                }
                return asset(str_replace(public_path(), '', $file)); // Gera a URL pública do arquivo
            }, $arquivos);

            // Envia mensagem e arquivos
            $retorno = null;
            foreach ($arquivosAsUrls as $key => $fileUrl) {
                $texto = $key === 0 ? $mensagem : ''; // Envia mensagem apenas no primeiro arquivo
                $retorno = $this->whatsapputil->sendMessage($numero, $texto, $this->empresa_id, $fileUrl);
                $this->assertWhatsAppSendSucceeded($retorno);
            }

            // Caso não haja arquivos, envia apenas a mensagem
            if (empty($arquivos) && !empty($mensagem)) {
                $retorno = $this->whatsapputil->sendMessage($numero, $mensagem, $this->empresa_id);
                $this->assertWhatsAppSendSucceeded($retorno);
            }

            // 🔹 Registra o log do envio de WhatsApp
            $this->logService->registrar('whatsapp_send', 'WhatsApp', [
                'registro_id' => null,
                'dados_antes' => null,
                'dados_depois' => [
                    'empresa_id' => $this->empresa_id,
                    'usuario_id' => $this->usuario_id,
                    'numero' => $numero,
                    'mensagem' => $mensagem,
                    'arquivos' => $arquivosAsUrls,
                    'status' => 'sucesso'
                ],
            ]);

            // Remove arquivos temporários após o envio bem-sucedido
            $this->removerArquivosTemporarios($arquivos);

            session()->flash('mensagem_sucesso', 'Mensagem enviada com sucesso para o número ' . $numero);

            return [
                'success' => true,
                'message' => 'Mensagem enviada com sucesso para o número ' . $numero,
            ];
        } catch (\Exception $e) {
            \Log::error('Erro ao enviar WhatsApp', [
                'numero' => $numero ?? 'Indefinido',
                'arquivos' => $arquivos ?? [],
                'mensagem' => $mensagem,
                'erro' => $e->getMessage(),
            ]);

            // 🔹 Registra log da falha no envio
            $this->logService->registrar('whatsapp_error', 'WhatsApp', [
                'registro_id' => null,
                'dados_antes' => null,
                'dados_depois' => [
                    'empresa_id' => $this->empresa_id,
                    'usuario_id' => $this->usuario_id,
                    'numero' => $numero,
                    'mensagem' => $mensagem,
                    'arquivos' => $arquivosAsUrls ?? [],
                    'status' => 'erro',
                    'erro' => $e->getMessage()
                ],
            ]);

            // Tenta remover os arquivos temporários mesmo em caso de erro
            $this->removerArquivosTemporarios($arquivos);

            session()->flash('mensagem_erro', 'Erro ao enviar mensagem: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erro ao enviar mensagem: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Remove arquivos temporários.
     *
     * @param array $arquivos Lista de caminhos absolutos dos arquivos a serem removidos.
     */
    protected function removerArquivosTemporarios(array $arquivos)
    {
        foreach ($arquivos as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Estado operacional do WhatsApp desta empresa na Connect|API.
     *
     * Não utiliza token manual/configuração legada. A disponibilidade depende
     * exclusivamente da instância local provisionada, não bloqueada e conectada.
     */
    protected function getConnectApiWhatsAppState(): array
    {
        $instance = ConnectApiInstance::query()
            ->where('empresa_id', $this->empresa_id)
            ->whereNull('deleted_at')
            ->first();

        $status = $instance?->connection_status ?: 'not_provisioned';
        $provisioned = (bool) ($instance && $instance->provisioned_at && $instance->instance_token);
        $ready = $provisioned
            && !$instance->is_blocked
            && $status === 'open';

        return [
            'instance' => $instance,
            'provisioned' => $provisioned,
            'ready' => $ready,
            'status' => $status,
        ];
    }

    /**
     * Converte o retorno JSON da fachada WhatsApp em falha de domínio quando a
     * Connect|API rejeita o envio. Evita falso positivo nos módulos legados.
     */
    protected function assertWhatsAppSendSucceeded(?string $response): void
    {
        $payload = json_decode((string) $response, true);

        if (!is_array($payload) || !($payload['success'] ?? false)) {
            $message = is_array($payload)
                ? ($payload['message'] ?? 'Falha ao enviar mensagem pela Connect|API.')
                : 'Resposta inválida recebida da Connect|API.';

            throw new \RuntimeException((string) $message);
        }
    }

    /**
     * Valida o código OTP para qualquer usuário (por padrão o logado via $this->usuario_id), opcionalmente em outro tenant.
     *
     * @param  Request     $request
     * @param  int|null    $usuarioId  Se quiser validar outro usuário, passe o ID; senão, assume $this->usuario_id
     * @param  mixed       $tenant     Se quiser mudar de tenant, passe aqui (ex: subdomínio ou chave)
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function validateOtp(Request $request, ?int $usuarioId = null): void
    {
        // 1) valida formato
        $request->validate([
            'code' => 'required|digits:6',
        ]);

        // 2) determina qual usuário usar (ou o logado)
        $usuarioId = $usuarioId ?: $this->usuario_id;

        /** @var Usuario $user */
        $user = Usuario::findOrFail($usuarioId);

        // 3) verifica se tem OTP cadastrado
        if (! $user->hasOtp()) {
            // grava log de usuário sem OTP
            $this->logService->registrar(
                'otp_missing',
                get_class($user),
                [
                    'usuario_id' => $usuarioId,
                    'mensagem'   => 'Usuário sem OTP cadastrado'
                ]
            );

            throw ValidationException::withMessages([
                'code' => ['Usuário sem OTP cadastrado.'],
            ]);
        }

        // 4) pega o segredo já DESCRIPTOGRAFADO pelo accessor
        $secret = $user->otp_secret;

        // 5) verifica a chave (com janela de tolerância de 1)
        $google2fa = app(Google2FA::class);
        $code      = $request->input('code');

        if (! $google2fa->verifyKey($secret, $code, 1)) {
            // grava log de tentativa inválida de código OTP
            $this->logService->registrar(
                'otp_invalid',
                get_class($user),
                [
                    'usuario_id'     => $usuarioId,
                    'attempted_code' => $code,
                    'mensagem'       => 'Código OTP inválido'
                ]
            );

            throw ValidationException::withMessages([
                'code' => ['Código OTP inválido.'],
            ]);
        }

        // 6) sucesso: grava log de validação OK
        $this->logService->registrar(
            'otp_valid',
            get_class($user),
            [
                'usuario_id' => $usuarioId,
                'mensagem'   => 'Código OTP validado com sucesso'
            ]
        );

        // retorna void e prossegue normalmente
    }

    protected function validateOtp_perfeito_sem_log(Request $request, ?int $usuarioId = null): void
    {
        // 1) valida formato
        $request->validate([
            'code' => 'required|digits:6',
        ]);

        // 2) determina qual usuário usar (ou o logado)
        $usuarioId = $usuarioId ?: $this->usuario_id;

        /** @var Usuario $user */
        $user = Usuario::findOrFail($usuarioId);

        if (! $user->hasOtp()) {
            throw ValidationException::withMessages([
                'code' => ['Usuário sem OTP cadastrado.'],
            ]);
        }

        // 3) pega o segredo já DESCRIPTOGRAFADO pelo accessor
        $secret = $user->otp_secret;

        // 4) verifica a chave (com janela de tolerância de 1)
        $google2fa = app(Google2FA::class);
        if (! $google2fa->verifyKey($secret, $request->input('code'), 1)) {
            throw ValidationException::withMessages([
                'code' => ['Código OTP inválido.'],
            ]);
        }
    }

    protected function disableNextModelAudit(): void
    {
        AuditContext::skipNextModelAudit();
    }

    protected function markManualAuditExecuted(): void
    {
        AuditContext::markManualAuditExecuted();
    }
}
