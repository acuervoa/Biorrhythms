# Biorrhythms — Plan de actuación

> Fecha: 2026-10-24
> Origen: auditoría de código y producto (sin cambios aplicados).
> Convención: un commit por tarea, una rama por fase. Sin commits ni push sin petición explícita.

## Estado

| Fase | Estado | Release |
|---|---|---|
| 0 | Hecha | v1.1.1 |
| A | Hecha | v1.2.0 |
| B | Hecha | v1.3.0 |
| C | Hecha (ver notas) | v1.4.0 |
| D, E | Pendientes | — |

Notas de la Fase C: el ritual de la web y de `/api/v1/` sale ya del mismo código PHP (`Ritual::daily`), y `/api/` conserva `Ritual::generate`. La compatibilidad con pesos y tendencia (C7) se expone en `/api/v1/`; la web mantiene su cálculo con `PRESET_WEIGHTS`, que coincide con los pesos de PHP. Fuera de alcance según decisión: efemérides y ciclo menstrual.

Los puntos marcados como **hipótesis** en la auditoría se verifican en la Fase 0 antes de decidir alcance.

---

## Fase 0 — Verificar hipótesis (S, ~1 h, no cambia código)

| # | Comprobación | Cómo |
|---|---|---|
| 0.1 | Línea base de tests | phpunit en contenedor; anotar resultado actual |
| 0.2 | ¿Lógica de ritual duplicada en JS? | Leer JS de `index.php` ~3500-3600 contra `Ritual.php` |
| 0.3 | Origen de cada `innerHTML` | Revisar líneas 3529-3832: ¿algún valor viene de la URL? |
| 0.4 | Zona horaria del contenedor y efecto DST | `php -r` con TZ no UTC y `daysBetween` |
| 0.5 | Fechas desbordadas | `?birth=2026-02-31` en web y API; documentar resultado |

Salida: nota con hechos confirmados. Si 0.3 encuentra XSS real, sube a prioridad máxima.

---

## Fase A — Corrección (S–M)

Objetivo: dejar de devolver resultados falsos sin aviso.

| # | Tarea | Contrato afectado | Verificación |
|---|---|---|---|
| A1 | `src/DateInput.php`: parseo estricto con `getLastErrors()`, rango 1900..hoy+N, sin desbordamiento. Sustituye los dos `clampDateInput` duplicados | Interno | Tests unitarios (válida, `02-31`, vacía, futura, basura) |
| A2 | Nuevo `/api/v1/index.php`: 400 con JSON de error si `birth` o `focus` son inválidos o `birth` falta. `/api/` legado intacto, con header `Deprecation` | Aditivo (el legado no cambia) | Test + curl a ambos endpoints |
| A3 | Web: aviso visible cuando una fecha es inválida o falta | UI | Revisión manual |
| A4 | `daysBetween` por fechas de calendario (UTC o `diff()->days`), no `timestamp/86400` | Valores pueden cambiar ±0.04 día con DST | Test con TZ `Europe/Madrid` cruzando cambio de hora |
| A5 | "Hoy" según zona del usuario: JS envía `focus` por defecto cuando no viene en la URL | Comportamiento por defecto | Manual con TZ simulada |
| A6 | Mover `profileLabel` y ventana de 90 días a `src/` (un solo sitio, web y API) | Ninguno | Tests existentes siguen verdes |
| A7 | `profileLabel` en español en UI y `/api/v1/`; añadir `profile.key` estable en inglés. `/api/` legado conserva el label en inglés | Aditivo (solo v1 y UI cambian) | Documentar en README |
| A8 | `/api/v1/`: honrar `preset` | Aditivo | Test |

Rollback: `git revert` por commit. Sin migraciones ni datos persistentes.

---

## Fase B — Núcleo del modelo (M)

TDD, en `src/`, sin tocar UI hasta que la lógica esté probada.

| # | Tarea | Notas |
|---|---|---|
| B1 | Fase y pendiente por ritmo (signo de `cos`) | Estados: ascendente, pico, descendente, valle |
| B2 | Días críticos: cruces por cero dobles y triples | Tolerancia ±0.5 día, documentada |
| B3 | Dispersión (máx−mín entre ritmos) y detección de "conflicto interno" | Corrige el caso +90/−90/0 |
| B4 | `dominantKey` por magnitud absoluta; caso "todos negativos" | Cambia texto del ritual |
| B5 | Revisar umbrales asimétricos de `Ritual` (0.35 / 0.1 / −0.15) | Si son intencionales, comentarlo |
| B6 | `ExtremeDays` con umbral configurable (`?extreme=0.8`), default sin cambios | Aditivo |
| B7 | Ritmos compuestos: pasión (F+E), sabiduría (E+I), maestría (F+E+I) | Aditivo |
| B8 | Exponer fase, pendiente y críticos en la API | Aditivo |

Verificación: phpunit por módulo; test de propiedad (pico en `t = P/4`); cruces de cero conocidos.

---

## Fase C — Valor nuevo (M)

| # | Tarea | Dependencias |
|---|---|---|
| C1 | **Disclaimer** corto y visible: herramienta de reflexión, sin validez predictiva demostrada | — (hacer primero) |
| C2 | Diario en `localStorage`: energía, ánimo y foco (1–5); exportar/importar JSON; "olvidar" | Backlog 2.1/2.3 |
| C3 | Correlación curva vs. diario (Pearson por ritmo, n mínimo, aviso de baja muestra) | C2 |
| C4 | `src/Numerology.php`: camino de vida, año/mes/día personal, números maestros | — |
| C5 | `src/Lunar.php`: fase lunar (sinódico 29.53 d), signo solar, zodiaco chino | — |
| C6 | Ritual con variantes rotativas, pendiente, críticos próximos y día personal | B1, B2, C4 |
| C7 | Compatibilidad con diferencia de fase y pesos por preset (trabajo → intelectual, pareja → emocional) | B1 |
| C8 | Calendario mensual con semáforo y exportar `.ics` de críticos | B2 |

Nota: la correlación (C3) puede dar "sin relación". Es un resultado válido; el texto debe presentarlo como tal.

---

## Fase D — Cronobiología y PWA (M)

| # | Tarea |
|---|---|
| D1 | Cronotipo: cuestionario corto (MEQ reducido) → ventana de pico cognitivo; guardado local |
| D2 | Bloques ultradianos de ~90 min en el ritual |
| D3 | PWA: `manifest.json`, iconos, service worker cache-first (backlog 2.4/2.5) |
| D4 | Persistir `birth` en localStorage + formulario de primera visita (backlog 2.1/2.2) |
| D5 | Aviso local de días críticos (Notifications API, sin backend) |

---

## Fase E — Opcional (L, decidir después)

Amanecer/atardecer con geolocalización; ritmos secundarios 38/43/48/53 d.

Descartado por decisión: efemérides (Swiss Ephemeris) y ciclo menstrual.

---

## Transversal

| # | Tarea | Cuándo |
|---|---|---|
| T1 | Privacidad: aviso de que `birth` va en la URL; evaluar `#fragment` para compartir | Tras A |
| T2 | Accesibilidad: resumen textual/tabla del SVG, patrones de línea para daltonismo | Tras A |
| T3 | Reconciliar `docs/BACKLOG.md` (hecho vs. solo decidido) | Ya |
| T4 | Tests de integración mínimos de la API (200/400, forma del JSON) en CI | Con A2 |
| T5 | php-fpm y cabeceras (CSP, `X-Frame-Options` salvo embed) | Solo si se publica |
| T6 | Trocear `index.php` (CSS/JS a archivos) | No ahora; solo si C/D lo hacen inmanejable |

---

## Orden de ejecución

```
0 → C1 → A1 → A4 → A6 → A2/A3/A7/A8 → B1 → B2 → B3/B4/B5 → B6/B7/B8
  → C2 → C3 → C4 → C5 → C6 → C7 → C8 → D1..D5 → (E bajo demanda)
```

Racional: primero lo que da resultados falsos (A); luego el modelo (B), del que dependen C y D; el diario (C2/C3) pronto, porque valida o descarta la utilidad del resto.

---

## Decisiones tomadas (2026-10-24)

1. **API:** se crea `/api/v1/` estricto (400 en entradas inválidas). `/api/` queda como legado sin cambios de comportamiento, con deprecation header.
2. **Entrega:** una fase por PR (una rama por fase, un commit por tarea).
3. **Astrología:** solo signo solar y fase lunar, sin dependencias. Descartadas las efemérides (Swiss Ephemeris).
4. **Ciclo menstrual:** fuera de alcance (no aplica al usuario).
