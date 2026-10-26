# Biorrhythms: nueva identidad y flujo de lectura

## Estado

Spec para revisión. Todavía no autoriza cambios de interfaz.

## Objetivo

Renovar la interfaz de Biorrhythms para que una persona que llega por primera vez pueda configurar su fecha una sola vez y entender enseguida la lectura matemática del día elegido. Después podrá recorrer el gráfico y encontrar las herramientas secundarias sin que compitan con esa tarea. La interfaz seguirá siendo una aplicación en español, personal y local al navegador.

## Verdad del producto

- Biorrhythms calcula tres ciclos sinusoidales clásicos: físico (23 días), emocional (28 días) e intelectual (33 días).
- Sus porcentajes son salidas matemáticas del modelo; no miden energía biológica ni ofrecen predicciones validadas. La experiencia invita a la reflexión personal y desalienta su uso para decisiones importantes.
- La persona usa la aplicación para consultar su propia lectura. Compatibilidad es un uso secundario.
- La fecha de nacimiento, las preferencias y el diario permanecen en el navegador. No hay cuenta ni perfil guardado en servidor.
- Astrología, numerología y contexto lunar son opcionales y se identifican como simbólicos, separados del cálculo de biorritmos.
- Se conserva el nombre Biorrhythms y el idioma español.

## Dirección visual aprobada

**Bitácora de observación**, representada por `.impeccable/mocks/decision/model-pick.png` y medida en `.impeccable/build/spec.json`.

La composición se apoya en una hoja editorial clara con margen de fecha: cabecera compacta, índice de días, fecha seleccionada dominante, tres valores del modelo con colores estables, gráfico temporal amplio y una sugerencia neutral de observación. La paleta usa tinta carbón y papel cálido con terracota para físico (`#B84D39`), verde azulado para emocional (`#24776C`) y azul para intelectual (`#4168A0`). La fecha y los encabezados emplean una voz serif editorial; los controles y datos priorizan legibilidad y cifras tabulares.

La referencia no se literaliza como imagen: no se rasteriza texto ni controles, no se introduce textura que el comp excluye, y el gráfico sigue dibujándose a partir de datos. No se añade campo de escritura, cuenta, símbolos astrológicos, promesas predictivas ni un gesto de diario ficticio. El comp muestra valores de ejemplo; en producción se deben usar los valores reales del perfil, nunca los porcentajes ilustrativos `+42%`, `−18%`, `+76%` como datos del usuario.

La jerarquía nueva se extiende al resto de la página: la lectura y el gráfico forman el recorrido principal. Compatibilidad, calendario, diario, cronotipo, contexto simbólico, exportación, widget e integración/API siguen disponibles bajo una navegación clara de herramientas secundarias. La metáfora editorial no debe ocultar controles, estados ni etiquetas exactas.

## Problemas prioritarios que resolver

1. **Alta duplicada en primera visita (P1):** mostrar un único formulario de fecha de nacimiento; no presentar a la vez el formulario de bienvenida y los controles persistentes. Tras guardar localmente, abrir la lectura personal y permitir cambiar esa fecha desde un único punto claro.
2. **Lectura y utilidades compiten (P1):** ordenar primero lectura diaria y gráfico; mover compatibilidad, calendario, diario y demás herramientas a navegación/secciones secundarias, sin eliminar capacidades ni ocultarlas sin una ruta reconocible.
3. **Demasiadas decisiones en el primer viewport (P1):** priorizar fecha, valores del modelo y una acción principal. El pronóstico, explicaciones extensas y controles de exploración no deben desplazar la lectura.
4. **Controles alejados del gráfico (P2):** mantener navegación de fecha, foco y zoom junto al gráfico y su lectura temporal, agrupados por tarea.
5. **Overflow señalado en compatibilidad (P2, por confirmar):** inspeccionar en viewport móvil real/emulado correctamente y corregir solo si se reproduce. El informe anterior detectó `innerWidth=1044` al declarar 390 px; la captura no demuestra por sí sola un fallo móvil.

## Flujo principal

### Primera visita

1. Presentar una explicación breve y neutral de qué calcula el modelo, seguida de un solo campo obligatorio de fecha de nacimiento.
2. Validar con las reglas existentes y mostrar recuperación clara para fechas inválidas. No presentar datos de muestra como personales ni mostrar simultáneamente un segundo campo de nacimiento.
3. Al enviar, conservar el comportamiento local existente, guardar la fecha en el navegador y abrir la lectura del día local actual.

### Visita posterior

1. Abrir directamente la lectura del día actual del perfil local existente.
2. Mostrar fecha seleccionada y tres porcentajes reales, siempre marcados como valores del modelo.
3. Permitir pasar al día anterior/siguiente y volver al día actual; reflejar la selección en el gráfico y en la URL según el comportamiento actual.
4. Mostrar el gráfico de 90 días como continuación inmediata de la lectura, con periodos alternativos y exportación agrupados con sus controles.
5. Ofrecer una sugerencia reflexiva breve, sin pedir ni guardar una respuesta en ese flujo.
6. Desde una navegación secundaria accesible, llegar a herramientas preservadas y regresar a Lectura.

## Requisitos de interacción y responsive

- En escritorio, el índice de fechas y la hoja central deben mantener relación visual con la fecha actual; el gráfico ocupa el ancho principal de lectura.
- En móvil, eliminar el raíl lateral cuando no haya espacio y convertir el selector de fecha en una secuencia compacta; valores, gráfico y controles deben caber sin recortes ni scroll horizontal de la página.
- Los controles deben ser semánticos, etiquetados, navegables por teclado y mostrar foco visible. Mantener soporte para movimiento reducido.
- Conservar estados de validación, carga y error existentes; los mensajes identifican el problema y la recuperación.
- Mantener contraste legible con la paleta elegida. No depender solo del color para distinguir los tres ciclos.
- No añadir dependencias de frontend ni cambiar el runtime PHP como parte del rediseño.

## Límites y compatibilidad

El cambio es de presentación y flujo. Debe preservar las fórmulas y semántica del modelo, rutas API, parámetros URL (`birth`, `focus`, `partner_birth`, `preset`, `embed`, `view`, `theme` y demás existentes), exportaciones, PWA, widget, almacenamiento local, diario y todos los cálculos secundarios. No se cambia el contrato de datos ni se mueve información personal al servidor.

Se mantiene el stack actual PHP 8.2+ y JavaScript/CSS del proyecto. El punto de entrada principal es `index.php`; cualquier separación de estilos se justifica solo si reduce claramente el riesgo del diff y respeta la estructura actual.

## No objetivos

- Reescribir cálculos, API, almacenamiento o persistencia.
- Añadir cuentas, sincronización, telemetría, generación predictiva o recomendaciones con consecuencias.
- Quitar funciones secundarias.
- Cambiar el nombre del producto o convertirlo en una app de astrología/medicina.
- Tratar como confirmado un defecto responsive cuya captura previa tenía un viewport incoherente.

## Criterios de aceptación

- Primera visita presenta exactamente un punto de captura para fecha de nacimiento; al completarlo se muestra una lectura personal sin valores ilustrativos.
- En segundos, una persona sin experiencia puede identificar la fecha, los tres valores, que son resultados de un modelo matemático y cómo avanzar de día.
- El gráfico, selector de fecha, zoom y volver al día foco forman un conjunto claro.
- Las herramientas secundarias siguen siendo localizables y funcionales, y la vuelta a Lectura es obvia.
- La vista funciona en desktop y móvil sin overflow horizontal ni controles recortados; compatibilidad se verifica con un viewport móvil válido.
- Se conservan query params, rutas, cálculo, almacenamiento local y exportación/widget/PWA.
- Capturas desktop y móvil permiten revisar la identidad y la jerarquía de lectura.

## Base observada

La crítica está en `.impeccable/critique/2026-10-24T16-54-09Z__index-php.md` (20/40; tres hallazgos P1 y dos P2). El punto de entrada actual es `index.php`, con CSS embebido y una página extensa de secciones. La primera visita muestra un formulario de bienvenida además de `controls-bar`, que también contiene `birth`. La captura de compatibilidad reportada no es evidencia concluyente de viewport móvil porque su ancho interior fue 1044 px.
