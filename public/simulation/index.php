<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Medicine Box Simulation</title>
    <!-- Tailwind for quick styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { margin: 0; overflow: hidden; font-family: 'Inter', sans-serif; }
        #canvas-container { width: 100vw; height: 100vh; background: #e0e5ec; }
        #ui-layer {
            position: absolute; top: 20px; left: 20px; width: 350px;
            background: rgba(255, 255, 255, 0.9);
            padding: 20px; border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            max-height: 90vh; overflow-y: auto;
        }
        .status-dot { height: 10px; width: 10px; border-radius: 50%; display: inline-block; }
        .dot-green { background-color: #10B981; }
        .dot-red { background-color: #EF4444; }
        .dot-gray { background-color: #D1D5DB; }
        .slot-indicator {
            width: 100%; border: 1px solid #ddd; padding: 10px; margin-bottom: 8px; border-radius: 4px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .slot-active { border-color: #EF4444; background-color: #FEF2F2; animation: pulse 1s infinite; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.8; } 100% { opacity: 1; } }
    </style>
</head>
<body>

<div id="canvas-container"></div>

<div id="ui-layer">
    <h1 class="text-2xl font-bold mb-2 text-gray-800">Smart MedBox</h1>
    <div class="mb-4 text-sm text-gray-600">
        <p><strong>Simulated Time:</strong> <span id="sim-time" class="font-mono text-lg font-bold text-blue-600">--:--</span></p>
        <p><strong>Grace Period:</strong> <span id="grace-period">--</span> mins</p>
    </div>

    <div class="mb-4">
        <label class="block text-xs font-bold text-gray-500 uppercase">Controls</label>
        <div class="flex gap-2 mt-1">
            <button onclick="overrideTime(prompt('Enter Time (HH:MM):', '08:00'))" class="bg-gray-200 hover:bg-gray-300 px-3 py-1 rounded text-sm">Set Time</button>
            <button onclick="window.location.reload()" class="bg-gray-200 hover:bg-gray-300 px-3 py-1 rounded text-sm">Refresh</button>
        </div>
    </div>

    <hr class="my-4 border-gray-200">

    <h2 class="text-lg font-semibold mb-2">Medicine Slots</h2>
    <div id="slots-container">
        <!-- Slots injected here -->
        <div class="text-center text-gray-400 text-sm">Loading schedules...</div>
    </div>

    <div class="mt-4 p-2 bg-yellow-50 border border-yellow-200 rounded text-xs text-yellow-800">
        <strong>Instructions:</strong>
        <ul class="list-disc pl-4 mt-1">
            <li>Red blinking box = Time to take medicine.</li>
            <li>Click the blinking lid to "Take".</li>
            <li>If you wait too long, it marks as "Missed".</li>
        </ul>
    </div>
    
    <div id="log-console" class="mt-4 p-2 bg-black text-green-400 font-mono text-xs h-32 overflow-y-auto rounded">
        > System Initialized...
    </div>
</div>

<!-- Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<!-- Simpler OrbitControls CDN -->
<script src="https://unpkg.com/three@0.128.0/examples/js/controls/OrbitControls.js"></script>

<script src="scene.js"></script>
<script src="app.js"></script>

</body>
</html>
