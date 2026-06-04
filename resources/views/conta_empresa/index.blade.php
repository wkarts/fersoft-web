@extends('default.layout', ['title' => 'Contas da Empresa'])
@section('content')

<div class="card card-custom gutter-b">
	<div class="card-body">
		<div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-12">

				<a href="{{ route('contas-empresa.create') }}" class="btn btn-lg btn-success">
                    <i class="fa fa-plus"></i>Nova Conta
                </a>

                {{-- NOVO BOTÃO DO MANUAL --}}
                <button type="button" class="btn btn-lg btn-info ml-2" data-toggle="modal" data-target="#modalManual">
                    <i class="fa fa-question-circle"></i> Como Funciona
                </button>
			</div>
		</div>
		<br>

		<div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

			<br>
			<h4>Lista de Contas</h4>

			<div class="row">
				<div class="table-responsive">
					<table class="table">
						<thead>
							<tr class="bg-light-dark">
								<th>Nome</th>
								<th>Plano de conta</th>
								<th>Banco</th>
								<th>Agência</th>
								<th>Conta</th>
								{{-- ADICIONADO: CABEÇALHO LOCAL --}}
								<th>Local</th>
								<th>Status</th>
								<th>Dashboard</th>
								<th>Saldo</th>
								<th>Ações</th>
							</tr>
						</thead>
						<tbody class="striped">
							@foreach($data as $item)
							<tr>
								<td>{{ $item->nome }}</td>
								<td>{{ $item->plano->descricao }}</td>
								<td>{{ $item->banco }}</td>
								<td>{{ $item->agencia }}</td>
								<td>{{ $item->conta }}</td>
								
								{{-- ADICIONADO: ETIQUETA DE FILIAL/MATRIZ --}}
								<td>
									@php
										$nomeLocal = 'Matriz'; 
										if ($item->filial_id && $item->filial_id > 0) {
											$listaLocais = __locaisAtivos();
											$nomeLocal = isset($listaLocais[$item->filial_id]) ? $listaLocais[$item->filial_id] : 'Filial ' . $item->filial_id;
										}
									@endphp
									<span class="label label-inline label-light-danger font-weight-bold" style="font-size: 10px;">
										{{ $nomeLocal }}
									</span>
								</td>

								<td>
									@if($item->status)
									<i class="la la-check text-success"></i>
									@else
									<i class="la la-close text-danger"></i>
									@endif
								</td>
								<td>
									@if($item->exibir_dashboard_analitico)
										<span class="label label-inline label-light-success font-weight-bold">Exibir</span>
									@else
										<span class="label label-inline label-light-secondary font-weight-bold">Ocultar</span>
									@endif
								</td>
								<td>{{ moeda($item->saldo) }}</td>
								<td>
									<form action="{{ route('contas-empresa.destroy', $item->id) }}" method="post" id="form-{{$item->id}}" style="width: 150px">
										@method('delete')
										@csrf
										<a href="{{ route('contas-empresa.edit', $item->id) }}" class="btn btn-sm btn-warning">
											<i class="la la-edit"></i>
										</a>

										<button type="button" class="btn btn-sm btn-danger" onclick="excluirConta({{$item->id}})">
											<i class="la la-trash"></i>
										</button>

										<a title="Movimentações" href="{{ route('contas-empresa.show', $item->id) }}" class="btn btn-sm btn-dark">
											<i class="la la-list"></i>
										</a>
									</form>
								</td>
							</tr>
							@endforeach
						</tbody>
					</table>
				</div>
			</div>

		</div>
	</div>
</div>

@section('javascript')
<script>
function excluirConta(id) {
    Swal.fire({
        title: 'Excluir esta Conta?',
        text: "Isso apagará a conta e todo o histórico. Digite a senha:",
        input: 'password',
        showCancelButton: true,
        confirmButtonText: 'Confirmar',
        cancelButtonText: 'Cancelar',
        preConfirm: (senha) => {
            if (!senha) { Swal.showValidationMessage('Senha obrigatória'); }
            return senha;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "/contas-empresa/delete/" + id + "?senha=" + result.value;
        }
    });
}
</script>
@endsection
{{-- MODAL DE MANUAL DE INSTRUÇÕES --}}
<div class="modal fade" id="modalManual" tabindex="-1" role="dialog" aria-labelledby="modalManualLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title font-weight-bold" id="modalManualLabel">
                    <i class="fa fa-book text-info mr-2"></i> Manual: Gestão de Contas e Movimentações
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i aria-hidden="true" class="ki ki-close"></i>
                </button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                
                <h5 class="text-primary">1. Visão Geral e Listagem de Contas</h5>
                <p>A tela principal de "Contas da Empresa" funciona como o painel central de controle bancário.</p>
                <ul>
                    <li><strong>Acompanhamento Rápido:</strong> A grade exibe todas as contas cadastradas com informações vitais, incluindo o banco, agência, conta e o saldo atualizado.</li>
                    <li><strong>Controle de Locais:</strong> Você pode visualizar facilmente se a conta pertence à Matriz ou a uma Filial específica por meio das etiquetas visuais.</li>
                    <li><strong>Ações Disponíveis:</strong> Na coluna de ações de cada linha, você pode editar o cadastro, excluir a conta (mediante senha de segurança) ou acessar o extrato detalhado de movimentações.</li>
                </ul>

                <hr>

                <h5 class="text-primary">2. Como Cadastrar ou Editar uma Conta</h5>
                <p>Para registrar um novo banco ou caixa, clique no botão verde "Nova Conta" na tela principal.</p>
                <ul>
                    <li><strong>Dados Básicos:</strong> Preencha o nome da conta, o banco correspondente, agência e o número da conta.</li>
                    <li><strong>Integração Contábil:</strong> Selecione o "Plano de Contas" gerencial do sistema e, obrigatoriamente, vincule a "Conta Contábil" correta para garantir a conformidade na exportação para sistemas contábeis (ex: Prosoft).</li>
                    <li><strong>Vínculo e Saldos:</strong> Defina se o local da conta será a Matriz ou uma Filial. Em novos cadastros, insira com atenção o "Saldo Inicial".</li>
                    <li><strong>Visibilidade:</strong> Escolha o Status (Ativa/Desativada) e ative a opção "Exibir no Dashboard Analítico" caso queira que o saldo desta conta seja somado no painel inicial de Saldos Bancários.</li>
                </ul>

                <hr>

                <h5 class="text-primary">3. Explorando o Extrato e as Movimentações</h5>
                <p>Clicando no botão escuro de "lista" na grade de contas, você acessa a tela de Extrato da conta específica.</p>
                <ul>
                    <li><strong>Cabeçalho da Conta:</strong> Exibe os dados bancários principais e disponibiliza a opção para "Imprimir Extrato" físico ou em PDF.</li>
                    <li><strong>Filtros de Busca:</strong> Utilize o formulário central para buscar dados por período ("Data inicial" e "Data final") e por tipo ("Entrada" ou "Saída").</li>
                    <li><strong>Leitura da Tabela:</strong> A listagem apresenta primeiro o "Saldo Anterior" ao período filtrado. Logo abaixo, linha a linha, detalha-se a data, descrição, usuário, valor e saldo atualizado.</li>
                    <li><strong>Comprovantes:</strong> É possível imprimir um comprovante individual de qualquer transação clicando no botão azul de impressora.</li>
                </ul>

                <hr>

                <h5 class="text-primary">4. Lançamentos Manuais e Transferências</h5>
                <p>Na tela de extrato, utilize o formulário "Novo Lançamento Manual" para despesas/receitas não integradas automaticamente.</p>
                <ol>
                    <li>Informe a <strong>Data</strong> e a <strong>Descrição</strong> da operação.</li>
                    <li>Preencha o <strong>Valor</strong> monetário e selecione o <strong>Tipo</strong> (Entrada ou Saída).</li>
                    <li><strong>Transferências:</strong> Se selecionar uma "Conta de Destino", o sistema realizará a transferência automática (cria a saída em uma e entrada na outra).</li>
                    <li>Vincule à <strong>Categoria</strong> gerencial correta e salve.</li>
                </ol>

                <hr>

                <h5 class="text-primary">5. Sincronização Automática de Histórico</h5>
                <p>A ferramenta "Sincronizar Histórico" busca dados do Contas a Pagar e Contas a Receber e injeta no extrato.</p>
                <ul>
                    <li><strong>Como usar:</strong> É obrigatório selecionar um período de datas no filtro principal da tela antes de clicar no botão amarelo de sincronização.</li>
                    <li><strong>Inteligência de Filtro:</strong> Se a conta for "Caixa/Fundo Fixo", importará apenas movimentações em "Dinheiro". Para bancos, importará as demais (PIX, Cartão, Transferência), ignorando dinheiro físico.</li>
                    <li>Os lançamentos automáticos possuem um ícone de <strong>cadeado</strong> e não podem ser excluídos manualmente pelo extrato.</li>
                </ul>

                <hr>

                <h5 class="text-primary">6. Regras de Segurança e Exclusão</h5>
                <ul>
                    <li>Para excluir um lançamento avulso (lixeira) ou apagar uma conta inteira, será exigida a <strong>senha de segurança global</strong> do sistema.</li>
                    <li>O sistema não permite exclusão manual de transações sincronizadas (com cadeado). A correção deve ser feita na origem (Contas a Pagar/Receber).</li>
                    <li>Quando excluído com sucesso (via senha), os saldos são recalculados e estornados imediatamente.</li>
                </ul>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">Fechar Manual</button>
            </div>
        </div>
    </div>
</div>
@endsection
