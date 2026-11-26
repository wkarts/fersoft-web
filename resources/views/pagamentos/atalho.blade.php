@if(!$titulo->status && \App\Services\Pagamentos\Estabelecimento::disponivel())
    <a class="btn btn-light-primary btn-sm" href="{{ route('pagamentos.index', ['titulo' => $titulo->id]) }}">Cobrar pelo Asaas</a>
@endif
