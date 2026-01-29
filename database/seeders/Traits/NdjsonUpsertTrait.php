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
            if (!array_key_exists('ELOQUENT_UUID', $row) || empty($row['ELOQUENT_UUID'])) {
                $row['ELOQUENT_UUID'] = (string) Str::uuid();
            }
            if (!array_key_exists('CREATED_AT', $row) || empty($row['CREATED_AT'])) {
                $row['CREATED_AT'] = $now;
            }
            if (!array_key_exists('UPDATED_AT', $row) || empty($row['UPDATED_AT'])) {
                $row['UPDATED_AT'] = $now;
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
