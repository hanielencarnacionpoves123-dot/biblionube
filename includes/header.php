<?php
/** @var string $titulo */
$actual = basename($_SERVER['PHP_SELF']);
$menu = [
    'index.php'      => 'Inicio',
    'libros.php'     => 'Libros',
    'categorias.php' => 'Categorías',
    'lectores.php'   => 'Lectores',
    'prestamos.php'  => 'Préstamos',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($titulo ?? 'BiblioNube') ?> · BiblioNube</title>
  <link rel="stylesheet" href="assets/estilo.css">
</head>
<body>
<header class="barra">
  <div class="marca">📚 BiblioNube</div>
  <nav>
    <?php foreach ($menu as $url => $nombre): ?>
      <a href="<?= $url ?>" class="<?= $actual === $url ? 'activo' : '' ?>"><?= $nombre ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="sesion">
    <?= e($_SESSION['nombre'] ?? '') ?> · <a href="logout.php">Salir</a>
  </div>
</header>
<main class="contenedor">
<?= mostrar_flash() ?>
