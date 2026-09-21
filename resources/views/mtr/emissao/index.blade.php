@extends('default.layout')

@section('content')
<style>
    .badge-status { font-size: 0.85rem; padding: 6px 12px; border-radius: 4px; font-weight: bold; }
    .bg-rascunho { background-color: #ffb800; color: #fff; }
    .bg-emitido { background-color: #1bc5bd; color: #fff; }
    .bg-recebido { background-color: #0bb783; color: #fff; }
    .bg-cancelado { background-color: #f64e60; color: #fff; }
    .bg-rejeitado { background-color: #3f4254; color: #fff; }
    .filter-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 15px; margin-bottom: 20px; }
    
    /* Ajustes da tabela */
    .table-head-custom thead th { background-color: #f3f6f9; font-weight: 700; color: #3f4254; }
    .td-envolvidos { font-size: 0.85rem; line-height: 1.4; }
</style>

<div class="card card-custom gutter-b">
    <div class="card-header border-0 py-5">
        <h3 class="card-title align-items-start flex-column">
            <span class="card-label font-weight-bolder text-dark">
                <i class="fa fa-truck text-primary mr-2"></i> Central de Emissão de MTR (SINIR / IEMA)
            </span>
            <span class="text-muted mt-1 font-size-sm">Gerenciamento, emissão e consulta ao vivo de manifestos de resíduos</span>
        </h3>
        <div class="card-toolbar d-flex align-items-center">
            <a href="{{ route('mtr.emissao.create.avulso') }}" class="btn font-weight-bolder mr-2" style="background-color: #1bc5bd; color: white;">
                <i class="fa fa-plus text-white"></i> Novo MTR Avulso
            </a>

            <button type="button" class="btn font-weight-bolder mr-2" data-toggle="modal" data-target="#modalImportarNfe" style="background-color: #3699ff; color: white;">
                <i class="fa fa-file-invoice text-white"></i> Importar de NF-e
            </button>

            <button type="button" class="btn font-weight-bolder text-dark" data-toggle="modal" data-target="#modalImportarPesagem" style="background-color: #ffb822;">
                <i class="fa fa-balance-scale text-dark"></i> Importar da Pesagem
            </button>
        </div>
    </div>

    <div class="card-body pt-0">
        @if(session('sucesso'))
            <div class="alert alert-custom alert-light-success fade show mb-4" role="alert">
                <div class="alert-icon"><i class="fa fa-check-circle"></i></div>
                <div class="alert-text font-weight-bold">{{ session('sucesso') }}</div>
                <div class="alert-close"><button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>
            </div>
        @endif

        @if(session('erro'))
            <div class="alert alert-custom alert-light-danger fade show mb-4" role="alert">
                <div class="alert-icon"><i class="fa fa-exclamation-triangle"></i></div>
                <div class="alert-text font-weight-bold">{{ session('erro') }}</div>
                <div class="alert-close"><button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>
            </div>
        @endif

        <!-- FILTROS AVANÇADOS -->
        <form method="GET" action="{{ route('mtr.emissao.index') }}" class="filter-box">
            <div class="row">
                <div class="col-md-2 form-group">
                    <label class="font-weight-bold">Nº MTR / Cód:</label>
                    <input type="text" name="numero_mtr" class="form-control form-control-sm" value="{{ request('numero_mtr') }}" placeholder="Ex: 291032...">
                </div>
                <div class="col-md-3 form-group">
                    <label class="font-weight-bold">Gerador / Destinador:</label>
                    <input type="text" name="envolvido" class="form-control form-control-sm" value="{{ request('envolvido') }}" placeholder="Razão Social ou Nome">
                </div>
                <div class="col-md-2 form-group">
                    <label class="font-weight-bold">Transportadora:</label>
                    <input type="text" name="transportadora" class="form-control form-control-sm" value="{{ request('transportadora') }}" placeholder="Nome Transp.">
                </div>
                <div class="col-md-2 form-group">
                    <label class="font-weight-bold">Status:</label>
                    <select name="status" class="form-control form-control-sm">
                        <option value="">-- Todos --</option>
                        <option value="rascunho" {{ request('status') == 'rascunho' ? 'selected' : '' }}>Rascunho</option>
                        <option value="transmitido" {{ request('status') == 'transmitido' ? 'selected' : '' }}>Emitido / Autorizado</option>
                        <option value="recebido" {{ request('status') == 'recebido' ? 'selected' : '' }}>Recebido pelo Destinador</option>
                        <option value="cancelado" {{ request('status') == 'cancelado' ? 'selected' : '' }}>Cancelado</option>
                    </select>
                </div>
                <div class="col-md-3 form-group d-flex align-items-end">
                    <button type="submit" class="btn btn-sm btn-primary font-weight-bold mr-2 w-100">
                        <i class="fa fa-search"></i> Filtrar
                    </button>
                    <a href="{{ route('mtr.emissao.index') }}" class="btn btn-sm btn-secondary font-weight-bold" title="Limpar Filtros">
                        <i class="fa fa-eraser"></i>
                    </a>
                </div>
            </div>
        </form>

        <!-- GRADE DE MANIFESTOS -->
        <div class="table-responsive">
            <table class="table table-bordered table-head-custom table-hover">
                <thead>
                    <tr>
                        <th>Órgão</th>
                        <th>Nº MTR Oficial</th>
                        <th>Cód. Interno</th>
                        <th>Origem</th>
                        <th>Data Expedição</th>
                        <th>Gerador / Destinador</th>
                        <th>Transporte (Transp. / Mot. / Placa)</th>
                        <th class="text-center">Status</th>
                        <th class="text-center" style="min-width: 150px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($manifestos as $m)
                        <tr>
                            <td class="align-middle">
                                <span class="badge badge-primary font-weight-bold">{{ $m->orgao ?? 'SINIR' }}</span>
                            </td>
                            <td class="align-middle">
                                @if($m->numero_mtr)
                                    <strong class="text-success">{{ $m->numero_mtr }}</strong>
                                @else
                                    <span class="text-muted font-italic">Aguardando</span>
                                @endif
                            </td>
                            <td class="align-middle"><small class="text-danger font-weight-bold">{{ $m->seu_codigo }}</small></td>
                            <td class="align-middle">
                                <span class="badge badge-secondary font-weight-bold">{{ strtoupper($m->tipo_origem) }}</span>
                            </td>
                            <td class="align-middle text-nowrap">
                                {{ date('d/m/Y H:i', strtotime($m->data_expedicao)) }}
                            </td>
                            <td class="align-middle td-envolvidos">
                                <div class="mb-1"><strong class="text-dark">G:</strong> {{ $m->gerador_nome }}</div>
                                <div><strong class="text-dark">D:</strong> {{ $m->destinador_nome }}</div>
                            </td>
                            <td class="align-middle td-envolvidos">
                                <div class="mb-1"><strong class="text-dark">T:</strong> {{ $m->transportador_nome ?? 'Não informado' }}</div>
                                <div class="mb-1"><strong class="text-dark">M:</strong> {{ $m->motorista_nome ?? 'Não informado' }}</div>
                                <div><strong class="text-dark">P:</strong> <span class="badge badge-light-dark font-weight-bold">{{ $m->veiculo_placa }}</span></div>
                            </td>
                            <td class="text-center align-middle">
                                @if($m->status === 'rascunho')
                                    <span class="badge-status bg-rascunho"><i class="fa fa-clock text-white mr-1"></i> Rascunho</span>
                                @elseif($m->status === 'transmitido')
                                    <span class="badge-status bg-emitido"><i class="fa fa-check text-white mr-1"></i> Emitido</span>
                                @elseif($m->status === 'recebido')
                                    <span class="badge-status bg-recebido"><i class="fa fa-warehouse text-white mr-1"></i> Recebido</span>
                                @elseif($m->status === 'cancelado')
                                    <span class="badge-status bg-cancelado"><i class="fa fa-ban text-white mr-1"></i> Cancelado</span>
                                @else
                                    <span class="badge-status bg-rejeitado">{{ strtoupper($m->status) }}</span>
                                @endif
                            </td>
                            <td class="text-center align-middle text-nowrap">
                                @if($m->status === 'rascunho')
                                    <!-- Ações para Rascunho -->
                                    <form action="{{ route('mtr.emissao.transmitir', $m->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-icon btn-success" title="Transmitir MTR">
                                            <i class="fa fa-paper-plane"></i>
                                        </button>
                                    </form>

                                    <a href="{{ route('mtr.emissao.edit', $m->id) }}" class="btn btn-sm btn-icon btn-warning text-dark" title="Editar Rascunho">
                                        <i class="fa fa-edit"></i>
                                    </a>

                                    <form action="{{ route('mtr.emissao.destroy', $m->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-icon btn-danger" onclick="return confirm('Excluir este rascunho?')" title="Excluir">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </form>

                                @elseif($m->status === 'cancelado')
                                    <span class="text-muted font-size-sm"><i class="fa fa-ban"></i> Sem ações</span>

                                @else
                                    <!-- AÇÕES PARA MTR EMITIDO / RECEBIDO -->
                                    <a href="{{ route('mtr.emissao.pdf', $m->numero_mtr ?? $m->id) }}" target="_blank" class="btn btn-sm btn-primary font-weight-bold" title="Baixar MTR (PDF)">
                                        <i class="fa fa-file-pdf"></i> PDF
                                    </a>

                                    <a href="{{ route('mtr.emissao.cdf', $m->numero_mtr ?? $m->id) }}" target="_blank" class="btn btn-sm btn-info font-weight-bold" style="background-color: #8950FC; border-color: #8950FC;" title="Baixar CDF">
                                        <i class="fa fa-certificate"></i> CDF
                                    </a>

                                    <button type="button" class="btn btn-sm btn-icon btn-success ml-1" onclick="abrirModalWhatsapp({{ $m->id }}, '{{ $m->numero_mtr }}')" title="Enviar MTR via WhatsApp">
                                        <i class="fab fa-whatsapp"></i>
                                    </button>

                                    <div class="btn-group ml-1">
                                        <button type="button" class="btn btn-sm btn-icon btn-secondary" onclick="consultarSinir({{ $m->id }})" title="Consultar Situação Oficial">
                                            <i class="fa fa-sync"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-icon btn-danger" onclick="abrirModalCancelar({{ $m->id }}, '{{ $m->numero_mtr }}')" title="Cancelar MTR no SINIR">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted font-size-lg">
                                <i class="fa fa-folder-open font-size-h1 d-block mb-3"></i>
                                Nenhum Manifesto de Resíduo encontrado com os filtros aplicados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-end mt-3">
            {{ $manifestos->links() }}
        </div>
    </div>
</div>

<!-- MODAL CANCELAR MTR -->
<div class="modal fade" id="modalCancelarMtr" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="formCancelarMtr" method="POST" action="">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title text-white font-weight-bold">
                        <i class="fa fa-exclamation-triangle text-white mr-2"></i> Cancelar MTR Oficial no SINIR
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="font-size-lg">Confirma o cancelamento do MTR <strong id="lblMtrCancelar" class="text-danger"></strong>?</p>
                    <div class="form-group mt-3">
                        <label class="font-weight-bold">Justificativa do Cancelamento: <span class="text-danger">*</span></label>
                        <textarea name="justificativa" class="form-control" rows="3" minlength="20" required placeholder="Digite o motivo do cancelamento (mínimo de 20 caracteres)..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-danger font-weight-bold"><i class="fa fa-times"></i> Cancelar MTR</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- MODAL ENVIAR WHATSAPP -->
<div class="modal fade" id="modalWhatsappMtr" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="formWhatsappMtr">
            @csrf
            <input type="hidden" name="mtr_id" id="whatsMtrId">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title font-weight-bold text-white">
                        <i class="fab fa-whatsapp text-white mr-2"></i> Enviar MTR via WhatsApp
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="font-weight-bold">Número do Cliente / Motorista:</label>
                        <input type="text" name="whatsapp" id="whatsNumero" class="form-control" placeholder="(00) 00000-0000" required>
                        <small class="form-text text-muted">A mensagem incluirá o número do MTR, dados do transporte e os links para impressão (PDF e CDF).</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success font-weight-bold" id="btnDispararWhats">
                        <i class="fa fa-paper-plane"></i> Enviar Mensagem
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- MODAL IMPORTAR NF-E -->
<div class="modal fade" id="modalImportarNfe" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title font-weight-bold text-white">
                    <i class="fa fa-file-invoice text-white mr-2"></i> Importar NF-e para MTR
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive" style="max-height: 450px; overflow-y: auto;">
                    <table class="table table-bordered table-striped table-hover mb-0">
                        <thead class="thead-light sticky-top" style="z-index: 1;">
                            <tr>
                                <th>Nº Venda / NF-e</th>
                                <th>Data</th>
                                <th>Cliente</th>
                                <th class="text-center" width="120">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($vendasImportacao as $v)
                                <tr>
                                    <td class="font-weight-bold text-primary">#{{ $v->numero_nfe ?? $v->id }}</td>
                                    <td>{{ date('d/m/Y', strtotime($v->created_at)) }}</td>
                                    <td>{{ $v->cliente_nome }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('mtr.emissao.create.nfe', $v->id) }}" class="btn btn-sm btn-primary font-weight-bold">
                                            Importar
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        Nenhuma Nota Fiscal pendente de importação.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL IMPORTAR PESAGEM -->
<div class="modal fade" id="modalImportarPesagem" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title font-weight-bold text-dark">
                    <i class="fa fa-balance-scale text-dark mr-2"></i> Importar Tickets de Pesagem (Finalizadas)
                </h5>
                <button type="button" class="close text-dark" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive" style="max-height: 450px; overflow-y: auto;">
                    <table class="table table-bordered table-striped table-hover mb-0">
                        <thead class="thead-light sticky-top" style="z-index: 1;">
                            <tr>
                                <th>Ticket</th>
                                <th>Data</th>
                                <th>Cliente</th>
                                <th>Placa</th>
                                <th>Peso Líquido</th>
                                <th class="text-center" width="120">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ticketsImportacao as $t)
                                <tr>
                                    <td class="font-weight-bold text-warning text-dark">#{{ $t->id }}</td>
                                    <td>{{ date('d/m/Y H:i', strtotime($t->created_at)) }}</td>
                                    <td>{{ $t->cliente_nome }}</td>
                                    <td><span class="badge badge-secondary">{{ $t->placa_veiculo ?? $t->placa ?? '--' }}</span></td>
                                    <td class="font-weight-bold text-success">{{ number_format($t->peso_calculado, 2, ',', '.') }} Kg</td>
                                    <td class="text-center">
                                        <a href="{{ route('mtr.emissao.create.pesagem', $t->id) }}" class="btn btn-sm btn-warning text-dark font-weight-bold">
                                            Importar
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        Nenhuma pesagem finalizada pendente de importação.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
    // Máscara para o telefone (se o jQuery Mask estiver carregado no painel)
    if($.fn.mask){
        $('#whatsNumero').mask('(00) 00000-0000');
    }

    function abrirModalCancelar(id, numero_mtr) {
        $('#lblMtrCancelar').text(numero_mtr);
        $('#formCancelarMtr').attr('action', '/mtr/emissao/cancelar/' + id);
        $('#formCancelarMtr textarea[name="justificativa"]').val('');
        $('#modalCancelarMtr').modal('show');
    }

    function abrirModalWhatsapp(id, numero_mtr) {
        $('#whatsMtrId').val(id);
        $('#whatsNumero').val('');
        $('#modalWhatsappMtr').modal('show');
    }

    function consultarSinir(id) {
        swal({
            title: "Consultando SINIR...",
            text: "Verificando o status atual no portal do governo.",
            icon: "info",
            buttons: false,
            closeOnClickOutside: false
        });

        $.get('/mtr/emissao/consultar/' + id)
            .done(function(res) {
                if (res.success) {
                    swal("Sucesso!", res.message, "success").then(() => {
                        location.reload();
                    });
                } else {
                    swal("Atenção", res.message, "warning");
                }
            })
            .fail(function() {
                swal("Erro", "Falha de comunicação com o SINIR.", "error");
            });
    }

    $('#formWhatsappMtr').on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#btnDispararWhats');
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Enviando...');

        $.post('/mtr/emissao/whatsapp', $(this).serialize())
            .done(function(res) {
                if (res.success) {
                    swal("Enviado!", res.message, "success");
                    $('#modalWhatsappMtr').modal('hide');
                } else {
                    swal("Atenção", res.message, "warning");
                }
            })
            .fail(function() {
                swal("Erro", "Erro ao processar o disparo do WhatsApp.", "error");
            })
            .always(function() {
                $btn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Enviar Mensagem');
            });
    });
</script>
@endsection