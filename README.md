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

- PHP 8.1 o superior con PDO MySQL, `mbstring`, `fileinfo`, `iconv` y GD con soporte WebP. EXIF permite corregir la orientación de fotos JPEG.
- MySQL 8.
- Apache o servidor PHP compatible.

## Configuración local

En Fedora, instala PHP y sus extensiones:

```bash
sudo dnf install php-cli php-mysqlnd php-mbstring php-xml php-gd php-intl php-process composer
```

El archivo `composer.lock` del respaldo fijaba mPDF 8.2.0, compatible hasta PHP 8.2.
Al recuperar el proyecto en un PHP más reciente, actualiza la dependencia y conserva
el nuevo `composer.lock`:

```bash
composer update mpdf/mpdf --with-all-dependencies --no-interaction --no-plugins --no-scripts
composer check-platform-reqs
```

Para iniciar el servidor local:

```bash
bash scripts/serve.sh
```

Abre `http://127.0.0.1:8080`. El router local impide descargar `.env`, los SQL,
el directorio de dependencias y las herramientas de mantenimiento. Este comando
es para desarrollo; no configura un servidor de producción.

Las credenciales no se almacenan en el repositorio. Configura las variables de entorno del servidor usando `.env.example` como referencia:

```text
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=side_b
DB_USER=scored
DB_PASSWORD=tu_secreto_local
```

> La aplicación requiere `DB_PASSWORD` para abrir la conexión.

### Spotify en las fichas de álbum

Copia el Client ID y el Client Secret de tu aplicación desde el
[panel de Spotify for Developers](https://developer.spotify.com/dashboard)
al archivo `.env` de la raíz:

```dotenv
SPOTIFY_CLIENT_ID=tu_client_id
SPOTIFY_CLIENT_SECRET=tu_client_secret
SPOTIFY_MARKET=MX
```

Recarga la ficha para consultar la lista de canciones. PHP necesita las
extensiones `curl` y `mbstring`. Si Spotify no se puede consultar, el botón
abre la búsqueda por título y artista.

## Base de datos

Hay un respaldo **con datos**, `assets/sql/side_b_2026-11-09.sql`, terminado el
9 de noviembre de 2026 a las 23:04:07 según la cabecera del archivo. Incluye
18 tablas: catálogo, usuarios, publicaciones, pedidos, direcciones y guardados.
Requiere MySQL 8 o superior; usa la colación `utf8mb4_0900_ai_ci`.

Para restaurarlo en una base vacía, configura `.env` y ejecuta:

```bash
python3 scripts/restore-db.py
```

El restaurador comprueba que `side_b` no tenga tablas antes de importar, y aplica
el desglose de pedidos que necesita el checkout. El usuario MySQL configurado
debe tener permisos de creación sobre `side_b`. Si la base ya está restaurada,
aplica únicamente la migración repetible:

```bash
php scripts/migrate.php
```

El dump contiene `DROP TABLE`: no lo importes directamente sobre una base con
datos que quieras conservar. Las imágenes de `uploads/` no están incluidas en
SQL ni en Git; sus rutas se conservan en las tablas y hay que recuperar los
archivos desde otro respaldo.

Para una instalación desde el esquema en vez del respaldo con datos:

1. Ejecuta `assets/sql/SIDE_B_V1-2.sql` en una base nueva.
2. Ejecuta `assets/sql/2026_10_29_editorial_roles.sql` para las relaciones editoriales y el rol Editor.
3. Para instalaciones existentes, aplica las migraciones aditivas:
   - `assets/sql/2026_11_02_producto_album.sql`
   - `assets/sql/2026_11_02_producto_slug.sql`
   - `assets/sql/2026_11_07_paypal_pedidos.sql`
   - `assets/sql/2026_11_09_direcciones_usuario.sql`
   - `assets/sql/2026_11_09_guardados_publicaciones.sql`
   - `assets/sql/2026_11_11_pedido_desglose.sql`

No apliques otra vez las migraciones antiguas sobre el dump del 9 de noviembre;
ya contiene esos cambios. Para ese dump basta `php scripts/migrate.php`.

## Imágenes subidas

Artistas, álbumes, productos, publicaciones y avatares comparten la conversión
automática a WebP (calidad 82). Se aceptan JPG, PNG y WebP de hasta 5 MB y
16 megapíxeles, con un máximo de 12000 píxeles por lado. Se conserva la
transparencia y la proporción; las imágenes se reducen a un máximo de 1920 píxeles
por lado, o 512 para avatares, sin ampliar imágenes pequeñas.

La estructura de `uploads/` se conserva con archivos `.gitkeep`; las imágenes
siguen excluidas de Git y deben respaldarse por separado del SQL. `albumes/`
recibe las portadas nuevas; `albums/portadas_arctic/` conserva la ruta usada por
el respaldo antiguo. El proceso del servidor necesita permiso de escritura en
las carpetas donde se suben archivos. Los archivos existentes no se convierten.

### Descargar portadas en lote

El descargador usa MusicBrainz para identificar el álbum por título y artista,
y Cover Art Archive para descargar su portada. Necesita PHP CLI con PDO MySQL,
Python 3.9 o superior y Pillow con soporte WebP (`sudo dnf install python3-pillow`).
Lee la conexión desde el `.env` de la aplicación; no pide credenciales de las APIs.

Desde la raíz del proyecto:

```bash
# Vista previa: consulta coincidencias, sin cambiar imágenes ni la BD.
python3 scripts/download-album-covers.py --limit 5

# Descargar todas las portadas pendientes y actualizar album.portada.
python3 scripts/download-album-covers.py --apply --report /tmp/side-b-portadas.json

# Procesar un artista concreto.
python3 scripts/download-album-covers.py --apply --artist "Arctic Monkeys"

# Pruebas sin conexión a Internet ni MySQL.
python3 tests/album_covers_test.py
```

Solo acepta coincidencias únicas de título y artista normalizados. Los casos
ambiguos quedan como `review`; una portada no disponible queda como `no_cover`.
En vista previa, `matched_preview` confirma la coincidencia del álbum, pero no
comprueba todavía la disponibilidad de la imagen. Consulta el informe JSON para
ver candidatos, fuentes y errores. Las solicitudes son secuenciales, con pausas
y reintentos; puedes volver a ejecutar el comando para continuar.

Las portadas locales válidas se conservan. Las nuevas se convierten a WebP de
calidad 82 y máximo 1920 px, guardándose en `uploads/albumes/`. Solo se actualiza
la BD si su ruta no cambió durante la descarga. No elimina archivos anteriores
ni modifica imágenes de artistas, productos o publicaciones.

Fuentes: https://musicbrainz.org/doc/MusicBrainz_API/Search y
https://musicbrainz.org/doc/Cover_Art_Archive/API.

### Descargar fotos de artistas

El script identifica al artista en MusicBrainz, sigue su enlace a Wikidata y
consulta la foto P18 en Wikimedia Commons. No elige automáticamente entre
artistas homónimos ni entre varias fotos sin prioridad. Necesita los mismos
requisitos del descargador de portadas.

```bash
# Preparar fotos candidatas sin cambiar imágenes ni la BD.
python3 scripts/download-artist-images.py
```

Abre `/tmp/side-b-artistas.html` en el navegador para revisar las fotos, fuentes
y licencias. La selección está en `/tmp/side-b-artistas.json`: puedes retirar
entradas que no quieras aplicar, o cambiar su estado de `preview` a `skip`.
Las miniaturas de la vista previa se cargan desde Wikimedia y requieren Internet.

```bash
# Aplicar las candidatas que revisaste.
python3 scripts/download-artist-images.py --apply --from-preview /tmp/side-b-artistas.json

# Pruebas sin Internet ni MySQL.
python3 tests/artist_images_test.py
```

Puedes limitar una ejecución con `--artist "Arctic Monkeys"` o `--limit 1`.
Las fotos válidas existentes se conservan. Los archivos nuevos se guardan en
`uploads/artistas/` como WebP, y se actualiza `artista.imagen`. Se conserva junto
a cada foto un archivo `.credits.json` con autor, licencia, fuente y cambios;
el pie de página muestra estos créditos. Respalda también esos archivos.
No cambia la foto si su ruta se modificó desde la vista previa.

El resultado de aplicación se guarda en `/tmp/side-b-artistas-resultado.json`
cuando la selección de entrada usa la ruta predeterminada. Los casos `review`,
`no_wikidata` o `no_image` necesitan revisión manual; `error` puede reintentarse.
No se sustituyen automáticamente fotos por imágenes de otro artista.

Fuentes: https://musicbrainz.org/doc/MusicBrainz_API y
https://www.mediawiki.org/wiki/API:Imageinfo.

## Comprobación local

```bash
find . -name '*.php' -not -path './vendor/*' -not -path './.git/*' -print0 | xargs -0 -n1 php -l
php tests/editorial_test.php
php tests/images_test.php
composer check-platform-reqs
```

Las pruebas de contenido editorial no requieren MySQL. El registro, checkout,
Backstage y los pedidos sí requieren la base restaurada. Los pagos necesitan
credenciales de PayPal sandbox y una comprobación separada de creación y captura;
recuperar los archivos no verifica una transacción.

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
