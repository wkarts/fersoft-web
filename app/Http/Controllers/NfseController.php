<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Certificado;
use NFePHP\Common\Certificate;
use App\Models\ConfigNota;
use App\Models\Cliente;
use App\Models\Servico;
use App\Models\NfseServico;
use App\Models\OrdemServico;
use App\Models\Nfse as NotaServico;
use App\Models\CategoriaConta;
use App\Models\Tributacao;
use App\Services\Nfse\Drivers\SalvadorTools;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Nfse\NfseTaxCalculator;
use Mail;

class NfseController extends Controller
{
    protected $empresa_id = null;

    public function __construct(){
        if(!is_dir(public_path('nfse_pdf'))){
            mkdir(public_path('nfse_pdf'), 0777, true);
        }
        if(!is_dir(public_path('nfse_doc'))){
            mkdir(public_path('nfse_doc'), 0777, true);
        }

        $this->middleware(function ($request, $next) {
            $this->empresa_id = $request->empresa_id ?? session('user_logged')['empresa'] ?? null;
            $value = session('user_logged');
            if(!$value){
                return redirect("/login");
            }
            return $next($request);
        });
    }

    public function index(){
        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();

        if($config == null){
            session()->flash('mensagem_erro', 'Realize a configuração do emitente!');
            return redirect()->back();
        }

        $data = NotaServico::where('empresa_id', $this->empresa_id)
            ->orderBy('id', 'desc')
            ->paginate(30);

        $total = 0;
        foreach($data as $item){
            $total += $item->valor_total;
        }

        $certificado = Certificado::where('empresa_id', $this->empresa_id)->first();
        $clientes = Cliente::where('empresa_id', $this->empresa_id)->get();

        $estado = 'TODOS';
        return view('nfse.index', compact('data', 'certificado', 'config', 'estado', 'total'))
            ->with('links', true)
            ->with('clientes', $clientes)
            ->with('title', 'NFSe');
    }

    public function filtro(Request $request){
        $dataInicial = $request->data_inicial;
        $dataFinal = $request->data_final;
        $estado = $request->estado;
        $cliente_id = $request->cliente_id;

        $data = NotaServico::where('nfses.empresa_id', $this->empresa_id)
            ->select('nfses.*');

        if(($dataInicial) && ($dataFinal)){
            $data->whereBetween('created_at', [
                $this->parseDate($dataInicial),
                $this->parseDate($dataFinal, true)
            ]);
        }

        if($estado){
            $data->where('estado', $estado);
        }

        if($cliente_id != 'null'){
            $data->where('cliente_id', $cliente_id);
        }

        $data = $data->get();

        $total = 0;
        foreach($data as $item){
            $total += $item->valor_total;
        }

        $certificado = Certificado::where('empresa_id', $this->empresa_id)->first();
        $clientes = Cliente::where('empresa_id', $this->empresa_id)->get();
        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();

        return view('nfse.index', compact('data', 'certificado', 'config'))
            ->with('dataInicial', $dataInicial)
            ->with('dataFinal', $dataFinal)
            ->with('estado', $estado)
            ->with('total', $total)
            ->with('cliente_id', $cliente_id)
            ->with('clientes', $clientes)
            ->with('tipoPesquisa', $request->tipo_pesquisa)
            ->with('title', 'NFSe');
    }

    private function parseDate($date, $plusDay = false){
        if($plusDay == false)
            return date('Y-m-d', strtotime(str_replace("/", "-", $date)));
        else
            return date('Y-m-d', strtotime("+1 day",strtotime(str_replace("/", "-", $date))));
    }

    public function create(){
        $clientes = Cliente::where('empresa_id', $this->empresa_id)
            ->orderBy('razao_social', 'asc')
            ->where('inativo', false)
            ->get();

        $servicos = Servico::where('empresa_id', $this->empresa_id)
            ->orderBy('nome', 'desc')
            ->get();

        $categorias = CategoriaConta::where('empresa_id', $this->empresa_id)->get();

        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();
        $tiposPagamento = ['Dinheiro', 'Boleto', 'Cartão de Crédito', 'Cartão de Débito', 'Pix', 'Transferência'];

        return view('nfse.create', compact('clientes', 'config', 'servicos', 'tiposPagamento', 'categorias'))
            ->with('title', 'Nova NFSe');
    }

    public function store(Request $request){
        $this->_validate($request);
        try{
            $totalServico = (float)__replace($request->valor_servico);

            // Trava Anti-Duplicação
            $notaExistente = NotaServico::where('empresa_id', $this->empresa_id)
                ->where('cliente_id', $request->cliente)
                ->where('valor_total', $totalServico)
                ->where('created_at', '>=', now()->subSeconds(5))
                ->first();

            if ($notaExistente) {
                session()->flash('mensagem_sucesso', 'NFS-e gerada com sucesso!');
                return redirect('/nfse');
            }

            DB::transaction(function () use ($request, $totalServico) {
                $usuario_id = session('user_logged')['id'] ?? null;
                $filial_id = $request->input('filial_id') ?? (session('user_logged')['filial'] ?? null);

                $nfse = NotaServico::create([
                    'empresa_id' => $this->empresa_id,
                    'filial_id' => $filial_id,
                    'valor_total' => $totalServico,
                    'estado' => 'novo',
                    'serie' => '1',
                    'codigo_verificacao' => '',
                    'numero_nfse' => 0,
                    'url_xml' => '',
                    'url_pdf_nfse' => '',
                    'url_pdf_rps' => '',
                    'cliente_id' => $request->cliente,
                    'natureza_operacao' => $request->natureza_operacao,
                    'documento' => $request->documento,
                    'razao_social' => $request->razao_social,
                    'im' => $request->im ?? '',
                    'ie' => $request->ie ?? '',
                    'cep' => $request->cep ?? '',
                    'rua' => $request->rua,
                    'numero' => $request->numero,
                    'bairro' => $request->bairro,
                    'complemento' => $request->complemento ?? '',
                    'cidade_id' => $request->cidade_id,
                    'email' => $request->email ?? '',
                    'telefone' => $request->telefone ?? ''
                ]);

                NfseServico::create([
                    'nfse_id' => $nfse->id,
                    'discriminacao' => $request->discriminacao,
                    'valor_servico' => $totalServico,
                    'servico_id' => $request->servico_id,
                    'codigo_cnae' => $request->codigo_cnae ?? '',
                    'codigo_servico' => $request->codigo_servico ?? '',
                    'codigo_tributacao_municipio' => $request->codigo_tributacao_municipio ?? '',
                    'exigibilidade_iss' => $request->exigibilidade_iss ?? 1,
                    'iss_retido' => $request->iss_retido ?? 2,
                    'data_competencia' => $request->data_competencia ?? null,
                    'estado_local_prestacao_servico' => $request->estado_local_prestacao_servico ?? '',
                    'cidade_local_prestacao_servico' => $request->cidade_local_prestacao_servico ?? '',
                    'valor_deducoes' => $request->valor_deducoes ? __replace($request->valor_deducoes) : 0,
                    'desconto_incondicional' => $request->desconto_incondicional ? __replace($request->desconto_incondicional) : 0,
                    'desconto_condicional' => $request->desconto_condicional ? __replace($request->desconto_condicional) : 0,
                    'outras_retencoes' => $request->outras_retencoes ? __replace($request->outras_retencoes) : 0,
                    'aliquota_iss' => $request->aliquota_iss ? __replace($request->aliquota_iss) : 0,
                    'aliquota_pis' => $request->aliquota_pis ? __replace($request->aliquota_pis) : 0,
                    'aliquota_cofins' => $request->aliquota_cofins ? __replace($request->aliquota_cofins) : 0,
                    'aliquota_inss' => $request->aliquota_inss ? __replace($request->aliquota_inss) : 0,
                    'aliquota_ir' => $request->aliquota_ir ? __replace($request->aliquota_ir) : 0,
                    'aliquota_csll' => $request->aliquota_csll ? __replace($request->aliquota_csll) : 0,
                    'intermediador' => $request->intermediador ?? 'n',
                    'documento_intermediador' => $request->documento_intermediador ?? '',
                    'nome_intermediador' => $request->nome_intermediador ?? '',
                    'im_intermediador' => $request->im_intermediador ?? '',
                    'responsavel_retencao_iss' => $request->responsavel_retencao_iss ?? 1,
                ]);

                // Gravação Segura no Contas a Receber
                if ($request->input('gerar_contas_receber') == '1' && $request->has('parcelas') && is_array($request->parcelas)) {
                    $categoriaId = $request->categoria_conta_id;
                    if (empty($categoriaId)) {
                        $primeiraCat = DB::table('categoria_contas')->where('empresa_id', $this->empresa_id)->first();
                        $categoriaId = $primeiraCat->id ?? null;
                    }

                    foreach ($request->parcelas as $key => $parcela) {
                        $vlParcela = (float) __replace($parcela['valor'] ?? '0');
                        $numParcela = $key + 1;
                        $totalParcelas = count($request->parcelas);

                        DB::table('conta_recebers')->insert([
                            'empresa_id'      => $this->empresa_id,
                            'filial_id'       => $filial_id,
                            'cliente_id'      => $request->cliente,
                            'usuario_id'      => $usuario_id,
                            'categoria_id'    => $categoriaId,
                            'valor_integral'  => $vlParcela,
                            'data_vencimento' => $parcela['vencimento'],
                            'status'          => 0,
                            'referencia'      => "NFS-e #" . $nfse->id . " (Parcela {$numParcela}/{$totalParcelas})",
                            'tipo_pagamento'  => $request->tipo_pagamento ?? 'Boleto',
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ]);
                    }
                }

                if(isset($request->os_id)){
                    $ordem = OrdemServico::findOrFail($request->os_id);
                    $ordem->nfse_id = $nfse->id;
                    $ordem->save();
                }
            });

            session()->flash('mensagem_sucesso', 'NFS-e criada com sucesso!');
        }catch(\Throwable $e){
            Log::error("Erro store NFS-e: " . $e->getMessage());
            session()->flash('mensagem_erro', 'Erro ao salvar NFS-e: ' . $e->getMessage());
        }
        return redirect('/nfse');
    }

    /**
     * Exclui NFS-e não aprovada e seus vínculos
     */
    public function delete($id)
    {
        try {
            $item = NotaServico::where('empresa_id', $this->empresa_id)->findOrFail($id);

            if ($item->estado === 'aprovado') {
                session()->flash('mensagem_erro', 'NFS-e autorizada não pode ser excluída. Utilize a opção Cancelar!');
                return redirect()->back();
            }

            DB::transaction(function () use ($item) {
                // Remove os serviços vinculados
                NfseServico::where('nfse_id', $item->id)->delete();

                // Remove os lançamentos no contas a receber
                DB::table('conta_recebers')
                    ->where('empresa_id', $this->empresa_id)
                    ->where('referencia', 'like', "NFS-e #" . $item->id . "%")
                    ->delete();

                // Remove arquivo XML local se existir
                $xmlPath = public_path('nfse_doc/NFSe_' . $item->id . '.xml');
                if (file_exists($xmlPath)) {
                    @unlink($xmlPath);
                }

                $item->delete();
            });

            session()->flash('mensagem_sucesso', 'NFS-e #' . $id . ' excluída com sucesso!');
        } catch (\Throwable $e) {
            Log::error("Erro ao excluir NFS-e #{$id}: " . $e->getMessage());
            session()->flash('mensagem_erro', 'Erro ao excluir NFS-e: ' . $e->getMessage());
        }

        return redirect('/nfse');
    }

    /**
     * Atualização da NFS-e
     */
    public function update(Request $request, $id)
    {
        $this->_validate($request);
        try {
            $item = NotaServico::where('empresa_id', $this->empresa_id)->findOrFail($id);

            if ($item->estado === 'aprovado') {
                session()->flash('mensagem_erro', 'NFS-e autorizada não pode ser alterada!');
                return redirect('/nfse');
            }

            $totalServico = (float)__replace($request->valor_servico);

            $item->update([
                'valor_total'       => $totalServico,
                'cliente_id'        => $request->cliente,
                'natureza_operacao' => $request->natureza_operacao,
                'documento'         => $request->documento,
                'razao_social'      => $request->razao_social,
                'im'                => $request->im ?? '',
                'ie'                => $request->ie ?? '',
                'cep'               => $request->cep ?? '',
                'rua'               => $request->rua,
                'numero'            => $request->numero,
                'bairro'            => $request->bairro,
                'complemento'       => $request->complemento ?? '',
                'cidade_id'         => $request->cidade_id,
                'email'             => $request->email ?? '',
                'telefone'          => $request->telefone ?? ''
            ]);

            NfseServico::where('nfse_id', $item->id)->update([
                'discriminacao'                  => $request->discriminacao,
                'valor_servico'                  => $totalServico,
                'servico_id'                     => $request->servico_id,
                'codigo_cnae'                    => $request->codigo_cnae ?? '',
                'codigo_servico'                 => $request->codigo_servico ?? '',
                'codigo_tributacao_municipio'    => $request->codigo_tributacao_municipio ?? '',
                'exigibilidade_iss'              => $request->exigibilidade_iss ?? 1,
                'iss_retido'                     => $request->iss_retido ?? 2,
                'data_competencia'               => $request->data_competencia ?? null,
                'estado_local_prestacao_servico' => $request->estado_local_prestacao_servico ?? '',
                'cidade_local_prestacao_servico' => $request->cidade_local_prestacao_servico ?? '',
            ]);

            session()->flash('mensagem_sucesso', 'NFS-e atualizada com sucesso!');
        } catch (\Throwable $e) {
            session()->flash('mensagem_erro', 'Erro ao atualizar NFS-e: ' . $e->getMessage());
        }

        return redirect('/nfse');
    }

    /**
     * Envio da NFS-e via WhatsApp com PDF em anexo
     */
    public function enviarWhatsapp(Request $request, $id)
    {
        Log::info("=== INICIANDO ENVIO DE WHATSAPP NFS-e (#{$id}) ===");

        try {
            set_time_limit(60);

            $item = NotaServico::with(['servico'])->where('empresa_id', $this->empresa_id)->findOrFail($id);
            $cliente = Cliente::where('empresa_id', $this->empresa_id)->find($item->cliente_id);

            $numeroOriginal = $cliente->celular ?? $cliente->whatsapp ?? $cliente->telefone ?? $item->telefone;
            $numero = preg_replace('/[^0-9]/', '', $numeroOriginal);

            if (empty($numero) || strlen($numero) < 10) {
                return redirect()->back()->with('mensagem_erro', "O cliente '{$item->razao_social}' não possui número de WhatsApp válido.");
            }

            if (substr($numero, 0, 2) !== '55') {
                $numero = "55" . $numero;
            }

            $empresa = DB::table('empresas')->where('id', $this->empresa_id)->first();
            $nomeEmpresa = $empresa->nome ?? $empresa->razao_social ?? 'FerSoft ERP';
            $valorFormatado = number_format($item->valor_total, 2, ',', '.');
            $numNota = $item->numero_nfse > 0 ? $item->numero_nfse : $item->id;

            $caminhoPdf = public_path('nfse_pdf/NFSe_' . $item->id . '.pdf');

            $mensagem  = "📄 *NOTA FISCAL DE SERVIÇO ELETRÔNICA (NFS-e) #{$numNota}*\n\n";
            $mensagem .= "Olá, *{$item->razao_social}*!\n\n";
            $mensagem .= "Segue em anexo o documento referente aos serviços prestados:\n\n";
            $mensagem .= "• *Número da Nota:* {$numNota}\n";
            $mensagem .= "• *Valor Total:* R$ {$valorFormatado}\n";
            if (!empty($item->codigo_verificacao)) {
                $mensagem .= "• *Cód. Verificação:* {$item->codigo_verificacao}\n";
            }
            $mensagem .= "\nFicamos à disposição para dúvidas.\n\n";
            $mensagem .= "Atenciosamente,\n*{$nomeEmpresa}*";

            $instanciaWhats = app('\App\Utils\WhatsAppUtil');
            $retornoJson = $instanciaWhats->sendMessage($numero, $mensagem, $this->empresa_id, file_exists($caminhoPdf) ? $caminhoPdf : null);

            $res = json_decode($retornoJson, true);
            if (isset($res['success']) && $res['success'] === false) {
                $msgErro = $res['message'] ?? 'Falha ao enviar arquivo via WhatsApp';
                return redirect()->back()->with('mensagem_erro', "Erro no envio do WhatsApp: " . $msgErro);
            }

            return redirect()->back()->with('mensagem_sucesso', "NFS-e #{$numNota} enviada por WhatsApp para {$item->razao_social}!");

        } catch (\Throwable $e) {
            Log::error("WhatsApp NFS-e #{$id}: Erro - " . $e->getMessage());
            return redirect()->back()->with('mensagem_erro', 'Falha ao enviar por WhatsApp: ' . $e->getMessage());
        }
    }


    public function enviar(Request $request)
    {
        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();
        $certRes = Certificado::where('empresa_id', $this->empresa_id)->first();
        $tributacao = Tributacao::where('empresa_id', $this->empresa_id)->first();

        if (!$config || !$certRes) {
            return response()->json(['motivo' => 'Configurações do emitente ou certificado digital não encontrados.'], 400);
        }

        // 1. CARREGA O REGISTRO DA NFS-E E SEUS VÍNCULOS
        $item = NotaServico::with(['servico.servico', 'cidade'])->findOrFail($request->id);
        $nfseServico = $item->servico;
        $servicoMaster = $nfseServico->servico ?? Servico::find($nfseServico->servico_id);
        $cliente = Cliente::with('cidade')->find($item->cliente_id);

        try {
            // Leitura do Certificado Digital A1
            $certContent = $certRes->arquivo;
            if (file_exists(storage_path('app/public/certificados/' . $certRes->arquivo))) {
                $certContent = file_get_contents(storage_path('app/public/certificados/' . $certRes->arquivo));
            }
            $certificate = Certificate::readPfx($certContent, $certRes->senha);

            // 2. IDENTIFICAÇÃO DO MUNICÍPIO EMITENTE
            $codigoIbgeEmitente = null;
            if (!empty($config->cidade_id)) {
                $cidadeObj = DB::table('cidades')->where('id', $config->cidade_id)->first();
                if ($cidadeObj) {
                    $codigoIbgeEmitente = $cidadeObj->codigo ?? $cidadeObj->codigo_ibge ?? null;
                }
            }
            if (!$codigoIbgeEmitente) {
                $codigoIbgeEmitente = $config->codMun ?? $config->codigo_ibge_municipio ?? '2919207';
            }
            $codigoIbgeEmitente = str_pad(preg_replace('/\D/', '', $codigoIbgeEmitente), 7, '0', STR_PAD_LEFT);

            // 3. SELEÇÃO DINÂMICA DO PROVEDOR
            $configArray = $config->toArray();
            switch ((string) $codigoIbgeEmitente) {
                case '2910057': // Dias d'Ávila - BA
                    $provedor = 'diasdavila';
                    $configArray['usuario_saatri'] = '05950998669';
                    $configArray['senha_saatri'] = 'Ciclos24';
                    break;

                case '2927408': // Salvador - BA
                    $provedor = 'salvador';
                    break;

                case '2919207': // Lauro de Freitas - BA (Portal Nacional)
                default:
                    $provedor = 'nacional';
                    break;
            }

            $emissor = \App\Services\Nfse\NfseServiceFactory::criar($provedor, $configArray, $certificate);

            // 4. MAPEAMENTO DO REGIME TRIBUTÁRIO (0=Simples, 1=Normal, 2=MEI)
            $regimeErp = (int) ($tributacao->regime ?? $config->opcao_simples_nacional ?? 0);
            if ($regimeErp === 0) {
                $opSimpNac = 2;   // Optante - ME ou EPP (Simples Nacional)
                $regApTribSN = 1; // DAS
            } elseif ($regimeErp === 2) {
                $opSimpNac = 3;   // Optante - MEI
                $regApTribSN = 1; // DAS
            } else {
                $opSimpNac = 1;   // Não Optante (Regime Normal)
                $regApTribSN = 1;
            }

            // 5. DOCUMENTO E ENDEREÇO DO TOMADOR
            $docTomador = preg_replace('/\D/', '', $item->documento ?? $cliente->cpf_cnpj ?? '');
            $tipoDocTomador = strlen($docTomador) > 11 ? 'CNPJ' : 'CPF';
            $codigoIbgeTomador = preg_replace('/\D/', '', $item->cidade->codigo ?? $cliente->cidade->codigo ?? $codigoIbgeEmitente);

            // 6. CÁLCULO DE VALORES E REFORMA TRIBUTÁRIA USANDO O SERVICE
            $valorServico = (float) $item->valor_total;
            $vDescIncond = (float) ($nfseServico->desconto_incondicional ?? 0);
            $vDeducoes   = (float) ($nfseServico->valor_deducoes ?? 0);

            // Instancia o service oficial da Reforma Tributária
            $rtService = new \App\Services\ReformaTributariaService();

            // Monta um array com os dados para o calculador oficial do service
            $itemCalculo = [
                'valor_total' => $valorServico,
                'cst_ibs_cbs' => $servicoMaster->cst_ibscbs ?? $tributacao->cst_ibs_cbs ?? '000',
                'class_trib_ibs_cbs' => $servicoMaster->codigo_class_trib ?? $tributacao->class_trib_ibs_cbs ?? '000001',
                'aliq_cbs' => (float)($tributacao->aliq_cbs ?? 0.9000),
                'aliq_ibs_uf' => (float)($tributacao->aliq_ibs_uf ?? 0.1000),
                'aliq_ibs_mun' => (float)($tributacao->aliq_ibs_mun ?? 0.0000),
            ];

            // Executa o motor de cálculo oficial do service
            $rtService->calcularItem($itemCalculo, $this->empresa_id);

            $baseCalculoIbsCbs = $itemCalculo['bc_ibs_cbs'] ?? round($valorServico - $vDeducoes - $vDescIncond, 2);
            $valIbsEstadual    = $itemCalculo['valor_ibs_uf'] ?? round($baseCalculoIbsCbs * 0.001, 2);
            $valCbs            = $itemCalculo['valor_cbs'] ?? round($baseCalculoIbsCbs * 0.009, 2);

            $aliquotaIss = (float) ($nfseServico->aliquota_iss ?? $servicoMaster->aliquota_iss ?? 3.00);
            $issRetido   = ((int) ($nfseServico->iss_retido ?? 0) === 1);

            // Retenções na Fonte
            $vRetCP   = (float) ($nfseServico->aliquota_inss ?? $servicoMaster->aliquota_inss ?? 0) > 0
                ? round($valorServico * (($nfseServico->aliquota_inss ?? $servicoMaster->aliquota_inss) / 100), 2) : 0.00;
            $vRetIRRF = (float) ($nfseServico->aliquota_ir ?? 0) > 0
                ? round($valorServico * ($nfseServico->aliquota_ir / 100), 2) : 0.00;
            $vRetCSLL = (float) ($nfseServico->aliquota_csll ?? 0) > 0
                ? round($valorServico * ($nfseServico->aliquota_csll / 100), 2) : 0.00;

            // PIS/COFINS
            $cstPisCofins = ($opSimpNac === 1) ? '01' : '07';
            $vPis    = (float) ($nfseServico->aliquota_pis ?? $servicoMaster->aliquota_pis ?? 0) > 0
                ? round($valorServico * (($nfseServico->aliquota_pis ?? $servicoMaster->aliquota_pis) / 100), 2) : 0.00;
            $vCofins = (float) ($nfseServico->aliquota_cofins ?? $servicoMaster->aliquota_cofins ?? 0) > 0
                ? round($valorServico * (($nfseServico->aliquota_cofins ?? $servicoMaster->aliquota_cofins) / 100), 2) : 0.00;

            // 7. MONTAGEM DA ESTRUTURA DPS (PADRÃO NACIONAL)
            $dpsData = [
                'infDPS' => [
                    'dhEmi'    => date('Y-m-d\TH:i:sP'),
                    'dCompet'  => $nfseServico->data_competencia ? date('Y-m-d', strtotime($nfseServico->data_competencia)) : date('Y-m-d'),
                    'tpEmit'   => 1,
                    'cLocEmi'  => $codigoIbgeEmitente,
                    'serie'    => $config->numero_serie_nfse ?? $item->serie ?? '1',
                    'nDPS'     => (int) ($config->ultimo_numero_nfse ? $config->ultimo_numero_nfse + 1 : $item->id),

                    // Prestador
                    'prest' => [
                        'CNPJ'    => preg_replace('/\D/', '', $config->cnpj),
                        'IM'      => preg_replace('/\D/', '', $config->inscricao_municipal ?? ''),
                        'regTrib' => [
                            'opSimpNac'   => $opSimpNac,
                            'regApTribSN' => $regApTribSN,
                            'regEspTrib'  => (int) ($config->regime_especial_tributacao ?? 0),
                        ]
                    ],

                    // Tomador
                    'toma' => [
                        $tipoDocTomador => $docTomador,
                        'xNome' => $item->razao_social ?? $cliente->razao_social ?? 'CONSUMIDOR',
                        'ender' => [
                            'cMun'    => $codigoIbgeTomador,
                            'CEP'     => preg_replace('/\D/', '', $item->cep ?? $cliente->cep ?? '40000000'),
                            'xLgr'    => $item->rua ?? $cliente->rua ?? 'Logradouro',
                            'nro'     => $item->numero ?? $cliente->numero ?? 'S/N',
                            'xBairro' => $item->bairro ?? $cliente->bairro ?? 'Centro'
                        ]
                    ],

                    // Serviço
                    'serv' => [
                        'locPrest' => [
                            'cLocPrest' => preg_replace('/\D/', '', $nfseServico->cidade_local_prestacao_servico ?? $codigoIbgeEmitente),
                        ],
                        'cServ' => [
                            'cTribNac'  => preg_replace('/\D/', '', $nfseServico->codigo_servico ?? $servicoMaster->codigo_servico ?? '140501'),
                            'xDescServ' => $nfseServico->discriminacao ?? $servicoMaster->nome ?? 'PRESTAÇÃO DE SERVIÇOS',
                            'cNBS'      => preg_replace('/\D/', '', $servicoMaster->codigo_nbs ?? '101010100'),
                        ]
                    ],

                    // Valores e Tributos
                    'valores' => [
                        'vServPrest' => [
                            'vServ' => number_format($valorServico, 2, '.', '')
                        ],
                        'trib' => [
                            'tribMun' => [
                                'tribISSQN'  => (int) ($nfseServico->exigibilidade_iss ?? 1),
                                'tpRetISSQN' => $issRetido ? 2 : 1,
                                'pAliq'      => number_format($aliquotaIss, 2, '.', '')
                            ],
                            'vDescIncond' => number_format($vDescIncond, 2, '.', ''),
                            'vDed'        => number_format($vDeducoes, 2, '.', ''),
                            'tribFed'     => [
                                'vRetCP'    => number_format($vRetCP, 2, '.', ''),
                                'vRetIRRF'  => number_format($vRetIRRF, 2, '.', ''),
                                'vRetCSLL'  => number_format($vRetCSLL, 2, '.', ''),
                                'piscofins' => [
                                    'CST'     => $cstPisCofins,
                                    'vPIS'    => number_format($vPis, 2, '.', ''),
                                    'vCOFINS' => number_format($vCofins, 2, '.', '')
                                ]
                            ]
                        ]
                    ],

                    // Reforma Tributária (IBS/CBS) - Base de Cálculo e Indicador Oficial
                    'ibscbs' => [
                        'CST'         => $itemCalculo['cst_ibs_cbs'] ?? '000',
                        'cClassTrib'  => $itemCalculo['class_trib_ibs_cbs'] ?? '000001',
                        'cIndOp'      => '050101',
                        'baseCalculo' => number_format($baseCalculoIbsCbs, 2, '.', ''),
                        'percIbsMun'  => '0.00',
                        'valIbsMun'   => '0.00',
                        'percIbsEst'  => '0.10',
                        'valIbsEst'   => number_format($valIbsEstadual, 2, '.', ''),
                        'percCbs'     => '0.90',
                        'valCbs'      => number_format($valCbs, 2, '.', ''),
                    ]
                ]
            ];

            // 8. TRANSMISSÃO
            $resultado = $emissor->emitir($dpsData);

            $xmlRetorno = $resultado['xml_retorno'] ?? (is_string($resultado) ? $resultado : '');
            $xmlDecodificado = html_entity_decode($xmlRetorno);

            // 9. CAPTURA DE RETORNO COM ERRO OU REJEIÇÃO
            $temErro = false;
            $motivoRejeicao = '';

            if (stripos($xmlDecodificado, '<Sucesso>false</Sucesso>') !== false
                || stripos($xmlDecodificado, '<Erro>') !== false
                || stripos($xmlDecodificado, '<faultstring>') !== false) {

                $temErro = true;
                preg_match('/<Codigo>(.*?)<\/Codigo>/', $xmlDecodificado, $mCod);
                preg_match('/<Descricao>(.*?)<\/Descricao>/', $xmlDecodificado, $mDesc);

                $cod = $mCod[1] ?? '999';
                $desc = $mDesc[1] ?? 'Rejeição no WebService da Prefeitura.';
                $motivoRejeicao = "Erro {$cod}: {$desc}";
            }

            if ($temErro || empty($resultado['numero_nfse'])) {
                $item->estado = 'rejeitado';
                $item->numero_nfse = 0;
                $item->motivo_rejeicao = $motivoRejeicao ?: 'NFS-e rejeitada (Sem confirmação de emissão).';
                $item->save();

                return response()->json([
                    'erro' => true,
                    'motivo' => $item->motivo_rejeicao,
                    'ajuda' => 'Verifique se os dados de endereço, regime tributário e códigos de serviço estão válidos.'
                ], 422);
            }

            // 10. TRATAMENTO DO RETORNO DA DPS (CÓDIGO 0 = AGUARDANDO SEFIN / PROCESSANDO)
            $numeroNfse = $resultado['numero_nfse'];
            $codVerificacao = $resultado['codigo_verificacao']
                ?? $resultado['resposta_sefaz']['chaveAcesso']
                ?? $resultado['chave']
                ?? $emissor->getChave();

            // Verifica se foi código 0 (sucesso assíncrono)
            $isAssincrono = (strpos($xmlDecodificado, '<Codigo>0</Codigo>') !== false);

            $urlImpressao = "https://diasdavila.saatri.com.br/ConsultaNota/Visualizar?codigo=" . $codVerificacao;

            // Nome do XML salvo
            $xmlName = (strlen($codVerificacao) >= 40) ? $codVerificacao . '.xml' : 'NFSe_' . $item->id . '.xml';
            file_put_contents(public_path('nfse_doc/' . $xmlName), $xmlRetorno);

            if ($isAssincrono || $codVerificacao === 'AGUARDANDO_SINCRONIZACAO_SEFIN') {
                $item->estado = 'processando'; // Fica processando para permitir nova consulta sem travar
                $item->numero_nfse = $numeroNfse;
                $item->codigo_verificacao = $codVerificacao;
                $item->uuid = $xmlName;
                $item->url_xml = asset('nfse_doc/' . $xmlName);
                $item->url_pdf_nfse = $urlImpressao;
                $item->motivo_rejeicao = 'DPS gerada com sucesso. Aguardando sincronização no Ambiente Nacional (SEFIN).';
                $item->save();

                return response()->json([
                    'status' => 'processando',
                    'mensagem' => 'DPS gerada com sucesso e compartilhada com a SEFIN. Utilize o botão Consultar em instantes.'
                ], 200);
            }

            // Aprovada Definitiva
            $item->estado = 'aprovado';
            $item->numero_nfse = $numeroNfse;
            $item->codigo_verificacao = $codVerificacao;
            $item->uuid = $xmlName;
            $item->url_xml = asset('nfse_doc/' . $xmlName);
            $item->url_pdf_nfse = $urlImpressao;
            $item->motivo_rejeicao = null;
            $item->save();

            if (!empty($config->ultimo_numero_nfse) && (int)$resultado['numero_nfse'] > (int)$config->ultimo_numero_nfse) {
                $config->ultimo_numero_nfse = (int)$resultado['numero_nfse'];
                $config->save();
            }

            DB::table('conta_recebers')
                ->where('empresa_id', $this->empresa_id)
                ->where('referencia', 'like', "NFS-e #" . $item->id . "%")
                ->update([
                    'nf_numero'  => $item->numero_nfse,
                    'nf_modelo'  => 55,
                    'updated_at' => now()
                ]);

            return response()->json([
                'status' => 'processado',
                'info_nfse' => [[
                    'numero' => $item->numero_nfse,
                    'codigo_verificacao' => $item->codigo_verificacao,
                    'xml' => $item->url_xml,
                    'pdf_nfse' => route('nfse.imprimir', $item->id)
                ]]
            ], 200);

        } catch (\Throwable $th) {
            $item->estado = 'rejeitado';
            $item->numero_nfse = 0;
            $item->motivo_rejeicao = $th->getMessage();
            $item->save();
            Log::error("Erro na emissão NFSe #{$item->id}: " . $th->getMessage());

            return response()->json([
                'erro' => true,
                'motivo' => $th->getMessage(),
                'ajuda' => 'Falha técnica ou inconsistência de esquema junto ao Portal Nacional.'
            ], 422);
        }
    }


    public function cancelar(Request $request)
    {
        $id = $request->id;
        if (empty($id)) {
            return response()->json(['erro' => true, 'motivo' => 'ID da NFS-e não selecionado.'], 400);
        }

        $item = NotaServico::where('empresa_id', $this->empresa_id)->findOrFail($id);

        if ($item->estado !== 'aprovado') {
            return response()->json(['erro' => true, 'motivo' => 'Apenas notas aprovadas podem ser canceladas.'], 422);
        }

        $chaveAcesso = $item->codigo_verificacao;
        if (empty($chaveAcesso)) {
            return response()->json(['erro' => true, 'motivo' => 'Chave de acesso não localizada para esta nota.'], 422);
        }

        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();
        $certRes = Certificado::where('empresa_id', $this->empresa_id)->first();

        if (!$config || !$certRes) {
            return response()->json(['erro' => true, 'motivo' => 'Certificado digital ou configurações não configurados.'], 400);
        }

        try {
            $certContent = $certRes->arquivo;
            if (file_exists(storage_path('app/public/certificados/' . $certRes->arquivo))) {
                $certContent = file_get_contents(storage_path('app/public/certificados/' . $certRes->arquivo));
            }
            $certificate = Certificate::readPfx($certContent, $certRes->senha);

            $codigoIbge = $config->codMun ?? $config->codigo_ibge_municipio ?? '2919207';
            $configArray = $config->toArray();

            $provedor = match ((string) $codigoIbge) {
                '2910057' => 'diasdavila',
                '2927408' => 'salvador',
                default   => 'nacional',
            };

            $emissor = \App\Services\Nfse\NfseServiceFactory::criar($provedor, $configArray, $certificate);

            $motivosTexto = [
                '1' => 'Erro na emissão',
                '2' => 'Serviço não prestado',
                '4' => 'Duplicidade de nota'
            ];
            $motivoEnvio = $motivosTexto[$request->motivo] ?? 'Erro na emissão';

            // Dispara o cancelamento no Portal Nacional
            $resultado = $emissor->cancelar($chaveAcesso, $motivoEnvio);

            // Atualiza a nota no ERP
            $item->estado = 'cancelado';
            $item->motivo_rejeicao = 'Cancelada: ' . $motivoEnvio;
            $item->save();

            // Inativa contas a receber geradas por essa nota
            DB::table('conta_recebers')
                ->where('empresa_id', $this->empresa_id)
                ->where('referencia', 'like', "NFS-e #" . $item->id . "%")
                ->update(['status' => false, 'updated_at' => now()]);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'NFS-e cancelada com sucesso!'
            ], 200);

        } catch (\Throwable $th) {
            Log::error("Erro no cancelamento da NFS-e #{$item->id}: " . $th->getMessage());

            return response()->json([
                'erro' => true,
                'motivo' => $th->getMessage()
            ], 422);
        }
    }

    public function consultar(Request $request)
    {
        Log::info("=== INICIANDO CONSULTA NFS-e (#{$request->id}) ===");

        $item = NotaServico::where('empresa_id', $this->empresa_id)->findOrFail($request->id);
        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();
        $certRes = Certificado::where('empresa_id', $this->empresa_id)->first();

        try {
            $certContent = file_exists(storage_path('app/public/certificados/' . $certRes->arquivo))
                ? file_get_contents(storage_path('app/public/certificados/' . $certRes->arquivo))
                : $certRes->arquivo;
            $certificate = Certificate::readPfx($certContent, $certRes->senha);

            // Identificação do município emitente
            $codigoIbgeEmitente = null;
            if (!empty($config->cidade_id)) {
                $cidadeObj = DB::table('cidades')->where('id', $config->cidade_id)->first();
                if ($cidadeObj) {
                    $codigoIbgeEmitente = $cidadeObj->codigo ?? $cidadeObj->codigo_ibge ?? null;
                }
            }
            if (!$codigoIbgeEmitente) {
                $codigoIbgeEmitente = $config->codMun ?? $config->codigo_ibge_municipio ?? '2919207';
            }
            $codigoIbgeEmitente = str_pad(preg_replace('/\D/', '', $codigoIbgeEmitente), 7, '0', STR_PAD_LEFT);

            Log::info("Consulta NFS-e: Município detectado = {$codigoIbgeEmitente}");

            $configArray = $config->toArray();
            switch ((string) $codigoIbgeEmitente) {
                case '2910057': // Dias d'Ávila - BA
                    $provedor = 'diasdavila';
                    $configArray['usuario_saatri'] = '05950998669';
                    $configArray['senha_saatri'] = 'Ciclos24';
                    break;
                case '2927408': // Salvador
                    $provedor = 'salvador';
                    break;
                default:
                    $provedor = 'nacional';
                    break;
            }

            Log::info("Consulta NFS-e: Provedor selecionado = {$provedor}");

            $emissor = \App\Services\Nfse\NfseServiceFactory::criar($provedor, $configArray, $certificate);

            // Realiza a consulta no webservice da prefeitura
            $retorno = $emissor->consultar($item->numero_nfse);

            $statusRetorno = strtoupper($retorno['status'] ?? '');
            $isCancelada   = $retorno['cancelada'] ?? false;
            $numeroOficial = $retorno['numero_nfse'] ?? null;
            $codigoVerif   = $retorno['codigo_verificacao'] ?? null;

            // SE A PREFEITURA INDICAR QUE ESTÁ CANCELADA
            if ($isCancelada || $statusRetorno === 'CANCELADO') {
                $item->estado = 'cancelado';
                if ($numeroOficial) $item->numero_nfse = $numeroOficial;
                if ($codigoVerif) $item->codigo_verificacao = $codigoVerif;
                $item->motivo_rejeicao = 'Cancelada diretamente no Portal da Prefeitura de Dias d\'Ávila.';
                $item->save();

                // Atualiza Contas a Receber (inativa os lançamentos ou marca conforme regra de cancelamento)
                DB::table('conta_recebers')
                    ->where('empresa_id', $this->empresa_id)
                    ->where('referencia', 'like', "NFS-e #" . $item->id . "%")
                    ->update(['status' => false, 'updated_at' => now()]);

                return response()->json([
                    'status' => 'CANCELADO',
                    'mensagem' => 'A consulta detetou que a NFS-e foi cancelada no portal. Status atualizado no sistema com sucesso!'
                ]);
            }

            // SE ESTIVER AUTORIZADA
            if ($statusRetorno === 'AUTORIZADO' || !empty($numeroOficial)) {
                $item->estado = 'aprovado';
                $item->numero_nfse = $numeroOficial ?? $item->numero_nfse;
                if (!empty($codigoVerif)) {
                    $item->codigo_verificacao = $codigoVerif;
                }
                $item->motivo_rejeicao = null;
                $item->save();

                DB::table('conta_recebers')
                    ->where('empresa_id', $this->empresa_id)
                    ->where('referencia', 'like', "NFS-e #" . $item->id . "%")
                    ->update([
                        'nf_numero'  => $item->numero_nfse,
                        'nf_modelo'  => 55,
                        'updated_at' => now()
                    ]);

                return response()->json([
                    'status' => 'APROVADO',
                    'mensagem' => "NFS-e consultada com sucesso! Número oficial: {$item->numero_nfse}"
                ]);
            }

            return response()->json([
                'status' => strtoupper($item->estado),
                'mensagem' => 'A nota ainda consta como processando na prefeitura. Tente novamente em instantes.'
            ]);

        } catch (\Throwable $th) {
            Log::error("Erro crítico na consulta NFS-e #{$item->id}: " . $th->getMessage() . " em " . $th->getFile() . ":" . $th->getLine());
            return response()->json([
                'erro' => true,
                'mensagem' => 'Erro ao consultar: ' . $th->getMessage()
            ], 422);
        }
    }

    public function baixarXml($id){
        $item = NotaServico::findOrFail($id);
        $path = public_path('nfse_doc/NFSe_' . $item->id . '.xml');

        if(file_exists($path)){
            return response()->download($path);
        } else {
            return redirect()->back()->with('mensagem_erro', 'Arquivo XML não encontrado!');
        }
    }

    public function imprimir($id)
    {
        $item = NotaServico::where('empresa_id', $this->empresa_id)->findOrFail($id);
        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();
        $cliente = Cliente::with('cidade')->find($item->cliente_id);
        $servicoMaster = $item->servico ? ($item->servico->servico ?? Servico::find($item->servico->servico_id)) : null;

        // 1. RECUPERAÇÃO DA CHAVE E LEITURA DO XML
        $chaveAcesso = $item->codigo_verificacao;

        // Localiza o XML independente de ter sido salvo pelo ID, pela Chave ou UUID
        $xmlPath = public_path('nfse_doc/NFSe_' . $item->id . '.xml');
        if (!file_exists($xmlPath) && !empty($chaveAcesso)) {
            $xmlPath = public_path('nfse_doc/' . $chaveAcesso . '.xml');
        }
        if (!file_exists($xmlPath) && !empty($item->uuid)) {
            $xmlPath = public_path('nfse_doc/' . $item->uuid);
        }

        $descricaoTribNac = null;
        $descricaoNbs     = null;

        // Lê o XML sempre para extrair as tags oficiais retornadas pela SEFIN
        if (file_exists($xmlPath)) {
            $xmlContent = file_get_contents($xmlPath);

            // Chave de acesso (se ainda não estava no banco)
            if (empty($chaveAcesso) || strlen($chaveAcesso) < 40) {
                if (preg_match('/(?:Id="NFS|chaveAcesso="?|chNFSe>)([0-9]{50})/i', $xmlContent, $m)) {
                    $chaveAcesso = $m[1];
                } elseif (preg_match('/([0-9]{50})/', $xmlContent, $m)) {
                    $chaveAcesso = $m[1];
                }
            }

            // Descrição da Tributação Nacional (<xTribNac>)
            if (preg_match('/<xTribNac>(.*?)<\/xTribNac>/is', $xmlContent, $mTrib)) {
                $descricaoTribNac = html_entity_decode(trim($mTrib[1]), ENT_QUOTES, 'UTF-8');
            }

            // Descrição da NBS (<xNBS>)
            if (preg_match('/<xNBS>(.*?)<\/xNBS>/is', $xmlContent, $mNbs)) {
                $descricaoNbs = html_entity_decode(trim($mNbs[1]), ENT_QUOTES, 'UTF-8');
            }
        }

        // Fallbacks de segurança
        if (empty($chaveAcesso)) {
            $chaveAcesso = '29192072205693853000170000000000014026101412894024';
        }

        $codServico = $item->servico->codigo_servico ?? $servicoMaster->codigo_servico ?? '030303';
        if (empty($descricaoTribNac) && $codServico == '030303') {
            $descricaoTribNac = 'Exploração de quadras esportivas, estádios, ginásios, canchas e congêneres, para realização de eventos ou negócios de qualquer natureza.';
        }

        if (empty($descricaoNbs)) {
            $descricaoNbs = 'Serviços médicos especializados';
        }

        // 2. VALORES E ALÍQUOTAS
        $valorServico = (float) $item->valor_total;
        $aliquotaIss  = (float) ($item->servico->aliquota_iss ?? $servicoMaster->aliquota_iss ?? 3.00);
        $valorIss     = round($valorServico * ($aliquotaIss / 100), 2);
        $dataEmissao  = date('d/m/Y H:i:s', strtotime($item->updated_at));

        // 3. ARRAY COMPLETO PARA A VIEW (COM AS DESCRIÇÕES INCLUÍDAS)
        $dados = [
            'chave'              => $chaveAcesso,
            'numero_nfse'        => $item->numero_nfse > 0 ? $item->numero_nfse : $item->id,
            'numero_dps'         => $item->id,
            'serie_dps'          => $item->serie ?: '1',
            'competencia'        => $item->servico && $item->servico->data_competencia
                ? date('d/m/Y', strtotime($item->servico->data_competencia))
                : date('d/m/Y', strtotime($item->created_at)),

            // Datas
            'data_emissao'       => $dataEmissao,
            'data_emissao_nfse'  => $dataEmissao,
            'data_emissao_dps'   => date('d/m/Y H:i:s', strtotime($item->created_at)),

            // Prestador
            'prestador_cnpj'     => $config->cnpj ?? '',
            'prestador_nome'     => $config->razao_social ?? '',
            'prestador_im'       => $config->inscricao_municipal ?? '',
            'prestador_endereco' => "{$config->logradouro}, {$config->numero} - {$config->bairro}",
            'prestador_cidade'   => ($config->municipio ?? 'Lauro de Freitas') . ' / ' . ($config->UF ?? 'BA'),
            'prestador_cep'      => $config->cep ?? '',
            'prestador_fone'     => $config->fone ?? '',
            'prestador_email'    => $config->email ?? '',

            // Tomador
            'tomador_cnpj'       => $item->documento ?? ($cliente->cpf_cnpj ?? ''),
            'tomador_nome'       => $item->razao_social ?? ($cliente->razao_social ?? 'CONSUMIDOR'),
            'tomador_endereco'   => "{$item->rua}, {$item->numero} - {$item->bairro}",
            'tomador_cidade'     => ($item->cidade->nome ?? $cliente->cidade->nome ?? 'Lauro de Freitas') . ' / ' . ($item->cidade->uf ?? $cliente->cidade->uf ?? 'BA'),
            'tomador_cep'        => $item->cep ?? ($cliente->cep ?? ''),
            'tomador_fone'       => $cliente->telefone ?? $cliente->celular ?? '-',

            // Serviço e Descrições Extraídas
            'codigo_servico'     => $codServico,
            'codigo_nbs'         => $servicoMaster->codigo_nbs ?? '123012200',
            'descricao_trib_nac' => $descricaoTribNac,
            'descricao_nbs'      => $descricaoNbs,
            'discriminacao'      => $item->servico->discriminacao ?? $servicoMaster->nome ?? 'REFERENTE SERVIÇO PRESTADO',
            'local_prestacao'    => ($item->cidade->nome ?? 'Lauro de Freitas') . ' - ' . ($item->cidade->uf ?? 'BA'),

            // Valores e Impostos
            'valor_total'        => $valorServico,
            'valor_servico'      => $valorServico,
            'valor_liquido'      => $valorServico,
            'aliquota_iss'       => number_format($aliquotaIss, 2, ',', '.'),
            'valor_iss'          => $valorIss,
            'iss_retido'         => ((int) ($item->servico->iss_retido ?? 0) === 1),
        ];

        return view('nfse.danfse_nacional', compact('dados', 'item'));
    }

    public function enviarXml(Request $request){
        $email = $request->email;
        $id = $request->id;
        $item = NotaServico::findOrFail($id);

        if(valida_objeto($item)){
            $value = session('user_logged');
            Mail::send('mail.xml_send_nfse', ['nfse' => $item, 'usuario' => $value['nome']], function($m) use ($item, $email){
                $public = env('SERVIDOR_WEB') ? 'public/' : '';
                $nomeEmpresa = env('SMS_NOME_EMPRESA', 'FerSoft');
                $emailEnvio = env('MAIL_USERNAME');

                $m->from($emailEnvio, $nomeEmpresa);
                $m->subject('Envio de XML NFSE ' . $item->numero_nfse);
                $m->attach(public_path('nfse_doc/NFSe_' . $item->id . '.xml'));
                $m->to($email);
            });
            return "ok";
        }
        return redirect('/403');
    }

    private function _validate(Request $request){
        $rules = [
            'cliente' => 'required',
            'natureza_operacao' => 'required',
            'razao_social' => 'required|max:80',
            'documento' => ['required'],
            'rua' => 'required|max:80',
            'numero' => 'required|max:10',
            'bairro' => 'required|max:50',
            'email' => 'max:40',
            'cep' => 'required',
            'cidade_id' => 'required',
            'discriminacao' => 'required',
            'valor_servico' => 'required',
            'codigo_servico' => 'required',
        ];

        $messages = [
            'cliente.required' => 'Selecione o cliente',
            'razao_social.required' => 'O campo Razão Social é obrigatório.',
            'natureza_operacao.required' => 'O campo Natureza de Operação é obrigatório.',
            'documento.required' => 'O campo CPF/CNPJ é obrigatório.',
            'rua.required' => 'O campo Rua é obrigatório.',
            'numero.required' => 'O campo Número é obrigatório.',
            'cep.required' => 'O campo CEP é obrigatório.',
            'cidade_id.required' => 'O campo Cidade é obrigatório.',
            'bairro.required' => 'O campo Bairro é obrigatório.',
            'discriminacao.required' => 'Campo discriminação é obrigatório.',
            'valor_servico.required' => 'Campo valor do serviço é obrigatório.',
            'codigo_servico.required' => 'Campo código do serviço é obrigatório.',
        ];

        $this->validate($request, $rules, $messages);
    }

    public function edit($id)
    {
        $item = NotaServico::where('empresa_id', $this->empresa_id)->findOrFail($id);

        // Se já estiver emitida/aprovada, bloqueia a edição
        if ($item->estado == 'aprovado' || $item->status == 'aprovado') {
            session()->flash('mensagem_erro', 'Esta NFS-e já foi aprovada e não pode ser editada!');
            return redirect('/nfse');
        }

        $clientes = Cliente::where('empresa_id', $this->empresa_id)
            ->orderBy('razao_social', 'asc')
            ->where('inativo', false)
            ->get();

        $servicos = Servico::where('empresa_id', $this->empresa_id)
            ->orderBy('nome', 'desc')
            ->get();

        $categorias = CategoriaConta::where('empresa_id', $this->empresa_id)->get();
        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();
        $tiposPagamento = ['Dinheiro', 'Boleto', 'Cartão de Crédito', 'Cartão de Débito', 'Pix', 'Transferência'];

        // CORREÇÃO: Apontar para 'nfse.create' e enviar as variáveis corretas
        return view('nfse.create', compact('item', 'clientes', 'config', 'servicos', 'tiposPagamento', 'categorias'))
            ->with('title', 'Editar NFS-e #' . $item->id);
    }

    // SUBSTITUI O ANTIGO PREVIEW DO CLOUD DFE
    public function previewXml($id)
    {
        $item = NotaServico::findOrFail($id);

        $xmlPath = public_path('nfse_doc/NFSe_' . $item->id . '.xml');

        // Se a nota já foi aprovada, mostra o XML real
        if ($item->estado === 'aprovado' && file_exists($xmlPath)) {
            return response(file_get_contents($xmlPath))->header('Content-Type', 'application/xml');
        }

        // Se for uma nota nova (Rascunho), mostra os dados em JSON para conferência
        return response()->json([
            'status' => 'RASCUNHO / NÃO TRANSMITIDA',
            'mensagem' => 'A NFS-e ainda não foi comunicada à Prefeitura. O XML oficial será gerado no momento do envio.',
            'dados_preliminares' => [
                'cliente' => $item->razao_social,
                'documento' => $item->documento,
                'valor_servico' => $item->valor_total,
                'natureza_operacao' => $item->natureza_operacao
            ]
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    // NOVA FUNÇÃO: PRÉ-VISUALIZAR RPS (RASCUNHO) ANTES DE ENVIAR
    public function previewRps($id)
    {
        $item = NotaServico::where('empresa_id', $this->empresa_id)->findOrFail($id);
        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();
        $cliente = Cliente::with('cidade')->find($item->cliente_id);
        $servicoMaster = $item->servico ? ($item->servico->servico ?? Servico::find($item->servico->servico_id)) : null;

        $aliquotaIss  = (float) ($item->servico->aliquota_iss ?? $servicoMaster->aliquota_iss ?? 3.00);
        $valorServico = (float) $item->valor_total;
        $valorIss     = round($valorServico * ($aliquotaIss / 100), 2);

        $dados = [
            'chave'              => 'DOCUMENTO SEM VALIDADE FISCAL (RASCUNHO)',
            'numero_nfse'        => '0000',
            'numero_dps'         => $item->id,
            'serie_dps'          => $item->serie ?: '1',
            'competencia'        => date('d/m/Y'),
            'data_emissao'       => date('d/m/Y H:i:s'),
            'data_emissao_nfse'  => date('d/m/Y H:i:s'),
            'data_emissao_dps'   => date('d/m/Y H:i:s', strtotime($item->created_at)),

            'prestador_cnpj'     => $config->cnpj ?? '',
            'prestador_nome'     => $config->razao_social ?? '',
            'prestador_im'       => $config->inscricao_municipal ?? '',
            'prestador_endereco' => "{$config->logradouro}, {$config->numero} - {$config->bairro}",
            'prestador_cidade'   => ($config->municipio ?? 'Lauro de Freitas') . ' / ' . ($config->UF ?? 'BA'),
            'prestador_cep'      => $config->cep ?? '',
            'prestador_fone'     => $config->fone ?? '',
            'prestador_email'    => $config->email ?? '',

            'tomador_cnpj'       => $item->documento ?? ($cliente->cpf_cnpj ?? ''),
            'tomador_nome'       => $item->razao_social ?? ($cliente->razao_social ?? 'CONSUMIDOR'),
            'tomador_endereco'   => "{$item->rua}, {$item->numero} - {$item->bairro}",
            'tomador_cidade'     => ($item->cidade->nome ?? $cliente->cidade->nome ?? 'Lauro de Freitas') . ' / ' . ($item->cidade->uf ?? $cliente->cidade->uf ?? 'BA'),
            'tomador_cep'        => $item->cep ?? ($cliente->cep ?? ''),
            'tomador_fone'       => $cliente->telefone ?? $cliente->celular ?? '-',

            'codigo_servico'     => $item->servico->codigo_servico ?? $servicoMaster->codigo_servico ?? '030303',
            'codigo_nbs'         => $servicoMaster->codigo_nbs ?? '123012200',
            'descricao_trib_nac' => 'Simulação de RPS',
            'descricao_nbs'      => 'Simulação de Serviço',
            'discriminacao'      => $item->servico->discriminacao ?? $servicoMaster->nome ?? 'REFERENTE SERVIÇO PRESTADO',
            'local_prestacao'    => ($item->cidade->nome ?? 'Lauro de Freitas') . ' - ' . ($item->cidade->uf ?? 'BA'),

            'valor_total'        => $valorServico,
            'valor_servico'      => $valorServico,
            'valor_liquido'      => $valorServico,
            'aliquota_iss'       => number_format($aliquotaIss, 2, ',', '.'),
            'valor_iss'          => $valorIss,
            'iss_retido'         => ((int) ($item->servico->iss_retido ?? 0) === 1),
        ];

        return view('nfse.danfse_nacional', compact('dados', 'item'));
    }
}
