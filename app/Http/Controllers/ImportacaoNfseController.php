<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Fornecedor;
use App\Models\Compra;
use App\Models\ItemCompra;
use App\Models\Produto;
use App\Models\ContaPagar;
use App\Models\Veiculo;
use App\Models\CategoriaConta;
use App\Models\Categoria;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportacaoNfseController extends BaseController
{
    public function __construct()
    {
        $this->model = Compra::class;
        $this->redirectPage = '/compras/importacaoNfse';
        $this->formTitle = 'Importação de NFS-e (Serviço)';
        parent::__construct();
    }

    protected function rules(): array { return ['xmls' => 'required', 'categoria_conta_id' => 'required', 'prazo_pagamento' => 'required']; }
    protected function messages(): array { return ['xmls.required' => 'Selecione os XMLs.', 'categoria_conta_id.required' => 'Selecione a categoria.', 'prazo_pagamento.required' => 'Informe o prazo.']; }

    public function index(Request $request = null)
    {
        $veiculos = Veiculo::where('empresa_id', $this->empresa_id)->get();
        $categoriasDeConta = CategoriaConta::where('empresa_id', $this->empresa_id)->where('tipo', 'pagar')->orderBy('nome', 'asc')->get();
        return view('nfse.importar', ['veiculos' => $veiculos, 'categoriasDeConta' => $categoriasDeConta, 'title' => $this->formTitle]);
    }

    public function importarLote(Request $request)
    {
        if (!$this->validateRequest($request)) return redirect()->back()->withInput();
        $arquivos = $request->file('xmls');
        $sucessos = 0; $erros = [];

        if (!file_exists(public_path('xml_servico'))) mkdir(public_path('xml_servico'), 0777, true);

        foreach ($arquivos as $arquivo) {
            try {
                DB::beginTransaction();
                $xmlString = file_get_contents($arquivo);
                $xmlClean = preg_replace('/ xmlns[^=]*="[^"]*"/i', '', $xmlString);
                $xml = simplexml_load_string($xmlClean);

                if (!isset($xml->infNFSe)) throw new \Exception("XML não reconhecido como NFS-e Nacional.");

                // Pega a chave completa (Id) sem o prefixo NFS
                $chaveCompleta = str_replace('NFS', '', (string)$xml->infNFSe['Id']);
                $nomeArquivo = $chaveCompleta . ".xml";
                
                // Move o arquivo para a pasta definitiva
                $arquivo->move(public_path('xml_servico'), $nomeArquivo);

                $this->processarXml($xml, $request->veiculo_id, $request->categoria_conta_id, $request->prazo_pagamento, $nomeArquivo, $chaveCompleta);

                DB::commit();
                $sucessos++;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Erro Importação NFS-e: " . $e->getMessage());
                $erros[] = "Erro no arquivo " . $arquivo->getClientOriginalName() . ": " . $e->getMessage();
            }
        }
        
        session()->flash($sucessos > 0 ? 'mensagem_sucesso' : 'mensagem_erro', "$sucessos notas importadas. " . implode(' ', $erros));
        return redirect('/compras');
    }

    private function processarXml($xml, $veiculo_id, $categoria_id, $prazo, $nomeArquivo, $chave)
    {
        $inf = $xml->infNFSe;
        $dps = $inf->DPS->infDPS ?? $inf;
        $data_emi = isset($dps->dhEmi) ? substr((string)$dps->dhEmi, 0, 10) : date('Y-m-d');
        $vencimento = date('Y-m-d', strtotime($data_emi . " +{$prazo} days"));
        $valor = (float)$inf->valores->vLiq > 0 ? (float)$inf->valores->vLiq : (float)$inf->valores->vServ;
        $nNFSe = (string)$inf->nNFSe;

        $fornecedor = $this->obterFornecedor($inf->emit, (string)$inf->emit->CNPJ);
        
        // Dados do Serviço
        $servBloco = $dps->serv->cServ ?? $inf->serv->cServ;
        $descricaoServico = (string)$servBloco->xDescServ;
        $tribMun = (string)$inf->xTribMun;

        $servico = $this->obterServico((string)($servBloco->cTribNac ?? '140101'), $descricaoServico, $valor);

        // Prepara descrições limpas
        $obs = trim(str_replace(["\r", "\n"], ' ', $descricaoServico . " | Trib. Mun: " . $tribMun));
        $refFinanceiro = "NFS-e " . $nNFSe . " - " . substr($obs, 0, 180);

        // 1. Grava a Compra (Estado IMPORTADO e Numero Emissão 0)
        $this->disableNextModelAudit();
        $compra = Compra::create([
            'fornecedor_id' => $fornecedor->id,
            'usuario_id' => $this->usuario_id,
            'nf' => $nNFSe,
            'data_emissao' => $data_emi,
            'valor' => $valor,
            'veiculo_id' => $veiculo_id,
            'estado' => 'IMPORTADO',
            'xml_importado' => 1,
            'xml_path' => $nomeArquivo, // Nome real do arquivo físico
            'categoria_conta_id' => $categoria_id,
            'chave' => $chave, // Chave completa (50 dígitos)
            'empresa_id' => $this->empresa_id,
            'filial_id' => $this->filial_id,
            'observacao' => $obs,
            'numero_emissao' => 0
        ]);

        // 2. Grava o Item da Compra (Fundamental para a tela de edição não quebrar)
        ItemCompra::create([
            'compra_id' => $compra->id,
            'produto_id' => $servico->id,
            'quantidade' => 1,
            'valor_unitario' => $valor,
            'unidade_compra' => 'UN',
            'cfop_entrada' => '1933'
        ]);

        // 3. Grava o Financeiro
        ContaPagar::create([
            'compra_id' => $compra->id,
            'fornecedor_id' => $fornecedor->id,
            'data_vencimento' => $vencimento,
            'data_emissao' => $data_emi,
            'valor_integral' => $valor,
            'valor_original' => $valor,
            'status' => false,
            'referencia' => $refFinanceiro,
            'categoria_id' => $categoria_id,
            'empresa_id' => $this->empresa_id,
            'filial_id' => $this->filial_id,
            'veiculo_id' => $veiculo_id,
            'numero_nota_fiscal' => $nNFSe
        ]);
    }

    private function obterFornecedor($emit, $cnpj)
    {
        $cnpjLimpo = preg_replace('/[^0-9]/', '', $cnpj);
        $forn = Fornecedor::where('empresa_id', $this->empresa_id)->where(function($q) use ($cnpjLimpo){
            $q->where('cpf_cnpj', $cnpjLimpo)->orWhere('cpf_cnpj', $this->formataCnpj($cnpjLimpo));
        })->first();

        if (!$forn) {
            $forn = Fornecedor::create([
                'cpf_cnpj' => $this->formataCnpj($cnpjLimpo), 'razao_social' => (string)$emit->xNome, 'nome_fantasia' => (string)$emit->xNome,
                'rua' => (string)$emit->enderNac->xLgr ?? '', 'numero' => (string)$emit->enderNac->nro ?? '', 'bairro' => (string)$emit->enderNac->xBairro ?? '',
                'empresa_id' => $this->empresa_id, 'cidade_id' => 1
            ]);
        }
        return $forn;
    }

    private function obterServico($cod, $desc, $valor)
    {
        $s = Produto::where('empresa_id', $this->empresa_id)->where('referencia', $cod)->first();
        if (!$s) {
            $cat = Categoria::where('empresa_id', $this->empresa_id)->first();
            $s = Produto::create([
                'nome' => trim(substr(str_replace(["\r", "\n"], ' ', $desc), 0, 100)),
                'referencia' => $cod,
                'valor_compra' => $valor,
                'valor_venda' => $valor,
                'gerenciar_estoque' => 0,
                'categoria_id' => $cat->id ?? 1,
                'empresa_id' => $this->empresa_id,
                'locais' => '["-1"]', // Garante vínculo com a Matriz
                'tipo_item' => '09', // Serviço (SPED)
                'unidade_compra' => 'UN',
                'unidade_venda' => 'UN',
                'conversao_unitaria' => 1,
                'percentual_lucro' => 0,
                'estoque_minimo' => 0,
                'inativo' => 0
            ]);
        }
        return $s;
    }

    public function visualizar($id)
    {
        $compra = Compra::findOrFail($id);
        $arquivo = str_contains($compra->xml_path, '.xml') ? $compra->xml_path : $compra->xml_path . ".xml";
        
        $pastas = ['xml_servico', 'xml_entrada'];
        $caminhoFinal = null;

        foreach ($pastas as $pasta) {
            $teste = public_path($pasta . DIRECTORY_SEPARATOR . $arquivo);
            if (file_exists($teste)) { $caminhoFinal = $teste; break; }
        }

        if (!$caminhoFinal) return "Xml não encontrado! Arquivo: $arquivo";

        $xml = simplexml_load_string(preg_replace('/ xmlns[^=]*="[^"]*"/i', '', file_get_contents($caminhoFinal)));
        return view('nfse.visualizar', ['xml' => $xml, 'title' => 'DANFSE']);
    }

    private function formataCnpj($c){ return strlen($c) == 14 ? substr($c,0,2).'.'.substr($c,2,3).'.'.substr($c,5,3).'/'.substr($c,8,4).'-'.substr($c,12,2) : $c; }
}