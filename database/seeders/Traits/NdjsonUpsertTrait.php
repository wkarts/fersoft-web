<?php

namespace Database\Seeders\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

trait NdjsonUpsertTrait
{
    /**
     * Lê um arquivo NDJSON (1 JSON por linha) e executa UPSERT em lotes.
     *
     * @param  string  $table          Nome da tabela
     * @param  array   $uniqueBy       Colunas chave (unique index) para UPSERT
     * @param  array   $updateColumns  Colunas a atualizar em conflito
     * @param  string  $relativePath   Caminho relativo a database/seeders/data/
     * @param  int     $chunkSize      Tamanho do lote
     */
    protected function upsertFromNdjson(string $table, array $uniqueBy, array $updateColumns, string $relativePath, int $chunkSize = 500): void
    {
        $path = database_path('seeders/data/' . ltrim($relativePath, '/'));
        if (!is_file($path)) {
            throw new \RuntimeException("Arquivo NDJSON não encontrado: {$path}");
        }

        $file = new \SplFileObject($path, 'r');
        $batch = [];
        $now = Carbon::now();

        while (!$file->eof()) {
            $line = trim((string) $file->fgets());
            if ($line === '') {
                continue;
            }

            $row = json_decode($line, true, 512, JSON_THROW_ON_ERROR);

            // Padrões "Eloquent" custom (colunas maiúsculas)
            if (!array_key_exists('eloquent_uuid', $row) || empty($row[ 'eloquent_uuid'])) {
                $row[ 'eloquent_uuid'] = (string) Str::uuid();
            }
            if (!array_key_exists('created_at', $row) || empty($row[ 'created_at'])) {
                $row[ 'created_at'] = $now;
            }
            if (!array_key_exists('updated_at', $row) || empty($row[ 'updated_at'])) {
                $row[ 'updated_at'] = $now;
            }

            $batch[] = $row;

            if (count($batch) >= $chunkSize) {
                DB::table($table)->upsert($batch, $uniqueBy, $updateColumns);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table($table)->upsert($batch, $uniqueBy, $updateColumns);
        }
    }
}
