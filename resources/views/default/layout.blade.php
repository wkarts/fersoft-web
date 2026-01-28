<style type="text/css">
	.select2-selection__choice__remove::before{
		display: none
	}
</style>

@php use Carbon\Carbon; @endphp
<script>
    // diferença, em minutos, entre UTC e o timezone do servidor
    window.SERVER_OFFSET_MIN = {{ Carbon::now()->getOffset() / 60 }};

    /**
     * Retorna um objeto Date já ajustado para o horário do servidor.
     */
    function getServerDate() {
        const d = new Date();
        const serverOffset = window.SERVER_OFFSET_MIN;
        const localOffset  = -d.getTimezoneOffset();       // sinal invertido
        const diffMin      = serverOffset - localOffset;
        d.setMinutes(d.getMinutes() + diffMin);
        return d;
    }

    /**
     * Formata um Date para a string “YYYY-MM-DDThh:mm:ss”
     * compatível com <input type="datetime-local">
     */
    function formatDateTimeLocal(d) {
        const pad = n => String(n).padStart(2,'0');
        return  d.getFullYear()
            + '-' + pad(d.getMonth()+1)
            + '-' + pad(d.getDate())
            + 'T' + pad(d.getHours())
            + ':' + pad(d.getMinutes())
            + ':' + pad(d.getSeconds());
    }
</script>


@extends('default/menu_'.$tipoMenu)

