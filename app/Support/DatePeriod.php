<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DatePeriod
{
    public const PRESETS = [
        '1m' => ['label' => '1 Bulan', 'months' => 1],
        '3m' => ['label' => '3 Bulan', 'months' => 3],
        '6m' => ['label' => '6 Bulan', 'months' => 6],
        '12m' => ['label' => '1 Tahun', 'months' => 12],
        'all' => ['label' => 'Semua', 'months' => null],
        'custom' => ['label' => 'Kustom', 'months' => null],
    ];

    public function __construct(
        public string $range,
        public ?Carbon $from,
        public ?Carbon $to,
    ) {}

    public static function fromRequest(Request $request, string $default = '6m', bool $allowAll = false): self
    {
        $range = $request->string('range', $default)->toString();
        if (! isset(self::PRESETS[$range])) {
            $range = $default;
        }
        if ($range === 'all' && ! $allowAll) {
            $range = $default;
        }

        if ($range === 'custom') {
            $from = $request->filled('from') ? Carbon::parse($request->input('from'))->startOfDay() : null;
            $to = $request->filled('to') ? Carbon::parse($request->input('to'))->endOfDay() : now()->endOfDay();
            if (! $from) {
                $from = now()->subMonths(6)->startOfDay();
            }
            if ($from->gt($to)) {
                [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
            }

            return new self('custom', $from, $to);
        }

        if ($range === 'all') {
            return new self('all', null, null);
        }

        $months = self::PRESETS[$range]['months'] ?? 6;

        return new self($range, now()->subMonths($months)->startOfDay(), now()->endOfDay());
    }

    public function label(): string
    {
        if ($this->range === 'all' || (! $this->from && ! $this->to)) {
            return 'Semua periode';
        }

        return id_date($this->from).' – '.id_date($this->to);
    }

    public function query(array $extra = []): array
    {
        $q = array_merge(['range' => $this->range], $extra);
        if ($this->range === 'custom' && $this->from && $this->to) {
            $q['from'] = $this->from->toDateString();
            $q['to'] = $this->to->toDateString();
        }

        return $q;
    }

    public function apply($query, string $column)
    {
        if ($this->from) {
            $query->where($column, '>=', $this->from);
        }
        if ($this->to) {
            $query->where($column, '<=', $this->to);
        }

        return $query;
    }

    public function contains(mixed $at): bool
    {
        if (! $at) {
            return false;
        }
        $date = $at instanceof Carbon ? $at : Carbon::parse($at);
        if ($this->from && $date->lt($this->from)) {
            return false;
        }
        if ($this->to && $date->gt($this->to)) {
            return false;
        }

        return true;
    }
}
