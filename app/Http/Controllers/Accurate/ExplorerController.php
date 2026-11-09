<?php

namespace App\Http\Controllers\Accurate;

use App\Http\Controllers\Controller;
use App\Http\Requests\AccurateListRequest;
use App\Services\Accurate\AccurateClient;
use App\Services\Accurate\AccurateException;
use App\Services\Accurate\ResponseEnvelope;

class ExplorerController extends Controller
{
    public function __construct(private readonly AccurateClient $client) {}

    public function dashboard()
    {
        return view('dashboard', [
            'configured' => (bool) config('accurate.access_token'),
            'defaultDatabaseId' => config('accurate.default_database_id'),
        ]);
    }

    public function databases()
    {
        $endpoint = '/accurate/databases';

        try {
            $response = $this->client->databases();
            $envelope = ResponseEnvelope::success($response, $endpoint);
        } catch (AccurateException $exception) {
            $envelope = ResponseEnvelope::error($exception, $endpoint);
        }

        $databases = data_get($envelope, 'data.d') ?? data_get($envelope, 'data', []);

        return view('databases.index', [
            'envelope' => $envelope,
            'databases' => is_iterable($databases) ? $databases : [],
        ]);
    }

    public function list(AccurateListRequest $request, string $module)
    {
        $this->ensureModule($module);
        [$envelope, $records] = $this->fetchRecords($request, $module);

        return view("{$module}.index", [
            'envelope' => $envelope,
            'records' => $records,
            'module' => $module,
        ]);
    }

    public function inbound(AccurateListRequest $request)
    {
        [$envelope, $records] = $this->fetchRecords($request, 'purchase-order');
        $records = $this->enrichInboundPrRecords($records, $request->input('database_id'));

        return view('inbound.index', [
            'envelope' => $envelope,
            'records' => $records,
        ]);
    }

    public function dailyInbound(AccurateListRequest $request)
    {
        [$poEnvelope, $purchaseOrders] = $this->fetchRecords($request, 'purchase-order');
        [$itEnvelope, $itemTransfers] = $this->fetchRecords($request, 'item-transfer');

        $purchaseOrders = $this->enrichInboundPrRecords($purchaseOrders, $request->input('database_id'));

        return view('daily-inbound.index', [
            'envelope' => $poEnvelope,
            'poEnvelope' => $poEnvelope,
            'itEnvelope' => $itEnvelope,
            'records' => array_merge(
                $this->buildDailyInboundRows($purchaseOrders, 'purchase-order'),
                $this->buildDailyInboundRows($itemTransfers, 'item-transfer'),
            ),
        ]);
    }

    public function inboundPr(AccurateListRequest $request)
    {
        [$envelope, $records] = $this->fetchRecords($request, 'purchase-order');
        $records = $this->enrichInboundPrRecords($records, $request->input('database_id'));

        return view('inbound-pr.index', [
            'envelope' => $envelope,
            'records' => $records,
        ]);
    }

    public function detail(AccurateListRequest $request, string $module)
    {
        $this->ensureModule($module);
        $query = $request->providerQuery();
        $endpoint = "/accurate/{$module}/detail";

        try {
            $response = $this->client->business(
                $module,
                'detail',
                $query,
                $request->input('database_id'),
            );
            $envelope = ResponseEnvelope::success($response, $endpoint, $query);
        } catch (AccurateException $exception) {
            $envelope = ResponseEnvelope::error($exception, $endpoint, $query);
        }

        $record = data_get($envelope, 'data.d') ?? data_get($envelope, 'data', []);

        return view("{$module}.show", [
            'envelope' => $envelope,
            'record' => $record ?? [],
            'module' => $module,
        ]);
    }

    private function ensureModule(string $module): void
    {
        abort_unless(array_key_exists($module, config('accurate.endpoints', [])), 404);
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<int, array<string, mixed>>}
     */
    private function fetchRecords(AccurateListRequest $request, string $module): array
    {
        $query = $request->providerQuery();
        $endpoint = "/accurate/{$module}/list";

        try {
            $response = $this->client->business(
                $module,
                'list',
                $query,
                $request->input('database_id'),
            );
            $envelope = ResponseEnvelope::success($response, $endpoint, $query);
        } catch (AccurateException $exception) {
            $envelope = ResponseEnvelope::error($exception, $endpoint, $query);
        }

        $records = data_get($envelope, 'data.d') ?? data_get($envelope, 'data', []);
        $records = is_iterable($records)
            ? $this->loadCompleteRecords($module, $records, $request->input('database_id'))
            : [];

        return [$envelope, $records];
    }

    /**
     * @param  iterable<mixed>  $records
     * @return array<int, array<string, mixed>>
     */
    private function loadCompleteRecords(string $module, iterable $records, ?string $databaseId): array
    {
        $completeRecords = [];

        foreach ($records as $record) {
            if (! is_array($record)) {
                continue;
            }

            $recordId = data_get($record, 'id');

            if ($recordId === null || $recordId === '') {
                $completeRecords[] = $record;

                continue;
            }

            try {
                $detail = $this->client->business(
                    $module,
                    'detail',
                    ['id' => (string) $recordId],
                    $databaseId,
                );
                $detailRecord = data_get($detail->body, 'd') ?? $detail->body;

                $completeRecords[] = is_array($detailRecord)
                    ? array_replace(['id' => $recordId], $detailRecord)
                    : $record;
            } catch (AccurateException $exception) {
                $completeRecords[] = array_replace($record, [
                    '_detail_error' => $exception->getMessage(),
                ]);
            }
        }

        return $completeRecords;
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @return array<int, array<string, mixed>>
     */
    private function enrichInboundPrRecords(array $records, ?string $databaseId): array
    {
        foreach ($records as &$record) {
            $record['_inboundPr'] = [
                'requestDate' => null,
                'deliveries' => [],
            ];

            $prId = data_get($record, 'purchaseRequisition.id')
                ?? data_get($record, 'purchaseRequisitionId')
                ?? data_get($record, 'prId')
                ?? data_get($record, 'detailItem.0.purchaseRequisition.id');

            if ($prId !== null && $prId !== '') {
                try {
                    $prResponse = $this->client->business(
                        'purchase-requisition',
                        'detail',
                        ['id' => (string) $prId],
                        $databaseId,
                    );
                    $prRecord = data_get($prResponse->body, 'd') ?? $prResponse->body;
                    $requiredDate = data_get($prRecord, 'detailItem.0.requiredDate');

                    if ($requiredDate !== null && $requiredDate !== '') {
                        $record['_inboundPr']['requestDate'] = $requiredDate;
                    }
                } catch (AccurateException $exception) {
                    $record['_inboundPr']['prDetailError'] = $exception->getMessage();
                }
            }

            $histories = data_get($record, 'processHistory', []);

            if (! is_array($histories)) {
                continue;
            }

            foreach ($histories as $history) {
                if (data_get($history, 'historyType') !== 'RI') {
                    continue;
                }

                $historyId = data_get($history, 'id') ?? data_get($history, 'historyId');
                $quantity = null;
                $number = null;

                if ($historyId !== null && $historyId !== '') {
                    try {
                        $riResponse = $this->client->business(
                            'receive-item',
                            'detail',
                            ['id' => (string) $historyId],
                            $databaseId,
                        );
                        $riRecord = data_get($riResponse->body, 'd') ?? $riResponse->body;
                        $quantity = data_get($riRecord, 'detailItem.0.quantity');
                        $number = data_get($riRecord, 'number')
                            ?? data_get($riRecord, 'no')
                            ?? data_get($riRecord, 'documentNumber');
                    } catch (AccurateException $exception) {
                        $quantity = null;
                        $number = null;
                    }
                }

                $record['_inboundPr']['deliveries'][] = [
                    'index' => data_get($history, 'index', PHP_INT_MAX),
                    'date' => data_get($history, 'historyDate'),
                    'quantity' => $quantity,
                    'number' => $number ?? data_get($history, 'number') ?? data_get($history, 'documentNumber'),
                ];
            }

            usort(
                $record['_inboundPr']['deliveries'],
                static fn (array $left, array $right): int => $left['index'] <=> $right['index'],
            );
        }

        unset($record);

        return $records;
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @return array<int, array<string, mixed>>
     */
    private function buildDailyInboundRows(array $records, string $source): array
    {
        $rows = [];

        foreach ($records as $record) {
            $items = data_get($record, 'detailItem', []);
            $items = is_array($items) && $items !== [] ? $items : [[]];
            $deliveries = data_get($record, '_inboundPr.deliveries', []);
            $deliveries = is_array($deliveries) ? $deliveries : [];
            $firstDelivery = $deliveries[0] ?? [];
            $itemTransferOutgoingNumber = $this->firstNonEmpty($record, [
                'itemTransferOutNumber',
                'transferOutNumber',
                'outboundNumber',
                'sendNumber',
            ]);
            $itemTransferIncomingNumber = $this->receiveHistoryNumber($record) ?? $this->firstNonEmpty($record, [
                'itemTransferInNumber',
                'transferInNumber',
                'inboundNumber',
                'receiveNumber',
            ]);

            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $quantity = data_get($item, 'quantity') ?? data_get($item, 'quantityDefault');
                $remainingQuantity = data_get($item, 'remainingQuantity');
                $realizedQuantity = $source === 'purchase-order'
                    ? $this->sumDeliveryQuantities($deliveries)
                    : data_get($item, 'shipQuantity') ?? $quantity;
                $difference = is_numeric($quantity) && is_numeric($realizedQuantity)
                    ? (float) $quantity - (float) $realizedQuantity
                    : (is_numeric($remainingQuantity) ? $remainingQuantity : null);
                $inboundNumber = $source === 'purchase-order'
                    ? data_get($firstDelivery, 'number')
                    : ($itemTransferIncomingNumber ?? $this->historyNumber($record, ['receive', 'in']));
                $noDoSj = $source === 'purchase-order'
                    ? data_get($record, 'number') ?? data_get($record, 'no')
                    : ($itemTransferOutgoingNumber ?? $this->historyNumber($record, ['send', 'out']) ?? data_get($record, 'number'));

                $rows[] = [
                    'eta' => data_get($record, 'shipDateView') ?? data_get($record, 'shipDate') ?? data_get($record, 'transDate'),
                    'no_do_sj' => $noDoSj,
                    'inbound_number' => $inboundNumber,
                    'lot' => data_get($item, 'lotNumber') ?? data_get($item, 'lot'),
                    'supplier' => data_get($record, 'vendor.name') ?? data_get($record, 'vendorName'),
                    'sku_code' => data_get($item, 'item.no') ?? data_get($item, 'itemNo') ?? data_get($item, 'itemCode'),
                    'sku_name' => data_get($item, 'detailName') ?? data_get($item, 'item.name') ?? data_get($item, 'itemName'),
                    'ctn' => data_get($item, 'quantity'),
                    'pcs' => data_get($item, 'quantityDefault') ?? data_get($item, 'shipQuantity'),
                    'realization' => $realizedQuantity,
                    'difference' => $difference,
                    'status' => data_get($record, 'statusName') ?? data_get($record, 'status'),
                    'ata' => data_get($firstDelivery, 'date') ?? data_get($record, 'transDate'),
                    'remarks' => data_get($item, 'detailNotes') ?? data_get($record, 'description') ?? data_get($record, 'comment'),
                ];
            }
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $deliveries
     */
    private function sumDeliveryQuantities(array $deliveries): mixed
    {
        $quantities = array_filter(
            array_map(static fn (mixed $delivery): mixed => is_array($delivery) ? data_get($delivery, 'quantity') : null, $deliveries),
            static fn (mixed $quantity): bool => is_numeric($quantity),
        );

        return $quantities === [] ? null : array_sum(array_map(static fn (mixed $quantity): float => (float) $quantity, $quantities));
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  array<int, string>  $keywords
     */
    private function historyNumber(array $record, array $keywords): mixed
    {
        $histories = data_get($record, 'processHistory', []);

        if (! is_array($histories)) {
            return null;
        }

        foreach ($histories as $history) {
            $historyType = strtolower((string) data_get($history, 'historyType'));

            foreach ($keywords as $keyword) {
                if (str_contains($historyType, $keyword)) {
                    return data_get($history, 'number')
                        ?? data_get($history, 'documentNumber')
                        ?? data_get($history, 'no');
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  array<int, string>  $paths
     */
    private function firstNonEmpty(array $record, array $paths): mixed
    {
        foreach ($paths as $path) {
            $value = data_get($record, $path);

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function receiveHistoryNumber(array $record): mixed
    {
        $receiveHistory = data_get($record, 'receiveHistory', []);

        if (! is_array($receiveHistory)) {
            return null;
        }

        foreach ($receiveHistory as $history) {
            $historyNumber = is_array($history) ? data_get($history, 'historyNumber') : null;

            if ($historyNumber !== null && $historyNumber !== '') {
                return $historyNumber;
            }
        }

        return null;
    }
}
