<?php

namespace Tests\Unit;

use App\Models\SystemSetting;
use App\Services\SystemUpdateManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

class SystemUpdateManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolve_mode_falls_back_to_config(): void
    {
        config(['update_manager.default_mode' => 'patch_hotfix']);

        $manager = app(SystemUpdateManager::class);

        $this->assertSame('patch_hotfix', $manager->resolveMode());
    }

    public function test_resolve_mode_prefers_database_setting(): void
    {
        config(['update_manager.default_mode' => 'full_release']);
        SystemSetting::create(['key' => 'update_mode', 'value' => 'db_only']);

        $manager = app(SystemUpdateManager::class);

        $this->assertSame('db_only', $manager->resolveMode());
    }

    #[Group('ci-slow')]
    public function test_run_creates_patch_hotfix_dry_run_entry(): void
    {
        config(['update_manager.default_mode' => 'patch_hotfix']);

        $file = base_path('app/DummyPatch.php');
        file_put_contents($file, "<?php echo 'dummy';");

        $manager = app(SystemUpdateManager::class);

        $update = $manager->run('HEAD', ['app/DummyPatch.php'], [
            'dry_run' => true,
            'mysql_db_backup' => false,
            'composer_update' => false,
            'migrations_update' => false,
            'initiated_by' => 'test-suite',
        ]);

        $this->assertSame('success', $update->status);
        $this->assertTrue($update->dry_run);
        $this->assertCount(1, $update->files);
        $this->assertGreaterThan(0, $update->logs()->count());
    }
}
