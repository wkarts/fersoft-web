<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class AppVersion extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'version',
        'version_major',
        'version_minor',
        'version_patch',
        'title',
        'release_notes_html',
        'release_notes_pdf_path',
        'release_notes_current_html',
        'release_notes_cumulative_html',
        'release_notes_current_pdf_path',
        'release_notes_cumulative_pdf_path',
        'release_notes_current_html_path',
        'release_notes_cumulative_html_path',
        'release_notes_format',
        'is_current',
        'released_at',
        'installed_at',
        'build_number',
        'commit_hash',
        'release_channel',
        'author',
        'observations',
        'metadata',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'released_at' => 'datetime',
        'installed_at' => 'datetime',
        'metadata' => 'array',
    ];
}
