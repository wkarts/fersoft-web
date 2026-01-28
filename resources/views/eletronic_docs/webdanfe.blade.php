@extends('default.layout')

@section('content')
    <div class="container mt-4">
        <h3 class="mb-4">Visualizar Documento Eletrônico</h3>

        <!-- Modal de Carregamento -->
        <div id="loadingModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Carregando...</span>
                        </div>
                        <p class="mt-3">Buscando o XML...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Iframe para carregar o WebDanfe -->
        <div class="d-flex justify-content-center align-items-center" style="height: 80vh;">
            <iframe id="danfeFrame" src="{{ $siteUrl }}" frameborder="0" style="width: 100%; height: 100%;"></iframe>
        </div>
    </div>
@endsection

@section('javascript')
    <script>
        $(document).ready(function () {
            // Exibe o modal de carregamento
            $('#loadingModal').modal('show');

            // Aguarda o carregamento do iframe
            $('#danfeFrame').on('load', function () {
                $('#loadingModal').modal('hide');
            });
        });
    </script>
@endsection
