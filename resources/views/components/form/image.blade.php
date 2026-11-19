{{-- Carga de imagen con vista previa. Valida tipo y tamaño también en el cliente. --}}
@props(['name', 'label', 'current' => null, 'removeName' => null, 'hint' => 'PNG, JPG o WebP · máx. 4 MB. Se conserva la transparencia.'])
<div x-data="{ preview: @js($current), error: '', remove: false,
        pick(e) { const f = e.target.files[0]; this.error = '';
            if (!f) return;
            if (!['image/png','image/jpeg','image/webp'].includes(f.type)) { this.error = 'Formato no permitido.'; e.target.value=''; return; }
            if (f.size > 4 * 1024 * 1024) { this.error = 'La imagen supera 4 MB.'; e.target.value=''; return; }
            this.preview = URL.createObjectURL(f); this.remove = false; } }"
     {{ $attributes->merge(['class' => '']) }}>
    <span class="label">{{ $label }}</span>
    <div class="flex items-center gap-4">
        <div class="grid size-24 shrink-0 place-items-center overflow-hidden rounded-2xl border border-dashed border-white/15 bg-ink-900 bg-[conic-gradient(#182131_25%,#101722_0_50%,#182131_0_75%,#101722_0)] bg-[length:16px_16px]">
            <template x-if="preview && !remove"><img :src="preview" alt="" class="size-full object-contain p-1"></template>
            <template x-if="!preview || remove"><x-icon name="image" class="size-7 text-mute-dim" /></template>
        </div>
        <div class="space-y-2">
            <label class="btn-secondary btn-sm cursor-pointer">
                <x-icon name="image" class="size-4" /> Seleccionar
                <input type="file" name="{{ $name }}" accept="image/png,image/jpeg,image/webp" class="sr-only" @change="pick">
            </label>
            @if ($removeName && $current)
                <label class="flex items-center gap-2 text-xs text-mute">
                    <input type="checkbox" name="{{ $removeName }}" value="1" x-model="remove" class="rounded border-white/20 bg-ink-900 text-brand"> Quitar imagen actual
                </label>
            @endif
            <p class="hint">{{ $hint }}</p>
            <p class="error" x-show="error" x-text="error" x-cloak></p>
            @error($name)<p class="error">{{ $message }}</p>@enderror
        </div>
    </div>
</div>
