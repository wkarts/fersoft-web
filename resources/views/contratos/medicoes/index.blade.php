@extends('default.layout')
@section('content')
<style>
    /* Força a visibilidade e o estilo padrão dos selects de filtro */
    .filter-container select, 
    .form-control, 
    select.form-control,
    .select2-container--default .select2-selection--single {
        background-color: #ffffff !important;
        color: #495057 !important;
        border: 1px solid #ced4da !important;
        height: calc(2.25rem + 2px) !important;
        padding: 0.375rem 0.75rem !important;
        font-size: 1rem !important;
        border-radius: 0.25rem !important;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #495057 !important;
        line-height: 28px !important;
    }
</style>

<div class="card shadow-sm border-0 mb-3">
    <div class="card-body bg-light rounded">
        <form action="{{ route('contratos.medicoes.index') }}" method="GET">
            <div class="row">
                <!-- Filtro por Cliente -->
                <div class="form-group col-md-3">
                    <label class="small font-weight-bold text-muted" for="cliente_id">CLIENTE</label>
                    <select name="cliente_id" id="cliente_id" class="form-control">
                        <option value="">Selecione o Cliente...</option>
                        @foreach($clientes as $cliente)
                            <option value="{{ $cliente->id }}" {{ request('cliente_id') == $cliente->id ? 'selected' : '' }}>
                                {{ $cliente->razao_social ?? $cliente->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filtro por Contrato -->
                <div class="form-group col-md-3">
                    <label class="small font-weight-bold text-muted" for="contrato_id">CONTRATO</label>
                    <select name="contrato_id" id="contrato_id" class="form-control">
                        <option value="">Selecione o Contrato...</option>
                        @foreach($contratos as $contrato)
                            <option value="{{ $contrato->id }}" {{ request('contrato_id') == $contrato->id ? 'selected' : '' }}>
                                Contrato Nº {{ $contrato->numero_contrato ?? $contrato->id }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filtro por Status -->
                <div class="form-group col-md-2">
                    <label class="small font-weight-bold text-muted">STATUS</label>
                    <select name="status" class="form-control">
                        <option value="">Todos</option>
                        <option value="Pendente" {{ request('status') == 'Pendente' ? 'selected' : '' }}>Pendente</option>
                        <option value="Finalizado" {{ request('status') == 'Finalizado' ? 'selected' : '' }}>Finalizado</option>
                        <option value="Quitado" {{ request('status') == 'Quitado' ? 'selected' : '' }}>Quitado</option>
                    </select>
                </div>

                <!-- Data Inicial -->
                <div class="form-group col-md-2">
                    <label class="small font-weight-bold text-muted">DATA INICIAL</label>
                    <input type="date" name="data_inicial" class="form-control" value="{{ request('data_inicial') }}">
                </div>

                <!-- Data Final -->
                <div class="form-group col-md-2">
                    <label class="small font-weight-bold text-muted">DATA FINAL</label>
                    <input type="date" name="data_final" class="form-control" value="{{ request('data_final') }}">
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 text-right">
                    <a href="{{ route('contratos.medicoes.index') }}" class="btn btn-secondary btn-sm px-3">
                        <i class="fa fa-eraser"></i> Limpar
                    </a>
                    <button type="submit" class="btn btn-primary btn-sm px-4">
                        <i class="fa fa-filter"></i> Filtrar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="row mb-3 align-items-center">
            <div class="col-md-6">
                <h4 class="text-secondary font-weight-bold m-0">
                    <i class="fa fa-file-invoice-dollar"></i> Lista de Medições e Faturamento
                </h4>
                <span class="text-muted small">Registros encontrados: {{ $medicoes->total() ?? count($medicoes) }}</span>
            </div>
            
            <div class="col-md-6 text-right">
                <a href="{{ url('/contratos/medicoes/create/novo') }}" class="btn btn-success btn-sm px-3 font-weight-bold">
                    <i class="fa fa-plus"></i> Novo Lançamento
                </a>
            </div>
        </div>

        @if(session('mensagem_sucesso'))
            <div class="alert alert-success">{{ session('mensagem_sucesso') }}</div>
        @endif
        @if(session('mensagem_erro'))
            <div class="alert alert-danger">{{ session('mensagem_erro') }}</div>
        @endif

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="bg-light text-secondary">
                    <tr>
                        <th style="width: 4%;">#</th>
                        <th style="width: 18%;">AÇÕES</th>
                        <th style="width: 5%;">ID</th>
                        <th style="width: 10%;">RPS / SÉRIE</th>
                        <th style="width: 10%;">Nº NFS-E</th>
                        <th style="width: 18%;">CLIENTE</th>
                        <th style="width: 11%;">DATA</th>
                        <th style="width: 10%;">VALOR (R$)</th>
                        <th style="width: 14%;" class="text-center">STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($medicoes as $medicao)
                    <tr>
                        <td><input type="checkbox" name="id[]" value="{{ $medicao->id }}"></td>
                        <td>
                            <div class="btn-group" role="group">
                                <!-- Botão de Imprimir Faturamento Interno -->
                                <a href="{{ route('contratos.medicoes.imprimir', $medicao->id) }}" target="_blank" class="btn btn-sm btn-secondary" title="Imprimir Comprovante Interno">
                                    <i class="fa fa-file-alt"></i>
                                </a>
                                
                                <!-- Botão de Imprimir/Visualizar NFS-e Oficial (Só acende se tiver número) -->
                                @if(!empty($medicao->numero_nfse))
                                    <a href="{{ url('nfse/imprimir', $medicao->id) }}" target="_blank" class="btn btn-sm btn-primary" title="Imprimir NFS-e Oficial">
                                        <i class="fa fa-print"></i>
                                    </a>
                                @else
                                    <button class="btn btn-sm btn-light text-muted" disabled title="NFS-e não emitida"><i class="fa fa-print"></i></button>
                                @endif

                                @if($medicao->status != 'Finalizado' && $medicao->status != 'Quitado')
                                    <a href="{{ route('contratos.medicoes.edit', $medicao->id) }}" class="btn btn-sm btn-warning text-white" title="Editar">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    <a href="{{ route('contratos.medicoes.destroy', $medicao->id) }}" onclick="return confirm('Deseja excluir esta medição?')" class="btn btn-sm btn-danger" title="Excluir">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                @else
                                    <button class="btn btn-sm btn-secondary" disabled title="Bloqueado"><i class="fa fa-lock"></i></button>
                                @endif
                            </div>
                        </td>
                        <td class="font-weight-bold">#{{ $medicao->id }}</td>
                        <!-- Exibe o número do RPS e Série controlados pelo ERP -->
                        <td>{{ $medicao->serie_nfse ?? '1' }} / {{ $medicao->id }}</td>
                        <!-- Exibe o número oficial da Nota gerado pela prefeitura -->
                        <td>
                            @if(!empty($medicao->numero_nfse))
                                <span class="badge badge-success font-weight-bold" style="font-size: 0.9rem;">{{ $medicao->numero_nfse }}</span>
                            @else
                                <span class="text-muted small">Não emitida</span>
                            @endif
                        </td>
                        <td>{{ $medicao->cliente->razao_social ?? $medicao->cliente->nome ?? 'N/D' }}</td>
                        <td>{{ date('d/m/Y H:i', strtotime($medicao->data_faturamento ?? $medicao->created_at)) }}</td>
                        <td class="font-weight-bold text-primary">R$ {{ number_format($medicao->valor_total, 2, ',', '.') }}</td>
                        <td class="text-center">
                            <form action="{{ route('contratos.medicoes.status', $medicao->id) }}" method="POST">
                                @csrf
                                <select name="status" class="form-control form-control-sm text-center font-weight-bold {{ $medicao->status == 'Quitado' ? 'text-success' : ($medicao->status == 'Finalizado' ? 'text-primary' : 'text-warning') }}" onchange="this.form.submit()">
                                    <option value="Pendente" {{ $medicao->status == 'Pendente' ? 'selected' : '' }}>Pendente</option>
                                    <option value="Finalizado" {{ $medicao->status == 'Finalizado' ? 'selected' : '' }}>Finalizado</option>
                                    <option value="Quitado" {{ $medicao->status == 'Quitado' ? 'selected' : '' }}>Quitado</option>
                                </select>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">Nenhum registro encontrado com os filtros informados.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Painel de Ações Inferior -->
        <div class="row mt-4">
            <div class="col-12" style="display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between;">
                
                <div style="flex: 1; display: flex; flex-direction: column; gap: 10px;">
                    <button class="btn btn-block text-white font-weight-bold shadow-sm" onclick="acaoEmLote('emitir_nfse')" style="background-color: #5bc0de; border:none; border-radius: 4px;">Emitir NFS-e</button>
                    <button class="btn btn-block text-white font-weight-bold shadow-sm" onclick="acaoEmLote('danfe')" style="background-color: #66b3ff; border:none; border-radius: 4px;">Visualizar Danfe</button>
                </div>

                <div style="flex: 1; display: flex; flex-direction: column; gap: 10px;">
                    <button class="btn btn-block font-weight-bold shadow-sm text-muted" onclick="acaoEmLote('imprimir')" style="background-color: #f8f9fa; border: 1px solid #ddd; border-radius: 4px;">Imprimir</button>
                    <button class="btn btn-block text-white font-weight-bold shadow-sm" onclick="acaoEmLote('baixar_xml')" style="background-color: #20c997; border:none; border-radius: 4px;">Baixar XML</button>
                </div>

                <div style="flex: 1; display: flex; flex-direction: column; gap: 10px;">
                    <button class="btn btn-block text-white font-weight-bold shadow-sm" onclick="acaoEmLote('consultar')" style="background-color: #a55eea; border:none; border-radius: 4px;">Consultar NFS-e</button>
                    <button class="btn btn-block text-white font-weight-bold shadow-sm" onclick="acaoEmLote('email')" style="background-color: #a55eea; border:none; border-radius: 4px;">Enviar Email</button>
                </div>

                <div style="flex: 1; display: flex; flex-direction: column; gap: 10px;">
                    <button class="btn btn-block text-white font-weight-bold shadow-sm" onclick="acaoEmLote('cancelar')" style="background-color: #ff7675; border:none; border-radius: 4px;">Cancelar</button>
                    <button class="btn btn-block text-white font-weight-bold shadow-sm" onclick="acaoEmLote('whatsapp')" style="background-color: #00b894; border:none; border-radius: 4px;">WhatsApp</button>
                </div>
                
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-12">
                {{ $medicoes->appends(request()->all())->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Scripts de Alerta e Ações em Lote -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@if(session('imprimir_id'))
<script>
    document.addEventListener("DOMContentLoaded", function() {
        Swal.fire({
            title: 'Alerta',
            text: "Deseja imprimir o comprovante da medição?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#f1f1f1',
            confirmButtonText: 'Sim',
            cancelButtonText: '<span style="color:#333">Não</span>'
        }).then((result) => {
            if (result.isConfirmed) {
                window.open("{{ route('contratos.medicoes.imprimir', session('imprimir_id')) }}", "_blank");
            }
        });
    });
</script>
@endif

<script>
    function acaoEmLote(acao) {
        let checkboxSelecionado = document.querySelector('input[name="id[]"]:checked');
        
        if (!checkboxSelecionado) {
            Swal.fire('Atenção', 'Selecione uma medição na tabela primeiro.', 'warning');
            return;
        }

        let id = checkboxSelecionado.value;
        
        // Dicionário com TODAS as rotas dos botões mapeadas
        let rotas = {
            'imprimir': "{{ url('contratos/medicoes/imprimir') }}/" + id,
            'whatsapp': "{{ url('contratos/medicoes/enviar-whatsapp') }}/" + id,
            'emitir_nfse': "{{ url('contratos/medicoes/nfse/emitir') }}/" + id,
            'danfe': "{{ url('contratos/medicoes/nfse/imprimir') }}/" + id,       // <--- MAPEADO (Abre o espelho/DANFE da Nota)
            'baixar_xml': "{{ url('contratos/medicoes/nfse/xml') }}/" + id,
            'consultar': "{{ url('contratos/medicoes/nfse/consultar') }}/" + id,  // <--- MAPEADO
            'cancelar': "{{ url('contratos/medicoes/cancelar') }}/" + id,
            'email': "{{ url('contratos/medicoes/enviar-email') }}/" + id
        };

        if (rotas[acao]) {
            if (acao === 'imprimir' || acao === 'danfe' || acao === 'baixar_xml') {
                window.open(rotas[acao], '_blank');
            } else if (acao === 'emitir_nfse') {
                Swal.fire({
                    title: 'Emitir NFS-e Nacional?',
                    text: "A nota será assinada com o certificado A1 e transmitida ao Portal Nacional.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sim, Emitir!',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = rotas[acao];
                    }
                });
            } else if (acao === 'cancelar') {
                Swal.fire({
                    title: 'Cancelar Medição?',
                    text: "Deseja realmente cancelar este faturamento?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sim, Cancelar',
                    cancelButtonText: 'Voltar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = rotas[acao];
                    }
                });
            } else {
                window.location.href = rotas[acao];
            }
        } else {
            Swal.fire('Em desenvolvimento', 'Esta função ainda não possui rota configurada.', 'info');
        }
    }
</script>
@endsection