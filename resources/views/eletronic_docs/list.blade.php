@extends('default.layout')

@section('content')
    <div class="container mt-4">
        <h3 class="mb-4">Consulta de Documentos Eletrônicos</h3>

        <!-- Alerta de erro -->
        @if(session('mensagem_erro'))
            <div class="alert alert-danger">{{ session('mensagem_erro') }}</div>
        @endif

        <!-- Formulário -->
        <form method="POST" action="{{ route('eletronic_docs.download') }}" id="formConsulta">
            @csrf
            <div class="form-group">
                <label for="chave">Chave de Acesso</label>
                <input type="text" id="chave" name="chave" class="form-control" required maxlength="44">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Consultar Documento</button>
        </form>

        <!-- Modal para exibir os links -->
        <div class="modal fade" id="modalDownloadLinks" tabindex="-1" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Links para Download</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body text-center">
                        <a id="downloadPdf" href="#" class="btn btn-danger" target="_blank">Download PDF</a>
                        <a id="downloadXml" href="#" class="btn btn-success" target="_blank">Download XML</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de carregamento -->
        <div class="modal fade" id="loadingModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-body text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Carregando...</span>
                        </div>
                        <p class="mt-3">Processando sua solicitação. Por favor, aguarde...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Reutilizável para Alertas e Mensagens -->
        <div class="modal fade" id="modalMensagem" tabindex="-1" role="dialog" aria-labelledby="modalMensagemLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <!-- Cabeçalho -->
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalMensagemLabel">Mensagem</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <!-- Corpo -->
                    <div class="modal-body" id="modalMensagemTexto">
                        Mensagem exibida aqui.
                    </div>
                    <!-- Rodapé -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-dismiss="modal">OK</button>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@section('javascript')
    <script>
        function abrirModalMensagem(titulo, mensagem) {
            $('#modalMensagemLabel').text(titulo);
            $('#modalMensagemTexto').html(mensagem.replace(/\n/g, '<br>')); // Formata quebra de linha
            $('#modalMensagem').modal('show');
        }

        $('#formConsulta').on('submit', function (e) {
            e.preventDefault();

            // Mostra o modal de carregamento
            $('#loadingModal').modal('show');

            const formData = $(this).serialize();

            $.ajax({
                url: "{{ route('eletronic_docs.download') }}",
                method: "POST",
                data: formData,
                success: function (response) {
                    $('#loadingModal').modal('hide'); // Fecha modal de carregamento

                    // Atualiza links no modal
                    $('#downloadPdf').attr('href', response.pdf_url);
                    $('#downloadXml').attr('href', response.xml_url);

                    // Exibe o modal de download
                    $('#modalDownloadLinks').modal('show');
                },
                error: function (xhr) {
                    $('#loadingModal').modal('hide'); // Fecha modal de carregamento
                    let errorMessage = 'Erro desconhecido.';

                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        errorMessage = xhr.responseJSON.error;
                    } else if (xhr.responseText) {
                        errorMessage = xhr.responseText;
                    }

                    abrirModalMensagem('Erro', errorMessage + "<br><br>Tente novamente.");
                }
            });
        });
    </script>
@endsection
