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

## API

Базовый адрес: `/api/v1`. Формат: JSON. Авторизация: заголовок `Authorization: Bearer <token>` (Laravel Sanctum).
Единый формат ошибок: `{"message": "..."}`, для валидации `{"message": "Ошибка валидации", "errors": {"поле": ["..."]}}`.
Коды: 401 нет токена, 403 недостаточно прав, 404 не найдено, 409 конфликт состояния, 422 ошибка валидации или бизнес-правила.

| Группа | Эндпоинты | Роль |
|---|---|---|
| Аутентификация | `POST /auth/register`, `POST /auth/login`, `POST /auth/logout` | гость, гость, любой вошедший |
| Профиль | `GET /profile`, `PATCH /profile` | любой вошедший |
| Виды занятий | `GET /workouts`, `GET /workouts/{id}` | все |
| | `POST/PATCH/DELETE /workouts` | администратор |
| Типы абонементов | `GET /membership-types`, `GET /membership-types/{id}` | все |
| | `POST/PATCH/DELETE /membership-types` | администратор |
| Расписание | `GET /schedule-items` (фильтры, поиск, сортировка, пагинация), `GET /schedule-items/{id}` | все |
| | `POST/PATCH /schedule-items`, `POST /schedule-items/{id}/cancel` | администратор |
| | `GET /trainer/schedule-items` | тренер |
| Абонементы клиента | `GET /memberships`, `POST /memberships` | клиент |
| Записи | `POST /schedule-items/{id}/bookings`, `GET /bookings` | клиент |
| | `DELETE /bookings/{id}` | клиент (своя запись), администратор |
| Тренер | `GET /schedule-items/{id}/bookings`, `POST /bookings/{id}/visit` | тренер (своё занятие), администратор |
| Пользователи | `GET /users`, `PATCH /users/{id}` | администратор |
| Отчёты | `GET /reports/memberships-sales`, `/reports/bookings-by-status`, `/reports/popular-workouts` | администратор |

Подробное описание каждого эндпоинта с примерами запросов и ответов: документация Scribe (см. ниже).

## Тестовые пользователи

Создаются командой `php artisan migrate --seed`. Пароль у всех: `password`.

| Роль | Email |
|---|---|
| Администратор | admin@fitclub.test |
| Тренер | sokolov@fitclub.test, lebedeva@fitclub.test |
| Клиент | petrov@fitclub.test, smirnova@fitclub.test, orlov@fitclub.test, kim@fitclub.test |

## Тесты

Тесты используют SQLite в памяти (`phpunit.xml`), трейт `RefreshDatabase` и фабрики.

```bash
php artisan test
```

## Документация API (Scribe)

```bash
composer require --dev knuckleswtf/scribe
php artisan vendor:publish --tag=scribe-config
php artisan scribe:generate
```

Описание берётся из PHPDoc-комментариев контроллеров (`@group`, `@bodyParam`, `@queryParam`, `@response`).
После генерации документация открывается по адресу http://localhost:8000/docs.

## Журнал

Каждый запрос к API записывается в `storage/logs/laravel.log` (middleware `LogApiRequests`): метод, адрес,
id пользователя, код ответа и время выполнения. Бизнес-события (запись, отмена, покупка абонемента, отметка
посещения) пишутся уровнями `info` и `warning`, непредвиденные ошибки — `error`. Тело запроса и заголовок
`Authorization` не логируются, поэтому пароли и токены в журнал не попадают.

## Вход через Google и сброс пароля

**Вход через Google.** Клиент (React) показывает кнопку Google Identity Services, получает ID-токен и отправляет его
на `POST /api/v1/auth/google`. Сервер проверяет токен у Google (подпись, срок, получатель `aud`, подтверждённый email),
затем находит пользователя по `google_id` или email либо создаёт нового клиента, и возвращает токен Sanctum.
Заблокированный пользователь войти не может (403).

Настройка:

1. В [Google Cloud Console](https://console.cloud.google.com/) создайте проект, затем «API и сервисы» → «Экран согласия OAuth»
   (тип «Внешний», добавьте свой Gmail в тестовые пользователи) → «Учётные данные» → «Создать учётные данные» →
   «Идентификатор клиента OAuth» → тип «Веб-приложение». В «Разрешённые источники JavaScript» добавьте
   `http://localhost:5173`.
2. Скопируйте Client ID в `.env` сервера (`GOOGLE_CLIENT_ID=...`) и в `frontend/.env` (`VITE_GOOGLE_CLIENT_ID=...`).
3. Выполните `php artisan migrate` (добавляет столбец `users.google_id`) и перезапустите `php artisan serve` и `npm run dev`.

**Сброс пароля.** `POST /api/v1/auth/forgot-password` отправляет на email письмо со ссылкой
`FRONTEND_URL/reset-password?token=...&email=...` (ссылка действует 60 минут); ответ всегда одинаковый, поэтому по нему
нельзя узнать, зарегистрирован ли адрес. `POST /api/v1/auth/reset-password` задаёт новый пароль и отзывает все выданные
токены. Пользователь, зарегистрированный через Google, может задать пароль этим же способом.

Для отправки писем на реальную почту в `.env` укажите SMTP (пример для Gmail: включите двухэтапную аутентификацию,
создайте «пароль приложения» и подставьте его в `MAIL_PASSWORD`; блок настроек есть в `.env.example`).
С `MAIL_MAILER=log` письма не отправляются, а пишутся в `storage/logs/laravel.log`: так удобно проверять работу без SMTP.
После изменения `.env` выполните `php artisan config:clear`.

