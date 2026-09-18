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

    protected function rules(): array
    {
        return [
            'xmls' => 'required',
            'categoria_conta_id' => 'required',
            'prazo_pagamento' => 'required',
            'quantidade_parcelas' => 'required|integer|min:1'
        ];
    }

    protected function messages(): array
    {
        return [
            'xmls.required' => 'Selecione os XMLs.',
            'categoria_conta_id.required' => 'Selecione a categoria.',
            'prazo_pagamento.required' => 'Informe o prazo.',
            'quantidade_parcelas.required' => 'Informe a quantidade de parcelas.'
        ];
    }

    public function index(Request $request = null)
    {
        $veiculos = Veiculo::where('empresa_id', $this->empresa_id)->get();
        $categoriasDeConta = CategoriaConta::where('empresa_id', $this->empresa_id)
            ->where('tipo', 'pagar')
            ->orderBy('nome', 'asc')
            ->get();
        return view('nfse.importar', [
            'veiculos' => $veiculos,
            'categoriasDeConta' => $categoriasDeConta,
            'title' => $this->formTitle
        ]);
    }

    public function importarLote(Request $request)
    {
        $arquivos = $request->file('xmls');
        $sucessos = 0;
        $erros = [];

        if (!file_exists(public_path('xml_servico'))) {
            mkdir(public_path('xml_servico'), 0777, true);
        }

        foreach ($arquivos as $arquivo) {
            try {
                DB::beginTransaction();
                $xmlString = file_get_contents($arquivo);
                $xmlClean = preg_replace('/ xmlns[^=]*="[^"]*"/i', '', $xmlString);
                $xml = simplexml_load_string($xmlClean);

                if (!isset($xml->infNFSe)) throw new \Exception("XML não reconhecido.");

                $idAtributo = (string)$xml->infNFSe['Id'];
                $chaveCompleta = preg_replace('/[^0-9]/', '', $idAtributo);
                $nNFSe = (string)$xml->infNFSe->nNFSe;

                $jaExiste = Compra::where('chave', $chaveCompleta)->where('empresa_id', $this->empresa_id)->first();
                if ($jaExiste) throw new \Exception("A nota Nº $nNFSe já foi importada.");

                $nomeArquivo = trim($chaveCompleta) . ".xml";
                $arquivo->move(public_path('xml_servico'), $nomeArquivo);

                $this->processarXml(
                    $xml,
                    $request->veiculo_id,
                    $request->categoria_conta_id,
                    $request->prazo_pagamento,
                    $nomeArquivo,
                    $chaveCompleta,
                    $request->input('quantidade_parcelas', 1)
                );

                DB::commit();
                $sucessos++;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Erro Importação NFS-e: " . $e->getMessage());
                $erros[] = "Erro no arquivo " . $arquivo->getClientOriginalName() . ": " . $e->getMessage();
            }
        }

        session()->flash($sucessos > 0 ? 'mensagem_sucesso' : 'mensagem_erro', "$sucessos notas importadas.");
        return redirect('/compras');
    }

    private function processarXml($xml, $veiculo_id, $categoria_id, $prazo, $nomeArquivo, $chave, $qtdParcelas)
    {
        $inf = $xml->infNFSe;
        $valores = $inf->valores;
        $dps = $inf->DPS->infDPS ?? $inf;
        $data_emi = isset($dps->dhEmi) ? substr((string)$dps->dhEmi, 0, 10) : date('Y-m-d');

        // VALOR BRUTO CORRIGIDO: Busca em múltiplas tags possíveis
        $vServico = (float)($valores->vServ ?? $dps->valores->vServPrest->vServ ?? $valores->vBC ?? 0);
        $vLiquido = (float)($valores->vLiq ?? $vServico);

        if($vServico <= 0) throw new \Exception("Valor do serviço não localizado.");

        // LÓGICA DE ISS RETIDO
        $tpRetISSQN = (int)($dps->valores->trib->tribMun->tpRetISSQN ?? 2);
        $vIssRetido = ($tpRetISSQN === 1) ? (float)($valores->vISSQN ?? 0) : 0;

        // CONTINGÊNCIA DE IMPOSTOS FEDERAIS
        $vPis = (float)($valores->vPIS ?? 0);
        $vCofins = (float)($valores->vCOFINS ?? 0);
        $vIr = (float)($valores->vIR ?? 0);
        $vCsll = (float)($valores->vCSLL ?? 0);
        $diffFederal = round($vServico - $vLiquido - $vIssRetido, 2);

        if ($diffFederal > 0 && ($vPis + $vCofins + $vIr + $vCsll) == 0) {
            $vPis = round($vServico * 0.0065, 2);
            $vCofins = round($vServico * 0.03, 2);
            $vCsll = round($vServico * 0.01, 2);
            $vIr = round($vServico * 0.015, 2);
        }

        $retencoes = [
            'valor_iss' => $vIssRetido,
            'valor_pis' => $vPis,
            'valor_cofins' => $vCofins,
            'valor_ir' => $vIr,
            'valor_csll' => $vCsll,
            'valor_inss' => (float)($valores->vINSS ?? 0),
        ];

        $fornecedor = $this->obterFornecedor($inf->emit, (string)$inf->emit->CNPJ);
        $servBloco = $dps->serv->cServ ?? $inf->serv->cServ;
        $descCurta = substr((string)$servBloco->xDescServ, 0, 50); // Pega os primeiros 50 caracteres
        $servico = $this->obterServico((string)($servBloco->cTribNac ?? '140101'), (string)$servBloco->xDescServ, $vServico);

        $this->disableNextModelAudit();
        $compra = Compra::create([
            'fornecedor_id' => $fornecedor->id,
            'usuario_id' => $this->usuario_id,
            'nf' => (string)$inf->nNFSe,
            'data_emissao' => $data_emi,
            'valor' => $vServico,
            'veiculo_id' => $veiculo_id,
            'estado' => 'IMPORTADO',
            'xml_importado' => 1,
            'xml_path' => $nomeArquivo,
            'categoria_conta_id' => $categoria_id,
            'chave' => $chave,
            'empresa_id' => $this->empresa_id,
            'filial_id' => $this->filial_id,
            'observacao' => trim(str_replace(["\r", "\n"], ' ', (string)$servBloco->xDescServ)),
            'numero_emissao' => 0
        ]);

        ItemCompra::create([
            'compra_id' => $compra->id,
            'produto_id' => $servico->id,
            'quantidade' => 1,
            'valor_unitario' => $vServico,
            'unidade_compra' => 'UN',
            'cfop_entrada' => '1933'
        ]);

        $qtdParcelas = (int) $qtdParcelas > 0 ? (int) $qtdParcelas : 1;
        $valorParcela = round($vLiquido / $qtdParcelas, 2);
        $somaAcumulada = 0;

        for ($i = 1; $i <= $qtdParcelas; $i++) {
            $vencimento = date('Y-m-d', strtotime($data_emi . " + " . ($prazo * $i) . " days"));
            $valorFinal = ($i == $qtdParcelas) ? round($vLiquido - $somaAcumulada, 2) : $valorParcela;
            $somaAcumulada += $valorFinal;

            ContaPagar::create([
                'compra_id' => $compra->id,
                'fornecedor_id' => $fornecedor->id,
                'data_vencimento' => $vencimento,
                'data_emissao' => $data_emi,
                'valor_integral' => $valorFinal,
                'valor_original' => $valorFinal,
                'status' => false,
                // REFERÊNCIA ATUALIZADA COM DESCRIÇÃO
                'referencia' => "NFS-e " . $inf->nNFSe . " - " . $descCurta . " ($i/$qtdParcelas)",
                'categoria_id' => $categoria_id,
                'empresa_id' => $this->empresa_id,
                'filial_id' => $this->filial_id,
                'veiculo_id' => $veiculo_id,
                'numero_nota_fiscal' => (string)$inf->nNFSe,
                'usuario_id' => $this->usuario_id,
                ...($i == 1 ? $retencoes : [])
            ]);
        }
    }
    private function obterFornecedor($emit, $cnpj)
    {
        $cnpjLimpo = preg_replace('/[^0-9]/', '', $cnpj);
        $forn = Fornecedor::where('empresa_id', $this->empresa_id)->where('cpf_cnpj', $this->formataCnpj($cnpjLimpo))->first();
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
            $s = Produto::create([
                'nome' => trim(substr(str_replace(["\r", "\n"], ' ', $desc), 0, 100)), 'referencia' => $cod,
                'valor_compra' => $valor, 'valor_venda' => $valor, 'gerenciar_estoque' => 0, 'categoria_id' => Categoria::where('empresa_id', $this->empresa_id)->first()->id ?? 1,
                'empresa_id' => $this->empresa_id, 'locais' => '["-1"]', 'tipo_item' => '09', 'unidade_compra' => 'UN', 'unidade_venda' => 'UN', 'conversao_unitaria' => 1, 'percentual_lucro' => 0, 'estoque_minimo' => 0, 'inativo' => 0
            ]);
        }
        return $s;
    }

    public function visualizar($id)
    {
        $compra = Compra::findOrFail($id);
        $arquivo = preg_replace('/[^a-zA-Z0-9.]/', '', $compra->xml_path);
        if (!str_ends_with(strtolower($arquivo), '.xml')) $arquivo .= ".xml";

        $pastas = ['xml_servico', 'xml_entrada', 'xml_entrada_emetida'];

        foreach ($pastas as $pasta) {
            $caminho = public_path($pasta . DIRECTORY_SEPARATOR . $arquivo);

            if (file_exists($caminho)) {
                $xmlString = file_get_contents($caminho);
                $xmlClean = preg_replace('/ xmlns[^=]*="[^"]*"/i', '', $xmlString);
                $xml = simplexml_load_string($xmlClean);

                // 1. Mapeamento dos dados
                $nota = (object)[
                    'numero_nota' => (string)$xml->infNFSe->nNFSe,
                    'nsu' => 'N/A',
                    'data_emissao' => (string)$xml->infNFSe->DPS->infDPS->dhEmi,
                    'prestador_nome' => (string)$xml->infNFSe->emit->xNome,
                    'prestador_cnpj_cpf' => (string)$xml->infNFSe->emit->CNPJ,
                    'valor_servico' => (float)($xml->infNFSe->valores->vLiq ?? 0),
                    'observacao' => (string)$xml->infNFSe->DPS->infDPS->serv->cServ->xDescServ
                ];

                $tomador = [
                    'nome' => (string)$xml->infNFSe->DPS->infDPS->toma->xNome,
                    'cnpj_cpf' => (string)$xml->infNFSe->DPS->infDPS->toma->CNPJ,
                    'endereco' => (string)$xml->infNFSe->DPS->infDPS->toma->end->xLgr . ', ' .
                        (string)$xml->infNFSe->DPS->infDPS->toma->end->nro . ' - ' .
                        (string)$xml->infNFSe->DPS->infDPS->toma->end->xBairro
                ];

                $retencoes = [
                    'total_retido' => 0,
                    'iss_retido' => 0,
                    'pis' => 0,
                    'cofins' => 0,
                    'csll' => 0,
                    'irrf' => 0,
                    'liquido' => (float)($xml->infNFSe->valores->vLiq ?? 0)
                ];

                $enderecoPrestador = (string)$xml->infNFSe->emit->enderNac->xLgr . ', ' .
                    (string)$xml->infNFSe->emit->enderNac->nro . ' - ' .
                    (string)$xml->infNFSe->emit->enderNac->xBairro;

                $descricaoServico = (string)$xml->infNFSe->DPS->infDPS->serv->cServ->xDescServ;

                // 2. RETORNO PARA A VIEW (Aqui está o que faltava)
                return view('nfse.visualizar', [
                    'xml' => $xml,
                    'nota' => $nota,
                    'tomador' => $tomador,
                    'retencoes' => $retencoes,
                    'descricao_servico' => $descricaoServico,
                    'endereco_prestador' => $enderecoPrestador,
                    'title' => 'DANFSE - Nota ' . $nota->numero_nota
                ]);
            }
        }

        return "Arquivo XML não localizado.";
    }

    private function formataCnpj($c){ return strlen($c) == 14 ? substr($c,0,2).'.'.substr($c,2,3).'.'.substr($c,5,3).'/'.substr($c,8,4).'-'.substr($c,12,2) : $c; }
}
