# PriceTracker

PriceTracker ir web lietotne, kur lietotājs pievieno kripto aktīvus, sistēma periodiski atjauno cenas no CoinGecko un rāda vēsturi ar paziņojumiem par izmaiņām.

## Tehnoloģijas

- Backend: PHP 8.2, Laravel 12, Laravel Sanctum
- Frontend: Vue 3, Vite, Vuetify, Pinia, Vue Router, vue-i18n
- Dati: MySQL
- Grafiki: Chart.js
- Ārējais datu avots: CoinGecko API

## Ko projekts dara

- Reģistrācija un pieslēgšanās ar tokenu autentifikāciju.
- Kripto aktīvu pievienošana uzraudzībai.
- Aktuālās cenas un cenu vēsture ar grafikiem.
- Paziņojumi par cenu izmaiņām un nelasīto paziņojumu skaits.
- Uzraudzības noteikumu (tracking rules) rediģēšana katram aktīvam.
- Admin sadaļa lietotāju, aktīvu, darbību un logu pārvaldībai.

## Lokāla palaišana

Prasības: PHP 8.2+, Composer, Node.js 20+, npm, MySQL.

1. Backend iestatīšana:

```bash
cd backend
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
```

2. Backend servisu palaišana (atsevišķos termināļos):

```bash
cd backend
php artisan serve
```

```bash
cd backend
php artisan queue:listen
```

```bash
cd backend
php artisan schedule:work
```

3. Frontend palaišana:

```bash
cd frontend
npm install
npm run dev
```

Frontend pēc noklusējuma darbojas uz http://localhost:5173, backend API uz http://localhost:8000.

## Vides mainīgie

Projekts izmanto backend/.env.example.

Svarīgākie mainīgie:

- APP_URL
- FRONTEND_URL
- DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
- QUEUE_CONNECTION
- UNVERIFIED_USER_MONTHLY_LIMIT, VERIFIED_USER_MONTHLY_LIMIT
- COINGECKO_BASE_URL, COINGECKO_VS_CURRENCY, COINGECKO_API_KEY, COINGECKO_TIMEOUT
