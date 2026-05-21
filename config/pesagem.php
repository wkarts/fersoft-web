<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Armazenamento dos snapshots da pesagem
    |--------------------------------------------------------------------------
    |
    | public_path: grava diretamente em public/pesagem_ticket_imagens/...
    | public: grava em storage/app/public e expõe por /storage
    | s3/minio: grava no disco configurado em config/filesystems.php
    |
    */
    'snapshot_disk' => env('PESAGEM_SNAPSHOT_DISK', 'public_path'),
    'snapshot_base_path' => env('PESAGEM_SNAPSHOT_BASE_PATH', 'pesagem_ticket_imagens'),
    'snapshot_visibility' => env('PESAGEM_SNAPSHOT_VISIBILITY', 'public'),
];
