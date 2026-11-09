<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AccurateExplorerTest extends TestCase
{
    public function test_dashboard_is_available_without_provider_credentials(): void
    {
        config()->set('accurate.access_token', null);

        $this->get('/')
            ->assertOk()
            ->assertSee('ACCURATE_ACCESS_TOKEN belum diatur');
    }

    public function test_purchase_order_list_opens_database_and_renders_inspector_envelope(): void
    {
        config()->set([
            'accurate.access_token' => 'test-access-token',
            'accurate.signature_secret' => 'test-signature-secret',
            'accurate.default_database_id' => 'database-123',
        ]);

        Http::fake([
            'https://account.accurate.id/api/open-db.do' => Http::response([
                'host' => 'https://company.accurate.id',
                'session' => 'session-123',
            ]),
            'https://company.accurate.id/accurate/api/purchase-order/list.do*' => Http::response([
                's' => true,
                'd' => [
                    ['id' => 1, 'number' => 'PO-001'],
                ],
            ]),
            'https://company.accurate.id/accurate/api/purchase-order/detail.do*' => Http::response([
                's' => true,
                'd' => [
                    'id' => 1,
                    'number' => 'PO-001',
                    'transDate' => '05/10/2026',
                    'vendorName' => 'Vendor Lengkap',
                    'description' => 'Pembelian barang',
                    'statusName' => 'Diterima',
                    'totalAmount' => 6000000,
                    'detailItem' => [['itemName' => 'Produk']],
                ],
            ]),
        ]);

        $response = $this->get('/accurate/purchase-order/list?page=1');

        $response
            ->assertOk()
            ->assertSee('PO-001')
            ->assertSee('Vendor Lengkap')
            ->assertSee('Pembelian barang')
            ->assertSee('6.000.000')
            ->assertSee('purchase-order/list');

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://company.accurate.id/accurate/api/purchase-order/list.do?sp.page=1&sp.pageSize=20'
                && $request->header('Authorization') === ['Bearer test-access-token']
                && $request->header('X-Session-ID') === ['session-123'];
        });
    }

    public function test_provider_failure_is_rendered_as_safe_error(): void
    {
        config()->set([
            'accurate.access_token' => 'test-access-token',
            'accurate.signature_secret' => 'test-signature-secret',
        ]);

        Http::fake([
            'https://account.accurate.id/api/db-list.do' => Http::response([], 401),
        ]);

        $this->get('/accurate/databases')
            ->assertOk()
            ->assertSee('ACCURATE_UNAUTHORIZED')
            ->assertDontSee('test-access-token');
    }

    public function test_database_api_token_skips_open_database_and_uses_token_host(): void
    {
        config()->set([
            'accurate.access_token' => 'aat.test-database-token',
            'accurate.signature_secret' => 'test-signature-secret',
            'accurate.api_token_url' => 'https://zeus.accurate.id',
        ]);

        Http::fake([
            'https://zeus.accurate.id/accurate/api/purchase-order/list.do*' => Http::response([
                's' => true,
                'd' => [
                    ['id' => 1, 'number' => 'PO-API-TOKEN'],
                ],
            ]),
            'https://zeus.accurate.id/accurate/api/purchase-order/detail.do*' => Http::response([
                's' => true,
                'd' => ['id' => 1, 'number' => 'PO-API-TOKEN', 'vendorName' => 'Vendor API'],
            ]),
        ]);

        $this->get('/accurate/purchase-order/list?page=1')
            ->assertOk()
            ->assertSee('PO-API-TOKEN');

        Http::assertSent(function ($request): bool {
            return str_starts_with($request->url(), 'https://zeus.accurate.id/accurate/api/purchase-order/list.do');
        });

        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), '/api/open-db.do'));
    }

    public function test_database_api_token_follows_provider_host_redirect_with_authentication(): void
    {
        config()->set([
            'accurate.access_token' => 'aat.test-database-token',
            'accurate.signature_secret' => 'test-signature-secret',
            'accurate.api_token_url' => 'https://zeus.accurate.id',
        ]);

        Http::fake([
            'https://zeus.accurate.id/accurate/api/purchase-order/list.do*' => Http::response([
                'endpoint' => 'https://odin.accurate.id',
                's' => false,
                'd' => ['Database ini tidak tersedia pada alamat host ini'],
            ], 308),
            'https://odin.accurate.id/accurate/api/purchase-order/list.do*' => Http::response([
                's' => true,
                'd' => [['id' => 1, 'number' => 'PO-REDIRECTED']],
            ]),
            'https://odin.accurate.id/accurate/api/purchase-order/detail.do*' => Http::response([
                's' => true,
                'd' => ['id' => 1, 'number' => 'PO-REDIRECTED', 'vendorName' => 'Vendor Redirected'],
            ]),
        ]);

        $this->get('/accurate/purchase-order/list?page=1')
            ->assertOk()
            ->assertSee('PO-REDIRECTED');

        Http::assertSentCount(3);
        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), '/api/open-db.do'));
    }

    public function test_internal_transfer_renders_requested_table_columns(): void
    {
        config()->set([
            'accurate.access_token' => 'aat.test-database-token',
            'accurate.signature_secret' => 'test-signature-secret',
            'accurate.api_token_url' => 'https://odin.accurate.id',
        ]);

        Http::fake([
            'https://odin.accurate.id/accurate/api/item-transfer/list.do*' => Http::response([
                's' => true,
                'd' => [['id' => 102]],
            ]),
            'https://odin.accurate.id/accurate/api/item-transfer/detail.do*' => Http::response([
                's' => true,
                'd' => [
                    'id' => 102,
                    'number' => 'IT-001',
                    'transDate' => '05/10/2026',
                    'itemTransferType' => ['name' => 'Antar Gudang'],
                    'referenceWarehouseName' => 'Gudang Tujuan',
                    'warehouseName' => 'Gudang Asal',
                    'description' => 'Pemindahan stok',
                    'itemTransferOutStatus' => 'Sudah Dikirim',
                ],
            ]),
        ]);

        $this->get('/accurate/item-transfer/list?page=1')
            ->assertOk()
            ->assertSee('Nomor IT')
            ->assertSee('Tipe Proses')
            ->assertSee('Gudang tujuan/dari')
            ->assertSee('Sudah Dikirim')
            ->assertSee('Pemindahan stok');
    }

    public function test_inbound_uses_purchase_order_payload_and_renders_five_delivery_pairs(): void
    {
        config()->set([
            'accurate.access_token' => 'aat.test-database-token',
            'accurate.signature_secret' => 'test-signature-secret',
            'accurate.api_token_url' => 'https://odin.accurate.id',
        ]);

        Http::fake([
            'https://odin.accurate.id/accurate/api/purchase-order/list.do*' => Http::response([
                's' => true,
                'd' => [['id' => 103]],
            ]),
            'https://odin.accurate.id/accurate/api/purchase-order/detail.do*' => Http::response([
                's' => true,
                'd' => [
                    'id' => 103,
                    'number' => 'PR-INBOUND-001',
                    'transDate' => '06/10/2026',
                    'shipDate' => '07/10/2026',
                    'vendor' => ['name' => 'Vendor Inbound'],
                    'detailItem' => [[
                        'itemName' => ['name' => 'Produk Inbound'],
                        'quantity' => 2,
                        'quantityDefault' => 40,
                        'itemUnit' => ['name' => 'Karton'],
                        'unitRatio' => 20,
                        'shipQuantity' => 40,
                        'remainingQuantity' => 8,
                        'warehouse' => ['name' => 'Gudang Inbound'],
                        'purchaseRequisition' => ['id' => 301, 'number' => 'PR-INBOUND-001'],
                    ]],
                    'processHistory' => [
                        ['historyType' => 'RI', 'id' => 401, 'index' => 1, 'historyDate' => '08/10/2026'],
                        ['historyType' => 'RI', 'id' => 402, 'index' => 2, 'historyDate' => '15/10/2026'],
                    ],
                    'statusName' => 'Menunggu diproses',
                ],
            ]),
            'https://odin.accurate.id/accurate/api/purchase-requisition/detail.do*id=301*' => Http::response([
                's' => true,
                'd' => ['detailItem' => [['requiredDate' => '05/10/2026']]],
            ]),
            'https://odin.accurate.id/accurate/api/receive-item/detail.do*id=401*' => Http::response([
                's' => true,
                'd' => ['detailItem' => [['quantity' => 2]]],
            ]),
            'https://odin.accurate.id/accurate/api/receive-item/detail.do*id=402*' => Http::response([
                's' => true,
                'd' => ['detailItem' => [['quantity' => 0.5]]],
            ]),
        ]);

        $response = $this->get('/accurate/inbound?page=1');

        $response
            ->assertOk()
            ->assertSee('TANGGAL REQ')
            ->assertSee('TGL DELIVERY - 5')
            ->assertSee('QTY DELIVERY - 5')
            ->assertSee('Produk Inbound')
            ->assertSee('Karton')
            ->assertSee('Vendor Inbound')
            ->assertSee('05/10/2026')
            ->assertSee('08/10/2026')
            ->assertSee('15/10/2026')
            ->assertSee('PR-INBOUND-001')
            ->assertSee('Gudang Inbound')
            ->assertSee('2')
            ->assertSee('1.6')
            ->assertSee('0.4')
            ->assertSee('0.5')
            ->assertSee('Bertahap');

        $this->assertSame(2, substr_count($response->getContent(), '>2</td>'));

        Http::assertSent(function ($request): bool {
            return str_starts_with($request->url(), 'https://odin.accurate.id/accurate/api/purchase-order/list.do');
        });
    }

    public function test_inbound_pr_uses_purchase_order_and_receive_item_mapping(): void
    {
        config()->set([
            'accurate.access_token' => 'aat.test-database-token',
            'accurate.signature_secret' => 'test-signature-secret',
            'accurate.api_token_url' => 'https://odin.accurate.id',
        ]);

        Http::fake([
            'https://odin.accurate.id/accurate/api/purchase-order/list.do*' => Http::response([
                's' => true,
                'd' => [['id' => 104]],
            ]),
            'https://odin.accurate.id/accurate/api/purchase-order/detail.do*' => Http::response([
                's' => true,
                'd' => [
                    'id' => 104,
                    'number' => 'PO-001',
                    'shipDate' => '15/10/2026',
                    'purchaseRequisition' => ['id' => 105, 'number' => 'PR-001'],
                    'warehouse' => [['name' => 'Gudang Tujuan']],
                    'vendor' => ['name' => 'Supplier PO'],
                    'detailItem' => [[
                        'detailName' => 'Produk PR',
                        'quantity' => 12,
                        'itemUnit' => ['name' => 'PCS'],
                        'remainingQuantity' => 5,
                        'detailNotes' => 'Kirim pagi',
                    ]],
                    'processHistory' => [
                        ['historyType' => 'RI', 'id' => 201, 'index' => 2, 'historyDate' => '20/10/2026'],
                        ['historyType' => 'RI', 'id' => 200, 'index' => 1, 'historyDate' => '16/10/2026'],
                    ],
                    'statusName' => 'Sebagian diterima',
                    'createdBy' => 'PIC PR',
                ],
            ]),
            'https://odin.accurate.id/accurate/api/purchase-requisition/detail.do*' => Http::response([
                's' => true,
                'd' => ['detailItem' => [['requiredDate' => '08/10/2026']]],
            ]),
            'https://odin.accurate.id/accurate/api/receive-item/detail.do*id=200*' => Http::response([
                's' => true,
                'd' => ['detailItem' => [['quantity' => 3]]],
            ]),
            'https://odin.accurate.id/accurate/api/receive-item/detail.do*id=201*' => Http::response([
                's' => true,
                'd' => ['detailItem' => [['quantity' => 4]]],
            ]),
        ]);

        $this->get('/accurate/inbound-pr?page=1')
            ->assertOk()
            ->assertSee('Inbound (PR)')
            ->assertSee('TGL DELIVERY 31')
            ->assertSee('QTY DELIVERY 31')
            ->assertSee('08/10/2026')
            ->assertSee('Gudang Tujuan')
            ->assertSee('Produk PR')
            ->assertSee('12')
            ->assertSee('PCS')
            ->assertSee('15/10/2026')
            ->assertSee('PR-001')
            ->assertSee('Supplier PO')
            ->assertSee('16/10/2026')
            ->assertSee('20/10/2026')
            ->assertSee('3')
            ->assertSee('4')
            ->assertSee('7')
            ->assertSee('5')
            ->assertSee('Sebagian diterima')
            ->assertSee('PIC PR')
            ->assertSee('Kirim pagi')
            ->assertSee('Bertahap');

        Http::assertSent(function ($request): bool {
            return str_starts_with($request->url(), 'https://odin.accurate.id/accurate/api/purchase-order/list.do');
        });
    }

    public function test_daily_inbound_combines_purchase_orders_and_item_transfers(): void
    {
        config()->set([
            'accurate.access_token' => 'aat.test-database-token',
            'accurate.signature_secret' => 'test-signature-secret',
            'accurate.api_token_url' => 'https://odin.accurate.id',
        ]);

        Http::fake([
            'https://odin.accurate.id/accurate/api/purchase-order/list.do*' => Http::response([
                's' => true,
                'd' => [['id' => 501]],
            ]),
            'https://odin.accurate.id/accurate/api/purchase-order/detail.do*' => Http::response([
                's' => true,
                'd' => [
                    'id' => 501,
                    'number' => 'PO-DAILY-001',
                    'shipDate' => '22/10/2026',
                    'vendorName' => 'Supplier PO',
                    'detailItem' => [[
                        'itemCode' => 'SKU-PO',
                        'detailName' => 'SKU Purchase Order',
                        'quantity' => 10,
                        'quantityDefault' => 10,
                    ]],
                    'processHistory' => [
                        ['historyType' => 'RI', 'id' => 601, 'index' => 1, 'historyDate' => '23/10/2026'],
                    ],
                ],
            ]),
            'https://odin.accurate.id/accurate/api/receive-item/detail.do*' => Http::response([
                's' => true,
                'd' => [
                    'number' => 'RI-DAILY-001',
                    'detailItem' => [['quantity' => 8]],
                ],
            ]),
            'https://odin.accurate.id/accurate/api/item-transfer/list.do*' => Http::response([
                's' => true,
                'd' => [['id' => 502]],
            ]),
            'https://odin.accurate.id/accurate/api/item-transfer/detail.do*' => Http::response([
                's' => true,
                'd' => [
                    'id' => 502,
                    'number' => 'IT-KIRIM-001',
                    'receiveHistory' => [
                        ['historyNumber' => 'IT-TERIMA-001'],
                    ],
                    'transDate' => '24/10/2026',
                    'detailItem' => [[
                        'itemCode' => 'SKU-IT',
                        'detailName' => 'SKU Internal Transfer',
                        'quantity' => 5,
                        'quantityDefault' => 5,
                    ]],
                    'statusName' => 'Selesai',
                ],
            ]),
        ]);

        $this->get('/accurate/daily-inbound?page=1')
            ->assertOk()
            ->assertSee('NO DO/SJ')
            ->assertSee('INBOUND NUMBER')
            ->assertSee('SKU Purchase Order')
            ->assertSee('PO-DAILY-001')
            ->assertSee('RI-DAILY-001')
            ->assertSee('SKU Internal Transfer')
            ->assertSee('IT-KIRIM-001')
            ->assertSee('IT-TERIMA-001');
    }
}
