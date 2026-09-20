<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ConfigNota;
use App\Models\Certificado;
use App\Models\Empresa;
use App\Models\Cidade;
use App\Models\CashBackConfig;
use App\Models\CashBackCliente;
use App\Models\EscritorioContabil;
use App\Models\NaturezaOperacao;
use App\Models\BalancaConfig;
use App\Services\NFService;
use NFePHP\Common\Certificate;
use Mail;

class ConfigNotaController extends Controller
{

    use \App\Traits\GeocodeTrait;

    protected $empresa_id = null;
    public function __construct(){
        $this->middleware(function ($request, $next) {
            $this->empresa_id = $request->empresa_id;

            $value = session('user_logged');
            if(!$value){
                return redirect("/login");
            }
            return $next($request);
        });
    }

    function sanitizeString($str){
        return preg_replace('{\W}', ' ', preg_replace('{ +}', ' ', strtr(
            utf8_decode(html_entity_decode($str)),
            utf8_decode('ÀÁÃÂÉÊÍÓÕÔÚÜÇÑàáãâéêíóõôúüçñ'),
            'AAAAEEIOOOUUCNaaaaeeiooouucn')));
    }

    public function index(){
        try{
            $naturezas = NaturezaOperacao::
            where('empresa_id', $this->empresa_id)
                ->get();
            $tiposPagamento = ConfigNota::tiposPagamento();
            $tiposFrete = ConfigNota::tiposFrete();
            $listaCSTCSOSN = ConfigNota::listaCST();
            $listaCSTPISCOFINS = ConfigNota::listaCST_PIS_COFINS();
            $listaCSTIPI = ConfigNota::listaCST_IPI();

            $config = ConfigNota::
            where('empresa_id', $this->empresa_id)
                ->first();
            $certificado = Certificado::
            where('empresa_id', $this->empresa_id)
                ->first();

            $cUF = ConfigNota::estados();

            $infoCertificado = null;
            if($certificado != null){
                $infoCertificado = $this->getInfoCertificado($certificado);
            }

            $soapDesativado = !extension_loaded('soap');

            $cidades = Cidade::all();

            if($config != null){
                $config->graficos_dash = $config->graficos_dash ? json_decode($config->graficos_dash) : [];
            }

            $empresa = Empresa::findOrFail($this->empresa_id);
            $cnpj = $empresa->cnpj;
            $balancasAtivas = BalancaConfig::where('empresa_id', $this->empresa_id)->where('ativo', true)->get();

            return view('configNota/index')
                ->with('config', $config)
                ->with('cnpj', $cnpj)
                ->with('naturezas', $naturezas)
                ->with('tiposPagamento', $tiposPagamento)
                ->with('tiposFrete', $tiposFrete)
                ->with('infoCertificado', $infoCertificado)
                ->with('soapDesativado', $soapDesativado)
                ->with('listaCSTCSOSN', $listaCSTCSOSN)
                ->with('listaCSTPISCOFINS', $listaCSTPISCOFINS)
                ->with('listaCSTIPI', $listaCSTIPI)
                ->with('cUF', $cUF)
                ->with('cidades', $cidades)
                ->with('testeJs', true)
                ->with('configJs', true)
                ->with('certificado', $certificado)
                ->with('balancasAtivas', $balancasAtivas)
                ->with('title', 'Configurar Emitente');
        }catch(\Exception $e){
            echo $e->getMessage();
            echo "<br><a href='/configNF/deleteCertificado'>Remover Certificado</a>";
        }
    }

    private function getInfoCertificado($certificado){

        $infoCertificado = Certificate::readPfx($certificado->arquivo, $certificado->senha);

        $publicKey = $infoCertificado->publicKey;

        $inicio =  $publicKey->validFrom->format('Y-m-d H:i:s');
        $expiracao =  $publicKey->validTo->format('Y-m-d H:i:s');

        return [
            'serial' => $publicKey->serialNumber,
            'inicio' => \Carbon\Carbon::parse($inicio)->format('d-m-Y H:i'),
            'expiracao' => \Carbon\Carbon::parse($expiracao)->format('d-m-Y H:i'),
            'id' => $publicKey->commonName
        ];

    }

    public function save(Request $request){
        $request->merge([
            'permitir_estoque_negativo' => $request->has('permitir_estoque_negativo') ? 1 : 0
        ]);
        if ($request->id == 0) {
            $config = new ConfigNota();
        } else {
            $config = ConfigNota::find($request->id);
        }
        $this->_validate($request);

        $tentouDesabilitarProdutoReferenciado = false;

        $request->merge([
            'bloquear_pesagem_manual_balanca' => $request->boolean('bloquear_pesagem_manual_balanca'),
            'usar_valores_ticket_pesagem' => $request->boolean('usar_valores_ticket_pesagem'),
            'usa_produto_referenciado_pesagem' => $request->boolean('usa_produto_referenciado_pesagem'),
            'exibir_valores_ticket_pesagem_grid' => $request->boolean('exibir_valores_ticket_pesagem_grid'),
            'desbloquear_campo_peso_bag_ticket' => $request->boolean('desbloquear_campo_peso_bag_ticket'),
            'conectar_automaticamente_balanca_padrao_usuario' => $request->boolean('conectar_automaticamente_balanca_padrao_usuario'),
            'conectar_automaticamente_balanca_ao_selecionar' => $request->boolean('conectar_automaticamente_balanca_ao_selecionar'),
            'pesagem_habilitar_preview_cameras' => $request->boolean('pesagem_habilitar_preview_cameras'),
            'pesagem_exigir_imagem_quando_balanca_tem_camera' => $request->boolean('pesagem_exigir_imagem_quando_balanca_tem_camera'),
            'pesagem_auto_concluir_ticket' => $request->boolean('pesagem_auto_concluir_ticket'),
            'pesagem_bloquear_edicao_ticket_concluido' => $request->boolean('pesagem_bloquear_edicao_ticket_concluido'),
            'pesagem_imprimir_imagens_a4' => $request->boolean('pesagem_imprimir_imagens_a4'),
            'pesagem_imprimir_imagens_80mm' => $request->boolean('pesagem_imprimir_imagens_80mm'),
            'pesagem_enviar_email_ao_concluir' => $request->boolean('pesagem_enviar_email_ao_concluir'),
            'pesagem_enviar_whatsapp_ao_concluir' => $request->boolean('pesagem_enviar_whatsapp_ao_concluir'),
            'pesagem_enviar_imagens_notificacao' => $request->boolean('pesagem_enviar_imagens_notificacao'),
            'pesagem_permitir_cadastro_rapido_cliente' => $request->boolean('pesagem_permitir_cadastro_rapido_cliente'),
            'pesagem_permitir_cadastro_rapido_fornecedor' => $request->boolean('pesagem_permitir_cadastro_rapido_fornecedor'),
            'pesagem_permitir_cadastro_rapido_veiculo' => $request->boolean('pesagem_permitir_cadastro_rapido_veiculo'),
            'pesagem_permitir_cadastro_rapido_motorista' => $request->boolean('pesagem_permitir_cadastro_rapido_motorista'),
            'pesagem_exibir_chave_pix_relatorio' => $request->boolean('pesagem_exibir_chave_pix_relatorio'),
            'pesagem_exibir_valores_relatorio' => $request->boolean('pesagem_exibir_valores_relatorio'),
            'pesagem_manter_modal_ticket_aberto_apos_salvar' => $request->boolean('pesagem_manter_modal_ticket_aberto_apos_salvar'),
        ]);


        $request->merge([
            'pesagem_email_destinos_json' => $this->normalizarDestinosPesagem($request->input('pesagem_email_destinos', $request->pesagem_email_destino ?? ''), true),
            'pesagem_whatsapp_destinos_json' => $this->normalizarDestinosPesagem($request->input('pesagem_whatsapp_destinos', $request->pesagem_whatsapp_destino ?? ''), false),
            // O storage local/sistema é técnico e não fica exposto ao usuário final.
            // Se o tenant não configurar storage próprio, usa o storage padrão da aplicação/env.
            'pesagem_snapshot_disk' => env('PESAGEM_SNAPSHOT_DISK', 'public_path'),
            'pesagem_snapshot_base_path' => trim((string) env('PESAGEM_SNAPSHOT_BASE_PATH', 'pesagem_ticket_imagens'), '/'),
            'pesagem_storage_provider' => $this->normalizarProviderStoragePesagem($request->input('pesagem_storage_provider', 'system')),
            'pesagem_storage_config_json' => $this->normalizarConfigStoragePesagem($request),
        ]);

        if ((int) $request->id > 0) {
            $tentouDesabilitarProdutoReferenciado = !$request->boolean('usa_produto_referenciado_pesagem');
            $configExistente = ConfigNota::where('empresa_id', $this->empresa_id)->first();

            if ($configExistente && (bool) ($configExistente->usa_produto_referenciado_pesagem ?? false)) {
                $request->merge(['usa_produto_referenciado_pesagem' => true]);
            }
        }

        if ($request->bloquear_pesagem_manual_balanca) {
            $validacaoBalanca = $this->validarAtivacaoBalancaPadrao($request);
            if ($validacaoBalanca !== true) {
                session()->flash('mensagem_erro', $validacaoBalanca);
                return redirect()->back()->withInput();
            }
        }
        $uf = $request->uf;

        $nomeImagem = "";

        $empresa = Empresa::find($this->empresa_id);

        $cnpjEmitente = preg_replace('/[^0-9]/', '', $empresa->cnpj);
        $cnpjEmpresa = preg_replace('/[^0-9]/', '', $request->cnpj);
        $value = session('user_logged');
        if($cnpjEmitente != $cnpjEmpresa && !$value['super']){
            session()->flash('mensagem_erro', 'É necessário informar o mesmo CNPJ/CPF do cadastro de empresa!');
            return redirect()->back();
        }

        if($request->hasFile('file')){
            $file = $request->file('file');

            $extensao = $file->getClientOriginalExtension();
            $rand = rand(0, 999999);
            $nomeImagem = md5($file->getClientOriginalName()).$rand.".".$extensao;
            $upload = $file->move(public_path('logos'), $nomeImagem);
        }

        $cidade = Cidade::find($request->cidade);
        $codMun = $cidade->codigo;
        $uf = $cidade->uf;
        $cUF = ConfigNota::getCodUF($uf);
        $municipio = $cidade->nome;

        $enderecoBusca = $request->logradouro . ', ' . $request->numero . ', ' . $request->bairro . ', ' . $municipio . ' - ' . $uf;
        $coords = $this->buscarCoordenadas($enderecoBusca);
        $request->merge([
            'latitude' => $coords['latitude'],
            'longitude' => $coords['longitude']
        ]);


        $request->merge([
            'senha_remover' => trim($request->senha_remover)
        ]);

        if(!isset($request->graficos_dash)){
            $request->graficos_dash = '[]';
        }else{
            $request->graficos_dash = json_encode($request->graficos_dash);
        }
        if($request->id == 0){

            $result = ConfigNota::create([
                'latitude' => $request->latitude,   // <- ADICIONE AQUI
                'longitude' => $request->longitude, // <- ADICIONE AQUI
                'razao_social' => strtoupper($this->sanitizeString($request->razao_social)),
                'nome_fantasia' => strtoupper($this->sanitizeString($request->nome_fantasia)),
                'cnpj' => $request->cnpj,
                'ie' => $request->ie,
                'logradouro' => strtoupper($this->sanitizeString($request->logradouro)),
                'complemento' => strtoupper($this->sanitizeString($request->complemento)),
                'numero' => strtoupper($this->sanitizeString($request->numero)),
                'bairro' => strtoupper($this->sanitizeString($request->bairro)),
                'cep' => $request->cep,
                'email' => $request->email ?? '',
                'municipio' => strtoupper($municipio),
                'codMun' => $codMun,
                'codPais' => '1058',
                'UF' => $uf,
                'pais' => 'BRASIL',
                'fone' => $this->sanitizeString($request->fone),
                'CST_CSOSN_padrao' => $request->CST_CSOSN_padrao,
                'CST_COFINS_padrao' => $request->CST_COFINS_padrao,
                'CST_PIS_padrao' => $request->CST_PIS_padrao,
                'CST_IPI_padrao' => $request->CST_IPI_padrao,
                'busca_documento_automatico' => $request->busca_documento_automatico,
                'frete_padrao' => $request->frete_padrao,
                'tipo_impressao_danfe' => $request->tipo_impressao_danfe,
                'tipo_pagamento_padrao' => $request->tipo_pagamento_padrao,
                'nat_op_padrao' => $request->nat_op_padrao ?? 0,
                'cBenef_padrao' => $request->cBenef_padrao ?? '',
                'validade_orcamento' => $request->validade_orcamento ?? 0,
                'ambiente' => env("APP_ENV") == "demo" ? 2 : $request->ambiente,
                'cUF' => $cUF,
                'ultimo_numero_nfe' => $request->ultimo_numero_nfe,
                'ultimo_numero_nfce' => $request->ultimo_numero_nfce,
                'ultimo_numero_cte' => $request->ultimo_numero_cte ?? 0,
                'ultimo_numero_mdfe' => $request->ultimo_numero_mdfe ?? 0,
                'ultimo_numero_nfse' => $request->ultimo_numero_nfse ?? 0,
                'numero_serie_nfe' => $request->numero_serie_nfe,
                'numero_serie_nfce' => $request->numero_serie_nfce,
                'numero_serie_cte' => $request->numero_serie_cte ?? 0,
                'numero_serie_mdfe' => $request->numero_serie_mdfe ?? 0,
                'numero_serie_nfse' => $request->numero_serie_nfse ?? '0',
                'percentual_max_desconto' => $request->percentual_max_desconto ?? 0,
                'csc' => $request->csc,
                'csc_id' => $request->csc_id,
                'certificado_a3' => $request->certificado_a3 ? true: false,
                'gerenciar_estoque_produto' => $request->gerenciar_estoque_produto,
                'gerenciar_comissao_usuario_logado' => $request->gerenciar_comissao_usuario_logado,
                'empresa_id' => $request->empresa_id,
                'inscricao_municipal' => $request->inscricao_municipal ?? '',
                'alerta_sonoro' => $request->alerta_sonoro ?? '',
                'aut_xml' => $request->aut_xml ?? '',
                'logo' => $nomeImagem,
                'campo_obs_nfe' => $request->campo_obs_nfe ?? '',
                'exibir_ibscbs_inf_cpl' => $request->exibir_ibscbs_inf_cpl ? 1 : 0,
                'exibir_piscofins_inf_cpl' => $request->exibir_piscofins_inf_cpl ? 1 : 0,
                'exibir_deolho_imposto_inf_cpl' => $request->exibir_deolho_imposto_inf_cpl ? 1 : 0,
                'usar_email_proprio' => $request->usar_email_proprio,
                'campo_obs_pedido' => $request->campo_obs_pedido ?? '',
                'token_ibpt' => $request->token_ibpt ?? '',
                'token_nfse' => $request->token_nfse ?? '',
                'integracao_nfse' => $request->integracao_nfse ?? '',
                'token_sync' => $request->token_sync ?? '',
                'codigo_tributacao_municipio' => $request->codigo_tributacao_municipio ?? '',
                'casas_decimais' => $request->casas_decimais,
                'casas_decimais_qtd' => $request->casas_decimais_qtd,
                'sobrescrita_csonn_consumidor_final' => $request->sobrescrita_csonn_consumidor_final ?? '',
                'percentual_lucro_padrao' => $request->percentual_lucro_padrao ?? 0,
                'parcelamento_maximo' => $request->parcelamento_maximo ?? 12,
                'caixa_por_usuario' => $request->caixa_por_usuario,
                'juro_padrao' => $request->juro_padrao ? __replace($request->juro_padrao) : 0,
                'multa_padrao' => $request->multa_padrao ? __replace($request->multa_padrao) : 0,
                'graficos_dash' => $request->graficos_dash,
                'senha_remover' => trim($request->senha_remover) != '' ? md5($request->senha_remover) : '',
                'bloquear_pesagem_manual_balanca' => $request->bloquear_pesagem_manual_balanca,
                'usar_valores_ticket_pesagem' => $request->usar_valores_ticket_pesagem,
                'usa_produto_referenciado_pesagem' => $request->usa_produto_referenciado_pesagem,
                'exibir_valores_ticket_pesagem_grid' => $request->exibir_valores_ticket_pesagem_grid,
                'desbloquear_campo_peso_bag_ticket' => $request->desbloquear_campo_peso_bag_ticket,
                'conectar_automaticamente_balanca_padrao_usuario' => $request->conectar_automaticamente_balanca_padrao_usuario,
                'conectar_automaticamente_balanca_ao_selecionar' => $request->conectar_automaticamente_balanca_ao_selecionar,
                'pesagem_habilitar_preview_cameras' => $request->pesagem_habilitar_preview_cameras,
                'pesagem_exigir_imagem_quando_balanca_tem_camera' => $request->pesagem_exigir_imagem_quando_balanca_tem_camera,
                'pesagem_auto_concluir_ticket' => $request->pesagem_auto_concluir_ticket,
                'pesagem_bloquear_edicao_ticket_concluido' => $request->pesagem_bloquear_edicao_ticket_concluido,
                'pesagem_imprimir_imagens_a4' => $request->pesagem_imprimir_imagens_a4,
                'pesagem_imprimir_imagens_80mm' => $request->pesagem_imprimir_imagens_80mm,
                'pesagem_email_destino' => $request->pesagem_email_destino ?? '',
                'pesagem_email_destinos_json' => $request->pesagem_email_destinos_json,
                'pesagem_whatsapp_destino' => $request->pesagem_whatsapp_destino ?? '',
                'pesagem_whatsapp_destinos_json' => $request->pesagem_whatsapp_destinos_json,
                'pesagem_snapshot_disk' => $request->pesagem_snapshot_disk,
                'pesagem_snapshot_base_path' => $request->pesagem_snapshot_base_path,
                'pesagem_storage_provider' => $request->pesagem_storage_provider,
                'pesagem_storage_config_json' => $request->pesagem_storage_config_json,
                'pesagem_enviar_email_ao_concluir' => $request->pesagem_enviar_email_ao_concluir,
                'pesagem_enviar_whatsapp_ao_concluir' => $request->pesagem_enviar_whatsapp_ao_concluir,
                'pesagem_enviar_imagens_notificacao' => $request->pesagem_enviar_imagens_notificacao,
                'pesagem_permitir_cadastro_rapido_cliente' => $request->pesagem_permitir_cadastro_rapido_cliente,
                'pesagem_permitir_cadastro_rapido_fornecedor' => $request->pesagem_permitir_cadastro_rapido_fornecedor,
                'pesagem_permitir_cadastro_rapido_veiculo' => $request->pesagem_permitir_cadastro_rapido_veiculo,
                'pesagem_permitir_cadastro_rapido_motorista' => $request->pesagem_permitir_cadastro_rapido_motorista,
                'pesagem_exibir_chave_pix_relatorio' => $request->pesagem_exibir_chave_pix_relatorio,
                'pesagem_exibir_valores_relatorio' => $request->pesagem_exibir_valores_relatorio,
                'pesagem_manter_modal_ticket_aberto_apos_salvar' => $request->pesagem_manter_modal_ticket_aberto_apos_salvar,
                'permitir_estoque_negativo' => $request->permitir_estoque_negativo, // ADICIONE ESTA LINHA
            ]);
        }else{
            $config = ConfigNota::
            where('empresa_id', $this->empresa_id)
                ->first();

            // --- FAZ A BUSCA PARA ATUALIZAÇÃO ---
            $cidadeObj = Cidade::find($request->cidade);
            $nomeMunicipio = $cidadeObj->nome ?? '';
            $nomeUf = $cidadeObj->uf ?? '';
            $enderecoBusca = $request->logradouro . ', ' . $request->numero . ', ' . $request->bairro . ', ' . $nomeMunicipio . ' - ' . $nomeUf;
            $coords = $this->buscarCoordenadas($enderecoBusca, $nomeMunicipio, $nomeUf);

            $config->latitude = $coords['latitude'];
            $config->longitude = $coords['longitude'];

            $config->razao_social = strtoupper($this->sanitizeString($request->razao_social));
            $config->nome_fantasia = strtoupper($this->sanitizeString($request->nome_fantasia));
            $config->cnpj = $this->sanitizeString($request->cnpj);
            $config->ie = $this->sanitizeString($request->ie);
            $config->logradouro = strtoupper($this->sanitizeString($request->logradouro));
            $config->numero = strtoupper($this->sanitizeString($request->numero));
            $config->bairro = strtoupper($this->sanitizeString($request->bairro));
            $config->cep = $request->cep;
            $config->municipio = strtoupper($this->sanitizeString($municipio));
            $config->codMun = $codMun;
            $config->UF = $uf;
            $config->fone = $request->fone;
            $config->email = $request->email ?? '';
            $config->alerta_sonoro = $request->alerta_sonoro ?? '';

            $config->CST_CSOSN_padrao = $request->CST_CSOSN_padrao;
            $config->CST_COFINS_padrao = $request->CST_COFINS_padrao;
            $config->CST_PIS_padrao = $request->CST_PIS_padrao;
            $config->CST_IPI_padrao = $request->CST_IPI_padrao;
            $config->cBenef_padrao = $request->cBenef_padrao ?? '';
            $config->busca_documento_automatico = $request->busca_documento_automatico;

            $config->frete_padrao = $request->frete_padrao;
            $config->tipo_pagamento_padrao = $request->tipo_pagamento_padrao;
            $config->nat_op_padrao = $request->nat_op_padrao ?? 0;
            $config->percentual_lucro_padrao = $request->percentual_lucro_padrao ?? 0;
            $config->ambiente = env("APP_ENV") == "demo" ? 2 : $request->ambiente;
            $config->caixa_por_usuario = $request->caixa_por_usuario;
            $config->cUF = $cUF;
            $config->ultimo_numero_nfe = $request->ultimo_numero_nfe;
            $config->ultimo_numero_nfce = $request->ultimo_numero_nfce;
            $config->tipo_impressao_danfe = $request->tipo_impressao_danfe;
            $config->ultimo_numero_cte = $request->ultimo_numero_cte ?? 0;
            $config->ultimo_numero_nfse = $request->ultimo_numero_nfse ?? 0;
            $config->parcelamento_maximo = $request->parcelamento_maximo ?? 0;
            $config->ultimo_numero_mdfe = $request->ultimo_numero_mdfe ?? 0;
            $config->validade_orcamento = $request->validade_orcamento ?? 0;
            $config->numero_serie_nfe = $request->numero_serie_nfe;
            $config->numero_serie_nfce = $request->numero_serie_nfce;
            $config->numero_serie_cte = $request->numero_serie_cte ?? 0;
            $config->numero_serie_mdfe = $request->numero_serie_mdfe ?? 0;
            $config->numero_serie_nfse = $request->numero_serie_nfse ?? '0';
            $config->juro_padrao = $request->juro_padrao ? __replace($request->juro_padrao) : 0;
            $config->multa_padrao = $request->multa_padrao ? __replace($request->multa_padrao) : 0;
            $config->percentual_max_desconto = $request->percentual_max_desconto ?? 0;
            $config->csc = $request->csc;
            $config->csc_id = $request->csc_id;
            $config->campo_obs_nfe = $request->campo_obs_nfe ?? '';
            $config->exibir_ibscbs_inf_cpl = $request->exibir_ibscbs_inf_cpl ? 1 : 0;
            $config->exibir_piscofins_inf_cpl = $request->exibir_piscofins_inf_cpl ? 1 : 0;
            $config->exibir_deolho_imposto_inf_cpl = $request->exibir_deolho_imposto_inf_cpl ? 1 : 0;
            $config->campo_obs_pedido = $request->campo_obs_pedido ?? '';
            $config->token_ibpt = $request->token_ibpt ?? '';
            $config->token_nfse = $request->token_nfse ?? '';
            $config->integracao_nfse = $request->integracao_nfse ?? '';
            $config->token_sync = $request->token_sync ?? '';
            $config->codigo_tributacao_municipio = $request->codigo_tributacao_municipio ?? '';
            $config->complemento = $request->complemento ?? '';
            $config->sobrescrita_csonn_consumidor_final = $request->sobrescrita_csonn_consumidor_final ?? '';
            if(trim($request->senha_remover) != ""){
                $config->senha_remover = md5($request->senha_remover);
            }
            $config->casas_decimais = $request->casas_decimais;
            $config->casas_decimais_qtd = $request->casas_decimais_qtd;
            $config->certificado_a3 = $request->certificado_a3 ? true : false;
            $config->gerenciar_estoque_produto = $request->gerenciar_estoque_produto;
            $config->gerenciar_comissao_usuario_logado = $request->gerenciar_comissao_usuario_logado;
            $config->usar_email_proprio = $request->usar_email_proprio;
            $config->graficos_dash = $request->graficos_dash;
            $config->bloquear_pesagem_manual_balanca = $request->bloquear_pesagem_manual_balanca;
            $config->usar_valores_ticket_pesagem = $request->usar_valores_ticket_pesagem;
            $config->usa_produto_referenciado_pesagem = $request->usa_produto_referenciado_pesagem;
            $config->exibir_valores_ticket_pesagem_grid = $request->exibir_valores_ticket_pesagem_grid;
            $config->desbloquear_campo_peso_bag_ticket = $request->desbloquear_campo_peso_bag_ticket;
            $config->conectar_automaticamente_balanca_padrao_usuario = $request->conectar_automaticamente_balanca_padrao_usuario;
            $config->conectar_automaticamente_balanca_ao_selecionar = $request->conectar_automaticamente_balanca_ao_selecionar;
            $config->pesagem_habilitar_preview_cameras = $request->pesagem_habilitar_preview_cameras;
            $config->pesagem_exigir_imagem_quando_balanca_tem_camera = $request->pesagem_exigir_imagem_quando_balanca_tem_camera;
            $config->pesagem_auto_concluir_ticket = $request->pesagem_auto_concluir_ticket;
            $config->pesagem_bloquear_edicao_ticket_concluido = $request->pesagem_bloquear_edicao_ticket_concluido;
            $config->pesagem_imprimir_imagens_a4 = $request->pesagem_imprimir_imagens_a4;
            $config->pesagem_imprimir_imagens_80mm = $request->pesagem_imprimir_imagens_80mm;
            $config->pesagem_email_destino = $request->pesagem_email_destino ?? '';
            $config->pesagem_email_destinos_json = $request->pesagem_email_destinos_json;
            $config->pesagem_whatsapp_destino = $request->pesagem_whatsapp_destino ?? '';
            $config->pesagem_whatsapp_destinos_json = $request->pesagem_whatsapp_destinos_json;
            $config->pesagem_snapshot_disk = $request->pesagem_snapshot_disk;
            $config->pesagem_snapshot_base_path = $request->pesagem_snapshot_base_path;
            $config->pesagem_storage_provider = $request->pesagem_storage_provider;
            $config->pesagem_storage_config_json = $request->pesagem_storage_config_json;
            $config->pesagem_enviar_email_ao_concluir = $request->pesagem_enviar_email_ao_concluir;
            $config->pesagem_enviar_whatsapp_ao_concluir = $request->pesagem_enviar_whatsapp_ao_concluir;
            $config->pesagem_enviar_imagens_notificacao = $request->pesagem_enviar_imagens_notificacao;
            $config->pesagem_permitir_cadastro_rapido_cliente = $request->pesagem_permitir_cadastro_rapido_cliente;
            $config->pesagem_permitir_cadastro_rapido_fornecedor = $request->pesagem_permitir_cadastro_rapido_fornecedor;
            $config->pesagem_permitir_cadastro_rapido_veiculo = $request->pesagem_permitir_cadastro_rapido_veiculo;
            $config->pesagem_permitir_cadastro_rapido_motorista = $request->pesagem_permitir_cadastro_rapido_motorista;
            $config->pesagem_exibir_chave_pix_relatorio = $request->pesagem_exibir_chave_pix_relatorio;
            $config->pesagem_exibir_valores_relatorio = $request->pesagem_exibir_valores_relatorio;
            $config->pesagem_manter_modal_ticket_aberto_apos_salvar = $request->pesagem_manter_modal_ticket_aberto_apos_salvar;
            $config->permitir_estoque_negativo = $request->permitir_estoque_negativo; // ADICIONE ESTA LINHA
            $config->inscricao_municipal = $request->inscricao_municipal ?? '';
            $config->aut_xml = $request->aut_xml ?? '';
            if($request->hasFile('file')){
                $config->logo = $nomeImagem;
            }

            $result = $config->save();

            if ($result && (bool) ($config->usa_produto_referenciado_pesagem ?? false) && $tentouDesabilitarProdutoReferenciado) {
                session()->flash('mensagem_erro', 'A configuração de produto referenciado da pesagem já está ativa e permanece bloqueada para desativação.');
            }
        }

        $value = session('user_logged');

        $value['ambiente'] = $request->ambiente == 1 ? 'Produção' : 'Homologação';

        session()->put('user_logged', $value);

        if($result){
            session()->flash("mensagem_sucesso", "Configurado com sucesso!");
        }else{
            session()->flash('mensagem_erro', 'Erro ao configurar!');
        }
        return redirect('/configNF');
    }





    private function normalizarProviderStoragePesagem(?string $provider): string
    {
        $provider = strtolower(trim((string) $provider));
        return in_array($provider, ['system', 's3', 'minio', 'dropbox', 'onedrive', 'google_drive'], true) ? $provider : 'system';
    }

    private function normalizarConfigStoragePesagem(Request $request): ?array
    {
        $provider = $this->normalizarProviderStoragePesagem($request->input('pesagem_storage_provider', 'system'));

        if ($provider === 'system') {
            return null;
        }

        $basePath = trim(str_replace('\\', '/', (string) $request->input('pesagem_storage_base_path', 'pesagem_ticket_imagens')), '/');
        if ($basePath === '') {
            $basePath = 'pesagem_ticket_imagens';
        }

        $config = [
            'provider' => $provider,
            'base_path' => $basePath,
        ];

        if (in_array($provider, ['s3', 'minio'], true)) {
            $config += [
                'bucket' => trim((string) $request->input('pesagem_storage_bucket')),
                'region' => trim((string) $request->input('pesagem_storage_region', 'us-east-1')) ?: 'us-east-1',
                'endpoint' => trim((string) $request->input('pesagem_storage_endpoint')) ?: null,
                'url' => trim((string) $request->input('pesagem_storage_url')) ?: null,
                'access_key' => trim((string) $request->input('pesagem_storage_access_key')),
                'secret_key' => trim((string) $request->input('pesagem_storage_secret_key')),
                'use_path_style_endpoint' => $request->boolean('pesagem_storage_use_path_style_endpoint'),
            ];
        }

        if ($provider === 'dropbox') {
            $config += [
                'access_token' => trim((string) $request->input('pesagem_storage_access_token')),
                'folder' => trim((string) $request->input('pesagem_storage_folder', $basePath)) ?: $basePath,
            ];
        }

        if ($provider === 'onedrive') {
            $config += [
                'access_token' => trim((string) $request->input('pesagem_storage_access_token')),
                'drive_id' => trim((string) $request->input('pesagem_storage_drive_id')) ?: null,
                'folder' => trim((string) $request->input('pesagem_storage_folder', $basePath)) ?: $basePath,
            ];
        }

        if ($provider === 'google_drive') {
            $config += [
                'access_token' => trim((string) $request->input('pesagem_storage_access_token')),
                'folder_id' => trim((string) $request->input('pesagem_storage_folder_id')) ?: null,
                'folder' => trim((string) $request->input('pesagem_storage_folder', $basePath)) ?: $basePath,
                'make_public' => $request->boolean('pesagem_storage_make_public'),
            ];
        }

        return $config;
    }

    private function normalizarDestinosPesagem($value, bool $email): array
    {
        if (is_array($value)) {
            $items = $value;
        } else {
            $items = preg_split('/[\r\n,;]+/', (string) $value);
        }

        $resultado = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                $item = $item['value'] ?? $item['email'] ?? $item['whatsapp'] ?? $item['numero'] ?? '';
            }

            $item = trim((string) $item);
            if ($item === '') {
                continue;
            }

            if ($email) {
                if (filter_var($item, FILTER_VALIDATE_EMAIL)) {
                    $resultado[] = $item;
                }
                continue;
            }

            $item = preg_replace('/[^0-9]/', '', $item);
            if ($item === '') {
                continue;
            }
            if (!str_starts_with($item, '55')) {
                $item = '55' . $item;
            }
            $resultado[] = $item;
        }

        return array_values(array_unique($resultado));
    }

    private function validarAtivacaoBalancaPadrao(Request $request)
    {
        $balancasAtivas = BalancaConfig::where('empresa_id', $this->empresa_id)
            ->where('ativo', true)
            ->count();

        if ($balancasAtivas <= 0) {
            return 'Não é possível ativar o bloqueio de pesagem manual sem balança cadastrada/ativa.';
        }

        return true;
    }

    private function _validate(Request $request){
        $rules = [
            'razao_social' => 'required|max:60',
            'nome_fantasia' => 'required|max:60',
            'cnpj' => 'required',
            'ie' => 'required',
            'logradouro' => 'required|max:80',
            'numero' => 'required|max:10',
            'bairro' => 'required|max:50',
            'fone' => 'required|max:20',
            'email' => 'max:60',
            'cep' => 'required',
            'file' => 'max:300',
            // 'municipio' => 'required',
            // 'codMun' => 'required',
            // 'uf' => 'required|max:2|min:2',
            'ultimo_numero_nfe' => 'required',
            'ultimo_numero_nfce' => 'required',
            // 'ultimo_numero_cte' => 'required',
            // 'ultimo_numero_mdfe' => 'required',
            'numero_serie_nfe' => 'required|max:3',
            'numero_serie_nfce' => 'required|max:3',
            // 'numero_serie_cte' => 'required|max:3',
            // 'numero_serie_mdfe' => 'required|max:3',
            'csc' => 'required',
            'csc_id' => 'required',
            // 'file' => 'max:2000',
        ];

        $messages = [
            'razao_social.required' => 'O Razão social nome é obrigatório.',
            'razao_social.max' => '60 caracteres maximos permitidos.',
            'nome_fantasia.required' => 'O campo Nome Fantasia é obrigatório.',
            'nome_fantasia.max' => '60 caracteres maximos permitidos.',
            'cnpj.required' => 'O campo CNPJ é obrigatório.',
            'logradouro.required' => 'O campo Logradouro é obrigatório.',
            'ie.required' => 'O campo Inscrição Estadual é obrigatório.',
            'logradouro.max' => '80 caracteres maximos permitidos.',
            'numero.required' => 'O campo Numero é obrigatório.',
            'cep.required' => 'O campo CEP é obrigatório.',
            'municipio.required' => 'O campo Municipio é obrigatório.',
            'numero.max' => '10 caracteres maximos permitidos.',
            'bairro.required' => 'O campo Bairro é obrigatório.',
            'bairro.max' => '50 caracteres maximos permitidos.',
            'fone.required' => 'O campo Telefone é obrigatório.',
            'fone.max' => '20 caracteres maximos permitidos.',

            'uf.required' => 'O campo UF é obrigatório.',
            'uf.max' => 'UF inválida.',
            'uf.min' => 'UF inválida.',

            'pais.required' => 'O campo Pais é obrigatório.',
            'codPais.required' => 'O campo Código do Pais é obrigatório.',
            'codMun.required' => 'O campo Código do Municipio é obrigatório.',
            'rntrc.max' => '12 caracteres maximos permitidos.',
            'ultimo_numero_nfe.required' => 'Campo obrigatório.',
            'ultimo_numero_nfe.required' => 'Campo obrigatório.',
            'ultimo_numero_nfce.required' => 'Campo obrigatório.',
            'ultimo_numero_cte.required' => 'Campo obrigatório.',
            'ultimo_numero_mdfe.required' => 'Campo obrigatório.',
            'numero_serie_nfe.required' => 'Campo obrigatório.',
            'numero_serie_nfe.max' => 'Maximo de 3 Digitos.',
            'numero_serie_nfce.required' => 'Campo obrigatório.',
            'numero_serie_nfce.max' => 'Maximo de 3 Digitos.',
            'numero_serie_cte.required' => 'Campo obrigatório.',
            'numero_serie_cte.max' => 'Maximo de 3 Digitos.',
            'numero_serie_mdfe.required' => 'Campo obrigatório.',
            'numero_serie_mdfe.max' => 'Maximo de 3 Digitos.',
            'csc.required' => 'O CSC é obrigatório.',
            'csc_id.required' => 'O CSCID é obrigatório.',
            'file.max' => 'Upload de até 300KB.',
            'email.required' => 'Campo obrigatório.',
            'email.max' => 'Máximo de 60caracteres.',
            'email.email' => 'Email inválido.',
        ];

        $this->validate($request, $rules, $messages);
    }

    public function certificado(){
        $escritorio = EscritorioContabil::
        where('empresa_id', $this->empresa_id)
            ->first();
        return view('configNota/upload', compact('escritorio'))
            ->with('title', 'Upload de Certificado');
    }

    public function download(){
        $certificado = Certificado::
        where('empresa_id', $this->empresa_id)
            ->first();
        // echo "Senha: " . $certificado->senha;
        try{
            safe_file_put_contents(public_path('cd.bin'), $certificado->arquivo);
            return response()->download(public_path('cd.bin'));
        }catch(\Exception $e){
            echo $e->getMessage();
        }
    }

    public function senha(){
        $certificado = Certificado::
        where('empresa_id', $this->empresa_id)
            ->first();
        echo "Senha: " . $certificado->senha;

    }

    public function saveCertificado(Request $request){

        if($request->hasFile('file') && strlen($request->senha) > 0){

            $enviarCertificado = $request->enviar_certificado_contabilidade;

            $file = $request->file('file');
            $temp = safe_file_get_contents($file);

            $extensao = $file->getClientOriginalExtension();

            $config = ConfigNota::
            where('empresa_id', $request->empresa_id)
                ->first();

            $cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

            $fileName = "$cnpj.$extensao";

            $file->move(public_path('certificados'), $fileName);

            $res = Certificado::create([
                'senha' => $request->senha,
                'arquivo' => $temp,
                'file_name' => $fileName,
                'empresa_id' => $request->empresa_id
            ]);

            if($enviarCertificado){
                $this->enviarCertificadoEmail($fileName, $request->senha);
            }

            if($res){
                session()->flash("mensagem_sucesso", "Upload de certificado realizado!");
                return redirect('/configNF');
            }
        }else{
            session()->flash("mensagem_erro", "Envie o arquivo e senha por favor!");
            return redirect('/configNF/certificado');
        }
    }

    public function enviarCertificado(){
        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
            ->first();

        $cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

        $files = array_diff(scandir(public_path('certificados')), array('.', '..'));
        $certificados = [];
        foreach ($files as $file) {
            $name_file = explode(".", $file);
            if($name_file[0] == $cnpj){
                array_push($certificados, $file);
            }
        }

        // if(file_exists(public_path('certificados/').$cnpj. '.p12')){
        // 	$fileName = $cnpj. '.p12';
        // }
        // elseif(file_exists(public_path('certificados/').$cnpj. '.pfx')){
        // 	$fileName = $cnpj. '.pfx';
        // }
        // elseif(file_exists(public_path('certificados/').$cnpj. '.bin')){
        // 	$fileName = $cnpj. '.bin';
        // }else{
        // 	echo "Nenhum arquivo encontrado!";
        // 	die;
        // }

        $certificado = Certificado::
        where('empresa_id', $this->empresa_id)
            ->first();

        $fileName = $certificado->file_name;

        if($fileName == ''){
            session()->flash("mensagem_erro", "Certificado sem caminho definido, faça o upload novamente!");
            return redirect()->back();
        }

        $cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

        $this->enviarCertificadoEmail($fileName, $certificado->senha);
        session()->flash("mensagem_sucesso", "Certificado enviado!");
        return redirect()->back();
    }

    private function enviarCertificadoEmail($fileName, $senha){
        $empresa = Empresa::findOrFail($this->empresa_id);
        $escritorio = EscritorioContabil::
        where('empresa_id', $this->empresa_id)
            ->first();
        if($escritorio == null){
            session()->flash("mensagem_erro", "Escritório não configurado!");
            return redirect()->back();
        }
        $email = $escritorio->email;
        Mail::send('mail.certificado', ['senha' => $senha, 'empresa' => $empresa->nome], function($m) use ($email, $fileName){

            $nomeEmpresa = env('MAIL_NAME');
            $nomeEmpresa = str_replace("_", " ",  $nomeEmpresa);
            $nomeEmpresa = str_replace("_", " ",  $nomeEmpresa);
            $emailEnvio = env('MAIL_USERNAME');

            $m->from($emailEnvio, $nomeEmpresa);
            $m->subject("Envio de Certificado");
            $m->attach(public_path('certificados/').$fileName);

            $m->to($email);
        });
    }

    public function deleteCertificado(){
        Certificado::
        where('empresa_id', $this->empresa_id)
            ->delete();
        session()->flash("mensagem_sucesso", "Certificado Removido!");
        return redirect('configNF');
    }

    public function teste()
    {
        $config = CashBackConfig::where('empresa_id', $this->empresa_id)->first();
        $cashBackCliente = CashBackCliente::first();

        if (!$config || !$cashBackCliente || !$cashBackCliente->cliente) {
            return response()->json(['success' => false, 'message' => 'Dados de cashback não encontrados.'], 404);
        }

        $number = preg_replace('/[^0-9]/', '', (string) $cashBackCliente->cliente->celular);
        $message = (string) $config->mensagem_padrao_whatsapp;
        $message = str_replace("{credito}", moeda($cashBackCliente->valor_credito), $message);
        $message = str_replace("{expiracao}", __date($cashBackCliente->data_expiracao, 0), $message);
        $message = str_replace("{nome}", $cashBackCliente->cliente->razao_social, $message);

        $result = app(\App\Utils\WhatsAppUtil::class)->sendMessage(
            $number,
            $message,
            (int) $this->empresa_id
        );

        return response($result, 200)->header('Content-Type', 'application/json');
    }

    public function testeEmail(){

        $mailDriver = env("MAIL_HOST");
        $mailHost = env("MAIL_DRIVER");
        $mailPort = env("MAIL_PORT");
        $mailUsername = env("MAIL_USERNAME");
        $mailPass = env("MAIL_PASSWORD");
        $mailCpt = env("MAIL_ENCRYPTION");
        $mailName = env("MAIL_NAME");

        if($mailDriver == '') return response()->json("Configure no .env MAIL_HOST", 403);
        if($mailHost == '') return response()->json("Configure no .env MAIL_DRIVER", 403);
        if($mailPort == '') return response()->json("Configure no .env MAIL_PORT", 403);
        if($mailUsername == '') return response()->json("Configure no .env MAIL_USERNAME", 403);
        if($mailPass == '') return response()->json("Configure no .env MAIL_PASSWORD", 403);
        if($mailCpt == '') return response()->json("Configure no .env MAIL_ENCRYPTION", 403);
        if($mailName == '') return response()->json("Configure no .env MAIL_NAME", 403);

        try{
            Mail::send('mail.teste', [], function($m){
                $nomeEmail = env("MAIL_NAME");
                $mail = env("MAIL_USERNAME");
                $nomeEmail = str_replace("_", " ", $nomeEmail);
                $m->from(env('MAIL_USERNAME'), $nomeEmail);
                $m->subject('Teste de email');
                $m->to($mail);
            });
        }catch(\Exception $e){
            return response()->json($e->getMessage(), 403);
        }

    }

    public function removeLogo($id){
        $config = ConfigNota::find($id);

        if($config->logo != ''){
            if(file_exists(public_path('logos/').$config->logo)){
                unlink(public_path('logos/').$config->logo);
            }
        }
        $config->logo = '';
        $config->save();
        session()->flash("mensagem_sucesso", "Logo removida!");
        return redirect('/configNF');
    }

    public function removeSenha($id){
        $config = ConfigNota::find($id);

        $config->senha_remover = '';
        $config->save();
        session()->flash("mensagem_sucesso", "Senha removida!");
        return redirect('/configNF');
    }

    public function verificaSenha(Request $request){

        $config = ConfigNota::
        where('senha_remover', md5($request->senha))
            ->where('empresa_id', $this->empresa_id)
            ->first();

        if($config != null){
            return response()->json("ok", 200);
        }else{
            return response()->json("", 401);
        }
    }

    public function verificaSenhaAcesso(){
        try{
            $config = ConfigNota::
            where('empresa_id', $this->empresa_id)
                ->first();

            if($config->senha_remover != null && $config->senha_remover != ''){
                return response()->json("sim", 200);
            }
            return response()->json(null, 404);
        }catch(\Exception $e){
            return response()->json($e->getMessage(), 401);
        }
    }

}
