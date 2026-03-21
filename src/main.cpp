#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <LittleFS.h>

// Wi-Fi credentials
String ssid = "";
String password = "";

// LED pins
#define SLOT1_LED 15
#define SLOT2_LED 26
#define SLOT3_LED 4

// Buzzer
#define BUZZER_PIN 27

// Reed switch pins
#define REED_SWITCH_PIN_1 32
#define REED_SWITCH_PIN_2 33
#define REED_SWITCH_PIN_3 25

// Slot status
bool slotDue[3] = {false, false, false};
bool ledState[3] = {false, false, false};
unsigned long previousMillis[3] = {0,0,0};
const long blinkInterval = 500;

// Reed switch tracking
bool lastReedState[3] = {HIGH, HIGH, HIGH};
bool isDoseReported[3] = {false, false, false};

// API polling
unsigned long lastCheck = 0;
const unsigned long checkInterval = 3000;

// ================== REPORT INTAKE ==================
void reportIntake(int slot_id) {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;

    String url = "http://10.160.152.231/Sushrusha/public/api/simulation/report_intake.php?user_id=10&slot_id=" + String(slot_id);
    http.begin(url);

    int httpCode = http.GET();

    if (httpCode > 0) {
      Serial.println("Intake reported for Slot " + String(slot_id) + " Code: " + String(httpCode));
    } else {
      Serial.println("Failed to report intake for Slot " + String(slot_id));
    }

    http.end();
  }
}

// ================== SETUP ==================
void setup() {
  Serial.begin(115200);

  pinMode(SLOT1_LED, OUTPUT);
  pinMode(SLOT2_LED, OUTPUT);
  pinMode(SLOT3_LED, OUTPUT);
  pinMode(BUZZER_PIN, OUTPUT);

  pinMode(REED_SWITCH_PIN_1, INPUT_PULLUP);
  pinMode(REED_SWITCH_PIN_2, INPUT_PULLUP);
  pinMode(REED_SWITCH_PIN_3, INPUT_PULLUP);

  if(!LittleFS.begin(true)){
    Serial.println("LittleFS Mount Failed");
    return;
  }

  // Load Wi-Fi credentials
  File configFile = LittleFS.open("/config.json", "r");
  if(configFile){
    size_t size = configFile.size();
    std::unique_ptr<char[]> buf(new char[size]);
    configFile.readBytes(buf.get(), size);
    configFile.close();

    DynamicJsonDocument doc(1024);
    if(deserializeJson(doc, buf.get()) == DeserializationError::Ok){
      ssid = doc["wifi_ssid"].as<String>();
      password = doc["wifi_pass"].as<String>();
      Serial.println("Config loaded");
    }
  }

  if(ssid == "" || password == ""){
    Serial.println("WiFi credentials missing!");
    return;
  }

  WiFi.begin(ssid.c_str(), password.c_str());
  Serial.println("Connecting to Wi-Fi...");

  int retries = 0;
  while (WiFi.status() != WL_CONNECTED && retries < 20) {
    delay(500);
    Serial.print(".");
    retries++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\nWiFi Connected!");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println("\nWiFi FAILED");
  }
}

// ================== LOOP ==================
void loop() {
  unsigned long currentMillis = millis();

  // -------- REED SWITCH HANDLING --------
  static unsigned long lastDebounceTime[3] = {0, 0, 0};
  static bool stableReedState[3] = {HIGH, HIGH, HIGH}; 
  const unsigned long debounceDelay = 50; 
  int reedPins[3] = {REED_SWITCH_PIN_1, REED_SWITCH_PIN_2, REED_SWITCH_PIN_3};

  for(int i = 0; i < 3; i++) {
    bool currentReedState = digitalRead(reedPins[i]);

    if (currentReedState != lastReedState[i]) {
      lastDebounceTime[i] = currentMillis;
    }

    if ((currentMillis - lastDebounceTime[i]) > debounceDelay) {
      if (currentReedState != stableReedState[i]) {
        stableReedState[i] = currentReedState;

        // Slot opened
        if (stableReedState[i] == HIGH) {
          Serial.println("Slot " + String(i+1) + " opened");

          if (slotDue[i] && !isDoseReported[i]) {
            Serial.println("Reporting intake for Slot " + String(i+1));
            reportIntake(i + 1);

            slotDue[i] = false;
            isDoseReported[i] = true;
          }

        // Slot closed
        } else {
          Serial.println("Slot " + String(i+1) + " closed");
          isDoseReported[i] = false;
        }
      }
    }
    lastReedState[i] = currentReedState;
  }

  // -------- API CHECK --------
  if(currentMillis - lastCheck >= checkInterval){
    lastCheck = currentMillis;

    if(WiFi.status() == WL_CONNECTED){
      HTTPClient httpSlots;
      httpSlots.begin("http://10.160.152.231/Sushrusha/public/api/simulation/get_state.php?user_id=10");

      int httpCodeSlots = httpSlots.GET();

      if(httpCodeSlots > 0){
        String payload = httpSlots.getString();

        DynamicJsonDocument doc(4096);
        if(deserializeJson(doc, payload) == DeserializationError::Ok){

          for(int i=0;i<3;i++) slotDue[i] = false;

          JsonArray schedules = doc["schedules"];
          
          Serial.println("--- API Check ---");

          for(JsonObject schedule : schedules){
            int slot = (int)schedule["slot_id"] - 1;
            bool due = schedule["is_due_today"];

            if(slot>=0 && slot<3){
              Serial.println("Slot " + String(slot+1) + " API says Due: " + (due ? "YES" : "NO") + " | DoseReported: " + (isDoseReported[slot] ? "YES" : "NO"));
              
              if(due && !isDoseReported[slot]) {
                slotDue[slot] = true;
              }
            }
          }
          Serial.println("-----------------");
        }
      }

      httpSlots.end();
    }
  }

  // -------- LED BLINK --------
  for(int i=0;i<3;i++){
    if(slotDue[i]){
      if(currentMillis - previousMillis[i] >= blinkInterval){
        previousMillis[i] = currentMillis;
        ledState[i] = !ledState[i];
      }
    } else {
      ledState[i] = false;
    }

    if(i==0) digitalWrite(SLOT1_LED, ledState[i]);
    if(i==1) digitalWrite(SLOT2_LED, ledState[i]);
    if(i==2) digitalWrite(SLOT3_LED, ledState[i]);
  }

  // -------- BUZZER --------
  bool anyLedOn = false;

  for(int i=0; i<3; i++){
    if(ledState[i]){
      anyLedOn = true;
      break;
    }
  }

  digitalWrite(BUZZER_PIN, anyLedOn ? HIGH : LOW);
}