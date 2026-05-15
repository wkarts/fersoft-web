<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ConfigNota;
use App\Models\Certificado;
use App\Models\ManifestaDfe;
use App\Models\BuscaDocumentoLog;
use App\Models\Filial;
use App\Services\DFeService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DfeCron extends Command
{
    protected $signature = 'dfe:cron';
    protected $description = 'Busca de documentos DFe na SEFAZ para Matriz e Filiais';

    public function handle()
    {
        // 1. BUSCA NAS MATRIZES
        $matrizes = ConfigNota::where('busca_documento_automatico', 1)->get();
        foreach($matrizes as $m){
            $this->executarRobo($m, null, "Matriz");
        }

        // 2. BUSCA NAS FILIAIS (Apenas as que têm busca automática ligada)
        $filiais = Filial::where('busca_documento_automatico', 1)->get();
        foreach($filiais as $f){
            $this->executarRobo($f, $f->id, "Filial: " . $f->descricao);
        }
    }

    private function executarRobo($config, $filial_id, $identificador)
    {
        try {
            // TRAVA DE 65 MINUTOS (Agora separada por Unidade/Filial)
            $ultimaBusca = BuscaDocumentoLog::where('empresa_id', $config->empresa_id)
                ->where('filial_id', $filial_id)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($ultimaBusca) {
                // Tática Anti-Fuso Horário (Idêntica ao Raio-X)
                $dataBanco = strtotime($ultimaBusca->created_at);
                $dataAgora = strtotime(date('Y-m-d H:i:s'));
                $minutos = round(abs($dataAgora - $dataBanco) / 60, 2);
                
                if ($minutos < 65) return; 
            }

            $certificado = Certificado::where('empresa_id', $config->empresa_id)->first();
            if($certificado == null) throw new \Exception("O robô parou pois o Certificado Digital não foi encontrado para: " . $identificador);

            $cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);
            $dfe_service = new DFeService([
                "atualizacao" => date('Y-m-d h:i:s'),
                "tpAmb" => 1,
                "razaosocial" => $config->razao_social,
                "siglaUF" => $config->UF,
                "cnpj" => $cnpj,
                "schemes" => config('fiscal.default_schemes'),
                "versao" => "4.00",
                "tokenIBPT" => "AAAAAAA",
                "CSC" => $config->csc,
                "CSCid" => $config->csc_id,
                "is_filial" => $filial_id
            ], 55, $config->empresa_id);

            // Busca o NSU específico desta unidade (Matriz ou Filial)
            $manifesto = ManifestaDfe::where('empresa_id', $config->empresa_id)
                ->where('filial_id', $filial_id)
                ->orderBy('nsu', 'desc')
                ->first();

            $nsu = $manifesto == null ? 0 : $manifesto->nsu;
            
            $docs = $dfe_service->novaConsulta($nsu, $config->empresa_id);

            if(!isset($docs['erro']) && is_array($docs)){
                $novos = 0;
                foreach($docs as $d) {
                    if($this->validaNaoInserido($d['chave'], $config->empresa_id)){
                        if(isset($d['valor']) && $d['valor'] > 0 && isset($d['nome'])){
                            
                            $d['nNF'] = (int) substr($d['chave'], 25, 9);
                            $d['filial_id'] = $filial_id; // Amarra a filial na nota encontrada

                            // Verificação Cruzada Inteligente (Chave vs Compra)
                            $compraExistente = \App\Models\Compra::where('chave', $d['chave'])->where('empresa_id', $config->empresa_id)->first();
                            if ($compraExistente) {
                                $d['compra_id'] = $compraExistente->id; 
                                if (\App\Models\ContaPagar::where('compra_id', $compraExistente->id)->exists()) {
                                    $d['fatura_salva'] = 1; 
                                }
                            }
                            
                            $novoManifesto = ManifestaDfe::create($d);
                            $novos++;

                            // Manifestação Automática (Ciência da Operação)
                            try { $dfe_service->manifesta($d['chave'], 1); } catch (\Exception $e) {}
                        }
                    }
                }
                
                BuscaDocumentoLog::create([
                    'empresa_id' => $config->empresa_id,
                    'filial_id' => $filial_id,
                    'resultado' => "($identificador) Sucesso: $novos documentos encontrados",
                    'sucesso' => 1
                ]);
            } else {
                BuscaDocumentoLog::create([
                    'empresa_id' => $config->empresa_id,
                    'filial_id' => $filial_id,
                    'resultado' => "($identificador) Erro: " . ($docs['message'] ?? 'Erro SEFAZ'),
                    'sucesso' => 0
                ]);
            }
        } catch (\Exception $e) {
            throw new \Exception("Erro oculto no Robô ($identificador): " . $e->getMessage() . " | Arquivo: " . $e->getFile() . " | Linha: " . $e->getLine());
        }
    }

    public function validaNaoInserido($chave, $empresa_id){
        return !ManifestaDfe::where('empresa_id', $empresa_id)->where('chave', $chave)->exists();
    }
}