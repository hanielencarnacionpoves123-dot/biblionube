# BiblioNube · Sistema de préstamos de biblioteca

Sistema web dinámico (PHP 8 + MariaDB/MySQL) para gestionar una biblioteca:
libros, categorías, lectores y préstamos/devoluciones, con inicio de sesión.
Hecho para practicar el despliegue en AWS (EC2 + RDS + HTTPS).

## Funciones
- Login con contraseña cifrada (`password_hash`) y cierre de sesión.
- Panel con totales (títulos, ejemplares, lectores, préstamos activos y vencidos).
- CRUD de **Categorías**, **Libros** (con búsqueda) y **Lectores**.
- **Préstamos**: registrar, devolver, eliminar; controla ejemplares disponibles y marca los vencidos.
- Seguridad básica: consultas preparadas (PDO), escape de HTML, token CSRF, carpetas internas bloqueadas.

## Estructura
```
index.php          Panel principal
login.php / logout.php
libros.php  categorias.php  lectores.php  prestamos.php
config/conexion.php   <- datos de conexión (con Docker se pasan como variables de entorno)
estado.php            página de diagnóstico
Dockerfile, docker-compose.yml, .env.example, docker/
includes/             funciones, cabecera y pie
assets/estilo.css
bd/biblioteca.sql     estructura + datos de ejemplo (5 tablas)
.htaccess             bloquea /bd, /config, /includes y .git
```

## Usuarios de prueba
| Usuario | Contraseña |
|---|---|
| admin  | admin123   |
| biblio | biblio2026 |

## Instalación rápida
```bash
# 1) Crear la base vacía y el usuario de la aplicación
mysql -h HOST -u admin -p -e "CREATE DATABASE IF NOT EXISTS biblioteca CHARACTER SET utf8mb4;
  CREATE USER 'biblio_app'@'%' IDENTIFIED BY 'Biblio2026Nube';
  GRANT ALL PRIVILEGES ON biblioteca.* TO 'biblio_app'@'%'; FLUSH PRIVILEGES;"
# 2) Importar tablas y datos
mysql -h HOST -u biblio_app -p biblioteca < bd/biblioteca.sql
# 3) Editar config/conexion.php (DB_HOST, DB_USER, DB_PASS)
```
Requiere: Apache 2.4, PHP 8 con `php-mysql`, MySQL 8.4 (RDS) o MariaDB 10.x.

## Docker
```bash
sudo docker build -t biblionube .
sudo docker run -d --name biblionube -p 80:80 \
  -e DB_HOST=ENDPOINT -e DB_NAME=biblioteca -e DB_USER=biblio_app -e DB_PASS=Biblio2026Nube \
  --restart unless-stopped biblionube
```
O con Compose: `cp .env.example .env`, editar `.env` y `sudo docker compose up -d --build`.

## Diagnóstico
`/estado.php` muestra versión de PHP, extensión pdo_mysql, host de BD y si la conexión funciona (y si va cifrada).
