@props(['cattle', 'panel', 'tab' => null])
@php
    $href = $tab
        ? route($panel.'.cattle.show', [$cattle, 'tab' => $tab])
        : route($panel.'.cattle.show', $cattle);
@endphp
<a href="{{ $href }}" {{ $attributes->merge(['class' => 'font-semibold text-primary hover:underline']) }}>{{ $slot->isNotEmpty() ? $slot : $cattle->code }}</a>
