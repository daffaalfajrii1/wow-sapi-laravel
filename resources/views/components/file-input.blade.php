@props([
    'name' => 'image',
    'accept' => 'image/jpeg,image/png',
    'required' => false,
    'label' => 'Pilih foto',
])

<label x-data="{ filename: '' }" class="file-wow">
    <span class="flex flex-col sm:flex-row sm:items-center gap-3">
        <span class="inline-flex items-center justify-center rounded-xl bg-white border border-line px-4 py-2.5 text-sm font-semibold text-ink shrink-0">
            {{ $label }}
        </span>
        <span class="text-sm text-muted break-all" x-text="filename || 'Belum ada file dipilih'"></span>
    </span>
    <input type="file" name="{{ $name }}" accept="{{ $accept }}" @required($required) {{ $attributes }}
           @change="filename = $event.target.files?.[0]?.name || ''">
</label>
