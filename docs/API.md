# AquaTrack device API

Base URL: `http://<server>:8000/api/v1`   ·   Auth: `Authorization: Bearer <device token>`

Tokens are created in **Settings → ESP32 devices** (or by the seeder). Only a SHA-256 hash is stored; a token can be revoked or regenerated at any time.

## POST /readings
```json
{
  "device_id": "AQUATRACK-ESP32-001",
  "ph_value": 7.12,
  "temperature": null,
  "measured_at": "2026-10-23T08:00:00+08:00",
  "sensor_status": "ok",
  "raw_voltage_mv": 2480,
  "firmware": "1.0.0"
}
```
| Field | Rules |
|---|---|
| device_id | required, must match the token's device |
| ph_value | nullable number. Missing/impossible → stored as *invalid reading* (never "safe") |
| temperature | optional (-50…150), leave `null` unless a sensor exists |
| measured_at | optional ISO-8601; not >5 min in the future, not >7 days old. Omitted → server time |
| sensor_status | `ok` (default) · `disconnected` · `error` |

| HTTP | Meaning |
|---|---|
| 201 | stored |
| 200 | duplicate (same device + `measured_at`) ignored |
| 401 | missing / invalid / revoked token |
| 403 | token does not belong to `device_id` |
| 422 | validation failed (`errors` object) |
| 429 | rate limit (30 requests/min per device) |
| 500 | server error (details only in `storage/logs`) |

```bash
curl -X POST http://127.0.0.1:8000/api/v1/readings \
  -H "Authorization: Bearer YOUR_DEVICE_TOKEN" -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"device_id":"AQUATRACK-ESP32-001","ph_value":7.12,"temperature":null,"measured_at":"2026-10-23T08:00:00+08:00"}'
```

## POST /heartbeat
Updates `last_seen_at`; returns `server_time` and `reporting_interval_seconds`.
