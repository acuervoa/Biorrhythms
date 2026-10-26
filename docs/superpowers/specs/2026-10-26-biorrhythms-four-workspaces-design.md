# Biorrhythms: cuatro espacios para una lectura serena

## Estado

Especificación para revisión del usuario. Aprobada en conversación la dirección de cuatro espacios; esta especificación concreta su contenido y las decisiones de producto. No autoriza todavía la implementación.

## Objetivo

Transformar Biorrhythms de una página larga de módulos en una aplicación con cuatro espacios explícitos —Lectura, Explorar, Diario y Herramientas—. La persona debe poder entender su lectura diaria sin recorrer contenido secundario, y llegar a las demás capacidades desde una navegación predecible.

La reorganización mantendrá una identidad editorial de «Bitácora de observación», eliminará el encuadre de recomendación predictiva y reducirá la cantidad de información simultánea. El resultado debe sentirse deliberado, tranquilo y personal, no como un dashboard que acumula funciones.

## Contexto y evidencia

- `PRODUCT.md` define como propósito comprender los tres valores del modelo para un día elegido y explorar patrones como ejercicio de reflexión. La compatibilidad es secundaria; los datos personales son locales.
- La crítica de la instancia local (`.impeccable/critique/2026-10-26T10-33-48Z__127-0-0-1.md`) puntuó 23/40. Observó una lectura inicial clara, seguida de una página larga con muchos módulos de jerarquía similar, un panel «Vista rápida» aparentemente vacío y lenguaje de recomendaciones que excede la evidencia del modelo.
- El punto de entrada actual es `index.php`: concentra markup, estilos e interacción de Lectura, eventos, días extremos, compatibilidad, asistente, ritual, contexto simbólico, calendario, diario, cronotipo, preferencias, Story mode, compartir, widget y API.
- La dirección visual editorial ya aprobada vive en `docs/superpowers/specs/2026-10-25-biorrhythms-visual-redesign-design.md`. Esta propuesta mantiene esa dirección; reorganiza la arquitectura de información y ajusta el lenguaje del producto.

## Verdad del producto

- Biorrhythms calcula tres ciclos sinusoidales clásicos: físico (23 días), emocional (28 días) e intelectual (33 días).
- Los porcentajes son resultados matemáticos de ese modelo; no son mediciones de energía, diagnósticos ni predicciones validadas.
- La tarea primaria es consultar la lectura propia de una fecha y explorar el gráfico.
- Las fechas de nacimiento, preferencias y entradas del diario permanecen en el navegador. No se añade cuenta ni perfil de servidor.
- El idioma sigue siendo español. La identidad sigue siendo Biorrhythms y la dirección visual, Bitácora de observación.

## Principios de experiencia

1. **Una intención por espacio.** Cada sección responde a una pregunta diferente y no mezcla herramientas de otros espacios.
2. **Lectura primero.** Una persona nueva entiende fecha, valores y naturaleza del modelo antes de encontrar análisis secundarios.
3. **Menos información simultánea.** El contenido extenso se resume y se revela por elección; no se eliminan opciones a escondidas.
4. **Sin recomendaciones basadas en un modelo no validado.** La app invita a observar y registrar, no a decidir por la persona.
5. **Datos personales bajo control local.** Exportar, importar y borrar datos siguen siendo acciones explícitas y comprensibles.
6. **Navegación estable y accesible.** Los cuatro espacios son localizables desde cualquier vista, con nombre visible, foco y estado actual semántico.

## Arquitectura de información

### 1. Lectura — espacio inicial

Responde: **«¿Qué valores calcula el modelo para la fecha que elegí?»**

Contiene únicamente:

- Fecha de foco y controles de día anterior, siguiente y volver a hoy.
- Tres valores del modelo con nombres, colores estables y una etiqueta visible: «resultado matemático, no medición ni predicción».
- Gráfico temporal principal y sus controles de rango/exportación, agrupados junto al gráfico.
- Una explicación breve de cómo leer el eje y los valores.
- Una pregunta opcional de autoobservación, neutral y no derivada como instrucción del score.
- Un enlace de contexto a las limitaciones del modelo, visible sin abrir Herramientas.

No presenta la fecha de otra persona, puntuaciones de compatibilidad, listas extensas de eventos, «mejor/peor día», agenda de acciones, rituales ni módulos simbólicos. La fecha de nacimiento se configura una vez en primera visita; en visitas posteriores se puede cambiar desde Datos personales en Herramientas. En Lectura puede existir un enlace discreto a ese ajuste.

La primera visita contiene un solo campo de fecha de nacimiento, una explicación breve y una acción para continuar. No muestra valores de ejemplo como si pertenecieran a la persona.

### 2. Explorar

Responde: **«¿Cómo se distribuyen estos ciclos a lo largo del tiempo?»**

Agrupa calendario mensual, eventos de ciclos, cruces por cero y extremos. La vista inicial usa un horizonte práctico alrededor de la fecha seleccionada (por defecto, los próximos 30 días) y una jerarquía de resumen, no una cuadrícula de decenas de tarjetas.

La persona puede cambiar explícitamente el horizonte (por ejemplo, 7/30/90 días) y pedir el ciclo completo. El listado completo nunca se despliega de forma automática. Los eventos se agrupan por fecha o tipo de ciclo y cada elemento explica si es pico, valle o cruce; el color no es la única señal.

La exportación de calendario `.ics` vive junto al calendario. El gráfico de Lectura conserva su ventana principal y no se duplica aquí como una segunda hero.

### 3. Diario

Responde: **«¿Qué observé y qué relación veo con el modelo en mis propios registros?»**

Contiene el formulario de registro, historial, correlaciones y acciones de importar, exportar y borrar. Explica que la correlación no demuestra causalidad ni valida los biorritmos como predictor. Indica cuántos registros se necesitan antes de mostrar análisis y ofrece un estado claro cuando no hay suficientes datos o son constantes.

Las entradas y operaciones permanecen en `localStorage`; la reorganización no cambia esquema ni migra datos al servidor.

### 4. Herramientas

Responde: **«¿Qué funciones complementarias están disponibles?»**

Se divide en grupos explícitos, con descripciones breves:

- **Comparar ciclos:** reemplaza el rótulo «Modo compatibilidad» y sugiere comparación matemática de patrones, no ajuste personal o consejo sobre una relación. Puede conservar internamente el cálculo y los parámetros existentes. En la interfaz, todo score se denomina y explica como similitud/solapamiento matemático, nunca como probabilidad de compatibilidad humana.
- **Cronotipo y horario:** conserva su carácter orientativo y declara junto al resultado que no es diagnóstico ni el cuestionario rMEQ validado.
- **Contexto simbólico:** astrología, numerología y fase lunar quedan agrupadas en un módulo opcional, etiquetado como simbólico y separado visualmente de los valores calculados.
- **Compartir e integrar:** tarjeta PNG, copiar resumen/enlace, widget y documentación/preview de API. Las funciones de desarrollo no se presentan como tareas principales para una persona nueva.
- **Datos personales y preferencias:** nacimiento, tema, avisos y borrado de preferencias, con efectos de cada acción claros.

### Disposición de funciones actuales

| Función | Nuevo destino o decisión |
|---|---|
| Valores, fecha, gráfico y zoom | Lectura |
| Días especiales, extremos y calendario | Explorar; resumen acotado y detalle progresivo |
| Diario y correlación | Diario |
| Compatibilidad | Herramientas → Comparar ciclos; cambiar encuadre y copy |
| Cronotipo y horario | Herramientas → Cronotipo y horario |
| Astrología, numerología y fase lunar | Herramientas → Contexto simbólico, opcional |
| PNG, enlace y tarjeta compartible | Herramientas → Compartir; atajo contextual solo si cabe sin competir |
| Widget y API | Herramientas → Compartir e integrar |
| Tema, avisos y perfil local | Herramientas → Datos y preferencias |
| Asistente de decisiones | Retirar como función independiente; no generar recomendaciones del modelo |
| Ritual diario | Retirar como función independiente; sustituir, si aporta valor, por una sola pregunta neutral de autoobservación en Lectura |
| Story mode | Retirar del recorrido principal por duplicar la lectura; mantener la tarjeta PNG como salida compartible |

No se elimina ninguna ruta pública, cálculo API ni dato almacenado por estas decisiones de interfaz. La retirada de una función visual no autoriza a cambiar contratos existentes.

## Navegación y comportamiento

- Navegación persistente de cuatro elementos con los nombres **Lectura**, **Explorar**, **Diario** y **Herramientas**. En desktop se integra en la cabecera; en móvil se presenta como barra inferior con texto e icono, área táctil suficiente y respeto al safe area.
- Lectura es la vista predeterminada tanto en primera visita como al abrir la aplicación sin una vista solicitada.
- Cada vista tiene un título visible, una frase que explica su propósito y una acción primaria como máximo.
- Activar otra vista lleva el foco al encabezado de esa vista, actualiza el indicador accesible de navegación y evita dejar el scroll en una posición inesperada.
- La vista activa se representa en el fragmento de URL (`#lectura`, `#explorar`, `#diario`, `#herramientas`) para permitir enlaces y back/forward sin introducir un nuevo parámetro de query.
- Se conservan los enlaces actuales a IDs de secciones secundarias: `#monthCalendar` abre Explorar; `#diary` abre Diario; compatibilidad, cronotipo, contexto, compartir, widget, API y preferencias abren Herramientas y enfocan su encabezado correspondiente. Se confirma el mapa completo durante la implementación.
- Los parámetros de query ya existentes no cambian. `view=widget` continúa significando exclusivamente el modo widget.
- La navegación debe funcionar con teclado. Si JavaScript no está disponible, el contenido de las cuatro áreas debe seguir siendo accesible como página larga con enlaces a sus anclas; el comportamiento tabulado se activa solo cuando el controlador de vistas está listo.

## Dirección visual y tono

- Mantener la identidad de Bitácora de observación existente: papel claro, tinta legible, jerarquía editorial y colores estables por ciclo.
- Reducir bordes, pills, tarjetas repetidas, cajas de estado y adornos con la misma prioridad. Reservar superficies destacadas para la lectura actual y el gráfico.
- Usar estructura, espacio, títulos y separadores antes de añadir más componentes o color.
- No usar verde/rojo, «mejor/peor», «valle» como instrucción, o badges de compatibilidad para insinuar que el modelo evalúa la capacidad de una persona.
- El copy es breve y concreto; los conceptos simbólicos y límites científicos se explican donde se presentan.
- Los estados vacíos son intencionales, con una frase que explique por qué no hay datos y cuál es el siguiente paso. No se muestran contenedores vacíos.

## Arquitectura técnica

- Mantener PHP server-rendered, JavaScript y CSS nativos, Docker y el runtime actual. No introducir framework ni dependencia.
- Separar markup por responsabilidad en vistas PHP: `views/reading.php`, `views/explore.php`, `views/diary.php` y `views/tools.php`; la cabecera y navegación permanecen en un shell compartido. `index.php` conserva cálculo, preparación de datos, query handling y punto de composición.
- Mantener los IDs DOM que consumen los controladores actuales mientras se mueve markup. No trasladar cálculos a las vistas ni duplicar DOM interactivo con IDs repetidos.
- Añadir un controlador acotado para seleccionar vista, sincronizar fragmento, gestionar foco y mapear los anclajes heredados. El controlador no modifica datos de perfil ni cálculos.
- Los módulos que consumen los datos iniciales siguen montados una vez para no duplicar solicitudes/estado; solo la vista activa es visible y accesible. Si el contenido oculto causa efectos secundarios o trabajo innecesario, se pospone renderizado únicamente tras demostrarlo y sin romper sus contratos.
- Extraer estilos específicos del shell y navegación a `assets/workspaces.css`; el resto de estilos puede permanecer en su lugar durante este cambio para evitar una reescritura general ajena al objetivo.
- Evitar cambios en endpoints API, clases de cálculo, persistencia local, esquema diario, `sw.js`, manifest y `docker-compose.yml`, salvo que una verificación revele un bloqueo concreto de navegación o caché.

## Contratos y datos que se preservan

- Fórmulas y semántica de valores de los tres ciclos.
- Parámetros de URL actuales: `birth`, `focus`, `partner_birth`, `preset`, `embed`, `view`, `theme` y el resto reconocido por la implementación.
- `view=widget` y el modo embed.
- Rutas `/api/`, `/api/v1/`, campos, códigos HTTP y semántica de respuesta.
- Persistencia local de fecha, preferencias, cronotipo y diario; operaciones existentes de importación/exportación/borrado.
- Exportación PNG, calendario `.ics`, widget, PWA, avisos y URL compartidas.
- Idioma español, sin cuentas, telemetría, sincronización en servidor ni migración de datos.

## Responsive y accesibilidad

- Validar a 320, 390, 768 y 1440 px, y en orientación horizontal móvil. La navegación no provoca overflow; la barra inferior no tapa controles ni contenido y admite safe area.
- La vista Lectura muestra fecha y tres valores completos en el primer viewport móvil; el gráfico empieza inmediatamente después y no obliga a atravesar módulos secundarios.
- No hay scroll horizontal de página, valores recortados, controles inaccesibles ni listas enormes sin paginación/revelado explícito.
- Navegación y controles funcionan por teclado; el cambio de vista anuncia su título, el foco no queda dentro de una vista oculta y los estados usan semántica accesible.
- Contraste de texto y foco suficientes; color nunca es el único medio para identificar un ciclo o un estado.
- Se respeta `prefers-reduced-motion`; las transiciones entre vistas no dependen de animación.

## Criterios de aceptación

1. Hay exactamente cuatro destinos principales, nombrados y accesibles desde cualquier vista; Lectura es el destino inicial.
2. Una primera visita presenta una sola captura de nacimiento y no muestra datos de ejemplo como personales.
3. En una visita configurada, fecha, tres resultados del modelo, explicación neutral y gráfico son la experiencia principal; no aparecen en Lectura compatibilidad, asistente, ritual, Story mode ni listas extensas de eventos.
4. Cualquier función conservada tiene un espacio propio y es localizable desde la navegación en un paso; detalle dentro de una vista queda a no más de una acción adicional.
5. Explorar muestra por defecto un horizonte acotado; el ciclo completo requiere una acción explícita y se presenta en grupos manejables.
6. Diario y análisis no convierten correlación en causalidad; el usuario conserva exportación, importación y borrado local.
7. Comparar ciclos, cronotipo y contexto simbólico explican sus limitaciones junto al resultado.
8. Los parámetros, API, PWA, widget, exportaciones y almacenamiento descritos en contratos se comportan igual que antes.
9. Se puede cambiar entre vistas, usar atrás/adelante y abrir los anclajes heredados sin perder la fecha elegida ni alterar query params.
10. Capturas de los cuatro espacios en desktop y móvil muestran jerarquía clara, estados intencionales y ningún overflow horizontal.
11. Una revisión manual de teclado confirma foco visible, orden lógico, encabezado anunciado al cambiar de vista y ausencia de foco en contenido oculto.

## No objetivos

- Cambiar fórmulas, mensajes del API, códigos HTTP, persistencia o autenticación.
- Añadir predicciones, decisiones automáticas, alertas basadas en evidencia no demostrada o afirmaciones de salud.
- Reemplazar la identidad editorial aprobada o volver a diseñar los componentes por una tendencia visual nueva.
- Eliminar la disponibilidad del API, widget, calendario, diario, compatibilidad o contexto simbólico; se reorganizan y, cuando hace falta, se reencuadran.
- Añadir telemetría o recopilar datos personales.

## Revisión de decisiones abiertas

- La propuesta retira Story mode de la interfaz porque duplica la lectura y deja la tarjeta compartible como salida visual. Si el usuario identifica un trabajo distinto que el modo Story resuelve, debe señalarlo antes de aprobar la especificación.
- El módulo «Días extremos» deja de desplegar el ciclo completo por defecto; sigue disponible bajo petición en Explorar.
- La navegación fragmento/hash es aditiva y no cambia query parameters; la compatibilidad de anclajes heredados se confirmará al inspeccionar sus consumidores en la implementación.
- La afirmación «una acción» para encontrar herramientas se mide desde la barra principal hasta el espacio; acciones internas del módulo se cuentan aparte.
