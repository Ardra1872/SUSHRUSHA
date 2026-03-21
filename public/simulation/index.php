<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart MedBox Simulation</title>
    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />

    <style>
        body { margin: 0; overflow-x: hidden; font-family: 'Inter', sans-serif; background: #f0f4f8; }
        #canvas-container { width: 100vw; height: 100vh; position: fixed; top: 0; left: 0; z-index: 0; }
        
        <?php
        $buzzerFile = __DIR__ . '/../api/simulation/buzzer_state.json';
        $buzzerOn = false;
        if (file_exists($buzzerFile)) {
            $stateData = json_decode(file_get_contents($buzzerFile), true);
            $buzzerOn = ($stateData['buzzer'] === 'on');
        }
        ?>
        /* Glassmorphism Panel Base */
        .glass-panel {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08); 
        }

        /* Animations */
        @keyframes pulse-ring {
            0% { transform: scale(0.8); opacity: 0.5; }
            100% { transform: scale(2.2); opacity: 0; }
        }
        .animate-ring::before {
            content: ''; absolute; inset: 0; border-radius: 50%; background: inherit; z-index: -1;
            animation: pulse-ring 2s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
        }

        /* Slot Active Animation */
        .slot-active {
            border-color: #EF4444 !important;
            background-color: #FEF2F2 !important;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.15);
            animation: slot-pulse 1.5s infinite;
        }
        @keyframes slot-pulse {
            0%, 100% { box-shadow: 0 0 0 0px rgba(239, 68, 68, 0.2); }
            50% { box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
        }

        /* Scrollbars */
        .custom-scroll::-webkit-scrollbar { width: 4px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 2px; }
    </style>
</head>
<body class="text-slate-800">

<script>
    function toggleMobilePanels() {
        const panels = document.getElementById('mobile-panels-container');
        const icon = document.getElementById('toggle-icon');
        const isHidden = panels.classList.toggle('hidden');
        icon.innerText = isHidden ? 'menu' : 'close';
    }
</script>

<!-- 3D Canvas -->
<div id="canvas-container"></div>

<!-- UI LAYER (Absolute Positioning) -->
<div id="ui-layer" class="relative z-10 w-full min-h-screen pointer-events-none p-3 md:p-6 flex flex-col justify-between overflow-x-hidden">
    
    <!-- TOP BAR -->
    <header class="flex justify-between items-center gap-2 pointer-events-auto">
        <!-- Branding -->
        <div class="glass-panel p-2 md:p-4 rounded-2xl flex items-center gap-2 md:gap-4">
            <div class="size-8 md:size-10 bg-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-500/30">
                <span class="material-symbols-outlined text-lg md:text-2xl">deployed_code</span>
            </div>
            <div>
                <h1 class="text-base md:text-lg font-bold leading-tight">Smart MedBox</h1>
                <p class="text-[8px] md:text-xs text-slate-500 font-medium tracking-wide">DIGITAL TWIN</p>
            </div>
             <div class="hidden md:block h-8 w-px bg-slate-200 mx-2"></div>
             <a href="../../src/views/dashboard.php" class="hidden md:flex text-xs font-semibold text-slate-500 hover:text-blue-600 items-center gap-1 transition-colors">
                 <span class="material-symbols-outlined text-sm">arrow_back</span>
                 Back
             </a>
        </div>

        <!-- Mobile Toggle -->
        <button onclick="toggleMobilePanels()" class="md:hidden size-10 glass-panel rounded-xl flex items-center justify-center text-slate-600 active:scale-95 transition-transform">
            <span id="toggle-icon" class="material-symbols-outlined">menu</span>
        </button>

        <!-- Clock Display (Compact on Mobile) -->
        <div class="glass-panel px-3 py-2 md:p-4 rounded-2xl flex flex-col items-center md:items-end min-w-[80px] md:min-w-[160px]">
             <div id="sim-time" class="text-xl md:text-4xl font-extrabold font-mono text-slate-800 tracking-tight leading-none">08:00</div>
             <div class="hidden md:flex items-center gap-1 mt-1 text-xs text-slate-500">
                 <span class="size-1.5 md:size-2 rounded-full bg-green-500 animate-pulse"></span>
                 Running 1x
            </div>
        </div>
    </header>

    <!-- MIDDLE SECTION (Left & Right Sidebar) -->
    <div id="mobile-panels-container" class="hidden md:flex flex-1 flex flex-col md:flex-row justify-between items-center py-4 md:py-6 w-full pointer-events-none gap-4">
        
        <!-- LEFT PANEL: Medicine Slots (Scrollable if many) -->
        <div class="glass-panel w-full md:w-72 rounded-3xl p-4 md:p-5 pointer-events-auto flex flex-col max-h-[40vh] md:max-h-[60vh]">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-600 flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg">medication</span>
                    Slots Status
                </h2>
                <span class="bg-blue-100 text-blue-700 text-[10px] px-2 py-0.5 rounded-full font-bold">4 Active</span>
            </div>
            
            <div id="slots-container" class="space-y-3 overflow-y-auto custom-scroll pr-1 flex-1">
                <!-- Slots injected by JS -->
                <div class="animate-pulse flex space-x-4">
                    <div class="flex-1 space-y-4 py-1">
                        <div class="h-2 bg-slate-200 rounded"></div>
                        <div class="space-y-3">
                            <div class="grid grid-cols-3 gap-4">
                                <div class="h-2 bg-slate-200 rounded col-span-2"></div>
                                <div class="h-2 bg-slate-200 rounded col-span-1"></div>
                            </div>
                            <div class="h-2 bg-slate-200 rounded"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL: Status & Settings -->
        <div class="flex flex-col gap-4 pointer-events-auto w-full md:w-auto">
             <a href="../../src/views/dashboard.php" class="md:hidden glass-panel p-3 rounded-xl text-xs font-bold text-slate-600 flex items-center justify-center gap-2 transition-colors">
                 <span class="material-symbols-outlined text-sm">arrow_back</span>
                 Back to Dashboard
             </a>
            <div class="glass-panel w-full md:w-64 rounded-2xl p-4">
                <div class="flex justify-between items-center mb-1">
                    <span class="text-xs font-semibold text-slate-500">Sync Status</span>
                    <span id="sync-status" class="text-[10px] font-mono text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded">Never</span>
                </div>
                <div class="flex justify-between mt-2">
                    <span class="text-[10px] text-slate-400">Firmware Mode</span>
                    <span class="text-[10px] font-bold text-slate-600">Standalone</span>
                </div>
                
                <div class="h-px bg-slate-100 my-4"></div>

                <div class="flex items-center justify-between bg-slate-50/50 p-2 md:p-3 rounded-xl border border-slate-100 cursor-pointer hover:bg-slate-100 transition-colors" onclick="toggleBuzzer()">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-slate-400 text-lg">volume_up</span>
                        <span class="xs md:text-sm font-bold text-slate-600">Buzzer</span>
                    </div>
                    <span id="buzzer-badge" class="size-2 rounded-full <?php echo $buzzerOn ? 'bg-green-500' : 'bg-slate-300'; ?> transition-colors duration-200"></span>
                </div>
            </div>
        </div>

    </div>

    <!-- PUSH UP SPACER FOR MOBILE (to keep box visible) -->
    <div class="flex-1 md:hidden"></div>


    <!-- BOTTOM DOCK: Timeline & Controls -->
    <div class="flex justify-center items-end pointer-events-none pb-2 md:pb-0">
        
        <div class="glass-panel p-1.5 md:p-2 rounded-2xl flex flex-wrap justify-center items-center gap-2 pointer-events-auto transform transition-all hover:scale-[1.01] shadow-xl max-w-full">
            
            <!-- Time Controls -->
            <div class="flex items-center bg-white rounded-xl p-1 border border-slate-100 shadow-inner">
                <button onclick="overrideTime(prompt('Enter Time (HH:MM):', '08:00'))" 
                        class="px-3 md:px-4 py-1.5 md:py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs md:text-sm font-bold flex items-center gap-1 md:gap-2 transition-all active:scale-95">
                    <span class="material-symbols-outlined text-sm md:text-lg">schedule</span>
                    Set Time
                </button>
                <div class="w-px h-5 md:h-6 bg-slate-200 mx-1"></div>
                <button onclick="window.location.reload()" 
                        class="size-8 md:size-9 rounded-lg hover:bg-slate-100 text-slate-500 flex items-center justify-center transition-colors" title="Reset Simulation">
                    <span class="material-symbols-outlined text-sm md:text-lg">restart_alt</span>
                </button>
            </div>

            <!-- Grace Period Indicator -->
            <div class="px-4 flex flex-col justify-center border-l border-slate-200/50 pl-4">
                 <span class="text-[10px] font-bold uppercase text-slate-400 tracking-wide">Grace Period</span>
                 <span class="text-sm font-bold text-slate-700"><span id="grace-period">--</span> min</span>
            </div>

            <!-- Log Toggle (Optional, minimal log view) -->
            <div class="relative group hidden sm:block">
                <div id="log-console" class="w-48 md:w-64 h-10 md:h-12 bg-slate-900 rounded-xl p-2 md:p-2.5 font-mono text-[8px] md:text-[10px] text-green-400 overflow-hidden leading-tight opacity-80 hover:opacity-100 transition-opacity cursor-pointer">
                    > System Ready...
                </div>
                <!-- Tooltip like behavior for full logs could be added -->
            </div>

        </div>
    </div>

</div>

<!-- Logic Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="https://unpkg.com/three@0.128.0/examples/js/controls/OrbitControls.js"></script>
<script src="scene.js"></script>
<script src="app.js"></script>

</body>
</html>
