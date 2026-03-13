<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Utils\WhatsTokenGenerator;
use App\Models\Empresa;
use Illuminate\Support\Str;
use finfo;

class EvoApiService
{
    protected string $ddi;
    protected string $ddd;
    protected string $baseUrl;
    protected string $globalApiKey;
    protected string $version;
    protected ?string $qrLogoBase64;
    protected float $qrLogoSize;

    public function __construct()
    {
        $this->ddi           = config('evoapi.ddi');
        $this->ddd           = config('evoapi.ddd');
        $this->baseUrl       = (string) config('evoapi.base_url', '');
        $this->globalApiKey  = (string) config('evoapi.global_api', '');
        $this->version       = config('evoapi.version', 'V1');
        $this->qrLogoBase64  = config('evoapi.qr_logo_base64');
        $this->qrLogoSize    = (float) config('evoapi.qr_logo_size', 0.2);
    }

    // -----------------
    //  Helpers internos
    // -----------------

    protected function getBaseUrl(?string $overrideUrl): string
    {
        return $overrideUrl ?: $this->baseUrl;
    }

    protected function getApiKey(bool $useInstanceKey, ?string $overrideKey): string
    {
        if ($overrideKey) {
            return $overrideKey;
        }
        return $useInstanceKey
            ? Cache::get('evoapi.instance_api_key', $this->globalApiKey)
            : $this->globalApiKey;
    }

    protected function client(string $url, bool $useInstanceKey = false, ?string $overrideKey = null)
    {
        return Http::withHeaders([
            'apikey' => $this->getApiKey($useInstanceKey, $overrideKey),
            'Accept' => 'application/json',
        ])->baseUrl($url);
    }

    /**
     * Normaliza um número de telefone para o formato DDI + DDD + número.
     *
     * @param  string  $number  Qualquer texto contendo o telefone.
     * @return string
     */
    protected function formatNumber(string $number): string
    {
        // remove tudo que não é dígito
        $digits = preg_replace('/\D+/', '', $number);

        $ddi = $this->ddi; // ex: "55"
        $ddd = $this->ddd; // ex: "11"
        $len = strlen($digits);

        // 1) já veio com DDI (começa com o código do país)
        if (substr($digits, 0, strlen($ddi)) === $ddi) {
            return $digits;
        }

        // 2) só número local (8 ou 9 dígitos): adiciona DDD + DDI
        if ($len === 8 || $len === 9) {
            return $ddi . $ddd . $digits;
        }

        // 3) veio com DDD + número (10 ou 11 dígitos): adiciona só o DDI
        if ($len === (strlen($ddd) + 8) || $len === (strlen($ddd) + 9)) {
            return $ddi . $digits;
        }

        // 4) fallback: adiciona apenas o DDI
        return $ddi . $digits;
    }

    protected function formatNumber_(string $number): string
    {
        // remove tudo que não é dígito
        $digits = preg_replace('/\D+/', '', $number);

        // se o usuário já incluiu DDI (ex: começa com "55"), retorna como veio
        if (str_starts_with($digits, $this->ddi)) {
            return $digits;
        }

        $len = strlen($digits);

        // apenas número local (8 ou 9 dígitos): adiciona DDI + DDD padrão
        if ($len === 8 || $len === 9) {
            return "{$this->ddi}{$this->ddd}{$digits}";
        }

        // inclui DDD (10 ou 11 dígitos): adiciona só DDI
        if ($len === 10 || $len === 11) {
            return "{$this->ddi}{$digits}";
        }

        // qualquer outro caso (ex: já veio com DDI ou formato inesperado), retorna puro
        return $digits;
    }

    protected function detectFileType(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match($ext) {
            'jpg','jpeg','png','gif' => 'image',
            'mp3','wav','ogg'     => 'audio',
            default               => 'document',
        };
    }

    protected function storeInstanceData(string $name, string $apiKey, string $id): void
    {
        Cache::forever('evoapi.instance_name',       $name);
        Cache::forever('evoapi.instance_api_key',    $apiKey);
        Cache::forever('evoapi.instance_id',         $id);
    }

    // -----------------
    //  Instâncias
    // -----------------

    public function fetchInstance(string $name, ?string $overrideUrl = null, ?string $overrideGlobalKey = null): array
    {
        $url = $this->getBaseUrl($overrideUrl);
        $endpoint = "/instance/fetchInstances?instanceName={$name}";
        $res = $this->client($url, false, $overrideGlobalKey)
            ->get($endpoint);

        if ($res->successful() && isset($res['instance'])) {
            $inst = $res['instance'];
            $this->storeInstanceData(
                $inst['instanceName'] ?? $name,
                $inst['apikey']       ?? Cache::get('evoapi.instance_api_key'),
                $inst['instanceId']   ?? ''
            );
            return ['success'=>true, 'data'=>$inst];
        }
        return ['success'=>false, 'error'=>"HTTP {$res->status()}: {$res->body()}"];
    }

    public function fetchAllInstances(?string $overrideUrl = null, ?string $overrideGlobalKey = null): array
    {
        $url = $this->getBaseUrl($overrideUrl);
        $res = $this->client($url, false, $overrideGlobalKey)
            ->get('/instance/fetchInstances');

        return $res->successful()
            ? ['success'=>true,'data'=>$res->json()]
            : ['success'=>false,'error'=>"HTTP {$res->status()}: {$res->body()}"];
    }

    public function createInstance(
        string $name,
        ?string $tokenApi = null,
        array  $options  = [],
        ?string $overrideUrl = null,
        ?string $overrideGlobalKey = null
    ): array {
        $url = $this->getBaseUrl($overrideUrl);
        $payload = array_merge([
            'instanceName'    => $name,
            'token'           => $tokenApi ?: Cache::get('evoapi.instance_api_key', $this->globalApiKey),
            'integration'     => 'WHATSAPP-BAILEYS',
            'qrcode'          => true,
            'reject_call'     => false,
            'groupsIgnore'    => true,
            'alwaysOnline'    => false,
            'readMessages'    => false,
            'readStatus'      => false,
            'syncFullHistory' => false,
        ], $options);

        $res = $this->client($url, false, $overrideGlobalKey)
            ->post('/instance/create', $payload);

        if (in_array($res->status(), [200,201])) {
            $json     = $res->json();
            $inst     = $json['instance'] ?? [];
            $apiKey   = $json['hash']['apikey'] ?? ($json['hash'] ?? '');
            $id       = $inst['instanceId'] ?? '';
            $this->storeInstanceData($inst['instanceName'] ?? $name, $apiKey, $id);
            return ['success'=>true,'data'=>$json];
        }
        if ($res->status() === 409 || str_contains($res->body(), 'already exists')) {
            return ['success'=>false,'error'=>'Instância já existe'];
        }
        return ['success'=>false,'error'=>"HTTP {$res->status()}: {$res->body()}"];
    }

    public function deleteInstance(
        ?string $name = null,
        ?string $overrideUrl = null,
        ?string $overrideGlobalKey = null
    ): array {
        $name = $name ?: Cache::get('evoapi.instance_name');
        $url  = $this->getBaseUrl($overrideUrl);
        $res  = $this->client($url, false, $overrideGlobalKey)
            ->delete("/instance/delete/{$name}");

        if ($res->successful()) {
            Cache::forget('evoapi.instance_name');
            Cache::forget('evoapi.instance_api_key');
            Cache::forget('evoapi.instance_id');
            return ['success'=>true];
        }
        return ['success'=>false,'error'=>"HTTP {$res->status()}: {$res->body()}"];
    }

    public function logoutInstance(
        ?string $name = null,
        ?string $overrideUrl = null,
        ?string $overrideGlobalKey = null
    ): array {
        $name = $name ?: Cache::get('evoapi.instance_name');
        $url  = $this->getBaseUrl($overrideUrl);
        $res  = $this->client($url, false, $overrideGlobalKey)
            ->delete("/instance/logout/{$name}");

        return $res->successful()
            ? ['success'=>true]
            : ['success'=>false,'error'=>"HTTP {$res->status()}: {$res->body()}"];
    }

    public function restartInstance(
        ?string $name = null,
        ?string $overrideUrl = null,
        ?string $overrideGlobalKey = null
    ): array {
        $name = $name ?: Cache::get('evoapi.instance_name');
        $url  = $this->getBaseUrl($overrideUrl);
        $method = $this->version === 'V1' ? 'put' : 'post';
        $res = $this->client($url, false, $overrideGlobalKey)
            ->{$method}("/instance/restart/{$name}");

        return $res->successful()
            ? ['success'=>true]
            : ['success'=>false,'error'=>"HTTP {$res->status()}: {$res->body()}"];
    }

    public function statusInstance(
        ?string $name = null,
        ?string $overrideUrl = null,
        ?string $overrideApiKey = null
    ): array {
        $name = $name ?: Cache::get('evoapi.instance_name');
        $url  = $this->getBaseUrl($overrideUrl);
        $res  = $this->client($url, true, $overrideApiKey)
            ->get("/instance/connectionState/{$name}");

        if ($res->successful() && isset($res['instance'])) {
            return ['success'=>true,'data'=>$res['instance']];
        }

        return ['success'=>false,'error'=>"HTTP {$res->status()}: {$res->body()}"];
    }

    public function updateInstanceSettings(
        bool   $rejectCall,
        bool   $groupsIgnore,
        bool   $alwaysOnline,
        bool   $readMessages,
        bool   $readStatus,
        bool   $syncFullHistory,
        string $msgCall,
        ?string $name = null,
        ?string $overrideUrl = null,
        ?string $overrideGlobalKey = null
    ): array {
        $name = $name ?: Cache::get('evoapi.instance_name');
        $url  = $this->getBaseUrl($overrideUrl);
        $payload = compact(
                'rejectCall','groupsIgnore','alwaysOnline',
                'readMessages','readStatus','syncFullHistory'
            ) + ['msg_call'=>$msgCall];

        $res = $this->client($url, false, $overrideGlobalKey)
            ->post("/settings/set/{$name}", $payload);

        return $res->successful()
            ? ['success'=>true]
            : ['success'=>false,'error'=>"HTTP {$res->status()}: {$res->body()}"];
    }

    // -----------------
    //  Mensagens
    // -----------------

    public function sendText(
        string $number,
        string $message,
        ?string $name = null,
        ?string $overrideUrl = null,
        ?string $overrideApiKey = null
    ): array {
        $name = $name ?: Cache::get('evoapi.instance_name');
        $url  = $this->getBaseUrl($overrideUrl);
        $body = [
            'number'      => $this->formatNumber($number),
            'textMessage' => ['text'=>$message],
        ];
        if ($this->version==='V1') {
            $body['options']=['delay'=>1200,'presence'=>'composing'];
        }
        $res = $this->client($url, true, $overrideApiKey)
            ->post("/message/sendText/{$name}", $body);

        if (in_array($res->status(), [200,201])) {
            return ['success'=>true,'messageId'=> data_get($res->json(),'key.id')];
        }
        return ['success'=>false,'error'=>"HTTP {$res->status()}: {$res->body()}"];
    }

    /**
     * Detecta MIME type a partir de:
     * 1) Data URL ("data:...;base64,...")
     * 2) Caminho de arquivo físico
     * 3) Raw Base64 + extensão do nome (fallback)
     */
    protected function detectMime(string $input, ?string $fileName = null): string
    {
        // 1) data URL
        if (preg_match('#^data:(.+?);base64,#', trim($input), $m)) {
            return $m[1];
        }

        // 2) arquivo físico
        if (file_exists($input)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $input);
            finfo_close($finfo);
            return $mime ?: 'application/octet-stream';
        }

        // 3) raw Base64 → inferência por extensão
        if ($fileName) {
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            return match($ext) {
                // imagens
                'jpg','jpeg' => 'image/jpeg',
                'png'        => 'image/png',
                'gif'        => 'image/gif',
                'bmp'        => 'image/bmp',
                'webp'       => 'image/webp',
                'svg'        => 'image/svg+xml',

                // áudio
                'mp3'        => 'audio/mpeg',
                'wav'        => 'audio/wav',
                'ogg'        => 'audio/ogg',
                'm4a'        => 'audio/mp4',

                // vídeo
                'mp4'        => 'video/mp4',
                'mov'        => 'video/quicktime',
                'avi'        => 'video/x-msvideo',
                'mkv'        => 'video/x-matroska',
                'webm'       => 'video/webm',

                // documentos
                'pdf'        => 'application/pdf',
                'doc'        => 'application/msword',
                'docx'       => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'xls'        => 'application/vnd.ms-excel',
                'xlsx'       => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'ppt'        => 'application/vnd.ms-powerpoint',
                'pptx'       => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'txt'        => 'text/plain',
                'csv'        => 'text/csv',
                'json'       => 'application/json',
                'zip'        => 'application/zip',
                'rar'        => 'application/vnd.rar',
                '7z'         => 'application/x-7z-compressed',

                // XML
                'xml'  => 'application/xml',
                'xsd'  => 'application/xml',
                'xsl'  => 'application/xml',
                'rss'  => 'application/rss+xml',
                'atom' => 'application/atom+xml',

                // fallback
                default      => 'application/octet-stream',
            };
        }

        return 'application/octet-stream';
    }

    /**
     * A partir de um MIME type, determina o mediatype:
     * - começa com "image/" → "image"
     * - "audio/" → "audio"
     * - "video/" → "video"
     * - tudo o mais → "document"
     */
    protected function detectMediaType(string $mimeType): string
    {
        $prefix = strtolower(explode('/', $mimeType)[0]);
        return in_array($prefix, ['image','audio','video'])
            ? $prefix
            : 'document';
    }

    /**
     * Envia arquivo físico: não importa o $caption nem $overrideApiKey,
     * o serviço vai extrair name, mime e mediatype sozinho.
     */
    public function sendMedia(
        string $number,
        string $filePath,
        string $caption   = '',
        ?string $instName = null,
        ?string $overrideUrl    = null,
        ?string $overrideApiKey = null
    ): array {
        $instName = $instName ?: Cache::get('evoapi.instance_name');
        $basename = basename($filePath);                            // ex: foto.png
        $filename = pathinfo($basename, PATHINFO_FILENAME);         // ex: foto
        $caption  = $caption ?: $filename;                          // sem extensão
        $content  = safe_file_get_contents($filePath) ?: '';
        $mimeType = $this->detectMime($filePath);                   // usa helper
        $mediaType= $this->detectMediaType($mimeType);              // image/audio/video/document

        $body = [
            'number'       => $this->formatNumber($number),
            'mediaMessage' => [
                'mediatype' => $mediaType,
                'fileName'  => $basename,
                'mimetype'  => $mimeType,
                'caption'   => $caption,
                'media'     => base64_encode($content),
            ],
        ];
        if ($this->version==='V1') {
            $body['options'] = ['delay'=>1200,'presence'=>'composing'];
        }

        $res = $this->client($this->getBaseUrl($overrideUrl), true, $overrideApiKey)
            ->post("/message/sendMedia/{$instName}", $body);

        return in_array($res->status(), [200,201])
            ? ['success'=>true,'messageId'=> data_get($res->json(),'key.id')]
            : ['success'=>false,'error'=>"HTTP {$res->status()}: {$res->body()}"];
    }

    /**
     * Envia Base64 (Data URL ou raw) para WhatsApp,
     * detectando automaticamente:
     *  - MIME type
     *  - mediaType (image | audio | video | document)
     *  - fileName (com extensão correta)
     *  - caption (nome sem extensão)
     *
     * @param string      $number         Número formato E.164
     * @param string      $base64         Data URL ou raw Base64
     * @param string      $fileName       Nome do arquivo com extensão ou 'file'
     * @param string      $dummyType      Ignorado (manter compatibilidade)
     * @param string      $caption        Legenda opcional
     * @param string|null $instName       Nome da instância EvoAPI
     * @param string|null $overrideUrl    URL alternativa da API
     * @param string|null $overrideApiKey Chave de API alternativa
     * @return array ['success'=>bool,'messageId'=>string] ou ['success'=>false,'error'=>string]
     */
    public function sendBase64(
        string  $number,
        string  $base64,
        string  $fileName,
        string  $dummyType      = 'document',
        ?string $caption        = null,
        ?string $instName       = null,
        ?string $overrideUrl    = null,
        ?string $overrideApiKey = null
    ): array {
        // 1) Definir instância
        $instNameLocal = $instName ?: Cache::get('evoapi.instance_name');

        // 2) Detectar MIME e mediaType
        $mimeType  = $this->detectMime($base64, $fileName);
        $mediaType = $this->detectMediaType($mimeType);

        // 3) Se vier como 'file' ou vazio, gerar fileName = mediaType + extensão
        if (empty($fileName) || strtolower($fileName) === 'file') {
            // pega o subtipo após '/'
            $sub   = explode('/', $mimeType, 2)[1] ?? '';
            // separa por '+' ou ';'
            $parts = preg_split('/[+;]/', $sub);
            $ext   = strtolower($parts[0] ?? '');
            if ($ext === 'jpeg') {
                $ext = 'jpg';
            }
            $fileName = "{$mediaType}.{$ext}";
        }

        // 4) Gerar caption = nome sem extensão
        $baseName     = pathinfo($fileName, PATHINFO_FILENAME);
        $captionLocal = $caption ?: $baseName;

        // 5) Extrair Base64 puro
        if (preg_match('/^data:[^;]+;base64,(.+)$/', trim($base64), $m)) {
            $b64 = $m[1];
        } else {
            $b64 = preg_replace('/[^A-Za-z0-9+\/=]/', '', $base64);
        }

        // 6) Montar payload
        $body = [
            'number'       => $this->formatNumber($number),
            'mediaMessage' => [
                'mediatype' => $mediaType,
                'fileName'  => $fileName,
                'mimetype'  => $mimeType,
                'caption'   => $captionLocal,
                'media'     => $b64,
            ],
        ];

        if ($this->version === 'V1') {
            $body['options'] = ['delay' => 1200, 'presence' => 'composing'];
        }

        // 7) Enviar
        $res = $this->client(
            $this->getBaseUrl($overrideUrl),
            true,
            $overrideApiKey
        )->post("/message/sendMedia/{$instNameLocal}", $body);

        // 8) Retornar resultado
        if (in_array($res->status(), [200, 201])) {
            return [
                'success'   => true,
                'messageId' => data_get($res->json(), 'key.id'),
            ];
        }

        return [
            'success' => false,
            'error'   => "HTTP {$res->status()}: {$res->body()}",
        ];
    }

    public function sendList(
        string $number,
        string $title,
        string $description,
        string $buttonText,
        string $footerText,
        array  $sections,
        ?string $name = null,
        ?string $overrideUrl = null,
        ?string $overrideApiKey = null
    ): array {
        $name = $name ?: Cache::get('evoapi.instance_name');
        $url  = $this->getBaseUrl($overrideUrl);
        $body = compact('title','description','buttonText','footerText','sections')
            + ['number'=>$this->formatNumber($number)];
        if ($this->version==='V1') {
            $body['options']     = ['delay'=>1200,'presence'=>'composing'];
            $body['listMessage'] = $body;
        }
        $res = $this->client($url, true, $overrideApiKey)
            ->post("/message/sendList/{$name}", $body);

        return $res->status()===201
            ? ['success'=>true]
            : ['success'=>false,'error'=>"HTTP {$res->status()}: {$res->body()}"];
    }

    public function sendButtons(
        string $number,
        string $title,
        string $description,
        string $thumbnailUrl,
        string $footer,
        array  $buttons,
        ?string $name = null,
        ?string $overrideUrl = null,
        ?string $overrideApiKey = null
    ): array {
        $name = $name ?: Cache::get('evoapi.instance_name');
        $url  = $this->getBaseUrl($overrideUrl);
        $body = [
            'number'       => $this->formatNumber($number),
            'title'        => $title,
            'description'  => $description,
            'thumbnailUrl' => $thumbnailUrl,
            'footer'       => $footer,
            'buttons'      => $buttons,
            'options'      => ['delay'=>1200],
        ];
        $res = $this->client($url, true, $overrideApiKey)
            ->post("/message/sendButtons/{$name}", $body);

        return $res->status()===201
            ? ['success'=>true]
            : ['success'=>false,'error'=>"HTTP {$res->status()}: {$res->body()}"];
    }

    public function sendLocation(
        string $number,
        string $nameLoc,
        string $address,
        float  $latitude,
        float  $longitude,
        ?string $instName = null,
        ?string $overrideUrl = null,
        ?string $overrideApiKey = null
    ): array {
        $instName = $instName ?: Cache::get('evoapi.instance_name');
        $url      = $this->getBaseUrl($overrideUrl);
        $body     = [
            'number'    => $this->formatNumber($number),
            'name'      => $nameLoc,
            'address'   => $address,
            'latitude'  => $latitude,
            'longitude' => $longitude,
        ];
        if ($this->version==='V1') {
            $body['options']=['delay'=>1200,'presence'=>'composing'];
        }
        $res = $this->client($url, true, $overrideApiKey)
            ->post("/message/sendLocation/{$instName}", $body);

        if ($res->successful()) {
            return ['success'=>true,'messageId'=> data_get($res->json(),'key.id')];
        }
        return ['success'=>false,'error'=>"HTTP {$res->status()}: {$res->body()}"];
    }

    public function makeCall(
        string $number,
        int    $duration,
        ?string $instName      = null,
        ?string $overrideUrl   = null,
        ?string $overrideApiKey= null
    ): array {
        if ($this->version!=='V2') {
            return ['success'=>false,'error'=>'Chamadas disponíveis só em V2'];
        }
        $instName = $instName ?: Cache::get('evoapi.instance_name');
        $url      = $this->getBaseUrl($overrideUrl);
        $body     = ['number'=>$this->formatNumber($number),'callDuration'=>$duration];
        $res      = $this->client($url, true, $overrideApiKey)
            ->post("/call/offer/{$instName}", $body);

        return $res->successful()
            ? ['success'=>true,'callId'=>data_get($res->json(),'id')]
            : ['success'=>false,'error'=>"HTTP {$res->status()}: {$res->body()}"];
    }

    public function statusMessage(
        string $msgId,
        string $number,
        ?string $instName      = null,
        ?string $overrideUrl   = null,
        ?string $overrideApiKey= null
    ): array {
        $instName = $instName ?: Cache::get('evoapi.instance_name');
        $url      = $this->getBaseUrl($overrideUrl);
        $body     = [
            'where'  => [
                'remoteJid' => $this->formatNumber($number).'@s.whatsapp.net',
                'id'        => $msgId
            ],
            'page'=>1,'offset'=>10
        ];
        $res = $this->client($url, true, $overrideApiKey)
            ->post("/chat/findStatusMessage/{$instName}", $body);

        if ($res->successful()) {
            $arr  = $res->json();
            $last = end($arr);
            return ['success'=>true,'status'=>$last['status']??null];
        }
        return ['success'=>false,'error'=>"HTTP {$res->status()}: {$res->body()}"];
    }

    public function checkWhatsAppNumbers(
        string $numbers,
        ?string $instName      = null,
        ?string $overrideUrl   = null,
        ?string $overrideApiKey= null
    ): array {
        $instName = $instName ?: Cache::get('evoapi.instance_name');
        $url      = $this->getBaseUrl($overrideUrl);
        $list     = array_map(fn($n)=> $this->formatNumber(trim($n)), explode(',', $numbers));
        $body     = ['numbers'=>$list];

        $res = $this->client($url, true, $overrideApiKey)
            ->post("/chat/whatsappNumbers/{$instName}", $body);

        return $res->successful()
            ? ['success'=>true,'data'=>$res->json()]
            : ['success'=>false,'error'=>"HTTP {$res->status()}: {$res->body()}"];
    }

    public function fetchContacts(
        ?string $instName      = null,
        ?string $overrideUrl   = null,
        ?string $overrideApiKey= null
    ): array {
        $instName = $instName ?: Cache::get('evoapi.instance_name');
        $url      = $this->getBaseUrl($overrideUrl);
        $res = $this->client($url, true, $overrideApiKey)
            ->post("/chat/findContacts/{$instName}", ['where'=>new \stdClass()]);

        return $res->successful()
            ? ['success'=>true,'data'=>$res->json()]
            : ['success'=>false,'error'=>"HTTP {$res->status()}: {$res->body()}"];
    }

    public function fetchGroups(
        bool   $participants=false,
        ?string $instName       = null,
        ?string $overrideUrl    = null,
        ?string $overrideApiKey = null
    ): array {
        $instName = $instName ?: Cache::get('evoapi.instance_name');
        $url      = $this->getBaseUrl($overrideUrl);
        $q        = $participants ? 'true':'false';
        $res = $this->client($url, true, $overrideApiKey)
            ->get("/group/fetchAllGroups/{$instName}?getParticipants={$q}");

        return $res->successful()
            ? ['success'=>true,'data'=>$res->json()]
            : ['success'=>false,'error'=>"HTTP {$res->status()}: {$res->body()}"];
    }

    public function getGroupParticipants(
        string $groupJid,
        ?string $instName       = null,
        ?string $overrideUrl    = null,
        ?string $overrideApiKey = null
    ): array {
        $instName = $instName ?: Cache::get('evoapi.instance_name');
        $url      = $this->getBaseUrl($overrideUrl);
        $res = $this->client($url, true, $overrideApiKey)
            ->get("/group/participants/{$instName}?groupJid={$groupJid}");

        return $res->successful()
            ? ['success'=>true,'data'=>$res->json()['participants'] ?? []]
            : ['success'=>false,'error'=>"HTTP {$res->status()}: {$res->body()}"];
    }

    public function getContactDetail(
        string $contactId,
        bool   $saveToDisk    = false,
        ?string $diskPath     = null,
        ?string $instName     = null,
        ?string $overrideUrl  = null,
        ?string $overrideApiKey= null
    ): array {
        $instName = $instName ?: Cache::get('evoapi.instance_name');
        $url      = $this->getBaseUrl($overrideUrl);
        $body     = ['number'=>$this->formatNumber($contactId).'@s.whatsapp.net'];
        $res = $this->client($url, true, $overrideApiKey)
            ->withHeaders(['User-Agent'=>'Laravel'])
            ->post("/chat/fetchProfile/{$instName}", $body);

        if ($res->successful()) {
            $j = $res->json();
            if ($saveToDisk && $diskPath && isset($j['profilePictureUrl'])) {
                file_put_contents($diskPath, Http::get($j['profilePictureUrl'])->body());
                return ['success'=>true,'path'=>$diskPath,'data'=>$j];
            }
            return ['success'=>true,'data'=>$j];
        }
        return ['success'=>false,'error'=>"HTTP {$res->status()}: {$res->body()}"];
    }

    public function callTypebot(
        string $remoteJid,
        string $typebotName,
        array  $variables,
        bool   $startSession = false,
        ?string $instName     = null,
        ?string $overrideUrl  = null,
        ?string $overrideApiKey= null
    ): array {
        $instName = $instName ?: Cache::get('evoapi.instance_name');
        $url      = $this->getBaseUrl($overrideUrl);
        $data     = [
            'url'          => $url,
            'typebot'      => $typebotName,
            'remoteJid'    => $this->formatNumber($remoteJid).'@s.whatsapp.net',
            'startSession' => $startSession,
            'variables'    => array_map(fn($kv)=>['name'=>$kv[0],'value'=>$kv[1]], $variables),
        ];
        $res = $this->client($url, true, $overrideApiKey)
            ->post("/typebot/start/{$instName}", $data);

        return $res->successful()
            ? ['success'=>true,'data'=>$res->body()]
            : ['success'=>false,'error'=>"HTTP {$res->status()}: {$res->body()}"];
    }

    public function getServerVersion(?string $overrideUrl = null, ?string $overrideApiKey = null): array
    {
        $url = $this->getBaseUrl($overrideUrl);
        $res = $this->client($url, false, $overrideApiKey)->get('/');
        if ($res->successful() && isset($res['version'])) {
            return ['success'=>true,'version'=>$res['version']];
        }
        return ['success'=>false,'error'=>"HTTP {$res->status()}: {$res->body()}"];
    }

    // -----------------
    //  QR Code
    // -----------------
    // App/Services/EvoApiService.php
    public function generateQrCode(
        int    $size              = 300,
        ?string $instName         = null,
        ?string $overrideUrl      = null,
        ?string $overrideApiKey   = null
    ): array {
        $instName = $instName ?: Cache::get('evoapi.instance_name');
        $url      = $this->getBaseUrl($overrideUrl);
        $res      = $this->client($url, true, $overrideApiKey)
            ->get("/instance/connect/{$instName}");

        if (! $res->successful()) {
            return ['success'=>false,'error'=>"HTTP {$res->status()}: {$res->body()}"];
        }
        $apiB64 = $res->json()['base64'] ?? null;
        if (! $apiB64) {
            return ['success'=>false,'error'=>'Campo base64 ausente'];
        }

        // gera QR com simple-qrcode + merge de logo
        $builder = QrCode::format('png')
            ->size($size)
            ->margin(1);

        if ($this->qrLogoBase64) {
            // cria arquivo temporário do logo
            $tmp = tempnam(sys_get_temp_dir(), 'qr_logo_').'.png';
            file_put_contents($tmp, base64_decode($this->qrLogoBase64));
            $builder->merge($tmp, $this->qrLogoSize, true);
        }

        $png = $builder->generate($apiB64);

        // limpa temp
        if (isset($tmp)) {
            @unlink($tmp);
        }

        return ['success'=>true,'qr_base64'=>base64_encode($png)];
    }

    /**
     * Gera o nome e o token para uma empresa.
     */
    public function generateCredentials(Empresa $empresa): array
    {
        $cnpjClean = preg_replace('/\D+/', '', $empresa->cnpj);

        // usa razão social (campo nome) em UPPER_SNAKE
        $razao = \Str::of($empresa->nome)
            ->ascii()
            ->upper()
            ->replaceMatches('/\W+/', '_')
            ->trim('_')
            ->__toString();

        $instanceName = "{$cnpjClean}-{$razao}";
        $token        = WhatsTokenGenerator::generateSecretKey();

        return [
            'instance_name' => $instanceName,
            'api_key'       => $token,
        ];
    }

    /**
     * Busca na EvoAPI apenas o payload (base64) do QR code.
     * Não gera imagem nenhuma — só devolve a string.
     *
     * @param  string|null  $instName
     * @param  string|null  $overrideUrl
     * @param  string|null  $overrideApiKey
     * @return string       A string base64 bruta para gerar o QR
     * @throws \RuntimeException em caso de erro HTTP ou ausência de campo
     */
    public function fetchQrString(string $instName, string $overrideUrl, string $overrideApiKey): array
    {
        $url = $this->getBaseUrl($overrideUrl);
        $res = $this->client($url, true, $overrideApiKey)
            ->get("/instance/connect/{$instName}");

        if (! $res->successful()) {
            return ['success'=>false,'error'=>"HTTP {$res->status()}: {$res->body()}"];
        }

        $b64 = $res->json()['base64'] ?? null;
        if (! $b64) {
            return ['success'=>false,'error'=>'Campo base64 ausente'];
        }

        return ['success'=>true,'base64'=>$b64];
    }

    /**
     * Cria ou busca instância na EvoAPI.
     *
     * @return array ['success'=>bool, 'data'=>... or 'error'=>string]
     */
    public function fetchOrCreateInstance(
        string $name,
        string $tokenApi,
        array  $options  = [],
        ?string $overrideUrl     = null,
        ?string $overrideGlobalKey = null
    ): array {
        // 1) tenta buscar
        $url = $this->getBaseUrl($overrideUrl);
        $fetch = $this->client($url, false, $overrideGlobalKey)
            ->get("/instance/fetchInstances?instanceName={$name}");
        if ($fetch->successful() && isset($fetch['instance'])) {
            return [
                'success' => true,
                'data'    => [
                    'instance' => $fetch['instance'],
                    'hash'     => ['apikey'=> $fetch['instance']['apikey'] ?? $tokenApi]
                ]
            ];
        }
        // 2) se não existir, criar
        return $this->createInstance($name, $tokenApi, $options, $overrideUrl, $overrideGlobalKey);
    }

    /**
     * Alias para logoutInstance(), compatibilizando chamadas a disconnectInstance().
     */
    public function disconnectInstance(
        ?string $name = null,
        ?string $overrideUrl = null,
        ?string $overrideGlobalKey = null
    ): array {
        return $this->logoutInstance($name, $overrideUrl, $overrideGlobalKey);
    }
}
