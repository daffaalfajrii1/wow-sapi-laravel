@php
    $period = $period ?? null;
    $current = $period?->range ?? ($default ?? '6m');
    $presets = $presets ?? ['1m' => '1 Bulan', '3m' => '3 Bulan', '6m' => '6 Bulan', '12m' => '1 Tahun'];
    $allowAll = $allowAll ?? false;
    if ($allowAll) {
        $presets = ['all' => 'Semua'] + $presets;
    }
    $presets['custom'] = 'Kustom';
    $hidden = $hidden ?? [];
    $action = $action ?? url()->current();
    $herd = $herd ?? collect();
    $chartCattleId = $chartCattleId ?? null;
@endphp
<form method="get" action="{{ $action }}" class="flex flex-col gap-2">
    @foreach ($hidden as $name => $value)
        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endforeach
    @if($herd->isNotEmpty())
        <input type="hidden" name="range" value="{{ $current }}">
        <label class="text-xs text-muted">
            Sapi
            <select name="cattle_id" class="input-wow mt-1" onchange="this.form.submit()">
                <option value="">Semua sapi</option>
                @foreach ($herd as $cow)
                    <option value="{{ $cow->id }}" @selected((string) $chartCattleId === (string) $cow->id)>
                        {{ $cow->code }}{{ $cow->name ? ' · '.$cow->name : '' }}
                    </option>
                @endforeach
            </select>
        </label>
    @endif
    <div class="flex flex-wrap items-center gap-1.5">
        @foreach ($presets as $key => $label)
            <button type="submit" name="range" value="{{ $key }}"
                class="px-3 py-1.5 rounded-full text-xs font-semibold border transition-colors {{ $current === $key ? 'bg-primary text-white border-primary' : 'bg-white border-line text-ink hover:bg-primary-soft' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>
    @if($current === 'custom')
        <div class="flex flex-col sm:flex-row gap-2 items-end">
            <input type="hidden" name="range" value="custom">
            <label class="text-xs text-muted">Dari
                <input type="date" name="from" class="input-wow mt-1" value="{{ $period?->from?->toDateString() }}" required>
            </label>
            <label class="text-xs text-muted">Sampai
                <input type="date" name="to" class="input-wow mt-1" value="{{ $period?->to?->toDateString() }}" required>
            </label>
            <button class="btn-primary">Terapkan</button>
        </div>
    @endif
</form>
