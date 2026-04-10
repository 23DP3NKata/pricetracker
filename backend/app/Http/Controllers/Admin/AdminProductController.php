<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\Product;
use App\Models\SystemLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:180'],
            'symbol' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', Rule::in(['active', 'hidden', 'deleted'])],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
            'sort_by' => ['nullable', Rule::in(['id', 'title', 'symbol', 'status', 'current_price', 'tracking_count', 'created_at'])],
            'sort_dir' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $perPage = $validated['per_page'] ?? 20;
        $sortMap = [
            'id' => 'id',
            'title' => 'title',
            'symbol' => 'symbol',
            'status' => 'status',
            'current_price' => 'current_price',
            'tracking_count' => 'tracking_count',
            'created_at' => 'created_at',
        ];
        $sortBy = $validated['sort_by'] ?? 'id';
        $sortDir = $validated['sort_dir'] ?? 'desc';
        $sortColumn = $sortMap[$sortBy] ?? 'id';

        $products = Product::query()
            ->when(isset($validated['product_id']), fn($query) => $query->where('id', $validated['product_id']))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('symbol', 'like', "%{$search}%")
                        ->orWhere('coingecko_id', 'like', "%{$search}%")
                        ->orWhere('product_page_url', 'like', "%{$search}%");
                });
            })
            ->when(isset($validated['symbol']), fn($query) => $query->where('symbol', strtoupper(trim((string) $validated['symbol']))))
            ->when(isset($validated['status']), fn($query) => $query->where('status', $validated['status']))
            ->orderBy($sortColumn, $sortDir)
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
            $oldStatus = $product->status;
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

                $reason = trim((string) ($validated['reason'] ?? ''));
                $message = sprintf(
                    'Admin #%d changed product #%d (%s) status: %s -> %s%s',
                    $request->user()->id,
                    $product->id,
                    $product->title,
                    $oldStatus,
                    $newStatus,
                    $reason !== '' ? '. Reason: ' . $reason : ''
                );

                SystemLog::create([
                    'level' => 'info',
                    'category' => 'admin',
                    'message' => $message,
                    'user_id' => $request->user()->id,
                    'user_name_snapshot' => $request->user()->name,
                    'product_id' => $product->id,
                ]);
            }
        }

        return response()->json([
            'message' => 'Product status updated.',
            'product' => $product->fresh(),
        ]);
    }
}
