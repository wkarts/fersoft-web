<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MovimentacaoVeiculo;
use App\Models\AbastecimentoMovimentacao;
use App\Models\Produto;
use Carbon\Carbon;

class AbastecimentoExternoController extends Controller
{
    // Renderiza a tela do celular para o motorista
    public function create($id)
    {
        $movimentacao = MovimentacaoVeiculo::with(['veiculo'])->findOrFail($id);

        // Busca apenas produtos do tipo combustível/Arla da empresa
        $combustiveis = Produto::where('empresa_id', $movimentacao->empresa_id)
            ->where(function($q) {
                $q->where('nome', 'LIKE', '%DIESEL%')
                    ->orWhere('nome', 'LIKE', '%ARLA%')
                    ->orWhere('nome', 'LIKE', '%GASOLINA%')
                    ->orWhere('nome', 'LIKE', '%ETANOL%');
            })->get();

        return view('abastecimento_externo.create', compact('movimentacao', 'combustiveis'));
    }

    // Recebe e salva os dados enviados pelo celular
    // Recebe e salva os dados enviados pelo celular
    public function store(Request $request)
    {
        $request->validate([
            'movimentacao_id' => 'required',
            'produto_id' => 'required',
            'quantidade' => 'required', // Litros
            'valor_total' => 'required',
            'km_abastecimento' => 'required',
            'foto_cupom' => 'nullable|image|max:5120' // Máximo 5MB
        ]);

        // Adicionado o 'with(motorista)' para puxarmos o ID de usuário dele
        $movimentacao = MovimentacaoVeiculo::with('motorista')->findOrFail($request->movimentacao_id);

        // Limpeza de formatação brasileira (Ex: 1.250,50 -> 1250.50)
        $qtd = (float) str_replace(',', '.', $request->quantidade);
        $totalLimpo = str_replace(['R$', '.', ' '], '', $request->valor_total);
        $total = (float) str_replace(',', '.', $totalLimpo);
        $km = (float) str_replace(',', '.', $request->km_abastecimento);

        $unitario = $qtd > 0 ? ($total / $qtd) : 0;

        // --- SOLUÇÃO DA CHAVE ESTRANGEIRA (USUÁRIO) ---
        // Se o motorista tem login no ERP, usamos o ID dele. Se não, pegamos o usuário Master da empresa.
        $usuarioId = $movimentacao->motorista->usuario_id ?? null;
        if (!$usuarioId) {
            $usuarioId = \App\Models\Usuario::where('empresa_id', $movimentacao->empresa_id)->value('id');
        }

        // Upload da foto do cupom fiscal
        $nomeFoto = null;
        if ($request->hasFile('foto_cupom') && $request->file('foto_cupom')->isValid()) {
            $ext = $request->foto_cupom->extension();
            $nomeFoto = 'cupom_' . $movimentacao->id . '_' . time() . '.' . $ext;
            $request->foto_cupom->move(public_path('imgs_abastecimentos'), $nomeFoto);
        }

        AbastecimentoMovimentacao::create([
            'empresa_id' => $movimentacao->empresa_id,
            'usuario_id' => $usuarioId, // <--- CAMPO INSERIDO AQUI PARA RESOLVER O ERRO 1452
            'filial_id' => $movimentacao->filial_id,
            'movimentacao_id' => $movimentacao->id,
            'produto_id' => $request->produto_id,
            'tipo' => 'externo',
            'data_abastecimento' => Carbon::now()->format('Y-m-d'),
            'quantidade' => $qtd,
            'valor_unitario' => $unitario,
            'valor_total' => $total,
            'km_abastecimento' => $km,
            'observacao' => $nomeFoto ? 'Foto do cupom salva: ' . $nomeFoto : 'Sem foto do cupom'
        ]);

        return redirect()->back()->with('sucesso', 'Abastecimento registrado com sucesso! Boa viagem 🚚');
    }
}
