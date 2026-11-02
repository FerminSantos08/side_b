# SIDE B

Plataforma web editorial y de comercio musical construida con PHP, PDO y MySQL.

## Estado actual

### Sitio público

- Inicio con publicaciones editoriales y productos destacados obtenidos desde MySQL.
- Catálogo de artistas, perfiles de artista, álbumes y publicaciones.
- Etiquetas editoriales construidas desde la relación `blog_artista → artista`, no desde etiquetas libres.
- Fichas de producto por URL pública con slug, por ejemplo `producto.php?slug=am-arctic-monkeys`.
- Ficha de álbum (`album.php`) con artista, portada, publicaciones y productos vinculados.
- Tienda con productos activos y con stock disponible.
- Perfil de usuario autenticado con datos reales de `usuario` y `perfil_usuario`.
- Header público compartido con navegación e iconos para búsqueda, perfil y carrito.

### Backstage

- Acceso restringido por rol para Editorial y administración.
- Gestión editorial de publicaciones, artistas relacionados y álbumes relacionados.
- Selector editorial de artistas y álbumes con relaciones dependientes y validación en servidor.
- CRUD básico para productos, categorías, artistas, álbumes, pedidos y usuarios.
- Desactivación lógica de productos.
- Vista de consulta de roles fijos y asignación de roles desde Usuarios.
- Validaciones CSRF, PDO preparado, validación de imágenes y restricciones de relaciones principales.

### Comercio y catálogo musical

- `producto.album_id` es una relación opcional: sirve para CD, vinilo o merch de un lanzamiento, sin limitar productos generales como ropa o audio.
- Categoría inicial: `Álbumes musicales`.
- El selector de álbum en Backstage se muestra únicamente al elegir esa categoría.
- Productos generales pueden existir sin álbum asociado.

## Requisitos

- PHP 8.1 o superior con PDO MySQL, `mbstring`, `fileinfo` y `iconv`.
- MySQL 8.
- Apache o servidor PHP compatible.

## Configuración local

Las credenciales no se almacenan en el repositorio. Configura las variables de entorno del servidor usando `.env.example` como referencia:

```text
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=side_b
DB_USER=scored
DB_PASSWORD=tu_secreto_local
```

> La aplicación requiere `DB_PASSWORD` para abrir la conexión.

## Base de datos

1. Ejecuta `assets/sql/SIDE_B_V1-2.sql` en una base nueva.
2. Ejecuta `assets/sql/2026_10_29_editorial_roles.sql` para las relaciones editoriales y el rol Editor.
3. Para instalaciones existentes, aplica las migraciones aditivas:
   - `assets/sql/2026_11_02_producto_album.sql`
   - `assets/sql/2026_11_02_producto_slug.sql`

## Estructura relevante

```text
assets/php/bootstrap.php   Configuración, sesión, conexión PDO y componentes comunes
assets/php/editorial.php   Utilidades de publicaciones
assets/sql/                Esquema y migraciones
uploads/                   Archivos subidos en ejecución (no versionados)
editorial.php              Gestión de publicaciones
admin.php                  CRUD administrativo
roles.php                  Consulta de roles y permisos
album.php                  Ficha pública de álbum
producto.php               Ficha comercial de producto
```

## Pendientes

- Completar tablas administrativas responsive con buscadores, filtros, paginación y acciones visuales unificadas.
- Aplicar restricciones de transición de pedidos y una integración verificable de pagos antes de marcar pedidos como pagados.
- Implementar carrito persistente, creación de pedidos y descuento de inventario atómico al confirmar pagos.
- Mover imágenes editoriales y de catálogo que deban versionarse desde `uploads/` a recursos administrados del proyecto.
- Añadir carga/edición de avatar, alias, biografía y ubicación desde Mi perfil.
- Implementar búsqueda real para el icono de búsqueda.
- Completar filtros de categorías en la tienda y productos destacados configurables.
- Añadir pruebas automatizadas de integración y flujos de Backstage.
- Integrar un logotipo gráfico final, transparente y optimizado en el header y footer compartidos.

## Seguridad

- No subir `.env`, secretos ni credenciales.
- `uploads/` se excluye del repositorio porque contiene archivos generados o cargados en ejecución.
- Las validaciones visuales no sustituyen las validaciones PHP ni las claves foráneas de MySQL.
