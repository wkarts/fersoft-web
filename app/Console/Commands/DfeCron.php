<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ConfigNota;
use App\Models\Empresa;
use App\Models\Certificado;
use App\Models\ManifestaDfe;
use App\Models\BuscaDocumentoLog;
use App\Services\DFeService;

class DfeCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dfe:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Busca de documentos';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $configs = ConfigNota::where('busca_documento_automatico', 1)->get();
        foreach($configs as $config){
            $certificado = Certificado::where('empresa_id', $config->empresa_id)->first();
            if($certificado != null){
                $cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

                $dfe_service = new DFeService([
                    "atualizacao" => date('Y-m-d h:i:s'),
                    "tpAmb" => 1,
                    "razaosocial" => $config->razao_social,
                    "siglaUF" => $config->UF,
                    "cnpj" => $cnpj,
                    "schemes" => "PL_009_V4",
                    "versao" => "4.00",
                    "tokenIBPT" => "AAAAAAA",
                    "CSC" => $config->csc,
                    "CSCid" => $config->csc_id,
                    "is_filial" => null
                ], 55, $config->empresa_id);

                $manifesto = ManifestaDfe::
                where('empresa_id', $config->empresa_id)
                ->orderBy('nsu', 'desc')->first();

                if($manifesto == null) $nsu = 0;
                else $nsu = $manifesto->nsu;
                $docs = $dfe_service->novaConsulta($nsu, $config->empresa_id);

                if(!isset($docs['erro'])){
                    $novos = 0;
                    foreach($docs as $d) {
                        if($this->validaNaoInserido($d['chave'], $config->empresa_id)){
                            if($d['valor'] > 0 && $d['nome']){
                                ManifestaDfe::create($d);
                                $novos++;
                            }
                        }
                    }
                    $resultado = "Busca realizada com sucesso, foram encontrados $novos documentos";
                    BuscaDocumentoLog::create([
                        'empresa_id' => $config->empresa_id,
                        'resultado' => $resultado,
                        'sucesso' => 1
                    ]);
                }else{
                    BuscaDocumentoLog::create([
                        'empresa_id' => $config->empresa_id,
                        'resultado' => "algo deu errado: " . $docs['message'] . " - ultnsu : $nsu",
                        'sucesso' => 0
                    ]);
                }
            }
        }
    }

    public function validaNaoInserido($chave, $empresa_id){
        $m = ManifestaDfe::
        where('empresa_id', $empresa_id)
        ->where('chave', $chave)->first();
        if($m == null) return true;
        else return false;
    }
}
