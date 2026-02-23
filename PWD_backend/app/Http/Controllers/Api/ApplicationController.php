<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ApplicationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DB::table('applications');

        if ($request->filled('jobId')) {
            $query->where('job_id', (string) $request->input('jobId'));
        }

        if ($request->filled('applicantId')) {
            $query->where('applicant_id', (string) $request->input('applicantId'));
        }

        if ($request->filled('applicantEmail')) {
            $query->where('applicant_email', (string) $request->input('applicantEmail'));
        }

        if ($request->filled('companyId') || $request->filled('postedByUid')) {
            $jobQuery = DB::table('jobs')->select('id');

            if ($request->filled('companyId')) {
                $jobQuery->where('company_id', (string) $request->input('companyId'));
            }

            if ($request->filled('postedByUid')) {
                $jobQuery->where('posted_by_uid', (string) $request->input('postedByUid'));
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

        $id = DB::table('applications')->insertGetId([
            'job_id' => $jobId,
            'job_title' => $payload['jobTitle'] ?? null,
            'applicant_id' => $applicantId !== '' ? $applicantId : null,
            'applicant_name' => $payload['applicantName'] ?? null,
            'applicant_email' => $applicantEmail !== '' ? $applicantEmail : null,
            'status' => $payload['status'] ?? 'pending',
            'applied_at' => $appliedAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

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
        ]);

        DB::table('applications')->where('id', $id)->update([
            'status' => trim((string) $payload['status']),
            'updated_at' => now(),
        ]);

        $updated = DB::table('applications')->where('id', $id)->first();

        return response()->json([
            'id' => (string) $updated->id,
            'jobId' => $updated->job_id,
            'jobTitle' => $updated->job_title,
            'applicantId' => $updated->applicant_id,
            'applicantName' => $updated->applicant_name,
            'applicantEmail' => $updated->applicant_email,
            'status' => $updated->status,
            'appliedAt' => (string) $updated->applied_at,
            'createdAt' => (string) $updated->created_at,
            'updatedAt' => (string) $updated->updated_at,
        ]);
    }
}
