@extends('default.layout')

@section('content')
    <style type="text/css">
        /* ----- GRID GERAL ----- */
        .datatable-row, .datatable-cell {
            height: 30px !important;               /* diminui um pouco a altura */
            vertical-align: middle !important;
        }
        .datatable-cell {
            text-align: center !important;
            padding: 4px 6px !important;           /* espaçamento reduzido */
            white-space: nowrap !important;
            font-size: 0.85rem !important;          /* fonte em rem para responsividade */
        }
        .thead-light th {
            font-size: 0.9rem !important;           /* cabeçalho um pouco maior que o corpo */
            padding: 6px 8px !important;
        }
        .btn-custom {
            padding: 2px 6px !important;
            font-size: 0.75rem !important;
            line-height: 1.2 !important;
        }
        .status-led {
            display: inline-block;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            border: 1px solid #333;
        }

        /* ajustes responsivos */
        @media (max-width: 1200px) {
            .datatable-cell { font-size: 0.8rem !important; }
            .thead-light th  { font-size: 0.85rem !important; }
        }
        @media (max-width: 992px) {
            .datatable-cell { font-size: 0.75rem !important; }
            .thead-light th  { font-size: 0.8rem !important; }
        }
        @media (max-width: 768px) {
            .datatable-cell { font-size: 0.7rem !important; }
            .thead-light th  { font-size: 0.75rem !important; }
            .btn-custom     { font-size: 0.65rem !important; padding: 1px 4px !important; }
        }

        /* ----- GRID DE INSTÂNCIAS ----- */
        .table-instances {
            font-size: 0.85rem !important;  /* padrão menor */
            max-height: 350px !important;
        }
        .table-instances th,
        .table-instances td {
            white-space: nowrap !important;
        }

        .table-responsive {
            overflow-x: auto !important;
            overflow-y: auto !important;
        }

        /* Garante altura total ≃290×440 */
        #modalQr .modal-content {
            width: 290px;
            height: 440px;
        }
        #modalQr .modal-body {
            padding: 0.4rem;
        }

    </style>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="card card-custom gutter-b">
        <div class="card-body">
            {{-- mensagens --}}
            @if(session('mensagem_sucesso'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('mensagem_sucesso') }}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            @endif
            @if(session('mensagem_erro'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('mensagem_erro') }}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            @endif

            {{-- botão Nova Instância --}}
            <div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft mb-3">
                <button id="btnNovaInstancia" class="btn btn-lg btn-success" data-toggle="modal" data-target="#modalRegisterInstance">
                    <i class="fa fa-plus"></i> Nova Instância
                </button>
            </div>

            {{-- tabela --}}
            <h4>Lista de Instâncias</h4>
            <label>Total de registros: {{ count($records) }}</label>
            <div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight table-responsive table-instances mt-3">
                <table class="table table-bordered table-hover">
                    <thead class="thead-light">
                    <tr class="datatable-row">
                        <th>ID</th>
                        <th>Empresa</th>
                        <th>Nome Instâcia</th>
                        <th>API Key</th>
                        <th>Versão</th>
                        <th>Status</th>
                        <th>Bloqueado</th>
                        <th>Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($records as $inst)
                        <tr class="datatable-row">
                            <td class="datatable-cell">{{ $inst->id }}</td>
                            <td class="datatable-cell">
                                {{ optional($inst->empresa)->nome_fantasia }}
                            </td>
                            <td class="datatable-cell">{{ $inst->name }}</td>
                            <td class="datatable-cell"><code>{{ $inst->api_key }}</code></td>
                            <td class="datatable-cell">{{ $inst->version }}</td>
                            <td class="datatable-cell">
                                <span id="led-{{ $inst->id }}" class="status-led"
                                      style="background: {{ $inst->instance_id ? 'green' : 'gray' }};">
                                </span>
                            </td>
                            <td class="datatable-cell">
                                @if($inst->is_blocked)
                                    <span class="badge badge-danger">Sim</span>
                                @else
                                    <span class="badge badge-success">Não</span>
                                @endif
                            </td>
                            <td class="datatable-cell">
                                <button type="button"
                                        class="btn btn-warning btn-sm btn-custom btn-edit"
                                        data-id="{{ $inst->id }}">
                                    <i class="fa fa-edit"></i>
                                </button>
                                <form action="{{ route('evo-instances.delete', $inst->id) }}" method="POST" style="display:inline">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-sm btn-custom"><i class="fa fa-trash"></i></button>
                                </form>
                                <button class="btn btn-info btn-sm btn-custom btn-status" data-id="{{ $inst->id }}">
                                    <i class="fa fa-sync-alt"></i>
                                </button>
                                <button class="btn btn-success btn-sm btn-custom btn-create-api" data-id="{{ $inst->id }}">
                                    <i class="fa fa-plug"></i>
                                </button>
                                <button class="btn btn-secondary btn-sm btn-custom"
                                        data-toggle="modal"
                                        data-target="#modalQr"
                                        data-id="{{ $inst->id }}">
                                    <i class="fa fa-qrcode"></i>
                                </button>
                                <form action="{{ route($inst->is_blocked ? 'evo-instances.unblock' : 'evo-instances.block', $inst->id) }}"
                                      method="POST" style="display:inline">
                                    @csrf
                                    <button class="btn btn-{{ $inst->is_blocked ? 'info' : 'dark' }} btn-sm btn-custom">
                                        <i class="fa fa-{{ $inst->is_blocked ? 'unlock' : 'lock' }}"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">Nenhuma instância cadastrada.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal Nova/Editar Instância --}}
    <div class="modal fade" id="modalRegisterInstance" tabindex="-1">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                {{-- usa a rota nomeada para salvar --}}
                <form id="formInstance" method="POST" action="{{ route('evo-instances.save') }}">
                    @csrf
                    <input type="hidden" name="id" id="instance_id" value="">
                    <input type="hidden" name="_method" id="_methodType" value="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Nova Instância EvoAPI</h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        {{-- Tenant --}}
                        <div class="form-group mb-1">
                            <label>Empresa:</label>
                            <select id="empresa_select" name="empresa_select" class="form-control" style="width:100%">
                                <option value="">Selecione uma Empresa</option>
                            </select>
                        </div>
                        <div id="empresa-detalhes"
                             class="card card-sm gutter-b mt-3"
                             style="padding:8px; display:none;">
                            <div class="card-body"
                                 style="padding:8px; display:flex; align-items:center; justify-content:space-between;">
                                <div>
                                    <h6>ID: <strong id="empresa_select_info" class="text-primary">--</strong></h6>
                                    <h6>Razão Social: <strong id="razao_social_empresa" class="text-primary">--</strong></h6>
                                    <h6>Nome Fantasia: <strong id="nome_fantasia_empresa" class="text-primary">--</strong></h6>
                                    <h6>CPF/CNPJ: <strong id="cnpj_empresa" class="text-primary">--</strong></h6>
                                    <h6>Telefone: <strong id="telefone_empresa" class="text-primary">--</strong></h6>
                                    <h6>Cidade: <strong id="cidade_empresa" class="text-primary">--</strong></h6>
                                </div>
                                <div>
                                    <img id="logo_empresa"
                                         src="/imgs/no_empresas.png"
                                         alt="Logo da empresa"
                                         style="max-width:180px; max-height:180px; object-fit:contain; border:1px solid #ddd; padding:4px; border-radius:4px;">
                                </div>
                            </div>
                        </div>

                        {{-- Nome da Instância --}}
                        <div class="form-group">
                            <label>Nome da Instância</label>
                            <div class="input-group">
                                <input type="text" id="name" name="name" class="form-control" required>
                                <div class="input-group-append">
                                    <button type="button" id="btnGenCreds" class="btn btn-outline-secondary">Gerar</button>
                                </div>
                            </div>
                        </div>

                        {{-- API Key --}}
                        <div class="form-group">
                            <label>API Key</label>
                            <div class="input-group">
                                <input type="text" id="api_key" name="api_key" class="form-control" required>
                                <div class="input-group-append">
                                    <button type="button" id="btnGenCreds2" class="btn btn-outline-secondary">Gerar</button>
                                </div>
                            </div>
                        </div>

                        {{-- Base URL --}}
                        <div class="form-group">
                            <label>Base URL</label>
                            <input type="url" id="base_url" name="base_url" class="form-control" required value="{{ config('evoapi.base_url') }}">
                        </div>

                        {{-- DDI / DDD / Versão --}}
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label>DDI</label>
                                <input type="text" id="ddi" name="ddi" class="form-control" required value="{{ config('evoapi.ddi') }}">
                            </div>
                            <div class="form-group col-md-4">
                                <label>DDD</label>
                                <input type="text" id="ddd" name="ddd" class="form-control" required value="{{ config('evoapi.ddd') }}">
                            </div>
                            <div class="form-group col-md-4">
                                <label>Versão</label>
                                <select id="version" name="version" class="form-control" required>
                                    <option value="V1">V1</option>
                                    <option value="V2">V2</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal QR Code EvoAPI -->
    <div class="modal fade" id="modalQr" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Escaneie o QR Code</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body text-center" style="height:200px; display:flex; align-items:center; justify-content:center;">
                    <div id="qr-container" style="position:relative; width:100%; max-width:280px;">
                        <img id="qr-img" src="" alt="QR Code" style="width:100%; height:auto; display:block;"/>
                        @if(env('EVO_QR_LOGO_BASE64'))
                            <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); width:00%; height:00%;">
                                <img src="data:image/png;base64,{{ env('EVO_QR_LOGO_BASE64') }}"
                                     style="width:100%; height:100%; object-fit:contain;"/>
                            </div>
                        @endif
                    </div>

                </div>
                <div class="modal-footer">
                    <button id="refreshQr" class="btn btn-light btn-sm" type="button">
                        ⟳ Atualizar
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Reutilizável -->
    <div class="modal fade" id="modalConfirmacao" tabindex="-1" role="dialog" aria-labelledby="modalConfirmacaoLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <!-- Cabeçalho -->
                <div class="modal-header">
                    <h5 class="modal-title" id="modalConfirmacaoLabel">Confirmação</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <!-- Corpo -->
                <div class="modal-body" id="modalConfirmacaoMensagem">
                    Deseja realmente continuar?
                </div>
                <!-- Rodapé -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Não</button>
                    <button type="button" id="btnConfirmarAcao" class="btn btn-primary">Sim</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Reutilizável para Alertas e Mensagens -->
    <div class="modal fade" id="modalMensagem" tabindex="-1" role="dialog" aria-labelledby="modalMensagemLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <!-- Cabeçalho -->
                <div class="modal-header">
                    <h5 class="modal-title" id="modalMensagemLabel">Mensagem</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <!-- Corpo -->
                <div class="modal-body" id="modalMensagemTexto">
                    Mensagem exibida aqui.
                </div>
                <!-- Rodapé -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('styles')
    <style>
        .clear-button {
            background: #fff;
            color: #000;
            border: none;
            padding: 4px 6px;
            cursor: pointer;
            margin-left: 4px;
            border-radius: 0;
        }
    </style>
@endsection

@section('javascript')
    <script src="{{ asset('js/axios.min.js') }}"></script>
    <script type="text/javascript">
        $(function(){
            // 1) Função para adicionar o botão de limpar
            function addClearButton(selector, callback){
                const $sel = $(selector).next('.select2-container');
                $sel.after(`<button type="button" class="clear-button" data-select="${selector}">✖</button>`);
                $(document).on('click', `.clear-button[data-select="${selector}"]`, callback);
            }

            // 2) Inicializa o Select2 de Empresas
            $('#empresa_select').select2({
                placeholder: 'Selecione uma Empresa',
                allowClear: true,
                minimumInputLength: 0,
                dropdownParent: $('#modalRegisterInstance'),
                ajax: {
                    url: '{{ route("evo-instances.tenants.search") }}',
                    dataType: 'json',
                    delay: 250,
                    data: params => ({ term: params.term }),
                    processResults: data => ({
                        results: data.map(item => ({
                            id:            item.id,
                            empresa_select:    item.empresa_select,
                            text:          item.text,
                            razao_social:  item.razao_social,
                            nome_fantasia: item.nome_fantasia,
                            cpf_cnpj:      item.cpf_cnpj,
                            telefone:      item.telefone,
                            cidade:        item.cidade,
                            img:           item.imgApp
                        }))
                    }),
                },
                templateResult: formatEmpresa,
                templateSelection: formatEmpresaSelection,
                escapeMarkup: m => m
            });

            // 3) Botão de limpar
            addClearButton('#empresa_select', () => {
                $('#empresa_select').val(null).trigger('change');
                $('#empresa-detalhes').hide();
            });

            // 4) Renderiza cada opção
            function formatEmpresa(item) {
                if (!item.id) return item.text;
                const img = item.img || '/imgs/no_empresas.png';
                return $(`
                    <div style="display:flex;align-items:center;">
                        <img src="${img}" alt="Logo"
                             style="max-width:80px;max-height:80px;object-fit:contain;border:1px solid #ddd;padding:4px;border-radius:4px;margin-right:10px;">
                        <div>
                            <div><strong></strong></div>
                            <div><strong>ID: ${item.id} - ${item.text}</strong></div>
                            <small>Razão: ${item.razao_social}</small><br>
                            <small>CPF/CNPJ: ${item.cpf_cnpj}</small><br>
                            <small>${item.cidade}</small>
                        </div>
                    </div>
                `);
            }

            // 5) Texto quando selecionado
            function formatEmpresaSelection(item) {
                return item.text || 'Selecione uma Empresa';
            }

            // 6) Preenche detalhes
            $('#empresa_select').on('select2:select', function(e){
                const d = e.params.data;
                $('#empresa_select_info').text(d.id);
                $('#logo_empresa').attr('src', d.img || '/imgs/no_empresas.png');
                $('#razao_social_empresa').text(d.razao_social);
                //$('#cpf_cnpj').text(d.cpf_cnpj);
                $('#cnpj_empresa').text(d.cpf_cnpj);
                $('#nome_fantasia_empresa').text(d.nome_fantasia);
                $('#telefone_empresa').text(d.telefone);
                $('#cidade_empresa').text(d.cidade);
                $('#empresa-detalhes').show();
            });

            // Novo
            $('#btnNovaInstancia').on('click', () => {
                $('#empresa_select').prop('disabled', false);
                $('.clear-button[data-select="#empresa_select"]').show();
                $('#formInstance').trigger('reset');
                $('#_methodType').val('POST');
                $('#empresa-detalhes').hide();
                $('#formInstance').attr('action', '{{ route("evo-instances.save") }}');
            });

            // Editar
            $(document).on('click', '.btn-edit', function(){
                const id = $(this).data('id');
                $('#instance_id').val(id);
                $('#_methodType').val('PUT');
                $('#formInstance').attr('action', `/evo-instances/update/${id}`);
                axios.get(`/evo-instances/edit/${id}`).then(({data}) => {
                    $('#name').val(data.name);
                    $('#api_key').val(data.api_key);
                    $('#base_url').val(data.base_url);
                    $('#ddi').val(data.ddi);
                    $('#ddd').val(data.ddd);
                    $('#version').val(data.version);
                    const option = new Option(data.empresa.text, data.empresa.id, true, true);
                    $('#empresa_select').append(option).trigger('change');
                    //$('#empresa_select').prop('disabled', true);
                    $('.clear-button[data-select="#empresa_select"]').hide();
                    $('#modalRegisterInstance').modal('show');
                });
            });

            // Gerar credenciais via API
            function gerarCredenciais() {
                const empresaId = $('#empresa_select').val();
                if (!empresaId) {
                    return alert('Escolha primeiro a Empresa.');
                }
                axios.get(`/evo-instances/credentials/${empresaId}`)
                    .then(({ data }) => {
                        $('#name').val(data.instance_name);
                        $('#api_key').val(data.api_key);
                    })
                    .catch(() => alert('Falha ao gerar credenciais.'));
            }

            $('#btnGenCreds, #btnGenCreds2').on('click', gerarCredenciais);

        });

    </script>

    <script>
        $(function(){
            let qrInterval;

            // helper para buscar status e atualizar LED
            function checkStatus(id) {
                return axios.get(`/evo-instances/status/${id}`)
                    .then(({data}) => {
                        if (!data.success) throw 'status fail';
                        const st = (data.data.state||'').toLowerCase();
                        let color = 'red';
                        if (st==='open')          color='green';
                        else if (st==='connecting') color='blue';
                        else if (st==='closed')    color='orange';
                        $(`#led-${id}`).css('background', color);
                        return st;
                    })
                    .catch(() => null);
            }

            // helper para carregar QR
            function loadQr(id) {
                axios.get(`/evo-instances/qr/${id}`)
                    .then(({data}) => {
                        if (!data.success) return console.error(data.error);
                        const uri = data.qr_base64.startsWith('data:')
                            ? data.qr_base64
                            : 'data:image/png;base64,' + data.qr_base64;
                        $('#qr-img').attr('src', uri);
                    })
                    .catch(console.error);
            }

            // Intercepta clique no botão de QR
            $('button[data-target="#modalQr"]')
                .off('click')
                .on('click', function(e){
                    e.preventDefault();
                    const id = $(this).data('id');

                    checkStatus(id).then(st => {
                        if (st==='open') {
                            // já conectado: mostra a mensagem e NÃO abre o modalQr
                            $('#modalMensagemLabel').text('Aviso');
                            $('#modalMensagemTexto').text('Instância já está conectada.');
                            $('#modalMensagem').modal('show');
                            return;
                        }

                        // limpa QR antigo
                        $('#qr-img').attr('src','');
                        // define o instId
                        $('#modalQr').data('instId', id);
                        // carrega NOVO QR antes de abrir
                        loadQr(id);
                        // abre o modal
                        $('#modalQr').modal('show');
                    });
                });

            // Ao abrir o modal, SÓ inicia o polling (não carrega o QR de novo)
            $('#modalQr')
                .off('show.bs.modal')
                .on('show.bs.modal', function(){
                    const id = $(this).data('instId');
                    clearInterval(qrInterval);
                    qrInterval = setInterval(() => {
                        checkStatus(id).then(st => {
                            if (st === 'open') {
                                clearInterval(qrInterval);
                                $('#modalQr').modal('hide');
                            }
                        });
                    }, 1000);
                })
                .off('hidden.bs.modal')
                .on('hidden.bs.modal', () => clearInterval(qrInterval));

            // botão atualizar dentro do modal
            $('#refreshQr').on('click', function(){
                const id = $('#modalQr').data('instId');
                if (!id) return;
                clearInterval(qrInterval);
                $('#qr-img').attr('src','');
                loadQr(id);
                qrInterval = setInterval(() => {
                    checkStatus(id).then(st => {
                        if (st==='open') {
                            clearInterval(qrInterval);
                            $('#modalQr').modal('hide');
                        }
                    });
                }, 1000);
            });

            // (Opcional) ao carregar a página, atualiza todos os LEDs
            $('span[id^="led-"]').each(function(){
                const id = this.id.split('-')[1];
                checkStatus(id);
            });
        });
    </script>



@endsection
