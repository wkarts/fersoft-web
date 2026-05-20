<?php

namespace App\Http\Controllers;

use App\Models\BalancaConfig;
use App\Services\Balanca\BalancaLeitorService;
use Illuminate\Http\Request;

class BalancaLeitorController extends BaseController
{
    public function __construct(private BalancaLeitorService $service)
    {
        parent::__construct();
    }

    public function index()
    {
        $balancas = BalancaConfig::where('empresa_id', $this->empresa_id)->where('ativo', true)->where('integrador', 'adp')->get();
        return view('balanca_leitor.index', compact('balancas'));
    }

    public function configuradas() { return response()->json(BalancaConfig::where('empresa_id', $this->empresa_id)->where('ativo', true)->where('integrador', 'adp')->get()); }
    public function status($id) { return response()->json($this->service->status($this->balanca($id))); }
    public function details($id) { return response()->json($this->service->details($this->balanca($id))); }
    public function open($id) { return response()->json($this->service->open($this->balanca($id))); }
    public function close($id) { return response()->json($this->service->close($this->balanca($id))); }
    public function read($id) { return response()->json($this->service->read($this->balanca($id))); }
    public function snapshot($id) { return response()->json($this->service->snapshot($this->balanca($id))); }
    public function captureEvidence($id) { return response()->json($this->service->captureEvidence($this->balanca($id))); }

    public function applyToTicket(Request $request)
    {
        return response()->json(['success' => true, 'data' => $request->only(['balanca_config_id', 'peso', 'peso_origem', 'balanca_evidence_json'])]);
    }

    private function balanca($id): BalancaConfig
    {
        return BalancaConfig::where('empresa_id', $this->empresa_id)->where('integrador', 'adp')->findOrFail($id);
    }
}
