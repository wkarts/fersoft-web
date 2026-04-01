<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class FiscalDateHelper
{
    public static function timezone(): string
    {
        return (string) config('app.timezone', env('APP_TIMEZONE', 'America/Bahia'));
    }

    public static function applyDefaultTimezone(): void
    {
        date_default_timezone_set(static::timezone());
    }

    public static function nowXml(): string
    {
        static::applyDefaultTimezone();

        return Carbon::now(static::timezone())->format('Y-m-d\TH:i:sP');
    }

    public static function toXmlDateTime($value = null, bool $allowNull = false): ?string
    {
        static::applyDefaultTimezone();

        if ($value === null || $value === '') {
            return $allowNull ? null : static::nowXml();
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)
                ->setTimezone(static::timezone())
                ->format('Y-m-d\TH:i:sP');
        }

        $value = trim((string) $value);

        if ($value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
            return $allowNull ? null : static::nowXml();
        }

        $now = Carbon::now(static::timezone());
        $formats = [
            'Y-m-d\TH:i:sP',
            'Y-m-d H:i:s',
            'Y-m-d',
            'd/m/Y H:i:s',
            'd/m/Y',
        ];

        foreach ($formats as $format) {
            try {
                $dt = Carbon::createFromFormat($format, $value, static::timezone());

                if ($dt !== false) {
                    if (in_array($format, ['Y-m-d', 'd/m/Y'], true)) {
                        $dt->setTime($now->hour, $now->minute, $now->second);
                    }

                    return $dt
                        ->setTimezone(static::timezone())
                        ->format('Y-m-d\TH:i:sP');
                }
            } catch (\Throwable $e) {
            }
        }

        try {
            return Carbon::parse($value, static::timezone())
                ->setTimezone(static::timezone())
                ->format('Y-m-d\TH:i:sP');
        } catch (\Throwable $e) {
            return $allowNull ? null : static::nowXml();
        }
    }
}
