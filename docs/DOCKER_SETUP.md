# Run FINALS in Docker

From `C:\WEBDEV\FINALS`, with Docker Desktop running:

```powershell
docker compose up -d --build
```

Docker Desktop groups the application, MySQL, phpMyAdmin, and Mailpit under **finals**.

- Application: http://localhost:8000
- phpMyAdmin: http://localhost:8081 (use your existing database credentials)
- Mailpit: run `docker compose port mailer 8025` to find its local web port.

This is a local development setup using Symfony's development environment. The application port is accessible only from this computer. It is not a production deployment.

## Data and settings

The application reads the existing `.env` and `.env.dev` through read-only mounts; both are excluded from the image. Inside Docker, `DB_HOST` becomes `symfony-db` and `DB_PORT` becomes `3306`. Your Windows database settings remain unchanged. Application emails go to Mailpit inside Docker.

MySQL keeps the existing `symfony-db-data` volume and credentials. Uploads remain in `public/uploads` in this folder; application cache, logs, and sessions use the `finals_app-var` volume. No migrations, schema changes, or database resets run automatically.

The existing database and phpMyAdmin containers are already managed by the `finals` Compose project. Unrelated Docker projects are not included.

## Everyday commands

```powershell
docker compose ps
docker compose logs --tail=100 app
docker compose exec --user www-data app php bin/console cache:clear
docker compose stop
docker compose start
```

After changing application code, rebuild with `docker compose up -d --build app`. Upload changes are available immediately. Start the whole project with the default Compose files so the Mailpit service is included.

Do not use `docker compose down --volumes`: it deletes the database and application volumes. A regular `docker compose stop` preserves all data.
