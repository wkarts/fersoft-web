<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class ApplicationNameCentralizationTest extends TestCase
{
    /**
     * O nome exibido pelo sistema é definido exclusivamente por config('app.name').
     * Protege as views, scripts e códigos operacionais contra marcas fixas.
     */
    public function test_application_does_not_hardcode_the_application_name(): void
    {
        $root = dirname(__DIR__, 2);
        $directories = [
            'app',
            'config',
            'routes',
            'resources/views',
            'resources/js',
            'public/js',
        ];

        $matches = [];

        foreach ($directories as $directory) {
            $base = $root . '/' . $directory;
            if (!is_dir($base)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($base, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            /** @var SplFileInfo $entry */
            foreach ($iterator as $entry) {
                if (!$entry->isFile()) {
                    continue;
                }

                $filename = $entry->getFilename();
                if (!preg_match('/\.(?:php|blade\.php|js|ts|vue|html|json)$/i', $filename)) {
                    continue;
                }

                $source = file_get_contents($entry->getPathname());
                if ($source === false || stripos($source, 'FERSOFT WEB') === false) {
                    continue;
                }

                foreach (preg_split('/\r\n|\r|\n/', $source) as $index => $line) {
                    if (preg_match('/FERSOFT[ \t]+WEB/i', $line)) {
                        $relative = substr($entry->getPathname(), strlen($root) + 1);
                        $matches[] = str_replace('\\', '/', $relative) . ':' . ($index + 1);
                    }
                }
            }
        }

        $this->assertSame(
            [],
            $matches,
            'Nome de aplicação fixo detectado. Usar config(\'app.name\') em: '
                . implode(', ', array_slice($matches, 0, 120))
                . (count($matches) > 120 ? ' (e mais ' . (count($matches) - 120) . ')' : '')
        );
    }
}
