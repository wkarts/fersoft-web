<?php

namespace App\Models;

use App\Models\MarcaVeiculo;
use App\Models\ModeloVeiculo;
use App\Models\CombustivelVeiculo;
use App\Models\Funcionario;
use App\Models\Manutencao;

class Veiculo extends BaseModel
{

    protected $fillable = [
        'empresa_id',
        'usuario_id',
        'filial_id',
        'placa',
        'quilometragem',
        'data_ultima_manutencao',
        'quilometragem_ultima_manutencao',
        'proxima_manutencao_km',
        'data_proxima_revisao',
        'status_manutencao',
        'observacoes_manutencao',
        'motorista_id',
        'consumo_medio',
        'custo_por_km',
        'ativo',
        'uf',
        'cor',
        'marca',
        'modelo',
        'rntrc',
        'taf',
        'renavam',
        'numero_registro_estadual',
        'tipo',
        'tipo_carroceira',
        'tipo_rodado',
        'tara',
        'capacidade',
        'proprietario_documento',
        'proprietario_nome',
        'proprietario_ie',
        'proprietario_uf',
        'proprietario_tp',
        'ano_fabricacao',
        'ano_modelo',
        'marca_fk',
        'modelo_fk',
        'chassi',
        'combustivel',
        'combustivel_fk',
        'km_hora',
        'foto_veiculo',
        'versao_veiculo',
        'traccar_id',
    ];

    protected $appends = ['imgApp', 'img'];

    protected $casts = [
        'data_ultima_manutencao' => 'date',
        'data_proxima_revisao' => 'date',
        'quilometragem' => 'float',
        'quilometragem_ultima_manutencao' => 'float',
        'proxima_manutencao_km' => 'float',
    ];

    /**
     * Acessor para a imagem com caminho completo
     */
    public function getImgAppAttribute()
    {
        if (empty($this->foto_veiculo)) {
            return env("PATH_URL") . "/imgs/no_veiculos.png";
        }
        return env("PATH_URL") . "/imgs_veiculos/{$this->foto_veiculo}";
    }

    /**
     * Acessor para a imagem padrão
     */
    public function getImgAttribute()
    {
        if ($this->foto_veiculo == "") {
            return "/imagens/sem-imagem.png";
        }
        return env("PATH_URL") . "/imgs_veiculos/" . $this->foto_veiculo;
    }

    /**
     * Relacionamento com MarcaVeiculo
     */
    public function marcaVeiculo()
    {
        return $this->belongsTo(MarcaVeiculo::class, 'marca_fk');
    }

    /**
     * Relacionamento com ModeloVeiculo
     */
    public function modeloVeiculo()
    {
        return $this->belongsTo(ModeloVeiculo::class, 'modelo_fk');
    }

    /**
     * Relacionamento com CombustivelVeiculo
     */
    public function combustivelVeiculo()
    {
        return $this->belongsTo(CombustivelVeiculo::class, 'combustivel_fk');
    }

    /**
     * Relacionamento com Funcionario (Motorista)
     */
    public function motorista()
    {
        return $this->belongsTo(Funcionario::class, 'motorista_id');
    }

    public function manutencoes()
    {
        return $this->hasMany(Manutencao::class, 'veiculo_id');
    }

    /**
     * Métodos para descrever campos relacionados
     */
    public function getMarcaDescricaoAttribute()
    {
        return $this->marcaVeiculo ? $this->marcaVeiculo->descricao : 'N/A';
    }

    public function getModeloDescricaoAttribute()
    {
        return $this->modeloVeiculo ? $this->modeloVeiculo->descricao : 'N/A';
    }

    public function getCombustivelDescricaoAttribute()
    {
        return $this->combustivelVeiculo ? $this->combustivelVeiculo->descricao : 'N/A';
    }

    public function getMotoristaNomeAttribute()
    {
        return $this->motorista ? $this->motorista->nome : 'Sem Motorista';
    }

    /**
     * Métodos estáticos para tipos de veículo, rodado, carroceria, e proprietário
     */
    public static function tipos()
    {
        return [
            "02" => "CICLOMOTO",
            "03" => "MOTONETA",
            "04" => "MOTOCICLO",
            "05" => "TRICICLO",
            "06" => "AUTOMÓVEL",
            "07" => "MICRO-ÔNIBUS",
            "08" => "ÔNIBUS",
            "10" => "REBOQUE",
            "11" => "SEMIRREBOQUE",
            "13" => "CAMIONETA",
            "14" => "CAMINHÃO",
            "17" => "CAMINHÃO TRATOR",
            "18" => "TRATOR RODAS",
            "19" => "TRATOR ESTEIRAS",
            "20" => "TRATOR MISTO",
            "21" => "QUADRICICLO",
            "22" => "ESP / ÔNIBUS",
            "23" => "CAMINHONETE",
            "24" => "CARGA/CAM",
            "25" => "UTILITÁRIO",
            "26" => "MOTOR-CASA"
        ];
    }

    public static function getTipo($tipo)
    {
        return self::tipos()[$tipo] ?? 'N/A';
    }

    public static function tiposRodado()
    {
        return [
            "01" => "TRUCK",
            "02" => "TOCO",
            "03" => "CAVALO MECANICO",
            "04" => "VAN",
            "05" => "UTILITARIO",
            "06" => "OUTROS"
        ];
    }

    public static function getTipoRodado($tipo)
    {
        return self::tiposRodado()[$tipo] ?? 'N/A';
    }

    public static function tiposCarroceria()
    {
        return [
            "00" => "NAO APLICAVEL",
            "01" => "ABERTA",
            "02" => "FECHADA/BAU",
            "03" => "GRANELEIRA",
            "04" => "PORTA CONTAINER",
            "05" => "SLIDER"
        ];
    }

    public static function getTipoCarroceria($tipo)
    {
        return self::tiposCarroceria()[$tipo] ?? 'N/A';
    }

    public static function tiposProprietario()
    {
        return [
            "0" => "TAC AGREGADO",
            "1" => "TAC INDEPENDENTE",
            "2" => "OUTROS"
        ];
    }

    public static function getTipoProprietario($tipo)
    {
        return self::tiposProprietario()[$tipo] ?? 'N/A';
    }

    public static function cUF()
    {
        return [
            '12' => 'AC',
            '27' => 'AL',
            '13' => 'AM',
            '16' => 'AP',
            '29' => 'BA',
            '23' => 'CE',
            '53' => 'DF',
            '32' => 'ES',
            '52' => 'GO',
            '21' => 'MA',
            '31' => 'MG',
            '50' => 'MS',
            '51' => 'MT',
            '15' => 'PA',
            '25' => 'PB',
            '26' => 'PE',
            '22' => 'PI',
            '41' => 'PR',
            '33' => 'RJ',
            '24' => 'RN',
            '11' => 'RO',
            '14' => 'RR',
            '43' => 'RS',
            '28' => 'SE',
            '42' => 'SC',
            '35' => 'SP',
            '17' => 'TO'
        ];
    }

    public static function getUf($ufCode)
    {
        return self::cUF()[$ufCode] ?? 'N/A';
    }
  public function getStatusManutencaoNivelAttribute()
{
    // Se não tiver KM de próxima manutenção definido, fica neutro
    if (!$this->proxima_manutencao_km) return 'indefinido';

    $kmRestante = $this->proxima_manutencao_km - $this->quilometragem;

    if ($kmRestante <= 0) {
        return 'vencido'; // Vermelho
    } elseif ($kmRestante <= 500) {
        return 'alerta'; // Amarelo (Faltam menos de 500km)
    }

    return 'em_dia'; // Verde
}

}
