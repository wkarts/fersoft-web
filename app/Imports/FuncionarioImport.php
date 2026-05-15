<?php

namespace App\Imports;

use App\Models\Funcionario;
use App\Models\Funcao;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;

class FuncionarioImport implements ToCollection, WithHeadingRow
{
    protected $empresa_id;
    protected $filial_id;

    public function __construct($empresa_id, $filial_id)
    {
        $this->empresa_id = $empresa_id;
        // Se for Matriz, salva como null no banco
        $this->filial_id = ($filial_id == 'NULL' || !$filial_id) ? null : $filial_id;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $nome = $row['nome'] ?? null;
            if (!$nome || empty(trim($nome))) continue;

            // 1. Verificação de Duplicidade pelo CPF
            $cpf = isset($row['cpf']) ? trim($row['cpf']) : '000.000.000-00';
            if ($cpf !== '000.000.000-00') {
                $existeFuncionario = Funcionario::where('empresa_id', $this->empresa_id)
                                                ->where('cpf', $cpf)
                                                ->exists();
                if ($existeFuncionario) continue; 
            }

            // --- LÓGICA DE FUNÇÃO ---
            $funcaoId = null;
            $nomeFuncao = $this->removerAcentos(strtoupper(trim($row['funcao'] ?? '')));

            if (!empty($nomeFuncao)) {
                // Busca a função antes de tentar criar para evitar erro 1062
                $queryFuncao = Funcao::where('nome', $nomeFuncao)
                                     ->where('empresa_id', $this->empresa_id);

                if ($this->filial_id === null) {
                    $queryFuncao->whereNull('filial_id');
                } else {
                    $queryFuncao->where('filial_id', $this->filial_id);
                }

                $f_existente = $queryFuncao->first();

                if ($f_existente) {
                    $funcaoId = $f_existente->id;
                } else {
                    try {
                        $novaFuncao = Funcao::create([
                            'nome'       => $nomeFuncao,
                            'empresa_id' => $this->empresa_id,
                            'filial_id'  => $this->filial_id,
                            'usuario_id' => session('user_logged')['id'] ?? null
                        ]);
                        $funcaoId = $novaFuncao->id;
                    } catch (\Exception $e) {
                        // Resgate caso o banco aponte duplicidade "invisível"
                        $f_recuperada = Funcao::where('nome', $nomeFuncao)
                                             ->where('empresa_id', $this->empresa_id);
                        
                        if ($this->filial_id === null) $f_recuperada->whereNull('filial_id');
                        else $f_recuperada->where('filial_id', $this->filial_id);
                        
                        $res = $f_recuperada->first();
                        $funcaoId = $res ? $res->id : null;
                    }
                }
            }

            $contato = $this->tratarTelefone($row['telefone'] ?? '');

            // --- GRAVAÇÃO DO FUNCIONÁRIO ---
            $funcionario = new Funcionario();
            $funcionario->empresa_id = $this->empresa_id;
            $funcionario->filial_id  = $this->filial_id;
            $funcionario->usuario_id = null; 
            
            $funcionario->nome = strtoupper(trim($nome));
            $funcionario->cpf  = $cpf;
            $funcionario->rg   = $row['rg'] ?? '0';
            $funcionario->numero_registro = $row['cod_func'] ?? null;
            $funcionario->funcao_id = $funcaoId;
            
            $funcionario->rua    = $row['endereco'] ?? 'NÃO INFORMADO';
            $funcionario->numero = $row['numero'] ?? 'S/N';
            $funcionario->bairro = $row['bairro'] ?? 'NÃO INFORMADO';
            
            $funcionario->telefone = $contato;
            $funcionario->celular  = $contato;
            $funcionario->email    = $row['email'] ?? '';
            $funcionario->salario  = $row['salario'] ?? 0;
            
            $funcionario->data_nascimento = $this->parseData($row['data_nascimento'] ?? null);
            $funcionario->data_admissao   = $this->parseData($row['data_admissao'] ?? null);
            
            $dataReg = $this->parseData($row['data_registro'] ?? null);
            $funcionario->data_registro = $dataReg ? $dataReg : date('Y-m-d');

            $funcionario->unidade_tipo = ($this->filial_id) ? 2 : 1; 
            $funcionario->unidade      = ($this->filial_id) ? 'Filial' : 'Matriz';
            $funcionario->status_funcionario = 'Ativo';
            $funcionario->status_motorista   = 'Ativo';

            $funcionario->save();
        }
    }

    private function removerAcentos($string) {
        return preg_replace([
            "/(á|à|â|ã|ä)/", "/(Á|À|Â|Ã|Ä)/", "/(é|è|ê|ë)/", "/(É|È|È|Ë)/", 
            "/(í|ì|î|ï)/", "/(Í|Ì|Î|Ï)/", "/(ó|ò|ô|õ|ö)/", "/(Ó|Ò|Ô|Õ|Ö)/", 
            "/(ú|ù|û|ü)/", "/(Ú|Ù|Û|Ü)/", "/(ñ)/", "/(Ñ)/", "/(ç)/", "/(Ç)/"
        ], explode(" ","a A e E i I o O u U n N c C"), $string);
    }

    private function tratarTelefone($n) {
        $n = preg_replace('/\D/', '', trim($n));
        if (empty($n)) return '7199999999';
        return (strlen($n) <= 9) ? '71' . $n : $n;
    }

    private function parseData($v) {
        if (!$v) return null;
        try {
            if (is_numeric($v)) return Date::excelToDateTimeObject($v)->format('Y-m-d');
            return Carbon::parse($v)->format('Y-m-d');
        } catch (\Exception $e) { return null; }
    }
}