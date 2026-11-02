# Contrato editorial para FastAPI / Flutter

El backend FastAPI no forma parte de este repositorio web. Para conservar la arquitectura
Flutter → FastAPI → MySQL, el servicio debe reutilizar las tablas `blog`, `blog_detalle`,
`blog_artista` y `blog_album` creadas por la migración editorial.

## Endpoints propuestos

- `GET /editorial/posts`: Editor y Artist reciben solo publicaciones con
  `autor_id` igual al usuario autenticado; Executive Producer recibe todas.
- `POST /editorial/posts`: roles 2, 3 y 4. El servidor toma `autor_id` del token,
  nunca del cuerpo. Inserta `blog` y `blog_detalle` dentro de una transacción.
- `GET /editorial/posts/{id}`: Executive Producer o autor de la publicación.
- `PUT /editorial/posts/{id}` y `DELETE /editorial/posts/{id}`: Executive Producer
  o autor. No aceptar una reasignación de `autor_id`.
- `GET /editorial/catalog`: artistas y álbumes disponibles para asociaciones.
- `PUT /admin/users/{id}/role`: exclusivamente Executive Producer; roles válidos
  `1`, `2`, `3`, `4`.

Los payloads de creación/actualización deben incluir `titulo`, `descripcion`,
`contenido`, `tags`, `estado`, `artista_ids` y `album_ids`; los dos últimos son
opcionales. Validar longitudes, sanitizar el HTML editorial y controlar el estado,
autoría y rol en FastAPI antes de ejecutar consultas preparadas.
