@extends('default.layout')

@section('content')
    <style type="text/css">
        /* ----- GRID GERAL ----- */
        .datatable-row, .datatable-cell {
            height: 30px !important;
            vertical-align: middle !important;
        }
        .datatable-cell {
            text-align: center !important;
            padding: 4px 6px !important;
            white-space: nowrap !important;
            font-size: 0.85rem !important;
        }
        .thead-light th {
            font-size: 0.9rem !important;
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
        @media (max-width: 768px) {
            .btn-custom {
                font-size: 0.65rem !important;
                padding: 1px 4px !important;
            }
        }
        .table-instances {
            font-size: 0.85rem !important;
            max-height: 350px !important;
        }
        .table-responsive {
            overflow-x: auto !important;
            overflow-y: auto !important;
        }
        /* QR */
        #modalQr .modal-content {
            width: 290px;
            height: 440px;
        }
        #modalQr .modal-body {
            padding: 0.4rem;
        }

        /* garante z-index para modais de mensagem e confirmação */
        #modalMensagem, #modalConfirmacao { z-index: 2100; }
        #modalMensagem .modal-content, #modalConfirmacao .modal-content {
            max-width: 500px;
            word-wrap: break-word;
        }
        /* evita que o backdrop se sobreponha */
        // .modal-backdrop { z-index: 2000; }
    </style>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="card card-custom gutter-b">
        <div class="card-body">
            <h4>Configuração da Instância EvoAPI</h4>

            @if(session('mensagem_sucesso'))
                <div class="alert alert-success alert-dismissible fade show">
                    {!! session('mensagem_sucesso') !!}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            @endif
            @if(session('mensagem_erro'))
                <div class="alert alert-danger alert-dismissible fade show">
                    {!! session('mensagem_erro') !!}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            @endif

            <label>Total de instâncias: {{ count($records) }}</label>

            <div class="table-responsive table-instances mt-3">
                <table class="table table-bordered table-hover">
                    <thead class="thead-light">
                    <tr class="datatable-row">
                        <th>Nome</th>
                        <th>API Key</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($records as $inst)
                        <tr class="datatable-row"
                            data-id="{{ $inst->id }}"
                            data-empresa="{{ $inst->empresa_id }}"
                            data-key="{{ $inst->api_key }}">
                            <td class="datatable-cell name-cell">
                                {{ $inst->name }}
                            </td>
                            <td class="datatable-cell key-cell">
                                <code>{{ $inst->api_key }}</code>
                            </td>
                            <td class="datatable-cell">
                                <span id="led-{{ $inst->id }}" class="status-led"></span>
                            </td>
                            <td class="datatable-cell">
                                <!-- 1) Copiar token -->
                                <button class="btn btn-info btn-sm btn-custom btn-copy-token" data-id="{{ $inst->id }}">
                                    <i class="fa fa-clipboard"></i>
                                </button>
                                <!-- 2) Criar/Recuperar instância -->
                                <button class="btn btn-success btn-sm btn-custom btn-create-api" data-id="{{ $inst->id }}">
                                    <i class="fa fa-plug"></i>
                                </button>
                                <!-- 3) Gerar novo token -->
                                <button class="btn btn-warning btn-sm btn-custom btn-gen-token" data-id="{{ $inst->id }}">
                                    <i class="fa fa-key"></i>
                                </button>
                                <!-- 4) QR Code -->
                                <button class="btn btn-secondary btn-sm btn-custom btn-qr"
                                        data-id="{{ $inst->id }}"
                                        data-toggle="modal" data-target="#modalQr">
                                    <i class="fa fa-qrcode"></i>
                                </button>
                                <!-- 5) Enviar WhatsApp -->
                                <button class="btn btn-primary btn-sm btn-custom btn-open-whatsapp" data-id="{{ $inst->id }}">
                                    <i class="fa fa-whatsapp"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal QR Code EvoAPI --}}
    <div class="modal fade" id="modalQr" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:320px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Escaneie o QR Code</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body text-center"
                     style="height:200px; display:flex; align-items:center; justify-content:center;">
                    <div id="qr-container" style="position:relative; width:100%; max-width:280px;">
                        <img id="qr-img" src="" alt="QR Code" style="width:100%; height:auto; display:block;" />
                        @if(env('EVO_QR_LOGO_BASE64'))
                            <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); width:00%; height:00%;">
                                <img src="data:image/png;base64,{{ env('EVO_QR_LOGO_BASE64') }}"
                                     style="width:100%; height:100%; object-fit:contain;" />
                            </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="refreshQr" class="btn btn-light btn-sm">⟳ Atualizar</button>
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Reutilizável de Confirmação --}}
    <div class="modal fade" id="modalConfirmacao" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="modalConfirmacaoLabel" class="modal-title">Confirmação</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div id="modalConfirmacaoMensagem" class="modal-body">
                    Deseja realmente continuar?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Não</button>
                    <button type="button" id="btnConfirmarAcao" class="btn btn-primary">Sim</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Reutilizável de Mensagem --}}
    <div class="modal fade" id="modalMensagem" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="modalMensagemLabel" class="modal-title">Mensagem</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div id="modalMensagemTexto" class="modal-body">
                    Mensagem exibida aqui.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Unificado de WhatsApp -->
    <div class="modal fade" id="modalWhatsApp" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered"><!-- centralizado -->
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Enviar WhatsApp</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="wh_inst_id">

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Telefone (DDDNúmero)</label>
                            <input type="tel" id="wh_number" class="form-control" placeholder="5511999999999">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Modo</label>
                            <select id="wh_mode" class="form-control">
                                <option value="text">Texto</option>
                                <option value="files">Arquivos</option>
                                <option value="base64">Base64</option>
                            </select>
                        </div>
                    </div>

                    {{-- Texto --}}
                    <div id="wh_panel_text" class="form-group">
                        <label>Mensagem</label>
                        <textarea id="wh_text_msg" class="form-control" rows="4"></textarea>
                    </div>

                    {{-- Arquivos --}}
                    <div id="wh_panel_files" class="form-group d-none">
                        <label>Selecione arquivos</label>
                        <input type="file" id="wh_files_input" class="form-control-file" multiple>
                        <small class="form-text text-muted">Imagens, PDF etc.</small>
                        <ul id="wh_file_list" class="list-unstyled mt-2"></ul>
                    </div>

                    {{-- Base64 --}}
                    <div id="wh_panel_base64" class="form-group d-none">
                        <label>Cole strings Base64 (uma por linha) ou converta arquivo:</label>
                        <textarea id="wh_base64_input" class="form-control" rows="4"></textarea>
                        <div class="mt-2">
                            <input type="file" id="wh_convert_file" class="form-control-file d-inline-block" style="width:auto;">
                            <button id="wh_convert_btn" class="btn btn-secondary btn-sm ml-2">Converter para Base64</button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                    <button id="wh_send_btn" class="btn btn-primary">Enviar</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    <script src="{{ asset('js/axios.min.js') }}"></script>
    <script>
        $(function(){
            let qrInterval, _whFilesData = [];

            // --- utilitário AJAX para status ---
            function checkStatus(id){
                return axios.get(`/evo-instances/status/${id}`)
                    .then(r => {
                        const st = (r.data.data.state||'').toLowerCase();
                        const color = st==='open'       ? 'green'
                            : st==='connecting'? 'blue'
                                : st==='closed'    ? 'orange'
                                    : 'red';
                        $(`#led-${id}`).css('background', color);
                        return st;
                    })
                    .catch(()=> null);
            }

            // --- copiar token ---
            $('.btn-copy-token').click(function(){
                let key = $(this).closest('tr').data('key');
                navigator.clipboard.writeText(key).then(()=>{
                    showMessage('Sucesso','<p>Token copiado para a área de transferência.</p>','success');
                });
            });

            // --- criar ou recuperar instância ---
            $('.btn-create-api').click(function(){
                let id = $(this).data('id');
                confirmAction('Criar instância','Deseja criar/recuperar esta instância?', ()=>{
                    axios.post(`/evo-instances/create-api/${id}`, {}, { headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'} })
                        .then(()=> location.reload())
                        .catch(e=> showMessage('Erro','<p>'+ (e.response?.data?.error||e.message) +'</p>','danger'));
                });
            });

            // --- gerar novo token ---
            $('.btn-gen-token').click(function(){
                let row = $(this).closest('tr'), id = row.data('id');
                let msg = `
                <p><strong>Atenção:</strong> ao gerar um novo token, a instância será excluída e recriada na EvoAPI,
                desconectando o dispositivo atual.</p>
                <p>Deseja continuar?</p>`;
                confirmAction('Gerar novo token', msg, ()=>{
                    axios.post(`/evo-instances/credentials-tenant/${id}`, {}, { headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'} })
                        .then(({data})=>{
                            row.find('.name-cell').text(data.instance_name);
                            row.find('.key-cell code').text(data.api_key);
                            showMessage('Sucesso','<p>Token atualizado com sucesso.</p>','success');
                        })
                        .catch(e=> showMessage('Erro','<p>'+ (e.response?.data?.error||e.message) +'</p>','danger'));
                });
            });

            // --- QR Code ---
            $('.btn-qr').click(function(e){
                e.preventDefault();
                let id = $(this).data('id');
                checkStatus(id).then(state=>{
                    if(state==='open'){
                        showMessage('Aviso','<p>Instância já conectada.</p>','danger');
                    } else {
                        $('#modalQr').data('instId',id).modal('show');
                        loadQr(id);
                    }
                });
            });
            function loadQr(id){
                axios.get(`/evo-instances/qr/${id}`)
                    .then(r=>{
                        if(r.data.success){
                            let uri = r.data.qr_base64.startsWith('data:')
                                ? r.data.qr_base64
                                : 'data:image/png;base64,'+r.data.qr_base64;
                            $('#qr-img').attr('src',uri);
                        } else console.error(r.data.error);
                    }).catch(console.error);
            }
            $('#modalQr').on('show.bs.modal',function(){
                clearInterval(qrInterval);
                qrInterval = setInterval(()=>{
                    checkStatus($(this).data('instId')).then(s=>{
                        if(s==='open'){
                            clearInterval(qrInterval);
                            $('#modalQr').modal('hide');
                        }
                    });
                },1000);
            }).on('hidden.bs.modal',()=> clearInterval(qrInterval));
            $('#refreshQr').click(()=> loadQr($('#modalQr').data('instId')));

            // --- abrir WhatsApp ---
            $('.btn-open-whatsapp').click(function(){
                $('#wh_inst_id').val($(this).data('id'));
                $('#wh_number,#wh_text_msg,#wh_base64_input').val('');
                $('#wh_file_list').empty();
                $('#wh_files_input,#wh_convert_file').val(null);
                _whFilesData = [];
                $('#wh_mode').val('text').trigger('change');
                $('#modalWhatsApp').modal('show');
            });

            // --- alternar painéis ---
            $('#wh_mode').change(function(){
                let m = $(this).val();
                $('#wh_panel_text').toggleClass('d-none', m!=='text');
                $('#wh_panel_files').toggleClass('d-none', m!=='files');
                $('#wh_panel_base64').toggleClass('d-none', m!=='base64');
            });

            // --- ler arquivos ---
            $('#wh_files_input').on('change',function(){
                _whFilesData = [];
                $('#wh_file_list').empty();
                Array.from(this.files).forEach(f=>{
                    $('<li>').text(f.name).appendTo('#wh_file_list');
                    let reader = new FileReader();
                    reader.onload = ()=> _whFilesData.push({ name:f.name, data:reader.result });
                    reader.readAsDataURL(f);
                });
            });

            // --- converter arquivo em Base64 ---
            $('#wh_convert_btn').click(function(){
                const file = $('#wh_convert_file')[0].files[0];
                if(!file){
                    showMessage('Erro','<p>Selecione um arquivo para converter.</p>','danger');
                    return;
                }
                let reader = new FileReader();
                reader.onload = ()=> {
                    let cur = $('#wh_base64_input').val();
                    $('#wh_base64_input').val( cur + (cur?'\n':'') + reader.result );
                };
                reader.readAsDataURL(file);
            });

            // --- enviar WhatsApp ---
            $('#wh_send_btn').click(function(){
                const instId = $('#wh_inst_id').val();
                const number = $('#wh_number').val().trim();
                const mode   = $('#wh_mode').val();
                const text   = $('#wh_text_msg').val().trim();
                const raws   = $('#wh_base64_input').val()
                    .split(/\r?\n/).map(l=>l.trim()).filter(l=>l);

                if(!number){
                    showMessage('Erro','<p>Informe o número de destino.</p>','danger');
                    return;
                }

                // 1) confirma status
                checkStatus(instId).then(state=>{
                    if(state!=='open'){
                        showMessage('Erro','<p>API não conectada. Escaneie o QR Code primeiro.</p>','danger');
                        return;
                    }
                    // 2) confirma envio
                    let tipo = mode==='text' ? 'mensagem de texto' : mode==='files' ? 'arquivos' : 'dados Base64';
                    confirmAction('Confirmar envio',
                        `<p>Enviar <strong>${tipo}</strong> para <strong>${number}</strong>?</p>`,
                        () => doSend(instId, number, mode, text, raws));
                });
            });

            function doSend(instId, number, mode, text, raws){
                let payload = { number, mode };
                if(mode==='text')   payload.text  = text;
                if(mode==='files')  payload.files = _whFilesData;
                if(mode==='base64') payload.raws  = raws;

                let btn = $('#wh_send_btn').prop('disabled', true).text('Enviando…');
                axios.post(`/evo-instances/send-whatsapp/${instId}`, payload, {
                    headers:{ 'X-CSRF-TOKEN':'{{ csrf_token() }}' }
                }).then(()=>{
                    showMessage('Sucesso','<p>Enviado com sucesso!</p>','success');
                }).catch(e=>{
                    showMessage('Erro','<p>'+ (e.response?.data?.error||e.message) +'</p>','danger');
                }).finally(()=>{
                    btn.prop('disabled', false).text('Enviar');
                });
            }

            // --- ao fechar WhatsApp, reseta botão ---
            $('#modalWhatsApp').on('hidden.bs.modal',function(){
                $('#wh_send_btn').prop('disabled', false).text('Enviar');
            });

            // --- modais genéricos ---
            function showMessage(title, html, type){
                // fecha tudo
                $('.modal').modal('hide');
                $('.modal-backdrop').remove();
                // ajusta conteúdo
                $('#modalMensagemLabel').text(title);
                $('#modalMensagemTexto').html(html);
                $('#modalMensagem .modal-header')
                    .removeClass('bg-success bg-danger')
                    .addClass(type==='success'?'bg-success':'bg-danger')
                    .find('.modal-title').addClass('text-white');
                // exibe
                $('#modalMensagem').modal({ backdrop:'static' });
            }
            function confirmAction(title, html, onConfirm){
                // fecha mensagem anterior
                $('#modalMensagem').modal('hide');
                $('#modalConfirmacaoLabel').text(title);
                $('#modalConfirmacaoMensagem').html(html);
                $('#modalConfirmacao').modal({ backdrop:'static' });
                $('#btnConfirmarAcao').off('click').on('click', ()=>{
                    $('#modalConfirmacao').modal('hide');
                    onConfirm();
                });
            }

            $('span.status-led').each(function(){
                const id = this.id.split('-')[1];
                checkStatus(id);
            });
        });
    </script>
@endsection
