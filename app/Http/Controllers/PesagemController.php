<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use App\Models\Usuario;
use App\Models\Pesagem;
use App\Models\Veiculo;
use App\Models\Funcionario;
use App\Models\Cliente;
use App\Models\Fornecedor;
use App\Models\Produto;
use App\Models\TicketPesagem;
use App\Models\BalancaConfig;
use App\Models\Transportadora;
use App\Models\Cidade;
use App\Models\ItemCompra;
use App\Models\Compra;
use App\Models\Venda; // Adicione esta linha
use App\Models\ItemVenda; // Para os itens de venda
use App\Models\ConfigNota;
use App\Events\MovimentoRealtime;
use App\Services\MonitorPesagemService;
use App\Services\StockService;
use Dompdf\Dompdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Http\Controllers\CompraManualController;
use App\Http\Controllers\RelatorioController;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;


class PesagemController extends BaseController
{
    protected $model = Pesagem::class;
    protected $resource = 'pesagens';
    protected $formTitle = 'Pesagem';
    protected $listView = 'pesagens.list';
    protected $registerView = 'pesagens.list';
    protected $redirectPage = '/pesagens';

    public function __construct()
    {
        parent::__construct();// Herda as configurações do BaseController
        $this->middleware(function ($request, $next) {
            if (!$this->empresa_id) {
                return redirect('/login')->withErrors(['error' => 'Empresa não encontrada.']);
            }
            return $next($request);
        });
    }


    /**
     * Definição dos filtros disponíveis na listagem.
     */
    protected function defineFilters(Request $request): array
    {
        return [
            [
                'name' => 'veiculo_id',
                'label' => 'Veículo',
                'type' => 'select',
                'placeholder' => 'Todos os Veículos',
                'options' => Veiculo::all()->map(function ($veiculo) {
                    return [
                        'value' => $veiculo->id,
                        'label' => $veiculo->placa,
                    ];
                })->toArray(),
            ],
            [
                'name' => 'status',
                'label' => 'Status',
                'type' => 'select',
                'placeholder' => 'Todos os Status',
                'options' => [
                    ['value' => 'em andamento', 'label' => 'Em Andamento'],
                    ['value' => 'concluído', 'label' => 'Concluído'],
                ],
            ],
            [
                'name' => 'data_inicial',
                'label' => 'Data Inicial',
                'type' => 'date',
            ],
            [
                'name' => 'data_final',
                'label' => 'Data Final',
                'type' => 'date',
            ],
        ];
    }

    /**
     * Definição dos cabeçalhos da tabela.
     */
    protected function headers(): array
    {
        return ['ID', 'Veículo', 'Peso', 'Data', 'Status'];
    }

    /**
     * Definição dos campos da tabela.
     */
    protected function fields(): array
    {
        return ['id', 'veiculo.placa', 'peso', 'dt_registro', 'status'];
    }

    /**
     * Validação das regras.
     */
    protected function rules(): array
    {
        return [
            'veiculo_id' => 'required|exists:veiculos,id',
            'tipo' => 'required|in:compra,venda,avulsa',
            'cliente_id' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if (request()->input('tipo') === 'venda') {
                        if (empty($value)) {
                            $fail('O cliente é obrigatório para o tipo venda.');
                        }
                    }
                },
            ],
            'fornecedor_id' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if (request()->input('tipo') === 'compra') {
                        if (empty($value)) {
                            $fail('O fornecedor é obrigatório para o tipo compra.');
                        }
                    }
                },
            ],
        ];
    }

    /**
     * Mensagens personalizadas para validação.
     */
    protected function messages(): array
    {
        return [
            'veiculo_id.required' => 'O veículo é obrigatório.',
            'tipo.required' => 'O tipo de operação é obrigatório.',
            'status.required' => 'O status é obrigatório.',
            'peso.numeric' => 'O peso deve ser um valor numérico.',
            'cliente_id.exists' => 'O cliente selecionado é inválido.',
            'fornecedor_id.exists' => 'O fornecedor selecionado é inválido.',
        ];
    }

    /**
     * Salvar uma nova pesagem.
     */
    public function save(Request $request)
    {
        // Validação dos campos
        $request->validate($this->rules(), $this->messages());

        try {
            $data = $request->all();
            $data['empresa_id'] = $this->empresa_id;
            $data['usuario_id'] = $this->usuario_id;

            // Gera um token único somente para novas pesagens
            if (!$request->filled('id')) {
                $data['token'] = md5(uniqid(rand(), true));
            }

            // Garante que checkboxes booleanos sejam tratados corretamente
            $data['danificado'] = $request->has('danificado') ? 1 : 0;
            $data['quebrado'] = $request->has('quebrado') ? 1 : 0;
            $data['esverdeado'] = $request->has('esverdeado') ? 1 : 0;
            $data['ardido'] = $request->has('ardido') ? 1 : 0;
            $data['secagem'] = $request->has('secagem') ? 1 : 0;

            // Garante que os valores dos campos de desconto sejam enviados
            $data['danificado_desconto'] = $request->input('danificado_desconto', 0.00);
            $data['quebrado_desconto'] = $request->input('quebrado_desconto', 0.00);
            $data['esverdeado_desconto'] = $request->input('esverdeado_desconto', 0.00);
            $data['ardido_desconto'] = $request->input('ardido_desconto', 0.00);
            $data['secagem_desconto'] = $request->input('secagem_desconto', 0.00);

            //$data['motorista'] = $request->input('motorista') ?? null;

            // Verifica tipo e ajusta campos relacionados
            if ($data['tipo'] === 'compra') {
                $data['cliente_id'] = null; // Zera o cliente
                if (empty($data['fornecedor_id'])) {
                    return redirect()->back()->withInput()->withErrors(['fornecedor_id' => 'O fornecedor é obrigatório para o tipo compra.']);
                }
            } elseif ($data['tipo'] === 'venda') {
                $data['fornecedor_id'] = null; // Zera o fornecedor
                if (empty($data['cliente_id'])) {
                    return redirect()->back()->withInput()->withErrors(['cliente_id' => 'O cliente é obrigatório para o tipo venda.']);
                }
            }

            // Verifica se a pesagem será criada ou atualizada
            if ($request->filled('id')) {
                // Atualiza pesagem existente
                $pesagem = Pesagem::findOrFail($request->id);
                $dadosAntes = $pesagem->toArray();
                $pesagem->update($data);
                $mensagem = 'Pesagem atualizada com sucesso!';
                $acao = 'update';
            } else {
                // Cria nova pesagem
                $pesagem = Pesagem::create($data);
                $mensagem = 'Pesagem registrada com sucesso!';
                $acao = 'create';
                $dadosAntes = null;
            }

            // Obtém a classe correta do modelo, mesmo sendo string
            $modelInstance = is_string(Pesagem::class) ? app(Pesagem::class) : Pesagem::class;

            // 🔹 Registra o log da operação
            $this->logService->registrar($acao, get_class($modelInstance), [
                'registro_id' => $pesagem->id,
                'dados_antes' => $dadosAntes,
                'dados_depois' => $pesagem->toArray(),
            ]);

            $tipoEvento = $acao === 'create' ? 'pesagem.created' : 'pesagem.updated';
            $this->dispararMonitoramentoPesagem($pesagem->id, $tipoEvento);

            // Redireciona com sucesso, fechando o modal atual e abrindo o modal de ticket
            return redirect()->route('pesagens.list')
                ->with('success', $mensagem)
                ->with('openTicketModal', $pesagem->id); // Passa o ID da nova pesagem para abrir o modal de tickets
        } catch (\Exception $e) {
            \Log::error("Erro ao salvar pesagem: " . $e->getMessage());
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /** Cria cadastros mínimos no tenant atual para a pesagem. */
    public function cadastroRapido(Request $request, string $tipo)
    {
        if (!$this->empresa_id || !$this->usuario_id) abort(403);
        try {
            return DB::transaction(function () use ($request, $tipo) {
                if (in_array($tipo, ['cliente', 'fornecedor'], true)) {
                    $doc = preg_replace('/\D/', '', (string) $request->input('cpf_cnpj'));
                    $exterior = $request->boolean('exterior');
                    $data = $request->validate(['razao_social'=>'required|string|max:100','nome_fantasia'=>'nullable|string|max:80','telefone'=>'nullable|string|max:20','rua'=>'nullable|string|max:80','numero'=>'nullable|string|max:10','bairro'=>'nullable|string|max:50','cidade_id'=>'required|integer','cpf_cnpj'=>$exterior ? 'nullable|string|max:30' : 'required|digits_between:11,14','imagem'=>'nullable|image|mimes:jpg,jpeg,png,webp|max:2048']); unset($data['imagem']);
                    if (!Cidade::whereKey($data['cidade_id'])->exists()) return response()->json(['message'=>'A cidade informada é inválida.','errors'=>['cidade_id'=>['Selecione uma cidade válida.']]],422);
                    if (!$exterior && !$this->documentoValido($doc)) return response()->json(['message'=>'CPF/CNPJ inválido.','errors'=>['cpf_cnpj'=>['Informe um CPF ou CNPJ válido.']]],422);
                    $model = $tipo === 'cliente' ? Cliente::class : Fornecedor::class;
                    if (!$exterior && $model::where('empresa_id',$this->empresa_id)->whereRaw("REPLACE(REPLACE(REPLACE(cpf_cnpj, '.', ''), '-', ''), '/', '') = ?",[$doc])->exists()) return response()->json(['message'=>'Documento já cadastrado nesta empresa.','errors'=>['cpf_cnpj'=>['Já existe um cadastro com este CPF/CNPJ.']]],422);
                    $common=array_merge($data,['cpf_cnpj'=>$doc,'nome_fantasia'=>$data['nome_fantasia'] ?: $data['razao_social'],'empresa_id'=>$this->empresa_id,'celular'=>$request->input('celular',''),'email'=>$request->input('email',''),'cep'=>preg_replace('/\D/','',(string)$request->input('cep')),'ie_rg'=>$request->input('ie_rg',''),'complemento'=>$request->input('complemento',''),'cod_pais'=>$exterior ? (int)$request->input('cod_pais',0) : 1058]);
                    if ($tipo === 'cliente') $common += ['consumidor_final'=>1,'contribuinte'=>1,'limite_venda'=>0,'rua_cobranca'=>'','numero_cobranca'=>'','bairro_cobranca'=>'','cep_cobranca'=>'','rua_entrega'=>'','numero_entrega'=>'','bairro_entrega'=>'','cep_entrega'=>'','nome_entrega'=>'','cpf_cnpj_entrega'=>'']; else $common += ['contribuinte'=>1];
                    $record=$model::create($common); $this->salvarImagemCadastroRapido($request, $record, $tipo); $record->load('cidade'); return response()->json(['message'=>ucfirst($tipo).' cadastrado com sucesso.','data'=>$this->quickPayload($record,$tipo)],201);
                }
                if ($tipo === 'veiculo') {
                    $data=$request->validate(['placa'=>'required|string|max:8','uf'=>'required|string|size:2','marca'=>'required|string|max:20','modelo'=>'required|string|max:20','cor'=>'required|string|max:10','tipo'=>'required|string|max:2','tara'=>'required|string|max:10','capacidade'=>'required|string|max:10','combustivel'=>'required|string|max:30','proprietario_nome'=>'required|string|max:40','proprietario_documento'=>'required|string|max:20','proprietario_ie'=>'required|string|max:13','proprietario_uf'=>'required|string|size:2','proprietario_tp'=>'required|integer','motorista_id'=>'nullable|integer','imagem'=>'nullable|image|mimes:jpg,jpeg,png,webp|max:2048']); unset($data['imagem']); $data['placa']=strtoupper(preg_replace('/[^A-Za-z0-9]/','',$data['placa']));
                    $q=Veiculo::where('empresa_id',$this->empresa_id)->where('placa',$data['placa']); if($this->filial_id)$q->where('filial_id',$this->filial_id); if($q->exists()) return response()->json(['message'=>'Placa já cadastrada.','errors'=>['placa'=>['Já existe veículo com esta placa nesta filial.']]],422);
                    if(!empty($data['motorista_id'])&&!Funcionario::where('id',$data['motorista_id'])->where('empresa_id',$this->empresa_id)->where('status_motorista','Ativo')->exists())return response()->json(['message'=>'Motorista inválido.','errors'=>['motorista_id'=>['Selecione um motorista ativo desta empresa.']]],422);
                    $record=Veiculo::create(array_merge($data,['empresa_id'=>$this->empresa_id,'usuario_id'=>$this->usuario_id,'filial_id'=>$this->filial_id,'rntrc'=>'','taf'=>'','renavam'=>'','numero_registro_estadual'=>'','tipo_carroceira'=>'','tipo_rodado'=>'','ativo'=>'Sim','quilometragem'=>0])); $this->salvarImagemCadastroRapido($request, $record, $tipo); return response()->json(['message'=>'Veículo cadastrado com sucesso.','data'=>$this->quickPayload($record,$tipo)],201);
                }
                $data=$request->validate(['nome'=>'required|string|max:50','cpf'=>'required|digits:11','rg'=>'nullable|string|max:15','rua'=>'nullable|string|max:80','numero'=>'nullable|string|max:10','bairro'=>'nullable|string|max:50','telefone'=>'nullable|string|max:20','celular'=>'nullable|string|max:20','funcao_id'=>'nullable|integer','motorista'=>'nullable|boolean','cnh'=>'nullable|string|max:255','categoria_cnh'=>'nullable|in:A,B,C,D,E','vencimento_cnh'=>'nullable|date','imagem'=>'nullable|image|mimes:jpg,jpeg,png,webp|max:2048']); unset($data['imagem']); $motorista=$request->boolean('motorista');
                if($motorista&&(!( $data['cnh'] ?? null)||!( $data['categoria_cnh'] ?? null)||!( $data['vencimento_cnh'] ?? null)))return response()->json(['message'=>'Informe todos os dados da CNH.','errors'=>['cnh'=>['CNH, categoria e vencimento são obrigatórios para motorista.']]],422);
                if(!$this->documentoValido($data['cpf']))return response()->json(['message'=>'CPF inválido.','errors'=>['cpf'=>['Informe um CPF válido.']]],422);
                if(Funcionario::where('empresa_id',$this->empresa_id)->whereRaw("REPLACE(REPLACE(cpf, '.', ''), '-', '') = ?",[$data['cpf']])->exists())return response()->json(['message'=>'CPF já cadastrado nesta empresa.','errors'=>['cpf'=>['Já existe funcionário com este CPF.']]],422);
                if(!empty($data['funcao_id'])&&!\App\Models\Funcao::where('id',$data['funcao_id'])->where('empresa_id',$this->empresa_id)->exists())return response()->json(['message'=>'Função inválida.','errors'=>['funcao_id'=>['Selecione uma função desta empresa.']]],422);
                $record=Funcionario::create(array_merge($data,['empresa_id'=>$this->empresa_id,'usuario_id'=>$this->usuario_id,'filial_id'=>$this->filial_id,'cpf'=>preg_replace('/\D/','',$data['cpf']),'rg'=>$data['rg'] ?? '','rua'=>$data['rua'] ?? '','numero'=>$data['numero'] ?? '','bairro'=>$data['bairro'] ?? '','telefone'=>$data['telefone'] ?? '','celular'=>$data['celular'] ?? '','data_registro'=>now()->toDateString(),'status_funcionario'=>'Ativo','status_motorista'=>$motorista?'Ativo':'Inativo'])); $this->salvarImagemCadastroRapido($request, $record, 'funcionario'); return response()->json(['message'=>$motorista?'Motorista cadastrado com sucesso.':'Colaborador cadastrado com sucesso.','data'=>$this->quickPayload($record,'funcionario')],201);
            });
        } catch (\Illuminate\Validation\ValidationException $e) { throw $e; } catch (\Throwable $e) { Log::error('Erro no cadastro rápido da pesagem.', ['tipo'=>$tipo,'empresa_id'=>$this->empresa_id,'exception'=>$e]); return response()->json(['message'=>'Não foi possível concluir o cadastro. Tente novamente.'],500); }
    }
    private function salvarImagemCadastroRapido(Request $request, $record, string $tipo): void
    {
        if (!$request->hasFile('imagem') || !$request->file('imagem')->isValid()) return;
        $config = [
            'cliente' => ['imgs_clientes', 'imagem'], 'fornecedor' => ['imgs_fornecedores', 'imagem'],
            'veiculo' => ['imgs_veiculos', 'foto_veiculo'], 'funcionario' => ['imgs_funcionarios', 'foto_funcionario'],
        ][$tipo] ?? null;
        if (!$config) return;
        [$directory, $column] = $config;
        $path = public_path($directory);
        if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) throw new \RuntimeException('Não foi possível preparar o diretório da imagem.');
        $filename = Str::uuid()->toString().'.'.$request->file('imagem')->extension();
        $request->file('imagem')->move($path, $filename);
        $record->forceFill([$column => $filename])->save();
    }

    private function documentoValido(string $documento): bool
    {
        $d = preg_replace('/\D/', '', $documento);
        if (preg_match('/^(\d)\1+$/', $d)) return false;
        if (strlen($d) === 11) { for ($i = 9; $i < 11; $i++) { $sum = 0; for ($j = 0; $j < $i; $j++) $sum += $d[$j] * (($i + 1) - $j); $digit = (($sum * 10) % 11) % 10; if ((int) $d[$i] !== $digit) return false; } return true; }
        if (strlen($d) === 14) { foreach ([12,13] as $i) { $sum=0; $weight=$i===12?5:6; for($j=0;$j<$i;$j++){ $sum+=(int)$d[$j]*$weight; if(--$weight<2)$weight=9; } $digit=$sum%11<2?0:11-($sum%11); if((int)$d[$i]!==$digit)return false; } return true; }
        return false;
    }

    private function quickPayload($record, string $tipo): array
    { if($tipo==='veiculo')return ['id'=>$record->id,'text'=>$record->placa,'placa'=>$record->placa]; if($tipo==='funcionario')return ['id'=>$record->id,'text'=>$record->nome.' | '.$record->cpf,'nome'=>$record->nome,'status_motorista'=>$record->status_motorista]; return ['id'=>$record->id,'text'=>$record->razao_social,'razao_social'=>$record->razao_social,'nome_fantasia'=>$record->nome_fantasia,'cpf_cnpj'=>$record->cpf_cnpj,'telefone'=>$record->telefone,'cidade'=>optional($record->cidade)->info]; }

    /**
     * Concluir a pesagem.
     */
    public function concluir(Request $request, $id)
    {
        try {
            // Busca a pesagem com seus tickets
            $pesagem = Pesagem::with('tickets')->findOrFail($id);

            // Verifica se há tickets pendentes
            $ticketsPendentes = $pesagem->tickets->where('status', 'em andamento');
            if ($ticketsPendentes->count() > 0) {
                return response()->json(['error' => 'Finalize os tickets antes de concluir a pesagem.']);
            }

            // Cálculo do peso bruto
            $entradas = $pesagem->tickets->where('tipo', 'entrada')->sum('peso');
            $saidas = $pesagem->tickets->where('tipo', 'saida')->sum('peso');
            $avulsas = $pesagem->tickets->where('tipo', 'avulsa')->sum('peso');
            //$pesoBruto = abs($entradas - $saidas + $avulsas);
            $pesoBruto = abs($entradas + $avulsas);
            // Captura os dados antes da atualização para log
            $dadosAnteriores = $pesagem->toArray();

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
            $pesoFinal = max(0, $pesoBruto - $descontos - $saidas);

            // Atualiza a pesagem
            $pesagem->update([
                'view_public' => 1,
                'peso' => $pesoFinal,
                'peso_liquido_bruto' => $pesoBruto,
                'peso_final' => $pesoFinal,
                'status' => 'concluído',
            ]);

            // Captura os dados após a atualização para log
            $dadosDepois = $pesagem->toArray();

            // Atualiza todos os tickets como concluídos
            TicketPesagem::where('pesagem_id', $id)->update(['status' => 'concluído']);

            // Obtém a classe correta do modelo para log
            $modelInstance = is_string(Pesagem::class) ? app(Pesagem::class) : Pesagem::class;

            // 🔹 Registra log da conclusão da pesagem
            $this->logService->registrar('update', get_class($modelInstance), [
                'registro_id' => $pesagem->id,
                'dados_antes' => $dadosAnteriores,
                'dados_depois' => $dadosDepois,
            ]);

            $this->dispararMonitoramentoPesagem($pesagem->id, 'pesagem.finished');

            return response()->json(['success' => 'Pesagem concluída com sucesso!']);
        } catch (\Exception $e) {
            \Log::error('Erro ao concluir a pesagem', [
                'pesagemId' => $id,
                'exception' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Erro ao concluir a pesagem: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Listar todas as pesagens com filtros e paginação.
     */
    public function list(Request $request)
    {
        $query = Pesagem::with(['veiculo', 'tickets'])
            ->where('empresa_id', $this->empresa_id);

        // Busca incremental
        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function($q) use ($term) {
                $q->where('placa_veiculo', 'like', "%{$term}%")
                    ->orWhere('placa_carreta', 'like', "%{$term}%")
                    ->orWhere('motorista_nome', 'like', "%{$term}%")
                    ->orWhereHas('veiculo', function($qv) use ($term) {
                        $qv->where('placa', 'like', "%{$term}%")
                            ->orWhere('chassi', 'like', "%{$term}%");
                    })
                    ->orWhereHas('cliente', function($qc) use ($term) {
                        $qc->where('razao_social',   'like', "%{$term}%")
                            ->orWhere('nome_fantasia', 'like', "%{$term}%");
                    })
                    ->orWhereHas('fornecedor', function($qf) use ($term) {
                        $qf->where('razao_social',   'like', "%{$term}%")
                            ->orWhere('nome_fantasia', 'like', "%{$term}%");
                    });
            });
        }

        // Filtros existentes
        if ($request->filled('data_inicial')) {
            $query->where('created_at', '>=', $request->data_inicial);
        }
        if ($request->filled('data_final')) {
            $query->where('created_at', '<=', $request->data_final);
        }
        if ($request->filled('veiculo_id')) {
            $query->where('veiculo_id', $request->veiculo_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Paginação
        $perPage = 15;
        $pesagens = $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->appends($request->query());

        // Dropdowns
        $produtos     = Produto::where('empresa_id', $this->empresa_id)->get();
        $clientes     = Cliente::where('empresa_id', $this->empresa_id)->get();
        $fornecedores = Fornecedor::where('empresa_id', $this->empresa_id)->get();
        $veiculos     = Veiculo::where('empresa_id', $this->empresa_id)->get();
        $motoristas   = Funcionario::where('empresa_id', $this->empresa_id)
            ->where('status_motorista','Ativo')->get();
        $balancas     = BalancaConfig::where('empresa_id', $this->empresa_id)
            ->where('ativo', true)
            ->where('integrador', 'adp')
            ->get();
        $tickets      = TicketPesagem::where('empresa_id', $this->empresa_id)
            ->with('produto')->get();

        $usuarioLogado = \App\Models\Usuario::find(session('user_logged')['id']);
        $configNota = ConfigNota::where('empresa_id', $this->empresa_id)->first();
        $hasOtp = $usuarioLogado ? $usuarioLogado->hasOtp() : false;

        // **Aqui passamos de volta cada filtro usado**
        return view($this->listView, [
            'title'        => $this->formTitle,
            'pesagens'     => $pesagens,
            'veiculos'     => $veiculos,
            'motoristas'   => $motoristas,
            'clientes'     => $clientes,
            'fornecedores' => $fornecedores,
            'produtos'     => $produtos,
            'balancas'     => $balancas,
            'tickets'      => $tickets,
            'hasOtp'       => $hasOtp,
            'configNota'    => $configNota,
            'balancaPadraoUsuarioId' => $usuarioLogado->balanca_padrao_id ?? null,

            // filtros
            'search'      => $request->input('search'),
            'dataInicial' => $request->input('data_inicial'),
            'dataFinal'   => $request->input('data_final'),
            'veiculo_id'  => $request->input('veiculo_id'),
            'status'      => $request->input('status'),
        ]);
    }

    public function list______________(Request $request)
    {
        // 1) Consulta principal com filtros aplicados
        $query = Pesagem::with(['veiculo', 'tickets'])
            ->where('empresa_id', $this->empresa_id);

        // 2) Busca incremental por texto livre (veículo, placa, motorista, cliente, fornecedor)
        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function($q) use ($term) {
                $q->where('placa_veiculo', 'like', "%{$term}%")
                    ->orWhere('placa_carreta', 'like', "%{$term}%")
                    ->orWhere('motorista_nome', 'like', "%{$term}%")
                    // relacionamentos
                    ->orWhereHas('veiculo', function($qv) use ($term) {
                        $qv->where('placa', 'like', "%{$term}%")
                            ->orWhere('chassi', 'like', "%{$term}%");
                    })
                    ->orWhereHas('cliente', function($qc) use ($term) {
                        $qc->where('razao_social',   'like', "%{$term}%")
                            ->orWhere('nome_fantasia', 'like', "%{$term}%");
                    })
                    ->orWhereHas('fornecedor', function($qf) use ($term) {
                        $qf->where('razao_social',   'like', "%{$term}%")
                            ->orWhere('nome_fantasia', 'like', "%{$term}%");
                    });
            });
        }

        // 3) Filtros opcionais já existentes
        if ($request->filled('data_inicial')) {
            $query->where('created_at', '>=', $request->data_inicial);
        }
        if ($request->filled('data_final')) {
            $query->where('created_at', '<=', $request->data_final);
        }
        if ($request->filled('veiculo_id')) {
            $query->where('veiculo_id', $request->veiculo_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 4) Paginação (mantendo todos os parâmetros, inclusive o novo 'search')
        $perPage = 15;
        $pesagens = $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->appends($request->query());

        // 5) Demais consultas para dropdowns e seleções (sem alterações)
        $produtos     = Produto::where('empresa_id', $this->empresa_id)->get();
        $clientes     = Cliente::where('empresa_id', $this->empresa_id)->get();
        $fornecedores = Fornecedor::where('empresa_id', $this->empresa_id)->get();
        $veiculos     = Veiculo::where('empresa_id', $this->empresa_id)->get();
        $motoristas   = Funcionario::where('empresa_id', $this->empresa_id)
            ->where('status_motorista', 'Ativo')
            ->get();
        $balancas     = BalancaConfig::where('empresa_id', $this->empresa_id)
            ->where('ativo', true)
            ->where('integrador', 'adp')
            ->get();
        $tickets      = TicketPesagem::where('empresa_id', $this->empresa_id)
            ->with('produto')
            ->get();

        // 6) Retorna a view com todos os dados — adicionei apenas 'search' para manter o valor no input
        return view($this->listView, [
            'title'       => $this->formTitle,
            'pesagens'    => $pesagens,
            'veiculos'    => $veiculos,
            'motoristas'  => $motoristas,
            'clientes'    => $clientes,
            'fornecedores'=> $fornecedores,
            'produtos'    => $produtos,
            'balancas'    => $balancas,
            'tickets'     => $tickets,
            'search'      => $request->input('search'),   // <-- aqui
        ]);
    }

    public function list__01072025_bkp(Request $request)
    {
        // Consulta principal com filtros aplicados
        $query = Pesagem::with(['veiculo', 'tickets'])
            ->where('empresa_id', $this->empresa_id);

        // Filtros opcionais
        if ($request->filled('data_inicial')) {
            $query->where('created_at', '>=', $request->data_inicial);
        }

        if ($request->filled('data_final')) {
            $query->where('created_at', '<=', $request->data_final);
        }

        if ($request->filled('veiculo_id')) {
            $query->where('veiculo_id', $request->veiculo_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Ordena os registros por data (mais recentes primeiro)
        //$pesagens = $query->orderBy('created_at', 'desc')->get(); // Remove paginação (paginate)
        // Em vez de ->get(), pagine:
        $perPage = 15;
        $pesagens = $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->appends($request->query());

        // Demais consultas para dropdowns e seleções
        $produtos = Produto::where('empresa_id', $this->empresa_id)->get();
        $clientes = Cliente::where('empresa_id', $this->empresa_id)->get();
        $fornecedores = Fornecedor::where('empresa_id', $this->empresa_id)->get();
        $veiculos = Veiculo::where('empresa_id', $this->empresa_id)->get();
        $motoristas = Funcionario::where('empresa_id', $this->empresa_id)
            ->where('status_motorista', 'Ativo')
            ->get();

        // Carrega todas as balanças para seleção
        $balancas = BalancaConfig::where('empresa_id', $this->empresa_id)
            ->where('ativo', true)
            ->where('integrador', 'adp')
            ->get();

        $tickets = TicketPesagem::where('empresa_id', $this->empresa_id)
            ->with('produto')
            ->get();

        // Retorna a view com os dados
        return view($this->listView, [
            'title' => $this->formTitle,
            'pesagens' => $pesagens, // Atualizado
            'veiculos' => $veiculos,
            'motoristas' => $motoristas,
            'clientes' => $clientes,
            'fornecedores' => $fornecedores,
            'produtos' => $produtos,
            'balancas' => $balancas,
            'tickets' => $tickets,
        ]);
    }

    public function showBalanca(Request $request)
    {
        // Carrega todas as balanças para seleção
        $balancas = BalancaConfig::where('empresa_id', $this->empresa_id)
            ->where('ativo', true)
            ->where('integrador', 'adp')
            ->get();

        // Passa as balanças para a view
        return view($this->balancaView, [
            'balancas' => $balancas,
            'title' => $this->balancaTitle,
        ]);
    }

    /**
     * Registro de uma nova pesagem.
     */
    public function register($id = null)
    {
        $transportadoras = Transportadora::where('empresa_id', $this->empresa_id)->get();
        $produtos = Produto::where('empresa_id', $this->empresa_id)->get();
        $clientes = Cliente::where('empresa_id', $this->empresa_id)->get();
        $fornecedores = Fornecedor::where('empresa_id', $this->empresa_id)->get();
        $veiculos = Veiculo::where('empresa_id', $this->empresa_id)->get();
        $motoristas = Funcionario::where('empresa_id', $this->empresa_id)
            ->where('status_motorista', 'Ativo')
            ->get();

        $title = $id ? "Editar Pesagem #{$id}" : 'Nova Pesagem';

        if ($id) {
            $pesagem = Pesagem::with('tickets')->findOrFail($id);
            $tickets = $pesagem->tickets ?? collect(); // Força a coleção vazia se não existir tickets
            $action = route('pesagens.update', $id);
        } else {
            $pesagem = null;
            $tickets = collect(); // Garante sempre uma coleção vazia
            $action = route('pesagens.save');
        }


        return view($this->registerView, compact(
            'veiculos', 'motoristas', 'transportadoras', 'produtos', 'tickets', 'pesagem', 'action', 'title'
        ));
    }

    public function update(Request $request, $id)
    {
        \Log::info('Método HTTP recebido:', ['method' => $request->method()]);
        \Log::info('URL da requisição:', ['url' => $request->fullUrl()]);

        $request->validate($this->rules(), $this->messages());

        try {
            $data = $request->all();
            $data['empresa_id'] = $this->empresa_id;
            $data['usuario_id'] = $this->usuario_id;

            // Garante que checkboxes booleanos sejam tratados corretamente
            $data['danificado'] = $request->has('danificado') ? 1 : 0;
            $data['quebrado'] = $request->has('quebrado') ? 1 : 0;
            $data['esverdeado'] = $request->has('esverdeado') ? 1 : 0;
            $data['ardido'] = $request->has('ardido') ? 1 : 0;
            $data['secagem'] = $request->has('secagem') ? 1 : 0;

            // Garante que os valores dos campos de desconto sejam enviados
            $data['danificado_desconto'] = $request->input('danificado_desconto', 0.00);
            $data['quebrado_desconto'] = $request->input('quebrado_desconto', 0.00);
            $data['esverdeado_desconto'] = $request->input('esverdeado_desconto', 0.00);
            $data['ardido_desconto'] = $request->input('ardido_desconto', 0.00);
            $data['secagem_desconto'] = $request->input('secagem_desconto', 0.00);

            //$data['motorista'] = $request->input('motorista') ?? null;

            // Ajusta cliente e fornecedor conforme o tipo
            if ($data['tipo'] === 'compra') {
                $data['cliente_id'] = null;
                if (empty($data['fornecedor_id'])) {
                    return redirect()->back()->withInput()->withErrors(['fornecedor_id' => 'O fornecedor é obrigatório para o tipo compra.']);
                }
            } elseif ($data['tipo'] === 'venda') {
                $data['fornecedor_id'] = null;
                if (empty($data['cliente_id'])) {
                    return redirect()->back()->withInput()->withErrors(['cliente_id' => 'O cliente é obrigatório para o tipo venda.']);
                }
            }

            // Busca os dados antes da alteração para o log
            $pesagem = Pesagem::findOrFail($id);
            $dadosAnteriores = $pesagem->toArray();

            // Atualiza os dados
            $pesagem->update($data);

            // Obtém a classe correta do modelo
            $modelInstance = is_string(Pesagem::class) ? app(Pesagem::class) : Pesagem::class;

            // 🔹 Registra o log da operação
            $this->logService->registrar('update', get_class($modelInstance), [
                'registro_id' => $pesagem->id,
                'dados_antes' => $dadosAnteriores,
                'dados_depois' => $pesagem->toArray(),
            ]);

            $this->dispararMonitoramentoPesagem($pesagem->id, 'pesagem.updated');

            return redirect($this->redirectPage)->with('success', 'Pesagem atualizada com sucesso!');
        } catch (\Exception $e) {
            \Log::error("Erro ao atualizar pesagem: " . $e->getMessage());
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function delete($id)
    {
        try {
            // 🔹 Busca os dados antes da exclusão para o log
            $pesagem = Pesagem::findOrFail($id);
            $dadosAnteriores = $pesagem->toArray();

            // Exclui a pesagem
            $pesagem->delete();

            // Obtém a classe correta do modelo
            $modelInstance = is_string(Pesagem::class) ? app(Pesagem::class) : Pesagem::class;

            // 🔹 Registra o log da exclusão
            $this->logService->registrar('delete', get_class($modelInstance), [
                'registro_id' => $id,
                'dados_antes' => $dadosAnteriores,
                'dados_depois' => null, // Exclusão, então não há dados após
            ]);

            return response()->json(['success' => 'Pesagem excluída com sucesso!']);
        } catch (\Exception $e) {
            \Log::error("Erro ao excluir pesagem: " . $e->getMessage());
            return response()->json(['error' => 'Erro ao excluir pesagem: ' . $e->getMessage()], 500);
        }
    }

    public function edit($id)
    {
        try {
            // Busca os dados da pesagem com os relacionamentos necessários
            $pesagem = Pesagem::with(['veiculo', 'tickets', 'motorista', 'cliente', 'fornecedor'])
                ->where('empresa_id', $this->empresa_id) // Filtra pelo tenant
                ->findOrFail($id);

            // Obtém a classe correta do modelo
            $modelInstance = is_string(Pesagem::class) ? app(Pesagem::class) : Pesagem::class;

            // 🔹 Registra log da edição
            $this->logService->registrar('edit', get_class($modelInstance), [
                'registro_id' => $id,
                'dados_anteriores' => $pesagem->toArray(),
            ]);

            // Retorna os dados diretamente em JSON
            return response()->json($pesagem);
        } catch (\Exception $e) {
            // Reaproveita o tratamento de erros do BaseController
            return parent::edit($id);
        }
    }

    public function getTotais($id)
    {
        // Busca a pesagem com os relacionamentos necessários
        $pesagem = Pesagem::with(['veiculo', 'tickets']) // Carrega os relacionamentos
        ->where('empresa_id', $this->empresa_id) // Filtra pela empresa
        ->findOrFail($id); // Busca ou retorna erro 404

        // Retorna os dados formatados em JSON
        return response()->json([
            'peso' => (float) $pesagem->peso,                             // Converte para número
            'peso_liquido_bruto' => (float) $pesagem->peso_liquido_bruto, // Converte para número
            'peso_final' => (float) $pesagem->peso_final,                 // Converte para número
            'status' => $pesagem->status,                                 // Status da pesagem
            'veiculo' => $pesagem->veiculo->placa ?? 'N/A',               // Placa do veículo
            'tickets_count' => $pesagem->tickets->count()                 // Número de tickets associados
        ]);
    }

    public function getDados($id)
    {
        // Busca a pesagem com os relacionamentos necessários
        $pesagem = Pesagem::with(['veiculo', 'tickets']) // Inclui os relacionamentos
        ->where('empresa_id', $this->empresa_id) // Filtra pela empresa
        ->findOrFail($id); // Busca o ID especificado ou falha

        // Retorna os dados formatados em JSON
        return response()->json([
            'tipo' => $pesagem->tipo,
            'cliente_id' => $pesagem->cliente_id,
            'cliente_razao_social' => $pesagem->cliente->razao_social ?? 'N/A',
            'fornecedor_id' => $pesagem->fornecedor_id,
            'fornecedor_razao_social' => $pesagem->fornecedor->razao_social ?? 'N/A',
            'motorista_id' => $pesagem->motorista_id,
            'motorista_nome' => $pesagem->motorista ?? 'N/A',
            'peso' => (float) $pesagem->peso,
            'peso_liquido_bruto' => (float) $pesagem->peso_liquido_bruto, // Converte para número
            'peso_final' => (float) $pesagem->peso_final,                 // Converte para número
            'status' => $pesagem->status,                                 // Status
            'veiculo' => $pesagem->veiculo->placa ?? 'N/A',               // Placa do veículo
            'tickets_count' => $pesagem->tickets->count()                 // Número de tickets associados
        ]);
    }

    public function searchVeiculo(Request $request)
    {
        $term = $request->get('term', '');

        $veiculos = Veiculo::with(['marcaVeiculo', 'modeloVeiculo', 'motorista'])
            ->where('empresa_id', $this->empresa_id)
            ->when($term, function ($query, $term) {
                $query->where('placa', 'like', "%{$term}%")
                    ->orWhere('chassi', 'like', "%{$term}%")
                    ->orWhereHas('marcaVeiculo', function ($q) use ($term) {
                        $q->where('descricao', 'like', "%{$term}%");
                    })
                    ->orWhereHas('modeloVeiculo', function ($q) use ($term) {
                        $q->where('descricao', 'like', "%{$term}%");
                    });
            })
            ->limit(10)
            ->get([
                'id',
                'placa',
                'marca_fk',
                'modelo_fk',
                'motorista_id',
                'quilometragem',
                'foto_veiculo',
            ]);

        $results = $veiculos->map(function ($veiculo) {
            return [
                'id' => $veiculo->id,
                'text' => "{$veiculo->placa} | {$veiculo->marcaDescricao} | {$veiculo->modeloDescricao} | {$veiculo->motoristaNome}",
                'placa' => $veiculo->placa,
                'marcaDescricao' => $veiculo->marcaDescricao,
                'modeloDescricao' => $veiculo->modeloDescricao,
                'motoristaNome' => $veiculo->motoristaNome,
                'imgApp' => $veiculo->imgApp,
                'quilometragem' => "{$veiculo->quilometragem} km",
            ];
        });

        return response()->json($results);
    }

    public function searchCliente(Request $request)
    {
        $term = $request->get('term', '');

        $clientes = Cliente::where('empresa_id', $this->empresa_id)
            ->where(function ($query) use ($term) {
                $query->where('razao_social', 'like', "%{$term}%")
                    ->orWhere('cpf_cnpj', 'like', "%{$term}%")
                    ->orWhere('telefone', 'like', "%{$term}%")
                    ->orWhere('cidade_id', 'like', "%{$term}%")
                    ->orWhere('nome_fantasia', 'like', "%{$term}%");
            })
            ->limit(10)
            ->get([
                'id',
                'razao_social',
                'nome_fantasia',
                'cpf_cnpj',
                'telefone',
                'cidade_id',
                'imagem',
            ]);

        $results = $clientes->map(function ($cliente) {
            return [
                'id' => $cliente->id,
                'text' => "{$cliente->razao_social} | {$cliente->cpf_cnpj}",
                'razao_social' => $cliente->razao_social ?? 'N/A',
                'nome_fantasia' => $cliente->nome_fantasia ?? 'N/A',
                'cpf_cnpj' => $cliente->cpf_cnpj ?? 'N/A',
                'telefone' => $cliente->telefone ?? 'N/A',
                'cidade' => $cliente->cidade->nome ?? 'N/A',
                'imgApp' => $cliente->imagem
                    ? env("PATH_URL") . "/imgs_clientes/{$cliente->imagem}"
                    : env("PATH_URL") . "/imgs/no_clientes.png",
            ];
        });

        return response()->json(['results' => $results]);
    }

    public function searchFornecedor(Request $request)
    {
        $term = $request->get('term', '');

        $fornecedores = Fornecedor::where('empresa_id', $this->empresa_id)
            ->where(function ($query) use ($term) {
                $query->where('razao_social', 'like', "%{$term}%")
                    ->orWhere('nome_fantasia', 'like', "%{$term}%")
                    ->orWhere('telefone', 'like', "%{$term}%")
                    ->orWhere('cidade_id', 'like', "%{$term}%")
                    ->orWhere('cpf_cnpj', 'like', "%{$term}%");
            })
            ->limit(10)
            ->get([
                'id',
                'razao_social',
                'nome_fantasia',
                'cpf_cnpj',
                'telefone',
                'cidade_id',
                'imagem',
            ]);

        $results = $fornecedores->map(function ($fornecedor) {
            return [
                'id' => $fornecedor->id,
                'text' => "{$fornecedor->razao_social} | {$fornecedor->cpf_cnpj}",
                'razao_social' => $fornecedor->razao_social ?? 'N/A',
                'nome_fantasia' => $fornecedor->nome_fantasia ?? 'N/A',
                'cpf_cnpj' => $fornecedor->cpf_cnpj ?? 'N/A',
                'telefone' => $fornecedor->telefone ?? 'N/A',
                'cidade' => $fornecedor->cidade->nome ?? 'N/A',
                'imgApp' => $fornecedor->imagem
                    ? env("PATH_URL") . "/imgs_fornecedores/{$fornecedor->imagem}"
                    : env("PATH_URL") . "/imgs/no_fornecedores.png",
            ];
        });

        return response()->json($results);
    }

    public function searchMotorista(Request $request)
    {
        $term = $request->get('term', '');

        $motoristas = Funcionario::where('empresa_id', $this->empresa_id)
            ->where('status_motorista', 'Ativo')
            ->where(function ($query) use ($term) {
                $query->where('nome', 'like', "%{$term}%")
                    ->orWhere('cpf', 'like', "%{$term}%");
            })
            ->limit(10)
            ->get([
                'id',
                'nome',
                'cpf',
                'cnh',
                'foto_funcionario',
            ]);

        $results = $motoristas->map(function ($motorista) {
            return [
                'id' => $motorista->id,
                'text' => "{$motorista->nome} | {$motorista->cpf}",
                'nome' => $motorista->nome,
                'cpf' => $motorista->cpf,
                'cnh' => $motorista->cnh,
                'categoria_cnh' => $motorista->categoria_cnh,
                'vencimento_cnh' => $motorista->vencimento_cnh,
                'imgApp' => $motorista->foto_funcionario
                    ? asset("imgs_funcionarios/{$motorista->foto_funcionario}")
                    : asset("imgs/no_funcionarios.png"),
            ];
        });

        return response()->json(['results' => $results]);
    }

    public function searchProduto(Request $request)
    {
        $term = $request->get('term', '');

        $produtos = Produto::where('empresa_id', $this->empresa_id)
            ->where('controla_pesagem', true)
            ->where(function ($query) use ($term) {
                $query->where('nome', 'like', "%{$term}%")
                    ->orWhere('referencia', 'like', "%{$term}%")
                    ->orWhere('codBarras', 'like', "%{$term}%");
            })
            ->limit(10)
            ->get([
                'id', 'nome', 'categoria_id', 'cor', 'valor_venda', 'NCM', 'CST_CSOSN',
                'CST_PIS', 'CST_COFINS', 'CST_IPI', 'unidade_compra', 'unidade_venda',
                'composto', 'codBarras', 'conversao_unitaria', 'valor_livre', 'perc_icms',
                'perc_pis', 'perc_cofins', 'perc_ipi', 'CFOP_saida_estadual',
                'CFOP_saida_inter_estadual', 'codigo_anp', 'descricao_anp', 'perc_iss',
                'cListServ', 'imagem', 'alerta_vencimento', 'valor_compra', 'gerenciar_estoque',
                'estoque_minimo', 'referencia', 'empresa_id', 'largura', 'comprimento',
                'altura', 'peso_liquido', 'peso_bruto', 'limite_maximo_desconto', 'pRedBC',
                'cBenef', 'percentual_lucro', 'CST_CSOSN_EXP', 'referencia_grade', 'grade',
                'str_grade', 'perc_glp', 'perc_gnn', 'perc_gni', 'valor_partida',
                'unidade_tributavel', 'quantidade_tributavel', 'perc_icms_interestadual',
                'perc_icms_interno', 'perc_fcp_interestadual', 'inativo', 'CEST',
                'sub_categoria_id', 'marca_id', 'referencia_balanca', 'renavam', 'placa',
                'chassi', 'combustivel', 'ano_modelo', 'cor_veiculo', 'reajuste_automatico',
                'valor_locacao', 'lote', 'vencimento', 'origem', 'tipo_dimensao', 'perc_comissao',
                'acrescimo_perca', 'nuvemshop_id', 'info_tecnica_composto', 'CST_CSOSN_entrada',
                'CST_PIS_entrada', 'CST_COFINS_entrada', 'CST_IPI_entrada', 'CFOP_entrada_estadual',
                'CFOP_entrada_inter_estadual', 'custo_assessor', 'envia_controle_pedidos',
                'cenq_ipi', 'tela_pedido_id', 'ifood_id', 'modBCST', 'modBC', 'pICMSST',
                'locais', 'perc_frete', 'perc_outros', 'perc_mlv', 'perc_mva', 'valor_comissao',
                'adRemICMSRet', 'tipo_servico', 'pBio', 'indImport', 'cUFOrig', 'pOrig',
                'peso', 'info_adicional_item', 'observacao'
            ]);

        $results = $produtos->map(function ($produto) {
            return [
                'id' => $produto->id,
                'nome' => $produto->nome,
                'text' => "{$produto->referencia} | {$produto->nome} | {$produto->codBarras}",
                'valor_venda' => number_format($produto->valor_venda, 2, ',', '.'),
                'referencia' => $produto->referencia,
                'codBarras' => $produto->codBarras,
                'categoria' => $produto->categoria->descricao ?? 'Sem categoria',
                'imagem' => $produto->imagem
                    ? asset("imgs_produtos/{$produto->imagem}")
                    : asset("imgs/no_image.png"),
                'dados_completos' => $produto->only([
                    'cor', 'NCM', 'CST_CSOSN', 'CST_PIS', 'CST_COFINS', 'CST_IPI',
                    'unidade_compra', 'unidade_venda', 'composto', 'conversao_unitaria',
                    'valor_livre', 'perc_icms', 'perc_pis', 'perc_cofins', 'perc_ipi',
                    'CFOP_saida_estadual', 'CFOP_saida_inter_estadual', 'codigo_anp',
                    'descricao_anp', 'perc_iss', 'cListServ', 'alerta_vencimento',
                    'valor_compra', 'gerenciar_estoque', 'estoque_minimo', 'referencia',
                    'empresa_id', 'largura', 'comprimento', 'altura', 'peso_liquido',
                    'peso_bruto', 'limite_maximo_desconto', 'pRedBC', 'cBenef',
                    'percentual_lucro', 'CST_CSOSN_EXP', 'referencia_grade', 'grade',
                    'str_grade', 'perc_glp', 'perc_gnn', 'perc_gni', 'valor_partida',
                    'unidade_tributavel', 'quantidade_tributavel', 'perc_icms_interestadual',
                    'perc_icms_interno', 'perc_fcp_interestadual', 'inativo', 'CEST',
                    'sub_categoria_id', 'marca_id', 'referencia_balanca', 'renavam', 'placa',
                    'chassi', 'combustivel', 'ano_modelo', 'cor_veiculo', 'reajuste_automatico',
                    'valor_locacao', 'lote', 'vencimento', 'origem', 'tipo_dimensao',
                    'perc_comissao', 'acrescimo_perca', 'nuvemshop_id', 'info_tecnica_composto',
                    'CST_CSOSN_entrada', 'CST_PIS_entrada', 'CST_COFINS_entrada', 'CST_IPI_entrada',
                    'CFOP_entrada_estadual', 'CFOP_entrada_inter_estadual', 'custo_assessor',
                    'envia_controle_pedidos', 'cenq_ipi', 'tela_pedido_id', 'ifood_id',
                    'modBCST', 'modBC', 'pICMSST', 'locais', 'perc_frete', 'perc_outros',
                    'perc_mlv', 'perc_mva', 'valor_comissao', 'adRemICMSRet', 'tipo_servico',
                    'pBio', 'indImport', 'cUFOrig', 'pOrig', 'peso', 'info_adicional_item',
                    'observacao'
                ]),
            ];
        });

        return response()->json(['results' => $results]);
    }

    public function enviarRelatorioWhatsApp(Request $request, $id)
    {

        try {
            // Obtém o CSRF token da sessão para uso como identificador único
            $csrfToken = md5(uniqid(rand(), true)) . session()->token();

            $pesagem = Pesagem::with(['veiculo', 'tickets', 'motorista'])
                ->where('empresa_id', $this->empresa_id)
                ->findOrFail($id);

            $mensagemPadrao = "Ticket de Pesagem\n";
            $mensagemPadrao .= "Referente Pesagem: #{$pesagem->id}\n";
            $mensagemPadrao .= "Token: {$pesagem->token}\n";
            $mensagemPadrao .= "Data/Hora: " . ($pesagem->created_at ? $pesagem->created_at->format('d/m/Y H:i') : 'N/A') . "\n";
            $mensagemPadrao .= "Veículo Placa: " . ($pesagem->placa_veiculo ?? 'N/A') . "\n";
            $mensagemPadrao .= "Carreta Placa: " . ($pesagem->placa_carreta ?? 'N/A') . "\n";
            $mensagemPadrao .= "Motorista: " . ($pesagem->motorista_nome ?? 'N/A') . "\n";

            $mensagemAdicional = $request->input('texto', '');
            $mensagemFinal = $mensagemPadrao . (!empty($mensagemAdicional) ? "\n\n{$mensagemAdicional}" : '');

            $arquivos = [];
            $relatorioController = app(RelatorioController::class);

            if ($request->has('relatorio_80mm')) {
                $relatorio80mm = $relatorioController->imprimirPesagem80mmWhats($id, $this->empresa_id);
                $path80mm = public_path("tmp_files/RL{$csrfToken}80MM.pdf");
                safe_file_put_contents($path80mm, $relatorio80mm->getContent());
                if (file_exists($path80mm)) {
                    $arquivos[] = $path80mm; // Usa caminho absoluto
                } else {
                    \Log::error("Arquivo 80mm não encontrado: {$path80mm}");
                }
            }

            if ($request->has('relatorio_a4')) {
                $relatorioA4 = $relatorioController->imprimirPesagemA4Whats($id, $this->empresa_id);
                $pathA4 = public_path("tmp_files/RL{$csrfToken}A4.pdf");
                safe_file_put_contents($pathA4, $relatorioA4->getContent());
                if (file_exists($pathA4)) {
                    $arquivos[] = $pathA4; // Usa caminho absoluto
                } else {
                    \Log::error("Arquivo A4 não encontrado: {$pathA4}");
                }
            }

            // Chama o método para enviar WhatsApp
            $resultado = $this->enviarWhatsApp($request, $arquivos, $mensagemFinal);

            if ($resultado['success']) {
                \Log::info('Mensagem enviada com sucesso', [
                    'numero' => $request->celular,
                    'arquivos' => $arquivos,
                ]);
                session()->flash('mensagem_sucesso', $resultado['message']);
                \Log::info('Mensagem sucesso gravada na sessão', session()->all());
                return redirect()->back();
            } else {
                \Log::error('Erro ao enviar mensagem', [
                    'numero' => $request->celular,
                    'arquivos' => $arquivos,
                    'erro' => $resultado['message'],
                ]);
                session()->flash('mensagem_erro', $resultado['message']);
                \Log::info('Mensagem erro gravada na sessão', session()->all());
                return redirect()->back();
            }
        } catch (\Exception $e) {
            \Log::error('Erro ao enviar relatório via WhatsApp', [
                'exception' => $e->getMessage(),
                'arquivos' => $arquivos ?? [],
                'mensagem' => $mensagemFinal ?? '',
            ]);
            session()->flash('mensagem_erro', 'Erro ao enviar o relatório via WhatsApp: ' . $e->getMessage());
            \Log::info('Mensagem exception gravada na sessão', session()->all());
            return redirect()->back();
        }
    }

    public function togglePublicacao($id)
    {
        try {
            // Encontra a pesagem pelo ID com relacionamentos
            $pesagem = Pesagem::with(['veiculo', 'tickets', 'motorista'])
                ->where('empresa_id', $this->empresa_id)  // Garantir que a pesagem pertence à empresa
                ->findOrFail($id);

            // Captura os dados antes da alteração para log
            $dadosAnteriores = $pesagem->toArray();

            // Alterna o valor de view_public (0 para 1 e vice-versa)
            $pesagem->view_public = $pesagem->view_public == 1 ? 0 : 1;
            $pesagem->save(); // Salva a alteração no banco

            // Captura os dados após a alteração para log
            $dadosDepois = $pesagem->toArray();

            // Obtém a classe correta do modelo para log
            $modelInstance = is_string(Pesagem::class) ? app(Pesagem::class) : Pesagem::class;

            // 🔹 Registra log da alteração de visibilidade
            $this->logService->registrar('update', get_class($modelInstance), [
                'registro_id' => $pesagem->id,
                'dados_antes' => $dadosAnteriores,
                'dados_depois' => $dadosDepois,
            ]);

            // Monta a mensagem de sucesso com o token e ID da pesagem
            $mensagemSucesso = 'Visibilidade da pesagem alterada com sucesso. ';
            $mensagemSucesso .= 'Pesagem Token ' . $pesagem->token . ' ID ' . $pesagem->id;
            $mensagemSucesso .= $pesagem->view_public == 1 ? ' agora visível online através do QRCode.' : ' agora indisponível online através do QRCode.';

            // Adiciona a mensagem de sucesso na sessão
            session()->flash('mensagem_sucesso', $mensagemSucesso);

            return response()->json(['success' => true]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('Pesagem não encontrada', [
                'id' => $id,
                'empresa_id' => $this->empresa_id,
                'exception' => $e->getMessage(),
            ]);

            // Mensagem de erro em caso de pesagem não encontrada
            session()->flash('mensagem_erro', "Pesagem Token {$id} não encontrada.");

            return response()->json(['success' => false]);

        } catch (\Exception $e) {
            \Log::error('Erro ao alterar visibilidade da pesagem', [
                'id' => $id,
                'empresa_id' => $this->empresa_id,
                'exception' => $e->getMessage(),
            ]);

            // Mensagem de erro genérico
            session()->flash('mensagem_erro', "Erro ao alterar a visibilidade da pesagem Token {$id} ID {$pesagem->id}. Tente novamente mais tarde.");

            return response()->json(['success' => false]);
        }
    }

    public function gerarQRCode($token)
    {
        try {
            //$url = env('URL_PESAGEM_TOKEN') . '/getTicket/withToken/relPrn80mm/' . $token;
            $url = url('/getTicket/withToken/relPrn80mm/' . $token);

            // Configura o renderer para usar o backend de SVG
            $renderer = new \BaconQrCode\Renderer\ImageRenderer(
                new \BaconQrCode\Renderer\RendererStyle\RendererStyle(200), // Define o tamanho do QR Code
                new \BaconQrCode\Renderer\Image\SvgImageBackEnd() // Usa SVG como backend
            );

            $writer = new \BaconQrCode\Writer($renderer);

            // Gera o QR Code como string SVG
            $qrcode = $writer->writeString($url);

            // Retorna o QR Code como base64 para exibição
            return response()->json([
                'success' => true,
                'qrCodeBase64' => 'data:image/svg+xml;base64,' . base64_encode($qrcode), // Base64 do SVG
                'url' => $url,
            ]);
        } catch (\Exception $e) {
            // Log do erro e mensagem flash
            \Log::error('Erro ao gerar o QRCode', ['token' => $token, 'exception' => $e->getMessage()]);
            session()->flash('mensagem_erro', "Erro ao gerar o QRCode para o Token {$token}. Tente novamente mais tarde.");
            return response()->json(['success' => false], 500);
        }
    }

    public function criarVendaDePesagem($pesagemId)
    {
        try {
            // Recuperar a pesagem
            $pesagem = Pesagem::with(['cliente', 'tickets', 'veiculo', 'motorista'])
                ->where('empresa_id', $this->empresa_id)
                ->findOrFail($pesagemId);

            // Verificar se já existe uma venda associada
            if ($pesagem->venda_id) {
                throw new \Exception('Já existe uma venda associada a esta pesagem.');
            }

            // Validar se a pesagem é do tipo "venda"
            if ($pesagem->tipo !== 'venda') {
                throw new \Exception('Apenas pesagens do tipo "venda" podem gerar uma venda.');
            }

            // Buscar cliente
            if (!$pesagem->cliente) {
                throw new \Exception('Não foi encontrado um cliente associado à pesagem.');
            }

            // Recuperar o produto do ticket associado
            $ticket = $pesagem->tickets->first();
            if (!$ticket || !$ticket->produto_id) {
                throw new \Exception('Nenhum produto foi associado à pesagem.');
            }

            $produto = Produto::findOrFail($ticket->produto_id);

            // Captura os dados antes da criação para log
            $dadosAnteriores = $pesagem->toArray();

            // Criar a venda com status "DISPONÍVEL"
            $venda = Venda::create([
                'cliente_id'    => $pesagem->cliente_id,
                'usuario_id'    => $pesagem->usuario_id,
                'valor_total'   => 0, // Atualizado posteriormente
                'desconto'      => 0,
                'acrescimo'     => 0,
                'estado'        => 'DISPONIVEL',
                'empresa_id'    => $this->empresa_id,
                'observacao'    => 'Venda gerada automaticamente a partir da pesagem #' . $pesagem->id,
                'natureza_id'   => 1,   // Substitua pelo ID da natureza padrão
            ]);

            $configNota = ConfigNota::where('empresa_id', $this->empresa_id)->first();
            $usarValoresTicket = (bool) ($configNota->usar_valores_ticket_pesagem ?? false);

            // —————————————————————————————————————————
            // Agrupar tickets por produto (usa produto_referenciado_id se houver)
            // e calcular peso líquido neto (entrada + avulsa – saída)
            // —————————————————————————————————————————
            $grupos = [];
            foreach ($pesagem->tickets as $t) {
                $pOrig   = Produto::findOrFail($t->produto_id);
                $pId     = $pOrig->produto_referenciado_id ?: $pOrig->id;
                $grupos[$pId][] = $t;
            }

            foreach ($grupos as $prodId => $ticketsGrupo) {
                $entrada = $saida = $avulsa = 0;
                foreach ($ticketsGrupo as $t) {
                    $pesoLiq = max(0, $t->peso - $t->peso_bag);
                    if ($t->tipo === 'entrada') {
                        $entrada += $pesoLiq;
                    } elseif ($t->tipo === 'saida') {
                        $saida   += $pesoLiq;
                    } else { // avulsa
                        $avulsa  += $pesoLiq;
                    }
                }
                $quantidade = max(0, ($entrada + $avulsa) - $saida);

                $pGrupo = Produto::findOrFail($prodId);
                $valorProdutoPadrao = (float) $pGrupo->valor_venda;

                $valorTotalPrioritario = 0.0;
                foreach ($ticketsGrupo as $ticketGrupo) {
                    $pesoLiqTicket = max(0, ((float) $ticketGrupo->peso) - ((float) $ticketGrupo->peso_bag));
                    $valorUnitarioPrioritario = (float) ($ticketGrupo->valor_unitario ?? 0) > 0
                        ? (float) $ticketGrupo->valor_unitario
                        : $valorProdutoPadrao;

                    $valorTotalPrioritario += (float) ($ticketGrupo->valor_total ?? 0) > 0
                        ? (float) $ticketGrupo->valor_total
                        : ($pesoLiqTicket * $valorUnitarioPrioritario);
                }

                $valorUnitarioCalculado = $quantidade > 0
                    ? ($valorTotalPrioritario / $quantidade)
                    : $valorProdutoPadrao;

                $valorItem = $usarValoresTicket
                    ? ($valorUnitarioCalculado > 0 ? $valorUnitarioCalculado : $valorProdutoPadrao)
                    : $valorProdutoPadrao;

                ItemVenda::create([
                    'venda_id'     => $venda->id,
                    'produto_id'   => $pGrupo->id,
                    'produto_nome' => $pGrupo->nome,
                    'quantidade'   => (float) $quantidade,
                    'valor'        => (float) $valorItem,
                    'valor_custo'  => (float) $pGrupo->valor_compra,
                ]);
            }

            // Atualizar valor total da venda
            $valorVenda = $venda->itens
                ->sum(fn($item) => $item->quantidade * $item->valor);

            $venda->valor_total = $valorVenda;
            $venda->save();

            // Associar a venda à pesagem
            $pesagem->venda_id = $venda->id;
            $pesagem->save();

            // Captura os dados após a criação para log
            $dadosDepois = $pesagem->toArray();

            // Obtém a classe correta do modelo para log
            $modelInstance = is_string(Pesagem::class) ? app(Pesagem::class) : Pesagem::class;

            // 🔹 Registra log da criação da venda
            $this->logService->registrar('create', get_class($modelInstance), [
                'registro_id'  => $pesagem->id,
                'dados_antes'  => $dadosAnteriores,
                'dados_depois' => $dadosDepois,
            ]);

            if (config('stock_ledger.enabled')) {
                $this->registrarMovimentosPesagem($pesagem, 'PESAGEM', 'pesagem_venda');

                if (config('stock_ledger.transfer_pesagem_to_erp')) {
                    $this->transferirPesagemParaErp($pesagem, 'pesagem_venda');
                }
            }

            $this->dispararMonitoramentoPesagem($pesagem->id, 'venda.created');

            // Adiciona mensagem de sucesso na sessão
            $mensagemSucesso = 'Venda gerada com sucesso! ';
            $mensagemSucesso .= 'Token da pesagem: ' . $pesagem->token . ' ';
            $mensagemSucesso .= 'ID da pesagem: ' . $pesagem->id . ' ';
            $mensagemSucesso .= 'ID da venda: ' . $venda->id . '.';

            session()->flash('mensagem_sucesso', $mensagemSucesso);

            return response()->json([
                'success'  => true,
                'message'  => 'Venda gerada com sucesso.',
                'venda_id' => $pesagem->venda_id
            ], 200);

        } catch (\Exception $e) {

            \Log::error('Erro ao criar venda a partir da pesagem', [
                'pesagemId' => $pesagemId,
                'exception' => $e->getMessage(),
            ]);

            session()->flash('mensagem_erro', "Erro ao criar venda: {$e->getMessage()}");

            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);

        }
    }

    public function criarCompraDePesagem($pesagemId)
    {
        try {
            // Recuperar a pesagem
            $pesagem = Pesagem::with(['fornecedor', 'tickets', 'veiculo', 'motorista'])
                ->where('empresa_id', $this->empresa_id)
                ->findOrFail($pesagemId);

            // Verificar se já existe uma compra associada
            if ($pesagem->compra_id) {
                throw new \Exception('Já existe uma compra associada a esta pesagem.');
            }

            // Validar se a pesagem é do tipo "compra"
            if ($pesagem->tipo !== 'compra') {
                throw new \Exception('Apenas pesagens do tipo "compra" podem gerar uma compra.');
            }

            // Verificar fornecedor
            if (!$pesagem->fornecedor) {
                throw new \Exception('Não foi encontrado um fornecedor associado à pesagem.');
            }

            // Criar a compra com estado "NOVO"
            $compra = Compra::create([
                'fornecedor_id' => $pesagem->fornecedor_id,
                'usuario_id'    => $pesagem->usuario_id,
                'valor'         => 0,  // Atualizado posteriormente
                'desconto'      => 0,
                'acrescimo'     => 0,
                'nf'            => 0,
                'estado'        => 'NOVO',
                'empresa_id'    => $this->empresa_id,
                'observacao'    => 'Compra gerada automaticamente a partir da pesagem #' . $pesagem->id,
                'placa'         => $pesagem->placa_veiculo,
                'uf'            => $pesagem->veiculo->uf ?? '',
                'qtdVolumes'    => 1,  // Pode ser ajustado conforme necessidade
                'peso_liquido'  => $pesagem->peso_liquido,
                'peso_bruto'    => $pesagem->peso,
            ]);

            $configNota = ConfigNota::where('empresa_id', $this->empresa_id)->first();
            $usarValoresTicket = (bool) ($configNota->usar_valores_ticket_pesagem ?? false);

            // —————————————————————————————————————————
            // Agrupar tickets por produto e calcular peso líquido neto
            // —————————————————————————————————————————
            $grupos = [];
            foreach ($pesagem->tickets as $t) {
                $pOrig   = Produto::findOrFail($t->produto_id);
                $pId     = $pOrig->produto_referenciado_id ?: $pOrig->id;
                $grupos[$pId][] = $t;
            }

            foreach ($grupos as $prodId => $ticketsGrupo) {
                $entrada = $saida = $avulsa = 0;
                foreach ($ticketsGrupo as $t) {
                    $pesoLiq = max(0, $t->peso - $t->peso_bag);
                    if ($t->tipo === 'entrada') {
                        $entrada += $pesoLiq;
                    } elseif ($t->tipo === 'saida') {
                        $saida   += $pesoLiq;
                    } else {
                        $avulsa  += $pesoLiq;
                    }
                }
                $quantidade = max(0, ($entrada + $avulsa) - $saida);

                $pGrupo = Produto::findOrFail($prodId);
                $valorProdutoPadrao = (float) $pGrupo->valor_compra;

                $valorTotalPrioritario = 0.0;
                foreach ($ticketsGrupo as $ticketGrupo) {
                    $pesoLiqTicket = max(0, ((float) $ticketGrupo->peso) - ((float) $ticketGrupo->peso_bag));
                    $valorUnitarioPrioritario = (float) ($ticketGrupo->valor_unitario ?? 0) > 0
                        ? (float) $ticketGrupo->valor_unitario
                        : $valorProdutoPadrao;

                    $valorTotalPrioritario += (float) ($ticketGrupo->valor_total ?? 0) > 0
                        ? (float) $ticketGrupo->valor_total
                        : ($pesoLiqTicket * $valorUnitarioPrioritario);
                }

                $valorUnitarioCalculado = $quantidade > 0
                    ? ($valorTotalPrioritario / $quantidade)
                    : $valorProdutoPadrao;

                $valorUnitario = $usarValoresTicket
                    ? ($valorUnitarioCalculado > 0 ? $valorUnitarioCalculado : $valorProdutoPadrao)
                    : $valorProdutoPadrao;

                ItemCompra::create([
                    'compra_id'      => $compra->id,
                    'produto_id'     => $pGrupo->id,
                    'quantidade'     => (float) $quantidade,
                    'valor_unitario' => (float) $valorUnitario,
                    'unidade_compra' => $pGrupo->unidade_compra,
                ]);
            }

            // Atualizar valor total da compra (Collection::sum aceita closure)
            $valorTotal = $compra->itens  // observe que é propriedade, não método
            ->sum(fn($item) => $item->quantidade * $item->valor_unitario);

            $compra->valor = $valorTotal;
            $compra->save();

            // Associar a compra à pesagem
            $pesagem->compra_id = $compra->id;
            $pesagem->save();

            // Mensagem de sucesso para a sessão
            $mensagemSucesso = 'Compra gerada com sucesso! ';
            $mensagemSucesso .= 'Token da pesagem: ' . $pesagem->token . '. ';
            $mensagemSucesso .= 'ID da pesagem: ' . $pesagem->id . '. ';
            $mensagemSucesso .= 'ID da compra: ' . $compra->id . '.';
            session()->flash('mensagem_sucesso', $mensagemSucesso);

            // Obtém a classe correta do modelo
            $modelInstance = is_string(Compra::class) ? app(Compra::class) : Compra::class;

            // 🔹 Registra log da criação da compra
            $this->logService->registrar('create', get_class($modelInstance), [
                'registro_id'     => $compra->id,
                'dados_anteriores'=> [],
                'dados_depois'    => $compra->toArray(),
            ]);

            if (config('stock_ledger.enabled')) {
                $this->registrarMovimentosPesagem($pesagem, 'PESAGEM', 'pesagem_compra');

                if (config('stock_ledger.transfer_pesagem_to_erp')) {
                    $this->transferirPesagemParaErp($pesagem, 'pesagem_compra');
                }
            }

            $this->dispararMonitoramentoPesagem($pesagem->id, 'compra.created');

            return response()->json([
                'success'   => true,
                'message'   => $mensagemSucesso,
                'compra_id' => $pesagem->compra_id
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Erro ao criar compra a partir da pesagem', [
                'pesagemId' => $pesagemId,
                'exception' => $e->getMessage(),
            ]);
            session()->flash('mensagem_erro', "Erro ao criar compra a partir da pesagem");

            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }


    private function registrarMovimentosPesagem(Pesagem $pesagem, string $contexto, string $origemTipo): void
    {
        $stockService = app(StockService::class);

        foreach ($pesagem->tickets as $ticket) {
            if (empty($ticket->produto_id)) {
                continue;
            }

            $pesoLiquido = max(0, ((float) $ticket->peso) - ((float) $ticket->peso_bag));
            if ($pesoLiquido <= 0) {
                continue;
            }

            if ($ticket->tipo === 'entrada' || $ticket->tipo === 'avulsa') {
                $tipoMov = 'entrada';
            } elseif ($ticket->tipo === 'saida') {
                $tipoMov = 'saida';
            } else {
                continue;
            }

            $idempotencyKey = implode(':', [
                'pesagem',
                $pesagem->id,
                'ticket',
                $ticket->id,
                $contexto,
                $tipoMov,
            ]);

            $stockService->mover([
                'empresa_id' => $pesagem->empresa_id,
                'filial_id' => $pesagem->filial_id,
                'usuario_id' => $this->usuario_id,
                'produto_id' => $ticket->produto_id,
                'contexto' => $contexto,
                'tipo' => $tipoMov,
                'quantidade' => $pesoLiquido,
                'custo_unitario' => $ticket->valor_unitario ?? null,
                'origem_tipo' => $origemTipo,
                'origem_id' => $pesagem->id,
                'movimentado_em' => now(),
                'idempotency_key' => $idempotencyKey,
                'metadata' => [
                    'ticket_id' => $ticket->id,
                    'tipo_ticket' => $ticket->tipo,
                ],
            ]);
        }
    }

    private function transferirPesagemParaErp(Pesagem $pesagem, string $origemTipo): void
    {
        $stockService = app(StockService::class);

        $configNota = ConfigNota::where('empresa_id', $pesagem->empresa_id)->first();
        $usarProdutoReferenciado = (bool) ($configNota->usa_produto_referenciado_pesagem ?? false);

        $agregados = [];

        foreach ($pesagem->tickets as $ticket) {
            if (empty($ticket->produto_id)) {
                continue;
            }

            $pesoLiquido = max(0, ((float) $ticket->peso) - ((float) $ticket->peso_bag));
            if ($pesoLiquido <= 0) {
                continue;
            }

            $produtoOrigemId = (int) $ticket->produto_id;
            $produtoDestinoId = $produtoOrigemId;
            $regraAplicada = 'PESADO';
            $fallbackReferenciado = false;

            if ($usarProdutoReferenciado) {
                $produtoPesado = Produto::find($produtoOrigemId);
                $produtoReferenciadoId = (int) ($produtoPesado->produto_referenciado_id ?? 0);

                if ($produtoReferenciadoId > 0) {
                    $produtoDestinoId = $produtoReferenciadoId;
                    $regraAplicada = 'REFERENCIADO';
                } else {
                    $fallbackReferenciado = true;
                }
            }

            $ponteDirecao = null;
            if ($ticket->tipo === 'entrada' || $ticket->tipo === 'avulsa') {
                $ponteDirecao = 'PESAGEM_PARA_ERP';
            } elseif ($ticket->tipo === 'saida') {
                $ponteDirecao = 'ERP_PARA_PESAGEM';
            }

            if ($ponteDirecao === null) {
                continue;
            }

            $key = implode(':', [
                $ponteDirecao,
                $produtoOrigemId,
                $produtoDestinoId,
                $regraAplicada,
                $fallbackReferenciado ? 'fallback' : 'ok',
            ]);

            if (!isset($agregados[$key])) {
                $agregados[$key] = [
                    'quantidade' => 0.0,
                    'produto_origem_id' => $produtoOrigemId,
                    'produto_destino_id' => $produtoDestinoId,
                    'regra_aplicada' => $regraAplicada,
                    'fallback_referenciado' => $fallbackReferenciado,
                    'ponte_direcao' => $ponteDirecao,
                    'ticket_ids' => [],
                ];
            }

            $agregados[$key]['quantidade'] += $pesoLiquido;
            $agregados[$key]['ticket_ids'][] = (int) $ticket->id;
        }

        foreach ($agregados as $item) {
            $quantidade = (float) $item['quantidade'];
            if ($quantidade <= 0) {
                continue;
            }

            $contextoOrigem = $item['ponte_direcao'] === 'PESAGEM_PARA_ERP' ? 'PESAGEM' : 'ERP';
            $contextoDestino = $item['ponte_direcao'] === 'PESAGEM_PARA_ERP' ? 'ERP' : 'PESAGEM';

            $idempotencyKey = implode(':', [
                'pesagem',
                $pesagem->id,
                'ponte',
                $item['ponte_direcao'],
                'origem',
                $item['produto_origem_id'],
                'destino',
                $item['produto_destino_id'],
                'regra',
                strtolower($item['regra_aplicada']),
                $item['fallback_referenciado'] ? 'fallback' : 'ok',
            ]);

            $stockService->transferirEntreContextos([
                'empresa_id' => $pesagem->empresa_id,
                'filial_id' => $pesagem->filial_id,
                'usuario_id' => $this->usuario_id,
                'produto_origem_id' => (int) $item['produto_origem_id'],
                'produto_destino_id' => (int) $item['produto_destino_id'],
                'quantidade' => $quantidade,
                'custo_unitario' => null,
                'origem_tipo' => $origemTipo . '_transfer',
                'origem_id' => $pesagem->id,
                'movimentado_em' => now(),
                'contexto_origem' => $contextoOrigem,
                'contexto_destino' => $contextoDestino,
                'idempotency_key' => $idempotencyKey,
                'metadata' => [
                    'pesagem_id' => $pesagem->id,
                    'ticket_ids' => array_values(array_unique($item['ticket_ids'])),
                    'produto_origem_id' => (int) $item['produto_origem_id'],
                    'produto_destino_id' => (int) $item['produto_destino_id'],
                    'regra_aplicada' => $item['regra_aplicada'],
                    'fallback_referenciado' => (bool) $item['fallback_referenciado'],
                    'ponte_direcao' => $item['ponte_direcao'],
                ],
            ]);
        }
    }

    private function dispararMonitoramentoPesagem(int $pesagemId, string $tipoEvento): void
    {
        DB::afterCommit(function () use ($pesagemId, $tipoEvento) {
            $pesagem = Pesagem::with([
                'filial',
                'cliente',
                'fornecedor',
                'motorista',
                'tickets.produto',
                'venda.itens',
                'compra.itens',
            ])->find($pesagemId);

            if (!$pesagem) {
                return;
            }

            $payload = app(MonitorPesagemService::class)
                ->buildPayload($pesagem, $tipoEvento, now());

            try {
                event(new MovimentoRealtime($tipoEvento, $payload, $pesagem->empresa_id, $pesagem->filial_id));
            } catch (\Throwable $e) {
                \Log::warning('Falha ao emitir evento de monitoramento de pesagem', [
                    'pesagem_id' => $pesagemId,
                    'tipo' => $tipoEvento,
                    'erro' => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * POST /pesagens/reabrir/{id}
     */
    public function reabrir___(Request $request, $id)
    {
        // 1) Valida o código OTP do usuário logado.
        //    Se falhar, lança abort(422, 'mensagem…') conforme definido no BaseController.
        $this->validateOtp($request);

        // 2) Busca a pesagem dentro do tenant/empresa
        $pesagem = $this->model
            ::where('empresa_id', $this->empresa_id)
            ->findOrFail($id);

        // 3) Só então reabre
        $pesagem->status = 'em andamento';
        $pesagem->save();

        return response()->json(['success' => 'Pesagem reaberta com sucesso.']);
    }

    public function reabrir(Request $request, $id)
    {
        // 1) dispara exceção 422 se falhar
        $this->validateOtp($request);

        // 2) recupera e checa status
        $pesagem = Pesagem::findOrFail($id);
        if ($pesagem->status !== 'concluído') {
            return response()->json(['error'=>'Só é possível reabrir pesagens concluídas.'], 422);
        }

        // 3) reabre em transação e registra log
        DB::transaction(function() use ($pesagem) {
            $antes = $pesagem->toArray();
            $pesagem->update([
                'status'      => 'em andamento',
                'view_public' => 0,
            ]);
            $this->logService->registrar('reabrir', Pesagem::class, [
                'registro_id'  => $pesagem->id,
                'dados_antes'  => $antes,
                'dados_depois' => $pesagem->toArray(),
            ]);
        });

        return response()->json(['success'=>'Pesagem reaberta com sucesso.']);
    }

    /**
     * Relatório analítico de pesagens com pesos e abatimentos sempre positivos.
     */
    public function relatorioAnalitico_(Request $request)
    {
        $request->validate([
            'data_inicial' => 'nullable|date',
            'data_final'   => 'nullable|date',
            'tipo'         => 'nullable|in:compra,venda,avulsa',
            'cliente_id'   => 'nullable|exists:clientes,id',
            'fornecedor_id'=> 'nullable|exists:fornecedors,id',
            'status'       => 'nullable|in:concluído,em andamento',
            'nota'         => 'nullable|in:com,sem',
            'formato'      => 'nullable|in:html,pdf,html_download',
        ]);

        $inicio = $request->input('data_inicial') ? Carbon::parse($request->input('data_inicial'))->format('Y-m-d') : null;
        $fim    = $request->input('data_final')   ? Carbon::parse($request->input('data_final'))->format('Y-m-d')   : null;
        $formato = $request->input('formato', 'html');
        $tituloRelatorio = 'Relatório Analítico de Pesagens';
        $clienteFiltro = $request->filled('cliente_id') ? Cliente::find($request->cliente_id) : null;
        $fornecedorFiltro = $request->filled('fornecedor_id') ? Fornecedor::find($request->fornecedor_id) : null;

        $pesagens = Pesagem::with([
            'cliente',
            'fornecedor',
            'motorista',
            'usuario',
            'veiculo',
            'tickets.produto',
            'venda',
            'compra',
        ])
        ->where('empresa_id', $this->empresa_id)
        ->when($inicio, fn($q) => $q->whereDate('dt_registro', '>=', $inicio))
        ->when($fim, fn($q) => $q->whereDate('dt_registro', '<=', $fim))
        ->when($request->input('tipo'), fn($q, $tipo) => $q->where('tipo', $tipo))
        ->when($request->filled('cliente_id'), fn($q) => $q->where('cliente_id', $request->cliente_id))
        ->when($request->filled('fornecedor_id'), fn($q) => $q->where('fornecedor_id', $request->fornecedor_id))
        ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
        ->when($request->filled('nota'), function ($q) use ($request) {
            if ($request->nota === 'com') {
                $q->where(function ($sub) {
                    $sub->whereHas('venda', function ($query) {
                        $query->whereNotNull('chave')->where('NfNumero', '>', 0);
                    })->orWhereHas('compra', function ($query) {
                        $query->whereNotNull('chave')->where('numero_emissao', '>', 0);
                    });
                });
            } elseif ($request->nota === 'sem') {
                $q->where(function ($sub) {
                    $sub->whereDoesntHave('venda', function ($query) {
                        $query->whereNotNull('chave')->where('NfNumero', '>', 0);
                    })->whereDoesntHave('compra', function ($query) {
                        $query->whereNotNull('chave')->where('numero_emissao', '>', 0);
                    });
                });
            }
        })
        ->orderByDesc('dt_registro')
        ->get();

        $relatorio = $pesagens->map(function (Pesagem $pesagem) {
            $entradas = $pesagem->tickets
                ->where('tipo', 'entrada')
                ->sum(fn($t) => max(0, ($t->peso ?? 0) - ($t->peso_bag ?? 0)));
            $saidas = $pesagem->tickets
                ->where('tipo', 'saida')
                ->sum(fn($t) => max(0, ($t->peso ?? 0) - ($t->peso_bag ?? 0)));
            $avulsas = $pesagem->tickets
                ->where('tipo', 'avulsa')
                ->sum(fn($t) => max(0, ($t->peso ?? 0) - ($t->peso_bag ?? 0)));

            $pesoLiquido = max(0, ($entradas + $avulsas) - $saidas);

            $abatimentos = [
                'umidade'    => max(0, (float) $pesagem->umidade_desconto),
                'impureza'   => max(0, (float) $pesagem->impureza_desconto),
                'danificado' => $pesagem->danificado ? max(0, (float) $pesagem->danificado_desconto) : 0,
                'quebrado'   => $pesagem->quebrado   ? max(0, (float) $pesagem->quebrado_desconto)   : 0,
                'esverdeado' => $pesagem->esverdeado ? max(0, (float) $pesagem->esverdeado_desconto) : 0,
                'ardido'     => $pesagem->ardido     ? max(0, (float) $pesagem->ardido_desconto)     : 0,
                'secagem'    => $pesagem->secagem    ? max(0, (float) $pesagem->secagem_desconto)    : 0,
            ];

            $percentualAbatimento = array_sum($abatimentos);
            $pesoFinal = max(0, $pesoLiquido - ($pesoLiquido * ($percentualAbatimento / 100)));

            $venda = $pesagem->venda;
            $compra = $pesagem->compra;

            $notaFiscal = null;
            if ($venda && (int) $venda->NfNumero > 0 && $venda->chave) {
                $notaFiscal = [
                    'tipo'   => 'NF-e de Saída',
                    'numero' => (int) $venda->NfNumero,
                    'serie'  => $venda->nSerie,
                    'estado' => $venda->estado,
                    'data'   => $venda->data_emissao,
                ];
            } elseif ($compra && (int) $compra->numero_emissao > 0 && $compra->chave) {
                $notaFiscal = [
                    'tipo'   => 'NF-e de Entrada',
                    'numero' => (int) $compra->numero_emissao,
                    'serie'  => $compra->nSerie ?? null,
                    'estado' => $compra->estado,
                    'data'   => $compra->data_emissao,
                ];
            }

            return [
                'id'              => $pesagem->id,
                'tipo'            => $pesagem->tipo,
                'data'            => $pesagem->dt_registro,
                'status'         => $pesagem->status,
                'usuario'         => optional($pesagem->usuario)->name,
                'motorista'       => optional($pesagem->motorista)->nome,
                'veiculo'         => optional($pesagem->veiculo)->placa ?? $pesagem->placa_veiculo,
                'cliente'         => optional($pesagem->cliente)->razao_social,
                'fornecedor'      => optional($pesagem->fornecedor)->razao_social,
                'documento'       => $venda ? 'Venda #' . $venda->id : ($compra ? 'Compra #' . $compra->id : '-'),
                'nota_fiscal'     => $notaFiscal,
                'peso_liquido'    => $pesoLiquido,
                'peso_final'      => $pesoFinal,
                'abatimentos'     => $abatimentos,
                'tickets'         => $pesagem->tickets->map(function ($ticket) {
                    $pesoLiquidoTicket = max(0, ($ticket->peso ?? 0) - ($ticket->peso_bag ?? 0));
                    return [
                        'id'           => $ticket->id,
                        'tipo'         => $ticket->tipo,
                        'produto'      => optional($ticket->produto)->nome,
                        'peso'         => $ticket->peso,
                        'peso_bag'     => $ticket->peso_bag,
                        'peso_liquido' => $pesoLiquidoTicket,
                        'created_at'   => $ticket->created_at,
                    ];
                }),
            ];
        });

        if ($request->wantsJson()) {
            return response()->json([
                'sucesso' => true,
                'total'   => $relatorio->count(),
                'data'    => $relatorio,
            ]);
        }

        $configEmitente = ConfigNota::where('empresa_id', $this->empresa_id)->first();

        $totaisPorTipo = $relatorio->groupBy('tipo')->map(function ($grupo) {
            return [
                'quantidade'    => $grupo->count(),
                'peso_liquido'  => $grupo->sum('peso_liquido'),
                'peso_final'    => $grupo->sum('peso_final'),
            ];
        });

        $viewData = [
            'relatorio'        => $relatorio,
            'data_inicial'     => $inicio ? Carbon::parse($inicio)->format('d/m/Y') : null,
            'data_final'       => $fim ? Carbon::parse($fim)->format('d/m/Y') : null,
            'filtro_tipo'      => $request->input('tipo'),
            'filtro_status'    => $request->input('status'),
            'filtro_nota'      => $request->input('nota'),
            'filtro_cliente'   => $clienteFiltro,
            'filtro_fornecedor'=> $fornecedorFiltro,
            'total_liquido'    => $relatorio->sum('peso_liquido'),
            'total_final'      => $relatorio->sum('peso_final'),
            'totais_por_tipo'  => $totaisPorTipo,
            'configEmitente'   => $configEmitente,
            'total_pesagens'   => $relatorio->count(),
            'title'            => $tituloRelatorio,
            'pdf_url'          => route('pesagens.relatorios.analitico', array_merge($request->query(), ['formato' => 'pdf'])),
            'html_download_url'=> route('pesagens.relatorios.analitico', array_merge($request->query(), ['formato' => 'html_download'])),
        ];

        if ($formato === 'pdf') {
            $dompdf = new Dompdf([
                'enable_remote' => true,
            ]);
            $dompdf->loadHtml(view('relatorios.pesagem_analitico_pdf', $viewData)->render());
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            return response($dompdf->output())
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="relatorio-analitico-pesagens.pdf"')
                ->header('Cache-Control', 'no-cache, must-revalidate');
        }

        if ($formato === 'html_download') {
            $html = view('relatorios.pesagem_analitico_pdf', $viewData)->render();
            return response($html)
                ->header('Content-Type', 'text/html')
                ->header('Content-Disposition', 'attachment; filename="relatorio-analitico-pesagens.html"')
                ->header('Cache-Control', 'no-cache, must-revalidate');
        }

        return view('relatorios.pesagem_analitico', $viewData);
    }

    public function relatorioAnalitico(Request $request)
    {
        // ✅ Validação ajustada para formato brasileiro dd/mm/aaaa
        $request->validate([
            'data_inicial'  => 'nullable|date_format:d/m/Y',
            'data_final'    => 'nullable|date_format:d/m/Y',
            'tipo'          => 'nullable|in:compra,venda,avulsa',
            'cliente_id'    => 'nullable|exists:clientes,id',
            'fornecedor_id' => 'nullable|exists:fornecedors,id',
            'status'        => 'nullable|in:concluído,em andamento',
            'nota'          => 'nullable|in:com,sem',
            'formato'       => 'nullable|in:html,pdf,html_download',
        ], [
            'data_inicial.date_format' => 'A data inicial deve estar no formato dd/mm/aaaa.',
            'data_final.date_format'   => 'A data final deve estar no formato dd/mm/aaaa.',
        ]);

        // ✅ Converte dd/mm/aaaa -> Y-m-d para usar no whereDate
        $inicio = $request->filled('data_inicial')
            ? Carbon::createFromFormat('d/m/Y', $request->input('data_inicial'))->format('Y-m-d')
            : null;

        $fim = $request->filled('data_final')
            ? Carbon::createFromFormat('d/m/Y', $request->input('data_final'))->format('Y-m-d')
            : null;

        // ✅ Garante que data_final >= data_inicial (comparando já em Y-m-d)
        if ($inicio && $fim && $fim < $inicio) {
            return back()
                ->withErrors(['data_final' => 'A data final deve ser maior ou igual à data inicial.'])
                ->withInput();
        }

        $formato          = $request->input('formato', 'html');
        $tituloRelatorio  = 'Relatório Analítico de Pesagens';
        $clienteFiltro    = $request->filled('cliente_id')    ? Cliente::find($request->cliente_id)    : null;
        $fornecedorFiltro = $request->filled('fornecedor_id') ? Fornecedor::find($request->fornecedor_id) : null;

        $pesagens = Pesagem::with([
            'cliente',
            'fornecedor',
            'motorista',
            'usuario',
            'veiculo',
            'tickets.produto',
            'venda',
            'compra',
        ])
            ->where('empresa_id', $this->empresa_id)
            ->when($inicio, fn($q) => $q->whereDate('dt_registro', '>=', $inicio))
            ->when($fim, fn($q) => $q->whereDate('dt_registro', '<=', $fim))
            ->when($request->input('tipo'), fn($q, $tipo) => $q->where('tipo', $tipo))
            ->when($request->filled('cliente_id'), fn($q) => $q->where('cliente_id', $request->cliente_id))
            ->when($request->filled('fornecedor_id'), fn($q) => $q->where('fornecedor_id', $request->fornecedor_id))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('nota'), function ($q) use ($request) {
                if ($request->nota === 'com') {
                    $q->where(function ($sub) {
                        $sub->whereHas('venda', function ($query) {
                            $query->whereNotNull('chave')->where('NfNumero', '>', 0);
                        })->orWhereHas('compra', function ($query) {
                            $query->whereNotNull('chave')->where('numero_emissao', '>', 0);
                        });
                    });
                } elseif ($request->nota === 'sem') {
                    $q->where(function ($sub) {
                        $sub->whereDoesntHave('venda', function ($query) {
                            $query->whereNotNull('chave')->where('NfNumero', '>', 0);
                        })->whereDoesntHave('compra', function ($query) {
                            $query->whereNotNull('chave')->where('numero_emissao', '>', 0);
                        });
                    });
                }
            })
            ->orderByDesc('dt_registro')
            ->get();

        $relatorio = $pesagens->map(function (Pesagem $pesagem) {
            $entradas = $pesagem->tickets
                ->where('tipo', 'entrada')
                ->sum(fn($t) => max(0, ($t->peso ?? 0) - ($t->peso_bag ?? 0)));
            $saidas = $pesagem->tickets
                ->where('tipo', 'saida')
                ->sum(fn($t) => max(0, ($t->peso ?? 0) - ($t->peso_bag ?? 0)));
            $avulsas = $pesagem->tickets
                ->where('tipo', 'avulsa')
                ->sum(fn($t) => max(0, ($t->peso ?? 0) - ($t->peso_bag ?? 0)));

            $pesoLiquido = max(0, ($entradas + $avulsas) - $saidas);

            $abatimentos = [
                'umidade'    => max(0, (float) $pesagem->umidade_desconto),
                'impureza'   => max(0, (float) $pesagem->impureza_desconto),
                'danificado' => $pesagem->danificado ? max(0, (float) $pesagem->danificado_desconto) : 0,
                'quebrado'   => $pesagem->quebrado   ? max(0, (float) $pesagem->quebrado_desconto)   : 0,
                'esverdeado' => $pesagem->esverdeado ? max(0, (float) $pesagem->esverdeado_desconto) : 0,
                'ardido'     => $pesagem->ardido     ? max(0, (float) $pesagem->ardido_desconto)     : 0,
                'secagem'    => $pesagem->secagem    ? max(0, (float) $pesagem->secagem_desconto)    : 0,
            ];

            $percentualAbatimento = array_sum($abatimentos);
            $pesoFinal = max(0, $pesoLiquido - ($pesoLiquido * ($percentualAbatimento / 100)));

            $venda = $pesagem->venda;
            $compra = $pesagem->compra;

            $notaFiscal = null;
            if ($venda && (int) $venda->NfNumero > 0 && $venda->chave) {
                $notaFiscal = [
                    'tipo'   => 'NF-e de Saída',
                    'numero' => (int) $venda->NfNumero,
                    'serie'  => $venda->nSerie,
                    'estado' => $venda->estado,
                    'data'   => $venda->data_emissao,
                ];
            } elseif ($compra && (int) $compra->numero_emissao > 0 && $compra->chave) {
                $notaFiscal = [
                    'tipo'   => 'NF-e de Entrada',
                    'numero' => (int) $compra->numero_emissao,
                    'serie'  => $compra->nSerie ?? null,
                    'estado' => $compra->estado,
                    'data'   => $compra->data_emissao,
                ];
            }

            return [
                'id'              => $pesagem->id,
                'tipo'            => $pesagem->tipo,
                'data'            => $pesagem->dt_registro,
                'status'          => $pesagem->status,
                'usuario'         => optional($pesagem->usuario)->name,
                'motorista'       => optional($pesagem->motorista)->nome,
                'veiculo'         => optional($pesagem->veiculo)->placa ?? $pesagem->placa_veiculo,
                'cliente'         => optional($pesagem->cliente)->razao_social,
                'fornecedor'      => optional($pesagem->fornecedor)->razao_social,
                'documento'       => $venda ? 'Venda #' . $venda->id : ($compra ? 'Compra #' . $compra->id : '-'),
                'nota_fiscal'     => $notaFiscal,
                'peso_liquido'    => $pesoLiquido,
                'peso_final'      => $pesoFinal,
                'abatimentos'     => $abatimentos,
                'tickets'         => $pesagem->tickets->map(function ($ticket) {
                    $pesoLiquidoTicket = max(0, ($ticket->peso ?? 0) - ($ticket->peso_bag ?? 0));
                    return [
                        'id'           => $ticket->id,
                        'tipo'         => $ticket->tipo,
                        'produto'      => optional($ticket->produto)->nome,
                        'peso'         => $ticket->peso,
                        'peso_bag'     => $ticket->peso_bag,
                        'peso_liquido' => $pesoLiquidoTicket,
                        'created_at'   => $ticket->created_at,
                    ];
                }),
            ];
        });

        if ($request->wantsJson()) {
            return response()->json([
                'sucesso' => true,
                'total'   => $relatorio->count(),
                'data'    => $relatorio,
            ]);
        }

        $configEmitente = ConfigNota::where('empresa_id', $this->empresa_id)->first();

        $totaisPorTipo = $relatorio->groupBy('tipo')->map(function ($grupo) {
            return [
                'quantidade'    => $grupo->count(),
                'peso_liquido'  => $grupo->sum('peso_liquido'),
                'peso_final'    => $grupo->sum('peso_final'),
            ];
        });

        $viewData = [
            'relatorio'         => $relatorio,
            // ✅ devolve novamente em dd/mm/aaaa para a view
            'data_inicial'      => $inicio ? Carbon::parse($inicio)->format('d/m/Y') : null,
            'data_final'        => $fim ? Carbon::parse($fim)->format('d/m/Y') : null,
            'filtro_tipo'       => $request->input('tipo'),
            'filtro_status'     => $request->input('status'),
            'filtro_nota'       => $request->input('nota'),
            'filtro_cliente'    => $clienteFiltro,
            'filtro_fornecedor' => $fornecedorFiltro,
            'total_liquido'     => $relatorio->sum('peso_liquido'),
            'total_final'       => $relatorio->sum('peso_final'),
            'totais_por_tipo'   => $totaisPorTipo,
            'configEmitente'    => $configEmitente,
            'total_pesagens'    => $relatorio->count(),
            'title'             => $tituloRelatorio,
            'pdf_url'           => route('pesagens.relatorios.analitico', array_merge($request->query(), ['formato' => 'pdf'])),
            'html_download_url' => route('pesagens.relatorios.analitico', array_merge($request->query(), ['formato' => 'html_download'])),
        ];

        if ($formato === 'pdf') {
            $dompdf = new Dompdf([
                'enable_remote' => true,
            ]);
            $dompdf->loadHtml(view('relatorios.pesagem_analitico_pdf', $viewData)->render());
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            return response($dompdf->output())
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="relatorio-analitico-pesagens.pdf"')
                ->header('Cache-Control', 'no-cache, must-revalidate');
        }

        if ($formato === 'html_download') {
            $html = view('relatorios.pesagem_analitico_pdf', $viewData)->render();
            return response($html)
                ->header('Content-Type', 'text/html')
                ->header('Content-Disposition', 'attachment; filename="relatorio-analitico-pesagens.html"')
                ->header('Cache-Control', 'no-cache, must-revalidate');
        }

        return view('relatorios.pesagem_analitico', $viewData);
    }

    public function reabrir_(Request $request, $id)
    {
        $request->validate([
            'code' => 'required|digits:6',
        ]);

        // 1) Busque o usuário autenticado pelo seu modelo Usuario
        /** @var Usuario $user */
        $user = Usuario::findOrFail(Auth::id());

        // 2) Verifique se ele tem OTP habilitado
        if (! $user->hasOtp()) {
            return response()->json(['error' => 'Usuário sem OTP cadastrado.'], 422);
        }

        // 3) Descriptografe o segredo
        $secret = decrypt($user->otp_secret);

        // 4) Verifique o código
        $google2fa = app(Google2FA::class);
        if (! $google2fa->verifyKey($secret, $request->input('code'))) {
            return response()->json(['error' => 'Código OTP inválido.'], 422);
        }

        // 5) Recupere a pesagem e valide o status
        $pesagem = Pesagem::findOrFail($id);
        if ($pesagem->status !== 'concluído') {
            return response()->json(['error' => 'Só é possível reabrir pesagens concluídas.'], 422);
        }

        // 6) Rode tudo em transação e registre log
        DB::beginTransaction();
        try {
            $antes = $pesagem->toArray();

            $pesagem->update([
                'status'      => 'em andamento',
                'view_public' => 0,
            ]);

            // supondo que você tenha um serviço de log injetado
            $this->logService->registrar('reabrir', Pesagem::class, [
                'registro_id'  => $pesagem->id,
                'dados_antes'  => $antes,
                'dados_depois' => $pesagem->toArray(),
            ]);

            DB::commit();
            return response()->json(['success' => 'Pesagem reaberta com sucesso.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => 'Falha ao reabrir a pesagem.'], 500);
        }
    }

}
