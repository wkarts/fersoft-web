<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\DatabaseHealth\DatabaseHealthOverview;

class SuperAdminDatabaseHealthController extends Controller
{
    public function index(DatabaseHealthOverview $overview)
    {
        $user = session('user_logged');
        abort_unless(is_array($user) && (int) ($user['super'] ?? 0) === 1, 403);

        $snapshot = $overview->snapshot();
        $snapshot['checked_at_label'] = $snapshot['checked_at']->format('d/m/Y H:i:s');
        $snapshot['totals']['data_label'] = $this->humanBytes($snapshot['totals']['data_bytes']);
        $snapshot['totals']['index_label'] = $this->humanBytes($snapshot['totals']['index_bytes']);
        $snapshot['totals']['allocated_label'] = $this->humanBytes($snapshot['totals']['allocated_bytes']);

        foreach ($snapshot['tables'] as &$table) {
            $table['data_label'] = $this->humanBytes($table['data_bytes']);
            $table['index_label'] = $this->humanBytes($table['index_bytes']);
            $table['allocated_label'] = $this->humanBytes($table['allocated_bytes']);
        }
        unset($table);

        return view('super_admin.database_health', [
            'snapshot' => $snapshot,
            'title' => 'Saúde do banco',
        ]);
    }

    private function humanBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = (float) max(0, $bytes);
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return number_format($size, $unit === 0 ? 0 : 1, ',', '.') . ' ' . $units[$unit];
    }
}
