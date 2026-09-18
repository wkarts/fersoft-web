<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cte;
use App\Models\ContaReceber;
use App\Models\Cliente;
use App\Models\Cidade;
use App\Models\CategoriaConta;
use App\Models\NaturezaOperacao;
use App\Models\Veiculo;
use Illuminate\Support\Facades\DB;

class ImportacaoCteController extends BaseController
{
    public function __construct()
    {
        $this->model = Cte::class;
        $this->redirectPage = '/importarCte';
        parent::__construct();
    }

    protected function rules(): array 
    { 
        return [
            'xmls' => 'required',
            'categoria_id' => 'required',
            'prazo' => 'required'
        ]; 
    }

    protected function messages(): array 
    { 
        return [
            'xmls.required' => 'Selecione os arquivos XML.',
            'categoria_id.required' => 'A categoria é obrigatória.',
            'prazo.required' => 'O prazo é obrigatório.'
        ]; 
    }

    public function index(Request $request = null)
    {
        $categorias = CategoriaConta::where('empresa_id', $this->empresa_id)
            ->where('tipo', 'LIKE', 'receber%')
            ->orderBy('nome', 'asc')
            ->get();

        if ($categorias->isEmpty()) {
            $categorias = CategoriaConta::where('tipo', 'LIKE', 'receber%')->get();
        }

        return view('cte.importar', [
            'categorias' => $categorias, 
            'title' => 'Importação de CT-e'
        ]);
    }

    public function importarLote(Request $request)
    {
        $arquivos = $request->file('xmls');
        $prazo = $request->prazo;
        $sucessos = 0;
        $duplicados = 0;

        $user_logged = session('user_logged');
        $empresa_id = $user_logged['empresa'];
        $filial_id = $request->input('filial_id');
        if ($filial_id <= 0) { $filial_id = null; }

        foreach ($arquivos as $arquivo) {
            try {
                $content = file_get_contents($arquivo);
                $xml = simplexml_load_string($content);

                // Busca o infCte independente do prefixo ou namespace
                $nodes = $xml->xpath('//*[local-name()="infCte"]');

                if (empty($nodes)) {
                    continue;
                }

                $infCte = $nodes[0];
                $nCT = (string)$infCte->ide->nCT;
                $chave = str_replace('CTe', '', (string)$infCte['Id']);
                $dataEmi = substr((string)$infCte->ide->dhEmi, 0, 10);

                // --- CORREÇÃO: Verificação ÚNICA e consolidada ---
                $queryCte = Cte::where('empresa_id', $empresa_id)->where('cte_numero', $nCT);
                $queryConta = ContaReceber::where('empresa_id', $empresa_id)->where('numero_nota_fiscal', $nCT)->where('nf_modelo', '57');

                if ($filial_id) {
                    $queryCte->where('filial_id', $filial_id);
                    $queryConta->where('filial_id', $filial_id);
                } else {
                    $queryCte->whereNull('filial_id');
                    $queryConta->whereNull('filial_id');
                }

                if ($queryCte->exists() || $queryConta->exists()) {
                    $duplicados++;
                    continue;
                }

                $infCte = $nodes[0];
                $nCT = (string)$infCte->ide->nCT;
                $chave = str_replace('CTe', '', (string)$infCte['Id']);
                
                // EXTRAI A DATA DE EMISSÃO DO XML (Ano-Mês-Dia)
                $dataEmi = substr((string)$infCte->ide->dhEmi, 0, 10);

                

                DB::beginTransaction();

                // 1. Busca IDs obrigatórios (FKs)
                $natOpDesc = (string)$infCte->ide->natOp;
                // 2. CORREÇÃO: Busca de Natureza e Veículo
                // Adicione o filtro de filial aqui, caso existam registros vinculados à filial no seu BD
                $natureza = NaturezaOperacao::where('empresa_id', $empresa_id)
                            ->when($filial_id, function($q) use ($filial_id) { $q->where('filial_id', $filial_id); })
                            ->where('natureza', 'LIKE', "%$natOpDesc%")
                            ->first() 
                            ?? NaturezaOperacao::where('empresa_id', $empresa_id)->first();

                if (!$natureza) throw new \Exception("Natureza de operação não encontrada.");

                $veiculo = Veiculo::where('empresa_id', $empresa_id)
                            ->when($filial_id, function($q) use ($filial_id) { $q->where('filial_id', $filial_id); })
                            ->first();

                if (!$veiculo) throw new \Exception("Veículo não encontrado.");

                $cidEnvio = Cidade::where('codigo', (string)$infCte->ide->cMunEnv)->first();
                $cidIni = Cidade::where('codigo', (string)$infCte->ide->cMunIni)->first();
                $cidFim = Cidade::where('codigo', (string)$infCte->ide->cMunFim)->first();

                // 2. Identifica Participantes
                $remetente = $this->buscarOuCriarParticipante($infCte->rem, $empresa_id);
                $destinatario = $this->buscarOuCriarParticipante($infCte->dest, $empresa_id);
                $clienteTomador = $this->identificarTomador($infCte, $empresa_id);

                // 3. Grava o CT-e preenchendo os campos NOT NULL do seu banco
                Cte::create([
                    'empresa_id' => $empresa_id,
                    'filial_id' => $filial_id,
                    'usuario_id' => $this->usuario_id,
                    'remetente_id' => $remetente->id,
                    'destinatario_id' => $destinatario->id,
                    'natureza_id' => $natureza->id,
                    'veiculo_id' => $veiculo->id,
                    'municipio_envio' => $cidEnvio->id ?? 1,
                    'municipio_inicio' => $cidIni->id ?? 1,
                    'municipio_fim' => $cidFim->id ?? 1,
                    'municipio_tomador' => $cidEnvio->id ?? 1,
                    'chave' => $chave,
                    'cte_numero' => $nCT,
                    'data_emissao' => $dataEmi, // <--- ADICIONADO: Salvando a data correta no BD!
                    'chave_nfe' => (string)($infCte->infCTeNorm->infDoc->infNFe->chave ?? ''),
                    'valor_transporte' => (float)$infCte->vPrest->vTPrest,
                    'valor_receber' => (float)$infCte->vPrest->vRec,
                    'valor_carga' => (float)($infCte->infCTeNorm->infCarga->vCarga ?? 0),
                    'produto_predominante' => (string)($infCte->infCTeNorm->infCarga->proPred ?? 'DIVERSOS'),
                    'data_previsata_entrega' => date('Y-m-d', strtotime($dataEmi . ' + 1 day')),
                    'tomador' => (int)($infCte->ide->toma3->toma ?? 3),
                    'estado' => 'APROVADO',
                    'path_xml' => $chave . '.xml',
                    'retira' => (int)($infCte->ide->retira ?? 0),
                    'modal' => (string)($infCte->ide->modal ?? '01'),
                    'tpDoc' => '00', 'observacao' => '', 'sequencia_cce' => 0, 'detalhes_retira' => '', 'descOutros' => '', 'nDoc' => 0, 'vDocFisc' => 0, 'globalizado' => 0
                ]);

                // 4. Grava no Contas a Receber
                $dataVencimento = ($prazo == '0') ? $dataEmi : date('Y-m-d', strtotime($dataEmi . " + $prazo days"));
                ContaReceber::create([
                    'empresa_id' => $empresa_id,
                    'filial_id' => $filial_id,
                    'usuario_id' => $this->usuario_id,
                    'cliente_id' => $clienteTomador->id,
                    'categoria_id' => $request->categoria_id,
                    'nf_modelo' => '57',
                    'nf_chave' => $chave,
                    'nf_data_emissao' => $dataEmi,
                    'numero_nota_fiscal' => $nCT,
                    'valor_integral' => (float)$infCte->vPrest->vRec,
                    'data_vencimento' => $dataVencimento,
                    'data_emissao' => date('Y-m-d'),
                    'status' => false,
                    'referencia' => "CT-e " . $nCT,
                ]);

                DB::commit();

                // 5. SALVA O XML FISICAMENTE NA PASTA (MUITO IMPORTANTE PARA O SPED LER DEPOIS)
                $pastaXml = public_path('xml_cte');
                if (!file_exists($pastaXml)) {
                    mkdir($pastaXml, 0777, true);
                }
                file_put_contents($pastaXml . '/' . $chave . '.xml', $content);

                $sucessos++;
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error("Erro no CT-e $nCT: " . $e->getMessage());
            }
        }
        session()->flash('mensagem_sucesso', "Importados: $sucessos | Ignorados: $duplicados");
        return redirect($this->redirectPage);
    }

    private function buscarOuCriarParticipante($node, $empresa_id)
    {
        $cnpj = (string)($node->CNPJ ?? $node->CPF);
        $cliente = Cliente::where('cpf_cnpj', $cnpj)->where('empresa_id', $empresa_id)->first();
        if (!$cliente) {
            $cliente = Cliente::create([
                'empresa_id' => $empresa_id,
                'razao_social' => (string)$node->xNome,
                'cpf_cnpj' => $cnpj,
                'cidade_id' => 1 
            ]);
        }
        return $cliente;
    }

    private function identificarTomador($infCte, $empresa_id)
    {
        $tomaPos = (int)($infCte->ide->toma3->toma ?? 3);
        $tags = ['rem', 'exped', 'receb', 'dest'];
        $tagBusca = $tags[$tomaPos] ?? 'dest';
        return $this->buscarOuCriarParticipante($infCte->$tagBusca, $empresa_id);
    }
}