<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
        if ($request->filled('financeApprovalStatus') && $this->hasJobsColumn('finance_approval_status')) {
            $query->where('finance_approval_status', (string) $request->input('financeApprovalStatus'));
        }

        return response()->json(
            $query->orderByDesc('created_at')->get()->map(fn ($row) => $this->normalizeRow($row))
        );
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->all();
        $id = DB::table('jobs')->insertGetId($this->toDbPayload($payload, true));
        $job = DB::table('jobs')->where('id', $id)->first();

        $this->writeLog(
            'job_posted',
            $payload['postedByEmail'] ?? $payload['postedByName'] ?? (string) $id,
            $payload['title'] ?? 'Untitled job'
        );

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

        $financeDecision = (string) $request->input('financeApprovalStatus', '');
        if ($financeDecision !== '') {
            $this->writeLog(
                'job_finance_reviewed',
                (string) $request->input('financeReviewedByEmail', 'finance'),
                sprintf(
                    '%s marked as %s',
                    $updated->title ?? 'Job',
                    $financeDecision
                )
            );
        } else {
            $this->writeLog(
                'job_updated',
                $request->input('postedByEmail', 'system'),
                $request->input('title', $updated->title ?? 'Job updated')
            );
        }

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
        $images = null;
        if (array_key_exists('images', $payload)) {
            if (is_array($payload['images'])) {
                $images = json_encode($payload['images']);
            } elseif (is_string($payload['images'])) {
                $images = $payload['images'];
            }
        }

        $mapped = [
            'title' => $payload['title'] ?? null,
            'location' => $payload['location'] ?? null,
            'exact_address' => $payload['exactAddress'] ?? ($payload['exact_address'] ?? null),
            'type' => $payload['type'] ?? null,
            'description' => $payload['description'] ?? null,
            'qualifications' => $payload['qualifications'] ?? null,
            'disability_type' => $payload['disabilityType'] ?? null,
            'accommodations' => $payload['accommodations'] ?? null,
            'salary' => $payload['salary'] ?? null,
            'benefits' => $payload['benefits'] ?? null,
            'image_url' => $payload['imageUrl'] ?? null,
            'image_url2' => $payload['imageUrl2'] ?? null,
            'images' => $images,
            'status' => array_key_exists('status', $payload)
                ? $payload['status']
                : ($isCreate ? 'pending_finance_review' : null),
            'finance_approval_status' => array_key_exists('financeApprovalStatus', $payload)
                ? $payload['financeApprovalStatus']
                : ($isCreate ? 'pending' : null),
            'finance_approval_note' => array_key_exists('financeApprovalNote', $payload)
                ? $payload['financeApprovalNote']
                : null,
            'finance_rejection_reason' => array_key_exists('financeRejectionReason', $payload)
                ? $payload['financeRejectionReason']
                : null,
            'finance_reviewed_by_uid' => array_key_exists('financeReviewedByUid', $payload)
                ? $payload['financeReviewedByUid']
                : null,
            'finance_reviewed_by_name' => array_key_exists('financeReviewedByName', $payload)
                ? $payload['financeReviewedByName']
                : null,
            'finance_reviewed_by_email' => array_key_exists('financeReviewedByEmail', $payload)
                ? $payload['financeReviewedByEmail']
                : null,
            'finance_reviewed_at' => array_key_exists('financeReviewedAt', $payload)
                ? $payload['financeReviewedAt']
                : null,
            'published_at' => array_key_exists('publishedAt', $payload)
                ? $payload['publishedAt']
                : null,
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

        $available = array_flip($this->availableJobsColumns());
        $filtered = array_filter(
            $mapped,
            fn ($value, $key) => $value !== null && isset($available[$key]),
            ARRAY_FILTER_USE_BOTH
        );

        return $filtered;
    }

    private function normalizeRow(object $row): array
    {
        return [
            'id' => (string) $row->id,
            'title' => $this->rowValue($row, 'title'),
            'location' => $this->rowValue($row, 'location'),
            'exactAddress' => $this->rowValue($row, 'exact_address'),
            'type' => $this->rowValue($row, 'type'),
            'description' => $this->rowValue($row, 'description'),
            'qualifications' => $this->rowValue($row, 'qualifications'),
            'disabilityType' => $this->rowValue($row, 'disability_type'),
            'accommodations' => $this->rowValue($row, 'accommodations'),
            'salary' => $this->rowValue($row, 'salary'),
            'benefits' => $this->rowValue($row, 'benefits'),
            'imageUrl' => $this->rowValue($row, 'image_url'),
            'imageUrl2' => $this->rowValue($row, 'image_url2'),
            'images' => $this->rowValue($row, 'images') ? json_decode((string) $this->rowValue($row, 'images'), true) : [],
            'status' => $this->rowValue($row, 'status'),
            'financeApprovalStatus' => $this->rowValue($row, 'finance_approval_status', 'pending'),
            'financeApprovalNote' => $this->rowValue($row, 'finance_approval_note'),
            'financeRejectionReason' => $this->rowValue($row, 'finance_rejection_reason'),
            'financeReviewedByUid' => $this->rowValue($row, 'finance_reviewed_by_uid'),
            'financeReviewedByName' => $this->rowValue($row, 'finance_reviewed_by_name'),
            'financeReviewedByEmail' => $this->rowValue($row, 'finance_reviewed_by_email'),
            'financeReviewedAt' => $this->rowValue($row, 'finance_reviewed_at') ? (string) $this->rowValue($row, 'finance_reviewed_at') : null,
            'publishedAt' => $this->rowValue($row, 'published_at') ? (string) $this->rowValue($row, 'published_at') : null,
            'postedByName' => $this->rowValue($row, 'posted_by_name'),
            'postedByEmail' => $this->rowValue($row, 'posted_by_email'),
            'postedByRole' => $this->rowValue($row, 'posted_by_role'),
            'postedByUid' => $this->rowValue($row, 'posted_by_uid'),
            'companyId' => $this->rowValue($row, 'company_id'),
            'companyName' => $this->rowValue($row, 'company_name'),
            'createdByCompanyAdminUid' => $this->rowValue($row, 'created_by_company_admin_uid'),
            'createdAt' => $this->rowValue($row, 'created_at') ? (string) $this->rowValue($row, 'created_at') : null,
            'updatedAt' => $this->rowValue($row, 'updated_at') ? (string) $this->rowValue($row, 'updated_at') : null,
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

    private function availableJobsColumns(): array
    {
        static $columns = null;
        if ($columns !== null) {
            return $columns;
        }

        if (!Schema::hasTable('jobs')) {
            $columns = [];
            return $columns;
        }

        $columns = Schema::getColumnListing('jobs');
        return $columns;
    }

    private function hasJobsColumn(string $column): bool
    {
        return in_array($column, $this->availableJobsColumns(), true);
    }

    private function rowValue(object $row, string $key, mixed $default = null): mixed
    {
        return property_exists($row, $key) ? $row->{$key} : $default;
    }
}
