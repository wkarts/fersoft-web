<?php

namespace App\Http\Controllers;

use App\Services\ProdutoIbsCbsUpdater;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Atualização em massa de IBS/CBS/IS a partir da TIPI (NCM)
 *
 * Endpoint (exemplo):
 *   POST /produtos/ibs-cbs/atualizar
 */
class ProdutoIbsCbsController extends BaseController
{
    // BaseController exige redirectPage
    protected $redirectPage = '/produtos';

    // Apenas para manter coerência com BaseController
    protected $formTitle = 'Produtos';

    protected function rules(): array
    {
        return [];
    }

    protected function messages(): array
    {
        return [];
    }

    public function atualizar(Request $request, ProdutoIbsCbsUpdater $updater)
    {
        $payload = $request->validate([
            'only_missing' => ['sometimes'],
            'chunk'        => ['nullable', 'integer', 'min:50', 'max:5000'],
            'empresa_id'   => ['nullable', 'integer'],
        ]);

        $onlyMissing = array_key_exists('only_missing', $payload)
            ? filter_var($payload['only_missing'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE)
            : true;

        if ($onlyMissing === null) {
            $onlyMissing = true;
        }

        // Preferência: BaseController → request → Auth/session
        $empresaId = $this->empresa_id
            ?? ($payload['empresa_id'] ?? null)
            ?? session('user_logged.empresa_id')
            ?? (Auth::check() ? (Auth::user()->empresa_id ?? null) : null)
            ?? session('empresa_id');

        if (!$empresaId) {
            return response()->json([
                'ok'      => false,
                'message' => 'empresa_id não informado (nem no BaseController, nem na request, nem na sessão).',
            ], 422);
        }

        $chunk = (int)($payload['chunk'] ?? 500);

        // ================================
        // Relatório CLIENTE (user-friendly)
        // ================================
        $runAt   = Carbon::now();
        $runId   = $runAt->format('Ymd_His') . '_' . Str::lower(Str::random(8));
        $user    = Auth::user();
        $userId  = $user?->id;
        $userNome= $user?->name ?? $user?->nome ?? 'Usuário';

        $publicDir = public_path('relatorios_rt/ibs-cbs/prd/' . (int)$empresaId);
        if (!File::exists($publicDir)) {
            File::makeDirectory($publicDir, 0755, true);
        }

        $reportName        = "relatorio_ibs_cbs_{$runId}.html";
        $csvUpdatedName    = "produtos_atualizados_{$runId}.csv";
        $csvNoTipiName     = "produtos_sem_tipi_{$runId}.csv";

        $reportPath     = $publicDir . DIRECTORY_SEPARATOR . $reportName;
        $csvUpdatedPath = $publicDir . DIRECTORY_SEPARATOR . $csvUpdatedName;
        $csvNoTipiPath  = $publicDir . DIRECTORY_SEPARATOR . $csvNoTipiName;

        // temporários (para escrever linhas HTML sem estourar memória)
        $tmpUpdatedRowsPath = $publicDir . DIRECTORY_SEPARATOR . "__rows_updated_{$runId}.tmp";
        $tmpNoTipiRowsPath  = $publicDir . DIRECTORY_SEPARATOR . "__rows_no_tipi_{$runId}.tmp";

        $fhRowsUpdated = null;
        $fhRowsNoTipi  = null;
        $fhCsvUpdated  = null;
        $fhCsvNoTipi   = null;

        // limites pra não travar o browser com HTML gigante
        $maxHtmlRowsUpdated = 600; // mostra no HTML
        $maxHtmlRowsNoTipi  = 200; // mostra no HTML
        $countHtmlUpdated   = 0;
        $countHtmlNoTipi    = 0;

        try {
            $fhRowsUpdated = fopen($tmpUpdatedRowsPath, 'wb');
            $fhRowsNoTipi  = fopen($tmpNoTipiRowsPath, 'wb');

            $fhCsvUpdated = fopen($csvUpdatedPath, 'wb');
            $fhCsvNoTipi  = fopen($csvNoTipiPath, 'wb');

            // BOM UTF-8 para Excel
            fwrite($fhCsvUpdated, "\xEF\xBB\xBF");
            fwrite($fhCsvNoTipi, "\xEF\xBB\xBF");

            // Cabeçalhos CSV
            fputcsv($fhCsvUpdated, [
                'CODIGO/ID', 'REFERENCIA', 'DESCRICAO', 'NCM',
                'MUDANCAS',
                'CST_IBS_CBS', 'CLASS_TRIB_IBS_CBS', 'REDUCAO_IBS', 'REDUCAO_CBS',
                'FLAG_IS', 'CST_IS', 'ALIQ_IS'
            ], ';');

            fputcsv($fhCsvNoTipi, [
                'CODIGO/ID', 'REFERENCIA', 'DESCRICAO', 'NCM', 'MOTIVO'
            ], ';');

            $esc = function ($v) {
                return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            };

            $onUpdated = function (array $info) use (
                &$countHtmlUpdated, $maxHtmlRowsUpdated, $fhRowsUpdated, $fhCsvUpdated, $esc, $runAt
            ) {
                $codigo = trim((string)($info['codigo'] ?? ''));
                $codigo = $codigo !== '' ? $codigo : (string)($info['id'] ?? '');
                $ref    = (string)($info['referencia'] ?? '');
                $desc   = (string)($info['descricao'] ?? '');
                $ncm    = (string)($info['ncm'] ?? '');

                $changes = $info['changes'] ?? [];
                $mudancas = is_array($changes) ? implode(', ', $changes) : (string)$changes;

                $after = $info['after'] ?? [];
                $cst   = $after['cst_ibs_cbs'] ?? '';
                $cls   = $after['class_trib_ibs_cbs'] ?? '';
                $rIbs  = $after['reducao_ibs'] ?? '';
                $rCbs  = $after['reducao_cbs'] ?? '';
                $flag  = $after['flag_is'] ?? '';
                $cstIs = $after['cst_is'] ?? '';
                $aliq  = $after['aliq_is'] ?? '';

                // CSV completo
                fputcsv($fhCsvUpdated, [
                    $codigo, $ref, $desc, $ncm,
                    $mudancas,
                    $cst, $cls, $rIbs, $rCbs,
                    $flag, $cstIs, $aliq
                ], ';');

                // HTML (limitado)
                if ($countHtmlUpdated < $maxHtmlRowsUpdated) {
                    $countHtmlUpdated++;
                    $row = '<tr>'
                        . '<td>' . $esc($codigo) . '</td>'
                        . '<td>' . $esc($ref) . '</td>'
                        . '<td style="text-align:left">' . $esc($desc) . '</td>'
                        . '<td>' . $esc($ncm) . '</td>'
                        . '<td style="text-align:left">' . $esc($mudancas) . '</td>'
                        . '<td>' . $esc($cst) . '</td>'
                        . '<td>' . $esc($cls) . '</td>'
                        . '<td>' . $esc($rIbs) . '</td>'
                        . '<td>' . $esc($rCbs) . '</td>'
                        . '</tr>' . PHP_EOL;
                    fwrite($fhRowsUpdated, $row);
                }
            };

            $onNoTipi = function (array $info) use (
                &$countHtmlNoTipi, $maxHtmlRowsNoTipi, $fhRowsNoTipi, $fhCsvNoTipi, $esc
            ) {
                $codigo = trim((string)($info['codigo'] ?? ''));
                $codigo = $codigo !== '' ? $codigo : (string)($info['id'] ?? '');
                $ref    = (string)($info['referencia'] ?? '');
                $desc   = (string)($info['descricao'] ?? '');
                $ncm    = (string)($info['ncm'] ?? '');
                $motivo = (string)($info['reason'] ?? 'Sem correspondência na TIPI (NCM)');

                // CSV completo
                fputcsv($fhCsvNoTipi, [$codigo, $ref, $desc, $ncm, $motivo], ';');

                // HTML (limitado)
                if ($countHtmlNoTipi < $maxHtmlRowsNoTipi) {
                    $countHtmlNoTipi++;
                    $row = '<tr>'
                        . '<td>' . $esc($codigo) . '</td>'
                        . '<td>' . $esc($ref) . '</td>'
                        . '<td style="text-align:left">' . $esc($desc) . '</td>'
                        . '<td>' . $esc($ncm) . '</td>'
                        . '<td style="text-align:left">' . $esc($motivo) . '</td>'
                        . '</tr>' . PHP_EOL;
                    fwrite($fhRowsNoTipi, $row);
                }
            };

            // Executa atualização (service) com callbacks (sem mudar rota / sem quebrar fluxo)
            $result = $updater->exec((int)$empresaId, (bool)$onlyMissing, $chunk, [
                'on_updated' => $onUpdated,
                'on_no_tipi' => $onNoTipi,
            ]);

            // Fecha temporários
            if (is_resource($fhRowsUpdated)) fclose($fhRowsUpdated);
            if (is_resource($fhRowsNoTipi)) fclose($fhRowsNoTipi);
            if (is_resource($fhCsvUpdated)) fclose($fhCsvUpdated);
            if (is_resource($fhCsvNoTipi)) fclose($fhCsvNoTipi);

            $fhRowsUpdated = $fhRowsNoTipi = $fhCsvUpdated = $fhCsvNoTipi = null;

            // Monta HTML final (cliente)
            $rowsUpdatedHtml = File::exists($tmpUpdatedRowsPath) ? File::get($tmpUpdatedRowsPath) : '';
            $rowsNoTipiHtml  = File::exists($tmpNoTipiRowsPath) ? File::get($tmpNoTipiRowsPath) : '';

            // URLs públicas
            $baseUrl = url('relatorios_rt/ibs-cbs/prd/' . (int)$empresaId);
            $reportUrl = $baseUrl . '/' . $reportName;
            $csvUpdatedUrl = $baseUrl . '/' . $csvUpdatedName;
            $csvNoTipiUrl  = $baseUrl . '/' . $csvNoTipiName;

            $totalTargets = $result['total_targets'] ?? 0;
            $updated      = $result['updated'] ?? 0;
            $unchanged    = $result['unchanged'] ?? 0;
            $noTipi       = $result['no_tipi'] ?? 0;
            $durationMs   = $result['duration_ms'] ?? 0;

            $html = '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"/>'
                . '<meta name="viewport" content="width=device-width,initial-scale=1"/>'
                . '<title>Relatório de Atualização IBS/CBS/IS</title>'
                . '<style>
                    body{font-family:Arial,Helvetica,sans-serif;margin:24px;background:#f7f7f8;color:#111}
                    .box{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:18px}
                    h1{font-size:20px;margin:0 0 8px 0}
                    .muted{color:#6b7280;font-size:12px}
                    .kpi{display:flex;gap:10px;flex-wrap:wrap;margin-top:10px}
                    .kpi .card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:12px;min-width:160px}
                    .kpi .v{font-size:20px;font-weight:700}
                    .kpi .l{font-size:12px;color:#6b7280}
                    .btn{display:inline-block;padding:8px 12px;border-radius:8px;border:1px solid #e5e7eb;background:#fff;color:#111;text-decoration:none;margin-right:8px;font-size:13px}
                    .btn:hover{background:#f3f4f6}
                    hr{border:none;border-top:1px solid #e5e7eb;margin:16px 0}
                    table{width:100%;border-collapse:collapse;margin-top:10px}
                    th,td{border:1px solid #e5e7eb;padding:8px;font-size:12px;text-align:center}
                    th{background:#f9fafb}
                    .left{text-align:left}
                    .note{font-size:12px;color:#374151;background:#f9fafb;border:1px dashed #e5e7eb;border-radius:10px;padding:10px}
                </style></head><body>';

            $html .= '<div class="box">'
                . '<h1>Relatório de Atualização IBS/CBS/IS (TIPI)</h1>'
                . '<div class="muted">Empresa: <b>' . (int)$empresaId . '</b> &nbsp;|&nbsp; Executado por: <b>' . $esc($userNome) . '</b> &nbsp;|&nbsp; Data/Hora: <b>' . $esc($runAt->format('d/m/Y H:i:s')) . '</b></div>'
                . '<div class="muted">Modo: <b>' . ($onlyMissing ? 'Atualizar somente faltantes (recomendado)' : 'Forçar atualização (sobrescreve)') . '</b></div>';

            $html .= '<div class="kpi">'
                . '<div class="card"><div class="v">' . (int)$totalTargets . '</div><div class="l">Produtos com NCM encontrado na TIPI</div></div>'
                . '<div class="card"><div class="v">' . (int)$updated . '</div><div class="l">Produtos atualizados</div></div>'
                . '<div class="card"><div class="v">' . (int)$unchanged . '</div><div class="l">Sem alteração (já estavam OK)</div></div>'
                . '<div class="card"><div class="v">' . (int)$noTipi . '</div><div class="l">Não atualizados (sem TIPI/NCM)</div></div>'
                . '<div class="card"><div class="v">' . (int)$durationMs . ' ms</div><div class="l">Tempo total</div></div>'
                . '</div>';

            $html .= '<hr>'
                . '<a class="btn" href="' . $esc($csvUpdatedUrl) . '" target="_blank" rel="noopener">Baixar CSV — Produtos Atualizados</a>'
                . '<a class="btn" href="' . $esc($csvNoTipiUrl) . '" target="_blank" rel="noopener">Baixar CSV — Produtos Sem TIPI</a>'
                . '<div class="note" style="margin-top:10px">'
                . 'Este relatório lista os <b>produtos atualizados</b> (com código, referência, descrição e NCM). '
                . 'Se a lista for muito grande, utilize os <b>CSVs</b> acima para ver tudo no Excel.'
                . '</div>';

            // Tabela Atualizados (amostra no HTML)
            $html .= '<hr><h2 style="font-size:16px;margin:0">Produtos Atualizados</h2>'
                . '<div class="muted">Mostrando até <b>' . (int)$maxHtmlRowsUpdated . '</b> no HTML. A lista completa está no CSV.</div>';

            $html .= '<table><thead><tr>'
                . '<th>Código/ID</th>'
                . '<th>Referência</th>'
                . '<th class="left">Descrição</th>'
                . '<th>NCM</th>'
                . '<th class="left">O que mudou</th>'
                . '<th>CST</th>'
                . '<th>Class. Trib</th>'
                . '<th>Red. IBS</th>'
                . '<th>Red. CBS</th>'
                . '</tr></thead><tbody>'
                . ($rowsUpdatedHtml !== '' ? $rowsUpdatedHtml : '<tr><td colspan="9">Nenhum produto foi atualizado.</td></tr>')
                . '</tbody></table>';

            // Tabela Sem TIPI (amostra no HTML)
            $html .= '<hr><h2 style="font-size:16px;margin:0">Não Atualizados (Sem TIPI/NCM)</h2>'
                . '<div class="muted">Mostrando até <b>' . (int)$maxHtmlRowsNoTipi . '</b> no HTML. A lista completa está no CSV.</div>';

            $html .= '<table><thead><tr>'
                . '<th>Código/ID</th>'
                . '<th>Referência</th>'
                . '<th class="left">Descrição</th>'
                . '<th>NCM</th>'
                . '<th class="left">Motivo</th>'
                . '</tr></thead><tbody>'
                . ($rowsNoTipiHtml !== '' ? $rowsNoTipiHtml : '<tr><td colspan="5">Nenhum produto ficou pendente por falta de TIPI.</td></tr>')
                . '</tbody></table>';

            $html .= '</div></body></html>';

            File::put($reportPath, $html);

            // limpa temporários
            if (File::exists($tmpUpdatedRowsPath)) File::delete($tmpUpdatedRowsPath);
            if (File::exists($tmpNoTipiRowsPath)) File::delete($tmpNoTipiRowsPath);

            return response()->json([
                'ok' => true,
                'result' => $result,

                // links do CLIENTE
                'report_url' => $reportUrl,
                'csv_updated_url' => $csvUpdatedUrl,
                'csv_no_tipi_url' => $csvNoTipiUrl,
            ]);

        } catch (\Throwable $e) {
            // fecha handles
            if (is_resource($fhRowsUpdated)) fclose($fhRowsUpdated);
            if (is_resource($fhRowsNoTipi)) fclose($fhRowsNoTipi);
            if (is_resource($fhCsvUpdated)) fclose($fhCsvUpdated);
            if (is_resource($fhCsvNoTipi)) fclose($fhCsvNoTipi);

            // limpa temporários
            if (File::exists($tmpUpdatedRowsPath)) File::delete($tmpUpdatedRowsPath);
            if (File::exists($tmpNoTipiRowsPath)) File::delete($tmpNoTipiRowsPath);

            // não apaga CSV/HTML se já foram gerados (pode ajudar debug),
            // mas pode apagar se quiser.

            throw $e;
        }
    }
}
