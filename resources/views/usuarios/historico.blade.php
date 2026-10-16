@extends('default.layout')
@section('css')
@unless(config('ui_theme.active', false))
<link rel="stylesheet" href="{{ asset('css/ui-admin.css') }}?v={{ filemtime(public_path('css/ui-admin.css')) }}">
@endunless
@endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-admin.css') }}?v={{ filemtime(public_path('css/ui-admin.css')) }}">
@endpush
@section('content')
<div class="ui-admin ui-admin-usuarios">

    

    <div class="ui-admin-panel gutter-b">
        <div class="ui-admin-panel-head border-0 pt-6 pb-2">
            <h3 class="card-title font-weight-bolder text-dark">
                <i class="flaticon-time-3 text-primary mr-2 font-size-h3"></i>
                Histórico de Atividades: <span class="text-info ml-2">{{$usuario->nome}}</span>
            </h3>
            <div class="card-toolbar">
                <a href="/usuarios" class="btn btn-light-dark font-weight-bold btn-sm">
                    <i class="la la-arrow-left"></i> Voltar
                </a>
            </div>
        </div>

        <div class="ui-admin-panel-body pt-3">
            <div class="mb-4">
                <span class="text-muted font-weight-bold font-size-sm">Total de registos encontrados: <span class="text-dark">{{$acessos->total()}}</span></span>
            </div>

            <div class="table-responsive">
                <table class="table table-head-custom table-vertical-center table-hover">
                    <thead>
                    <tr class="text-left">
                        <th style="min-width: 150px">Data / Hora</th>
                        <th style="min-width: 120px">IP de Origem</th>
                        <th style="min-width: 160px">Ação Executada</th>
                        <th style="min-width: 250px">Módulo (Tabela) / ID do Registo</th>
                        <th style="min-width: 100px" class="text-right">Detalhes</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($acessos as $a)
                        <tr>
                            <td>
                            <span class="text-dark-75 font-weight-bolder d-block font-size-lg">
                                {{ \Carbon\Carbon::parse($a->created_at)->format('d/m/Y') }}
                            </span>
                                <span class="text-muted font-weight-bold d-block font-size-sm">
                                {{ \Carbon\Carbon::parse($a->created_at)->format('H:i:s') }}
                            </span>
                            </td>

                            <td>
                            <span class="label label-inline label-light-dark font-weight-bold">
                                {{ $a->ip_address ?? $a->ip ?? 'N/D' }}
                            </span>
                            </td>

                            <td>
                                @php
                                    $acaoStr = strtolower($a->tipo ?? $a->action ?? 'acesso');
                                    $cor = 'info';
                                    $texto = 'Acesso ao Sistema';

                                    if($acaoStr == 'create' || $acaoStr == 'criar' || $acaoStr == 'emissao') {
                                    $cor = 'success'; $texto = 'Criação / Emissão';
                                    } elseif($acaoStr == 'update' || $acaoStr == 'atualizar' || $acaoStr == 'edit') {
                                    $cor = 'warning'; $texto = 'Edição de Registo';
                                    } elseif($acaoStr == 'delete' || $acaoStr == 'excluir' || $acaoStr == 'cancelamento') {
                                    $cor = 'danger'; $texto = 'Exclusão / Cancelamento';
                                    } elseif($acaoStr == 'import') {
                                    $cor = 'primary'; $texto = 'Importação';
                                    } elseif($acaoStr == 'login' || $acaoStr == 'auth') {
                                    $cor = 'dark'; $texto = 'Login / Autenticação';
                                    } else {
                                    $texto = strtoupper($acaoStr);
                                    }
                                @endphp

                                <span class="label label-lg label-light-{{$cor}} label-inline font-weight-bold" style="padding: 15px 10px;">
                                {{ $texto }}
                            </span>
                            </td>

                            <td>
                                @php
                                    $nomeTabela = $a->tabela ?? $a->model ?? 'Sistema Geral';
                                    $nomeAmigavel = class_basename($nomeTabela);
                                @endphp
                                <span class="text-dark-75 font-weight-bolder d-block font-size-lg">
                                {{ strtoupper($nomeAmigavel) }}
                            </span>
                                @if(isset($a->registro_id) && $a->registro_id > 0)
                                    <span class="text-muted font-weight-bold d-block font-size-sm mt-1">
                                Registo Afetado ID: <strong class="text-dark">#{{ $a->registro_id }}</strong>
                            </span>
                                @endif
                            </td>

                            <td class="text-right">
                                @php
                                    // Blindagem: Pega os dados apenas se a coluna existir, senão assume nulo
                                    $dadosAntesRaw = $a->dados_antes ?? $a->dados_anteriores ?? null;
                                    $dadosDepoisRaw = $a->dados_depois ?? $a->dados_novos ?? null;
                                @endphp

                                @if(!empty($dadosAntesRaw) || !empty($dadosDepoisRaw))
                                    <!-- O Botão chama o Javascript usando o ID da linha -->
                                    <button type="button" class="btn btn-sm btn-light-primary font-weight-bolder" onclick="abrirRaioX({{ $a->id ?? $loop->index }})">
                                        <i class="fa fa-search"></i> Ver
                                    </button>

                                    @php
                                        // Desempacota o JSON com segurança
                                        $antesArray = is_string($dadosAntesRaw) ? json_decode($dadosAntesRaw, true) : $dadosAntesRaw;
                                        $depoisArray = is_string($dadosDepoisRaw) ? json_decode($dadosDepoisRaw, true) : $dadosDepoisRaw;

                                        // Proteção contra JSON codificado duas vezes
                                        if(is_string($antesArray)) $antesArray = json_decode($antesArray, true);
                                        if(is_string($depoisArray)) $depoisArray = json_decode($depoisArray, true);

                                        $antesArray = is_array($antesArray) ? $antesArray : [];
                                        $depoisArray = is_array($depoisArray) ? $depoisArray : [];
                                    @endphp

                                    <div id="conteudo_log_{{ $a->id ?? $loop->index }}" style="display: none;">
                                        <div class="row m-0">
                                            <!-- LADO ESQUERDO: COMO ESTAVA ANTES -->
                                            <div class="col-lg-6 p-4 border-right bg-light-danger" style="max-height: 60vh; overflow-y: auto;">
                                                <h6 class="font-weight-bolder text-danger mb-4"><i class="fa fa-minus-circle mr-2"></i>Como estava antes:</h6>
                                                @if(count($antesArray) > 0)
                                                    <table class="w-100 log-table">
                                                        @foreach($antesArray as $chave => $valor)
                                                            @if(!is_array($valor) && !is_object($valor) && $chave != 'senha' && $chave != 'otp_secret')
                                                                <tr>
                                                                    <td>
                                                                        <span class="log-key">{{ str_replace('_', ' ', $chave) }}</span>
                                                                        <span class="font-weight-bold">{{ empty($valor) && $valor !== 0 && $valor !== '0' ? '-' : $valor }}</span>
                                                                    </td>
                                                                </tr>
                                                            @endif
                                                        @endforeach
                                                    </table>
                                                @else
                                                    <div class="alert alert-custom alert-light-danger py-2 px-4 mt-3">
                                                        <div class="alert-text">Nenhum dado anterior (Registo Novo ou Vazio).</div>
                                                    </div>
                                                @endif
                                            </div>

                                            <!-- LADO DIREITO: COMO FICOU DEPOIS -->
                                            <div class="col-lg-6 p-4 bg-light-success" style="max-height: 60vh; overflow-y: auto;">
                                                <h6 class="font-weight-bolder text-success mb-4"><i class="fa fa-plus-circle mr-2"></i>Como ficou depois:</h6>
                                                @if(count($depoisArray) > 0)
                                                    <table class="w-100 log-table">
                                                        @foreach($depoisArray as $chave => $valor)
                                                            @if(!is_array($valor) && !is_object($valor) && $chave != 'senha' && $chave != 'otp_secret')
                                                                <tr>
                                                                    <td>
                                                                        <span class="log-key text-success">{{ str_replace('_', ' ', $chave) }}</span>
                                                                        <span class="font-weight-bold">{{ empty($valor) && $valor !== 0 && $valor !== '0' ? '-' : $valor }}</span>
                                                                    </td>
                                                                </tr>
                                                            @endif
                                                        @endforeach
                                                    </table>
                                                @else
                                                    <div class="alert alert-custom alert-light-success py-2 px-4 mt-3">
                                                        <div class="alert-text">Nenhum dado capturado após a ação.</div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted font-size-sm">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach

                    @if(count($acessos) == 0)
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                Nenhum histórico de atividade encontrado para este utilizador.
                            </td>
                        </tr>
                    @endif
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <div class="d-flex justify-content-between align-items-center flex-wrap mt-5">
                <div class="d-flex flex-wrap py-2 mr-3">
                    @if(method_exists($acessos, 'links') && $acessos->links())
                        {{$acessos->links()}}
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Principal Raio-X -->
    <div class="modal fade" id="modalRaioX" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-light">
                    <h5 class="modal-title font-weight-bolder text-dark"><i class="fa fa-search text-primary mr-2"></i> Raio-X da Operação</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <i aria-hidden="true" class="ki ki-close"></i>
                    </button>
                </div>
                <div class="modal-body p-0" id="conteudo-modal-raiox">
                    <!-- O conteúdo html oculto será injetado aqui -->
                </div>
                <div class="modal-footer bg-light py-3">
                    <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">Fechar Detalhes</button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('javascript')
    <script>
        $(document).ready(function() {
            $('[data-toggle="tooltip"]').tooltip();
        });

        function abrirRaioX(id) {
            // Pega o HTML limpo gerado pelo PHP e coloca dentro do Modal
            let htmlConteudo = $('#conteudo_log_' + id).html();
            $('#conteudo-modal-raiox').html(htmlConteudo);
            $('#modalRaioX').modal('show');
        }
    </script>
@endsection
