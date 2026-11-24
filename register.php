<?php
session_start();
require 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm-password'] ?? '';

    if (empty($nombre) || empty($email) || strlen($password) < 8) {
        $error = "Complete todos los campos y asegúrese de que la contraseña tenga al menos 8 caracteres.";
    } elseif ($password !== $confirm) {
        $error = "Las contraseñas no coinciden.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "El correo electrónico ya está registrado.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password_hash) VALUES (?, ?, ?)");
            $stmt->execute([$nombre, $email, $hash]);
            header("Location: login.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vanguard - Registro de cuenta</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>

    <header class="gov-header">
        <div class="gov-container">
            <a href="#" class="gov-header-brand">
                <div>
                    <h1 class="gov-header-title gov-brand-title">Vanguard</h1>
                    <div class="gov-header-subtitle">Portal Oficial Educativo "Vanguard"</div>
                </div>
            </a>
        </div>
    </header>

    <nav class="gov-nav">
        <div class="gov-container">
            <ul class="gov-nav-list">
                <li class="gov-nav-item"><a href="#">Inicio</a></li>
                <li class="gov-nav-item"><a href="#">Servicios</a></li>
                <li class="gov-nav-item"><a href="#">Trámites</a></li>
                <li class="gov-nav-item"><a href="#">Contacto</a></li>
            </ul>
        </div>
    </nav>

    <main class="gov-container gov-main-spacing">
        <div class="gov-card">
            <h1>Registro de cuenta</h1>
            <p>Cree su cuenta oficial para acceder a los servicios de la plataforma.</p>

            <?php if ($error): ?>
                <div class="gov-alert gov-alert-info" style="margin-bottom: 1rem;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" novalidate>
                <div class="gov-form-group">
                    <label for="nombre" class="gov-label">Nombre de usuario</label>
                    <input type="text" id="nombre" name="nombre" class="gov-input" required autocomplete="name">
                </div>

                <div class="gov-form-group">
                    <label for="email" class="gov-label">Correo electrónico</label>
                    <div class="gov-hint">Utilice su dirección de correo institucional o personal habitual.</div>
                    <input type="email" id="email" name="email" class="gov-input" required autocomplete="email">
                </div>

                <div class="gov-form-group">
                    <label for="password" class="gov-label">Contraseña</label>
                    <div class="gov-hint">Debe tener al menos 8 caracteres.</div>
                    <input type="password" id="password" name="password" class="gov-input" required autocomplete="new-password">
                </div>

                <div class="gov-form-group">
                    <label for="confirm-password" class="gov-label">Confirmar contraseña</label>
                    <input type="password" id="confirm-password" name="confirm-password" class="gov-input" required autocomplete="new-password">
                </div>

                <button type="submit" class="gov-btn gov-btn-primary">Crear cuenta</button>
            </form>

            <p class="gov-register-prompt">
                ¿Ya tiene una cuenta? <a href="login.php">Iniciar sesión</a>
            </p>
        </div>
    </main>

    <footer class="gov-footer">
        <div class="gov-container">
            <ul class="gov-footer-links">
                <li><a href="#">Política de Privacidad</a></li>
                <li><a href="#">Términos y Condiciones</a></li>
                <li><a href="#">Accesibilidad</a></li>
                <li><a href="#">Contacto institucional</a></li>
            </ul>
            <p>&copy; 2026 Vanguard. Todos los derechos reservados.</p>
        </div>
    </footer>

</body>
</html>