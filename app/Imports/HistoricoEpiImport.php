<?php

namespace App\Imports;

use App\Models\Funcionario;
use App\Models\Produto;
use App\Models\Requisicao;
use App\Models\RequisicaoItem;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Shared\Date; // Importante para a data

class HistoricoEpiImport implements WithMultipleSheets
{
    protected $empresa_id;

    public function __construct($empresa_id)
    {
        $this->empresa_id = (int)$empresa_id;
    }

    public function sheets(): array
    {
        return [
            3 => new FilialMappingImport(),
            0 => new ProdutosReferenciaImport(), 
            1 => new LancamentosImport($this->empresa_id),
        ];
    }
}

class FilialMappingImport implements ToCollection, WithHeadingRow {
    public static $mapaFiliais = [];
    public function collection(Collection $rows) {
        foreach ($rows as $row) {
            $codigo = trim((string)($row['codigo'] ?? $row['Código'] ?? ''));
            $filialId = $row['filial'] ?? $row['FILIAL'] ?? null;
            if ($codigo !== '') {
                self::$mapaFiliais[$codigo] = $filialId;
            }
        }
    }
}

class ProdutosReferenciaImport implements ToCollection, WithHeadingRow {
    public static $dadosProdutos = [];
    public function collection(Collection $rows) {
        foreach ($rows as $row) {
            $ref = trim((string)($row['id'] ?? $row['ID'] ?? ''));
            if ($ref !== '') {
                self::$dadosProdutos[$ref] = [
                    'ca'  => $row['detalhes'] ?? $row['Detalhes'] ?? null,
                    'fab' => $row['embalagem'] ?? $row['Embalagem'] ?? null
                ];
            }
        }
    }
}

class LancamentosImport implements ToCollection, WithHeadingRow
{
    protected $empresa_id;
    protected $mapaFuncionarios = [];

    public function __construct($empresa_id)
    {
        $this->empresa_id = $empresa_id;
        $funcionarios = Funcionario::where('empresa_id', $this->empresa_id)->get();
        foreach ($funcionarios as $f) {
            $this->mapaFuncionarios[$this->limpezaTotal($f->nome)] = $f;
        }
    }

    public function collection(Collection $rows)
    {
        $usuarioId = session('user_logged')['id'] ?? 2;
        $requisicoesAgrupadas = $rows->groupBy('requisicao');

        foreach ($requisicoesAgrupadas as $codigoReqAntiga => $itens) {
            $primeiroItem = $itens->first();
            $nomePlanilha = $primeiroItem['descricao'] ?? $primeiroItem['descrição'] ?? null;

            if (!$nomePlanilha) continue;

            $funcionario = $this->mapaFuncionarios[$this->limpezaTotal($nomePlanilha)] ?? null;
            if (!$funcionario) continue;

            // Busca Filial (Tratando como String para o Match)
            $ccPlanilha = trim((string)($primeiroItem['cc'] ?? $primeiroItem['CC'] ?? ''));
            $idMapeado = FilialMappingImport::$mapaFiliais[$ccPlanilha] ?? null;
            $filialId = ($idMapeado && strtoupper((string)$idMapeado) !== 'NULL') ? (int)$idMapeado : ($funcionario->filial_id ?? 1);

            // Criar Cabeçalho
            $requisicao = Requisicao::create([
                'empresa_id'      => $this->empresa_id,
                'filial_id'       => $filialId,
                'usuario_id'      => $usuarioId,
                'funcionario_id'  => $funcionario->id,
                'data_requisicao' => $this->converterDataExcel($primeiroItem['data']),
                'observacao'      => "Migração Histórica - Req: " . $codigoReqAntiga,
                'status'          => 'Finalizado'
            ]);

            foreach ($itens as $item) {
                $ref = trim((string)($item['produto'] ?? $item['Produto'] ?? ''));
                if ($ref) {
                    $produto = Produto::where('empresa_id', $this->empresa_id)
                        ->where('referencia', $ref)
                        ->first();

                    if ($produto) {
                        $infoExtra = ProdutosReferenciaImport::$dadosProdutos[$ref] ?? null;

                        RequisicaoItem::create([
                            'empresa_id'    => $this->empresa_id,
                            'filial_id'     => $filialId,
                            'usuario_id'    => $usuarioId,
                            'requisicao_id' => $requisicao->id,
                            'produto_id'    => $produto->id,
                            'quantidade'    => $item['quantidade'] ?? 0,
                            'ca_snapshot'   => $infoExtra['ca'] ?? null,
                            'fab_snapshot'  => $infoExtra['fab'] ?? null
                        ]);
                    }
                }
            }
        }
    }

    private function converterDataExcel($data) {
        if (!$data) return now();
        try {
            // Se o Excel enviou um número, converte corretamente
            if (is_numeric($data)) {
                return Date::excelToDateTimeObject($data)->format('Y-m-d H:i:s');
            }
            return \Carbon\Carbon::parse($data)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return now();
        }
    }

    private function limpezaTotal($texto) {
        if (!$texto) return "";
        $texto = mb_strtoupper($texto, 'UTF-8');
        $remover = array("Á"=>"A","À"=>"A","Â"=>"A","Ã"=>"A","Ä"=>"A","É"=>"E","È"=>"E","Ê"=>"E","Ë"=>"E","Í"=>"I","Ì"=>"I","Î"=>"I","Ï"=>"I","Ó"=>"O","Ò"=>"O","Ô"=>"O","Õ"=>"O","Ö"=>"O","Ú"=>"U","Ù"=>"U","Û"=>"U","Ü"=>"U","Ç"=>"C","Ñ"=>"N");
        return preg_replace('/[^A-Z0-9]/', '', strtr($texto, $remover));
    }
}