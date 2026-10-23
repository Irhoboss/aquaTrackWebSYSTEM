<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Reading;
use App\Support\Setting;
use Carbon\Carbon;

class ReadingIngestService
{
    /** @return array{0: Reading, 1: bool} [reading, wasCreated] */
    public static function ingest(Device $device, array $d): array
    {
        $sensor = $d['sensor_status'] ?? 'ok';
        $ph = isset($d['ph_value']) && $d['ph_value'] !== '' ? (float) $d['ph_value'] : null;
        $status = PhClassifier::classify($ph, $sensor);
        $measured = !empty($d['measured_at']) ? Carbon::parse($d['measured_at'])->utc() : now()->utc();

        $reading = Reading::firstOrCreate(
            ['device_id' => $device->id, 'measured_at' => $measured->format('Y-m-d H:i:s')],
            [
                'ph_value' => $ph,
                'temperature' => $d['temperature'] ?? null,
                'raw_voltage_mv' => $d['raw_voltage_mv'] ?? null,
                'sensor_status' => $sensor,
                'status' => $status,
                'received_at' => now()->utc(),
            ]
        );

        if ($reading->wasRecentlyCreated && $measured->gte(now()->subMinutes((int) Setting::get('offline_minutes')))) {
            self::alerts($device, $reading);
        }
        return [$reading, $reading->wasRecentlyCreated];
    }

    private static function alerts(Device $device, Reading $r): void
    {
        $k = "ph:{$device->id}";
        $s = "sensor:{$device->id}";
        $ctx = ['device_id' => $device->id, 'related_type' => 'device', 'related_id' => $device->id, 'related_label' => $device->name];
        AlertService::recover("offline:{$device->id}", 'Auto-resolved: the device is reporting again.');

        switch ($r->status) {
            case 'low':
                AlertService::recover([$s, "$k:high"]);
                AlertService::raise('ph_low', 'warning', "Low pH at {$device->name}",
                    "pH {$r->ph_value} is below the configured minimum of ".Setting::get('ph_min').'.', "$k:low", $ctx);
                break;
            case 'high':
                AlertService::recover([$s, "$k:low"]);
                AlertService::raise('ph_high', 'warning', "High pH at {$device->name}",
                    "pH {$r->ph_value} is above the configured maximum of ".Setting::get('ph_max').'.', "$k:high", $ctx);
                break;
            case 'normal':
                AlertService::recover(["$k:low", "$k:high", $s]);
                break;
            default: // invalid | sensor_error
                AlertService::raise('sensor_invalid', 'critical', "Sensor problem at {$device->name}",
                    $r->status === 'sensor_error'
                        ? "The device reported sensor status \"{$r->sensor_status}\". Check the probe and wiring."
                        : 'The device sent a missing or impossible pH value. Check the probe, wiring and calibration.',
                    $s, $ctx);
        }
    }
}
