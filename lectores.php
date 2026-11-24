<?php
require_once __DIR__ . '/includes/funciones.php';
requiere_login();
$pdo = db();
$tipos = ['Estudiante', 'Docente', 'Administrativo'];

// ---- Guardar / eliminar ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $id = (int)($_POST['id'] ?? 0);

    if (($_POST['accion'] ?? '') === 'eliminar') {
        try {
            $pdo->prepare('DELETE FROM lectores WHERE id = ?')->execute([$id]);
            flash('ok', 'Lector eliminado.');
        } catch (PDOException $e) {
            flash('error', 'No se puede eliminar: el lector tiene préstamos registrados.');
        }
        redirigir('lectores.php');
    }

    $dni  = trim($_POST['dni'] ?? '');
    $tipo = in_array($_POST['tipo'] ?? '', $tipos, true) ? $_POST['tipo'] : 'Estudiante';
    $d = [$dni, trim($_POST['nombres'] ?? ''), trim($_POST['apellidos'] ?? ''),
          trim($_POST['correo'] ?? ''), trim($_POST['telefono'] ?? ''), $tipo];

    if (!preg_match('/^\d{8}$/', $dni) || $d[1] === '' || $d[2] === '') {
        flash('error', 'DNI (8 dígitos), nombres y apellidos son obligatorios.');
        redirigir('lectores.php');
    }
    try {
        if ($id) {
            $d[] = $id;
            $pdo->prepare('UPDATE lectores SET dni=?, nombres=?, apellidos=?, correo=?, telefono=?, tipo=? WHERE id=?')->execute($d);
            flash('ok', 'Lector actualizado.');
        } else {
            $pdo->prepare('INSERT INTO lectores (dni, nombres, apellidos, correo, telefono, tipo) VALUES (?,?,?,?,?,?)')->execute($d);
            flash('ok', 'Lector registrado.');
        }
    } catch (PDOException $e) {
        flash('error', 'Ya existe un lector con ese DNI.');
    }
    redirigir('lectores.php');
}

// ---- Editar ----
$edit = ['id' => 0, 'dni' => '', 'nombres' => '', 'apellidos' => '', 'correo' => '', 'telefono' => '', 'tipo' => 'Estudiante'];
if (isset($_GET['editar'])) {
    $st = $pdo->prepare('SELECT * FROM lectores WHERE id = ?');
    $st->execute([(int)$_GET['editar']]);
    $edit = $st->fetch() ?: $edit;
}

$lista = $pdo->query(
    "SELECT le.*, (SELECT COUNT(*) FROM prestamos p WHERE p.lector_id = le.id AND p.estado='Prestado') AS activos
       FROM lectores le ORDER BY le.apellidos, le.nombres"
)->fetchAll();

$titulo = 'Lectores';
require __DIR__ . '/includes/header.php';
?>
<h1>Lectores</h1>
<div class="dos-col">
  <form method="post" class="formulario">
    <h2><?= $edit['id'] ? 'Editar lector' : 'Nuevo lector' ?></h2>
    <?= csrf_campo() ?>
    <input type="hidden" name="id" value="<?= e($edit['id']) ?>">
    <label>DNI <input name="dni" required pattern="\d{8}" maxlength="8" value="<?= e($edit['dni']) ?>"></label>
    <label>Nombres <input name="nombres" required maxlength="100" value="<?= e($edit['nombres']) ?>"></label>
    <label>Apellidos <input name="apellidos" required maxlength="100" value="<?= e($edit['apellidos']) ?>"></label>
    <label>Correo <input name="correo" type="email" maxlength="120" value="<?= e($edit['correo']) ?>"></label>
    <label>Teléfono <input name="telefono" maxlength="15" value="<?= e($edit['telefono']) ?>"></label>
    <label>Tipo
      <select name="tipo">
        <?php foreach ($tipos as $t): ?>
          <option <?= $t === $edit['tipo'] ? 'selected' : '' ?>><?= $t ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <button class="btn primario">Guardar</button>
    <?php if ($edit['id']): ?><a class="btn" href="lectores.php">Cancelar</a><?php endif; ?>
  </form>

  <table>
    <tr><th>DNI</th><th>Apellidos y nombres</th><th>Tipo</th><th>Correo</th><th>Préstamos activos</th><th></th></tr>
    <?php foreach ($lista as $l): ?>
    <tr>
      <td><?= e($l['dni']) ?></td>
      <td><?= e($l['apellidos'] . ', ' . $l['nombres']) ?></td>
      <td><?= e($l['tipo']) ?></td>
      <td><?= e($l['correo']) ?></td>
      <td><?= e($l['activos']) ?></td>
      <td class="acciones">
        <a class="btn chico" href="?editar=<?= e($l['id']) ?>">Editar</a>
        <form method="post" onsubmit="return confirm('¿Eliminar este lector?')">
          <?= csrf_campo() ?>
          <input type="hidden" name="accion" value="eliminar">
          <input type="hidden" name="id" value="<?= e($l['id']) ?>">
          <button class="btn chico peligro">Eliminar</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
