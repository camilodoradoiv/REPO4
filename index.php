<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/db.php';

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
function h(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function home(): never { header('Location: index.php'); exit; }

$error = '';
$form = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
        http_response_code(403);
        exit('Solicitud no válida. Recarga la página e inténtalo de nuevo.');
    }
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id) { $s = $pdo->prepare('DELETE FROM articulos WHERE id=?'); $s->execute([$id]); }
        home();
    }
    if ($action === 'save') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: null;
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $marca = trim((string)($_POST['marca'] ?? ''));
        $unidadesRaw = trim((string)($_POST['unidades'] ?? ''));
        $ubicacion = trim((string)($_POST['ubicacion'] ?? ''));
        $unidades = filter_var($unidadesRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($nombre === '' || $marca === '' || $ubicacion === '' || $unidades === false) {
            $error = 'Completa todos los campos y usa un entero igual o mayor que cero para unidades.';
            $form = compact('id', 'nombre', 'marca', 'unidadesRaw', 'ubicacion');
        } elseif (mb_strlen($nombre) > 180 || mb_strlen($marca) > 120 || mb_strlen($ubicacion) > 180) {
            $error = 'El nombre admite hasta 180 caracteres, la marca 120 y la ubicación 180.';
            $form = compact('id', 'nombre', 'marca', 'unidadesRaw', 'ubicacion');
        } else {
            if ($id) {
                $s = $pdo->prepare('UPDATE articulos SET nombre=?, marca=?, unidades=?, ubicacion=? WHERE id=?');
                $s->execute([$nombre, $marca, $unidades, $ubicacion, $id]);
            } else {
                $s = $pdo->prepare('INSERT INTO articulos (nombre, marca, unidades, ubicacion) VALUES (?, ?, ?, ?)');
                $s->execute([$nombre, $marca, $unidades, $ubicacion]);
            }
            home();
        }
    }
}
if (isset($_GET['editar']) && !$form) {
    $id = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT);
    if ($id) {
        $s = $pdo->prepare('SELECT id,nombre,marca,unidades,ubicacion FROM articulos WHERE id=?');
        $s->execute([$id]);
        if ($r = $s->fetch()) { $form = $r; $form['unidadesRaw'] = (string)$r['unidades']; }
    }
}
$q = trim((string)($_GET['q'] ?? ''));
if ($q !== '') {
    $s = $pdo->prepare('SELECT * FROM articulos WHERE nombre LIKE ? OR marca LIKE ? OR ubicacion LIKE ? ORDER BY creado_en DESC,id DESC');
    $like = '%' . $q . '%'; $s->execute([$like, $like, $like]); $articulos = $s->fetchAll();
} else {
    $articulos = $pdo->query('SELECT * FROM articulos ORDER BY creado_en DESC,id DESC')->fetchAll();
}
$totalUnidades = array_sum(array_map(static fn($a) => (int)$a['unidades'], $articulos));
?>
<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Gestión de artículos</title>
<style>
:root{--blue:#2457c5;--ink:#172033;--muted:#65718a;--line:#e2e7f0;--bg:#f4f7fb}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--ink);font:15px/1.5 system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif}
header{background:linear-gradient(120deg,#183c91,#2d6be0);color:white;padding:28px 20px}.wrap{max-width:1180px;margin:auto;padding:0 18px}header h1{margin:0;font-size:27px}header p{margin:5px 0 0;color:#dbe7ff}
main{padding:25px 0 45px}.layout{display:grid;grid-template-columns:320px 1fr;gap:20px;align-items:start}.panel{background:white;border:1px solid var(--line);border-radius:14px;padding:20px;box-shadow:0 5px 20px #1a2b4d0a}
h2{font-size:18px;margin:0 0 16px}label{display:block;font-weight:650;margin:13px 0 6px}input[type=text],input[type=number]{width:100%;border:1px solid #cbd4e3;border-radius:8px;padding:10px 11px;font:inherit;background:white}
.btn{display:inline-block;border:0;border-radius:8px;padding:10px 14px;background:var(--blue);color:white;font:inherit;font-weight:650;text-decoration:none;cursor:pointer}.secondary{background:#eaf0fc!important;color:#234a9f!important}.danger{background:#fff0f0!important;color:#a52121!important}
.actions{display:flex;gap:8px;flex-wrap:wrap}.error{background:#fff0f0;border:1px solid #ffd0d0;color:#9d2020;border-radius:8px;padding:10px;margin-bottom:14px}
.search{display:flex;gap:8px;margin-bottom:15px}.search input{flex:1;min-width:0}.table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:12px 10px;border-bottom:1px solid var(--line);vertical-align:top}th{font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:var(--muted)}td small{color:var(--muted)}.empty{text-align:center;padding:34px 10px;color:var(--muted)}
.stats{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:16px}.stat{background:#f5f8ff;border:1px solid #e1e9fc;border-radius:10px;padding:10px 14px}.stat strong{font-size:19px;display:block}.stat span{font-size:12px;color:var(--muted)}
@media(max-width:850px){.layout{grid-template-columns:1fr}.panel{padding:16px}.search{flex-wrap:wrap}.search input{flex-basis:100%}}
</style></head><body>
<header><div class="wrap"><h1>Gestión de artículos</h1><p>Controla artículos, marcas, cantidades y ubicaciones.</p></div></header>
<main class="wrap"><div class="layout">
<section class="panel"><h2><?= $form ? 'Editar artículo' : 'Registrar artículo' ?></h2>
<?php if ($error): ?><div class="error"><?= h($error) ?></div><?php endif; ?>
<form method="post" action="index.php">
<input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="save">
<input type="hidden" name="id" value="<?= h(isset($form['id']) ? (string)$form['id'] : '') ?>">
<label for="nombre">Nombre</label><input id="nombre" name="nombre" type="text" maxlength="180" required value="<?= h($form['nombre'] ?? '') ?>" placeholder="Nombre del artículo">
<label for="marca">Marca</label><input id="marca" name="marca" type="text" maxlength="120" required value="<?= h($form['marca'] ?? '') ?>" placeholder="Marca">
<label for="unidades">Unidades</label><input id="unidades" name="unidades" type="number" min="0" step="1" required value="<?= h($form['unidadesRaw'] ?? (isset($form['unidades']) ? (string)$form['unidades'] : '0')) ?>">
<label for="ubicacion">Ubicación</label><input id="ubicacion" name="ubicacion" type="text" maxlength="180" required value="<?= h($form['ubicacion'] ?? '') ?>" placeholder="Bodega, estante o ubicación">
<div class="actions" style="margin-top:16px"><button class="btn" type="submit"><?= $form ? 'Guardar cambios' : 'Crear artículo' ?></button><?php if ($form): ?><a class="btn secondary" href="index.php">Cancelar</a><?php endif; ?></div>
</form></section>
<section class="panel"><h2>Inventario registrado</h2>
<div class="stats"><div class="stat"><strong><?= count($articulos) ?></strong><span>Artículos listados</span></div><div class="stat"><strong><?= number_format($totalUnidades,0,',','.') ?></strong><span>Unidades en los resultados</span></div></div>
<form class="search" method="get" action="index.php"><input type="text" name="q" value="<?= h($q) ?>" placeholder="Buscar por nombre, marca o ubicación"><button class="btn" type="submit">Buscar</button><?php if ($q !== ''): ?><a class="btn secondary" href="index.php">Limpiar</a><?php endif; ?></form>
<?php if (!$articulos): ?><div class="empty"><?= $q !== '' ? 'No hay resultados para esa búsqueda.' : 'Todavía no hay artículos. Usa el formulario para registrar el primero.' ?></div>
<?php else: ?><div class="table-wrap"><table><thead><tr><th>ID</th><th>Nombre</th><th>Marca</th><th>Unidades</th><th>Ubicación</th><th>Acciones</th></tr></thead><tbody>
<?php foreach ($articulos as $a): ?><tr><td>#<?= (int)$a['id'] ?></td><td><strong><?= h($a['nombre']) ?></strong><br><small><?= h(date('d/m/Y H:i',strtotime($a['creado_en']))) ?></small></td><td><?= h($a['marca']) ?></td><td><?= number_format((int)$a['unidades'],0,',','.') ?></td><td><?= h($a['ubicacion']) ?></td><td><div class="actions"><a class="btn secondary" href="index.php?editar=<?= (int)$a['id'] ?>">Editar</a>
<form method="post" action="index.php" onsubmit="return confirm('¿Seguro que deseas eliminar este artículo?');"><input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><button class="btn danger" type="submit">Eliminar</button></form></div></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</section></div></main></body></html>
