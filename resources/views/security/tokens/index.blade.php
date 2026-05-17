@extends('default.layout')

@section('content')
<div class="card card-custom gutter-b">
    <div class="card-body">
        @if(session('mensagem_sucesso'))<div class="alert alert-success">{{ session('mensagem_sucesso') }}</div>@endif
        @if(session('mensagem_erro'))<div class="alert alert-danger">{{ session('mensagem_erro') }}</div>@endif
        @if($plainToken)
            <div class="alert alert-warning">
                <strong>Token gerado:</strong>
                <code style="font-size: 18px;">{{ $plainToken }}</code><br>
                Copie agora. Por segurança, este valor não será exibido novamente.
            </div>
        @endif

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3>Tokens de Liberação</h3>
                <p class="text-muted mb-0">Tokens operacionais individuais dos usuários autorizadores. O banco armazena apenas o hash do token. Também é possível usar app autenticador no cadastro de Autorizadores.</p>
            </div>
            <a href="/seguranca" class="btn btn-light">Voltar ao painel</a>
        </div>

        @if($isSuper)
            <form method="get" class="mb-4">
                <div class="row">
                    <div class="col-md-5">
                        <select name="empresa_id" class="form-control">
                            <option value="">Tokens globais</option>
                            @foreach($empresas as $empresa)
                                <option value="{{ $empresa->id }}" {{ (string)$empresaId === (string)$empresa->id ? 'selected' : '' }}>{{ $empresa->nome }} - {{ $empresa->cnpj }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3"><button class="btn btn-primary">Filtrar empresa</button></div>
                </div>
            </form>
        @endif

        <div class="card bg-light mb-4">
            <div class="card-body">
                <h5 class="mb-3">Novo token</h5>
                <form method="post" action="/seguranca/tokens">
                    @csrf
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label>Autorizador</label>
                            <select name="security_authorizer_id" class="form-control" required>
                                <option value="">Selecione</option>
                                @foreach($authorizers as $authorizer)
                                    <option value="{{ $authorizer->id }}">{{ optional($authorizer->usuario)->nome }} {{ optional($authorizer->usuario)->login ? '(' . optional($authorizer->usuario)->login . ')' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Nome do token</label>
                            <input type="text" name="name" class="form-control" value="Token principal">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Token manual opcional</label>
                            <input type="text" name="token" class="form-control" placeholder="Deixe vazio para gerar">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label>Validade</label>
                            <input type="date" name="expires_at" class="form-control">
                        </div>
                    </div>

                    <div class="alert alert-info mb-3">
                        O token em texto puro aparece apenas uma vez após salvar. Depois disso, somente será possível gerar outro token. Este recurso continua disponível mesmo usando app autenticador.
                    </div>

                    <button class="btn btn-primary">Gerar token</button>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Token</th>
                        <th>Autorizador</th>
                        <th>Empresa</th>
                        <th>Validade</th>
                        <th>Último uso</th>
                        <th>Ativo</th>
                        <th width="180">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tokens as $token)
                        <tr>
                            <td><strong>{{ $token->name }}</strong><br><small>Hash protegido</small></td>
                            <td>{{ optional(optional($token->authorizer)->usuario)->nome }}</td>
                            <td>{{ optional($token->empresa)->nome ?: 'Global' }}</td>
                            <td>{{ $token->expires_at ? $token->expires_at->format('d/m/Y') : 'Sem validade' }}</td>
                            <td>{{ $token->last_used_at ? $token->last_used_at->format('d/m/Y H:i') : '-' }}</td>
                            <td>{{ $token->enabled ? 'Sim' : 'Não' }}</td>
                            <td>
                                <div class="d-flex">
                                    <form method="post" action="/seguranca/tokens/{{ $token->id }}/toggle" class="mr-2">
                                        @csrf
                                        <button class="btn btn-sm btn-warning">{{ $token->enabled ? 'Desativar' : 'Ativar' }}</button>
                                    </form>
                                    <form method="post" action="/seguranca/tokens/{{ $token->id }}" onsubmit="return confirm('Remover este token?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger">Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">Nenhum token configurado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $tokens->links() }}
    </div>
</div>
@endsection
