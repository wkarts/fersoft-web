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
use Illuminate\Support\Facades\Schema;
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

    /**
     * Define o provedor da NFS-e:
     * - Simples Nacional (0) ou MEI (2/3) -> Sempre Portal Nacional ('nacional')
     * - Regime Normal (1) -> Prefeitura Municipal (Dias d'Ávila, Salvador) ou Nacional
     */
    private function definirProvedorNfse($config, $tributacao, string $codigoIbge): string
    {
        $regimeTributacao = isset($tributacao->regime) ? (int) $tributacao->regime : 1;

        // Se na Tributação Padrão NÃO for Regime Normal (1), ou seja, for Simples Nacional ou MEI:
        // Envia obrigatoriamente para o Portal Nacional, mesmo sendo de Dias d'Ávila ou Salvador
        if ($regimeTributacao !== 1) {
            return 'nacional';
        }

        // Regime Normal (1): envia para o provedor municipal conforme código IBGE
        return match ((string) $codigoIbge) {
            '2910057' => 'diasdavila',
            '2927408' => 'salvador',
            default   => 'nacional',
        };
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
        $tributacao = Tributacao::where('empresa_id', $this->empresa_id)->first();
        $tiposPagamento = ['Dinheiro', 'Boleto', 'Cartão de Crédito', 'Cartão de Débito', 'Pix', 'Transferência'];

        return view('nfse.create', compact('clientes', 'config', 'servicos', 'tiposPagamento', 'categorias', 'tributacao'))
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
                $tributacao = Tributacao::where('empresa_id', $this->empresa_id)->first();

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

                // PIS, COFINS e INSS
                $aliqPis    = ($request->aliquota_pis !== null && $request->aliquota_pis !== '') ? (float)__replace($request->aliquota_pis) : (float)($tributacao->pis ?? 0);
                $aliqCofins = ($request->aliquota_cofins !== null && $request->aliquota_cofins !== '') ? (float)__replace($request->aliquota_cofins) : (float)($tributacao->cofins ?? 0);
                $aliqInss   = $request->aliquota_inss ? (float)__replace($request->aliquota_inss) : 0;
                $aliqIss    = $request->aliquota_iss ? (float)__replace($request->aliquota_iss) : 0;

                // REGRA: Só coloca CSLL e IR se "Retém PIS/COFINS/CSLL?" estiver marcado como "Sim" (3)
                $retemFederal = ((int)$request->input('retencao_federal', 1) === 3);
                if ($retemFederal) {
                    $aliqIr   = ($request->aliquota_ir !== null && $request->aliquota_ir !== '' && (float)__replace($request->aliquota_ir) > 0)
                        ? (float)__replace($request->aliquota_ir) : (float)($tributacao->ir ?? 0);
                    $aliqCsll = ($request->aliquota_csll !== null && $request->aliquota_csll !== '' && (float)__replace($request->aliquota_csll) > 0)
                        ? (float)__replace($request->aliquota_csll) : (float)($tributacao->csll ?? 0);
                } else {
                    $aliqIr   = 0;
                    $aliqCsll = 0;
                }

                $vDescIncond    = $request->desconto_incondicional ? (float)__replace($request->desconto_incondicional) : 0;
                $outrasRet      = $request->outras_retencoes ? (float)__replace($request->outras_retencoes) : 0;
                $issRetidoFlag  = (int)($request->iss_retido ?? 2);

                $dadosServico = [
                    'nfse_id' => $nfse->id,
                    'discriminacao' => $request->discriminacao,
                    'valor_servico' => $totalServico,
                    'servico_id' => $request->servico_id,
                    'codigo_cnae' => $request->codigo_cnae ?? '',
                    'codigo_servico' => $request->codigo_servico ?? '',
                    'codigo_tributacao_municipio' => $request->codigo_tributacao_municipio ?? '',
                    'exigibilidade_iss' => $request->exigibilidade_iss ?? 1,
                    'iss_retido' => $issRetidoFlag,
                    'data_competencia' => $request->data_competencia ?? null,
                    'estado_local_prestacao_servico' => $request->estado_local_prestacao_servico ?? '',
                    'cidade_local_prestacao_servico' => $request->cidade_local_prestacao_servico ?? '',
                    'valor_deducoes' => $request->valor_deducoes ? __replace($request->valor_deducoes) : 0,
                    'desconto_incondicional' => $vDescIncond,
                    'desconto_condicional' => $request->desconto_condicional ? __replace($request->desconto_condicional) : 0,
                    'outras_retencoes' => $outrasRet,
                    'aliquota_iss' => $aliqIss,
                    'aliquota_pis' => $aliqPis,
                    'aliquota_cofins' => $aliqCofins,
                    'aliquota_inss' => $aliqInss,
                    'aliquota_ir' => $aliqIr,
                    'aliquota_csll' => $aliqCsll,
                    'intermediador' => $request->intermediador ?? 'n',
                    'documento_intermediador' => $request->documento_intermediador ?? '',
                    'nome_intermediador' => $request->nome_intermediador ?? '',
                    'im_intermediador' => $request->im_intermediador ?? '',
                    'responsavel_retencao_iss' => $request->responsavel_retencao_iss ?? 1,
                ];

                if (Schema::hasColumn('nfse_servicos', 'aliq_ibs_uf')) {
                    $dadosServico['aliq_ibs_uf'] = $request->aliq_ibs_uf ? __replace($request->aliq_ibs_uf) : ($tributacao->aliq_ibs_uf ?? 0.10);
                }
                if (Schema::hasColumn('nfse_servicos', 'aliq_cbs')) {
                    $dadosServico['aliq_cbs'] = $request->aliq_cbs ? __replace($request->aliq_cbs) : ($tributacao->aliq_cbs ?? 0.90);
                }

                NfseServico::create($dadosServico);

                // Cálculo do Valor Líquido (sem retenções) para o Contas a Receber
                $vInssCalc = $aliqInss > 0 ? round($totalServico * ($aliqInss / 100), 2) : 0;
                $vIrCalc   = $aliqIr > 0 ? round($totalServico * ($aliqIr / 100), 2) : 0;
                $vCsllCalc = 0;
                if ($retemFederal) {
                    $vPisCalc    = round($totalServico * ($aliqPis / 100), 2);
                    $vCofinsCalc = round($totalServico * ($aliqCofins / 100), 2);
                    $vCsllPuro   = round($totalServico * ($aliqCsll / 100), 2);
                    $vCsllCalc   = round($vPisCalc + $vCofinsCalc + $vCsllPuro, 2);
                }
                $vIssRetCalc = ($issRetidoFlag === 1) ? round($totalServico * ($aliqIss / 100), 2) : 0;
                $totalRetencoesNota = round($vInssCalc + $vIrCalc + $vCsllCalc + $vIssRetCalc + $outrasRet + $vDescIncond, 2);
                $valorLiquidoReceber = max(0, round($totalServico - $totalRetencoesNota, 2));

                // Gravação Segura no Contas a Receber (pelo Valor Líquido sem a retenção)
                if ($request->input('gerar_contas_receber') == '1' && $request->has('parcelas') && is_array($request->parcelas)) {
                    $categoriaId = $request->categoria_conta_id;
                    if (empty($categoriaId)) {
                        $primeiraCat = DB::table('categoria_contas')->where('empresa_id', $this->empresa_id)->first();
                        $categoriaId = $primeiraCat->id ?? null;
                    }

                    $totalParcelas = count($request->parcelas);
                    $somaParcelasTela = 0;
                    foreach ($request->parcelas as $p) {
                        $somaParcelasTela += (float) __replace($p['valor'] ?? '0');
                    }

                    // Se na tela ainda estava o valor bruto cheio e houve retenção, divide o valorLiquidoReceber
                    $recalcularPeloLiquido = ($totalRetencoesNota > 0 && abs($somaParcelasTela - $totalServico) < 0.05);
                    $valorParcelaBase = $totalParcelas > 0 ? floor(($valorLiquidoReceber / $totalParcelas) * 100) / 100 : $valorLiquidoReceber;
                    $restoCentavos = round($valorLiquidoReceber - ($valorParcelaBase * $totalParcelas), 2);

                    foreach ($request->parcelas as $key => $parcela) {
                        $numParcela = $key + 1;
                        if ($recalcularPeloLiquido) {
                            $vlParcela = ($numParcela === $totalParcelas) ? round($valorParcelaBase + $restoCentavos, 2) : $valorParcelaBase;
                        } else {
                            $vlParcela = (float) __replace($parcela['valor'] ?? '0');
                        }

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
     * Atualização da NFS-e (incluindo gravação completa de todos os impostos)
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
            $tributacao = Tributacao::where('empresa_id', $this->empresa_id)->first();

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

            $aliqPis    = ($request->aliquota_pis !== null && $request->aliquota_pis !== '') ? (float)__replace($request->aliquota_pis) : (float)($tributacao->pis ?? 0);
            $aliqCofins = ($request->aliquota_cofins !== null && $request->aliquota_cofins !== '') ? (float)__replace($request->aliquota_cofins) : (float)($tributacao->cofins ?? 0);
            $aliqInss   = $request->aliquota_inss ? (float)__replace($request->aliquota_inss) : 0;
            $aliqIss    = $request->aliquota_iss ? (float)__replace($request->aliquota_iss) : 0;

            // REGRA: Só coloca CSLL e IR se "Retém PIS/COFINS/CSLL?" estiver marcado como "Sim" (3)
            $retemFederal = ((int)$request->input('retencao_federal', 1) === 3);
            if ($retemFederal) {
                $aliqIr   = ($request->aliquota_ir !== null && $request->aliquota_ir !== '' && (float)__replace($request->aliquota_ir) > 0)
                    ? (float)__replace($request->aliquota_ir) : (float)($tributacao->ir ?? 0);
                $aliqCsll = ($request->aliquota_csll !== null && $request->aliquota_csll !== '' && (float)__replace($request->aliquota_csll) > 0)
                    ? (float)__replace($request->aliquota_csll) : (float)($tributacao->csll ?? 0);
            } else {
                $aliqIr   = 0;
                $aliqCsll = 0;
            }

            $dadosUpdateServico = [
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
                'valor_deducoes'                 => $request->valor_deducoes ? __replace($request->valor_deducoes) : 0,
                'desconto_incondicional'         => $request->desconto_incondicional ? __replace($request->desconto_incondicional) : 0,
                'desconto_condicional'           => $request->desconto_condicional ? __replace($request->desconto_condicional) : 0,
                'outras_retencoes'               => $request->outras_retencoes ? __replace($request->outras_retencoes) : 0,
                'aliquota_iss'                   => $aliqIss,
                'aliquota_pis'                   => $aliqPis,
                'aliquota_cofins'                => $aliqCofins,
                'aliquota_inss'                  => $aliqInss,
                'aliquota_ir'                    => $aliqIr,
                'aliquota_csll'                  => $aliqCsll,
                'intermediador'                  => $request->intermediador ?? 'n',
                'documento_intermediador'        => $request->documento_intermediador ?? '',
                'nome_intermediador'             => $request->nome_intermediador ?? '',
                'im_intermediador'               => $request->im_intermediador ?? '',
                'responsavel_retencao_iss'       => $request->responsavel_retencao_iss ?? 1,
            ];

            if (Schema::hasColumn('nfse_servicos', 'aliq_ibs_uf')) {
                $dadosUpdateServico['aliq_ibs_uf'] = $request->aliq_ibs_uf ? __replace($request->aliq_ibs_uf) : ($tributacao->aliq_ibs_uf ?? 0.10);
            }
            if (Schema::hasColumn('nfse_servicos', 'aliq_cbs')) {
                $dadosUpdateServico['aliq_cbs'] = $request->aliq_cbs ? __replace($request->aliq_cbs) : ($tributacao->aliq_cbs ?? 0.90);
            }

            NfseServico::where('nfse_id', $item->id)->update($dadosUpdateServico);

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

            // 1. Garante que o PDF físico existe na pasta nfse_pdf (gera se não existir)
            $caminhoPdf = public_path('nfse_pdf/NFSe_' . $item->id . '.pdf');
            if (!file_exists($caminhoPdf)) {
                try {
                    $pdfContent = view('nfse.danfse_nacional', [
                        'dados' => $this->prepararDadosPdf($item),
                        'item' => $item
                    ])->render();

                    $pdf = \App::make('dompdf.wrapper');
                    $pdf->loadHTML($pdfContent)->setPaper('a4', 'portrait')->save($caminhoPdf);
                } catch (\Throwable $ex) {
                    Log::warning("Aviso ao gerar PDF no WhatsApp: " . $ex->getMessage());
                }
            }

            // 2. Localiza o XML correspondente
            $caminhoXml = public_path('nfse_doc/NFSe_' . $item->id . '.xml');
            if (!file_exists($caminhoXml) && !empty($item->codigo_verificacao)) {
                $caminhoXml = public_path('nfse_doc/' . $item->codigo_verificacao . '.xml');
            }
            if (!file_exists($caminhoXml) && !empty($item->uuid)) {
                $caminhoXml = public_path('nfse_doc/' . $item->uuid);
            }

            // Mensagem informativa limpa
            $mensagem  = "📄 *NOTA FISCAL DE SERVIÇO ELETRÔNICA (NFS-e) #{$numNota}*\n\n";
            $mensagem .= "Olá, *{$item->razao_social}*!\n\n";
            $mensagem .= "Segue em anexo o documento em PDF e o arquivo XML referentes aos serviços prestados:\n\n";
            $mensagem .= "• *Número da Nota:* {$numNota}\n";
            $mensagem .= "• *Valor Total:* R$ {$valorFormatado}\n";
            if (!empty($item->codigo_verificacao)) {
                $mensagem .= "• *Cód. Verificação:* {$item->codigo_verificacao}\n";
            }
            $mensagem .= "\nFicamos à disposição para dúvidas.\n\n";
            $mensagem .= "Atenciosamente,\n*{$nomeEmpresa}*";

            $instanciaWhats = app('\App\Utils\WhatsAppUtil');

            // 3. Disparar o envio principal anexando primariamente o PDF
            $retornoJson = null;
            if (file_exists($caminhoPdf)) {
                // Se a ferramenta aceitar arquivo no 4º parâmetro
                $retornoJson = $instanciaWhats->sendMessage($numero, $mensagem, $this->empresa_id, $caminhoPdf);
            } else {
                $retornoJson = $instanciaWhats->sendMessage($numero, $mensagem, $this->empresa_id, null);
            }

            // 4. Envia o XML logo em seguida como arquivo complementar
            if (file_exists($caminhoXml)) {
                $instanciaWhats->sendMessage($numero, "📎 *XML Oficial da NFS-e #{$numNota}*", $this->empresa_id, $caminhoXml);
            }

            $res = json_decode($retornoJson, true);
            if (isset($res['success']) && $res['success'] === false) {
                $msgErro = $res['message'] ?? 'Falha ao enviar arquivo via WhatsApp';
                return redirect()->back()->with('mensagem_erro', "Erro no envio do WhatsApp: " . $msgErro);
            }

            return redirect()->back()->with('mensagem_sucesso', "NFS-e #{$numNota} enviada com sucesso por WhatsApp para {$item->razao_social}!");

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
            // Regra solicitada: Simples Nacional e MEI emitem sempre pelo Portal Nacional ('nacional').
            // Regime Normal emite pelas Prefeituras (Dias d'Ávila, Salvador) ou Nacional.
            $configArray = $config->toArray();
            $provedor = $this->definirProvedorNfse($config, $tributacao, $codigoIbgeEmitente);

            if ($provedor === 'diasdavila' && (empty($configArray['usuario_saatri']) || empty($configArray['senha_saatri']))) {
                throw new \RuntimeException('Configure as credenciais do provedor municipal antes de continuar.');
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
            $docTemp = !empty($item->documento) ? $item->documento : ($cliente->cpf_cnpj ?? '');
            $docTomador = preg_replace('/\D/', '', $docTemp);
            $tipoDocTomador = strlen($docTomador) > 11 ? 'CNPJ' : 'CPF';
            $codigoIbgeTomador = preg_replace('/\D/', '', $item->cidade->codigo ?? $cliente->cidade->codigo ?? $codigoIbgeEmitente);
            $cMunPrestacao = preg_replace('/\D/', '', $nfseServico->cidade_local_prestacao_servico ?? $codigoIbgeEmitente);

            // 6. CÁLCULO DE VALORES E REFORMA TRIBUTÁRIA USANDO O SERVICE
            $valorServico = (float) $item->valor_total;
            $vDescIncond = (float) ($nfseServico->desconto_incondicional ?? 0);
            $vDeducoes   = (float) ($nfseServico->valor_deducoes ?? 0);

            // Calcula o ISS exato para poder deduzir da Reforma Tributária
            $aliquotaIss = (float) ($nfseServico->aliquota_iss ?? $servicoMaster->aliquota_iss ?? 3.00);
            $valorIssCalculado = round($valorServico * ($aliquotaIss / 100), 2);
            $issRetido   = ((int) ($nfseServico->iss_retido ?? 0) === 1);

            // Alíquotas IBS e CBS (da nota ou da Tributação Padrão)
            $aliqIbsUfNota = (float) (($nfseServico->aliq_ibs_uf ?? 0) > 0 ? $nfseServico->aliq_ibs_uf : ($tributacao->aliq_ibs_uf ?? 0.1000));
            $aliqCbsNota   = (float) (($nfseServico->aliq_cbs ?? 0) > 0 ? $nfseServico->aliq_cbs : ($tributacao->aliq_cbs ?? 0.9000));

            // Instancia o service oficial da Reforma Tributária
            $rtService = new \App\Services\ReformaTributariaService();

            // Monta um array com os dados para o calculador oficial do service
            $itemCalculo = [
                'valor_total' => $valorServico,
                'cst_ibs_cbs' => $servicoMaster->cst_ibscbs ?? $tributacao->cst_ibs_cbs ?? '000',
                'class_trib_ibs_cbs' => $servicoMaster->codigo_class_trib ?? $tributacao->class_trib_ibs_cbs ?? '000001',
                'aliq_cbs' => $aliqCbsNota,
                'aliq_ibs_uf' => $aliqIbsUfNota,
                'aliq_ibs_mun' => (float)($tributacao->aliq_ibs_mun ?? 0.0000),
            ];

            $rtService->calcularItem($itemCalculo, $this->empresa_id);

            // >>> REGRA NACIONAL EXATA: O ISS NÃO INTEGRA A BASE DO IBS/CBS <<<
            $baseCalculoIbsCbs = round($valorServico - $vDeducoes - $vDescIncond - $valorIssCalculado, 2);

            // Calcula os impostos aplicando as alíquotas rigorosamente sobre a Base Limpa
            $valIbsEstadual = round($baseCalculoIbsCbs * (($itemCalculo['aliq_ibs_uf'] ?? 0.10) / 100), 2);
            $valCbs         = round($baseCalculoIbsCbs * (($itemCalculo['aliq_cbs'] ?? 0.90) / 100), 2);


            // 1. Retenções na Fonte (INSS e IRRF)
            $aliqInssNota = (float) ($nfseServico->aliquota_inss ?? 0);
            $aliqIrNota   = (float) ($nfseServico->aliquota_ir ?? 0);

            $vRetCP   = $aliqInssNota > 0 ? round($valorServico * ($aliqInssNota / 100), 2) : 0.00;
            $vRetIRRF = $aliqIrNota > 0 ? round($valorServico * ($aliqIrNota / 100), 2) : 0.00;

            // 2. Alíquotas de PIS, COFINS (da nota ou tributação padrão) e CSLL
            $cstPisCofins = ($opSimpNac === 1) ? '01' : '07';
            $aliqPis      = (float) ($nfseServico->aliquota_pis > 0 ? $nfseServico->aliquota_pis : ($tributacao->pis ?? 0));
            $aliqCofins   = (float) ($nfseServico->aliquota_cofins > 0 ? $nfseServico->aliquota_cofins : ($tributacao->cofins ?? 0));
            $aliqCsll     = (float) (($nfseServico->aliquota_csll ?? 0) > 0 ? $nfseServico->aliquota_csll : 0);

            // 3. Valores de PIS, COFINS e CSLL
            $vPis      = round($valorServico * ($aliqPis / 100), 2);
            $vCofins   = round($valorServico * ($aliqCofins / 100), 2);
            $vCsllPuro = round($valorServico * ($aliqCsll / 100), 2);

            // 4. Define se TEM RETENÇÃO ou NÃO:
            // Se tiver CSLL preenchida (> 0) ou se veio retencao_federal = 3
            $temRetencaoFederal = ($aliqCsll > 0 || (int) $request->input('retencao_federal', 0) === 3);

            if ($temRetencaoFederal) {
                $tpRetPisCofins = 3; // 3 = PIS/COFINS/CSLL Retidos pelo Tomador
                // No Portal Nacional, a tag vRetCSLL exibe o total de Contribuições Sociais Retidas (PIS + COFINS + CSLL)
                $totalContribuicoesSociaisRetidas = round($vPis + $vCofins + $vCsllPuro, 2);
                $vRetCSLL = $totalContribuicoesSociaisRetidas;
            } else {
                $tpRetPisCofins = 2; // 2 = PIS/COFINS Não Retidos (Apuração Própria)
                $totalContribuicoesSociaisRetidas = 0.00;
                $vRetCSLL = 0.00;
            }

            // 5. Abatimento na Base de Cálculo do IBS/CBS (sempre abate PIS, COFINS e ISS)
            $baseCalculoIbsCbsAbatida = round($valorServico - $vDeducoes - $vDescIncond - $valorIssCalculado - $vPis - $vCofins, 2);

            if ($baseCalculoIbsCbsAbatida > 0) {
                $valIbsEstadual = round($baseCalculoIbsCbsAbatida * (($itemCalculo['aliq_ibs_uf'] ?? 0.10) / 100), 2);
                $valCbs         = round($baseCalculoIbsCbsAbatida * (($itemCalculo['aliq_cbs'] ?? 0.90) / 100), 2);
            } else {
                $baseCalculoIbsCbsAbatida = 0;
                $valIbsEstadual = 0;
                $valCbs = 0;
            }

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
                            'cLocPrest' => $cMunPrestacao,
                        ],
                        'cServ' => [
                            'cTribNac'  => preg_replace('/[^0-9.]/', '', $nfseServico->codigo_servico ?? $servicoMaster->codigo_servico ?? '140501'),
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
                                    'CST'            => $cstPisCofins,
                                    'vBCPisCofins'   => number_format($valorServico, 2, '.', ''),
                                    'pAliqPis'       => number_format($aliqPis, 2, '.', ''),
                                    'pAliqCofins'    => number_format($aliqCofins, 2, '.', ''),
                                    'vPis'           => number_format($vPis, 2, '.', ''),
                                    'vCofins'        => number_format($vCofins, 2, '.', ''),
                                    'tpRetPisCofins' => $tpRetPisCofins
                                ]
                            ]
                        ]
                    ],

                    // Reforma Tributária (IBS/CBS) - Base de Cálculo e Indicador Oficial
                    'ibscbs' => [
                        'CST'         => $itemCalculo['cst_ibs_cbs'] ?? '000',
                        'cClassTrib'  => $itemCalculo['class_trib_ibs_cbs'] ?? '000001',
                        'cIndOp'      => $servicoMaster->codigo_indicador_operacao
                            ?? $servicoMaster->cIndOp
                                ?? (($cMunPrestacao === $codigoIbgeEmitente) ? '050101' : '050201'),
                        'indDest'     => '0',
                        'baseCalculo' => number_format($baseCalculoIbsCbsAbatida, 2, '.', ''), // BC Abatida
                        'percIbsMun'  => '0.00',
                        'valIbsMun'   => '0.00',
                        'percIbsEst'  => number_format($aliqIbsUfNota, 2, '.', ''),
                        'valIbsEst'   => number_format($valIbsEstadual, 2, '.', ''),
                        'percCbs'     => number_format($aliqCbsNota, 2, '.', ''),
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

            $this->atualizarStatusMedicaoNota($item->id, 'aprovado', $numeroNfse, $codVerificacao);

            try {
                $pdfPath = public_path('nfse_pdf/NFSe_' . $item->id . '.pdf');
                // Simula a renderização da view de impressão para salvar no servidor
                $pdfContent = view('nfse.danfse_nacional', [
                    'dados' => $this->prepararDadosPdf($item),
                    'item' => $item
                ])->render();

                // Se estiver a usar Dompdf ou biblioteca similar no seu projeto:
                $pdf = \App::make('dompdf.wrapper');
                $pdf->loadHTML($pdfContent)->setPaper('a4', 'portrait')->save($pdfPath);
            } catch (\Throwable $ex) {
                Log::warning("Aviso: Não foi possível gerar o PDF automático: " . $ex->getMessage());
            }

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

        // 1. Recupera a chave completa (50 dígitos no Padrão Nacional)
        $chaveAcesso = trim((string) $item->codigo_verificacao);

        // Se o banco cortou em 20 caracteres, recupera do campo uuid (ex: 2919207...xml)
        if (strlen($chaveAcesso) < 50 && !empty($item->uuid)) {
            $chaveUuid = str_replace('.xml', '', trim($item->uuid));
            if (strlen($chaveUuid) >= 50) {
                $chaveAcesso = $chaveUuid;
            }
        }

        // Se ainda não tiver 50 dígitos, tenta ler de dentro do arquivo XML salvo
        if (strlen($chaveAcesso) < 50) {
            $xmlPath = !empty($item->uuid) ? public_path('nfse_doc/' . $item->uuid) : public_path('nfse_doc/NFSe_' . $item->id . '.xml');
            if (file_exists($xmlPath)) {
                $xmlContent = file_get_contents($xmlPath);
                if (preg_match('/Id="NFS([0-9]{50})"/i', $xmlContent, $m)) {
                    $chaveAcesso = $m[1];
                }
            }
        }

        if (empty($chaveAcesso)) {
            return response()->json(['erro' => true, 'motivo' => 'Chave de acesso não localizada para esta nota.'], 422);
        }

        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();
        $certRes = Certificado::where('empresa_id', $this->empresa_id)->first();
        $tributacao = Tributacao::where('empresa_id', $this->empresa_id)->first();

        if (!$config || !$certRes) {
            return response()->json(['erro' => true, 'motivo' => 'Certificado digital ou configurações não configurados.'], 400);
        }

        try {
            $certContent = $certRes->arquivo;
            if (file_exists(storage_path('app/public/certificados/' . $certRes->arquivo))) {
                $certContent = file_get_contents(storage_path('app/public/certificados/' . $certRes->arquivo));
            }
            $certificate = Certificate::readPfx($certContent, $certRes->senha);

            // Identificação segura do município emitente
            $codigoIbge = null;
            if (!empty($config->cidade_id)) {
                $cidadeObj = DB::table('cidades')->where('id', $config->cidade_id)->first();
                if ($cidadeObj) {
                    $codigoIbge = $cidadeObj->codigo ?? $cidadeObj->codigo_ibge ?? null;
                }
            }
            if (!$codigoIbge) {
                $codigoIbge = $config->codMun ?? $config->codigo_ibge_municipio ?? '2919207';
            }
            $codigoIbge = str_pad(preg_replace('/\D/', '', $codigoIbge), 7, '0', STR_PAD_LEFT);

            $configArray = $config->toArray();

            $provedor = (strlen(preg_replace('/\D/', '', $chaveAcesso)) === 50)
                ? 'nacional'
                : $this->definirProvedorNfse($config, $tributacao, $codigoIbge);

            $emissor = \App\Services\Nfse\NfseServiceFactory::criar($provedor, $configArray, $certificate);

            // No Portal Nacional, xMotivo exige no mínimo 15 caracteres
            $motivosTexto = [
                '1' => 'Cancelamento por erro na emissao da nota fiscal',
                '2' => 'Cancelamento por servico nao prestado ao tomador',
                '4' => 'Cancelamento por duplicidade de emissao da nota'
            ];
            $motivoEnvio = $motivosTexto[$request->motivo] ?? 'Cancelamento por erro na emissao da nota fiscal';

            // Dispara o cancelamento no Portal Nacional / Municipal
            $resultado = $emissor->cancelar($chaveAcesso, $motivoEnvio);

            // Atualiza a nota no ERP
            $item->estado = 'cancelado';
            $item->motivo_rejeicao = 'Cancelada: ' . $motivoEnvio;
            $item->save();

            $this->atualizarStatusMedicaoNota($item->id, 'cancelado');

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
        $tributacao = Tributacao::where('empresa_id', $this->empresa_id)->first();

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
            $provedor = $this->definirProvedorNfse($config, $tributacao, $codigoIbgeEmitente);

            if ($provedor === 'diasdavila' && (empty($configArray['usuario_saatri']) || empty($configArray['senha_saatri']))) {
                throw new \RuntimeException('Configure as credenciais do provedor municipal antes de continuar.');
            }

            Log::info("Consulta NFS-e: Provedor selecionado = {$provedor}");

            $emissor = \App\Services\Nfse\NfseServiceFactory::criar($provedor, $configArray, $certificate);

            // >>> CORREÇÃO 1: Recupera a chave de 50 dígitos para o Portal Nacional ou usa o número/ID
            $chaveNacional = trim((string) $item->codigo_verificacao);
            if (strlen($chaveNacional) < 50 && !empty($item->uuid)) {
                $possivelChave = str_replace('.xml', '', trim($item->uuid));
                if (strlen($possivelChave) >= 50) {
                    $chaveNacional = $possivelChave;
                }
            }

            if ($provedor === 'nacional' && strlen($chaveNacional) >= 50) {
                $numeroParaConsulta = $chaveNacional;
            } else {
                $numeroParaConsulta = $item->numero_nfse > 0 ? $item->numero_nfse : $item->id;
            }

            // Realiza a consulta no webservice usando o identificador correto
            $retorno = $emissor->consultar($numeroParaConsulta);

            $statusRetorno = strtoupper($retorno['status'] ?? '');
            $isCancelada   = $retorno['cancelada'] ?? false;
            $numeroOficial = $retorno['numero_nfse'] ?? null;
            $codigoVerif   = $retorno['codigo_verificacao'] ?? null;

            // Captura mensagem de rejeição caso venha no retorno da consulta
            $mensagemRetorno = $retorno['mensagem'] ?? '';
            $xmlRespConsulta = $retorno['xml_resposta'] ?? (is_string($retorno) ? $retorno : '');

            // >>> CORREÇÃO 2: TRATAMENTO DO E91 (NOTA AINDA NA FILA) <<<
            // Tem que vir ANTES da regra de rejeição, senão ele acha que é erro!
            if (stripos($xmlRespConsulta, 'E91') !== false || stripos($xmlRespConsulta, 'não consta em nossa base') !== false) {
                return response()->json([
                    'status' => 'PROCESSANDO',
                    'mensagem' => 'A nota está na fila de processamento do Ambiente Nacional. Aguarde uns minutos e consulte novamente.'
                ]);
            }

            // 1. SE A PREFEITURA INDICAR QUE FOI REJEITADA DE VERDADE (ex: E1521, E1530)
            if (stripos($xmlRespConsulta, 'Rejeit') !== false || stripos($xmlRespConsulta, '<Codigo>E') !== false) {
                preg_match('/<Mensagem>(.*?)<\/Mensagem>/s', $xmlRespConsulta, $mMsg);
                $descricaoErro = $mMsg[1] ?? 'Rejeitada no Ambiente Nacional.';

                $item->estado = 'rejeitado';
                $item->motivo_rejeicao = html_entity_decode(trim($descricaoErro));
                $item->save();

                return response()->json([
                    'erro' => true,
                    'mensagem' => 'A consulta detetou a rejeição: ' . $item->motivo_rejeicao
                ], 422);
            }

            // 2. SE A PREFEITURA INDICAR QUE ESTÁ CANCELADA
            if ($isCancelada || $statusRetorno === 'CANCELADO') {
                $item->estado = 'cancelado';
                if ($numeroOficial) $item->numero_nfse = $numeroOficial;
                if ($codigoVerif) $item->codigo_verificacao = $codigoVerif;
                $item->motivo_rejeicao = 'Cancelada diretamente no Portal da Prefeitura.';
                $item->save();

                $this->atualizarStatusMedicaoNota($item->id, 'cancelado');

                DB::table('conta_recebers')
                    ->where('empresa_id', $this->empresa_id)
                    ->where('referencia', 'like', "NFS-e #" . $item->id . "%")
                    ->update(['status' => false, 'updated_at' => now()]);

                return response()->json([
                    'status' => 'CANCELADO',
                    'mensagem' => 'A consulta detetou que a NFS-e foi cancelada no portal.'
                ]);
            }

            // 3. SE ESTIVER AUTORIZADA
            if ($statusRetorno === 'AUTORIZADO' || !empty($numeroOficial)) {
                $item->estado = 'aprovado';
                $item->numero_nfse = $numeroOficial ?? $item->numero_nfse;
                if (!empty($codigoVerif)) {
                    $item->codigo_verificacao = $codigoVerif;
                }

                // Se a consulta devolveu o XML oficial da nota, salva na pasta nfse_doc
                if (!empty($xmlRespConsulta) && str_contains($xmlRespConsulta, '<NFSe')) {
                    $xmlName = (!empty($codigoVerif) && strlen($codigoVerif) >= 40) ? $codigoVerif . '.xml' : 'NFSe_' . $item->id . '.xml';
                    file_put_contents(public_path('nfse_doc/' . $xmlName), $xmlRespConsulta);
                    $item->uuid = $xmlName;
                    $item->url_xml = asset('nfse_doc/' . $xmlName);
                }

                $item->motivo_rejeicao = null;
                $item->save();

                $this->atualizarStatusMedicaoNota($item->id, 'aprovado', $item->numero_nfse, $item->codigo_verificacao);

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

    public function baixarXml($id)
    {
        $item = NotaServico::where('empresa_id', $this->empresa_id)->findOrFail($id);

        // Lista de possíveis nomes com que o XML foi salvo na pasta nfse_doc
        $arquivosTentativa = [];

        // 1. Nome salvo no campo uuid (ex: 2919207...xml ou NFSe_16.xml)
        if (!empty($item->uuid)) {
            $nomeUuid = str_ends_with($item->uuid, '.xml') ? $item->uuid : $item->uuid . '.xml';
            $arquivosTentativa[] = public_path('nfse_doc/' . $nomeUuid);
        }

        // 2. Chave salva em codigo_verificacao
        if (!empty($item->codigo_verificacao)) {
            $arquivosTentativa[] = public_path('nfse_doc/' . $item->codigo_verificacao . '.xml');
        }

        // 3. Padrão por ID da nota ou RPS local
        $arquivosTentativa[] = public_path('nfse_doc/NFSe_' . $item->id . '.xml');
        $arquivosTentativa[] = public_path('nfse_doc/RPS_Local_' . $item->id . '.xml');

        foreach ($arquivosTentativa as $path) {
            if (file_exists($path)) {
                $numeroDownload = $item->numero_nfse > 0 ? $item->numero_nfse : $item->id;
                return response()->download($path, "NFSe_{$numeroDownload}.xml", [
                    'Content-Type' => 'application/xml',
                ]);
            }
        }

        return redirect()->back()->with('mensagem_erro', 'Arquivo XML não encontrado no servidor!');
    }

    public function imprimir($id)
    {
        $item = NotaServico::where('empresa_id', $this->empresa_id)->findOrFail($id);
        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();
        $tributacao = Tributacao::where('empresa_id', $this->empresa_id)->first();
        $cliente = Cliente::with('cidade')->find($item->cliente_id);
        $servicoItem = $item->servico;
        $servicoMaster = $servicoItem ? ($servicoItem->servico ?? Servico::find($servicoItem->servico_id)) : null;

        // 1. LOCALIZA O ARQUIVO XML E RECUPERA A CHAVE DE 50 DÍGITOS
        $chaveAcesso = trim((string) $item->codigo_verificacao);
        if (strlen($chaveAcesso) < 50 && !empty($item->uuid)) {
            $possivelChave = str_replace('.xml', '', trim($item->uuid));
            if (strlen($possivelChave) >= 50) {
                $chaveAcesso = $possivelChave;
            }
        }

        $possiveisXml = [
            !empty($item->uuid) ? public_path('nfse_doc/' . (str_ends_with($item->uuid, '.xml') ? $item->uuid : $item->uuid . '.xml')) : null,
            !empty($chaveAcesso) ? public_path('nfse_doc/' . $chaveAcesso . '.xml') : null,
            public_path('nfse_doc/NFSe_' . $item->id . '.xml'),
        ];

        $xmlPath = null;
        foreach (array_filter($possiveisXml) as $caminho) {
            if (file_exists($caminho)) {
                $xmlPath = $caminho;
                break;
            }
        }

        $descricaoTribNac = null;
        $descricaoNbs     = null;
        $xmlContent       = null;

        if ($xmlPath && file_exists($xmlPath)) {
            $xmlContent = file_get_contents($xmlPath);

            if (empty($chaveAcesso) || strlen($chaveAcesso) < 50) {
                if (preg_match('/(?:Id="NFS|chaveAcesso="?|chNFSe>)([0-9]{50})/i', $xmlContent, $m)) {
                    $chaveAcesso = $m[1];
                } elseif (preg_match('/([0-9]{50})/', $xmlContent, $m)) {
                    $chaveAcesso = $m[1];
                }
            }

            if (preg_match('/<xTribNac>(.*?)<\/xTribNac>/is', $xmlContent, $mTrib)) {
                $descricaoTribNac = html_entity_decode(trim($mTrib[1]), ENT_QUOTES, 'UTF-8');
            }

            if (preg_match('/<xNBS>(.*?)<\/xNBS>/is', $xmlContent, $mNbs)) {
                $descricaoNbs = html_entity_decode(trim($mNbs[1]), ENT_QUOTES, 'UTF-8');
            }
        }

        if (empty($chaveAcesso)) {
            $chaveAcesso = '29192072205693853000170000000000014026101412894024';
        }

        $codServico = $servicoItem->codigo_servico ?? $servicoMaster->codigo_servico ?? '040303';
        if (empty($descricaoTribNac)) {
            $descricaoTribNac = $servicoMaster->nome ?? 'Serviços prestados';
        }
        if (empty($descricaoNbs)) {
            $descricaoNbs = 'Serviços especializados';
        }

        // 2. CÁLCULO DE VALORES, RETENÇÕES E VALOR LÍQUIDO
        $valorServico  = (float) ($item->valor_total ?? 0);
        $vDescIncond   = (float) ($servicoItem->desconto_incondicional ?? 0);
        $vDescCond     = (float) ($servicoItem->desconto_condicional ?? 0);
        $vDeducoes     = (float) ($servicoItem->valor_deducoes ?? 0);

        $aliquotaIss   = (float) ($servicoItem->aliquota_iss ?? $servicoMaster->aliquota_iss ?? 3.00);
        $valorIss      = round($valorServico * ($aliquotaIss / 100), 2);
        $issRetidoBool = ((int) ($servicoItem->iss_retido ?? 2) === 1);

        // Alíquotas Federais (prioriza o que foi salvo na nota; para PIS/COFINS usa fallback da tributação padrão)
        $aliqPis    = (float) (($servicoItem->aliquota_pis ?? 0) > 0 ? $servicoItem->aliquota_pis : ($tributacao->pis ?? 0));
        $aliqCofins = (float) (($servicoItem->aliquota_cofins ?? 0) > 0 ? $servicoItem->aliquota_cofins : ($tributacao->cofins ?? 0));
        $aliqInss   = (float) ($servicoItem->aliquota_inss ?? 0);
        $aliqIr     = (float) ($servicoItem->aliquota_ir ?? 0);
        $aliqCsll   = (float) ($servicoItem->aliquota_csll ?? 0);

        $valPis    = round($valorServico * ($aliqPis / 100), 2);
        $valCofins = round($valorServico * ($aliqCofins / 100), 2);
        $vRetCP    = $aliqInss > 0 ? round($valorServico * ($aliqInss / 100), 2) : 0.00;
        $vRetIRRF  = $aliqIr > 0 ? round($valorServico * ($aliqIr / 100), 2) : 0.00;
        $vCsllPuro = $aliqCsll > 0 ? round($valorServico * ($aliqCsll / 100), 2) : 0.00;

        // Verifica no XML se tpRetPisCofins == 3 ou se CSLL > 0 na nota
        $temRetencaoPisCofinsCsll = ($aliqCsll > 0);
        if ($xmlContent && preg_match('/<tpRetPisCofins>([0-9]+)<\/tpRetPisCofins>/i', $xmlContent, $mTpRet)) {
            $temRetencaoPisCofinsCsll = ((int) $mTpRet[1] === 3);
        }

        if ($temRetencaoPisCofinsCsll) {
            // Soma PIS + COFINS + CSLL quando retido
            $totalContribuicoesSociaisRetidas = round($valPis + $valCofins + $vCsllPuro, 2);
            // Se o XML já trouxer <vRetCSLL>, garante exatamente o valor do XML
            if ($xmlContent && preg_match('/<vRetCSLL>([0-9.]+)<\/vRetCSLL>/i', $xmlContent, $mCsllXml)) {
                $totalContribuicoesSociaisRetidas = (float) $mCsllXml[1];
            }
        } else {
            $totalContribuicoesSociaisRetidas = 0.00;
        }

        // Total de retenções federais e líquido da nota
        $totalRetencoesFederais = round($totalContribuicoesSociaisRetidas + $vRetIRRF + $vRetCP, 2);
        $totalRetencoesGeral    = round($totalRetencoesFederais + ($issRetidoBool ? $valorIss : 0), 2);
        $valorLiquidoNfse       = round($valorServico - $vDescIncond - $totalRetencoesGeral, 2);

        // Se o XML autorizado tiver <vTotalRet> e <vLiq>, usa os valores oficiais da SEFIN
        if ($xmlContent) {
            if (preg_match('/<vTotalRet>([0-9.]+)<\/vTotalRet>/i', $xmlContent, $mTotRet)) {
                $totalRetencoesGeral = (float) $mTotRet[1];
            }
            if (preg_match('/<vLiq>([0-9.]+)<\/vLiq>/i', $xmlContent, $mLiq)) {
                $valorLiquidoNfse = (float) $mLiq[1];
            }
        }

        // 3. CÁLCULO IBS / CBS (REFORMA TRIBUTÁRIA)
        $baseCalculoIbsCbs = max(0, round($valorServico - $vDeducoes - $vDescIncond - $valorIss - $valPis - $valCofins, 2));
        if ($xmlContent && preg_match('/<IBSCBS>.*?<valores>.*?<vBC>([0-9.]+)<\/vBC>/is', $xmlContent, $mBcIbs)) {
            $baseCalculoIbsCbs = (float) $mBcIbs[1];
        }

        $valCbs         = round($baseCalculoIbsCbs * 0.0036, 2);
        $valIbsEstadual = round($baseCalculoIbsCbs * 0.0004, 2);
        if ($xmlContent) {
            if (preg_match('/<vCBS>([0-9.]+)<\/vCBS>/i', $xmlContent, $mVCbs)) {
                $valCbs = (float) $mVCbs[1];
            }
            if (preg_match('/<vIBSUF>([0-9.]+)<\/vIBSUF>/i', $xmlContent, $mVIbsUf)) {
                $valIbsEstadual = (float) $mVIbsUf[1];
            }
        }
        $valTotalIbsCbs = round($valCbs + $valIbsEstadual, 2);

        $dataEmissao = date('d/m/Y H:i:s', strtotime($item->updated_at));

        // 4. MONTAGEM COMPLETA DO ARRAY $dados PARA O DANFSE
        $dados = [
            'chave'              => $chaveAcesso,
            'numero_nfse'        => $item->numero_nfse > 0 ? $item->numero_nfse : $item->id,
            'numero_dps'         => $item->id,
            'serie_dps'          => $item->serie ?: '1',
            'competencia'        => $servicoItem && $servicoItem->data_competencia
                ? date('d/m/Y', strtotime($servicoItem->data_competencia))
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

            // Serviço
            'codigo_servico'     => $codServico,
            'codigo_nbs'         => $servicoMaster->codigo_nbs ?? '123012200',
            'descricao_trib_nac' => $descricaoTribNac,
            'descricao_nbs'      => $descricaoNbs,
            'discriminacao'      => $servicoItem->discriminacao ?? $servicoMaster->nome ?? 'REFERENTE SERVIÇO PRESTADO',
            'local_prestacao'    => (function() use ($servicoItem) {
                $codMun = $servicoItem->cidade_local_prestacao_servico ?? null;
                if (!empty($codMun)) {
                    $cidadeObj = \App\Models\Cidade::where('codigo', $codMun)->orWhere('id', $codMun)->first();
                    if ($cidadeObj) {
                        return $cidadeObj->nome . ' - ' . $cidadeObj->uf;
                    }
                    return $codMun;
                }
                return 'Lauro de Freitas - BA';
            })(),

            // Valores Principais e ISS
            'valor_total'                 => $valorServico,
            'valor_servico'               => $valorServico,
            'desconto_incondicional'      => $vDescIncond,
            'desconto_condicional'        => $vDescCond,
            'valor_deducoes'              => $vDeducoes,
            'valor_liquido'               => $valorLiquidoNfse,
            'v_liq'                       => $valorLiquidoNfse,
            'aliquota_iss'                => number_format($aliquotaIss, 2, ',', '.'),
            'valor_iss'                   => $valorIss,
            'iss_retido'                  => $issRetidoBool,

            // Tributos Federais e Retenções (envia todos os aliases para a Blade não ficar zerada)
            'pis_proprio'                 => $valPis,
            'cofins_proprio'              => $valCofins,
            'valor_pis'                   => $valPis,
            'valor_cofins'                => $valCofins,
            'v_pis'                       => $valPis,
            'v_cofins'                    => $valCofins,
            'v_ret_pis'                   => $valPis,
            'v_ret_cofins'                => $valCofins,
            'pis'                         => $valPis,
            'cofins'                      => $valCofins,

            'v_ret_cp'                    => $vRetCP,
            'v_ret_inss'                  => $vRetCP,
            'valor_inss'                  => $vRetCP,
            'inss'                        => $vRetCP,

            'v_ret_irrf'                  => $vRetIRRF,
            'valor_ir'                    => $vRetIRRF,
            'valor_irrf'                  => $vRetIRRF,
            'irrf'                        => $vRetIRRF,
            'ir'                          => $vRetIRRF,

            // CSLL pura (1%) e Contribuições Sociais Retidas (PIS + COFINS + CSLL)
            'csll_puro'                   => $vCsllPuro,
            'v_ret_csll'                  => $vCsllPuro,
            'valor_csll'                  => $vCsllPuro,
            'csll'                        => $vCsllPuro,
            'total_contribuicoes_sociais' => $totalContribuicoesSociaisRetidas,
            'total_retencoes'             => $totalRetencoesGeral,
            'valor_total_retencoes'       => $totalRetencoesGeral,
            'v_total_ret'                 => $totalRetencoesGeral,
            'outras_retencoes'            => $totalRetencoesGeral,

            // IBS / CBS (Reforma Tributária)
            'ibscbs_bc'                   => $baseCalculoIbsCbs,
            'cst_ibscbs'                  => $servicoMaster->cst_ibscbs ?? '200',
            'class_trib'                  => $servicoMaster->codigo_class_trib ?? '200029',
            'aliq_cbs'                    => 0.90,
            'aliq_efetiva_cbs'            => 0.36,
            'val_cbs'                     => $valCbs,
            'aliq_ibs_est'                => 0.10,
            'aliq_efetiva_est'            => 0.04,
            'val_ibs_est'                 => $valIbsEstadual,
            'aliq_ibs_mun'                => 0.00,
            'aliq_efetiva_mun'            => 0.00,
            'val_ibs_mun'                 => 0.00,
            'val_total_ibscbs'            => $valTotalIbsCbs,
            'total_ibscbs'                => $valTotalIbsCbs,
        ];

        try {
            $pdfPath = public_path('nfse_pdf/NFSe_' . $item->id . '.pdf');
            $pdfContent = view('nfse.danfse_nacional', [
                'dados' => $dados,
                'item'  => $item
            ])->render();

            $pdf = \App::make('dompdf.wrapper');
            $pdf->loadHTML($pdfContent)->setPaper('a4', 'portrait')->save($pdfPath);
        } catch (\Throwable $ex) {
            Log::warning("Erro ao salvar PDF físico na impressão: " . $ex->getMessage());
        }

        return view('nfse.danfse_nacional', compact('dados', 'item'));
    }

    private function prepararDadosPdf($item)
    {
        // Reaproveita a mesma lógica chamando os dados completos
        $response = $this->imprimir($item->id);
        return $response->getData()['dados'] ?? [];
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
        $tributacao = Tributacao::where('empresa_id', $this->empresa_id)->first();
        $tiposPagamento = ['Dinheiro', 'Boleto', 'Cartão de Crédito', 'Cartão de Débito', 'Pix', 'Transferência'];

        // CORREÇÃO: Apontar para 'nfse.create' e enviar as variáveis corretas
        return view('nfse.create', compact('item', 'clientes', 'config', 'servicos', 'tiposPagamento', 'categorias', 'tributacao'))
            ->with('title', 'Editar NFS-e #' . $item->id);
    }

    // SUBSTITUI O ANTIGO PREVIEW DO CLOUD DFE
    public function previewXml($id)
    {
        $item = NotaServico::findOrFail($id);

        $possiveisXml = [
            !empty($item->uuid) ? public_path('nfse_doc/' . (str_ends_with($item->uuid, '.xml') ? $item->uuid : $item->uuid . '.xml')) : null,
            !empty($item->codigo_verificacao) ? public_path('nfse_doc/' . $item->codigo_verificacao . '.xml') : null,
            public_path('nfse_doc/NFSe_' . $item->id . '.xml'),
        ];

        // Se a nota já foi aprovada, mostra o XML real
        foreach (array_filter($possiveisXml) as $xmlPath) {
            if ($item->estado === 'aprovado' && file_exists($xmlPath)) {
                return response(file_get_contents($xmlPath))->header('Content-Type', 'application/xml');
            }
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
            'tomador_cidade'     => ($item->cidade->nome ?? $cliente->cidade->nome ?? 'Lauro de Freitas') . ' / ' . ($item->cidade->uf ?? 'BA'),
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

    /**
     * Duplica uma NFS-e existente gerando um novo rascunho
     */
    public function clone($id)
    {
        try {
            $original = NotaServico::where('empresa_id', $this->empresa_id)->with('servico')->findOrFail($id);

            DB::transaction(function () use ($original) {
                $novaNota = $original->replicate();
                $novaNota->estado = 'novo';
                $novaNota->numero_nfse = 0;
                $novaNota->codigo_verificacao = '';
                $novaNota->url_xml = '';
                $novaNota->url_pdf_nfse = '';
                $novaNota->url_pdf_rps = '';
                $novaNota->created_at = now();
                $novaNota->updated_at = now();
                $novaNota->save();

                if ($original->servico) {
                    $novoServico = $original->servico->replicate();
                    $novoServico->nfse_id = $novaNota->id;
                    $novoServico->save();
                }
            });

            session()->flash('mensagem_sucesso', 'NFS-e duplicada com sucesso!');
        } catch (\Throwable $e) {
            Log::error("Erro ao clonar NFS-e #{$id}: " . $e->getMessage());
            session()->flash('mensagem_erro', 'Erro ao duplicar NFS-e: ' . $e->getMessage());
        }

        return redirect('/nfse');
    }

    /*private function prepararDadosPdf($item) {
        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();
        $cliente = Cliente::with('cidade')->find($item->cliente_id);
        $servicoMaster = $item->servico ? ($item->servico->servico ?? Servico::find($item->servico->servico_id)) : null;

        $valorServico = (float) $item->valor_total;
        $aliquotaIss  = (float) ($item->servico->aliquota_iss ?? $servicoMaster->aliquota_iss ?? 3.00);
        $valorIss     = round($valorServico * ($aliquotaIss / 100), 2);
        $dataEmissao  = date('d/m/Y H:i:s', strtotime($item->updated_at));

        // Dados completos exigidos pelo DANFSe
        return [
            'chave'              => $item->codigo_verificacao ?? '29192072205693853000170000000000014026101412894024',
            'numero_nfse'        => $item->numero_nfse > 0 ? $item->numero_nfse : $item->id,
            'numero_dps'         => $item->id,
            'serie_dps'          => $item->serie ?: '1',
            'competencia'        => date('d/m/Y', strtotime($item->created_at)),
            'data_emissao'       => $dataEmissao,
            'data_emissao_nfse'  => $dataEmissao,
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
            'tomador_cidade'     => ($item->cidade->nome ?? $cliente->cidade->nome ?? 'Lauro de Freitas') . ' / ' . ('BA'),
            'tomador_cep'        => $item->cep ?? ($cliente->cep ?? ''),
            'tomador_fone'       => $cliente->telefone ?? $cliente->celular ?? '-',

            'codigo_servico'     => $item->servico->codigo_servico ?? '030303',
            'codigo_nbs'         => $servicoMaster->codigo_nbs ?? '123012200',
            'descricao_trib_nac' => 'Serviço Prestado',
            'descricao_nbs'      => 'Serviço Especializado',
            'discriminacao'      => $item->servico->discriminacao ?? 'REFERENTE SERVIÇO PRESTADO',
            'local_prestacao'    => 'Lauro de Freitas - BA',

            'valor_total'        => $valorServico,
            'valor_servico'      => $valorServico,
            'valor_liquido'      => $valorServico,
            'aliquota_iss'       => number_format($aliquotaIss, 2, ',', '.'),
            'valor_iss'          => $valorIss,
            'iss_retido'         => ((int) ($item->servico->iss_retido ?? 0) === 1),

            'pis_proprio'                 => 0,
            'cofins_proprio'              => 0,
            'v_ret_irrf'                  => 0,
            'v_ret_cp'                    => 0,
            'total_contribuicoes_sociais' => 0,
            'ibscbs_bc'                   => $valorServico,
            'cst_ibscbs'                  => '200',
            'class_trib'                  => '200029',
            'val_cbs'                     => 0,
            'val_ibs_est'                 => 0,
            'val_total_ibscbs'            => 0,
        ];
    }*/

    public function importarMedicao(Request $request)
    {
        $medicaoId = $request->input('fatura_eng_id');
        $fatura = \App\Models\FaturaEngenharia::with(['itens.servico', 'cliente', 'contrato'])
            ->where('empresa_id', $this->empresa_id)
            ->findOrFail($medicaoId);

        if ($fatura->status_aprovacao !== 'aprovado') {
            return redirect()->back()->with('mensagem_erro', 'Esta medição ainda não foi aprovada pelo cliente no portal.');
        }

        // >>> INÍCIO DA MELHORIA 1: VERIFICAÇÃO DE NOTAS EXISTENTES <<<
        // Procura se já existem notas para esta medição que NÃO estejam canceladas nem rejeitadas
        $notasExistentes = DB::table('fatura_eng_notas')
            ->where('fatura_eng_id', $medicaoId)
            ->whereNotIn('status', ['cancelado', 'rejeitado'])
            ->get();

        if ($notasExistentes->count() > 0) {
            $msg = 'Atenção! Esta medição já possui notas fiscais ativas ou em processamento (';
            $numeros = [];
            foreach ($notasExistentes as $n) {
                $numeros[] = $n->numero_nfse > 0 ? '#' . $n->numero_nfse : '(Rascunho)';
            }
            $msg .= implode(', ', $numeros) . '). Só é possível re-importar se as notas anteriores forem canceladas.';

            return redirect()->back()->with('mensagem_erro', $msg);
        }
        // >>> FIM DA MELHORIA 1 <<<

        try {
            DB::transaction(function () use ($fatura) {
                $tributacao = Tributacao::where('empresa_id', $this->empresa_id)->first();

                // Agrupa os itens por serviço fiscal para gerar múltiplas notas se necessário
                $gruposServicos = [];
                foreach ($fatura->itens as $it) {
                    $sId = $it->servico_id ?? $fatura->servico_id;
                    if (!isset($gruposServicos[$sId])) {
                        $gruposServicos[$sId] = [
                            'servico_id' => $sId,
                            'valor_total' => 0,
                            'discriminacao' => "Medição de Contrato #" . ($fatura->contrato->numero_contrato ?? $fatura->id) . "\n"
                        ];
                    }
                    $gruposServicos[$sId]['valor_total'] += (float)$it->valor_total;
                    $nomeItem = $it->descricao ?? $it->servico->nome ?? $it->produto->nome ?? 'Item';

                    // CORREÇÃO: Garante que os códigos/detalhes dos itens são levados para a discriminação
                    $codProduto = $it->produto_id ? " (Cód. {$it->produto_id})" : "";
                    $gruposServicos[$sId]['discriminacao'] .= "- {$nomeItem}{$codProduto} | Qtd: {$it->quantidade} | R$ " . number_format($it->valor_total, 2, ',', '.') . "\n";
                }

                $cliente = $fatura->cliente;

                // Gera um rascunho de NFS-e para cada grupo de serviço separado
                foreach ($gruposServicos as $grupo) {
                    $servicoObj = \App\Models\Servico::find($grupo['servico_id']);

                    // Cria a NFS-e principal respeitando a Matriz ou Filial da medição
                    $nfse = \App\Models\Nfse::create([
                        'empresa_id'         => $this->empresa_id,
                        'filial_id'          => $fatura->filial_id,
                        'valor_total'        => $grupo['valor_total'],
                        'estado'             => 'novo',
                        'serie'              => '1',
                        'cliente_id'         => $fatura->cliente_id,
                        'natureza_operacao'  => '1',
                        'documento'          => preg_replace('/\D/', '', $cliente->cpf_cnpj ?? ''),
                        'razao_social'       => $cliente->razao_social ?? $cliente->nome,
                        'cep'                => preg_replace('/\D/', '', $cliente->cep ?? ''),
                        'rua'                => $cliente->rua ?? 'Não informada',
                        'numero'             => $cliente->numero ?? 'S/N',
                        'bairro'             => $cliente->bairro ?? 'Centro',
                        'cidade_id'          => $cliente->cidade_id ?? $fatura->municipio_prestacao_id,
                        'email'              => $cliente->email ?? '',
                        'telefone'           => $cliente->telefone ?? $cliente->celular ?? ''
                    ]);

                    // Insere os detalhes do serviço fiscal vinculado
                    \App\Models\NfseServico::create([
                        'nfse_id'                        => $nfse->id,
                        'discriminacao'                  => trim($grupo['discriminacao']),
                        'valor_servico'                  => $grupo['valor_total'],
                        'servico_id'                     => $grupo['servico_id'],
                        'codigo_servico'                 => $servicoObj->codigo_servico ?? $servicoObj->codigo_tributacao_nacional ?? '1405',
                        'aliquota_iss'                   => $servicoObj->aliquota_iss ?? 3.00,
                        'aliquota_pis'                   => $tributacao->pis ?? 0.00,
                        'aliquota_cofins'                => $tributacao->cofins ?? 0.00,
                        'aliquota_ir'                    => $tributacao->ir ?? 0.00,
                        'aliquota_csll'                  => $tributacao->csll ?? 0.00,
                        'iss_retido'                     => $servicoObj->iss_retido ?? 2,
                        'cidade_local_prestacao_servico' => $fatura->municipio_prestacao_id
                    ]);

                    // Vincula na tabela auxiliar de notas da medição para rastreio
                    DB::table('fatura_eng_notas')->insert([
                        'fatura_eng_id' => $fatura->id,
                        'empresa_id'    => $this->empresa_id,
                        'numero_nfse'   => 0,
                        'chave_nfse'    => 'RASCUNHO-NFSE-' . $nfse->id,
                        'status'        => 'novo',
                        'created_at'    => now(),
                        'updated_at'    => now()
                    ]);
                }
            });

            return redirect()->route('nfse.index')->with('mensagem_sucesso', 'Medição importada com sucesso para a rotina de NFS-e! As notas estão prontas como rascunho.');

        } catch (\Throwable $e) {
            Log::error("Erro ao importar medição para NFS-e: " . $e->getMessage());
            return redirect()->back()->with('mensagem_erro', 'Erro ao importar medição: ' . $e->getMessage());
        }
    }

    public function gerarXmlLocal($id)
    {
        try {
            $item = NotaServico::with(['servico.servico', 'cidade'])->findOrFail($id);
            $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();
            $tributacao = Tributacao::where('empresa_id', $this->empresa_id)->first();
            $nfseServico = $item->servico;
            $servicoMaster = $nfseServico->servico ?? Servico::find($nfseServico->servico_id);
            $cliente = Cliente::with('cidade')->find($item->cliente_id);

            // 1. Emitente
            $codigoIbgeEmitente = str_pad(preg_replace('/\D/', '', $config->codMun ?? '2910057'), 7, '0', STR_PAD_LEFT);
            $cnpjEmitente = preg_replace('/\D/', '', $config->cnpj);
            $imEmitente = preg_replace('/\D/', '', $config->inscricao_municipal);

            // 2. Tomador
            $docTomador = preg_replace('/\D/', '', $item->documento ?? $cliente->cpf_cnpj);
            $tagDocTomador = strlen($docTomador) > 11 ? '<Cnpj>'.$docTomador.'</Cnpj>' : '<Cpf>'.$docTomador.'</Cpf>';
            $codigoIbgeTomador = preg_replace('/\D/', '', $item->cidade->codigo ?? $cliente->cidade->codigo ?? $codigoIbgeEmitente);

            // 3. Serviços, Valores e Indicador de Operação
            $cMunPrestacao = preg_replace('/\D/', '', $nfseServico->cidade_local_prestacao_servico ?? $codigoIbgeEmitente);
            $cIndOp = $servicoMaster->codigo_indicador_operacao ?? $servicoMaster->cIndOp ?? (($cMunPrestacao === $codigoIbgeEmitente) ? '050101' : '050201');

            $valorServico = (float) $item->valor_total;
            $aliquotaIss = (float) ($nfseServico->aliquota_iss ?? $servicoMaster->aliquota_iss ?? 3.00);
            $valorIss = round($valorServico * ($aliquotaIss / 100), 2);
            $issRetido = ((int) ($nfseServico->iss_retido ?? 0) === 1) ? 1 : 2;

            // 4. Reforma Tributária
            $rtService = new \App\Services\ReformaTributariaService();
            $itemCalculo = [
                'valor_total' => $valorServico,
                'cst_ibs_cbs' => $servicoMaster->cst_ibscbs ?? $tributacao->cst_ibs_cbs ?? '000',
                'class_trib_ibs_cbs' => $servicoMaster->codigo_class_trib ?? $tributacao->class_trib_ibs_cbs ?? '000001',
                'aliq_cbs' => (float)($tributacao->aliq_cbs ?? 0.9000),
                'aliq_ibs_uf' => (float)($tributacao->aliq_ibs_uf ?? 0.1000),
                'aliq_ibs_mun' => (float)($tributacao->aliq_ibs_mun ?? 0.0000),
            ];
            $rtService->calcularItem($itemCalculo, $this->empresa_id);

            $bcIbsCbs = number_format($itemCalculo['bc_ibs_cbs'] ?? $valorServico, 2, '.', '');
            $valIbsEst = number_format($itemCalculo['valor_ibs_uf'] ?? round($valorServico * 0.001, 2), 2, '.', '');
            $valCbs = number_format($itemCalculo['valor_cbs'] ?? round($valorServico * 0.009, 2), 2, '.', '');

            // 5. Montagem Completa do XML
            $xmlString = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xmlString .= '<GerarNfseEnvio xmlns="http://www.abrasf.org.br/nfse.xsd">' . "\n";
            $xmlString .= '  <Rps>' . "\n";
            $xmlString .= '    <InfDeclaracaoPrestacaoServico Id="Declaracao_' . $item->id . '">' . "\n";
            $xmlString .= '      <Rps Id="RPS_' . $item->id . '">' . "\n";
            $xmlString .= '        <IdentificacaoRps>' . "\n";
            $xmlString .= '          <Numero>' . $item->id . '</Numero>' . "\n";
            $xmlString .= '          <Serie>1</Serie>' . "\n";
            $xmlString .= '          <Tipo>1</Tipo>' . "\n";
            $xmlString .= '        </IdentificacaoRps>' . "\n";
            $xmlString .= '        <DataEmissao>' . date('Y-m-d', strtotime($item->created_at)) . '</DataEmissao>' . "\n";
            $xmlString .= '        <Status>1</Status>' . "\n";
            $xmlString .= '      </Rps>' . "\n";
            $xmlString .= '      <Competencia>' . date('Y-m-d', strtotime($item->created_at)) . '</Competencia>' . "\n";
            $xmlString .= '      <Servico>' . "\n";
            $xmlString .= '        <Valores>' . "\n";
            $xmlString .= '          <ValorServicos>' . number_format($valorServico, 2, '.', '') . '</ValorServicos>' . "\n";
            $xmlString .= '          <ValorIss>' . number_format($valorIss, 2, '.', '') . '</ValorIss>' . "\n";
            $xmlString .= '          <Aliquota>' . number_format($aliquotaIss, 4, '.', '') . '</Aliquota>' . "\n";
            $xmlString .= '        </Valores>' . "\n";
            $xmlString .= '        <IssRetido>' . $issRetido . '</IssRetido>' . "\n";
            $xmlString .= '        <ResponsavelRetencao>1</ResponsavelRetencao>' . "\n";
            $xmlString .= '        <ItemListaServico>' . preg_replace('/[^0-9.]/', '', $nfseServico->codigo_servico ?? '140501') . '</ItemListaServico>' . "\n";
            $xmlString .= '        <CodigoNbs>' . preg_replace('/\D/', '', $servicoMaster->codigo_nbs ?? '120029000') . '</CodigoNbs>' . "\n";
            $xmlString .= '        <Discriminacao>' . htmlspecialchars($nfseServico->discriminacao ?? 'SERVICO PRESTADO') . '</Discriminacao>' . "\n";
            $xmlString .= '        <CodigoMunicipio>' . $cMunPrestacao . '</CodigoMunicipio>' . "\n";
            $xmlString .= '        <ExigibilidadeISS>1</ExigibilidadeISS>' . "\n";
            $xmlString .= '        <MunicipioIncidencia>' . $cMunPrestacao . '</MunicipioIncidencia>' . "\n";
            $xmlString .= '      </Servico>' . "\n";
            $xmlString .= '      <Prestador>' . "\n";
            $xmlString .= '        <CpfCnpj><Cnpj>' . $cnpjEmitente . '</Cnpj></CpfCnpj>' . "\n";
            $xmlString .= '        <InscricaoMunicipal>' . $imEmitente . '</InscricaoMunicipal>' . "\n";
            $xmlString .= '      </Prestador>' . "\n";
            $xmlString .= '      <Tomador>' . "\n";
            $xmlString .= '        <IdentificacaoTomador><CpfCnpj>' . $tagDocTomador . '</CpfCnpj></IdentificacaoTomador>' . "\n";
            $xmlString .= '        <RazaoSocial>' . htmlspecialchars($item->razao_social ?? $cliente->razao_social) . '</RazaoSocial>' . "\n";
            $xmlString .= '        <Endereco>' . "\n";
            $xmlString .= '          <Endereco>' . htmlspecialchars($item->rua ?? $cliente->rua) . '</Endereco>' . "\n";
            $xmlString .= '          <Numero>' . htmlspecialchars($item->numero ?? $cliente->numero) . '</Numero>' . "\n";
            $xmlString .= '          <Bairro>' . htmlspecialchars($item->bairro ?? $cliente->bairro) . '</Bairro>' . "\n";
            $xmlString .= '          <CodigoMunicipio>' . $codigoIbgeTomador . '</CodigoMunicipio>' . "\n";
            $xmlString .= '          <Uf>' . ($item->cidade->uf ?? 'BA') . '</Uf>' . "\n";
            $xmlString .= '          <Cep>' . preg_replace('/\D/', '', $item->cep ?? $cliente->cep) . '</Cep>' . "\n";
            $xmlString .= '        </Endereco>' . "\n";
            $xmlString .= '      </Tomador>' . "\n";
            $xmlString .= '      <OptanteSimplesNacional>' . ($config->opcao_simples_nacional == 1 ? 1 : 2) . '</OptanteSimplesNacional>' . "\n";
            $xmlString .= '      <IncentivoFiscal>2</IncentivoFiscal>' . "\n";
            $xmlString .= '      <IbsCbs>' . "\n";
            $xmlString .= '        <MunicipioIncidencia>' . $cMunPrestacao . '</MunicipioIncidencia>' . "\n";
            $xmlString .= '        <IndicadorOperacao>' . $cIndOp . '</IndicadorOperacao>' . "\n";
            $xmlString .= '        <ClassificacaoTributaria>' . ($itemCalculo['class_trib_ibs_cbs'] ?? '000001') . '</ClassificacaoTributaria>' . "\n";
            $xmlString .= '        <IndicadorDestino>0</IndicadorDestino>' . "\n";
            $xmlString .= '        <BaseCalculo>' . $bcIbsCbs . '</BaseCalculo>' . "\n";
            $xmlString .= '        <IbsMunicipal><Percentual>0.00</Percentual><PercAliqEfet>0.00</PercAliqEfet><Valor>0.00</Valor></IbsMunicipal>' . "\n";
            $xmlString .= '        <IbsEstadual><Percentual>0.10</Percentual><PercAliqEfet>0.10</PercAliqEfet><Valor>' . $valIbsEst . '</Valor></IbsEstadual>' . "\n";
            $xmlString .= '        <Cbs><Percentual>0.90</Percentual><PercAliqEfet>0.90</PercAliqEfet><Valor>' . $valCbs . '</Valor></Cbs>' . "\n";
            $xmlString .= '      </IbsCbs>' . "\n";
            $xmlString .= '    </InfDeclaracaoPrestacaoServico>' . "\n";
            $xmlString .= '  </Rps>' . "\n";
            $xmlString .= '</GerarNfseEnvio>';

            // Salva e força download
            $xmlName = 'RPS_Local_' . $item->id . '.xml';
            $caminhoCompleto = public_path('nfse_doc/' . $xmlName);
            file_put_contents($caminhoCompleto, $xmlString);

            return response()->download($caminhoCompleto, $xmlName, [
                'Content-Type' => 'application/xml',
            ]);

        } catch (\Throwable $th) {
            return redirect()->back()->with('mensagem_erro', 'Erro ao gerar XML local: ' . $th->getMessage());
        }
    }

    public function importarXml(Request $request)
    {
        $request->validate([
            'id_nota' => 'required|integer',
            'xml_file' => 'required|file|mimes:xml'
        ]);

        $id = $request->id_nota;
        $file = $request->file('xml_file');

        try {
            $item = NotaServico::where('empresa_id', $this->empresa_id)->findOrFail($id);
            $xmlContent = file_get_contents($file->getRealPath());

            // Carrega o XML (usando a tag base NFSe)
            $xml = simplexml_load_string($xmlContent);
            if ($xml === false) {
                return response()->json(['erro' => true, 'mensagem' => 'Ficheiro XML inválido.'], 400);
            }

            // Lê as informações do XML usando o namespace para lidar com as tags
            $ns = $xml->getNamespaces(true);
            $infNFSe = $xml->children($ns[''])->infNFSe;

            if (!$infNFSe) {
                return response()->json(['erro' => true, 'mensagem' => 'O XML não é uma NFS-e válida.'], 400);
            }

            // Extração de dados da NFS-e
            $numeroNfse = (int)$infNFSe->nNFSe;
            // A chave (codigo_verificacao) vem do atributo Id da tag infNFSe
            $chave = str_replace('NFS', '', (string)$infNFSe['Id']);

            // Se o XML veio com a tag DPS, tentaremos pegar a chave do ID do DPS também (como no seu exemplo)
            if(empty($chave)) {
                $chave = (string)$infNFSe->DPS->infDPS['Id'] ?? null;
            }

            // Nomeia e salva o arquivo
            $xmlName = (strlen($chave) >= 40) ? $chave . '.xml' : 'NFSe_' . $item->id . '.xml';
            $file->move(public_path('nfse_doc'), $xmlName);

            // Atualiza os dados da nota no banco
            $item->estado = 'aprovado';
            $item->numero_nfse = $numeroNfse;
            $item->codigo_verificacao = $chave;
            $item->uuid = $xmlName;
            $item->url_xml = asset('nfse_doc/' . $xmlName);
            // Aqui mantemos a URL de impressão que aponta para o sistema de Dias d'Ávila, conforme o código existente
            $item->url_pdf_nfse = "https://diasdavila.saatri.com.br/ConsultaNota/Visualizar?codigo=" . $chave;
            $item->motivo_rejeicao = null;
            $item->save();

            $this->atualizarStatusMedicaoNota($item->id, 'aprovado', $numeroNfse, $chave);

            // Atualiza a tabela de conta_recebers, se existir
            DB::table('conta_recebers')
                ->where('empresa_id', $this->empresa_id)
                ->where('referencia', 'like', "NFS-e #" . $item->id . "%")
                ->update([
                    'nf_numero'  => $item->numero_nfse,
                    'nf_modelo'  => 55, // Assumindo modelo de NFS-e
                    'status' => 1,
                    'updated_at' => now()
                ]);

            return response()->json(['sucesso' => true, 'mensagem' => 'XML importado com sucesso. Nota atualizada para aprovada!']);

        } catch (\Throwable $th) {
            Log::error("Erro na importação XML da NFSe #{$id}: " . $th->getMessage());
            return response()->json(['erro' => true, 'mensagem' => 'Erro ao processar ficheiro: ' . $th->getMessage()], 500);
        }
    }

    /**
     * Função auxiliar para atualizar o status da medição ao aprovar/cancelar uma nota.
     */
    private function atualizarStatusMedicaoNota($nfseId, $statusNovo, $numeroNfse = null, $chave = null)
    {
        // 1. Procura o vínculo usando a chave de rascunho ou a chave oficial
        $vinculo = DB::table('fatura_eng_notas')
            ->where('empresa_id', $this->empresa_id)
            ->where(function($query) use ($nfseId, $chave) {
                $query->where('chave_nfse', 'RASCUNHO-NFSE-' . $nfseId);
                if ($chave) {
                    $query->orWhere('chave_nfse', $chave);
                }
            })
            ->first();

        if ($vinculo) {
            // Atualiza os dados na tabela de vínculo
            $updateDados = [
                'status' => $statusNovo,
                'updated_at' => now()
            ];

            if ($numeroNfse) $updateDados['numero_nfse'] = $numeroNfse;
            if ($chave) $updateDados['chave_nfse'] = $chave;

            DB::table('fatura_eng_notas')
                ->where('id', $vinculo->id)
                ->update($updateDados);

            // 2. Atualiza a medição na tabela principal para que apareça na listagem
            if ($statusNovo === 'aprovado') {
                $dadosFatura = [
                    'status' => 'Finalizado',
                    'estado_nfse' => 'aprovado'
                ];

                // Grava o número e a chave diretamente na medição para exibir na grelha
                if ($numeroNfse) $dadosFatura['numero_nfse'] = $numeroNfse;
                if ($chave) $dadosFatura['chave_nfse'] = $chave;
                if ($chave) $dadosFatura['protocolo_nfse'] = $chave;

                // Tenta atualizar a coluna estado_nfse se ela existir (pode dar erro silencioso se não existir, por isso usamos try)
                try {
                    DB::table('faturas_engenharia')
                        ->where('id', $vinculo->fatura_eng_id)
                        ->update($dadosFatura);
                } catch (\Exception $e) {
                    // Se a coluna estado_nfse não existir, atualiza só o que existe
                    unset($dadosFatura['estado_nfse']);
                    DB::table('faturas_engenharia')
                        ->where('id', $vinculo->fatura_eng_id)
                        ->update($dadosFatura);
                }
            }
            elseif ($statusNovo === 'cancelado') {
                $outrasAtivas = DB::table('fatura_eng_notas')
                    ->where('fatura_eng_id', $vinculo->fatura_eng_id)
                    ->whereNotIn('status', ['cancelado', 'rejeitado'])
                    ->count();

                if ($outrasAtivas === 0) {
                    try {
                        DB::table('faturas_engenharia')
                            ->where('id', $vinculo->fatura_eng_id)
                            ->update(['status' => 'Cancelado', 'estado_nfse' => 'cancelado']);
                    } catch (\Exception $e) {
                        DB::table('faturas_engenharia')
                            ->where('id', $vinculo->fatura_eng_id)
                            ->update(['status' => 'Cancelado']);
                    }
                }
            }
        }
    }
}
