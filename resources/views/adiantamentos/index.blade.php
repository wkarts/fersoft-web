@extends('default.layout', ['title' => 'Adiantamentos'])

@section('content')
    <style type="text/css">
        .select2-container--open { z-index: 9999999 !important; }

        /* Estilo para centralizar o título mantendo o padrão do seu card */
        .titulo-centralizado {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            margin: 0;
            font-weight: bold;
            color: #333;
        }

        @media print {
            body.printing-adiantamento > *:not(#adiantamento-relatorio) { display: none !important; }
            body.printing-adiantamento #adiantamento-relatorio {
                display: block !important; width: 100% !important; background: #fff !important;
            }
            .no-print { display: none !important; }
        }
    </style>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                {{-- ID adicionado para a função de impressão --}}
                <div class="card card-primary card-outline" id="adiantamento-relatorio">
                    <div class="card-header d-flex align-items-center" style="position: relative;">

                        <h3 class="card-title titulo-centralizado"><i class="fas fa-wallet"></i> Saldo por Cliente/Fornecedor</h3>

                        <div class="card-tools ml-auto no-print">
                            <button type="button" class="btn btn-default btn-sm mr-2" onclick="imprimir()">
                                <i class="fas fa-print"></i> Imprimir
                            </button>
                            <a href="{{ route('adiantamentos.sincronizar') }}" class="btn btn-warning btn-sm mr-2" onclick="return confirm('Sincronizar Notas Autorizadas?')">
                                <i class="fas fa-sync"></i> Sincronizar
                            </a>
                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalAdiantamento">
                                <i class="fas fa-plus"></i> Novo Adiantamento
                            </button>
                        </div>
                    </div>

                    <div class="card-body">

                        {{-- INÍCIO DOS FILTROS - Mantendo as classes do seu sistema --}}
                        <form method="get" action="/adiantamentos" id="form-filtro" class="mb-4 no-print">
                            <div class="row">
                                <div class="col-md-3">
                                    <label>Filtrar Tipo</label>
                                    <select name="tipo" id="tipo_filtro" class="form-control form-control-sm" onchange="$('#form-filtro').submit()">
                                        <option value="">Todos</option>
                                        <option value="cliente" {{ request('tipo') == 'cliente' ? 'selected' : '' }}>Clientes</option>
                                        <option value="fornecedor" {{ request('tipo') == 'fornecedor' ? 'selected' : '' }}>Fornecedores</option>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label>Pessoa (Busca)</label>
                                    <select class="form-control form-control-sm select2" id="pessoa_id_filtro" name="pessoa_id" onchange="$('#form-filtro').submit()">
                                        <option value="">Digite para buscar...</option>
                                        @if(request('pessoa_id'))
                                            <option value="{{ request('pessoa_id') }}" selected>Filtrado</option>
                                        @endif
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label>&nbsp;</label>
                                    <a href="/adiantamentos" class="btn btn-default btn-sm btn-block text-danger">Limpar</a>
                                </div>
                            </div>
                        </form>
                        <hr class="no-print">

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover datatable">
                                <thead>
                                <tr class="bg-light">
                                    <th>Razão Social</th>
                                    <th>Tipo</th>
                                    <th>Total Adiantado (+)</th>
                                    <th>Total Consumido (-)</th>
                                    <th>Saldo Disponível (=)</th>
                                    <th width="150" class="text-center no-print">Ações</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($saldos as $s)
                                    <tr>
                                        <td>{{ $s->cliente->razao_social ?? $s->fornecedor->razao_social ?? 'Não identificado' }}</td>
                                        <td>
                                        <span class="badge {{ $s->cliente_id ? 'badge-info' : 'badge-warning' }}">
                                            {{ $s->cliente_id ? 'Cliente' : 'Fornecedor' }}
                                        </span>
                                        </td>
                                        <td>R$ {{ number_format($s->total_gerado, 2, ',', '.') }}</td>
                                        <td class="text-danger">R$ {{ number_format($s->total_usado, 2, ',', '.') }}</td>
                                        <td class="text-primary font-weight-bold">R$ {{ number_format($s->saldo_disponivel, 2, ',', '.') }}</td>
                                        <td class="text-center no-print">
                                        <div class="btn-group">
                                            <a href="{{ route('adiantamentos.extrato', ['tipo' => $s->cliente_id ? 'cliente' : 'fornecedor', 'id' => $s->cliente_id ?? $s->fornecedor_id]) }}" class="btn btn-sm btn-default" title="Ver Extrato">
                                                <i class="fas fa-list"></i> Extrato
                                            </a>

                                            <!-- NOVO BOTÃO DE DEVOLUÇÃO -->
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                onclick="abrirModalDevolucao('{{ $s->cliente_id ?? $s->fornecedor_id }}', '{{ $s->cliente_id ? 'cliente' : 'fornecedor' }}', '{{ $s->saldo_disponivel }}')" 
                                                title="Devolver Valor">
                                                <i class="fas fa-undo"></i> Devolver
                                            </button>
                                        </div>
                                    </td>
                                    </tr>                                 
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Nenhum saldo pendente encontrado.</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Novo Adiantamento - Exatamente como no seu arquivo funcional --}}
    <div class="modal fade" id="modalAdiantamento" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('adiantamentos.store') }}" method="POST">
                    @csrf
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Novo Adiantamento</h5>
                        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label>Tipo</label>
                                <select name="tipo_pessoa" id="tipo_pessoa" class="form-control" required>
                                    <option value="cliente">Cliente (Entrada)</option>
                                    <option value="fornecedor">Fornecedor (Saída)</option>
                                </select>
                            </div>
                            <div class="col-md-8 form-group">
                                <label>Razão Social</label>
                                <select name="pessoa_id" id="pessoa_id" class="form-control" style="width: 100%;" required>
                                    <option value="">Digite para buscar...</option>
                                </select>
                                <input type="hidden" name="nome_pessoa" id="nome_pessoa">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Valor</label>
                                <input type="text" name="valor" class="form-control money" placeholder="0,00" required>
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Data</label>
                                <input type="date" name="data" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Conta Bancária</label>
                                <select name="conta_id" class="form-control" required>
                                    @foreach($contas as $c)
                                        <option value="{{ $c->id }}">{{ $c->nome }} (Saldo: R$ {{ number_format($c->saldo, 2, ',', '.') }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12 form-group">
                                <label>Categoria Financeira</label>
                                <select name="categoria_id" id="categoria_id" class="form-control" required>
                                    <option value="">Selecione uma categoria...</option>
                                    @foreach($categorias as $cat)
                                        <option value="{{ $cat->id }}" data-tipo="{{ strtolower($cat->tipo ?? '') }}">{{ $cat->nome }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                        <button type="submit" class="btn btn-success">Confirmar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<!-- MODAL DE DEVOLUÇÃO -->
    <div class="modal fade" id="modalDevolucao" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('adiantamentos.devolver') }}" method="POST">
                    @csrf
                    <input type="hidden" name="pessoa_id" id="dev_pessoa_id">
                    <input type="hidden" name="tipo_pessoa" id="dev_tipo_pessoa">

                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Registrar Devolução de Saldo</h5>
                        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <p class="text-muted">Este lançamento registrará a saída/entrada do dinheiro na conta empresa e abaterá o saldo do adiantamento.</p>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Saldo Disponível</label>
                                <input type="text" id="dev_saldo_ver" class="form-control" readonly>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Valor a Devolver</label>
                                <input type="text" name="valor_devolucao" id="valor_devolucao" class="form-control money" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Data da Devolução</label>
                                <input type="date" name="data_devolucao" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Conta Bancária</label>
                                <select name="conta_id" class="form-control" required>
                                    @foreach($contas as $c)
                                        <option value="{{ $c->id }}">{{ $c->nome }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Confirmar Devolução</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('javascript')
    <script>
        $(document).ready(function() {

            // FUNÇÃO DE IMPRESSÃO PROFISSIONAL
            window.imprimir = function() {
                const relatorio = document.getElementById('adiantamento-relatorio');
                const origem = relatorio.parentNode;
                const placeholder = document.createElement('div');
                origem.insertBefore(placeholder, relatorio);
                document.body.appendChild(relatorio);
                document.body.classList.add('printing-adiantamento');
                window.print();
                setTimeout(() => {
                    origem.insertBefore(relatorio, placeholder);
                    origem.removeChild(placeholder);
                    document.body.classList.remove('printing-adiantamento');
                }, 1200);
            };

            // SELECT2 DO FILTRO DE PESQUISA (TOPO)
            $('#pessoa_id_filtro').select2({
                placeholder: 'Pesquisar...',
                ajax: {
                    url: "{{ route('adiantamentos.buscarPessoas') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function (params) { return { term: params.term || '', tipo: $('#tipo_filtro').val() || 'cliente' }; },
                    processResults: function (data) { return data; }
                }
            });

            // SELECT2 DO MODAL
            $('#modalAdiantamento').on('shown.bs.modal', function () {
                $('#pessoa_id').select2({
                    placeholder: 'Clique para selecionar...',
                    dropdownParent: $('#modalAdiantamento .modal-content'),
                    ajax: {
                        url: "{{ route('adiantamentos.buscarPessoas') }}",
                        dataType: 'json',
                        delay: 250,
                        data: function (params) { return { term: params.term || '', tipo: $('#tipo_pessoa').val() }; },
                        processResults: function (data) { return data; }
                    }
                });
            });

                      
          // LÓGICA DE CATEGORIAS E MÁSCARAS
            $('#tipo_pessoa').on('change', function() {
                var tipoPessoa = $(this).val();
                $('#pessoa_id').val(null).trigger('change');
                var tipoCategoriaPermitida = (tipoPessoa === 'cliente') ? 'receber' : 'pagar';
                $('#categoria_id option').each(function() {
                    var tipoCat = $(this).data('tipo');
                    if ($(this).val() === '' || tipoCat === tipoCategoriaPermitida) $(this).show();
                    else $(this).hide();
                });
            });
            $('.money').mask('#.##0,00', {reverse: true});
            $('#pessoa_id').on('select2:select', function (e) { $('#nome_pessoa').val(e.params.data.text); });
        });
    </script>

@section('javascript')
<script>
    $(document).ready(function() {
        // 1. Mascaras
        $('.money').mask('#.##0,00', {reverse: true});

        // 2. Select2 do Filtro (Topo da página)
        $('#pessoa_id_filtro').select2({
            placeholder: 'Pesquisar...',
            ajax: {
                url: "{{ route('adiantamentos.buscarPessoas') }}",
                dataType: 'json',
                delay: 250,
                data: function (params) { 
                    return { 
                        term: params.term || '', 
                        tipo: $('#tipo_filtro').val() || 'cliente' 
                    }; 
                },
                processResults: function (data) { return data; }
            }
        });

        // 3. Select2 do Modal (Novo Adiantamento)
        $('#modalAdiantamento').on('shown.bs.modal', function () {
            $('#pessoa_id').select2({
                placeholder: 'Clique para selecionar...',
                dropdownParent: $('#modalAdiantamento'), // Garantir que o dropdown apareça sobre o modal
                ajax: {
                    url: "{{ route('adiantamentos.buscarPessoas') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function (params) { 
                        return { 
                            term: params.term || '', 
                            tipo: $('#tipo_pessoa').val() 
                        }; 
                    },
                    processResults: function (data) { return data; }
                }
            });
        });

        // 4. Lógica de troca de tipo (Limpa o select se mudar de Cliente para Fornecedor)
        $('#tipo_pessoa').on('change', function() {
            $('#pessoa_id').val(null).trigger('change');
        });

        // 5. Captura o nome da pessoa selecionada
        $('#pessoa_id').on('select2:select', function (e) { 
            $('#nome_pessoa').val(e.params.data.text); 
        });
    });

    // Função fora do ready para o onclick do botão Devolver funcionar
    function abrirModalDevolucao(pessoaId, tipoPessoa, saldo) {
        $('#dev_pessoa_id').val(pessoaId);
        $('#dev_tipo_pessoa').val(tipoPessoa);
        let saldoFormatado = parseFloat(saldo).toLocaleString('pt-br', {style: 'currency', currency: 'BRL'});
        $('#dev_saldo_ver').val(saldoFormatado);
        $('#valor_devolucao').val('');
        $('#modalDevolucao').modal('show');
    }
</script>
@endsection
