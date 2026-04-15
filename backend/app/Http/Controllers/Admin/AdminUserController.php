<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['active', 'blocked'])],
            'role' => ['nullable', Rule::in(['user', 'admin'])],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
            'sort_by' => ['nullable', Rule::in(['id', 'name', 'email', 'role', 'status', 'monthly_limit', 'checks_used', 'created_at'])],
            'sort_dir' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        $perPage = $validated['per_page'] ?? 20;
        $search = trim((string) ($validated['search'] ?? ''));

        $sortMap = [
            'id' => 'id',
            'name' => 'name',
            'email' => 'email',
            'role' => 'role',
            'status' => 'status',
            'monthly_limit' => 'monthly_limit',
            'checks_used' => 'checks_used',
            'created_at' => 'created_at',
        ];
        $sortBy = $validated['sort_by'] ?? 'id';
        $sortDir = $validated['sort_dir'] ?? 'desc';
        $sortColumn = $sortMap[$sortBy] ?? 'id';

        $users = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(isset($validated['status']), fn($query) => $query->where('status', $validated['status']))
            ->when(isset($validated['role']), fn($query) => $query->where('role', $validated['role']))
            ->orderBy($sortColumn, $sortDir)
            ->paginate($perPage);

        return response()->json($users);
    }

    public function updateStatus(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['active', 'blocked'])],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        if ($request->user()->id === $user->id && $validated['status'] === 'blocked') {
            return response()->json(['message' => 'You cannot block your own account.'], 422);
        }

        $newStatus = $validated['status'];

        if ($user->status !== $newStatus) {
            $oldStatus = $user->status;
            $user->update([
                'status' => $newStatus,
                'status_changed_by' => $request->user()->id,
                'status_changed_at' => now(),
            ]);

            $reason = trim((string) ($validated['reason'] ?? ''));
            $actionType = 'unblock_user';
            if ($newStatus === 'blocked') {
                $actionType = 'block_user';
            }

            $actionReason = null;
            if ($reason !== '') {
                $actionReason = $reason;
            }

            $reasonSuffix = '';
            if ($reason !== '') {
                $reasonSuffix = '. Reason: ' . $reason;
            }

            AdminAction::create([
                'admin_user_id' => $request->user()->id,
                'action_type' => $actionType,
                'target_user_id' => $user->id,
                'reason' => $actionReason,
            ]);

            SystemLog::create([
                'level' => 'info',
                'category' => 'admin',
                'message' => sprintf(
                    'Admin #%d changed user #%d (%s) status: %s -> %s%s',
                    $request->user()->id,
                    $user->id,
                    $user->email,
                    $oldStatus,
                    $newStatus,
                    $reasonSuffix
                ),
                'user_id' => $request->user()->id,
                'user_name_snapshot' => $request->user()->name,
            ]);
        }

        return response()->json([
            'message' => 'User status updated.',
            'user' => $user->fresh(),
        ]);
    }

    public function updateLimit(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'monthly_limit' => ['required', 'integer', 'min:0', 'max:100000'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $reason = $validated['reason'] ?? null;
        $reasonText = null;
        if ($reason) {
            $reasonText = (string) $reason;
        }

        $oldLimit = (int) $user->monthly_limit;
        $newLimit = (int) $validated['monthly_limit'];

        if ($oldLimit !== $newLimit) {
            $user->update(['monthly_limit' => $newLimit]);

            $actionReason = "Monthly limit changed from {$oldLimit} to {$newLimit}";
            if ($reasonText) {
                $actionReason .= '. ' . $reasonText;
            }

            $logReasonSuffix = '';
            if ($reasonText) {
                $logReasonSuffix = '. Reason: ' . $reasonText;
            }

            AdminAction::create([
                'admin_user_id' => $request->user()->id,
                'action_type' => 'change_user_limit',
                'target_user_id' => $user->id,
                'reason' => $actionReason,
            ]);

            SystemLog::create([
                'level' => 'info',
                'category' => 'admin',
                'message' => sprintf(
                    'Admin #%d changed user #%d (%s) monthly limit: %d -> %d%s',
                    $request->user()->id,
                    $user->id,
                    $user->email,
                    $oldLimit,
                    $newLimit,
                    $logReasonSuffix
                ),
                'user_id' => $request->user()->id,
                'user_name_snapshot' => $request->user()->name,
            ]);
        }

        return response()->json([
            'message' => 'Monthly limit updated.',
            'user' => $user->fresh(),
        ]);
    }

    public function updateRole(Request $request, User $user): JsonResponse
    {
        return response()->json([
            'message' => 'User role changes are disabled in the admin panel.',
        ], 403);
    }
}
