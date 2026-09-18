<?php

namespace App\Http\Controllers;

use App\Models\TicketPesagem;
use Illuminate\Http\Request;
use App\Models\Pesagem;
use App\Models\Produto;
use App\Models\ConfigNota;
use App\Models\BalancaConfig;
use App\Services\Pesagem\PesagemTicketImagemService;
use App\Services\Pesagem\PesagemTicketNotificacaoService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use App\Support\BinaryPayloadSanitizer;

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

            // Edição exige confirmação explícita da interface.
            if ($request->filled('id') && !$request->boolean('edicao_confirmada')) {
                return response()->json([
                    'error' => 'Confirme a edição do ticket de pesagem antes de salvar as alterações.'
                ], 422);
            }

            // Idempotência por token: tickets_pesagem.token já possui índice UNIQUE.
            // O mesmo token é mantido durante toda a tentativa de inclusão, inclusive
            // enquanto o ADP captura evidências. Requisições repetidas retornam o
            // ticket já criado em vez de gerar uma segunda pesagem.
            $tokenSolicitado = trim((string) $request->input('token', ''));
            if (!$request->filled('id') && $tokenSolicitado !== '') {
                $ticketJaCriado = TicketPesagem::query()
                    ->where('token', $tokenSolicitado)
                    ->where('empresa_id', $this->empresa_id)
                    ->where('pesagem_id', $request->pesagem_id)
                    ->first();

                if ($ticketJaCriado) {
                    return response()->json([
                        'success' => 'Ticket já havia sido salvo. A duplicidade foi impedida.',
                        'duplicado_prevenido' => true,
                        'ticket_id' => $ticketJaCriado->id,
                    ], 200);
                }
            }

            // Base64 é permitido somente durante o transporte/ciclo desta request.
            // Nunca deve ser persistido em TicketPesagem nem seguir para a auditoria.
            $snapshotsRaw = $request->input('camera_snapshots_json');
            $evidenceRaw = $request->input('balanca_evidence_json');

            $data = $request->all();
            $data['peso_origem'] = $request->input('peso_origem', 'manual');
            $data['empresa_id'] = $this->empresa_id;
            $data['balanca_evidence_json'] = is_string($evidenceRaw)
                ? BinaryPayloadSanitizer::sanitizeJsonString($evidenceRaw)
                : null;
            $data['camera_snapshots_json'] = is_string($snapshotsRaw)
                ? BinaryPayloadSanitizer::sanitizeJsonString($snapshotsRaw)
                : null;
            $data['camera_snapshot_at'] = $request->filled('camera_snapshots_json') ? now() : null;
            $data['usuario_id'] = $this->usuario_id;
            $data['filial_id'] = $this->filial_id ?? null;

            // Compatibilidade: algumas bases ainda não possuem esta coluna.
            // A fonte oficial das imagens é a tabela pesagem_ticket_imagens.
            $possuiColunaImagensPersistidas = Schema::hasColumn('tickets_pesagem', 'imagens_persistidas_json');
            if ($possuiColunaImagensPersistidas && $request->filled('imagens_persistidas_json')) {
                $imagensPersistidasRaw = $request->input('imagens_persistidas_json');
                $data['imagens_persistidas_json'] = is_string($imagensPersistidasRaw)
                    ? BinaryPayloadSanitizer::sanitizeJsonString($imagensPersistidasRaw)
                    : BinaryPayloadSanitizer::sanitize($imagensPersistidasRaw);
            }

            $taraInformada = $request->input('tara', $request->input('peso_bag', 0));
            $permiteEditarPesoBag = (bool) ($config->desbloquear_campo_peso_bag_ticket ?? false);
            $data['peso_bag'] = $permiteEditarPesoBag
                ? max(0, (float) $request->input('peso_bag', 0))
                : 0;

            if ($config && (bool) ($config->pesagem_auto_concluir_ticket ?? false)) {
                $data['status'] = 'concluído';
                if (empty($data['fim'])) {
                    $data['fim'] = now()->format('Y-m-d H:i:s');
                }
            }

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
                    \Log::debug('TicketPesagem sem tara explícita na leitura da balança; mantendo regra atual.', [
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

                \Log::debug('TicketPesagem valor definido por regra de origem', [
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

                if ($config && (bool) ($config->pesagem_bloquear_edicao_ticket_concluido ?? false) && $ticket->status === 'concluído') {
                    return response()->json([
                        'error' => 'Este ticket já está concluído e não pode ser editado conforme a configuração da empresa.'
                    ], 422);
                }
                $dadosAnteriores = BinaryPayloadSanitizer::sanitize($ticket->getAttributes()); // Snapshot sem relações Eloquent
                $ticket->update($data);
                $mensagem = 'Ticket atualizado!';
                $acao = 'update';
                $registroId = $ticket->id;

                // Atualiza os totais na tabela 'pesagens'
                $this->atualizarTotais($ticket->pesagem_id);
            } else {
                // 🔹 Criação de um novo ticket.
                // O token nasce no front antes da captura ADP e permanece estável
                // até a resposta do servidor, permitindo idempotência real.
                $data['token'] = $tokenSolicitado !== ''
                    ? $tokenSolicitado
                    : Str::random(40);

                try {
                    $ticket = TicketPesagem::create($data);
                } catch (QueryException $e) {
                    $ehDuplicidade = (string) $e->getCode() === '23000'
                        || (int) ($e->errorInfo[1] ?? 0) === 1062;

                    if (!$ehDuplicidade) {
                        throw $e;
                    }

                    $ticket = TicketPesagem::query()
                        ->where('token', $data['token'])
                        ->where('empresa_id', $this->empresa_id)
                        ->where('pesagem_id', $request->pesagem_id)
                        ->first();

                    if (!$ticket) {
                        throw $e;
                    }

                    return response()->json([
                        'success' => 'Ticket já havia sido salvo. A duplicidade foi impedida.',
                        'duplicado_prevenido' => true,
                        'ticket_id' => $ticket->id,
                    ], 200);
                }

                $registroId = $ticket->id;

                // Atualiza os totais na tabela 'pesagens'
                $this->atualizarTotais($ticket->pesagem_id);
                $mensagem = 'Ticket criado com sucesso!';
            }


            // Persistência física das imagens capturadas pelas câmeras ADP.
            // A imagem vem do navegador como data:image/base64 e o Laravel salva em public/S3/MinIO.
            $imagensPersistidas = [];
            if ($request->filled('camera_snapshots_json')) {
                try {
                    $imagensPersistidas = app(PesagemTicketImagemService::class)
                        ->persistirDoTicket($ticket->fresh(), $snapshotsRaw);

                    $referenciasPersistidas = array_values(array_filter(
                        $imagensPersistidas,
                        static fn (array $item) => (bool) ($item['success'] ?? false)
                    ));

                    if (Schema::hasColumn('tickets_pesagem', 'imagens_persistidas_json')) {
                        $ticket->imagens_persistidas_json = $referenciasPersistidas;
                    }

                    // A partir deste ponto o Base64 deixa de existir na persistência.
                    // As colunas legadas continuam existindo, mas passam a guardar
                    // somente caminho/URL e metadados pequenos das imagens.
                    $ticket->camera_snapshots_json = json_encode(
                        $referenciasPersistidas,
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    );
                    $ticket->balanca_evidence_json = $this->evidenciaPersistidaSemBase64(
                        $evidenceRaw,
                        $referenciasPersistidas
                    );
                    $ticket->camera_snapshot_at = now();
                    $ticket->saveQuietly();
                } catch (\Throwable $e) {
                    \Log::error('Erro ao persistir imagens do ticket de pesagem', [
                        'ticket_id' => $ticket->id,
                        'pesagem_id' => $ticket->pesagem_id,
                        'empresa_id' => $this->empresa_id,
                        'exception' => $e->getMessage(),
                    ]);

                    return response()->json([
                        'error' => 'O ticket foi processado, mas não foi possível salvar as imagens da pesagem: ' . $e->getMessage(),
                    ], 500);
                }
            }

            $ticket = $ticket->fresh();

            if ($config && $ticket && $ticket->status === 'concluído') {
                $statusAnterior = is_array($dadosAnteriores) ? ($dadosAnteriores['status'] ?? null) : null;
                $temNovaColetaCamera = $request->filled('camera_snapshots_json');
                if ($acao === 'create' || $statusAnterior !== 'concluído' || $temNovaColetaCamera) {
                    app(PesagemTicketNotificacaoService::class)->notificarConclusao($ticket, $config);
                }
            }

            // Retorna como JSON para atualização dinâmica
            return response()->json([
                'success' => $mensagem,
                'ticket_id' => $ticket->id,
                'acao' => $acao,
                'imagens_persistidas' => $imagensPersistidas,
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Erro ao salvar ticket', [
                'usuario_id' => $this->usuario_id,
                'empresa_id' => $this->empresa_id,
                'exception' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Erro ao salvar ticket. Verifique os dados informados e tente novamente.'], 500);
        }
    }


    /**
     * Mantém a evidência funcional da balança, substituindo os snapshots
     * transitórios por referências aos arquivos definitivamente persistidos.
     */
    private function evidenciaPersistidaSemBase64(mixed $evidenceRaw, array $referenciasPersistidas): ?string
    {
        $evidence = [];

        if (is_string($evidenceRaw) && trim($evidenceRaw) !== '') {
            $decoded = json_decode($evidenceRaw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $evidence = BinaryPayloadSanitizer::sanitize($decoded);
            }
        } elseif (is_array($evidenceRaw)) {
            $evidence = BinaryPayloadSanitizer::sanitize($evidenceRaw);
        }

        if (!$evidence && !$referenciasPersistidas) {
            return null;
        }

        $evidence['cameras'] = $referenciasPersistidas;

        $encoded = json_encode(
            $evidence,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        );

        return $encoded === false ? null : $encoded;
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

        if (!$request->filled('balanca_evidence_json')) {
            return 'Pesagem manual bloqueada. Capture a evidência da balança antes de salvar o ticket.';
        }

        $evidence = json_decode((string) $request->input('balanca_evidence_json'), true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($evidence)) {
            return 'Evidência da balança inválida. Refaça a captura de peso.';
        }

        $evidenceBalancaId = (int) data_get($evidence, 'balanca.id', 0);
        if ($evidenceBalancaId > 0 && $evidenceBalancaId !== $balancaSelecionadaId) {
            return 'A evidência capturada não pertence à balança selecionada.';
        }

        $evidencePeso = (float) data_get($evidence, 'peso.valor', 0);
        if ($evidencePeso <= 0) {
            return 'Evidência da balança sem peso válido. Refaça a leitura.';
        }

        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();
        if ($config && (bool) ($config->pesagem_exigir_imagem_quando_balanca_tem_camera ?? false)) {
            $balanca = BalancaConfig::where('empresa_id', $this->empresa_id)->find($balancaSelecionadaId);
            $cameraUuids = json_decode((string) ($balanca->adp_camera_uuids ?? '[]'), true) ?: [];
            if (!empty($cameraUuids)) {
                $snapshots = json_decode((string) $request->input('camera_snapshots_json'), true) ?: [];
                $comImagem = collect($snapshots)->contains(function ($snapshot) {
                    return (bool) data_get($snapshot, 'success') && (
                            data_get($snapshot, 'image_data_url') ||
                            data_get($snapshot, 'data_url') ||
                            data_get($snapshot, 'image_base64') ||
                            data_get($snapshot, 'base64') ||
                            data_get($snapshot, 'response.image_base64') ||
                            data_get($snapshot, 'response.base64') ||
                            data_get($snapshot, 'response.data.image_base64') ||
                            data_get($snapshot, 'response.result.image_base64') ||
                            data_get($snapshot, 'response.image_url') ||
                            data_get($snapshot, 'response.snapshot_url') ||
                            data_get($snapshot, 'response.snapshot.file_path') ||
                            data_get($snapshot, 'snapshot.file_path') ||
                            data_get($snapshot, 'file_path')
                        );
                });

                if (!$comImagem) {
                    return 'A balança possui câmera vinculada. Capture ao menos uma imagem válida antes de salvar o ticket.';
                }
            }
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
            // Busca o ticket; a auditoria de exclusão é responsabilidade do BaseModel.
            $ticket = TicketPesagem::findOrFail($id);

            // 🔹 Exclui imagens físicas e registros vinculados ao ticket antes da exclusão.
            app(PesagemTicketImagemService::class)->excluirDoTicket($ticket, true);

            // 🔹 Exclui o ticket
            $ticket->delete();

            // Atualiza os totais na tabela 'pesagens'
            $this->atualizarTotais($ticket->pesagem_id);

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

            // A atualização acima já é auditada uma única vez pelo BaseModel,
            // contendo somente os campos efetivamente alterados.

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
