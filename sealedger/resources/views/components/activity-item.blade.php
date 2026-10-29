@props(['icon' => 'bi-activity', 'title', 'detail' => null, 'time' => null])

<div class="flex items-start gap-[11px]">
    <span class="grid size-8 flex-none place-items-center rounded-[10px] bg-[#e2f5f2] text-brand">
        <i class="bi {{ $icon }}"></i>
    </span>
    <div>
        <strong class="block text-xs">{{ $title }}</strong>
        @if ($detail)
            <span class="mt-[3px] block text-[11px]">{{ $detail }}</span>
        @endif
        @if ($time)
            <small class="mt-[5px] block text-[10px] text-[#9aa7ac]">{{ $time }}</small>
        @endif
    </div>
</div>
