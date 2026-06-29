const {
    default: makeWASocket,
    useMultiFileAuthState,
    DisconnectReason,
    delay,
    jidNormalizedUser,
    fetchLatestBaileysVersion,
    Browsers
} = require("@whiskeysockets/baileys");
const express = require("express");
const cors = require("cors");
const pino = require("pino");
const QRCode = require("qrcode");
const fs = require("fs");
const path = require("path");

const app = express();
app.use(express.json());
app.use(cors());

// Prevent application crash from unhandled promise rejections or uncaught exceptions
process.on("unhandledRejection", (reason, promise) => {
    console.error("⚠️ Unhandled Rejection at:", promise, "reason:", reason);
});

process.on("uncaughtException", (err, origin) => {
    console.error(`⚠️ Uncaught Exception: ${err.message}\nOrigin: ${origin}\nStack: ${err.stack}`);
});

const PORT = process.env.PORT || 9000;
const SESSION_DIR = path.join(__dirname, "session");

// Ensure main session folder exists
if (!fs.existsSync(SESSION_DIR)) {
    fs.mkdirSync(SESSION_DIR, { recursive: true });
}

const DEVICE_COUNT = 1;
const devices = {};

// Initialize device objects
for (let i = 1; i <= DEVICE_COUNT; i++) {
    devices[i] = {
        sock: null,
        connectionState: "disconnected", // disconnected, connecting, qr, connected
        latestQR: null,
        latestPairingCode: null,
        pairingNumber: null,
        user: null
    };
}

// Initialize logger
const logger = pino({ level: "silent" });

async function connectToWhatsApp(deviceId) {
    const deviceSessionDir = path.join(SESSION_DIR, `device_${deviceId}`);
    const { state, saveCreds } = await useMultiFileAuthState(deviceSessionDir);

    let version = [2, 3000, 1015901307]; // Fallback stable version
    try {
        const { version: latestVersion, isLatest } = await fetchLatestBaileysVersion();
        version = latestVersion;
        if (deviceId === 1) {
            console.log(`🌐 Using WhatsApp Web v${version.join(".")}, isLatest: ${isLatest}`);
        }
    } catch (err) {
        if (deviceId === 1) {
            console.warn("Failed to fetch latest WhatsApp version, using fallback stable version:", err.message);
        }
    }

    const sock = makeWASocket({
        auth: state,
        logger: logger,
        version: version,
        browser: Browsers.ubuntu('Chrome'),
        markOnlineOnConnect: true
    });

    devices[deviceId].sock = sock;

    // Save credentials whenever updated
    sock.ev.on("creds.update", saveCreds);

    // Listen for incoming messages to forward to python webhook
    sock.ev.on("messages.upsert", async (m) => {
        if (m.type === "notify") {
            for (const msg of m.messages) {
                // Ignore messages sent by ourselves
                if (msg.key.fromMe) continue;

                // Only process private chats (ignore group chats, newsletters, broadcasts, etc.)
                const remoteJid = msg.key.remoteJid;
                if (!remoteJid || (!remoteJid.endsWith("@s.whatsapp.net") && !remoteJid.endsWith("@lid"))) continue;

                // Extract message body
                const messageText = msg.message?.conversation || 
                                    msg.message?.extendedTextMessage?.text || 
                                    msg.message?.imageMessage?.caption || 
                                    msg.message?.videoMessage?.caption || 
                                    "";

                if (!messageText.trim()) continue;

                const senderPhone = remoteJid.replace("@s.whatsapp.net", "");
                const senderName = msg.pushName || "";

                // Post to Python FastAPI /api/incoming
                try {
                    console.log(`📩 [Device ${deviceId}] Incoming message from +${senderPhone} (${senderName}): "${messageText}"`);
                    const response = await fetch("http://127.0.0.1:8000/api/incoming", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({
                            phone: senderPhone,
                            message: messageText,
                            device_id: deviceId.toString(),
                            name: senderName
                        })
                    });
                    if (!response.ok) {
                        console.error(`[Device ${deviceId}] Failed to forward message: HTTP ${response.status} - ${response.statusText}`);
                    }
                } catch (err) {
                    console.error(`[Device ${deviceId}] Connection error forwarding message to Python backend:`, err.message);
                }
            }
        }
    });

    // Monitor connection events
    sock.ev.on("connection.update", async (update) => {
        const { connection, lastDisconnect, qr } = update;

        if (qr) {
            devices[deviceId].connectionState = "qr";
            try {
                devices[deviceId].latestQR = await QRCode.toDataURL(qr);
            } catch (err) {
                console.error(`[Device ${deviceId}] Failed to generate QR data URL:`, err);
            }
        }

        if (connection === "connecting") {
            devices[deviceId].connectionState = "connecting";
            devices[deviceId].latestQR = null;
        }

        if (connection === "open") {
            devices[deviceId].connectionState = "connected";
            devices[deviceId].latestQR = null;
            devices[deviceId].latestPairingCode = null;
            devices[deviceId].pairingNumber = null;
            
            const phone = jidNormalizedUser(sock.user.id).replace("@s.whatsapp.net", "");
            devices[deviceId].user = phone;
            console.log(`⚡ [Device ${deviceId}] WhatsApp Connection successfully opened for +${phone}!`);
        }

        if (connection === "close") {
            const shouldReconnect = lastDisconnect?.error?.output?.statusCode !== DisconnectReason.loggedOut && !sock.isManualShutdown;
            devices[deviceId].connectionState = "disconnected";
            devices[deviceId].latestQR = null;
            devices[deviceId].latestPairingCode = null;
            devices[deviceId].user = null;

            console.log(`❌ [Device ${deviceId}] Connection closed. Reason:`, lastDisconnect?.error?.message || "Unknown");

            // Clean up the socket listeners so this instance won't trigger any more event callbacks
            try {
                sock.ev.removeAllListeners();
            } catch (e) {
                // ignore
            }

            if (shouldReconnect) {
                console.log(`🔄 [Device ${deviceId}] Reconnecting...`);
                setTimeout(() => connectToWhatsApp(deviceId), 3000);
            } else {
                console.log(`🚫 [Device ${deviceId}] Logged out. Deleting session directory and starting fresh...`);
                try {
                    fs.rmSync(deviceSessionDir, { recursive: true, force: true });
                } catch (e) {
                    console.error(`[Device ${deviceId}] Failed to clear session:`, e);
                }
                setTimeout(() => connectToWhatsApp(deviceId), 1000);
            }
        }
    });
}

// REST API Endpoints

// Get current state of all devices or a specific device
app.get("/status", (req, res) => {
    const requestedDeviceId = parseInt(req.query.device_id);
    
    // Auto-connect device on demand when status is queried and no socket exists
    if (requestedDeviceId && devices[requestedDeviceId] && !devices[requestedDeviceId].sock) {
        console.log(`[Device ${requestedDeviceId}] Status queried and socket not initialized. Connecting on-demand...`);
        connectToWhatsApp(requestedDeviceId).catch(err => {
            console.error(`Failed to connect Device ${requestedDeviceId} on-demand:`, err);
        });
    }

    const summary = {};
    for (let i = 1; i <= DEVICE_COUNT; i++) {
        summary[i] = {
            state: devices[i].connectionState,
            user: devices[i].user
        };
    }

    if (requestedDeviceId && devices[requestedDeviceId]) {
        return res.json({
            devices: summary,
            current: {
                device_id: requestedDeviceId,
                state: devices[requestedDeviceId].connectionState,
                qr: devices[requestedDeviceId].latestQR,
                pairingCode: devices[requestedDeviceId].latestPairingCode,
                user: devices[requestedDeviceId].user
            }
        });
    }

    res.json({
        devices: summary
    });
});

// Request pairing code (link with phone number)
app.post("/pair", async (req, res) => {
    let { number, device_id } = req.body;
    const deviceId = parseInt(device_id);

    if (!number) {
        return res.status(400).json({ error: "Phone number is required" });
    }
    if (!deviceId || !devices[deviceId]) {
        return res.status(400).json({ error: "Valid device_id (1-10) is required" });
    }

    // Sanitize number: remove +, spaces, dashes
    number = number.replace(/[^0-9]/g, "");
    if (number.startsWith("0")) {
        number = "62" + number.substring(1);
    }

    if (devices[deviceId].connectionState === "connected") {
        return res.status(400).json({ error: `Device ${deviceId} is already connected` });
    }

    try {
        console.log(`🔑 [Device ${deviceId}] Generating pairing code for ${number}...`);
        devices[deviceId].pairingNumber = number;
        
        // Always clear existing session files to start 100% fresh for new pairing request
        const deviceSessionDir = path.join(SESSION_DIR, `device_${deviceId}`);
        console.log(`[Device ${deviceId}] Clearing old session files for fresh pairing...`);
        try {
            if (fs.existsSync(deviceSessionDir)) {
                fs.rmSync(deviceSessionDir, { recursive: true, force: true });
            }
        } catch(e) {
            console.error(`Failed to clear session dir:`, e);
        }

        // Initialize fresh socket connection and wait
        console.log(`[Device ${deviceId}] Initializing fresh socket connection...`);
        if (devices[deviceId].sock) {
            try {
                devices[deviceId].sock.isManualShutdown = true;
                devices[deviceId].sock.end();
            } catch(e) {}
        }
        await connectToWhatsApp(deviceId);
        await delay(5000); // Wait 5 seconds for socket initialization and handshake
        
        const sock = devices[deviceId].sock;
        if (!sock) {
            return res.status(500).json({ error: `Socket for device ${deviceId} not initialized. Please try again.` });
        }
        
        // Request pairing code from baileys
        const code = await sock.requestPairingCode(number);
        devices[deviceId].latestPairingCode = code;
        devices[deviceId].connectionState = "qr"; // We use QR state/login phase
        
        res.json({ code: code });
    } catch (err) {
        console.error(`[Device ${deviceId}] Failed to generate pairing code, retrying with fresh socket:`, err);
        
        // Retry connection logic: reset state and force recreate socket
        try {
            devices[deviceId].connectionState = "disconnected";
            if (devices[deviceId].sock) {
                try {
                    devices[deviceId].sock.isManualShutdown = true;
                    devices[deviceId].sock.end();
                } catch(e) {}
            }
            
            await connectToWhatsApp(deviceId);
            await delay(5000); // Wait 5 seconds for connection
            
            const freshSock = devices[deviceId].sock;
            if (!freshSock) {
                throw new Error("Fresh socket could not be initialized");
            }
            
            const code = await freshSock.requestPairingCode(number);
            devices[deviceId].latestPairingCode = code;
            devices[deviceId].connectionState = "qr";
            
            res.json({ code: code });
        } catch (retryErr) {
            console.error(`[Device ${deviceId}] Pairing retry failed:`, retryErr);
            res.status(500).json({ error: "Failed to generate pairing code: " + retryErr.message });
        }
    }
});

// Send message
app.post("/send", async (req, res) => {
    let { number, message, device_id } = req.body;
    
    if (!number || !message) {
        return res.status(400).json({ error: "Number and message are required" });
    }

    // Determine which device to use
    let deviceId = parseInt(device_id);
    if (!deviceId || !devices[deviceId]) {
        // Auto-select: find first connected device
        const connectedDeviceIds = Object.keys(devices).filter(
            id => devices[id].connectionState === "connected" && devices[id].sock
        );
        if (connectedDeviceIds.length === 0) {
            return res.status(400).json({ error: "No WhatsApp device is connected" });
        }
        // Use the first one or random
        deviceId = parseInt(connectedDeviceIds[0]);
    }

    const device = devices[deviceId];
    if (device.connectionState !== "connected" || !device.sock) {
        return res.status(400).json({ error: `WhatsApp Device ${deviceId} is not connected` });
    }

    // Format number to JID: e.g. "628xxx@s.whatsapp.net"
    let jid;
    let shouldQueryOnWa = false;
    let cleanNumber = "";
    
    if (number.includes("@")) {
        jid = number;
    } else {
        cleanNumber = number.replace(/[^0-9]/g, "");
        if (cleanNumber.startsWith("0")) {
            cleanNumber = "62" + cleanNumber.substring(1);
        }
        jid = `${cleanNumber}@s.whatsapp.net`;
        shouldQueryOnWa = true;
    }

    try {
        if (shouldQueryOnWa && cleanNumber) {
            // Resolve JID and force prekeys negotiation to prevent "Waiting for this message" E2E error
            try {
                console.log(`[Device ${deviceId}] Querying number status on WhatsApp: ${cleanNumber}`);
                const onWa = await device.sock.onWhatsApp(cleanNumber);
                if (onWa && onWa.length > 0 && onWa[0].exists) {
                    jid = onWa[0].jid;
                    console.log(`[Device ${deviceId}] Successfully resolved JID: ${jid}`);
                } else {
                    console.warn(`[Device ${deviceId}] Number ${cleanNumber} not registered on WhatsApp. Sending to fallback JID.`);
                }
            } catch (e) {
                console.error(`[Device ${deviceId}] Failed to resolve JID via onWhatsApp, using fallback:`, e.message);
            }
        }

        // Human-like Presence Simulation:
        // 1. Send Typing state (composing)
        await device.sock.sendPresenceUpdate("composing", jid);
        
        // 2. Wait 2 seconds (simulate typing)
        await delay(2000);
        
        // 3. Mark presence as paused
        await device.sock.sendPresenceUpdate("paused", jid);

        // 4. Send the message
        const result = await device.sock.sendMessage(jid, { text: message });

        res.json({ success: true, messageId: result.key.id, device_id: deviceId });
    } catch (err) {
        console.error(`[Device ${deviceId}] Failed to send message:`, err);
        res.status(500).json({ error: "Failed to send message: " + err.message });
    }
});

// Logout
app.post("/logout", async (req, res) => {
    const { device_id } = req.body;
    const deviceId = parseInt(device_id);

    if (!deviceId || !devices[deviceId]) {
        return res.status(400).json({ error: "Valid device_id (1-10) is required" });
    }

    const device = devices[deviceId];
    const deviceSessionDir = path.join(SESSION_DIR, `device_${deviceId}`);

    try {
        if (device.sock) {
            device.sock.isManualShutdown = true;
            try {
                await device.sock.logout();
            } catch (e) {
                console.warn(`[Device ${deviceId}] sock.logout() error:`, e.message);
            }
        }
        device.connectionState = "disconnected";
        device.latestQR = null;
        device.latestPairingCode = null;
        device.user = null;
        
        // Delete session dir to clear cache
        if (fs.existsSync(deviceSessionDir)) {
            fs.rmSync(deviceSessionDir, { recursive: true, force: true });
        }
        
        res.json({ success: true, message: `Logged out device ${deviceId} successfully` });
    } catch (err) {
        console.error(`Logout failed for device ${deviceId}:`, err);
        res.status(500).json({ error: `Logout failed: ` + err.message });
    }
});

// Helper to check if session files exist for a device
function hasSavedSession(deviceId) {
    const deviceSessionDir = path.join(SESSION_DIR, `device_${deviceId}`);
    return fs.existsSync(path.join(deviceSessionDir, "creds.json"));
}

// Run local API server
app.listen(PORT, "127.0.0.1", () => {
    console.log(`🚀 Node.js WhatsApp Worker listening locally on http://127.0.0.1:${PORT}`);
    
    // Initialize connection only for devices that have a saved session to optimize resources
    for (let i = 1; i <= DEVICE_COUNT; i++) {
        if (hasSavedSession(i)) {
            console.log(`Initializing saved session for Device ${i}...`);
            connectToWhatsApp(i).catch(err => {
                console.error(`Failed to initialize Device ${i}:`, err);
            });
        } else {
            console.log(`Device ${i} has no saved session. Waiting for user interaction.`);
        }
    }
});
