<?php
require_once __DIR__ . '/includes/funciones.php';
requiere_login();

$pdo = db();
$tot = [
    'libros'    => $pdo->query('SELECT COALESCE(SUM(stock),0) FROM libros')->fetchColumn(),
    'titulos'   => $pdo->query('SELECT COUNT(*) FROM libros')->fetchColumn(),
    'lectores'  => $pdo->query('SELECT COUNT(*) FROM lectores')->fetchColumn(),
    'activos'   => $pdo->query("SELECT COUNT(*) FROM prestamos WHERE estado='Prestado'")->fetchColumn(),
    'vencidos'  => $pdo->query("SELECT COUNT(*) FROM prestamos WHERE estado='Prestado' AND fecha_limite < CURDATE()")->fetchColumn(),
];

$ultimos = $pdo->query(
    "SELECT p.id, l.titulo, CONCAT(le.nombres,' ',le.apellidos) AS lector,
            p.fecha_prestamo, p.fecha_limite, p.estado
       FROM prestamos p
       JOIN libros l   ON l.id = p.libro_id
       JOIN lectores le ON le.id = p.lector_id
   ORDER BY p.id DESC LIMIT 5"
)->fetchAll();

$version = $pdo->query('SELECT VERSION()')->fetchColumn();

$titulo = 'Inicio';
require __DIR__ . '/includes/header.php';
?>
<h1>Panel principal</h1>
<div class="tarjetas">
  <div class="tarjeta"><span><?= e($tot['titulos']) ?></span>Títulos</div>
  <div class="tarjeta"><span><?= e($tot['libros']) ?></span>Ejemplares</div>
  <div class="tarjeta"><span><?= e($tot['lectores']) ?></span>Lectores</div>
  <div class="tarjeta"><span><?= e($tot['activos']) ?></span>Préstamos activos</div>
  <div class="tarjeta roja"><span><?= e($tot['vencidos']) ?></span>Vencidos</div>
</div>

<h2>Últimos préstamos</h2>
<table>
  <tr><th>#</th><th>Libro</th><th>Lector</th><th>Préstamo</th><th>Límite</th><th>Estado</th></tr>
  <?php foreach ($ultimos as $p): ?>
  <tr>
    <td><?= e($p['id']) ?></td>
    <td><?= e($p['titulo']) ?></td>
    <td><?= e($p['lector']) ?></td>
    <td class="nw"><?= e($p['fecha_prestamo']) ?></td>
    <td class="nw"><?= e($p['fecha_limite']) ?></td>
    <td><span class="etiqueta <?= $p['estado'] === 'Prestado' ? 'naranja' : 'verde' ?>"><?= e($p['estado']) ?></span></td>
  </tr>
  <?php endforeach; ?>
</table>

<p class="info">Base de datos conectada: <b><?= e(cfg('DB_HOST')) ?></b> · Motor <?= e($version) ?></p>
<?php require __DIR__ . '/includes/footer.php'; ?>
