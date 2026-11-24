<?php
session_start();
require 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

// Obtener lista de cursos del usuario para el menú lateral
$stmtCursos = $pdo->prepare("
    SELECT c.id, c.nombre, c.seccion, c.codigo_acceso, m.nombre AS materia_nombre, r.nombre AS rol, cu.id AS curso_usuario_id
    FROM curso_usuarios cu
    JOIN cursos c ON cu.curso_id = c.id
    JOIN materias m ON c.materia_id = m.id
    JOIN roles r ON cu.rol_id = r.id
    WHERE cu.usuario_id = ?
");
$stmtCursos->execute([$usuario_id]);
$cursos = $stmtCursos->fetchAll();

$curso_seleccionado = null;
$tareas_curso = [];

if (isset($_GET['curso_id'])) {
    $cid = (int)$_GET['curso_id'];
    foreach ($cursos as $c) {
        if ($c['id'] === $cid) {
            $curso_seleccionado = $c;
            break;
        }
    }
}

// Crear nueva tarea si es docente
if ($curso_seleccionado && $curso_seleccionado['rol'] === 'docente' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nueva_tarea'])) {
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $fecha_limite = !empty($_POST['fecha_limite']) ? $_POST['fecha_limite'] : null;

    if ($titulo) {
        $stmtIns = $pdo->prepare("INSERT INTO tareas (curso_id, titulo, descripcion, fecha_limite) VALUES (?, ?, ?, ?)");
        $stmtIns->execute([$curso_seleccionado['id'], $titulo, $descripcion, $fecha_limite]);
        header("Location: classes.php?curso_id=" . $curso_seleccionado['id']);
        exit();
    }
}

if ($curso_seleccionado) {
    $stmtT = $pdo->prepare("SELECT * FROM tareas WHERE curso_id = ? ORDER BY creado_en DESC");
    $stmtT->execute([$curso_seleccionado['id']]);
    $tareas_curso = $stmtT->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vanguard - Classes</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>

    <header class="gov-header">
        <div class="gov-container">
            <a href="index.php" class="gov-header-brand">
                <h1 class="gov-header-title">Vanguard</h1>
            </a>
        </div>
    </header>

    <div class="gov-container" style="display: flex; gap: var(--gov-spacing-md); margin-top: var(--gov-spacing-lg);">
        
        <aside style="width: 280px; flex-shrink: 0;">
            <nav class="gov-nav" style="border: 1px solid var(--gov-border-color); border-radius: var(--gov-border-radius);">
                <ul class="gov-nav-list" style="flex-direction: column;">
                    <li class="gov-nav-item"><a href="index.php">HOME</a></li>
                    <li class="gov-nav-item"><a href="newsbox.html">NEWS BOX</a></li>
                    <li class="gov-nav-item"><a href="classes.php" class="active">CLASSES</a></li>
                </ul>
            </nav>

            <div class="gov-card" style="margin-top: 1rem; padding: 0.75rem;">
                <?php if (empty($cursos)): ?>
                    <div>
                        <strong>SIN CLASES</strong>
                        <p class="gov-hint" style="margin: 0;">No tienes materias asignadas</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($cursos as $index => $c): ?>
                        <div style="<?= $index < count($cursos) - 1 ? 'border-bottom: 1px solid var(--gov-surface-border); padding-bottom: 0.5rem; margin-bottom: 0.5rem;' : '' ?>">
                            <a href="classes.php?curso_id=<?= $c['id'] ?>" style="text-decoration: none; color: inherit;">
                                <strong><?= htmlspecialchars($c['nombre']) ?></strong>
                                <p class="gov-hint" style="margin: 0;"><?= htmlspecialchars($c['materia_nombre']) ?> (<?= htmlspecialchars($c['rol']) ?>)</p>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div style="margin-top: 2rem;">
                <a href="profile.php" class="gov-btn gov-btn-secondary">SETTINGS</a>
            </div>
        </aside>


        <main style="flex: 1;">
            <?php if (!$curso_seleccionado): ?>
                <div class="gov-card">
                    <h2>Class Details</h2>
                    <p>Selecciona una asignatura del menú lateral para ver el contenido detallado.</p>
                </div>
            <?php else: ?>
                <div class="gov-card" style="margin-bottom: 1rem;">
                    <h2><?= htmlspecialchars($curso_seleccionado['nombre']) ?> - <?= htmlspecialchars($curso_seleccionado['materia_nombre']) ?></h2>
                    <p class="gov-hint">Sección: <?= htmlspecialchars($curso_seleccionado['seccion'] ?? 'General') ?> | Código de acceso: <strong><?= htmlspecialchars($curso_seleccionado['codigo_acceso']) ?></strong></p>
                </div>

                <?php if ($curso_seleccionado['rol'] === 'docente'): ?>
                    <div class="gov-card" style="margin-bottom: 1rem;">
                        <h3>Asignar Nueva Tarea</h3>
                        <form method="POST" action="classes.php?curso_id=<?= $curso_seleccionado['id'] ?>">
                            <input type="hidden" name="nueva_tarea" value="1">
                            <div class="gov-form-group">
                                <label class="gov-label" for="titulo">Título de la tarea</label>
                                <input type="text" id="titulo" name="titulo" class="gov-input" required>
                            </div>
                            <div class="gov-form-group">
                                <label class="gov-label" for="descripcion">Consigna / Descripción</label>
                                <textarea id="descripcion" name="descripcion" class="gov-input" rows="3"></textarea>
                            </div>
                            <div class="gov-form-group">
                                <label class="gov-label" for="fecha_limite">Fecha límite</label>
                                <input type="datetime-local" id="fecha_limite" name="fecha_limite" class="gov-input">
                            </div>
                            <button type="submit" class="gov-btn gov-btn-primary">Publicar Tarea</button>
                        </form>
                    </div>
                <?php endif; ?>

                <div class="gov-card">
                    <h3>Tareas de la asignatura</h3>
                    <?php if (empty($tareas_curso)): ?>
                        <p>No hay tareas publicadas en esta clase.</p>
                    <?php else: ?>
                        <div class="gov-table-wrapper">
                            <table class="gov-table">
                                <thead>
                                    <tr>
                                        <th>Tarea</th>
                                        <th>Fecha Límite</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tareas_curso as $t): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($t['titulo']) ?></strong>
                                                <p class="gov-hint" style="margin: 0;"><?= htmlspecialchars($t['descripcion'] ?? '') ?></p>
                                            </td>
                                            <td><span class="gov-badge gov-badge-primary"><?= $t['fecha_limite'] ? date('d/m/Y H:i', strtotime($t['fecha_limite'])) : 'Sin límite' ?></span></td>
                                            <td>
                                                <a href="task_detail.php?id=<?= $t['id'] ?>" class="gov-btn gov-btn-primary">
                                                    <?= $curso_seleccionado['rol'] === 'docente' ? 'Ver entregas y comentar' : 'Entregar tarea' ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <footer class="gov-footer">
        <div class="gov-container">
            <ul class="gov-footer-links">
                <li><a href="#">Ayuda</a></li>
                <li><a href="#">Términos y Condiciones</a></li>
            </ul>
        </div>
    </footer>

</body>
</html>