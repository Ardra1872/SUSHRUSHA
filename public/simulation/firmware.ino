#include <Arduino.h>
#include <WiFi.h>
#include <WebServer.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <LittleFS.h>
#include <time.h>

#define NUM_SLOTS 4

// Config variables
String wifi_ssid = "";
String wifi_pass = "";
String api_url = "";

// Time synchronization
const long gmtOffset_sec = 19800; // India
const int daylightOffset_sec = 0;
const char* ntpServer = "pool.ntp.org";

WebServer server(80);

const int SLOT_LED_PINS[NUM_SLOTS] = {15, 4, 16, 17};
const int BUZZER_PIN = 27;

enum SlotStatus { Waiting, Alerting, Taken, Missed };

struct Slot {
  int id;
  int ledPin;
  int scheduleMinutes; // Minutes since midnight
  SlotStatus status;
  bool alerting;
  unsigned long alertStartMillis;
  unsigned long lastBlinkMillis;
  bool ledState;
  int lastTriggeredDay;
  String medicineName;
};

Slot slots[NUM_SLOTS];

const unsigned long ALERT_DURATION = 5 * 60 * 1000; // 5 Minutes
const unsigned long BLINK_INTERVAL = 500;
const unsigned long SYNC_INTERVAL = 10 * 60 * 1000; // Sync every 10 mins

unsigned long lastSyncMillis = 0;

// --- CONFIG & SYNC FUNCTIONS ---

bool loadConfig() {
  if (!LittleFS.exists("/config.json")) {
    Serial.println("Config file not found");
    return false;
  }
  File file = LittleFS.open("/config.json", "r");
  StaticJsonDocument<512> doc;
  DeserializationError error = deserializeJson(doc, file);
  file.close();

  if (error) {
    Serial.println("Failed to parse config");
    return false;
  }

  wifi_ssid = doc["wifi_ssid"].as<String>();
  wifi_pass = doc["wifi_pass"].as<String>();
  api_url = doc["api_url"].as<String>();
  return true;
}

void syncSchedules() {
  if (WiFi.status() != WL_CONNECTED) return;

  Serial.println("Syncing schedules from backend...");
  HTTPClient http;
  http.begin(api_url);
  int httpCode = http.GET();

  if (httpCode == HTTP_CODE_OK) {
    String payload = http.getString();
    StaticJsonDocument<2048> doc;
    deserializeJson(doc, payload);

    JsonArray schedules = doc["schedules"];
    
    // Reset internal slots state before populating (or intelligently merge)
    for (int i = 0; i < NUM_SLOTS; i++) {
        slots[i].scheduleMinutes = -1;
        slots[i].medicineName = "Empty";
    }

    for (JsonObject s : schedules) {
      int slotIdx = s["slot_id"].as<int>();
      if (slotIdx >= 0 && slotIdx < NUM_SLOTS) {
        String timeStr = s["intake_time_formatted"].as<String>(); // "HH:MM"
        int hh = timeStr.substring(0, 2).toInt();
        int mm = timeStr.substring(3, 5).toInt();
        
        slots[slotIdx].scheduleMinutes = hh * 60 + mm;
        slots[slotIdx].medicineName = s["medicine_name"].as<String>();
        slots[slotIdx].status = Waiting;
        slots[slotIdx].alerting = false;
        
        Serial.printf("Slot %d: %s at %02d:%02d\n", slotIdx + 1, slots[slotIdx].medicineName.c_str(), hh, mm);
      }
    }
    lastSyncMillis = millis();
    Serial.println("Sync Complete.");
  } else {
    Serial.printf("HTTP GET failed, error: %s\n", http.errorString(httpCode).c_str());
  }
  http.end();
}

// --- SETUP ---

void setup() {
  Serial.begin(115200);

  // Initialize Slots
  for (int i = 0; i < NUM_SLOTS; i++) {
    slots[i].id = i + 1;
    slots[i].ledPin = SLOT_LED_PINS[i];
    slots[i].scheduleMinutes = -1;
    slots[i].status = Waiting;
    slots[i].alerting = false;
    slots[i].lastTriggeredDay = -1;
    slots[i].medicineName = "Empty";

    pinMode(slots[i].ledPin, OUTPUT);
    digitalWrite(slots[i].ledPin, LOW);
  }

  pinMode(BUZZER_PIN, OUTPUT);
  digitalWrite(BUZZER_PIN, LOW);

  // LittleFS
  if (!LittleFS.begin(true)) {
    Serial.println("LittleFS Mount Failed");
  } else {
    Serial.println("LittleFS Mounted");
  }

  // Load Config
  if (loadConfig()) {
    WiFi.begin(wifi_ssid.c_str(), wifi_pass.c_str());
    Serial.print("Connecting to WiFi");
    int retry = 0;
    while (WiFi.status() != WL_CONNECTED && retry < 20) {
      delay(500); Serial.print("."); retry++;
    }
    if (WiFi.status() == WL_CONNECTED) {
      Serial.println("\nWiFi Connected: " + WiFi.localIP().toString());
      configTime(gmtOffset_sec, daylightOffset_sec, ntpServer);
      syncSchedules();
    } else {
      Serial.println("\nWiFi Connection Failed. Using default/standalone mode.");
    }
  }

  // Web Server Routes
  server.on("/", []() {
    File file = LittleFS.open("/index.html", "r");
    if (!file) {
      server.send(404, "text/plain", "File Not Found");
      return;
    }
    server.streamFile(file, "text/html");
    file.close();
  });

  server.on("/status", []() {
    StaticJsonDocument<1024> doc;
    struct tm timeinfo;
    if (getLocalTime(&timeinfo)) {
      char buf[10];
      strftime(buf, sizeof(buf), "%H:%M", &timeinfo);
      doc["time"] = String(buf);
    } else {
      doc["time"] = "--:--";
    }
    doc["synced"] = (millis() - lastSyncMillis < SYNC_INTERVAL * 2);

    JsonArray slotsArr = doc.createNestedArray("slots");
    for (int i = 0; i < NUM_SLOTS; i++) {
      JsonObject s = slotsArr.createNestedObject();
      s["id"] = slots[i].id;
      s["medicine"] = slots[i].medicineName;
      
      String statusStr = "Waiting";
      if (slots[i].status == Alerting) statusStr = "Alerting";
      else if (slots[i].status == Taken) statusStr = "Taken";
      else if (slots[i].status == Missed) statusStr = "Missed";
      s["status"] = statusStr;

      if (slots[i].scheduleMinutes >= 0) {
        char buf[10];
        sprintf(buf, "%02d:%02d", slots[i].scheduleMinutes / 60, slots[i].scheduleMinutes % 60);
        s["schedule"] = String(buf);
      } else {
        s["schedule"] = (char*)NULL;
      }
    }
    String output;
    serializeJson(doc, output);
    server.send(200, "application/json", output);
  });

  server.on("/open", []() {
    int s = server.arg("slot").toInt() - 1;
    if (s >= 0 && s < NUM_SLOTS) {
      slots[s].alerting = false;
      slots[s].status = Taken;
      digitalWrite(slots[s].ledPin, LOW);
      digitalWrite(BUZZER_PIN, LOW);
      server.send(200, "text/plain", "OK");
    } else {
      server.send(400, "text/plain", "Invalid Slot");
    }
  });

  server.begin();
}

// --- LOOP ---

void loop() {
  server.handleClient();

  unsigned long nowMs = millis();

  // Periodic Sync
  if (nowMs - lastSyncMillis > SYNC_INTERVAL) {
    syncSchedules();
  }

  struct tm timeinfo;
  if (!getLocalTime(&timeinfo)) return;

  int nowMin = timeinfo.tm_hour * 60 + timeinfo.tm_min;
  int day = timeinfo.tm_yday;

  for (int i = 0; i < NUM_SLOTS; i++) {
    Slot &s = slots[i];

    // Trigger Alert
    if (s.scheduleMinutes >= 0 && s.status == Waiting) {
      if (nowMin >= s.scheduleMinutes && nowMin < s.scheduleMinutes + 5 && s.lastTriggeredDay != day) {
        s.alerting = true;
        s.status = Alerting;
        s.alertStartMillis = nowMs;
        s.lastBlinkMillis = nowMs;
        s.lastTriggeredDay = day;
        Serial.printf("Alert started for Slot %d: %s\n", s.id, s.medicineName.c_str());
      }
    }

    // Handle Alerting
    if (s.alerting) {
      if (nowMs - s.lastBlinkMillis > BLINK_INTERVAL) {
        s.lastBlinkMillis = nowMs;
        s.ledState = !s.ledState;
        digitalWrite(s.ledPin, s.ledState);
        digitalWrite(BUZZER_PIN, s.ledState);
      }

      // Timeout
      if (nowMs - s.alertStartMillis > ALERT_DURATION) {
        s.alerting = false;
        s.status = Missed;
        digitalWrite(s.ledPin, LOW);
        digitalWrite(BUZZER_PIN, LOW);
        Serial.printf("Alert timeout for Slot %d\n", s.id);
      }
    }
  }
}
