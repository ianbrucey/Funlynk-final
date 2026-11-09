<button type="button"
        wire:click.stop="toggle"
        wire:loading.attr="disabled"
        wire:target="toggle"
        aria-pressed="{{ $interested ? 'true' : 'false' }}"
        class="inline-flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl px-3.5 py-3 text-sm font-semibold text-white whitespace-nowrap transition disabled:cursor-wait disabled:opacity-60 hover:scale-[1.02]"
        style="{{ $interested
            ? 'background: rgba(232,161,58,0.22); border: 1px solid rgba(232,161,58,0.65); color: #fcd9a0;'
            : 'background: transparent; border: 1px solid rgba(255,255,255,0.14); color: #eef1ff;' }}">
    <span>{{ $interested ? '✓ Interested' : "I'm interested" }}</span>
    @if($count > 0)
        <span class="rounded-full px-1.5 py-0.5 text-[0.7rem] font-bold"
              style="background: rgba(255,255,255,0.12);">{{ $count }}</span>
    @endif
</button>
