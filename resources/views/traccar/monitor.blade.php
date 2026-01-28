@extends('default.layout')

@section('content')
    <div class="container mt-5">
        <h3>{{ $title }}</h3>
        <div id="monitor" class="p-4" style="background-color: #f9f9f9; border: 1px solid #ddd; border-radius: 5px;">
            <p>Conectando ao WebSocket...</p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const monitorElement = document.getElementById('monitor');
            const socketUrl = "{{ $socket_url }}";

            if (socketUrl) {
                const socket = new WebSocket(socketUrl);

                socket.onopen = () => {
                    monitorElement.innerHTML = '<p>Conexão estabelecida.</p>';
                };

                socket.onmessage = (event) => {
                    const data = JSON.parse(event.data);
                    monitorElement.innerHTML += `<p>Dados recebidos: ${JSON.stringify(data)}</p>`;
                };

                socket.onerror = (error) => {
                    monitorElement.innerHTML = `<p style="color: red;">Erro na conexão: ${error.message}</p>`;
                };

                socket.onclose = () => {
                    monitorElement.innerHTML += '<p style="color: red;">Conexão encerrada.</p>';
                };
            } else {
                monitorElement.innerHTML = '<p style="color: red;">URL do WebSocket não configurada.</p>';
            }
        });
    </script>
@endsection
