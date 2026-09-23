@props(['variant' => 'full'])
@php
    $src = $variant === 'mark'
        ? asset('images/wow-sapi-mark.png')
        : asset('images/wow-sapi-logo.png');
@endphp
<img src="{{ $src }}" alt="WOW SAPI" {{ $attributes->merge(['class' => 'object-contain']) }}>
