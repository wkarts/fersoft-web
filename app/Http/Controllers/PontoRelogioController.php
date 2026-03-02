<?php

namespace App\Http\Controllers;

use App\Http\Requests\PontoRelogioRequest;
use App\Models\PontoRelogio;
use Illuminate\Http\Request;

class PontoRelogioController extends Controller
{
    protected $empresa_id;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->empresa_id = $request->empresa_id;
            if (!session('user_logged')) {
                return redirect('/login');
            }
            return $next($request);
        });
    }

    public function index()
    {
        $itens = PontoRelogio::where('empresa_id', $this->empresa_id)
            ->orderBy('id', 'desc')
            ->get();

        return view('ponto_relogio.list', compact('itens'))
            ->with('title', 'Relógios de Ponto');
    }

    public function new()
    {
        return view('ponto_relogio.register')
            ->with('title', 'Novo Relógio');
    }

    public function edit($id)
    {
        $item = PontoRelogio::where('empresa_id', $this->empresa_id)->findOrFail($id);
        return view('ponto_relogio.register', compact('item'))
            ->with('title', 'Editar Relógio');
    }

    public function save(PontoRelogioRequest $request)
    {
        PontoRelogio::create(array_merge(
            $request->validated(),
            [
                'empresa_id' => $this->empresa_id,
                'ativo' => (bool)$request->input('ativo', true),
            ]
        ));

        session()->flash('mensagem_sucesso', 'Relógio cadastrado com sucesso!');
        return redirect('/ponto/relogios');
    }

    public function update($id, PontoRelogioRequest $request)
    {
        $item = PontoRelogio::where('empresa_id', $this->empresa_id)->findOrFail($id);
        $item->update(array_merge($request->validated(), [
            'ativo' => (bool)$request->input('ativo', false),
        ]));

        session()->flash('mensagem_sucesso', 'Relógio atualizado com sucesso!');
        return redirect('/ponto/relogios');
    }

    public function delete($id)
    {
        $item = PontoRelogio::where('empresa_id', $this->empresa_id)->findOrFail($id);
        $item->delete();

        session()->flash('mensagem_sucesso', 'Relógio removido com sucesso!');
        return redirect('/ponto/relogios');
    }
}
