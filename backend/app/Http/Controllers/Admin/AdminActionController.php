<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminActionController extends Controller
{
    protected const ACTION_TYPES = [
        'block_user', 'unblock_user', 'delete_user', 'restore_user',
        'hide_product', 'delete_product', 'restore_product', 'change_user_role',
    ];

    public function index(Request $request): JsonResponse
    {
        $validated = $this->validateFilters($request);

        $perPage = $validated['per_page'] ?? 20;

        $actions = $this->baseQuery($validated)
            ->with([
                'admin:id,name,email',
                'targetUser:id,name,email',
                'targetProduct:id,title',
            ])
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json($actions);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $validated = $this->validateFilters($request);
        $fileName = 'admin-actions-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($validated) {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, [
                'id', 'created_at', 'action_type', 'admin_user_id', 'admin_email',
                'target_user_id', 'target_user_email', 'target_product_id', 'target_product_title', 'reason',
            ]);

            $this->baseQuery($validated)
                ->with(['admin:id,email', 'targetUser:id,email', 'targetProduct:id,title'])
                ->orderByDesc('created_at')
                ->chunk(500, function ($actions) use ($handle) {
                    foreach ($actions as $action) {
                        fputcsv($handle, [
                            $action->id,
                            optional($action->created_at)->toDateTimeString(),
                            $action->action_type,
                            $action->admin_user_id,
                            optional($action->admin)->email,
                            $action->target_user_id,
                            optional($action->targetUser)->email,
                            $action->target_product_id,
                            optional($action->targetProduct)->title,
                            $action->reason,
                        ]);
                    }
                });

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function validateFilters(Request $request): array
    {
        return $request->validate([
            'action_type' => ['nullable', Rule::in(self::ACTION_TYPES)],
            'admin_user_id' => ['nullable', 'integer', 'min:1'],
            'target_user_id' => ['nullable', 'integer', 'min:1'],
            'target_product_id' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:200'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);
    }

    protected function baseQuery(array $validated)
    {
        $search = trim((string) ($validated['search'] ?? ''));
        $from = isset($validated['from']) ? Carbon::parse($validated['from'])->startOfDay() : null;
        $to = isset($validated['to']) ? Carbon::parse($validated['to'])->endOfDay() : null;

        return AdminAction::query()
            ->when(isset($validated['action_type']), fn($query) => $query->where('action_type', $validated['action_type']))
            ->when(isset($validated['admin_user_id']), fn($query) => $query->where('admin_user_id', $validated['admin_user_id']))
            ->when(isset($validated['target_user_id']), fn($query) => $query->where('target_user_id', $validated['target_user_id']))
            ->when(isset($validated['target_product_id']), fn($query) => $query->where('target_product_id', $validated['target_product_id']))
            ->when($from, fn($query) => $query->where('created_at', '>=', $from))
            ->when($to, fn($query) => $query->where('created_at', '<=', $to))
            ->when($search !== '', fn($query) => $query->where('reason', 'like', "%{$search}%"));
    }
}
