<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JobController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DB::table('jobs');

        if ($request->filled('companyId')) {
            $query->where('company_id', (string) $request->input('companyId'));
        }
        if ($request->filled('postedByUid')) {
            $query->where('posted_by_uid', (string) $request->input('postedByUid'));
        }
        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        return response()->json($query->orderByDesc('created_at')->get()->map(fn ($row) => $this->normalizeRow($row)));
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->all();
        $id = DB::table('jobs')->insertGetId($this->toDbPayload($payload, true));
        $job = DB::table('jobs')->where('id', $id)->first();

        $this->writeLog('job_posted', $payload['postedByEmail'] ?? $payload['postedByName'] ?? (string) $id, $payload['title'] ?? 'Untitled job');

        return response()->json($this->normalizeRow($job), 201);
    }

    public function show(string $id): JsonResponse
    {
        $job = DB::table('jobs')->where('id', $id)->first();
        if (!$job) {
            return response()->json(['message' => 'Job not found'], 404);
        }

        return response()->json($this->normalizeRow($job));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $job = DB::table('jobs')->where('id', $id)->first();
        if (!$job) {
            return response()->json(['message' => 'Job not found'], 404);
        }

        DB::table('jobs')->where('id', $id)->update($this->toDbPayload($request->all(), false));
        $updated = DB::table('jobs')->where('id', $id)->first();

        $this->writeLog('job_updated', $request->input('postedByEmail', 'system'), $request->input('title', $updated->title ?? 'Job updated'));

        return response()->json($this->normalizeRow($updated));
    }

    public function destroy(string $id): JsonResponse
    {
        $job = DB::table('jobs')->where('id', $id)->first();
        if (!$job) {
            return response()->json(['message' => 'Job not found'], 404);
        }

        DB::table('jobs')->where('id', $id)->delete();
        $this->writeLog('job_deleted', 'system', $job->title ?? "Job {$id}");

        return response()->json(['ok' => true]);
    }

    private function toDbPayload(array $payload, bool $isCreate): array
    {
        $mapped = [
            'title' => $payload['title'] ?? null,
            'location' => $payload['location'] ?? null,
            'type' => $payload['type'] ?? null,
            'description' => $payload['description'] ?? null,
            'qualifications' => $payload['qualifications'] ?? null,
            'disability_type' => $payload['disabilityType'] ?? null,
            'accommodations' => $payload['accommodations'] ?? null,
            'salary' => $payload['salary'] ?? null,
            'benefits' => $payload['benefits'] ?? null,
            'image_url' => $payload['imageUrl'] ?? null,
            'image_url2' => $payload['imageUrl2'] ?? null,
            'images' => isset($payload['images']) ? json_encode($payload['images']) : null,
            'status' => $payload['status'] ?? 'open',
            'posted_by_name' => $payload['postedByName'] ?? null,
            'posted_by_email' => $payload['postedByEmail'] ?? null,
            'posted_by_role' => $payload['postedByRole'] ?? null,
            'posted_by_uid' => $payload['postedByUid'] ?? null,
            'company_id' => $payload['companyId'] ?? null,
            'company_name' => $payload['companyName'] ?? null,
            'created_by_company_admin_uid' => $payload['createdByCompanyAdminUid'] ?? null,
            'updated_at' => now(),
        ];

        if ($isCreate) {
            $mapped['created_at'] = now();
        }

        return array_filter($mapped, fn ($value) => $value !== null);
    }

    private function normalizeRow(object $row): array
    {
        return [
            'id' => (string) $row->id,
            'title' => $row->title,
            'location' => $row->location,
            'type' => $row->type,
            'description' => $row->description,
            'qualifications' => $row->qualifications,
            'disabilityType' => $row->disability_type,
            'accommodations' => $row->accommodations,
            'salary' => $row->salary,
            'benefits' => $row->benefits,
            'imageUrl' => $row->image_url,
            'imageUrl2' => $row->image_url2,
            'images' => $row->images ? json_decode($row->images, true) : [],
            'status' => $row->status,
            'postedByName' => $row->posted_by_name,
            'postedByEmail' => $row->posted_by_email,
            'postedByRole' => $row->posted_by_role,
            'postedByUid' => $row->posted_by_uid,
            'companyId' => $row->company_id,
            'companyName' => $row->company_name,
            'createdByCompanyAdminUid' => $row->created_by_company_admin_uid,
            'createdAt' => (string) $row->created_at,
            'updatedAt' => (string) $row->updated_at,
        ];
    }

    private function writeLog(string $eventType, string $account, string $details): void
    {
        if (!DB::getSchemaBuilder()->hasTable('logs')) {
            return;
        }

        DB::table('logs')->insert([
            'source' => 'jobs',
            'event_type' => $eventType,
            'account' => $account,
            'details' => $details,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
