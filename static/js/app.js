document.addEventListener("DOMContentLoaded", () => {
    // DOM Elements
    const statusDot = document.getElementById("status-dot");
    const statusText = document.getElementById("status-text");
    const authSection = document.getElementById("auth-section");
    const dashboardSection = document.getElementById("dashboard-section");
    const logoutBtn = document.getElementById("logout-btn");

    // QR & Pairing DOMs
    const qrWrapper = document.getElementById("qr-wrapper");
    const qrImg = document.getElementById("qr-image");
    const pairingForm = document.getElementById("pairing-form");
    const pairingBtn = document.getElementById("pairing-btn");
    const pairingCodeDisplay = document.getElementById("pairing-code-display");
    const pairingCodeVal = document.getElementById("pairing-code-val");

    // Send Form DOMs
    const sendForm = document.getElementById("send-form");
    const sendBtn = document.getElementById("send-btn");

    // Tab DOMs
    const tabBtns = document.querySelectorAll(".tab-btn");
    const tabContents = document.querySelectorAll(".tab-content");

    // Stats & History
    const statPending = document.getElementById("stat-pending");
    const statSent = document.getElementById("stat-sent");
    const statFailed = document.getElementById("stat-failed");
    const historyList = document.getElementById("history-list");

    // AI Settings DOMs
    const aiSettingsDeviceId = document.getElementById("ai-settings-device-id");
    const aiEnabledCheckbox = document.getElementById("ai-enabled-checkbox");
    const aiConfigFields = document.getElementById("ai-config-fields");
    const aiGeminiKey = document.getElementById("ai-gemini-key");
    const aiRolePreset = document.getElementById("ai-role-preset");
    const aiInstructions = document.getElementById("ai-instructions");
    const aiSettingsForm = document.getElementById("ai-settings-form");
    const saveAiSettingsBtn = document.getElementById("save-ai-settings-btn");

    let activeDevice = "1";
    let isConnected = false;
    let pollInterval = null;

    // Tabs toggle
    tabBtns.forEach(btn => {
        btn.addEventListener("click", () => {
            tabBtns.forEach(b => b.classList.remove("active"));
            tabContents.forEach(c => c.classList.remove("active"));

            btn.classList.add("active");
            const tabId = btn.getAttribute("data-tab");
            document.getElementById(`tab-${tabId}`).classList.add("active");
        });
    });

    // Device selection toggle
    const deviceBtns = document.querySelectorAll(".device-tab-btn");
    deviceBtns.forEach(btn => {
        btn.addEventListener("click", () => {
            deviceBtns.forEach(b => b.classList.remove("active"));
            btn.classList.add("active");
            activeDevice = btn.getAttribute("data-device");
            
            // Update UI device label and fetch settings
            if (aiSettingsDeviceId) {
                aiSettingsDeviceId.innerText = activeDevice;
            }
            loadAiSettings(activeDevice);
            
            // Immediately refresh status for the selected device
            checkStatus();
        });
    });

    // Check Engine Status
    async function checkStatus() {
        try {
            const res = await fetch(`/api/status?device_id=${activeDevice}`);
            const data = await res.json();
            
            // Update connection state dots for all devices in the selector
            if (data.devices) {
                Object.entries(data.devices).forEach(([devId, devInfo]) => {
                    const dot = document.getElementById(`device-dot-${devId}`);
                    if (dot) {
                        dot.className = `device-dot ${devInfo.state}`;
                    }
                });
            }

            const current = data.current || { state: "disconnected", qr: null, pairingCode: null, user: null };
            
            // Set Connection Class on main status Dot
            statusDot.className = "status-dot " + current.state;
            
            if (current.state === "connected") {
                isConnected = true;
                statusText.innerHTML = `Device ${activeDevice} Connected (+${current.user})`;
                authSection.style.display = "none";
                dashboardSection.style.display = "block";
                logoutBtn.style.display = "block";
                
                // Stop rendering QR and pairing codes
                qrImg.src = "";
                pairingCodeDisplay.style.display = "none";
            } else {
                isConnected = false;
                logoutBtn.style.display = "none";
                authSection.style.display = "block";
                dashboardSection.style.display = "none";

                if (current.state === "connecting") {
                    statusText.innerText = `Device ${activeDevice}: Connecting to WhatsApp...`;
                    qrWrapper.style.display = "none";
                    pairingCodeDisplay.style.display = "none";
                } else if (current.state === "qr") {
                    statusText.innerText = `Device ${activeDevice}: Ready to Link Device`;
                    
                    if (current.qr) {
                        qrWrapper.style.display = "flex";
                        qrImg.src = current.qr;
                    } else {
                        qrWrapper.style.display = "none";
                    }

                    if (current.pairingCode) {
                        pairingCodeDisplay.style.display = "block";
                        pairingCodeVal.innerText = formatPairingCode(current.pairingCode);
                    } else {
                        pairingCodeDisplay.style.display = "none";
                    }
                } else {
                    statusText.innerText = `Device ${activeDevice}: Disconnected`;
                    qrWrapper.style.display = "none";
                    pairingCodeDisplay.style.display = "none";
                }
            }
        } catch (err) {
            console.error("Error checking status:", err);
            statusDot.className = "status-dot disconnected";
            statusText.innerText = "Engine Offline";
        }
    }

    function formatPairingCode(code) {
        if (!code) return "";
        if (code.includes("-")) return code;
        return code.slice(0, 4) + "-" + code.slice(4);
    }

    // Submit Pairing Phone Form
    pairingForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        const number = document.getElementById("pairing-phone").value.trim();
        if (!number) return;

        pairingBtn.disabled = true;
        pairingBtn.innerText = "Requesting Code...";

        try {
            const res = await fetch("/api/pair", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ number: number, device_id: activeDevice })
            });
            const data = await res.json();
            
            if (res.ok && data.code) {
                pairingCodeDisplay.style.display = "block";
                pairingCodeVal.innerText = formatPairingCode(data.code);
                // Trigger immediate status check to verify changes
                checkStatus();
            } else {
                alert("Error: " + (data.error || "Failed to get code"));
            }
        } catch (err) {
            console.error("Pair request error:", err);
            alert("Connection error occurred requesting code.");
        } finally {
            pairingBtn.disabled = false;
            pairingBtn.innerText = "Generate Pairing Code";
        }
    });

    // Send Notification Form
    sendForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        const number = document.getElementById("send-phone").value.trim();
        const message = document.getElementById("send-message").value.trim();
        const deviceId = document.getElementById("send-device").value;

        if (!number || !message) {
            alert("Please fill in both fields");
            return;
        }

        sendBtn.disabled = true;
        sendBtn.innerText = "Queueing Message...";

        try {
            const payload = { number, message };
            if (deviceId) {
                payload.device_id = deviceId;
            }
            
            const res = await fetch("/api/send", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (res.ok && data.success) {
                document.getElementById("send-message").value = "";
                // Switch to logs tab and refresh stats
                document.querySelector('[data-tab="logs"]').click();
                fetchHistory();
                fetchStats();
            } else {
                alert("Failed to queue: " + (data.error || "Unknown error"));
            }
        } catch (err) {
            console.error("Send error:", err);
            alert("Connection error sending message");
        } finally {
            sendBtn.disabled = false;
            sendBtn.innerText = "Queue Notification";
        }
    });

    // Logout Action
    logoutBtn.addEventListener("click", async () => {
        if (!confirm(`Are you sure you want to disconnect WhatsApp session for Device ${activeDevice}?`)) return;
        try {
            await fetch("/api/logout", { 
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ device_id: activeDevice })
            });
            checkStatus();
        } catch (err) {
            console.error("Logout error:", err);
        }
    });

    // Fetch Stats & History
    async function fetchStats() {
        try {
            const res = await fetch("/api/stats");
            const data = await res.json();
            statPending.innerText = data.pending;
            statSent.innerText = data.sent;
            statFailed.innerText = data.failed;
        } catch (err) {
            console.error("Stats fetch error:", err);
        }
    }

    async function fetchHistory() {
        try {
            const res = await fetch("/api/history");
            const data = await res.json();
            
            if (data.length === 0) {
                historyList.innerHTML = `<div style="text-align: center; color: var(--text-secondary); margin-top: 2rem;">No notifications sent yet.</div>`;
                return;
            }

            historyList.innerHTML = data.map(item => {
                const date = new Date(item.created_at + "Z").toLocaleString();
                const errSnippet = item.error ? `<div class="history-error" style="color: var(--color-error); font-size: 0.8rem; margin-top: 0.25rem;">Err: ${item.error}</div>` : "";
                const devSnippet = item.device_id ? `<span style="opacity: 0.7; margin-left: 0.5rem;">[Dev ${item.device_id}]</span>` : "";
                
                return `
                    <div class="history-item">
                        <div class="history-meta">
                            <span class="history-phone">${item.phone}${devSnippet}</span>
                            <span class="badge ${item.status}">${item.status}</span>
                        </div>
                        <div class="history-msg">${escapeHTML(item.message)}</div>
                        ${errSnippet}
                        <div style="font-size: 0.7rem; color: var(--text-secondary); text-align: right; margin-top: 0.25rem;">
                            ${date}
                        </div>
                    </div>
                `;
            }).join("");
        } catch (err) {
            console.error("History fetch error:", err);
        }
    }

    function escapeHTML(str) {
        return str.replace(/[&<>'"]/g, 
            tag => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                "'": '&#39;',
                '"': '&quot;'
            }[tag] || tag)
        );
    }

    // Set dynamic API links
    const origin = window.location.origin;
    const endpointEl = document.getElementById("api-endpoint-url");
    if (endpointEl) {
        endpointEl.innerText = `${origin}/api/send`;
    }
    document.querySelectorAll(".api-placeholder").forEach(el => {
        el.innerText = origin;
    });

    // Code tabs toggle
    const codeTabBtns = document.querySelectorAll(".code-tab-btn");
    const codeContents = document.querySelectorAll(".code-content");
    codeTabBtns.forEach(btn => {
        btn.addEventListener("click", () => {
            codeTabBtns.forEach(b => {
                b.style.background = "rgba(255,255,255,0.05)";
                b.style.color = "var(--text-secondary)";
            });
            codeContents.forEach(c => c.style.display = "none");

            btn.style.background = "rgba(37, 211, 102, 0.08)";
            btn.style.color = "var(--color-accent)";
            
            const lang = btn.getAttribute("data-lang");
            document.getElementById(`code-${lang}`).style.display = "block";
        });
    });

    // AI Settings Functions
    async function loadAiSettings(deviceId) {
        if (!aiEnabledCheckbox) return;
        try {
            const res = await fetch(`/api/settings/${deviceId}`);
            if (res.ok) {
                const data = await res.json();
                aiEnabledCheckbox.checked = data.ai_enabled;
                aiGeminiKey.value = data.gemini_api_key || "";
                aiRolePreset.value = data.ai_role_preset || "human";
                aiInstructions.value = data.ai_instructions || "";
                
                toggleAiFields();
            }
        } catch (err) {
            console.error(`Failed to load AI settings for device ${deviceId}:`, err);
        }
    }

    function toggleAiFields() {
        if (!aiEnabledCheckbox || !aiConfigFields) return;
        if (aiEnabledCheckbox.checked) {
            aiConfigFields.style.display = "flex";
            aiGeminiKey.required = true;
        } else {
            aiConfigFields.style.display = "none";
            aiGeminiKey.required = false;
        }
    }

    if (aiEnabledCheckbox) {
        aiEnabledCheckbox.addEventListener("change", toggleAiFields);
    }

    if (aiSettingsForm) {
        aiSettingsForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            saveAiSettingsBtn.disabled = true;
            saveAiSettingsBtn.innerText = "Saving Settings...";
            
            const payload = {
                ai_enabled: aiEnabledCheckbox.checked,
                gemini_api_key: aiGeminiKey.value.trim(),
                ai_role_preset: aiRolePreset.value,
                ai_instructions: aiInstructions.value.trim()
            };
            
            try {
                const res = await fetch(`/api/settings/${activeDevice}`, {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    alert(`AI settings for Device ${activeDevice} saved successfully!`);
                } else {
                    alert("Failed to save AI settings: " + (data.error || "Unknown error"));
                }
            } catch (err) {
                console.error("Save AI settings error:", err);
                alert("Connection error saving AI settings.");
            } finally {
                saveAiSettingsBtn.disabled = false;
                saveAiSettingsBtn.innerText = "Save AI Settings";
            }
        });
    }

    // Polling schedules
    checkStatus();
    fetchStats();
    fetchHistory();
    loadAiSettings(activeDevice);

    pollInterval = setInterval(checkStatus, 3000);
    setInterval(fetchStats, 5000);
    setInterval(fetchHistory, 5000);
});
