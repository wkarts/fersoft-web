<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tarefa;
use Carbon\Carbon;

class TarefaController extends BaseController
{
    public function __construct()
    {
        // Propriedades originais
        $this->model = Tarefa::class;
        $this->formTitle = 'Tarefa';
        $this->redirectPage = '/tarefas';
        $this->listView = 'tarefas/list';
        $this->registerView = 'tarefas/register';

        // Inicia o BaseController
        parent::__construct(); 

        // PLANO INFALÍVEL: Força o sistema a saber qual é a empresa em TODAS as rotas
        $this->middleware(function ($request, $next) {
            if (empty($this->empresa_id)) {
                $usuario_id = session('user_logged')['id'];
                $usuario = \App\Models\Usuario::find($usuario_id);
                $this->empresa_id = $usuario->empresa_id;
            }
            return $next($request);
        });
    }

    protected function rules(): array
    {
        return [
            'funcionario_id' => 'required|integer',
            'titulo'         => 'required|string|max:255',
            'data'           => 'required|date',
            'hora_estimada'  => 'nullable',
            'data_limite'    => 'nullable|date',
            'hora_limite'    => 'nullable',
            'prioridade'     => 'required|string|in:Baixa,Normal,Alta,Urgente'
        ];
    }

    protected function messages(): array
    {
        return [
            'funcionario_id.required' => 'O funcionário responsável é obrigatório.',
            'titulo.required'         => 'O título da tarefa é obrigatório.',
            'data.required'           => 'A data da tarefa é obrigatória.',
        ];
    }

    protected function headers(): array
    {
        // Adicionamos a Prioridade na listagem
        return ['ID', 'Título', 'Funcionário', 'Prioridade', 'Data', 'Status', 'Tempo Gasto (min)'];
    }

    protected function fields(): array
    {
        // Ajustado para os nomes corretos das colunas/relações
        return ['id', 'titulo', 'funcionario_id', 'prioridade', 'data', 'status', 'tempo_gasto_minutos'];
    }

    public function register($id = null)
    {
        $view = parent::register($id);
        
        $usuario_id = session('user_logged')['id'];
        $usuario = \App\Models\Usuario::find($usuario_id);
        $empresa_id = $usuario->empresa_id;

        $funcionarios = \App\Models\Funcionario::select('funcionarios.*')
            ->join('usuarios', 'usuarios.id', '=', 'funcionarios.usuario_id')
            ->where('funcionarios.empresa_id', $empresa_id)
            ->get();

        return $view->with('funcionarios', $funcionarios);
    }

    public function iniciar($id)
    {
        try {
            $tarefa = $this->getTenantRecords()->findOrFail($id);

            // --- NOVA TRAVA DE SEGURANÇA (COM SUPER PODER PARA ADM) ---
            $usuario_logado_id = session('user_logged')['id'];
            $usuarioLogado = \App\Models\Usuario::find($usuario_logado_id);
            $funcionarioDaTarefa = \App\Models\Funcionario::find($tarefa->funcionario_id);
            
            // Se NÃO for ADM e tentar mexer na tarefa de outro funcionário, barra!
            if (!$usuarioLogado->adm && $funcionarioDaTarefa && $funcionarioDaTarefa->usuario_id != $usuario_logado_id) {
                session()->flash('mensagem_erro', 'Acesso Negado! Você só pode mexer nas suas próprias tarefas.');
                return redirect()->back();
            }
            // -------------------------------

            $dadosAnteriores = json_encode($tarefa->toArray(), JSON_UNESCAPED_UNICODE);

            $tarefa->update([
                'status' => 'em_andamento',
                'iniciado_em' => \Carbon\Carbon::now()
            ]);

            $this->logService->registrar('iniciar_tarefa', Tarefa::class, [
                'registro_id' => $tarefa->id,
                'dados_antes' => $dadosAnteriores,
                'dados_depois' => json_encode($tarefa->fresh()->toArray(), JSON_UNESCAPED_UNICODE),
            ]);

            session()->flash('mensagem_sucesso', 'Tarefa iniciada! O tempo começou a contar.');
        } catch (\Exception $e) {
            session()->flash('mensagem_erro', 'Erro ao iniciar tarefa: ' . $e->getMessage());
        }

        return redirect()->back();
    }
  
    /**
     * Finalizar com Trava de Atraso e Pop-up (Opção B)
     */
   /**
     * Finalizar com Trava de Atraso e Trava de Usuário
     */
    public function finalizar(\Illuminate\Http\Request $request, $id)
    {
        try {
            $tarefa = $this->getTenantRecords()->findOrFail($id);
            
            // --- NOVA TRAVA DE SEGURANÇA (COM SUPER PODER PARA ADM) ---
            $usuario_logado_id = session('user_logged')['id'];
            $usuarioLogado = \App\Models\Usuario::find($usuario_logado_id);
            $funcionarioDaTarefa = \App\Models\Funcionario::find($tarefa->funcionario_id);
            
            // Se NÃO for ADM e tentar mexer na tarefa de outro funcionário, barra!
            if (!$usuarioLogado->adm && $funcionarioDaTarefa && $funcionarioDaTarefa->usuario_id != $usuario_logado_id) {
                session()->flash('mensagem_erro', 'Acesso Negado! Você só pode mexer nas suas próprias tarefas.');
                return redirect()->back();
            }
            // -------------------------------

            $dadosAnteriores = json_encode($tarefa->toArray(), JSON_UNESCAPED_UNICODE);
            $agora = \Carbon\Carbon::now();
            $justificativa = $request->query('justificativa');

            // --- BLOCO DA OPÇÃO B: VERIFICAÇÃO DE ATRASO ---
            if (!empty($tarefa->data_limite)) {
                $dataApenas = \Carbon\Carbon::parse($tarefa->data_limite)->format('Y-m-d');
                $horaLimite = !empty($tarefa->hora_limite) ? $tarefa->hora_limite : '23:59:59';
                $dataHoraLimite = \Carbon\Carbon::parse($dataApenas . ' ' . $horaLimite);

                if ($agora->greaterThan($dataHoraLimite)) {
                    if (empty($justificativa) && empty($tarefa->justificativa_atraso)) {
                        session()->flash("mensagem_erro", "Esta tarefa está ATRASADA! A justificativa é obrigatória.");
                        return redirect()->back();
                    }
                    if (!empty($justificativa)) {
                        $tarefa->justificativa_atraso = $justificativa;
                    }
                }
            }
            // -----------------------------------------------

            $minutosGastos = 0;
            if ($tarefa->iniciado_em) {
                $minutosGastos = \Carbon\Carbon::parse($tarefa->iniciado_em)->diffInMinutes($agora);
            }

            $tarefa->update([
                'status' => 'concluida',
                'finalizado_em' => $agora,
                'tempo_gasto_minutos' => $minutosGastos,
                'justificativa_atraso' => $tarefa->justificativa_atraso
            ]);

            $this->logService->registrar('finalizar_tarefa', \App\Models\Tarefa::class, [
                'registro_id' => $tarefa->id,
                'dados_antes' => $dadosAnteriores,
                'dados_depois' => json_encode($tarefa->fresh()->toArray(), JSON_UNESCAPED_UNICODE),
            ]);

            session()->flash('mensagem_sucesso', "Tarefa concluída! Tempo total: {$minutosGastos} minutos.");
        } catch (\Exception $e) {
            session()->flash('mensagem_erro', 'Erro ao finalizar tarefa: ' . $e->getMessage());
        }

        return redirect()->back();
    }
  
  public function painel()
    {
        $usuario_id = session('user_logged')['id'];
        $usuario = \App\Models\Usuario::find($usuario_id);
        
        // Regra de Visualização: Adm vê tudo, Funcionário vê só o dele
        $query = \App\Models\Tarefa::where('empresa_id', $this->empresa_id);
        
        if (!$usuario->adm) {
            $funcionario = \App\Models\Funcionario::where('usuario_id', $usuario_id)->first();
            $query->where('funcionario_id', $funcionario->id ?? 0);
        }

        $tarefas = $query->get();

        // Organiza as tarefas em colunas para a View
        $dados = [
            'pendentes' => $tarefas->where('status', 'pendente'),
            'andamento' => $tarefas->where('status', 'em_andamento'),
            'concluidas' => $tarefas->where('status', 'concluida'),
            'atrasadas' => $tarefas->filter(function($t) {
                if($t->status == 'concluida') return false;
                if(empty($t->data_limite)) return false;
                
                // O REMÉDIO: Extrair apenas a data antes de juntar a hora
                $dataApenas = \Carbon\Carbon::parse($t->data_limite)->format('Y-m-d');
                $limite = \Carbon\Carbon::parse($dataApenas . ' ' . ($t->hora_limite ?: '23:59:59'));
                
                return \Carbon\Carbon::now()->greaterThan($limite);
            })
        ];

        return view('tarefas/painel', $dados)->with('title', 'Painel Kanban de Tarefas');
    }
  
}