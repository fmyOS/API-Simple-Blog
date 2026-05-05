<?php
/**
 * YOUR_NAME - 友链
 */
require_once 'config.php';
$pdo = getDB();
$stmt = $pdo->query("SELECT * FROM links WHERE status = 'active' ORDER BY sort_order ASC, id DESC");
$links = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>友情链接 - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
</head>
<body>
    <canvas id="particles"></canvas>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="hero-section">
        <h1 class="glitch-text" data-text="友情链接">友情链接</h1>
        <p class="hero-subtitle">认识很多有趣的朋友，一起玩耍吧~ 🤝</p>
    </div>
    
    <div class="container" style="position:relative;z-index:1;">
        <div class="links-grid">
            <?php foreach ($links as $link): ?>
            <div class="link-card">
                <div class="link-icon">
                    <?php
                    if ($link['logo']):
                        echo "<img src='" . e($link['logo']) . "' alt='" . e($link['name']) . "'>";
                    else:
                        echo substr(e($link['name'] ?? ''), 0, 2);
                    endif;
                    ?>
                </div>
                <h3><a href="<?= e($link['url']) ?>" target="_blank"><?= e($link['name']) ?></a></h3>
                <p><?= e($link['description']) ?></p>
                <a href="<?= e($link['url']) ?>" target="_blank" class="link-btn">访问网站 →</a>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="card" style="margin-top: 40px;">
            <div class="card-header"><h2>🤝 申请友链</h2></div>
            <div class="card-body">
                <p>如果你想交换友链，请通过以下方式联系我：</p>
                <ul class="apply-list">
                    <li>📧 发送邮件到 admin@YOUR_NAME.dev</li>
                    <li>📝 在留言板留言 "申请友链"</li>
                    <li>🐙 在 GitHub 提交 Issue</li>
                </ul>
                <p class="text-muted">友链要求：有独立域名、内容健康、定期更新</p>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
</body>
</html>
