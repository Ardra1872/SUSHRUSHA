# Smart Medicine Box — ESP32 (4 Slots)

## Overview

A Smart Medicine Box system with 4 independent medicine slots. Each slot can be scheduled for a specific time. When the scheduled time arrives:
- The **built-in LED (GPIO 2)** starts blinking as an alert
- The **system status** changes to "**Alerting**"
- User can click "**Open Slot**" button in the web UI to mark medicine as "Taken"
- If not opened within 5 minutes, status becomes "**Missed**"

All control and monitoring is done **through a web UI** hosted on the ESP32 (or via Serial commands).

## Features

✅ **4 independent medicine slots** with per-slot schedules (HH:MM)  
✅ **Web UI** hosted on ESP32 for full remote control  
✅ **Built-in LED (GPIO 2)** blinks when any slot is alerting  
✅ **Per-slot LEDs** (GPIO 15, 4, 16, 17) for visual feedback in simulation  
✅ **Simulated clock** based on `millis()` — easily set via web UI  
✅ **Status tracking**: Waiting → Alerting → Taken/Missed  
✅ **5-minute alert window** per slot  
✅ **JSON API endpoints** for integration with other systems  
✅ **Serial commands** as fallback (backup control)  

## Quick Start — Web UI (Easiest)

### 1. Edit Wi-Fi Credentials

Open `src/main.cpp` and find these lines near the top:

```cpp
const char* WIFI_SSID = "your-ssid";
const char* WIFI_PASS = "your-password";
```

Replace with your actual Wi-Fi credentials.

### 2. Build & Upload

```bash
cd c:\Users\LENOVO\OneDrive\Documents\PlatformIO\Projects\Sushrusha
pio run --target upload
```

### 3. Connect via Browser

1. Open Serial Monitor (115200 baud) to see the ESP32's IP address after it connects to Wi-Fi.
   - Output will show: `Connected. IP: 192.168.x.x` (your IP will differ)
2. Open that IP in your browser: **http://192.168.x.x/**
3. You'll see the Medicine Box web UI.

### 4. Test via Web UI

**Set Simulated Time:**
- Use the "Set Sim Time" input to set the current time
- Example: Set to 08:29

**Set a Medicine Schedule:**
- Input: Slot **1**, Time **08:30**
- Click "Set" button
- Slot 1 status will show "Waiting" with Schedule "08:30"

**Observe Alert:**
- Wait 60 seconds (or advance simulated time to 08:30)
- Slot 1 status changes to "**Alerting**"
- LED indicator turns red and blinks
- Built-in LED on ESP32 starts blinking

**Take Medicine:**
- Click the "**Open Lid**" button on Slot 1
- Status immediately changes to "**Taken**"
- LED stops blinking

**Page auto-refreshes** every 1 second, so you'll see live updates.

## Hardware / Pin Mapping

| Component | GPIO | Notes |
|-----------|------|-------|
| Built-in LED | 2 | Blinks while any slot is alerting |
| Slot 1 LED | 15 | Simulation LED (Wokwi) |
| Slot 2 LED | 4 | Simulation LED (Wokwi) |
| Slot 3 LED | 16 | Simulation LED (Wokwi) |
| Slot 4 LED | 17 | Simulation LED (Wokwi) |
| Slot 1 Button | 32 | Simulate lid open (INPUT_PULLUP) |
| Slot 2 Button | 33 | Simulate lid open (INPUT_PULLUP) |
| Slot 3 Button | 25 | Simulate lid open (INPUT_PULLUP) |
| Slot 4 Button | 26 | Simulate lid open (INPUT_PULLUP) |

## Web UI Endpoints (JSON API)

If you want to integrate with other systems:

- **GET `/`** — Serve the HTML web UI
- **GET `/status`** — Return JSON with current time, day, and all slot statuses
- **GET `/set?slot=N&hh=HH&mm=MM`** — Set schedule for slot N (1-4)
- **GET `/time?hh=HH&mm=MM`** — Set simulated current time
- **GET `/open?slot=N`** — Simulate opening slot N (mark as Taken if alerting)

**Example: Set Slot 2 to 14:30**
```
http://192.168.x.x/set?slot=2&hh=14&mm=30
```

**Example: Set simulated time to 23:45**
```
http://192.168.x.x/time?hh=23&mm=45
```

**Example: Check status (JSON)**
```
http://192.168.x.x/status
```

## Serial Commands (Backup)

If web UI is unavailable, you can use the Serial Monitor:

```
help              → Show all available commands
show              → Print status and schedules for all slots
time HH:MM        → Set simulated current time (e.g., time 08:30)
set N HH:MM       → Set schedule for slot N (e.g., set 1 08:30)
```

## Wokwi Simulation (Test Without Hardware)

If you don't have an ESP32 or Wi-Fi available, you can test everything in the browser using Wokwi:

1. Go to [**https://wokwi.com/**](https://wokwi.com/)
2. Create a new **ESP32** project
3. Copy the contents of `src/main.cpp` into the Wokwi editor
4. In the Wokwi editor, paste the circuit diagram from **diagram.json** (see Wokwi Diagram section below)
5. Click **"Run"** to start the simulation
6. Look for the embedded **"Serial Monitor"** output to find the simulated IP (usually `192.168.4.1`)
7. Click **"Virtual Browser"** in Wokwi and navigate to `http://192.168.4.1/`
8. Interact with the web UI in the browser — the simulated LEDs will blink in real time

### Wokwi Diagram

Use the diagram below in Wokwi:

```json
{
  "version": 1,
  "author": "Medicine Box",
  "title": "Smart Medicine Box - 4 Slots",
  "files": {
    "sketch.ino": {
      "name": "sketch.ino",
      "content": "// [paste src/main.cpp contents here]"
    }
  },
  "connections": [
    ["ESP32:15", "LED1:A"],
    ["GND:GND", "LED1:K"],
    ["ESP32:4", "LED2:A"],
    ["GND:GND", "LED2:K"],
    ["ESP32:16", "LED3:A"],
    ["GND:GND", "LED3:K"],
    ["ESP32:17", "LED4:A"],
    ["GND:GND", "LED4:K"],
    ["ESP32:32", "BTN1:1"],
    ["GND:GND", "BTN1:2"],
    ["ESP32:33", "BTN2:1"],
    ["GND:GND", "BTN2:2"],
    ["ESP32:25", "BTN3:1"],
    ["GND:GND", "BTN3:2"],
    ["ESP32:26", "BTN4:1"],
    ["GND:GND", "BTN4:2"]
  ],
  "parts": [
    {"type": "wokwi-esp32-devkit-v1", "id": "ESP32", "top": 0, "left": 0, "attrs": {}},
    {"type": "wokwi-led", "id": "LED1", "top": 100, "left": 300, "attrs": {"color": "red"}},
    {"type": "wokwi-led", "id": "LED2", "top": 150, "left": 300, "attrs": {"color": "red"}},
    {"type": "wokwi-led", "id": "LED3", "top": 200, "left": 300, "attrs": {"color": "red"}},
    {"type": "wokwi-led", "id": "LED4", "top": 250, "left": 300, "attrs": {"color": "red"}},
    {"type": "wokwi-pushbutton", "id": "BTN1", "top": 100, "left": 400, "attrs": {}},
    {"type": "wokwi-pushbutton", "id": "BTN2", "top": 150, "left": 400, "attrs": {}},
    {"type": "wokwi-pushbutton", "id": "BTN3", "top": 200, "left": 400, "attrs": {}},
    {"type": "wokwi-pushbutton", "id": "BTN4", "top": 250, "left": 400, "attrs": {}}
  ]
}
```

## Key Logic

### Scheduling
- Each slot stores a `scheduleMinutes` value (minutes since midnight, 0–1439)
- When simulated current time equals schedule time, the slot enters "**Alerting**" state
- To prevent retriggering the same day, a `lastTriggeredDay` tracker is used

### Alert Behavior
- **Blink rate**: 500 ms on, 500 ms off (built-in LED)
- **Alert duration**: 5 minutes
- **Button response**: If user opens the lid during alert, status → "**Taken**" immediately
- **Timeout**: If lid not opened after 5 minutes, status → "**Missed**" automatically

### Built-in LED Control
- GPIO 2 is the ESP32 built-in LED
- **Always OFF** unless at least one slot is alerting
- When any slot alerting, LED **blinks in sync** with slot LEDs (500 ms interval)

## Notes

- **Simulated Clock**: The firmware uses `millis()` to track time. You control the "current time" via the web UI by setting it manually.
- **Persistent Storage**: Schedules are currently lost on reset. Use [LittleFS](https://github.com/eadf/esp32-littlefs) if you need persistence.
- **Real Time Clock**: To use actual time instead of simulated time, integrate an RTC module (e.g., DS3231) and update `currentSimMinutesOfDay()`.
- **Physical Buttons**: Pins 32, 33, 25, 26 still work as physical buttons (INPUT_PULLUP) to simulate lid open if you add push buttons.

## Troubleshooting

**"Cannot connect to Wi-Fi"**
- Check SSID and password in code
- Verify ESP32 is in range of Wi-Fi router
- Serial Monitor will warn if Wi-Fi fails; you can still use Serial commands

**"Page won't load"**
- Make sure you're using the correct IP from Serial Monitor
- Check that ESP32 is powered and running
- Try power-cycling the board

**"LEDs not blinking"**
- If using Wokwi, check connections in diagram
- On physical hardware, check pin connections
- Verify GPIO pins in `SLOT_LED_PINS[]` array match your setup

**"Status won't update"**
- Page auto-refreshes every 1 second
- If stuck, try refreshing the browser manually (F5)

## Future Enhancements

- [ ] **Persistent Storage**: Save schedules to LittleFS so they survive resets
- [ ] **Real-time Clock**: Add RTC module for actual time instead of `millis()` simulation
- [ ] **Mobile App**: Build a dedicated mobile app for easier control
- [ ] **Multiple Users**: Support user accounts and personalized schedules
- [ ] **Notifications**: Email or SMS alerts when medicine is missed
- [ ] **Dashboard**: Admin dashboard to manage multiple medicine boxes across multiple users
