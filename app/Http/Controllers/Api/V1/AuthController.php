<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AccountStatus;
use App\Http\Controllers\Api\ApiController;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends ApiController
{
    public function __construct(private AuditService $audit) {}

    /**
     * POST /api/v1/auth/token — issue a Sanctum token.
     */
    public function token(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status !== AccountStatus::Active) {
            return $this->fail('Account is '.$user->status->value.'.', 403);
        }

        $user->tokens()->where('name', $validated['device_name'] ?? 'api')->delete();

        $token = $user->createToken($validated['device_name'] ?? 'api');

        $this->audit->log('api.token.issued', $user);

        return $this->ok([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
        ], 'Token issued');
    }

    /**
     * POST /api/v1/auth/revoke — revoke the current token.
     */
    public function revoke(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        $this->audit->log('api.token.revoked', $request->user());

        return $this->ok(null, 'Token revoked');
    }

    /**
     * GET /api/v1/auth/me — current user + roles/permissions.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->ok([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status->value,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }
}
