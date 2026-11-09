@extends('layouts.app')

@section('content')
    <div class="mb-7 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Good morning, Explorer</h1>
            <span class="mt-2 block max-w-2xl text-sm text-slate-600">Pantau koneksi dan jelajahi data Accurate dari satu ruang kerja.</span>
        </div>
        <a href="{{ route('accurate.databases') }}" class="inline-flex items-center justify-center rounded-lg bg-slate-800 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-slate-200 transition hover:bg-slate-700">Pilih database <span class="ml-2">→</span></a>
    </div>

    <div class="mb-6 grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between"><span class="text-xs font-medium uppercase tracking-wider text-slate-500">Connection</span><span class="rounded-md bg-slate-100 px-2 py-1 text-[11px] text-slate-700">{{ $configured ? 'Ready' : 'Setup' }}</span></div>
            <strong class="mt-4 block text-2xl font-bold text-slate-900">{{ $configured ? 'Connected' : 'Not connected' }}</strong>
            <span class="mt-1 block text-xs text-slate-500">Accurate API provider</span>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <span class="text-xs font-medium uppercase tracking-wider text-slate-500">Default database</span>
            <strong class="mt-4 block truncate font-mono text-2xl text-slate-900">{{ $defaultDatabaseId ?: '—' }}</strong>
            <span class="mt-1 block text-xs text-slate-500">Database aktif dari environment</span>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <span class="text-xs font-medium uppercase tracking-wider text-slate-500">API mode</span>
            <strong class="mt-4 block text-2xl text-slate-900">Read-only</strong>
            <span class="mt-1 block text-xs text-slate-500">Tidak ada data yang diubah</span>
        </div>
    </div>

    <div class="mb-6 grid gap-4 md:grid-cols-2">
        <a href="{{ route('accurate.list', ['module' => 'purchase-order']) }}" class="group rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-400 hover:shadow-md">
            <div class="mb-8 flex items-center justify-between"><span class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-xl text-slate-600">▤</span><span class="text-slate-600 transition group-hover:translate-x-1 group-hover:text-slate-900">→</span></div>
            <span class="text-xs font-medium uppercase tracking-wider text-slate-500">Module 01</span>
            <h2 class="mt-2 text-xl font-semibold text-slate-900">Purchase Orders</h2>
            <span class="mt-2 block text-sm leading-6 text-slate-600">Lihat daftar, filter, dan respons mentah purchase order.</span>
        </a>
        <a href="{{ route('accurate.list', ['module' => 'item-transfer']) }}" class="group rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-400 hover:shadow-md">
            <div class="mb-8 flex items-center justify-between"><span class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-xl text-slate-600">⇄</span><span class="text-slate-600 transition group-hover:translate-x-1 group-hover:text-slate-900">→</span></div>
            <span class="text-xs font-medium uppercase tracking-wider text-slate-500">Module 02</span>
            <h2 class="mt-2 text-xl font-semibold text-slate-900">Internal Transfers</h2>
            <span class="mt-2 block text-sm leading-6 text-slate-600">Telusuri perpindahan barang antar gudang dan statusnya.</span>
        </a>
        <a href="{{ route('accurate.inbound') }}" class="group rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-400 hover:shadow-md">
            <div class="mb-8 flex items-center justify-between"><span class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-xl text-slate-600">↓</span><span class="text-slate-600 transition group-hover:translate-x-1 group-hover:text-slate-900">→</span></div>
            <span class="text-xs font-medium uppercase tracking-wider text-slate-500">Module 03</span>
            <h2 class="mt-2 text-xl font-semibold text-slate-900">Inbound</h2>
            <span class="mt-2 block text-sm leading-6 text-slate-600">Pantau delivery berdasarkan data Purchase Order.</span>
        </a>
        <a href="{{ route('accurate.daily-inbound') }}" class="group rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-400 hover:shadow-md">
            <div class="mb-8 flex items-center justify-between"><span class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-xl text-slate-600">▥</span><span class="text-slate-600 transition group-hover:translate-x-1 group-hover:text-slate-900">→</span></div>
            <span class="text-xs font-medium uppercase tracking-wider text-slate-500">Module 04</span>
            <h2 class="mt-2 text-xl font-semibold text-slate-900">Daily Inbound</h2>
            <span class="mt-2 block text-sm leading-6 text-slate-600">Gabungkan inbound dari Purchase Order dan Internal Transfer.</span>
        </a>
    </div>

    <div class="flex items-start gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-sm text-slate-700">i</span>
        <div><h2 class="font-semibold text-slate-900">Status konfigurasi</h2>
        @if ($configured)
            <span class="mt-1 block text-sm text-slate-600">Access token tersedia. Endpoint dapat diuji.</span>
        @else
            <span class="mt-1 block text-sm text-slate-600">ACCURATE_ACCESS_TOKEN belum diatur. Tambahkan konfigurasi di file .env sebelum memanggil API.</span>
        @endif
        </div>
    </div>
@endsection
