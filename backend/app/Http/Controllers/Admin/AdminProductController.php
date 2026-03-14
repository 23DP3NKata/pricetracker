<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:180'],
            'status' => ['nullable', Rule::in(['active', 'hidden', 'deleted'])],
            'store_name' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $perPage = $validated['per_page'] ?? 20;

        $products = Product::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('url', 'like', "%{$search}%")
                        ->orWhere('product_page_url', 'like', "%{$search}%");
                });
            })
            ->when(isset($validated['status']), fn($query) => $query->where('status', $validated['status']))
            ->when(isset($validated['store_name']), fn($query) => $query->where('store_name', $validated['store_name']))
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json($products);
    }

    public function updateStatus(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['active', 'hidden', 'deleted'])],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $newStatus = $validated['status'];

        if ($product->status !== $newStatus) {
            $product->update(['status' => $newStatus]);

            $actionType = match ($newStatus) {
                'active' => 'restore_product',
                'hidden' => 'hide_product',
                'deleted' => 'delete_product',
                default => null,
            };

            if ($actionType) {
                AdminAction::create([
                    'admin_user_id' => $request->user()->id,
                    'action_type' => $actionType,
                    'target_product_id' => $product->id,
                    'reason' => $validated['reason'] ?? null,
                ]);
            }
        }

        return response()->json([
            'message' => 'Product status updated.',
            'product' => $product->fresh(),
        ]);
    }
}
