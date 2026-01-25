/**
 * app.js
 * Logic Controller for Smart Medicine Box Simulation
 */

const STATE = {
    currentTime: "00:00",
    schedules: [],
    gracePeriodMinutes: 5,
    activeAlerts: {}, // Map schedule_id -> { startTime: Date, slotId: int }
    overrideTime: null
};

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
    let url = '../api/simulation/get_state.php';
    if (STATE.overrideTime) {
        url += `?time=${STATE.overrideTime}`;
    }

    try {
        const res = await fetch(url);
        const data = await res.json();

        if (data.error) {
            log("Authentication Error: " + data.error);
            alert("You must be logged in to view the simulation.");
            window.location.href = '../login.php'; // Redirect to login
            return;
        }

        STATE.currentTime = data.current_time;
        STATE.gracePeriodMinutes = data.grace_period_minutes;
        STATE.schedules = data.schedules;

        updateUI();
        checkAlerts();

        // Update 3D Scene Text/Context if needed
        // (Visual updates happen in checkAlerts via Scene API)

    } catch (e) {
        console.error("Fetch Error", e);
        log("Fetch Error: " + e.message);
    }
}

// 2. Update UI DOM
function updateUI() {
    document.getElementById('sim-time').innerText = STATE.currentTime;
    document.getElementById('grace-period').innerText = STATE.gracePeriodMinutes;

    const container = document.getElementById('slots-container');
    container.innerHTML = '';

    // Group by Slot
    const slots = [0, 1, 2, 3];

    slots.forEach(slotId => {
        // Find medicines for this slot
        const meds = STATE.schedules.filter(s => s.slot_id === slotId);

        let content = `<div class="font-bold text-gray-700">Slot ${slotId + 1}</div>`;

        if (meds.length === 0) {
            content += `<div class="text-xs text-gray-400">Empty</div>`;
        } else {
            meds.forEach(m => {
                let statusBadge = '';
                if (m.log_status === 'TAKEN') statusBadge = '<span class="text-xs bg-green-100 text-green-800 px-1 rounded">Taken</span>';
                else if (m.log_status === 'MISSED') statusBadge = '<span class="text-xs bg-red-100 text-red-800 px-1 rounded">Missed</span>';
                else statusBadge = '<span class="text-xs bg-gray-100 text-gray-800 px-1 rounded">Pending</span>';

                content += `
                    <div class="text-xs mt-1 flex justify-between">
                        <span>${m.intake_time_formatted} - ${m.medicine_name}</span>
                        ${statusBadge}
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

// 3. Check Alerts Logic
function checkAlerts() {
    const nowStr = STATE.currentTime; // HH:MM

    STATE.schedules.forEach(sched => {
        // Only check if not already logged
        if (sched.log_status) return;

        // If time matches
        if (sched.intake_time_formatted === nowStr) {
            if (!STATE.activeAlerts[sched.schedule_id]) {
                // START ALERT
                startAlert(sched);
            }
        }

        // If alert is active, check timeout
        if (STATE.activeAlerts[sched.schedule_id]) {
            const alertInfo = STATE.activeAlerts[sched.schedule_id];
            // Calc elapsed minutes (Simulation simplified: we count real seconds as minutes ?? 
            // NO, for accurate testing we should track "simulated time" progression or just use real wall-clock for grace period timeout if manual time set.
            // For this simulation: we'll use a simple counter since last check.

            // Actually, best way: Store Timestamp when alert started. Compare with current Date.now()
            const elapsedMs = Date.now() - alertInfo.startedAt;
            const elapsedMins = elapsedMs / 60000;

            if (elapsedMins > STATE.gracePeriodMinutes) {
                // TIMEOUT -> MISSED
                log(`Slot ${sched.slot_id + 1} Timeout! Dosing Missed.`);
                reportAction(sched.schedule_id, 'MISSED');
                stopAlert(sched.schedule_id);
            }
        }
    });

    // Sync specific slots in 3D scene
    const activeSlots = Object.values(STATE.activeAlerts).map(a => a.slotId);
    if (window.SceneManager) {
        window.SceneManager.updateAlerts(activeSlots);
    }
}

function startAlert(sched) {
    log(`⏰ ALERT: Time for ${sched.medicine_name} (Slot ${sched.slot_id + 1})`);
    STATE.activeAlerts[sched.schedule_id] = {
        slotId: sched.slot_id,
        startedAt: Date.now()
    };
    // Trigger visual/audio
    // Audio: Simple beep
    playSound();
}

function stopAlert(scheduleId) {
    delete STATE.activeAlerts[scheduleId];
    // Update 3D immediately
    const activeSlots = Object.values(STATE.activeAlerts).map(a => a.slotId);
    if (window.SceneManager) {
        window.SceneManager.updateAlerts(activeSlots);
    }
}

function isSlotActive(slotId) {
    return Object.values(STATE.activeAlerts).some(a => a.slotId === slotId);
}

// 4. Handle User Actions (Lid Open)
// Called from scene.js when user clicks a mesh
window.onLidClick = function (slotIndex) { // 0-based
    log(`User opened Slot ${slotIndex + 1}...`);

    // Check if this slot has an active alert
    const alertEntry = Object.entries(STATE.activeAlerts).find(([id, info]) => info.slotId === slotIndex);

    if (alertEntry) {
        const [schedId, info] = alertEntry;
        log(`✅ Valid Dose Taken for Schedule #${schedId}`);
        reportAction(schedId, 'TAKEN');
        stopAlert(schedId);

        // Animate Lid Open/Close
        window.SceneManager.animateLid(slotIndex);
    } else {
        log(`(Opened empty/inactive slot)`);
        window.SceneManager.animateLid(slotIndex); // Just open for fun
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
    // Simple oscillator beep
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        osc.connect(ctx.destination);
        osc.frequency.value = 800; // Hz
        osc.start();
        setTimeout(() => osc.stop(), 200);
    } catch (e) {
        // Audio might be blocked by browser policy until interaction
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
