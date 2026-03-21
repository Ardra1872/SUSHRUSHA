/**
 * app.js
 * Logic Controller for Smart Medicine Box Simulation
 */

const STATE = {
    currentTime: "00:00",
    schedules: [],
    gracePeriodMinutes: 5,
    activeAlerts: {}, // Map schedule_id -> { startTime: Date, slotId: int }
    overrideTime: null,
    lastSync: "Never",
    buzzerOn: false
};

// Firmware constants
const ALERT_DURATION = 5 * 60 * 1000; // 5 minutes
const BLINK_INTERVAL = 500;

// Polling Interval
const TICK_RATE = 1000; // 1 second

function log(msg) {
    const consoleDiv = document.getElementById('log-console');
    const time = new Date().toLocaleTimeString();
    consoleDiv.innerHTML += `<div>[${time}] ${msg}</div>`;
    consoleDiv.scrollTop = consoleDiv.scrollHeight;
}

// 1. Fetch State from API
async function fetchState() {
    log("📡 [System] Syncing schedules from backend...");
    let url = '../api/simulation/get_state.php';
    if (STATE.overrideTime) {
        url += `?time=${STATE.overrideTime}`;
    }

    try {
        const res = await fetch(url);
        const data = await res.json();

        if (data.error) {
            log("❌ [Error] Authentication failed: " + data.error);
            alert("You must be logged in to view the simulation.");
            window.location.href = '../login.php';
            return;
        }

        STATE.currentTime = data.current_time;
        STATE.gracePeriodMinutes = data.grace_period_minutes;
        STATE.schedules = data.schedules;
        STATE.lastSync = new Date().toLocaleTimeString();

        // 🔥 Sync Buzzer State from main API
        if (data.buzzer_state) {
            const isBuzzerOn = data.buzzer_state === 'on';
            if (STATE.buzzerOn !== isBuzzerOn) {
                STATE.buzzerOn = isBuzzerOn;
                const badge = document.getElementById("buzzer-badge");
                if (badge) {
                    badge.classList.toggle("bg-green-500", STATE.buzzerOn);
                    badge.classList.toggle("bg-slate-300", !STATE.buzzerOn);
                }
            }
        }

        log(`✅ [System] Sync Complete. Current Time: ${STATE.currentTime}`);

        updateUI();
        checkAlerts();

    } catch (e) {
        console.error("Fetch Error", e);
        log("❌ [Error] Fetch failed: " + e.message);
    }
}

// 2. Update UI DOM
function updateUI() {
    document.getElementById('sim-time').innerText = STATE.currentTime;
    document.getElementById('grace-period').innerText = STATE.gracePeriodMinutes;
    if (document.getElementById('sync-status')) {
        document.getElementById('sync-status').innerText = STATE.lastSync;
    }

    const container = document.getElementById('slots-container');
    container.innerHTML = '';

    const slots = [0, 1, 2, 3];

    slots.forEach(slotId => {
        // Find ALL medicines assigned to this slot (slot_id is now 1-based from API)
        const meds = STATE.schedules.filter(s => parseInt(s.slot_id) === (slotId + 1));

        let content = `<div class="font-bold text-gray-700">Slot ${slotId + 1}</div>`;

        if (meds.length === 0) {
            content += `<div class="text-xs text-gray-400 italic">Empty</div>`;
        } else {
            meds.forEach(m => {
                let statusBadge = '';
                const alertInfo = STATE.activeAlerts[m.schedule_id];

                if (m.log_status === 'TAKEN' || (alertInfo && alertInfo.status === 'Taken')) {
                    statusBadge = '<span class="text-[10px] bg-green-100 text-green-800 px-1 rounded uppercase font-bold">Taken</span>';
                } else if (m.log_status === 'MISSED' || (alertInfo && alertInfo.status === 'Missed')) {
                    statusBadge = '<span class="text-[10px] bg-red-100 text-red-800 px-1 rounded uppercase font-bold">Missed</span>';
                } else if (alertInfo && alertInfo.status === 'Alerting') {
                    statusBadge = '<span class="text-[10px] bg-yellow-100 text-yellow-800 px-1 rounded animate-pulse uppercase font-bold">Alerting</span>';
                } else if (!m.is_due_today) {
                    statusBadge = '<span class="text-[10px] bg-slate-100 text-slate-400 px-1 rounded uppercase font-bold">Not Today</span>';
                } else {
                    statusBadge = '<span class="text-[10px] bg-blue-50 text-blue-600 px-1 rounded uppercase font-bold">Waiting</span>';
                }

                content += `
                    <div class="text-xs mt-1 flex flex-col gap-0.5 p-2 rounded bg-slate-50/50 border border-slate-100">
                        <div class="flex justify-between items-center">
                            <span class="font-semibold">${m.medicine_name}</span>
                            ${statusBadge}
                        </div>
                        <div class="text-[10px] text-slate-500 font-mono">
                           ${m.intake_time_formatted || 'As Needed'}
                        </div>
                        ${(alertInfo && alertInfo.status === 'Alerting') ? `
                        <button onclick="triggerReedSwitch(${slotId})" class="mt-2 w-full py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl flex items-center justify-center gap-1 transition-all active:scale-95 shadow-lg shadow-blue-500/20">
                            <span class="material-symbols-outlined text-sm">sensors</span>
                            Trigger Reed Switch
                        </button>
                        ` : ''}
                    </div>
                `;
            });
        }

        const div = document.createElement('div');
        div.className = `slot-indicator ${isSlotActive(slotId) ? 'slot-active' : 'bg-white'}`;
        div.innerHTML = content;
        container.appendChild(div);
    });
}

// 3. Check Alerts Logic (Mirrors Firmware)
function checkAlerts() {
    const nowStr = STATE.currentTime;
    let alertsChanged = false;

    // A. Sync from DB: START or STOP alerts based on schedule status
    STATE.schedules.forEach(sched => {
        const scheduleId = String(sched.schedule_id);
        const alertInfo = STATE.activeAlerts[scheduleId];

        const isTakenOrExpired = sched.log_status || !sched.is_due_today;

        if (isTakenOrExpired) {
            if (alertInfo) {
                log(`✅ [Hardware] Alert cleared for #${scheduleId} (Slot ${sched.slot_id}) - Status: ${sched.log_status || 'Expired'}`);
                delete STATE.activeAlerts[scheduleId];
                alertsChanged = true;
            }
            return;
        }

        // START ALERT if time matches and not already alerting/taken
        if (sched.intake_time_formatted === nowStr) {
            if (!alertInfo) {
                log(`🔔 [Hardware] Alert started for Slot ${sched.slot_id}: ${sched.medicine_name}`);
                startAlert(sched);
                alertsChanged = true;
            }
        }
    });

    // B. Internal Timeouts: Check if alerting items have timed out
    Object.entries(STATE.activeAlerts).forEach(([id, info]) => {
        if (info.status === 'Alerting') {
            const elapsedMs = Date.now() - info.startedAt;
            if (elapsedMs > ALERT_DURATION) {
                log(`Slot ${info.slotId + 1} Timeout! Dosing Missed.`);
                info.status = 'Missed';
                reportAction(id, 'MISSED');
                alertsChanged = true;
            }
        }
    });

    // Update 3D scene: only slots with status 'Alerting' should blink
    updateSimulation3D();
}

function startAlert(sched) {
    log(`⏰ ALERT: Time for ${sched.medicine_name} (Slot ${sched.slot_id})`);
    STATE.activeAlerts[sched.schedule_id] = {
        slotId: sched.slot_id - 1, // Convert 1-based to 0-based for internal state
        startedAt: Date.now(),
        status: 'Alerting'
    };
    playSound();
}

function stopAlert(scheduleId) {
    delete STATE.activeAlerts[scheduleId];
    updateSimulation3D();
}

function updateSimulation3D() {
    const alertingSlots = Object.values(STATE.activeAlerts)
        .filter(a => a.status === 'Alerting')
        .map(a => a.slotId);

    if (window.SceneManager) {
        window.SceneManager.updateAlerts(alertingSlots);
    }
}

function isSlotActive(slotId) {
    return Object.values(STATE.activeAlerts).some(a => a.slotId === slotId && a.status === 'Alerting');
}

// 4. Handle User Actions (Reed Switch / Lid Open)
window.triggerReedSwitch = function (slotIndex) {
    log(`🧲 [Reed Switch] Triggered for Slot ${slotIndex + 1}...`);

    // Find ALL alerting medicines for this slot
    const alertingDoses = Object.entries(STATE.activeAlerts).filter(([id, info]) => info.slotId === slotIndex && info.status === 'Alerting');

    if (alertingDoses.length > 0) {
        alertingDoses.forEach(([schedId, info]) => {
            log(`✅ Valid Dose Taken for Schedule #${schedId}`);
            info.status = 'Taken';
            reportAction(schedId, 'TAKEN');
        });

        updateSimulation3D();
        window.SceneManager.animateLid(slotIndex);
    } else {
        log(`(Opened empty/inactive slot)`);
        window.SceneManager.animateLid(slotIndex);
    }
};

// Compatibility for 3D clicks
window.onLidClick = window.triggerReedSwitch;

// 5. Send Action to API
async function reportAction(scheduleId, status) {
    try {
        await fetch('../api/simulation/log_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ schedule_id: scheduleId, status: status })
        });
        // Force refresh state
        fetchState();
    } catch (e) {
        console.error("Report Error", e);
    }
}

// Utils
function playSound() {
    if (!STATE.buzzerOn) {
        log("🔇 [Buzzer] Muted (Software Toggle)");
        return;
    }

    const badge = document.getElementById('buzzer-badge');
    const originalColor = STATE.buzzerOn ? 'bg-green-500' : 'bg-slate-300';
    if (badge) badge.classList.replace(originalColor, 'bg-red-500');

    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        osc.connect(ctx.destination);
        osc.frequency.value = 800; // Hz
        osc.start();
        setTimeout(() => {
            osc.stop();
            if (badge) badge.classList.replace('bg-red-500', originalColor);
        }, 200);
    } catch (e) {
        if (badge) badge.classList.replace('bg-red-500', originalColor);
    }
}

window.overrideTime = function (t) {
    if (t) {
        STATE.overrideTime = t;
        fetchState();
    }
};
async function toggleBuzzer() {
    const newState = !STATE.buzzerOn;
    const action = newState ? 'on' : 'off';

    try {
        const res = await fetch(`../api/simulation/set_buzzer.php?action=${action}`);
        const data = await res.json();

        if (data.status === 'success') {
            STATE.buzzerOn = newState;
            const badge = document.getElementById("buzzer-badge");
            if (badge) {
                badge.classList.toggle("bg-green-500", STATE.buzzerOn);
                badge.classList.toggle("bg-slate-300", !STATE.buzzerOn);
            }
            log(`🔔 [Buzzer] Toggled ${action.toUpperCase()}`);
        }
    } catch (err) {
        console.error("Buzzer Toggle Error:", err);
        log("❌ [Error] Failed to toggle buzzer");
    }
}

// Init
setInterval(fetchState, TICK_RATE);
fetchState();
