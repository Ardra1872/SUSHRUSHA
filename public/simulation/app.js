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
    lastSync: "Never"
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
        // Find ALL medicines assigned to this slot
        const meds = STATE.schedules.filter(s => s.slot_id === slotId);

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

    STATE.schedules.forEach(sched => {
        const scheduleId = sched.schedule_id;

        // Skip if already taken/missed in DB, OR if not due today
        if (sched.log_status || !sched.is_due_today) return;

        const alertInfo = STATE.activeAlerts[scheduleId];

        // START ALERT if time matches and not already alerting
        if (sched.intake_time_formatted === nowStr) {
            if (!alertInfo) {
                log(`🔔 [Hardware] Alert started for Slot ${sched.slot_id + 1}: ${sched.medicine_name}`);
                startAlert(sched);
            }
        }

        // If alerting, check timeout
        if (alertInfo && alertInfo.status === 'Alerting') {
            const elapsedMs = Date.now() - alertInfo.startedAt;

            if (elapsedMs > ALERT_DURATION) {
                // TIMEOUT -> MISSED (Mirror firmware logic)
                log(`Slot ${sched.slot_id + 1} Timeout! Dosing Missed.`);
                alertInfo.status = 'Missed';
                reportAction(scheduleId, 'MISSED');
                // We keep it in activeAlerts as 'Missed' for UI but stop LED
            }
        }
    });

    // Update 3D scene: only slots with status 'Alerting' should blink
    const alertingSlots = Object.values(STATE.activeAlerts)
        .filter(a => a.status === 'Alerting')
        .map(a => a.slotId);

    if (window.SceneManager) {
        window.SceneManager.updateAlerts(alertingSlots);
    }
}

function startAlert(sched) {
    log(`⏰ ALERT: Time for ${sched.medicine_name} (Slot ${sched.slot_id + 1})`);
    STATE.activeAlerts[sched.schedule_id] = {
        slotId: sched.slot_id,
        startedAt: Date.now(),
        status: 'Alerting'
    };
    playSound();
}

function stopAlert(scheduleId) {
    delete STATE.activeAlerts[scheduleId];
    sync3D();
}

function sync3D() {
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

// 4. Handle User Actions (Lid Open)
window.onLidClick = function (slotIndex) {
    log(`User opened Slot ${slotIndex + 1}...`);

    const alertEntry = Object.entries(STATE.activeAlerts).find(([id, info]) => info.slotId === slotIndex && info.status === 'Alerting');

    if (alertEntry) {
        const [schedId, info] = alertEntry;
        log(`✅ Valid Dose Taken for Schedule #${schedId}`);
        info.status = 'Taken';
        reportAction(schedId, 'TAKEN');
        sync3D();

        window.SceneManager.animateLid(slotIndex);
    } else {
        log(`(Opened empty/inactive slot)`);
        window.SceneManager.animateLid(slotIndex);
    }
};

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
    const badge = document.getElementById('buzzer-badge');
    if (badge) badge.classList.replace('bg-slate-300', 'bg-red-500');

    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        osc.connect(ctx.destination);
        osc.frequency.value = 800; // Hz
        osc.start();
        setTimeout(() => {
            osc.stop();
            if (badge) badge.classList.replace('bg-red-500', 'bg-slate-300');
        }, 200);
    } catch (e) {
        if (badge) badge.classList.replace('bg-red-500', 'bg-slate-300');
    }
}

window.overrideTime = function (t) {
    if (t) {
        STATE.overrideTime = t;
        fetchState();
    }
};

// Init
setInterval(fetchState, TICK_RATE);
fetchState();
