<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
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
        ]);

        $perPage = $validated['per_page'] ?? 20;
        $search = trim((string) ($validated['search'] ?? ''));

        $users = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(isset($validated['status']), fn($query) => $query->where('status', $validated['status']))
            ->when(isset($validated['role']), fn($query) => $query->where('role', $validated['role']))
            ->orderByDesc('id')
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
            $user->update([
                'status' => $newStatus,
                'status_changed_by' => $request->user()->id,
                'status_changed_at' => now(),
            ]);

            AdminAction::create([
                'admin_user_id' => $request->user()->id,
                'action_type' => $newStatus === 'blocked' ? 'block_user' : 'unblock_user',
                'target_user_id' => $user->id,
                'reason' => $validated['reason'] ?? null,
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

        $newLimit = (int) $validated['monthly_limit'];

        if ((int) $user->monthly_limit !== $newLimit) {
            $user->update(['monthly_limit' => $newLimit]);
        }

        return response()->json([
            'message' => 'Monthly limit updated.',
            'user' => $user->fresh(),
        ]);
    }

    public function updateRole(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(['user', 'admin'])],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        if ($request->user()->id === $user->id && $validated['role'] !== 'admin') {
            return response()->json(['message' => 'You cannot remove your own admin role.'], 422);
        }

        $newRole = $validated['role'];

        if ($user->role !== $newRole) {
            $user->update(['role' => $newRole]);

            AdminAction::create([
                'admin_user_id' => $request->user()->id,
                'action_type' => 'change_user_role',
                'target_user_id' => $user->id,
                'reason' => 'Role changed to ' . $newRole . ($validated['reason'] ? '. ' . $validated['reason'] : ''),
            ]);
        }

        return response()->json([
            'message' => 'User role updated.',
            'user' => $user->fresh(),
        ]);
    }
}
