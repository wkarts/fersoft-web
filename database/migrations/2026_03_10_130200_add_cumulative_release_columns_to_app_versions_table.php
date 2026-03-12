<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('app_versions')) {
            return;
        }

        Schema::table('app_versions', function (Blueprint $table) {
            if (!Schema::hasColumn('app_versions', 'release_notes_current_html')) {
                $table->longText('release_notes_current_html')->nullable()->after('title');
            }

            if (!Schema::hasColumn('app_versions', 'release_notes_cumulative_html')) {
                $table->longText('release_notes_cumulative_html')->nullable()->after('release_notes_current_html');
            }

            if (!Schema::hasColumn('app_versions', 'release_notes_current_pdf_path')) {
                $table->string('release_notes_current_pdf_path')->nullable()->after('release_notes_cumulative_html');
            }

            if (!Schema::hasColumn('app_versions', 'release_notes_cumulative_pdf_path')) {
                $table->string('release_notes_cumulative_pdf_path')->nullable()->after('release_notes_current_pdf_path');
            }

            if (!Schema::hasColumn('app_versions', 'release_notes_current_html_path')) {
                $table->string('release_notes_current_html_path')->nullable()->after('release_notes_cumulative_pdf_path');
            }

            if (!Schema::hasColumn('app_versions', 'release_notes_cumulative_html_path')) {
                $table->string('release_notes_cumulative_html_path')->nullable()->after('release_notes_current_html_path');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('app_versions')) {
            return;
        }

        Schema::table('app_versions', function (Blueprint $table) {
            $columns = [
                'release_notes_current_html',
                'release_notes_cumulative_html',
                'release_notes_current_pdf_path',
                'release_notes_cumulative_pdf_path',
                'release_notes_current_html_path',
                'release_notes_cumulative_html_path',
            ];

            $drop = [];
            foreach ($columns as $column) {
                if (Schema::hasColumn('app_versions', $column)) {
                    $drop[] = $column;
                }
            }

            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
