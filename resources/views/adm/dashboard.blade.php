@extends('layouts.adm')

@section('content')
    <div class="space-y-6">
        <section class="bg-white shadow-lg rounded-2xl p-6 border border-slate-100">
            <div class="flex flex-col gap-2">
                <p class="text-xs uppercase tracking-widest text-slate-400">Área Administrativa</p>
                <h1 class="text-3xl font-bold text-slate-900">
                    Painel do Admin, {{ auth()->user()->name ?? 'Administrador' }}
                </h1>
                <p class="text-sm text-slate-500">
                    Todas as rotas críticas estão disponíveis na coluna lateral, comece por monitorar conexões e payloads.
                </p>
            </div>
            <div class="mt-6 grid gap-4 md:grid-cols-3">
                <article class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                    <p class="text-sm text-slate-500">Usuários registrados</p>
                    <p class="text-2xl font-semibold text-slate-900">—</p>
                </article>
                <article class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                    <p class="text-sm text-slate-500">Payloads pendentes</p>
                    <p class="text-2xl font-semibold text-slate-900">—</p>
                </article>
                <article class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                    <p class="text-sm text-slate-500">Conexões vivas</p>
                    <p class="text-2xl font-semibold text-slate-900">—</p>
                </article>
            </div>
        </section>

        <section class="bg-white shadow-lg rounded-2xl p-6 border border-slate-100">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">Checklist rápido</h2>
                <span class="text-xs text-slate-400">{{ now()->format('d/m/Y H:i') }}</span>
            </div>
            <div class="mt-4 grid gap-3 md:grid-cols-2 text-sm text-slate-600">
                <p>• Verifique relatórios de conexões e exporte CSV quando necessário.</p>
                <p>• Ajuste permissões de assistentes e plataformas no menu lateral.</p>
                <p>• Monitore payloads e logs de erros na seção "Payload".</p>
                <p>• Use as funções de IA para corrigir conv_id quando requisitado.</p>
            </div>
        </section>
    </div>
@endsection
