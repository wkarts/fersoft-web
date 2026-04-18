@extends('default.layout', ['title' => 'Adiantamentos'])

@section('content')
<style type="text/css">
    /* Garante que o dropdown do Select2 fique acima de qualquer elemento do modal */
    .select2-container--open {
        z-index: 9999999 !important;
    }
</style>
<div class="container-fluid">
    
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-wallet"></i> Saldo por Cliente/Fornecedor</h3>
                    <div class="card-tools">
                        <a href="{{ route('adiantamentos.sincronizar') }}" class="btn btn-warning btn-sm mr-2" onclick="return confirm('Sincronizar Notas Autorizadas?')">
                            <i class="fas fa-sync"></i> Sincronizar
                        </a>
                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalAdiantamento">
                            <i class="fas fa-plus"></i> Novo Adiantamento
                        </button>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover datatable">
                            <thead>
                                <tr class="bg-light">
                                    <th>Razão Social</th>
                                    <th>Tipo</th>
                                    <th>Total Adiantado (+)</th>
                                    <th>Total Consumido (-)</th>
                                    <th>Saldo Disponível (=)</th>
                                    <th width="150" class="text-center">Ações</th>
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
                                    <td class="text-center">
                                        {{-- Botão que leva para a rotina de extrato detalhado (A segunda rotina que você sugeriu) --}}
                                        <a href="{{ route('adiantamentos.extrato', ['tipo' => $s->cliente_id ? 'cliente' : 'fornecedor', 'id' => $s->cliente_id ?? $s->fornecedor_id]) }}" class="btn btn-sm btn-default">
                                            <i class="fas fa-list"></i> Ver Extrato
                                        </a>
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

{{-- Modal Novo Adiantamento (Mesma lógica que já funciona) --}}
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
                                    <option value="{{ $cat->id }}" data-tipo="{{ strtolower($cat->tipo ?? '') }}">
                                        {{ $cat->nome }}
                                    </option>
                                @endforeach
                            </select>
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
@endsection

@section('javascript')
<script>
$(document).ready(function() {
    
    // =======================================================
    // 1. LÓGICA DE CATEGORIAS E CLIENTE/FORNECEDOR
    // Colocamos primeiro para garantir que execute logo
    // =======================================================
    $('#tipo_pessoa').on('change', function() {
        var tipoPessoa = $(this).val(); 
        
        // Limpa a razão social
        $('#pessoa_id').val(null).trigger('change');

        // Filtro: Cliente = receber | Fornecedor = pagar
        var tipoCategoriaPermitida = (tipoPessoa === 'cliente') ? 'receber' : 'pagar';

        $('#categoria_id option').each(function() {
            var tipoCat = $(this).data('tipo');
            
            if ($(this).val() === '') {
                $(this).show(); 
                return;
            }

            if (tipoCat === tipoCategoriaPermitida) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });

        $('#categoria_id').val('');
    });

    // Dispara logo que abre a página
    $('#tipo_pessoa').trigger('change');


    // =======================================================
    // 2. SELECT2 (BUSCA AO CLICAR)
    // =======================================================
    if ($.fn.select2) {
        $('#modalAdiantamento').on('shown.bs.modal', function () {
            $('#pessoa_id').select2({
                placeholder: 'Clique para selecionar...',
                dropdownParent: $('#modalAdiantamento .modal-content'), 
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
                    processResults: function (data) {
                        return data; 
                    },
                    cache: true
                }
            });
        });

        $('#pessoa_id').on('select2:select', function (e) {
            $('#nome_pessoa').val(e.params.data.text);
        });
    }

    // =======================================================
    // 3. DATATABLE (BLINDADO CONTRA ERROS)
    // =======================================================
    try {
        if ($.fn.DataTable) {
            $('.table').DataTable({
                "language": { "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Portuguese-Brasil.json" },
                "order": [[ 4, "desc" ]],
                "pageLength": 50
            });
        }
    } catch (e) {
        console.warn("DataTable ignorado para não travar a tela:", e);
    }

    // =======================================================
    // 4. MÁSCARAS
    // =======================================================
    if ($.fn.mask) {
        $('.money').mask('#.##0,00', {reverse: true});
    }

});
</script>
@endsection