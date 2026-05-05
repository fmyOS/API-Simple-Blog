<?php
/**
 * YOUR_NAME - 联系方式
 */
require_once 'config.php';
$pdo = getDB();
$stmt = $pdo->query("SELECT * FROM contact_info ORDER BY sort_order ASC");
$contacts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>联系方式 - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
</head>
<body>
    <canvas id="particles"></canvas>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="hero-section">
        <h1 class="glitch-text" data-text="联系我">联系我</h1>
        <p class="hero-subtitle">有任何问题或合作，随时找我喵~ 💌</p>
    </div>
    
    <div class="container" style="position:relative;z-index:1;">
        <div class="contact-grid">
            <?php foreach ($contacts as $c): ?>
            <div class="contact-card">
                <div class="contact-icon">
                    <?php
                    $icons = [
                        'mail' => '📧', 'github' => '🐙', 'twitter' => '🐦',
                        'bilibili' => '📺', 'wechat' => '💬', 'telegram' => '✈️'
                    ];
                    echo $icons[$c['icon']] ?? '📌';
                    ?>
                </div>
                <h3><?= e($c['label']) ?></h3>
                <p class="contact-value">
                    <?php if (strpos($c['value'], 'http') === 0): ?>
                        <a href="<?= e($c['value']) ?>" target="_blank"><?= e($c['value']) ?></a>
                    <?php else: ?>
                        <?= e($c['value']) ?>
                    <?php endif; ?>
                </p>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- 联系表单 -->
        <div class="card" style="margin-top: 40px;">
            <div class="card-header"><h2>💌 给我发邮件</h2></div>
            <div class="card-body">
                <form id="contactForm" class="contact-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label>姓名</label>
                            <input type="text" id="cf-name" required>
                        </div>
                        <div class="form-group">
                            <label>邮箱</label>
                            <input type="email" id="cf-email" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>主题</label>
                        <input type="text" id="cf-subject" required>
                    </div>
                    <div class="form-group">
                        <label>内容</label>
                        <textarea id="cf-message" rows="6" required></textarea>
                    </div>
                    <button type="submit" class="btn-cyber">发送邮件 💌</button>
                </form>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
    document.getElementById('contactForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const name = document.getElementById('cf-name').value;
        const email = document.getElementById('cf-email').value;
        const subject = document.getElementById('cf-subject').value;
        const message = document.getElementById('cf-message').value;
        const mailto = `mailto:${contacts[0]?.value || 'admin@YOUR_NAME.dev'}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(`来自: ${name} (${email})\n\n${message}`)}`;
        window.location.href = mailto;
    });
    </script>
</body>
</html>
