<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ProductLotteryManagementResource;
use App\Models\ProductLottery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ProductLotteryManagementController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = ProductLottery::query()
            ->with('product')
            ->withCount('prizes')
            ->withCount('codes')
            ->withCount([
                'codes as unredeemed_codes_count' => static fn ($q) => $q->whereNull('redeemed_at'),
            ])
            ->orderByDesc('id');

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->integer('product_id'));
        }

        $perPage = min(max($request->integer('per_page', 25), 1), 100);

        return ProductLotteryManagementResource::collection($query->paginate($perPage));
    }

    public function show(ProductLottery $productLottery): ProductLotteryManagementResource
    {
        $productLottery->load(['product', 'prizes']);
        $productLottery->loadCount('prizes');
        $productLottery->loadCount('codes');
        $productLottery->loadCount([
            'codes as unredeemed_codes_count' => static fn ($q) => $q->whereNull('redeemed_at'),
        ]);

        return new ProductLotteryManagementResource($productLottery);
    }
}
