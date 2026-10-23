// AquaTrack ESP32 firmware: reads pH on ADC pin 34, drives the RGB LED,
// prints to the Serial Monitor and posts each reading to the Laravel API.
#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>

// ---- edit these ----
const char* WIFI_SSID  = "YOUR_WIFI_NAME";
const char* WIFI_PASS  = "YOUR_WIFI_PASSWORD";
const char* SERVER_URL = "http://192.168.1.10:8000/api/readings"; // your laptop's LAN IP
const char* DEVICE_KEY = "";                                      // same as PH_DEVICE_KEY in .env (optional)
// --------------------

#define PH_SENSOR_PIN 34
#define RED_PIN       25
#define GREEN_PIN     26
#define BLUE_PIN      27

const float PH_ACIDIC_BELOW   = 6.5;
const float PH_ALKALINE_ABOVE = 8.5;
const float PH_NEUTRAL        = 7.0;  // keep equal to 'neutral' in config/ph.php

void setLed(bool r, bool g, bool b) {
  digitalWrite(RED_PIN, r);
  digitalWrite(GREEN_PIN, g);
  digitalWrite(BLUE_PIN, b);
}

void sendReading(int raw, float voltage, float ph) {
  if (WiFi.status() != WL_CONNECTED) return;
  JsonDocument doc;
  doc["device_id"] = "esp32-01";
  doc["raw"] = raw;
  doc["voltage"] = voltage;
  doc["ph"] = ph;
  String body;
  serializeJson(doc, body);

  HTTPClient http;
  http.begin(SERVER_URL);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Accept", "application/json");
  if (strlen(DEVICE_KEY) > 0) http.addHeader("X-Device-Key", DEVICE_KEY);
  int code = http.POST(body);
  Serial.printf("POST -> %d\n", code);
  http.end();
}

void setup() {
  Serial.begin(115200);
  pinMode(PH_SENSOR_PIN, INPUT);
  pinMode(RED_PIN, OUTPUT);
  pinMode(GREEN_PIN, OUTPUT);
  pinMode(BLUE_PIN, OUTPUT);
  setLed(LOW, LOW, LOW);

  Serial.println("=== AquaTrack Water pH Monitor ===");
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  for (int i = 0; i < 30 && WiFi.status() != WL_CONNECTED; i++) { delay(500); Serial.print("."); }
  Serial.println(WiFi.status() == WL_CONNECTED ? "\nWiFi connected" : "\nWiFi unavailable, serial only");
}

void loop() {
  int raw = analogRead(PH_SENSOR_PIN);
  float voltage = raw * (3.3 / 4095.0);
  float ph = PH_NEUTRAL + ((2.5 - voltage) / 0.18);
  const char* status;

  if (ph < PH_ACIDIC_BELOW)        { status = "ACIDIC";   setLed(HIGH, HIGH, LOW); } // yellow
  else if (ph > PH_ALKALINE_ABOVE) { status = "ALKALINE"; setLed(HIGH, LOW, LOW);  } // red
  else                             { status = "NORMAL";   setLed(LOW, HIGH, LOW);  } // green

  Serial.printf("Raw: %d | Voltage: %.3f V | pH: %.2f | %s\n", raw, voltage, ph, status);
  sendReading(raw, voltage, ph);
  delay(3000);
}
