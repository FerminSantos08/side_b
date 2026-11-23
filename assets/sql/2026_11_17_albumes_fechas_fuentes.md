# Fuentes de fechas de lanzamiento

Consultadas el 17 de noviembre de 2026. Alcance: 47 álbumes de 9 artistas del respaldo `side_b_2026-11-09.sql`. La conexión a la BD local no estuvo disponible desde el entorno del agente; el SQL incluye consultas para detectar diferencias respecto del catálogo actual.

Se usan fechas de las ediciones originales, no fechas de reediciones o versiones deluxe. El SQL actualiza únicamente `album.fecha_lanzamiento`, identificando cada registro por título y artista; no inserta registros. Las cinco fechas de Tyler ya presentes en el respaldo coinciden con las consultadas.

| Artista | Fuente |
| --- | --- |
| Arctic Monkeys | https://en.wikipedia.org/wiki/Arctic_Monkeys_discography |
| Childish Gambino | https://en.wikipedia.org/wiki/Childish_Gambino_discography |
| Frank Ocean | https://en.wikipedia.org/wiki/Frank_Ocean_discography |
| Gorillaz | https://en.wikipedia.org/wiki/Gorillaz_discography |
| Joji | https://en.wikipedia.org/wiki/Joji_discography |
| Kendrick Lamar | https://en.wikipedia.org/wiki/Kendrick_Lamar_albums_discography |
| Michael Jackson | https://en.wikipedia.org/wiki/Michael_Jackson_albums_discography |
| The Weeknd | https://en.wikipedia.org/wiki/The_Weeknd_discography |
| Tyler, The Creator | https://en.wikipedia.org/wiki/Tyler,_the_Creator_discography |

## Diferencias regionales y comprobaciones adicionales

- **AM:** 6 de septiembre de 2013, primer lanzamiento internacional registrado en la discografía; la edición británica salió el 9 de septiembre.
- **Gorillaz / Demon Days / Plastic Beach:** fechas británicas (26 de marzo de 2001, 23 de mayo de 2005 y 8 de marzo de 2010). Los dos últimos tuvieron ediciones japonesas anteriores.
- **Thriller:** 30 de noviembre de 1982, según la descripción oficial del artista: https://www.youtube.com/watch?v=sOnqjkJTMaA . La discografía también registra el 29 de noviembre por su distribución anticipada.
- **HIStory:** 20 de junio de 1995, edición estadounidense; hubo lanzamientos regionales el 16 de junio.
- **Xscape:** 13 de mayo de 2014, edición estadounidense: https://music.apple.com/us/album/xscape-deluxe/850697616 . Hubo lanzamientos regionales el 9 de mayo.
- **nostalgia, ULTRA:** 16 de febrero de 2011, corroborado con https://musicbrainz.org/release/4fe337a7-2e91-4475-be84-af9cf8ad2e3c .
- **Atavista:** 13 de mayo de 2024, corroborado con https://apnews.com/article/0faa8bd083ddb07e1f1b0ed2ba736e32 .
- **The Mountain:** 27 de febrero de 2026, corroborado por los créditos oficiales: https://www.youtube.com/watch?v=4xN5NDAAOkY .
- **Piss in the Wind:** 6 de febrero de 2026, corroborado con la agencia del artista: https://twntythree.com/roster/joji/ .

## Aplicación

Desde la raíz del proyecto:

```bash
mysql -u scored -p side_b < assets/sql/2026_11_17_albumes_fechas.sql
```

La primera consulta muestra álbumes previstos que no se encontraron. Tras actualizar, el SQL lista las fechas guardadas con resultado `OK` y los álbumes que aún tengan fecha nula. Puede repetirse sin crear duplicados.
