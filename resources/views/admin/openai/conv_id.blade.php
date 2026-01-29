@extends('layouts.adm')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">OpenAI / Conversação</h2>
            <p class="text-sm text-slate-500">Busque itens de conversa usando o conv_id presente em AssistantLead.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('adm.openai.conv_id') }}" class="mb-6 space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div>
            <label for="convId" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Conv ID</label>
            <input id="convId" name="conv_id" type="text" value="{{ old('conv_id', $convId ?? '') }}" required class="mt-1 w-full rounded-lg border border-slate-200 px-4 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
        </div>
        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Buscar</button>
            @if($convId && !$error)
                <span class="text-xs text-emerald-600">Pesquisa realizada com conv_id {{ $convId }}</span>
            @endif
        </div>
    </form>

    @if($error)
        <div class="mb-6 rounded-lg border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ $error }}
        </div>
    @endif

    @if($result)
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-500">JSON retornado</h3>
            <pre class="mt-3 max-h-[650px] overflow-auto rounded-lg bg-slate-900/80 p-4 text-xs text-slate-50 whitespace-pre-wrap break-words">
{{ json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}
            </pre>
        </div>
    @endif
@endsection
