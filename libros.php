<?php
require_once __DIR__ . '/includes/funciones.php';
requiere_login();
$pdo = db();

// ---- Guardar / eliminar ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $id = (int)($_POST['id'] ?? 0);

    if (($_POST['accion'] ?? '') === 'eliminar') {
        try {
            $pdo->prepare('DELETE FROM libros WHERE id = ?')->execute([$id]);
            flash('ok', 'Libro eliminado.');
        } catch (PDOException $e) {
            flash('error', 'No se puede eliminar: el libro tiene préstamos registrados.');
        }
        redirigir('libros.php');
    }

    $d = [
        trim($_POST['isbn'] ?? ''),
        trim($_POST['titulo'] ?? ''),
        trim($_POST['autor'] ?? ''),
        ($_POST['anio'] ?? '') === '' ? null : (int)$_POST['anio'],
        ($_POST['categoria_id'] ?? '') === '' ? null : (int)$_POST['categoria_id'],
        max(0, (int)($_POST['stock'] ?? 1)),
    ];
    if ($d[0] === '' || $d[1] === '' || $d[2] === '') {
        flash('error', 'ISBN, título y autor son obligatorios.');
        redirigir('libros.php');
    }
    try {
        if ($id) {
            $d[] = $id;
            $pdo->prepare('UPDATE libros SET isbn=?, titulo=?, autor=?, anio=?, categoria_id=?, stock=? WHERE id=?')->execute($d);
            flash('ok', 'Libro actualizado.');
        } else {
            $pdo->prepare('INSERT INTO libros (isbn, titulo, autor, anio, categoria_id, stock) VALUES (?,?,?,?,?,?)')->execute($d);
            flash('ok', 'Libro registrado.');
        }
    } catch (PDOException $e) {
        flash('error', 'Ya existe un libro con ese ISBN.');
    }
    redirigir('libros.php');
}

// ---- Editar ----
$edit = ['id' => 0, 'isbn' => '', 'titulo' => '', 'autor' => '', 'anio' => '', 'categoria_id' => '', 'stock' => 1];
if (isset($_GET['editar'])) {
    $st = $pdo->prepare('SELECT * FROM libros WHERE id = ?');
    $st->execute([(int)$_GET['editar']]);
    $edit = $st->fetch() ?: $edit;
}

$categorias = $pdo->query('SELECT id, nombre FROM categorias ORDER BY nombre')->fetchAll();

// ---- Listado con búsqueda ----
$q = trim($_GET['q'] ?? '');
$st = $pdo->prepare(
    "SELECT l.*, c.nombre AS categoria,
            l.stock - (SELECT COUNT(*) FROM prestamos p WHERE p.libro_id = l.id AND p.estado = 'Prestado') AS disponibles
       FROM libros l LEFT JOIN categorias c ON c.id = l.categoria_id
      WHERE l.titulo LIKE ? OR l.autor LIKE ? OR l.isbn LIKE ?
   ORDER BY l.titulo"
);
$st->execute(["%$q%", "%$q%", "%$q%"]);
$lista = $st->fetchAll();

$titulo = 'Libros';
require __DIR__ . '/includes/header.php';
?>
<h1>Libros</h1>
<div class="dos-col">
  <form method="post" class="formulario">
    <h2><?= $edit['id'] ? 'Editar libro' : 'Nuevo libro' ?></h2>
    <?= csrf_campo() ?>
    <input type="hidden" name="id" value="<?= e($edit['id']) ?>">
    <label>ISBN <input name="isbn" required maxlength="20" value="<?= e($edit['isbn']) ?>"></label>
    <label>Título <input name="titulo" required maxlength="200" value="<?= e($edit['titulo']) ?>"></label>
    <label>Autor <input name="autor" required maxlength="150" value="<?= e($edit['autor']) ?>"></label>
    <label>Año <input name="anio" type="number" min="1500" max="2100" value="<?= e($edit['anio']) ?>"></label>
    <label>Categoría
      <select name="categoria_id">
        <option value="">(Sin categoría)</option>
        <?php foreach ($categorias as $c): ?>
          <option value="<?= e($c['id']) ?>" <?= (string)$c['id'] === (string)$edit['categoria_id'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Ejemplares (stock) <input name="stock" type="number" min="0" value="<?= e($edit['stock']) ?>"></label>
    <button class="btn primario">Guardar</button>
    <?php if ($edit['id']): ?><a class="btn" href="libros.php">Cancelar</a><?php endif; ?>
  </form>

  <div>
    <form method="get" class="buscador">
      <input name="q" placeholder="Buscar por título, autor o ISBN" value="<?= e($q) ?>">
      <button class="btn">Buscar</button>
    </form>
    <table>
      <tr><th>ISBN</th><th>Título</th><th>Autor</th><th>Año</th><th>Categoría</th><th>Disp./Total</th><th></th></tr>
      <?php foreach ($lista as $l): ?>
      <tr>
        <td><?= e($l['isbn']) ?></td>
        <td><?= e($l['titulo']) ?></td>
        <td><?= e($l['autor']) ?></td>
        <td><?= e($l['anio']) ?></td>
        <td><?= e($l['categoria'] ?? '-') ?></td>
        <td><?= e($l['disponibles']) ?> / <?= e($l['stock']) ?></td>
        <td class="acciones">
          <a class="btn chico" href="?editar=<?= e($l['id']) ?>">Editar</a>
          <form method="post" onsubmit="return confirm('¿Eliminar este libro?')">
            <?= csrf_campo() ?>
            <input type="hidden" name="accion" value="eliminar">
            <input type="hidden" name="id" value="<?= e($l['id']) ?>">
            <button class="btn chico peligro">Eliminar</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$lista): ?><tr><td colspan="7">No hay libros.</td></tr><?php endif; ?>
    </table>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
