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
const int REED_SWITCH_PINS[NUM_SLOTS] = {32, 33, 34, 35}; // Pins for Slots 1, 2, 3, 4
const int BUZZER_PIN = 27;

enum SlotStatus { Waiting, Alerting, Taken, Missed };

struct Slot {
  int id;
  int ledPin;
  int reedPin;
  int scheduleMinutes; // Minutes since midnight
  SlotStatus status;
  bool alerting;
  unsigned long alertStartMillis;
  unsigned long lastBlinkMillis;
  bool ledState;
  int lastTriggeredDay;
  int lastReedState;
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

void reportIntake(int slotId) {
    if (WiFi.status() != WL_CONNECTED) return;

    Serial.printf("Reporting intake for Slot %d...\n", slotId);
    HTTPClient http;
    // Base URL assumed to be in same directory as get_state (api_url)
    // api_url typically: http://.../get_state.php?user_id=10
    // We need to extract the base path
    String baseUrl = api_url.substring(0, api_url.lastIndexOf('/') + 1);
    String url = baseUrl + "report_intake.php?user_id=10&slot_id=" + String(slotId);
    
    http.begin(url);
    int httpCode = http.GET();
    if (httpCode == HTTP_CODE_OK) {
        Serial.println("Intake reported successfully.");
    } else {
        Serial.printf("Failed to report intake, code: %d\n", httpCode);
    }
    http.end();
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
        // Now using 1-based matching from API
      int slotNum = s["slot_id"].as<int>();
      int slotIdx = slotNum - 1; 

      if (slotIdx >= 0 && slotIdx < NUM_SLOTS) {
        String timeStr = s["intake_time_formatted"].as<String>(); // "HH:MM"
        int hh = timeStr.substring(0, 2).toInt();
        int mm = timeStr.substring(3, 5).toInt();
        
        slots[slotIdx].scheduleMinutes = hh * 60 + mm;
        slots[slotIdx].medicineName = s["medicine_name"].as<String>();
        
        String statusStr = s["log_status"].as<String>();
        if (statusStr == "Taken") {
            slots[slotIdx].status = Taken;
            slots[slotIdx].alerting = false;
        } else {
            slots[slotIdx].status = Waiting;
            slots[slotIdx].alerting = false;
        }
        
        Serial.printf("Slot %d: %s at %02d:%02d (%s)\n", slotNum, slots[slotIdx].medicineName.c_str(), hh, mm, statusStr.c_str());
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
    slots[i].reedPin = REED_SWITCH_PINS[i];
    slots[i].scheduleMinutes = -1;
    slots[i].status = Waiting;
    slots[i].alerting = false;
    slots[i].lastTriggeredDay = -1;
    slots[i].lastReedState = LOW;
    slots[i].medicineName = "Empty";

    pinMode(slots[i].ledPin, OUTPUT);
    digitalWrite(slots[i].ledPin, LOW);
    pinMode(slots[i].reedPin, INPUT_PULLUP);
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
      // Manually trigger reporting to simulate Reed Switch for web-based testing
      reportIntake(s + 1);
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

    // --- REED SWITCH MONITORING ---
    int currentReedState = digitalRead(s.reedPin);
    if (currentReedState == HIGH && s.lastReedState == LOW) {
        // Rising edge: Magnet removed (Slot opened)
        Serial.printf("Reed Switch Slot %d triggered!\n", s.id);
        
        // Report intake to backend
        reportIntake(s.id);

        // Update local state and stop alerts
        s.alerting = false;
        s.status = Taken;
        digitalWrite(s.ledPin, LOW);
        digitalWrite(BUZZER_PIN, LOW);
    }
    s.lastReedState = currentReedState;

    // --- ALERT LOGIC ---
    // Trigger Alert
    if (s.scheduleMinutes >= 0 && s.status == Waiting) {
      if (nowMin >= s.scheduleMinutes && nowMin < s.scheduleMinutes + 30 && s.lastTriggeredDay != day) {
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

      // Timeout (e.g., 30 mins)
      if (nowMs - s.alertStartMillis > (30 * 60 * 1000)) {
        s.alerting = false;
        s.status = Missed;
        digitalWrite(s.ledPin, LOW);
        digitalWrite(BUZZER_PIN, LOW);
        Serial.printf("Alert timeout for Slot %d\n", s.id);
      }
    }
  }
}
