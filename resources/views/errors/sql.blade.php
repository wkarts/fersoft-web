<!DOCTYPE html>
<html lang="br">
<head>
    <meta charset="utf-8" />
    <title>Erro de Banco de Dados (SQL)</title>
    <meta name="description" content="Erro de Banco de Dados">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700|Roboto:300,400,500,600,700">
    <link href="/metronic/css/fullcalendar.bundle.css" rel="stylesheet" type="text/css" />
    <link href="/metronic/css/wizard.css" rel="stylesheet" type="text/css" />
    <link href="/css/style.css" rel="stylesheet" type="text/css" />
    <link href="/metronic/css/plugins.bundle.css" rel="stylesheet" type="text/css" />
    <link href="/metronic/css/prismjs.bundle.css" rel="stylesheet" type="text/css" />
    <link href="/metronic/css/style.bundle.css" rel="stylesheet" type="text/css" />

    <!-- Styles adicionais para caixa memo -->
    <style>
        .error-details {
            background-color: #f8f9fa;
            border: 1px solid #d1d3e2;
            padding: 10px;
            border-radius: 5px;
            max-height: 200px;
            overflow-y: auto;
            word-wrap: break-word;
            white-space: pre-wrap;
            font-size: 0.9rem;
        }
        .copy-button {
            background-color: #3699FF;
            color: #fff;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            margin-top: 10px;
        }
        .copy-button:hover {
            background-color: #1d6ed8;
        }
        /* Error details */
        .flex-root .bgi-no-repeat .error-details{
            min-height:184px;
            width:100% !important;
        }

        /* Copy button */
        .flex-root .bgi-no-repeat .copy-button{
            width:15% !important;
        }

        /* Button */
        .flex-root .bgi-no-repeat .btn-primary{
            width:15% !important;
        }
        /* Button */
        .flex-root .bgi-no-repeat .btn-primary{
            transform:translatex(3px) translatey(-612px);
        }
    </style>

    <!-- Inclusão Centralizada dos Scripts de Monitoramento -->
    @include('layouts.tracking')

    <!-- Configurações Adicionais -->
    <script>var HOST_URL = "/metronic/theme/html/tools/preview";</script>
    <script>
        var KTAppSettings = {
            "breakpoints": {
                "sm": 576,
                "md": 768,
                "lg": 992,
                "xl": 1200,
                "xxl": 1400
            },
            "colors": {
                "theme": {
                    "base": {
                        "white": "#ffffff",
                        "primary": "#3699FF",
                        "secondary": "#E5EAEE",
                        "success": "#1BC5BD",
                        "info": "#8950FC",
                        "warning": "#FFA800",
                        "danger": "#F64E60",
                        "light": "#E4E6EF",
                        "dark": "#181C32"
                    },
                    "light": {
                        "white": "#ffffff",
                        "primary": "#E1F0FF",
                        "secondary": "#EBEDF3",
                        "success": "#C9F7F5",
                        "info": "#EEE5FF",
                        "warning": "#FFF4DE",
                        "danger": "#FFE2E5",
                        "light": "#F3F6F9",
                        "dark": "#D6D6E0"
                    },
                    "inverse": {
                        "white": "#ffffff",
                        "primary": "#ffffff",
                        "secondary": "#3F4254",
                        "success": "#ffffff",
                        "info": "#ffffff",
                        "warning": "#ffffff",
                        "danger": "#ffffff",
                        "light": "#464E5F",
                        "dark": "#ffffff"
                    }
                },
                "gray": {
                    "gray-100": "#F3F6F9",
                    "gray-200": "#EBEDF3",
                    "gray-300": "#E4E6EF",
                    "gray-400": "#D1D3E0",
                    "gray-500": "#B5B5C3",
                    "gray-600": "#7E8299",
                    "gray-700": "#5E6278",
                    "gray-800": "#3F4254",
                    "gray-900": "#181C32"
                }
            },
            "font-family": "Poppins"
        };
    </script>
</head>
<body id="kt_body" class="header-fixed header-mobile-fixed subheader-enabled subheader-fixed aside-enabled aside-fixed aside-minimize-hoverable page-loading">
<div class="d-flex flex-column flex-root">
    <div class="d-flex flex-row-fluid flex-column bgi-size-cover bgi-position-center bgi-no-repeat p-10 p-sm-30">
        <h1 class="font-weight-boldest text-dark-75 mt-15" style="font-size: 3rem">Erro de Banco de Dados</h1>
        <p class="font-size-h3 text-muted font-weight-normal">OOPS! Ocorreu um problema com o banco de dados.</p>
        <p class="font-size-h6 text-muted font-weight-normal">Tente novamente ou entre em contato com o suporte técnico.</p>
        <pre class="error-details" id="error-details">{{ $motivo ?? 'Erro desconhecido' }}</pre>
        <button class="copy-button" onclick="copyErrorDetails()">Copiar Detalhes do Erro</button>
        <button onclick="window.history.back();" class="btn btn-primary mt-4">Voltar para a Página Anterior</button>
    </div>
</div>

<script>
    function copyErrorDetails() {
        const errorDetails = document.getElementById('error-details').textContent;
        navigator.clipboard.writeText(errorDetails).then(() => {
            alert('Detalhes copiados para a área de transferência!');
        });
    }
</script>

<script src="/metronic/js/plugins.bundle.js"></script>
<script src="/metronic/js/prismjs.bundle.js"></script>
<script src="/metronic/js/scripts.bundle.js"></script>
<script src="/metronic/js/fullcalendar.bundle.js"></script>
<script src="/metronic/js/wizard.js"></script>
<script src="/metronic/js/user.js"></script>
<script src="/js/jquery.mask.min.js"></script>
<script src="/js/mascaras.js"></script>
<script src="/metronic/js/select2.js"></script>
</body>
</html>
