<?php
session_start();

$config_path = '/home/Pauladmin/web/axoptec.cl/private/panel_config.php';

// Validar existencia y legibilidad del config del panel
if (!file_exists($config_path)) {
    die("Error de seguridad/ruta: No se puede acceder al archivo en private. Verifica open_basedir de HestiaCP.");
}

$cfg = require $config_path;
$master_pass = $cfg['master_pass'] ?? '';

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['panel_password'])) {
    if (!empty($master_pass) && trim($_POST['panel_password']) === $master_pass) {
        session_regenerate_id(true);
        $_SESSION['master_authenticated'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $error = "Contraseña maestra incorrecta.";
    }
}

// Cerrar sesión
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Si no está autenticado, mostrar formulario de acceso elegante
if (!isset($_SESSION['master_authenticated']) || $_SESSION['master_authenticated'] !== true):
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acceso Panel Maestro - MTS Digital Factory</title>
    <style>
        body { 
            background: #fdfbf7; 
            color: #2c2c2c; 
            font-family: 'Playfair Display', 'Inter', sans-serif; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0; 
        }
        .login-card { 
            background: #ffffff; 
            padding: 40px 30px; 
            border-radius: 16px; 
            border: 1px solid #e6e2db; 
            width: 320px; 
            text-align: center; 
            box-shadow: 0 10px 30px rgba(44, 44, 44, 0.04);
        }
        .login-card h3 {
            margin-top: 0;
            color: #1a1a1a;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        .login-card input { 
            width: 100%; 
            padding: 12px; 
            margin: 15px 0; 
            background: #faf8f5; 
            border: 1px solid #dcd6cd; 
            color: #2c2c2c; 
            border-radius: 8px; 
            box-sizing: border-box; 
            font-size: 0.9rem;
        }
        .login-card input:focus {
            outline: none;
            border-color: #bfa181;
            background: #fff;
        }
        .login-card button { 
            width: 100%; 
            padding: 12px; 
            background: #2c2c2c; 
            color: #ffffff; 
            border: none; 
            border-radius: 8px; 
            font-weight: 500; 
            cursor: pointer; 
            letter-spacing: 0.5px;
            transition: background 0.3s;
        }
        .login-card button:hover { background: #4a4a4a; }
        .error { color: #b94a48; font-size: 0.8rem; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="login-card">
        <h3>Panel Maestro</h3>
        <p style="color:#7a756f; font-size:0.8rem; margin-bottom: 20px;">Acceso de Alta Costura y Gestión</p>
        <?php if (!empty($error)): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST">
            <input type="password" name="panel_password" placeholder="Contraseña Maestra" required autofocus>
            <button type="submit">Ingresar</button>
        </form>
    </div>
</body>
</html>
<?php
    exit();
endif;

// ==========================================
// CÓDIGO DEL PANEL PRINCIPAL (PROTEGIDO)
// ==========================================
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_client') {
        $client_id = trim($_POST['client_id']);
        $client_email = trim($_POST['client_email']);
        $site_url = trim($_POST['site_url']);
        $billing_date = $_POST['billing_date'] ?: null;
        $expiration_date = $_POST['expiration_date'] ?: null;
        $notification_sent = $_POST['notification_sent'] ?? 'no';
        $today_notice_sent = $_POST['today_notice_sent'] ?? 'no';
        $cut_in_progress = $_POST['cut_in_progress'] ?? 'no';
        $status = $_POST['subscription_status'];
        
        $raw_password = trim($_POST['client_password'] ?? '');
        $client_password = !empty($raw_password) ? password_hash($raw_password, PASSWORD_DEFAULT) : null;

        $stmt = $pdo->prepare("INSERT INTO licenses (client_id, client_email, client_password, site_url, billing_date, expiration_date, notification_sent, today_notice_sent, cut_in_progress, subscription_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE client_email = ?, client_password = COALESCE(?, client_password), site_url = ?, billing_date = ?, expiration_date = ?, notification_sent = ?, today_notice_sent = ?, cut_in_progress = ?, subscription_status = ?");
        
        $stmt->execute([
            $client_id, $client_email, $client_password, $site_url, $billing_date, $expiration_date, $notification_sent, $today_notice_sent, $cut_in_progress, $status, 
            $client_email, $client_password, $site_url, $billing_date, $expiration_date, $notification_sent, $today_notice_sent, $cut_in_progress, $status
        ]);
    }
}

$stmt = $pdo->query("SELECT * FROM licenses");
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Maestro - Alta Costura / MTS</title>
    <style>
        body { 
            padding: 35px; 
            color: #2c2c2c; 
            font-size: 0.85rem; 
            background: #fdfbf7; 
            font-family: 'Inter', sans-serif; 
        }
        .header-box { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 25px; 
            border-bottom: 1px solid #e6e2db;
            padding-bottom: 15px;
        }
        .header-box h1 {
            font-weight: 500;
            color: #1a1a1a;
            margin: 0 0 5px 0;
            font-size: 1.4rem;
        }
        .btn-logout { 
            background: #f4ece1; 
            color: #8c6d53; 
            border: 1px solid #dfd3c3; 
            padding: 8px 16px; 
            border-radius: 6px; 
            text-decoration: none; 
            font-weight: 500; 
            font-size: 0.8rem; 
            transition: 0.2s;
        }
        .btn-logout:hover { background: #ebdccb; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 25px; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e6e2db; box-shadow: 0 4px 20px rgba(44,44,44,0.02); }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #f2eee9; }
        th { background: #f5f2ec; color: #59524c; font-weight: 600; letter-spacing: 0.5px; }
        td { color: #3a3835; }

        .badge-active { background: #eaf4ed; color: #2e6930; padding: 4px 10px; border-radius: 20px; font-weight: 500; font-size: 0.75rem; border: 1px solid #c7e2cc; }
        .badge-inactive { background: #fdf2f2; color: #9c3b3b; padding: 4px 10px; border-radius: 20px; font-weight: 500; font-size: 0.75rem; border: 1px solid #f7d6d6; }
        .badge-unique { background: #f0f4f8; color: #3b699c; padding: 4px 10px; border-radius: 20px; font-weight: 500; font-size: 0.75rem; border: 1px solid #d1e2f3; }
        
        .form-box { background: #ffffff; padding: 25px; border-radius: 12px; border: 1px solid #e6e2db; display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; align-items: end; box-shadow: 0 4px 20px rgba(44,44,44,0.02); }
        .form-box input, .form-box select { width: 100%; padding: 10px; background: #faf8f5; border: 1px solid #dcd6cd; color: #2c2c2c; border-radius: 6px; box-sizing: border-box; font-size: 0.85rem; }
        .form-box input:focus, .form-box select:focus { outline: none; border-color: #bfa181; background: #fff; }
        .form-box label { font-weight: 500; color: #6b635b; }
        
        .btn-add { background: #2c2c2c; color: white; border: none; padding: 12px; border-radius: 6px; font-weight: 500; cursor: pointer; grid-column: span 4; letter-spacing: 0.5px; transition: background 0.3s; }
        .btn-add:hover { background: #4a4a4a; }
    </style>
</head>
<body>
    <div class="header-box">
        <div>
            <h1>Panel Maestro - Administración Comercial</h1>
            <p style="color:#7a756f; margin: 0;">Gestión de plataformas, suscripciones y tiendas de alta costura.</p>
        </div>
        <a href="?logout=1" class="btn-logout">Cerrar Sesión</a>
    </div>
    
    <form method="POST" class="form-box">
        <input type="hidden" name="action" value="add_client">
        <div>
            <label>ID del Cliente:</label>
            <input type="text" name="client_id" placeholder="ej. casa_novias" required>
        </div>
        <div>
            <label>Correo:</label>
            <input type="email" name="client_email" placeholder="contacto@novias.cl" required>
        </div>
        <div>
            <label>Contraseña Inicial:</label>
            <input type="password" name="client_password" placeholder="Clave temporal" required>
        </div>
        <div>
            <label>URL del Sitio:</label>
            <input type="text" name="site_url" placeholder="http://tusitio.cl" required>
        </div>
        <div>
            <label>Fecha Facturación:</label>
            <input type="date" name="billing_date">
        </div>
        <div>
            <label>Fecha Vencimiento:</label>
            <input type="date" name="expiration_date">
        </div>
        <div>
            <label>5 Días Ant.:</label>
            <select name="notification_sent">
                <option value="no">No</option>
                <option value="si">Sí</option>
            </select>
        </div>
        <div>
            <label>Hoy Vence:</label>
            <select name="today_notice_sent">
                <option value="no">No</option>
                <option value="si">Sí</option>
            </select>
        </div>
        <div>
            <label>Corte Prog.:</label>
            <select name="cut_in_progress">
                <option value="no">No</option>
                <option value="si">Sí</option>
            </select>
        </div>
        <div>
            <label>Estado:</label>
            <select name="subscription_status">
                <option value="active">Activo (Al día)</option>
                <option value="inactive">Inactivo (En pausa)</option>
            </select>
        </div>
        <button type="submit" class="btn-add">Registrar / Vincular Proyecto</button>
    </form>

    <h2 style="font-weight: 500; color: #1a1a1a; margin-top: 35px;">Listado de Clientes y Proyectos</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Correo</th>
                <th>Sitio Web</th>
                <th>Facturación</th>
                <th>Vencimiento</th>
                <th>5 Días Ant.</th>
                <th>Hoy Vence</th>
                <th>Corte Prog.</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($clients)): ?>
                <tr><td colspan="10" style="text-align:center; color:#7a756f;">No hay clientes registrados en el sistema.</td></tr>
            <?php else: ?>
                <?php foreach ($clients as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['client_id'] ?? '') ?></td>
                    <td><?= htmlspecialchars($c['client_email'] ?? '') ?></td>
                    <td><a href="<?= htmlspecialchars($c['site_url'] ?? '#') ?>" target="_blank" style="color:#8c6d53; text-decoration:none; font-weight: 500;"><?= htmlspecialchars($c['site_url'] ?? '') ?></a></td>
                    <td><?= htmlspecialchars($c['billing_date'] ?? '-') ?></td>
                    <td>
                        <?php if (empty($c['expiration_date'])): ?>
                            <span class="badge-unique">PAGO ÚNICO</span>
                        <?php else: ?>
                            <?= htmlspecialchars($c['expiration_date']) ?>
                        <?php endif; ?>
                    </td>
                    <td><span style="color:<?= ($c['notification_sent'] ?? 'no') === 'si' ? '#b8860b' : '#7a756f' ?>;"><?= strtoupper($c['notification_sent'] ?? 'no') ?></span></td>
                    <td><span style="color:<?= ($c['today_notice_sent'] ?? 'no') === 'si' ? '#b8860b' : '#7a756f' ?>;"><?= strtoupper($c['today_notice_sent'] ?? 'no') ?></span></td>
                    <td><span style="color:<?= ($c['cut_in_progress'] ?? 'no') === 'si' ? '#9c3b3b' : '#7a756f' ?>;"><?= strtoupper($c['cut_in_progress'] ?? 'no') ?></span></td>
                    <td>
                        <?php if (($c['subscription_status'] ?? '') === 'active'): ?>
                            <span class="badge-active">ACTIVO</span>
                        <?php else: ?>
                            <span class="badge-inactive">PAUSADO</span>
                        <?php endif; ?>
                    </td>
                    <td><a href="editar-cliente.php?id=<?= htmlspecialchars($c['client_id'] ?? '') ?>" style="color:#59524c; text-decoration:none; font-weight: 500;">Editar</a></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
