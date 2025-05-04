@echo off
REM === Start Apache from XAMPP ===
echo Starting Apache...
start "" "C:\xampp\xampp-control.exe"
timeout /t 5 /nobreak >nul

REM === Start ngrok tunnel on port 80 ===
echo Starting ngrok tunnel...
start "" "C:\Users\vhmfa\ngrok-bin\ngrok.exe" http 80

REM === Optional: open your local page in browser ===
timeout /t 3 >nul
start "" http://localhost/cya/index.html

echo All systems launched. You may close this window after testing.
pause
