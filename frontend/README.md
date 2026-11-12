# Клиент «Фитнес-клуб» (React + Vite)

## Запуск

1. Запустите API (в каталоге Laravel): `php artisan serve` (адрес http://localhost:8000).
2. В каталоге `frontend`:
```
npm install
npm run dev
```
3. Откройте http://localhost:5173.

Адрес API задаётся в `frontend/.env`: `VITE_API_URL=http://localhost:8000/api/v1`.
Адрес клиента должен быть указан в `.env` Laravel: `CORS_ALLOWED_ORIGINS=http://localhost:5173`.
Чтобы изображения занятий открывались, один раз выполните `php artisan storage:link`.

## Тестовые пользователи (пароль `password`)

| Роль | Email |
|---|---|
| Администратор | admin@fitclub.test |
| Тренер | sokolov@fitclub.test |
| Клиент | petrov@fitclub.test |
