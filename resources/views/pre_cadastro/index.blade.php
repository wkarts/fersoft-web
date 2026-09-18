@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">
    <div class="card-header">
        <div class="card-title">
            <h3 class="card-label">
                <i class="la la-user-clock text-primary fs-2 me-2"></i>
                Pré-Cadastros Pendentes
                <small>Aprove os parceiros, importe para o ERP e libere o acesso ao portal</small>
            </h3>
        </div>
      	
      	<div class="card-toolbar">
            <a href="/cadastros-pendentes/aprovados" class="btn btn-success font-weight-bold">
                <i class="la la-check-circle"></i> Ver Aprovados
            </a>
        </div>
    </div>
    
  	  
    <div class="card-body">

        @if(session('sucesso'))
        <div class="alert alert-success">
            {{ session('sucesso') }}
        </div>
        @endif

        @if(session('erro'))
        <div class="alert alert-danger">
            {{ session('erro') }}
        </div>
        @endif

        <div class="table-responsive">
            <table class="table table-hover table-bordered table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th>Data</th>
                        <th>Nome / Razão Social</th>
                        <th>Tipo</th>
                        <th>CPF / CNPJ</th>
                        <th>Telefone</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendentes as $p)
                    <tr>
                        <td class="align-middle">{{ \Carbon\Carbon::parse($p->created_at)->format('d/m/Y H:i') }}</td>
                        <td class="align-middle font-weight-bold">{{ $p->nome_completo }}</td>
                        <td class="align-middle">
                            <span class="badge badge-{{ $p->tipo_pessoa == 'Fisica' ? 'info' : 'primary' }}">
                                {{ $p->tipo_pessoa }}
                            </span>
                        </td>
                        <td class="align-middle">{{ $p->cpf_cnpj }}</td>
                        <td class="align-middle">{{ $p->telefone }}</td>
                        <td class="align-middle text-center">
                            <!-- Botão para ver todos os dados detalhados -->
                            <button type="button" class="btn btn-sm btn-light-info font-weight-bold" onclick="verDetalhes({{ json_encode($p) }})">
                                <i class="la la-eye"></i> Detalhes
                            </button>

                            <!-- Botões de Aprovação com Escolha de Destino -->
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-danger font-weight-bold" onclick="abrirModalRejeicao({{ $p->id }})">
                                    <i class="la la-times"></i> Rejeitar
                                </button>
                              	<button id="btnGroupDrop1" type="button" class="btn btn-sm btn-success font-weight-bold dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="la la-check"></i> Aprovar Como...
                                </button>
                                <div class="dropdown-menu" aria-labelledby="btnGroupDrop1">
                                    <a class="dropdown-item text-success font-weight-bold" href="#" onclick="aprovarParceiro({{ $p->id }}, 'cliente')">
                                        <i class="la la-user-tag me-1"></i> Cadastrar como Cliente
                                    </a>
                                    <a class="dropdown-item text-primary font-weight-bold" href="#" onclick="aprovarParceiro({{ $p->id }}, 'fornecedor')">
                                        <i class="la la-truck me-1"></i> Cadastrar como Fornecedor
                                    </a>
                                    <a class="dropdown-item text-dark font-weight-bold" href="#" onclick="aprovarParceiro({{ $p->id }}, 'ambos')">
                                        <i class="la la-users me-1"></i> Cadastrar como Ambos
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="la la-inbox fs-1 mb-2 d-block"></i>
                            Nenhum cadastro pendente no momento.
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
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title text-white"><i class="la la-user-shield me-1"></i> Detalhes Completos do Pré-Cadastro</h5>
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

<!-- Modal de Rejeição -->
<div class="modal fade" id="modal-rejeicao" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="form-rejeitar" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title text-white">Motivo da Rejeição / Correção</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="font-weight-bold">Explique o que precisa ser ajustado:</label>
                        <textarea name="motivo" class="form-control" rows="4" required placeholder="Ex: O documento enviado está ilegível ou o CNPJ não confere..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Confirmar Rejeição e Avisar Cliente</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function abrirModalRejeicao(id) {
        $('#form-rejeitar').attr('action', '/cadastros-pendentes/rejeitar/' + id);
        $('#modal-rejeicao').modal('show');
    }
</script>

<!-- Formulário oculto para submeter a aprovação com o tipo escolhido -->
<form id="form-aprovar-destino" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="tipo_cadastro" id="input_tipo_cadastro">
</form>

@endsection

@section('javascript')
<script>
    // Função para disparar a aprovação inteligente
    function aprovarParceiro(id, destino) {
        let textoDestino = destino === 'cliente' ? 'Cliente' : (destino === 'fornecedor' ? 'Fornecedor' : 'Cliente e Fornecedor');
        
        swal({
            title: "Aprovar e Importar?",
            text: "O cadastro será enviado para a tabela de " + textoDestino + ", o portal será liberado e a senha será enviada via WhatsApp para o parceiro.",
            icon: "warning",
            buttons: ["Cancelar", "Sim, Aprovar!"],
            dangerMode: false,
        }).then((willApprove) => {
            if (willApprove) {
                let form = $('#form-aprovar-destino');
                form.attr('action', '/cadastros-pendentes/aprovar/' + id);
                $('#input_tipo_cadastro').val(destino);
                form.submit();
            }
        });
    }

    // Função para popular todos os campos preenchidos no Modal
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

                <div class="col-md-4 mb-3"><strong>Responsável Comercial:</strong><br><span class="text-dark">${p.responsável_comercial || p.responsavel_comercial || '-'}</span></div>
                <div class="col-md-4 mb-3"><strong>Origem do Cadastro:</strong><br><span class="text-dark">${p.origem_cadastro || '-'}</span></div>
                <div class="col-md-4 mb-3"><strong>Data da Solicitação:</strong><br><span class="text-dark">${p.created_at ? new Date(p.created_at).toLocaleString() : '-'}</span></div>

                <div class="col-12"><hr></div>
                <h6 class="text-primary fw-bold mb-3"><i class="la la-map-marker"></i> Endereço de Coleta</h6>

                <div class="col-md-3 mb-3"><strong>CEP:</strong><br><span class="text-dark">${p.cep_coleta || '-'}</span></div>
                <div class="col-md-5 mb-3"><strong>Rua:</strong><br><span class="text-dark">${p.rua_coleta || '-'}</span></div>
                <div class="col-md-2 mb-3"><strong>Número:</strong><br><span class="text-dark">${p.numero_coleta || '-'}</span></div>
                <div class="col-md-2 mb-3"><strong>Bairro:</strong><br><span class="text-dark">${p.bairro_coleta || '-'}</span></div>
                <div class="col-md-4 mb-3"><strong>Cidade / Estado:</strong><br><span class="text-dark">${p.cidade_coleta || ''} / ${p.estado_coleta || ''}</span></div>
                <div class="col-md-8 mb-3"><strong>Complemento / Referência:</strong><br><span class="text-dark">${p.complemento_coleta || '-'}</span></div>

                <div class="col-12"><hr></div>
                <h6 class="text-primary fw-bold mb-3"><i class="la la-university"></i> Dados Bancários & Pix</h6>

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