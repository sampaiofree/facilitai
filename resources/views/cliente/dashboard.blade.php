@extends('layouts.cliente')

@section('title', 'Dashboard Cliente')

@section('header')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-lg font-semibold text-slate-900">Bem-vindo, {{ auth('client')->user()->nome ?? 'Cliente' }}</h1>
            <p class="text-sm text-slate-500">Area exclusiva do cliente.</p>
        </div>
        <form method="POST" action="{{ route('cliente.logout') }}">
            @csrf
            <button type="submit" class="rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">Sair</button>
        </form>
    </div>
@endsection

@section('content')
    <div class="grid gap-4 md:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Conexões</div>
            <div class="mt-2 text-3xl font-semibold text-slate-900">{{ $conexoesCount ?? 0 }}</div>
            <p class="mt-1 text-sm text-slate-500">Conexões ativas registradas.</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Conversas</div>
            <div class="mt-2 text-3xl font-semibold text-slate-900">{{ $conversasCount ?? 0 }}</div>
            <p class="mt-1 text-sm text-slate-500">Leads cadastrados no seu painel.</p>
        </div>
    </div>
@endsection
