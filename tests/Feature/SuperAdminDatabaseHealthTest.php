<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\DatabaseHealth\DatabaseHealthOverview;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SuperAdminDatabaseHealthTest extends TestCase
{
    public function test_guest_cannot_access_database_health_dashboard(): void
    {
        $this->get('/super-admin/database-health')->assertForbidden();
    }

    public function test_regular_user_cannot_access_database_health_dashboard(): void
    {
        $this->withSession([
            'user_logged' => ['id' => 1, 'super' => 0],
        ])->get('/super-admin/database-health')->assertForbidden();
    }

    public function test_mysql_overview_reads_only_table_metadata(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('A verificação de information_schema exige MySQL/MariaDB.');
        }

        $snapshot = app(DatabaseHealthOverview::class)->snapshot();

        $this->assertTrue($snapshot['available']);
        $this->assertSame('mysql', $snapshot['driver']);
        $this->assertGreaterThanOrEqual(0, $snapshot['totals']['table_count']);
        $this->assertIsArray($snapshot['tables']);
        $this->assertLessThanOrEqual(25, count($snapshot['tables']));
    }
}
