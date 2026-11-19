@echo off
set ROOT=%~dp0
set PHP=C:\OSPanel\modules\PHP-8.3\php.exe
if not exist "%PHP%" (
  echo PHP not found: %PHP%
  pause
  exit /b 1
)

cd /d "%ROOT%"

if not exist vendor (
  echo === Installing PHP dependencies ===
  if not exist composer.phar curl -L -o composer.phar https://getcomposer.org/download/latest-stable/composer.phar
  "%PHP%" composer.phar install
  if not exist .env copy .env.example .env
  "%PHP%" artisan key:generate
  "%PHP%" artisan migrate --seed --force
  "%PHP%" artisan schedule:fill
)

if not exist frontend\node_modules (
  echo === Installing frontend dependencies ===
  cd /d "%ROOT%frontend"
  call npm install
  cd /d "%ROOT%"
)

start "Laravel" cmd /k "cd /d %ROOT% && "%PHP%" artisan serve"
start "Vite" cmd /k "cd /d %ROOT%frontend && npm run dev"
timeout /t 5 >nul
start http://localhost:5173