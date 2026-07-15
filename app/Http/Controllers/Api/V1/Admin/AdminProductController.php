<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Catálogo de productos para el panel admin del kiosk.
 *
 * Requiere middleware lottery.management (Bearer token).
 */
final class AdminProductController extends Controller
{
    // ── Serializer helper ────────────────────────────────────────────────────

    private function serialize(Product $p): array
    {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'brand' => $p->brand,
            'description' => $p->description,
            'barcode' => $p->barcode,
            'price' => (string) $p->price,
            'cost' => (string) $p->cost,
            'main_image' => $p->main_image,
            'is_active' => $p->is_active,
        ];
    }

    // ── GET /api/v1/admin/products ───────────────────────────────────────────

    /**
     * Lista paginada del catálogo (activos + inactivos).
     *
     * Query params:
     *   - search    string  busca en name, sku, brand
     *   - active    bool    filtrar solo activos (default: todos)
     *   - per_page  int     (1-100, default 50)
     *   - page      int
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'search' => ['sometimes', 'string', 'max:100'],
            'active' => ['sometimes', 'boolean'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Product::query()->orderBy('name');

        // Por defecto muestra todos; si active=1 filtra solo activos
        if ($request->filled('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        if ($request->filled('search')) {
            $term = '%'.$request->input('search').'%';
            $query->where(function ($q) use ($term): void {
                $q->where('name', 'like', $term)
                    ->orWhere('sku', 'like', $term)
                    ->orWhere('brand', 'like', $term);
            });
        }

        $paginated = $query->paginate((int) $request->input('per_page', 50));

        return response()->json([
            'data' => collect($paginated->items())
                ->map(fn (Product $p) => $this->serialize($p))
                ->values(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    // ── POST /api/v1/admin/products ──────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['sometimes', 'nullable', 'string', 'max:100', 'unique:products,sku'],
            'brand' => ['sometimes', 'nullable', 'string', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'barcode' => ['sometimes', 'nullable', 'string', 'max:100'],
            'price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'cost' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $product = Product::create(array_merge(
            ['is_active' => true],
            $validated,
        ));

        return response()->json([
            'ok' => true,
            'product' => $this->serialize($product),
        ], 201);
    }

    // ── PATCH /api/v1/admin/products/{id} ───────────────────────────────────

    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'sku' => ['sometimes', 'nullable', 'string', 'max:100', "unique:products,sku,{$id}"],
            'brand' => ['sometimes', 'nullable', 'string', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'barcode' => ['sometimes', 'nullable', 'string', 'max:100'],
            'price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'cost' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $product->fill($validated)->save();

        return response()->json([
            'ok' => true,
            'product' => $this->serialize($product),
        ]);
    }
}
