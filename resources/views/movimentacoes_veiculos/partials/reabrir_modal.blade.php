{{-- Reaproveita o conceito/modal OTP da pesagem, sem alterar suas views. --}}
<div class="modal fade" id="reabrirMovimentacaoOtpModal" tabindex="-1" role="dialog"
     aria-labelledby="reabrirMovimentacaoOtpTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form id="formReabrirMovimentacaoOtp" autocomplete="off">
                @csrf
                <div class="modal-header">
                    <h5 id="reabrirMovimentacaoOtpTitulo" class="modal-title font-weight-bold">
                        <i class="la la-lock text-primary mr-1"></i> Confirme o código OTP
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light-warning mb-4" role="alert">
                        Deseja reabrir o agendamento <strong id="reabrirMovimentacaoNumero"></strong>?
                        O status retornará para <strong>Em percurso</strong> para permitir ajustes.
                        Horários, quilometragem, destinos, pesagens e demais vínculos serão preservados.
                    </div>
                    <label for="reabrir-movimentacao-otp-code" class="font-weight-bold">
                        Código de autenticação (6 dígitos)
                    </label>
                    <input type="text" id="reabrir-movimentacao-otp-code"
                           class="form-control text-center" placeholder="000000"
                           inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                           autocomplete="one-time-code" required aria-describedby="reabrir-movimentacao-otp-error">
                    <div id="reabrir-movimentacao-otp-error" class="text-danger small mt-2"
                         role="alert" style="display:none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" id="btnReabrirMovimentacaoValidar" class="btn btn-primary">
                        <i class="la la-unlock"></i> Validar e Reabrir
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
