<?php

namespace App\Console\Commands;

use App\Services\Ponto\PontoAfdImportService;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;

class PontoImportarAfdCommand extends Command
{
    protected $signature = 'ponto:importar-afd {empresa_id : ID da empresa} {arquivo : Caminho do arquivo AFD} {--usuario_id=} {--ponto_relogio_id=}';

    protected $description = 'Importa um arquivo AFD manualmente via linha de comando';

    public function handle(PontoAfdImportService $service): int
    {
        $empresaId = (int) $this->argument('empresa_id');
        $arquivoPath = (string) $this->argument('arquivo');

        if (!file_exists($arquivoPath) || !is_readable($arquivoPath)) {
            $this->error('Arquivo inválido ou sem permissão de leitura: ' . $arquivoPath);
            return self::FAILURE;
        }

        $uploaded = new UploadedFile(
            $arquivoPath,
            basename($arquivoPath),
            mime_content_type($arquivoPath) ?: 'text/plain',
            null,
            true
        );

        $result = $service->importar(
            $uploaded,
            $empresaId,
            $this->option('usuario_id') ? (int) $this->option('usuario_id') : null,
            $this->option('ponto_relogio_id') ? (int) $this->option('ponto_relogio_id') : null
        );

        if ($result['duplicado']) {
            $this->warn('Arquivo já importado para a empresa. Run-ID: ' . ($result['run_id'] ?? '-'));
            return self::SUCCESS;
        }

        $this->info('Importação concluída com sucesso. Arquivo ID: ' . $result['arquivo']->id . ' | Run-ID: ' . ($result['run_id'] ?? '-'));
        return self::SUCCESS;
    }
}
