#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <LittleFS.h>

// Wi-Fi credentials (from config.json)
String ssid = "";
String password = "";

// LED pins
#define SLOT1_LED 15
#define SLOT2_LED 18
#define SLOT3_LED 4

// Buzzer pin
#define BUZZER_PIN 27

// Reed switch pin
#define REED_SWITCH_PIN 32

// Slot status
bool slotDue[3] = {false, false, false};
bool ledState[3] = {false, false, false};
unsigned long previousMillis[3] = {0,0,0};
const long blinkInterval = 500;

// Reed switch state tracking
bool lastReedState = HIGH; 
bool isDoseReported = false;

// API polling
unsigned long lastCheck = 0;
const unsigned long checkInterval = 3000; // 3s for better simulation response

void reportIntake() {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
  
    // Assuming slot_id=1 for the Reed switch on GPIO 32
    String url = "http://10.160.152.231/Sushrusha/public/api/simulation/report_intake.php?user_id=10&slot_id=1";
    http.begin(url);
    int httpCode = http.GET();
    if (httpCode > 0) {
      Serial.println("Intake reported successfully, code: " + String(httpCode));
    } else {
      Serial.println("Failed to report intake, code: " + String(httpCode));
    }
    http.end();
  }
}

void setup() {
  Serial.begin(115200);

  pinMode(SLOT1_LED, OUTPUT);
  pinMode(SLOT2_LED, OUTPUT);
  pinMode(SLOT3_LED, OUTPUT);
  pinMode(BUZZER_PIN, OUTPUT);
  pinMode(REED_SWITCH_PIN, INPUT_PULLUP);

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
Serial.println(ssid);
Serial.println(password);
  WiFi.begin(ssid.c_str(), password.c_str());
Serial.println("Connecting to Wi-Fi...");

int retries = 0;
while (WiFi.status() != WL_CONNECTED && retries < 20) {
  delay(500);
  Serial.print(".");
  Serial.println(WiFi.status());
  retries++;
}

if (WiFi.status() == WL_CONNECTED) {
  Serial.println("\nWiFi Connected!");
  Serial.println(WiFi.localIP());
} else {
  Serial.println("\nWiFi FAILED");
}
}

void loop() {
  unsigned long currentMillis = millis();

  // --- Reed Switch Monitoring ---
  bool currentReedState = digitalRead(REED_SWITCH_PIN);
  if (currentReedState == HIGH && lastReedState == LOW) {
    // Magnet removed (slot opened)
    Serial.println("Slot opened (Reed switch triggered)");
    
    // Only report if a dose is actually due for slot 1
    if (slotDue[0] && !isDoseReported) {
      Serial.println("Reporting intake for Slot 1...");
      reportIntake();
      slotDue[0] = false;    // Immediate local feedback: stop blinking
      isDoseReported = true; // Mark as reported to prevent double reporting
    }
  } else if (currentReedState == LOW && lastReedState == HIGH) {
    // Magnet replaced (slot closed)
    Serial.println("Slot closed");
    isDoseReported = false; // Reset for next dose if needed
  }
  lastReedState = currentReedState;

  if(currentMillis - lastCheck >= checkInterval){
    lastCheck = currentMillis;

    if(WiFi.status() == WL_CONNECTED){
      // --- Slot API ---
      HTTPClient httpSlots;
      httpSlots.begin("http://10.160.152.231/Sushrusha/public/api/simulation/get_state.php?user_id=10");
      int httpCodeSlots = httpSlots.GET();
      if(httpCodeSlots > 0){
        String payload = httpSlots.getString();
        DynamicJsonDocument doc(4096);
        if(deserializeJson(doc, payload) == DeserializationError::Ok){
          for(int i=0;i<3;i++) slotDue[i] = false;
          JsonArray schedules = doc["schedules"];
          for(JsonObject schedule : schedules){
            int slot = (int)schedule["slot_id"] - 1; // Convert 1-based UI to 0-based index
            bool due = schedule["is_due_today"];
            if(slot>=0 && slot<3 && due) {
              // If we just reported a dose and the slot is still open, 
              // keep slotDue[0] false until the API reflects the change
              if (slot == 0 && isDoseReported) {
                // Keep it false
              } else {
                slotDue[slot] = true;
              }
            }
          }
        }
      }
      httpSlots.end();

    }
  }

  // Blink LEDs
  for(int i=0;i<3;i++){
    if(slotDue[i]){
      if(currentMillis - previousMillis[i] >= blinkInterval){
        previousMillis[i] = currentMillis;
        ledState[i] = !ledState[i];
      }
    } else ledState[i] = false;

    if(i==0) digitalWrite(SLOT1_LED, ledState[i]);
    if(i==1) digitalWrite(SLOT2_LED, ledState[i]);
    if(i==2) digitalWrite(SLOT3_LED, ledState[i]);
  }

  // Handle Buzzer (beep accordingly when any LED is blinking)
  bool anyLedOn = false;
  for(int i=0; i<3; i++) {
    if(ledState[i]) {
      anyLedOn = true;
      break;
    }
  }
  
  if(anyLedOn) {
    digitalWrite(BUZZER_PIN, HIGH);
  } else {
    digitalWrite(BUZZER_PIN, LOW);
  }
}
