<?php

namespace Fleetbase\TeraHarvest\Tests\Feature;

use Fleetbase\TeraHarvest\Models\PaymentTransaction;
use Fleetbase\TeraHarvest\Models\PaymentWallet;
use Fleetbase\TeraHarvest\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class PaymentLedgerTest extends TestCase
{
    use RefreshDatabase;

    private PaymentWallet      $wallet;
    private PaymentTransaction $transaction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wallet = PaymentWallet::create([
            'uuid'         => (string) Str::uuid(),
            'company_id'   => 'test-co',
            'owner_id'     => 'user-1',
            'owner_type'   => 'user',
            'balance_etb'  => '1000.00',
            'reserved_etb' => '0.00',
            'currency'     => 'ETB',
            'status'       => 'active',
        ]);

        $this->transaction = PaymentTransaction::create([
            'uuid'      => (string) Str::uuid(),
            'wallet_id' => $this->wallet->id,
            'order_id'  => 'order-1',
            'type'      => 'escrow_hold',
            'amount_etb'=> '500.00',
            'fee_etb'   => '0.00',
            'provider'  => 'internal',
            'status'    => 'completed',
        ]);
    }

    public function test_payment_event_chain_is_valid(): void
    {
        $this->transaction->appendEvent('escrow_hold_created', ['amount' => '500.00', 'order_id' => 'order-1']);
        $this->transaction->appendEvent('escrow_released',     ['amount' => '490.00', 'trigger' => 'delivery_confirmed']);

        $events = $this->transaction->events()->orderBy('created_at')->get();
        $this->assertCount(2, $events);

        foreach ($events as $event) {
            $this->assertTrue($event->verifyChainIntegrity(), "Chain integrity check failed for event: {$event->event_type}");
        }
    }

    public function test_tampered_event_fails_integrity(): void
    {
        $this->transaction->appendEvent('deposit', ['amount' => '100.00']);

        $event = $this->transaction->events()->first();

        // Tamper the payload in DB
        $event->payload = ['amount' => '9999.00'];
        $this->assertFalse($event->verifyChainIntegrity());
    }

    public function test_wallet_available_balance_uses_bcmath(): void
    {
        $wallet = PaymentWallet::create([
            'uuid'         => (string) Str::uuid(),
            'company_id'   => 'test-co',
            'owner_id'     => 'user-2',
            'owner_type'   => 'user',
            'balance_etb'  => '1000.00',
            'reserved_etb' => '300.00',
            'currency'     => 'ETB',
            'status'       => 'active',
        ]);

        $this->assertEquals('700.00', $wallet->availableBalance());
    }

    public function test_payment_event_has_no_updated_at(): void
    {
        $this->transaction->appendEvent('test', ['x' => 1]);
        $event = $this->transaction->events()->first();

        $columns = \Illuminate\Support\Facades\Schema::getColumnListing('payment_events');
        $this->assertNotContains('updated_at', $columns);
    }
}
