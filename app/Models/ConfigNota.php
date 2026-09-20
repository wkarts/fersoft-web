<?php

namespace App\Models;


class ConfigNota extends BaseModel
{
    protected $fillable = [
        'razao_social',
        'nome_fantasia',
        'cnpj',
        'ie',
        'logradouro',
        'numero',
        'bairro',
        'municipio',
        'codMun',
        'pais',
        'codPais',
        'fone',
        'cep',
        'UF',
        'CST_CSOSN_padrao',
        'CST_COFINS_padrao',
        'CST_PIS_padrao',
        'CST_IPI_padrao',
        'frete_padrao',
        'tipo_pagamento_padrao',
        'nat_op_padrao',
        'ambiente',
        'cUF',
        'ultimo_numero_nfe',
        'ultimo_numero_nfce',
        'ultimo_numero_cte',
        'ultimo_numero_mdfe',
        'numero_serie_nfe',
        'numero_serie_nfce',
        'numero_serie_cte',
        'csc',
        'csc_id',
        'certificado_a3',
        'empresa_id',
        'inscricao_municipal',
        'aut_xml',
        'logo',
        'casas_decimais',
        'email',
        'campo_obs_nfe',
        'exibir_ibscbs_inf_cpl',
        'exibir_piscofins_inf_cpl',
        'exibir_deolho_imposto_inf_cpl',
        'senha_remover',
        'percentual_lucro_padrao',
        'complemento',
        'numero_serie_mdfe',
        'sobrescrita_csonn_consumidor_final',
        'caixa_por_usuario',
        'percentual_max_desconto',
        'campo_obs_pedido',
        'token_ibpt',
        'validade_orcamento',
        'usar_email_proprio',
        'alerta_sonoro',
        'casas_decimais_qtd',
        'gerenciar_estoque_produto',
        'permitir_estoque_negativo', // <-- ADICIONADO AQUI
        'token_nfse',
        'parcelamento_maximo',
        'codigo_tributacao_municipio',
        'busca_documento_automatico',
        'graficos_dash',
        'multa_padrao',
        'juro_padrao',
        'tipo_impressao_danfe',
        'cBenef_padrao',
        'gerenciar_comissao_usuario_logado',
        'modelo_pdv',
        'cupom_modelo',
        'integracao_nfse',
        'ultimo_numero_nfse',
        'numero_serie_nfse',
        'token_sync',
        'bloquear_pesagem_manual_balanca',
        'usar_valores_ticket_pesagem',
        'usa_produto_referenciado_pesagem',
        'exibir_valores_ticket_pesagem_grid',
        'desbloquear_campo_peso_bag_ticket',
        'conectar_automaticamente_balanca_padrao_usuario',
        'conectar_automaticamente_balanca_ao_selecionar',
        'codigo_prosoft',
        'latitude',
        'longitude',
        'conta_estoque',
        'pesagem_habilitar_preview_cameras',
        'pesagem_exigir_imagem_quando_balanca_tem_camera',
        'pesagem_auto_concluir_ticket',
        'pesagem_bloquear_edicao_ticket_concluido',
        'pesagem_imprimir_imagens_a4',
        'pesagem_imprimir_imagens_80mm',
        'pesagem_email_destino',
        'pesagem_email_destinos_json',
        'pesagem_whatsapp_destino',
        'pesagem_whatsapp_destinos_json',
        'pesagem_enviar_email_ao_concluir',
        'pesagem_enviar_whatsapp_ao_concluir',
        'pesagem_enviar_imagens_notificacao',
        'pesagem_snapshot_disk',
        'pesagem_snapshot_base_path',
        'pesagem_storage_provider',
        'pesagem_storage_config_json',
        'pesagem_permitir_cadastro_rapido_cliente',
        'pesagem_permitir_cadastro_rapido_fornecedor',
        'pesagem_permitir_cadastro_rapido_veiculo',
        'pesagem_permitir_cadastro_rapido_motorista',
        'pesagem_exibir_chave_pix_relatorio',
        'pesagem_exibir_valores_relatorio',
        'pesagem_manter_modal_ticket_aberto_apos_salvar',
    ];

    protected $casts = [
        'pesagem_email_destinos_json' => 'array',
        'pesagem_whatsapp_destinos_json' => 'array',
        'pesagem_habilitar_preview_cameras' => 'boolean',
        'pesagem_exigir_imagem_quando_balanca_tem_camera' => 'boolean',
        'pesagem_auto_concluir_ticket' => 'boolean',
        'pesagem_bloquear_edicao_ticket_concluido' => 'boolean',
        'pesagem_imprimir_imagens_a4' => 'boolean',
        'pesagem_imprimir_imagens_80mm' => 'boolean',
        'pesagem_enviar_email_ao_concluir' => 'boolean',
        'pesagem_enviar_whatsapp_ao_concluir' => 'boolean',
        'pesagem_enviar_imagens_notificacao' => 'boolean',
        'pesagem_storage_config_json' => 'array',
        'pesagem_permitir_cadastro_rapido_cliente' => 'boolean',
        'pesagem_permitir_cadastro_rapido_fornecedor' => 'boolean',
        'pesagem_permitir_cadastro_rapido_veiculo' => 'boolean',
        'pesagem_permitir_cadastro_rapido_motorista' => 'boolean',
        'pesagem_exibir_chave_pix_relatorio' => 'boolean',
        'pesagem_exibir_valores_relatorio' => 'boolean',
        'pesagem_manter_modal_ticket_aberto_apos_salvar' => 'boolean',
    ];



    public function pesagemStorageProvider(): string
    {
        $provider = strtolower(trim((string) ($this->pesagem_storage_provider ?? 'system')));
        return in_array($provider, ['s3', 'minio', 'dropbox', 'onedrive', 'google_drive'], true) ? $provider : 'system';
    }

    public function usaStorageProprioPesagem(): bool
    {
        return $this->pesagemStorageProvider() !== 'system';
    }

    public function pesagemStorageConfig(): array
    {
        $config = $this->pesagem_storage_config_json ?? [];
        if (is_string($config)) {
            $decoded = json_decode($config, true);
            $config = json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : [];
        }
        return is_array($config) ? $config : [];
    }

    public function pesagemEmailsDestino(): array
    {
        return $this->normalizarListaDestinos($this->pesagem_email_destinos_json ?? null, $this->pesagem_email_destino ?? null, true);
    }

    public function pesagemWhatsappsDestino(): array
    {
        return $this->normalizarListaDestinos($this->pesagem_whatsapp_destinos_json ?? null, $this->pesagem_whatsapp_destino ?? null, false);
    }

    private function normalizarListaDestinos($json, ?string $legacy, bool $email): array
    {
        $items = [];

        if (is_string($json)) {
            $decoded = json_decode($json, true);
            $json = json_last_error() === JSON_ERROR_NONE ? $decoded : $json;
        }

        if (is_array($json)) {
            $items = array_merge($items, $json);
        } elseif (is_string($json) && trim($json) !== '') {
            $items[] = $json;
        }

        if (is_string($legacy) && trim($legacy) !== '') {
            $items[] = $legacy;
        }

        $resultado = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                $item = $item['value'] ?? $item['email'] ?? $item['whatsapp'] ?? $item['numero'] ?? null;
            }

            if (!is_string($item)) {
                continue;
            }

            foreach (preg_split('/[\r\n,;]+/', $item) as $valor) {
                $valor = trim($valor);
                if ($valor === '') {
                    continue;
                }

                if ($email && !filter_var($valor, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }

                if (!$email) {
                    $valor = preg_replace('/[^0-9]/', '', $valor);
                    if ($valor === '') {
                        continue;
                    }
                    if (!str_starts_with($valor, '55')) {
                        $valor = '55' . $valor;
                    }
                }

                $resultado[] = $valor;
            }
        }

        return array_values(array_unique($resultado));
    }

    public static function configStatic(){
        $value = session('user_logged');
        $empresa_id = $value['empresa'];
        $config = ConfigNota::
        where('empresa_id', $empresa_id)
            ->first();
        return $config;
    }

    public function natureza(){
        return $this->belongsTo(NaturezaOperacao::class, 'nat_op_padrao');
    }

    public static function tiposPagamento(){
        return [
            '01' => 'Dinheiro',
            '02' => 'Cheque',
            '03' => 'Cartão de Crédito',
            '04' => 'Cartão de Débito',
            '05' => 'Crédito Loja',
            '10' => 'Vale Alimentação',
            '11' => 'Vale Refeição',
            '12' => 'Vale Presente',
            '13' => 'Vale Combustível',
            '14' => 'Duplicata Mercantil',
            '15' => 'Boleto Bancário',
            '90' => 'Sem pagamento',
            // '99' => 'Outros',
        ];
    }

    public static function listaCST(){
        return [
            '00' => 'Tributa integralmente',
            '10' => 'Tributada e com cobrança do ICMS por substituição tributária',
            '20' => 'Com redução da Base de Calculo',
            '30' => 'Isenta / não tributada e com cobrança do ICMS por substituição tributária',
            '40' => 'Isenta',
            '41' => 'Não tributada',
            '50' => 'Com suspensão',
            '51' => 'Com diferimento',
            '60' => 'ICMS cobrado anteriormente por substituição tributária',
            '61' => 'ICMS Monofásico',
            '70' => 'Com redução da BC e cobrança do ICMS por substituição tributária',
            '90' => 'Outras',

            '101' => 'Tributada pelo Simples Nacional com permissão de crédito',
            '102' => 'Tributada pelo Simples Nacional sem permissão de crédito',
            '103' => 'Isenção do ICMS no Simples Nacional para faixa de receita bruta',
            '201' => 'Tributada pelo Simples Nacional com permissão de crédito e com cobrança do ICMS por substituição tributária',
            '202' => 'Tributada pelo Simples Nacional sem permissão de crédito e com cobrança do ICMS por substituição tributária',
            '203' => 'Isenção do ICMS no Simples Nacional para faixa de receita bruta e com cobrança do ICMS por substituição tributária',
            '300' => 'Imune',
            '400' => 'Não tributada pelo Simples Nacional',
            '500' => 'ICMS cobrado anteriormente por substituição tributária (substituído) ou por antecipação',
            '900' => 'Outros'
        ];
    }

    public static function listaCST_PIS_COFINS(){
        return [
            '01' => 'Operação Tributável com Alíquota Básica',
            '02' => 'Operação Tributável com Alíquota por Unidade de Medida de Produto',
            '03' => 'Operação Tributável com Alíquota por Unidade de Medida de Produto',
            '04' => 'Operação Tributável Monofásica – Revenda a Alíquota Zero',
            '05' => 'Operação Tributável por Substituição Tributária',
            '06' => 'Operação Tributável a Alíquota Zero',
            '07' => 'Operação Isenta da Contribuição',
            '08' => 'Operação sem Incidência da Contribuição',
            '09' => 'Operação com Suspensão da Contribuição',
            '49' => 'Outras Operações de Saída'
        ];
    }

    public static function listaCST_IPI(){
        return [
            '50' => 'Saída Tributada',
            '51' => 'Saída Tributável com Alíquota Zero',
            '52' => 'Saída Isenta',
            '53' => 'Saída Não Tributada',
            '54' => 'Saída Imune',
            '55' => 'Saída com Suspensão',
            '99' => 'Outras Saídas'
        ];
    }

    public static function tiposFrete(){

        return [
            '0' => 'Emitente',
            '1' => 'Destinatário',
            '2' => 'Terceiros',
            '3' => 'Própio por conta do remetente',
            '4' => 'Própio por conta do destinatário',
            '9' => 'Sem Frete',
        ];

    }

    public static function estados(){
        return [
            '11' => 'RO',
            '12' => 'AC',
            '13' => 'AM',
            '14' => 'RR',
            '15' => 'PA',
            '16' => 'AP',
            '17' => 'TO',
            '21' => 'MA',
            '22' => 'PI',
            '23' => 'CE',
            '24' => 'RN',
            '25' => 'PB',
            '26' => 'PE',
            '27' => 'AL',
            '28' => 'SE',
            '29' => 'BA',
            '31' => 'MG',
            '32' => 'ES',
            '33' => 'RJ',
            '35' => 'SP',
            '41' => 'PR',
            '42' => 'SC',
            '43' => 'RS',
            '50' => 'MS',
            '51' => 'MT',
            '52' => 'GO',
            '53' => 'DF'
        ];
    }

    public static function getUF($cUF){
        foreach(ConfigNota::estados() as $key => $u){
            if($cUF == $key){
                return $u;
            }
        }
    }

    public static function getCodUF($uf){
        foreach(ConfigNota::estados() as $key => $u){
            if($uf == $u){
                return $key;
            }
        }
    }

    public static function getAlertas(){
        return [
            'song1.mp3' => 'Game quick',
            'song2.wav' => 'Alert chime',
            'song3.wav' => 'Stopwatch',
            'song4.wav' => 'Dry Pop',
        ];
    }

    public static function formataCnpj($cnpj){
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

        $temp = substr($cnpj, 0, 2);
        $temp .= ".".substr($cnpj, 2, 3);
        $temp .= ".".substr($cnpj, 5, 3);
        $temp .= "/".substr($cnpj, 8, 4);
        $temp .= "-".substr($cnpj, 12, 2);
        return $temp;
    }

    public static function graficos(){
        return [
            'contas_pagar' => 'Contas a Pagar',
            'contas_receber' => 'Contas a Receber',
            'vendas' => 'Vendas',
            'vendas_pdv' => 'Venda de PDV',
            'orcamentos' => 'Orçamentos',
            'produtos' => 'Prdutos',
            'nfe' => 'NFe emitidas',
            'nfce' => 'NFCe emitidas',
        ];
    }


    public static function securityResource(): array
    {
        return array_replace_recursive(parent::securityResource(), [
            'module' => 'Configurações',
            'name' => 'Emitente',
            'plural_name' => 'Emitentes',
            'description' => 'Cadastro e configurações do emitente fiscal.',
            'route_prefix' => 'configNF',
            'icon' => 'building',
            'sensitive' => true,
            'tenant_visible' => true,
            'super_admin_only' => false,
            'actions' => [
                'view' => true,
                'create' => false,
                'edit' => true,
                'delete' => false,
                'restore' => false,
                'export' => false,
                'print' => false,
            ],
        ]);
    }

}
