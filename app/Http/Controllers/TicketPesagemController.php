<?php

namespace App\Http\Controllers;

use App\Models\TicketPesagem;
use Illuminate\Http\Request;
use App\Models\Pesagem;
use App\Models\Produto;
use App\Models\ConfigNota;
use App\Models\BalancaConfig;

class TicketPesagemController extends BaseController
{
    protected $model = TicketPesagem::class;
    protected $resource = 'tickets_pesagem';
    protected $formTitle = 'Ticket de Pesagem';
    //protected $listView = 'tickets_pesagem.list';
    protected $listView = 'pesagens.list';
    protected $registerView = 'tickets_pesagem.register';
    protected $redirectPage = '/ticketsPesagem';

    public function __construct()
    {
        parent::__construct(); // Chama o construtor do BaseController
    }

    protected function rules(): array
    {
        return [
            'pesagem_id' => 'required|exists:pesagens,id',
            'veiculo_id' => 'required|exists:veiculos,id',
            'produto_id' => 'required|exists:produtos,id',
            'peso' => 'required|numeric|min:0',
            'balanca_config_id' => 'nullable|exists:balanca_configs,id',
            'peso_origem' => 'nullable|in:manual,balanca',
            'balanca_evidence_json' => 'nullable|string',
            'camera_snapshots_json' => 'nullable|string',
            'valor_unitario' => 'nullable|numeric|min:0',
            'valor_total' => 'nullable|numeric|min:0',
            //'peso_bag' => 'nullable|numeric|min:0|max:10000',
            'tipo' => 'required|in:entrada,saida,avulsa',
            'status' => 'required|in:em andamento,concluído',
        ];
    }

    protected function messages(): array
    {
        return [
            'pesagem_id.required' => 'A pesagem é obrigatória.',
            'produto_id.required' => 'O produto é obrigatório.',
            'veiculo_id.required' => 'O veículo é obrigatório.',
            'peso.required' => 'O peso é obrigatório.',
        ];
    }

    /**
     * Salvar Ticket
     */
    public function save(Request $request)
    {
        $request->validate($this->rules(), $this->messages());

        try {
            $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();
            $pesagem = Pesagem::where('empresa_id', $this->empresa_id)->findOrFail($request->pesagem_id);
            $ticketExistente = $request->filled('id')
                ? TicketPesagem::where('empresa_id', $this->empresa_id)->find($request->id)
                : null;

            $data = $request->all();
            $data['peso_origem'] = $request->input('peso_origem', 'manual');
            $data['empresa_id'] = $this->empresa_id;
            $data['balanca_evidence_json'] = $request->input('balanca_evidence_json');
            $data['camera_snapshots_json'] = $request->input('camera_snapshots_json');
            $data['camera_snapshot_at'] = $request->filled('camera_snapshots_json') ? now() : null;
            $data['usuario_id'] = $this->usuario_id;
            $data['filial_id'] = $this->filial_id ?? null;
            $taraInformada = $request->input('tara', $request->input('peso_bag', 0));
            $permiteEditarPesoBag = (bool) ($config->desbloquear_campo_peso_bag_ticket ?? false);
            $data['peso_bag'] = $permiteEditarPesoBag
                ? max(0, (float) $request->input('peso_bag', 0))
                : 0;

            if ($config && $config->bloquear_pesagem_manual_balanca) {
                $validacao = $this->validarPesagemSomenteBalanca($request);
                if ($validacao !== true) {
                    return response()->json(['error' => $validacao], 422);
                }

                if ((float) $request->input('peso', 0) <= 0) {
                    return response()->json([
                        'error' => 'Não foi possível obter pesagem da balança selecionada. Verifique a leitura e tente novamente.'
                    ], 422);
                }

                $data['balanca_config_id'] = (int) $request->input('balanca_config_id');
                $data['peso_origem'] = 'balanca';
                $data['peso_bag'] = $permiteEditarPesoBag
                    ? max(0, (float) $request->input('peso_bag', $taraInformada))
                    : max(0, (float) $taraInformada);

                if ($data['peso_bag'] <= 0) {
                    \Log::info('TicketPesagem sem tara explícita na leitura da balança; mantendo regra atual.', [
                        'empresa_id' => $this->empresa_id,
                        'usuario_id' => $this->usuario_id,
                        'pesagem_id' => $pesagem->id,
                        'balanca_config_id' => $data['balanca_config_id'] ?? null,
                    ]);
                }
            }

            if ($config && $config->usar_valores_ticket_pesagem) {
                $valorUnitarioInput = $request->input('valor_unitario', $ticketExistente->valor_unitario ?? 0);

                $data['valor_unitario'] = max(0, (float) $valorUnitarioInput);

                $pesoBruto = max(0, (float) $request->input('peso', 0));
                $pesoRecipiente = max(0, (float) ($data['peso_bag'] ?? $request->input('peso_bag', 0)));
                $pesoLiquido = max(0, $pesoBruto - $pesoRecipiente);
                $data['valor_total'] = max(0, $pesoLiquido * $data['valor_unitario']);

                $data['valor_origem'] = ($data['valor_unitario'] > 0 || $data['valor_total'] > 0)
                    ? 'ticket'
                    : 'manual';

                \Log::info('TicketPesagem valor definido por regra de origem', [
                    'empresa_id' => $this->empresa_id,
                    'usuario_id' => $this->usuario_id,
                    'pesagem_id' => $pesagem->id,
                    'valor_origem' => $data['valor_origem'],
                    'valor_unitario' => $data['valor_unitario'],
                    'valor_total' => $data['valor_total'],
                ]);
            }
            $acao = 'create';
            $registroId = null;
            $dadosAnteriores = [];

            // Verifica se está atualizando ou criando um novo ticket
            if ($request->filled('id')) {
                // 🔹 Atualização do ticket
                $ticket = TicketPesagem::findOrFail($request->id);
                $dadosAnteriores = $ticket->toArray(); // Captura os dados antes da alteração
                $ticket->update($data);
                $mensagem = 'Ticket atualizado!';
                $acao = 'update';
                $registroId = $ticket->id;

                // Atualiza os totais na tabela 'pesagens'
                $this->atualizarTotais($ticket->pesagem_id);
            } else {
                // 🔹 Criação de um novo ticket
                $data['token'] = md5(uniqid(rand(), true)); // Gera um token único
                $ticket = TicketPesagem::create($data); // Armazena o ticket criado
                $registroId = $ticket->id;

                // Atualiza os totais na tabela 'pesagens'
                $this->atualizarTotais($ticket->pesagem_id);
                $mensagem = 'Ticket criado com sucesso!';
            }

            // 🔹 Obtém a instância correta da model TicketPesagem
            $modelInstance = is_string(TicketPesagem::class) ? app(TicketPesagem::class) : TicketPesagem::class;

            // 🔹 Registra log da criação ou atualização do ticket
            $this->logService->registrar($acao, get_class($modelInstance), [
                'registro_id' => $registroId,
                'dados_antes' => $dadosAnteriores,
                'dados_depois' => $ticket->toArray(),
            ]);

            // Retorna como JSON para atualização dinâmica
            return response()->json(['success' => $mensagem], 200);
        } catch (\Exception $e) {
            \Log::error('Erro ao salvar ticket', [
                'usuario_id' => $this->usuario_id,
                'empresa_id' => $this->empresa_id,
                'exception' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Erro ao salvar ticket. Verifique os dados informados e tente novamente.'], 500);
        }
    }


    private function validarPesagemSomenteBalanca(Request $request)
    {
        $balancasAtivas = BalancaConfig::where('empresa_id', $this->empresa_id)
            ->where('ativo', true)
            ->count();

        if ($balancasAtivas <= 0) {
            return 'Pesagem manual bloqueada. Cadastre e ative ao menos uma balança para continuar.';
        }

        $balancaSelecionadaId = (int) $request->input('balanca_config_id');
        if ($balancaSelecionadaId <= 0) {
            return 'Pesagem manual bloqueada. Selecione uma balança ativa para capturar o peso.';
        }

        $balancaValida = BalancaConfig::where('empresa_id', $this->empresa_id)
            ->where('ativo', true)
            ->where('id', $balancaSelecionadaId)
            ->exists();

        if (!$balancaValida) {
            return 'Pesagem manual bloqueada. A balança selecionada está inativa ou inválida.';
        }

        if ($request->input('peso_origem') !== 'balanca') {
            return 'Pesagem manual bloqueada. Utilize a leitura da balança selecionada.';
        }

        return true;
    }

    private function resolverValoresTicket(Request $request, Pesagem $pesagem): array
    {
        $valorUnitarioInformado = (float) $request->input('valor_unitario', 0);
        $valorTotalInformado = (float) $request->input('valor_total', 0);

        if ($valorUnitarioInformado > 0 && $valorTotalInformado > 0) {
            return [
                'valor_unitario' => $valorUnitarioInformado,
                'valor_total' => $valorTotalInformado,
                'valor_origem' => 'ticket',
            ];
        }

        $produto = Produto::where('empresa_id', $this->empresa_id)->find($request->produto_id);
        $valorBase = 0.0;

        if ($produto) {
            $valorBase = $pesagem->tipo === 'compra'
                ? (float) ($produto->valor_compra ?? 0)
                : (float) ($produto->valor_venda ?? 0);
        }

        $pesoLiquido = max(0, ((float) $request->input('peso', 0)) - ((float) $request->input('peso_bag', 0)));

        return [
            'valor_unitario' => $valorBase,
            'valor_total' => $pesoLiquido * $valorBase,
            'valor_origem' => 'fallback_produto',
        ];
    }

    public function delete($id)
    {
        try {
            // 🔹 Busca o ticket antes da exclusão para capturar os dados
            $ticket = TicketPesagem::findOrFail($id);
            $dadosAnteriores = $ticket->toArray();
            $registroId = $ticket->id;

            // 🔹 Obtém a instância correta da model TicketPesagem
            $modelInstance = is_string(TicketPesagem::class) ? app(TicketPesagem::class) : TicketPesagem::class;

            // 🔹 Exclui o ticket
            $ticket->delete();

            // Atualiza os totais na tabela 'pesagens'
            $this->atualizarTotais($ticket->pesagem_id);

            // 🔹 Registra log da exclusão do ticket
            $this->logService->registrar('delete', get_class($modelInstance), [
                'registro_id' => $registroId,
                'dados_antes' => $dadosAnteriores,
                'dados_depois' => null, // Não há dados depois da exclusão
            ]);

            return redirect()->back()->with('success', 'Ticket excluído com sucesso!');
        } catch (\Exception $e) {
            \Log::error('Erro ao excluir ticket', [
                'id' => $id,
                'empresa_id' => $this->empresa_id,
                'usuario_id' => $this->usuario_id,
                'exception' => $e->getMessage(),
            ]);

            return redirect()->back()->withErrors(['error' => 'Erro ao excluir ticket: ' . $e->getMessage()]);
        }
    }

    /**
     * Abrir Modal para Edição
     */
    public function edit($id)
    {
        $ticket = TicketPesagem::findOrFail($id);
        return response()->json($ticket); // Retorna os dados em JSON para preencher a modal
    }

    public function list($pesagemId)
    {
        try {
            $tickets = TicketPesagem::where('pesagem_id', $pesagemId)
                ->with('produto')
                ->get();

            return response()->json($tickets);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao carregar tickets: ' . $e->getMessage()], 500);
        }
    }

    public function atualizarTotais($pesagemId)
    {
        try {
            // 🔹 Busca a pesagem com seus tickets
            $pesagem = Pesagem::with('tickets')->findOrFail($pesagemId);

            // 🔹 Captura os dados antes da alteração para log
            $dadosAnteriores = $pesagem->toArray();

            // Calcula os totais dos tickets
            //$entrada = $pesagem->tickets->where('tipo', 'entrada')->sum('peso');
            //$saida = $pesagem->tickets->where('tipo', 'saida')->sum('peso');
            //$avulsa = $pesagem->tickets->where('tipo', 'avulsa')->sum('peso');
            $entrada = $pesagem->tickets->where('tipo', 'entrada')->sum(function ($t) {
                return $t->peso - $t->peso_bag;
            });
            $saida = $pesagem->tickets->where('tipo', 'saida')->sum(function ($t) {
                return $t->peso - $t->peso_bag;
            });
            $avulsa = $pesagem->tickets->where('tipo', 'avulsa')->sum(function ($t) {
                return $t->peso - $t->peso_bag;
            });

            // Cálculo do peso bruto
            //$pesoBruto = abs($entrada - $saida + $avulsa);
            $pesoBruto = abs($entrada + $avulsa);

            // Calcula os descontos aplicáveis
            $descontos = 0;

            // Verifica e aplica descontos de campos ativos
            if ($pesagem->danificado) {
                $descontos += $pesoBruto * ($pesagem->danificado_desconto / 100);
            }
            if ($pesagem->quebrado) {
                $descontos += $pesoBruto * ($pesagem->quebrado_desconto / 100);
            }
            if ($pesagem->esverdeado) {
                $descontos += $pesoBruto * ($pesagem->esverdeado_desconto / 100);
            }
            if ($pesagem->ardido) {
                $descontos += $pesoBruto * ($pesagem->ardido_desconto / 100);
            }
            if ($pesagem->secagem) {
                $descontos += $pesoBruto * ($pesagem->secagem_desconto / 100);
            }

            // Descontos fixos
            $descontos += $pesoBruto * ($pesagem->umidade_desconto / 100);
            $descontos += $pesoBruto * ($pesagem->impureza_desconto / 100);

            // Cálculo do peso final
            //$pesoFinal = max(0, $pesoBruto - $descontos);
            $pesoFinal = max(0, $pesoBruto - $descontos - $saida);

            // 🔹 Atualiza os campos na tabela 'pesagens'
            $pesagem->update([
                'peso' => $pesoFinal, // Atualiza o campo 'peso'
                'peso_liquido_bruto' => $pesoBruto,
                'peso_final' => $pesoFinal
            ]);

            // 🔹 Captura os dados depois da alteração para log
            $dadosDepois = $pesagem->toArray();

            // 🔹 Obtém a instância correta da model Pesagem
            $modelInstance = is_string(Pesagem::class) ? app(Pesagem::class) : Pesagem::class;

            // 🔹 Registra log da atualização dos totais da pesagem
            $this->logService->registrar('update', get_class($modelInstance), [
                'registro_id' => $pesagem->id,
                'dados_antes' => $dadosAnteriores,
                'dados_depois' => $dadosDepois,
            ]);

        } catch (\Exception $e) {
            \Log::error('Erro ao atualizar totais da pesagem', [
                'pesagemId' => $pesagemId,
                'empresa_id' => $this->empresa_id,
                'usuario_id' => $this->usuario_id,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    public function getTicketsDados($pesagemId)
    {
        // Busca a pesagem com os tickets relacionados
        $pesagem = Pesagem::with(['tickets.produto']) // Inclui os relacionamentos necessários
        ->where('empresa_id', $this->empresa_id)  // Filtra pela empresa
        ->findOrFail($pesagemId);                 // Busca ou falha

        // Formata os tickets para a resposta JSON
        $tickets = $pesagem->tickets->map(function ($ticket) {
            return [
                'id' => $ticket->id,
                'produto' => $ticket->produto->nome ?? 'N/A',  // Produto ou N/A
                'referencia' => $ticket->produto->referencia ?? 'N/A',  // Produto ou N/A
                'tipo' => ucfirst($ticket->tipo),              // Tipo formatado
                'peso' => (float) $ticket->peso,               // Peso como número
                'peso_bag' => (float) $ticket->peso_bag,
                'peso_liquido' => (float) $ticket->peso - $ticket->peso_bag,
                'status' => $ticket->status,                   // Status
                'inicio' => $ticket->inicio ? $ticket->inicio->format('Y-m-d H:i') : null, // Início formatado
                'fim' => $ticket->fim ? $ticket->fim->format('Y-m-d H:i') : null           // Fim formatado
            ];
        });

        // Calcula os totais por tipo
        $totalPorTipo = [
            'Entrada' => $tickets->where('tipo', 'Entrada')->sum('peso'), // Total de Entrada
            'Saida' => $tickets->where('tipo', 'Saida')->sum('peso'),     // Total de Saída
            'Avulsa' => $tickets->where('tipo', 'Avulsa')->sum('peso')    // Total de Avulsa
        ];

        // Retorna os dados formatados em JSON
        return response()->json([
            'tickets' => $tickets,                     // Lista de tickets
            'total_peso' => $tickets->sum('peso'),     // Soma total do peso
            'tickets_count' => $tickets->count(),      // Quantidade de tickets
            'total_por_tipo' => $totalPorTipo          // Totais separados por tipo
        ]);
    }

}
