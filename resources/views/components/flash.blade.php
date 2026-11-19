@php
    $messages = array_filter(['success' => session('success'), 'error' => session('error'), 'status' => session('status')]);
@endphp
<div x-data="{ toasts: [] , add(m, t='success'){ const id=Date.now()+Math.random(); this.toasts.push({id,m,t}); setTimeout(()=>this.toasts=this.toasts.filter(x=>x.id!==id), 5000) } }"
     x-init="@foreach ($messages as $type => $msg) add(@js($msg), @js($type === 'error' ? 'error' : 'success')); @endforeach"
     @notify.window="add($event.detail.message, $event.detail.type ?? 'success')"
     class="pointer-events-none fixed inset-x-4 bottom-4 z-[60] flex flex-col items-center gap-2 sm:inset-x-auto sm:right-6 sm:items-end" aria-live="polite">
    <template x-for="t in toasts" :key="t.id">
        <div x-transition.opacity.duration.200ms role="status"
             class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl border px-4 py-3 text-sm shadow-2xl backdrop-blur"
             :class="t.t === 'error' ? 'border-brand/40 bg-ink-800/95 text-white' : 'border-success/30 bg-ink-800/95 text-white'">
            <span class="mt-0.5 size-2 shrink-0 rounded-full" :class="t.t === 'error' ? 'bg-brand' : 'bg-success'"></span>
            <span class="flex-1" x-text="t.m"></span>
            <button type="button" class="text-mute hover:text-white" @click="toasts = toasts.filter(x => x.id !== t.id)" aria-label="Cerrar">✕</button>
        </div>
    </template>
</div>
