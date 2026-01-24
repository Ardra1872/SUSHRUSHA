<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription History</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: "#2563EB",
                        primaryDark: "#1D4ED8",
                        surface: "#FFFFFF",
                        bg: "#F3F6FA",
                        textMain: "#1E293B",
                        textSub: "#64748B",
                        success: "#10B981",
                        warning: "#F59E0B",
                        danger: "#EF4444",
                    },
                    fontFamily: {
                        display: ["Plus Jakarta Sans", "sans-serif"],
                        body: ["Inter", "sans-serif"]
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-bg text-textMain font-body">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white border-b border-slate-200 sticky top-0 z-40">
            <div class="max-w-6xl mx-auto px-4 md:px-8 py-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <a href="dashboard.php" class="text-textSub hover:text-textMain">
                            <span class="material-symbols-outlined text-2xl">arrow_back</span>
                        </a>
                        <div>
                            <h1 class="text-3xl font-bold text-textMain flex items-center gap-3">
                                <span class="material-symbols-outlined text-4xl text-blue-600">history</span>
                                Prescription History
                            </h1>
                            <p class="text-textSub mt-1">All your medical prescriptions in one place</p>
                        </div>
                    </div>
                    <a href="add_prescription.html" class="bg-primary hover:bg-primaryDark text-white px-6 py-3 rounded-xl font-semibold flex items-center gap-2 transition hidden md:flex">
                        <span class="material-symbols-outlined">add</span>
                        New Prescription
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-6xl mx-auto px-4 md:px-8 py-8">
            <!-- Filter & Sort -->
            <div class="flex items-center gap-4 mb-6">
                <input type="text" id="searchInput" placeholder="Search by disease or doctor..."
                    class="flex-1 px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                <select id="sortSelect" class="px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary">
                    <option value="date-desc">Newest First</option>
                    <option value="date-asc">Oldest First</option>
                    <option value="name-asc">Disease (A-Z)</option>
                </select>
            </div>

            <!-- Prescriptions List -->
            <div id="prescriptionsList" class="space-y-4">
                <p class="text-center text-textSub py-8">Loading prescriptions...</p>
            </div>
        </main>
    </div>

    <!-- Prescription Detail Modal -->
    <div id="detailModal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <!-- Header -->
            <div class="sticky top-0 bg-white border-b border-slate-200 p-6 flex items-center justify-between">
                <h2 id="modalTitle" class="text-2xl font-bold text-textMain">Prescription Details</h2>
                <button onclick="closeModal()" class="text-textSub hover:text-textMain text-2xl">✕</button>
            </div>

            <!-- Content -->
            <div id="modalContent" class="p-6">
                <!-- Loaded dynamically -->
            </div>
        </div>
    </div>

    <script>
        let allPrescriptions = [];

        async function loadPrescriptions() {
            const container = document.getElementById('prescriptionsList');
            try {
                const response = await fetch('fetch_prescription.php?action=list');
                const data = await response.json();

                if (!data.success) {
                    container.innerHTML = '<p class="text-danger text-center">Failed to load prescriptions</p>';
                    return;
                }

                allPrescriptions = data.prescriptions;
                renderPrescriptions(allPrescriptions);
            } catch (error) {
                console.error('Error:', error);
                container.innerHTML = '<p class="text-danger text-center">Error loading prescriptions</p>';
            }
        }

        function renderPrescriptions(prescriptions) {
            const container = document.getElementById('prescriptionsList');

            if (prescriptions.length === 0) {
                container.innerHTML = `
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-12 text-center">
                        <span class="material-symbols-outlined text-6xl text-blue-400 block mb-3">receipt_long</span>
                        <p class="text-textSub text-lg mb-4">No prescriptions found</p>
                        <a href="add_prescription.html" class="bg-primary text-white px-6 py-2 rounded-lg inline-block">
                            Add Your First Prescription
                        </a>
                    </div>
                `;
                return;
            }

            container.innerHTML = prescriptions.map(prescription => `
                <div class="bg-white rounded-xl shadow-soft border border-slate-200 overflow-hidden hover:shadow-lg transition">
                    <div class="p-4 md:p-6">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 md:gap-6">
                            <!-- Disease Info -->
                            <div class="md:col-span-2">
                                <h3 class="text-lg md:text-xl font-bold text-textMain mb-2">${escapeHtml(prescription.disease_name)}</h3>
                                <p class="text-sm text-textSub mb-4">${escapeHtml(prescription.disease_description || 'No description provided')}</p>
                                <div class="space-y-2 text-sm">
                                    <p class="text-textSub"><strong>Doctor:</strong> ${escapeHtml(prescription.doctor_name)}</p>
                                    ${prescription.hospital_name ? `<p class="text-textSub"><strong>Hospital:</strong> ${escapeHtml(prescription.hospital_name)}</p>` : ''}
                                </div>
                            </div>

                            <!-- Date & Status -->
                            <div class="bg-slate-50 p-4 rounded-lg">
                                <p class="text-xs text-textSub font-semibold mb-2">DATE</p>
                                <p class="text-lg font-bold text-textMain">${formatDate(prescription.prescription_date)}</p>
                            </div>

                            <!-- Actions -->
                            <div class="flex flex-col gap-2">
                                <button onclick="viewDetails(${prescription.id})" class="w-full bg-primary hover:bg-primaryDark text-white px-3 py-2 rounded-lg text-sm font-semibold transition">
                                    View Details
                                </button>
                                <button onclick="deletePrescription(${prescription.id})" class="w-full border border-danger text-danger hover:bg-red-50 px-3 py-2 rounded-lg text-sm font-semibold transition">
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        async function viewDetails(prescriptionId) {
            try {
                const response = await fetch(`fetch_prescription.php?action=detail&id=${prescriptionId}`);
                const data = await response.json();

                if (!data.success) {
                    alert('Failed to load details');
                    return;
                }

                const p = data.prescription;
                const medicines = data.medicines || [];
                const tests = data.tests || [];

                const content = `
                    <div class="space-y-6">
                        <!-- Header Info -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-blue-50 p-4 rounded-xl">
                                <p class="text-xs text-textSub font-semibold mb-1">DOCTOR</p>
                                <p class="text-lg font-bold text-textMain">${escapeHtml(p.doctor_name)}</p>
                                ${p.hospital_name ? `<p class="text-sm text-textSub mt-1">${escapeHtml(p.hospital_name)}</p>` : ''}
                            </div>
                            <div class="bg-green-50 p-4 rounded-xl">
                                <p class="text-xs text-textSub font-semibold mb-1">DATE</p>
                                <p class="text-lg font-bold text-textMain">${formatDate(p.prescription_date)}</p>
                            </div>
                        </div>

                        <!-- Disease Description -->
                        ${p.disease_description ? `
                            <div class="bg-slate-50 p-4 rounded-xl">
                                <p class="text-xs text-textSub font-semibold mb-2">DISEASE DESCRIPTION</p>
                                <p class="text-textMain">${escapeHtml(p.disease_description)}</p>
                            </div>
                        ` : ''}

                        <!-- Medicines -->
                        <div>
                            <h3 class="text-lg font-bold text-textMain mb-3 flex items-center gap-2">
                                <span class="material-symbols-outlined">medication</span>
                                Medicines (${medicines.length})
                            </h3>
                            ${medicines.length > 0 ? `
                                <div class="space-y-3">
                                    ${medicines.map(m => `
                                        <div class="border border-slate-200 p-4 rounded-lg">
                                            <div class="grid grid-cols-2 gap-4 mb-3">
                                                <div>
                                                    <p class="text-xs text-textSub font-semibold mb-1">MEDICINE</p>
                                                    <p class="font-semibold text-textMain">${escapeHtml(m.medicine_name)}</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-textSub font-semibold mb-1">DOSAGE</p>
                                                    <p class="font-semibold text-textMain">${escapeHtml(m.dosage || 'N/A')}</p>
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <p class="text-xs text-textSub font-semibold mb-1">FREQUENCY</p>
                                                    <p class="text-textMain">${escapeHtml(m.frequency || 'N/A')}</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-textSub font-semibold mb-1">DURATION</p>
                                                    <p class="text-textMain">${escapeHtml(m.duration || 'N/A')}</p>
                                                </div>
                                            </div>
                                            ${m.instructions ? `
                                                <div class="mt-3 bg-yellow-50 border border-yellow-200 p-3 rounded text-sm text-textMain">
                                                    <strong>Instructions:</strong> ${escapeHtml(m.instructions)}
                                                </div>
                                            ` : ''}
                                        </div>
                                    `).join('')}
                                </div>
                            ` : '<p class="text-textSub">No medicines prescribed</p>'}
                        </div>

                        <!-- Tests -->
                        <div>
                            <h3 class="text-lg font-bold text-textMain mb-3 flex items-center gap-2">
                                <span class="material-symbols-outlined">science</span>
                                Tests (${tests.length})
                            </h3>
                            ${tests.length > 0 ? `
                                <div class="space-y-3">
                                    ${tests.map(t => `
                                        <div class="border border-slate-200 p-4 rounded-lg">
                                            <div class="grid grid-cols-2 gap-4 mb-3">
                                                <div>
                                                    <p class="text-xs text-textSub font-semibold mb-1">TEST</p>
                                                    <p class="font-semibold text-textMain">${escapeHtml(t.test_name)}</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-textSub font-semibold mb-1">TYPE</p>
                                                    <span class="inline-block bg-primary/10 text-primary px-3 py-1 rounded-full text-xs font-semibold">${escapeHtml(t.test_type)}</span>
                                                </div>
                                            </div>
                                            ${t.test_description ? `<p class="text-sm text-textMain mb-2">${escapeHtml(t.test_description)}</p>` : ''}
                                            <div class="flex items-center justify-between text-sm">
                                                <p class="text-textSub">
                                                    ${t.recommended_date ? `Recommended: ${formatDate(t.recommended_date)}` : 'No date specified'}
                                                </p>
                                                <select onchange="updateTestStatus(${t.id}, this.value)" class="px-2 py-1 border border-slate-300 rounded text-xs">
                                                    <option value="Pending" ${t.status === 'Pending' ? 'selected' : ''}>Pending</option>
                                                    <option value="Completed" ${t.status === 'Completed' ? 'selected' : ''}>Completed</option>
                                                    <option value="Cancelled" ${t.status === 'Cancelled' ? 'selected' : ''}>Cancelled</option>
                                                </select>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            ` : '<p class="text-textSub">No tests prescribed</p>'}
                        </div>

                        <!-- Notes -->
                        ${p.notes ? `
                            <div class="bg-slate-50 p-4 rounded-xl">
                                <p class="text-xs text-textSub font-semibold mb-2">NOTES</p>
                                <p class="text-textMain">${escapeHtml(p.notes)}</p>
                            </div>
                        ` : ''}
                    </div>
                `;

                document.getElementById('modalTitle').textContent = p.disease_name;
                document.getElementById('modalContent').innerHTML = content;
                document.getElementById('detailModal').classList.remove('hidden');
            } catch (error) {
                console.error('Error:', error);
                alert('Error loading details');
            }
        }

        function closeModal() {
            document.getElementById('detailModal').classList.add('hidden');
        }

        async function deletePrescription(prescriptionId) {
            if (!confirm('Are you sure you want to delete this prescription?')) return;

            try {
                const response = await fetch(`fetch_prescription.php?action=delete&id=${prescriptionId}`);
                const data = await response.json();

                if (data.success) {
                    alert('Prescription deleted');
                    loadPrescriptions();
                } else {
                    alert('Error deleting prescription');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error deleting prescription');
            }
        }

        async function updateTestStatus(testId, status) {
            try {
                await fetch('fetch_prescription.php?action=updateTestStatus', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `test_id=${testId}&status=${encodeURIComponent(status)}`
                });
            } catch (error) {
                console.error('Error:', error);
            }
        }

        function escapeHtml(text) {
            if (!text) return '';
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        function formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        }

        // Search & Sort
        document.getElementById('searchInput').addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            const filtered = allPrescriptions.filter(p =>
                p.disease_name.toLowerCase().includes(query) ||
                p.doctor_name.toLowerCase().includes(query) ||
                (p.hospital_name && p.hospital_name.toLowerCase().includes(query))
            );
            renderPrescriptions(filtered);
        });

        document.getElementById('sortSelect').addEventListener('change', (e) => {
            const sorted = [...allPrescriptions];
            if (e.target.value === 'date-asc') {
                sorted.sort((a, b) => new Date(a.prescription_date) - new Date(b.prescription_date));
            } else if (e.target.value === 'date-desc') {
                sorted.sort((a, b) => new Date(b.prescription_date) - new Date(a.prescription_date));
            } else if (e.target.value === 'name-asc') {
                sorted.sort((a, b) => a.disease_name.localeCompare(b.disease_name));
            }
            renderPrescriptions(sorted);
        });

        // Initial load
        loadPrescriptions();
    </script>
</body>
</html>
