<?php

namespace Fleetbase\TeraHarvest\Services;

use Fleetbase\TeraHarvest\Models\ForwardContract;
use Fleetbase\TeraHarvest\Models\ContractFulfilment;
use Fleetbase\TeraHarvest\Models\PaymentWallet;
use Fleetbase\TeraHarvest\Models\EscrowHold;
use Illuminate\Support\Facades\DB;

class ForwardContractService
{
    public function create(array $data): ForwardContract
    {
        $totalValue  = bcmul((string) $data['quantity_kg'], (string) $data['price_per_kg_etb'], 2);
        $depositPct  = $data['deposit_pct'] ?? '10.00';
        $depositEtb  = bcmul($totalValue, bcdiv($depositPct, '100', 6), 2);

        return ForwardContract::create(array_merge($data, [
            'total_value_etb' => $totalValue,
            'deposit_pct'     => $depositPct,
            'deposit_etb'     => $depositEtb,
            'status'          => 'draft',
        ]));
    }

    public function processDeposit(ForwardContract $contract, string $transactionId): void
    {
        DB::transaction(function () use ($contract, $transactionId) {
            $wallet = PaymentWallet::where('owner_id', $contract->buyer_id)
                                   ->where('company_id', $contract->company_id)
                                   ->lockForUpdate()
                                   ->firstOrFail();

            $balance = (string) $wallet->balance;
            if (bccomp($balance, (string) $contract->deposit_etb, 2) < 0) {
                throw new \RuntimeException('Insufficient wallet balance for contract deposit.');
            }

            $wallet->decrement('balance', (float) $contract->deposit_etb);

            EscrowHold::create([
                'company_id'      => $contract->company_id,
                'payer_id'        => $contract->buyer_id,
                'payee_id'        => null,
                'amount'          => $contract->deposit_etb,
                'currency'        => 'ETB',
                'reference_type'  => 'forward_contract',
                'reference_id'    => $contract->id,
                'status'          => 'held',
            ]);

            $contract->update([
                'deposit_transaction_id' => $transactionId,
                'deposit_status'         => 'paid',
                'status'                 => 'active',
            ]);
        });
    }

    public function recordFulfilment(ForwardContract $contract, array $data): ContractFulfilment
    {
        return DB::transaction(function () use ($contract, $data) {
            $quantityKg = $data['quantity_kg'];
            $remaining  = $contract->remainingKg();

            if (bccomp((string) $quantityKg, $remaining, 3) > 0) {
                throw new \RuntimeException('Fulfilment quantity exceeds contract remaining balance.');
            }

            $total = bcmul((string) $quantityKg, (string) $data['price_per_kg_etb'], 2);
            $fulfilment = ContractFulfilment::create(array_merge($data, [
                'contract_id' => $contract->id,
                'total_etb'   => $total,
                'status'      => 'pending',
            ]));

            $newFulfilledKg = bcadd((string) $contract->fulfilled_kg, (string) $quantityKg, 3);
            $newStatus      = bccomp($newFulfilledKg, (string) $contract->quantity_kg, 3) >= 0
                ? 'fulfilled'
                : 'partially_fulfilled';

            $contract->update(['fulfilled_kg' => $newFulfilledKg, 'status' => $newStatus]);
            return $fulfilment;
        });
    }

    public function cancel(ForwardContract $contract, string $reason, bool $refundDeposit = false): void
    {
        DB::transaction(function () use ($contract, $reason, $refundDeposit) {
            if (!in_array($contract->status, ['draft', 'active', 'partially_fulfilled'])) {
                throw new \RuntimeException('Contract cannot be cancelled in its current status.');
            }

            if ($refundDeposit && $contract->deposit_status === 'paid') {
                $wallet = PaymentWallet::where('owner_id', $contract->buyer_id)
                                       ->where('company_id', $contract->company_id)
                                       ->lockForUpdate()
                                       ->firstOrFail();
                $wallet->increment('balance', (float) $contract->deposit_etb);

                EscrowHold::where('reference_id', $contract->id)
                          ->where('reference_type', 'forward_contract')
                          ->where('status', 'held')
                          ->update(['status' => 'released']);

                $contract->update(['deposit_status' => 'refunded']);
            } elseif (!$refundDeposit && $contract->deposit_status === 'paid') {
                EscrowHold::where('reference_id', $contract->id)
                          ->where('reference_type', 'forward_contract')
                          ->where('status', 'held')
                          ->update(['status' => 'released']);
                $contract->update(['deposit_status' => 'forfeited']);
            }

            $contract->update([
                'status'               => 'cancelled',
                'cancellation_reason'  => $reason,
                'cancelled_at'         => now(),
            ]);
        });
    }
}
