@props(['label'])

@php
    $tone = match ($label) {
        'Low stock', 'Sold out', 'Pending' => 'bg-[#fff1d8] text-[#9a620c]',
        'Confirmed', 'Out for delivery' => 'bg-[#e1efff] text-[#1d5fa8]',
        'Unavailable', 'Cancelled' => 'bg-[#edf1f3] text-[#64747d]',
        default => 'bg-[#e3f7ed] text-[#1c8156]',
    };
@endphp

<span class="inline-block whitespace-nowrap rounded-full px-2 py-[5px] text-[10px] font-bold {{ $tone }}">{{ $label }}</span>
