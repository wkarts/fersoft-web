<!DOCTYPE html>
<html lang="br">
<head>
    <meta charset="utf-8" />
    <title>404 - Página Não Encontrada</title>
    <meta name="description" content="Updates and statistics">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!--begin::Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700|Roboto:300,400,500,600,700">

    <link href="/metronic/css/fullcalendar.bundle.css" rel="stylesheet" type="text/css" />
    <link href="/metronic/css/wizard.css" rel="stylesheet" type="text/css" />
    <link href="/css/style.css" rel="stylesheet" type="text/css" />
    <link href="/metronic/css/plugins.bundle.css" rel="stylesheet" type="text/css" />
    <link href="/metronic/css/prismjs.bundle.css" rel="stylesheet" type="text/css" />
    <link href="/metronic/css/style.bundle.css" rel="stylesheet" type="text/css" />

    <style type="text/css">
        body {
            background: #5C48CB;
        }
    </style>

    <!-- Styles adicionais para caixa memo -->
     <style>
         /* Bgi repeat */
         .flex-root .bgi-no-repeat{
             transform:translatex(0px) translatey(0px);
         }

         /* Button */
         .flex-root .bgi-no-repeat .btn-primary{
             position:relative;
             top:238px;
             transform: translatex(-6px) translatey(-181px);
         }

         /* Button */
         .flex-root .bgi-no-repeat .btn-primary{
             width:18% !important;
             transform:translatex(0px) translatey(-595px);
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
        <h1 class="font-weight-boldest text-dark-75 mt-15" style="font-size: 10rem">404</h1>
        <p class="font-size-h3 text-muted font-weight-normal">OOPS! Página não encontrada</p>
        <p class="font-size-h6 text-muted font-weight-normal">Verifique o endereço informado!</p>
        <button onclick="window.history.back();" class="btn btn-primary mt-4">Voltar para a Página Anterior</button>
    </div>
</div>

<script src="/metronic/js/plugins.bundle.js" type="text/javascript"></script>
<script src="/metronic/js/prismjs.bundle.js" type="text/javascript"></script>
<script src="/metronic/js/scripts.bundle.js" type="text/javascript"></script>
<script src="/metronic/js/fullcalendar.bundle.js" type="text/javascript"></script>
<script src="/metronic/js/wizard.js" type="text/javascript"></script>
<script src="/metronic/js/user.js" type="text/javascript"></script>
<script src="/js/jquery.mask.min.js"></script>
<script src="/js/mascaras.js"></script>
<script src="/metronic/js/select2.js" type="text/javascript"></script>
</body>
</html>
