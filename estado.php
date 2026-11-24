<?php
/* Página de diagnóstico: comprueba PHP y la conexión a la base de datos. */
require_once __DIR__ . '/config/conexion.php';
header('Content-Type: text/plain; charset=utf-8');

echo "BiblioNube - estado\n";
echo "Servidor : " . gethostname() . "\n";
echo "PHP      : " . PHP_VERSION . "\n";
echo "pdo_mysql: " . (extension_loaded('pdo_mysql') ? 'OK' : 'FALTA (instalar php-mysql)') . "\n";
echo "BD host  : " . cfg('DB_HOST') . "\n";

try {
    $opc = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5];
    if (is_readable(cfg('DB_SSL_CA'))) {
        $opc[PDO::MYSQL_ATTR_SSL_CA] = cfg('DB_SSL_CA');
        $opc[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }
    $pdo = new PDO('mysql:host=' . cfg('DB_HOST') . ';port=3306;dbname=' . cfg('DB_NAME'), cfg('DB_USER'), cfg('DB_PASS'), $opc);
    $v   = $pdo->query('SELECT VERSION()')->fetchColumn();
    $n   = $pdo->query('SELECT COUNT(*) FROM libros')->fetchColumn();
    $ssl = $pdo->query("SHOW SESSION STATUS LIKE 'Ssl_cipher'")->fetch(PDO::FETCH_NUM)[1] ?? '';
    echo "BD       : OK (motor $v, $n libros, SSL: " . ($ssl ?: 'no') . ")\n";
} catch (PDOException $e) {
    http_response_code(500);
    echo "BD       : ERROR -> " . $e->getMessage() . "\n";
}
