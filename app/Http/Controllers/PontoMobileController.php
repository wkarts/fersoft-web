<?php

namespace App\Http\Controllers;

use App\Models\Funcionario;
use App\Models\PontoDispositivo;
use App\Models\PontoMarcacao;
use Illuminate\Http\Request;

class PontoMobileController extends Controller
{
    public function registrarMarcacao(Request $request)
    {
        $request->validate([
            'empresa_id' => 'required|integer',
            'funcionario_id' => 'required|integer',
            'uuid' => 'required|string|max:120',
            'data_hora_marcacao' => 'nullable|date',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'foto' => 'nullable|string',
            'justificativa' => 'nullable|string|max:500',
        ]);

        $funcionario = Funcionario::where('empresa_id', $request->empresa_id)
            ->where('id', $request->funcionario_id)
            ->firstOrFail();

        PontoDispositivo::updateOrCreate([
            'empresa_id' => $request->empresa_id,
            'uuid' => $request->uuid,
        ], [
            'funcionario_id' => $funcionario->id,
            'plataforma' => $request->input('plataforma'),
            'modelo' => $request->input('modelo'),
            'versao_app' => $request->input('versao_app'),
            'ativo' => true,
            'ultimo_acesso_em' => now(),
        ]);

        $marcacao = PontoMarcacao::create([
            'empresa_id' => $request->empresa_id,
            'funcionario_id' => $funcionario->id,
            'data_hora_marcacao' => $request->input('data_hora_marcacao', now()),
            'origem' => 'mobile',
            'status' => 'bruta',
            'dados_brutos' => [
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'foto' => $request->foto,
                'uuid' => $request->uuid,
                'justificativa' => $request->justificativa,
            ],
            'observacoes' => $request->justificativa,
        ]);

        return response()->json([
            'ok' => true,
            'id' => $marcacao->id,
            'mensagem' => 'Marcação remota registrada com sucesso',
        ]);
    }
}
