@props([
    'name' => 'image',
    'required' => false,
])

<div
    class="file-wow"
    x-data="{
        filename: '',
        preview: '',
        pick(which) { this.$refs[which].click() },
        use(event) {
            const file = event.target.files?.[0]
            if (! file) return
            this.filename = file.name
            const transfer = new DataTransfer()
            transfer.items.add(file)
            this.$refs.field.files = transfer.files
            if (this.preview) URL.revokeObjectURL(this.preview)
            this.preview = URL.createObjectURL(file)
        }
    }"
>
    <input type="file" name="{{ $name }}" accept="image/*" class="sr-only" x-ref="field" @required($required) {{ $attributes }}>
    <input type="file" accept="image/*" capture="environment" class="sr-only" x-ref="camera" @change="use($event)">
    <input type="file" accept="image/*" class="sr-only" x-ref="gallery" @change="use($event)">

    <div class="grid grid-cols-2 gap-2">
        <button type="button" class="btn-ghost !min-h-11 !py-2.5" @click="pick('camera')">Kamera</button>
        <button type="button" class="btn-ghost !min-h-11 !py-2.5" @click="pick('gallery')">Galeri</button>
    </div>
    <p class="text-sm text-muted break-all" x-text="filename || 'Belum ada foto dipilih'"></p>
    <img x-show="preview" x-cloak :src="preview" alt="Pratinjau foto" class="h-36 w-full rounded-xl object-cover bg-white">
</div>
