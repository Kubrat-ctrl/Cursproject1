@echo off
set PHP=C:\OSPanel\modules\PHP-8.3\php.exe
if not exist "%PHP%" (
  echo PHP not found: %PHP%
  pause
  exit /b 1
)
start "Laravel" cmd /k "cd /d C:\OSPanel\home\Cursproject1 && "%PHP%" artisan serve"
start "Vite" cmd /k "cd /d C:\OSPanel\home\Cursproject1\frontend && npm run dev"
timeout /t 5 >nul
start http://localhost:5173
