# Biorrhythms

Producto PHP simple para visualizar biorritmos sin instalar dependencias en la máquina, manteniendo una demo separada de alto impacto.

## Verlo en local con Docker

```bash
docker compose up
```

Abre:

```text
http://localhost:8000
```

## Rutas

- `/` producto principal con lectura diaria, forecast, compatibilidad, widget y API.
- `/demo/` demo visual con timeline, story mode y exportación de imágenes.
- `/api/` endpoint JSON estable para integraciones.

## Qué hace el producto

- Calcula biorritmos físico, emocional e intelectual.
- Muestra lectura diaria, forecast y ritual accionable.
- Permite comparar compatibilidad entre dos personas.
- Expone widget embebible y API JSON.
- Funciona sin Composer, Node ni PHP instalados en el host.

## Qué hace la demo

- Mantiene la experiencia visual original con timeline y story mode.
- Conserva share cards, exportaciones PNG y compatibilidad avanzada.
- Sigue disponible como escaparate público sin perder la versión producto.
