<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\SystemLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSystemController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'add_product_enabled' => AppSetting::getBool('products.add_enabled', true),
        ]);
    }

    public function updateAddProduct(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $enabled = (bool) $validated['enabled'];
        AppSetting::setBool('products.add_enabled', $enabled);

        SystemLog::create([
            'level' => 'warning',
            'category' => 'admin',
            'message' => $enabled
                ? 'Admin enabled adding products.'
                : 'Admin disabled adding products.' . (!empty($validated['reason']) ? ' Reason: ' . $validated['reason'] : ''),
        ]);

        return response()->json([
            'message' => $enabled
                ? 'Adding products has been enabled.'
                : 'Adding products has been disabled.',
            'add_product_enabled' => $enabled,
        ]);
    }
}
