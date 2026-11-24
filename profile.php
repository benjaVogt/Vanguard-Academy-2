<?php
session_start();
require 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nombre'])) {
    $nuevo_nombre = trim($_POST['nombre']);
    if (!empty($nuevo_nombre)) {
        $stmtUpd = $pdo->prepare("UPDATE usuarios SET nombre = ? WHERE id = ?");
        $stmtUpd->execute([$nuevo_nombre, $usuario_id]);
        $_SESSION['usuario_nombre'] = $nuevo_nombre;
        $mensaje = "Datos actualizados correctamente.";
    }
}

$stmt = $pdo->prepare("
    SELECT u.*, d.nombre AS division_nombre, a.nombre AS anio_nombre, e.nombre AS esp_nombre
    FROM usuarios u
    LEFT JOIN divisiones d ON u.division_id = d.id
    LEFT JOIN anios a ON d.anio_id = a.id
    LEFT JOIN especialidades e ON d.especialidad_id = e.id
    WHERE u.id = ?
");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vanguard - Profile / Settings</title>
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

    <main class="gov-container">

        <div class="gov-login-wrapper">
            <div class="gov-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--gov-spacing-md);">
                    <div>
                        <h3 style="margin: 0;"><?= htmlspecialchars($usuario['nombre']) ?></h3>
                        <p class="gov-hint" style="margin: 0;"><?= htmlspecialchars($usuario['email']) ?></p>
                    </div>
                </div>

                <?php if ($mensaje): ?>
                    <div class="gov-alert gov-alert-info" style="margin-bottom: 1rem;">
                        <?= htmlspecialchars($mensaje) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="profile.php">
                    <div class="gov-form-group" style="border: 1px solid var(--gov-border-color); padding: 1rem; border-radius: var(--gov-border-radius); text-align: center; margin-bottom: var(--gov-spacing-lg);">
                        <p style="margin: 0 0 0.5rem 0; font-weight: 600;">
                            <?= $usuario['division_id'] ? htmlspecialchars($usuario['anio_nombre'] . ' "' . $usuario['division_nombre'] . '" - ' . $usuario['esp_nombre']) : 'Usuario Institucional' ?>
                        </p>
                        <input type="text" name="nombre" class="gov-input" value="<?= htmlspecialchars($usuario['nombre']) ?>" required>
                    </div>

                    <div style="display: flex; justify-content: space-between; gap: var(--gov-spacing-sm);">
                        <button type="submit" class="gov-btn gov-btn-primary" style="width: auto;">SAVE</button>
                        <a href="profile.php?logout=1" class="gov-btn gov-btn-secondary" style="width: auto;">CERRAR SESIÓN</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

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