@extends('layouts.app')

@section('content')
    @php
        $display = static function (mixed $value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            if (is_scalar($value)) {
                return (string) $value;
            }

            if (is_array($value)) {
                return (string) (data_get($value, 'name') ?? data_get($value, 'value') ?? '-');
            }

            return $value instanceof Stringable ? (string) $value : '-';
        };
    @endphp

    <div class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Daily Inbound</h1>
            <span class="mt-2 block text-sm text-slate-600">Gabungan inbound dari Purchase Order dan Internal Transfer.</span>
        </div>
        <a href="{{ route('accurate.daily-inbound', request()->query()) }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:border-slate-400 hover:text-slate-900">Refresh data</a>
    </div>

    @if (isset($envelope['error']) || isset($itEnvelope['error']))
        <div class="mb-5 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            Sebagian data tidak tersedia. Periksa API Inspector untuk detail respons provider.
        </div>
    @endif

    <form method="GET" class="mb-5 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Filter data</h2>
                <span class="mt-1 block text-xs text-slate-500">Filter diteruskan ke endpoint Purchase Order dan Internal Transfer.</span>
            </div>
            <span class="rounded-md bg-slate-100 px-2 py-1 text-[11px] text-slate-500">PO + IT</span>
        </div>
        <div class="grid gap-3 md:grid-cols-5">
            <input name="database_id" value="{{ request('database_id') }}" placeholder="Database ID" class="rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
            <input name="date_from" type="date" value="{{ request('date_from') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
            <input name="date_to" type="date" value="{{ request('date_to') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
            <input name="status" value="{{ request('status') }}" placeholder="Status" class="rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
            <button class="rounded-lg bg-blue-600 px-3 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700">Terapkan filter</button>
        </div>
    </form>

    <div class="w-full overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-[1900px] divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    @foreach (['ETA', 'NO DO/SJ', 'INBOUND NUMBER', 'Lot', 'Nama Supplier', 'SKU Code', 'SKU Name', 'Ctn', 'Pcs', 'Realisasi', 'Selisih', 'Status', 'ATA', 'REMARKS'] as $heading)
                        <th class="whitespace-nowrap px-4 py-3 font-semibold">{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($records as $record)
                    <tr class="align-top transition hover:bg-slate-50">
                        @foreach (['eta', 'no_do_sj', 'inbound_number', 'lot', 'supplier', 'sku_code', 'sku_name', 'ctn', 'pcs', 'realization', 'difference', 'status', 'ata', 'remarks'] as $field)
                            <td class="whitespace-nowrap px-4 py-4 text-slate-700">{{ $display(data_get($record, $field)) }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="14" class="px-5 py-8 text-center text-sm text-slate-600">Tidak ada data PO atau IT yang dikembalikan Accurate.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
