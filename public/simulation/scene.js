/**
 * scene.js
 * Three.js 3D Visualizer for Medicine Box
 */

const SceneManager = {
    scene: null,
    camera: null,
    renderer: null,
    lids: [],
    leds: [],
    alertState: [false, false, false, false], // Slot 0-3

    init: function () {
        const container = document.getElementById('canvas-container');

        // Scene
        this.scene = new THREE.Scene();
        this.scene.background = new THREE.Color(0xe0e5ec);

        // Camera
        this.camera = new THREE.PerspectiveCamera(45, window.innerWidth / window.innerHeight, 0.1, 1000);
        this.camera.position.set(0, 15, 15);
        this.camera.lookAt(0, 0, 0);

        // Renderer
        this.renderer = new THREE.WebGLRenderer({ antialias: true });
        this.renderer.setSize(window.innerWidth, window.innerHeight);
        this.renderer.shadowMap.enabled = true;
        container.appendChild(this.renderer.domElement);

        // Lights
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
        this.scene.add(ambientLight);

        const dirLight = new THREE.DirectionalLight(0xffffff, 0.8);
        dirLight.position.set(10, 20, 10);
        dirLight.castShadow = true;
        this.scene.add(dirLight);

        // Controls
        const controls = new THREE.OrbitControls(this.camera, this.renderer.domElement);
        controls.enableDamping = true;

        // Build Box
        this.buildBox();

        // Interaction
        window.addEventListener('resize', this.onWindowResize.bind(this), false);
        window.addEventListener('click', this.onMouseClick.bind(this), false);

        // Animation Loop
        this.animate();
    },

    buildBox: function () {
        // Base Box
        const baseGeo = new THREE.BoxGeometry(8, 2, 4);
        const baseMat = new THREE.MeshStandardMaterial({ color: 0xffffff });
        const base = new THREE.Mesh(baseGeo, baseMat);
        base.receiveShadow = true;
        base.castShadow = true;
        this.scene.add(base);

        // Compartments (visual dividers) - Just logic, mesh is simple
        // Slots are arranged:
        // Slot 0: x = -3
        // Slot 1: x = -1
        // Slot 2: x = 1
        // Slot 3: x = 3

        const slotPositions = [-3, -1, 1, 3];

        slotPositions.forEach((x, index) => {
            // 1. Lid (Hinged at back z = -2 ?? Or simply translate up/rot)
            // Let's make a simple lid
            const lidGroup = new THREE.Group();
            lidGroup.position.set(x, 1, -2); // Hinge point at back top edge

            const lidGeo = new THREE.BoxGeometry(1.8, 0.2, 3.8);
            const lidMat = new THREE.MeshStandardMaterial({ color: 0x4B5563 }); // Dark Gray
            const lidMesh = new THREE.Mesh(lidGeo, lidMat);
            lidMesh.position.set(0, 0, 1.9); // Offset so pivot is at edge
            lidMesh.castShadow = true;

            // User Data for Raycasting
            lidMesh.userData = { isLid: true, slotIndex: index };

            lidGroup.add(lidMesh);
            this.scene.add(lidGroup);

            this.lids.push({ group: lidGroup, open: false, targetRot: 0 });

            // 2. LED (Small sphere on front)
            const ledGeo = new THREE.SphereGeometry(0.2, 16, 16);
            const ledMat = new THREE.MeshBasicMaterial({ color: 0x333333 }); // Off
            const led = new THREE.Mesh(ledGeo, ledMat);
            led.position.set(x, 0.5, 2.1); // Front face
            this.scene.add(led);

            this.leds.push({ mesh: led, active: false });
        });
    },

    updateAlerts: function (activeSlotIds) {
        // Reset all
        this.leds.forEach(l => {
            l.active = false;
            l.mesh.material.color.setHex(0x333333);
        });

        // Set active
        activeSlotIds.forEach(id => {
            if (this.leds[id]) {
                this.leds[id].active = true;
            }
        });
    },

    animateLid: function (index) {
        const lid = this.lids[index];
        if (!lid) return;

        // Toggle
        lid.open = !lid.open;
        lid.targetRot = lid.open ? -Math.PI / 2 : 0; // Rotate -90deg to open

        // If opening, auto-close after 2 seconds (simulation convenience)
        if (lid.open) {
            setTimeout(() => {
                lid.open = false;
                lid.targetRot = 0;
            }, 2000);
        }
    },

    onMouseClick: function (event) {
        const raycaster = new THREE.Raycaster();
        const mouse = new THREE.Vector2();

        mouse.x = (event.clientX / window.innerWidth) * 2 - 1;
        mouse.y = -(event.clientY / window.innerHeight) * 2 + 1;

        raycaster.setFromCamera(mouse, this.camera);

        const intersects = raycaster.intersectObjects(this.scene.children, true);

        for (let i = 0; i < intersects.length; i++) {
            const obj = intersects[i].object;
            if (obj.userData && obj.userData.isLid) {
                // Call App Logic
                if (window.onLidClick) {
                    window.onLidClick(obj.userData.slotIndex);
                }
                break;
            }
        }
    },

    onWindowResize: function () {
        this.camera.aspect = window.innerWidth / window.innerHeight;
        this.camera.updateProjectionMatrix();
        this.renderer.setSize(window.innerWidth, window.innerHeight);
    },

    animate: function () {
        requestAnimationFrame(this.animate.bind(this));

        // Animate Lids
        this.lids.forEach(lid => {
            // Smooth lerp
            lid.group.rotation.x += (lid.targetRot - lid.group.rotation.x) * 0.1;
        });

        // Animate LEDs (Blink if active)
        const time = Date.now() * 0.005; // Speed
        this.leds.forEach(led => {
            if (led.active) {
                // Blink Red
                const intensity = (Math.sin(time * 2) + 1) / 2; // 0 to 1
                if (intensity > 0.5) {
                    led.mesh.material.color.setHex(0xFF0000);
                } else {
                    led.mesh.material.color.setHex(0x550000);
                }
            }
        });

        this.renderer.render(this.scene, this.camera);
    }
};

// Start
SceneManager.init();
window.SceneManager = SceneManager;
