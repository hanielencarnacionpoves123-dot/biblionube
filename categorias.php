<?php
require_once __DIR__ . '/includes/funciones.php';
requiere_login();
$pdo = db();

// ---- Guardar / eliminar ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $id = (int)($_POST['id'] ?? 0);

    if (($_POST['accion'] ?? '') === 'eliminar') {
        $pdo->prepare('DELETE FROM categorias WHERE id = ?')->execute([$id]);
        flash('ok', 'Categoría eliminada.');
        redirigir('categorias.php');
    }

    $nombre = trim($_POST['nombre'] ?? '');
    $desc   = trim($_POST['descripcion'] ?? '');
    if ($nombre === '') {
        flash('error', 'El nombre es obligatorio.');
        redirigir('categorias.php');
    }
    try {
        if ($id) {
            $pdo->prepare('UPDATE categorias SET nombre=?, descripcion=? WHERE id=?')->execute([$nombre, $desc, $id]);
            flash('ok', 'Categoría actualizada.');
        } else {
            $pdo->prepare('INSERT INTO categorias (nombre, descripcion) VALUES (?, ?)')->execute([$nombre, $desc]);
            flash('ok', 'Categoría registrada.');
        }
    } catch (PDOException $e) {
        flash('error', 'Ya existe una categoría con ese nombre.');
    }
    redirigir('categorias.php');
}

// ---- Editar ----
$edit = ['id' => 0, 'nombre' => '', 'descripcion' => ''];
if (isset($_GET['editar'])) {
    $st = $pdo->prepare('SELECT * FROM categorias WHERE id = ?');
    $st->execute([(int)$_GET['editar']]);
    $edit = $st->fetch() ?: $edit;
}

$lista = $pdo->query(
    'SELECT c.*, COUNT(l.id) AS libros
       FROM categorias c LEFT JOIN libros l ON l.categoria_id = c.id
   GROUP BY c.id ORDER BY c.nombre'
)->fetchAll();

$titulo = 'Categorías';
require __DIR__ . '/includes/header.php';
?>
<h1>Categorías</h1>
<div class="dos-col">
  <form method="post" class="formulario">
    <h2><?= $edit['id'] ? 'Editar categoría' : 'Nueva categoría' ?></h2>
    <?= csrf_campo() ?>
    <input type="hidden" name="id" value="<?= e($edit['id']) ?>">
    <label>Nombre <input name="nombre" required maxlength="80" value="<?= e($edit['nombre']) ?>"></label>
    <label>Descripción <input name="descripcion" maxlength="255" value="<?= e($edit['descripcion']) ?>"></label>
    <button class="btn primario">Guardar</button>
    <?php if ($edit['id']): ?><a class="btn" href="categorias.php">Cancelar</a><?php endif; ?>
  </form>

  <table>
    <tr><th>Nombre</th><th>Descripción</th><th>Libros</th><th></th></tr>
    <?php foreach ($lista as $c): ?>
    <tr>
      <td><?= e($c['nombre']) ?></td>
      <td><?= e($c['descripcion']) ?></td>
      <td><?= e($c['libros']) ?></td>
      <td class="acciones">
        <a class="btn chico" href="?editar=<?= e($c['id']) ?>">Editar</a>
        <form method="post" onsubmit="return confirm('¿Eliminar esta categoría?')">
          <?= csrf_campo() ?>
          <input type="hidden" name="accion" value="eliminar">
          <input type="hidden" name="id" value="<?= e($c['id']) ?>">
          <button class="btn chico peligro">Eliminar</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
