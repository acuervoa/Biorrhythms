# Biorrhythms

App PHP para visualizar biorritmos personales: físico, emocional e intelectual.
Sin dependencias externas — no requiere Composer, Node ni PHP instalados en el host.

La lectura está pensada para una persona: configuras tu fecha de nacimiento una vez,
eliges el día que quieres observar y consultas los tres valores matemáticos del modelo.
No son mediciones biológicas ni predicciones validadas.

---

## Requisitos

- [Docker](https://docs.docker.com/get-docker/) y Docker Compose

---

## Instalación y arranque

```bash
git clone https://github.com/acuervoa/Biorrhythms.git
cd Biorrhythms
docker compose up
```

Abre en el navegador:

```
http://localhost:8002
```

La primera visita pide la fecha de nacimiento. Después, la pantalla principal abre la
lectura del día local y permite recorrer otras fechas desde el gráfico o el control de día.
El perfil se conserva solo en este navegador.

La guía de producto y del sistema visual está en [PRODUCT.md](PRODUCT.md) y
[DESIGN.md](DESIGN.md). El historial de cambios está en [CHANGELOG.md](CHANGELOG.md).

Para parar:

```bash
docker compose down
```

---

## Parámetros de URL

| Parámetro | Descripción | Ejemplo |
|---|---|---|
| `birth` | Fecha de nacimiento (YYYY-MM-DD) | `1972-09-19` |
| `focus` | Fecha foco / día a analizar | `2026-06-26` |
| `partner_birth` | Fecha de nacimiento de la otra persona (compatibilidad) | `1971-05-30` |
| `extreme` | Umbral de «días extremos» (0.5–0.99, por defecto 0.95) | `0.8` |

Ejemplo completo:

```
http://localhost:8002/?birth=1972-09-19&focus=2026-06-26&partner_birth=1971-05-30
```

---

## Rutas

| Ruta | Descripción |
|---|---|
| `/` | App principal |
| `/api/v1/` | Endpoint JSON estricto (recomendado) |
| `/api/` | Endpoint JSON legado (obsoleto) |

### API

Hay dos endpoints:

| Endpoint | Estado | Comportamiento |
|---|---|---|
| `/api/v1/` | Recomendado | Estricto: fechas inválidas o ausentes devuelven **400** con un JSON de error. `birth` es obligatorio; `focus` por defecto es hoy; `partner_birth` es opcional (sin él `compatibility` es `null`). Acepta `preset` (`pair`, `friend`, `work`, `custom`). `profile.key` es estable (`physical`, `emotional`, `intellectual`) y `profile.label` está en español. |
| `/api/` | Obsoleto (`Deprecation: true`) | Sin cambios de contrato: las fechas inválidas o ausentes caen a valores por defecto sin avisar (`birth=1990-01-01`, `partner_birth=1991-01-01`) y el label va en inglés. |

```bash
curl "http://localhost:8002/api/v1/?birth=1972-09-19&focus=2026-06-26&pretty=1"

# Error de validación
curl -i "http://localhost:8002/api/v1/?birth=2026-02-31"
# HTTP/1.1 400 Bad Request
# {"error":{"code":"invalid_date","field":"birth","message":"..."}}
```

Fechas válidas: formato `YYYY-MM-DD`, entre `1900-01-01` y `2100-12-31`. Códigos de error: `missing_parameter`, `invalid_date`, `invalid_value`.

Campos añadidos en `/api/v1/` (el legado no los incluye):

| Campo | Contenido |
|---|---|
| `profile.phases` | Por ritmo: `period`, `day_in_cycle`, `value`, `slope` y `state` (`peak`, `valley`, `rising`, `falling`) |
| `profile.strongest` | Ritmo de mayor magnitud con signo (`polarity`: `high`/`low`). `profile.key` sigue siendo el de mayor valor |
| `profile.conflict` | `true` si un ritmo está ≥ +50% y otro ≤ −50% (la media puede ocultarlo) |
| `profile.all_negative`, `profile.spread` | Los tres ritmos negativos; diferencia entre el mayor y el menor |
| `profile.composites` | `passion` (físico+emocional), `wisdom` (emocional+intelectual), `mastery` (los tres) |
| `critical_days` | Cruces por cero de hoy a +45 días; `kind`: `single`, `double` o `triple` |
| `extreme_days` | Solo si se pasa `extreme`; incluye `threshold` y la lista de días |

Un cruce por cero se asigna al día cuya ventana [d−0.5, d+0.5) lo contiene (los de periodo impar, 23 y 33, caen en medios días y se redondean hacia arriba).

Devuelve un JSON con: ventana de 90 días, scores por ritmo, forecast 7 días, ritual diario, compatibilidad y metadatos.

---

## Qué hace la app

### Cálculo de biorritmos

Tres ritmos sinusoidales calculados desde la fecha de nacimiento:

| Ritmo | Período | Color |
|---|---|---|
| Físico | 23 días | Terracota |
| Emocional | 28 días | Verde azulado |
| Intelectual | 33 días | Azul |

Fórmula: `sin(2π × días_desde_nacimiento / período)`

El valor oscila entre −1 (−100%) y +1 (+100%).

### Widgets disponibles

**Timeline interactiva**
Gráfico SVG de los tres ritmos. Zoom configurable: 7, 30, 60 o 90 días (por defecto 90). El marcador identifica el día seleccionado, que puede ser distinto de hoy. En pantallas estrechas reduce las marcas temporales para que las fechas sigan legibles.

**Lectura diaria**
La fecha elegida, los tres valores calculados y el gráfico forman el recorrido principal. Los ciclos se identifican siempre con color y nombre: físico (terracota), emocional (verde azulado) e intelectual (azul). La lectura conserva la aclaración de que son salidas matemáticas, no mediciones.

**Identidad visual**
La interfaz usa una hoja editorial de papel cálido, tinta oscura y tipografía serif para fechas y valores. Incluye tema claro y oscuro, diseño responsive y primera visita sin datos de ejemplo.

**Decisión diaria**
Calendario de 7 días con colores por score medio. Ritual accionable adaptado al ritmo dominante del día.

**Días especiales**
Picos, valles y cruces de cero de cada ritmo en la ventana de 90 días, agrupados por ritmo (Físico / Emocional / Intelectual). Solo muestra fechas desde el día foco en adelante.

**Días extremos**
Fechas futuras donde la media de los 3 ritmos supera el +95% o cae por debajo del −95%, calculadas sobre el ciclo completo (~58 años, LCM de 23/28/33 = 21 252 días).

**Modo compatibilidad**
Compara los biorritmos de dos personas. Muestra score de compatibilidad diaria, heatmap semanal y desglose por ritmo con barras en el color de cada ritmo.

**Contexto del día**
Signo solar (con aviso de cúspide), camino de vida, día personal numerológico y fase lunar aproximada (±1 día) para la fecha de foco. Es un marco simbólico, no evidencia empírica.

**Calendario mensual**
Cuadrícula del mes con la media diaria (verde, ámbar, rojo, y el porcentaje en texto) y los días críticos simples, dobles y triples. Descarga `.ics` (`/calendar.php?birth=…&from=…&days=90&min=1`) para llevar los días críticos a tu calendario.

**Diario de autoobservación**
Registro diario de energía, ánimo y claridad (1–5) guardado solo en el navegador (`localStorage`), con exportar/importar JSON y «olvidar mis datos». Calcula la correlación de Pearson con cada curva (mínimo 14 registros) con su intervalo de confianza al 95 %, y avisa cuando no hay relación o los datos son constantes.

**Cronotipo y ritmo del día**
Con tu hora de despertar propone una plantilla orientativa (luz, ventanas de foco, valle postprandial, último café, bajar luces, acostarse) y bloques de trabajo de 90 min. Incluye un cuestionario corto de cronotipo: es una adaptación propia inspirada en el rMEQ (Adan y Almirall, 1991), con su mismo rango (4–25) y tres grupos, pero **no** es el instrumento validado ni un diagnóstico.

**Datos guardados y avisos**
La fecha de nacimiento, el cronotipo, la hora de despertar y las preferencias se guardan solo en el navegador (`localStorage`). Una primera visita muestra un formulario de bienvenida; las siguientes recuperan tu fecha sin parámetros de URL. «Olvidar mi fecha y preferencias» lo borra todo. Los avisos de días críticos usan la API de notificaciones del navegador y **solo aparecen al abrir la app** el día crítico o la víspera (no hay servidor que envíe avisos en segundo plano).

**App instalable (PWA)**
`manifest.webmanifest` y `sw.js`: se puede añadir a la pantalla de inicio. Los scripts e iconos se precachean; las páginas van «red primero» (la lectura es siempre del día actual) y, sin conexión, se sirve la última copia de esa página o un aviso de «Sin conexión». `/api/` y `calendar.php` nunca pasan por el service worker. Si cambias un archivo precacheado, sube `VERSION` en `sw.js`.

**Tarjeta compartible**
Genera una imagen PNG con el resumen del día, exportable.

**Widget embebible**
Snippet de iframe para incrustar la vista compacta en otra página.

---

## Estructura del proyecto

```
Biorrhythms/
├── index.php          # App principal (PHP + CSS + JS en un solo archivo)
├── api/
│   └── index.php      # Endpoint JSON
├── src/
│   └── *.php            # Lógica de cálculo, fechas, API (sin dependencias)
├── api/v1/index.php   # Endpoint JSON estricto
├── docker-compose.yml
└── README.md
```

---

## Notas técnicas

- Tests: `docker run --rm -v "$PWD":/app -w /app php:8.3-cli-alpine php vendor/bin/phpunit` (PHP) y `node --test tests/js/*.test.js` (diario)
- PHP 8.3 sobre Alpine Linux (imagen `php:8.3-cli-alpine`)
- Servidor de desarrollo integrado: `php -S 0.0.0.0:8002`
- No apto para producción sin un servidor web real (nginx/apache) delante
- El ciclo completo de los 3 ritmos se repite cada **21 252 días** (~58,2 años)
