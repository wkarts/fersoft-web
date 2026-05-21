<?php

namespace App\Services\Pesagem;

use App\Models\AdpCamera;
use App\Models\ConfigNota;
use App\Models\PesagemTicketImagem;
use App\Models\TicketPesagem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PesagemTicketImagemService
{
    public function persistirDoTicket(TicketPesagem $ticket, mixed $snapshots): array
    {
        $items = $this->normalizarSnapshots($snapshots);
        $salvas = [];

        if (!$items) {
            return [];
        }

        // Reprocessamento seguro: se o ticket for editado ou salvo novamente,
        // remove imagens antigas antes de persistir as novas evidências.
        $this->excluirDoTicket($ticket, true, false);

        $config = ConfigNota::where('empresa_id', $ticket->empresa_id)->first();
        $storage = $this->resolverStorage($config);

        $ordem = 1;
        foreach ($items as $item) {
            $cameraUuid = (string) data_get($item, 'uuid', data_get($item, 'camera_uuid', data_get($item, 'response.snapshot.uuid', '')));
            $source = $this->extrairImagem($item);

            if (!$source['data'] && !$source['url']) {
                $salvas[] = [
                    'success' => false,
                    'camera_uuid' => $cameraUuid,
                    'message' => data_get($item, 'message', data_get($item, 'response.snapshot.message', 'Snapshot sem imagem.')),
                ];
                continue;
            }

            $arquivoPath = null;
            $arquivoUrl = $source['url'];
            $mime = $source['mime'] ?: 'image/jpeg';
            $bytes = null;
            $largura = null;
            $altura = null;

            if ($source['data']) {
                $extension = $this->extensionFromMime($mime);
                $dir = $this->buildRelativeDir($ticket, $storage['base_path']);
                $filename = Str::slug($cameraUuid ?: 'camera')
                    . '-' . now()->format('YmdHis')
                    . '-' . Str::lower(Str::random(8))
                    . '.' . $extension;
                $arquivoPath = $dir . '/' . $filename;
                $externalMetadata = null;

                if (($storage['disk'] ?? '') === 'tenant_external') {
                    $uploaded = $this->uploadArquivoExterno($storage, $arquivoPath, $source['data'], $mime);
                    $arquivoPath = $uploaded['path'] ?? $arquivoPath;
                    $arquivoUrl = $uploaded['url'] ?? null;
                    $externalMetadata = $uploaded;
                } else {
                    $this->putArquivo($storage['disk'], $arquivoPath, $source['data'], $storage['visibility']);
                    $arquivoUrl = $this->buildUrl($storage['disk'], $arquivoPath);
                }
                $bytes = strlen($source['data']);

                $imageInfo = @getimagesizefromstring($source['data']);
                if (is_array($imageInfo)) {
                    $largura = $imageInfo[0] ?? null;
                    $altura = $imageInfo[1] ?? null;
                    $mime = $imageInfo['mime'] ?? $mime;
                }
            }

            $camera = null;
            if ($cameraUuid !== '') {
                $camera = AdpCamera::where('empresa_id', $ticket->empresa_id)
                    ->where('camera_uuid', $cameraUuid)
                    ->first();
            }

            $metadata = $item;
            $metadata['storage'] = [
                'disk' => $storage['disk'],
                'base_path' => $storage['base_path'],
                'arquivo_path' => $arquivoPath,
                'arquivo_url' => $arquivoUrl,
                'source_kind' => $source['kind'] ?? null,
                'provider' => $storage['provider'] ?? null,
                'external' => $externalMetadata ?? null,
            ];

            $registro = PesagemTicketImagem::create([
                'empresa_id' => $ticket->empresa_id,
                'pesagem_id' => $ticket->pesagem_id,
                'ticket_pesagem_id' => $ticket->id,
                'balanca_config_id' => $ticket->balanca_config_id,
                'adp_camera_id' => $camera?->id,
                'camera_uuid' => $cameraUuid ?: null,
                'camera_descricao' => $camera?->descricao ?? data_get($item, 'camera_descricao', data_get($item, 'response.device.name')),
                'ordem' => $ordem++,
                'arquivo_path' => $arquivoPath,
                'thumb_path' => null,
                'arquivo_url' => $arquivoUrl,
                'mime_type' => $mime,
                'tamanho_bytes' => $bytes,
                'largura' => $largura,
                'altura' => $altura,
                'capturado_em' => data_get($item, 'captured_at') ?: data_get($item, 'capturado_em') ?: data_get($item, 'response.snapshot.captured_at') ?: now(),
                'metadata_json' => $metadata,
                'ativo' => true,
                'storage_disk' => $storage['disk'],
                'storage_base_path' => $storage['base_path'],
            ]);

            $salvas[] = [
                'success' => true,
                'id' => $registro->id,
                'camera_uuid' => $registro->camera_uuid,
                'arquivo_path' => $registro->arquivo_path,
                'arquivo_url' => $registro->imagem_url,
                'storage_disk' => $registro->storage_disk,
            ];
        }

        return $salvas;
    }

    public function excluirDoTicket(TicketPesagem $ticket, bool $apagarArquivos = true, bool $forceDelete = true): void
    {
        $query = PesagemTicketImagem::withTrashed()
            ->where('empresa_id', $ticket->empresa_id)
            ->where('ticket_pesagem_id', $ticket->id);

        foreach ($query->get() as $imagem) {
            if ($apagarArquivos && $imagem->arquivo_path) {
                $this->deleteArquivo($imagem->storage_disk ?: 'public_path', $imagem->arquivo_path);
            }

            if ($forceDelete && method_exists($imagem, 'forceDelete')) {
                $imagem->forceDelete();
            } else {
                $imagem->delete();
            }
        }
    }

    public function imagemSrcParaRelatorio(PesagemTicketImagem $imagem, bool $preferirEmbed = true): ?string
    {
        if (!$imagem->arquivo_path && !$imagem->arquivo_url) {
            return null;
        }

        $disk = $imagem->storage_disk ?: config('pesagem.snapshot_disk', 'public_path');
        $mime = $imagem->mime_type ?: 'image/jpeg';

        if ($preferirEmbed) {
            $localPath = null;
            if ($disk === 'public_path' && $imagem->arquivo_path) {
                $localPath = public_path(ltrim($imagem->arquivo_path, '/'));
            } elseif ($disk === 'public' && $imagem->arquivo_path) {
                $localPath = storage_path('app/public/' . ltrim($imagem->arquivo_path, '/'));
            }

            if ($localPath && is_file($localPath)) {
                $data = function_exists('safe_get_file_content') ? safe_get_file_content($localPath) : @file_get_contents($localPath);
                if ($data !== false && $data !== null && $data !== '') {
                    return 'data:' . $mime . ';base64,' . base64_encode($data);
                }
            }

            $external = $this->baixarConteudoExterno($imagem);
            if ($external['data']) {
                return 'data:' . ($external['mime'] ?: $mime) . ';base64,' . base64_encode($external['data']);
            }
        }

        return $imagem->imagem_url ?: $imagem->arquivo_url;
    }

    private function baixarConteudoExterno(PesagemTicketImagem $imagem): array
    {
        $metadata = is_array($imagem->metadata_json) ? $imagem->metadata_json : [];
        $provider = data_get($metadata, 'storage.provider');
        $external = data_get($metadata, 'storage.external', []);

        if (!in_array($provider, ['dropbox', 'onedrive', 'google_drive'], true)) {
            return ['data' => null, 'mime' => null];
        }

        $config = ConfigNota::where('empresa_id', $imagem->empresa_id)->first();
        $storageConfig = $config && method_exists($config, 'pesagemStorageConfig') ? $config->pesagemStorageConfig() : [];
        $token = trim((string) ($storageConfig['access_token'] ?? ''));
        if ($token === '') {
            return ['data' => null, 'mime' => null];
        }

        try {
            if ($provider === 'dropbox') {
                $path = data_get($external, 'path', $imagem->arquivo_path);
                $link = Http::withToken($token)->post('https://api.dropboxapi.com/2/files/get_temporary_link', ['path' => $path]);
                if ($link->successful() && $link->json('link')) {
                    $download = Http::get($link->json('link'));
                    return $download->successful() ? ['data' => $download->body(), 'mime' => $download->header('Content-Type')] : ['data' => null, 'mime' => null];
                }
            }

            if ($provider === 'onedrive') {
                $fileId = data_get($external, 'file_id');
                if ($fileId) {
                    $driveId = trim((string) ($storageConfig['drive_id'] ?? ''));
                    $url = $driveId !== ''
                        ? 'https://graph.microsoft.com/v1.0/drives/' . rawurlencode($driveId) . '/items/' . rawurlencode($fileId) . '/content'
                        : 'https://graph.microsoft.com/v1.0/me/drive/items/' . rawurlencode($fileId) . '/content';
                    $download = Http::withToken($token)->get($url);
                    return $download->successful() ? ['data' => $download->body(), 'mime' => $download->header('Content-Type')] : ['data' => null, 'mime' => null];
                }
            }

            if ($provider === 'google_drive') {
                $fileId = data_get($external, 'file_id', $imagem->arquivo_path);
                if ($fileId) {
                    $download = Http::withToken($token)->get('https://www.googleapis.com/drive/v3/files/' . rawurlencode($fileId), ['alt' => 'media']);
                    return $download->successful() ? ['data' => $download->body(), 'mime' => $download->header('Content-Type')] : ['data' => null, 'mime' => null];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Falha ao baixar imagem externa da pesagem para relatório.', [
                'imagem_id' => $imagem->id,
                'provider' => $provider,
                'message' => $e->getMessage(),
            ]);
        }

        return ['data' => null, 'mime' => null];
    }

    public function normalizarSnapshots(mixed $snapshots): array
    {
        if (is_string($snapshots)) {
            $decoded = json_decode($snapshots, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $snapshots = $decoded;
            }
        }

        if (!is_array($snapshots)) {
            return [];
        }

        if (array_is_list($snapshots)) {
            return array_values(array_filter($snapshots, 'is_array'));
        }

        if (isset($snapshots['cameras']) && is_array($snapshots['cameras'])) {
            return array_values(array_filter($snapshots['cameras'], 'is_array'));
        }

        return [$snapshots];
    }

    private function resolverStorage(?ConfigNota $config): array
    {
        $tenantStorage = $this->resolverStorageTenant($config);
        if ($tenantStorage !== null) {
            return $tenantStorage;
        }

        $disk = trim((string) ($config->pesagem_snapshot_disk ?? config('pesagem.snapshot_disk', 'public_path')));
        $basePath = trim((string) ($config->pesagem_snapshot_base_path ?? config('pesagem.snapshot_base_path', 'pesagem_ticket_imagens')));

        if ($disk === '') {
            $disk = 'public_path';
        }

        if ($basePath === '') {
            $basePath = 'pesagem_ticket_imagens';
        }

        $basePath = trim(str_replace('\\', '/', $basePath), '/');
        $visibility = config('pesagem.snapshot_visibility', 'public');

        return [
            'disk' => $disk,
            'base_path' => $basePath,
            'visibility' => $visibility,
            'dynamic' => false,
            'provider' => 'system',
        ];
    }

    private function resolverStorageTenant(?ConfigNota $config): ?array
    {
        if (!$config || !method_exists($config, 'pesagemStorageProvider')) {
            return null;
        }

        $provider = $config->pesagemStorageProvider();
        if ($provider === 'system') {
            return null;
        }

        $storageConfig = method_exists($config, 'pesagemStorageConfig')
            ? $config->pesagemStorageConfig()
            : ($config->pesagem_storage_config_json ?? []);

        if (!is_array($storageConfig)) {
            return null;
        }

        $basePath = trim(str_replace('\\', '/', (string) ($storageConfig['base_path'] ?? 'pesagem_ticket_imagens')), '/');
        if ($basePath === '') {
            $basePath = 'pesagem_ticket_imagens';
        }

        if (in_array($provider, ['s3', 'minio'], true)) {
            $bucket = trim((string) ($storageConfig['bucket'] ?? ''));
            $accessKey = trim((string) ($storageConfig['access_key'] ?? ''));
            $secretKey = trim((string) ($storageConfig['secret_key'] ?? ''));

            if ($bucket === '' || $accessKey === '' || $secretKey === '') {
                Log::warning('Storage próprio da pesagem incompleto; usando storage padrão da aplicação.', [
                    'empresa_id' => $config->empresa_id ?? null,
                    'provider' => $provider,
                ]);
                return null;
            }

            $diskName = 'tenant_pesagem_' . (int) ($config->empresa_id ?? 0) . '_' . $provider;
            config([
                'filesystems.disks.' . $diskName => [
                    'driver' => 's3',
                    'key' => $accessKey,
                    'secret' => $secretKey,
                    'region' => $storageConfig['region'] ?? 'us-east-1',
                    'bucket' => $bucket,
                    'endpoint' => $storageConfig['endpoint'] ?? null,
                    'url' => $storageConfig['url'] ?? null,
                    'use_path_style_endpoint' => (bool) ($storageConfig['use_path_style_endpoint'] ?? ($provider === 'minio')),
                    'throw' => false,
                ],
            ]);

            return [
                'disk' => $diskName,
                'base_path' => $basePath,
                'visibility' => 'public',
                'dynamic' => true,
                'provider' => $provider,
            ];
        }

        if (in_array($provider, ['dropbox', 'onedrive', 'google_drive'], true)) {
            $token = trim((string) ($storageConfig['access_token'] ?? ''));
            if ($token === '') {
                Log::warning('Storage próprio da pesagem sem token de acesso; usando storage padrão da aplicação.', [
                    'empresa_id' => $config->empresa_id ?? null,
                    'provider' => $provider,
                ]);
                return null;
            }

            return [
                'disk' => 'tenant_external',
                'base_path' => $basePath,
                'visibility' => 'public',
                'dynamic' => true,
                'provider' => $provider,
                'config' => $storageConfig,
            ];
        }

        Log::warning('Provider de storage próprio da pesagem configurado, mas sem driver ativo nesta instalação.', [
            'empresa_id' => $config->empresa_id ?? null,
            'provider' => $provider,
        ]);

        return null;
    }

    private function buildRelativeDir(TicketPesagem $ticket, string $basePath): string
    {
        return trim($basePath, '/') . '/'
            . (int) $ticket->empresa_id . '/'
            . (int) $ticket->pesagem_id . '/'
            . (int) $ticket->id;
    }

    private function uploadArquivoExterno(array $storage, string $path, string $data, string $mime): array
    {
        $provider = (string) ($storage['provider'] ?? '');
        $config = is_array($storage['config'] ?? null) ? $storage['config'] : [];
        $token = trim((string) ($config['access_token'] ?? ''));

        if ($token === '') {
            throw new \RuntimeException('Token do armazenamento externo não informado.');
        }

        return match ($provider) {
            'dropbox' => $this->uploadDropbox($token, $path, $data),
            'onedrive' => $this->uploadOneDrive($token, $config, $path, $data),
            'google_drive' => $this->uploadGoogleDrive($token, $config, $path, $data, $mime),
            default => throw new \RuntimeException('Provedor de armazenamento externo não suportado: ' . $provider),
        };
    }

    private function uploadDropbox(string $token, string $path, string $data): array
    {
        $dropboxPath = '/' . ltrim($path, '/');
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Dropbox-API-Arg' => json_encode([
                'path' => $dropboxPath,
                'mode' => 'overwrite',
                'autorename' => false,
                'mute' => true,
                'strict_conflict' => false,
            ], JSON_UNESCAPED_SLASHES),
            'Content-Type' => 'application/octet-stream',
        ])->withBody($data, 'application/octet-stream')
            ->post('https://content.dropboxapi.com/2/files/upload');

        if (!$response->successful()) {
            throw new \RuntimeException('Falha ao enviar imagem para Dropbox: ' . $response->body());
        }

        $linkResponse = Http::withToken($token)
            ->post('https://api.dropboxapi.com/2/files/get_temporary_link', ['path' => $dropboxPath]);

        return [
            'provider' => 'dropbox',
            'path' => $dropboxPath,
            'url' => $linkResponse->successful() ? ($linkResponse->json('link') ?: null) : null,
            'response' => $response->json(),
        ];
    }

    private function uploadOneDrive(string $token, array $config, string $path, string $data): array
    {
        $path = ltrim($path, '/');
        $driveId = trim((string) ($config['drive_id'] ?? ''));
        $baseUrl = $driveId !== ''
            ? 'https://graph.microsoft.com/v1.0/drives/' . rawurlencode($driveId) . '/root:/'
            : 'https://graph.microsoft.com/v1.0/me/drive/root:/';

        $response = Http::withToken($token)
            ->withBody($data, 'application/octet-stream')
            ->put($baseUrl . str_replace('%2F', '/', rawurlencode($path)) . ':/content');

        if (!$response->successful()) {
            throw new \RuntimeException('Falha ao enviar imagem para OneDrive: ' . $response->body());
        }

        return [
            'provider' => 'onedrive',
            'path' => $path,
            'url' => $response->json('@microsoft.graph.downloadUrl') ?: $response->json('webUrl'),
            'file_id' => $response->json('id'),
            'response' => $response->json(),
        ];
    }

    private function uploadGoogleDrive(string $token, array $config, string $path, string $data, string $mime): array
    {
        $name = basename($path);
        $folderId = trim((string) ($config['folder_id'] ?? ''));
        $metadata = ['name' => $name];
        if ($folderId !== '') {
            $metadata['parents'] = [$folderId];
        }

        $boundary = 'pesagem_' . Str::random(24);
        $body = "--{$boundary}\r\n"
            . "Content-Type: application/json; charset=UTF-8\r\n\r\n"
            . json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            . "\r\n--{$boundary}\r\n"
            . "Content-Type: {$mime}\r\n\r\n"
            . $data
            . "\r\n--{$boundary}--";

        $response = Http::withToken($token)
            ->withHeaders(['Content-Type' => 'multipart/related; boundary=' . $boundary])
            ->withBody($body, 'multipart/related; boundary=' . $boundary)
            ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id,name,webViewLink,webContentLink');

        if (!$response->successful()) {
            throw new \RuntimeException('Falha ao enviar imagem para Google Drive: ' . $response->body());
        }

        $fileId = (string) $response->json('id');
        if (($config['make_public'] ?? false) && $fileId !== '') {
            Http::withToken($token)->post('https://www.googleapis.com/drive/v3/files/' . rawurlencode($fileId) . '/permissions', [
                'role' => 'reader',
                'type' => 'anyone',
            ]);
        }

        return [
            'provider' => 'google_drive',
            'path' => $fileId ?: $path,
            'logical_path' => $path,
            'url' => $fileId !== '' ? 'https://drive.google.com/uc?export=view&id=' . rawurlencode($fileId) : null,
            'file_id' => $fileId,
            'response' => $response->json(),
        ];
    }

    private function putArquivo(string $disk, string $path, string $data, string $visibility = 'public'): void
    {
        if ($disk === 'public_path') {
            $fullPath = public_path($path);

            if (!function_exists('safe_file_put_contents')) {
                $directory = dirname($fullPath);
                if (!is_dir($directory)) {
                    @mkdir($directory, 0755, true);
                }
                $written = @file_put_contents($fullPath, $data);
            } else {
                $written = safe_file_put_contents($fullPath, $data);
            }

            if ($written === false) {
                Log::warning('Falha ao gravar imagem do ticket de pesagem', [
                    'disk' => $disk,
                    'path' => $path,
                    'full_path' => $fullPath,
                ]);
                throw new \RuntimeException('Não foi possível gravar a imagem do ticket de pesagem.');
            }

            return;
        }

        $options = [];
        if (in_array($disk, ['s3', 'minio'], true)) {
            $options['visibility'] = $visibility;
        }

        Storage::disk($disk)->put($path, $data, $options);
    }

    private function deleteArquivo(string $disk, string $path): void
    {
        try {
            if ($disk === 'public_path' || $disk === '') {
                $fullPath = public_path($path);
                if (is_file($fullPath)) {
                    @unlink($fullPath);
                }
                $this->removeEmptyParentDirs(dirname($fullPath), public_path('pesagem_ticket_imagens'));
                return;
            }

            Storage::disk($disk)->delete($path);
        } catch (\Throwable $e) {
            Log::warning('Falha ao remover imagem do ticket de pesagem', [
                'disk' => $disk,
                'path' => $path,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function removeEmptyParentDirs(string $directory, string $stopAt): void
    {
        $directory = rtrim(str_replace('\\', '/', $directory), '/');
        $stopAt = rtrim(str_replace('\\', '/', $stopAt), '/');

        while ($directory !== '' && $directory !== $stopAt && str_starts_with($directory, $stopAt)) {
            if (@is_dir($directory) && count(@scandir($directory) ?: []) <= 2) {
                @rmdir($directory);
                $directory = dirname($directory);
                continue;
            }
            break;
        }
    }

    private function buildUrl(string $disk, string $path): ?string
    {
        if ($disk === 'public_path') {
            return asset(ltrim($path, '/'));
        }

        if ($disk === 'public') {
            return asset('storage/' . ltrim($path, '/'));
        }

        try {
            return Storage::disk($disk)->url($path);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function extrairImagem(array $item): array
    {
        foreach ($this->collectImageCandidates($item) as $value) {
            if (!is_string($value) || trim($value) === '') {
                continue;
            }

            $value = trim($value);

            if (preg_match('/^https?:\/\//i', $value) || $this->looksLikeLocalPath($value)) {
                continue;
            }

            $parsed = $this->decodeBase64Image($value);
            if ($parsed['data']) {
                $parsed['kind'] = 'base64';
                return $parsed;
            }
        }

        foreach ($this->collectLocalPathCandidates($item) as $path) {
            $local = $this->readLocalFileCandidate($path);
            if ($local['data']) {
                $local['kind'] = 'local_file_path';
                return $local;
            }
        }

        foreach ($this->collectImageCandidates($item) as $value) {
            if (is_string($value) && preg_match('/^https?:\/\//i', trim($value))) {
                return [
                    'data' => null,
                    'mime' => null,
                    'url' => trim($value),
                    'kind' => 'url',
                ];
            }
        }

        return [
            'data' => null,
            'mime' => null,
            'url' => null,
            'kind' => null,
        ];
    }

    private function collectImageCandidates(mixed $value): array
    {
        $candidates = [];
        $preferredKeys = [
            'image_data_url', 'data_url', 'image_base64', 'base64', 'snapshot_base64',
            'file_base64', 'jpeg_base64', 'jpg_base64', 'png_base64', 'image',
            'image_url', 'snapshot_url', 'url', 'file_url', 'public_url', 'download_url',
        ];

        $walk = function ($node) use (&$walk, &$candidates, $preferredKeys) {
            if (is_string($node)) {
                $trim = trim($node);
                if ($trim !== '' && (
                    str_starts_with($trim, 'data:image') ||
                    preg_match('/^https?:\/\//i', $trim) ||
                    strlen($trim) > 200
                )) {
                    $candidates[] = $trim;
                }
                return;
            }

            if (!is_array($node)) {
                return;
            }

            foreach ($preferredKeys as $key) {
                if (array_key_exists($key, $node) && is_string($node[$key]) && trim($node[$key]) !== '') {
                    $candidates[] = trim($node[$key]);
                }
            }

            foreach ($node as $child) {
                if (is_array($child)) {
                    $walk($child);
                }
            }
        };

        $walk($value);

        return array_values(array_unique($candidates));
    }

    private function collectLocalPathCandidates(mixed $value): array
    {
        $candidates = [];
        $preferredKeys = [
            'file_path', 'local_path', 'path', 'snapshot_path', 'image_path',
            'response.snapshot.file_path', 'snapshot.file_path',
        ];

        $walk = function ($node) use (&$walk, &$candidates, $preferredKeys) {
            if (!is_array($node)) {
                return;
            }

            foreach ($node as $key => $child) {
                if (is_string($child)) {
                    $trim = trim($child);
                    if ($trim !== '' && (
                        in_array($key, ['file_path', 'local_path', 'path', 'snapshot_path', 'image_path'], true) ||
                        $this->looksLikeLocalPath($trim)
                    )) {
                        $candidates[] = $trim;
                    }
                } elseif (is_array($child)) {
                    $walk($child);
                }
            }
        };

        foreach ($preferredKeys as $path) {
            $valueFromPath = data_get($value, $path);
            if (is_string($valueFromPath) && trim($valueFromPath) !== '') {
                $candidates[] = trim($valueFromPath);
            }
        }

        $walk($value);

        return array_values(array_unique($candidates));
    }

    private function looksLikeLocalPath(string $value): bool
    {
        $value = trim($value);
        return (bool) (
            preg_match('~^[a-zA-Z]:[\\\\/]~', $value) ||
            str_starts_with($value, '/') ||
            str_starts_with($value, 'file://')
        );
    }

    private function readLocalFileCandidate(string $path): array
    {
        $path = trim($path);
        if ($path === '') {
            return ['data' => null, 'mime' => null, 'url' => null];
        }

        $path = preg_replace('/^file:\/\//i', '', $path) ?: $path;

        if (!is_file($path)) {
            return ['data' => null, 'mime' => null, 'url' => null];
        }

        $data = function_exists('safe_get_file_content')
            ? safe_get_file_content($path)
            : @file_get_contents($path);

        if ($data === false || $data === null || $data === '') {
            return ['data' => null, 'mime' => null, 'url' => null];
        }

        $mime = 'image/jpeg';
        $imageInfo = @getimagesizefromstring($data);
        if (is_array($imageInfo) && !empty($imageInfo['mime'])) {
            $mime = $imageInfo['mime'];
        }

        return ['data' => $data, 'mime' => $mime, 'url' => null];
    }

    private function decodeBase64Image(string $value): array
    {
        $value = trim($value);
        $mime = 'image/jpeg';

        if (preg_match('~^data:(image/[a-zA-Z0-9.+-]+);base64,(.*)$~s', $value, $matches)) {
            $mime = $matches[1];
            $value = $matches[2];
        }

        $value = preg_replace('/\s+/', '', $value);
        if ($value === '' || strlen($value) < 64) {
            return ['data' => null, 'mime' => null, 'url' => null];
        }

        $data = base64_decode($value, true);
        if ($data === false) {
            return ['data' => null, 'mime' => null, 'url' => null];
        }

        return ['data' => $data, 'mime' => $mime, 'url' => null];
    }

    private function extensionFromMime(string $mime): string
    {
        return match (strtolower($mime)) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'jpg',
        };
    }
}
