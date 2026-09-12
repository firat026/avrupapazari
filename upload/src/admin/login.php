<?php
/**
 * admin/login.php
 * Konum: /admin/login.php
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass = trim($_POST['password'] ?? '');
    
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, password, role FROM users WHERE email = ? AND role IN ('admin','moderator') AND is_active = 1 AND is_banned = 0 LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
   if ($user && password_verify($pass, $user['password'])) {
    $_SESSION['admin_id'] = $user['id'];
    $_SESSION['admin_name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['admin_email'] = $user['email'];
    $_SESSION['admin_role'] = $user['role'];
    header('Location: ' . BASE_URL . '/admin/index.php');
    exit;
} else {
            $error = 'E-posta veya şifre hatalı.';
        }
    } catch (Exception $e) {
        $error = 'Hata: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Giriş - AvrupaPazari</title>
<link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Instrument Sans',system-ui,sans-serif;background:#f7faf8;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
.login-card{background:#fff;border:1px solid #dce8e2;border-radius:16px;padding:40px;width:100%;max-width:400px;box-shadow:0 4px 24px rgba(26,43,35,0.06)}
.login-logo{font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:1.3rem;color:#1a2b23;letter-spacing:-0.04em;text-align:center;margin-bottom:8px}
.login-logo em{font-style:normal;color:#1d7a4e}
.login-sub{text-align:center;font-size:0.85rem;color:#7a9488;margin-bottom:28px}
.field{margin-bottom:16px}
.field label{display:block;font-size:0.75rem;font-weight:700;color:#4a6355;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px}
.field input{width:100%;height:44px;border:1.5px solid #dce8e2;border-radius:10px;padding:0 14px;font-family:inherit;font-size:0.9rem;color:#1a2b23;transition:border-color 0.2s}
.field input:focus{outline:none;border-color:#1d7a4e;box-shadow:0 0 0 3px rgba(29,122,78,0.08)}
.login-btn{width:100%;height:46px;border:none;background:#1d7a4e;color:#fff;font-family:inherit;font-size:0.9rem;font-weight:700;border-radius:10px;cursor:pointer;margin-top:8px;transition:background 0.15s,transform 0.15s}
.login-btn:hover{background:#156b42;transform:translateY(-1px)}
.error{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;font-size:0.82rem;padding:10px 14px;border-radius:8px;margin-bottom:16px}
</style>
</head>
<body>
<div class="login-card">
  <div class="login-logo">avrupa<em>pazari</em></div>
  <div class="login-sub">Admin Panel Girişi</div>
  <?php if($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
  <form method="POST">
    <div class="field">
      <label>E-posta</label>
      <input type="email" name="email" required autocomplete="email" value="<?= e($_POST['email']??'') ?>">
    </div>
    <div class="field">
      <label>Şifre</label>
      <input type="password" name="password" required autocomplete="current-password">
    </div>
    <button type="submit" class="login-btn">Giriş Yap</button>
  </form>
</div>
</body>
</html>