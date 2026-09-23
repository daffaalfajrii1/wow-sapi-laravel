@php
    $kinds = [
        'ai' => ['label' => 'AI', 'class' => 'bg-primary-soft text-primary-dark'],
        'bobot' => ['label' => 'Bobot', 'class' => 'bg-emerald-50 text-emerald-800'],
        'kesehatan' => ['label' => 'Kesehatan', 'class' => 'bg-rose-50 text-rose-700'],
        'vaksin' => ['label' => 'Vaksin', 'class' => 'bg-sky-50 text-sky-800'],
        'bcs' => ['label' => 'BCS', 'class' => 'bg-amber-50 text-amber-800'],
        'pakan' => ['label' => 'Pakan', 'class' => 'bg-lime-50 text-lime-800'],
        'reproduksi' => ['label' => 'Reproduksi', 'class' => 'bg-violet-50 text-violet-800'],
        'kematian' => ['label' => 'Kematian', 'class' => 'bg-slate-100 text-slate-700'],
    ];
@endphp
<ol class="relative">
    @forelse ($timeline as $item)
        @php $meta = $kinds[$item['kind']] ?? ['label' => ucfirst($item['kind']), 'class' => 'bg-primary-soft text-primary-dark']; @endphp
        <li class="relative pl-8 {{ $loop->last ? '' : 'pb-4' }}">
            @unless($loop->last)
                <span class="absolute left-[5px] top-3 bottom-0 w-px bg-line"></span>
            @endunless
            <span class="absolute left-0 top-2.5 h-2.5 w-2.5 rounded-full bg-primary ring-4 ring-primary-soft"></span>
            <article class="rounded-2xl border border-line bg-white px-4 py-3 shadow-soft">
                <div class="flex flex-wrap items-center gap-2 mb-1">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $meta['class'] }}">{{ $meta['label'] }}</span>
                    <time class="text-xs text-muted">{{ id_date($item['at'], true) }}</time>
                </div>
                <p class="text-sm font-medium text-ink">{{ $item['title'] }}</p>
            </article>
        </li>
    @empty
        <li class="text-sm text-muted pl-1">Belum ada riwayat untuk sapi ini.</li>
    @endforelse
</ol>
