<?php
require_once __DIR__ . '/includes/funciones.php';

if (!empty($_SESSION['usuario_id'])) {
    redirigir('index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $usuario = trim($_POST['usuario'] ?? '');
    $clave   = $_POST['clave'] ?? '';

    $st = db()->prepare('SELECT id, nombre, password_hash, rol FROM usuarios WHERE usuario = ?');
    $st->execute([$usuario]);
    $u = $st->fetch();

    if ($u && password_verify($clave, $u['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = $u['id'];
        $_SESSION['nombre']     = $u['nombre'];
        $_SESSION['rol']        = $u['rol'];
        redirigir('index.php');
    }
    $error = 'Usuario o contraseña incorrectos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ingresar · BiblioNube</title>
  <link rel="stylesheet" href="assets/estilo.css">
</head>
<body class="login-fondo">
  <form method="post" class="login-caja">
    <h1>📚 BiblioNube</h1>
    <p class="sub">Sistema de préstamos de biblioteca</p>
    <?php if ($error): ?><div class="alerta error"><?= e($error) ?></div><?php endif; ?>
    <?= csrf_campo() ?>
    <label>Usuario
      <input name="usuario" required autofocus value="<?= e($_POST['usuario'] ?? '') ?>">
    </label>
    <label>Contraseña
      <input name="clave" type="password" required>
    </label>
    <button type="submit" class="btn primario ancho">Ingresar</button>
    <p class="ayuda">Demo: <b>admin</b> / <b>admin123</b></p>
  </form>
</body>
</html>
