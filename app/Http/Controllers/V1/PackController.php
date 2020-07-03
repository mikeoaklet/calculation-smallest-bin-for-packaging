<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PackController extends Controller
{
    public function calculateSmallestBinToPack(Request $request)
    {
        $items = $request->items;
        $hash = sha1(serialize($items));

        // If exists, return cached response. If not, calculate result and return then.
        return response(
            Cache::rememberForever($hash, function () use ($items) {
                $calculationRequest = new CalculationRequest();
                $calculationRequest->items = $items;

                return $calculationRequest->calculate();
            })
        );
    }
}
