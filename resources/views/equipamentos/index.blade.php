@extends('default.layout')
@section('content')

    <!-- Leaflet CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        /* Estilos do Menu Flutuante no Mapa */
        .map-wrapper {
            position: relative;
            width: 100%;
            margin-bottom: 30px;
        }

        #mapa_equipamentos {
            height: 600px;
            width: 100%;
            border-radius: 12px;
            border: 2px solid #e4e6ef;
            z-index: 1;
        }

        .floating-menu {
            position: absolute;
            top: 15px;
            right: 15px;
            z-index: 1000; /* Fica por cima do Leaflet */
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            width: 280px;
            max-height: 570px;
            display: flex;
            flex-direction: column;
            border: 1px solid #e4e6ef;
        }

        .floating-menu-header {
            font-weight: 700;
            color: #3F4254;
            border-bottom: 2px solid #EBEDF3;
            padding-bottom: 10px;
            margin-bottom: 10px;
            font-size: 1.05rem;
        }

        .floating-list {
            overflow-y: auto;
            flex-grow: 1;
            padding-right: 5px;
        }

        /* Scrollbar customizada para o menu */
        .floating-list::-webkit-scrollbar { width: 6px; }
        .floating-list::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px;}
        .floating-list::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 4px;}
        .floating-list::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }

        .floating-item {
            cursor: pointer;
            padding: 10px;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.2s ease;
            border-radius: 6px;
        }

        .floating-item:hover {
            background-color: #f3f6f9;
            transform: translateX(-2px);
        }

        .floating-item.active-item {
            background-color: #e1f0ff;
            border-left: 4px solid #3699FF;
        }

        .row-clicavel {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .row-clicavel:hover {
            background-color: #f3f6f9 !important;
        }
    </style>

    <div class="d-flex flex-column flex-column-fluid" id="kt_content">
        <div class="card card-custom gutter-b shadow-sm">
            <div class="card-header">
                <div class="card-title">
                    <h3 class="card-label font-weight-bolder">
                        <i class="la la-map-marked-alt text-primary icon-xl mr-2"></i>
                        {{ $title }}
                    </h3>
                </div>
            </div>

            <div class="card-body">

                <!-- MAPA LEAFLET COM MENU FLUTUANTE -->
                <div class="map-wrapper">
                    <div id="mapa_equipamentos"></div>

                    <!-- Menu Flutuante -->
                    <div class="floating-menu">
                        <div class="floating-menu-header">
                            <i class="fas fa-layer-group text-primary mr-1"></i> Equipamentos em Campo
                        </div>
                        <div class="form-group mb-2">
                            <input type="text" id="filtroMapa" class="form-control form-control-sm" placeholder="🔍 Buscar caçamba..." onkeyup="filtrarMenuMapa()">
                        </div>
                        <div class="floating-list" id="lista_flutuante_conteudo">
                            <!-- Preenchido via JavaScript -->
                        </div>
                    </div>
                </div>

                <hr class="my-6">

                <h4 class="font-weight-bold mb-4 text-dark"><i class="fas fa-table mr-2 text-primary"></i> Tabela Geral de Ativos</h4>

                <!-- TABELA DE RESUMO (Agora Clicável) -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead class="thead-dark">
                        <tr>
                            <th>Código/Ref</th>
                            <th>Equipamento / Máquina</th>
                            <th>Status / Localização</th>
                            <th>Cliente / Fornecedor Atual</th>
                            <th>Endereço</th>
                            <th class="text-center">Ação</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($equipamentos as $eq)
                            <tr class="row-clicavel" onclick="focarNoMapa({{ $eq->id }})">
                                <td class="font-weight-bold">{{ $eq->referencia ?? $eq->codBarras }}</td>
                                <td>{{ $eq->nome }}</td>
                                <td>
                                    @if($eq->status_localizacao == 'cliente' || $eq->status_localizacao == 'fornecedor')
                                        <span class="badge badge-danger badge-pill">Alocado no Parceiro</span>
                                    @else
                                        <span class="badge badge-success badge-pill">Pátio / Base</span>
                                    @endif
                                </td>
                                <td class="font-weight-bold text-primary">
                                    {{ $eq->clienteAtual->razao_social ?? $eq->fornecedorAtual->razao_social ?? 'Empresa (Base)' }}
                                </td>
                                <td>{{ $eq->endereco_atual ?? 'Pátio Interno' }}</td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-light-primary font-weight-bold">
                                        <i class="fas fa-crosshairs"></i> Localizar
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        var map;
        var markersDict = {}; // Dicionário para guardar as instâncias dos marcadores
        var marcadoresDados = @json($marcadores);

        document.addEventListener("DOMContentLoaded", function() {
            // Inicializa o mapa
            map = L.map('mapa_equipamentos').setView([{{ $latBase }}, {{ $lonBase }}], 12);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            // Configuração dos Ícones
            var iconBase = new L.Icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
            });

            var iconCliente = new L.Icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
            });

            var iconFornecedor = new L.Icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-orange.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
            });

            var menuFlutuanteHTML = '';

            marcadoresDados.forEach(function(m) {
                // Seleciona a cor do ícone
                var icone = (m.cor === 'green') ? iconBase : ((m.cor === 'orange') ? iconFornecedor : iconCliente);
                var statusBadge = (m.cor === 'green') ? '<span class="text-success"><i class="fas fa-home"></i> Base</span>' : '<span class="text-danger"><i class="fas fa-truck-loading"></i> Alocado</span>';

                // Adiciona o Marcador no mapa
                var marker = L.marker([m.lat, m.lon], {icon: icone}).addTo(map);

                // Conteúdo do Balão (Popup)
                var popupContent = `
                <div style="font-size: 13px; min-width: 200px;">
                    <b style="color: #3699FF; font-size: 15px; display: block; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 8px;">
                        ${m.codigo ? m.codigo + ' - ' : ''}${m.nome}
                    </b>
                    <p class="mb-1"><b>Status:</b> ${statusBadge}</p>
                    <p class="mb-1"><b>Local:</b> ${m.local}</p>
                    <p class="mb-0 text-muted" style="line-height: 1.2;"><small><b>Endereço:</b> ${m.endereco}</small></p>
                </div>
            `;
                marker.bindPopup(popupContent);

                // Guarda a referência do marcador para poder abri-lo via JS
                markersDict[m.id] = marker;

                // Adiciona o item na lista do menu flutuante
                menuFlutuanteHTML += `
                <div class="floating-item" id="menu-item-${m.id}" onclick="focarNoMapa(${m.id})">
                    <div class="d-flex justify-content-between">
                        <strong class="text-dark d-block text-truncate" style="max-width: 170px;">${m.nome}</strong>
                        ${(m.cor === 'green') ? '<i class="fas fa-circle text-success" style="font-size: 10px; margin-top: 5px;"></i>' : '<i class="fas fa-circle text-danger" style="font-size: 10px; margin-top: 5px;"></i>'}
                    </div>
                    <small class="text-muted d-block text-truncate" title="${m.local}">${m.local}</small>
                </div>
            `;
            });

            // Injeta a lista no menu lateral do mapa
            document.getElementById('lista_flutuante_conteudo').innerHTML = menuFlutuanteHTML;
        });

        // Função global para focar a câmara no equipamento
        window.focarNoMapa = function(equipamentoId) {
            var dados = marcadoresDados.find(item => item.id == equipamentoId);

            if (!dados) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Atenção', 'Este equipamento não possui coordenadas GPS válidas para localização.', 'warning');
                } else {
                    alert('Equipamento sem localização no mapa.');
                }
                return;
            }

            // Remove a classe 'active' de todos os itens do menu
            document.querySelectorAll('.floating-item').forEach(el => el.classList.remove('active-item'));

            // Adiciona 'active' no clicado
            let menuClick = document.getElementById('menu-item-' + equipamentoId);
            if(menuClick) {
                menuClick.classList.add('active-item');
                menuClick.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }

            // Animação de Voo (FlyTo) do Leaflet para as coordenadas
            map.flyTo([dados.lat, dados.lon], 17, {
                animate: true,
                duration: 1.5 // Segundos de animação
            });

            // Após o voo, abre automaticamente o balão popup
            setTimeout(() => {
                if (markersDict[equipamentoId]) {
                    markersDict[equipamentoId].openPopup();
                }
            }, 1500);

            // Rola a página para cima (para a secção do mapa) suavemente
            document.getElementById('mapa_equipamentos').scrollIntoView({ behavior: 'smooth', block: 'center' });
        };

        // Função para pesquisar dentro do Menu Flutuante
        window.filtrarMenuMapa = function() {
            let filtro = document.getElementById('filtroMapa').value.toLowerCase();
            let itens = document.querySelectorAll('.floating-item');

            itens.forEach(function(item) {
                let texto = item.innerText.toLowerCase();
                if (texto.includes(filtro)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        };
    </script>
@endsection
