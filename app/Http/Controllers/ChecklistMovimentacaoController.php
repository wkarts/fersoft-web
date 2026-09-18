<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MovimentacaoVeiculo;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ChecklistMovimentacaoController extends Controller
{
    // Exibe o formulário leve para o motorista preencher no celular
    public function showForm($movimentacaoId)
    {
        $movimentacao = MovimentacaoVeiculo::with(['veiculo', 'motorista'])->findOrFail($movimentacaoId);

        // Verifica se já respondeu hoje para evitar duplicidade
        $jaRespondido = DB::table('checklists_movimentacoes')
            ->where('movimentacao_veiculo_id', $movimentacaoId)
            ->exists();

        return view('movimentacoes_veiculos.checklist_public', compact('movimentacao', 'jaRespondido'));
    }

    // Salva o checklist enviado pelo celular do motorista
    public function store(Request $request, $movimentacaoId)
    {
        $movimentacao = MovimentacaoVeiculo::findOrFail($movimentacaoId);

        try {
            DB::transaction(function () use ($request, $movimentacao) {

                // Monta o array com as respostas dos itens em formato JSON
                $respostas = [
                    'pneus' => $request->input('pneus', 'ok'),
                    'oleo_agua' => $request->input('oleo_agua', 'ok'),
                    'freios' => $request->input('freios', 'ok'),
                    'farois_lanternas' => $request->input('farois_lanternas', 'ok'),
                    'documentacao' => $request->input('documentacao', 'ok'),
                ];

                // Define se há alguma ressalva
                $statusGeral = 'aprovado';
                foreach ($respostas as $resp) {
                    if ($resp == 'problema') {
                        $statusGeral = 'com_ressalvas';
                    }
                }

                // Salva na tabela principal
                $checklistId = DB::table('checklists_movimentacoes')->insertGetId([
                    'empresa_id' => $movimentacao->empresa_id,
                    'movimentacao_veiculo_id' => $movimentacao->id,
                    'funcionario_id' => $movimentacao->motorista_id ?? 0,
                    'data_resposta' => Carbon::now(),
                    'obrigatorio' => 1,
                    'status_geral' => $statusGeral,
                    'observacoes' => $request->input('observacoes'),
                    'respostas_json' => json_encode($respostas),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

                // Salva as fotos enviadas (Frente, Painel, etc.)
                if ($request->hasFile('fotos')) {
                    foreach ($request->file('fotos') as $tipo => $foto) {
                        if ($foto->isValid()) {
                            $nomeArquivo = 'checklist_' . $checklistId . '_' . $tipo . '_' . time() . '.' . $foto->getClientOriginalExtension();
                            $path = $foto->storeAs('public/checklists', $nomeArquivo);

                            DB::table('checklist_fotos')->insert([
                                'checklist_movimentacao_id' => $checklistId,
                                'tipo_foto' => $tipo,
                                'caminho_arquivo' => 'storage/checklists/' . $nomeArquivo,
                                'created_at' => Carbon::now(),
                                'updated_at' => Carbon::now(),
                            ]);
                        }
                    }
                }
            });

            return view('movimentacoes_veiculos.checklist_sucesso');

        } catch (\Exception $e) {
            return redirect()->back()->with('erro', 'Erro ao salvar o checklist: ' . $e->getMessage())->withInput();
        }
    }
}
