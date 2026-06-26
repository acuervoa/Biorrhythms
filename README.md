# Biorrhythms

App PHP para visualizar biorritmos sin instalar dependencias en la máquina.

## Verlo en local con Docker

```bash
docker compose up
```

Abre:

```text
http://localhost:8002
```

## Rutas

- `/` app principal: timeline, lectura diaria, forecast, compatibilidad, story mode, widget y exportación.
- `/api/` endpoint JSON estable para integraciones.

## Qué hace

- Calcula biorritmos físico, emocional e intelectual.
- Muestra timeline de 90 días, forecast de 7 días y ritual accionable.
- Permite comparar compatibilidad entre dos personas.
- Exporta tarjeta PNG y expone widget embebible vía iframe.
- Expone API JSON para integraciones externas.
- Funciona sin Composer, Node ni PHP instalados en el host.
