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

## API Contract

### Input

`POST /pack` JSON body:

```json
{
  "products": [
    { "width": 3.4, "height": 2.1, "length": 3.0, "weight": 4.0 },
    { "width": 4.9, "height": 1.0, "length": 2.4, "weight": 9.9 }
  ]
}
```

### Success response

- `200 OK`

```json
{
  "box": {
    "id": 4,
    "width": 5.5,
    "height": 6.0,
    "length": 7.5,
    "maxWeight": 30.0
  }
}
```

If no single box can fit all products:

```json
{
  "box": null
}
```

### Error responses

- `400` malformed JSON
- `422` validation failure (`products` missing/empty, non-numeric values, non-positive values)
- `500` unexpected internal error

## Design Decisions

### Units and integer conversion

- Input accepts decimal numbers (`float`)
- The 3rd-party API expects integers, so values are converted by `round(value * 1000)`
- Service is unit-agnostic, but all values in one request must use consistent units

### Caching

- Results are cached in DB table `packing_cache`
- Cache key is SHA-256 of canonicalized input:
  - product dimensions normalized (sorted triplets) to be rotation-agnostic
  - product list sorted to be order-agnostic
  - current box definitions included, so box config changes invalidate cache naturally
- No TTL is used; invalidation is data-driven via hash composition

### Fallback strategy

Fallback is used for temporary API unavailability:
- network/connectivity issues
- timeouts
- HTTP `429`
- HTTP `5xx`

Fallback is not used for request validation errors.

Local fallback heuristic checks:
- total weight <= box max weight
- total product volume <= box volume
- each individual product fits into the box with rotation allowed

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
