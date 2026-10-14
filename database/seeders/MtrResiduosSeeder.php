<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Catálogo de resíduos MTR do arquivo MTR_RESIDUOS.ndjson.
 *
 * Identidade funcional: res_codigo (índice UNIQUE da migration existente).
 * Não importa PKs da origem nem modifica vínculos empresa/filial/usuário
 * de registros preexistentes. Os itens novos são referências globais.
 */
class MtrResiduosSeeder extends Seeder
{
    private const TABLE = 'mtr_residuos';

    private const DATA_FILE = 'seeders/data/MTR_RESIDUOS.ndjson';

    private const CATALOG_FIELDS = [
        'res_codigo_ibama',
        'res_descricao',
        'grr_descricao',
        'grr_representacao',
    ];

    public function run(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            throw new RuntimeException('A migration de mtr_residuos precisa ser executada antes do seeder.');
        }

        $data = $this->readCatalog(); // Valida todo o arquivo antes de escrever.

        // Uma transação para não deixar importações parciais. É seguro reexecutar.
        DB::transaction(function () use ($data): void {
            foreach (array_chunk($data, 100, true) as $chunk) {
                $codes = array_keys($chunk);
                $existing = DB::table(self::TABLE)
                    ->whereIn('res_codigo', $codes)
                    ->get(array_merge(['res_codigo'], self::CATALOG_FIELDS))
                    ->keyBy('res_codigo');

                $inserts = [];
                $now = now()->toDateTimeString();

                foreach ($chunk as $code => $item) {
                    $saved = $existing->get($code);

                    if ($saved !== null) {
                        $changes = [];

                        foreach (self::CATALOG_FIELDS as $field) {
                            // Não mexer em timestamps nem gerar UPDATE sem mudança.
                            if ($saved->{$field} !== $item[$field]) {
                                $changes[$field] = $item[$field];
                            }
                        }

                        if ($changes !== []) {
                            $changes['updated_at'] = $now;
                            DB::table(self::TABLE)
                                ->where('res_codigo', $code)
                                ->update($changes);
                        }

                        continue;
                    }

                    $inserts[] = $item + [
                        'empresa_id' => null,
                        'filial_id' => null,
                        'usuario_id' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($inserts !== []) {
                    // A UNIQUE res_codigo elimina a condição de corrida de
                    // duas instalações executando o mesmo catálogo.
                    DB::table(self::TABLE)->upsert(
                        $inserts,
                        ['res_codigo'],
                        self::CATALOG_FIELDS
                    );
                }
            }
        }, 3);
    }

    /** @return array<int, array<string, int|string|null>> */
    private function readCatalog(): array
    {
        $path = database_path(self::DATA_FILE);

        if (!is_readable($path)) {
            throw new RuntimeException('Dados do catálogo MTR não encontrados: ' . $path);
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Não foi possível abrir os dados do catálogo MTR.');
        }

        $items = [];
        $lineNumber = 0;

        try {
            while (($line = fgets($handle)) !== false) {
                $lineNumber++;
                if (trim($line) === '') {
                    continue;
                }

                $item = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($item)) {
                    throw new RuntimeException("Registro inválido no catálogo MTR, linha {$lineNumber}.");
                }

                $expectedKeys = array_merge(['res_codigo'], self::CATALOG_FIELDS);
                if (count($item) !== count($expectedKeys)
                    || array_diff($expectedKeys, array_keys($item)) !== []) {
                    throw new RuntimeException("Campos inesperados no catálogo MTR, linha {$lineNumber}.");
                }

                $code = $item['res_codigo'];
                if (!is_int($code) || $code <= 0) {
                    throw new RuntimeException("res_codigo inválido no catálogo MTR, linha {$lineNumber}.");
                }

                if (array_key_exists($code, $items)) {
                    throw new RuntimeException("res_codigo duplicado no arquivo de origem: {$code}.");
                }

                foreach (['res_codigo_ibama', 'res_descricao'] as $required) {
                    if (!is_string($item[$required]) || trim($item[$required]) === '') {
                        throw new RuntimeException("{$required} inválido no catálogo MTR, linha {$lineNumber}.");
                    }
                }

                foreach (['grr_descricao', 'grr_representacao'] as $optional) {
                    if ($item[$optional] !== null && !is_string($item[$optional])) {
                        throw new RuntimeException("{$optional} inválido no catálogo MTR, linha {$lineNumber}.");
                    }
                }

                if (mb_strlen($item['res_codigo_ibama']) > 50
                    || ($item['grr_representacao'] !== null && mb_strlen($item['grr_representacao']) > 20)) {
                    throw new RuntimeException("Campo excede o tamanho do schema MTR, linha {$lineNumber}.");
                }

                $items[$code] = $item;
            }
        } finally {
            fclose($handle);
        }

        if (count($items) !== 895) {
            throw new RuntimeException(
                sprintf('Catálogo MTR incompleto: encontrados %d registros; esperados 895.', count($items))
            );
        }

        return $items;
    }
}