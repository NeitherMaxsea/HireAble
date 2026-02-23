<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:50'],
            'isActive' => ['nullable', 'boolean'],
            'contact' => ['nullable', 'string', 'max:100'],
            'disability' => ['nullable', 'string', 'max:255'],
            'companyId' => ['nullable', 'string', 'max:100'],
            'companyName' => ['nullable', 'string', 'max:255'],
            'companyAddress' => ['nullable', 'string', 'max:255'],
            'companyIndustry' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'profileCompleted' => ['nullable', 'boolean'],
            'onboardingData' => ['nullable', 'array'],
            'activeSessionKey' => ['nullable', 'string', 'max:191'],
            'sessionLastSeenAt' => ['nullable'],
        ]);

        $role = strtolower(trim((string) ($payload['role'] ?? '')));
        if (!in_array($role, ['hr', 'finance', 'operation'], true)) {
            return response()->json(['message' => 'Invalid employee role.'], 422);
        }

        $username = trim((string) $payload['username']);
        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            $name = $username;
        }

        $status = strtolower(trim((string) ($payload['status'] ?? 'active')));
        if (!in_array($status, ['active', 'inactive', 'suspended'], true)) {
            $status = 'active';
        }
        $isActive = array_key_exists('isActive', $payload) ? (bool) $payload['isActive'] : $status === 'active';

        $user = User::create([
            'username' => $username,
            'name' => $name,
            'email' => strtolower(trim((string) $payload['email'])),
            'password' => (string) $payload['password'],
            'role' => $role,
            'status' => $status,
            'is_active' => $isActive,
            'contact' => $payload['contact'] ?? null,
            'disability' => $payload['disability'] ?? null,
            'company_id' => $payload['companyId'] ?? null,
            'company_name' => $payload['companyName'] ?? null,
            'company_address' => $payload['companyAddress'] ?? null,
            'company_industry' => $payload['companyIndustry'] ?? null,
            'position' => $payload['position'] ?? null,
            'department' => $payload['department'] ?? null,
            'profile_completed' => array_key_exists('profileCompleted', $payload) ? (bool) $payload['profileCompleted'] : true,
            'onboarding_data' => $payload['onboardingData'] ?? null,
            'last_login_at' => null,
            'active_session_key' => $payload['activeSessionKey'] ?? null,
            'session_last_seen_at' => $payload['sessionLastSeenAt'] ?? null,
        ]);

        $result = $this->mapUser($user);
        $result['currentPassword'] = (string) $payload['password'];

        return response()->json($result, 201);
    }

    public function index(Request $request): JsonResponse
    {
        $query = User::query()->whereRaw('LOWER(COALESCE(role, "")) != ?', ['admin']);

        if ($request->filled('companyId')) {
            $query->where('company_id', (string) $request->input('companyId'));
        }

        return response()->json($query->orderByDesc('created_at')->get()->map(fn (User $user) => $this->mapUser($user)));
    }

    public function show(string $id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json($this->mapUser($user));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $payload = $request->validate([
            'username' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'role' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:50'],
            'isActive' => ['nullable', 'boolean'],
            'contact' => ['nullable', 'string', 'max:100'],
            'disability' => ['nullable', 'string', 'max:255'],
            'photoURL' => ['nullable', 'string', 'max:2048'],
            'photoPath' => ['nullable', 'string', 'max:2048'],
            'companyId' => ['nullable', 'string', 'max:100'],
            'companyName' => ['nullable', 'string', 'max:255'],
            'companyAddress' => ['nullable', 'string', 'max:255'],
            'companyIndustry' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'lastLoginAt' => ['nullable'],
            'lastLogoutAt' => ['nullable'],
            'passwordResetRequestedAt' => ['nullable'],
            'activeSessionKey' => ['nullable', 'string', 'max:191'],
            'sessionLastSeenAt' => ['nullable'],
            'profileCompleted' => ['nullable', 'boolean'],
            'onboardingData' => ['nullable', 'array'],
        ]);

        if (array_key_exists('role', $payload) && strtolower(trim((string) ($payload['role'] ?? ''))) === 'admin') {
            return response()->json(['message' => 'Admin accounts are managed separately.'], 422);
        }

        if (array_key_exists('username', $payload) && !array_key_exists('name', $payload)) {
            $payload['name'] = $payload['username'];
        }

        $user->fill([
            'username' => $payload['username'] ?? $user->username,
            'name' => $payload['name'] ?? $user->name,
            'email' => array_key_exists('email', $payload) ? strtolower(trim((string) $payload['email'])) : $user->email,
            'role' => $payload['role'] ?? $user->role,
            'status' => $payload['status'] ?? $user->status,
            'is_active' => $payload['isActive'] ?? $user->is_active,
            'contact' => $payload['contact'] ?? $user->contact,
            'disability' => $payload['disability'] ?? $user->disability,
            'photo_url' => $payload['photoURL'] ?? $user->photo_url,
            'photo_path' => $payload['photoPath'] ?? $user->photo_path,
            'company_id' => $payload['companyId'] ?? $user->company_id,
            'company_name' => $payload['companyName'] ?? $user->company_name,
            'company_address' => $payload['companyAddress'] ?? $user->company_address,
            'company_industry' => $payload['companyIndustry'] ?? $user->company_industry,
            'position' => $payload['position'] ?? $user->position,
            'department' => $payload['department'] ?? $user->department,
            'profile_completed' => array_key_exists('profileCompleted', $payload)
                ? (bool) $payload['profileCompleted']
                : $user->profile_completed,
            'onboarding_data' => array_key_exists('onboardingData', $payload)
                ? $payload['onboardingData']
                : $user->onboarding_data,
            'last_login_at' => $payload['lastLoginAt'] ?? $user->last_login_at,
            'last_logout_at' => $payload['lastLogoutAt'] ?? $user->last_logout_at,
            'password_reset_requested_at' => $payload['passwordResetRequestedAt'] ?? $user->password_reset_requested_at,
            'active_session_key' => array_key_exists('activeSessionKey', $payload)
                ? (trim((string) ($payload['activeSessionKey'] ?? '')) ?: null)
                : $user->active_session_key,
            'session_last_seen_at' => $payload['sessionLastSeenAt'] ?? $user->session_last_seen_at,
        ]);

        $nextStatus = strtolower((string) ($user->status ?? ''));
        $nextIsActive = (bool) ($user->is_active ?? false);
        if ($nextStatus === 'inactive' || $nextStatus === 'suspended' || !$nextIsActive) {
            $user->active_session_key = null;
            $user->session_last_seen_at = null;
        }
        $user->save();

        return response()->json($this->mapUser($user));
    }

    public function destroy(string $id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->delete();
        return response()->json(['message' => 'User deleted']);
    }

    public function passwordResetRequest(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $payload['email'])->first();
        if ($user) {
            $user->password_reset_requested_at = now();
            $user->save();
        }

        return response()->json(['message' => 'Password reset request accepted']);
    }

    private function mapUser(User $user): array
    {
        return [
            'id' => (string) $user->id,
            'name' => $user->name,
            'username' => $user->username ?? $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
            'isActive' => (bool) ($user->is_active ?? false),
            'contact' => $user->contact,
            'disability' => $user->disability,
            'photoURL' => $user->photo_url,
            'photoPath' => $user->photo_path,
            'companyId' => $user->company_id,
            'companyName' => $user->company_name,
            'companyAddress' => $user->company_address,
            'companyIndustry' => $user->company_industry,
            'position' => $user->position,
            'department' => $user->department,
            'profileCompleted' => (bool) ($user->profile_completed ?? false),
            'onboardingData' => $user->onboarding_data,
            'lastLoginAt' => $user->last_login_at,
            'lastLogoutAt' => $user->last_logout_at,
            'passwordResetRequestedAt' => $user->password_reset_requested_at,
            'hasActiveSession' => !empty($user->active_session_key),
            'sessionLastSeenAt' => $user->session_last_seen_at,
            'createdAt' => optional($user->created_at)?->toISOString(),
            'updatedAt' => optional($user->updated_at)?->toISOString(),
        ];
    }
}
