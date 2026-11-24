
<?php
session_start();
require 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

// Obtener cursos del usuario
$stmtCursos = $pdo->prepare("
    SELECT c.id, c.nombre, c.seccion, m.nombre AS materia_nombre, r.nombre AS rol,
           (SELECT COUNT(*) FROM tareas t WHERE t.curso_id = c.id) AS total_tareas
    FROM curso_usuarios cu
    JOIN cursos c ON cu.curso_id = c.id
    JOIN materias m ON c.materia_id = m.id
    JOIN roles r ON cu.rol_id = r.id
    WHERE cu.usuario_id = ?
");
$stmtCursos->execute([$usuario_id]);
$cursos = $stmtCursos->fetchAll();

// Obtener tareas de los cursos del usuario
$stmtTareas = $pdo->prepare("
    SELECT t.id, t.titulo, t.descripcion, t.fecha_limite, c.nombre AS curso_nombre
    FROM tareas t
    JOIN cursos c ON t.curso_id = c.id
    JOIN curso_usuarios cu ON cu.curso_id = c.id
    WHERE cu.usuario_id = ?
    ORDER BY t.fecha_limite ASC
");
$stmtTareas->execute([$usuario_id]);
$tareas = $stmtTareas->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vanguard - Dashboard</title>
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

    <nav class="gov-nav">
        <div class="gov-container">
            <ul class="gov-nav-list">
                <li class="gov-nav-item"><a href="index.php" class="active">HOME</a></li>
                <li class="gov-nav-item"><a href="newsbox.html">NEWS BOX</a></li>
                <li class="gov-nav-item"><a href="classes.php">CLASSES</a></li>
                <li class="gov-nav-item"><a href="profile.php">SETTINGS</a></li>
            </ul>
        </div>
    </nav>

    <main class="gov-container">

        <section class="gov-card">
            <h2 class="gov-card-title">Classes</h2>
            <div class="gov-grid">
                <?php if (empty($cursos)): ?>
                    <p>No estás inscrito en ninguna clase aún.</p>
                <?php else: ?>
                    <?php foreach ($cursos as $curso): ?>
                        <article class="gov-card">
                            <h3><a href="classes.php?curso_id=<?= $curso['id'] ?>" style="text-decoration: none; color: inherit;"><?= htmlspecialchars($curso['nombre']) ?></a></h3>
                            <p class="gov-hint"><?= htmlspecialchars($curso['materia_nombre']) ?> <?= $curso['seccion'] ? '- ' . htmlspecialchars($curso['seccion']) : '' ?></p>
                            <div class="gov-alert gov-alert-info">
                                <?= $curso['total_tareas'] ?> tareas asignadas (Rol: <?= htmlspecialchars($curso['rol']) ?>)
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>


        <section class="gov-card">
            <h2 class="gov-card-title">Tasks</h2>
            <div class="gov-table-wrapper">
                <table class="gov-table">
                    <thead>
                        <tr>
                            <th>Task Name</th>
                            <th>Description</th>
                            <th>Expiration Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tareas)): ?>
                            <tr>
                                <td colspan="3">No hay tareas pendientes.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tareas as $tarea): ?>
                                <tr>
                                    <td><a href="task_detail.php?id=<?= $tarea['id'] ?>"><strong><?= htmlspecialchars($tarea['titulo']) ?></strong></a> (<?= htmlspecialchars($tarea['curso_nombre']) ?>)</td>
                                    <td><?= htmlspecialchars($tarea['descripcion'] ?? '') ?></td>
                                    <td><span class="gov-badge gov-badge-primary"><?= $tarea['fecha_limite'] ? date('d/m/Y H:i', strtotime($tarea['fecha_limite'])) : 'Sin límite' ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <footer class="gov-footer">
        <div class="gov-container">
            <ul class="gov-footer-links">
                <li><a href="#">Ayuda</a></li>
                <li><a href="#">Términos y Condiciones</a></li>
                <li><a href="#">Privacidad</a></li>
            </ul>
        </div>
    </footer>

</body>
</html>