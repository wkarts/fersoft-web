<?php

namespace App\Services\Ponto;

use App\Models\Funcionario;
use App\Models\PontoAfdArquivo;
use App\Models\PontoAfdRegistro;
use App\Models\PontoMarcacao;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class PontoAfdImportService
{
    public function __construct(private PontoAfdParserService $parser)
    {
    }

    public function importar(UploadedFile $arquivo, int $empresaId, ?int $usuarioId = null, ?int $pontoRelogioId = null): array
    {
        $hash = hash_file('sha256', $arquivo->getRealPath());

        $existente = PontoAfdArquivo::where('empresa_id', $empresaId)
            ->where('hash_arquivo', $hash)
            ->first();

        if ($existente) {
            return ['duplicado' => true, 'arquivo' => $existente];
        }

        return DB::transaction(function () use ($arquivo, $empresaId, $usuarioId, $pontoRelogioId, $hash) {
            $storedName = now()->format('YmdHis') . '_' . $hash . '.' . $arquivo->getClientOriginalExtension();
            $caminho = $arquivo->storeAs('ponto_afd/' . $empresaId, $storedName);

            $afd = PontoAfdArquivo::create([
                'empresa_id' => $empresaId,
                'ponto_relogio_id' => $pontoRelogioId,
                'nome_original' => $arquivo->getClientOriginalName(),
                'hash_arquivo' => $hash,
                'caminho_arquivo' => $caminho,
                'status' => 'processando',
                'usuario_id' => $usuarioId,
            ]);

            $linhas = file($arquivo->getRealPath(), FILE_IGNORE_NEW_LINES);

            $total = 0;
            $validas = 0;
            $invalidas = 0;

            foreach ($linhas as $index => $linha) {
                if (trim((string)$linha) === '') {
                    continue;
                }

                $total++;
                $parse = $this->parser->parseLine((string)$linha);
                $funcionarioId = $this->resolverFuncionarioId($empresaId, $parse);

                $registro = PontoAfdRegistro::create([
                    'empresa_id' => $empresaId,
                    'ponto_afd_arquivo_id' => $afd->id,
                    'funcionario_id' => $funcionarioId,
                    'numero_linha' => $index + 1,
                    'tipo_registro' => $parse['tipo_registro'],
                    'pis' => $parse['pis'],
                    'cpf' => $parse['cpf'],
                    'matricula' => $parse['matricula'],
                    'data_hora_marcacao' => $parse['data_hora_marcacao'],
                    'linha_bruta' => $parse['linha_bruta'],
                    'dados_parseados' => $parse['dados_parseados'],
                    'inconsistente' => $parse['inconsistente'],
                    'motivo_inconsistencia' => $parse['motivo_inconsistencia'],
                ]);

                if ($parse['inconsistente']) {
                    $invalidas++;
                    continue;
                }

                $validas++;

                if ($funcionarioId) {
                    PontoMarcacao::firstOrCreate([
                        'empresa_id' => $empresaId,
                        'funcionario_id' => $funcionarioId,
                        'ponto_afd_registro_id' => $registro->id,
                        'data_hora_marcacao' => $parse['data_hora_marcacao'],
                        'origem' => 'afd',
                    ], [
                        'status' => 'bruta',
                        'dados_brutos' => $parse['dados_parseados'],
                        'inconsistente' => false,
                    ]);
                }
            }

            $afd->update([
                'total_linhas' => $total,
                'linhas_validas' => $validas,
                'linhas_invalidas' => $invalidas,
                'status' => 'concluido',
                'processado_em' => now(),
            ]);

            return ['duplicado' => false, 'arquivo' => $afd];
        });
    }

    private function resolverFuncionarioId(int $empresaId, array $parse): ?int
    {
        $query = Funcionario::where('empresa_id', $empresaId);

        if (!empty($parse['pis'])) {
            $func = (clone $query)->where('pis', $parse['pis'])->first();
            if ($func) return $func->id;
        }

        if (!empty($parse['cpf'])) {
            $cpf = preg_replace('/\D/', '', (string)$parse['cpf']);
            $func = (clone $query)->whereRaw("REPLACE(REPLACE(REPLACE(cpf,'.',''),'-',''),' ','') = ?", [$cpf])->first();
            if ($func) return $func->id;
        }

        if (!empty($parse['matricula'])) {
            $func = (clone $query)->where('matricula', $parse['matricula'])->first();
            if ($func) return $func->id;
        }

        return null;
    }
}
