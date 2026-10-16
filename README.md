# OMG Back Office

Standalone merchant back end for the OMG Casino API, extracted from the casino app.
Only two roles exist: **webmaster** and **merchant**.

| Role | Section | What it does |
|---|---|---|
| `merchant` | `/backoffice` | Dashboard (incl. per-game and per-provider bet, win, GGR, RTP vs target), merchant credit (read-only), rankings, trend, retention, player list, game/order records, live OMG bet records, per-player RTP control (calls OMG), game config & batch changes (recorded as change requests), operation log |
| `webmaster` | `/webmaster` | Load/deduct/reset merchant credit, work through the merchant's game-config change requests, create/update the merchant login |

## Setup

```bash
composer install
cp .env.example .env && php artisan key:generate
# set WEBMASTER_PASSWORD and the OMG_* values in .env
php artisan migrate --seed     # roles + first webmaster
npm install && npm run build
php artisan serve
```

Sign in at `/login` as the webmaster, then create the merchant login under **Merchant Account**.

## Data

Reports read `omg_transactions` and `players`, which the casino's OMG callback fills. This app
has no player login and no callback endpoint; point it at (or sync into) those two tables.
`php artisan backoffice:reconcile-merchant-credit` folds in any transactions the credit balance
hasn't counted yet (safe to run repeatedly).

## OMG errors

OMG error codes (general and backend) are translated to plain language, with a hint where the merchant can act, in `App\Support\OmgErrorCodes`. API failures throw `App\Exceptions\OmgApiException`.

## Tests

`php artisan test`
