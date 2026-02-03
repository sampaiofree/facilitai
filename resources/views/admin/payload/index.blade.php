@extends('layouts.adm')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Payload Uazapi</h1>
            <p class="text-sm text-slate-500">Envie manualmente um JSON para uma rota da API.</p>
        </div>
    </div>

    <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-200 max-w-3xl">
        <form method="POST" action="{{ route('adm.payload.send') }}" class="space-y-4">
            @csrf

            <div>
                <label for="apiRoute" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Rota da API</label>
                <select id="apiRoute" name="api_route" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                    <option value="/api/uazapi/messages/text" selected>/api/uazapi/messages/text</option>
                    <option value="/api/uazapi/messages/AudioMessage">/api/uazapi/messages/AudioMessage</option>
                    <option value="/api/uazapi/messages/ImageMessage">/api/uazapi/messages/ImageMessage</option>
                    <option value="/api/uazapi/messages/DocumentMessage">/api/uazapi/messages/DocumentMessage</option>
                </select>
            </div>

            <div>
                <label for="payload" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Payload JSON</label>
                <textarea id="payload" name="payload" rows="10" class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-mono text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring focus:ring-blue-200" placeholder='{"number": "5562995772922", "text": "Olá"}'></textarea>
            </div>

            <div class="flex items-center justify-between">
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                    Enviar payload
                </button>
                <p class="text-xs text-slate-400">O payload será enviado para a rota selecionada.</p>
            </div>
        </form>
    </div>
@endsection
