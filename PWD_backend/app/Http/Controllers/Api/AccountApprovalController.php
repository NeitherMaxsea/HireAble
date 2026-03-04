<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PhpMailerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountApprovalController extends Controller
{
    public function __construct(private readonly PhpMailerService $mailer)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $statusFilter = strtolower(trim((string) $request->input('status', 'pending_review')));

        $query = User::query()
            ->whereIn('role', ['applicant', 'company_admin']);

        if ($statusFilter !== 'all') {
            if ($statusFilter === 'active') {
                // "Approved" accounts may later become "inactive" after logout.
                $query->whereIn('status', ['active', 'inactive']);
            } else {
                $query->where('status', $statusFilter);
            }
        } else {
            $query->whereIn('status', ['pending_review', 'rejected', 'active', 'inactive']);
        }

        $rows = $query
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (User $user) => $this->mapRow($user))
            ->values();

        if ($statusFilter === 'pending_review' || $statusFilter === 'all') {
            $pendingRows = collect();
            if (Schema::hasTable('pending_registrations')) {
                $existingUserEmails = User::query()
                    ->whereNotNull('email')
                    ->pluck('email')
                    ->map(fn ($email) => strtolower(trim((string) $email)))
                    ->filter()
                    ->values()
                    ->all();
                $existingUserEmailLookup = array_fill_keys($existingUserEmails, true);

                $pendingRows = DB::table('pending_registrations')
                    ->orderByDesc('updated_at')
                    ->orderByDesc('created_at')
                    ->get()
                    ->filter(function ($row) {
                        $role = strtolower(trim((string) ($row->role ?? 'applicant')));
                        return in_array($role, ['applicant', 'company_admin'], true);
                    })
                    ->reject(function ($row) use ($existingUserEmailLookup) {
                        $email = strtolower(trim((string) ($row->email ?? '')));
                        return $email !== '' && isset($existingUserEmailLookup[$email]);
                    })
                    ->map(fn ($row) => $this->mapPendingRegistrationRow($row))
                    ->values();
            }

            $rows = $rows
                ->concat($pendingRows)
                ->sortByDesc(fn (array $row) => $row['_sortTs'] ?? $row['updatedAt'] ?? $row['createdAt'] ?? '')
                ->values()
                ->map(function (array $row) {
                    unset($row['_sortTs']);
                    return $row;
                })
                ->values();
        }

        return response()->json($rows);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if (!in_array(strtolower((string) $user->role), ['applicant', 'company_admin'], true)) {
            return response()->json(['message' => 'This account type is not part of registration approvals.'], 422);
        }

        $payload = $request->validate([
            'action' => ['required', 'string', 'in:accept,reject'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'reviewedByEmail' => ['nullable', 'string', 'max:255'],
        ]);

        $action = strtolower((string) $payload['action']);
        $reason = trim((string) ($payload['reason'] ?? ''));
        if ($action === 'reject' && $reason === '') {
            return response()->json(['message' => 'Rejection reason is required.'], 422);
        }

        $reviewerEmail = trim((string) ($payload['reviewedByEmail'] ?? 'system-admin'));
        $isAccepted = $action === 'accept';

        $user->status = $isAccepted ? 'active' : 'rejected';
        $user->is_active = $isAccepted;
        if (Schema::hasColumn('users', 'review_rejection_reason')) {
            $user->review_rejection_reason = $isAccepted ? null : $reason;
        }
        if (Schema::hasColumn('users', 'reviewed_at')) {
            $user->reviewed_at = now();
        }
        if (Schema::hasColumn('users', 'reviewed_by_email')) {
            $user->reviewed_by_email = $reviewerEmail ?: 'system-admin';
        }
        if (!$isAccepted) {
            $user->active_session_key = null;
            $user->session_last_seen_at = null;
            $user->last_logout_at = now();
        }
        $user->save();

        $this->sendDecisionEmail($user, $isAccepted, $reason);

        return response()->json($this->mapRow($user));
    }

    private function sendDecisionEmail(User $user, bool $accepted, string $reason = ''): void
    {
        $email = trim((string) $user->email);
        if ($email === '') return;

        $isApplicant = strtolower((string) $user->role) === 'applicant';
        $accountLabel = $isApplicant ? 'Applicant account' : 'Employer account';

        if ($accepted) {
            $subject = 'PWD Portal Registration Approved';
            $body = "Hello {$user->name},\n\nYour {$accountLabel} registration has been approved. You may now sign in to the platform.\n\nThank you.";
            $this->mailer->sendPlainText($email, $subject, $body);
            return;
        }

        $subject = 'PWD Portal Registration Update';
        $body = "Hello {$user->name},\n\nYour {$accountLabel} registration was not approved.\n\nReason: {$reason}\n\nPlease review and register again with the correct requirements.";
        $this->mailer->sendPlainText($email, $subject, $body);
    }

    private function mapRow(User $user): array
    {
        return [
            'id' => (string) $user->id,
            'recordSource' => 'users',
            'canDecide' => true,
            'name' => $user->name,
            'username' => $user->username,
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
            'profileCompleted' => (bool) ($user->profile_completed ?? false),
            'onboardingData' => $user->onboarding_data,
            'reviewRejectionReason' => $user->review_rejection_reason,
            'reviewedAt' => optional($user->reviewed_at)?->toISOString(),
            'reviewedByEmail' => $user->reviewed_by_email,
            'createdAt' => optional($user->created_at)?->toISOString(),
            'updatedAt' => optional($user->updated_at)?->toISOString(),
            '_sortTs' => optional($user->updated_at)?->toISOString() ?: optional($user->created_at)?->toISOString(),
        ];
    }

    private function mapPendingRegistrationRow(object $row): array
    {
        $role = strtolower(trim((string) ($row->role ?? 'applicant')));
        $createdAt = $this->normalizeDateString($row->created_at ?? null);
        $updatedAt = $this->normalizeDateString($row->updated_at ?? null);

        return [
            'id' => 'pending:' . md5(strtolower(trim((string) ($row->email ?? '')))),
            'recordSource' => 'pending_registrations',
            'canDecide' => false,
            'name' => $row->name ?? null,
            'username' => $row->name ?? null,
            'email' => $row->email ?? null,
            'role' => $role ?: 'applicant',
            'status' => 'pending_review',
            'isActive' => false,
            'contact' => $row->contact ?? null,
            'disability' => $row->disability ?? null,
            'photoURL' => null,
            'photoPath' => null,
            'companyId' => $row->company_id ?? null,
            'companyName' => $row->company_name ?? null,
            'companyAddress' => $row->company_address ?? null,
            'companyIndustry' => $row->company_industry ?? null,
            'profileCompleted' => false,
            'onboardingData' => null,
            'reviewRejectionReason' => null,
            'reviewedAt' => null,
            'reviewedByEmail' => null,
            'createdAt' => $createdAt,
            'updatedAt' => $updatedAt,
            'pendingOtpVerification' => true,
            '_sortTs' => $updatedAt ?: $createdAt,
        ];
    }

    private function normalizeDateString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        try {
            return \Illuminate\Support\Carbon::parse((string) $value)->toISOString();
        } catch (\Throwable) {
            return null;
        }
    }
}
