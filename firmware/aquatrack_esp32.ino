/*
 * AquaTrack ESP32 firmware  -  PH-4502C pH module -> Laravel API (HTTP POST)
 *
 * Board : ESP32 DevKit (ESP32-WROOM-32), Arduino core for ESP32 2.x / 3.x
 * Sensor: PH-4502C module + BNC pH probe, analog pin "Po"
 *
 * !!! WIRING / VOLTAGE (read docs/HARDWARE.md before powering) !!!
 *  - The PH-4502C is powered from 5 V and its Po output can swing up to ~5 V.
 *  - The ESP32 ADC is NOT 5 V tolerant (3.3 V max, linear only up to ~3.1 V with 11 dB attenuation).
 *  - Put a voltage divider between Po and the ADC pin:  Po --[R1 10k]--+--[R2 15k]-- GND,  ADC pin at the "+" node.
 *    Ratio = R2/(R1+R2) = 0.6  ->  5.0 V becomes 3.0 V.  Add a 100 nF capacitor from the ADC pin to GND.
 *  - ESP32 GND, module GND and probe ground must be common.
 *  - Use an ADC1 pin (GPIO32-39). ADC2 cannot be used while Wi-Fi is on.
 *  - Verify the real Po voltage with a multimeter first (probe in pH 7 buffer: roughly 2.5 V on most modules,
 *    adjust the offset trimmer if needed).  Never assume it is safe.
 */
#include <WiFi.h>
#include <HTTPClient.h>
#include <time.h>
#include <math.h>

// ======================= USER CONFIGURATION =======================
const char* WIFI_SSID     = "YOUR_WIFI_NAME";
const char* WIFI_PASSWORD = "YOUR_WIFI_PASSWORD";

// Laravel API. For XAMPP/artisan on your PC use its LAN IP (not "localhost"): e.g. php artisan serve --host=0.0.0.0
const char* API_URL       = "http://192.168.1.10:8000/api/v1/readings";
const char* DEVICE_ID     = "AQUATRACK-ESP32-001";
const char* DEVICE_TOKEN  = "PASTE_TOKEN_FROM_SEEDER_OR_SETTINGS";   // shown once in Settings > Devices
const char* FIRMWARE_VER  = "1.0.0";

const uint32_t SEND_INTERVAL_MS = 60000;   // how often to upload (Settings > "Reporting interval" is only guidance)

// ---- ADC / signal conditioning ----
const int   PH_PIN            = 34;        // ADC1_CH6, input-only pin
const float DIVIDER_RATIO     = 0.60f;     // R2/(R1+R2) of the divider described above. Set 1.0 ONLY if the module output is <= 3.0 V.
const int   SAMPLES           = 25;        // samples per measurement (median filtered)
const float EMA_ALPHA         = 0.25f;     // smoothing between measurements (0..1, lower = smoother)

// ---- Two-point calibration (module-side millivolts, i.e. BEFORE the divider) ----
// 1) Rinse probe, put it in buffer A (pH 6.86 or 7.00), wait to stabilise, read "Module mV" in Serial Monitor -> CAL_MV_A
// 2) Rinse, put it in buffer B (pH 4.01), wait, read "Module mV"                                            -> CAL_MV_B
// Use fresh, in-date buffer solutions at the same temperature. Re-calibrate regularly.
const float CAL_PH_A = 6.86f;   const float CAL_MV_A = 2500.0f;   // <-- replace with YOUR measured value
const float CAL_PH_B = 4.01f;   const float CAL_MV_B = 3000.0f;   // <-- replace with YOUR measured value

// ---- Sensor sanity limits ----
const float MODULE_MV_MIN   = 100.0f;      // below -> sensor/wiring fault
const float MODULE_MV_MAX   = 4900.0f;     // above -> saturated / fault
const float MAX_NOISE_MV    = 60.0f;       // spread (max-min of middle samples) above this -> unstable/disconnected
const float PH_VALID_MIN    = 0.0f, PH_VALID_MAX = 14.0f;

const long  TZ_OFFSET_SEC   = 8 * 3600;    // Philippines UTC+8
// =================================================================

struct Sample { float ph; int mv; const char* status; char ts[32]; bool hasTs; };
const int QUEUE_MAX = 20;
Sample queueBuf[QUEUE_MAX];
int queueLen = 0;

float emaPh = NAN;
uint32_t lastSend = 0;

// ---------- helpers ----------
bool timeSynced() { return time(nullptr) > 1700000000; }

bool isoNow(char* out, size_t n) {
  if (!timeSynced()) return false;
  time_t t = time(nullptr) + TZ_OFFSET_SEC;
  struct tm tmv; gmtime_r(&t, &tmv);
  strftime(out, n, "%Y-%m-%dT%H:%M:%S+08:00", &tmv);
  return true;
}

void connectWifi() {
  if (WiFi.status() == WL_CONNECTED) return;
  Serial.printf("Wi-Fi: connecting to %s ", WIFI_SSID);
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  uint32_t t0 = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - t0 < 20000) { delay(500); Serial.print('.'); }
  if (WiFi.status() == WL_CONNECTED) {
    Serial.printf("\nWi-Fi OK, IP %s\n", WiFi.localIP().toString().c_str());
    configTime(0, 0, "pool.ntp.org", "time.google.com");   // keep system clock in UTC; we format +08:00 ourselves
  } else {
    Serial.println("\nWi-Fi failed (will retry)");
  }
}

int cmpInt(const void* a, const void* b) { return *(const int*)a - *(const int*)b; }

// Reads the pin, returns the MEDIAN millivolt at the ADC pin and the spread of the middle samples.
int readAdcMv(float& spreadMv) {
  int v[SAMPLES];
  for (int i = 0; i < SAMPLES; i++) { v[i] = analogReadMilliVolts(PH_PIN); delay(8); }
  qsort(v, SAMPLES, sizeof(int), cmpInt);
  int lo = SAMPLES / 4, hi = SAMPLES - SAMPLES / 4 - 1;      // drop the extreme quarters
  spreadMv = (v[hi] - v[lo]) / DIVIDER_RATIO;
  return v[SAMPLES / 2];
}

// Linear two-point calibration:  pH = pH_A + slope * (mV - mV_A),  slope = (pH_B - pH_A) / (mV_B - mV_A)
float mvToPh(float moduleMv) {
  float slope = (CAL_PH_B - CAL_PH_A) / (CAL_MV_B - CAL_MV_A);
  return CAL_PH_A + slope * (moduleMv - CAL_MV_A);
}

// Takes one measurement. status: "ok" | "disconnected" | "error". NEVER invents a value.
bool measure(float& ph, int& moduleMv, const char*& status) {
  float spread;
  int adcMv = readAdcMv(spread);
  moduleMv = (int)(adcMv / DIVIDER_RATIO);
  Serial.printf("ADC pin: %d mV | Module mV: %d | noise: %.0f mV\n", adcMv, moduleMv, spread);

  if (moduleMv < MODULE_MV_MIN || moduleMv > MODULE_MV_MAX) { status = "disconnected"; return false; }
  if (spread > MAX_NOISE_MV)                                { status = "error";        return false; }

  float raw = mvToPh((float)moduleMv);
  if (isnan(raw) || raw < PH_VALID_MIN - 1.0f || raw > PH_VALID_MAX + 1.0f) { status = "error"; return false; }

  emaPh = isnan(emaPh) ? raw : (EMA_ALPHA * raw + (1.0f - EMA_ALPHA) * emaPh);
  ph = emaPh; status = "ok";
  return true;
}

// ---------- HTTP ----------
// returns true when the server accepted it (201/200) or it is permanently invalid (4xx) so we must not retry forever
bool postReading(const Sample& s) {
  if (WiFi.status() != WL_CONNECTED) return false;
  HTTPClient http;
  http.setTimeout(8000);
  if (!http.begin(API_URL)) return false;
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Accept", "application/json");
  http.addHeader("Authorization", String("Bearer ") + DEVICE_TOKEN);

  String body = "{\"device_id\":\"" + String(DEVICE_ID) + "\",";
  if (strcmp(s.status, "ok") == 0) body += "\"ph_value\":" + String(s.ph, 2) + ","; else body += "\"ph_value\":null,";
  body += "\"temperature\":null,";                                   // no temperature sensor installed
  body += "\"raw_voltage_mv\":" + String(s.mv) + ",";
  body += "\"sensor_status\":\"" + String(s.status) + "\",";
  body += "\"firmware\":\"" + String(FIRMWARE_VER) + "\"";
  if (s.hasTs) body += ",\"measured_at\":\"" + String(s.ts) + "\"";
  body += "}";

  int code = http.POST(body);
  Serial.printf("POST -> HTTP %d\n", code);
  if (code > 0 && code < 500) { if (code >= 400) Serial.println(http.getString()); http.end(); return code < 400 || code == 422 || code == 403; }
  http.end();
  return false;   // network error or 5xx: keep in queue
}

void enqueue(const Sample& s) {
  if (queueLen == QUEUE_MAX) { memmove(&queueBuf[0], &queueBuf[1], sizeof(Sample) * (QUEUE_MAX - 1)); queueLen--; }  // drop oldest
  queueBuf[queueLen++] = s;
}

void flushQueue() {
  while (queueLen > 0) {
    if (!postReading(queueBuf[0])) return;
    memmove(&queueBuf[0], &queueBuf[1], sizeof(Sample) * (queueLen - 1));
    queueLen--;
  }
}

void setup() {
  Serial.begin(115200);
  analogReadResolution(12);
  analogSetPinAttenuation(PH_PIN, ADC_11db);
  pinMode(PH_PIN, INPUT);
  connectWifi();
  lastSend = millis() - SEND_INTERVAL_MS;   // first reading right away
}

void loop() {
  connectWifi();                              // reconnect if dropped
  if (millis() - lastSend >= SEND_INTERVAL_MS) {
    lastSend = millis();
    Sample s{}; float ph = NAN; int mv = 0; const char* st = "error";
    measure(ph, mv, st);
    s.ph = ph; s.mv = mv; s.status = st; s.hasTs = isoNow(s.ts, sizeof(s.ts));
    if (strcmp(st, "ok") == 0) Serial.printf("pH %.2f\n", ph); else Serial.printf("Sensor status: %s (nothing faked)\n", st);

    if (!postReading(s) && s.hasTs) enqueue(s);   // only queue when we have a real timestamp
    flushQueue();
  }
  delay(200);
}
