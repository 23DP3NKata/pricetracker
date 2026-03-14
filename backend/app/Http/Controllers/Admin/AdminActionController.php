<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminActionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        $perPage = $validated['per_page'] ?? 20;

        $actions = AdminAction::query()
            ->with([
                'admin:id,name,email',
                'targetUser:id,name,email',
                'targetProduct:id,title',
            ])
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json($actions);
    }
}
