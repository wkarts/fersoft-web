<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Produto;
use App\Models\ConfigNota;

class EquipamentoController extends BaseController
{
    // 👇 Propriedades exigidas pelo BaseController
    protected $redirectPage = '/equipamentos/mapa';
    protected $model = Produto::class;
    protected $resource = 'equipamentos';
    protected $table = 'produtos';

    // --- Métodos obrigatórios do BaseController ---
    public function rules(): array { return []; }
    public function messages(): array { return []; }

    public function index()
    {
        $empresa_id = $this->empresa_id;

        // Traz as coordenadas da base da empresa para os equipamentos que estão no Pátio
        $base = ConfigNota::where('empresa_id', $empresa_id)->first();
        $latBase = $base->latitude ?? '-12.9714'; // Fallback
        $lonBase = $base->longitude ?? '-38.5014';

        // Busca apenas as caçambas, containers e máquinas (Ativos)
        $equipamentos = Produto::with(['clienteAtual', 'fornecedorAtual'])
            ->where('empresa_id', $empresa_id)
            ->where('inativo', 0)
            ->where(function($q) {
                $q->where('valor_locacao', '>', 0)
                    ->orWhere('locacao', 1);
            })
            ->get();

        $marcadores = [];

        foreach ($equipamentos as $eq) {
            $lat = null;
            $lon = null;
            $local = '';
            $cor = 'green'; // Base

            if ($eq->status_localizacao == 'cliente' && $eq->cliente_atual_id) {
                $lat = $eq->latitude_atual ?? $eq->clienteAtual->latitude ?? null;
                $lon = $eq->longitude_atual ?? $eq->clienteAtual->longitude ?? null;
                $local = 'Cliente: ' . ($eq->clienteAtual->razao_social ?? $eq->clienteAtual->nome);
                $cor = 'red'; // No Cliente
            } elseif ($eq->status_localizacao == 'fornecedor' && $eq->fornecedor_atual_id) {
                $lat = $eq->latitude_atual ?? $eq->fornecedorAtual->latitude ?? null;
                $lon = $eq->longitude_atual ?? $eq->fornecedorAtual->longitude ?? null;
                $local = 'Fornecedor: ' . ($eq->fornecedorAtual->razao_social ?? $eq->fornecedorAtual->nome);
                $cor = 'orange'; // No Fornecedor
            } else {
                $lat = $latBase;
                $lon = $lonBase;
                $local = 'Pátio / Base da Empresa';
                $cor = 'green';
            }

            if ($lat && $lon) {
                $marcadores[] = [
                    'id' => $eq->id,
                    'nome' => $eq->nome,
                    'codigo' => $eq->codBarras ?? $eq->referencia,
                    'status' => $eq->status_localizacao,
                    'local' => $local,
                    'endereco' => $eq->endereco_atual ?? 'Endereço não registrado',
                    'lat' => $lat,
                    'lon' => $lon,
                    'cor' => $cor
                ];
            }
        }

        $title = "Mapa de Equipamentos e Caçambas";

        return view('equipamentos.index', compact('equipamentos', 'marcadores', 'title', 'latBase', 'lonBase'));
    }
}
