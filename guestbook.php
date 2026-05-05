<?php
/**
 * YOUR_NAME - 留言板
 */
require_once 'config.php';
$pdo = getDB();

// 提交留言
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if (empty($name) || empty($content)) {
        $error = '姓名和内容不能为空喵~';
    } else {
        $stmt = $pdo->prepare("INSERT INTO guestbook (author, email, content, ip, status) VALUES (?, ?, ?, ?, 'approved')");
        $stmt->execute([$name, $email, $content, $_SERVER['REMOTE_ADDR'] ?? '']);
        $success = '留言成功喵~ ✨';
    }
}

// 获取已审核留言
$stmt = $pdo->query("SELECT * FROM guestbook WHERE status = 'approved' ORDER BY created_at DESC LIMIT 50");
$guestbooks = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>留言板 - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
</head>
<body>
    <canvas id="particles"></canvas>
    <?php include 'includes/navbar.php'; ?>

    <div class="hero-section">
        <h1 class="glitch-text" data-text="留言板">留言板</h1>
        <p class="hero-subtitle">有什么想说的，留下言吧喵~ 🐾</p>
    </div>

    <div class="container" style="position:relative;z-index:1;">
        <div class="page-grid">
            <div class="page-content">
                <div class="card">
                    <div class="card-header"><h2>📝 最新留言</h2></div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
                        <?php if (!empty($success)): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
                        <?php if (empty($guestbooks)): ?>
                            <p style="color:rgba(255,255,255,0.5);text-align:center;padding:30px 0;">还没有留言，来做第一个喵~ 🐱</p>
                        <?php else: ?>
                            <?php foreach ($guestbooks as $g): ?>
                            <div class="comment-item" style="padding:16px 0;border-bottom:1px solid rgba(255,255,255,0.1);">
                                <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
                                    <span style="color:#00f5ff;font-weight:bold;"><?= e($g['author']) ?></span>
                                    <span style="color:rgba(255,255,255,0.4);font-size:0.8rem;"><?= date('Y-m-d', strtotime($g['created_at'])) ?></span>
                                </div>
                                <p style="color:rgba(255,255,255,0.85);line-height:1.6;"><?= e($g['content']) ?></p>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card" style="margin-top:30px;">
                    <div class="card-header"><h2>💬 发表评论</h2></div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="submit">
                            <div class="form-group">
                                <label>昵称 *</label>
                                <input type="text" name="name" required placeholder="你的昵称喵~">
                            </div>
                            <div class="form-group">
                                <label>邮箱（选填）</label>
                                <input type="email" name="email" placeholder="your@email.com">
                            </div>
                            <div class="form-group">
                                <label>留言内容 *</label>
                                <textarea name="content" rows="5" required placeholder="说点什么吧喵~"></textarea>
                            </div>
                            <button type="submit" class="btn-cyber">提交留言 🐾</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
