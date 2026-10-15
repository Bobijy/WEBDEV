# Existing Docker setup

The project keeps `compose.yaml` as its single main Compose file. It matches the running Symfony database containers inspected on 13 October 2026:

| Service / container | Image | Host port → container port | Network |
| --- | --- | --- | --- |
| `symfony-db` | `mysql:8.0` | `3310 → 3306` | `platform-network` |
| `symfony-pma` | `phpmyadmin` | `8081 → 80` | `platform-network` |

MySQL uses the existing volume `symfony-db-data` mounted at `/var/lib/mysql`. Both that volume and the existing network are declared external. The old PostgreSQL service and port override have been removed. The separate Mailpit development service remains in `compose.override.yaml`.

## Application connection

Symfony runs on the Windows host and uses `DB_HOST=127.0.0.1`, `DB_PORT=3310`, and `DB_NAME=app_db` from the existing `.env`. Its current account is root. Compose obtains `MYSQL_ROOT_PASSWORD` from the existing `DB_PASSWORD`; no password is copied into YAML, and no new credentials are introduced. `MYSQL_USER` is intentionally omitted because the official MySQL image creates root separately.

phpMyAdmin connects inside Docker to `symfony-db:3306`. Open **http://localhost:8081** and sign in with the existing MySQL account. The host port 3310 is for host applications, not phpMyAdmin's internal connection.

## Current containers versus Compose ownership

The inspected `symfony-db` and `symfony-pma` containers were created outside Compose. Matching `container_name` does not transfer their ownership to Compose. They remain usable as they are; editing these files does not replace or change their data. The YAML adds restart and health-check settings that would take effect only when containers are created from it.

To start the existing containers if stopped:

```powershell
docker start symfony-db symfony-pma
```

Validate configuration without printing resolved passwords:

```powershell
docker compose config --quiet
docker compose config --services
```

Do not run `docker compose up` against these existing names expecting automatic adoption: Docker would report a name conflict. A future migration to Compose management requires a verified database backup and deliberate replacement of the two containers while retaining the external volume and network. That replacement has not been performed. MySQL initialization environment variables do not reset credentials or import data into an already initialized volume.

The unrelated `webdev_b_activity_2_mysql` and `webdev_b_activity_2_phpmyadmin` containers are not part of this application's configuration.

References: [Docker external volumes](https://docs.docker.com/reference/compose-file/volumes/), [Docker existing networks](https://docs.docker.com/compose/how-tos/networking/), [MySQL official image](https://hub.docker.com/_/mysql/), [phpMyAdmin official image](https://hub.docker.com/_/phpmyadmin).
