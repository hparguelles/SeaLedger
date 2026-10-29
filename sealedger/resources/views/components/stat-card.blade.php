@props(['kicker', 'label'])

<article class="card sm:p-[18px]">
    <span class="kicker">{{ $kicker }}</span>
    <div class="mb-1 mt-2.5 text-[29px] font-extrabold tracking-tight">{{ $slot }}</div>
    <div class="text-xs">{{ $label }}</div>
</article>
