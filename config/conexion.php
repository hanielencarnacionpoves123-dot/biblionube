<?php
/*
 * ÚNICO archivo que se edita al desplegar con Apache.
 * En AWS: DB_HOST = endpoint de RDS (ej. biblionube-db.xxxx.us-east-2.rds.amazonaws.com)
 *
 * Con Docker no se edita: los valores llegan como variables de entorno
 * (DB_HOST, DB_NAME, DB_USER, DB_PASS) y tienen prioridad sobre estos.
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'biblioteca');
define('DB_USER', 'root');
define('DB_PASS', '');

/* Certificado de RDS para conectar cifrado (SSL/TLS). Si el archivo existe, se usa. */
define('DB_SSL_CA', '/etc/ssl/certs/rds-global-bundle.pem');

/** Devuelve el valor de configuración: primero variable de entorno, luego la constante. */
function cfg(string $k): string
{
    $v = getenv($k);
    return ($v !== false && $v !== '') ? $v : constant($k);
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT            => 5,
        ];
        if (is_readable(cfg('DB_SSL_CA'))) {
            $opciones[PDO::MYSQL_ATTR_SSL_CA] = cfg('DB_SSL_CA');
            $opciones[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        }
        try {
            $pdo = new PDO(
                'mysql:host=' . cfg('DB_HOST') . ';port=3306;dbname=' . cfg('DB_NAME') . ';charset=utf8mb4',
                cfg('DB_USER'),
                cfg('DB_PASS'),
                $opciones
            );
        } catch (PDOException $e) {
            error_log('Error de conexion BD: ' . $e->getMessage());
            http_response_code(500);
            exit('<h2 style="font-family:sans-serif">No se pudo conectar a la base de datos.</h2>'
               . '<p style="font-family:sans-serif">Revise config/conexion.php (o las variables del contenedor) '
               . 'y el grupo de seguridad del puerto 3306. Detalle en el log de Apache / docker logs.</p>');
        }
    }
    return $pdo;
}
