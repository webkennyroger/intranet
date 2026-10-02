<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Painel' }} — Intranet DPEMT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css'])
    @livewireStyles
</head>
<body class="h-full font-sans antialiased bg-slate-100 flex flex-col text-slate-800" x-data="{ sidebarCollapsed: false, mobileSidebarOpen: false }">
    <x-layout.header />

    <div class="flex flex-1 overflow-hidden relative">
        <div
            x-show="mobileSidebarOpen"
            x-cloak
            @click="mobileSidebarOpen = false"
            class="fixed inset-0 bg-black/40 z-40 lg:hidden"
        ></div>

        <x-layout.sidebar />

        <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8 bg-slate-100">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html>
