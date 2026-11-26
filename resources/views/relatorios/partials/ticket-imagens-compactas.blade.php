@php
    $configNotaRelatorio = $configNota ?? \App\Models\ConfigNota::where('empresa_id', $ticket->empresa_id ?? null)->first();
    $limiteImagens = max(1, (int) ($limiteImagens ?? 4));
    $preferirEmbed = (bool) ($preferirEmbed ?? true);
    $permitirImagem = (bool) ($configNotaRelatorio->pesagem_imprimir_imagens_a4 ?? true);
    $imagemServiceRelatorio = app(\App\Services\Pesagem\PesagemTicketImagemService::class);
    $imagensTicket = collect();

    if ($permitirImagem && !empty($ticket->id)) {
        try {
            if ($ticket->relationLoaded('imagens')) {
                $imagensTicket = $ticket->imagens
                    ->filter(function ($imagem) {
                        return (bool) ($imagem->ativo ?? true) && empty($imagem->deleted_at);
                    })
                    ->sortBy('id')
                    ->take($limiteImagens)
                    ->values();
            } else {
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
            }
        } catch (\Throwable $e) {
            \Log::warning('Falha ao carregar imagens compactas do ticket para relatório.', [
                'ticket_id' => $ticket->id ?? null,
                'message' => $e->getMessage(),
            ]);
            $imagensTicket = collect();
        }

        if ($imagensTicket->isEmpty() && !empty($ticket->imagens_persistidas_json)) {
            $fallback = is_array($ticket->imagens_persistidas_json)
                ? $ticket->imagens_persistidas_json
                : (json_decode((string) $ticket->imagens_persistidas_json, true) ?: []);

            $imagensTicket = collect($fallback)
                ->filter(function ($item) {
                    return is_array($item) && (!empty($item['arquivo_path']) || !empty($item['arquivo_url']));
                })
                ->take($limiteImagens)
                ->map(function ($item) use ($ticket) {
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
                })
                ->values();
        }
    }
    $totalImagensTicket = $imagensTicket->count();
@endphp

@if($permitirImagem && $totalImagensTicket)
    <table class="rp-images-table">
        @php
            $singleImage = $totalImagensTicket === 1;
            $doubleImage = $totalImagensTicket === 2;
        @endphp
        @foreach($imagensTicket->chunk(2) as $linhaImagens)
            <tr>
                @foreach($linhaImagens as $imagemTicket)
                    @php
                        $srcImagemTicket = $imagemServiceRelatorio->imagemSrcParaRelatorio($imagemTicket, $preferirEmbed);
                    @endphp
                    <td class="rp-image-cell" @if($singleImage && $linhaImagens->count() === 1) colspan="2" @endif>
                        @if($srcImagemTicket)
                            <img src="{{ $srcImagemTicket }}" alt="Imagem ticket {{ $ticket->id }}" style="@if($singleImage) max-height: 62mm; object-fit: contain; @elseif($doubleImage) max-height: 38mm; object-fit: cover; @endif">
                            <div class="rp-image-caption">{{ $imagemTicket->camera_descricao ?: $imagemTicket->camera_uuid }}</div>
                        @endif
                    </td>
                @endforeach
                @if($linhaImagens->count() < 2)
                    <td class="rp-image-cell" @if($singleImage && $linhaImagens->count() === 1) colspan="2" @endif></td>
                @endif
            </tr>
        @endforeach
    </table>
@elseif($permitirImagem)
    <div class="rp-no-image">Sem evidência fotográfica vinculada.</div>
@endif
