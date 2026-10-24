# Fase 0 — Verificación de hipótesis

> Fecha: 2026-10-24 · Rama: `fase-0-verificacion` · Sin cambios de código.
> Entorno: contenedor `php:8.3-cli-alpine` (PHP 8.3.31), el mismo que usa `docker-compose.yml` y CI.

## Resumen

| # | Comprobación | Resultado |
|---|---|---|
| 0.1 | Línea base de tests | **OK**: 63 tests, 140 assertions, PHPUnit 11.5.55 |
| 0.2 | ¿Ritual duplicado en JS? | **Confirmado**: la web usa su propia implementación en JS; `Ritual.php` solo lo usa la API |
| 0.3 | Origen de `innerHTML` / XSS | **Sin vector encontrado** (ver detalle) |
| 0.4 | Zona horaria y DST | **Confirmado el defecto, sin efecto hoy**: el contenedor está en UTC |
| 0.5 | Fechas desbordadas | **Confirmado**: `2026-02-31` se acepta y se normaliza a `2026-03-03` |

## 0.1 Línea base

```
docker run --rm -v "$PWD":/app -w /app php:8.3-cli-alpine php vendor/bin/phpunit
OK (63 tests, 140 assertions)
```

Los tests solo cubren `src/` (`tests/unit/*`). Nada cubre `index.php`, `api/index.php` ni el JS.

## 0.2 Ritual duplicado

- `index.php` solo hace `require_once` de `Biorrhythms`, `Compatibility`, `ExtremeDays` y `DateFormatter`. **No carga `Forecast.php` ni `Ritual.php`.**
- La web calcula decisión y ritual en JS: `decisionForPoint` y `ritualForPoint` (`index.php` ~3427 en adelante).
- `api/index.php` sí usa `Ritual::generate`.
- Los textos **ya divergen**. Ejemplo, línea 1 del ritual:
  - JS: `Empieza con ${currentDecision.action.toLowerCase()}.`
  - PHP: frases fijas por ritmo dominante («Empieza con una acción física y un cierre rápido.»).
- Consecuencia: la web y `/api/` pueden mostrar rituales distintos para la misma fecha. `Ritual.php` está testeado y la versión visible en la web no.
- Implicación para el plan: C6 (ritual con variantes) debe decidir un único origen. Propuesta: la lógica vive en PHP y la web consume el resultado serializado, o se retira `Ritual.php`. Se decide en la Fase B/C, no ahora.

## 0.3 `innerHTML` y XSS

Entradas no confiables: solo `$_GET` (`birth`, `focus`, `partner_birth`, `preset`, `embed`, `view`, `theme`) y `$_SERVER['PHP_SELF']`.

- `birth`, `focus` y `partner_birth` pasan por `clampDateInput`, que devuelve `DateTime::format('Y-m-d')`: siempre dígitos y guiones, nunca el texto original.
- `preset` y `theme` se validan contra una lista cerrada.
- Los strings que llegan a `innerHTML` (`point.label`, `rhythm.label`, `decision.*`, `label` de extremos) vienen de `DateFormatter` (tablas fijas) o de literales del propio JS. Los valores numéricos se formatean con `Math.round`/`toFixed`.
- `PHP_SELF` aparece una vez (línea 123), y todos sus usos en HTML pasan por `htmlspecialchars`. No se interpola en el JS.
- El `json_encode` embebido en `<script>` (línea 2946) no usa `JSON_HEX_TAG`. Hoy es inocuo porque los datos están normalizados; **si en el futuro se serializa texto libre (p. ej. notas del diario), hay que añadir `JSON_HEX_TAG | JSON_HEX_AMP`.**
- Alcance: revisé los sitios de `innerHTML` de ~3180, ~3529, ~3701-3840; no leí todo el JS línea a línea.

**Conclusión:** no hay XSS explotable hoy. No cambia la prioridad. El riesgo aparece con C2 (diario) si se renderizan textos del usuario con `innerHTML`; usar `textContent` desde el principio.

## 0.4 Zona horaria y DST

`daysBetween()` = `(timestamp_to − timestamp_from) / 86400`.

```
UTC            2026-03-30  days=19550.000000  (calendario: 19550)
Europe/Madrid  2026-03-30  days=19549.958333  (calendario: 19550)
Europe/Madrid  2026-10-24  days=19757.958333  (calendario: 19758)
```

- Con TZ de Madrid, tras el cambio de hora, la diferencia es −0.0417 días (1 h).
- Efecto sobre los valores: cambio pequeño (≈0.01 en el seno del ritmo de 23 días), pero rompe la igualdad exacta en tests y puede desplazar un cruce por cero de un día.
- **El contenedor corre en UTC** (`date_default_timezone_get()` = `UTC`), así que hoy no se manifiesta. Sí lo haría en un despliegue con otra zona o con PHP en el host.
- Además, «hoy» por defecto es `new DateTimeImmutable('today')` en la zona del servidor, no la del usuario (A5).

## 0.5 Fechas desbordadas

`createFromFormat('Y-m-d', ...)` con la implementación actual de `clampDateInput`:

| Entrada | Resultado | Aviso de PHP |
|---|---|---|
| `2026-02-31` | `2026-03-03` | `The parsed date was invalid` (ignorado) |
| `2026-13-01` | `2027-01-01` | idem |
| `0000-00-00` | `-0001-11-30` | idem |
| `abc` | `false` → cae al default | — |
| `2999-01-01` | aceptada | ninguno |
| `1800-01-01` | aceptada | ninguno |

- `clampDateInput` no consulta `getLastErrors()`, por eso entradas inválidas producen una fecha distinta y aceptada.
- No hay rango: fechas futuras o de siglos atrás se procesan igual.
- Esto aplica por igual a `index.php` y `api/index.php` (la función está copiada en ambos).

## Efecto en el plan

- **A1/A2/A3:** confirmados como necesarios; el test de A1 puede usar la tabla de 0.5 como casos.
- **A4/A5:** confirmados como defecto latente; añadir test con `Europe/Madrid` (caso `2026-03-30`).
- **A6/C6:** añadir al alcance la decisión de «un único origen del ritual» (hallazgo 0.2).
- **C2:** requisito nuevo: el diario se renderiza con `textContent` y el JSON embebido usa `JSON_HEX_TAG | JSON_HEX_AMP`.
- **Prioridad:** sin cambios. 0.3 no encontró nada que adelante la seguridad.
