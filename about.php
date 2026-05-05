<?php
/**
 * YOUR_NAME - 关于我
 */
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>关于我 - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
</head>
<body>
    <canvas id="particles"></canvas>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="hero-section">
        <h1 class="glitch-text" data-text="关于我">关于我</h1>
        <p class="hero-subtitle">一个热爱技术的二次元IT小伙 🐱</p>
    </div>
    
    <div class="container" style="position:relative;z-index:1;">
        <div class="card">
            <div class="card-body about-content">
                <div class="about-avatar">
                    <div class="avatar-placeholder">🐱</div>
                </div>
                
                <h2>YOUR_NAME</h2>
                <p class="about-desc">
                    欢迎来到YOUR_NAME！这是一个纯 PHP 构建的赛博朋克风格博客。
                    这里记录着我的技术学习、生活感悟和一些有趣的项目。
                </p>
                
                <div class="about-stats">
                    <div class="stat-item">
                        <div class="stat-number" id="stat-posts">2</div>
                        <div class="stat-label">文章</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number" id="stat-categories">3</div>
                        <div class="stat-label">分类</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number" id="stat-tags">5</div>
                        <div class="stat-label">标签</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number" id="stat-days">1</div>
                        <div class="stat-label">天数</div>
                    </div>
                </div>
                
                <h3>🛠️ 技术栈</h3>
                <div class="tech-tags">
                    <span class="tech-tag">PHP</span>
                    <span class="tech-tag">MySQL</span>
                    <span class="tech-tag">Nginx</span>
                    <span class="tech-tag">JavaScript</span>
                    <span class="tech-tag">CSS</span>
                    <span class="tech-tag">HTML</span>
                    <span class="tech-tag">Git</span>
                    <span class="tech-tag">Linux</span>
                </div>
                
                <h3>🎯 本站特色</h3>
                <div class="features-list">
                    <div class="feature-item">
                        <span class="feature-icon">✨</span>
                        <div>
                            <strong>赛博朋克风格</strong>
                            <p>暗色主题 + 霓虹光效 + 粒子背景</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">📡</span>
                        <div>
                            <strong>RESTful API</strong>
                            <p>完整的 API 接口，支持远程文章管理</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">📱</span>
                        <div>
                            <strong>响应式设计</strong>
                            <p>手机、平板、电脑都能完美显示</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">🐱</span>
                        <div>
                            <strong>Your Theme</strong>
                            <p>Your custom content</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
</body>
</html>
