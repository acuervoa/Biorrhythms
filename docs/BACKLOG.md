# Biorrhythms — Backlog

> Fecha de creación: 2026-06-26
> Basado en v1.0.0

---

## Decisiones tomadas

| Feature | Decisión | Razón |
|---|---|---|
| localStorage para birth date | ✅ v1.2.0 | Elimina fricción de uso diario; tradeoff URL aceptado |
| PWA (instalar en home screen) | ✅ v1.2.0 | Útil en móvil; depende de que mobile responsive esté resuelto |
| Multi-persona (>2) | ❌ Descartado por ahora | Rediseño de UI/modelo demasiado grande para el scope actual |
| Dark/light mode automático | ✅ v1.1.0 | XS de complejidad, mejora la primera impresión |
| Notificaciones email/push | ❌ Descartado | Requiere backend persistente, fuera de scope |
| Export PDF | ❌ Descartado | Poco beneficio sobre PNG existente; añade dependencias |
| Historial de consultas | ❌ Descartado | App stateless por diseño; requeriría DB |

---

## Milestone v1.1.0 — Calidad

Objetivo: cero deuda técnica conocida, CI verde, app coherente en español y en móvil.

| # | Tarea | Complejidad | Dependencias |
|---|---|---|---|
| 1.1 | `src/Ritual.php` + tests | M | — |
| 1.2 | `src/Forecast.php` + tests | S | — |
| 1.3 | GitHub Actions — phpunit en push/PR | S | — |
| 1.4 | Fechas en español ("Lun 5 Nov") en PHP y JS | S | — |
| 1.5 | Verificar + fix embed iframe | S | — |
| 1.6 | Verificar + fix exportar PNG compatibilidad | S | — |
| 1.7 | Mobile responsive pass | M | — |
| 1.8 | Dark/light mode automático (`prefers-color-scheme`) | XS | — |

### Verificación v1.1.0
- `./vendor/bin/phpunit` → 100% verde
- GitHub Actions pasa en PR
- Todas las fechas visibles en castellano
- Iframe embebible carga correctamente
- Export PNG genera imagen sin errores
- Layout usable en 375px (iPhone SE)
- Dark/light cambia automáticamente según sistema y se puede sobreescribir con el toggle

---

## Milestone v1.2.0 — UX

Objetivo: app instalable y usable sin fricciones en uso personal diario.

| # | Tarea | Complejidad | Dependencias |
|---|---|---|---|
| 2.1 | localStorage: persistir `birth` entre visitas | M | — |
| 2.2 | Formulario de primera visita si no hay `birth` guardado | S | 2.1 |
| 2.3 | Botón "olvidar mis datos" (borrar localStorage) | XS | 2.1 |
| 2.4 | PWA: `manifest.json` + iconos | S | 1.7 (mobile) |
| 2.5 | PWA: service worker con estrategia cache-first para assets | M | 2.4 |

### Verificación v1.2.0
- Recargar la app sin parámetros de URL → carga la fecha guardada
- Primera visita sin datos → aparece el formulario de configuración
- "Olvidar mis datos" borra localStorage y vuelve al formulario
- En Chrome/Safari móvil → "Añadir a pantalla de inicio" disponible
- App instalada abre sin barra de navegador, carga offline los assets estáticos

---

## Complejidad estimada

| Nivel | Descripción |
|---|---|
| XS | < 1h — un cambio de pocas líneas |
| S | 1–3h — tarea concreta y acotada |
| M | 3–8h — requiere diseño + implementación + tests |
| L | > 8h — múltiples archivos, rediseño parcial |

---

## Fuera del backlog (no se implementa)

- Multi-persona (>2): rediseño mayor, se reevalúa en v2.0.0 si hay demanda
- Notificaciones email/push: requiere backend persistente
- Export PDF: sin beneficio claro sobre PNG
- Historial: app stateless por diseño, añadir DB rompe la simplicidad
