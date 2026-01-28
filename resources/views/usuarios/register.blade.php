@extends('default.layout')
@section('content')

    <div class=" d-flex flex-column flex-column-fluid" id="kt_content">
        <div class="card card-custom gutter-b example example-compact">
            <div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
                <div class="col-lg-12">
                    <br>
                    <form method="post" action="/usuarios/{{{ isset($usuario) ? 'update' : 'save' }}}">

                        <input type="hidden" name="id" value="{{{ isset($usuario) ? $usuario->id : 0 }}}">
                        <div class="card card-custom gutter-b example example-compact">
                            <div class="card-header">
                                <h3 class="card-title">{{isset($usuario) ? 'Editar' : 'Novo'}} Usuário</h3>
                            </div>
                        </div>
                        @csrf

                        <div class="row">
                            <div class="col-xl-12">
                                <div class="kt-section kt-section--first">
                                    <div class="kt-section__body">
                                        <div class="row">
                                            <div class="form-group validated col-sm-10 col-lg-3">
                                                <label class="col-form-label">Nome</label>
                                                <div class="">
                                                    <input id="nome" type="text" class="form-control @if($errors->has('nome')) is-invalid @endif" name="nome" value="{{{ isset($usuario) ? $usuario->nome : old('nome') }}}">
                                                    @if($errors->has('nome'))
                                                        <div class="invalid-feedback">
                                                            {{ $errors->first('nome') }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>

                                            @isset($usuario)
                                                {!! __view_locais_user_edit($usuario->locais) !!}
                                            @else
                                                {!! __view_locais_user() !!}
                                            @endisset

                                            <div class="form-group validated col-sm-10 col-lg-3">
                                                <label class="col-form-label">Local padrão</label>
                                                <div class="">
                                                    <select name="local_padrao" class="form-select form-control">
                                                        <option value="0">--</option>
                                                        @foreach(__locaisAtivosAll() as $key => $l)
                                                            <option
                                                                @isset($usuario)
                                                                    @if($usuario->local_padrao == $key) selected @endif
                                                                @endisset
                                                                value="{{$key}}"
                                                            >{{$l}}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="form-group validated col-sm-10 col-lg-3">
                                                <label class="col-form-label">Login</label>
                                                <div class="">
                                                    <input id="login" type="text" class="form-control @if($errors->has('login')) is-invalid @endif" name="login" value="{{{ isset($usuario) ? $usuario->login : old('login') }}}">
                                                    @if($errors->has('login'))
                                                        <div class="invalid-feedback">
                                                            {{ $errors->first('login') }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="form-group validated col-sm-10 col-lg-3">
                                                <label class="col-form-label">Senha</label>
                                                <div class="">
                                                    <input id="senha" type="password" class="form-control @if($errors->has('senha')) is-invalid @endif" name="senha" value="{{{ isset($usuario) ? '' : old('senha') }}}">
                                                    @if($errors->has('senha'))
                                                        <div class="invalid-feedback">
                                                            {{ $errors->first('senha') }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="form-group validated col-sm-10 col-lg-3">
                                                <label class="col-form-label">Email</label>
                                                <div class="">
                                                    <input id="email" type="email" class="form-control @if($errors->has('email')) is-invalid @endif" name="email" value="{{{ isset($usuario) ? $usuario->email : old('email') }}}">
                                                    @if($errors->has('email'))
                                                        <div class="invalid-feedback">
                                                            {{ $errors->first('email') }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="form-group validated col-sm-10 col-lg-4">
                                                <label class="col-form-label">Rota padrão ao logar (opcional)</label>
                                                <button type="button" class="btn btn-light-info btn-sm btn-icon col-lg-6 col-sm-6" data-toggle="popover" data-trigger="click" data-content="Copie o caminho completo da URL, exemplo: http://meusistema.com.br/frenteCaixa"><i class="la la-info"></i></button>
                                                <div class="">
                                                    <input id="rota_acesso" type="text" class="form-control @if($errors->has('rota_acesso')) is-invalid @endif" name="rota_acesso" value="{{{ isset($usuario) ? $usuario->rota_acesso : old('rota_acesso') }}}">
                                                    @if($errors->has('rota_acesso'))
                                                        <div class="invalid-feedback">
                                                            {{ $errors->first('rota_acesso') }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="form-group validated col-sm-6 col-lg-2">
                                                <label class="col-form-label text-left col-lg-12 col-sm-12">Ativo</label>
                                                <div class="col-6">
												<span class="switch switch-outline switch-warning">
													<label>
														<input id="ativo" @if(isset($usuario->ativo) && $usuario->ativo) checked @endisset
                                                        name="ativo" type="checkbox" >
														<span></span>
													</label>
												</span>

                                                </div>
                                            </div>

                                            <div class="form-group validated col-sm-6 col-lg-2">
                                                <label class="col-form-label text-left col-lg-12 col-sm-12">ADM</label>
                                                <div class="col-6">
												<span class="switch switch-outline switch-primary">
													<label>
														<input id="adm" @if(isset($usuario->adm) && $usuario->adm) checked @endisset
                                                        name="adm" type="checkbox" >
														<span></span>
													</label>
												</span>

                                                </div>
                                            </div>

                                            <div class="form-group validated col-sm-6 col-lg-2">
                                                <label class="col-form-label text-left col-lg-12 col-sm-12">Somente Fiscal</label>
                                                <div class="col-6">
												<span class="switch switch-outline switch-info">
													<label>
														<input id="somente_fiscal" @if(isset($usuario->somente_fiscal) && $usuario->somente_fiscal) checked @endisset
                                                        name="somente_fiscal" type="checkbox" >
														<span></span>
													</label>
												</span>

                                                </div>
                                            </div>
                                            <div class="form-group validated col-sm-6 col-lg-3">
                                                <label class="col-form-label">Caixa livre</label>
                                                <button type="button" class="btn btn-light-warning btn-sm btn-icon col-lg-6 col-sm-6" data-toggle="popover" data-trigger="click" data-content="Marcar para selcionar o vendedor/fúncionário no PDV para comissão"><i class="la la-info"></i></button>
                                                <div class="col-6">
												<span class="switch switch-outline switch-warning">
													<label>
														<input id="caixa_livre" @if(isset($usuario->caixa_livre) && $usuario->caixa_livre) checked @endisset
                                                        name="caixa_livre" type="checkbox" >
														<span></span>
													</label>
												</span>
                                                </div>
                                            </div>
                                            <div class="form-group validated col-sm-6 col-lg-3">
                                                <label class="col-form-label">Permite desconto em vendas</label>
                                                <button type="button" class="btn btn-light-danger btn-sm btn-icon col-lg-6 col-sm-6" data-toggle="popover" data-trigger="click" data-content="Marcar para usuário conceder desconto na venda PDV e pedido"><i class="la la-info"></i></button>
                                                <div class="col-6">
												<span class="switch switch-outline switch-danger">
													<label>
														<input id="permite_desconto" @if(isset($usuario->permite_desconto) && $usuario->permite_desconto) checked @endisset
                                                        name="permite_desconto" type="checkbox" >
														<span></span>
													</label>
												</span>
                                                </div>
                                            </div>

                                            @if(session('user_logged')['tipo_representante'] == 1)
                                                <div class="form-group validated col-sm-6 col-lg-3">
                                                    <label class="col-form-label">Acesso representante</label>
                                                    <button type="button" class="btn btn-light-dark btn-sm btn-icon col-lg-6 col-sm-6" data-toggle="popover" data-trigger="click" data-content="Marcar para usuário ter acesso ao menu representante/Contador"><i class="la la-info"></i></button>
                                                    <div class="col-6">
												<span class="switch switch-outline switch-dark">
													<label>
														<input id="menu_representante" @if(isset($usuario->menu_representante) && $usuario->menu_representante) checked @endisset
                                                        name="menu_representante" type="checkbox" >
														<span></span>
													</label>
												</span>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Botões OTP: mostrar apenas ao EDITAR --}}
                                        @isset($usuario)
                                            @if($usuario->hasOtp())
                                                <button
                                                    type="button"
                                                    id="btn-change-otp"
                                                    class="btn btn-sm btn-danger"
                                                    data-preview-url="{{ route('usuarios.preview-otp', $usuario->id) }}"
                                                >Alterar OTP</button>
                                            @else
                                                <button
                                                    type="button"
                                                    id="btn-generate-otp"
                                                    class="btn btn-sm btn-secondary"
                                                    data-preview-url="{{ route('usuarios.preview-otp', $usuario->id) }}"
                                                >Configurar OTP</button>
                                            @endif
                                        @endisset

                                        <div class="row">
                                            <div class="form-group validated col-sm-12">

                                                <label class="col-3 col-form-label">Permissão de Acesso:</label>
                                                <input type="hidden" id="menus" value="{{json_encode($menu)}}" name="">
                                                @foreach($menuAux as $m)
                                                    @if($m['ativo'] == 1)
                                                        <div class="col-12 col-form-label">
												<span>
													<label class="checkbox checkbox-info">
														<input id="todos_{{str_replace(' ', '_', $m['titulo'])}}" onclick="marcarTudo('{{$m['titulo']}}')" type="checkbox" >
														<span></span><strong class="text-info" style="margin-left: 5px; font-size: 16px;">{{$m['titulo']}} </strong>
													</label>
												</span>
                                                            <div class="checkbox-inline" style="margin-top: 10px;">
                                                                @foreach($m['subs'] as $s)
                                                                    @if(in_array($s['rota'], $permissoesAtivas))
                                                                        <label class="checkbox checkbox-info check-sub">
                                                                            <input id="sub_{{str_replace('/', 	'', $s['rota'])}}" @if(in_array($s['rota'], $permissoesUsuario)) checked @endif type="checkbox" name="{{$s['rota']}}">
                                                                            <span></span>{{$s['nome']}}
                                                                        </label>
                                                                    @endif
                                                                @endforeach
                                                            </div>

                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>

                                    </div>

                                </div>
                            </div>
                        </div>
                </div>
                <div class="card-footer">

                    <div class="row">
                        <div class="col-xl-2">

                        </div>
                        <div class="col-lg-3 col-sm-6 col-md-4">
                            <a style="width: 100%" class="btn btn-danger" href="/usuarios">
                                <i class="la la-close"></i>
                                <span class="">Cancelar</span>
                            </a>
                        </div>
                        <div class="col-lg-3 col-sm-6 col-md-4">
                            <button style="width: 100%" type="submit" class="btn btn-success">
                                <i class="la la-check"></i>
                                <span class="">Salvar</span>
                            </button>
                        </div>

                    </div>
                </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ========== Modais OTP: renderizar apenas ao EDITAR ========== --}}
    @isset($usuario)
        {{-- Modal principal (QR + salvar) --}}
        <div class="modal fade" id="otpModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog"><div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">OTP — {{ config('app.name') }} / {{ $usuario->nome }}</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body text-center">
                        <div id="otp-content">
                            <p>Escaneie no Google Authenticator:</p>
                            <img id="modal-qr-img" class="img-fluid mb-2" style="max-width:200px;">
                            <p>Código manual: <strong id="modal-secret-text"></strong></p>
                            <input type="text" id="modal-otp-code" class="form-control mb-2" placeholder="000000" maxlength="6">
                            <div id="modal-error" class="text-danger small" style="display:none;"></div>
                        </div>
                        <div id="otp-loading" class="text-center" style="display:none;">
                            <span class="spinner-border"></span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button id="modal-validate-btn" class="btn btn-primary">Validar e Salvar</button>
                        <button class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    </div>
                </div></div>
        </div>

        <!-- Modal para validar o código atual -->
        <div class="modal fade" id="otpCurrentVerifyModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirme o código atual</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body text-center">
                        <input
                            type="text"
                            id="verify-current-otp-code"
                            class="form-control mb-2"
                            placeholder="000000"
                            maxlength="6"
                        >
                        <div id="verify-current-error" class="text-danger small" style="display:none;"></div>
                    </div>
                    <div class="modal-footer">
                        <button id="btn-verify-current-otp" class="btn btn-primary">Validar</button>
                        <button class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    </div>
                </div>
            </div>
        </div>
    @endisset

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

@section('javascript')
    <script type="text/javascript">
        $('[data-toggle="popover"]').popover()
    </script>
    <script type="text/javascript">
        $('[data-toggle="popover"]').popover()
    </script>

    {{-- ========== Scripts OTP: carregar apenas ao EDITAR ========== --}}
    @isset($usuario)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const btnPreview = document.getElementById('btn-generate-otp');
                const otpModal  = $('#otpModal');
                const userId    = {{ $usuario->id }};
                const verifyUrl = "{{ route('usuarios.verify-current-otp', $usuario->id) }}";
                const saveUrl   = "{{ route('usuarios.save-otp', $usuario->id) }}";

                // Helpers
                function showConfirm(message, onConfirm) {
                    $('#modalConfirmacaoMensagem').text(message);
                    $('#btnConfirmarAcao').off('click').on('click', function(){
                        $('#modalConfirmacao').modal('hide');
                        onConfirm();
                    });
                    $('#modalConfirmacao').modal('show');
                }
                function showMessage(title, text, callback) {
                    $('#modalMensagemLabel').text(title);
                    $('#modalMensagemTexto').text(text);
                    $('#modalMensagem').off('hidden.bs.modal');
                    if (callback) {
                        $('#modalMensagem').on('hidden.bs.modal', callback);
                    }
                    $('#modalMensagem').modal('show');
                }

                // 1) Preview + abrir modal (só se existir botão)
                if (btnPreview) {
                    btnPreview.addEventListener('click', function () {
                        const url = this.dataset.previewUrl;
                        $('#modal-error').hide();
                        $('#modal-otp-code').val('');
                        $('#otp-content').show();
                        $('#otp-loading').hide();

                        fetch(url, {
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        })
                            .then(r => r.ok ? r.json() : Promise.reject(r))
                            .then(json => {
                                $('#modal-secret-text').text(json.secret);
                                $('#modal-qr-img').attr('src', json.qrCode);
                                otpModal.modal('show');
                            })
                            .catch(() => {
                                showMessage('Erro', 'Não foi possível gerar o preview do OTP.');
                            });
                    });
                }

                // 2) Validar + salvar secret
                $('#modal-validate-btn').on('click', function(){
                    const code   = $('#modal-otp-code').val().trim();
                    const secret = $('#modal-secret-text').text().trim();

                    if (!/^\d{6}$/.test(code)) {
                        $('#modal-error').text('Informe um código de 6 dígitos.').show();
                        return;
                    }

                    $('#otp-content').hide();
                    $('#otp-loading').show();
                    $('#modal-error').hide();

                    fetch(saveUrl, {
                        method: 'POST',
                        headers:{
                            'X-CSRF-TOKEN':'{{ csrf_token() }}',
                            'Content-Type':'application/json','Accept':'application/json'
                        },
                        body: JSON.stringify({ secret, code })
                    })
                        .then(r => r.ok ? r.json() : r.json().then(e => Promise.reject(e)))
                        .then(() => {
                            showMessage('Sucesso', 'OTP configurado com sucesso.', () => {
                                location.reload();
                            });
                        })
                        .catch(err => {
                            const msg = err.error || (err.errors?.code?.[0]) || 'Código inválido.';
                            $('#modal-error').text(msg).show();
                            $('#otp-loading').hide();
                            $('#otp-content').show();
                        });
                });
            });
        </script>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const btnChange = document.getElementById('btn-change-otp');
                if (!btnChange) return;

                function showConfirm(msg, onOk) {
                    $('#modalConfirmacaoMensagem').text(msg);
                    $('#btnConfirmarAcao').off('click').on('click', () => {
                        $('#modalConfirmacao').modal('hide');
                        onOk();
                    });
                    $('#modalConfirmacao').modal('show');
                }
                function showMessage(title, text) {
                    $('#modalMensagemLabel').text(title);
                    $('#modalMensagemTexto').text(text);
                    $('#modalMensagem').modal('show');
                }

                const verifyUrl = "{{ route('usuarios.verify-current-otp', $usuario->id) }}";

                // 1) clicar em “Alterar OTP”
                btnChange.addEventListener('click', () => {
                    showConfirm(
                        'Já existe um token. Deseja realmente alterar o OTP?',
                        () => $('#otpCurrentVerifyModal').modal('show')
                    );
                });

                // 2) validar código atual
                document.getElementById('btn-verify-current-otp').addEventListener('click', () => {
                    const code = $('#verify-current-otp-code').val().trim();
                    if (!/^\d{6}$/.test(code)) {
                        $('#verify-current-error').text('Informe 6 dígitos.').show();
                        return;
                    }
                    fetch(verifyUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN':'{{ csrf_token() }}',
                            'Content-Type':'application/json',
                            'Accept':'application/json'
                        },
                        body: JSON.stringify({ code })
                    })
                        .then(r => r.ok ? r.json() : r.json().then(e => Promise.reject(e)))
                        .then(json => {
                            if (json.valid) {
                                $('#otpCurrentVerifyModal').modal('hide');

                                const previewBtn =
                                    document.getElementById('btn-change-otp')
                                    || document.getElementById('btn-generate-otp');

                                const url = previewBtn.dataset.previewUrl;

                                $('#modal-error').hide();
                                $('#modal-otp-code').val('');
                                $('#otp-content').show();
                                $('#otp-loading').hide();

                                fetch(url, {
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json'
                                    }
                                })
                                    .then(r => r.ok ? r.json() : Promise.reject(r))
                                    .then(d => {
                                        $('#modal-secret-text').text(d.secret);
                                        $('#modal-qr-img').attr('src', d.qrCode);
                                        $('#otpModal').modal('show');
                                    })
                                    .catch(() => {
                                        $('#modalMensagemLabel').text('Erro');
                                        $('#modalMensagemTexto').text('Não foi possível gerar o preview do OTP.');
                                        $('#modalMensagem').modal('show');
                                    });

                            } else {
                                throw 'inválido';
                            }
                        })
                        .catch(err => {
                            const msg = err.error || 'Código inválido.';
                            $('#verify-current-error').text(msg).show();
                        });
                });
            });
        </script>
    @endisset
@endsection
