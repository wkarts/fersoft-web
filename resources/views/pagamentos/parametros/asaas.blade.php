<form method="post" action="{{ route('configuracao-pagamentos.salvar', ['local' => $local, 'provedor' => $codigo]) }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="ambiente" value="{{ $ambiente }}">
    <input type="hidden" name="estabelecimento" value="{{ $site->empresa }}-{{ $local }}">
    <div class="pg-secao"><fieldset>
        <legend class="h6 mb-3">Credenciais de {{ $ambientes[$ambiente] }}</legend>
        @if($conexao)<p class="text-muted">Preencha apenas as credenciais que deseja substituir.</p>@endif
        <div class="row">
            <div class="form-group col-12 col-md-6">
                <label for="pag-chave">Chave de API Asaas</label>
                <input id="pag-chave" class="form-control" type="password" name="credencial" autocomplete="new-password" @required(!$conexao)>
            </div>
            <div class="form-group col-12 col-md-6">
                <label for="pag-token">Token do webhook</label>
                <div class="input-group">
                    <input id="pag-token" class="form-control" type="password" name="segredo" autocomplete="new-password" minlength="32" maxlength="255" @required(!$conexao?->webhook_hash)>
                    <div class="input-group-append"><button id="pag-gerar" class="btn btn-light-primary" type="button">Gerar token</button></div>
                </div>
                <small id="pag-aviso-token" class="form-text text-muted" aria-live="polite"></small>
            </div>
        </div>
        <label for="pag-webhook">URL do webhook · {{ $ambientes[$ambiente] }}</label>
        @if($conexao)
            @if(!$conexao->webhook_hash)<p class="text-warning">Gere um token exclusivo para voltar a receber eventos neste ambiente.</p>@endif
            <div class="input-group">
                <input id="pag-webhook" class="form-control" readonly value="{{ route('pagamentos.webhook', $conexao->webhook_id) }}">
                <div class="input-group-append"><button id="pag-copiar" class="btn btn-light-primary" type="button">Copiar URL</button></div>
            </div>
            <small class="form-text" aria-live="polite" id="pag-copia-status"></small>
        @else
            <p class="mb-0">A URL estará disponível após salvar as credenciais deste ambiente.</p>
        @endif
    </fieldset></div>
    <div class="pg-secao"><fieldset>
        <legend class="h6 mb-3">Formas de pagamento</legend>
        <input type="hidden" name="configurar_metodos" value="1">
        <div class="row align-items-center">
            <div class="col-12 col-md-7 mb-3">
                @foreach(\App\Models\PagamentoConexao::METODOS as $id => $nome)
                    <div class="custom-control custom-checkbox custom-control-inline my-1 pg-opcao">
                        <input class="custom-control-input" id="pag-metodo-{{ $id }}" type="checkbox" name="metodos[]" value="{{ $id }}" @checked(!$conexao || isset($conexao->metodosAtivos()[$id]))>
                        <label class="custom-control-label" for="pag-metodo-{{ $id }}">{{ $nome }}</label>
                    </div>
                @endforeach
            </div>
            <div class="col-12 col-md-5 form-group">
                <label for="pag-parcelas">Máximo de parcelas no cartão</label>
                <input required id="pag-parcelas" class="form-control" type="number" name="max_parcelas_cartao" min="1" value="{{ $conexao?->max_parcelas_cartao ?? 1 }}">
            </div>
        </div>
        @if($ambiente === 'production')
            <label for="pag-conta">Conta bancária para conciliação</label>
            <select id="pag-conta" class="form-control" name="conta_bancaria_id" @disabled($conexao?->conta_bancaria_id)>
                <option value="">Selecione uma conta para importar extratos</option>
                @foreach($site->contasBancarias()->orderBy('nome')->pluck('nome','id') as $id => $nome)
                    <option value="{{ $id }}" @selected($id == $conexao?->conta_bancaria_id)>{{ $nome }}</option>
                @endforeach
            </select>
        @endif
    </fieldset></div>
    <div class="pg-rodape">
        <div class="custom-control custom-checkbox">
            <input class="custom-control-input" id="pag-ativar" name="ativar" type="checkbox" value="1" @checked($conexao?->selecionada)>
            <label class="custom-control-label" for="pag-ativar">Usar {{ $ambientes[$ambiente] }} nas novas cobranças deste estabelecimento</label>
        </div>
        <button class="btn btn-primary" type="submit">Salvar {{ $ambientes[$ambiente] }}</button>
    </div>
</form>
<script>
    const copiarWebhook = document.getElementById('pag-copiar');
    if (copiarWebhook) {
        copiarWebhook.addEventListener('click', async function () {
            const campo = document.getElementById('pag-webhook');
            const status = document.getElementById('pag-copia-status');
            try {
                await navigator.clipboard.writeText(campo.value);
                status.textContent = 'URL copiada.';
            } catch {
                campo.focus();
                campo.select();
                status.textContent = 'URL selecionada. Use a opção Copiar do navegador.';
            }
        });
    }
    document.getElementById('pag-gerar').addEventListener('click', function () {
        const campo = this.form.elements.segredo;
        const bytes = crypto.getRandomValues(new Uint8Array(32));
        campo.value = Array.from(bytes, byte => byte.toString(16).padStart(2, '0')).join('');
        campo.type = 'text';
        campo.select();
        document.getElementById('pag-aviso-token').textContent = 'Copie o token para o webhook deste ambiente no Asaas e salve.';
    });
</script>
