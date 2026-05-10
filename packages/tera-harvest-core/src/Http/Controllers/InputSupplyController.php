<?php

namespace Fleetbase\TeraHarvest\Http\Controllers;

use Fleetbase\TeraHarvest\Models\InputSupplier;
use Fleetbase\TeraHarvest\Models\InputProduct;
use Fleetbase\TeraHarvest\Models\InputOrder;
use Fleetbase\TeraHarvest\Models\InputOrderItem;
use Fleetbase\TeraHarvest\Jobs\DispatchInputOrder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InputSupplyController extends Controller
{
    public function listSuppliers(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-Id');
        $suppliers = InputSupplier::where('company_id', $companyId)
                                  ->active()
                                  ->when($request->type, fn($q) => $q->where('type', $request->type))
                                  ->when($request->region_id, fn($q) => $q->where('region_id', $request->region_id))
                                  ->with('products')
                                  ->paginate(20);

        return response()->json(['data' => $suppliers]);
    }

    public function storeSupplier(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|max:255',
            'type'          => 'required|in:seed,fertilizer,pesticide,equipment,mixed',
            'contact_phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $supplier = InputSupplier::create(array_merge(
            $request->only(['name', 'type', 'registration_number', 'contact_phone', 'contact_email', 'region_id', 'woreda_id', 'address', 'latitude', 'longitude']),
            ['company_id' => $request->header('X-Company-Id')]
        ));

        return response()->json(['data' => $supplier], 201);
    }

    public function listProducts(string $supplierId, Request $request): JsonResponse
    {
        $products = InputProduct::where('supplier_id', $supplierId)
                                ->active()
                                ->when($request->category, fn($q) => $q->where('category', $request->category))
                                ->paginate(20);

        return response()->json(['data' => $products]);
    }

    public function storeProduct(string $supplierId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'               => 'required|string|max:255',
            'category'           => 'required|in:seed,fertilizer,pesticide,herbicide,fungicide,equipment,other',
            'unit'               => 'required|string|max:20',
            'price_per_unit_etb' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $product = InputProduct::create(array_merge(
            $request->only(['name', 'sku', 'category', 'description', 'unit', 'price_per_unit_etb', 'stock_quantity', 'reorder_threshold', 'certifications']),
            ['supplier_id' => $supplierId, 'company_id' => $request->header('X-Company-Id')]
        ));

        return response()->json(['data' => $product], 201);
    }

    public function placeOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'farmer_id'   => 'required|string',
            'supplier_id' => 'required|string',
            'items'       => 'required|array|min:1',
            'items.*.product_id' => 'required|string',
            'items.*.quantity'   => 'required|numeric|min:0.001',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $companyId = $request->header('X-Company-Id');

        $order = DB::transaction(function () use ($request, $companyId) {
            $subtotal = '0.00';
            $itemRows = [];

            foreach ($request->items as $item) {
                $product     = InputProduct::findOrFail($item['product_id']);
                $lineTotal   = bcmul((string) $item['quantity'], (string) $product->price_per_unit_etb, 2);
                $subtotal    = bcadd($subtotal, $lineTotal, 2);
                $itemRows[]  = [
                    'product'    => $product,
                    'quantity'   => $item['quantity'],
                    'line_total' => $lineTotal,
                ];
            }

            $deliveryFee = '50.00';
            $total       = bcadd($subtotal, $deliveryFee, 2);

            $order = InputOrder::create([
                'farmer_id'      => $request->farmer_id,
                'supplier_id'    => $request->supplier_id,
                'company_id'     => $companyId,
                'subtotal_etb'   => $subtotal,
                'delivery_fee_etb' => $deliveryFee,
                'total_etb'      => $total,
                'payment_method' => $request->payment_method ?? 'wallet',
                'delivery_address' => $request->delivery_address,
                'delivery_woreda_id' => $request->delivery_woreda_id,
            ]);

            foreach ($itemRows as $row) {
                InputOrderItem::create([
                    'order_id'       => $order->id,
                    'product_id'     => $row['product']->id,
                    'company_id'     => $companyId,
                    'product_name'   => $row['product']->name,
                    'product_sku'    => $row['product']->sku,
                    'quantity'       => $row['quantity'],
                    'unit'           => $row['product']->unit,
                    'unit_price_etb' => $row['product']->price_per_unit_etb,
                    'line_total_etb' => $row['line_total'],
                ]);
            }

            return $order;
        });

        DispatchInputOrder::dispatch($order->id);

        return response()->json(['data' => $order->load('items')], 201);
    }

    public function showOrder(string $id): JsonResponse
    {
        $order = InputOrder::with(['supplier', 'items'])->findOrFail($id);
        return response()->json(['data' => $order]);
    }
}
