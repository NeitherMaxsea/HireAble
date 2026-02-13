<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LogController extends Controller
{
    public function index(): JsonResponse
    {
        if (!DB::getSchemaBuilder()->hasTable('logs')) {
            return response()->json([]);
        }

        $rows = DB::table('logs')->orderByDesc('created_at')->limit(500)->get();

        return response()->json($rows->map(function ($row) {
            return [
                'id' => (string) $row->id,
                'source' => $row->source,
                'eventType' => $row->event_type,
                'account' => $row->account,
                'details' => $row->details,
                'createdAt' => (string) $row->created_at,
                'updatedAt' => (string) $row->updated_at,
            ];
        }));
    }
}
