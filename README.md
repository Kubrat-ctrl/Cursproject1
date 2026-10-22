# Fitness Club API

REST API фитнес-клуба: абонементы, расписание групповых занятий, запись клиентов.
Стек: PHP 8.3, Laravel, MySQL 8, Laravel Sanctum.

## Установка

```bash
git clone https://github.com/Kubrat-ctrl/Cursproject1.git
cd Cursproject1
composer install
cp .env.example .env
php artisan key:generate
# создайте БД fitness_club и укажите доступы к ней в .env
php artisan migrate --seed
php artisan serve
```

В Windows вместо `cp .env.example .env` можно выполнить `copy .env.example .env`.

Проверка: GET http://localhost:8000/api/health

Ожидаемый ответ (код 200):

```json
{"app":"Fitness Club API","time":"2026-10-22T10:53:15+00:00","database":"ok"}
```

## Примечание для OSPanel

В OSPanel хост MySQL задаётся именем модуля, например `DB_HOST=MySQL-8.4`,
а не `127.0.0.1`.