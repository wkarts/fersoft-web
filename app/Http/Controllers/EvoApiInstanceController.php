<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use App\Services\EvoApiService;
use App\Models\EvoApiInstance;
use App\Models\Empresa;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\Usuario;


class EvoApiInstanceController extends BaseController
{
    /** @var int|null **/
    protected $override_empresa_id;
    protected $isSuper;
    public function __construct()
    {
        parent::__construct();

        // Valores default (super-admin)
        $this->model         = EvoApiInstance::class;
        $this->formTitle     = 'Instância EvoAPI';
        $this->redirectPage  = 'evo-instances';
        $this->listView      = 'evo_instances.index';
        $this->registerView  = 'evo_instances.index';
        $this->Prefix_Route  = 'evo-instances.';
        $this->resource      = 'evo_instances';
        $this->table         = 'evo_api_instances';

        $this->middleware(function ($request, $next) {
            $user = session('user_logged', []);
            $this->isSuper = $user['super'] ?? false;

            // ───────── Normaliza filial_id ─────────
            // qualquer valor <= 0 vira null, para não quebrar a FK
            $this->filial_id = ($this->filial_id && $this->filial_id > 0)
                ? $this->filial_id
                : null;

            if (! $this->isSuper ) {
                // Tenant-only overrides
                $this->redirectPage = 'evoapi';                 // ajuste para sua rota
                $this->listView     = 'evo_instances.tenant';
                $this->registerView = 'evo_instances.tenant';
                $this->Prefix_Route  = 'evoapi.';
                //return redirect('/evoapi');// ajuste para sua rota
            }

            return $next($request);
        });

    }


    protected function rules(): array
    {
        return [
            'empresa_select' => 'required|exists:empresas,id',
            'name'    => 'required|string|max:100',
            'api_key' => 'required|string',
            'base_url'=> 'required|url',
            'ddi'     => 'required|string',
            'ddd'     => 'required|string',
            'version' => 'required|in:V1,V2',
        ];
    }

    protected function messages(): array
    {
        return [];
    }

    protected function defineFilters(Request $request): array
    {
        return [
            ['name'=>'name',    'type'=>'text',   'default'=>''],
            ['name'=>'version', 'type'=>'select', 'default'=>'', 'options'=>['V1'=>'V1','V2'=>'V2']],
        ];
    }

    /**
     * Agora sobrescrevemos para NÃO filtrar por empresa_id:
     */
    protected function getTenantRecords()
    {
        if (! $this->isSuper ) {
            $query = $this->model::where('empresa_id', $this->empresa_id);
            if (method_exists($this->model, 'bootSoftDeletes')) {
                $query = $query->withTrashed();
            }
            return $query;
        } else {
            return EvoApiInstance::with('empresa');
        }
    }

    /**
     * GET /evo-instances/tenant
     * Exibe a tela de tenant sem precisar de {id} na URL,
     * cria/recupera automaticamente a instância e exibe o grid.
     */
    public function tenantView(EvoApiService $service)
    {
        // tenta carregar uma instância existente
        $inst = EvoApiInstance::where('empresa_id', $this->empresa_id)->first();

        // se não existir, gera nome e token via generateCredentials()
        if (! $inst) {
            /** @var Empresa $empresa */
            $empresa = Empresa::findOrFail($this->empresa_id);

            // Gera name + api_key seguindo o mesmo padrão de credentials
            $creds = $service->generateCredentials($empresa);
            $name   = $creds['instance_name'];
            $apiKey = $creds['api_key'];

            // chama a EvoAPI para criar a instância
            $res = $service->fetchOrCreateInstance(
                $name,
                $apiKey,
                [],                                   // opções extras
                config('evoapi.base_url'),
                null
            );

            if ($res['success']) {
                $inst = EvoApiInstance::create([
                    'empresa_id'  => $this->empresa_id,
                    'usuario_id'  => $this->usuario_id,
                    //'filial_id'   => $this->filial_id > 0 ? $this->filial_id : null,
                    'filial_id'   => $this->filial_id,
                    'name'        => $name,
                    'api_key'     => $res['data']['hash']['apikey'] ?? $apiKey,
                    'instance_id' => $res['data']['instance']['instanceId'] ?? null,
                    'base_url'    => config('evoapi.base_url'),
                    'ddi'         => config('evoapi.ddi'),
                    'ddd'         => config('evoapi.ddd'),
                    'version'     => 'V1',
                ]);
                session()->flash('mensagem_sucesso','Instância criada automaticamente.');
            } else {
                session()->flash('mensagem_erro','Erro ao criar instância: '.$res['error']);
            }
        }

        // busca todas as instâncias desta empresa (normalmente só 1)
        $records = EvoApiInstance::with('empresa')
            ->where('empresa_id', $this->empresa_id)
            ->get();

        // envia para a view
        return view('evo_instances.tenant', [
            'data'    => $inst,
            'records' => $records,
            'title'   => $this->formTitle,
        ]);
    }

    /**
     * POST /evo-instances/create-api/{id}
     * Idem: fetchOrCreate antes de dar erro
     */
    public function createApiInstance(Request $request, EvoApiService $service)
    {
        $inst = EvoApiInstance::findOrFail($request->id);

        $res = $service->fetchOrCreateInstance(
            $inst->name,
            $inst->api_key,
            [],
            $inst->base_url,
            null
        );

        if ($res['success']) {
            $instance = $res['data']['instance'];
            $inst->instance_id = $instance['instanceId'] ?? $inst->instance_id;
            $inst->api_key     = $res['data']['hash']['apikey'] ?? $inst->api_key;
            $inst->save();
            session()->flash('mensagem_sucesso','Instância criada/recuperada com sucesso.');
        } else {
            session()->flash('mensagem_erro',$res['error']);
        }

        return redirect($this->redirectPage);
    }

    public function statusApiInstance($id, EvoApiService $service)
    {
        $inst = EvoApiInstance::findOrFail($id);
        $res  = $service->statusInstance(
            $inst->name,
            $inst->base_url,
            $inst->api_key
        );
        return response()->json($res);
    }

    /**
     * GET /evo-instances/qr/{id}
     * Gera o PNG do QR na hora, a partir da string bruta.
     */
    public function qrCode(int $id, EvoApiService $service)
    {
        $inst = EvoApiInstance::findOrFail($id);

        // chama a EvoAPI e obtém só a Base64 pura do PNG
        $res = $service->fetchQrString(
            $inst->name,
            $inst->base_url,
            $inst->api_key
        );

        if (! $res['success']) {
            return response()->json([
                'success' => false,
                'error'   => $res['error']
            ], 500);
        }

        return response()->json([
            'success'   => true,
            // esta é a string Base64 sem “data:image/…”
            'qr_base64' => $res['base64'],
        ]);
    }

    /**
     * GET /evo-instances/edit/{id}
     * Retorna JSON, não uma view
     */
    public function edit($id)
    {
        $inst = EvoApiInstance::findOrFail($id);
        // Se você precisa dos dados da empresa, ajuste aqui:
        $empresa = [
            'id'   => $inst->empresa_id,
            'text' => optional($inst->empresa)->nome_fantasia,
        ];

        return response()->json(array_merge(
            $inst->only(['id','name','api_key','base_url','ddi','ddd','version']),
            ['empresa' => $empresa]
        ));
    }

    /**
     * Salva a instância, mapeando `empresa_select` → `empresa_id`
     */
    public function save(Request $request)
    {
        // 1) validação
        $validated = $request->validate([
            'empresa_select' => 'required|exists:empresas,id',
            'name'           => 'required|string|max:100',
            'api_key'        => 'required|string',
            'base_url'       => 'required|url',
            'ddi'            => 'required|string',
            'ddd'            => 'required|string',
            'version'        => 'required|in:V1,V2',
        ]);

        // 2) monta os dados para o updateOrCreate
        $data = [
            'empresa_id' => $validated['empresa_select'],
            'usuario_id' => $this->usuario_id,
            'filial_id'  => $this->filial_id,
            'name'       => $validated['name'],
            'api_key'    => $validated['api_key'],
            'base_url'   => $validated['base_url'],
            'ddi'        => $validated['ddi'],
            'ddd'        => $validated['ddd'],
            'version'    => $validated['version'],
        ];

        // 2.5) verifica se há um registro soft-deleted com mesmos empresa_id e name e, se houver, restaura-o
        $trashed = EvoApiInstance::withTrashed()
            ->where('empresa_id', $data['empresa_id'])
            ->where('name',       $data['name'])
            ->first();
        if ($trashed && $trashed->trashed()) {
            $trashed->restore();
        }

        // 3) cria ou atualiza, tratando eventual duplicate-name
        try {
            $inst = EvoApiInstance::updateOrCreate(
                ['empresa_id' => $data['empresa_id'], 'name' => $data['name']],
                $data
            );
        } catch (QueryException $e) {
            // código 1062 = duplicate entry
            if (
                $e->errorInfo[1] === 1062
                && Str::contains($e->getMessage(), 'evo_api_instances_name_unique')
            ) {
                // já existe um registro com esse name em qualquer empresa: vamos atualizá-lo
                $inst = EvoApiInstance::where('name', $data['name'])->firstOrFail();
                $inst->fill($data);
                $inst->save();
            } else {
                throw $e;
            }
        }

        // 4) chama a EvoAPI para criar/recuperar remotamente
        $service = app(EvoApiService::class);
        $res = $service->fetchOrCreateInstance(
            $inst->name,
            $inst->api_key,
            [],
            $inst->base_url,
            null
        );

        if ($res['success']) {
            $inst->instance_id = $res['data']['instance']['instanceId'] ?? $inst->instance_id;
            $inst->api_key     = $res['data']['hash']['apikey']      ?? $inst->api_key;
            $inst->saveQuietly();
            session()->flash('mensagem_sucesso', 'Instância criada/recuperada com sucesso.');
        } else {
            session()->flash('mensagem_erro', 'Erro na EvoAPI: ' . $res['error']);
        }

        return redirect()->route('evo-instances.list');
    }

    public function save_(Request $request)
    {
        // 1) validação
        $validated = $request->validate([
            'empresa_select' => 'required|exists:empresas,id',
            'name'           => 'required|string|max:100',
            'api_key'        => 'required|string',
            'base_url'       => 'required|url',
            'ddi'            => 'required|string',
            'ddd'            => 'required|string',
            'version'        => 'required|in:V1,V2',
        ]);

        // 2) monta os dados para o updateOrCreate
        $data = [
            'empresa_id' => $validated['empresa_select'],
            'usuario_id' => $this->usuario_id,
            'filial_id'  => $this->filial_id,
            'name'       => $validated['name'],
            'api_key'    => $validated['api_key'],
            'base_url'   => $validated['base_url'],
            'ddi'        => $validated['ddi'],
            'ddd'        => $validated['ddd'],
            'version'    => $validated['version'],
        ];

        // 3) cria ou atualiza, tratando eventual duplicate-name
        try {
            $inst = EvoApiInstance::updateOrCreate(
                ['empresa_id' => $data['empresa_id'], 'name' => $data['name']],
                $data
            );
        } catch (QueryException $e) {
            // código 1062 = duplicate entry
            if (
                $e->errorInfo[1] === 1062
                && Str::contains($e->getMessage(), 'evo_api_instances_name_unique')
            ) {
                // já existe um registro com esse name em qualquer empresa: vamos atualizá-lo
                $inst = EvoApiInstance::where('name', $data['name'])->firstOrFail();
                $inst->fill($data);
                $inst->save();
            } else {
                throw $e;
            }
        }

        // 4) chama a EvoAPI para criar/recuperar remotamente
        $service = app(EvoApiService::class);
        $res = $service->fetchOrCreateInstance(
            $inst->name,
            $inst->api_key,
            [],
            $inst->base_url,
            null
        );

        if ($res['success']) {
            $inst->instance_id = $res['data']['instance']['instanceId'] ?? $inst->instance_id;
            $inst->api_key     = $res['data']['hash']['apikey']      ?? $inst->api_key;
            $inst->saveQuietly();
            session()->flash('mensagem_sucesso', 'Instância criada/recuperada com sucesso.');
        } else {
            session()->flash('mensagem_erro', 'Erro na EvoAPI: ' . $res['error']);
        }

        return redirect()->route('evo-instances.list');
    }

    /**
     * DELETE /evo-instances/delete/{id}
     */
    public function delete($id)
    {
        // busca direta pelo ID, sem filtro de empresa
        $inst = EvoApiInstance::findOrFail($id);
        $service = app(EvoApiService::class);

        if ($inst->instance_id) {
            // delete-remoto
            $service->deleteInstance($inst->name);
        }

        $inst->delete();
        session()->flash('mensagem_sucesso','Instância excluída local e remotamente.');
        return redirect()->route('evo-instances.list');
    }

    /**
     * POST /evo-instances/block/{id}
     * Bloqueia a instância e faz logout na EvoAPI.
     */
    public function block($id, EvoApiService $service)
    {
        $inst = EvoApiInstance::findOrFail($id);

        if ($inst->instance_id) {
            // usa logoutInstance via disconnectInstance()
            $service->disconnectInstance(
                $inst->name,
                $inst->base_url,
                $inst->api_key
            );
        }

        $inst->is_blocked = true;
        $inst->save();

        session()->flash('mensagem_sucesso', "Instância #{$inst->id} bloqueada e desconectada.");
        return redirect()->route('evo-instances.list');
    }

    public function unblock($id)
    {
        $inst = EvoApiInstance::findOrFail($id);
        $inst->is_blocked = false;
        $inst->save();
        session()->flash('mensagem_sucesso', "Instância #{$id} desbloqueada.");
        return redirect($this->redirectPage);
    }

    public function tenants(Request $request)
    {
        $term = $request->get('term', '');

        $rows = \DB::table('empresas')
            ->join('config_notas', 'empresas.id', '=', 'config_notas.empresa_id')
            ->join('cidades',     'config_notas.codMun', '=', 'cidades.codigo')
            ->where('empresas.status', 1)
            ->when($term, function($q) use ($term) {
                $q->where('empresas.nome',                     'like', "%{$term}%")
                    ->orWhere('empresas.nome_fantasia',           'like', "%{$term}%")
                    ->orWhere('empresas.cnpj',                    'like', "%{$term}%")
                    ->orWhere('empresas.cpf_representante_legal', 'like', "%{$term}%");
            })
            ->select([
                'empresas.id',
                'empresas.nome            AS razao_social',
                'empresas.nome_fantasia',
                'empresas.cnpj            AS raw_cnpj',
                'empresas.telefone',
                'cidades.nome             AS cidade',
                'cidades.codigo           AS codigo_municipio',
                'config_notas.logo        AS logo',
            ])
            ->limit(50)
            ->get();

        $results = $rows->map(function($e) {
            // formata CPF/CNPJ
            $digits = preg_replace('/\D/', '', $e->raw_cnpj);
            if (strlen($digits) === 11) {
                $cpf_cnpj = substr($digits, 0, 3) .'.'
                    . substr($digits, 3, 3) .'.'
                    . substr($digits, 6, 3) .'-'
                    . substr($digits, 9, 2);
            } else {
                $cpf_cnpj = substr($digits, 0, 2) .'.'
                    . substr($digits, 2, 3) .'.'
                    . substr($digits, 5, 3) .'/'
                    . substr($digits, 8, 4) .'-'
                    . substr($digits,12, 2);
            }

            // monta a URL do logo (ou retorna o "no_empresas.png" padrão)
            $logoFile = public_path('logos/' . $e->logo);
            if (! $e->logo || ! file_exists($logoFile)) {
                $logoUrl = asset('imgs/no_empresas.png');
            } else {
                $logoUrl = asset('logos/' . $e->logo);
            }

            return [
                'id'               => $e->id,
                'empresa_id'       => $e->id,
                'text'             => $e->nome_fantasia,
                'razao_social'     => $e->razao_social,
                'nome_fantasia'    => $e->nome_fantasia,
                'cpf_cnpj'         => $cpf_cnpj,
                'telefone'         => $e->telefone,
                'cidade'           => $e->cidade,
                'codigo_municipio' => $e->codigo_municipio,
                'imgApp'           => $logoUrl,
            ];
        });

        return response()->json($results->all());
    }

    /**
     * GET /evo-instances/credentials/{empresa}
     * Retorna JSON com nome e token gerados.
     */
    public function credentials(Empresa $empresa, EvoApiService $service)
    {
        $creds = $service->generateCredentials($empresa);
        return response()->json($creds);
    }

    public function credentialsTenant(int $id, EvoApiService $service): JsonResponse
    {
        // 1) busca local
        $inst = EvoApiInstance::findOrFail($id);
        $empresa = $inst->empresa;

        // 2) gera novo name+key
        $creds = $service->generateCredentials($empresa);
        $name  = $creds['instance_name'];
        $key   = $creds['api_key'];

        // 3) deleta a instância remota (se existir)
        $service->deleteInstance($inst->name, $inst->base_url, $inst->api_key);

        // 4) cria NOVA instância remota com o novo token
        $res = $service->createInstance(
            $name,
            $key,
            [],                // opções extras
            $inst->base_url,
            null
        );

        if (! $res['success']) {
            return response()->json([
                'success' => false,
                'error'   => $res['error']
            ], 500);
        }

        // 5) persiste localmente
        $inst->name        = $name;
        $inst->api_key     = $res['data']['hash']['apikey']     ?? $key;
        $inst->instance_id = $res['data']['instance']['instanceId'] ?? $inst->instance_id;
        $inst->save();

        // 6) devolve para o JS
        return response()->json([
            'success'       => true,
            'instance_name' => $inst->name,
            'api_key'       => $inst->api_key,
        ]);
    }

    /**
     * GET /evo-instances/tenant/{id?}
     * Exibe a tela de configuração do tenant.
     */
    public function tenant($id = null)
    {
        if ($id) {
            // carrega a instância específica
            $inst = EvoApiInstance::findOrFail($id);
        } else {
            // ou pega a primeira instância do tenant atual
            // ajuste aqui para filtrar por empresa_id ou usuário:
            $inst = EvoApiInstance::where('empresa_id', $this->empresa_id)
                ->first();
        }

        // a blade espera a variável $data, ou null
        return view(
         'evo_instances.tenant',
               ['data' => $inst],
               ['title' => $this->formTitle]
        );
    }

    /**
     * Envia WhatsApp: texto, arquivos ou Base64
     */
    public function sendWhatsApp_(
        int $id,
        Request $r,
        EvoApiService $service
    ): JsonResponse {
        $inst = EvoApiInstance::findOrFail($id);

        $number = $r->input('number');
        $mode   = $r->input('mode');
        $text   = $r->input('text', '');
        $files  = $r->input('files', []);    // array de ['name'=>..., 'data'=>Base64]
        $raws   = $r->input('raws', []);     // array de Base64 strings

        // 1) texto
        if ($mode === 'text' && $text) {
            $res = $service->sendText(
                $number,
                $text,
                $inst->name,
                $inst->base_url,
                $inst->api_key
            );
            if (! $res['success']) {
                return response()->json($res, 500);
            }
        }

        // 2) arquivos
        if ($mode === 'files') {
            foreach ($files as $f) {
                // novo – usa sendBase64 para Data-URL
                $res = $service->sendBase64(
                    $number,
                    $f['data'],      // dataURL ou raw Base64
                    $f['name'],      // nome do arquivo com extensão
                    'document',      // mediatype (ou 'image', 'audio' conforme quiser)
                    '',              // legenda
                    $inst->name,
                    $inst->base_url,
                    $inst->api_key
                );

                if (! $res['success']) {
                    return response()->json($res, 500);
                }
            }
        }

        // 3) Base64 manual
        if ($mode === 'base64') {
            foreach ($raws as $raw) {
                $res = $service->sendBase64(
                    $number,
                    $raw,
                    'file',          // você pode parametrizar filename se quiser
                    'document',
                    '',
                    $inst->name,
                    $inst->base_url,
                    $inst->api_key
                );
                if (! $res['success']) {
                    return response()->json($res, 500);
                }
            }
        }

        return response()->json(['success'=>true]);
    }

    /**
     * Envia WhatsApp: texto, arquivos ou Base64 enfileirados sequencialmente modal publica whastsapp evo
     */
    public function sendWhatsAppButton_(
        Request $r,
        EvoApiService $service
    ): JsonResponse {
        // 1) recupera o usuário logado (via BaseController)
        $usuario = Usuario::find($this->usuario_id);
        if (! $usuario) {
            abort(403, 'Usuário não autenticado.');
        }

        // 2) puxa o empresa_id
        $empresaId = $usuario->empresa_id;

        // 3) busca a instância na EvoAPI
        $inst = EvoApiInstance::where('empresa_id', $empresaId)
            ->firstOrFail();

        // 4) parâmetros do request
        $number  = $r->input('number');
        $mode    = $r->input('mode', 'mixed');       // text|files|base64|mixed
        $text    = $r->input('text', '');
        $caption = $r->input('caption', '');
        $files   = $r->input('files', []);           // [ ['name'=>..., 'data'=>...], ... ]
        $raws    = $r->input('raws', []);            // [ 'data:...base64...', ... ]

        // helper local para repetir as chamadas
        $doText = function() use ($service, $number, $text, $inst) {
            $res = $service->sendText(
                $number,
                $text,
                $inst->name,
                $inst->base_url,
                $inst->api_key
            );
            if (! $res['success']) {
                return response()->json($res, 500);
            }
            return null;
        };

        $doFile = function($f) use ($service, $number, $caption, $inst) {
            $res = $service->sendBase64(
                $number,
                $f['data'],
                $f['name'],
                'document',
                $caption,
                $inst->name,
                $inst->base_url,
                $inst->api_key
            );
            if (! $res['success']) {
                return response()->json($res, 500);
            }
            return null;
        };

        $doRaw = function($raw) use ($service, $number, $inst) {
            $res = $service->sendBase64(
                $number,
                $raw,
                'file',
                'document',
                '',
                $inst->name,
                $inst->base_url,
                $inst->api_key
            );
            if (! $res['success']) {
                return response()->json($res, 500);
            }
            return null;
        };

        // 5) dispatch conforme o modo
        if ($mode === 'mixed') {
            // primeiro texto
            if ($text) {
                if ($err = $doText()) {
                    return $err;
                }
            }
            // depois cada arquivo
            foreach ($files as $f) {
                if ($err = $doFile($f)) {
                    return $err;
                }
            }
            // e finalmente cada raw base64
            foreach ($raws as $raw) {
                if ($err = $doRaw($raw)) {
                    return $err;
                }
            }

        } elseif ($mode === 'text') {
            if ($text) {
                if ($err = $doText()) {
                    return $err;
                }
            }

        } elseif ($mode === 'files') {
            foreach ($files as $f) {
                if ($err = $doFile($f)) {
                    return $err;
                }
            }

        } elseif ($mode === 'base64') {
            foreach ($raws as $raw) {
                if ($err = $doRaw($raw)) {
                    return $err;
                }
            }
        }

        // 6) se chegou aqui, todos os envios deram sucesso
        return response()->json(['success' => true]);
    }

    /**
     * Envia WhatsApp: texto, arquivos ou Base64
     */
    public function sendWhatsApp(
        int $id,
        Request $r,
        EvoApiService $service
    ): JsonResponse {
        $inst = EvoApiInstance::findOrFail($id);

        // ──────────────── ➤ TRATAMENTO DE BLOQUEIO
        if ($inst->is_blocked) {
            return response()->json([
                'success' => false,
                'error'   => 'Esta instância está bloqueada. Entre em contato com o suporte para desbloquear.'
            ], 423);
        }

        $number = $r->input('number');
        $mode   = $r->input('mode');
        $text   = $r->input('text', '');
        $files  = $r->input('files', []);    // array de ['name'=>..., 'data'=>Base64]
        $raws   = $r->input('raws', []);     // array de Base64 strings

        // 1) texto
        if ($mode === 'text' && $text) {
            $res = $service->sendText(
                $number,
                $text,
                $inst->name,
                $inst->base_url,
                $inst->api_key
            );
            if (! $res['success']) {
                return response()->json($res, 500);
            }
        }

        // 2) arquivos
        if ($mode === 'files') {
            foreach ($files as $f) {
                // novo – usa sendBase64 para Data-URL
                $res = $service->sendBase64(
                    $number,
                    $f['data'],      // dataURL ou raw Base64
                    $f['name'],      // nome do arquivo com extensão
                    'document',      // mediatype (ou 'image', 'audio' conforme quiser)
                    '',              // legenda
                    $inst->name,
                    $inst->base_url,
                    $inst->api_key
                );

                if (! $res['success']) {
                    return response()->json($res, 500);
                }
            }
        }

        // 3) Base64 manual
        if ($mode === 'base64') {
            foreach ($raws as $raw) {
                $res = $service->sendBase64(
                    $number,
                    $raw,
                    'file',          // você pode parametrizar filename se quiser
                    'document',
                    '',
                    $inst->name,
                    $inst->base_url,
                    $inst->api_key
                );
                if (! $res['success']) {
                    return response()->json($res, 500);
                }
            }
        }

        return response()->json(['success'=>true]);
    }

    /**
     * Envia WhatsApp: texto, arquivos ou Base64 enfileirados sequencialmente
     */
    public function sendWhatsAppButton(
        Request $r,
        EvoApiService $service
    ): JsonResponse {
        // 1) recupera o usuário logado (via BaseController)
        $usuario = Usuario::find($this->usuario_id);
        if (! $usuario) {
            abort(403, 'Usuário não autenticado.');
        }

        // 2) puxa o empresa_id
        $empresaId = $usuario->empresa_id;

        // 3) busca a instância na EvoAPI
        $inst = EvoApiInstance::where('empresa_id', $empresaId)
            ->firstOrFail();

        // ──────────────── ➤ TRATAMENTO DE BLOQUEIO
        if ($inst->is_blocked) {
            return response()->json([
                'success' => false,
                'error'   => 'Esta instância está bloqueada. Entre em contato com o suporte para desbloquear.'
            ], 423);
        }

        // 4) parâmetros do request
        $number  = $r->input('number');
        $mode    = $r->input('mode', 'mixed');       // text|files|base64|mixed
        $text    = $r->input('text', '');
        $caption = $r->input('caption', '');
        $files   = $r->input('files', []);           // [ ['name'=>..., 'data'=>...], ... ]
        $raws    = $r->input('raws', []);            // [ 'data:...base64...', ... ]

        // helper local para repetir as chamadas
        $doText = function() use ($service, $number, $text, $inst) {
            $res = $service->sendText(
                $number,
                $text,
                $inst->name,
                $inst->base_url,
                $inst->api_key
            );
            if (! $res['success']) {
                return response()->json($res, 500);
            }
            return null;
        };

        $doFile = function($f) use ($service, $number, $caption, $inst) {
            $res = $service->sendBase64(
                $number,
                $f['data'],
                $f['name'],
                'document',
                $caption,
                $inst->name,
                $inst->base_url,
                $inst->api_key
            );
            if (! $res['success']) {
                return response()->json($res, 500);
            }
            return null;
        };

        $doRaw = function($raw) use ($service, $number, $inst) {
            $res = $service->sendBase64(
                $number,
                $raw,
                'file',
                'document',
                '',
                $inst->name,
                $inst->base_url,
                $inst->api_key
            );
            if (! $res['success']) {
                return response()->json($res, 500);
            }
            return null;
        };

        // 5) dispatch conforme o modo
        if ($mode === 'mixed') {
            // primeiro texto
            if ($text) {
                if ($err = $doText()) {
                    return $err;
                }
            }
            // depois cada arquivo
            foreach ($files as $f) {
                if ($err = $doFile($f)) {
                    return $err;
                }
            }
            // e finalmente cada raw base64
            foreach ($raws as $raw) {
                if ($err = $doRaw($raw)) {
                    return $err;
                }
            }

        } elseif ($mode === 'text') {
            if ($text) {
                if ($err = $doText()) {
                    return $err;
                }
            }

        } elseif ($mode === 'files') {
            foreach ($files as $f) {
                if ($err = $doFile($f)) {
                    return $err;
                }
            }

        } elseif ($mode === 'base64') {
            foreach ($raws as $raw) {
                if ($err = $doRaw($raw)) {
                    return $err;
                }
            }
        }

        // 6) se chegou aqui, todos os envios deram sucesso
        return response()->json(['success' => true]);
    }

}
