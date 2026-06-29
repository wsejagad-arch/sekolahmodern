import os
import time
import random
import sqlite3
import threading
import logging
from typing import Optional
from fastapi import FastAPI, Request, HTTPException, Form, Response, Cookie, Depends
from fastapi.responses import HTMLResponse, JSONResponse, RedirectResponse
from fastapi.staticfiles import StaticFiles
import requests

# Setup Logging
logging.basicConfig(level=logging.INFO, format="%(asctime)s - %(levelname)s - %(message)s")
logger = logging.getLogger("WASender")

app = FastAPI(title="WA Notification Gateway")

# Paths and Config
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
DB_PATH = os.path.join(BASE_DIR, "notifications.db")
NODE_WORKER_URL = "http://127.0.0.1:9000"

ROLE_PRESETS = {
    "human": (
        "Anda adalah orang biasa (manusia) yang sedang mengobrol santai lewat WhatsApp. "
        "Gunakan gaya bahasa chat WhatsApp Indonesia yang kasual, sangat santai, dan ramah. "
        "JANGAN gunakan kata gaul Jakarta seperti 'lu'/'gue'/'gw'/'lo' (kecuali jika lawan bicara menggunakannya terlebih dahulu, "
        "Anda boleh menyesuaikan secara natural). Gunakan panggilan 'saya' atau 'sy' untuk diri sendiri, dan 'Kak' atau 'Anda' untuk lawan bicara. "
        "Anda bisa memahami bahasa Jawa (seperti dialek kromo, ngoko, 'piye', 'sugeng enjang', dll) atau dialek daerah lainnya dengan sangat baik, "
        "dan Anda boleh membalasnya dengan bahasa Jawa/daerah secara natural jika lawan bicara menggunakan bahasa tersebut. "
        "Jawablah dengan singkat, padat, langsung ke inti obrolan, dan komunikatif layaknya manusia asli yang sedang mengetik di HP. "
        "JANGAN PERNAH menyebutkan bahwa Anda adalah AI, bot, robot, program, atau asisten virtual. Bertindaklah 100% sebagai manusia sesungguhnya."
    ),
    "admin_olshop": (
        "Anda adalah admin online shop yang ramah, sopan, dan santai. Gunakan gaya bahasa chat WhatsApp Indonesia yang santai "
        "dan ramah, seperti menggunakan panggilan 'Kak', 'Sis', atau 'Gan'. Jawab dengan singkat, padat, dan langsung ke tujuan. "
        "Jangan gunakan format yang terlalu kaku or formal. Gunakan singkatan chat yang wajar (seperti 'yg', 'kalo', 'aja', 'dgn') "
        "agar terkesan natural seperti manusia asli yang sedang mengetik di HP. Bantu pembeli menanyakan produk, stok, atau ongkir."
    ),
    "admin_sekolah": (
        "Anda adalah admin staf administrasi sekolah yang ramah, sopan, dan informatif. Gunakan gaya bahasa Indonesia yang sopan "
        "namun tetap bersahabat. Sapa orang tua murid atau siswa dengan panggilan 'Bapak', 'Ibu', atau 'Kakak'. Jawab dengan jelas, "
        "singkat, dan bantu memberikan informasi mengenai pendaftaran, biaya sekolah, jadwal, atau kegiatan sekolah."
    ),
    "admin_perusahaan": (
        "Anda adalah admin customer service perusahaan yang profesional, sopan, dan solutif. Gunakan bahasa Indonesia formal yang "
        "baik, sopan, dan jelas. Bantu menjawab pertanyaan pelanggan seputar layanan perusahaan, jam operasional, atau keluhan "
        "dengan gaya yang ramah dan membantu."
    ),
    "custom": ""
}

BASE_HUMAN_INSTRUCTIONS = (
    "ATURAN UTAMA CHAT WHATSAPP (SANGAT PENTING):\n"
    "1. Anda harus membalas pesan secara alami, natural, dan organik layaknya manusia asli yang sedang mengetik di WhatsApp. JANGAN PERNAH menyebutkan bahwa Anda adalah AI, bot, robot, LLM, atau program komputer. Bertindaklah 100% sebagai manusia biasa yang santun dan ramah.\n"
    "2. Jangan gunakan format tanggapan bergaya robot (seperti tanda bintang berlebihan, bullet points, daftar panjang, penomoran kaku, "
    "atau bahasa pembuka/penutup yang terlalu template seperti 'Ada yang bisa saya bantu hari ini?').\n"
    "3. Sapaan harus dibalas secara natural dan timbal balik:\n"
    "   - Jika menyapa 'assalamualaikum', balas wajib dengan 'waalaikumsalam' atau 'waalaikumsalam wr wb' secara ramah.\n"
    "   - Jika menyapa 'selamat pagi/siang/sore/malam', balas dengan sapaan serupa yang ramah, seperti 'Halo, selamat siang juga Kak!' atau sejenisnya.\n"
    "   - Jika hanya menyapa 'p', 'ping', 'halo', 'hi', balas dengan sapaan kasual seperti 'Halo Kak, ada yang bisa dibantu?' atau 'Iya halo, silakan Kak.'\n"
    "4. Bersikaplah lebih komunikatif: ajukan pertanyaan singkat kembali jika relevan untuk menjaga percakapan tetap berjalan aktif dan membantu.\n"
    "5. Tulis jawaban Anda singkat dan padat (maksimal 2-3 kalimat per pesan) agar tidak seperti penjelasan panjang lebar khas robot.\n"
    "6. Jika tidak mengetahui jawabannya, katakan dengan jujur secara ramah dan tawarkan bantuan lain, atau katakan bahwa Anda akan mengeceknya terlebih dahulu."
)

# Initialize SQLite Database
def init_db():
    conn = sqlite3.connect(DB_PATH)
    cursor = conn.cursor()
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS queue (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            phone TEXT NOT NULL,
            message TEXT NOT NULL,
            status TEXT NOT NULL, -- pending, sending, sent, failed
            error TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            sent_at TIMESTAMP
        )
    """)
    
    # Create device_settings table
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS device_settings (
            device_id TEXT PRIMARY KEY,
            ai_enabled INTEGER DEFAULT 0,
            gemini_api_key TEXT,
            ai_role_preset TEXT,
            ai_instructions TEXT
        )
    """)
    
    # Auto-migration: Check if device_id column exists
    cursor.execute("PRAGMA table_info(queue)")
    columns = [col[1] for col in cursor.fetchall()]
    if "device_id" not in columns:
        cursor.execute("ALTER TABLE queue ADD COLUMN device_id TEXT")
        logger.info("Migrated SQLite database: added device_id column.")
        
    conn.commit()
    conn.close()

init_db()

# Mount Static Directories (creating them if they don't exist)
os.makedirs(os.path.join(BASE_DIR, "static"), exist_ok=True)
os.makedirs(os.path.join(BASE_DIR, "static", "css"), exist_ok=True)
os.makedirs(os.path.join(BASE_DIR, "static", "js"), exist_ok=True)
os.makedirs(os.path.join(BASE_DIR, "templates"), exist_ok=True)

app.mount("/static", StaticFiles(directory=os.path.join(BASE_DIR, "static")), name="static")

# Queue Processing Worker
def queue_worker_loop():
    logger.info("Anti-Spam Queue Worker thread started.")
    while True:
        try:
            conn = sqlite3.connect(DB_PATH)
            cursor = conn.cursor()
            
            # Fetch one pending message
            cursor.execute(
                "SELECT id, phone, message, device_id FROM queue WHERE status = 'pending' ORDER BY id ASC LIMIT 1"
            )
            item = cursor.fetchone()
            
            if item:
                msg_id, phone, message, msg_device_id = item
                
                # Mark as sending
                cursor.execute("UPDATE queue SET status = 'sending' WHERE id = ?", (msg_id,))
                conn.commit()
                
                logger.info(f"Processing message ID {msg_id} to {phone}...")
                
                # Check connected devices from Node worker to load-balance/auto-route
                connected_devices = []
                try:
                    status_res = requests.get(f"{NODE_WORKER_URL}/status", timeout=5)
                    if status_res.status_code == 200:
                        status_data = status_res.json()
                        devices_info = status_data.get("devices", {})
                        connected_devices = [
                            dev_id for dev_id, dev_data in devices_info.items() 
                            if dev_data.get("state") == "connected"
                        ]
                except Exception as e:
                    logger.error(f"Failed to query Node worker status: {e}")
                
                # Choose device_id to send
                target_device_id = msg_device_id
                if not target_device_id or str(target_device_id) not in connected_devices:
                    if connected_devices:
                        target_device_id = connected_devices[0]
                    else:
                        target_device_id = None
                        
                if not target_device_id:
                    # No connected devices available
                    cursor.execute(
                        "UPDATE queue SET status = 'failed', error = 'No connected WhatsApp devices available' WHERE id = ?",
                        (msg_id,)
                    )
                    logger.error(f"✗ Message ID {msg_id} failed: No connected WhatsApp devices available")
                else:
                    # Try sending via Node.js Baileys Worker
                    try:
                        res = requests.post(
                            f"{NODE_WORKER_URL}/send",
                            json={"number": phone, "message": message, "device_id": target_device_id},
                            timeout=15
                        )
                        
                        if res.status_code == 200:
                            # Success
                            res_data = res.json()
                            used_device = res_data.get("device_id", target_device_id)
                            cursor.execute(
                                "UPDATE queue SET status = 'sent', sent_at = CURRENT_TIMESTAMP, device_id = ? WHERE id = ?",
                                (str(used_device), msg_id)
                            )
                            logger.info(f"✓ Message ID {msg_id} sent successfully via Device {used_device}.")
                        else:
                            err_msg = res.json().get("error", "Unknown Node worker error")
                            cursor.execute(
                                "UPDATE queue SET status = 'failed', error = ? WHERE id = ?",
                                (err_msg, msg_id)
                            )
                            logger.error(f"✗ Message ID {msg_id} failed: {err_msg}")
                            
                    except requests.exceptions.RequestException as e:
                        cursor.execute(
                            "UPDATE queue SET status = 'failed', error = ? WHERE id = ?",
                            (str(e), msg_id)
                        )
                        logger.error(f"✗ Node worker communication failure: {e}")
                
                conn.commit()
                conn.close()
                
                # Anti-Spam pacing delay (e.g. 3 to 7 seconds)
                delay_sec = random.uniform(3.0, 7.0)
                logger.info(f"Waiting for {delay_sec:.2f} seconds before next message...")
                time.sleep(delay_sec)
                
            else:
                conn.close()
                time.sleep(1) # Wait 1 second before checking for new messages
                
        except Exception as ex:
            logger.error(f"Queue worker exception: {ex}")
            time.sleep(2)

# Start background queue thread
worker_thread = threading.Thread(target=queue_worker_loop, daemon=True)
worker_thread.start()


# HTTP Router API / Views

SESSION_COOKIE_NAME = "session"
SESSION_SECRET = "wahyu_authenticated_session"

# Dependency to check API authentication
def verify_auth(session: Optional[str] = Cookie(None)):
    if session != SESSION_SECRET:
        raise HTTPException(status_code=401, detail="Unauthorized")
    return "wahyu"

@app.get("/login", response_class=HTMLResponse)
async def login_page(session: Optional[str] = Cookie(None)):
    if session == SESSION_SECRET:
        return RedirectResponse(url="/", status_code=303)
    login_html_path = os.path.join(BASE_DIR, "templates", "login.html")
    if os.path.exists(login_html_path):
        with open(login_html_path, "r", encoding="utf-8") as f:
            return HTMLResponse(content=f.read())
    return HTMLResponse("<h3>Login Template Not Found</h3>")

@app.post("/login")
async def process_login(response: Response, username: str = Form(...), password: str = Form(...)):
    if username == "wahyu" and password == "wahyu123":
        response.set_cookie(
            key=SESSION_COOKIE_NAME,
            value=SESSION_SECRET,
            httponly=True,
            max_age=86400 * 7, # 7 days
            samesite="lax"
        )
        return {"success": True}
    raise HTTPException(status_code=401, detail="Invalid username or password")

@app.post("/logout")
async def process_logout():
    response = RedirectResponse(url="/login", status_code=303)
    response.delete_cookie(key=SESSION_COOKIE_NAME)
    return response

@app.get("/", response_class=HTMLResponse)
async def index_page(session: Optional[str] = Cookie(None)):
    if session != SESSION_SECRET:
        return RedirectResponse(url="/login", status_code=303)
    index_html_path = os.path.join(BASE_DIR, "templates", "index.html")
    if os.path.exists(index_html_path):
        with open(index_html_path, "r", encoding="utf-8") as f:
            return HTMLResponse(content=f.read())
    return HTMLResponse("<h3>Error: templates/index.html not found!</h3>")

# Proxy to Node worker for status check
@app.get("/api/status", dependencies=[Depends(verify_auth)])
async def get_whatsapp_status(device_id: Optional[str] = None):
    try:
        url = f"{NODE_WORKER_URL}/status"
        if device_id:
            url += f"?device_id={device_id}"
        res = requests.get(url, timeout=5)
        return res.json()
    except requests.exceptions.RequestException:
        return {"state": "disconnected", "error": "WhatsApp Engine offline"}

# Proxy to Node worker for pairing code
@app.post("/api/pair", dependencies=[Depends(verify_auth)])
async def pair_whatsapp(data: dict):
    number = data.get("number")
    device_id = data.get("device_id", "1")
    if not number:
        raise HTTPException(status_code=400, detail="Number is required")
    try:
        res = requests.post(f"{NODE_WORKER_URL}/pair", json={"number": number, "device_id": device_id}, timeout=10)
        return JSONResponse(status_code=res.status_code, content=res.json())
    except requests.exceptions.RequestException as e:
        raise HTTPException(status_code=500, detail=f"Engine communication error: {e}")

# Proxy to Node worker for logout (Disconnect WhatsApp connection)
@app.post("/api/logout", dependencies=[Depends(verify_auth)])
async def logout_whatsapp(data: dict = {}):
    device_id = data.get("device_id", "1")
    try:
        res = requests.post(f"{NODE_WORKER_URL}/logout", json={"device_id": device_id}, timeout=5)
        return res.json()
    except requests.exceptions.RequestException as e:
        raise HTTPException(status_code=500, detail=f"Engine communication error: {e}")

# Receive new notifications (Pushes into database queue) - PUBLIC ENDPOINT FOR PROGRAMMATIC NOTIFICATIONS
@app.post("/api/send")
async def push_to_send_queue(data: dict):
    number = data.get("number")
    message = data.get("message")
    device_id = data.get("device_id") # optional
    
    if not number or not message:
        return JSONResponse(
            status_code=400,
            content={"success": False, "error": "number and message are required"}
        )
        
    try:
        conn = sqlite3.connect(DB_PATH)
        cursor = conn.cursor()
        cursor.execute(
            "INSERT INTO queue (phone, message, status, device_id) VALUES (?, ?, 'pending', ?)",
            (number, message, device_id)
        )
        msg_id = cursor.lastrowid
        conn.commit()
        conn.close()
        
        return {
            "success": True,
            "status": "queued",
            "message_id": msg_id,
            "info": f"Notification accepted and queued for device routing."
        }
    except Exception as ex:
        return JSONResponse(
            status_code=500,
            content={"success": False, "error": f"Failed to queue message: {ex}"}
        )

# Get queue status & history
@app.get("/api/history", dependencies=[Depends(verify_auth)])
async def get_history():
    try:
        conn = sqlite3.connect(DB_PATH)
        conn.row_factory = sqlite3.Row
        cursor = conn.cursor()
        cursor.execute(
            "SELECT id, phone, message, status, error, created_at, sent_at, device_id FROM queue ORDER BY id DESC LIMIT 50"
        )
        rows = cursor.fetchall()
        
        history = []
        for r in rows:
            history.append({
                "id": r["id"],
                "phone": r["phone"],
                "message": r["message"],
                "status": r["status"],
                "error": r["error"],
                "created_at": r["created_at"],
                "sent_at": r["sent_at"],
                "device_id": r["device_id"]
            })
        conn.close()
        return history
    except Exception as ex:
        raise HTTPException(status_code=500, detail=str(ex))

# Load device-specific settings
@app.get("/api/settings/{device_id}", dependencies=[Depends(verify_auth)])
async def get_device_settings(device_id: str):
    try:
        conn = sqlite3.connect(DB_PATH)
        conn.row_factory = sqlite3.Row
        cursor = conn.cursor()
        cursor.execute(
            "SELECT ai_enabled, gemini_api_key, ai_role_preset, ai_instructions FROM device_settings WHERE device_id = ?",
            (device_id,)
        )
        row = cursor.fetchone()
        conn.close()
        
        if row:
            return {
                "ai_enabled": bool(row["ai_enabled"]),
                "gemini_api_key": row["gemini_api_key"] or "",
                "ai_role_preset": row["ai_role_preset"] or "custom",
                "ai_instructions": row["ai_instructions"] or ""
            }
        return {
            "ai_enabled": False,
            "gemini_api_key": "",
            "ai_role_preset": "custom",
            "ai_instructions": ""
        }
    except Exception as ex:
        raise HTTPException(status_code=500, detail=str(ex))

# Save device-specific settings
@app.post("/api/settings/{device_id}", dependencies=[Depends(verify_auth)])
async def save_device_settings(device_id: str, data: dict):
    ai_enabled = 1 if data.get("ai_enabled") else 0
    gemini_api_key = data.get("gemini_api_key", "").strip()
    ai_role_preset = data.get("ai_role_preset", "custom").strip()
    ai_instructions = data.get("ai_instructions", "").strip()
    
    try:
        conn = sqlite3.connect(DB_PATH)
        cursor = conn.cursor()
        cursor.execute(
            """
            INSERT INTO device_settings (device_id, ai_enabled, gemini_api_key, ai_role_preset, ai_instructions)
            VALUES (?, ?, ?, ?, ?)
            ON CONFLICT(device_id) DO UPDATE SET
                ai_enabled=excluded.ai_enabled,
                gemini_api_key=excluded.gemini_api_key,
                ai_role_preset=excluded.ai_role_preset,
                ai_instructions=excluded.ai_instructions
            """,
            (device_id, ai_enabled, gemini_api_key, ai_role_preset, ai_instructions)
        )
        conn.commit()
        conn.close()
        return {"success": True}
    except Exception as ex:
        raise HTTPException(status_code=500, detail=str(ex))

# Webhook to process incoming messages from WhatsApp
@app.post("/api/incoming")
async def process_incoming_message(data: dict):
    phone = data.get("phone")
    message = data.get("message")
    device_id = data.get("device_id")
    name = data.get("name", "")
    
    if not phone or not message or not device_id:
        return JSONResponse(status_code=400, content={"success": False, "error": "phone, message, and device_id are required"})
        
    # Strict privacy filter: Only trigger auto-replies for the test contact "SMA Sumber Puguh Cahyo 10" or JID "152965310640299"
    name_str = str(name).lower()
    phone_str = str(phone).lower()
    is_target_contact = ("sma sumber puguh cahyo 10" in name_str) or ("152965310640299" in phone_str)
    
    if not is_target_contact:
        logger.info(f"🛡️ [Safe Mode] Ignored message from +{phone} (Name: '{name}') to protect user privacy.")
        return {"success": True, "info": "Ignored message: not the approved test contact."}
        
    try:
        # 1. Check if AI is enabled for this device
        conn = sqlite3.connect(DB_PATH)
        conn.row_factory = sqlite3.Row
        cursor = conn.cursor()
        cursor.execute(
            "SELECT ai_enabled, gemini_api_key, ai_role_preset, ai_instructions FROM device_settings WHERE device_id = ?",
            (str(device_id),)
        )
        row = cursor.fetchone()
        conn.close()
        
        if not row or not row["ai_enabled"]:
            # AI not enabled for this device, ignore or return early
            return {"success": True, "info": f"AI Auto-Reply is disabled for Device {device_id}"}
            
        api_key = row["gemini_api_key"]
        role_preset = row["ai_role_preset"] or "custom"
        custom_instructions = row["ai_instructions"] or ""
        
        if not api_key:
            logger.error(f"AI Auto-Reply enabled for Device {device_id} but Gemini API Key is missing.")
            return {"success": False, "error": "Gemini API Key is not configured."}
            
        # Time of day context
        local_hour = time.localtime().tm_hour
        if 4 <= local_hour < 11:
            time_of_day = "pagi"
        elif 11 <= local_hour < 15:
            time_of_day = "siang"
        elif 15 <= local_hour < 18:
            time_of_day = "sore"
        else:
            time_of_day = "malam"
            
        time_context = f"Konteks Waktu: Waktu saat ini adalah {time_of_day}. Jika lawan bicara menyapa Anda, balaslah dengan sapaan waktu yang sesuai (seperti 'Halo, selamat pagi Kak', 'Selamat sore Kak', dll)."
            
        # Combine role preset + custom instructions + time context + base rules
        system_parts = []
        preset_text = ROLE_PRESETS.get(role_preset, "")
        if preset_text:
            system_parts.append(preset_text)
        if custom_instructions:
            system_parts.append(custom_instructions)
        system_parts.append(time_context)
        system_parts.append(BASE_HUMAN_INSTRUCTIONS)
        
        combined_system_instructions = "\n\n".join(system_parts)
            
        # 2. Call Gemini API to generate response
        url = f"https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={api_key}"
        headers = {"Content-Type": "application/json"}
        
        payload = {
            "contents": [
                {"role": "user", "parts": [{"text": message}]}
            ]
        }
        if combined_system_instructions:
            payload["systemInstruction"] = {
                "parts": [{"text": combined_system_instructions}]
            }
            
        res = requests.post(url, headers=headers, json=payload, timeout=20)
        if res.status_code != 200:
            logger.error(f"Gemini API returned error {res.status_code}: {res.text}")
            return {"success": False, "error": "AI response generation failed."}
            
        res_data = res.json()
        candidates = res_data.get("candidates", [])
        if not candidates:
            logger.error("No candidates returned from Gemini API.")
            return {"success": False, "error": "AI returned empty response."}
            
        reply_text = candidates[0].get("content", {}).get("parts", [{}])[0].get("text", "").strip()
        
        if not reply_text:
            return {"success": True, "info": "AI generated empty reply, skipped."}
            
        # 3. Queue the generated reply
        conn = sqlite3.connect(DB_PATH)
        cursor = conn.cursor()
        cursor.execute(
            "INSERT INTO queue (phone, message, status, device_id) VALUES (?, ?, 'pending', ?)",
            (phone, reply_text, str(device_id))
        )
        msg_id = cursor.lastrowid
        conn.commit()
        conn.close()
        
        logger.info(f"AI Auto-Reply generated and queued for {phone} via Device {device_id} (Message ID: {msg_id})")
        return {"success": True, "status": "queued", "message_id": msg_id}
        
    except Exception as ex:
        logger.error(f"Incoming message handling error: {ex}")
        return JSONResponse(status_code=500, content={"success": False, "error": str(ex)})

# Get summary statistics
@app.get("/api/stats", dependencies=[Depends(verify_auth)])
async def get_stats():
    try:
        conn = sqlite3.connect(DB_PATH)
        cursor = conn.cursor()
        
        cursor.execute("SELECT COUNT(*) FROM queue WHERE status = 'pending'")
        pending = cursor.fetchone()[0]
        
        cursor.execute("SELECT COUNT(*) FROM queue WHERE status = 'sent'")
        sent = cursor.fetchone()[0]
        
        cursor.execute("SELECT COUNT(*) FROM queue WHERE status = 'failed'")
        failed = cursor.fetchone()[0]
        
        conn.close()
        return {
            "pending": pending,
            "sent": sent,
            "failed": failed
        }
    except Exception as ex:
        raise HTTPException(status_code=500, detail=str(ex))

if __name__ == "__main__":
    import uvicorn
    uvicorn.run("main:app", host="0.0.0.0", port=8000, reload=False)
