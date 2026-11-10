-- MySQL dump 10.13  Distrib 8.4.13, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: side_b
-- ------------------------------------------------------
-- Server version	8.4.13

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `side_b`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `side_b` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `side_b`;

--
-- Table structure for table `album`
--

DROP TABLE IF EXISTS `album`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `album` (
  `id_album` int NOT NULL AUTO_INCREMENT,
  `artista_id` int NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `fecha_lanzamiento` date DEFAULT NULL,
  `portada` varchar(255) DEFAULT NULL,
  `tipo` enum('album','ep','sencillo') NOT NULL DEFAULT 'album',
  PRIMARY KEY (`id_album`),
  KEY `idx_album_artista` (`artista_id`),
  KEY `idx_album_lanzamiento` (`fecha_lanzamiento`),
  CONSTRAINT `fk_album_artista` FOREIGN KEY (`artista_id`) REFERENCES `artista` (`id_artista`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `album`
--

LOCK TABLES `album` WRITE;
/*!40000 ALTER TABLE `album` DISABLE KEYS */;
INSERT INTO `album` VALUES (2,2,'Whatever People Say I Am, That\'s What I\'m Not',NULL,NULL,'album'),(3,2,'Favourite Worst Nightmare',NULL,NULL,'album'),(4,2,'AM',NULL,'uploads/albums/portadas_arctic/am-arctic-monkeys.webp','album'),(5,2,'Tranquility Base Hotel & Casino',NULL,NULL,'album'),(6,2,'The Car',NULL,NULL,'album'),(7,3,'Camp',NULL,NULL,'album'),(8,3,'Because the Internet',NULL,NULL,'album'),(9,3,'Awaken, My Love!',NULL,NULL,'album'),(10,3,'Atavista',NULL,NULL,'album'),(11,3,'Bando Stone & the New World',NULL,NULL,'album'),(12,4,'nostalgia, ULTRA',NULL,NULL,'album'),(13,4,'channel ORANGE',NULL,NULL,'album'),(14,4,'Endless',NULL,NULL,'album'),(15,4,'Blonde',NULL,NULL,'album'),(16,5,'Gorillaz',NULL,NULL,'album'),(17,5,'Demon Days',NULL,NULL,'album'),(18,5,'Plastic Beach',NULL,NULL,'album'),(19,5,'Humanz',NULL,NULL,'album'),(20,5,'Cracker Island',NULL,NULL,'album'),(21,5,'The Mountain',NULL,NULL,'album'),(22,6,'BALLADS 1',NULL,NULL,'album'),(23,6,'Nectar',NULL,NULL,'album'),(24,6,'SMITHEREENS',NULL,NULL,'album'),(25,6,'Piss in the Wind',NULL,NULL,'album'),(26,7,'good kid, m.A.A.d city',NULL,NULL,'album'),(27,7,'To Pimp a Butterfly',NULL,NULL,'album'),(28,7,'DAMN.',NULL,NULL,'album'),(29,7,'Mr. Morale & the Big Steppers',NULL,NULL,'album'),(30,7,'GNX',NULL,NULL,'album'),(31,8,'Off the Wall',NULL,NULL,'album'),(32,8,'Thriller',NULL,NULL,'album'),(33,8,'Bad',NULL,NULL,'album'),(34,8,'Dangerous',NULL,NULL,'album'),(35,8,'HIStory',NULL,NULL,'album'),(36,8,'Invincible',NULL,NULL,'album'),(37,8,'Xscape',NULL,NULL,'album'),(38,9,'Kiss Land',NULL,NULL,'album'),(39,9,'Beauty Behind the Madness',NULL,NULL,'album'),(40,9,'Starboy',NULL,NULL,'album'),(41,9,'After Hours',NULL,NULL,'album'),(42,9,'Dawn FM',NULL,NULL,'album'),(43,9,'Hurry Up Tomorrow',NULL,NULL,'album'),(44,10,'Flower Boy','2017-07-21','uploads/albumes/flower-boy.jpg','album'),(45,10,'IGOR','2019-05-17','uploads/albumes/igor.jpg','album'),(46,10,'CALL ME IF YOU GET LOST','2021-06-25','uploads/albumes/call-me-if-you-get-lost.png','album'),(47,10,'CHROMAKOPIA','2024-10-28','uploads/albumes/chromakopia.jpg','album'),(48,10,'DON\'T TAP THE GLASS','2025-07-21','uploads/albumes/dont-tap-the-glass.jpg','album');
/*!40000 ALTER TABLE `album` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `artista`
--

DROP TABLE IF EXISTS `artista`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `artista` (
  `id_artista` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int DEFAULT NULL,
  `nombre` varchar(150) NOT NULL,
  `biografia` text,
  `imagen` varchar(255) DEFAULT NULL,
  `genero` varchar(100) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_artista`),
  UNIQUE KEY `usuario_id` (`usuario_id`),
  KEY `idx_artista_nombre` (`nombre`),
  CONSTRAINT `fk_artista_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id_usr`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `artista`
--

LOCK TABLES `artista` WRITE;
/*!40000 ALTER TABLE `artista` DISABLE KEYS */;
INSERT INTO `artista` VALUES (2,NULL,'Arctic Monkeys','Origen: Sheffield, Inglaterra. Formación: 2002.\\n\\nArctic Monkeys nació entre las calles de Sheffield y se convirtió en una de las bandas que redefinieron el rock británico del siglo XXI. Sus primeras canciones retrataban noches, bares, relaciones juveniles y vida cotidiana con una energía cruda.\\n\\nLa banda transformó continuamente su identidad: de su debut a la estética nocturna de AM, y después al piano, la ciencia ficción y la crítica cultural de Tranquility Base Hotel & Casino. The Car profundizó una atmósfera cinematográfica y sofisticada.','uploads/artistas/arctic mokeys.jpg','Indie rock, rock alternativo, garage rock',1),(3,NULL,'Childish Gambino','Origen: Estados Unidos. Nacimiento: 25 de septiembre de 1983.\\n\\nDetrás de Childish Gambino se encuentra Donald Glover, un artista que ha construido su carrera entre música, actuación, escritura y dirección. Su obra explora identidad, fama, soledad y las contradicciones de la sociedad contemporánea.\\n\\nBecause the Internet expandió el formato de álbum con una experiencia narrativa; Awaken, My Love! lo llevó hacia el funk y soul psicodélico, mientras This Is America abrió una conversación internacional sobre violencia, racismo y entretenimiento.','uploads/artistas/childis gambino.jpg','Hip-hop alternativo, R&B, funk, soul',1),(4,NULL,'Frank Ocean','Origen: Long Beach, California, Estados Unidos. Nacimiento: 28 de octubre de 1987.\\n\\nFrank Ocean es una de las voces más particulares del R&B contemporáneo. Sus canciones trabajan recuerdos, amor, identidad y paso del tiempo desde una sensibilidad introspectiva.\\n\\nTras nostalgia, ULTRA y channel ORANGE, Blonde llevó su propuesta a un territorio más experimental, con estructuras poco convencionales, voces procesadas y una narrativa entre distintas etapas de la memoria.','uploads/artistas/frank-ocean.jpg','R&B alternativo, neo-soul, pop experimental',1),(5,NULL,'Gorillaz','Origen: Reino Unido. Creación: 1998.\\n\\nGorillaz fue fundado por Damon Albarn y el ilustrador Jamie Hewlett como una banda donde los músicos no eran necesariamente los protagonistas visibles. 2-D, Murdoc, Noodle y Russel forman un universo donde música, animación y crítica social conviven.\\n\\nDemon Days exploró miedo e incertidumbre; Plastic Beach reflexionó sobre consumismo y deterioro ambiental. Su propuesta mezcla rock, electrónica, hip-hop y colaboraciones internacionales.','uploads/artistas/gorillaz.webp','Rock alternativo, electrónica, hip-hop, trip-hop',1),(6,NULL,'Joji','Origen: Osaka, Japón. Nacimiento: 16 de septiembre de 1992.\\n\\nAntes de Joji, George Miller fue conocido en internet por personajes como Filthy Frank y Pink Guy. Al abandonar esa etapa desarrolló una identidad musical distinta: canciones melancólicas, producciones minimalistas y vulnerabilidad emocional.\\n\\nBALLADS 1, Nectar y SMITHEREENS consolidaron un sonido entre R&B alternativo, electrónica y pop, mostrando una trayectoria de reinvención artística.','uploads/artistas/joji.jpg','R&B alternativo, lo-fi, trip-hop, pop alternativo',1),(7,NULL,'Kendrick Lamar','Origen: Compton, California, Estados Unidos. Nacimiento: 17 de junio de 1987.\\n\\nKendrick Lamar convirtió las experiencias de su infancia en Compton en una propuesta narrativa sobre violencia, desigualdad, identidad, fama y las contradicciones del éxito.\\n\\ngood kid, m.A.A.d city relató crecer bajo presión social; To Pimp a Butterfly incorporó jazz, funk y soul; DAMN. alcanzó reconocimiento histórico con el Pulitzer de Música.','uploads/artistas/kendrick lamar.jpg','Hip-hop, rap consciente, jazz rap',1),(8,NULL,'Michael Jackson','Origen: Gary, Indiana, Estados Unidos. Nacimiento: 29 de agosto de 1958. Fallecimiento: 25 de junio de 2009.\\n\\nMichael Jackson transformó la música popular y la manera en que el público experimentaba a sus artistas. Desde The Jackson 5 hasta su carrera solista, combinó innovación musical, coreografía y ambición visual.\\n\\nOff the Wall conectó disco, funk y soul; Thriller redefinió el alcance comercial y audiovisual de un álbum; Bad y Dangerous ampliaron su sonido. Xscape recuperó grabaciones inéditas y abrió conversaciones sobre su legado.','uploads/artistas/michael jackson.jpg','Pop, R&B, funk, soul, dance-pop',1),(9,NULL,'The Weeknd','Origen: Toronto, Canadá. Nacimiento: 16 de febrero de 1990.\\n\\nAbel Tesfaye, conocido como The Weeknd, construyó una identidad musical alrededor de la vida nocturna, relaciones destructivas, deseo y consecuencias de los excesos. Sus primeras grabaciones circularon por internet envueltas en misterio.\\n\\nStarboy exploró fama y materialismo; After Hours desarrolló una estética de neón, aislamiento y autodestrucción; Dawn FM presentó una experiencia conceptual de radio y tránsito hacia otra existencia.','uploads/artistas/the weekend.jpg','R&B alternativo, synth-pop, dark pop',1),(10,NULL,'Tyler, The Creator','Origen: Hawthorne, California, Estados Unidos. Nacimiento: 6 de marzo de 1991.\\n\\nTyler, The Creator ha construido una de las transformaciones más interesantes del hip-hop contemporáneo. Sus inicios con Odd Future estuvieron marcados por provocación y humor irreverente.\\n\\nFlower Boy mostró una faceta introspectiva; IGOR construyó una narrativa sobre enamoramiento y pérdida; CALL ME IF YOU GET LOST exploró viajes y búsqueda personal; CHROMAKOPIA profundizó en el miedo a crecer y la adultez.','uploads/artistas/tyler, the creator.jpg','Hip-hop alternativo, neo-soul, jazz rap, R&B',1);
/*!40000 ALTER TABLE `artista` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blog`
--

DROP TABLE IF EXISTS `blog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog` (
  `id_blog` int NOT NULL AUTO_INCREMENT,
  `titulo` varchar(200) NOT NULL,
  `desc` varchar(500) DEFAULT NULL,
  `img` varchar(255) DEFAULT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `slug` varchar(220) NOT NULL,
  PRIMARY KEY (`id_blog`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blog`
--

LOCK TABLES `blog` WRITE;
/*!40000 ALTER TABLE `blog` DISABLE KEYS */;
INSERT INTO `blog` VALUES (1,'IGOR: el amor y el arte de dejar ir','Tyler, The Creator transforma el desamor, los celos y la aceptación en una experiencia musical que combina hip-hop, soul y sintetizadores.','Collage_igor.png','Tyler The Creator, IGOR, Hip-Hop, Soul','igor-una-experiencia-musical-diferente'),(2,'Xscape: la historia de Chicago, la canción que volvió a conquistar el mundo','Grabada en 1999, descartada de Invincible y recuperada para Xscape en 2014, Chicago encontró una nueva generación de oyentes en 2026 gracias a las redes sociales, una animación viral y el renovado interés por Michael Jackson.','Michael-Jackson-Chicago.jpg','Michael Jackson, Xscape, Chicago, Invincible, Timbaland, Pop, R&B, Billboard, Cultura Digital','xscape-historia-chicago-michael-jackson'),(3,'CHROMAKOPIA: el miedo a crecer y el camino de regreso a casa','En *CHROMAKOPIA*, Tyler, The Creator explora el miedo a crecer, la identidad, el amor y las heridas del pasado. Un viaje íntimo detrás de la máscara de Saint Chroma, donde madurar significa aprender a enfrentar nuestros temores y encontrar nuestro propio camino.','b9d370c5aca415ffe6b280e5e4367ad5.png','Tyler The Creator, CHROMAKOPIA, Saint Chroma, Hip-Hop, Rap Alternativo, Música, Identidad, Crecimiento Personal, Madurez, Introspección','chromakopia-el-miedo-a-crecer-y-el-camino-de-regreso-a-casa'),(4,'DON\'T TAP THE GLASS: Una fiesta detrás del cristal','Tyler, The Creator nos invita a dejar atrás las preocupaciones y recuperar la libertad de bailar sin sentirnos juzgados. Entre ritmos de funk, hip-hop y emociones que se esconden detrás de la fiesta, *DON\'T TAP THE GLASS* nos recuerda que disfrutar del presente también es una forma de liberarnos.','cc18333634a54bd2e86b1dd3884bec8e.png','Tyler, The Creator, DON\'T TAP THE GLASS, Big Poe, Hip-Hop, Funk, Música electrónica, Cultura musical, 2025','don-t-tap-the-glass-una-fiesta-detr-as-del-cristal'),(5,'THE CAR: Elegancia en el asiento trasero','Arctic Monkeys abandonan la urgencia del rock para conducirnos por un universo de cuerdas, nostalgia y elegancia cinematográfica. The Car no busca acelerar: nos invita a disfrutar del trayecto.','the-car-arctic-monkeys.png','Arctic Monkeys, The Car, Indie Rock, Art Rock, Baroque Pop, 2022','the-car-elegancia-en-el-asiento-trasero');
/*!40000 ALTER TABLE `blog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blog_album`
--

DROP TABLE IF EXISTS `blog_album`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_album` (
  `blog_id` int NOT NULL,
  `album_id` int NOT NULL,
  PRIMARY KEY (`blog_id`,`album_id`),
  KEY `fk_blog_album_album` (`album_id`),
  CONSTRAINT `fk_blog_album_album` FOREIGN KEY (`album_id`) REFERENCES `album` (`id_album`) ON DELETE RESTRICT,
  CONSTRAINT `fk_blog_album_blog` FOREIGN KEY (`blog_id`) REFERENCES `blog` (`id_blog`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blog_album`
--

LOCK TABLES `blog_album` WRITE;
/*!40000 ALTER TABLE `blog_album` DISABLE KEYS */;
INSERT INTO `blog_album` VALUES (5,6),(2,37),(1,45),(3,47),(4,48);
/*!40000 ALTER TABLE `blog_album` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blog_artista`
--

DROP TABLE IF EXISTS `blog_artista`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_artista` (
  `blog_id` int NOT NULL,
  `artista_id` int NOT NULL,
  PRIMARY KEY (`blog_id`,`artista_id`),
  KEY `fk_blog_artista_artista` (`artista_id`),
  CONSTRAINT `fk_blog_artista_artista` FOREIGN KEY (`artista_id`) REFERENCES `artista` (`id_artista`) ON DELETE RESTRICT,
  CONSTRAINT `fk_blog_artista_blog` FOREIGN KEY (`blog_id`) REFERENCES `blog` (`id_blog`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blog_artista`
--

LOCK TABLES `blog_artista` WRITE;
/*!40000 ALTER TABLE `blog_artista` DISABLE KEYS */;
INSERT INTO `blog_artista` VALUES (5,2),(2,8),(1,10),(3,10),(4,10);
/*!40000 ALTER TABLE `blog_artista` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blog_detalle`
--

DROP TABLE IF EXISTS `blog_detalle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_detalle` (
  `blog_id` int NOT NULL,
  `contenido` longtext NOT NULL,
  `autor_id` int NOT NULL,
  `estado` enum('borrador','revision','publicado','archivado') NOT NULL DEFAULT 'borrador',
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fecha_publicacion` datetime DEFAULT NULL,
  PRIMARY KEY (`blog_id`),
  KEY `idx_blog_detalle_autor` (`autor_id`),
  KEY `idx_blog_detalle_estado_fecha` (`estado`,`fecha_publicacion`),
  CONSTRAINT `fk_blog_detalle_autor` FOREIGN KEY (`autor_id`) REFERENCES `usuario` (`id_usr`) ON DELETE RESTRICT,
  CONSTRAINT `fk_blog_detalle_blog` FOREIGN KEY (`blog_id`) REFERENCES `blog` (`id_blog`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blog_detalle`
--

LOCK TABLES `blog_detalle` WRITE;
/*!40000 ALTER TABLE `blog_detalle` DISABLE KEYS */;
INSERT INTO `blog_detalle` VALUES (1,'<p>Lanzado en 2019, IGOR es uno de los proyectos más personales de Tyler, The Creator. A través de canciones como EARFQUAKE, NEW MAGIC WAND y GONE, GONE / THANK YOU, el álbum relata una historia de amor marcada por la obsesión, la inseguridad y la dificultad de dejar ir. Su producción experimental, sus sintetizadores distorsionados y su narrativa emocional convierten a IGOR en una experiencia que va más allá del hip-hop tradicional.</p>',1,'publicado','2026-10-29 06:24:21','2026-10-31 09:31:02','2026-10-29 06:24:21'),(2,'<p>Hay canciones que encuentran su público en el momento de su lanzamiento y otras que necesitan años para hacerlo. Chicago, de Michael Jackson, pertenece a la segunda categoría. Su historia comenzó en 1999, cuando el cantante trabajaba en el material que terminaría formando parte de Invincible, pero la grabación quedó fuera de aquel disco. Quince años después apareció en Xscape, un álbum publicado tras su muerte, y con el tiempo encontró nuevos oyentes en las plataformas digitales. Es un recorrido poco habitual: una canción concebida al final de los noventa terminó formando parte de conversaciones musicales que Jackson nunca habría podido imaginar.</p>\n<h3>UNA GRABACIÓN QUE QUEDÓ FUERA DE INVINCIBLE</h3>\n<p>Antes de conocerse como Chicago, la composición llevaba el nombre She Was Lovin Me. Fue escrita por Cory Rooney y grabada por Michael Jackson durante las sesiones de 1999, en una etapa en la que el artista exploraba numerosas canciones para su siguiente proyecto. La letra cuenta una historia de engaño sentimental: el narrador descubre que la mujer con quien mantiene una relación le ha ocultado otra vida. Jackson interpreta esa revelación con una mezcla de incredulidad, tensión y frustración, recursos que dan fuerza a una canción construida alrededor de una historia bastante concreta. No se trata solamente de un ritmo pegadizo, sino de una interpretación que cambia de intensidad conforme avanza el relato.</p>\n<p>Cuando Invincible llegó a las tiendas en octubre de 2001, She Was Lovin Me no figuraba entre sus canciones. Eso no significa necesariamente que hubiera sido descartada por falta de calidad: los álbumes suelen reunir una selección pequeña de todo el material grabado durante años de trabajo, y no existe una explicación pública definitiva que reduzca esta decisión a una sola causa. La grabación permaneció inédita durante la vida de Jackson, mientras Invincible se convertía en su último álbum de estudio publicado en vida. Durante mucho tiempo, aquella interpretación fue parte de un archivo musical al que el público general no tenía acceso.</p>\n<h3>XSCAPE: UNA SEGUNDA OPORTUNIDAD EN 2014</h3>\n<p>El 13 de mayo de 2014 se publicó Xscape, un proyecto póstumo que reunió ocho grabaciones de distintas etapas de la carrera de Michael Jackson. Bajo la dirección ejecutiva de L.A. Reid, el equipo eligió conservar las voces originales y construir nuevos arreglos musicales a su alrededor. Productores como Timbaland, Rodney Jerkins y Stargate participaron en el álbum, que buscaba presentar ese material a oyentes contemporáneos sin perder el elemento central de cada canción: la interpretación de Jackson. El resultado despertó interés por permitir escuchar música hasta entonces inédita, aunque también generó preguntas sobre las decisiones creativas tomadas después de la muerte del artista.</p>\n<p>She Was Lovin Me fue una de las piezas recuperadas. Para Xscape recibió una nueva producción de Timbaland y Jerome J-Roc Harmon, además de un nuevo título: Chicago. La versión de 2014 acentúa la base rítmica y los elementos electrónicos, con una energía distinta de la grabación original. Lo interesante es que ambas interpretaciones permiten escuchar la misma voz desde dos contextos musicales separados por quince años. La edición deluxe del álbum incluyó las versiones originales de las ocho canciones, de modo que cada oyente pudiera comparar los arreglos históricos con las reinterpretaciones posteriores y decidir cuál le resulta más convincente.</p>\n<p>Esa comparación abre una cuestión que va más allá de Chicago. Publicar canciones póstumas permite recuperar grabaciones que de otro modo podrían permanecer guardadas, pero también implica terminar o transformar obras sin la participación del músico. La voz de Jackson en Xscape es auténtica; las decisiones de producción añadidas en 2014, en cambio, pertenecen a otras personas. Reconocer ambas cosas no obliga a rechazar el álbum ni a aceptarlo sin reservas. Más bien invita a escucharlo con contexto, entendiendo que es al mismo tiempo un documento de su trabajo y una reinterpretación realizada en otra época.</p>\n<h3>CUANDO CHICAGO ENCONTRÓ UNA NUEVA AUDIENCIA</h3>\n<p>Aunque Xscape apareció en 2014, la vida pública de Chicago no terminó con aquella promoción. Años después, fragmentos de la canción comenzaron a circular en videos cortos y publicaciones de redes sociales, donde la música suele desprenderse de su álbum original y adquirir significados nuevos. Las versiones aceleradas, los montajes y las tendencias de baile facilitaron que personas que nunca habían escuchado Xscape se encontraran con su estribillo. Ese tipo de redescubrimiento es especialmente llamativo en el caso de Jackson: un artista asociado a grandes lanzamientos físicos, videoclips televisivos y espectáculos masivos cuya música sigue viajando por formatos digitales muy distintos.</p>\n<p>En 2026, la circulación de una animación de pájaros bailando, conocida en internet como birds for some reason, volvió a relacionar Chicago con el humor visual y los videos virales. La escena resultaba inesperada: una canción grabada en 1999 acompañaba movimientos cómicos de personajes animados y despertaba curiosidad por la grabación completa. Sería simplificar demasiado atribuir todo el interés renovado a un único video, porque las canciones suelen ganar audiencia por varias vías al mismo tiempo. Aun así, la combinación muestra cómo una obra puede entrar en una conversación cultural nueva sin haber sido creada para ella.</p>\n<p>La historia de Chicago no es solamente la de una canción rescatada de un archivo. También es la de tres momentos musicales que se encuentran: las sesiones de Michael Jackson a finales de los noventa, la producción póstuma de Xscape en 2014 y las formas de escuchar y compartir música de la década de 2020. Cada etapa cambió la manera en que el público se acercó a la misma interpretación vocal. Quizá por eso su recorrido resulta tan interesante: lo que una vez quedó fuera de un álbum terminó encontrando una segunda vida y, después, una audiencia que llegó a ella por caminos completamente inesperados.</p>',1,'publicado','2026-10-29 07:59:39','2026-10-31 09:35:14','2026-10-29 07:59:39'),(3,'<p>Crecer no siempre significa tener todas las respuestas. A veces significa descubrir que las preguntas que evitamos durante años finalmente nos han alcanzado. En <em>CHROMAKOPIA</em>, Tyler, The Creator abandona parte de la seguridad que le proporcionaban sus antiguos personajes para enfrentarse a sus propios miedos: el paso del tiempo, la soledad, las relaciones amorosas y la posibilidad de convertirse en alguien que todavía no está preparado para ser.</p><p>Detrás de la máscara de Saint Chroma encontramos a un hombre que, pese a haber alcanzado el éxito, comienza a cuestionarse qué significa realmente tener una vida completa. Canciones como <em>Noid</em> muestran el precio de la fama y la pérdida de privacidad, mientras que <em>Darling, I</em> explora el deseo de amar sin renunciar a la libertad personal. Tyler parece debatirse constantemente entre aquello que quiere experimentar y aquello que teme perder.</p><p>Sin embargo, el álbum adquiere una dimensión más íntima cuando comienza a hablar del futuro. En <em>Hey Jane</em> y <em>Tomorrow</em>, la posibilidad de convertirse en padre deja de ser una idea lejana y se transforma en una pregunta incómoda. Mientras las personas de su entorno construyen familias y avanzan hacia nuevas etapas, Tyler observa su propia vida y se pregunta si realmente está preparado para seguir ese mismo camino. No se trata únicamente del miedo a tener hijos, sino del temor a perder la identidad que ha construido durante tantos años.</p><p>En <em>Like Him</em>, esa búsqueda de identidad nos conduce inevitablemente hacia el pasado. Tyler reflexiona sobre la ausencia de su padre y las semejanzas que podrían existir entre ambos. La participación de su madre convierte la canción en uno de los momentos más vulnerables del proyecto, recordándonos que crecer también implica reconsiderar las historias que hemos contado sobre nuestra propia vida y aceptar que algunas respuestas pueden cambiarlo todo.</p><p>Pero quizá una de las reflexiones más importantes aparece en <em>Take Your Mask Off</em>. Después de construir personajes, esconder inseguridades y proyectar una imagen de fortaleza, Tyler se enfrenta a una pregunta esencial: ¿quién eres cuando dejas de interpretar el papel que los demás esperan de ti? La máscara deja de ser únicamente un elemento visual para convertirse en una representación de todas aquellas versiones de nosotros mismos que utilizamos para evitar mostrar nuestra vulnerabilidad.</p><p>Finalmente, <em>I Hope You Find Your Way Home</em> funciona como una despedida que no pretende resolver todas las dudas del álbum. Tyler no termina su recorrido convertido en alguien que comprende perfectamente el amor, la familia o el futuro. Termina siendo alguien dispuesto a seguir buscando. Y quizá esa sea la verdadera esencia de <em>CHROMAKOPIA</em>: comprender que crecer no consiste en dejar de tener miedo, sino en aprender a avanzar a pesar de él. Porque hay discos que escuchamos para descubrir a un artista, y hay discos a los que volvemos para descubrir cuánto hemos cambiado nosotros.</p>',1,'publicado','2026-10-31 09:07:27','2026-10-31 09:17:22','2026-10-31 09:17:22'),(4,'<p>¿Cuándo fue la última vez que bailaste sin preocuparte por quién te estaba mirando? Sin pensar en las cámaras, en las opiniones de los demás o en la posibilidad de que un momento espontáneo termine convertido en una publicación de internet. Vivimos en una época donde prácticamente todo puede grabarse, compartirse y juzgarse, y esa posibilidad ha cambiado nuestra manera de comportarnos incluso en los momentos donde deberíamos sentirnos libres. En medio de esa realidad, Tyler, The Creator nos invita a hacer algo tan sencillo como olvidarnos de todo y bailar.</p><p>El 21 de julio de 2025, apenas nueve meses después de <em>CHROMAKOPIA</em>, el artista lanzó <em>DON\'T TAP THE GLASS</em>, un proyecto de diez canciones y aproximadamente veintiocho minutos que se aleja de las confesiones personales y los conflictos emocionales que dominaron algunos de sus trabajos anteriores. Con influencias del funk, el hip-hop y la música electrónica, Tyler construye una experiencia donde el movimiento adquiere protagonismo. Sin embargo, detrás de su aparente sencillez existe una pregunta interesante: ¿qué llevó al artista a crear un álbum dedicado a recuperar el placer de divertirse?</p><h2>Cuando bailar se convierte en un acto de libertad</h2><p>Durante la promoción del álbum, Tyler compartió una experiencia que ayuda a comprender parte de su propuesta. Había preguntado a algunos amigos por qué ya no bailaban cuando salían y descubrió que varias respuestas coincidían en una preocupación: el miedo a ser grabados y ridiculizados en internet. La posibilidad de que alguien sacara su teléfono, capturara un movimiento incómodo y lo compartiera con miles de desconocidos era suficiente para impedirles disfrutar. Aquella situación lo llevó a cuestionarse cómo habíamos llegado a un punto donde necesitamos sentirnos seguros de no ser observados para permitirnos divertirnos.</p><p>Esa inquietud terminó convirtiéndose en uno de los principios que rodean a <em>DON\'T TAP THE GLASS</em>, aunque el propio artista aclaró que no fue la única razón detrás de su creación. El 20 de julio de 2025, un día antes del lanzamiento, Tyler reunió a aproximadamente 300 personas en el Masonic Lodge de Hollywood Forever, en Los Ángeles, para una escucha anticipada con una condición particular: no se permitían teléfonos ni cámaras. Durante aquella noche, el álbum sonó dos veces mientras el público bailaba sin preocuparse por cómo se vería en una grabación. Más que presentar música nueva, Tyler estaba poniendo a prueba una idea: descubrir qué ocurre cuando desaparece la necesidad de aparentar frente a los demás.</p><p>La filosofía del proyecto se resume en tres reglas: mover el cuerpo y no permanecer quieto, hablar desde lo positivo dejando las preocupaciones en casa y, finalmente, no golpear el cristal. Estas instrucciones no buscan establecer una narrativa compleja, sino proponer una manera distinta de experimentar la música. El álbum invita a abandonar temporalmente las presiones cotidianas y recuperar la espontaneidad, aunque eso no signifique que nuestros problemas desaparezcan por completo.</p><h2>Big Poe: la extravagancia de volver a divertirse</h2><p>Para esta etapa, Tyler adoptó una identidad visual que contrasta con el personaje militar y enmascarado de <em>CHROMAKOPIA</em>. La gorra roja, los lentes oscuros, la chaqueta de cuero y una enorme cadena dorada construyen la apariencia de Big Poe, una figura extravagante, segura de sí misma y dispuesta a ocupar cualquier espacio sin pedir permiso. Su presencia representa una faceta del artista que no necesita justificar constantemente sus decisiones ni explicar cada una de sus emociones. Big Poe existe para disfrutar del momento, expresar confianza y dejarse llevar por la música.</p><p>Esa actitud también define el sonido del álbum. En lugar de desarrollar una historia extensa, Tyler recupera la energía del hip-hop de principios de los años 2000, el funk y la música electrónica, utilizando bajos prominentes, sintetizadores y repeticiones vocales que funcionan como elementos de percusión. «Big Poe», la canción que abre el proyecto, establece inmediatamente esa identidad mediante la participación de Pharrell Williams, acreditado como Sk8brd, y un sample de «Pass the Courvoisier, Part II», de Busta Rhymes. La referencia conecta con una época especialmente importante para las influencias musicales de Tyler, particularmente por el trabajo de Pharrell y The Neptunes, cuya producción ayudó a definir buena parte del sonido que el artista admiraba durante su juventud.</p><p>Otras composiciones amplían esa misma personalidad desde diferentes perspectivas. En «Sugar on My Tongue», los sintetizadores, el bajo y las insinuaciones sexuales construyen una atmósfera provocadora y juguetona, mientras que «Sucka Free» celebra el éxito económico y la independencia personal con una confianza casi desafiante. Aunque estas canciones tienen enfoques distintos, comparten una intención: transmitir una energía que pueda sentirse físicamente. Tyler permite que las palabras, los sonidos y los ritmos se mezclen para convertir la música en una experiencia donde bailar resulta tan importante como escuchar.</p><h2>Lo que dejamos fuera de la fiesta</h2><p>Aunque <em>DON\'T TAP THE GLASS</em> propone abandonar nuestras preocupaciones, algunas canciones dejan entrever que no resulta tan sencillo. «Ring Ring Ring» es uno de los ejemplos más interesantes porque conserva una producción bailable mientras presenta una situación emocionalmente contradictoria. Tyler intenta comunicarse con una persona que parece haber decidido alejarse, recuerda la cercanía que existía entre ambos y expresa su deseo de volver a escuchar aquella voz, incluso cuando sabe que quizá ya no recibirá una respuesta. La música invita a moverse, pero las palabras revelan a alguien que todavía encuentra dificultades para dejar atrás una relación.</p><p>Esta contradicción recuerda a «ARE WE STILL FRIENDS?», la canción que cierra <em>IGOR</em>, donde Tyler se resiste a aceptar una despedida y suplica por conservar algún vínculo con la persona que ama. Aunque no existe motivo para afirmar que ambas composiciones narren una misma relación, sí podemos reconocer una preocupación emocional semejante expresada mediante lenguajes musicales diferentes. Mientras <em>IGOR</em> transforma esa necesidad en una súplica desgarradora, «Ring Ring Ring» la presenta dentro de una producción mucho más ligera. La tristeza no desaparece; simplemente encuentra otra manera de manifestarse.</p><p>Hacia el cierre del álbum, esa sensibilidad adquiere nuevas formas. En «I\'ll Take Care of You», la voz de Yebba acompaña una promesa de cuidado y cercanía, mientras la producción combina una atmósfera delicada con elementos de mayor intensidad. Finalmente, «Tell Me What It Is» introduce una inquietud que parece contradecir la seguridad exhibida durante buena parte del proyecto. Tyler puede disfrutar de su éxito, su dinero y su independencia, pero todavía se pregunta por qué encontrar una relación amorosa duradera resulta tan complicado. Esta preocupación conecta con algunas reflexiones de <em>CHROMAKOPIA</em>, particularmente con «Tomorrow», donde el paso del tiempo y las expectativas sobre su futuro adquirían protagonismo. De esta manera, el álbum que comenzó invitándonos a dejar los problemas fuera termina recordándonos que algunas preguntas siguen esperando una respuesta.</p><h2>Una fiesta detrás del cristal</h2><p>Quizá la mayor virtud de <em>DON\'T TAP THE GLASS</em> sea que no necesita convertir cada una de sus canciones en una confesión profunda. Después de proyectos como <em>IGOR</em> y <em>CHROMAKOPIA</em>, donde el amor, la identidad, el miedo y las expectativas personales ocupaban gran parte de su música, Tyler también encuentra valor en crear algo que simplemente nos haga sentir bien. Sus composiciones son directas, sus ritmos buscan provocar movimiento y su duración permite disfrutar de la experiencia sin exigir una interpretación compleja. Sin embargo, eso no significa que las preocupaciones del artista hayan desaparecido, sino que durante este proyecto ha decidido relacionarse con ellas de una manera diferente.</p><p>Podemos interpretar el cristal como una separación entre la vida cotidiana y ese espacio donde la música nos permite disfrutar sin sentirnos observados. Es transparente porque sabemos que nuestros problemas siguen ahí y es frágil porque basta un recuerdo, una llamada sin respuesta o una pregunta inesperada para devolvernos a ellos. Sin embargo, no necesitamos resolver todas nuestras inquietudes antes de permitirnos disfrutar. La propuesta de Tyler no consiste en ignorar permanentemente nuestras emociones, sino en reconocer que también merecemos momentos donde no controlen cada uno de nuestros movimientos.</p><p><em>DON\'T TAP THE GLASS</em> termina convirtiéndose en una celebración de la libertad personal, la espontaneidad y el placer de vivir el presente. Detrás de la extravagancia de Big Poe, los ritmos contagiosos y la confianza que transmite el álbum, encontramos a un artista que continúa enfrentándose a preguntas sobre el amor, el futuro y sus propias emociones. Quizá esa sea la verdadera razón por la que su invitación resulta tan cercana: incluso alguien que aparentemente lo tiene todo puede necesitar una noche para olvidarse de sus preocupaciones. <strong>La fiesta no elimina lo que existe detrás del cristal. Simplemente nos recuerda que, por un instante, podemos elegir no tocarlo.</strong></p>',1,'publicado','2026-11-02 08:30:46','2026-11-06 13:24:42','2026-11-02 08:30:46'),(5,'<p>En 2022, Arctic Monkeys regresó con un álbum que parecía alejarse todavía más de las guitarras frenéticas que definieron sus primeros años. <em>The Car</em>, su séptimo trabajo de estudio, no intenta recuperar el sonido de <em>Whatever People Say I Am, That\'s What I\'m Not</em> ni repetir el éxito de <em>AM</em>. En cambio, propone un viaje mucho más pausado, elegante y cinematográfico.</p>\n<p>Después de <em>Tranquility Base Hotel &amp; Casino</em>, Alex Turner y compañía continuaron explorando un territorio donde el piano, las cuerdas y los arreglos orquestales adquieren tanto protagonismo como las guitarras.</p>\n<h2>Una película sin pantalla</h2>\n<p>Desde los primeros segundos de <em>There\'d Better Be a Mirrorball</em>, el álbum establece su atmósfera: una despedida envuelta en melancolía, acompañada por arreglos que podrían pertenecer a una película de otra época.</p>\n<p><em>Body Paint</em> es uno de sus momentos más interesantes. Comienza con una delicadeza casi teatral y termina creciendo hasta convertirse en una explosión instrumental que recuerda que Arctic Monkeys todavía sabe construir grandes momentos de rock.</p>\n<p>En <em>Sculptures of Anything Goes</em>, la banda apuesta por una instrumentación más oscura y minimalista. Sus sintetizadores y su ritmo pesado contrastan con la suavidad predominante del disco.</p>\n<h2>El arte de bajar la velocidad</h2>\n<p>Canciones como <em>Big Ideas</em>, <em>Hello You</em> y <em>Perfect Sense</em> muestran a una banda más interesada en construir atmósferas que en perseguir estribillos inmediatos.</p>\n<p>Aquí las canciones parecen desarrollarse como escenas: algunas evocan despedidas, otras conversaciones a media voz y otras simplemente la sensación de observar cómo pasa el tiempo.</p>\n<p>Es un álbum que puede resultar extraño para quien espere el sonido de <em>Brianstorm</em> o <em>R U Mine?</em>, pero precisamente ahí reside su personalidad.</p>\n<h2>El automóvil como escenario</h2>\n<p>La portada muestra un automóvil blanco visto desde arriba, estacionado en la azotea de un edificio. La fotografía fue tomada por el baterista Matt Helders.</p>\n<p>La imagen encaja con el espíritu del álbum: un vehículo inmóvil que, paradójicamente, nos invita a viajar.</p>\n<p><em>The Car</em> no necesita una carretera para llevarnos a otro lugar. Le basta con sus arreglos, su atmósfera y la voz de Alex Turner.</p>\n<h2>El otro lado del vinilo</h2>\n<p>Quizá lo más interesante de <em>The Car</em> sea que Arctic Monkeys ya no parece interesado en demostrar que puede ser la banda de rock más explosiva de su generación.</p>\n<p>Ahora prefiere experimentar, detenerse y construir algo diferente.</p>\n<p>Y aunque no todos quieran acompañarlos en ese recorrido, hay algo admirable en una banda que decide cambiar de dirección sin pedir permiso.</p>\n<p>Porque a veces lo importante no es qué tan rápido conduce un automóvil, sino todo lo que puedes sentir durante el viaje.</p>',1,'publicado','2026-11-05 07:02:14','2026-11-06 13:24:18','2026-11-05 07:05:36');
/*!40000 ALTER TABLE `blog_detalle` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `carrito_item`
--

DROP TABLE IF EXISTS `carrito_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `carrito_item` (
  `id_item` int NOT NULL AUTO_INCREMENT,
  `usr_id` int NOT NULL,
  `producto_id` int NOT NULL,
  `cantidad` int NOT NULL DEFAULT '1',
  `fecha_agregado` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_item`),
  UNIQUE KEY `uq_carrito_usuario_producto` (`usr_id`,`producto_id`),
  KEY `idx_carrito_producto` (`producto_id`),
  CONSTRAINT `fk_carrito_producto` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id_producto`) ON DELETE RESTRICT,
  CONSTRAINT `fk_carrito_usuario` FOREIGN KEY (`usr_id`) REFERENCES `usuario` (`id_usr`) ON DELETE CASCADE,
  CONSTRAINT `chk_carrito_cantidad` CHECK ((`cantidad` > 0))
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `carrito_item`
--

LOCK TABLES `carrito_item` WRITE;
/*!40000 ALTER TABLE `carrito_item` DISABLE KEYS */;
INSERT INTO `carrito_item` VALUES (8,7,2,1,'2026-11-09 07:45:43'),(9,5,2,1,'2026-11-09 12:36:46');
/*!40000 ALTER TABLE `carrito_item` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categoria_producto`
--

DROP TABLE IF EXISTS `categoria_producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categoria_producto` (
  `id_categoria` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_categoria`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categoria_producto`
--

LOCK TABLES `categoria_producto` WRITE;
/*!40000 ALTER TABLE `categoria_producto` DISABLE KEYS */;
INSERT INTO `categoria_producto` VALUES (2,'Álbumes musicales','Productos físicos vinculados a álbumes musicales');
/*!40000 ALTER TABLE `categoria_producto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comentarios`
--

DROP TABLE IF EXISTS `comentarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comentarios` (
  `id_com` int NOT NULL AUTO_INCREMENT,
  `comentario` varchar(500) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `estatus` enum('visible','oculto','eliminado') NOT NULL DEFAULT 'visible',
  `usr_id` int NOT NULL,
  `blog_id` int NOT NULL,
  PRIMARY KEY (`id_com`),
  KEY `idx_comentarios_blog_estado_fecha` (`blog_id`,`estatus`,`fecha`),
  KEY `idx_comentarios_usuario` (`usr_id`),
  CONSTRAINT `fk_comentarios_blog` FOREIGN KEY (`blog_id`) REFERENCES `blog` (`id_blog`) ON DELETE RESTRICT,
  CONSTRAINT `fk_comentarios_usuario` FOREIGN KEY (`usr_id`) REFERENCES `usuario` (`id_usr`) ON DELETE RESTRICT,
  CONSTRAINT `chk_comentarios_no_vacio` CHECK ((char_length(trim(`comentario`)) > 0))
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comentarios`
--

LOCK TABLES `comentarios` WRITE;
/*!40000 ALTER TABLE `comentarios` DISABLE KEYS */;
INSERT INTO `comentarios` VALUES (1,'prueba de comentario','2026-11-09 11:49:38','2026-11-09 11:49:38','visible',5,5);
/*!40000 ALTER TABLE `comentarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `direccion_usuario`
--

DROP TABLE IF EXISTS `direccion_usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `direccion_usuario` (
  `id_direccion` int NOT NULL AUTO_INCREMENT,
  `usr_id` int NOT NULL,
  `etiqueta` varchar(60) NOT NULL DEFAULT 'Casa',
  `direccion` varchar(255) NOT NULL,
  `es_principal` tinyint(1) NOT NULL DEFAULT '0',
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_direccion`),
  KEY `idx_direccion_usuario` (`usr_id`,`es_principal`),
  CONSTRAINT `fk_direccion_usuario` FOREIGN KEY (`usr_id`) REFERENCES `usuario` (`id_usr`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `direccion_usuario`
--

LOCK TABLES `direccion_usuario` WRITE;
/*!40000 ALTER TABLE `direccion_usuario` DISABLE KEYS */;
/*!40000 ALTER TABLE `direccion_usuario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `guardado_publicacion`
--

DROP TABLE IF EXISTS `guardado_publicacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `guardado_publicacion` (
  `id_guardado_publicacion` int NOT NULL AUTO_INCREMENT,
  `usr_id` int NOT NULL,
  `blog_id` int NOT NULL,
  `fecha_guardado` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_guardado_publicacion`),
  UNIQUE KEY `uq_guardado_publicacion_usuario_blog` (`usr_id`,`blog_id`),
  KEY `idx_guardado_publicacion_blog` (`blog_id`),
  CONSTRAINT `fk_guardado_publicacion_blog` FOREIGN KEY (`blog_id`) REFERENCES `blog` (`id_blog`) ON DELETE CASCADE,
  CONSTRAINT `fk_guardado_publicacion_usuario` FOREIGN KEY (`usr_id`) REFERENCES `usuario` (`id_usr`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `guardado_publicacion`
--

LOCK TABLES `guardado_publicacion` WRITE;
/*!40000 ALTER TABLE `guardado_publicacion` DISABLE KEYS */;
INSERT INTO `guardado_publicacion` VALUES (3,5,5,'2026-11-09 22:57:12');
/*!40000 ALTER TABLE `guardado_publicacion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedido`
--

DROP TABLE IF EXISTS `pedido`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedido` (
  `id_pedido` int NOT NULL AUTO_INCREMENT,
  `usr_id` int NOT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estado` enum('pendiente','pagado','preparando','enviado','entregado','cancelado') NOT NULL DEFAULT 'pendiente',
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `direccion_envio` varchar(255) DEFAULT NULL,
  `paypal_order_id` varchar(64) DEFAULT NULL,
  `paypal_capture_id` varchar(64) DEFAULT NULL,
  `fecha_pago` datetime DEFAULT NULL,
  `fecha_actualizacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pedido`),
  UNIQUE KEY `paypal_order_id` (`paypal_order_id`),
  UNIQUE KEY `paypal_capture_id` (`paypal_capture_id`),
  KEY `idx_pedido_usuario_fecha` (`usr_id`,`fecha`),
  CONSTRAINT `fk_pedido_usuario` FOREIGN KEY (`usr_id`) REFERENCES `usuario` (`id_usr`) ON DELETE RESTRICT,
  CONSTRAINT `chk_pedido_total` CHECK ((`total` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedido`
--

LOCK TABLES `pedido` WRITE;
/*!40000 ALTER TABLE `pedido` DISABLE KEYS */;
INSERT INTO `pedido` VALUES (1,5,'2026-11-07 12:53:05','pendiente',498.00,'Calle 7, bosques de la hacienda',NULL,NULL,NULL,'2026-11-07 12:53:05'),(2,5,'2026-11-07 13:17:08','pendiente',498.00,'calle perdida 1','7TT307494D002502H',NULL,NULL,'2026-11-07 13:52:35'),(3,5,'2026-11-09 05:35:34','pendiente',498.00,'calle perdida','5WC37629PS738593F',NULL,NULL,'2026-11-09 05:42:00'),(4,5,'2026-11-09 05:42:15','pendiente',498.00,'calle perdida','37D30233UN796880Y',NULL,NULL,'2026-11-09 05:44:45'),(5,5,'2026-11-09 05:45:30','pendiente',498.00,'calle perdida','1U406472GN958811S',NULL,NULL,'2026-11-09 05:45:33'),(6,5,'2026-11-09 05:47:04','pagado',498.00,'calle perdida','4DX83403M23123740','26V93052R12267924','2026-11-09 05:47:15','2026-11-09 05:47:15'),(7,5,'2026-11-09 05:53:43','pagado',498.00,'calle c','7DJ496282T1717249','00A481852S926474D','2026-11-09 05:53:54','2026-11-09 05:53:54');
/*!40000 ALTER TABLE `pedido` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedido_detalle`
--

DROP TABLE IF EXISTS `pedido_detalle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedido_detalle` (
  `id_detalle` int NOT NULL AUTO_INCREMENT,
  `pedido_id` int NOT NULL,
  `producto_id` int NOT NULL,
  `nombre_producto` varchar(150) NOT NULL,
  `cantidad` int NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_detalle`),
  UNIQUE KEY `uq_pedido_producto` (`pedido_id`,`producto_id`),
  KEY `idx_detalle_producto` (`producto_id`),
  CONSTRAINT `fk_detalle_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedido` (`id_pedido`) ON DELETE RESTRICT,
  CONSTRAINT `fk_detalle_producto` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id_producto`) ON DELETE RESTRICT,
  CONSTRAINT `chk_detalle_cantidad` CHECK ((`cantidad` > 0)),
  CONSTRAINT `chk_detalle_precio` CHECK ((`precio_unitario` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedido_detalle`
--

LOCK TABLES `pedido_detalle` WRITE;
/*!40000 ALTER TABLE `pedido_detalle` DISABLE KEYS */;
INSERT INTO `pedido_detalle` VALUES (1,1,2,'AM — Arctic Monkeys',1,399.00),(2,2,2,'AM — Arctic Monkeys',1,399.00),(3,3,2,'AM — Arctic Monkeys',1,399.00),(4,4,2,'AM — Arctic Monkeys',1,399.00),(5,5,2,'AM — Arctic Monkeys',1,399.00),(6,6,2,'AM — Arctic Monkeys',1,399.00),(7,7,2,'AM — Arctic Monkeys',1,399.00);
/*!40000 ALTER TABLE `pedido_detalle` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `perfil_usuario`
--

DROP TABLE IF EXISTS `perfil_usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `perfil_usuario` (
  `usr_id` int NOT NULL,
  `alias` varchar(50) DEFAULT NULL,
  `biografia` varchar(300) DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`usr_id`),
  CONSTRAINT `fk_perfil_usuario` FOREIGN KEY (`usr_id`) REFERENCES `usuario` (`id_usr`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `perfil_usuario`
--

LOCK TABLES `perfil_usuario` WRITE;
/*!40000 ALTER TABLE `perfil_usuario` DISABLE KEYS */;
INSERT INTO `perfil_usuario` VALUES (2,'Fermin Santos Conde',NULL,NULL,'2026-10-29 07:02:12'),(3,'FerminAdmin',NULL,NULL,'2026-10-29 09:04:02'),(4,'Mani',NULL,NULL,'2026-11-04 11:23:03'),(5,'Fermin',NULL,NULL,'2026-11-04 21:42:28'),(7,'prueba',NULL,NULL,'2026-11-09 07:44:53');
/*!40000 ALTER TABLE `perfil_usuario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `producto`
--

DROP TABLE IF EXISTS `producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `producto` (
  `id_producto` int NOT NULL AUTO_INCREMENT,
  `categoria_id` int NOT NULL,
  `album_id` int DEFAULT NULL,
  `nombre` varchar(150) NOT NULL,
  `slug` varchar(180) DEFAULT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `descripcion` text,
  `precio` decimal(10,2) NOT NULL,
  `stock` int NOT NULL DEFAULT '0',
  `imagen` varchar(255) DEFAULT NULL,
  `imagen_2` varchar(255) DEFAULT NULL,
  `imagen_3` varchar(255) DEFAULT NULL,
  `imagen_4` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_producto`),
  UNIQUE KEY `uq_producto_slug` (`slug`),
  KEY `idx_producto_categoria` (`categoria_id`),
  KEY `idx_producto_nombre` (`nombre`),
  KEY `idx_producto_album` (`album_id`),
  CONSTRAINT `fk_producto_album` FOREIGN KEY (`album_id`) REFERENCES `album` (`id_album`) ON DELETE SET NULL,
  CONSTRAINT `fk_producto_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categoria_producto` (`id_categoria`) ON DELETE RESTRICT,
  CONSTRAINT `chk_producto_precio` CHECK ((`precio` >= 0)),
  CONSTRAINT `chk_producto_stock` CHECK ((`stock` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `producto`
--

LOCK TABLES `producto` WRITE;
/*!40000 ALTER TABLE `producto` DISABLE KEYS */;
INSERT INTO `producto` VALUES (2,2,4,'AM — Arctic Monkeys','am-arctic-monkeys','Arctic Monkeys','Producto físico vinculado al álbum.',399.00,3,'uploads/albums/portadas_arctic/am-arctic-monkeys.webp',NULL,NULL,NULL,1,'2026-11-02 10:00:33','2026-11-09 05:53:54');
/*!40000 ALTER TABLE `producto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rol`
--

DROP TABLE IF EXISTS `rol`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rol` (
  `id_rol` tinyint unsigned NOT NULL,
  `nombre` varchar(60) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_rol`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rol`
--

LOCK TABLES `rol` WRITE;
/*!40000 ALTER TABLE `rol` DISABLE KEYS */;
INSERT INTO `rol` VALUES (1,'Listener','Usuario convencional'),(2,'Artist','Usuario artista'),(3,'Executive Producer','Superadministrador'),(4,'Editor','Integrante del equipo editorial de SIDE B');
/*!40000 ALTER TABLE `rol` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario`
--

DROP TABLE IF EXISTS `usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario` (
  `id_usr` int NOT NULL AUTO_INCREMENT,
  `nom_usr` varchar(100) NOT NULL,
  `mail` varchar(150) NOT NULL,
  `pass` varchar(255) NOT NULL,
  `dir_usr` varchar(255) DEFAULT NULL,
  `tip_usu` tinyint unsigned NOT NULL DEFAULT '1',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_usr`),
  UNIQUE KEY `mail` (`mail`),
  KEY `fk_usuario_rol` (`tip_usu`),
  CONSTRAINT `fk_usuario_rol` FOREIGN KEY (`tip_usu`) REFERENCES `rol` (`id_rol`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario`
--

LOCK TABLES `usuario` WRITE;
/*!40000 ALTER TABLE `usuario` DISABLE KEYS */;
INSERT INTO `usuario` VALUES (1,'Editor SIDE B','editor@sideb.com','$2y$12$tlTNlzBuQJrN/3IRd5iPd.99Q9O3.5AU9HWJi4sfVnwHHqLKwxRxO',NULL,3,1,'2026-10-29 06:23:51','2026-11-02 09:08:36'),(2,'Fermin Santos Conde','fermin@gmail.com','$2y$12$Vt3EvPvaG3SYpSCQNweOh.PJ8pMOykgwnfujoeF50HnAY.hgMU7a2',NULL,1,1,'2026-10-29 07:02:12','2026-10-29 07:02:12'),(3,'FerminAdmin','admin@gmail.com','$2y$12$tlTNlzBuQJrN/3IRd5iPd.99Q9O3.5AU9HWJi4sfVnwHHqLKwxRxO',NULL,3,1,'2026-10-29 09:04:02','2026-10-31 08:17:08'),(4,'Mani','paomeza@gmail.com','$2y$12$mDXkjgiYh6IrXVIIhG1qiODlzCiIbNWa48cqEPbuJ6KxBja7Wx2dq',NULL,1,1,'2026-11-04 11:23:03','2026-11-04 11:23:03'),(5,'Fermin','ferminconde6@gmail.com','$2b$12$o5SdfbnGtHaVQIq8nIV5EuRU/JlM6OooPQzR1bJfzGTGsxzNcOrC6',NULL,1,1,'2026-11-04 21:42:28','2026-11-04 21:42:28'),(7,'prueba','prueba@gmail.com','$2y$12$PEXky0uDpcqkwFn28jaahejSXTnLYrwtOV0PFvPIQW8dpp1tNPaaC',NULL,1,1,'2026-11-09 07:44:53','2026-11-09 07:44:53');
/*!40000 ALTER TABLE `usuario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wishlist_item`
--

DROP TABLE IF EXISTS `wishlist_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wishlist_item` (
  `id_item` int NOT NULL AUTO_INCREMENT,
  `usr_id` int NOT NULL,
  `producto_id` int NOT NULL,
  `fecha_guardado` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_item`),
  UNIQUE KEY `uq_wishlist_usuario_producto` (`usr_id`,`producto_id`),
  KEY `idx_wishlist_producto` (`producto_id`),
  CONSTRAINT `fk_wishlist_producto` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id_producto`) ON DELETE RESTRICT,
  CONSTRAINT `fk_wishlist_usuario` FOREIGN KEY (`usr_id`) REFERENCES `usuario` (`id_usr`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wishlist_item`
--

LOCK TABLES `wishlist_item` WRITE;
/*!40000 ALTER TABLE `wishlist_item` DISABLE KEYS */;
INSERT INTO `wishlist_item` VALUES (1,5,2,'2026-11-04 22:04:35');
/*!40000 ALTER TABLE `wishlist_item` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'side_b'
--

--
-- Dumping routines for database 'side_b'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-09 23:04:07
