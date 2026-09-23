@php
    $cattle = $cattle ?? null;
    $bcs = $bcs ?? $cattle?->latestBcs;
    $href = $href ?? null;
    $score = (float) ($bcs?->score ?? 0);
    $badge = $score < 2.5 ? 'badge-bahaya' : ($score <= 3.5 ? 'badge-sehat' : 'badge-suspek');
    $items = $bcs?->recommendations ?? [];
    $classes = 'block rounded-2xl border border-line bg-white overflow-hidden hover:border-primary/40 hover:shadow-soft transition';
@endphp
@if($href)
    <a href="{{ $href }}" class="{{ $classes }}">
@else
    <article class="{{ $classes }}">
@endif
    <div class="flex items-start gap-4 p-4">
        <img src="{{ $cattle?->photoUrl() }}" alt="" width="64" height="64" class="h-16 w-16 shrink-0 rounded-2xl object-cover bg-primary-soft">
        <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-extrabold truncate">{{ $cattle?->name ?: 'Tanpa nama' }}</p>
                    <p class="text-xs font-semibold text-primary">{{ $cattle?->code }}</p>
                    @if($bcs?->assessed_at || $bcs?->weight_kg_snapshot)
                        <p class="text-xs text-muted mt-1">
                            {{ $bcs?->assessed_at ? id_date($bcs->assessed_at) : '' }}
                            @if($bcs?->weight_kg_snapshot)
                                · {{ id_kg($bcs->weight_kg_snapshot) }}
                            @endif
                        </p>
                    @endif
                </div>
                <div class="shrink-0 text-right">
                    <p class="text-[11px] uppercase tracking-wide text-muted font-semibold">BCS</p>
                    <p class="text-3xl font-extrabold text-primary-dark leading-none tabular-nums">{{ $bcs ? number_format($score, 1) : '—' }}</p>
                    @if($bcs?->category)
                        <span class="{{ $badge }} mt-1.5">{{ $bcs->category }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @if($bcs?->recommendation_summary || !empty($items))
        <div class="px-4 pb-4">
            <div class="rounded-2xl bg-primary-soft/70 px-4 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-primary-dark">Rekomendasi</p>
                @if($bcs?->recommendation_summary)
                    <p class="font-semibold text-sm mt-1 leading-relaxed">{{ $bcs->recommendation_summary }}</p>
                @endif
                @if(!empty($items))
                    <ul class="mt-2 space-y-1.5 text-sm text-ink/80">
                        @foreach ($items as $item)
                            <li class="flex gap-2 leading-relaxed">
                                <span class="text-primary font-bold shrink-0">•</span>
                                <span>{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    @endif
@if($href)
    </a>
@else
    </article>
@endif
