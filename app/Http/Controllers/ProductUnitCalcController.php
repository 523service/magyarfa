<?php

namespace App\Http\Controllers;

use App\Models\Shop\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductUnitCalcController extends Controller
{
    public function calc(Request $request, Product $product): JsonResponse
    {
        $config = $product->unitConfig()->with(['baseUnit', 'secondaryUnit'])->first();

        if (! $config) {
            return response()->json(['error' => 'No unit config found for this product.'], 404);
        }

        $requestedQty = (float) $request->input('qty', $config->min_order_qty);
        $selectedUnit = $request->input('unit', 'base'); // 'base' vagy 'secondary'

        // Ha másodlagos egységben (pl. bálában) adja meg → visszaváltás alap egységre
        if ($selectedUnit === 'secondary' && $config->secondary_unit_qty) {
            $requestedQty = $requestedQty * (float) $config->secondary_unit_qty;
        }

        $actualQty = $config->roundUpToStep($requestedQty);
        $pricePerUnit = $config->price_per_base_unit !== null
            ? (float) $config->price_per_base_unit
            : (float) $product->price;
        $totalPrice = $actualQty * $pricePerUnit;
        $secondaryQty = $config->toSecondaryUnit($actualQty);

        return response()->json([
            'actual_base_qty' => $actualQty,
            'secondary_qty' => $secondaryQty,
            'base_unit_label' => $config->baseUnit->label_short,
            'secondary_unit_label' => $config->secondaryUnit?->label_short,
            'price_per_base_unit' => $pricePerUnit,
            'total_price' => $totalPrice,
            'was_rounded_up' => $actualQty > $requestedQty,
        ]);
    }
}
