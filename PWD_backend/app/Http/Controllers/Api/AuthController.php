<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\User;
use App\Services\PhpMailerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(private readonly PhpMailerService $mailer)
    {
    }

    public function register(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', 'unique:admins,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['nullable', 'string', 'max:50'],
            'contact' => ['nullable', 'string', 'max:100'],
            'disability' => ['nullable', 'string', 'max:255'],
            'companyId' => ['nullable', 'string', 'max:100'],
            'companyName' => ['nullable', 'string', 'max:255'],
            'companyAddress' => ['nullable', 'string', 'max:255'],
            'companyIndustry' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
        ]);

        $normalizedRole = strtolower(trim((string) ($payload['role'] ?? 'applicant')));
        if ($normalizedRole === 'employer') {
            $normalizedRole = 'company_admin';
        }
        if ($normalizedRole === 'admin') {
            return response()->json([
                'message' => 'Admin accounts are managed separately.',
            ], 403);
        }

        $isCompanyAdmin = $normalizedRole === 'company_admin';
        $email = strtolower(trim((string) $payload['email']));
        $companyName = isset($payload['companyName']) ? trim((string) $payload['companyName']) : null;
        $companyId = isset($payload['companyId']) ? trim((string) $payload['companyId']) : null;
        if ($isCompanyAdmin && !$companyId) {
            $companyId = 'CMP-' . strtoupper(Str::random(8));
        }

        DB::table('pending_registrations')->updateOrInsert(
            ['email' => $email],
            [
                'name' => $payload['name'],
                'password' => Hash::make((string) $payload['password']),
                'role' => $normalizedRole,
                'contact' => $payload['contact'] ?? null,
                'disability' => $payload['disability'] ?? null,
                'company_id' => $companyId,
                'company_name' => $companyName,
                'company_address' => $payload['companyAddress'] ?? null,
                'company_industry' => $payload['companyIndustry'] ?? null,
                'position' => $payload['position'] ?? null,
                'department' => $payload['department'] ?? null,
                'expires_at' => now()->addMinutes(30),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $otpSent = true;
        try {
            $this->issueOtpForEmail($email, $normalizedRole);
        } catch (\Throwable $e) {
            $otpSent = false;
            Log::warning('Registration OTP send failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'message' => $otpSent ? 'Registered. OTP sent to your email.' : 'Registered. OTP sending failed, please resend.',
            'otpSent' => $otpSent,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'verificationCode' => ['nullable', 'digits:6'],
            'sessionKey' => ['nullable', 'string', 'max:191'],
        ]);

        $loginValue = strtolower(trim((string) ($payload['email'] ?? '')));
        if ($loginValue === '') {
            return response()->json(['message' => 'Login is required'], 422);
        }

        $admin = $this->findAdminByLogin($loginValue);
        if ($admin) {
            if (!Hash::check($payload['password'], (string) $admin->password)) {
                return response()->json(['message' => 'Invalid credentials'], 401);
            }

            $status = strtolower((string) ($admin->status ?? ''));
            if ($status === 'suspended') {
                return response()->json([
                    'message' => 'Your account has been suspended. Please contact support.',
                ], 403);
            }

            $sessionKey = trim((string) ($payload['sessionKey'] ?? ''));
            if ($sessionKey === '') {
                $sessionKey = (string) Str::uuid();
            }

            if ($this->isSessionInUseByAnotherDevice($admin, $sessionKey)) {
                return response()->json([
                    'message' => 'This account is currently in use on another device. Please try again later.',
                    'code' => 'ACCOUNT_IN_USE',
                ], 409);
            }

            $admin->last_login_at = now();
            $admin->status = 'active';
            $admin->is_active = true;
            $admin->active_session_key = $sessionKey;
            $admin->session_last_seen_at = now();
            $admin->save();

            return response()->json([
                'message' => 'Login successful',
                'user' => $this->mapUser($admin),
                'sessionKey' => $sessionKey,
            ]);
        }

        $user = $this->findUserByLogin($loginValue);
        if (!$user || !Hash::check($payload['password'], (string) $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $status = strtolower((string) ($user->status ?? ''));
        if ($status === 'suspended') {
            return response()->json([
                'message' => 'Your account has been suspended. Please ask your boss.',
            ], 403);
        }

        $role = strtolower((string) ($user->role ?? ''));
        if ($role === 'company_admin') {
            $verificationCode = (string) ($payload['verificationCode'] ?? '');
            if ($verificationCode === '') {
                return response()->json([
                    'message' => 'Company admin verification required',
                    'requiresCompanyAdminVerification' => true,
                ]);
            }
            if (!Hash::check($verificationCode, (string) ($user->company_login_code ?? ''))) {
                return response()->json(['message' => 'Invalid company admin verification code'], 401);
            }
        }

        $sessionKey = trim((string) ($payload['sessionKey'] ?? ''));
        if ($sessionKey === '') {
            $sessionKey = (string) Str::uuid();
        }

        if ($this->isSessionInUseByAnotherDevice($user, $sessionKey)) {
            return response()->json([
                'message' => 'This account is currently in use on another device. Please try again later.',
                'code' => 'ACCOUNT_IN_USE',
            ], 409);
        }

        $user->last_login_at = now();
        $user->status = 'active';
        $user->is_active = true;
        $user->active_session_key = $sessionKey;
        $user->session_last_seen_at = now();
        $user->save();

        return response()->json([
            'message' => 'Login successful',
            'user' => $this->mapUser($user),
            'sessionKey' => $sessionKey,
        ]);
    }

    public function pingSession(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'uid' => ['required', 'string'],
            'sessionKey' => ['required', 'string', 'max:191'],
            'accountType' => ['nullable', 'string', 'in:users,admins'],
        ]);

        $account = $this->resolveAccountForSession(
            (string) $payload['uid'],
            $payload['accountType'] ?? null,
            (string) $payload['sessionKey']
        );

        if (!$account) {
            return response()->json(['message' => 'Account not found.'], 404);
        }

        $currentSessionKey = trim((string) ($account->active_session_key ?? ''));
        if ($currentSessionKey === '' || !hash_equals($currentSessionKey, (string) $payload['sessionKey'])) {
            return response()->json([
                'message' => 'This account is now in use on another device.',
                'code' => 'ACCOUNT_IN_USE',
            ], 409);
        }

        $account->session_last_seen_at = now();
        if (strtolower((string) ($account->status ?? '')) !== 'suspended') {
            $account->status = 'active';
            $account->is_active = true;
        }
        $account->save();

        return response()->json(['message' => 'Session active.']);
    }

    public function logoutSession(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'uid' => ['required', 'string'],
            'sessionKey' => ['required', 'string', 'max:191'],
            'accountType' => ['nullable', 'string', 'in:users,admins'],
        ]);

        $account = $this->resolveAccountForSession(
            (string) $payload['uid'],
            $payload['accountType'] ?? null,
            (string) $payload['sessionKey']
        );

        if (!$account) {
            return response()->json(['message' => 'Logged out']);
        }

        $currentSessionKey = trim((string) ($account->active_session_key ?? ''));
        if ($currentSessionKey !== '' && hash_equals($currentSessionKey, (string) $payload['sessionKey'])) {
            $account->active_session_key = null;
            $account->session_last_seen_at = null;
            $account->is_active = false;
            if (strtolower((string) ($account->status ?? '')) !== 'suspended') {
                $account->status = 'inactive';
            }
            $account->last_logout_at = now();
            $account->save();
        }

        return response()->json(['message' => 'Logged out']);
    }

    public function sendOtp(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower(trim($payload['email']));
        $admin = Admin::where('email', $email)->first();
        $role = strtolower((string) ($admin->role ?? ''));
        if (!$role) {
            $user = User::where('email', $email)->first();
            $role = strtolower((string) ($user->role ?? ''));
        }
        if (!$role) {
            $pending = DB::table('pending_registrations')->where('email', $email)->first();
            $role = strtolower((string) ($pending->role ?? ''));
        }

        $this->issueOtpForEmail($email, $role ?: 'applicant');

        return response()->json(['message' => 'OTP sent']);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
            'mode' => ['nullable', 'string', 'max:20'],
        ]);

        $email = strtolower(trim($payload['email']));
        $otp = (string) $payload['otp'];

        $record = DB::table('otp_codes')
            ->where('email', $email)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('id')
            ->first();

        if (!$record || !Hash::check($otp, (string) $record->otp_code)) {
            return response()->json(['valid' => false], 422);
        }

        DB::table('otp_codes')
            ->where('id', $record->id)
            ->update([
                'verified_at' => now(),
                'updated_at' => now(),
            ]);

        $mode = strtolower((string) ($payload['mode'] ?? ''));
        if ($mode === 'register') {
            $pending = DB::table('pending_registrations')
                ->where('email', $email)
                ->first();
        } else {
            $pending = null;
        }

        if ($pending !== null) {
            if ($pending->expires_at && now()->greaterThan($pending->expires_at)) {
                DB::table('pending_registrations')->where('id', $pending->id)->delete();
                return response()->json(['valid' => false, 'message' => 'Registration session expired. Please register again.'], 422);
            }

            $pendingRole = strtolower((string) ($pending->role ?? 'applicant'));
            if ($pendingRole === 'admin') {
                DB::table('pending_registrations')->where('id', $pending->id)->delete();
                return response()->json([
                    'valid' => false,
                    'message' => 'Admin accounts are managed separately.',
                ], 403);
            }

            $existing = User::where('email', $email)->first();
            if (!$existing) {
                $isCompanyAdmin = $pendingRole === 'company_admin';
                $companyLoginCode = $isCompanyAdmin ? (string) random_int(100000, 999999) : null;

                $created = User::create([
                    'name' => (string) $pending->name,
                    'username' => (string) $pending->name,
                    'email' => $email,
                    'password' => (string) $pending->password,
                    'role' => $pendingRole ?: 'applicant',
                    'status' => 'active',
                    'is_active' => true,
                    'contact' => $pending->contact,
                    'disability' => $pending->disability,
                    'company_id' => $pending->company_id,
                    'company_name' => $pending->company_name,
                    'company_address' => $pending->company_address,
                    'company_industry' => $pending->company_industry,
                    'company_login_code' => $isCompanyAdmin ? Hash::make((string) $companyLoginCode) : null,
                    'position' => $pending->position,
                    'department' => $pending->department,
                    'last_login_at' => now(),
                ]);

                if ($isCompanyAdmin) {
                    try {
                        $this->mailer->sendPlainText(
                            $created->email,
                            'PWD Portal Company Account Created',
                            "Your company admin account has been created successfully.\n\nYour company admin verification code is: {$companyLoginCode}\n\nUse this code together with your email and password when logging in.\nNo OTP will be sent during login."
                        );
                    } catch (\Throwable $e) {
                        Log::warning('Company registration notice email failed', [
                            'email' => $created->email,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            DB::table('pending_registrations')->where('id', $pending->id)->delete();
        }

        return response()->json(['valid' => true]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
            'otp' => ['nullable', 'digits:6'],
        ]);

        $email = strtolower(trim($payload['email']));

        if (!empty($payload['otp'])) {
            $record = DB::table('otp_codes')
                ->where('email', $email)
                ->where('expires_at', '>', now())
                ->orderByDesc('id')
                ->first();

            if (!$record || !Hash::check((string) $payload['otp'], (string) $record->otp_code)) {
                return response()->json(['message' => 'Invalid OTP'], 422);
            }

            DB::table('otp_codes')->where('id', $record->id)->update([
                'verified_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $verified = DB::table('otp_codes')
                ->where('email', $email)
                ->whereNotNull('verified_at')
                ->where('verified_at', '>', now()->subMinutes(15))
                ->orderByDesc('id')
                ->first();

            if (!$verified) {
                return response()->json(['message' => 'OTP verification required'], 422);
            }
        }

        $admin = Admin::where('email', $email)->first();
        if ($admin) {
            $admin->password = $payload['password'];
            $admin->save();

            return response()->json(['message' => 'Password reset successful']);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json(['message' => 'Account not found'], 404);
        }

        $user->password = $payload['password'];
        $user->save();

        return response()->json(['message' => 'Password reset successful']);
    }

    private function mapUser(User|Admin $user): array
    {
        $isAdmin = $user instanceof Admin;

        return [
            'id' => (string) $user->id,
            'name' => $user->name,
            'username' => $user->username ?? $user->name,
            'email' => $user->email,
            'role' => $user->role ?? 'applicant',
            'status' => $user->status ?? 'active',
            'isActive' => (bool) ($user->is_active ?? true),
            'companyId' => $isAdmin ? null : $user->company_id,
            'companyName' => $isAdmin ? null : $user->company_name,
            'companyAddress' => $isAdmin ? null : $user->company_address,
            'companyIndustry' => $isAdmin ? null : $user->company_industry,
            'position' => $isAdmin ? null : $user->position,
            'department' => $isAdmin ? null : $user->department,
            'hasActiveSession' => !empty($user->active_session_key),
            'sessionLastSeenAt' => $user->session_last_seen_at,
            'accountType' => $isAdmin ? 'admins' : 'users',
        ];
    }

    private function isSessionInUseByAnotherDevice(User|Admin $user, string $sessionKey): bool
    {
        $activeSessionKey = trim((string) ($user->active_session_key ?? ''));
        if ($activeSessionKey === '') {
            return false;
        }

        if (hash_equals($activeSessionKey, $sessionKey)) {
            return false;
        }

        if (!(bool) ($user->is_active ?? false)) {
            return false;
        }

        if ($user->last_logout_at && $user->last_login_at && $user->last_logout_at->greaterThanOrEqualTo($user->last_login_at)) {
            return false;
        }

        $lastSeen = $user->session_last_seen_at ?? $user->last_login_at;
        if (!$lastSeen) {
            return false;
        }

        return $lastSeen->greaterThan(now()->subMinutes(3));
    }

    private function findUserByLogin(string $loginValue): ?User
    {
        $query = User::query()->whereRaw('LOWER(email) = ?', [$loginValue]);

        if (Schema::hasColumn('users', 'username')) {
            $query->orWhereRaw('LOWER(username) = ?', [$loginValue]);
        }

        try {
            return $query->first();
        } catch (QueryException $e) {
            Log::warning('Login username lookup failed for users table; falling back to email-only lookup.', [
                'error' => $e->getMessage(),
            ]);

            return User::query()
                ->whereRaw('LOWER(email) = ?', [$loginValue])
                ->first();
        }
    }

    private function findAdminByLogin(string $loginValue): ?Admin
    {
        $query = Admin::query()->whereRaw('LOWER(email) = ?', [$loginValue]);

        if (Schema::hasColumn('admins', 'username')) {
            $query->orWhereRaw('LOWER(username) = ?', [$loginValue]);
        }

        try {
            return $query->first();
        } catch (QueryException $e) {
            Log::warning('Login username lookup failed for admins table; falling back to email-only lookup.', [
                'error' => $e->getMessage(),
            ]);

            return Admin::query()
                ->whereRaw('LOWER(email) = ?', [$loginValue])
                ->first();
        }
    }

    private function resolveAccountForSession(string $uid, ?string $accountType, string $sessionKey): User|Admin|null
    {
        $normalizedType = strtolower(trim((string) ($accountType ?? '')));
        if ($normalizedType === 'admins') {
            return Admin::find($uid);
        }
        if ($normalizedType === 'users') {
            return User::find($uid);
        }

        $user = User::find($uid);
        $admin = Admin::find($uid);

        if ($user && $this->sessionKeyMatches($user, $sessionKey)) {
            return $user;
        }
        if ($admin && $this->sessionKeyMatches($admin, $sessionKey)) {
            return $admin;
        }

        return $user ?: $admin;
    }

    private function sessionKeyMatches(User|Admin $account, string $sessionKey): bool
    {
        $currentSessionKey = trim((string) ($account->active_session_key ?? ''));
        if ($currentSessionKey === '') {
            return false;
        }

        return hash_equals($currentSessionKey, $sessionKey);
    }

    private function issueOtpForEmail(string $email, string $role): void
    {
        $otp = (string) random_int(100000, 999999);
        $recipientLabel = 'Applicant';
        if ($role === 'company_admin' || $role === 'employer') {
            $recipientLabel = 'Company Admin';
        } elseif ($role === 'admin') {
            $recipientLabel = 'Admin';
        }

        DB::table('otp_codes')->insert([
            'email' => $email,
            'otp_code' => Hash::make($otp),
            'purpose' => 'auth',
            'expires_at' => now()->addMinutes(10),
            'verified_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->mailer->sendOtpEmail($email, $otp, $recipientLabel);
    }
}
