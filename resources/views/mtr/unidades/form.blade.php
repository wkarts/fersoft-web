@php
    $editing = isset($unidade) && $unidade;
@endphp
<div class="row">
    <div class="col-lg-3 form-group">
        <label>Órgão *</label>
        <select name="orgao" class="form-control" required>
            @foreach(['SINIR','IEMA','SIGOR','FEAM','IMA','FEPAM'] as $orgao)
                <option value="{{ $orgao }}" {{ old('orgao',$unidade->orgao ?? 'SINIR') === $orgao ? 'selected' : '' }}>{{ $orgao }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-lg-3 form-group">
        <label>Perfil *</label>
        <select name="perfil" class="form-control" required>
            @foreach(['Gerador','Transportador','Destinador','Armazenador'] as $perfil)
                <option value="{{ $perfil }}" {{ old('perfil',$unidade->perfil ?? 'Gerador') === $perfil ? 'selected' : '' }}>{{ $perfil }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-lg-3 form-group">
        <label>Ambiente *</label>
        <select name="ambiente" class="form-control" required>
            <option value="homologacao" {{ old('ambiente',$unidade->ambiente ?? 'homologacao') === 'homologacao' ? 'selected' : '' }}>Homologação</option>
            <option value="producao" {{ old('ambiente',$unidade->ambiente ?? '') === 'producao' ? 'selected' : '' }}>Produção</option>
        </select>
    </div>
    <div class="col-lg-3 form-group">
        <label>Ativo</label>
        <select name="ativo" class="form-control">
            <option value="1" {{ old('ativo',$unidade->ativo ?? 1) ? 'selected' : '' }}>Sim</option>
            <option value="0" {{ !old('ativo',$unidade->ativo ?? 1) ? 'selected' : '' }}>Não</option>
        </select>
    </div>

    <div class="col-lg-4 form-group">
        <label>CPF/CNPJ da unidade *</label>
        <input name="cpf_cnpj" class="form-control" required value="{{ old('cpf_cnpj',$unidade->cpf_cnpj ?? '') }}">
    </div>
    <div class="col-lg-4 form-group">
        <label>CPF do usuário</label>
        <input name="cpf_usuario" class="form-control" value="{{ old('cpf_usuario',$unidade->cpf_usuario ?? '') }}">
    </div>
    <div class="col-lg-4 form-group">
        <label>Código da unidade *</label>
        <input name="unidade_id" class="form-control" required value="{{ old('unidade_id',$unidade->unidade_id ?? '1') }}">
    </div>

    <div class="col-lg-6 form-group">
        <label>Descrição</label>
        <input name="descricao" class="form-control" value="{{ old('descricao',$unidade->descricao ?? '') }}">
    </div>
    <div class="col-lg-6 form-group">
        <label>Filial</label>
        <select name="filial_id" class="form-control">
            <option value="">Matriz / sem filial específica</option>
            @foreach($filiais as $filial)
                <option value="{{ $filial->id }}" {{ (string)old('filial_id',$unidade->filial_id ?? '') === (string)$filial->id ? 'selected' : '' }}>{{ $filial->descricao }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-lg-12 form-group">
        <label>Senha / Token de integração {{ $editing ? '(deixe em branco para manter)' : '' }}</label>
        <input type="password" name="senha" id="mtrSenha" class="form-control" autocomplete="new-password">
        <small class="text-muted">O valor é armazenado criptografado pela aplicação.</small>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mt-3">
    <button type="button" id="btnTestarMtr" class="btn btn-light-primary">Testar conexão</button>
    <div>
        <a href="{{ route('mtr.unidades.index') }}" class="btn btn-light-danger">Cancelar</a>
        <button class="btn btn-success">Salvar</button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const btn = document.getElementById('btnTestarMtr');
    if (!btn) return;

    btn.addEventListener('click', async function(){
        const form = btn.closest('form');
        const payload = Object.fromEntries(new FormData(form).entries());
        @if($editing)
        payload.unidade_db_id = '{{ $unidade->id }}';
        @endif

        btn.disabled = true;
        const old = btn.textContent;
        btn.textContent = 'Testando...';

        try {
            const response = await fetch('{{ route('mtr.unidades.testarConexao') }}', {
                method:'POST',
                credentials:'same-origin',
                headers:{
                    'Accept':'application/json',
                    'Content-Type':'application/json',
                    'X-CSRF-TOKEN':'{{ csrf_token() }}'
                },
                body:JSON.stringify(payload)
            });
            const data = await response.json();
            alert(data.mensagem || (data.sucesso ? 'Conexão OK.' : 'Falha na conexão.'));
        } catch (e) {
            alert('Falha ao testar conexão: ' + e.message);
        } finally {
            btn.disabled = false;
            btn.textContent = old;
        }
    });
});
</script>
