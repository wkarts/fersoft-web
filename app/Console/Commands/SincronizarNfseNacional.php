<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\NFSeNacionalService;
use App\Models\Empresa;
use App\Models\Filial;
use App\Models\ConfigNota;
use Exception;
use Illuminate\Support\Facades\Log;

class SincronizarNfseNacional extends Command
{
    // O comando que você usará no terminal ou agendador
    protected $signature = 'nfse:sincronizar-automatica';

    protected $description = 'Sincroniza automaticamente as NFS-e Tomadas da Receita para Matriz e Filiais';

    public function handle()
    {
        $this->info('Iniciando sincronização automática de NFS-e Tomadas...');

        // 1. Busca todas as empresas ativas no sistema que possuem configurações de nota
        // Ajuste o select ou regras conforme o seu banco de dados
        $configuracoesMatriz = DB::table('config_notas')->get();

        foreach ($configuracoesMatriz as $config) {
            $empresaId = $config->empresa_id;
            
            // --- PROCESSA A MATRIZ ---
            $this->info("Processando MATRIZ da Empresa ID: {$empresaId}");
            
            $certificadoMatriz = DB::table('certificados')
                ->where('empresa_id', $empresaId)
                ->first();

            if ($certificadoMatriz && $config->cnpj) {
                $this->sincronizarUnidade(
                    $config->cnpj, 
                    $certificadoMatriz->arquivo, 
                    $certificadoMatriz->senha, 
                    $empresaId, 
                    null // Matriz não tem filial_id
                );
            }

            // --- PROCESSA AS FILIAIS ---
            $filiais = Filial::where('empresa_id', $empresaId)->get();
            
            foreach ($filiais as $filial) {
                $this->info("Processando FILIAL: {$filial->descricao} (ID: {$filial->id})");
                
                // Verifica se a filial tem certificado e CNPJ próprios cadastrados
                if ($filial->cnpj && $filial->certificado_arquivo) {
                    $this->sincronizarUnidade(
                        $filial->cnpj, 
                        $filial->certificado_arquivo, 
                        $filial->certificado_senha, 
                        $empresaId, 
                        $filial->id
                    );
                }
            }
        }

        $this->info('Sincronização automática finalizada.');
        return Command::SUCCESS;
    }

    /**
     * Lógica isolada de sincronização de cada CNPJ (Copiada/adaptada do seu Controller)
     */
    private function sincronizarUnidade($cnpj, $certBlob, $senha, $empresaId, $filialId = null)
    {
        try {
            $servico = new NFSeNacionalService($cnpj, $certBlob, $senha);

            // Busca o último NSU gravado para esta unidade específica
            $nsuAtual = DB::table('manifesta_nfse_tomadas')
                ->where('empresa_id', $empresaId)
                ->where('filial_id', $filialId)
                ->max('nsu') ?? 0;

            $consultasRealizadas = 0;
            $limiteConsultas = 15; // Limite menor para não estourar o limite da Receita em rotinas automáticas

            while ($consultasRealizadas < $limiteConsultas) {
                $consultasRealizadas++;
                
                // Faz a busca na Receita
                $resultado = $servico->consultar($nsuAtual);

                if (!$resultado || empty($resultado['LoteDFe'])) {
                    break;
                }

                $notasNesseLote = 0;

                foreach ($resultado['LoteDFe'] as $doc) {
                    $nsuNota = $doc['NSU'];
                    
                    // Se o NSU já estiver salvo, pula para o próximo
                    $existe = DB::table('manifesta_nfse_tomadas')
                        ->where('empresa_id', $empresaId)
                        ->where('filial_id', $filialId)
                        ->where('nsu', $nsuNota)
                        ->exists();

                    if ($existe) {
                        $nsuAtual = $nsuNota;
                        continue;
                    }

                    // --- DECODE DO XML ---
                    $xmlGzip = base64_decode($doc['ArquivoXml']);
                    $xmlString = gzdecode($xmlGzip);
                    $xml = simplexml_load_string($xmlString);

                    if ($xml) {
                        // Extração dos dados base do XML (Igual ao seu Controller)
                        $chave = (string)($xml->infNFSe->chNFSe ?? $xml->chNFSe ?? '');
                        $numeroNota = (string)($xml->infNFSe->numero ?? $xml->numero ?? '');
                        $dataEmissao = isset($xml->infNFSe->dhEmit) ? date('Y-m-d H:i:s', strtotime($xml->infNFSe->dhEmit)) : null;
                        
                        $prestadorNome = (string)($xml->infNFSe->prest->xNome ?? '');
                        $prestadorCnpj = (string)($xml->infNFSe->prest->CNPJ ?? $xml->infNFSe->prest->CPF ?? '');
                        $valorServico = (float)($xml->infNFSe->valores->vServ ?? 0);

                        // Salva no banco de dados
                        DB::table('manifesta_nfse_tomadas')->insert([
                            'empresa_id' => $empresaId,
                            'filial_id' => $filialId,
                            'nsu' => $nsuNota,
                            'chave' => $chave,
                            'numero_nota' => $numeroNota,
                            'data_emissao' => $dataEmissao,
                            'prestador_nome' => $prestadorNome,
                            'prestador_cnpj_cpf' => $prestadorCnpj,
                            'valor_servico' => $valorServico,
                            'xml' => $xmlString,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $notasNesseLote++;
                    }

                    $nsuAtual = $nsuNota;
                }

                // Se o lote veio incompleto ou vazio, significa que chegou no fim da fila da Receita
                if ($notasNesseLote == 0) {
                    break;
                }

                // Pausa de 1.5 segundos para evitar o erro 503/429 de consumo indevido
                usleep(1500000); 
            }

        } catch (Exception $e) {
            Log::error("Erro na sincronização automática do CNPJ {$cnpj}: " . $e->getMessage());
        }
    }
}