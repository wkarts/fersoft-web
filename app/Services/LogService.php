<?php

namespace App\Services;

use App\Models\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class LogService
{
    protected $empresaId;
    protected $usuarioId;
    protected $filialId;

    /**
     * Construtor que recebe a empresa e o usuário automaticamente.
     *
     * @param int|null $empresaId ID da empresa logada.
     * @param int|null $usuarioId ID do usuário logado.
     * @param int|null $filialId ID da filial logada.
 */
    public function __construct( $empresaId, $usuarioId, $filialId)
    {
        $this->empresaId = $empresaId;
        $this->usuarioId = $usuarioId;
        $this->filialId = $filialId;
    }

    /**
     * Registra um evento no log de atividades do sistema.
     *
     * @param string $acao Exemplo: create, update, delete, login, logout.
     * @param string|null $modelo Nome do Model afetado.
     * @param array $dados Informações do registro afetado (dados antes e depois).
     */
    public function registrar(string $acao, ?string $modelo, array $dados = [])
    {
        try {

            // 🔹 Impede gravação de log se empresa ou usuário estiverem ausentes
            /*
            if (empty($this->empresaId) || empty($this->usuarioId)) {
                return;
            }
            */
            // 🔹 Se `registro_id` não for passado, tenta capturar do próprio modelo
            $registroId = $dados['registro_id'] ?? null;

            // 🔹 Verifica se os dados já são JSON string e converte corretamente
            $dadosAntes = isset($dados['dados_antes']) ? $this->formatarJson($dados['dados_antes']) : null;
            $dadosDepois = isset($dados['dados_depois']) ? $this->formatarJson($dados['dados_depois']) : null;

            // 🔹 Garante que o token sempre seja único e não esteja duplicado
            $token = Str::uuid()->toString();

            // 🔹 Registra o log no banco de dados
            Log::create([
                'empresa_id' => in_array($this->empresaId, [null, 'null'], true) ? null : (int) $this->empresaId,
                'usuario_id' => in_array($this->usuarioId, [null, 'null'], true) ? null : (int) $this->usuarioId,
                'filial_id'  => in_array($this->filialId, [null, 'null', -1, '-1'], true) ? null : (int) $this->filialId,
                'acao' => $acao,
                'modelo' => $modelo,
                'registro_id' => $registroId,
                'dados_anteriores' => $dadosAntes,
                'dados_depois' => $dadosDepois,
                'ip_address' => Request::ip(),
                'user_agent' => Request::header('User-Agent'),
                'token' => $token, // 🔹 Garante que o token sempre seja único
            ]);

        } catch (\Exception $e) {
            \Log::error('Erro ao registrar log de atividade', ['erro' => $e->getMessage()]);
        }
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
}
