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
                foreach (['name', 'value', 'label', 'description'] as $key) {
                    if (array_key_exists($key, $value) && is_scalar($value[$key])) {
                        return (string) $value[$key];
                    }
                }

                return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '-';
            }

            if ($value instanceof Stringable) {
                return (string) $value;
            }

            return '-';
        };
    @endphp

    <div class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Inbound (PR)</h1>
            <span class="mt-2 block text-sm text-slate-600">Plan inbound berdasarkan data Purchase Requisition.</span>
        </div>
        <a href="{{ route('accurate.inbound-pr', request()->query()) }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:border-slate-400 hover:text-slate-900">Refresh data</a>
    </div>

    @if (isset($envelope['error']))
        <div class="mb-5 rounded-lg border border-slate-200 bg-slate-100 p-4 text-sm text-slate-700">
            <strong>{{ $display($envelope['error']['code'] ?? null) }}:</strong> {{ $display($envelope['error']['message'] ?? null) }}
        </div>
    @endif

    <form method="GET" class="mb-5 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Filter data</h2>
                <span class="mt-1 block text-xs text-slate-500">Filter diteruskan ke endpoint Purchase Order.</span>
            </div>
            <span class="rounded-md bg-slate-100 px-2 py-1 text-[11px] text-slate-500">PURCHASE ORDER</span>
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
        <table class="min-w-[4200px] divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    @foreach (['TANGGAL REQ', 'WH DESTINATION', 'ITEM NAME', 'REQ QTY', 'SATUAN', 'ETA', 'NOMOR PR', 'SUPPLIER', 'NOMOR PO', 'TGL RELEASE PO SISTEM'] as $heading)
                        <th class="whitespace-nowrap px-4 py-3 font-semibold">{{ $heading }}</th>
                    @endforeach
                    @for ($deliveryNumber = 1; $deliveryNumber <= 31; $deliveryNumber++)
                        <th class="whitespace-nowrap px-4 py-3 font-semibold">TGL DELIVERY {{ $deliveryNumber }}</th>
                        <th class="whitespace-nowrap px-4 py-3 font-semibold">QTY DELIVERY {{ $deliveryNumber }}</th>
                    @endfor
                    @foreach (['QTY TERPROSES', 'QTY BELUM TERPROSES', 'STATUS', 'PIC', 'KETERANGAN', 'DELIVERY TYPE'] as $heading)
                        <th class="whitespace-nowrap px-4 py-3 font-semibold">{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($records as $record)
                    @php
                        $items = data_get($record, 'detailItem', []);
                        $items = is_array($items) && $items !== [] ? $items : [[]];
                        $histories = data_get($record, 'processHistory', []);
                        $histories = is_array($histories) ? $histories : [];
                        $deliveries = data_get($record, '_inboundPr.deliveries', []);
                        $poNumber = data_get($record, 'number');
                        $requestDate = data_get($record, '_inboundPr.requestDate')
                            ?? data_get($item, 'requiredDate');
                    @endphp
                    @foreach ($items as $item)
                        @php
                            $quantity = data_get($item, 'quantity');
                            $remainingQuantity = data_get($item, 'remainingQuantity');
                            $processedQuantity = is_numeric($quantity) && is_numeric($remainingQuantity)
                                ? (float) $quantity - (float) $remainingQuantity
                                : null;
                        @endphp
                        <tr class="align-top transition hover:bg-slate-50">
                            <td class="whitespace-nowrap px-4 py-4 text-slate-700">{{ $display($requestDate) }}</td>
                            <td class="px-4 py-4 text-slate-700">{{ $display(data_get($record, 'warehouse.0.name') ?? data_get($record, 'warehouse.name')) }}</td>
                            <td class="px-4 py-4 text-slate-700">{{ $display(data_get($item, 'detailName')) }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-slate-700">{{ $display($quantity) }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-slate-700">{{ $display(data_get($item, 'itemUnit.name')) }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-slate-700">{{ $display(data_get($record, 'shipDate')) }}</td>
                            <td class="whitespace-nowrap px-4 py-4 font-medium text-slate-900">{{ $display(data_get($record, 'purchaseRequisition.number') ?? data_get($item, 'purchaseRequisition.number')) }}</td>
                            <td class="px-4 py-4 text-slate-700">{{ $display(data_get($record, 'vendor.name')) }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-slate-700">{{ $display($poNumber) }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-slate-700">{{ $display($requestDate) }}</td>
                            @for ($deliveryNumber = 0; $deliveryNumber < 31; $deliveryNumber++)
                                @php
                                    $delivery = $deliveries[$deliveryNumber] ?? [];
                                    $deliveryQuantity = data_get($delivery, 'quantity');
                                @endphp
                                <td class="whitespace-nowrap px-4 py-4 text-slate-700">{{ $display(data_get($delivery, 'date')) }}</td>
                                <td class="whitespace-nowrap px-4 py-4 text-slate-700">{{ $display($deliveryQuantity) }}</td>
                            @endfor
                            <td class="whitespace-nowrap px-4 py-4 text-slate-700">{{ $display($processedQuantity) }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-slate-700">{{ $display($remainingQuantity) }}</td>
                            <td class="px-4 py-4 text-slate-700">{{ $display(data_get($record, 'statusName')) }}</td>
                            <td class="px-4 py-4 text-slate-700">{{ $display(data_get($record, 'createdBy')) }}</td>
                            <td class="px-4 py-4 text-slate-700">{{ $display(data_get($item, 'detailNotes')) }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-slate-700">{{ count($deliveries) === 1 ? 'Sekali kirim' : (count($deliveries) > 1 ? 'Bertahap' : '-') }}</td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="78" class="px-4 py-10 text-center text-sm text-slate-500">Tidak ada data Purchase Requisition.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
