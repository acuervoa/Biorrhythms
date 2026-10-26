# Biorrhythms

App PHP para visualizar biorritmos personales: físico, emocional e intelectual.
Sin dependencias externas — no requiere Composer, Node ni PHP instalados en el host.

La app organiza el recorrido en cuatro espacios: **Lectura**, **Explorar**, **Diario** y
**Herramientas**. Configuras tu fecha de nacimiento una vez y consultas los tres valores
matemáticos para el día seleccionado. No son mediciones biológicas ni predicciones validadas.

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

### Dominio local con Avahi y Nginx

Avahi publica `biorrhythms.local` mediante mDNS y Nginx recibe HTTP en el puerto 80 y
reenvía las peticiones al contenedor local en `127.0.0.1:8002`. Instala y activa el
publicador de Avahi como servicio del usuario:

```bash
install -D -m 0644 deploy/systemd/biorrhythms-local-alias.service \
  "$HOME/.config/systemd/user/biorrhythms-local-alias.service"
systemctl --user daemon-reload
systemctl --user enable --now biorrhythms-local-alias.service
```

Comprueba si Nginx ya tiene un bloque para el dominio. Si falta, instala el virtual host
del repositorio una vez en el host:

```bash
sudo nginx -T 2>&1 | grep -F 'server_name biorrhythms.local'
# Solo si el comando anterior no muestra una configuración existente:
sudo install -D -m 0644 deploy/nginx/biorrhythms.local.conf /etc/nginx/conf.d/biorrhythms.local.conf
sudo nginx -t && sudo systemctl reload nginx
```

Comprueba la resolución con `getent ahostsv4 biorrhythms.local` y la ruta HTTP con
`curl -I http://biorrhythms.local/`. Avahi publica el nombre; el virtual host de Nginx
conecta el dominio con la aplicación. No añadas este alias a `/etc/hosts`.

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

### Lectura

El día seleccionado, sus tres valores y el gráfico forman el flujo principal. El gráfico SVG permite cambiar entre 7, 30, 60 y 90 días; el control de fechas recorre los días cercanos y mantiene el foco elegido en la URL. En la primera visita, la app pide la fecha de nacimiento y no muestra valores de ejemplo como si fueran personales.

Los ritmos físico (terracota), emocional (verde azulado) e intelectual (azul) se identifican por nombre y color. Una pregunta neutral invita a observar el día. La app aclara que son salidas de un modelo matemático, no mediciones ni predicciones validadas.

### Explorar

- El horizonte de eventos se puede acotar a 7, 30 o 90 días; empieza en 30. Picos, valles y cruces de cero se agrupan por fecha y ciclo.
- La lista de días extremos empieza con un resumen de 30 días y permite revelar el ciclo completo en bloques. Se calcula con los ciclos de 23, 28 y 33 días (su periodo común es 21 252 días, unos 58,2 años).
- El calendario mensual muestra la media de los tres ciclos y los días críticos. Descarga un archivo `.ics` con `/calendar.php?birth=…&from=…&days=90&min=1`.

### Diario

Registra energía, ánimo y claridad (1–5) por día. Los datos permanecen en `localStorage` de este navegador; se pueden exportar o importar como JSON y borrar desde el Diario. La tabla compara esos registros con cada curva mediante correlación de Pearson. Requiere al menos 14 registros por ritmo; la asociación no demuestra causa ni capacidad predictiva, y el intervalo de confianza es optimista porque los días consecutivos no son independientes.

### Herramientas

- **Comparar ciclos:** calcula el solapamiento matemático entre dos perfiles y permite exportar la comparación. No evalúa compatibilidad interpersonal.
- **Contexto simbólico:** muestra signo solar, numerología y fase lunar aproximada (±1 día); son marcos simbólicos, no evidencia empírica.
- **Cronotipo y horario:** ofrece una plantilla a partir de la hora de despertar y un cuestionario adaptado del rMEQ. No es el instrumento validado ni un diagnóstico; los bloques de 90 minutos tampoco se presentan como duración ideal demostrada.
- **Compartir e integrar:** crea una tarjeta PNG, un enlace a la Lectura, un iframe del widget y una vista previa de la API. Las preferencias y avisos se gestionan en el apartado «Perfil y preferencias».

El asistente de decisiones, la generación de rituales y Story mode ya no aparecen como flujos de la interfaz. Los campos de ritual y comparación se conservan en las respuestas de la API para mantener compatibilidad con consumidores existentes.

### Identidad visual y PWA

La interfaz usa papel cálido, tinta oscura y tipografía serif para fechas y valores. Incluye temas claro y oscuro, diseño adaptable, navegación por teclado y respeto por la preferencia de movimiento reducido. `manifest.webmanifest` y `sw.js` permiten instalar la app; `/api/` y `calendar.php` no pasan por el service worker. Al cambiar recursos precacheados, actualiza `VERSION` en `sw.js`.

---

## Estructura del proyecto

```
Biorrhythms/
├── index.php          # App principal (PHP y lógica de interacción)
├── assets/
│   ├── workspaces.css # Navegación, estructura y responsive
│   ├── workspaces.js  # Activación de los cuatro espacios
│   └── *.js           # Diario, perfil, cronotipo y avisos
├── views/             # Parciales PHP de cada espacio
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
