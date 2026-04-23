<?php

return [
    'default_schemes' => env('FISCAL_DEFAULT_SCHEMES', 'PL_009_V4'),
    'default_schemes_cte' => env('FISCAL_DEFAULT_SCHEMES_CTE', 'PL_CTe_400'),
    'default_schemes_mdfe' => env('FISCAL_DEFAULT_SCHEMES_MDFE', 'PL_MDFe_300a'),

    'ver_proc_prefix' => env('FISCAL_VERPROC_PREFIX', env('APP_NAME', '')),
    'ver_proc_version_token' => env('FISCAL_VERPROC_VERSION_TOKEN', 'v'),
];
