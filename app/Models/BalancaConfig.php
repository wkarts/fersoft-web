<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Helpers\EncryptionHelper;

class BalancaConfig extends BaseModel
{
    use HasFactory, SoftDeletes;

    // Nome da tabela
    protected $table = 'balanca_configs';

    // Campos preenchíveis
    protected $fillable = [
        'empresa_id',
        'usuario_id',
        'descricao',
        'modelo',
        'marca',
        'port',
        'velocidade',
        'bits',
        'paridade',
        'bits_stop',
        'cabecalho',
        'rodape',
        'timeout',
        'serie_number',
        'ativo',
        'data_install',
        'observacoes',
        'tipo_balanca',
        'tipo',
        'token',
        'backend_server_address',
        'integrador',
        'driver',
        'porta_serial',
        'baud_rate',
        'adp_scale_uuid',
        'connection_token',
        'connection_token_enabled',
        'connection_token_type',
        'connection_token_header',
        'timeout_ms',
        'read_interval_ms',
        'usa_cameras',
        'quantidade_cameras',
        'adp_camera_uuids',
        'captura_snapshot_automatica',
        'snapshot_retorno_base64',
        'snapshot_baixa_visibilidade',
        'exigir_peso_estavel',
    ];

    // Casting de tipos
    protected $casts = [
        'ativo' => 'boolean', // Converte ativo para booleano
        'data_install' => 'datetime:Y-m-d', // Formata a data de instalação
        'connection_token' => 'encrypted',
        'connection_token_enabled' => 'boolean',
        'usa_cameras' => 'boolean',
        'captura_snapshot_automatica' => 'boolean',
        'snapshot_retorno_base64' => 'boolean',
        'snapshot_baixa_visibilidade' => 'boolean',
        'exigir_peso_estavel' => 'boolean',
    ];

    /**
     * Relacionamento com a empresa.
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Relacionamento com o usuário.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    /**
     * Retorna o status formatado.
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->ativo ? 'Ativo' : 'Inativo';
    }

    /**
     * Formata a data de instalação para exibição.
     */
    public function getDataInstallFormatadaAttribute(): ?string
    {
        return $this->data_install ? $this->data_install->format('d/m/Y') : null;
    }

    /**
     * Retorna o tipo da balança formatado.
     */
    public function getTipoLabelAttribute(): string
    {
        $tipos = [
            'analitica' => 'Analítica',
            'animais' => 'Animais',
            'animais_suspensa' => 'Animais Suspensa',
            'antropometrica' => 'Antropométrica',
            'bancada' => 'Bancada',
            'cadeira_rodas' => 'Cadeira de Rodas',
            'checkout' => 'Checkout',
            'contadora' => 'Contadora',
            'digital_inteligente' => 'Digital Inteligente',
            'dosadora' => 'Dosadora',
            'etiquetadora' => 'Etiquetadora',
            'ferroviaria' => 'Ferroviária',
            'gancho' => 'Gancho',
            'graos' => 'Grãos',
            'grua' => 'Grua',
            'hospitalar' => 'Hospitalar',
            'imc' => 'IMC',
            'joias' => 'Joias',
            'mercado' => 'Mercado',
            'microbalanca' => 'Microbalança',
            'palete' => 'Palete',
            'pediatrica' => 'Pediátrica',
            'plataforma' => 'Plataforma',
            'piso' => 'Piso',
            'portatil' => 'Portátil',
            'postal' => 'Postal',
            'precisao' => 'Precisão',
            'preco_calculado' => 'Preço Calculado',
            'rodoviaria' => 'Rodoviária',
            'tanque' => 'Tanque',
        ];

        return $tipos[$this->tipo] ?? 'Desconhecido';
    }

    /**
     * Define o tipo formatado ao salvar.
     */
    public function setTipoAttribute($value): void
    {
        $this->attributes['tipo'] = strtolower($value);
    }

    /**
     * Retorna os tipos de balança disponíveis.
     */
    public static function tipos(): array
    {
        return [
            'analitica' => 'Analítica',
            'animais' => 'Animais',
            'animais_suspensa' => 'Animais Suspensa',
            'antropometrica' => 'Antropométrica',
            'bancada' => 'Bancada',
            'cadeira_rodas' => 'Cadeira de Rodas',
            'checkout' => 'Checkout',
            'contadora' => 'Contadora',
            'digital_inteligente' => 'Digital Inteligente',
            'dosadora' => 'Dosadora',
            'etiquetadora' => 'Etiquetadora',
            'ferroviaria' => 'Ferroviária',
            'gancho' => 'Gancho',
            'graos' => 'Grãos',
            'grua' => 'Grua',
            'hospitalar' => 'Hospitalar',
            'imc' => 'IMC',
            'joias' => 'Joias',
            'mercado' => 'Mercado',
            'microbalanca' => 'Microbalança',
            'palete' => 'Palete',
            'pediatrica' => 'Pediátrica',
            'plataforma' => 'Plataforma',
            'piso' => 'Piso',
            'portatil' => 'Portátil',
            'postal' => 'Postal',
            'precisao' => 'Precisão',
            'preco_calculado' => 'Preço Calculado',
            'rodoviaria' => 'Rodoviária',
            'tanque' => 'Tanque',
        ];
    }

    /**
     * Lista de portas COM editáveis.
     */
    public static function portas(): array
    {
        $ports = [''];
        for ($i = 1; $i <= 20; $i++) {
            $ports[] = "COM$i";
        }
        return $ports;
    }

    /**
     * Lista de velocidades padrão (baud rate).
     */
    public static function velocidades(): array
    {
        return ['', 1200, 2400, 4800, 9600, 12000];
    }

    /**
     * Lista de paridades padrão.
     */
    public static function paridades(): array
    {
        return ['', 'PAR', 'IMPAR', 'NENHUM'];
    }

    /**
     * Lista de bits padrão.
     */
    public static function bits(): array
    {
        return ['', 7, 8];
    }

    /**
     * Lista de stop bits padrão.
     */
    public static function stopBits(): array
    {
        return ['', 1, 1.5, 2];
    }

    /**
     * Verifica se a balança está ativa.
     */
    public function isAtivo(): bool
    {
        return $this->ativo;
    }

    /**
     * Gera um token único ao criar o registro.
     */
    protected static function booted(): void
    {
        static::creating(function ($balancaConfig) {
            $balancaConfig->token = $balancaConfig->token ?? bin2hex(random_bytes(16)); // Token aleatório
        });
    }

    /**
     * Criptografa o número de série ao salvar.
     */
    public function setSerialNumberAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['serie_number'] = EncryptionHelper::encrypt($value);
        }
    }

    /**
     * Descriptografa o número de série ao buscar.
     */
    public function getSerialNumberAttribute($value)
    {
        return $value ? EncryptionHelper::decrypt($value) : null;
    }
}
