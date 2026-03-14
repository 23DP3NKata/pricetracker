<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Get full profile info for the authenticated user.
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
            'monthly_limit' => $user->monthly_limit,
            'checks_used' => $user->checks_used,
            'last_username_change' => $user->last_username_change,
            'email_verified_at' => $user->email_verified_at,
            'created_at' => $user->created_at,
        ]);
    }

    /**
     * Update user name. Limited to once per 30 days.
     */
    public function updateName(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check 30-day cooldown
        if ($user->last_username_change && $user->last_username_change->diffInDays(now()) < 30) {
            $daysLeft = 30 - $user->last_username_change->diffInDays(now());
            return response()->json([
                'message' => "You can change your name again in {$daysLeft} days.",
            ], 422);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('users')->ignore($user->id)],
        ]);

        $user->name = $validated['name'];
        $user->last_username_change = now();
        $user->save();

        return response()->json(['message' => 'Name updated.', 'user' => $user]);
    }

    /**
     * Update email while keeping current verification status.
     */
    public function updateEmail(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['required', 'string'],
        ]);

        if (!Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Incorrect password.'], 422);
        }

        $user->email = $validated['email'];
        $user->save();

        return response()->json(['message' => 'Email updated.', 'user' => $user]);
    }

    /**
     * Change password.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        return response()->json(['message' => 'Password changed.']);
    }
}
