@php
    $configNotaRelatorio = $configNota ?? \App\Models\ConfigNota::where('empresa_id', $ticket->empresa_id ?? null)->first();
    $tipoRelatorioImagem = $tipoRelatorioImagem ?? 'a4';
    $limiteImagens = (int) ($limiteImagens ?? ($tipoRelatorioImagem === '80mm' ? 2 : 6));
    $preferirEmbed = (bool) ($preferirEmbed ?? true);

    $flagA4 = $configNotaRelatorio->pesagem_imprimir_imagens_a4 ?? true;
    $flag80 = $configNotaRelatorio->pesagem_imprimir_imagens_80mm ?? true;
    $permitirImagem = $tipoRelatorioImagem === '80mm' ? ((bool) $flag80) : ((bool) $flagA4);

    $imagensTicket = collect();
    $imagemServiceRelatorio = app(\App\Services\Pesagem\PesagemTicketImagemService::class);

    if ($permitirImagem && !empty($ticket->id)) {
        try {
            $imagensTicket = \App\Models\PesagemTicketImagem::query()
                ->where('ticket_pesagem_id', $ticket->id)
                ->where('ativo', true)
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->limit($limiteImagens)
                ->get([
                    'id', 'empresa_id', 'pesagem_id', 'ticket_pesagem_id', 'camera_uuid',
                    'camera_descricao', 'arquivo_path', 'arquivo_url', 'mime_type',
                    'metadata_json', 'storage_disk', 'storage_base_path', 'ativo', 'deleted_at'
                ]);
        } catch (\Throwable $e) {
            \Log::warning('Falha ao carregar imagens do ticket para relatório.', [
                'ticket_id' => $ticket->id ?? null,
                'message' => $e->getMessage(),
            ]);
            $imagensTicket = collect();
        }

        if ($imagensTicket->isEmpty() && !empty($ticket->imagens_persistidas_json)) {
            $fallback = is_array($ticket->imagens_persistidas_json)
                ? $ticket->imagens_persistidas_json
                : (json_decode((string) $ticket->imagens_persistidas_json, true) ?: []);

            $imagensTicket = collect($fallback)->filter(function ($item) {
                return is_array($item) && (!empty($item['arquivo_path']) || !empty($item['arquivo_url']));
            })->take($limiteImagens)->map(function ($item) use ($ticket) {
                $model = new \App\Models\PesagemTicketImagem();
                $model->forceFill([
                    'id' => $item['id'] ?? null,
                    'empresa_id' => $ticket->empresa_id ?? null,
                    'pesagem_id' => $ticket->pesagem_id ?? null,
                    'ticket_pesagem_id' => $ticket->id ?? null,
                    'camera_uuid' => $item['camera_uuid'] ?? null,
                    'camera_descricao' => $item['camera_descricao'] ?? ($item['camera_uuid'] ?? 'Câmera'),
                    'arquivo_path' => $item['arquivo_path'] ?? null,
                    'arquivo_url' => $item['arquivo_url'] ?? null,
                    'mime_type' => $item['mime_type'] ?? 'image/jpeg',
                    'metadata_json' => $item['metadata_json'] ?? [],
                    'storage_disk' => $item['storage_disk'] ?? 'public_path',
                    'storage_base_path' => $item['storage_base_path'] ?? 'pesagem_ticket_imagens',
                    'ativo' => true,
                ]);
                return $model;
            })->values();
        }
    }
@endphp

@if($permitirImagem && $imagensTicket->count())
    <div class="ticket-imagens ticket-imagens-{{ $tipoRelatorioImagem }}">
        @foreach($imagensTicket as $imagemTicket)
            @php
                $srcImagemTicket = $imagemServiceRelatorio->imagemSrcParaRelatorio($imagemTicket, $preferirEmbed);
            @endphp
            @if($srcImagemTicket)
                <div class="ticket-imagem-item">
                    <img src="{{ $srcImagemTicket }}" alt="Imagem ticket {{ $ticket->id }}">
                    <div class="ticket-imagem-caption">{{ $imagemTicket->camera_descricao ?: $imagemTicket->camera_uuid }}</div>
                </div>
            @endif
        @endforeach
    </div>
@endif
