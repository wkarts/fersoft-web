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

    /*
    |--------------------------------------------------------------------------
    | Otimização conservadora das evidências fotográficas
    |--------------------------------------------------------------------------
    |
    | O Base64 continua sendo usado apenas no transporte ADP. Depois de
    | decodificado, snapshots JPEG maiores que 150 KB podem ser recomprimidos
    | de forma moderada. A imagem original é mantida se o resultado não ficar
    | menor. Nenhuma alteração no streaming/preview das câmeras é necessária.
    |
    */
    'snapshot_optimize' => true,
    'snapshot_optimize_min_bytes' => 150 * 1024,
    'snapshot_max_dimension' => 1920,
    'snapshot_jpeg_quality' => 88,
];
