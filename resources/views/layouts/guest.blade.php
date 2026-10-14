{{--
    Layout minimalista usado pela tela de login (e outras telas sem
    autenticação): sem header, sidebar ou menu, só o essencial pra
    renderizar o conteúdo (slot) centralizado na página.
--}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Login' }} — Intranet DPEMT</title>
    <style>
        :root {
            --focus-ring: var(--color-primary-500);
        }
    </style>
    @vite(['resources/css/app.css'])
    @livewireStyles
</head>
<body class="antialiased">
    {{ $slot }}
    @livewireScripts
</body>
</html>
