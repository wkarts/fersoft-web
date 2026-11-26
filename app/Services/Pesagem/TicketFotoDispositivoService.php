<?php

namespace App\Services\Pesagem;

use App\Models\PesagemTicketImagem;
use App\Models\TicketPesagem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TicketFotoDispositivoService
{
    /** O ticket e as referências são confirmados juntos. Na falha, remove apenas os arquivos desta tentativa. */
    public function salvar(TicketPesagem $ticket, array $dados, array $fotos, int $usuarioId, bool $bloquearConcluido): ?TicketPesagem
    {
        $disco = Storage::disk(PesagemTicketImagem::DISCO_DISPOSITIVO);
        $arquivos = [];
        try {
            return DB::transaction(function () use ($ticket, $dados, $fotos, $usuarioId, $bloquearConcluido, $disco, &$arquivos) {
                if ($ticket->exists) {
                    $ticket = TicketPesagem::where('empresa_id', $dados['empresa_id'])
                        ->where('pesagem_id', $dados['pesagem_id'])
                        ->where('filial_id', $dados['filial_id'])
                        ->lockForUpdate()->findOrFail($ticket->id);
                    $chavesExistentes = $ticket->fotosDispositivo()
                        ->whereIn('metadata_json->chave', array_column($fotos, 'chave'))->count();
                    if ($chavesExistentes === count($fotos)) {
                        // A resposta da edição pode ter se perdido. Evita repetir seus efeitos sobre o estoque.
                        return null;
                    }
                    if ($bloquearConcluido && $ticket->status === 'concluído') {
                        throw new \DomainException('Este ticket já está concluído e não pode ser editado.');
                    }
                }

                $ticket->fill($dados)->save();
                foreach ($fotos as $foto) {
                    $chave = $foto['chave'];
                    if ($ticket->fotosDispositivo()->where('metadata_json->chave', $chave)->exists()) {
                        continue;
                    }

                    $arquivo = $foto['arquivo'];
                    $nome = Str::uuid() . '.jpg';
                    $diretorio = $ticket->empresa_id . '/' . $ticket->id;
                    $arquivos[] = $diretorio . '/' . $nome;
                    $path = $disco->putFileAs($diretorio, $arquivo, $nome);
                    if (!$path) {
                        throw new \RuntimeException('Falha ao gravar foto do dispositivo.');
                    }
                    [$largura, $altura] = getimagesize($arquivo->getRealPath());
                    PesagemTicketImagem::create([
                        'empresa_id' => $ticket->empresa_id,
                        'pesagem_id' => $ticket->pesagem_id,
                        'ticket_pesagem_id' => $ticket->id,
                        'storage_disk' => PesagemTicketImagem::DISCO_DISPOSITIVO,
                        'arquivo_path' => $path,
                        'mime_type' => 'image/jpeg',
                        'tamanho_bytes' => $arquivo->getSize(),
                        'largura' => $largura,
                        'altura' => $altura,
                        'camera_descricao' => 'Câmera do dispositivo',
                        'capturado_em' => $foto['capturado_em'],
                        'metadata_json' => [
                            'origem' => 'dispositivo',
                            'usuario_id' => $usuarioId,
                            'legenda' => $foto['legenda'] ?? null,
                            'chave' => $chave,
                        ],
                        'ativo' => true,
                    ]);
                }
                return $ticket;
            });
        } catch (\Throwable $e) {
            if ($arquivos) {
                $disco->delete($arquivos);
            }
            throw $e;
        }
    }
}
