@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-semibold text-ink tracking-normal']) }}>
    {{ $value ?? $slot }}
</label>
