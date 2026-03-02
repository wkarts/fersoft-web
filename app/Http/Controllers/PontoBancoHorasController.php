<?php

namespace App\Http\Controllers;

use App\Models\Funcionario;
use App\Models\PontoBancoHora;
use App\Services\Ponto\PontoBancoHorasService;
use Illuminate\Http\Request;

class PontoBancoHorasController extends Controller
{
    protected $empresa_id;

    public function __construct(private PontoBancoHorasService $service)
    {
        $this->middleware(function ($request, $next) {
            $this->empresa_id = $request->empresa_id;
            if (!session('user_logged')) {
                return redirect('/login');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $funcionarios = Funcionario::where('empresa_id', $this->empresa_id)->orderBy('nome')->get();

        $lancamentos = PontoBancoHora::where('empresa_id', $this->empresa_id)
            ->when($request->funcionario_id, fn($q) => $q->where('funcionario_id', $request->funcionario_id))
            ->orderBy('data_referencia', 'desc')
            ->paginate(40);

        return view('ponto_banco_horas.index', compact('funcionarios', 'lancamentos'))
            ->with('title', 'Banco de Horas');
    }

    public function recalcular(Request $request)
    {
        $request->validate([
            'funcionario_id' => 'required|integer',
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after_or_equal:data_inicio',
        ]);

        $saldo = $this->service->recalcular((int)$this->empresa_id, (int)$request->funcionario_id, $request->data_inicio, $request->data_fim);
        session()->flash('mensagem_sucesso', 'Banco de horas recalculado. Saldo final: ' . $saldo . ' minutos.');
        return redirect('/ponto/banco-horas?funcionario_id=' . $request->funcionario_id);
    }
}
