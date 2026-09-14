<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#111827">
    <title>{{ config('app.name') }}</title>

    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full font-sans antialiased text-gray-900">
    <div class="min-h-full flex flex-col">
        <main class="flex-1 pb-20">
            {{ $slot }}
        </main>

        {{-- Bottom nav: Home / History / Profile, per UX/UI Spec §2. --}}
        <nav class="fixed bottom-0 inset-x-0 bg-white border-t border-gray-200 flex justify-around py-2"
             style="padding-bottom: env(safe-area-inset-bottom);">
            <a href="{{ route('trainings.index') }}" wire:navigate class="flex-1 text-center py-2 text-sm">Home</a>
            <a href="{{ route('history') }}" wire:navigate class="flex-1 text-center py-2 text-sm">History</a>
            <a href="{{ route('profile') }}" wire:navigate class="flex-1 text-center py-2 text-sm">Profile</a>
        </nav>
    </div>

    @livewireScripts

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js', { scope: '/' }));
        }
    </script>
</body>
</html>
