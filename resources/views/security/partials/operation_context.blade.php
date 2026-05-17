@php
    $securityOperationContextPayload = $securityOperationContext ?? null;
@endphp

<script type="text/javascript">
    window.SecurityOperationPage = @json($securityOperationContextPayload);
</script>
