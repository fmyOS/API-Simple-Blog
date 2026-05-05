<?php
/**
 * YOUR_NAME - 首页
 */
require_once 'config.php';

$pdo = getDB();
$settings = getSettings();

// 获取当前选中的分类
$selectedCategory = isset($_GET['category']) ? $_GET['category'] : '';

// 获取所有分类
$allCategories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// 计算每个分类的文章数
$categoryCounts = [];
$catRows = $pdo->query("SELECT c.id, c.name, COUNT(p.id) as cnt FROM categories c LEFT JOIN posts p ON c.id = p.category_id AND p.status = 'published' GROUP BY c.id, c.name ORDER BY cnt DESC")->fetchAll();
foreach ($catRows as $row) {
    $categoryCounts[$row['name']] = $row['cnt'];
}

// 分页
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = (int)($settings['posts_per_page'] ?? POSTS_PER_PAGE);
$offset = ($page - 1) * $perPage;

// 构建查询
$whereClauses = ["p.status = 'published'"];
$params = [];
$namedParams = [];

if ($selectedCategory) {
    $catStmt = $pdo->prepare("SELECT id FROM categories WHERE slug = :slug OR name = :name");
    $catStmt->execute([':slug' => $selectedCategory, ':name' => $selectedCategory]);
    $catId = $catStmt->fetchColumn();
    if ($catId) {
        $whereClauses[] = "p.category_id = :catId";
        $namedParams[':catId'] = $catId;
    }
}

$whereSql = implode(' AND ', $whereClauses);

// 获取总数
$countSql = "SELECT COUNT(*) FROM posts p WHERE $whereSql";
$stmt = $pdo->prepare($countSql);
$allParams = array_merge($namedParams, $params);
$stmt->execute($allParams);
$total = $stmt->fetchColumn();
$totalPages = ceil($total / $perPage);

// 获取文章
$sql = "SELECT p.*, c.name as category_name, c.slug as category_slug,
               GROUP_CONCAT(t.name SEPARATOR ', ') as tag_names
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN post_tags pt ON p.id = pt.post_id
        LEFT JOIN tags t ON pt.tag_id = t.id
        WHERE $whereSql
        GROUP BY p.id
        ORDER BY p.published_at DESC
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
foreach ($namedParams as $key => $val) {
    $stmt->bindValue($key, $val, PDO::PARAM_STR);
}
$stmt->execute();
$posts = $stmt->fetchAll();

// 获取标签用于搜索
$tags = $pdo->query("SELECT * FROM tags ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($settings['site_name'] ?? 'YOUR_NAME') ?> - <?= e($settings['site_description'] ?? '一个炫酷的PHP博客') ?></title>
    <meta name="description" content="<?= e($settings['site_description'] ?? '一个炫酷的PHP博客') ?>">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
</head>
<body>
    <!-- 粒子背景 -->
    <canvas id="particles"></canvas>

    <?php include 'includes/navbar.php'; ?>

    <!-- Hero -->
    <header class="hero">
        <div class="hero-content">
            <h1 class="glitch" data-text="<?= e($settings['site_name'] ?? 'YOUR_NAME') ?>"><?= e($settings['site_name'] ?? 'YOUR_NAME') ?></h1>
            <p class="hero-subtitle"><?= e($settings['site_description'] ?? '一个炫酷的PHP博客') ?></p>
        </div>
    </header>

    <!-- 文章列表 -->
    <main class="container">
        <?php if (!empty($selectedCategory)): ?>
            <div class="category-filter-bar">
                <span>📂 分类：</span>
                <a href="index.php" class="btn btn-sm btn-outline active">全部</a>
                <?php foreach ($allCategories as $cat): ?>
                    <a href="index.php?category=<?= urlencode($cat['name']) ?>" class="btn btn-sm <?= $selectedCategory === $cat['name'] ? 'btn-primary' : 'btn-outline' ?>">
                        <?= e($cat['name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="category-filter-bar">
                <span>📂 分类：</span>
                <a href="index.php" class="btn btn-sm btn-outline active">全部</a>
                <?php foreach ($allCategories as $cat): ?>
                    <a href="index.php?category=<?= urlencode($cat['name']) ?>" class="btn btn-sm btn-outline">
                        <?= e($cat['name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($posts)): ?>
            <div class="empty-state">
                <div class="empty-icon">📝</div>
                <h3>暂无文章</h3>
                <p>敬请期待...</p>
            </div>
        <?php else: ?>
            <div class="posts-grid">
                <?php foreach ($posts as $post): ?>
                <article class="post-card"><a href="post.php?slug=<?php echo e($post["slug"]); ?>" class="post-card-link" style="text-decoration:none;color:inherit;display:block">
                    <?php if ($post['cover_image']): ?>
                    <div class="post-cover" style="background-image: url('<?= e($post['cover_image']) ?>')">
                    <?php else: ?>
                    <div class="post-cover" style="background-image: url('http://api.dujin.org/bing/1366.php')">
                    <?php endif; ?>
                        <span class="post-category"><?= e($post['category_name'] ?? '未分类') ?></span>
                    </div>
                    <div class="post-content">
                        <h2 class="post-title"><?= e($post["title"]) ?></h2>
                        <p class="post-excerpt"><?= e($post['excerpt'] ?: mb_substr(strip_tags($post['content']), 0, 100)) ?></p>
                        <div class="post-meta">
                            <span>📅 <?= date('Y-m-d', strtotime($post['published_at'] ?? $post['created_at'])) ?></span>
                            <span>👁️ <?= $post['view_count'] ?></span>
                            <?php if (!empty($post['tag_names'])): ?>
                                <span class="post-tags">
                                    <?php foreach (explode(',', $post['tag_names']) as $tag): ?>
                                        <span class="tag"><?= e(trim($tag)) ?></span>
                                    <?php endforeach; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a></article>
                <?php endforeach; ?>
            </div>

            <!-- 分页 -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?><?= $selectedCategory ? '&category=' . urlencode($selectedCategory) : '' ?>" class="btn btn-ghost">← 上一页</a>
                <?php endif; ?>
                <span class="page-info">第 <?= $page ?> / <?= $totalPages ?> 页</span>
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?><?= $selectedCategory ? '&category=' . urlencode($selectedCategory) : '' ?>" class="btn btn-ghost">下一页 →</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <!-- 侧边栏触发按钮 -->
    <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">☰</button>

    <!-- 侧边栏遮罩 -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- 侧边栏 -->
    <aside class="sidebar sidebar-outside" id="sidebar">
        <button class="sidebar-close" onclick="toggleSidebar()">✕</button>

        <!-- 分类列表 -->
        <div class="sidebar-card">
            <h3 class="sidebar-title">📂 分类</h3>
            <ul class="sidebar-list">
                <li>
                    <a href="index.php" class="sidebar-link <?= empty($selectedCategory) ? 'active' : '' ?>">
                        <span class="sidebar-label">全部文章</span>
                        <span class="sidebar-count"><?= $total ?></span>
                    </a>
                </li>
                <?php foreach ($allCategories as $cat): ?>
                <li>
                    <a href="index.php?category=<?= urlencode($cat['name']) ?>" class="sidebar-link <?= $selectedCategory === $cat['name'] ? 'active' : '' ?>">
                        <span class="sidebar-label"><?= e($cat['name']) ?></span>
                        <span class="sidebar-count"><?= $categoryCounts[$cat['name']] ?? 0 ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- 标签云 -->
        <div class="sidebar-card">
            <h3 class="sidebar-title">🏷️ 标签</h3>
            <div class="tag-cloud">
                <?php foreach ($tags as $tag): ?>
                    <a href="index.php?tag=<?= urlencode($tag['name']) ?>" class="sidebar-tag"><?= e($tag['name']) ?></a>
                <?php endforeach; ?>
                <?php if (empty($tags)): ?>
                    <span class="tag-empty">暂无标签</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- 站点信息 -->
        <div class="sidebar-card">
            <h3 class="sidebar-title">📊 站点统计</h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-value"><?= $total ?></span>
                    <span class="stat-label">文章</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?= count($allCategories) ?></span>
                    <span class="stat-label">分类</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?= count($tags) ?></span>
                    <span class="stat-label">标签</span>
                </div>
            </div>
        </div>
    </aside>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/particles.js"></script>
</body>
</html>
