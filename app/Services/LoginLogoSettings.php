<?php

namespace App\Services;

/**
 * Configuração opcional da logo da access_4. Não consulta nem altera o banco.
 * Ausência de personalização mantém integralmente o CSS de cada modelo.
 */
final class LoginLogoSettings
{
    public const LIMITS = [
        'desktop' => ['width' => [24, 480], 'height' => [24, 320]],
        'mobile' => ['width' => [24, 240], 'height' => [24, 180]],
    ];

    public static function defaults(): array
    {
        return [
            'width' => null,
            'height' => null,
            'align' => 'default',
            'position' => 'default',
            'inset' => null,
            'space_before' => null,
            'space_after' => null,
        ];
    }

    public static function rules(): array
    {
        $rules = ['logo' => ['sometimes', 'array:desktop,mobile']];
        foreach (self::LIMITS as $scope => $sizes) {
            $prefix = 'logo.' . $scope;
            $rules[$prefix] = ['sometimes', 'array:width,height,align,position,inset,space_before,space_after'];
            foreach ($sizes + ['inset' => [0, 64], 'space_before' => [0, 96], 'space_after' => [0, 96]] as $key => [$min, $max]) {
                $rules[$prefix . '.' . $key] = ['sometimes', 'nullable', 'integer', "between:{$min},{$max}"];
            }
            $rules[$prefix . '.align'] = ['sometimes', 'string', 'in:default,left,center,right'];
            $rules[$prefix . '.position'] = ['sometimes', 'string', 'in:default,before,after'];
        }
        return $rules;
    }

    /** Valores não reconhecidos, inclusive JSON legado malformado, não viram CSS. */
    public static function normalize($raw): array
    {
        $raw = is_array($raw) ? $raw : [];
        $result = [];
        foreach (self::LIMITS as $scope => $sizes) {
            $profile = is_array($raw[$scope] ?? null) ? $raw[$scope] : [];
            $result[$scope] = self::defaults();
            foreach ($sizes + ['inset' => [0, 64], 'space_before' => [0, 96], 'space_after' => [0, 96]] as $key => [$min, $max]) {
                $value = $profile[$key] ?? null;
                if ((is_int($value) || (is_string($value) && ctype_digit($value)))
                    && $value >= $min && $value <= $max) {
                    $result[$scope][$key] = (int) $value;
                }
            }
            foreach (['align' => ['default', 'left', 'center', 'right'], 'position' => ['default', 'before', 'after']] as $key => $allowed) {
                if (in_array($profile[$key] ?? null, $allowed, true)) {
                    $result[$scope][$key] = $profile[$key];
                }
            }
        }
        return $result;
    }

    /** Gera somente nomes fixos de atributos e medidas numéricas em pixels. */
    public static function presentation($raw): array
    {
        $profiles = self::normalize($raw);
        $attributes = [];
        $styles = [];
        foreach ($profiles as $scope => $profile) {
            $custom = false;
            foreach ($profile as $key => $value) {
                if ($value === null || $value === 'default') {
                    continue;
                }
                $custom = true;
                $suffix = str_replace('_', '-', $key);
                $attributes['data-logo-' . $scope . '-' . $suffix] = (string) $value;
                if (is_int($value)) {
                    $styles[] = '--login-logo-' . $scope . '-' . $suffix . ':' . $value . 'px';
                }
            }
            if ($custom) {
                $attributes['data-logo-' . $scope] = 'custom';
            }
            if ($profile['width'] !== null || $profile['height'] !== null) {
                $attributes['data-logo-' . $scope . '-size'] = 'custom';
            }
        }
        return ['profiles' => $profiles, 'attributes' => $attributes, 'style' => implode(';', $styles)];
    }
}
