@echo off
echo Starting WhatsApp Node Worker...
start cmd /k "cd worker && node index.js"

echo Starting FastAPI Python Backend...
start cmd /k "venv\Scripts\activate && python main.py"

echo.
echo All services started in separate windows!
echo You can safely close this window now.
timeout /t 3
exit
