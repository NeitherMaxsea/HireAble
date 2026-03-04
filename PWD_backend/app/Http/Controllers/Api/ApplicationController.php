<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ApplicationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DB::table('applications');

        if ($request->filled('jobId')) {
            $query->where('job_id', (string) $request->input('jobId'));
        }

        $hasApplicantId = $request->filled('applicantId');
        $hasApplicantEmail = $request->filled('applicantEmail');
        if ($hasApplicantId || $hasApplicantEmail) {
            $query->where(function ($builder) use ($request, $hasApplicantId, $hasApplicantEmail) {
                if ($hasApplicantId) {
                    $builder->orWhere('applicant_id', (string) $request->input('applicantId'));
                }
                if ($hasApplicantEmail) {
                    $builder->orWhere('applicant_email', (string) $request->input('applicantEmail'));
                }
            });
        }

        if ($request->filled('companyId') || $request->filled('postedByUid')) {
            if (!Schema::hasTable('jobs')) {
                return response()->json([]);
            }

            $jobQuery = DB::table('jobs')->select('id');
            $appliedJobScopeFilter = false;

            if ($request->filled('companyId') && Schema::hasColumn('jobs', 'company_id')) {
                $jobQuery->where('company_id', (string) $request->input('companyId'));
                $appliedJobScopeFilter = true;
            }

            if ($request->filled('postedByUid') && Schema::hasColumn('jobs', 'posted_by_uid')) {
                $jobQuery->where('posted_by_uid', (string) $request->input('postedByUid'));
                $appliedJobScopeFilter = true;
            }

            if (!$appliedJobScopeFilter) {
                return response()->json([]);
            }

            $jobIds = $jobQuery
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->values()
                ->all();

            if (empty($jobIds)) {
                return response()->json([]);
            }

            $query->whereIn('job_id', $jobIds);
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        $rows = $query->orderByDesc('created_at')->get();

        return response()->json($rows->map(function ($row) {
            return [
                'id' => (string) $row->id,
                'jobId' => $row->job_id,
                'jobTitle' => $row->job_title,
                'applicantId' => $row->applicant_id,
                'applicantName' => $row->applicant_name,
                'applicantEmail' => $row->applicant_email,
                'status' => $row->status,
                'rejectionReason' => $this->rowValue($row, 'rejection_reason'),
                'appliedAt' => (string) $row->applied_at,
                'createdAt' => (string) $row->created_at,
                'updatedAt' => (string) $row->updated_at,
            ];
        }));
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'jobId' => ['required'],
            'jobTitle' => ['nullable', 'string', 'max:255'],
            'applicantId' => ['nullable'],
            'applicantName' => ['nullable', 'string', 'max:255'],
            'applicantEmail' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
            'rejectionReason' => ['nullable', 'string', 'max:2000'],
            'appliedAt' => ['nullable'],
        ]);

        $jobId = trim((string) $payload['jobId']);
        $applicantId = trim((string) ($payload['applicantId'] ?? ''));
        $applicantEmail = trim((string) ($payload['applicantEmail'] ?? ''));

        $duplicateQuery = DB::table('applications')->where('job_id', $jobId);
        if ($applicantId !== '') {
            $duplicateQuery->where('applicant_id', $applicantId);
        } elseif ($applicantEmail !== '') {
            $duplicateQuery->where('applicant_email', $applicantEmail);
        } else {
            $duplicateQuery = null;
        }

        if ($duplicateQuery && $duplicateQuery->exists()) {
            return response()->json(['message' => 'You already applied for this job.'], 409);
        }

        $appliedAt = $payload['appliedAt'] ?? null;
        if ($appliedAt !== null) {
            try {
                $appliedAt = Carbon::parse((string) $appliedAt)->toDateTimeString();
            } catch (\Throwable $e) {
                $appliedAt = now()->toDateTimeString();
            }
        } else {
            $appliedAt = now()->toDateTimeString();
        }

        $insert = [
            'job_id' => $jobId,
            'job_title' => $payload['jobTitle'] ?? null,
            'applicant_id' => $applicantId !== '' ? $applicantId : null,
            'applicant_name' => $payload['applicantName'] ?? null,
            'applicant_email' => $applicantEmail !== '' ? $applicantEmail : null,
            'status' => $payload['status'] ?? 'pending',
            'applied_at' => $appliedAt,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('applications', 'rejection_reason')) {
            $insert['rejection_reason'] = $payload['rejectionReason'] ?? null;
        }

        $id = DB::table('applications')->insertGetId($insert);

        return response()->json(['id' => (string) $id], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $existing = DB::table('applications')->where('id', $id)->first();
        if (!$existing) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        $payload = $request->validate([
            'status' => ['required', 'string', 'max:50'],
            'rejectionReason' => ['nullable', 'string', 'max:2000'],
        ]);

        $status = strtolower(trim((string) $payload['status']));
        $reason = trim((string) ($payload['rejectionReason'] ?? ''));

        if ($status === 'accepted' && strtolower(trim((string) $existing->status)) !== 'accepted') {
            $vacancies = $this->resolveJobVacancies((string) $existing->job_id);
            $acceptedCount = DB::table('applications')
                ->where('job_id', (string) $existing->job_id)
                ->where('id', '!=', (string) $existing->id)
                ->whereIn('status', ['accepted', 'hired'])
                ->count();

            if ($acceptedCount >= $vacancies) {
                return response()->json([
                    'message' => "Hiring limit reached for this job ({$vacancies} max hire(s)).",
                ], 422);
            }
        }

        if ($status === 'rejected' && $reason === '') {
            return response()->json(['message' => 'Rejection reason is required.'], 422);
        }

        $updates = [
            'status' => $status,
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('applications', 'rejection_reason')) {
            $updates['rejection_reason'] = $status === 'rejected' ? $reason : null;
        }

        DB::table('applications')->where('id', $id)->update($updates);

        $updated = DB::table('applications')->where('id', $id)->first();

        return response()->json([
            'id' => (string) $updated->id,
            'jobId' => $updated->job_id,
            'jobTitle' => $updated->job_title,
            'applicantId' => $updated->applicant_id,
            'applicantName' => $updated->applicant_name,
            'applicantEmail' => $updated->applicant_email,
            'status' => $updated->status,
            'rejectionReason' => $this->rowValue($updated, 'rejection_reason'),
            'appliedAt' => (string) $updated->applied_at,
            'createdAt' => (string) $updated->created_at,
            'updatedAt' => (string) $updated->updated_at,
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $existing = DB::table('applications')->where('id', $id)->first();
        if (!$existing) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        DB::table('applications')->where('id', $id)->delete();
        return response()->json(['ok' => true]);
    }

    private function rowValue(object $row, string $key, mixed $default = null): mixed
    {
        return property_exists($row, $key) ? $row->{$key} : $default;
    }

    private function resolveJobVacancies(string $jobId): int
    {
        if ($jobId === '' || !Schema::hasTable('jobs')) {
            return 1;
        }

        $job = DB::table('jobs')->where('id', $jobId)->first();
        if (!$job) return 1;

        $raw = property_exists($job, 'vacancies')
            ? $job->vacancies
            : (property_exists($job, 'slots') ? $job->slots : 1);

        $parsed = (int) $raw;
        return $parsed > 0 ? $parsed : 1;
    }
}
