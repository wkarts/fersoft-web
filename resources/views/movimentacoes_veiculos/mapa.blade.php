@extends('default.layout')

@section('content')

    <!-- CSS do Leaflet, Cluster e Animações -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />

    <style>
        /* Marcador base */
        .marcador-frota {
            width: 34px;
            height: 34px;
            border-radius: 50% 50% 50% 0;
            border: 3px solid #fff;
            box-shadow: 0 0 8px rgba(0,0,0,0.6);
            transform: rotate(-45deg);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .marcador-frota i {
            transform: rotate(45deg);
            color: #fff;
            font-size: 14px;
        }

        /* Efeito Pulsar/Piscar funcionando */
        .marcador-piscante {
            animation: pulsarIcone 1.2s infinite alternate ease-in-out;
        }

        @keyframes pulsarIcone {
            0% {
                transform: rotate(-45deg) scale(0.92);
                box-shadow: 0 0 4px rgba(0,0,0,0.4);
                filter: brightness(0.9);
            }
            100% {
                transform: rotate(-45deg) scale(1.22);
                box-shadow: 0 0 16px rgba(0,0,0,0.85);
                filter: brightness(1.2);
            }
        }

        /* Layout da Área do Mapa */
        .map-wrapper {
            position: relative;
            height: 720px;
            width: 100%;
            border-bottom-left-radius: 6px;
            border-bottom-right-radius: 6px;
            overflow: hidden;
        }
        #mapa-frota {
            height: 100%;
            width: 100%;
            z-index: 1;
        }

        /* Painel Suspenso Lateral */
        .painel-dispositivos-traccar {
            position: absolute;
            top: 15px;
            left: 15px;
            width: 320px;
            max-height: calc(100% - 30px);
            background: #ffffff;
            z-index: 1000;
            border-radius: 8px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.25);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .painel-traccar-header {
            padding: 12px;
            background: #f8f9fa;
            border-bottom: 1px solid #ebedf3;
        }
        .painel-traccar-body {
            overflow-y: auto;
            flex-grow: 1;
        }
        .item-dispositivo-traccar {
            padding: 10px 14px;
            border-bottom: 1px solid #f1f1f4;
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: background 0.2s;
        }
        .item-dispositivo-traccar:hover {
            background: #f3f6f9;
        }

        /* Estilo dos agrupamentos (Clusters 2, 3...) */
        .marker-cluster-small, .marker-cluster-medium, .marker-cluster-large {
            background-color: rgba(54, 153, 255, 0.35) !important;
        }
        .marker-cluster-small div, .marker-cluster-medium div, .marker-cluster-large div {
            background-color: #3699ff !important;
            color: #fff !important;
            font-weight: bold;
            font-size: 13px;
        }
    </style>

    <div class="card card-custom gutter-b">
        <div class="card-header border-0 pt-5">
            <h3 class="card-title align-items-start flex-column">
            <span class="card-label font-weight-bolder text-dark">
                <i class="la la-map-marked text-primary mr-2"></i> Monitoramento em Tempo Real
            </span>
                <span class="text-muted mt-2 font-weight-bold font-size-sm">Frota, Equipamentos e Locações</span>
            </h3>
            <div class="card-toolbar">
                <div class="d-flex align-items-center flex-wrap">
                    <span class="badge badge-success mr-2 mb-1">🟢 Em Movimento</span>
                    <span class="badge badge-warning mr-2 mb-1">🟡 Ligado / Parado</span>
                    <span class="badge badge-danger mr-2 mb-1">🔴 Desligado</span>
                    <span class="badge mr-2 mb-1 text-white" style="background-color: #8e44ad;">🟣 App Celular</span>
                    <span class="badge mb-1 text-white" style="background-color: #1BC5BD;">🩵 Equipamento/Locação</span>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="map-wrapper">
                <!-- Menu Suspenso Lateral -->
                <div class="painel-dispositivos-traccar">
                    <div class="painel-traccar-header">
                        <div class="input-icon input-icon-right">
                            <input type="text" id="filtroDispositivos" class="form-control form-control-sm" placeholder="Pesquisar por placa, produto...">
                            <span><i class="la la-search text-muted"></i></span>
                        </div>
                    </div>
                    <div class="painel-traccar-body" id="listaDispositivosFrota">
                        <div class="text-center p-4 text-muted font-size-sm">
                            <i class="la la-spinner la-spin text-primary"></i> Carregando lista...
                        </div>
                    </div>
                </div>

                <!-- Mapa -->
                <div id="mapa-frota"></div>
            </div>
        </div>
    </div>

@endsection

@section('javascript')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

    <script>
        $(document).ready(function() {
            var osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap',
                maxZoom: 19
            });

            var googleTrafficLayer = L.tileLayer('https://mt1.google.com/vt/lyrs=m,traffic&x={x}&y={y}&z={z}', {
                attribution: '© Google Maps Traffic',
                maxZoom: 19
            });

            var map = L.map('mapa-frota', {
                center: [-12.924687, -38.478212],
                zoom: 12,
                layers: [googleTrafficLayer]
            });

            var baseMaps = {
                "Trânsito ao Vivo (Google)": googleTrafficLayer,
                "Mapa Padrão (OSM)": osmLayer
            };
            L.control.layers(baseMaps).addTo(map);

            var clusterGroup = L.markerClusterGroup({
                showCoverageOnHover: false,
                maxClusterRadius: 35,
                spiderfyOnMaxZoom: true
            });
            map.addLayer(clusterGroup);

            var marcadores = {};
            var dadosFrota = [];

            function renderizarLista(frota) {
                var filtro = $('#filtroDispositivos').val().toLowerCase();
                var container = $('#listaDispositivosFrota');
                container.empty();

                var filtrados = frota.filter(function(item) {
                    var nome = (item.placa || item.nome || '').toLowerCase();
                    var motorista = (item.motorista || '').toLowerCase();
                    var produto = (item.produto || '').toLowerCase();
                    return nome.includes(filtro) || motorista.includes(filtro) || produto.includes(filtro);
                });

                if (filtrados.length === 0) {
                    container.html('<div class="text-center p-3 text-muted">Nenhum item encontrado.</div>');
                    return;
                }

                filtrados.forEach(function(item) {
                    var isLocacao = item.tipo_pino === 'locacao';
                    var icone = isLocacao ? 'la la-box' : 'la la-truck';
                    var linhaPrincipal = item.placa || item.nome;
                    var linhaSub = isLocacao ? (item.produto || 'Equipamento') : (item.motorista || 'Sem motorista');

                    var html = `
                    <div class="item-dispositivo-traccar" onclick="focarItem('${item.id}')">
                        <div class="mr-3">
                            <span class="btn btn-icon btn-light btn-circle btn-sm" style="background-color: ${item.cor}20; color: ${item.cor}; border: 1px solid ${item.cor};">
                                <i class="${icone}"></i>
                            </span>
                        </div>
                        <div class="d-flex flex-column flex-grow-1 overflow-hidden">
                            <span class="font-weight-bold text-dark font-size-sm text-truncate">${linhaPrincipal}</span>
                            <span class="text-muted font-size-xs text-truncate">${linhaSub}</span>
                        </div>
                        <div class="text-right ml-2">
                            <span class="badge badge-pill" style="background-color: ${item.cor}; width: 10px; height: 10px; padding: 0; display: inline-block;"></span>
                        </div>
                    </div>
                `;
                    container.append(html);
                });
            }

            $('#filtroDispositivos').on('keyup', function() {
                renderizarLista(dadosFrota);
            });

            // Clique para centralizar o mapa no item desejado
            window.focarItem = function(id) {
                var marcador = marcadores[id];
                if (marcador) {
                    clusterGroup.zoomToShowLayer(marcador, function() {
                        map.flyTo(marcador.getLatLng(), 18, {
                            animate: true,
                            duration: 1
                        });
                        setTimeout(function() {
                            marcador.openPopup();
                        }, 1050);
                    });
                }
            };

            function atualizarMapa() {
                $.ajax({
                    url: '/traccar/posicoes-tempo-real',
                    type: 'GET',
                    success: function(frota) {
                        if (!Array.isArray(frota)) return;
                        dadosFrota = frota;
                        renderizarLista(frota);

                        frota.forEach(function(item) {
                            var isLocacao = item.tipo_pino === 'locacao';
                            var iconeClasse = isLocacao ? 'la la-box' : 'la la-truck';

                            // Injeção da classe 'marcador-piscante'
                            var iconePersonalizado = L.divIcon({
                                className: 'custom-div-icon',
                                html: `<div class="marcador-frota marcador-piscante" style="background-color: ${item.cor};"><i class="${iconeClasse}"></i></div>`,
                                iconSize: [34, 44],
                                iconAnchor: [17, 44],
                                popupAnchor: [0, -38]
                            });

                            // Balão exibindo Nome do Produto se for Locação
                            var popupHtml = `
                            <div style="min-width: 190px;">
                                <h6 class="font-weight-bolder text-dark mb-2">
                                    <span class="label label-inline label-light-primary font-weight-bold">
                                        <i class="${iconeClasse} mr-1"></i> ${item.placa}
                                    </span>
                                </h6>
                                ${isLocacao ? `
                                    <div class="mb-2" style="font-size: 12px; color: #181C32;">
                                        <i class="la la-cube text-primary mr-1"></i><b>Equipamento:</b><br>${item.produto || 'Não informado'}
                                    </div>
                                    <div class="mb-2" style="font-size: 11px; color: #7E8299;">
                                        <i class="la la-building mr-1"></i><b>Cliente:</b> ${item.motorista}
                                    </div>
                                ` : `
                                    <div class="mb-2" style="font-size: 12px; color: #464E5F;">
                                        <i class="la la-user text-primary mr-1"></i><b>Motorista:</b><br>${item.motorista}
                                    </div>
                                `}
                                <hr class="my-1">
                                <div class="my-2" style="color: ${item.cor}; font-size: 12px; font-weight: bold;">
                                    ${item.status}
                                </div>
                                <hr class="my-1">
                                <span class="text-muted" style="font-size: 10px;">
                                    <i class="la la-clock-o"></i> Atualizado: ${item.atualizacao}
                                </span>
                            </div>
                        `;

                            if (marcadores[item.id]) {
                                marcadores[item.id].setLatLng([item.lat, item.lon]);
                                marcadores[item.id].setIcon(iconePersonalizado);
                                marcadores[item.id].setPopupContent(popupHtml);
                            } else {
                                var novoMarcador = L.marker([item.lat, item.lon], {icon: iconePersonalizado})
                                    .bindPopup(popupHtml);

                                clusterGroup.addLayer(novoMarcador);
                                marcadores[item.id] = novoMarcador;
                            }
                        });
                    },
                    error: function(err) {
                        console.error("Erro ao sincronizar posições:", err);
                    }
                });
            }

            atualizarMapa();
            setInterval(atualizarMapa, 10000);
        });
    </script>
@endsection
