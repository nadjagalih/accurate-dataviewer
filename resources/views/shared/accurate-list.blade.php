@extends('layouts.app')

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $title }}</h1>
            <span class="mt-2 block text-sm text-slate-600">Data read-only dari Accurate API.</span>
        </div>
        <a href="{{ route('accurate.list', ['module' => $module]) }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:border-slate-400 hover:text-slate-900">Refresh data</a>
    </div>

    @if (isset($envelope['error']))
        <div class="mb-5 rounded-lg border border-slate-200 bg-slate-100 p-4 text-sm text-slate-700">
            <strong>{{ $envelope['error']['code'] }}:</strong> {{ $envelope['error']['message'] }}
        </div>
    @endif

    <form method="GET" class="mb-5 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4 flex items-center justify-between"><div><h2 class="text-sm font-semibold text-slate-900">Filter data</h2><span class="mt-1 block text-xs text-slate-500">Persempit hasil berdasarkan parameter provider.</span></div><span class="rounded-md bg-slate-100 px-2 py-1 text-[11px] text-slate-500">QUERY</span></div>
        <div class="grid gap-3 md:grid-cols-5">
            <input name="database_id" value="{{ request('database_id') }}" placeholder="Database ID" class="rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
            <input name="date_from" type="date" value="{{ request('date_from') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
            <input name="date_to" type="date" value="{{ request('date_to') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
            <input name="status" value="{{ request('status') }}" placeholder="Status" class="rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
            <button class="rounded-lg bg-blue-600 px-3 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700">Terapkan filter</button>
        </div>
    </form>

    <div class="w-full overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="w-full min-w-[980px] table-fixed divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    @if ($module === 'item-transfer')
                        <th class="w-[11%] px-5 py-3 font-semibold">Nomor IT</th>
                        <th class="w-[11%] px-5 py-3 font-semibold">Tanggal IT</th>
                        <th class="w-[13%] px-5 py-3 font-semibold">Tipe Proses</th>
                        <th class="w-[14%] px-5 py-3 font-semibold">Gudang tujuan/dari</th>
                        <th class="w-[13%] px-5 py-3 font-semibold">Gudang</th>
                        <th class="w-[15%] px-5 py-3 font-semibold">Keterangan</th>
                        <th class="w-[13%] px-5 py-3 font-semibold">Status pengiriman</th>
                    @else
                        <th class="w-[13%] px-5 py-3 font-semibold">Nomor PO</th>
                        <th class="w-[12%] px-5 py-3 font-semibold">Tanggal PO</th>
                        <th class="w-[16%] px-5 py-3 font-semibold">Pemasok</th>
                        <th class="w-[18%] px-5 py-3 font-semibold">Keterangan</th>
                        <th class="w-[14%] px-5 py-3 font-semibold">Status</th>
                        <th class="w-[17%] px-5 py-3 text-right font-semibold">Total biaya</th>
                    @endif
                    <th class="w-[10%] px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($records as $record)
                    @php
                        $recordId = data_get($record, 'id');
                        $supplier = data_get($record, 'vendor.name') ?? data_get($record, 'vendorName') ?? data_get($record, 'supplierName');
                        $description = data_get($record, 'description') ?? data_get($record, 'comment') ?? '-';
                        $status = data_get($record, 'statusName') ?? data_get($record, 'status') ?? '-';
                        $total = data_get($record, 'totalAmount') ?? data_get($record, 'total') ?? data_get($record, 'totalCost');
                        $processType = data_get($record, 'itemTransferType.name') ?? data_get($record, 'itemTransferType') ?? '-';
                        $warehouseFromTo = data_get($record, 'referenceWarehouseName') ?? data_get($record, 'inTransitWarehouse.name') ?? data_get($record, 'inTransitWarehouseName') ?? '-';
                        $warehouse = data_get($record, 'warehouseName') ?? data_get($record, 'warehouse.name') ?? '-';
                        $deliveryStatus = data_get($record, 'itemTransferOutStatus') ?? '-';
                    @endphp
                    <tr class="align-top transition hover:bg-slate-50">
                        @if ($module === 'item-transfer')
                            <td class="break-words px-5 py-4 font-medium text-slate-900">{{ data_get($record, 'number') ?? '-' }}</td>
                            <td class="break-words px-5 py-4 text-slate-700">{{ data_get($record, 'transDate') ?? '-' }}</td>
                            <td class="break-words px-5 py-4 text-slate-700">{{ $processType }}</td>
                            <td class="break-words px-5 py-4 text-slate-700">{{ $warehouseFromTo }}</td>
                            <td class="break-words px-5 py-4 text-slate-700">{{ $warehouse }}</td>
                            <td class="break-words px-5 py-4 text-slate-700">{{ $description }}</td>
                            <td class="break-words px-5 py-4"><span class="inline-flex max-w-full rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700">{{ $deliveryStatus }}</span></td>
                        @else
                            <td class="break-words px-5 py-4 font-medium text-slate-900">{{ data_get($record, 'number') ?? data_get($record, 'no') ?? '-' }}</td>
                            <td class="break-words px-5 py-4 text-slate-700">{{ data_get($record, 'transDate') ?? data_get($record, 'date') ?? '-' }}</td>
                            <td class="break-words px-5 py-4 text-slate-700">{{ $supplier ?? '-' }}</td>
                            <td class="break-words px-5 py-4 text-slate-700">{{ $description }}</td>
                            <td class="break-words px-5 py-4"><span class="inline-flex max-w-full rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700">{{ $status }}</span></td>
                            <td class="break-words px-5 py-4 text-right font-medium text-slate-900">{{ is_numeric($total) ? number_format((float) $total, 0, ',', '.') : ($total ?? '-') }}</td>
                        @endif
                        <td class="break-words px-5 py-4 text-right">
                            @if ($recordId !== null)
                                <a href="{{ route('accurate.detail', ['module' => $module, 'id' => $recordId, 'database_id' => request('database_id')]) }}" class="font-medium text-blue-700 hover:text-blue-900">Detail</a>
                            @endif
                        </td>
                    </tr>
                    @if (isset($record['_detail_error']))
                        <tr><td colspan="8" class="bg-amber-50 px-5 py-3 text-sm text-amber-800"><strong>Detail tidak tersedia:</strong> {{ $record['_detail_error'] }}</td></tr>
                    @endif
                @empty
                    <tr><td colspan="8" class="px-5 py-8 text-center text-sm text-slate-600">Tidak ada data yang dikembalikan Accurate.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
