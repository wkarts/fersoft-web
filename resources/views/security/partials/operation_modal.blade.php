<div class="modal fade" id="securityOperationModal" tabindex="-1" role="dialog" aria-labelledby="securityOperationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light-warning">
                <h5 class="modal-title" id="securityOperationModalLabel">
                    <i class="la la-shield-alt text-warning"></i>
                    Liberação de operação
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning mb-4" id="securityOperationMessage">
                    Esta operação exige liberação por usuário autorizado.
                </div>

                <input type="hidden" id="securityOperationResource" value="">
                <input type="hidden" id="securityOperationAction" value="">
                <input type="hidden" id="securityOperationRecordId" value="">
                <input type="hidden" id="securityOperationProtectionType" value="">

                <div class="form-group" id="securityAuthorizerLoginGroup">
                    <label for="securityAuthorizerLogin">Usuário autorizador</label>
                    <input type="text" class="form-control" id="securityAuthorizerLogin" autocomplete="off" placeholder="E-mail, login ou usuário autorizador">
                    <small class="form-text text-muted">Informe um usuário com privilégio para liberar esta operação.</small>
                </div>

                <div class="form-group" id="securityAuthorizerTokenGroup">
                    <label for="securityAuthorizerToken">Token de liberação</label>
                    <input type="password" class="form-control" id="securityAuthorizerToken" autocomplete="new-password" placeholder="Token individual do autorizador">
                </div>

                <div class="form-group" id="securityAuthorizerOtpGroup">
                    <label for="securityAuthorizerOtp">Código do aplicativo autenticador</label>
                    <input type="text" class="form-control" id="securityAuthorizerOtp" inputmode="numeric" autocomplete="one-time-code" placeholder="Código de 6 dígitos">
                </div>

                <div class="form-group" id="securityLegacyPasswordGroup">
                    <label for="securityLegacyPassword">Senha legada de liberação</label>
                    <input type="password" class="form-control" id="securityLegacyPassword" autocomplete="new-password" placeholder="Senha antiga de liberação">
                    <small class="form-text text-muted">Compatibilidade temporária com a rotina antiga.</small>
                </div>

                <div class="alert alert-danger d-none" id="securityOperationError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" id="securityOperationAuthorizeBtn">
                    <span class="security-operation-btn-text">Autorizar</span>
                    <span class="spinner-border spinner-border-sm d-none" id="securityOperationSpinner" role="status" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </div>
</div>
