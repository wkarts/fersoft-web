<?php

namespace App\Services\Pesagem;

use App\Models\ConfigNota;
use App\Models\TicketPesagem;
use App\Utils\WhatsAppUtil;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PesagemTicketNotificacaoService
{
    public function notificarConclusao(TicketPesagem $ticket, ConfigNota $config): void
    {
        $ticket->loadMissing(['pesagem', 'produto', 'veiculo', 'imagens']);

        if ((bool) ($config->pesagem_enviar_email_ao_concluir ?? false)) {
            $this->enviarEmails($ticket, $config);
        }

        if ((bool) ($config->pesagem_enviar_whatsapp_ao_concluir ?? false)) {
            $this->enviarWhatsApps($ticket, $config);
        }
    }

    private function montarMensagem(TicketPesagem $ticket, bool $incluirLinksImagens = true): string
    {
        $ticket->loadMissing(['pesagem', 'produto', 'veiculo', 'imagens']);
        $peso = number_format((float) $ticket->peso, 2, ',', '.');
        $tara = number_format((float) ($ticket->peso_bag ?? 0), 2, ',', '.');
        $liquido = number_format(max(0, (float) $ticket->peso - (float) ($ticket->peso_bag ?? 0)), 2, ',', '.');
        $imagens = $ticket->imagens()->where('ativo', true)->orderBy('id')->limit(12)->get();
        $qtdImagens = $imagens->count();

        $pesagem = $ticket->pesagem;
        $pessoa = $pesagem->cliente->razao_social
            ?? $pesagem->cliente->nome
            ?? $pesagem->fornecedor->razao_social
            ?? $pesagem->fornecedor->nome
            ?? '-';

        $mensagem = "Coleta de pesagem concluída\n" .
            "Ticket: #{$ticket->id}\n" .
            "Pesagem: #{$ticket->pesagem_id}\n" .
            "Cliente/Fornecedor: {$pessoa}\n" .
            "Produto: " . ($ticket->produto->nome ?? '-') . "\n" .
            "Veículo: " . ($ticket->veiculo->placa ?? '-') . "\n" .
            "Tipo: " . ucfirst((string) $ticket->tipo) . "\n" .
            "Peso bruto: {$peso} kg\n" .
            "Tara: {$tara} kg\n" .
            "Peso líquido: {$liquido} kg\n" .
            "Imagens: {$qtdImagens}\n" .
            "Status: " . ucfirst((string) $ticket->status) . "\n" .
            "Data: " . optional($ticket->updated_at)->format('d/m/Y H:i:s');

        if ($incluirLinksImagens) {
            $links = $imagens->map(fn ($imagem) => $imagem->imagem_url ?: $imagem->arquivo_url)->filter()->values();
            if ($links->count()) {
                $mensagem .= "\n\nImagens:\n" . $links->implode("\n");
            }
        }

        return $mensagem;
    }

    private function enviarEmails(TicketPesagem $ticket, ConfigNota $config): void
    {
        $emails = method_exists($config, 'pesagemEmailsDestino')
            ? $config->pesagemEmailsDestino()
            : $this->normalizarEmails($config->pesagem_email_destino ?? '');

        if (empty($emails)) {
            Log::info('Pesagem sem e-mails de notificação configurados.', [
                'ticket_id' => $ticket->id,
                'empresa_id' => $ticket->empresa_id,
            ]);
            return;
        }

        $mensagem = $this->montarMensagem($ticket);
        $imagens = $ticket->imagens()->where('ativo', true)->orderBy('id')->limit(12)->get();
        $to = array_shift($emails);
        $cc = $emails;

        try {
            Mail::raw($mensagem, function ($m) use ($to, $cc, $ticket, $imagens, $config) {
                $m->to($to);
                if (!empty($cc)) {
                    $m->cc($cc);
                }
                $m->subject('Ticket de pesagem concluído #' . $ticket->id);

                if ((bool) ($config->pesagem_enviar_imagens_notificacao ?? true)) {
                    foreach ($imagens as $imagem) {
                        $path = $this->arquivoLocalParaAnexo($imagem);
                        if ($path && is_file($path)) {
                            $m->attach($path);
                        }
                    }
                }
            });

            Log::info('E-mail de coleta de pesagem enviado.', [
                'ticket_id' => $ticket->id,
                'empresa_id' => $ticket->empresa_id,
                'to' => $to,
                'cc' => $cc,
                'imagens' => $imagens->count(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Falha ao enviar e-mail de coleta de pesagem.', [
                'ticket_id' => $ticket->id,
                'empresa_id' => $ticket->empresa_id,
                'to' => $to,
                'cc' => $cc,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function enviarWhatsApps(TicketPesagem $ticket, ConfigNota $config): void
    {
        $numeros = method_exists($config, 'pesagemWhatsappsDestino')
            ? $config->pesagemWhatsappsDestino()
            : $this->normalizarWhatsapps($config->pesagem_whatsapp_destino ?? '');

        if (empty($numeros)) {
            Log::info('Pesagem sem WhatsApps de notificação configurados.', [
                'ticket_id' => $ticket->id,
                'empresa_id' => $ticket->empresa_id,
            ]);
            return;
        }

        $util = app(WhatsAppUtil::class);
        $mensagem = $this->montarMensagem($ticket, false);
        $imagens = $ticket->imagens()->where('ativo', true)->orderBy('id')->limit(12)->get();

        foreach ($numeros as $numero) {
            try {
                $retornoTexto = $util->sendMessage($numero, $mensagem, (int) $ticket->empresa_id);
                $retornosMidia = [];

                if ((bool) ($config->pesagem_enviar_imagens_notificacao ?? true)) {
                    foreach ($imagens as $imagem) {
                        $arquivoLocal = $this->arquivoLocalParaAnexo($imagem);
                        if ($arquivoLocal && is_file($arquivoLocal)) {
                            $retornosMidia[] = $util->sendMessage($numero, '', (int) $ticket->empresa_id, $arquivoLocal);
                            continue;
                        }

                        $url = $imagem->imagem_url ?: $imagem->arquivo_url;
                        if ($url) {
                            // Envia a URL como mídia sem expor o caminho no texto da mensagem.
                            $retornosMidia[] = $util->sendMessage($numero, '', (int) $ticket->empresa_id, $url);
                        }
                    }
                }

                Log::info('WhatsApp de coleta de pesagem enviado.', [
                    'ticket_id' => $ticket->id,
                    'empresa_id' => $ticket->empresa_id,
                    'numero' => $numero,
                    'retorno_texto' => $retornoTexto,
                    'retornos_midia' => $retornosMidia,
                ]);
            } catch (\Throwable $e) {
                Log::error('Falha ao enviar WhatsApp de coleta de pesagem.', [
                    'ticket_id' => $ticket->id,
                    'empresa_id' => $ticket->empresa_id,
                    'numero' => $numero,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    private function arquivoLocalParaAnexo($imagem): ?string
    {
        if (!$imagem->arquivo_path) {
            return null;
        }

        $disk = $imagem->storage_disk ?: config('pesagem.snapshot_disk', 'public_path');

        if ($disk === 'public_path') {
            return public_path(ltrim($imagem->arquivo_path, '/'));
        }

        if ($disk === 'public') {
            return storage_path('app/public/' . ltrim($imagem->arquivo_path, '/'));
        }

        return null;
    }

    private function normalizarEmails(string $value): array
    {
        return array_values(array_unique(array_filter(array_map('trim', preg_split('/[\r\n,;]+/', $value)), fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))));
    }

    private function normalizarWhatsapps(string $value): array
    {
        $numeros = [];
        foreach (preg_split('/[\r\n,;]+/', $value) as $numero) {
            $numero = preg_replace('/[^0-9]/', '', (string) $numero);
            if ($numero === '') {
                continue;
            }
            if (!str_starts_with($numero, '55')) {
                $numero = '55' . $numero;
            }
            $numeros[] = $numero;
        }

        return array_values(array_unique($numeros));
    }
}
