<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | SeaLedger</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-mist font-sans text-sm text-ink antialiased">
    @php
        $links = [
            ['route' => 'dashboard', 'match' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'bi-grid-1x2'],
            ['route' => 'inventory.index', 'match' => 'inventory.*', 'label' => 'Fish Inventory', 'icon' => 'bi-box-seam'],
            ['route' => 'restocking.index', 'match' => 'restocking.*', 'label' => 'Restocking Plans', 'icon' => 'bi-calendar2-check'],
            ['route' => 'sales.index', 'match' => 'sales.*', 'label' => 'Daily Sales', 'icon' => 'bi-receipt'],
            ['route' => 'deliveries.index', 'match' => 'deliveries.*', 'label' => 'Delivery Service', 'icon' => 'bi-truck'],
            ['route' => 'reports.index', 'match' => 'reports.*', 'label' => 'Reports & History', 'icon' => 'bi-bar-chart-line'],
        ];
    @endphp

    <aside id="sidebar" class="fixed inset-y-0 left-0 z-20 flex w-[260px] -translate-x-full flex-col border-r border-line bg-white px-4 py-6 transition-transform md:w-[220px] md:translate-x-0 lg:w-[250px] print:hidden">
        <a href="{{ route('dashboard') }}" class="flex flex-col items-center gap-0.5 px-2 pb-5 text-center">
            <img src="{{ asset('images/logo.png') }}" alt="SeaLedger" class="block h-auto w-[130px]">
            <small class="mt-1 block text-[11px]">Fish Inventory &amp; Sales</small>
        </a>

        <p class="kicker mx-2.5 mb-2.5 mt-1">WORKSPACE</p>

        <nav class="grid gap-1">
            @foreach ($links as $link)
                <a href="{{ route($link['route']) }}"
                   class="flex items-center gap-3 rounded-[10px] px-3 py-3 font-semibold transition-colors hover:bg-soft hover:text-brand {{ request()->routeIs($link['match']) ? 'bg-soft text-brand' : 'text-black' }}">
                    <i class="bi {{ $link['icon'] }} text-lg"></i>
                    {{ $link['label'] }}
                    @if ($link['route'] === 'restocking.index')
                        <span class="ml-auto rounded-full bg-[#d2eeeb] px-[7px] py-0.5 text-[11px] text-brand-dark">{{ $pendingBadge }}</span>
                    @endif
                    @if ($link['route'] === 'deliveries.index' && $deliveryBadge > 0)
                        <span class="ml-auto rounded-full bg-[#d2eeeb] px-[7px] py-0.5 text-[11px] text-brand-dark">{{ $deliveryBadge }}</span>
                    @endif
                </a>
            @endforeach
        </nav>

        <div class="mt-auto grid gap-1">
            <a href="{{ route('home') }}" class="flex items-center gap-3 rounded-[10px] px-3 py-3 font-semibold text-black transition-colors hover:bg-soft hover:text-brand">
                <i class="bi bi-shop text-lg"></i>
                Customer view
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex w-full cursor-pointer items-center gap-3 rounded-[10px] px-3 py-3 text-left font-semibold text-black transition-colors hover:bg-soft hover:text-brand">
                    <i class="bi bi-box-arrow-left text-lg"></i>
                    Log out
                </button>
            </form>
        </div>
    </aside>

    <div class="min-h-screen md:ml-[220px] lg:ml-[250px] print:ml-0">
        <header class="sticky top-0 z-10 flex h-[76px] items-center justify-between gap-3 border-b border-line bg-white px-4 md:px-[22px] lg:px-[34px] print:hidden">
            <button type="button" id="mobileMenuButton" aria-label="Toggle navigation" class="cursor-pointer text-2xl md:hidden">
                <i class="bi bi-list"></i>
            </button>

            <div>
                <span class="text-[11px]">Workspace /</span>
                <strong>@yield('title')</strong>
            </div>

            <div class="flex items-center gap-2.5">
                <span class="grid size-9 place-items-center rounded-full bg-[#d9f1ee] font-extrabold text-brand">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                <span class="hidden sm:block">
                    <strong class="block text-xs">{{ auth()->user()->name }}</strong>
                    <small class="block text-[11px]">Store account</small>
                </span>
            </div>
        </header>

        <main class="mx-auto max-w-[1600px] px-3.5 py-[22px] md:px-[22px] lg:px-[34px] lg:py-[30px] print:p-0">
            @if ($errors->any())
                <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">
                    <strong class="mb-1 block">Please fix the following:</strong>
                    <ul class="list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')

            <footer class="flex flex-col justify-between gap-3 px-0.5 pb-1 pt-7 text-[10px] text-[#95a2a8] sm:flex-row">
                © 2026 SeaLedger · Fish Inventory and Sales Monitoring System
            </footer>
        </main>
    </div>

    <div id="sidebarOverlay" class="fixed inset-0 z-[15] hidden bg-[#0c252c]/45 md:hidden"></div>
    <div id="appToast" class="pointer-events-none fixed bottom-6 right-6 z-[100] translate-y-3 rounded-[10px] bg-[#163b43] px-[18px] py-3 text-white opacity-0 shadow-xl transition duration-200">{{ session('success') }}</div>
</body>
</html>
