<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApplicantProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
        $query = User::query()
            ->with('applicantProfile')
            ->whereRaw('LOWER(COALESCE(role, "")) != ?', ['admin']);

        if ($request->filled('companyId')) {
            $query->where('company_id', (string) $request->input('companyId'));
        }

        return response()->json($query->orderByDesc('created_at')->get()->map(fn (User $user) => $this->mapUser($user)));
    }

    public function show(string $id): JsonResponse
    {
        $user = User::with('applicantProfile')->find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json($this->mapUser($user));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $user = User::with('applicantProfile')->find($id);
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

        if (strtolower((string) ($user->role ?? '')) === 'applicant' && array_key_exists('onboardingData', $payload)) {
            $this->syncApplicantProfile($user, is_array($payload['onboardingData']) ? $payload['onboardingData'] : []);
            $user->refresh()->loadMissing('applicantProfile');
        }

        return response()->json($this->mapUser($user));
    }

    public function destroy(string $id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        DB::transaction(function () use ($user) {
            $this->deleteRelatedRecords($user);
            $user->delete();
        });

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
        $user->loadMissing('applicantProfile');
        $applicantProfile = $this->mapApplicantProfile($user->applicantProfile);

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
            'applicantProfile' => $applicantProfile,
            'lastLoginAt' => $user->last_login_at,
            'lastLogoutAt' => $user->last_logout_at,
            'passwordResetRequestedAt' => $user->password_reset_requested_at,
            'hasActiveSession' => !empty($user->active_session_key),
            'sessionLastSeenAt' => $user->session_last_seen_at,
            'createdAt' => optional($user->created_at)?->toISOString(),
            'updatedAt' => optional($user->updated_at)?->toISOString(),
        ];
    }

    private function mapApplicantProfile(?ApplicantProfile $profile): ?array
    {
        if (!$profile) {
            return null;
        }

        return [
            'id' => (string) $profile->id,
            'userId' => (string) $profile->user_id,
            'firstName' => $profile->first_name,
            'lastName' => $profile->last_name,
            'sexAtBirth' => $profile->sex_at_birth,
            'birthDate' => optional($profile->birth_date)?->toDateString(),
            'academicLevel' => $profile->academic_level,
            'addressProvince' => $profile->address_province,
            'addressCity' => $profile->address_city,
            'mobileNumber' => $profile->mobile_number,
            'pwdCategory' => $profile->pwd_category,
            'pwdIdNumber' => $profile->pwd_id_number,
            'preferredRole' => $profile->preferred_role,
            'preferredSkills' => $profile->preferred_skills,
            'yearsExperience' => $profile->years_experience,
            'preferredProvince' => $profile->preferred_province,
            'preferredCity' => $profile->preferred_city,
            'workMode' => $profile->work_mode,
            'salaryMin' => $profile->salary_min,
            'salaryMax' => $profile->salary_max,
            'profileSummary' => $profile->profile_summary,
            'profilePictureUrl' => $profile->profile_picture_url,
            'profilePicturePath' => $profile->profile_picture_path,
            'pwdIdImageUrl' => $profile->pwd_id_image_url,
            'pwdIdImagePath' => $profile->pwd_id_image_path,
            'rawPayload' => $profile->raw_payload,
            'createdAt' => optional($profile->created_at)?->toISOString(),
            'updatedAt' => optional($profile->updated_at)?->toISOString(),
        ];
    }

    private function syncApplicantProfile(User $user, array $data): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('applicant_profiles')) {
            return;
        }

        $preferredSkills = $data['preferredSkills'] ?? null;
        if (!is_array($preferredSkills)) {
            $preferredSkills = null;
        }

        ApplicantProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'first_name' => $data['firstName'] ?? null,
                'last_name' => $data['lastName'] ?? null,
                'sex_at_birth' => $data['sexAtBirth'] ?? null,
                'birth_date' => $data['birthDate'] ?? null,
                'academic_level' => $data['academicLevel'] ?? null,
                'address_province' => $data['addressProvince'] ?? null,
                'address_city' => $data['addressCity'] ?? null,
                'mobile_number' => $data['mobileNumber'] ?? $user->contact,
                'pwd_category' => $data['pwdCategory'] ?? $user->disability,
                'pwd_id_number' => $data['pwdIdNumber'] ?? null,
                'preferred_role' => $data['preferredRole'] ?? null,
                'preferred_skills' => $preferredSkills,
                'years_experience' => $data['yearsExperience'] ?? null,
                'preferred_province' => $data['preferredProvince'] ?? null,
                'preferred_city' => $data['preferredCity'] ?? null,
                'work_mode' => $data['workMode'] ?? null,
                'salary_min' => $data['salaryMin'] ?? null,
                'salary_max' => $data['salaryMax'] ?? null,
                'profile_summary' => $data['profileSummary'] ?? null,
                'profile_picture_url' => $data['profilePictureUrl'] ?? $user->photo_url,
                'profile_picture_path' => $data['profilePicturePath'] ?? $user->photo_path,
                'pwd_id_image_url' => $data['pwdIdImageUrl'] ?? null,
                'pwd_id_image_path' => $data['pwdIdImagePath'] ?? null,
                'raw_payload' => $data ?: null,
            ]
        );
    }

    private function deleteRelatedRecords(User $user): void
    {
        $userId = (string) $user->id;
        $userEmail = strtolower(trim((string) ($user->email ?? '')));
        $jobIds = [];
        $applicationIds = [];

        if (Schema::hasTable('jobs')) {
            $jobQuery = DB::table('jobs')->select('id');
            $canFilterByPoster = false;

            $jobQuery->where(function ($query) use ($userId, $userEmail, &$canFilterByPoster) {
                if (Schema::hasColumn('jobs', 'posted_by_uid')) {
                    $query->orWhere('posted_by_uid', $userId);
                    $canFilterByPoster = true;
                }
                if ($userEmail !== '' && Schema::hasColumn('jobs', 'posted_by_email')) {
                    $query->orWhereRaw('LOWER(posted_by_email) = ?', [$userEmail]);
                    $canFilterByPoster = true;
                }
            });

            if ($canFilterByPoster) {
                $jobIds = $jobQuery
                    ->pluck('id')
                    ->map(fn ($value) => (string) $value)
                    ->values()
                    ->all();
            }
        }

        if (Schema::hasTable('applications')) {
            $applicationQuery = DB::table('applications')->select('id');
            $hasAppFilter = false;

            $applicationQuery->where(function ($query) use ($userId, $userEmail, $jobIds, &$hasAppFilter) {
                if (Schema::hasColumn('applications', 'applicant_id')) {
                    $query->orWhere('applicant_id', $userId);
                    $hasAppFilter = true;
                }
                if ($userEmail !== '' && Schema::hasColumn('applications', 'applicant_email')) {
                    $query->orWhereRaw('LOWER(applicant_email) = ?', [$userEmail]);
                    $hasAppFilter = true;
                }
                if (!empty($jobIds) && Schema::hasColumn('applications', 'job_id')) {
                    $query->orWhereIn('job_id', $jobIds);
                    $hasAppFilter = true;
                }
            });

            if ($hasAppFilter) {
                $applicationIds = $applicationQuery
                    ->pluck('id')
                    ->map(fn ($value) => (string) $value)
                    ->values()
                    ->all();

                if (!empty($applicationIds)) {
                    DB::table('applications')->whereIn('id', $applicationIds)->delete();
                }
            }
        }

        if (Schema::hasTable('interview_schedules')) {
            $scheduleQuery = DB::table('interview_schedules');
            $hasScheduleFilter = false;

            $scheduleQuery->where(function ($query) use ($userId, $userEmail, $applicationIds, &$hasScheduleFilter) {
                if (Schema::hasColumn('interview_schedules', 'created_by_uid')) {
                    $query->orWhere('created_by_uid', $userId);
                    $hasScheduleFilter = true;
                }
                if (Schema::hasColumn('interview_schedules', 'applicant_id')) {
                    $query->orWhere('applicant_id', $userId);
                    $hasScheduleFilter = true;
                }
                if ($userEmail !== '' && Schema::hasColumn('interview_schedules', 'applicant_email')) {
                    $query->orWhereRaw('LOWER(applicant_email) = ?', [$userEmail]);
                    $hasScheduleFilter = true;
                }
                if (!empty($applicationIds) && Schema::hasColumn('interview_schedules', 'application_id')) {
                    $query->orWhereIn('application_id', $applicationIds);
                    $hasScheduleFilter = true;
                }
            });

            if ($hasScheduleFilter) {
                $scheduleQuery->delete();
            }
        }

        if (!empty($jobIds) && Schema::hasTable('jobs')) {
            DB::table('jobs')->whereIn('id', $jobIds)->delete();
        }

        if (Schema::hasTable('applicant_profiles')) {
            DB::table('applicant_profiles')->where('user_id', $userId)->delete();
        }
    }
}
