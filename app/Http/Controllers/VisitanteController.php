<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Visitante;

class VisitanteController extends Controller
{
    protected $empresa_id = null;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->empresa_id = $request->empresa_id;
            if (!session('user_logged')) {
                return redirect("/login");
            }
            return $next($request);
        });
    }

    // 1. TELA PRINCIPAL DE LISTAGEM
    public function index(Request $request)
    {
        $title = 'Gestão de Visitantes';
        $pesquisa = $request->pesquisa;

        // Inicia a busca garantindo que só puxe da empresa logada
        $query = Visitante::where('empresa_id', $this->empresa_id);

        // Se o usuário digitou algo na barra de pesquisa (nome ou cpf)
        if ($pesquisa) {
            $query->where(function($q) use ($pesquisa) {
                $q->where('nome', 'like', "%{$pesquisa}%")
                    ->orWhere('cpf', 'like', "%{$pesquisa}%");
            });
        }

        // Traz os visitantes paginados (30 por página) e ordenados
        $visitantes = $query->orderBy('nome', 'asc')->paginate(30);

        return view('visitantes.index', compact('visitantes', 'title', 'pesquisa'));
    }

    // 2. SALVAR EDIÇÃO DO VISITANTE
    public function atualizar(Request $request)
    {
        // Trava de segurança no backend: só admin deve gerenciar isso
        if (session('user_logged')['super'] != 1) {
            session()->flash("mensagem_erro", "Você não tem permissão para editar visitantes.");
            return redirect()->back();
        }

        $visitante = Visitante::where('id', $request->visitante_id)
            ->where('empresa_id', $this->empresa_id)
            ->first();

        if ($visitante) {
            $visitante->nome = $request->nome;
            $visitante->telefone = preg_replace('/[^0-9]/', '', $request->telefone); // Salva só os números

            // Lógica da Lista Negra
            $visitante->bloqueado = $request->bloqueado;
            $visitante->motivo_bloqueio = ($request->bloqueado == 1) ? $request->motivo_bloqueio : null;

            $visitante->save();

            session()->flash("mensagem_sucesso", "Cadastro do visitante atualizado com sucesso!");
        } else {
            session()->flash("mensagem_erro", "Visitante não encontrado.");
        }

        return redirect()->back();
    }
}
