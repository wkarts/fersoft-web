@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">
    <div class="card-header">
        <div class="card-title">
            <h3 class="card-label">
                <i class="la la-user-check text-success fs-2 me-2"></i>
                Parceiros Aprovados
                <small>Histórico de cadastros liberados no sistema</small>
            </h3>
        </div>
        <div class="card-toolbar">
            <a href="/cadastros-pendentes" class="btn btn-light-primary font-weight-bold">
                <i class="la la-arrow-left"></i> Voltar para Pendentes
            </a>
        </div>
    </div>
    
    <div class="card-body">

        @if(session('sucesso'))
        <div class="alert alert-success">
            {{ session('sucesso') }}
        </div>
        @endif

        <div class="table-responsive">
            <table class="table table-hover table-bordered table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th>Data Aprovação</th>
                        <th>Nome / Razão Social</th>
                        <th>Tipo</th>
                        <th>CPF / CNPJ</th>
                        <th>Telefone / WhatsApp</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($aprovados as $p)
                    <tr>
                        <td class="align-middle">{{ \Carbon\Carbon::parse($p->updated_at)->format('d/m/Y H:i') }}</td>
                        <td class="align-middle font-weight-bold">{{ $p->nome_completo }}</td>
                        <td class="align-middle">
                            <span class="badge badge-{{ $p->tipo_pessoa == 'Fisica' ? 'info' : 'primary' }}">
                                {{ $p->tipo_pessoa }}
                            </span>
                        </td>
                        <td class="align-middle">{{ $p->cpf_cnpj }}</td>
                        <td class="align-middle">{{ $p->telefone }}</td>
                        <td class="align-middle text-center">
                            <!-- Botão de Detalhes -->
                            <button type="button" class="btn btn-sm btn-light-info font-weight-bold" onclick="verDetalhes({{ json_encode($p) }})">
                                <i class="la la-eye"></i> Detalhes
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="la la-folder-open fs-1 mb-2 d-block"></i>
                            Nenhum cadastro aprovado encontrado até o momento.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Completo de Detalhes -->
<div class="modal fade" id="modal-detalhes" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title text-white"><i class="la la-user-check me-1"></i> Detalhes do Parceiro Aprovado</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body" id="corpo-detalhes">
                <!-- Preenchido via JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('javascript')
<script>
    // Função para popular todos os campos no Modal de Detalhes
    function verDetalhes(p) {
        let html = `
            <div class="row">
                <div class="col-md-4 mb-3"><strong>Nome / Razão Social:</strong><br><span class="text-dark">${p.nome_completo || '-'}</span></div>
                <div class="col-md-4 mb-3"><strong>Tipo de Pessoa:</strong><br><span class="text-dark">${p.tipo_pessoa || '-'}</span></div>
                <div class="col-md-4 mb-3"><strong>CPF / CNPJ:</strong><br><span class="text-dark">${p.cpf_cnpj || '-'}</span></div>
                
                <div class="col-md-4 mb-3"><strong>Regime Tributário:</strong><br><span class="text-dark">${p.regime_tributario || '-'}</span></div>
                <div class="col-md-4 mb-3"><strong>Simples Nacional:</strong><br><span class="text-dark">${p.simples_nacional || '-'}</span></div>
                <div class="col-md-4 mb-3"><strong>RG / Inscrição Estadual:</strong><br><span class="text-dark">${p.rg_ie || '-'}</span></div>

                <div class="col-md-4 mb-3"><strong>Telefone / WhatsApp:</strong><br><span class="text-dark">${p.telefone || '-'}</span></div>
                <div class="col-md-4 mb-3"><strong>E-mail:</strong><br><span class="text-dark">${p.email || '-'}</span></div>
                <div class="col-md-4 mb-3"><strong>Pessoa de Contato:</strong><br><span class="text-dark">${p.pessoa_contato || '-'}</span></div>

                <div class="col-md-4 mb-3"><strong>Responsável Comercial:</strong><br><span class="text-dark">${p.responsavel_comercial || '-'}</span></div>
                <div class="col-md-4 mb-3"><strong>Origem do Cadastro:</strong><br><span class="text-dark">${p.origem_cadastro || '-'}</span></div>
                <div class="col-md-4 mb-3"><strong>Data da Aprovação:</strong><br><span class="text-dark">${p.updated_at ? new Date(p.updated_at).toLocaleString() : '-'}</span></div>

                <div class="col-12"><hr></div>
                <h6 class="text-success fw-bold mb-3"><i class="la la-map-marker"></i> Endereço de Coleta</h6>

                <div class="col-md-3 mb-3"><strong>CEP:</strong><br><span class="text-dark">${p.cep_coleta || '-'}</span></div>
                <div class="col-md-5 mb-3"><strong>Rua:</strong><br><span class="text-dark">${p.rua_coleta || '-'}</span></div>
                <div class="col-md-2 mb-3"><strong>Número:</strong><br><span class="text-dark">${p.numero_coleta || '-'}</span></div>
                <div class="col-md-2 mb-3"><strong>Bairro:</strong><br><span class="text-dark">${p.bairro_coleta || '-'}</span></div>
                <div class="col-md-4 mb-3"><strong>Cidade / Estado:</strong><br><span class="text-dark">${p.cidade_coleta || ''} / ${p.estado_coleta || ''}</span></div>
                <div class="col-md-8 mb-3"><strong>Complemento / Referência:</strong><br><span class="text-dark">${p.complemento_coleta || '-'}</span></div>

                <div class="col-12"><hr></div>
                <h6 class="text-success fw-bold mb-3"><i class="la la-university"></i> Dados Bancários & Pix</h6>

                <div class="col-md-3 mb-3"><strong>Banco:</strong><br><span class="text-dark">${p.banco || '-'}</span></div>
                <div class="col-md-2 mb-3"><strong>Agência:</strong><br><span class="text-dark">${p.agencia || '-'}</span></div>
                <div class="col-md-3 mb-3"><strong>Conta / Dígito:</strong><br><span class="text-dark">${p.conta || '-'}-${p.digito || ''}</span></div>
                <div class="col-md-4 mb-3"><strong>Tipo de Conta:</strong><br><span class="text-dark">${p.tipo_conta || '-'}</span></div>
                <div class="col-md-6 mb-3"><strong>Chave PIX:</strong><br><span class="text-dark">${p.chave_pix || '-'}</span></div>
            </div>
        `;
        $('#corpo-detalhes').html(html);
        $('#modal-detalhes').modal('show');
    }
</script>
@endsection