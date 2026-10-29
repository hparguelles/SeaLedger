# Deploying SeaLedger on Railway

1. **Push the code to GitHub.** The repo root must be this `sealedger` folder (the one containing `Dockerfile` and `artisan`).
2. **Railway → New Project → Deploy from GitHub repo** → pick the repo. Railway reads `railway.json` and builds the `Dockerfile`.
3. **Add a database:** in the project, *+ New → Database → MySQL*.
4. **Add a Volume** (keeps uploaded fish photos across deploys): open the app service → *Volumes → New Volume* → mount path `/app/storage/app/public`.
5. **Set variables** on the app service (Variables tab):

   | Variable | Value |
   |---|---|
   | `APP_KEY` | run `php artisan key:generate --show` locally and paste the result (`base64:...`) |
   | `APP_ENV` | `production` |
   | `APP_DEBUG` | `false` |
   | `APP_URL` | your Railway URL, e.g. `https://sealedger-production.up.railway.app` |
   | `DB_CONNECTION` | `mysql` |
   | `DB_URL` | `${{MySQL.MYSQL_URL}}` |
   | `SESSION_DRIVER` | `database` |
   | `MESSENGER_URL` | your Messenger link, e.g. `https://m.me/yourpagename` (shows the Message us button on the customer page) |
   | `RUN_SEED` | `true` (first deploy only, then delete it) |

6. **Generate a public domain:** app service → *Settings → Networking → Generate Domain*, then put it in `APP_URL`.
7. Open the site. Vendor login after seeding: username `vendor`, password `SeaLedger123!` (change it after the first login).

Notes
- Without the Volume, uploaded photos disappear on every redeploy.
- Redeploys run `php artisan migrate --force` automatically.
