@extends('default.layout')
@section('content')
    <style>
        /* Estilização moderna e limpa para tabelas corporativas */
        .table-hover tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.04);
        }
        .badge-status {
            padding: 6px 12px;
            font-weight: 600;
            border-radius: 4px;
            font-size: 11px;
            text-transform: uppercase;
        }

        /* ----- BADGES MTR E NFE (Logística) ----- */
        .badge-fixed-width-doc {
            width: 75px !important;
            display: inline-block !important;
            text-align: center !important;
            padding: 5px !important;
            font-size: 10px !important;
            font-weight: bold;
        }

        /* Ajuste para garantir que o HTML formatado não seja escapado e exiba as tags <span> */
        .datatable-cell-html {
            white-space: normal !important;
        }
    </style>

    <div class="card card-custom gutter-b border-0 shadow-sm">
        <div class="card-body">
            <div class="mb-5" id="kt_user_profile_aside">
                <input type="hidden" id="_token" value="{{ csrf_token() }}">

                <!-- Cabeçalho e Botão de Novo Registro -->
                <div class="d-flex justify-content-between align-items-center flex-wrap mb-5">
                    <div>
                        <h3 class="font-weight-bolder text-dark mb-1">{{ $title }}</h3>
                        <span class="text-muted font-weight-bold font-size-sm">Total de Registros Encontrados: <strong class="text-success">{{ count($records) }}</strong></span>
                    </div>
                    <div>
                        <a href="{{ $newItemUrl }}" class="btn btn-primary font-weight-bold px-4 py-2">
                            <i class="la la-plus"></i> {{ $newItemText }}
                        </a>
                    </div>
                </div>

                <!-- Filtros de Pesquisa Fixos e Dinâmicos -->
                @php
                    // Captura a empresa_id de forma segura direto da sessão/requisição do ERP
                    $empresaIdSegura = request()->empresa_id ?? session('user_logged')['empresa_id'] ?? 1;

                    // Busca os dados diretamente para não depender do array do Controller
                    $listaVeiculos = \App\Models\Veiculo::where('empresa_id', $empresaIdSegura)->get();
                    $listaMotoristas = \App\Models\Funcionario::where('empresa_id', $empresaIdSegura)->get();
                @endphp

                <div class="card bg-light border-0 p-4 mb-5">
                    <form class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft" method="get" action="{{ $filterUrl }}">
                        <div class="row align-items-end">

                            <!-- Filtro por Veículo -->
                            <div class="form-group col-lg-3 col-md-4 col-sm-6 mb-3">
                                <label class="font-weight-bold text-dark font-size-sm">Veículo</label>
                                <select name="veiculo_id" class="form-control form-control-solid">
                                    <option value="">Todos os Veículos</option>
                                    @foreach($listaVeiculos as $v)
                                        <option value="{{ $v->id }}" {{ request()->get('veiculo_id') == $v->id ? 'selected' : '' }}>
                                            {{ $v->placa }} - {{ $v->modelo }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Filtro por Motorista -->
                            <div class="form-group col-lg-3 col-md-4 col-sm-6 mb-3">
                                <label class="font-weight-bold text-dark font-size-sm">Motorista</label>
                                <select name="motorista_id" class="form-control form-control-solid">
                                    <option value="">Todos os Motoristas</option>
                                    @foreach($listaMotoristas as $f)
                                        <option value="{{ $f->id }}" {{ request()->get('motorista_id') == $f->id ? 'selected' : '' }}>
                                            {{ $f->nome }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Filtro por Status -->
                            <div class="form-group col-lg-2 col-md-4 col-sm-6 mb-3">
                                <label class="font-weight-bold text-dark font-size-sm">Status</label>
                                <select name="status" class="form-control form-control-solid">
                                    <option value="">Todos</option>
                                    <option value="agendado" {{ request()->get('status') == 'agendado' ? 'selected' : '' }}>Agendado</option>
                                    <option value="iniciado" {{ request()->get('status') == 'iniciado' ? 'selected' : '' }}>Iniciado</option>
                                    <option value="finalizado" {{ request()->get('status') == 'finalizado' ? 'selected' : '' }}>Finalizado</option>
                                    <option value="cancelado" {{ request()->get('status') == 'cancelado' ? 'selected' : '' }}>Cancelado</option>
                                </select>
                            </div>

                            <!-- Filtro por Filial / Matriz -->
                            <div class="form-group col-lg-3 col-md-4 col-sm-6 mb-3">
                                <label class="font-weight-bold text-dark font-size-sm">Filial / Local</label>
                                <select name="filial_id" class="form-control form-control-solid">
                                    <option value="">Todas as Filiais Permitidas</option>
                                    <option value="0" {{ request('filial_id') === '0' ? 'selected' : '' }}>Matriz</option>
                                    @if(isset($listaFiliais) && count($listaFiliais) > 0)
                                        @foreach($listaFiliais as $filial)
                                            <option value="{{ $filial->id }}" {{ request('filial_id') == $filial->id ? 'selected' : '' }}>
                                                {{ $filial->descricao ?? ('Filial #' . $filial->id) }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            <!-- Filtro Data Início -->
                            <div class="form-group col-lg-2 col-md-4 col-sm-6 mb-3">
                                <label class="font-weight-bold text-dark font-size-sm">Data Início</label>
                                <input type="date" name="data_inicio" class="form-control form-control-solid" value="{{ request()->get('data_inicio', '') }}">
                            </div>

                            <!-- Botões de Ação -->
                            <div class="col-lg-2 col-md-4 col-sm-12 mb-3">
                                <button type="submit" class="btn btn-primary font-weight-bold px-3 mr-1">
                                    <i class="la la-search"></i> Filtrar
                                </button>
                                <a href="{{ $filterUrl }}" class="btn btn-secondary font-weight-bold px-2">
                                    <i class="la la-redo"></i> Limpar
                                </a>
                            </div>

                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabela de Registros Profissional -->
            <div class="row @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">
                <div class="col-12">
                    <div class="table-responsive">
                        <table class="table table-hover table-vertical-center border-0">
                            <thead class="bg-gray-100 text-uppercase font-size-sm font-weight-bolder text-muted">
                            <tr>
                                @foreach($headers as $header)
                                    <th class="pl-4">{{ $header }}</th>
                                @endforeach
                                <th class="text-center" style="min-width: 220px;">Ações</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($records as $record)
                                <tr>
                                    @foreach($fields as $field)
                                        <td class="pl-4 font-size-sm">
                                            @if($field == 'documentos_fiscais_formatado')
                                                <!-- 🚀 Renderiza as Badges do MTR e NFE formatadas no Model -->
                                                {!! data_get($record, $field) !!}

                                            @elseif($field == 'status_formatado')
                                                @php
                                                    $statusVal = strtolower($record->status ?? '');
                                                    $badgeClass = match($statusVal) {
                                                        'agendado' => 'badge-warning text-dark',
                                                        'iniciado' => 'badge-primary',
                                                        'finalizado', 'concluida' => 'badge-success',
                                                        'cancelado' => 'badge-danger',
                                                        default => 'badge-secondary'
                                                    };
                                                @endphp
                                                <span class="badge badge-status {{ $badgeClass }}">
                                                    {{ ucfirst($record->status ?? 'N/A') }}
                                                </span>

                                                @php
                                                    $nomeFilialRegisto = 'Matriz';
                                                    if (!empty($record->filial_id)) {
                                                        $fObj = \Illuminate\Support\Facades\DB::table('filials')->where('id', $record->filial_id)->first();
                                                        $nomeFilialRegisto = $fObj->descricao ?? ('Filial #' . $record->filial_id);
                                                    }
                                                @endphp
                                                <div class="badge badge-light-info font-weight-bold mb-1" style="font-size: 10px;">{{ $nomeFilialRegisto }}</div>

                                            @elseif($field == 'entidade_nome')
                                                @php
                                                    $nomesEntidades = [];

                                                    // 1. Verifica se há múltiplos destinos (Multi-Stop)
                                                    if ($record->destinos && $record->destinos->count() > 0) {
                                                        foreach ($record->destinos as $dest) {
                                                            if ($dest->cliente) {
                                                                $nomesEntidades[] = $dest->cliente->razao_social ?? $dest->cliente->nome_fantasia ?? $dest->cliente->nome;
                                                            } elseif ($dest->fornecedor) {
                                                                $nomesEntidades[] = '[FOR] ' . ($dest->fornecedor->razao_social ?? $dest->fornecedor->nome_fantasia ?? $dest->fornecedor->nome);
                                                            }
                                                        }
                                                    }

                                                    // 2. Fallback: Se não achou nos destinos, pega direto da movimentação ou do accessor entidade_nome
                                                    if (empty($nomesEntidades)) {
                                                        if ($record->cliente) {
                                                            $nomesEntidades[] = $record->cliente->razao_social ?? $record->cliente->nome_fantasia ?? $record->cliente->nome;
                                                        } elseif ($record->fornecedor) {
                                                            $nomesEntidades[] = '[FOR] ' . ($record->fornecedor->razao_social ?? $record->fornecedor->nome_fantasia ?? $record->fornecedor->nome);
                                                        } elseif (!empty($record->entidade_nome) && $record->entidade_nome !== 'N/A') {
                                                            $nomesEntidades[] = $record->entidade_nome;
                                                        }
                                                    }

                                                    $nomesEntidades = array_filter(array_unique($nomesEntidades));
                                                @endphp

                                                @if(count($nomesEntidades) > 0)
                                                    <div style="font-size: 11px; line-height: 1.25; max-width: 220px;">
                                                        @foreach($nomesEntidades as $nomeEnt)
                                                            <div class="text-dark-75 font-weight-bold text-truncate" title="{{ $nomeEnt }}">
                                                                • {{ $nomeEnt }}
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <span class="text-muted font-size-xs">N/A</span>
                                                @endif

                                            @else
                                                <span class="text-dark-75 font-weight-bold">{{ data_get($record, $field) ?: 'N/A' }}</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center flex-wrap" style="gap: 4px;">

                                            <!-- 🚀 NOVO BOTÃO: Vincular Docs Fiscais -->
                                            @if(in_array(strtolower($record->status), ['finalizado', 'concluida']))
                                                <button type="button" class="btn btn-icon btn-light-info btn-sm btn-vincular-docs-frota"
                                                        data-id="{{ $record->id }}"
                                                        data-nfe="{{ $record->nfe_numero }}"
                                                        data-mtr="{{ $record->mtr_numero }}"
                                                        title="Vincular NF-e e MTR Oficial">
                                                    <i class="fa fa-file-invoice"></i>
                                                </button>
                                            @endif

                                            <!-- Botão de Enviar Link de Abastecimento Manualmente -->
                                            <a href="/movimentacaoVeiculo/enviarLinkAbastecimento/{{ $record->id }}" class="btn btn-icon btn-light-info btn-sm" title="Enviar Link de Abastecimento via WhatsApp">
                                                <i class="la la-gas-pump"></i>
                                            </a>

                                            <!-- Botão para Enviar o Link de Coleta e Resíduos via WhatsApp para o Motorista -->
                                            <a href="/movimentacaoVeiculo/enviar-link-coleta/{{ $record->id }}" class="btn btn-icon btn-light-warning btn-sm" title="Enviar Link de Coleta/Resíduos via WhatsApp">
                                                <i class="la la-recycle"></i>
                                            </a>
                                            <!-- Botão de Enviar Link do Checklist Manualmente -->
                                            <a href="/movimentacaoVeiculo/enviar-checklist/{{ $record->id }}" class="btn btn-icon btn-light-success btn-sm" title="Enviar Link do Checklist via WhatsApp">
                                                <i class="la la-whatsapp"></i>
                                            </a>

                                            <a href="{{ url('/movimentacaoVeiculo/enviar-resumo/' . $record->id) }}"
                                               class="btn btn-sm btn-light-success btn-icon"
                                               title="Enviar Ordem de Coleta / Resumo pelo WhatsApp">
                                                <i class="fab fa-whatsapp"></i>
                                            </a>

                                            <!-- Botão de Ver Respostas do Checklist -->
                                            <a href="/movimentacaoVeiculo/ver-checklist/{{ $record->id }}" class="btn btn-icon btn-light-primary btn-sm" title="Ver Respostas e Fotos do Checklist">
                                                <i class="la la-clipboard-check"></i>
                                            </a>

                                            <a href="{{ url('/movimentacaoVeiculo/historico/' . $record->id) }}" class="btn btn-sm btn-light-info btn-icon" title="Ver Histórico">
                                                <i class="la la-history"></i>
                                            </a>

                                            <a href="/movimentacaoVeiculo/imprimir/{{ $record->id }}" target="_blank" class="btn btn-icon btn-light-dark btn-sm" title="Imprimir">
                                                <i class="la la-print"></i>
                                            </a>

                                            @if(($podeReabrirAgenda ?? false) && in_array($record->status, ['finalizado', 'concluida'], true))
                                                <button type="button"
                                                        class="btn btn-icon btn-light-primary btn-sm btn-reabrir-movimentacao"
                                                        data-id="{{ $record->id }}"
                                                        title="Reabrir agendamento com OTP"
                                                        aria-label="Reabrir agendamento #{{ $record->id }}">
                                                    <i class="la la-unlock"></i>
                                                </button>
                                            @endif

                                            @if(!in_array($record->status, ['finalizado', 'concluida'], true))
                                                <a href="{{ $editUrl }}/{{ $record->id }}" class="btn btn-icon btn-light-warning btn-sm" title="Editar">
                                                    <i class="la la-edit"></i>
                                                </a>
                                            @endif

                                            <a onclick="if(confirm('Deseja realmente excluir esta movimentação?')) { window.location.href = '{{ $deleteUrl }}/{{ $record->id }}' }" class="btn btn-icon btn-light-danger btn-sm" title="Excluir" style="cursor: pointer;">
                                                <i class="la la-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($headers) + 1 }}" class="text-center py-5 text-muted font-weight-bold">
                                        Nenhuma movimentação encontrada para o período ou filtros selecionados.
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Paginação -->
            @if(isset($links) && method_exists($records, 'links'))
                <div class="d-flex justify-content-between align-items-center flex-wrap mt-4">
                    <div class="d-flex flex-wrap py-2 mr-3">
                        {{ $links }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal para Vincular NF-e e MTR (Frota) -->
    <div class="modal fade" id="modalVincularDocsFrota" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form id="formVincularDocsFrota">
                @csrf
                <input type="hidden" name="movimentacao_id" id="vincularMovimentacaoId">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title font-weight-bold text-white"><i class="fa fa-file-invoice mr-2"></i> Vincular NF-e e MTR à Viagem</h5>
                        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="font-weight-bold">Número da Nota Fiscal (NF-e):</label>
                            <input type="text" name="nfe_numero" id="inputNfeFrota" class="form-control" placeholder="Ex: 000123456 ou Chave de Acesso">
                            <small class="text-muted">Informe o número ou chave da NF-e referente ao frete/serviço.</small>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold">Número do MTR (Manifesto):</label>
                            <input type="text" name="mtr_numero" id="inputMtrFrota" class="form-control" placeholder="Ex: MTR-2026-XXXXXX">
                            <small class="text-muted">Informe o MTR oficial vinculado a esta carga.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary font-weight-bold" id="btnSalvarVinculoDocsFrota">
                            <i class="fa fa-save"></i> Salvar Vínculo
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @include('movimentacoes_veiculos.partials.reabrir_modal')
@endsection

@section('javascript')
    @include('movimentacoes_veiculos.partials.reabrir_script')

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Abre o modal preenchendo os dados atuais
            $(document).on('click', '.btn-vincular-docs-frota', function(e) {
                e.preventDefault();
                $('#vincularMovimentacaoId').val($(this).attr('data-id'));
                $('#inputNfeFrota').val($(this).attr('data-nfe'));
                $('#inputMtrFrota').val($(this).attr('data-mtr'));
                $('#modalVincularDocsFrota').modal('show');
            });

            // Submete o vínculo via AJAX
            $('#formVincularDocsFrota').on('submit', function(e) {
                e.preventDefault();
                const id = $('#vincularMovimentacaoId').val();
                const formData = $(this).serialize();
                const $btn = $('#btnSalvarVinculoDocsFrota');

                $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Salvando...');

                $.post(`/movimentacaoVeiculo/vincular-documentos/${id}`, formData)
                    .done(function(res) {
                        if(res.success) {
                            $('#modalVincularDocsFrota').modal('hide');
                            if(typeof Swal !== 'undefined') {
                                Swal.fire('Sucesso!', res.message, 'success').then(() => location.reload());
                            } else {
                                alert(res.message);
                                location.reload();
                            }
                        } else {
                            if(typeof Swal !== 'undefined') {
                                Swal.fire('Atenção', res.error || "Erro ao salvar.", 'warning');
                            } else {
                                alert(res.error || "Erro ao salvar.");
                            }
                        }
                    })
                    .fail(function() {
                        if(typeof Swal !== 'undefined') {
                            Swal.fire('Erro', "Falha de comunicação com o servidor.", 'error');
                        } else {
                            alert("Falha de comunicação com o servidor.");
                        }
                    })
                    .always(function() {
                        $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Salvar Vínculo');
                    });
            });
        });
    </script>
@endsection
