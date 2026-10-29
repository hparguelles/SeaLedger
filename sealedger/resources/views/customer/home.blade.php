<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Today's Fresh Fish | SeaLedger</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-mist font-sans text-sm text-ink antialiased">
    @php
        // Opens a Messenger chat with the vendor. MESSENGER_URL in .env overrides this.
        $messengerUrl = config('services.messenger.url') ?: 'https://m.me/tricia.francisco.121';
    @endphp
    <header class="sticky top-0 z-10 border-b border-line bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <img src="{{ asset('images/logo.png') }}" alt="SeaLedger" class="h-12 w-auto">
            </a>

            @auth
                <a href="{{ route('dashboard') }}" class="btn-primary">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="btn-outline">
                    <i class="bi bi-person"></i>
                    Vendor login
                </a>
            @endauth
        </div>
    </header>

    <section class="bg-gradient-to-br from-brand-dark to-brand text-white">
        <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 sm:py-16">
            <div class="text-[10px] font-extrabold tracking-widest text-white/70">TODAY'S CATCH</div>
            <h1 class="mt-3 text-3xl font-extrabold tracking-tight sm:text-5xl">Fresh fish, fair prices</h1>
            <p class="mt-3 max-w-xl text-base text-white/80">See what is available at the stall today and the price per kilo before you come.</p>
            <a href="{{ $messengerUrl }}" target="_blank" rel="noopener"
               class="mt-6 inline-flex cursor-pointer items-center gap-2 rounded-lg bg-white px-5 py-2.5 text-sm font-bold text-brand transition-colors hover:bg-soft">
                <i class="bi bi-truck"></i>
                Order for delivery
            </a>
        </div>
    </section>

    <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="flex flex-wrap gap-2">
                <button type="button" data-category="all" class="category-chip cursor-pointer rounded-full border border-line bg-brand px-4 py-1.5 text-xs font-bold text-white">All</button>
                @foreach ($categories as $category)
                    <button type="button" data-category="{{ $category }}" class="category-chip cursor-pointer rounded-full border border-line bg-white px-4 py-1.5 text-xs font-bold text-ink">{{ $category }}</button>
                @endforeach
            </div>

            <input type="text" id="fishSearch" placeholder="Search fish..." class="field md:max-w-xs">
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($products as $product)
                <article class="fish-card card flex flex-col overflow-hidden !p-0"
                         data-search="{{ strtolower($product->name.' '.$product->category) }}"
                         data-category="{{ $product->category }}">
                    @if ($product->image_url)
                        <button type="button"
                                class="fish-photo block aspect-[4/3] w-full cursor-zoom-in overflow-hidden bg-[#e4f5f2]"
                                data-full="{{ $product->image_url }}"
                                data-title="{{ $product->name }}"
                                aria-label="View photo of {{ $product->name }}">
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy" class="size-full object-cover transition-transform duration-300 hover:scale-105">
                        </button>
                    @else
                        <div class="grid aspect-[4/3] w-full place-items-center bg-[#e4f5f2] text-3xl font-extrabold text-brand/60">{{ $product->initials }}</div>
                    @endif

                    <div class="flex flex-1 items-center gap-4 p-4">
                        <div class="min-w-0 flex-1">
                            <h2 class="truncate text-base font-extrabold">{{ $product->name }}</h2>
                            <p class="text-xs">{{ $product->category ?: 'Fresh catch' }}</p>
                            <div class="mt-2"><x-status-pill :label="$product->status_label" /></div>
                        </div>

                        <div class="text-right">
                            <div class="text-xl font-extrabold tracking-tight">₱{{ number_format($product->price, 2) }}</div>
                            <div class="text-[11px]">per kg</div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <x-empty-state id="fishEmpty" class="{{ $products->isEmpty() ? '' : 'hidden' }}">No fish match your search.</x-empty-state>


        <p class="mt-8 text-center text-xs">Prices and availability change through the day. Visit the stall to buy.</p>
    </main>

    <div id="photoViewer" class="fixed inset-0 z-50 hidden items-center justify-center bg-ink/80 p-4" role="dialog" aria-modal="true">
        <figure class="max-h-full max-w-3xl">
            <img id="photoViewerImg" src="" alt="" class="max-h-[80vh] w-auto rounded-xl object-contain">
            <figcaption id="photoViewerTitle" class="mt-3 text-center text-sm font-bold text-white"></figcaption>
        </figure>
        <button type="button" id="photoViewerClose" class="absolute right-4 top-4 cursor-pointer text-2xl text-white" aria-label="Close">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <a href="{{ $messengerUrl }}" target="_blank" rel="noopener"
       class="fixed bottom-5 right-5 z-40 flex items-center gap-2 rounded-full bg-[#0084ff] px-5 py-3 text-sm font-bold text-white shadow-xl transition-transform hover:scale-105 print:hidden"
       aria-label="Chat with us on Messenger">
        <i class="bi bi-messenger text-xl"></i>
        <span class="hidden sm:inline">Chat with us</span>
    </a>

    <footer class="px-4 pb-8 text-center text-[10px] text-[#95a2a8]">© 2026 SeaLedger · Fish Inventory and Sales Monitoring System</footer>
</body>
</html>