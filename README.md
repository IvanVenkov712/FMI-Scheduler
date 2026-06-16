# WebProject

## Local Docker setup

Run the app and a local MySQL database with:

```sh
docker compose up --build
```

The application is available at `http://localhost:8080`.
The database is exposed on `127.0.0.1:3307` for direct local PHP runs or manual inspection.

The checked-in Compose file passes database settings to the `app` container.
If you run the image without this Compose file, provide the same `DB_*` values
with environment variables, `--env-file`, or a mounted `/var/www/html/.env`.

## Environment configuration

Copy `.env.example` to `.env` and set the values for your local environment.
The `.env` file is ignored by Git and must not contain values intended for
version control.

Configuration priority, from highest to lowest:

1. System environment variables
2. Values from `.env`
3. Fallback values from `config/app.ini`

If a value in `.env` contains `$`, wrap that value in single quotes so Docker
Compose does not treat part of it as another environment variable.
