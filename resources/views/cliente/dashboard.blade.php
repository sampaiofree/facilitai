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
    <div class="rounded-xl bg-white p-6 shadow-md">
        <p class="text-sm text-slate-600">Dashboard do cliente pronta para receber novos modulos.</p>
    </div>
@endsection
