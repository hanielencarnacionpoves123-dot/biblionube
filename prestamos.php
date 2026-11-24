<?php
require_once __DIR__ . '/includes/funciones.php';
requiere_login();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $accion = $_POST['accion'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    if ($accion === 'devolver') {
        $pdo->prepare("UPDATE prestamos SET estado='Devuelto', fecha_devolucion=CURDATE() WHERE id=? AND estado='Prestado'")
            ->execute([$id]);
        flash('ok', 'Devolución registrada.');
        redirigir('prestamos.php');
    }

    if ($accion === 'eliminar') {
        $pdo->prepare('DELETE FROM prestamos WHERE id = ?')->execute([$id]);
        flash('ok', 'Préstamo eliminado.');
        redirigir('prestamos.php');
    }

    // Nuevo préstamo
    $libro  = (int)($_POST['libro_id'] ?? 0);
    $lector = (int)($_POST['lector_id'] ?? 0);
    $dias   = max(1, min(30, (int)($_POST['dias'] ?? 7)));
    $obs    = trim($_POST['observacion'] ?? '');

    // Verificar que haya ejemplares disponibles
    $st = $pdo->prepare(
        "SELECT l.stock - (SELECT COUNT(*) FROM prestamos p WHERE p.libro_id = l.id AND p.estado='Prestado')
           FROM libros l WHERE l.id = ?"
    );
    $st->execute([$libro]);
    $disp = $st->fetchColumn();

    if ($disp === false || $lector === 0) {
        flash('error', 'Seleccione un libro y un lector válidos.');
    } elseif ((int)$disp <= 0) {
        flash('error', 'No hay ejemplares disponibles de ese libro.');
    } else {
        $pdo->prepare(
            "INSERT INTO prestamos (libro_id, lector_id, usuario_id, fecha_prestamo, fecha_limite, observacion)
             VALUES (?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL ? DAY), ?)"
        )->execute([$libro, $lector, $_SESSION['usuario_id'], $dias, $obs]);
        flash('ok', 'Préstamo registrado.');
    }
    redirigir('prestamos.php');
}

$libros   = $pdo->query('SELECT id, titulo FROM libros ORDER BY titulo')->fetchAll();
$lectores = $pdo->query("SELECT id, CONCAT(apellidos, ', ', nombres, ' (', dni, ')') AS nombre FROM lectores ORDER BY apellidos")->fetchAll();

$filtro = $_GET['estado'] ?? 'todos';
$sql = "SELECT p.*, l.titulo, CONCAT(le.nombres, ' ', le.apellidos) AS lector, u.usuario,
               (p.estado = 'Prestado' AND p.fecha_limite < CURDATE()) AS vencido
          FROM prestamos p
          JOIN libros l    ON l.id = p.libro_id
          JOIN lectores le ON le.id = p.lector_id
     LEFT JOIN usuarios u  ON u.id = p.usuario_id";
if ($filtro === 'Prestado' || $filtro === 'Devuelto') {
    $st = $pdo->prepare($sql . ' WHERE p.estado = ? ORDER BY p.id DESC');
    $st->execute([$filtro]);
} else {
    $st = $pdo->query($sql . ' ORDER BY p.id DESC');
}
$lista = $st->fetchAll();

$titulo = 'Préstamos';
require __DIR__ . '/includes/header.php';
?>
<h1>Préstamos</h1>
<div class="dos-col">
  <form method="post" class="formulario">
    <h2>Nuevo préstamo</h2>
    <?= csrf_campo() ?>
    <label>Libro
      <select name="libro_id" required>
        <option value="">Seleccione...</option>
        <?php foreach ($libros as $l): ?><option value="<?= e($l['id']) ?>"><?= e($l['titulo']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>Lector
      <select name="lector_id" required>
        <option value="">Seleccione...</option>
        <?php foreach ($lectores as $l): ?><option value="<?= e($l['id']) ?>"><?= e($l['nombre']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>Días de préstamo <input name="dias" type="number" min="1" max="30" value="7"></label>
    <label>Observación <input name="observacion" maxlength="255"></label>
    <button class="btn primario">Registrar préstamo</button>
  </form>

  <div>
    <div class="filtros">
      <?php foreach (['todos' => 'Todos', 'Prestado' => 'Prestados', 'Devuelto' => 'Devueltos'] as $k => $v): ?>
        <a class="btn chico <?= $filtro === $k ? 'primario' : '' ?>" href="?estado=<?= $k ?>"><?= $v ?></a>
      <?php endforeach; ?>
    </div>
    <table>
      <tr><th>#</th><th>Libro</th><th>Lector</th><th>Préstamo</th><th>Límite</th><th>Devolución</th><th>Estado</th><th>Atendió</th><th></th></tr>
      <?php foreach ($lista as $p): ?>
      <tr>
        <td><?= e($p['id']) ?></td>
        <td><?= e($p['titulo']) ?></td>
        <td><?= e($p['lector']) ?></td>
        <td class="nw"><?= e($p['fecha_prestamo']) ?></td>
        <td class="nw"><?= e($p['fecha_limite']) ?></td>
        <td class="nw"><?= e($p['fecha_devolucion'] ?? '-') ?></td>
        <td>
          <?php if ($p['estado'] === 'Devuelto'): ?>
            <span class="etiqueta verde">Devuelto</span>
          <?php elseif ($p['vencido']): ?>
            <span class="etiqueta roja">Vencido</span>
          <?php else: ?>
            <span class="etiqueta naranja">Prestado</span>
          <?php endif; ?>
        </td>
        <td><?= e($p['usuario'] ?? '-') ?></td>
        <td class="acciones">
          <?php if ($p['estado'] === 'Prestado'): ?>
          <form method="post">
            <?= csrf_campo() ?>
            <input type="hidden" name="accion" value="devolver">
            <input type="hidden" name="id" value="<?= e($p['id']) ?>">
            <button class="btn chico primario">Devolver</button>
          </form>
          <?php endif; ?>
          <form method="post" onsubmit="return confirm('¿Eliminar este préstamo?')">
            <?= csrf_campo() ?>
            <input type="hidden" name="accion" value="eliminar">
            <input type="hidden" name="id" value="<?= e($p['id']) ?>">
            <button class="btn chico peligro">Eliminar</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$lista): ?><tr><td colspan="9">No hay préstamos.</td></tr><?php endif; ?>
    </table>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
