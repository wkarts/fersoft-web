@extends('default.layout')
@section('content')

<style>
    .card-custom { border-radius: 12px !important; box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important; border: none !important; }
    .form-control { border-radius: 8px !important; border: 1px solid #e4e6ef !important; padding: 10px 15px !important; }
    .btn-success { border-radius: 8px !important; transition: 0.3s; }
    .btn-success:hover { transform: translateY(-2px); }
</style>

<div class="d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="container">
        <form method="post" action="/estoque/saveApontamento" id="formApontamento">
            @csrf
            <div class="card card-custom gutter-b">
                <div class="card-header"><h3 class="card-title">Apontamento de Produção</h3></div>

                <div class="card-body">
                    @if(session('mensagem_erro') && strpos(session('mensagem_erro'), 'Atenção') !== false)
                        <div class="alert alert-warning mb-5">
                            <strong>{{ session('mensagem_erro') }}</strong>
                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" name="confirmar_negativo" value="1" id="checkConfirmar">
                                <label class="form-check-label" for="checkConfirmar" style="font-weight: bold; cursor: pointer;">
                                    Estou ciente e desejo prosseguir com saldo negativo.
                                </label>
                            </div>
                        </div>
                    @endif

                    <div class="row">
                        <div class="form-group col-lg-3">
                            <label>Local / Filial</label>
                            <select name="filial_id" class="form-control">
                                <option value="">Matriz</option>
                                @foreach($filiais as $f)
                                    <option value="{{ $f->id }}" {{ old('filial_id', request('filial_id')) == $f->id ? 'selected' : '' }}>{{ $f->descricao }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-lg-5">
                            <label>Produto tipo composto</label>
                            <select required class="form-control" id="kt_select2_2" name="produto">
                                <option value="">Selecione</option>
                                @foreach($produtos as $p)
                                    <option value="{{$p->id}}" {{ old('produto') == $p->id ? 'selected' : '' }}>{{$p->id}} - {{$p->nome}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-lg-2">
                            <label>Quantidade</label>
                            <input type="text" id="quantidade" class="form-control" name="quantidade" value="{{ old('quantidade') }}" placeholder="0,000">
                        </div>
                        <div class="form-group col-lg-2">
                            <label>Data da Produção</label>
                            <input type="date" class="form-control" name="data" value="{{ old('data', date('Y-m-d')) }}">
                        </div>
                    </div>
                </div>

                <div class="card-footer text-right">
                    <a href="/estoque" class="btn btn-danger">Cancelar</a>
                    <button type="button" class="btn btn-success" id="btnSalvar">Salvar</button>
                </div>
            </div>
        </form>

        <div class="card card-custom gutter-b">
            <div class="card-body">
                <h4>Últimos 5 Apontamentos</h4>
                <table class="table table-hover">
                    <thead>
                        <tr><th>Produto</th><th>Quantidade</th><th>Data de registro</th><th>Usuário</th></tr>
                    </thead>
                    <tbody>
                        @foreach($apontamentos as $a)
                        <tr>
                            <td>{{$a->produto->nome}}</td>
                            <td>{{$a->quantidade}}</td>
                            <td>{{ \Carbon\Carbon::parse($a->data_registro)->format('d/m/Y H:i:s') }}</td>
                            <td>{{$a->usuario->nome}}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.getElementById('btnSalvar').addEventListener('click', function(e) {
    let form = document.getElementById('formApontamento');
    // Se o checkbox de confirmação já estiver visível na tela, não abre o alerta novamente, apenas envia
    if(document.getElementById('checkConfirmar') && document.getElementById('checkConfirmar').checked == false) {
        // Se apareceu o aviso e ele não marcou, avisa
        Swal.fire("Atenção", "Por favor, marque a caixa de confirmação de estoque negativo.", "warning");
        return;
    }

    Swal.fire({
        title: 'Confirmar Produção?',
        text: "Esta ação atualizará o estoque. Deseja continuar?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sim, confirmar',
        cancelButtonText: 'Revisar'
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
});
</script>
@endsection
