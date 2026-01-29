<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard Cliente</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-900">
    <div class="min-h-screen p-6">
        <div class="max-w-4xl mx-auto bg-white shadow-md rounded-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold">Bem-vindo, {{ auth('client')->user()->nome ?? 'Cliente' }}</h1>
                    <p class="text-sm text-gray-500">Área exclusiva do cliente.</p>
                </div>
                <form method="POST" action="{{ route('cliente.logout') }}">
                    @csrf
                    <button type="submit" class="rounded-md bg-gray-800 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-900">Sair</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
