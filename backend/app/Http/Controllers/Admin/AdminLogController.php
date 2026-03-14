<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'level' => ['nullable', Rule::in(['info', 'warning', 'error', 'critical'])],
            'category' => [
                'nullable',
                Rule::in(['scraper', 'price_check', 'auth', 'email', 'database', 'api', 'system']),
            ],
            'product_id' => ['nullable', 'integer', 'min:1'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:150'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $perPage = $validated['per_page'] ?? 20;

        $logs = SystemLog::query()
            ->with(['user:id,name,email', 'product:id,title'])
            ->when(isset($validated['level']), fn($query) => $query->where('level', $validated['level']))
            ->when(isset($validated['category']), fn($query) => $query->where('category', $validated['category']))
            ->when(isset($validated['product_id']), fn($query) => $query->where('product_id', $validated['product_id']))
            ->when(isset($validated['user_id']), fn($query) => $query->where('user_id', $validated['user_id']))
            ->when($search !== '', fn($query) => $query->where('message', 'like', "%{$search}%"))
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json($logs);
    }
}
