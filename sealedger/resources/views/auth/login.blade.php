<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in | SeaLedger</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid min-h-screen place-items-center bg-[#0b4f5c] bg-cover bg-center p-6 font-sans text-sm text-ink antialiased"
      style="background-image: linear-gradient(rgba(4, 44, 54, .35), rgba(4, 44, 54, .55)), url('{{ asset('images/login-bg.jpg') }}')">
    <main class="w-full max-w-[400px] rounded-[18px] border border-white/60 bg-white/90 p-[35px] text-center shadow-[0_20px_60px_rgba(0,20,30,.35)] backdrop-blur-md">
        <img src="{{ asset('images/logo.png') }}" alt="SeaLedger logo" class="mx-auto mb-1.5 block h-auto w-[190px] max-w-[70%]">
        <h1 class="sr-only">SeaLedger</h1>
        <p class="mb-6 font-medium">Fish Inventory &amp; Sales Monitoring</p>

        <form method="POST" action="{{ route('login.attempt') }}" class="text-left">
            @csrf

            <label for="username" class="field-label">Username</label>
            <input type="text" id="username" name="username" value="{{ old('username') }}" required autofocus autocomplete="username" class="field mb-4">

            <label for="password" class="field-label">Password</label>
            <input type="password" id="password" name="password" required autocomplete="current-password" class="field mb-4">

            <label class="mb-4 flex items-center gap-2">
                <input type="checkbox" name="remember" value="1" class="size-4 accent-brand">
                <span class="text-xs">Keep me signed in</span>
            </label>

            @error('username')
                <div class="mb-4 text-xs text-red-600">{{ $message }}</div>
            @enderror

            <button type="submit" class="btn-primary w-full">Sign in</button>
        </form>

        <a href="{{ route('home') }}" class="mt-5 inline-block text-xs font-bold text-brand">← Back to today's fish prices</a>
    </main>
</body>
</html>
