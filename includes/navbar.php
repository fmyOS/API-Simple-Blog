<?php
/**
 * YOUR_NAME - 导航栏
 */
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>

<!-- 阅读进度条 -->
<div class="reading-progress" id="readingProgress"></div>

<nav class="navbar" id="navbar">
    <div class="nav-container">
        <a href="index.php" class="nav-logo">🐱 <?= SITE_NAME ?></a>
        <div class="nav-links" id="navLinks">
            <a href="index.php" class="<?= $currentPage === 'index' ? 'active' : '' ?>">首页</a>
            <a href="guestbook.php" class="<?= $currentPage === 'guestbook' ? 'active' : '' ?>">留言板</a>
            <a href="links.php" class="<?= $currentPage === 'links' ? 'active' : '' ?>">友链</a>
            <a href="contact.php" class="<?= $currentPage === 'contact' ? 'active' : '' ?>">联系</a>
            <a href="about.php" class="<?= $currentPage === 'about' ? 'active' : '' ?>">关于</a>
        </div>
    </div>
</nav>

<!-- 侧边栏遮罩 -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- 侧边栏 (右侧) -->
<aside class="sidebar" id="sidebar">
    <button class="sidebar-close" onclick="toggleSidebar()">✕</button>

    <!-- 分类列表 -->
    <div class="sidebar-card">
        <h3 class="sidebar-title">📂 分类</h3>
        <ul class="sidebar-list">
            <li>
                <a href="index.php" class="sidebar-link <?= empty($selectedCategory) ? 'active' : '' ?>">
                    <span class="sidebar-label">全部文章</span>
                    <span class="sidebar-count"><?= $total ?? 0 ?></span>
                </a>
            </li>
            <?php if (!empty($allCategories)): ?>
            <?php foreach ($allCategories as $cat): ?>
            <li>
                <a href="index.php?category=<?= urlencode($cat['name']) ?>" class="sidebar-link <?= $selectedCategory === $cat['name'] ? 'active' : '' ?>">
                    <span class="sidebar-label"><?= e($cat['name']) ?></span>
                    <span class="sidebar-count"><?= $categoryCounts[$cat['name']] ?? 0 ?></span>
                </a>
            </li>
            <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>

    <!-- 标签云 -->
    <div class="sidebar-card">
        <h3 class="sidebar-title">🏷️ 标签</h3>
        <div class="tag-cloud">
            <?php if (!empty($tags)): ?>
            <?php foreach ($tags as $tag): ?>
                <a href="index.php?tag=<?= urlencode($tag['name']) ?>" class="sidebar-tag"><?= e($tag['name']) ?></a>
            <?php endforeach; ?>
            <?php else: ?>
                <span class="sidebar-tag" style="opacity:0.5">暂无标签</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- 站点信息 -->
    <div class="sidebar-card">
        <h3 class="sidebar-title">📊 站点统计</h3>
        <div class="stats-grid" style="flex-direction:column;gap:12px">
            <div class="stat-item" style="display:flex;justify-content:space-between;align-items:center">
                <span class="stat-label">文章</span>
                <span class="sidebar-count"><?= $total ?? '-' ?></span>
            </div>
            <div class="stat-item" style="display:flex;justify-content:space-between;align-items:center">
                <span class="stat-label">分类</span>
                <span class="sidebar-count"><?= count($allCategories ?? []) ?></span>
            </div>
            <div class="stat-item" style="display:flex;justify-content:space-between;align-items:center">
                <span class="stat-label">标签</span>
                <span class="sidebar-count"><?= count($tags ?? []) ?></span>
            </div>
        </div>
    </div>
</aside>

<!-- 侧边栏触发按钮 -->
<button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">≡</button>

<!-- 回到顶部 -->
<button class="back-to-top" id="backToTop" onclick="window.scrollTo({top:0,behavior:'smooth'})">↑</button>

<script>
// 侧边栏开关
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (!sidebar || !overlay) return;
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
}

// ESC 关闭侧边栏
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const sidebar = document.getElementById('sidebar');
        if (sidebar && sidebar.classList.contains('active')) {
            toggleSidebar();
        }
    }
});
</script>