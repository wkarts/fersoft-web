<script>
    $(function () {
        // Não modifica os handlers do modal de pesagens, agenda ou demais ações.
        const modal = $('#reabrirMovimentacaoOtpModal');
        const formulario = $('#formReabrirMovimentacaoOtp');
        const codigo = $('#reabrir-movimentacao-otp-code');
        const erro = $('#reabrir-movimentacao-otp-error');
        const confirmar = $('#btnReabrirMovimentacaoValidar');

        $(document).on('click', '.btn-reabrir-movimentacao', function (event) {
            event.preventDefault();
            const id = $(this).data('id');
            if (!/^\d+$/.test(String(id))) return;

            formulario[0].reset();
            erro.hide().text('');
            confirmar.prop('disabled', false);
            $('#reabrirMovimentacaoNumero').text('#' + id);
            modal.data('movimentacao-id', id);

            // O modal da agenda precisa ser fechado ANTES do OTP (Bootstrap 4).
            const resumo = $('#modalResumoColeta');
            if (resumo.length && resumo.hasClass('show')) {
                resumo.one('hidden.bs.modal', function () { modal.modal('show'); });
                resumo.modal('hide');
            } else {
                modal.modal('show');
            }
        });

        modal.on('shown.bs.modal', function () { codigo.trigger('focus'); });
        formulario.on('submit', function (event) {
            event.preventDefault();
            if (confirmar.prop('disabled')) return;

            const code = codigo.val().trim();
            erro.hide().text('');
            if (!/^\d{6}$/.test(code)) {
                erro.text('Informe um código OTP válido de 6 dígitos.').show();
                return;
            }

            confirmar.prop('disabled', true);
            $.ajax({
                url: @json(url('/movimentacaoVeiculo/reabrir')) + '/' + modal.data('movimentacao-id'),
                type: 'POST',
                dataType: 'json',
                headers: { 'Accept': 'application/json' },
                data: { _token: formulario.find('input[name="_token"]').val(), code: code }
            }).done(function (res) {
                erro.removeClass('text-danger').addClass('text-success')
                    .text(res.success || 'Agendamento reaberto com sucesso.').show();
                codigo.prop('readonly', true);
                confirmar.prop('disabled', true);
                setTimeout(function () { window.location.reload(); }, 900);
            }).fail(function (xhr) {
                let mensagem = 'Não foi possível reabrir o agendamento.';
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.error) {
                        mensagem = xhr.responseJSON.error;
                    } else if (xhr.responseJSON.errors) {
                        const primeiro = Object.values(xhr.responseJSON.errors)[0];
                        mensagem = Array.isArray(primeiro) ? primeiro[0] : primeiro;
                    } else if (xhr.responseJSON.message) {
                        mensagem = xhr.responseJSON.message;
                    }
                }
                erro.removeClass('text-success').addClass('text-danger').text(mensagem).show();
            }).always(function () {
                if (!codigo.prop('readonly')) confirmar.prop('disabled', false);
            });
        });
        modal.on('hidden.bs.modal', function () {
            formulario[0].reset();
            codigo.prop('readonly', false);
            erro.removeClass('text-success').addClass('text-danger').hide().text('');
            confirmar.prop('disabled', false);
        });
    });
</script>
