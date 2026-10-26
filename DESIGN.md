---
name: Biorrhythms
description: Lectura personal de los valores matemáticos de tres ciclos clásicos.
colors:
  background: "#f8f7ee"
  paper: "#f8f7ee"
  paper-muted: "#f4f0e7"
  surface: "#f8f5ed"
  stroke: "#d3cbc1"
  text: "#29231f"
  muted: "#716960"
  physical: "#b84d39"
  emotional: "#24776c"
  intellectual: "#4168a0"
  accent: "#9f4633"
  focus-ring: "#315d96"
  dark-background: "#202522"
  dark-paper: "#292f2b"
  dark-paper-muted: "#303733"
  dark-surface: "#2a302c"
  dark-stroke: "#505a52"
  dark-text: "#f2eee4"
  dark-muted: "#c0c1b8"
  dark-physical: "#e27a61"
  dark-emotional: "#69b8a5"
  dark-intellectual: "#86a9dc"
  dark-accent: "#efaa85"
  dark-focus-ring: "#a8c5f2"
typography:
  display:
    fontFamily: "Georgia, Times New Roman, serif"
    fontSize: "clamp(3.4rem, 6vw, 5.2rem)"
    fontWeight: 400
    lineHeight: 0.98
    letterSpacing: "-0.04em"
  body:
    fontFamily: "Avenir Next, Avenir, Segoe UI, sans-serif"
    fontSize: "16px"
    fontWeight: 400
    lineHeight: 1.5
  label:
    fontFamily: "Avenir Next, Avenir, Segoe UI, sans-serif"
    fontSize: "0.78rem"
    fontWeight: 700
    letterSpacing: "0.17em"
rounded:
  sheet: "10px"
  control: "7px"
  field: "6px"
  pill: "999px"
components:
  button-primary:
    backgroundColor: "{colors.text}"
    textColor: "{colors.paper}"
    rounded: "{rounded.control}"
    padding: "8px 12px"
    height: "40px"
  button-secondary:
    backgroundColor: "{colors.paper}"
    textColor: "{colors.text}"
    rounded: "{rounded.control}"
    padding: "8px 12px"
    height: "40px"
  input-date:
    backgroundColor: "{colors.paper}"
    textColor: "{colors.text}"
    rounded: "{rounded.field}"
    padding: "8px 10px"
    height: "42px"
---

# Design System: Biorrhythms

## Overview

**Creative North Star: “Bitácora de observación”**

La interfaz se lee como una página de una bitácora editorial: papel cálido, tinta oscura, márgenes generosos y una fecha protagonista. La serif Georgia aporta carácter a fechas, cifras y encabezados; la sans serif Avenir Next mantiene controles, etiquetas y explicaciones claros.

La lectura individual del día y el gráfico forman el recorrido principal. Los tres colores identifican ciclos de forma constante en cifras, leyenda y curvas; las herramientas adicionales quedan después de la lectura. El tema oscuro conserva la misma jerarquía con superficies y acentos propios.

**Key Characteristics:**

- Papel cálido con textura sutil en la hoja principal clara.
- Contraste editorial entre serif de lectura y sans serif de interfaz.
- Acentos terracota, verde azulado y azul asignados a los tres ciclos.
- Valores descritos como salidas matemáticas del modelo, no mediciones ni predicciones.

## Colors

La paleta clara combina papel marfil y tinta marrón carbón; el modo oscuro cambia a superficies verde carbón y aclara los tres acentos para conservar su identificación.

### Primary
- **Terracota físico** (`#b84d39`; oscuro `#e27a61`): identifica el ciclo físico, el estado seleccionado y acentos de navegación.

### Secondary
- **Verde azulado emocional** (`#24776c`; oscuro `#69b8a5`): identifica el ciclo emocional en cifras, leyenda y curvas.

### Tertiary
- **Azul intelectual** (`#4168a0`; oscuro `#86a9dc`): identifica el ciclo intelectual. El azul de foco (`#315d96`; oscuro `#a8c5f2`) se reserva para el foco visible.

### Neutral
- **Papel** (`#f8f7ee`): fondo claro y superficie principal.
- **Papel secundario** (`#f4f0e7`): superficies de apoyo en tema claro.
- **Superficie clara** (`#f8f5ed`): superficie auxiliar clara.
- **Borde claro** (`#d3cbc1`): contornos y divisores.
- **Tinta** (`#29231f`): texto principal y botones primarios.
- **Tinta secundaria** (`#716960`): texto auxiliar, etiquetas y ejes.
- **Fondo oscuro** (`#202522`), **papel oscuro** (`#292f2b`), **papel oscuro secundario** (`#303733`) y **superficie oscura** (`#2a302c`): capas del tema oscuro.
- **Borde oscuro** (`#505a52`), **texto oscuro** (`#f2eee4`) y **texto auxiliar oscuro** (`#c0c1b8`): estructura y texto del tema oscuro.
- **Acento claro** (`#9f4633`) y **acento oscuro** (`#efaa85`): acento general de la interfaz en cada tema.

### Named Rules
**The Cycle Color Rule.** Mantén terracota para físico, verde azulado para emocional y azul para intelectual; acompaña el color con nombre o etiqueta, nunca dependas solo del color.

## Typography

**Display Font:** Georgia (con Times New Roman y serif como fallback)

**Body Font:** Avenir Next (con Avenir, Segoe UI y sans-serif como fallback)

**Label/Mono Font:** Avenir Next; no hay una familia monoespaciada dedicada.

**Character:** La serif editorial da peso a la fecha seleccionada y a los valores numéricos. La sans serif sostiene lectura funcional en navegación, controles, notas y escalas del gráfico.

### Hierarchy
- **Display** (400, `clamp(3.4rem, 6vw, 5.2rem)`, line-height `0.98`): fecha seleccionada en escritorio; en móvil se reduce a `clamp(2.8rem, 11vw, 3.5rem)`.
- **Headline** (700, `clamp(1.65rem, 2.7vw, 2.75rem)`, line-height `1`): marca y títulos editoriales de primer nivel.
- **Title** (700, `1.25rem`, line-height normal): encabezado “Valores del modelo”; otros encabezados de sección escalan según el espacio.
- **Body** (400, `16px`, line-height `1.5`): texto de lectura y explicación; párrafos limitados a `72ch`.
- **Label** (700, `0.78rem`, letter-spacing `0.17em`, uppercase): día de semana y metadatos breves. Las etiquetas de ciclo usan tracking más moderado.

### Named Rules
**The Editorial Hierarchy Rule.** Reserva Georgia para fecha, marca, cifras y encabezados de lectura; usa la sans serif para controles, etiquetas y texto funcional.

## Layout

La aplicación se organiza en cuatro destinos de navegación: **Lectura**, **Explorar**, **Diario** y **Herramientas**. Cada destino tiene un encabezado identificable y se muestra como espacio independiente; los enlaces conservan fragmentos en la URL para restaurar la selección y enlazar directamente a módulos secundarios.

En escritorio, la navegación principal se mantiene junto al encabezado. A `820px` o menos, pasa a una barra fija inferior que respeta el `safe-area` del dispositivo y deja espacio al contenido. A `560px`, los formularios del diario se distribuyen en dos columnas y la tabla se desplaza dentro de su propio contenedor; el documento no debe adquirir scroll horizontal. El gráfico conserva el límite de cinco marcas temporales hasta `700px`.

## Elevation & Depth

El sistema combina bordes finos y fondos tonales con sombras suaves en hojas y tarjetas. La textura de papel aparece solo en la hoja principal clara. Los controles y paneles internos se mantienen planos para no competir con la lectura.

### Shadow Vocabulary
- **Welcome sheet** (`0 14px 36px rgba(47, 39, 31, 0.08)`): tarjeta de bienvenida inicial.
- **Reading cards** (`0 12px 28px rgba(47, 39, 31, 0.07)`): elevación discreta de tarjetas sobre el fondo.

### Named Rules
**The Quiet Surface Rule.** Usa bordes y cambios de tono para separar contenido; reserva las sombras para la hoja o tarjeta exterior.

## Shapes

La geometría es contenida y casi rectangular: hoja y tarjetas usan `10px`; controles, `7px`; campos, `6px`; chips de navegación secundaria, `999px`. Divisores de `1px` organizan encabezados y columnas. No redondees la hoja como una tarjeta flotante de app móvil.

## Components

### Buttons
- **Shape:** esquinas de `7px`; altura mínima `40px` y padding `8px 12px`.
- **Primary:** fondo tinta (`--text`), texto papel (`--paper`) y borde del mismo color; se usa para la acción principal de un formulario.
- **Hover / Focus:** hover cambia al tono de papel secundario en botones estándar; el primario usa `#403830`. El foco global usa contorno de `3px`, offset `3px`, con el color de foco del tema.
- **Secondary:** fondo papel, texto tinta y borde `--stroke`; hover usa papel secundario.

### Chips
- **Style:** enlaces de herramientas con fondo papel, borde `--stroke`, texto `--text`, radio de `999px`, altura mínima `38px` y padding `6px 10px`.
- **State:** hover cambia el borde a terracota. No presentes estas rutas secundarias con más peso que la lectura diaria.

### Cards / Containers
- **Corner Style:** radio `10px` en hojas y tarjetas.
- **Background:** papel principal; usa papel secundario para grupos internos.
- **Shadow Strategy:** sombra discreta en tarjeta exterior y sin sombra en filas o paneles anidados.
- **Border:** `1px solid --stroke`.
- **Internal Padding:** hoja principal `38px clamp(24px, 4.8vw, 72px)` en escritorio y `20px 16px` en móvil; paneles secundarios `22px 26px` en escritorio y `15px 13px` en móvil.

### Inputs / Fields
- **Style:** fondo `--paper`, texto `--text`, borde `--stroke`, radio `6px`, altura mínima `42px`, padding `8px 10px`.
- **Focus:** contorno visible de `3px` con offset de `3px`, usando `--focus-ring`.

### Navigation
- **Style:** la navegación principal es texto sans serif de `0.95rem`, con objetivos de al menos `44px` de alto y separación inferior de `2px`.
- **State:** activo y hover usan tinta y subrayado terracota; inactivo usa texto secundario.
- **Mobile:** hasta `820px`, presenta las cuatro rutas en una barra fija inferior con áreas táctiles de al menos `48px`; incluye `safe-area` y espacio inferior equivalente en el contenido.
- **Navigation behavior:** refleja el destino activo con `aria-current="page"`, actualiza el fragmento del URL y enfoca/desplaza el encabezado del destino. Los fragmentos heredados siguen llevando al espacio que contiene su módulo.

### Workspaces
- **Lectura:** fecha elegida, tres valores reales del modelo, gráfico y una pregunta de reflexión neutral.
- **Explorar:** eventos en horizonte de 7/30/90 días, calendario mensual y consulta progresiva de extremos del ciclo.
- **Diario:** registro y análisis de autoobservación guardados localmente en el navegador.
- **Herramientas:** comparación matemática de perfiles, cronotipo, contexto simbólico, preferencias, compartir y opciones de integración; los detalles se agrupan en disclosures.
- Mantén el lenguaje neutral: los ciclos no determinan compatibilidad, decisiones ni recomendaciones.

### Daily Reading
La fecha aparece en Georgia con cifras tabulares; los tres valores se presentan en una fila de tres columnas con separadores verticales y etiquetas con nombre, ciclo y acento de color correspondiente. Mantén visible que son “salidas matemáticas” y no mediciones. El gráfico debe conservar leyenda textual y etiquetas del eje legibles. La navegación a otro espacio enfoca su encabezado y desplaza el nuevo contenido a la vista; respeta `prefers-reduced-motion`.

## Do's and Don'ts

### Do:
- **Do** conserva el papel, la tinta y el mapeo cromático de los ciclos en claro y oscuro.
- **Do** presenta primero la lectura personal del día y su gráfico; deja las herramientas secundarias en un nivel posterior y localizable.
- **Do** usa cifras tabulares para fechas y porcentajes, y etiquetas visibles además del color.
- **Do** mantén foco visible y respeta `prefers-reduced-motion`.
- **Do** describe los porcentajes como valores del modelo o salidas matemáticas; conserva el lenguaje factual del producto.

### Don't:
- **Don't** describas los valores como energía medida, diagnóstico o predicción validada.
- **Don't** conviertas el marco de bitácora en una promesa de registro: la sugerencia invita a observar y no solicita una respuesta en el flujo principal.
- **Don't** uses cifras ilustrativas como si fueran datos del perfil.
- **Don't** dependas solo del color para identificar ciclos ni sacrifiques la legibilidad del eje del gráfico en móvil.
