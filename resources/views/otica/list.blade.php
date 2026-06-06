@extends('default.layout')
@section('content')
    <div class="card card-custom gutter-b">
        <div class="card-header border-0 pt-6">
            <h3 class="card-title align-items-start flex-column">
                <span class="card-label font-weight-bolder font-size-h3 text-dark">Lista de Ordens de Serviço (Ótica)</span>
            </h3>
            <div class="card-toolbar">
                <button type="button" class="btn btn-light-info font-weight-bolder mr-2" data-toggle="modal" data-target="#modalInstrucoesLista">
                    <i class="fa fa-info-circle"></i> Como funciona a rotina?
                </button>
                <a href="{{ route('otica.create') }}" class="btn btn-success font-weight-bolder">
                    <i class="fa fa-plus"></i> Nova OS / Receita
                </a>
            </div>
        </div>

        <div class="modal fade" id="modalInstrucoesLista" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary">
                        <h5 class="modal-title text-white font-weight-bold">Guia Operacional: Gestão de OS Ótica</h5>
                    </div>
                    <div class="modal-body">
                        <h6 class="font-weight-bolder text-dark">1. Fluxo de Status (Linear)</h6>
                        <p class="text-muted">As ordens de serviço seguem uma ordem lógica. Você só pode avançar o status (ex: de Orçamento para Laboratório). O sistema impede o retrocesso para garantir a integridade do processo.</p>
                        <hr>
                        <h6 class="font-weight-bolder text-dark">2. Notificações Automáticas</h6>
                        <p class="text-muted">Ao mudar o status para <span class="label label-light-success label-inline font-weight-bold">Pronto</span>, o cliente receberá automaticamente uma mensagem no WhatsApp informando que já pode retirar o óculos.</p>
                        <hr>
                        <h6 class="font-weight-bolder text-dark">3. Faturamento e Travas</h6>
                        <p class="text-muted">Após faturar (PDV ou NF-e), a OS será bloqueada para edição. Isso garante que os dados técnicos coincidam sempre com a venda realizada.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">Entendi</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modalEnviarWhatsDireto" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title text-white font-weight-bold"><i class="fa fa-whatsapp text-white"></i> Enviar Mensagem via WhatsApp</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="white">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="whats_modal_numero">
                        <div class="form-group">
                            <label class="font-weight-bold">Cliente</label>
                            <input type="text" id="whats_modal_cliente" class="form-control bg-light" readonly>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold">Mensagem</label>
                            <textarea id="whats_modal_mensagem" class="form-control" rows="5" placeholder="Digite a mensagem para o cliente..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-success font-weight-bolder" onclick="processarEnvioWhatsDireto()">
                            <i class="fa fa-paper-plane"></i> Enviar Agora
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('otica.list') }}" class="mb-7">
                @csrf
                <div class="row align-items-center bg-light p-4 rounded">
                    <div class="col-md-4 form-group mb-0">
                        <label class="font-weight-bold">Pesquisar Cliente</label>
                        <input type="text" name="cliente" class="form-control" placeholder="Nome ou CPF..." value="{{ $cliente ?? '' }}">
                    </div>
                    <div class="col-md-4 form-group mb-0">
                        <label class="font-weight-bold">Filtrar por Status</label>
                        <select name="status" class="form-control">
                            <option value="">Todos</option>
                            <option value="orcamento">Orçamento / Aguardando</option>
                            <option value="pendente">Aguardando Laboratório</option>
                            <option value="laboratorio">Em Production no Lab.</option>
                            <option value="conferencia">Conferência / Qualidade</option>
                            <option value="pronto">Pronto para Retirada</option>
                            <option value="entregue">Entregue / Faturado</option>
                        </select>
                    </div>
                    <div class="col-md-4 form-group mb-0 text-right">
                        <button type="submit" class="btn btn-primary font-weight-bold mt-7 px-8">
                            <i class="fa fa-search"></i> Filtrar
                        </button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-head-custom table-vertical-center table-bordered table-hover">
                    <thead class="thead-light">
                    <tr>
                        <th width="80" class="text-center">ID</th>
                        <th>Cliente</th>
                        <th class="text-center" width="120">Data</th>
                        <th class="text-center" width="180">Status da OS</th>
                        <th class="text-right" width="130">Total (R$)</th>
                        <th width="240" class="text-center">Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($data as $item)
                        <tr>
                            <td class="text-center font-weight-bold text-muted">#{{ $item->id }}</td>
                            <td>
                                <span class="text-dark-75 font-weight-bolder d-block font-size-lg">{{ $item->cliente->razao_social ?? 'Não Identificado' }}</span>
                            </td>
                            <td class="text-center font-weight-bold">
                                {{ $item->data && $item->data != '0000-00-00' ? \Carbon\Carbon::parse($item->data)->format('d/m/Y') : 'N/D' }}
                            </td>
                            <td class="text-center">
                                @php
                                    $niveis = ['orcamento' => 1, 'pendente' => 2, 'laboratorio' => 3, 'conferencia' => 4, 'pronto' => 5, 'entregue' => 6];
                                    $nivelAtual = $niveis[$item->status] ?? 0;
                                @endphp
                                <select class="form-control form-control-sm status-rapido font-weight-bold"
                                        data-id="{{ $item->id }}"
                                        data-atual="{{ $item->status }}"
                                        style="{{ $item->status == 'entregue' ? 'background-color: #c9f7f5; color: #1bc5bd; border-color: #1bc5bd;' : 'background-color: #f3f6f9; color: #3f4254;' }}">

                                    <option value="orcamento" {{ $item->status == 'orcamento' ? 'selected' : '' }} {{ $nivelAtual > 1 ? 'disabled' : '' }}>Orçamento</option>
                                    <option value="pendente" {{ $item->status == 'pendente' ? 'selected' : '' }} {{ $nivelAtual > 2 ? 'disabled' : '' }}>Aguardando Lab.</option>
                                    <option value="laboratorio" {{ $item->status == 'laboratorio' ? 'selected' : '' }} {{ $nivelAtual > 3 ? 'disabled' : '' }}>Em Produção</option>
                                    <option value="conferencia" {{ $item->status == 'conferencia' ? 'selected' : '' }} {{ $nivelAtual > 4 ? 'disabled' : '' }}>Conferência</option>
                                    <option value="pronto" {{ $item->status == 'pronto' ? 'selected' : '' }} {{ $nivelAtual > 5 ? 'disabled' : '' }}>Pronto</option>
                                    <option value="entregue" disabled {{ $item->status == 'entregue' ? 'selected' : '' }}>Entregue / Faturado</option>
                                </select>
                            </td>
                            <td class="text-right font-weight-bolder text-success font-size-lg">
                                R$ {{ number_format(($item->valor_lente ?? 0) + ($item->valor_armacao ?? 0), 2, ',', '.') }}
                            </td>

                            <td class="text-center">
                                <div class="d-flex justify-content-center align-items-center">

                                    @if(!empty($item->anexo_receita))
                                        <a href="{{ asset('storage/' . $item->anexo_receita) }}" target="_blank" class="btn btn-icon btn-light-primary btn-sm mr-1" title="Ver Receita Anexada">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                    @endif

                                    <a href="{{ route('otica.imprimirOS', $item->id) }}" target="_blank" class="btn btn-icon btn-light-dark btn-sm mr-1" title="Imprimir OS Técnica (Laboratório)">
                                        <i class="fa fa-print"></i>
                                    </a>

                                    <a href="{{ route('otica.imprimirRecibo', $item->id) }}" target="_blank" class="btn btn-icon btn-light-info btn-sm mr-1" title="Imprimir Recibo do Cliente">
                                        <i class="fa fa-file-text-o"></i>
                                    </a>

                                    @php
                                        $numeroWhats = !empty($item->cliente->whatsapp) ? $item->cliente->whatsapp : ($item->cliente->telefone ?? '');
                                        $clienteNome = $item->cliente->razao_social ?? 'Cliente';
                                        $textoDefault = "Olá, " . $clienteNome . "! Gostaria de falar sobre o andamento da sua Ordem de Serviço #" . $item->id . ".";
                                    @endphp

                                    <button type="button"
                                            class="btn btn-icon btn-light-success btn-sm mr-1"
                                            title="Enviar Mensagem Interna"
                                            onclick="abrirModalWhatsDireto('{{ $numeroWhats }}', '{{ $clienteNome }}', '{{ $textoDefault }}')">
                                        <i class="fa fa-whatsapp" style="color: #25D366; font-size: 16px; font-weight: bold;"></i>
                                    </button>

                                    @if($item->status != 'entregue')
                                        <a href="{{ route('otica.edit', $item->id) }}" class="btn btn-icon btn-light-primary btn-sm mr-1" title="Editar OS">
                                            <i class="fa fa-edit"></i>
                                        </a>

                                        <a href="{{ route('otica.faturar', ['id' => $item->id, 'tipo' => 'pdv']) }}" class="btn btn-icon btn-light-success btn-sm mr-1" title="Venda Rápida PDV (NFC-e)" onclick="return confirm('Deseja enviar para a Frente de Caixa (PDV)?')">
                                            <i class="fa fa-shopping-cart"></i>
                                        </a>

                                        <a href="{{ route('otica.faturar', ['id' => $item->id, 'tipo' => 'nfe']) }}" class="btn btn-icon btn-light-warning btn-sm mr-1" title="Venda Completa (NF-e)" onclick="return confirm('Deseja enviar para as Vendas (NF-e)?')">
                                            <i class="fa fa-file-text"></i>
                                        </a>

                                        <a href="/otica/delete/{{ $item->id }}" class="btn btn-icon btn-light-danger btn-sm" onclick="return confirm('Excluir esta OS definitivamente?')" title="Excluir OS">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    @else
                                        <span class="label label-light-success label-inline font-weight-bold ml-2 px-2">Faturado</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center flex-wrap mt-5">{{ $data->links() }}</div>
        </div>
    </div>
@endsection

@section('javascript')
    <script>
        $(document).on('change', '.status-rapido', function() {
            let select = $(this);
            let idOS = select.data('id');
            let novoStatus = select.val();
            let statusAnterior = select.data('atual');

            select.prop('disabled', true);

            $.post("{{ route('otica.alterarStatus') }}", {
                _token: "{{ csrf_token() }}",
                id: idOS,
                status: novoStatus
            }, function(res) {
                if(res.success) {
                    select.data('atual', novoStatus);
                    select.css({'background-color': '#e1f0ff', 'color': '#3699FF'});

                    if(novoStatus === 'pronto' || novoStatus === 'entregue') {
                        window.location.reload();
                    } else {
                        select.prop('disabled', false);
                    }
                } else {
                    alert(res.message || 'O sistema não permitiu alterar o status.');
                    select.val(statusAnterior).prop('disabled', false);
                }
            }).fail(function() {
                alert('Erro ao se comunicar com o servidor para atualizar o status.');
                select.val(statusAnterior).prop('disabled', false);
            });
        });

        // FUNÇÃO PARA PREPARAR E ABRIR O MODAL DO WHATSAPP
        function abrirModalWhatsDireto(numero, cliente, texto) {
            if(!numero || numero.replace(/[^0-9]/g, '').length < 10) {
                alert('Este cliente não possui um número de WhatsApp válido cadastrado.');
                return;
            }
            $('#whats_modal_numero').val(numero);
            $('#whats_modal_cliente').val(cliente);
            $('#whats_modal_mensagem').val(texto);
            $('#modalEnviarWhatsDireto').modal('show');
        }

        // FUNÇÃO VIA AJAX QUE CHAMA O MOTOR DE ENVIO DO SEU ERP
        function processarEnvioWhatsDireto() {
            let btn = $('#modalEnviarWhatsDireto .btn-success');
            let textoOriginal = btn.html();

            let dados = {
                _token: "{{ csrf_token() }}",
                whatsapp: $('#whats_modal_numero').val(),
                mensagem: $('#whats_modal_mensagem').val()
            };

            if(!dados.mensagem.trim()) {
                alert('Por favor, digite uma mensagem antes de enviar.');
                return;
            }

            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Enviando...');

            $.ajax({
                url: "/otica/enviar-whatsapp-direto",
                type: "POST",
                data: dados,
                timeout: 8000,
                success: function(res) {
                    if(res.success) {
                        alert('Mensagem enviada com sucesso pelo sistema!');
                        $('#modalEnviarWhatsDireto').modal('hide');
                    } else {
                        alert('Aviso do sistema: ' + res.message);
                    }
                },
                error: function(xhr, textStatus, errorThrown) {
                    if(textStatus === 'timeout') {
                        alert('O envio está demorando mais que o esperado, mas foi processado em segundo plano.');
                    } else {
                        alert('Erro de comunicação com o servidor de envio (Código: ' + xhr.status + ').');
                    }
                    $('#modalEnviarWhatsDireto').modal('hide');
                },
                complete: function() {
                    btn.prop('disabled', false).html(textoOriginal);
                }
            });
        }
    </script>
@endsection
