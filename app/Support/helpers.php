<?php

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

if (! function_exists('id_date')) {
    /**
     * Indonesian display date: 21 September 2026
     */
    function id_date(mixed $value, bool $time = false): string
    {
        if (blank($value)) {
            return '—';
        }

        $date = $value instanceof CarbonInterface
            ? $value->copy()->locale('id')
            : Carbon::parse($value)->locale('id');

        return $date->translatedFormat($time ? 'd F Y, H:i' : 'd F Y');
    }
}

if (! function_exists('id_kg')) {
    function id_kg(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return number_format((float) $value, 1, ',', '.').' kg';
    }
}

if (! function_exists('id_rupiah')) {
    function id_rupiah(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'Rp 0';
        }

        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }
}

if (! function_exists('table_no')) {
    function table_no(mixed $paginator, int $index): int
    {
        if (is_object($paginator) && method_exists($paginator, 'firstItem')) {
            return (int) ($paginator->firstItem() ?? 1) + $index;
        }

        return $index + 1;
    }
}
