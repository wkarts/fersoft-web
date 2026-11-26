@foreach(['sandbox' => 'Homologação', 'production' => 'Produção'] as $ambiente => $rotulo)
    @continue($ambiente === 'production' && $ambienteFiscal !== 'production')
    @php($conexao = $conexoes->firstWhere('ambiente', $ambiente))
    <form method="post" action="{{ route($rotaParametros.'salvar', $destino + ['provedor' => $provedor->provedor]) }}" class="border rounded p-3 mb-3">
        @csrf
        @method('PUT')
        <input type="hidden" name="ambiente" value="{{ $ambiente }}">
        <input type="hidden" name="estabelecimento" value="{{ $sitePagamento->empresa }}-{{ $localPagamento }}">
        <h6>{{ $rotulo }}</h6>
        <label>Chave de API Asaas <input class="form-control" type="password" name="credencial" autocomplete="new-password" @required(!$conexao)></label>
        <label>Token do webhook <input class="form-control" type="password" name="segredo" autocomplete="new-password" minlength="32" maxlength="255" @required(!$conexao)></label>
        @if($conexao)
            <p>Deixe as credenciais em branco para manter as atuais. Cadastre no Asaas os eventos de cobrança com o mesmo token e esta URL.</p>
            <p><code>{{ route('pagamentos.webhook', $conexao->webhook_id) }}</code></p>
        @endif
        @if($ambiente === 'production')
            <label>Conta bancária para conciliação
                <select class="form-control" name="conta_bancaria_id" @disabled($conexao?->conta_bancaria_id)>
                    <option value="">Selecione uma conta para importar extratos</option>
                    @foreach($sitePagamento->contasBancarias()->orderBy('nome')->pluck('nome','id') as $id => $nome)
                        <option value="{{ $id }}" @selected($id == $conexao?->conta_bancaria_id)>{{ $nome }}</option>
                    @endforeach
                </select>
            </label>
        @endif
        <div><label><input name="ativar" type="checkbox" value="1" @checked($conexao?->selecionada)> Usar este ambiente nas novas cobranças</label></div>
        <button class="btn btn-primary" type="submit">Salvar {{ $rotulo }}</button>
    </form>
@endforeach
