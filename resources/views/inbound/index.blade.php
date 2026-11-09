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
            <h1 class="text-2xl font-bold text-slate-900">Inbound</h1>
            <span class="mt-2 block text-sm text-slate-600">Data inbound berdasarkan payload Purchase Order.</span>
        </div>
        <a href="{{ route('accurate.inbound', request()->query()) }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:border-slate-400 hover:text-slate-900">Refresh data</a>
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
        <table class="min-w-[3000px] divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    @foreach (['TANGGAL REQ', 'WH DESTINATION', 'ITEM NAME', 'REQ QTY', 'SATUAN', 'ETA', 'NOMOR PR', 'SUPPLIER', 'PO MANUAL/SISTEM', 'TGL RELEASE PO MANUAL', 'NOMOR PO', 'TGL RELEASE PO SISTEM', 'DELIVERY TYPE'] as $heading)
                        <th class="whitespace-nowrap px-4 py-3 font-semibold">{{ $heading }}</th>
                    @endforeach
                    @for ($deliveryNumber = 1; $deliveryNumber <= 5; $deliveryNumber++)
                        <th class="whitespace-nowrap px-4 py-3 font-semibold">TGL DELIVERY - {{ $deliveryNumber }}</th>
                        <th class="whitespace-nowrap px-4 py-3 font-semibold">QTY DELIVERY - {{ $deliveryNumber }}</th>
                    @endfor
                    <th class="whitespace-nowrap px-4 py-3 font-semibold">QTY TERPROSES</th>
                    <th class="whitespace-nowrap px-4 py-3 font-semibold">QTY BELUM TERPROSES</th>
                    <th class="whitespace-nowrap px-4 py-3 font-semibold">STATUS</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($records as $record)
                    @php
                        $items = data_get($record, 'detailItem', []);
                        $item = is_array($items) && is_array($items[0] ?? null) ? $items[0] : [];
                        $unitRatio = data_get($item, 'unitRatio');
                        $normaliseQuantity = static fn (mixed $quantity): mixed => is_numeric($quantity)
                            && is_numeric($unitRatio)
                            && (float) $unitRatio > 0
                            ? (float) $quantity / (float) $unitRatio
                            : $quantity;
                        $deliveries = data_get($record, '_inboundPr.deliveries', []);
                        $deliveries = is_array($deliveries) ? $deliveries : [];
                        $deliveryCount = count($deliveries);
                        $deliveryDate = static fn (int $number): mixed => data_get($deliveries, ($number - 1).'.date')
                            ?? ($number === 1 ? data_get($record, 'shipDateView') ?? data_get($record, 'shipDate') : null)
                            ?? data_get($record, "deliveryDate{$number}");
                        $deliveryQuantity = static fn (int $number): mixed => data_get($deliveries, ($number - 1).'.quantity')
                            ?? data_get($record, "deliveryQuantity{$number}");
                        $processedQuantity = is_numeric(data_get($item, 'quantity'))
                            && is_numeric(data_get($item, 'remainingQuantity'))
                            ? (float) data_get($item, 'quantity') - $normaliseQuantity(data_get($item, 'remainingQuantity'))
                            : data_get($record, 'processedQuantity') ?? data_get($record, 'qtyProcessed');
                        $unprocessedQuantity = $normaliseQuantity(data_get($item, 'remainingQuantity'))
                            ?? data_get($record, 'unprocessedQuantity')
                            ?? data_get($record, 'qtyUnprocessed');
                    @endphp
                    <tr class="align-top transition hover:bg-slate-50">
                        <td class="whitespace-nowrap px-4 py-4 text-slate-700">{{ $display(data_get($record, '_inboundPr.requestDate') ?? data_get($record, 'transDateView') ?? data_get($record, 'transDate')) }}</td>
                        <td class="px-4 py-4 text-slate-700">{{ $display(data_get($record, 'warehouseName') ?? data_get($item, 'warehouse.name') ?? data_get($record, 'warehouse.name')) }}</td>
                        <td class="px-4 py-4 text-slate-700">{{ $display(data_get($item, 'detailName') ?? data_get($item, 'item.name') ?? data_get($item, 'itemName') ?? data_get($record, 'itemName')) }}</td>
                        <td class="whitespace-nowrap px-4 py-4 text-slate-700">{{ $display(data_get($item, 'quantity') ?? data_get($record, 'quantity') ?? data_get($item, 'quantityDefault') ?? data_get($record, 'quantityDefault')) }}</td>
                        <td class="whitespace-nowrap px-4 py-4 text-slate-700">{{ $display(data_get($item, 'itemUnit.name') ?? data_get($item, 'itemUnit') ?? data_get($item, 'unit')) }}</td>
                        <td class="whitespace-nowrap px-4 py-4 text-slate-700">{{ $display(data_get($record, 'shipDateView') ?? data_get($record, 'shipDate')) }}</td>
                        <td class="px-4 py-4 text-slate-700">{{ $display(data_get($record, 'prNumber') ?? data_get($record, 'purchaseRequestNumber') ?? data_get($item, 'purchaseRequisition.number')) }}</td>
                        <td class="px-4 py-4 text-slate-700">{{ $display(data_get($record, 'vendor.name') ?? data_get($record, 'vendorName')) }}</td>
                        <td class="px-4 py-4 text-slate-700">Sistem</td>
                        <td class="whitespace-nowrap px-4 py-4 text-slate-700">-</td>
                        <td class="whitespace-nowrap px-4 py-4 font-medium text-slate-900">{{ $display(data_get($record, 'number') ?? data_get($record, 'no')) }}</td>
                        <td class="whitespace-nowrap px-4 py-4 text-slate-700">{{ $display(data_get($record, 'transDateView') ?? data_get($record, 'transDate')) }}</td>
                        <td class="px-4 py-4 text-slate-700">{{ $deliveryCount === 1 ? 'Sekali kirim' : ($deliveryCount > 1 ? 'Bertahap' : null) }}</td>
                        @for ($deliveryNumber = 1; $deliveryNumber <= 5; $deliveryNumber++)
                            <td class="whitespace-nowrap px-4 py-4 text-slate-700">{{ $display($deliveryDate($deliveryNumber)) }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-slate-700">{{ $display($deliveryQuantity($deliveryNumber)) }}</td>
                        @endfor
                        <td class="whitespace-nowrap px-4 py-4 text-slate-700">{{ $display($processedQuantity) }}</td>
                        <td class="whitespace-nowrap px-4 py-4 text-slate-700">{{ $display($unprocessedQuantity) }}</td>
                        <td class="px-4 py-4"><span class="inline-flex rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700">{{ $display(data_get($record, 'statusName') ?? data_get($record, 'status')) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="26" class="px-5 py-8 text-center text-sm text-slate-600">Tidak ada data Purchase Order yang dikembalikan Accurate.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
