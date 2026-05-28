# Bin Packing Microservice

Microservice computes the smallest single box that can fit all input products.

It uses a 3rd-party bin packing API for primary calculation:
- [Bin Packing API](https://binpacking.janedbal.cz/)

If the API is temporarily unavailable, it uses a local fallback heuristic.

## What the service does

- Accepts JSON input with `products` (each has `width`, `height`, `length`, `weight`)
- Loads available boxes from DB table `packaging`
- Returns one smallest usable box
- If products cannot be packed into a single box, returns `{"box": null}`

Products are rotation-friendly (dimensions can be permuted on any axis).

## Terminology

| Term in code / DB | Meaning |
|---|---|
| `Packaging` entity, `packaging` table | Warehouse **box** (stub naming) |
| `box` in API response | Selected shipping box returned to the client |
| `container` in `BinPackingApiClient` | External [Bin Packing API](https://binpacking.janedbal.cz/) naming only |

## API

HTTP contract (request/response schemas, status codes, examples): **[openapi.json](./openapi.json)**

You can preview it in Swagger Editor or any OpenAPI viewer.

## Design Decisions

### Units and integer conversion

The service is **unit-agnostic** (millimeters, centimeters, etc.), but **one consistent unit system must be used everywhere**:

- product dimensions and weight in the request (`products[]`)
- box dimensions and `maxWeight` in the database (`packaging` table, seed `data/packaging-data.sql`)

If request products and configured boxes use different scales (e.g. products as `34 × 21 × 30` while boxes remain `5.5 × 6.0 × 7.5` from seed), results will be wrong — often `{"box": null}`. Removing a decimal point is **not** equivalent (`3.4` ≠ `34`) unless box data is scaled the same way.

- Input accepts `int` or `float` (e.g. `3.4` or `34.0`)
- The 3rd-party API expects positive integers; values are sent as `round(value * 1000)` with the **same factor** applied to products and boxes
- Response `box` dimensions use the same unit system as stored in `packaging`

### Caching

- Results are cached in DB table `packing_cache`
- Cache key is built by `PackingCacheKeyGenerator` (SHA-256 of canonicalized input):
  - product dimensions normalized (sorted triplets) to be rotation-agnostic
  - product list sorted to be order-agnostic
  - current box definitions included, so box config changes invalidate cache naturally
- `PackingService` works with cache only via `findForInput` / `saveForInput` (no hash logic)
- No TTL is used; invalidation is data-driven via hash composition

### Fallback strategy

Fallback is used only for recoverable external API failures:
- network/connectivity issues (`BinPackingApiTransportException`)
- HTTP `429` (`BinPackingApiRateLimitedException`)
- HTTP `5xx` (`BinPackingApiServerException`)
- invalid/malformed API response after successful HTTP status (`BinPackingApiResponseException`)

Fallback is **not** used for:
- request validation errors from this service (`400/422`)
- external API client errors (`4xx`, except `429`) -> returns `502`

Local fallback heuristic checks:
- total weight <= box max weight
- total product volume <= box volume
- each individual product fits into the box with rotation allowed

### Error handling and observability

- Recoverable failures are handled in `PackingService` via `RecoverablePackingException`
- Non-recoverable external API failures bubble to `Application` as `502`
- Unexpected errors are logged via `ErrorLogger` (`error_log` JSON payload) and returned as `500`
- All dependencies are constructed explicitly in `ApplicationFactory` (no hidden `new` defaults in constructors)

### External API layering

- `BinPackingApi` — interface for the HTTP provider (swappable implementation)
- `JanedbalBinPackingApi` — calls [Bin Packing API](https://binpacking.janedbal.cz/) and returns decoded JSON
- `BinPackingRequestMapper` — maps domain `products` / boxes to API request shape
- `BinPackingResponseSelector` — picks smallest box from API response
- `BinPackingApiClient` — orchestrates the above

## Local development

## Requirements

- Podman + podman-compose, or Docker + docker-compose
- PHP dependencies are installed in container

### Start environment (Podman)

```bash
podman compose up -d
```

### Start environment (Docker)

```bash
docker-compose up -d
```

### Initialize app

Run in app container:

```bash
composer install
bin/doctrine orm:schema-tool:create
bin/doctrine dbal:run-sql "$(cat data/packaging-data.sql)"
```

With Podman in one command:

```bash
podman compose run --rm shipmonk-packing-app bash -lc "composer install && bin/doctrine orm:schema-tool:create && bin/doctrine dbal:run-sql \"$(cat data/packaging-data.sql)\""
```

### Run sample request

Inside app container:

```bash
php run.php "$(cat sample.json)"
```

Or with Podman:

```bash
podman compose run --rm shipmonk-packing-app bash -lc "php -r '\$payload = file_get_contents(\"sample.json\"); passthru(\"php run.php \" . escapeshellarg(\$payload));'"
```

## Testing

Run unit tests:

```bash
vendor/bin/phpunit
```

## Adminer

- URL: `http://localhost:8080/?server=mysql&username=root&db=packing`
- Password: `secret`
