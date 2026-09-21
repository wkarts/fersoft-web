<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Exception;

class SinirIemaService
{
    private string $baseUrl;
    private string $orgao;

    public function __construct(string $orgao = 'SINIR', string $ambiente = 'homologacao')
    {
        $this->orgao = $orgao;

        if ($orgao === 'IEMA') {
            $this->baseUrl = ($ambiente === 'producao')
                ? 'https://mtr.iema.es.gov.br/apiws/rest'
                : 'https://mtr-homologacao.iema.es.gov.br/apiws/rest';
        } else {
            // Padrão do Novo MTR Nacional / SINIR
            $this->baseUrl = ($ambiente === 'producao')
                ? 'https://admin.sinir.gov.br/api'
                : 'https://admin-homologacao.sinir.gov.br/api';
        }
    }

    /**
     * Autenticação conforme documentação Swagger do SINIR
     */
    /**
     * Retorna o token para autenticação
     */
    /**
     * Retorna o token para autenticação
     */
    /**
     * Retorna o token para autenticação
     */
    public function getToken(string $cpfCnpj, string $senha, string $unidade = '1'): string
    {
        $senhaLimpa = trim($senha);

        // Se a senha for o Token Gigante (Token de Integração)
        if (strlen($senhaLimpa) > 100) {
            
            $endpointToken = "{$this->baseUrl}/token";
            
            $responseToken = Http::withHeaders([
                'Authorization' => 'Bearer ' . $senhaLimpa,
                'Accept'        => 'application/json',
            ])->post($endpointToken);

            if ($responseToken->successful()) {
                $dadosToken = $responseToken->json();
                
                // O Swagger do governo indica que o token curto vem dentro de "objetoResposta"
                if (isset($dadosToken['objetoResposta'])) {
                    // Removemos a palavra "Bearer " caso a API já a envie junta, para não duplicar depois
                    return trim(str_replace('Bearer ', '', $dadosToken['objetoResposta']));
                }
                
                if (isset($dadosToken['access_token'])) {
                    return $dadosToken['access_token'];
                }
                
                if (isset($dadosToken['token'])) {
                    return $dadosToken['token'];
                }
                
                throw new Exception("Autenticado com sucesso, mas o formato da resposta do SINIR mudou. Resposta: " . json_encode($dadosToken));
            }

            throw new Exception("Falha ao gerar Token de Acesso de 8h (Status {$responseToken->status()}): " . strip_tags($responseToken->body()));
        }

        // =========================================================
        // LEGADO (Para o IEMA ou quem ainda usa utilizador/senha curtos)
        // =========================================================
        $endpoint = "{$this->baseUrl}/autenticar";
        
        $config = DB::table('mtr_configs')->where('cpf_cnpj', preg_replace('/\D/', '', $cpfCnpj))->first();
        $identificacaoLogin = ($config && !empty($config->cpf_usuario)) 
            ? preg_replace('/\D/', '', $config->cpf_usuario) 
            : preg_replace('/\D/', '', $cpfCnpj);

        $payload = [
            'cpfCnpj' => $identificacaoLogin,
            'senha'   => $senhaLimpa,
            'unidade' => (int)$unidade,
        ];

        $responseAuth = Http::withHeaders([
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($endpoint, $payload);

        if ($responseAuth->failed()) {
            throw new Exception("Falha de Autenticação Legada: " . strip_tags($responseAuth->body()));
        }

        $dadosAuth = $responseAuth->json();
        
        if (isset($dadosAuth['objetoResposta'])) {
            return trim(str_replace('Bearer ', '', $dadosAuth['objetoResposta']));
        }
        
        return $dadosAuth['token'] ?? $dadosAuth['api_key'] ?? "CONEXAO_OK";
    }
  
    /**
     * Transmissão do Lote de Manifestos
     */
    public function transmitirManifesto(string $token, string $cpfCnpj, string $senha, string $unidade, array $payloadMtr): array
    {
        $endpoint = "{$this->baseUrl}/salvarManifestoLote";

        // PASSO 2: Envia o MTR usando o Token de Acesso que pegamos lá em cima
        $response = Http::withToken($token)
            ->withHeaders([
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($endpoint, $payloadMtr);

        if ($response->failed()) {
            $bodyErro = $response->body();

            if ($response->status() == 401) {
                throw new Exception("Erro 401 (Acesso Não Autorizado): O Token de Acesso expirou ou é inválido para este CNPJ.");
            }

            if (str_contains(strtolower($bodyErro), '<html') || str_contains(strtolower($bodyErro), '<body')) {
                throw new Exception("Erro 400 do SINIR. O payload recusado foi: " . json_encode($payloadMtr));
            }

            throw new Exception("Erro ao emitir MTR (Status {$response->status()}): " . strip_tags($bodyErro));
        }

        return $response->json();
    }
  
  /**
     * Busca os tratamentos válidos direto da API do SINIR
     */
    public function getTratamentos(string $token): array
    {
        $response = Http::withToken($token)
            ->withHeaders(['Accept' => 'application/json'])
            ->get("{$this->baseUrl}/dominio/tratamento"); // Endpoint padrão do SINIR para listar tratamentos

        if ($response->successful()) {
            return $response->json();
        }

        throw new Exception("Falha ao buscar domínios de tratamento do SINIR: " . $response->body());
    }
}