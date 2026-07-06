#!/bin/bash

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

echo -e "${CYAN}====================================================${NC}"
echo -e "${CYAN}      WhatsApp Notification Gateway Setup Script     ${NC}"
echo -e "${CYAN}                 (aaPanel Optimized)                ${NC}"
echo -e "${CYAN}====================================================${NC}"

# Check if script is run in the project root
if [ ! -f "main.py" ] || [ ! -d "worker" ]; then
    echo -e "${RED}Error: Jalankan script ini di direktori root project! (tempat main.py berada)${NC}"
    exit 1
fi

# Detect OS / system
echo -e "${YELLOW}[1/4] Memeriksa kebutuhan system...${NC}"

# Check for Python
if command -v python3 &>/dev/null; then
    PYTHON_CMD="python3"
elif command -v python &>/dev/null; then
    PYTHON_CMD="python"
else
    echo -e "${RED}Error: Python3 tidak ditemukan. Silakan install Python 3.9+ melalui aaPanel App Store atau App Manager.${NC}"
    exit 1
fi

PY_VERSION=$($PYTHON_CMD -c 'import sys; print(".".join(map(str, sys.version_info[:2])))')
echo -e "${GREEN}✓ Python ditemukan: v$PY_VERSION ($PYTHON_CMD)${NC}"

# Check for Node.js & NPM
if command -v node &>/dev/null; then
    NODE_VERSION=$(node -v)
    echo -e "${GREEN}✓ Node.js ditemukan: $NODE_VERSION${NC}"
else
    echo -e "${RED}Error: Node.js tidak ditemukan. Silakan install Node.js (v16+) melalui PM2 Manager di aaPanel App Store.${NC}"
    exit 1
fi

if command -v npm &>/dev/null; then
    NPM_VERSION=$(npm -v)
    echo -e "${GREEN}✓ NPM ditemukan: v$NPM_VERSION${NC}"
else
    echo -e "${RED}Error: NPM tidak ditemukan.${NC}"
    exit 1
fi

# 2. Setup Node.js worker
echo -e "\n${YELLOW}[2/4] Menginstal dependensi Node.js Worker...${NC}"
cd worker || exit
if [ -d "node_modules" ]; then
    echo -e "${YELLOW}Folder node_modules sudah ada, membersihkan terlebih dahulu...${NC}"
    rm -rf node_modules
fi

echo -e "Menjalankan npm install di folder worker..."
npm install --no-audit --no-fund
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Dependensi Node.js berhasil diinstal.${NC}"
else
    echo -e "${RED}✗ Gagal menginstal dependensi Node.js.${NC}"
    exit 1
fi
cd ..

# 3. Setup Python virtual environment
echo -e "\n${YELLOW}[3/4] Menyiapkan Python Virtual Environment (venv)...${NC}"
if [ -d "venv" ]; then
    echo -e "${YELLOW}Virtual environment 'venv' sudah ada, melewati pembuatan...${NC}"
else
    echo -e "Membuat virtual environment baru..."
    $PYTHON_CMD -m venv venv
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Virtual environment berhasil dibuat.${NC}"
    else
        echo -e "${RED}✗ Gagal membuat virtual environment.${NC}"
        exit 1
    fi
fi

echo -e "Mengaktifkan venv dan menginstal requirements..."
source venv/bin/activate
pip install --upgrade pip
pip install -r requirements.txt
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Dependensi Python berhasil diinstal.${NC}"
else
    echo -e "${RED}✗ Gagal menginstal dependensi Python.${NC}"
    deactivate
    exit 1
fi
deactivate

# 4. Final verification
echo -e "\n${YELLOW}[4/4] Verifikasi Port & Database...${NC}"

# Ensure template and static dirs exist
mkdir -p static/css static/js templates

# Initialize Database
echo -e "Menginisialisasi file database SQLite..."
source venv/bin/activate
python -c "import sqlite3; conn = sqlite3.connect('notifications.db'); conn.close(); print('Database notifications.db siap.')"
deactivate

# Check ports
PORT_8000_IN_USE=$(lsof -i :8000 | wc -l)
PORT_9000_IN_USE=$(lsof -i :9000 | wc -l)

if [ "$PORT_8000_IN_USE" -gt 0 ]; then
    echo -e "${RED}Peringatan: Port 8000 sedang digunakan oleh proses lain.${NC}"
fi
if [ "$PORT_9000_IN_USE" -gt 0 ]; then
    echo -e "${RED}Peringatan: Port 9000 sedang digunakan oleh proses lain.${NC}"
fi

echo -e "\n${GREEN}====================================================${NC}"
echo -e "${GREEN}      SELESAI! Setup Dasar Berhasil Dilakukan.     ${NC}"
echo -e "${GREEN}====================================================${NC}"
echo -e "\nSilakan ikuti langkah berikut untuk menjalankan di aaPanel:"
echo -e "1. ${CYAN}Jalankan Node.js Worker (Port 9000):${NC}"
echo -e "   - Buka ${YELLOW}PM2 Manager${NC} di aaPanel -> Project List -> Add Project."
echo -e "   - Startup File: ${YELLOW}/www/wwwroot/wa-sender/worker/index.js${NC}"
echo -e "   - Run Directory: ${YELLOW}/www/wwwroot/wa-sender/worker${NC}"
echo -e "   - Name: ${YELLOW}wa-worker${NC}"
echo -e "   - Port: ${YELLOW}9000${NC}"
echo -e ""
echo -e "2. ${CYAN}Jalankan Python FastAPI (Port 8000):${NC}"
echo -e "   - Buka ${YELLOW}Python Manager${NC} di aaPanel -> Add Project."
echo -e "   - Path: ${YELLOW}/www/wwwroot/wa-sender${NC}"
echo -e "   - Startup File: ${YELLOW}/www/wwwroot/wa-sender/main.py${NC}"
echo -e "   - Version: Pilih python v3.9+${NC}"
echo -e "   - Framework: ${YELLOW}FastAPI${NC} atau ${YELLOW}Uvicorn${NC}"
echo -e "   - Port: ${YELLOW}8000${NC}"
echo -e ""
echo -e "3. ${CYAN}Reverse Proxy (Opsional):${NC}"
echo -e "   - Buat situs baru di aaPanel dengan domain Anda."
echo -e "   - Pasang Let's Encrypt SSL."
echo -e "   - Tambah Reverse Proxy mengarah ke ${YELLOW}http://127.0.0.1:8000${NC}"
echo -e "===================================================="
