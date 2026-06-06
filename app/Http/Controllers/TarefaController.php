<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tarefa;
use Carbon\Carbon;

class TarefaController extends BaseController
{
    public function __construct()
    {
        $this->model = Tarefa::class;
        $this->formTitle = 'Tarefa';
        $this->redirectPage = '/tarefas';
        $this->listView = 'tarefas/list';
        $this->registerView = 'tarefas/register';

        parent::__construct();

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
        return ['ID', 'Título', 'Funcionário', 'Prioridade', 'Data', 'Status', 'Tempo Gasto (min)'];
    }

    protected function fields(): array
    {
        // CORRIGIDO: Incluídos 'data_limite' e 'hora_limite' para que o sistema permita salvá-los no banco!
        return [
            'id', 'titulo', 'funcionario_id', 'prioridade', 'data', 'status',
            'tempo_gasto_minutos', 'data_limite', 'hora_limite', 'justificativa_atraso'
        ];
    }

    public function save(Request $request)
    {
        $this->validate($request, $this->rules(), $this->messages());

        // Regra de Fim de Semana
        $dataInicio = Carbon::parse($request->data);
        if ($dataInicio->isWeekend()) {
            session()->flash('mensagem_erro', 'Não é permitido gerar ou iniciar tarefas aos sábados e domingos.');
            return redirect()->back()->withInput();
        }

        // Força o preenchimento do status inicial padrão se for novo registro
        $request->merge(['status' => $request->input('status', 'pendente')]);

        return parent::save($request);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, $this->rules(), $this->messages());

        // Regra de Fim de Semana
        $dataInicio = Carbon::parse($request->data);
        if ($dataInicio->isWeekend()) {
            session()->flash('mensagem_erro', 'Não é permitido alterar o início de tarefas para sábados e domingos.');
            return redirect()->back()->withInput();
        }

        return parent::update($request, $id);
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

            if (Carbon::now()->isWeekend()) {
                session()->flash('mensagem_erro', 'Alertas e ações de tarefas estão suspensos aos sábados e domingos.');
                return redirect()->back();
            }

            $usuario_logado_id = session('user_logged')['id'];
            $usuarioLogado = \App\Models\Usuario::find($usuario_logado_id);
            $funcionarioDaTarefa = \App\Models\Funcionario::find($tarefa->funcionario_id);

            if (!$usuarioLogado->adm && $funcionarioDaTarefa && $funcionarioDaTarefa->usuario_id != $usuario_logado_id) {
                session()->flash('mensagem_erro', 'Acesso Negado! Você só pode mexer nas suas próprias tarefas.');
                return redirect()->back();
            }

            $dadosAnteriores = json_encode($tarefa->toArray(), JSON_UNESCAPED_UNICODE);

            $tarefa->update([
                'status' => 'em_andamento',
                'iniciado_em' => Carbon::now()
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

    public function pausar($id)
    {
        try {
            $tarefa = $this->getTenantRecords()->findOrFail($id);

            if (Carbon::now()->isWeekend()) {
                session()->flash('mensagem_erro', 'Alertas e ações de tarefas estão suspensos aos sábados e domingos.');
                return redirect()->back();
            }

            $usuario_logado_id = session('user_logged')['id'];
            $usuarioLogado = \App\Models\Usuario::find($usuario_logado_id);
            $funcionarioDaTarefa = \App\Models\Funcionario::find($tarefa->funcionario_id);

            if (!$usuarioLogado->adm && $funcionarioDaTarefa && $funcionarioDaTarefa->usuario_id != $usuario_logado_id) {
                session()->flash('mensagem_erro', 'Acesso Negado! Você só pode mexer nas suas próprias tarefas.');
                return redirect()->back();
            }

            $dadosAnteriores = json_encode($tarefa->toArray(), JSON_UNESCAPED_UNICODE);
            $agora = Carbon::now();

            $minutosDessaTranche = 0;
            if ($tarefa->iniciado_em) {
                $minutosDessaTranche = Carbon::parse($tarefa->iniciado_em)->diffInMinutes($agora);
            }
            $novoTempoTotal = ($tarefa->tempo_gasto_minutos ?? 0) + $minutosDessaTranche;

            $tarefa->update([
                'status' => 'pendente',
                'iniciado_em' => null,
                'tempo_gasto_minutos' => $novoTempoTotal
            ]);

            $this->logService->registrar('pausar_tarefa', Tarefa::class, [
                'registro_id' => $tarefa->id,
                'dados_antes' => $dadosAnteriores,
                'dados_depois' => json_encode($tarefa->fresh()->toArray(), JSON_UNESCAPED_UNICODE),
            ]);

            session()->flash('mensagem_sucesso', 'Tarefa pausada com sucesso. Tempo salvo!');
        } catch (\Exception $e) {
            session()->flash('mensagem_erro', 'Erro ao pausar tarefa: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    public function finalizar(Request $request, $id)
    {
        try {
            $tarefa = $this->getTenantRecords()->findOrFail($id);

            if (Carbon::now()->isWeekend()) {
                session()->flash('mensagem_erro', 'Alertas e ações de tarefas estão suspensos aos sábados e domingos.');
                return redirect()->back();
            }

            $usuario_logado_id = session('user_logged')['id'];
            $usuarioLogado = \App\Models\Usuario::find($usuario_logado_id);
            $funcionarioDaTarefa = \App\Models\Funcionario::find($tarefa->funcionario_id);

            if (!$usuarioLogado->adm && $funcionarioDaTarefa && $funcionarioDaTarefa->usuario_id != $usuario_logado_id) {
                session()->flash('mensagem_erro', 'Acesso Negado! Você só pode mexer nas suas próprias tarefas.');
                return redirect()->back();
            }

            $dadosAnteriores = json_encode($tarefa->toArray(), JSON_UNESCAPED_UNICODE);
            $agora = Carbon::now();
            $justificativa = $request->query('justificativa');

            if (!empty($tarefa->data_limite)) {
                $dataApenas = Carbon::parse($tarefa->data_limite)->format('Y-m-d');
                $horaLimite = !empty($tarefa->hora_limite) ? $tarefa->hora_limite : '23:59:59';
                $dataHoraLimite = Carbon::parse($dataApenas . ' ' . $horaLimite);

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

            $minutosDessaTranche = 0;
            if ($tarefa->iniciado_em) {
                $minutosDessaTranche = Carbon::parse($tarefa->iniciado_em)->diffInMinutes($agora);
            }
            $tempoFinalAcumulado = ($tarefa->tempo_gasto_minutos ?? 0) + $minutosDessaTranche;

            $tarefa->update([
                'status' => 'concluida',
                'finalizado_em' => $agora,
                'tempo_gasto_minutos' => $tempoFinalAcumulado,
                'justificativa_atraso' => $tarefa->justificativa_atraso
            ]);

            $this->logService->registrar('finalizar_tarefa', Tarefa::class, [
                'registro_id' => $tarefa->id,
                'dados_antes' => $dadosAnteriores,
                'dados_depois' => json_encode($tarefa->fresh()->toArray(), JSON_UNESCAPED_UNICODE),
            ]);

            session()->flash('mensagem_sucesso', "Tarefa concluída! Tempo total: {$tempoFinalAcumulado} minutos.");
        } catch (\Exception $e) {
            session()->flash('mensagem_erro', 'Erro ao finalizar tarefa: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    public function painel()
    {
        $usuario_id = session('user_logged')['id'];
        $usuario = \App\Models\Usuario::find($usuario_id);

        $query = Tarefa::where('empresa_id', $this->empresa_id);

        if (!$usuario->adm) {
            $funcionario = \App\Models\Funcionario::where('usuario_id', $usuario_id)->first();
            $query->where('funcionario_id', $funcionario->id ?? 0);
        }

        $tarefas = $query->get();

        $dados = [
            'pendentes' => $tarefas->where('status', 'pendente'),
            'andamento' => $tarefas->where('status', 'em_andamento'),
            'concluidas' => $tarefas->where('status', 'concluida'),
            'atrasadas' => $tarefas->filter(function($t) {
                if($t->status == 'concluida') return false;
                if(empty($t->data_limite)) return false;

                $dataApenas = Carbon::parse($t->data_limite)->format('Y-m-d');
                $limite = Carbon::parse($dataApenas . ' ' . ($t->hora_limite ?: '23:59:59'));

                return Carbon::now()->greaterThan($limite);
            })
        ];

        return view('tarefas/painel', $dados)->with('title', 'Painel Kanban de Tarefas');
    }
}
