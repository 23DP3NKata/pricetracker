<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminLogController extends Controller
{
    protected const CATEGORIES = ['scraper', 'price_check', 'auth', 'email', 'database', 'api', 'system'];

    public function index(Request $request): JsonResponse
    {
        $validated = $this->validateFilters($request);

        $search = trim((string) ($validated['search'] ?? ''));
        $perPage = $validated['per_page'] ?? 20;
        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortDir = $validated['sort_dir'] ?? 'desc';
        $sortColumn = match ($sortBy) {
            'id' => 'id',
            'level' => 'level',
            'category' => 'category',
            'created_at' => 'created_at',
            default => 'created_at',
        };

        $logs = $this->baseQuery($validated, $search)
            ->with(['user:id,name,email', 'product:id,title'])
            ->orderBy($sortColumn, $sortDir)
            ->paginate($perPage);

        return response()->json($logs);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $validated = $this->validateFilters($request);
        $search = trim((string) ($validated['search'] ?? ''));
        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortDir = $validated['sort_dir'] ?? 'desc';
        $sortColumn = match ($sortBy) {
            'id' => 'id',
            'level' => 'level',
            'category' => 'category',
            'created_at' => 'created_at',
            default => 'created_at',
        };

        $fileName = 'admin-logs-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($validated, $search, $sortColumn, $sortDir) {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['id', 'created_at', 'level', 'category', 'message', 'user_id', 'user_email', 'product_id', 'product_title']);

            $this->baseQuery($validated, $search)
                ->with(['user:id,email', 'product:id,title'])
                ->orderBy($sortColumn, $sortDir)
                ->chunk(500, function ($logs) use ($handle) {
                    foreach ($logs as $log) {
                        fputcsv($handle, [
                            $log->id,
                            optional($log->created_at)->toDateTimeString(),
                            $log->level,
                            $log->category,
                            $log->message,
                            $log->user_id,
                            optional($log->user)->email,
                            $log->product_id,
                            optional($log->product)->title,
                        ]);
                    }
                });

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function validateFilters(Request $request): array
    {
        return $request->validate([
            'level' => ['nullable', Rule::in(['info', 'warning', 'error', 'critical'])],
            'category' => ['nullable', Rule::in(self::CATEGORIES)],
            'product_id' => ['nullable', 'integer', 'min:1'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:150'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
            'sort_by' => ['nullable', Rule::in(['id', 'level', 'category', 'created_at'])],
            'sort_dir' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
    }

    protected function baseQuery(array $validated, string $search)
    {
        $from = isset($validated['from']) ? Carbon::parse($validated['from'])->startOfDay() : null;
        $to = isset($validated['to']) ? Carbon::parse($validated['to'])->endOfDay() : null;

        return SystemLog::query()
            ->when(isset($validated['level']), fn($query) => $query->where('level', $validated['level']))
            ->when(isset($validated['category']), fn($query) => $query->where('category', $validated['category']))
            ->when(isset($validated['product_id']), fn($query) => $query->where('product_id', $validated['product_id']))
            ->when(isset($validated['user_id']), fn($query) => $query->where('user_id', $validated['user_id']))
            ->when($from, fn($query) => $query->where('created_at', '>=', $from))
            ->when($to, fn($query) => $query->where('created_at', '<=', $to))
            ->when($search !== '', fn($query) => $query->where('message', 'like', "%{$search}%"));
    }
}
