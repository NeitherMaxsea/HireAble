<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InterviewScheduleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DB::table('interview_schedules');

        if ($request->filled('applicationId')) {
            $query->where('application_id', (string) $request->input('applicationId'));
        }
        if ($request->filled('applicantId')) {
            $query->where('applicant_id', (string) $request->input('applicantId'));
        }
        if ($request->filled('applicantEmail')) {
            $query->where('applicant_email', (string) $request->input('applicantEmail'));
        }
        if ($request->filled('companyId')) {
            $query->where('company_id', (string) $request->input('companyId'));
        }
        if ($request->filled('createdByUid')) {
            $query->where('created_by_uid', (string) $request->input('createdByUid'));
        }
        if ($request->filled('scheduleStatus')) {
            $query->where('schedule_status', (string) $request->input('scheduleStatus'));
        }
        if ($request->filled('applicantResponse') && Schema::hasColumn('interview_schedules', 'applicant_response')) {
            $query->where('applicant_response', (string) $request->input('applicantResponse'));
        }

        $rows = $query->orderBy('scheduled_at')->orderByDesc('created_at')->get();

        return response()->json($rows->map(fn ($row) => $this->toResponse($row)));
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'applicationId' => ['nullable', 'string', 'max:255'],
            'jobTitle' => ['nullable', 'string', 'max:255'],
            'companyId' => ['nullable', 'string', 'max:255'],
            'companyName' => ['nullable', 'string', 'max:255'],
            'createdByUid' => ['nullable', 'string', 'max:255'],
            'applicantId' => ['nullable', 'string', 'max:255'],
            'applicantName' => ['nullable', 'string', 'max:255'],
            'applicantEmail' => ['nullable', 'string', 'max:255'],
            'interviewType' => ['required', 'string', 'max:50'],
            'scheduledAt' => ['required'],
            'mode' => ['required', 'string', 'max:50'],
            'locationOrLink' => ['nullable', 'string'],
            'interviewer' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'scheduleStatus' => ['nullable', 'string', 'max:50'],
            'applicantResponse' => ['nullable', 'string', 'max:50'],
            'applicantResponseNote' => ['nullable', 'string'],
            'applicantResponseAt' => ['nullable'],
        ]);

        $scheduledAt = Carbon::parse((string) $payload['scheduledAt'])->toDateTimeString();
        $applicationId = trim((string) ($payload['applicationId'] ?? ''));
        $interviewType = strtolower(trim((string) ($payload['interviewType'] ?? '')));

        if ($applicationId !== '' && $interviewType !== '') {
            $duplicateExists = DB::table('interview_schedules')
                ->where('application_id', $applicationId)
                ->whereRaw('LOWER(interview_type) = ?', [$interviewType])
                ->whereRaw("LOWER(COALESCE(schedule_status, 'scheduled')) != 'cancelled'")
                ->exists();

            if ($duplicateExists) {
                return response()->json([
                    'message' => 'An active interview schedule already exists for this applicant and interview type.',
                ], 409);
            }
        }

        $insert = [
            'application_id' => $applicationId !== '' ? $applicationId : null,
            'job_title' => $payload['jobTitle'] ?? null,
            'company_id' => $payload['companyId'] ?? null,
            'company_name' => $payload['companyName'] ?? null,
            'created_by_uid' => $payload['createdByUid'] ?? null,
            'applicant_id' => $payload['applicantId'] ?? null,
            'applicant_name' => $payload['applicantName'] ?? null,
            'applicant_email' => $payload['applicantEmail'] ?? null,
            'interview_type' => $payload['interviewType'],
            'scheduled_at' => $scheduledAt,
            'mode' => $payload['mode'],
            'location_or_link' => $payload['locationOrLink'] ?? null,
            'interviewer' => $payload['interviewer'],
            'notes' => $payload['notes'] ?? null,
            'schedule_status' => $payload['scheduleStatus'] ?? 'scheduled',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('interview_schedules', 'applicant_response')) {
            $insert['applicant_response'] = $payload['applicantResponse'] ?? null;
        }
        if (Schema::hasColumn('interview_schedules', 'applicant_response_note')) {
            $insert['applicant_response_note'] = $payload['applicantResponseNote'] ?? null;
        }
        if (Schema::hasColumn('interview_schedules', 'applicant_response_at')) {
            $insert['applicant_response_at'] = array_key_exists('applicantResponseAt', $payload)
                ? Carbon::parse((string) $payload['applicantResponseAt'])->toDateTimeString()
                : null;
        }

        $id = DB::table('interview_schedules')->insertGetId($insert);

        $created = DB::table('interview_schedules')->where('id', $id)->first();
        return response()->json($this->toResponse($created), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $row = DB::table('interview_schedules')->where('id', $id)->first();
        if (!$row) {
            return response()->json(['message' => 'Interview schedule not found'], 404);
        }

        $payload = $request->validate([
            'interviewType' => ['nullable', 'string', 'max:50'],
            'scheduledAt' => ['nullable'],
            'mode' => ['nullable', 'string', 'max:50'],
            'locationOrLink' => ['nullable', 'string'],
            'interviewer' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'scheduleStatus' => ['nullable', 'string', 'max:50'],
            'applicantResponse' => ['nullable', 'string', 'max:50'],
            'applicantResponseNote' => ['nullable', 'string'],
            'applicantResponseAt' => ['nullable'],
        ]);

        $updates = [];
        if (array_key_exists('interviewType', $payload)) $updates['interview_type'] = $payload['interviewType'];
        if (array_key_exists('scheduledAt', $payload)) $updates['scheduled_at'] = Carbon::parse((string) $payload['scheduledAt'])->toDateTimeString();
        if (array_key_exists('mode', $payload)) $updates['mode'] = $payload['mode'];
        if (array_key_exists('locationOrLink', $payload)) $updates['location_or_link'] = $payload['locationOrLink'];
        if (array_key_exists('interviewer', $payload)) $updates['interviewer'] = $payload['interviewer'];
        if (array_key_exists('notes', $payload)) $updates['notes'] = $payload['notes'];
        if (array_key_exists('scheduleStatus', $payload)) $updates['schedule_status'] = $payload['scheduleStatus'];
        if (array_key_exists('applicantResponse', $payload) && Schema::hasColumn('interview_schedules', 'applicant_response')) {
            $updates['applicant_response'] = $payload['applicantResponse'];
        }
        if (array_key_exists('applicantResponseNote', $payload) && Schema::hasColumn('interview_schedules', 'applicant_response_note')) {
            $updates['applicant_response_note'] = $payload['applicantResponseNote'];
        }
        if (array_key_exists('applicantResponseAt', $payload) && Schema::hasColumn('interview_schedules', 'applicant_response_at')) {
            $updates['applicant_response_at'] = Carbon::parse((string) $payload['applicantResponseAt'])->toDateTimeString();
        }

        if (!empty($updates)) {
            $updates['updated_at'] = now();
            DB::table('interview_schedules')->where('id', $id)->update($updates);
        }

        $updated = DB::table('interview_schedules')->where('id', $id)->first();
        return response()->json($this->toResponse($updated));
    }

    public function destroy(string $id): JsonResponse
    {
        $row = DB::table('interview_schedules')->where('id', $id)->first();
        if (!$row) {
            return response()->json(['message' => 'Interview schedule not found'], 404);
        }

        DB::table('interview_schedules')->where('id', $id)->delete();
        return response()->json(['ok' => true]);
    }

    private function toResponse(object $row): array
    {
        return [
            'id' => (string) $row->id,
            'applicationId' => (string) ($row->application_id ?? ''),
            'jobTitle' => (string) ($row->job_title ?? ''),
            'companyId' => (string) ($row->company_id ?? ''),
            'companyName' => (string) ($row->company_name ?? ''),
            'createdByUid' => (string) ($row->created_by_uid ?? ''),
            'applicantId' => (string) ($row->applicant_id ?? ''),
            'applicantName' => (string) ($row->applicant_name ?? ''),
            'applicantEmail' => (string) ($row->applicant_email ?? ''),
            'interviewType' => (string) ($row->interview_type ?? ''),
            'scheduledAt' => (string) ($row->scheduled_at ?? ''),
            'mode' => (string) ($row->mode ?? ''),
            'locationOrLink' => (string) ($row->location_or_link ?? ''),
            'interviewer' => (string) ($row->interviewer ?? ''),
            'notes' => (string) ($row->notes ?? ''),
            'scheduleStatus' => (string) ($row->schedule_status ?? 'scheduled'),
            'applicantResponse' => Schema::hasColumn('interview_schedules', 'applicant_response')
                ? (string) ($row->applicant_response ?? '')
                : '',
            'applicantResponseNote' => Schema::hasColumn('interview_schedules', 'applicant_response_note')
                ? (string) ($row->applicant_response_note ?? '')
                : '',
            'applicantResponseAt' => Schema::hasColumn('interview_schedules', 'applicant_response_at')
                ? (string) ($row->applicant_response_at ?? '')
                : '',
            'createdAt' => (string) ($row->created_at ?? ''),
            'updatedAt' => (string) ($row->updated_at ?? ''),
        ];
    }
}
