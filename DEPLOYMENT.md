# Deploying Lakeside Hotel

The production host runs PHP. It does not run Node, a queue worker, or a
scheduler, and this repository is arranged so that it does not need to.

## What the host has to provide

- PHP 8.3 or newer (the project is developed on 8.4) with `pdo_mysql`, `gd`,
  `mbstring`, `openssl`, `fileinfo`, `exif`, and `tokenizer`.
  `gd` is not optional: every image the site shows is resized by the media
  library through it, so without `gd` the site comes up with no photographs.
- MySQL 8 (the local database is already MySQL, so nothing here is SQLite-only).
- Composer, or a machine that can run it before the files are uploaded.
- A writable `storage/`, `bootstrap/cache/`, and `public/bucket/`.

Node is deliberately absent from that list. `public/build` is committed, because
`@vite()` in `resources/views/app.blade.php` throws a
`ViteManifestNotFoundException` on every page when the compiled assets and their
manifest are missing, and the server has no `npm` to produce them.

## First deploy

```bash
git clone https://github.com/MotionPhix/lakeside-hotel.git
cd lakeside-hotel

composer install --no-dev --optimize-autoloader

cp .env.production.example .env
#   fill in APP_URL, the database, and the mailbox; then:
php artisan key:generate

php artisan migrate --force
```

`--force` is required: without it Artisan stops and asks for confirmation
because `APP_ENV=production`. The `booking_reference_sequences` table arrives
with these migrations and has to exist before the first reservation is taken.

### Content, once, on an empty database

```bash
php artisan db:seed --class=TeamSeeder --force
php artisan db:seed --class=SiteContentSeeder --force
php artisan db:seed --class=SiteSectionSeeder --force
php artisan db:seed --class=AccommodationSeeder --force
php artisan db:seed --class=ExperienceSeeder --force
php artisan db:seed --class=DiningPhotographySeeder --force
php artisan db:seed --class=MarketingSeeder --force
php artisan db:seed --class=HotelHouseRulesSeeder --force
```

Run these on a fresh database and not again afterwards: the seeders write the
hotel's copy over whatever is in the database, so re-running them after staff
have edited a page in the dashboard puts the original text back.

**Do not run `php artisan db:seed` on its own.** `DatabaseSeeder` ends with
`GuestSeeder`, which creates forty-eight demonstration guests and then writes
real `Booking` rows for them. `GuestSeeder` now refuses to run when
`APP_ENV=production`, but the class list above is still the correct way to seed
a live site.

## Media

`public/bucket` is not in git. The 75 MB in it is output: resized copies of the
51 photographs the repository does ship in `database/seeders/photography`. The
content seeders above regenerate it, which is why they are run on the server
rather than copied over. The two brand marks (`lakeside-wordmark.png`,
`lakeside_hotel_logo.png`) are source files and are tracked.

If you would rather not have the server generate them, upload the local
`public/bucket` folder by SFTP into place before seeding. The paths are the same
either way.

## Finishing the deploy

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

`storage:link` is not needed: `config/filesystems.php` writes media under
`public/bucket`, which the web server serves directly.

Nothing needs to stay running afterwards. No class implements `ShouldQueue`,
nothing dispatches a job, both booking emails use `Mail::send` synchronously,
and there is no `schedule()` call anywhere - so no worker and no cron entry.

## Payments

With `PAYCHANGU_SECRET_KEY` blank the site is complete but takes no money
online: `isOnlinePaymentEnabled()` returns false, the booking page drops the Pay
Now option, and the server rejects a payment attempt anyway. Guests reserve and
settle at the hotel.

Two things have to be true for online payment to appear, not one: the key has to
be set, and the **Online card payment** toggle under the dashboard's booking
settings has to be on.

To turn payments on, set `PAYCHANGU_SECRET_KEY` and `PAYCHANGU_WEBHOOK_SECRET`,
then point the webhook in the PayChangu dashboard at:

```
https://<live-domain>/webhooks/paychangu
```

That route is outside CSRF (see `bootstrap/app.php`) and authenticates callers
with a signature over the raw body instead.

## Check it worked

- `https://<live-domain>/up` returns a healthy response.
- `/robots.txt` returns `text/plain` and a `Sitemap:` line built from `APP_URL`.
- `/sitemap.xml` returns `application/xml` and lists the room categories.
- The homepage shows photographs, not empty frames - that is `public/bucket`
  having been generated.
- Make a booking and confirm the guest receives the confirmation email.

## Shipping an update

```bash
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

If the update touched anything under `resources/`, build on a machine with Node
(`npm run build`), commit `public/build` with it, and pull that. Do not leave the
committed manifest older than the source it was built from.

## HTTPS behind a proxy

`APP_URL` has to be the `https://` address. If TLS ends at a proxy or CDN,
make sure `X-Forwarded-Proto: https` reaches PHP - otherwise Laravel generates
`http://` URLs and the signed booking links in confirmation emails will not
open. Configure trusted proxies for the load balancer in that case.
