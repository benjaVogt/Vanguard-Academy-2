<?php
session_start();
require 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$tarea_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Obtener datos de la tarea y verificar relación del usuario con el curso
$stmt = $pdo->prepare("
    SELECT t.*, c.id AS curso_id, c.nombre AS curso_nombre, m.nombre AS materia_nombre,
           cu.id AS curso_usuario_id, r.nombre AS rol
    FROM tareas t
    JOIN cursos c ON t.curso_id = c.id
    JOIN materias m ON c.materia_id = m.id
    JOIN curso_usuarios cu ON cu.curso_id = c.id
    JOIN roles r ON cu.rol_id = r.id
    WHERE t.id = ? AND cu.usuario_id = ?
");
$stmt->execute([$tarea_id, $usuario_id]);
$tarea = $stmt->fetch();

if (!$tarea) {
    header("Location: index.php");
    exit();
}

$mensaje = '';

// LÓGICA ALUMNO: Entregar o actualizar tarea
if ($tarea['rol'] === 'alumno' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['entregar_tarea'])) {
    $contenido = trim($_POST['contenido_texto'] ?? '');
    if (!empty($contenido)) {
        $stmtEnt = $pdo->prepare("
            INSERT INTO entregas (tarea_id, curso_usuario_id, contenido_texto, entregado, entregado_en)
            VALUES (?, ?, ?, 1, CURRENT_TIMESTAMP)
            ON DUPLICATE KEY UPDATE contenido_texto = VALUES(contenido_texto), entregado = 1, entregado_en = CURRENT_TIMESTAMP
        ");
        $stmtEnt->execute([$tarea_id, $tarea['curso_usuario_id'], $contenido]);
        $mensaje = "Tarea entregada correctamente.";
    }
}

// LÓGICA DOCENTE: Guardar comentario (feedback) y nota
if ($tarea['rol'] === 'docente' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['calificar_entrega'])) {
    $entrega_id = (int)$_POST['entrega_id'];
    $feedback = trim($_POST['feedback'] ?? '');
    $nota = isset($_POST['nota']) && $_POST['nota'] !== '' ? (float)$_POST['nota'] : 0.00;

    $stmtCal = $pdo->prepare("
        INSERT INTO calificaciones (entrega_id, curso_usuario_id, nota, feedback, calificado_en)
        VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
        ON DUPLICATE KEY UPDATE curso_usuario_id = VALUES(curso_usuario_id), nota = VALUES(nota), feedback = VALUES(feedback), calificado_en = CURRENT_TIMESTAMP
    ");
    $stmtCal->execute([$entrega_id, $tarea['curso_usuario_id'], $nota, $feedback]);
    $mensaje = "Comentario y calificación guardados correctamente.";
}

// Consultar datos según rol
$mi_entrega = null;
$entregas_alumnos = [];

if ($tarea['rol'] === 'alumno') {
    $stmtMi = $pdo->prepare("
        SELECT e.*, cal.nota, cal.feedback, cal.calificado_en
        FROM entregas e
        LEFT JOIN calificaciones cal ON cal.entrega_id = e.id
        WHERE e.tarea_id = ? AND e.curso_usuario_id = ?
    ");
    $stmtMi->execute([$tarea_id, $tarea['curso_usuario_id']]);
    $mi_entrega = $stmtMi->fetch();
} else {
    $stmtAll = $pdo->prepare("
        SELECT e.*, u.nombre AS alumno_nombre, u.email AS alumno_email,
               cal.nota, cal.feedback, cal.calificado_en
        FROM entregas e
        JOIN curso_usuarios cu ON e.curso_usuario_id = cu.id
        JOIN usuarios u ON cu.usuario_id = u.id
        LEFT JOIN calificaciones cal ON cal.entrega_id = e.id
        WHERE e.tarea_id = ?
        ORDER BY e.entregado_en DESC
    ");
    $stmtAll->execute([$tarea_id]);
    $entregas_alumnos = $stmtAll->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vanguard - <?= htmlspecialchars($tarea['titulo']) ?></title>
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
                    <li class="gov-nav-item"><a href="classes.php?curso_id=<?= $tarea['curso_id'] ?>" class="active">CLASSES</a></li>
                </ul>
            </nav>

            <div class="gov-card" style="margin-top: 1rem; padding: 0.75rem;">
                <div>
                    <strong><?= htmlspecialchars($tarea['curso_nombre']) ?></strong>
                    <p class="gov-hint" style="margin: 0;"><?= htmlspecialchars($tarea['materia_nombre']) ?></p>
                </div>
            </div>

            <div style="margin-top: 2rem;">
                <a href="profile.php" class="gov-btn gov-btn-secondary">SETTINGS</a>
            </div>
        </aside>

        <main style="flex: 1;">
            <?php if ($mensaje): ?>
                <div class="gov-alert gov-alert-info" style="margin-bottom: 1rem;">
                    <?= htmlspecialchars($mensaje) ?>
                </div>
            <?php endif; ?>

            <!-- Detalle de la tarea -->
            <div class="gov-card" style="margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h2 style="margin: 0;"><?= htmlspecialchars($tarea['titulo']) ?></h2>
                    <span class="gov-badge gov-badge-primary">
                        Vence: <?= $tarea['fecha_limite'] ? date('d/m/Y H:i', strtotime($tarea['fecha_limite'])) : 'Sin límite' ?>
                    </span>
                </div>
                <p class="gov-hint">Puntaje máximo: <?= htmlspecialchars($tarea['puntaje_maximo']) ?></p>
                <p><?= nl2br(htmlspecialchars($tarea['descripcion'] ?? 'Sin descripción adicional.')) ?></p>
            </div>

            <?php if ($tarea['rol'] === 'alumno'): ?>
                <!-- VISTA DEL ALUMNO: Entregar Tarea -->
                <div class="gov-card">
                    <h3>Tu Entrega</h3>

                    <?php if ($mi_entrega): ?>
                        <div class="gov-alert gov-alert-info" style="margin-bottom: 1rem;">
                            Entregado el <?= date('d/m/Y H:i', strtotime($mi_entrega['entregado_en'])) ?>
                        </div>

                        <?php if ($mi_entrega['feedback'] !== null || $mi_entrega['nota'] !== null): ?>
                            <div class="gov-card" style="margin-bottom: 1rem; border-left: 4px solid var(--gov-border-color);">
                                <strong>Devolución del Profesor (Nota: <?= htmlspecialchars($mi_entrega['nota']) ?> / <?= htmlspecialchars($tarea['puntaje_maximo']) ?>)</strong>
                                <p style="margin-top: 0.5rem; margin-bottom: 0;"><?= nl2br(htmlspecialchars($mi_entrega['feedback'] ?? 'Sin comentarios.')) ?></p>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <form method="POST" action="task_detail.php?id=<?= $tarea_id ?>">
                        <input type="hidden" name="entregar_tarea" value="1">
                        <div class="gov-form-group">
                            <label for="contenido_texto" class="gov-label">Respuesta / Desarrollo de la tarea</label>
                            <div class="gov-hint">Escriba su resolución o pegue el enlace a su trabajo.</div>
                            <textarea id="contenido_texto" name="contenido_texto" class="gov-input" rows="6" required><?= htmlspecialchars($mi_entrega['contenido_texto'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" class="gov-btn gov-btn-primary">
                            <?= $mi_entrega ? 'Actualizar Entrega' : 'Entregar Tarea' ?>
                        </button>
                    </form>
                </div>

            <?php else: ?>
                <!-- VISTA DEL PROFESOR: Revisar entregas y escribir comentario -->
                <div class="gov-card">
                    <h3>Entregas de los Alumnos (<?= count($entregas_alumnos) ?>)</h3>

                    <?php if (empty($entregas_alumnos)): ?>
                        <p>Ningún alumno ha entregado esta tarea todavía.</p>
                    <?php else: ?>
                        <?php foreach ($entregas_alumnos as $ent): ?>
                            <article class="gov-card" style="margin-bottom: 1rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <strong><?= htmlspecialchars($ent['alumno_nombre']) ?></strong>
                                    <span class="gov-badge"><?= date('d/m/Y H:i', strtotime($ent['entregado_en'])) ?></span>
                                </div>
                                <p class="gov-hint" style="margin-top: 0;"><?= htmlspecialchars($ent['alumno_email']) ?></p>

                                <div style="border: 1px solid var(--gov-border-color); padding: 0.75rem; border-radius: var(--gov-border-radius); margin-bottom: 1rem;">
                                    <?= nl2br(htmlspecialchars($ent['contenido_texto'])) ?>
                                </div>

                                <form method="POST" action="task_detail.php?id=<?= $tarea_id ?>">
                                    <input type="hidden" name="calificar_entrega" value="1">
                                    <input type="hidden" name="entrega_id" value="<?= $ent['id'] ?>">

                                    <div class="gov-form-group">
                                        <label class="gov-label" for="nota_<?= $ent['id'] ?>">Calificación (Max: <?= htmlspecialchars($tarea['puntaje_maximo']) ?>)</label>
                                        <input type="number" step="0.01" max="<?= htmlspecialchars($tarea['puntaje_maximo']) ?>" min="0" id="nota_<?= $ent['id'] ?>" name="nota" class="gov-input" value="<?= htmlspecialchars($ent['nota'] ?? '') ?>" required>
                                    </div>

                                    <div class="gov-form-group">
                                        <label class="gov-label" for="feedback_<?= $ent['id'] ?>">Comentario del profesor</label>
                                        <textarea id="feedback_<?= $ent['id'] ?>" name="feedback" class="gov-input" rows="3" placeholder="Escribe un comentario sobre la entrega del alumno..."><?= htmlspecialchars($ent['feedback'] ?? '') ?></textarea>
                                    </div>

                                    <button type="submit" class="gov-btn gov-btn-primary">Guardar Comentario y Nota</button>
                                </form>
                            </article>
                        <?php endforeach; ?>
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