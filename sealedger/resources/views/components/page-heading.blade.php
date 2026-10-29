@props(['title'])

<section class="mb-[23px] flex flex-col items-start justify-between gap-5 sm:flex-row sm:items-center print:hidden">
    <div>
        <div class="kicker">YOUR BUSINESS AT A GLANCE</div>
        <h1 class="my-[7px] text-[25px] font-extrabold tracking-tight md:text-[29px]">{{ $title }}</h1>
    </div>
    {{ $slot }}
</section>
