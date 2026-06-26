# Biorrhythms

App PHP para visualizar biorritmos personales: físico, emocional e intelectual.
Sin dependencias externas — no requiere Composer, Node ni PHP instalados en el host.

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

Ejemplo completo:

```
http://localhost:8002/?birth=1972-09-19&focus=2026-06-26&partner_birth=1971-05-30
```

---

## Rutas

| Ruta | Descripción |
|---|---|
| `/` | App principal |
| `/api/` | Endpoint JSON (mismos parámetros de URL) |

### API

```bash
curl "http://localhost:8002/api/?birth=1972-09-19&focus=2026-06-26&pretty=1"
```

Devuelve un JSON con: ventana de 90 días, scores por ritmo, forecast 7 días, ritual diario, compatibilidad y metadatos.

---

## Qué hace la app

### Cálculo de biorritmos

Tres ritmos sinusoidales calculados desde la fecha de nacimiento:

| Ritmo | Período | Color |
|---|---|---|
| Físico | 23 días | Rojo |
| Emocional | 28 días | Verde |
| Intelectual | 33 días | Azul |

Fórmula: `sin(2π × días_desde_nacimiento / período)`

El valor oscila entre −1 (−100%) y +1 (+100%).

### Widgets disponibles

**Timeline interactiva**
Gráfico SVG de los 3 ritmos. Zoom configurable: 1 semana, 30, 60 o 90 días (por defecto 90D). Slider de «Vista rápida» para navegar día a día. Marcador «Hoy» siempre visible.

**Hero del día**
Scores instantáneos de cada ritmo para el día foco. Chips con el mejor y peor día de la semana siguiente.

**Decisión diaria**
Calendario de 7 días con colores por score medio. Ritual accionable adaptado al ritmo dominante del día.

**Días especiales**
Picos, valles y cruces de cero de cada ritmo en la ventana de 90 días, agrupados por ritmo (Físico / Emocional / Intelectual). Solo muestra fechas desde el día foco en adelante.

**Días extremos**
Fechas futuras donde la media de los 3 ritmos supera el +95% o cae por debajo del −95%, calculadas sobre el ciclo completo (~58 años, LCM de 23/28/33 = 21 252 días).

**Modo compatibilidad**
Compara los biorritmos de dos personas. Muestra score de compatibilidad diaria, heatmap semanal y desglose por ritmo con barras en el color de cada ritmo.

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
│   └── Biorrhythms.php  # Lógica de cálculo (sin dependencias)
├── docker-compose.yml
└── README.md
```

---

## Notas técnicas

- PHP 8.3 sobre Alpine Linux (imagen `php:8.3-cli-alpine`)
- Servidor de desarrollo integrado: `php -S 0.0.0.0:8002`
- No apto para producción sin un servidor web real (nginx/apache) delante
- El ciclo completo de los 3 ritmos se repite cada **21 252 días** (~58,2 años)
