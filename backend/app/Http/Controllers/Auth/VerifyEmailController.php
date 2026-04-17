<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class VerifyEmailController extends Controller
{
    /**
     * Mark user's email as verified from signed verification URL.
     */
    public function __invoke(Request $request, int $id, string $hash): RedirectResponse|JsonResponse
    {
        /** @var User $user */
        $user = User::findOrFail($id);

        if ($user->hasVerifiedEmail()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Email already verified.',
                    'status' => 'already_verified',
                    'verified' => true,
                ]);
            }

            return redirect(config('app.frontend_url').'/dashboard?verified=1&status=already_verified')
                ->with('success', 'Email already verified');
        }

        if (! $request->hasValidSignature()) {
            throw new AccessDeniedHttpException('Invalid or expired verification link.');
        }

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            throw new AccessDeniedHttpException('Invalid verification link.');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Email verified successfully.',
                'status' => 'verified',
                'verified' => true,
            ]);
        }

        return redirect(config('app.frontend_url').'/dashboard?verified=1&status=verified')
            ->with('success', 'Email verified successfully');
    }
}
