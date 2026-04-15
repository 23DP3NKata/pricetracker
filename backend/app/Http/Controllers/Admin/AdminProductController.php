<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\Product;
use App\Models\SystemLog;
use App\Models\UserProduct;
use App\Services\CoinGeckoPriceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminProductController extends Controller
{
    public function __construct(
        protected CoinGeckoPriceService $priceService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:180'],
            'symbol' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', Rule::in(['active', 'hidden'])],
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
            ->whereIn('status', ['active', 'hidden'])
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
            'status' => ['required', Rule::in(['active', 'hidden'])],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $newStatus = $validated['status'];

        if ($product->status !== $newStatus) {
            $oldStatus = $product->status;
            $product->update(['status' => $newStatus]);

            $actionType = match ($newStatus) {
                'active' => 'restore_product',
                'hidden' => 'hide_product',
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
                $reasonSuffix = '';
                if ($reason !== '') {
                    $reasonSuffix = '. Reason: ' . $reason;
                }

                $message = sprintf(
                    'Admin #%d changed product #%d (%s) status: %s -> %s%s',
                    $request->user()->id,
                    $product->id,
                    $product->title,
                    $oldStatus,
                    $newStatus,
                    $reasonSuffix
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

    public function refreshPrice(Request $request, Product $product): JsonResponse
    {
        $result = $this->priceService->refreshProductNow($product);
        $reason = trim((string) $request->input('reason', ''));
        $reasonSuffix = '';
        if ($reason !== '') {
            $reasonSuffix = '. Reason: ' . $reason;
        }

        if (($result['errors'] ?? 0) > 0) {
            $errorMessage = (string) (($result['error_details'][0]['message'] ?? 'Failed to refresh product price.'));

            SystemLog::create([
                'level' => 'warning',
                'category' => 'admin',
                'message' => sprintf(
                    'Admin #%d failed to force refresh product #%d (%s): %s',
                    $request->user()->id,
                    $product->id,
                    $product->title,
                    $errorMessage
                ),
                'user_id' => $request->user()->id,
                'user_name_snapshot' => $request->user()->name,
                'product_id' => $product->id,
            ]);

            return response()->json([
                'message' => $errorMessage,
                'result' => $result,
            ], 422);
        }

        SystemLog::create([
            'level' => 'info',
            'category' => 'admin',
            'message' => sprintf(
                'Admin #%d force refreshed product #%d (%s)%s',
                $request->user()->id,
                $product->id,
                $product->title,
                $reasonSuffix
            ),
            'user_id' => $request->user()->id,
            'user_name_snapshot' => $request->user()->name,
            'product_id' => $product->id,
        ]);

        return response()->json([
            'message' => 'Product price refreshed.',
            'product' => $product->fresh(),
            'result' => $result,
        ]);
    }

    public function refreshAllPrices(Request $request): JsonResponse
    {
        $result = $this->priceService->refreshAllActiveProducts();
        $reason = trim((string) $request->input('reason', ''));

        $level = 'info';
        if (($result['errors'] ?? 0) > 0) {
            $level = 'warning';
        }

        $reasonSuffix = '';
        if ($reason !== '') {
            $reasonSuffix = '. Reason: ' . $reason;
        }

        SystemLog::create([
            'level' => $level,
            'category' => 'admin',
            'message' => sprintf(
                'Admin #%d force refreshed all active products. Checked: %d, Errors: %d%s',
                $request->user()->id,
                (int) ($result['checked'] ?? 0),
                (int) ($result['errors'] ?? 0),
                $reasonSuffix
            ),
            'user_id' => $request->user()->id,
            'user_name_snapshot' => $request->user()->name,
        ]);

        return response()->json([
            'message' => 'Prices refreshed for active products.',
            'result' => $result,
        ]);
    }

    public function stopAllProducts(Request $request): JsonResponse
    {
        $updated = Product::query()
            ->where('status', 'active')
            ->update(['status' => 'hidden']);

        SystemLog::create([
            'level' => 'warning',
            'category' => 'admin',
            'message' => sprintf(
                'Admin #%d stopped API price updates for all products. Moved to hidden: %d',
                $request->user()->id,
                (int) $updated,
            ),
            'user_id' => $request->user()->id,
            'user_name_snapshot' => $request->user()->name,
        ]);

        return response()->json([
            'message' => 'Price updates stopped for all products.',
            'updated' => (int) $updated,
        ]);
    }

    public function startAllProducts(Request $request): JsonResponse
    {
        $productIds = Product::query()
            ->where('status', 'hidden')
            ->pluck('id');

        $updated = 0;
        $trackersScheduled = 0;

        if ($productIds->isNotEmpty()) {
            $updated = Product::query()
                ->whereIn('id', $productIds)
                ->update(['status' => 'active']);

            $trackersScheduled = UserProduct::query()
                ->whereIn('product_id', $productIds)
                ->where('is_active', true)
                ->update(['next_check_at' => now()]);
        }

        SystemLog::create([
            'level' => 'info',
            'category' => 'admin',
            'message' => sprintf(
                'Admin #%d resumed API price updates for all products. Moved to active: %d, trackers scheduled: %d',
                $request->user()->id,
                (int) $updated,
                (int) $trackersScheduled,
            ),
            'user_id' => $request->user()->id,
            'user_name_snapshot' => $request->user()->name,
        ]);

        return response()->json([
            'message' => 'Price updates resumed for all products.',
            'updated' => (int) $updated,
            'trackers_scheduled' => (int) $trackersScheduled,
        ]);
    }
}
