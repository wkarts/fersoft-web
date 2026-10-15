<?php

declare(strict_types=1);

namespace App\Services\DatabaseHealth;

use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Produz uma fotografia somente de metadados do banco conectado.
 * Não conta linhas diretamente nem lê conteúdo de tabelas.
 */
final class DatabaseHealthOverview
{
    public function snapshot(): array
    {
        $checkedAt = now();

        try {
            $connection = DB::connection();
            $driver = $connection->getDriverName();

            if ($driver !== 'mysql') {
                return $this->unavailable(
                    $driver,
                    $checkedAt,
                    'O painel online consulta metadados de MySQL/MariaDB. Para outros formatos, use o analisador offline.'
                );
            }

            $records = $connection->select(
                'SELECT TABLE_NAME AS table_name,
                        COALESCE(TABLE_ROWS, 0) AS estimated_rows,
                        COALESCE(DATA_LENGTH, 0) AS data_bytes,
                        COALESCE(INDEX_LENGTH, 0) AS index_bytes,
                        ENGINE AS engine,
                        UPDATE_TIME AS updated_at
                   FROM information_schema.TABLES
                  WHERE TABLE_SCHEMA = ?
                    AND TABLE_TYPE = ?
                  ORDER BY (COALESCE(DATA_LENGTH, 0) + COALESCE(INDEX_LENGTH, 0)) DESC',
                [$connection->getDatabaseName(), 'BASE TABLE']
            );
        } catch (Throwable $exception) {
            return $this->unavailable(
                config('database.default', 'desconhecido'),
                $checkedAt,
                'Não foi possível consultar os metadados do banco nesta requisição.'
            );
        }

        $tables = array_map(static function ($record): array {
            $dataBytes = max(0, (int) ($record->data_bytes ?? 0));
            $indexBytes = max(0, (int) ($record->index_bytes ?? 0));

            return [
                'name' => (string) ($record->table_name ?? ''),
                'estimated_rows' => max(0, (int) ($record->estimated_rows ?? 0)),
                'data_bytes' => $dataBytes,
                'index_bytes' => $indexBytes,
                'allocated_bytes' => $dataBytes + $indexBytes,
                'engine' => (string) ($record->engine ?? '—'),
                'updated_at' => $record->updated_at ? (string) $record->updated_at : null,
            ];
        }, $records);

        $totals = [
            'table_count' => count($tables),
            'estimated_rows' => 0,
            'data_bytes' => 0,
            'index_bytes' => 0,
            'allocated_bytes' => 0,
        ];

        foreach ($tables as $table) {
            $totals['estimated_rows'] += $table['estimated_rows'];
            $totals['data_bytes'] += $table['data_bytes'];
            $totals['index_bytes'] += $table['index_bytes'];
            $totals['allocated_bytes'] += $table['allocated_bytes'];
        }

        return [
            'available' => true,
            'driver' => $driver,
            'checked_at' => $checkedAt,
            'message' => null,
            'totals' => $totals,
            'tables' => array_slice($tables, 0, 25),
        ];
    }

    private function unavailable(string $driver, $checkedAt, string $message): array
    {
        return [
            'available' => false,
            'driver' => $driver,
            'checked_at' => $checkedAt,
            'message' => $message,
            'totals' => [
                'table_count' => 0,
                'estimated_rows' => 0,
                'data_bytes' => 0,
                'index_bytes' => 0,
                'allocated_bytes' => 0,
            ],
            'tables' => [],
        ];
    }
}
