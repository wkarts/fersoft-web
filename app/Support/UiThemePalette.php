<?php

namespace App\Support;

/** Pure palette functions; no SQL or global state, safe for cached config and tests. */
final class UiThemePalette
{
    public static function hex(mixed $value, string $fallback): string
    {
        return is_string($value) && preg_match('/^#[a-fA-F0-9]{6}$/D', $value)
            ? strtoupper($value) : strtoupper($fallback);
    }

    public static function luminance(string $hex): float
    {
        $values = [];
        for ($i = 1; $i < 7; $i += 2) {
            $v = hexdec(substr($hex, $i, 2)) / 255;
            $values[] = $v <= 0.04045 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
        }
        return .2126 * $values[0] + .7152 * $values[1] + .0722 * $values[2];
    }

    public static function contrast(string $a, string $b): float
    {
        $a = self::luminance($a); $b = self::luminance($b);
        return (max($a, $b) + .05) / (min($a, $b) + .05);
    }

    public static function foreground(string $background): string
    {
        return self::contrast($background, '#FFFFFF') >= self::contrast($background, '#000000')
            ? '#FFFFFF' : '#000000';
    }

    public static function mix(string $base, string $overlay, float $fraction): string
    {
        $fraction = min(1, max(0, $fraction));
        $hex = '#';
        for ($i = 1; $i < 7; $i += 2) {
            $value = round(hexdec(substr($base, $i, 2)) * (1 - $fraction)
                + hexdec(substr($overlay, $i, 2)) * $fraction);
            $hex .= str_pad(strtoupper(dechex((int) $value)), 2, '0', STR_PAD_LEFT);
        }
        return $hex;
    }

    public static function tokens(array $theme): array
    {
        $dark = (bool) ($theme['dark'] ?? false);
        $surface = self::hex($theme['surface'] ?? null, $dark ? '#1A2B3A' : '#FFFFFF');
        $ink = self::hex($theme['text'] ?? null, $dark ? '#EDF4FC' : '#1C2A40');
        if (self::contrast($surface, $ink) < 4.5) $ink = self::foreground($surface);
        $accent = self::hex($theme['accent'] ?? null, '#168F81');
        $aside = self::hex($theme['sidebar'] ?? null, '#FFFFFF');
        $asideInk = self::hex($theme['sidebar_text'] ?? null, '#24334E');
        if (self::contrast($aside, $asideInk) < 4.5) $asideInk = self::foreground($aside);
        $navActive = self::mix($aside, $accent, .16);
        $navInk = self::contrast($navActive, $accent) >= 4.5 ? $accent : $asideInk;
        return [
            '--ui-accent' => $accent,
            '--ui-accent-hover' => self::hex($theme['accent_hover'] ?? null, '#11786D'),
            '--ui-on-accent' => self::foreground($accent),
            '--ui-accent-soft' => self::mix($surface, $accent, .09),
            '--ui-bg' => self::hex($theme['background'] ?? null, $dark ? '#111C2A' : '#F5F7FB'),
            '--ui-surface' => $surface,
            '--ui-surface-alt' => self::mix($surface, $ink, .025),
            '--ui-aside' => $aside,
            '--ui-sidebar-ink' => $asideInk,
            '--ui-ink' => $ink,
            '--ui-muted' => self::mix($surface, $ink, .70),
            '--ui-border' => self::hex($theme['border'] ?? null, $dark ? '#33475C' : '#DEE5EF'),
            '--ui-hover' => self::mix($surface, $accent, .06),
            '--ui-focus' => self::mix($surface, $accent, .28),
            '--ui-nav-active-bg' => $navActive,
            '--ui-nav-active-text' => $navInk,
            '--ui-danger-text' => $dark ? '#FFB8BC' : '#B5213B',
            '--ui-danger-bg' => $dark ? '#492634' : '#FFF1F2',
            '--ui-danger-border' => $dark ? '#76404B' : '#FFD4DD',
            '--ui-warning-text' => $dark ? '#FFDB96' : '#855509',
            '--ui-warning-bg' => $dark ? '#423522' : '#FFFAED',
            '--ui-warning-border' => $dark ? '#735836' : '#F1D6A2',
        ];
    }
}
