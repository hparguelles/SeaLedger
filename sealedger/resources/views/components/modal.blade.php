@props(['id', 'title'])

<div id="{{ $id }}" class="modal-root fixed inset-0 z-50 hidden items-center justify-center bg-ink/50 p-4">
    <div class="max-h-[92vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-line px-[22px] py-[18px]">
            <h2 id="{{ $id }}Title" class="text-lg font-bold">{{ $title }}</h2>
            <button type="button" data-close-modal class="cursor-pointer text-xl leading-none text-gray-500 hover:text-ink" aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        {{ $slot }}
    </div>
</div>
