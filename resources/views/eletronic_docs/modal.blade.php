<div class="modal fade right" id="modalNFe" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
     aria-hidden="true" data-backdrop="true">
    <div class="modal-dialog modal-side modal-notify modal-info" role="document">
        <!-- Content -->
        <div class="modal-content">
            <!-- Header -->
            <div class="modal-header" style="background-color:#4ac4f3">
                <p class="heading" style="color:#fff;">
                    <strong>CONSULTA DANFE: DANFE GERADO COM SUCESSO!</strong>
                </p>

                <button type="button" class="close" style="opacity:0.8;" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" style="color:red;">&times;</span>
                </button>
            </div>

            <!-- Body -->
            <div class="modal-body">
                <div class="row">
                    <div class="col-12 text-center">
                        <i class="fas fa-file fa-4x"></i>
                        <p><strong>Atenção:</strong> DANFE Gerado com Sucesso!</p>
                        <p>Escolha uma das opções abaixo para download da sua NFe.</p>
                    </div>

                    <div class="col-12 text-center">
                        <a href="{{ $pdf_url }}" class="btn btn-primary" style="background-color:#4ac4f3;border-color:#4ac4f3;">
                            Download DANFe PDF <i class="far fa-file-pdf ml-1 white-text"></i>
                        </a>
                    </div>
                    <div class="col-12 text-center mt-2">
                        <a href="{{ $xml_url }}" class="btn btn-primary" style="background-color:#4ac4f3;border-color:#4ac4f3;">
                            Download Arquivo XML <i class="far fa-download ml-1 white-text"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer flex-center">
                <a type="button" class="btn btn-outline-danger" data-dismiss="modal">Fechar</a>
            </div>
        </div>
    </div>
</div>
