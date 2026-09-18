@extends('default.layout', ['title' => 'Movimentações conta ' . $item->nome])

@section('content')

    {{-- (GRUPO 1) CABEÇALHO: Informações da conta e botões de ação --}}
    <div class="card card-custom gutter-b shadow-sm">
        <div class="card-body">
            @if(session('mensagem_sucesso'))
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    {{ session('mensagem_sucesso') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif
            @if(session('mensagem_erro'))
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    {{ session('mensagem_erro') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <div class="row align-items-center">
                <div class="col-md-6">
                    <h3 class="mb-1 font-weight-bolder text-dark">Conta: {{ $item->nome }}</h3>
                    <p class="text-muted mb-0 font-weight-bold">
                        Banco: {{ $item->banco ?? 'Caixa Interno' }} | Agência: {{ $item->agencia ?? '-' }} | Conta: {{ $item->conta ?? '-' }}
                    </p>
                </div>
                <div class="col-md-6 text-right">
                    <button type="button" class="btn btn-info font-weight-bold ml-1" data-toggle="modal" data-target="#modalManual">
                        <i class="fa fa-question-circle"></i> Como Funciona
                    </button>

                    <button onclick="confirmarSincronismo({{ $item->id }})" class="btn btn-warning font-weight-bold ml-1">
                        <i class="la la-refresh"></i> Sincronizar Histórico
                    </button>

                    <a href="{{ route('contas-empresa.imprimir-extrato', $item->id) }}?data_inicio={{$data_inicio}}&data_final={{$data_final}}" target="_blank" class="btn btn-primary font-weight-bold ml-1">
                        <i class="fa fa-print"></i> Imprimir Extrato
                    </a>
                </div>

                <div class="col-12 mt-3">
                    <a href="{{ route('contas-empresa.index') }}" class="btn btn-light-primary font-weight-bold">
                        <i class="la la-arrow-left"></i> Voltar para Contas
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- (GRUPO 2) CARDS DE BALANÇO FINANCEIRO --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card card-custom bg-light-primary padding-4 shadow-sm">
                <div class="card-body p-4">
                    <span class="text-muted font-weight-bold d-block">Saldo Inicial da Conta</span>
                    <span class="font-weight-bolder font-size-h3 text-primary">R$ {{ moeda($item->saldo_inicial) }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-custom bg-light-info padding-4 shadow-sm">
                <div class="card-body p-4">
                    <span class="text-muted font-weight-bold d-block">Saldo Anterior ao Período</span>
                    <span class="font-weight-bolder font-size-h3 {{ $saldo_anterior < 0 ? 'text-danger' : 'text-info' }}">
                    R$ {{ moeda($saldo_anterior) }}
                </span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-custom bg-light-success padding-4 shadow-sm">
                <div class="card-body p-4">
                    <span class="text-muted font-weight-bold d-block">Saldo Atual da Conta (Geral)</span>
                    <span class="font-weight-bolder font-size-h3 {{ $item->saldo < 0 ? 'text-danger' : 'text-success' }}">
                    R$ {{ moeda($item->saldo) }}
                </span>
                </div>
            </div>
        </div>
    </div>

    {{-- (GRUPO 3) LANÇAMENTO MANUAL --}}
    <div class="card card-custom gutter-b shadow-sm">
        <div class="card-header border-0 pt-5">
            <h3 class="card-title font-weight-bolder text-dark">Novo Lançamento Manual</h3>
        </div>
        <div class="card-body pt-2">
            <form action="{{ route('item-conta.store') }}" method="post">
                @csrf
                <input type="hidden" name="conta_id" value="{{ $item->id }}">
                <div class="row">
                    <div class="col-md-2">
                        <label class="font-weight-bold">Data</label>
                        <input type="date" name="data_pagamento" class="form-control" required value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="font-weight-bold">Descrição</label>
                        <input type="text" name="descricao" class="form-control" required placeholder="Ex: Pagamento de tarifa, Depósito em dinheiro">
                    </div>
                    <div class="col-md-2">
                        <label class="font-weight-bold">Valor</label>
                        <input type="text" name="valor" class="form-control money" required placeholder="0,00">
                    </div>
                    <div class="col-md-2">
                        <label class="font-weight-bold">Tipo</label>
                        <select name="tipo" class="form-control custom-select" required>
                            <option value="entrada">Entrada (Crédito)</option>
                            <option value="saida">Saída (Débito)</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="font-weight-bold">Categoria</label>
                        <select name="plano_conta_id" class="form-control custom-select" required>
                            @foreach($categorias as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->nome }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-8 mt-3">
                        <label class="font-weight-bold">Conta de Destino (Caso seja uma Transferência entre Contas)</label>
                        <select name="conta_destino_id" class="form-control custom-select">
                            <option value="">Nenhuma (Lançamento Simples)</option>
                            @foreach($contas as $c)
                                @if($c->id != $item->id)
                                    <option value="{{ $c->id }}">{{ $c->nome }} ({{ $c->banco ?? 'Caixa' }})</option>
                                @endif
                            @endforeach
                        </select>
                        <small class="text-info">Ao selecionar uma conta de destino, o sistema criará o lançamento oposto na conta informada.</small>
                    </div>

                    <div class="col-md-4 mt-3 text-right my-auto">
                        <button type="submit" class="btn btn-primary btn-block font-weight-bold">
                            <i class="la la-check-circle"></i> Salvar Lançamento
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- (GRUPO 4) EXTRATO / MOVIEMNTAÇÕES COM FILTRO --}}
    <div class="card card-custom gutter-b shadow-sm">
        <div class="card-body">
            <div class="table-responsive">

                {{-- Formulário de Filtro --}}
                <form class="row mb-5" method="get" action="{{ route('contas-empresa.show', [$item->id]) }}">
                    <div class="col-md-2">
                        <label class="font-weight-bold">Data inicial</label>
                        <input value="{{ $data_inicio ?? '' }}" type="date" name="data_inicio" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="font-weight-bold">Data final</label>
                        <input value="{{ $data_final ?? '' }}" type="date" name="data_final" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="font-weight-bold">Tipo</label>
                        <select name="tipo" class="form-control custom-select">
                            <option value="">Todos</option>
                            <option @if(isset($tipo) && $tipo == 'entrada') selected @endif value="entrada">Entrada</option>
                            <option @if(isset($tipo) && $tipo == 'saida') selected @endif value="saida">Saída</option>
                        </select>
                    </div>
                    <div class="col-md-6 mt-auto">
                        <button type="submit" class="btn btn-light-primary px-6 font-weight-bold">
                            <i class="la la-search"></i> Filtrar
                        </button>
                        <a class="btn btn-warning px-6 font-weight-bold ml-1" href="{{ route('contas-empresa.show', [$item->id]) }}">
                            <i class="la la-eraser"></i> Limpar
                        </a>
                    </div>
                </form>

                <table class="table table-hover table-head-custom">
                    <thead>
                    <tr>
                        <th>Data</th>
                        <th>Descrição</th>
                        <th>Categoria / Usuário</th>
                        <th class="text-right">Entrada (+)</th>
                        <th class="text-right">Saída (-)</th>
                        <th class="text-right">Saldo da Linha</th>
                        <th class="text-center">Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr class="bg-light">
                        <td colspan="5"><strong>SALDO ANTERIOR AO PERÍODO FILTRADO</strong></td>
                        <td class="text-right">
                            <strong class="{{ $saldo_anterior < 0 ? 'text-danger' : 'text-info' }}">
                                R$ {{ moeda($saldo_anterior) }}
                            </strong>
                        </td>
                        <td></td>
                    </tr>

                    @forelse($data as $m)
                        <tr>
                            <td>{{ __date($m->data_pagamento ?? $m->created_at) }}</td>
                            <td class="font-weight-bold text-dark-75">{{ $m->descricao }}</td>
                            <td>
                                <small class="d-block text-dark font-weight-bold">Cat: {{ $m->categoria->nome ?? 'Sem Categoria' }}</small>
                                <small class="d-block text-muted">User: {{ $m->usuario->nome ?? 'N/A' }}</small>
                            </td>

                            <td class="text-right font-weight-bold text-success">
                                {{ $m->tipo == 'entrada' ? '+ R$ ' . moeda($m->valor) : '-' }}
                            </td>
                            <td class="text-right font-weight-bold text-danger">
                                {{ $m->tipo == 'saida' ? '- R$ ' . moeda($m->valor) : '-' }}
                            </td>

                            {{-- EXIBE O SALDO ATUALIZADO VINDOS DO BANCO (CORRIGE O PROBLEMA DA PAGINAÇÃO) --}}
                            <td class="text-right">
                                <span class="{{ ($m->saldo_atual ?? 0) < 0 ? 'text-danger' : 'text-info' }} font-weight-bolder font-size-lg">
                                    R$ {{ moeda($m->saldo_atual ?? 0) }}
                                </span>
                            </td>

                            <td class="text-center">
                                <a href="{{ route('contas-empresa.imprimir-transacao', $m->id) }}" target="_blank"
                                   class="btn btn-sm btn-icon btn-light-info" title="Imprimir Comprovante">
                                    <i class="la la-print"></i>
                                </a>

                                @if($m->conta_receber_id == null && $m->conta_pagar_id == null)
                                    <button type="button" onclick="excluirLancamentoManual({{ $m->id }})"
                                            class="btn btn-sm btn-icon btn-light-danger ml-1" title="Excluir lançamento manual">
                                        <i class="la la-trash"></i>
                                    </button>
                                @else
                                    <span class="ml-1" title="Lançamento automático vindo do Financeiro (Contas a Pagar/Receber)">
                                        <i class="la la-lock text-muted font-size-h5"></i>
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted p-5">Nenhuma movimentação encontrada neste período!</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="col-12 mt-5">
                {{ $data->appends(request()->all())->links() }}
            </div>
        </div>
    </div>

    <input type="hidden" id="casas_decimais" value="{{ $casasDecimais ?? 2 }}">

    {{-- SCRIPTS JS --}}
    <script type="text/javascript">
        function confirmarSincronismo(id) {
            let dInicio = $("input[name='data_inicio']").val();
            let dFinal = $("input[name='data_final']").val();

            if(!dInicio || !dFinal) {
                Swal.fire("Atenção", "Selecione um período (Data Inicial e Final) nos filtros antes de sincronizar.", "info");
                return;
            }

            Swal.fire({
                title: 'Sincronizar Período?',
                text: "O sistema buscará lançamentos financeiros entre " + dInicio + " e " + dFinal + " que ainda não constam nesta conta. Confirmar?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3699FF',
                confirmButtonText: 'Sim, Sincronizar!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Sincronizando...',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    let url = "{{ route('contas-empresa.sincronizar', '') }}/" + id +
                        "?data_inicial=" + dInicio + "&data_final=" + dFinal;

                    window.location.href = url;
                }
            });
        }

        function excluirLancamentoManual(id) {
            Swal.fire({
                title: 'Excluir Lançamento?',
                text: "Digite a senha de segurança para confirmação:",
                input: 'password',
                showCancelButton: true,
                confirmButtonText: 'Confirmar Exclusão',
                cancelButtonText: 'Cancelar',
                preConfirm: (senha) => {
                    if (!senha) { Swal.showValidationMessage('A senha é obrigatória!'); }
                    return senha;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "{{ route('contas-empresa.delete-lancamento', ['id' => ':id']) }}".replace(':id', id) + "?senha=" + result.value;
                }
            });
        }
    </script>

    {{-- MODAL DE MANUAL DE INSTRUÇÕES --}}
    <div class="modal fade" id="modalManual" tabindex="-1" role="dialog" aria-labelledby="modalManualLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title font-weight-bold" id="modalManualLabel">
                        <i class="fa fa-book text-info mr-2"></i> Manual: Gestão de Contas e Movimentações
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <i aria-hidden="true" class="ki ki-close"></i>
                    </button>
                </div>
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">

                    <h5 class="text-primary">1. Visão Geral e Listagem de Contas</h5>
                    <p>A tela principal de "Contas da Empresa" funciona como o painel central de controle bancário.</p>
                    <ul>
                        <li><strong>Acompanhamento Rápido:</strong> A grade exibe todas as contas cadastradas com informações vitais, incluindo o banco, agência, conta e o saldo atualizado.</li>
                        <li><strong>Controle de Locais:</strong> Você pode visualizar facilmente se a conta pertence à Matriz ou a uma Filial específica por meio das etiquetas visuais.</li>
                        <li><strong>Ações Disponíveis:</strong> Na coluna de ações de cada linha, você pode editar o cadastro, excluir a conta (mediante senha de segurança) ou acessar o extrato detalhado de movimentações.</li>
                    </ul>

                    <hr>

                    <h5 class="text-primary">2. Como Cadastrar ou Editar uma Conta</h5>
                    <p>Para registrar um novo banco ou caixa, clique no botão verde "Nova Conta" na tela principal.</p>
                    <ul>
                        <li><strong>Dados Básicos:</strong> Preencha o nome da conta, o banco correspondente, agência e o número da conta.</li>
                        <li><strong>Integração Contábil:</strong> Selecione o "Plano de Contas" gerencial do sistema e, obrigatoriamente, vincule a "Conta Contábil" correta para garantir a conformidade na exportação para sistemas contábeis (ex: Prosoft).</li>
                        <li><strong>Vínculo e Saldos:</strong> Defina se o local da conta será a Matriz ou uma Filial. Em novos cadastros, insira com atenção o "Saldo Inicial".</li>
                        <li><strong>Visibilidade:</strong> Escolha o Status (Ativa/Desativada) e ative a opção "Exibir no Dashboard Analítico" caso queira que o saldo desta conta seja somado no painel inicial de Saldos Bancários.</li>
                    </ul>

                    <hr>

                    <h5 class="text-primary">3. Explorando o Extrato e as Movimentações</h5>
                    <p>Clicando no botão escuro de "lista" na grade de contas, você acessa a tela de Extrato da conta específica.</p>
                    <ul>
                        <li><strong>Cabeçalho da Conta:</strong> Exibe os dados bancários principais e disponibiliza a opção para "Imprimir Extrato" físico ou em PDF.</li>
                        <li><strong>Filtros de Busca:</strong> Utilize o formulário central para buscar dados por período ("Data inicial" e "Data final") e por tipo ("Entrada" ou "Saída").</li>
                        <li><strong>Leitura da Tabela:</strong> A listagem apresenta primeiro o "Saldo Anterior" ao período filtrado. Logo abaixo, linha a linha, detalha-se a data, descrição, usuário, valor e saldo atualizado.</li>
                        <li><strong>Comprovantes:</strong> É possível imprimir um comprovante individual de qualquer transação clicando no botão azul de impressora.</li>
                    </ul>

                    <hr>

                    <h5 class="text-primary">4. Lançamentos Manuais e Transferências</h5>
                    <p>Na tela de extrato, utilize o formulário "Novo Lançamento Manual" para despesas/receitas não integradas automaticamente.</p>
                    <ol>
                        <li>Informe a <strong>Data</strong> e a <strong>Descrição</strong> da operação.</li>
                        <li>Preencha o <strong>Valor</strong> monetário e selecione o <strong>Tipo</strong> (Entrada ou Saída).</li>
                        <li><strong>Transferências:</strong> Se selecionar uma "Conta de Destino", o sistema realizará a transferência automática (cria a saída em uma e entrada na outra).</li>
                        <li>Vincule à <strong>Categoria</strong> gerencial correta e salve.</li>
                    </ol>

                    <hr>

                    <h5 class="text-primary">5. Sincronização Automática de Histórico</h5>
                    <p>A ferramenta "Sincronizar Histórico" busca dados do Contas a Pagar e Contas a Receber e injeta no extrato.</p>
                    <ul>
                        <li><strong>Como usar:</strong> É obrigatório selecionar um período de datas no filtro principal da tela antes de clicar no botão amarelo de sincronização.</li>
                        <li><strong>Inteligência de Filtro:</strong> Se a conta for "Caixa/Fundo Fixo", importará apenas movimentações em "Dinheiro". Para bancos, importará as demais (PIX, Cartão, Transferência), ignorando dinheiro físico.</li>
                        <li>Os lançamentos automáticos possuem um ícone de <strong>cadeado</strong> e não podem ser excluídos manualmente pelo extrato.</li>
                    </ul>

                    <hr>

                    <h5 class="text-primary">6. Regras de Segurança e Exclusão</h5>
                    <ul>
                        <li>Para excluir um lançamento avulso (lixeira) ou apagar uma conta inteira, será exigida a <strong>senha de segurança global</strong> do sistema.</li>
                        <li>O sistema não permite exclusão manual de transações sincronizadas (com cadeado). A correção deve ser feita na origem (Contas a Pagar/Receber).</li>
                        <li>Quando excluído com sucesso (via senha), os saldos são recalculados e estornados imediatamente.</li>
                    </ul>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">Fechar Manual</button>
                </div>
            </div>
        </div>
    </div>
@endsection
