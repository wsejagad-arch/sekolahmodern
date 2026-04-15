#!/bin/bash

echo "Starting WhatsApp Node Worker in background..."
cd worker
npm install
nohup node index.js > ../worker.log 2>&1 &
cd ..

echo "Starting FastAPI Python Backend in background..."
# Try to activate virtual environment if it exists
if [ -d "venv" ]; then
    source venv/bin/activate
fi
nohup python3 main.py > backend.log 2>&1 &

echo ""
echo "✅ All services successfully started in the background!"
echo "To view Node worker logs: tail -f worker.log"
echo "To view Python backend logs: tail -f backend.log"
