# Open-Meteo Gateway

A Symfony + Vue 3 app for weather search and saved favorites. The frontend only calls the Symfony API, which acts as the Open-Meteo gateway.

## Prerequisites

- Docker and Docker Compose

## Run the app

```bash
docker compose up --build
```

- Frontend: http://localhost:5173
- Backend: http://localhost:8080

The backend container installs dependencies and runs migrations on start.

## Tests (backend)

```bash
docker compose exec backend php bin/phpunit
```

## API (backend)

- `GET /api/weather?city=Paris`
- `GET /api/weather?lat=48.856&lon=2.352`
- `GET /api/favorites`
- `POST /api/favorites`
- `DELETE /api/favorites/{id}`
