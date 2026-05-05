<?php
session_start();
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = 'admin' LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_name'] = $user['username'];
        header('Location: index.php');
        exit;
    } else {
        $error = '用户名或密码错误';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员登录 - <?php echo SITE_NAME; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0f0f1a; color: #e8e8f0; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-box { background: rgba(20,20,35,0.95); border: 1px solid rgba(0,245,255,0.2); border-radius: 12px; padding: 40px; width: 100%; max-width: 400px; }
        h1 { color: #00f5ff; font-size: 1.5rem; margin-bottom: 30px; text-align: center; text-shadow: 0 0 20px rgba(0,245,255,0.5); }
        input { width: 100%; padding: 12px 16px; margin-bottom: 16px; background: rgba(0,0,0,0.4); border: 1px solid rgba(0,245,255,0.3); border-radius: 8px; color: #e8e8f0; font-size: 1rem; outline: none; transition: border-color 0.3s; }
        input:focus { border-color: #00f5ff; }
        button { width: 100%; padding: 14px; background: linear-gradient(135deg, #00f5ff, #00a8cc); border: none; border-radius: 8px; color: #0f0f1a; font-size: 1rem; font-weight: bold; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; }
        button:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,245,255,0.4); }
        .error { background: rgba(255,50,50,0.2); border: 1px solid rgba(255,50,50,0.5); color: #ff6b6b; padding: 12px; border-radius: 8px; margin-bottom: 16px; text-align: center; }
        .back { display: block; text-align: center; margin-top: 20px; color: rgba(255,255,255,0.5); text-decoration: none; font-size: 0.9rem; }
        .back:hover { color: #00f5ff; }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>🔐 管理员登录</h1>
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="管理员账号" required autofocus>
            <input type="password" name="password" placeholder="密码" required>
            <button type="submit">登 录</button>
        </form>
        <a href="../index.php" class="back">← 返回首页</a>
    </div>
</body>
</html>