#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <LittleFS.h>

// Wi-Fi credentials (loaded from config.json)
String ssid = "";
String password = "";

// LED pins for 3 slots
#define SLOT1_LED 15
#define SLOT2_LED 18
#define SLOT3_LED 4

// Buzzer pin
#define BUZZER_PIN 27

// Slot due status
bool slotDue[3] = {false, false, false};

// LED blinking state
bool ledState[3] = {false, false, false};
unsigned long previousMillis[3] = {0,0,0};
const long blinkInterval = 500; // 500ms blink

// API polling
unsigned long lastCheck = 0;
const unsigned long checkInterval = 10000; // 10 sec

void setup() {
  Serial.begin(115200);

  pinMode(SLOT1_LED, OUTPUT);
  pinMode(SLOT2_LED, OUTPUT);
  pinMode(SLOT3_LED, OUTPUT);
  pinMode(BUZZER_PIN, OUTPUT);

  // Initialize LittleFS
  if(!LittleFS.begin(true)){
    Serial.println("LittleFS Mount Failed");
    return;
  }

  // Load config.json
  File configFile = LittleFS.open("/config.json", "r");
  if(!configFile){
    Serial.println("Failed to open config file");
  } else {
    size_t size = configFile.size();
    std::unique_ptr<char[]> buf(new char[size]);
    configFile.readBytes(buf.get(), size);
    configFile.close();

    DynamicJsonDocument doc(1024);
    DeserializationError error = deserializeJson(doc, buf.get());
    if(!error){
      ssid = doc["wifi_ssid"].as<String>();
      password = doc["wifi_pass"].as<String>();
      Serial.println("Config loaded from LittleFS");
    } else {
      Serial.println("Failed to parse config file");
    }
  }

  if (ssid == "" || password == "") {
    Serial.println("WiFi credentials not found in config.json");
    return;
  }

  WiFi.begin(ssid.c_str(), password.c_str());
  Serial.println("Connecting to Wi-Fi...");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\nConnected!");
}

void loop() {
  unsigned long currentMillis = millis();

  // 🔄 Poll API every 10 seconds
  if(currentMillis - lastCheck >= checkInterval){
    lastCheck = currentMillis;

    if(WiFi.status() == WL_CONNECTED){
      HTTPClient http;
      http.begin("http://192.168.1.8/Sushrusha/public/api/simulation/get_state.php?user_id=10");
      int httpCode = http.GET();

      if(httpCode > 0){
        String payload = http.getString();
        Serial.println(payload);

        // Parse JSON using ArduinoJson
        DynamicJsonDocument doc(4096);
        DeserializationError error = deserializeJson(doc, payload);
        if(!error){
          // Reset all slots
          for(int i=0;i<3;i++) slotDue[i] = false;

          JsonArray schedules = doc["schedules"];
          for(JsonObject schedule : schedules){
            int slot = schedule["slot_id"];       // 0-indexed
            bool due = schedule["is_due_today"];
            if(slot >=0 && slot < 3 && due){
              slotDue[slot] = true;
              Serial.println("Slot " + String(slot+1) + " Due!");
            }
          }
        } else {
          Serial.println("JSON parse error!");
        }
      } else {
        Serial.println("HTTP GET failed, code: " + String(httpCode));
      }

      http.end();
    }
  }

  // 🔹 Blink LEDs independently using millis()
  bool anyDue = false;
  for(int i=0;i<3;i++){
    if(slotDue[i]){
      anyDue = true;
      if(currentMillis - previousMillis[i] >= blinkInterval){
        previousMillis[i] = currentMillis;
        ledState[i] = !ledState[i]; // toggle LED
      }
    } else {
      ledState[i] = false; // turn off if not due
    }
  }

  digitalWrite(SLOT1_LED, ledState[0]);
  digitalWrite(SLOT2_LED, ledState[1]);
  digitalWrite(SLOT3_LED, ledState[2]);

  // 🔹 Buzzer ON if any slot is due
  digitalWrite(BUZZER_PIN, anyDue);
}
