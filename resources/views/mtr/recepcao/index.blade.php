@extends('default.layout')

@section('content')
<style>
    .section-title { font-size: 1.1rem; font-weight: bold; color: #1e1e2d; border-left: 4px solid #0bb783; padding-left: 10px; margin-bottom: 15px; }
    .box-recebimento { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 20px; margin-bottom: 25px; }
</style>

<div class="card card-custom gutter-b">
    <div class="card-header border-0 py-5">
        <h3 class="card-title align-items-start flex-column">
            <span class="card-label font-weight-bolder text-dark">
                <i class="fa fa-clipboard-list text-success mr-2"></i> Recepção e Transporte de MTR (Portal SINIR)
            </span>
            <span class="text-muted mt-1 font-size-sm">Consulta de manifestos externos e recebimento de cargas (Limite de 5 dias por consulta)</span>
        </h3>
    </div>

    <div class="card-body">
        @if(session('sucesso'))
            <div class="alert alert-custom alert-light-success fade show mb-4">
                <div class="alert-icon"><i class="fa fa-check-circle"></i></div>
                <div class="alert-text font-weight-bold">{{ session('sucesso') }}</div>
            </div>
        @endif

        @if(session('erro'))
            <div class="alert alert-custom alert-light-danger fade show mb-4">
                <div class="alert-icon"><i class="fa fa-exclamation-triangle"></i></div>
                <div class="alert-text font-weight-bold">{{ session('erro') }}</div>
            </div>
        @endif

        <div class="mb-5">
            <div class="section-title">Meus MTRs como Transportador</div>
            
            <form method="GET" action="{{ route('mtr.recepcao.index') }}" class="box-recebimento">
                <div class="row align-items-end">
                    <div class="col-md-3 form-group mb-0">
                        <label class="font-weight-bold">Data Inicial:</label>
                        <input type="date" name="dt_inicio_transp" class="form-control form-control-sm" value="{{ request('dt_inicio_transp', date('Y-m-d', strtotime('-5 days'))) }}">
                    </div>
                    <div class="col-md-3 form-group mb-0">
                        <label class="font-weight-bold">Data Final (Max. 5 dias):</label>
                        <input type="date" name="dt_fim_transp" class="form-control form-control-sm" value="{{ request('dt_fim_transp', date('Y-m-d')) }}">
                    </div>
                    <div class="col-md-4 form-group mb-0">
                        <label class="font-weight-bold">MTR Nº (Opcional):</label>
                        <input type="text" name="mtr_transp" class="form-control form-control-sm" value="{{ request('mtr_transp') }}" placeholder="Número do manifesto">
                    </div>
                    <div class="col-md-2 form-group mb-0">
                        <button type="submit" class="btn btn-sm btn-success font-weight-bold w-100">
                            <i class="fa fa-search"></i> Pesquisa
                        </button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-head-custom">
                    <thead class="thead-light">
                        <tr>
                            <th>MTR Nº</th>
                            <th>Data Emissão</th>
                            <th>Gerador</th>
                            <th>Destinador</th>
                            <th>Situação</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mtrsTransportador as $t)
                            <tr>
                                <td><strong>{{ $t['numeroMtr'] ?? '--' }}</strong></td>
                                <td>{{ isset($t['dataEmissao']) ? date('d/m/Y', $t['dataEmissao']/1000) : '--' }}</td>
                                <td>{{ $t['geradorNome'] ?? '--' }}</td>
                                <td>{{ $t['destinadorNome'] ?? '--' }}</td>
                                <td><span class="badge badge-light-success font-weight-bold">{{ $t['situacao'] ?? 'Ativo' }}</span></td>
                                <td class="text-center">
                                    <a href="#" class="btn btn-xs btn-primary" title="Imprimir"><i class="fa fa-print"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Nenhum registro encontrado como transportador no período.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <hr class="my-6">

        <div>
            <div class="section-title" style="border-left-color: #3699ff;">Meus MTRs como Destinador</div>

            <div class="row">
                <div class="col-md-6">
                    <div class="box-recebimento bg-white">
                        <h6 class="text-primary font-weight-bold mb-3"><i class="fa fa-check-circle mr-1"></i> Receber MTR Oficial</h6>
                        <div class="form-group mb-0">
                            <label class="font-weight-bold">Número do MTR:</label>
                            <div class="input-group">
                                <input type="text" id="inputMtrBuscaRapida" class="form-control" placeholder="Ex: 291032062524" autocomplete="off">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-primary font-weight-bold" onclick="consultarEAbirRecebimento()">
                                        <i class="fa fa-search"></i> Consultar & Receber
                                    </button>
                                </div>
                            </div>
                            <small class="form-text text-muted">Digite o MTR para abrir a conferência de quantidades.</small>
                        </div>
                    </div>
                </div>
          
                <div class="col-md-6">
                    <div class="box-recebimento bg-white">
                        <h6 class="text-info font-weight-bold mb-3"><i class="fa fa-file-alt mr-1"></i> Receber MTR Provisório</h6>
                        <form action="{{ route('mtr.recepcao.provisorio') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 form-group mb-0">
                                    <label class="font-weight-bold">Número MTR Provisório:</label>
                                    <input type="text" name="numero_provisorio" class="form-control" placeholder="Nº Provisório" required>
                                </div>
                                <div class="col-md-6 form-group mb-0">
                                    <label class="font-weight-bold">CNPJ do Gerador:</label>
                                    <input type="text" name="cnpj_gerador" class="form-control" placeholder="00.000.000/0000-00" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-info font-weight-bold mt-3">
                                <i class="fa fa-download"></i> Receber Provisório
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <form method="GET" action="{{ route('mtr.recepcao.index') }}" class="box-recebimento">
                <div class="row align-items-end">
                    <div class="col-md-3 form-group mb-0">
                        <label class="font-weight-bold">Data Inicial:</label>
                        <input type="date" name="dt_inicio_dest" class="form-control form-control-sm" value="{{ request('dt_inicio_dest', date('Y-m-d', strtotime('-5 days'))) }}">
                    </div>
                    <div class="col-md-3 form-group mb-0">
                        <label class="font-weight-bold">Data Final (Max. 5 dias):</label>
                        <input type="date" name="dt_fim_dest" class="form-control form-control-sm" value="{{ request('dt_fim_dest', date('Y-m-d')) }}">
                    </div>
                    <div class="col-md-4 form-group mb-0">
                        <label class="font-weight-bold">MTR Nº (Opcional):</label>
                        <input type="text" name="mtr_dest" class="form-control form-control-sm" value="{{ request('mtr_dest') }}" placeholder="Número do manifesto">
                    </div>
                    <div class="col-md-2 form-group mb-0">
                        <button type="submit" class="btn btn-sm btn-primary font-weight-bold w-100">
                            <i class="fa fa-search"></i> Pesquisa
                        </button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-head-custom">
                    <thead class="thead-light">
                        <tr>
                            <th>MTR Nº</th>
                            <th>Data Emissão</th>
                            <th>Gerador</th>
                            <th>Transportador</th>
                            <th>Situação</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mtrsDestinador as $d)
                            <tr>
                                <td><strong>{{ $d['numeroMtr'] ?? '--' }}</strong></td>
                                <td>{{ isset($d['dataEmissao']) ? date('d/m/Y', $d['dataEmissao']/1000) : '--' }}</td>
                                <td>{{ $d['geradorNome'] ?? '--' }}</td>
                                <td>{{ $d['transportadorNome'] ?? '--' }}</td>
                                <td><span class="badge badge-light-primary font-weight-bold">{{ $d['situacao'] ?? 'Ativo' }}</span></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-xs btn-success font-weight-bold" onclick="abrirModalRecebimento('{{ $d['numeroMtr'] ?? '' }}')" title="Receber MTR">
                                        <i class="fa fa-check"></i> Receber
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Nenhum registro encontrado como destinador no período.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRecebimentoMtr" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form id="formRecebimentoMtr">
            @csrf
            <input type="hidden" name="numero_mtr" id="recNumeroMtr">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title font-weight-bold text-white">
                        <i class="fa fa-check-circle mr-2"></i> Recebimento de MTR
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row bg-light p-3 rounded mb-3">
                        <div class="col-md-4">
                            <small class="text-muted d-block">Número do MTR</small>
                            <strong id="lblRecMtr" class="text-primary font-size-h6">--</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Placa do Veículo</small>
                            <strong id="lblRecPlaca" class="font-size-h6">--</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Data de Recebimento</small>
                            <input type="date" name="data_recebimento" class="form-control form-control-sm mt-1" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Responsável pelo Recebimento: <span class="text-danger">*</span></label>
                        <input type="text" name="responsavel" class="form-control" placeholder="Nome do responsável pelo recebimento no pátio" required>
                    </div>

                    <h6 class="font-weight-bold text-dark mt-4 mb-2"><i class="fa fa-boxes mr-1"></i> Lista de Resíduos</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="thead-light">
                                <tr>
                                    <th>Resíduo</th>
                                    <th>Un.</th>
                                    <th>Tratamento</th>
                                    <th>Qtd. Emitida</th>
                                    <th width="150">Qtd. Recebida</th>
                                </tr>
                            </thead>
                            <tbody id="tabelaResiduosRecebimento">
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Carregando itens...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success font-weight-bold" id="btnSalvarRecebimento">
                        <i class="fa fa-download"></i> Confirmar Recebimento no SINIR
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('javascript')
<script>
    function abrirModalRecebimento(numeroMtr) {
        if(!numeroMtr) {
            numeroMtr = $('#inputMtrBuscaRapida').val();
        }
        if(!numeroMtr) {
            swal("Atenção", "Informe o número do MTR.", "warning");
            return;
        }

        swal({ title: "Buscando MTR...", text: "Consultando dados no SINIR", icon: "info", buttons: false, closeOnClickOutside: false });

        $.get('/mtr/detalhes/' + numeroMtr)
            .done(function(res) {
                swal.close();
                if(res.success) {
                    var d = res.data;
                    $('#recNumeroMtr').val(d.numeroMtr || numeroMtr);
                    $('#lblRecMtr').text(d.numeroMtr || numeroMtr);
                    $('#lblRecPlaca').text(d.veiculoPlaca || '--');
                    
                    var htmlRows = '';
                    if(d.residuos && d.residuos.length > 0) {
                        d.residuos.forEach(function(r, index) {
                            htmlRows += `<tr>
                                <td>${r.codigoIbama} - ${r.descricaoResiduo}</td>
                                <td>${r.unidadeMedidaNome || 'TON'}</td>
                                <td>${r.tratamentoNome || 'Reciclagem'}</td>
                                <td><strong>${r.quantidade}</strong></td>
                                <td>
                                    <input type="hidden" name="residuos[${index}][id]" value="${r.id || index}">
                                    <input type="hidden" name="residuos[${index}][cod_ibama]" value="${r.codigoIbama}">
                                    <input type="hidden" name="residuos[${index}][descricao]" value="${r.descricaoResiduo}">
                                    <input type="text" name="residuos[${index}][quantidadeRecebida]" class="form-control form-control-sm text-center font-weight-bold" value="${r.quantidade}" required>
                                </td>
                            </tr>`;
                        });
                    } else {
                        htmlRows = `<tr><td colspan="5" class="text-center text-muted">Nenhum resíduo retornado.</td></tr>`;
                    }
                    $('#tabelaResiduosRecebimento').html(htmlRows);
                    $('#modalRecebimentoMtr').modal('show');
                } else {
                    swal("Erro", res.message, "error");
                }
            })
            .fail(function() {
                swal.close();
                swal("Erro", "Falha ao consultar o MTR no servidor.", "error");
            });
    }

    function consultarEAbirRecebimento() {
        var num = $('#inputMtrBuscaRapida').val();
        abrirModalRecebimento(num);
    }

    $('#formRecebimentoMtr').on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#btnSalvarRecebimento');
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processando...');

        $.post('/mtr/receber-completo', $(this).serialize())
            .done(function(res) {
                if(res.success) {
                    swal("Sucesso!", res.message, "success").then(() => { location.reload(); });
                } else {
                    swal("Atenção", res.message, "warning");
                    $btn.prop('disabled', false).html('<i class="fa fa-download"></i> Confirmar Recebimento no SINIR');
                }
            })
            .fail(function() {
                swal("Erro", "Erro ao enviar o recebimento.", "error");
                $btn.prop('disabled', false).html('<i class="fa fa-download"></i> Confirmar Recebimento no SINIR');
            });
    });
</script>
@endsection