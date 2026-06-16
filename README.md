# WebProject

## Local Docker setup

Run the app and a local MySQL database with:

```sh
docker compose up --build
```

The application is available at `http://localhost:8080`.
The database is exposed on `127.0.0.1:3307` for direct local PHP runs or manual inspection.

## Environment configuration

Copy `.env.example` to `.env` and set the values for your local environment.
The `.env` file is ignored by Git and must not contain values intended for
version control.

Configuration priority, from highest to lowest:

1. System environment variables
2. Values from `.env`
3. Fallback values from `config/app.ini`
