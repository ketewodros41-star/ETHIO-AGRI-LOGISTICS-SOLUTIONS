<?php

namespace Fleetbase\TeraHarvest\Http\Controllers;

use Fleetbase\TeraHarvest\Jobs\ProcessEscrowRelease;
use Fleetbase\TeraHarvest\Models\EscrowHold;
use Fleetbase\TeraHarvest\Models\PaymentEvent;
use Fleetbase\TeraHarvest\Models\PaymentTransaction;
use Fleetbase\TeraHarvest\Models\PaymentWallet;
use Fleetbase\TeraHarvest\Services\Payments\ChapaPaymentService;
use Fleetbase\TeraHarvest\Services\Payments\CBEBirrPaymentService;
use Fleetbase\TeraHarvest\Services\Payments\TelebirrPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct(
        private readonly ChapaPaymentService $chapa,
        private readonly TelebirrPaymentService $telebirr,
        private readonly CBEBirrPaymentService $cbeBirr
    ) {}

    public function initiate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|string',
            'provider' => 'required|in:chapa,telebirr,cbe_birr',
            'phone'    => 'required_unless:provider,chapa|string',
            'email'    => 'required_if:provider,chapa|email',
            'amount'   => 'required|numeric|min:1',
        ]);

        $reference = 'TH-' . Str::upper(Str::random(12));

        $result = match ($validated['provider']) {
            'chapa'    => $this->chapa->initiate($validated['amount'], $validated['email'], $validated['phone'], $reference, route('payments.webhook.chapa')),
            'telebirr' => $this->telebirr->initiate($validated['amount'], $validated['phone'], $reference),
            'cbe_birr' => $this->cbeBirr->initiate($validated['amount'], $validated['phone'], $reference),
        };

        return response()->json(['reference' => $reference, 'result' => $result]);
    }

    public function chapaWebhook(Request $request): JsonResponse
    {
        if (!$this->chapa->validateWebhook($request)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $data = $request->json()->all();
        // Find transaction by provider reference and update status
        PaymentTransaction::where('provider_reference', $data['tx_ref'] ?? '')
            ->first()
            ?->update(['status' => 'completed']);

        return response()->json(['received' => true]);
    }

    public function telebirrWebhook(Request $request): JsonResponse
    {
        // Telebirr signature validation (provider-specific)
        $data = $request->json()->all();

        PaymentTransaction::where('provider_reference', $data['outTradeNo'] ?? '')
            ->first()
            ?->update(['status' => 'completed']);

        return response()->json(['received' => true]);
    }

    public function myWallet(): JsonResponse
    {
        $wallet = PaymentWallet::where('owner_id', auth()->id())->first();
        return response()->json($wallet);
    }

    public function walletTransactions(string $walletId): JsonResponse
    {
        $wallet = PaymentWallet::findOrFail($walletId);
        $this->authorize('view', $wallet);

        return response()->json($wallet->transactions()->latest()->paginate(50));
    }

    public function escrowStatus(string $orderId): JsonResponse
    {
        $hold = EscrowHold::where('order_id', $orderId)->firstOrFail();
        return response()->json($hold);
    }

    public function releaseEscrow(string $orderId): JsonResponse
    {
        $this->authorize('admin-action');

        $hold = EscrowHold::where('order_id', $orderId)
            ->where('status', 'held')
            ->firstOrFail();

        ProcessEscrowRelease::dispatch($hold->id, 'manual_admin', auth()->id());

        return response()->json(['status' => 'release_queued']);
    }

    public function disputeEscrow(Request $request, string $orderId): JsonResponse
    {
        $validated = $request->validate(['reason' => 'required|string|max:500']);

        $hold = EscrowHold::where('order_id', $orderId)->where('status', 'held')->firstOrFail();
        $hold->update(['status' => 'disputed']);

        return response()->json($hold);
    }

    public function ledger(string $orderId): JsonResponse
    {
        $transactions = PaymentTransaction::where('order_id', $orderId)->with('events')->get();

        $events = $transactions->flatMap->events->sortBy('created_at');

        $valid = true;
        foreach ($events as $event) {
            if (!$event->verifyChainIntegrity()) {
                $valid = false;
                break;
            }
        }

        return response()->json([
            'order_id'     => $orderId,
            'chain_valid'  => $valid,
            'transactions' => $transactions,
            'events'       => $events->values(),
        ]);
    }
}
