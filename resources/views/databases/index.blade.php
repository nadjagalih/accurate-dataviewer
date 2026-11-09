@extends('layouts.app')

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
        <div>
            <div class="mb-3 flex items-center gap-2 text-xs text-slate-500"><span>Workspace</span><span>/</span><span class="text-slate-700">Databases</span></div>
            <h1 class="text-2xl font-bold text-slate-900">Database tersedia</h1>
            <span class="mt-2 block text-sm text-slate-600">Pilih database Accurate yang ingin Anda eksplorasi.</span>
        </div>
        <a href="{{ route('accurate.databases') }}" class="rounded-lg border border-blue-200 bg-white px-3 py-2 text-sm font-medium text-blue-700 hover:border-blue-400 hover:bg-blue-50">Refresh</a>
    </div>

    @if (isset($envelope['error']))
        <div class="mb-5 rounded-lg border border-slate-200 bg-slate-100 p-4 text-sm text-slate-700">
            <strong>{{ $envelope['error']['code'] }}:</strong> {{ $envelope['error']['message'] }}
        </div>
    @endif

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4"><div><h2 class="font-semibold text-slate-900">Daftar database</h2><span class="mt-1 block text-xs text-slate-500">{{ is_countable($databases) ? count($databases) : 0 }} database ditemukan</span></div><span class="rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700">GET /db-list.do</span></div>
        <table class="w-full text-left text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                <tr><th class="px-5 py-3">Database ID</th><th class="px-5 py-3">Nama/Alias</th><th class="px-5 py-3 text-right">Action</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($databases as $database)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-4 font-mono text-xs text-slate-600">{{ data_get($database, 'id', '-') }}</td>
                        <td class="px-5 py-4 font-medium text-slate-800">{{ data_get($database, 'alias', data_get($database, 'name', '-')) }}</td>
                        <td class="px-5 py-4 text-right"><a href="{{ route('accurate.list', ['module' => 'purchase-order', 'database_id' => data_get($database, 'id')]) }}" class="text-xs font-medium text-blue-700 hover:text-blue-900">Explore →</a></td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-5 py-10 text-center text-slate-500">Tidak ada database untuk ditampilkan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
