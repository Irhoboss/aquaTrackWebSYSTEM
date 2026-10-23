<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReadingIngestService;
use App\Support\Setting;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DeviceApiController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $device = $request->attributes->get('device');

        $v = Validator::make($request->all(), [
            'device_id' => ['required', 'string', 'max:64'],
            'ph_value' => ['nullable', 'numeric', 'between:-999,999'],
            'temperature' => ['nullable', 'numeric', 'between:-50,150'],
            'measured_at' => ['nullable', 'date'],
            'sensor_status' => ['nullable', 'in:ok,disconnected,error'],
            'raw_voltage_mv' => ['nullable', 'integer', 'between:0,6000'],
            'firmware' => ['nullable', 'string', 'max:32'],
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $v->errors()], 422);
        }
        $data = $v->validated();

        if ($data['device_id'] !== $device->device_id) {
            return response()->json(['success' => false, 'message' => 'Token does not belong to this device_id.'], 403);
        }

        if (!empty($data['measured_at'])) {
            $at = Carbon::parse($data['measured_at']);
            if ($at->gt(now()->addMinutes(5))) {
                return response()->json(['success' => false, 'message' => 'measured_at is in the future.', 'errors' => ['measured_at' => ['Timestamp is in the future.']]], 422);
            }
            if ($at->lt(now()->subDays(7))) {
                return response()->json(['success' => false, 'message' => 'measured_at is older than 7 days.', 'errors' => ['measured_at' => ['Timestamp is too old.']]], 422);
            }
        }

        if (!empty($data['firmware'])) {
            $device->update(['firmware' => $data['firmware']]);
        }

        try {
            [$reading, $created] = ReadingIngestService::ingest($device, $data);
        } catch (\Throwable $e) {
            Log::error('Reading ingest failed', ['device' => $device->device_id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Server error while saving the reading.'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => $created ? 'Reading stored.' : 'Duplicate reading ignored.',
            'data' => ['id' => $reading->id, 'status' => $reading->status, 'ph_value' => $reading->ph_value, 'measured_at' => $reading->measured_at->toIso8601String()],
        ], $created ? 201 : 200);
    }

    /** Lightweight "I'm alive" ping. Also tells the device how often to report. */
    public function heartbeat(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'server_time' => now()->toIso8601String(),
            'reporting_interval_seconds' => (int) Setting::get('reporting_interval_seconds'),
        ]);
    }
}
