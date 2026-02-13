<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApplicationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DB::table('applications');

        if ($request->filled('jobId')) {
            $query->where('job_id', (string) $request->input('jobId'));
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

        $id = DB::table('applications')->insertGetId([
            'job_id' => (string) $payload['jobId'],
            'job_title' => $payload['jobTitle'] ?? null,
            'applicant_id' => array_key_exists('applicantId', $payload) ? (string) $payload['applicantId'] : null,
            'applicant_name' => $payload['applicantName'] ?? null,
            'applicant_email' => $payload['applicantEmail'] ?? null,
            'status' => $payload['status'] ?? 'pending',
            'applied_at' => $payload['appliedAt'] ?? now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['id' => (string) $id], 201);
    }
}
