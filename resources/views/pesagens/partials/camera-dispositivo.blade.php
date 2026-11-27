<fieldset class="form-group col-lg-12" data-camera-ticket>
    <div class="d-flex flex-wrap" data-fotos-salvas></div>
    <button type="button" class="btn btn-outline-primary" data-acao="abrir">
        <i class="fa fa-camera" aria-hidden="true"></i> Tirar foto
    </button>
    <div class="mt-2" data-painel hidden>
        <label data-selecao hidden>Câmera
            <select class="form-control" data-cameras></select>
        </label>
        <video class="w-100" style="max-height: 42vh" muted autoplay playsinline hidden></video>
        <img class="w-100" style="max-height: 42vh; object-fit: contain" data-previa alt="Prévia da captura" hidden>
        <label class="w-100" data-rotulo hidden>Legenda (opcional)
            <input type="text" class="form-control" maxlength="160" data-legenda>
        </label>
        <div class="mt-2">
            <button type="button" class="btn btn-primary" data-acao="capturar" hidden>Capturar</button>
            <button type="button" class="btn btn-success" data-acao="usar" hidden>Usar foto</button>
            <button type="button" class="btn btn-secondary" data-acao="refazer" hidden>Tirar novamente</button>
            <button type="button" class="btn btn-light" data-acao="cancelar">Cancelar câmera</button>
        </div>
    </div>
    <div role="status" class="small mt-2" data-aviso></div>
    <div class="d-flex flex-wrap" data-pendentes></div>
</fieldset>
