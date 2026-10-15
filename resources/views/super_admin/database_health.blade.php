@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
    <div class="card-header border-0 py-5 d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h3 class="card-title font-weight-bolder text-dark">Saúde do banco</h3>
            <span class="text-muted">Fotografia somente de metadados do banco conectado</span>
        </div>
        <a href="{{ route('super-admin.database-health') }}" class="btn btn-light-primary font-weight-bold">
            <i class="la la-sync"></i> Atualizar
        </a>
    </div>
    <div class="card-body pt-0">
        <div class="alert alert-info">
            Esta tela consulta apenas <code>information_schema.TABLES</code>. Não lê payloads, não executa contagens completas e não altera dados.
            Em InnoDB, a quantidade de linhas é estimada pelo MySQL/MariaDB.
        </div>

        <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
            <span class="text-muted">Verificado em {{ $snapshot['checked_at_label'] }} · driver {{ $snapshot['driver'] }}</span>
            @if($snapshot['available'])
                <span class="label label-inline label-light-success">Metadados disponíveis</span>
            @else
                <span class="label label-inline label-light-warning">Leitura indisponível</span>
            @endif
        </div>

        @if(!$snapshot['available'])
            <div class="alert alert-warning">{{ $snapshot['message'] }}</div>
        @else
            <div class="row">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card card-custom bg-light-primary h-100"><div class="card-body">
                        <div class="text-muted font-weight-bold">Tabelas</div>
                        <div class="font-size-h2 font-weight-bolder">{{ number_format($snapshot['totals']['table_count'], 0, ',', '.') }}</div>
                    </div></div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card card-custom bg-light-info h-100"><div class="card-body">
                        <div class="text-muted font-weight-bold">Linhas estimadas</div>
                        <div class="font-size-h2 font-weight-bolder">{{ number_format($snapshot['totals']['estimated_rows'], 0, ',', '.') }}</div>
                    </div></div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card card-custom bg-light-success h-100"><div class="card-body">
                        <div class="text-muted font-weight-bold">Dados</div>
                        <div class="font-size-h2 font-weight-bolder">{{ $snapshot['totals']['data_label'] }}</div>
                    </div></div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card card-custom bg-light-warning h-100"><div class="card-body">
                        <div class="text-muted font-weight-bold">Dados + índices</div>
                        <div class="font-size-h2 font-weight-bolder">{{ $snapshot['totals']['allocated_label'] }}</div>
                    </div></div>
                </div>
            </div>

            <h4 class="font-weight-bolder text-dark mt-5">Maiores tabelas por espaço estimado</h4>
            <div class="table-responsive">
                <table class="table table-head-custom table-vertical-center">
                    <thead>
                        <tr>
                            <th>Tabela</th>
                            <th class="text-right">Linhas estimadas</th>
                            <th class="text-right">Dados</th>
                            <th class="text-right">Índices</th>
                            <th class="text-right">Total</th>
                            <th>Engine</th>
                            <th>Atualização</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($snapshot['tables'] as $table)
                            <tr>
                                <td>
                                    <span class="font-weight-bolder">{{ $table['name'] }}</span>
                                    @if(in_array($table['name'], ['logs', 'connect_api_webhook_events'], true))
                                        <span class="label label-inline label-light-warning ml-2">monitorada</span>
                                    @endif
                                </td>
                                <td class="text-right">{{ number_format($table['estimated_rows'], 0, ',', '.') }}</td>
                                <td class="text-right">{{ $table['data_label'] }}</td>
                                <td class="text-right">{{ $table['index_label'] }}</td>
                                <td class="text-right font-weight-bolder">{{ $table['allocated_label'] }}</td>
                                <td>{{ $table['engine'] }}</td>
                                <td>{{ $table['updated_at'] ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-8">Nenhuma tabela encontrada nesta conexão.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        <div class="separator separator-dashed my-8"></div>
        <div class="alert alert-light">
            <h5 class="font-weight-bolder">Análise de conteúdo e duplicidade</h5>
            <p class="mb-2">A varredura de Base64, QR, credenciais e fingerprints roda offline sobre um dump, sem consultar a base ativa. Exemplo:</p>
            <code>python3 tools/database_health/db_health.py analyze banco.zip --config tools/database_health/fersoft-health.json --report-md relatorio.md</code>
            <p class="mt-3 mb-0 text-muted">O painel é uma fotografia sob demanda e não grava histórico. O relatório da CLI mede bytes das tuplas INSERT, não o espaço físico do MySQL.</p>
        </div>
    </div>
</div>
@endsection
