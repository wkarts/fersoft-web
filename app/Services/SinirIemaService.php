<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class SinirIemaService
{
    private string $baseUrl;
    private string $orgao;

    public function __construct(string $orgao = 'SINIR', string $ambiente = 'homologacao')
    {
        $this->orgao = strtoupper($orgao);

        if ($this->orgao === 'IEMA') {
            $this->baseUrl = $ambiente === 'producao'
                ? 'https://mtr.iema.es.gov.br/apiws/rest'
                : 'https://mtr-homologacao.iema.es.gov.br/apiws/rest';
        } else {
            $this->baseUrl = $ambiente === 'producao'
                ? 'https://admin.sinir.gov.br/api'
                : 'https://admin-homologacao.sinir.gov.br/api';
        }
    }

    public function getToken(
        string $cpfCnpj,
        string $senha,
        string $unidade = '1',
        ?string $cpfUsuario = null
    ): string {
        $senhaLimpa = trim($senha);

        if ($senhaLimpa === '') {
            throw new Exception('A credencial MTR não possui senha/token configurado.');
        }

        if (strlen($senhaLimpa) > 100) {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $senhaLimpa,
                'Accept' => 'application/json',
            ])
                ->connectTimeout(10)
                ->timeout(30)
                ->post($this->baseUrl . '/token');

            if ($response->failed()) {
                throw new Exception(
                    "Falha ao gerar Token de Acesso (HTTP {$response->status()}): "
                    . $this->safeBody($response->body())
                );
            }

            return $this->extractToken((array) $response->json());
        }

        $identificacao = preg_replace('/\D+/', '', $cpfUsuario ?: $cpfCnpj);

        $response = Http::acceptJson()
            ->asJson()
            ->connectTimeout(10)
            ->timeout(30)
            ->post($this->baseUrl . '/autenticar', [
                'cpfCnpj' => $identificacao,
                'senha' => $senhaLimpa,
                'unidade' => (int) $unidade,
            ]);

        if ($response->failed()) {
            throw new Exception(
                "Falha de autenticação no {$this->orgao} (HTTP {$response->status()}): "
                . $this->safeBody($response->body())
            );
        }

        return $this->extractToken((array) $response->json(), true);
    }

    public function transmitirManifesto(
        string $token,
        string $cpfCnpj,
        string $senha,
        string $unidade,
        array $payloadMtr
    ): array {
        $response = Http::withToken($token)
            ->acceptJson()
            ->asJson()
            ->connectTimeout(10)
            ->timeout(45)
            ->post($this->baseUrl . '/salvarManifestoLote', $payloadMtr);

        if ($response->failed()) {
            if ($response->status() === 401) {
                throw new Exception('Token de acesso expirado ou inválido para este CNPJ.');
            }

            throw new Exception(
                "Erro ao emitir MTR (HTTP {$response->status()}): "
                . $this->safeBody($response->body())
            );
        }

        return (array) $response->json();
    }

    public function getTratamentos(string $token): array
    {
        $response = Http::withToken($token)
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout(30)
            ->get($this->baseUrl . '/dominio/tratamento');

        if ($response->failed()) {
            throw new Exception(
                'Falha ao buscar tratamentos: ' . $this->safeBody($response->body())
            );
        }

        return (array) $response->json();
    }

    public function requestWithToken(string $token, string $method, string $path, array $payload = []): array
    {
        $request = Http::withToken($token)
            ->acceptJson()
            ->asJson()
            ->connectTimeout(10)
            ->timeout(45);

        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');

        $response = strtoupper($method) === 'GET'
            ? $request->get($url, $payload)
            : $request->send(strtoupper($method), $url, ['json' => $payload]);

        if ($response->failed()) {
            throw new Exception(
                "Falha na operação MTR (HTTP {$response->status()}): "
                . $this->safeBody($response->body())
            );
        }

        return (array) $response->json();
    }

    public function downloadWithToken(string $token, string $path): string
    {
        $response = Http::withToken($token)
            ->accept('*/*')
            ->connectTimeout(10)
            ->timeout(45)
            ->get(rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/'));

        if ($response->failed()) {
            throw new Exception(
                "Falha no download MTR (HTTP {$response->status()}): "
                . $this->safeBody($response->body())
            );
        }

        return $response->body();
    }

    private function extractToken(array $data, bool $allowConnectionOk = false): string
    {
        $token = $data['objetoResposta']
            ?? $data['access_token']
            ?? $data['token']
            ?? $data['api_key']
            ?? null;

        if (is_string($token) && trim($token) !== '') {
            return trim(str_replace('Bearer ', '', $token));
        }

        if ($allowConnectionOk && ($data['sucesso'] ?? false)) {
            return 'CONEXAO_OK';
        }

        throw new Exception(
            'Autenticação concluída, mas a API não retornou um token reconhecido.'
        );
    }

    private function safeBody(string $body): string
    {
        $text = preg_replace('/\s+/', ' ', strip_tags($body));
        return mb_substr(trim((string) $text), 0, 1200);
    }
}
